<?php

namespace App\Http\Controllers;

use App\Models\Tiket;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    public function index(Request $request)
    {
        $tiket = null;
        $noTiket = $request->get('no_tiket');

        if ($noTiket) {
            $tiket = Tiket::with([
                'jenisPermohonan',
                'bidangTanahs',
                'riwayatStatuses' => function ($q) {
                    $q->orderBy('created_at', 'asc');
                }
            ])->where('no_tiket', trim($noTiket))->first();
        }

        return view('tracking.index', compact('tiket', 'noTiket'));
    }

    public function show($noTiket)
    {
        $tiket = Tiket::with([
            'jenisPermohonan',
            'bidangTanahs',
            'riwayatStatuses' => function ($q) {
                $q->orderBy('created_at', 'asc');
            }
        ])->where('no_tiket', urldecode($noTiket))->firstOrFail();

        return view('tracking.index', [
            'tiket' => $tiket,
            'noTiket' => $tiket->no_tiket
        ]);
    }
}
