<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notifikasi extends Model
{
    protected $table = 'notifikasis';

    public const TIPE_FORWARD = 'forward';
    public const TIPE_REVISI = 'revisi';
    public const TIPE_REVISI_DIKONFIRMASI = 'revisi_dikonfirmasi';
    public const TIPE_REVISI_SELESAI = 'revisi_selesai';
    public const TIPE_INFO = 'info';

    protected $fillable = [
        'tiket_id',
        'tiket_revisi_id',
        'role_tujuan',
        'sub_bidang',
        'tipe',
        'judul',
        'pesan',
        'link_role',
        'butuh_konfirmasi',
        'is_read',
        'dibaca_oleh',
        'dibaca_pada',
    ];

    protected $casts = [
        'butuh_konfirmasi' => 'boolean',
        'is_read' => 'boolean',
        'dibaca_pada' => 'datetime',
    ];

    public function tiket(): BelongsTo
    {
        return $this->belongsTo(Tiket::class);
    }

    public function tiketRevisi(): BelongsTo
    {
        return $this->belongsTo(TiketRevisi::class);
    }

    public function pembaca(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibaca_oleh');
    }

    public function scopeForRole(Builder $query, string $role): Builder
    {
        return $query->where('role_tujuan', $role);
    }

    /**
     * Scope untuk notifikasi milik seorang user (admin melihat semua stage).
     * Akun sub-role (validator_btel/alih_media_btel, dst) hanya melihat notifikasi
     * sub-bidangnya sendiri; notifikasi umum (sub_bidang null) tetap terlihat.
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        $role = $user->notificationStageRole();

        if ($role === null) {
            return $query;
        }

        $query->where('role_tujuan', $role);

        $sub = $user->notificationSubBidang();
        if ($sub !== null) {
            $query->where(function (Builder $q) use ($sub) {
                $q->whereNull('sub_bidang')
                  ->orWhere('sub_bidang', $sub);
            });
        }

        return $query;
    }

    public function scopeUnreadForRole(Builder $query, string $role): Builder
    {
        return $query->forRole($role)->where('is_read', false);
    }

    public function scopeButuhKonfirmasi(Builder $query, string $role): Builder
    {
        return $query->forRole($role)
            ->where('butuh_konfirmasi', true)
            ->where('is_read', false);
    }

    public function tandaiDibaca(?User $user = null): bool
    {
        if ($this->is_read) {
            return false;
        }

        $this->is_read = true;
        $this->dibaca_oleh = $user?->id;
        $this->dibaca_pada = now();

        return $this->save();
    }
}