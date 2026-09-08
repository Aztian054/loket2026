<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaranKoreksi extends Model
{
    protected $fillable = [
        'jenis_permohonan_id',
        'nama_dokumen_kurang',
        'pesan_koreksi',
        'dasar_hukum',
    ];

    public function jenisPermohonan(): BelongsTo
    {
        return $this->belongsTo(JenisPermohonan::class);
    }
}
