<?php

namespace App\Http\Controllers;

use App\Models\JenisPermohonan;
use App\Models\Tiket;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::today()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::today()->format('Y-m-d'));
        $jenisPermohonanId = $request->input('jenis_permohonan_id');
        $status = $request->input('status');

        // Seluruh tiket (aktif + arsip) untuk laporan periode/kinerja
        $query = Tiket::with(['jenisPermohonan', 'bidangTanahs', 'petugasLoket'])
            ->whereDate('tanggal_masuk', '>=', $startDate)
            ->whereDate('tanggal_masuk', '<=', $endDate)
            ->latest('tanggal_masuk');

        if ($jenisPermohonanId) {
            $query->where('jenis_permohonan_id', $jenisPermohonanId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $tikets = $query->get();

        $jenisPermohonans = JenisPermohonan::all();

        $stats = [
            'total' => $tikets->count(),
            'selesai' => $tikets->where('status', 'selesai')->count(),
            'proses' => $tikets->whereNotIn('status', ['selesai', 'batal', 'dikembalikan'])->count(),
            'dikembalikan' => $tikets->where('status', 'dikembalikan')->count(),
            'batal' => $tikets->where('status', 'batal')->count(),
        ];

        return view('reports.index', compact('tikets', 'jenisPermohonans', 'startDate', 'endDate', 'jenisPermohonanId', 'status', 'stats'));
    }

    public function print(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::today()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::today()->format('Y-m-d'));
        $jenisPermohonanId = $request->input('jenis_permohonan_id');
        $status = $request->input('status');

        $query = Tiket::with(['jenisPermohonan', 'bidangTanahs', 'petugasLoket'])
            ->whereDate('tanggal_masuk', '>=', $startDate)
            ->whereDate('tanggal_masuk', '<=', $endDate)
            ->latest('tanggal_masuk');

        if ($jenisPermohonanId) {
            $query->where('jenis_permohonan_id', $jenisPermohonanId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $tikets = $query->get();
        $jenisPermohonan = $jenisPermohonanId ? JenisPermohonan::find($jenisPermohonanId) : null;

        return view('reports.print', compact('tikets', 'startDate', 'endDate', 'jenisPermohonan', 'status'));
    }
}
