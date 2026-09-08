{{--
    Filter Tabs — Tahapan Paralel
    Usage: @include('partials.filter_tabs', ['route' => 'verifikator.index', 'filter' => $filter ?? null])
--}}
@php
    $current = $filter ?? 'active';
    $tabs = [
        'active' => ['label' => 'Aktif (Baru)', 'icon' => 'bi-inbox', 'class' => 'btn-outline-primary'],
        'revisi' => ['label' => 'Revisi', 'icon' => 'bi-arrow-repeat', 'class' => 'btn-outline-warning'],
        'done'   => ['label' => 'Selesai', 'icon' => 'bi-check2-all', 'class' => 'btn-outline-success'],
    ];
@endphp

<div class="d-flex flex-wrap gap-2 mb-3">
    @foreach($tabs as $key => $tab)
        <a href="{{ route($route, array_merge(['filter' => $key], request()->except(['filter', 'page']))) }}"
           class="btn btn-sm {{ $key === $current ? $tab['class'] . ' active' : 'btn-light border' }}">
            <i class="bi {{ $tab['icon'] }} me-1"></i>
            {{ $tab['label'] }}
        </a>
    @endforeach
</div>