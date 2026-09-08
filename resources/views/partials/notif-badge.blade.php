@php($roleBadge = $role ?? '')
@if(($revisiPendingCounts[$roleBadge] ?? 0) > 0)
    <span class="badge bg-danger rounded-pill ms-auto notif-pulse" title="Revisi menunggu konfirmasi">
        <i class="bi bi-bell-fill me-1"></i>{{ $revisiPendingCounts[$roleBadge] }}
    </span>
@elseif(($notifCounts[$roleBadge] ?? 0) > 0)
    <span class="badge bg-primary rounded-pill ms-auto" title="Notifikasi belum dibaca">{{ $notifCounts[$roleBadge] }}</span>
@endif