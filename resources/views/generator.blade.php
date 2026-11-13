<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $project->judul }} — Workspace Skripsi</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-app: #f8fafc;
            --bg-sidebar: #ffffff;
            --bg-canvas: #eef2f7;
            --surface: #ffffff;
            --border: #e2e8f0;
            --border-strong: #cbd5e1;
            --text-primary: #0f172a;
            --text-secondary: #334155;
            --text-muted: #64748b;
            --accent: #2563eb;
            --accent-hover: #1d4ed8;
            --accent-soft: #eff6ff;
            --accent-border: #bfdbfe;
            --green: #059669;
            --green-soft: #ecfdf5;
            --red: #dc2626;
            --sidebar-w: 276px;
            --topbar-h: 60px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body {
            height: 100%;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-app);
            color: var(--text-primary);
            -webkit-font-smoothing: antialiased;
        }

        .studio-shell {
            display: flex;
            min-height: 100vh;
            width: 100%;
            overflow-x: hidden;
        }

        /* Sidebar */
        .studio-sidebar {
            width: var(--sidebar-w);
            flex-shrink: 0;
            background: var(--bg-sidebar);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            height: 100vh;
            position: sticky;
            top: 0;
            z-index: 40;
            transition: margin-left 0.22s ease, transform 0.22s ease;
        }
        .studio-shell.sidebar-collapsed .studio-sidebar {
            margin-left: calc(var(--sidebar-w) * -1);
        }

        .sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(2px);
            z-index: 35;
        }

        .sidebar-header {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border);
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            color: var(--accent);
            text-decoration: none;
            margin-bottom: 10px;
        }
        .back-link:hover { text-decoration: underline; }
        .project-title-side {
            font-size: 13px;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: 6px;
        }
        .project-mode-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            font-weight: 700;
            color: var(--accent);
            background: var(--accent-soft);
            border: 1px solid var(--accent-border);
            padding: 3px 9px;
            border-radius: 999px;
        }

        .progress-wrap {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border);
            background: #fafcff;
        }
        .progress-label {
            display: flex;
            justify-content: space-between;
            font-size: 11.5px;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 6px;
        }
        .progress-track {
            height: 6px;
            background: #e2e8f0;
            border-radius: 999px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #2563eb, #3b82f6);
            border-radius: 999px;
            transition: width 0.35s ease;
        }

        .sidebar-nav {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .nav-section-label {
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-muted);
            padding: 6px 10px 4px;
        }
        .nav-item {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 9px 11px;
            border-radius: 9px;
            border: 1px solid transparent;
            background: transparent;
            color: var(--text-secondary);
            font-family: inherit;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-align: left;
            transition: all 0.15s ease;
        }
        .nav-item:hover {
            background: #f1f5f9;
            color: var(--text-primary);
        }
        .nav-item.active {
            background: var(--accent-soft);
            color: var(--accent-hover);
            border-color: var(--accent-border);
        }
        .nav-item-left {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
        }
        .nav-item-text {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: #cbd5e1;
            flex-shrink: 0;
        }
        .status-dot.done { background: var(--green); }
        .status-dot.generating {
            background: var(--accent);
            animation: pulseDot 1s infinite;
        }
        @keyframes pulseDot {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.45); opacity: 0.55; }
        }

        .nav-item-revisi {
            margin-top: 4px;
            border: 1px dashed var(--accent-border);
            background: #f8fbff;
            color: var(--accent-hover);
        }
        .nav-item-revisi.active {
            border-style: solid;
            background: var(--accent);
            color: #ffffff;
            border-color: var(--accent);
        }

        .auto-box {
            margin: 8px 10px 4px;
            padding: 12px;
            border-radius: 12px;
            background: linear-gradient(135deg, #eff6ff 0%, #f8fbff 100%);
            border: 1px solid var(--accent-border);
        }
        .auto-box-title {
            font-size: 12px;
            font-weight: 700;
            color: var(--accent-hover);
            margin-bottom: 4px;
        }
        .auto-box-desc {
            font-size: 11px;
            color: var(--text-secondary);
            line-height: 1.45;
            margin-bottom: 10px;
        }
        .btn-auto {
            width: 100%;
            border: none;
            border-radius: 8px;
            padding: 8px 10px;
            background: var(--accent);
            color: #ffffff;
            font-family: inherit;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.15s;
        }
        .btn-auto:hover { background: var(--accent-hover); }
        .btn-auto:disabled { opacity: 0.6; cursor: not-allowed; }
        .btn-auto-secondary {
            margin-top: 6px;
            background: #ffffff;
            color: var(--accent);
            border: 1px solid var(--accent-border);
        }
        .btn-auto-secondary:hover { background: var(--accent-soft); }

        .sidebar-footer {
            padding: 10px 12px 14px;
            border-top: 1px solid var(--border);
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            background: #ffffff;
        }
        .btn-dl-side {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            padding: 8px 10px;
            border-radius: 8px;
            font-family: inherit;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            border: 1px solid var(--border-strong);
            background: #ffffff;
            color: var(--text-primary);
            white-space: nowrap;
        }
        .btn-dl-side:hover {
            border-color: var(--accent);
            color: var(--accent);
            background: var(--accent-soft);
        }
        .btn-dl-side.primary {
            background: var(--accent);
            border-color: var(--accent);
            color: #ffffff;
        }
        .btn-dl-side.primary:hover {
            background: var(--accent-hover);
        }

        /* Main Area */
        .studio-main {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            background: var(--bg-canvas);
        }

        .studio-topbar {
            min-height: var(--topbar-h);
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid var(--border);
            padding: 8px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            position: sticky;
            top: 0;
            z-index: 30;
            flex-wrap: wrap;
        }
        .topbar-left {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
            flex: 1;
        }
        .btn-icon {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: #ffffff;
            color: var(--text-secondary);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            flex-shrink: 0;
        }
        .btn-icon:hover {
            border-color: var(--accent);
            color: var(--accent);
            background: var(--accent-soft);
        }
        .topbar-title {
            font-size: 13.5px;
            font-weight: 700;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 420px;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .model-select {
            height: 34px;
            border: 1px solid var(--border-strong);
            border-radius: 8px;
            padding: 0 10px;
            font-family: inherit;
            font-size: 12.5px;
            font-weight: 600;
            color: var(--text-primary);
            background: #ffffff;
            cursor: pointer;
        }
        .btn-top {
            height: 34px;
            padding: 0 13px;
            border-radius: 8px;
            font-family: inherit;
            font-size: 12.5px;
            font-weight: 700;
            cursor: pointer;
            border: 1px solid var(--border-strong);
            background: #ffffff;
            color: var(--text-primary);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
            transition: all 0.15s;
        }
        .btn-top:hover {
            border-color: var(--accent);
            color: var(--accent);
            background: var(--accent-soft);
        }
        .btn-top.primary {
            background: var(--accent);
            border-color: var(--accent);
            color: #ffffff;
        }
        .btn-top.primary:hover {
            background: var(--accent-hover);
            color: #ffffff;
        }
        .btn-top.success {
            background: var(--green);
            border-color: var(--green);
            color: #ffffff;
        }
        .btn-top:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Paper Canvas */
        .canvas-scroll {
            flex: 1;
            padding: 24px 20px 64px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 18px;
        }
        .chapter-bar {
            width: 100%;
            max-width: 820px;
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }
        .chapter-bar-title {
            font-size: 13.5px;
            font-weight: 700;
            color: var(--text-primary);
        }
        .chapter-bar-sub {
            font-size: 11.5px;
            color: var(--text-muted);
            margin-top: 2px;
        }
        .chapter-bar-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        #sheets-container {
            width: 100%;
            max-width: 820px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .a4-sheet {
            width: 100%;
            background: #ffffff;
            border: 1px solid #dbe3ee;
            border-radius: 4px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
            padding: 64px 68px;
            color: #111827;
            font-family: "Times New Roman", Georgia, serif;
            font-size: 16px;
            line-height: 1.75;
        }

        .cover-sheet {
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 820px;
        }
        .cover-kind {
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 18px;
        }
        .cover-title {
            font-size: 18px;
            font-weight: 700;
            line-height: 1.5;
            text-transform: uppercase;
            margin-bottom: 14px;
        }
        .cover-sub {
            font-size: 14px;
            line-height: 1.6;
            max-width: 520px;
            margin: 0 auto 24px;
        }
        .cover-logo-box {
            margin: 20px auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }
        .cover-logo-img {
            width: 120px;
            height: 120px;
            object-fit: contain;
        }
        .cover-author-block {
            margin: 24px 0;
            font-size: 15px;
            line-height: 1.7;
        }
        .cover-campus-block {
            font-size: 15px;
            font-weight: 700;
            line-height: 1.55;
            text-transform: uppercase;
        }

        .front-heading {
            text-align: center;
            font-size: 17px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 24px;
        }
        .front-body p {
            text-align: justify;
            text-indent: 36px;
            margin-bottom: 14px;
        }
        .toc-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
            font-size: 14.5px;
        }
        .toc-row {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 12px;
        }
        .toc-row.sub { padding-left: 24px; font-weight: 400; }
        .toc-row.main { font-weight: 700; margin-top: 4px; }
        .toc-dots {
            flex: 1;
            border-bottom: 1px dotted #94a3b8;
        }

        .paper-editor {
            outline: none;
            min-height: 420px;
        }
        .paper-editor:focus {
            box-shadow: inset 0 0 0 2px #dbeafe;
            border-radius: 4px;
        }
        .paper-editor h1, .paper-editor h2 {
            text-align: center;
            font-size: 17px;
            font-weight: 700;
            margin: 0 0 22px;
            line-height: 1.45;
        }
        .paper-editor h3 {
            font-size: 15.5px;
            font-weight: 700;
            margin: 22px 0 10px;
        }
        .paper-editor h4 {
            font-size: 15px;
            font-weight: 700;
            margin: 14px 0 8px;
        }
        .paper-editor p {
            text-align: justify;
            text-indent: 36px;
            margin-bottom: 12px;
        }
        .paper-editor ul, .paper-editor ol {
            margin: 10px 0 14px 36px;
        }
        .paper-editor li {
            text-align: justify;
            margin-bottom: 6px;
        }
        .paper-editor .dp-entry {
            text-indent: -32px;
            padding-left: 32px;
            text-align: left;
            margin-bottom: 10px;
        }
        .paper-editor .table-wrap {
            width: 100%;
            overflow-x: auto;
            margin: 14px 0;
        }
        .paper-editor table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13.5px;
        }
        .paper-editor th, .paper-editor td {
            border: 1px solid #334155;
            padding: 7px 9px;
            text-align: left;
        }
        .paper-editor th { background: #f1f5f9; font-weight: 700; }

        /* Empty state & AI Loading */
        .empty-sheet {
            text-align: center;
            padding: 56px 24px;
            font-family: 'Inter', sans-serif;
        }
        .empty-sheet h3 {
            font-size: 17px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        .empty-sheet p {
            font-size: 13.5px;
            color: var(--text-muted);
            max-width: 440px;
            margin: 0 auto 20px;
            line-height: 1.55;
        }

        .ai-loading-card {
            background: #ffffff;
            border: 1px solid var(--accent-border);
            border-radius: 14px;
            padding: 36px 24px;
            text-align: center;
            font-family: 'Inter', sans-serif;
            box-shadow: 0 10px 28px rgba(37, 99, 235, 0.08);
        }
        .ai-spinner {
            width: 44px;
            height: 44px;
            border: 4px solid #dbeafe;
            border-top-color: var(--accent);
            border-radius: 999px;
            margin: 0 auto 14px;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .ai-loading-title {
            font-size: 15.5px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 6px;
        }
        .ai-loading-sub {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 18px;
        }
        .shimmer-lines {
            max-width: 520px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .shimmer-bar {
            height: 12px;
            border-radius: 999px;
            background: linear-gradient(90deg, #eff6ff 25%, #dbeafe 50%, #eff6ff 75%);
            background-size: 200% 100%;
            animation: shimmer 1.3s infinite;
        }
        @keyframes shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* Lab Revisi Workspace */
        .revisi-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 26px rgba(15, 23, 42, 0.05);
            font-family: 'Inter', sans-serif;
        }
        .revisi-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }
        .revisi-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 19px;
            font-weight: 800;
            color: var(--text-primary);
        }
        .revisi-mode-tabs {
            display: inline-flex;
            background: #f1f5f9;
            padding: 4px;
            border-radius: 10px;
            gap: 4px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }
        .revisi-mode-tab {
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            font-family: inherit;
            font-size: 13px;
            font-weight: 700;
            color: var(--text-secondary);
            background: transparent;
            cursor: pointer;
            transition: all 0.15s;
        }
        .revisi-mode-tab.active {
            background: var(--accent);
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25);
        }
        .form-label {
            display: block;
            font-size: 12.5px;
            font-weight: 700;
            color: var(--text-secondary);
            margin-bottom: 6px;
        }
        .revisi-select, .revisi-textarea, .revisi-file {
            width: 100%;
            border: 1px solid var(--border-strong);
            border-radius: 10px;
            font-family: inherit;
            font-size: 13.5px;
            color: var(--text-primary);
            background: #ffffff;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .revisi-select {
            height: 44px;
            padding: 0 12px;
            font-weight: 600;
            margin-bottom: 14px;
        }
        .revisi-select:focus, .revisi-textarea:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.14);
        }
        .revisi-textarea {
            min-height: 120px;
            padding: 12px 14px;
            resize: vertical;
            line-height: 1.55;
            margin-bottom: 14px;
        }
        .revisi-file {
            padding: 10px 12px;
            font-size: 12.5px;
            margin-bottom: 16px;
            background: #f8fafc;
        }
        .revisi-preview-box {
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 16px;
            max-height: 220px;
            overflow-y: auto;
        }
        .revisi-preview-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--accent);
            margin-bottom: 6px;
        }
        .revisi-preview-text {
            font-size: 13px;
            color: var(--text-secondary);
            line-height: 1.6;
            white-space: pre-line;
        }
        .revisi-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        /* Toast */
        .toast-wrap {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 90;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .toast {
            background: #0f172a;
            color: #ffffff;
            padding: 11px 16px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.22);
            max-width: 360px;
        }
        .toast.success { background: #065f46; }
        .toast.error { background: #991b1b; }

        /* Responsive */
        @media (max-width: 900px) {
            .studio-sidebar {
                position: fixed;
                left: 0;
                top: 0;
                transform: translateX(-100%);
                margin-left: 0 !important;
            }
            .studio-shell.mobile-sidebar-open .studio-sidebar {
                transform: translateX(0);
            }
            .studio-shell.mobile-sidebar-open .sidebar-backdrop {
                display: block;
            }
            .a4-sheet {
                padding: 36px 28px;
                font-size: 15px;
            }
            .topbar-title { max-width: 210px; }
        }
        @media (max-width: 640px) {
            .studio-topbar { padding: 8px 12px; }
            .canvas-scroll { padding: 14px 10px 48px; }
            .a4-sheet {
                padding: 26px 18px;
                font-size: 14.5px;
            }
            .cover-sheet { min-height: auto; padding-top: 32px; padding-bottom: 32px; }
            .topbar-actions { width: 100%; justify-content: flex-end; }
            .btn-top, .model-select { font-size: 12px; padding: 0 10px; }
            .revisi-card { padding: 16px; }
        }
    </style>
</head>
<body>
@php
    $tahap = $meta['tahap_pengerjaan'] ?? 'Skripsi Penuh (Bab I–V)';
    $isProposal = str_contains(strtolower($tahap), 'proposal')
        && empty($project->bab4_content)
        && empty($project->bab5_content);

    $chapterTitles = [
        'bab1' => 'Bab I Pendahuluan',
        'bab2' => 'Bab II Tinjauan Pustaka',
        'bab3' => 'Bab III Metodologi',
    ];
    if (!$isProposal) {
        $chapterTitles['bab4'] = 'Bab IV Hasil Penelitian';
        $chapterTitles['bab5'] = 'Bab V Penutup';
    }

    $maxBab = $isProposal ? 3 : 5;
    $completedCount = 0;
    foreach (array_keys($chapterTitles) as $ck) {
        if (!empty($project->{$ck . '_content'})) {
            $completedCount++;
        }
    }
    $progressPct = round(($completedCount / $maxBab) * 100);
@endphp

<div class="studio-shell" id="studio-shell">
    <div class="sidebar-backdrop" onclick="toggleStudioSidebar()"></div>

    <aside class="studio-sidebar">
        <div class="sidebar-header">
            <a href="{{ route('app.create', ['tab' => 'projects']) }}" class="back-link">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                <span>Kembali ke Proyek</span>
            </a>
            <div class="project-title-side" title="{{ $project->judul }}">{{ $project->judul }}</div>
            <span class="project-mode-badge">
                {{ $isProposal ? 'Proposal (Bab I–III)' : 'Skripsi Penuh (Bab I–V)' }}
            </span>
        </div>

        <div class="progress-wrap">
            <div class="progress-label">
                <span id="progress-text">{{ $completedCount }} dari {{ $maxBab }} Bab</span>
                <span id="progress-pct">{{ $progressPct }}%</span>
            </div>
            <div class="progress-track">
                <div class="progress-fill" id="progress-fill" style="width: {{ $progressPct }}%"></div>
            </div>
        </div>

        <nav class="sidebar-nav" aria-label="Navigasi Bab Skripsi">
            <div class="nav-section-label">Struktur Naskah</div>
            @foreach ($chapterTitles as $key => $label)
                <button type="button"
                        class="nav-item {{ $key === 'bab1' ? 'active' : '' }}"
                        id="nav-item-{{ $key }}"
                        onclick="selectChapterView('{{ $key }}')">
                    <span class="nav-item-left">
                        <span class="nav-item-text">{{ $label }}</span>
                    </span>
                    <span class="status-dot {{ !empty($project->{$key . '_content'}) ? 'done' : '' }}" id="dot-{{ $key }}"></span>
                </button>
            @endforeach

            <button type="button"
                    class="nav-item"
                    id="nav-item-daftar_pustaka"
                    onclick="selectChapterView('daftar_pustaka')">
                <span class="nav-item-left">
                    <span class="nav-item-text">Daftar Pustaka</span>
                </span>
                <span class="status-dot {{ !empty($project->daftar_pustaka) ? 'done' : '' }}" id="dot-daftar_pustaka"></span>
            </button>

            <button type="button"
                    class="nav-item nav-item-revisi"
                    id="nav-item-revisi"
                    onclick="selectChapterView('revisi')">
                <span class="nav-item-left">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 2v7.31L4.62 17.6A2 2 0 0 0 6.3 20.5h11.4a2 2 0 0 0 1.68-2.9L14 9.31V2z"/><line x1="8.5" y1="2" x2="15.5" y2="2"/><line x1="14" y1="9" x2="10" y2="9"/></svg>
                    <span class="nav-item-text">Lab Revisi</span>
                </span>
                <span style="font-size: 11px; font-weight: 700;">Sub-bab</span>
            </button>

            <div class="auto-box">
                <div class="auto-box-title" style="display: flex; align-items: center; gap: 6px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                    <span>Susun Otomatis</span>
                </div>
                <div class="auto-box-desc">
                    @if ($isProposal)
                        Susun Bab I–III & Daftar Pustaka berurutan sesuai pengaturan awal proyekmu.
                    @else
                        Susun berurutan Bab I–V & Daftar Pustaka sesuai struktur akademik.
                    @endif
                </div>
                <button type="button" class="btn-auto" id="btn-generate-all" onclick="generateSequential()">
                    {{ $isProposal ? 'Generate Proposal (Bab I–III)' : 'Generate Skripsi (Bab I–V)' }}
                </button>
                @if ($isProposal)
                    <button type="button" class="btn-auto btn-auto-secondary" onclick="upgradeToFullSkripsi()">
                        Lanjutkan ke Bab IV–V (Full Skripsi)
                    </button>
                @endif
            </div>
        </nav>

        <div class="sidebar-footer">
            <button type="button" class="btn-dl-side primary" onclick="submitQuickDownload('docx')">⬇ Word (.docx)</button>
            <button type="button" class="btn-dl-side" onclick="submitQuickDownload('pdf')">⬇ PDF (.pdf)</button>
        </div>
    </aside>

    <main class="studio-main">
        <header class="studio-topbar">
            <div class="topbar-left">
                <button type="button" class="btn-icon" onclick="toggleStudioSidebar()" aria-label="Toggle Sidebar" title="Buka/Tutup Sidebar">☰</button>
                <div class="topbar-title" title="{{ $project->judul }}">{{ $project->judul }}</div>
            </div>

            <div class="topbar-actions">
                <select id="studio-model" class="model-select" aria-label="Pilih Model AI">
                    <option value="auto" selected>Mode Otomatis (AI)</option>
                    <option value="deepseek">DeepSeek AI</option>
                    <option value="gemini">Google Gemini</option>
                </select>
                <button type="button" class="btn-top success" id="btn-save-edit" style="display: none;" onclick="saveActiveEdit()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>
                    <span>Simpan Editan</span>
                </button>
                <button type="button" class="btn-top primary" id="btn-generate-current" onclick="generateCurrentView()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    <span>Tulis / Susun Ulang Bab Ini</span>
                </button>
                <button type="button" class="btn-top" onclick="submitQuickDownload('docx')">Unduh Word</button>
                <button type="button" class="btn-top" onclick="submitQuickDownload('pdf')">Unduh PDF</button>
            </div>
        </header>

        <div class="canvas-scroll">
            <div class="chapter-bar" id="chapter-bar">
                <div>
                    <div class="chapter-bar-title" id="active-chapter-title">BAB I · PENDAHULUAN</div>
                    <div class="chapter-bar-sub" id="active-chapter-sub">Pratinjau Kertas A4 (Times New Roman 12pt · Margin 4-4-3-3 cm)</div>
                </div>
                <div class="chapter-bar-actions">
                    <button type="button" class="btn-top" onclick="selectChapterView('revisi')">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 2v7.31L4.62 17.6A2 2 0 0 0 6.3 20.5h11.4a2 2 0 0 0 1.68-2.9L14 9.31V2z"/></svg>
                        <span>Buka Lab Revisi</span>
                    </button>
                </div>
            </div>

            <div id="sheets-container"></div>
        </div>
    </main>
</div>

<!-- Hidden Download Form (Auto-filled from initial project setup) -->
<form id="quick-download-form" method="POST" style="display: none;">
    @csrf
    <input type="hidden" name="nama" value="{{ $meta['nama'] ?? (auth()->user()->name ?? 'Mahasiswa') }}">
    <input type="hidden" name="npm" value="{{ $meta['nim'] ?? '' }}">
    <input type="hidden" name="kampus" value="{{ $meta['kampus'] ?? '' }}">
    <input type="hidden" name="fakultas" value="{{ $meta['fakultas'] ?? '' }}">
    <input type="hidden" name="logo_base64" value="{{ $meta['logo_base64'] ?? '' }}">
    <input type="hidden" name="jenis_dokumen" value="{{ $isProposal ? 'PROPOSAL SKRIPSI' : 'SKRIPSI' }}">
    <input type="hidden" name="margin_top" value="4">
    <input type="hidden" name="margin_left" value="4">
    <input type="hidden" name="margin_bottom" value="3">
    <input type="hidden" name="margin_right" value="3">
</form>

<div class="toast-wrap" id="toast-wrap"></div>

<script>
const projectId = @json($project->id);
const projectTitle = @json($project->judul);
const projectProdi = @json($project->prodi);
const projectMeta = @json($meta ?? []);
const isProposalMode = @json($isProposal);
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

let allowBab45 = !isProposalMode;
let activeView = 'bab1';
let isDirty = false;
let currentReviseMode = 'content';

const chapterLabels = {
    bab1: 'BAB I · PENDAHULUAN',
    bab2: 'BAB II · TINJAUAN PUSTAKA',
    bab3: 'BAB III · METODOLOGI PENELITIAN',
    bab4: 'BAB IV · HASIL PENELITIAN DAN PEMBAHASAN',
    bab5: 'BAB V · PENUTUP',
    daftar_pustaka: 'DAFTAR PUSTAKA',
    revisi: 'LAB REVISI SUB-BAB'
};

const chapterContents = {
    bab1: @json($project->bab1_content ?? ''),
    bab2: @json($project->bab2_content ?? ''),
    bab3: @json($project->bab3_content ?? ''),
    bab4: @json($project->bab4_content ?? ''),
    bab5: @json($project->bab5_content ?? ''),
    daftar_pustaka: @json($project->daftar_pustaka ?? '')
};

const defaultSubchapters = [
    { chapter: 'bab1', code: '1.1', label: '1.1 Latar Belakang Masalah' },
    { chapter: 'bab1', code: '1.2', label: '1.2 Identifikasi Masalah' },
    { chapter: 'bab1', code: '1.3', label: '1.3 Batasan Masalah' },
    { chapter: 'bab1', code: '1.4', label: '1.4 Rumusan Masalah' },
    { chapter: 'bab1', code: '1.5', label: '1.5 Tujuan Penelitian' },
    { chapter: 'bab1', code: '1.6', label: '1.6 Manfaat Penelitian' },
    { chapter: 'bab1', code: '1.7', label: '1.7 Sistematika Penulisan' },
    { chapter: 'bab1', code: '', label: 'Semua Bab I (Utuh)' },
    { chapter: 'bab2', code: '2.1', label: '2.1 Landasan Teori' },
    { chapter: 'bab2', code: '2.2', label: '2.2 Penelitian Terdahulu' },
    { chapter: 'bab2', code: '2.3', label: '2.3 Kerangka Pemikiran' },
    { chapter: 'bab2', code: '2.4', label: '2.4 Hipotesis Penelitian' },
    { chapter: 'bab2', code: '', label: 'Semua Bab II (Utuh)' },
    { chapter: 'bab3', code: '3.1', label: '3.1 Jenis dan Desain Penelitian' },
    { chapter: 'bab3', code: '3.2', label: '3.2 Lokasi dan Waktu Penelitian' },
    { chapter: 'bab3', code: '3.3', label: '3.3 Populasi dan Sampel' },
    { chapter: 'bab3', code: '3.4', label: '3.4 Definisi Operasional Variabel' },
    { chapter: 'bab3', code: '3.5', label: '3.5 Teknik Pengumpulan Data' },
    { chapter: 'bab3', code: '3.6', label: '3.6 Uji Instrumen Penelitian' },
    { chapter: 'bab3', code: '3.7', label: '3.7 Teknik Analisis Data' },
    { chapter: 'bab3', code: '', label: 'Semua Bab III (Utuh)' },
    { chapter: 'bab4', code: '4.1', label: '4.1 Gambaran Umum Objek Penelitian' },
    { chapter: 'bab4', code: '4.2', label: '4.2 Hasil Penelitian' },
    { chapter: 'bab4', code: '4.3', label: '4.3 Pembahasan' },
    { chapter: 'bab4', code: '', label: 'Semua Bab IV (Utuh)' },
    { chapter: 'bab5', code: '5.1', label: '5.1 Kesimpulan' },
    { chapter: 'bab5', code: '5.2', label: '5.2 Saran' },
    { chapter: 'bab5', code: '', label: 'Semua Bab V (Utuh)' }
];

function escapeHtml(str) {
    return String(str || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function showToast(msg, type = 'info') {
    const wrap = document.getElementById('toast-wrap');
    const el = document.createElement('div');
    el.className = `toast ${type}`;
    el.textContent = msg;
    wrap.appendChild(el);
    setTimeout(() => el.remove(), 3800);
}

function toggleStudioSidebar() {
    const shell = document.getElementById('studio-shell');
    if (window.innerWidth <= 900) {
        shell.classList.toggle('mobile-sidebar-open');
    } else {
        shell.classList.toggle('sidebar-collapsed');
    }
}

function updateProgressUI() {
    const keys = allowBab45 ? ['bab1', 'bab2', 'bab3', 'bab4', 'bab5'] : ['bab1', 'bab2', 'bab3'];
    let done = 0;
    keys.forEach((k) => {
        const has = Boolean((chapterContents[k] || '').trim());
        if (has) done++;
        const dot = document.getElementById(`dot-${k}`);
        if (dot) dot.classList.toggle('done', has);
    });
    const dpDot = document.getElementById('dot-daftar_pustaka');
    if (dpDot) dpDot.classList.toggle('done', Boolean((chapterContents.daftar_pustaka || '').trim()));

    const total = keys.length;
    const pct = Math.round((done / total) * 100);
    document.getElementById('progress-text').textContent = `${done} dari ${total} Bab`;
    document.getElementById('progress-pct').textContent = `${pct}%`;
    document.getElementById('progress-fill').style.width = `${pct}%`;
}

function selectChapterView(viewKey) {
    activeView = viewKey;
    document.querySelectorAll('.nav-item').forEach((el) => el.classList.remove('active'));
    const navEl = document.getElementById(`nav-item-${viewKey}`);
    if (navEl) navEl.classList.add('active');

    document.getElementById('active-chapter-title').textContent = chapterLabels[viewKey] || 'WORKSPACE';
    const subEl = document.getElementById('active-chapter-sub');
    const genBtn = document.getElementById('btn-generate-current');

    if (viewKey === 'revisi') {
        subEl.textContent = 'Revisi spesifik per sub-bab sesuai catatan dosen pembimbing atau perbaikan format penulisan';
        genBtn.style.display = 'none';
        document.getElementById('btn-save-edit').style.display = 'none';
    } else {
        subEl.textContent = 'Pratinjau Kertas A4 (Times New Roman 12pt · Margin 4-4-3-3 cm) — Klik teks untuk mengedit langsung';
        genBtn.style.display = 'inline-flex';
        genBtn.textContent = viewKey === 'daftar_pustaka'
            ? 'Rapikan / Susun Daftar Pustaka'
            : ((chapterContents[viewKey] || '').trim() ? 'Susun Ulang Bab Ini' : 'Tulis Bab Ini');
    }

    if (window.innerWidth <= 900) {
        document.getElementById('studio-shell').classList.remove('mobile-sidebar-open');
    }
    renderActiveSheet();
}

function renderLoadingSheet(title, subtitle) {
    const container = document.getElementById('sheets-container');
    container.innerHTML = `
        <div class="ai-loading-card">
            <div class="ai-spinner"></div>
            <div class="ai-loading-title">${escapeHtml(title)}</div>
            <div class="ai-loading-sub">${escapeHtml(subtitle)}</div>
            <div class="shimmer-lines">
                <div class="shimmer-bar" style="width: 92%"></div>
                <div class="shimmer-bar" style="width: 78%"></div>
                <div class="shimmer-bar" style="width: 85%"></div>
            </div>
        </div>
    `;
}

function formatInlineMarkdown(text) {
    return escapeHtml(text)
        .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.+?)\*/g, '<em>$1</em>');
}

function markdownToAcademicHtml(mdText, isDaftarPustaka = false) {
    if (!mdText || !mdText.trim()) return '';
    const lines = mdText.replace(/\r\n/g, '\n').split('\n');
    let html = '';
    let inUl = false;
    let inOl = false;
    let inTable = false;
    let tableRows = [];

    const flushLists = () => {
        if (inUl) { html += '</ul>'; inUl = false; }
        if (inOl) { html += '</ol>'; inOl = false; }
    };
    const flushTable = () => {
        if (!inTable || tableRows.length === 0) { inTable = false; tableRows = []; return; }
        html += '<div class="table-wrap"><table>';
        tableRows.forEach((cols, idx) => {
            if (idx === 1 && cols.every((c) => /^[:\-\s]+$/.test(c))) return;
            const tag = idx === 0 ? 'th' : 'td';
            html += '<tr>' + cols.map((c) => `<${tag}>${formatInlineMarkdown(c)}</${tag}>`).join('') + '</tr>';
        });
        html += '</table></div>';
        inTable = false;
        tableRows = [];
    };

    lines.forEach((raw) => {
        const line = raw.trim();
        if (!line) {
            flushLists();
            flushTable();
            return;
        }
        if (line.startsWith('|') && line.endsWith('|')) {
            flushLists();
            inTable = true;
            const cells = line.slice(1, -1).split('|').map((c) => c.trim());
            tableRows.push(cells);
            return;
        } else if (inTable) {
            flushTable();
        }

        if (isDaftarPustaka) {
            flushLists();
            if (/^#+\s*/.test(line)) {
                html += `<h2>${formatInlineMarkdown(line.replace(/^#+\s*/, ''))}</h2>`;
            } else {
                html += `<p class="dp-entry">${formatInlineMarkdown(line)}</p>`;
            }
            return;
        }

        if (/^#\s+/.test(line)) {
            flushLists();
            html += `<h2>${formatInlineMarkdown(line.replace(/^#\s+/, ''))}</h2>`;
        } else if (/^##\s+/.test(line)) {
            flushLists();
            html += `<h3>${formatInlineMarkdown(line.replace(/^##\s+/, ''))}</h3>`;
        } else if (/^###\s+/.test(line)) {
            flushLists();
            html += `<h4>${formatInlineMarkdown(line.replace(/^###\s+/, ''))}</h4>`;
        } else if (/^[-*]\s+/.test(line)) {
            if (inOl) { html += '</ol>'; inOl = false; }
            if (!inUl) { html += '<ul>'; inUl = true; }
            html += `<li>${formatInlineMarkdown(line.replace(/^[-*]\s+/, ''))}</li>`;
        } else if (/^\d+\.\s+/.test(line)) {
            if (inUl) { html += '</ul>'; inUl = false; }
            if (!inOl) { html += '<ol>'; inOl = true; }
            html += `<li>${formatInlineMarkdown(line.replace(/^\d+\.\s+/, ''))}</li>`;
        } else {
            flushLists();
            html += `<p>${formatInlineMarkdown(line)}</p>`;
        }
    });

    flushLists();
    flushTable();
    return html;
}

function buildCoverAndFrontMatterHtml() {
    const nama = projectMeta.nama || @json(auth()->user()->name ?? 'Mahasiswa');
    const nim = projectMeta.nim || '';
    const kampus = projectMeta.kampus || 'UNIVERSITAS';
    const fakultas = projectMeta.fakultas || '';
    const logo = projectMeta.logo_base64 || '';
    const tahun = new Date().getFullYear();
    const docKind = allowBab45 ? 'SKRIPSI' : 'PROPOSAL SKRIPSI';

    const tocChapters = allowBab45
        ? [
            { title: 'BAB I PENDAHULUAN', subs: ['1.1 Latar Belakang Masalah', '1.2 Identifikasi Masalah', '1.3 Batasan Masalah', '1.4 Rumusan Masalah', '1.5 Tujuan Penelitian', '1.6 Manfaat Penelitian', '1.7 Sistematika Penulisan'] },
            { title: 'BAB II TINJAUAN PUSTAKA', subs: ['2.1 Landasan Teori', '2.2 Penelitian Terdahulu', '2.3 Kerangka Pemikiran', '2.4 Hipotesis Penelitian'] },
            { title: 'BAB III METODOLOGI PENELITIAN', subs: ['3.1 Jenis dan Desain Penelitian', '3.2 Lokasi dan Waktu Penelitian', '3.3 Populasi dan Sampel', '3.4 Teknik Pengumpulan Data', '3.5 Teknik Analisis Data'] },
            { title: 'BAB IV HASIL PENELITIAN DAN PEMBAHASAN', subs: ['4.1 Gambaran Umum Objek', '4.2 Hasil Penelitian', '4.3 Pembahasan'] },
            { title: 'BAB V PENUTUP', subs: ['5.1 Kesimpulan', '5.2 Saran'] }
        ]
        : [
            { title: 'BAB I PENDAHULUAN', subs: ['1.1 Latar Belakang Masalah', '1.2 Identifikasi Masalah', '1.3 Batasan Masalah', '1.4 Rumusan Masalah', '1.5 Tujuan Penelitian', '1.6 Manfaat Penelitian', '1.7 Sistematika Penulisan'] },
            { title: 'BAB II TINJAUAN PUSTAKA', subs: ['2.1 Landasan Teori', '2.2 Penelitian Terdahulu', '2.3 Kerangka Pemikiran', '2.4 Hipotesis Penelitian'] },
            { title: 'BAB III METODOLOGI PENELITIAN', subs: ['3.1 Jenis dan Desain Penelitian', '3.2 Lokasi dan Waktu Penelitian', '3.3 Populasi dan Sampel', '3.4 Teknik Pengumpulan Data', '3.5 Teknik Analisis Data'] }
        ];

    const tocHtml = tocChapters.map((ch) => {
        const subsHtml = ch.subs.map((s) => `<div class="toc-row sub"><span>${escapeHtml(s)}</span><span class="toc-dots"></span><span>•</span></div>`).join('');
        return `<div class="toc-row main"><span>${escapeHtml(ch.title)}</span><span class="toc-dots"></span><span>•</span></div>${subsHtml}`;
    }).join('') + `<div class="toc-row main"><span>DAFTAR PUSTAKA</span><span class="toc-dots"></span><span>•</span></div>`;

    return `
        <div class="a4-sheet cover-sheet">
            <div>
                <div class="cover-kind">${docKind}</div>
                <div class="cover-title">${escapeHtml(projectTitle)}</div>
                <div class="cover-sub">Diajukan sebagai Salah Satu Syarat untuk Memperoleh Gelar Sarjana pada Program Studi ${escapeHtml(projectProdi)}</div>
            </div>
            <div class="cover-logo-box">
                ${logo ? `<img src="${logo}" alt="Logo Kampus" class="cover-logo-img">` : '<div style="font-size:12px;color:#64748b;">[Logo Kampus]</div>'}
            </div>
            <div>
                <div class="cover-author-block">
                    <div>Oleh:</div>
                    <div><strong>${escapeHtml(nama)}</strong></div>
                    ${nim ? `<div>NIM / NPM: ${escapeHtml(nim)}</div>` : ''}
                </div>
                <div class="cover-campus-block">
                    <div>PROGRAM STUDI ${escapeHtml(projectProdi)}</div>
                    ${fakultas ? `<div>${escapeHtml(fakultas)}</div>` : ''}
                    <div>${escapeHtml(kampus)}</div>
                    <div>${tahun}</div>
                </div>
            </div>
        </div>
        <div class="a4-sheet front-body">
            <div class="front-heading">DAFTAR ISI</div>
            <div class="toc-list">${tocHtml}</div>
        </div>
    `;
}

function buildSubchapterOptions() {
    const allowedChapters = allowBab45
        ? ['bab1', 'bab2', 'bab3', 'bab4', 'bab5']
        : ['bab1', 'bab2', 'bab3'];

    const options = [];
    const seen = new Set();

    defaultSubchapters.forEach((item) => {
        if (!allowedChapters.includes(item.chapter)) return;
        const val = `${item.chapter}|${item.label}`;
        seen.add(item.label.toLowerCase());
        options.push({ value: val, label: item.label });
    });

    // Tambahkan sub-bab aktual dari naskah bila ada yang kustom
    allowedChapters.forEach((ch) => {
        const text = chapterContents[ch] || '';
        const matches = text.match(/^##\s+(.+)$/gm) || [];
        matches.forEach((heading) => {
            const clean = heading.replace(/^##\s+/, '').trim();
            if (clean && !seen.has(clean.toLowerCase())) {
                seen.add(clean.toLowerCase());
                options.push({ value: `${ch}|${clean}`, label: clean });
            }
        });
    });

    return options;
}

function extractSubchapterPreview(chapterKey, subLabel) {
    const content = (chapterContents[chapterKey] || '').trim();
    if (!content) {
        return 'Bab ini belum disusun. Kamu tetap bisa memproses atau membuat ulang isi sub-bab langsung dari instruksi di atas.';
    }
    if (subLabel.toLowerCase().includes('semua bab')) {
        return content.slice(0, 900) + (content.length > 900 ? '\n...' : '');
    }
    const codeMatch = subLabel.match(/^(\d+\.\d+)/);
    const code = codeMatch ? codeMatch[1] : '';
    const titlePart = subLabel.replace(/^\d+\.\d+\s*/, '').trim();
    const escapedCode = code.replace('.', '\\.');
    const escapedTitle = titlePart.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const regex = code
        ? new RegExp(`##\\s*${escapedCode}[^\\n]*\\n([\\s\\S]*?)(?=\\n##\\s|$)`, 'i')
        : new RegExp(`##\\s*[^\\n]*${escapedTitle}[^\\n]*\\n([\\s\\S]*?)(?=\\n##\\s|$)`, 'i');
    const m = content.match(regex);
    if (m && m[0]) {
        const slice = m[0].trim();
        return slice.slice(0, 900) + (slice.length > 900 ? '\n...' : '');
    }
    return `Sub-bab "${subLabel}" belum ditemukan secara eksplisit pada ${chapterKey.toUpperCase()}, AI akan menyisipkan/memperbaruinya ke dalam ${chapterKey.toUpperCase()}.`;
}

function updateRevisePreview() {
    const sel = document.getElementById('revise-subchapter-select');
    const box = document.getElementById('revise-preview-text');
    if (!sel || !box) return;
    if (!sel.value) {
        box.textContent = 'Pilih sub-bab di atas untuk melihat cuplikan teks yang akan direvisi.';
        return;
    }
    const [chapterKey, subLabel] = sel.value.split('|');
    box.textContent = extractSubchapterPreview(chapterKey, subLabel);
}

function setReviseMode(mode) {
    currentReviseMode = mode;
    document.querySelectorAll('.revisi-mode-tab').forEach((btn) => {
        btn.classList.toggle('active', btn.dataset.mode === mode);
    });
    const ta = document.getElementById('revise-instruction');
    if (ta) {
        ta.placeholder = mode === 'content'
            ? 'Contoh: Pertajam fenomena gap penelitian dengan data tahun 2024–2025, tambahkan sitasi terbaru, atau sesuaikan dengan catatan dosen pembimbing...'
            : 'Contoh: Rapikan gaya bahasa agar lebih baku sesuai EYD/PUEBI, perjelas transisi antarparagraf, dan hilangkan pengulangan kalimat...';
    }
}

function renderLabRevisi() {
    const options = buildSubchapterOptions();
    const optHtml = options.map((o) => `<option value="${escapeHtml(o.value)}">${escapeHtml(o.label)}</option>`).join('');
    const container = document.getElementById('sheets-container');

    container.innerHTML = `
        <div class="revisi-card" id="view-revisi">
            <div class="revisi-header">
                <div class="revisi-title" style="display: flex; align-items: center; gap: 8px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 2v7.31L4.62 17.6A2 2 0 0 0 6.3 20.5h11.4a2 2 0 0 0 1.68-2.9L14 9.31V2z"/><line x1="8.5" y1="2" x2="15.5" y2="2"/><line x1="14" y1="9" x2="10" y2="9"/></svg>
                    <span>Lab Revisi Sub-Bab</span>
                </div>
                <span class="project-mode-badge">Revisi Terfokus per Sub-Bab</span>
            </div>

            <div class="revisi-mode-tabs">
                <button type="button" class="revisi-mode-tab ${currentReviseMode === 'content' ? 'active' : ''}" data-mode="content" onclick="setReviseMode('content')">
                    Revisi Isi Skripsi
                </button>
                <button type="button" class="revisi-mode-tab ${currentReviseMode === 'format' ? 'active' : ''}" data-mode="format" onclick="setReviseMode('format')">
                    Revisi Format Penulisan
                </button>
            </div>

            <label class="form-label" for="revise-subchapter-select">Pilih Sub-bab yang Ingin Direvisi</label>
            <select id="revise-subchapter-select" class="revisi-select" onchange="updateRevisePreview()">
                <option value="">— Pilih sub-bab yang ingin direvisi —</option>
                ${optHtml}
            </select>

            <div class="revisi-preview-box">
                <div class="revisi-preview-label">Cuplikan Teks Saat Ini</div>
                <div class="revisi-preview-text" id="revise-preview-text">Pilih sub-bab di atas untuk melihat cuplikan teks yang akan direvisi.</div>
            </div>

            <label class="form-label" for="revise-instruction">Catatan / Instruksi Revisi Dosen Pembimbing</label>
            <textarea id="revise-instruction" class="revisi-textarea" placeholder="Contoh: Pertajam fenomena gap penelitian dengan data empiris terbaru, perjelas rumusan masalah agar sinkron dengan metode analisis..."></textarea>

            <label class="form-label" for="revise-file">Lampiran Foto Coretan / Dokumen Revisi (Opsional)</label>
            <input type="file" id="revise-file" class="revisi-file" accept=".jpg,.jpeg,.png,.webp,.pdf,.docx,.txt">

            <div class="revisi-actions">
                <span style="font-size: 12px; color: var(--text-muted);">Perubahan hanya memperbarui sub-bab atau bab target tanpa merusak struktur bab lain.</span>
                <button type="button" class="btn-top primary" id="btn-submit-revisi" onclick="submitLabRevisi()">
                    Proses Revisi Sub-Bab
                </button>
            </div>
        </div>
    `;
}

function renderActiveSheet() {
    const container = document.getElementById('sheets-container');
    if (activeView === 'revisi') {
        renderLabRevisi();
        return;
    }

    const raw = (chapterContents[activeView] || '').trim();
    if (!raw) {
        container.innerHTML = `
            <div class="a4-sheet empty-sheet">
                <h3>${escapeHtml(chapterLabels[activeView] || 'Bagian Belum Disusun')}</h3>
                <p>Bagian ini belum ditulis. Klik tombol di bawah untuk menyusun naskah lengkap berstandar skripsi S1 secara otomatis.</p>
                <button type="button" class="btn-top primary" onclick="generateCurrentView()">Mulai Susun Bagian Ini</button>
            </div>
        `;
        return;
    }

    const frontHtml = '';
    const bodyHtml = markdownToAcademicHtml(raw, activeView === 'daftar_pustaka');

    container.innerHTML = `
        ${frontHtml}
        <div class="a4-sheet">
            <div class="paper-editor" id="paper-editor" contenteditable="true" spellcheck="false">${bodyHtml}</div>
        </div>
    `;

    const editor = document.getElementById('paper-editor');
    if (editor) {
        editor.addEventListener('input', () => {
            isDirty = true;
            document.getElementById('btn-save-edit').style.display = 'inline-flex';
        });
    }
}

async function generateCurrentView() {
    const model = document.getElementById('studio-model').value;
    const dot = document.getElementById(`dot-${activeView}`);
    if (dot) dot.classList.add('generating');

    if (activeView === 'daftar_pustaka') {
        renderLoadingSheet('Menyusun & Merapikan Daftar Pustaka...', 'AI sedang memformat referensi terverifikasi dalam gaya APA 7th Edition.');
        try {
            const res = await fetch(`/project/${projectId}/refine-references`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ model })
            });
            const data = await res.json();
            if (!res.ok || data.status !== 'success') throw new Error(data.message || 'Gagal merapikan daftar pustaka.');
            chapterContents.daftar_pustaka = data.daftar_pustaka || '';
            showToast('Daftar Pustaka berhasil diperbarui.', 'success');
        } catch (err) {
            showToast(err.message || 'Terjadi kesalahan.', 'error');
        } finally {
            if (dot) dot.classList.remove('generating');
            updateProgressUI();
            renderActiveSheet();
        }
        return;
    }

    renderLoadingSheet(`Menyusun ${chapterLabels[activeView]}...`, 'AI sedang menulis paragraf akademik mendalam beserta referensi ilmiah.');
    try {
        const res = await fetch(`/project/${projectId}/generate`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ chapter: activeView, model })
        });
        const data = await res.json();
        if (!res.ok || data.status !== 'success') throw new Error(data.message || 'Gagal menyusun bab.');
        chapterContents[activeView] = data.content || '';
        if (data.daftar_pustaka) chapterContents.daftar_pustaka = data.daftar_pustaka;
        showToast(`${chapterLabels[activeView]} selesai disusun.`, 'success');
    } catch (err) {
        showToast(err.message || 'Gagal memproses AI.', 'error');
    } finally {
        if (dot) dot.classList.remove('generating');
        updateProgressUI();
        renderActiveSheet();
    }
}

