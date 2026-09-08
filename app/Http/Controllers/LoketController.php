<?php

namespace App\Http\Controllers;

use App\Models\BidangTanah;
use App\Models\JenisPermohonan;
use App\Models\LembarKerjaWarkah;
use App\Models\RiwayatStatus;
use App\Models\Tiket;
use App\Models\TiketRevisi;
use App\Models\VerifikasiBerkas;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LoketController extends Controller
{
    public function index(Request $request)
    {
        $query = Tiket::aktif()->with(['jenisPermohonan', 'petugasLoket', 'bidangTanahs'])->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('no_tiket', 'like', "%{$search}%")
                    ->orWhere('nama_pemohon', 'like', "%{$search}%")
                    ->orWhere('nik_pemohon', 'like', "%{$search}%")
                    ->orWhere('no_hp_pemohon', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $statuses = array_filter(array_map('trim', explode(',', $request->status)));
            $query->whereIn('status', $statuses);
        }

        if ($request->filled('jenis_permohonan_id')) {
            $query->where('jenis_permohonan_id', $request->jenis_permohonan_id);
        }

        $tikets = $query->paginate(15)->withQueryString();
        $jenisPermohonans = JenisPermohonan::where('is_active', true)->get();

        return view('loket.index', compact('tikets', 'jenisPermohonans'));
    }

    public function create()
    {
        $jenisPermohonans = JenisPermohonan::where('is_active', true)->with('persyaratanDokumens')->get();

        return view('loket.create', compact('jenisPermohonans'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'no_tiket' => 'required|string|max:50|unique:tikets,no_tiket',
            'jenis_permohonan_id' => 'required|exists:jenis_permohonans,id',
            'nama_pemohon' => 'required|string|max:200',
            'nik_pemohon' => 'nullable|string|max:20',
            'no_hp_pemohon' => 'required|string|max:20',
            'keterangan' => 'nullable|string',
            'status_awal' => 'required|in:diterima,verifikasi',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $jp = JenisPermohonan::findOrFail($validated['jenis_permohonan_id']);
            $today = Carbon::today();
            $targetSelesai = $today->copy()->addDays($jp->batas_hari_sla);

            $tiket = Tiket::create([
                'no_tiket' => $validated['no_tiket'],
                'status_pembetulan' => 'P0',
                'tanggal_masuk' => $today,
                'jenis_permohonan_id' => $jp->id,
                'nama_pemohon' => $validated['nama_pemohon'],
                'nik_pemohon' => $validated['nik_pemohon'],
                'no_hp_pemohon' => $validated['no_hp_pemohon'],
                'jumlah_bidang' => 1,
                'petugas_loket_id' => Auth::id(),
                'status' => 'diterima',
                'keterangan' => $validated['keterangan'],
                'tanggal_target_selesai' => $targetSelesai,
            ]);

            // Buat satu bidang tanah default — detail (NIB, hak, lokasi) dilengkapi di tahap selanjutnya
            BidangTanah::create([
                'tiket_id' => $tiket->id,
                'urutan' => 1,
            ]);

            // Audit Trail
            RiwayatStatus::create([
                'tiket_id' => $tiket->id,
                'stage_dari' => 'Registrasi Loket',
                'stage_ke' => $validated['status_awal'] === 'verifikasi' ? 'Verifikator Berkas' : 'Loket (Draf)',
                'changed_by' => Auth::id(),
                'keterangan' => 'Pendaftaran permohonan baru di Loket.',
            ]);

            // If forwarded to verifikator, trigger parallel send (Verifikator & Warkah)
            if ($validated['status_awal'] === 'verifikasi') {
                $this->forwardParalel($tiket);
            }

            return redirect()->route('loket.show', $tiket->id)
                ->with('success', "Tiket berhasil dibuat dengan Nomor: {$tiket->no_tiket}.");
        });
    }

    /**
     * Detail tiket yang sedang diproses di loket.
     *
     * @param  int  $id  ID tiket.
     */
    public function show($id)
    {
        $tiket = Tiket::with([
            'jenisPermohonan.persyaratanDokumens',
            'petugasLoket',
            'bidangTanahs',
            'verifikasiBerkas.verifikator',
            'lembarKerjaWarkahs.petugas',
            'lembarKerjaValidasis.validator',
            'lembarKerjaAlihMedias.petugas',
            'riwayatStatuses.user',
            'activeRevisis.creator',
            'diterimaRevisis.confirmer',
        ])->findOrFail($id);

        app(NotificationService::class)->tandaiDibacaUntukTiket($tiket, 'loket', Auth::user());

        return view('loket.show', compact('tiket'));
    }

    /**
     * Form edit data tiket di loket.
     *
     * @param  int  $id  ID tiket.
     */
    public function edit($id)
    {
        $tiket = Tiket::with(['bidangTanahs', 'jenisPermohonan'])->findOrFail($id);
        $jenisPermohonans = JenisPermohonan::where('is_active', true)->get();

        return view('loket.edit', compact('tiket', 'jenisPermohonans'));
    }

    /**
     * Perbarui data tiket di loket.
     *
     * @param  int  $id  ID tiket.
     */
    public function update(Request $request, $id)
    {
        $tiket = Tiket::findOrFail($id);

        $validated = $request->validate([
            'no_tiket' => 'required|string|max:50|unique:tikets,no_tiket,' . $tiket->id,
            'jenis_permohonan_id' => 'required|exists:jenis_permohonans,id',
            'nama_pemohon' => 'required|string|max:200',
            'nik_pemohon' => 'nullable|string|max:20',
            'no_hp_pemohon' => 'required|string|max:20',
            'keterangan' => 'nullable|string',
        ]);

        $tiket->update($validated);

        return redirect()->route('loket.show', $tiket->id)->with('success', 'Data tiket berhasil diperbarui.');
    }

    // Teruskan ke Verifikator dari Loket
    /**
     * Teruskan berkas ke verifikator dari loket.
     *
     * @param  int  $id  ID tiket.
     */
    public function forwardToVerifikator($id)
    {
        $tiket = Tiket::findOrFail($id);

        DB::transaction(function () use ($tiket) {
            $tiket->update(['status' => 'diterima']);

            $this->forwardParalel($tiket);
        });

        return redirect()->route('loket.show', $tiket->id)
            ->with('success', 'Berkas diteruskan. Verifikator & Warkah memulai pengecekan secara paralel.');
    }

    /**
     * Jalur pengiriman paralel ke Verifikator & Warkah.
     *
     * Dipakai oleh forwardToVerifikator() dan store() (status_awal=verifikasi).
     * Set flags stage, buat VerifikasiBerkas + LembarKerjaWarkah, catat riwayat,
     * dan kirim notifikasi ke kedua stage.
     */
    private function forwardParalel(Tiket $tiket): void
    {
        $today = Carbon::today();

        // Parallel assignment: trigger Verifikator + Warkah bersamaan
        $tiket->update([
            'status_verifikator' => 'proses',
            'status_warkah'      => 'proses',
        ]);

        // Inisialisasi Verifikasi Berkas (iterasi ke-1)
        VerifikasiBerkas::firstOrCreate(
            ['tiket_id' => $tiket->id, 'iterasi' => 1],
            ['tanggal_diterima' => $today, 'status' => 'proses']
        );

        // Inisialisasi Lembar Kerja Warkah per bidang
        foreach ($tiket->bidangTanahs as $bidang) {
            LembarKerjaWarkah::firstOrCreate(
                ['tiket_id' => $tiket->id, 'bidang_id' => $bidang->id],
                ['tanggal_mulai' => $today, 'status_keberadaan' => 'ada', 'kondisi' => 'baik']
            );
        }

        RiwayatStatus::create([
            'tiket_id'    => $tiket->id,
            'stage_dari'  => 'Loket',
            'stage_ke'    => 'Verifikator & Warkah (Paralel)',
            'changed_by'  => Auth::id(),
            'keterangan'  => 'Berkas diteruskan dari Loket. Verifikator & Warkah memulai pengecekan secara bersamaan.',
        ]);

        $svc = app(NotificationService::class);
        $svc->kirimForward($tiket, 'verifikator');
        $svc->kirimForward($tiket, 'warkah');
    }

    // Input Ulang / Perbaikan dari Pemohon (Iterasi P0 -> P1 -> P2 dst)
    /**
     * Input ulang / perbaikan dari pemohon (iterasi P0 -> P1 -> dst).
     *
     * @param  int  $id  ID tiket.
     */
    public function resubmit(Request $request, $id)
    {
        $tiket = Tiket::findOrFail($id);

        $maxIterasi = (int) $tiket->verifikasiBerkas()->max('iterasi');
        $nextIterasi = $maxIterasi + 1;

        $levelPembetulan = max($nextIterasi - 1, 0);
        $nextP = 'P' . min($levelPembetulan, 5);

        DB::transaction(function () use ($tiket, $nextP, $nextIterasi, $request) {
            // Deteksi stage mana yang dikembalikan (SEBELUM status revisi diubah)
            $revisiAktif = $tiket->tiketRevisis()
                ->where('stage_tujuan', 'Loket')
                ->where('status', 'aktif')
                ->latest('id')
                ->first();
            $revisiStage = $revisiAktif?->stage_asal;

            // Resolve revisi aktif yang dikembalikan ke Loket
            $tiket->activeRevisis()->where('stage_tujuan', 'Loket')->update(['status' => 'tertangani']);

            // Reset flags stage yang direvisi
            $updateData = [
                'status' => 'diterima',
                'status_pembetulan' => $nextP,
            ];

            if ($revisiStage === 'Verifikator') {
                $updateData['status_verifikator'] = 'proses';
            } elseif ($revisiStage === 'Warkah') {
                $updateData['status_warkah'] = 'proses';
            } else {
                // Default: reset both
                $updateData['status_verifikator'] = 'proses';
                $updateData['status_warkah'] = 'proses';
            }

            $tiket->update($updateData);

            VerifikasiBerkas::create([
                'tiket_id' => $tiket->id,
                'iterasi' => $nextIterasi,
                'tanggal_diterima' => Carbon::today(),
                'status' => 'proses',
                'catatan' => 'Perbaikan diserahkan pemohon: ' . $request->input('catatan_perbaikan', 'Dokumen perbaikan telah dilengkapi.'),
            ]);

            $stageTarget = $revisiStage ? "Verifikator (revisi dari {$revisiStage})" : 'Verifikator & Warkah (Paralel)';
            RiwayatStatus::create([
                'tiket_id' => $tiket->id,
                'stage_dari' => 'Loket (Perbaikan ' . $nextP . ')',
                'stage_ke' => $stageTarget,
                'changed_by' => Auth::id(),
                'keterangan' => 'Pemohon melengkapi berkas perbaikan (' . $nextP . '). Dikembalikan ke ' . ($revisiStage ?? 'Verifikator & Warkah') . '.',
            ]);

            if ($revisiAktif) {
                app(NotificationService::class)->kirimUlangSetelahRevisi($revisiAktif);
            }
        });

        return redirect()->route('loket.show', $tiket->id)->with('success', "Berkas perbaikan {$nextP} berhasil didaftarkan ulang dan dikirim kembali.");
    }

    /**
     * Konfirmasi penerimaan revisi yang dikembalikan ke Loket.
     *
     * @param  int  $id  ID tiket.
     * @param  int  $revisiId  ID tiket_revisis.
     */
    public function konfirmasiRevisi($id, $revisiId)
    {
        $tiket = Tiket::findOrFail($id);
        $revisi = $tiket->tiketRevisis()
            ->where('status', 'aktif')
            ->where('stage_tujuan', 'Loket')
            ->findOrFail($revisiId);

        app(NotificationService::class)->konfirmasiRevisi($revisi, Auth::user());

        return redirect()->route('loket.show', $tiket->id)
            ->with('success', 'Revisi dari ' . $revisi->stage_asal . ' telah dikonfirmasi. Silakan lanjutkan perbaikan berkas.');
    }

    /**
     * Cetak tanda terima (receipt) tiket.
     *
     * @param  int  $id  ID tiket.
     */
    public function printReceipt($id)
    {
        $tiket = Tiket::with(['jenisPermohonan.persyaratanDokumens', 'bidangTanahs', 'petugasLoket'])->findOrFail($id);
        return view('loket.print_receipt', compact('tiket'));
    }

    /**
     * Cetak checklist kelengkapan berkas tiket.
     *
     * @param  int  $id  ID tiket.
     */
    public function printChecklist($id)
    {
        $tiket = Tiket::with([
            'jenisPermohonan.persyaratanDokumens',
            'bidangTanahs',
            'petugasLoket',
            'verifikasiBerkas.verifikator',
            'lembarKerjaWarkahs.petugas',
        ])->findOrFail($id);
        return view('loket.print_checklist', compact('tiket'));
    }
}
