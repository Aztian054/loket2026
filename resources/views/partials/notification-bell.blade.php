<div class="dropdown">
    <button class="btn btn-light rounded-circle p-2 position-relative" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" style="width:42px; height:42px;">
        <i class="bi bi-bell-fill fs-5 text-secondary"></i>
        @if(($notifCounts['total'] ?? 0) > 0)
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.62rem;">{{ $notifCounts['total'] }}</span>
        @endif
    </button>
    <ul class="dropdown-menu dropdown-menu-end shadow border-0 py-2" style="width:360px; max-height:460px; overflow-y:auto;">
        <li class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
            <strong class="text-dark">Notifikasi</strong>
            @if(($notifCounts['total'] ?? 0) > 0)
                <form method="POST" action="{{ route('notifikasi.bacaSemua') }}" class="m-0">
                    @csrf
                    <button class="btn btn-sm btn-link text-decoration-none p-0 text-primary">Tandai semua dibaca</button>
                </form>
            @endif
        </li>
        @forelse($notifikasiTerbaru as $notif)
            @php
                [$notifIcon, $notifColor] = match ($notif->tipe) {
                    'forward' => ['bi-arrow-right-circle-fill', 'text-primary'],
                    'revisi' => ['bi-exclamation-octagon-fill', 'text-danger'],
                    'revisi_dikonfirmasi' => ['bi-check-circle-fill', 'text-success'],
                    'revisi_selesai' => ['bi-arrow-repeat', 'text-success'],
                    default => ['bi-info-circle-fill', 'text-secondary'],
                };
            @endphp
            <li>
                <form method="POST" action="{{ route('notifikasi.tandaiBaca', $notif->id) }}" class="m-0">
                    @csrf
                    <button type="submit" class="dropdown-item py-2 px-3 d-flex align-items-start gap-2 {{ $notif->is_read ? '' : 'bg-light' }}">
                        <i class="bi {{ $notifIcon }} fs-5 {{ $notifColor }} mt-1"></i>
                        <span class="flex-grow-1 text-start">
                            <span class="d-block fw-semibold {{ $notif->is_read ? 'text-muted' : 'text-dark' }}" style="font-size:0.85rem;">
                                {{ $notif->judul }}
                                @if(!$notif->is_read)
                                    <span class="badge bg-danger rounded-pill ms-1" style="font-size:0.55rem;">BARU</span>
                                @endif
                            </span>
                            <span class="d-block text-muted" style="font-size:0.75rem;">{{ $notif->pesan }}</span>
                            <span class="d-block text-muted" style="font-size:0.7rem;">{{ $notif->created_at?->diffForHumans() }}</span>
                        </span>
                    </button>
                </form>
            </li>
        @empty
            <li class="px-3 py-4 text-center text-muted small">
                <i class="bi bi-bell-slash d-block mb-1 fs-4"></i>
                Tidak ada notifikasi.
            </li>
        @endforelse
        <li class="border-top">
            <a href="{{ route('notifikasi.index') }}" class="dropdown-item text-center fw-semibold py-2">Lihat Semua Notifikasi</a>
        </li>
    </ul>
</div>