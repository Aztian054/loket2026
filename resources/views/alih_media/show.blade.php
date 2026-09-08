@extends('layouts.app')

@section('title', 'Lembar Kerja Alih Media ' . $tiket->no_tiket)

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h4 class="fw-bold mb-1">Alih Media & Penerbitan Sertifikat Elektronik: {{ $tiket->no_tiket }}</h4>
        <p class="text-muted small mb-0">Tahap akhir digitalisasi & penerbitan sertifikat elektronik. Permohonan SELESAI setelah Pra-BTel &amp; Pra-SuEl keduanya menekan "Selesaikan Permohonan".</p>
    </div>
    <a href="{{ route('alih_media.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
</div>

@include('partials.stepper', ['steps' => $tiket->getStepperData()])

<!-- Bab 9: Panel Konfirmasi Revisi -->
@include('partials.revisi-panel', ['tiket' => $tiket, 'stageTujuan' => 'Alih Media', 'routeKonfirmasi' => 'alih_media.konfirmasiRevisi'])

@php
    // Gate-AND Final: permohonan selesai hanya bila KEDUA sub-bidang selesai pada semua bidang
    $btelSelesai = $tiket->bidangTanahs->count() > 0
        && $tiket->bidangTanahs->every(function ($b) use ($tiket) {
            $v = $tiket->lembarKerjaAlihMedias->where('bidang_id', $b->id)->first();
            return $v && $v->status_btel === 'selesai';
        });
    $suelSelesai = $tiket->bidangTanahs->count() > 0
        && $tiket->bidangTanahs->every(function ($b) use ($tiket) {
            $v = $tiket->lembarKerjaAlihMedias->where('bidang_id', $b->id)->first();
            return $v && $v->status_suel === 'selesai';
        });
    $semuaSelesai = $btelSelesai && $suelSelesai;
@endphp

