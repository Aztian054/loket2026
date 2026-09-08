<?php

namespace App\Http\Controllers;

use App\Models\BidangTanah;
use App\Models\LembarKerjaAlihMedia;
use App\Models\RiwayatStatus;
use App\Models\Tiket;
use App\Models\TiketRevisi;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AlihMediaController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $isBtel = $user->role === 'alih_media_btel';
        $isSuel = $user->role === 'alih_media_suel';

        $query = Tiket::with(['jenisPermohonan', 'bidangTanahs', 'lembarKerjaAlihMedias'])
            ->aktif()
            ->latest();

        $filter = $request->input('filter');
        if ($filter === 'done') {
            $query->where('status', 'selesai');
        } else {
            $query->where('status', 'alih_media');

            // Filter per sub-role: hanya tampilkan tiket yang masih butuh kerjaan sub-bidang ini
            if ($isBtel) {
                $query->where(function ($q) {
                    $q->whereDoesntHave('lembarKerjaAlihMedias')
                       ->orWhereHas('lembarKerjaAlihMedias', fn ($q2) => $q2->where('status_btel', '!=', 'selesai'));
                });
            } elseif ($isSuel) {
                $query->where(function ($q) {
                    $q->whereDoesntHave('lembarKerjaAlihMedias')
                       ->orWhereHas('lembarKerjaAlihMedias', fn ($q2) => $q2->where('status_suel', '!=', 'selesai'));
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
        return view('alih_media.index', compact('tikets', 'filter'));
    }

    /**
     * Detail tiket pada tahap alih media.
     *
     * @param  int  $id  ID tiket.
     */
    public function show($id)
    {
        $tiket = Tiket::with([
            'jenisPermohonan',
            'bidangTanahs',
            'lembarKerjaValidasis.validatorBtel',
            'lembarKerjaValidasis.validatorSuel',
            'lembarKerjaAlihMedias.petugas',
            'lembarKerjaAlihMedias.petugasBtel',
            'lembarKerjaAlihMedias.petugasSuel',
            'activeRevisis.creator',
            'diterimaRevisis.confirmer',
        ])->aktif()->where('status', 'alih_media')->findOrFail($id);

        app(NotificationService::class)->tandaiDibacaUntukTiket($tiket, 'alih_media', Auth::user());

        $petugasBtel = User::whereIn('role', ['alih_media_btel', 'admin'])->where('is_active', true)->get();
        $petugasSuel = User::whereIn('role', ['alih_media_suel', 'admin'])->where('is_active', true)->get();

        // Konteks role: sub-bidang yang ditangani akun yang sedang login
        // Role generik 'alih_media' menangani KEDUA sub-bidang (seperti admin dalam lingkup Alih Media).
        $user = Auth::user();
        $isAdmin = in_array($user->role, ['admin', 'alih_media']);
        $isBtel = in_array($user->role, ['admin', 'alih_media', 'alih_media_btel']);
        $isSuel = in_array($user->role, ['admin', 'alih_media', 'alih_media_suel']);

        return view('alih_media.show', compact('tiket', 'petugasBtel', 'petugasSuel', 'isAdmin', 'isBtel', 'isSuel'));
    }

    /**
     * Simpan draf / selesaikan lembar kerja alih media.
     *
     * @param  int  $id  ID tiket.
     */
    public function updateAlihMedia(Request $request, $id)
    {
        $tiket = Tiket::findOrFail($id);

        $user = Auth::user();
        $isAdmin = $user->role === 'admin';
        $isBtel = $user->role === 'alih_media_btel';
        $isSuel = $user->role === 'alih_media_suel';

        // Role generik 'alih_media' dapat mengisi KEDUA sub-bidang.
        $canBtel = in_array($user->role, ['admin', 'alih_media', 'alih_media_btel']);
        $canSuel = in_array($user->role, ['admin', 'alih_media', 'alih_media_suel']);

        // Validasi hanya field sub-bidang milik akun yang sedang login (BTel / SuEl / admin keduanya)
        $rules = [
            'action_type' => 'required|in:save_draft,complete_btel,complete_suel,return_to_validator',
            'revisi_pesan' => 'required_if:action_type,return_to_validator|nullable|string',
            'revisi_sub_bidang' => 'required_if:action_type,return_to_validator|in:pra_btel,pra_suel',
            'items' => 'required|array',
            'items.*.bidang_id' => 'required|exists:bidang_tanahs,id',
            'items.*.catatan' => 'nullable|string',
        ];

        if ($canBtel) {
            $rules['petugas_btel_id'] = 'required|exists:users,id';
        }
        if ($canSuel) {
            $rules['petugas_suel_id'] = 'required|exists:users,id';
        }

        $validated = $request->validate($rules);

        return DB::transaction(function () use ($tiket, $validated, $user, $canBtel, $canSuel) {
            $today = Carbon::today();

            foreach ($validated['items'] as $item) {
                $data = [
                    'catatan' => $item['catatan'] ?? null,
                    'tanggal_mulai' => $today,
                    'tanggal_selesai' => in_array($validated['action_type'], ['complete_btel', 'complete_suel', 'return_to_validator']) ? $today : null,
                ];

                // Update hanya sub-bidang milik akun yang sedang login
                if ($canBtel) {
                    $data['petugas_btel_id'] = $validated['petugas_btel_id'] ?? $user->id;
                }
                if ($canSuel) {
                    $data['petugas_suel_id'] = $validated['petugas_suel_id'] ?? $user->id;
                }

                LembarKerjaAlihMedia::updateOrCreate(
                    ['tiket_id' => $tiket->id, 'bidang_id' => $item['bidang_id']],
                    $data
                );
            }

            if ($validated['action_type'] === 'complete_btel') {
                LembarKerjaAlihMedia::where('tiket_id', $tiket->id)
                    ->update(['status_btel' => 'selesai']);
                $tiket->activeRevisis()->where('stage_tujuan', 'Alih Media')->where('sub_bidang', 'pra_btel')->update(['status' => 'tertangani']);
                RiwayatStatus::create([
                    'tiket_id' => $tiket->id, 'stage_dari' => 'Alih Media (Pra-BTel)', 'stage_ke' => 'Pra-BTel Selesai',
                    'changed_by' => Auth::id(), 'keterangan' => 'Sub-bidang Pra-BTel SELESAI.',
                ]);
                return $this->finalizeJikaKeduaSelesai($tiket, 'Pra-BTel');

            } elseif ($validated['action_type'] === 'complete_suel') {
                LembarKerjaAlihMedia::where('tiket_id', $tiket->id)
                    ->update(['status_suel' => 'selesai']);
                $tiket->activeRevisis()->where('stage_tujuan', 'Alih Media')->where('sub_bidang', 'pra_suel')->update(['status' => 'tertangani']);
                RiwayatStatus::create([
                    'tiket_id' => $tiket->id, 'stage_dari' => 'Alih Media (Pra-SuEl)', 'stage_ke' => 'Pra-SuEl Selesai',
                    'changed_by' => Auth::id(), 'keterangan' => 'Sub-bidang Pra-SuEl SELESAI.',
                ]);
                return $this->finalizeJikaKeduaSelesai($tiket, 'Pra-SuEl');

            } elseif ($validated['action_type'] === 'return_to_validator') {
                $subBidang = $validated['revisi_sub_bidang'];
                $tiket->lembarKerjaValidasis()->update(['status_validasi_bidang' => 'proses']);
                $revisi = TiketRevisi::create([
                    'tiket_id' => $tiket->id, 'stage_asal' => 'Alih Media', 'stage_tujuan' => 'Validator',
                    'sub_bidang' => $subBidang,
                    'pesan_catatan' => $validated['revisi_pesan'] ?? 'Pengecekan ulang diperlukan.',
                    'created_by' => Auth::id(),
                ]);

                app(NotificationService::class)->kirimRevisi($tiket, $revisi);

                RiwayatStatus::create([
                    'tiket_id' => $tiket->id, 'stage_dari' => 'Lembar Kerja Alih Media', 'stage_ke' => 'Revisi ke Validator',
                    'changed_by' => Auth::id(), 'keterangan' => "Alih Media mengembalikan ke Validator (sub: {$subBidang}).",
                ]);
                return redirect()->route('alih_media.index')
                    ->with('warning', "Tiket {$tiket->no_tiket} dikembalikan ke Validator dari Alih Media.");
            }

            return redirect()->back()->with('success', 'Draf lembar kerja alih media berhasil disimpan.');
        });
    }

    /**
     * Gate-AND Final untuk Stage 5: tiket hanya dinyatakan SELESAI bila KEDUA
     * sub-bidang (Pra-BTel &amp; Pra-SuEl) telah menekan "Selesaikan Permohonan".
     *
     * @param  \App\Models\Tiket  $tiket
     * @param  string  $subYangBaruSelesai  Nama sub-bidang yang baru saja selesai.
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function finalizeJikaKeduaSelesai(Tiket $tiket, string $subYangBaruSelesai)
    {
        $allDone = $tiket->lembarKerjaAlihMedias()->count() > 0
            && $tiket->lembarKerjaAlihMedias()
                ->where(function ($q) {
                    $q->where('status_btel', '!=', 'selesai')
                      ->orWhere('status_suel', '!=', 'selesai');
                })->count() === 0;

        if (!$allDone) {
            return redirect()->route('alih_media.index')
                ->with('success', "Sub-bidang {$subYangBaruSelesai} Tiket {$tiket->no_tiket} SELESAI. Menunggu sub-bidang lainnya selesai sebelum permohonan diterbitkan.");
        }

        // Kedua sub-bidang selesai → permohonan SELESAI & sertifikat diterbitkan
        $tiket->update([
            'status' => 'selesai',
            'status_alih_media' => 'selesai',
            'tanggal_selesai' => Carbon::today(),
        ]);
        $tiket->activeRevisis()->where('stage_tujuan', 'Alih Media')->update(['status' => 'tertangani']);

        $svc = app(NotificationService::class);
        $svc->kirimInfo($tiket, 'loket', 'Permohonan Selesai', "Sertifikat elektronik Tiket {$tiket->no_tiket} telah diterbitkan. Permohonan dinyatakan SELESAI.");

        RiwayatStatus::create([
            'tiket_id' => $tiket->id, 'stage_dari' => 'Lembar Kerja Alih Media', 'stage_ke' => 'Selesai',
            'changed_by' => Auth::id(), 'keterangan' => 'Alih Media SELESAI (Pra-BTel & Pra-SuEl). Sertifikat elektronik diterbitkan.',
        ]);

        return redirect()->route('alih_media.index')
            ->with('success', "Alih Media Tiket {$tiket->no_tiket} SELESAI. Permohonan diterbitkan.");
    }

    /**
     * Konfirmasi penerimaan revisi yang menuju ke Alih Media.
     *
     * @param  int  $id  ID tiket.
     * @param  int  $revisiId  ID tiket_revisis.
     */
    public function konfirmasiRevisi($id, $revisiId)
    {
        $tiket = Tiket::findOrFail($id);
        $revisi = $tiket->tiketRevisis()
            ->where('status', 'aktif')
            ->where('stage_tujuan', 'Alih Media')
            ->findOrFail($revisiId);

        app(NotificationService::class)->konfirmasiRevisi($revisi, Auth::user());

        return redirect()->route('alih_media.show', $tiket->id)
            ->with('success', 'Revisi dari ' . $revisi->stage_asal . ' telah dikonfirmasi.');
    }
}
