<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — Sistem Loket Pertanahan Elektronik BMN BALAM</title>
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
            background: linear-gradient(135deg, #0b2239 0%, #173b61 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .login-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.25);
            max-width: 950px;
            width: 100%;
            overflow: hidden;
        }
        .btn-gold {
            background: var(--bpn-gold);
            color: #fff;
            font-weight: 600;
        }
        .btn-gold:hover {
            background: #b0810f;
            color: #fff;
        }
        .role-pill {
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.8rem;
        }
        .role-pill:hover {
            background-color: #e2e8f0 !important;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

<div class="login-card row g-0">
    <!-- Left Column: Branding -->
    <div class="col-lg-5 p-4 p-md-5 d-flex flex-column justify-content-between text-white" style="background: linear-gradient(180deg, #07192c 0%, #0c2b4d 100%);">
        <div>
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="bg-warning text-dark p-2 rounded-3">
                    <i class="bi bi-building-fill-check fs-2"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0">LOKET 2026</h5>
                    <small class="text-white-50">Kantah Kota Bandar Lampung</small>
                </div>
            </div>
            <h4 class="fw-bold mb-3 text-warning">Sistem Loket Pertanahan Elektronik</h4>
            <p class="text-white-50 small leading-relaxed">
                Platform digitalisasi permohonan berkas BMN & umum terintegrasi dari Loket, Verifikator, Warkah, Validator hingga Alih Media Sertifikat Elektronik.
            </p>
        </div>

        <div class="mt-4 pt-3 border-top border-secondary">
            <div class="d-flex align-items-center gap-2 text-warning mb-2">
                <i class="bi bi-shield-lock-fill"></i>
                <span class="small fw-semibold">Akses Terenkripsi & Aman</span>
            </div>
            <a href="{{ route('tracking.index') }}" class="btn btn-sm btn-outline-light w-100 rounded-pill mt-2">
                <i class="bi bi-qr-code-scan me-1"></i> Buka Portal Tracking Publik
            </a>
        </div>
    </div>

    <!-- Right Column: Login Form -->
    <div class="col-lg-7 p-4 p-md-5 bg-white">
        <h4 class="fw-bold text-dark mb-1">Masuk ke Sistem</h4>
        <p class="text-muted small mb-4">Gunakan akun terdaftar sesuai peran operasional Anda.</p>

        @if(session('error'))
            <div class="alert alert-danger py-2 small">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="alert alert-success py-2 small">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger py-2 small">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('login.submit') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label class="form-label small fw-semibold text-muted">Username atau Email</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-secondary"></i></span>
                    <input type="text" name="username" id="usernameInput" class="form-control bg-light border-start-0" placeholder="Masukkan username..." value="{{ old('username') }}" required autofocus>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-semibold text-muted">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-key text-secondary"></i></span>
                    <input type="password" name="password" id="passwordInput" class="form-control bg-light border-start-0" placeholder="Masukkan password..." required>
                </div>
            </div>

            <button type="submit" class="btn btn-gold w-100 py-2 rounded-3 shadow-sm mb-3">
                <i class="bi bi-box-arrow-in-right me-1"></i> Masuk Sekarang
            </button>
        </form>

        <!-- Quick Login Helper for Testing -->
        <div class="mt-4 pt-3 border-top">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="small fw-bold text-muted"><i class="bi bi-lightning-charge-fill text-warning"></i> Akun Uji Coba (Klik Cepat):</span>
            </div>
            <div class="d-flex flex-wrap gap-1">
                <span class="badge bg-light text-dark border role-pill" onclick="quickFill('admin', 'admin123')">👑 Admin</span>
                <span class="badge bg-light text-dark border role-pill" onclick="quickFill('loket1', 'loket123')">🎫 Loket</span>
                <span class="badge bg-light text-dark border role-pill" onclick="quickFill('verifikator1', 'verif123')">📑 Verifikator</span>
                <span class="badge bg-light text-dark border role-pill" onclick="quickFill('warkah1', 'warkah123')">📁 Warkah</span>
                <span class="badge bg-light text-dark border role-pill" onclick="quickFill('validator1', 'valid123')">🔍 Validator</span>
                <span class="badge bg-light text-dark border role-pill" onclick="quickFill('alih1', 'alih123')">💾 Alih Media</span>
                <span class="badge bg-light text-dark border role-pill" onclick="quickFill('pimpinan', 'pimpinan123')">👔 Pimpinan</span>
            </div>
        </div>
    </div>
</div>

<script>
    function quickFill(user, pass) {
        document.getElementById('usernameInput').value = user;
        document.getElementById('passwordInput').value = pass;
    }
</script>

</body>
</html>
