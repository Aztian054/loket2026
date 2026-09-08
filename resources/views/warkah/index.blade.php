@extends('layouts.app')

@section('title', 'Lembar Kerja Warkah — Stage 3')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="fw-bold mb-1">Lembar Kerja Warkah (Stage 2B)</h4>
        <p class="text-muted small mb-0">Pencarian & pencatatan kondisi arsip warkah (data salinan BPN) — paralel dengan Verifikator. Setelah selesai, otomatis memicu Validator.</p>
    </div>
</div>

@include('partials.filter_tabs', ['route' => 'warkah.index', 'filter' => $filter ?? null])

<!-- Search Bar -->
<div class="card card-custom p-3 mb-4">
    <form action="{{ route('warkah.index') }}" method="GET" class="row g-2 align-items-end">
        @if(request('filter')) <input type="hidden" name="filter" value="{{ request('filter') }}"> @endif
        <div class="col-md-5">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control bg-light" placeholder="Cari No Tiket / Nama Pemohon..." value="{{ request('search') }}">
            </div>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search me-1"></i> Cari</button>
        </div>
    </form>
</div>

<div class="card card-custom p-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
            <thead class="table-light">
                <tr>
                    <th>No. Tiket</th>
                    <th>Pemohon</th>
                    <th>Jenis Layanan</th>
                    <th>Jumlah Bidang</th>
                    <th>Status Warkah</th>
                    <th class="text-center" style="width: 140px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tikets as $t)
                    <tr>
                        <td>
                            <span class="fw-bold text-dark">{{ $t->no_tiket }}</span>
                            <div class="text-muted small">{{ $t->tanggal_masuk->format('d/m/Y') }}</div>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $t->nama_pemohon }}</div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $t->jenisPermohonan->kode }}</span>
                            <small class="d-block">{{ $t->jenisPermohonan->nama }}</small>
                        </td>
                        <td><span class="badge bg-secondary">{{ $t->bidangTanahs->count() }} Bidang</span></td>
                        <td>
                            @php
                                $warkahs = $t->lembarKerjaWarkahs;
                                $adaCount = $warkahs->where('status_keberadaan', 'ada')->count();
                            @endphp
                            @if($t->status_warkah === 'selesai')
                                <span class="badge bg-success">Warkah Selesai</span>
                            @elseif($t->status_warkah === 'revisi')
                                <span class="badge bg-danger">Revisi</span>
                            @elseif($t->status_warkah === 'proses')
                                <span class="badge bg-info">Diproses</span>
                            @elseif($warkahs->isEmpty())
                                <span class="badge bg-secondary">Belum Diperiksa</span>
                            @else
                                <span class="badge bg-info text-dark">{{ $adaCount }} / {{ $t->bidangTanahs->count() }} Warkah Ada</span>
                            @endif
                            <small class="d-block text-muted">Verifikator: {{ \App\Models\Tiket::labelStatusStage($t->status_verifikator) }}</small>
                        </td>
                        <td class="text-center">
                            <a href="{{ route('warkah.show', $t->id) }}" class="btn btn-sm btn-primary px-3">
                                <i class="bi bi-archive-fill"></i> Lembar Kerja
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-folder-check fs-1 d-block mb-2 text-secondary"></i>
                            Tidak ada berkas di bagian Warkah saat ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $tikets->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection
