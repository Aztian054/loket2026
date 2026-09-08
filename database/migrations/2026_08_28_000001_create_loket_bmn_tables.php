<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Jenis Permohonan
        Schema::create('jenis_permohonans', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 10)->unique();
            $table->string('nama', 200);
            $table->enum('kategori', ['umum', 'bmn', 'alih_media'])->default('umum');
            $table->integer('batas_hari_sla')->default(7);
            $table->text('deskripsi')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Persyaratan Dokumen
        Schema::create('persyaratan_dokumens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jenis_permohonan_id')->constrained('jenis_permohonans')->onDelete('cascade');
            $table->string('nama_dokumen', 200);
            $table->boolean('wajib')->default(true);
            $table->string('keterangan', 255)->nullable();
            $table->integer('urutan')->default(0);
            $table->timestamps();
        });

        // 3. Saran Koreksi (Template Kekurangan)
        Schema::create('saran_koreksis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jenis_permohonan_id')->nullable()->constrained('jenis_permohonans')->onDelete('cascade');
            $table->string('nama_dokumen_kurang', 200);
            $table->text('pesan_koreksi');
            $table->string('dasar_hukum', 255)->nullable();
            $table->timestamps();
        });

        // 4. Tikets
        Schema::create('tikets', function (Blueprint $table) {
            $table->id();
            $table->string('no_tiket', 50)->unique(); // K/23/070225/1
            $table->enum('status_pembetulan', ['P0', 'P1', 'P2', 'P3', 'P4', 'P5'])->default('P0');
            $table->date('tanggal_masuk');
            $table->foreignId('jenis_permohonan_id')->constrained('jenis_permohonans');
            $table->string('nama_pemohon', 200);
            $table->string('nik_pemohon', 20)->nullable();
            $table->string('no_hp_pemohon', 20);
            $table->string('satuan_kerja', 200)->nullable();
            $table->integer('jumlah_bidang')->default(1);
            $table->foreignId('petugas_loket_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', [
                'diterima',
                'verifikasi',
                'warkah',
                'validasi',
                'alih_media',
                'selesai',
                'dikembalikan',
                'batal'
            ])->default('diterima');
            $table->boolean('status_sps')->default(false);
            $table->date('tanggal_sps')->nullable();
            $table->text('keterangan')->nullable();
            $table->date('tanggal_target_selesai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->timestamps();
        });

        // 5. Bidang Tanah
        Schema::create('bidang_tanahs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tiket_id')->constrained('tikets')->onDelete('cascade');
            $table->string('nib', 50)->nullable();
            $table->string('no_sertifikat_lama', 100)->nullable();
            $table->string('no_sertifikat_elektronik', 100)->nullable();
            $table->enum('jenis_hak', ['HM', 'HGB', 'HGU', 'HP', 'HPL'])->nullable();
            $table->string('nama_pemegang_hak', 200)->nullable();
            $table->decimal('luas_m2', 12, 2)->nullable();
            $table->string('desa_kelurahan', 100)->nullable();
            $table->string('kecamatan', 100)->nullable();
            $table->enum('status_plotting', ['belum', 'sudah'])->default('belum');
            $table->integer('urutan')->default(1);
            $table->timestamps();
        });

        // 6. Verifikasi Berkas
        Schema::create('verifikasi_berkas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tiket_id')->constrained('tikets')->onDelete('cascade');
            $table->foreignId('verifikator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('iterasi')->default(1);
            $table->date('tanggal_diterima')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->enum('status', ['proses', 'lengkap', 'perbaikan', 'batal'])->default('proses');
            $table->text('catatan')->nullable();
            $table->json('dokumen_kurang')->nullable();
            $table->timestamps();
        });

        // 7. Lembar Kerja Warkah
        Schema::create('lembar_kerja_warkahs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tiket_id')->constrained('tikets')->onDelete('cascade');
            $table->foreignId('bidang_id')->nullable()->constrained('bidang_tanahs')->nullOnDelete();
            $table->foreignId('petugas_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->string('lokasi_fisik', 200)->nullable();
            $table->enum('kondisi', ['baik', 'rusak', 'tidak_terbaca'])->nullable();
            $table->enum('status_keberadaan', ['ada', 'tidak_ada', 'perlu_dicari'])->nullable();
            $table->boolean('status_scan')->default(false);
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        // 8. Lembar Kerja Validasi
        Schema::create('lembar_kerja_validasis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tiket_id')->constrained('tikets')->onDelete('cascade');
            $table->foreignId('bidang_id')->nullable()->constrained('bidang_tanahs')->nullOnDelete();
            $table->foreignId('validator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->enum('kesesuaian_nama', ['sesuai', 'tidak_sesuai'])->nullable();
            $table->enum('kesesuaian_luas', ['sesuai', 'tidak_sesuai'])->nullable();
            $table->enum('status_pra_btel', ['belum', 'proses', 'selesai'])->default('belum');
            $table->enum('status_pra_suel', ['belum', 'proses', 'selesai'])->default('belum');
            $table->enum('status_validasi', ['proses', 'lulus', 'perlu_koreksi', 'ditolak'])->default('proses');
            $table->text('catatan')->nullable();
            $table->boolean('diteruskan_alih_media')->default(false);
            $table->timestamps();
        });

        // 9. Lembar Kerja Alih Media
        Schema::create('lembar_kerja_alih_medias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tiket_id')->constrained('tikets')->onDelete('cascade');
            $table->foreignId('bidang_id')->nullable()->constrained('bidang_tanahs')->nullOnDelete();
            $table->foreignId('petugas_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->enum('status_scan_buku_tanah', ['belum', 'sudah', 'kualitas_buruk'])->default('belum');
            $table->enum('status_scan_surat_ukur', ['belum', 'sudah', 'kualitas_buruk'])->default('belum');
            $table->enum('status_scan_warkah', ['belum', 'sudah'])->default('belum');
            $table->enum('status_upload_kkp', ['belum', 'sudah'])->default('belum');
            $table->enum('status_ttd_elektronik', ['belum', 'sudah'])->default('belum');
            $table->date('tanggal_terbit_sertifikat_el')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        // 10. Riwayat Status (Audit Trail)
        Schema::create('riwayat_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tiket_id')->constrained('tikets')->onDelete('cascade');
            $table->string('stage_dari', 50);
            $table->string('stage_ke', 50);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('riwayat_statuses');
        Schema::dropIfExists('lembar_kerja_alih_medias');
        Schema::dropIfExists('lembar_kerja_validasis');
        Schema::dropIfExists('lembar_kerja_warkahs');
        Schema::dropIfExists('verifikasi_berkas');
        Schema::dropIfExists('bidang_tanahs');
        Schema::dropIfExists('tikets');
        Schema::dropIfExists('saran_koreksis');
        Schema::dropIfExists('persyaratan_dokumens');
        Schema::dropIfExists('jenis_permohonans');
    }
};