async function generateSequential() {
    const btn = document.getElementById('btn-generate-all');
    const keys = allowBab45 ? ['bab1', 'bab2', 'bab3', 'bab4', 'bab5'] : ['bab1', 'bab2', 'bab3'];
    const model = document.getElementById('studio-model').value;
    btn.disabled = true;

    for (const key of keys) {
        selectChapterView(key);
        const dot = document.getElementById(`dot-${key}`);
        if (dot) dot.classList.add('generating');
        renderLoadingSheet(`Menyusun Berurutan: ${chapterLabels[key]}...`, 'Mohon tunggu, AI sedang menulis setiap bab secara konsisten.');
        try {
            const res = await fetch(`/project/${projectId}/generate`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ chapter: key, model })
            });
            const data = await res.json();
            if (!res.ok || data.status !== 'success') throw new Error(data.message || `Gagal menyusun ${key}`);
            chapterContents[key] = data.content || '';
            if (data.daftar_pustaka) chapterContents.daftar_pustaka = data.daftar_pustaka;
            updateProgressUI();
        } catch (err) {
            showToast(err.message || `Berhenti di ${key}`, 'error');
            if (dot) dot.classList.remove('generating');
            break;
        }
        if (dot) dot.classList.remove('generating');
    }

    btn.disabled = false;
    renderActiveSheet();
    showToast('Penyusunan otomatis selesai!', 'success');
}

