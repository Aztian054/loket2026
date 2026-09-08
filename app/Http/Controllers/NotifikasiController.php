<?php

namespace App\Http\Controllers;

use App\Models\Notifikasi;
use App\Models\Tiket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotifikasiController extends Controller
{
    /**
     * Halaman inbox notifikasi untuk role yang sedang login.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Notifikasi::forUser($user)->with(['tiket', 'tiketRevisi'])->latest();

        if ($request->filled('tipe')) {
            $query->where('tipe', $request->tipe);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->whereHas('tiket', function ($t) use ($q) {
                $t->where('no_tiket', 'like', "%{$q}%")
                    ->orWhere('nama_pemohon', 'like', "%{$q}%");
            });
        }

        $notifikasis = $query->paginate(15)->withQueryString();
        $unreadCount = Notifikasi::forUser($user)->where('is_read', false)->count();

        return view('notifikasi.index', compact('notifikasis', 'unreadCount'));
    }

    /**
     * Tandai satu notifikasi sebagai dibaca lalu arahkan ke tiket terkait.
     */
    public function tandaiBaca($id)
    {
        $user = Auth::user();
        $notif = Notifikasi::forUser($user)->findOrFail($id);
        $notif->tandaiDibaca($user);

        // Auto-read notifikasi lain milik tiket yang sama (role yang sama).
        Notifikasi::forUser($user)
            ->where('tiket_id', $notif->tiket_id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'dibaca_oleh' => $user->id,
                'dibaca_pada' => now(),
            ]);

        return $this->redirectTarget($notif);
    }

    /**
     * Tandai semua notifikasi milik user sebagai dibaca.
     */
    public function tandaiSemuaBaca()
    {
        $user = Auth::user();

        Notifikasi::forUser($user)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'dibaca_oleh' => $user->id,
                'dibaca_pada' => now(),
            ]);

        return back()->with('success', 'Semua notifikasi telah ditandai dibaca.');
    }

    /**
     * Bangun URL tujuan setelah notifikasi dibaca.
     */
    private function redirectTarget(Notifikasi $notif)
    {
        $role = $notif->link_role ?? $notif->role_tujuan;
        $routeMap = [
            'loket' => ['loket.show', 'loket.index'],
            'verifikator' => ['verifikator.show', 'verifikator.index'],
            'warkah' => ['warkah.show', 'warkah.index'],
            'validator' => ['validator.show', 'validator.index'],
            'alih_media' => ['alih_media.show', 'alih_media.index'],
            'pembayaran' => ['pembayaran.show', 'pembayaran.index'],
        ];

        [$show, $index] = $routeMap[$role] ?? [null, 'dashboard'];

        if ($show && $this->stageMasihTerjangkau($role, $notif->tiket_id)) {
            return redirect()->route($show, $notif->tiket_id);
        }

        return redirect()->route($index);
    }

    /**
     * Cek apakah tiket masih bisa dibuka pada show stage bersangkutan.
     */
    private function stageMasihTerjangkau(string $role, int $tiketId): bool
    {
        try {
            $tiket = Tiket::aktif()->findOrFail($tiketId);
        } catch (\Throwable $e) {
            return false;
        }

        return match ($role) {
            'verifikator' => in_array($tiket->status_verifikator, ['proses', 'tertunda', 'revisi_ke_loket']),
            'warkah' => in_array($tiket->status_warkah, ['proses', 'revisi']),
            'validator' => $tiket->status === 'validasi' || $tiket->isAktifRevisiMenuju('Validator'),
            'alih_media' => $tiket->status === 'alih_media' || $tiket->isAktifRevisiMenuju('Alih Media'),
            'pembayaran' => $tiket->status_alih_media === 'selesai' && $tiket->status_pembayaran === 'belum_lunas',
            'loket' => true,
            default => true,
        };
    }
}