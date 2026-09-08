@extends('layouts.app')

@section('title', 'Validasi Data ' . $tiket->no_tiket)

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h4 class="fw-bold mb-1">Validasi Data Pertanahan & KKP: {{ $tiket->no_tiket }}</h4>
        <p class="text-muted small mb-0">Validasi paralel Pra-BTel &amp; Pra-SuEl per bidang. Bidang otomatis LULUS jika kedua sub-bidang selesai.</p>
    </div>
    <a href="{{ route('validator.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
</div>

@include('partials.stepper', ['steps' => $tiket->getStepperData()])

<!-- Bab 9: Panel Konfirmasi Revisi -->
@include('partials.revisi-panel', ['tiket' => $tiket, 'stageTujuan' => 'Validator', 'routeKonfirmasi' => 'validator.konfirmasiRevisi'])

@php
    // Gatekeeper: semua bidang harus LULUS (Pra-BTel & Pra-SuEl = selesai)
    $semuaLulus = $tiket->bidangTanahs->count() > 0
        && $tiket->bidangTanahs->every(function ($b) use ($tiket) {
            $v = $tiket->lembarKerjaValidasis->where('bidang_id', $b->id)->first();
            return $v && $v->status_pra_btel === 'selesai' && $v->status_pra_suel === 'selesai';
        });
@endphp

