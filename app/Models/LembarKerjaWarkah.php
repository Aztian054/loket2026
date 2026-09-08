<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LembarKerjaWarkah extends Model
{
    protected $table = 'lembar_kerja_warkahs';

    protected $fillable = [
        'tiket_id',
        'bidang_id',
        'petugas_id',
        'tanggal_mulai',
        'tanggal_selesai',
        'lokasi_fisik',
        'kondisi',
        'status_keberadaan',
        'status_scan',
        'catatan',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'status_scan' => 'boolean',
    ];

    public function tiket(): BelongsTo
    {
        return $this->belongsTo(Tiket::class);
    }

    public function bidang(): BelongsTo
    {
        return $this->belongsTo(BidangTanah::class, 'bidang_id');
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }
}
