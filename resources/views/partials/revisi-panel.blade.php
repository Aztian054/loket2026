{{--
    Panel status revisi untuk halaman show tiap stage (Bab 9).
    Props: tiket, stageTujuan (string nama stage), routeKonfirmasi (nama route)
--}}
@props([
    'tiket' => null,
    'stageTujuan' => null,
    'routeKonfirmasi' => null,
])

@if($tiket && $stageTujuan)
    @php
        $revisiAktifStage = $tiket->activeRevisis->where('stage_tujuan', $stageTujuan);
        $revisiDikonfirmasi = $tiket->diterimaRevisis->firstWhere('stage_tujuan', $stageTujuan);
    @endphp

    @foreach($revisiAktifStage as $revisi)
        <div class="alert alert-danger border-0 shadow-sm d-flex align-items-start" style="border-radius: 12px;">
            <i class="bi bi-exclamation-octagon-fill fs-4 me-3 text-danger"></i>
            <div class="flex-grow-1">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <strong class="me-auto">Revisi Masuk dari {{ $revisi->stage_asal }}</strong>
                    <span class="badge bg-danger rounded-pill">Menunggu Konfirmasi</span>
                </div>
                <p class="mb-2 mt-1 text-dark small">{{ $revisi->pesan_catatan }}</p>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    @if($routeKonfirmasi)
                        <form method="POST" action="{{ route($routeKonfirmasi, [$tiket->id, $revisi->id]) }}" class="m-0">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="bi bi-check2-circle me-1"></i> Konfirmasi Terima Revisi
                            </button>
                        </form>
                    @endif
                    <small class="text-muted">
                        Dikirim oleh {{ $revisi->creator?->name ?? 'Sistem' }}
                        ({{ $revisi->created_at?->diffForHumans() }})
                    </small>
                </div>
            </div>
        </div>
    @endforeach

    @if($revisiAktifStage->isEmpty() && $revisiDikonfirmasi)
        <div class="alert alert-success border-0 shadow-sm d-flex align-items-start" style="border-radius: 12px;">
            <i class="bi bi-check-circle-fill fs-4 me-3 text-success"></i>
            <div>
                <strong>Revisi dari {{ $revisiDikonfirmasi->stage_asal }} telah dikonfirmasi.</strong>
                <p class="mb-0 mt-1 small text-muted">
                    Dikonfirmasi oleh {{ $revisiDikonfirmasi->confirmer?->name ?? '-' }}
                    ({{ $revisiDikonfirmasi->dikonfirmasi_pada?->translatedFormat('d M Y, H:i') ?? '-' }}).
                    Silakan lanjutkan perbaikan dan kirim kembali berkas.
                </p>
            </div>
        </div>
    @endif
@endif