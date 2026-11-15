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
            overflow: hidden;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-app);
            color: var(--text-primary);
            -webkit-font-smoothing: antialiased;
        }

        .studio-shell {
            display: flex;
            height: 100vh;
            max-height: 100vh;
            width: 100%;
            overflow: hidden;
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
            max-height: 100vh;
            overflow: hidden;
            position: relative;
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
            line-height: 1.45;
            word-break: break-word;
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
            height: 100vh;
            max-height: 100vh;
            display: flex;
            flex-direction: column;
            background: var(--bg-canvas);
            overflow: hidden;
        }

        .studio-topbar {
            height: var(--topbar-h);
            min-height: var(--topbar-h);
            flex-shrink: 0;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid var(--border);
            padding: 8px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
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
            line-height: 1.35;
            word-break: break-word;
            max-width: 650px;
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
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 24px 20px 64px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 18px;
            -webkit-overflow-scrolling: touch;
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
            padding: 75px 60px 60px 75px; /* Rasio standar margin skripsi 4-4-3-3 cm */
            color: #111827;
            font-family: "Times New Roman", Georgia, serif;
            font-size: 16px;
            line-height: 1.75;
            box-sizing: border-box;
            word-break: break-word;
        }

        .cover-sheet {
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 980px;
            box-sizing: border-box;
            padding: 55px 50px 45px 55px;
        }
        .cover-top-block {
            margin-bottom: 16px;
        }
        .cover-kind {
            font-size: 14pt;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            margin-bottom: 12px;
            color: #0f172a;
        }
        .cover-title {
            font-size: 14.5pt;
            font-weight: 700;
            line-height: 1.35;
            text-transform: uppercase;
            margin-bottom: 12px;
            color: #0f172a;
        }
        .cover-sub {
            font-size: 11pt;
            line-height: 1.45;
            max-width: 540px;
            margin: 0 auto;
            color: #334155;
        }
        .cover-logo-box {
            margin: 20px auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .cover-logo-img {
            max-width: 110px;
            max-height: 110px;
            width: auto;
            height: auto;
            object-fit: contain;
            display: block;
            margin: 0 auto;
        }
        .cover-bottom-block {
            margin-top: auto;
            padding-top: 16px;
        }
        .cover-author-block {
            margin-bottom: 20px;
            font-size: 12pt;
            line-height: 1.6;
            color: #0f172a;
        }
        .cover-campus-block {
            font-size: 12.5pt;
            font-weight: 700;
            line-height: 1.4;
            text-transform: uppercase;
            color: #0f172a;
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
        .toc-page {
            font-weight: 700;
            min-width: 24px;
            text-align: right;
            color: #1e293b;
            font-size: 14px;
        }
        .sheet-footer {
            margin-top: 38px;
            padding-top: 14px;
            text-align: center;
            font-size: 13.5px;
            font-family: 'Times New Roman', serif;
            font-weight: 600;
            color: #475569;
            border-top: 1px solid rgba(203, 213, 225, 0.6);
            user-select: none;
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
            margin: 18px 0;
            -webkit-overflow-scrolling: touch;
        }
        .paper-editor table {
            width: 100%;
            min-width: 620px;
            border-collapse: collapse;
            font-size: 11pt;
            line-height: 1.45;
            font-family: "Times New Roman", Georgia, serif;
            margin: 8px auto 16px;
            word-break: normal !important;
            overflow-wrap: break-word !important;
            table-layout: auto;
            background: #ffffff;
        }
        .paper-editor th, .paper-editor td {
            border: 1px solid #0f172a;
            padding: 8px 10px;
            vertical-align: top;
            text-align: left;
            text-indent: 0 !important;
            word-break: normal !important;
            overflow-wrap: break-word !important;
            hyphens: none !important;
            box-sizing: border-box;
        }
        .paper-editor th {
            background: #f1f5f9;
            font-weight: 700;
            color: #0f172a;
            text-align: center;
            vertical-align: middle;
            font-size: 11pt;
            border-bottom: 2px solid #0f172a;
        }
        .paper-editor table th:first-child,
        .paper-editor table td:first-child {
            width: 44px !important;
            min-width: 44px !important;
            max-width: 50px !important;
            text-align: center !important;
            white-space: nowrap !important;
        }
        .paper-editor table th:nth-child(2),
        .paper-editor table td:nth-child(2) {
            min-width: 110px;
            width: 120px;
            white-space: normal;
        }
        .paper-editor table th:nth-child(3),
        .paper-editor table td:nth-child(3) {
            min-width: 140px;
            white-space: normal;
        }
        .paper-editor table th:nth-child(4),
        .paper-editor table td:nth-child(4) {
            min-width: 110px;
            width: 125px;
            white-space: normal;
        }
        .paper-editor table th:nth-child(5),
        .paper-editor table td:nth-child(5) {
            min-width: 130px;
            white-space: normal;
        }
        .paper-editor table th:nth-child(6),
        .paper-editor table td:nth-child(6) {
            min-width: 140px;
            white-space: normal;
        }
        .academic-diagram-box {
            margin: 18px 0;
            padding: 16px 20px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-left: 4px solid #2563eb;
            border-radius: 8px;
            font-family: 'Consolas', 'Courier New', monospace;
            font-size: 10.5pt;
            line-height: 1.5;
            white-space: pre;
            overflow-x: auto;
            color: #0f172a;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }

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

        /* Responsive Styles */
        @media (max-width: 900px) {
            .studio-sidebar {
                position: fixed;
                left: 0;
                top: 0;
                transform: translateX(-100%);
                margin-left: 0 !important;
                z-index: 100;
            }
            .studio-shell.mobile-sidebar-open .studio-sidebar {
                transform: translateX(0);
            }
            .studio-shell.mobile-sidebar-open .sidebar-backdrop {
                display: block;
            }
        }

        @media (max-width: 768px) {
            .studio-topbar {
                height: auto;
                min-height: 50px;
                padding: 8px 12px;
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                gap: 8px;
            }
            .topbar-left {
                width: auto;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .topbar-brand-label {
                font-size: 13px !important;
            }
            .topbar-actions {
                width: auto;
                display: flex;
                align-items: center;
                justify-content: flex-end;
                gap: 6px;
                flex-wrap: nowrap;
            }
            .topbar-actions .model-select {
                max-width: 125px;
                height: 32px;
                font-size: 11px;
                padding: 4px 6px;
            }
            .topbar-actions #btn-generate-current {
                height: 32px;
                font-size: 11.5px;
                padding: 0 10px;
                white-space: nowrap;
            }
            .topbar-actions #btn-save-edit {
                height: 32px;
                font-size: 11.5px;
                padding: 0 10px;
                white-space: nowrap;
            }
            .chapter-bar {
                padding: 10px 12px;
                flex-direction: column;
                align-items: stretch;
                gap: 8px;
            }
            .chapter-bar-actions {
                width: 100%;
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
            }
            .chapter-bar-actions .btn-top {
                flex: 1 1 calc(50% - 6px);
                min-width: 120px;
                justify-content: center;
                font-size: 11.5px;
                padding: 6px 8px;
            }
            .canvas-scroll {
                padding: 10px 8px 60px;
            }
            #sheets-container {
                width: 100%;
                max-width: 100%;
            }
            .a4-sheet {
                width: 100%;
                max-width: 100%;
                box-sizing: border-box;
                padding: 24px 16px 20px 20px;
                font-size: 13.5px;
                line-height: 1.6;
                border-radius: 6px;
                box-shadow: 0 2px 10px rgba(15, 23, 42, 0.05);
                word-break: break-word;
                overflow-x: hidden;
            }
            .cover-sheet {
                min-height: unset !important;
                height: auto !important;
                aspect-ratio: 210 / 297;
                padding: 28px 18px 24px 20px !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
                box-sizing: border-box;
            }
            .cover-kind {
                font-size: 11px !important;
                margin-bottom: 6px !important;
            }
            .cover-title {
                font-size: 12.5px !important;
                line-height: 1.35 !important;
                margin-bottom: 6px !important;
            }
            .cover-sub {
                font-size: 10px !important;
                line-height: 1.35 !important;
                margin: 0 auto 8px !important;
            }
            .cover-logo-box {
                margin: 8px auto !important;
            }
            .cover-logo-img {
                width: 60px !important;
                height: 60px !important;
            }
            .cover-bottom-block {
                margin-top: auto !important;
                padding-top: 8px !important;
            }
            .cover-author-block {
                margin-bottom: 10px !important;
                font-size: 11px !important;
                line-height: 1.35 !important;
            }
            .cover-campus-block {
                font-size: 11px !important;
                line-height: 1.35 !important;
            }
            .front-heading {
                font-size: 15px;
                margin-bottom: 16px;
            }
            .toc-row {
                font-size: 13px;
                gap: 6px;
            }
            .toc-row.sub {
                padding-left: 12px;
            }
            .revisi-card {
                padding: 16px;
            }
        }

        /* Olah Data Modal Modern Styling */
        .olah-chip {
            padding: 7px 14px;
            font-size: 12px;
            font-weight: 500;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 999px;
            color: #334155;
            cursor: pointer;
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-family: inherit;
        }
        .olah-chip:hover {
            background: #eff6ff;
            border-color: #93c5fd;
            color: #1d4ed8;
            transform: translateY(-1px);
        }
        .olah-input {
            width: 100%;
            padding: 9px 13px;
            border: 1.5px solid #cbd5e1;
            border-radius: 9px;
            font-size: 13px;
            color: #0f172a;
            background: #ffffff;
            box-sizing: border-box;
            transition: border-color 0.15s, box-shadow 0.15s;
            font-family: inherit;
        }
        .olah-input:focus {
            border-color: #2563eb;
            outline: none;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
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
            <a href="{{ route('app.create') }}#projects" class="back-link">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                <span>Kembali ke Proyek</span>
            </a>
            <span class="project-mode-badge" style="margin-top: 6px;">
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
            <button type="button"
                    class="nav-item"
                    id="nav-item-all"
                    onclick="selectChapterView('all')"
                    style="margin-bottom: 6px; background: linear-gradient(135deg, #eff6ff 0%, #f8fafc 100%); border: 1px solid #bfdbfe;">
                <span class="nav-item-left">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    <span class="nav-item-text" style="color: #1e40af; font-weight: 700;">Lihat Semua Bab (Full)</span>
                </span>
                <span style="font-size: 10px; font-weight: 700; color: #2563eb; background: #dbeafe; padding: 2px 6px; border-radius: 999px;">A4 Full</span>
            </button>
            <button type="button"
                    class="nav-item"
                    id="nav-item-front"
                    onclick="selectChapterView('front')"
                    style="margin-bottom: 8px; padding: 7px 10px; height: auto;">
                <span class="nav-item-left" style="align-items: flex-start;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-top: 2px; flex-shrink: 0;"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                    <div style="display: flex; flex-direction: column; text-align: left; line-height: 1.25;">
                        <span style="font-weight: 700; font-size: 12.5px;">Sampul &amp;</span>
                        <span style="font-size: 11.5px; opacity: 0.9;">Bagian Depan</span>
                    </div>
                </span>
                <span style="font-size: 10px; font-weight: 700; color: #16a34a; background: #dcfce7; padding: 2px 6px; border-radius: 999px; align-self: center;">Lengkap</span>
            </button>
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
                    <span class="nav-item-text">Revisi</span>
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

            <!-- Format & Document Settings Card (Tersimpan di scroll nav agar tombol download selalu terlihat) -->
            <div class="format-settings-card" style="padding: 10px; margin-top: 14px; margin-bottom: 8px; background: #f8fafc; border: 1px solid var(--border); border-radius: 9px; font-size: 11.5px;">
                <div style="font-weight: 700; color: var(--text-primary); margin-bottom: 6px; display: flex; align-items: center; justify-content: space-between;">
                    <span>Format &amp; Margin</span>
                    <span style="font-size: 10px; color: var(--accent); font-weight: 700; background: #e0f2fe; padding: 1px 6px; border-radius: 4px;">4-4-3-3 cm</span>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin-bottom: 6px;">
                    <div>
                        <label style="display: block; font-size: 9.5px; color: var(--text-secondary); margin-bottom: 2px;">Font Naskah</label>
                        <select id="doc-font-select" onchange="updateDocFont(this.value)" style="width: 100%; font-size: 10.5px; padding: 3px 4px; border: 1px solid var(--border-strong); border-radius: 5px; background: #fff;">
                            <option value="times" selected>Times New Roman 12pt</option>
                            <option value="arial">Arial 11pt</option>
                            <option value="georgia">Georgia 11pt</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 9.5px; color: var(--text-secondary); margin-bottom: 2px;">Margin K-A-K-B (cm)</label>
                        <div style="display: flex; gap: 2px;">
                            <input type="number" id="m-left" value="4" min="2" max="5" style="width: 25%; font-size: 10.5px; padding: 2px 1px; text-align: center; border: 1px solid var(--border-strong); border-radius: 4px;" title="Kiri (cm)" onchange="updateMargins()">
                            <input type="number" id="m-top" value="4" min="2" max="5" style="width: 25%; font-size: 10.5px; padding: 2px 1px; text-align: center; border: 1px solid var(--border-strong); border-radius: 4px;" title="Atas (cm)" onchange="updateMargins()">
                            <input type="number" id="m-right" value="3" min="2" max="5" style="width: 25%; font-size: 10.5px; padding: 2px 1px; text-align: center; border: 1px solid var(--border-strong); border-radius: 4px;" title="Kanan (cm)" onchange="updateMargins()">
                            <input type="number" id="m-bottom" value="3" min="2" max="5" style="width: 25%; font-size: 10.5px; padding: 2px 1px; text-align: center; border: 1px solid var(--border-strong); border-radius: 4px;" title="Bawah (cm)" onchange="updateMargins()">
                        </div>
                    </div>
                </div>

                <div>
                    <label style="display: block; font-size: 9.5px; color: var(--text-secondary); margin-bottom: 2px;">Logo Kampus (Cover)</label>
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <img id="sidebar-logo-preview" src="{{ !empty($meta['logo_base64']) ? $meta['logo_base64'] : '/images/academic_seal.svg' }}" alt="Logo" style="width: 24px; height: 24px; object-fit: contain; border-radius: 4px; border: 1px solid #cbd5e1; background: #fff; cursor: pointer;" title="Klik untuk ganti logo kampus" onclick="document.getElementById('sidebar-logo-upload').click();" onerror="this.onerror=null;this.src='/images/academic_seal.svg';">
                        <button type="button" onclick="document.getElementById('sidebar-logo-upload').click()" style="flex: 1; padding: 4px 8px; border: 1px dashed var(--accent-border); background: #ffffff; border-radius: 5px; font-size: 10.5px; color: var(--accent); font-weight: 600; text-align: center; cursor: pointer; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-family: inherit;">
                            <span>📷 Ganti Logo</span>
                        </button>
                        <input type="file" id="sidebar-logo-upload" accept="image/png,image/jpeg,image/svg+xml,image/webp" style="display: none;" onchange="handleSidebarLogoUpload(this)">
                    </div>
                </div>
            </div>
        </nav>

        <!-- Sticky Download Buttons (Selalu Tampak di Desktop & HP) -->
        <div style="flex-shrink: 0; padding: 10px 12px; padding-bottom: max(14px, env(safe-area-inset-bottom, 14px)); background: #ffffff; border-top: 1px solid var(--border); box-shadow: 0 -4px 12px rgba(15, 23, 42, 0.06); z-index: 10;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                <button type="button" class="btn-dl-side primary" onclick="submitQuickDownload('docx')">⬇ Word (.docx)</button>
                <button type="button" class="btn-dl-side" onclick="submitQuickDownload('pdf')">⬇ PDF (.pdf)</button>
            </div>
        </div>
    </aside>

    <main class="studio-main">
        <header class="studio-topbar">
            <div class="topbar-left">
                <button type="button" class="btn-icon" onclick="toggleStudioSidebar()" aria-label="Toggle Sidebar" title="Buka/Tutup Sidebar">☰</button>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span class="topbar-brand-label" style="font-size: 14px; font-weight: 700; color: var(--text-primary); letter-spacing: 0.01em;">Workspace Penulisan</span>
                </div>
            </div>

            <div class="topbar-actions">
                <input type="hidden" id="studio-model" value="auto">
                <button type="button" class="btn-top success" id="btn-save-edit" style="display: none;" onclick="saveActiveEdit()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>
                    <span>Simpan Editan</span>
                </button>
                <button type="button" class="btn-top primary" id="btn-generate-current" onclick="generateCurrentView()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    <span>Tulis / Susun Ulang Bab Ini</span>
                </button>
            </div>
        </header>

        <div class="canvas-scroll">
            <div class="chapter-bar" id="chapter-bar">
                <div style="flex: 1; min-width: 220px;">
                    <div class="chapter-bar-title" id="active-chapter-title">BAB I · PENDAHULUAN</div>
                    <div class="chapter-bar-sub" id="active-chapter-sub" style="display: none;"></div>
                </div>
                <div class="chapter-bar-actions" style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap;">
                    <button type="button" class="btn-top" onclick="selectChapterView('all')" title="Lihat naskah lengkap bersambung dari sampul sampai daftar pustaka">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                        <span>Lihat Semua Bab</span>
                    </button>
                    <button type="button" class="btn-top" id="btn-menu-olah-data" onclick="openDataRiilModal()" style="border-color: #cbd5e1;" title="Isi data olahan & rumus penelitian (SPSS / PLS / ML / Eksperimen) untuk Bab 3 & 4">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10"/><path d="M12 20V4"/><path d="M6 20v-6"/></svg>
                        <span>Olah Data Penelitian</span>
                    </button>
                    <button type="button" class="btn-top" onclick="selectChapterView('revisi')">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 2v7.31L4.62 17.6A2 2 0 0 0 6.3 20.5h11.4a2 2 0 0 0 1.68-2.9L14 9.31V2z"/></svg>
                        <span>Buka Revisi</span>
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

<!-- Modal Menu Olah Data & Hasil Penelitian -->
<div id="modal-data-riil" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(5px); z-index: 9999; justify-content: center; align-items: center; padding: 16px; box-sizing: border-box;">
    <div style="background: #ffffff; border-radius: 18px; width: 100%; max-width: 660px; max-height: 90vh; overflow-y: auto; padding: 24px; box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25); box-sizing: border-box; display: flex; flex-direction: column; gap: 18px; border: 1px solid #e2e8f0;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid #e2e8f0; padding-bottom: 14px;">
            <div style="display: flex; gap: 12px; align-items: flex-start;">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); color: #2563eb; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid #bfdbfe;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M18 20V10"/><path d="M12 20V4"/><path d="M6 20v-6"/></svg>
                </div>
                <div>
                    <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 4px 0;">Menu Olah Data &amp; Hasil Penelitian</h3>
                    <p style="font-size: 12.5px; color: #64748b; margin: 0; line-height: 1.5;">Masukkan output olah data riil &amp; rumus agar dijabarkan langkah demi langkah di Bab III &amp; IV.</p>
                </div>
            </div>
            <button type="button" onclick="closeDataRiilModal()" style="border: none; background: #f1f5f9; width: 28px; height: 28px; border-radius: 50%; font-size: 18px; color: #64748b; cursor: pointer; display: flex; align-items: center; justify-content: center; line-height: 1; transition: all 0.15s;">&times;</button>
        </div>

        <!-- Preset Template Cepat -->
        <div>
            <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">Pilih Template Olah Data Siap Pakai:</div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <button type="button" class="olah-chip" onclick="applyOlahDataPreset('spss')">📊 SPSS (Regresi &amp; Asumsi)</button>
                <button type="button" class="olah-chip" onclick="applyOlahDataPreset('pls')">📈 SmartPLS (SEM-PLS)</button>
                <button type="button" class="olah-chip" onclick="applyOlahDataPreset('ml')">🤖 Machine Learning / AI</button>
                <button type="button" class="olah-chip" onclick="applyOlahDataPreset('eksperimen')">🧪 Eksperimen (N-Gain / T-Test)</button>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 14px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 12px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; color: #1e293b; margin-bottom: 5px;">Software / Metode Olah Data</label>
                    <input type="text" id="riil-metode" class="olah-input" placeholder="cth: IBM SPSS Statistics 26 (Regresi Linier)">
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; color: #1e293b; margin-bottom: 5px;">Populasi / Sampel / Dataset Riil</label>
                    <input type="text" id="riil-sampel" class="olah-input" placeholder="cth: 120 Responden Mahasiswa / 1.500 Data">
                </div>
            </div>

            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; color: #1e293b; margin-bottom: 5px;">Rumus Matematika / Statistik Wajib (Langkah demi Langkah)</label>
                <input type="text" id="riil-rumus" class="olah-input" placeholder="cth: Y = a + b1X1 + b2X2 + e, Uji t = (r√n-2)/√(1-r^2), R-Square, atau Akurasi = (TP+TN)/Total">
            </div>

            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; color: #1e293b; margin-bottom: 5px;">Lokasi / Objek / Sumber Data Penelitian</label>
                <input type="text" id="riil-lokasi" class="olah-input" placeholder="cth: Universitas Handayani / PT X / Kaggle Dataset">
            </div>

            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; color: #1e293b; margin-bottom: 5px;">Nilai Metrik / Angka Riil Pengujian (Untuk Narasi &amp; Tabel Bab IV)</label>
                <textarea id="riil-metrik" rows="3" class="olah-input" style="resize: vertical; min-height: 72px;" placeholder="cth: Output SPSS: R-Square = 0.684, F-hitung = 45.21 (Sig 0.000 < 0.05). X1 t-hitung = 3.42 (Sig 0.001), X2 t-hitung = 4.15 (Sig 0.000). Semua lolos uji asumsi klasik."></textarea>
            </div>

            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; color: #1e293b; margin-bottom: 5px;">Teknik Pengujian &amp; Kriteria Uji Hipotesis</label>
                <input type="text" id="riil-evaluasi" class="olah-input" placeholder="cth: Uji Asumsi Klasik (Kolmogorov-Smirnov, VIF, Glejser), Uji t parsial, Uji F simultan, R2">
            </div>
        </div>

        <div style="display: flex; justify-content: flex-end; align-items: center; gap: 10px; border-top: 1px solid #e2e8f0; padding-top: 14px; flex-wrap: wrap;">
            <button type="button" onclick="closeDataRiilModal()" style="padding: 9px 18px; border: 1px solid #cbd5e1; background: #ffffff; border-radius: 9px; font-size: 13px; font-weight: 600; color: #475569; cursor: pointer; transition: all 0.15s;">Batal</button>
            <button type="button" onclick="saveDataRiil()" style="padding: 9px 20px; border: none; background: #2563eb; color: #ffffff; border-radius: 9px; font-size: 13px; font-weight: 600; cursor: pointer; box-shadow: 0 2px 4px rgba(37,99,235,0.25); transition: all 0.15s;">Simpan Data Olahan</button>
        </div>
    </div>
</div>

<div class="toast-wrap" id="toast-wrap"></div>

<script>
const projectId = @json($project->id);
const projectTitle = @json($project->judul);
const projectProdi = @json($project->prodi);
const projectMeta = @json($meta ?? []);
const defaultUserName = @json(auth()->user()->name ?? 'Mahasiswa');
const isProposalMode = @json($isProposal);
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

let allowBab45 = !isProposalMode;
let activeView = 'bab1';
let isDirty = false;
let currentReviseMode = 'content';

const chapterLabels = {
    front: 'SAMPUL & HALAMAN DEPAN',
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

    const titleEl = document.getElementById('active-chapter-title');
    const subEl = document.getElementById('active-chapter-sub');
    const genBtn = document.getElementById('btn-generate-current');

    if (subEl) subEl.textContent = '';

    if (viewKey === 'all') {
        titleEl.textContent = 'NASKAH LENGKAP SKRIPSI (FULL DOCUMENT)';
        genBtn.style.display = 'none';
        document.getElementById('btn-save-edit').style.display = 'none';
    } else if (viewKey === 'front') {
        titleEl.textContent = 'SAMPUL & BAGIAN DEPAN';
        genBtn.style.display = 'none';
        document.getElementById('btn-save-edit').style.display = 'none';
    } else if (viewKey === 'revisi') {
        titleEl.textContent = 'REVISI';
        genBtn.style.display = 'none';
        document.getElementById('btn-save-edit').style.display = 'none';
    } else {
        titleEl.textContent = chapterLabels[viewKey] || 'WORKSPACE';
        genBtn.style.display = 'inline-flex';
        genBtn.textContent = viewKey === 'daftar_pustaka'
            ? 'Rapikan / Susun Daftar Pustaka'
            : ((chapterContents[viewKey] || '').trim() ? 'Susun Ulang Bab Ini' : 'Tulis Bab Ini');
    }

    if (window.innerWidth <= 900) {
        document.getElementById('studio-shell').classList.remove('mobile-sidebar-open');
    }

    // Sorot tombol Menu Olah Data pada Bab 3, Bab 4, dan Lihat Semua Bab
    const olahBtn = document.getElementById('btn-menu-olah-data');
    if (olahBtn) {
        if (viewKey === 'bab4' || viewKey === 'bab3' || viewKey === 'all') {
            olahBtn.style.background = '#eff6ff';
            olahBtn.style.color = '#1d4ed8';
            olahBtn.style.borderColor = '#93c5fd';
            olahBtn.style.fontWeight = '700';
            olahBtn.title = 'Prioritas Bab 3 & 4: Klik untuk mengisi atau melihat olah data & rumus penelitian';
        } else {
            olahBtn.style.background = '';
            olahBtn.style.color = '';
            olahBtn.style.borderColor = '#cbd5e1';
            olahBtn.style.fontWeight = '';
            olahBtn.title = 'Isi data olahan & rumus penelitian (SPSS / PLS / ML / Eksperimen) untuk Bab 3 & 4';
        }
    }

    // Auto-scroll ke paling atas halaman saat navigasi bab diklik
    const canvasScroll = document.querySelector('.canvas-scroll');
    if (canvasScroll) {
        canvasScroll.scrollTo({ top: 0, behavior: 'smooth' });
    }

    renderActiveSheet();
}

let loadingProgressInterval = null;

function renderLoadingSheet(title, subtitle) {
    if (loadingProgressInterval) {
        clearInterval(loadingProgressInterval);
        loadingProgressInterval = null;
    }

    const container = document.getElementById('sheets-container');
    container.innerHTML = `
        <div class="ai-loading-card" style="padding: 40px 28px; max-width: 600px; margin: 36px auto; background: #ffffff; border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.08), 0 8px 10px -6px rgba(15, 23, 42, 0.04); border: 1px solid #e2e8f0; text-align: center;">
            <div class="ai-spinner" style="width: 44px; height: 44px; border: 3px solid #e2e8f0; border-top-color: #2563eb; border-radius: 50%; margin: 0 auto 16px; animation: spin 0.85s linear infinite;"></div>
            <div class="ai-loading-title" style="font-size: 16.5px; font-weight: 700; color: #0f172a; margin-bottom: 6px;">${escapeHtml(title)}</div>
            <div class="ai-loading-sub" id="loading-stage-label" style="font-size: 13px; color: #64748b; margin-bottom: 20px;">${escapeHtml(subtitle || 'Mempersiapkan penyusunan naskah akademik...')}</div>

            <!-- Live Animated 0% to 100% Progress Bar -->
            <div style="background: #f1f5f9; border-radius: 999px; height: 10px; width: 100%; overflow: hidden; position: relative; margin-bottom: 12px; border: 1px solid #e2e8f0;">
                <div id="loading-progress-bar" style="background: linear-gradient(90deg, #2563eb 0%, #3b82f6 50%, #06b6d4 100%); height: 100%; width: 2%; border-radius: 999px; transition: width 0.3s ease;"></div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: #64748b; font-weight: 600;">
                <span id="loading-progress-step" style="display: flex; align-items: center; gap: 5px;">
                    <span style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: #2563eb;"></span>
                    <span>Menganalisis metodologi & rumusan masalah...</span>
                </span>
                <span id="loading-progress-pct" style="color: #2563eb; font-weight: 700; font-size: 13px;">0%</span>
            </div>

            <div class="shimmer-lines" style="margin-top: 22px; opacity: 0.65;">
                <div class="shimmer-bar" style="width: 92%"></div>
                <div class="shimmer-bar" style="width: 78%"></div>
                <div class="shimmer-bar" style="width: 85%"></div>
            </div>
        </div>
    `;

    let currentPct = 2;
    const bar = document.getElementById('loading-progress-bar');
    const pctLabel = document.getElementById('loading-progress-pct');
    const stepLabel = document.getElementById('loading-progress-step');

    const stages = [
        { pct: 15, text: 'Menganalisis struktur prodi & rumusan masalah...' },
        { pct: 35, text: 'Menyusun struktur sub-bab akademik standar S1...' },
        { pct: 55, text: 'Merumuskan landasan teori & komparasi ilmiah...' },
        { pct: 75, text: 'Mengintegrasikan data empiris & sitasi jurnal...' },
        { pct: 90, text: 'Memvalidasi koherensi kalimat & standar APA 7th...' },
        { pct: 96, text: 'Memformat naskah sesuai standar kertas A4...' }
    ];

    loadingProgressInterval = setInterval(() => {
        if (currentPct < 96) {
            const inc = Math.max(0.6, (96 - currentPct) * 0.05);
            currentPct = Math.min(96, currentPct + inc);
            if (bar) bar.style.width = `${Math.round(currentPct)}%`;
            if (pctLabel) pctLabel.textContent = `${Math.round(currentPct)}%`;

            for (let i = stages.length - 1; i >= 0; i--) {
                if (currentPct >= stages[i].pct) {
                    if (stepLabel && stepLabel.lastElementChild) {
                        stepLabel.lastElementChild.textContent = stages[i].text;
                    }
                    break;
                }
            }
        }
    }, 400);
}

function finishLoadingSheet() {
    if (loadingProgressInterval) {
        clearInterval(loadingProgressInterval);
        loadingProgressInterval = null;
    }
    const bar = document.getElementById('loading-progress-bar');
    const pctLabel = document.getElementById('loading-progress-pct');
    const stepLabel = document.getElementById('loading-progress-step');
    if (bar) bar.style.width = '100%';
    if (pctLabel) pctLabel.textContent = '100%';
    if (stepLabel && stepLabel.lastElementChild) stepLabel.lastElementChild.textContent = 'Selesai! Menampilkan naskah...';
}

function formatInlineMarkdown(text) {
    return escapeHtml(text)
        .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.+?)\*/g, '<em>$1</em>');
}

