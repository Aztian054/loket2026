<?php

namespace App\Http\Controllers;

use App\Models\LembarKerjaValidasi;
use App\Models\LembarKerjaWarkah;
use App\Models\RiwayatStatus;
use App\Models\Tiket;
use App\Models\TiketRevisi;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WarkahController extends Controller
{
    public function index(Request $request)
    {
        $query = Tiket::with(['jenisPermohonan', 'bidangTanahs', 'lembarKerjaWarkahs'])
            ->aktif()
            ->latest();

        $filter = $request->input('filter');
        if ($filter === 'revisi') {
            // Tiket di Warkah yang dikembalikan dari Validator
            $query->where('status_warkah', 'revisi');
        } elseif ($filter === 'done') {
            $query->whereIn('status', ['validasi', 'alih_media', 'selesai']);
        } else {
            // Default: tiket yang sedang aktif di stage Warkah
            $query->where(function ($q) {
                $q->where('status_warkah', 'proses')
                  ->orWhere('status_warkah', 'revisi');
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('no_tiket', 'like', "%{$search}%")
                    ->orWhere('nama_pemohon', 'like', "%{$search}%");
            });
        }

        $tikets = $query->paginate(15)->withQueryString();
        return view('warkah.index', compact('tikets', 'filter'));
    }

    /**
     * Detail tiket pada tahap warkah.
     *
     * @param  int  $id  ID tiket.
     */
    public function show($id)
    {
        $tiket = Tiket::with([
            'jenisPermohonan',
            'bidangTanahs',
            'lembarKerjaWarkahs.petugas',
            'verifikasiBerkas.verifikator',
            'activeRevisis.creator',
            'diterimaRevisis.confirmer',
        ])->aktif()->where(function ($q) {
            $q->where('status_warkah', 'proses')
              ->orWhere('status_warkah', 'revisi');
        })->findOrFail($id);

        app(NotificationService::class)->tandaiDibacaUntukTiket($tiket, 'warkah', Auth::user());

        $petugasWarkah = User::whereIn('role', ['warkah', 'admin'])->where('is_active', true)->get();

        return view('warkah.show', compact('tiket', 'petugasWarkah'));
    }

    /**
     * Simpan draf / teruskan lembar kerja warkah ke validator.
     *
     * @param  int  $id  ID tiket.
     */
    public function updateWarkah(Request $request, $id)
    {
        $tiket = Tiket::findOrFail($id);

        $validated = $request->validate([
            'petugas_id' => 'required|exists:users,id',
            'action_type' => 'required|in:save_draft,forward_to_validator,return_to_loket,return_to_verifikator',
            'revisi_pesan' => 'required_if:action_type,return_to_loket,return_to_verifikator|string',
            'catatan' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($tiket, $validated) {
            $today = Carbon::today();

            // Simpan catatan warkah pada bidang pertama (data warkah kini satu catatan per permohonan)
            $bidangPertama = $tiket->bidangTanahs()->orderBy('urutan')->first();
            if ($bidangPertama) {
                LembarKerjaWarkah::updateOrCreate(
                    ['tiket_id' => $tiket->id, 'bidang_id' => $bidangPertama->id],
                    [
                        'petugas_id' => $validated['petugas_id'],
                        'catatan' => $validated['catatan'] ?? null,
                        'tanggal_mulai' => $today,
                        'tanggal_selesai' => $validated['action_type'] === 'forward_to_validator' ? $today : null,
                    ]
                );
            }

            if ($validated['action_type'] === 'forward_to_validator') {
                // Warkah selesai → Trigger Validator (tanpa tunggu Verifikator)
                $tiket->update([
                    'status_warkah' => 'selesai',
                    'status' => 'validasi',
                ]);

                // Revisi aktif (jika ada) ditandai tertangani
                $tiket->activeRevisis()->where('stage_tujuan', 'Warkah')->update(['status' => 'tertangani']);

                // Notifikasi: berkas diteruskan ke Validator
                $svc = app(NotificationService::class);
                $svc->kirimForward($tiket, 'validator');

                // Beri tahu Validator bila revisi yang sudah dikonfirmasi kini tuntas
                $revisiDiterima = $tiket->diterimaRevisis()->where('stage_tujuan', 'Warkah')->first();
                if ($revisiDiterima) {
                    $svc->kirimUlangSetelahRevisi($revisiDiterima);
                }

                foreach ($tiket->bidangTanahs as $bidang) {
                    LembarKerjaValidasi::firstOrCreate(
                        ['tiket_id' => $tiket->id, 'bidang_id' => $bidang->id],
                        ['tanggal_mulai' => $today, 'status_validasi' => 'proses', 'status_validasi_bidang' => 'proses']
                    );
                }

                RiwayatStatus::create([
                    'tiket_id' => $tiket->id,
                    'stage_dari' => 'Lembar Kerja Warkah',
                    'stage_ke' => 'Lembar Kerja Validator',
                    'changed_by' => Auth::id(),
                    'keterangan' => 'Pengecekan warkah selesai. Diteruskan ke Validator.',
                ]);

                return redirect()->route('warkah.index')
                    ->with('success', "Warkah Tiket {$tiket->no_tiket} selesai. Diteruskan ke Validator.");

            } elseif ($validated['action_type'] === 'return_to_loket') {
                // Revisi ke Loket — non-blocking untuk Verifikator
                $tiket->update([
                    'status_warkah' => 'revisi',
                    'status' => 'dikembalikan',
                ]);

                $revisi = TiketRevisi::create([
                    'tiket_id' => $tiket->id,
                    'stage_asal' => 'Warkah',
                    'stage_tujuan' => 'Loket',
                    'pesan_catatan' => $validated['revisi_pesan'] ?? 'Data salinan BPN perlu perbaikan dari bagian Warkah.',
                    'created_by' => Auth::id(),
                ]);

                app(NotificationService::class)->kirimRevisi($tiket, $revisi);

                RiwayatStatus::create([
                    'tiket_id' => $tiket->id,
                    'stage_dari' => 'Lembar Kerja Warkah',
                    'stage_ke' => 'Revisi ke Loket',
                    'changed_by' => Auth::id(),
                    'keterangan' => 'Warkah meminta perbaikan data salinan BPN. Dikembalikan ke Loket.',
                ]);

                return redirect()->route('warkah.index')
                    ->with('warning', "Warkah Tiket {$tiket->no_tiket}: PERBAIKAN diperlukan. Verifikator tetap berjalan.");

            } elseif ($validated['action_type'] === 'return_to_verifikator') {
                // Revisi dari Warkah ke Verifikator (data asli perlu pengecekan ulang)
                $tiket->update([
                    'status_verifikator' => 'proses',
                    'status' => 'verifikasi',
                ]);

                $revisi = TiketRevisi::create([
                    'tiket_id' => $tiket->id,
                    'stage_asal' => 'Warkah',
                    'stage_tujuan' => 'Verifikator',
                    'pesan_catatan' => $validated['revisi_pesan'] ?? 'Data asli pemohon perlu pengecekan ulang dari bagian Warkah.',
                    'created_by' => Auth::id(),
                ]);

                app(NotificationService::class)->kirimRevisi($tiket, $revisi);

                RiwayatStatus::create([
                    'tiket_id' => $tiket->id,
                    'stage_dari' => 'Lembar Kerja Warkah',
                    'stage_ke' => 'Revisi ke Verifikator',
                    'changed_by' => Auth::id(),
                    'keterangan' => 'Warkah mengembalikan ke Verifikator: data asli perlu koreksi.',
                ]);

                return redirect()->route('warkah.index')
                    ->with('warning', "Warkah Tiket {$tiket->no_tiket}: dikembalikan ke Verifikator untuk pengecekan ulang.");
            }

            return redirect()->back()->with('success', 'Draf lembar kerja warkah berhasil disimpan.');
        });
    }

    /**
     * Konfirmasi penerimaan revisi dari Validator ke Warkah.
     *
     * @param  int  $id  ID tiket.
     * @param  int  $revisiId  ID tiket_revisis.
     */
    public function konfirmasiRevisi($id, $revisiId)
    {
        $tiket = Tiket::findOrFail($id);
        $revisi = $tiket->tiketRevisis()
            ->where('status', 'aktif')
            ->where('stage_tujuan', 'Warkah')
            ->findOrFail($revisiId);

        app(NotificationService::class)->konfirmasiRevisi($revisi, Auth::user());

        return redirect()->route('warkah.show', $tiket->id)
            ->with('success', 'Revisi dari ' . $revisi->stage_asal . ' telah dikonfirmasi. Silakan lakukan pengecekan ulang.');
    }
}
