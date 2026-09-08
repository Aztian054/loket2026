<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LembarKerjaValidasi extends Model
{
    protected $table = 'lembar_kerja_validasis';

    protected $fillable = [
        'tiket_id',
        'bidang_id',
        'validator_id',
        'validator_btel_id',
        'validator_suel_id',
        'tanggal_mulai',
        'tanggal_selesai',
        'kesesuaian_nama',
        'kesesuaian_luas',
        'status_pra_btel',
        'status_pra_suel',
        'status_validasi',
        'status_validasi_bidang',
        'catatan',
        'catatan_validator',
        'diteruskan_alih_media',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'diteruskan_alih_media' => 'boolean',
    ];

    public function tiket(): BelongsTo
    {
        return $this->belongsTo(Tiket::class);
    }

    public function bidang(): BelongsTo
    {
        return $this->belongsTo(BidangTanah::class, 'bidang_id');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validator_id');
    }

    public function validatorBtel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validator_btel_id');
    }

    public function validatorSuel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validator_suel_id');
    }
}