function isStandaloneFormula(line) {
    const t = line.trim();
    const len = t.length;
    if (len < 3 || len > 180) return false;
    if (/^(dengan|dimana|di mana|yaitu|karena|sehingga|adalah|untuk|pada)\b/i.test(t)) return false;
    if (t.endsWith('.') && t.split(/\s+/).length > 8) return false;
    const hasRelation = /[=≈≤≥]|:=/.test(t);
    if (!hasRelation && !/^\$\$.+\$\$$/.test(t)) return false;
    const hasMathTokens = /[\+\*\/\^×÷√∑∏∫αβγδεληθμσλ\[\]\|]|\b(count|sqrt|sum|log|exp|sin|cos|tan|lim|max|min|Akurasi|Accuracy|Recall|Precision|F1|R-Square|R2|t-hitung|F-hitung|Uji\s*t|Uji\s*F|P\([a-zA-Z0-9_\|\s,\+\-\*\/\^]+\))\b/i.test(t);
    return hasRelation && hasMathTokens;
}

function markdownToAcademicHtml(mdText, isDaftarPustaka = false) {
    if (!mdText || !mdText.trim()) return '';
    const lines = mdText.replace(/\r\n/g, '\n').split('\n');
    let html = '';
    let inUl = false;
    let inOl = false;
    let inTable = false;
    let inCode = false;
    let codeBuffer = '';
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

        // Handle Code Blocks / Flowcharts / Architecture Diagrams
        if (line.startsWith('```')) {
            flushLists();
            flushTable();
            if (inCode) {
                html += `<div class="academic-diagram-box" style="margin: 18px 0; padding: 14px 18px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; font-family: 'Courier New', monospace; font-size: 11pt; line-height: 1.5; white-space: pre-wrap; overflow-x: auto; color: #1e293b;">${escapeHtml(codeBuffer.trim())}</div>`;
                inCode = false;
                codeBuffer = '';
            } else {
                inCode = true;
                codeBuffer = '';
            }
            return;
        }
        if (inCode) {
            codeBuffer += raw + '\n';
            return;
        }

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

        let normLine = line;
        // Normalisasi sub-bab huruf tanpa titik ("A Latar Belakang" -> "A. Latar Belakang")
        const letterHeadingMatch = normLine.match(/^([A-Z])\s+([A-Z][a-zA-Z\s]+)$/);
        if (letterHeadingMatch) {
            normLine = letterHeadingMatch[1] + '. ' + letterHeadingMatch[2];
        }

        if (/^#\s+/.test(normLine)) {
            flushLists();
            html += `<h2>${formatInlineMarkdown(normLine.replace(/^#\s+/, ''))}</h2>`;
        } else if (/^##\s+/.test(normLine) || /^[A-Z]\.\s+[A-Z]/.test(normLine) || /^\d+\.\d+\.?\s+[A-Z]/.test(normLine)) {
            flushLists();
            const cleanHeading = normLine.replace(/^##\s+/, '');
            html += `<h3>${formatInlineMarkdown(cleanHeading)}</h3>`;
        } else if (/^###\s+/.test(normLine) || /^\d+\.\d+\.\d+\.?\s+[A-Z]/.test(normLine) || /^\d+\.\s+(Manfaat\s+[A-Za-z]+|Tujuan\s+[A-Za-z]+)/i.test(normLine)) {
            flushLists();
            const cleanSub = normLine.replace(/^###\s+/, '');
            html += `<h4>${formatInlineMarkdown(cleanSub)}</h4>`;
        } else if (/^[-*]\s+/.test(normLine)) {
            if (inOl) { html += '</ol>'; inOl = false; }
            if (!inUl) { html += '<ul>'; inUl = true; }
            html += `<li>${formatInlineMarkdown(normLine.replace(/^[-*]\s+/, ''))}</li>`;
        } else if (/^\d+\.\s+/.test(normLine)) {
            if (inUl) { html += '</ul>'; inUl = false; }
            if (!inOl) { html += '<ol>'; inOl = true; }
            html += `<li>${formatInlineMarkdown(normLine.replace(/^\d+\.\s+/, ''))}</li>`;
        } else if (/^(?:Tabel|Gambar|Bagan)\s+\d+\.\d+/i.test(normLine)) {
            flushLists();
            html += `<p class="academic-caption" style="text-indent:0; font-weight:700; text-align:left; margin:14px 0 6px; font-size:11pt; color:#0f172a;">${formatInlineMarkdown(normLine)}</p>`;
        } else if (isStandaloneFormula(normLine)) {
            flushLists();
            html += `<p class="academic-formula" style="text-align:center !important; text-indent:0 !important; font-family:'Times New Roman', serif; font-size:12.5pt; margin:16px 0; padding:4px 8px; font-style:normal; line-height:1.6; color:#0f172a;">${formatInlineMarkdown(normLine)}</p>`;
        } else {
            flushLists();
            html += `<p>${formatInlineMarkdown(normLine)}</p>`;
        }
    });

    flushLists();
    flushTable();
    return html;
}

