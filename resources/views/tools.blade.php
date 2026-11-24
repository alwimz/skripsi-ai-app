<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Tools Mahasiswa Gratis — Flowchart AI, Rapikan Skripsi, Kompres PDF/Word &amp; Hapus Background | SkripsiLab AI</title>
    <meta name="description"
        content="Kumpulan 10 tools mahasiswa gratis dari SkripsiLab AI: generator flowchart skripsi AI, rapikan format skripsi Word (.docx) tanpa ubah isi, konversi PDF ke Word, gabung &amp; pisah PDF, kompres PDF/Word/gambar, hapus background tanda tangan &amp; pas foto, JPG ke PDF A4, kalkulator Slovin &amp; Lemeshow, dan hitung kata.">
    <meta name="keywords"
        content="buat flowchart skripsi otomatis, rapikan format skripsi word otomatis, tools mahasiswa gratis, pdf ke word gratis, word ke pdf online, gabung pdf skripsi, pisah halaman pdf, kompres pdf siakad, kompres word docx, kompres gambar, hapus background tanda tangan pas foto, jpg ke pdf a4, kalkulator slovin lemeshow bab 3, hitung kata skripsi, skripsilab ai">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url('/tools') }}">
    <meta name="theme-color" content="#2563eb">
    <link rel="icon" type="image/svg+xml" href="/images/logo.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Client-side PDF & DOCX engines (100% gratis, privat di browser, bebas batas 4.5MB server) -->
    <script src="https://cdn.jsdelivr.net/npm/pdf-lib@1.17.1/dist/pdf-lib.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/jszip@3.10.1/dist/jszip.min.js" defer></script>

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
            'description' => 'Kumpulan 10 alat produktivitas gratis untuk mahasiswa Indonesia: Diagram & Flowchart AI, Rapikan Format Skripsi Word, PDF ke Word, Gabung & Pisah PDF, Kompres PDF/Word/Gambar, Hapus Background Gambar, JPG ke PDF A4, Kalkulator Slovin & Lemeshow, dan Hitung Kata.',
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
            overflow-x: hidden;
        }
        a { color: inherit; text-decoration: none; }
        .container {
            max-width: 1120px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Workspace Sidebar Shell (identik dengan pre_generator saat user login) */
        .ws-sidebar {
            width: 248px;
            background: #ffffff;
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 50;
            transition: transform 0.22s ease;
        }
        body.ws-sidebar-collapsed .ws-sidebar { transform: translateX(-100%); }
        body.ws-sidebar-collapsed .ws-main { margin-left: 0; }
        .ws-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(2px);
            z-index: 45;
            display: none;
        }
        .ws-backdrop.open { display: block; }
        .ws-sidebar-head {
            padding: 15px 16px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }
        .ws-brand-left { display: flex; align-items: center; gap: 10px; min-width: 0; }
        .ws-brand-name { font-size: 14px; font-weight: 700; color: var(--text-main); line-height: 1.25; }
        .ws-brand-user { font-size: 12px; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .ws-close-btn {
            background: #f8fafc;
            border: 1px solid var(--border);
            color: var(--text-secondary);
            width: 28px;
            height: 28px;
            border-radius: 7px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            flex-shrink: 0;
        }
        .ws-close-btn:hover { background: #eff6ff; color: var(--primary); }
        .ws-nav {
            padding: 14px 10px;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 3px;
            overflow-y: auto;
        }
        .ws-group {
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #94a3b8;
            padding: 10px 12px 4px;
        }
        .ws-nav-btn {
            width: 100%;
            min-height: 40px;
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 9px 12px;
            border-radius: 10px;
            color: #334155;
            font-size: 13.5px;
            font-weight: 500;
            white-space: nowrap;
            transition: background 0.15s ease, color 0.15s ease;
        }
        .ws-nav-btn:hover { background: #f1f5f9; color: var(--text-main); }
        .ws-nav-btn.active {
            background: #eff6ff;
            color: #1d4ed8;
            font-weight: 600;
            box-shadow: inset 3px 0 0 var(--primary);
        }
        .ws-nav-btn svg { width: 18px; height: 18px; color: #64748b; flex-shrink: 0; }
        .ws-nav-btn.active svg { color: var(--primary); }
        .ws-sidebar-foot {
            padding: 12px 16px;
            border-top: 1px solid #f1f5f9;
        }
        .ws-logout-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--text-muted);
            background: none;
            border: none;
            font-size: 13px;
            font-family: inherit;
            cursor: pointer;
            padding: 6px 8px;
            border-radius: 6px;
        }
        .ws-logout-btn:hover { background: #eff6ff; color: var(--text-main); }
        .ws-main {
            margin-left: 248px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            min-width: 0;
            transition: margin-left 0.22s ease;
        }
        .ws-topbar {
            position: sticky;
            top: 0;
            z-index: 35;
            height: 58px;
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border);
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .ws-breadcrumb {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            color: var(--text-muted);
            min-width: 0;
        }
        .ws-toggle-btn {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: #ffffff;
            color: var(--text-main);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            flex-shrink: 0;
        }
        .ws-toggle-btn:hover { background: #f8fafc; border-color: var(--border-strong); }
        .ws-content {
            max-width: 1060px;
            width: 100%;
            margin: 0 auto;
            padding: 24px 26px 48px;
        }
        .ws-page-title {
            font-size: 25px;
            font-weight: 800;
            letter-spacing: -0.025em;
            color: var(--text-main);
            margin-bottom: 5px;
        }
        .ws-page-sub {
            font-size: 13.5px;
            color: var(--text-secondary);
            margin-bottom: 18px;
        }
        @media (max-width: 920px) {
            .ws-sidebar { transform: translateX(-100%); }
            body.ws-mobile-open .ws-sidebar { transform: translateX(0); }
            .ws-main { margin-left: 0; }
            .ws-topbar { padding: 0 14px; }
            .ws-content { padding: 16px 14px 40px; }
            .ws-page-title { font-size: 20px; }
        }

        /* Header (untuk tamu / publik) */
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
        .topbar-actions { display: flex; align-items: center; gap: 8px; }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            min-height: 40px;
            padding: 9px 16px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.16s ease;
            text-align: center;
            line-height: 1.3;
        }
        .btn-primary {
            background: var(--primary);
            color: #ffffff;
            box-shadow: 0 6px 16px -6px rgba(37, 99, 235, 0.5);
        }
        .btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); }
        .btn-primary:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        .btn-success {
            background: #059669;
            color: #ffffff;
            box-shadow: 0 6px 16px -6px rgba(5, 150, 105, 0.45);
        }
        .btn-success:hover { background: #047857; transform: translateY(-1px); }
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

        /* Responsive Action Bar & Button Stacks */
        .tool-action-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 14px;
            padding-top: 14px;
            border-top: 1px solid #f1f5f9;
        }
        .tool-action-controls {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            flex: 1;
            min-width: 0;
        }
        .btn-row-responsive {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }
        .btn-row-responsive .btn-grow { flex: 1 1 200px; }

        /* Hero Intro (Publik) */
        .tools-hero {
            padding: 26px 0 18px;
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
            max-width: 720px;
        }

        /* Tool Selector Grid (5x2 desktop, 2x5 mobile) */
        .tools-nav-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 9px;
            margin: 16px 0 20px;
        }
        .tool-tab-card {
            background: #ffffff;
            border: 1.5px solid var(--border);
            border-radius: 12px;
            padding: 11px 11px;
            text-align: left;
            cursor: pointer;
            font-family: inherit;
            display: flex;
            flex-direction: column;
            gap: 5px;
            position: relative;
            transition: all 0.16s ease;
        }
        .tool-tab-card:hover {
            border-color: #93c5fd;
            transform: translateY(-2px);
            box-shadow: 0 8px 18px -10px rgba(37, 99, 235, 0.28);
        }
        .tool-tab-card.active {
            border-color: var(--primary);
            background: linear-gradient(180deg, #eff6ff 0%, #ffffff 100%);
            box-shadow: 0 8px 20px -10px rgba(37, 99, 235, 0.36);
        }
        .tool-tab-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            font-size: 9.5px;
            font-weight: 700;
            padding: 1px 6px;
            border-radius: 999px;
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #a7f3d0;
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
        .tool-panel { display: none; padding-bottom: 36px; }
        .tool-panel.active { display: block; animation: panelEnter 0.24s cubic-bezier(0.16, 1, 0.3, 1); }
        @keyframes panelEnter { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }

        .panel-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 22px;
            box-shadow: 0 10px 26px -16px rgba(15, 23, 42, 0.1);
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
        .panel-title { font-size: 17.5px; font-weight: 800; color: var(--text-main); letter-spacing: -0.015em; }
        .panel-desc { font-size: 13px; color: var(--text-secondary); margin-top: 3px; max-width: 680px; }
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
            border-radius: 11px;
            gap: 4px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }
        .seg-btn {
            padding: 8px 14px;
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
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);
        }

        /* Dropzone */
        .dropzone {
            border: 2px dashed #93c5fd;
            background: linear-gradient(180deg, #f8fbff 0%, #f1f7ff 100%);
            border-radius: 14px;
            padding: 24px 16px;
            text-align: center;
            cursor: pointer;
            transition: all 0.18s ease;
            margin-bottom: 14px;
        }
        .dropzone:hover, .dropzone.dragover {
            border-color: var(--primary);
            background: #eff6ff;
            transform: scale(0.997);
        }
        .dropzone-title { font-size: 14px; font-weight: 700; color: var(--text-main); margin-top: 8px; word-break: break-word; }
        .dropzone-sub { font-size: 12px; color: var(--text-muted); margin-top: 3px; }

        /* Form controls */
        .form-grid-2 {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 14px;
        }
        .form-grid-3 {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
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
        .check-card {
            display: flex;
            align-items: flex-start;
            gap: 9px;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: #f8fafc;
            font-size: 12.5px;
            color: #334155;
            cursor: pointer;
            line-height: 1.45;
        }
        .check-card input { margin-top: 2px; accent-color: var(--primary); flex-shrink: 0; }

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
            padding: 10px 12px;
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 11px;
            animation: fadeInRow 0.2s ease;
        }
        @keyframes fadeInRow { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: translateY(0); } }
        .file-row-left {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
            flex: 1;
        }
        .file-thumb {
            width: 44px;
            height: 44px;
            border-radius: 7px;
            object-fit: cover;
            border: 1px solid var(--border-strong);
            background: #ffffff;
            flex-shrink: 0;
            transition: transform 0.2s ease;
        }
        .file-pdf-thumb {
            width: 38px;
            height: 50px;
            border-radius: 6px;
            object-fit: cover;
            border: 1px solid var(--border-strong);
            background: #ffffff;
            flex-shrink: 0;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.08);
        }
        .file-badge {
            width: 36px;
            height: 36px;
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
        .file-meta { font-size: 11.5px; color: var(--text-muted); margin-top: 2px; }
        .file-actions { display: flex; align-items: center; gap: 5px; flex-shrink: 0; flex-wrap: wrap; }
        .mini-btn {
            height: 30px;
            padding: 0 9px;
            border-radius: 7px;
            border: 1px solid var(--border-strong);
            background: #ffffff;
            color: var(--text-main);
            font-size: 11.5px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }
        .mini-btn:hover { background: #f1f5f9; }
        .mini-btn:disabled { opacity: 0.45; cursor: not-allowed; }
        .mini-btn.danger { color: var(--danger); border-color: #fecaca; }

        /* Animated Processing Bar & Rich Result Card */
        .proc-box {
            display: none;
            margin-top: 14px;
            padding: 14px 16px;
            border-radius: 13px;
            background: linear-gradient(135deg, #eff6ff 0%, #f8fafc 100%);
            border: 1px solid #bfdbfe;
            animation: slideUpPop 0.25s ease;
        }
        .proc-box.active { display: block; }
        .proc-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 9px;
            font-size: 13px;
            font-weight: 600;
            color: #1e40af;
        }
        .proc-head-left { display: flex; align-items: center; gap: 9px; min-width: 0; }
        .proc-spinner {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: 2.5px solid #bfdbfe;
            border-top-color: #2563eb;
            animation: spin 0.75s linear infinite;
            flex-shrink: 0;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .proc-track {
            width: 100%;
            height: 8px;
            background: #dbeafe;
            border-radius: 999px;
            overflow: hidden;
            position: relative;
        }
        .proc-fill {
            height: 100%;
            width: 20%;
            border-radius: 999px;
            background: linear-gradient(90deg, #2563eb 0%, #3b82f6 50%, #0ea5e9 100%);
            background-size: 200% 100%;
            transition: width 0.28s ease;
            animation: shimmerBar 1.4s linear infinite;
        }
        @keyframes shimmerBar {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        .result-card {
            display: none;
            margin-top: 14px;
            padding: 16px 18px;
            border-radius: 14px;
            background: linear-gradient(135deg, #ecfdf5 0%, #ffffff 65%);
            border: 1.5px solid #6ee7b7;
            box-shadow: 0 10px 24px -14px rgba(5, 150, 105, 0.3);
            animation: slideUpPop 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .result-card.active { display: block; }
        @keyframes slideUpPop {
            from { opacity: 0; transform: translateY(8px) scale(0.99); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .result-card-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
        }
        .result-info {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
            flex: 1;
        }
        .result-check {
            width: 40px;
            height: 40px;
            border-radius: 11px;
            background: #059669;
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 6px 14px -4px rgba(5, 150, 105, 0.5);
        }
        .result-title { font-size: 14px; font-weight: 800; color: #065f46; word-break: break-word; }
        .result-meta { font-size: 12.5px; color: #047857; margin-top: 2px; }
        .result-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .audit-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px dashed #a7f3d0;
        }
        .audit-pill {
            font-size: 11.5px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 999px;
            background: #ffffff;
            border: 1px solid #a7f3d0;
            color: #065f46;
        }

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

        /* Split PDF Visual Page Grid */
        .split-thumb-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(92px, 1fr));
            gap: 10px;
            max-height: 310px;
            overflow-y: auto;
            padding: 12px;
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 12px;
            margin-bottom: 14px;
        }
        .split-page-card {
            position: relative;
            background: #ffffff;
            border: 2px solid var(--border);
            border-radius: 9px;
            padding: 6px;
            cursor: pointer;
            text-align: center;
            transition: all 0.15s ease;
            user-select: none;
        }
        .split-page-card:hover { border-color: #93c5fd; transform: translateY(-1px); }
        .split-page-card.selected {
            border-color: var(--primary);
            background: #eff6ff;
            box-shadow: 0 4px 12px -4px rgba(37, 99, 235, 0.35);
        }
        .split-page-card.delete-mode.selected {
            border-color: #dc2626;
            background: #fef2f2;
            opacity: 0.78;
        }
        .split-page-card canvas, .split-page-card img {
            width: 100%;
            height: 92px;
            object-fit: contain;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            display: block;
        }
        .split-page-num {
            font-size: 11px;
            font-weight: 700;
            color: var(--text-secondary);
            margin-top: 4px;
        }
        .split-page-card.selected .split-page-num { color: var(--primary); }
        .split-page-card.delete-mode.selected .split-page-num { color: #dc2626; text-decoration: line-through; }
        .split-page-tick {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 19px;
            height: 19px;
            border-radius: 50%;
            background: var(--primary);
            color: #ffffff;
            font-size: 11px;
            font-weight: 800;
            display: none;
            align-items: center;
            justify-content: center;
        }
        .split-page-card.selected .split-page-tick { display: inline-flex; }
        .split-page-card.delete-mode.selected .split-page-tick { background: #dc2626; }

        .page-picker-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 14px;
            max-height: 150px;
            overflow-y: auto;
            padding: 10px;
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 10px;
        }
        .page-pill {
            min-width: 36px;
            height: 32px;
            padding: 0 8px;
            border-radius: 7px;
            border: 1px solid var(--border-strong);
            background: #ffffff;
            color: var(--text-secondary);
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            font-family: inherit;
        }
        .page-pill.selected { background: var(--primary); border-color: var(--primary); color: #ffffff; }
        .page-pill.delete-mode.selected { background: #dc2626; border-color: #dc2626; color: #ffffff; }

        /* Remove Background Checkerboard & Swatches */
        .bg-preview-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            margin: 14px 0;
        }
        .bg-stage {
            border: 1px solid var(--border-strong);
            border-radius: 12px;
            overflow: hidden;
            background: #ffffff;
        }
        .bg-stage-head {
            padding: 8px 12px;
            font-size: 12px;
            font-weight: 700;
            color: var(--text-secondary);
            background: #f8fafc;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .bg-checker {
            min-height: 220px;
            max-height: 320px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px;
            background-color: #f8fafc;
            background-image:
                linear-gradient(45deg, #e2e8f0 25%, transparent 25%),
                linear-gradient(-45deg, #e2e8f0 25%, transparent 25%),
                linear-gradient(45deg, transparent 75%, #e2e8f0 75%),
                linear-gradient(-45deg, transparent 75%, #e2e8f0 75%);
            background-size: 18px 18px;
            background-position: 0 0, 0 9px, 9px -9px, -9px 0;
        }
        .bg-checker img, .bg-checker canvas {
            max-width: 100%;
            max-height: 290px;
            object-fit: contain;
            display: block;
            border-radius: 4px;
        }
        .swatch-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            margin-top: 6px;
        }
        .swatch-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 11px;
            border-radius: 999px;
            border: 1.5px solid var(--border-strong);
            background: #ffffff;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-main);
            cursor: pointer;
            font-family: inherit;
        }
        .swatch-btn.active { border-color: var(--primary); background: var(--primary-soft); color: var(--primary); }
        .swatch-dot {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            border: 1px solid rgba(15, 23, 42, 0.25);
            display: inline-block;
        }

        /* Stats, Slovin & Word Count */
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

        .slovin-result {
            background: linear-gradient(135deg, #eff6ff 0%, #ffffff 100%);
            border: 1px solid #bfdbfe;
            border-radius: 14px;
            padding: 18px;
            margin-top: 14px;
        }
        .slovin-num { font-size: 28px; font-weight: 800; color: var(--primary); line-height: 1.15; }
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
            overflow-x: auto;
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
        .compare-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            font-size: 12.5px;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
        }
        .compare-table th, .compare-table td {
            border: 1px solid var(--border);
            padding: 7px 10px;
            text-align: center;
        }
        .compare-table th { background: #f1f5f9; font-weight: 700; color: #334155; }

        .preset-row { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px; }
        .preset-chip {
            padding: 6px 11px;
            border-radius: 999px;
            border: 1px solid var(--border-strong);
            background: #ffffff;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
            cursor: pointer;
            font-family: inherit;
            transition: all 0.14s ease;
        }
        .preset-chip:hover, .preset-chip.active {
            background: var(--primary-soft);
            border-color: var(--primary);
            color: var(--primary);
        }
        .top-words-wrap { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 12px; }
        .top-word-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 999px;
            background: #f1f5f9;
            border: 1px solid var(--border);
            font-size: 11.5px;
            color: #334155;
            font-weight: 600;
        }
        .top-word-pill span { color: var(--primary); font-weight: 800; }
        .abstract-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 12px;
            padding: 6px 12px;
            border-radius: 9px;
            font-size: 12px;
            font-weight: 600;
            background: #f8fafc;
            border: 1px solid var(--border);
            color: #475569;
        }
        .abstract-badge.ideal { background: #ecfdf5; border-color: #a7f3d0; color: #047857; }
        .abstract-badge.warn { background: #fffbeb; border-color: #fde68a; color: #b45309; }

        /* Flowchart Studio */
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
        .fc-ai-row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
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
            flex-wrap: wrap;
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
            min-height: 340px;
        }
        .fc-preview-paper.transparent-bg {
            background-image:
                linear-gradient(45deg, #e2e8f0 25%, transparent 25%),
                linear-gradient(-45deg, #e2e8f0 25%, transparent 25%),
                linear-gradient(45deg, transparent 75%, #e2e8f0 75%),
                linear-gradient(-45deg, transparent 75%, #e2e8f0 75%);
            background-size: 16px 16px;
            background-position: 0 0, 0 8px, 8px -8px, -8px 0;
        }
        .fc-preview-paper svg { max-width: 100%; height: auto; display: block; }
        .fc-export-bar {
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr;
            gap: 8px;
            margin-top: 12px;
        }

        .footer {
            border-top: 1px solid var(--border);
            padding: 20px 0;
            font-size: 12.5px;
            color: var(--text-muted);
            text-align: center;
            background: #ffffff;
        }

        @media (max-width: 980px) {
            .tools-nav-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .fc-layout { grid-template-columns: 1fr; }
            .form-grid-3 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 720px) {
            .container { padding: 0 14px; }
            .topbar { height: 56px; }
            .brand { font-size: 14.5px; gap: 7px; }
            .brand img { width: 26px; height: 26px; }
            .brand-pill { display: none; }
            .tools-nav-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
            .tool-tab-card { padding: 10px; }
            .panel-card { padding: 15px 13px; border-radius: 14px; }
            .form-grid-2, .form-grid-3, .bg-preview-grid { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .file-row { flex-direction: column; align-items: stretch; }
            .file-actions { justify-content: flex-end; border-top: 1px solid #e2e8f0; padding-top: 7px; }

            /* Mobile Responsive Buttons & Action Bars di SEMUA tools */
            .seg-switch { display: grid; grid-template-columns: repeat(auto-fit, minmax(110px, 1fr)); width: 100%; }
            .seg-btn { text-align: center; padding: 8px 8px; font-size: 12px; }
            .tool-action-bar { flex-direction: column; align-items: stretch; gap: 10px; }
            .tool-action-controls { flex-direction: column; align-items: stretch; width: 100%; }
            .tool-action-bar > .btn,
            .tool-action-bar .btn-row-responsive { width: 100%; }
            .btn-row-responsive { display: grid; grid-template-columns: 1fr; width: 100%; }
            .btn-row-responsive.two-col-mobile { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .fc-ai-row { flex-direction: column; align-items: stretch; }
            .fc-ai-row .btn, .fc-ai-row .form-select { width: 100% !important; min-width: 0 !important; }
            .fc-export-bar { grid-template-columns: 1fr 1fr; }
            .fc-export-bar .fc-btn-main { grid-column: 1 / -1; }
            .result-card-top { flex-direction: column; align-items: stretch; }
            .result-actions { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); width: 100%; }
            .result-actions.single-action { grid-template-columns: 1fr; }
            .slovin-head-row { flex-direction: column; align-items: stretch !important; }
            .slovin-head-row .btn { width: 100%; }
            .wc-head-actions { width: 100%; display: grid !important; grid-template-columns: 1fr auto; }
        }
    </style>
</head>
<body>
    @auth
        @php $authUser = auth()->user(); @endphp
        <div class="ws-backdrop" id="ws-backdrop" onclick="closeWsSidebarMobile()"></div>

        <aside class="ws-sidebar" id="ws-sidebar">
            <div class="ws-sidebar-head">
                <div class="ws-brand-left">
                    <a href="/" style="display:block; flex-shrink:0;">
                        <img src="/images/logo.svg" alt="SkripsiLab AI" style="width:32px; height:32px; border-radius:8px; display:block;">
                    </a>
                    <div style="overflow:hidden;">
                        <div class="ws-brand-name">SkripsiLab AI</div>
                        <div class="ws-brand-user">{{ $authUser->name }}</div>
                    </div>
                </div>
                <button type="button" class="ws-close-btn" onclick="toggleWsSidebar()" title="Tutup / Buka Sidebar">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>

            <nav class="ws-nav">
                <div class="ws-group">Utama</div>
                <a href="{{ route('app.create', ['tab' => 'dashboard']) }}" class="ws-nav-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('app.create', ['tab' => 'projects']) }}" class="ws-nav-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    <span style="flex:1;">Proyek Saya</span>
                    <span style="font-size:11px; font-weight:700; padding:1px 7px; border-radius:999px; background:var(--primary-soft); color:var(--primary);">{{ $projectCount ?? 0 }}</span>
                </a>

                <div class="ws-group">Penulisan &amp; Riset</div>
                <a href="{{ route('app.create', ['tab' => 'titles']) }}" class="ws-nav-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <span>Cari Judul</span>
                </a>
                <a href="{{ route('app.create', ['tab' => 'ppt']) }}" class="ws-nav-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                    <span>Generate PPT Sidang</span>
                </a>
                <a href="{{ route('app.create', ['tab' => 'defense']) }}" class="ws-nav-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                    <span>Simulasi Sidang</span>
                </a>
                <a href="{{ route('app.create', ['tab' => 'journals']) }}" class="ws-nav-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                    <span>Cari Jurnal</span>
                </a>
                <a href="{{ route('app.create', ['tab' => 'paraphrase']) }}" class="ws-nav-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                    <span>Parafrase</span>
                </a>
                {{-- Menu Cek Plagiasi disembunyikan sementara --}}
                <a href="{{ route('app.create', ['tab' => 'olahdata']) }}" class="ws-nav-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 20V10"/><path d="M12 20V4"/><path d="M6 20v-6"/></svg>
                    <span style="flex:1;">Olah Data</span>
                    <span style="font-size:10px; font-weight:700; padding:1px 6px; border-radius:999px; background:#ecfdf5; color:#059669;">SPSS</span>
                </a>
                <a href="{{ route('app.create', ['tab' => 'qa']) }}" class="ws-nav-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 2 3 3 6 3s6-1 6-3v-5"/></svg>
                    <span>Dosen AI</span>
                </a>
                <a href="{{ route('tools.hub') }}" class="ws-nav-btn active">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                    <span style="flex:1;">Tools Mahasiswa</span>
                    <span style="font-size:10px; font-weight:700; padding:1px 6px; border-radius:999px; background:#ecfdf5; color:#059669;">10</span>
                </a>

                <div class="ws-group">Akun &amp; Bonus</div>
                <a href="{{ route('app.create', ['tab' => 'account']) }}" class="ws-nav-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <span>Akun Saya</span>
                </a>
                <a href="{{ route('app.create', ['tab' => 'referral']) }}" class="ws-nav-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                    <span>Ajak Teman (Referral)</span>
                </a>

                <div class="ws-group">Bantuan &amp; Pengembang</div>
                <a href="{{ route('app.create', ['tab' => 'feedback']) }}" class="ws-nav-btn" style="color:#0284c7;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                    <span>Saran &amp; Laporan</span>
                </a>
            </nav>

            <div class="ws-sidebar-foot">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="ws-logout-btn" title="Keluar">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        <span>Keluar</span>
                    </button>
                </form>
            </div>
        </aside>

        <div class="ws-main">
            <header class="ws-topbar">
                <div class="ws-breadcrumb">
                    <button type="button" class="ws-toggle-btn" onclick="toggleWsSidebar()" title="Buka / Tutup Sidebar" aria-label="Toggle Sidebar">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                    </button>
                    <a href="{{ route('app.create', ['tab' => 'dashboard']) }}" style="display:inline-flex; align-items:center; gap:8px; color:var(--text-main); font-weight:700; font-size:14px;">
                        <img src="/images/logo.svg" alt="SkripsiLab AI" style="width:24px; height:24px; border-radius:6px; display:block;">
                        <span>SkripsiLab AI</span>
                    </a>
                </div>
            </header>

            <main class="ws-content">
                <h1 class="ws-page-title">Tools Mahasiswa</h1>
                <p class="ws-page-sub">10 alat lengkap gratis untuk kebutuhan harian skripsi &amp; kuliah: Flowchart AI, Rapikan Skripsi Word, PDF ⇄ Word, Gabung &amp; Pisah PDF, Kompres PDF/Word/Gambar, Hapus Background, Foto ke PDF A4, Slovin/Lemeshow, dan Hitung Kata.</p>
    @else
        <header class="topbar">
            <div class="container topbar-inner">
                <a href="/" class="brand">
                    <img src="/images/logo.svg" alt="SkripsiLab AI">
                    <span>SkripsiLab AI</span>
                    <span class="brand-pill">Tools Mahasiswa</span>
                </a>
                <div class="topbar-actions">
                    <a href="/" class="btn btn-outline">Beranda</a>
                    <a href="{{ route('register') }}" class="btn btn-primary">Coba Gratis</a>
                </div>
            </div>
        </header>

        <section class="tools-hero">
            <div class="container">
                <div class="hero-eyebrow">10 Alat Produktivitas Akademik · 100% Gratis</div>
                <h1>Tools Mahasiswa SkripsiLab AI</h1>
                <p>Buat flowchart skripsi AI, rapikan tata letak Word skripsi tanpa mengubah isi, konversi PDF ⇄ Word, gabung &amp; pisah PDF, kompres PDF/Word/gambar, hapus background tanda tangan &amp; pas foto, hingga kalkulator sampel Bab III.</p>
            </div>
        </section>

        <main class="container">
    @endauth

        <!-- Grid Pemilih 10 Tools (5x2 Desktop, 2x5 HP) -->
        <div class="tools-nav-grid" role="tablist" aria-label="Pilih Tool Mahasiswa">
            <button type="button" class="tool-tab-card active" data-tool="flowchart" onclick="switchTool('flowchart', true)">
                <span class="tool-tab-icon">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="7" y="2" width="10" height="5" rx="2.5"/><path d="M12 7v3"/><rect x="5" y="10" width="14" height="5" rx="1"/><path d="M12 15v3"/><rect x="7" y="18" width="10" height="4" rx="2"/></svg>
                </span>
                <span class="tool-tab-name">Diagram &amp; Flowchart</span>
                <span class="tool-tab-sub">AI + PNG HD &amp; SVG</span>
            </button>

            <button type="button" class="tool-tab-card" data-tool="tidy-docx" onclick="switchTool('tidy-docx', true)">
                <span class="tool-tab-badge">Baru</span>
                <span class="tool-tab-icon">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l2.4 7.4L22 12l-7.6 2.6L12 22l-2.4-7.4L2 12l7.6-2.6z"/></svg>
                </span>
                <span class="tool-tab-name">Rapikan Skripsi</span>
                <span class="tool-tab-sub">Tata Word tanpa ubah isi</span>
            </button>

            <button type="button" class="tool-tab-card" data-tool="pdf-word" onclick="switchTool('pdf-word', true)">
                <span class="tool-tab-icon">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 15h6"/><path d="M12 12l3 3-3 3"/></svg>
                </span>
                <span class="tool-tab-name">PDF ⇄ Word</span>
                <span class="tool-tab-sub">.pdf ke .docx &amp; sebaliknya</span>
            </button>

            <button type="button" class="tool-tab-card" data-tool="merge-pdf" onclick="switchTool('merge-pdf', true)">
                <span class="tool-tab-icon">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="8" height="10" rx="1.5"/><rect x="13" y="3" width="8" height="10" rx="1.5"/><path d="M7 13v4a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-4"/></svg>
                </span>
                <span class="tool-tab-name">Gabung PDF</span>
                <span class="tool-tab-sub">Satukan bab &amp; lampiran</span>
            </button>

            <button type="button" class="tool-tab-card" data-tool="split-pdf" onclick="switchTool('split-pdf', true)">
                <span class="tool-tab-icon">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><line x1="20" y1="4" x2="8.12" y2="15.88"/><line x1="14.47" y1="14.48" x2="20" y2="20"/><line x1="8.12" y1="8.12" x2="12" y2="12"/></svg>
                </span>
                <span class="tool-tab-name">Pisah PDF</span>
                <span class="tool-tab-sub">Ambil / hapus halaman</span>
            </button>

            <button type="button" class="tool-tab-card" data-tool="compress" onclick="switchTool('compress', true)">
                <span class="tool-tab-badge">3-in-1</span>
                <span class="tool-tab-icon">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 14 10 14 10 20"/><polyline points="20 10 14 10 14 4"/><line x1="14" y1="10" x2="21" y2="3"/><line x1="3" y1="21" x2="10" y2="14"/></svg>
                </span>
                <span class="tool-tab-name">Kompres File</span>
                <span class="tool-tab-sub">PDF, Word &amp; Gambar</span>
            </button>

            <button type="button" class="tool-tab-card" data-tool="remove-bg" onclick="switchTool('remove-bg', true)">
                <span class="tool-tab-badge">Baru</span>
                <span class="tool-tab-icon">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 15l5-5 4 4 3-3 6 6"/><circle cx="9" cy="8.5" r="1.5"/></svg>
                </span>
                <span class="tool-tab-name">Hapus Background</span>
                <span class="tool-tab-sub">TTD transparan &amp; pas foto</span>
            </button>

            <button type="button" class="tool-tab-card" data-tool="img-pdf" onclick="switchTool('img-pdf', true)">
                <span class="tool-tab-icon">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                </span>
                <span class="tool-tab-name">JPG/PNG → PDF</span>
                <span class="tool-tab-sub">Foto berkas ke PDF A4</span>
            </button>

            <button type="button" class="tool-tab-card" data-tool="slovin" onclick="switchTool('slovin', true)">
                <span class="tool-tab-icon">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="6" x2="16" y2="6"/><line x1="8" y1="11" x2="10" y2="11"/><line x1="14" y1="11" x2="16" y2="11"/><line x1="8" y1="16" x2="10" y2="16"/><line x1="14" y1="16" x2="16" y2="16"/></svg>
                </span>
                <span class="tool-tab-name">Kalkulator Sampel</span>
                <span class="tool-tab-sub">Slovin &amp; Lemeshow Bab III</span>
            </button>

            <button type="button" class="tool-tab-card" data-tool="word-count" onclick="switchTool('word-count', true)">
                <span class="tool-tab-icon">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="14" y2="12"/><line x1="4" y1="18" x2="18" y2="18"/></svg>
                </span>
                <span class="tool-tab-name">Hitung Kata</span>
                <span class="tool-tab-sub">Abstrak &amp; estimasi A4</span>
            </button>
        </div>

        <div id="tools-active-anchor"></div>

        <!-- ======================================================= -->
        <!-- 1. GENERATOR FLOWCHART SKRIPSI & PROPOSAL (AI + VISUAL) -->
        <!-- ======================================================= -->
        <section class="tool-panel active" id="panel-flowchart">
            <div class="panel-card">
                <div class="panel-head">
                    <div>
                        <h2 class="panel-title">Generator Diagram &amp; Flowchart Skripsi</h2>
                        <p class="panel-desc">Buat diagram alur penelitian (Bab III), kerangka berpikir (Bab II), diagram sistem, atau flowchart analisis data otomatis dengan AI — edit simbol standar ISO lalu unduh PNG HD bersih siap tempel Word.</p>
                    </div>
                    <span class="privacy-badge">Otomatis AI · Siap Tempel Word</span>
                </div>

                <div class="fc-layout">
                    <!-- Kolom Kiri: AI + Preset + Editor Langkah -->
                    <div>
                        <div class="fc-ai-box">
                            <div style="font-size:12.5px; font-weight:700; color:#1d4ed8; margin-bottom:8px;">Buat Diagram Otomatis Pakai AI</div>
                            <div style="margin-bottom:8px;">
                                <label class="form-label" for="fc-ai-topic">Judul Skripsi / Topik Penelitian</label>
                                <input type="text" id="fc-ai-topic" class="form-input" placeholder="cth: Pengaruh Kualitas Layanan &amp; Harga terhadap Kepuasan Pelanggan">
                            </div>
                            <div class="fc-ai-row">
                                <select id="fc-ai-kind" class="form-select" style="flex:1; min-width:180px;" onchange="onChangeFlowchartKind(this.value)">
                                    <option value="prosedur">1. Flowchart Prosedur Penelitian (Bab III)</option>
                                    <option value="kerangka">2. Diagram Kerangka Berpikir / Variabel (Bab II)</option>
                                    <option value="sistem">3. Diagram Arsitektur Sistem &amp; Database (IT/R&amp;D)</option>
                                    <option value="analisis">4. Pipeline Pengumpulan &amp; Analisis Data (Bab III)</option>
                                </select>
                                <button type="button" class="btn btn-primary" id="btn-fc-ai" onclick="generateFlowchartWithAi()">Buat dengan AI</button>
                            </div>
                            <div id="proc-fc" class="proc-box">
                                <div class="proc-head">
                                    <div class="proc-head-left"><span class="proc-spinner"></span><span id="proc-fc-text">Menyusun diagram sesuai jenis pilihanmu...</span></div>
                                    <span id="proc-fc-pct">...</span>
                                </div>
                                <div class="proc-track"><div class="proc-fill" id="proc-fc-bar" style="width:65%;"></div></div>
                            </div>
                            <div id="status-fc" class="status-box"></div>
                        </div>

                        <!-- Sub-opsi Bentuk Khusus Kerangka Berpikir (hanya tampil saat kind=kerangka) -->
                        <div id="fc-kerangka-layout-bar" style="display:none; margin-bottom:10px; padding:10px 12px; border-radius:10px; background:#f8fafc; border:1px solid var(--border);">
                            <div style="font-size:12px; font-weight:700; color:var(--text-main); margin-bottom:6px;">Bentuk Kerangka Berpikir / Paradigma Penelitian (Bab II):</div>
                            <div class="preset-row" style="margin-bottom:0;">
                                <button type="button" class="preset-chip" id="kb-btn-regresi" onclick="setKerangkaLayout('regresi')">Paradigma Regresi (X₁, X₂ → Y)</button>
                                <button type="button" class="preset-chip" id="kb-btn-mediasi" onclick="setKerangkaLayout('mediasi')">Jalur Mediasi (X → Z → Y)</button>
                                <button type="button" class="preset-chip" id="kb-btn-moderasi" onclick="setKerangkaLayout('moderasi')">Variabel Moderasi (X → Y via M)</button>
                                <button type="button" class="preset-chip" id="kb-btn-bertingkat" onclick="setKerangkaLayout('bertingkat')">Alur Kerangka Berpikir (Semua Jurusan)</button>
                            </div>
                        </div>

                        <!-- Preset Cepat Khusus Flowchart Prosedur -->
                        <div id="fc-prosedur-preset-bar">
                            <div style="font-size:12px; font-weight:700; color:var(--text-secondary); margin-bottom:6px;">Template Cepat 1-Klik:</div>
                            <div class="preset-row">
                                <button type="button" class="preset-chip" onclick="loadFlowchartPreset('kuantitatif')">Kuantitatif (Survei)</button>
                                <button type="button" class="preset-chip" onclick="loadFlowchartPreset('kualitatif')">Kualitatif</button>
                                <button type="button" class="preset-chip" onclick="loadFlowchartPreset('rnd')">Teknik / Lab / R&amp;D</button>
                            </div>
                        </div>

                        <div style="display:flex; align-items:center; justify-content:space-between; margin:10px 0 8px; gap:8px;">
                            <span style="font-size:12.5px; font-weight:700; color:var(--text-main);" id="fc-editor-heading">Edit Langkah &amp; Simbol ISO</span>
                            <button type="button" class="mini-btn" id="btn-fc-add-item" style="color:var(--primary); border-color:var(--primary-border);" onclick="addDiagramItem()">+ Tambah Langkah</button>
                        </div>

                        <div id="fc-steps-editor" class="fc-steps-list"></div>
                    </div>

                    <!-- Kolom Kanan: Pratinjau SVG Bersih + Gaya & Ekspor -->
                    <div>
                        <div class="form-grid-2" style="margin-bottom:10px;">
                            <div>
                                <label class="form-label" for="fc-style-select">Tema Warna Diagram</label>
                                <select id="fc-style-select" class="form-select" onchange="renderFlowchartSvg()">
                                    <option value="bw" selected>Hitam-Putih Resmi (Siap Cetak)</option>
                                    <option value="blue">Biru Akademik Modern</option>
                                    <option value="navy">Navy Eksekutif</option>
                                    <option value="emerald">Hijau Emerald Ilmiah</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label" for="fc-bg-select">Latar Belakang Gambar</label>
                                <select id="fc-bg-select" class="form-select" onchange="renderFlowchartSvg()">
                                    <option value="white" selected>Putih Solid (Rekomendasi Word)</option>
                                    <option value="transparent">Transparan (PNG Tanpa Background)</option>
                                </select>
                            </div>
                        </div>

                        <div class="fc-preview-shell">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; gap:8px;">
                                <span style="font-size:11.5px; font-weight:700; color:var(--text-secondary);">Pratinjau Diagram HD (Siap Tempel Word)</span>
                                <button type="button" class="mini-btn" onclick="openFcZoomModal()" style="display:inline-flex; align-items:center; gap:5px; color:var(--primary); border-color:var(--primary-border);">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>
                                    <span>Perbesar Pratinjau</span>
                                </button>
                            </div>
                            <div class="fc-preview-paper" id="fc-svg-preview" style="cursor:zoom-in;" title="Klik untuk memperbesar diagram" onclick="openFcZoomModal()"></div>
                            <div class="fc-export-bar">
                                <button type="button" class="btn btn-primary fc-btn-main" onclick="downloadFlowchartPng()">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                    <span>Unduh PNG HD</span>
                                </button>
                                <button type="button" class="btn btn-soft" onclick="copyFlowchartPng()">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                    <span>Salin Gambar</span>
                                </button>
                                <button type="button" class="btn btn-outline" onclick="downloadFlowchartSvg()">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                    <span>Unduh SVG</span>
                                </button>
                            </div>
                            <div id="status-fc-export" class="status-box"></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ======================================================= -->
        <!-- 2. RAPIKAN SKRIPSI / PROPOSAL WORD (.DOCX) — TANPA UBAH ISI -->
        <!-- ======================================================= -->
        <section class="tool-panel" id="panel-tidy-docx">
            <div class="panel-card">
                <div class="panel-head">
                    <div>
                        <h2 class="panel-title">Rapikan Tata Letak Skripsi &amp; Proposal (.docx)</h2>
                        <p class="panel-desc">Unggah file Word yang halaman, margin, font, spasi, atau tabelnya masih berantakan. Sistem menata ulang formatnya secara otomatis <strong>tanpa mengubah 1 kata pun isi skripsimu</strong> (gambar, grafik, &amp; tabel tetap utuh).</p>
                    </div>
                    <span class="privacy-badge">✓ 100% Isi Asli Utuh · Tanpa Batas Ukuran</span>
                </div>

                <div class="dropzone" onclick="document.getElementById('input-tidy-docx').click()">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 14l2 2 4-4"/></svg>
                    <div class="dropzone-title" id="label-tidy-docx">Klik atau seret file Word skripsi / proposal (.docx) ke sini</div>
                    <div class="dropzone-sub">Diproses langsung di browser — aman untuk file besar (hingga 50 MB) yang berisi banyak lampiran &amp; gambar</div>
                    <input type="file" id="input-tidy-docx" accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document" style="display:none" onchange="onSelectTidyDocx(this)">
                </div>

                <div class="form-grid-3">
                    <div>
                        <label class="form-label" for="tidy-margin">Standar Margin A4</label>
                        <select id="tidy-margin" class="form-select">
                            <option value="4433" selected>Skripsi 4-4-3-3 cm (Atas 4, Kiri 4, Bawah 3, Kanan 3)</option>
                            <option value="3333">Simetris 3-3-3-3 cm</option>
                            <option value="normal">Normal 2,54 cm (Standar Jurnal)</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="tidy-font">Jenis &amp; Ukuran Font</label>
                        <select id="tidy-font" class="form-select">
                            <option value="Times New Roman|24" selected>Times New Roman 12pt (Standar Skripsi)</option>
                            <option value="Arial|22">Arial 11pt</option>
                            <option value="Calibri|22">Calibri 11pt</option>
                            <option value="Garamond|24">Garamond 12pt</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="tidy-spacing">Spasi Baris Paragraf</label>
                        <select id="tidy-spacing" class="form-select">
                            <option value="360" selected>1,5 Spasi (Standar Skripsi Indonesia)</option>
                            <option value="480">2,0 Spasi (Spasi Ganda)</option>
                            <option value="276">1,15 Spasi (Makalah / Jurnal)</option>
                        </select>
                    </div>
                </div>

                <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; margin-bottom:10px;">
                    <label class="form-label" style="margin:0;">Opsi Perapian <span style="color:#dc2626;">*</span> <span style="font-weight:500; color:var(--text-muted);">(Pilih sendiri minimal 1)</span></label>
                    <button type="button" id="btn-toggle-all-tidy" onclick="toggleAllTidyOpts(this)" style="font-size:12px; font-weight:600; color:var(--primary); background:#eff6ff; border:1px solid #bfdbfe; border-radius:7px; cursor:pointer; padding:4px 10px;">Centang Semua</button>
                </div>

                <div class="form-grid-2" id="tidy-options-grid" style="margin-bottom:8px;">
                    <label class="check-card">
                        <input type="checkbox" id="tidy-opt-pagebreak" onchange="syncTidyToggleBtn()">
                        <span><strong>Rapikan Pindah Halaman &amp; Hapus Halaman Kosong:</strong> Mulai halaman baru otomatis tiap <code>BAB I–V</code>, <code>ABSTRAK</code>, <code>DAFTAR PUSTAKA</code>, serta hapus enter &amp; page break kosong berlebih.</span>
                    </label>
                    <label class="check-card">
                        <input type="checkbox" id="tidy-opt-indent" onchange="syncTidyToggleBtn()">
                        <span><strong>Rata Kanan-Kiri, Indentasi &amp; Anti Yatim:</strong> Paragraf isi jadi Justify + masuk 1,25 cm, <code>widowControl</code> aktif, Sub-BAB terkunci (Keep Next), &amp; Daftar Pustaka otomatis Hanging Indent.</span>
                    </label>
                    <label class="check-card">
                        <input type="checkbox" id="tidy-opt-tables" onchange="syncTidyToggleBtn()">
                        <span><strong>Rapikan Tabel Data (Bebas Potong Margin):</strong> Skalakan lebar kolom tabel pas 100% margin, border tipis rapi, font tabel 10,5pt spasi 1,0, &amp; judul tabel berulang tiap halaman (tabel tanda tangan otomatis dilewati).</span>
                    </label>
                    <label class="check-card">
                        <input type="checkbox" id="tidy-opt-spaces" onchange="syncTidyToggleBtn()">
                        <span><strong>Bersihkan Spasi &amp; Tanda Baca Berantakan:</strong> Hapus spasi ganda/spasi sebelum titik/koma tanpa mengubah satu pun kata, kutipan, rumus, atau angka asli mahasiswa.</span>
                    </label>
                    <label class="check-card">
                        <input type="checkbox" id="tidy-opt-images" onchange="syncTidyToggleBtn()">
                        <span><strong>Rapikan Gambar &amp; Keterangan (Anti Terpotong):</strong> Perkecil proporsional gambar yang melebar keluar margin kertas, posisikan rata tengah, &amp; kunci judul Gambar/Tabel agar tidak terpisah halaman.</span>
                    </label>
                    <label class="check-card">
                        <input type="checkbox" id="tidy-opt-cleanstyle" onchange="syncTidyToggleBtn()">
                        <span><strong>Hapus Bekas Copy-Paste (Shading &amp; Warna Teks):</strong> Bersihkan latar belakang abu-abu/putih, stabilo highlight, &amp; seragamkan warna teks isi menjadi hitam pekat siap cetak.</span>
                    </label>
                    <label class="check-card">
                        <input type="checkbox" id="tidy-opt-cover" onchange="syncTidyToggleBtn()">
                        <span><strong>Lindungi Halaman Sampul &amp; Lembar Pengesahan:</strong> Pertahankan tata letak Cover, logo kampus, jarak tanda tangan, Daftar Isi otomatis (TOC), &amp; rumus matematika Word (Equation) agar tidak ikut berubah.</span>
                    </label>
                    <label class="check-card">
                        <input type="checkbox" id="tidy-opt-abstract" onchange="syncTidyToggleBtn()">
                        <span><strong>Format Khusus Abstrak &amp; Kutipan Blok:</strong> Otomatis atur bagian <code>ABSTRAK / ABSTRACT</code> menjadi spasi 1,0 (tunggal) sesuai pedoman kampus Indonesia, sementara isi Bab tetap mengikuti pilihan spasi di atas.</span>
                    </label>
                    <div class="check-card" style="grid-column: 1 / -1; flex-direction: column; gap: 10px; background: #f8faff; border-color: #dbeafe;" onclick="if(event.target.closest('select, label.sub-lbl')) return;">
                        <label style="display:flex; align-items:flex-start; gap:9px; width:100%; cursor:pointer;">
                            <input type="checkbox" id="tidy-opt-pagenum" onchange="onToggleTidyPageNum()">
                            <span><strong>Rapikan Penomoran Halaman Skripsi Otomatis:</strong> Pisahkan Section <code>Cover</code> (tanpa nomor), <code>Bagian Awal</code> (Romawi <code>i, ii, iii</code> tengah bawah), dan <code>BAB I–V</code> + <code>DAFTAR PUSTAKA</code> (mulai angka <code>1, 2, 3</code>) dengan Word native <em>Section Break</em> &amp; <em>Page Field</em> tanpa merusak tata letak atau bagian Landscape.</span>
                        </label>
                        <div id="tidy-pagenum-subopts" class="form-grid-2" style="display:none; width:100%; min-width:0; padding-left:0; margin-bottom:0; gap:10px;">
                            <div style="min-width:0;">
                                <label class="form-label" for="tidy-pagenum-layout" style="font-size:11.5px; margin-bottom:4px;">Letak Nomor Bagian Isi (BAB I–V &amp; Pustaka)</label>
                                <select id="tidy-pagenum-layout" class="form-select" style="font-size:12.5px; min-height:36px; width:100%;">
                                    <option value="bab_bottom_right" selected>Standar Skripsi (Awal BAB Tengah Bawah, Isi Kanan Atas)</option>
                                    <option value="all_bottom_center">Semua Tengah Bawah (Awal BAB &amp; Isi di Tengah Bawah)</option>
                                    <option value="all_top_right">Kanan Atas Semua (Seluruh Halaman BAB I–V di Kanan Atas)</option>
                                </select>
                            </div>
                            <div style="min-width:0;">
                                <label class="form-label" for="tidy-pagenum-roman-start" style="font-size:11.5px; margin-bottom:4px;">Nomor Awal Bagian Depan (Jika Ada Cover)</label>
                                <select id="tidy-pagenum-roman-start" class="form-select" style="font-size:12.5px; min-height:36px; width:100%;">
                                    <option value="2" selected>Mulai ii (Cover dihitung i tersembunyi — Umum di Kampus)</option>
                                    <option value="1">Mulai i (Halaman pertama setelah Cover bernomor i)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tool-action-bar">
                    <div class="tool-action-controls" style="font-size:12.5px; color:var(--text-muted); display:flex; align-items:center; gap:6px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>
                        <span>File asli tidak tertimpa — hasil diunduh sebagai file baru <code>_Rapi.docx</code></span>
                    </div>
                    <button type="button" class="btn btn-primary" id="btn-tidy-docx" onclick="runTidyDocx()">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 14l2 2 4-4"/></svg>
                        <span>Rapikan Skripsi Sekarang</span>
                    </button>
                </div>

                <div id="proc-tidy" class="proc-box">
                    <div class="proc-head">
                        <div class="proc-head-left"><span class="proc-spinner"></span><span id="proc-tidy-text">Membaca struktur dokumen...</span></div>
                        <span id="proc-tidy-pct">0%</span>
                    </div>
                    <div class="proc-track"><div class="proc-fill" id="proc-tidy-bar" style="width:10%;"></div></div>
                </div>

                <div id="result-tidy" class="result-card">
                    <div class="result-card-top">
                        <div class="result-info">
                            <span class="result-check">✓</span>
                            <div style="min-width:0;">
                                <div class="result-title" id="result-tidy-title">Skripsi berhasil dirapikan!</div>
                                <div class="result-meta" id="result-tidy-meta">100% teks, gambar, &amp; tabel asli dipertahankan</div>
                            </div>
                        </div>
                        <div class="result-actions single-action">
                            <button type="button" class="btn btn-success" id="btn-download-tidy">⬇ Unduh Word Rapi (.docx)</button>
                        </div>
                    </div>
                    <div class="audit-pills" id="result-tidy-pills"></div>
                </div>
                <div id="status-tidy" class="status-box"></div>
            </div>
        </section>

        <!-- ======================================================= -->
        <!-- 3. PDF <-> WORD CONVERTER -->
        <!-- ======================================================= -->
        <section class="tool-panel" id="panel-pdf-word">
            <div class="panel-card">
                <div class="panel-head">
                    <div>
                        <h2 class="panel-title">Konverter PDF ⇄ Word (.docx)</h2>
                        <p class="panel-desc">Ubah file PDF teks (skripsi, jurnal, makalah) menjadi Word siap edit dengan deteksi heading &amp; daftar otomatis, atau ubah file Word (.docx) menjadi PDF ukuran A4.</p>
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
                        <div class="dropzone-title" id="label-pdf2docx">Klik atau seret file PDF (.pdf) ke sini</div>
                        <div class="dropzone-sub">PDF teks diekstrak langsung di browser (aman hingga 25 MB) dengan urutan baris &amp; heading rapi</div>
                        <input type="file" id="input-pdf2docx" accept=".pdf,application/pdf" style="display:none" onchange="onSelectPdf2Docx(this)">
                    </div>

                    <div class="tool-action-bar">
                        <div class="tool-action-controls">
                            <label class="check-card" style="width:100%;">
                                <input type="checkbox" id="chk-pdf2docx-academic" checked>
                                <span>Rapikan ke format Skripsi A4 (Times New Roman 12pt, Margin 4-4-3-3 cm, Spasi 1,5, rata kanan-kiri)</span>
                            </label>
                        </div>
                        <button type="button" class="btn btn-primary" id="btn-pdf2docx" onclick="runPdfToDocx()">Konversi ke Word (.docx)</button>
                    </div>

                    <div id="proc-pdf2docx" class="proc-box">
                        <div class="proc-head">
                            <div class="proc-head-left"><span class="proc-spinner"></span><span id="proc-pdf2docx-text">Membaca halaman PDF...</span></div>
                            <span id="proc-pdf2docx-pct">0%</span>
                        </div>
                        <div class="proc-track"><div class="proc-fill" id="proc-pdf2docx-bar" style="width:15%;"></div></div>
                    </div>

                    <div id="result-pdf2docx" class="result-card">
                        <div class="result-card-top">
                            <div class="result-info">
                                <span class="result-check">✓</span>
                                <div style="min-width:0;">
                                    <div class="result-title" id="result-pdf2docx-title">Dokumen Word siap!</div>
                                    <div class="result-meta" id="result-pdf2docx-meta"></div>
                                </div>
                            </div>
                            <div class="result-actions single-action">
                                <button type="button" class="btn btn-success" id="btn-download-pdf2docx">⬇ Unduh Word (.docx)</button>
                            </div>
                        </div>
                    </div>
                    <div id="status-pdf2docx" class="status-box"></div>
                </div>

                <!-- Submode: DOCX -> PDF -->
                <div id="box-docx2pdf" style="display:none;">
                    <div class="dropzone" onclick="document.getElementById('input-docx2pdf').click()">
                        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="1.8"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        <div class="dropzone-title" id="label-docx2pdf">Klik atau seret file Word (.docx) ke sini</div>
                        <div class="dropzone-sub">Mendukung heading, paragraf, bold/italic, daftar, tabel (colspan), &amp; gambar tertanam (maks 4 MB)</div>
                        <input type="file" id="input-docx2pdf" accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document" style="display:none" onchange="onSelectDocx2Pdf(this)">
                    </div>

                    <div class="form-grid-2">
                        <div>
                            <label for="sel-docx2pdf-margin" class="form-label">Margin Kertas A4</label>
                            <select id="sel-docx2pdf-margin" class="form-select">
                                <option value="standard">Standar Normal (2,54 cm)</option>
                                <option value="skripsi">Standar Skripsi (4-4-3-3 cm)</option>
                            </select>
                        </div>
                        <div style="display:flex; align-items:flex-end;">
                            <label class="check-card" style="width:100%;">
                                <input type="checkbox" id="chk-docx2pdf-pagenum" checked>
                                <span>Tambahkan nomor halaman otomatis (tengah bawah)</span>
                            </label>
                        </div>
                    </div>

                    <div class="tool-action-bar">
                        <div class="tool-action-controls" style="font-size:12.5px; color:var(--text-muted);">
                            <span>Tips: File &gt; 4 MB bisa dikecilkan dulu lewat menu <strong>Kompres File → Word</strong>.</span>
                        </div>
                        <button type="button" class="btn btn-primary" id="btn-docx2pdf" onclick="runDocxToPdf()">Konversi ke PDF (.pdf)</button>
                    </div>

                    <div id="proc-docx2pdf" class="proc-box">
                        <div class="proc-head">
                            <div class="proc-head-left"><span class="proc-spinner"></span><span id="proc-docx2pdf-text">Menyusun PDF A4...</span></div>
                            <span id="proc-docx2pdf-pct">...</span>
                        </div>
                        <div class="proc-track"><div class="proc-fill" id="proc-docx2pdf-bar" style="width:65%;"></div></div>
                    </div>

                    <div id="result-docx2pdf" class="result-card">
                        <div class="result-card-top">
                            <div class="result-info">
                                <span class="result-check">✓</span>
                                <div style="min-width:0;">
                                    <div class="result-title" id="result-docx2pdf-title">Dokumen PDF siap!</div>
                                    <div class="result-meta" id="result-docx2pdf-meta"></div>
                                </div>
                            </div>
                            <div class="result-actions">
                                <button type="button" class="btn btn-outline" id="btn-preview-docx2pdf"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg> Lihat PDF</button>
                                <button type="button" class="btn btn-success" id="btn-download-docx2pdf">⬇ Unduh PDF</button>
                            </div>
                        </div>
                    </div>
                    <div id="status-docx2pdf" class="status-box"></div>
                </div>
            </div>
        </section>

        <!-- ======================================================= -->
        <!-- 4. GABUNG PDF (MERGE PDF) — PRATINJAU THUMBNAIL + PRESISI -->
        <!-- ======================================================= -->
        <section class="tool-panel" id="panel-merge-pdf">
            <div class="panel-card">
                <div class="panel-head">
                    <div>
                        <h2 class="panel-title">Gabung PDF (Merge PDF)</h2>
                        <p class="panel-desc">Satukan Cover, Lembar Pengesahan hasil scan, Bab I–V, dan Lampiran menjadi satu file PDF utuh — dilengkapi pratinjau halaman pertama tiap file &amp; tombol cek hasil PDF langsung.</p>
                    </div>
                    <span class="privacy-badge"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg> 100% di Browser · Tanpa Batas Ukuran</span>
                </div>

                <div class="dropzone" onclick="document.getElementById('input-merge-pdf').click()">
                    <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="1.8"><path d="M12 5v14M5 12h14"/></svg>
                    <div class="dropzone-title">Klik atau seret file PDF ke sini (bisa pilih banyak sekaligus)</div>
                    <div class="dropzone-sub">Urutkan file dengan ▲ / ▼ — PDF terproteksi kata sandi otomatis terdeteksi agar tidak jadi halaman kosong</div>
                    <input type="file" id="input-merge-pdf" accept=".pdf,application/pdf" multiple style="display:none" onchange="addMergePdfFiles(this)">
                </div>

                <div id="merge-pdf-summary" style="display:none; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px; margin-bottom:10px; font-size:12.5px; color:var(--text-secondary);">
                    <span id="merge-pdf-summary-text" style="font-weight:600;"></span>
                    <button type="button" class="mini-btn danger" onclick="clearMergePdf()">Hapus Semua</button>
                </div>

                <div id="merge-pdf-list" class="file-list"></div>

                <div class="form-grid-2">
                    <div>
                        <label class="form-label" for="merge-output-name">Nama File Hasil PDF</label>
                        <input type="text" id="merge-output-name" class="form-input" placeholder="cth: Skripsi_Lengkap" value="Gabungan_Skripsi">
                    </div>
                    <div style="display:flex; align-items:flex-end;">
                        <label class="check-card" style="width:100%;">
                            <input type="checkbox" id="merge-add-pagenum">
                            <span>Tambahkan nomor halaman (1, 2, 3...) di tengah bawah hasil gabungan</span>
                        </label>
                    </div>
                </div>

                <div class="tool-action-bar">
                    <div class="tool-action-controls" style="font-size:12.5px; color:var(--text-muted);">
                        <span>Urutan halaman mengikuti urutan daftar file dari atas ke bawah.</span>
                    </div>
                    <button type="button" class="btn btn-primary" id="btn-merge-pdf" onclick="runMergePdf()">Gabungkan PDF Sekarang</button>
                </div>

                <div id="proc-merge" class="proc-box">
                    <div class="proc-head">
                        <div class="proc-head-left"><span class="proc-spinner"></span><span id="proc-merge-text">Menggabungkan dokumen PDF...</span></div>
                        <span id="proc-merge-pct">0%</span>
                    </div>
                    <div class="proc-track"><div class="proc-fill" id="proc-merge-bar" style="width:15%;"></div></div>
                </div>

                <div id="result-merge" class="result-card">
                    <div class="result-card-top">
                        <div class="result-info">
                            <span class="result-check">✓</span>
                            <div style="min-width:0;">
                                <div class="result-title" id="result-merge-title">PDF berhasil digabungkan!</div>
                                <div class="result-meta" id="result-merge-meta"></div>
                            </div>
                        </div>
                        <div class="result-actions">
                            <button type="button" class="btn btn-outline" id="btn-preview-merge"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg> Lihat PDF</button>
                            <button type="button" class="btn btn-success" id="btn-download-merge">⬇ Unduh PDF</button>
                        </div>
                    </div>
                </div>
                <div id="status-merge-pdf" class="status-box"></div>
            </div>
        </section>

        <!-- ======================================================= -->
        <!-- 5. PISAH / HAPUS HALAMAN PDF (VISUAL THUMBNAIL PICKER) -->
        <!-- ======================================================= -->
        <section class="tool-panel" id="panel-split-pdf">
            <div class="panel-card">
                <div class="panel-head">
                    <div>
                        <h2 class="panel-title">Pisah / Hapus Halaman PDF</h2>
                        <p class="panel-desc">Klik langsung halaman PDF dari pratinjau visual atau ketik rentang halaman — bisa <strong>Ambil Halaman Terpilih</strong> maupun <strong>Hapus Halaman Terpilih</strong> (misalnya hapus halaman kosong).</p>
                    </div>
                    <span class="privacy-badge"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg> 100% di Browser · Pratinjau Visual</span>
                </div>

                <div class="seg-switch">
                    <button type="button" class="seg-btn active" id="seg-split-extract" onclick="setSplitMode('extract')">Ambil Halaman Terpilih</button>
                    <button type="button" class="seg-btn" id="seg-split-delete" onclick="setSplitMode('delete')">Hapus Halaman Terpilih</button>
                </div>

                <div class="dropzone" onclick="document.getElementById('input-split-pdf').click()">
                    <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="1.8"><circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><line x1="20" y1="4" x2="8.12" y2="15.88"/><line x1="14.47" y1="14.48" x2="20" y2="20"/></svg>
                    <div class="dropzone-title" id="label-split-pdf">Klik atau seret 1 file PDF ke sini</div>
                    <div class="dropzone-sub" id="sub-split-pdf">Pratinjau tiap halaman otomatis muncul agar pilihan halaman 100% akurat</div>
                    <input type="file" id="input-split-pdf" accept=".pdf,application/pdf" style="display:none" onchange="onSelectSplitPdf(this)">
                </div>

                <div id="split-controls-wrap" style="display:none;">
                    <div class="preset-row" id="split-presets">
                        <button type="button" class="preset-chip" onclick="selectSplitPreset('first')">Hal 1 saja</button>
                        <button type="button" class="preset-chip" onclick="selectSplitPreset('1-3')">Hal 1–3</button>
                        <button type="button" class="preset-chip" onclick="selectSplitPreset('odd')">Ganjil</button>
                        <button type="button" class="preset-chip" onclick="selectSplitPreset('even')">Genap</button>
                        <button type="button" class="preset-chip" onclick="selectSplitPreset('last')">Hal Terakhir</button>
                        <button type="button" class="preset-chip" onclick="selectSplitPreset('all')">Pilih Semua</button>
                        <button type="button" class="preset-chip" onclick="selectSplitPreset('none')">Reset</button>
                    </div>

                    <div id="split-visual-grid" class="split-thumb-grid"></div>
                    <div id="split-pill-grid" class="page-picker-grid" style="display:none;"></div>

                    <div class="form-grid-2">
                        <div>
                            <label class="form-label" for="split-range-input" id="split-range-label">Halaman yang Diambil (sinkron otomatis dengan klik di atas)</label>
                            <input type="text" id="split-range-input" class="form-input" placeholder="Contoh: 1-3, 5, 8-10" oninput="onSplitRangeInputTyped()">
                        </div>
                        <div>
                            <label class="form-label" for="split-output-name">Nama File Hasil PDF</label>
                            <input type="text" id="split-output-name" class="form-input" placeholder="cth: Bab_1_Skripsi">
                        </div>
                    </div>

                    <div class="tool-action-bar">
                        <div class="tool-action-controls">
                            <span id="split-selection-summary" style="font-size:13px; font-weight:700; color:#1d4ed8;"></span>
                        </div>
                        <button type="button" class="btn btn-primary" id="btn-split-pdf" onclick="runSplitPdf()">Ekstrak Halaman PDF</button>
                    </div>
                </div>

                <div id="proc-split" class="proc-box">
                    <div class="proc-head">
                        <div class="proc-head-left"><span class="proc-spinner"></span><span id="proc-split-text">Memproses halaman PDF...</span></div>
                        <span id="proc-split-pct">0%</span>
                    </div>
                    <div class="proc-track"><div class="proc-fill" id="proc-split-bar" style="width:20%;"></div></div>
                </div>

                <div id="result-split" class="result-card">
                    <div class="result-card-top">
                        <div class="result-info">
                            <span class="result-check">✓</span>
                            <div style="min-width:0;">
                                <div class="result-title" id="result-split-title">PDF berhasil dipisahkan!</div>
                                <div class="result-meta" id="result-split-meta"></div>
                            </div>
                        </div>
                        <div class="result-actions">
                            <button type="button" class="btn btn-outline" id="btn-preview-split"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg> Lihat PDF</button>
                            <button type="button" class="btn btn-success" id="btn-download-split">⬇ Unduh PDF</button>
                        </div>
                    </div>
                </div>
                <div id="status-split-pdf" class="status-box"></div>
            </div>
        </section>

        <!-- ======================================================= -->
        <!-- 6. KOMPRES FILE 3-IN-1 (PDF / WORD .DOCX / GAMBAR) -->
        <!-- ======================================================= -->
        <section class="tool-panel" id="panel-compress">
            <div class="panel-card">
                <div class="panel-head">
                    <div>
                        <h2 class="panel-title">Kompres File (PDF, Word .docx &amp; Gambar)</h2>
                        <p class="panel-desc">Kecilkan ukuran file skripsi PDF, dokumen Word yang berat karena banyak foto, maupun gambar/scan agar lolos batas upload SIAKAD &amp; repository kampus.</p>
                    </div>
                    <span class="privacy-badge"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg> 100% di Browser · Tanpa Batas 4 MB</span>
                </div>

                <div class="seg-switch">
                    <button type="button" class="seg-btn active" id="seg-comp-pdf" onclick="setCompressMode('pdf')">Kompres PDF</button>
                    <button type="button" class="seg-btn" id="seg-comp-docx" onclick="setCompressMode('docx')">Kompres Word (.docx)</button>
                    <button type="button" class="seg-btn" id="seg-comp-img" onclick="setCompressMode('img')">Kompres Gambar</button>
                </div>

                <!-- 6A. KOMPRES PDF -->
                <div id="box-comp-pdf">
                    <div class="dropzone" onclick="document.getElementById('input-comp-pdf').click()">
                        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="1.8"><polyline points="4 14 10 14 10 20"/><polyline points="20 10 14 10 14 4"/><line x1="14" y1="10" x2="21" y2="3"/><line x1="3" y1="21" x2="10" y2="14"/></svg>
                        <div class="dropzone-title" id="label-comp-pdf">Klik atau seret file PDF yang ingin dikompres</div>
                        <div class="dropzone-sub">Cocok untuk memperkecil PDF skripsi, hasil scan lembar pengesahan, atau berkas pendaftaran SIAKAD</div>
                        <input type="file" id="input-comp-pdf" accept=".pdf,application/pdf" style="display:none" onchange="onSelectCompPdf(this)">
                    </div>

                    <div class="form-grid-2">
                        <div>
                            <label class="form-label" for="comp-pdf-level">Tingkat Kompresi PDF</label>
                            <select id="comp-pdf-level" class="form-select">
                                <option value="low">Sangat Ringan / SIAKAD (Ukuran paling kecil)</option>
                                <option value="medium" selected>Seimbang (Rekomendasi — tajam &amp; hemat 50–80%)</option>
                                <option value="high">Tajam / Siap Cetak (Kualitas tinggi)</option>
                            </select>
                        </div>
                        <div style="display:flex; align-items:flex-end;">
                            <div style="font-size:12px; color:var(--text-muted); padding:8px 10px; background:#f8fafc; border:1px solid var(--border); border-radius:9px; width:100%;">
                                Setiap halaman dioptimasi ukurannya dengan rasio halaman asli tetap terjaga (Portrait/Landscape).
                            </div>
                        </div>
                    </div>

                    <div class="tool-action-bar">
                        <div class="tool-action-controls" id="comp-pdf-info" style="font-size:12.5px; color:var(--text-muted);">Pilih file PDF lalu klik Kompres.</div>
                        <button type="button" class="btn btn-primary" id="btn-comp-pdf" onclick="runCompressPdf()">Kompres PDF Sekarang</button>
                    </div>

                    <div id="proc-comp-pdf" class="proc-box">
                        <div class="proc-head">
                            <div class="proc-head-left"><span class="proc-spinner"></span><span id="proc-comp-pdf-text">Mengompres PDF...</span></div>
                            <span id="proc-comp-pdf-pct">0%</span>
                        </div>
                        <div class="proc-track"><div class="proc-fill" id="proc-comp-pdf-bar" style="width:10%;"></div></div>
                    </div>

                    <div id="result-comp-pdf" class="result-card">
                        <div class="result-card-top">
                            <div class="result-info">
                                <span class="result-check">✓</span>
                                <div style="min-width:0;">
                                    <div class="result-title" id="result-comp-pdf-title">Kompresi PDF selesai!</div>
                                    <div class="result-meta" id="result-comp-pdf-meta"></div>
                                </div>
                            </div>
                            <div class="result-actions">
                                <button type="button" class="btn btn-outline" id="btn-preview-comp-pdf"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg> Lihat PDF</button>
                                <button type="button" class="btn btn-success" id="btn-download-comp-pdf">⬇ Unduh PDF</button>
                            </div>
                        </div>
                    </div>
                    <div id="status-comp-pdf" class="status-box"></div>
                </div>

                <!-- 6B. KOMPRES WORD (.DOCX) -->
                <div id="box-comp-docx" style="display:none;">
                    <div class="dropzone" onclick="document.getElementById('input-comp-docx').click()">
                        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 15h6"/></svg>
                        <div class="dropzone-title" id="label-comp-docx">Klik atau seret file Word (.docx) yang ukurannya besar</div>
                        <div class="dropzone-sub">Mengompres semua foto/screenshot di dalam dokumen Word (aman untuk file 5–40 MB) tanpa merusak teks &amp; format</div>
                        <input type="file" id="input-comp-docx" accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document" style="display:none" onchange="onSelectCompDocx(this)">
                    </div>

                    <div class="form-grid-2">
                        <div>
                            <label class="form-label" for="comp-docx-level">Tingkat Kompresi Gambar di Word</label>
                            <select id="comp-docx-level" class="form-select">
                                <option value="low">Sangat Ringan (Maks 1000px · Kualitas 60%)</option>
                                <option value="medium" selected>Seimbang — Rekomendasi (Maks 1400px · Kualitas 76%)</option>
                                <option value="high">Tajam (Maks 1800px · Kualitas 85%)</option>
                            </select>
                        </div>
                        <div style="display:flex; align-items:flex-end;">
                            <div style="font-size:12px; color:var(--text-muted); padding:8px 10px; background:#f8fafc; border:1px solid var(--border); border-radius:9px; width:100%;">
                                Mengoptimasi media internal <code>word/media/*</code> + kompresi ZIP level maksimal. Teks &amp; tabel 100% aman.
                            </div>
                        </div>
                    </div>

                    <div class="tool-action-bar">
                        <div class="tool-action-controls" id="comp-docx-info" style="font-size:12.5px; color:var(--text-muted);">Pilih file .docx lalu klik Kompres Word.</div>
                        <button type="button" class="btn btn-primary" id="btn-comp-docx" onclick="runCompressDocx()">Kompres Word (.docx)</button>
                    </div>

                    <div id="proc-comp-docx" class="proc-box">
                        <div class="proc-head">
                            <div class="proc-head-left"><span class="proc-spinner"></span><span id="proc-comp-docx-text">Membuka arsip Word...</span></div>
                            <span id="proc-comp-docx-pct">0%</span>
                        </div>
                        <div class="proc-track"><div class="proc-fill" id="proc-comp-docx-bar" style="width:12%;"></div></div>
                    </div>

                    <div id="result-comp-docx" class="result-card">
                        <div class="result-card-top">
                            <div class="result-info">
                                <span class="result-check">✓</span>
                                <div style="min-width:0;">
                                    <div class="result-title" id="result-comp-docx-title">Kompresi Word selesai!</div>
                                    <div class="result-meta" id="result-comp-docx-meta"></div>
                                </div>
                            </div>
                            <div class="result-actions single-action">
                                <button type="button" class="btn btn-success" id="btn-download-comp-docx">⬇ Unduh Word (.docx)</button>
                            </div>
                        </div>
                    </div>
                    <div id="status-comp-docx" class="status-box"></div>
                </div>

                <!-- 6C. KOMPRES GAMBAR -->
                <div id="box-comp-img" style="display:none;">
                    <div class="dropzone" onclick="document.getElementById('input-compress-img').click()">
                        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        <div class="dropzone-title">Klik atau seret 1 atau banyak gambar (JPG, PNG, WEBP)</div>
                        <div class="dropzone-sub">Bisa langsung pilih target batas ukuran SIAKAD (&lt; 200 KB, &lt; 500 KB, &lt; 1 MB) atau atur manual</div>
                        <input type="file" id="input-compress-img" accept="image/jpeg,image/png,image/webp" multiple style="display:none" onchange="onSelectCompressImages(this)">
                    </div>

                    <div style="font-size:12px; font-weight:700; color:var(--text-secondary); margin-bottom:6px;">Target Ukuran Otomatis SIAKAD:</div>
                    <div class="preset-row">
                        <button type="button" class="preset-chip active" data-imgtarget="manual" onclick="setImgTargetPreset('manual', this)">Manual Slider</button>
                        <button type="button" class="preset-chip" data-imgtarget="200" onclick="setImgTargetPreset('200', this)">Target &lt; 200 KB</button>
                        <button type="button" class="preset-chip" data-imgtarget="500" onclick="setImgTargetPreset('500', this)">Target &lt; 500 KB</button>
                        <button type="button" class="preset-chip" data-imgtarget="1024" onclick="setImgTargetPreset('1024', this)">Target &lt; 1 MB</button>
                    </div>

                    <div class="form-grid-2" id="comp-img-manual-controls">
                        <div>
                            <label class="form-label" for="compress-quality">Kualitas Kompresi: <strong id="compress-quality-val">75%</strong></label>
                            <input type="range" id="compress-quality" min="25" max="92" value="75" style="width:100%; accent-color:#2563eb;" oninput="document.getElementById('compress-quality-val').textContent = this.value + '%'; runCompressImages();">
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
                    <div id="comp-img-bulk-bar" class="tool-action-bar" style="display:none;">
                        <span id="comp-img-bulk-meta" style="font-size:12.5px; font-weight:600; color:#047857;"></span>
                        <button type="button" class="btn btn-success" onclick="downloadAllCompressedImages()">⬇ Unduh Semua Gambar</button>
                    </div>
                    <div id="status-compress-img" class="status-box"></div>
                </div>
            </div>
        </section>

        <!-- ======================================================= -->
        <!-- 7. HAPUS BACKGROUND GAMBAR (TANDA TANGAN, LOGO & PAS FOTO) -->
        <!-- ======================================================= -->
        <section class="tool-panel" id="panel-remove-bg">
            <div class="panel-card">
                <div class="panel-head">
                    <div>
                        <h2 class="panel-title">Hapus Background Gambar (TTD, Logo &amp; Pas Foto)</h2>
                        <p class="panel-desc">Buat tanda tangan transparan untuk Lembar Pengesahan Word, bersihkan background logo kampus, atau ganti latar pas foto mahasiswa ke Transparan / Merah / Biru secara otomatis.</p>
                    </div>
                    <span class="privacy-badge"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg> 100% di Browser · Privat</span>
                </div>

                <div class="seg-switch">
                    <button type="button" class="seg-btn active" id="seg-rmbg-ai" onclick="setRemoveBgMode('ai')"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:4px;"><path d="M12 3l1.9 5.8L20 11l-6.1 2.2L12 19l-1.9-5.8L4 11l6.1-2.2z"/></svg>Hapus Background AI (Otomatis · Semua Gambar)</button>
                    <button type="button" class="seg-btn" id="seg-rmbg-smart" onclick="setRemoveBgMode('smart')">Tanda Tangan &amp; Logo (Latar Polos)</button>
                </div>

                <div class="dropzone" onclick="document.getElementById('input-rmbg').click()">
                    <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 15l5-5 4 4 3-3 6 6"/><circle cx="9" cy="8.5" r="1.5"/></svg>
                    <div class="dropzone-title" id="label-rmbg">Klik atau seret gambar (Foto, Pas Foto, Produk, Logo, atau Tanda Tangan)</div>
                    <div class="dropzone-sub">Mendukung JPG, PNG, WEBP — langsung dihapus background-nya otomatis oleh AI di browser</div>
                    <input type="file" id="input-rmbg" accept="image/jpeg,image/png,image/webp" style="display:none" onchange="onSelectRemoveBgFile(this)">
                </div>

                <!-- Kontrol Mode AI (Default — Semua Foto, Pas Foto, Produk & Objek Rumit) -->
                <div id="rmbg-ai-controls" style="margin-bottom:12px;">
                    <div style="padding:11px 14px; border-radius:10px; background:#eff6ff; border:1px solid #bfdbfe; font-size:12.5px; color:#1e40af; display:flex; align-items:flex-start; gap:8px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0; margin-top:2px;"><path d="M12 3l1.9 5.8L20 11l-6.1 2.2L12 19l-1.9-5.8L4 11l6.1-2.2z"/></svg>
                        <span><strong>Mode AI Otomatis (ISNet / RMBG):</strong> Memisahkan subjek, orang, pas foto, maupun objek rumit dari latar belakang langsung di browser. Pemakaian pertama mengunduh model AI sekali saja (tersimpan otomatis di cache browser).</span>
                    </div>
                </div>

                <!-- Kontrol Mode Latar Polos (Khusus Tanda Tangan / Logo Latar Putih) -->
                <div id="rmbg-smart-controls" style="display:none;">
                    <div class="form-grid-3">
                        <div>
                            <label class="form-label" for="rmbg-tolerance">Toleransi Warna Latar: <strong id="rmbg-tol-val">38</strong></label>
                            <input type="range" id="rmbg-tolerance" min="8" max="110" value="38" style="width:100%; accent-color:#2563eb;" oninput="document.getElementById('rmbg-tol-val').textContent=this.value; if(rmbgSourceImg) processRemoveBgSmart();">
                        </div>
                        <div>
                            <label class="form-label" for="rmbg-softness">Haluskan Tepi (Feather): <strong id="rmbg-soft-val">18</strong></label>
                            <input type="range" id="rmbg-softness" min="0" max="50" value="18" style="width:100%; accent-color:#2563eb;" oninput="document.getElementById('rmbg-soft-val').textContent=this.value; if(rmbgSourceImg) processRemoveBgSmart();">
                        </div>
                        <div style="display:flex; align-items:flex-end;">
                            <label class="check-card" style="width:100%;">
                                <input type="checkbox" id="rmbg-ink-enhance" onchange="if(rmbgSourceImg) processRemoveBgSmart();">
                                <span><strong>Mode Tanda Tangan:</strong> Pertajam &amp; hitamkan tinta pulpen agar bersih di Word</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div style="margin-bottom:12px;">
                    <label class="form-label">Ganti Warna Latar Hasil:</label>
                    <div class="swatch-row">
                        <button type="button" class="swatch-btn active" data-bg="transparent" onclick="setRmbgOutputColor('transparent', this)">
                            <span class="swatch-dot" style="background:repeating-conic-gradient(#cbd5e1 0% 25%, #fff 0% 50%) 50%/8px 8px;"></span> Transparan (PNG)
                        </button>
                        <button type="button" class="swatch-btn" data-bg="#db1514" onclick="setRmbgOutputColor('#db1514', this)">
                            <span class="swatch-dot" style="background:#db1514;"></span> Merah Pas Foto
                        </button>
                        <button type="button" class="swatch-btn" data-bg="#0057b8" onclick="setRmbgOutputColor('#0057b8', this)">
                            <span class="swatch-dot" style="background:#0057b8;"></span> Biru Pas Foto
                        </button>
                        <button type="button" class="swatch-btn" data-bg="#ffffff" onclick="setRmbgOutputColor('#ffffff', this)">
                            <span class="swatch-dot" style="background:#ffffff;"></span> Putih
                        </button>
                        <label class="swatch-btn" style="padding:4px 10px;">
                            <input type="color" id="rmbg-custom-color" value="#1e3a8a" style="width:18px; height:18px; border:none; background:none; cursor:pointer;" onchange="setRmbgOutputColor(this.value, this.parentElement)">
                            <span>Warna Lain</span>
                        </label>
                    </div>
                </div>

                <div id="proc-rmbg" class="proc-box">
                    <div class="proc-head">
                        <div class="proc-head-left"><span class="proc-spinner"></span><span id="proc-rmbg-text">Menghapus background...</span></div>
                        <span id="proc-rmbg-pct">...</span>
                    </div>
                    <div class="proc-track"><div class="proc-fill" id="proc-rmbg-bar" style="width:35%;"></div></div>
                </div>

                <div id="rmbg-preview-wrap" style="display:none;">
                    <div class="bg-preview-grid">
                        <div class="bg-stage">
                            <div class="bg-stage-head"><span>Gambar Asli</span><span id="rmbg-orig-dim"></span></div>
                            <div class="bg-checker" style="background:#f8fafc;">
                                <img id="rmbg-orig-img" alt="Asli">
                            </div>
                        </div>
                        <div class="bg-stage">
                            <div class="bg-stage-head"><span>Hasil Tanpa Background</span><span style="color:#059669;">Siap Unduh</span></div>
                            <div class="bg-checker" id="rmbg-out-stage">
                                <canvas id="rmbg-out-canvas"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tool-action-bar">
                    <div class="tool-action-controls">
                        <button type="button" class="btn btn-soft" id="btn-rmbg-run" onclick="runRemoveBgCurrentMode()"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg> Proses Ulang dengan AI</button>
                    </div>
                    <div class="btn-row-responsive two-col-mobile">
                        <button type="button" class="btn btn-primary" id="btn-rmbg-dl-png" onclick="downloadRemoveBg('png')" disabled>⬇ Unduh PNG</button>
                        <button type="button" class="btn btn-outline" id="btn-rmbg-dl-jpg" onclick="downloadRemoveBg('jpg')" disabled>⬇ Unduh JPG</button>
                    </div>
                </div>
                <div id="status-rmbg" class="status-box"></div>
            </div>
        </section>

        <!-- ======================================================= -->
        <!-- 8. JPG / PNG -> PDF A4 (DENGAN PUTAR FOTO 90° + PRATINJAU) -->
        <!-- ======================================================= -->
        <section class="tool-panel" id="panel-img-pdf">
            <div class="panel-card">
                <div class="panel-head">
                    <div>
                        <h2 class="panel-title">JPG / PNG → PDF A4</h2>
                        <p class="panel-desc">Ubah foto Lembar Pengesahan bertanda tangan, Surat Izin Penelitian, KRS, atau Kuesioner menjadi dokumen PDF A4 rapi — bisa putar foto 90° &amp; atur urutan halaman.</p>
                    </div>
                    <span class="privacy-badge"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg> 100% di Browser</span>
                </div>

                <div class="dropzone" onclick="document.getElementById('input-img-pdf').click()">
                    <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    <div class="dropzone-title">Klik atau seret foto JPG, PNG, atau WEBP (bisa banyak)</div>
                    <div class="dropzone-sub">Setiap foto otomatis diposisikan proporsional di 1 halaman kertas A4</div>
                    <input type="file" id="input-img-pdf" accept="image/jpeg,image/png,image/webp" multiple style="display:none" onchange="addImgPdfFiles(this)">
                </div>

                <div id="img-pdf-list" class="file-list"></div>

                <div class="form-grid-3">
                    <div>
                        <label class="form-label" for="img-pdf-orient">Orientasi Kertas A4</label>
                        <select id="img-pdf-orient" class="form-select">
                            <option value="portrait">A4 Tegak (Portrait)</option>
                            <option value="landscape">A4 Mendatar (Landscape)</option>
                            <option value="auto">Otomatis Sesuai Foto</option>
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
                    <div>
                        <label class="form-label" for="img-pdf-name">Nama File Hasil PDF</label>
                        <input type="text" id="img-pdf-name" class="form-input" value="Dokumen_Gambar_A4" placeholder="cth: Lembar_Pengesahan">
                    </div>
                </div>

                <div class="tool-action-bar">
                    <div class="tool-action-controls" style="font-size:12.5px; color:var(--text-muted);">
                        <span>Klik tombol <strong>↻ Putar</strong> di daftar foto jika hasil jepretan HP miring.</span>
                    </div>
                    <button type="button" class="btn btn-primary" id="btn-img-pdf" onclick="runImgToPdf()">Buat PDF A4 Sekarang</button>
                </div>

                <div id="proc-img-pdf" class="proc-box">
                    <div class="proc-head">
                        <div class="proc-head-left"><span class="proc-spinner"></span><span id="proc-img-pdf-text">Menyusun foto ke PDF A4...</span></div>
                        <span id="proc-img-pdf-pct">0%</span>
                    </div>
                    <div class="proc-track"><div class="proc-fill" id="proc-img-pdf-bar" style="width:15%;"></div></div>
                </div>

                <div id="result-img-pdf" class="result-card">
                    <div class="result-card-top">
                        <div class="result-info">
                            <span class="result-check">✓</span>
                            <div style="min-width:0;">
                                <div class="result-title" id="result-img-pdf-title">PDF A4 berhasil dibuat!</div>
                                <div class="result-meta" id="result-img-pdf-meta"></div>
                            </div>
                        </div>
                        <div class="result-actions">
                            <button type="button" class="btn btn-outline" id="btn-preview-img-pdf"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg> Lihat PDF</button>
                            <button type="button" class="btn btn-success" id="btn-download-img-pdf">⬇ Unduh PDF</button>
                        </div>
                    </div>
                </div>
                <div id="status-img-pdf" class="status-box"></div>
            </div>
        </section>

        <!-- ======================================================= -->
        <!-- 9. KALKULATOR SAMPEL (SLOVIN & LEMESHOW BAB III) -->
        <!-- ======================================================= -->
        <section class="tool-panel" id="panel-slovin">
            <div class="panel-card">
                <div class="panel-head">
                    <div>
                        <h2 class="panel-title">Kalkulator Sampel Penelitian (Slovin &amp; Lemeshow)</h2>
                        <p class="panel-desc">Hitung jumlah sampel minimal penelitian kuantitatif (populasi diketahui pakai <strong>Slovin</strong>, atau populasi tidak diketahui pakai <strong>Lemeshow</strong>) lengkap dengan paragraf siap salin ke Bab III.</p>
                    </div>
                    <span class="privacy-badge">✓ Siap Salin ke Bab III</span>
                </div>

                <div class="seg-switch">
                    <button type="button" class="seg-btn active" id="seg-sample-slovin" onclick="setSampleMode('slovin')">Rumus Slovin (Populasi Diketahui)</button>
                    <button type="button" class="seg-btn" id="seg-sample-lemeshow" onclick="setSampleMode('lemeshow')">Rumus Lemeshow (Populasi Tidak Diketahui)</button>
                </div>

                <div id="box-sample-slovin">
                    <div class="form-grid-2">
                        <div>
                            <label class="form-label" for="slovin-n">Jumlah Populasi (N)</label>
                            <input type="number" id="slovin-n" class="form-input" min="2" value="350" oninput="calcSample()">
                        </div>
                        <div>
                            <label class="form-label" for="slovin-e">Batas Kesalahan / Margin of Error (e)</label>
                            <select id="slovin-e" class="form-select" onchange="onSlovinErrorSelect()">
                                <option value="0.05" selected>5% (0,05) — Standar Skripsi Umum</option>
                                <option value="0.1">10% (0,10) — Populasi Kecil / Terbatas</option>
                                <option value="0.01">1% (0,01) — Ketelitian Sangat Tinggi</option>
                                <option value="custom">Kustom (Tulis Sendiri %)</option>
                            </select>
                            <input type="number" id="slovin-e-custom" class="form-input" style="display:none; margin-top:8px;" min="0.5" max="25" step="0.5" value="7" placeholder="Masukkan % (misal: 7)" oninput="calcSample()">
                        </div>
                    </div>
                </div>

                <div id="box-sample-lemeshow" style="display:none;">
                    <div class="form-grid-3">
                        <div>
                            <label class="form-label" for="lem-z">Tingkat Kepercayaan (Z)</label>
                            <select id="lem-z" class="form-select" onchange="calcSample()">
                                <option value="1.96" selected>95% (Z = 1,96 — Standar Skripsi)</option>
                                <option value="1.645">90% (Z = 1,645)</option>
                                <option value="2.576">99% (Z = 2,576)</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label" for="lem-p">Estimasi Maksimal Proporsi (P)</label>
                            <input type="number" id="lem-p" class="form-input" min="0.05" max="0.95" step="0.05" value="0.5" oninput="calcSample()">
                        </div>
                        <div>
                            <label class="form-label" for="lem-d">Sampling Error / Alpha (d)</label>
                            <select id="lem-d" class="form-select" onchange="calcSample()">
                                <option value="0.1" selected>10% (0,10 — Paling Umum, n ≈ 96/100)</option>
                                <option value="0.05">5% (0,05 — Presisi Tinggi, n ≈ 385)</option>
                                <option value="0.07">7% (0,07 — Menengah, n ≈ 196)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="slovin-result" id="slovin-output">
                    <div class="slovin-head-row" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
                        <div>
                            <div style="font-size:12px; font-weight:700; color:#1d4ed8; text-transform:uppercase;" id="sample-result-label">Jumlah Sampel Minimal (n)</div>
                            <div class="slovin-num" id="slovin-final-n">187 Responden</div>
                        </div>
                        <button type="button" class="btn btn-primary" onclick="copySlovinParagraph()"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg> Salin Paragraf Bab III</button>
                    </div>
                    <div class="slovin-steps" id="slovin-steps-box"></div>
                    <div class="slovin-para" id="slovin-para-box"></div>
                    <div id="slovin-compare-wrap"></div>
                </div>
                <div id="status-slovin" class="status-box"></div>
            </div>
        </section>

        <!-- ======================================================= -->
        <!-- 10. HITUNG KATA, CEK ABSTRAK & ESTIMASI HALAMAN A4 -->
        <!-- ======================================================= -->
        <section class="tool-panel" id="panel-word-count">
            <div class="panel-card">
                <div class="panel-head">
                    <div>
                        <h2 class="panel-title">Hitung Kata, Cek Abstrak &amp; Estimasi Halaman A4</h2>
                        <p class="panel-desc">Tempel teks bab / abstrak skripsimu atau unggah file <code>.txt</code>, <code>.docx</code>, atau <code>.pdf</code> untuk melihat jumlah kata, standar panjang abstrak, kata tersering, dan perkiraan halaman A4.</p>
                    </div>
                    <div class="wc-head-actions" style="display:flex; gap:7px; flex-wrap:wrap;">
                        <button type="button" class="btn btn-soft" onclick="document.getElementById('input-wc-file').click()">Unggah .docx / .pdf / .txt</button>
                        <button type="button" class="btn btn-outline" onclick="clearWordCount()">Bersihkan</button>
                        <input type="file" id="input-wc-file" accept=".txt,.docx,.pdf" style="display:none" onchange="loadWordCountFile(this)">
                    </div>
                </div>

                <textarea id="wc-textarea" class="form-textarea" placeholder="Tempel teks Bab I–V atau abstrak skripsimu di sini..." oninput="updateWordCount()"></textarea>
                <div id="status-wc" class="status-box"></div>

                <div class="stats-grid">
                    <div class="stat-box">
                        <div class="stat-label">Total Kata</div>
                        <div class="stat-val" id="wc-words">0</div>
                        <div class="stat-sub" id="wc-read-time">0 menit presentasi</div>
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

                <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
                    <div id="wc-abstract-badge" class="abstract-badge"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg><span>Standar abstrak skripsi Indonesia umumnya 150–250 kata.</span></div>
                    <button type="button" class="btn btn-outline" style="margin-top:12px;" onclick="copyWordCountSummary()"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg> Salin Ringkasan</button>
                </div>
                <div id="wc-top-words-wrap" style="display:none; margin-top:12px;">
                    <div style="font-size:12px; font-weight:700; color:var(--text-secondary);">Kata Kunci Paling Sering Muncul:</div>
                    <div id="wc-top-words" class="top-words-wrap"></div>
                </div>
            </div>
        </section>

    </main>

    @auth
        </div>
    @else
        <footer class="footer">
            <div class="container">
                &copy; {{ date('Y') }} <strong>SkripsiLab AI</strong> &bull; Tools Mahasiswa Gratis &bull;
                <a href="/" style="color:var(--primary); font-weight:600;">Beranda</a> &bull;
                <a href="{{ route('register') }}" style="color:var(--primary); font-weight:600;">Workspace Proposal &amp; Skripsi</a>
            </div>
        </footer>
    @endauth

    <script>
        function toggleWsSidebar() {
            if (window.innerWidth <= 920) {
                const isOpen = document.body.classList.toggle('ws-mobile-open');
                const bd = document.getElementById('ws-backdrop');
                if (bd) bd.classList.toggle('open', isOpen);
            } else {
                document.body.classList.toggle('ws-sidebar-collapsed');
            }
        }
        function closeWsSidebarMobile() {
            document.body.classList.remove('ws-mobile-open');
            const bd = document.getElementById('ws-backdrop');
            if (bd) bd.classList.remove('open');
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function setStatus(id, type, msg) {
            const el = document.getElementById(id);
            if (!el) return;
            if (!type || !msg) {
                el.className = 'status-box';
                el.textContent = '';
                return;
            }
            el.className = 'status-box ' + type;
            el.textContent = msg;
        }

        function setProc(prefix, show, text, pct) {
            const box = document.getElementById('proc-' + prefix);
            if (!box) return;
            box.classList.toggle('active', !!show);
            if (!show) return;
            const txtEl = document.getElementById('proc-' + prefix + '-text');
            const pctEl = document.getElementById('proc-' + prefix + '-pct');
            const barEl = document.getElementById('proc-' + prefix + '-bar');
            if (txtEl && text !== undefined) txtEl.textContent = text;
            if (pct !== undefined && pct !== null) {
                const clamped = Math.max(5, Math.min(100, Math.round(pct)));
                if (pctEl) pctEl.textContent = clamped + '%';
                if (barEl) barEl.style.width = clamped + '%';
            }
        }

        function hideResult(prefix) {
            const el = document.getElementById('result-' + prefix);
            if (el) el.classList.remove('active');
        }

        function showResultCard(prefix, title, meta) {
            const el = document.getElementById('result-' + prefix);
            if (!el) return;
            const t = document.getElementById('result-' + prefix + '-title');
            const m = document.getElementById('result-' + prefix + '-meta');
            if (t) t.textContent = title;
            if (m) m.textContent = meta;
            el.classList.add('active');
        }

        function formatBytes(bytes) {
            if (!bytes && bytes !== 0) return '0 B';
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
            setTimeout(() => URL.revokeObjectURL(url), 6000);
        }

        function openBlobPreview(blob) {
            if (!blob) return;
            const url = URL.createObjectURL(blob);
            window.open(url, '_blank', 'noopener');
            setTimeout(() => URL.revokeObjectURL(url), 90000);
        }

        function ensurePdfJsWorker() {
            if (window.pdfjsLib && !window.pdfjsLib.GlobalWorkerOptions.workerSrc) {
                window.pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.worker.min.js';
            }
        }

        async function waitForLib(globalKey, label) {
            for (let i = 0; i < 35; i++) {
                if (window[globalKey]) return window[globalKey];
                await new Promise(r => setTimeout(r, 150));
            }
            throw new Error(`Mesin ${label} gagal dimuat. Periksa koneksi internet lalu muat ulang halaman.`);
        }

        function initDropzones() {
            document.querySelectorAll('.dropzone').forEach(dz => {
                const input = dz.querySelector('input[type="file"]');
                if (!input) return;
                ['dragenter', 'dragover'].forEach(ev => dz.addEventListener(ev, e => {
                    e.preventDefault();
                    dz.classList.add('dragover');
                }));
                ['dragleave', 'drop'].forEach(ev => dz.addEventListener(ev, e => {
                    e.preventDefault();
                    dz.classList.remove('dragover');
                }));
                dz.addEventListener('drop', e => {
                    const files = e.dataTransfer && e.dataTransfer.files;
                    if (!files || !files.length) return;
                    const dt = new DataTransfer();
                    Array.from(files).forEach(f => dt.items.add(f));
                    input.files = dt.files;
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                });
            });
        }

        // ============================================================
        // TAB SWITCHING (+ URL HASH + SMOOTH MOBILE SCROLL)
        // ============================================================
        function switchTool(toolId, userClicked = false) {
            if (toolId === 'compress-img') {
                toolId = 'compress';
                setCompressMode('img');
            }
            document.querySelectorAll('.tool-tab-card').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.tool === toolId);
            });
            document.querySelectorAll('.tool-panel').forEach(p => {
                p.classList.toggle('active', p.id === 'panel-' + toolId);
            });
            if (history.replaceState) {
                history.replaceState(null, '', '#' + toolId);
            }
            if (userClicked && window.innerWidth <= 860) {
                const anchor = document.getElementById('tools-active-anchor');
                if (anchor) anchor.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        // ============================================================
        // 1. GENERATOR DIAGRAM & FLOWCHART SKRIPSI (4 BENTUK BAKU + 4 TEMA)
        // ============================================================
        const FLOWCHART_PRESETS = {
            kuantitatif: {
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
                steps: [
                    { type: 'start', label: 'Mulai' },
                    { type: 'io', label: 'Persiapan Material / Dataset & Analisis Kebutuhan' },
                    { type: 'process', label: 'Perancangan Model / Mix Design / Arsitektur' },
                    { type: 'process', label: 'Pengujian Laboratorium / Implementasi Sistem' },
                    { type: 'decision', label: 'Hasil Uji Memenuhi Standar / Akurasi?', no_label: 'Evaluasi & Perbaikan' },
                    { type: 'io', label: 'Analisis Hasil & Dokumentasi Laporan' },
                    { type: 'end', label: 'Selesai' }
                ]
            }
        };

        let fcCurrentKind = 'prosedur';
        let fcSteps = JSON.parse(JSON.stringify(FLOWCHART_PRESETS.kuantitatif.steps));
        let fcKerangka = {
            layout: 'regresi',
            x_vars: [
                { code: 'X1', name: 'Kualitas Layanan', indicators: 'Keandalan, Daya Tanggap, Empati' },
                { code: 'X2', name: 'Persepsi Harga', indicators: 'Keterjangkauan, Kesesuaian Manfaat' },
                { code: 'X3', name: 'Citra Merek', indicators: 'Kepercayaan, Reputasi' }
            ],
            mediator: { code: 'Z', name: 'Kepuasan Pelanggan', indicators: 'Harapan vs Kinerja' },
            moderator: { code: 'M', name: 'Komitmen Organisasi', indicators: 'Loyalitas, Keterikatan' },
            y_var: { code: 'Y', name: 'Loyalitas Pelanggan', indicators: 'Pembelian Ulang, Rekomendasi' },
            hypotheses: ['H1', 'H2', 'H3'],
            simultaneous: true,
            stages: [
                'Fenomena & Masalah: Kesenjangan kinerja atau permasalahan riil di objek penelitian',
                'Landasan Teori: Grand theory, konsep utama & sintesis penelitian terdahulu',
                'Pendekatan & Metode: Penentuan variabel, instrumen/uji lab & teknik analisis data',
                'Hasil yang Diharapkan: Temuan empiris, jawaban rumusan masalah & rekomendasi'
            ]
        };
        let fcSistem = {
            actor: 'Pengguna / Peneliti',
            frontend: 'Aplikasi Web / Mobile Client',
            frontend_sub: 'Form Input, Dashboard & Laporan',
            backend: 'Server Aplikasi & Mesin AI',
            backend_sub: 'REST API, Validasi & Pemodelan',
            database: 'Basis Data (MySQL)',
            database_sub: 'Tabel Data, Dataset & Riwayat',
            req_label: 'Input / Aksi',
            api_label: 'HTTP Request / JSON',
            db_label: 'Query SQL / CRUD',
            output: 'Hasil Analisis & Laporan PDF/Word'
        };
        let fcAnalisis = [
            { title: '1. Sumber Data', details: ['Data Primer (Survei / Lab)', 'Data Sekunder (Literatur)', 'Penentuan Populasi & Sampel'] },
            { title: '2. Pengumpulan Data', details: ['Kuesioner / Uji Benda Uji', 'Observasi & Dokumentasi', 'Tabulasi & Coding Data'] },
            { title: '3. Uji Kelayakan Data', details: ['Uji Validitas & Reliabilitas', 'Uji Normalitas & Asumsi Klasik', 'Pembersihan Outlier'] },
            { title: '4. Analisis & Simpulan', details: ['Regresi / ANOVA / Uji Beda', 'Pengujian Hipotesis (Sig < 0,05)', 'Interpretasi Bab IV & Saran'] }
        ];

        let lastFlowchartSvgString = '';
        let lastFlowchartDims = { w: 620, h: 640 };

        function escapeSvgXml(s) {
            return String(s || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function wrapFlowchartLines(text, maxChars, maxLines = 3) {
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
            return lines.slice(0, maxLines);
        }

        function onChangeFlowchartKind(kind) {
            fcCurrentKind = kind || 'prosedur';
            const kbBar = document.getElementById('fc-kerangka-layout-bar');
            const prBar = document.getElementById('fc-prosedur-preset-bar');
            const heading = document.getElementById('fc-editor-heading');
            const addBtn = document.getElementById('btn-fc-add-item');

            if (kbBar) kbBar.style.display = fcCurrentKind === 'kerangka' ? 'block' : 'none';
            if (prBar) prBar.style.display = fcCurrentKind === 'prosedur' ? 'block' : 'none';

            if (fcCurrentKind === 'kerangka') {
                if (heading) heading.textContent = 'Edit Variabel & Hubungan Kerangka Berpikir';
                if (addBtn) {
                    addBtn.style.display = 'inline-flex';
                    addBtn.textContent = fcKerangka.layout === 'bertingkat' ? '+ Tambah Tahap' : '+ Tambah Variabel X';
                }
                highlightKerangkaLayoutBtn();
            } else if (fcCurrentKind === 'sistem') {
                if (heading) heading.textContent = 'Edit Komponen Arsitektur Sistem & Database';
                if (addBtn) addBtn.style.display = 'none';
            } else if (fcCurrentKind === 'analisis') {
                if (heading) heading.textContent = 'Edit Tahapan Pipeline Analisis Data';
                if (addBtn) { addBtn.style.display = 'inline-flex'; addBtn.textContent = '+ Tambah Tahap'; }
            } else {
                if (heading) heading.textContent = 'Edit Langkah & Simbol ISO Flowchart';
                if (addBtn) { addBtn.style.display = 'inline-flex'; addBtn.textContent = '+ Tambah Langkah'; }
            }

            renderFlowchartEditor();
            renderFlowchartSvg();
        }

        function highlightKerangkaLayoutBtn() {
            ['regresi', 'mediasi', 'moderasi', 'bertingkat'].forEach(k => {
                const b = document.getElementById('kb-btn-' + k);
                if (!b) return;
                const active = fcKerangka.layout === k;
                b.style.background = active ? '#2563eb' : '';
                b.style.color = active ? '#ffffff' : '';
                b.style.borderColor = active ? '#2563eb' : '';
            });
        }

        function setKerangkaLayout(layout) {
            fcKerangka.layout = layout;
            const addBtn = document.getElementById('btn-fc-add-item');
            if (addBtn) addBtn.textContent = layout === 'bertingkat' ? '+ Tambah Tahap' : '+ Tambah Variabel X';
            highlightKerangkaLayoutBtn();
            renderFlowchartEditor();
            renderFlowchartSvg();
        }

        function loadFlowchartPreset(key) {
            const p = FLOWCHART_PRESETS[key];
            if (!p) return;
            fcCurrentKind = 'prosedur';
            const kindEl = document.getElementById('fc-ai-kind');
            if (kindEl) kindEl.value = 'prosedur';
            fcSteps = JSON.parse(JSON.stringify(p.steps));
            onChangeFlowchartKind('prosedur');
        }

        function addDiagramItem() {
            if (fcCurrentKind === 'prosedur') {
                const insertAt = Math.max(0, fcSteps.length - 1);
                fcSteps.splice(insertAt, 0, { type: 'process', label: 'Tahap Penelitian Baru' });
            } else if (fcCurrentKind === 'kerangka') {
                if (fcKerangka.layout === 'bertingkat') {
                    if (fcKerangka.stages.length < 6) fcKerangka.stages.push('Tahap Konseptual Baru');
                } else {
                    if (fcKerangka.x_vars.length < 5) {
                        const n = fcKerangka.x_vars.length + 1;
                        fcKerangka.x_vars.push({ code: 'X' + n, name: 'Variabel Independen ' + n, indicators: '' });
                        fcKerangka.hypotheses.push('H' + n);
                    }
                }
            } else if (fcCurrentKind === 'analisis') {
                if (fcAnalisis.length < 5) {
                    const n = fcAnalisis.length + 1;
                    fcAnalisis.push({ title: `${n}. Tahap Analisis ${n}`, details: ['Sub-kegiatan 1', 'Sub-kegiatan 2'] });
                }
            }
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

        function updateKerangkaX(idx, field, val) {
            if (!fcKerangka.x_vars[idx]) return;
            fcKerangka.x_vars[idx][field] = val;
            renderFlowchartSvg();
        }

        function deleteKerangkaX(idx) {
            if (fcKerangka.x_vars.length <= 1) return;
            fcKerangka.x_vars.splice(idx, 1);
            fcKerangka.hypotheses.splice(idx, 1);
            renderFlowchartEditor();
            renderFlowchartSvg();
        }

        function updateAnalisisStage(idx, field, val) {
            if (!fcAnalisis[idx]) return;
            if (field === 'details') {
                fcAnalisis[idx].details = val.split('\n').map(s => s.trim()).filter(Boolean).slice(0, 4);
            } else {
                fcAnalisis[idx][field] = val;
            }
            renderFlowchartSvg();
        }

        function deleteAnalisisStage(idx) {
            if (fcAnalisis.length <= 2) return;
            fcAnalisis.splice(idx, 1);
            renderFlowchartEditor();
            renderFlowchartSvg();
        }

        function renderFlowchartEditor() {
            const wrap = document.getElementById('fc-steps-editor');
            if (!wrap) return;

            if (fcCurrentKind === 'kerangka') {
                if (fcKerangka.layout === 'bertingkat') {
                    wrap.innerHTML = fcKerangka.stages.map((st, i) => `
                        <div class="fc-step-row">
                            <div class="fc-step-top">
                                <span class="fc-step-num">${i + 1}</span>
                                <span style="font-size:12px; font-weight:700; color:var(--text-secondary);">Blok Konseptual ${i + 1}</span>
                                ${fcKerangka.stages.length > 2 ? `<button type="button" class="mini-btn danger" style="margin-left:auto;" onclick="fcKerangka.stages.splice(${i},1); renderFlowchartEditor(); renderFlowchartSvg();">✕</button>` : ''}
                            </div>
                            <input type="text" class="form-input" style="min-height:34px; padding:6px 10px; font-size:12.5px;" value="${escapeSvgXml(st)}" oninput="fcKerangka.stages[${i}]=this.value; renderFlowchartSvg();">
                        </div>
                    `).join('');
                    return;
                }

                const xHtml = fcKerangka.x_vars.map((xv, i) => `
                    <div class="fc-step-row">
                        <div class="fc-step-top">
                            <span class="fc-step-num">${escapeSvgXml(xv.code || ('X' + (i + 1)))}</span>
                            <input type="text" class="form-input" style="width:68px; min-height:30px; padding:4px 7px; font-size:12px; font-weight:700;" value="${escapeSvgXml(xv.code || ('X' + (i + 1)))}" placeholder="X1" oninput="updateKerangkaX(${i},'code',this.value)">
                            <input type="text" class="form-input" style="width:64px; min-height:30px; padding:4px 7px; font-size:12px;" value="${escapeSvgXml(fcKerangka.hypotheses[i] || ('H' + (i + 1)))}" placeholder="H1" oninput="fcKerangka.hypotheses[${i}]=this.value; renderFlowchartSvg();">
                            <span style="font-size:11px; color:var(--text-muted);">Variabel Bebas</span>
                            ${fcKerangka.x_vars.length > 1 ? `<button type="button" class="mini-btn danger" style="margin-left:auto;" onclick="deleteKerangkaX(${i})">✕</button>` : ''}
                        </div>
                        <input type="text" class="form-input" style="min-height:33px; padding:6px 9px; font-size:12.5px;" value="${escapeSvgXml(xv.name)}" placeholder="Nama variabel (cth: Kualitas Layanan)" oninput="updateKerangkaX(${i},'name',this.value)">
                        <input type="text" class="form-input" style="min-height:30px; padding:4px 9px; font-size:11.5px; color:#475569;" value="${escapeSvgXml(xv.indicators || '')}" placeholder="Indikator singkat (opsional, pisahkan koma)" oninput="updateKerangkaX(${i},'indicators',this.value)">
                    </div>
                `).join('');

                const zHtml = fcKerangka.layout === 'mediasi' ? `
                    <div class="fc-step-row" style="border-color:#c4b5fd; background:#faf5ff;">
                        <div class="fc-step-top">
                            <span class="fc-step-num" style="background:#7c3aed;">Z</span>
                            <span style="font-size:12px; font-weight:700; color:#6d28d9;">Variabel Mediasi / Intervening (Z)</span>
                        </div>
                        <input type="text" class="form-input" style="min-height:33px; padding:6px 9px; font-size:12.5px;" value="${escapeSvgXml((fcKerangka.mediator && fcKerangka.mediator.name) || 'Kepuasan Pelanggan')}" oninput="if(!fcKerangka.mediator) fcKerangka.mediator={code:'Z',name:'',indicators:''}; fcKerangka.mediator.name=this.value; renderFlowchartSvg();">
                        <input type="text" class="form-input" style="min-height:30px; padding:4px 9px; font-size:11.5px;" value="${escapeSvgXml((fcKerangka.mediator && fcKerangka.mediator.indicators) || '')}" placeholder="Indikator Z (opsional)" oninput="if(!fcKerangka.mediator) fcKerangka.mediator={code:'Z',name:'',indicators:''}; fcKerangka.mediator.indicators=this.value; renderFlowchartSvg();">
                    </div>
                ` : (fcKerangka.layout === 'moderasi' ? `
                    <div class="fc-step-row" style="border-color:#fcd34d; background:#fffbeb;">
                        <div class="fc-step-top">
                            <span class="fc-step-num" style="background:#d97706;">M</span>
                            <span style="font-size:12px; font-weight:700; color:#b45309;">Variabel Moderasi (M — Memperkuat/Memperlemah)</span>
                        </div>
                        <input type="text" class="form-input" style="min-height:33px; padding:6px 9px; font-size:12.5px;" value="${escapeSvgXml((fcKerangka.moderator && fcKerangka.moderator.name) || 'Komitmen Organisasi')}" oninput="if(!fcKerangka.moderator) fcKerangka.moderator={code:'M',name:'',indicators:''}; fcKerangka.moderator.name=this.value; renderFlowchartSvg();">
                        <input type="text" class="form-input" style="min-height:30px; padding:4px 9px; font-size:11.5px;" value="${escapeSvgXml((fcKerangka.moderator && fcKerangka.moderator.indicators) || '')}" placeholder="Indikator M (opsional)" oninput="if(!fcKerangka.moderator) fcKerangka.moderator={code:'M',name:'',indicators:''}; fcKerangka.moderator.indicators=this.value; renderFlowchartSvg();">
                    </div>
                ` : '');

                const yHtml = `
                    <div class="fc-step-row" style="border-color:#93c5fd; background:#eff6ff;">
                        <div class="fc-step-top">
                            <span class="fc-step-num" style="background:#1d4ed8;">Y</span>
                            <span style="font-size:12px; font-weight:700; color:#1e40af;">Variabel Dependen / Terikat (Y)</span>
                        </div>
                        <input type="text" class="form-input" style="min-height:33px; padding:6px 9px; font-size:12.5px;" value="${escapeSvgXml(fcKerangka.y_var.name)}" oninput="fcKerangka.y_var.name=this.value; renderFlowchartSvg();">
                        <input type="text" class="form-input" style="min-height:30px; padding:4px 9px; font-size:11.5px;" value="${escapeSvgXml(fcKerangka.y_var.indicators || '')}" placeholder="Indikator Y (opsional)" oninput="fcKerangka.y_var.indicators=this.value; renderFlowchartSvg();">
                        ${fcKerangka.layout === 'regresi' && fcKerangka.x_vars.length >= 2 ? `
                            <label style="display:flex; align-items:center; gap:7px; font-size:12px; color:#1e3a8a; margin-top:4px; cursor:pointer;">
                                <input type="checkbox" ${fcKerangka.simultaneous ? 'checked' : ''} style="accent-color:#2563eb;" onchange="fcKerangka.simultaneous=this.checked; renderFlowchartSvg();">
                                <span>Tampilkan garis panah pengaruh simultan putus-putus (Uji F)</span>
                            </label>
                        ` : ''}
                    </div>
                `;
                wrap.innerHTML = xHtml + zHtml + yHtml;
                return;
            }

            if (fcCurrentKind === 'sistem') {
                wrap.innerHTML = `
                    <div class="fc-step-row">
                        <div class="fc-step-top"><span class="fc-step-num">1</span><span style="font-size:12px; font-weight:700;">Aktor / Pengguna Utama</span></div>
                        <input type="text" class="form-input" style="min-height:33px; padding:6px 9px; font-size:12.5px;" value="${escapeSvgXml(fcSistem.actor)}" oninput="fcSistem.actor=this.value; renderFlowchartSvg();">
                    </div>
                    <div class="fc-step-row">
                        <div class="fc-step-top"><span class="fc-step-num">2</span><span style="font-size:12px; font-weight:700;">Antarmuka / Client App</span></div>
                        <input type="text" class="form-input" style="min-height:33px; padding:6px 9px; font-size:12.5px;" value="${escapeSvgXml(fcSistem.frontend)}" oninput="fcSistem.frontend=this.value; renderFlowchartSvg();">
                        <input type="text" class="form-input" style="min-height:30px; padding:4px 9px; font-size:11.5px;" value="${escapeSvgXml(fcSistem.frontend_sub)}" placeholder="Sub-label teknologi" oninput="fcSistem.frontend_sub=this.value; renderFlowchartSvg();">
                    </div>
                    <div class="fc-step-row">
                        <div class="fc-step-top"><span class="fc-step-num">3</span><span style="font-size:12px; font-weight:700;">Backend / Server / Mesin Proses</span></div>
                        <input type="text" class="form-input" style="min-height:33px; padding:6px 9px; font-size:12.5px;" value="${escapeSvgXml(fcSistem.backend)}" oninput="fcSistem.backend=this.value; renderFlowchartSvg();">
                        <input type="text" class="form-input" style="min-height:30px; padding:4px 9px; font-size:11.5px;" value="${escapeSvgXml(fcSistem.backend_sub)}" placeholder="Sub-label algoritma / API" oninput="fcSistem.backend_sub=this.value; renderFlowchartSvg();">
                    </div>
                    <div class="fc-step-row">
                        <div class="fc-step-top"><span class="fc-step-num">4</span><span style="font-size:12px; font-weight:700;">Basis Data (Silinder Database)</span></div>
                        <input type="text" class="form-input" style="min-height:33px; padding:6px 9px; font-size:12.5px;" value="${escapeSvgXml(fcSistem.database)}" oninput="fcSistem.database=this.value; renderFlowchartSvg();">
                        <input type="text" class="form-input" style="min-height:30px; padding:4px 9px; font-size:11.5px;" value="${escapeSvgXml(fcSistem.database_sub)}" placeholder="Sub-label tabel / storage" oninput="fcSistem.database_sub=this.value; renderFlowchartSvg();">
                    </div>
                    <div class="fc-step-row">
                        <div class="fc-step-top"><span class="fc-step-num">5</span><span style="font-size:12px; font-weight:700;">Luaran / Output Sistem</span></div>
                        <input type="text" class="form-input" style="min-height:33px; padding:6px 9px; font-size:12.5px;" value="${escapeSvgXml(fcSistem.output)}" oninput="fcSistem.output=this.value; renderFlowchartSvg();">
                    </div>
                `;
                return;
            }

            if (fcCurrentKind === 'analisis') {
                wrap.innerHTML = fcAnalisis.map((st, i) => `
                    <div class="fc-step-row">
                        <div class="fc-step-top">
                            <span class="fc-step-num">${i + 1}</span>
                            <input type="text" class="form-input" style="flex:1; min-height:32px; padding:4px 8px; font-size:12.5px; font-weight:600;" value="${escapeSvgXml(st.title)}" oninput="updateAnalisisStage(${i},'title',this.value)">
                            ${fcAnalisis.length > 2 ? `<button type="button" class="mini-btn danger" onclick="deleteAnalisisStage(${i})">✕</button>` : ''}
                        </div>
                        <textarea class="form-input" style="min-height:58px; padding:6px 9px; font-size:11.5px; line-height:1.45; resize:vertical;" placeholder="1 baris per poin metode..." oninput="updateAnalisisStage(${i},'details',this.value)">${escapeSvgXml((st.details || []).join('\n'))}</textarea>
                    </div>
                `).join('');
                return;
            }

            wrap.innerHTML = fcSteps.map((st, i) => `
                <div class="fc-step-row">
                    <div class="fc-step-top">
                        <span class="fc-step-num">${i + 1}</span>
                        <select class="form-select" style="flex:1; min-width:130px; min-height:32px; padding:4px 8px; font-size:12px;" onchange="updateFcStep(${i}, 'type', this.value)">
                            <option value="start" ${st.type === 'start' ? 'selected' : ''}>Mulai (Oval)</option>
                            <option value="process" ${st.type === 'process' ? 'selected' : ''}>Proses (Kotak)</option>
                            <option value="io" ${st.type === 'io' ? 'selected' : ''}>Input/Data (Jajar Genjang)</option>
                            <option value="decision" ${st.type === 'decision' ? 'selected' : ''}>Keputusan (Belah Ketupat)</option>
                            <option value="end" ${st.type === 'end' ? 'selected' : ''}>Selesai (Oval)</option>
                        </select>
                        <div style="display:flex; gap:4px; margin-left:auto;">
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

        function getDiagramThemePalette() {
            const styleKey = document.getElementById('fc-style-select')?.value || 'bw';
            const themes = {
                bw: {
                    bg: '#ffffff', stroke: '#0f172a', text: '#0f172a', subText: '#475569', arrow: '#0f172a',
                    termFill: '#f8fafc', procFill: '#ffffff', ioFill: '#ffffff', decFill: '#f8fafc',
                    accentFill: '#f1f5f9', accentStroke: '#0f172a', headerFill: '#0f172a', headerText: '#ffffff',
                    medFill: '#f8fafc', yFill: '#f1f5f9', branchText: '#334155'
                },
                blue: {
                    bg: '#ffffff', stroke: '#1d4ed8', text: '#0f172a', subText: '#475569', arrow: '#2563eb',
                    termFill: '#dbeafe', procFill: '#eff6ff', ioFill: '#f0f9ff', decFill: '#fef3c7',
                    accentFill: '#dbeafe', accentStroke: '#2563eb', headerFill: '#1d4ed8', headerText: '#ffffff',
                    medFill: '#f5f3ff', yFill: '#dbeafe', branchText: '#1d4ed8'
                },
                navy: {
                    bg: '#ffffff', stroke: '#1e293b', text: '#0f172a', subText: '#475569', arrow: '#1e3a8a',
                    termFill: '#1e3a8a', termText: '#ffffff', procFill: '#f8fafc', ioFill: '#eef2ff', decFill: '#e0e7ff',
                    accentFill: '#e0e7ff', accentStroke: '#1e3a8a', headerFill: '#0f172a', headerText: '#ffffff',
                    medFill: '#eef2ff', yFill: '#dbeafe', branchText: '#1e3a8a'
                },
                emerald: {
                    bg: '#ffffff', stroke: '#047857', text: '#0f172a', subText: '#475569', arrow: '#059669',
                    termFill: '#d1fae5', procFill: '#ecfdf5', ioFill: '#f0fdf4', decFill: '#fef3c7',
                    accentFill: '#d1fae5', accentStroke: '#059669', headerFill: '#047857', headerText: '#ffffff',
                    medFill: '#f0fdfa', yFill: '#d1fae5', branchText: '#047857'
                }
            };
            return themes[styleKey] || themes.bw;
        }

        function renderKerangkaSvg(pal) {
            // Helper kotak variabel standar skripsi Indonesia (bersih, besar, tanpa badge web kecil)
            const drawAcademicVarBox = (x, y, w, h, code, name, ind, fill) => {
                const titleStr = code ? `${name} (${code})` : name;
                const nLines = wrapFlowchartLines(titleStr, Math.max(18, Math.floor(w / 9.2)), 2);
                const indLines = ind ? wrapFlowchartLines(String(ind), Math.max(24, Math.floor(w / 7.2)), 2) : [];
                let s = `<rect x="${x}" y="${y}" width="${w}" height="${h}" rx="4" fill="${fill}" stroke="${pal.stroke}" stroke-width="1.8"/>`;
                if (indLines.length) {
                    const topAreaH = nLines.length === 1 ? 34 : 46;
                    const titleStartY = nLines.length === 1 ? (y + 23) : (y + 20);
                    nLines.forEach((ln, li) => {
                        s += `<text x="${x + w / 2}" y="${titleStartY + li * 16}" text-anchor="middle" font-family="'Times New Roman', Times, serif" font-size="14" font-weight="bold" fill="${pal.text}">${escapeSvgXml(ln)}</text>`;
                    });
                    s += `<line x1="${x + 12}" y1="${y + topAreaH}" x2="${x + w - 12}" y2="${y + topAreaH}" stroke="${pal.stroke}" stroke-opacity="0.35" stroke-width="1"/>`;
                    const indStartY = y + topAreaH + ((h - topAreaH) - (indLines.length - 1) * 14) / 2 + 4;
                    indLines.forEach((il, li) => {
                        s += `<text x="${x + w / 2}" y="${indStartY + li * 14}" text-anchor="middle" font-family="'Times New Roman', Times, serif" font-size="11.2" font-style="italic" fill="${pal.subText}">${escapeSvgXml(il)}</text>`;
                    });
                } else {
                    const startY = y + h / 2 - ((nLines.length - 1) * 17) / 2 + 5;
                    nLines.forEach((ln, li) => {
                        s += `<text x="${x + w / 2}" y="${startY + li * 17}" text-anchor="middle" font-family="'Times New Roman', Times, serif" font-size="14.5" font-weight="bold" fill="${pal.text}">${escapeSvgXml(ln)}</text>`;
                    });
                }
                return s;
            };

            // 1. ALUR KERANGKA BERPIKIR SKRIPSI (SEMUA JURUSAN: IT, TEKNIK SIPIL, PENDIDIKAN, KUALITATIF, MANAJEMEN)
            if (fcKerangka.layout === 'bertingkat') {
                const svgW = 640;
                const boxW = 540;
                const cx = svgW / 2;
                let y = 24;
                let shapes = '';
                const defaultPhaseTitles = ['FENOMENA & PERMASALAHAN', 'KAJIAN TEORI & PENDEKATAN', 'METODE & VARIABEL PENELITIAN', 'ANALISIS & PENGUJIAN', 'HASIL YANG DIHARAPKAN', 'KESIMPULAN & REKOMENDASI'];
                fcKerangka.stages.forEach((rawTxt, i) => {
                    const txt = String(rawTxt || '').trim();
                    const colonIdx = txt.indexOf(':');
                    let headTitle = defaultPhaseTitles[i] || (`TAHAP ${i + 1}`);
                    let bodyText = txt;
                    if (colonIdx > 2 && colonIdx < 38) {
                        headTitle = txt.slice(0, colonIdx).trim().toUpperCase();
                        bodyText = txt.slice(colonIdx + 1).trim();
                    }
                    const lines = wrapFlowchartLines(bodyText, 54, 3);
                    const headH = 26;
                    const bodyH = Math.max(40, 18 + lines.length * 18);
                    const h = headH + bodyH;
                    const fill = i === fcKerangka.stages.length - 1 ? pal.yFill : (i === 0 ? pal.accentFill : pal.procFill);
                    shapes += `<rect x="${cx - boxW / 2}" y="${y}" width="${boxW}" height="${h}" rx="5" fill="${fill}" stroke="${pal.stroke}" stroke-width="1.8"/>`;
                    shapes += `<rect x="${cx - boxW / 2}" y="${y}" width="${boxW}" height="${headH}" rx="4" fill="${pal.headerFill}"/>`;
                    shapes += `<rect x="${cx - boxW / 2}" y="${y + headH - 6}" width="${boxW}" height="6" fill="${pal.headerFill}"/>`;
                    shapes += `<text x="${cx}" y="${y + 17}" text-anchor="middle" font-family="'Times New Roman', Times, serif" font-size="11.5" font-weight="bold" letter-spacing="0.5" fill="${pal.headerText}">${escapeSvgXml(headTitle)}</text>`;
                    const startY = y + headH + bodyH / 2 - ((lines.length - 1) * 18) / 2 + 5;
                    lines.forEach((ln, li) => {
                        shapes += `<text x="${cx}" y="${startY + li * 18}" text-anchor="middle" font-family="'Times New Roman', Times, serif" font-size="13.5" fill="${pal.text}">${escapeSvgXml(ln)}</text>`;
                    });
                    if (i < fcKerangka.stages.length - 1) {
                        shapes += `<line x1="${cx}" y1="${y + h}" x2="${cx}" y2="${y + h + 26}" stroke="${pal.arrow}" stroke-width="1.9" marker-end="url(#fc-arrow)"/>`;
                    }
                    y += h + 30;
                });
                return { w: svgW, h: y - 4, shapes };
            }

            const xs = fcKerangka.x_vars;
            const isMed = fcKerangka.layout === 'mediasi';
            const isMod = fcKerangka.layout === 'moderasi';

            // 2. VARIABEL MODERASI (X -> Y DIMODERASI M) — STANDAR SUGIYONO / GHOZALI
            if (isMod) {
                const boxW = 240;
                const boxH = 82;
                const gapY = 24;
                const padTop = 32;
                const totalXH = xs.length * boxH + Math.max(0, xs.length - 1) * gapY;
                const svgW = 760;
                const xColX = 36;
                const yColX = svgW - boxW - 36;
                const midX = Math.round((xColX + boxW + yColX) / 2);
                const midY = Math.round(padTop + totalXH / 2);
                const mBoxY = padTop + totalXH + 42;
                const svgH = mBoxY + boxH + 42;
                const yBoxY = Math.round(midY - boxH / 2);

                let shapes = '';
                shapes += drawAcademicVarBox(yColX, yBoxY, boxW, boxH, fcKerangka.y_var.code || 'Y', fcKerangka.y_var.name, fcKerangka.y_var.indicators, pal.yFill);

                xs.forEach((xv, i) => {
                    const by = padTop + i * (boxH + gapY);
                    const cy = Math.round(by + boxH / 2);
                    const targetY = Math.round(yBoxY + ((i + 1) / (xs.length + 1)) * boxH);
                    shapes += drawAcademicVarBox(xColX, by, boxW, boxH, xv.code || ('X' + (i + 1)), xv.name, xv.indicators, pal.procFill);
                    shapes += `<line x1="${xColX + boxW}" y1="${cy}" x2="${yColX - 5}" y2="${targetY}" stroke="${pal.arrow}" stroke-width="1.8" marker-end="url(#fc-arrow)"/>`;
                    const lx = Math.round(xColX + boxW + (yColX - (xColX + boxW)) * 0.34);
                    const ly = Math.round(cy + (targetY - cy) * 0.34) - 8;
                    const hLbl = (fcKerangka.hypotheses[i] || ('H' + (i + 1))).trim();
                    shapes += `<rect x="${lx - 15}" y="${ly - 12}" width="30" height="18" rx="3" fill="#ffffff" stroke="${pal.stroke}" stroke-width="0.9"/>`;
                    shapes += `<text x="${lx}" y="${ly + 1}" text-anchor="middle" font-family="'Times New Roman', serif" font-size="11.5" font-weight="bold" fill="${pal.branchText}">${escapeSvgXml(hLbl)}</text>`;
                });

                // Kotak Moderator M di bawah tengah + panah vertikal memotong garis tengah X -> Y
                const mod = fcKerangka.moderator || { code: 'M', name: 'Variabel Moderasi', indicators: '' };
                const mBoxX = Math.round(midX - boxW / 2);
                shapes += drawAcademicVarBox(mBoxX, mBoxY, boxW, boxH, mod.code || 'M', mod.name, mod.indicators, pal.medFill);
                shapes += `<line x1="${midX}" y1="${mBoxY}" x2="${midX}" y2="${midY + 6}" stroke="${pal.arrow}" stroke-width="1.9" stroke-dasharray="6,4" marker-end="url(#fc-arrow)"/>`;
                const hMod = 'H' + (xs.length + 1) + ' (Moderasi)';
                shapes += `<rect x="${midX + 10}" y="${Math.round((mBoxY + midY) / 2) - 8}" width="88" height="20" rx="3" fill="#ffffff" stroke="${pal.stroke}" stroke-width="0.9"/>`;
                shapes += `<text x="${midX + 54}" y="${Math.round((mBoxY + midY) / 2) + 6}" text-anchor="middle" font-family="'Times New Roman', serif" font-size="11.5" font-weight="bold" fill="${pal.branchText}">${hMod}</text>`;

                const legY = svgH - 12;
                shapes += `<line x1="36" y1="${legY - 4}" x2="68" y2="${legY - 4}" stroke="${pal.arrow}" stroke-width="1.8" marker-end="url(#fc-arrow)"/>`;
                shapes += `<text x="76" y="${legY}" font-family="'Times New Roman', serif" font-size="11.5" fill="${pal.subText}">Pengaruh Langsung</text>`;
                shapes += `<line x1="196" y1="${legY - 4}" x2="228" y2="${legY - 4}" stroke="${pal.arrow}" stroke-width="1.8" stroke-dasharray="5,3" marker-end="url(#fc-arrow)"/>`;
                shapes += `<text x="236" y="${legY}" font-family="'Times New Roman', serif" font-size="11.5" fill="${pal.subText}">Efek Moderasi (Interaksi X × M)</text>`;
                return { w: svgW, h: svgH, shapes };
            }

            // 3. JALUR MEDIASI (X -> Z -> Y + JALUR LANGSUNG X -> Y)
            if (isMed) {
                const boxW = 212;
                const boxH = 80;
                const gapY = 24;
                const padTop = 58; // ruang untuk panah pengaruh langsung atas X1 -> Y
                const padBot = 62; // ruang untuk panah pengaruh langsung bawah + legenda
                const totalXH = xs.length * boxH + Math.max(0, xs.length - 1) * gapY;
                const svgW = 840;
                const svgH = Math.max(300, totalXH + padTop + padBot);
                const xColX = 30;
                const yColX = svgW - boxW - 30;
                const zColX = Math.round((xColX + boxW + yColX) / 2 - boxW / 2);
                const midY = Math.round(padTop + totalXH / 2);
                const yBoxY = Math.round(midY - boxH / 2);
                const zBoxY = Math.round(midY - boxH / 2);

                let shapes = '';
                shapes += drawAcademicVarBox(yColX, yBoxY, boxW, boxH, fcKerangka.y_var.code || 'Y', fcKerangka.y_var.name, fcKerangka.y_var.indicators, pal.yFill);
                const med = fcKerangka.mediator || { code: 'Z', name: 'Variabel Mediasi', indicators: '' };
                shapes += drawAcademicVarBox(zColX, zBoxY, boxW, boxH, med.code || 'Z', med.name, med.indicators, pal.medFill);

                // Panah Z -> Y
                shapes += `<line x1="${zColX + boxW}" y1="${midY}" x2="${yColX - 5}" y2="${midY}" stroke="${pal.arrow}" stroke-width="1.9" marker-end="url(#fc-arrow)"/>`;
                const hZY = 'H' + (xs.length + 1);
                shapes += `<rect x="${(zColX + boxW + yColX) / 2 - 16}" y="${midY - 24}" width="32" height="19" rx="3" fill="#ffffff" stroke="${pal.stroke}" stroke-width="0.9"/>`;
                shapes += `<text x="${(zColX + boxW + yColX) / 2}" y="${midY - 11}" text-anchor="middle" font-family="'Times New Roman', serif" font-size="11.5" font-weight="bold" fill="${pal.branchText}">${hZY}</text>`;

                // Panah X -> Z
                xs.forEach((xv, i) => {
                    const by = padTop + i * (boxH + gapY);
                    const cy = Math.round(by + boxH / 2);
                    const targetY = Math.round(zBoxY + ((i + 1) / (xs.length + 1)) * boxH);
                    shapes += drawAcademicVarBox(xColX, by, boxW, boxH, xv.code || ('X' + (i + 1)), xv.name, xv.indicators, pal.procFill);
                    shapes += `<line x1="${xColX + boxW}" y1="${cy}" x2="${zColX - 5}" y2="${targetY}" stroke="${pal.arrow}" stroke-width="1.8" marker-end="url(#fc-arrow)"/>`;
                    const lx = Math.round((xColX + boxW + zColX) / 2);
                    const ly = Math.round((cy + targetY) / 2) - 8;
                    const hLbl = (fcKerangka.hypotheses[i] || ('H' + (i + 1))).trim();
                    shapes += `<rect x="${lx - 15}" y="${ly - 12}" width="30" height="18" rx="3" fill="#ffffff" stroke="${pal.stroke}" stroke-width="0.9"/>`;
                    shapes += `<text x="${lx}" y="${ly + 1}" text-anchor="middle" font-family="'Times New Roman', serif" font-size="11.5" font-weight="bold" fill="${pal.branchText}">${escapeSvgXml(hLbl)}</text>`;
                });

                // Panah Pengaruh Langsung (Direct Effect) X1 -> Y lewat atas Z, dan X terakhir -> Y lewat bawah Z (jika >= 2 X)
                const topRouteY = 22;
                const xMidX = xColX + Math.round(boxW / 2);
                const yMidX = yColX + Math.round(boxW / 2);
                shapes += `<polyline points="${xMidX},${padTop} ${xMidX},${topRouteY} ${yMidX},${topRouteY} ${yMidX},${yBoxY - 5}" fill="none" stroke="${pal.arrow}" stroke-width="1.6" stroke-dasharray="6,4" marker-end="url(#fc-arrow)"/>`;
                shapes += `<rect x="${zColX + boxW / 2 - 58}" y="${topRouteY - 10}" width="116" height="20" rx="3" fill="#ffffff" stroke="${pal.stroke}" stroke-width="0.9"/>`;
                shapes += `<text x="${zColX + boxW / 2}" y="${topRouteY + 4}" text-anchor="middle" font-family="'Times New Roman', serif" font-size="11" font-weight="bold" fill="${pal.branchText}">Pengaruh Langsung (X₁→Y)</text>`;

                if (xs.length >= 2) {
                    const botRouteY = padTop + totalXH + 24;
                    shapes += `<polyline points="${xMidX},${padTop + totalXH} ${xMidX},${botRouteY} ${yMidX},${botRouteY} ${yMidX},${yBoxY + boxH + 5}" fill="none" stroke="${pal.arrow}" stroke-width="1.6" stroke-dasharray="6,4" marker-end="url(#fc-arrow)"/>`;
                    const lastCode = xs[xs.length - 1].code || ('X' + xs.length);
                    shapes += `<rect x="${zColX + boxW / 2 - 58}" y="${botRouteY - 10}" width="116" height="20" rx="3" fill="#ffffff" stroke="${pal.stroke}" stroke-width="0.9"/>`;
                    shapes += `<text x="${zColX + boxW / 2}" y="${botRouteY + 4}" text-anchor="middle" font-family="'Times New Roman', serif" font-size="11" font-weight="bold" fill="${pal.branchText}">Pengaruh Langsung (${escapeSvgXml(lastCode)}→Y)</text>`;
                }

                const legY = svgH - 12;
                shapes += `<line x1="30" y1="${legY - 4}" x2="62" y2="${legY - 4}" stroke="${pal.arrow}" stroke-width="1.8" marker-end="url(#fc-arrow)"/>`;
                shapes += `<text x="70" y="${legY}" font-family="'Times New Roman', serif" font-size="11.5" fill="${pal.subText}">Jalur Mediasi (X → Z → Y)</text>`;
                shapes += `<line x1="238" y1="${legY - 4}" x2="270" y2="${legY - 4}" stroke="${pal.arrow}" stroke-width="1.6" stroke-dasharray="5,3" marker-end="url(#fc-arrow)"/>`;
                shapes += `<text x="278" y="${legY}" font-family="'Times New Roman', serif" font-size="11.5" fill="${pal.subText}">Pengaruh Langsung (Direct Effect X → Y)</text>`;
                return { w: svgW, h: svgH, shapes };
            }

            // 4. PARADIGMA PENELITIAN KUANTITATIF / REGRESI (X1, X2, ... -> Y + SIMULTAN UJI F)
            const boxW = 248;
            const boxH = 82;
            const gapY = 24;
            const padTop = 34;
            const padBot = fcKerangka.simultaneous && xs.length >= 2 ? 78 : 42;
            const totalXH = xs.length * boxH + Math.max(0, xs.length - 1) * gapY;
            const svgW = 740;
            const svgH = Math.max(260, totalXH + padTop + padBot);

            const xColX = 40;
            const yColX = svgW - boxW - 34;
            const midY = Math.round(padTop + totalXH / 2);
            const yBoxY = Math.round(midY - boxH / 2);

            let shapes = '';
            // Kotak Variabel Y di kanan
            shapes += drawAcademicVarBox(yColX, yBoxY, boxW, boxH, fcKerangka.y_var.code || 'Y', fcKerangka.y_var.name, fcKerangka.y_var.indicators, pal.yFill);

            // Kotak-kotak Variabel X di kiri + panah parsial H1, H2, H3
            xs.forEach((xv, i) => {
                const by = padTop + i * (boxH + gapY);
                const cy = Math.round(by + boxH / 2);
                shapes += drawAcademicVarBox(xColX, by, boxW, boxH, xv.code || ('X' + (i + 1)), xv.name, xv.indicators, pal.procFill);

                const targetY = Math.round(yBoxY + ((i + 1) / (xs.length + 1)) * boxH);
                shapes += `<line x1="${xColX + boxW}" y1="${cy}" x2="${yColX - 5}" y2="${targetY}" stroke="${pal.arrow}" stroke-width="1.8" marker-end="url(#fc-arrow)"/>`;

                const lx = Math.round((xColX + boxW + yColX) / 2);
                const ly = Math.round((cy + targetY) / 2) - 8;
                const hLbl = (fcKerangka.hypotheses[i] || ('H' + (i + 1))).trim();
                shapes += `<rect x="${lx - 15}" y="${ly - 12}" width="30" height="18" rx="3" fill="#ffffff" stroke="${pal.stroke}" stroke-width="0.9"/>`;
                shapes += `<text x="${lx}" y="${ly + 1}" text-anchor="middle" font-family="'Times New Roman', serif" font-size="11.5" font-weight="bold" fill="${pal.branchText}">${escapeSvgXml(hLbl)}</text>`;
            });

            // Garis Simultan putus-putus (Uji F) — kotak rapi mengelilingi grup X, panah keluar dari bawah ke bawah Y
            if (fcKerangka.simultaneous && xs.length >= 2) {
                const grpX = xColX - 18;
                const grpY = padTop - 14;
                const grpW = boxW + 36;
                const grpH = totalXH + 28;
                const simBottomY = grpY + grpH + 26;
                const yMidX = yColX + Math.round(boxW / 2);
                const hSim = 'H' + (xs.length + 1) + ' (Simultan)';
                shapes += `<rect x="${grpX}" y="${grpY}" width="${grpW}" height="${grpH}" rx="8" fill="none" stroke="${pal.arrow}" stroke-width="1.4" stroke-dasharray="6,4"/>`;
                shapes += `<polyline points="${xColX + Math.round(boxW / 2)},${grpY + grpH} ${xColX + Math.round(boxW / 2)},${simBottomY} ${yMidX},${simBottomY} ${yMidX},${yBoxY + boxH + 5}" fill="none" stroke="${pal.arrow}" stroke-width="1.7" stroke-dasharray="6,4" marker-end="url(#fc-arrow)"/>`;
                const lblMidX = Math.round((xColX + boxW / 2 + yMidX) / 2);
                shapes += `<rect x="${lblMidX - 50}" y="${simBottomY - 11}" width="100" height="21" rx="3" fill="#ffffff" stroke="${pal.stroke}" stroke-width="0.9"/>`;
                shapes += `<text x="${lblMidX}" y="${simBottomY + 4}" text-anchor="middle" font-family="'Times New Roman', serif" font-size="11.5" font-weight="bold" fill="${pal.branchText}">${hSim}</text>`;
            }

            // Legenda standar skripsi
            const legY = svgH - 12;
            shapes += `<line x1="40" y1="${legY - 4}" x2="72" y2="${legY - 4}" stroke="${pal.arrow}" stroke-width="1.8" marker-end="url(#fc-arrow)"/>`;
            shapes += `<text x="80" y="${legY}" font-family="'Times New Roman', serif" font-size="11.5" fill="${pal.subText}">Pengaruh Parsial</text>`;
            if (fcKerangka.simultaneous && xs.length >= 2) {
                shapes += `<line x1="194" y1="${legY - 4}" x2="226" y2="${legY - 4}" stroke="${pal.arrow}" stroke-width="1.6" stroke-dasharray="5,3" marker-end="url(#fc-arrow)"/>`;
                shapes += `<text x="234" y="${legY}" font-family="'Times New Roman', serif" font-size="11.5" fill="${pal.subText}">Pengaruh Simultan (Uji F)</text>`;
            }

            return { w: svgW, h: svgH, shapes };
        }

        function openFcZoomModal() {
            let modal = document.getElementById('fc-zoom-modal');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'fc-zoom-modal';
                modal.style.cssText = 'position:fixed; inset:0; background:rgba(15,23,42,0.75); backdrop-filter:blur(4px); z-index:9999; display:flex; align-items:center; justify-content:center; padding:20px; box-sizing:border-box;';
                modal.onclick = (e) => { if (e.target === modal) modal.style.display = 'none'; };
                modal.innerHTML = `<div style="background:#ffffff; border-radius:16px; width:100%; max-width:980px; max-height:90vh; overflow:auto; padding:20px; box-shadow:0 24px 60px rgba(0,0,0,0.35);">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; padding-bottom:10px; border-bottom:1px solid #e2e8f0;">
                        <strong style="font-size:14.5px; color:#0f172a;">Pratinjau Penuh Diagram (Resolusi HD)</strong>
                        <button type="button" onclick="document.getElementById('fc-zoom-modal').style.display='none'" style="border:none; background:#f1f5f9; width:30px; height:30px; border-radius:50%; font-size:18px; color:#475569; cursor:pointer;">&times;</button>
                    </div>
                    <div id="fc-zoom-body" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:10px; padding:18px; text-align:center;"></div>
                </div>`;
                document.body.appendChild(modal);
            }
            const body = document.getElementById('fc-zoom-body');
            if (body) body.innerHTML = lastFlowchartSvgString || '';
            modal.style.display = 'flex';
        }

        function renderSistemSvg(pal) {
            const svgW = 820;
            const svgH = 385;
            let shapes = '';

            // 1. Aktor / User (kiri)
            const ux = 32, uy = 68, uw = 136, uh = 136;
            shapes += `<rect x="${ux}" y="${uy}" width="${uw}" height="${uh}" rx="10" fill="${pal.accentFill}" stroke="${pal.stroke}" stroke-width="1.8"/>`;
            // Stick-figure user icon
            const ucx = ux + uw / 2;
            shapes += `<circle cx="${ucx}" cy="${uy + 34}" r="13" fill="#ffffff" stroke="${pal.stroke}" stroke-width="1.8"/>`;
            shapes += `<path d="M ${ucx - 20} ${uy + 76} C ${ucx - 20} ${uy + 56}, ${ucx + 20} ${uy + 56}, ${ucx + 20} ${uy + 76}" fill="none" stroke="${pal.stroke}" stroke-width="1.8"/>`;
            const actLines = wrapFlowchartLines(fcSistem.actor, 16, 2);
            actLines.forEach((ln, i) => {
                shapes += `<text x="${ucx}" y="${uy + 100 + i * 16}" text-anchor="middle" font-family="'Times New Roman', serif" font-size="12.5" font-weight="bold" fill="${pal.text}">${escapeSvgXml(ln)}</text>`;
            });

            // 2. Frontend / Client App
            const fx = 232, fy = 58, fw = 215, fh = 156;
            shapes += `<rect x="${fx}" y="${fy}" width="${fw}" height="${fh}" rx="8" fill="${pal.procFill}" stroke="${pal.stroke}" stroke-width="1.8"/>`;
            shapes += `<rect x="${fx}" y="${fy}" width="${fw}" height="30" rx="8" fill="${pal.headerFill}"/>`;
            shapes += `<rect x="${fx}" y="${fy + 16}" width="${fw}" height="14" fill="${pal.headerFill}"/>`;
            shapes += `<text x="${fx + fw / 2}" y="${fy + 20}" text-anchor="middle" font-family="'Times New Roman', serif" font-size="11.5" font-weight="bold" fill="${pal.headerText}">CLIENT / ANTARMUKA</text>`;
            const fLines = wrapFlowchartLines(fcSistem.frontend, 24, 2);
            fLines.forEach((ln, i) => {
                shapes += `<text x="${fx + fw / 2}" y="${fy + 66 + i * 18}" text-anchor="middle" font-family="'Times New Roman', serif" font-size="13.5" font-weight="bold" fill="${pal.text}">${escapeSvgXml(ln)}</text>`;
            });
            const fsLines = wrapFlowchartLines(fcSistem.frontend_sub, 28, 2);
            fsLines.forEach((ln, i) => {
                shapes += `<text x="${fx + fw / 2}" y="${fy + 114 + i * 15}" text-anchor="middle" font-family="'Times New Roman', serif" font-size="11" fill="${pal.subText}">${escapeSvgXml(ln)}</text>`;
            });

            // 3. Backend / Server / Engine
            const bx = 548, by = 58, bw = 236, bh = 156;
            shapes += `<rect x="${bx}" y="${by}" width="${bw}" height="${bh}" rx="8" fill="${pal.procFill}" stroke="${pal.stroke}" stroke-width="1.8"/>`;
            shapes += `<rect x="${bx}" y="${by}" width="${bw}" height="30" rx="8" fill="${pal.headerFill}"/>`;
            shapes += `<rect x="${bx}" y="${by + 16}" width="${bw}" height="14" fill="${pal.headerFill}"/>`;
            shapes += `<text x="${bx + bw / 2}" y="${by + 20}" text-anchor="middle" font-family="'Times New Roman', serif" font-size="11.5" font-weight="bold" fill="${pal.headerText}">SERVER / PROSES INTI</text>`;
            const bLines = wrapFlowchartLines(fcSistem.backend, 25, 2);
            bLines.forEach((ln, i) => {
                shapes += `<text x="${bx + bw / 2}" y="${by + 66 + i * 18}" text-anchor="middle" font-family="'Times New Roman', serif" font-size="13.5" font-weight="bold" fill="${pal.text}">${escapeSvgXml(ln)}</text>`;
            });
            const bsLines = wrapFlowchartLines(fcSistem.backend_sub, 30, 2);
            bsLines.forEach((ln, i) => {
                shapes += `<text x="${bx + bw / 2}" y="${by + 114 + i * 15}" text-anchor="middle" font-family="'Times New Roman', serif" font-size="11" fill="${pal.subText}">${escapeSvgXml(ln)}</text>`;
            });

            // 4. Silinder 3D Database (bawah kanan)
            const dx = 576, dy = 264, dw = 180, dh = 92;
            shapes += `<path d="M ${dx} ${dy + 14} L ${dx} ${dy + dh - 14} A ${dw / 2} 14 0 0 0 ${dx + dw} ${dy + dh - 14} L ${dx + dw} ${dy + 14} Z" fill="${pal.yFill}" stroke="${pal.stroke}" stroke-width="1.8"/>`;
            shapes += `<ellipse cx="${dx + dw / 2}" cy="${dy + 14}" rx="${dw / 2}" ry="14" fill="${pal.accentFill}" stroke="${pal.stroke}" stroke-width="1.8"/>`;
            const dLines = wrapFlowchartLines(fcSistem.database, 22, 2);
            dLines.forEach((ln, i) => {
                shapes += `<text x="${dx + dw / 2}" y="${dy + 49 + i * 15}" text-anchor="middle" font-family="'Times New Roman', serif" font-size="12.5" font-weight="bold" fill="${pal.text}">${escapeSvgXml(ln)}</text>`;
            });
            shapes += `<text x="${dx + dw / 2}" y="${dy + 78}" text-anchor="middle" font-family="'Times New Roman', serif" font-size="10.2" fill="${pal.subText}">${escapeSvgXml(String(fcSistem.database_sub || '').slice(0, 28))}</text>`;

            // 5. Output / Laporan (bawah kiri-tengah)
            const ox = 152, oy = 276, ow = 295, oh = 74;
            shapes += `<rect x="${ox}" y="${oy}" width="${ow}" height="${oh}" rx="8" fill="${pal.yFill}" stroke="${pal.stroke}" stroke-width="1.8"/>`;
            shapes += `<text x="${ox + ow / 2}" y="${oy + 24}" text-anchor="middle" font-family="'Times New Roman', serif" font-size="10.5" font-weight="bold" fill="${pal.branchText}">OUTPUT / LUARAN SISTEM</text>`;
            const oLines = wrapFlowchartLines(fcSistem.output, 34, 2);
            oLines.forEach((ln, i) => {
                shapes += `<text x="${ox + ow / 2}" y="${oy + 45 + i * 16}" text-anchor="middle" font-family="'Times New Roman', serif" font-size="13" font-weight="bold" fill="${pal.text}">${escapeSvgXml(ln)}</text>`;
            });

            // Panah-panah arus data berlabel
            // User <-> Frontend
            shapes += `<line x1="${ux + uw}" y1="122" x2="${fx - 4}" y2="122" stroke="${pal.arrow}" stroke-width="1.7" marker-end="url(#fc-arrow)"/>`;
            shapes += `<line x1="${fx}" y1="150" x2="${ux + uw + 4}" y2="150" stroke="${pal.arrow}" stroke-width="1.5" stroke-dasharray="4,3" marker-end="url(#fc-arrow)"/>`;
            shapes += `<text x="${(ux + uw + fx) / 2}" y="114" text-anchor="middle" font-family="'Times New Roman', serif" font-size="10.5" font-weight="bold" fill="${pal.branchText}">${escapeSvgXml(fcSistem.req_label || 'Input')}</text>`;

            // Frontend <-> Backend
            shapes += `<line x1="${fx + fw}" y1="118" x2="${bx - 4}" y2="118" stroke="${pal.arrow}" stroke-width="1.8" marker-end="url(#fc-arrow)"/>`;
            shapes += `<text x="${(fx + fw + bx) / 2}" y="110" text-anchor="middle" font-family="'Times New Roman', serif" font-size="10.5" font-weight="bold" fill="${pal.branchText}">${escapeSvgXml(fcSistem.api_label || 'Request')}</text>`;
            shapes += `<line x1="${bx}" y1="154" x2="${fx + fw + 4}" y2="154" stroke="${pal.arrow}" stroke-width="1.6" stroke-dasharray="4,3" marker-end="url(#fc-arrow)"/>`;
            shapes += `<text x="${(fx + fw + bx) / 2}" y="170" text-anchor="middle" font-family="'Times New Roman', serif" font-size="10.5" fill="${pal.subText}">Response Data</text>`;

            // Backend <-> Database
            shapes += `<line x1="${bx + bw / 2 - 14}" y1="${by + bh}" x2="${bx + bw / 2 - 14}" y2="${dy - 3}" stroke="${pal.arrow}" stroke-width="1.7" marker-end="url(#fc-arrow)"/>`;
            shapes += `<line x1="${bx + bw / 2 + 14}" y1="${dy}" x2="${bx + bw / 2 + 14}" y2="${by + bh + 4}" stroke="${pal.arrow}" stroke-width="1.5" stroke-dasharray="4,3" marker-end="url(#fc-arrow)"/>`;
            shapes += `<text x="${bx + bw / 2 + 26}" y="${(by + bh + dy) / 2 + 4}" font-family="'Times New Roman', serif" font-size="10.5" font-weight="bold" fill="${pal.branchText}">${escapeSvgXml(fcSistem.db_label || 'Query SQL')}</text>`;

            // Frontend -> Output -> User
            shapes += `<line x1="${fx + fw / 2}" y1="${fy + fh}" x2="${fx + fw / 2}" y2="${oy - 4}" stroke="${pal.arrow}" stroke-width="1.7" marker-end="url(#fc-arrow)"/>`;
            shapes += `<polyline points="${ox},${oy + oh / 2} ${ucx},${oy + oh / 2} ${ucx},${uy + uh + 4}" fill="none" stroke="${pal.arrow}" stroke-width="1.6" marker-end="url(#fc-arrow)"/>`;

            return { w: svgW, h: svgH, shapes };
        }

        function renderAnalisisSvg(pal) {
            const count = fcAnalisis.length;
            const cols = count <= 4 ? count : 3;
            const rows = Math.ceil(count / cols);
            const cardW = cols <= 3 ? 236 : 194;
            const cardH = 148;
            const gapX = 32;
            const gapY = 38;
            const padX = 28;
            const padY = 28;
            const svgW = padX * 2 + cols * cardW + (cols - 1) * gapX;
            const svgH = padY * 2 + rows * cardH + (rows - 1) * gapY;

            let shapes = '';
            fcAnalisis.forEach((st, idx) => {
                const r = Math.floor(idx / cols);
                const c = idx % cols;
                const x = padX + c * (cardW + gapX);
                const y = padY + r * (cardH + gapY);

                // Card body
                shapes += `<rect x="${x}" y="${y}" width="${cardW}" height="${cardH}" rx="8" fill="${idx === count - 1 ? pal.yFill : pal.procFill}" stroke="${pal.stroke}" stroke-width="1.8"/>`;
                shapes += `<rect x="${x}" y="${y}" width="${cardW}" height="36" rx="8" fill="${pal.headerFill}"/>`;
                shapes += `<rect x="${x}" y="${y + 22}" width="${cardW}" height="14" fill="${pal.headerFill}"/>`;
                const tLines = wrapFlowchartLines(st.title, cols <= 3 ? 26 : 21, 1);
                shapes += `<text x="${x + cardW / 2}" y="${y + 23}" text-anchor="middle" font-family="'Times New Roman', serif" font-size="12" font-weight="bold" fill="${pal.headerText}">${escapeSvgXml(tLines[0])}</text>`;

                // Bullet details
                const details = (st.details || []).slice(0, 4);
                let dy = y + 56;
                details.forEach((dt) => {
                    const dLines = wrapFlowchartLines('• ' + dt, cols <= 3 ? 28 : 23, 2);
                    dLines.forEach((ln, li) => {
                        shapes += `<text x="${x + 12}" y="${dy}" font-family="'Times New Roman', serif" font-size="11.2" fill="${pal.text}">${escapeSvgXml(li > 0 ? '  ' + ln : ln)}</text>`;
                        dy += 14;
                    });
                    dy += 5;
                });

                // Panah ke kartu berikutnya
                if (idx < count - 1) {
                    const nextR = Math.floor((idx + 1) / cols);
                    if (nextR === r) {
                        shapes += `<line x1="${x + cardW}" y1="${y + cardH / 2}" x2="${x + cardW + gapX - 4}" y2="${y + cardH / 2}" stroke="${pal.arrow}" stroke-width="1.8" marker-end="url(#fc-arrow)"/>`;
                    } else {
                        const nx = padX + cardW / 2;
                        const ny = y + cardH + gapY;
                        const mid = y + cardH + gapY / 2;
                        shapes += `<polyline points="${x + cardW / 2},${y + cardH} ${x + cardW / 2},${mid} ${nx},${mid} ${nx},${ny - 4}" fill="none" stroke="${pal.arrow}" stroke-width="1.8" marker-end="url(#fc-arrow)"/>`;
                    }
                }
            });

            return { w: svgW, h: svgH, shapes };
        }

        function renderFlowchartSvg() {
            const bgMode = document.getElementById('fc-bg-select')?.value || 'white';
            const pal = getDiagramThemePalette();

            let svgW = 500, svgH = 600, shapes = '';

            if (fcCurrentKind === 'kerangka') {
                const res = renderKerangkaSvg(pal);
                svgW = res.w; svgH = res.h; shapes = res.shapes;
            } else if (fcCurrentKind === 'sistem') {
                const res = renderSistemSvg(pal);
                svgW = res.w; svgH = res.h; shapes = res.shapes;
            } else if (fcCurrentKind === 'analisis') {
                const res = renderAnalisisSvg(pal);
                svgW = res.w; svgH = res.h; shapes = res.shapes;
            } else {
                const hasDecision = fcSteps.some(s => s.type === 'decision');
                svgW = hasDecision ? 640 : 500;
                const cx = hasDecision ? 225 : 250;
                const boxW = 304;
                const gapY = 30;
                let y = 24;

                const nodesMeta = [];
                fcSteps.forEach((st) => {
                    const maxC = st.type === 'decision' ? 20 : (st.type === 'io' ? 28 : 32);
                    const lines = wrapFlowchartLines(st.label, maxC);
                    let h = 48;
                    let rightEdge = cx + boxW / 2;
                    if (st.type === 'start' || st.type === 'end') {
                        h = lines.length > 1 ? 52 : 44;
                        const tw = Math.min(boxW, Math.max(170, lines[0].length * 9 + 48));
                        rightEdge = cx + tw / 2;
                    } else if (st.type === 'decision') {
                        h = lines.length >= 3 ? 98 : (lines.length === 2 ? 86 : 74);
                        rightEdge = cx + 132;
                    } else if (st.type === 'io') {
                        h = Math.max(48, 22 + lines.length * 18);
                        rightEdge = cx + boxW / 2 - 9;
                    } else {
                        h = Math.max(48, 22 + lines.length * 18);
                    }
                    nodesMeta.push({ st, lines, y, h, cy: y + h / 2, bottom: y + h, rightEdge });
                    y += h + gapY;
                });

                svgH = y - gapY + 24;

                nodesMeta.forEach((n, idx) => {
                    const { st, lines, y: ny, h, cy, bottom } = n;
                    const fill = (st.type === 'start' || st.type === 'end') ? pal.termFill
                        : (st.type === 'io' ? pal.ioFill : (st.type === 'decision' ? pal.decFill : pal.procFill));
                    const txtColor = ((st.type === 'start' || st.type === 'end') && pal.termText) ? pal.termText : pal.text;

                    if (st.type === 'start' || st.type === 'end') {
                        const tw = (n.rightEdge - cx) * 2;
                        shapes += `<rect x="${cx - tw / 2}" y="${ny}" width="${tw}" height="${h}" rx="${h / 2}" ry="${h / 2}" fill="${fill}" stroke="${pal.stroke}" stroke-width="1.8"/>`;
                    } else if (st.type === 'io') {
                        const skew = 18;
                        const x1 = cx - boxW / 2 + skew;
                        const x2 = cx + boxW / 2;
                        const x3 = cx + boxW / 2 - skew;
                        const x4 = cx - boxW / 2;
                        shapes += `<polygon points="${x1},${ny} ${x2},${ny} ${x3},${bottom} ${x4},${bottom}" fill="${fill}" stroke="${pal.stroke}" stroke-width="1.8"/>`;
                    } else if (st.type === 'decision') {
                        const dw = 132;
                        const pts = `${cx},${ny} ${cx + dw},${cy} ${cx},${bottom} ${cx - dw},${cy}`;
                        shapes += `<polygon points="${pts}" fill="${fill}" stroke="${pal.stroke}" stroke-width="1.8"/>`;

                        const loopX = cx + dw + 140;
                        const backIdx = Math.max(0, idx - 1);
                        const targetNode = nodesMeta[backIdx];
                        const targetY = targetNode.cy;
                        const targetRightX = targetNode.rightEdge + 4;
                        const noTxt = (st.no_label || 'Revisi / Ulangi').trim();
                        const pillW = Math.min(180, Math.max(118, noTxt.length * 6.5 + 22));
                        const pillX = Math.min(svgW - pillW - 10, loopX - pillW / 2);

                        shapes += `<polyline points="${cx + dw},${cy} ${loopX},${cy} ${loopX},${targetY} ${targetRightX},${targetY}" fill="none" stroke="${pal.arrow}" stroke-width="1.5" stroke-dasharray="4,3" marker-end="url(#fc-arrow)"/>`;
                        shapes += `<text x="${cx + dw + 10}" y="${cy - 6}" font-family="'Times New Roman', serif" font-size="11.5" font-weight="bold" fill="${pal.branchText}">Tidak</text>`;
                        shapes += `<rect x="${pillX}" y="${(cy + targetY) / 2 - 12}" width="${pillW}" height="24" rx="4" fill="#ffffff" stroke="${pal.stroke}" stroke-width="0.95"/>`;
                        shapes += `<text x="${pillX + pillW / 2}" y="${(cy + targetY) / 2 + 3.5}" text-anchor="middle" font-family="'Times New Roman', serif" font-size="10.5" fill="${pal.text}">${escapeSvgXml(noTxt.slice(0, 28))}</text>`;
                    } else {
                        shapes += `<rect x="${cx - boxW / 2}" y="${ny}" width="${boxW}" height="${h}" rx="4" fill="${fill}" stroke="${pal.stroke}" stroke-width="1.8"/>`;
                    }

                    const lineH = 16;
                    const startTextY = cy - ((lines.length - 1) * lineH) / 2 + 4.5;
                    lines.forEach((ln, li) => {
                        const boldAttr = (st.type === 'start' || st.type === 'end') ? 'font-weight="bold"' : '';
                        shapes += `<text x="${cx}" y="${startTextY + li * lineH}" text-anchor="middle" font-family="'Times New Roman', Times, serif" font-size="13" ${boldAttr} fill="${txtColor}">${escapeSvgXml(ln)}</text>`;
                    });

                    if (idx < nodesMeta.length - 1) {
                        const nextY = nodesMeta[idx + 1].y;
                        shapes += `<line x1="${cx}" y1="${bottom}" x2="${cx}" y2="${nextY - 3}" stroke="${pal.arrow}" stroke-width="1.6" marker-end="url(#fc-arrow)"/>`;
                        if (st.type === 'decision') {
                            shapes += `<text x="${cx + 8}" y="${bottom + 17}" font-family="'Times New Roman', serif" font-size="11.5" font-weight="bold" fill="${pal.branchText}">Ya</text>`;
                        }
                    }
                });
            }

            lastFlowchartDims = { w: svgW, h: svgH };
            const bgRect = bgMode === 'transparent' ? '' : `<rect width="100%" height="100%" fill="${pal.bg}"/>`;
            lastFlowchartSvgString = `<svg xmlns="http://www.w3.org/2000/svg" width="${svgW}" height="${svgH}" viewBox="0 0 ${svgW} ${svgH}">
                <defs>
                    <marker id="fc-arrow" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="6.5" markerHeight="6.5" orient="auto-start-reverse">
                        <path d="M 0 1 L 10 5 L 0 9 z" fill="${pal.arrow}"/>
                    </marker>
                </defs>
                ${bgRect}
                ${shapes}
            </svg>`;

            const previewEl = document.getElementById('fc-svg-preview');
            if (previewEl) {
                previewEl.classList.toggle('transparent-bg', bgMode === 'transparent');
                previewEl.innerHTML = lastFlowchartSvgString;
            }
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
            setStatus('status-fc', '', '');
            setProc('fc', true, 'AI sedang merancang diagram sesuai jenis & topik penelitianmu...', 45);
            try {
                const res = await fetch('{{ route("tools.flowchart.generate") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ topic, kind })
                });
                const data = await res.json();
                if (!res.ok || data.status !== 'success') {
                    throw new Error(data.message || 'Gagal menyusun diagram.');
                }
                setProc('fc', true, 'Menggambar diagram vektor HD...', 95);
                fcCurrentKind = kind;
                if (kind === 'kerangka' && data.diagram) {
                    const d = data.diagram;
                    fcKerangka = {
                        layout: ['regresi', 'mediasi', 'bertingkat'].includes(d.layout) ? d.layout : 'regresi',
                        x_vars: Array.isArray(d.x_vars) && d.x_vars.length ? d.x_vars.slice(0, 5) : fcKerangka.x_vars,
                        mediator: d.mediator || fcKerangka.mediator,
                        y_var: d.y_var || fcKerangka.y_var,
                        hypotheses: Array.isArray(d.hypotheses) && d.hypotheses.length ? d.hypotheses : ['H1', 'H2', 'H3'],
                        simultaneous: d.simultaneous !== false,
                        stages: Array.isArray(d.stages) && d.stages.length >= 3 ? d.stages.slice(0, 6) : fcKerangka.stages
                    };
                } else if (kind === 'sistem' && data.diagram) {
                    fcSistem = Object.assign({}, fcSistem, data.diagram);
                } else if (kind === 'analisis' && Array.isArray(data.diagram) && data.diagram.length >= 2) {
                    fcAnalisis = data.diagram.slice(0, 5);
                } else if (Array.isArray(data.steps) && data.steps.length >= 3) {
                    fcSteps = data.steps;
                }
                onChangeFlowchartKind(kind);
                setProc('fc', false);
                setStatus('status-fc', 'success', 'Berhasil! Diagram siap diedit, ganti tema warna, atau unduh PNG HD ke Word.');
            } catch (err) {
                setProc('fc', false);
                setStatus('status-fc', 'error', err.message || 'Terjadi kesalahan saat generate diagram.');
            } finally {
                btn.disabled = false;
            }
        }

        function renderFlowchartToPngBlob() {
            return new Promise((resolve, reject) => {
                if (!lastFlowchartSvgString) return reject(new Error('Flowchart belum tersedia.'));
                const bgMode = document.getElementById('fc-bg-select')?.value || 'white';
                const svgBlob = new Blob([lastFlowchartSvgString], { type: 'image/svg+xml;charset=utf-8' });
                const url = URL.createObjectURL(svgBlob);
                const img = new Image();
                img.onload = () => {
                    const scale = 3;
                    const w = lastFlowchartDims.w || img.naturalWidth || 620;
                    const h = lastFlowchartDims.h || img.naturalHeight || 640;
                    const canvas = document.createElement('canvas');
                    canvas.width = Math.round(w * scale);
                    canvas.height = Math.round(h * scale);
                    const ctx = canvas.getContext('2d');
                    if (bgMode !== 'transparent') {
                        ctx.fillStyle = '#ffffff';
                        ctx.fillRect(0, 0, canvas.width, canvas.height);
                    }
                    ctx.scale(scale, scale);
                    ctx.drawImage(img, 0, 0, w, h);
                    URL.revokeObjectURL(url);
                    canvas.toBlob(blob => blob ? resolve(blob) : reject(new Error('Gagal membuat PNG.')), 'image/png');
                };
                img.onerror = () => { URL.revokeObjectURL(url); reject(new Error('Gagal merender SVG ke PNG.')); };
                img.src = url;
            });
        }

        function downloadFlowchartSvg() {
            if (!lastFlowchartSvgString) return;
            triggerBlobDownload(new Blob([lastFlowchartSvgString], { type: 'image/svg+xml;charset=utf-8' }), 'Flowchart_Skripsi.svg');
            setStatus('status-fc-export', 'success', '✓ File Flowchart_Skripsi.svg berhasil diunduh.');
        }

        async function downloadFlowchartPng() {
            try {
                const blob = await renderFlowchartToPngBlob();
                triggerBlobDownload(blob, 'Flowchart_Skripsi_HD.png');
                setStatus('status-fc-export', 'success', '✓ File Flowchart_Skripsi_HD.png (Resolusi 3x HD) berhasil diunduh.');
            } catch (err) {
                setStatus('status-fc-export', 'error', err.message || 'Gagal mengunduh PNG.');
            }
        }

        async function copyFlowchartPng() {
            try {
                const blob = await renderFlowchartToPngBlob();
                if (!navigator.clipboard || !window.ClipboardItem) {
                    throw new Error('Browser ini belum mendukung salin gambar langsung. Gunakan tombol Unduh PNG HD.');
                }
                await navigator.clipboard.write([new ClipboardItem({ 'image/png': blob })]);
                setStatus('status-fc-export', 'success', '✓ Gambar Flowchart disalin ke Clipboard! Tinggal Ctrl+V / Cmd+V di Microsoft Word.');
            } catch (err) {
                setStatus('status-fc-export', 'error', err.message || 'Gagal menyalin gambar.');
            }
        }

        // ============================================================
        // 2. RAPIKAN SKRIPSI WORD (.DOCX) — 100% OOXML SURGERY (ISI UTUH)
        // ============================================================
        let tidyDocxFile = null;
        let tidyDocxResultBlob = null;
        let tidyDocxOutName = 'Skripsi_Rapi.docx';

        function onSelectTidyDocx(input) {
            if (!input.files || !input.files[0]) return;
            const f = input.files[0];
            if (!f.name.toLowerCase().endsWith('.docx')) {
                setStatus('status-tidy', 'error', 'Format file harus Microsoft Word (.docx).');
                return;
            }
            tidyDocxFile = f;
            hideResult('tidy');
            document.getElementById('label-tidy-docx').textContent = `${f.name} (${formatBytes(f.size)})`;
            setStatus('status-tidy', 'info', 'File Word siap dirapikan. Pilih standar margin/font lalu klik "Rapikan Skripsi Sekarang".');
        }

        function toggleAllTidyOpts(btn) {
            const boxes = Array.from(document.querySelectorAll('#tidy-options-grid input[type="checkbox"]'));
            const allChecked = boxes.length > 0 && boxes.every(b => b.checked);
            boxes.forEach(b => { b.checked = !allChecked; });
            syncTidyToggleBtn();
        }

        function onToggleTidyPageNum() {
            const cb = document.getElementById('tidy-opt-pagenum');
            const sub = document.getElementById('tidy-pagenum-subopts');
            if (sub) sub.style.display = (cb && cb.checked) ? 'grid' : 'none';
            syncTidyToggleBtn();
        }

        function syncTidyToggleBtn() {
            const boxes = Array.from(document.querySelectorAll('#tidy-options-grid input[type="checkbox"]'));
            const btn = document.getElementById('btn-toggle-all-tidy');
            if (btn) {
                const allChecked = boxes.length > 0 && boxes.every(b => b.checked);
                btn.textContent = allChecked ? 'Kosongkan Semua' : 'Centang Semua';
            }
            const cb = document.getElementById('tidy-opt-pagenum');
            const sub = document.getElementById('tidy-pagenum-subopts');
            if (sub && cb) sub.style.display = cb.checked ? 'grid' : 'none';
        }

        async function runTidyDocx() {
            if (!tidyDocxFile) {
                setStatus('status-tidy', 'error', 'Pilih file Word (.docx) skripsimu terlebih dahulu.');
                return;
            }
            const checkedTidyCount = document.querySelectorAll('#tidy-options-grid input[type="checkbox"]:checked').length;
            if (checkedTidyCount < 1) {
                setStatus('status-tidy', 'error', 'Silakan pilih minimal 1 opsi perapian terlebih dahulu.');
                document.getElementById('tidy-options-grid')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
            const btn = document.getElementById('btn-tidy-docx');
            btn.disabled = true;
            hideResult('tidy');
            setStatus('status-tidy', '', '');
            try {
                const JSZip = await waitForLib('JSZip', 'JSZip');
                setProc('tidy', true, 'Membuka struktur dokumen Word (.docx)...', 15);
                const zip = await JSZip.loadAsync(await tidyDocxFile.arrayBuffer());
                const docXmlStr = await zip.file('word/document.xml')?.async('string');
                if (!docXmlStr) throw new Error('File .docx tidak valid atau korup (word/document.xml tidak ditemukan).');

                setProc('tidy', true, 'Menganalisis BAB, Sub-BAB, Daftar Pustaka, & Tabel...', 35);
                const W_NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
                const parser = new DOMParser();
                const xmlDoc = parser.parseFromString(docXmlStr, 'application/xml');
                if (xmlDoc.getElementsByTagName('parsererror').length) {
                    throw new Error('Struktur XML dokumen Word bermasalah.');
                }

                const marginVal = document.getElementById('tidy-margin').value;
                const [fontName, fontSzStr] = document.getElementById('tidy-font').value.split('|');
                const bodySz = parseInt(fontSzStr, 10) || 24;
                const lineVal = document.getElementById('tidy-spacing').value || '360';
                const optPageBreak = Boolean(document.getElementById('tidy-opt-pagebreak')?.checked);
                const optIndent = Boolean(document.getElementById('tidy-opt-indent')?.checked);
                const optTables = Boolean(document.getElementById('tidy-opt-tables')?.checked);
                const optSpaces = Boolean(document.getElementById('tidy-opt-spaces')?.checked);
                const optImages = Boolean(document.getElementById('tidy-opt-images')?.checked);
                const optCleanStyle = Boolean(document.getElementById('tidy-opt-cleanstyle')?.checked);
                const optProtectCover = Boolean(document.getElementById('tidy-opt-cover')?.checked);
                const optAbstract = Boolean(document.getElementById('tidy-opt-abstract')?.checked);
                const optPageNum = Boolean(document.getElementById('tidy-opt-pagenum')?.checked);
                const pageNumLayout = document.getElementById('tidy-pagenum-layout')?.value || 'bab_bottom_right';
                const romanStartVal = parseInt(document.getElementById('tidy-pagenum-roman-start')?.value || '2', 10) || 2;
                const onlyPageNum = optPageNum && !optPageBreak && !optIndent && !optTables && !optSpaces && !optImages && !optCleanStyle && !optProtectCover && !optAbstract;

                const marginPresets = {
                    '4433': { top: 2268, left: 2268, bottom: 1701, right: 1701 },
                    '3333': { top: 1701, left: 1701, bottom: 1701, right: 1701 },
                    'normal': { top: 1440, left: 1440, bottom: 1440, right: 1440 }
                };
                const mar = marginPresets[marginVal] || marginPresets['4433'];
                const pageWTwip = 11906;
                const contentWTwip = pageWTwip - mar.left - mar.right;
                const maxImgEmu = Math.round(contentWTwip * 635); // 1 twip = 635 EMUs

                // Urutan elemen anak sesuai skema OOXML agar Microsoft Word macOS/Windows tidak korup atau mengabaikan style
                const PPR_ORDER = ['pStyle', 'keepNext', 'keepLines', 'pageBreakBefore', 'widowControl', 'numPr', 'pBdr', 'shd', 'tabs', 'spacing', 'ind', 'jc', 'outlineLvl', 'rPr', 'sectPr'];
                const RPR_ORDER = ['rStyle', 'rFonts', 'b', 'bCs', 'i', 'iCs', 'caps', 'strike', 'color', 'sz', 'szCs', 'highlight', 'u', 'shd', 'vertAlign'];
                const TBLPR_ORDER = ['tblStyle', 'tblW', 'jc', 'tblInd', 'tblBorders', 'shd', 'tblLayout', 'tblCellMar', 'tblLook'];

                const mkW = (tag) => xmlDoc.createElementNS(W_NS, 'w:' + tag);
                const setWAttr = (el, name, val) => el.setAttributeNS(W_NS, 'w:' + name, String(val));
                const getDirectChild = (parent, local) => Array.from(parent.childNodes).find(n => n.nodeType === 1 && n.localName === local);
                const ensureOrderedChild = (parent, local, orderList) => {
                    let c = getDirectChild(parent, local);
                    if (c) return c;
                    c = mkW(local);
                    if (orderList && orderList.length) {
                        const targetIdx = orderList.indexOf(local);
                        if (targetIdx >= 0) {
                            const nextEl = Array.from(parent.childNodes).find(n => {
                                if (n.nodeType !== 1) return false;
                                const ni = orderList.indexOf(n.localName);
                                return ni > targetIdx;
                            });
                            if (nextEl) {
                                parent.insertBefore(c, nextEl);
                                return c;
                            }
                        }
                    }
                    parent.appendChild(c);
                    return c;
                };
                const ensureChild = (parent, local, prepend = false) => {
                    let c = getDirectChild(parent, local);
                    if (!c) {
                        c = mkW(local);
                        if (prepend && parent.firstChild) parent.insertBefore(c, parent.firstChild);
                        else parent.appendChild(c);
                    }
                    return c;
                };
                const removeDirectChild = (parent, local) => {
                    Array.from(parent.childNodes).filter(n => n.nodeType === 1 && n.localName === local).forEach(n => parent.removeChild(n));
                };

                const stats = {
                    paragraphsTidied: 0,
                    emptyRemoved: 0,
                    chaptersFixed: 0,
                    subHeadingsFixed: 0,
                    tablesFixed: 0,
                    tablesProtected: 0,
                    bibEntries: 0,
                    spacesCleaned: 0,
                    imagesResized: 0,
                    stylesCleaned: 0,
                    abstractFixed: 0,
                    pageSectionsFixed: 0,
                    imagesKept: 0
                };

                zip.forEach((relPath) => {
                    if (/^word\/media\//i.test(relPath)) stats.imagesKept++;
                });

                const body = xmlDoc.getElementsByTagNameNS(W_NS, 'body')[0];
                if (!body) throw new Error('Body dokumen tidak ditemukan.');

                // 1. Atur kertas A4 & Margin (lewati bila user hanya memilih fokus Rapikan Penomoran Halaman)
                if (!onlyPageNum) {
                    Array.from(xmlDoc.getElementsByTagNameNS(W_NS, 'sectPr')).forEach(sectPr => {
                        const pgSz = ensureChild(sectPr, 'pgSz');
                        const isLandscape = (pgSz.getAttributeNS(W_NS, 'orient') || '').toLowerCase() === 'landscape';
                        if (isLandscape) {
                            setWAttr(pgSz, 'w', '16838');
                            setWAttr(pgSz, 'h', '11906');
                        } else {
                            setWAttr(pgSz, 'w', '11906');
                            setWAttr(pgSz, 'h', '16838');
                        }
                        const pgMar = ensureChild(sectPr, 'pgMar');
                        setWAttr(pgMar, 'top', mar.top);
                        setWAttr(pgMar, 'left', mar.left);
                        setWAttr(pgMar, 'bottom', mar.bottom);
                        setWAttr(pgMar, 'right', mar.right);
                        if (!pgMar.getAttributeNS(W_NS, 'header')) setWAttr(pgMar, 'header', '709');
                        if (!pgMar.getAttributeNS(W_NS, 'footer')) setWAttr(pgMar, 'footer', '709');
                    });
                }

                setProc('tidy', true, onlyPageNum ? 'Menganalisis bagian Cover, Bagian Awal & BAB untuk penomoran halaman...' : 'Merapikan paragraf, BAB, gambar, tabel & menghapus halaman kosong...', 55);

                const isInsideEquation = (node) => {
                    let cur = node.parentNode;
                    while (cur && cur.nodeType === 1) {
                        if (cur.localName === 'oMath' || cur.localName === 'oMathPara') return true;
                        cur = cur.parentNode;
                    }
                    return false;
                };

                const applyRunStyle = (rNode, szHalfPt, forceBold = null) => {
                    if (optProtectCover && isInsideEquation(rNode)) return;
                    const rPr = ensureChild(rNode, 'rPr', true);
                    const rFonts = ensureOrderedChild(rPr, 'rFonts', RPR_ORDER);
                    setWAttr(rFonts, 'ascii', fontName);
                    setWAttr(rFonts, 'hAnsi', fontName);
                    setWAttr(rFonts, 'cs', fontName);
                    setWAttr(rFonts, 'eastAsia', fontName);

                    // Pertahankan ukuran kecil untuk pangkat/subskrip (superscript/subscript)
                    const hasVertAlign = !!getDirectChild(rPr, 'vertAlign');
                    const finalSz = hasVertAlign ? Math.max(16, szHalfPt - 4) : szHalfPt;
                    const sz = ensureOrderedChild(rPr, 'sz', RPR_ORDER);
                    setWAttr(sz, 'val', finalSz);
                    const szCs = ensureOrderedChild(rPr, 'szCs', RPR_ORDER);
                    setWAttr(szCs, 'val', finalSz);

                    if (optCleanStyle) {
                        const color = getDirectChild(rPr, 'color');
                        if (color) {
                            const cv = (color.getAttributeNS(W_NS, 'val') || '').toLowerCase();
                            if (cv && cv !== 'auto' && cv !== '000000') {
                                setWAttr(color, 'val', '000000');
                                stats.stylesCleaned++;
                            }
                        }
                        if (getDirectChild(rPr, 'shd')) { removeDirectChild(rPr, 'shd'); stats.stylesCleaned++; }
                        if (getDirectChild(rPr, 'highlight')) { removeDirectChild(rPr, 'highlight'); stats.stylesCleaned++; }
                    }

                    if (forceBold === true) {
                        ensureOrderedChild(rPr, 'b', RPR_ORDER);
                    }
                };

                const cleanRunSpaces = (pNode) => {
                    if (!optSpaces) return;
                    Array.from(pNode.getElementsByTagNameNS(W_NS, 't')).forEach(tNode => {
                        if (optProtectCover && isInsideEquation(tNode)) return;
                        const orig = tNode.textContent || '';
                        if (!orig || /^\s+$/.test(orig)) return;
                        let fixed = orig.replace(/[ \u00A0]{2,}/g, ' ').replace(/ +([,.;:!?])/g, '$1');
                        if (fixed !== orig) {
                            tNode.textContent = fixed;
                            tNode.setAttribute('xml:space', 'preserve');
                            stats.spacesCleaned++;
                        }
                    });
                };

                // Helper skalakan gambar yang melebar melebihi batas margin kertas (EMU) secara proporsional
                const constrainNodeImages = (node) => {
                    if (!optImages) return;
                    const drawings = Array.from(node.getElementsByTagNameNS('*', 'drawing'));
                    drawings.forEach(drw => {
                        const extents = Array.from(drw.getElementsByTagNameNS('*', 'extent'));
                        extents.forEach(ext => {
                            const cx = parseInt(ext.getAttribute('cx') || '0', 10);
                            const cy = parseInt(ext.getAttribute('cy') || '0', 10);
                            if (cx > maxImgEmu && cx > 0 && cy > 0) {
                                const ratio = maxImgEmu / cx;
                                const newCx = maxImgEmu;
                                const newCy = Math.round(cy * ratio);
                                ext.setAttribute('cx', String(newCx));
                                ext.setAttribute('cy', String(newCy));
                                Array.from(drw.getElementsByTagNameNS('*', 'ext')).forEach(aExt => {
                                    const ax = parseInt(aExt.getAttribute('cx') || '0', 10);
                                    const ay = parseInt(aExt.getAttribute('cy') || '0', 10);
                                    if (ax > maxImgEmu && ay > 0) {
                                        aExt.setAttribute('cx', String(newCx));
                                        aExt.setAttribute('cy', String(Math.round(ay * (newCx / ax))));
                                    }
                                });
                                stats.imagesResized++;
                            }
                        });
                    });
                };

                const bodyChildren = Array.from(body.childNodes).filter(n => n.nodeType === 1);
                // Deteksi apakah dokumen memiliki halaman depan/cover sebelum ABSTRAK / KATA PENGANTAR / DAFTAR ISI / BAB I
                const firstMajorIdx = bodyChildren.findIndex(n => {
                    if (n.localName !== 'p') return false;
                    const t = Array.from(n.getElementsByTagNameNS(W_NS, 't')).map(x => x.textContent || '').join('').replace(/\s+/g, ' ').trim();
                    if (/\.{3,}/.test(t) || n.getElementsByTagNameNS(W_NS, 'tab').length > 0) return false;
                    return t.length <= 75 && /^(?:BAB\s+[IVX0-9]+|KATA\s+PENGANTAR|ABSTRAK|ABSTRACT|DAFTAR\s+ISI)$/i.test(t);
                });
                const hasCoverZone = optProtectCover && firstMajorIdx > 0;

                let inBibliography = false;
                let inAbstract = false;
                let inTocSection = false;
                let prevWasBabHeading = false;
                let prevWasPageBreak = false;
                let seenRealContent = false;
                let consecutiveEmpty = 0;

                for (let idx = 0; idx < (onlyPageNum ? 0 : bodyChildren.length); idx++) {
                    const node = bodyChildren[idx];
                    const inCoverZone = hasCoverZone && idx < firstMajorIdx;

                    if (node.localName === 'p') {
                        const hasDrawing = node.getElementsByTagNameNS('*', 'drawing').length > 0
                            || node.getElementsByTagNameNS('*', 'pict').length > 0
                            || node.getElementsByTagNameNS('*', 'object').length > 0;
                        const pPrExisting = getDirectChild(node, 'pPr');
                        const hasSectPr = !!pPrExisting && !!getDirectChild(pPrExisting, 'sectPr');
                        const styleVal = (pPrExisting && getDirectChild(pPrExisting, 'pStyle')?.getAttributeNS(W_NS, 'val')) || '';
                        const isTocStyle = /^TOC/i.test(styleVal);
                        const hasFieldCode = node.getElementsByTagNameNS(W_NS, 'fldChar').length > 0 || node.getElementsByTagNameNS(W_NS, 'instrText').length > 0;
                        const hasTabEl = node.getElementsByTagNameNS(W_NS, 'tab').length > 0;
                        const rawText = Array.from(node.getElementsByTagNameNS(W_NS, 't')).map(t => t.textContent || '').join('');
                        const text = rawText.replace(/\s+/g, ' ').trim();

                        // Deteksi baris Daftar Isi / Daftar Tabel / Daftar Gambar (TOC otomatis Word, tab-stop dot-leader PHPWord, maupun titik manual "........ ii / 12")
                        const hasDotLeader = /\.{3,}/.test(text);
                        const tabNodes = Array.from(node.getElementsByTagNameNS(W_NS, 'tab'));
                        const hasDotTabStop = tabNodes.some(tb => (tb.getAttributeNS(W_NS, 'leader') || '').toLowerCase() === 'dot');
                        const hasLiteralTab = rawText.includes('\t');
                        const endsWithTocPage = (hasDotLeader || hasTabEl || hasLiteralTab || /\s{2,}/.test(rawText)) && /(?:\b[ivxlcdm]{1,6}|\b\d{1,4})\s*$/i.test(text);
                        const isTocEntry = isTocStyle || hasDotTabStop || hasDotLeader || endsWithTocPage;

                        // Lindungi Cover Page / Lembar Pengesahan / Halaman Depan
                        if (inCoverZone) {
                            if (hasDrawing) constrainNodeImages(node);
                            if (text) {
                                seenRealContent = true;
                                Array.from(node.getElementsByTagNameNS(W_NS, 'r')).forEach(r => {
                                    const rPr = ensureChild(r, 'rPr', true);
                                    const rFonts = ensureOrderedChild(rPr, 'rFonts', RPR_ORDER);
                                    setWAttr(rFonts, 'ascii', fontName);
                                    setWAttr(rFonts, 'hAnsi', fontName);
                                    setWAttr(rFonts, 'cs', fontName);
                                });
                            }
                            prevWasBabHeading = false;
                            prevWasPageBreak = hasSectPr;
                            continue;
                        }

                        // SELALU lindungi entri Daftar Isi / TOC (agar tidak pernah diberi page-break atau diubah jadi judul BAB!)
                        if (isTocEntry || (hasFieldCode && /TOC|PAGEREF/i.test(Array.from(node.getElementsByTagNameNS(W_NS, 'instrText')).map(x => x.textContent || '').join('')))) {
                            seenRealContent = true;
                            prevWasBabHeading = false;
                            prevWasPageBreak = false;
                            // Sesuaikan posisi tab-stop rata kanan dot-leader agar pas dengan margin kertas yang dipilih
                            tabNodes.forEach(tb => {
                                if ((tb.getAttributeNS(W_NS, 'val') || '').toLowerCase() === 'right') {
                                    setWAttr(tb, 'pos', String(contentWTwip));
                                }
                            });
                            // Seragamkan hanya jenis font secara aman tanpa merusak tab stop / dot leader / halaman
                            Array.from(node.getElementsByTagNameNS(W_NS, 'r')).forEach(r => {
                                const rPr = ensureChild(r, 'rPr', true);
                                const rFonts = ensureOrderedChild(rPr, 'rFonts', RPR_ORDER);
                                setWAttr(rFonts, 'ascii', fontName);
                                setWAttr(rFonts, 'hAnsi', fontName);
                                setWAttr(rFonts, 'cs', fontName);
                            });
                            continue;
                        }

                        if (!text && !hasDrawing && !hasSectPr) {
                            const hasBrPage = Array.from(node.getElementsByTagNameNS(W_NS, 'br'))
                                .some(b => b.getAttributeNS(W_NS, 'type') === 'page');
                            if (optPageBreak && (hasBrPage || consecutiveEmpty >= 1 || !seenRealContent)) {
                                body.removeChild(node);
                                stats.emptyRemoved++;
                                // Jangan set prevWasPageBreak = true karena break kosongnya baru saja dihapus;
                                // judul BAB berikutnya tetap butuh pageBreakBefore agar mulai di halaman baru!
                                continue;
                            }
                            if (hasBrPage) prevWasPageBreak = true;
                            consecutiveEmpty++;
                            continue;
                        }

                        consecutiveEmpty = 0;
                        cleanRunSpaces(node);
                        if (hasDrawing) constrainNodeImages(node);

                        const pPr = ensureChild(node, 'pPr', true);
                        if (optCleanStyle && getDirectChild(pPr, 'shd')) {
                            removeDirectChild(pPr, 'shd');
                            stats.stylesCleaned++;
                        }

                        if (hasDrawing && !text) {
                            if (optImages) {
                                const jc = ensureOrderedChild(pPr, 'jc', PPR_ORDER);
                                setWAttr(jc, 'val', 'center');
                                removeDirectChild(pPr, 'ind');
                                const sp = ensureOrderedChild(pPr, 'spacing', PPR_ORDER);
                                setWAttr(sp, 'before', '120');
                                setWAttr(sp, 'after', '100');
                                ensureOrderedChild(pPr, 'keepNext', PPR_ORDER);
                            }
                            seenRealContent = true;
                            prevWasBabHeading = false;
                            prevWasPageBreak = false;
                            continue;
                        }

                        const upperText = text.toUpperCase();
                        const isMajorHeading = !hasTabEl && text.length <= 75 && /^(?:BAB\s+[IVX0-9]+(?:\s*[:\-–.]?\s*[A-ZÀ-Ÿ\s]{0,50})?|DAFTAR\s+PUSTAKA|DAFTAR\s+ISI|DAFTAR\s+TABEL|DAFTAR\s+GAMBAR|DAFTAR\s+LAMPIRAN|KATA\s+PENGANTAR|ABSTRAK|ABSTRACT|LEMBAR\s+PENGESAHAN|SURAT\s+PERNYATAAN(?:\s+KEASLIAN)?|PERNYATAAN\s+KEASLIAN|LAMPIRAN(?:\s+[A-Z0-9]+)?)$/i.test(text);
                        const isBabOnlyLine = /^BAB\s+[IVX0-9]+\s*$/i.test(text);
                        // Judul lanjutan BAB (mis. "PENDAHULUAN" tepat di bawah "BAB I") HANYA jika paragraf sebelumnya benar-benar baris "BAB I/II/III/IV/V"
                        const isBabTitleFollowup = prevWasBabHeading && text.length <= 75 && upperText === text && /^[A-Z0-9\s,&\-–]+$/.test(text) && text.split(' ').length <= 8 && !/^\d/.test(text);
                        const isSubHeading = !isMajorHeading && !inTocSection && text.length <= 120 && /^(?:\d+\.\d+(?:\.\d+)?\.?\s+|[A-Z]\.\s+)[A-Za-zÀ-ÿ]/.test(text);
                        const isCaption = !inTocSection && /^(?:Gambar|Tabel|Grafik|Bagan|Sumber\s*:)\s*/i.test(text) && text.length <= 170;
                        const isListItem = !!getDirectChild(pPr, 'numPr') || /^(?:\d+[\.\)]\s+|[a-z][\.\)]\s+|[•\-–]\s+)/.test(text);

                        if (/^DAFTAR\s+(?:ISI|TABEL|GAMBAR|LAMPIRAN)$/i.test(text)) {
                            inTocSection = true;
                            inBibliography = false;
                            inAbstract = false;
                        } else if (/^DAFTAR\s+PUSTAKA$/i.test(text)) {
                            inTocSection = false;
                            inBibliography = true;
                            inAbstract = false;
                        } else if (/^(?:ABSTRAK|ABSTRACT)$/i.test(text)) {
                            inTocSection = false;
                            inAbstract = true;
                            inBibliography = false;
                        } else if (isMajorHeading) {
                            inTocSection = false;
                            inAbstract = false;
                            if (/^(?:BAB\s+|LAMPIRAN)/i.test(text)) inBibliography = false;
                        }

                        // Jika sedang berada di dalam zona Daftar Isi/Tabel/Gambar manual, jangan acak tata letak baris daftar isinya
                        if (inTocSection && !isMajorHeading) {
                            Array.from(node.getElementsByTagNameNS(W_NS, 'r')).forEach(r => {
                                const rPr = ensureChild(r, 'rPr', true);
                                const rFonts = ensureOrderedChild(rPr, 'rFonts', RPR_ORDER);
                                setWAttr(rFonts, 'ascii', fontName);
                                setWAttr(rFonts, 'hAnsi', fontName);
                                setWAttr(rFonts, 'cs', fontName);
                            });
                            seenRealContent = true;
                            prevWasBabHeading = false;
                            prevWasPageBreak = false;
                            continue;
                        }

                        const runs = Array.from(node.getElementsByTagNameNS(W_NS, 'r'));

                        if (isMajorHeading) {
                            if (optPageBreak && seenRealContent && !prevWasPageBreak) {
                                ensureOrderedChild(pPr, 'pageBreakBefore', PPR_ORDER);
                            }
                            ensureOrderedChild(pPr, 'keepNext', PPR_ORDER);
                            ensureOrderedChild(pPr, 'keepLines', PPR_ORDER);
                            const jc = ensureOrderedChild(pPr, 'jc', PPR_ORDER);
                            setWAttr(jc, 'val', 'center');
                            removeDirectChild(pPr, 'ind');
                            const sp = ensureOrderedChild(pPr, 'spacing', PPR_ORDER);
                            setWAttr(sp, 'before', '0');
                            setWAttr(sp, 'after', '180');
                            setWAttr(sp, 'line', '276');
                            setWAttr(sp, 'lineRule', 'auto');
                            runs.forEach(r => applyRunStyle(r, Math.max(bodySz + 4, 28), true));
                            stats.chaptersFixed++;
                            prevWasBabHeading = isBabOnlyLine;
                            prevWasPageBreak = false;
                        } else if (isBabTitleFollowup) {
                            ensureOrderedChild(pPr, 'keepNext', PPR_ORDER);
                            ensureOrderedChild(pPr, 'keepLines', PPR_ORDER);
                            const jc = ensureOrderedChild(pPr, 'jc', PPR_ORDER);
                            setWAttr(jc, 'val', 'center');
                            removeDirectChild(pPr, 'ind');
                            const sp = ensureOrderedChild(pPr, 'spacing', PPR_ORDER);
                            setWAttr(sp, 'before', '0');
                            setWAttr(sp, 'after', '200');
                            setWAttr(sp, 'line', '276');
                            setWAttr(sp, 'lineRule', 'auto');
                            runs.forEach(r => applyRunStyle(r, bodySz + 2, true));
                            prevWasBabHeading = false;
                            prevWasPageBreak = false;
                        } else if (isSubHeading) {
                            prevWasBabHeading = false;
                            prevWasPageBreak = false;
                            ensureOrderedChild(pPr, 'keepNext', PPR_ORDER);
                            ensureOrderedChild(pPr, 'keepLines', PPR_ORDER);
                            const jc = ensureOrderedChild(pPr, 'jc', PPR_ORDER);
                            setWAttr(jc, 'val', 'left');
                            removeDirectChild(pPr, 'ind');
                            const sp = ensureOrderedChild(pPr, 'spacing', PPR_ORDER);
                            setWAttr(sp, 'before', '200');
                            setWAttr(sp, 'after', '80');
                            setWAttr(sp, 'line', lineVal);
                            setWAttr(sp, 'lineRule', 'auto');
                            runs.forEach(r => applyRunStyle(r, bodySz, true));
                            stats.subHeadingsFixed++;
                        } else if (isCaption) {
                            if (optImages) {
                                const jc = ensureOrderedChild(pPr, 'jc', PPR_ORDER);
                                setWAttr(jc, 'val', 'center');
                                removeDirectChild(pPr, 'ind');
                                if (/^Tabel\s+\d/i.test(text)) ensureOrderedChild(pPr, 'keepNext', PPR_ORDER);
                            }
                            const sp = ensureOrderedChild(pPr, 'spacing', PPR_ORDER);
                            setWAttr(sp, 'before', '80');
                            setWAttr(sp, 'after', '100');
                            setWAttr(sp, 'line', '240');
                            setWAttr(sp, 'lineRule', 'auto');
                            runs.forEach(r => applyRunStyle(r, Math.max(20, bodySz - 2), null));
                        } else if (inBibliography) {
                            const jc = ensureOrderedChild(pPr, 'jc', PPR_ORDER);
                            setWAttr(jc, 'val', optIndent ? 'both' : 'left');
                            if (optIndent) {
                                ensureOrderedChild(pPr, 'widowControl', PPR_ORDER);
                                removeDirectChild(pPr, 'ind');
                                const ind = ensureOrderedChild(pPr, 'ind', PPR_ORDER);
                                setWAttr(ind, 'left', '709');
                                setWAttr(ind, 'hanging', '709');
                            }
                            const sp = ensureOrderedChild(pPr, 'spacing', PPR_ORDER);
                            setWAttr(sp, 'before', '0');
                            setWAttr(sp, 'after', '120');
                            setWAttr(sp, 'line', '240');
                            setWAttr(sp, 'lineRule', 'auto');
                            runs.forEach(r => applyRunStyle(r, bodySz, null));
                            stats.bibEntries++;
                        } else if (inAbstract && optAbstract) {
                            ensureOrderedChild(pPr, 'widowControl', PPR_ORDER);
                            const jc = ensureOrderedChild(pPr, 'jc', PPR_ORDER);
                            setWAttr(jc, 'val', 'both');
                            removeDirectChild(pPr, 'ind');
                            const sp = ensureOrderedChild(pPr, 'spacing', PPR_ORDER);
                            setWAttr(sp, 'before', '0');
                            setWAttr(sp, 'after', '120');
                            setWAttr(sp, 'line', '240'); // Spasi 1.0 standar abstrak Indonesia
                            setWAttr(sp, 'lineRule', 'auto');
                            runs.forEach(r => applyRunStyle(r, bodySz, null));
                            stats.abstractFixed++;
                        } else if (isListItem) {
                            if (optIndent) ensureOrderedChild(pPr, 'widowControl', PPR_ORDER);
                            const jc = ensureOrderedChild(pPr, 'jc', PPR_ORDER);
                            setWAttr(jc, 'val', optIndent ? 'both' : 'left');
                            const sp = ensureOrderedChild(pPr, 'spacing', PPR_ORDER);
                            setWAttr(sp, 'before', '0');
                            setWAttr(sp, 'after', '60');
                            setWAttr(sp, 'line', lineVal);
                            setWAttr(sp, 'lineRule', 'auto');
                            runs.forEach(r => applyRunStyle(r, bodySz, null));
                        } else {
                            const existingJc = getDirectChild(pPr, 'jc')?.getAttributeNS(W_NS, 'val');
                            const keepCenter = existingJc === 'center' && text.length < 90;
                            if (optIndent) ensureOrderedChild(pPr, 'widowControl', PPR_ORDER);
                            const jc = ensureOrderedChild(pPr, 'jc', PPR_ORDER);
                            setWAttr(jc, 'val', keepCenter ? 'center' : (optIndent ? 'both' : (existingJc || 'left')));
                            if (optIndent && !keepCenter && text.length > 65) {
                                removeDirectChild(pPr, 'ind');
                                const ind = ensureOrderedChild(pPr, 'ind', PPR_ORDER);
                                setWAttr(ind, 'firstLine', '709');
                            }
                            const sp = ensureOrderedChild(pPr, 'spacing', PPR_ORDER);
                            setWAttr(sp, 'before', '0');
                            setWAttr(sp, 'after', '0');
                            setWAttr(sp, 'line', lineVal);
                            setWAttr(sp, 'lineRule', 'auto');
                            runs.forEach(r => applyRunStyle(r, bodySz, null));
                        }

                        seenRealContent = true;
                        if (!isMajorHeading) {
                            prevWasBabHeading = false;
                            prevWasPageBreak = false;
                        }
                        stats.paragraphsTidied++;
                    } else if (node.localName === 'tbl') {
                        seenRealContent = true;
                        consecutiveEmpty = 0;
                        if (optImages) constrainNodeImages(node);

                        if (!optTables) continue;

                        // Deteksi tabel tanda tangan / identitas / cover tanpa garis agar TIDAK dirusak menjadi kotak bergaris!
                        const tblText = Array.from(node.getElementsByTagNameNS(W_NS, 't')).map(x => x.textContent || '').join(' ');
                        const tblPr = ensureChild(node, 'tblPr', true);
                        const origBdr = getDirectChild(tblPr, 'tblBorders');
                        const isOriginallyBorderless = origBdr && Array.from(origBdr.childNodes).filter(n => n.nodeType === 1).every(b => {
                            const v = (b.getAttributeNS(W_NS, 'val') || '').toLowerCase();
                            return v === 'none' || v === 'nil';
                        });
                        const isSignatureLike = isOriginallyBorderless || (optProtectCover && inCoverZone) || /(?:Pembimbing\s*[I12]|Mengetahui|Disetujui|Penguji|Ketua\s+Program\s+Studi|NIM\s*:|NPM\s*:)/i.test(tblText);

                        if (isSignatureLike) {
                            // Hanya seragamkan font tabel tanda tangan tanpa menambah garis border!
                            Array.from(node.getElementsByTagNameNS(W_NS, 'r')).forEach(r => {
                                const rPr = ensureChild(r, 'rPr', true);
                                const rFonts = ensureOrderedChild(rPr, 'rFonts', RPR_ORDER);
                                setWAttr(rFonts, 'ascii', fontName);
                                setWAttr(rFonts, 'hAnsi', fontName);
                                setWAttr(rFonts, 'cs', fontName);
                            });
                            stats.tablesProtected++;
                            continue;
                        }

                        // Skalakan tblGrid & cell widths agar tabel tidak pernah keluar batas margin kertas
                        const tblW = ensureOrderedChild(tblPr, 'tblW', TBLPR_ORDER);
                        setWAttr(tblW, 'w', String(contentWTwip));
                        setWAttr(tblW, 'type', 'dxa');

                        const tblGrid = getDirectChild(node, 'tblGrid');
                        if (tblGrid) {
                            const cols = Array.from(tblGrid.childNodes).filter(n => n.nodeType === 1 && n.localName === 'gridCol');
                            const totalColW = cols.reduce((acc, c) => acc + (parseInt(c.getAttributeNS(W_NS, 'w') || '0', 10) || 0), 0);
                            if (totalColW > contentWTwip && cols.length > 0) {
                                const ratio = contentWTwip / totalColW;
                                cols.forEach(c => {
                                    const cw = parseInt(c.getAttributeNS(W_NS, 'w') || '1000', 10);
                                    setWAttr(c, 'w', Math.max(400, Math.round(cw * ratio)));
                                });
                            }
                        }

                        removeDirectChild(tblPr, 'tblBorders');
                        const borders = ensureOrderedChild(tblPr, 'tblBorders', TBLPR_ORDER);
                        ['top', 'left', 'bottom', 'right', 'insideH', 'insideV'].forEach(side => {
                            const b = mkW(side);
                            setWAttr(b, 'val', 'single');
                            setWAttr(b, 'sz', '4');
                            setWAttr(b, 'space', '0');
                            setWAttr(b, 'color', '334155');
                            borders.appendChild(b);
                        });

                        removeDirectChild(tblPr, 'tblCellMar');
                        const cellMar = ensureOrderedChild(tblPr, 'tblCellMar', TBLPR_ORDER);
                        [['top', 65], ['bottom', 65], ['left', 90], ['right', 90]].forEach(([s, v]) => {
                            const m = mkW(s);
                            setWAttr(m, 'w', v);
                            setWAttr(m, 'type', 'dxa');
                            cellMar.appendChild(m);
                        });

                        const rows = Array.from(node.childNodes).filter(n => n.nodeType === 1 && n.localName === 'tr');
                        rows.forEach((tr, rIdx) => {
                            const trPr = ensureChild(tr, 'trPr', true);
                            ensureChild(trPr, 'cantSplit');
                            if (rIdx === 0) ensureChild(trPr, 'tblHeader');
                            const cells = Array.from(tr.childNodes).filter(n => n.nodeType === 1 && n.localName === 'tc');
                            const rowSumW = cells.reduce((acc, tc) => {
                                const tcPr = getDirectChild(tc, 'tcPr');
                                const tcW = tcPr && getDirectChild(tcPr, 'tcW');
                                const tp = tcW ? (tcW.getAttributeNS(W_NS, 'type') || 'dxa') : 'dxa';
                                return acc + (tp === 'dxa' ? (parseInt(tcW?.getAttributeNS(W_NS, 'w') || '0', 10) || 0) : 0);
                            }, 0);
                            if (rowSumW > contentWTwip) {
                                const ratio = contentWTwip / rowSumW;
                                cells.forEach(tc => {
                                    const tcPr = getDirectChild(tc, 'tcPr');
                                    const tcW = tcPr && getDirectChild(tcPr, 'tcW');
                                    if (tcW && (tcW.getAttributeNS(W_NS, 'type') || 'dxa') === 'dxa') {
                                        const w = parseInt(tcW.getAttributeNS(W_NS, 'w') || '0', 10);
                                        if (w > 0) setWAttr(tcW, 'w', Math.max(400, Math.round(w * ratio)));
                                    }
                                });
                            }
                            Array.from(tr.getElementsByTagNameNS(W_NS, 'p')).forEach(tp => {
                                cleanRunSpaces(tp);
                                const tpPr = ensureChild(tp, 'pPr', true);
                                const sp = ensureOrderedChild(tpPr, 'spacing', PPR_ORDER);
                                setWAttr(sp, 'before', '40');
                                setWAttr(sp, 'after', '40');
                                setWAttr(sp, 'line', '240');
                                setWAttr(sp, 'lineRule', 'auto');
                                Array.from(tp.getElementsByTagNameNS(W_NS, 'r')).forEach(r => {
                                    applyRunStyle(r, Math.max(20, bodySz - 3), rIdx === 0 ? true : null);
                                });
                            });
                        });
                        stats.tablesFixed++;
                    }
                }

                // 2. Penomoran Halaman Skripsi Otomatis (ECMA-376 OOXML Spec Compliant)
                if (optPageNum) {
                    setProc('tidy', true, 'Menata Section Break & Penomoran Halaman (Cover, Romawi, BAB I–V)...', 74);

                    const R_NS = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
                    const REL_NS = 'http://schemas.openxmlformats.org/package/2006/relationships';
                    const CT_NS = 'http://schemas.openxmlformats.org/package/2006/content-types';
                    const XML_NS = 'http://www.w3.org/XML/1998/namespace';
                    const SECTPR_ORDER = [
                        'headerReference', 'footerReference', 'footnotePr', 'endnotePr',
                        'type', 'pgSz', 'pgMar', 'paperSrc', 'pgBorders', 'lnNumType',
                        'pgNumType', 'cols', 'formProt', 'vAlign', 'noEndnote',
                        'titlePg', 'textDirection', 'bidi', 'rtlGutter', 'docGrid'
                    ];

                    const docRoot = xmlDoc.documentElement;
                    if (!docRoot.getAttribute('xmlns:r')) {
                        docRoot.setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:r', R_NS);
                    }

                    // Baca atau siapkan word/_rels/document.xml.rels dan [Content_Types].xml
                    const XML_DECL = '<' + '?xml version="1.0" encoding="UTF-8" standalone="yes"?' + '>';
                    const relsPath = 'word/_rels/document.xml.rels';
                    let relsStr = await zip.file(relsPath)?.async('string');
                    if (!relsStr) {
                        relsStr = XML_DECL + '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"></Relationships>';
                    }
                    const relsDoc = parser.parseFromString(relsStr, 'application/xml');
                    if (relsDoc.getElementsByTagName('parsererror').length) {
                        throw new Error('File relasi dokumen Word (.rels) rusak.');
                    }

                    const ctStr = await zip.file('[Content_Types].xml')?.async('string');
                    if (!ctStr) throw new Error('[Content_Types].xml tidak ditemukan pada file Word.');
                    const ctDoc = parser.parseFromString(ctStr, 'application/xml');
                    if (ctDoc.getElementsByTagName('parsererror').length) {
                        throw new Error('Struktur [Content_Types].xml dokumen Word rusak.');
                    }

                    const usedRelIds = new Set(
                        Array.from(relsDoc.getElementsByTagName('Relationship')).map(r => r.getAttribute('Id') || '')
                    );
                    let nextRelNum = 901;
                    const allocRelId = () => {
                        while (usedRelIds.has('rId' + nextRelNum)) nextRelNum++;
                        const id = 'rId' + nextRelNum;
                        usedRelIds.add(id);
                        return id;
                    };

                    const allocPartName = (baseName) => {
                        let candidate = baseName + '.xml';
                        let n = 1;
                        while (zip.file('word/' + candidate)) {
                            candidate = baseName + '_' + n + '.xml';
                            n++;
                        }
                        return candidate;
                    };

                    const serializerInner = new XMLSerializer();
                    const registerHeaderFooterPart = (baseName, isHeader, xmlContent) => {
                        const fileName = allocPartName(baseName);
                        zip.file('word/' + fileName, xmlContent);

                        const relId = allocRelId();
                        const relEl = relsDoc.createElementNS(REL_NS, 'Relationship');
                        relEl.setAttribute('Id', relId);
                        relEl.setAttribute('Type', isHeader
                            ? 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/header'
                            : 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/footer');
                        relEl.setAttribute('Target', fileName);
                        relsDoc.documentElement.appendChild(relEl);

                        const partName = '/word/' + fileName;
                        const contentType = isHeader
                            ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.header+xml'
                            : 'application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml';
                        const hasOverride = Array.from(ctDoc.getElementsByTagName('Override'))
                            .some(o => (o.getAttribute('PartName') || '').toLowerCase() === partName.toLowerCase());
                        if (!hasOverride) {
                            const ov = ctDoc.createElementNS(CT_NS, 'Override');
                            ov.setAttribute('PartName', partName);
                            ov.setAttribute('ContentType', contentType);
                            ctDoc.documentElement.appendChild(ov);
                        }
                        return relId;
                    };

                    const escapeXmlAttr = (s) => String(s || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                    const numSz = Math.max(22, bodySz - 2);
                    const safeFont = escapeXmlAttr(fontName);
                    const buildEmptyPartXml = (rootTag) => XML_DECL
                        + `<w:${rootTag} xmlns:w="${W_NS}"><w:p><w:pPr><w:spacing w:before="0" w:after="0"/></w:pPr></w:p></w:${rootTag}>`;
                    const buildPageFieldPartXml = (rootTag, alignVal) => {
                        const rPrXml = `<w:rPr><w:rFonts w:ascii="${safeFont}" w:hAnsi="${safeFont}" w:cs="${safeFont}" w:eastAsia="${safeFont}"/><w:color w:val="000000"/><w:sz w:val="${numSz}"/><w:szCs w:val="${numSz}"/></w:rPr>`;
                        return XML_DECL
                            + `<w:${rootTag} xmlns:w="${W_NS}">`
                            + `<w:p><w:pPr><w:spacing w:before="0" w:after="0"/><w:jc w:val="${alignVal}"/></w:pPr>`
                            + `<w:r>${rPrXml}<w:fldChar w:fldCharType="begin"/></w:r>`
                            + `<w:r>${rPrXml}<w:instrText xml:space="preserve"> PAGE </w:instrText></w:r>`
                            + `<w:r>${rPrXml}<w:fldChar w:fldCharType="separate"/></w:r>`
                            + `<w:r>${rPrXml}<w:t>1</w:t></w:r>`
                            + `<w:r>${rPrXml}<w:fldChar w:fldCharType="end"/></w:r>`
                            + `</w:p></w:${rootTag}>`;
                    };

                    const rIdHdrEmpty = registerHeaderFooterPart('sl_hdr_empty', true, buildEmptyPartXml('hdr'));
                    const rIdFtrEmpty = registerHeaderFooterPart('sl_ftr_empty', false, buildEmptyPartXml('ftr'));
                    const rIdFtrCenter = registerHeaderFooterPart('sl_ftr_center', false, buildPageFieldPartXml('ftr', 'center'));
                    const rIdHdrRight = (pageNumLayout === 'bab_bottom_right' || pageNumLayout === 'all_top_right')
                        ? registerHeaderFooterPart('sl_hdr_right', true, buildPageFieldPartXml('hdr', 'right'))
                        : null;

                    // Ambil template sectPr akhir dokumen (sebelum membersihkan section break portrait lama)
                    let bodyFinalSectPr = Array.from(body.childNodes).reverse().find(n => n.nodeType === 1 && n.localName === 'sectPr');
                    if (!bodyFinalSectPr) {
                        bodyFinalSectPr = mkW('sectPr');
                        const pgSz = mkW('pgSz');
                        setWAttr(pgSz, 'w', '11906');
                        setWAttr(pgSz, 'h', '16838');
                        const pgMar = mkW('pgMar');
                        setWAttr(pgMar, 'top', mar.top);
                        setWAttr(pgMar, 'left', mar.left);
                        setWAttr(pgMar, 'bottom', mar.bottom);
                        setWAttr(pgMar, 'right', mar.right);
                        setWAttr(pgMar, 'header', '709');
                        setWAttr(pgMar, 'footer', '709');
                        bodyFinalSectPr.appendChild(pgSz);
                        bodyFinalSectPr.appendChild(pgMar);
                        body.appendChild(bodyFinalSectPr);
                    }

                    const createPortraitSectTemplate = () => {
                        const clone = bodyFinalSectPr.cloneNode(true);
                        ['headerReference', 'footerReference', 'type', 'pgNumType', 'titlePg'].forEach(tag => removeDirectChild(clone, tag));
                        const pgSz = ensureOrderedChild(clone, 'pgSz', SECTPR_ORDER);
                        if ((pgSz.getAttributeNS(W_NS, 'orient') || '').toLowerCase() === 'landscape') {
                            pgSz.removeAttributeNS(W_NS, 'orient');
                            pgSz.removeAttribute('w:orient');
                            setWAttr(pgSz, 'w', '11906');
                            setWAttr(pgSz, 'h', '16838');
                        }
                        const pgMar = ensureOrderedChild(clone, 'pgMar', SECTPR_ORDER);
                        if (!pgMar.getAttributeNS(W_NS, 'header') || pgMar.getAttributeNS(W_NS, 'header') === '0') setWAttr(pgMar, 'header', '709');
                        if (!pgMar.getAttributeNS(W_NS, 'footer') || pgMar.getAttributeNS(W_NS, 'footer') === '0') setWAttr(pgMar, 'footer', '709');
                        return clone;
                    };
                    const sectTemplate = createPortraitSectTemplate();

                    // Kumpulkan node konten saat ini (tidak termasuk body > sectPr akhir) & deteksi section landscape eksisting
                    let workNodes = Array.from(body.childNodes).filter(n => n.nodeType === 1 && n.localName !== 'sectPr');
                    const isLandscapeSectPr = (sp) => {
                        if (!sp) return false;
                        const pgSz = getDirectChild(sp, 'pgSz');
                        return !!pgSz && (pgSz.getAttributeNS(W_NS, 'orient') || '').toLowerCase() === 'landscape';
                    };

                    // Bersihkan section break portrait lama yang mungkin berantakan di dalam paragraf
                    for (let wi = 0; wi < workNodes.length; wi++) {
                        const wn = workNodes[wi];
                        if (wn.localName !== 'p') continue;
                        const wpPr = getDirectChild(wn, 'pPr');
                        const oldSp = wpPr && getDirectChild(wpPr, 'sectPr');
                        if (!oldSp) continue;

                        // Pertahankan jika section tersebut atau section berikutnya adalah Landscape (misal Lampiran lebar)
                        let nextSp = bodyFinalSectPr;
                        for (let ni = wi + 1; ni < workNodes.length; ni++) {
                            if (workNodes[ni].localName === 'p') {
                                const npPr = getDirectChild(workNodes[ni], 'pPr');
                                const candSp = npPr && getDirectChild(npPr, 'sectPr');
                                if (candSp) { nextSp = candSp; break; }
                            }
                        }
                        if (isLandscapeSectPr(oldSp) || isLandscapeSectPr(nextSp)) {
                            continue;
                        }
                        wpPr.removeChild(oldSp);
                        const hasText = Array.from(wn.getElementsByTagNameNS(W_NS, 't')).some(t => (t.textContent || '').trim() !== '');
                        const hasMedia = wn.getElementsByTagNameNS('*', 'drawing').length > 0
                            || wn.getElementsByTagNameNS('*', 'pict').length > 0
                            || wn.getElementsByTagNameNS('*', 'object').length > 0;
                        if (!hasText && !hasMedia) {
                            body.removeChild(wn);
                        }
                    }

                    workNodes = Array.from(body.childNodes).filter(n => n.nodeType === 1 && n.localName !== 'sectPr');

                    const getParaInfoForPageNum = (el) => {
                        if (!el) return { type: 'other', text: '' };
                        if (el.localName === 'sdt') {
                            const sdtTxt = Array.from(el.getElementsByTagNameNS(W_NS, 't')).map(x => x.textContent || '').join(' ').replace(/\s+/g, ' ').trim();
                            if (/DAFTAR\s+ISI|TABLE\s+OF\s+CONTENTS/i.test(sdtTxt)) return { type: 'front', text: 'DAFTAR ISI' };
                            return { type: 'other', text: '' };
                        }
                        if (el.localName !== 'p') return { type: 'other', text: '' };

                        const pPr = getDirectChild(el, 'pPr');
                        const stVal = (pPr && getDirectChild(pPr, 'pStyle')?.getAttributeNS(W_NS, 'val')) || '';
                        if (/^TOC/i.test(stVal)) return { type: 'other', text: '' };
                        const hasTab = el.getElementsByTagNameNS(W_NS, 'tab').length > 0;
                        if (hasTab) return { type: 'other', text: '' };
                        const rawT = Array.from(el.getElementsByTagNameNS(W_NS, 't')).map(x => x.textContent || '').join('');
                        const txt = rawT.replace(/\s+/g, ' ').trim();
                        if (!txt || txt.length > 80 || /\.{3,}/.test(txt)) return { type: 'other', text: txt };

                        if (/^(?:LEMBAR\s+(?:PERSETUJUAN|PENGESAHAN|MOTTO|PERSEMBAHAN)|HALAMAN\s+(?:PERSETUJUAN|PENGESAHAN|MOTTO|PERSEMBAHAN)|SURAT\s+PERNYATAAN(?:\s+KEASLIAN)?|PERNYATAAN\s+KEASLIAN|KATA\s+PENGANTAR|UCAPAN\s+TERIMA\s+KASIH|ABSTRAK|ABSTRACT|DAFTAR\s+(?:ISI|TABEL|GAMBAR|LAMPIRAN)|RINGKASAN|PRAKATA)(?:\s+PROPOSAL|\s+SKRIPSI)?(?:\s*[:\-–.]\s*.*)?$/i.test(txt)) {
                            return { type: 'front', text: txt };
                        }
                        if (/^BAB\s+(?:[IVXLCDM]+|\d+)(?:\s*[:\-–.]?\s*[A-ZÀ-Ÿ0-9\s,&\-–]{0,60})?$/i.test(txt)) {
                            return { type: 'bab', text: txt };
                        }
                        if (/^(?:DAFTAR\s+PUSTAKA|REFERENSI|BIBLIOGRAFI)$/i.test(txt)) {
                            return { type: 'bib', text: txt };
                        }
                        if (/^LAMPIRAN(?:\s+[A-Z0-9.\-]+)?(?:\s*[:\-–.].*)?$/i.test(txt)) {
                            return { type: 'app', text: txt };
                        }
                        return { type: 'other', text: txt };
                    };

                    const hasVisibleNodeContent = (el) => {
                        if (!el) return false;
                        if (el.localName === 'tbl' || el.localName === 'sdt') return true;
                        const txt = Array.from(el.getElementsByTagNameNS(W_NS, 't')).map(x => x.textContent || '').join('').trim();
                        if (txt !== '') return true;
                        return el.getElementsByTagNameNS('*', 'drawing').length > 0
                            || el.getElementsByTagNameNS('*', 'pict').length > 0
                            || el.getElementsByTagNameNS('*', 'object').length > 0;
                    };

                    let firstFrontIdx = -1;
                    let firstBabIdx = -1;
                    const bodyBreakTargets = []; // [{ idx, role }]
                    let seenLampiranBreak = false;

                    for (let wi = 0; wi < workNodes.length; wi++) {
                        const info = getParaInfoForPageNum(workNodes[wi]);
                        if (info.type === 'front' && firstFrontIdx === -1 && firstBabIdx === -1) {
                            firstFrontIdx = wi;
                        } else if ((info.type === 'bab' || info.type === 'bib' || info.type === 'app') && firstBabIdx === -1) {
                            firstBabIdx = wi;
                            bodyBreakTargets.push({ idx: wi, kind: info.type });
                            if (info.type === 'app') seenLampiranBreak = true;
                        } else if (firstBabIdx !== -1 && pageNumLayout === 'bab_bottom_right') {
                            if (info.type === 'bab' || info.type === 'bib') {
                                bodyBreakTargets.push({ idx: wi, kind: info.type });
                            } else if (info.type === 'app' && !seenLampiranBreak) {
                                bodyBreakTargets.push({ idx: wi, kind: info.type });
                                seenLampiranBreak = true;
                            }
                        }
                    }

                    // Bangun daftar batas section: [ { breakBeforeIdx, role } ]
                    // role bagian SEBELUM breakBeforeIdx ditentukan oleh posisi berikutnya
                    const boundaries = [];
                    if (firstFrontIdx >= 0 && (firstBabIdx === -1 || firstFrontIdx < firstBabIdx)) {
                        const coverHasContent = workNodes.slice(0, firstFrontIdx).some(hasVisibleNodeContent);
                        if (coverHasContent) {
                            boundaries.push({ breakBeforeIdx: firstFrontIdx, prevRole: 'cover', nextRole: 'front' });
                        }
                    }
                    if (firstBabIdx >= 0) {
                        const prevOfFirstBab = (firstFrontIdx >= 0 && firstFrontIdx < firstBabIdx)
                            ? 'front'
                            : (workNodes.slice(0, firstBabIdx).some(hasVisibleNodeContent) ? 'cover' : null);
                        if (prevOfFirstBab) {
                            boundaries.push({ breakBeforeIdx: firstBabIdx, prevRole: prevOfFirstBab, nextRole: 'body_first' });
                        }
                        for (let bi = 1; bi < bodyBreakTargets.length; bi++) {
                            boundaries.push({ breakBeforeIdx: bodyBreakTargets[bi].idx, prevRole: 'body', nextRole: 'body_next' });
                        }
                    }

                    const configureSectPr = (sectPr, role, isFirstBody, withNextPageType) => {
                        ['headerReference', 'footerReference', 'type', 'pgNumType', 'titlePg'].forEach(tag => removeDirectChild(sectPr, tag));

                        const addHeaderRef = (typeVal, relId) => {
                            const h = mkW('headerReference');
                            setWAttr(h, 'type', typeVal);
                            h.setAttributeNS(R_NS, 'r:id', relId);
                            const nxt = Array.from(sectPr.childNodes).find(n => n.nodeType === 1 && SECTPR_ORDER.indexOf(n.localName) > SECTPR_ORDER.indexOf('headerReference'));
                            if (nxt) sectPr.insertBefore(h, nxt); else sectPr.appendChild(h);
                        };
                        const addFooterRef = (typeVal, relId) => {
                            const f = mkW('footerReference');
                            setWAttr(f, 'type', typeVal);
                            f.setAttributeNS(R_NS, 'r:id', relId);
                            const nxt = Array.from(sectPr.childNodes).find(n => n.nodeType === 1 && SECTPR_ORDER.indexOf(n.localName) > SECTPR_ORDER.indexOf('footerReference'));
                            if (nxt) sectPr.insertBefore(f, nxt); else sectPr.appendChild(f);
                        };

                        if (withNextPageType) {
                            const tp = ensureOrderedChild(sectPr, 'type', SECTPR_ORDER);
                            setWAttr(tp, 'val', 'nextPage');
                        }

                        if (role === 'cover') {
                            addHeaderRef('default', rIdHdrEmpty);
                            addFooterRef('default', rIdFtrEmpty);
                        } else if (role === 'front') {
                            addHeaderRef('default', rIdHdrEmpty);
                            addFooterRef('default', rIdFtrCenter);
                            const pgNum = ensureOrderedChild(sectPr, 'pgNumType', SECTPR_ORDER);
                            setWAttr(pgNum, 'fmt', 'lowerRoman');
                            const hasCoverBefore = boundaries.some(b => b.prevRole === 'cover');
                            setWAttr(pgNum, 'start', String(hasCoverBefore ? romanStartVal : 1));
                        } else {
                            // Bagian Isi (BAB I-V / Daftar Pustaka)
                            if (pageNumLayout === 'bab_bottom_right') {
                                addHeaderRef('default', rIdHdrRight);
                                addHeaderRef('first', rIdHdrEmpty);
                                addFooterRef('default', rIdFtrEmpty);
                                addFooterRef('first', rIdFtrCenter);
                                ensureOrderedChild(sectPr, 'titlePg', SECTPR_ORDER);
                            } else if (pageNumLayout === 'all_top_right') {
                                addHeaderRef('default', rIdHdrRight);
                                addFooterRef('default', rIdFtrEmpty);
                            } else {
                                addHeaderRef('default', rIdHdrEmpty);
                                addFooterRef('default', rIdFtrCenter);
                            }
                            const pgNum = ensureOrderedChild(sectPr, 'pgNumType', SECTPR_ORDER);
                            setWAttr(pgNum, 'fmt', 'decimal');
                            if (isFirstBody) {
                                setWAttr(pgNum, 'start', '1');
                            } else {
                                pgNum.removeAttributeNS(W_NS, 'start');
                                pgNum.removeAttribute('w:start');
                            }
                        }

                        const pgMar = ensureOrderedChild(sectPr, 'pgMar', SECTPR_ORDER);
                        if (!pgMar.getAttributeNS(W_NS, 'header') || pgMar.getAttributeNS(W_NS, 'header') === '0') setWAttr(pgMar, 'header', '709');
                        if (!pgMar.getAttributeNS(W_NS, 'footer') || pgMar.getAttributeNS(W_NS, 'footer') === '0') setWAttr(pgMar, 'footer', '709');
                    };

                    // Pastikan urutan child di <w:pPr> meletakkan <w:sectPr> selalu di paling akhir
                    const attachSectPrBeforeNode = (targetNode, sectPr) => {
                        let prev = targetNode.previousElementSibling;
                        // Jika tepat sebelum heading terdapat paragraf kosong yang hanya berisi page-break, buang br/paragraf kosong tsb agar tak terjadi halaman kosong ganda
                        while (prev && prev.localName === 'p') {
                            const hasBreak = Array.from(prev.getElementsByTagNameNS(W_NS, 'br'))
                                .some(br => (br.getAttributeNS(W_NS, 'type') || '').toLowerCase() === 'page');
                            const hasTxt = Array.from(prev.getElementsByTagNameNS(W_NS, 't')).some(t => (t.textContent || '').trim() !== '');
                            const hasPic = prev.getElementsByTagNameNS('*', 'drawing').length > 0
                                || prev.getElementsByTagNameNS('*', 'pict').length > 0
                                || prev.getElementsByTagNameNS('*', 'object').length > 0;
                            const hasOldSp = !!(getDirectChild(prev, 'pPr') && getDirectChild(getDirectChild(prev, 'pPr'), 'sectPr'));
                            if (hasBreak && !hasTxt && !hasPic && !hasOldSp) {
                                const toDel = prev;
                                prev = prev.previousElementSibling;
                                body.removeChild(toDel);
                            } else {
                                break;
                            }
                        }

                        if (prev && prev.localName === 'p') {
                            const prevPPr = ensureChild(prev, 'pPr', true);
                            Array.from(prev.getElementsByTagNameNS(W_NS, 'br')).forEach(br => {
                                if ((br.getAttributeNS(W_NS, 'type') || '').toLowerCase() === 'page') {
                                    br.parentNode?.removeChild(br);
                                }
                            });
                            if (!getDirectChild(prevPPr, 'sectPr')) {
                                prevPPr.appendChild(sectPr);
                            }
                        } else {
                            const holderP = mkW('p');
                            const holderPPr = mkW('pPr');
                            const sp = mkW('spacing');
                            setWAttr(sp, 'before', '0');
                            setWAttr(sp, 'after', '0');
                            setWAttr(sp, 'line', '20');
                            setWAttr(sp, 'lineRule', 'exact');
                            const hRPr = mkW('rPr');
                            const hSz = mkW('sz');
                            setWAttr(hSz, 'val', '2');
                            hRPr.appendChild(hSz);
                            holderPPr.appendChild(sp);
                            holderPPr.appendChild(hRPr);
                            holderPPr.appendChild(sectPr);
                            holderP.appendChild(holderPPr);
                            body.insertBefore(holderP, targetNode);
                        }
                        // Hilangkan pageBreakBefore di heading awal section baru karena nextPage section break sudah pindah halaman
                        if (targetNode.localName === 'p') {
                            const tPPr = getDirectChild(targetNode, 'pPr');
                            if (tPPr) removeDirectChild(tPPr, 'pageBreakBefore');
                        }
                    };

                    let finalRole = 'body';
                    if (firstBabIdx === -1 && firstFrontIdx >= 0) finalRole = 'front';
                    else if (firstBabIdx === -1 && firstFrontIdx === -1) finalRole = 'body';

                    let bodySecStarted = false;
                    let hasPrevSection = false;
                    for (let bi = 0; bi < boundaries.length; bi++) {
                        const b = boundaries[bi];
                        const targetEl = workNodes[b.breakBeforeIdx];
                        if (!targetEl) continue;

                        // Jika sudah ada sectPr landscape tepat sebelum targetEl, jangan ditimpa; cukup lanjutkan numbering
                        const prevEl = targetEl.previousElementSibling;
                        const existingLandscape = prevEl && prevEl.localName === 'p' && getDirectChild(prevEl, 'pPr') && getDirectChild(getDirectChild(prevEl, 'pPr'), 'sectPr');
                        if (existingLandscape && isLandscapeSectPr(existingLandscape)) {
                            const isFirstB = (b.prevRole === 'body' && !bodySecStarted);
                            if (b.prevRole === 'body') bodySecStarted = true;
                            configureSectPr(existingLandscape, b.prevRole, isFirstB, hasPrevSection);
                            hasPrevSection = true;
                            stats.pageSectionsFixed++;
                            continue;
                        }

                        const newSp = sectTemplate.cloneNode(true);
                        const isFirstB = (b.prevRole === 'body' && !bodySecStarted);
                        if (b.prevRole === 'body') bodySecStarted = true;
                        configureSectPr(newSp, b.prevRole, isFirstB, hasPrevSection);
                        attachSectPrBeforeNode(targetEl, newSp);
                        hasPrevSection = true;
                        stats.pageSectionsFixed++;
                    }

                    // Konfigurasikan section terakhir (trailing body > sectPr) & setiap landscape sectPr tersisa
                    const isFinalFirstBody = (finalRole === 'body' && !bodySecStarted);
                    configureSectPr(bodyFinalSectPr, finalRole, isFinalFirstBody, hasPrevSection);
                    stats.pageSectionsFixed++;

                    // Jika masih ada section break landscape internal sebelum akhir dokumen, pastikan penomorannya tetap Arab berlanjut
                    Array.from(body.childNodes).forEach(ch => {
                        if (ch.nodeType === 1 && ch.localName === 'p') {
                            const sp = getDirectChild(ch, 'pPr') && getDirectChild(getDirectChild(ch, 'pPr'), 'sectPr');
                            if (sp && isLandscapeSectPr(sp)) {
                                configureSectPr(sp, 'body', false, true);
                            }
                        }
                    });

                    // Jika dokumen sebelumnya memakai "Different Odd & Even Pages", matikan agar halaman genap (2, 4, dst.) tidak hilang nomornya
                    const settingsStr = await zip.file('word/settings.xml')?.async('string');
                    if (settingsStr) {
                        const settingsDoc = parser.parseFromString(settingsStr, 'application/xml');
                        if (!settingsDoc.getElementsByTagName('parsererror').length) {
                            const oddEven = Array.from(settingsDoc.getElementsByTagNameNS(W_NS, 'evenAndOddHeaders'));
                            if (oddEven.length) {
                                oddEven.forEach(el => el.parentNode?.removeChild(el));
                                zip.file('word/settings.xml', serializerInner.serializeToString(settingsDoc));
                            }
                        }
                    }

                    zip.file(relsPath, serializerInner.serializeToString(relsDoc));
                    zip.file('[Content_Types].xml', serializerInner.serializeToString(ctDoc));
                }

                setProc('tidy', true, 'Menyimpan kembali dokumen Word rapi (.docx)...', 82);
                const serializer = new XMLSerializer();
                zip.file('word/document.xml', serializer.serializeToString(xmlDoc));

                const stylesXmlStr = await zip.file('word/styles.xml')?.async('string');
                if (stylesXmlStr && !onlyPageNum) {
                    const stylesDoc = parser.parseFromString(stylesXmlStr, 'application/xml');
                    if (!stylesDoc.getElementsByTagName('parsererror').length) {
                        Array.from(stylesDoc.getElementsByTagNameNS(W_NS, 'rFonts')).forEach(rf => {
                            rf.setAttributeNS(W_NS, 'w:ascii', fontName);
                            rf.setAttributeNS(W_NS, 'w:hAnsi', fontName);
                            rf.setAttributeNS(W_NS, 'w:cs', fontName);
                        });
                        zip.file('word/styles.xml', serializer.serializeToString(stylesDoc));
                    }
                }

                tidyDocxResultBlob = await zip.generateAsync({
                    type: 'blob',
                    mimeType: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    compression: 'DEFLATE',
                    compressionOptions: { level: 6 }
                });
                tidyDocxOutName = tidyDocxFile.name.replace(/\.docx$/i, '') + '_Rapi.docx';

                setProc('tidy', true, 'Selesai!', 100);
                setTimeout(() => setProc('tidy', false), 260);

                showResultCard(
                    'tidy',
                    `${tidyDocxOutName} (${formatBytes(tidyDocxResultBlob.size)})`,
                    '100% teks, angka, kutipan, tabel & gambar asli tidak diubah — hanya format dan tata letak yang dirapikan.'
                );
                document.getElementById('btn-download-tidy').onclick = () => triggerBlobDownload(tidyDocxResultBlob, tidyDocxOutName);

                const pills = [
                    stats.pageSectionsFixed > 0 ? `✓ ${stats.pageSectionsFixed} Section penomoran halaman ditata` : null,
                    stats.paragraphsTidied > 0 ? `✓ ${stats.paragraphsTidied} Paragraf dirapikan` : null,
                    stats.emptyRemoved > 0 ? `✓ ${stats.emptyRemoved} Baris/halaman kosong dihapus` : null,
                    stats.chaptersFixed > 0 ? `✓ ${stats.chaptersFixed} Judul BAB ditata` : null,
                    stats.subHeadingsFixed > 0 ? `✓ ${stats.subHeadingsFixed} Sub-BAB (Keep Next)` : null,
                    stats.tablesFixed > 0 ? `✓ ${stats.tablesFixed} Tabel data pas margin` : null,
                    stats.tablesProtected > 0 ? `✓ ${stats.tablesProtected} Tabel tanda tangan dilindungi` : null,
                    stats.abstractFixed > 0 ? `✓ ${stats.abstractFixed} Paragraf Abstrak (spasi 1,0)` : null,
                    stats.bibEntries > 0 ? `✓ ${stats.bibEntries} Daftar Pustaka (Hanging)` : null,
                    stats.spacesCleaned > 0 ? `✓ ${stats.spacesCleaned} Spasi ganda dibersihkan` : null,
                    stats.stylesCleaned > 0 ? `✓ ${stats.stylesCleaned} Bekas copy-paste dibersihkan` : null,
                    stats.imagesResized > 0 ? `✓ ${stats.imagesResized} Gambar disesuaikan ke margin` : null,
                    `✓ ${stats.imagesKept} Gambar 100% utuh`
                ].filter(Boolean);
                document.getElementById('result-tidy-pills').innerHTML = pills.map(p => `<span class="audit-pill">${p}</span>`).join('');
            } catch (err) {
                setProc('tidy', false);
                setStatus('status-tidy', 'error', err.message || 'Gagal merapikan dokumen Word.');
            } finally {
                btn.disabled = false;
            }
        }

        // ============================================================
        // 3. PDF <-> WORD CONVERTER (EKSTRAKSI TERURUT Y-DESC X-ASC)
        // ============================================================
        let selectedPdf2DocxFile = null;
        let selectedDocx2PdfFile = null;
        let lastPdf2DocxBlob = null, lastPdf2DocxName = '';
        let lastDocx2PdfBlob = null, lastDocx2PdfName = '';

        function setConvertMode(mode) {
            document.getElementById('seg-pdf2docx').classList.toggle('active', mode === 'pdf2docx');
            document.getElementById('seg-docx2pdf').classList.toggle('active', mode === 'docx2pdf');
            document.getElementById('box-pdf2docx').style.display = mode === 'pdf2docx' ? 'block' : 'none';
            document.getElementById('box-docx2pdf').style.display = mode === 'docx2pdf' ? 'block' : 'none';
        }

        function onSelectPdf2Docx(input) {
            if (!input.files || !input.files[0]) return;
            selectedPdf2DocxFile = input.files[0];
            hideResult('pdf2docx');
            document.getElementById('label-pdf2docx').textContent = `${selectedPdf2DocxFile.name} (${formatBytes(selectedPdf2DocxFile.size)})`;
            setStatus('status-pdf2docx', 'info', 'File PDF siap dikonversi. Klik tombol "Konversi ke Word (.docx)".');
        }

        function onSelectDocx2Pdf(input) {
            if (!input.files || !input.files[0]) return;
            selectedDocx2PdfFile = input.files[0];
            hideResult('docx2pdf');
            document.getElementById('label-docx2pdf').textContent = `${selectedDocx2PdfFile.name} (${formatBytes(selectedDocx2PdfFile.size)})`;
            setStatus('status-docx2pdf', 'info', 'File Word siap dikonversi. Klik tombol "Konversi ke PDF (.pdf)".');
        }

        async function extractPagesFromPdfFile(file, onProgress) {
            await waitForLib('pdfjsLib', 'PDF.js');
            ensurePdfJsWorker();
            const buf = new Uint8Array(await file.arrayBuffer());
            const pdf = await window.pdfjsLib.getDocument({ data: buf }).promise;
            const pages = [];

            for (let p = 1; p <= pdf.numPages; p++) {
                if (onProgress) onProgress(p, pdf.numPages);
                const page = await pdf.getPage(p);
                const viewport = page.getViewport({ scale: 1 });
                const pageH = viewport.height || 842;
                const tc = await page.getTextContent();

                const rawItems = (tc.items || [])
                    .filter(it => (it.str || '').trim() !== '')
                    .map(it => ({
                        str: it.str,
                        x: it.transform ? it.transform[4] : 0,
                        y: it.transform ? it.transform[5] : 0,
                        h: Math.abs(it.height || (it.transform ? it.transform[3] : 11) || 11),
                        bold: /bold|black|heavy|demi/i.test(it.fontName || '')
                    }))
                    .sort((a, b) => {
                        if (Math.abs(b.y - a.y) > 4.5) return b.y - a.y;
                        return a.x - b.x;
                    });

                const lines = [];
                for (const it of rawItems) {
                    const last = lines[lines.length - 1];
                    if (last && Math.abs(last.y - it.y) <= 4.5) {
                        last.parts.push(it);
                        last.maxH = Math.max(last.maxH, it.h);
                        if (it.bold) last.boldCount++;
                    } else {
                        lines.push({ y: it.y, x: it.x, maxH: it.h, boldCount: it.bold ? 1 : 0, parts: [it] });
                    }
                }

                const cleanLines = [];
                for (const ln of lines) {
                    const txt = ln.parts.map(pt => pt.str).join(' ').replace(/\s+/g, ' ').trim();
                    if (!txt) continue;
                    const isEdgePageNum = (ln.y < 68 || ln.y > pageH - 55) && /^(?:\d{1,4}|[ivxlcdm]{1,6}|halaman\s+\d+)$/i.test(txt);
                    if (isEdgePageNum) continue;
                    cleanLines.push({
                        y: ln.y,
                        x: ln.x,
                        maxH: ln.maxH,
                        isBold: ln.boldCount >= Math.ceil(ln.parts.length * 0.6),
                        text: txt
                    });
                }

                const heights = cleanLines.map(l => l.maxH).sort((a, b) => a - b);
                const medianH = heights.length ? heights[Math.floor(heights.length / 2)] : 11;

                const blocks = [];
                let paraBuf = '';
                let paraType = 'paragraph';
                let prevY = null;

                const flushPara = () => {
                    const t = paraBuf.replace(/\s+/g, ' ').trim();
                    if (t) blocks.push({ type: paraType, text: t });
                    paraBuf = '';
                    paraType = 'paragraph';
                };

                for (const ln of cleanLines) {
                    const gap = prevY !== null ? Math.abs(prevY - ln.y) : 0;
                    const isMainHead = /^(?:BAB\s+[IVX0-9]+|DAFTAR\s+[A-Z]+|KATA\s+PENGANTAR|ABSTRAK|ABSTRACT|LAMPIRAN)\b/i.test(ln.text)
                        || (ln.maxH >= medianH * 1.28 && ln.text.length <= 90);
                    const isSubHead = !isMainHead && (
                        /^(?:\d+\.\d+(?:\.\d+)?\.?\s+|[A-Z]\.\s+)/.test(ln.text) && ln.text.length <= 110
                        || (ln.isBold && ln.maxH >= medianH * 1.05 && ln.text.length <= 80 && !/[.,;]$/.test(ln.text))
                    );
                    const isList = !isMainHead && !isSubHead && /^(?:\d+[\.\)]\s+|[•\-–]\s+)/.test(ln.text);

                    if (isMainHead || isSubHead) {
                        flushPara();
                        blocks.push({ type: isMainHead ? 'heading' : 'subheading', text: ln.text });
                    } else if (isList) {
                        flushPara();
                        paraType = 'list';
                        paraBuf = ln.text;
                    } else if (paraBuf && gap > medianH * 1.9) {
                        flushPara();
                        paraType = 'paragraph';
                        paraBuf = ln.text;
                    } else if (paraBuf) {
                        if (/[A-Za-z]-$/.test(paraBuf) && /^[a-z]/.test(ln.text)) {
                            paraBuf = paraBuf.slice(0, -1) + ln.text;
                        } else {
                            paraBuf += ' ' + ln.text;
                        }
                    } else {
                        paraBuf = ln.text;
                    }
                    prevY = ln.y;
                }
                flushPara();
                pages.push({ pageNum: p, blocks, paragraphs: blocks.map(b => b.text) });
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
            hideResult('pdf2docx');
            setStatus('status-pdf2docx', '', '');
            try {
                setProc('pdf2docx', true, 'Membaca teks & struktur heading PDF...', 15);
                const pages = await extractPagesFromPdfFile(selectedPdf2DocxFile, (cur, tot) => {
                    setProc('pdf2docx', true, `Mengekstrak halaman ${cur} dari ${tot}...`, 15 + (cur / tot) * 55);
                });
                const totalBlocks = pages.reduce((acc, p) => acc + p.blocks.length, 0);
                if (totalBlocks === 0) {
                    throw new Error('PDF ini tidak memiliki lapisan teks (kemungkinan hasil scan foto). Konversi hanya mendukung PDF berbasis teks.');
                }

                setProc('pdf2docx', true, `Menyusun dokumen Word (.docx) dari ${pages.length} halaman...`, 80);
                const res = await fetch('{{ route("tools.convert.pdfToDocx") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({
                        filename: selectedPdf2DocxFile.name,
                        pages: pages.map(p => ({ pageNum: p.pageNum, blocks: p.blocks })),
                        academic_format: document.getElementById('chk-pdf2docx-academic').checked
                    })
                });
                if (!res.ok) {
                    const errData = await res.json().catch(() => ({}));
                    throw new Error(errData.message || 'Gagal menyusun file Word.');
                }
                lastPdf2DocxBlob = await res.blob();
                lastPdf2DocxName = selectedPdf2DocxFile.name.replace(/\.pdf$/i, '') + '.docx';
                setProc('pdf2docx', true, 'Selesai!', 100);
                setTimeout(() => setProc('pdf2docx', false), 250);

                showResultCard('pdf2docx', lastPdf2DocxName, `${pages.length} halaman · ${totalBlocks} blok teks · ${formatBytes(lastPdf2DocxBlob.size)}`);
                document.getElementById('btn-download-pdf2docx').onclick = () => triggerBlobDownload(lastPdf2DocxBlob, lastPdf2DocxName);
                triggerBlobDownload(lastPdf2DocxBlob, lastPdf2DocxName);
            } catch (err) {
                setProc('pdf2docx', false);
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
                setStatus('status-docx2pdf', 'error', 'Ukuran file .docx melebihi 4 MB. Gunakan menu Kompres File → Word terlebih dahulu.');
                return;
            }
            const btn = document.getElementById('btn-docx2pdf');
            btn.disabled = true;
            hideResult('docx2pdf');
            setStatus('status-docx2pdf', '', '');
            try {
                setProc('docx2pdf', true, 'Mengonversi dokumen Word (.docx) ke PDF A4...', 55);
                const fd = new FormData();
                fd.append('file', selectedDocx2PdfFile);
                fd.append('margin', document.getElementById('sel-docx2pdf-margin').value);
                fd.append('page_numbers', document.getElementById('chk-docx2pdf-pagenum').checked ? '1' : '0');
                const res = await fetch('{{ route("tools.convert.docxToPdf") }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    body: fd
                });
                if (!res.ok) {
                    const errData = await res.json().catch(() => ({}));
                    throw new Error(errData.message || 'Gagal mengonversi ke PDF.');
                }
                lastDocx2PdfBlob = await res.blob();
                lastDocx2PdfName = selectedDocx2PdfFile.name.replace(/\.docx$/i, '') + '.pdf';
                setProc('docx2pdf', true, 'Selesai!', 100);
                setTimeout(() => setProc('docx2pdf', false), 250);

                showResultCard('docx2pdf', lastDocx2PdfName, `Ukuran PDF: ${formatBytes(lastDocx2PdfBlob.size)} · Format kertas A4`);
                document.getElementById('btn-preview-docx2pdf').onclick = () => openBlobPreview(lastDocx2PdfBlob);
                document.getElementById('btn-download-docx2pdf').onclick = () => triggerBlobDownload(lastDocx2PdfBlob, lastDocx2PdfName);
                triggerBlobDownload(lastDocx2PdfBlob, lastDocx2PdfName);
            } catch (err) {
                setProc('docx2pdf', false);
                setStatus('status-docx2pdf', 'error', err.message || 'Terjadi kesalahan saat konversi.');
            } finally {
                btn.disabled = false;
            }
        }

        // ============================================================
        // 4. GABUNG PDF (MERGE PDF) — FIXED ENCRYPTION & THUMBNAILS
        // ============================================================
        let mergePdfItems = [];
        let lastMergePdfBlob = null, lastMergePdfName = 'Gabungan_Skripsi.pdf';

        async function renderPdfPageThumbDataUrl(uint8Bytes, pageNum = 1, maxW = 140) {
            try {
                await waitForLib('pdfjsLib', 'PDF.js');
                ensurePdfJsWorker();
                const pdf = await window.pdfjsLib.getDocument({ data: uint8Bytes.slice() }).promise;
                const page = await pdf.getPage(pageNum);
                const vp0 = page.getViewport({ scale: 1 });
                const scale = Math.min(1, maxW / (vp0.width || 595));
                const vp = page.getViewport({ scale });
                const canvas = document.createElement('canvas');
                canvas.width = Math.max(1, Math.round(vp.width));
                canvas.height = Math.max(1, Math.round(vp.height));
                const ctx = canvas.getContext('2d');
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                await page.render({ canvasContext: ctx, viewport: vp }).promise;
                return canvas.toDataURL('image/jpeg', 0.72);
            } catch (e) {
                return '';
            }
        }

        async function addMergePdfFiles(input) {
            if (!input.files || !input.files.length) return;
            setStatus('status-merge-pdf', '', '');
            hideResult('merge');
            try {
                await waitForLib('PDFLib', 'PDF-Lib');
                const rejected = [];
                for (const f of Array.from(input.files)) {
                    const bytes = new Uint8Array(await f.arrayBuffer());
                    try {
                        const doc = await window.PDFLib.PDFDocument.load(bytes);
                        const pageCount = doc.getPageCount();
                        const thumb = await renderPdfPageThumbDataUrl(bytes, 1, 120);
                        mergePdfItems.push({ name: f.name, size: f.size, bytes, pages: pageCount, thumb });
                    } catch (err) {
                        rejected.push(f.name);
                    }
                }
                if (rejected.length) {
                    setStatus('status-merge-pdf', 'error', `File dilewati karena terenkripsi kata sandi atau korup: ${rejected.join(', ')}. Buka kuncinya dulu sebelum digabung.`);
                }
            } catch (err) {
                setStatus('status-merge-pdf', 'error', err.message || 'Gagal membaca file PDF.');
            }
            input.value = '';
            renderMergePdfList();
        }

        function renderMergePdfList() {
            const wrap = document.getElementById('merge-pdf-list');
            const sumWrap = document.getElementById('merge-pdf-summary');
            if (!mergePdfItems.length) {
                wrap.innerHTML = '';
                sumWrap.style.display = 'none';
                return;
            }
            const totalPages = mergePdfItems.reduce((a, b) => a + (b.pages || 0), 0);
            sumWrap.style.display = 'flex';
            document.getElementById('merge-pdf-summary-text').textContent = `${mergePdfItems.length} file dipilih · Total ${totalPages} halaman akan digabungkan`;

            wrap.innerHTML = mergePdfItems.map((it, i) => `
                <div class="file-row">
                    <div class="file-row-left">
                        ${it.thumb ? `<img src="${it.thumb}" class="file-pdf-thumb" alt="Hal 1">` : `<span class="file-badge">PDF</span>`}
                        <div style="min-width:0;">
                            <div class="file-name">${i + 1}. ${it.name.replace(/</g, '&lt;')}</div>
                            <div class="file-meta">${it.pages} halaman · ${formatBytes(it.size)}</div>
                        </div>
                    </div>
                    <div class="file-actions">
                        <button type="button" class="mini-btn" onclick="moveMergePdf(${i}, -1)" ${i === 0 ? 'disabled' : ''}>▲ Naik</button>
                        <button type="button" class="mini-btn" onclick="moveMergePdf(${i}, 1)" ${i === mergePdfItems.length - 1 ? 'disabled' : ''}>▼ Turun</button>
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
        function removeMergePdf(i) { mergePdfItems.splice(i, 1); renderMergePdfList(); }
        function clearMergePdf() { mergePdfItems = []; hideResult('merge'); renderMergePdfList(); }

        async function runMergePdf() {
            if (mergePdfItems.length < 2) {
                setStatus('status-merge-pdf', 'error', 'Tambahkan minimal 2 file PDF untuk digabungkan.');
                return;
            }
            const btn = document.getElementById('btn-merge-pdf');
            btn.disabled = true;
            hideResult('merge');
            setStatus('status-merge-pdf', '', '');
            try {
                await waitForLib('PDFLib', 'PDF-Lib');
                const addPageNum = document.getElementById('merge-add-pagenum').checked;
                const merged = await window.PDFLib.PDFDocument.create();
                const font = addPageNum ? await merged.embedFont(window.PDFLib.StandardFonts.TimesRoman) : null;

                for (let idx = 0; idx < mergePdfItems.length; idx++) {
                    const item = mergePdfItems[idx];
                    setProc('merge', true, `Menggabungkan file ${idx + 1} / ${mergePdfItems.length}: ${item.name}`, ((idx + 0.5) / mergePdfItems.length) * 80);
                    const src = await window.PDFLib.PDFDocument.load(item.bytes.slice());
                    const copied = await merged.copyPages(src, src.getPageIndices());
                    copied.forEach(p => merged.addPage(p));
                }

                if (addPageNum && font) {
                    const allPages = merged.getPages();
                    allPages.forEach((pg, i) => {
                        const { width } = pg.getSize();
                        const label = String(i + 1);
                        const tw = font.widthOfTextAtSize(label, 11);
                        pg.drawText(label, { x: (width - tw) / 2, y: 24, size: 11, font, color: window.PDFLib.rgb(0.1, 0.1, 0.1) });
                    });
                }

                setProc('merge', true, 'Menyusun file PDF akhir (kompatibilitas universal)...', 92);
                const bytes = await merged.save({ useObjectStreams: false });
                lastMergePdfName = (document.getElementById('merge-output-name').value || 'Gabungan_Skripsi').trim().replace(/\.pdf$/i, '') + '.pdf';
                lastMergePdfBlob = new Blob([bytes], { type: 'application/pdf' });

                setProc('merge', true, 'Selesai!', 100);
                setTimeout(() => setProc('merge', false), 220);

                showResultCard('merge', lastMergePdfName, `${merged.getPageCount()} halaman dari ${mergePdfItems.length} file · ${formatBytes(lastMergePdfBlob.size)}`);
                document.getElementById('btn-preview-merge').onclick = () => openBlobPreview(lastMergePdfBlob);
                document.getElementById('btn-download-merge').onclick = () => triggerBlobDownload(lastMergePdfBlob, lastMergePdfName);
            } catch (err) {
                setProc('merge', false);
                setStatus('status-merge-pdf', 'error', err.message || 'Gagal menggabungkan PDF.');
            } finally {
                btn.disabled = false;
            }
        }

        // ============================================================
        // 5. PISAH / HAPUS HALAMAN PDF — VISUAL THUMBNAIL + AKURAT 100%
        // ============================================================
        let splitPdfBytes = null;
        let splitPdfName = '';
        let splitPdfTotalPages = 0;
        let splitMode = 'extract';
        let splitSelectedSet = new Set();
        let lastSplitPdfBlob = null, lastSplitPdfName = 'Halaman_PDF.pdf';

        function setSplitMode(mode) {
            splitMode = mode;
            document.getElementById('seg-split-extract').classList.toggle('active', mode === 'extract');
            document.getElementById('seg-split-delete').classList.toggle('active', mode === 'delete');
            document.getElementById('split-range-label').textContent = mode === 'extract'
                ? 'Halaman yang Diambil (sinkron otomatis dengan klik di atas)'
                : 'Halaman yang Dihapus (halaman lain akan tetap disimpan)';
            document.getElementById('btn-split-pdf').textContent = mode === 'extract'
                ? 'Ekstrak Halaman Terpilih'
                : 'Hapus Halaman & Simpan PDF';
            syncSplitSelectionUi();
        }

        function setToCompactRange(nums) {
            if (!nums.length) return '';
            const sorted = Array.from(new Set(nums)).sort((a, b) => a - b);
            const parts = [];
            let start = sorted[0], prev = sorted[0];
            for (let i = 1; i <= sorted.length; i++) {
                if (sorted[i] === prev + 1) {
                    prev = sorted[i];
                } else {
                    parts.push(start === prev ? String(start) : `${start}-${prev}`);
                    start = prev = sorted[i];
                }
            }
            return parts.join(', ');
        }

        function parsePageRanges1Based(str, maxPage) {
            const set = new Set();
            String(str || '').split(',').forEach(raw => {
                const p = raw.trim();
                if (!p) return;
                if (p.includes('-')) {
                    const [a, b] = p.split('-').map(n => parseInt(n.trim(), 10));
                    if (!isNaN(a) && !isNaN(b)) {
                        const s = Math.max(1, Math.min(a, b));
                        const e = Math.min(maxPage, Math.max(a, b));
                        for (let i = s; i <= e; i++) set.add(i);
                    }
                } else {
                    const n = parseInt(p, 10);
                    if (!isNaN(n) && n >= 1 && n <= maxPage) set.add(n);
                }
            });
            return set;
        }

        function syncSplitSelectionUi(updateInput = true) {
            const arr = Array.from(splitSelectedSet).sort((a, b) => a - b);
            if (updateInput) {
                const inp = document.getElementById('split-range-input');
                if (inp) inp.value = setToCompactRange(arr);
            }

            document.querySelectorAll('.split-page-card').forEach(el => {
                const p = parseInt(el.dataset.page, 10);
                const sel = splitSelectedSet.has(p);
                el.classList.toggle('selected', sel);
                el.classList.toggle('delete-mode', splitMode === 'delete');
                const tick = el.querySelector('.split-page-tick');
                if (tick) tick.textContent = splitMode === 'delete' ? '✕' : '✓';
            });
            document.querySelectorAll('.page-pill').forEach(el => {
                const p = parseInt(el.dataset.page, 10);
                el.classList.toggle('selected', splitSelectedSet.has(p));
                el.classList.toggle('delete-mode', splitMode === 'delete');
            });

            const sumEl = document.getElementById('split-selection-summary');
            if (!sumEl || !splitPdfTotalPages) return;
            if (splitMode === 'extract') {
                sumEl.style.color = '#1d4ed8';
                sumEl.textContent = arr.length
                    ? `Hasil PDF: ${arr.length} halaman diambil (${setToCompactRange(arr)})`
                    : 'Belum ada halaman yang dipilih';
            } else {
                const keptCount = Math.max(0, splitPdfTotalPages - arr.length);
                sumEl.style.color = '#b91c1c';
                sumEl.textContent = arr.length
                    ? `${arr.length} halaman dihapus · Sisa ${keptCount} halaman akan disimpan`
                    : `Pilih halaman yang ingin dihapus (total ${splitPdfTotalPages} halaman)`;
            }
        }

        function toggleSplitPage(p) {
            if (splitSelectedSet.has(p)) splitSelectedSet.delete(p);
            else splitSelectedSet.add(p);
            syncSplitSelectionUi(true);
        }

        function onSplitRangeInputTyped() {
            splitSelectedSet = parsePageRanges1Based(document.getElementById('split-range-input').value, splitPdfTotalPages);
            syncSplitSelectionUi(false);
        }

        function selectSplitPreset(kind) {
            splitSelectedSet.clear();
            if (kind === 'first') splitSelectedSet.add(1);
            else if (kind === '1-3') { for (let i = 1; i <= Math.min(3, splitPdfTotalPages); i++) splitSelectedSet.add(i); }
            else if (kind === 'odd') { for (let i = 1; i <= splitPdfTotalPages; i += 2) splitSelectedSet.add(i); }
            else if (kind === 'even') { for (let i = 2; i <= splitPdfTotalPages; i += 2) splitSelectedSet.add(i); }
            else if (kind === 'last') splitSelectedSet.add(splitPdfTotalPages);
            else if (kind === 'all') { for (let i = 1; i <= splitPdfTotalPages; i++) splitSelectedSet.add(i); }
            syncSplitSelectionUi(true);
        }

        async function onSelectSplitPdf(input) {
            if (!input.files || !input.files[0]) return;
            const f = input.files[0];
            hideResult('split');
            setStatus('status-split-pdf', '', '');
            try {
                await waitForLib('PDFLib', 'PDF-Lib');
                const bytes = new Uint8Array(await f.arrayBuffer());
                const doc = await window.PDFLib.PDFDocument.load(bytes);
                splitPdfBytes = bytes;
                splitPdfName = f.name;
                splitPdfTotalPages = doc.getPageCount();

                document.getElementById('label-split-pdf').textContent = `${f.name} (${formatBytes(f.size)})`;
                document.getElementById('sub-split-pdf').textContent = `Total ${splitPdfTotalPages} halaman terdeteksi`;
                document.getElementById('split-output-name').value = f.name.replace(/\.pdf$/i, '') + '_pisah';
                document.getElementById('split-controls-wrap').style.display = 'block';

                splitSelectedSet = new Set();
                for (let i = 1; i <= Math.min(3, splitPdfTotalPages); i++) splitSelectedSet.add(i);

                const thumbGrid = document.getElementById('split-visual-grid');
                const pillGrid = document.getElementById('split-pill-grid');

                if (splitPdfTotalPages <= 36) {
                    thumbGrid.style.display = 'grid';
                    pillGrid.style.display = 'none';
                    thumbGrid.innerHTML = Array.from({ length: splitPdfTotalPages }, (_, i) => `
                        <div class="split-page-card" data-page="${i + 1}" onclick="toggleSplitPage(${i + 1})">
                            <span class="split-page-tick">✓</span>
                            <canvas id="split-thumb-cv-${i + 1}" width="110" height="145"></canvas>
                            <div class="split-page-num">Hal. ${i + 1}</div>
                        </div>
                    `).join('');
                    syncSplitSelectionUi(true);

                    await waitForLib('pdfjsLib', 'PDF.js');
                    ensurePdfJsWorker();
                    const pdfJsDoc = await window.pdfjsLib.getDocument({ data: bytes.slice() }).promise;
                    for (let p = 1; p <= splitPdfTotalPages; p++) {
                        const cv = document.getElementById('split-thumb-cv-' + p);
                        if (!cv) break;
                        const pg = await pdfJsDoc.getPage(p);
                        const vp0 = pg.getViewport({ scale: 1 });
                        const scale = Math.min(0.65, 130 / (vp0.width || 595));
                        const vp = pg.getViewport({ scale });
                        cv.width = Math.max(1, Math.round(vp.width));
                        cv.height = Math.max(1, Math.round(vp.height));
                        const ctx = cv.getContext('2d');
                        ctx.fillStyle = '#ffffff';
                        ctx.fillRect(0, 0, cv.width, cv.height);
                        await pg.render({ canvasContext: ctx, viewport: vp }).promise;
                    }
                } else {
                    thumbGrid.style.display = 'none';
                    pillGrid.style.display = 'flex';
                    pillGrid.innerHTML = Array.from({ length: splitPdfTotalPages }, (_, i) => `
                        <button type="button" class="page-pill" data-page="${i + 1}" onclick="toggleSplitPage(${i + 1})">${i + 1}</button>
                    `).join('');
                    syncSplitSelectionUi(true);
                }
            } catch (err) {
                document.getElementById('split-controls-wrap').style.display = 'none';
                setStatus('status-split-pdf', 'error', 'Gagal membuka PDF. Pastikan file tidak terproteksi kata sandi atau korup.');
            }
            input.value = '';
        }

        async function runSplitPdf() {
            if (!splitPdfBytes || !splitPdfTotalPages) {
                setStatus('status-split-pdf', 'error', 'Pilih file PDF terlebih dahulu.');
                return;
            }
            let targetPages1Based = [];
            if (splitMode === 'extract') {
                targetPages1Based = Array.from(splitSelectedSet).sort((a, b) => a - b);
            } else {
                for (let i = 1; i <= splitPdfTotalPages; i++) {
                    if (!splitSelectedSet.has(i)) targetPages1Based.push(i);
                }
            }
            if (!targetPages1Based.length) {
                setStatus('status-split-pdf', 'error', splitMode === 'extract'
                    ? 'Pilih minimal 1 halaman yang ingin diambil.'
                    : 'Tidak bisa menghapus semua halaman — minimal sisakan 1 halaman.');
                return;
            }

            const btn = document.getElementById('btn-split-pdf');
            btn.disabled = true;
            hideResult('split');
            setStatus('status-split-pdf', '', '');
            try {
                await waitForLib('PDFLib', 'PDF-Lib');
                setProc('split', true, `Menyalin ${targetPages1Based.length} halaman terpilih...`, 45);
                const src = await window.PDFLib.PDFDocument.load(splitPdfBytes.slice());
                const out = await window.PDFLib.PDFDocument.create();
                const zeroIndices = targetPages1Based.map(p => p - 1);
                const copied = await out.copyPages(src, zeroIndices);
                copied.forEach(p => out.addPage(p));

                setProc('split', true, 'Menyimpan file PDF hasil...', 88);
                const outBytes = await out.save({ useObjectStreams: false });
                const base = (document.getElementById('split-output-name').value || 'Halaman_PDF').trim().replace(/\.pdf$/i, '');
                lastSplitPdfName = base + '.pdf';
                lastSplitPdfBlob = new Blob([outBytes], { type: 'application/pdf' });

                setProc('split', true, 'Selesai!', 100);
                setTimeout(() => setProc('split', false), 220);

                showResultCard(
                    'split',
                    lastSplitPdfName,
                    `${targetPages1Based.length} halaman (${setToCompactRange(targetPages1Based)}) · ${formatBytes(lastSplitPdfBlob.size)}`
                );
                document.getElementById('btn-preview-split').onclick = () => openBlobPreview(lastSplitPdfBlob);
                document.getElementById('btn-download-split').onclick = () => triggerBlobDownload(lastSplitPdfBlob, lastSplitPdfName);
            } catch (err) {
                setProc('split', false);
                setStatus('status-split-pdf', 'error', err.message || 'Gagal memisahkan PDF.');
            } finally {
                btn.disabled = false;
            }
        }

        // ============================================================
        // 6. KOMPRES FILE 3-IN-1 (PDF / WORD .DOCX / GAMBAR)
        // ============================================================
        function setCompressMode(mode) {
            ['pdf', 'docx', 'img'].forEach(m => {
                document.getElementById('seg-comp-' + m).classList.toggle('active', m === mode);
                document.getElementById('box-comp-' + m).style.display = m === mode ? 'block' : 'none';
            });
        }

        // --- 6A. KOMPRES PDF ---
        let compPdfFile = null;
        let lastCompPdfBlob = null, lastCompPdfName = 'Kompres.pdf';

        function onSelectCompPdf(input) {
            if (!input.files || !input.files[0]) return;
            compPdfFile = input.files[0];
            hideResult('comp-pdf');
            document.getElementById('label-comp-pdf').textContent = `${compPdfFile.name} (${formatBytes(compPdfFile.size)})`;
            document.getElementById('comp-pdf-info').textContent = `Ukuran asli: ${formatBytes(compPdfFile.size)} — siap dikompres.`;
            setStatus('status-comp-pdf', '', '');
        }

        async function runCompressPdf() {
            if (!compPdfFile) {
                setStatus('status-comp-pdf', 'error', 'Pilih file PDF terlebih dahulu.');
                return;
            }
            const btn = document.getElementById('btn-comp-pdf');
            btn.disabled = true;
            hideResult('comp-pdf');
            setStatus('status-comp-pdf', '', '');
            try {
                await waitForLib('pdfjsLib', 'PDF.js');
                await waitForLib('PDFLib', 'PDF-Lib');
                ensurePdfJsWorker();

                const level = document.getElementById('comp-pdf-level').value;
                const cfg = level === 'low'
                    ? { scale: 1.05, quality: 0.48 }
                    : (level === 'high' ? { scale: 1.65, quality: 0.78 } : { scale: 1.35, quality: 0.64 });

                setProc('comp-pdf', true, 'Membuka file PDF...', 8);
                const rawBytes = new Uint8Array(await compPdfFile.arrayBuffer());
                const pdfJsDoc = await window.pdfjsLib.getDocument({ data: rawBytes }).promise;
                const outDoc = await window.PDFLib.PDFDocument.create();

                const total = pdfJsDoc.numPages;
                for (let p = 1; p <= total; p++) {
                    setProc('comp-pdf', true, `Mengompres halaman ${p} dari ${total}...`, 10 + (p / total) * 80);
                    const pg = await pdfJsDoc.getPage(p);
                    const origVp = pg.getViewport({ scale: 1 });
                    const renderVp = pg.getViewport({ scale: cfg.scale });

                    const cv = document.createElement('canvas');
                    cv.width = Math.max(1, Math.round(renderVp.width));
                    cv.height = Math.max(1, Math.round(renderVp.height));
                    const ctx = cv.getContext('2d');
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, cv.width, cv.height);
                    await pg.render({ canvasContext: ctx, viewport: renderVp }).promise;

                    const jpgBlob = await new Promise(res => cv.toBlob(res, 'image/jpeg', cfg.quality));
                    const jpgBytes = new Uint8Array(await jpgBlob.arrayBuffer());
                    const embedded = await outDoc.embedJpg(jpgBytes);
                    const outPage = outDoc.addPage([origVp.width, origVp.height]);
                    outPage.drawImage(embedded, { x: 0, y: 0, width: origVp.width, height: origVp.height });
                }

                setProc('comp-pdf', true, 'Menyimpan PDF hasil kompresi...', 94);
                const finalBytes = await outDoc.save({ useObjectStreams: false });
                lastCompPdfBlob = new Blob([finalBytes], { type: 'application/pdf' });
                lastCompPdfName = compPdfFile.name.replace(/\.pdf$/i, '') + '_kompres.pdf';

                setProc('comp-pdf', true, 'Selesai!', 100);
                setTimeout(() => setProc('comp-pdf', false), 220);

                const savedPct = compPdfFile.size > 0 ? Math.round((1 - lastCompPdfBlob.size / compPdfFile.size) * 100) : 0;
                const diffTxt = savedPct >= 0
                    ? `${formatBytes(compPdfFile.size)} → ${formatBytes(lastCompPdfBlob.size)} (Hemat ${savedPct}%)`
                    : `${formatBytes(compPdfFile.size)} → ${formatBytes(lastCompPdfBlob.size)} (PDF teks asli sudah sangat ringan — coba tingkat Sangat Ringan)`;

                showResultCard('comp-pdf', lastCompPdfName, `${total} halaman · ${diffTxt}`);
                document.getElementById('btn-preview-comp-pdf').onclick = () => openBlobPreview(lastCompPdfBlob);
                document.getElementById('btn-download-comp-pdf').onclick = () => triggerBlobDownload(lastCompPdfBlob, lastCompPdfName);
            } catch (err) {
                setProc('comp-pdf', false);
                setStatus('status-comp-pdf', 'error', err.message || 'Gagal mengompres PDF.');
            } finally {
                btn.disabled = false;
            }
        }

        // --- 6B. KOMPRES WORD (.DOCX) — 100% CLIENT-SIDE JSZIP ---
        let compDocxFile = null;
        let lastCompDocxBlob = null, lastCompDocxName = 'Kompres.docx';

        function onSelectCompDocx(input) {
            if (!input.files || !input.files[0]) return;
            const f = input.files[0];
            if (!f.name.toLowerCase().endsWith('.docx')) {
                setStatus('status-comp-docx', 'error', 'Format file harus .docx.');
                return;
            }
            compDocxFile = f;
            hideResult('comp-docx');
            document.getElementById('label-comp-docx').textContent = `${f.name} (${formatBytes(f.size)})`;
            document.getElementById('comp-docx-info').textContent = `Ukuran asli: ${formatBytes(f.size)} — siap dikompres.`;
            setStatus('status-comp-docx', '', '');
        }

        function recompressMediaBlob(blob, ext, maxW, quality) {
            return new Promise((resolve) => {
                const img = new Image();
                const url = URL.createObjectURL(blob);
                img.onload = () => {
                    let w = img.naturalWidth, h = img.naturalHeight;
                    if (maxW > 0 && w > maxW) {
                        h = Math.round((h * maxW) / w);
                        w = maxW;
                    }
                    const cv = document.createElement('canvas');
                    cv.width = Math.max(1, w);
                    cv.height = Math.max(1, h);
                    const ctx = cv.getContext('2d');
                    const isPng = ext === 'png';
                    if (!isPng) {
                        ctx.fillStyle = '#ffffff';
                        ctx.fillRect(0, 0, cv.width, cv.height);
                    }
                    ctx.drawImage(img, 0, 0, cv.width, cv.height);
                    URL.revokeObjectURL(url);
                    cv.toBlob(outBlob => {
                        if (outBlob && outBlob.size < blob.size) resolve(outBlob);
                        else resolve(null);
                    }, isPng ? 'image/png' : 'image/jpeg', isPng ? undefined : quality);
                };
                img.onerror = () => { URL.revokeObjectURL(url); resolve(null); };
                img.src = url;
            });
        }

        async function runCompressDocx() {
            if (!compDocxFile) {
                setStatus('status-comp-docx', 'error', 'Pilih file Word (.docx) terlebih dahulu.');
                return;
            }
            const btn = document.getElementById('btn-comp-docx');
            btn.disabled = true;
            hideResult('comp-docx');
            setStatus('status-comp-docx', '', '');
            try {
                const JSZip = await waitForLib('JSZip', 'JSZip');
                const level = document.getElementById('comp-docx-level').value;
                const cfg = level === 'low'
                    ? { maxW: 1000, quality: 0.60 }
                    : (level === 'high' ? { maxW: 1800, quality: 0.85 } : { maxW: 1400, quality: 0.76 });

                setProc('comp-docx', true, 'Membuka arsip dokumen Word...', 12);
                const zip = await JSZip.loadAsync(await compDocxFile.arrayBuffer());
                const mediaPaths = [];
                zip.forEach((relPath, entry) => {
                    if (!entry.dir && /^word\/media\/.+\.(jpe?g|png)$/i.test(relPath)) {
                        mediaPaths.push(relPath);
                    }
                });

                let compressedImgCount = 0;
                for (let i = 0; i < mediaPaths.length; i++) {
                    const p = mediaPaths[i];
                    setProc('comp-docx', true, `Mengompres gambar ${i + 1} / ${mediaPaths.length}...`, 15 + ((i + 1) / Math.max(1, mediaPaths.length)) * 68);
                    const ext = p.split('.').pop().toLowerCase();
                    const origBlob = await zip.file(p).async('blob');
                    const smaller = await recompressMediaBlob(origBlob, ext, cfg.maxW, cfg.quality);
                    if (smaller) {
                        zip.file(p, await smaller.arrayBuffer());
                        compressedImgCount++;
                    }
                }

                setProc('comp-docx', true, 'Mengemas ulang dokumen .docx dengan kompresi maksimal...', 90);
                lastCompDocxBlob = await zip.generateAsync({
                    type: 'blob',
                    mimeType: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    compression: 'DEFLATE',
                    compressionOptions: { level: 9 }
                });
                lastCompDocxName = compDocxFile.name.replace(/\.docx$/i, '') + '_kompres.docx';

                setProc('comp-docx', true, 'Selesai!', 100);
                setTimeout(() => setProc('comp-docx', false), 220);

                const pct = compDocxFile.size > 0 ? Math.max(0, Math.round((1 - lastCompDocxBlob.size / compDocxFile.size) * 100)) : 0;
                showResultCard(
                    'comp-docx',
                    lastCompDocxName,
                    `${formatBytes(compDocxFile.size)} → ${formatBytes(lastCompDocxBlob.size)} (Hemat ${pct}%) · ${compressedImgCount}/${mediaPaths.length} gambar dioptimasi`
                );
                document.getElementById('btn-download-comp-docx').onclick = () => triggerBlobDownload(lastCompDocxBlob, lastCompDocxName);
            } catch (err) {
                setProc('comp-docx', false);
                setStatus('status-comp-docx', 'error', err.message || 'Gagal mengompres Word (.docx).');
            } finally {
                btn.disabled = false;
            }
        }

        // --- 6C. KOMPRES GAMBAR (DENGAN TARGET UKURAN SIAKAD) ---
        let compressSourceFiles = [];
        let compressedOutputs = [];
        let imgTargetKbPreset = 'manual';

        function setImgTargetPreset(val, btnEl) {
            imgTargetKbPreset = val;
            document.querySelectorAll('[data-imgtarget]').forEach(b => b.classList.remove('active'));
            if (btnEl) btnEl.classList.add('active');
            document.getElementById('comp-img-manual-controls').style.opacity = val === 'manual' ? '1' : '0.55';
            if (compressSourceFiles.length) runCompressImages();
        }

        function onSelectCompressImages(input) {
            if (!input.files || !input.files.length) return;
            compressSourceFiles = Array.from(input.files);
            runCompressImages();
        }

        function renderCanvasToJpegBlob(img, maxWidth, quality) {
            return new Promise((resolve, reject) => {
                let w = img.naturalWidth, h = img.naturalHeight;
                if (maxWidth > 0 && w > maxWidth) {
                    h = Math.round((h * maxWidth) / w);
                    w = maxWidth;
                }
                const canvas = document.createElement('canvas');
                canvas.width = Math.max(1, w);
                canvas.height = Math.max(1, h);
                const ctx = canvas.getContext('2d');
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                canvas.toBlob(blob => blob ? resolve(blob) : reject(new Error('Gagal memproses gambar.')), 'image/jpeg', quality);
            });
        }

        function loadImageFromFile(file) {
            return new Promise((resolve, reject) => {
                const img = new Image();
                const url = URL.createObjectURL(file);
                img.onload = () => { URL.revokeObjectURL(url); resolve(img); };
                img.onerror = () => { URL.revokeObjectURL(url); reject(new Error('File bukan gambar valid: ' + file.name)); };
                img.src = url;
            });
        }

        async function compressOneImageSmart(file) {
            const img = await loadImageFromFile(file);
            let blob = null;
            if (imgTargetKbPreset === 'manual') {
                const quality = (parseInt(document.getElementById('compress-quality').value, 10) || 75) / 100;
                const maxW = parseInt(document.getElementById('compress-maxw').value, 10) || 0;
                blob = await renderCanvasToJpegBlob(img, maxW, quality);
            } else {
                const targetBytes = parseInt(imgTargetKbPreset, 10) * 1024;
                const steps = [
                    { w: 1600, q: 0.85 }, { w: 1600, q: 0.72 }, { w: 1400, q: 0.62 },
                    { w: 1200, q: 0.52 }, { w: 1000, q: 0.45 }, { w: 800, q: 0.38 }, { w: 680, q: 0.30 }
                ];
                for (const st of steps) {
                    blob = await renderCanvasToJpegBlob(img, st.w, st.q);
                    if (blob.size <= targetBytes) break;
                }
            }
            return {
                name: file.name.replace(/\.\w+$/, '') + '_kompres.jpg',
                origSize: file.size,
                newSize: blob.size,
                blob,
                preview: URL.createObjectURL(blob)
            };
        }

        async function runCompressImages() {
            if (!compressSourceFiles.length) return;
            try {
                compressedOutputs.forEach(o => URL.revokeObjectURL(o.preview));
                compressedOutputs = [];
                for (const f of compressSourceFiles) {
                    compressedOutputs.push(await compressOneImageSmart(f));
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
                                <button type="button" class="btn btn-primary" style="min-height:34px; padding:5px 12px; font-size:12px;" onclick="downloadCompressed(${i})">⬇ Unduh</button>
                            </div>
                        </div>
                    `;
                }).join('');

                const totOrig = compressedOutputs.reduce((a, b) => a + b.origSize, 0);
                const totNew = compressedOutputs.reduce((a, b) => a + b.newSize, 0);
                const totPct = totOrig > 0 ? Math.max(0, Math.round((1 - totNew / totOrig) * 100)) : 0;
                const bulkBar = document.getElementById('comp-img-bulk-bar');
                bulkBar.style.display = compressedOutputs.length > 0 ? 'flex' : 'none';
                document.getElementById('comp-img-bulk-meta').textContent = `${compressedOutputs.length} gambar selesai · Total ${formatBytes(totOrig)} → ${formatBytes(totNew)} (Hemat ${totPct}%)`;
                setStatus('status-compress-img', '', '');
            } catch (err) {
                setStatus('status-compress-img', 'error', err.message || 'Gagal mengompresi gambar.');
            }
        }

        function downloadCompressed(i) {
            const item = compressedOutputs[i];
            if (item) triggerBlobDownload(item.blob, item.name);
        }
        function downloadAllCompressedImages() {
            compressedOutputs.forEach((o, idx) => {
                setTimeout(() => triggerBlobDownload(o.blob, o.name), idx * 180);
            });
        }

        // ============================================================
        // 7. HAPUS BACKGROUND GAMBAR (DUAL-ENGINE AI OTOMATIS + TTD/LOGO)
        // ============================================================
        let rmbgMode = 'ai';
        let rmbgSourceFile = null;
        let rmbgSourceImg = null;
        let rmbgTransparentCanvas = null;
        let rmbgOutputBg = 'transparent';
        let rmbgImglyFn = null;
        let rmbgTransformersCache = null;

        function setRemoveBgMode(mode) {
            rmbgMode = mode;
            document.getElementById('seg-rmbg-smart').classList.toggle('active', mode === 'smart');
            document.getElementById('seg-rmbg-ai').classList.toggle('active', mode === 'ai');
            document.getElementById('rmbg-smart-controls').style.display = mode === 'smart' ? 'block' : 'none';
            document.getElementById('rmbg-ai-controls').style.display = mode === 'ai' ? 'block' : 'none';
            document.getElementById('btn-rmbg-run').innerHTML = mode === 'ai'
                ? '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg> Proses Ulang dengan AI'
                : '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg> Proses Hapus Background';
            if (rmbgSourceImg) {
                if (mode === 'smart') {
                    processRemoveBgSmart();
                } else {
                    runRemoveBgCurrentMode();
                }
            }
        }

        async function onSelectRemoveBgFile(input) {
            if (!input.files || !input.files[0]) return;
            rmbgSourceFile = input.files[0];
            rmbgTransparentCanvas = null;
            document.getElementById('btn-rmbg-dl-png').disabled = true;
            document.getElementById('btn-rmbg-dl-jpg').disabled = true;
            setStatus('status-rmbg', '', '');
            document.getElementById('label-rmbg').textContent = `${rmbgSourceFile.name} (${formatBytes(rmbgSourceFile.size)})`;
            try {
                rmbgSourceImg = await loadImageFromFile(rmbgSourceFile);
                const origEl = document.getElementById('rmbg-orig-img');
                origEl.src = URL.createObjectURL(rmbgSourceFile);
                document.getElementById('rmbg-orig-dim').textContent = `${rmbgSourceImg.naturalWidth} × ${rmbgSourceImg.naturalHeight} px`;
                document.getElementById('rmbg-preview-wrap').style.display = 'block';
                const outCv = document.getElementById('rmbg-out-canvas');
                outCv.width = Math.min(800, rmbgSourceImg.naturalWidth);
                outCv.height = Math.min(800, rmbgSourceImg.naturalHeight);
                outCv.getContext('2d').clearRect(0, 0, outCv.width, outCv.height);

                if (rmbgMode === 'smart') {
                    processRemoveBgSmart();
                } else {
                    await runRemoveBgCurrentMode();
                }
            } catch (err) {
                setStatus('status-rmbg', 'error', err.message || 'Gagal memuat gambar.');
            }
        }

        function renderRmbgComposite() {
            if (!rmbgTransparentCanvas) return;
            const outCv = document.getElementById('rmbg-out-canvas');
            outCv.width = rmbgTransparentCanvas.width;
            outCv.height = rmbgTransparentCanvas.height;
            const ctx = outCv.getContext('2d');
            ctx.clearRect(0, 0, outCv.width, outCv.height);
            if (rmbgOutputBg !== 'transparent') {
                ctx.fillStyle = rmbgOutputBg;
                ctx.fillRect(0, 0, outCv.width, outCv.height);
            }
            ctx.drawImage(rmbgTransparentCanvas, 0, 0);
            document.getElementById('btn-rmbg-dl-png').disabled = false;
            document.getElementById('btn-rmbg-dl-jpg').disabled = false;
        }

        function setRmbgOutputColor(color, btnEl) {
            rmbgOutputBg = color;
            document.querySelectorAll('.swatch-btn').forEach(b => b.classList.remove('active'));
            if (btnEl) btnEl.classList.add('active');
            renderRmbgComposite();
        }

        function cleanFaintAlphaHalo(canvas) {
            const ctx = canvas.getContext('2d');
            const imgData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const d = imgData.data;
            let transparentCount = 0;
            for (let i = 3; i < d.length; i += 4) {
                if (d[i] < 16) {
                    d[i] = 0;
                    transparentCount++;
                } else if (d[i] < 128) {
                    transparentCount++;
                }
            }
            ctx.putImageData(imgData, 0, 0);
            return (transparentCount / (canvas.width * canvas.height)) * 100;
        }

        function processRemoveBgSmart() {
            if (!rmbgSourceImg) return;
            const tol = parseInt(document.getElementById('rmbg-tolerance').value, 10) || 38;
            const soft = parseInt(document.getElementById('rmbg-softness').value, 10) || 18;
            const inkEnhance = document.getElementById('rmbg-ink-enhance').checked;

            const maxDim = 1600;
            let w = rmbgSourceImg.naturalWidth, h = rmbgSourceImg.naturalHeight;
            if (Math.max(w, h) > maxDim) {
                const sc = maxDim / Math.max(w, h);
                w = Math.round(w * sc);
                h = Math.round(h * sc);
            }

            const cv = document.createElement('canvas');
            cv.width = w;
            cv.height = h;
            const ctx = cv.getContext('2d');
            ctx.drawImage(rmbgSourceImg, 0, 0, w, h);
            const imgData = ctx.getImageData(0, 0, w, h);
            const d = imgData.data;

            // Kumpulkan beberapa sampel warna tepi agar tidak kacau bila 4 sudut berbeda warna
            const borderSeeds = [];
            const seedCoords = [
                [2, 2], [w - 3, 2], [2, h - 3], [w - 3, h - 3],
                [Math.floor(w / 2), 2], [Math.floor(w / 2), h - 3],
                [2, Math.floor(h / 2)], [w - 3, Math.floor(h / 2)]
            ];
            for (const [sx, sy] of seedCoords) {
                const idx = (Math.max(0, Math.min(h - 1, sy)) * w + Math.max(0, Math.min(w - 1, sx))) * 4;
                if (d[idx + 3] > 200) {
                    borderSeeds.push([d[idx], d[idx + 1], d[idx + 2]]);
                }
            }
            if (!borderSeeds.length) borderSeeds.push([255, 255, 255]);

            const colorDist = (idx) => {
                let best = 999;
                const r = d[idx], g = d[idx + 1], b = d[idx + 2];
                for (let k = 0; k < borderSeeds.length; k++) {
                    const dr = r - borderSeeds[k][0], dg = g - borderSeeds[k][1], db = b - borderSeeds[k][2];
                    const dist = Math.sqrt(dr * dr + dg * dg + db * db);
                    if (dist < best) best = dist;
                }
                return best;
            };

            let removedPx = 0;
            if (inkEnhance) {
                for (let i = 0; i < d.length; i += 4) {
                    const dist = colorDist(i);
                    const lum = 0.299 * d[i] + 0.587 * d[i + 1] + 0.114 * d[i + 2];
                    const effDist = Math.min(dist, (255 - lum) * 1.35);
                    if (effDist <= tol) {
                        d[i + 3] = 0;
                        removedPx++;
                    } else if (effDist < tol + soft + 15) {
                        const a = Math.round(((effDist - tol) / (soft + 15)) * 255);
                        d[i + 3] = Math.min(d[i + 3], a);
                        d[i] = 15; d[i + 1] = 23; d[i + 2] = 42;
                    } else {
                        d[i] = 15; d[i + 1] = 23; d[i + 2] = 42;
                    }
                }
            } else {
                const visited = new Uint8Array(w * h);
                const queue = new Int32Array(w * h);
                let head = 0, tail = 0;
                const thresh = tol + soft;

                const tryPush = (x, y) => {
                    const p = y * w + x;
                    if (visited[p]) return;
                    const dist = colorDist(p * 4);
                    if (dist <= thresh) {
                        visited[p] = 1;
                        queue[tail++] = p;
                    }
                };
                for (let x = 0; x < w; x++) { tryPush(x, 0); tryPush(x, h - 1); }
                for (let y = 0; y < h; y++) { tryPush(0, y); tryPush(w - 1, y); }

                while (head < tail) {
                    const p = queue[head++];
                    const x = p % w, y = (p - x) / w;
                    const idx = p * 4;
                    const dist = colorDist(idx);
                    if (dist <= tol) {
                        d[idx + 3] = 0;
                        removedPx++;
                    } else {
                        const ratio = (dist - tol) / Math.max(1, soft);
                        d[idx + 3] = Math.round(d[idx + 3] * Math.max(0, Math.min(1, ratio)));
                        if (d[idx + 3] < 128) removedPx++;
                    }
                    if (x > 0) tryPush(x - 1, y);
                    if (x < w - 1) tryPush(x + 1, y);
                    if (y > 0) tryPush(x, y - 1);
                    if (y < h - 1) tryPush(x, y + 1);
                }
            }

            ctx.putImageData(imgData, 0, 0);
            rmbgTransparentCanvas = cv;
            renderRmbgComposite();
            const removedPct = (removedPx / (w * h)) * 100;
            if (removedPct < 4) {
                setStatus('status-rmbg', 'info', 'Background gambar ini tampak kompleks untuk mode Latar Polos. Klik tab "Hapus Background AI (Otomatis)" di atas untuk pemisahan subjek yang bersih!');
            } else {
                setStatus('status-rmbg', 'success', `✓ Background polos berhasil dihapus (${removedPct.toFixed(0)}% area latar dibersihkan). Geser slider Toleransi jika perlu, lalu unduh PNG/JPG.`);
            }
        }

        async function runAiWithImgly(file) {
            if (!rmbgImglyFn) {
                setProc('rmbg', true, 'Memuat mesin AI pemisah objek (ISNet · tersimpan di cache browser)...', 15);
                const mod = await import('https://cdn.jsdelivr.net/npm/@imgly/background-removal@1.7.0/+esm');
                rmbgImglyFn = mod.removeBackground || mod.default;
                if (typeof rmbgImglyFn !== 'function') throw new Error('Modul AI ISNet tidak tersedia.');
            }
            const resultBlob = await rmbgImglyFn(file, {
                model: 'isnet_quint8',
                device: 'cpu',
                progress: (key, cur, tot) => {
                    const pct = tot > 0 ? Math.min(92, Math.max(18, Math.round((cur / tot) * 90))) : 55;
                    const label = String(key || '').includes('compute')
                        ? 'AI sedang memisahkan subjek dari latar belakang...'
                        : 'Mengunduh model AI (pertama kali saja, selanjutnya instan)...';
                    setProc('rmbg', true, label, pct);
                }
            });
            const outImg = await loadImageFromFile(resultBlob);
            const cv = document.createElement('canvas');
            cv.width = outImg.naturalWidth;
            cv.height = outImg.naturalHeight;
            cv.getContext('2d').drawImage(outImg, 0, 0);
            return cv;
        }

        async function runAiWithTransformers(file) {
            if (!rmbgTransformersCache) {
                setProc('rmbg', true, 'Memuat model AI cadangan (BRIA RMBG-1.4)...', 25);
                const tf = await import('https://cdn.jsdelivr.net/npm/@huggingface/transformers@3.4.0/dist/transformers.min.js');
                tf.env.allowLocalModels = false;
                const model = await tf.AutoModel.from_pretrained('briaai/RMBG-1.4', {
                    config: { model_type: 'custom' },
                    dtype: 'q8'
                });
                const processor = await tf.AutoProcessor.from_pretrained('briaai/RMBG-1.4', {
                    config: {
                        do_normalize: true, do_pad: false, do_rescale: true, do_resize: true,
                        image_mean: [0.5, 0.5, 0.5], feature_extractor_type: 'ImageFeatureExtractor',
                        image_std: [1, 1, 1], resample: 2, rescale_factor: 0.00392156862745098,
                        size: { width: 1024, height: 1024 },
                    }
                });
                rmbgTransformersCache = { tf, model, processor };
            }
            setProc('rmbg', true, 'AI RMBG-1.4 sedang memisahkan objek dari background...', 70);
            const { tf, model, processor } = rmbgTransformersCache;
            const objUrl = URL.createObjectURL(file);
            try {
                const image = await tf.RawImage.fromURL(objUrl);
                const { pixel_values } = await processor(image);
                const { output } = await model({ input: pixel_values });
                const mask = await tf.RawImage.fromTensor(output[0].mul(255).to('uint8')).resize(image.width, image.height);
                const cv = document.createElement('canvas');
                cv.width = image.width;
                cv.height = image.height;
                const ctx = cv.getContext('2d');
                ctx.drawImage(rmbgSourceImg, 0, 0, cv.width, cv.height);
                const imgData = ctx.getImageData(0, 0, cv.width, cv.height);
                const px = imgData.data;
                for (let i = 0; i < mask.data.length; i++) {
                    px[i * 4 + 3] = mask.data[i];
                }
                ctx.putImageData(imgData, 0, 0);
                return cv;
            } finally {
                URL.revokeObjectURL(objUrl);
            }
        }

        async function runRemoveBgCurrentMode() {
            if (!rmbgSourceFile || !rmbgSourceImg) {
                setStatus('status-rmbg', 'error', 'Pilih gambar terlebih dahulu.');
                return;
            }
            if (rmbgMode === 'smart') {
                processRemoveBgSmart();
                return;
            }

            const btn = document.getElementById('btn-rmbg-run');
            btn.disabled = true;
            setStatus('status-rmbg', '', '');
            setProc('rmbg', true, 'Menyiapkan AI Hapus Background di browser...', 12);

            try {
                let cv = null;
                let engineName = 'AI ISNet';
                try {
                    cv = await runAiWithImgly(rmbgSourceFile);
                } catch (primaryErr) {
                    console.warn('Primary AI (ISNet) gagal, mencoba BRIA RMBG-1.4:', primaryErr);
                    engineName = 'AI RMBG-1.4';
                    cv = await runAiWithTransformers(rmbgSourceFile);
                }

                const removedPct = cleanFaintAlphaHalo(cv);
                rmbgTransparentCanvas = cv;
                renderRmbgComposite();

                setProc('rmbg', true, 'Selesai!', 100);
                setTimeout(() => setProc('rmbg', false), 250);
                setStatus('status-rmbg', 'success', `✓ ${engineName} berhasil menghapus latar belakang (${removedPct.toFixed(0)}% area latar dibersihkan)! Pilih warna Transparan / Merah / Biru lalu unduh.`);
            } catch (err) {
                setProc('rmbg', false);
                console.error('AI Remove Background gagal:', err);
                setStatus('status-rmbg', 'error', 'Gagal memuat model AI dari internet. Pastikan koneksi stabil lalu klik "Proses Ulang dengan AI", atau pakai mode "Tanda Tangan & Logo (Latar Polos)" untuk gambar berlatar putih.');
            } finally {
                btn.disabled = false;
            }
        }

        function downloadRemoveBg(fmt) {
            const outCv = document.getElementById('rmbg-out-canvas');
            if (!outCv || !rmbgTransparentCanvas) return;
            const base = (rmbgSourceFile ? rmbgSourceFile.name.replace(/\.\w+$/, '') : 'Gambar') + '_nobg';
            if (fmt === 'jpg') {
                const tmp = document.createElement('canvas');
                tmp.width = outCv.width;
                tmp.height = outCv.height;
                const ctx = tmp.getContext('2d');
                ctx.fillStyle = rmbgOutputBg === 'transparent' ? '#ffffff' : rmbgOutputBg;
                ctx.fillRect(0, 0, tmp.width, tmp.height);
                ctx.drawImage(rmbgTransparentCanvas, 0, 0);
                tmp.toBlob(b => b && triggerBlobDownload(b, base + '.jpg'), 'image/jpeg', 0.94);
            } else {
                outCv.toBlob(b => b && triggerBlobDownload(b, base + '.png'), 'image/png');
            }
        }

        // ============================================================
        // 8. JPG / PNG -> PDF A4 (DENGAN PUTAR 90° & PRATINJAU HASIL)
        // ============================================================
        let imgPdfItems = [];
        let lastImgPdfBlob = null, lastImgPdfName = 'Dokumen_Gambar_A4.pdf';

        function addImgPdfFiles(input) {
            if (!input.files || !input.files.length) return;
            hideResult('img-pdf');
            for (const f of Array.from(input.files)) {
                imgPdfItems.push({ file: f, previewUrl: URL.createObjectURL(f), rotate: 0 });
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
                        <img src="${it.previewUrl}" class="file-thumb" style="transform:rotate(${it.rotate}deg);" alt="Preview">
                        <div style="min-width:0;">
                            <div class="file-name">${it.file.name.replace(/</g, '&lt;')}</div>
                            <div class="file-meta">Halaman ${i + 1} · Putar ${it.rotate}° · ${formatBytes(it.file.size)}</div>
                        </div>
                    </div>
                    <div class="file-actions">
                        <button type="button" class="mini-btn" onclick="rotateImgPdf(${i})" title="Putar 90°">↻ Putar</button>
                        <button type="button" class="mini-btn" onclick="moveImgPdf(${i}, -1)" ${i === 0 ? 'disabled' : ''}>▲</button>
                        <button type="button" class="mini-btn" onclick="moveImgPdf(${i}, 1)" ${i === imgPdfItems.length - 1 ? 'disabled' : ''}>▼</button>
                        <button type="button" class="mini-btn danger" onclick="removeImgPdf(${i})">Hapus</button>
                    </div>
                </div>
            `).join('');
        }

        function rotateImgPdf(i) {
            if (!imgPdfItems[i]) return;
            imgPdfItems[i].rotate = (imgPdfItems[i].rotate + 90) % 360;
            renderImgPdfList();
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

        function imageFileToRotatedJpegBytes(file, rotateDeg) {
            return new Promise((resolve, reject) => {
                const img = new Image();
                const url = URL.createObjectURL(file);
                img.onload = () => {
                    const rad = (rotateDeg * Math.PI) / 180;
                    const swap = rotateDeg === 90 || rotateDeg === 270;
                    const cw = swap ? img.naturalHeight : img.naturalWidth;
                    const ch = swap ? img.naturalWidth : img.naturalHeight;
                    const canvas = document.createElement('canvas');
                    canvas.width = cw;
                    canvas.height = ch;
                    const ctx = canvas.getContext('2d');
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, cw, ch);
                    ctx.translate(cw / 2, ch / 2);
                    ctx.rotate(rad);
                    ctx.drawImage(img, -img.naturalWidth / 2, -img.naturalHeight / 2);
                    URL.revokeObjectURL(url);
                    canvas.toBlob(async (blob) => {
                        if (!blob) return reject(new Error('Gagal memproses gambar.'));
                        resolve({ bytes: new Uint8Array(await blob.arrayBuffer()), width: cw, height: ch });
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
            hideResult('img-pdf');
            setStatus('status-img-pdf', '', '');
            try {
                await waitForLib('PDFLib', 'PDF-Lib');
                const orient = document.getElementById('img-pdf-orient').value;
                const margin = parseInt(document.getElementById('img-pdf-margin').value, 10) || 0;
                const doc = await window.PDFLib.PDFDocument.create();

                for (let i = 0; i < imgPdfItems.length; i++) {
                    const it = imgPdfItems[i];
                    setProc('img-pdf', true, `Menyusun foto ${i + 1} dari ${imgPdfItems.length}...`, ((i + 0.5) / imgPdfItems.length) * 85);
                    const { bytes: jpgBytes, width: iw, height: ih } = await imageFileToRotatedJpegBytes(it.file, it.rotate);
                    const embedded = await doc.embedJpg(jpgBytes);
                    const isLand = orient === 'landscape' || (orient === 'auto' && iw > ih);
                    const pageW = isLand ? 841.89 : 595.28;
                    const pageH = isLand ? 595.28 : 841.89;
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

                setProc('img-pdf', true, 'Menyimpan file PDF A4...', 95);
                const bytes = await doc.save({ useObjectStreams: false });
                lastImgPdfName = (document.getElementById('img-pdf-name').value || 'Dokumen_Gambar_A4').trim().replace(/\.pdf$/i, '') + '.pdf';
                lastImgPdfBlob = new Blob([bytes], { type: 'application/pdf' });

                setProc('img-pdf', true, 'Selesai!', 100);
                setTimeout(() => setProc('img-pdf', false), 220);

                showResultCard('img-pdf', lastImgPdfName, `${imgPdfItems.length} halaman A4 · ${formatBytes(lastImgPdfBlob.size)}`);
                document.getElementById('btn-preview-img-pdf').onclick = () => openBlobPreview(lastImgPdfBlob);
                document.getElementById('btn-download-img-pdf').onclick = () => triggerBlobDownload(lastImgPdfBlob, lastImgPdfName);
            } catch (err) {
                setProc('img-pdf', false);
                setStatus('status-img-pdf', 'error', err.message || 'Gagal membuat PDF.');
            } finally {
                btn.disabled = false;
            }
        }

        // ============================================================
        // 9. KALKULATOR SAMPEL (SLOVIN & LEMESHOW)
        // ============================================================
        let sampleMode = 'slovin';
        let lastSlovinParagraph = '';

        function setSampleMode(mode) {
            sampleMode = mode;
            document.getElementById('seg-sample-slovin').classList.toggle('active', mode === 'slovin');
            document.getElementById('seg-sample-lemeshow').classList.toggle('active', mode === 'lemeshow');
            document.getElementById('box-sample-slovin').style.display = mode === 'slovin' ? 'block' : 'none';
            document.getElementById('box-sample-lemeshow').style.display = mode === 'lemeshow' ? 'block' : 'none';
            calcSample();
        }

        function onSlovinErrorSelect() {
            const sel = document.getElementById('slovin-e').value;
            document.getElementById('slovin-e-custom').style.display = sel === 'custom' ? 'block' : 'none';
            calcSample();
        }

        function calcSample() {
            const cmpWrap = document.getElementById('slovin-compare-wrap');
            if (sampleMode === 'slovin') {
                const N = parseFloat(document.getElementById('slovin-n').value);
                const selVal = document.getElementById('slovin-e').value;
                const e = selVal === 'custom'
                    ? (parseFloat(document.getElementById('slovin-e-custom').value) / 100)
                    : parseFloat(selVal);

                if (!N || N < 2 || !e || e <= 0 || e >= 1) {
                    document.getElementById('slovin-final-n').textContent = '—';
                    document.getElementById('slovin-steps-box').textContent = 'Masukkan populasi N (minimal 2) dan batas kesalahan valid.';
                    document.getElementById('slovin-para-box').textContent = '';
                    cmpWrap.innerHTML = '';
                    return;
                }
                const calcOne = (err) => Math.ceil(N / (1 + N * err * err));
                const ne2 = N * e * e;
                const denom = 1 + ne2;
                const rawN = N / denom;
                const roundedN = Math.ceil(rawN);
                const pctStr = (e * 100).toFixed(e * 100 % 1 === 0 ? 0 : 1).replace('.', ',') + '%';
                const eComma = String(e).replace('.', ',');

                document.getElementById('sample-result-label').textContent = 'Jumlah Sampel Minimal — Rumus Slovin';
                document.getElementById('slovin-final-n').textContent = `${roundedN.toLocaleString('id-ID')} Responden`;
                document.getElementById('slovin-steps-box').textContent =
                    `Rumus Slovin : n = N / (1 + N × e²)\n` +
                    `Diketahui    : N = ${N.toLocaleString('id-ID')}, e = ${eComma} (${pctStr})\n` +
                    `Perhitungan  : n = ${N} / (1 + ${N} × (${eComma})²)\n` +
                    `             : n = ${N} / (1 + ${ne2.toFixed(4)}) = ${N} / ${denom.toFixed(4)} = ${rawN.toFixed(2)}\n` +
                    `Pembulatan   : n = ${roundedN} responden`;

                lastSlovinParagraph = `Penentuan jumlah sampel dalam penelitian ini menggunakan rumus Slovin dengan ukuran populasi sebanyak N = ${N.toLocaleString('id-ID')} dan batas toleransi kesalahan (margin of error) sebesar e = ${pctStr} (${eComma}). Berdasarkan perhitungan n = ${N} / (1 + ${N} × (${eComma})²) = ${rawN.toFixed(2).replace('.', ',')}, maka jumlah sampel minimal yang dibulatkan ke atas dan digunakan dalam penelitian ini adalah sebanyak ${roundedN} responden.`;
                document.getElementById('slovin-para-box').textContent = lastSlovinParagraph;

                cmpWrap.innerHTML = `
                    <table class="compare-table">
                        <thead><tr><th>Margin Error (e)</th><th>1% (0,01)</th><th>5% (0,05)</th><th>10% (0,10)</th></tr></thead>
                        <tbody><tr><td><strong>Sampel Minimal (n)</strong></td><td>${calcOne(0.01)} resp.</td><td><strong>${calcOne(0.05)} resp.</strong></td><td>${calcOne(0.10)} resp.</td></tr></tbody>
                    </table>
                `;
            } else {
                const Z = parseFloat(document.getElementById('lem-z').value) || 1.96;
                const P = parseFloat(document.getElementById('lem-p').value) || 0.5;
                const d = parseFloat(document.getElementById('lem-d').value) || 0.1;
                const z2 = Z * Z;
                const pq = P * (1 - P);
                const d2 = d * d;
                const rawN = (z2 * pq) / d2;
                const roundedN = Math.ceil(rawN);
                const conf = Z >= 2.5 ? '99%' : (Z >= 1.9 ? '95%' : '90%');

                document.getElementById('sample-result-label').textContent = 'Jumlah Sampel Minimal — Rumus Lemeshow';
                document.getElementById('slovin-final-n').textContent = `${roundedN.toLocaleString('id-ID')} Responden`;
                document.getElementById('slovin-steps-box').textContent =
                    `Rumus Lemeshow : n = (Z² × P × (1 - P)) / d²\n` +
                    `Diketahui      : Z = ${Z} (kepercayaan ${conf}), P = ${P}, d = ${d} (${Math.round(d * 100)}%)\n` +
                    `Perhitungan    : n = (${Z}² × ${P} × ${(1 - P).toFixed(2)}) / ${d}²\n` +
                    `               : n = (${z2.toFixed(4)} × ${pq.toFixed(4)}) / ${d2.toFixed(4)} = ${rawN.toFixed(2)}\n` +
                    `Pembulatan     : n = ${roundedN} responden`;

                lastSlovinParagraph = `Karena jumlah populasi dalam penelitian ini tidak diketahui secara pasti, penentuan jumlah sampel menggunakan rumus Lemeshow dengan tingkat kepercayaan ${conf} (Z = ${String(Z).replace('.', ',')}), estimasi maksimal proporsi P = ${String(P).replace('.', ',')}, dan tingkat kesalahan pengambilan sampel d = ${Math.round(d * 100)}% (${String(d).replace('.', ',')}). Berdasarkan hasil perhitungan n = (${String(Z).replace('.', ',')}² × ${String(P).replace('.', ',')} × ${String((1 - P).toFixed(2)).replace('.', ',')}) / (${String(d).replace('.', ',')})² = ${rawN.toFixed(2).replace('.', ',')}, maka jumlah sampel minimal yang digunakan dalam penelitian ini adalah sebanyak ${roundedN} responden.`;
                document.getElementById('slovin-para-box').textContent = lastSlovinParagraph;
                cmpWrap.innerHTML = '';
            }
        }

        function copySlovinParagraph() {
            if (!lastSlovinParagraph) return;
            navigator.clipboard.writeText(lastSlovinParagraph).then(() => {
                setStatus('status-slovin', 'success', '✓ Paragraf perhitungan sampel berhasil disalin! Siap ditempel ke Bab III.');
            }).catch(() => {
                setStatus('status-slovin', 'error', 'Gagal menyalin otomatis. Silakan blok dan salin manual.');
            });
        }

        // ============================================================
        // 10. HITUNG KATA, CEK ABSTRAK & TOP KATA KUNCI
        // ============================================================
        const ID_STOPWORDS = new Set([
            'yang', 'dan', 'di', 'ke', 'dari', 'pada', 'dalam', 'untuk', 'dengan', 'adalah', 'ini', 'itu',
            'atau', 'sebagai', 'oleh', 'tidak', 'juga', 'dapat', 'akan', 'tersebut', 'bahwa', 'secara',
            'telah', 'antara', 'serta', 'karena', 'lebih', 'seperti', 'bisa', 'saat', 'para',
            'ada', 'bagi', 'hanya', 'agar', 'namun', 'tetapi', 'sudah', 'masih', 'maupun', 'merupakan',
            'hasil', 'satu', 'dua', 'tiga', 'the', 'and', 'of', 'to', 'in', 'is', 'for', 'with', 'that'
        ]);

        function updateWordCount() {
            const text = document.getElementById('wc-textarea').value || '';
            const trimmed = text.trim();
            const wordList = trimmed ? trimmed.split(/\s+/).filter(Boolean) : [];
            const words = wordList.length;
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

            const absBadge = document.getElementById('wc-abstract-badge');
            const icInfo = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>';
            const icCheck = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" style="flex-shrink:0;"><polyline points="20 6 9 17 4 12"/></svg>';
            const icWarn = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>';
            const icDoc = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>';
            if (words === 0) {
                absBadge.className = 'abstract-badge';
                absBadge.innerHTML = `${icInfo}<span>Standar abstrak skripsi Indonesia umumnya 150–250 kata.</span>`;
            } else if (words >= 150 && words <= 250) {
                absBadge.className = 'abstract-badge ideal';
                absBadge.innerHTML = `${icCheck}<span>Ideal untuk Abstrak Skripsi (${words} kata — masuk rentang 150–250 kata).</span>`;
            } else if (words < 150) {
                absBadge.className = 'abstract-badge warn';
                absBadge.innerHTML = `${icInfo}<span>Jika ini Abstrak: kurang ${150 - words} kata lagi dari minimal 150 kata.</span>`;
            } else if (words <= 350) {
                absBadge.className = 'abstract-badge warn';
                absBadge.innerHTML = `${icWarn}<span>Jika ini Abstrak: melebihi 250 kata (+${words - 250} kata).</span>`;
            } else {
                const avgSentence = sentences > 0 ? Math.round(words / sentences) : 0;
                absBadge.className = 'abstract-badge ideal';
                absBadge.innerHTML = `${icDoc}<span>Mode Naskah Bab: rata-rata ${avgSentence} kata per kalimat · ±${pages15} halaman A4.</span>`;
            }

            const freq = {};
            wordList.forEach(raw => {
                const clean = raw.toLowerCase().replace(/[^a-z0-9à-ÿ-]/g, '');
                if (clean.length >= 4 && !ID_STOPWORDS.has(clean) && !/^\d+$/.test(clean)) {
                    freq[clean] = (freq[clean] || 0) + 1;
                }
            });
            const top = Object.entries(freq).sort((a, b) => b[1] - a[1]).slice(0, 8);
            const topWrap = document.getElementById('wc-top-words-wrap');
            if (top.length >= 2) {
                topWrap.style.display = 'block';
                document.getElementById('wc-top-words').innerHTML = top
                    .map(([w, c]) => `<span class="top-word-pill">${w.replace(/</g, '&lt;')} <span>×${c}</span></span>`)
                    .join('');
            } else {
                topWrap.style.display = 'none';
            }
        }

        function copyWordCountSummary() {
            const summary = `Statistik Naskah — Total Kata: ${document.getElementById('wc-words').textContent}, Karakter: ${document.getElementById('wc-chars').textContent}, Paragraf: ${document.getElementById('wc-paras').textContent}, Estimasi Halaman A4: ${document.getElementById('wc-pages-15').textContent} (${document.getElementById('wc-pages-20').textContent}).`;
            navigator.clipboard.writeText(summary).then(() => {
                setStatus('status-wc', 'success', '✓ Ringkasan statistik berhasil disalin ke Clipboard.');
            }).catch(() => {});
        }

        function clearWordCount() {
            document.getElementById('wc-textarea').value = '';
            setStatus('status-wc', '', '');
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

        // Init
        initDropzones();
        renderFlowchartEditor();
        renderFlowchartSvg();
        calcSample();
        updateWordCount();
        const initHash = (window.location.hash || '').replace('#', '');
        if (initHash && (document.getElementById('panel-' + initHash) || initHash === 'compress-img')) {
            switchTool(initHash, false);
        }
    </script>
</body>
</html>
