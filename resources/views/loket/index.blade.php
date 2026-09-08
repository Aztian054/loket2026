@extends('layouts.app')

@section('title', 'Daftar Tiket Permohonan — Loket')

@section('content')
{{-- Filter context banner when arriving from sidebar submenu --}}
@if(request()->filled('status'))
    @php
        $statusMap = [
            'dikembalikan'                      => ['label' => 'Revisi', 'desc' => 'Tiket yang dikembalikan oleh Verifikator', 'color' => 'danger'],
            'verifikasi,warkah,validasi,alih_media,selesai' => ['label' => 'Selesai', 'desc' => 'Tiket yang sudah melewati Loket', 'color' => 'success'],
        ];
        $filterInfo = $statusMap[request('status')] ?? null;
    @endphp
    @if($filterInfo)
        <div class="alert alert-{{ $filterInfo['color'] }} border-0 d-flex align-items-center mb-3 shadow-sm" style="border-radius: 12px;">
            <i class="bi bi-funnel-fill fs-5 me-3"></i>
            <div>
                <strong>{{ $filterInfo['label'] }}</strong> — {{ $filterInfo['desc'] }}
            </div>
            <a href="{{ route('loket.index') }}" class="btn btn-sm btn-outline-{{ $filterInfo['color'] }} ms-auto">Tampilkan Semua</a>
        </div>
    @endif
@endif

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="fw-bold mb-1">Daftar Permohonan Berkas</h4>
        <p class="text-muted small mb-0">Kelola dan pantau seluruh tiket permohonan pelayanan pertanahan.</p>
    </div>
    <a href="{{ route('loket.create') }}" class="btn btn-gold rounded-3 shadow-sm">
        <i class="bi bi-plus-circle-fill me-1"></i> Registrasi Tiket Baru
    </a>
</div>

<!-- Filter Card -->
<div class="card card-custom p-3 mb-4">
    <form action="{{ route('loket.index') }}" method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label small fw-semibold text-muted">Cari Permohonan</label>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control bg-light" placeholder="No Tiket, Nama Pemohon, NIK, HP..." value="{{ request('search') }}">
            </div>
        </div>

        <div class="col-md-3">
            <label class="form-label small fw-semibold text-muted">Filter Status</label>
            <select name="status" class="form-select form-select-sm bg-light">
                <option value="">-- Semua Status --</option>
                <option value="diterima" {{ request('status') === 'diterima' ? 'selected' : '' }}>Diterima di Loket</option>
                <option value="verifikasi" {{ request('status') === 'verifikasi' ? 'selected' : '' }}>Pemeriksaan Verifikator</option>
                <option value="warkah" {{ request('status') === 'warkah' ? 'selected' : '' }}>Pencarian Warkah</option>
                <option value="validasi" {{ request('status') === 'validasi' ? 'selected' : '' }}>Validasi Data</option>
                <option value="alih_media" {{ request('status') === 'alih_media' ? 'selected' : '' }}>Alih Media</option>
                <option value="selesai" {{ request('status') === 'selesai' ? 'selected' : '' }}>Selesai</option>
                <option value="dikembalikan" {{ request('status') === 'dikembalikan' ? 'selected' : '' }}>Dikembalikan</option>
                <option value="batal" {{ request('status') === 'batal' ? 'selected' : '' }}>Dibatalkan</option>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label small fw-semibold text-muted">Jenis Layanan</label>
            <select name="jenis_permohonan_id" class="form-select form-select-sm bg-light">
                <option value="">-- Semua Layanan --</option>
                @foreach($jenisPermohonans as $jp)
                    <option value="{{ $jp->id }}" {{ request('jenis_permohonan_id') == $jp->id ? 'selected' : '' }}>
                        {{ $jp->kode }} - {{ $jp->nama }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-filter"></i> Filter</button>
            <a href="{{ route('loket.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</div>

<!-- Table Card -->
<div class="card card-custom p-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>No. Tiket & Tanggal</th>
                    <th>Pemohon</th>
                    <th>Jenis Permohonan</th>
                    <th>Bidang</th>
                    <th>Target SLA</th>
                    <th>Status Berkas</th>
                    <th class="text-center" style="width: 140px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tikets as $index => $t)
                    <tr>
                        <td>{{ $tikets->firstItem() + $index }}</td>
                        <td>
                            <a href="{{ route('loket.show', $t->id) }}" class="fw-bold text-dark text-decoration-none">
                                {{ $t->no_tiket }}
                            </a>
                            <div class="text-muted small">{{ $t->tanggal_masuk->format('d/m/Y') }}</div>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $t->nama_pemohon }}</div>
                            <small class="text-muted">{{ $t->no_hp_pemohon }}</small>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $t->jenisPermohonan->kode }}</span>
                            <div class="small text-truncate" style="max-width: 220px;" title="{{ $t->jenisPermohonan->nama }}">
                                {{ $t->jenisPermohonan->nama }}
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-secondary">{{ $t->jumlah_bidang }} Bidang</span>
                        </td>
                        <td>
                            @if($t->tanggal_target_selesai)
                                @if($t->status !== 'selesai' && $t->status !== 'batal' && now()->startOfDay()->gt($t->tanggal_target_selesai))
                                    <span class="text-danger fw-bold small"><i class="bi bi-exclamation-circle-fill"></i> {{ $t->tanggal_target_selesai->format('d/m/Y') }} (Lewat)</span>
                                @else
                                    <span class="small text-muted">{{ $t->tanggal_target_selesai->format('d/m/Y') }}</span>
                                @endif
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $t->status_badge }}">{{ $t->status_label }}</span>
                            @if($t->status_pembetulan !== 'P0')
                                <span class="badge bg-warning text-dark">{{ $t->status_pembetulan }}</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('loket.show', $t->id) }}" class="btn btn-outline-primary" title="Lihat Detail">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('loket.printReceipt', $t->id) }}" target="_blank" class="btn btn-outline-secondary" title="Cetak Tanda Terima">
                                    <i class="bi bi-printer"></i>
                                </a>
                            </div>
                            @if($t->status === 'diterima' && !$t->status_verifikator && !$t->status_warkah)
                                <form action="{{ route('loket.forward', $t->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-gold mt-1" title="Kirim otomatis ke Verifikator & Warkah (paralel)" onclick="return confirm('Kirim tiket {{ $t->no_tiket }}? Berkas otomatis diteruskan ke Verifikator DAN Warkah secara paralel.')">
                                        <i class="bi bi-send-check-fill me-1"></i> Kirim
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                            Tidak ada data tiket permohonan yang ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $tikets->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection
