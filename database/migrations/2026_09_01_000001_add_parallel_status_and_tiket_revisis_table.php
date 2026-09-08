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
        // ──────────────────────────────────────────────────────
        // 1. Tambah kolom status paralel ke tabel `tikets`
        // ──────────────────────────────────────────────────────
        Schema::table('tikets', function (Blueprint $table) {
            $table->enum('status_verifikator', ['proses', 'tertunda', 'revisi_ke_loket', 'selesai'])
                ->default('proses')
                ->after('status');
            $table->enum('status_warkah', ['proses', 'revisi', 'selesai'])
                ->default('proses')
                ->after('status_verifikator');
            $table->enum('status_alih_media', ['proses', 'selesai'])
                ->default('proses')
                ->after('status_warkah');
        });

        // ──────────────────────────────────────────────────────
        // 2. Tambah kolom `status_validasi_bidang` & `catatan_validator`
        //    ke tabel `lembar_kerja_validasis` (per-bidang)
        // ──────────────────────────────────────────────────────
        Schema::table('lembar_kerja_validasis', function (Blueprint $table) {
            $table->enum('status_validasi_bidang', ['proses', 'lulus'])
                ->default('proses')
                ->after('status_validasi');
            $table->text('catatan_validator')->nullable()
                ->after('catatan');
        });

        // ──────────────────────────────────────────────────────
        // 3. Tabel baru `tiket_revisis` — log catatan revisi
        // ──────────────────────────────────────────────────────
        Schema::create('tiket_revisis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tiket_id')->constrained('tikets')->onDelete('cascade');
            $table->string('stage_asal', 50);     // Verifikator, Warkah, Validator, Alih Media
            $table->string('stage_tujuan', 50);    // Loket, Verifikator, Warkah, Validator
            $table->string('sub_bidang', 20)->nullable(); // pra_btel / pra_suel (khusus Validator ↔ Alih Media)
            $table->text('pesan_catatan');
            $table->enum('status', ['aktif', 'tertangani'])->default('aktif');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tiket_revisis');

        Schema::table('lembar_kerja_validasis', function (Blueprint $table) {
            $table->dropColumn(['status_validasi_bidang', 'catatan_validator']);
        });

        Schema::table('tikets', function (Blueprint $table) {
            $table->dropColumn(['status_verifikator', 'status_warkah', 'status_alih_media']);
        });
    }
};
