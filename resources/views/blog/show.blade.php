<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $article['meta_title'] }}</title>
    <meta name="description" content="{{ $article['meta_description'] }}">
    <meta name="keywords" content="{{ $article['keywords'] }}">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">
    <link rel="canonical" href="{{ url('/blog/' . $article['slug']) }}">
    <meta name="theme-color" content="#2563eb">
    <link rel="icon" type="image/png" sizes="512x512" href="/images/logo.png?v=3">
    <link rel="shortcut icon" href="/favicon.ico?v=3">
    <link rel="apple-touch-icon" href="/images/logo.png?v=3">

    <meta property="og:type" content="article">
    <meta property="og:locale" content="id_ID">
    <meta property="og:site_name" content="SkripsiLab AI">
    <meta property="og:url" content="{{ url('/blog/' . $article['slug']) }}">
    <meta property="og:title" content="{{ $article['title'] }}">
    <meta property="og:description" content="{{ $article['meta_description'] }}">
    <meta property="og:image" content="{{ url('/images/logo.png?v=3') }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $article['title'] }}">
    <meta name="twitter:description" content="{{ $article['meta_description'] }}">

    @php
        $articleSchema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'BlogPosting',
                    'headline' => $article['title'],
                    'description' => $article['meta_description'],
                    'datePublished' => $article['published_at'],
                    'dateModified' => $article['published_at'],
                    'inLanguage' => 'id-ID',
                    'mainEntityOfPage' => url('/blog/' . $article['slug']),
                    'author' => [
                        '@type' => 'Organization',
                        'name' => 'SkripsiLab AI',
                    ],
                    'publisher' => [
                        '@type' => 'Organization',
                        'name' => 'SkripsiLab AI',
                        'logo' => [
                            '@type' => 'ImageObject',
                            'url' => url('/images/logo.png?v=3'),
                        ],
                    ],
                ],
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda', 'item' => url('/')],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => url('/blog')],
                        ['@type' => 'ListItem', 'position' => 3, 'name' => $article['title'], 'item' => url('/blog/' . $article['slug'])],
                    ],
                ],
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($articleSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { max-width: 100%; overflow-x: clip; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; background: #f8fafc; color: #0f172a; line-height: 1.72; }
        a { text-decoration: none; color: inherit; }
        .container { max-width: 1120px; width: 100%; margin: 0 auto; padding: 0 20px; }
        .topbar { background: rgba(255,255,255,0.94); backdrop-filter: blur(12px); border-bottom: 1px solid #e2e8f0; position: sticky; top: 0; z-index: 40; }
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
        .article-wrap { max-width: 820px; width: 100%; margin: 28px auto 56px; padding: 0 20px; }
        .article-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 34px; box-shadow: 0 12px 30px -22px rgba(15, 23, 42, 0.2); }
        .breadcrumb { display: flex; align-items: center; flex-wrap: wrap; gap: 6px; font-size: 12.5px; color: #64748b; margin-bottom: 14px; }
        .breadcrumb a:hover { color: #2563eb; }
        .meta-row { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 12px; }
        .badge { padding: 4px 11px; border-radius: 999px; background: #eff6ff; color: #1d4ed8; font-size: 12px; font-weight: 700; }
        .meta-date { font-size: 12.5px; color: #64748b; font-weight: 500; }
        .article-card h1 { font-size: clamp(23px, 3.2vw, 32px); font-weight: 800; line-height: 1.25; letter-spacing: -0.02em; margin-bottom: 14px; color: #0f172a; }
        .lead { font-size: 15.5px; color: #475569; padding-bottom: 20px; border-bottom: 1px solid #f1f5f9; margin-bottom: 26px; }
        .art-sec { margin-bottom: 24px; }
        .art-sec h2 { font-size: 19px; font-weight: 700; color: #0f172a; margin-bottom: 10px; line-height: 1.35; }
        .art-sec p { font-size: 15px; color: #334155; margin-bottom: 12px; }
        .art-sec ul { margin: 8px 0 12px 20px; display: flex; flex-direction: column; gap: 8px; }
        .art-sec li { font-size: 14.5px; color: #334155; }
        .cta-box { margin-top: 28px; padding: 22px; border-radius: 14px; background: linear-gradient(135deg, #eff6ff 0%, #f8fafc 100%); border: 1px solid #bfdbfe; display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; }
        .cta-box strong { display: block; font-size: 15.5px; color: #0f172a; margin-bottom: 3px; }
        .cta-box p { font-size: 13.5px; color: #475569; margin: 0; }
        .related-wrap { margin-top: 30px; }
        .related-title { font-size: 16px; font-weight: 800; color: #0f172a; margin-bottom: 12px; }
        .related-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; }
        .related-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; transition: border-color 0.15s ease; }
        .related-card:hover { border-color: #93c5fd; }
        .related-card span { display: block; font-size: 11px; font-weight: 700; color: #2563eb; margin-bottom: 4px; }
        .related-card strong { font-size: 13.8px; color: #0f172a; line-height: 1.4; display: block; }
        @media (max-width: 720px) {
            .nav-links { display: none; }
            .article-wrap { margin: 16px auto 40px; padding: 0 14px; }
            .article-card { padding: 20px 16px; border-radius: 16px; }
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

    <main class="article-wrap">
        <article class="article-card">
            <div class="breadcrumb">
                <a href="{{ url('/') }}">Beranda</a> <span>/</span>
                <a href="{{ url('/blog') }}">Blog</a> <span>/</span>
                <span style="color:#0f172a;font-weight:600;">{{ $article['category'] }}</span>
            </div>

            <div class="meta-row">
                <span class="badge">{{ $article['category'] }}</span>
                <span class="meta-date">{{ $article['published_label'] }} &bull; {{ $article['reading_time'] }}</span>
            </div>

            <h1>{{ $article['title'] }}</h1>
            <p class="lead">{{ $article['excerpt'] }}</p>

            @foreach($article['sections'] as $sec)
                <section class="art-sec">
                    <h2>{{ $sec['heading'] }}</h2>
                    @foreach($sec['paragraphs'] as $p)
                        <p>{{ $p }}</p>
                    @endforeach
                    @if(!empty($sec['bullets']))
                        <ul>
                            @foreach($sec['bullets'] as $b)
                                <li>{{ $b }}</li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endforeach

            <div class="cta-box">
                <div>
                    <strong>{{ $article['cta_title'] }}</strong>
                    <p>{{ $article['cta_desc'] }}</p>
                </div>
                <a href="{{ auth()->check() ? route('app.create') : route('register') }}" class="btn btn-primary">
                    {{ auth()->check() ? 'Buka Workspace →' : 'Coba Gratis Sekarang →' }}
                </a>
            </div>
        </article>

        @if(!empty($related))
            <section class="related-wrap" aria-label="Artikel Terkait">
                <h2 class="related-title">Artikel Lainnya di Blog SkripsiLab AI</h2>
                <div class="related-grid">
                    @foreach($related as $rel)
                        <a href="{{ route('blog.show', $rel['slug']) }}" class="related-card">
                            <span>{{ $rel['category'] }}</span>
                            <strong>{{ $rel['title'] }}</strong>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </main>

    @include('partials.public_footer')
</body>
</html>
