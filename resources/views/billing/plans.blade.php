<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Paket Harga &amp; Tier Akun — SkripsiLab AI</title>
    <meta name="description"
        content="Daftar paket harga SkripsiLab AI mulai Rp15.000 tanpa sistem kredit: paket kuota naskah atau unlimited lengkap dengan ekspor Word, PDF, PPT sidang, dan Olah Data AI.">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">
    <link rel="canonical" href="{{ url('/paket') }}">
    <link rel="icon" type="image/svg+xml" href="/images/logo.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f8fafc;
            color: #0f172a;
            min-height: 100vh;
        }
        .topbar {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 14px 20px;
            position: sticky;
            top: 0;
            z-index: 40;
        }
        .topbar-inner {
            max-width: 1160px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }
        .brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #0f172a;
            font-weight: 800;
            font-size: 16px;
        }
        .wrap {
            max-width: 1160px;
            margin: 0 auto;
            padding: 28px 20px 64px;
        }
        .btn-nav {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            padding: 9px 15px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            border: 1px solid #cbd5e1;
            color: #0f172a;
            background: #ffffff;
            white-space: nowrap;
            transition: background 0.15s, border-color 0.15s;
        }
        .btn-nav:hover {
            background: #eff6ff;
            border-color: #2563eb;
            color: #1d4ed8;
        }
        .btn-nav.primary {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #ffffff;
            border-color: #1d4ed8;
        }
        .btn-nav.primary:hover {
            filter: brightness(1.06);
            color: #ffffff;
        }
        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        @media (max-width: 640px) {
            .wrap { padding: 18px 14px 48px; }
            .topbar { padding: 12px 14px; }
            .topbar-actions { width: 100%; }
            .topbar-actions .btn-nav { flex: 1 1 130px; }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="topbar-inner">
            <a href="{{ url('/') }}" class="brand">
                <img src="/images/logo.svg" alt="SkripsiLab AI" width="30" height="30" style="border-radius:8px;">
                <span>SkripsiLab AI</span>
            </a>
            <div class="topbar-actions">
                @if(!empty($summary) && empty($summary['is_paid_active']))
                    <span class="btn-nav" style="background:#fffbeb; border-color:#fde68a; color:#b45309; cursor:default; font-weight:700;">Paket Free</span>
                @endif
                <a href="{{ auth()->check() ? route('tools.hub') : route('login') }}" class="btn-nav">10 Tools Mahasiswa</a>
                @auth
                    <a href="{{ route('app.create') }}" class="btn-nav primary">Kembali ke Workspace →</a>
                @else
                    <a href="{{ route('login') }}" class="btn-nav">Masuk</a>
                    <a href="{{ route('register') }}" class="btn-nav primary">Daftar Gratis</a>
                @endauth
            </div>
        </div>
    </header>

    <main class="wrap">
        <div style="margin-bottom: 20px;">
            <span style="display:inline-block; font-size:11.5px; font-weight:700; padding:4px 10px; border-radius:999px; background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe;">
                HARGA RAMAH MAHASISWA · TANPA SISTEM KREDIT
            </span>
            <h1 style="font-size: 26px; font-weight: 800; margin: 10px 0 6px; letter-spacing: -0.02em;">
                Paket Harga &amp; Tier Akun
            </h1>
            <p style="font-size: 14px; color: #475569; max-width: 680px; margin: 0;">
                Bayar sekali sesuai kuota naskah atau pilih Unlimited — semua fitur aktif setelah konfirmasi Admin.
            </p>
        </div>

        @if(!empty($summary))
            @include('partials.billing_status_card', ['bs' => $summary, 'inWorkspace' => false])
        @endif

        @include('partials.pricing_catalog', ['plans' => $plans, 'billingSummary' => $summary])
    </main>

    @auth
        @include('partials.upgrade_checkout_modal', ['plans' => $plans])
    @endauth
</body>
</html>
