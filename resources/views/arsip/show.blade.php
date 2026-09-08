@extends('layouts.app')

@section('title', 'Arsip ' . $tiket->no_tiket)

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h4 class="fw-bold mb-0">Arsip: {{ $tiket->no_tiket }}</h4>
            <span class="badge bg-{{ $tiket->status_badge }} fs-6">{{ $tiket->status_label }}</span>
            <span class="badge bg-dark"><i class="bi bi-archive me-1"></i> {{ $tiket->periode ?? $tiket->tahun }}</span>
            @if($tiket->status_pembetulan !== 'P0')
                <span class="badge bg-warning text-dark">{{ $tiket->status_pembetulan }}</span>
            @endif
        </div>
        <p class="text-muted small mb-0">
            Tanggal Masuk: <strong>{{ $tiket->tanggal_masuk?->format('d F Y') ?? '-' }}</strong>
            &bull; Diarsipkan: <strong>{{ $tiket->diarsipkan_pada?->format('d/m/Y H:i') ?? '-' }}</strong>
            &bull; Oleh: <strong>{{ $tiket->diarsipkanOleh->name ?? '-' }}</strong>
        </p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('tracking.show', urlencode($tiket->no_tiket)) }}" target="_blank" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-qr-code me-1"></i> Tracking Publik
        </a>
        @if(auth()->user()->isAdmin())
        <form action="{{ route('arsip.restore', $tiket->id) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-warning btn-sm" onclick="return confirm('Kembalikan {{ $tiket->no_tiket }} ke daftar aktif?')">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Restore ke Daftar Aktif
            </button>
        </form>
        @endif
        <a href="{{ route('arsip.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Data Pemohon -->
    <div class="col-lg-6">
        <div class="card card-custom p-4 h-100">
            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-person-badge text-primary me-2"></i>Informasi Pemohon</h6>
            <table class="table table-sm table-borderless small mb-0">
                <tr><td class="text-muted" style="width: 150px;">Nama Pemohon</td><td class="fw-bold text-dark">: {{ $tiket->nama_pemohon }}</td></tr>
                <tr><td class="text-muted">NIK Pemohon</td><td>: {{ $tiket->nik_pemohon ?? '-' }}</td></tr>
                <tr><td class="text-muted">No. HP / WhatsApp</td><td>: {{ $tiket->no_hp_pemohon ?? '-' }}</td></tr>
                <tr><td class="text-muted">Petugas Loket</td><td>: {{ $tiket->petugasLoket->name ?? '-' }}</td></tr>
                <tr><td class="text-muted">Catatan</td><td>: {{ $tiket->keterangan ?? '-' }}</td></tr>
            </table>
        </div>
    </div>

    <!-- Data Layanan & Arsip -->
    <div class="col-lg-6">
        <div class="card card-custom p-4 h-100">
            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-briefcase text-warning me-2"></i>Informasi Layanan & Arsip</h6>
            <table class="table table-sm table-borderless small mb-0">
                <tr>
                    <td class="text-muted" style="width: 150px;">Jenis Layanan</td>
                    <td class="fw-bold text-dark">: {{ $tiket->jenisPermohonan->kode }} &bull; {{ $tiket->jenisPermohonan->nama }}</td>
                </tr>
                <tr><td class="text-muted">Kategori</td><td>: <span class="badge bg-light text-dark border text-uppercase">{{ $tiket->jenisPermohonan->kategori }}</span></td></tr>
                <tr><td class="text-muted">Status Akhir</td><td>: <span class="badge bg-{{ $tiket->status_badge }}">{{ $tiket->status_label }}</span></td></tr>
                <tr><td class="text-muted">Tahun</td><td>: <span class="badge bg-dark">{{ $tiket->tahun ?? '-' }}</span></td></tr>
                <tr><td class="text-muted">Periode Arsip</td><td>: {{ $tiket->periode ?? '-' }}</td></tr>
                <tr><td class="text-muted">Sumber Data</td><td>: {{ $tiket->sumber_data ?? '-' }}</td></tr>
                <tr><td class="text-muted">Tanggal Selesai</td><td>: {{ $tiket->tanggal_selesai?->format('d/m/Y') ?? '-' }}</td></tr>
            </table>
        </div>
    </div>
</div>

<!-- Daftar Bidang Tanah -->
<div class="card card-custom p-4 mb-4">
    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-geo-alt-fill text-danger me-2"></i>Rincian Bidang Tanah ({{ $tiket->bidangTanahs->count() }} Bidang)</h6>
    <div class="table-responsive">
        <table class="table table-bordered table-sm align-middle small mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>NIB</th>
                    <th>No. Sertifikat Lama</th>
                    <th>Jenis Hak</th>
                    <th>Pemegang Hak</th>
                    <th>Luas (m²)</th>
                    <th>Kelurahan / Kecamatan</th>
                    <th>Sertifikat Elektronik</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tiket->bidangTanahs as $idx => $b)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td class="fw-bold">{{ $b->nib ?? '-' }}</td>
                        <td>{{ $b->no_sertifikat_lama ?? '-' }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $b->jenis_hak ?? '-' }}</span></td>
                        <td>{{ $b->nama_pemegang_hak ?? '-' }}</td>
                        <td>{{ $b->luas_m2 ? number_format($b->luas_m2, 2) . ' m²' : '-' }}</td>
                        <td>{{ $b->desa_kelurahan ?? '-' }} / {{ $b->kecamatan ?? '-' }}</td>
                        <td>
                            @if($b->no_sertifikat_elektronik)
                                <span class="badge bg-success"><i class="bi bi-patch-check"></i> {{ $b->no_sertifikat_elektronik }}</span>
                            @else
                                <span class="text-muted small fst-italic">Belum Terbit</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center py-3 text-muted">Tidak ada data bidang tanah.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Riwayat Status & Audit Trail -->
<div class="card card-custom p-4">
    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-clock-history text-secondary me-2"></i>Riwayat Perjalanan Berkas (Audit Trail)</h6>
    <div class="table-responsive">
        <table class="table table-sm table-striped small mb-0">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Dari Tahap</th>
                    <th>Ke Tahap</th>
                    <th>Petugas</th>
                    <th>Keterangan / Catatan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tiket->riwayatStatuses as $r)
                    <tr>
                        <td>{{ $r->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $r->stage_dari }}</td>
                        <td class="fw-bold text-primary">{{ $r->stage_ke }}</td>
                        <td>{{ $r->user->name ?? 'Sistem' }}</td>
                        <td>{{ $r->keterangan }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-2 text-muted">Belum ada riwayat status.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection