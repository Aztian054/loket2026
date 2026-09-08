<?php

namespace App\Http\Controllers;

use App\Models\JenisPermohonan;
use App\Models\Tiket;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function tracking(string $noTiket): JsonResponse
    {
        $tiket = Tiket::with([
            'jenisPermohonan',
            'bidangTanahs',
            'riwayatStatuses' => function ($q) {
                $q->orderBy('created_at', 'asc');
            }
        ])->where('no_tiket', urldecode($noTiket))->first();

        if (!$tiket) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor tiket tidak ditemukan dalam sistem.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'no_tiket' => $tiket->no_tiket,
                'status_pembetulan' => $tiket->status_pembetulan,
                'status' => $tiket->status,
                'status_label' => $tiket->status_label,
                'status_badge' => $tiket->status_badge,
                'tanggal_masuk' => $tiket->tanggal_masuk->format('d/m/Y'),
                'tanggal_target_selesai' => $tiket->tanggal_target_selesai?->format('d/m/Y'),
                'tanggal_selesai' => $tiket->tanggal_selesai?->format('d/m/Y'),
                'nama_pemohon' => $tiket->nama_pemohon,
                'jenis_permohonan' => [
                    'kode' => $tiket->jenisPermohonan->kode,
                    'nama' => $tiket->jenisPermohonan->nama,
                    'kategori' => $tiket->jenisPermohonan->kategori,
                    'sla_hari' => $tiket->jenisPermohonan->batas_hari_sla,
                ],
                'jumlah_bidang' => $tiket->jumlah_bidang,
                'bidang_tanah' => $tiket->bidangTanahs->map(function ($b) {
                    return [
                        'nib' => $b->nib,
                        'no_sertifikat_lama' => $b->no_sertifikat_lama,
                        'no_sertifikat_elektronik' => $b->no_sertifikat_elektronik,
                        'jenis_hak' => $b->jenis_hak,
                        'nama_pemegang_hak' => $b->nama_pemegang_hak,
                        'luas_m2' => $b->luas_m2,
                        'desa_kelurahan' => $b->desa_kelurahan,
                        'kecamatan' => $b->kecamatan,
                    ];
                }),
                'riwayat_timeline' => $tiket->riwayatStatuses->map(function ($r) {
                    return [
                        'stage_dari' => $r->stage_dari,
                        'stage_ke' => $r->stage_ke,
                        'keterangan' => $r->keterangan,
                        'waktu' => $r->created_at->format('d/m/Y H:i:s'),
                    ];
                }),
            ]
        ]);
    }

    public function stats(): JsonResponse
    {
        $today = Carbon::today();

        return response()->json([
            'success' => true,
            'data' => [
                'total_tiket' => Tiket::aktif()->count(),
                'tiket_hari_ini' => Tiket::aktif()->whereDate('tanggal_masuk', $today)->count(),
                'tiket_selesai' => Tiket::aktif()->where('status', 'selesai')->count(),
                'stage_counts' => [
                    'diterima' => Tiket::aktif()->where('status', 'diterima')->count(),
                    'verifikasi' => Tiket::aktif()->where('status', 'verifikasi')->count(),
                    'warkah' => Tiket::aktif()->where('status', 'warkah')->count(),
                    'validasi' => Tiket::aktif()->where('status', 'validasi')->count(),
                    'alih_media' => Tiket::aktif()->where('status', 'alih_media')->count(),
                    'selesai' => Tiket::aktif()->where('status', 'selesai')->count(),
                    'dikembalikan' => Tiket::aktif()->where('status', 'dikembalikan')->count(),
                    'batal' => Tiket::aktif()->where('status', 'batal')->count(),
                ],
                'overdue_sla' => Tiket::aktif()->whereNotIn('status', ['selesai', 'batal'])
                    ->whereNotNull('tanggal_target_selesai')
                    ->whereDate('tanggal_target_selesai', '<', $today)
                    ->count(),
            ]
        ]);
    }

    public function jenisPermohonan(): JsonResponse
    {
        $data = JenisPermohonan::where('is_active', true)
            ->with('persyaratanDokumens')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
