<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TiketRevisi extends Model
{
    protected $table = 'tiket_revisis';

    protected $fillable = [
        'tiket_id',
        'stage_asal',
        'stage_tujuan',
        'sub_bidang',
        'pesan_catatan',
        'status',
        'created_by',
        'dikonfirmasi_pada',
        'dikonfirmasi_oleh',
    ];

    protected $casts = [
        'dikonfirmasi_pada' => 'datetime',
    ];

    public function tiket(): BelongsTo
    {
        return $this->belongsTo(Tiket::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dikonfirmasi_oleh');
    }

    public function notifikasis(): HasMany
    {
        return $this->hasMany(Notifikasi::class, 'tiket_revisi_id');
    }

    /**
     * Konfirmasi penerimaan revisi oleh stage tujuan (status aktif -> diterima).
     * Ikut menandai notifikasi revisi terkait sebagai dibaca.
     */
    public function konfirmasi(User $user): void
    {
        $this->update([
            'status' => 'diterima',
            'dikonfirmasi_pada' => now(),
            'dikonfirmasi_oleh' => $user->id,
        ]);

        $this->notifikasis()
            ->where('butuh_konfirmasi', true)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'dibaca_oleh' => $user->id,
                'dibaca_pada' => now(),
            ]);
    }

    /**
     * Tandai revisi selesai ditindaklanjuti (status -> tertangani).
     */
    public function tandaiTertangani(): void
    {
        $this->update(['status' => 'tertangani']);
    }
}
