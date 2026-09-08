<?php

namespace App\Http\Controllers;

use App\Models\RiwayatStatus;
use App\Models\Tiket;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today();

        // Statistics
        $totalTiket = Tiket::aktif()->count();
        $tiketHariIni = Tiket::aktif()->whereDate('tanggal_masuk', $today)->count();
        $tiketSelesai = Tiket::aktif()->where('status', 'selesai')->count();
        $tiketDikembalikan = Tiket::aktif()->where('status', 'dikembalikan')->count();

        // Cross-stage indicators (parallel flag-based)
        $stageCounts = [
            'diterima' => Tiket::aktif()->where('status', 'diterima')->count(),
            'verifikasi' => Tiket::aktif()->whereIn('status_verifikator', ['proses', 'tertunda', 'revisi_ke_loket'])->count(),
            'warkah' => Tiket::aktif()->whereIn('status_warkah', ['proses', 'revisi'])->count(),
            'validasi' => Tiket::aktif()->where('status', 'validasi')->count(),
            'alih_media' => Tiket::aktif()->where('status', 'alih_media')->count(),
            'selesai' => $tiketSelesai,
            'dikembalikan' => $tiketDikembalikan,
            'batal' => Tiket::aktif()->where('status', 'batal')->count(),
        ];

        // Revisi active di setiap stage (notification badges)
        $revisiCounts = [
            'verifikator' => Tiket::aktif()->where('status_verifikator', 'revisi_ke_loket')->count(),
            'warkah' => Tiket::aktif()->where('status_warkah', 'revisi')->count(),
            'validator' => Tiket::aktif()
                ->whereHas('tiketRevisis', fn ($q) => $q->where('stage_tujuan', 'Validator')->where('status', 'aktif'))
                ->count(),
        ];

        // Overdue / Melewati SLA
        $overdueCount = Tiket::aktif()->whereNotIn('status', ['selesai', 'batal'])
            ->whereNotNull('tanggal_target_selesai')
            ->whereDate('tanggal_target_selesai', '<', $today)
            ->count();

        // Tiket terbaru sesuai hak akses atau umum (menggunakan flag paralel)
        $recentQuery = Tiket::aktif()->with(['jenisPermohonan', 'petugasLoket', 'bidangTanahs'])->latest();

        if ($user->role === 'loket') {
            $recentQuery->where('status', 'diterima');
        } elseif ($user->role === 'verifikator') {
            $recentQuery->whereIn('status_verifikator', ['proses', 'tertunda', 'revisi_ke_loket']);
        } elseif ($user->role === 'warkah') {
            $recentQuery->whereIn('status_warkah', ['proses', 'revisi']);
        } elseif (in_array($user->role, ['validator', 'validator_btel', 'validator_suel'])) {
            $recentQuery->where('status', 'validasi');
        } elseif (in_array($user->role, ['alih_media', 'alih_media_btel', 'alih_media_suel'])) {
            $recentQuery->where('status', 'alih_media');
        } elseif ($user->role === 'pembayaran') {
            $recentQuery->where('status_alih_media', 'selesai')->where('status_pembayaran', 'belum_lunas');
        }

        $recentTikets = $recentQuery->take(8)->get();

        // Recent Audit Trail (khusus tiket aktif, tidak menampilkan aktivitas arsip)
        $recentActivities = RiwayatStatus::with(['tiket', 'user'])
            ->whereHas('tiket', fn ($q) => $q->aktif())
            ->latest()
            ->take(6)
            ->get();

        return view('dashboard.index', compact(
            'totalTiket',
            'tiketHariIni',
            'tiketSelesai',
            'tiketDikembalikan',
            'stageCounts',
            'revisiCounts',
            'overdueCount',
            'recentTikets',
            'recentActivities'
        ));
    }
}
