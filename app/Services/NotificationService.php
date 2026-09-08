<?php

namespace App\Services;

use App\Models\Notifikasi;
use App\Models\Tiket;
use App\Models\TiketRevisi;
use App\Models\User;

/**
 * Bab 9: Notifikasi & Konfirmasi Revisi.
 * Pusat pembuatan notifikasi lintas stage + trigger konfirmasi revisi.
 */
class NotificationService
{
    /** Pemetaan nama stage (kolom tiket_revisis) ke role inbok notifikasi. */
    public const STAGE_TO_ROLE = [
        'Loket' => 'loket',
        'Verifikator' => 'verifikator',
        'Warkah' => 'warkah',
        'Validator' => 'validator',
        'Alih Media' => 'alih_media',
        'Pembayaran' => 'pembayaran',
    ];

    public const ROLE_LABEL = [
        'loket' => 'Loket',
        'verifikator' => 'Verifikator',
        'warkah' => 'Warkah',
        'validator' => 'Validator',
        'alih_media' => 'Alih Media',
        'pembayaran' => 'Pembayaran',
    ];

    public function roleUntukStage(string $stage): ?string
    {
        return self::STAGE_TO_ROLE[$stage] ?? null;
    }

    public function labelRole(string $role): string
    {
        return self::ROLE_LABEL[$role] ?? ucfirst($role);
    }

    /**
     * Notifikasi tiket diteruskan ke suatu stage.
     */
    public function kirimForward(Tiket $tiket, string $roleTujuan, ?string $subBidang = null, ?string $pesanTambahan = null): Notifikasi
    {
        $pesan = "Tiket {$tiket->no_tiket} ({$tiket->nama_pemohon}) diteruskan ke {$this->labelRole($roleTujuan)}. Silakan lanjutkan pemeriksaan.";

        // Pesan/instruksi internal opsional dari stage asal (mis. Catatan Verifikator ke Validator).
        if ($pesanTambahan !== null && trim($pesanTambahan) !== '') {
            $pesan .= " Instruksi dari Verifikator: {$pesanTambahan}";
        }

        return $this->buat([
            'tiket_id' => $tiket->id,
            'role_tujuan' => $roleTujuan,
            'sub_bidang' => $subBidang,
            'tipe' => Notifikasi::TIPE_FORWARD,
            'judul' => 'Berkas masuk: ' . $tiket->no_tiket,
            'pesan' => $pesan,
            'link_role' => $roleTujuan,
            'butuh_konfirmasi' => false,
        ]);
    }

    /**
     * Notifikasi revisi yang menunggu konfirmasi penerimaan.
     */
    public function kirimRevisi(Tiket $tiket, TiketRevisi $revisi): Notifikasi
    {
        $roleTujuan = $this->roleUntukStage($revisi->stage_tujuan) ?? 'loket';

        return $this->buat([
            'tiket_id' => $tiket->id,
            'tiket_revisi_id' => $revisi->id,
            'role_tujuan' => $roleTujuan,
            'sub_bidang' => $revisi->sub_bidang,
            'tipe' => Notifikasi::TIPE_REVISI,
            'judul' => 'Revisi menunggu konfirmasi',
            'pesan' => "Tiket {$tiket->no_tiket}: {$revisi->stage_asal} meminta perbaikan. Catatan: " . ($revisi->pesan_catatan ?: '-') . " Klik untuk melihat detail dan konfirmasi.",
            'link_role' => $roleTujuan,
            'butuh_konfirmasi' => true,
        ]);
    }

    /**
     * Konfirmasi penerimaan revisi oleh stage tujuan + notifikasi balik ke stage asal.
     */
    public function konfirmasiRevisi(TiketRevisi $revisi, User $konfirmator): void
    {
        $revisi->konfirmasi($konfirmator);

        $roleAsal = $this->roleUntukStage($revisi->stage_asal);
        if ($roleAsal === null) {
            return;
        }

        $this->buat([
            'tiket_id' => $revisi->tiket_id,
            'tiket_revisi_id' => $revisi->id,
            'role_tujuan' => $roleAsal,
            'sub_bidang' => $revisi->sub_bidang,
            'tipe' => Notifikasi::TIPE_REVISI_DIKONFIRMASI,
            'judul' => 'Revisi telah dikonfirmasi',
            'pesan' => "{$this->labelRole($konfirmator->notificationStageRole() ?? 'Sistem')} telah menerima dan mengonfirmasi revisi dari {$revisi->stage_asal} untuk Tiket {$revisi->tiket->no_tiket}.",
            'link_role' => $roleAsal,
            'butuh_konfirmasi' => false,
        ]);
    }

    /**
     * Notifikasi berkas telah dikirim ulang / tuntas setelah revisi dikonfirmasi.
     */
    public function kirimUlangSetelahRevisi(TiketRevisi $revisi): ?Notifikasi
    {
        $roleTujuan = $this->roleUntukStage($revisi->stage_asal);
        if ($roleTujuan === null) {
            return null;
        }

        return $this->buat([
            'tiket_id' => $revisi->tiket_id,
            'tiket_revisi_id' => $revisi->id,
            'role_tujuan' => $roleTujuan,
            'sub_bidang' => $revisi->sub_bidang,
            'tipe' => Notifikasi::TIPE_REVISI_SELESAI,
            'judul' => 'Revisi telah diselesaikan',
            'pesan' => "Tiket {$revisi->tiket->no_tiket}: {$this->labelRole($roleTujuan)} dapat melanjutkan pemeriksaan. Berkas telah dikirim ulang.",
            'link_role' => $roleTujuan,
            'butuh_konfirmasi' => false,
        ]);
    }

    /**
     * Notifikasi info umum ke suatu role.
     */
    public function kirimInfo(Tiket $tiket, string $roleTujuan, string $judul, string $pesan, bool $butuhKonfirmasi = false): Notifikasi
    {
        return $this->buat([
            'tiket_id' => $tiket->id,
            'role_tujuan' => $roleTujuan,
            'tipe' => Notifikasi::TIPE_INFO,
            'judul' => $judul,
            'pesan' => $pesan,
            'link_role' => $roleTujuan,
            'butuh_konfirmasi' => $butuhKonfirmasi,
        ]);
    }

    /**
     * Tandai semua notifikasi belum dibaca milik tiket + role sebagai dibaca.
     * Untuk akun sub-role, hanya notifikasi sub-bidang miliknya yang ditandai.
     */
    public function tandaiDibacaUntukTiket(Tiket $tiket, string $roleTujuan, ?User $pembaca = null): int
    {
        $query = Notifikasi::where('tiket_id', $tiket->id)
            ->where('role_tujuan', $roleTujuan)
            ->where('is_read', false);

        if ($pembaca !== null) {
            $sub = $pembaca->notificationSubBidang();
            if ($sub !== null) {
                $query->where(function ($q) use ($sub) {
                    $q->whereNull('sub_bidang')
                      ->orWhere('sub_bidang', $sub);
                });
            }
        }

        return $query->update([
            'is_read' => true,
            'dibaca_oleh' => $pembaca?->id,
            'dibaca_pada' => now(),
        ]);
    }

    protected function buat(array $data): Notifikasi
    {
        return Notifikasi::create($data);
    }
}