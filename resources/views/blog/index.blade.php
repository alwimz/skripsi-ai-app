<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog SkripsiLab AI — Tips Skripsi, Jurnal DOI, Format A4 &amp; Persiapan Sidang</title>
    <meta name="description"
        content="Artikel edukasi mahasiswa dari SkripsiLab AI: cara menentukan judul skripsi cepat ACC, mencari jurnal ber-DOI, format margin 4-4-3-3, teknik parafrase EYD V, dan tips sidang skripsi.">
    <meta name="keywords"
        content="blog skripsi, tips skripsi cepat lulus, cara cari judul skripsi, format skripsi 4-4-3-3, cara cari jurnal doi, tips sidang skripsi, skripsilab ai">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">
    <link rel="canonical" href="{{ url('/blog') }}">
    <meta name="theme-color" content="#2563eb">
    <link rel="icon" type="image/png" sizes="512x512" href="/images/logo.png?v=3">
    <link rel="shortcut icon" href="/favicon.ico?v=3">
    <link rel="apple-touch-icon" href="/images/logo.png?v=3">

    <meta property="og:type" content="website">
    <meta property="og:locale" content="id_ID">
    <meta property="og:site_name" content="SkripsiLab AI">
    <meta property="og:url" content="{{ url('/blog') }}">
    <meta property="og:title" content="Blog SkripsiLab AI — Artikel &amp; Tips Penyusunan Skripsi Mahasiswa">
    <meta property="og:description"
        content="Kumpulan artikel praktis seputar penulisan proposal, skripsi Bab I–V, pencarian jurnal DOI, parafrase EYD V, dan simulasi sidang.">
    <meta property="og:image" content="{{ url('/images/logo.png?v=3') }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Blog SkripsiLab AI — Tips Skripsi &amp; Riset Mahasiswa">
    <meta name="twitter:description"
        content="Pelajari cara menyusun proposal &amp; skripsi lebih terstruktur dari penentuan judul hingga sidang.">

    @php
        $blogCollectionSchema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Blog',
                    'name' => 'Blog SkripsiLab AI',
                    'url' => url('/blog'),
                    'inLanguage' => 'id-ID',
                    'description' => 'Artikel panduan dan tips penyusunan proposal skripsi, penulisan Bab I–V, pencarian jurnal ber-DOI, format Word/PDF, dan persiapan sidang.',
                ],
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda', 'item' => url('/')],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => url('/blog')],
                    ],
                ],
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($blogCollectionSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>

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
        .brand { display: inline-flex; align-items: center; gap: 9px; font-weight: 800; font-size: 16px; color: #0f172a; white-space: nowrap; flex-shrink: 0; }
        .nav-links { display: flex; align-items: center; gap: 18px; font-size: 14px; font-weight: 500; color: #475569; }
        .nav-links a:hover, .nav-links a.active { color: #2563eb; }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 8px 16px; border-radius: 9px; font-size: 13.5px; font-weight: 600; transition: all 0.15s; white-space: nowrap; }
        .topbar-actions { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-outline { background: #fff; border: 1px solid #cbd5e1; color: #0f172a; }
        .btn-outline:hover { border-color: #2563eb; color: #2563eb; }
        .hero { padding: 52px 0 34px; text-align: center; }
        .eyebrow { display: inline-block; padding: 4px 12px; border-radius: 999px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; font-size: 12px; font-weight: 700; letter-spacing: 0.03em; text-transform: uppercase; margin-bottom: 14px; }
        .hero h1 { font-size: clamp(26px, 4vw, 40px); font-weight: 800; letter-spacing: -0.025em; line-height: 1.2; margin-bottom: 14px; color: #0f172a; }
        .hero p { max-width: 660px; margin: 0 auto 22px; color: #475569; font-size: 15.5px; }
        .blog-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(290px, 1fr)); gap: 18px; padding-bottom: 58px; }
        .blog-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 22px; min-width: 0; display: flex; flex-direction: column; justify-content: space-between; transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease; }
        .blog-card:hover { transform: translateY(-2px); border-color: #93c5fd; box-shadow: 0 14px 30px -12px rgba(37, 99, 235, 0.16); }
        .card-top { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 14px; flex-wrap: wrap; }
        .cat-badge { font-size: 11.5px; font-weight: 700; color: #1d4ed8; background: #eff6ff; padding: 3px 10px; border-radius: 999px; }
        .meta-time { font-size: 11.5px; font-weight: 600; color: #64748b; }
        .blog-card h2 { font-size: 18px; font-weight: 700; line-height: 1.38; margin-bottom: 10px; color: #0f172a; }
        .blog-card p { font-size: 13.8px; color: #475569; margin-bottom: 16px; }
        .card-foot { display: flex; align-items: center; justify-content: space-between; padding-top: 12px; border-top: 1px solid #f1f5f9; font-size: 13px; font-weight: 600; color: #2563eb; }
        @media (max-width: 720px) {
            .nav-links { display: none; }
            .hero { padding: 34px 0 24px; }
            .container { padding: 0 16px; }
            .btn { padding: 7px 12px; font-size: 12.5px; }
        }
    </style>
    @include('partials.meta_pixel')
</head>

<body>
    <header class="topbar">
        <div class="container topbar-inner">
            <a href="{{ url('/') }}" class="brand">
                <img src="/images/logo.png?v=3" alt="SkripsiLab AI" style="width:36px;height:36px;object-fit:contain;display:block;">
                <span>SkripsiLab AI</span>
            </a>
            <nav class="nav-links" aria-label="Navigasi Blog">
                <a href="{{ url('/blog') }}" class="active">Blog</a>
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

    <main>
        <section class="hero">
            <div class="container">
                <div class="eyebrow">Blog &amp; Artikel Akademik</div>
                <h1>Tips Skripsi, Referensi Jurnal &amp; Strategi Lulus Sidang</h1>
                <p>Kumpulan artikel praktis untuk mahasiswa Indonesia dalam menyusun proposal Bab I–III, skripsi penuh Bab I–V, format A4, parafrase EYD V, hingga persiapan menghadapi dosen penguji.</p>
            </div>
        </section>

        <section class="container">
            <div class="blog-grid">
                @foreach($articles as $art)
                    <a href="{{ route('blog.show', $art['slug']) }}" class="blog-card">
                        <div>
                            <div class="card-top">
                                <span class="cat-badge">{{ $art['category'] }}</span>
                                <span class="meta-time">{{ $art['published_label'] }} &bull; {{ $art['reading_time'] }}</span>
                            </div>
                            <h2>{{ $art['title'] }}</h2>
                            <p>{{ $art['excerpt'] }}</p>
                        </div>
                        <div class="card-foot">
                            <span>Baca Artikel Lengkap</span>
                            <span>→</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    </main>

    @include('partials.public_footer')
</body>
</html>
