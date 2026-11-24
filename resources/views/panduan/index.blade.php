<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panduan Lengkap SkripsiLab AI — Cari Judul, Jurnal DOI, Bab I–V, PPT &amp; Olah Data</title>
    <meta name="description"
        content="Pusat panduan resmi SkripsiLab AI: cara cari judul skripsi, cari jurnal ber-DOI, membuat proyek &amp; generate Bab I–V, buat PPT sidang, simulasi sidang AI, parafrase anti-Turnitin, dan olah data statistik setara SPSS.">
    <meta name="keywords"
        content="panduan skripsi ai, cara membuat skripsi otomatis, cara cari jurnal skripsi, cara buat ppt sidang, cara simulasi sidang, cara parafrase anti turnitin, cara olah data skripsi spss, skripsilab ai">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">
    <link rel="canonical" href="{{ url('/panduan') }}">
    <meta name="theme-color" content="#2563eb">
    <link rel="icon" type="image/svg+xml" href="/images/logo.svg">

    <meta property="og:type" content="website">
    <meta property="og:locale" content="id_ID">
    <meta property="og:site_name" content="SkripsiLab AI">
    <meta property="og:url" content="{{ url('/panduan') }}">
    <meta property="og:title" content="Panduan Lengkap SkripsiLab AI — 7 Modul Penulisan &amp; Sidang Skripsi">
    <meta property="og:description"
        content="Pelajari langkah demi langkah memakai SkripsiLab AI: Cari Judul, Cari Jurnal DOI, Generate Bab I–V, PPT Sidang, Simulasi Sidang, Parafrase Pro, dan Olah Data Statistik.">
    <meta property="og:image" content="{{ url('/images/logo.svg') }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Panduan Lengkap SkripsiLab AI — 7 Modul Penulisan &amp; Sidang Skripsi">
    <meta name="twitter:description"
        content="Panduan praktis menyusun skripsi dari nol hingga lulus sidang dengan SkripsiLab AI.">

    @php
        $collectionSchema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'CollectionPage',
                    'name' => 'Pusat Panduan SkripsiLab AI',
                    'url' => url('/panduan'),
                    'inLanguage' => 'id-ID',
                    'description' => 'Kumpulan 7 panduan lengkap penggunaan fitur SkripsiLab AI dari mencari judul, mencari jurnal, menyusun Bab I–V, parafrase anti-plagiarisme, olah data statistik, membuat PPT sidang, hingga simulasi sidang.',
                ],
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda', 'item' => url('/')],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Panduan', 'item' => url('/panduan')],
                    ],
                ],
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($collectionSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { max-width: 100%; overflow-x: clip; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; background: #f8fafc; color: #0f172a; line-height: 1.65; }
        a { text-decoration: none; color: inherit; }
        .container { max-width: 1120px; width: 100%; margin: 0 auto; padding: 0 20px; }
        .topbar { background: rgba(255,255,255,0.92); backdrop-filter: blur(12px); border-bottom: 1px solid #e2e8f0; position: sticky; top: 0; z-index: 40; }
        .topbar-inner { height: 64px; display: flex; align-items: center; justify-content: space-between; gap: 10px; }
        .brand { display: inline-flex; align-items: center; gap: 8px; font-weight: 800; font-size: 16px; color: #0f172a; white-space: nowrap; flex-shrink: 0; }
        .nav-links { display: flex; align-items: center; gap: 18px; font-size: 14px; font-weight: 500; color: #475569; }
        .nav-links a:hover, .nav-links a.active { color: #2563eb; }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 8px 16px; border-radius: 9px; font-size: 13.5px; font-weight: 600; transition: all 0.15s; white-space: nowrap; }
        .topbar-actions { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-outline { background: #fff; border: 1px solid #cbd5e1; color: #0f172a; }
        .btn-outline:hover { border-color: #2563eb; color: #2563eb; }
        .hero { padding: 54px 0 36px; text-align: center; }
        .eyebrow { display: inline-block; padding: 4px 12px; border-radius: 999px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; font-size: 12px; font-weight: 700; letter-spacing: 0.03em; text-transform: uppercase; margin-bottom: 14px; }
        .hero h1 { font-size: clamp(26px, 4vw, 42px); font-weight: 800; letter-spacing: -0.025em; line-height: 1.2; margin-bottom: 14px; color: #0f172a; }
        .hero p { max-width: 680px; margin: 0 auto 24px; color: #475569; font-size: 15.5px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 18px; padding-bottom: 56px; }
        .mobile-subbar { display: none; }
        .guide-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 22px; min-width: 0; display: flex; flex-direction: column; justify-content: space-between; transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease; }
        .guide-card:hover { transform: translateY(-2px); border-color: #93c5fd; box-shadow: 0 14px 30px -12px rgba(37, 99, 235, 0.16); }
        .card-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
        .num-pill { font-size: 12px; font-weight: 800; color: #1d4ed8; background: #eff6ff; padding: 3px 10px; border-radius: 999px; }
        .cat-pill { font-size: 11.5px; font-weight: 600; color: #64748b; }
        .guide-card h2 { font-size: 18px; font-weight: 700; line-height: 1.35; margin-bottom: 8px; color: #0f172a; }
        .guide-card p { font-size: 13.8px; color: #475569; margin-bottom: 16px; }
        .card-foot { display: flex; align-items: center; justify-content: space-between; padding-top: 12px; border-top: 1px solid #f1f5f9; font-size: 13px; font-weight: 600; color: #2563eb; }
        .footer { background: #fff; border-top: 1px solid #e2e8f0; padding: 26px 0; font-size: 13px; color: #64748b; }
        .footer-inner { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
        @media (max-width: 720px) {
            .nav-links { display: none; }
            .mobile-subbar { display: flex; gap: 6px; overflow-x: auto; padding: 8px 16px; background: #fff; border-bottom: 1px solid #e2e8f0; scrollbar-width: none; }
            .mobile-subbar a { white-space: nowrap; font-size: 12.5px; font-weight: 600; padding: 5px 11px; border-radius: 999px; background: #f8fafc; border: 1px solid #e2e8f0; color: #475569; }
            .mobile-subbar a.active { background: #eff6ff; border-color: #93c5fd; color: #1d4ed8; }
            .hero { padding: 34px 0 24px; }
            .container { padding: 0 14px; }
            .btn { padding: 7px 12px; font-size: 12.5px; }
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
            <nav class="nav-links" aria-label="Navigasi Panduan">
                <a href="{{ url('/') }}">Beranda</a>
                <a href="{{ url('/panduan') }}" class="active">Panduan</a>
                <a href="{{ url('/tools') }}">Tools Gratis (10)</a>
            </nav>
            <div class="topbar-actions">
                @auth
                    <a href="{{ route('app.create') }}" class="btn btn-primary">Workspace →</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline">Masuk</a>
                    <a href="{{ route('register') }}" class="btn btn-primary">Daftar Gratis</a>
                @endauth
            </div>
        </div>
    </header>
    <div class="mobile-subbar">
        <a href="{{ url('/') }}">Beranda</a>
        <a href="{{ url('/panduan') }}" class="active">Semua Panduan (7)</a>
        <a href="{{ url('/tools') }}">10 Tools Gratis</a>
    </div>

    <main>
        <section class="hero">
            <div class="container">
                <div class="eyebrow">Pusat Panduan &amp; Dokumentasi Resmi</div>
                <h1>Panduan Lengkap Menyusun Skripsi dengan SkripsiLab AI</h1>
                <p>Ikuti 7 alur terstruktur dari pencarian ide judul hingga simulasi sidang penguji agar naskah Proposal &amp; Skripsimu rapi, valid secara metodologi, dan bebas plagiasi.</p>
                <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
                    <a href="{{ auth()->check() ? route('app.create') : route('register') }}" class="btn btn-primary">Mulai Susun Skripsi Sekarang →</a>
                    <a href="{{ url('/tools') }}" class="btn btn-outline">Coba 10 Tools Mahasiswa Gratis</a>
                </div>
            </div>
        </section>

        <section class="container">
            <div class="grid">
                @foreach($guides as $g)
                    <a href="{{ route('panduan.show', $g['slug']) }}" class="guide-card">
                        <div>
                            <div class="card-top">
                                <span class="num-pill">Panduan {{ $g['num'] }} · {{ $g['category'] }}</span>
                                <span class="cat-pill">{{ $g['reading_time'] }}</span>
                            </div>
                            <h2>{{ $g['title'] }}</h2>
                            <p>{{ $g['hero_subtitle'] }}</p>
                        </div>
                        <div class="card-foot">
                            <span>Baca Langkah Lengkap ({{ count($g['steps']) }} Tahap)</span>
                            <span>→</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="container footer-inner">
            <div>&copy; {{ date('Y') }} <strong>SkripsiLab AI</strong> &bull; Workspace Penulisan Proposal &amp; Skripsi Akademik.</div>
            <div style="display:flex;gap:16px;flex-wrap:wrap;">
                <a href="{{ url('/') }}">Beranda</a>
                <a href="{{ url('/panduan') }}">Panduan</a>
                <a href="{{ url('/tools') }}">Tools Mahasiswa</a>
                <a href="{{ url('/kebijakan-privasi') }}">Kebijakan Privasi</a>
                <a href="{{ url('/syarat-ketentuan') }}">Syarat &amp; Ketentuan</a>
                <a href="{{ url('/tentang') }}">Tentang</a>
                <a href="{{ url('/register') }}">Daftar</a>
            </div>
        </div>
    </footer>
</body>
</html>
