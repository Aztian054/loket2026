@extends('layouts.app')

@section('title', 'Pendaftaran Tiket Permohonan Baru')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="fw-bold mb-1">Registrasi Permohonan Baru (Stage 1 : Loket)</h4>
        <p class="text-muted small mb-0">Input data pemohon dan jenis layanan untuk menerbitkan kode tiket.</p>
    </div>
    <a href="{{ route('loket.index') }}" class="btn btn-outline-secondary btn-sm rounded-3">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
    </a>
</div>

<form action="{{ route('loket.store') }}" method="POST" id="formCreateTiket">
    @csrf
    <div class="row g-4 justify-content-center">
        <div class="col-lg-8">
            <div class="card card-custom p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-person-lines-fill text-warning me-2"></i>Data Pemohon & Layanan</h6>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Nomor Tiket <span class="text-danger">*</span></label>
                    <input type="text" name="no_tiket" class="form-control" placeholder="Masukkan nomor tiket..." value="{{ old('no_tiket') }}" required>
                    @error('no_tiket')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Jenis Permohonan / Layanan <span class="text-danger">*</span></label>
                    <select name="jenis_permohonan_id" id="jenisPermohonanSelect" class="form-select bg-light" required>
                        <option value="">-- Pilih Jenis Permohonan --</option>
                        @foreach($jenisPermohonans as $jp)
                            <option value="{{ $jp->id }}" data-sla="{{ $jp->batas_hari_sla }}">
                                {{ $jp->kode }} - {{ $jp->nama }} (SLA: {{ $jp->batas_hari_sla }} Hari)
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Nama Lengkap Pemohon / Kuasa <span class="text-danger">*</span></label>
                    <input type="text" name="nama_pemohon" class="form-control" placeholder="Contoh: Budi Santoso / Ir. Ahmad Fauzi" required value="{{ old('nama_pemohon') }}">
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">NIK Pemohon (Opsional)</label>
                        <input type="text" name="nik_pemohon" class="form-control" placeholder="16 digit NIK..." maxlength="16" value="{{ old('nik_pemohon') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">No. HP / WhatsApp <span class="text-danger">*</span></label>
                        <input type="text" name="no_hp_pemohon" class="form-control" placeholder="08xxxxxxxxxx" required value="{{ old('no_hp_pemohon') }}">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Catatan / Keterangan Awal Loket</label>
                    <textarea name="keterangan" rows="3" class="form-control" placeholder="Catatan atau keterangan awal dari petugas loket...">{{ old('keterangan') }}</textarea>
                </div>

                <input type="hidden" name="jumlah_bidang" value="1">

                <div class="d-flex flex-wrap gap-2 mt-3">
                    <button type="submit" name="status_awal" value="diterima" class="btn btn-outline-secondary btn-sm px-3">
                        <i class="bi bi-save me-1"></i> Simpan Saja
                    </button>
                    <button type="submit" name="status_awal" value="verifikasi" class="btn btn-primary btn-sm px-4 fw-bold">
                        <i class="bi bi-send-check-fill me-1"></i> Simpan & Teruskan
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
