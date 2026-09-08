<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'LOKET 2026') â€” Sistem Pelayanan Pertanahan BMN Balam</title>

    <!-- Bootstrap 5.3 CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --bpn-navy: #0b2239;
            --bpn-navy-light: #163659;
            --bpn-gold: #c69214;
            --bpn-gold-hover: #b0810f;
            --bpn-bg: #f4f6f9;
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bpn-bg);
            color: #334155;
            min-height: 100vh;
        }
        /* Sidebar Styling */
        #sidebar {
            width: 260px;
            height: 100vh;
            background: linear-gradient(180deg, #0b2239 0%, #061524 100%);
            color: #fff;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            transition: all 0.3s;
            box-shadow: 4px 0 15px rgba(0,0,0,0.05);
        }
        #sidebar .brand-box {
            flex-shrink: 0;
            padding: 1.5rem 1.25rem;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        /* Menu sidebar dapat di-scroll jika item melebihi tinggi layar */
        #sidebar .sidebar-menu {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            overscroll-behavior: contain;
            scrollbar-width: thin;
            scrollbar-color: rgba(198,146,20,0.55) transparent;
        }
        #sidebar .sidebar-menu::-webkit-scrollbar { width: 6px; }
        #sidebar .sidebar-menu::-webkit-scrollbar-track { background: transparent; }
        #sidebar .sidebar-menu::-webkit-scrollbar-thumb { background: rgba(198,146,20,0.55); border-radius: 8px; }
        #sidebar .sidebar-menu::-webkit-scrollbar-thumb:hover { background: var(--bpn-gold); }
        #sidebar .nav-link {
            color: #cbd5e1;
            padding: 0.75rem 1.25rem;
            border-radius: 8px;
            margin: 0.2rem 0.75rem;
            font-weight: 500;
            font-size: 0.92rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.2s;
        }
        #sidebar .nav-link:hover {
            color: #fff;
            background: rgba(255,255,255,0.08);
        }
        #sidebar .nav-link.active {
            color: #fff;
            background: var(--bpn-gold);
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(198, 146, 20, 0.3);
        }
        #sidebar .nav-heading {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
            padding: 1.2rem 1.25rem 0.4rem;
            font-weight: 700;
        }
        /* Content Area */
        #main-content {
            margin-left: 260px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .topbar {
            background: #fff;
            padding: 0.85rem 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            position: sticky;
            top: 0;
            z-index: 999;
        }
        .card-custom {
            border: none;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.04);
            background: #fff;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-bpn {
            background: var(--bpn-navy);
            color: #fff;
            border: none;
            font-weight: 600;
        }
        .btn-bpn:hover {
            background: var(--bpn-navy-light);
            color: #fff;
        }
        .btn-gold {
            background: var(--bpn-gold);
            color: #fff;
            font-weight: 600;
            border: none;
        }
        .btn-gold:hover {
            background: var(--bpn-gold-hover);
            color: #fff;
        }
        .badge-stage {
            font-weight: 600;
            padding: 0.45em 0.85em;
            border-radius: 6px;
            font-size: 0.82rem;
        }
        /* Bab 9: badge notifikasi revisi dengan efek pulse */
        .notif-pulse {
            animation: notifPulse 1.6s ease-in-out infinite;
        }
        @keyframes notifPulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.6); }
            50% { box-shadow: 0 0 0 7px rgba(220, 53, 69, 0); }
        }
        /* Sidebar overlay backdrop */
        #sidebarOverlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.45);
            z-index: 999;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s;
        }
        #sidebarOverlay.active {
            opacity: 1;
            pointer-events: auto;
        }
        @media (max-width: 992px) {
            #sidebar {
                transform: translateX(-100%);
                box-shadow: none;
            }
            #sidebar.show {
                transform: translateX(0);
                box-shadow: 4px 0 20px rgba(0,0,0,0.3);
            }
            #main-content {
                margin-left: 0;
            }
            #main-content .topbar {
                padding: 0.85rem 1rem;
            }
            #main-content .container-fluid {
                padding-left: 1rem !important;
                padding-right: 1rem !important;
            }
        }
        @media (max-width: 576px) {
            #main-content .topbar {
                padding: 0.75rem 0.75rem;
            }
            #main-content .container-fluid {
                padding-left: 0.75rem !important;
                padding-right: 0.75rem !important;
            }
        }
    </style>
    @yield('styles')