<form action="{{ route('validator.update', $tiket->id) }}" method="POST">
    @csrf

    <!-- Info Tiket -->
    <div class="card card-custom p-4 mb-4">
        <div class="row g-3">
            <div class="col-md-3">
                <span class="text-muted small">Pemohon:</span>
                <div class="fw-bold text-dark">{{ $tiket->nama_pemohon }}</div>
            </div>
            <div class="col-md-3">
                <span class="text-muted small">Layanan:</span>
                <div class="fw-bold text-dark">{{ $tiket->jenisPermohonan->kode }} - {{ $tiket->jenisPermohonan->nama }}</div>
            </div>
            @if($isBtel || $isAdmin)
            <div class="col-md-3">
                <label class="form-label text-muted small fw-bold mb-1">Validator Pra-BTel @if(!$isAdmin)<span class="text-danger">*</span>@endif</label>
                <select name="validator_btel_id" class="form-select form-select-sm" {{ $isAdmin ? '' : 'required' }}>
                    @if($isAdmin)<option value="">-- Pilih Validator BTel --</option>@endif
                    @foreach($validatorsBtel as $v)
                        <option value="{{ $v->id }}" {{ auth()->id() == $v->id ? 'selected' : '' }}>
                            {{ $v->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif
            @if($isSuel || $isAdmin)
            <div class="col-md-3">
                <label class="form-label text-muted small fw-bold mb-1">Validator Pra-SuEl @if(!$isAdmin)<span class="text-danger">*</span>@endif</label>
                <select name="validator_suel_id" class="form-select form-select-sm" {{ $isAdmin ? '' : 'required' }}>
                    @if($isAdmin)<option value="">-- Pilih Validator SuEl --</option>@endif
                    @foreach($validatorsSuel as $v)
                        <option value="{{ $v->id }}" {{ auth()->id() == $v->id ? 'selected' : '' }}>
                            {{ $v->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif
        </div>
    </div>

    <!-- Lembar Kerja Validasi Per Bidang -->
    <div class="card card-custom p-4 mb-4">
        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-shield-check text-primary me-2"></i>Rincian Validasi Bidang Tanah</h6>

        @foreach($tiket->bidangTanahs as $idx => $b)
            @php
                $lkValidasi = $tiket->lembarKerjaValidasis->where('bidang_id', $b->id)->first();
                $autoLulus = ($lkValidasi?->status_pra_btel ?? '') === 'selesai' && ($lkValidasi?->status_pra_suel ?? '') === 'selesai';
            @endphp
            <div class="border rounded-3 p-3 mb-3 bg-light">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 pb-2 border-bottom gap-1">
                    <div>
                        <span class="badge bg-secondary me-2">Bidang #{{ $idx + 1 }}</span>
                        <strong>NIB:</strong> {{ $b->nib ?? '-' }} &bull;
                        <strong>No Hak:</strong> {{ $b->jenis_hak }} {{ $b->no_sertifikat_lama ?? '-' }} &bull;
                        <strong>Nama di Berkas:</strong> {{ $b->nama_pemegang_hak ?? '-' }} &bull;
                        <strong>Luas:</strong> {{ $b->luas_m2 ? number_format($b->luas_m2, 2) . ' m²' : '-' }}
                    </div>
                    @if($autoLulus)
                        <span class="badge bg-success"><i class="bi bi-check-circle-fill me-1"></i>LULUS</span>
                    @else
                        <span class="badge bg-secondary"><i class="bi bi-hourglass-split me-1"></i>PROSES</span>
                    @endif
                </div>

                <input type="hidden" name="items[{{ $idx }}][bidang_id]" value="{{ $b->id }}">

                <div class="row g-2">
                    @if($isBtel || $isAdmin)
                    <div class="col-md-3">
                        <label class="form-label small fw-bold mb-1">Status Pra-BTel @if(!$isAdmin)<span class="text-danger">*</span>@endif</label>
                        <select name="items[{{ $idx }}][status_pra_btel]" class="form-select form-select-sm" {{ $isAdmin ? '' : 'required' }}>
                            <option value="belum" {{ ($lkValidasi?->status_pra_btel ?? 'belum') === 'belum' ? 'selected' : '' }}>Belum</option>
                            <option value="proses" {{ $lkValidasi?->status_pra_btel === 'proses' ? 'selected' : '' }}>Proses</option>
                            <option value="selesai" {{ $lkValidasi?->status_pra_btel === 'selesai' ? 'selected' : '' }}>Selesai</option>
                        </select>
                    </div>
                    @endif

                    @if($isSuel || $isAdmin)
                    <div class="col-md-3">
                        <label class="form-label small fw-bold mb-1">Status Pra-SuEl @if(!$isAdmin)<span class="text-danger">*</span>@endif</label>
                        <select name="items[{{ $idx }}][status_pra_suel]" class="form-select form-select-sm" {{ $isAdmin ? '' : 'required' }}>
                            <option value="belum" {{ ($lkValidasi?->status_pra_suel ?? 'belum') === 'belum' ? 'selected' : '' }}>Belum</option>
                            <option value="proses" {{ $lkValidasi?->status_pra_suel === 'proses' ? 'selected' : '' }}>Proses</option>
                            <option value="selesai" {{ $lkValidasi?->status_pra_suel === 'selesai' ? 'selected' : '' }}>Selesai</option>
                        </select>
                    </div>
                    @endif

                    <div class="col-md-4 mt-2">
                        <label class="form-label small fw-bold mb-1">Status Validasi Bidang (Otomatis)</label>
                        @if($autoLulus)
                            <span class="badge bg-success"><i class="bi bi-check-circle-fill me-1"></i>LULUS</span>
                            <small class="d-block text-muted">Pra-BTel &amp; Pra-SuEl selesai.</small>
                        @else
                            <span class="badge bg-secondary"><i class="bi bi-hourglass-split me-1"></i>PROSES</span>
                            <small class="d-block text-muted">Menunggu kedua sub-bidang selesai.</small>
                        @endif
                    </div>

                    <div class="col-md-8 mt-2">
                        <label class="form-label small text-muted mb-1">Catatan Validator:</label>
                        <input type="text" name="items[{{ $idx }}][catatan]" class="form-control form-control-sm" placeholder="Catatan hasil validasi data KKP..." value="{{ $lkValidasi?->catatan }}">
                    </div>
                </div>
            </div>
        @endforeach

        <!-- Action Footer -->
        <div class="card card-custom p-3 mb-4 border-start border-warning border-4">
            <div class="mb-3">
                <label class="form-label small fw-bold text-dark">Catatan Validator (wajib jika revisi):</label>
                <textarea name="catatan_validator" rows="2" class="form-control form-control-sm" placeholder="Jelaskan temuan tidak valid / alasan revisi...">{{ $tiket->lembarKerjaValidasis->first()?->catatan_validator }}</textarea>
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex flex-wrap gap-2">
                    <div class="dropdown">
                        <button class="btn btn-outline-danger btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Revisi / Kembalikan
                        </button>
                        <ul class="dropdown-menu shadow-sm">
                            <li>
                                <button type="submit" name="action_type" value="return_to_verifikator" class="dropdown-item" onclick="return confirm('Kembalikan ke Verifikator? Warkah tetap berjalan (non-blocking).')">
                                    <i class="bi bi-arrow-return-left text-warning me-1"></i> Revisi ke <strong>Verifikator</strong>
                                </button>
                            </li>
                            <li>
                                <button type="submit" name="action_type" value="return_to_warkah" class="dropdown-item" onclick="return confirm('Kembalikan ke Warkah? Verifikator tetap berjalan (non-blocking).')">
                                    <i class="bi bi-arrow-return-left text-info me-1"></i> Revisi ke <strong>Warkah</strong>
                                </button>
                            </li>
                        </ul>
                    </div>
                    <button type="submit" name="action_type" value="reject" class="btn btn-outline-danger btn-sm" onclick="return confirm('Tolak permohonan ini?')">
                        <i class="bi bi-x-circle me-1"></i> Tolak Permohonan
                    </button>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" name="action_type" value="save_draft" class="btn btn-outline-secondary btn-sm px-3">
                        <i class="bi bi-save me-1"></i> Simpan Draf
                    </button>
                    <button type="submit" name="action_type" value="forward_to_alih_media" class="btn btn-primary btn-sm px-4 fw-bold"
                        {{ !$semuaLulus ? 'disabled' : '' }}
                        onclick="return confirm('Semua bidang LULUS oleh Pra-BTel & Pra-SuEl? Data akan diteruskan ke Alih Media.')">
                        <i class="bi bi-check2-circle me-1"></i> Lulus Semua & Teruskan ke Alih Media
                    </button>
                </div>
            </div>
            <small class="text-muted d-block mt-2"><i class="bi bi-info-circle me-1"></i> Gatekeeper (AND): tombol Alih Media aktif hanya jika SEMUA bidang LULUS oleh KEDUA sub-bidang (Pra-BTel &amp; Pra-SuEl).</small>
        </div>
    </div>
</form>
@endsection