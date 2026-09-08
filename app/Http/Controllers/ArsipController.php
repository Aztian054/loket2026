<?php

namespace App\Http\Controllers;

use App\Models\RiwayatStatus;
use App\Models\Tiket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ArsipController extends Controller
{
    /**
     * Izin TULIS (arsip/restore) hanya untuk admin. Pimpinan read-only.
     */
    private function authorizeWrite(): void
    {
        abort_unless(Auth::check() && Auth::user()->role === 'admin', 403, 'Hanya administrator yang dapat mengarsipkan tiket.');
    }

    /**
     * Daftar tiket arsip + panel tiket siap arsip (khusus admin).
     */
    public function index(Request $request)
    {
        $tahun = $request->input('tahun');
        $search = $request->input('search');

        $query = Tiket::with(['jenisPermohonan', 'diarsipkanOleh'])
            ->arsip()
            ->latest('diarsipkan_pada');

        if ($tahun) {
            $query->where('tahun', $tahun);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('no_tiket', 'like', "%{$search}%")
                    ->orWhere('nama_pemohon', 'like', "%{$search}%");
            });
        }

        $arsips = $query->paginate(15)->withQueryString();

        // Statistik per tahun
        $statistikPerTahun = Tiket::arsip()
            ->selectRaw("tahun, COUNT(*) as total, SUM(status = 'selesai') as selesai, SUM(status = 'batal') as batal")
            ->groupBy('tahun')
            ->orderByDesc('tahun')
            ->get();

        $totalArsip = Tiket::arsip()->count();
        $siapArsip = Tiket::siapArsip()->count();
        $tahunTersedia = Tiket::arsip()
            ->select('tahun')
            ->distinct()
            ->orderBy('tahun', 'desc')
            ->pluck('tahun');

        $isPimpinan = Auth::user()->role === 'pimpinan';

        $siapArsipList = !$isPimpinan
            ? Tiket::siapArsip()
                ->with(['jenisPermohonan'])
                ->latest('tanggal_selesai')
                ->take(100)
                ->get()
            : collect();

        $tahunSiapArsip = Tiket::siapArsip()
            ->select('tahun')
            ->distinct()
            ->orderBy('tahun')
            ->pluck('tahun');

        return view('arsip.index', compact(
            'arsips',
            'totalArsip',
            'siapArsip',
            'tahunTersedia',
            'tahun',
            'search',
            'siapArsipList',
            'tahunSiapArsip',
            'statistikPerTahun',
            'isPimpinan'
        ));
    }

    /**
     * Detail tiket arsip.
     *
     * @param  int  $id  ID tiket arsip.
     */
    public function show($id)
    {
        $tiket = Tiket::with([
            'jenisPermohonan',
            'bidangTanahs',
            'diarsipkanOleh',
            'petugasLoket',
            'riwayatStatuses.user',
        ])->arsip()->findOrFail($id);

        return view('arsip.show', compact('tiket'));
    }

    /**
     * Arsipkan satu tiket (status selesai / batal).
     *
     * @param  int  $id  ID tiket.
     */
    public function arsipkan(Request $request, $id)
    {
        $this->authorizeWrite();

        /** @var \App\Models\User $actor */
        $actor = Auth::user();

        $validated = $request->validate([
            'periode' => 'nullable|string|max:20',
            'sumber_data' => 'nullable|string|max:50',
        ]);

        $tiket = Tiket::siapArsip()->findOrFail($id);

        DB::transaction(function () use ($tiket, $validated, $actor) {
            $tiket->update([
                'periode' => $validated['periode'] ?? $tiket->tahun,
                'sumber_data' => $validated['sumber_data'] ?? 'sistem_loket',
                'diarsipkan_pada' => now(),
                'diarsipkan_oleh' => Auth::id(),
            ]);

            RiwayatStatus::create([
                'tiket_id' => $tiket->id,
                'stage_dari' => 'Aktif (' . strtoupper($tiket->status) . ')',
                'stage_ke' => 'Arsip Tahunan ' . ($tiket->tahun ?? '-'),
                'changed_by' => Auth::id(),
                'keterangan' => 'Tiket dipindahkan ke arsip tahunan oleh ' . $actor->name . '.',
            ]);
        });

        return redirect()->route('arsip.index')
            ->with('success', "Tiket {$tiket->no_tiket} berhasil diarsipkan.");
    }

    /**
     * Arsipkan banyak tiket sekaligus berdasarkan checkbox.
     */
    public function arsipkanMassal(Request $request)
    {
        $this->authorizeWrite();

        /** @var \App\Models\User $actor */
        $actor = Auth::user();

        $validated = $request->validate([
            'tiket_ids' => 'required|array|min:1',
            'tiket_ids.*' => 'integer|exists:tikets,id',
        ]);

        $count = 0;

        DB::transaction(function () use ($validated, $actor, &$count) {
            foreach ($validated['tiket_ids'] as $tiketId) {
                $tiket = Tiket::siapArsip()->find($tiketId);
                if (!$tiket) {
                    continue;
                }

                $tiket->update([
                    'periode' => $tiket->tahun,
                    'sumber_data' => 'sistem_loket',
                    'diarsipkan_pada' => now(),
                    'diarsipkan_oleh' => Auth::id(),
                ]);

                RiwayatStatus::create([
                    'tiket_id' => $tiket->id,
                    'stage_dari' => 'Aktif (' . strtoupper($tiket->status) . ')',
                    'stage_ke' => 'Arsip Tahunan ' . ($tiket->tahun ?? '-'),
                    'changed_by' => Auth::id(),
                    'keterangan' => 'Arsip massal oleh ' . $actor->name . '.',
                ]);

                $count++;
            }
        });

        return redirect()->route('arsip.index')->with('success', "Berhasil mengarsipkan {$count} tiket.");
    }

    /**
     * Arsipkan seluruh tiket selesai/batal pada satu tahun.
     *
     * @param  int  $tahun  Tahun arsip.
     */
    public function arsipkanMassalTahun(Request $request, $tahun)
    {
        $this->authorizeWrite();

        /** @var \App\Models\User $actor */
        $actor = Auth::user();

        $request->validate(['konfirmasi' => 'required|in:yes']);

        $count = 0;

        DB::transaction(function () use ($tahun, $actor, &$count) {
            $siapArsip = Tiket::siapArsip()->where('tahun', $tahun)->get();

            foreach ($siapArsip as $tiket) {
                $tiket->update([
                    'periode' => $tahun,
                    'sumber_data' => 'sistem_loket',
                    'diarsipkan_pada' => now(),
                    'diarsipkan_oleh' => Auth::id(),
                ]);

                RiwayatStatus::create([
                    'tiket_id' => $tiket->id,
                    'stage_dari' => 'Aktif (' . strtoupper($tiket->status) . ')',
                    'stage_ke' => 'Arsip Tahunan ' . $tahun,
                    'changed_by' => Auth::id(),
                    'keterangan' => 'Arsip massal tahun ' . $tahun . ' oleh ' . $actor->name . '.',
                ]);

                $count++;
            }
        });

        return redirect()->route('arsip.index')
            ->with('success', "Berhasil mengarsipkan {$count} tiket tahun {$tahun}.");
    }

    /**
     * Restore tiket arsip kembali ke daftar aktif.
     *
     * @param  int  $id  ID tiket arsip.
     */
    public function restore(Request $request, $id)
    {
        $this->authorizeWrite();

        /** @var \App\Models\User $actor */
        $actor = Auth::user();

        $tiket = Tiket::arsip()->findOrFail($id);

        DB::transaction(function () use ($tiket, $actor) {
            $tahun = $tiket->tahun;

            $tiket->update([
                'periode' => null,
                'diarsipkan_pada' => null,
                'diarsipkan_oleh' => null,
            ]);

            RiwayatStatus::create([
                'tiket_id' => $tiket->id,
                'stage_dari' => 'Arsip Tahunan ' . ($tahun ?? '-'),
                'stage_ke' => 'Aktif (Selesai/Batal)',
                'changed_by' => Auth::id(),
                'keterangan' => 'Tiket dikembalikan dari arsip oleh ' . $actor->name . '.',
            ]);
        });

        return redirect()->route('arsip.index')
            ->with('success', "Tiket {$tiket->no_tiket} dikembalikan ke daftar aktif.");
    }
}