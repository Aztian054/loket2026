<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BidangTanah extends Model
{
    protected $fillable = [
        'tiket_id',
        'nib',
        'no_sertifikat_lama',
        'no_sertifikat_elektronik',
        'jenis_hak',
        'nama_pemegang_hak',
        'luas_m2',
        'desa_kelurahan',
        'kecamatan',
        'status_plotting',
        'urutan',
    ];

    protected $casts = [
        'luas_m2' => 'decimal:2',
        'urutan' => 'integer',
    ];

    public function tiket(): BelongsTo
    {
        return $this->belongsTo(Tiket::class);
    }
}
