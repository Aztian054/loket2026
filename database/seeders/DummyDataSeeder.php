<?php

namespace Database\Seeders;

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
use Illuminate\Database\Seeder;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        $loket  = User::where('username', 'loket1')->first();
        $verif  = User::where('username', 'verifikator1')->first();
        $warkah = User::where('username', 'warkah1')->first();
        $valid  = User::where('username', 'validator1')->first();
        $alih   = User::where('username', 'alih1')->first();

        $jp01 = JenisPermohonan::where('kode', 'JP01')->first();
        $jp02 = JenisPermohonan::where('kode', 'JP02')->first();
        $jp13 = JenisPermohonan::where('kode', 'JP13')->first();
        $jp14 = JenisPermohonan::where('kode', 'JP14')->first();

        $today = Carbon::today();
        $dateCode = $today->format('dmy');
        $n = 1;

        // ════════════════════════════════════════════════════════════
        // STAGE 1 — LOKET (3 tiket): status = diterima
        // ════════════════════════════════════════════════════════════

        // --- Tiket 1: Jual Beli Perumahan ---
        $t = Tiket::create([
            'no_tiket'               => "K/{$n}/{$dateCode}/1",
            'status_pembetulan'      => 'P0',
            'tanggal_masuk'          => $today,
            'jenis_permohonan_id'    => $jp02->id,
            'nama_pemohon'           => 'Andi Prasetyo',
            'nik_pemohon'            => '1871031205900001',
            'no_hp_pemohon'          => '081234567890',
            'jumlah_bidang'          => 1,
            'petugas_loket_id'       => $loket->id,
            'status'                 => 'diterima',
            'tanggal_target_selesai' => $today->copy()->addDays(7),
            'keterangan'             => 'Peralihan hak jual beli rumah tinggal di Rajabasa.',
        ]);
        BidangTanah::create([
            'tiket_id'           => $t->id,
            'nib'                => '18.71.03.2001.00142',
            'no_sertifikat_lama' => 'SHM No. 5201',
            'jenis_hak'          => 'HM',
            'nama_pemegang_hak'  => 'Andi Prasetyo',
            'luas_m2'            => 180.00,
            'desa_kelurahan'     => 'Rajabasa',
            'kecamatan'          => 'Rajabasa',
            'urutan'             => 1,
        ]);
        RiwayatStatus::create([
            'tiket_id'   => $t->id,
            'stage_dari' => 'Pendaftaran Loket',
            'stage_ke'   => 'Loket (Diterima)',
            'changed_by' => $loket->id,
            'keterangan' => 'Berkas fisik diterima di Loket Pelayanan.',
        ]);

        // --- Tiket 2: Roya ---
        $n++;
        $t = Tiket::create([
            'no_tiket'               => "K/{$n}/{$dateCode}/1",
            'status_pembetulan'      => 'P0',
            'tanggal_masuk'          => $today,
            'jenis_permohonan_id'    => $jp13->id,
            'nama_pemohon'           => 'PT Sinar Bakti',
            'nik_pemohon'            => '1871020100010003',
            'no_hp_pemohon'          => '085678123456',
            'jumlah_bidang'          => 1,
            'petugas_loket_id'       => $loket->id,
            'status'                 => 'diterima',
            'tanggal_target_selesai' => $today->copy()->addDays(14),
            'keterangan'             => 'Roya atas jaminan bank yang telah lunas.',
        ]);
        BidangTanah::create([
            'tiket_id'           => $t->id,
            'nib'                => '18.71.02.1004.00087',
            'no_sertifikat_lama' => 'HGB No. 187',
            'jenis_hak'          => 'HGB',
            'nama_pemegang_hak'  => 'PT Sinar Bakti',
            'luas_m2'            => 520.00,
            'desa_kelurahan'     => 'Sungai Besar',
            'kecamatan'          => 'Kedaton',
            'urutan'             => 1,
        ]);
        RiwayatStatus::create([
            'tiket_id'   => $t->id,
            'stage_dari' => 'Pendaftaran Loket',
            'stage_ke'   => 'Loket (Diterima)',
            'changed_by' => $loket->id,
            'keterangan' => 'Berkas fisik diterima di Loket Pelayanan.',
        ]);

        // --- Tiket 3: Penatagunaan Tanah ---
        $n++;
        $t = Tiket::create([
            'no_tiket'               => "K/{$n}/{$dateCode}/1",
            'status_pembetulan'      => 'P0',
            'tanggal_masuk'          => $today,
            'jenis_permohonan_id'    => $jp01->id,
            'nama_pemohon'           => 'Ir. Bambang Suharto',
            'nik_pemohon'            => '1871040603780009',
            'no_hp_pemohon'          => '087890123456',
            'jumlah_bidang'          => 1,
            'petugas_loket_id'       => $loket->id,
            'status'                 => 'diterima',
            'tanggal_target_selesai' => $today->copy()->addDays(7),
            'keterangan'             => 'Pendaftaran tanah pertama kali.',
        ]);
        BidangTanah::create([
            'tiket_id'           => $t->id,
            'nib'                => '18.71.05.3002.00213',
            'no_sertifikat_lama' => '',
            'jenis_hak'          => 'HM',
            'nama_pemegang_hak'  => 'Ir. Bambang Suharto',
            'luas_m2'            => 320.00,
            'desa_kelurahan'     => 'Way Halim Permai',
            'kecamatan'          => 'Way Halim',
            'urutan'             => 1,
        ]);
        RiwayatStatus::create([
            'tiket_id'   => $t->id,
            'stage_dari' => 'Pendaftaran Loket',
            'stage_ke'   => 'Loket (Diterima)',
            'changed_by' => $loket->id,
            'keterangan' => 'Berkas fisik diterima di Loket Pelayanan.',
        ]);

        // ════════════════════════════════════════════════════════════
        // STAGE 2 — VERIFIKASI (1 tiket): status = verifikasi
        // ════════════════════════════════════════════════════════════

        $n++;
        $t = Tiket::create([
            'no_tiket'               => "K/{$n}/{$dateCode}/1",
            'status_pembetulan'      => 'P0',
            'tanggal_masuk'          => $today->copy()->subDays(1),
            'jenis_permohonan_id'    => $jp14->id,
            'nama_pemohon'           => 'Pemkot Bandar Lampung',
            'nik_pemohon'            => '1871010101010001',
            'no_hp_pemohon'          => '081399887766',
            'jumlah_bidang'          => 2,
            'petugas_loket_id'       => $loket->id,
            'status'                 => 'verifikasi',
            'status_verifikator'     => 'proses',
            'tanggal_target_selesai' => $today->copy()->addDays(14),
            'keterangan'             => 'Sertifikasi BMN tanah kantor pemerintah daerah.',
        ]);
        BidangTanah::create([
            'tiket_id'           => $t->id,
            'nib'                => '18.71.01.0001.00003',
            'no_sertifikat_lama' => 'HP No. 34/BMN',
            'jenis_hak'          => 'HP',
            'nama_pemegang_hak'  => 'Pemerintah Kota Bandar Lampung',
            'luas_m2'            => 1250.00,
            'desa_kelurahan'     => 'Enggal',
            'kecamatan'          => 'Enggal',
            'urutan'             => 1,
        ]);
        BidangTanah::create([
            'tiket_id'           => $t->id,
            'nib'                => '18.71.01.0001.00004',
            'no_sertifikat_lama' => 'HP No. 35/BMN',
            'jenis_hak'          => 'HP',
            'nama_pemegang_hak'  => 'Pemerintah Kota Bandar Lampung',
            'luas_m2'            => 680.00,
            'desa_kelurahan'     => 'Enggal',
            'kecamatan'          => 'Enggal',
            'urutan'             => 2,
        ]);
        VerifikasiBerkas::create([
            'tiket_id'         => $t->id,
            'verifikator_id'   => $verif->id,
            'iterasi'          => 1,
            'tanggal_diterima' => $today->copy()->subDays(1),
            'status'           => 'proses',
            'catatan'          => 'Sedang diperiksa kelengkapan berkas oleh Verifikator.',
        ]);
        RiwayatStatus::create([
            'tiket_id'   => $t->id,
            'stage_dari' => 'Loket (Diterima)',
            'stage_ke'   => 'Verifikator Berkas',
            'changed_by' => $loket->id,
            'keterangan' => 'Berkas diteruskan ke Verifikator untuk pemeriksaan.',
        ]);


        // ════════════════════════════════════════════════════════════
        // STAGE 3 — WARKAH (1 tiket): status = warkah
        // ════════════════════════════════════════════════════════════

        $n++;
        $t = Tiket::create([
            'no_tiket'               => "K/{$n}/{$dateCode}/1",
            'status_pembetulan'      => 'P0',
            'tanggal_masuk'          => $today->copy()->subDays(2),
            'jenis_permohonan_id'    => $jp02->id,
            'nama_pemohon'           => 'Siti Nurhaliza',
            'nik_pemohon'            => '1871024807850002',
            'no_hp_pemohon'          => '082134567890',
            'jumlah_bidang'          => 1,
            'petugas_loket_id'       => $loket->id,
            'status'                 => 'warkah',
            'status_verifikator'     => 'selesai',
            'status_warkah'          => 'proses',
            'tanggal_target_selesai' => $today->copy()->addDays(5),
            'keterangan'             => 'Jual beli tanah pekarangan di Kedaton.',
        ]);
        $b = BidangTanah::create([
            'tiket_id'           => $t->id,
            'nib'                => '18.71.03.2004.00456',
            'no_sertifikat_lama' => 'SHM No. 1092',
            'jenis_hak'          => 'HM',
            'nama_pemegang_hak'  => 'Siti Nurhaliza',
            'luas_m2'            => 285.00,
            'desa_kelurahan'     => 'Kedaton',
            'kecamatan'          => 'Kedaton',
            'urutan'             => 1,
        ]);
        VerifikasiBerkas::create([
            'tiket_id'         => $t->id,
            'verifikator_id'   => $verif->id,
            'iterasi'          => 1,
            'tanggal_diterima' => $today->copy()->subDays(1),
            'tanggal_selesai'  => $today,
            'status'           => 'lengkap',
            'catatan'          => 'Berkas lengkap, diteruskan ke Warkah.',
        ]);
        LembarKerjaWarkah::create([
            'tiket_id'          => $t->id,
            'bidang_id'         => $b->id,
            'petugas_id'        => $warkah->id,
            'tanggal_mulai'     => $today,
            'lokasi_fisik'      => 'Ruang Arsip Warkah Rak C-05',
            'kondisi'           => 'baik',
            'status_keberadaan' => 'ada',
            'status_scan'       => true,
            'catatan'           => 'Warkah ditemukan, buku tanah fisik dalam kondisi baik.',
        ]);
        RiwayatStatus::create([
            'tiket_id'   => $t->id,
            'stage_dari' => 'Verifikator Berkas',
            'stage_ke'   => 'Verifikator Berkas (Lengkap)',
            'changed_by' => $verif->id,
            'keterangan' => 'Berkas dinyatakan LENGKAP oleh Verifikator.',
        ]);
        RiwayatStatus::create([
            'tiket_id'   => $t->id,
            'stage_dari' => 'Verifikator Berkas (Lengkap)',
            'stage_ke'   => 'Lembar Kerja Warkah',
            'changed_by' => $verif->id,
            'keterangan' => 'Diteruskan ke Warkah untuk pencarian warkah fisik.',
        ]);


        // ════════════════════════════════════════════════════════════
        // STAGE 4 — VALIDATOR (2 tiket): status = validasi
        // ════════════════════════════════════════════════════════════

        // --- Tiket 7: Validator — Jual Beli ---
        $n++;
        $t7 = Tiket::create([
            'no_tiket'               => "K/{$n}/{$dateCode}/1",
            'status_pembetulan'      => 'P0',
            'tanggal_masuk'          => $today->copy()->subDays(3),
            'jenis_permohonan_id'    => $jp02->id,
            'nama_pemohon'           => 'Hendra Wijaya',
            'nik_pemohon'            => '1871015212800005',
            'no_hp_pemohon'          => '083456789012',
            'jumlah_bidang'          => 1,
            'petugas_loket_id'       => $loket->id,
            'status'                 => 'validasi',
            'status_verifikator'     => 'selesai',
            'status_warkah'          => 'selesai',
            'status_alih_media'      => 'proses',
            'tanggal_target_selesai' => $today->copy()->addDays(3),
            'keterangan'             => 'Validasi data pertanahan jual beli tanah di Sukarame.',
        ]);
        $b7 = BidangTanah::create([
            'tiket_id'           => $t7->id,
            'nib'                => '18.71.05.2002.00178',
            'no_sertifikat_lama' => 'SHM No. 3042',
            'jenis_hak'          => 'HM',
            'nama_pemegang_hak'  => 'Hendra Wijaya',
            'luas_m2'            => 310.00,
            'desa_kelurahan'     => 'Sukarame',
            'kecamatan'          => 'Sukarame',
            'urutan'             => 1,
        ]);
        VerifikasiBerkas::create([
            'tiket_id'         => $t7->id,
            'verifikator_id'   => $verif->id,
            'iterasi'          => 1,
            'tanggal_diterima' => $today->copy()->subDays(2),
            'tanggal_selesai'  => $today->copy()->subDays(1),
            'status'           => 'lengkap',
            'catatan'          => 'Berkas lengkap.',
        ]);
        LembarKerjaWarkah::create([
            'tiket_id'          => $t7->id,
            'bidang_id'         => $b7->id,
            'petugas_id'        => $warkah->id,
            'tanggal_mulai'     => $today->copy()->subDays(2),
            'tanggal_selesai'   => $today->copy()->subDays(1),
            'lokasi_fisik'      => 'Ruang Arsip Warkah Rak B-02',
            'kondisi'           => 'baik',
            'status_keberadaan' => 'ada',
            'status_scan'       => true,
            'catatan'           => 'Warkah ditemukan dan terverifikasi.',
        ]);
        LembarKerjaValidasi::create([
            'tiket_id'               => $t7->id,
            'bidang_id'              => $b7->id,
            'validator_id'           => $valid->id,
            'tanggal_mulai'          => $today,
            'kesesuaian_nama'        => 'sesuai',
            'kesesuaian_luas'        => 'sesuai',
            'status_pra_btel'        => 'proses',
            'status_pra_suel'        => 'belum',
            'status_validasi'        => 'proses',
            'status_validasi_bidang' => 'proses',
            'catatan'                => 'Sedang diverifikasi data vs KKP.',
        ]);
        RiwayatStatus::create([
            'tiket_id'   => $t7->id,
            'stage_dari' => 'Lembar Kerja Warkah',
            'stage_ke'   => 'Lembar Kerja Warkah (Selesai)',
            'changed_by' => $warkah->id,
            'keterangan' => 'Warkah fisik ditemukan dan dipindai.',
        ]);
        RiwayatStatus::create([
            'tiket_id'   => $t7->id,
            'stage_dari' => 'Lembar Kerja Warkah (Selesai)',
            'stage_ke'   => 'Validator',
            'changed_by' => $warkah->id,
            'keterangan' => 'Diteruskan ke Validator untuk validasi data pertanahan.',
        ]);


        // --- Tiket 8: Validator — Penatagunaan ---
        $n++;
        $t8 = Tiket::create([
            'no_tiket'               => "K/{$n}/{$dateCode}/1",
            'status_pembetulan'      => 'P0',
            'tanggal_masuk'          => $today->copy()->subDays(4),
            'jenis_permohonan_id'    => $jp01->id,
            'nama_pemohon'           => 'Yayasan Pendidikan Harapan Bangsa',
            'nik_pemohon'            => '1871000000000005',
            'no_hp_pemohon'          => '085612340099',
            'jumlah_bidang'          => 1,
            'petugas_loket_id'       => $loket->id,
            'status'                 => 'validasi',
            'status_verifikator'     => 'selesai',
            'status_warkah'          => 'selesai',
            'status_alih_media'      => 'proses',
            'tanggal_target_selesai' => $today->copy()->addDays(2),
            'keterangan'             => 'Validasi data tanah yayasan untuk sertifikasi pertama kali.',
        ]);
        $b8 = BidangTanah::create([
            'tiket_id'           => $t8->id,
            'nib'                => '18.71.04.1001.00034',
            'no_sertifikat_lama' => '',
            'jenis_hak'          => 'HM',
            'nama_pemegang_hak'  => 'Yayasan Pendidikan Harapan Bangsa',
            'luas_m2'            => 1800.00,
            'desa_kelurahan'     => 'Bumi Waras',
            'kecamatan'          => 'Bumi Waras',
            'urutan'             => 1,
        ]);
        VerifikasiBerkas::create([
            'tiket_id'         => $t8->id,
            'verifikator_id'   => $verif->id,
            'iterasi'          => 1,
            'tanggal_diterima' => $today->copy()->subDays(3),
            'tanggal_selesai'  => $today->copy()->subDays(2),
            'status'           => 'lengkap',
            'catatan'          => 'Berkas lengkap.',
        ]);
        LembarKerjaWarkah::create([
            'tiket_id'          => $t8->id,
            'bidang_id'         => $b8->id,
            'petugas_id'        => $warkah->id,
            'tanggal_mulai'     => $today->copy()->subDays(2),
            'tanggal_selesai'   => $today->copy()->subDays(1),
            'lokasi_fisik'      => 'Ruang Arsip Warkah Rak D-01',
            'kondisi'           => 'baik',
            'status_keberadaan' => 'ada',
            'status_scan'       => true,
            'catatan'           => 'Warkah ditemukan.',
        ]);
        LembarKerjaValidasi::create([
            'tiket_id'               => $t8->id,
            'bidang_id'              => $b8->id,
            'validator_id'           => $valid->id,
            'tanggal_mulai'          => $today->copy()->subDays(1),
            'kesesuaian_nama'        => 'sesuai',
            'kesesuaian_luas'        => 'tidak_sesuai',
            'status_pra_btel'        => 'belum',
            'status_pra_suel'        => 'proses',
            'status_validasi'        => 'proses',
            'status_validasi_bidang' => 'proses',
            'catatan'                => 'Luas tanah perlu diverifikasi ulang dengan ukur.',
        ]);
        RiwayatStatus::create([
            'tiket_id'   => $t8->id,
            'stage_dari' => 'Lembar Kerja Warkah (Selesai)',
            'stage_ke'   => 'Validator',
            'changed_by' => $warkah->id,
            'keterangan' => 'Diteruskan ke Validator untuk validasi data.',
        ]);


        // ════════════════════════════════════════════════════════════
        // STAGE 5 — ALIH MEDIA (2 tiket): status = alih_media
        // ════════════════════════════════════════════════════════════

        // --- Tiket 9: Alih Media — Jual Beli ---
        $n++;
        $t9 = Tiket::create([
            'no_tiket'               => "K/{$n}/{$dateCode}/1",
            'status_pembetulan'      => 'P0',
            'tanggal_masuk'          => $today->copy()->subDays(5),
            'jenis_permohonan_id'    => $jp02->id,
            'nama_pemohon'           => 'Dewi Kartika Sari',
            'nik_pemohon'            => '1871026305920007',
            'no_hp_pemohon'          => '087812345678',
            'jumlah_bidang'          => 1,
            'petugas_loket_id'       => $loket->id,
            'status'                 => 'alih_media',
            'status_verifikator'     => 'selesai',
            'status_warkah'          => 'selesai',
            'status_alih_media'      => 'proses',
            'tanggal_target_selesai' => $today->copy()->addDays(1),
            'keterangan'             => 'Alih media sertifikat jual beli tanah di Panjang.',
        ]);
        $b9 = BidangTanah::create([
            'tiket_id'           => $t9->id,
            'nib'                => '18.71.06.2003.00098',
            'no_sertifikat_lama' => 'SHM No. 2215',
            'jenis_hak'          => 'HM',
            'nama_pemegang_hak'  => 'Dewi Kartika Sari',
            'luas_m2'            => 195.00,
            'desa_kelurahan'     => 'Panjang',
            'kecamatan'          => 'Panjang',
            'urutan'             => 1,
        ]);
        VerifikasiBerkas::create([
            'tiket_id'         => $t9->id,
            'verifikator_id'   => $verif->id,
            'iterasi'          => 1,
            'tanggal_diterima' => $today->copy()->subDays(4),
            'tanggal_selesai'  => $today->copy()->subDays(3),
            'status'           => 'lengkap',
            'catatan'          => 'Berkas lengkap.',
        ]);
        LembarKerjaWarkah::create([
            'tiket_id'          => $t9->id,
            'bidang_id'         => $b9->id,
            'petugas_id'        => $warkah->id,
            'tanggal_mulai'     => $today->copy()->subDays(3),
            'tanggal_selesai'   => $today->copy()->subDays(2),
            'lokasi_fisik'      => 'Ruang Arsip Warkah Rak A-03',
            'kondisi'           => 'baik',
            'status_keberadaan' => 'ada',
            'status_scan'       => true,
            'catatan'           => 'Warkah ditemukan.',
        ]);
        LembarKerjaValidasi::create([
            'tiket_id'               => $t9->id,
            'bidang_id'              => $b9->id,
            'validator_id'           => $valid->id,
            'tanggal_mulai'          => $today->copy()->subDays(2),
            'tanggal_selesai'        => $today->copy()->subDays(1),
            'kesesuaian_nama'        => 'sesuai',
            'kesesuaian_luas'        => 'sesuai',
            'status_pra_btel'        => 'selesai',
            'status_pra_suel'        => 'selesai',
            'status_validasi'        => 'lulus',
            'status_validasi_bidang' => 'lulus',
            'catatan'                => 'Data valid, diteruskan ke Alih Media.',
            'diteruskan_alih_media'  => true,
        ]);
        LembarKerjaAlihMedia::create([
            'tiket_id'               => $t9->id,
            'bidang_id'              => $b9->id,
            'petugas_id'             => $alih->id,
            'tanggal_mulai'          => $today,
            'status_scan_buku_tanah' => 'sudah',
            'status_scan_surat_ukur' => 'belum',
            'status_scan_warkah'     => 'belum',
            'status_upload_kkp'      => 'belum',
            'status_ttd_elektronik'  => 'belum',
            'catatan'                => 'Scan buku tanah selesai, scan surat ukur masih diproses.',
        ]);
        RiwayatStatus::create([
            'tiket_id'   => $t9->id,
            'stage_dari' => 'Validator (Lulus)',
            'stage_ke'   => 'Lembar Kerja Alih Media',
            'changed_by' => $valid->id,
            'keterangan' => 'Validasi lulus, diteruskan ke Alih Media untuk digitalisasi.',
        ]);


        // --- Tiket 10: Alih Media — Penatagunaan ---
        $n++;
        $t10 = Tiket::create([
            'no_tiket'               => "K/{$n}/{$dateCode}/1",
            'status_pembetulan'      => 'P0',
            'tanggal_masuk'          => $today->copy()->subDays(6),
            'jenis_permohonan_id'    => $jp01->id,
            'nama_pemohon'           => 'PT Mega Konstruksi',
            'nik_pemohon'            => '1871000000000010',
            'no_hp_pemohon'          => '089912340088',
            'jumlah_bidang'          => 1,
            'petugas_loket_id'       => $loket->id,
            'status'                 => 'alih_media',
            'status_verifikator'     => 'selesai',
            'status_warkah'          => 'selesai',
            'status_alih_media'      => 'proses',
            'tanggal_target_selesai' => $today,
            'keterangan'             => 'Alih media sertifikat BMN jalan nasional.',
        ]);
        $b10 = BidangTanah::create([
            'tiket_id'                 => $t10->id,
            'nib'                      => '18.71.05.4001.00067',
            'no_sertifikat_lama'       => 'Alas Hak No. 12/JL/2024',
            'no_sertifikat_elektronik' => '',
            'jenis_hak'                => 'HP',
            'nama_pemegang_hak'        => 'Pemerintah Republik Indonesia',
            'luas_m2'                  => 3200.00,
            'desa_kelurahan'           => 'Natar',
            'kecamatan'                => 'Natar',
            'urutan'                   => 1,
        ]);
        VerifikasiBerkas::create([
            'tiket_id'         => $t10->id,
            'verifikator_id'   => $verif->id,
            'iterasi'          => 1,
            'tanggal_diterima' => $today->copy()->subDays(5),
            'tanggal_selesai'  => $today->copy()->subDays(4),
            'status'           => 'lengkap',
            'catatan'          => 'Berkas lengkap.',
        ]);
        LembarKerjaWarkah::create([
            'tiket_id'          => $t10->id,
            'bidang_id'         => $b10->id,
            'petugas_id'        => $warkah->id,
            'tanggal_mulai'     => $today->copy()->subDays(4),
            'tanggal_selesai'   => $today->copy()->subDays(3),
            'lokasi_fisik'      => 'Ruang Arsip Warkah Rak D-01',
            'kondisi'           => 'baik',
            'status_keberadaan' => 'ada',
            'status_scan'       => true,
            'catatan'           => 'Warkah fisik ditemukan.',
        ]);
        LembarKerjaValidasi::create([
            'tiket_id'               => $t10->id,
            'bidang_id'              => $b10->id,
            'validator_id'           => $valid->id,
            'tanggal_mulai'          => $today->copy()->subDays(3),
            'tanggal_selesai'        => $today->copy()->subDays(2),
            'kesesuaian_nama'        => 'sesuai',
            'kesesuaian_luas'        => 'sesuai',
            'status_pra_btel'        => 'selesai',
            'status_pra_suel'        => 'selesai',
            'status_validasi'        => 'lulus',
            'status_validasi_bidang' => 'lulus',
            'catatan'                => 'Semua data valid.',
            'diteruskan_alih_media'  => true,
        ]);
        LembarKerjaAlihMedia::create([
            'tiket_id'               => $t10->id,
            'bidang_id'              => $b10->id,
            'petugas_id'             => $alih->id,
            'tanggal_mulai'          => $today->copy()->subDays(2),
            'status_scan_buku_tanah' => 'sudah',
            'status_scan_surat_ukur' => 'sudah',
            'status_scan_warkah'     => 'belum',
            'status_upload_kkp'      => 'belum',
            'status_ttd_elektronik'  => 'belum',
            'catatan'                => 'Buku tanah & surat ukur ter-scan, scan warkah berikutnya.',
        ]);
        RiwayatStatus::create([
            'tiket_id'   => $t10->id,
            'stage_dari' => 'Validator (Lulus)',
            'stage_ke'   => 'Lembar Kerja Alih Media',
            'changed_by' => $valid->id,
            'keterangan' => 'Validasi lulus, diteruskan ke Alih Media.',
        ]);


        // ════════════════════════════════════════════════════════════
        // SELESAI (2 tiket): status = selesai
        // ════════════════════════════════════════════════════════════

        // --- Tiket 11: Selesai — Jual Beli ---
        $n++;
        $t11 = Tiket::create([
            'no_tiket'               => "K/{$n}/{$dateCode}/1",
            'status_pembetulan'      => 'P0',
            'tanggal_masuk'          => $today->copy()->subDays(10),
            'jenis_permohonan_id'    => $jp02->id,
            'nama_pemohon'           => 'Rina Marlina',
            'nik_pemohon'            => '1871025105880004',
            'no_hp_pemohon'          => '081987654321',
            'jumlah_bidang'          => 1,
            'petugas_loket_id'       => $loket->id,
            'status'                 => 'selesai',
            'status_verifikator'     => 'selesai',
            'status_warkah'          => 'selesai',
            'status_alih_media'      => 'selesai',
            'tanggal_target_selesai' => $today->copy()->addDays(5),
            'tanggal_selesai'        => $today,
            'keterangan'             => 'Sertifikat Elektronik terbit, selesai.',
        ]);
        $b11 = BidangTanah::create([
            'tiket_id'                 => $t11->id,
            'nib'                      => '18.71.03.2001.00289',
            'no_sertifikat_lama'       => 'SHM No. 7782',
            'no_sertifikat_elektronik' => 'AB-009901',
            'jenis_hak'                => 'HM',
            'nama_pemegang_hak'        => 'Rina Marlina',
            'luas_m2'                  => 220.00,
            'desa_kelurahan'           => 'Rajabasa',
            'kecamatan'                => 'Rajabasa',
            'urutan'                   => 1,
        ]);
        VerifikasiBerkas::create([
            'tiket_id'         => $t11->id,
            'verifikator_id'   => $verif->id,
            'iterasi'          => 1,
            'tanggal_diterima' => $today->copy()->subDays(9),
            'tanggal_selesai'  => $today->copy()->subDays(8),
            'status'           => 'lengkap',
            'catatan'          => 'Berkas lengkap.',
        ]);
        LembarKerjaWarkah::create([
            'tiket_id'          => $t11->id,
            'bidang_id'         => $b11->id,
            'petugas_id'        => $warkah->id,
            'tanggal_mulai'     => $today->copy()->subDays(8),
            'tanggal_selesai'   => $today->copy()->subDays(7),
            'lokasi_fisik'      => 'Ruang Arsip Warkah Rak B-02',
            'kondisi'           => 'baik',
            'status_keberadaan' => 'ada',
            'status_scan'       => true,
            'catatan'           => 'Warkah ditemukan.',
        ]);
        LembarKerjaValidasi::create([
            'tiket_id'               => $t11->id,
            'bidang_id'              => $b11->id,
            'validator_id'           => $valid->id,
            'tanggal_mulai'          => $today->copy()->subDays(7),
            'tanggal_selesai'        => $today->copy()->subDays(6),
            'kesesuaian_nama'        => 'sesuai',
            'kesesuaian_luas'        => 'sesuai',
            'status_pra_btel'        => 'selesai',
            'status_pra_suel'        => 'selesai',
            'status_validasi'        => 'lulus',
            'status_validasi_bidang' => 'lulus',
            'catatan'                => 'Data valid.',
            'diteruskan_alih_media'  => true,
        ]);
        LembarKerjaAlihMedia::create([
            'tiket_id'                     => $t11->id,
            'bidang_id'                    => $b11->id,
            'petugas_id'                   => $alih->id,
            'tanggal_mulai'                => $today->copy()->subDays(6),
            'tanggal_selesai'              => $today->copy()->subDays(1),
            'status_scan_buku_tanah'       => 'sudah',
            'status_scan_surat_ukur'       => 'sudah',
            'status_scan_warkah'           => 'sudah',
            'status_upload_kkp'            => 'sudah',
            'status_ttd_elektronik'        => 'sudah',
            'tanggal_terbit_sertifikat_el' => $today->copy()->subDays(1),
            'catatan'                      => 'Sertifikat Elektronik AB-009901 resmi diterbitkan.',
        ]);
        RiwayatStatus::create([
            'tiket_id'   => $t11->id,
            'stage_dari' => 'Lembar Kerja Alih Media (Selesai)',
            'stage_ke'   => 'SELESAI (Sertifikat Elektronik Terbit)',
            'changed_by' => $alih->id,
            'keterangan' => 'Sertifikat Elektronik AB-009901 terbit, siap diambil.',
        ]);


        // --- Tiket 12: Selesai — Roya ---
        $n++;
        $t12 = Tiket::create([
            'no_tiket'               => "K/{$n}/{$dateCode}/1",
            'status_pembetulan'      => 'P0',
            'tanggal_masuk'          => $today->copy()->subDays(12),
            'jenis_permohonan_id'    => $jp13->id,
            'nama_pemohon'           => 'PT Bank Nasional Indonesia',
            'nik_pemohon'            => '1871000000000020',
            'no_hp_pemohon'          => '081200112233',
            'jumlah_bidang'          => 1,
            'petugas_loket_id'       => $loket->id,
            'status'                 => 'selesai',
            'status_verifikator'     => 'selesai',
            'status_warkah'          => 'selesai',
            'status_alih_media'      => 'selesai',
            'tanggal_target_selesai' => $today->copy()->addDays(2),
            'tanggal_selesai'        => $today->copy()->subDays(1),
            'keterangan'             => 'Roya jaminan bank selesai, sertifikat elektronik terbit.',
        ]);
        $b12 = BidangTanah::create([
            'tiket_id'                 => $t12->id,
            'nib'                      => '18.71.02.3001.00112',
            'no_sertifikat_lama'       => 'HGB No. 502',
            'no_sertifikat_elektronik' => 'AB-009902',
            'jenis_hak'                => 'HGB',
            'nama_pemegang_hak'        => 'PT Bank Nasional Indonesia',
            'luas_m2'                  => 750.00,
            'desa_kelurahan'           => 'Gedong Air',
            'kecamatan'                => 'Sukabumi',
            'urutan'                   => 1,
        ]);
        VerifikasiBerkas::create([
            'tiket_id'         => $t12->id,
            'verifikator_id'   => $verif->id,
            'iterasi'          => 1,
            'tanggal_diterima' => $today->copy()->subDays(11),
            'tanggal_selesai'  => $today->copy()->subDays(10),
            'status'           => 'lengkap',
            'catatan'          => 'Berkas lengkap.',
        ]);
        LembarKerjaWarkah::create([
            'tiket_id'          => $t12->id,
            'bidang_id'         => $b12->id,
            'petugas_id'        => $warkah->id,
            'tanggal_mulai'     => $today->copy()->subDays(10),
            'tanggal_selesai'   => $today->copy()->subDays(9),
            'lokasi_fisik'      => 'Ruang Arsip Warkah Rak A-03',
            'kondisi'           => 'baik',
            'status_keberadaan' => 'ada',
            'status_scan'       => true,
            'catatan'           => 'Warkah ditemukan.',
        ]);
        LembarKerjaValidasi::create([
            'tiket_id'               => $t12->id,
            'bidang_id'              => $b12->id,
            'validator_id'           => $valid->id,
            'tanggal_mulai'          => $today->copy()->subDays(9),
            'tanggal_selesai'        => $today->copy()->subDays(8),
            'kesesuaian_nama'        => 'sesuai',
            'kesesuaian_luas'        => 'sesuai',
            'status_pra_btel'        => 'selesai',
            'status_pra_suel'        => 'selesai',
            'status_validasi'        => 'lulus',
            'status_validasi_bidang' => 'lulus',
            'catatan'                => 'Data valid.',
            'diteruskan_alih_media'  => true,
        ]);
        LembarKerjaAlihMedia::create([
            'tiket_id'                     => $t12->id,
            'bidang_id'                    => $b12->id,
            'petugas_id'                   => $alih->id,
            'tanggal_mulai'                => $today->copy()->subDays(8),
            'tanggal_selesai'              => $today->copy()->subDays(2),
            'status_scan_buku_tanah'       => 'sudah',
            'status_scan_surat_ukur'       => 'sudah',
            'status_scan_warkah'           => 'sudah',
            'status_upload_kkp'            => 'sudah',
            'status_ttd_elektronik'        => 'sudah',
            'tanggal_terbit_sertifikat_el' => $today->copy()->subDays(2),
            'catatan'                      => 'Sertifikat Elektronik AB-009902 resmi diterbitkan.',
        ]);
        RiwayatStatus::create([
            'tiket_id'   => $t12->id,
            'stage_dari' => 'Lembar Kerja Alih Media (Selesai)',
            'stage_ke'   => 'SELESAI (Sertifikat Elektronik Terbit)',
            'changed_by' => $alih->id,
            'keterangan' => 'Sertifikat Elektronik AB-009902 terbit.',
        ]);

        $this->command?->info("✅ DummyDataSeeder selesai: 11 tiket dummy berhasil dibuat.");
    }
}

