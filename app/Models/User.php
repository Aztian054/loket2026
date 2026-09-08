<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isLoket(): bool
    {
        return $this->role === 'loket' || $this->role === 'admin';
    }

    public function isVerifikator(): bool
    {
        return $this->role === 'verifikator' || $this->role === 'admin';
    }

    public function isWarkah(): bool
    {
        return $this->role === 'warkah' || $this->role === 'admin';
    }

    public function isValidator(): bool
    {
        return in_array($this->role, ['validator', 'validator_btel', 'validator_suel', 'admin']);
    }

    public function isValidatorBtel(): bool
    {
        return $this->role === 'validator_btel' || $this->role === 'admin';
    }

    public function isValidatorSuel(): bool
    {
        return $this->role === 'validator_suel' || $this->role === 'admin';
    }

    public function isAlihMedia(): bool
    {
        return in_array($this->role, ['alih_media', 'alih_media_btel', 'alih_media_suel', 'admin']);
    }

    public function isAlihMediaBtel(): bool
    {
        return $this->role === 'alih_media_btel' || $this->role === 'admin';
    }

    public function isAlihMediaSuel(): bool
    {
        return $this->role === 'alih_media_suel' || $this->role === 'admin';
    }

    public function isPembayaran(): bool
    {
        return $this->role === 'pembayaran' || $this->role === 'admin';
    }

    public function isPimpinan(): bool
    {
        return $this->role === 'pimpinan' || $this->role === 'admin';
    }

    /**
     * Bab 9: Role inbox notifikasi untuk stage ini.
     * Admin/pimpinan melihat semua stage (null = all).
     */
    public function notificationStageRole(): ?string
    {
        return match ($this->role) {
            'admin' => null,
            'loket' => 'loket',
            'verifikator' => 'verifikator',
            'warkah' => 'warkah',
            'validator', 'validator_btel', 'validator_suel' => 'validator',
            'alih_media', 'alih_media_btel', 'alih_media_suel' => 'alih_media',
            'pembayaran' => 'pembayaran',
            'pimpinan' => null,
            default => null,
        };
    }

    /**
     * Bab 9: Sub-bidang yang ditangani akun (isolasi revisi/notifikasi).
     * Akun umum (validator/alih_media/admin) menangani semua sub-bidang (null = all).
     */
    public function notificationSubBidang(): ?string
    {
        return match ($this->role) {
            'validator_btel', 'alih_media_btel' => 'pra_btel',
            'validator_suel', 'alih_media_suel' => 'pra_suel',
            default => null,
        };
    }
}
