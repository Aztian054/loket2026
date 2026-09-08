<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tiket extends Model
{
    protected $fillable = [
        'no_tiket',
        'status_pembetulan',
        'tanggal_masuk',
        'jenis_permohonan_id',
        'nama_pemohon',
        'nik_pemohon',
        'no_hp_pemohon',
        'jumlah_bidang',
        'petugas_loket_id',
        'status',
        'status_verifikator',
        'status_warkah',
        'status_alih_media',
        'status_pembayaran',
        'jumlah_pembayaran',
        'tanggal_pembayaran',
        'petugas_pembayaran_id',
        'keterangan',
        'tanggal_target_selesai',
        'tanggal_selesai',
        'periode',
        'tahun',
        'sumber_data',
        'diarsipkan_pada',
        'diarsipkan_oleh',
    ];

    protected $casts = [
        'tanggal_masuk' => 'date',
        'tanggal_target_selesai' => 'date',
        'tanggal_selesai' => 'date',
        'tanggal_pembayaran' => 'date',
        'diarsipkan_pada' => 'datetime',
        'jumlah_bidang' => 'integer',
        'jumlah_pembayaran' => 'decimal:2',
    ];

    public static function generateNextNoTiket(int $iterasi = 1): string
    {
        $today = Carbon::today();
        $dateCode = $today->format('dmy'); // DDMMYY

        // Ambil nomor urut terbesar yang sudah ada untuk hari ini
        $lastNo = self::whereDate('tanggal_masuk', $today)
            ->where('no_tiket', 'like', "K/%/{$dateCode}/%")
            ->orderByRaw("CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(no_tiket, '/', 2), '/', -1) AS UNSIGNED) DESC")
            ->value('no_tiket');

        $nextNumber = 1;
        if ($lastNo) {
            // Format: K/{nomor}/{tanggal}/{iterasi}
            $parts = explode('/', $lastNo);
            if (count($parts) >= 2 && is_numeric($parts[1])) {
                $nextNumber = (int) $parts[1] + 1;
            }
        }

        return sprintf('K/%d/%s/%d', $nextNumber, $dateCode, $iterasi);
    }

    public function jenisPermohonan(): BelongsTo
    {
        return $this->belongsTo(JenisPermohonan::class);
    }

    public function petugasLoket(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_loket_id');
    }

    public function bidangTanahs(): HasMany
    {
        return $this->hasMany(BidangTanah::class)->orderBy('urutan');
    }

    public function verifikasiBerkas(): HasMany
    {
        return $this->hasMany(VerifikasiBerkas::class)->orderByDesc('iterasi');
    }

    public function latestVerifikasi(): HasOne
    {
        return $this->hasOne(VerifikasiBerkas::class)->latestOfMany();
    }

    public function lembarKerjaWarkahs(): HasMany
    {
        return $this->hasMany(LembarKerjaWarkah::class);
    }

    public function latestWarkah(): HasOne
    {
        return $this->hasOne(LembarKerjaWarkah::class)->latestOfMany();
    }

    public function lembarKerjaValidasis(): HasMany
    {
        return $this->hasMany(LembarKerjaValidasi::class);
    }

    public function latestValidasi(): HasOne
    {
        return $this->hasOne(LembarKerjaValidasi::class)->latestOfMany();
    }

    public function petugasPembayaran(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_pembayaran_id');
    }

    public function lembarKerjaAlihMedias(): HasMany
    {
        return $this->hasMany(LembarKerjaAlihMedia::class);
    }

    public function riwayatStatuses(): HasMany
    {
        return $this->hasMany(RiwayatStatus::class)->orderByDesc('created_at');
    }

    public function tiketRevisis(): HasMany
    {
        return $this->hasMany(TiketRevisi::class)->orderByDesc('created_at');
    }

    public function activeRevisis(): HasMany
    {
        return $this->hasMany(TiketRevisi::class)->where('status', 'aktif');
    }

    public function diterimaRevisis(): HasMany
    {
        return $this->hasMany(TiketRevisi::class)->where('status', 'diterima');
    }

    public function notifikasis(): HasMany
    {
        return $this->hasMany(Notifikasi::class);
    }

    /**
     * Cek apakah ada revisi aktif menuju stage tertentu.
     */
    public function isAktifRevisiMenuju(string $stage): bool
    {
        return $this->tiketRevisis()
            ->where('stage_tujuan', $stage)
            ->where('status', 'aktif')
            ->exists();
    }

    // ─── Stepper Helper Methods ────────────────────────────────

    public function isVerifikatorSelesai(): bool
    {
        return $this->status_verifikator === 'selesai';
    }

    public function isWarkahSelesai(): bool
    {
        return $this->status_warkah === 'selesai';
    }

    public function isValidasiLulus(): bool
    {
        $validasis = $this->lembarKerjaValidasis;
        if ($validasis->isEmpty()) {
            return false;
        }
        return $validasis->every('status_validasi_bidang', 'lulus');
    }

    public function isAlihMediaSelesai(): bool
    {
        return $this->status_alih_media === 'selesai';
    }

    public function isFinalSelesai(): bool
    {
        return $this->status === 'selesai';
    }

    public function getValidatorSummary(): array
    {
        $validasis = $this->lembarKerjaValidasis;
        if ($validasis->isEmpty()) {
            return ['semua_lulus' => false, 'details' => []];
        }
        $details = $validasis->map(fn($v) => [
            'bidang_id' => $v->bidang_id,
            'pra_btel' => $v->status_pra_btel,
            'pra_suel' => $v->status_pra_suel,
            'validasi_bidang' => $v->status_validasi_bidang,
        ])->toArray();

        return [
            'semua_lulus' => $validasis->every('status_validasi_bidang', 'lulus'),
            'details' => $details,
        ];
    }

    /**
     * Ambil teks dinamis untuk stepper (label perubahan antar stage).
     */
    public function getStepperDynamicLabel(): ?string
    {
        if ($this->status_verifikator === 'revisi_ke_loket' && in_array($this->status, ['diterima', 'dikembalikan'])) {
            return 'Revisi dari Verifikator';
        }
        if ($this->status_warkah === 'revisi' && in_array($this->status, ['diterima', 'dikembalikan'])) {
            return 'Revisi dari Warkah';
        }
        if ($this->status_verifikator === 'tertunda') {
            return 'Tertunda';
        }
        if (in_array($this->status, ['validasi']) && !$this->isValidasiLulus()) {
            $summary = $this->getValidatorSummary();
            $parts = [];
            foreach ($summary['details'] as $d) {
                if ($d['pra_btel'] === 'selesai' && $d['pra_suel'] !== 'selesai') {
                    $parts[] = 'Pra-BTel Selesai, Pra-SuEl Proses';
                } elseif ($d['pra_suel'] === 'selesai' && $d['pra_btel'] !== 'selesai') {
                    $parts[] = 'Pra-SuEl Selesai, Pra-BTel Proses';
                }
            }
            return $parts ? implode(' | ', $parts) : null;
        }
        return null;
    }

    /**
     * Array status untuk komponen stepper.
     */
    public function getStepperData(): array
    {
        $dynamicLabel = $this->getStepperDynamicLabel();

        $loketDone = $this->status !== 'diterima';
        $loketLoading = in_array($this->status_verifikator, ['revisi_ke_loket'])
            || ($this->status_warkah === 'revisi' && in_array($this->status, ['diterima', 'dikembalikan']));
        $loketClass = $loketLoading ? 'text-warning' : ($loketDone ? 'text-success' : 'text-secondary');
        $loketLabel = $loketLoading ? ($dynamicLabel ?? 'Perlu Perbaikan') : ($loketDone ? 'Selesai' : 'Proses');

        $verifClass = $this->isVerifikatorSelesai() ? 'text-success'
            : ($this->status_verifikator === 'tertunda' ? 'text-warning' : 'text-secondary');
        $verifLabel = $this->isVerifikatorSelesai() ? 'Selesai'
            : ($this->status_verifikator === 'tertunda' ? 'Tertunda' : 'Proses');

        $warkahClass = $this->isWarkahSelesai() ? 'text-success' : 'text-secondary';
        $warkahLabel = $this->isWarkahSelesai() ? 'Selesai' : 'Proses';

        $validasiDone = $this->isValidasiLulus();
        $validasiLoading = in_array($this->status, ['validasi']) && !$validasiDone;
        $validatorClass = $validasiDone ? 'text-success' : ($validasiLoading ? 'text-warning' : 'text-secondary');
        $validatorLabel = $validasiDone ? 'LULUS' : ($validasiLoading ? ($dynamicLabel ?? 'Proses') : 'Menunggu');

        $alihMediaDone = $this->isAlihMediaSelesai();
        $alihMediaLoading = $this->status === 'alih_media' && !$alihMediaDone;
        $alihMediaClass = $alihMediaDone ? 'text-success' : ($alihMediaLoading ? 'text-warning' : 'text-secondary');
        $alihMediaLabel = $alihMediaDone ? 'Selesai' : ($alihMediaLoading ? 'Proses' : 'Menunggu');

        // Pembayaran — muncul setelah Alih Media selesai (sebelum Selesai)
        $pembayaranLoading = $alihMediaDone && $this->status_pembayaran === 'belum_lunas' && $this->status !== 'batal';
        $pembayaranDone = $this->status_pembayaran === 'lunas';
        $pembayaranClass = $pembayaranDone ? 'text-success' : ($pembayaranLoading ? 'text-warning' : 'text-secondary');
        $pembayaranLabel = $pembayaranDone ? 'Lunas' : ($pembayaranLoading ? 'Menunggu' : 'Menunggu');

        $finalDone = $this->isFinalSelesai();
        $finalClass = $finalDone ? 'text-success' : 'text-secondary';
        $finalLabel = $finalDone ? 'Selesai' : 'Menunggu';

        return [
            ['name' => 'Loket',         'icon' => 'bi-person-fill',     'class' => $loketClass,      'label' => $loketLabel,      'sublabel' => $loketLoading ? $dynamicLabel : null],
            ['name' => 'Verifikator',   'icon' => 'bi-clipboard-check', 'class' => $verifClass,       'label' => $verifLabel,      'sublabel' => $this->status_verifikator === 'tertunda' ? 'Tertunda' : null],
            ['name' => 'Warkah',        'icon' => 'bi-archive-fill',    'class' => $warkahClass,      'label' => $warkahLabel,     'sublabel' => null],
            ['name' => 'Validator',     'icon' => 'bi-shield-check',    'class' => $validatorClass,   'label' => $validatorLabel,  'sublabel' => $dynamicLabel && str_contains($dynamicLabel, 'Pra-') ? $dynamicLabel : null],
            ['name' => 'Alih Media',    'icon' => 'bi-cpu-fill',        'class' => $alihMediaClass,   'label' => $alihMediaLabel,  'sublabel' => null],
            ['name' => 'Pembayaran',    'icon' => 'bi-credit-card-fill','class' => $pembayaranClass,  'label' => $pembayaranLabel, 'sublabel' => null],
            ['name' => 'Selesai',       'icon' => 'bi-check-circle-fill','class' => $finalClass,      'label' => $finalLabel,      'sublabel' => null],
        ];
    }

    /**
     * Scope: hanya tiket yang BELUM diarsipkan (aktif).
     */
    public function scopeAktif(Builder $query): void
    {
        $query->whereNull('diarsipkan_pada');
    }

    /**
     * Scope: hanya tiket yang SUDAH diarsipkan.
     */
    public function scopeArsip(Builder $query): void
    {
        $query->whereNotNull('diarsipkan_pada');
    }

    /**
     * Scope: tiket aktif yang siap diarsipkan (selesai / batal).
     */
    public function scopeSiapArsip(Builder $query): void
    {
        $query->aktif()->whereIn('status', ['selesai', 'batal']);
    }

    /**
     * Relasi: user yang mengarsipkan tiket.
     */
    public function diarsipkanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diarsipkan_oleh');
    }

    /**
     * Auto-fill tahun & sumber_data saat tiket dibuat.
     */
    protected static function booted(): void
    {
        static::creating(function (Tiket $tiket) {
            if (empty($tiket->tahun) && $tiket->tanggal_masuk) {
                $tiket->tahun = $tiket->tanggal_masuk->year;
            }
            if (empty($tiket->sumber_data)) {
                $tiket->sumber_data = 'sistem_loket';
            }
        });
    }

    /**
     * Accessor: apakah tiket ini sudah diarsipkan?
     */
    public function getIsArsipAttribute(): bool
    {
        return !is_null($this->diarsipkan_pada);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'diterima' => 'warning',
            'verifikasi' => 'info',
            'warkah' => 'secondary',
            'validasi' => 'primary',
            'alih_media' => 'dark',
            'selesai' => 'success',
            'dikembalikan' => 'danger',
            'batal' => 'danger',
            default => 'light',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'diterima' => 'Diterima di Loket',
            'verifikasi' => 'Pemeriksaan Verifikator',
            'warkah' => 'Pencarian Warkah',
            'validasi' => 'Validasi Data Pertanahan',
            'alih_media' => 'Proses Alih Media',
            'selesai' => 'Selesai (Sertifikat Terbit)',
            'dikembalikan' => 'Dikembalikan (Perlu Perbaikan)',
            'batal' => 'Dibatalkan',
            default => ucfirst($this->status),
        };
    }

    // ─── Stage Status Helpers ─────────────────────────────────

    /**
     * Label Indonesia untuk nilai flag status stage (proses, selesai, dll).
     */
    public static function labelStatusStage(?string $status): string
    {
        return match ($status) {
            'proses'          => 'Diproses',
            'selesai'         => 'Selesai',
            'tertunda'        => 'Tertunda',
            'revisi_ke_loket' => 'Revisi ke Loket',
            'revisi'          => 'Revisi',
            default           => 'Menunggu',
        };
    }

    /**
     * Badge color Bootstrap untuk flag status stage.
     */
    public static function badgeStatusStage(?string $status): string
    {
        return match ($status) {
            'proses'          => 'info',
            'selesai'         => 'success',
            'tertunda'        => 'warning',
            'revisi_ke_loket' => 'danger',
            'revisi'          => 'danger',
            default           => 'secondary',
        };
    }
}
