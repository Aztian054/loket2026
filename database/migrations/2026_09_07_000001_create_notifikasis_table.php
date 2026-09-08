<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bab 9: Sistem Notifikasi & Konfirmasi Revisi.
     * Tabel notifikasi per-role (inbox per stage).
     */
    public function up(): void
    {
        Schema::create('notifikasis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tiket_id')->constrained('tikets')->cascadeOnDelete();
            $table->foreignId('tiket_revisi_id')->nullable()->constrained('tiket_revisis')->nullOnDelete();
            $table->string('role_tujuan', 30);
            $table->string('sub_bidang', 20)->nullable();
            $table->string('tipe', 30);
            $table->string('judul', 191);
            $table->text('pesan');
            $table->string('link_role', 30)->nullable();
            $table->boolean('butuh_konfirmasi')->default(false);
            $table->boolean('is_read')->default(false);
            $table->foreignId('dibaca_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dibaca_pada')->nullable();
            $table->timestamps();

            $table->index(['role_tujuan', 'is_read']);
            $table->index(['tiket_id', 'is_read']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifikasis');
    }
};