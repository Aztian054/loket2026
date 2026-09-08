<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tikets', function (Blueprint $table) {
            $table->string('periode', 20)->nullable()->after('tanggal_masuk');
            $table->string('tahun', 4)->nullable()->after('periode');
            $table->string('sumber_data', 50)->nullable()->after('tahun');
            $table->timestamp('diarsipkan_pada')->nullable()->after('tanggal_selesai');
            $table->foreignId('diarsipkan_oleh')->nullable()->after('diarsipkan_pada')
                ->constrained('users')
                ->nullOnDelete();
        });

        // Backfill kolom tahun dari tanggal_masuk untuk seluruh data tiket yang sudah ada
        DB::table('tikets')
            ->whereNull('tahun')
            ->update(['tahun' => DB::raw('YEAR(tanggal_masuk)')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tikets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('diarsipkan_oleh');
            $table->dropColumn(['periode', 'tahun', 'sumber_data', 'diarsipkan_pada']);
        });
    }
};