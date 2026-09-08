<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JenisPermohonan extends Model
{
    protected $fillable = [
        'kode',
        'nama',
        'kategori',
        'batas_hari_sla',
        'deskripsi',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'batas_hari_sla' => 'integer',
    ];

    public function persyaratanDokumens(): HasMany
    {
        return $this->hasMany(PersyaratanDokumen::class)->orderBy('urutan');
    }

    public function saranKoreksis(): HasMany
    {
        return $this->hasMany(SaranKoreksi::class);
    }

    public function tikets(): HasMany
    {
        return $this->hasMany(Tiket::class);
    }
}
