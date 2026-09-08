@extends('layouts.app')

@section('title', 'Dashboard Monitoring')

@section('content')
<div class="row g-3 mb-4">
    <div class="col-12 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h4 class="fw-bold mb-1">Dashboard Pelayanan</h4>
            <p class="text-muted small mb-0">Selamat datang, <strong>{{ auth()->user()->name }}</strong> (Peran: <span class="badge bg-secondary text-uppercase">{{ auth()->user()->role }}</span>)</p>
        </div>
        <div class="d-flex gap-2">
            @if(auth()->user()->isAdmin() || auth()->user()->role === 'loket')
                <a href="{{ route('loket.create') }}" class="btn btn-gold btn-sm rounded-3 px-3 shadow-sm">
                    <i class="bi bi-plus-circle me-1"></i> Input Tiket Baru
                </a>
            @endif
            <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm rounded-3 px-3">
                <i class="bi bi-printer me-1"></i> Rekap Laporan
            </a>
        </div>
    </div>
</div>

<!-- 4 Key Metrics -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card card-custom p-3 border-start border-4 border-primary">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Total Permohonan</div>
                    <h3 class="fw-bold my-1 text-dark">{{ number_format($totalTiket) }}</h3>
                    <span class="text-success small fw-semibold"><i class="bi bi-calendar-event"></i> Kumulatif Sistem</span>
                </div>
                <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle">
                    <i class="bi bi-folder-fill fs-3"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card card-custom p-3 border-start border-4 border-warning">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Masuk Hari Ini</div>
                    <h3 class="fw-bold my-1 text-dark">{{ number_format($tiketHariIni) }}</h3>
                    <span class="text-muted small"><i class="bi bi-clock"></i> Tanggal {{ now()->format('d/m/Y') }}</span>
                </div>
                <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-circle">
                    <i class="bi bi-inbox-fill fs-3"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card card-custom p-3 border-start border-4 border-success">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Selesai (Sertifikat El)</div>
                    <h3 class="fw-bold my-1 text-success">{{ number_format($tiketSelesai) }}</h3>
                    <span class="text-success small fw-semibold"><i class="bi bi-check-circle"></i> Sertifikat Terbit</span>
                </div>
                <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle">
                    <i class="bi bi-patch-check-fill fs-3"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card card-custom p-3 border-start border-4 border-danger">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Perhatian SLA / Lewat</div>
                    <h3 class="fw-bold my-1 text-danger">{{ number_format($overdueCount) }}</h3>
                    <span class="text-danger small fw-semibold"><i class="bi bi-exclamation-octagon"></i> Perlu Tindak Lanjut</span>
                </div>
                <div class="bg-danger bg-opacity-10 text-danger p-3 rounded-circle">
                    <i class="bi bi-hourglass-bottom fs-3"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 5-Stage Visual Workflow Pipeline -->
