<?php

namespace App\Http\Controllers;

use App\Models\LembarKerjaAlihMedia;
use App\Models\LembarKerjaValidasi;
use App\Models\RiwayatStatus;
use App\Models\Tiket;
use App\Models\TiketRevisi;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ValidatorController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $isBtel = $user->role === 'validator_btel';
        $isSuel = $user->role === 'validator_suel';

        $query = Tiket::with(['jenisPermohonan', 'bidangTanahs', 'lembarKerjaValidasis'])
            ->aktif()
            ->latest();

        $filter = $request->input('filter');
        if ($filter === 'revisi') {
            // Revisi dari Alih Media — filter berdasarkan sub_bidang sesuai role
            $query->whereHas('tiketRevisis', function ($q) use ($isBtel, $isSuel) {
                $q->where('stage_tujuan', 'Validator')->where('status', 'aktif');
                if ($isBtel) {
                    $q->where('sub_bidang', 'pra_btel');
                } elseif ($isSuel) {
                    $q->where('sub_bidang', 'pra_suel');
                }
            });
        } elseif ($filter === 'done') {
            $query->whereIn('status', ['alih_media', 'selesai']);
        } else {
            // Default: tiket yang sedang di Validator
            $query->where(function ($q) {
                $q->where('status', 'validasi')
                    ->orWhereHas('tiketRevisis', fn ($r) => $r->where('stage_tujuan', 'Validator')->where('status', 'aktif'));
            });

            // Filter per sub-role: hanya tampilkan tiket yang masih butuh kerjaan sub-bidang ini
            if ($isBtel) {
                $query->where(function ($q) {
                    $q->whereDoesntHave('lembarKerjaValidasis')
                       ->orWhereHas('lembarKerjaValidasis', fn ($q2) => $q2->where('status_pra_btel', '!=', 'selesai'));
                });
            } elseif ($isSuel) {
                $query->where(function ($q) {
                    $q->whereDoesntHave('lembarKerjaValidasis')
                       ->orWhereHas('lembarKerjaValidasis', fn ($q2) => $q2->where('status_pra_suel', '!=', 'selesai'));
                });
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('no_tiket', 'like', "%{$search}%")
                    ->orWhere('nama_pemohon', 'like', "%{$search}%");
            });
        }

        $tikets = $query->paginate(15)->withQueryString();
        return view('validator.index', compact('tikets', 'filter'));
    }

    /**
     * Detail tiket pada tahap validasi data pertanahan.
     *
     * @param  int  $id  ID tiket.
     */
    public function show($id)
    {
        $tiket = Tiket::with([
            'jenisPermohonan',
            'bidangTanahs',
            'lembarKerjaWarkahs.petugas',
            'lembarKerjaValidasis.validatorBtel',
            'lembarKerjaValidasis.validatorSuel',
            'activeRevisis.creator',
            'diterimaRevisis.confirmer',
        ])->aktif()->where(function ($q) {
            $q->where('status', 'validasi')
                ->orWhereHas('tiketRevisis', fn ($r) => $r->where('stage_tujuan', 'Validator')->where('status', 'aktif'));
        })->findOrFail($id);

        app(NotificationService::class)->tandaiDibacaUntukTiket($tiket, 'validator', Auth::user());

        $validatorsBtel = User::whereIn('role', ['validator_btel', 'admin'])->where('is_active', true)->get();
        $validatorsSuel = User::whereIn('role', ['validator_suel', 'admin'])->where('is_active', true)->get();

        // Konteks role: sub-bidang yang ditangani akun yang sedang login
        // Role generik 'validator' menangani KEDUA sub-bidang (seperti admin dalam lingkup Validator).
        $user = Auth::user();
        $isAdmin = in_array($user->role, ['admin', 'validator']);
        $isBtel = in_array($user->role, ['admin', 'validator', 'validator_btel']);
        $isSuel = in_array($user->role, ['admin', 'validator', 'validator_suel']);

        return view('validator.show', compact('tiket', 'validatorsBtel', 'validatorsSuel', 'isAdmin', 'isBtel', 'isSuel'));
    }

    /**
     * Proses validasi data (setuju / kembali warkah / tolak).
     *
     * @param  int  $id  ID tiket.
     */
    public function updateValidasi(Request $request, $id)
    {
        $tiket = Tiket::findOrFail($id);

        $user = Auth::user();
        $isAdmin = $user->role === 'admin';
        $isBtel = $user->role === 'validator_btel';
        $isSuel = $user->role === 'validator_suel';

        // Validasi hanya field sub-bidang milik akun yang sedang login (BTel / SuEl / admin keduanya)
        // Role generik 'validator' dapat mengisi KEDUA sub-bidang.
        $canBtel = in_array($user->role, ['admin', 'validator', 'validator_btel']);
        $canSuel = in_array($user->role, ['admin', 'validator', 'validator_suel']);

        $rules = [
            'action_type' => 'required|in:save_draft,forward_to_alih_media,return_to_verifikator,return_to_warkah,reject',
            'catatan_validator' => 'nullable|string',
            'revisi_pesan' => 'nullable|string',
            'items' => 'required|array',
            'items.*.bidang_id' => 'required|exists:bidang_tanahs,id',
            'items.*.catatan' => 'nullable|string',
        ];

        if ($canBtel) {
            $rules['validator_btel_id'] = 'required|exists:users,id';
            $rules['items.*.status_pra_btel'] = 'required|in:belum,proses,selesai';
        }
        if ($canSuel) {
            $rules['validator_suel_id'] = 'required|exists:users,id';
            $rules['items.*.status_pra_suel'] = 'required|in:belum,proses,selesai';
        }

        $validated = $request->validate($rules);

        return DB::transaction(function () use ($tiket, $validated, $user, $canBtel, $canSuel) {
            $today = Carbon::today();

            foreach ($validated['items'] as $item) {
                $existing = LembarKerjaValidasi::where('tiket_id', $tiket->id)
                    ->where('bidang_id', $item['bidang_id'])->first();

                $data = [
                    'catatan' => $item['catatan'] ?? null,
                    'catatan_validator' => $validated['catatan_validator'] ?? ($existing?->catatan_validator),
                    'diteruskan_alih_media' => $validated['action_type'] === 'forward_to_alih_media',
                    'tanggal_mulai' => $today,
                    'tanggal_selesai' => in_array($validated['action_type'], ['forward_to_alih_media', 'return_to_verifikator', 'return_to_warkah', 'reject']) ? $today : null,
                ];

                // Update hanya sub-bidang milik akun yang sedang login (BTel / SuEl / admin & validator-generik keduanya)
                if ($canBtel) {
                    $data['validator_btel_id'] = $validated['validator_btel_id'] ?? $user->id;
                    $data['status_pra_btel'] = $item['status_pra_btel'] ?? ($existing?->status_pra_btel ?? 'belum');
                }
                if ($canSuel) {
                    $data['validator_suel_id'] = $validated['validator_suel_id'] ?? $user->id;
                    $data['status_pra_suel'] = $item['status_pra_suel'] ?? ($existing?->status_pra_suel ?? 'belum');
                }

                // Status validasi bidang = LULUS hanya bila KEDUA sub-bidang selesai (Gate-AND)
                $praBtel = $data['status_pra_btel'] ?? $existing?->status_pra_btel ?? 'belum';
                $praSuel = $data['status_pra_suel'] ?? $existing?->status_pra_suel ?? 'belum';
                $data['status_validasi_bidang'] = ($praBtel === 'selesai' && $praSuel === 'selesai') ? 'lulus' : 'proses';

                if ($existing) {
                    $existing->update($data);
                } else {
                    LembarKerjaValidasi::create(array_merge($data, [
                        'tiket_id' => $tiket->id, 'bidang_id' => $item['bidang_id'],
                    ]));
                }
            }

            if ($validated['action_type'] === 'forward_to_alih_media') {
                // GATEKEEPER: semua bidang harus LULUS
                $allLulus = $tiket->lembarKerjaValidasis()->count() > 0
                    && $tiket->lembarKerjaValidasis()->where('status_validasi_bidang', '!=', 'lulus')->count() === 0;

                if (!$allLulus) {
                    return redirect()->back()->with('error', 'Semua bidang harus LULUS sebelum diteruskan ke Alih Media.');
                }

                $tiket->update(['status' => 'alih_media']);
                $tiket->activeRevisis()->where('stage_tujuan', 'Validator')->update(['status' => 'tertangani']);

                app(NotificationService::class)->kirimForward($tiket, 'alih_media');

                foreach ($tiket->bidangTanahs as $bidang) {
                    LembarKerjaAlihMedia::firstOrCreate(
                        ['tiket_id' => $tiket->id, 'bidang_id' => $bidang->id],
                        ['tanggal_mulai' => $today]
                    );
                }

                RiwayatStatus::create([
                    'tiket_id' => $tiket->id, 'stage_dari' => 'Lembar Kerja Validator', 'stage_ke' => 'Lembar Kerja Alih Media',
                    'changed_by' => Auth::id(), 'keterangan' => 'Data VALID. Pra-BTel & Pra-SuEl LULUS. Diteruskan ke Alih Media.',
                ]);

                return redirect()->route('validator.index')
                    ->with('success', "Validasi Tiket {$tiket->no_tiket} LULUS. Diteruskan ke Alih Media.");

            } elseif ($validated['action_type'] === 'return_to_verifikator') {
                $tiket->update(['status_verifikator' => 'proses']);
                $revisi = TiketRevisi::create([
                    'tiket_id' => $tiket->id, 'stage_asal' => 'Validator', 'stage_tujuan' => 'Verifikator',
                    'pesan_catatan' => $validated['revisi_pesan'] ?? ($validated['catatan_validator'] ?? 'Data asli pemohon perlu pengecekan ulang.'),
                    'created_by' => Auth::id(),
                ]);

                app(NotificationService::class)->kirimRevisi($tiket, $revisi);

                RiwayatStatus::create([
                    'tiket_id' => $tiket->id, 'stage_dari' => 'Lembar Kerja Validator', 'stage_ke' => 'Revisi ke Verifikator',
                    'changed_by' => Auth::id(), 'keterangan' => 'Validator mengembalikan ke Verifikator: data asli perlu koreksi.',
                ]);
                return redirect()->route('validator.index')
                    ->with('warning', "Tiket {$tiket->no_tiket} dikembalikan ke Verifikator.");

            } elseif ($validated['action_type'] === 'return_to_warkah') {
                $tiket->update(['status_warkah' => 'revisi']);
                $revisi = TiketRevisi::create([
                    'tiket_id' => $tiket->id, 'stage_asal' => 'Validator', 'stage_tujuan' => 'Warkah',
                    'pesan_catatan' => $validated['revisi_pesan'] ?? ($validated['catatan_validator'] ?? 'Data salinan BPN perlu pengecekan ulang.'),
                    'created_by' => Auth::id(),
                ]);

                app(NotificationService::class)->kirimRevisi($tiket, $revisi);

                RiwayatStatus::create([
                    'tiket_id' => $tiket->id, 'stage_dari' => 'Lembar Kerja Validator', 'stage_ke' => 'Revisi ke Warkah',
                    'changed_by' => Auth::id(), 'keterangan' => 'Validator mengembalikan ke Warkah: data salinan perlu koreksi.',
                ]);
                return redirect()->route('validator.index')
                    ->with('warning', "Tiket {$tiket->no_tiket} dikembalikan ke Warkah.");

            } elseif ($validated['action_type'] === 'reject') {
                $tiket->update(['status' => 'batal']);
                RiwayatStatus::create([
                    'tiket_id' => $tiket->id, 'stage_dari' => 'Lembar Kerja Validator', 'stage_ke' => 'Permohonan Ditolak',
                    'changed_by' => Auth::id(), 'keterangan' => 'Permohonan DITOLAK pada tahap Validasi.',
                ]);
                return redirect()->route('validator.index')
                    ->with('danger', "Validasi Tiket {$tiket->no_tiket} DITOLAK.");
            }

            return redirect()->back()->with('success', 'Draf lembar kerja validasi berhasil disimpan.');
        });
    }

    /**
     * Konfirmasi penerimaan revisi dari Alih Media ke Validator.
     *
     * @param  int  $id  ID tiket.
     * @param  int  $revisiId  ID tiket_revisis.
     */
    public function konfirmasiRevisi($id, $revisiId)
    {
        $tiket = Tiket::findOrFail($id);
        $revisi = $tiket->tiketRevisis()
            ->where('status', 'aktif')
            ->where('stage_tujuan', 'Validator')
            ->findOrFail($revisiId);

        app(NotificationService::class)->konfirmasiRevisi($revisi, Auth::user());

        return redirect()->route('validator.show', $tiket->id)
            ->with('success', 'Revisi dari ' . $revisi->stage_asal . ' telah dikonfirmasi. Silakan lakukan validasi ulang.');
    }
}
