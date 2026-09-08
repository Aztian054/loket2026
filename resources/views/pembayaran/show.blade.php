@extends('layouts.app')

@section('title', 'Pembayaran ' . $tiket->no_tiket)

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h4 class="fw-bold mb-1">Pembayaran & Finalisasi: {{ $tiket->no_tiket }}</h4>
        <p class="text-muted small mb-0">Konfirmasi pembayaran SPS sebelum tiket dinyatakan SELESAI.</p>
    </div>
    <a href="{{ route('pembayaran.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
</div>

@include('partials.stepper', ['steps' => $tiket->getStepperData()])

<form action="{{ route('pembayaran.update', $tiket->id) }}" method="POST">
    @csrf

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
            <div class="col-md-3">
                <span class="text-muted small">Jumlah Bidang:</span>
                <div class="fw-bold text-dark">{{ $tiket->bidangTanahs->count() }} Bidang</div>
            </div>
        </div>
    </div>

    <div class="card card-custom p-4 mb-4">
        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-credit-card-fill text-primary me-2"></i>Formulir Pembayaran</h6>

        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label small fw-bold mb-1">Petugas Pembayaran <span class="text-danger">*</span></label>
                <select name="petugas_pembayaran_id" class="form-select form-select-sm" required>
                    <option value="">-- Pilih Petugas --</option>
                    @foreach($petugasList as $p)
                        <option value="{{ $p->id }}" {{ auth()->id() == $p->id ? 'selected' : '' }}>
                            {{ $p->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold mb-1">Jumlah Pembayaran (Rp)</label>
                <input type="number" name="jumlah_pembayaran" class="form-control form-control-sm" step="1000" min="0"
                    value="{{ $tiket->jumlah_pembayaran ?? '' }}" placeholder="0">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold mb-1">Tanggal Pembayaran</label>
                <input type="date" name="tanggal_pembayaran" class="form-control form-control-sm"
                    value="{{ $tiket->tanggal_pembayaran?->format('Y-m-d') ?? now()->format('Y-m-d') }}">
            </div>
        </div>
    </div>

    <div class="card card-custom p-3 mb-4 border-start border-success border-4">
        <div class="d-flex flex-wrap justify-content-end gap-2">
            <button type="submit" name="action_type" value="save_draft" class="btn btn-outline-secondary btn-sm px-3">
                <i class="bi bi-save me-1"></i> Simpan Draf
            </button>
            <button type="submit" name="action_type" value="confirm_lunas" class="btn btn-success btn-sm px-4 fw-bold shadow-sm"
                onclick="return confirm('Konfirmasi pembayaran LUNAS? Tiket akan dinyatakan SELESAI.')">
                <i class="bi bi-check-circle-fill me-1"></i> Konfirmasi Pembayaran Lunas & Selesaikan
            </button>
        </div>
        <small class="text-muted d-block mt-2"><i class="bi bi-info-circle me-1"></i> Konfirmasi pembayaran adalah tahap terakhir sebelum tiket dinyatakan SELESAI.</small>
    </div>
</form>
@endsection
