@extends('layouts.app')

@section('title', 'Edit Tiket ' . $tiket->no_tiket)

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="fw-bold mb-1">Edit Informasi Tiket: {{ $tiket->no_tiket }}</h4>
        <p class="text-muted small mb-0">Ubah informasi pemohon atau catatan permohonan.</p>
    </div>
    <a href="{{ route('loket.show', $tiket->id) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Detail
    </a>
</div>

<div class="card card-custom p-4" style="max-width: 700px;">
    <form action="{{ route('loket.update', $tiket->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label small fw-bold">Nomor Tiket <span class="text-danger">*</span></label>
            <input type="text" name="no_tiket" class="form-control form-control-sm" value="{{ old('no_tiket', $tiket->no_tiket) }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label small fw-bold">Jenis Permohonan / Layanan <span class="text-danger">*</span></label>
            <select name="jenis_permohonan_id" class="form-select form-select-sm bg-light" required>
                @foreach($jenisPermohonans as $jp)
                    <option value="{{ $jp->id }}" @selected($tiket->jenis_permohonan_id === $jp->id)>
                        {{ $jp->kode }} - {{ $jp->nama }} (SLA: {{ $jp->batas_hari_sla }} Hari)
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label small fw-bold">Nama Pemohon <span class="text-danger">*</span></label>
            <input type="text" name="nama_pemohon" class="form-control form-control-sm" value="{{ old('nama_pemohon', $tiket->nama_pemohon) }}" required>
        </div>

        <div class="row g-2 mb-3">
            <div class="col-md-6">
                <label class="form-label small fw-bold">NIK Pemohon</label>
                <input type="text" name="nik_pemohon" class="form-control form-control-sm" value="{{ old('nik_pemohon', $tiket->nik_pemohon) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-bold">No. HP / WhatsApp <span class="text-danger">*</span></label>
                <input type="text" name="no_hp_pemohon" class="form-control form-control-sm" value="{{ old('no_hp_pemohon', $tiket->no_hp_pemohon) }}" required>
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label small fw-bold">Catatan Keterangan</label>
            <textarea name="keterangan" rows="3" class="form-control form-control-sm">{{ old('keterangan', $tiket->keterangan) }}</textarea>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('loket.show', $tiket->id) }}" class="btn btn-secondary btn-sm">Batal</a>
            <button type="submit" class="btn btn-primary btn-sm fw-bold px-4">Simpan Perubahan</button>
        </div>
    </form>
</div>
@endsection
