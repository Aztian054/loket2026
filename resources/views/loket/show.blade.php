@extends('layouts.app')

@section('title', 'Detail Tiket ' . $tiket->no_tiket)

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h4 class="fw-bold mb-0">Tiket: {{ $tiket->no_tiket }}</h4>
            <span class="badge bg-{{ $tiket->status_badge }} fs-6">{{ $tiket->status_label }}</span>
            @if($tiket->status_pembetulan !== 'P0')
                <span class="badge bg-warning text-dark">{{ $tiket->status_pembetulan }}</span>
            @endif
        </div>
        <p class="text-muted small mb-0">Tanggal Masuk: <strong>{{ $tiket->tanggal_masuk->format('d F Y') }}</strong> &bull; Target Selesai: <strong>{{ $tiket->tanggal_target_selesai?->format('d F Y') ?? '-' }}</strong></p>
    </div>

    <!-- Action Buttons -->
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('loket.printReceipt', $tiket->id) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-printer-fill me-1"></i> Cetak Tanda Terima
        </a>
        <a href="{{ route('loket.printChecklist', $tiket->id) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-card-checklist me-1"></i> Cetak Lembar Ceklis
        </a>
        <a href="{{ route('tracking.show', urlencode($tiket->no_tiket)) }}" target="_blank" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-qr-code me-1"></i> Halaman Tracking Publik
        </a>
        <a href="{{ route('loket.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>

        @if($tiket->status === 'diterima' && !$tiket->status_verifikator && !$tiket->status_warkah)
            <form action="{{ route('loket.forward', $tiket->id) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-gold btn-sm" title="Otomatis diteruskan ke Verifikator & Warkah (paralel)" onclick="return confirm('Kirim berkas ini? Berkas otomatis diteruskan ke Verifikator DAN Warkah secara paralel.')">
                    <i class="bi bi-send-check-fill me-1"></i> Kirim
                </button>
            </form>
        @endif

        @if($tiket->status === 'dikembalikan')
            <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalResubmit">
                <i class="bi bi-arrow-repeat me-1"></i> Input Perbaikan (P{{ (int) str_replace('P', '', $tiket->status_pembetulan) + 1 }})
            </button>
        @endif

        @if(auth()->user()->isAdmin() && in_array($tiket->status, ['selesai', 'batal']))
            <form action="{{ route('arsip.arsipkan', $tiket->id) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-dark btn-sm" onclick="return confirm('Arsipkan tiket {{ $tiket->no_tiket }} ke arsip tahunan?')">
                    <i class="bi bi-archive-fill me-1"></i> Arsipkan
                </button>
            </form>
        @endif
    </div>
</div>

<!-- Bab 9: Panel Konfirmasi Revisi -->
@include('partials.revisi-panel', ['tiket' => $tiket, 'stageTujuan' => 'Loket', 'routeKonfirmasi' => 'loket.konfirmasiRevisi'])