function extractTablesForDocument() {
    const list = [];
    const chapters = [
        { key: 'bab1', num: 1, basePage: 1 },
        { key: 'bab2', num: 2, basePage: 6 },
        { key: 'bab3', num: 3, basePage: 18 },
        { key: 'bab4', num: 4, basePage: 32 },
        { key: 'bab5', num: 5, basePage: 46 }
    ];

    chapters.forEach(ch => {
        if (!allowBab45 && (ch.key === 'bab4' || ch.key === 'bab5')) return;
        const text = (chapterContents[ch.key] || '').trim();
        if (!text) return;

        const lines = text.split('\n');
        let tableIdx = 0;
        let inTbl = false;

        lines.forEach((line, lIdx) => {
            const trimmed = line.trim();
            const capMatch = trimmed.match(/^Tabel\s+(\d+\.\d+[\w\.\-]*)\s*(.*)$/i);
            if (capMatch) {
                tableIdx++;
                const title = capMatch[2].replace(/^[:\-\s]+/, '').trim() || `Tabel ${capMatch[1]}`;
                const estPage = ch.basePage + Math.floor(lIdx / 45);
                list.push({ num: capMatch[1], title: `Tabel ${capMatch[1]} ${title}`, page: estPage });
            } else if (trimmed.startsWith('|') && trimmed.endsWith('|')) {
                if (!inTbl) {
                    inTbl = true;
                    const prevLine = (lines[lIdx - 1] || '').trim();
                    if (!/^Tabel\s+\d+\.\d+/i.test(prevLine)) {
                        tableIdx++;
                        const defaultTitles = {
                            1: ['Karakteristik Data Penelitian', 'Distribusi Responden'],
                            2: ['Matriks Komparasi Penelitian Terdahulu', 'Sintesis Kajian Teori'],
                            3: ['Operasionalisasi Variabel & Parameter', 'Matriks Kebingungan (Confusion Matrix)', 'Spesifikasi Pengujian'],
                            4: ['Statistik Dataset Penelitian', 'Evaluasi Metrik Kinerja (Confusion Matrix)', 'Hasil Analisis Komparasi'],
                            5: ['Rekapitulasi Capaian Tujuan Penelitian']
                        };
                        const fallbackTitle = (defaultTitles[ch.num] && defaultTitles[ch.num][tableIdx - 1]) || `Tabel Analisis Data ${ch.num}.${tableIdx}`;
                        const estPage = ch.basePage + Math.floor(lIdx / 45);
                        list.push({ num: `${ch.num}.${tableIdx}`, title: `Tabel ${ch.num}.${tableIdx} ${fallbackTitle}`, page: estPage });
                    }
                }
            } else {
                inTbl = false;
            }
        });
    });

    return list;
}

