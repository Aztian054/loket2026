@extends('layouts.app')

@section('title', 'Lembar Kerja Alih Media — Stage 5')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="fw-bold mb-1">Alih Media & Sertifikat Elektronik (Stage 5)</h4>
        <p class="text-muted small mb-0">Digitalisasi dokumen warkah, upload KKP, TTD elektronik, dan penerbitan sertifikat elektronik. Finalisasi menandai tiket SELESAI.</p>
    </div>
</div>

@include('partials.filter_tabs', ['route' => 'alih_media.index', 'filter' => $filter ?? null])

<!-- Search Bar -->
<div class="card card-custom p-3 mb-4">
    <form action="{{ route('alih_media.index') }}" method="GET" class="row g-2 align-items-end">
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
                    <th>Bidang</th>
                    <th>Status Digitalisasi</th>
                    <th>Petugas</th>
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
                                $amStatuses = $t->lembarKerjaAlihMedias;
                                $btelDone = $amStatuses->where('status_btel', 'selesai')->count();
                                $suelDone = $amStatuses->where('status_suel', 'selesai')->count();
                                $totalBidang = max($t->bidangTanahs->count(), 1);
                            @endphp
                            @if($btelDone > 0 || $suelDone > 0)
                                <span class="badge bg-info text-dark">BTel: {{ $btelDone }}/{{ $totalBidang }}</span>
                                <span class="badge bg-info text-dark">SuEl: {{ $suelDone }}/{{ $totalBidang }}</span>
                            @else
                                <span class="badge bg-dark"><i class="bi bi-file-earmark-diff"></i> Siap Alih Media</span>
                            @endif
                        </td>
                        <td>{{ $t->lembarKerjaAlihMedias->first()?->petugas->name ?? 'Belum Diplot' }}</td>
                        <td class="text-center">
                            <a href="{{ route('alih_media.show', $t->id) }}" class="btn btn-sm btn-dark px-3">
                                <i class="bi bi-cpu-fill"></i> Proses Alih Media
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-file-earmark-check fs-1 d-block mb-2 text-secondary"></i>
                            Tidak ada berkas di bagian Alih Media saat ini.
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
