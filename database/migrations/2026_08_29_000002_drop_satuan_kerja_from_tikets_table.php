<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tikets', function (Blueprint $table) {
            $table->dropColumn('satuan_kerja');
        });
    }

    public function down(): void
    {
        Schema::table('tikets', function (Blueprint $table) {
            $table->string('satuan_kerja', 200)->nullable()->after('no_hp_pemohon');
        });
    }
};
