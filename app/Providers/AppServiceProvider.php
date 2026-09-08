<?php

namespace App\Providers;

use App\Models\Notifikasi;
use App\Models\Tiket;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Bagikan data revisi & notifikasi per-stage ke seluruh view (untuk badge sidebar & bell).
        View::composer(['layouts.app', 'dashboard.*', 'verifikator.*', 'warkah.*', 'validator.*', 'alih_media.*', 'loket.*', 'pembayaran.*', 'notifikasi.*'], function ($view) {
            $view->with('revisiCounts', [
                'verifikator' => Tiket::aktif()->where('status_verifikator', 'revisi_ke_loket')->count(),
                'warkah' => Tiket::aktif()->where('status_warkah', 'revisi')->count(),
                'validator' => Tiket::aktif()
                    ->whereHas('tiketRevisis', fn ($q) => $q->where('stage_tujuan', 'Validator')->where('status', 'aktif'))
                    ->count(),
                'alih_media' => Tiket::aktif()
                    ->whereHas('tiketRevisis', fn ($q) => $q->where('stage_tujuan', 'Alih Media')->where('status', 'aktif'))
                    ->count(),
                'loket' => Tiket::aktif()->where('status', 'dikembalikan')->count(),
                'pembayaran' => Tiket::aktif()->where('status_alih_media', 'selesai')->where('status_pembayaran', 'belum_lunas')
                    ->where('status', '!=', 'batal')->count(),
            ]);

            $roles = ['loket', 'verifikator', 'warkah', 'validator', 'alih_media', 'pembayaran'];

            /** @var User|null $user */
            $user = Auth::user();
            $userSub = $user ? $user->notificationSubBidang() : null;
            $userStage = $user ? $user->notificationStageRole() : null;

            $notifCounts = [];
            $revisiPendingCounts = [];
            foreach ($roles as $role) {
                $query = Notifikasi::where('role_tujuan', $role)->where('is_read', false);

                // Isolasi sub-bidang untuk badge stage milik akun sub-role
                if ($userSub !== null && $userStage === $role) {
                    $query->where(function ($q) use ($userSub) {
                        $q->whereNull('sub_bidang')
                          ->orWhere('sub_bidang', $userSub);
                    });
                }

                $notifCounts[$role] = (clone $query)->count();
                $revisiPendingCounts[$role] = (clone $query)
                    ->where('butuh_konfirmasi', true)
                    ->count();
            }

            $notifCounts['total'] = $user ? Notifikasi::forUser($user)->where('is_read', false)->count() : 0;

            $view->with('notifCounts', $notifCounts)
                ->with('revisiPendingCounts', $revisiPendingCounts)
                ->with('notifikasiTerbaru', $user
                    ? Notifikasi::forUser($user)->with(['tiket'])->latest()->limit(10)->get()
                    : collect());
        });
    }
}
