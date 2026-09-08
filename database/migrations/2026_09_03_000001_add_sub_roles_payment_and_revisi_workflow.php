<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan:
     *  1. Sub-akun Validator & Alih Media (Pra-BTel / Pra-SuEl) + role kasir pembayaran
     *  2. Tahap pembayaran (grand final) di tabel `tikets`
     *  3. Penugasan per sub-bidang di `lembar_kerja_validasis` & `lembar_kerja_alih_medias`
     *  4. Alur konfirmasi penerimaan revisi di `tiket_revisis`
     */
    public function up(): void
    {
        // 1. Perluas ENUM role: sub-akun validator/alih media + kasir pembayaran
        DB::statement("ALTER TABLE users MODIFY role ENUM('admin','loket','verifikator','warkah','validator','alih_media','pimpinan','validator_btel','validator_suel','alih_media_btel','alih_media_suel','pembayaran') NOT NULL DEFAULT 'loket'");

        // 2. Kolom pembayaran (grand final) di `tikets`
        Schema::table('tikets', function (Blueprint $table) {
            $table->enum('status_pembayaran', ['belum_lunas', 'lunas'])->default('belum_lunas')->after('status_alih_media');
            $table->decimal('jumlah_pembayaran', 15, 2)->nullable()->after('status_pembayaran');
            $table->date('tanggal_pembayaran')->nullable()->after('jumlah_pembayaran');
            $table->foreignId('petugas_pembayaran_id')->nullable()->after('tanggal_pembayaran')->constrained('users')->nullOnDelete();
        });

        // 3. Sub-akun validator per sub-bidang
        Schema::table('lembar_kerja_validasis', function (Blueprint $table) {
            $table->foreignId('validator_btel_id')->nullable()->after('validator_id')->constrained('users')->nullOnDelete();
            $table->foreignId('validator_suel_id')->nullable()->after('validator_btel_id')->constrained('users')->nullOnDelete();
        });

        // 4. Sub-akun & status per sub-bidang di alih media
        Schema::table('lembar_kerja_alih_medias', function (Blueprint $table) {
            $table->foreignId('petugas_btel_id')->nullable()->after('petugas_id')->constrained('users')->nullOnDelete();
            $table->foreignId('petugas_suel_id')->nullable()->after('petugas_btel_id')->constrained('users')->nullOnDelete();
            $table->enum('status_btel', ['belum', 'proses', 'selesai'])->default('belum')->after('petugas_suel_id');
            $table->enum('status_suel', ['belum', 'proses', 'selesai'])->default('belum')->after('status_btel');

            // Sub-bidang Pra-BTel
            $table->enum('status_scan_buku_tanah_btel', ['belum', 'sudah', 'kualitas_buruk'])->default('belum')->after('status_suel');
            $table->enum('status_scan_warkah_btel', ['belum', 'sudah'])->default('belum')->after('status_scan_buku_tanah_btel');
            $table->enum('status_upload_kkp_btel', ['belum', 'sudah'])->default('belum')->after('status_scan_warkah_btel');
            $table->enum('status_ttd_elektronik_btel', ['belum', 'sudah'])->default('belum')->after('status_upload_kkp_btel');
            $table->date('tanggal_terbit_btel')->nullable()->after('status_ttd_elektronik_btel');

            // Sub-bidang Pra-SuEl
            $table->enum('status_scan_surat_ukur_suel', ['belum', 'sudah', 'kualitas_buruk'])->default('belum')->after('tanggal_terbit_btel');
            $table->enum('status_scan_warkah_suel', ['belum', 'sudah'])->default('belum')->after('status_scan_surat_ukur_suel');
            $table->enum('status_upload_kkp_suel', ['belum', 'sudah'])->default('belum')->after('status_scan_warkah_suel');
            $table->enum('status_ttd_elektronik_suel', ['belum', 'sudah'])->default('belum')->after('status_upload_kkp_suel');
            $table->date('tanggal_terbit_suel')->nullable()->after('status_ttd_elektronik_suel');
        });

        // 5. Konfirmasi penerimaan revisi + perluas status (aktif -> diterima -> tertangani)
        DB::statement("ALTER TABLE tiket_revisis MODIFY status ENUM('aktif','diterima','tertangani') NOT NULL DEFAULT 'aktif'");
        Schema::table('tiket_revisis', function (Blueprint $table) {
            $table->timestamp('dikonfirmasi_pada')->nullable()->after('status');
            $table->foreignId('dikonfirmasi_oleh')->nullable()->after('dikonfirmasi_pada')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tiket_revisis', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dikonfirmasi_oleh');
            $table->dropColumn(['dikonfirmasi_pada']);
        });
        DB::statement("ALTER TABLE tiket_revisis MODIFY status ENUM('aktif','tertangani') NOT NULL DEFAULT 'aktif'");

        Schema::table('lembar_kerja_alih_medias', function (Blueprint $table) {
            $table->dropConstrainedForeignId('petugas_btel_id');
            $table->dropConstrainedForeignId('petugas_suel_id');
            $table->dropColumn([
                'status_btel',
                'status_suel',
                'status_scan_buku_tanah_btel',
                'status_scan_warkah_btel',
                'status_upload_kkp_btel',
                'status_ttd_elektronik_btel',
                'tanggal_terbit_btel',
                'status_scan_surat_ukur_suel',
                'status_scan_warkah_suel',
                'status_upload_kkp_suel',
                'status_ttd_elektronik_suel',
                'tanggal_terbit_suel',
            ]);
        });

        Schema::table('lembar_kerja_validasis', function (Blueprint $table) {
            $table->dropConstrainedForeignId('validator_btel_id');
            $table->dropConstrainedForeignId('validator_suel_id');
        });

        Schema::table('tikets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('petugas_pembayaran_id');
            $table->dropColumn(['status_pembayaran', 'jumlah_pembayaran', 'tanggal_pembayaran']);
        });

        DB::statement("ALTER TABLE users MODIFY role ENUM('admin','loket','verifikator','warkah','validator','alih_media','pimpinan') NOT NULL DEFAULT 'loket'");
    }
};
