<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $guide['meta_title'] }}</title>
    <meta name="description" content="{{ $guide['meta_description'] }}">
    <meta name="keywords" content="{{ $guide['keywords'] }}">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">
    <link rel="canonical" href="{{ url('/panduan/' . $guide['slug']) }}">
    <meta name="theme-color" content="#2563eb">
    <link rel="icon" type="image/svg+xml" href="/images/logo.svg">

    <meta property="og:type" content="article">
    <meta property="og:locale" content="id_ID">
    <meta property="og:site_name" content="SkripsiLab AI">
    <meta property="og:url" content="{{ url('/panduan/' . $guide['slug']) }}">
    <meta property="og:title" content="{{ $guide['title'] }}">
    <meta property="og:description" content="{{ $guide['meta_description'] }}">
    <meta property="og:image" content="{{ url('/images/logo.svg') }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $guide['title'] }}">
    <meta name="twitter:description" content="{{ $guide['meta_description'] }}">

    @php
        $howToSteps = [];
        foreach ($guide['steps'] as $idx => $st) {
            $howToSteps[] = [
                '@type' => 'HowToStep',
                'position' => $idx + 1,
                'name' => $st['title'],
                'text' => $st['desc'],
            ];
        }
        $faqEntities = [];
        foreach ($guide['faqs'] as $fq) {
            $faqEntities[] = [
                '@type' => 'Question',
                'name' => $fq['q'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $fq['a']],
            ];
        }
        $detailSchema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'HowTo',
                    'name' => $guide['title'],
                    'description' => $guide['meta_description'],
                    'inLanguage' => 'id-ID',
                    'step' => $howToSteps,
                ],
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda', 'item' => url('/')],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Panduan', 'item' => url('/panduan')],
                        ['@type' => 'ListItem', 'position' => 3, 'name' => $guide['short_title'], 'item' => url('/panduan/' . $guide['slug'])],
                    ],
                ],
                ...(!empty($faqEntities) ? [[
                    '@type' => 'FAQPage',
                    'mainEntity' => $faqEntities,
                ]] : []),
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($detailSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { max-width: 100%; overflow-x: clip; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; background: #f8fafc; color: #0f172a; line-height: 1.7; }
        a { text-decoration: none; color: inherit; }
        .container { max-width: 1060px; width: 100%; margin: 0 auto; padding: 0 20px; }
        .topbar { background: rgba(255,255,255,0.92); backdrop-filter: blur(12px); border-bottom: 1px solid #e2e8f0; position: sticky; top: 0; z-index: 40; }
        .topbar-inner { height: 64px; display: flex; align-items: center; justify-content: space-between; gap: 10px; }
        .brand { display: inline-flex; align-items: center; gap: 8px; font-weight: 800; font-size: 16px; color: #0f172a; white-space: nowrap; flex-shrink: 0; }
        .nav-links { display: flex; align-items: center; gap: 18px; font-size: 14px; font-weight: 500; color: #475569; }
        .nav-links a:hover, .nav-links a.active { color: #2563eb; }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 9px 17px; border-radius: 9px; font-size: 13.5px; font-weight: 600; transition: all 0.15s; white-space: nowrap; }
        .topbar-actions { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-outline { background: #fff; border: 1px solid #cbd5e1; color: #0f172a; }
        .btn-outline:hover { border-color: #2563eb; color: #2563eb; }
        .layout { display: grid; grid-template-columns: 260px minmax(0, 1fr); gap: 26px; padding: 28px 0 60px; align-items: start; width: 100%; }
        .sidebar-box { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 14px; position: sticky; top: 82px; min-width: 0; max-width: 100%; }
        .sidebar-toggle-mobile { display: none; }
        .sidebar-title { font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; margin-bottom: 10px; }
        .side-link { display: flex; align-items: center; gap: 8px; padding: 8px 10px; border-radius: 8px; font-size: 13px; font-weight: 500; color: #334155; margin-bottom: 3px; }
        .side-link:hover { background: #f1f5f9; color: #0f172a; }
        .side-link.active { background: #eff6ff; color: #1d4ed8; font-weight: 700; }
        .mobile-subbar { display: none; }
        .article-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 18px; padding: 28px; min-width: 0; max-width: 100%; overflow-wrap: break-word; }
        .breadcrumb { display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: #64748b; margin-bottom: 12px; flex-wrap: wrap; }
        .breadcrumb a:hover { color: #2563eb; }
        .badge-row { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 10px; }
        .badge { font-size: 12px; font-weight: 700; padding: 3px 10px; border-radius: 999px; background: #eff6ff; color: #1d4ed8; }
        .badge-muted { font-size: 12px; font-weight: 600; padding: 3px 10px; border-radius: 999px; background: #f1f5f9; color: #475569; }
        h1 { font-size: clamp(22px, 3vw, 31px); font-weight: 800; line-height: 1.25; letter-spacing: -0.02em; margin-bottom: 10px; }
        .lead { font-size: 15px; color: #475569; margin-bottom: 22px; }
        .step-card { border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px 20px; margin-bottom: 14px; background: #ffffff; }
        .step-head { display: flex; align-items: center; gap: 12px; margin-bottom: 8px; }
        .step-num { width: 28px; height: 28px; border-radius: 999px; background: #2563eb; color: #fff; font-size: 13px; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .step-card h2 { font-size: 16px; font-weight: 700; color: #0f172a; line-height: 1.35; }
        .step-card p { font-size: 14.2px; color: #334155; margin-bottom: 10px; }
        .tip-box { font-size: 13px; background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 9px 12px; border-radius: 9px; }
        .warn-box { margin-top: 22px; padding: 16px 18px; border-radius: 14px; background: #fff7ed; border: 1px solid #fed7aa; }
        .warn-box h3 { font-size: 15px; font-weight: 700; color: #9a3412; margin-bottom: 8px; }
        .warn-box ul { padding-left: 18px; font-size: 13.8px; color: #7c2d12; }
        .warn-box li { margin-bottom: 4px; }
        .faq-section { margin-top: 24px; }
        .faq-section h3 { font-size: 16px; font-weight: 700; margin-bottom: 10px; }
        .faq-item { border: 1px solid #e2e8f0; border-radius: 11px; padding: 14px 16px; margin-bottom: 10px; }
        .faq-q { font-size: 14.2px; font-weight: 700; color: #0f172a; margin-bottom: 4px; }
        .faq-a { font-size: 13.8px; color: #475569; }
        .cta-banner { margin-top: 24px; padding: 20px; border-radius: 14px; background: linear-gradient(135deg, #eff6ff, #f8fafc); border: 1px solid #bfdbfe; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px; }
        .pager { margin-top: 22px; padding-top: 18px; border-top: 1px solid #e2e8f0; display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 10px; }
        .pager-card { display: flex; flex-direction: column; gap: 3px; padding: 12px 14px; border: 1px solid #e2e8f0; border-radius: 12px; background: #f8fafc; transition: all 0.15s ease; min-width: 0; }
        .pager-card:hover { border-color: #93c5fd; background: #eff6ff; }
        .pager-card.next { text-align: right; align-items: flex-end; }
        .pager-kicker { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #64748b; }
        .pager-title { font-size: 13.5px; font-weight: 700; color: #2563eb; line-height: 1.35; }
        .footer { background: #fff; border-top: 1px solid #e2e8f0; padding: 22px 0; font-size: 13px; color: #64748b; }
        .footer-inner { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
        @media (max-width: 900px) {
            .nav-links { display: none; }
            .mobile-subbar { display: flex; gap: 6px; overflow-x: auto; padding: 8px 20px; background: #fff; border-bottom: 1px solid #e2e8f0; scrollbar-width: none; }
            .mobile-subbar::-webkit-scrollbar { display: none; }
            .mobile-subbar a { white-space: nowrap; font-size: 12.5px; font-weight: 600; padding: 5px 11px; border-radius: 999px; background: #f8fafc; border: 1px solid #e2e8f0; color: #475569; }
            .mobile-subbar a.active { background: #eff6ff; border-color: #93c5fd; color: #1d4ed8; }
            .layout { grid-template-columns: minmax(0, 1fr); gap: 12px; padding: 16px 0 44px; }
            .sidebar-box { position: static; padding: 8px 10px; border-radius: 12px; width: 100%; overflow: hidden; }
            .sidebar-title, .sidebar-tools-link { display: none; }
            .sidebar-nav-list { display: flex; gap: 6px; width: 100%; overflow-x: auto; padding-bottom: 2px; scrollbar-width: none; -webkit-overflow-scrolling: touch; }
            .sidebar-nav-list::-webkit-scrollbar { display: none; }
            .side-link { margin-bottom: 0; white-space: nowrap; flex-shrink: 0; padding: 6px 11px; border: 1px solid #e2e8f0; background: #f8fafc; font-size: 12.5px; }
            .side-link.active { border-color: #93c5fd; }
            .article-card { padding: 18px 16px; border-radius: 14px; }
        }
        @media (max-width: 640px) {
            .cta-banner { flex-direction: column; align-items: stretch; padding: 16px; gap: 12px; }
            .cta-banner .btn { width: 100%; }
            .pager-card.next { text-align: left; align-items: flex-start; }
        }
        @media (max-width: 520px) {
            .container { padding: 0 14px; }
            .topbar-inner { height: 56px; }
            .brand { font-size: 15px; }
            .btn { padding: 7px 12px; font-size: 12.5px; }
            .step-card { padding: 14px; }
            .footer-inner { flex-direction: column; text-align: center; gap: 8px; }
        }
    </style>
</head>

<body>
    <header class="topbar">
        <div class="container topbar-inner">
            <a href="{{ url('/') }}" class="brand">
                <img src="/images/logo.svg" alt="SkripsiLab AI" style="width:28px;height:28px;border-radius:7px;">
                <span>SkripsiLab AI</span>
            </a>
            <nav class="nav-links" aria-label="Navigasi">
                <a href="{{ url('/') }}">Beranda</a>
                <a href="{{ url('/panduan') }}" class="active">Panduan</a>
                <a href="{{ auth()->check() ? route('tools.hub') : route('login') }}">Tools Mahasiswa</a>
                <a href="{{ url('/paket') }}">Paket</a>
            </nav>
            <div class="topbar-actions">
                @auth
                    <a href="{{ route('app.create', ['tab' => $guide['tab']]) }}" class="btn btn-primary">Buka Workspace →</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline">Masuk</a>
                    <a href="{{ route('register') }}" class="btn btn-primary">Daftar Gratis</a>
                @endauth
            </div>
        </div>
    </header>
    <div class="mobile-subbar">
        <a href="{{ url('/') }}">Beranda</a>
        <a href="{{ url('/panduan') }}" class="active">Semua Panduan</a>
        <a href="{{ auth()->check() ? route('tools.hub') : route('login') }}">10 Tools Mahasiswa</a>
        <a href="{{ url('/paket') }}">Paket</a>
    </div>

    <main class="container">
        <div class="layout">
            <aside class="sidebar-box">
                <div class="sidebar-title">7 Panduan SkripsiLab AI</div>
                <div class="sidebar-nav-list">
                    @foreach($guides as $item)
                        <a href="{{ route('panduan.show', $item['slug']) }}" class="side-link {{ $item['slug'] === $guide['slug'] ? 'active' : '' }}">
                            <span style="font-weight:700;opacity:0.75;">{{ $item['num'] }}.</span>
                            <span>{{ $item['short_title'] }}</span>
                        </a>
                    @endforeach
                </div>
                <div class="sidebar-tools-link" style="margin-top:12px;padding-top:12px;border-top:1px solid #f1f5f9;">
                    <a href="{{ auth()->check() ? route('tools.hub') : route('login') }}" class="side-link">10 Tools Mahasiswa →</a>
                </div>
            </aside>

            <article class="article-card">
                <div class="breadcrumb">
                    <a href="{{ url('/') }}">Beranda</a> <span>/</span>
                    <a href="{{ url('/panduan') }}">Panduan</a> <span>/</span>
                    <span style="color:#0f172a;font-weight:600;">{{ $guide['short_title'] }}</span>
                </div>

                <div class="badge-row">
                    <span class="badge">Panduan {{ $guide['num'] }} · {{ $guide['category'] }}</span>
                    <span class="badge-muted">{{ $guide['reading_time'] }}</span>
                </div>

                <h1>{{ $guide['title'] }}</h1>
                <p class="lead">{{ $guide['hero_subtitle'] }}</p>

                @foreach($guide['steps'] as $i => $st)
                    <div class="step-card">
                        <div class="step-head">
                            <span class="step-num">{{ $i + 1 }}</span>
                            <h2>{{ $st['title'] }}</h2>
                        </div>
                        <p>{{ $st['desc'] }}</p>
                        @if(!empty($st['tip']))
                            <div class="tip-box"><strong>Tips:</strong> {{ $st['tip'] }}</div>
                        @endif
                    </div>
                @endforeach

                @if(!empty($guide['mistakes']))
                    <div class="warn-box">
                        <h3>Kesalahan Umum yang Sebaiknya Dihindari</h3>
                        <ul>
                            @foreach($guide['mistakes'] as $ms)
                                <li>{{ $ms }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(!empty($guide['faqs']))
                    <div class="faq-section">
                        <h3>Pertanyaan yang Sering Ditanyakan</h3>
                        @foreach($guide['faqs'] as $fq)
                            <div class="faq-item">
                                <div class="faq-q">{{ $fq['q'] }}</div>
                                <div class="faq-a">{{ $fq['a'] }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="cta-banner">
                    <div style="min-width:0;flex:1 1 240px;">
                        <div style="font-size:15px;font-weight:800;color:#0f172a;">Siap langsung mempraktikkannya di skripsimu?</div>
                        <div style="font-size:13.3px;color:#475569;">Daftar gratis atau buka langsung modul {{ $guide['short_title'] }} di Workspace SkripsiLab AI.</div>
                    </div>
                    <a href="{{ auth()->check() ? route('app.create', ['tab' => $guide['tab']]) : route('register') }}" class="btn btn-primary">{{ $guide['cta_label'] }}</a>
                </div>

                <div class="pager">
                    @if($prevGuide)
                        <a href="{{ route('panduan.show', $prevGuide['slug']) }}" class="pager-card">
                            <span class="pager-kicker">← Panduan Sebelumnya</span>
                            <span class="pager-title">{{ $prevGuide['num'] }}. {{ $prevGuide['short_title'] }}</span>
                        </a>
                    @else
                        <a href="{{ url('/panduan') }}" class="pager-card">
                            <span class="pager-kicker">← Indeks Panduan</span>
                            <span class="pager-title">Semua 7 Panduan</span>
                        </a>
                    @endif

                    @if($nextGuide)
                        <a href="{{ route('panduan.show', $nextGuide['slug']) }}" class="pager-card next">
                            <span class="pager-kicker">Panduan Berikutnya →</span>
                            <span class="pager-title">{{ $nextGuide['num'] }}. {{ $nextGuide['short_title'] }}</span>
                        </a>
                    @else
                        <a href="{{ auth()->check() ? route('tools.hub') : route('login') }}" class="pager-card next">
                            <span class="pager-kicker">Lanjut Jelajahi →</span>
                            <span class="pager-title">10 Tools Mahasiswa</span>
                        </a>
                    @endif
                </div>
            </article>
        </div>
    </main>

    <footer class="footer">
        <div class="container footer-inner">
            <div>&copy; {{ date('Y') }} <strong>SkripsiLab AI</strong> &bull; Panduan Akademik Mahasiswa Indonesia.</div>
            <div style="display:flex;gap:16px;flex-wrap:wrap;justify-content:center;">
                <a href="{{ url('/') }}">Beranda</a>
                <a href="{{ url('/panduan') }}">Semua Panduan</a>
                <a href="{{ auth()->check() ? route('tools.hub') : route('login') }}">Tools Mahasiswa</a>
                <a href="{{ url('/kebijakan-privasi') }}">Kebijakan Privasi</a>
                <a href="{{ url('/syarat-ketentuan') }}">Syarat &amp; Ketentuan</a>
                <a href="{{ url('/tentang') }}">Tentang</a>
            </div>
        </div>
    </footer>
</body>
</html>
