<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tracking Status Berkas — Loket Pertanahan Kantah Balam</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --bpn-navy: #0b2239;
            --bpn-gold: #c69214;
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
        }
        .hero-banner {
            background: linear-gradient(135deg, #0b2239 0%, #163e66 100%);
            color: #fff;
            padding: 3.5rem 1rem 4.5rem;
            text-align: center;
        }
        .search-box-card {
            margin-top: -35px;
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.08);
            border: none;
            background: #fff;
        }
        .card-custom {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
            background: #fff;
        }
        .stepper-item {
            position: relative;
            text-align: center;
            flex: 1;
        }
        .stepper-circle {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <!-- Hero Header -->
    <div class="hero-banner">
        <div class="container">
            <div class="d-flex justify-content-center align-items-center gap-2 mb-3">
                <i class="bi bi-building-fill-check fs-2 text-warning"></i>
                <h5 class="mb-0 fw-bold tracking-wide">Kantor Pertanahan Kota Bandar Lampung</h5>
            </div>
            <h2 class="fw-bold mb-2">Pelacakan Status Berkas Pertanahan</h2>
            <p class="text-white-50 mx-auto" style="max-width: 600px;">
                Pantau proses penyelesaian permohonan sertifikat dan berkas pertanahan Anda secara transparan dan real-time.
            </p>
            <div class="mt-3">
                <a href="{{ route('login') }}" class="btn btn-sm btn-outline-light rounded-pill px-3">
                    <i class="bi bi-shield-lock me-1"></i> Login Petugas / Internal Kantah
                </a>
            </div>
        </div>
    </div>

    <!-- Main Container -->
    <div class="container py-4" style="max-width: 900px;">
        <!-- Search Card -->
        <div class="card search-box-card p-4 mb-4">
            <form action="{{ route('tracking.index') }}" method="GET">
                <div class="row g-2 align-items-center">
                    <div class="col-md-9">
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-qr-code-scan text-primary"></i></span>
                            <input type="text" name="no_tiket" class="form-control bg-light border-start-0" placeholder="Masukkan Nomor Tiket..." value="{{ $noTiket }}" required autofocus>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-warning w-100 py-3 fw-bold shadow-sm" style="background: var(--bpn-gold); color:#fff;">
                            <i class="bi bi-search me-1"></i> Lacak Berkas
                        </button>
                    </div>
                </div>
            </form>
        </div>

        @if($noTiket && !$tiket)
            <div class="card card-custom p-5 text-center my-4">
                <i class="bi bi-exclamation-octagon text-danger fs-1 mb-2"></i>
                <h5 class="fw-bold text-dark">Nomor Tiket Tidak Ditemukan</h5>
                <p class="text-muted small mb-0">Nomor tiket <strong>{{ $noTiket }}</strong> tidak terdaftar dalam sistem. Pastikan format nomor tiket sudah sesuai dengan lembar tanda terima Anda.</p>
            </div>
        @endif

        @if($tiket)
            <!-- Ticket Info Card -->
            <div class="card card-custom p-4 mb-4 border-top border-4 border-primary">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-3 border-bottom gap-2">
                    <div>
                        <span class="badge bg-secondary mb-1">Kode Tiket Resmi</span>
                        <h3 class="fw-bold text-dark mb-0">{{ $tiket->no_tiket }}</h3>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-{{ $tiket->status_badge }} fs-6 px-3 py-2">{{ $tiket->status_label }}</span>
                        @if($tiket->status_pembetulan !== 'P0')
                            <span class="badge bg-warning text-dark fs-6">{{ $tiket->status_pembetulan }}</span>
                        @endif
                    </div>
                </div>

                <!-- 5 Stage Stepper Progress -->
                @php
                    $stages = [
                        'diterima' => ['label' => '1. Loket', 'icon' => 'bi-inbox-fill'],
                        'verifikasi' => ['label' => '2. Verifikasi', 'icon' => 'bi-clipboard-check-fill'],
                        'warkah' => ['label' => '3. Warkah', 'icon' => 'bi-archive-fill'],
                        'validasi' => ['label' => '4. Validasi', 'icon' => 'bi-shield-check'],
                        'alih_media' => ['label' => '5. Alih Media', 'icon' => 'bi-file-earmark-diff-fill'],
                        'selesai' => ['label' => 'Selesai', 'icon' => 'bi-patch-check-fill'],
                    ];
                    $stageOrder = ['diterima', 'verifikasi', 'warkah', 'validasi', 'alih_media', 'selesai'];
                    $currentIndex = array_search($tiket->status, $stageOrder);
                    if ($currentIndex === false) $currentIndex = 1;
                @endphp

                <div class="py-3">
                    <div class="d-flex flex-nowrap justify-content-between text-center overflow-auto">
                        @foreach($stages as $key => $s)
                            @php
                                $stepIdx = array_search($key, $stageOrder);
                                $isPassed = $stepIdx <= $currentIndex;
                                $isCurrent = $tiket->status === $key;
                            @endphp
                            <div class="stepper-item" style="min-width: 72px;">
                                <div class="stepper-circle {{ $isCurrent ? 'bg-warning text-dark ring' : ($isPassed ? 'bg-success text-white' : 'bg-light text-muted border') }}">
                                    <i class="bi {{ $s['icon'] }}"></i>
                                </div>
                                <div class="small fw-semibold {{ $isCurrent ? 'text-dark fw-bold' : ($isPassed ? 'text-success' : 'text-muted') }}" style="font-size: 0.76rem;">
                                    {{ $s['label'] }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Info Table -->
                <div class="row g-3 mt-2">
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 h-100">
                            <h6 class="fw-bold text-dark mb-2"><i class="bi bi-person-fill text-primary"></i> Data Pemohon</h6>
                            <table class="table table-sm table-borderless small mb-0">
                                <tr>
                                    <td class="text-muted" style="width: 120px;">Nama Pemohon</td>
                                    <td class="fw-bold">: {{ $tiket->nama_pemohon }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Tgl. Masuk</td>
                                    <td>: {{ $tiket->tanggal_masuk->format('d F Y') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 h-100">
                            <h6 class="fw-bold text-dark mb-2"><i class="bi bi-briefcase-fill text-warning"></i> Layanan Pertanahan</h6>
                            <table class="table table-sm table-borderless small mb-0">
                                <tr>
                                    <td class="text-muted" style="width: 120px;">Jenis Layanan</td>
                                    <td class="fw-bold">: {{ $tiket->jenisPermohonan->nama }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Target SLA</td>
                                    <td>: {{ $tiket->tanggal_target_selesai?->format('d F Y') ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Sertifikat Elektronik Box (Jika Selesai) -->
                @if($tiket->status === 'selesai')
                    <div class="alert alert-success mt-4 d-flex align-items-center gap-3">
                        <i class="bi bi-patch-check-fill fs-1 text-success"></i>
                        <div>
                            <h6 class="fw-bold mb-1 text-success">Sertifikat Elektronik Telah Terbit!</h6>
                            <p class="small mb-0">Permohonan Anda telah selesai diproses dan Sertifikat Elektronik telah ditandatangani. Silakan datang ke Loket Pengambilan atau unduh melalui akun resmi Anda.</p>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Timeline Card -->
            <div class="card card-custom p-4 mb-4">
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-clock-history text-secondary me-2"></i>Riwayat Perjalanan Berkas</h6>
                <div class="list-group list-group-flush small">
                    @foreach($tiket->riwayatStatuses as $r)
                        <div class="list-group-item px-0 py-2 border-bottom">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-primary">{{ $r->stage_ke }}</span>
                                <small class="text-muted">{{ $r->created_at->format('d/m/Y H:i') }} WIB</small>
                            </div>
                            <div class="text-muted">{{ $r->keterangan }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <footer class="text-center text-muted small py-4 mt-5 border-top bg-white">
        &copy; {{ date('Y') }} Kantor Pertanahan Kota Bandar Lampung &bull; Layanan Cepat, Transparan dan Akuntabel.
    </footer>

</body>
</html>
