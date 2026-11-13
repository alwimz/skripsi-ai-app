<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SkripsiAI — Ruang Kerja</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/pptxgenjs@3.12.0/dist/pptxgen.bundle.js"></script>
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --bg-page: #ffffff;
            --bg-sidebar: #ffffff;
            --bg-muted: #f3f4f6;
            --bg-subtle: #f9fafb;
            --text-main: #111827;
            --text-secondary: #4b5563;
            --text-muted: #6b7280;
            --border: #e5e7eb;
            --border-light: #f3f4f6;
            --primary: #2e2a5a;
            --primary-hover: #242048;
            --primary-soft: #ede9fe;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-page);
            color: var(--text-main);
            -webkit-font-smoothing: antialiased;
            display: flex;
            min-height: 100vh;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        /* Sidebar — Exact Clean Minimalist Style */
        .sidebar {
            width: 240px;
            background: var(--bg-sidebar);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 40;
        }

        .sidebar-header {
            padding: 16px 18px;
            border-bottom: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .brand-avatar {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: var(--primary);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .brand-info {
            overflow: hidden;
        }

        .brand-name {
            font-size: 14px;
            font-weight: 700;
            color: var(--text-main);
            line-height: 1.25;
            white-space: nowrap;
        }

        .brand-user {
            font-size: 12px;
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar-nav {
            padding: 14px 10px;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 2px;
            overflow-y: auto;
        }

        .nav-btn {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 9px 12px;
            border-radius: 9px;
            border: none;
            background: transparent;
            color: #374151;
            font-size: 13.5px;
            font-weight: 500;
            font-family: inherit;
            cursor: pointer;
            text-align: left;
            white-space: nowrap;
            transition: background 0.12s ease, color 0.12s ease;
        }

        .nav-btn:hover {
            background: var(--bg-subtle);
            color: var(--text-main);
        }

        .nav-btn.active {
            background: var(--bg-muted);
            color: var(--text-main);
            font-weight: 600;
        }

        .nav-btn svg {
            width: 18px;
            height: 18px;
            color: #4b5563;
            flex-shrink: 0;
        }

        .nav-btn.active svg {
            color: var(--text-main);
        }

        .sidebar-footer {
            padding: 12px 16px;
            border-top: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logout-btn {
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

        .logout-btn:hover {
            background: var(--bg-muted);
            color: var(--text-main);
        }

        /* Main Area */
        .main-wrapper {
            flex: 1;
            margin-left: 240px;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Top Bar / Breadcrumb */
        .topbar {
            height: 54px;
            border-bottom: 1px solid var(--border-light);
            padding: 0 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #ffffff;
            position: sticky;
            top: 0;
            z-index: 20;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            color: var(--text-muted);
        }

        .breadcrumb-back {
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            padding: 4px;
            border-radius: 6px;
        }

        .breadcrumb-back:hover {
            background: var(--bg-muted);
        }

        .breadcrumb-current {
            color: var(--text-main);
            font-weight: 600;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .model-select {
            font-family: inherit;
            font-size: 12.5px;
            color: var(--text-secondary);
            background: var(--bg-subtle);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 6px 10px;
            outline: none;
        }

        .token-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12.5px;
            font-weight: 500;
            color: var(--text-secondary);
            background: var(--bg-subtle);
            border: 1px solid var(--border);
            padding: 5px 11px;
            border-radius: 8px;
            cursor: pointer;
        }

        .token-pill:hover {
            background: var(--bg-muted);
        }

        /* Content Container */
        .page-content {
            width: 100%;
            max-width: 780px;
            margin: 0 auto;
            padding: 44px 28px 80px;
        }

        .tab-pane {
            display: none;
        }

        .tab-pane.active {
            display: block;
        }

        .page-eyebrow {
            font-size: 12.5px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 6px;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.025em;
            color: var(--text-main);
            line-height: 1.2;
        }

        .page-subtitle {
            font-size: 14.5px;
            color: var(--text-muted);
            margin-top: 6px;
            margin-bottom: 28px;
            line-height: 1.55;
        }

        /* Clean Card */
        .card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 24px;
        }

        .card-flush {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
        }

        .card-header-bar {
            padding: 14px 20px;
            border-bottom: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 13.5px;
            font-weight: 600;
            color: var(--text-main);
        }

        .count-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            border-radius: 999px;
            background: var(--bg-muted);
            color: var(--text-secondary);
            font-size: 11.5px;
            font-weight: 600;
            margin-left: 6px;
        }

        /* Form Elements — Clean & Spacious */
        .form-group {
            margin-bottom: 18px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 7px;
        }

        .form-label span.req {
            color: #ef4444;
        }

        .form-label span.opt {
            font-weight: 400;
            color: var(--text-muted);
        }

        .form-input, .form-select, .form-textarea {
            width: 100%;
            font-family: inherit;
            font-size: 14px;
            color: var(--text-main);
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 11px 14px;
            outline: none;
            transition: border-color 0.15s ease;
        }

        .form-input:focus, .form-select:focus, .form-textarea:focus {
            border-color: var(--primary);
        }

        .form-textarea {
            min-height: 110px;
            resize: vertical;
            line-height: 1.55;
        }

        .card-footer-bar {
            margin-top: 20px;
            padding-top: 18px;
            border-top: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .footer-meta {
            font-size: 11.5px;
            font-weight: 600;
            letter-spacing: 0.04em;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        /* Buttons */
        .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: var(--primary);
            color: #ffffff;
            border: none;
            border-radius: 10px;
            padding: 11px 22px;
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: background 0.15s ease;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-full {
            width: 100%;
            padding: 13px 20px;
        }

        .btn-outline {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            background: #ffffff;
            color: var(--text-main);
            border: 1px solid var(--border);
            border-radius: 9px;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 500;
            font-family: inherit;
            cursor: pointer;
        }

        .btn-outline:hover {
            background: var(--bg-subtle);
        }

        /* Stepper Pills (1 Pilih skripsi  2 Buat slide  3 Rapikan & latihan  4 Unduh) */
        .stepper {
            display: flex;
            align-items: center;
            gap: 18px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .step-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--text-muted);
            font-weight: 500;
            background: none;
            border: none;
            font-family: inherit;
            cursor: pointer;
        }

        .step-num-circle {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: var(--bg-muted);
            color: var(--text-muted);
            font-size: 11.5px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .step-pill.active {
            color: var(--text-main);
            font-weight: 600;
        }

        .step-pill.active .step-num-circle {
            background: var(--primary);
            color: #ffffff;
        }

        /* Project Selection Row (Skripsita Style) */
        .project-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 20px;
            border-bottom: 1px solid var(--border-light);
            cursor: pointer;
            transition: background 0.12s ease;
        }

        .project-row:last-child {
            border-bottom: none;
        }

        .project-row:hover {
            background: var(--bg-subtle);
        }

        .project-row-left {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
        }

        .project-initial {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--primary-soft);
            color: var(--primary);
            font-size: 13.5px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .project-row-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-main);
            line-height: 1.35;
        }

        .project-row-meta {
            font-size: 12.5px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        /* Dashboard Grid */
        .dash-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
            margin-top: 20px;
        }

        .dash-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            cursor: pointer;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }

        .dash-card:hover {
            border-color: #d1d5db;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
        }

        .dash-icon {
            width: 38px;
            height: 38px;
            border-radius: 9px;
            background: var(--bg-muted);
            color: var(--text-main);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .dash-card h3 {
            font-size: 14.5px;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 4px;
        }

        .dash-card p {
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.45;
        }

        /* Interactive PPT Studio (16:9 Slide Canvas) */
        .ppt-canvas {
            border-radius: 14px;
            padding: 36px 40px;
            min-height: 340px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border: 1px solid var(--border);
            transition: all 0.2s ease;
        }

        .ppt-theme-navy { background: #1e1b4b; color: #ffffff; }
        .ppt-theme-light { background: #ffffff; color: #111827; }
        .ppt-theme-slate { background: #0f172a; color: #f8fafc; }
        .ppt-theme-emerald { background: #064e3b; color: #ecfdf5; }

        .theme-dot {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 2px solid #e5e7eb;
            cursor: pointer;
        }

        .theme-dot.active {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }

        /* Simulasi Sidang Score Bar */
        .score-row {
            margin-bottom: 14px;
        }

        .score-row-top {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 6px;
        }

        .score-bar-track {
            height: 8px;
            background: var(--bg-muted);
            border-radius: 999px;
            overflow: hidden;
        }

        .score-bar-fill {
            height: 100%;
            background: var(--primary);
            border-radius: 999px;
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: 1px solid var(--border);
            border-radius: 7px;
            padding: 6px;
            cursor: pointer;
        }

        @media (max-width: 840px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.2s ease;
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .main-wrapper {
                margin-left: 0;
            }
            .mobile-menu-btn {
                display: inline-flex;
            }
            .form-row, .dash-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="/" class="brand-avatar">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/></svg>
            </a>
            <div class="brand-info">
                <div class="brand-name">SkripsiAI</div>
                <div class="brand-user">{{ $user->name }}</div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <button type="button" class="nav-btn active" id="nav-dashboard" onclick="openTab('dashboard', 'Dashboard')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                <span>Dashboard</span>
            </button>

            <button type="button" class="nav-btn" id="nav-projects" onclick="openTab('projects', 'Proyek Skripsi')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                <span>Proyek Skripsi</span>
            </button>

            <button type="button" class="nav-btn" id="nav-titles" onclick="openTab('titles', 'Cari Judul')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <span>Cari Judul</span>
            </button>

            <button type="button" class="nav-btn" id="nav-ppt" onclick="openTab('ppt', 'PPT Sidang')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                <span>PPT Sidang</span>
            </button>

            <button type="button" class="nav-btn" id="nav-defense" onclick="openTab('defense', 'Simulasi Sidang')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                <span>Simulasi Sidang</span>
            </button>

            <button type="button" class="nav-btn" id="nav-journals" onclick="openTab('journals', 'Cari Jurnal')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                <span>Cari Jurnal</span>
            </button>

            <button type="button" class="nav-btn" id="nav-paraphrase" onclick="openTab('paraphrase', 'Parafrase')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                <span>Parafrase</span>
            </button>

            <button type="button" class="nav-btn" id="nav-qa" onclick="openTab('qa', 'Diskusi & Tugas')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                <span>Diskusi & Tugas</span>
            </button>

            <button type="button" class="nav-btn" id="nav-apikeys" onclick="openTab('apikeys', 'Pengaturan API')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                <span>Pengaturan API</span>
            </button>
        </nav>

        <div class="sidebar-footer">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="logout-btn" title="Keluar">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    <span>Keluar</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="main-wrapper">
        <!-- Topbar -->
        <header class="topbar">
            <div class="breadcrumb">
                <button type="button" class="mobile-menu-btn" onclick="document.getElementById('sidebar').classList.toggle('open')">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <button type="button" class="breadcrumb-back" onclick="openTab('dashboard', 'Dashboard')">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                </button>
                <span>Ruang Kerja</span>
                <span>›</span>
                <span class="breadcrumb-current" id="breadcrumb-label">Dashboard</span>
            </div>

            <div class="topbar-right">
                <select id="global-model" class="model-select">
                    <option value="auto">Mode Otomatis (AI)</option>
                    <option value="gemini">Google Gemini</option>
                    <option value="groq">Groq Llama 3.3</option>
                </select>
                <button type="button" class="token-pill" onclick="claimFreeTokens()" title="Klik untuk tambah token gratis">
                    <span>Token: <strong id="credit-count">{{ $user->credits }}</strong></span>
                    <span style="color: var(--primary); font-weight: 700;">+</span>
                </button>
            </div>
        </header>

        <main class="page-content">
            @if(session('success'))
                <div style="padding: 12px 16px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; color: #166534; font-size: 13.5px; margin-bottom: 20px;">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div style="padding: 12px 16px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; color: #991b1b; font-size: 13.5px; margin-bottom: 20px;">
                    {{ session('error') }}
                </div>
            @endif

            <!-- ========================================================= -->
            <!-- 1. TAB DASHBOARD -->
            <!-- ========================================================= -->
            <section class="tab-pane active" id="pane-dashboard">
                <div class="page-eyebrow">Ruang Kerja Akademik</div>
                <h1 class="page-title">Halo, {{ explode(' ', trim($user->name))[0] }}</h1>
                <p class="page-subtitle">Pilih tahapan skripsi yang ingin kamu kerjakan hari ini, mulai dari mencari ide judul, menulis proposal & skripsi Bab 1–5, hingga persiapan sidang.</p>

                <!-- Proyek Terakhir -->
                @if($history->count() > 0)
                    <div class="card-flush" style="margin-bottom: 24px;">
                        <div class="card-header-bar">
                            <div>Proyek Skripsi Terbaru <span class="count-badge">{{ $history->count() }}</span></div>
                            <button type="button" class="btn-outline" style="padding: 5px 11px; font-size: 12px;" onclick="openTab('projects', 'Proyek Skripsi')">+ Proyek Baru</button>
                        </div>
                        @foreach($history->take(3) as $proj)
                            <a href="{{ route('app.index', ['id' => $proj->id]) }}" class="project-row">
                                <div class="project-row-left">
                                    <div class="project-initial">{{ strtoupper(substr($proj->judul, 0, 1)) }}</div>
                                    <div>
                                        <div class="project-row-title">{{ $proj->judul }}</div>
                                        <div class="project-row-meta">{{ $proj->prodi }} · {{ $proj->metode_penelitian }} · {{ $proj->updated_at->diffForHumans() }}</div>
                                    </div>
                                </div>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                            </a>
                        @endforeach
                    </div>
                @endif

                <div class="dash-grid">
                    <div class="dash-card" onclick="openTab('projects', 'Proyek Skripsi')">
                        <div class="dash-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        </div>
                        <div>
                            <h3>Tulis Proposal & Skripsi</h3>
                            <p>Susun Bab 1–3 (Proposal) atau Bab 1–5 lengkap sesuai pedoman kampus.</p>
                        </div>
                    </div>

                    <div class="dash-card" onclick="openTab('titles', 'Cari Judul')">
                        <div class="dash-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        </div>
                        <div>
                            <h3>Cari Judul Skripsi</h3>
                            <p>Dapatkan saran judul penelitian sesuai topik, jurusan, dan metodologi.</p>
                        </div>
                    </div>

                    <div class="dash-card" onclick="openTab('ppt', 'PPT Sidang')">
                        <div class="dash-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/></svg>
                        </div>
                        <div>
                            <h3>PPT Sidang</h3>
                            <p>Buat slide sidang otomatis dari proyek skripsimu, edit tema, lalu unduh .pptx.</p>
                        </div>
                    </div>

                    <div class="dash-card" onclick="openTab('defense', 'Simulasi Sidang')">
                        <div class="dash-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                        </div>
                        <div>
                            <h3>Simulasi Sidang</h3>
                            <p>Latihan tanya-jawab dengan penguji AI lengkap dengan skor dan saran jawaban.</p>
                        </div>
                    </div>

                    <div class="dash-card" onclick="openTab('journals', 'Cari Jurnal')">
                        <div class="dash-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                        </div>
                        <div>
                            <h3>Cari Jurnal & Download PDF</h3>
                            <p>Temukan jurnal open-access beserta link unduh PDF asli dan sitasi APA.</p>
                        </div>
                    </div>

                    <div class="dash-card" onclick="openTab('paraphrase', 'Parafrase')">
                        <div class="dash-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                        </div>
                        <div>
                            <h3>Parafrase Akademik</h3>
                            <p>Ubah kalimat kutipan menjadi bahasa ilmiah baku tanpa mengubah makna.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ========================================================= -->
            <!-- 2. TAB PROYEK SKRIPSI (PROPOSAL BAB 1-3 & SKRIPSI BAB 1-5) -->
            <!-- ========================================================= -->
            <section class="tab-pane" id="pane-projects">
                <div class="page-eyebrow">Penulisan Terstruktur</div>
                <h1 class="page-title">Proyek Skripsi & Proposal</h1>
                <p class="page-subtitle">Di dalam ruang kerja proyek, kamu bisa menyusun sampai Bab 3 untuk Seminar Proposal atau melanjutkan sampai Bab 5 untuk Skripsi lengkap.</p>

                <!-- Form Buat Proyek Baru (Mantra Riset Complete Setup) -->
                <form action="{{ route('project.start') }}" method="POST" enctype="multipart/form-data" onsubmit="const btn = this.querySelector('button[type=submit]'); btn.disabled = true; btn.innerText = 'Membuka Studio Skripsi...';">
                    @csrf

                    <!-- Card 1: Judul, Logo Kampus, & Identitas -->
                    <div class="card" style="margin-bottom: 16px;">
                        <div style="display: grid; grid-template-columns: 1fr 180px; gap: 16px; align-items: stretch; margin-bottom: 18px;">
                            <div>
                                <label class="form-label">Judul Penelitian <span class="req">*</span></label>
                                <textarea name="judul" id="input-project-judul" class="form-textarea" style="min-height: 92px;" placeholder="Gunakan judul yang sesuai dengan fokus penelitianmu (cth: Pengaruh Literasi Digital dan Regulasi Diri terhadap Kesiapan Kerja Mahasiswa...)" required></textarea>
                            </div>
                            <div>
                                <label class="form-label">Logo Kampus <span class="opt">(opsional)</span></label>
                                <label style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 92px; border: 1px dashed var(--border); border-radius: 10px; background: var(--bg-subtle); cursor: pointer; text-align: center; padding: 8px; overflow: hidden;">
                                    <img id="new-proj-logo-preview" src="" alt="" style="display:none; max-height:46px; max-width:90px; object-fit:contain; margin-bottom:4px;">
                                    <span id="new-proj-logo-label" style="font-size: 12px; font-weight: 600; color: var(--text-secondary);">Unggah Logo</span>
                                    <span style="font-size: 11px; color: var(--text-muted);">PNG/JPG, maks 2 MB</span>
                                    <input type="file" name="logo_kampus" accept="image/png,image/jpeg,image/webp" style="display:none;" onchange="if(this.files[0]){ const r=new FileReader(); r.onload=e=>{ const img=document.getElementById('new-proj-logo-preview'); img.src=e.target.result; img.style.display='block'; document.getElementById('new-proj-logo-label').innerText=this.files[0].name.substring(0,16); }; r.readAsDataURL(this.files[0]); }">
                                </label>
                            </div>
                        </div>

                        <div class="form-row">
                            <div>
                                <label class="form-label">Nama lengkap</label>
                                <input type="text" name="nama" class="form-input" value="{{ strtoupper($user->name) }}" placeholder="Nama Lengkap Mahasiswa">
                            </div>
                            <div>
                                <label class="form-label">NIM</label>
                                <input type="text" name="nim" class="form-input" placeholder="Nomor Induk Mahasiswa">
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px;">
                            <div>
                                <label class="form-label">Nama kampus</label>
                                <input type="text" name="kampus" class="form-input" placeholder="Contoh: UNIVERSITAS INDONESIA">
                            </div>
                            <div>
                                <label class="form-label">Jurusan / Program studi <span class="req">*</span></label>
                                <input type="text" name="prodi" id="input-project-prodi" class="form-input" placeholder="Contoh: MANAJEMEN" required>
                            </div>
                            <div>
                                <label class="form-label">Fakultas</label>
                                <input type="text" name="fakultas" class="form-input" placeholder="Masukkan nama fakultas">
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: Jenis dan Metode Penelitian -->
                    <div class="card" style="margin-bottom: 16px;">
                        <div style="font-size: 14px; font-weight: 600; color: var(--text-main); margin-bottom: 12px;">Jenis dan metode penelitian</div>

                        <label class="form-label" style="font-size: 12.5px; color: var(--text-secondary);">Jenis penelitian</label>
                        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 18px;">
                            @foreach(['Skripsi (S1)', 'Tesis (S2)', 'Disertasi (S3)', 'Ubah Skripsi → Artikel'] as $idx => $jp)
                                <label style="display: flex; align-items: center; gap: 8px; padding: 10px 12px; border: 1px solid var(--border); border-radius: 9px; font-size: 13px; font-weight: 500; cursor: pointer; background: #fff;">
                                    <input type="radio" name="jenis_penelitian" value="{{ $jp }}" {{ $idx === 0 ? 'checked' : '' }}>
                                    <span>{{ $jp }}</span>
                                </label>
                            @endforeach
                        </div>

                        <label class="form-label" style="font-size: 12.5px; color: var(--text-secondary);">Metode penelitian <span class="req">*</span></label>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;">
                            @php
                                $metodeOptions = [
                                    ['val' => 'Kualitatif (Studi Kasus / Deskriptif)', 'title' => 'Kualitatif', 'sub' => 'Wawancara, observasi, studi kasus'],
                                    ['val' => 'Kuantitatif (Survei / Regresi)', 'title' => 'Kuantitatif', 'sub' => 'Survei kuesioner, regresi, SEM'],
                                    ['val' => 'Kuantitatif — Data Sekunder', 'title' => 'Kuantitatif — Data Sekunder', 'sub' => 'BPS, deret waktu, laporan arsip'],
                                    ['val' => 'Studi Pustaka (Library Research)', 'title' => 'Studi Pustaka', 'sub' => 'Library Research / kajian literatur'],
                                    ['val' => 'PTK (Penelitian Tindakan Kelas)', 'title' => 'PTK', 'sub' => 'Penelitian Tindakan Kelas'],
                                    ['val' => 'R&D / Pengembangan Sistem', 'title' => 'R&D', 'sub' => 'Pengembangan produk / sistem IT'],
                                    ['val' => 'Mixed Method', 'title' => 'Mixed Method', 'sub' => 'Kombinasi kualitatif & kuantitatif'],
                                    ['val' => 'Hukum Normatif', 'title' => 'Hukum Normatif', 'sub' => 'Kajian perundang-undangan & doktrin'],
                                    ['val' => 'Hukum Empiris', 'title' => 'Hukum Empiris', 'sub' => 'Sosiologi hukum & efektivitas lapangan'],
                                    ['val' => 'Eksperimen / Rekayasa', 'title' => 'Eksperimen / Rekayasa', 'sub' => 'Bengkel, alat, uji laboratorium'],
                                ];
                            @endphp
                            @foreach($metodeOptions as $mIdx => $mOpt)
                                <label style="display: flex; align-items: flex-start; gap: 8px; padding: 10px 12px; border: 1px solid var(--border); border-radius: 9px; cursor: pointer; background: #fff;">
                                    <input type="radio" name="metode_penelitian" value="{{ $mOpt['val'] }}" {{ $mIdx === 1 ? 'checked' : '' }} style="margin-top: 3px;">
                                    <div>
                                        <div style="font-size: 13px; font-weight: 600; color: var(--text-main);">{{ $mOpt['title'] }}</div>
                                        <div style="font-size: 11.5px; color: var(--text-muted); line-height: 1.35;">{{ $mOpt['sub'] }}</div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Card 3: Tahap Pengerjaan -->
                    <div class="card" style="margin-bottom: 16px;">
                        <div style="font-size: 14px; font-weight: 600; color: var(--text-main);">Tahap pengerjaan</div>
                        <div style="font-size: 12.5px; color: var(--text-muted); margin-bottom: 12px;">Pilih sesuai kesiapan penelitianmu (dapat dilanjutkan kapan saja di ruang kerja).</div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <label style="display: flex; align-items: center; gap: 9px; padding: 12px 14px; border: 1px solid var(--border); border-radius: 10px; font-size: 13.5px; font-weight: 600; cursor: pointer;">
                                <input type="radio" name="tahap_pengerjaan" value="Proposal (Bab I–III)">
                                <span>Proposal (Bab I–III)</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 9px; padding: 12px 14px; border: 1px solid var(--border); border-radius: 10px; font-size: 13.5px; font-weight: 600; cursor: pointer;">
                                <input type="radio" name="tahap_pengerjaan" value="Skripsi Penuh (Bab I–V)" checked>
                                <span>Skripsi Penuh (Bab I–V)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Card 4: Pedoman Kampus (Opsional) -->
                    <div class="card" style="margin-bottom: 16px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                            <div style="font-size: 14px; font-weight: 600; color: var(--text-main);">Pedoman kampus <span style="font-size: 11px; font-weight: 500; color: var(--text-muted); margin-left: 6px;">OPSIONAL</span></div>
                        </div>
                        <input type="file" name="pedoman_file" class="form-input" accept=".pdf,.docx,.doc,.txt" style="padding: 9px 12px; font-size: 13px;">
                        <div style="font-size: 12px; color: var(--text-muted); margin-top: 6px;">Pilih berkas buku pedoman penulisan kampus (.pdf / .docx, maks 15MB).</div>
                    </div>

                    <!-- Card 5: Struktur Bab Kustom (Opsional) -->
                    <div class="card" style="margin-bottom: 16px;">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <div style="font-size: 14px; font-weight: 600; color: var(--text-main);">Struktur bab kustom <span style="font-size: 11px; font-weight: 500; color: var(--text-muted); margin-left: 6px;">OPSIONAL</span></div>
                                <div style="font-size: 12.5px; color: var(--text-muted); margin-top: 2px;">Atur subbab secara manual jika pedoman kampusmu memiliki urutan khusus. Jika tidak diaktifkan, sistem menggunakan struktur standar Bab I–V.</div>
                            </div>
                            <input type="checkbox" onchange="document.getElementById('custom-structure-wrap').style.display = this.checked ? 'block' : 'none';" style="width: 18px; height: 18px; cursor: pointer;">
                        </div>
                        <div id="custom-structure-wrap" style="display: none; margin-top: 14px;">
                            <textarea name="struktur_kustom" class="form-textarea" style="min-height: 85px;" placeholder="Contoh: Bab I terdiri dari 1.1 Konteks Penelitian, 1.2 Fokus Penelitian, 1.3 Tujuan Penelitian..."></textarea>
                        </div>
                    </div>

                    <!-- Card 6: Data Awal Penelitian (Opsional) -->
                    <div class="card" style="margin-bottom: 16px;">
                        <div style="font-size: 14px; font-weight: 600; color: var(--text-main);">Data awal penelitian <span style="font-size: 11px; font-weight: 500; color: var(--text-muted); margin-left: 6px;">OPSIONAL</span></div>
                        <div style="font-size: 12.5px; color: var(--text-muted); margin: 4px 0 12px;">Tambahkan kondisi nyata, angka, atau hasil observasi awal untuk mendukung latar belakang penelitian.</div>
                        <textarea name="data_awal" class="form-textarea" style="min-height: 85px;" placeholder="Contoh: Di SMAN 1 X, 62% siswa belum memanfaatkan e-library; rata-rata skor literasi digital 48/100 (survei 2024)."></textarea>
                    </div>

                    <!-- Card 7: Pengaturan Sitasi -->
                    <div class="card" style="margin-bottom: 16px;">
                        <div style="font-size: 14px; font-weight: 600; color: var(--text-main);">Pengaturan sitasi</div>
                        <div style="font-size: 12.5px; color: var(--text-muted); margin: 2px 0 14px;">Sesuaikan referensi dan format penulisan dengan kebutuhan penelitianmu.</div>

                        <div class="form-row">
                            <div>
                                <label class="form-label">Tahun terbit minimum</label>
                                <select name="tahun_min" class="form-select">
                                    <option value="2020">Semua tahun (2018–2026)</option>
                                    <option value="2025">2025</option>
                                    <option value="2024">2024</option>
                                    <option value="2023" selected>2023</option>
                                    <option value="2022">2022</option>
                                    <option value="2021">2021</option>
                                    <option value="2020">2020</option>
                                    <option value="2019">2019</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Asal referensi</label>
                                <select name="asal_referensi" class="form-select">
                                    <option value="Indonesia + Internasional">Indonesia + Internasional</option>
                                    <option value="Indonesia">Indonesia (Nasional)</option>
                                    <option value="Internasional">Internasional</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div>
                                <label class="form-label">Gaya sitasi</label>
                                <select name="gaya_sitasi" class="form-select">
                                    <option value="APA 7th">APA 7th</option>
                                    <option value="IEEE">IEEE</option>
                                    <option value="Harvard">Harvard</option>
                                    <option value="Chicago">Chicago</option>
                                    <option value="Vancouver">Vancouver</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Bahasa penulisan</label>
                                <select name="bahasa_penulisan" class="form-select">
                                    <option value="Indonesia">Indonesia</option>
                                    <option value="Inggris">Inggris (English)</option>
                                </select>
                            </div>
                        </div>

                        <label class="form-label">Sumber referensi</label>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;">
                            <label style="display: flex; align-items: center; gap: 8px; padding: 10px 12px; border: 1px solid var(--border); border-radius: 9px; font-size: 13px; font-weight: 500; cursor: pointer;">
                                <input type="checkbox" name="sumber_referensi[]" value="Umum" checked>
                                <span>Umum</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; padding: 10px 12px; border: 1px solid var(--border); border-radius: 9px; font-size: 13px; font-weight: 500; cursor: pointer;">
                                <input type="checkbox" name="sumber_referensi[]" value="Terindeks Sinta" checked>
                                <span>Terindeks Sinta</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; padding: 10px 12px; border: 1px solid var(--border); border-radius: 9px; font-size: 13px; font-weight: 500; cursor: pointer;">
                                <input type="checkbox" name="sumber_referensi[]" value="Terindeks Scopus">
                                <span>Terindeks Scopus</span>
                            </label>
                        </div>
                    </div>

                    <!-- Card 8: Unggah Artikel atau Buku Referensi Wajib (Opsional) -->
                    <div class="card" style="margin-bottom: 24px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                            <div style="font-size: 14px; font-weight: 600; color: var(--text-main);">Unggah artikel atau buku <span style="font-size: 11px; font-weight: 500; color: var(--text-muted); margin-left: 6px;">OPSIONAL</span></div>
                            <span style="font-size: 12px; color: var(--text-muted);">Maks 5 berkas</span>
                        </div>
                        <div style="font-size: 12.5px; color: var(--text-muted); margin-bottom: 12px;">
                            Untuk sumber yang wajib kamu pakai — arahan pembimbing, buku teori, atau jurnal khusus. Sistem membaca isinya dan memasukkannya ke dalam naskah serta Daftar Pustaka.
                        </div>
                        <input type="file" name="referensi_files[]" multiple accept=".pdf,.docx,.doc,.txt" class="form-input" style="padding: 9px 12px; font-size: 13px;">

                        <div class="card-footer-bar">
                            <span class="footer-meta">SIAP DISUSUN DI STUDIO PENULISAN</span>
                            <button type="submit" class="btn-primary">Buat Proyek & Buka Studio →</button>
                        </div>
                    </div>
                </form>

                <!-- Daftar Proyek Tersimpan -->
                <div class="card-flush">
                    <div class="card-header-bar">
                        <div>Daftar Skripsi Saya <span class="count-badge">{{ $history->count() }}</span></div>
                    </div>
                    @forelse($history as $proj)
                        @php
                            $doneCount = 0;
                            foreach(['bab1','bab2','bab3','bab4','bab5'] as $b) {
                                if (!empty($proj->{$b.'_content'})) $doneCount++;
                            }
                        @endphp
                        <div class="project-row" style="cursor: default;">
                            <a href="{{ route('app.index', ['id' => $proj->id]) }}" class="project-row-left" style="flex: 1;">
                                <div class="project-initial">{{ strtoupper(substr($proj->judul, 0, 1)) }}</div>
                                <div>
                                    <div class="project-row-title">{{ $proj->judul }}</div>
                                    <div class="project-row-meta">{{ $proj->prodi }} · {{ $proj->metode_penelitian }} · {{ $doneCount }}/5 Bab ditulis</div>
                                </div>
                            </a>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <a href="{{ route('app.index', ['id' => $proj->id]) }}" class="btn-outline" style="padding: 6px 12px; font-size: 12.5px;">Buka</a>
                                <form action="{{ route('project.delete', ['id' => $proj->id]) }}" method="POST" onsubmit="return confirm('Hapus proyek ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-outline" style="padding: 6px 10px; font-size: 12.5px; color: #dc2626; border-color: #fecaca;">Hapus</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div style="padding: 32px; text-align: center; color: var(--text-muted); font-size: 13.5px;">
                            Belum ada proyek skripsi. Isi form di atas untuk memulai.
                        </div>
                    @endforelse
                </div>
            </section>

            <!-- ========================================================= -->
            <!-- 3. TAB CARI JUDUL SKRIPSI (EXACT SKRIPSITA STYLE) -->
            <!-- ========================================================= -->
            <section class="tab-pane" id="pane-titles">
                <div class="page-eyebrow">Eksplorasi Topik · AI Akademik</div>
                <h1 class="page-title">Cari Judul Skripsi</h1>
                <p class="page-subtitle">Isi topik dan metodologi — sistem langsung sarankan judul terbaik sesuai bidangmu dalam hitungan detik.</p>

                <div class="card">
                    <div class="form-group">
                        <label class="form-label">Topik atau Bidang Penelitian <span class="req">*</span></label>
                        <input type="text" id="title-topik" class="form-input" placeholder="cth: machine learning, manajemen SDM, hukum perdata...">
                    </div>

                    <div class="form-row" style="margin-bottom: 0;">
                        <div>
                            <label class="form-label">Metodologi <span class="req">*</span></label>
                            <select id="title-metode" class="form-select">
                                <option value="">Pilih Metodologi</option>
                                <option value="Kuantitatif (Survei / Regresi)">Kuantitatif</option>
                                <option value="Kualitatif (Studi Kasus / Deskriptif)">Kualitatif</option>
                                <option value="R&D / Pengembangan Sistem (Waterfall / Prototype)">R&D / Pengembangan Sistem</option>
                                <option value="Yuridis Normatif / Empiris">Yuridis Normatif / Empiris</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Program Studi <span class="opt">(opsional)</span></label>
                            <input type="text" id="title-prodi" class="form-input" placeholder="cth: Informatika, Manajemen...">
                        </div>
                    </div>

                    <div class="card-footer-bar">
                        <span class="footer-meta">REKOMENDASI JUDUL TERSTRUKTUR</span>
                        <button type="button" id="btn-find-titles" class="btn-primary" onclick="handleFindTitles()">Temukan Judul →</button>
                    </div>
                </div>

                <!-- Hasil Judul -->
                <div id="titles-result-box" style="display: none; margin-top: 20px;" class="card-flush">
                    <div class="card-header-bar">
                        <span>Rekomendasi Judul Skripsi</span>
                        <span style="font-size: 12px; font-weight: 400; color: var(--text-muted);">Klik "Gunakan Judul" untuk langsung menulis</span>
                    </div>
                    <div id="titles-list"></div>
                </div>
            </section>

            <!-- ========================================================= -->
            <!-- 4. TAB PPT SIDANG (EXACT 4-STEP FLOW + LIVE PPT EDITOR) -->
            <!-- ========================================================= -->
            <section class="tab-pane" id="pane-ppt">
                <h1 class="page-title">PPT Sidang</h1>
                <p class="page-subtitle">Slide sidang dari isi skripsimu. Tinggal rapikan, latihan presentasi, lalu unduh.</p>

                <!-- 4-Step Indicator -->
                <div class="stepper">
                    <button type="button" class="step-pill active" id="ppt-step-btn-1" onclick="setPptStep(1)">
                        <span class="step-num-circle">1</span>
                        <span>Pilih skripsi</span>
                    </button>
                    <button type="button" class="step-pill" id="ppt-step-btn-2" onclick="if(selectedPptProject.title || document.getElementById('ppt-file-input').files.length) setPptStep(2)">
                        <span class="step-num-circle">2</span>
                        <span>Buat slide</span>
                    </button>
                    <button type="button" class="step-pill" id="ppt-step-btn-3" onclick="if(pptSlides.length) setPptStep(3)">
                        <span class="step-num-circle">3</span>
                        <span>Rapikan & latihan</span>
                    </button>
                    <button type="button" class="step-pill" id="ppt-step-btn-4" onclick="if(pptSlides.length) downloadPptxFile()">
                        <span class="step-num-circle">4</span>
                        <span>Unduh</span>
                    </button>
                </div>

                <!-- STEP 1: Pilih Skripsi atau Upload File -->
                <div id="ppt-view-step-1">
                    <div class="card-flush" style="margin-bottom: 18px;">
                        <div class="card-header-bar">
                            <div>Pilih skripsi <span class="count-badge">{{ $history->count() }}</span></div>
                        </div>
                        @forelse($history as $proj)
                            <div class="project-row" onclick="chooseProjectForPpt({{ $proj->id }}, '{{ addslashes($proj->judul) }}')">
                                <div class="project-row-left">
                                    <div class="project-initial">{{ strtoupper(substr($proj->judul, 0, 1)) }}</div>
                                    <div>
                                        <div class="project-row-title">{{ $proj->judul }}</div>
                                        <div class="project-row-meta">{{ $proj->updated_at->diffForHumans() }}</div>
                                    </div>
                                </div>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                            </div>
                        @empty
                            <div style="padding: 24px; text-align: center; color: var(--text-muted); font-size: 13.5px;">
                                Belum ada proyek di akunmu. Kamu bisa mengunggah file skripsi (.pdf / .docx) di bawah ini.
                            </div>
                        @endforelse
                    </div>

                    <!-- Opsi Upload File PDF/DOCX Langsung -->
                    <div class="card">
                        <div class="form-label" style="margin-bottom: 8px;">Atau unggah file skripsi / proposal (.pdf, .docx)</div>
                        <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 14px;">AI akan merangkum isi file dokumenmu dan menyusun 9 slide presentasi sidang secara otomatis.</p>
                        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                            <input type="file" id="ppt-file-input" class="form-input" accept=".pdf,.docx,.doc,.txt" style="flex: 1; padding: 8px 12px; font-size: 13px;" onchange="onUploadFileForPpt(this)">
                        </div>
                    </div>
                </div>

                <!-- STEP 2: Buat Slide (Matches Screenshot 2) -->
                <div id="ppt-view-step-2" style="display: none;">
                    <div class="card">
                        <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 20px;">
                            <div class="project-initial" id="ppt-selected-initial">S</div>
                            <div>
                                <div style="font-size: 11px; font-weight: 600; letter-spacing: 0.05em; color: var(--text-muted); text-transform: uppercase;" id="ppt-selected-source">SKRIPSI</div>
                                <div style="font-size: 15.5px; font-weight: 700; color: var(--text-main);" id="ppt-selected-title">-</div>
                            </div>
                        </div>

                        <button type="button" id="btn-generate-ppt" class="btn-primary btn-full" onclick="handleGeneratePpt()">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                            <span>Buat slide sidang</span>
                        </button>

                        <p style="font-size: 12.5px; color: var(--text-muted); margin-top: 12px;">
                            Sekitar 10–30 detik. Slide disusun dari isi skripsimu, lengkap dengan catatan bicara, dan bisa kamu ubah setelahnya.
                        </p>
                    </div>
                </div>

                <!-- STEP 3 & 4: Rapikan, Edit Tema, & Unduh .PPTX -->
                <div id="ppt-view-step-3" style="display: none;">
                    <!-- Toolbar Tema & Download -->
                    <div class="card" style="padding: 14px 18px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span style="font-size: 13px; font-weight: 600; color: var(--text-secondary);">Tema Slide:</span>
                            <button type="button" class="theme-dot active" style="background: #1e1b4b;" onclick="setPptTheme('navy', this)" title="Academic Navy"></button>
                            <button type="button" class="theme-dot" style="background: #ffffff;" onclick="setPptTheme('light', this)" title="Clean White"></button>
                            <button type="button" class="theme-dot" style="background: #0f172a;" onclick="setPptTheme('slate', this)" title="Modern Slate"></button>
                            <button type="button" class="theme-dot" style="background: #064e3b;" onclick="setPptTheme('emerald', this)" title="Emerald Campus"></button>
                        </div>

                        <div style="display: flex; align-items: center; gap: 8px;">
                            <button type="button" class="btn-outline" onclick="addNewSlide()">+ Tambah Slide</button>
                            <button type="button" class="btn-primary" style="padding: 8px 16px; font-size: 13px;" onclick="downloadPptxFile()">
                                Unduh PowerPoint (.pptx)
                            </button>
                        </div>
                    </div>

                    <!-- 16:9 Editable PowerPoint Canvas -->
                    <div id="ppt-canvas-box" class="ppt-canvas ppt-theme-navy">
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: center; opacity: 0.7; font-size: 12px; font-weight: 600; margin-bottom: 14px;">
                                <span id="slide-section-label" contenteditable="true" onblur="syncCurrentSlideEdit()">BAGIAN SLIDE</span>
                                <span id="slide-counter-label">Slide 1 / 9</span>
                            </div>
                            <h2 id="slide-title-edit" contenteditable="true" onblur="syncCurrentSlideEdit()" style="font-size: 24px; font-weight: 700; line-height: 1.3; margin-bottom: 8px; outline: none;">Judul Slide</h2>
                            <div id="slide-subtitle-edit" contenteditable="true" onblur="syncCurrentSlideEdit()" style="font-size: 14px; opacity: 0.8; margin-bottom: 20px; outline: none;">Subjudul</div>
                            <ul id="slide-bullets-edit" contenteditable="true" onblur="syncCurrentSlideEdit()" style="padding-left: 20px; font-size: 15px; line-height: 1.75; outline: none;"></ul>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 20px; margin-top: 24px; border-top: 1px solid rgba(156,163,175,0.25); font-size: 12px; opacity: 0.75;">
                            <span>Klik teks pada slide untuk mengedit isi secara langsung</span>
                            <div style="display: flex; gap: 6px;">
                                <button type="button" onclick="prevSlide()" style="padding: 4px 10px; border-radius: 6px; border: 1px solid rgba(156,163,175,0.4); background: transparent; color: inherit; cursor: pointer;">← Sebelumnya</button>
                                <button type="button" onclick="nextSlide()" style="padding: 4px 10px; border-radius: 6px; border: 1px solid rgba(156,163,175,0.4); background: transparent; color: inherit; cursor: pointer;">Selanjutnya →</button>
                            </div>
                        </div>
                    </div>

                    <!-- Catatan Bicara Presenter (Editable) -->
                    <div class="card" style="margin-top: 14px;">
                        <label class="form-label">Catatan Bicara (Speaker Notes) — bisa diedit</label>
                        <textarea id="slide-notes-edit" class="form-textarea" style="min-height: 80px;" oninput="syncCurrentSlideEdit()"></textarea>
                    </div>
                </div>
            </section>

            <!-- ========================================================= -->
            <!-- 5. TAB SIMULASI SIDANG (PILIH SKRIPSI / UPLOAD PDF -> JAWAB 1-5 -> SKOR & SARAN) -->
            <!-- ========================================================= -->
            <section class="tab-pane" id="pane-defense">
                <h1 class="page-title">Simulasi Sidang</h1>
                <p class="page-subtitle">Latihan tanya-jawab berdasarkan isi skripsimu, lengkap dengan skor per pertanyaan dan saran jawaban.</p>

                <div class="stepper">
                    <button type="button" class="step-pill active" id="def-step-btn-1" onclick="setDefenseStep(1)">
                        <span class="step-num-circle">1</span>
                        <span>Pilih skripsi</span>
                    </button>
                    <button type="button" class="step-pill" id="def-step-btn-2">
                        <span class="step-num-circle">2</span>
                        <span>Latihan tanya-jawab</span>
                    </button>
                    <button type="button" class="step-pill" id="def-step-btn-3">
                        <span class="step-num-circle">3</span>
                        <span>Evaluasi & skor</span>
                    </button>
                </div>

                <!-- STEP 1: Pilih Skripsi atau Upload File PDF/DOCX -->
                <div id="def-view-step-1">
                    <div class="card-flush" style="margin-bottom: 18px;">
                        <div class="card-header-bar">
                            <div>Pilih skripsi untuk diujikan <span class="count-badge">{{ $history->count() }}</span></div>
                        </div>
                        @forelse($history as $proj)
                            <div class="project-row" onclick="startDefenseWithProject({{ $proj->id }}, '{{ addslashes($proj->judul) }}')">
                                <div class="project-row-left">
                                    <div class="project-initial">{{ strtoupper(substr($proj->judul, 0, 1)) }}</div>
                                    <div>
                                        <div class="project-row-title">{{ $proj->judul }}</div>
                                        <div class="project-row-meta">{{ $proj->prodi }} · {{ $proj->metode_penelitian }}</div>
                                    </div>
                                </div>
                                <span style="font-size: 13px; font-weight: 600; color: var(--primary);">Mulai Sidang →</span>
                            </div>
                        @empty
                            <div style="padding: 24px; text-align: center; color: var(--text-muted); font-size: 13.5px;">
                                Belum ada proyek tersimpan. Unggah file skripsimu di bawah ini untuk memulai simulasi.
                            </div>
                        @endforelse
                    </div>

                    <div class="card">
                        <div class="form-label">Atau unggah file skripsi / proposal (.pdf, .docx)</div>
                        <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 14px;">AI akan membaca dokumenmu dan menyusun 5 pertanyaan penguji sesuai isi naskah.</p>
                        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                            <input type="file" id="def-file-input" class="form-input" accept=".pdf,.docx,.doc,.txt" style="flex: 1; padding: 8px 12px; font-size: 13px;">
                            <button type="button" id="btn-start-def-file" class="btn-primary" onclick="startDefenseWithFile()">Mulai Simulasi →</button>
                        </div>
                    </div>
                </div>

                <!-- STEP 2: Jawab Pertanyaan Satu per Satu -->
                <div id="def-view-step-2" style="display: none;">
                    <div class="card">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                            <span style="font-size: 12.5px; font-weight: 600; color: var(--primary);" id="def-q-category">Bab I · Urgensi Penelitian</span>
                            <span style="font-size: 12.5px; color: var(--text-muted);" id="def-q-counter">Pertanyaan 1 dari 5</span>
                        </div>

                        <h3 id="def-q-text" style="font-size: 17px; font-weight: 600; line-height: 1.5; color: var(--text-main); margin-bottom: 12px;">-</h3>

                        <div id="def-q-hint" style="padding: 10px 14px; background: var(--bg-subtle); border: 1px solid var(--border-light); border-radius: 9px; font-size: 13px; color: var(--text-secondary); margin-bottom: 18px;"></div>

                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Jawabanmu</label>
                            <textarea id="def-answer-input" class="form-textarea" placeholder="Ketik jawaban argumen sidangmu di sini..."></textarea>
                        </div>

                        <div class="card-footer-bar">
                            <span class="footer-meta" id="def-progress-label">JAWAB SATU PER SATU</span>
                            <button type="button" id="btn-next-def-q" class="btn-primary" onclick="submitDefenseAnswer()">Simpan & Pertanyaan Berikutnya →</button>
                        </div>
                    </div>
                </div>

                <!-- STEP 3: Hasil Evaluasi (Ringkasan | Detail Jawaban | Saran + Skor per Pertanyaan) -->
                <div id="def-view-step-3" style="display: none;">
                    <div class="card" style="margin-bottom: 18px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px; padding-bottom: 18px; border-bottom: 1px solid var(--border-light);">
                            <div>
                                <div style="font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">HASIL SIMULASI SIDANG</div>
                                <h3 id="def-eval-verdict" style="font-size: 20px; font-weight: 700; color: var(--text-main); margin-top: 2px;">-</h3>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 28px; font-weight: 700; color: var(--primary);" id="def-eval-score">0.0/10</div>
                                <div style="font-size: 12px; color: var(--text-muted);">Rata-rata Skor Jawaban</div>
                            </div>
                        </div>

                        <!-- Sub-tabs: Ringkasan | Detail Jawaban | Saran -->
                        <div style="display: flex; gap: 8px; margin: 16px 0; border-bottom: 1px solid var(--border-light); padding-bottom: 12px;">
                            <button type="button" class="btn-outline" id="eval-tab-summary" style="background: var(--bg-muted); font-weight: 600;" onclick="switchEvalSubtab('summary')">Ringkasan</button>
                            <button type="button" class="btn-outline" id="eval-tab-details" onclick="switchEvalSubtab('details')">Detail Jawaban & Saran</button>
                        </div>

                        <!-- Subtab 1: Ringkasan & Skor per Pertanyaan (Matches Screenshot 1) -->
                        <div id="eval-pane-summary">
                            <p id="def-eval-summary" style="font-size: 14px; color: var(--text-secondary); line-height: 1.65; margin-bottom: 20px;"></p>
                            <div style="font-size: 11.5px; font-weight: 700; color: var(--text-muted); letter-spacing: 0.05em; text-transform: uppercase; margin-bottom: 12px;">SKOR PER PERTANYAAN</div>
                            <div id="def-score-bars"></div>
                        </div>

                        <!-- Subtab 2: Detail Jawaban & Saran Jawaban Terbaik -->
                        <div id="eval-pane-details" style="display: none;">
                            <div id="def-details-list" style="display: flex; flex-direction: column; gap: 14px;"></div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ========================================================= -->
            <!-- 6. TAB CARI JURNAL & DOWNLOAD PDF ASLI -->
            <!-- ========================================================= -->
            <section class="tab-pane" id="pane-journals">
                <div class="page-eyebrow">Referensi Ilmiah Open-Access</div>
                <h1 class="page-title">Cari Jurnal & Unduh PDF Asli</h1>
                <p class="page-subtitle">Temukan jurnal penelitian relevan lengkap dengan tautan unduh PDF asli (Open-Access / DOI) serta format sitasi Daftar Pustaka APA.</p>

                <div class="card">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Kata Kunci / Variabel Penelitian <span class="req">*</span></label>
                        <input type="text" id="journal-keyword" class="form-input" placeholder="cth: pengaruh beban kerja terhadap kinerja karyawan, klasifikasi citra random forest...">
                    </div>

                    <div class="card-footer-bar">
                        <span class="footer-meta">TERHUBUNG DATABASE OPENALEX & GOOGLE SCHOLAR PDF</span>
                        <button type="button" id="btn-find-journals" class="btn-primary" onclick="handleFindJournals()">Cari Jurnal →</button>
                    </div>
                </div>

                <div id="journals-result-box" style="display: none; margin-top: 20px;" class="card-flush">
                    <div class="card-header-bar">
                        <span>Hasil Pencarian Jurnal</span>
                        <button type="button" class="btn-outline" style="padding: 5px 11px; font-size: 12px;" onclick="downloadJournalListTxt()">Unduh Daftar Sitasi (.txt)</button>
                    </div>
                    <div id="journals-list"></div>
                </div>
            </section>

            <!-- ========================================================= -->
            <!-- 7. TAB PARAFRASE ONLINE -->
            <!-- ========================================================= -->
            <section class="tab-pane" id="pane-paraphrase">
                <div class="page-eyebrow">Penulisan Akademik</div>
                <h1 class="page-title">Parafrase Online</h1>
                <p class="page-subtitle">Tulis ulang paragraf kutipan menjadi bahasa ilmiah yang natural sesuai kaidah KBBI & EYD V tanpa menghilangkan sitasi.</p>

                <div class="card">
                    <div class="form-group">
                        <label class="form-label">Gaya Parafrase</label>
                        <select id="para-style" class="form-select">
                            <option value="academic">Akademik Standar (Bahasa Baku Skripsi)</option>
                            <option value="natural">Ubah Struktur Kalimat (Turunkan Kemiripan Turnitin)</option>
                            <option value="concise">Ringkas & Padat</option>
                            <option value="expand">Perluas Penjelasan Ilmiah</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Teks Asli <span class="req">*</span></label>
                        <textarea id="para-input" class="form-textarea" placeholder="Tempelkan paragraf yang ingin diparafrase di sini..."></textarea>
                    </div>

                    <div class="card-footer-bar">
                        <span class="footer-meta">SITASI DALAM KURUNG TETAP DIPERTAHANKAN</span>
                        <button type="button" id="btn-paraphrase" class="btn-primary" onclick="handleParaphrase()">Parafrase Sekarang →</button>
                    </div>
                </div>

                <div id="para-result-card" class="card" style="display: none; margin-top: 18px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <span class="form-label" style="margin-bottom: 0;">Hasil Parafrase</span>
                        <button type="button" class="btn-outline" style="padding: 5px 11px; font-size: 12px;" onclick="navigator.clipboard.writeText(document.getElementById('para-output').innerText); alert('Hasil parafrase disalin!');">Salin Teks</button>
                    </div>
                    <div id="para-output" style="font-size: 14px; line-height: 1.75; color: var(--text-main); white-space: pre-wrap;"></div>
                </div>
            </section>

            <!-- ========================================================= -->
            <!-- 8. TAB DISKUSI & TUGAS KULIAH -->
            <!-- ========================================================= -->
            <section class="tab-pane" id="pane-qa">
                <div class="page-eyebrow">Asisten Materi</div>
                <h1 class="page-title">Diskusi Skripsi & Tugas Kuliah</h1>
                <p class="page-subtitle">Tanyakan konsep teori, pengolahan data statistik, atau unggah foto soal untuk dibahas secara bertahap.</p>

                <div class="card">
                    <div class="form-group">
                        <label class="form-label">Pertanyaan atau Instruksi <span class="req">*</span></label>
                        <textarea id="qa-question" class="form-textarea" placeholder="Tulis pertanyaanmu di sini..."></textarea>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Lampiran Gambar Soal / Tabel <span class="opt">(opsional)</span></label>
                        <input type="file" id="qa-image" class="form-input" accept="image/*" style="padding: 8px 12px; font-size: 13px;">
                    </div>
                    <div class="card-footer-bar">
                        <span class="footer-meta">PENJELASAN SISTEMATIS</span>
                        <button type="button" id="btn-solve-qa" class="btn-primary" onclick="handleSolveQa()">Kirim Pertanyaan →</button>
                    </div>
                </div>

                <div id="qa-result-card" class="card" style="display: none; margin-top: 18px;">
                    <div class="form-label">Jawaban & Pembahasan</div>
                    <div id="qa-output" style="font-size: 14px; line-height: 1.75; color: var(--text-main); white-space: pre-wrap;"></div>
                </div>
            </section>

            <!-- ========================================================= -->
            <!-- 9. TAB PENGATURAN API KEY -->
            <!-- ========================================================= -->
            <section class="tab-pane" id="pane-apikeys">
                <div class="page-eyebrow">Konfigurasi</div>
                <h1 class="page-title">Pengaturan API Key</h1>
                <p class="page-subtitle">Tambahkan API Key Google Gemini (`AIza...`) atau Groq (`gsk_...`) milikmu sendiri untuk kecepatan akses penuh.</p>

                <div class="card" style="margin-bottom: 20px;">
                    <form action="{{ route('api.key.add') }}" method="POST">
                        @csrf
                        <div class="form-row" style="margin-bottom: 0;">
                            <div>
                                <label class="form-label">Provider AI</label>
                                <select name="provider" class="form-select">
                                    <option value="gemini">Google Gemini (AI Studio)</option>
                                    <option value="groq">Groq Cloud (Llama 3.3)</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">API Key <span class="req">*</span></label>
                                <input type="text" name="key" class="form-input" placeholder="Tempelkan API Key (AIza... atau gsk_...)" required>
                            </div>
                        </div>
                        <div class="card-footer-bar">
                            <span class="footer-meta">ROTASI OTOMATIS MULTI-KEY</span>
                            <button type="submit" class="btn-primary">Simpan API Key</button>
                        </div>
                    </form>
                </div>

                <div class="card-flush">
                    <div class="card-header-bar">
                        <span>Daftar API Key Tersimpan <span class="count-badge">{{ count($apiKeys) }}</span></span>
                    </div>
                    @forelse($apiKeys as $k)
                        <div class="project-row" style="cursor: default;">
                            <div>
                                <div class="project-row-title">{{ substr($k->key, 0, 10) }}••••••••••••</div>
                                <div class="project-row-meta">Digunakan {{ $k->usage_count ?? 0 }}x</div>
                            </div>
                            <form action="{{ route('api.key.delete', ['id' => $k->id]) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-outline" style="color: #dc2626; padding: 5px 10px; font-size: 12px;">Hapus</button>
                            </form>
                        </div>
                    @empty
                        <div style="padding: 24px; text-align: center; color: var(--text-muted); font-size: 13.5px;">
                            Belum ada API Key pribadi. Sistem saat ini menggunakan server cadangan otomatis.
                        </div>
                    @endforelse
                </div>
            </section>

        </main>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function getSelectedModel() {
            return document.getElementById('global-model').value || 'auto';
        }

        function updateCredits(val) {
            if (val !== undefined && val !== null) {
                document.getElementById('credit-count').innerText = val;
            }
        }

        function openTab(tabId, label) {
            document.querySelectorAll('.tab-pane').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.nav-btn').forEach(el => el.classList.remove('active'));

            const pane = document.getElementById('pane-' + tabId);
            if (pane) pane.classList.add('active');

            const navBtn = document.getElementById('nav-' + tabId);
            if (navBtn) navBtn.classList.add('active');

            if (label) {
                document.getElementById('breadcrumb-label').innerText = label;
            }
            document.getElementById('sidebar').classList.remove('open');
        }

        // Buka tab sesuai parameter URL ?tab=...
        (function initTabFromUrl() {
            const params = new URLSearchParams(window.location.search);
            const tab = params.get('tab');
            const map = {
                'dashboard': 'Dashboard',
                'projects': 'Proyek Skripsi',
                'skripsi': 'Proyek Skripsi',
                'proposal': 'Proyek Skripsi',
                'titles': 'Cari Judul',
                'ppt': 'PPT Sidang',
                'defense': 'Simulasi Sidang',
                'journals': 'Cari Jurnal',
                'paraphrase': 'Parafrase',
                'qa': 'Diskusi & Tugas',
                'apikeys': 'Pengaturan API'
            };
            if (tab && map[tab]) {
                const target = (tab === 'skripsi' || tab === 'proposal') ? 'projects' : tab;
                openTab(target, map[tab]);
            }
        })();

        async function claimFreeTokens() {
            try {
                const res = await fetch("{{ route('reward.claim') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ type: 'share' })
                });
                const data = await res.json();
                if (data.status === 'success') {
                    updateCredits(data.new_credits);
                    alert(data.message);
                }
            } catch (e) {}
        }

        // =====================================================================
        // 1. CARI JUDUL SKRIPSI
        // =====================================================================
        async function handleFindTitles() {
            const topik = document.getElementById('title-topik').value.trim();
            const metode = document.getElementById('title-metode').value;
            const prodi = document.getElementById('title-prodi').value.trim();

            if (!topik) {
                alert('Silakan isi Topik atau Bidang Penelitian terlebih dahulu.');
                return;
            }

            const btn = document.getElementById('btn-find-titles');
            btn.disabled = true;
            btn.innerText = 'Mencari Judul...';

            try {
                const res = await fetch("{{ route('tools.titles') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ topik, metode, prodi: prodi || topik, model: getSelectedModel() })
                });
                const data = await res.json();
                if (data.status === 'success') {
                    updateCredits(data.new_credits);
                    const list = document.getElementById('titles-list');
                    list.innerHTML = '';
                    (data.data || []).forEach((t, i) => {
                        const row = document.createElement('div');
                        row.className = 'project-row';
                        row.style.cursor = 'default';
                        row.innerHTML = `
                            <div class="project-row-left" style="flex:1; padding-right:12px;">
                                <div class="project-initial">${i + 1}</div>
                                <div class="project-row-title">${t}</div>
                            </div>
                            <button type="button" class="btn-outline" onclick="useTitleForProject('${t.replace(/'/g, "\\'")}', '${prodi.replace(/'/g, "\\'")}')">Gunakan Judul →</button>
                        `;
                        list.appendChild(row);
                    });
                    document.getElementById('titles-result-box').style.display = 'block';
                } else {
                    alert(data.message || 'Gagal mencari judul.');
                }
            } catch (e) {
                alert('Terjadi kesalahan koneksi.');
            } finally {
                btn.disabled = false;
                btn.innerText = 'Temukan Judul →';
            }
        }

        function useTitleForProject(judul, prodi) {
            document.getElementById('input-project-judul').value = judul;
            if (prodi) document.getElementById('input-project-prodi').value = prodi;
            openTab('projects', 'Proyek Skripsi');
        }

        // =====================================================================
        // 2. PPT SIDANG (4-STEP WORKFLOW + LIVE PPTX EXPORT)
        // =====================================================================
        let selectedPptProject = { id: null, title: '' };
        let pptSlides = [];
        let currentSlideIdx = 0;
        let currentPptTheme = 'navy';

        function setPptStep(step) {
            [1, 2, 3].forEach(s => {
                const view = document.getElementById('ppt-view-step-' + s);
                if (view) view.style.display = (s === step) ? 'block' : 'none';
            });
            [1, 2, 3, 4].forEach(s => {
                const pill = document.getElementById('ppt-step-btn-' + s);
                if (pill) pill.classList.toggle('active', s === step);
            });
        }

        function chooseProjectForPpt(id, title) {
            selectedPptProject = { id, title };
            document.getElementById('ppt-selected-initial').innerText = title.charAt(0).toUpperCase();
            document.getElementById('ppt-selected-source').innerText = 'SKRIPSI';
            document.getElementById('ppt-selected-title').innerText = title;
            setPptStep(2);
        }

        function onUploadFileForPpt(input) {
            if (input.files && input.files[0]) {
                const fname = input.files[0].name;
                selectedPptProject = { id: null, title: fname.replace(/\.[^/.]+$/, '') };
                document.getElementById('ppt-selected-initial').innerText = 'F';
                document.getElementById('ppt-selected-source').innerText = 'DOKUMEN UNGGAHAN';
                document.getElementById('ppt-selected-title').innerText = fname;
                setPptStep(2);
            }
        }

        async function handleGeneratePpt() {
            const btn = document.getElementById('btn-generate-ppt');
            btn.disabled = true;
            btn.innerHTML = '<span>Menyusun 9 Slide Presentasi...</span>';

            const formData = new FormData();
            formData.append('model', getSelectedModel());
            if (selectedPptProject.id) {
                formData.append('project_id', selectedPptProject.id);
                formData.append('judul', selectedPptProject.title);
            } else {
                const fileInput = document.getElementById('ppt-file-input');
                if (fileInput.files.length > 0) {
                    formData.append('file_skripsi', fileInput.files[0]);
                }
                formData.append('judul', selectedPptProject.title);
            }

            try {
                const res = await fetch("{{ route('tools.ppt') }}", {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    body: formData
                });
                const data = await res.json();
                if (data.status === 'success' && Array.isArray(data.slides_data)) {
                    updateCredits(data.new_credits);
                    pptSlides = data.slides_data;
                    currentSlideIdx = 0;
                    renderCurrentSlide();
                    setPptStep(3);
                } else {
                    alert(data.message || 'Gagal menyusun slide.');
                }
            } catch (e) {
                alert('Gagal menyusun slide PPT.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<span>Buat slide sidang</span>';
            }
        }

        function renderCurrentSlide() {
            if (!pptSlides.length) return;
            const s = pptSlides[currentSlideIdx];
            document.getElementById('slide-section-label').innerText = s.section || ('Slide ' + (currentSlideIdx + 1));
            document.getElementById('slide-counter-label').innerText = `Slide ${currentSlideIdx + 1} / ${pptSlides.length}`;
            document.getElementById('slide-title-edit').innerText = s.title || '';
            document.getElementById('slide-subtitle-edit').innerText = s.subtitle || '';

            const ul = document.getElementById('slide-bullets-edit');
            ul.innerHTML = '';
            (s.bullets || []).forEach(b => {
                const li = document.createElement('li');
                li.innerText = b;
                li.style.marginBottom = '6px';
                ul.appendChild(li);
            });

            document.getElementById('slide-notes-edit').value = s.speaker_notes || '';
        }

        function syncCurrentSlideEdit() {
            if (!pptSlides.length) return;
            const s = pptSlides[currentSlideIdx];
            s.section = document.getElementById('slide-section-label').innerText.trim();
            s.title = document.getElementById('slide-title-edit').innerText.trim();
            s.subtitle = document.getElementById('slide-subtitle-edit').innerText.trim();
            const bullets = [];
            document.querySelectorAll('#slide-bullets-edit li').forEach(li => {
                if (li.innerText.trim()) bullets.push(li.innerText.trim());
            });
            if (bullets.length) s.bullets = bullets;
            s.speaker_notes = document.getElementById('slide-notes-edit').value;
        }

        function prevSlide() {
            syncCurrentSlideEdit();
            if (currentSlideIdx > 0) {
                currentSlideIdx--;
                renderCurrentSlide();
            }
        }

        function nextSlide() {
            syncCurrentSlideEdit();
            if (currentSlideIdx < pptSlides.length - 1) {
                currentSlideIdx++;
                renderCurrentSlide();
            }
        }

        function addNewSlide() {
            syncCurrentSlideEdit();
            pptSlides.push({
                slide_number: pptSlides.length + 1,
                section: 'Slide Tambahan',
                title: 'Judul Slide Baru (Klik untuk Edit)',
                subtitle: 'Subjudul atau Poin Utama',
                bullets: ['Poin penjelasan pertama', 'Poin penjelasan kedua', 'Poin penjelasan ketiga'],
                speaker_notes: 'Catatan bicara untuk slide ini...'
            });
            currentSlideIdx = pptSlides.length - 1;
            renderCurrentSlide();
        }

        function setPptTheme(theme, btn) {
            currentPptTheme = theme;
            const box = document.getElementById('ppt-canvas-box');
            box.className = 'ppt-canvas ppt-theme-' + theme;
            document.querySelectorAll('.theme-dot').forEach(d => d.classList.remove('active'));
            btn.classList.add('active');
        }

        function downloadPptxFile() {
            syncCurrentSlideEdit();
            if (!pptSlides.length) return;

            const pptx = new PptxGenJS();
            pptx.layout = 'LAYOUT_16x9';

            const palettes = {
                navy: { bg: '1E1B4B', fg: 'FFFFFF', sub: 'C7D2FE' },
                light: { bg: 'FFFFFF', fg: '111827', sub: '4B5563' },
                slate: { bg: '0F172A', fg: 'F8FAFC', sub: '94A3B8' },
                emerald: { bg: '064E3B', fg: 'ECFDF5', sub: 'A7F3D0' }
            };
            const pal = palettes[currentPptTheme] || palettes.navy;

            pptSlides.forEach((s, idx) => {
                const slide = pptx.addSlide();
                slide.background = { color: pal.bg };

                slide.addText(s.section || `Slide ${idx + 1}`, {
                    x: 0.6, y: 0.4, w: 8.5, h: 0.35,
                    fontSize: 11, bold: true, color: pal.sub
                });

                slide.addText(s.title || '', {
                    x: 0.6, y: 0.85, w: 8.8, h: 0.9,
                    fontSize: 22, bold: true, color: pal.fg
                });

                if (s.subtitle) {
                    slide.addText(s.subtitle, {
                        x: 0.6, y: 1.75, w: 8.8, h: 0.4,
                        fontSize: 13, color: pal.sub
                    });
                }

                const bulletItems = (s.bullets || []).map(b => ({ text: b, options: { bullet: true, breakLine: true } }));
                if (bulletItems.length) {
                    slide.addText(bulletItems, {
                        x: 0.6, y: 2.3, w: 8.8, h: 2.6,
                        fontSize: 14, color: pal.fg, lineSpacing: 24, valign: 'top'
                    });
                }

                if (s.speaker_notes) {
                    slide.addNotes(s.speaker_notes);
                }
            });

            pptx.writeFile({ fileName: 'Slide_Sidang_SkripsiAI.pptx' });
        }

        // =====================================================================
        // 3. SIMULASI SIDANG (PILIH SKRIPSI / UPLOAD FILE -> 5 SOAL -> SKOR & SARAN)
        // =====================================================================
        let defenseTitle = '';
        let defenseQuestions = [];
        let defenseAnswers = [];
        let defCurrentIdx = 0;

        function setDefenseStep(step) {
            [1, 2, 3].forEach(s => {
                const v = document.getElementById('def-view-step-' + s);
                if (v) v.style.display = (s === step) ? 'block' : 'none';
                const p = document.getElementById('def-step-btn-' + s);
                if (p) p.classList.toggle('active', s === step);
            });
        }

        async function startDefenseWithProject(projectId, title) {
            defenseTitle = title;
            const formData = new FormData();
            formData.append('mode', 'generate_questions');
            formData.append('project_id', projectId);
            formData.append('judul', title);
            formData.append('model', getSelectedModel());
            await requestDefenseQuestions(formData);
        }

        async function startDefenseWithFile() {
            const fileInput = document.getElementById('def-file-input');
            if (!fileInput.files.length) {
                alert('Pilih file skripsi (.pdf / .docx) terlebih dahulu.');
                return;
            }
            const btn = document.getElementById('btn-start-def-file');
            btn.disabled = true;
            btn.innerText = 'Membuat Pertanyaan...';

            const formData = new FormData();
            formData.append('mode', 'generate_questions');
            formData.append('file_skripsi', fileInput.files[0]);
            formData.append('model', getSelectedModel());

            await requestDefenseQuestions(formData);
            btn.disabled = false;
            btn.innerText = 'Mulai Simulasi →';
        }

        async function requestDefenseQuestions(formData) {
            try {
                const res = await fetch("{{ route('tools.defense') }}", {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    body: formData
                });
                const data = await res.json();
                if (data.status === 'success' && Array.isArray(data.questions)) {
                    updateCredits(data.new_credits);
                    defenseTitle = data.title || defenseTitle;
                    defenseQuestions = data.questions;
                    defenseAnswers = [];
                    defCurrentIdx = 0;
                    renderDefenseQuestion();
                    setDefenseStep(2);
                } else {
                    alert(data.message || 'Gagal menyiapkan pertanyaan sidang.');
                }
            } catch (e) {
                alert('Terjadi kesalahan saat memulai simulasi sidang.');
            }
        }

        function renderDefenseQuestion() {
            const q = defenseQuestions[defCurrentIdx];
            document.getElementById('def-q-category').innerText = q.category || 'Pertanyaan Penguji';
            document.getElementById('def-q-counter').innerText = `Pertanyaan ${defCurrentIdx + 1} dari ${defenseQuestions.length}`;
            document.getElementById('def-q-text').innerText = q.question || '';
            document.getElementById('def-q-hint').innerText = 'Kisi-kisi: ' + (q.hint || 'Jawab secara sistematis sesuai kerangka penelitian Anda.');
            document.getElementById('def-answer-input').value = '';

            const btn = document.getElementById('btn-next-def-q');
            btn.innerText = (defCurrentIdx === defenseQuestions.length - 1)
                ? 'Selesai & Lihat Skor Evaluasi →'
                : 'Simpan & Pertanyaan Berikutnya →';
        }

        async function submitDefenseAnswer() {
            const ans = document.getElementById('def-answer-input').value.trim();
            if (!ans) {
                alert('Silakan isi jawabanmu terlebih dahulu.');
                return;
            }

            defenseAnswers.push({
                question: defenseQuestions[defCurrentIdx].question,
                answer: ans
            });

            if (defCurrentIdx < defenseQuestions.length - 1) {
                defCurrentIdx++;
                renderDefenseQuestion();
                return;
            }

            // Semua pertanyaan selesai -> Evaluasi Akhir
            const btn = document.getElementById('btn-next-def-q');
            btn.disabled = true;
            btn.innerText = 'Mengevaluasi Seluruh Jawaban...';

            try {
                const res = await fetch("{{ route('tools.defense') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({
                        mode: 'evaluate_all',
                        judul: defenseTitle,
                        qa_list: defenseAnswers,
                        model: getSelectedModel()
                    })
                });
                const data = await res.json();
                if (data.status === 'success' && data.evaluation) {
                    updateCredits(data.new_credits);
                    renderDefenseEvaluation(data.evaluation);
                    setDefenseStep(3);
                } else {
                    alert(data.message || 'Gagal mengevaluasi jawaban.');
                }
            } catch (e) {
                alert('Gagal mengevaluasi sesi sidang.');
            } finally {
                btn.disabled = false;
            }
        }

        function renderDefenseEvaluation(ev) {
            document.getElementById('def-eval-verdict').innerText = ev.verdict || 'Evaluasi Selesai';
            document.getElementById('def-eval-score').innerText = (ev.overall_score || 7.8) + '/10';
            document.getElementById('def-eval-summary').innerText = ev.summary || '';

            const bars = document.getElementById('def-score-bars');
            bars.innerHTML = '';
            const details = document.getElementById('def-details-list');
            details.innerHTML = '';

            (ev.items || []).forEach((item, i) => {
                const sc = parseFloat(item.score || 7.5);
                const pct = Math.min(100, Math.max(10, sc * 10));

                bars.innerHTML += `
                    <div class="score-row">
                        <div class="score-row-top">
                            <span>Pertanyaan ${i + 1}</span>
                            <span style="color: var(--primary); font-weight: 700;">${sc}/10</span>
                        </div>
                        <div class="score-bar-track">
                            <div class="score-bar-fill" style="width: ${pct}%;"></div>
                        </div>
                    </div>
                `;

                details.innerHTML += `
                    <div style="padding: 16px; border: 1px solid var(--border); border-radius: 11px;">
                        <div style="display:flex; justify-content:space-between; font-size:12.5px; font-weight:600; color:var(--primary); margin-bottom:6px;">
                            <span>Pertanyaan ${i + 1}</span>
                            <span>Skor: ${sc}/10</span>
                        </div>
                        <div style="font-size:14px; font-weight:600; margin-bottom:8px;">${item.question}</div>
                        <div style="font-size:13px; color:var(--text-secondary); margin-bottom:10px;"><strong>Jawabanmu:</strong> ${item.student_answer}</div>
                        <div style="font-size:13px; color:var(--text-main); margin-bottom:10px;"><strong>Evaluasi Penguji:</strong> ${item.feedback}</div>
                        <div style="padding:10px 12px; background:var(--bg-subtle); border-radius:8px; font-size:13px; color:var(--text-main);">
                            <strong>Saran Jawaban Terbaik:</strong><br>${item.better_answer}
                        </div>
                    </div>
                `;
            });
        }

        function switchEvalSubtab(sub) {
            document.getElementById('eval-pane-summary').style.display = (sub === 'summary') ? 'block' : 'none';
            document.getElementById('eval-pane-details').style.display = (sub === 'details') ? 'block' : 'none';
            document.getElementById('eval-tab-summary').style.background = (sub === 'summary') ? 'var(--bg-muted)' : '#fff';
            document.getElementById('eval-tab-details').style.background = (sub === 'details') ? 'var(--bg-muted)' : '#fff';
        }

        // =====================================================================
        // 4. CARI JURNAL & DOWNLOAD PDF ASLI
        // =====================================================================
        let lastJournals = [];

        async function handleFindJournals() {
            const keyword = document.getElementById('journal-keyword').value.trim();
            if (!keyword) {
                alert('Masukkan kata kunci atau topik penelitian.');
                return;
            }

            const btn = document.getElementById('btn-find-journals');
            btn.disabled = true;
            btn.innerText = 'Mencari Jurnal...';

            try {
                const res = await fetch("{{ route('tools.journals') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ keyword, model: getSelectedModel() })
                });
                const data = await res.json();
                if (data.status === 'success') {
                    updateCredits(data.new_credits);
                    lastJournals = data.data || [];
                    const list = document.getElementById('journals-list');
                    list.innerHTML = '';

                    lastJournals.forEach((j, idx) => {
                        const row = document.createElement('div');
                        row.className = 'project-row';
                        row.style.cursor = 'default';
                        row.style.alignItems = 'flex-start';
                        row.innerHTML = `
                            <div class="project-row-left" style="flex:1; padding-right:14px; align-items:flex-start;">
                                <div class="project-initial" style="margin-top:2px;">${idx + 1}</div>
                                <div>
                                    <div class="project-row-title">${j.title}</div>
                                    <div class="project-row-meta">${j.author} · <strong>${j.journal}</strong> (${j.year})</div>
                                    <div style="font-size:12px; color:var(--text-muted); margin-top:6px; background:var(--bg-subtle); padding:6px 10px; border-radius:6px;">
                                        Sitasi APA: ${j.citation || `${j.author} (${j.year}). ${j.title}. ${j.journal}.`}
                                    </div>
                                </div>
                            </div>
                            <div style="display:flex; gap:6px; flex-shrink:0;">
                                <a href="${j.pdf_url}" target="_blank" rel="noopener" class="btn-primary" style="padding:7px 12px; font-size:12.5px;">Unduh PDF Asli ↗</a>
                            </div>
                        `;
                        list.appendChild(row);
                    });

                    document.getElementById('journals-result-box').style.display = 'block';
                } else {
                    alert(data.message || 'Gagal mencari jurnal.');
                }
            } catch (e) {
                alert('Terjadi kesalahan saat mencari jurnal.');
            } finally {
                btn.disabled = false;
                btn.innerText = 'Cari Jurnal →';
            }
        }

        function downloadJournalListTxt() {
            if (!lastJournals.length) return;
            const lines = lastJournals.map((j, i) => `${i + 1}. ${j.citation || `${j.author} (${j.year}). ${j.title}. ${j.journal}.`}\n   Link PDF: ${j.pdf_url}`).join('\n\n');
            const blob = new Blob([lines], { type: 'text/plain;charset=utf-8' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'Daftar_Jurnal_Referensi.txt';
            a.click();
        }

        // =====================================================================
        // 5. PARAFRASE & DISKUSI
        // =====================================================================
        async function handleParaphrase() {
            const text = document.getElementById('para-input').value.trim();
            const style = document.getElementById('para-style').value;
            if (text.length < 15) {
                alert('Masukkan minimal 1 kalimat lengkap untuk diparafrase.');
                return;
            }
            const btn = document.getElementById('btn-paraphrase');
            btn.disabled = true;
            btn.innerText = 'Memproses...';

            try {
                const res = await fetch("{{ route('tools.paraphrase') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ text, style, model: getSelectedModel() })
                });
                const data = await res.json();
                if (data.status === 'success') {
                    updateCredits(data.new_credits);
                    document.getElementById('para-output').innerText = data.result;
                    document.getElementById('para-result-card').style.display = 'block';
                } else {
                    alert(data.message || 'Gagal memproses parafrase.');
                }
            } catch (e) {
                alert('Gagal memproses parafrase.');
            } finally {
                btn.disabled = false;
                btn.innerText = 'Parafrase Sekarang →';
            }
        }

        async function handleSolveQa() {
            const question = document.getElementById('qa-question').value.trim();
            if (!question) {
                alert('Tuliskan pertanyaan terlebih dahulu.');
                return;
            }
            const btn = document.getElementById('btn-solve-qa');
            btn.disabled = true;
            btn.innerText = 'Memproses...';

            let imageBase64 = null;
            const imgInput = document.getElementById('qa-image');
            if (imgInput.files && imgInput.files[0]) {
                imageBase64 = await new Promise(resolve => {
                    const r = new FileReader();
                    r.onload = e => resolve(e.target.result);
                    r.readAsDataURL(imgInput.files[0]);
                });
            }

            try {
                const res = await fetch("{{ route('tools.solve') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ question, image: imageBase64, model: getSelectedModel() })
                });
                const data = await res.json();
                if (data.status === 'success') {
                    updateCredits(data.new_credits);
                    document.getElementById('qa-output').innerText = data.answer;
                    document.getElementById('qa-result-card').style.display = 'block';
                } else {
                    alert(data.message || 'Gagal memproses pertanyaan.');
                }
            } catch (e) {
                alert('Gagal memproses pertanyaan.');
            } finally {
                btn.disabled = false;
                btn.innerText = 'Kirim Pertanyaan →';
            }
        }
    </script>
</body>
</html>
