<?php

namespace Database\Seeders;

use App\Models\JenisPermohonan;
use App\Models\PersyaratanDokumen;
use App\Models\SaranKoreksi;
use Illuminate\Database\Seeder;

class JenisPermohonanSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'kode' => 'JP01',
                'nama' => 'Pendaftaran Tanah Pertama Kali (PTSK/BMN)',
                'kategori' => 'bmn',
                'batas_hari_sla' => 14,
                'persyaratan' => [
                    'Formulir Permohonan & Surat Kuasa (bila dikuasakan)',
                    'Fotokopi Identitas Diri (KTP, KK)',
                    'Bukti Kepemilikan Tanah / Alas Hak Asli',
                    'SPPT PBB Tahun Berjalan',
                    'Surat Pernyataan Penguasaan Fisik Bidang Tanah (Sporadik)',
                    'Surat Keterangan Riwayat Tanah dari Kelurahan',
                ],
            ],
            [
                'kode' => 'JP02',
                'nama' => 'Peralihan Hak — Jual Beli',
                'kategori' => 'umum',
                'batas_hari_sla' => 7,
                'persyaratan' => [
                    'Formulir Permohonan Ditandatangani Pemohon/Kuasa',
                    'Sertifikat Asli',
                    'Akta Jual Beli (AJB) dari PPAT',
                    'Fotokopi KTP & KK Penjual dan Pembeli',
                    'Bukti Bayar BPHTB & PPh Final',
                    'SPPT PBB Tahun Berjalan & Bukti Lunas',
                ],
            ],
            [
                'kode' => 'JP03',
                'nama' => 'Peralihan Hak — Waris',
                'kategori' => 'umum',
                'batas_hari_sla' => 7,
                'persyaratan' => [
                    'Formulir Permohonan Waris',
                    'Sertifikat Asli',
                    'Surat Keterangan Kematian Pewaris',
                    'Surat Keterangan Waris / Akta Waris Notaris',
                    'Fotokopi KTP & KK Seluruh Ahli Waris',
                    'Bukti Lunas BPHTB Waris & PBB',
                ],
            ],
            [
                'kode' => 'JP04',
                'nama' => 'Peralihan Hak — Hibah',
                'kategori' => 'umum',
                'batas_hari_sla' => 7,
                'persyaratan' => [
                    'Formulir Permohonan Hibah',
                    'Sertifikat Asli',
                    'Akta Hibah dari PPAT',
                    'Fotokopi KTP & KK Pemberi dan Penerima Hibah',
                    'Bukti Bayar BPHTB Hibah & PPh',
                    'SPPT PBB Tahun Berjalan',
                ],
            ],
            [
                'kode' => 'JP05',
                'nama' => 'Roya (Penghapusan Hak Tanggungan)',
                'kategori' => 'umum',
                'batas_hari_sla' => 5,
                'persyaratan' => [
                    'Formulir Permohonan Roya',
                    'Sertifikat Tanah Asli',
                    'Sertifikat Hak Tanggungan (SHT) Asli',
                    'Surat Pelunasan / Keterangan Roya dari Kreditur/Bank',
                    'Fotokopi KTP Pemohon',
                ],
            ],
            [
                'kode' => 'JP06',
                'nama' => 'Pemecahan Sertifikat',
                'kategori' => 'umum',
                'batas_hari_sla' => 14,
                'persyaratan' => [
                    'Formulir Permohonan Pemecahan',
                    'Sertifikat Asli',
                    'Site Plan / Gambar Rencana Pemecahan',
                    'Fotokopi KTP & KK Pemohon',
                    'Surat Persetujuan Batas Bidang Tetangga',
                    'SPPT PBB Terakhir',
                ],
            ],
            [
                'kode' => 'JP07',
                'nama' => 'Penggabungan Sertifikat',
                'kategori' => 'umum',
                'batas_hari_sla' => 14,
                'persyaratan' => [
                    'Formulir Permohonan Penggabungan',
                    'Seluruh Sertifikat Asli yang akan Digabung',
                    'Fotokopi KTP Pemegang Hak (Nama Harus Sama)',
                    'Surat Pernyataan Penggabungan Bidang',
                    'SPPT PBB Terakhir',
                ],
            ],
            [
                'kode' => 'JP08',
                'nama' => 'Pemisahan Sertifikat',
                'kategori' => 'umum',
                'batas_hari_sla' => 14,
                'persyaratan' => [
                    'Formulir Permohonan Pemisahan',
                    'Sertifikat Asli Induk',
                    'Peta / Gambar Rencana Pemisahan',
                    'Fotokopi KTP Pemohon',
                ],
            ],
            [
                'kode' => 'JP09',
                'nama' => 'Perubahan Hak',
                'kategori' => 'umum',
                'batas_hari_sla' => 7,
                'persyaratan' => [
                    'Formulir Permohonan Perubahan Hak',
                    'Sertifikat Asli (HGB/HP)',
                    'Fotokopi IMB / PBG / Surat Keterangan Rumah Tinggal',
                    'Fotokopi KTP Pemohon',
                    'SPPT PBB Tahun Berjalan',
                ],
            ],
            [
                'kode' => 'JP10',
                'nama' => 'Pengecekan Sertifikat',
                'kategori' => 'umum',
                'batas_hari_sla' => 1,
                'persyaratan' => [
                    'Formulir Permohonan Pengecekan / Surat Pengantar PPAT',
                    'Sertifikat Asli',
                    'Fotokopi KTP Pemohon / PPAT',
                ],
            ],
            [
                'kode' => 'JP11',
                'nama' => 'SKPT (Surat Keterangan Pendaftaran Tanah)',
                'kategori' => 'umum',
                'batas_hari_sla' => 5,
                'persyaratan' => [
                    'Formulir Permohonan SKPT',
                    'Fotokopi Sertifikat / Alas Hak',
                    'Fotokopi KTP Pemohon',
                    'Surat Permohonan Resmi / Penetapan Instansi Berwenang',
                ],
            ],
            [
                'kode' => 'JP12',
                'nama' => 'Hak Tanggungan Elektronik (HT-el)',
                'kategori' => 'umum',
                'batas_hari_sla' => 3,
                'persyaratan' => [
                    'Sertifikat Asli',
                    'Akta Pemberian Hak Tanggungan (APHT) dari PPAT',
                    'Surat Kuasa Membebankan Hak Tanggungan (SKMHT) jika ada',
                    'KTP Pemberi dan Penerima HT',
                ],
            ],
            [
                'kode' => 'JP13',
                'nama' => 'Alih Media (Sertifikat Analog ke Elektronik)',
                'kategori' => 'alih_media',
                'batas_hari_sla' => 14,
                'persyaratan' => [
                    'Formulir Permohonan Alih Media',
                    'Sertifikat Analog Asli',
                    'Fotokopi KTP & KK Pemegang Hak',
                    'Surat Pernyataan Penyerahan Sertifikat Analog',
                    'Bukti Pelunasan PBB Terakhir',
                ],
            ],
            [
                'kode' => 'JP14',
                'nama' => 'Sertifikasi BMN',
                'kategori' => 'bmn',
                'batas_hari_sla' => 30,
                'persyaratan' => [
                    'Surat Permohonan dari Kuasa Pengguna Barang (Satker)',
                    'SK Penetapan Status Penggunaan (PSP) dari KPKNL/Kemenkeu',
                    'Daftar Barang Kuasa Pengguna (DBKP) / SIMAN',
                    'Surat Pernyataan Penguasaan Fisik BMN',
                    'Berita Acara Tata Batas & Peta Situasi',
                ],
            ],
            [
                'kode' => 'JP15',
                'nama' => 'Penetapan Hak / SK Hak',
                'kategori' => 'bmn',
                'batas_hari_sla' => 30,
                'persyaratan' => [
                    'Surat Permohonan Penetapan Hak',
                    'Dokumen Alas Hak / Pelepasan Hak',
                    'Proposal Rencana Penggunaan Tanah',
                    'Peta Bidang Tanah (PBT) dari Kantah',
                    'SK Hak dan Berita Acara Pemeriksaan Tanah',
                ],
            ],
        ];

        foreach ($data as $item) {
            $jp = JenisPermohonan::updateOrCreate(
                ['kode' => $item['kode']],
                [
                    'nama' => $item['nama'],
                    'kategori' => $item['kategori'],
                    'batas_hari_sla' => $item['batas_hari_sla'],
                    'is_active' => true,
                ]
            );

            PersyaratanDokumen::where('jenis_permohonan_id', $jp->id)->delete();
            foreach ($item['persyaratan'] as $index => $req) {
                PersyaratanDokumen::create([
                    'jenis_permohonan_id' => $jp->id,
                    'nama_dokumen' => $req,
                    'wajib' => true,
                    'urutan' => $index + 1,
                ]);
            }

            // Default saran koreksi contoh
            SaranKoreksi::updateOrCreate(
                ['jenis_permohonan_id' => $jp->id, 'nama_dokumen_kurang' => 'Kelengkapan Identitas / Alas Hak'],
                [
                    'pesan_koreksi' => 'Harap melengkapi fotokopi KTP/KK yang masih berlaku atau melampirkan bukti alas hak/akta yang telah dilegalisir.',
                    'dasar_hukum' => 'PMNA/KBPN No. 3 Tahun 1997',
                ]
            );
        }
    }
}
