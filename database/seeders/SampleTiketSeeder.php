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

class SampleTiketSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('username', 'admin')->first();
        $loket = User::where('username', 'loket1')->first();
        $verif = User::where('username', 'verifikator1')->first();
        $warkah = User::where('username', 'warkah1')->first();
        $valid = User::where('username', 'validator1')->first();
        $alih = User::where('username', 'alih1')->first();

        $jp01 = JenisPermohonan::where('kode', 'JP01')->first();
        $jp02 = JenisPermohonan::where('kode', 'JP02')->first();
        $jp13 = JenisPermohonan::where('kode', 'JP13')->first();
        $jp14 = JenisPermohonan::where('kode', 'JP14')->first();

        $today = Carbon::today();
        $dateCode = $today->format('dmy');

        // Sample 1: Baru di Loket (Diterima)
        $t1 = Tiket::create([
            'no_tiket' => "K/1/{$dateCode}/1",
            'status_pembetulan' => 'P0',
            'tanggal_masuk' => $today,
            'jenis_permohonan_id' => $jp02->id,
            'nama_pemohon' => 'H. Hendra Setiawan',
            'nik_pemohon' => '1871021405820003',
            'no_hp_pemohon' => '081278998811',
            'jumlah_bidang' => 1,
            'petugas_loket_id' => $loket->id,
            'status' => 'diterima',
            'tanggal_target_selesai' => $today->copy()->addDays(7),
            'keterangan' => 'Permohonan peralihan hak jual beli perumahan Way Halim.',
        ]);
        BidangTanah::create([
            'tiket_id' => $t1->id,
            'nib' => '08.01.04.05.00192',
            'no_sertifikat_lama' => 'SHM No. 4102',
            'jenis_hak' => 'HM',
            'nama_pemegang_hak' => 'H. Hendra Setiawan',
            'luas_m2' => 240.00,
            'desa_kelurahan' => 'Way Halim Permai',
            'kecamatan' => 'Way Halim',
            'urutan' => 1,
        ]);
        RiwayatStatus::create([
            'tiket_id' => $t1->id,
            'stage_dari' => 'Pendaftaran Loket',
            'stage_ke' => 'Loket (Draf)',
            'changed_by' => $loket->id,
            'keterangan' => 'Berkas fisik diterima di Loket Pelayanan.',
        ]);

        // Sample 2: Di Verifikator
        $t2 = Tiket::create([
            'no_tiket' => "K/2/{$dateCode}/1",
            'status_pembetulan' => 'P0',
            'tanggal_masuk' => $today,
            'jenis_permohonan_id' => $jp14->id,
            'nama_pemohon' => 'KPKNL Bandar Lampung (Satker BMN)',
            'nik_pemohon' => '1871010101010001',
            'no_hp_pemohon' => '081399887766',
            'jumlah_bidang' => 2,
            'petugas_loket_id' => $loket->id,
            'status' => 'verifikasi',
            'tanggal_target_selesai' => $today->copy()->addDays(30),
            'keterangan' => 'Sertifikasi BMN Tanah Bangunan Kantor Pemerintah.',
        ]);
        BidangTanah::create([
            'tiket_id' => $t2->id,
            'nib' => '08.01.01.01.00841',
            'no_sertifikat_lama' => 'HP No. 12',
            'jenis_hak' => 'HP',
            'nama_pemegang_hak' => 'Pemerintah Republik Indonesia c.q. Kementerian PUPR',
            'luas_m2' => 1540.00,
            'desa_kelurahan' => 'Gunung Sari',
            'kecamatan' => 'Enggal',
            'urutan' => 1,
        ]);
        BidangTanah::create([
            'tiket_id' => $t2->id,
            'nib' => '08.01.01.01.00842',
            'no_sertifikat_lama' => 'HP No. 13',
            'jenis_hak' => 'HP',
            'nama_pemegang_hak' => 'Pemerintah Republik Indonesia c.q. Kementerian PUPR',
            'luas_m2' => 860.00,
            'desa_kelurahan' => 'Gunung Sari',
            'kecamatan' => 'Enggal',
            'urutan' => 2,
        ]);
        VerifikasiBerkas::create([
            'tiket_id' => $t2->id,
            'verifikator_id' => $verif->id,
            'iterasi' => 1,
            'tanggal_diterima' => $today,
            'status' => 'proses',
            'catatan' => 'Sedang dalam proses pencocokan SK PSP dan bukti perolehan fisik.',
        ]);
        RiwayatStatus::create([
            'tiket_id' => $t2->id,
            'stage_dari' => 'Loket',
            'stage_ke' => 'Verifikator Berkas',
            'changed_by' => $loket->id,
            'keterangan' => 'Berkas diteruskan ke Verifikator.',
        ]);

        // Sample 3: Di Warkah
        $t3 = Tiket::create([
            'no_tiket' => "K/3/{$dateCode}/1",
            'status_pembetulan' => 'P0',
            'tanggal_masuk' => $today->copy()->subDays(2),
            'jenis_permohonan_id' => $jp13->id,
            'nama_pemohon' => 'Dra. Hj. Siti Aminah',
            'nik_pemohon' => '1871035509750002',
            'no_hp_pemohon' => '085277889900',
            'jumlah_bidang' => 1,
            'petugas_loket_id' => $loket->id,
            'status' => 'warkah',
            'tanggal_target_selesai' => $today->copy()->addDays(12),
            'keterangan' => 'Permohonan alih media sertifikat analog ke elektronik.',
        ]);
        $b3 = BidangTanah::create([
            'tiket_id' => $t3->id,
            'nib' => '08.01.03.02.00412',
            'no_sertifikat_lama' => 'SHM No. 1092',
            'jenis_hak' => 'HM',
            'nama_pemegang_hak' => 'Dra. Hj. Siti Aminah',
            'luas_m2' => 385.00,
            'desa_kelurahan' => 'Kedaton',
            'kecamatan' => 'Kedaton',
            'urutan' => 1,
        ]);
        LembarKerjaWarkah::create([
            'tiket_id' => $t3->id,
            'bidang_id' => $b3->id,
            'petugas_id' => $warkah->id,
            'tanggal_mulai' => $today,
            'lokasi_fisik' => 'Ruang Arsip Warkah Rak C-05',
            'kondisi' => 'baik',
            'status_keberadaan' => 'ada',
            'status_scan' => true,
            'catatan' => 'Warkah lengkap dan buku tanah fisik telah ditemukan.',
        ]);
        RiwayatStatus::create([
            'tiket_id' => $t3->id,
            'stage_dari' => 'Verifikator Berkas',
            'stage_ke' => 'Lembar Kerja Warkah',
            'changed_by' => $verif->id,
            'keterangan' => 'Berkas dinyatakan LENGKAP oleh Verifikator.',
        ]);

        // Sample 4: Selesai (Sertifikat Elektronik Terbit)
        $t4 = Tiket::create([
            'no_tiket' => "K/4/{$dateCode}/1",
            'status_pembetulan' => 'P0',
            'tanggal_masuk' => $today->copy()->subDays(5),
            'jenis_permohonan_id' => $jp01->id,
            'nama_pemohon' => 'Balai Pelaksanaan Jalan Nasional (BPJN) Lampung',
            'nik_pemohon' => '1871000000000001',
            'no_hp_pemohon' => '081198765432',
            'jumlah_bidang' => 1,
            'petugas_loket_id' => $loket->id,
            'status' => 'selesai',
            'tanggal_target_selesai' => $today->copy()->addDays(9),
            'tanggal_selesai' => $today,
            'keterangan' => 'Pendaftaran tanah pertama kali BMN Jalan Nasional By Pass.',
        ]);
        $b4 = BidangTanah::create([
            'tiket_id' => $t4->id,
            'nib' => '08.01.05.02.00012',
            'no_sertifikat_lama' => 'Alas Hak No. 45/BMN/2024',
            'no_sertifikat_elektronik' => 'AB-009841',
            'jenis_hak' => 'HP',
            'nama_pemegang_hak' => 'Pemerintah Republik Indonesia c.q. Kementerian PUPR',
            'luas_m2' => 8420.00,
            'desa_kelurahan' => 'Sukarame',
            'kecamatan' => 'Sukarame',
            'urutan' => 1,
        ]);
        LembarKerjaAlihMedia::create([
            'tiket_id' => $t4->id,
            'bidang_id' => $b4->id,
            'petugas_id' => $alih->id,
            'status_scan_buku_tanah' => 'sudah',
            'status_scan_surat_ukur' => 'sudah',
            'status_scan_warkah' => 'sudah',
            'status_upload_kkp' => 'sudah',
            'status_ttd_elektronik' => 'sudah',
            'tanggal_terbit_sertifikat_el' => $today,
            'catatan' => 'Sertifikat Elektronik AB-009841 resmi diterbitkan.',
            'tanggal_mulai' => $today->copy()->subDays(1),
            'tanggal_selesai' => $today,
        ]);
        RiwayatStatus::create([
            'tiket_id' => $t4->id,
            'stage_dari' => 'Lembar Kerja Alih Media',
            'stage_ke' => 'SELESAI (Sertifikat Elektronik Terbit)',
            'changed_by' => $alih->id,
            'keterangan' => 'Digitalisasi dan TTD elektronik selesai. Sertifikat elektronik siap diambil.',
        ]);
    }
}
