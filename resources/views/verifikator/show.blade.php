@extends('layouts.app')

@section('title', 'Verifikasi Berkas ' . $tiket->no_tiket)

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h4 class="fw-bold mb-1">Pemeriksaan Berkas: {{ $tiket->no_tiket }}</h4>
        <p class="text-muted small mb-0">Verifikasi data asli pemohon — Warkah berjalan paralel (non-blocking).</p>
    </div>
    <a href="{{ route('verifikator.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
</div>

@include('partials.stepper', ['steps' => $tiket->getStepperData()])

<!-- Bab 9: Panel Konfirmasi Revisi -->
@include('partials.revisi-panel', ['tiket' => $tiket, 'stageTujuan' => 'Verifikator', 'routeKonfirmasi' => 'verifikator.konfirmasiRevisi'])

<div class="row g-4">
    <!-- Left Column: Data Berkas & Checklist Dokumen -->
    <div class="col-lg-6">
        <div class="card card-custom p-4 mb-4">
            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-info-circle text-primary me-2"></i>Data Permohonan</h6>
            <table class="table table-sm table-borderless small mb-3">
                <tr>
                    <td class="text-muted" style="width: 140px;">Pemohon</td>
                    <td class="fw-bold text-dark">: {{ $tiket->nama_pemohon }}</td>
                </tr>
                <tr>
                    <td class="text-muted">No. HP / WA</td>
                    <td>: <span class="text-success fw-semibold">{{ $tiket->no_hp_pemohon }}</span></td>
                </tr>
                <tr>
                    <td class="text-muted">Layanan</td>
                    <td class="fw-bold text-dark">: {{ $tiket->jenisPermohonan->kode }} - {{ $tiket->jenisPermohonan->nama }}</td>
                </tr>
                <tr>
                    <td class="text-muted">Jumlah Bidang</td>
                    <td>: {{ $tiket->jumlah_bidang }} Bidang Tanah</td>
                </tr>
                <tr>
                    <td class="text-muted">Iterasi Berkas</td>
                    <td>: <span class="badge bg-warning text-dark">{{ $tiket->status_pembetulan }}</span></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Right Column: Form Verifikasi & Saran Koreksi -->
    <div class="col-lg-6">
        <div class="card card-custom p-4">
            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-pencil-square text-success me-2"></i>Form Putusan Verifikator</h6>

            <form action="{{ route('verifikator.update', $tiket->id) }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label small fw-bold">Petugas Verifikator <span class="text-danger">*</span></label>
                    <select name="verifikator_id" class="form-select form-select-sm" required>
                        <option value="">-- Pilih Verifikator --</option>
                        @foreach($verifikators as $v)
                            <option value="{{ $v->id }}" {{ (auth()->id() == $v->id || $tiket->latestVerifikasi?->verifikator_id == $v->id) ? 'selected' : '' }}>
                                {{ $v->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Putusan Verifikasi</label>
                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <input type="radio" class="btn-check" name="status_verifikasi" id="statusLengkap" value="lengkap" checked>
                            <label class="btn btn-outline-success btn-sm w-100 py-2" for="statusLengkap">
                                <i class="bi bi-check-circle d-block mb-1 fs-5"></i>
                                <strong>LENGKAP</strong>
                            </label>
                        </div>
                        <div class="col-4">
                            <input type="radio" class="btn-check" name="status_verifikasi" id="statusPerbaikan" value="perbaikan">
                            <label class="btn btn-outline-warning btn-sm w-100 py-2" for="statusPerbaikan">
                                <i class="bi bi-exclamation-triangle d-block mb-1 fs-5"></i>
                                <strong>PERBAIKAN</strong>
                            </label>
                        </div>
                        <div class="col-4">
                            <input type="radio" class="btn-check" name="status_verifikasi" id="statusBatal" value="batal">
                            <label class="btn btn-outline-danger btn-sm w-100 py-2" for="statusBatal">
                                <i class="bi bi-x-circle d-block mb-1 fs-5"></i>
                                <strong>BATAL</strong>
                            </label>
                        </div>
                    </div>

                    <label class="form-label small fw-bold">
                        <i class="bi bi-clipboard-x text-danger me-1"></i>Catatan Verifikator
                        <small class="text-muted fw-normal d-block">Rincian kekurangan / saran koreksi — wajib diisi bila putusan PERBAIKAN.</small>
                    </label>
                    <textarea name="catatan" rows="4" class="form-control form-control-sm" placeholder="Jelaskan rincian dokumen yang kurang / saran perbaikan yang dibutuhkan pemohon...">{{ $tiket->latestVerifikasi?->catatan }}</textarea>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" name="action_type" value="forward_to_validator" class="btn btn-primary py-2 shadow-sm fw-bold">
                        <i class="bi bi-check-lg me-1"></i> LENGKAP — Teruskan ke Validator
                    </button>
                    <button type="submit" name="action_type" value="return_to_loket" class="btn btn-outline-warning py-2 fw-bold">
                        <i class="bi bi-arrow-repeat me-1"></i> PERBAIKAN — Kembalikan ke Loket
                    </button>
                    <button type="submit" name="action_type" value="save_draft" class="btn btn-outline-secondary py-1 fw-bold">
                        <i class="bi bi-save me-1"></i> Simpan Draf
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
