<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $page['meta_title'] }}</title>
    <meta name="description" content="{{ $page['meta_description'] }}">
    <meta name="author" content="{{ $owner_name }} — {{ $brand_name }}">
    <meta name="robots" content="index, follow">
    <meta name="theme-color" content="#2563eb">
    <link rel="canonical" href="{{ url($page['slug']) }}">
    <link rel="icon" type="image/png" href="/images/logo.png">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">

    <meta property="og:type" content="website">
    <meta property="og:locale" content="id_ID">
    <meta property="og:site_name" content="{{ $brand_name }}">
    <meta property="og:url" content="{{ url($page['slug']) }}">
    <meta property="og:title" content="{{ $page['meta_title'] }}">
    <meta property="og:description" content="{{ $page['meta_description'] }}">
    <meta property="og:image" content="{{ url('/images/logo.png') }}">

    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ $page['meta_title'] }}">
    <meta name="twitter:description" content="{{ $page['meta_description'] }}">

    @php
        $legalSchema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'WebPage',
                    'name' => $page['title'],
                    'description' => $page['meta_description'],
                    'url' => url($page['slug']),
                    'inLanguage' => 'id-ID',
                    'isPartOf' => [
                        '@type' => 'WebSite',
                        'name' => $brand_name,
                        'url' => url('/'),
                    ],
                    'publisher' => [
                        '@type' => 'Person',
                        'name' => $owner_name,
                        'address' => [
                            '@type' => 'PostalAddress',
                            'addressLocality' => 'Makassar',
                            'addressRegion' => 'Sulawesi Selatan',
                            'addressCountry' => 'ID',
                        ],
                    ],
                ],
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        [
                            '@type' => 'ListItem',
                            'position' => 1,
                            'name' => 'Beranda',
                            'item' => url('/'),
                        ],
                        [
                            '@type' => 'ListItem',
                            'position' => 2,
                            'name' => $page['title'],
                            'item' => url($page['slug']),
                        ],
                    ],
                ],
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($legalSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --bg: #f8fafc;
            --surface: #ffffff;
            --border: #e2e8f0;
            --text: #0f172a;
            --muted: #475569;
            --subtle: #64748b;
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --primary-soft: #eff6ff;
        }
        html, body {
            max-width: 100%;
            overflow-x: clip;
        }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.65;
            -webkit-font-smoothing: antialiased;
        }
        a { color: inherit; }

        .topbar {
            position: sticky;
            top: 0;
            z-index: 40;
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
        }
        .topbar-inner {
            max-width: 1040px;
            width: 100%;
            margin: 0 auto;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .brand {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            text-decoration: none;
            font-weight: 800;
            font-size: 1.02rem;
            letter-spacing: -0.02em;
            white-space: nowrap;
            min-width: 0;
        }
        .brand img {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            display: block;
            flex-shrink: 0;
        }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: nowrap;
            flex-shrink: 0;
        }
        .nav-link {
            text-decoration: none;
            font-size: 0.84rem;
            font-weight: 600;
            color: var(--muted);
            padding: 7px 11px;
            border-radius: 9px;
            white-space: nowrap;
            transition: background 0.15s ease, color 0.15s ease;
        }
        .nav-link:hover {
            background: #f1f5f9;
            color: var(--text);
        }
        .nav-link.active {
            background: var(--primary-soft);
            color: var(--primary);
        }
        .nav-cta {
            text-decoration: none;
            font-size: 0.84rem;
            font-weight: 700;
            color: #ffffff;
            background: var(--primary);
            padding: 8px 14px;
            border-radius: 9px;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .nav-cta:hover { background: var(--primary-hover); }

        .page-wrap {
            max-width: 1040px;
            width: 100%;
            margin: 0 auto;
            padding: 28px 20px 56px;
            display: grid;
            grid-template-columns: minmax(0, 260px) minmax(0, 1fr);
            gap: 24px;
            align-items: start;
        }
        .page-wrap > aside,
        .page-wrap > article {
            min-width: 0;
            max-width: 100%;
            width: 100%;
        }

        .side-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 18px;
            position: sticky;
            top: 76px;
            min-width: 0;
            max-width: 100%;
            width: 100%;
        }
        .side-label {
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--subtle);
            margin-bottom: 10px;
        }
        .side-nav {
            display: flex;
            flex-direction: column;
            gap: 4px;
            width: 100%;
            min-width: 0;
        }
        .side-nav::-webkit-scrollbar { display: none; }
        .side-item {
            display: block;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--muted);
            padding: 9px 12px;
            border-radius: 10px;
            transition: background 0.15s ease, color 0.15s ease;
        }
        .side-item:hover {
            background: #f1f5f9;
            color: var(--text);
        }
        .side-item.active {
            background: var(--primary-soft);
            color: var(--primary);
            font-weight: 700;
        }
        .side-divider {
            height: 1px;
            background: var(--border);
            margin: 14px 0;
        }
        .trust-box {
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 12px;
            font-size: 0.8rem;
            color: var(--muted);
        }
        .trust-box strong {
            display: block;
            color: var(--text);
            margin-bottom: 4px;
            font-size: 0.83rem;
        }
        .wa-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            width: 100%;
            margin-top: 10px;
            padding: 8px 12px;
            border-radius: 9px;
            background: #16a34a;
            color: #ffffff;
            text-decoration: none;
            font-size: 0.81rem;
            font-weight: 700;
        }
        .wa-btn:hover { background: #15803d; }

        .content-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 32px;
            box-shadow: 0 12px 30px -22px rgba(15, 23, 42, 0.25);
            min-width: 0;
            max-width: 100%;
            overflow-wrap: break-word;
            word-wrap: break-word;
        }
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8rem;
            color: var(--subtle);
            margin-bottom: 14px;
            flex-wrap: wrap;
        }
        .breadcrumb a {
            text-decoration: none;
            color: var(--muted);
        }
        .breadcrumb a:hover { color: var(--primary); }

        .hero-meta {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            font-size: 0.76rem;
            font-weight: 700;
            color: var(--primary);
            background: var(--primary-soft);
            padding: 4px 10px;
            border-radius: 999px;
        }
        .updated {
            font-size: 0.78rem;
            color: var(--subtle);
            font-weight: 500;
        }
        h1 {
            font-size: clamp(1.5rem, 3vw, 2.05rem);
            font-weight: 800;
            letter-spacing: -0.03em;
            line-height: 1.25;
            margin-bottom: 10px;
        }
        .subtitle {
            font-size: 0.98rem;
            color: var(--muted);
            padding-bottom: 22px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 24px;
        }

        .section-list {
            display: flex;
            flex-direction: column;
            gap: 22px;
        }
        .legal-section {
            padding: 20px 22px;
            border: 1px solid var(--border);
            border-radius: 14px;
            background: #ffffff;
            min-width: 0;
            max-width: 100%;
            overflow-wrap: anywhere;
        }
        .legal-section h2 {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.05rem;
            font-weight: 700;
            letter-spacing: -0.015em;
            margin-bottom: 10px;
            color: var(--text);
        }
        .sec-num {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 26px;
            height: 26px;
            border-radius: 8px;
            background: var(--primary-soft);
            color: var(--primary);
            font-size: 0.8rem;
            font-weight: 800;
            flex-shrink: 0;
        }
        .legal-section p {
            font-size: 0.93rem;
            color: #334155;
            margin-bottom: 10px;
        }
        .legal-section p:last-child { margin-bottom: 0; }
        .legal-section ul {
            margin: 8px 0 4px 20px;
            display: flex;
            flex-direction: column;
            gap: 7px;
        }
        .legal-section li {
            font-size: 0.91rem;
            color: #334155;
        }

        .bottom-banner {
            margin-top: 26px;
            padding: 18px 20px;
            border-radius: 14px;
            background: linear-gradient(135deg, #eff6ff 0%, #f8fafc 100%);
            border: 1px solid #dbeafe;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
        }
        .bottom-banner p {
            font-size: 0.88rem;
            color: var(--muted);
        }
        .bottom-banner strong {
            display: block;
            color: var(--text);
            font-size: 0.95rem;
            margin-bottom: 2px;
        }
        .banner-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .btn-outline {
            text-decoration: none;
            font-size: 0.84rem;
            font-weight: 600;
            color: var(--text);
            background: #ffffff;
            border: 1px solid var(--border);
            padding: 8px 13px;
            border-radius: 9px;
        }
        .btn-outline:hover { border-color: #94a3b8; }

        .site-footer {
            border-top: 1px solid var(--border);
            background: #ffffff;
            padding: 20px;
            text-align: center;
            font-size: 0.82rem;
            color: var(--subtle);
        }
        .footer-links {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 8px;
        }
        .footer-links a {
            text-decoration: none;
            color: var(--muted);
            font-weight: 600;
        }
        .footer-links a:hover { color: var(--primary); }

        @media (max-width: 840px) {
            .page-wrap {
                grid-template-columns: minmax(0, 1fr);
                padding: 16px 14px 40px;
                gap: 14px;
            }
            .side-card {
                position: static;
                padding: 14px;
            }
            .side-nav {
                flex-direction: row;
                overflow-x: auto;
                gap: 6px;
                padding-bottom: 2px;
                scrollbar-width: none;
                -webkit-overflow-scrolling: touch;
            }
            .side-item {
                white-space: nowrap;
                flex-shrink: 0;
                padding: 8px 11px;
                font-size: 0.83rem;
            }
            .content-card {
                padding: 20px 16px;
                border-radius: 16px;
            }
            .legal-section {
                padding: 15px;
            }
            .legal-section h2 {
                font-size: 0.98rem;
                align-items: flex-start;
            }
        }
        @media (max-width: 640px) {
            .topbar-inner { padding: 10px 14px; gap: 8px; }
            .nav-links .hide-xs { display: none; }
            .brand { font-size: 0.94rem; gap: 7px; }
            .brand img { width: 27px; height: 27px; }
            .nav-link { padding: 6px 9px; font-size: 0.8rem; }
            .nav-cta { padding: 7px 11px; font-size: 0.8rem; }
            h1 { font-size: 1.35rem; }
            .subtitle { font-size: 0.9rem; padding-bottom: 16px; margin-bottom: 18px; }
            .legal-section p, .legal-section li { font-size: 0.88rem; }
            .bottom-banner { padding: 14px; }
            .banner-actions { width: 100%; }
            .banner-actions a { flex: 1 1 auto; text-align: center; justify-content: center; }
        }
    </style>
    @include('partials.meta_pixel')
</head>

<body>
    <header class="topbar">
        <div class="topbar-inner">
            <a href="{{ url('/') }}" class="brand">
                <img src="/images/logo.png" alt="SkripsiLab AI">
                <span>{{ $brand_name }}</span>
            </a>
            <nav class="nav-links" aria-label="Navigasi Utama">
                <a href="{{ url('/') }}" class="nav-link">Beranda</a>
                <a href="{{ auth()->check() ? route('app.create', ['tab' => 'panduan']) . '#panduan' : url('/panduan') }}" class="nav-link hide-xs">Panduan</a>
                <a href="{{ auth()->check() ? route('tools.hub') : route('login') }}" class="nav-link hide-xs">Tools Mahasiswa</a>
                <a href="{{ url('/paket') }}" class="nav-link hide-xs">Paket</a>
                <a href="{{ url('/kebijakan-privasi') }}" class="nav-link hide-xs {{ $page['key'] === 'privacy' ? 'active' : '' }}">Privasi</a>
                <a href="{{ url('/syarat-ketentuan') }}" class="nav-link hide-xs {{ $page['key'] === 'terms' ? 'active' : '' }}">Ketentuan</a>
                <a href="{{ url('/tentang') }}" class="nav-link hide-xs {{ $page['key'] === 'about' ? 'active' : '' }}">Tentang</a>
                <a href="{{ route('login') }}" class="nav-cta">Masuk</a>
            </nav>
        </div>
    </header>

    <main class="page-wrap">
        <aside aria-label="Dokumen Legal dan Informasi Resmi">
            <div class="side-card">
                <div class="side-label">Legal &amp; Transparansi</div>
                <nav class="side-nav">
                    <a href="{{ url('/kebijakan-privasi') }}" class="side-item {{ $page['key'] === 'privacy' ? 'active' : '' }}">Kebijakan Privasi</a>
                    <a href="{{ url('/syarat-ketentuan') }}" class="side-item {{ $page['key'] === 'terms' ? 'active' : '' }}">Syarat &amp; Ketentuan</a>
                    <a href="{{ url('/tentang') }}" class="side-item {{ $page['key'] === 'about' ? 'active' : '' }}">Tentang &amp; Pengembang</a>
                    <a href="{{ auth()->check() ? route('app.create', ['tab' => 'panduan']) . '#panduan' : url('/panduan') }}" class="side-item">Panduan Lengkap</a>
                </nav>
                <div class="side-divider"></div>
                <div class="trust-box">
                    <strong>Pengelola Resmi</strong>
                    <div>{{ $owner_name }}</div>
                    <div>{{ $location }}</div>
                    <div style="margin-top:4px;font-size:0.76rem;">Domain: <code>skripsilabai.com</code></div>
                    <a href="https://wa.me/{{ $whatsapp_number }}?text=Halo%20Admin%20SkripsiLab%20AI%2C%20saya%20ingin%20bertanya%20terkait%20privasi%20atau%20layanan." target="_blank" rel="noopener noreferrer" class="wa-btn">
                        Hubungi Support Resmi
                    </a>
                </div>
            </div>
        </aside>

        <article class="content-card">
            <div class="breadcrumb">
                <a href="{{ url('/') }}">Beranda</a>
                <span>/</span>
                <span>{{ $page['title'] }}</span>
            </div>

            <div class="hero-meta">
                <span class="badge">{{ $page['badge'] }}</span>
                <span class="updated">Diperbarui: {{ $last_updated }}</span>
            </div>

            <h1>{{ $page['title'] }}</h1>
            <p class="subtitle">{{ $page['subtitle'] }}</p>

            <div class="section-list">
                @foreach($page['sections'] as $sec)
                    <section class="legal-section">
                        <h2>
                            <span class="sec-num">{{ $sec['num'] }}</span>
                            <span>{{ $sec['title'] }}</span>
                        </h2>
                        @foreach($sec['paragraphs'] as $paragraph)
                            <p>{{ $paragraph }}</p>
                        @endforeach
                        @if(!empty($sec['bullets']))
                            <ul>
                                @foreach($sec['bullets'] as $bullet)
                                    <li>{{ $bullet }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </section>
                @endforeach
            </div>

            <div class="bottom-banner">
                <div>
                    <strong>Butuh klarifikasi atau bantuan akun?</strong>
                    <p>Anda dapat membaca panduan penggunaan lengkap atau menghubungi pengelola resmi.</p>
                </div>
                <div class="banner-actions">
                    <a href="{{ auth()->check() ? route('app.create', ['tab' => 'panduan']) . '#panduan' : url('/panduan') }}" class="btn-outline">Baca Panduan</a>
                    <a href="{{ auth()->check() ? route('app.create') : route('register') }}" class="nav-cta">{{ auth()->check() ? 'Buka Workspace →' : 'Daftar Gratis →' }}</a>
                </div>
            </div>
        </article>
    </main>

    @include('partials.public_footer')
</body>

</html>
