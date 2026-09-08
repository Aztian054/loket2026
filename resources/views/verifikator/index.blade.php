@extends('layouts.app')

@section('title', 'Daftar Berkas Masuk — Verifikator')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="fw-bold mb-1">Pemeriksaan & Verifikasi Berkas (Stage 2)</h4>
        <p class="text-muted small mb-0">Verifikasi data asli pemohon — berjalan paralel dengan Warkah (data salinan BPN).</p>
    </div>
</div>

@include('partials.filter_tabs', ['route' => 'verifikator.index', 'filter' => $filter ?? null])

<!-- Search Bar -->
<div class="card card-custom p-3 mb-4">
    <form action="{{ route('verifikator.index') }}" method="GET" class="row g-2 align-items-end">
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

<!-- Table Card -->
<div class="card card-custom p-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
            <thead class="table-light">
                <tr>
                    <th>No. Tiket & Tanggal</th>
                    <th>Pemohon / Satker</th>
                    <th>Jenis Permohonan</th>
                    <th>Bidang</th>
                    <th>Status Berkas</th>
                    <th>Verifikator</th>
                    <th class="text-center" style="width: 140px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tikets as $t)
                    <tr>
                        <td>
                            <div class="fw-bold text-dark">{{ $t->no_tiket }}</div>
                            <small class="text-muted">{{ $t->tanggal_masuk->format('d/m/Y') }}</small>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $t->nama_pemohon }}</div>
                            <small class="text-muted">{{ $t->no_hp_pemohon }}</small>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $t->jenisPermohonan->kode }}</span>
                            <div class="small">{{ $t->jenisPermohonan->nama }}</div>
                        </td>
                        <td><span class="badge bg-secondary">{{ $t->bidangTanahs->count() }} Bidang</span></td>
                        <td>
                            @if($t->status_verifikator === 'selesai')
                                <span class="badge bg-success">Verifikasi Selesai</span>
                            @elseif($t->status_verifikator === 'revisi_ke_loket')
                                <span class="badge bg-danger">Revisi ke Loket</span>
                            @elseif($t->status_verifikator === 'tertunda')
                                <span class="badge bg-warning text-dark">Tertunda</span>
                            @else
                                <span class="badge bg-info">Diproses</span>
                            @endif
                            @if($t->status_verifikator === 'revisi_ke_loket')
                                <small class="d-block text-danger">Perlu perbaikan pemohon</small>
                            @else
                                <small class="d-block text-muted">Warkah: {{ \App\Models\Tiket::labelStatusStage($t->status_warkah) }}</small>
                            @endif
                            @if($t->status_pembetulan !== 'P0')
                                <span class="badge bg-warning text-dark">{{ $t->status_pembetulan }}</span>
                            @endif
                        </td>
                        <td>{{ $t->latestVerifikasi?->verifikator->name ?? 'Belum Diplot' }}</td>
                        <td class="text-center">
                            <a href="{{ route('verifikator.show', $t->id) }}" class="btn btn-sm btn-primary px-3">
                                <i class="bi bi-clipboard-check"></i> Periksa
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-check-all fs-1 d-block mb-2 text-success"></i>
                            Tidak ada berkas yang menunggu verifikasi saat ini.
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
