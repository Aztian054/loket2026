<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LembarKerjaAlihMedia extends Model
{
    protected $table = 'lembar_kerja_alih_medias';

    protected $fillable = [
        'tiket_id',
        'bidang_id',
        'petugas_id',
        'petugas_btel_id',
        'petugas_suel_id',
        'tanggal_mulai',
        'tanggal_selesai',
        'status_scan_buku_tanah',
        'status_scan_surat_ukur',
        'status_scan_warkah',
        'status_upload_kkp',
        'status_ttd_elektronik',
        'tanggal_terbit_sertifikat_el',
        'catatan',
        'status_btel',
        'status_suel',
        'status_scan_buku_tanah_btel',
        'status_scan_warkah_btel',
        'status_upload_kkp_btel',
        'status_ttd_elektronik_btel',
        'tanggal_terbit_btel',
        'status_scan_surat_ukur_suel',
        'status_scan_warkah_suel',
        'status_upload_kkp_suel',
        'status_ttd_elektronik_suel',
        'tanggal_terbit_suel',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'tanggal_terbit_sertifikat_el' => 'date',
        'tanggal_terbit_btel' => 'date',
        'tanggal_terbit_suel' => 'date',
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

    public function petugasBtel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_btel_id');
    }

    public function petugasSuel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_suel_id');
    }
}
