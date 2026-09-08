@extends('layouts.app')

@section('title', 'Daftar Pembayaran — Tahap Akhir')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="fw-bold mb-1">Pembayaran & Finalisasi (Stage 6)</h4>
        <p class="text-muted small mb-0">Konfirmasi pembayaran SPS / biaya layanan sebelum tiket dinyatakan SELESAI.</p>
    </div>
</div>

@include('partials.filter_tabs', ['route' => 'pembayaran.index', 'filter' => $filter ?? null])

<div class="card card-custom p-4">
    {{-- Search Bar --}}
    <div class="row g-2 mb-3">
        <div class="col-md-4">
            <form action="{{ route('pembayaran.index') }}" method="GET" class="input-group input-group-sm">
                @if(request('filter')) <input type="hidden" name="filter" value="{{ request('filter') }}"> @endif
                <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control bg-light" placeholder="Cari No Tiket / Nama Pemohon..." value="{{ request('search') }}">
                <button type="submit" class="btn btn-primary btn-sm">Cari</button>
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
            <thead class="table-light">
                <tr>
                    <th>No. Tiket</th>
                    <th>Pemohon</th>
                    <th>Jenis Layanan</th>
                    <th>Bidang</th>
                    <th>Status Pembayaran</th>
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
                            @if($t->status_pembayaran === 'lunas')
                                <span class="badge bg-success"><i class="bi bi-check-circle-fill"></i> Lunas</span>
                            @else
                                <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> Belum Lunas</span>
                            @endif
                            @if($t->jumlah_pembayaran)
                                <small class="d-block text-muted">Rp {{ number_format($t->jumlah_pembayaran, 0, ',', '.') }}</small>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="{{ route('pembayaran.show', $t->id) }}" class="btn btn-sm btn-primary px-3">
                                <i class="bi bi-credit-card-fill"></i> Bayar
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-credit-card fs-1 d-block mb-2 text-secondary"></i>
                            Tidak ada tiket yang menunggu pembayaran saat ini.
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
