@extends('layouts.app')

@section('title', 'Arsip Tahunan')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-archive-fill text-secondary me-2"></i>Arsip Tahunan</h4>
        <p class="text-muted small mb-0">Daftar tiket yang telah diarsipkan berdasarkan tahun &amp; periode.</p>
    </div>
    <span class="badge bg-secondary fs-6 py-2 px-3">
        <i class="bi bi-boxes me-1"></i> {{ number_format($totalArsip) }} Tiket Terarsip
    </span>
</div>

<!-- Statistik -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card card-custom p-3 border-start border-4 border-dark">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Total Arsip</div>
                    <h3 class="fw-bold my-1 text-dark">{{ number_format($totalArsip) }}</h3>
                    <span class="text-muted small">Semua periode</span>
                </div>
                <div class="bg-dark bg-opacity-10 text-dark p-3 rounded-circle"><i class="bi bi-archive-fill fs-3"></i></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card card-custom p-3 border-start border-4 border-warning">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-semibold text-uppercase">Siap Diarsipkan</div>
                    <h3 class="fw-bold my-1 text-warning">{{ number_format($siapArsip) }}</h3>
                    <span class="text-muted small">Selesai / Batal (belum diarsip)</span>
                </div>
                <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-circle"><i class="bi bi-inbox-fill fs-3"></i></div>
            </div>
        </div>
    </div>
    @foreach($statistikPerTahun as $st)
        @if($st->tahun)
        <div class="col-sm-6 col-xl-3">
            <div class="card card-custom p-3 border-start border-4 border-primary">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Tahun {{ $st->tahun }}</div>
                        <h3 class="fw-bold my-1 text-primary">{{ number_format($st->total) }}</h3>
                        <span class="text-muted small">{{ (int) $st->selesai }} selesai &bull; {{ (int) $st->batal }} batal</span>
                    </div>
                    <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle"><i class="bi bi-calendar2-check fs-3"></i></div>
                </div>
            </div>
        </div>
        @endif
    @endforeach
</div>