function extractFiguresForDocument() {
    const list = [];
    const chapters = [
        { key: 'bab1', num: 1, basePage: 1 },
        { key: 'bab2', num: 2, basePage: 6 },
        { key: 'bab3', num: 3, basePage: 18 },
        { key: 'bab4', num: 4, basePage: 32 },
        { key: 'bab5', num: 5, basePage: 46 }
    ];

    chapters.forEach(ch => {
        if (!allowBab45 && (ch.key === 'bab4' || ch.key === 'bab5')) return;
        const text = (chapterContents[ch.key] || '').trim();
        if (!text) return;

        // Gambar 2.1 Kerangka Berpikir jika ada di Bab 2
        if (ch.key === 'bab2' && (text.includes('Kerangka') || text.includes('kerangka') || text.includes('-->') || text.includes('```'))) {
            list.push({ num: '2.1', title: 'Gambar 2.1 Bagan Kerangka Pemikiran Penelitian', page: ch.basePage + 4 });
        }

        // Gambar 3.1 Flowchart jika ada di Bab 3
        if (ch.key === 'bab3' && (text.includes('Flowchart') || text.includes('flowchart') || text.includes('Alur') || text.includes('-->') || text.includes('```'))) {
            list.push({ num: '3.1', title: 'Gambar 3.1 Diagram Alur (Flowchart) Prosedur Penelitian', page: ch.basePage + 3 });
        }

        // Deteksi gambar eksplisit lainnya
        const lines = text.split('\n');
        lines.forEach((line, lIdx) => {
            const m = line.trim().match(/^Gambar\s+(\d+\.\d+[\w\.\-]*)\s*(.*)$/i);
            if (m) {
                const num = m[1];
                if (num !== '2.1' && num !== '3.1') {
                    const title = m[2].replace(/^[:\-\s]+/, '').trim() || `Gambar ${num}`;
                    list.push({ num: num, title: `Gambar ${num} ${title}`, page: ch.basePage + Math.floor(lIdx / 45) });
                }
            }
        });
    });

    return list;
}

