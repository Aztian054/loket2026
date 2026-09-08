<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersyaratanDokumen extends Model
{
    protected $fillable = [
        'jenis_permohonan_id',
        'nama_dokumen',
        'wajib',
        'keterangan',
        'urutan',
    ];

    protected $casts = [
        'wajib' => 'boolean',
        'urutan' => 'integer',
    ];

    public function jenisPermohonan(): BelongsTo
    {
        return $this->belongsTo(JenisPermohonan::class);
    }
}