</head>
<body>

    <!-- Sidebar Overlay (mobile) -->
    <div id="sidebarOverlay"></div>

    <!-- Sidebar Navigation -->
    <nav id="sidebar">
        <div class="brand-box d-flex align-items-center gap-3">
            <div class="bg-warning text-dark p-2 rounded-3 d-flex align-items-center justify-content-center" style="width:40px; height:40px;">
                <i class="bi bi-building-fill-check fs-4"></i>
            </div>
            <div>
                <h6 class="mb-0 text-white fw-bold tracking-wide">LOKET 2026</h6>
                <small class="text-secondary" style="font-size: 0.75rem;">Kantah BMN Kota Balam</small>
            </div>
        </div>

        <div class="py-2 sidebar-menu">
            <div class="nav-heading">Utama</div>
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2-fill"></i> Dashboard
            </a>
            <a href="{{ route('tracking.index') }}" target="_blank" class="nav-link">
                <i class="bi bi-qr-code-scan"></i> Portal Tracking Publik
            </a>
            <a href="{{ route('notifikasi.index') }}" class="nav-link {{ request()->routeIs('notifikasi.*') ? 'active' : '' }}">
                <i class="bi bi-bell-fill"></i> Notifikasi
                @if(($notifCounts['total'] ?? 0) > 0)
                    <span class="badge bg-danger rounded-pill ms-auto notif-pulse">
                        <i class="bi bi-bell-fill me-1"></i>{{ $notifCounts['total'] }}
                    </span>
                @endif
            </a>

            @if(auth()->user()->isAdmin() || auth()->user()->role === 'loket')
                <div class="nav-heading">Stage 1 : Loket</div>
                <a href="{{ route('loket.create') }}" class="nav-link {{ request()->routeIs('loket.create') ? 'active' : '' }}">
                    <i class="bi bi-plus-circle-fill"></i> Pendaftaran Baru
                </a>
                <a href="{{ route('loket.index') }}" class="nav-link {{ (request()->routeIs('loket.index') && !request()->filled('status')) || request()->routeIs('loket.show') ? 'active' : '' }}">
                    <i class="bi bi-ticket-detailed-fill"></i> Daftar Tiket Loket
                    @include('partials.notif-badge', ['role' => 'loket'])
                </a>
                <a href="{{ route('loket.index', ['status' => 'dikembalikan']) }}" class="nav-link {{ request()->routeIs('loket.index') && request('status') === 'dikembalikan' ? 'active' : '' }}">
                    <i class="bi bi-arrow-repeat"></i> Revisi
                    @if(($revisiCounts['loket'] ?? 0) > 0)
                        <span class="badge bg-danger rounded-pill ms-auto">{{ $revisiCounts['loket'] }}</span>
                    @endif
                    @include('partials.notif-badge', ['role' => 'loket'])
                </a>
                <a href="{{ route('loket.index', ['status' => 'verifikasi,warkah,validasi,alih_media,selesai']) }}" class="nav-link {{ request()->routeIs('loket.index') && request('status') === 'verifikasi,warkah,validasi,alih_media,selesai' ? 'active' : '' }}">
                    <i class="bi bi-check-circle-fill"></i> Selesai
                </a>
            @endif

            @if(auth()->user()->isAdmin() || auth()->user()->role === 'verifikator')
                <div class="nav-heading">Stage 2 : Verifikator</div>
                <a href="{{ route('verifikator.index') }}" class="nav-link {{ request()->routeIs('verifikator.*') ? 'active' : '' }}">
                    <i class="bi bi-clipboard-check-fill"></i> Verifikasi Berkas
                    @if(($revisiCounts['verifikator'] ?? 0) > 0)
                        <span class="badge bg-danger rounded-pill ms-auto">{{ $revisiCounts['verifikator'] }}</span>
                    @endif
                    @include('partials.notif-badge', ['role' => 'verifikator'])
                </a>
            @endif

            @if(auth()->user()->isAdmin() || auth()->user()->role === 'warkah')
                <div class="nav-heading">Stage 3 : Warkah</div>
                <a href="{{ route('warkah.index') }}" class="nav-link {{ request()->routeIs('warkah.*') ? 'active' : '' }}">
                    <i class="bi bi-archive-fill"></i> Lembar Kerja Warkah
                    @if(($revisiCounts['warkah'] ?? 0) > 0)
                        <span class="badge bg-danger rounded-pill ms-auto">{{ $revisiCounts['warkah'] }}</span>
                    @endif
                    @include('partials.notif-badge', ['role' => 'warkah'])
                </a>
            @endif

            @if(auth()->user()->isAdmin() || auth()->user()->isValidator())
                <div class="nav-heading">Stage 4 : Validator</div>
                <a href="{{ route('validator.index') }}" class="nav-link {{ request()->routeIs('validator.*') ? 'active' : '' }}">
                    <i class="bi bi-shield-check"></i> Validasi Data Pertanahan
                    @if(($revisiCounts['validator'] ?? 0) > 0)
                        <span class="badge bg-danger rounded-pill ms-auto">{{ $revisiCounts['validator'] }}</span>
                    @endif
                    @include('partials.notif-badge', ['role' => 'validator'])
                </a>
            @endif

            @if(auth()->user()->isAdmin() || auth()->user()->isAlihMedia())
                <div class="nav-heading">Stage 5 : Alih Media</div>
                <a href="{{ route('alih_media.index') }}" class="nav-link {{ request()->routeIs('alih_media.*') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-diff-fill"></i> Alih Media & Sertifikat
                    @if(($revisiCounts['alih_media'] ?? 0) > 0)
                        <span class="badge bg-danger rounded-pill ms-auto">{{ $revisiCounts['alih_media'] }}</span>
                    @endif
                    @include('partials.notif-badge', ['role' => 'alih_media'])
                </a>
            @endif

            @if(auth()->user()->isAdmin() || auth()->user()->isPembayaran())
                <div class="nav-heading">Stage 6 : Pembayaran</div>
                <a href="{{ route('pembayaran.index') }}" class="nav-link {{ request()->routeIs('pembayaran.*') ? 'active' : '' }}">
                    <i class="bi bi-credit-card-fill"></i> Pembayaran & Finalisasi
                    @if(($revisiCounts['pembayaran'] ?? 0) > 0)
                        <span class="badge bg-warning text-dark rounded-pill ms-auto">{{ $revisiCounts['pembayaran'] }}</span>
                    @endif
                    @include('partials.notif-badge', ['role' => 'pembayaran'])
                </a>
            @endif

            <div class="nav-heading">Monitoring & Laporan</div>
            <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-bar-graph-fill"></i> Laporan & Rekap SLA
            </a>

            @if(auth()->user()->isAdmin() || auth()->user()->role === 'pimpinan')
                <div class="nav-heading">Arsip</div>
                <a href="{{ route('arsip.index') }}" class="nav-link {{ request()->routeIs('arsip.*') ? 'active' : '' }}">
                    <i class="bi bi-archive-fill"></i> Arsip Tahunan
                </a>
            @endif


        </div>
    </nav>

    <!-- Main Content Wrapper -->
    <div id="main-content">
        <!-- Top Navbar -->
        <header class="topbar d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-sm btn-light d-lg-none" id="sidebarToggle">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <div class="d-none d-md-block">
                    <span class="text-muted small">Sistem Loket Pertanahan Elektronik BMN &bull; </span>
                    <span class="fw-semibold text-dark">{{ now()->translatedFormat('l, d F Y') }}</span>
                </div>
            </div>

            <!-- User Menu -->
            <div class="d-flex align-items-center gap-3">
                <div class="text-end d-none d-sm-block">
                    <div class="fw-bold text-dark mb-0">{{ auth()->user()->name }}</div>
                    @php
                        $roleDisplay = match(auth()->user()->role) {
                            'validator_btel' => 'Validator Pra-BTel',
                            'validator_suel' => 'Validator Pra-SuEl',
                            'alih_media_btel' => 'Alih Media Pra-BTel',
                            'alih_media_suel' => 'Alih Media Pra-SuEl',
                            'pembayaran' => 'Pembayaran',
                            default => ucfirst(auth()->user()->role),
                        };
                    @endphp
                    <span class="badge bg-primary text-uppercase" style="font-size:0.7rem;">{{ $roleDisplay }}</span>
                </div>
                <!-- Bab 9: Notification Bell -->
                @include('partials.notification-bell')

                <div class="dropdown">
                    <button class="btn btn-light rounded-circle p-2" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="width:42px; height:42px;">
                        <i class="bi bi-person-fill fs-5 text-secondary"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 py-2">
                        <li class="px-3 py-2 border-bottom">
                            <div class="fw-bold text-dark">{{ auth()->user()->name }}</div>
                            <small class="text-muted">{{ auth()->user()->username }} ({{ auth()->user()->email }})</small>
                        </li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST" class="m-0">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger py-2 mt-1">
                                    <i class="bi bi-box-arrow-right me-2"></i> Keluar Sistem
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Global Revision Banners -->
        @if(auth()->check() && (auth()->user()->isAdmin() || auth()->user()->role !== 'pimpinan'))
            @php
                $globalRevisis = \App\Models\Tiket::aktif()
                    ->whereHas('tiketRevisis', fn($q) => $q->where('status', 'aktif'))
                    ->with(['tiketRevisis' => fn($q) => $q->where('status', 'aktif')])
                    ->get()
                    ->pluck('tiketRevisis')
                    ->flatten()
                    ->groupBy('stage_tujuan');
            @endphp
            @foreach($globalRevisis as $stage => $revisis)
                <div class="container-fluid px-4">
                    <div class="alert alert-warning border-0 shadow-sm d-flex align-items-start mb-2" style="border-radius: 10px;">
                        <i class="bi bi-exclamation-triangle-fill fs-5 me-2 text-warning"></i>
                        <div>
                            <strong>{{ count($revisis) }} Revisi {{ $stage }}:</strong>
                            @foreach($revisis->take(3) as $rv)
                                <a href="{{ route(strtolower(str_replace(' ', '_', $stage)) . '.show', $rv->tiket_id) }}" class="text-decoration-underline">
                                    {{ $rv->tiket->no_tiket ?? '#' . $rv->tiket_id }}
                                </a>@if(!$loop->last), @endif
                            @endforeach
                            @if(count($revisis) > 3)
                                <span class="text-muted">...dan {{ count($revisis) - 3 }} lainnya</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        @endif

        <!-- Flash Alerts -->
        <div class="container-fluid px-4 pt-3">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm d-flex align-items-center" role="alert">
                    <i class="bi bi-check-circle-fill fs-5 me-2"></i>
                    <div>{{ session('success') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('warning'))
                <div class="alert alert-warning alert-dismissible fade show border-0 shadow-sm d-flex align-items-center" role="alert">
                    <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
                    <div>{{ session('warning') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error') || session('danger'))
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm d-flex align-items-center" role="alert">
                    <i class="bi bi-x-circle-fill fs-5 me-2"></i>
                    <div>{{ session('error') ?? session('danger') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
        </div>

        <!-- Page Body -->
        <main class="container-fluid px-4 py-3 flex-grow-1">
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="bg-white py-3 px-4 text-center border-top text-muted small mt-auto">
            &copy; {{ date('Y') }} Kantor Pertanahan Kota Bandar Lampung &bull; Sistem Loket Pelayanan Elektronik &bull; Versi 1.0 (Laravel 13)
        </footer>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const toggle  = document.getElementById('sidebarToggle');

            function openSidebar() {
                sidebar.classList.add('show');
                overlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
            function closeSidebar() {
                sidebar.classList.remove('show');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }

            toggle?.addEventListener('click', function() {
                if (sidebar.classList.contains('show')) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            });

            overlay?.addEventListener('click', closeSidebar);

            // Close sidebar on resize to desktop
            window.addEventListener('resize', function() {
                if (window.innerWidth > 992) {
                    closeSidebar();
                }
            });
        })();
    </script>
    @yield('scripts')
</body>
</html>
