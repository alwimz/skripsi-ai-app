<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Paket Harga &amp; Tier Akun — SkripsiLab AI</title>
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
        .tier-banner {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
            color: #ffffff;
            border-radius: 18px;
            padding: 22px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 24px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.16);
        }
        .tier-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 999px;
            background: rgba(56, 189, 248, 0.18);
            border: 1px solid rgba(56, 189, 248, 0.4);
            color: #7dd3fc;
            font-size: 12px;
            font-weight: 700;
        }
        .btn-nav {
            padding: 8px 14px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            border: 1px solid #cbd5e1;
            color: #0f172a;
            background: #ffffff;
        }
        .btn-nav.primary {
            background: #2563eb;
            color: #ffffff;
            border-color: #2563eb;
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
            <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                <a href="{{ url('/tools') }}" class="btn-nav">10 Tools Gratis</a>
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
            <h1 style="font-size: 28px; font-weight: 800; margin: 10px 0 6px; letter-spacing: -0.02em;">
                Pilih Paket Kuota Naskah atau Unlimited
            </h1>
            <p style="font-size: 14.5px; color: #475569; max-width: 720px; margin: 0;">
                Tidak pakai hitungan kredit yang bikin pusing saat revisi. Bayar sekali untuk kuota naskah atau pilih Unlimited bulanan — revisi AI, parafrase, PPT sidang, dan download Word/PDF langsung aktif otomatis via QRIS Pakasir.
            </p>
        </div>

        @if(!empty($summary))
            <div class="tier-banner">
                <div>
                    <span class="tier-pill">Tier Akun Anda Saat Ini</span>
                    <div style="font-size: 22px; font-weight: 800; margin-top: 8px;">
                        {{ $summary['is_admin'] ? 'Administrator (Unlimited)' : ($summary['plan']['name'] ?? 'Free (Coba Dulu)') }}
                    </div>
                    <div style="font-size: 13px; color: #cbd5e1; margin-top: 4px;">
                        @if($summary['is_unlimited'])
                            Status: <strong style="color:#4ade80;">Unlimited Aktif</strong> · Berlaku hingga: <strong>{{ $summary['expires_at_label'] }}</strong>
                        @elseif($summary['is_paid_active'])
                            Sisa Kuota Proyek Full: <strong style="color:#4ade80;">{{ $summary['remaining_quota'] }} Naskah</strong> (Terpakai {{ $summary['quota_used'] }}/{{ $summary['quota_limit'] }}) · Berlaku hingga {{ $summary['expires_at_label'] }}
                        @else
                            Proyek Uji Coba Free: <strong>{{ $summary['free_project_used'] }}/1</strong> (Sampul, Bab I &amp; Daftar Pustaka) · Download Word/PDF terkunci
                        @endif
                    </div>
                </div>
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <a href="{{ route('app.create', ['tab' => 'referral']) }}" style="padding:10px 14px; border-radius:10px; background:rgba(255,255,255,0.12); color:#fff; text-decoration:none; font-size:13px; font-weight:600; border:1px solid rgba(255,255,255,0.2);">
                        🎁 Komisi Referral: Rp {{ number_format($summary['referral_stats']['balance'] ?? 0, 0, ',', '.') }}
                    </a>
                </div>
            </div>
        @endif

        @include('partials.pricing_catalog', ['plans' => $plans, 'billingSummary' => $summary])
    </main>

    @auth
        @include('partials.upgrade_checkout_modal', ['plans' => $plans])
    @endauth
</body>
</html>
