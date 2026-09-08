<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Revisi Final: Hapus kolom SPS dari tabel `tikets`.
 *
 * Fitur Status SPS (status_sps & tanggal_sps) dihapus dari alur Revisi Final.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tikets', function ($table) {
            $table->dropColumn(['status_sps', 'tanggal_sps']);
        });
    }

    public function down(): void
    {
        Schema::table('tikets', function ($table) {
            $table->boolean('status_sps')->default(false)->after('petugas_pembayaran_id');
            $table->date('tanggal_sps')->nullable()->after('status_sps');
        });
    }
};
