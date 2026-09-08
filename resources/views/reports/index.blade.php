@extends('layouts.app')

@section('title', 'Laporan & Rekapitulasi Pelayanan')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="fw-bold mb-1">Laporan & Rekapitulasi Pelayanan</h4>
        <p class="text-muted small mb-0">Monitoring kinerja penyelesaian berkas permohonan dan evaluasi SLA.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('reports.print', request()->all()) }}" target="_blank" class="btn btn-outline-secondary btn-sm rounded-3">
            <i class="bi bi-printer me-1"></i> Cetak Rekap Laporan
        </a>
    </div>
</div>

<!-- Filter Card -->
<div class="card card-custom p-4 mb-4">
    <form action="{{ route('reports.index') }}" method="GET" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-bold">Tanggal Awal</label>
            <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}">
        </div>

        <div class="col-md-3">
            <label class="form-label small fw-bold">Tanggal Akhir</label>
            <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}">
        </div>

        <div class="col-md-3">
            <label class="form-label small fw-bold">Jenis Layanan</label>
            <select name="jenis_permohonan_id" class="form-select form-select-sm">
                <option value="">-- Semua Jenis Layanan --</option>
                @foreach($jenisPermohonans as $jp)
                    <option value="{{ $jp->id }}" {{ $jenisPermohonanId == $jp->id ? 'selected' : '' }}>
                        {{ $jp->kode }} - {{ $jp->nama }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search me-1"></i> Tampilkan Data</button>
            <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card card-custom p-3 border-start border-4 border-primary text-center">
            <div class="text-muted small fw-bold text-uppercase">Total Masuk</div>
            <h3 class="fw-bold my-1 text-dark">{{ $stats['total'] }}</h3>
            <small class="text-muted">Periode Terpilih</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom p-3 border-start border-4 border-success text-center">
            <div class="text-muted small fw-bold text-uppercase">Selesai (Sertifikat El)</div>
            <h3 class="fw-bold my-1 text-success">{{ $stats['selesai'] }}</h3>
            <small class="text-success fw-semibold">Penyelesaian: {{ $stats['total'] > 0 ? round(($stats['selesai'] / $stats['total']) * 100, 1) : 0 }}%</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom p-3 border-start border-4 border-warning text-center">
            <div class="text-muted small fw-bold text-uppercase">Dalam Proses</div>
            <h3 class="fw-bold my-1 text-warning">{{ $stats['proses'] }}</h3>
            <small class="text-muted">Di berbagai stage</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom p-3 border-start border-4 border-danger text-center">
            <div class="text-muted small fw-bold text-uppercase">Perbaikan / Batal</div>
            <h3 class="fw-bold my-1 text-danger">{{ $stats['dikembalikan'] + $stats['batal'] }}</h3>
            <small class="text-muted">{{ $stats['dikembalikan'] }} Perbaikan, {{ $stats['batal'] }} Batal</small>
        </div>
    </div>
</div>

<!-- Table Rekap -->
<div class="card card-custom p-4">
    <div class="table-responsive">
        <table class="table table-hover table-bordered align-middle small mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 40px;">No</th>
                    <th>No. Tiket</th>
                    <th>Tanggal Masuk</th>
                    <th>Pemohon</th>
                    <th>Jenis Permohonan</th>
                    <th>Jumlah Bidang</th>
                    <th>Target SLA</th>
                    <th>Status Akhir</th>
                    <th>Petugas Loket</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tikets as $idx => $t)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>
                            <a href="{{ route('loket.show', $t->id) }}" class="fw-bold text-dark text-decoration-none">
                                {{ $t->no_tiket }}
                            </a>
                        </td>
                        <td>{{ $t->tanggal_masuk->format('d/m/Y') }}</td>
                        <td>
                            <div class="fw-bold">{{ $t->nama_pemohon }}</div>
                        </td>
                        <td>{{ $t->jenisPermohonan->kode }} - {{ $t->jenisPermohonan->nama }}</td>
                        <td class="text-center">{{ $t->bidangTanahs->count() }} Bidang</td>
                        <td>{{ $t->tanggal_target_selesai?->format('d/m/Y') ?? '-' }}</td>
                        <td>
                            <span class="badge bg-{{ $t->status_badge }}">{{ $t->status_label }}</span>
                        </td>
                        <td>{{ $t->petugasLoket->name ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            Tidak ada data permohonan pada rentang tanggal yang dipilih.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
