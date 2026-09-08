@extends('layouts.app')

@section('title', 'Lembar Kerja Validator — Stage 4')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="fw-bold mb-1">Validasi Data Pertanahan & KKP (Stage 4)</h4>
        <p class="text-muted small mb-0">Validasi paralel Pra-BTel & Pra-SuEl per bidang. Tiket hanya diteruskan ke Alih Media jika SEMUA bidang LULUS.</p>
    </div>
</div>

@include('partials.filter_tabs', ['route' => 'validator.index', 'filter' => $filter ?? null])

<!-- Search Bar -->
<div class="card card-custom p-3 mb-4">
    <form action="{{ route('validator.index') }}" method="GET" class="row g-2 align-items-end">
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
                    <th>Progress Validasi</th>
                    <th>Validator</th>
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
                                $lulusCount = $t->lembarKerjaValidasis->where('status_validasi_bidang', 'lulus')->count();
                                $totalBidang = max($t->bidangTanahs->count(), 1);
                            @endphp
                            <span class="badge bg-{{ $lulusCount === $totalBidang ? 'success' : 'info' }}">
                                {{ $lulusCount }}/{{ $totalBidang }} LULUS
                            </span>
                            <small class="d-block mt-1 text-muted">Pra-BTel &amp; Pra-SuEl</small>
                        </td>
                        <td>{{ $t->lembarKerjaValidasis->first()?->validator->name ?? 'Belum Diplot' }}</td>
                        <td class="text-center">
                            <a href="{{ route('validator.show', $t->id) }}" class="btn btn-sm btn-primary px-3">
                                <i class="bi bi-shield-check"></i> Validasi
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-shield-slash fs-1 d-block mb-2 text-secondary"></i>
                            Tidak ada berkas yang menunggu validasi saat ini.
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
