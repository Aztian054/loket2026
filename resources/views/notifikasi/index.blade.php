@extends('layouts.app')

@section('title', 'Notifikasi')

@section('styles')
<style>
    .notif-unread {
        border-left: 4px solid var(--bpn-gold);
        background: #fbf7ec;
    }
</style>
@endsection

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <div>
            <h4 class="fw-bold text-dark mb-0">Notifikasi</h4>
            <small class="text-muted">Inbox informasi &amp; konfirmasi revisi untuk {{ auth()->user()->name }}</small>
        </div>
        <div class="ms-auto">
            @if($unreadCount > 0)
                <form method="POST" action="{{ route('notifikasi.bacaSemua') }}" class="d-inline">
                    @csrf
                    <button class="btn btn-sm btn-bpn"><i class="bi bi-check2-all me-1"></i> Tandai Semua Dibaca</button>
                </form>
            @endif
            <span class="badge {{ $unreadCount > 0 ? 'bg-danger' : 'bg-success' }} rounded-pill fs-6 px-3 py-2 ms-1">
                {{ $unreadCount }} belum dibaca
            </span>
        </div>
    </div>

    <form method="GET" action="{{ route('notifikasi.index') }}" class="row g-2 mb-3">
        <div class="col-md-4">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari no tiket / nama pemohon...">
        </div>
        <div class="col-md-3">
            <select name="tipe" class="form-select">
                <option value="">Semua Tipe</option>
                <option value="forward" @selected(request('tipe') === 'forward')>Berkas Masuk (Forward)</option>
                <option value="revisi" @selected(request('tipe') === 'revisi')>Revisi Menunggu Konfirmasi</option>
                <option value="revisi_dikonfirmasi" @selected(request('tipe') === 'revisi_dikonfirmasi')>Revisi Dikonfirmasi</option>
                <option value="revisi_selesai" @selected(request('tipe') === 'revisi_selesai')>Revisi Selesai</option>
                <option value="info" @selected(request('tipe') === 'info')>Info Lainnya</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-bpn w-100"><i class="bi bi-search me-1"></i> Filter</button>
        </div>
    </form>

    <div class="card card-custom">
        <div class="card-body p-0">
            @forelse($notifikasis as $notif)
                @php
                    [$notifIcon, $notifColor] = match ($notif->tipe) {
                        'forward' => ['bi-arrow-right-circle-fill', 'text-primary'],
                        'revisi' => ['bi-exclamation-octagon-fill', 'text-danger'],
                        'revisi_dikonfirmasi' => ['bi-check-circle-fill', 'text-success'],
                        'revisi_selesai' => ['bi-arrow-repeat', 'text-success'],
                        default => ['bi-info-circle-fill', 'text-secondary'],
                    };
                @endphp
                <div class="d-flex align-items-start gap-3 p-3 border-bottom {{ $notif->is_read ? '' : 'notif-unread' }}">
                    <i class="bi {{ $notifIcon }} fs-3 {{ $notifColor }}"></i>
                    <div class="flex-grow-1">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <strong class="{{ $notif->is_read ? 'text-secondary' : 'text-dark' }}">{{ $notif->judul }}</strong>
                            <span class="badge bg-secondary-subtle text-secondary rounded-pill">{{ $notif->role_tujuan }}</span>
                            @if(!$notif->is_read)
                                <span class="badge bg-danger rounded-pill">Baru</span>
                            @endif
                        </div>
                        <p class="mb-1 text-muted small">{{ $notif->pesan }}</p>
                        <small class="text-muted">
                            {{ $notif->created_at?->translatedFormat('d M Y, H:i') }}
                            @if($notif->tiket)
                                &bull; No Tiket: <a href="{{ route('tracking.show', $notif->tiket->no_tiket) }}" target="_blank" class="text-decoration-none text-primary">{{ $notif->tiket->no_tiket }}</a>
                            @endif
                        </small>
                    </div>
                    <div class="text-end">
                        @if(!$notif->is_read)
                            <form method="POST" action="{{ route('notifikasi.tandaiBaca', $notif->id) }}">
                                @csrf
                                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-envelope-open me-1"></i> Buka</button>
                            </form>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary">Dibaca</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-bell-slash fs-1 d-block mb-2"></i>
                    Tidak ada notifikasi.
                </div>
            @endforelse
        </div>
    </div>

    <div class="mt-3">{{ $notifikasis->links() }}</div>
</div>
@endsection