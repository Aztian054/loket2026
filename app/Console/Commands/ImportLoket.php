<?php

namespace App\Console\Commands;

use App\Models\BidangTanah;
use App\Models\JenisPermohonan;
use App\Models\LembarKerjaAlihMedia;
use App\Models\LembarKerjaValidasi;
use App\Models\LembarKerjaWarkah;
use App\Models\RiwayatStatus;
use App\Models\Tiket;
use App\Models\User;
use App\Models\VerifikasiBerkas;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class ImportLoket extends Command
{
    protected $signature = 'import:loket
        {--wipe : Kosongkan tabel tikets & turunannya sebelum impor}
        {--dry : Simulasi tanpa menulis ke database}
        {--with2026 : Coba impor tiket aktif 2026 dari 00 Dashboard (bisa lambat)}
        {--limit= : Batasi jumlah tiket yang diimpor (debug)}';

    protected $description = 'Impor data real 2024/2025 dari Excel (Kertas Kerja Migrasi + Arsip 2025) sebagai tiket arsip.';

    protected array $jpByKode = [];
    protected array $userByName = [];
    protected ?int $adminId = null;
    protected int $usersCreated = 0;
    protected array $tahunCount = [];
    protected array $existingKode = [];

    public function handle(): int
    {
        $this->newLine();
        $this->info('=== IMPOR DATA REAL LOKET 2024/2025 (ARSIP) ===');

        $base = base_path('Template_Panduan/Project Loket');
        $migrasi = $base . '/Kertas Kerja Migrasi.xlsx';
        $arsip25 = $base . '/#Arsip 2025 (23 Juli 2025)/02 Verifikator Berkas.xlsx';
        $aktif00 = $base . '/00 Dashboard Control Permohonan.xlsx';

        if (!is_file($migrasi)) { $this->error("Tidak ditemukan: {$migrasi}"); return self::FAILURE; }
        if (!is_file($arsip25)) { $this->error("Tidak ditemukan: {$arsip25}"); return self::FAILURE; }

        $this->line(sprintf(
            'Sebelum impor : tikets=%d | bidang_tanahs=%d | riwayat_statuses=%d | users=%d',
            Tiket::count(), BidangTanah::count(), RiwayatStatus::count(), User::count()
        ));

        $dry = $this->option('dry');

        if ($this->option('wipe') && !$dry) {
            $this->warn('Menghapus tabel tikets & turunannya (sudah disetujui user)...');
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            foreach (['riwayat_statuses','lembar_kerja_alih_medias','lembar_kerja_validasis','lembar_kerja_warkahs','verifikasi_berkas','bidang_tanahs','tikets'] as $table) {
                DB::table($table)->truncate();
            }
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            $this->info('Selesai dikosongkan.');
        }

        $this->loadJenis();
        $this->loadUsers();
        $this->existingKode = Tiket::pluck('no_tiket')->flip()->all();

        $this->line('');
        $this->info('Membaca file Excel...');
        $this->line('  - Migrasi Dashboard (master 2024)');
        $dashboard = $this->readDashboardRows($migrasi);
        $this->line('  - Migrasi Verifikator/Warkah/Validator/AlihMedia (pelengkap)');
        [$verifMap, $warkahMap, $validMap, $amMap] = $this->readStageMaps($migrasi);
        $this->line('  - Arsip 2025 Verifikator (tiket 2024-2025)');
        $arsip25Rows = $this->readArsip2025Rows($arsip25);

        $this->newLine();
        $this->info('Mulai impor tiket...');
        $limit = (int) ($this->option('limit') ?: 0);
        $count = 0;

        foreach ($dashboard as $kode => $row) {
            if ($limit && $count >= $limit) break;
            if (!$dry) $this->importDashboardRow($kode, $row, $verifMap, $warkahMap, $validMap, $amMap);
            $count++;
        }
        $this->line(sprintf('Dashboard 2024 : %d tiket %s.', $count, $dry ? '(dry)' : 'diimpor'));

        $count25 = 0;
        foreach ($arsip25Rows as $kode => $row) {
            if (isset($dashboard[$kode])) continue;
            if ($limit && $count25 >= $limit) break;
            if (!$dry) $this->importArsip2025Row($kode, $row);
            $count25++;
        }
        $this->line(sprintf('Arsip 2025     : %d tiket (di luar 2024) %s.', $count25, $dry ? '(dry)' : 'diimpor'));

        if ($this->option('with2026') && !$dry) {
            $n26 = $this->importActive2026($aktif00);
            $this->line(sprintf('Aktif 2026     : %d tiket diimpor sebagai PROSES/AKTIF.', $n26));
        } elseif ($this->option('with2026')) {
            $this->warn('--with2026 dilewati karena --dry.');
        } else {
            $this->warn('--with2026 tidak digunakan (file 12MB); data aktif 2026 tidak diimpor.');
        }

        $this->newLine();
        if (!$dry) { $this->summary(); }
        $this->info($dry ? 'SELESAI (MODE DRY) - TIDAK ADA DATA DITULIS' : 'IMPOR SELESAI');

        return self::SUCCESS;
    }

    protected function loadJenis(): void
    {
        foreach (JenisPermohonan::all() as $jp) {
            $this->jpByKode[$jp->kode] = $jp->id;
        }
    }

    protected function loadUsers(): void
    {
        $this->adminId = User::where('username', 'admin')->value('id');
        foreach (User::all() as $u) {
            $this->userByName[$this->normName($u->name)] = $u->id;
        }
    }

    protected function normName(?string $name): string
    {
        $n = strtolower(trim((string) $name));
        $n = preg_replace('/\s*\((bt|su|php|konsultan|notaris|ppat)\)/i', '', $n);
        $n = preg_replace('/[^a-z0-9]+/', ' ', $n);
        return trim(preg_replace('/\s+/', ' ', $n));
    }

    protected function user(?string $name, string $role): ?int
    {
        $raw = trim((string) $name);
        if ($raw === '' || in_array(strtoupper($raw), ['#REF!','#ERROR!','#N/A'], true)) return null;
        $key = $this->normName($raw);
        if ($key === '') return null;
        if (isset($this->userByName[$key])) {
            return $this->userByName[$key];
        }
        $display = trim(preg_replace('/\s*\((BT|SU|PHP|KONSULTAN|NOTARIS|PPAT)\)/i', '', $raw));
        $base = preg_replace('/[^a-z0-9]+/', '_', strtolower($display));
        $base = trim($base, '_') ?: 'petugas';
        $username = $base;
        $i = 1;
        while (User::where('username', $username)->exists()) {
            $username = $base . $i++;
        }
        $user = User::create([
            'name' => $display,
            'username' => $username,
            'email' => $username . '@loket.balam.go.id',
            'password' => Hash::make('loket123'),
            'role' => $role,
            'is_active' => true,
        ]);
        $this->userByName[$key] = $user->id;
        $this->usersCreated++;
        $this->line(sprintf('    + user baru: %s (rol %s)', $display, $role));
        return $user->id;
    }    protected function clean(mixed $v, int $max = 200): ?string
    {
        if ($v === null) return null;
        if (is_float($v) || is_int($v)) return (string) $v;
        if (is_object($v) && method_exists($v, 'getPlainText')) $v = $v->getPlainText();
        $s = trim((string) $v);
        if ($s === '') return null;
        if (str_starts_with($s, '=') && str_contains($s, 'DUMMYFUNCTION')) {
            if (preg_match('/DUMMYFUNCTION\(\s*"?"?(.*?)"?"?\s*\)/s', $s, $m)) {
                $s = trim($m[1]);
            } else {
                return null;
            }
        }
        if ($s === '') return null;
        $bad = ['#REF!','#ERROR!','#N/A','#DIV/0!','#VALUE!','#NAME?','///','-','--','.','Jangan Hapus','Jangan Hapus / Ganti Tiket'];
        if (in_array(strtoupper($s), $bad, true)) return null;
        return mb_substr($s, 0, $max);
    }

    protected function dateFrom(mixed $v): ?Carbon
    {
        if ($v === null) return null;
        if (is_float($v) || is_int($v)) {
            $num = (float) $v;
            if ($num < 10000 || $num > 80000) return null;
            try { return Carbon::instance(Date::excelToDateTimeObject($num)); } catch (\Throwable) { return null; }
        }
        if (is_string($v)) {
            $s = trim($v);
            if ($s === '') return null;
            if (is_numeric($s)) return $this->dateFrom((float) $s);
            if (preg_match('/^\d{6}$/', $s)) {
                $dd = (int) substr($s, 0, 2); $mm = (int) substr($s, 2, 2); $yy = 2000 + (int) substr($s, 4, 2);
                try { return Carbon::create($yy, $mm, $dd)->startOfDay(); } catch (\Throwable) { return null; }
            }
            try { return Carbon::parse($s); } catch (\Throwable) { return null; }
        }
        if ($v instanceof \DateTimeInterface) return Carbon::instance($v);
        return null;
    }

    protected function parseNomorHak(?string $text): array
    {
        $out = [];
        if ($text) {
            foreach (preg_split('/[;]+/', $text) as $part) {
                $part = trim($part);
                if ($part === '' || in_array(strtoupper($part), ['#REF!','#ERROR!','#N/A','//','///'], true)) continue;
                $jenis = null; $no = null; $kel = null;
                if (preg_match('/^([A-Za-z]{1,4})[\s.]*\s*(\d+[A-Za-z0-9]*)(.*)$/', $part, $m)) {
                    $jenis = $this->mapJenisHak(strtoupper($m[1]));
                    $no = $m[2];
                    $rest = ltrim($m[3], '/ ');
                    $kel = ($rest !== '') ? $rest : null;
                } elseif (str_contains($part, '/')) {
                    $seg = explode('/', $part, 2);
                    $kel = trim($seg[1] ?? '') ?: null;
                } else {
                    $kel = $part;
                }
                $out[] = ['jenis' => $jenis, 'no' => $no, 'kelurahan' => $kel];
            }
        }
        return $out ?: [['jenis' => null, 'no' => null, 'kelurahan' => null]];
    }

    protected function mapJenisHak(string $prefix): ?string
    {
        return match ($prefix) {
            'M' => 'HM',
            'B' => 'HGB',
            'C' => 'HM',
            'HGB' => 'HGB',
            'HGU' => 'HGU',
            'HP' => 'HP',
            'HPL' => 'HPL',
            default => null,
        };
    }

    protected function jenisKode(string $text): string
    {
        $u = strtoupper(preg_replace('/\s+/', ' ', trim($text)));
        $rules = [
            'WARIS' => 'JP03', 'KEWARISAN' => 'JP03', 'PENGADILAN' => 'JP03',
            'HIBAH' => 'JP04',
            'ROYA' => 'JP05', 'BPHTB' => 'JP05', 'HAPUS' => 'JP05',
            'PEMECAHAN' => 'JP06', 'PECAH' => 'JP06',
            'PENGGABUNGAN' => 'JP07',
            'HAK TANGGUNGAN' => 'JP12', 'HT-' => 'JP12',
            'ALIH MEDIA' => 'JP13',
            'SERTIFIKASI' => 'JP14', 'BMN' => 'JP14',
            'PENGUKURAN' => 'JP10', 'PENATAAN' => 'JP10', 'PLOTING' => 'JP10', 'PLOTTING' => 'JP10',
            'GANDES' => 'JP10', 'SKPT' => 'JP10', 'PENGECEKAN' => 'JP10', 'PENGECEKKAN' => 'JP10',
            'PERMOHONAN SK' => 'JP10', 'PENDAFTARAN SK' => 'JP10', 'SITU' => 'JP10', 'PETA' => 'JP10',
            'PERTAMA KALI' => 'JP01', 'PTPGT' => 'JP01', 'TANAH MENTAH' => 'JP01', 'PENDAFTARAN' => 'JP01',
            'BLOKIR' => 'JP11', 'SITA' => 'JP11', 'HILANG' => 'JP11', 'KREDITUR' => 'JP11',
            'JUAL' => 'JP02', 'BELI' => 'JP02', 'AJB' => 'JP02', 'LELANG' => 'JP02',
            'BALIK NAMA' => 'JP02', 'APHB' => 'JP02', 'PHB' => 'JP02', 'PEMBAGIAN' => 'JP02',
            'BN' => 'JP02', 'PH' => 'JP02', 'PERALIHAN' => 'JP02',
            'GANTI' => 'JP08', 'PERUBAHAN' => 'JP08', 'PENINGKATAN' => 'JP08', 'ASPEK' => 'JP08',
            'HGB' => 'JP08', 'KELURAHAN' => 'JP08', 'KECAMATAN' => 'JP08',
        ];
        foreach ($rules as $needle => $kode) {
            if (str_contains($u, $needle)) return $kode;
        }
        return 'JP02';
    }

    protected function statusFromRow(array $r): array
    {
        $am = strtoupper((string) ($r['KesimpulanAM'] ?? ''));
        $valid = strtoupper((string) ($r['KesimpulanValid'] ?? ''));
        $warkah = strtoupper((string) ($r['KesimpulanWarkah'] ?? ''));
        $verif = strtoupper((string) ($r['KesimpulanVerif'] ?? ''));
        if ($verif === '4') $verif = 'SELESAI (LENGKAP)';
        $status = 'diterima';
        if ($am !== '') { $status = ($am === 'SELESAI') ? 'selesai' : 'alih_media'; }
        elseif ($valid !== '') { $status = ($valid === 'SELESAI') ? 'alih_media' : 'validasi'; }
        elseif ($warkah !== '') { $status = str_starts_with($warkah, 'DISERAHKAN') ? 'validasi' : 'warkah'; }
        elseif ($verif !== '') {
            $status = match ($verif) {
                'SELESAI (LENGKAP)' => 'warkah',
                'PERBAIKAN' => 'dikembalikan',
                'DIBATALKAN' => 'batal',
                default => 'verifikasi',
            };
        } elseif (!empty($r['VerifStart'])) { $status = 'verifikasi'; }
        $tglSelesai = null;
        if ($status === 'selesai') {
            $tglSelesai = $r['AMEnd'] ?? $r['ValidEnd'] ?? $r['VerifEnd'] ?? null;
        }
        return [$status, $tglSelesai];
    }

    protected function lastStageDate(array $r): ?Carbon
    {
        foreach (['AMEnd', 'ValidEnd', 'WarkahEnd', 'VerifEnd'] as $k) {
            if (!empty($r[$k])) return $r[$k];
        }
        return null;
    }

    private function readDashboardRows(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);
        $sp = $reader->load($path);
        $sheet = $sp->getSheetByName('Dashboard');
        $out = [];
        $last = $sheet->getHighestDataRow();
        for ($r = 2; $r <= $last; $r++) {
            $kode = $this->clean($sheet->getCell([2, $r])->getValue(), 50);
            if (!$kode || !preg_match('/^[KL]\/\d+\/\d{6}\/\d+$/i', $kode)) continue;
            $tgl = $this->dateFrom($sheet->getCell([3, $r])->getValue());
            $row = [
                'KodeTiket' => $kode,
                'TanggalMasuk' => $tgl,
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
            $row['Tahun'] = $tgl ? (string) $tgl->year : null;
            $out[$kode] = $row;
        }
        return $out;
    }    private function readStageMaps(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);
        $sp = $reader->load($path);

        $verif = [];
        $sh = $sp->getSheetByName('Verifikator');
        for ($r = 2; $r <= $sh->getHighestDataRow(); $r++) {
            $kode = $this->clean($sh->getCell([1, $r])->getValue(), 50);
            if (!$kode || !preg_match('/^[KL]\/\d+\/\d{6}\/\d+$/i', $kode)) continue;
            $verif[$kode] = [
                'telpon' => $this->clean($sh->getCell([5, $r])->getValue(), 20),
                'namaVerif' => $this->clean($sh->getCell([10, $r])->getValue(), 100),
                'tglTerima' => $this->dateFrom($sh->getCell([11, $r])->getValue()),
                'status' => $this->clean($sh->getCell([22, $r])->getValue(), 50),
                'tglSelesai' => $this->dateFrom($sh->getCell([23, $r])->getValue()),
            ];
        }

        $warkah = [];
        $sh = $sp->getSheetByName('Warkah');
        for ($r = 2; $r <= $sh->getHighestDataRow(); $r++) {
            $kode = $this->clean($sh->getCell([2, $r])->getValue(), 50);
            if (!$kode || !preg_match('/^[KL]\/\d+\/\d{6}\/\d+$/i', $kode)) continue;
            $warkah[$kode] = [
                'noSertifikat' => $this->clean($sh->getCell([3, $r])->getValue(), 100),
                'petugas' => $this->clean($sh->getCell([6, $r])->getValue(), 100),
                'status' => $this->clean($sh->getCell([7, $r])->getValue(), 50),
                'tglSerah' => $this->dateFrom($sh->getCell([8, $r])->getValue()),
            ];
        }

        $valid = [];
        $sh = $sp->getSheetByName('Validator');
        for ($r = 2; $r <= $sh->getHighestDataRow(); $r++) {
            $kode = $this->clean($sh->getCell([3, $r])->getValue(), 50);
            if (!$kode || !preg_match('/^[KL]\/\d+\/\d{6}\/\d+$/i', $kode)) continue;
            $valid[$kode] = [
                'namaBt' => $this->clean($sh->getCell([7, $r])->getValue(), 100),
                'statusBt' => $this->clean($sh->getCell([8, $r])->getValue(), 50),
                'namaSu' => $this->clean($sh->getCell([10, $r])->getValue(), 100),
                'statusSu' => $this->clean($sh->getCell([11, $r])->getValue(), 50),
                'kesimpulan' => $this->clean($sh->getCell([13, $r])->getValue(), 50),
            ];
        }

        $am = [];
        $sh = $sp->getSheetByName('Alih Media');
        for ($r = 2; $r <= $sh->getHighestDataRow(); $r++) {
            $kode = $this->clean($sh->getCell([2, $r])->getValue(), 50);
            if (!$kode || !preg_match('/^[KL]\/\d+\/\d{6}\/\d+$/i', $kode)) continue;
            $am[$kode] = [
                'namaBt' => $this->clean($sh->getCell([7, $r])->getValue(), 100),
                'statusBt' => $this->clean($sh->getCell([8, $r])->getValue(), 50),
                'namaSu' => $this->clean($sh->getCell([10, $r])->getValue(), 100),
                'statusSu' => $this->clean($sh->getCell([11, $r])->getValue(), 50),
                'kesimpulan' => $this->clean($sh->getCell([13, $r])->getValue(), 100),
            ];
        }

        return [$verif, $warkah, $valid, $am];
    }

    private function readArsip2025Rows(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);
        $sp = $reader->load($path);
        $sh = $sp->getSheetByName('Verifikator') ?? $sp->getSheet(0);
        $out = [];
        for ($r = 2; $r <= $sh->getHighestDataRow(); $r++) {
            $kode = $this->clean($sh->getCell([1, $r])->getValue(), 50);
            if (!$kode || !preg_match('/^[KL]\/\d+\/\d{6}\/\d+$/i', $kode)) continue;
            $out[$kode] = [
                'TanggalMasuk' => $this->dateFrom($sh->getCell([2, $r])->getValue()),
                'Nama' => $this->clean($sh->getCell([4, $r])->getValue(), 200),
                'Telpon' => $this->clean($sh->getCell([5, $r])->getValue(), 20),
                'Jenis' => $this->clean($sh->getCell([6, $r])->getValue(), 100),
                'NomorHak' => $this->clean($sh->getCell([7, $r])->getValue(), 1000),
                'Kelurahan' => $this->clean($sh->getCell([8, $r])->getValue(), 100),
                'PetugasLoket' => $this->clean($sh->getCell([9, $r])->getValue(), 100),
                'NamaVerif' => $this->clean($sh->getCell([10, $r])->getValue(), 100),
                'TglTerima' => $this->dateFrom($sh->getCell([11, $r])->getValue()),
                'StatusBerkas' => $this->clean($sh->getCell([22, $r])->getValue(), 50),
                'TglSelesaiVerif' => $this->dateFrom($sh->getCell([23, $r])->getValue()),
            ];
        }
        return $out;
    }    private function importDashboardRow(string $kode, array $r, array $verifMap, array $warkahMap, array $validMap, array $amMap): void
    {
        if (!$r['TanggalMasuk']) return;
        if (isset($this->existingKode[$kode])) return;
        $jp = $this->jpByKode[$this->jenisKode((string) ($r['Jenis'] ?? ''))] ?? (array_values($this->jpByKode)[0] ?? null);
        if (!$jp) return;
        $loketId = $this->user($r['PetugasLoket'], 'loket');
        $verifUser = $r['NamaVerif'] ? $this->user($r['NamaVerif'], 'verifikator') : ($verifMap[$kode]['namaVerif'] ?? null ? $this->user($verifMap[$kode]['namaVerif'], 'verifikator') : null);
        $warkahUser = $r['NamaWarkah'] ? $this->user($r['NamaWarkah'], 'warkah') : ($warkahMap[$kode]['petugas'] ?? null ? $this->user($warkahMap[$kode]['petugas'], 'warkah') : null);
        [$status, $tglSelesai] = $this->statusFromRow($r);
        $bidangs = $this->parseNomorHak($r['NomorHak']);
        $tahun = (string) ($r['Tahun'] ?? ($r['TanggalMasuk']->year ?? ''));
        $telpon = preg_replace('/\D+/', '', (string) ($verifMap[$kode]['telpon'] ?? '000000000000'));
        $telpon = strlen($telpon) >= 9 ? substr($telpon, 0, 18) : '000000000000';
        $arsipPada = $this->lastStageDate($r) ?? $r['TanggalMasuk'];
        $sps = str_contains(strtoupper((string) ($r['SPS'] ?? '')), 'SUDAH');
        $nama = $r['Nama'] ?? ucfirst(strtolower($kode));

        $tiket = Tiket::create([
            'no_tiket' => $kode,
            'status_pembetulan' => 'P0',
            'tanggal_masuk' => $r['TanggalMasuk']->toDateString(),
            'jenis_permohonan_id' => $jp,
            'nama_pemohon' => $nama,
            'nik_pemohon' => null,
            'no_hp_pemohon' => $telpon,
            'jumlah_bidang' => count($bidangs),
            'petugas_loket_id' => $loketId,
            'status' => $status,
            'keterangan' => 'Tiket migrasi data real (Excel). ' . trim((string) ($r['Jenis'] ?? '')),
            'tanggal_target_selesai' => null,
            'tanggal_selesai' => $tglSelesai?->toDateString(),
            'periode' => $tahun,
            'tahun' => $tahun,
            'sumber_data' => 'migrasi_dashboard',
            'diarsipkan_pada' => $arsipPada->copy()->endOfDay()->toDateTimeString(),
            'diarsipkan_oleh' => null,
        ]);
        $this->existingKode[$kode] = true;

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

        $this->addRiwayat($tiket->id, 'Pendaftaran Loket', 'Loket (Tiket diterima)', null, 'Data tiket dimigrasi dari Excel.', $r['TanggalMasuk']);

        $verifKesimpulan = strtoupper((string) ($r['KesimpulanVerif'] ?? ''));
        if ($r['VerifStart']) {
            $this->addRiwayat($tiket->id, 'Loket', 'Verifikator Berkas', $verifUser, 'Berkas diperiksa verifikator.', $r['VerifStart']);
            $verifStatus = match ($verifKesimpulan) {
                'SELESAI (LENGKAP)', '4' => 'lengkap',
                'PERBAIKAN' => 'perbaikan',
                'DIBATALKAN' => 'batal',
                default => 'proses',
            };
            VerifikasiBerkas::create([
                'tiket_id' => $tiket->id,
                'verifikator_id' => $verifUser,
                'iterasi' => 1,
                'tanggal_diterima' => $r['VerifStart']->toDateString(),
                'tanggal_selesai' => $r['VerifEnd']?->toDateString(),
                'status' => $verifStatus,
                'catatan' => 'Impor migrasi: ' . ($verifKesimpulan ?: 'proses'),
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
                    'tanggal_mulai' => ($r['WarkahStart'] ?? $r['VerifEnd'] ?? $r['TanggalMasuk'])->toDateString(),
                    'tanggal_selesai' => $r['WarkahEnd']?->toDateString(),
                    'lokasi_fisik' => 'Ruang Arsip Warkah',
                    'kondisi' => 'baik',
                    'status_keberadaan' => 'ada',
                    'status_scan' => true,
                    'catatan' => 'Impor migrasi Excel.',
                ]);
            }
            $this->addRiwayat($tiket->id, 'Verifikator Berkas', 'Lembar Kerja Warkah', $verifUser, 'Verifikasi dinyatakan LENGKAP.', $r['WarkahEnd'] ?? $r['VerifEnd'] ?? $r['TanggalMasuk']);

            if ($isValid) {
                $validatorUser = $this->user($validMap[$kode]['namaBt'] ?? $r['NamaVerif'], 'validator');
                foreach ($tiket->bidangTanahs as $bidang) {
                    LembarKerjaValidasi::create([
                        'tiket_id' => $tiket->id,
                        'bidang_id' => $bidang->id,
                        'validator_id' => $validatorUser,
                        'tanggal_mulai' => ($r['ValidStart'] ?? $r['WarkahEnd'] ?? $r['VerifEnd'] ?? $r['TanggalMasuk'])->toDateString(),
                        'tanggal_selesai' => $r['ValidEnd']?->toDateString(),
                        'kesesuaian_nama' => 'sesuai',
                        'kesesuaian_luas' => 'sesuai',
                        'status_pra_btel' => 'selesai',
                        'status_pra_suel' => 'selesai',
                        'status_validasi' => 'lulus',
                        'catatan' => 'Impor migrasi Excel.',
                        'diteruskan_alih_media' => $isAm,
                    ]);
                }
                $this->addRiwayat($tiket->id, 'Lembar Kerja Warkah', 'Lembar Kerja Validator', $validatorUser, 'Warkah diserahkan ke validator.', $r['ValidStart'] ?? $r['WarkahEnd'] ?? $r['TanggalMasuk']);

                if ($isAm) {
                    $amUser = $this->user($amMap[$kode]['namaBt'] ?? null, 'alih_media');
                    $scanDone = $status === 'selesai' ? 'sudah' : 'belum';
                    foreach ($tiket->bidangTanahs as $bidang) {
                        LembarKerjaAlihMedia::create([
                            'tiket_id' => $tiket->id,
                            'bidang_id' => $bidang->id,
                            'petugas_id' => $amUser,
                            'tanggal_mulai' => ($r['AMStart'] ?? $r['ValidEnd'] ?? $r['TanggalMasuk'])->toDateString(),
                            'tanggal_selesai' => $r['AMEnd']?->toDateString(),
                            'status_scan_buku_tanah' => $scanDone,
                            'status_scan_surat_ukur' => $scanDone,
                            'status_scan_warkah' => $scanDone,
                            'status_upload_kkp' => $scanDone,
                            'status_ttd_elektronik' => $scanDone,
                            'tanggal_terbit_sertifikat_el' => ($status === 'selesai' && $r['AMEnd']) ? $r['AMEnd']->toDateString() : null,
                            'catatan' => 'Impor migrasi Excel.',
                        ]);
                    }
                    $this->addRiwayat($tiket->id, 'Lembar Kerja Validator', $status === 'selesai' ? 'SELESAI (Sertifikat Elektronik Terbit)' : 'Lembar Kerja Alih Media', $amUser, $status === 'selesai' ? 'Sertifikat elektronik terbit.' : 'Proses alih media berjalan.', $r['AMEnd'] ?? $r['ValidEnd'] ?? $r['TanggalMasuk']);
                }
            }
        } elseif ($status === 'dikembalikan') {
            $this->addRiwayat($tiket->id, 'Verifikator Berkas', 'Dikembalikan (Loket/Pemohon)', $verifUser, 'Berkas memerlukan perbaikan.', $r['VerifEnd'] ?? $r['TanggalMasuk']);
        } elseif ($status === 'batal') {
            $this->addRiwayat($tiket->id, 'Verifikator Berkas', 'Permohonan Dibatalkan', $verifUser, 'Permohonan dibatalkan.', $r['VerifEnd'] ?? $r['TanggalMasuk']);
        }

        $this->addRiwayat($tiket->id, 'Aktif (' . strtoupper($status) . ')', 'Arsip Tahunan ' . ($tahun ?: '-'), null, 'Migrasi arsip data real tahun ' . $tahun . '.', $arsipPada);
        $this->tahunCount[$tahun] = ($this->tahunCount[$tahun] ?? 0) + 1;
    }    private function importArsip2025Row(string $kode, array $row): void
    {
        $tgl = $row['TanggalMasuk'];
        if (!$tgl) return;
        if (isset($this->existingKode[$kode])) return;
        $jp = $this->jpByKode[$this->jenisKode((string) ($row['Jenis'] ?? ''))] ?? (array_values($this->jpByKode)[0] ?? null);
        if (!$jp) return;
        $sb = strtoupper((string) ($row['StatusBerkas'] ?? ''));
        $status = match ($sb) {
            'PERBAIKAN' => 'dikembalikan',
            'SELESAI (LENGKAP)', 'LENGKAP', 'SELESAI' => 'selesai',
            'DIBATALKAN', 'BATAL' => 'batal',
            default => 'diterima',
        };
        $tahun = (string) $tgl->year;
        $loketId = $this->user($row['PetugasLoket'], 'loket');
        $verifUser = $row['NamaVerif'] ? $this->user($row['NamaVerif'], 'verifikator') : null;
        $telpon = preg_replace('/\D+/', '', (string) ($row['Telpon'] ?? '000000000000'));
        $telpon = strlen($telpon) >= 9 ? substr($telpon, 0, 18) : '000000000000';
        $nomorHak = trim((string) ($row['NomorHak'] ?? ''));
        if ($row['Kelurahan']) { $nomorHak .= $nomorHak !== '' ? ';' . $row['Kelurahan'] : $row['Kelurahan']; }
        $bidangs = $this->parseNomorHak($nomorHak);
        $arsipPada = $row['TglSelesaiVerif'] ?? $tgl;
        $nama = $row['Nama'] ?? ucfirst(strtolower($kode));

        $tiket = Tiket::create([
            'no_tiket' => $kode,
            'status_pembetulan' => 'P0',
            'tanggal_masuk' => $tgl->toDateString(),
            'jenis_permohonan_id' => $jp,
            'nama_pemohon' => $nama,
            'nik_pemohon' => null,
            'no_hp_pemohon' => $telpon,
            'jumlah_bidang' => count($bidangs),
            'petugas_loket_id' => $loketId,
            'status' => $status,
            'keterangan' => 'Tiket arsip 2025 (verifikator). ' . trim((string) ($row['Jenis'] ?? '')),
            'tanggal_target_selesai' => null,
            'tanggal_selesai' => ($status === 'selesai' ? ($row['TglSelesaiVerif']?->toDateString() ?? $tgl->toDateString()) : null),
            'periode' => $tahun,
            'tahun' => $tahun,
            'sumber_data' => 'arsip_2025_verifikator',
            'diarsipkan_pada' => $arsipPada->copy()->endOfDay()->toDateTimeString(),
            'diarsipkan_oleh' => null,
        ]);
        $this->existingKode[$kode] = true;

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

        $this->addRiwayat($tiket->id, 'Pendaftaran Loket', 'Loket (Tiket diterima)', null, 'Data tiket diimpor dari arsip 2025.', $tgl);
        if ($row['TglTerima']) {
            $this->addRiwayat($tiket->id, 'Loket', 'Verifikator Berkas', $verifUser, 'Berkas diperiksa verifikator.', $row['TglTerima']);
            VerifikasiBerkas::create([
                'tiket_id' => $tiket->id,
                'verifikator_id' => $verifUser,
                'iterasi' => 1,
                'tanggal_diterima' => $row['TglTerima']->toDateString(),
                'tanggal_selesai' => $row['TglSelesaiVerif']?->toDateString(),
                'status' => match ($sb) { 'PERBAIKAN' => 'perbaikan', 'SELESAI (LENGKAP)', 'LENGKAP', 'SELESAI' => 'lengkap', 'DIBATALKAN' => 'batal', default => 'proses' },
                'catatan' => 'Impor arsip 2025.',
                'dokumen_kurang' => [],
            ]);
        }
        if ($status === 'dikembalikan') {
            $this->addRiwayat($tiket->id, 'Verifikator Berkas', 'Dikembalikan (Loket/Pemohon)', $verifUser, 'Berkas memerlukan perbaikan.', $row['TglSelesaiVerif'] ?? $tgl);
        } elseif ($status === 'selesai') {
            $this->addRiwayat($tiket->id, 'Verifikator Berkas', 'SELESAI', $verifUser, 'Verifikasi selesai; tiket diarsipkan.', $row['TglSelesaiVerif'] ?? $tgl);
        }
        $this->addRiwayat($tiket->id, 'Aktif (' . strtoupper($status) . ')', 'Arsip Tahunan ' . $tahun, null, 'Migrasi arsip data real (arsip 2025).', $arsipPada);
        $this->tahunCount[$tahun] = ($this->tahunCount[$tahun] ?? 0) + 1;
    }

    private function importActive2026(string $path): int
    {
        try {
            // File 12MB besar: alihkan cache sel PhpSpreadsheet ke disk agar tidak boros RAM
            \PhpOffice\PhpSpreadsheet\Settings::setCache(\Illuminate\Support\Facades\Cache::store('file'));
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $reader->setReadEmptyCells(false);
            $reader->setReadFilter(new class implements IReadFilter {
                public function readCell($columnAddress, $row, $worksheetName = ''): bool {
                    return Coordinate::columnIndexFromString((string) $columnAddress) <= 7;
                }
            });
            $sp = $reader->load($path);
        } catch (\Throwable $e) {
            $this->warn('Gagal membaca 00 Dashboard aktif: ' . $e->getMessage());
            return 0;
        }
        $sheet = $sp->getSheetByName('Dashboard') ?? $sp->getSheet(0);
        $n = 0;
        for ($r = 2; $r <= $sheet->getHighestDataRow(); $r++) {
            $kode = $this->clean($sheet->getCell([2, $r])->getValue(), 50);
            if (!$kode || !preg_match('/^[KL]\/\d+\/\d{6}\/\d+$/i', $kode)) continue;
            $tgl = $this->dateFrom($sheet->getCell([3, $r])->getValue());
            if (!$tgl || $tgl->year !== 2026) continue;
            if (Tiket::where('no_tiket', $kode)->exists()) continue;
            $this->importActiveRow($kode, [
                'TanggalMasuk' => $tgl,
                'Nama' => $this->clean($sheet->getCell([4, $r])->getValue(), 200),
                'NomorHak' => $this->clean($sheet->getCell([5, $r])->getValue(), 1000),
                'Jenis' => $this->clean($sheet->getCell([6, $r])->getValue(), 100),
                'PetugasLoket' => $this->clean($sheet->getCell([7, $r])->getValue(), 100),
            ]);
            $n++;
        }
        return $n;
    }

    private function importActiveRow(string $kode, array $r): void
    {
        $tgl = $r['TanggalMasuk'];
        if (isset($this->existingKode[$kode])) return;
        $jp = $this->jpByKode[$this->jenisKode((string) ($r['Jenis'] ?? ''))] ?? (array_values($this->jpByKode)[0] ?? null);
        if (!$jp) return;
        $nama = $r['Nama'] ?? ucfirst(strtolower($kode));
        $telpon = '000000000000';
        $bidangs = $this->parseNomorHak($r['NomorHak']);
        $tahun = (string) $tgl->year;

        $tiket = Tiket::create([
            'no_tiket' => $kode,
            'status_pembetulan' => 'P0',
            'tanggal_masuk' => $tgl->toDateString(),
            'jenis_permohonan_id' => $jp,
            'nama_pemohon' => $nama,
            'nik_pemohon' => null,
            'no_hp_pemohon' => $telpon,
            'jumlah_bidang' => count($bidangs),
            'petugas_loket_id' => $this->user($r['PetugasLoket'], 'loket'),
            'status' => 'diterima',
            'keterangan' => 'Tiket aktif 2026 dari dashboard.',
            'tanggal_target_selesai' => null,
            'tanggal_selesai' => null,
            'periode' => null,
            'tahun' => $tahun,
            'sumber_data' => 'dashboard_aktif',
            'diarsipkan_pada' => null,
            'diarsipkan_oleh' => null,
        ]);
        $this->existingKode[$kode] = true;
        foreach ($bidangs as $i => $b) {
            BidangTanah::create([
                'tiket_id' => $tiket->id, 'nib' => null, 'no_sertifikat_lama' => $b['no'],
                'no_sertifikat_elektronik' => null, 'jenis_hak' => $b['jenis'], 'nama_pemegang_hak' => $nama,
                'luas_m2' => null, 'desa_kelurahan' => $b['kelurahan'], 'kecamatan' => null,
                'status_plotting' => 'belum', 'urutan' => $i + 1,
            ]);
        }
        $this->addRiwayat($tiket->id, 'Pendaftaran Loket', 'Loket (Tiket diterima)', null, 'Tiket aktif 2026 diimpor dari dashboard.', $tgl);
        $this->tahunCount[$tahun] = ($this->tahunCount[$tahun] ?? 0) + 1;
    }

    protected function addRiwayat(int $tiketId, string $from, string $to, ?int $userId, string $ket, Carbon $at): void
    {
        RiwayatStatus::create([
            'tiket_id' => $tiketId,
            'stage_dari' => $from,
            'stage_ke' => $to,
            'changed_by' => $userId ?? $this->adminId,
            'keterangan' => $ket,
            'created_at' => $at->toDateTimeString(),
            'updated_at' => $at->toDateTimeString(),
        ]);
    }

    private function summary(): void
    {
        $this->newLine();
        $this->info('=== RINGKASAN IMPOR ===');
        $this->line('Tiket per tahun (dari proses impor): ' . json_encode($this->tahunCount));
        $this->line('Akun staf baru dibuat: ' . $this->usersCreated);
        $this->newLine();
        $this->table(
            ['Metrik', 'Jumlah'],
            [
                ['Total tikets', Tiket::count()],
                ['Tiket arsip (diarsipkan)', Tiket::count() > 0 ? Tiket::arsip()->count() : 0],
                ['Tiket aktif', Tiket::aktif()->count()],
                ['Bidang tanah', BidangTanah::count()],
                ['Verifikasi berkas', VerifikasiBerkas::count()],
                ['Lembar kerja (warkah+validasi+alih media)', LembarKerjaWarkah::count() + LembarKerjaValidasi::count() + LembarKerjaAlihMedia::count()],
                ['Riwayat status', RiwayatStatus::count()],
                ['Users', User::count()],
            ]
        );
        $this->newLine();
        $this->info('Distribusi status:');
        foreach (Tiket::select('status', DB::raw('COUNT(*) as c'))->groupBy('status')->orderByDesc('c')->get() as $s) {
            $this->line(sprintf('  %-15s = %d', $s->status, $s->c));
        }
        $this->info('Distribusi tahun:');
        foreach (Tiket::select('tahun', DB::raw('COUNT(*) as c'))->whereNotNull('tahun')->groupBy('tahun')->orderBy('tahun')->get() as $s) {
            $this->line(sprintf('  %-5s = %d', $s->tahun, $s->c));
        }
        $this->info('Distribusi jenis (top 10):');
        foreach (Tiket::with('jenisPermohonan')->get()->groupBy('jenisPermohonan.kode')->map->count()->sortDesc()->take(10) as $k => $c) {
            $this->line(sprintf('  %-6s = %d', $k ?: '?', $c));
        }
    }
}