function upgradeToFullSkripsi() {
    allowBab45 = true;
    const nav = document.querySelector('.sidebar-nav');
    const dpBtn = document.getElementById('nav-item-daftar_pustaka');
    if (!document.getElementById('nav-item-bab4')) {
        ['bab4', 'bab5'].forEach((k, idx) => {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'nav-item';
            b.id = `nav-item-${k}`;
            b.onclick = () => selectChapterView(k);
            b.innerHTML = `<span class="nav-item-left"><span class="nav-item-text">${idx === 0 ? 'Bab IV Hasil Penelitian' : 'Bab V Penutup'}</span></span><span class="status-dot" id="dot-${k}"></span>`;
            nav.insertBefore(b, dpBtn);
        });
    }
    const kindInput = document.querySelector('#quick-download-form input[name="jenis_dokumen"]');
    if (kindInput) kindInput.value = 'SKRIPSI';
    updateProgressUI();
    selectChapterView('bab4');
    showToast('Mode diperluas ke Full Skripsi (Bab I–V).', 'success');
}

async function submitLabRevisi() {
    const sel = document.getElementById('revise-subchapter-select');
    const instrEl = document.getElementById('revise-instruction');
    const fileEl = document.getElementById('revise-file');
    const model = document.getElementById('studio-model').value;

    if (!sel || !sel.value) {
        showToast('Pilih sub-bab yang ingin direvisi terlebih dahulu.', 'error');
        return;
    }
    const instruction = (instrEl?.value || '').trim();
    const hasFile = fileEl && fileEl.files && fileEl.files.length > 0;
    if (!instruction && !hasFile) {
        showToast('Masukkan catatan revisi atau unggah foto/dokumen catatan dosen.', 'error');
        return;
    }

    const [chapterKey, subLabel] = sel.value.split('|');
    const formData = new FormData();
    formData.append('chapter', chapterKey);
    formData.append('subchapter', subLabel);
    formData.append('revision_mode', currentReviseMode);
    formData.append('instruction', instruction || 'Revisi bagian ini sesuai lampiran catatan dosen.');
    formData.append('model', model);
    if (hasFile) {
        formData.append('revision_file', fileEl.files[0]);
    }

    renderLoadingSheet(`Memproses Revisi ${subLabel}...`, 'AI sedang menyempurnakan sub-bab target sesuai catatan & mode yang dipilih.');
    try {
        const res = await fetch(`/project/${projectId}/revise`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            body: formData
        });
        const data = await res.json();
        if (!res.ok || data.status !== 'success') throw new Error(data.message || 'Gagal memproses revisi.');
        chapterContents[chapterKey] = data.content || chapterContents[chapterKey];
        updateProgressUI();
        showToast(`Revisi ${subLabel} berhasil diterapkan!`, 'success');
        selectChapterView(chapterKey);
    } catch (err) {
        showToast(err.message || 'Terjadi kesalahan saat revisi.', 'error');
        renderLabRevisi();
    }
}

async function saveActiveEdit() {
    const editor = document.getElementById('paper-editor');
    if (!editor || activeView === 'revisi') return;
    const htmlContent = editor.innerText.trim();
    const btn = document.getElementById('btn-save-edit');
    btn.disabled = true;

    try {
        const res = await fetch(`/project/${projectId}/save-chapter`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ chapter: activeView, content: htmlContent })
        });
        const data = await res.json();
        if (!res.ok || data.status !== 'success') throw new Error(data.message || 'Gagal menyimpan perubahan.');
        chapterContents[activeView] = data.content || htmlContent;
        isDirty = false;
        btn.style.display = 'none';
        showToast('Perubahan naskah berhasil disimpan.', 'success');
    } catch (err) {
        showToast(err.message || 'Gagal menyimpan.', 'error');
    } finally {
        btn.disabled = false;
    }
}

function submitQuickDownload(format) {
    const form = document.getElementById('quick-download-form');
    form.action = format === 'pdf'
        ? @json(route('project.download.pdf', ['id' => $project->id]))
        : @json(route('app.downloadDocx', ['id' => $project->id]));
    form.submit();
}

updateProgressUI();
selectChapterView('bab1');
</script>
</body>
</html>
