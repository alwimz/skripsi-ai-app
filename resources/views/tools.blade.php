<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Tools Mahasiswa Gratis — Flowchart AI, PDF ke Word, Gabung PDF &amp; Slovin | SkripsiLab AI</title>
    <meta name="description"
        content="Kumpulan 8 tools mahasiswa gratis dari SkripsiLab AI: generator flowchart skripsi otomatis AI (unduh PNG HD/SVG), konversi PDF ke Word &amp; Word ke PDF, gabung &amp; pisah PDF, JPG ke PDF A4, kompres gambar, kalkulator Slovin Bab 3, dan hitung kata.">
    <meta name="keywords"
        content="buat flowchart skripsi otomatis, generator flowchart ai, diagram alur penelitian bab 3, tools mahasiswa gratis, pdf ke word gratis, word ke pdf online, gabung pdf skripsi, pisah halaman pdf, jpg ke pdf a4, kompres gambar siakad, kalkulator slovin bab 3, hitung kata skripsi, skripsilab ai">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url('/tools') }}">
    <meta name="theme-color" content="#2563eb">
    <link rel="icon" type="image/svg+xml" href="/images/logo.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Client-side PDF engines (gratis, privat di browser, bebas batas upload server) -->
    <script src="https://cdn.jsdelivr.net/npm/pdf-lib@1.17.1/dist/pdf-lib.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.min.js" defer></script>

    @php
        $toolsSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebApplication',
            'name' => 'Tools Mahasiswa — SkripsiLab AI',
            'url' => url('/tools'),
            'applicationCategory' => 'UtilitiesApplication',
            'operatingSystem' => 'Web Browser',
            'inLanguage' => 'id-ID',
            'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'IDR'],
            'description' => 'Kumpulan alat produktivitas gratis untuk mahasiswa Indonesia: konversi PDF ke Word, Word ke PDF, gabung PDF, pisah PDF, JPG ke PDF A4, kompres gambar, kalkulator sampel Slovin, dan penghitung kata skripsi.',
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($toolsSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --bg-page: #f8fafc;
            --bg-card: #ffffff;
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --primary-soft: #eff6ff;
            --primary-border: #bfdbfe;
            --text-main: #0f172a;
            --text-secondary: #475569;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --border-strong: #cbd5e1;
            --success: #059669;
            --danger: #dc2626;
        }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-page);
            color: var(--text-main);
            line-height: 1.55;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }
        a { color: inherit; text-decoration: none; }
        .container {
            max-width: 1120px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header */
        .topbar {
            position: sticky;
            top: 0;
            z-index: 40;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            height: 62px;
        }
        .topbar-inner {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .brand {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            font-weight: 800;
            font-size: 16px;
            letter-spacing: -0.02em;
            color: var(--text-main);
            white-space: nowrap;
        }
        .brand img { width: 30px; height: 30px; border-radius: 8px; display: block; }
        .brand-pill {
            font-size: 10.5px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 999px;
            background: var(--primary-soft);
            color: var(--primary);
            border: 1px solid var(--primary-border);
        }
        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.15s ease;
            white-space: nowrap;
        }
        .btn-primary {
            background: var(--primary);
            color: #ffffff;
            box-shadow: 0 4px 12px -4px rgba(37, 99, 235, 0.45);
        }
        .btn-primary:hover { background: var(--primary-hover); }
        .btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
        .btn-outline {
            background: #ffffff;
            color: var(--text-main);
            border-color: var(--border-strong);
        }
        .btn-outline:hover { background: #f8fafc; border-color: #94a3b8; }
        .btn-soft {
            background: var(--primary-soft);
            color: var(--primary-hover);
            border-color: var(--primary-border);
        }
        .btn-soft:hover { background: #dbeafe; }

        /* Hero Intro */
        .tools-hero {
            padding: 28px 0 18px;
            background: linear-gradient(180deg, #eff6ff 0%, #f8fafc 100%);
            border-bottom: 1px solid var(--border);
        }
        .hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11.5px;
            font-weight: 700;
            color: var(--primary);
            background: #ffffff;
            border: 1px solid var(--primary-border);
            padding: 3px 11px;
            border-radius: 999px;
            margin-bottom: 10px;
        }
        .tools-hero h1 {
            font-size: clamp(1.35rem, 1.1rem + 1.2vw, 1.85rem);
            font-weight: 800;
            letter-spacing: -0.025em;
            color: var(--text-main);
            margin-bottom: 6px;
        }
        .tools-hero p {
            font-size: 13.5px;
            color: var(--text-secondary);
            max-width: 680px;
        }

        /* Tool Selector Grid */
        .tools-nav-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(145px, 1fr));
            gap: 9px;
            margin: 18px 0 22px;
        }
        .tool-tab-card {
            background: #ffffff;
            border: 1.5px solid var(--border);
            border-radius: 12px;
            padding: 11px 12px;
            text-align: left;
            cursor: pointer;
            font-family: inherit;
            display: flex;
            flex-direction: column;
            gap: 6px;
            transition: all 0.16s ease;
        }
        .tool-tab-card:hover {
            border-color: #93c5fd;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px -8px rgba(37, 99, 235, 0.25);
        }
        .tool-tab-card.active {
            border-color: var(--primary);
            background: linear-gradient(180deg, #eff6ff 0%, #ffffff 100%);
            box-shadow: 0 8px 20px -10px rgba(37, 99, 235, 0.35);
        }
        .tool-tab-icon {
            width: 32px;
            height: 32px;
            border-radius: 9px;
            background: var(--primary-soft);
            color: var(--primary);
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .tool-tab-card.active .tool-tab-icon {
            background: var(--primary);
            color: #ffffff;
        }
        .tool-tab-name {
            font-size: 12.5px;
            font-weight: 700;
            color: var(--text-main);
            line-height: 1.25;
        }
        .tool-tab-sub {
            font-size: 11px;
            color: var(--text-muted);
            line-height: 1.3;
        }

        /* Tool Workspace Panels */
        .tool-panel { display: none; padding-bottom: 44px; }
        .tool-panel.active { display: block; animation: fadeIn 0.2s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: translateY(0); } }

        .panel-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 22px;
            box-shadow: 0 8px 24px -14px rgba(15, 23, 42, 0.08);
        }
        .panel-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 16px;
            padding-bottom: 14px;
            border-bottom: 1px solid #f1f5f9;
        }
        .panel-title { font-size: 17px; font-weight: 800; color: var(--text-main); }
        .panel-desc { font-size: 13px; color: var(--text-secondary); margin-top: 3px; }
        .privacy-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            font-weight: 600;
            color: #047857;
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            padding: 4px 10px;
            border-radius: 999px;
            white-space: nowrap;
        }

        /* Mode Segment Switch */
        .seg-switch {
            display: inline-flex;
            background: #f1f5f9;
            padding: 4px;
            border-radius: 10px;
            gap: 4px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }
        .seg-btn {
            padding: 7px 14px;
            border-radius: 8px;
            border: none;
            background: transparent;
            color: var(--text-secondary);
            font-size: 12.5px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .seg-btn.active {
            background: #ffffff;
            color: var(--primary);
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.08);
        }

        /* Dropzone */
        .dropzone {
            border: 2px dashed #93c5fd;
            background: #f8fbff;
            border-radius: 14px;
            padding: 26px 18px;
            text-align: center;
            cursor: pointer;
            transition: all 0.15s ease;
            margin-bottom: 14px;
        }
        .dropzone:hover, .dropzone.dragover {
            border-color: var(--primary);
            background: #eff6ff;
        }
        .dropzone-title { font-size: 14px; font-weight: 700; color: var(--text-main); margin-top: 8px; }
        .dropzone-sub { font-size: 12px; color: var(--text-muted); margin-top: 3px; }

        /* Form controls */
        .form-grid-2 {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 14px;
        }
        .form-label {
            display: block;
            font-size: 12.5px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 5px;
        }
        .form-input, .form-select, .form-textarea {
            width: 100%;
            min-height: 40px;
            padding: 8px 12px;
            border: 1px solid var(--border-strong);
            border-radius: 9px;
            font-size: 13.5px;
            font-family: inherit;
            color: var(--text-main);
            background: #ffffff;
        }
        .form-textarea { min-height: 180px; resize: vertical; line-height: 1.6; }
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.14);
        }

        /* File List */
        .file-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 14px;
        }
        .file-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 9px 12px;
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 10px;
        }
        .file-row-left {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
            flex: 1;
        }
        .file-thumb {
            width: 38px;
            height: 38px;
            border-radius: 7px;
            object-fit: cover;
            border: 1px solid var(--border-strong);
            flex-shrink: 0;
        }
        .file-badge {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            background: #dbeafe;
            color: #1d4ed8;
            font-size: 11px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .file-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-main);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .file-meta { font-size: 11.5px; color: var(--text-muted); }
        .file-actions { display: flex; align-items: center; gap: 5px; flex-shrink: 0; }
        .mini-btn {
            height: 28px;
            padding: 0 8px;
            border-radius: 7px;
            border: 1px solid var(--border-strong);
            background: #ffffff;
            color: var(--text-main);
            font-size: 11.5px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
        }
        .mini-btn:hover { background: #f1f5f9; }
        .mini-btn.danger { color: var(--danger); border-color: #fecaca; }

        /* Status Box */
        .status-box {
            display: none;
            margin-top: 12px;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
        }
        .status-box.info { display: block; background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; }
        .status-box.success { display: block; background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; }
        .status-box.error { display: block; background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            margin-top: 14px;
        }
        .stat-box {
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 12px 14px;
        }
        .stat-label { font-size: 11.5px; color: var(--text-muted); font-weight: 600; }
        .stat-val { font-size: 20px; font-weight: 800; color: var(--primary); margin-top: 3px; }
        .stat-sub { font-size: 11px; color: var(--text-muted); margin-top: 2px; }

        /* Slovin Result Card */
        .slovin-result {
            background: linear-gradient(135deg, #eff6ff 0%, #ffffff 100%);
            border: 1px solid #bfdbfe;
            border-radius: 14px;
            padding: 18px;
            margin-top: 14px;
        }
        .slovin-num { font-size: 30px; font-weight: 800; color: var(--primary); line-height: 1.1; }
        .slovin-steps {
            margin-top: 10px;
            padding: 11px 14px;
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 12.5px;
            color: #1e293b;
            line-height: 1.65;
            white-space: pre-wrap;
        }
        .slovin-para {
            margin-top: 10px;
            padding: 12px 14px;
            background: #ffffff;
            border-left: 3.5px solid var(--primary);
            border-radius: 8px;
            font-size: 13px;
            color: #1e293b;
            line-height: 1.65;
        }

        .preset-row { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px; }
        .preset-chip {
            padding: 5px 11px;
            border-radius: 999px;
            border: 1px solid var(--border-strong);
            background: #ffffff;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
            cursor: pointer;
            font-family: inherit;
        }
        .preset-chip:hover, .preset-chip.active {
            background: var(--primary-soft);
            border-color: var(--primary);
            color: var(--primary);
        }

        /* Flowchart Studio Split Layout */
        .fc-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.05fr) minmax(0, 0.95fr);
            gap: 18px;
            align-items: start;
        }
        .fc-ai-box {
            background: linear-gradient(135deg, #eff6ff 0%, #f8fafc 100%);
            border: 1px solid #bfdbfe;
            border-radius: 12px;
            padding: 14px;
            margin-bottom: 14px;
        }
        .fc-steps-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 12px;
            max-height: 430px;
            overflow-y: auto;
            padding-right: 2px;
        }
        .fc-step-row {
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 9px 10px;
            display: flex;
            flex-direction: column;
            gap: 7px;
        }
        .fc-step-top {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .fc-step-num {
            width: 22px;
            height: 22px;
            border-radius: 999px;
            background: var(--primary-soft);
            color: var(--primary);
            font-size: 11px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .fc-preview-shell {
            background: #f1f5f9;
            border: 1px solid var(--border-strong);
            border-radius: 14px;
            padding: 14px;
        }
        .fc-preview-paper {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 14px;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow-x: auto;
            min-height: 360px;
        }
        .fc-preview-paper svg {
            max-width: 100%;
            height: auto;
            display: block;
        }

        .footer {
            border-top: 1px solid var(--border);
            padding: 20px 0;
            font-size: 12.5px;
            color: var(--text-muted);
            text-align: center;
            background: #ffffff;
        }

        @media (max-width: 860px) {
            .fc-layout { grid-template-columns: 1fr; }
        }

        @media (max-width: 720px) {
            .container { padding: 0 14px; }
            .topbar { height: 56px; }
            .brand { font-size: 14.5px; gap: 7px; }
            .brand img { width: 26px; height: 26px; }
            .brand-pill { display: none; }
            .btn { padding: 7px 11px; font-size: 12px; }
            .tools-nav-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
            .panel-card { padding: 15px 14px; border-radius: 14px; }
            .form-grid-2 { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .file-row { flex-direction: column; align-items: stretch; }
            .file-actions { justify-content: flex-end; border-top: 1px solid #e2e8f0; padding-top: 6px; }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="container topbar-inner">
            <a href="/" class="brand">
                <img src="/images/logo.svg" alt="SkripsiLab AI">
                <span>SkripsiLab AI</span>
                <span class="brand-pill">Tools Mahasiswa</span>
            </a>
            <div class="topbar-actions">
                @auth
                    <a href="{{ route('app.create') }}" class="btn btn-soft">← Kembali ke Workspace</a>
                @else
                    <a href="/" class="btn btn-outline">Beranda</a>
                    <a href="{{ route('register') }}" class="btn btn-primary">Coba Gratis</a>
                @endauth
            </div>
        </div>
    </header>

    <section class="tools-hero">
        <div class="container">
            <div class="hero-eyebrow">8 Alat Produktivitas Akademik · 100% Gratis</div>
            <h1>Tools Mahasiswa SkripsiLab AI</h1>
            <p>Buat flowchart skripsi otomatis pakai AI, konversi PDF ⇄ Word, gabung &amp; pisah PDF, ubah foto ke PDF A4, kompres gambar SIAKAD, hitung sampel Slovin Bab III, hingga penghitung kata skripsi.</p>
        </div>
    </section>

    <main class="container">
        <!-- Grid Pemilih 8 Tools -->
        <div class="tools-nav-grid" role="tablist" aria-label="Pilih Tool Mahasiswa">
            <button type="button" class="tool-tab-card active" data-tool="flowchart" onclick="switchTool('flowchart')">
                <span class="tool-tab-icon">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="7" y="2" width="10" height="5" rx="2.5"/><path d="M12 7v3"/><rect x="5" y="10" width="14" height="5" rx="1"/><path d="M12 15v3"/><rect x="7" y="18" width="10" height="4" rx="2"/></svg>
                </span>
                <span class="tool-tab-name">Flowchart Skripsi</span>
                <span class="tool-tab-sub">AI + Unduh PNG HD</span>
            </button>

            <button type="button" class="tool-tab-card" data-tool="pdf-word" onclick="switchTool('pdf-word')">
                <span class="tool-tab-icon">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 15h6"/><path d="M12 12l3 3-3 3"/></svg>
                </span>
                <span class="tool-tab-name">PDF ⇄ Word</span>
                <span class="tool-tab-sub">.pdf ke .docx &amp; sebaliknya</span>
            </button>

            <button type="button" class="tool-tab-card" data-tool="merge-pdf" onclick="switchTool('merge-pdf')">
                <span class="tool-tab-icon">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="8" height="10" rx="1.5"/><rect x="13" y="3" width="8" height="10" rx="1.5"/><path d="M7 13v4a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-4"/></svg>
                </span>
                <span class="tool-tab-name">Gabung PDF</span>
                <span class="tool-tab-sub">Satukan bab &amp; lampiran</span>
            </button>

            <button type="button" class="tool-tab-card" data-tool="split-pdf" onclick="switchTool('split-pdf')">
                <span class="tool-tab-icon">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><line x1="20" y1="4" x2="8.12" y2="15.88"/><line x1="14.47" y1="14.48" x2="20" y2="20"/><line x1="8.12" y1="8.12" x2="12" y2="12"/></svg>
                </span>
                <span class="tool-tab-name">Pisah PDF</span>
                <span class="tool-tab-sub">Ambil halaman tertentu</span>
            </button>

            <button type="button" class="tool-tab-card" data-tool="img-pdf" onclick="switchTool('img-pdf')">
                <span class="tool-tab-icon">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                </span>
                <span class="tool-tab-name">JPG/PNG → PDF</span>
                <span class="tool-tab-sub">Foto berkas ke PDF A4</span>
            </button>

            <button type="button" class="tool-tab-card" data-tool="compress-img" onclick="switchTool('compress-img')">
                <span class="tool-tab-icon">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 14 10 14 10 20"/><polyline points="20 10 14 10 14 4"/><line x1="14" y1="10" x2="21" y2="3"/><line x1="3" y1="21" x2="10" y2="14"/></svg>
                </span>
                <span class="tool-tab-name">Kompres Gambar</span>
                <span class="tool-tab-sub">Kecilkan ukuran SIAKAD</span>
            </button>

            <button type="button" class="tool-tab-card" data-tool="slovin" onclick="switchTool('slovin')">
                <span class="tool-tab-icon">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="6" x2="16" y2="6"/><line x1="8" y1="11" x2="10" y2="11"/><line x1="14" y1="11" x2="16" y2="11"/><line x1="8" y1="16" x2="10" y2="16"/><line x1="14" y1="16" x2="16" y2="16"/></svg>
                </span>
                <span class="tool-tab-name">Kalkulator Slovin</span>
                <span class="tool-tab-sub">Rumus sampel Bab III</span>
            </button>

            <button type="button" class="tool-tab-card" data-tool="word-count" onclick="switchTool('word-count')">
                <span class="tool-tab-icon">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="14" y2="12"/><line x1="4" y1="18" x2="18" y2="18"/></svg>
                </span>
                <span class="tool-tab-name">Hitung Kata</span>
                <span class="tool-tab-sub">Kata &amp; estimasi A4</span>
            </button>
        </div>

        <!-- ======================================================= -->
        <!-- 0. GENERATOR FLOWCHART SKRIPSI & PROPOSAL (AI + VISUAL) -->
        <!-- ======================================================= -->
        <section class="tool-panel active" id="panel-flowchart">
            <div class="panel-card">
                <div class="panel-head">
                    <div>
                        <h2 class="panel-title">Generator Flowchart Skripsi &amp; Proposal</h2>
                        <p class="panel-desc">Buat diagram alur penelitian (Bab III), kerangka berpikir (Bab II), atau flowchart sistem secara otomatis dengan AI — edit simbol standar ISO lalu unduh PNG HD siap tempel ke Word.</p>
                    </div>
                    <span class="privacy-badge">✨ Otomatis AI · Siap Tempel Word</span>
                </div>

                <div class="fc-layout">
                    <!-- Kolom Kiri: Generator AI + Template + Editor Langkah -->
                    <div>
                        <div class="fc-ai-box">
                            <div style="font-size:12.5px; font-weight:700; color:#1d4ed8; margin-bottom:8px;">✨ Buat Langkah Otomatis Pakai AI</div>
                            <div style="margin-bottom:8px;">
                                <label class="form-label" for="fc-ai-topic">Judul Skripsi / Topik Penelitian</label>
                                <input type="text" id="fc-ai-topic" class="form-input" placeholder="cth: Klasifikasi Sentimen Komentar TikTok Menggunakan Naive Bayes">
                            </div>
                            <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                                <select id="fc-ai-kind" class="form-select" style="flex:1; min-width:190px; min-height:38px;">
                                    <option value="prosedur">Prosedur Penelitian (Bab III)</option>
                                    <option value="kerangka">Kerangka Berpikir (Bab II)</option>
                                    <option value="sistem">Alur Sistem / Aplikasi (IT &amp; R&amp;D)</option>
                                    <option value="analisis">Alur Pengumpulan &amp; Analisis Data</option>
                                </select>
                                <button type="button" class="btn btn-primary" id="btn-fc-ai" onclick="generateFlowchartWithAi()">✨ Buat dengan AI</button>
                            </div>
                            <div id="status-fc" class="status-box"></div>
                        </div>

                        <div style="font-size:12px; font-weight:700; color:var(--text-secondary); margin-bottom:6px;">Template Cepat 1-Klik:</div>
                        <div class="preset-row">
                            <button type="button" class="preset-chip" onclick="loadFlowchartPreset('kuantitatif')">Kuantitatif (Survei)</button>
                            <button type="button" class="preset-chip" onclick="loadFlowchartPreset('kualitatif')">Kualitatif</button>
                            <button type="button" class="preset-chip" onclick="loadFlowchartPreset('rnd')">R&amp;D / Sistem IT</button>
                            <button type="button" class="preset-chip" onclick="loadFlowchartPreset('kerangka')">Kerangka Berpikir</button>
                        </div>

                        <div style="display:flex; align-items:center; justify-content:space-between; margin:10px 0 8px;">
                            <span style="font-size:12.5px; font-weight:700; color:var(--text-main);">Edit Langkah &amp; Simbol Flowchart</span>
                            <button type="button" class="mini-btn" style="color:var(--primary); border-color:var(--primary-border);" onclick="addFlowchartStep()">+ Tambah Langkah</button>
                        </div>

                        <div id="fc-steps-editor" class="fc-steps-list"></div>
                    </div>

                    <!-- Kolom Kanan: Pratinjau SVG Tajam + Opsi Gaya & Unduh -->
                    <div>
                        <div class="form-grid-2" style="margin-bottom:10px;">
                            <div>
                                <label class="form-label" for="fc-style-select">Gaya Warna Skripsi</label>
                                <select id="fc-style-select" class="form-select" style="min-height:38px;" onchange="renderFlowchartSvg()">
                                    <option value="bw" selected>Hitam-Putih Resmi (Siap Cetak)</option>
                                    <option value="blue">Biru Akademik Modern</option>
                                    <option value="navy">Navy Elegan</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label" for="fc-caption-input">Keterangan Gambar (Bawah)</label>
                                <input type="text" id="fc-caption-input" class="form-input" style="min-height:38px;" value="Gambar 3.1 Diagram Alur Prosedur Penelitian" oninput="renderFlowchartSvg()">
                            </div>
                        </div>

                        <div class="fc-preview-shell">
                            <div class="fc-preview-paper" id="fc-svg-preview"></div>
                            <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:12px;">
                                <button type="button" class="btn btn-primary" style="flex:1;" onclick="downloadFlowchartPng()">⬇ Unduh PNG HD (Siap Tempel Word)</button>
                                <button type="button" class="btn btn-outline" onclick="downloadFlowchartSvg()">⬇ Unduh SVG</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ======================================================= -->
        <!-- 1. PDF <-> WORD CONVERTER -->
        <!-- ======================================================= -->
        <section class="tool-panel" id="panel-pdf-word">
            <div class="panel-card">
                <div class="panel-head">
                    <div>
                        <h2 class="panel-title">Konverter PDF ⇄ Word (.docx)</h2>
                        <p class="panel-desc">Ubah file PDF teks (skripsi, jurnal, makalah) menjadi Word siap edit, atau ubah file Word (.docx) menjadi PDF ukuran A4.</p>
                    </div>
                    <span class="privacy-badge">✓ File tidak disimpan permanen</span>
                </div>

                <div class="seg-switch">
                    <button type="button" class="seg-btn active" id="seg-pdf2docx" onclick="setConvertMode('pdf2docx')">PDF → Word (.docx)</button>
                    <button type="button" class="seg-btn" id="seg-docx2pdf" onclick="setConvertMode('docx2pdf')">Word (.docx) → PDF</button>
                </div>

                <!-- Submode: PDF -> DOCX -->
                <div id="box-pdf2docx">
                    <div class="dropzone" onclick="document.getElementById('input-pdf2docx').click()">
                        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="1.8"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        <div class="dropzone-title" id="label-pdf2docx">Klik untuk pilih file PDF (.pdf)</div>
                        <div class="dropzone-sub">PDF teks diekstrak langsung di browser (aman hingga 25 MB) lalu disusun ke .docx rapi</div>
                        <input type="file" id="input-pdf2docx" accept=".pdf,application/pdf" style="display:none" onchange="onSelectPdf2Docx(this)">
                    </div>

                    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
                        <label style="display:inline-flex; align-items:center; gap:8px; font-size:13px; color:#334155; cursor:pointer;">
                            <input type="checkbox" id="chk-pdf2docx-academic" checked>
                            <span>Rapikan ke format Skripsi A4 (Times New Roman 12pt, Margin 4-4-3-3, Spasi 1,5)</span>
                        </label>
                        <button type="button" class="btn btn-primary" id="btn-pdf2docx" onclick="runPdfToDocx()">Konversi ke Word (.docx)</button>
                    </div>
                    <div id="status-pdf2docx" class="status-box"></div>
                </div>

                <!-- Submode: DOCX -> PDF -->
                <div id="box-docx2pdf" style="display:none;">
                    <div class="dropzone" onclick="document.getElementById('input-docx2pdf').click()">
                        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="1.8"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        <div class="dropzone-title" id="label-docx2pdf">Klik untuk pilih file Word (.docx)</div>
                        <div class="dropzone-sub">Mendukung heading, paragraf, bold/italic, tabel, dan halaman baru (maks 4 MB)</div>
                        <input type="file" id="input-docx2pdf" accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document" style="display:none" onchange="onSelectDocx2Pdf(this)">
                    </div>

                    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <label for="sel-docx2pdf-margin" class="form-label" style="margin:0;">Margin A4:</label>
                            <select id="sel-docx2pdf-margin" class="form-select" style="width:auto; min-height:36px;">
                                <option value="standard">Standar Normal (2,54 cm)</option>
                                <option value="skripsi">Standar Skripsi (4-4-3-3 cm)</option>
                            </select>
                        </div>
                        <button type="button" class="btn btn-primary" id="btn-docx2pdf" onclick="runDocxToPdf()">Konversi ke PDF (.pdf)</button>
                    </div>
                    <div id="status-docx2pdf" class="status-box"></div>
                </div>
            </div>
        </section>

        <!-- ======================================================= -->
        <!-- 2. GABUNG PDF (MERGE PDF) -->
        <!-- ======================================================= -->
        <section class="tool-panel" id="panel-merge-pdf">
            <div class="panel-card">
                <div class="panel-head">
                    <div>
                        <h2 class="panel-title">Gabung PDF (Merge PDF)</h2>
                        <p class="panel-desc">Satukan Cover, Lembar Pengesahan hasil scan, Bab I–V, dan Lampiran menjadi satu file PDF utuh.</p>
                    </div>
                    <span class="privacy-badge">⚡ 100% di Browser · Tanpa Batas Ukuran</span>
                </div>

                <div class="dropzone" onclick="document.getElementById('input-merge-pdf').click()">
                    <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="1.8"><path d="M12 5v14M5 12h14"/></svg>
                    <div class="dropzone-title">Klik untuk tambah file PDF (bisa pilih banyak sekaligus)</div>
                    <div class="dropzone-sub">Atur urutan file dengan tombol ▲ / ▼ sebelum menggabungkan</div>
                    <input type="file" id="input-merge-pdf" accept=".pdf,application/pdf" multiple style="display:none" onchange="addMergePdfFiles(this)">
                </div>

                <div id="merge-pdf-list" class="file-list"></div>

                <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
                    <input type="text" id="merge-output-name" class="form-input" style="max-width:280px;" placeholder="Nama file hasil (cth: Skripsi_Lengkap)" value="Gabungan_Skripsi">
                    <button type="button" class="btn btn-primary" id="btn-merge-pdf" onclick="runMergePdf()">Gabungkan &amp; Unduh PDF</button>
                </div>
                <div id="status-merge-pdf" class="status-box"></div>
            </div>
        </section>

        <!-- ======================================================= -->
        <!-- 3. PISAH / EKSTRAK HALAMAN PDF (SPLIT PDF) -->
        <!-- ======================================================= -->
        <section class="tool-panel" id="panel-split-pdf">
            <div class="panel-card">
                <div class="panel-head">
                    <div>
                        <h2 class="panel-title">Pisah / Ambil Halaman PDF</h2>
                        <p class="panel-desc">Potong halaman tertentu dari PDF (misalnya ambil halaman Cover saja, Bab I–III saja, atau halaman lampiran tertentu).</p>
                    </div>
                    <span class="privacy-badge">⚡ 100% di Browser</span>
                </div>

                <div class="dropzone" onclick="document.getElementById('input-split-pdf').click()">
                    <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="1.8"><circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><line x1="20" y1="4" x2="8.12" y2="15.88"/><line x1="14.47" y1="14.48" x2="20" y2="20"/></svg>
                    <div class="dropzone-title" id="label-split-pdf">Klik untuk pilih 1 file PDF</div>
                    <div class="dropzone-sub" id="sub-split-pdf">Jumlah total halaman akan terdeteksi otomatis</div>
                    <input type="file" id="input-split-pdf" accept=".pdf,application/pdf" style="display:none" onchange="onSelectSplitPdf(this)">
                </div>

                <div class="preset-row" id="split-presets" style="display:none;">
                    <button type="button" class="preset-chip" onclick="setSplitRange('1')">Halaman 1 saja</button>
                    <button type="button" class="preset-chip" onclick="setSplitRange('1-3')">Halaman 1–3</button>
                    <button type="button" class="preset-chip" onclick="setSplitRange('1-5')">Halaman 1–5</button>
                    <button type="button" class="preset-chip" onclick="setSplitRangeLast()">Halaman Terakhir</button>
                </div>

                <div class="form-grid-2">
                    <div>
                        <label class="form-label" for="split-range-input">Halaman yang Diambil</label>
                        <input type="text" id="split-range-input" class="form-input" placeholder="Contoh: 1-3, 5, 8-10">
                    </div>
                    <div style="display:flex; align-items:flex-end;">
                        <button type="button" class="btn btn-primary" id="btn-split-pdf" style="width:100%;" onclick="runSplitPdf()">Ekstrak &amp; Unduh PDF</button>
                    </div>
                </div>
                <div id="status-split-pdf" class="status-box"></div>
            </div>
        </section>

        <!-- ======================================================= -->
        <!-- 4. JPG / PNG -> PDF A4 -->
        <!-- ======================================================= -->
        <section class="tool-panel" id="panel-img-pdf">
            <div class="panel-card">
                <div class="panel-head">
                    <div>
                        <h2 class="panel-title">JPG / PNG → PDF A4</h2>
                        <p class="panel-desc">Ubah foto Lembar Pengesahan bertanda tangan, Surat Izin Penelitian, KRS, atau Kuesioner menjadi dokumen PDF A4 rapi.</p>
                    </div>
                    <span class="privacy-badge">⚡ 100% di Browser</span>
                </div>

                <div class="dropzone" onclick="document.getElementById('input-img-pdf').click()">
                    <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    <div class="dropzone-title">Klik untuk pilih foto JPG, PNG, atau WEBP (bisa banyak)</div>
                    <div class="dropzone-sub">Setiap foto otomatis diposisikan proporsional di 1 halaman kertas A4</div>
                    <input type="file" id="input-img-pdf" accept="image/jpeg,image/png,image/webp" multiple style="display:none" onchange="addImgPdfFiles(this)">
                </div>

                <div id="img-pdf-list" class="file-list"></div>

                <div class="form-grid-2">
                    <div>
                        <label class="form-label" for="img-pdf-orient">Orientasi Kertas A4</label>
                        <select id="img-pdf-orient" class="form-select">
                            <option value="portrait">A4 Tegak (Portrait)</option>
                            <option value="landscape">A4 Mendatar (Landscape)</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="img-pdf-margin">Margin Halaman</label>
                        <select id="img-pdf-margin" class="form-select">
                            <option value="36">Rapi (Margin 1,25 cm)</option>
                            <option value="18">Tipis (Margin 0,6 cm)</option>
                            <option value="0">Penuh Tanpa Margin</option>
                        </select>
                    </div>
                </div>

                <button type="button" class="btn btn-primary" id="btn-img-pdf" onclick="runImgToPdf()">Buat &amp; Unduh PDF A4</button>
                <div id="status-img-pdf" class="status-box"></div>
            </div>
        </section>

        <!-- ======================================================= -->
        <!-- 5. KOMPRES GAMBAR (SIAKAD / LAMPIRAN) -->
        <!-- ======================================================= -->
        <section class="tool-panel" id="panel-compress-img">
            <div class="panel-card">
                <div class="panel-head">
                    <div>
                        <h2 class="panel-title">Kompres Gambar (JPG / PNG)</h2>
                        <p class="panel-desc">Kecilkan ukuran file foto/scan agar lolos batas upload portal kampus (SIAKAD) atau memperingan lampiran skripsi.</p>
                    </div>
                    <span class="privacy-badge">⚡ 100% di Browser</span>
                </div>

                <div class="dropzone" onclick="document.getElementById('input-compress-img').click()">
                    <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="1.8"><polyline points="4 14 10 14 10 20"/><polyline points="20 10 14 10 14 4"/><line x1="14" y1="10" x2="21" y2="3"/><line x1="3" y1="21" x2="10" y2="14"/></svg>
                    <div class="dropzone-title">Klik untuk pilih 1 atau banyak gambar</div>
                    <div class="dropzone-sub">Lihat langsung perbandingan ukuran sebelum &amp; sesudah kompresi</div>
                    <input type="file" id="input-compress-img" accept="image/jpeg,image/png,image/webp" multiple style="display:none" onchange="onSelectCompressImages(this)">
                </div>

                <div class="form-grid-2">
                    <div>
                        <label class="form-label" for="compress-quality">Kualitas Kompresi: <strong id="compress-quality-val">75%</strong></label>
                        <input type="range" id="compress-quality" min="30" max="92" value="75" style="width:100%; accent-color:#2563eb;" oninput="document.getElementById('compress-quality-val').textContent = this.value + '%'; runCompressImages();">
                    </div>
                    <div>
                        <label class="form-label" for="compress-maxw">Lebar Maksimal</label>
                        <select id="compress-maxw" class="form-select" onchange="runCompressImages()">
                            <option value="1600" selected>1600 px (Tajam &amp; Ringan)</option>
                            <option value="1200">1200 px (Sangat Ringan / SIAKAD)</option>
                            <option value="900">900 px (Super Kecil)</option>
                            <option value="0">Ukuran Asli</option>
                        </select>
                    </div>
                </div>

                <div id="compress-results" class="file-list"></div>
                <div id="status-compress-img" class="status-box"></div>
            </div>
        </section>

        <!-- ======================================================= -->
        <!-- 6. KALKULATOR SAMPEL SLOVIN (BAB III) -->
        <!-- ======================================================= -->
        <section class="tool-panel" id="panel-slovin">
            <div class="panel-card">
                <div class="panel-head">
                    <div>
                        <h2 class="panel-title">Kalkulator Rumus Slovin (Bab III)</h2>
                        <p class="panel-desc">Hitung jumlah sampel minimal penelitian kuantitatif secara otomatis lengkap dengan langkah perhitungan dan paragraf siap salin ke Bab III.</p>
                    </div>
                    <span class="privacy-badge">✓ Siap Salin ke Skripsi</span>
                </div>

                <div class="form-grid-2">
                    <div>
                        <label class="form-label" for="slovin-n">Jumlah Populasi (N)</label>
                        <input type="number" id="slovin-n" class="form-input" min="2" value="350" oninput="calcSlovin()">
                    </div>
                    <div>
                        <label class="form-label" for="slovin-e">Batas Kesalahan / Margin of Error (e)</label>
                        <select id="slovin-e" class="form-select" onchange="calcSlovin()">
                            <option value="0.05" selected>5% (0,05) — Standar Skripsi Umum</option>
                            <option value="0.1">10% (0,10) — Populasi Kecil / Terbatas</option>
                            <option value="0.01">1% (0,01) — Ketelitian Sangat Tinggi</option>
                        </select>
                    </div>
                </div>

                <div class="slovin-result" id="slovin-output">
                    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
                        <div>
                            <div style="font-size:12px; font-weight:700; color:#1d4ed8; text-transform:uppercase;">Jumlah Sampel Minimal (n)</div>
                            <div class="slovin-num" id="slovin-final-n">187 Responden</div>
                        </div>
                        <button type="button" class="btn btn-primary" onclick="copySlovinParagraph()">Salin Paragraf Bab III</button>
                    </div>
                    <div class="slovin-steps" id="slovin-steps-box"></div>
                    <div class="slovin-para" id="slovin-para-box"></div>
                </div>
                <div id="status-slovin" class="status-box"></div>
            </div>
        </section>

        <!-- ======================================================= -->
        <!-- 7. HITUNG KATA & ESTIMASI HALAMAN A4 -->
        <!-- ======================================================= -->
        <section class="tool-panel" id="panel-word-count">
            <div class="panel-card">
                <div class="panel-head">
                    <div>
                        <h2 class="panel-title">Hitung Kata &amp; Estimasi Halaman Skripsi A4</h2>
                        <p class="panel-desc">Tempel teks bab skripsimu atau unggah file <code>.txt</code>, <code>.docx</code>, atau <code>.pdf</code> untuk melihat jumlah kata, karakter, dan perkiraan halaman A4.</p>
                    </div>
                    <div style="display:flex; gap:6px; flex-wrap:wrap;">
                        <button type="button" class="btn btn-outline" style="padding:6px 11px; font-size:12px;" onclick="document.getElementById('input-wc-file').click()">Unggah .docx / .pdf / .txt</button>
                        <button type="button" class="btn btn-outline" style="padding:6px 11px; font-size:12px;" onclick="clearWordCount()">Bersihkan</button>
                        <input type="file" id="input-wc-file" accept=".txt,.docx,.pdf" style="display:none" onchange="loadWordCountFile(this)">
                    </div>
                </div>

                <textarea id="wc-textarea" class="form-textarea" placeholder="Tempel teks Bab I–V atau abstrak skripsimu di sini..." oninput="updateWordCount()"></textarea>
                <div id="status-wc" class="status-box"></div>

                <div class="stats-grid">
                    <div class="stat-box">
                        <div class="stat-label">Total Kata</div>
                        <div class="stat-val" id="wc-words">0</div>
                        <div class="stat-sub" id="wc-read-time">0 menit baca</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-label">Karakter</div>
                        <div class="stat-val" id="wc-chars">0</div>
                        <div class="stat-sub" id="wc-chars-nospace">0 tanpa spasi</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-label">Paragraf &amp; Kalimat</div>
                        <div class="stat-val" id="wc-paras">0</div>
                        <div class="stat-sub" id="wc-sentences">0 kalimat</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-label">Estimasi Halaman A4</div>
                        <div class="stat-val" id="wc-pages-15">0 hal</div>
                        <div class="stat-sub" id="wc-pages-20">Spasi 1,5 · (0 hal spasi 2)</div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="container">
            &copy; {{ date('Y') }} <strong>SkripsiLab AI</strong> &bull; Tools Mahasiswa Gratis &bull;
            <a href="/" style="color:var(--primary); font-weight:600;">Beranda</a> &bull;
            <a href="{{ route('register') }}" style="color:var(--primary); font-weight:600;">Workspace Proposal &amp; Skripsi</a>
        </div>
    </footer>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function setStatus(id, type, msg) {
            const el = document.getElementById(id);
            if (!el) return;
            el.className = 'status-box ' + type;
            el.textContent = msg;
        }

        function formatBytes(bytes) {
            if (!bytes) return '0 B';
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
        }

        function triggerBlobDownload(blob, filename) {
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            a.remove();
            setTimeout(() => URL.revokeObjectURL(url), 4000);
        }

        function ensurePdfJsWorker() {
            if (window.pdfjsLib && !window.pdfjsLib.GlobalWorkerOptions.workerSrc) {
                window.pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.worker.min.js';
            }
        }

        // ============================================================
        // TAB SWITCHING (+ URL HASH SUPPORT)
        // ============================================================
        function switchTool(toolId) {
            document.querySelectorAll('.tool-tab-card').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.tool === toolId);
            });
            document.querySelectorAll('.tool-panel').forEach(p => {
                p.classList.toggle('active', p.id === 'panel-' + toolId);
            });
            if (history.replaceState) {
                history.replaceState(null, '', '#' + toolId);
            }
        }

        // ============================================================
        // 1. PDF <-> WORD CONVERTER
        // ============================================================
        let selectedPdf2DocxFile = null;
        let selectedDocx2PdfFile = null;

        function setConvertMode(mode) {
            document.getElementById('seg-pdf2docx').classList.toggle('active', mode === 'pdf2docx');
            document.getElementById('seg-docx2pdf').classList.toggle('active', mode === 'docx2pdf');
            document.getElementById('box-pdf2docx').style.display = mode === 'pdf2docx' ? 'block' : 'none';
            document.getElementById('box-docx2pdf').style.display = mode === 'docx2pdf' ? 'block' : 'none';
        }

        function onSelectPdf2Docx(input) {
            if (!input.files || !input.files[0]) return;
            selectedPdf2DocxFile = input.files[0];
            document.getElementById('label-pdf2docx').textContent = `${selectedPdf2DocxFile.name} (${formatBytes(selectedPdf2DocxFile.size)})`;
            setStatus('status-pdf2docx', 'info', 'File PDF siap dikonversi. Klik tombol "Konversi ke Word (.docx)".');
        }

        function onSelectDocx2Pdf(input) {
            if (!input.files || !input.files[0]) return;
            selectedDocx2PdfFile = input.files[0];
            document.getElementById('label-docx2pdf').textContent = `${selectedDocx2PdfFile.name} (${formatBytes(selectedDocx2PdfFile.size)})`;
            setStatus('status-docx2pdf', 'info', 'File Word siap dikonversi. Klik tombol "Konversi ke PDF (.pdf)".');
        }

        async function extractPagesFromPdfFile(file) {
            if (!window.pdfjsLib) throw new Error('Mesin PDF.js sedang dimuat, tunggu 2 detik lalu coba lagi.');
            ensurePdfJsWorker();
            const buf = await file.arrayBuffer();
            const pdf = await window.pdfjsLib.getDocument({ data: buf }).promise;
            const pages = [];

            for (let p = 1; p <= pdf.numPages; p++) {
                const page = await pdf.getPage(p);
                const tc = await page.getTextContent();
                const items = tc.items || [];

                // Kelompokkan item teks berdasarkan koordinat Y (baris)
                const lines = [];
                let currentLine = [];
                let lastY = null;
                for (const item of items) {
                    const str = (item.str || '');
                    const y = item.transform ? Math.round(item.transform[5]) : lastY;
                    if (lastY !== null && Math.abs(y - lastY) > 6) {
                        const joined = currentLine.join(' ').replace(/\s+/g, ' ').trim();
                        if (joined) lines.push({ y: lastY, text: joined });
                        currentLine = [];
                    }
                    if (str.trim() !== '') currentLine.push(str);
                    lastY = y;
                }
                if (currentLine.length) {
                    const joined = currentLine.join(' ').replace(/\s+/g, ' ').trim();
                    if (joined) lines.push({ y: lastY, text: joined });
                }

                // Gabungkan baris-baris berdekatan menjadi paragraf alami
                const paragraphs = [];
                let paraBuf = '';
                let prevY = null;
                for (const ln of lines) {
                    const gap = prevY !== null ? Math.abs(prevY - ln.y) : 0;
                    const looksHeading = /^(?:BAB\s+[IVX]+|\d+\.\d+|DAFTAR\s+|KATA\s+PENGANTAR|ABSTRAK|ABSTRACT|LAMPIRAN)/i.test(ln.text);
                    if (paraBuf && (gap > 22 || looksHeading)) {
                        paragraphs.push(paraBuf.trim());
                        paraBuf = ln.text;
                    } else {
                        paraBuf = paraBuf ? (paraBuf + ' ' + ln.text) : ln.text;
                    }
                    prevY = ln.y;
                }
                if (paraBuf.trim()) paragraphs.push(paraBuf.trim());
                pages.push({ pageNum: p, paragraphs });
            }
            return pages;
        }

        async function runPdfToDocx() {
            if (!selectedPdf2DocxFile) {
                setStatus('status-pdf2docx', 'error', 'Pilih file PDF terlebih dahulu.');
                return;
            }
            const btn = document.getElementById('btn-pdf2docx');
            btn.disabled = true;
            try {
                setStatus('status-pdf2docx', 'info', 'Membaca struktur teks dari setiap halaman PDF...');
                const pages = await extractPagesFromPdfFile(selectedPdf2DocxFile);
                const totalParas = pages.reduce((acc, p) => acc + p.paragraphs.length, 0);
                if (totalParas === 0) {
                    throw new Error('PDF ini tidak memiliki lapisan teks (kemungkinan hasil scan gambar). Gunakan PDF berbasis teks.');
                }

                setStatus('status-pdf2docx', 'info', `Berhasil membaca ${pages.length} halaman. Menyusun dokumen Word (.docx)...`);
                const res = await fetch('{{ route("tools.convert.pdfToDocx") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({
                        filename: selectedPdf2DocxFile.name,
                        pages,
                        academic_format: document.getElementById('chk-pdf2docx-academic').checked
                    })
                });
                if (!res.ok) {
                    const errData = await res.json().catch(() => ({}));
                    throw new Error(errData.message || 'Gagal menyusun file Word.');
                }
                const blob = await res.blob();
                const outName = selectedPdf2DocxFile.name.replace(/\.pdf$/i, '') + '.docx';
                triggerBlobDownload(blob, outName);
                setStatus('status-pdf2docx', 'success', `Berhasil! File ${outName} (${pages.length} halaman) sudah diunduh.`);
            } catch (err) {
                setStatus('status-pdf2docx', 'error', err.message || 'Terjadi kesalahan saat konversi.');
            } finally {
                btn.disabled = false;
            }
        }

        async function runDocxToPdf() {
            if (!selectedDocx2PdfFile) {
                setStatus('status-docx2pdf', 'error', 'Pilih file Word (.docx) terlebih dahulu.');
                return;
            }
            if (selectedDocx2PdfFile.size > 4 * 1024 * 1024) {
                setStatus('status-docx2pdf', 'error', 'Ukuran file .docx melebihi batas 4 MB.');
                return;
            }
            const btn = document.getElementById('btn-docx2pdf');
            btn.disabled = true;
            try {
                setStatus('status-docx2pdf', 'info', 'Mengonversi dokumen Word (.docx) ke PDF A4...');
                const fd = new FormData();
                fd.append('file', selectedDocx2PdfFile);
                fd.append('margin', document.getElementById('sel-docx2pdf-margin').value);
                const res = await fetch('{{ route("tools.convert.docxToPdf") }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    body: fd
                });
                if (!res.ok) {
                    const errData = await res.json().catch(() => ({}));
                    throw new Error(errData.message || 'Gagal mengonversi ke PDF.');
                }
                const blob = await res.blob();
                const outName = selectedDocx2PdfFile.name.replace(/\.docx$/i, '') + '.pdf';
                triggerBlobDownload(blob, outName);
                setStatus('status-docx2pdf', 'success', `Berhasil! File ${outName} sudah diunduh.`);
            } catch (err) {
                setStatus('status-docx2pdf', 'error', err.message || 'Terjadi kesalahan saat konversi.');
            } finally {
                btn.disabled = false;
            }
        }

        // ============================================================
        // 2. GABUNG PDF (MERGE PDF) — 100% CLIENT-SIDE PDF-LIB
        // ============================================================
        let mergePdfItems = [];

        async function addMergePdfFiles(input) {
            if (!input.files || !input.files.length) return;
            for (const f of Array.from(input.files)) {
                let pageCount = '?';
                try {
                    if (window.PDFLib) {
                        const doc = await window.PDFLib.PDFDocument.load(await f.arrayBuffer(), { ignoreEncryption: true });
                        pageCount = doc.getPageCount();
                    }
                } catch (e) {}
                mergePdfItems.push({ file: f, pages: pageCount });
            }
            input.value = '';
            renderMergePdfList();
        }

        function renderMergePdfList() {
            const wrap = document.getElementById('merge-pdf-list');
            if (!mergePdfItems.length) {
                wrap.innerHTML = '';
                return;
            }
            wrap.innerHTML = mergePdfItems.map((it, i) => `
                <div class="file-row">
                    <div class="file-row-left">
                        <span class="file-badge">PDF</span>
                        <div style="min-width:0;">
                            <div class="file-name">${it.file.name.replace(/</g, '&lt;')}</div>
                            <div class="file-meta">${it.pages} halaman · ${formatBytes(it.file.size)}</div>
                        </div>
                    </div>
                    <div class="file-actions">
                        <button type="button" class="mini-btn" onclick="moveMergePdf(${i}, -1)" ${i === 0 ? 'disabled' : ''}>▲</button>
                        <button type="button" class="mini-btn" onclick="moveMergePdf(${i}, 1)" ${i === mergePdfItems.length - 1 ? 'disabled' : ''}>▼</button>
                        <button type="button" class="mini-btn danger" onclick="removeMergePdf(${i})">Hapus</button>
                    </div>
                </div>
            `).join('');
        }

        function moveMergePdf(i, d) {
            const t = i + d;
            if (t < 0 || t >= mergePdfItems.length) return;
            [mergePdfItems[i], mergePdfItems[t]] = [mergePdfItems[t], mergePdfItems[i]];
            renderMergePdfList();
        }

        function removeMergePdf(i) {
            mergePdfItems.splice(i, 1);
            renderMergePdfList();
        }

        async function runMergePdf() {
            if (mergePdfItems.length < 2) {
                setStatus('status-merge-pdf', 'error', 'Tambahkan minimal 2 file PDF untuk digabungkan.');
                return;
            }
            if (!window.PDFLib) {
                setStatus('status-merge-pdf', 'error', 'Library PDF sedang dimuat, coba lagi sebentar.');
                return;
            }
            const btn = document.getElementById('btn-merge-pdf');
            btn.disabled = true;
            try {
                setStatus('status-merge-pdf', 'info', `Menggabungkan ${mergePdfItems.length} file PDF...`);
                const merged = await window.PDFLib.PDFDocument.create();
                for (const item of mergePdfItems) {
                    const src = await window.PDFLib.PDFDocument.load(await item.file.arrayBuffer(), { ignoreEncryption: true });
                    const copied = await merged.copyPages(src, src.getPageIndices());
                    copied.forEach(p => merged.addPage(p));
                }
                const bytes = await merged.save();
                const nameBase = (document.getElementById('merge-output-name').value || 'Gabungan_PDF').trim().replace(/\.pdf$/i, '');
                triggerBlobDownload(new Blob([bytes], { type: 'application/pdf' }), nameBase + '.pdf');
                setStatus('status-merge-pdf', 'success', `Berhasil! ${merged.getPageCount()} halaman digabung ke ${nameBase}.pdf.`);
            } catch (err) {
                setStatus('status-merge-pdf', 'error', err.message || 'Gagal menggabungkan PDF.');
            } finally {
                btn.disabled = false;
            }
        }

        // ============================================================
        // 3. PISAH PDF (SPLIT PDF)
        // ============================================================
        let splitPdfFile = null;
        let splitPdfTotalPages = 0;

        async function onSelectSplitPdf(input) {
            if (!input.files || !input.files[0]) return;
            splitPdfFile = input.files[0];
            document.getElementById('label-split-pdf').textContent = `${splitPdfFile.name} (${formatBytes(splitPdfFile.size)})`;
            try {
                const doc = await window.PDFLib.PDFDocument.load(await splitPdfFile.arrayBuffer(), { ignoreEncryption: true });
                splitPdfTotalPages = doc.getPageCount();
                document.getElementById('sub-split-pdf').textContent = `Total ${splitPdfTotalPages} halaman terdeteksi`;
                document.getElementById('split-presets').style.display = 'flex';
                document.getElementById('split-range-input').value = splitPdfTotalPages >= 3 ? '1-3' : '1';
                setStatus('status-split-pdf', 'info', `PDF dimuat (${splitPdfTotalPages} halaman). Masukkan rentang halaman lalu klik Ekstrak.`);
            } catch (err) {
                setStatus('status-split-pdf', 'error', 'Gagal membaca PDF. Pastikan file tidak terproteksi kata sandi.');
            }
        }

        function setSplitRange(val) {
            document.getElementById('split-range-input').value = val;
        }
        function setSplitRangeLast() {
            if (splitPdfTotalPages > 0) document.getElementById('split-range-input').value = String(splitPdfTotalPages);
        }

        function parsePageRanges(str, maxPage) {
            const set = new Set();
            const parts = String(str || '').split(',');
            for (const raw of parts) {
                const p = raw.trim();
                if (!p) continue;
                if (p.includes('-')) {
                    const [a, b] = p.split('-').map(n => parseInt(n.trim(), 10));
                    if (!isNaN(a) && !isNaN(b)) {
                        const start = Math.max(1, Math.min(a, b));
                        const end = Math.min(maxPage, Math.max(a, b));
                        for (let i = start; i <= end; i++) set.add(i - 1);
                    }
                } else {
                    const n = parseInt(p, 10);
                    if (!isNaN(n) && n >= 1 && n <= maxPage) set.add(n - 1);
                }
            }
            return Array.from(set).sort((a, b) => a - b);
        }

        async function runSplitPdf() {
            if (!splitPdfFile || !splitPdfTotalPages) {
                setStatus('status-split-pdf', 'error', 'Pilih file PDF terlebih dahulu.');
                return;
            }
            const indices = parsePageRanges(document.getElementById('split-range-input').value, splitPdfTotalPages);
            if (!indices.length) {
                setStatus('status-split-pdf', 'error', `Masukkan rentang halaman yang valid (1 s/d ${splitPdfTotalPages}).`);
                return;
            }
            const btn = document.getElementById('btn-split-pdf');
            btn.disabled = true;
            try {
                const src = await window.PDFLib.PDFDocument.load(await splitPdfFile.arrayBuffer(), { ignoreEncryption: true });
                const out = await window.PDFLib.PDFDocument.create();
                const copied = await out.copyPages(src, indices);
                copied.forEach(p => out.addPage(p));
                const bytes = await out.save();
                const base = splitPdfFile.name.replace(/\.pdf$/i, '');
                triggerBlobDownload(new Blob([bytes], { type: 'application/pdf' }), `${base}_hal.pdf`);
                setStatus('status-split-pdf', 'success', `Berhasil mengekstrak ${indices.length} halaman!`);
            } catch (err) {
                setStatus('status-split-pdf', 'error', err.message || 'Gagal memisahkan PDF.');
            } finally {
                btn.disabled = false;
            }
        }

        // ============================================================
        // 4. JPG / PNG -> PDF A4
        // ============================================================
        let imgPdfItems = [];

        function addImgPdfFiles(input) {
            if (!input.files || !input.files.length) return;
            for (const f of Array.from(input.files)) {
                imgPdfItems.push({ file: f, previewUrl: URL.createObjectURL(f) });
            }
            input.value = '';
            renderImgPdfList();
        }

        function renderImgPdfList() {
            const wrap = document.getElementById('img-pdf-list');
            if (!imgPdfItems.length) { wrap.innerHTML = ''; return; }
            wrap.innerHTML = imgPdfItems.map((it, i) => `
                <div class="file-row">
                    <div class="file-row-left">
                        <img src="${it.previewUrl}" class="file-thumb" alt="Preview">
                        <div style="min-width:0;">
                            <div class="file-name">${it.file.name.replace(/</g, '&lt;')}</div>
                            <div class="file-meta">Halaman ${i + 1} · ${formatBytes(it.file.size)}</div>
                        </div>
                    </div>
                    <div class="file-actions">
                        <button type="button" class="mini-btn" onclick="moveImgPdf(${i}, -1)" ${i === 0 ? 'disabled' : ''}>▲</button>
                        <button type="button" class="mini-btn" onclick="moveImgPdf(${i}, 1)" ${i === imgPdfItems.length - 1 ? 'disabled' : ''}>▼</button>
                        <button type="button" class="mini-btn danger" onclick="removeImgPdf(${i})">Hapus</button>
                    </div>
                </div>
            `).join('');
        }

        function moveImgPdf(i, d) {
            const t = i + d;
            if (t < 0 || t >= imgPdfItems.length) return;
            [imgPdfItems[i], imgPdfItems[t]] = [imgPdfItems[t], imgPdfItems[i]];
            renderImgPdfList();
        }

        function removeImgPdf(i) {
            URL.revokeObjectURL(imgPdfItems[i].previewUrl);
            imgPdfItems.splice(i, 1);
            renderImgPdfList();
        }

        function imageFileToJpegBytes(file) {
            return new Promise((resolve, reject) => {
                const img = new Image();
                const url = URL.createObjectURL(file);
                img.onload = () => {
                    const canvas = document.createElement('canvas');
                    canvas.width = img.naturalWidth;
                    canvas.height = img.naturalHeight;
                    const ctx = canvas.getContext('2d');
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, canvas.width, canvas.height);
                    ctx.drawImage(img, 0, 0);
                    URL.revokeObjectURL(url);
                    canvas.toBlob(async (blob) => {
                        if (!blob) return reject(new Error('Gagal memproses gambar.'));
                        resolve(new Uint8Array(await blob.arrayBuffer()));
                    }, 'image/jpeg', 0.92);
                };
                img.onerror = () => { URL.revokeObjectURL(url); reject(new Error('Gagal memuat gambar ' + file.name)); };
                img.src = url;
            });
        }

        async function runImgToPdf() {
            if (!imgPdfItems.length) {
                setStatus('status-img-pdf', 'error', 'Pilih minimal 1 foto/gambar terlebih dahulu.');
                return;
            }
            const btn = document.getElementById('btn-img-pdf');
            btn.disabled = true;
            try {
                setStatus('status-img-pdf', 'info', `Menyusun ${imgPdfItems.length} gambar ke kertas A4...`);
                const orient = document.getElementById('img-pdf-orient').value;
                const margin = parseInt(document.getElementById('img-pdf-margin').value, 10) || 0;
                const pageW = orient === 'landscape' ? 841.89 : 595.28;
                const pageH = orient === 'landscape' ? 595.28 : 841.89;

                const doc = await window.PDFLib.PDFDocument.create();
                for (const it of imgPdfItems) {
                    const jpgBytes = await imageFileToJpegBytes(it.file);
                    const embedded = await doc.embedJpg(jpgBytes);
                    const page = doc.addPage([pageW, pageH]);
                    const maxW = pageW - margin * 2;
                    const maxH = pageH - margin * 2;
                    const scale = Math.min(maxW / embedded.width, maxH / embedded.height);
                    const drawW = embedded.width * scale;
                    const drawH = embedded.height * scale;
                    page.drawImage(embedded, {
                        x: (pageW - drawW) / 2,
                        y: (pageH - drawH) / 2,
                        width: drawW,
                        height: drawH
                    });
                }
                const bytes = await doc.save();
                triggerBlobDownload(new Blob([bytes], { type: 'application/pdf' }), 'Dokumen_Gambar_A4.pdf');
                setStatus('status-img-pdf', 'success', `Berhasil! ${imgPdfItems.length} halaman PDF A4 sudah diunduh.`);
            } catch (err) {
                setStatus('status-img-pdf', 'error', err.message || 'Gagal membuat PDF.');
            } finally {
                btn.disabled = false;
            }
        }

        // ============================================================
        // 5. KOMPRES GAMBAR
        // ============================================================
        let compressSourceFiles = [];
        let compressedOutputs = [];

        function onSelectCompressImages(input) {
            if (!input.files || !input.files.length) return;
            compressSourceFiles = Array.from(input.files);
            runCompressImages();
        }

        function compressOneImage(file, quality, maxWidth) {
            return new Promise((resolve, reject) => {
                const img = new Image();
                const url = URL.createObjectURL(file);
                img.onload = () => {
                    let w = img.naturalWidth;
                    let h = img.naturalHeight;
                    if (maxWidth > 0 && w > maxWidth) {
                        h = Math.round((h * maxWidth) / w);
                        w = maxWidth;
                    }
                    const canvas = document.createElement('canvas');
                    canvas.width = w;
                    canvas.height = h;
                    const ctx = canvas.getContext('2d');
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, w, h);
                    ctx.drawImage(img, 0, 0, w, h);
                    URL.revokeObjectURL(url);
                    canvas.toBlob((blob) => {
                        if (!blob) return reject(new Error('Gagal kompres ' + file.name));
                        resolve({
                            name: file.name.replace(/\.\w+$/, '') + '_kompres.jpg',
                            origSize: file.size,
                            newSize: blob.size,
                            blob,
                            preview: URL.createObjectURL(blob)
                        });
                    }, 'image/jpeg', quality);
                };
                img.onerror = () => { URL.revokeObjectURL(url); reject(new Error('File bukan gambar valid.')); };
                img.src = url;
            });
        }

        async function runCompressImages() {
            if (!compressSourceFiles.length) return;
            const quality = (parseInt(document.getElementById('compress-quality').value, 10) || 75) / 100;
            const maxW = parseInt(document.getElementById('compress-maxw').value, 10) || 0;
            try {
                compressedOutputs.forEach(o => URL.revokeObjectURL(o.preview));
                compressedOutputs = [];
                for (const f of compressSourceFiles) {
                    compressedOutputs.push(await compressOneImage(f, quality, maxW));
                }
                const wrap = document.getElementById('compress-results');
                wrap.innerHTML = compressedOutputs.map((o, i) => {
                    const pct = o.origSize > 0 ? Math.max(0, Math.round((1 - o.newSize / o.origSize) * 100)) : 0;
                    return `
                        <div class="file-row">
                            <div class="file-row-left">
                                <img src="${o.preview}" class="file-thumb" alt="Hasil">
                                <div style="min-width:0;">
                                    <div class="file-name">${o.name.replace(/</g, '&lt;')}</div>
                                    <div class="file-meta">${formatBytes(o.origSize)} → <strong style="color:#059669;">${formatBytes(o.newSize)}</strong> (hemat ${pct}%)</div>
                                </div>
                            </div>
                            <div class="file-actions">
                                <button type="button" class="btn btn-primary" style="padding:5px 11px; font-size:12px;" onclick="downloadCompressed(${i})">Unduh</button>
                            </div>
                        </div>
                    `;
                }).join('');
                setStatus('status-compress-img', 'success', `${compressedOutputs.length} gambar selesai dikompresi. Geser slider jika ingin ukuran lebih kecil.`);
            } catch (err) {
                setStatus('status-compress-img', 'error', err.message || 'Gagal mengompresi gambar.');
            }
        }

        function downloadCompressed(i) {
            const item = compressedOutputs[i];
            if (item) triggerBlobDownload(item.blob, item.name);
        }

        // ============================================================
        // 6. KALKULATOR SAMPEL SLOVIN (BAB III)
        // ============================================================
        let lastSlovinParagraph = '';

        function calcSlovin() {
            const N = parseFloat(document.getElementById('slovin-n').value);
            const e = parseFloat(document.getElementById('slovin-e').value);
            if (!N || N < 2 || !e) {
                document.getElementById('slovin-final-n').textContent = '—';
                document.getElementById('slovin-steps-box').textContent = 'Masukkan jumlah populasi N (minimal 2).';
                document.getElementById('slovin-para-box').textContent = '';
                return;
            }
            const e2 = e * e;
            const ne2 = N * e2;
            const denom = 1 + ne2;
            const rawN = N / denom;
            const roundedN = Math.ceil(rawN);
            const pctLabel = Math.round(e * 100) + '%';

            document.getElementById('slovin-final-n').textContent = `${roundedN.toLocaleString('id-ID')} Responden`;
            document.getElementById('slovin-steps-box').textContent =
                `Rumus Slovin : n = N / (1 + N × e²)\n` +
                `Diketahui    : N = ${N.toLocaleString('id-ID')}, e = ${e} (${pctLabel})\n` +
                `Perhitungan  : n = ${N} / (1 + ${N} × ${e}²)\n` +
                `             : n = ${N} / (1 + ${ne2.toFixed(4)})\n` +
                `             : n = ${N} / ${denom.toFixed(4)} = ${rawN.toFixed(2)}\n` +
                `Pembulatan   : n = ${roundedN} responden`;

            lastSlovinParagraph = `Penentuan jumlah sampel dalam penelitian ini menggunakan rumus Slovin dengan ukuran populasi sebanyak N = ${N.toLocaleString('id-ID')} dan batas toleransi kesalahan (margin of error) sebesar e = ${pctLabel} (${String(e).replace('.', ',')}). Berdasarkan hasil perhitungan n = ${N} / (1 + ${N} × (${String(e).replace('.', ',')})²) = ${rawN.toFixed(2).replace('.', ',')}, maka jumlah sampel minimal yang dibulatkan ke atas dan digunakan dalam penelitian ini adalah sebanyak ${roundedN} responden.`;
            document.getElementById('slovin-para-box').textContent = lastSlovinParagraph;
        }

        function copySlovinParagraph() {
            if (!lastSlovinParagraph) return;
            navigator.clipboard.writeText(lastSlovinParagraph).then(() => {
                setStatus('status-slovin', 'success', '✓ Paragraf perhitungan Slovin berhasil disalin! Siap ditempel ke Bab III.');
            }).catch(() => {
                setStatus('status-slovin', 'error', 'Gagal menyalin otomatis. Silakan blok dan salin manual.');
            });
        }

        // ============================================================
        // 7. HITUNG KATA & ESTIMASI HALAMAN A4
        // ============================================================
        function updateWordCount() {
            const text = document.getElementById('wc-textarea').value || '';
            const trimmed = text.trim();
            const words = trimmed ? trimmed.split(/\s+/).filter(Boolean).length : 0;
            const chars = text.length;
            const charsNoSpace = text.replace(/\s/g, '').length;
            const paras = trimmed ? trimmed.split(/\n\s*\n|\n/).filter(p => p.trim() !== '').length : 0;
            const sentences = trimmed ? trimmed.split(/[.!?]+/).filter(s => s.trim().length > 2).length : 0;
            const pages15 = words > 0 ? Math.max(1, Math.ceil(words / 320)) : 0;
            const pages20 = words > 0 ? Math.max(1, Math.ceil(words / 250)) : 0;
            const readMin = words > 0 ? Math.max(1, Math.ceil(words / 140)) : 0;

            document.getElementById('wc-words').textContent = words.toLocaleString('id-ID');
            document.getElementById('wc-read-time').textContent = `±${readMin} menit presentasi`;
            document.getElementById('wc-chars').textContent = chars.toLocaleString('id-ID');
            document.getElementById('wc-chars-nospace').textContent = `${charsNoSpace.toLocaleString('id-ID')} tanpa spasi`;
            document.getElementById('wc-paras').textContent = paras.toLocaleString('id-ID');
            document.getElementById('wc-sentences').textContent = `${sentences.toLocaleString('id-ID')} kalimat`;
            document.getElementById('wc-pages-15').textContent = `${pages15} hal`;
            document.getElementById('wc-pages-20').textContent = `Spasi 1,5 · (${pages20} hal spasi 2)`;
        }

        function clearWordCount() {
            document.getElementById('wc-textarea').value = '';
            document.getElementById('status-wc').className = 'status-box';
            updateWordCount();
        }

        async function loadWordCountFile(input) {
            if (!input.files || !input.files[0]) return;
            const f = input.files[0];
            const ext = (f.name.split('.').pop() || '').toLowerCase();
            try {
                setStatus('status-wc', 'info', `Membaca teks dari ${f.name}...`);
                if (ext === 'txt') {
                    document.getElementById('wc-textarea').value = await f.text();
                } else if (ext === 'pdf') {
                    const pages = await extractPagesFromPdfFile(f);
                    document.getElementById('wc-textarea').value = pages.map(p => p.paragraphs.join('\n\n')).join('\n\n');
                } else if (ext === 'docx') {
                    const fd = new FormData();
                    fd.append('file', f);
                    const res = await fetch('{{ route("tools.extract.docxText") }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken },
                        body: fd
                    });
                    const data = await res.json();
                    if (!res.ok || data.status !== 'success') throw new Error(data.message || 'Gagal membaca .docx');
                    document.getElementById('wc-textarea').value = data.text || '';
                } else {
                    throw new Error('Format harus .txt, .docx, atau .pdf');
                }
                updateWordCount();
                setStatus('status-wc', 'success', `Teks dari ${f.name} berhasil dimuat.`);
            } catch (err) {
                setStatus('status-wc', 'error', err.message || 'Gagal membaca file.');
            }
            input.value = '';
        }

        // ============================================================
        // 0. GENERATOR FLOWCHART SKRIPSI (AI + EDITOR SIMBOL ISO + PNG HD)
        // ============================================================
        const FLOWCHART_PRESETS = {
            kuantitatif: {
                caption: 'Gambar 3.1 Diagram Alur Penelitian Kuantitatif',
                steps: [
                    { type: 'start', label: 'Mulai' },
                    { type: 'process', label: 'Identifikasi Masalah & Kajian Literatur' },
                    { type: 'process', label: 'Perumusan Hipotesis & Penyusunan Kuesioner' },
                    { type: 'io', label: 'Pengumpulan Data Sampel Responden' },
                    { type: 'decision', label: 'Uji Validitas & Reliabilitas Terpenuhi?', no_label: 'Revisi Kuesioner' },
                    { type: 'process', label: 'Uji Asumsi Klasik & Analisis Regresi / SEM' },
                    { type: 'process', label: 'Pembahasan Hasil & Penarikan Kesimpulan' },
                    { type: 'end', label: 'Selesai' }
                ]
            },
            kualitatif: {
                caption: 'Gambar 3.1 Diagram Alur Penelitian Kualitatif',
                steps: [
                    { type: 'start', label: 'Mulai' },
                    { type: 'process', label: 'Penentuan Fokus & Lokasi Penelitian' },
                    { type: 'io', label: 'Wawancara Mendalam, Observasi & Dokumentasi' },
                    { type: 'process', label: 'Reduksi Data & Penyajian Temuan Lapangan' },
                    { type: 'decision', label: 'Data Jenuh & Terverifikasi (Triangulasi)?', no_label: 'Gali Data Lanjutan' },
                    { type: 'process', label: 'Interpretasi Makna & Penarikan Kesimpulan' },
                    { type: 'end', label: 'Selesai' }
                ]
            },
            rnd: {
                caption: 'Gambar 3.1 Flowchart Pengembangan Sistem & Pengujian',
                steps: [
                    { type: 'start', label: 'Mulai' },
                    { type: 'io', label: 'Pengumpulan Dataset & Analisis Kebutuhan' },
                    { type: 'process', label: 'Perancangan Arsitektur & Pra-Pemrosesan Data' },
                    { type: 'process', label: 'Implementasi Sistem / Pelatihan Model' },
                    { type: 'decision', label: 'Hasil Uji Kelayakan / Akurasi Optimal?', no_label: 'Evaluasi & Perbaikan' },
                    { type: 'io', label: 'Dokumentasi Hasil & Laporan Skripsi' },
                    { type: 'end', label: 'Selesai' }
                ]
            },
            kerangka: {
                caption: 'Gambar 2.1 Bagan Kerangka Berpikir Penelitian',
                steps: [
                    { type: 'start', label: 'Fenomena & Permasalahan Lapangan' },
                    { type: 'process', label: 'Landasan Teori & Penelitian Terdahulu' },
                    { type: 'io', label: 'Identifikasi Variabel Independen & Dependen' },
                    { type: 'process', label: 'Pengujian Empiris & Analisis Data' },
                    { type: 'end', label: 'Temuan Ilmiah & Rekomendasi Solusi' }
                ]
            }
        };

        let fcSteps = JSON.parse(JSON.stringify(FLOWCHART_PRESETS.kuantitatif.steps));
        let lastFlowchartSvgString = '';

        function escapeSvgXml(s) {
            return String(s || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function wrapFlowchartLines(text, maxChars) {
            const words = String(text || '').trim().split(/\s+/).filter(Boolean);
            if (!words.length) return [''];
            const lines = [];
            let cur = '';
            for (const w of words) {
                const candidate = cur ? (cur + ' ' + w) : w;
                if (candidate.length > maxChars && cur) {
                    lines.push(cur);
                    cur = w;
                } else {
                    cur = candidate;
                }
            }
            if (cur) lines.push(cur);
            return lines.slice(0, 3);
        }

        function loadFlowchartPreset(key) {
            const p = FLOWCHART_PRESETS[key];
            if (!p) return;
            fcSteps = JSON.parse(JSON.stringify(p.steps));
            document.getElementById('fc-caption-input').value = p.caption;
            renderFlowchartEditor();
            renderFlowchartSvg();
        }

        function addFlowchartStep() {
            const insertAt = Math.max(0, fcSteps.length - 1);
            fcSteps.splice(insertAt, 0, { type: 'process', label: 'Tahap Penelitian Baru' });
            renderFlowchartEditor();
            renderFlowchartSvg();
        }

        function updateFcStep(idx, field, val) {
            if (!fcSteps[idx]) return;
            fcSteps[idx][field] = val;
            if (field === 'type') {
                if (val === 'decision' && !fcSteps[idx].no_label) fcSteps[idx].no_label = 'Revisi / Ulangi';
                renderFlowchartEditor();
            }
            renderFlowchartSvg();
        }

        function moveFcStep(idx, dir) {
            const t = idx + dir;
            if (t < 0 || t >= fcSteps.length) return;
            [fcSteps[idx], fcSteps[t]] = [fcSteps[t], fcSteps[idx]];
            renderFlowchartEditor();
            renderFlowchartSvg();
        }

        function deleteFcStep(idx) {
            if (fcSteps.length <= 2) return;
            fcSteps.splice(idx, 1);
            renderFlowchartEditor();
            renderFlowchartSvg();
        }

        function renderFlowchartEditor() {
            const wrap = document.getElementById('fc-steps-editor');
            if (!wrap) return;
            wrap.innerHTML = fcSteps.map((st, i) => `
                <div class="fc-step-row">
                    <div class="fc-step-top">
                        <span class="fc-step-num">${i + 1}</span>
                        <select class="form-select" style="width:155px; min-height:32px; padding:4px 8px; font-size:12px;" onchange="updateFcStep(${i}, 'type', this.value)">
                            <option value="start" ${st.type === 'start' ? 'selected' : ''}>Mulai (Oval)</option>
                            <option value="process" ${st.type === 'process' ? 'selected' : ''}>Proses (Kotak)</option>
                            <option value="io" ${st.type === 'io' ? 'selected' : ''}>Input/Data (Jajar Genjang)</option>
                            <option value="decision" ${st.type === 'decision' ? 'selected' : ''}>Keputusan (Belah Ketupat)</option>
                            <option value="end" ${st.type === 'end' ? 'selected' : ''}>Selesai (Oval)</option>
                        </select>
                        <div style="margin-left:auto; display:flex; gap:4px;">
                            <button type="button" class="mini-btn" onclick="moveFcStep(${i}, -1)" ${i === 0 ? 'disabled' : ''}>▲</button>
                            <button type="button" class="mini-btn" onclick="moveFcStep(${i}, 1)" ${i === fcSteps.length - 1 ? 'disabled' : ''}>▼</button>
                            <button type="button" class="mini-btn danger" onclick="deleteFcStep(${i})" ${fcSteps.length <= 2 ? 'disabled' : ''}>✕</button>
                        </div>
                    </div>
                    <input type="text" class="form-input" style="min-height:34px; padding:6px 10px; font-size:12.5px;" value="${escapeSvgXml(st.label)}" placeholder="Tulis label langkah..." oninput="updateFcStep(${i}, 'label', this.value)">
                    ${st.type === 'decision' ? `
                        <input type="text" class="form-input" style="min-height:32px; padding:5px 9px; font-size:11.5px; background:#fffbeb; border-color:#fde68a;" value="${escapeSvgXml(st.no_label || 'Revisi / Ulangi')}" placeholder="Label cabang 'Tidak' (cth: Revisi Kuesioner)" oninput="updateFcStep(${i}, 'no_label', this.value)">
                    ` : ''}
                </div>
            `).join('');
        }

        function renderFlowchartSvg() {
            const styleKey = document.getElementById('fc-style-select')?.value || 'bw';
            const caption = (document.getElementById('fc-caption-input')?.value || '').trim();

            const themes = {
                bw: {
                    bg: '#ffffff', stroke: '#0f172a', text: '#0f172a', arrow: '#0f172a',
                    termFill: '#f8fafc', procFill: '#ffffff', ioFill: '#ffffff', decFill: '#f8fafc',
                    branchText: '#334155', captionColor: '#0f172a'
                },
                blue: {
                    bg: '#ffffff', stroke: '#1d4ed8', text: '#0f172a', arrow: '#2563eb',
                    termFill: '#dbeafe', procFill: '#eff6ff', ioFill: '#f0f9ff', decFill: '#fef3c7',
                    branchText: '#1d4ed8', captionColor: '#0f172a'
                },
                navy: {
                    bg: '#ffffff', stroke: '#1e293b', text: '#0f172a', arrow: '#1e3a8a',
                    termFill: '#1e3a8a', termText: '#ffffff', procFill: '#f8fafc', ioFill: '#eef2ff', decFill: '#e0e7ff',
                    branchText: '#1e3a8a', captionColor: '#0f172a'
                }
            };
            const pal = themes[styleKey] || themes.bw;

            const hasDecision = fcSteps.some(s => s.type === 'decision');
            const svgW = hasDecision ? 620 : 520;
            const cx = hasDecision ? 235 : 260;
            const boxW = 300;
            const gapY = 30;
            let y = 26;

            const nodesMeta = [];
            fcSteps.forEach((st) => {
                const maxC = st.type === 'decision' ? 20 : (st.type === 'io' ? 28 : 32);
                const lines = wrapFlowchartLines(st.label, maxC);
                let h = 48;
                if (st.type === 'start' || st.type === 'end') {
                    h = lines.length > 1 ? 52 : 44;
                } else if (st.type === 'decision') {
                    h = lines.length >= 3 ? 96 : (lines.length === 2 ? 84 : 74);
                } else {
                    h = Math.max(48, 22 + lines.length * 18);
                }
                nodesMeta.push({ st, lines, y, h, cy: y + h / 2, bottom: y + h });
                y += h + gapY;
            });

            const captionH = caption ? 34 : 10;
            const svgH = y - gapY + 24 + captionH;

            let shapes = '';
            nodesMeta.forEach((n, idx) => {
                const { st, lines, y: ny, h, cy, bottom } = n;
                const fill = (st.type === 'start' || st.type === 'end') ? pal.termFill
                    : (st.type === 'io' ? pal.ioFill : (st.type === 'decision' ? pal.decFill : pal.procFill));
                const txtColor = ((st.type === 'start' || st.type === 'end') && pal.termText) ? pal.termText : pal.text;

                if (st.type === 'start' || st.type === 'end') {
                    const tw = Math.min(boxW, Math.max(170, lines[0].length * 9 + 46));
                    shapes += `<rect x="${cx - tw / 2}" y="${ny}" width="${tw}" height="${h}" rx="${h / 2}" ry="${h / 2}" fill="${fill}" stroke="${pal.stroke}" stroke-width="1.8"/>`;
                } else if (st.type === 'io') {
                    const skew = 18;
                    const x1 = cx - boxW / 2 + skew;
                    const x2 = cx + boxW / 2;
                    const x3 = cx + boxW / 2 - skew;
                    const x4 = cx - boxW / 2;
                    shapes += `<polygon points="${x1},${ny} ${x2},${ny} ${x3},${bottom} ${x4},${bottom}" fill="${fill}" stroke="${pal.stroke}" stroke-width="1.8"/>`;
                } else if (st.type === 'decision') {
                    const dw = 128;
                    const pts = `${cx},${ny} ${cx + dw},${cy} ${cx},${bottom} ${cx - dw},${cy}`;
                    shapes += `<polygon points="${pts}" fill="${fill}" stroke="${pal.stroke}" stroke-width="1.8"/>`;

                    // Cabang kanan "Tidak" kembali ke langkah proses sebelumnya
                    const loopX = cx + dw + 128;
                    const backIdx = Math.max(0, idx - 1);
                    const targetY = nodesMeta[backIdx].cy;
                    const targetRightX = cx + boxW / 2 + 2;
                    const noTxt = st.no_label || 'Revisi / Ulangi';
                    shapes += `<polyline points="${cx + dw},${cy} ${loopX},${cy} ${loopX},${targetY} ${targetRightX},${targetY}" fill="none" stroke="${pal.arrow}" stroke-width="1.5" stroke-dasharray="4,3" marker-end="url(#fc-arrow)"/>`;
                    shapes += `<text x="${cx + dw + 10}" y="${cy - 6}" font-family="'Times New Roman', serif" font-size="11.5" font-weight="bold" fill="${pal.branchText}">Tidak</text>`;
                    shapes += `<rect x="${loopX - 66}" y="${(cy + targetY) / 2 - 12}" width="132" height="22" rx="4" fill="#ffffff" stroke="${pal.stroke}" stroke-width="0.9"/>`;
                    shapes += `<text x="${loopX}" y="${(cy + targetY) / 2 + 3}" text-anchor="middle" font-family="'Times New Roman', serif" font-size="10.5" fill="${pal.text}">${escapeSvgXml(noTxt.slice(0, 22))}</text>`;
                } else {
                    shapes += `<rect x="${cx - boxW / 2}" y="${ny}" width="${boxW}" height="${h}" rx="4" fill="${fill}" stroke="${pal.stroke}" stroke-width="1.8"/>`;
                }

                // Teks di tengah simbol
                const lineH = 16;
                const startTextY = cy - ((lines.length - 1) * lineH) / 2 + 4.5;
                lines.forEach((ln, li) => {
                    const boldAttr = (st.type === 'start' || st.type === 'end') ? 'font-weight="bold"' : '';
                    shapes += `<text x="${cx}" y="${startTextY + li * lineH}" text-anchor="middle" font-family="'Times New Roman', Times, serif" font-size="13" ${boldAttr} fill="${txtColor}">${escapeSvgXml(ln)}</text>`;
                });

                // Panah ke node berikutnya
                if (idx < nodesMeta.length - 1) {
                    const nextY = nodesMeta[idx + 1].y;
                    shapes += `<line x1="${cx}" y1="${bottom}" x2="${cx}" y2="${nextY - 3}" stroke="${pal.arrow}" stroke-width="1.6" marker-end="url(#fc-arrow)"/>`;
                    if (st.type === 'decision') {
                        shapes += `<text x="${cx + 8}" y="${bottom + 17}" font-family="'Times New Roman', serif" font-size="11.5" font-weight="bold" fill="${pal.branchText}">Ya</text>`;
                    }
                }
            });

            if (caption) {
                shapes += `<text x="${svgW / 2}" y="${svgH - 12}" text-anchor="middle" font-family="'Times New Roman', Times, serif" font-size="13.5" font-weight="bold" fill="${pal.captionColor}">${escapeSvgXml(caption)}</text>`;
            }

            lastFlowchartSvgString = `<svg xmlns="http://www.w3.org/2000/svg" width="${svgW}" height="${svgH}" viewBox="0 0 ${svgW} ${svgH}">
                <defs>
                    <marker id="fc-arrow" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="6.5" markerHeight="6.5" orient="auto-start-reverse">
                        <path d="M 0 1 L 10 5 L 0 9 z" fill="${pal.arrow}"/>
                    </marker>
                </defs>
                <rect width="100%" height="100%" fill="${pal.bg}"/>
                ${shapes}
            </svg>`;

            const previewEl = document.getElementById('fc-svg-preview');
            if (previewEl) previewEl.innerHTML = lastFlowchartSvgString;
        }

        async function generateFlowchartWithAi() {
            const topic = (document.getElementById('fc-ai-topic').value || '').trim();
            const kind = document.getElementById('fc-ai-kind').value;
            if (topic.length < 5) {
                setStatus('status-fc', 'error', 'Tuliskan judul skripsi atau topik penelitian terlebih dahulu (minimal 5 karakter).');
                return;
            }
            const btn = document.getElementById('btn-fc-ai');
            btn.disabled = true;
            btn.textContent = 'Menyusun Flowchart...';
            setStatus('status-fc', 'info', 'AI sedang menyusun alur flowchart sesuai topik penelitianmu...');
            try {
                const res = await fetch('{{ route("tools.flowchart.generate") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ topic, kind })
                });
                const data = await res.json();
                if (!res.ok || data.status !== 'success' || !Array.isArray(data.steps)) {
                    throw new Error(data.message || 'Gagal menyusun flowchart.');
                }
                fcSteps = data.steps;
                if (data.caption) document.getElementById('fc-caption-input').value = data.caption;
                renderFlowchartEditor();
                renderFlowchartSvg();
                setStatus('status-fc', 'success', `Berhasil! ${fcSteps.length} langkah flowchart siap diedit atau diunduh ke Word.`);
            } catch (err) {
                setStatus('status-fc', 'error', err.message || 'Terjadi kesalahan saat generate flowchart.');
            } finally {
                btn.disabled = false;
                btn.textContent = '✨ Buat dengan AI';
            }
        }

        function downloadFlowchartSvg() {
            if (!lastFlowchartSvgString) return;
            triggerBlobDownload(new Blob([lastFlowchartSvgString], { type: 'image/svg+xml;charset=utf-8' }), 'Flowchart_Skripsi.svg');
        }

        function downloadFlowchartPng() {
            if (!lastFlowchartSvgString) return;
            const svgBlob = new Blob([lastFlowchartSvgString], { type: 'image/svg+xml;charset=utf-8' });
            const url = URL.createObjectURL(svgBlob);
            const img = new Image();
            img.onload = () => {
                const scale = 3; // HD 3x siap cetak Microsoft Word
                const canvas = document.createElement('canvas');
                canvas.width = img.width * scale;
                canvas.height = img.height * scale;
                const ctx = canvas.getContext('2d');
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.scale(scale, scale);
                ctx.drawImage(img, 0, 0);
                URL.revokeObjectURL(url);
                canvas.toBlob((pngBlob) => {
                    if (pngBlob) triggerBlobDownload(pngBlob, 'Flowchart_Skripsi_HD.png');
                }, 'image/png');
            };
            img.onerror = () => { URL.revokeObjectURL(url); };
            img.src = url;
        }

        // Init
        renderFlowchartEditor();
        renderFlowchartSvg();
        calcSlovin();
        updateWordCount();
        const initHash = (window.location.hash || '').replace('#', '');
        if (initHash && document.getElementById('panel-' + initHash)) {
            switchTool(initHash);
        }
    </script>
</body>
</html>