function buildCoverAndFrontMatterHtml() {
    const nama = projectMeta.nama || defaultUserName;
    const nim = projectMeta.nim || '';
    const kampus = projectMeta.kampus || 'UNIVERSITAS';
    const fakultas = projectMeta.fakultas || '';
    const logo = projectMeta.logo_base64 || '';
    const tahun = new Date().getFullYear();
    const docKind = allowBab45 ? 'SKRIPSI' : 'PROPOSAL SKRIPSI';

    const chaptersDef = [
        { key: 'bab1', num: 'I', title: 'BAB I PENDAHULUAN' },
        { key: 'bab2', num: 'II', title: 'BAB II TINJAUAN PUSTAKA' },
        { key: 'bab3', num: 'III', title: 'BAB III METODOLOGI PENELITIAN' },
        { key: 'bab4', num: 'IV', title: 'BAB IV HASIL PENELITIAN DAN PEMBAHASAN' },
        { key: 'bab5', num: 'V', title: 'BAB V PENUTUP' }
    ];

    let currentPage = 1;
    const tocRows = [];

    // Bagian Awal Standar Akademik Indonesia (Front Matter)
    tocRows.push({ title: 'HALAMAN JUDUL', page: '', isMain: true, isSub: false });
    tocRows.push({ title: 'LEMBAR PENGESAHAN', page: 'i', isMain: true, isSub: false });
    tocRows.push({ title: 'SURAT PERNYATAAN KEASLIAN', page: 'ii', isMain: true, isSub: false });
    tocRows.push({ title: 'ABSTRAK (BAHASA INDONESIA)', page: 'iii', isMain: true, isSub: false });
    tocRows.push({ title: 'ABSTRACT (ENGLISH)', page: 'iv', isMain: true, isSub: false });
    tocRows.push({ title: 'KATA PENGANTAR', page: 'v', isMain: true, isSub: false });
    tocRows.push({ title: 'DAFTAR ISI', page: 'vi', isMain: true, isSub: false });
    tocRows.push({ title: 'DAFTAR TABEL', page: 'vii', isMain: true, isSub: false });
    tocRows.push({ title: 'DAFTAR GAMBAR', page: 'viii', isMain: true, isSub: false });
    tocRows.push({ title: 'DAFTAR LAMPIRAN', page: 'ix', isMain: true, isSub: false });

    chaptersDef.forEach((ch) => {
        if (!allowBab45 && (ch.key === 'bab4' || ch.key === 'bab5')) return;
        const text = (chapterContents[ch.key] || '').trim();
        if (!text) return;

        const chPage = currentPage;
        tocRows.push({ title: ch.title, page: String(chPage), isMain: true, isSub: false });

        const lines = text.split('\n');
        let subCount = 0;
        lines.forEach((raw) => {
            const line = raw.trim();
            const m = line.match(/^(?:##\s+)?(\d+\.\d+\.?\s+[^\n]+)/) ||
                      line.match(/^(?:##\s+)?([A-Z]\.\s+[A-Z][^\n]+)/);
            if (m && m[1]) {
                const clean = m[1].replace(/^##\s+/, '').trim();
                if (clean.length >= 4 && clean.length <= 80) {
                    subCount++;
                    const subPage = chPage + Math.floor((subCount - 1) / 2);
                    tocRows.push({ title: clean, page: String(subPage), isMain: false, isSub: true });
                }
            }
        });

        if (subCount === 0) {
            defaultSubchapters.forEach((item, idx) => {
                if (item.chapter === ch.key && item.code) {
                    const subPage = chPage + Math.floor(idx / 2);
                    tocRows.push({ title: item.label, page: String(subPage), isMain: false, isSub: true });
                    subCount++;
                }
            });
        }

        const estimatedPages = Math.max(2, Math.ceil(text.length / 2200), Math.ceil(subCount / 2));
        currentPage += estimatedPages;
    });

    const dpContent = (chapterContents.daftar_pustaka || '').trim();
    if (dpContent) {
        tocRows.push({ title: 'DAFTAR PUSTAKA', page: String(currentPage), isMain: true, isSub: false });
    }

    const tocHtml = tocRows.map((r) => {
        const cls = r.isSub ? 'toc-row sub' : 'toc-row main';
        const pageSpan = r.page ? `<span class="toc-page">${escapeHtml(r.page)}</span>` : '';
        return `<div class="${cls}"><span>${escapeHtml(r.title)}</span><span class="toc-dots"></span>${pageSpan}</div>`;
    }).join('');

    const daftarTabelList = extractTablesForDocument();
    const daftarTabelHtml = daftarTabelList.length > 0 
        ? daftarTabelList.map(t => `<div class="toc-row main"><span>${escapeHtml(t.title)}</span><span class="toc-dots"></span><span class="toc-page">${t.page}</span></div>`).join('')
        : '<div style="text-align:center; color:#64748b; font-style:italic; padding:20px;">(Tidak ada tabel dalam naskah penelitian)</div>';

    const daftarGambarList = extractFiguresForDocument();
    const daftarGambarHtml = daftarGambarList.length > 0
        ? daftarGambarList.map(g => `<div class="toc-row main"><span>${escapeHtml(g.title)}</span><span class="toc-dots"></span><span class="toc-page">${g.page}</span></div>`).join('')
        : '<div style="text-align:center; color:#64748b; font-style:italic; padding:20px;">(Tidak ada gambar dalam naskah penelitian)</div>';

    const DEFAULT_ACADEMIC_SEAL_SVG = `<svg width="105" height="105" viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg" style="display:block; margin:0 auto;">
      <circle cx="60" cy="60" r="56" stroke="#1e3a8a" stroke-width="3" fill="#f8fafc"/>
      <circle cx="60" cy="60" r="50" stroke="#2563eb" stroke-width="1.5" stroke-dasharray="3 3"/>
      <circle cx="60" cy="60" r="44" stroke="#1e3a8a" stroke-width="1.5" fill="#ffffff"/>
      <polygon points="60,25 70,45 92,48 76,64 80,86 60,75 40,86 44,64 28,48 50,45" fill="#f59e0b" stroke="#d97706" stroke-width="1.2"/>
      <path d="M42 66 L60 55 L78 66 L60 77 Z" fill="#1e3a8a"/>
      <path d="M48 69.5 V80 C48 83.5 53.5 86 60 86 C66.5 86 72 83.5 72 80 V69.5" fill="none" stroke="#1e3a8a" stroke-width="2"/>
      <path d="M78 66 V78" stroke="#d97706" stroke-width="2"/>
      <circle cx="78" cy="80" r="2" fill="#d97706"/>
    </svg>`;

    const hasValidLogo = logo && logo.length > 50 && (logo.startsWith('data:image/') || logo.startsWith('http'));
    const logoMarkup = hasValidLogo
        ? `<img src="${logo}" alt="Logo Kampus" class="cover-logo-img" onerror="this.outerHTML = DEFAULT_ACADEMIC_SEAL_SVG;">`
        : DEFAULT_ACADEMIC_SEAL_SVG;

    return `
        <!-- 1. HALAMAN JUDUL (COVER) -->
        <div class="a4-sheet cover-sheet" contenteditable="true" spellcheck="false">
            <div class="cover-top-block">
                <div class="cover-kind">${docKind}</div>
                <div class="cover-title">${escapeHtml(projectTitle)}</div>
                <div class="cover-sub">Diajukan sebagai Salah Satu Syarat untuk Memperoleh Gelar Sarjana pada Program Studi ${escapeHtml(projectProdi)}</div>
            </div>
            <div class="cover-logo-box" onclick="document.getElementById('sidebar-logo-upload').click()" title="Klik untuk ganti logo kampus" style="cursor: pointer;">
                ${logoMarkup}
            </div>
            <div class="cover-bottom-block">
                <div class="cover-author-block">
                    <div>OLEH:</div>
                    <div><u><strong>${escapeHtml(nama)}</strong></u></div>
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

        <!-- 2. LEMBAR PENGESAHAN -->
        <div class="a4-sheet front-body" contenteditable="true" spellcheck="false" style="display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div class="front-heading">LEMBAR PENGESAHAN</div>
                <div style="text-align: center; margin: 18px 0; font-weight: 700; font-size: 13pt; line-height: 1.4; text-transform: uppercase;">
                    ${escapeHtml(projectTitle)}
                </div>
                <div style="text-align: center; font-size: 11pt; margin-bottom: 22px;">
                    Disusun Oleh: <u><strong>${escapeHtml(nama)}</strong></u> ${nim ? `(NIM: ${escapeHtml(nim)})` : ''}
                </div>
                <p style="text-align: justify; line-height: 1.6; font-size: 11.5pt; text-indent: 1.27cm;">
                    Telah diuji dan dipertahankan di hadapan Tim Dewan Penguji Ujian ${docKind} pada Program Studi ${escapeHtml(projectProdi)}, ${fakultas ? escapeHtml(fakultas) + ', ' : ''}${escapeHtml(kampus)}, dan dinyatakan telah memenuhi seluruh persyaratan akademik kelulusan.
                </p>

                <div style="margin-top: 36px; display: grid; grid-template-columns: 1fr 1fr; gap: 24px; text-align: center; font-size: 11pt;">
                    <div>
                        <div>Dosen Pembimbing I,</div>
                        <div style="height: 65px;"></div>
                        <div>( __________________________ )</div>
                        <div style="font-size: 10pt; color: #64748b; margin-top: 2px;">NIP / NIDN.</div>
                    </div>
                    <div>
                        <div>Dosen Pembimbing II,</div>
                        <div style="height: 65px;"></div>
                        <div>( __________________________ )</div>
                        <div style="font-size: 10pt; color: #64748b; margin-top: 2px;">NIP / NIDN.</div>
                    </div>
                </div>

                <div style="margin-top: 36px; text-align: center; font-size: 11pt;">
                    <div>Mengetahui,</div>
                    <div>Ketua Program Studi ${escapeHtml(projectProdi)}</div>
                    <div style="height: 65px;"></div>
                    <div>( __________________________ )</div>
                    <div style="font-size: 10pt; color: #64748b; margin-top: 2px;">NIP / NIDN.</div>
                </div>
            </div>
            <div class="sheet-footer">i</div>
        </div>

        <!-- 3. SURAT PERNYATAAN KEASLIAN -->
        <div class="a4-sheet front-body" contenteditable="true" spellcheck="false" style="display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div class="front-heading">SURAT PERNYATAAN KEASLIAN</div>
                <p style="text-indent: 0; margin-top: 24px; font-size: 11.5pt;">Saya yang bertanda tangan di bawah ini:</p>
                <div style="margin: 14px 0 20px 24px; font-size: 11.5pt; line-height: 1.8;">
                    <div>Nama Mahasiswa &nbsp;: <strong>${escapeHtml(nama)}</strong></div>
                    <div>NIM / NPM &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: ${escapeHtml(nim || '-')}</div>
                    <div>Program Studi &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: ${escapeHtml(projectProdi)}</div>
                    <div>Fakultas &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: ${escapeHtml(fakultas || '-')}</div>
                </div>
                <p style="text-align: justify; line-height: 1.6; font-size: 11.5pt; text-indent: 1.27cm;">
                    Menyatakan dengan sesungguhnya bahwa ${docKind} berjudul <strong>"${escapeHtml(projectTitle)}"</strong> ini adalah murni hasil karya penelitian, pemikiran, dan penulisan mandiri saya sendiri. Karya ini belum pernah diajukan untuk memperoleh gelar akademik di perguruan tinggi mana pun, dan bebas dari unsur plagiarisme atau fabrikasi data.
                </p>
                <p style="text-align: justify; line-height: 1.6; font-size: 11.5pt; text-indent: 1.27cm; margin-top: 12px;">
                    Seluruh ide, kutipan, dan data yang bersumber dari karya pihak lain telah disitasi dan dicantumkan secara bertanggung jawab dalam daftar pustaka. Apabila di kemudian hari ditemukan bukti plagiarisme, saya bersedia menerima sanksi pencabutan gelar akademik dan sanksi hukum sesuai ketentuan yang berlaku.
                </p>

                <div style="margin-top: 40px; float: right; width: 220px; text-align: center; font-size: 11pt;">
                    <div>${escapeHtml(kampus)}, ${tahun}</div>
                    <div style="margin-top: 4px;">Yang membuat pernyataan,</div>
                    <div style="margin-top: 14px; font-size: 9pt; color: #94a3b8; border: 1px dashed #cbd5e1; padding: 6px; border-radius: 4px; display: inline-block;">Materai Rp 10.000</div>
                    <div style="height: 48px;"></div>
                    <div><u><strong>${escapeHtml(nama)}</strong></u></div>
                    ${nim ? `<div>NIM. ${escapeHtml(nim)}</div>` : ''}
                </div>
                <div style="clear: both;"></div>
            </div>
            <div class="sheet-footer">ii</div>
        </div>

        <!-- 4. ABSTRAK (BAHASA INDONESIA) -->
        <div class="a4-sheet front-body" contenteditable="true" spellcheck="false" style="display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div class="front-heading">ABSTRAK</div>
                <div style="text-align: center; margin: 12px 0 16px; font-weight: 700; font-size: 11.5pt; line-height: 1.35; text-transform: uppercase;">
                    ${escapeHtml(projectTitle)}
                </div>
                <div style="text-align: center; font-size: 10.5pt; color: #475569; margin-bottom: 14px;">
                    Oleh: ${escapeHtml(nama)} (NIM: ${escapeHtml(nim || '-')})<br>
                    Program Studi ${escapeHtml(projectProdi)}, ${escapeHtml(kampus)}
                </div>
                <p style="text-align: justify; line-height: 1.5; font-size: 11pt; text-indent: 1.27cm;">
                    Penelitian ini bertujuan untuk menginvestigasi, menganalisis, dan memecahkan permasalahan pada topik <em>"${escapeHtml(projectTitle)}"</em>. Menggunakan metode penelitian ${escapeHtml(projectMeta.metode || 'kuantitatif dan komputasi')} yang terstruktur, penelitian ini mengumpulkan dan memproses data empiris lapangan secara sistematis. Hasil pengujian menunjukkan bahwa implementasi metode mampu menjawab rumusan masalah secara komprehensif, memberikan akurasi atau tolok ukur terukur, serta berkontribusi nyata bagi pengembangan keilmuan dan kebutuhan praktis pada bidang ${escapeHtml(projectProdi)}.
                </p>
                <div style="font-size: 11pt; margin-top: 14px;">
                    <strong>Kata Kunci:</strong> ${escapeHtml(projectProdi)}, ${escapeHtml(projectMeta.metode || 'Analisis Empiris')}, Evaluasi Kinerja, Standar Skripsi S1.
                </div>
            </div>
            <div class="sheet-footer">iii</div>
        </div>

        <!-- 5. ABSTRACT (ENGLISH) -->
        <div class="a4-sheet front-body" contenteditable="true" spellcheck="false" style="display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div class="front-heading">ABSTRACT</div>
                <div style="text-align: center; margin: 12px 0 16px; font-weight: 700; font-size: 11.5pt; line-height: 1.35; font-style: italic; text-transform: uppercase;">
                    ${escapeHtml(projectTitle)}
                </div>
                <div style="text-align: center; font-size: 10.5pt; color: #475569; margin-bottom: 14px; font-style: italic;">
                    By: ${escapeHtml(nama)} (Student ID: ${escapeHtml(nim || '-')})<br>
                    Department of ${escapeHtml(projectProdi)}, ${escapeHtml(kampus)}
                </div>
                <p style="text-align: justify; line-height: 1.5; font-size: 11pt; text-indent: 1.27cm; font-style: italic;">
                    This research aims to investigate, analyze, and resolve the academic and empirical problems associated with <em>"${escapeHtml(projectTitle)}"</em>. Utilizing a structured scientific methodology in ${escapeHtml(projectProdi)}, the research systematically collects and analyzes field data. The experimental results demonstrate that the proposed method effectively addresses the research questions, yields verifiable benchmark performance, and delivers valuable empirical contributions to both theoretical frameworks and practical implementations.
                </p>
                <div style="font-size: 11pt; margin-top: 14px; font-style: italic;">
                    <strong>Keywords:</strong> ${escapeHtml(projectProdi)}, Empirical Research, Performance Evaluation, Undergraduate Thesis.
                </div>
            </div>
            <div class="sheet-footer">iv</div>
        </div>

        <!-- 6. KATA PENGANTAR -->
        <div class="a4-sheet front-body" contenteditable="true" spellcheck="false" style="display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div class="front-heading">KATA PENGANTAR</div>
                <p style="text-align: justify; line-height: 1.6; font-size: 11.5pt; text-indent: 1.27cm; margin-top: 18px;">
                    Puji dan syukur penulis panjatkan ke hadirat Tuhan Yang Maha Esa atas limpahan rahmat, taufik, dan hidayah-Nya, sehingga penulis dapat menyelesaikan naskah ${docKind} yang berjudul <strong>"${escapeHtml(projectTitle)}"</strong> dengan baik dan lancar.
                </p>
                <p style="text-align: justify; line-height: 1.6; font-size: 11.5pt; text-indent: 1.27cm;">
                    Penyusunan naskah ini diajukan sebagai salah satu syarat akademik guna menyelesaikan studi Program Sarjana (S1) pada Program Studi ${escapeHtml(projectProdi)}, ${fakultas ? escapeHtml(fakultas) + ', ' : ''}${escapeHtml(kampus)}. Penulis menyadari sepenuhnya bahwa penyusunan naskah ini tidak lepas dari bimbingan, arahan, dan dukungan moril dari berbagai pihak. Oleh karena itu, pada kesempatan ini penulis menyampaikan rasa hormat dan terima kasih yang sebesar-besarnya kepada:
                </p>
                <ol style="margin: 12px 0 16px 28px; line-height: 1.7; font-size: 11pt;">
                    <li>Rektor ${escapeHtml(kampus)} beserta jajaran pimpinan universitas;</li>
                    <li>Dekan ${fakultas ? escapeHtml(fakultas) : 'Fakultas'} yang telah memberikan fasilitas dan kesempatan belajar;</li>
                    <li>Ketua Program Studi ${escapeHtml(projectProdi)} yang senantiasa membimbing dan mengarahkan jalannya perkuliahan;</li>
                    <li>Dosen Pembimbing yang telah meluangkan waktu dan pemikiran dengan penuh kesabaran dalam membimbing penulisan skripsi ini;</li>
                    <li>Seluruh Dosen dan Staf Pengajar Program Studi ${escapeHtml(projectProdi)};</li>
                    <li>Kedua orang tua dan keluarga tercinta atas doa yang tiada henti, kasih sayang, dan pengorbanan yang tulus.</li>
                </ol>
                <div style="margin-top: 30px; float: right; width: 200px; text-align: center; font-size: 11pt;">
                    <div>${escapeHtml(kampus)}, ${tahun}</div>
                    <div style="height: 44px;"></div>
                    <div><strong>Penulis</strong></div>
                </div>
                <div style="clear: both;"></div>
            </div>
            <div class="sheet-footer">v</div>
        </div>

        <!-- 7. DAFTAR ISI -->
        <div class="a4-sheet front-body" contenteditable="true" spellcheck="false">
            <div class="front-heading">DAFTAR ISI</div>
            <div class="toc-list">${tocHtml}</div>
            <div class="sheet-footer">vi</div>
        </div>

        <!-- 8. DAFTAR TABEL -->
        <div class="a4-sheet front-body" contenteditable="true" spellcheck="false">
            <div class="front-heading">DAFTAR TABEL</div>
            <div class="toc-list" style="margin-top: 20px;">
                ${daftarTabelHtml}
            </div>
            <div class="sheet-footer">vii</div>
        </div>

        <!-- 9. DAFTAR GAMBAR -->
        <div class="a4-sheet front-body" contenteditable="true" spellcheck="false">
            <div class="front-heading">DAFTAR GAMBAR</div>
            <div class="toc-list" style="margin-top: 20px;">
                ${daftarGambarHtml}
            </div>
            <div class="sheet-footer">viii</div>
        </div>

        <!-- 10. DAFTAR LAMPIRAN -->
        <div class="a4-sheet front-body" contenteditable="true" spellcheck="false">
            <div class="front-heading">DAFTAR LAMPIRAN</div>
            <div class="toc-list" style="margin-top: 20px;">
                <div class="toc-row main"><span>Lampiran 1 Surat Keputusan &amp; Izin Penelitian Kampus</span><span class="toc-dots"></span><span class="toc-page">62</span></div>
                <div class="toc-row main"><span>Lampiran 2 Sampel Data Mentah &amp; Instrumen Penelitian</span><span class="toc-dots"></span><span class="toc-page">64</span></div>
                <div class="toc-row main"><span>Lampiran 3 Tabel Lengkap Hasil Uji Statistik &amp; Parameter</span><span class="toc-dots"></span><span class="toc-page">68</span></div>
                <div class="toc-row main"><span>Lampiran 4 Dokumentasi Pelaksanaan &amp; Log Bimbingan Skripsi</span><span class="toc-dots"></span><span class="toc-page">72</span></div>
            </div>
            <div class="sheet-footer">ix</div>
        </div>
    `;
}

function buildSubchapterOptions() {
    const allowedChapters = allowBab45
        ? ['bab1', 'bab2', 'bab3', 'bab4', 'bab5']
        : ['bab1', 'bab2', 'bab3'];

    const options = [];
    const seen = new Set();

    // Hanya tampilkan bab yang sudah memiliki isi naskah
    const activeChapters = allowedChapters.filter((ch) => Boolean((chapterContents[ch] || '').trim()));
    const targetChapters = activeChapters.length > 0 ? activeChapters : ['bab1'];

    targetChapters.forEach((ch) => {
        const text = (chapterContents[ch] || '').trim();
        const foundSubs = [];

        if (text) {
            const lines = text.split('\n');
            lines.forEach((raw) => {
                const line = raw.trim();
                const m = line.match(/^(?:##\s+)?(\d+\.\d+\.?\s+[^\n]+)/) ||
                          line.match(/^(?:##\s+)?([A-Z]\.\s+[A-Z][^\n]+)/);
                if (m && m[1]) {
                    const clean = m[1].replace(/^##\s+/, '').trim();
                    if (clean.length >= 4 && clean.length <= 80 && !seen.has(clean.toLowerCase())) {
                        seen.add(clean.toLowerCase());
                        foundSubs.push(clean);
                    }
                }
            });
        }

        if (foundSubs.length > 0) {
            foundSubs.forEach((lbl) => {
                options.push({ value: `${ch}|${lbl}`, label: lbl });
            });
        } else {
            defaultSubchapters.forEach((item) => {
                if (item.chapter === ch && item.code) {
                    if (!seen.has(item.label.toLowerCase())) {
                        seen.add(item.label.toLowerCase());
                        options.push({ value: `${ch}|${item.label}`, label: item.label });
                    }
                }
            });
        }

        const chLabel = chapterLabels[ch] || ch.toUpperCase();
        options.push({ value: `${ch}|Semua ${chLabel} (Utuh)`, label: `Semua ${chLabel} (Utuh)` });
    });

    return options;
}

function extractSubchapterPreview(chapterKey, subLabel) {
    const content = (chapterContents[chapterKey] || '').trim();
    if (!content) {
        return 'Bab ini belum disusun. Kamu tetap bisa memproses atau membuat ulang isi sub-bab langsung dari instruksi di atas.';
    }
    if (subLabel.toLowerCase().includes('semua bab') || subLabel.toLowerCase().includes('utuh')) {
        return content.slice(0, 900) + (content.length > 900 ? '\n...' : '');
    }

    const codeMatch = subLabel.match(/^(\d+\.\d+|[A-Z]\.)/);
    const code = codeMatch ? codeMatch[1].replace('.', '\\.') : '';
    const titlePart = subLabel.replace(/^(\d+\.\d+|[A-Z]\.)\s*/, '').trim();
    const escapedTitle = titlePart.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

    let regex = null;
    if (code) {
        regex = new RegExp(`(?:##\\s*)?${code}[^\\n]*\\n([\\s\\S]*?)(?=(?:\\n(?:##\\s*)?(?:\\d+\\.\\d+|[A-Z]\\.)\\s)|$)`, 'i');
    } else if (escapedTitle) {
        regex = new RegExp(`(?:##\\s*)?[^\\n]*${escapedTitle}[^\\n]*\\n([\\s\\S]*?)(?=(?:\\n(?:##\\s*)?(?:\\d+\\.\\d+|[A-Z]\\.)\\s)|$)`, 'i');
    }

    if (regex) {
        const m = content.match(regex);
        if (m && m[0]) {
            const slice = m[0].trim();
            return slice.slice(0, 900) + (slice.length > 900 ? '\n...' : '');
        }
    }

    return content.slice(0, 600) + (content.length > 600 ? '\n...' : '');
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

let researchDataRiil = projectMeta.data_riil || {};
try {
    const cached = localStorage.getItem(`skripsi_data_riil_${projectId}`);
    if (cached) researchDataRiil = Object.assign({}, researchDataRiil, JSON.parse(cached));
} catch(e) {}

function applyOlahDataPreset(type) {
    if (type === 'spss') {
        document.getElementById('riil-metode').value = 'IBM SPSS Statistics 26 (Analisis Regresi Linier Berganda)';
        document.getElementById('riil-sampel').value = '120 Responden Mahasiswa (Kuesioner Skala Likert 1-5)';
        document.getElementById('riil-rumus').value = 'Y = a + b1X1 + b2X2 + e, Uji t = (r√n-2)/√(1-r^2), R-Square, Uji F';
        document.getElementById('riil-lokasi').value = 'Universitas / Lingkup Objek Penelitian Mahasiswa';
        document.getElementById('riil-metrik').value = 'R-Square = 0.684 (68.4%), F-hitung = 45.21 (Sig 0.000 < 0.05). Koefisien B: X1 = 0.382 (t-hitung 3.42, Sig 0.001); X2 = 0.451 (t-hitung 4.15, Sig 0.000). Uji Normalitas K-S Asymp. Sig = 0.200 > 0.05, Multikolinearitas Tolerance > 0.10 & VIF < 10.';
        document.getElementById('riil-evaluasi').value = 'Uji Validitas Item (r-hitung > r-tabel), Uji Reliabilitas Cronbach Alpha > 0.70, Uji Asumsi Klasik, Uji t parsial, Uji F simultan, Koefisien Determinasi R2';
    } else if (type === 'pls') {
        document.getElementById('riil-metode').value = 'SmartPLS 4 (Structural Equation Modeling - Partial Least Squares)';
        document.getElementById('riil-sampel').value = '150 Responden Karyawan / Konsumen';
        document.getElementById('riil-rumus').value = 'Outer Model (Loading Factor > 0.70, AVE > 0.50, Composite Reliability > 0.70), Inner Model (R2, f2, Q2, Path Coefficients t-statistic > 1.96)';
        document.getElementById('riil-lokasi').value = 'Objek Perusahaan / Organisasi';
        document.getElementById('riil-metrik').value = 'AVE semua konstruk > 0.55, Cronbach Alpha > 0.80, Composite Reliability > 0.85. Path Coefficient: X1 -> Y = 0.345 (p-value 0.002), X2 -> Y = 0.420 (p-value 0.000). R2 = 0.612.';
        document.getElementById('riil-evaluasi').value = 'Evaluasi Measurement Model (Convergent & Discriminant Validity HTMT) dan Structural Model (Bootstrapping 5000 subsamples)';
    } else if (type === 'ml') {
        document.getElementById('riil-metode').value = 'Python Scikit-Learn (Algoritma Naive Bayes / SVM / Random Forest)';
        document.getElementById('riil-sampel').value = '1.500 Data Teks Ulasan / Komentar Media Sosial (Rasio Split 80:20)';
        document.getElementById('riil-rumus').value = 'Teorema Bayes: P(C|X) = P(X|C)P(C)/P(X); Akurasi = (TP+TN)/(TP+TN+FP+FN); Presisi = TP/(TP+FP); Recall = TP/(TP+FN); F1-Score = 2*(Presisi*Recall)/(Presisi+Recall)';
        document.getElementById('riil-lokasi').value = 'Platform Twitter (X) / TikTok / Google Play Review';
        document.getElementById('riil-metrik').value = 'Akurasi Keseluruhan = 88.50%, Macro Average Precision = 87.20%, Recall = 88.10%, F1-Score = 87.65%. Confusion Matrix: TP = 120, TN = 135, FP = 18, FN = 17.';
        document.getElementById('riil-evaluasi').value = 'K-Fold Cross Validation (10-Fold), Confusion Matrix (Akurasi, Precision, Recall, F1-Score)';
    } else if (type === 'eksperimen') {
        document.getElementById('riil-metode').value = 'Quasi-Experimental Design (Nonequivalent Pretest-Posttest Control Group)';
        document.getElementById('riil-sampel').value = '64 Siswa (Kelas Eksperimen n=32, Kelas Kontrol n=32)';
        document.getElementById('riil-rumus').value = 'Skor N-Gain = (Skor Posttest - Skor Pretest) / (Skor Maksimal - Skor Pretest), Independent Samples t-test';
        document.getElementById('riil-lokasi').value = 'Sekolah / Lembaga Pendidikan';
        document.getElementById('riil-metrik').value = 'Kelas Eksperimen: Rata-rata Pretest 54.20 -> Posttest 85.50 (N-Gain 0.683 kategori Sedang-Tinggi / Efektif). Kelas Kontrol: Pretest 53.80 -> Posttest 68.20 (N-Gain 0.311). Nilai t-hitung = 5.241 (Sig 0.000 < 0.05).';
        document.getElementById('riil-evaluasi').value = 'Uji Normalitas Shapiro-Wilk, Uji Homogenitas Levene, Independent Sample t-test, Kategori Tafsiran N-Gain Hake';
    }
}

function openDataRiilModal() {
    const m = document.getElementById('modal-data-riil');
    if (!m) return;
    document.getElementById('riil-metode').value = researchDataRiil.metode || '';
    document.getElementById('riil-sampel').value = researchDataRiil.sampel || '';
    document.getElementById('riil-rumus').value = researchDataRiil.rumus || '';
    document.getElementById('riil-lokasi').value = researchDataRiil.lokasi || '';
    document.getElementById('riil-metrik').value = researchDataRiil.metrik || '';
    document.getElementById('riil-evaluasi').value = researchDataRiil.evaluasi || '';
    m.style.display = 'flex';
}

function closeDataRiilModal() {
    const m = document.getElementById('modal-data-riil');
    if (m) m.style.display = 'none';
}

function saveDataRiil() {
    researchDataRiil = {
        metode: (document.getElementById('riil-metode').value || '').trim(),
        sampel: (document.getElementById('riil-sampel').value || '').trim(),
        rumus: (document.getElementById('riil-rumus')?.value || '').trim(),
        lokasi: (document.getElementById('riil-lokasi').value || '').trim(),
        metrik: (document.getElementById('riil-metrik').value || '').trim(),
        evaluasi: (document.getElementById('riil-evaluasi').value || '').trim()
    };
    try {
        localStorage.setItem(`skripsi_data_riil_${projectId}`, JSON.stringify(researchDataRiil));
    } catch(e) {}
    closeDataRiilModal();
    showToast('Data olahan & rumus penelitian berhasil disimpan! Siap diterapkan ke Bab 3 & 4.', 'success');
}

function renderAllSheets() {
    const container = document.getElementById('sheets-container');
    const frontHtml = buildCoverAndFrontMatterHtml();

    const chapterHeaders = {
        'bab1': { num: 'BAB I', title: 'PENDAHULUAN', page: '1' },
        'bab2': { num: 'BAB II', title: 'TINJAUAN PUSTAKA', page: '8' },
        'bab3': { num: 'BAB III', title: 'METODOLOGI PENELITIAN', page: '16' },
        'bab4': { num: 'BAB IV', title: 'HASIL PENELITIAN DAN PEMBAHASAN', page: '24' },
        'bab5': { num: 'BAB V', title: 'PENUTUP', page: '36' },
        'daftar_pustaka': { num: '', title: 'DAFTAR PUSTAKA', page: '42' }
    };

    const keys = allowBab45
        ? ['bab1', 'bab2', 'bab3', 'bab4', 'bab5', 'daftar_pustaka']
        : ['bab1', 'bab2', 'bab3', 'daftar_pustaka'];

    let sheetsHtml = frontHtml;

    keys.forEach((key) => {
        const raw = (chapterContents[key] || '').trim();
        const curH = chapterHeaders[key];
        if (!raw) {
            sheetsHtml += `
                <div class="a4-sheet empty-sheet" style="margin-top: 28px; min-height: 240px; display: flex; flex-direction: column; justify-content: center; align-items: center; border: 2px dashed #cbd5e1; background: #fafafa;">
                    <h3 style="font-size: 15px; font-weight: 700; color: #475569; margin-bottom: 6px;">${escapeHtml(chapterLabels[key] || curH.title)}</h3>
                    <p style="font-size: 12.5px; color: #94a3b8; margin-bottom: 12px;">Bagian ini belum ditulis.</p>
                    <button type="button" class="btn-top primary" onclick="selectChapterView('${key}')">Buka & Tulis ${escapeHtml(curH.num || curH.title)}</button>
                </div>
            `;
            return;
        }

        let bodyHtml = markdownToAcademicHtml(raw, key === 'daftar_pustaka');
        const testUpper = bodyHtml.slice(0, 350).toUpperCase();
        const hasHeaderAlready = (curH.num && testUpper.includes(curH.num)) || testUpper.includes(curH.title);
        if (!hasHeaderAlready) {
            const hHtml = `
                <div class="academic-chapter-header" style="text-align: center; margin-bottom: 26px; line-height: 1.45;">
                    ${curH.num ? `<div style="font-size: 14pt; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase;">${curH.num}</div>` : ''}
                    <div style="font-size: 14pt; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; margin-top: ${curH.num ? '4px' : '0'};">${curH.title}</div>
                </div>
            `;
            bodyHtml = hHtml + bodyHtml;
        }

        sheetsHtml += `
            <div class="a4-sheet" style="margin-top: 28px;">
                <div class="paper-editor" contenteditable="true" spellcheck="false" data-chapter="${key}">${bodyHtml}</div>
                <div class="sheet-footer">${curH.page}</div>
            </div>
        `;
    });

    container.innerHTML = sheetsHtml;

    container.querySelectorAll('.paper-editor[data-chapter]').forEach((editor) => {
        editor.addEventListener('input', () => {
            const chKey = editor.getAttribute('data-chapter');
            chapterContents[chKey] = editor.innerText;
            isDirty = true;
            document.getElementById('btn-save-edit').style.display = 'inline-flex';
        });
    });
}

function renderActiveSheet() {
    const container = document.getElementById('sheets-container');
    if (activeView === 'all') {
        renderAllSheets();
        return;
    }
    if (activeView === 'front') {
        const savedFront = projectMeta.front_matter_html;
        container.innerHTML = savedFront || buildCoverAndFrontMatterHtml();
        container.querySelectorAll('[contenteditable="true"]').forEach((el) => {
            el.addEventListener('input', () => {
                isDirty = true;
                const saveBtn = document.getElementById('btn-save-edit');
                if (saveBtn) saveBtn.style.display = 'inline-flex';
            });
        });
        return;
    }
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

    // Bersih tanpa banner panduan
    const frontNoticeHtml = '';
    let bodyHtml = markdownToAcademicHtml(raw, activeView === 'daftar_pustaka');

    // Header Bab Standar Skripsi Indonesia (BAB I PENDAHULUAN, dll.)
    const chapterHeaders = {
        'bab1': { num: 'BAB I', title: 'PENDAHULUAN' },
        'bab2': { num: 'BAB II', title: 'TINJAUAN PUSTAKA' },
        'bab3': { num: 'BAB III', title: 'METODOLOGI PENELITIAN' },
        'bab4': { num: 'BAB IV', title: 'HASIL PENELITIAN DAN PEMBAHASAN' },
        'bab5': { num: 'BAB V', title: 'PENUTUP' },
        'daftar_pustaka': { num: '', title: 'DAFTAR PUSTAKA' }
    };
    const curH = chapterHeaders[activeView];
    if (curH) {
        const testUpper = bodyHtml.slice(0, 350).toUpperCase();
        const hasHeaderAlready = (curH.num && testUpper.includes(curH.num)) || testUpper.includes(curH.title);
        if (!hasHeaderAlready) {
            const hHtml = `
                <div class="academic-chapter-header" style="text-align: center; margin-bottom: 26px; line-height: 1.45;">
                    ${curH.num ? `<div style="font-size: 14pt; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase;">${curH.num}</div>` : ''}
                    <div style="font-size: 14pt; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; margin-top: ${curH.num ? '4px' : '0'};">${curH.title}</div>
                </div>
            `;
            bodyHtml = hHtml + bodyHtml;
        }
    }

    let pageNumLabel = '1';
    if (activeView === 'bab2') pageNumLabel = '8';
    else if (activeView === 'bab3') pageNumLabel = '16';
    else if (activeView === 'bab4') pageNumLabel = '24';
    else if (activeView === 'bab5') pageNumLabel = '36';
    else if (activeView === 'daftar_pustaka') pageNumLabel = '42';

    container.innerHTML = `
        ${frontNoticeHtml}
        <div class="a4-sheet">
            <div class="paper-editor" id="paper-editor" contenteditable="true" spellcheck="false">${bodyHtml}</div>
            <div class="sheet-footer">${pageNumLabel}</div>
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
            finishLoadingSheet();
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
            body: JSON.stringify({ chapter: activeView, model, data_riil: researchDataRiil })
        });
        const data = await res.json();
        if (!res.ok || data.status !== 'success') throw new Error(data.message || 'Gagal menyusun bab.');
        chapterContents[activeView] = data.content || '';
        if (data.daftar_pustaka) chapterContents.daftar_pustaka = data.daftar_pustaka;
        showToast(`${chapterLabels[activeView]} selesai disusun.`, 'success');
    } catch (err) {
        showToast(err.message || 'Gagal memproses AI.', 'error');
    } finally {
        finishLoadingSheet();
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
                body: JSON.stringify({ chapter: key, model, data_riil: researchDataRiil })
            });
            const data = await res.json();
            if (!res.ok || data.status !== 'success') throw new Error(data.message || `Gagal menyusun ${key}`);
            chapterContents[key] = data.content || '';
            if (data.daftar_pustaka) chapterContents.daftar_pustaka = data.daftar_pustaka;
            finishLoadingSheet();
            updateProgressUI();
        } catch (err) {
            finishLoadingSheet();
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
    const container = document.getElementById('sheets-container');
    if (!container || activeView === 'revisi') return;
    const btn = document.getElementById('btn-save-edit');
    if (btn) btn.disabled = true;

    try {
        if (activeView === 'front') {
            const htmlContent = container.innerHTML;
            const res = await fetch(`/project/${projectId}/save-chapter`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ chapter: 'front', content: htmlContent })
            });
            const data = await res.json();
            if (!res.ok || data.status !== 'success') throw new Error(data.message || 'Gagal menyimpan.');
            projectMeta.front_matter_html = htmlContent;
            isDirty = false;
            if (btn) btn.style.display = 'none';
            showToast('Perubahan Halaman Sampul & Bagian Depan berhasil disimpan.', 'success');
            return;
        }

        const editor = document.getElementById('paper-editor');
        if (!editor) return;
        const htmlContent = editor.innerText.trim();
        const res = await fetch(`/project/${projectId}/save-chapter`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ chapter: activeView, content: htmlContent })
        });
        const data = await res.json();
        if (!res.ok || data.status !== 'success') throw new Error(data.message || 'Gagal menyimpan perubahan.');
        chapterContents[activeView] = data.content || htmlContent;
        isDirty = false;
        if (btn) btn.style.display = 'none';
        showToast('Perubahan naskah berhasil disimpan.', 'success');
    } catch (err) {
        showToast(err.message || 'Gagal menyimpan.', 'error');
    } finally {
        if (btn) btn.disabled = false;
    }
}

function updateDocFont(val) {
    const editor = document.getElementById('paper-editor');
    if (editor) {
        editor.style.fontFamily = val;
    }
    document.querySelectorAll('.a4-sheet, .paper-editor').forEach(el => {
        el.style.fontFamily = val;
    });
    showToast(`Font dokumen diubah ke ${val}`, 'info');
}

function updateMargins() {
    const top = document.getElementById('setting-margin-top')?.value || '4';
    const left = document.getElementById('setting-margin-left')?.value || '4';
    const bottom = document.getElementById('setting-margin-bottom')?.value || '3';
    const right = document.getElementById('setting-margin-right')?.value || '3';

    ['quick-download-form'].forEach(fId => {
        const f = document.getElementById(fId);
        if (f) {
            if (f.querySelector('input[name="margin_top"]')) f.querySelector('input[name="margin_top"]').value = top;
            if (f.querySelector('input[name="margin_left"]')) f.querySelector('input[name="margin_left"]').value = left;
            if (f.querySelector('input[name="margin_bottom"]')) f.querySelector('input[name="margin_bottom"]').value = bottom;
            if (f.querySelector('input[name="margin_right"]')) f.querySelector('input[name="margin_right"]').value = right;
        }
    });

    document.querySelectorAll('.a4-sheet').forEach(sheet => {
        sheet.style.padding = `${top}cm ${right}cm ${bottom}cm ${left}cm`;
    });
    showToast(`Margin diperbarui (${top}-${left}-${bottom}-${right} cm)`, 'info');
}

function handleSidebarLogoUpload(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    const reader = new FileReader();
    reader.onload = function(e) {
        const img = new Image();
        img.onload = function() {
            const canvas = document.createElement('canvas');
            const maxDim = 320;
            let w = img.width;
            let h = img.height;
            if (w > maxDim || h > maxDim) {
                if (w > h) {
                    h = Math.round((h * maxDim) / w);
                    w = maxDim;
                } else {
                    w = Math.round((w * maxDim) / h);
                    h = maxDim;
                }
            }
            canvas.width = w;
            canvas.height = h;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0, w, h);
            const dataUrl = canvas.toDataURL('image/png', 0.9);

            projectMeta.logo_base64 = dataUrl;
            const prev = document.getElementById('sidebar-logo-preview');
            if (prev) prev.src = dataUrl;

            const coverBox = document.querySelector('.cover-logo-box');
            if (coverBox) {
                coverBox.innerHTML = `<img src="${dataUrl}" alt="Logo Kampus" class="cover-logo-img">`;
            } else {
                const coverLogo = document.querySelector('.cover-logo-img');
                if (coverLogo) coverLogo.src = dataUrl;
            }

            const formLogo = document.querySelector('#quick-download-form input[name="logo_base64"]');
            if (formLogo) formLogo.value = dataUrl;

            fetch(`/project/${projectId}/save-chapter`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ chapter: 'front_logo', content: dataUrl })
            }).then(() => {
                showToast('Logo kampus berhasil diperbarui & disimpan!', 'success');
            }).catch(() => {
                showToast('Logo diterapkan di pratinjau.', 'info');
            });
        };
        img.src = e.target.result;
    };
    reader.readAsDataURL(file);
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