<!-- Stepper Visual Progress -->
<div class="card card-custom p-3 mb-4">
    <div class="d-flex flex-nowrap justify-content-between text-center position-relative overflow-auto">
        @php
            $stages = [
                'diterima' => ['label' => '1. Loket', 'icon' => 'bi-ticket-detailed'],
                'verifikasi' => ['label' => '2. Verifikator', 'icon' => 'bi-clipboard-check'],
                'warkah' => ['label' => '3. Warkah', 'icon' => 'bi-archive'],
                'validasi' => ['label' => '4. Validator', 'icon' => 'bi-shield-check'],
                'alih_media' => ['label' => '5. Alih Media', 'icon' => 'bi-file-earmark-diff'],
                'selesai' => ['label' => 'Selesai', 'icon' => 'bi-check-circle-fill'],
            ];
            $stageOrder = ['diterima', 'verifikasi', 'warkah', 'validasi', 'alih_media', 'selesai'];
            $currentIndex = array_search($tiket->status, $stageOrder);
            if ($currentIndex === false) $currentIndex = 1; // if dikembalikan / batal
        @endphp

        @foreach($stages as $key => $s)
            @php
                $stepIdx = array_search($key, $stageOrder);
                $isPassed = $stepIdx <= $currentIndex;
                $isCurrent = $tiket->status === $key;
            @endphp
            <div class="flex-fill" style="min-width: 80px;">
                <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-1 {{ $isCurrent ? 'bg-warning text-dark ring' : ($isPassed ? 'bg-success text-white' : 'bg-light text-muted border') }}" style="width: 38px; height: 38px;">
                    <i class="bi {{ $s['icon'] }} fs-5"></i>
                </div>
                <div class="small fw-semibold {{ $isCurrent ? 'text-dark fw-bold' : ($isPassed ? 'text-success' : 'text-muted') }}" style="font-size: 0.78rem; white-space: nowrap;">
                    {{ $s['label'] }}
                </div>
            </div>
        @endforeach
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Data Pemohon -->
    <div class="col-lg-6">
        <div class="card card-custom p-4 h-100">
            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-person-badge text-primary me-2"></i>Informasi Pemohon</h6>
            <table class="table table-sm table-borderless small mb-0">
                <tr>
                    <td class="text-muted" style="width: 140px;">Nama Pemohon</td>
                    <td class="fw-bold text-dark">: {{ $tiket->nama_pemohon }}</td>
                </tr>
                <tr>
                    <td class="text-muted">NIK Pemohon</td>
                    <td>: {{ $tiket->nik_pemohon ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="text-muted">Nomor WhatsApp/HP</td>
                    <td>: <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $tiket->no_hp_pemohon) }}" target="_blank" class="text-success fw-bold"><i class="bi bi-whatsapp"></i> {{ $tiket->no_hp_pemohon }}</a></td>
                </tr>
                <tr>
                    <td class="text-muted">Petugas Loket</td>
                    <td>: {{ $tiket->petugasLoket->name ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="text-muted">Catatan Awal</td>
                    <td>: {{ $tiket->keterangan ?? '-' }}</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Data Layanan & SLA -->
    <div class="col-lg-6">
        <div class="card card-custom p-4 h-100">
            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-briefcase text-warning me-2"></i>Informasi Layanan & SLA</h6>
            <table class="table table-sm table-borderless small mb-0">
                <tr>
                    <td class="text-muted" style="width: 140px;">Jenis Layanan</td>
                    <td class="fw-bold text-dark">: {{ $tiket->jenisPermohonan->kode }} &bull; {{ $tiket->jenisPermohonan->nama }}</td>
                </tr>
                <tr>
                    <td class="text-muted">Kategori</td>
                    <td>: <span class="badge bg-light text-dark border text-uppercase">{{ $tiket->jenisPermohonan->kategori }}</span></td>
                </tr>
                <tr>
                    <td class="text-muted">Standar Waktu (SLA)</td>
                    <td>: <span class="badge bg-primary">{{ $tiket->jenisPermohonan->batas_hari_sla }} Hari Kerja</span></td>
                </tr>
                <tr>
                    <td class="text-muted">Target Selesai</td>
                    <td>: {{ $tiket->tanggal_target_selesai?->format('d/m/Y') ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="text-muted">Tanggal Selesai</td>
                    <td>: {{ $tiket->tanggal_selesai?->format('d/m/Y') ?? '-' }}</td>
                </tr>
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
                @foreach($tiket->bidangTanahs as $idx => $b)
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
                @endforeach
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

<!-- Modal Resubmit Perbaikan -->
@if($tiket->status === 'dikembalikan')
<div class="modal fade" id="modalResubmit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('loket.resubmit', $tiket->id) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Pendaftaran Ulang Berkas Perbaikan</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body small">
                    <p>Pemohon telah menyerahkan dokumen perbaikan untuk Tiket <strong>{{ $tiket->no_tiket }}</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Catatan Dokumen Perbaikan yang Diserahkan:</label>
                        <textarea name="catatan_perbaikan" class="form-control form-control-sm" rows="3" placeholder="Contoh: Menyerahkan fotokopi KTP legalisir dan SPPT PBB asli..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning btn-sm">Kirim</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif

@endsection
