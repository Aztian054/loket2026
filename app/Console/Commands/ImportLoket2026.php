<?php

namespace App\Console\Commands;

use App\Models\BidangTanah;
use App\Models\LembarKerjaAlihMedia;
use App\Models\LembarKerjaValidasi;
use App\Models\LembarKerjaWarkah;
use App\Models\RiwayatStatus;
use App\Models\Tiket;
use App\Models\VerifikasiBerkas;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class ImportLoket2026 extends ImportLoket
{
    protected $signature = 'import:loket-2026
        {file? : Path file Excel (relatif ke root project, default: Template_Panduan/Project Loket/00 Dashboard Control Permohonan.xlsx)}
        {--dry : Simulasi tanpa menulis ke database}
        {--limit= : Batasi jumlah tiket 2026 yang diproses (debug)}
        {--no-update : Jangan update tiket tahun 2026 yang sudah pernah diimpor}
        {--wipe2026 : Hapus semua tiket tahun 2026 sebelum impor (clean re-import)}';

    protected $description = 'Impor data aktif 2026 dari 00 Dashboard Control Permohonan (sheet Dashboard) dengan map status tahapan (Antrian Loket s.d. Selesai).';

    private const FILE = 'Template_Panduan/Project Loket/00 Dashboard Control Permohonan.xlsx';

    public function handle(): int
    {
        $this->newLine();
        $this->info('=== IMPOR DATA AKTIF 2026 (DASHBOARD) ===');

        $fileArg = $this->argument('file');
        $path = $fileArg ? base_path((string) $fileArg) : base_path(self::FILE);
        if (!is_file($path)) {
            $this->error("Tidak ditemukan: {$path}");
            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry');
        $update = ! (bool) $this->option('no-update');
        $limit = (int) ($this->option('limit') ?: 0);

        $this->loadJenis();
        $this->loadUsers();
        $this->existingKode = Tiket::pluck('no_tiket')->flip()->all();

        $this->line(sprintf('Sebelum impor : tikets=%d | riwayat_statuses=%d', Tiket::count(), RiwayatStatus::count()));

        if ($this->option('wipe2026') && !$dry) {
            $ids = Tiket::where('tahun', 2026)->pluck('id');
            $nDel = $ids->count();
            foreach ($ids as $id) {
                Tiket::find($id)?->delete();
            }
            $this->info("--wipe2026: menghapus {$nDel} tiket tahun 2026 (beserta turunannya).");
        }
        $this->line('Membaca sheet Dashboard (in-memory, kolom 1-22)...');
        $rows = $this->readDashboard2026($path);

        $only2026 = [];
        foreach ($rows as $kode => $r) {
            if ($r['TanggalMasuk'] && $r['TanggalMasuk']->year === 2026) {
                $only2026[$kode] = $r;
            }
        }
        $this->line(sprintf('Baris Dashboard terbaca: %d | tiket tahun 2026: %d', count($rows), count($only2026)));

        if (count($only2026) === 0) {
            $this->warn('Tidak ditemukan tiket 2026 dengan nilai nyata di sheet Dashboard file ini.');
            $this->warn('Kemungkinan file masih hasil sinkronisasi Google Sheets (nilai sel = placeholder/formula), bukan export nilai.');
            $this->warn('Solusi: di Google Sheets gunakan File → Download → Microsoft Excel (.xlsx), lalu jalankan:');
            $this->warn('    php -d memory_limit=2048M artisan import:loket-2026 <path-ke-file-export.xlsx> --dry');
        }

        $this->newLine();
        $this->info('Mulai impor tiket 2026 (update_untuk_yang_sudah_ada=' . ($update ? 'YA' : 'TIDAK') . ')...');

        $count = 0;
        $inserted = 0;
        $updated = 0;
        $skipped = 0;
        $statusDist = [];

        foreach ($only2026 as $kode => $r) {
            if ($limit && $count >= $limit) {
                break;
            }
            $count++;

            [$status, $tglSelesai] = $this->statusFromRow($r);
            $statusDist[$status] = ($statusDist[$status] ?? 0) + 1;

            if (isset($this->existingKode[$kode])) {
                if (!$dry && $update) {
                    $this->update2026Row($kode, $r, $status, $tglSelesai);
                    $updated++;
                } else {
                    $skipped++;
                }
                continue;
            }

            if (!$dry) {
                $this->import2026Row($kode, $r, $status, $tglSelesai);
                $this->existingKode[$kode] = true;
            }
            $inserted++;
        }

        $this->newLine();
        $this->line(sprintf('Tiket 2026 diproses : %d', $count));
        $this->line(sprintf('  - baru dibuat : %d', $inserted));
        $this->line(sprintf('  - di-update   : %d', $updated));
        $this->line(sprintf('  - dilewati    : %d (sudah ada & --no-update / dry)', $skipped));
        $this->line('Distribusi status yang akan/dihasilkan:');
        foreach ($statusDist as $s => $c) {
            $this->line(sprintf('  %-15s = %d', $s, $c));
        }

        if (!$dry) {
            $this->newLine();
            $this->info('=== RINGKASAN ===');
            $this->table(['Metrik', 'Jumlah'], [
                ['Total tikets', Tiket::count()],
                ['Tiket aktif', Tiket::aktif()->count()],
                ['Tiket tahun 2026', Tiket::where('tahun', 2026)->count()],
                ['Verifikasi berkas', VerifikasiBerkas::count()],
                ['Lembar kerja (warkah+validasi+alih media)', LembarKerjaWarkah::count() + LembarKerjaValidasi::count() + LembarKerjaAlihMedia::count()],
                ['Riwayat status', RiwayatStatus::count()],
            ]);
            $this->newLine();
            $this->info('Distribusi status tiket 2026:');
            foreach (Tiket::select('status', \Illuminate\Support\Facades\DB::raw('COUNT(*) as c'))->where('tahun', 2026)->groupBy('status')->orderByDesc('c')->get() as $s) {
                $this->line(sprintf('  %-15s = %d', $s->status, $s->c));
            }
        }

        $this->info($dry ? 'SELESAI (MODE DRY) - TIDAK ADA DATA DITULIS' : 'IMPOR 2026 SELESAI');

        return self::SUCCESS;
    }
    /**
     * Baca sheet Dashboard secara in-memory dengan filter kolom 1-22 saja
     * (menghindari cache-disk yang membuat impor sebelumnya macet).
     */
    private function readDashboard2026(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);
        $reader->setReadFilter(new class implements IReadFilter {
            public function readCell($columnAddress, $row, $worksheetName = ''): bool
            {
                return Coordinate::columnIndexFromString((string) $columnAddress) <= 22;
            }
        });

        $sp = $reader->load($path);
        $sheet = $sp->getSheetByName('Dashboard') ?? $sp->getSheet(0);

        $out = [];
        $last = $sheet->getHighestDataRow();
        for ($r = 2; $r <= $last; $r++) {
            $kode = $this->clean($sheet->getCell([2, $r])->getValue(), 50);
            if (!$kode || !preg_match('/^[KL]\/\d+\/\d{6}\/\d+$/i', $kode)) {
                continue;
            }
            $out[$kode] = [
                'KodeTiket' => $kode,
                'TanggalMasuk' => $this->dateFrom($sheet->getCell([3, $r])->getValue()),
                'Nama' => $this->clean($sheet->getCell([4, $r])->getValue(), 200),
                'NomorHak' => $this->clean($sheet->getCell([5, $r])->getValue(), 1000),
                'Jenis' => $this->clean($sheet->getCell([6, $r])->getValue(), 100),
                'PetugasLoket' => $this->clean($sheet->getCell([7, $r])->getValue(), 100),
                'VerifStart' => $this->dateFrom($sheet->getCell([8, $r])->getValue()),
                'VerifEnd' => $this->dateFrom($sheet->getCell([9, $r])->getValue()),
                'NamaVerif' => $this->clean($sheet->getCell([10, $r])->getValue(), 100),
                'KesimpulanVerif' => $this->clean($sheet->getCell([11, $r])->getValue(), 50),
                'WarkahStart' => $this->dateFrom($sheet->getCell([12, $r])->getValue()),
                'WarkahEnd' => $this->dateFrom($sheet->getCell([13, $r])->getValue()),
                'NamaWarkah' => $this->clean($sheet->getCell([14, $r])->getValue(), 100),
                'KesimpulanWarkah' => $this->clean($sheet->getCell([15, $r])->getValue(), 50),
                'ValidStart' => $this->dateFrom($sheet->getCell([16, $r])->getValue()),
                'ValidEnd' => $this->dateFrom($sheet->getCell([17, $r])->getValue()),
                'KesimpulanValid' => $this->clean($sheet->getCell([18, $r])->getValue(), 50),
                'AMStart' => $this->dateFrom($sheet->getCell([19, $r])->getValue()),
                'AMEnd' => $this->dateFrom($sheet->getCell([20, $r])->getValue()),
                'KesimpulanAM' => $this->clean($sheet->getCell([21, $r])->getValue(), 100),
                'SPS' => $this->clean($sheet->getCell([22, $r])->getValue(), 50),
            ];
        }

        return $out;
    }
    /**
     * Tulis satu tiket 2026 (AKTIF, bukan diarsipkan) beserta bidang & lembar kerja tahapan.
     */
    private function import2026Row(string $kode, array $r, string $status, ?\Carbon\Carbon $tglSelesai): void
    {
        $tgl = $r['TanggalMasuk'];
        if (!$tgl) {
            return;
        }
        $jp = $this->jpByKode[$this->jenisKode((string) ($r['Jenis'] ?? ''))] ?? (array_values($this->jpByKode)[0] ?? null);
        if (!$jp) {
            return;
        }
        $nama = $r['Nama'] ?? ucfirst(strtolower($kode));
        $loketId = $this->user($r['PetugasLoket'], 'loket');
        $verifUser = $r['NamaVerif'] ? $this->user($r['NamaVerif'], 'verifikator') : null;
        $warkahUser = $r['NamaWarkah'] ? $this->user($r['NamaWarkah'], 'warkah') : null;
        $bidangs = $this->parseNomorHak($r['NomorHak']);
        $sps = str_contains(strtoupper((string) ($r['SPS'] ?? '')), 'SUDAH');

        $tiket = Tiket::create([
            'no_tiket' => $kode,
            'status_pembetulan' => 'P0',
            'tanggal_masuk' => $tgl->toDateString(),
            'jenis_permohonan_id' => $jp,
            'nama_pemohon' => $nama,
            'nik_pemohon' => null,
            'no_hp_pemohon' => '000000000000',
            'jumlah_bidang' => count($bidangs),
            'petugas_loket_id' => $loketId,
            'status' => $status,
            'keterangan' => 'Tiket aktif 2026 dari dashboard. ' . trim((string) ($r['Jenis'] ?? '')),
            'tanggal_target_selesai' => null,
            'tanggal_selesai' => $tglSelesai?->toDateString(),
            'periode' => null,
            'tahun' => 2026,
            'sumber_data' => 'dashboard_aktif',
            'diarsipkan_pada' => null,
            'diarsipkan_oleh' => null,
        ]);

        foreach ($bidangs as $i => $b) {
            BidangTanah::create([
                'tiket_id' => $tiket->id,
                'nib' => null,
                'no_sertifikat_lama' => $b['no'],
                'no_sertifikat_elektronik' => null,
                'jenis_hak' => $b['jenis'],
                'nama_pemegang_hak' => $nama,
                'luas_m2' => null,
                'desa_kelurahan' => $b['kelurahan'],
                'kecamatan' => null,
                'status_plotting' => 'belum',
                'urutan' => $i + 1,
            ]);
        }

        $this->addRiwayat($tiket->id, 'Pendaftaran Loket', 'Loket (Tiket diterima)', null, 'Tiket aktif 2026 diimpor dari dashboard.', $tgl);

        $verifKesimpulan = strtoupper((string) ($r['KesimpulanVerif'] ?? ''));
        if ($r['VerifStart']) {
            $this->addRiwayat($tiket->id, 'Loket', 'Verifikator Berkas', $verifUser, 'Berkas diperiksa verifikator.', $r['VerifStart']);
            VerifikasiBerkas::create([
                'tiket_id' => $tiket->id,
                'verifikator_id' => $verifUser,
                'iterasi' => 1,
                'tanggal_diterima' => $r['VerifStart']->toDateString(),
                'tanggal_selesai' => $r['VerifEnd']?->toDateString(),
                'status' => match ($verifKesimpulan) {
                    'SELESAI (LENGKAP)', '4' => 'lengkap',
                    'PERBAIKAN' => 'perbaikan',
                    'DIBATALKAN' => 'batal',
                    default => 'proses',
                },
                'catatan' => 'Impor dashboard 2026: ' . ($verifKesimpulan ?: 'proses'),
                'dokumen_kurang' => [],
            ]);
        }
        $isWarkah = in_array($status, ['warkah', 'validasi', 'alih_media', 'selesai'], true);
        $isValid = in_array($status, ['validasi', 'alih_media', 'selesai'], true);
        $isAm = in_array($status, ['alih_media', 'selesai'], true);

        if ($isWarkah) {
            foreach ($tiket->bidangTanahs as $bidang) {
                LembarKerjaWarkah::create([
                    'tiket_id' => $tiket->id,
                    'bidang_id' => $bidang->id,
                    'petugas_id' => $warkahUser,
                    'tanggal_mulai' => ($r['WarkahStart'] ?? $r['VerifEnd'] ?? $tgl)->toDateString(),
                    'tanggal_selesai' => $r['WarkahEnd']?->toDateString(),
                    'lokasi_fisik' => 'Ruang Arsip Warkah',
                    'kondisi' => 'baik',
                    'status_keberadaan' => 'ada',
                    'status_scan' => true,
                    'catatan' => 'Impor dashboard 2026.',
                ]);
            }
            $this->addRiwayat($tiket->id, 'Verifikator Berkas', 'Lembar Kerja Warkah', $verifUser, 'Verifikasi dinyatakan LENGKAP.', $r['WarkahEnd'] ?? $r['VerifEnd'] ?? $tgl);

            if ($isValid) {
                $validatorUser = $r['NamaVerif'] ? $this->user($r['NamaVerif'], 'validator') : null;
                foreach ($tiket->bidangTanahs as $bidang) {
                    LembarKerjaValidasi::create([
                        'tiket_id' => $tiket->id,
                        'bidang_id' => $bidang->id,
                        'validator_id' => $validatorUser,
                        'tanggal_mulai' => ($r['ValidStart'] ?? $r['WarkahEnd'] ?? $r['VerifEnd'] ?? $tgl)->toDateString(),
                        'tanggal_selesai' => $r['ValidEnd']?->toDateString(),
                        'kesesuaian_nama' => 'sesuai',
                        'kesesuaian_luas' => 'sesuai',
                        'status_pra_btel' => 'selesai',
                        'status_pra_suel' => 'selesai',
                        'status_validasi' => 'lulus',
                        'catatan' => 'Impor dashboard 2026.',
                        'diteruskan_alih_media' => $isAm,
                    ]);
                }
                $this->addRiwayat($tiket->id, 'Lembar Kerja Warkah', 'Lembar Kerja Validator', $validatorUser, 'Warkah diserahkan ke validator.', $r['ValidStart'] ?? $r['WarkahEnd'] ?? $tgl);

                if ($isAm) {
                    $scanDone = $status === 'selesai' ? 'sudah' : 'belum';
                    foreach ($tiket->bidangTanahs as $bidang) {
                        LembarKerjaAlihMedia::create([
                            'tiket_id' => $tiket->id,
                            'bidang_id' => $bidang->id,
                            'petugas_id' => null,
                            'tanggal_mulai' => ($r['AMStart'] ?? $r['ValidEnd'] ?? $tgl)->toDateString(),
                            'tanggal_selesai' => $r['AMEnd']?->toDateString(),
                            'status_scan_buku_tanah' => $scanDone,
                            'status_scan_surat_ukur' => $scanDone,
                            'status_scan_warkah' => $scanDone,
                            'status_upload_kkp' => $scanDone,
                            'status_ttd_elektronik' => $scanDone,
                            'tanggal_terbit_sertifikat_el' => ($status === 'selesai' && $r['AMEnd']) ? $r['AMEnd']->toDateString() : null,
                            'catatan' => 'Impor dashboard 2026.',
                        ]);
                    }
                    $this->addRiwayat($tiket->id, 'Lembar Kerja Validator', $status === 'selesai' ? 'SELESAI (Sertifikat Elektronik Terbit)' : 'Lembar Kerja Alih Media', null, $status === 'selesai' ? 'Sertifikat elektronik terbit.' : 'Proses alih media berjalan.', $r['AMEnd'] ?? $r['ValidEnd'] ?? $tgl);
                }
            }
        } elseif ($status === 'dikembalikan') {
            $this->addRiwayat($tiket->id, 'Verifikator Berkas', 'Dikembalikan (Loket/Pemohon)', $verifUser, 'Berkas memerlukan perbaikan.', $r['VerifEnd'] ?? $tgl);
        } elseif ($status === 'batal') {
            $this->addRiwayat($tiket->id, 'Verifikator Berkas', 'Permohonan Dibatalkan', $verifUser, 'Permohonan dibatalkan.', $r['VerifEnd'] ?? $tgl);
        }
    }
    /**
     * Update hanya kolom status/tanggal_selesai pada tiket 2026 yang sudah ada.
     */
    private function update2026Row(string $kode, array $r, string $status, ?\Carbon\Carbon $tglSelesai): void
    {
        $tiket = Tiket::where('no_tiket', $kode)->first();
        if (!$tiket) {
            return;
        }
        $tiket->status = $status;
        if ($tglSelesai) {
            $tiket->tanggal_selesai = $tglSelesai->toDateString();
        }
        $tiket->save();
    }
}