<form action="{{ route('alih_media.update', $tiket->id) }}" method="POST">
    @csrf

    <!-- Info Singkat -->
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
                <div class="row g-2">
                    @if($isBtel || $isAdmin)
                    <div class="col-md-6">
                        <label class="form-label text-muted fw-bold mb-1">Petugas Pra-BTel @if(!$isAdmin)<span class="text-danger">*</span>@endif</label>
                        <select name="petugas_btel_id" class="form-select form-select-sm" {{ $isAdmin ? '' : 'required' }}>
                            @if($isAdmin)<option value="">-- Pilih Petugas BTel --</option>@endif
                            @foreach($petugasBtel as $p)
                                <option value="{{ $p->id }}" {{ auth()->id() == $p->id ? 'selected' : '' }}>
                                    {{ $p->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    @if($isSuel || $isAdmin)
                    <div class="col-md-6">
                        <label class="form-label text-muted fw-bold mb-1">Petugas Pra-SuEl @if(!$isAdmin)<span class="text-danger">*</span>@endif</label>
                        <select name="petugas_suel_id" class="form-select form-select-sm" {{ $isAdmin ? '' : 'required' }}>
                            @if($isAdmin)<option value="">-- Pilih Petugas SuEl --</option>@endif
                            @foreach($petugasSuel as $p)
                                <option value="{{ $p->id }}" {{ auth()->id() == $p->id ? 'selected' : '' }}>
                                    {{ $p->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Lembar Kerja Alih Media Per Bidang -->
    <div class="card card-custom p-4 mb-4">
        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-patch-check-fill text-success me-2"></i>Penerbitan Sertifikat Elektronik Bidang Tanah</h6>

        @foreach($tiket->bidangTanahs as $idx => $b)
            @php
                $lkAlih = $tiket->lembarKerjaAlihMedias->where('bidang_id', $b->id)->first();
            @endphp
            <div class="border rounded-3 p-3 mb-3 bg-light">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 pb-2 border-bottom gap-1">
                    <div>
                        <span class="badge bg-secondary me-2">Bidang #{{ $idx + 1 }}</span>
                        <strong>NIB:</strong> {{ $b->nib ?? '-' }} &bull;
                        <strong>Sertifikat Lama:</strong> {{ $b->no_sertifikat_lama ?? '-' }} &bull;
                        <strong>Pemegang Hak:</strong> {{ $b->nama_pemegang_hak ?? '-' }} &bull;
                        <strong>Luas:</strong> {{ $b->luas_m2 ? number_format($b->luas_m2, 2) . ' m²' : '-' }}
                    </div>
                    <div class="d-flex gap-2">
                        <span class="badge bg-{{ ($lkAlih?->status_btel ?? 'belum') === 'selesai' ? 'success' : 'secondary' }}">
                            Pra-BTel: {{ ($lkAlih?->status_btel ?? 'belum') === 'selesai' ? 'Selesai' : 'Belum' }}
                        </span>
                        <span class="badge bg-{{ ($lkAlih?->status_suel ?? 'belum') === 'selesai' ? 'success' : 'secondary' }}">
                            Pra-SuEl: {{ ($lkAlih?->status_suel ?? 'belum') === 'selesai' ? 'Selesai' : 'Belum' }}
                        </span>
                    </div>
                </div>

                <input type="hidden" name="items[{{ $idx }}][bidang_id]" value="{{ $b->id }}">

                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label small text-muted mb-1">Catatan Alih Media:</label>
                        <textarea name="items[{{ $idx }}][catatan]" rows="2" class="form-control form-control-sm" placeholder="Catatan digitalisasi / arsip elektronik...">{{ $lkAlih?->catatan }}</textarea>
                    </div>
                </div>
            </div>
        @endforeach

        <!-- Action Footer -->
        <div class="card card-custom p-3 mb-4 border-start border-danger border-4">
            <div class="row g-2 mb-2">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-dark">Sub-bidang yang perlu diperbaiki <span class="text-danger">*</span></label>
                    <select name="revisi_sub_bidang" class="form-select form-select-sm">
                        <option value="">-- Pilih Sub-bidang --</option>
                        <option value="pra_btel">Pra-BTel (Berita Tanah Elektronik)</option>
                        <option value="pra_suel">Pra-SuEl (Surat Ukur Elektronik)</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label small fw-bold text-dark">Catatan Alih Media (wajib jika revisi ke Validator):</label>
                    <textarea name="revisi_pesan" rows="2" class="form-control form-control-sm" placeholder="Jelaskan yang perlu diperbaiki / dilengkapi..."></textarea>
                </div>
            </div>

            <div class="d-flex flex-wrap justify-content-end gap-2 pt-2 border-top">
                <button type="submit" name="action_type" value="return_to_validator" class="btn btn-outline-danger btn-sm px-3" onclick="return confirm('Kembalikan ke Validator untuk perbaikan data?')">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Perlu Perbaikan (Revisi ke Validator)
                </button>
                <button type="submit" name="action_type" value="save_draft" class="btn btn-outline-secondary btn-sm px-3">
                    <i class="bi bi-save me-1"></i> Simpan Draf
                </button>

                @if($isBtel || $isAdmin)
                <button type="submit" name="action_type" value="complete_btel" class="btn btn-success btn-sm px-4 fw-bold shadow-sm"
                    {{ $btelSelesai ? 'disabled' : '' }}
                    onclick="return confirm('Selesaikan pekerjaan Pra-BTel untuk semua bidang?')">
                    <i class="bi bi-patch-check-fill me-1"></i> Selesaikan Permohonan (Pra-BTel)
                </button>
                @endif
                @if($isSuel || $isAdmin)
                <button type="submit" name="action_type" value="complete_suel" class="btn btn-success btn-sm px-4 fw-bold shadow-sm"
                    {{ $suelSelesai ? 'disabled' : '' }}
                    onclick="return confirm('Selesaikan pekerjaan Pra-SuEl untuk semua bidang?')">
                    <i class="bi bi-patch-check-fill me-1"></i> Selesaikan Permohonan (Pra-SuEl)
                </button>
                @endif
            </div>
            <small class="text-muted d-block mt-2"><i class="bi bi-info-circle me-1"></i> Gatekeeper (AND): permohonan dinyatakan SELESAI &amp; sertifikat diterbitkan hanya setelah KEDUA sub-bidang (Pra-BTel &amp; Pra-SuEl) menekan "Selesaikan Permohonan".</small>
        </div>
    </div>
</form>
@endsection