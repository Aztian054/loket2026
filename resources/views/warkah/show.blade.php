@extends('layouts.app')

@section('title', 'Lembar Kerja Warkah ' . $tiket->no_tiket)

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h4 class="fw-bold mb-1">Lembar Kerja Warkah: {{ $tiket->no_tiket }}</h4>
        <p class="text-muted small mb-0">Pencatatan keberadaan &amp; kondisi fisik warkah (data salinan BPN) — berjalan paralel dengan Verifikator.</p>
    </div>
    <a href="{{ route('warkah.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
</div>

@include('partials.stepper', ['steps' => $tiket->getStepperData()])

<!-- Bab 9: Panel Konfirmasi Revisi -->
@include('partials.revisi-panel', ['tiket' => $tiket, 'stageTujuan' => 'Warkah', 'routeKonfirmasi' => 'warkah.konfirmasiRevisi'])

<form action="{{ route('warkah.update', $tiket->id) }}" method="POST">
    @csrf

    <!-- Info Tiket Singkat -->
    <div class="card card-custom p-3 mb-4">
        <div class="row g-2 align-items-center small">
            <div class="col-md-3">
                <span class="text-muted">Pemohon:</span>
                <div class="fw-bold text-dark">{{ $tiket->nama_pemohon }}</div>
            </div>
            <div class="col-md-4">
                <span class="text-muted">Layanan:</span>
                <div class="fw-bold text-dark">{{ $tiket->jenisPermohonan->kode }} - {{ $tiket->jenisPermohonan->nama }}</div>
            </div>
            <div class="col-md-5">
                <label class="form-label text-muted fw-bold mb-1">Petugas Warkah <span class="text-danger">*</span></label>
                <select name="petugas_id" class="form-select form-select-sm" required>
                    <option value="">-- Pilih Petugas Warkah --</option>
                    @foreach($petugasWarkah as $p)
                        <option value="{{ $p->id }}" {{ auth()->id() == $p->id ? 'selected' : '' }}>
                            {{ $p->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Lembar Kerja Warkah -->
    <div class="card card-custom p-4 mb-4">
        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-archive-fill text-warning me-2"></i>Lembar Kerja Warkah</h6>

        <div class="mb-3">
            <label class="form-label small fw-bold">
                <i class="bi bi-chat-left-text text-primary me-1"></i>Catatan Warkah
                <small class="text-muted fw-normal d-block">Catatan hasil pengecekan keberadaan &amp; kondisi fisik warkah (salinan data BPN) atas berkas permohonan.</small>
            </label>
            <textarea name="catatan" rows="5" class="form-control form-control-sm" placeholder="Contoh: Salinan sertifikat tersedia, kondisi baik, telah discan digital...">{{ $tiket->lembarKerjaWarkahs->first()?->catatan }}</textarea>
        </div>

        <!-- Catatan untuk tindakan revisi (return_to_loket / return_to_verifikator) -->
        <div class="mb-2">
            <label class="form-label small text-muted mb-1">Catatan revisi (diwajibkan bila memilih Perlu Perbaikan / Kembali ke Verifikator):</label>
            <textarea name="revisi_pesan" class="form-control form-control-sm" rows="2" placeholder="Jelaskan alasan perbaikan yang dibutuhkan..."></textarea>
        </div>

        <!-- Action Footer -->
        <div class="d-flex flex-wrap justify-content-end gap-2 pt-3 border-top mt-3">
            <button type="submit" name="action_type" value="return_to_verifikator" class="btn btn-outline-warning btn-sm px-3" onclick="return confirm('Kembalikan ke Verifikator untuk pengecekan ulang data asli pemohon?')">
                <i class="bi bi-arrow-repeat me-1"></i> Kembali ke Verifikator
            </button>
            <button type="submit" name="action_type" value="return_to_loket" class="btn btn-outline-danger btn-sm px-3" onclick="return confirm('Kembalikan ke Loket untuk perbaikan? Verifikator tetap berjalan (non-blocking).')">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Perlu Perbaikan (Revisi ke Loket)
            </button>
            <button type="submit" name="action_type" value="save_draft" class="btn btn-outline-secondary btn-sm px-3">
                <i class="bi bi-save me-1"></i> Simpan Draf
            </button>
            <button type="submit" name="action_type" value="forward_to_validator" class="btn btn-primary btn-sm px-4 fw-bold" onclick="return confirm('Warkah sudah selesai diperiksa dan siap diteruskan ke Validator?')">
                <i class="bi bi-send-check-fill me-1"></i> Selesaikan & Teruskan ke Validator
            </button>
        </div>
    </div>
</form>
@endsection