<div class="card card-custom p-4 mb-4">
    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-diagram-3-fill text-warning me-2"></i>Alur Proses Berkas Real-Time (5 Stage)</h6>
    <div class="row g-2 text-center">
        <!-- Stage 1: Loket -->
        <div class="col-6 col-md">
            <div class="p-3 rounded-3 bg-light border position-relative h-100">
                <span class="badge bg-warning text-dark mb-2">Stage 1</span>
                <div class="fw-bold text-dark mb-1">Loket</div>
                <h4 class="fw-bold text-warning mb-1">{{ $stageCounts['diterima'] }}</h4>
                <small class="text-muted d-block" style="font-size: 0.75rem;">Berkas Masuk</small>
            </div>
        </div>

        <!-- Stage 2: Verifikator -->
        <div class="col-6 col-md">
            <div class="p-3 rounded-3 bg-light border position-relative h-100">
                <span class="badge bg-info text-dark mb-2">Stage 2</span>
                <div class="fw-bold text-dark mb-1">Verifikator</div>
                <h4 class="fw-bold text-info mb-1">{{ $stageCounts['verifikasi'] }}</h4>
                <small class="text-muted d-block" style="font-size: 0.75rem;">Pemeriksaan Syarat</small>
            </div>
        </div>

        <!-- Stage 3: Warkah -->
        <div class="col-6 col-md">
            <div class="p-3 rounded-3 bg-light border position-relative h-100">
                <span class="badge bg-secondary mb-2">Stage 3</span>
                <div class="fw-bold text-dark mb-1">Warkah</div>
                <h4 class="fw-bold text-secondary mb-1">{{ $stageCounts['warkah'] }}</h4>
                <small class="text-muted d-block" style="font-size: 0.75rem;">Arsip Fisik</small>
            </div>
        </div>

        <!-- Stage 4: Validator -->
        <div class="col-6 col-md">
            <div class="p-3 rounded-3 bg-light border position-relative h-100">
                <span class="badge bg-primary mb-2">Stage 4</span>
                <div class="fw-bold text-dark mb-1">Validator</div>
                <h4 class="fw-bold text-primary mb-1">{{ $stageCounts['validasi'] }}</h4>
                <small class="text-muted d-block" style="font-size: 0.75rem;">Validasi KKP/SPS</small>
            </div>
        </div>

        <!-- Stage 5: Alih Media -->
        <div class="col-6 col-md">
            <div class="p-3 rounded-3 bg-light border position-relative h-100">
                <span class="badge bg-dark mb-2">Stage 5</span>
                <div class="fw-bold text-dark mb-1">Alih Media</div>
                <h4 class="fw-bold text-dark mb-1">{{ $stageCounts['alih_media'] }}</h4>
                <small class="text-muted d-block" style="font-size: 0.75rem;">Scan & TTD El</small>
            </div>
        </div>

        <!-- Stage: Selesai -->
        <div class="col-6 col-md">
            <div class="p-3 rounded-3 bg-light border position-relative h-100">
                <span class="badge bg-success mb-2">Final</span>
                <div class="fw-bold text-dark mb-1">Selesai</div>
                <h4 class="fw-bold text-success mb-1">{{ $stageCounts['selesai'] }}</h4>
                <small class="text-muted d-block" style="font-size: 0.75rem;">Terbit Sertifikat</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Tickets Table -->
    <div class="col-lg-8">
        <div class="card card-custom p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-clock-history me-2 text-primary"></i>Permohonan Terkini</h6>
                @if(auth()->user()->isAdmin() || auth()->user()->role === 'loket')
                    <a href="{{ route('loket.index') }}" class="btn btn-sm btn-link text-decoration-none">Lihat Semua &rarr;</a>
                @endif
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
                    <thead class="table-light">
                        <tr>
                            <th>No. Tiket</th>
                            <th>Pemohon & Satker</th>
                            <th>Layanan</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTikets as $t)
                            <tr>
                                <td>
                                    <span class="fw-bold text-dark">{{ $t->no_tiket }}</span>
                                    <div class="text-muted" style="font-size: 0.75rem;">{{ $t->tanggal_masuk->format('d/m/Y') }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $t->nama_pemohon }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $t->jenisPermohonan->kode }}</span>
                                    <small class="d-block text-truncate" style="max-width: 160px;" title="{{ $t->jenisPermohonan->nama }}">{{ $t->jenisPermohonan->nama }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $t->status_badge }}">{{ $t->status_label }}</span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('loket.show', $t->id) }}" class="btn btn-sm btn-outline-primary py-1 px-2">
                                        <i class="bi bi-eye"></i> Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">Belum ada data permohonan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Audit Trail / Log Aktivitas Terakhir -->
    <div class="col-lg-4">
        <div class="card card-custom p-4 h-100">
            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-activity text-warning me-2"></i>Aktivitas Sistem Terakhir</h6>
            <div class="list-group list-group-flush" style="font-size: 0.84rem;">
                @forelse($recentActivities as $act)
                    <div class="list-group-item px-0 py-2 border-bottom">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-bold text-dark">{{ $act->tiket->no_tiket ?? '-' }}</span>
                            <small class="text-muted" style="font-size: 0.72rem;">{{ $act->created_at->diffForHumans() }}</small>
                        </div>
                        <div class="text-primary small mb-1">
                            <i class="bi bi-arrow-right-short"></i> {{ $act->stage_dari }} &rarr; <strong>{{ $act->stage_ke }}</strong>
                        </div>
                        <div class="text-muted small fst-italic">{{ $act->keterangan }}</div>
                        <small class="text-secondary d-block mt-1" style="font-size: 0.72rem;">Oleh: {{ $act->user->name ?? 'Sistem' }}</small>
                    </div>
                @empty
                    <div class="text-center py-4 text-muted small">Belum ada catatan aktivitas.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