@if(!$isPimpinan)
<!-- Panel Arsip (Admin) -->
<div class="card card-custom p-4 mb-4 border-warning border">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-inbox-fill text-warning me-2"></i>Panel Arsip (Admin)</h6>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-sm btn-outline-primary" id="btnSelectAllSiap">
                <i class="bi bi-check2-square me-1"></i> Pilih Semua
            </button>
            @foreach($tahunSiapArsip as $th)
            <form action="{{ route('arsip.arsipkanTahun', $th) }}" method="POST" class="d-inline">
                @csrf
                <input type="hidden" name="konfirmasi" value="yes">
                <button type="submit" class="btn btn-sm btn-outline-dark"
                    onclick="return confirm('Arsipkan SEMUA tiket selesai/batal tahun {{ $th }}?')">
                    <i class="bi bi-archive me-1"></i> Arsipkan Tahun {{ $th }}
                </button>
            </form>
            @endforeach
        </div>
    </div>

    <form action="{{ route('arsip.arsipkanMassal') }}" method="POST" id="formArsipMassal">
        @csrf
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0" style="font-size:0.85rem;">
                <thead class="table-light">
                    <tr>
                        <th style="width:40px"><input type="checkbox" id="chkAllSiap"></th>
                        <th>No. Tiket</th>
                        <th>Nama Pemohon</th>
                        <th>Jenis Layanan</th>
                        <th>Status</th>
                        <th>Tanggal Masuk</th>
                        <th>Tanggal Selesai</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($siapArsipList as $t)
                    <tr>
                        <td><input type="checkbox" class="chk-siap" name="tiket_ids[]" value="{{ $t->id }}"></td>
                        <td class="fw-bold">{{ $t->no_tiket }}</td>
                        <td>{{ $t->nama_pemohon }}</td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $t->jenisPermohonan->kode }}</span>
                            <small class="d-block text-truncate text-muted" style="max-width:180px;" title="{{ $t->jenisPermohonan->nama }}">{{ $t->jenisPermohonan->nama }}</small>
                        </td>
                        <td><span class="badge bg-{{ $t->status_badge }}">{{ $t->status_label }}</span></td>
                        <td>{{ $t->tanggal_masuk?->format('d/m/Y') }}</td>
                        <td>{{ $t->tanggal_selesai?->format('d/m/Y') ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center py-4 text-muted">Tidak ada tiket selesai/batal yang menunggu arsip.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end mt-3">
            <button type="submit" class="btn btn-warning btn-sm px-4" {{ $siapArsipList->isEmpty() ? 'disabled' : '' }}>
                <i class="bi bi-archive me-1"></i> Arsipkan Terpilih
            </button>
        </div>
    </form>
</div>
@else
<div class="alert alert-light border text-muted small">
    <i class="bi bi-info-circle me-1"></i> Anda membuka arsip sebagai <strong>Pimpinan</strong> (read-only). Tindakan arsip/restore hanya dapat dilakukan oleh Administrator.
</div>
@endif

<!-- Filter -->
<div class="card card-custom p-3 mb-4">
    <form action="{{ route('arsip.index') }}" method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label small fw-semibold text-muted">Cari</label>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control bg-light" placeholder="No Tiket / Nama Pemohon..." value="{{ $search ?? '' }}">
            </div>
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold text-muted">Tahun</label>
            <select name="tahun" class="form-select form-select-sm bg-light">
                <option value="">-- Semua Tahun --</option>
                @foreach($tahunTersedia as $th)
                    <option value="{{ $th }}" {{ (string) $tahun === (string) $th ? 'selected' : '' }}>{{ $th }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4 d-flex gap-1">
            <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-filter"></i> Filter</button>
            <a href="{{ route('arsip.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</div>

<!-- Tabel Arsip -->
<div class="card card-custom p-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
            <thead class="table-light">
                <tr>
                    <th>No. Tiket</th>
                    <th>Nama Pemohon</th>
                    <th>Layanan</th>
                    <th>Status</th>
                    <th>Periode</th>
                    <th>Diarsipkan Pada</th>
                    <th>Diarsipkan Oleh</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($arsips as $t)
                <tr>
                    <td>
                        <span class="fw-bold text-dark">{{ $t->no_tiket }}</span>
                        <div class="text-muted small">{{ $t->tanggal_masuk?->format('d/m/Y') }}</div>
                    </td>
                    <td>{{ $t->nama_pemohon }}</td>
                    <td>
                        <span class="badge bg-light text-dark border">{{ $t->jenisPermohonan->kode }}</span>
                        <div class="small text-truncate text-muted" style="max-width:200px;" title="{{ $t->jenisPermohonan->nama }}">{{ $t->jenisPermohonan->nama }}</div>
                    </td>
                    <td><span class="badge bg-{{ $t->status_badge }}">{{ $t->status_label }}</span></td>
                    <td><span class="badge bg-dark">{{ $t->periode ?? $t->tahun }}</span></td>
                    <td>{{ $t->diarsipkan_pada?->format('d/m/Y H:i') ?? '-' }}</td>
                    <td>{{ $t->diarsipkanOleh->name ?? '-' }}</td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('arsip.show', $t->id) }}" class="btn btn-outline-primary" title="Detail"><i class="bi bi-eye"></i></a>
                            @if(!$isPimpinan)
                            <form action="{{ route('arsip.restore', $t->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-outline-warning" title="Restore ke daftar aktif"
                                    onclick="return confirm('Kembalikan {{ $t->no_tiket }} ke daftar aktif?')">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        <i class="bi bi-archive fs-1 d-block mb-2 text-secondary"></i>
                        Tidak ada tiket arsip ditemukan.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        {{ $arsips->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection

@section('scripts')
<script>
document.getElementById('chkAllSiap')?.addEventListener('change', function () {
    document.querySelectorAll('.chk-siap').forEach(cb => cb.checked = this.checked);
});
document.getElementById('btnSelectAllSiap')?.addEventListener('click', function () {
    document.querySelectorAll('.chk-siap').forEach(cb => cb.checked = true);
});
document.getElementById('formArsipMassal')?.addEventListener('submit', function (e) {
    const checked = document.querySelectorAll('.chk-siap:checked').length;
    if (checked === 0) {
        e.preventDefault();
        alert('Pilih minimal satu tiket untuk diarsipkan.');
    }
});
</script>
@endsection