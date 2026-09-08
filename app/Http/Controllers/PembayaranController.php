<?php

namespace App\Http\Controllers;

use App\Models\RiwayatStatus;
use App\Models\Tiket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PembayaranController extends Controller
{
    public function index(Request $request)
    {
        $query = Tiket::with(['jenisPermohonan', 'bidangTanahs', 'petugasPembayaran'])
            ->aktif()
            ->latest();

        $filter = $request->input('filter');
        if ($filter === 'lunas') {
            $query->where('status_pembayaran', 'lunas');
        } else {
            // Default: tiket yang menunggu pembayaran
            $query->where('status_alih_media', 'selesai')
                ->where('status_pembayaran', 'belum_lunas')
                ->where('status', '!=', 'batal');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('no_tiket', 'like', "%{$search}%")
                    ->orWhere('nama_pemohon', 'like', "%{$search}%");
            });
        }

        $tikets = $query->paginate(15)->withQueryString();
        return view('pembayaran.index', compact('tikets', 'filter'));
    }

    public function show($id)
    {
        $tiket = Tiket::with([
            'jenisPermohonan',
            'bidangTanahs',
            'petugasPembayaran',
            'activeRevisis',
        ])->aktif()->where('status_alih_media', 'selesai')
            ->where('status', '!=', 'batal')
            ->findOrFail($id);

        $petugasList = User::whereIn('role', ['pembayaran', 'admin'])->where('is_active', true)->get();

        return view('pembayaran.show', compact('tiket', 'petugasList'));
    }

    public function updatePembayaran(Request $request, $id)
    {
        $tiket = Tiket::findOrFail($id);

        $validated = $request->validate([
            'petugas_pembayaran_id' => 'required|exists:users,id',
            'action_type' => 'required|in:save_draft,confirm_lunas',
            'jumlah_pembayaran' => 'nullable|numeric|min:0',
            'tanggal_pembayaran' => 'nullable|date',
            'catatan_pembayaran' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($tiket, $validated) {
            $today = Carbon::today();

            if ($validated['action_type'] === 'confirm_lunas') {
                $tiket->update([
                    'status_pembayaran' => 'lunas',
                    'jumlah_pembayaran' => $validated['jumlah_pembayaran'] ?? 0,
                    'tanggal_pembayaran' => $validated['tanggal_pembayaran'] ?? $today,
                    'petugas_pembayaran_id' => $validated['petugas_pembayaran_id'],
                    'status' => 'selesai',
                    'tanggal_selesai' => $today,
                ]);

                // Tandai revisi aktif sebagai tertangani
                $tiket->activeRevisis()->update(['status' => 'tertangani']);

                RiwayatStatus::create([
                    'tiket_id' => $tiket->id,
                    'stage_dari' => 'Pembayaran',
                    'stage_ke' => 'SELESAI (Pembayaran Lunas)',
                    'changed_by' => Auth::id(),
                    'keterangan' => 'Pembayaran dikonfirmasi LUNAS. Tiket telah selesai.',
                ]);

                return redirect()->route('pembayaran.index')
                    ->with('success', "Pembayaran Tiket {$tiket->no_tiket} dikonfirmasi LUNAS. Tiket SELESAI.");

            } else {
                $tiket->update([
                    'petugas_pembayaran_id' => $validated['petugas_pembayaran_id'],
                    'jumlah_pembayaran' => $validated['jumlah_pembayaran'] ?? $tiket->jumlah_pembayaran,
                    'tanggal_pembayaran' => $validated['tanggal_pembayaran'] ?? $tiket->tanggal_pembayaran,
                ]);

                return redirect()->back()->with('success', 'Draf pembayaran berhasil disimpan.');
            }
        });
    }
}
