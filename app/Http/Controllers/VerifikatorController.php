<?php

namespace App\Http\Controllers;

use App\Models\LembarKerjaValidasi;
use App\Models\LembarKerjaWarkah;
use App\Models\RiwayatStatus;
use App\Models\Tiket;
use App\Models\TiketRevisi;
use App\Models\User;
use App\Models\VerifikasiBerkas;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VerifikatorController extends Controller
{
    public function index(Request $request)
    {
        $query = Tiket::with(['jenisPermohonan', 'bidangTanahs', 'latestVerifikasi'])
            ->aktif()
            ->latest();

        // Filter berdasarkan parameter 'filter'
        $filter = $request->input('filter');
        if ($filter === 'revisi') {
            $query->where('status_verifikator', 'revisi_ke_loket');
        } elseif ($filter === 'done') {
            $query->whereIn('status', ['warkah', 'validasi', 'alih_media', 'selesai']);
        } else {
            // Default: tiket aktif di stage Verifikator
            $query->where(function ($q) {
                $q->where('status_verifikator', 'proses')
                  ->orWhere('status_verifikator', 'tertunda')
                  ->orWhere('status_verifikator', 'revisi_ke_loket');
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
        return view('verifikator.index', compact('tikets', 'filter'));
    }

    /**
     * Detail tiket pada tahap verifikasi berkas.
     *
     * @param  int  $id  ID tiket.
     */
    public function show($id)
    {
        $tiket = Tiket::with([
            'jenisPermohonan.persyaratanDokumens',
            'bidangTanahs',
            'verifikasiBerkas.verifikator',
            'latestVerifikasi',
            'activeRevisis.creator',
            'diterimaRevisis.confirmer',
        ])->aktif()->where(function ($q) {
            $q->where('status_verifikator', 'proses')
              ->orWhere('status_verifikator', 'tertunda')
              ->orWhere('status_verifikator', 'revisi_ke_loket');
        })->findOrFail($id);

        app(NotificationService::class)->tandaiDibacaUntukTiket($tiket, 'verifikator', Auth::user());

        $verifikators = User::whereIn('role', ['verifikator', 'admin'])->where('is_active', true)->get();

        return view('verifikator.show', compact('tiket', 'verifikators'));
    }

    /**
     * Proses hasil verifikasi berkas (lengkap / perbaikan / batal).
     *
     * @param  int  $id  ID tiket.
     */
    public function updateVerification(Request $request, $id)
    {
        $validated = $request->validate([
            'verifikator_id' => 'required|exists:users,id',
            'action_type' => 'required|in:save_draft,forward_to_validator,return_to_loket',
            'catatan' => 'nullable|string',
        ]);

        // Lapis keamanan tambahan: revisi ke Loket wajib menyertakan alasan/catatan.
        if ($validated['action_type'] === 'return_to_loket'
            && trim((string) ($validated['catatan'] ?? '')) === '') {
            return back()
                ->withErrors(['catatan' => 'Alasan perbaikan wajib diisi sebelum dokumen direvisi ke Loket.'])
                ->withInput();
        }

        return DB::transaction(function () use ($id, $validated) {
            // Pessimistic Locking: kunci baris tiket agar tidak terjadi tabrakan data
            // bila beberapa verifikator menekan tombol aksi secara bersamaan.
            $tiket = Tiket::whereKey($id)->lockForUpdate()->firstOrFail();

            $today = Carbon::today();
            $action = $validated['action_type'];
            $catatan = trim((string) ($validated['catatan'] ?? ''));

            // Simpan / perbarui catatan Verifikasi Berkas (iterasi terakhir).
            $verifikasi = VerifikasiBerkas::where('tiket_id', $tiket->id)->latest('iterasi')->first();
            if (! $verifikasi) {
                $verifikasi = new VerifikasiBerkas();
                $verifikasi->tiket_id = $tiket->id;
                $verifikasi->iterasi = 1;
            }
            $verifikasi->verifikator_id = $validated['verifikator_id'];
            $verifikasi->catatan = $catatan ?: null;
            $verifikasi->save();

            if ($action === 'save_draft') {
                // Simpan draf: simpan petugas & catatan tanpa memfinalisasi status stage.
                return redirect()->back()->with('success', 'Draf verifikasi berhasil disimpan.');
            }

            $verifikasi->tanggal_selesai = $today;

            if ($action === 'forward_to_validator') {
                $verifikasi->status = 'lengkap';
                $verifikasi->save();

                // Verifikator selesai — non-blocking, Warkah tetap berjalan.
                $tiket->update([
                    'status_verifikator' => 'selesai',
                    'status' => 'validasi',
                ]);

                // Revisi aktif (jika ada) ditandai tertangani.
                $tiket->activeRevisis()->where('stage_tujuan', 'Verifikator')->update(['status' => 'tertangani']);

                // Beri tahu stage asal bila revisi yang sudah dikonfirmasi kini tuntas.
                $revisiDiterima = $tiket->diterimaRevisis()->where('stage_tujuan', 'Verifikator')->first();
                if ($revisiDiterima) {
                    app(NotificationService::class)->kirimUlangSetelahRevisi($revisiDiterima);
                }

                // Inisialisasi Lembar Kerja Validator per bidang (idempoten — aman
                // walau Warkah memicu Validator lebih dulu / bersamaan).
                foreach ($tiket->bidangTanahs as $bidang) {
                    LembarKerjaValidasi::firstOrCreate(
                        ['tiket_id' => $tiket->id, 'bidang_id' => $bidang->id],
                        ['tanggal_mulai' => $today, 'status_validasi' => 'proses', 'status_validasi_bidang' => 'proses']
                    );
                }

                // Catatan opsional dikirim sebagai instruksi internal ke Validator.
                app(NotificationService::class)->kirimForward($tiket, 'validator', null, $catatan ?: null);

                RiwayatStatus::create([
                    'tiket_id' => $tiket->id,
                    'stage_dari' => 'Verifikator Berkas',
                    'stage_ke' => 'Lembar Kerja Validator',
                    'changed_by' => Auth::id(),
                    'keterangan' => 'Verifikasi data asli pemohon SELESAI. Diteruskan ke Validator.'
                        . ($catatan !== '' ? " Instruksi: {$catatan}" : ''),
                ]);

                return redirect()->route('verifikator.index')
                    ->with('success', "Verifikasi Tiket {$tiket->no_tiket} SELESAI. Diteruskan ke Validator.");
            }

            // action === return_to_loket (catatan wajib, sudah divalidasi di atas).
            $verifikasi->status = 'perbaikan';
            $verifikasi->save();

            // Revisi ke Loket — non-blocking untuk Warkah.
            $tiket->update([
                'status_verifikator' => 'revisi_ke_loket',
                'status' => 'dikembalikan',
            ]);

            $revisi = TiketRevisi::create([
                'tiket_id' => $tiket->id,
                'stage_asal' => 'Verifikator',
                'stage_tujuan' => 'Loket',
                'pesan_catatan' => $catatan,
                'created_by' => Auth::id(),
            ]);

            app(NotificationService::class)->kirimRevisi($tiket, $revisi);

            RiwayatStatus::create([
                'tiket_id' => $tiket->id,
                'stage_dari' => 'Verifikator Berkas',
                'stage_ke' => 'Revisi ke Loket',
                'changed_by' => Auth::id(),
                'keterangan' => 'Verifikator meminta perbaikan data asli pemohon. Berkas dikembalikan ke Loket.',
            ]);

            return redirect()->route('verifikator.printSaranKoreksi', $tiket->id)
                ->with('warning', "Verifikasi Tiket {$tiket->no_tiket}: PERBAIKAN diperlukan. Warkah tetap berjalan.");
        });
    }

    /**
     * Konfirmasi penerimaan revisi dari Validator ke Verifikator.
     *
     * @param  int  $id  ID tiket.
     * @param  int  $revisiId  ID tiket_revisis.
     */
    public function konfirmasiRevisi($id, $revisiId)
    {
        $tiket = Tiket::findOrFail($id);
        $revisi = $tiket->tiketRevisis()
            ->where('status', 'aktif')
            ->where('stage_tujuan', 'Verifikator')
            ->findOrFail($revisiId);

        app(NotificationService::class)->konfirmasiRevisi($revisi, Auth::user());

        return redirect()->route('verifikator.show', $tiket->id)
            ->with('success', 'Revisi dari ' . $revisi->stage_asal . ' telah dikonfirmasi. Silakan lakukan pengecekan ulang.');
    }

    /**
     * Cetak lembar saran koreksi.
     *
     * @param  int  $id  ID tiket.
     */
    public function printSaranKoreksi($id)
    {
        $tiket = Tiket::with([
            'jenisPermohonan',
            'bidangTanahs',
            'latestVerifikasi.verifikator',
            'petugasLoket'
        ])->findOrFail($id);

        return view('verifikator.print_saran_koreksi', compact('tiket'));
    }
}
