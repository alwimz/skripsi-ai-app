<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkripsiAI — Workspace Penulisan Skripsi & Proposal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --bg-main: #ffffff;
            --bg-subtle: #f8fafc;
            --bg-muted: #f1f5f9;
            --text-primary: #0f172a;
            --text-secondary: #334155;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --border-hover: #cbd5e1;
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --primary-soft: #eff6ff;
            --primary-border: #bfdbfe;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg-main);
            color: var(--text-primary);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .container {
            width: 100%;
            max-width: 1140px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* Navbar */
        .navbar {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-color);
            height: 66px;
            display: flex;
            align-items: center;
        }

        .nav-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            gap: 12px;
        }

        .brand {
            font-size: 17px;
            font-weight: 800;
            color: var(--text-primary);
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }

        .brand-mark {
            width: 32px;
            height: 32px;
            border-radius: 9px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 4px;
            list-style: none;
        }

        .nav-item {
            position: relative;
        }

        .nav-link, .nav-trigger {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 13px;
            font-size: 14px;
            font-weight: 500;
            color: var(--text-secondary);
            border-radius: 8px;
            border: none;
            background: transparent;
            cursor: pointer;
            font-family: inherit;
            transition: background 0.15s ease, color 0.15s ease;
            white-space: nowrap;
        }

        .nav-link:hover, .nav-trigger:hover, .nav-item:hover .nav-trigger {
            background: var(--primary-soft);
            color: var(--primary-hover);
        }

        .dropdown-panel {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            width: 560px;
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 16px;
            box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.12);
            padding: 14px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 6px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(6px);
            transition: all 0.16s ease;
        }

        .nav-item:hover .dropdown-panel,
        .nav-item:focus-within .dropdown-panel {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 10px;
            transition: background 0.12s ease;
        }

        .dropdown-item:hover {
            background: var(--primary-soft);
        }

        .dropdown-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: var(--primary-soft);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .dropdown-title {
            font-size: 13.5px;
            font-weight: 600;
            color: var(--text-primary);
            line-height: 1.35;
        }

        .dropdown-desc {
            font-size: 12px;
            color: var(--text-muted);
            line-height: 1.4;
            margin-top: 2px;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            font-size: 13.5px;
            font-weight: 600;
            padding: 9px 16px;
            border-radius: 9px;
            cursor: pointer;
            transition: all 0.15s ease;
            font-family: inherit;
            white-space: nowrap;
            border: 1px solid transparent;
        }

        .btn-ghost {
            color: var(--text-secondary);
            background: transparent;
        }

        .btn-ghost:hover {
            color: var(--primary);
            background: var(--primary-soft);
        }

        .btn-primary {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
        }

        .btn-primary:hover {
            background: var(--primary-hover);
            border-color: var(--primary-hover);
        }

        .btn-outline {
            background: #ffffff;
            color: var(--text-primary);
            border-color: var(--border-color);
        }

        .btn-outline:hover {
            background: var(--primary-soft);
            border-color: var(--primary-border);
            color: var(--primary-hover);
        }

        /* Hero Section */
        .hero {
            padding: 68px 0 56px;
            text-align: center;
            border-bottom: 1px solid var(--border-color);
            background: radial-gradient(circle at top, #eff6ff 0%, #ffffff 65%);
        }

        .hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12.5px;
            font-weight: 700;
            color: var(--primary);
            background: var(--primary-soft);
            border: 1px solid var(--primary-border);
            padding: 4px 12px;
            border-radius: 999px;
            margin-bottom: 14px;
        }

        .hero h1 {
            font-size: clamp(1.95rem, 3.8vw + 0.5rem, 3.1rem);
            font-weight: 800;
            letter-spacing: -0.03em;
            line-height: 1.18;
            color: var(--text-primary);
            max-width: 740px;
            margin: 0 auto 16px;
        }

        .hero p {
            font-size: 16px;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto 28px;
        }

        .hero-cta {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* Section Generic */
        .section {
            padding: 68px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .section-subtle {
            background: var(--bg-subtle);
        }

        .section-header {
            max-width: 660px;
            margin: 0 auto 32px;
            text-align: center;
        }

        .section-label {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--primary);
            margin-bottom: 6px;
            display: block;
        }

        .section-title {
            font-size: clamp(1.4rem, 2.4vw, 2rem);
            font-weight: 800;
            letter-spacing: -0.025em;
            color: var(--text-primary);
            margin-bottom: 10px;
        }

        .section-desc {
            font-size: 14.5px;
            color: var(--text-secondary);
        }

        /* Workspace Preview */
        .major-selector {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .major-pill {
            padding: 8px 16px;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 600;
            border: 1px solid var(--border-color);
            background: #ffffff;
            color: var(--text-secondary);
            cursor: pointer;
            font-family: inherit;
            transition: all 0.15s;
        }

        .major-pill:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .major-pill.active {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
        }

        .studio-frame {
            background: #eef2f7;
            border: 1px solid #cbd5e1;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 45px -15px rgba(15, 23, 42, 0.1);
            display: grid;
            grid-template-columns: 245px 1fr;
            min-height: 540px;
        }

        .studio-frame-sidebar {
            background: #ffffff;
            border-right: 1px solid #e2e8f0;
            padding: 16px 12px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .sf-title {
            font-size: 11.5px;
            font-weight: 700;
            color: #0f172a;
            text-transform: uppercase;
            line-height: 1.4;
        }

        .sf-sub {
            font-size: 10.5px;
            font-weight: 700;
            color: var(--primary);
            text-transform: uppercase;
            margin-top: 4px;
        }

        .sf-nav-btn {
            width: 100%;
            text-align: left;
            padding: 8px 10px;
            border-radius: 8px;
            border: 1px solid transparent;
            background: transparent;
            font-size: 12.5px;
            font-weight: 600;
            color: #475569;
            cursor: pointer;
            font-family: inherit;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .sf-nav-btn:hover {
            background: #f1f5f9;
        }

        .sf-nav-btn.active {
            background: var(--primary-soft);
            color: var(--primary-hover);
            border-color: var(--primary-border);
        }

        .studio-frame-main {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .sf-topbar {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 10px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }

        .sf-paper-area {
            padding: 24px;
            flex: 1;
            overflow-y: auto;
            max-height: 500px;
        }

        .sf-a4-paper {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            max-width: 720px;
            margin: 0 auto;
            padding: 40px 48px;
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 1.75;
            color: #0f172a;
            text-align: justify;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
        }

        .sf-a4-paper h4 {
            font-size: 12pt;
            font-weight: bold;
            margin: 14px 0 6px;
            text-indent: 0;
        }

        .sf-a4-paper p {
            text-indent: 1.27cm;
            margin-bottom: 10px;
        }

        .cite-blue {
            color: #2563eb;
            text-decoration: underline;
            text-underline-offset: 2px;
        }

        /* Sample Showcase Cards Gallery */
        .sample-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }

        .sample-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 16px;
            transition: transform 0.16s ease, box-shadow 0.16s ease, border-color 0.16s ease;
        }

        .sample-card:hover {
            transform: translateY(-2px);
            border-color: var(--primary-border);
            box-shadow: 0 12px 28px rgba(37, 99, 235, 0.08);
        }

        .sample-badges {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 9px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
        }

        .badge-blue {
            background: var(--primary-soft);
            color: var(--primary-hover);
            border: 1px solid var(--primary-border);
        }

        .badge-slate {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        .sample-card h3 {
            font-size: 14.5px;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1.45;
            margin-bottom: 8px;
        }

        .sample-meta {
            font-size: 12.5px;
            color: var(--text-muted);
            line-height: 1.5;
        }

        .sample-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            padding-top: 12px;
            border-top: 1px solid #f1f5f9;
        }

        .sample-btn {
            padding: 7px 11px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid var(--border-color);
            background: #ffffff;
            color: var(--text-primary);
            cursor: pointer;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
        }

        .sample-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: var(--primary-soft);
        }

        .sample-btn.primary {
            background: var(--primary);
            border-color: var(--primary);
            color: #ffffff;
        }

        .sample-btn.primary:hover {
            background: var(--primary-hover);
            color: #ffffff;
        }

        /* Features Grid */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }

        .feature-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 22px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: border-color 0.16s, box-shadow 0.16s, transform 0.16s;
        }

        .feature-card:hover {
            border-color: #bfdbfe;
            box-shadow: 0 10px 24px rgba(37, 99, 235, 0.08);
            transform: translateY(-2px);
        }

        .feature-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: var(--primary-soft);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
        }

        .feature-card h3 {
            font-size: 15.5px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .feature-card p {
            font-size: 13.5px;
            color: var(--text-secondary);
            margin-bottom: 14px;
        }

        .feature-link {
            font-size: 13px;
            font-weight: 700;
            color: var(--primary);
        }

        /* Native PDF Modal Dialog */
        dialog.pdf-dialog {
            margin: auto;
            width: min(94vw, 960px);
            height: 88vh;
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 0;
            overflow: hidden;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.25);
        }
        dialog.pdf-dialog::backdrop {
            background: rgba(15, 23, 42, 0.55);
            backdrop-filter: blur(3px);
        }
        .pdf-dialog-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 18px;
            border-bottom: 1px solid var(--border-color);
            background: #ffffff;
        }
        .pdf-dialog-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .pdf-dialog-frame {
            width: 100%;
            height: calc(88vh - 56px);
            border: none;
            background: #f1f5f9;
        }

        /* Footer */
        .footer {
            padding: 36px 0;
            background: var(--bg-subtle);
            font-size: 13px;
            color: var(--text-muted);
        }

        .footer-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        @media (max-width: 960px) {
            .studio-frame { grid-template-columns: 1fr; }
            .studio-frame-sidebar { border-right: none; border-bottom: 1px solid #e2e8f0; }
            .sample-grid { grid-template-columns: repeat(2, 1fr); }
            .features-grid { grid-template-columns: repeat(2, 1fr); }
            .nav-links { display: none; }
        }

        @media (max-width: 640px) {
            .container { padding: 0 16px; }
            .hero { padding: 48px 0 40px; }
            .section { padding: 48px 0; }
            .sample-grid { grid-template-columns: 1fr; }
            .features-grid { grid-template-columns: 1fr; }
            .sf-paper-area { padding: 12px; }
            .sf-a4-paper { padding: 22px 16px; font-size: 11pt; }
            .btn { padding: 8px 12px; font-size: 12.5px; }
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <header class="navbar">
        <div class="container nav-inner">
            <a href="/" class="brand">
                <img src="/images/logo.svg" alt="SkripsiAI" style="width: 32px; height: 32px; border-radius: 8px; display: block;">
                SkripsiAI
            </a>

            <ul class="nav-links">
                <li class="nav-item">
                    <button type="button" class="nav-trigger">
                        Fitur
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div class="dropdown-panel">
                        <a href="{{ auth()->check() ? route('app.create', ['tab' => 'titles']) : route('login') }}" class="dropdown-item">
                            <div class="dropdown-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            </div>
                            <div>
                                <div class="dropdown-title">Cari Judul Skripsi</div>
                                <div class="dropdown-desc">10 saran judul sesuai jurusan & metodologi</div>
                            </div>
                        </a>

                        <a href="{{ auth()->check() ? route('app.create', ['tab' => 'projects']) : route('login') }}" class="dropdown-item">
                            <div class="dropdown-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            </div>
                            <div>
                                <div class="dropdown-title">Workspace Proposal & Skripsi</div>
                                <div class="dropdown-desc">Susun Bab 1–3 atau Bab 1–5 + Lab Revisi</div>
                            </div>
                        </a>

                        <a href="{{ auth()->check() ? route('app.create', ['tab' => 'paraphrase']) : route('login') }}" class="dropdown-item">
                            <div class="dropdown-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                            </div>
                            <div>
                                <div class="dropdown-title">Parafrase Online</div>
                                <div class="dropdown-desc">Parafrase akademik, hasil natural & bebas plagiasi</div>
                            </div>
                        </a>

                        <a href="{{ auth()->check() ? route('app.create', ['tab' => 'journals']) : route('login') }}" class="dropdown-item">
                            <div class="dropdown-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                            </div>
                            <div>
                                <div class="dropdown-title">Cari Jurnal Nasional & Internasional</div>
                                <div class="dropdown-desc">Referensi OpenAlex & DOI siap sitasi APA/IEEE</div>
                            </div>
                        </a>

                        <a href="{{ auth()->check() ? route('app.create', ['tab' => 'ppt']) : route('login') }}" class="dropdown-item">
                            <div class="dropdown-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/></svg>
                            </div>
                            <div>
                                <div class="dropdown-title">PPT Sidang &amp; Slide Show</div>
                                <div class="dropdown-desc">13 tema akademik profesional &amp; ekspor .pptx</div>
                            </div>
                        </a>

                        <a href="{{ auth()->check() ? route('app.create', ['tab' => 'defense']) : route('login') }}" class="dropdown-item">
                            <div class="dropdown-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                            </div>
                            <div>
                                <div class="dropdown-title">Simulasi Sidang</div>
                                <div class="dropdown-desc">Latihan tanya-jawab penguji &amp; skor evaluasi</div>
                            </div>
                        </a>

                        <a href="{{ auth()->check() ? route('app.create', ['tab' => 'qa']) : route('login') }}" class="dropdown-item">
                            <div class="dropdown-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                            </div>
                            <div>
                                <div class="dropdown-title">Dosen AI (Konsultasi)</div>
                                <div class="dropdown-desc">Bedah metodologi, analisis data &amp; tanya jawab soal</div>
                            </div>
                        </a>
                    </div>
                </li>
                <li><a href="#workspace-preview" class="nav-link">Pratinjau Workspace</a></li>
                <li><a href="#contoh-hasil" class="nav-link">Contoh Hasil</a></li>
                <li><a href="#fitur-lengkap" class="nav-link">Semua Modul</a></li>
            </ul>

            <div class="nav-actions">
                @auth
                    <a href="{{ route('app.create') }}" class="btn btn-primary">Buka Workspace</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-ghost">Masuk</a>
                    <a href="{{ route('register') }}" class="btn btn-primary">Mulai Sekarang</a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Hero -->
    <section class="hero">
        <div class="container">
            <div class="hero-eyebrow">Workspace Penulisan Akademik Mahasiswa</div>
            <h1>Susun Proposal & Skripsi Lebih Terstruktur</h1>
            <p>Mulai dari mencari judul, menyusun proposal Bab 1–3 dan skripsi Bab 1–5 sesuai pedoman kampus, revisi per sub-bab di Lab Revisi, hingga membuat slide PPT dan simulasi sidang.</p>

            <div class="hero-cta">
                @auth
                    <a href="{{ route('app.create', ['tab' => 'projects']) }}" class="btn btn-primary">Buat Proyek Baru →</a>
                @else
                    <a href="{{ route('register') }}" class="btn btn-primary">Masuk ke Workspace →</a>
                @endauth
                <a href="#contoh-hasil" class="btn btn-outline">Lihat Contoh Proposal & Skripsi</a>
            </div>
        </div>
    </section>

    <!-- Section 1: Pratinjau Workspace Interaktif -->
    <section id="workspace-preview" class="section section-subtle">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Pratinjau Workspace</span>
                <h2 class="section-title">Tampilan Editor Kertas A4 yang Rapi & Terstandar</h2>
                <p class="section-desc">Coba pindah antarprogram studi dan bab di bawah ini untuk melihat langsung format penulisan kertas A4 lengkap dengan Daftar Pustaka otomatis.</p>
            </div>

            <div class="major-selector">
                <button type="button" class="major-pill active" onclick="switchSampleMajor('manajemen', this)">Manajemen (Kuantitatif)</button>
                <button type="button" class="major-pill" onclick="switchSampleMajor('it', this)">Teknik Informatika (R&D)</button>
                <button type="button" class="major-pill" onclick="switchSampleMajor('hukum', this)">Ilmu Hukum (Normatif)</button>
            </div>

            <div class="studio-frame">
                <div class="studio-frame-sidebar">
                    <div>
                        <div class="sf-title" id="sf-proj-title">PENGARUH LITERASI DIGITAL, REGULASI DIRI, DAN DUKUNGAN INSTITUSI...</div>
                        <div class="sf-sub">PROPOSAL SKRIPSI · 3 DARI 3 BAB</div>
                    </div>

                    <div style="height: 6px; background: #e2e8f0; border-radius: 999px; overflow: hidden;">
                        <div style="width: 100%; height: 100%; background: #2563eb;"></div>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 4px;">
                        <button type="button" class="sf-nav-btn active" id="sf-btn-bab1" onclick="switchSampleChapter('bab1')">
                            <span>Bab I Pendahuluan</span>
                            <span style="color: #059669;">●</span>
                        </button>
                        <button type="button" class="sf-nav-btn" id="sf-btn-bab2" onclick="switchSampleChapter('bab2')">
                            <span>Bab II Tinjauan Pustaka</span>
                            <span style="color: #059669;">●</span>
                        </button>
                        <button type="button" class="sf-nav-btn" id="sf-btn-bab3" onclick="switchSampleChapter('bab3')">
                            <span>Bab III Metodologi</span>
                            <span style="color: #059669;">●</span>
                        </button>
                        <button type="button" class="sf-nav-btn" id="sf-btn-dp" onclick="switchSampleChapter('dp')">
                            <span>Daftar Pustaka</span>
                            <span style="color: #059669;">●</span>
                        </button>
                    </div>

                    <div style="margin-top: auto; padding: 10px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 9px; font-size: 11.5px; color: #1e40af; line-height: 1.45;">
                        <strong style="display: flex; align-items: center; gap: 6px; margin-bottom: 3px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 2v7.31L4.62 17.6A2 2 0 0 0 6.3 20.5h11.4a2 2 0 0 0 1.68-2.9L14 9.31V2z"/></svg>
                            <span>Lab Revisi Sub-bab</span>
                        </strong>
                        Perbaiki sub-bab spesifik (misal 1.1 atau 2.1) sesuai catatan dosen pembimbing.
                    </div>
                </div>

                <div class="studio-frame-main">
                    <div class="sf-topbar">
                        <div style="font-size: 12px; font-weight: 600; color: #475569;">
                            Workspace Skripsi · Margin 4-4-3-3 · Times New Roman 12pt
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            <a id="sample-dl-docx" href="{{ route('sample.download', ['major' => 'manajemen', 'format' => 'docx']) }}" class="btn btn-outline" style="padding: 6px 12px; font-size: 12px; display: inline-flex; align-items: center; gap: 5px;">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                <span>Unduh Word (.docx)</span>
                            </a>
                            <a id="sample-dl-pdf" href="{{ route('sample.download', ['major' => 'manajemen', 'format' => 'pdf']) }}" class="btn btn-primary" style="padding: 6px 12px; font-size: 12px; display: inline-flex; align-items: center; gap: 5px;">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                                <span>Unduh PDF (.pdf)</span>
                            </a>
                        </div>
                    </div>

                    <div class="sf-paper-area">
                        <div class="sf-a4-paper" id="sf-paper-content"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 2: Contoh Hasil Proposal & Skripsi -->
    <section id="contoh-hasil" class="section">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Contoh Hasil Proposal & Skripsi</span>
                <h2 class="section-title">Galeri Contoh Naskah Siap Baca & Unduh</h2>
                <p class="section-desc">Setiap dokumen memiliki halaman sampul, Kata Pengantar, Daftar Isi dengan titik-titik rapi, Bab I–III / Bab I–V lengkap, dan Daftar Pustaka terverifikasi.</p>
            </div>

            <div class="major-selector" id="gallery-filter-bar">
                <button type="button" class="major-pill active" onclick="filterGallery('all', this)">Semua</button>
                <button type="button" class="major-pill" onclick="filterGallery('proposal', this)">Proposal (Bab 1–3)</button>
                <button type="button" class="major-pill" onclick="filterGallery('skripsi', this)">Full Skripsi (Bab 1–5)</button>
            </div>

            @php
                $curatedSamples = [
                    [
                        'major' => 'manajemen',
                        'stage' => 'proposal',
                        'stage_label' => 'Proposal (Bab 1–3)',
                        'prodi' => 'Manajemen',
                        'metode' => 'Kuantitatif (Survei / Regresi)',
                        'title' => 'Pengaruh Literasi Digital, Regulasi Diri, dan Dukungan Institusi Terhadap Kesiapan Kerja Mahasiswa',
                    ],
                    [
                        'major' => 'it',
                        'stage' => 'skripsi',
                        'stage_label' => 'Full Skripsi (Bab 1–5)',
                        'prodi' => 'Teknik Informatika',
                        'metode' => 'Research & Development (R&D)',
                        'title' => 'Implementasi Algoritma Random Forest dan Ekstraksi Fitur Citra untuk Klasifikasi Penyakit Daun Padi',
                    ],
                    [
                        'major' => 'hukum',
                        'stage' => 'proposal',
                        'stage_label' => 'Proposal (Bab 1–3)',
                        'prodi' => 'Ilmu Hukum',
                        'metode' => 'Yuridis Normatif',
                        'title' => 'Pelindungan Hukum Bagi Konsumen atas Kebocoran Data Pribadi pada Layanan Pinjaman Daring Berdasarkan UU PDP',
                    ],
                    [
                        'major' => 'kedokteran',
                        'stage' => 'skripsi',
                        'stage_label' => 'Full Skripsi (Bab 1–5)',
                        'prodi' => 'Kedokteran / Keperawatan',
                        'metode' => 'Kuantitatif Observasional Analitik',
                        'title' => 'Hubungan Kualitas Tidur dan Tingkat Stres Akademik dengan Kejadian Hipertensi pada Mahasiswa Tingkat Akhir',
                    ],
                    [
                        'major' => 'pendidikan',
                        'stage' => 'proposal',
                        'stage_label' => 'Proposal (Bab 1–3)',
                        'prodi' => 'Pendidikan (PGSD / FKIP)',
                        'metode' => 'Quasi-Eksperimen',
                        'title' => 'Efektivitas Model Pembelajaran Problem Based Learning Berbantuan Media Interaktif Terhadap Berpikir Kritis Siswa',
                    ],
                    [
                        'major' => 'teknik',
                        'stage' => 'skripsi',
                        'stage_label' => 'Full Skripsi (Bab 1–5)',
                        'prodi' => 'Teknik Sipil / Industri',
                        'metode' => 'Eksperimen & Uji Laboratorium',
                        'title' => 'Analisis Kuat Tekan Beton Ramah Lingkungan dengan Substitusi Parsial Abu Sekam Padi dan Limbah Plastik',
                    ],
                ];
            @endphp

            <div class="sample-grid" id="sample-cards-grid">
                @foreach ($curatedSamples as $item)
                    @php
                        $pdfUrl = route('sample.download', ['major' => $item['major'], 'format' => 'pdf']);
                        $docxUrl = route('sample.download', ['major' => $item['major'], 'format' => 'docx']);
                    @endphp
                    <article class="sample-card" data-stage="{{ $item['stage'] }}">
                        <div>
                            <div class="sample-badges">
                                <span class="badge badge-blue">{{ $item['stage_label'] }}</span>
                                <span class="badge badge-slate">{{ $item['prodi'] }}</span>
                            </div>
                            <h3>{{ $item['title'] }}</h3>
                            <div class="sample-meta">Metode: {{ $item['metode'] }}</div>
                        </div>
                        <div class="sample-actions">
                            <button type="button" class="sample-btn primary" onclick="openPdfModal(@json($item['title']), @json($pdfUrl . '?inline=1'))">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <span>Lihat PDF</span>
                            </button>
                            <a href="{{ $docxUrl }}" class="sample-btn">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                <span>.docx</span>
                            </a>
                            <a href="{{ $pdfUrl }}" class="sample-btn">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                <span>.pdf</span>
                            </a>
                        </div>
                    </article>
                @endforeach

                @if (isset($publicProjects) && count($publicProjects) > 0)
                    @foreach ($publicProjects as $proj)
                        @php
                            $projHas45 = !empty($proj->bab4_content) || !empty($proj->bab5_content);
                            $projStage = $projHas45 ? 'skripsi' : 'proposal';
                            $projPdfUrl = route('public.project.download', ['id' => $proj->id, 'format' => 'pdf']);
                            $projDocxUrl = route('public.project.download', ['id' => $proj->id, 'format' => 'docx']);
                        @endphp
                        <article class="sample-card" data-stage="{{ $projStage }}">
                            <div>
                                <div class="sample-badges">
                                    <span class="badge badge-blue">{{ $projHas45 ? 'Full Skripsi (Bab 1–5)' : 'Proposal (Bab 1–3)' }}</span>
                                    <span class="badge badge-slate">{{ $proj->prodi ?: 'Skripsi S1' }}</span>
                                </div>
                                <h3>{{ $proj->judul }}</h3>
                                <div class="sample-meta">Metode: {{ $proj->metode_penelitian ?: 'Akademik' }}</div>
                            </div>
                            <div class="sample-actions">
                                <button type="button" class="sample-btn primary" onclick="openPdfModal(@json($proj->judul), @json($projPdfUrl . '?inline=1'))">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    <span>Lihat PDF</span>
                                </button>
                                <a href="{{ $projDocxUrl }}" class="sample-btn">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                    <span>.docx</span>
                                </a>
                                <a href="{{ $projPdfUrl }}" class="sample-btn">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                    <span>.pdf</span>
                                </a>
                            </div>
                        </article>
                    @endforeach
                @endif
            </div>
        </div>
    </section>

    <!-- Section 3: Semua Fitur -->
    <section id="fitur-lengkap" class="section section-subtle">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Semua Modul SkripsiAI</span>
                <h2 class="section-title">Pendamping Lengkap dari Ide Judul hingga Sidang</h2>
                <p class="section-desc">Sembilan modul akademik terintegrasi dalam satu workspace untuk membantu menyusun proposal, skripsi penuh, referensi, presentasi, hingga persiapan sidang.</p>
            </div>

            <div class="features-grid">
                <div class="feature-card">
                    <div>
                        <div class="feature-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        </div>
                        <h3>Workspace Proposal &amp; Skripsi</h3>
                        <p>Atur pedoman kampus, logo universitas, font &amp; margin 4-4-3-3, lalu susun Bab I–III atau Bab I–V lengkap dengan Daftar Isi otomatis, Daftar Pustaka berlink DOI, serta ekspor Word (.docx) &amp; PDF (.pdf).</p>
                    </div>
                    <a href="{{ auth()->check() ? route('app.create', ['tab' => 'projects']) : route('login') }}" class="feature-link">Buka Workspace →</a>
                </div>

                <div class="feature-card">
                    <div>
                        <div class="feature-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        </div>
                        <h3>Cari Judul Penelitian</h3>
                        <p>Hasilkan 10 rekomendasi judul skripsi terbaru beserta rumusan masalah yang selaras dengan program studi dan 9 pilihan metode penelitian (Kuantitatif, Kualitatif, R&amp;D, PTK, Normatif, Mixed Method, dll.).</p>
                    </div>
                    <a href="{{ auth()->check() ? route('app.create', ['tab' => 'titles']) : route('login') }}" class="feature-link">Cari Judul →</a>
                </div>

                <div class="feature-card">
                    <div>
                        <div class="feature-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                        </div>
                        <h3>Cari Jurnal &amp; Referensi DOI</h3>
                        <p>Temukan artikel jurnal nasional &amp; internasional terverifikasi Crossref/OpenAlex tahun 2021–2026 lengkap dengan tautan DOI aktif, filter cakupan, dan tombol salin sitasi APA 7th siap pakai.</p>
                    </div>
                    <a href="{{ auth()->check() ? route('app.create', ['tab' => 'journals']) : route('login') }}" class="feature-link">Cari Jurnal →</a>
                </div>

                <div class="feature-card">
                    <div>
                        <div class="feature-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/></svg>
                        </div>
                        <h3>PPT Sidang Otomatis</h3>
                        <p>Pilih proyek skripsimu atau unggah file Word/PDF, pilih dari 13 tema akademik profesional, presentasikan Fullscreen Slide Show lengkap dengan catatan bicara, atau ekspor PowerPoint (.pptx).</p>
                    </div>
                    <a href="{{ auth()->check() ? route('app.create', ['tab' => 'ppt']) : route('login') }}" class="feature-link">Buat Slide PPT →</a>
                </div>

                <div class="feature-card">
                    <div>
                        <div class="feature-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                        </div>
                        <h3>Simulasi Sidang Skripsi</h3>
                        <p>Latihan menjawab 5 pertanyaan penguji yang dibuat langsung dari isi naskah Bab I–V atau unggahan Word/PDF, lengkap dengan skor per pertanyaan, grade akhir, dan contoh jawaban terbaik.</p>
                    </div>
                    <a href="{{ auth()->check() ? route('app.create', ['tab' => 'defense']) : route('login') }}" class="feature-link">Mulai Latihan →</a>
                </div>

                <div class="feature-card">
                    <div>
                        <div class="feature-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                        </div>
                        <h3>Parafrase Akademik</h3>
                        <p>Tulis ulang paragraf agar gaya bahasanya lebih ilmiah, kohesif, dan natural sesuai kaidah EYD V tanpa mengubah makna kutipan untuk menurunkan tingkat kemiripan teks.</p>
                    </div>
                    <a href="{{ auth()->check() ? route('app.create', ['tab' => 'paraphrase']) : route('login') }}" class="feature-link">Buka Parafrase →</a>
                </div>

                <div class="feature-card">
                    <div>
                        <div class="feature-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        </div>
                        <h3>Dosen AI (Konsultasi &amp; Bedah Soal)</h3>
                        <p>Konsultasi interaktif seputar metodologi penelitian, pemilihan uji statistik, kerangka teori, hingga unggah foto soal, tabel, atau output pengujian untuk dibedah langkah demi langkah.</p>
                    </div>
                    <a href="{{ auth()->check() ? route('app.create', ['tab' => 'qa']) : route('login') }}" class="feature-link">Tanya Dosen AI →</a>
                </div>

                <div class="feature-card">
                    <div>
                        <div class="feature-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M10 2v7.31L4.62 17.6A2 2 0 0 0 6.3 20.5h11.4a2 2 0 0 0 1.68-2.9L14 9.31V2z"/></svg>
                        </div>
                        <h3>Lab Revisi &amp; Olah Data Penelitian</h3>
                        <p>Perbaiki sub-bab spesifik sesuai catatan dosen pembimbing serta masukkan angka olah data riil &amp; rumus (SPSS, SmartPLS, Machine Learning, Eksperimen) agar langsung terintegrasi ke Bab III &amp; IV.</p>
                    </div>
                    <a href="{{ auth()->check() ? route('app.create', ['tab' => 'projects']) : route('login') }}" class="feature-link">Kelola Proyek →</a>
                </div>

                <div class="feature-card">
                    <div>
                        <div class="feature-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                        </div>
                        <h3>Lampiran Otomatis (Word &amp; PDF)</h3>
                        <p>Unggah kuesioner, surat izin, dokumentasi sistem, atau dokumen Word/PDF/gambar sebagai lampiran yang otomatis tercatat di Daftar Isi &amp; Daftar Lampiran serta ikut tercetak di Word &amp; PDF.</p>
                    </div>
                    <a href="{{ auth()->check() ? route('app.create', ['tab' => 'projects']) : route('login') }}" class="feature-link">Tambah Lampiran →</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container footer-inner">
            <div class="brand" style="font-size: 15px;">
                <img src="/images/logo.svg" alt="SkripsiAI" style="width: 26px; height: 26px; border-radius: 6px; display: block;">
                SkripsiAI
            </div>
            <div>&copy; {{ date('Y') }} SkripsiAI &bull; Demo by Muh Alwi Syahrir &bull; Workspace penulisan akademik mahasiswa Indonesia.</div>
        </div>
    </footer>

    <!-- Modal Preview PDF -->
    <dialog id="pdf-preview-dialog" class="pdf-dialog">
        <div class="pdf-dialog-head">
            <div class="pdf-dialog-title" id="pdf-dialog-title">Pratinjau Dokumen PDF</div>
            <button type="button" class="sample-btn" onclick="closePdfModal()">✕ Tutup</button>
        </div>
        <iframe id="pdf-dialog-frame" class="pdf-dialog-frame" title="Pratinjau PDF Skripsi"></iframe>
    </dialog>

    <script>
        const studioSamples = {
            manajemen: {
                title: 'PENGARUH LITERASI DIGITAL, REGULASI DIRI, DAN DUKUNGAN INSTITUSI TERHADAP KESIAPAN KERJA MAHASISWA',
                bab1: `
                    <h4>1.1 Latar Belakang Masalah</h4>
                    <p>Perkembangan teknologi informasi di era transformasi digital telah mengubah struktur kompetensi yang dibutuhkan oleh dunia industri secara fundamental. Menurut <span class="cite-blue">Naufal & Wijaya (2024)</span>, lulusan perguruan tinggi tidak lagi hanya dituntut menguasai pengetahuan teoretis pada bidang keilmuannya, melainkan juga harus memiliki kecakapan literasi digital serta kemampuan adaptasi mandiri yang tinggi.</p>
                    <p>Hasil studi pendahuluan yang dilakukan oleh <span class="cite-blue">Kusuma et al. (2023)</span> menunjukkan bahwa meskipun intensitas penggunaan perangkat digital di kalangan mahasiswa mencapai lebih dari 8 jam per hari, pemanfaatan teknologi untuk produktivitas akademik dan persiapan karier profesional masih belum optimal. Hal ini sejalan dengan temuan <span class="cite-blue">Pratama & Santoso (2024)</span> yang mengungkapkan adanya kesenjangan antara penguasaan teknologi dasar dengan kesiapan kerja lulusan baru.</p>
                    <h4>1.2 Rumusan Masalah</h4>
                    <p>1. Apakah literasi digital berpengaruh positif dan signifikan terhadap kesiapan kerja mahasiswa?</p>
                    <p>2. Apakah regulasi diri berpengaruh positif dan signifikan terhadap kesiapan kerja mahasiswa?</p>
                `,
                bab2: `
                    <h4>2.1 Landasan Teori</h4>
                    <p>Literasi digital didefinisikan oleh <span class="cite-blue">Rahmawati & Hidayat (2023)</span> sebagai kemampuan individu untuk memahami, mengevaluasi, dan mengintegrasikan informasi dalam berbagai format digital secara kritis dan produktif. Indikator literasi digital mencakup pencarian informasi daring, evaluasi kredibilitas konten, serta kolaborasi digital.</p>
                    <p>Regulasi diri (self-regulation) menurut <span class="cite-blue">Zimmerman (2020)</span> merupakan kapasitas psikologis individu dalam merencanakan, memantau, dan mengendalikan perilaku belajarnya guna mencapai target karier yang telah ditetapkan.</p>
                `,
                bab3: `
                    <h4>3.1 Jenis dan Desain Penelitian</h4>
                    <p>Penelitian ini menggunakan pendekatan kuantitatif asosiatif kausal dengan metode survei menggunakan instrumen kuesioner skala Likert 1 sampai 5 (<span class="cite-blue">Sugiyono, 2022</span>). Penentuan jumlah sampel menggunakan rumus Slovin sehingga diperoleh sebanyak 175 responden.</p>
                `,
                dp: `
                    <h4>DAFTAR PUSTAKA</h4>
                    <p style="text-indent: -1.27cm; padding-left: 1.27cm;">Handayani, S., & Putri, M. A. (2023). Pengaruh kompetensi digital terhadap kesiapan kerja lulusan perguruan tinggi. <em>Jurnal Manajemen Pendidikan Indonesia</em>, 15(2), 112-125.</p>
                    <p style="text-indent: -1.27cm; padding-left: 1.27cm;">Kusuma, A. R., Pratama, D., & Wijaya, H. (2023). Transformasi digital dan kesiapan karier mahasiswa di Indonesia. <em>Jurnal Ekonomi dan Bisnis</em>, 18(1), 45-59.</p>
                    <p style="text-indent: -1.27cm; padding-left: 1.27cm;">Naufal, M., & Wijaya, B. (2024). Literasi digital dan adaptabilitas karier generasi Z. <em>Jurnal Riset Manajemen</em>, 11(3), 201-215.</p>
                `
            },
            it: {
                title: 'IMPLEMENTASI ALGORITMA RANDOM FOREST DAN EKSTRAKSI FITUR CITRA UNTUK KLASIFIKASI PENYAKIT DAUN PADI',
                bab1: `
                    <h4>1.1 Latar Belakang Masalah</h4>
                    <p>Komoditas padi merupakan sumber pangan utama di Indonesia yang produktivitasnya sangat dipengaruhi oleh kesehatan tanaman pada fase vegetatif. Serangan patogen seperti hawar daun bakteri dan bercak coklat sering kali terlambat diidentifikasi secara kasatmata oleh petani (<span class="cite-blue">Setiawan & Nugroho, 2023</span>).</p>
                    <p>Kemajuan bidang pengolahan citra digital dan pembelajaran mesin memungkinkan deteksi penyakit daun dilakukan secara otomatis melalui ekstraksi fitur warna HSV dan tekstur GLCM. Algoritma Random Forest dipilih karena kemampuannya menggabungkan banyak pohon keputusan sehingga menghasilkan akurasi klasifikasi yang stabil (<span class="cite-blue">Saputra et al., 2024</span>).</p>
                `,
                bab2: `
                    <h4>2.1 Landasan Teori</h4>
                    <p>Pengolahan citra digital merupakan proses transformasi matriks piksel dua dimensi untuk meningkatkan kualitas visual atau mengekstraksi informasi ciri tertentu dari suatu objek (<span class="cite-blue">Gonzalez & Woods, 2021</span>).</p>
                `,
                bab3: `
                    <h4>3.1 Metode Pengembangan Sistem</h4>
                    <p>Penelitian ini menerapkan metode Research and Development (R&D) dengan tahapan pengumpulan dataset 1.200 citra daun padi, preprocessing citra, ekstraksi fitur HSV dan GLCM, pelatihan model Random Forest, serta pengujian akurasi menggunakan Confusion Matrix.</p>
                `,
                dp: `
                    <h4>DAFTAR PUSTAKA</h4>
                    <p style="text-indent: -1.27cm; padding-left: 1.27cm;">Gonzalez, R. C., & Woods, R. E. (2021). <em>Digital image processing</em> (4th ed.). Pearson.</p>
                    <p style="text-indent: -1.27cm; padding-left: 1.27cm;">Saputra, D., Kurniawan, R., & Hakim, L. (2024). Klasifikasi penyakit tanaman pangan menggunakan ensemble machine learning. <em>Jurnal Teknologi Informasi dan Ilmu Komputer</em>, 11(1), 33-44.</p>
                `
            },
            hukum: {
                title: 'PELINDUNGAN HUKUM BAGI KONSUMEN ATAS KEBOCORAN DATA PRIBADI PADA LAYANAN PINJAMAN DARING BERDASARKAN UU PDP',
                bab1: `
                    <h4>1.1 Latar Belakang Masalah</h4>
                    <p>Perkembangan layanan pendanaan bersama berbasis teknologi informasi telah memperluas inklusi keuangan di Indonesia, namun di sisi lain menimbulkan kerentanan hukum terkait pelindungan data pribadi pengguna (<span class="cite-blue">Siregar & Wibowo, 2023</span>).</p>
                    <p>Pengesahan Undang-Undang Nomor 27 Tahun 2022 tentang Pelindungan Data Pribadi menjadi instrumen hukum positif dalam mengatur kewajiban pengendali data, namun mekanisme pemulihan kerugian bagi konsumen masih menghadapi kendala pembuktian (<span class="cite-blue">Maharani et al., 2024</span>).</p>
                `,
                bab2: `
                    <h4>2.1 Tinjauan Pustaka</h4>
                    <p>Teori Pelindungan Hukum menurut Satjipto Rahardjo menekankan bahwa hukum hadir untuk mengintegrasikan berbagai kepentingan dalam masyarakat dengan cara melindungi hak-hak pihak yang berada pada posisi lemah secara kontraktual.</p>
                `,
                bab3: `
                    <h4>3.1 Jenis dan Pendekatan Penelitian</h4>
                    <p>Penelitian ini merupakan penelitian hukum yuridis normatif dengan menggunakan pendekatan perundang-undangan (statute approach) terhadap UU Nomor 27 Tahun 2022 tentang Pelindungan Data Pribadi.</p>
                `,
                dp: `
                    <h4>DAFTAR PUSTAKA</h4>
                    <p style="text-indent: -1.27cm; padding-left: 1.27cm;">Maharani, D., Prasetyo, Y., & Santoso, H. (2024). Tanggung jawab pengendali data pribadi dalam perspektif UU Nomor 27 Tahun 2022. <em>Jurnal Hukum Ius Quia Iustum</em>, 31(1), 55-74.</p>
                `
            }
        };

        let sampleMajor = 'manajemen';
        let sampleChap = 'bab1';

        function renderSampleStudio() {
            const d = studioSamples[sampleMajor];
            document.getElementById('sf-proj-title').innerText = d.title;
            document.getElementById('sf-paper-content').innerHTML = d[sampleChap];
            document.getElementById('sample-dl-docx').href = `/sample/download/${sampleMajor}/docx`;
            document.getElementById('sample-dl-pdf').href = `/sample/download/${sampleMajor}/pdf`;
        }

        function switchSampleMajor(m, btn) {
            sampleMajor = m;
            document.querySelectorAll('#workspace-preview .major-pill').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            renderSampleStudio();
        }

        function switchSampleChapter(ch) {
            sampleChap = ch;
            ['bab1', 'bab2', 'bab3', 'dp'].forEach(k => {
                document.getElementById('sf-btn-' + k).classList.toggle('active', k === ch);
            });
            renderSampleStudio();
        }

        function filterGallery(stage, btn) {
            document.querySelectorAll('#gallery-filter-bar .major-pill').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            document.querySelectorAll('#sample-cards-grid .sample-card').forEach(card => {
                const show = stage === 'all' || card.dataset.stage === stage;
                card.style.display = show ? 'flex' : 'none';
            });
        }

        function openPdfModal(title, url) {
            const dialog = document.getElementById('pdf-preview-dialog');
            document.getElementById('pdf-dialog-title').textContent = title;
            document.getElementById('pdf-dialog-frame').src = url;
            dialog.showModal();
        }

        function closePdfModal() {
            const dialog = document.getElementById('pdf-preview-dialog');
            document.getElementById('pdf-dialog-frame').src = 'about:blank';
            dialog.close();
        }

        renderSampleStudio();
    </script>
</body>
</html>
