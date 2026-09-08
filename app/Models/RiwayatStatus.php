<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatStatus extends Model
{
    protected $table = 'riwayat_statuses';

    protected $fillable = [
        'tiket_id',
        'stage_dari',
        'stage_ke',
        'changed_by',
        'keterangan',
    ];

    public function tiket(): BelongsTo
    {
        return $this->belongsTo(Tiket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
