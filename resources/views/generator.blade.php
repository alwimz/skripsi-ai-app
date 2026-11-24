<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $project->judul }} — SkripsiLab AI Workspace</title>
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
            --ease-out: cubic-bezier(0.22, 1, 0.36, 1);
            --shadow-card: 0 1px 3px rgba(15, 23, 42, 0.04), 0 8px 24px -14px rgba(15, 23, 42, 0.08);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            accent-color: var(--accent);
        }

        html,
        body {
            height: 100%;
            overflow: hidden;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-app);
            color: var(--text-primary);
            -webkit-font-smoothing: antialiased;
        }

        :focus-visible {
            outline: 2px solid var(--accent);
            outline-offset: 2px;
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        .studio-shell {
            display: flex;
            height: 100vh;
            height: 100dvh;
            max-height: 100vh;
            max-height: 100dvh;
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
            height: 100dvh;
            max-height: 100vh;
            max-height: 100dvh;
            overflow: hidden;
            position: relative;
            z-index: 40;
            transition: margin-left 0.24s var(--ease-out), transform 0.24s var(--ease-out);
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
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            width: 100%;
            padding: 8px 12px;
            border-radius: 9px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            font-size: 12.5px;
            font-weight: 700;
            color: #1d4ed8;
            text-decoration: none;
            margin-bottom: 10px;
            transition: background 0.15s ease, border-color 0.15s ease, transform 0.12s ease;
        }

        .back-link:hover {
            background: #dbeafe;
            border-color: #93c5fd;
            text-decoration: none;
        }

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
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 transparent;
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
            min-height: 38px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 9px 11px;
            border-radius: 10px;
            border: 1px solid transparent;
            background: transparent;
            color: var(--text-secondary);
            font-family: inherit;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-align: left;
            transition: background 0.16s var(--ease-out), color 0.16s var(--ease-out), border-color 0.16s ease, box-shadow 0.16s ease;
        }

        .nav-item:hover {
            background: #f1f5f9;
            color: var(--text-primary);
        }

        .nav-item.active {
            background: var(--accent-soft);
            color: var(--accent-hover);
            border-color: var(--accent-border);
            box-shadow: inset 3px 0 0 var(--accent);
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

        .status-dot.done {
            background: var(--green);
        }

        .status-dot.generating {
            background: var(--accent);
            animation: pulseDot 1s infinite;
        }

        @keyframes pulseDot {

            0%,
            100% {
                transform: scale(1);
                opacity: 1;
            }

            50% {
                transform: scale(1.45);
                opacity: 0.55;
            }
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

        .btn-auto:hover {
            background: var(--accent-hover);
        }

        .btn-auto:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-auto-secondary {
            margin-top: 6px;
            background: #ffffff;
            color: var(--accent);
            border: 1px solid var(--accent-border);
        }

        .btn-auto-secondary:hover {
            background: var(--accent-soft);
        }

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
            height: 100dvh;
            max-height: 100vh;
            max-height: 100dvh;
            display: flex;
            flex-direction: column;
            background: var(--bg-canvas);
            overflow: hidden;
        }

        .studio-topbar {
            min-height: var(--topbar-h);
            flex-shrink: 0;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            padding: 8px 20px;
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
            min-height: 36px;
            padding: 6px 14px;
            border-radius: 10px;
            font-family: inherit;
            font-size: 12.5px;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid var(--border-strong);
            background: #ffffff;
            color: var(--text-primary);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
            transition: transform 0.16s var(--ease-out), box-shadow 0.16s var(--ease-out), background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
        }

        .btn-top:hover:not(:disabled) {
            border-color: #93c5fd;
            color: var(--accent-hover);
            background: var(--accent-soft);
            transform: translateY(-1px);
        }

        .btn-top:active:not(:disabled) {
            transform: translateY(0) scale(0.985);
        }

        .btn-top.primary {
            background: linear-gradient(180deg, #2563eb 0%, #1d4ed8 100%);
            border-color: var(--accent);
            color: #ffffff;
            box-shadow: 0 6px 14px -6px rgba(37, 99, 235, 0.45);
        }

        .btn-top.primary:hover:not(:disabled) {
            background: linear-gradient(180deg, #1d4ed8 0%, #1e40af 100%);
            color: #ffffff;
            box-shadow: 0 9px 18px -6px rgba(37, 99, 235, 0.5);
        }

        .btn-top.success {
            background: linear-gradient(180deg, #059669 0%, #047857 100%);
            border-color: var(--green);
            color: #ffffff;
            box-shadow: 0 6px 14px -6px rgba(5, 150, 105, 0.4);
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
            border-radius: 14px;
            padding: 13px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            box-shadow: var(--shadow-card);
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
            max-width: 794px;
            min-height: 1123px; /* Ukuran persis kertas A4 (210mm x 297mm @ 96dpi) */
            background: #ffffff;
            border: 1px solid #dbe3ee;
            border-radius: 6px;
            box-shadow: 0 12px 32px -12px rgba(15, 23, 42, 0.10), 0 2px 6px rgba(15, 23, 42, 0.04);
            padding: 80px 72px 58px 94px;
            /* Rasio presisi margin skripsi 4-4-3-3 cm & spasi 1,5 Word/PDF */
            color: #111827;
            font-family: "Times New Roman", Georgia, serif;
            font-size: 14.5px;
            line-height: 1.52;
            box-sizing: border-box;
            word-break: break-word;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            margin: 0 auto 24px auto;
            position: relative;
        }

        .cover-sheet {
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 1123px;
            box-sizing: border-box;
            padding: 95px 72px 85px 95px; /* Rasio presisi margin akademik 4-4-3-3 cm */
        }

        .cover-top-block {
            margin-bottom: 0;
        }

        .cover-kind {
            font-size: 14pt;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            margin-bottom: 14px;
            color: #0f172a;
        }

        .cover-title {
            font-size: 14pt;
            font-weight: 700;
            line-height: 1.4;
            text-transform: uppercase;
            margin-bottom: 14px;
            color: #0f172a;
        }

        .cover-sub {
            font-size: 11pt;
            line-height: 1.5;
            max-width: 560px;
            margin: 0 auto;
            color: #334155;
        }

        .cover-logo-box {
            margin: 32px auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .cover-logo-img {
            max-width: 135px;
            max-height: 135px;
            width: auto;
            height: auto;
            object-fit: contain;
            display: block;
            margin: 0 auto;
        }

        .cover-author-block {
            margin: 28px auto;
            font-size: 12pt;
            line-height: 1.6;
            color: #0f172a;
        }

        .cover-campus-block {
            margin-top: 0;
            font-size: 13pt;
            font-weight: 700;
            line-height: 1.5;
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
            gap: 3px;
            font-size: 13.5px;
            line-height: 1.42;
        }

        .toc-row {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 10px;
        }

        .toc-row.sub {
            padding-left: 22px;
            font-weight: 400;
        }

        .toc-row.main {
            font-weight: 700;
            margin-top: 2px;
        }

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
            margin-top: auto;
            padding-top: 16px;
            text-align: center;
            font-size: 13.5px;
            font-family: 'Times New Roman', serif;
            font-weight: 600;
            color: #475569;
            border-top: 1px solid rgba(203, 213, 225, 0.55);
            user-select: none;
            flex-shrink: 0;
        }

        .paper-editor {
            outline: none;
            min-height: 420px;
        }

        .paper-editor:focus {
            box-shadow: inset 0 0 0 2px #dbeafe;
            border-radius: 4px;
        }

        .paper-editor h1,
        .paper-editor h2 {
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
            margin-bottom: 6px;
        }

        .paper-editor ul,
        .paper-editor ol {
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
            max-width: 100%;
            overflow-x: auto;
            margin: 18px 0;
            -webkit-overflow-scrolling: touch;
        }

        .paper-editor table {
            width: 100%;
            max-width: 100%;
            border-collapse: collapse;
            font-size: 9.5pt;
            line-height: 1.3;
            font-family: inherit;
            margin: 6px 0 16px;
            table-layout: auto;
            background: #ffffff;
            color: #000000;
            border: 1.25px solid #000000;
        }

        .paper-editor th,
        .paper-editor td {
            border: 1px solid #1e293b;
            padding: 5px 6px;
            vertical-align: top;
            text-align: left;
            text-indent: 0 !important;
            word-break: break-word;
            overflow-wrap: anywhere;
            white-space: normal;
            color: #000000;
            box-sizing: border-box;
        }

        .paper-editor tr:nth-child(even) td {
            background: #fafbfc;
        }

        .paper-editor th {
            background: #f1f5f9;
            font-weight: 700;
            color: #000000;
            text-align: center;
            vertical-align: middle;
            font-size: 9.5pt;
            border: 1px solid #000000;
            border-bottom: 1.5px solid #000000;
        }

        /* Sitasi Biru Hyperlink Alami Akademik (Sesuai Standar Naskah Digital) */
        .academic-cite-badge, .cite-blue {
            color: #2563eb !important;
            text-decoration: underline !important;
            text-underline-offset: 2.5px;
            background: transparent !important;
            border: none !important;
            border-radius: 0 !important;
            padding: 0 !important;
            margin: 0 !important;
            font-weight: inherit !important;
            font-family: inherit !important;
            font-size: inherit !important;
            cursor: pointer;
            display: inline !important;
            box-shadow: none !important;
            transition: color 0.15s ease;
        }
        .academic-cite-badge:hover, .cite-blue:hover {
            color: #1d4ed8 !important;
            text-decoration-thickness: 1.5px;
        }
        .academic-cite-badge::after {
            display: none !important;
            content: none !important;
        }

        /* Di dalam tabel, teks sitasi bersih tanpa background & tombol kotak */
        .table-cite-text, table .academic-cite-badge, table .cite-blue {
            color: #0f172a !important;
            text-decoration: none !important;
            cursor: pointer;
            background: transparent !important;
            border: none !important;
            padding: 0 !important;
            margin: 0 !important;
            font-weight: inherit !important;
            display: inline !important;
        }
        .table-cite-text:hover, table .academic-cite-badge:hover, table .cite-blue:hover {
            color: #2563eb !important;
            text-decoration: underline !important;
        }

        .academic-diagram-box {
            margin: 18px 0;
            padding: 16px 20px;
            background: #ffffff;
            border: 1.5px solid #000000;
            border-radius: 0;
            font-family: 'Times New Roman', serif;
            font-size: 10.5pt;
            line-height: 1.5;
            white-space: pre;
            overflow-x: auto;
            color: #0f172a;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
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

        .btn-mini-spinner {
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255, 255, 255, 0.4);
            border-top-color: #ffffff;
            border-radius: 999px;
            display: inline-block;
            animation: spin 0.7s linear infinite;
            flex-shrink: 0;
        }

        .btn-mini-spinner-dark {
            width: 14px;
            height: 14px;
            border: 2px solid #bfdbfe;
            border-top-color: #2563eb;
            border-radius: 999px;
            display: inline-block;
            animation: spin 0.7s linear infinite;
            flex-shrink: 0;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

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
            0% {
                background-position: 200% 0;
            }

            100% {
                background-position: -200% 0;
            }
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

        .revisi-select,
        .revisi-textarea,
        .revisi-file {
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

        .revisi-select:focus,
        .revisi-textarea:focus {
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

        @keyframes toastSlideIn {
            from {
                opacity: 0;
                transform: translateY(10px) scale(0.97);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .toast {
            background: #0f172a;
            color: #ffffff;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            box-shadow: 0 14px 30px -8px rgba(15, 23, 42, 0.28);
            max-width: 360px;
            animation: toastSlideIn 0.22s var(--ease-out);
        }

        .toast.success {
            background: #065f46;
        }

        .toast.error {
            background: #991b1b;
        }

        /* Responsive Styles */
        @media (max-width: 900px) {
            .studio-sidebar {
                position: fixed;
                left: 0;
                top: 0;
                bottom: 0;
                height: 100%;
                height: 100dvh !important;
                max-height: 100dvh !important;
                transform: translateX(-100%);
                margin-left: 0 !important;
                z-index: 100;
                display: flex;
                flex-direction: column;
                overflow: hidden;
            }

            .studio-shell.mobile-sidebar-open .studio-sidebar {
                transform: translateX(0);
            }

            .studio-shell.mobile-sidebar-open .sidebar-backdrop {
                display: block;
            }

            .sidebar-nav {
                flex: 1 1 auto;
                min-height: 0;
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
            }

            .sidebar-download-sticky {
                position: sticky;
                bottom: 0;
                left: 0;
                right: 0;
                background: #ffffff !important;
                border-top: 1.5px solid #cbd5e1;
                padding: 12px 14px max(24px, env(safe-area-inset-bottom, 24px)) !important;
                box-shadow: 0 -6px 20px rgba(15, 23, 42, 0.12);
                z-index: 100;
            }

            .a4-sheet {
                width: 100% !important;
                max-width: 100% !important;
                min-height: 920px !important;
                height: auto !important;
                padding: 44px 36px 34px 46px !important;
                font-size: 14.5px !important;
                line-height: 1.56 !important;
                box-sizing: border-box !important;
            }
            .a4-sheet.empty-sheet,
            .a4-sheet.empty-sheet-premium {
                min-height: 260px !important;
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

            .topbar-actions .topbar-tier-pill {
                height: 32px;
                font-size: 11px;
                padding: 0 9px;
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
                min-height: 760px !important;
                height: auto !important;
                box-sizing: border-box;
                padding: 34px 22px 28px 26px !important;
                font-size: 13.5px !important;
                line-height: 1.55 !important;
                border-radius: 6px;
                box-shadow: 0 2px 10px rgba(15, 23, 42, 0.05);
                word-break: break-word;
                overflow-x: clip;
                display: flex;
                flex-direction: column;
                justify-content: flex-start;
                margin-bottom: 24px;
                position: relative;
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
                border-radius: 14px;
            }

            .olah-input,
            .revisi-select,
            .revisi-textarea {
                font-size: 13.5px;
            }
        }

        /* Lampiran Tersimpan Responsive Card */
        .lampiran-list-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 14px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
        }

        .lampiran-item-main {
            display: flex;
            align-items: center;
            gap: 11px;
            min-width: 0;
            flex: 1;
        }

        .lampiran-item-body {
            min-width: 0;
            flex: 1;
        }

        .lampiran-item-title {
            font-size: 13.5px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.35;
            overflow-wrap: anywhere;
        }

        .lampiran-item-meta {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
            margin-top: 3px;
            font-size: 11px;
            color: #64748b;
        }

        .lampiran-type-pill {
            display: inline-flex;
            align-items: center;
            padding: 1px 7px;
            border-radius: 999px;
            background: #e0f2fe;
            color: #0369a1;
            font-weight: 600;
            font-size: 10.5px;
        }

        .lampiran-filename {
            display: block;
            margin-top: 3px;
            font-size: 11.5px;
            color: #64748b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
        }

        .lampiran-item-actions {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-shrink: 0;
        }

        #lampiran-file-status > div {
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        @media (max-width: 640px) {
            .lampiran-list-item {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
                padding: 11px 12px;
            }

            .lampiran-item-actions {
                justify-content: flex-end;
                padding-top: 8px;
                border-top: 1px solid #e2e8f0;
            }

            .lampiran-item-actions .btn-top {
                height: 30px;
                font-size: 12px;
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

        .olah-modal-tabs {
            display: flex;
            gap: 6px;
            background: #f1f5f9;
            padding: 4px;
            border-radius: 11px;
            border: 1px solid #e2e8f0;
        }
        .olah-modal-tab {
            flex: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid transparent;
            background: transparent;
            color: #475569;
            font-size: 12.5px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .olah-modal-tab.active {
            background: #ffffff;
            color: #1d4ed8;
            border-color: #bfdbfe;
            box-shadow: 0 1px 4px rgba(37, 99, 235, 0.1);
        }
        .olah-mini-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 9px;
            border-radius: 999px;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            font-size: 11.5px;
            font-weight: 600;
            color: #1e293b;
            cursor: pointer;
        }
        .olah-mini-chip.checked {
            background: #eff6ff;
            border-color: #2563eb;
            color: #1d4ed8;
        }
        .olah-mini-tbl-wrap {
            width: 100%;
            overflow-x: auto;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
        }
        .olah-mini-tbl {
            width: 100%;
            border-collapse: collapse;
            font-size: 11.5px;
            white-space: nowrap;
        }
        .olah-mini-tbl th {
            background: #eff6ff;
            color: #1e3a8a;
            padding: 6px 8px;
            text-align: left;
            border-bottom: 1px solid #bfdbfe;
        }
        .olah-mini-tbl td {
            padding: 5px 8px;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
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
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
                        <line x1="19" y1="12" x2="5" y2="12" />
                        <polyline points="12 19 5 12 12 5" />
                    </svg>
                    <span>Kembali ke Proyek Saya</span>
                </a>
                <span class="project-mode-badge" style="margin-top: 2px;">
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
                <button type="button" class="nav-item active" id="nav-item-all" onclick="selectChapterView('all')"
                    style="margin-bottom: 6px; background: linear-gradient(135deg, #eff6ff 0%, #f8fafc 100%); border: 1px solid #bfdbfe;">
                    <span class="nav-item-left">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                            <polyline points="14 2 14 8 20 8" />
                            <line x1="16" y1="13" x2="8" y2="13" />
                            <line x1="16" y1="17" x2="8" y2="17" />
                            <polyline points="10 9 9 9 8 9" />
                        </svg>
                        <span class="nav-item-text" style="color: #1e40af; font-weight: 700;">Lihat Semua Bab
                            (Full)</span>
                    </span>
                    <span
                        style="font-size: 10px; font-weight: 700; color: #2563eb; background: #dbeafe; padding: 2px 6px; border-radius: 999px;">A4
                        Full</span>
                </button>
                <button type="button" class="nav-item" id="nav-item-front" onclick="selectChapterView('front')"
                    style="margin-bottom: 8px; padding: 7px 10px; height: auto;">
                    <span class="nav-item-left" style="align-items: flex-start;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" style="margin-top: 2px; flex-shrink: 0;">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />
                        </svg>
                        <div style="display: flex; flex-direction: column; text-align: left; line-height: 1.25;">
                            <span style="font-weight: 700; font-size: 12.5px;">Sampul &amp;</span>
                            <span style="font-size: 11.5px; opacity: 0.9;">Bagian Depan</span>
                        </div>
                    </span>
                    <span
                        style="font-size: 10px; font-weight: 700; color: #16a34a; background: #dcfce7; padding: 2px 6px; border-radius: 999px; align-self: center;">Lengkap</span>
                </button>
                @foreach ($chapterTitles as $key => $label)
                    <button type="button" class="nav-item" id="nav-item-{{ $key }}"
                        onclick="selectChapterView('{{ $key }}')">
                        <span class="nav-item-left">
                            <span class="nav-item-text">{{ $label }}</span>
                        </span>
                        <span class="status-dot {{ !empty($project->{$key . '_content'}) ? 'done' : '' }}"
                            id="dot-{{ $key }}"></span>
                    </button>
                @endforeach

                <button type="button" class="nav-item" id="nav-item-daftar_pustaka"
                    onclick="selectChapterView('daftar_pustaka')">
                    <span class="nav-item-left">
                        <span class="nav-item-text">Daftar Pustaka</span>
                    </span>
                    <span class="status-dot {{ !empty($project->daftar_pustaka) ? 'done' : '' }}"
                        id="dot-daftar_pustaka"></span>
                </button>

                @php $initLampiranCount = is_array($meta['lampiran'] ?? null) ? count($meta['lampiran']) : 0; @endphp
                <button type="button" class="nav-item" id="nav-item-lampiran"
                    onclick="selectChapterView('lampiran')">
                    <span class="nav-item-left">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48" />
                        </svg>
                        <span class="nav-item-text">Lampiran</span>
                    </span>
                    <span style="display: inline-flex; align-items: center; gap: 6px;">
                        <span id="lampiran-count-badge"
                            style="font-size: 10.5px; font-weight: 700; padding: 1px 6px; border-radius: 999px; background: {{ $initLampiranCount > 0 ? '#dbeafe' : '#f1f5f9' }}; color: {{ $initLampiranCount > 0 ? '#1d4ed8' : '#64748b' }};">{{ $initLampiranCount }}</span>
                        <span class="status-dot {{ $initLampiranCount > 0 ? 'done' : '' }}"
                            id="dot-lampiran"></span>
                    </span>
                </button>

                <button type="button" class="nav-item nav-item-revisi" id="nav-item-revisi"
                    onclick="selectChapterView('revisi')">
                    <span class="nav-item-left">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path d="M10 2v7.31L4.62 17.6A2 2 0 0 0 6.3 20.5h11.4a2 2 0 0 0 1.68-2.9L14 9.31V2z" />
                            <line x1="8.5" y1="2" x2="15.5" y2="2" />
                            <line x1="14" y1="9" x2="10" y2="9" />
                        </svg>
                        <span class="nav-item-text">Revisi</span>
                    </span>
                    <span style="font-size: 11px; font-weight: 700;">Sub-bab</span>
                </button>

                <div class="auto-box">
                    <div class="auto-box-title" style="display: flex; align-items: center; gap: 6px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                            <polyline points="14 2 14 8 20 8" />
                            <line x1="16" y1="13" x2="8" y2="13" />
                            <line x1="16" y1="17" x2="8" y2="17" />
                        </svg>
                        <span>Penulisan Berurutan</span>
                    </div>
                    <div class="auto-box-desc">
                        @if ($isProposal)
                            Susun Bab I–III & Daftar Pustaka berurutan sesuai pengaturan proyekmu.
                        @else
                            Susun berurutan Bab I–V & Daftar Pustaka sesuai struktur naskah.
                        @endif
                    </div>
                    <button type="button" class="btn-auto" id="btn-generate-all" onclick="generateSequential()">
                        {{ $isProposal ? 'Susun Proposal (Bab I–III)' : 'Susun Skripsi (Bab I–V)' }}
                    </button>
                    @if ($isProposal)
                        <button type="button" class="btn-auto btn-auto-secondary" onclick="upgradeToFullSkripsi()">
                            Lanjutkan ke Bab IV–V (Full Skripsi)
                        </button>
                    @endif
                </div>

                <!-- Format & Document Settings Card (Tersimpan di scroll nav agar tombol download selalu terlihat) -->
                <div class="format-settings-card"
                    style="padding: 10px; margin-top: 14px; margin-bottom: 8px; background: #f8fafc; border: 1px solid var(--border); border-radius: 9px; font-size: 11.5px;">
                    <div
                        style="font-weight: 700; color: var(--text-primary); margin-bottom: 6px; display: flex; align-items: center; justify-content: space-between;">
                        <span>Format &amp; Margin</span>
                        <span id="margin-badge-label"
                            style="font-size: 10px; color: var(--accent); font-weight: 700; background: #e0f2fe; padding: 1px 6px; border-radius: 4px;">4-4-3-3
                            cm</span>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin-bottom: 6px;">
                        <div>
                            <label
                                style="display: block; font-size: 9.5px; color: var(--text-secondary); margin-bottom: 2px;">Font
                                Naskah</label>
                            <select id="doc-font-select" onchange="updateDocFont(this.value)"
                                style="width: 100%; font-size: 10.5px; padding: 3px 4px; border: 1px solid var(--border-strong); border-radius: 5px; background: #fff;">
                                <option value="times" selected>Times New Roman 12pt</option>
                                <option value="arial">Arial 11pt</option>
                                <option value="georgia">Georgia 11pt</option>
                            </select>
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: 9.5px; color: var(--text-secondary); margin-bottom: 2px;">Margin
                                K-A-K-B (cm)</label>
                            <div style="display: flex; gap: 2px;">
                                <input type="number" id="m-left" value="4" min="2" max="5"
                                    style="width: 25%; font-size: 10.5px; padding: 2px 1px; text-align: center; border: 1px solid var(--border-strong); border-radius: 4px;"
                                    title="Kiri (cm)" onchange="updateMargins()">
                                <input type="number" id="m-top" value="4" min="2" max="5"
                                    style="width: 25%; font-size: 10.5px; padding: 2px 1px; text-align: center; border: 1px solid var(--border-strong); border-radius: 4px;"
                                    title="Atas (cm)" onchange="updateMargins()">
                                <input type="number" id="m-right" value="3" min="2" max="5"
                                    style="width: 25%; font-size: 10.5px; padding: 2px 1px; text-align: center; border: 1px solid var(--border-strong); border-radius: 4px;"
                                    title="Kanan (cm)" onchange="updateMargins()">
                                <input type="number" id="m-bottom" value="3" min="2" max="5"
                                    style="width: 25%; font-size: 10.5px; padding: 2px 1px; text-align: center; border: 1px solid var(--border-strong); border-radius: 4px;"
                                    title="Bawah (cm)" onchange="updateMargins()">
                            </div>
                        </div>
                    </div>

                    <div>
                        <label
                            style="display: block; font-size: 9.5px; color: var(--text-secondary); margin-bottom: 2px;">Logo
                            Kampus (Cover)</label>
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <img id="sidebar-logo-preview"
                                src="{{ !empty($meta['logo_base64']) ? $meta['logo_base64'] : '/images/academic_seal.svg' }}"
                                alt="Logo"
                                style="width: 24px; height: 24px; object-fit: contain; border-radius: 4px; border: 1px solid #cbd5e1; background: #fff; cursor: pointer;"
                                title="Klik untuk ganti logo kampus"
                                onclick="document.getElementById('sidebar-logo-upload').click();"
                                onerror="this.onerror=null;this.src='/images/academic_seal.svg';">
                            <button type="button" onclick="document.getElementById('sidebar-logo-upload').click()"
                                style="flex: 1; padding: 4px 8px; border: 1px dashed var(--accent-border); background: #ffffff; border-radius: 5px; font-size: 10.5px; color: var(--accent); font-weight: 600; text-align: center; cursor: pointer; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-family: inherit;">
                                <span>📷 Ganti Logo</span>
                            </button>
                            <input type="file" id="sidebar-logo-upload"
                                accept="image/png,image/jpeg,image/svg+xml,image/webp" style="display: none;"
                                onchange="handleSidebarLogoUpload(this)">
                        </div>
                        <button type="button" onclick="openEditIdentityModal()"
                            style="width: 100%; margin-top: 6px; padding: 5px 8px; border: 1px solid #bfdbfe; background: #eff6ff; border-radius: 5px; font-size: 10.5px; color: #2563eb; font-weight: 600; text-align: center; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 5px; font-family: inherit;">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                            <span>✏️ Edit Identitas (Nama &amp; NIM)</span>
                        </button>
                    </div>
                </div>
            </nav>

            <!-- Sticky Download Buttons (Selalu Tampak di Desktop & HP) -->
            <div class="sidebar-download-sticky"
                style="flex-shrink: 0; padding: 12px 14px; padding-bottom: max(16px, env(safe-area-inset-bottom, 16px)); background: #ffffff; border-top: 1px solid var(--border); box-shadow: 0 -4px 12px rgba(15, 23, 42, 0.06); z-index: 50;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                    <button type="button" class="btn-dl-side primary" onclick="submitQuickDownload('docx')">⬇ Word
                        (.docx)</button>
                    <button type="button" class="btn-dl-side" onclick="submitQuickDownload('pdf')">⬇ PDF (.pdf)</button>
                </div>
            </div>
        </aside>

        <main class="studio-main">
            <header class="studio-topbar">
                <div class="topbar-left">
                    <button type="button" class="btn-icon" onclick="toggleStudioSidebar()" aria-label="Toggle Sidebar"
                        title="Buka/Tutup Sidebar">☰</button>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="topbar-brand-label"
                            style="font-size: 14px; font-weight: 700; color: var(--text-primary); letter-spacing: 0.01em;">Workspace
                            Penulisan</span>
                    </div>
                </div>

                @php
                    $isGenUnlocked = !empty($projectUnlocked);
                @endphp
                <div class="topbar-actions">
                    <input type="hidden" id="studio-model" value="auto">
                    <a href="{{ route('billing.plans') }}" class="btn-top topbar-tier-pill"
                        style="background: {{ $isGenUnlocked ? '#f0fdf4' : '#fffbeb' }}; border-color: {{ $isGenUnlocked ? '#bbf7d0' : '#fde68a' }}; color: {{ $isGenUnlocked ? '#15803d' : '#b45309' }}; font-weight: 700; white-space: nowrap;">
                        <span>{{ $isGenUnlocked ? '✓ Full Akses' : 'Paket Free' }}</span>
                        @if(!$isGenUnlocked)
                            <span style="color: #2563eb; font-weight: 800;">Upgrade →</span>
                        @endif
                    </a>
                    <button type="button" class="btn-top success" id="btn-save-edit" style="display: none;"
                        onclick="saveActiveEdit()">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                            <polyline points="17 21 17 13 7 13 7 21" />
                        </svg>
                        <span>Simpan Editan</span>
                    </button>
                    <button type="button" class="btn-top primary" id="btn-generate-current"
                        onclick="generateCurrentView()">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path d="M12 20h9" />
                            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" />
                        </svg>
                        <span id="btn-generate-current-label">Tulis Bab Ini</span>
                    </button>
                </div>
            </header>

            <div class="canvas-scroll">
                <div class="chapter-bar" id="chapter-bar">
                    <div style="flex: 1; min-width: 220px;">
                        <div class="chapter-bar-title" id="active-chapter-title">NASKAH LENGKAP SKRIPSI (FULL DOCUMENT)</div>
                        <div class="chapter-bar-sub" id="active-chapter-sub" style="display: none;"></div>
                    </div>
                    <div class="chapter-bar-actions"
                        style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap;">
                        <button type="button" class="btn-top" onclick="selectChapterView('all')"
                            title="Lihat naskah lengkap bersambung dari sampul sampai daftar pustaka">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z" />
                                <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z" />
                            </svg>
                            <span>Lihat Semua Bab</span>
                        </button>
                        <button type="button" class="btn-top" id="btn-menu-olah-data" onclick="openDataRiilModal()"
                            style="border-color: #cbd5e1;"
                            title="Isi data olahan & rumus penelitian (SPSS / PLS / ML / Eksperimen) untuk Bab 3 & 4">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path d="M18 20V10" />
                                <path d="M12 20V4" />
                                <path d="M6 20v-6" />
                            </svg>
                            <span>Olah Data Penelitian</span>
                        </button>
                        <button type="button" class="btn-top" onclick="selectChapterView('revisi')">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path d="M10 2v7.31L4.62 17.6A2 2 0 0 0 6.3 20.5h11.4a2 2 0 0 0 1.68-2.9L14 9.31V2z" />
                            </svg>
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
        <input type="hidden" name="font_family" value="times">
        <input type="hidden" name="margin_top" value="4">
        <input type="hidden" name="margin_left" value="4">
        <input type="hidden" name="margin_bottom" value="3">
        <input type="hidden" name="margin_right" value="3">
    </form>

    <!-- Modal Menu Olah Data & Hasil Penelitian -->
    <div id="modal-data-riil" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(5px); z-index: 9999; justify-content: center; align-items: center; padding: 16px; box-sizing: border-box;">
        <div
            style="background: #ffffff; border-radius: 18px; width: 100%; max-width: 660px; max-height: 90vh; overflow-y: auto; padding: 24px; box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25); box-sizing: border-box; display: flex; flex-direction: column; gap: 18px; border: 1px solid #e2e8f0;">
            <div
                style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid #e2e8f0; padding-bottom: 14px;">
                <div style="display: flex; gap: 12px; align-items: flex-start;">
                    <div
                        style="width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); color: #2563eb; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid #bfdbfe;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.2">
                            <path d="M18 20V10" />
                            <path d="M12 20V4" />
                            <path d="M6 20v-6" />
                        </svg>
                    </div>
                    <div>
                        <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 4px 0;">Menu Olah Data
                            &amp; Hasil Penelitian</h3>
                        <p style="font-size: 12.5px; color: #64748b; margin: 0; line-height: 1.5;">Masukkan output olah
                            data riil &amp; rumus agar dijabarkan langkah demi langkah di Bab III &amp; IV.</p>
                    </div>
                </div>
                <button type="button" onclick="closeDataRiilModal()"
                    style="border: none; background: #f1f5f9; width: 28px; height: 28px; border-radius: 50%; font-size: 18px; color: #64748b; cursor: pointer; display: flex; align-items: center; justify-content: center; line-height: 1; transition: all 0.15s;">&times;</button>
            </div>

            <!-- Switcher Tab Modal: Hitung Otomatis Valid SPSS vs Isi Manual / Preset -->
            <div class="olah-modal-tabs">
                <button type="button" class="olah-modal-tab active" id="proj-olah-tab-calc" onclick="switchProjOlahTab('calc')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10"/><path d="M12 20V4"/><path d="M6 20v-6"/></svg>
                    <span>Hitung Otomatis Excel/CSV (Valid SPSS)</span>
                </button>
                <button type="button" class="olah-modal-tab" id="proj-olah-tab-manual" onclick="switchProjOlahTab('manual')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                    <span>Form Data Olahan Bab III &amp; IV</span>
                </button>
            </div>

            <!-- PANEL A: HITUNG OTOMATIS DARI EXCEL / CSV -->
            <div id="proj-olah-panel-calc" style="display: flex; flex-direction: column; gap: 12px;">
                <div style="background: #f8fbff; border: 1.5px dashed #93c5fd; border-radius: 12px; padding: 14px; display: flex; flex-direction: column; gap: 8px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
                        <div style="font-size: 12.5px; font-weight: 700; color: #0f172a;">1. Unggah Data Mentah Excel (.xlsx/.xls) atau CSV</div>
                        <a href="{{ route('app.create', ['tab' => 'olahdata']) }}" target="_blank" style="font-size: 11.5px; font-weight: 600; color: #2563eb; text-decoration: none;">Belum punya kuesioner / template? Buka Studio Olah Data &rarr;</a>
                    </div>
                    <input type="file" id="proj-od-file" accept=".xlsx,.xls,.csv,.tsv,.txt" class="olah-input" style="padding: 7px 10px; font-size: 12px;" onchange="projOdHandleFile(this)">
                    <div id="proj-od-file-status" style="font-size: 11.5px; color: #64748b;">Baris pertama otomatis menjadi nama kolom variabel. Semua hitungan menggunakan rumus eksak setara IBM SPSS.</div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 10px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; color: #1e293b; margin-bottom: 5px;">2. Pilih Jenis Uji Statistik</label>
                        <select id="proj-od-test" class="olah-input" onchange="projOdRenderVars()">
                            <option value="regression">Regresi Linier + Uji Asumsi Klasik Lengkap</option>
                            <option value="validity_reliability">Uji Validitas (Pearson) &amp; Reliabilitas (Cronbach Alpha)</option>
                            <option value="anova">One-Way ANOVA + Levene &amp; Post-Hoc (Teknik / Uji Lab)</option>
                            <option value="ttest_ind">Independent Samples t-Test (2 Kelompok Bebas)</option>
                            <option value="ttest_paired">Paired Samples t-Test (Pre-Test vs Post-Test)</option>
                            <option value="correlation">Korelasi Pearson &amp; Spearman Rank</option>
                            <option value="descriptive">Statistik Deskriptif &amp; Uji Normalitas K-S</option>
                            <option value="chisquare">Uji Chi-Square &amp; Crosstab (Kategorik)</option>
                            <option value="nonparam">Non-Parametrik (Mann-Whitney / Wilcoxon / Kruskal-Wallis)</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; color: #1e293b; margin-bottom: 5px;">Taraf Signifikansi (&alpha;)</label>
                        <select id="proj-od-alpha" class="olah-input">
                            <option value="0.05" selected>0.05 (Standar 95%)</option>
                            <option value="0.01">0.01 (Ketat 99%)</option>
                            <option value="0.10">0.10 (Eksploratif 90%)</option>
                        </select>
                    </div>
                </div>

                <div id="proj-od-vars-box" style="display: none; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px;"></div>
                <div id="proj-od-err" style="display: none; padding: 8px 12px; border-radius: 8px; background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; font-size: 12px; font-weight: 600;"></div>

                <div style="display: flex; justify-content: flex-end; gap: 8px; flex-wrap: wrap;">
                    <button type="button" id="proj-od-run-btn" onclick="projOdRunCalc()"
                        style="padding: 8px 16px; border: none; background: #059669; color: #ffffff; border-radius: 9px; font-size: 12.5px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                        <span>Hitung Statistik Valid</span>
                    </button>
                </div>

                <div id="proj-od-result-box" style="display: none; border: 1px solid #bbf7d0; background: #f0fdf4; border-radius: 12px; padding: 12px; flex-direction: column; gap: 10px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                        <div style="font-size: 12.5px; font-weight: 700; color: #166534;" id="proj-od-res-title">Hasil Valid Siap Diterapkan</div>
                        <button type="button" onclick="projOdApplyToForm()"
                            style="padding: 7px 14px; border: none; background: #2563eb; color: #ffffff; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer;">
                            Terapkan Hasil ke Form Bab III &amp; IV &rarr;
                        </button>
                    </div>
                    <div id="proj-od-res-hl" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 6px;"></div>
                    <div id="proj-od-res-preview" class="olah-mini-tbl-wrap"></div>
                </div>
            </div>

            <!-- PANEL B: FORM DATA OLAHAN MANUAL / PRESET -->
            <div id="proj-olah-panel-manual" style="display: none; flex-direction: column; gap: 14px;">
                <!-- Preset Template Cepat -->
                <div>
                    <div
                        style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">
                        Pilih Template Olah Data Siap Pakai:</div>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <button type="button" class="olah-chip" onclick="applyOlahDataPreset('spss')">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10"/><path d="M12 20V4"/><path d="M6 20v-6"/></svg>
                            <span>SPSS (Regresi &amp; Asumsi)</span>
                        </button>
                        <button type="button" class="olah-chip" onclick="applyOlahDataPreset('sipil')">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/></svg>
                            <span>Teknik Sipil / Uji Lab (ANOVA)</span>
                        </button>
                        <button type="button" class="olah-chip" onclick="applyOlahDataPreset('pls')">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                            <span>SmartPLS (SEM-PLS)</span>
                        </button>
                        <button type="button" class="olah-chip" onclick="applyOlahDataPreset('ml')">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/></svg>
                            <span>Machine Learning / AI</span>
                        </button>
                        <button type="button" class="olah-chip" onclick="applyOlahDataPreset('eksperimen')">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 3h6"/><path d="M10 3v6l-5 8a2 2 0 0 0 1.7 3h10.6a2 2 0 0 0 1.7-3l-5-8V3"/></svg>
                            <span>Eksperimen (N-Gain / t-Test)</span>
                        </button>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 12px;">
                        <div>
                            <label
                                style="display: block; font-size: 12px; font-weight: 600; color: #1e293b; margin-bottom: 5px;">Software
                                / Metode Olah Data</label>
                            <input type="text" id="riil-metode" class="olah-input"
                                placeholder="cth: IBM SPSS Statistics 26 (Regresi Linier)">
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: 12px; font-weight: 600; color: #1e293b; margin-bottom: 5px;">Populasi
                                / Sampel / Dataset Riil</label>
                            <input type="text" id="riil-sampel" class="olah-input"
                                placeholder="cth: 120 Responden Mahasiswa / 1.500 Data">
                        </div>
                    </div>

                    <div>
                        <label
                            style="display: block; font-size: 12px; font-weight: 600; color: #1e293b; margin-bottom: 5px;">Rumus
                            Matematika / Statistik Wajib (Langkah demi Langkah)</label>
                        <input type="text" id="riil-rumus" class="olah-input"
                            placeholder="cth: Y = a + b1X1 + b2X2 + e, Uji t = (r√n-2)/√(1-r^2), R-Square, atau Akurasi = (TP+TN)/Total">
                    </div>

                    <div>
                        <label
                            style="display: block; font-size: 12px; font-weight: 600; color: #1e293b; margin-bottom: 5px;">Lokasi
                            / Objek / Sumber Data Penelitian</label>
                        <input type="text" id="riil-lokasi" class="olah-input"
                            placeholder="cth: Universitas Handayani / PT X / Lab Struktur Beton">
                    </div>

                    <div>
                        <label
                            style="display: block; font-size: 12px; font-weight: 600; color: #1e293b; margin-bottom: 5px;">Nilai
                            Metrik / Angka Riil Pengujian (Untuk Narasi &amp; Tabel Bab IV)</label>
                        <textarea id="riil-metrik" rows="3" class="olah-input" style="resize: vertical; min-height: 72px;"
                            placeholder="cth: Output SPSS: R-Square = 0.684, F-hitung = 45.21 (Sig 0.000 < 0.05). X1 t-hitung = 3.42 (Sig 0.001), X2 t-hitung = 4.15 (Sig 0.000). Semua lolos uji asumsi klasik."></textarea>
                    </div>

                    <div>
                        <label
                            style="display: block; font-size: 12px; font-weight: 600; color: #1e293b; margin-bottom: 5px;">Teknik
                            Pengujian &amp; Kriteria Uji Hipotesis</label>
                        <input type="text" id="riil-evaluasi" class="olah-input"
                            placeholder="cth: Uji Asumsi Klasik (Kolmogorov-Smirnov, VIF, Glejser), Uji t parsial, Uji F simultan, R2">
                    </div>
                </div>
            </div>

            <div
                style="display: flex; justify-content: space-between; align-items: center; gap: 10px; border-top: 1px solid #e2e8f0; padding-top: 14px; flex-wrap: wrap;">
                <a href="{{ route('app.create', ['tab' => 'olahdata']) }}" target="_blank"
                    style="font-size: 12px; font-weight: 600; color: #2563eb; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    <span>Studio Olah Data Lengkap</span>
                </a>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <button type="button" onclick="closeDataRiilModal()"
                        style="padding: 9px 18px; border: 1px solid #cbd5e1; background: #ffffff; border-radius: 9px; font-size: 13px; font-weight: 600; color: #475569; cursor: pointer; transition: all 0.15s;">Batal</button>
                    <button type="button" id="btn-save-data-riil" onclick="saveDataRiil()"
                        style="padding: 9px 20px; border: none; background: #2563eb; color: #ffffff; border-radius: 9px; font-size: 13px; font-weight: 600; cursor: pointer; box-shadow: 0 2px 4px rgba(37,99,235,0.25); transition: all 0.15s; display: inline-flex; align-items: center; gap: 7px;">Simpan
                        Data Olahan</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit Identitas Mahasiswa (Nama, NIM, Fakultas, Kampus) -->
    <div class="modal-overlay" id="identity-modal" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.6); z-index: 1000; align-items: center; justify-content: center; padding: 16px;">
        <div style="background: #ffffff; border-radius: 14px; width: 100%; max-width: 480px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); overflow: hidden; border: 1px solid #e2e8f0;">
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-bottom: 1px solid #e2e8f0; background: #f8fafc;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div style="width: 32px; height: 32px; border-radius: 8px; background: #dbeafe; color: #2563eb; display: flex; align-items: center; justify-content: center;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </div>
                    <div>
                        <h3 style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 0;">Edit Identitas Mahasiswa</h3>
                        <p style="font-size: 12px; color: #64748b; margin: 0;">Data otomatis diterapkan ke Sampul, Pengesahan &amp; Pernyataan.</p>
                    </div>
                </div>
                <button type="button" onclick="closeEditIdentityModal()" style="border: none; background: transparent; font-size: 20px; color: #94a3b8; cursor: pointer;">&times;</button>
            </div>
            <div style="padding: 20px; display: flex; flex-direction: column; gap: 12px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px;">Nama Lengkap Mahasiswa</label>
                    <input type="text" id="id-modal-nama" class="form-input" style="width: 100%; padding: 8px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 8px;" placeholder="cth: MUH ALWI SYAHRIR">
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px;">NIM / NPM</label>
                    <input type="text" id="id-modal-nim" class="form-input" style="width: 100%; padding: 8px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 8px;" placeholder="cth: 210299">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px;">Fakultas</label>
                        <input type="text" id="id-modal-fakultas" class="form-input" style="width: 100%; padding: 8px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 8px;" placeholder="cth: Fakultas Ilmu Komputer">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px;">Program Studi</label>
                        <input type="text" id="id-modal-prodi" class="form-input" style="width: 100%; padding: 8px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 8px;" placeholder="cth: Teknik Informatika">
                    </div>
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px;">Nama Perguruan Tinggi / Kampus</label>
                    <input type="text" id="id-modal-kampus" class="form-input" style="width: 100%; padding: 8px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 8px;" placeholder="cth: UNIVERSITAS INDONESIA">
                </div>
            </div>
            <div style="padding: 12px 20px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" onclick="closeEditIdentityModal()" class="btn-top" style="padding: 0 16px; height: 36px;">Batal</button>
                <button type="button" onclick="saveStudentIdentityModal()" class="btn-top primary" id="btn-save-identity" style="padding: 0 18px; height: 36px; font-weight: 700;">Simpan Identitas</button>
            </div>
        </div>
    </div>

    <!-- Popover Interaktif Klik Sitasi (Buka Sumber / Ganti Sitasi Terverifikasi) -->
    <style>
        @keyframes citeSpinAnim { to { transform: rotate(360deg); } }
        @keyframes citePulseBar { 0% { transform: translateX(-100%); } 100% { transform: translateX(260%); } }
        .cite-spinner {
            width: 15px; height: 15px; border-radius: 50%;
            border: 2px solid rgba(37, 99, 235, 0.22);
            border-top-color: #2563eb;
            display: inline-block; flex-shrink: 0;
            animation: citeSpinAnim 0.7s linear infinite;
        }
        .cite-spinner-white {
            width: 14px; height: 14px; border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.35);
            border-top-color: #ffffff;
            display: inline-block; flex-shrink: 0;
            animation: citeSpinAnim 0.7s linear infinite;
        }
        .cite-progress-track {
            width: 100%; height: 4px; border-radius: 999px;
            background: #dbeafe; overflow: hidden; margin-top: 8px;
        }
        .cite-progress-fill {
            width: 42%; height: 100%; border-radius: 999px;
            background: linear-gradient(90deg, #2563eb, #0f766e);
            animation: citePulseBar 1.15s ease-in-out infinite;
        }
        .cite-active-sentence-hl {
            background: #fef08a;
            color: #0f172a;
            border-radius: 3px;
            padding: 1px 2px;
            box-decoration-break: clone;
            -webkit-box-decoration-break: clone;
        }
        .paper-editor p[data-split-cont="1"] {
            text-indent: 0 !important;
        }
    </style>
    <div id="cite-action-popover" onclick="event.stopPropagation();"
        style="display: none; position: fixed; z-index: 10050; width: 396px; max-width: calc(100vw - 24px); max-height: calc(100vh - 24px); flex-direction: column; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 14px; box-shadow: 0 20px 40px -10px rgba(15, 23, 42, 0.24); overflow: hidden; font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;">
        <div id="cite-pop-drag-handle" title="Tahan dan geser untuk memindahkan popup"
            style="padding: 11px 14px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; cursor: grab; user-select: none; flex-shrink: 0;">
            <div style="display: flex; align-items: flex-start; gap: 8px; min-width: 0; flex: 1;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" style="margin-top: 3px; flex-shrink: 0;"><circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/></svg>
                <div style="min-width: 0; flex: 1;">
                    <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                        <span id="cite-pop-label" style="font-size: 13px; font-weight: 700; color: #1e3a8a; word-break: break-word;">Sitasi</span>
                        <span id="cite-pop-rule" style="font-size: 10.5px; font-weight: 600; color: #2563eb; background: #dbeafe; padding: 1px 7px; border-radius: 999px;">APA 7th</span>
                    </div>
                    <div id="cite-pop-match" style="font-size: 11.5px; color: #334155; margin-top: 5px; line-height: 1.5; max-height: 115px; overflow-y: auto; padding-right: 4px; word-break: break-word;"></div>
                </div>
            </div>
            <button type="button" onclick="closeCitationPopover()" aria-label="Tutup"
                style="border: none; background: transparent; color: #64748b; cursor: pointer; padding: 2px; line-height: 1; font-size: 18px; flex-shrink: 0;">&times;</button>
        </div>

        <div style="overflow-y: auto; flex: 1; min-height: 0;">
            <div style="padding: 12px 14px; display: grid; grid-template-columns: 1fr 1fr; gap: 8px; background: #ffffff;">
                <button type="button" id="cite-pop-btn-open" onclick="handlePopoverOpenSource()"
                    style="display: inline-flex; align-items: center; justify-content: center; gap: 6px; height: 36px; border-radius: 9px; border: 1px solid #cbd5e1; background: #ffffff; color: #0f172a; font-size: 12.5px; font-weight: 600; cursor: pointer;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    <span>Buka Sumber</span>
                </button>
                <button type="button" id="cite-pop-btn-replace" onclick="toggleCitationReplacePanel()"
                    style="display: inline-flex; align-items: center; justify-content: center; gap: 6px; height: 36px; border-radius: 9px; border: none; background: #2563eb; color: #ffffff; font-size: 12.5px; font-weight: 600; cursor: pointer;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                    <span>Ganti Sitasi</span>
                </button>
            </div>

            <div id="cite-replace-panel" style="display: none; border-top: 1px solid #e2e8f0; background: #f8fafc; padding: 12px 14px;">
                <label style="display: flex; align-items: flex-start; gap: 7px; font-size: 11.5px; color: #334155; cursor: pointer; margin-bottom: 10px; line-height: 1.4;">
                    <input type="checkbox" id="cite-adjust-sentence" checked style="margin-top: 2px; accent-color: #2563eb;">
                    <span>Sesuaikan kalimat di sekitar sitasi agar cocok dengan jurnal terverifikasi pengganti (otomatis perbarui Daftar Pustaka).</span>
                </label>

                <button type="button" id="cite-btn-auto-replace" onclick="executeCitationReplace(null)"
                    style="width: 100%; height: 36px; border: none; border-radius: 9px; background: #0f766e; color: #ffffff; font-size: 12.5px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 6px; margin-bottom: 10px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                    <span>Ganti Otomatis Sesuai Pengaturan Sitasi</span>
                </button>

                <div style="display: flex; gap: 6px; margin-bottom: 8px;">
                    <input type="text" id="cite-search-input" placeholder="Cari topik jurnal pengganti lain..."
                        style="flex: 1; height: 32px; padding: 0 10px; font-size: 12px; border: 1px solid #cbd5e1; border-radius: 7px; background: #ffffff; color: #0f172a;"
                        onkeydown="if(event.key==='Enter'){event.preventDefault();loadCitationCandidates();}">
                    <button type="button" onclick="loadCitationCandidates()"
                        style="height: 32px; padding: 0 10px; border: 1px solid #cbd5e1; border-radius: 7px; background: #ffffff; color: #334155; font-size: 11.5px; font-weight: 600; cursor: pointer;">Cari</button>
                </div>

                <div id="cite-replace-loading" style="display: none; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 11px 12px; margin-bottom: 10px;">
                    <div style="display: flex; align-items: center; gap: 9px; font-size: 12px; font-weight: 600; color: #1e3a8a;">
                        <span class="cite-spinner"></span>
                        <span id="cite-replace-loading-text">Mengganti sitasi &amp; menyelaraskan kalimat...</span>
                    </div>
                    <div style="font-size: 11px; color: #475569; margin-top: 3px; padding-left: 24px;">Menyesuaikan dengan pengaturan sitasi &amp; memperbarui Daftar Pustaka otomatis.</div>
                    <div class="cite-progress-track"><div class="cite-progress-fill"></div></div>
                </div>

                <div id="cite-candidates-list" style="max-height: 210px; overflow-y: auto; display: flex; flex-direction: column; gap: 6px;"></div>
            </div>
        </div>
    </div>

    <div class="toast-wrap" id="toast-wrap"></div>

    <script>
        const projectId = @json($project->id);
        const projectTitle = @json($project->judul);
        let projectProdi = @json($project->prodi);
        const projectMeta = @json($meta ?? []);
        const defaultUserName = @json(auth()->user()->name ?? 'Mahasiswa');
        const isProposalMode = @json($isProposal);
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        let allowBab45 = !isProposalMode;
        let activeView = 'all';
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

        function selectChapterView(viewKey, options = {}) {
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
            } else if (viewKey === 'lampiran') {
                titleEl.textContent = 'LAMPIRAN PENELITIAN';
                genBtn.style.display = 'none';
                document.getElementById('btn-save-edit').style.display = 'none';
            } else if (viewKey === 'revisi') {
                titleEl.textContent = 'REVISI';
                genBtn.style.display = 'none';
                document.getElementById('btn-save-edit').style.display = 'none';
            } else {
                titleEl.textContent = chapterLabels[viewKey] || 'WORKSPACE';
                genBtn.style.display = 'inline-flex';
                const genLblEl = document.getElementById('btn-generate-current-label');
                const nextBtnLabel = viewKey === 'daftar_pustaka'
                    ? 'Rapikan Daftar Pustaka'
                    : ((chapterContents[viewKey] || '').trim() ? 'Susun Ulang Bab Ini' : 'Tulis Bab Ini');
                if (genLblEl) {
                    genLblEl.textContent = nextBtnLabel;
                } else {
                    genBtn.textContent = nextBtnLabel;
                }
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

            // Auto-scroll ke paling atas halaman saat navigasi bab diklik (kecuali saat preserveScroll aktif)
            const canvasScroll = document.querySelector('.canvas-scroll');
            if (canvasScroll && !options.preserveScroll) {
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

        function formatDaftarPustakaEntryHtml(rawLine) {
            if (!rawLine) return '';
            let s = escapeHtml(String(rawLine).trim());
            s = s.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
            s = s.replace(/\*([^*]+)\*/g, '<em>$1</em>');

            // Ubah URL penuh (https://doi.org/... atau https://...) menjadi link biru bisa diklik langsung di dalam editor
            s = s.replace(/(https?:\/\/[^\s<>"]+)/gi, (match) => {
                let url = match;
                let trailing = '';
                while (/[\.,;\)\]]$/.test(url)) {
                    trailing = url.slice(-1) + trailing;
                    url = url.slice(0, -1);
                }
                const cleanHref = url.replace(/&amp;/g, '&');
                return `<a href="${cleanHref}" target="_blank" rel="noopener noreferrer" contenteditable="false" class="academic-cite-badge dp-doi-link" title="Klik untuk membuka tautan DOI / Jurnal di tab baru" onclick="event.preventDefault(); event.stopPropagation(); window.open('${cleanHref.replace(/'/g, "\\'")}', '_blank', 'noopener,noreferrer');">${url}</a>${trailing}`;
            });

            // Ubah format "doi: 10.xxxx/..." yang belum memakai https:// menjadi link DOI klik
            s = s.replace(/(^|[\s(])doi:\s*(10\.\d{4,9}\/[^\s<>"]+)/gi, (match, pre, doiCode) => {
                let code = doiCode;
                let trailing = '';
                while (/[\.,;\)\]]$/.test(code)) {
                    trailing = code.slice(-1) + trailing;
                    code = code.slice(0, -1);
                }
                const href = 'https://doi.org/' + code.replace(/&amp;/g, '&');
                return `${pre}<a href="${href}" target="_blank" rel="noopener noreferrer" contenteditable="false" class="academic-cite-badge dp-doi-link" title="Klik untuk membuka DOI di tab baru" onclick="event.preventDefault(); event.stopPropagation(); window.open('${href.replace(/'/g, "\\'")}', '_blank', 'noopener,noreferrer');">doi:${code}</a>${trailing}`;
            });

            return s;
        }

        function formatInlineMarkdown(text, isTable = false) {
            if (!text) return '';
            let s = String(text);

            // 1. Bersihkan notasi matematika LaTeX agar tidak bocor kode mentah di naskah skripsi
            s = s.replace(/\\(left|right)\b/g, '');
            s = s.replace(/\\(text|mathrm|mathbf|mathit)\{([^{}]+)\}/g, '$1');
            s = s.replace(/\\sqrt\{([^{}]+)\}/g, '√($1)');
            s = s.replace(/\\sqrt/g, '√');
            s = s.replace(/\\hat\{([a-zA-Z])\}/g, '$1̂');
            s = s.replace(/\\hat\s*([a-zA-Z])/g, '$1̂');
            s = s.replace(/\\widehat\{([^{}]+)\}/g, '$1̂');
            while (/\\frac\{([^{}]+)\}\{([^{}]+)\}/.test(s)) {
                s = s.replace(/\\frac\{([^{}]+)\}\{([^{}]+)\}/g, '$1 / ($2)');
            }
            s = s.replace(/\{,\}/g, ',');
            s = s.replace(/\^2|\^\{2\}/g, '²');
            s = s.replace(/\^3|\^\{3\}/g, '³');
            s = s.replace(/\^t|\^\{t\}|\^T|\^\{T\}/g, 'ᵗ');
            s = s.replace(/\\times|\\cdot/g, '×');
            s = s.replace(/\\approx/g, '≈');
            s = s.replace(/\\le(?:q)?/g, '≤');
            s = s.replace(/\\ge(?:q)?/g, '≥');
            s = s.replace(/\\pm/g, '±');
            s = s.replace(/\\ne(?:q)?/g, '≠');
            s = s.replace(/\\alpha/g, 'α');
            s = s.replace(/\\beta/g, 'β');
            s = s.replace(/\\epsilon/g, 'ε');
            s = s.replace(/\\sigma/g, 'σ');
            s = s.replace(/\\mu/g, 'μ');
            s = s.replace(/\\theta/g, 'θ');
            s = s.replace(/\\lambda/g, 'λ');
            s = s.replace(/\\delta/g, 'δ');
            s = s.replace(/\\Delta/g, 'Δ');
            s = s.replace(/\\pi/g, 'π');
            s = s.replace(/\\infty/g, '∞');
            s = s.replace(/\\sum/g, '∑');
            s = s.replace(/\$\$([^$]+)\$\$/g, '$1');
            s = s.replace(/\$([^$]+)\$/g, '$1');
            s = s.replace(/\\([a-zA-Z]+)/g, '$1');

            let out = escapeHtml(s)
                .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.+?)\*/g, '<em>$1</em>');

            // 2. Format Sitasi Akademik Menjadi Hyperlink Alami yang Dapat Diklik Langsung
            // A. Sitasi Naratif: "Muflih dan Santosa (2023)", "Zuhdy (2024)", "Nambut et al. (2023)"
            out = out.replace(/\b([A-Z][a-zA-ZÀ-ÿ]+(?:\s+(?:et\s+al\.?|dan\s+[A-Z][a-zA-ZÀ-ÿ]+|&amp;\s+[A-Z][a-zA-ZÀ-ÿ]+))?)\s*\(((?:19\d{2}|20\d{2})(?:[a-z])?)\)/gu, (match, author, year) => {
                const authorClean = author.replace(/&amp;/g, '&').trim();
                const authorLower = authorClean.toLowerCase();
                if (['tabel', 'gambar', 'bagan', 'lampiran', 'bab', 'pasal', 'halaman', 'nomor'].includes(authorLower)) {
                    return match;
                }
                const labelSafe = escapeHtml(match.replace(/&amp;/g, '&'));
                const authorAttr = escapeHtml(authorClean);
                const yearAttr = escapeHtml(year);
                const cls = isTable ? 'table-cite-text' : 'academic-cite-badge cite-blue';
                return `<span class="${cls}" data-cite="${labelSafe}" data-author="${authorAttr}" data-year="${yearAttr}" onclick="openCitationLink(this, event)" title="Klik untuk Buka Sumber atau Ganti Sitasi">${labelSafe}</span>`;
            });

            // B. Sitasi dalam Kurung: "(Muflih & Santosa, 2023)", "(Nambut et al., 2023)", "(Zuhdy, 2024)"
            out = out.replace(/\(([A-Z][a-zA-ZÀ-ÿ\s\.\-&;]+(?:et\s+al\.?)?),?\s*((?:19\d{2}|20\d{2})(?:[a-z])?(?::\s*\d+|,?\s*(?:hlm|hal|p)\.?\s*\d+)?)\)/gu, (match, author, yearInfo) => {
                const authorClean = author.replace(/&amp;/g, '&').trim();
                const authorLower = authorClean.toLowerCase();
                if (['tabel', 'gambar', 'bagan', 'lampiran', 'bab', 'pasal', 'halaman', 'nomor'].includes(authorLower)) {
                    return match;
                }
                const year = (yearInfo.match(/\b(19\d{2}|20\d{2})\b/) || [])[1] || '';
                const labelSafe = escapeHtml(match.replace(/&amp;/g, '&'));
                const authorAttr = escapeHtml(authorClean);
                const yearAttr = escapeHtml(year);
                const cls = isTable ? 'table-cite-text' : 'academic-cite-badge cite-blue';
                return `<span class="${cls}" data-cite="${labelSafe}" data-author="${authorAttr}" data-year="${yearAttr}" onclick="openCitationLink(this, event)" title="Klik untuk Buka Sumber atau Ganti Sitasi">${labelSafe}</span>`;
            });

            return out;
        }

        let activeCitationCtx = null;
        let citationCandidatesCache = [];

        function findMatchedDpEntry(author, year) {
            const dpText = chapterContents.daftar_pustaka || '';
            const lines = dpText.split(/\n+/);
            const primaryAuthor = (author || '').split(/[\s,]+/)[0].replace(/[^a-zA-ZÀ-ÿ]/g, '');
            if (!primaryAuthor || primaryAuthor.length < 2) return '';
            const regex = new RegExp('\\b' + primaryAuthor + '\\b.*?' + (year ? '\\b' + year + '\\b' : ''), 'i');
            let matchedEntry = lines.find(line => regex.test(line)) || '';
            if (!matchedEntry && year) {
                matchedEntry = lines.find(line => line.toLowerCase().includes(primaryAuthor.toLowerCase()) && line.includes(year)) || '';
            }
            return matchedEntry;
        }

        function clearActiveSentenceHighlight() {
            document.querySelectorAll('mark.cite-active-sentence-hl').forEach((mk) => {
                const parent = mk.parentNode;
                if (!parent) return;
                while (mk.firstChild) parent.insertBefore(mk.firstChild, mk);
                parent.removeChild(mk);
                parent.normalize();
            });
        }

        function highlightSentenceForCitation(citeEl) {
            clearActiveSentenceHighlight();
            const container = citeEl.closest('p, li, td');
            if (!container) return null;

            // Kumpulkan seluruh text node + rentang seluruh sitasi di paragraf agar jika 1 kalimat majemuk punya >= 2 sitasi
            // (cth: "A (2025) ..., sementara B (2023) ..."), sorotan berhenti tepat di klausa milik sitasi yang diklik!
            const allCitesInContainer = Array.from(container.querySelectorAll('.academic-cite-badge, .table-cite-text'));
            const walker = document.createTreeWalker(container, NodeFilter.SHOW_TEXT, null);
            const nodes = [];
            let fullText = '';
            let citeStart = -1, citeEnd = -1;
            const otherCiteRanges = [];
            let n;
            while ((n = walker.nextNode())) {
                const start = fullText.length;
                const val = n.nodeValue || '';
                const end = start + val.length;
                nodes.push({ node: n, start, end });
                if (citeEl.contains(n)) {
                    if (citeStart === -1) citeStart = start;
                    citeEnd = end;
                } else {
                    const otherEl = allCitesInContainer.find(ce => ce !== citeEl && ce.contains(n));
                    if (otherEl) {
                        otherCiteRanges.push({ start, end });
                    }
                }
                fullText += val;
            }
            if (citeStart === -1) return null;

            // Cari awal kalimat sebelum citeStart (hindari memotong pada "et al." / "No." / "dkk.")
            let sentStart = 0;
            const before = fullText.slice(0, citeStart);
            const abbrTail = /(?:et\s+al|dkk|no|hlm|hal|dr|prof|st|mt|pd|uu|pp)$/i;
            for (let i = before.length - 1; i >= 0; i--) {
                const ch = before[i];
                if ((ch === '.' || ch === '!' || ch === '?') && (i + 1 < before.length && /\s/.test(before[i + 1]))) {
                    const prevToken = before.slice(Math.max(0, i - 10), i).trim();
                    if (!abbrTail.test(prevToken)) {
                        sentStart = i + 1;
                        while (sentStart < citeStart && /\s/.test(fullText[sentStart])) sentStart++;
                        break;
                    }
                }
            }

            // Cari akhir kalimat setelah citeEnd
            let sentEnd = fullText.length;
            for (let i = citeEnd; i < fullText.length; i++) {
                const ch = fullText[i];
                if ((ch === '.' || ch === '!' || ch === '?') && (i + 1 >= fullText.length || /\s/.test(fullText[i + 1]))) {
                    const prevToken = fullText.slice(Math.max(0, i - 10), i).trim();
                    if (!abbrTail.test(prevToken)) {
                        sentEnd = i + 1;
                        break;
                    }
                }
            }

            // Jika di dalam kalimat yang sama ada sitasi lain sebelum/sesudah sitasi yang diklik, potong sorotan pada batas klausa (koma/titik koma/konjungsi)
            const prevOther = otherCiteRanges.filter(r => r.end <= citeStart && r.start >= sentStart).sort((a, b) => b.end - a.end)[0];
            if (prevOther) {
                const between = fullText.slice(prevOther.end, citeStart);
                const mClause = between.match(/([,;]\s*(?:sementara|sedangkan|adapun|namun|akan\s+tetapi|berbeda\s+dengan|serta|dan)?\s*|\b(?:sementara|sedangkan|adapun)\s+)/i);
                if (mClause && mClause.index !== undefined) {
                    const cutPos = prevOther.end + mClause.index + (mClause[0].startsWith(',') || mClause[0].startsWith(';') ? 1 : 0);
                    sentStart = cutPos;
                    while (sentStart < citeStart && /\s/.test(fullText[sentStart])) sentStart++;
                } else {
                    sentStart = citeStart;
                }
            }
            const nextOther = otherCiteRanges.filter(r => r.start >= citeEnd && r.end <= sentEnd).sort((a, b) => a.start - b.start)[0];
            if (nextOther) {
                const between = fullText.slice(citeEnd, nextOther.start);
                const mClause = between.match(/([,;]\s*(?:sementara|sedangkan|adapun|namun|akan\s+tetapi|berbeda\s+dengan|serta|dan)?\s*|\b(?:sementara|sedangkan|adapun)\s+)/i);
                if (mClause && mClause.index !== undefined) {
                    sentEnd = citeEnd + mClause.index;
                } else {
                    sentEnd = nextOther.start;
                }
            }

            // Bungkus rentang teks kalimat dengan <mark class="cite-active-sentence-hl"> tanpa memutus elemen sitasi
            let lastMark = null;
            nodes.forEach((seg) => {
                if (seg.end <= sentStart || seg.start >= sentEnd) return;
                const localS = Math.max(0, sentStart - seg.start);
                const localE = Math.min(seg.node.nodeValue.length, sentEnd - seg.start);
                if (localE <= localS) return;
                try {
                    const rng = document.createRange();
                    rng.setStart(seg.node, localS);
                    rng.setEnd(seg.node, localE);
                    const mk = document.createElement('mark');
                    mk.className = 'cite-active-sentence-hl';
                    rng.surroundContents(mk);
                    lastMark = mk;
                } catch (e) {}
            });
            return lastMark ? lastMark.getBoundingClientRect() : null;
        }

        function openCitationLink(el, event) {
            if (event) {
                event.stopPropagation();
                event.preventDefault();
            }
            const citeText = el.getAttribute('data-cite') || el.innerText.trim();
            const author = el.getAttribute('data-author') || '';
            const year = el.getAttribute('data-year') || '';
            const sheetEl = el.closest('[data-chapter]');
            let chapterKey = sheetEl ? sheetEl.getAttribute('data-chapter') : activeView;
            if (!['bab1', 'bab2', 'bab3', 'bab4', 'bab5'].includes(chapterKey)) {
                chapterKey = ['bab1', 'bab2', 'bab3', 'bab4', 'bab5'].includes(activeView) ? activeView : 'bab1';
            }
            const paraEl = el.closest('p, li, td') || el.parentElement;
            const paragraph = paraEl ? paraEl.innerText.trim() : '';
            const matchedEntry = findMatchedDpEntry(author, year);

            // Auto-blok/sorot kalimat utuh dari sitasi yang diklik agar langsung terlihat konteks kalimatnya
            const hlRect = highlightSentenceForCitation(el);

            activeCitationCtx = { el, citeText, author, year, chapterKey, paragraph, matchedEntry };

            const pop = document.getElementById('cite-action-popover');
            const sitasiCfg = (projectMeta && projectMeta.sitasi) ? projectMeta.sitasi : {};
            const gaya = sitasiCfg.gaya || 'APA 7th';
            const thMin = sitasiCfg.tahun_min || '2023';
            const asal = sitasiCfg.asal || 'Indonesia + Internasional';

            document.getElementById('cite-pop-label').textContent = citeText;
            document.getElementById('cite-pop-rule').textContent = `${gaya} · ≥ ${thMin}`;
            const matchBox = document.getElementById('cite-pop-match');
            if (matchedEntry) {
                matchBox.innerHTML = `<span style="color:#047857; font-weight:700;">Terdaftar di Daftar Pustaka:</span> ${escapeHtml(matchedEntry)}`;
            } else {
                matchBox.innerHTML = `<span style="color:#b45309; font-weight:700;">Belum ada di Daftar Pustaka</span> · Pengaturan: ${escapeHtml(asal)}.`;
            }

            const repPanel = document.getElementById('cite-replace-panel');
            repPanel.style.display = 'none';
            document.getElementById('cite-search-input').value = '';
            document.getElementById('cite-candidates-list').innerHTML = '';

            // Tampilkan dan posisikan popover tepat di bawah kalimat yang disorot agar kalimatnya tetap terlihat jelas
            pop.style.display = 'flex';
            const anchorRect = hlRect || el.getBoundingClientRect();
            const popW = Math.min(396, window.innerWidth - 24);
            let left = el.getBoundingClientRect().left;
            if (left + popW > window.innerWidth - 12) {
                left = Math.max(12, window.innerWidth - popW - 12);
            }
            let top = anchorRect.bottom + 10;
            pop.style.left = `${left}px`;
            pop.style.top = `${top}px`;
            clampCitationPopoverToViewport();
        }

        function clampCitationPopoverToViewport() {
            const pop = document.getElementById('cite-action-popover');
            if (!pop || pop.style.display === 'none') return;
            const pr = pop.getBoundingClientRect();
            let left = pr.left;
            let top = pr.top;
            if (left + pr.width > window.innerWidth - 12) {
                left = Math.max(12, window.innerWidth - pr.width - 12);
            }
            if (left < 12) left = 12;
            if (top + pr.height > window.innerHeight - 12) {
                top = Math.max(12, window.innerHeight - pr.height - 12);
            }
            if (top < 12) top = 12;
            pop.style.left = `${left}px`;
            pop.style.top = `${top}px`;
        }

        // Aktifkan geser (drag & drop) pada header popover sitasi (Desktop Mouse & Touch)
        (function initCitationPopoverDrag() {
            const handle = document.getElementById('cite-pop-drag-handle');
            const pop = document.getElementById('cite-action-popover');
            if (!handle || !pop) return;
            let dragging = false, offsetX = 0, offsetY = 0;

            const startDrag = (clientX, clientY) => {
                dragging = true;
                const r = pop.getBoundingClientRect();
                offsetX = clientX - r.left;
                offsetY = clientY - r.top;
                handle.style.cursor = 'grabbing';
            };
            const moveDrag = (clientX, clientY) => {
                if (!dragging) return;
                const r = pop.getBoundingClientRect();
                const maxL = Math.max(12, window.innerWidth - r.width - 12);
                const maxT = Math.max(12, window.innerHeight - r.height - 12);
                const nextL = Math.min(maxL, Math.max(12, clientX - offsetX));
                const nextT = Math.min(maxT, Math.max(12, clientY - offsetY));
                pop.style.left = `${nextL}px`;
                pop.style.top = `${nextT}px`;
            };
            const endDrag = () => {
                if (!dragging) return;
                dragging = false;
                handle.style.cursor = 'grab';
            };

            handle.addEventListener('mousedown', (e) => {
                if (e.target.closest('button')) return;
                e.preventDefault();
                startDrag(e.clientX, e.clientY);
            });
            document.addEventListener('mousemove', (e) => moveDrag(e.clientX, e.clientY));
            document.addEventListener('mouseup', endDrag);

            handle.addEventListener('touchstart', (e) => {
                if (e.target.closest('button') || !e.touches[0]) return;
                startDrag(e.touches[0].clientX, e.touches[0].clientY);
            }, { passive: true });
            document.addEventListener('touchmove', (e) => {
                if (!dragging || !e.touches[0]) return;
                moveDrag(e.touches[0].clientX, e.touches[0].clientY);
            }, { passive: true });
            document.addEventListener('touchend', endDrag);
        })();

        let isReplacingCitation = false;

        function closeCitationPopover(force = false) {
            if (isReplacingCitation && !force) return;
            const pop = document.getElementById('cite-action-popover');
            if (pop) pop.style.display = 'none';
            clearActiveSentenceHighlight();
        }

        document.addEventListener('click', (e) => {
            const pop = document.getElementById('cite-action-popover');
            if (!pop || pop.style.display === 'none' || isReplacingCitation) return;
            if (!document.body.contains(e.target)) return;
            if (!pop.contains(e.target) && !e.target.closest('.academic-cite-badge, .table-cite-text')) {
                closeCitationPopover();
            }
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !isReplacingCitation) {
                closeCitationPopover();
            }
        });
        window.addEventListener('resize', () => {
            const pop = document.getElementById('cite-action-popover');
            if (!pop || pop.style.display === 'none') return;
            const pr = pop.getBoundingClientRect();
            const centeredLeft = Math.max(12, Math.round((window.innerWidth - pr.width) / 2));
            const maxTop = Math.max(12, window.innerHeight - pr.height - 12);
            const nextTop = Math.min(maxTop, Math.max(12, pr.top));
            pop.style.left = `${centeredLeft}px`;
            pop.style.top = `${nextTop}px`;
        });

        function handlePopoverOpenSource() {
            if (!activeCitationCtx) return;
            const { citeText, author, year, matchedEntry } = activeCitationCtx;
            closeCitationPopover();
            proceedOpenCitationSource(citeText, author, year, matchedEntry);
        }

        function toggleCitationReplacePanel() {
            const repPanel = document.getElementById('cite-replace-panel');
            if (!repPanel) return;
            const willOpen = repPanel.style.display === 'none';
            repPanel.style.display = willOpen ? 'block' : 'none';
            if (willOpen) {
                clampCitationPopoverToViewport();
                loadCitationCandidates();
            }
        }

        async function loadCitationCandidates() {
            if (!activeCitationCtx) return;
            const listEl = document.getElementById('cite-candidates-list');
            const query = (document.getElementById('cite-search-input')?.value || '').trim();
            listEl.innerHTML = '<div style="padding: 14px 10px; display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 12px; color: #334155; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 9px;"><span class="cite-spinner"></span><span>Mencari jurnal terverifikasi sesuai pengaturan sitasimu...</span></div>';

            try {
                const res = await fetch(`/project/${projectId}/citation/replace`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'candidates',
                        chapter: activeCitationCtx.chapterKey,
                        old_cite: activeCitationCtx.citeText,
                        old_author: activeCitationCtx.author,
                        old_year: activeCitationCtx.year,
                        paragraph: activeCitationCtx.paragraph,
                        query
                    })
                });
                const data = await res.json();
                if (!res.ok || data.status !== 'success') {
                    throw new Error(data.message || 'Gagal memuat kandidat jurnal.');
                }
                citationCandidatesCache = data.candidates || [];
                if (!citationCandidatesCache.length) {
                    listEl.innerHTML = '<div style="padding: 10px; text-align: center; font-size: 12px; color: #b45309; background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px;">Belum ada kandidat cocok. Coba ketik kata kunci topik yang lebih umum di kotak pencarian.</div>';
                    return;
                }
                listEl.innerHTML = citationCandidatesCache.map((c, idx) => {
                    const isId = (c.scope || '').toLowerCase().includes('indo');
                    const badge = isId
                        ? '<span style="font-size:10px; font-weight:700; color:#1d4ed8; background:#dbeafe; padding:1px 6px; border-radius:999px;">Indonesia</span>'
                        : '<span style="font-size:10px; font-weight:700; color:#047857; background:#d1fae5; padding:1px 6px; border-radius:999px;">Internasional</span>';
                    return `
                        <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:9px; padding:9px 10px; display:flex; flex-direction:column; gap:5px;">
                            <div style="display:flex; justify-content:space-between; align-items:center; gap:6px;">
                                <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                                    <strong style="font-size:12px; color:#0f172a;">${escapeHtml(c.in_text || '')} (${escapeHtml(String(c.year || ''))})</strong>
                                    ${badge}
                                </div>
                                <button type="button" onclick="executeCitationReplace(${idx})"
                                    style="border:none; background:#2563eb; color:#ffffff; font-size:11px; font-weight:600; padding:4px 10px; border-radius:6px; cursor:pointer; flex-shrink:0;">Pakai</button>
                            </div>
                            <div style="font-size:11.5px; color:#334155; line-height:1.35;">${escapeHtml((c.title || '').slice(0, 125))}${(c.title || '').length > 125 ? '…' : ''}</div>
                            <div style="font-size:10.5px; color:#64748b;">${escapeHtml(c.venue || '')}${c.doi ? ' · DOI Terverifikasi' : ''}</div>
                        </div>
                    `;
                }).join('');
                clampCitationPopoverToViewport();
            } catch (err) {
                listEl.innerHTML = `<div style="padding: 10px; font-size: 12px; color: #b91c1c; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px;">${escapeHtml(err.message || 'Gagal memuat kandidat.')}</div>`;
            }
        }

        async function executeCitationReplace(candidateIdx) {
            if (!activeCitationCtx || isReplacingCitation) return;
            isReplacingCitation = true;
            const autoBtn = document.getElementById('cite-btn-auto-replace');
            const loadingBox = document.getElementById('cite-replace-loading');
            const loadingTxt = document.getElementById('cite-replace-loading-text');
            const listEl = document.getElementById('cite-candidates-list');
            const origAutoHtml = autoBtn ? autoBtn.innerHTML : '';

            const chosenRef = (candidateIdx !== null && candidateIdx !== undefined)
                ? (citationCandidatesCache[candidateIdx] || null)
                : (citationCandidatesCache[0] || null);
            const adjustSentence = Boolean(document.getElementById('cite-adjust-sentence')?.checked);
            const query = (document.getElementById('cite-search-input')?.value || '').trim();

            if (autoBtn) {
                autoBtn.disabled = true;
                autoBtn.style.opacity = '0.78';
                autoBtn.innerHTML = '<span class="cite-spinner-white"></span><span>Sedang Mengganti Sitasi...</span>';
            }
            if (loadingBox) {
                loadingBox.style.display = 'block';
                if (loadingTxt) {
                    const targetLabel = chosenRef ? `${chosenRef.in_text} (${chosenRef.year})` : 'jurnal terverifikasi baru';
                    loadingTxt.textContent = adjustSentence
                        ? `Mengganti ke ${targetLabel} & menyelaraskan kalimat...`
                        : `Mengganti sitasi ke ${targetLabel}...`;
                }
            }
            if (listEl) {
                listEl.querySelectorAll('button').forEach((b) => { b.disabled = true; b.style.opacity = '0.55'; });
            }
            clampCitationPopoverToViewport();

            try {
                const res = await fetch(`/project/${projectId}/citation/replace`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'replace',
                        chapter: activeCitationCtx.chapterKey,
                        old_cite: activeCitationCtx.citeText,
                        old_author: activeCitationCtx.author,
                        old_year: activeCitationCtx.year,
                        paragraph: activeCitationCtx.paragraph,
                        chosen_ref: chosenRef,
                        adjust_sentence: adjustSentence,
                        query
                    })
                });
                const data = await res.json();
                if (!res.ok || data.status !== 'success') {
                    throw new Error(data.message || 'Gagal mengganti sitasi.');
                }

                const canvasScroll = document.querySelector('.canvas-scroll');
                const prevScrollTop = canvasScroll ? canvasScroll.scrollTop : 0;
                const prevWindowY = window.scrollY || window.pageYOffset || 0;
                const prevAnchorTop = activeCitationCtx.el ? activeCitationCtx.el.getBoundingClientRect().top : null;
                const replacedChapter = data.chapter;
                const replacedCite = (data.new_cite || '').trim();

                chapterContents[data.chapter] = data.updated_chapter_content;
                if (typeof data.updated_daftar_pustaka === 'string') {
                    chapterContents.daftar_pustaka = data.updated_daftar_pustaka;
                }
                isReplacingCitation = false;
                closeCitationPopover(true);
                selectChapterView(activeView, { preserveScroll: true });
                updateProgressUI();

                // Pertahankan posisi baca yang sama tanpa mental ke paling atas, lalu sorot sitasi pengganti
                if (canvasScroll) canvasScroll.scrollTop = prevScrollTop;
                if (prevWindowY > 0) window.scrollTo(0, prevWindowY);

                requestAnimationFrame(() => {
                    if (canvasScroll) canvasScroll.scrollTop = prevScrollTop;
                    if (prevWindowY > 0) window.scrollTo(0, prevWindowY);

                    const scopeRoot = document.querySelector(`.paper-editor[data-chapter="${replacedChapter}"], [data-chapter="${replacedChapter}"]`)?.closest('#sheets-container') || document.getElementById('sheets-container');
                    if (scopeRoot && replacedCite) {
                        const chapterNodes = Array.from(scopeRoot.querySelectorAll(`[data-chapter="${replacedChapter}"] .academic-cite-badge, .paper-editor[data-chapter="${replacedChapter}"] .academic-cite-badge, .academic-cite-badge`));
                        const matches = chapterNodes.filter((b) => {
                            const sheet = b.closest('[data-chapter]');
                            if (sheet && sheet.getAttribute('data-chapter') !== replacedChapter) return false;
                            const txt = (b.getAttribute('data-cite') || b.innerText || '').trim();
                            return txt === replacedCite || txt.includes(replacedCite);
                        });
                        if (matches.length) {
                            let targetBadge = matches[0];
                            if (prevAnchorTop !== null) {
                                targetBadge = matches.reduce((best, cur) => {
                                    const dCur = Math.abs(cur.getBoundingClientRect().top - prevAnchorTop);
                                    const dBest = Math.abs(best.getBoundingClientRect().top - prevAnchorTop);
                                    return dCur < dBest ? cur : best;
                                }, matches[0]);
                            }
                            const rect = targetBadge.getBoundingClientRect();
                            if (rect.top < 72 || rect.bottom > window.innerHeight - 60) {
                                targetBadge.scrollIntoView({ block: 'center', behavior: 'auto' });
                            }
                            targetBadge.style.transition = 'background-color 0.4s ease, box-shadow 0.4s ease';
                            targetBadge.style.backgroundColor = '#dcfce7';
                            targetBadge.style.boxShadow = '0 0 0 3px rgba(22, 163, 74, 0.35)';
                            targetBadge.style.borderRadius = '4px';
                            setTimeout(() => {
                                if (document.body.contains(targetBadge)) {
                                    targetBadge.style.backgroundColor = '';
                                    targetBadge.style.boxShadow = '';
                                }
                            }, 2400);
                        }
                    }
                });

                showToast(`Sitasi diganti menjadi ${data.new_cite} & Daftar Pustaka otomatis diperbarui!`, 'success');
            } catch (err) {
                showToast(err.message || 'Gagal mengganti sitasi.', 'error');
            } finally {
                isReplacingCitation = false;
                if (autoBtn) {
                    autoBtn.disabled = false;
                    autoBtn.style.opacity = '';
                    autoBtn.innerHTML = origAutoHtml;
                }
                if (loadingBox) loadingBox.style.display = 'none';
                if (listEl) {
                    listEl.querySelectorAll('button').forEach((b) => { b.disabled = false; b.style.opacity = ''; });
                }
            }
        }

        function proceedOpenCitationSource(citeText, author, year, matchedEntry) {
            const fullRaw = (citeText + ' ' + author + ' ' + year).toLowerCase();

            // 1. Pemetaan langsung laporan otoritas statistik & lembaga resmi nasional / global
            if (fullRaw.includes('we are social') || fullRaw.includes('meltwater') || fullRaw.includes('datareportal')) {
                window.open('https://datareportal.com/reports/digital-2024-indonesia', '_blank', 'noopener,noreferrer');
                return;
            }
            if (fullRaw.includes('bps') || fullRaw.includes('badan pusat statistik')) {
                window.open('https://www.bps.go.id', '_blank', 'noopener,noreferrer');
                return;
            }
            if (fullRaw.includes('apjii')) {
                window.open('https://apjii.or.id/survei', '_blank', 'noopener,noreferrer');
                return;
            }
            if (fullRaw.includes('kemendikbud') || fullRaw.includes('kemdikbud') || fullRaw.includes('bskap')) {
                window.open('https://kemdikbud.go.id', '_blank', 'noopener,noreferrer');
                return;
            }

            const primaryAuthor = (author || '').split(/[\s,]+/)[0].replace(/[^a-zA-ZÀ-ÿ]/g, '');
            let directUrl = '';
            let pubTitle = '';

            if (matchedEntry) {
                const urlMatch = matchedEntry.match(/(https?:\/\/[^\s]+)/i);
                if (urlMatch) {
                    directUrl = urlMatch[1].replace(/[.,;)]+$/, '');
                }
                const cleanEntry = matchedEntry.replace(/^[\s\d\.\-\*\[\]]+/, '').trim();
                const afterYear = cleanEntry.replace(/^.*?\((?:19|20)\d{2}[a-z]?\)\.\s*/i, '');
                if (afterYear && afterYear !== cleanEntry) {
                    const parts = afterYear.split(/\.\s+/);
                    pubTitle = parts[0] ? parts[0].trim().replace(/^["']|["']$/g, '') : '';
                }
            }

            if (directUrl && directUrl.startsWith('http')) {
                window.open(directUrl, '_blank', 'noopener,noreferrer');
                return;
            }

            const searchKeyword = (pubTitle && pubTitle.length >= 10) ? pubTitle : `${primaryAuthor || author} ${year}`;
            const cleanQuery = (pubTitle && pubTitle.length >= 10) ? `"${pubTitle}"` : `"${author || primaryAuthor}" ${year || ''}`.trim();
            const scholarFallbackUrl = `https://scholar.google.com/scholar?q=${encodeURIComponent(cleanQuery)}`;

            const targetWin = window.open('about:blank', '_blank');
            if (targetWin) {
                try {
                    targetWin.document.write('<html><head><title>Membuka Jurnal Ilmiah Asli...</title><style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;background:#f8fafc;color:#334155;text-align:center;}</style></head><body><div><div style="font-size:16px;font-weight:600;margin-bottom:8px;">Menghubungkan ke Repositori Jurnal Ilmiah Resmi...</div><div style="font-size:13px;color:#64748b;">Mencari DOI terverifikasi untuk kutipan: <i>' + escapeHtml(author || citeText) + '</i></div></div></body></html>');
                } catch (e) {}
            }

            fetch(`https://api.crossref.org/works?query.bibliographic=${encodeURIComponent(searchKeyword)}&rows=1`)
                .then(res => res.json())
                .then(data => {
                    const item = data?.message?.items?.[0];
                    const resolvedUrl = (item && item.DOI)
                        ? `https://doi.org/${item.DOI}`
                        : ((item && item.URL) ? item.URL : scholarFallbackUrl);
                    if (targetWin && !targetWin.closed) {
                        targetWin.location.href = resolvedUrl;
                    } else {
                        window.open(resolvedUrl, '_blank', 'noopener,noreferrer');
                    }
                })
                .catch(() => {
                    if (targetWin && !targetWin.closed) {
                        targetWin.location.href = scholarFallbackUrl;
                    } else {
                        window.open(scholarFallbackUrl, '_blank', 'noopener,noreferrer');
                    }
                });
        }

        function openAuditModal() {
            const modal = document.getElementById('modal-audit-dosen');
            if (!modal) return;
            modal.style.display = 'flex';
            runProjectAudit();
        }

        function closeAuditModal() {
            const modal = document.getElementById('modal-audit-dosen');
            if (modal) modal.style.display = 'none';
        }

        async function runProjectAudit() {
            const body = document.getElementById('audit-modal-body');
            if (!body) return;
            body.innerHTML = '<div style="padding: 28px; text-align: center; color: #64748b; font-size: 13px;">Memeriksa konsistensi Bab I–V, sitasi in-text, dan Daftar Pustaka...</div>';

            try {
                const res = await fetch(`/project/${projectId}/audit`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({})
                });
                const data = await res.json();
                if (!res.ok || data.status !== 'success') {
                    throw new Error(data.message || 'Gagal menjalankan audit.');
                }

                const score = Number(data.score || 0);
                const toneColor = score >= 88 ? '#047857' : (score >= 70 ? '#b45309' : '#b91c1c');
                const toneBg = score >= 88 ? '#ecfdf5' : (score >= 70 ? '#fffbeb' : '#fef2f2');
                const toneBorder = score >= 88 ? '#a7f3d0' : (score >= 70 ? '#fde68a' : '#fecaca');
                const cfg = data.citation_settings || {};

                const checksHtml = (data.checks || []).map((ck) => {
                    const isPass = ck.status === 'pass';
                    const isFail = ck.status === 'fail';
                    const iconSvg = isPass
                        ? '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>'
                        : (isFail
                            ? '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>'
                            : '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>');
                    const cardBorder = isPass ? '#d1fae5' : (isFail ? '#fecaca' : '#fde68a');
                    const cardBg = isPass ? '#f8fafc' : (isFail ? '#fef2f2' : '#fffbeb');
                    const detailsHtml = (ck.details || []).length
                        ? `<ul style="margin:6px 0 0 18px; padding:0; font-size:12px; color:#475569; line-height:1.5;">${ck.details.map(d => `<li>${escapeHtml(d)}</li>`).join('')}</ul>`
                        : '';
                    const actionBtn = ck.action
                        ? `<div style="margin-top:8px;"><button type="button" onclick="handleAuditAction('${escapeHtml(ck.action.type)}', '${escapeHtml(ck.action.target)}')" style="border:1px solid #bfdbfe; background:#eff6ff; color:#1d4ed8; font-size:11.5px; font-weight:600; padding:5px 11px; border-radius:7px; cursor:pointer;">${escapeHtml(ck.action.label)}</button></div>`
                        : '';

                    return `
                        <div style="border:1px solid ${cardBorder}; background:${cardBg}; border-radius:12px; padding:12px 14px; display:flex; gap:10px; align-items:flex-start;">
                            <div style="margin-top:2px; flex-shrink:0;">${iconSvg}</div>
                            <div style="flex:1; min-width:0;">
                                <div style="font-size:13px; font-weight:700; color:#0f172a;">${escapeHtml(ck.title)}</div>
                                <div style="font-size:12.5px; color:#334155; margin-top:2px; line-height:1.45;">${escapeHtml(ck.summary)}</div>
                                ${detailsHtml}
                                ${actionBtn}
                            </div>
                        </div>
                    `;
                }).join('');

                body.innerHTML = `
                    <div style="display:flex; justify-content:space-between; align-items:center; gap:14px; flex-wrap:wrap; padding:14px 16px; border-radius:13px; background:${toneBg}; border:1px solid ${toneBorder};">
                        <div>
                            <div style="font-size:11.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.04em; color:${toneColor};">Skor Kesiapan Bimbingan</div>
                            <div style="font-size:15px; font-weight:700; color:#0f172a; margin-top:2px;">${escapeHtml(data.grade_label || '')}</div>
                            <div style="font-size:11.5px; color:#475569; margin-top:3px;">Pengaturan Form: <strong>${escapeHtml(cfg.gaya || 'APA 7th')}</strong> · Tahun ≥ <strong>${escapeHtml(String(cfg.tahun_min || '2023'))}</strong> · ${escapeHtml(cfg.asal || '')}</div>
                        </div>
                        <div style="font-size:30px; font-weight:800; color:${toneColor}; line-height:1;">${score}<span style="font-size:14px; font-weight:600;">/100</span></div>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:9px;">${checksHtml}</div>
                `;
            } catch (err) {
                body.innerHTML = `<div style="padding:16px; border-radius:10px; background:#fef2f2; border:1px solid #fecaca; color:#b91c1c; font-size:13px;">${escapeHtml(err.message || 'Gagal menjalankan audit.')}</div>`;
            }
        }

        async function handleAuditAction(type, target) {
            if (type === 'sync_dp') {
                closeAuditModal();
                selectChapterView('daftar_pustaka');
                if (typeof generateCurrentView === 'function') {
                    generateCurrentView();
                }
                return;
            }
            if (type === 'olah_data') {
                closeAuditModal();
                if (typeof openDataRiilModal === 'function') {
                    openDataRiilModal();
                }
                return;
            }
            if (type === 'revise' || type === 'chapter') {
                closeAuditModal();
                selectChapterView(target || 'bab1');
            }
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
            let tableRawLines = [];
            let pendingTableCaption = '';
            let pendingFigureCaption = '';

            const flushLists = () => {
                if (inUl) { html += '</ul>'; inUl = false; }
                if (inOl) { html += '</ol>'; inOl = false; }
            };
            const flushTable = () => {
                if (!inTable || tableRows.length === 0) {
                    if (pendingTableCaption) {
                        html += pendingTableCaption;
                        pendingTableCaption = '';
                    }
                    inTable = false;
                    tableRows = [];
                    tableRawLines = [];
                    return;
                }
                const cleanRows = tableRows.filter((cols, idx) => !(idx === 1 && cols.every((c) => /^[:\-\s]+$/.test(c))));
                // Tabel valid WAJIB memiliki minimal 2 baris nyata (header + minimal 1 baris data);
                // baris tunggal yang kebetulan mengandung simbol matematika |V| atau P(x|c) dikembalikan sebagai paragraf biasa!
                if (cleanRows.length < 2) {
                    if (pendingTableCaption) {
                        html += pendingTableCaption;
                        pendingTableCaption = '';
                    }
                    tableRawLines.forEach((rawL) => {
                        html += `<p>${formatInlineMarkdown(rawL)}</p>`;
                    });
                    inTable = false;
                    tableRows = [];
                    tableRawLines = [];
                    return;
                }
                const maxCols = Math.max(...cleanRows.map(r => r.length));
                if (cleanRows[0].length < maxCols) {
                    while (cleanRows[0].length < maxCols) cleanRows[0].unshift('Kategori');
                }
                let tHtml = '<div class="table-wrap"><table class="academic-table">';
                cleanRows.forEach((rawCols, idx) => {
                    const cols = [...rawCols];
                    while (cols.length < maxCols) cols.push('-');
                    const tag = idx === 0 ? 'th' : 'td';
                    if (idx === 0) tHtml += '<thead>';
                    if (idx === 1) tHtml += '<tbody>';
                    tHtml += '<tr>' + cols.slice(0, maxCols).map((c, colIdx) => {
                        const trimmed = c.trim();
                        const isNum = /^[0-9\.,\s\(\)\%\-\/<>]+$/.test(trimmed) && trimmed.length < 22;
                        const align = idx === 0 ? 'center' : ((colIdx === 0 && /^\d+$/.test(trimmed)) || isNum ? 'center' : 'left');
                        return `<${tag} style="text-align:${align};">${formatInlineMarkdown(c, true)}</${tag}>`;
                    }).join('') + '</tr>';
                    if (idx === 0) tHtml += '</thead>';
                });
                if (cleanRows.length > 1) tHtml += '</tbody>';
                tHtml += '</table></div>';
                if (pendingTableCaption) {
                    html += `<div class="academic-table-container" style="page-break-inside: avoid; margin: 14px 0 18px;">${pendingTableCaption}${tHtml}</div>`;
                    pendingTableCaption = '';
                } else {
                    html += tHtml;
                }
                inTable = false;
                tableRows = [];
                tableRawLines = [];
            };

            lines.forEach((raw) => {
                const line = raw.trim();

                // Handle Code Blocks / Flowcharts / Architecture Diagrams
                if (line.startsWith('```')) {
                    flushLists();
                    flushTable();
                    if (inCode) {
                        const diagHtml = renderAcademicDiagramHtml(codeBuffer.trim());
                        const figCap = pendingFigureCaption
                            ? `<p class="academic-caption" style="text-indent:0; font-weight:700; text-align:center; margin:6px 0 16px; font-size:11pt; color:#000000;">${pendingFigureCaption}</p>`
                            : '';
                        pendingFigureCaption = '';
                        html += `<div style="page-break-inside: avoid;">${diagHtml}${figCap}</div>`;
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
                // Hindari salah deteksi paragraf yang berisi notasi kardinalitas |V|, |D|, atau probabilitas bersyarat P(t|c)
                const strippedMathPipes = line.replace(/\|[A-Za-z0-9_,\s+\-]{1,8}\|/g, ' ').replace(/[A-Za-z]\([^)]*\|[^)]*\)/g, ' ');
                const isMdTableRow = (line.startsWith('|') && line.endsWith('|') && (line.match(/\|/g) || []).length >= 3)
                    || (line.startsWith('|') && (strippedMathPipes.match(/\|/g) || []).length >= 2)
                    || ((strippedMathPipes.match(/\s\|\s/g) || []).length >= 2);
                if (isMdTableRow) {
                    flushLists();
                    inTable = true;
                    tableRawLines.push(line);
                    const inner = line.replace(/^\|/, '').replace(/\|$/, '');
                    const cells = inner.split('|').map((c) => c.trim());
                    tableRows.push(cells);
                    return;
                } else if (inTable) {
                    flushTable();
                }

                if (pendingFigureCaption && !/^(?:Gambar|Bagan)\s+\d+\.\d+/i.test(line)) {
                    html += `<p class="academic-caption" style="text-indent:0; font-weight:700; text-align:center; margin:12px 0 6px; font-size:11pt; color:#000000;">${pendingFigureCaption}</p>`;
                    pendingFigureCaption = '';
                }

                if (isDaftarPustaka) {
                    flushLists();
                    if (/^#+\s*/.test(line)) {
                        html += `<h2>${formatInlineMarkdown(line.replace(/^#+\s*/, ''))}</h2>`;
                    } else {
                        html += `<p class="dp-entry">${formatDaftarPustakaEntryHtml(line)}</p>`;
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
                } else if (/^Sumber\s*:/i.test(normLine)) {
                    flushLists();
                    flushTable();
                    html += `<p style="text-indent:0; font-style:italic; font-size:10pt; margin:-8px 0 12px; color:#334155;">${formatInlineMarkdown(normLine)}</p>`;
                } else if (/^Tabel\s+\d+\.\d+/i.test(normLine) && normLine.length <= 130 && !/menunjukkan bahwa|dapat dilihat|memperlihatkan/i.test(normLine)) {
                    flushLists();
                    flushTable();
                    pendingTableCaption = `<p class="academic-caption" style="text-indent:0; font-weight:700; text-align:left; margin:12px 0 4px; font-size:11pt; color:#000000;">${formatInlineMarkdown(normLine)}</p>`;
                } else if (/^(?:Gambar|Bagan)\s+\d+\.\d+/i.test(normLine) && normLine.length <= 130 && !/menunjukkan bahwa|dapat dilihat|memperlihatkan/i.test(normLine)) {
                    flushLists();
                    flushTable();
                    pendingFigureCaption = formatInlineMarkdown(normLine);
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

        function adjustDiagramScale(btn, delta) {
            const container = btn.closest('.academic-figure-container');
            if (!container) return;
            const inner = container.querySelector('.academic-diagram-scalable');
            const label = container.querySelector('.scale-label');
            if (!inner) return;
            let curScale = parseInt(inner.getAttribute('data-scale') || '100', 10);
            if (delta === 0) {
                curScale = 100;
            } else {
                curScale = Math.max(60, Math.min(160, curScale + delta));
            }
            inner.setAttribute('data-scale', curScale);
            inner.style.transform = `scale(${curScale / 100})`;
            inner.style.transformOrigin = 'top center';
            inner.style.transition = 'transform 0.15s ease';
            if (label) label.textContent = `${curScale}%`;
        }

        function renderAcademicDiagramHtml(rawCode) {
            if (!rawCode || !rawCode.trim()) return '';
            const clean = rawCode.trim();
            const isFlowchartLike = clean.includes('-->') || clean.includes('->') || clean.includes('↓') || /\[.+?\]/.test(clean) || /mulai|flowchart|prosedur|dataset|preprocessing|kerangka/i.test(clean);
            if (!isFlowchartLike) {
                return `<div class="academic-diagram-box" style="margin: 18px 0; padding: 14px 18px; background: #ffffff; border: 1.5px solid #000000; font-family: 'Times New Roman', serif; font-size: 11pt; line-height: 1.5; white-space: pre-wrap; overflow-x: auto; color: #000000;">${escapeHtml(clean)}</div>`;
            }

            const projJudul = (typeof projectTitle !== 'undefined' && projectTitle) ? projectTitle.trim() : 'Topik Penelitian';
            const sanitizedClean = clean
                .replace(/\[\s*(?:SIMBOL\s+DATA|DATA\s*\/\s*INPUT|SUB-PROSES|PREDEFINED\s+PROCESS|PROSES\s+SISTEM|PROSES|KEPUTUSAN|DECISION|TERMINATOR)[^\]]*\]/gi, '')
                .replace(/\[\/\s*|\s*\/\]/g, '');

            let nodes = [];
            const matches = [...sanitizedClean.matchAll(/\[([\s\S]*?)\]/g)];
            if (matches.length >= 2) {
                nodes = matches.map(m => m[1].replace(/^[+\-=_|~#\s]+|[+\-=_|~#\s]+$/g, '').trim())
                    .filter(t => t.length >= 2 && !/^[+\-=_|~#\s]+$/.test(t));
            }
            if (nodes.length < 2) {
                const rawLines = sanitizedClean.split(/\n|↓|-->|->/);
                for (let l of rawLines) {
                    let t = l.replace(/^[-*•\d\.\s\>Vv↓|]+/, '').replace(/[|]+$/, '').replace(/\[|\]/g, '').trim();
                    if (t.length >= 4 && !/^[+\-=_|~#\s]+$/.test(t) && !/^\+[-+]+\+$/.test(t) && !t.toLowerCase().startsWith('gambar') && !t.toLowerCase().startsWith('bagan')) {
                        nodes.push(t);
                    }
                }
            }
            nodes = nodes.filter(n => {
                const lower = n.toLowerCase().trim();
                if (/^(?:simbol\s+data|data\s*\/\s*input|sub-proses|predefined\s+process|proses\s+sistem|proses|keputusan|decision|terminator)$/i.test(lower)) return false;
                return lower.length >= 2;
            });

            const hasStartEnd = nodes.some(n => /^(mulai|start|selesai|finish|end)$/i.test(n.trim())) || /mulai|flowchart|prosedur/i.test(clean);
            const isFramework = !hasStartEnd && (/kerangka|bagan|konseptual|pemikiran|kondisi\s*awal|tindakan|permasalahan|landasan\s*teori/i.test(clean) || nodes.length <= 4);

            const arrowHtml = `
                <div style="display: flex; flex-direction: column; align-items: center; margin: 2px 0;">
                    <div style="width: 1.5px; height: 16px; background: #000000;"></div>
                    <div style="width: 0; height: 0; border-left: 5px solid transparent; border-right: 5px solid transparent; border-top: 6px solid #000000;"></div>
                </div>
            `;

            // A. BAGAN KERANGKA BERPIKIR / KONSEPTUAL (BAB II)
            if (isFramework) {
                if (nodes.length < 2) {
                    nodes = [
                        'Kondisi Empiris: Permasalahan nyata dan kesenjangan fenomena terkait ' + projJudul,
                        'Landasan Teoretis: Kajian grand theory, dimensi variabel, dan sintesis riset terdahulu',
                        'Pendekatan Metodologis: Pengumpulan data empiris, uji validitas instrumen, dan analisis data terstruktur',
                        'Luaran Penelitian: Pembuktian hipotesis ilmiah dan rekomendasi solutif'
                    ];
                }
                let frameworkContent = '';
                nodes.forEach((nodeText, idx) => {
                    let cleanLabel = nodeText.replace(/^[\(\[\/\s\-—]+|[\)\]\/\s\-—]+$/g, '').trim();
                    frameworkContent += `
                        <div style="width: 100%; max-width: 520px; border: 1.5px solid #000000; background: #ffffff; padding: 10px 16px; text-align: center; box-sizing: border-box;">
                            <div style="font-size: 10.5pt; line-height: 1.35; color: #000000;">${escapeHtml(cleanLabel)}</div>
                        </div>
                    `;
                    if (idx < nodes.length - 1) frameworkContent += arrowHtml;
                });

                return `
                    <div class="academic-figure-container" style="margin: 18px auto; max-width: 640px; background: transparent; padding: 8px 12px; text-align: center; page-break-inside: avoid; font-family: 'Times New Roman', serif;">
                        <div class="academic-diagram-scalable" style="display: flex; flex-direction: column; align-items: center; gap: 0; width: 100%;">
                            ${frameworkContent}
                        </div>
                    </div>
                `;
            }

            // B. FLOWCHART PROSEDUR PENELITIAN STANDAR ANSI (BAB III)
            if (nodes.length < 3) {
                nodes = [
                    'Mulai',
                    'Identifikasi Masalah & Kajian Literatur: ' + projJudul,
                    'Pengumpulan Data & Instrumen Penelitian',
                    'Pra-Pemrosesan Data & Uji Instrumen',
                    'Analisis Data & Pemodelan',
                    'Evaluasi Kinerja & Pengujian Hipotesis',
                    'Penyusunan Laporan Hasil & Rekomendasi',
                    'Selesai'
                ];
            }

            const decIdx = nodes.length >= 5
                ? nodes.findIndex((n, i) => i > 1 && i < nodes.length - 1 && /evaluasi|confusion|akurasi|validasi|memenuhi|uji\s*hipotesis|kelayakan|layak|\?/i.test(n))
                : -1;

            let flowContent = '';
            nodes.forEach((nodeText, idx) => {
                let cleanLabel = nodeText.replace(/^[\(\[\/\s\-—]+|[\)\]\/\s\-—]+$/g, '').trim();
                cleanLabel = cleanLabel.replace(/\[\s*(PROSES SISTEM|SIMBOL DATA|SUB-PROSES|EVALUASI|PREDEFINED PROCESS)[^\]]*\]/gi, '').trim();
                cleanLabel = cleanLabel.replace(/^(\s*───\s*|\s*—\s*)+|(\s*───\s*|\s*—\s*)+$/g, '').trim();

                const isFirst = idx === 0 || /^(mulai|start)$/i.test(cleanLabel);
                const isLast = idx === nodes.length - 1 || /^(selesai|finish|end)$/i.test(cleanLabel);
                const isDecision = idx === decIdx;
                const isData = !isFirst && !isLast && !isDecision && /dataset|pengumpulan|input|output|korpus|kuesioner|angket|wawancara|observasi|scraping|crawling|dokumentasi|laporan\s+hasil/i.test(cleanLabel);
                const isSubProcess = !isFirst && !isLast && !isDecision && !isData && /pra-pemrosesan|preprocessing|sub-proses|ekstraksi\s+fitur|pembobotan|tf-idf|uji\s+coba|uji\s+validitas|uji\s+reliabilitas|uji\s+prasyarat|uji\s+asumsi|triangulasi|perancangan\s+sistem/i.test(cleanLabel);

                if (isFirst || isLast) {
                    // TERMINATOR: Kapsul Oval
                    flowContent += `
                        <div style="display: inline-block; min-width: 150px; padding: 7px 32px; border: 1.8px solid #000000; border-radius: 999px; background: #ffffff; font-weight: 700; font-size: 10.5pt; font-family: 'Times New Roman', serif; text-align: center; text-transform: uppercase; color: #000000;">
                            ${escapeHtml(cleanLabel || (isFirst ? 'MULAI' : 'SELESAI'))}
                        </div>
                    `;
                } else if (isData) {
                    // DATA / INPUT-OUTPUT: Jajar Genjang (Parallelogram)
                    flowContent += `
                        <div style="display: inline-block; width: 360px; max-width: 90%; padding: 8px 22px; border: 1.5px solid #000000; background: #ffffff; transform: skewX(-14deg); box-sizing: border-box;">
                            <div style="transform: skewX(14deg); font-family: 'Times New Roman', serif; font-size: 10.5pt; text-align: center; line-height: 1.35; color: #000000;">
                                ${escapeHtml(cleanLabel)}
                            </div>
                        </div>
                    `;
                } else if (isSubProcess) {
                    // PROSES / SUB-PROSES: Kotak tunggal bersih tanpa garis ganda vertikal agar rapi & seragam dengan Word
                    flowContent += `
                        <div style="width: 360px; max-width: 92%; padding: 8px 18px; border: 1.5px solid #000000; background: #ffffff; font-family: 'Times New Roman', serif; font-size: 10.5pt; text-align: center; line-height: 1.35; color: #000000; box-sizing: border-box;">
                            ${escapeHtml(cleanLabel)}
                        </div>
                    `;
                } else if (isDecision) {
                    // DECISION: Belah Ketupat SVG asli dengan label Ya / Tidak
                    flowContent += `
                        <div style="position: relative; width: 100%; max-width: 500px; margin: 2px auto; display: flex; justify-content: center; align-items: center;">
                            <div style="position: relative; width: 250px; height: 92px; display: flex; align-items: center; justify-content: center;">
                                <svg width="250" height="92" viewBox="0 0 250 92" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;">
                                    <polygon points="125,2 248,46 125,90 2,46" fill="#ffffff" stroke="#000000" stroke-width="1.6"/>
                                </svg>
                                <div style="position: relative; z-index: 2; font-family: 'Times New Roman', serif; font-size: 9.5pt; text-align: center; line-height: 1.25; padding: 0 34px; color: #000000;">
                                    ${escapeHtml(cleanLabel)}
                                </div>
                            </div>
                            <div style="position: absolute; right: 24px; top: 34px; font-size: 9.5pt; font-weight: 700; font-family: 'Times New Roman', serif; color: #000000;">Tidak → Ulangi</div>
                        </div>
                    `;
                } else {
                    // PROSES STANDAR: Persegi Panjang
                    flowContent += `
                        <div style="width: 360px; max-width: 92%; padding: 8px 18px; border: 1.5px solid #000000; background: #ffffff; font-family: 'Times New Roman', serif; font-size: 10.5pt; text-align: center; line-height: 1.35; color: #000000; box-sizing: border-box;">
                            ${escapeHtml(cleanLabel)}
                        </div>
                    `;
                }

                if (idx < nodes.length - 1) {
                    if (isDecision) {
                        flowContent += `<div style="font-size: 9.5pt; font-weight: 700; font-family: 'Times New Roman', serif; color: #000000; line-height: 1;">Ya</div>`;
                    }
                    flowContent += arrowHtml;
                }
            });

            return `
                <div class="academic-figure-container" style="margin: 18px auto; max-width: 640px; background: transparent; padding: 8px 12px; text-align: center; page-break-inside: avoid; font-family: 'Times New Roman', serif;">
                    <div class="academic-diagram-scalable" style="display: flex; flex-direction: column; align-items: center; gap: 0; width: 100%;">
                        ${flowContent}
                    </div>
                </div>
            `;
        }

        const PREVIEW_CHAPTER_HEADERS = {
            'bab1': { num: 'BAB I', title: 'PENDAHULUAN' },
            'bab2': { num: 'BAB II', title: 'TINJAUAN PUSTAKA' },
            'bab3': { num: 'BAB III', title: 'METODOLOGI PENELITIAN' },
            'bab4': { num: 'BAB IV', title: 'HASIL PENELITIAN DAN PEMBAHASAN' },
            'bab5': { num: 'BAB V', title: 'PENUTUP' },
            'daftar_pustaka': { num: '', title: 'DAFTAR PUSTAKA' }
        };

        function buildChapterFullHtml(chapterKey) {
            const raw = (chapterContents[chapterKey] || '').trim();
            if (!raw) return '';
            const curH = PREVIEW_CHAPTER_HEADERS[chapterKey] || { num: '', title: chapterLabels[chapterKey] || 'DOKUMEN' };
            let bodyHtml = markdownToAcademicHtml(raw, chapterKey === 'daftar_pustaka');
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
            return bodyHtml;
        }

        function splitBodyHtmlToPages(fullBodyHtml) {
            if (!fullBodyHtml || !fullBodyHtml.trim()) return [];
            try {
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = fullBodyHtml;

                const blocks = Array.from(tempDiv.children);
                if (!blocks.length) {
                    return [fullBodyHtml];
                }

                // Kalibrasi presisi 1:1 sesuai halaman A4 Microsoft Word & PDF (Times New Roman 12pt, 1,5 spasi, margin 4-4-3-3 cm):
                // ~84 karakter per baris, ~33.5 baris isi per halaman A4 penuh.
                const CHARS_PER_LINE = 84;
                const MAX_LINES_PER_PAGE = 33.5;
                const estimateBlockLines = (block) => {
                    const textLen = (block.innerText || block.textContent || '').replace(/\s+/g, ' ').trim().length;
                    const tagName = (block.tagName || '').toLowerCase();
                    if (block.classList && block.classList.contains('academic-chapter-header')) {
                        return 3.2;
                    }
                    if (tagName === 'h1' || tagName === 'h2') {
                        return 2.4;
                    }
                    if (tagName === 'h3' || tagName === 'h4') {
                        return 1.8;
                    }
                    if (block.classList && block.classList.contains('academic-figure-container')) {
                        return 12.5;
                    }
                    if (tagName === 'table' || (block.classList && block.classList.contains('table-wrap')) || (block.querySelector && block.querySelector('table'))) {
                        const rows = block.querySelectorAll ? block.querySelectorAll('tr').length : 4;
                        return Math.min(24, Math.max(5.5, rows * 1.45 + 2.2));
                    }
                    if (block.querySelector && block.querySelector('img')) {
                        return 12.0;
                    }
                    if (tagName === 'ul' || tagName === 'ol') {
                        const items = Array.from(block.querySelectorAll ? block.querySelectorAll('li') : []);
                        if (items.length) {
                            return items.reduce((sum, li) => {
                                const len = (li.innerText || li.textContent || '').trim().length;
                                return sum + Math.max(1, Math.ceil(len / 76)) + 0.25;
                            }, 0.6);
                        }
                        return Math.max(2, Math.ceil(textLen / 76) + 0.6);
                    }
                    if (block.classList && block.classList.contains('dp-entry')) {
                        return Math.max(1.5, Math.ceil(textLen / 78)) + 0.45;
                    }
                    return Math.max(1, Math.ceil((textLen + 8) / CHARS_PER_LINE)) + 0.4;
                };

                const pages = [];
                let curPageBlocks = [];
                let curLines = 0;

                // Pecah paragraf HTML yang meluap di akhir halaman pada batas kalimat (seperti aliran halaman asli Word & PDF)
                const splitParagraphHtmlAtLines = (pEl, targetLines) => {
                    const html = pEl.innerHTML;
                    const plain = (pEl.innerText || pEl.textContent || '').replace(/\s+/g, ' ').trim();
                    const targetChars = Math.max(140, Math.floor((targetLines - 0.4) * CHARS_PER_LINE));
                    if (plain.length <= targetChars + 90) return null;

                    // Cari posisi titik akhir kalimat terdekat <= targetChars di dalam HTML (aman di luar tag <...>)
                    let inTag = false;
                    let visibleCount = 0;
                    let bestSplitHtmlIdx = -1;
                    for (let i = 0; i < html.length; i++) {
                        const ch = html[i];
                        if (ch === '<') { inTag = true; continue; }
                        if (ch === '>') { inTag = false; continue; }
                        if (inTag) continue;
                        visibleCount++;
                        if ((ch === '.' || ch === '!' || ch === '?') && (i + 1 < html.length && /\s/.test(html[i + 1]))) {
                            const beforeChunk = html.slice(Math.max(0, i - 12), i).replace(/<[^>]+>/g, '').trim();
                            if (!/(?:et\s+al|dkk|no|hlm|hal|dr|prof)$/i.test(beforeChunk)) {
                                if (visibleCount <= targetChars + 40 && visibleCount >= 110) {
                                    bestSplitHtmlIdx = i + 1;
                                } else if (visibleCount > targetChars + 40) {
                                    break;
                                }
                            }
                        }
                    }
                    if (bestSplitHtmlIdx === -1) return null;

                    const headHtml = html.slice(0, bestSplitHtmlIdx).trim();
                    const tailHtml = html.slice(bestSplitHtmlIdx).trim();
                    if (!headHtml || !tailHtml) return null;

                    // Pastikan tidak memotong di dalam tag terbuka (mis. <span> sitasi)
                    const openSpans = (headHtml.match(/<span\b/gi) || []).length;
                    const closeSpans = (headHtml.match(/<\/span>/gi) || []).length;
                    if (openSpans !== closeSpans) return null;

                    const pHead = document.createElement('p');
                    pHead.setAttribute('data-split-head', '1');
                    pHead.innerHTML = headHtml;
                    const pTail = document.createElement('p');
                    pTail.setAttribute('data-split-cont', '1');
                    pTail.innerHTML = tailHtml;
                    return [pHead, pTail];
                };

                blocks.forEach((block) => {
                    const blockLines = estimateBlockLines(block);
                    const tagName = (block.tagName || '').toLowerCase();
                    const isPlainPara = tagName === 'p' && (!block.classList || (!block.classList.contains('academic-caption') && !block.classList.contains('dp-entry')));

                    if (curPageBlocks.length > 0 && (curLines + blockLines > MAX_LINES_PER_PAGE + 1.5)) {
                        const remainingLines = MAX_LINES_PER_PAGE - curLines;
                        // Jika masih tersisa >= 2.5 baris di halaman ini dan blok adalah paragraf biasa, alirkan kalimat awalnya ke halaman ini (cocok 1:1 dengan Word & PDF!)
                        if (isPlainPara && remainingLines >= 2.5) {
                            const splitPair = splitParagraphHtmlAtLines(block, remainingLines);
                            if (splitPair) {
                                curPageBlocks.push(splitPair[0]);
                                pages.push(curPageBlocks.map(b => b.outerHTML).join(''));
                                curPageBlocks = [splitPair[1]];
                                curLines = estimateBlockLines(splitPair[1]);
                                return;
                            }
                        }

                        const lastBlock = curPageBlocks[curPageBlocks.length - 1];
                        const lastTag = (lastBlock.tagName || '').toLowerCase();
                        const isLastCaption = lastBlock.classList && lastBlock.classList.contains('academic-caption');
                        const isLastHeading = (lastTag === 'h2' || lastTag === 'h3' || lastTag === 'h4') || (lastBlock.classList && lastBlock.classList.contains('academic-chapter-header'));

                        if ((isLastHeading || isLastCaption) && curPageBlocks.length > 1) {
                            const orphanEl = curPageBlocks.pop();
                            const orphanLines = estimateBlockLines(orphanEl);
                            pages.push(curPageBlocks.map(b => b.outerHTML).join(''));
                            curPageBlocks = [orphanEl, block];
                            curLines = orphanLines + blockLines;
                        } else {
                            pages.push(curPageBlocks.map(b => b.outerHTML).join(''));
                            curPageBlocks = [block];
                            curLines = blockLines;
                        }
                    } else {
                        curPageBlocks.push(block);
                        curLines += blockLines;
                    }
                });

                if (curPageBlocks.length > 0) {
                    pages.push(curPageBlocks.map(b => b.outerHTML).join(''));
                }

                return pages.length ? pages : [fullBodyHtml];
            } catch (e) {
                console.error('Error splitBodyHtmlToPages:', e);
                return [fullBodyHtml];
            }
        }

        function findPageNumberInChapterPages(chPageInfo, searchNeedle, fallbackPage) {
            if (!chPageInfo || !Array.isArray(chPageInfo.pages) || !chPageInfo.pages.length) {
                return fallbackPage;
            }
            const norm = (s) => String(s || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim().toLowerCase();
            const needle = norm(searchNeedle);
            if (needle) {
                for (let i = 0; i < chPageInfo.pages.length; i++) {
                    if (norm(chPageInfo.pages[i]).includes(needle)) {
                        return chPageInfo.startPage + i;
                    }
                }
            }
            return Math.min(Math.max(fallbackPage, chPageInfo.startPage), chPageInfo.endPage);
        }

        function computeDocumentPagination() {
            const pageMap = {};
            let curPage = 1;

            const chapterKeys = allowBab45
                ? ['bab1', 'bab2', 'bab3', 'bab4', 'bab5', 'daftar_pustaka']
                : ['bab1', 'bab2', 'bab3', 'daftar_pustaka'];

            chapterKeys.forEach(k => {
                const bodyHtml = buildChapterFullHtml(k);
                if (bodyHtml) {
                    const pages = splitBodyHtmlToPages(bodyHtml);
                    const count = Math.max(1, pages.length);
                    pageMap[k] = {
                        startPage: curPage,
                        pageCount: count,
                        endPage: curPage + count - 1,
                        pages: pages
                    };
                    curPage += count;
                } else {
                    pageMap[k] = {
                        startPage: curPage,
                        pageCount: 0,
                        endPage: curPage - 1,
                        pages: []
                    };
                }
            });

            // Halaman pertama untuk Lampiran dimulai tepat setelah halaman terakhir bab/daftar pustaka (tanpa lompat!)
            pageMap._nextPageAfterMain = curPage;
            return pageMap;
        }

        function extractTablesForDocument(existingPageMap) {
            const list = [];
            const pageMap = existingPageMap || computeDocumentPagination();
            const chapters = [
                { key: 'bab1', num: 1 },
                { key: 'bab2', num: 2 },
                { key: 'bab3', num: 3 },
                { key: 'bab4', num: 4 },
                { key: 'bab5', num: 5 }
            ];

            chapters.forEach(ch => {
                if (!allowBab45 && (ch.key === 'bab4' || ch.key === 'bab5')) return;
                const text = (chapterContents[ch.key] || '').trim();
                const chInfo = pageMap[ch.key];
                if (!text || !chInfo || chInfo.pageCount === 0) return;

                const lines = text.split('\n');
                let inCode = false;

                lines.forEach((line, lIdx) => {
                    const trimmed = line.trim();
                    if (trimmed.startsWith('```')) {
                        inCode = !inCode;
                        return;
                    }
                    if (inCode) return;

                    const capMatch = trimmed.match(/^(?:\*{1,2}|<strong[^>]*>)?Tabel\s+(\d+\.\d+[\w\.\-]*)\s*[:\-\s]*(.*?)(?:\*{1,2}|<\/strong>)?$/i);
                    if (capMatch) {
                        const rawTitle = capMatch[2].replace(/^[:\-\s]+/, '').trim();
                        // Abaikan jika ini kalimat pembahasan/analisis teks di dalam paragraf
                        if (rawTitle.length > 80 || /memperlihatkan|menunjukkan|dapat dilihat|mengindikasikan|menyajikan|mereduksi|berkontribusi|berdasarkan tabel|terlihat pada|membuktikan|menjelaskan|menerangkan|diperoleh|dihasilkan/i.test(rawTitle)) {
                            return;
                        }
                        const num = capMatch[1];
                        const title = rawTitle || `Tabel ${num}`;
                        const fallbackPg = chInfo.startPage + Math.floor((lIdx / Math.max(1, lines.length)) * chInfo.pageCount);
                        const exactPage = findPageNumberInChapterPages(chInfo, `Tabel ${num}`, fallbackPg);
                        list.push({ num: num, title: `Tabel ${num} ${title}`, page: exactPage });
                    }
                });
            });

            return list;
        }

        function extractFiguresForDocument(existingPageMap) {
            const list = [];
            const pageMap = existingPageMap || computeDocumentPagination();
            const chapters = [
                { key: 'bab1', num: 1 },
                { key: 'bab2', num: 2 },
                { key: 'bab3', num: 3 },
                { key: 'bab4', num: 4 },
                { key: 'bab5', num: 5 }
            ];

            chapters.forEach(ch => {
                if (!allowBab45 && (ch.key === 'bab4' || ch.key === 'bab5')) return;
                const text = (chapterContents[ch.key] || '').trim();
                const chInfo = pageMap[ch.key];
                if (!text || !chInfo || chInfo.pageCount === 0) return;

                if (ch.key === 'bab2' && (text.includes('Kerangka') || text.includes('kerangka') || text.includes('-->') || text.includes('```'))) {
                    const exactPg = findPageNumberInChapterPages(chInfo, 'Gambar 2.1', chInfo.endPage);
                    list.push({ num: '2.1', title: 'Gambar 2.1 Bagan Kerangka Pemikiran Penelitian', page: exactPg });
                }

                if (ch.key === 'bab3' && (text.includes('Flowchart') || text.includes('flowchart') || text.includes('Alur') || text.includes('-->') || text.includes('```'))) {
                    const exactPg = findPageNumberInChapterPages(chInfo, 'Gambar 3.1', chInfo.startPage + Math.min(2, chInfo.pageCount - 1));
                    list.push({ num: '3.1', title: 'Gambar 3.1 Diagram Alur (Flowchart) Prosedur Penelitian', page: exactPg });
                }

                const lines = text.split('\n');
                lines.forEach((line, lIdx) => {
                    const m = line.trim().match(/^Gambar\s+(\d+\.\d+[\w\.\-]*)\s*(.*)$/i);
                    if (m) {
                        const num = m[1];
                        if (num !== '2.1' && num !== '3.1') {
                            const title = m[2].replace(/^[:\-\s]+/, '').trim() || `Gambar ${num}`;
                            const fallbackPg = chInfo.startPage + Math.floor((lIdx / Math.max(1, lines.length)) * chInfo.pageCount);
                            const exactPg = findPageNumberInChapterPages(chInfo, `Gambar ${num}`, fallbackPg);
                            list.push({ num: num, title: `Gambar ${num} ${title}`, page: exactPg });
                        }
                    }
                });
            });

            return list;
        }

        function buildCoverAndFrontMatterHtml(existingPageMap) {
            const pageMap = existingPageMap || computeDocumentPagination();
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

            const toRoman = (n) => {
                const map = [['x', 10], ['ix', 9], ['v', 5], ['iv', 4], ['i', 1]];
                let res = '';
                let num = n;
                for (const [r, v] of map) {
                    while (num >= v) { res += r; num -= v; }
                }
                return res;
            };

            const daftarTabelList = extractTablesForDocument(pageMap);
            const daftarGambarList = extractFiguresForDocument(pageMap);

            // Helper paginasi Daftar Isi / Daftar Tabel / Daftar Gambar / Daftar Lampiran per halaman A4 agar tidak menumpuk di 1 halaman!
            const paginateFrontListRows = (rows, firstPageMaxUnits = 20, nextPageMaxUnits = 23) => {
                if (!rows || !rows.length) return [];
                const chunks = [];
                let cur = [];
                let units = 0;
                rows.forEach((row) => {
                    const labelLen = String(row.title || row.label || '').length;
                    const rowUnits = labelLen > 52 ? 1.7 : (row.isMain ? 1.15 : 1.0);
                    const limit = chunks.length === 0 ? firstPageMaxUnits : nextPageMaxUnits;
                    if (cur.length > 0 && (units + rowUnits > limit)) {
                        chunks.push(cur);
                        cur = [row];
                        units = rowUnits;
                    } else {
                        cur.push(row);
                        units += rowUnits;
                    }
                });
                if (cur.length > 0) chunks.push(cur);
                return chunks;
            };

            const tocRows = [];
            let romanCur = 2;
            tocRows.push({ title: 'HALAMAN JUDUL', page: 'i', isMain: true, isSub: false });
            const pgPengesahan = toRoman(romanCur++);
            tocRows.push({ title: allowBab45 ? 'LEMBAR PENGESAHAN' : 'LEMBAR PERSETUJUAN PROPOSAL', page: pgPengesahan, isMain: true, isSub: false });

            let pgPernyataan = '', pgAbstrak = '', pgAbstract = '';
            if (allowBab45) {
                pgPernyataan = toRoman(romanCur++);
                pgAbstrak = toRoman(romanCur++);
                pgAbstract = toRoman(romanCur++);
                tocRows.push({ title: 'SURAT PERNYATAAN KEASLIAN', page: pgPernyataan, isMain: true, isSub: false });
                tocRows.push({ title: 'ABSTRAK', page: pgAbstrak, isMain: true, isSub: false });
                tocRows.push({ title: 'ABSTRACT', page: pgAbstract, isMain: true, isSub: false });
            }

            const pgKataPengantar = toRoman(romanCur++);
            const tocStartRomanIdx = romanCur;
            const pgDaftarIsi = toRoman(tocStartRomanIdx);
            tocRows.push({ title: 'KATA PENGANTAR', page: pgKataPengantar, isMain: true, isSub: false });
            tocRows.push({ title: 'DAFTAR ISI', page: pgDaftarIsi, isMain: true, isSub: false });

            // Placeholder entri Daftar Tabel/Gambar/Lampiran (nomor romawinya diperbarui setelah jumlah halaman Daftar Isi diketahui)
            const dtEntry = daftarTabelList.length > 0 ? { title: 'DAFTAR TABEL', page: '', isMain: true, isSub: false } : null;
            if (dtEntry) tocRows.push(dtEntry);
            const dgEntry = daftarGambarList.length > 0 ? { title: 'DAFTAR GAMBAR', page: '', isMain: true, isSub: false } : null;
            if (dgEntry) tocRows.push(dgEntry);

            const lampiranList = getLampiranWithPages(pageMap);
            const dlEntry = lampiranList.length > 0 ? { title: 'DAFTAR LAMPIRAN', page: '', isMain: true, isSub: false } : null;
            if (dlEntry) tocRows.push(dlEntry);

            chaptersDef.forEach((ch) => {
                if (!allowBab45 && (ch.key === 'bab4' || ch.key === 'bab5')) return;
                const text = (chapterContents[ch.key] || '').trim();
                const chInfo = pageMap[ch.key];
                if (!text || !chInfo || chInfo.pageCount === 0) return;

                const chPage = chInfo.startPage;
                tocRows.push({ title: ch.title, page: String(chPage), isMain: true, isSub: false });

                const lines = text.split('\n');
                let subCount = 0;
                lines.forEach((raw, lIdx) => {
                    const line = raw.trim();
                    const m = line.match(/^(?:##\s+)?(\d+\.\d+\.?\s+[^\n]+)/) ||
                        line.match(/^(?:##\s+)?([A-Z]\.\s+[A-Z][^\n]+)/);
                    if (m && m[1]) {
                        const clean = m[1].replace(/^##\s+/, '').trim();
                        if (clean.length >= 4 && clean.length <= 80) {
                            subCount++;
                            const fallbackPg = chPage + Math.floor((lIdx / Math.max(1, lines.length)) * chInfo.pageCount);
                            const subPage = findPageNumberInChapterPages(chInfo, clean, fallbackPg);
                            tocRows.push({ title: clean, page: String(subPage), isMain: false, isSub: true });
                        }
                    }
                });

                if (subCount === 0) {
                    defaultSubchapters.forEach((item, idx) => {
                        if (item.chapter === ch.key && item.code) {
                            const subPage = Math.min(chInfo.endPage, chPage + Math.floor(idx / 2));
                            tocRows.push({ title: item.label, page: String(subPage), isMain: false, isSub: true });
                            subCount++;
                        }
                    });
                }
            });

            const dpContent = (chapterContents.daftar_pustaka || '').trim();
            if (dpContent && pageMap.daftar_pustaka) {
                tocRows.push({ title: 'DAFTAR PUSTAKA', page: String(pageMap.daftar_pustaka.startPage), isMain: true, isSub: false });
            }

            if (lampiranList.length > 0) {
                tocRows.push({ title: 'LAMPIRAN', page: String(lampiranList[0].page), isMain: true, isSub: false });
            }

            // Bagi Daftar Isi, Daftar Tabel, Daftar Gambar, dan Daftar Lampiran ke lembar A4 masing-masing (28 baris per halaman persis seperti Word & PDF)
            const tocPages = paginateFrontListRows(tocRows, 28, 32);
            const dtPages = paginateFrontListRows(daftarTabelList, 27, 31);
            const dgPages = paginateFrontListRows(daftarGambarList, 27, 31);
            const dlPages = paginateFrontListRows(lampiranList, 27, 31);

            let romanTrack = tocStartRomanIdx + Math.max(1, tocPages.length);
            const dtStartRoman = romanTrack;
            if (dtEntry) {
                dtEntry.page = toRoman(dtStartRoman);
                romanTrack += dtPages.length;
            }
            const dgStartRoman = romanTrack;
            if (dgEntry) {
                dgEntry.page = toRoman(dgStartRoman);
                romanTrack += dgPages.length;
            }
            const dlStartRoman = romanTrack;
            if (dlEntry) {
                dlEntry.page = toRoman(dlStartRoman);
                romanTrack += dlPages.length;
            }

            const renderTocSheetsHtml = () => tocPages.map((pageRows, idx) => {
                const rowsHtml = pageRows.map((r) => {
                    const cls = r.isSub ? 'toc-row sub' : 'toc-row main';
                    const pageSpan = r.page ? `<span class="toc-page">${escapeHtml(r.page)}</span>` : '';
                    return `<div class="${cls}"><span>${escapeHtml(r.title)}</span><span class="toc-dots"></span>${pageSpan}</div>`;
                }).join('');
                const pgLabel = toRoman(tocStartRomanIdx + idx);
                return `
        <div class="a4-sheet front-body" contenteditable="true" spellcheck="false" style="display: flex; flex-direction: column; justify-content: flex-start; min-height: 1123px; position: relative;">
            <div>
                ${idx === 0 ? '<div class="front-heading">DAFTAR ISI</div>' : ''}
                <div class="toc-list">${rowsHtml}</div>
            </div>
            <div class="sheet-footer">${pgLabel}</div>
        </div>`;
            }).join('');

            const renderNamedListSheetsHtml = (heading, pagesArr, startRomanIdx, labelKey) => pagesArr.map((pageRows, idx) => {
                const rowsHtml = pageRows.map((item) => `<div class="toc-row main"><span>${escapeHtml(item[labelKey] || '')}</span><span class="toc-dots"></span><span class="toc-page">${escapeHtml(String(item.page || ''))}</span></div>`).join('');
                const pgLabel = toRoman(startRomanIdx + idx);
                return `
        <div class="a4-sheet front-body" contenteditable="true" spellcheck="false" style="display: flex; flex-direction: column; justify-content: flex-start; min-height: 1123px; position: relative;">
            <div>
                ${idx === 0 ? `<div class="front-heading">${heading}</div>` : ''}
                <div class="toc-list"${idx === 0 ? ' style="margin-top: 16px;"' : ''}>${rowsHtml}</div>
            </div>
            <div class="sheet-footer">${pgLabel}</div>
        </div>`;
            }).join('');

            const tocSheetsHtml = renderTocSheetsHtml();
            const daftarTabelSheetsHtml = dtPages.length > 0 ? renderNamedListSheetsHtml('DAFTAR TABEL', dtPages, dtStartRoman, 'title') : '';
            const daftarGambarSheetsHtml = dgPages.length > 0 ? renderNamedListSheetsHtml('DAFTAR GAMBAR', dgPages, dgStartRoman, 'title') : '';
            const daftarLampiranSheetsHtml = dlPages.length > 0 ? renderNamedListSheetsHtml('DAFTAR LAMPIRAN', dlPages, dlStartRoman, 'label') : '';

            const DEFAULT_ACADEMIC_SEAL_SVG = `<svg width="110" height="110" viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg" style="display:block; margin:0 auto;">
              <circle cx="60" cy="60" r="56" stroke="#000000" stroke-width="2.5" fill="#ffffff"/>
              <circle cx="60" cy="60" r="50" stroke="#000000" stroke-width="1.2" stroke-dasharray="3 3"/>
              <circle cx="60" cy="60" r="44" stroke="#000000" stroke-width="1.5" fill="#ffffff"/>
              <polygon points="60,24 69,43 90,46 75,61 79,82 60,72 41,82 45,61 30,46 51,43" fill="#ffffff" stroke="#000000" stroke-width="1.5"/>
              <path d="M42 66 L60 55 L78 66 L60 77 Z" fill="#000000"/>
              <path d="M48 69.5 V80 C48 83.5 53.5 86 60 86 C66.5 86 72 83.5 72 80 V69.5" fill="none" stroke="#000000" stroke-width="2"/>
              <path d="M78 66 V78" stroke="#000000" stroke-width="2"/>
              <circle cx="78" cy="80" r="2" fill="#000000"/>
            </svg>`;

            const hasValidLogo = logo && logo.length > 50 && (logo.startsWith('data:image/') || logo.startsWith('http'));
            const logoMarkup = hasValidLogo
                ? `<img src="${logo}" alt="Logo Kampus" class="cover-logo-img" onerror="this.outerHTML = DEFAULT_ACADEMIC_SEAL_SVG;">`
                : DEFAULT_ACADEMIC_SEAL_SVG;

            const coverSub = allowBab45
                ? `Diajukan sebagai Salah Satu Syarat untuk Memperoleh Gelar Sarjana pada Program Studi ${escapeHtml(projectProdi)}`
                : `Diajukan untuk Memenuhi Persyaratan Seminar Proposal Skripsi pada Program Studi ${escapeHtml(projectProdi)}`;
            const pengesahanHeading = allowBab45 ? 'LEMBAR PENGESAHAN' : 'LEMBAR PERSETUJUAN PROPOSAL';
            const pengesahanBody = allowBab45
                ? `Telah diuji dan dipertahankan di hadapan Tim Dewan Penguji Ujian SKRIPSI pada Program Studi ${escapeHtml(projectProdi)}, ${fakultas ? escapeHtml(fakultas) + ', ' : ''}${escapeHtml(kampus)}, dan dinyatakan telah memenuhi seluruh persyaratan akademik kelulusan.`
                : `Proposal skripsi ini telah diperiksa dan disetujui oleh Dosen Pembimbing untuk diseminarkan pada Seminar Proposal Penelitian Program Studi ${escapeHtml(projectProdi)}, ${fakultas ? escapeHtml(fakultas) + ', ' : ''}${escapeHtml(kampus)}.`;

            const skripsiOnlySheets = allowBab45 ? `
        <!-- SURAT PERNYATAAN KEASLIAN -->
        <div class="a4-sheet front-body" contenteditable="true" spellcheck="false" style="display: flex; flex-direction: column; justify-content: flex-start; min-height: 1123px; position: relative;">
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
                    Menyatakan dengan sesungguhnya bahwa skripsi berjudul <strong>"${escapeHtml(projectTitle)}"</strong> ini adalah murni hasil karya penelitian, pemikiran, dan penulisan mandiri saya sendiri. Karya ini belum pernah diajukan untuk memperoleh gelar akademik di perguruan tinggi mana pun, dan bebas dari unsur plagiarisme atau fabrikasi data.
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
            <div class="sheet-footer">${pgPernyataan}</div>
        </div>

        <!-- ABSTRAK (BAHASA INDONESIA — 1 SPASI) -->
        <div class="a4-sheet front-body" contenteditable="true" spellcheck="false" style="display: flex; flex-direction: column; justify-content: flex-start; min-height: 1123px; position: relative;">
            <div>
                <div class="front-heading">ABSTRAK</div>
                <div style="text-align: center; margin: 12px 0 16px; font-weight: 700; font-size: 11.5pt; line-height: 1.3; text-transform: uppercase;">
                    ${escapeHtml(projectTitle.replace(/\.+\s*$/, ''))}
                </div>
                <div style="text-align: center; font-size: 10.5pt; color: #475569; margin-bottom: 14px;">
                    Oleh: ${escapeHtml(nama)} (NIM: ${escapeHtml(nim || '-')})<br>
                    Program Studi ${escapeHtml(projectProdi)}, ${escapeHtml(kampus)}
                </div>
                <p style="text-align: justify; line-height: 1.15; font-size: 11pt; text-indent: 1.27cm;">
                    ${(() => {
                        const cleanT = projectTitle.replace(/\.+\s*$/, '');
                        const b3Txt = (chapterContents.bab3 || '').replace(/<[^>]+>/g, ' ');
                        const b4Txt = (chapterContents.bab4 || '').replace(/<[^>]+>/g, ' ');
                        const mMeth = b3Txt.match(/penelitian\s+(kuantitatif\s+eksperimental(?:\s+komputasi)?|eksperimental\s+komputasi|kualitatif\s+deskriptif|penelitian\s+dan\s+pengembangan\s*\(R&D\)|tindakan\s+kelas|yuridis\s+(?:normatif|empiris)|campuran\s*\(mixed\s+methods\))/i);
                        const meth = mMeth ? mMeth[1].trim() : (projectMeta.metode || 'ilmiah terstruktur');
                        const srcS = b4Txt || b3Txt;
                        const mSampFinal = srcS.match(/(?:dataset\s+(?:final|bersih)|lolos\s+verifikasi[^.\n]{0,35}|ditarik\s+sampel\s+sebanyak|ukuran\s+sampel\s+(?:penelitian\s+)?(?:ditetapkan\s+)?sebanyak|sampel\s+(?:penelitian\s+)?(?:sebanyak|berjumlah|adalah)|responden\s+(?:penelitian\s+)?(?:sebanyak|berjumlah|yaitu)|digunakan\s+dalam\s+penelitian\s+berjumlah|\bN\s*=\s*)[^.\n]{0,35}?\b(\d{1,3}(?:\.\d{3})+|\d{2,5})\s*(komentar|responden|sampel|dokumen|data|citra|siswa|mahasiswa|pasien|karyawan|observasi)?\b/i);
                        let sampStr = '';
                        let sampNum = '', sampUnit = '';
                        if (mSampFinal) {
                            sampNum = mSampFinal[1];
                            sampUnit = mSampFinal[2] || 'responden';
                            sampStr = ` dengan melibatkan ${sampNum} ${sampUnit}`;
                        } else {
                            const reGen = /\b(\d{1,3}(?:\.\d{3})+|\d{2,5})\s+(komentar|responden|sampel|dokumen|data|citra|siswa|mahasiswa|pasien|karyawan|observasi)\b/gi;
                            let gm;
                            while ((gm = reGen.exec(srcS)) !== null) {
                                const prevCtx = srcS.slice(Math.max(0, gm.index - 40), gm.index);
                                if (/populasi|jumlah\s+(?:siswa|mahasiswa|guru|karyawan)\s+sebanyak/i.test(prevCtx)) continue;
                                sampNum = gm[1];
                                sampUnit = gm[2];
                                sampStr = ` dengan melibatkan ${sampNum} ${sampUnit}`;
                                break;
                            }
                        }
                        const b4Sents = b4Txt.replace(/([.!?])\s+(?=[A-ZÀ-Ÿ])/g, '$1\n').split(/\n+/).map(s => s.replace(/\s+/g, ' ').trim());
                        let bestFind = b4Sents.find(s => s.length >= 30 && s.length <= 280 && !s.startsWith('|') && /(?:akurasi(?:\s+keseluruhan)?|F1-?[Ss]core|R-?[Ss]quare|R²|koefisien\s+determinasi|berpengaruh\s+signifikan|meningkat\s+dari)/i.test(s) && /(?:\d+[,\.]\d+|\d+\s*(?:%|persen))/i.test(s) && !/dapat\s+menyesatkan|mengabaikan\s+kelas/i.test(s));
                        if (bestFind) {
                            bestFind = bestFind.replace(/^(?:Berdasarkan\s+)?Tabel\s+\d+\.\d+\s+(?:memperlihatkan|menunjukkan)\s+bahwa\s+/i, 'Hasil pengujian menunjukkan bahwa ').replace(/\.+\s*$/, '') + '.';
                        }
                        const findStr = bestFind || `Hasil penelitian menunjukkan bahwa pendekatan yang diterapkan mampu menjawab seluruh rumusan masalah secara terukur dan memberikan evaluasi kinerja yang valid.`;
                        return escapeHtml(`Penelitian ini bertujuan untuk menganalisis dan menjawab permasalahan pada topik "${cleanT}". Pendekatan yang diterapkan adalah metode ${meth}${sampStr} melalui tahapan pengumpulan, pengolahan, dan pengujian data secara sistematis. ${findStr} Temuan penelitian ini diharapkan memberikan kontribusi teoretis serta rekomendasi praktis bagi pengembangan keilmuan pada Program Studi ${projectProdi}.`);
                    })()}
                </p>
                <div style="font-size: 11pt; margin-top: 14px; line-height: 1.15;">
                    <strong>Kata Kunci:</strong> ${(() => {
                        const stopKw = new Set(['dan','atau','yang','pada','dalam','untuk','dengan','terhadap','menggunakan','melalui','studi','kasus','pengaruh','analisis','evaluasi','implementasi','penerapan','perancangan','pengembangan','berbasis','di','ke','dari','oleh','secara']);
                        const kws = projectTitle.replace(/\.+\s*$/, '').split(/[\s,:;\-\(\)\/]+/).filter(w => w.length >= 4 && !stopKw.has(w.toLowerCase())).slice(0, 4);
                        return escapeHtml([...new Set([...kws, projectProdi])].join(', ') + '.');
                    })()}
                </div>
            </div>
            <div class="sheet-footer">${pgAbstrak}</div>
        </div>

        <!-- ABSTRACT (ENGLISH — 1 SPASI ITALIC) -->
        <div class="a4-sheet front-body" contenteditable="true" spellcheck="false" style="display: flex; flex-direction: column; justify-content: flex-start; min-height: 1123px; position: relative;">
            <div>
                <div class="front-heading">ABSTRACT</div>
                <div style="text-align: center; margin: 12px 0 16px; font-weight: 700; font-size: 11.5pt; line-height: 1.3; font-style: italic; text-transform: uppercase;">
                    ${escapeHtml(projectTitle.replace(/\.+\s*$/, ''))}
                </div>
                <div style="text-align: center; font-size: 10.5pt; color: #475569; margin-bottom: 14px; font-style: italic;">
                    By: ${escapeHtml(nama)} (Student ID: ${escapeHtml(nim || '-')})<br>
                    Department of ${escapeHtml(projectProdi)}, ${escapeHtml(kampus)}
                </div>
                <p style="text-align: justify; line-height: 1.15; font-size: 11pt; text-indent: 1.27cm; font-style: italic;">
                    ${(() => {
                        const cleanT = projectTitle.replace(/\.+\s*$/, '');
                        const b3Txt = (chapterContents.bab3 || '').replace(/<[^>]+>/g, ' ');
                        const b4Txt = (chapterContents.bab4 || '').replace(/<[^>]+>/g, ' ');
                        const unitEnMap = { komentar: 'comments', responden: 'respondents', sampel: 'samples', dokumen: 'documents', data: 'records', citra: 'images', siswa: 'students', mahasiswa: 'students', pasien: 'patients', karyawan: 'employees', observasi: 'observations' };
                        const srcS = b4Txt || b3Txt;
                        const mSampFinal = srcS.match(/(?:dataset\s+(?:final|bersih)|lolos\s+verifikasi[^.\n]{0,35}|ditarik\s+sampel\s+sebanyak|ukuran\s+sampel\s+(?:penelitian\s+)?(?:ditetapkan\s+)?sebanyak|sampel\s+(?:penelitian\s+)?(?:sebanyak|berjumlah|adalah)|responden\s+(?:penelitian\s+)?(?:sebanyak|berjumlah|yaitu)|digunakan\s+dalam\s+penelitian\s+berjumlah|\bN\s*=\s*)[^.\n]{0,35}?\b(\d{1,3}(?:\.\d{3})+|\d{2,5})\s*(komentar|responden|sampel|dokumen|data|citra|siswa|mahasiswa|pasien|karyawan|observasi)?\b/i);
                        let sampEn = '';
                        if (mSampFinal) {
                            const u = (mSampFinal[2] || 'responden').toLowerCase();
                            sampEn = ` involving ${mSampFinal[1]} ${unitEnMap[u] || 'respondents'}`;
                        } else {
                            const reGen = /\b(\d{1,3}(?:\.\d{3})+|\d{2,5})\s+(komentar|responden|sampel|dokumen|data|citra|siswa|mahasiswa|pasien|karyawan|observasi)\b/gi;
                            let gm;
                            while ((gm = reGen.exec(srcS)) !== null) {
                                const prevCtx = srcS.slice(Math.max(0, gm.index - 40), gm.index);
                                if (/populasi|jumlah\s+(?:siswa|mahasiswa|guru|karyawan)\s+sebanyak/i.test(prevCtx)) continue;
                                sampEn = ` involving ${gm[1]} ${unitEnMap[gm[2].toLowerCase()] || 'samples'}`;
                                break;
                            }
                        }
                        const mAcc = b4Txt.match(/(?:akurasi(?:\s+keseluruhan)?\s+(?:sebesar\s+)?|accuracy\s+(?:of\s+)?)(\d{1,3}[,\.]\d{1,2}\s*(?:persen|%)|0[,\.]\d{2,4})/i);
                        const mR2 = !mAcc ? b4Txt.match(/(?:R²|R-?Square)\s*(?:sebesar|=)\s*(0[,\.]\d{2,4}|\d{1,2}[,\.]\d{1,2}\s*%)/i) : null;
                        const findEn = mAcc
                            ? `Empirical evaluation confirms the effectiveness of the proposed approach, achieving an overall accuracy of ${mAcc[1].replace(/persen/i, '%').replace(',', '.')} alongside consistent class-level performance metrics.`
                            : (mR2
                                ? `Empirical testing confirms a statistically significant effect with a coefficient of determination (R-Square) of ${mR2[1].replace(',', '.')}.`
                                : `The empirical findings demonstrate that the implemented approach effectively addresses all research questions and provides verifiable evaluation outcomes.`);
                        return escapeHtml(`This study aims to investigate, analyze, and resolve the empirical problems addressed in "${cleanT}". Employing a structured research methodology${sampEn}, this study systematically collects, processes, and evaluates empirical data. ${findEn} Overall, the results offer both theoretical contributions and practical implications for the field of ${projectProdi}.`);
                    })()}
                </p>
                <div style="font-size: 11pt; margin-top: 14px; font-style: italic; line-height: 1.15;">
                    <strong>Keywords:</strong> ${(() => {
                        const stopKw = new Set(['dan','atau','yang','pada','dalam','untuk','dengan','terhadap','menggunakan','melalui','studi','kasus','pengaruh','analisis','evaluasi','implementasi','penerapan','perancangan','pengembangan','berbasis','di','ke','dari','oleh','secara']);
                        const kws = projectTitle.replace(/\.+\s*$/, '').split(/[\s,:;\-\(\)\/]+/).filter(w => w.length >= 4 && !stopKw.has(w.toLowerCase())).slice(0, 4);
                        return escapeHtml([...new Set([...kws, 'Empirical Evaluation'])].join(', ') + '.');
                    })()}
                </div>
            </div>
            <div class="sheet-footer">${pgAbstract}</div>
        </div>
            ` : '';

            return `
        <!-- 1. HALAMAN JUDUL (COVER) -->
        <div class="a4-sheet cover-sheet" contenteditable="true" spellcheck="false">
            <div class="cover-top-block">
                <div class="cover-kind">${docKind}</div>
                <div class="cover-title">${escapeHtml(projectTitle)}</div>
                <div class="cover-sub">${coverSub}</div>
            </div>
            <div class="cover-logo-box" onclick="document.getElementById('sidebar-logo-upload').click()" title="Klik untuk ganti logo kampus" style="cursor: pointer;">
                ${logoMarkup}
            </div>
            <div class="cover-author-block">
                <div style="font-size: 11pt; letter-spacing: 0.05em; margin-bottom: 4px;">OLEH:</div>
                <div style="font-size: 13pt; font-weight: 700;"><u>${escapeHtml(nama)}</u></div>
                ${nim ? `<div style="font-size: 11.5pt; margin-top: 3px;">NIM / NPM: ${escapeHtml(nim)}</div>` : ''}
            </div>
            <div class="cover-campus-block">
                <div>PROGRAM STUDI ${escapeHtml(projectProdi)}</div>
                ${fakultas ? `<div>${escapeHtml(fakultas)}</div>` : ''}
                <div>${escapeHtml(kampus)}</div>
                <div>${tahun}</div>
            </div>
        </div>

        <!-- 2. LEMBAR PERSETUJUAN / PENGESAHAN -->
        <div class="a4-sheet front-body" contenteditable="true" spellcheck="false" style="display: flex; flex-direction: column; justify-content: flex-start; min-height: 1123px; position: relative;">
            <div>
                <div class="front-heading">${pengesahanHeading}</div>
                <div style="text-align: center; margin: 18px 0; font-weight: 700; font-size: 13pt; line-height: 1.4; text-transform: uppercase;">
                    ${escapeHtml(projectTitle)}
                </div>
                <div style="text-align: center; font-size: 11pt; margin-bottom: 22px;">
                    Disusun Oleh: <u><strong>${escapeHtml(nama)}</strong></u> ${nim ? `(NIM: ${escapeHtml(nim)})` : ''}
                </div>
                <p style="text-align: justify; line-height: 1.6; font-size: 11.5pt; text-indent: 1.27cm;">
                    ${pengesahanBody}
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
            <div class="sheet-footer">${pgPengesahan}</div>
        </div>

        ${skripsiOnlySheets}

        <!-- KATA PENGANTAR (STANDAR AKADEMIK PERGURUAN TINGGI INDONESIA) -->
        <div class="a4-sheet front-body" contenteditable="true" spellcheck="false" style="display: flex; flex-direction: column; justify-content: flex-start; min-height: 1123px; position: relative;">
            <div>
                <div class="front-heading">KATA PENGANTAR</div>
                <p style="text-align: justify; line-height: 1.55; font-size: 11.2pt; text-indent: 1.27cm; margin-top: 14px; margin-bottom: 7px;">
                    Dengan memanjatkan puji syukur ke hadirat Tuhan Yang Maha Esa, yang telah melimpahkan rahmat dan hidayah-Nya, penulis dapat menyelesaikan penyusunan ${docKind.toLowerCase()} dengan judul <strong>"${escapeHtml(projectTitle)}"</strong>.
                </p>
                <p style="text-align: justify; line-height: 1.55; font-size: 11.2pt; text-indent: 1.27cm; margin-bottom: 7px;">
                    Penulisan ${docKind.toLowerCase()} ini ${allowBab45 ? 'dimaksudkan untuk memenuhi salah satu syarat memperoleh gelar Sarjana' : 'diajukan sebagai salah satu persyaratan Seminar Proposal Skripsi'} pada Program Studi ${escapeHtml(projectProdi)}${fakultas ? ', ' + escapeHtml(fakultas) : ''}, ${escapeHtml(kampus)}. Penulis berharap hasil penelitian ini dapat memperkaya kajian ilmiah pada bidang yang diteliti serta menjadi bahan pertimbangan bagi pihak-pihak yang terkait dengan permasalahan penelitian.
                </p>
                <p style="text-align: justify; line-height: 1.55; font-size: 11.2pt; text-indent: 1.27cm; margin-bottom: 6px;">
                    Penyelesaian ${docKind.toLowerCase()} ini tidak terlepas dari peran serta berbagai pihak. Oleh sebab itu, pada kesempatan yang baik ini penulis menyampaikan terima kasih dan penghargaan yang setinggi-tingginya kepada:
                </p>
                <ol style="margin: 4px 0 8px 24px; padding-left: 12px; font-size: 11pt; line-height: 1.48; text-align: justify;">
                    <li style="margin-bottom: 3px;">Bapak/Ibu (Nama Rektor), selaku Rektor ${escapeHtml(kampus)} yang telah memberikan kesempatan kepada penulis untuk menempuh pendidikan;</li>
                    <li style="margin-bottom: 3px;">Bapak/Ibu (Nama Dekan), selaku Dekan ${escapeHtml(fakultas || 'Fakultas terkait')} yang telah memberikan fasilitas dan kemudahan selama perkuliahan;</li>
                    <li style="margin-bottom: 3px;">Bapak/Ibu (Nama Ketua Program Studi), selaku Ketua Program Studi ${escapeHtml(projectProdi)} yang telah memberikan arahan dan dukungan akademik;</li>
                    <li style="margin-bottom: 3px;">Bapak/Ibu (Nama Dosen Pembimbing), selaku Dosen Pembimbing yang telah memberikan bimbingan, koreksi, dan motivasi sejak penyusunan proposal hingga terselesaikannya ${docKind.toLowerCase()} ini;</li>
                    <li style="margin-bottom: 3px;">Bapak/Ibu (Nama Dosen Pembimbing Akademik), selaku Dosen Pembimbing Akademik yang telah mendampingi penulis selama masa studi;</li>
                    <li style="margin-bottom: 3px;">Bapak/Ibu Dosen Penguji yang telah memberikan masukan berharga bagi penyempurnaan ${docKind.toLowerCase()} ini;</li>
                    <li style="margin-bottom: 3px;">Seluruh dosen dan tenaga kependidikan ${escapeHtml(fakultas || 'Fakultas')} ${escapeHtml(kampus)} atas ilmu dan pelayanan yang telah diberikan;</li>
                    <li style="margin-bottom: 3px;">Orang tua tercinta yang dengan tulus senantiasa mendoakan, membimbing, dan mendukung penulis dalam setiap langkah;</li>
                    <li style="margin-bottom: 3px;">Saudara, sahabat, serta rekan-rekan yang telah memberikan bantuan dan dorongan semangat selama proses penyusunan ${docKind.toLowerCase()} ini.</li>
                </ol>
                <p style="text-align: justify; line-height: 1.55; font-size: 11.2pt; text-indent: 1.27cm; margin-bottom: 6px;">
                    Semoga Tuhan Yang Maha Esa membalas segala kebaikan dan bantuan yang telah diberikan dengan pahala yang setimpal. Penulis menyadari sepenuhnya bahwa ${docKind.toLowerCase()} ini masih memiliki kekurangan. Oleh karena itu, kritik dan saran yang bersifat membangun sangat penulis harapkan demi penyempurnaan penelitian selanjutnya.
                </p>
                <div style="margin-top: 22px; float: right; width: 220px; text-align: right; font-size: 11pt; line-height: 1.4;">
                    <div>(Kota), ${tahun}</div>
                    <div>Penulis,</div>
                    <div style="height: 46px;"></div>
                    <div><strong>${escapeHtml(nama).toUpperCase()}</strong></div>
                </div>
                <div style="clear: both;"></div>
            </div>
            <div class="sheet-footer">${pgKataPengantar}</div>
        </div>

        <!-- DAFTAR ISI (OTOMATIS TERPAGINASI PER HALAMAN A4) -->
        ${tocSheetsHtml}

        <!-- DAFTAR TABEL -->
        ${daftarTabelSheetsHtml}

        <!-- DAFTAR GAMBAR -->
        ${daftarGambarSheetsHtml}

        <!-- DAFTAR LAMPIRAN -->
        ${daftarLampiranSheetsHtml}
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
        } catch (e) { }

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
            } else if (type === 'sipil') {
                document.getElementById('riil-metode').value = 'Uji Laboratorium SNI & One-Way ANOVA + Post-Hoc Bonferroni (Setara SPSS)';
                document.getElementById('riil-sampel').value = '20 Benda Uji Silinder Beton (4 Variasi Campuran × 5 Ulangan, Umur 28 Hari)';
                document.getElementById('riil-rumus').value = 'fc\' = P / A (MPa); One-Way ANOVA F = MSB / MSW; Homogenitas Levene; Post-Hoc Bonferroni';
                document.getElementById('riil-lokasi').value = 'Laboratorium Bahan & Struktur Teknik Sipil';
                document.getElementById('riil-metrik').value = 'Rata-rata Kuat Tekan 28 Hari: Normal 0% = 25.48 MPa, Fly Ash 10% = 28.76 MPa, Fly Ash 20% = 31.94 MPa (Optimum), Fly Ash 30% = 27.08 MPa. Uji Levene Sig. = 0.842 (Homogen). One-Way ANOVA F = 198.42 (Sig. < 0.001), Eta-Squared = 0.974.';
                document.getElementById('riil-evaluasi').value = 'Uji Slump & Kuat Tekan SNI 1974:2011, Uji Homogenitas Levene, One-Way ANOVA (α = 0.05), dan Uji Lanjut Post-Hoc Bonferroni';
            } else if (type === 'eksperimen') {
                document.getElementById('riil-metode').value = 'Quasi-Experimental Design (Nonequivalent Pretest-Posttest Control Group)';
                document.getElementById('riil-sampel').value = '64 Siswa (Kelas Eksperimen n=32, Kelas Kontrol n=32)';
                document.getElementById('riil-rumus').value = 'Skor N-Gain = (Skor Posttest - Skor Pretest) / (Skor Maksimal - Skor Pretest), Independent Samples t-test';
                document.getElementById('riil-lokasi').value = 'Sekolah / Lembaga Pendidikan';
                document.getElementById('riil-metrik').value = 'Kelas Eksperimen: Rata-rata Pretest 54.20 -> Posttest 85.50 (N-Gain 0.683 kategori Sedang-Tinggi / Efektif). Kelas Kontrol: Pretest 53.80 -> Posttest 68.20 (N-Gain 0.311). Nilai t-hitung = 5.241 (Sig 0.000 < 0.05).';
                document.getElementById('riil-evaluasi').value = 'Uji Normalitas Shapiro-Wilk, Uji Homogenitas Levene, Independent Sample t-test, Kategori Tafsiran N-Gain Hake';
            }
        }

        let projOdDataset = { columns: [], rows: [], numeric: [], categorical: [] };
        let projOdLastResult = null;

        function switchProjOlahTab(tab) {
            const pCalc = document.getElementById('proj-olah-panel-calc');
            const pManual = document.getElementById('proj-olah-panel-manual');
            const tCalc = document.getElementById('proj-olah-tab-calc');
            const tManual = document.getElementById('proj-olah-tab-manual');
            if (pCalc) pCalc.style.display = tab === 'calc' ? 'flex' : 'none';
            if (pManual) pManual.style.display = tab === 'manual' ? 'flex' : 'none';
            if (tCalc) tCalc.classList.toggle('active', tab === 'calc');
            if (tManual) tManual.classList.toggle('active', tab === 'manual');
        }

        function projOdLoadSheetJs() {
            if (window.XLSX) return Promise.resolve(window.XLSX);
            return new Promise((resolve, reject) => {
                const s = document.createElement('script');
                s.src = 'https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js';
                s.onload = () => window.XLSX ? resolve(window.XLSX) : reject(new Error('Library Excel gagal dimuat.'));
                s.onerror = () => reject(new Error('Gagal memuat pembaca Excel (.xlsx). Simpan sebagai .CSV lalu unggah ulang.'));
                document.head.appendChild(s);
            });
        }

        function projOdParseCsv(raw) {
            const lines = String(raw || '').replace(/^\uFEFF/, '').trim().split(/\r\n|\r|\n/).filter(l => l.trim() !== '');
            if (lines.length < 2) return { columns: [], rows: [] };
            const first = lines[0];
            let delim = ',';
            if ((first.match(/\t/g) || []).length > (first.match(/,/g) || []).length) delim = '\t';
            else if ((first.match(/;/g) || []).length > (first.match(/,/g) || []).length) delim = ';';
            const split = (line) => {
                const out = []; let cur = ''; let inQ = false;
                for (let i = 0; i < line.length; i++) {
                    const ch = line[i];
                    if (ch === '"') { if (inQ && line[i + 1] === '"') { cur += '"'; i++; } else inQ = !inQ; }
                    else if (ch === delim && !inQ) { out.push(cur.trim()); cur = ''; }
                    else cur += ch;
                }
                out.push(cur.trim());
                return out;
            };
            const columns = split(lines[0]).map((h, i) => h || ('Kolom_' + (i + 1)));
            const rows = [];
            for (let i = 1; i < lines.length; i++) {
                const vals = split(lines[i]);
                const obj = {};
                let ok = false;
                columns.forEach((c, idx) => { const v = vals[idx] !== undefined ? vals[idx] : ''; if (v !== '') ok = true; obj[c] = v; });
                if (ok) rows.push(obj);
            }
            return { columns, rows };
        }

        async function projOdHandleFile(input) {
            const errEl = document.getElementById('proj-od-err');
            const stEl = document.getElementById('proj-od-file-status');
            errEl.style.display = 'none';
            const file = input?.files?.[0];
            if (!file) return;
            const ext = (file.name.split('.').pop() || '').toLowerCase();
            try {
                let columns = [], rows = [];
                if (ext === 'xlsx' || ext === 'xls') {
                    const XLSX = await projOdLoadSheetJs();
                    const wb = XLSX.read(await file.arrayBuffer(), { type: 'array' });
                    const arr = XLSX.utils.sheet_to_json(wb.Sheets[wb.SheetNames[0]], { header: 1, defval: '' });
                    if (!arr || arr.length < 2) throw new Error('File Excel kosong.');
                    columns = arr[0].map((h, i) => String(h || '').trim() || ('Kolom_' + (i + 1)));
                    for (let i = 1; i < arr.length; i++) {
                        if (!arr[i] || !arr[i].some(v => String(v ?? '').trim() !== '')) continue;
                        const obj = {};
                        columns.forEach((c, idx) => { obj[c] = arr[i][idx] !== undefined ? String(arr[i][idx]).trim() : ''; });
                        rows.push(obj);
                    }
                } else {
                    const parsed = projOdParseCsv(await file.text());
                    columns = parsed.columns; rows = parsed.rows;
                }
                if (!columns.length || rows.length < 2) throw new Error('File minimal harus berisi 1 baris header dan 2 baris data.');
                const numeric = [], categorical = [];
                columns.forEach(col => {
                    let nOk = 0, tot = 0;
                    rows.slice(0, 100).forEach(r => {
                        const s = String(r[col] ?? '').trim();
                        if (s !== '') {
                            tot++;
                            const norm = s.includes(',') && !s.includes('.') ? s.replace(',', '.') : s.replace(/,/g, '');
                            if (!isNaN(parseFloat(norm)) && isFinite(norm)) nOk++;
                        }
                    });
                    if (tot > 0 && nOk / tot >= 0.7) numeric.push(col);
                    else categorical.push(col);
                });
                projOdDataset = { columns, rows, numeric, categorical };
                stEl.innerHTML = `<strong style="color:#047857;">File siap:</strong> ${escapeHtml(file.name)} (${rows.length} baris, ${numeric.length} numerik, ${categorical.length} kategorik).`;
                projOdRenderVars();
            } catch (e) {
                errEl.style.display = 'block';
                errEl.textContent = e.message || 'Gagal membaca file.';
            }
        }

        function projOdRenderVars() {
            const box = document.getElementById('proj-od-vars-box');
            if (!box || !projOdDataset.columns.length) return;
            box.style.display = 'block';
            const test = document.getElementById('proj-od-test').value;
            const all = projOdDataset.columns;
            const num = projOdDataset.numeric.length ? projOdDataset.numeric : all;
            const nonIdNum = num.filter(c => !/^(id|no|nomor|responden|kode)/i.test(c));
            const nonIdCat = projOdDataset.categorical.filter(c => !/^(id|no|nomor|responden|kode)/i.test(c));
            const isIdCol = c => /^(id|no|nomor|responden|kode_benda_uji|kode_benda|kode_sampel|kode|nim|npm)$/i.test(c.trim());
            const factorCols = all.filter(c => {
                if (isIdCol(c)) return false;
                const u = new Set(); let ne = 0;
                projOdDataset.rows.forEach(r => { const v = String(r[c] ?? '').trim(); if (v !== '') { ne++; u.add(v); } });
                return u.size >= 2 && u.size < ne && (projOdDataset.categorical.includes(c) || u.size <= Math.min(12, Math.max(2, Math.floor(ne / 2))));
            });
            const safeFactors = factorCols.length ? factorCols : (nonIdCat.length ? nonIdCat : all.filter(c => !isIdCol(c)));
            const safeNum = nonIdNum.length ? nonIdNum : num;
            const opts = (list, sel) => list.map(c => `<option value="${escapeHtml(c)}" ${c === sel ? 'selected' : ''}>${escapeHtml(c)}</option>`).join('');

            if (test === 'regression') {
                const isItem = c => /^[A-Za-z]+\d*[_\.\-\s]+\d+$/.test(c) && !/total|skor|sum/i.test(c);
                const summary = nonIdNum.filter(c => !isItem(c));
                const pool = summary.length >= 2 ? summary : nonIdNum;
                const prevY = document.getElementById('proj-od-y')?.value;
                const defY = (prevY && pool.includes(prevY)) ? prevY : (pool[pool.length - 1] || num[num.length - 1] || '');
                const defXs = pool.filter(c => c !== defY);
                const chips = defXs.map(c => `<label class="olah-mini-chip checked"><input type="checkbox" class="proj-od-x" value="${escapeHtml(c)}" checked onchange="this.parentElement.classList.toggle('checked', this.checked)">${escapeHtml(c)}</label>`).join('');
                box.innerHTML = `<div style="display:grid; grid-template-columns:1fr 1.5fr; gap:10px;">
                    <div><label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:4px;">Variabel Y (Dependen)</label><select id="proj-od-y" class="olah-input" style="padding:7px 9px; font-size:12px;" onchange="projOdRenderVars()">${opts(pool, defY)}</select></div>
                    <div><label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:4px;">Variabel X (Independen, otomatis bebas Y)</label><div style="display:flex; flex-wrap:wrap; gap:5px;">${chips}</div></div>
                </div>`;
            } else if (test === 'anova' || test === 'ttest_ind') {
                box.innerHTML = `<div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                    <div><label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:4px;">Kolom Kelompok / Perlakuan</label><select id="proj-od-g" class="olah-input" style="padding:7px 9px; font-size:12px;">${opts(safeFactors, safeFactors[0])}</select></div>
                    <div><label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:4px;">Kolom Nilai Numerik</label><select id="proj-od-v" class="olah-input" style="padding:7px 9px; font-size:12px;">${opts(safeNum, safeNum[0])}</select></div>
                </div>`;
            } else if (test === 'ttest_paired') {
                box.innerHTML = `<div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                    <div><label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:4px;">Kondisi 1 (Pre-Test)</label><select id="proj-od-pa" class="olah-input" style="padding:7px 9px; font-size:12px;">${opts(safeNum, safeNum[0])}</select></div>
                    <div><label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:4px;">Kondisi 2 (Post-Test)</label><select id="proj-od-pb" class="olah-input" style="padding:7px 9px; font-size:12px;">${opts(safeNum, safeNum[1] || safeNum[0])}</select></div>
                </div>`;
            } else if (test === 'chisquare') {
                box.innerHTML = `<div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                    <div><label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:4px;">Kategorik Baris</label><select id="proj-od-ca" class="olah-input" style="padding:7px 9px; font-size:12px;">${opts(safeFactors, safeFactors[0])}</select></div>
                    <div><label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:4px;">Kategorik Kolom</label><select id="proj-od-cb" class="olah-input" style="padding:7px 9px; font-size:12px;">${opts(safeFactors, safeFactors[1] || safeFactors[0])}</select></div>
                </div>`;
            } else if (test === 'nonparam') {
                const prevNp = document.getElementById('proj-od-np')?.value || 'mann_whitney';
                const leftPool = prevNp === 'wilcoxon' ? safeNum : safeFactors;
                box.innerHTML = `<div style="margin-bottom:8px;"><label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:4px;">Jenis Non-Parametrik</label>
                    <select id="proj-od-np" class="olah-input" style="padding:7px 9px; font-size:12px;" onchange="projOdRenderVars()">
                        <option value="mann_whitney" ${prevNp === 'mann_whitney' ? 'selected' : ''}>Mann-Whitney U (2 Kelompok)</option>
                        <option value="kruskal_wallis" ${prevNp === 'kruskal_wallis' ? 'selected' : ''}>Kruskal-Wallis H (≥ 3 Kelompok)</option>
                        <option value="wilcoxon" ${prevNp === 'wilcoxon' ? 'selected' : ''}>Wilcoxon Signed-Rank (Berpasangan)</option>
                    </select></div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                        <div><label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:4px;">${prevNp === 'wilcoxon' ? 'Kondisi 1' : 'Kolom Kelompok / Perlakuan'}</label><select id="proj-od-g" class="olah-input" style="padding:7px 9px; font-size:12px;">${opts(leftPool, leftPool[0])}</select></div>
                        <div><label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:4px;">${prevNp === 'wilcoxon' ? 'Kondisi 2' : 'Kolom Nilai Numerik'}</label><select id="proj-od-v" class="olah-input" style="padding:7px 9px; font-size:12px;">${opts(safeNum, prevNp === 'wilcoxon' ? (safeNum[1] || safeNum[0]) : safeNum[0])}</select></div>
                    </div>`;
            } else {
                const items = test === 'validity_reliability' ? nonIdNum.filter(c => !/total|sum/i.test(c)) : nonIdNum;
                const useList = items.length >= 2 ? items : nonIdNum;
                const chips = nonIdNum.map(c => `<label class="olah-mini-chip ${useList.includes(c) ? 'checked' : ''}"><input type="checkbox" class="proj-od-m" value="${escapeHtml(c)}" ${useList.includes(c) ? 'checked' : ''} onchange="this.parentElement.classList.toggle('checked', this.checked)">${escapeHtml(c)}</label>`).join('');
                box.innerHTML = `<label style="display:block; font-size:11.5px; font-weight:600; margin-bottom:5px;">Kolom Variabel Numerik</label><div style="display:flex; flex-wrap:wrap; gap:5px;">${chips}</div>`;
            }
        }

        async function projOdRunCalc() {
            const errEl = document.getElementById('proj-od-err');
            errEl.style.display = 'none';
            if (!projOdDataset.rows.length) {
                errEl.style.display = 'block';
                errEl.textContent = 'Unggah file Excel (.xlsx/.xls) atau CSV terlebih dahulu.';
                return;
            }
            const test = document.getElementById('proj-od-test').value;
            const payload = {
                columns: projOdDataset.columns,
                rows: projOdDataset.rows,
                test_type: test,
                alpha: parseFloat(document.getElementById('proj-od-alpha').value || '0.05')
            };
            if (test === 'regression') {
                payload.y_col = document.getElementById('proj-od-y')?.value || '';
                payload.x_cols = Array.from(document.querySelectorAll('.proj-od-x:checked')).map(el => el.value).filter(v => v !== payload.y_col);
            } else if (test === 'anova' || test === 'ttest_ind') {
                payload.group_col = document.getElementById('proj-od-g')?.value || '';
                payload.value_col = document.getElementById('proj-od-v')?.value || '';
            } else if (test === 'ttest_paired') {
                payload.pair_a = document.getElementById('proj-od-pa')?.value || '';
                payload.pair_b = document.getElementById('proj-od-pb')?.value || '';
            } else if (test === 'chisquare') {
                payload.cat_a = document.getElementById('proj-od-ca')?.value || '';
                payload.cat_b = document.getElementById('proj-od-cb')?.value || '';
            } else if (test === 'nonparam') {
                const mode = document.getElementById('proj-od-np')?.value || 'mann_whitney';
                payload.nonparam_mode = mode;
                if (mode === 'wilcoxon') {
                    payload.pair_a = document.getElementById('proj-od-g')?.value || '';
                    payload.pair_b = document.getElementById('proj-od-v')?.value || '';
                } else {
                    payload.group_col = document.getElementById('proj-od-g')?.value || '';
                    payload.value_col = document.getElementById('proj-od-v')?.value || '';
                }
            } else {
                payload.cols = Array.from(document.querySelectorAll('.proj-od-m:checked')).map(el => el.value);
            }

            const btn = document.getElementById('proj-od-run-btn');
            const orig = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="btn-mini-spinner"></span><span>Menghitung...</span>';
            try {
                const res = await fetch("{{ route('tools.stats.analyze') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (!res.ok || data.status !== 'success') throw new Error(data.message || 'Perhitungan gagal.');
                projOdLastResult = data.result;

                const box = document.getElementById('proj-od-result-box');
                box.style.display = 'flex';
                document.getElementById('proj-od-res-title').textContent = data.result.title || 'Hasil Valid Siap Diterapkan';
                document.getElementById('proj-od-res-hl').innerHTML = (data.result.highlights || []).map(h =>
                    `<div style="background:#ffffff; border:1px solid #d1fae5; border-radius:8px; padding:7px 9px;"><div style="font-size:10px; color:#64748b; font-weight:700;">${escapeHtml(h.label)}</div><div style="font-size:12.5px; font-weight:800; color:#0f172a; margin-top:2px;">${escapeHtml(h.value)}</div></div>`
                ).join('');
                const firstTbl = (data.result.tables || [])[0];
                if (firstTbl) {
                    document.getElementById('proj-od-res-preview').innerHTML = `<table class="olah-mini-tbl"><thead><tr>${firstTbl.headers.map(h => `<th>${escapeHtml(h)}</th>`).join('')}</tr></thead><tbody>${firstTbl.rows.slice(0, 6).map(r => `<tr>${r.map(c => `<td>${escapeHtml(c)}</td>`).join('')}</tr>`).join('')}</tbody></table>`;
                }
            } catch (e) {
                errEl.style.display = 'block';
                errEl.textContent = e.message || 'Gagal menghitung statistik.';
            } finally {
                btn.disabled = false;
                btn.innerHTML = orig;
            }
        }

        function projOdApplyToForm() {
            if (!projOdLastResult?.project_autofill) return;
            const af = projOdLastResult.project_autofill;
            document.getElementById('riil-metode').value = af.metode || '';
            document.getElementById('riil-sampel').value = af.sampel || '';
            document.getElementById('riil-rumus').value = af.rumus || '';
            document.getElementById('riil-metrik').value = af.metrik || '';
            document.getElementById('riil-evaluasi').value = af.evaluasi || '';
            switchProjOlahTab('manual');
            showToast('Angka valid hasil SPSS berhasil diisi ke form! Klik "Simpan Data Olahan" untuk mengunci ke Bab III & IV.', 'success');
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
            const hasExisting = Object.values(researchDataRiil || {}).some(v => String(v || '').trim().length > 0);
            switchProjOlahTab(hasExisting ? 'manual' : 'calc');
            m.style.display = 'flex';
        }

        function closeDataRiilModal() {
            const m = document.getElementById('modal-data-riil');
            if (m) m.style.display = 'none';
        }

        function openEditIdentityModal() {
            const modal = document.getElementById('identity-modal');
            if (!modal) return;
            document.getElementById('id-modal-nama').value = projectMeta.nama || defaultUserName || '';
            document.getElementById('id-modal-nim').value = projectMeta.nim || '';
            document.getElementById('id-modal-fakultas').value = projectMeta.fakultas || '';
            document.getElementById('id-modal-prodi').value = projectProdi || '';
            document.getElementById('id-modal-kampus').value = projectMeta.kampus || '';
            modal.style.display = 'flex';
        }

        function closeEditIdentityModal() {
            const modal = document.getElementById('identity-modal');
            if (modal) modal.style.display = 'none';
        }

        async function saveStudentIdentityModal() {
            const btn = document.getElementById('btn-save-identity');
            const nama = (document.getElementById('id-modal-nama').value || '').trim();
            const nim = (document.getElementById('id-modal-nim').value || '').trim();
            const fakultas = (document.getElementById('id-modal-fakultas').value || '').trim();
            const prodi = (document.getElementById('id-modal-prodi').value || '').trim();
            const kampus = (document.getElementById('id-modal-kampus').value || '').trim();

            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="btn-mini-spinner"></span><span>Menyimpan...</span>';
            }

            try {
                const res = await fetch(`/project/${projectId}/save-chapter`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({
                        chapter: 'identity',
                        nama, nim, fakultas, prodi, kampus
                    })
                });
                const data = await res.json();
                if (!res.ok || data.status !== 'success') throw new Error(data.message || 'Gagal menyimpan.');

                projectMeta.nama = nama;
                projectMeta.nim = nim;
                projectMeta.fakultas = fakultas;
                projectMeta.kampus = kampus;
                if (prodi) {
                    projectMeta.prodi = prodi;
                    projectProdi = prodi;
                }
                delete projectMeta.front_matter_html;

                // Sinkronkan hidden inputs form download agar Word/PDF langsung memakai identitas terbaru tanpa reload
                const dlForm = document.getElementById('quick-download-form');
                if (dlForm) {
                    const setField = (n, v) => { const el = dlForm.querySelector(`input[name="${n}"]`); if (el) el.value = v; };
                    setField('nama', nama);
                    setField('npm', nim);
                    setField('kampus', kampus);
                    setField('fakultas', fakultas);
                }

                closeEditIdentityModal();
                showToast('Identitas mahasiswa berhasil diperbarui.', 'success');
                renderActiveSheet();
            } catch (e) {
                showToast('Gagal menyimpan identitas: ' + e.message, 'error');
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = 'Simpan Identitas';
                }
            }
        }

        function updateOlahDataIndicator() {
            const btn = document.getElementById('btn-menu-olah-data');
            if (!btn) return;
            const hasData = Object.values(researchDataRiil || {}).some(v => String(v || '').trim().length > 0);
            btn.style.borderColor = hasData ? '#2563eb' : '#cbd5e1';
            btn.style.background = hasData ? '#eff6ff' : '';
            btn.style.color = hasData ? '#1d4ed8' : '';
        }

        async function saveDataRiil() {
            const btn = document.getElementById('btn-save-data-riil');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="btn-mini-spinner"></span><span>Menyimpan...</span>';
            }
            researchDataRiil = {
                metode: (document.getElementById('riil-metode').value || '').trim(),
                sampel: (document.getElementById('riil-sampel').value || '').trim(),
                rumus: (document.getElementById('riil-rumus')?.value || '').trim(),
                lokasi: (document.getElementById('riil-lokasi').value || '').trim(),
                metrik: (document.getElementById('riil-metrik').value || '').trim(),
                evaluasi: (document.getElementById('riil-evaluasi').value || '').trim()
            };
            projectMeta.data_riil = researchDataRiil;
            try {
                localStorage.setItem(`skripsi_data_riil_${projectId}`, JSON.stringify(researchDataRiil));
            } catch (e) { }
            updateOlahDataIndicator();

            try {
                const res = await fetch(`/project/${projectId}/save-chapter`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ chapter: 'data_riil', data_riil: researchDataRiil })
                });
                const data = await res.json();
                if (!res.ok || data.status !== 'success') {
                    throw new Error(data.message || 'Gagal sinkronisasi server.');
                }
                closeDataRiilModal();
                showToast('Data olahan & rumus penelitian tersimpan permanen di proyek! Siap diterapkan ke Bab III & IV.', 'success');
            } catch (e) {
                closeDataRiilModal();
                showToast('Data olahan tersimpan lokal (sinkronisasi server tertunda).', 'info');
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = 'Simpan Data Olahan';
                }
            }
        }

        // =========================================================================
        // PAGINASI PRESISI KERTAS A4 UNTUK NASKAH BAB & HALAMAN DEPAN
        // =========================================================================
        function paginateChapterToSheetsHtml(chapterKey, fullBodyHtml, startPageNum, precomputedPages) {
            const pages = Array.isArray(precomputedPages) && precomputedPages.length > 0
                ? precomputedPages
                : splitBodyHtmlToPages(fullBodyHtml);
            if (!pages.length) return '';

            let outputHtml = '';
            pages.forEach((pageContent, pIdx) => {
                const thisPageNum = startPageNum + pIdx;
                outputHtml += `
                    <div class="a4-sheet" style="min-height: 1123px; display: flex; flex-direction: column; justify-content: flex-start; margin-bottom: 24px; position: relative;">
                        <div class="paper-editor" contenteditable="true" spellcheck="false" data-chapter="${chapterKey}" data-page="${thisPageNum}">
                            ${pageContent}
                        </div>
                        <div class="sheet-footer">${thisPageNum}</div>
                    </div>
                `;
            });
            return outputHtml;
        }

        function renderAllSheets() {
            const container = document.getElementById('sheets-container');
            const pageMap = computeDocumentPagination();
            const frontHtml = buildCoverAndFrontMatterHtml(pageMap);

            const keys = allowBab45
                ? ['bab1', 'bab2', 'bab3', 'bab4', 'bab5', 'daftar_pustaka']
                : ['bab1', 'bab2', 'bab3', 'daftar_pustaka'];

            let sheetsHtml = frontHtml;

            keys.forEach((key) => {
                const raw = (chapterContents[key] || '').trim();
                const curH = PREVIEW_CHAPTER_HEADERS[key] || { num: '', title: chapterLabels[key] || 'DOKUMEN' };
                const chInfo = pageMap[key];
                if (!raw || !chInfo || chInfo.pageCount === 0) {
                    sheetsHtml += `
                <div class="a4-sheet empty-sheet" style="margin-top: 28px; min-height: 240px; display: flex; flex-direction: column; justify-content: center; align-items: center; border: 2px dashed #cbd5e1; background: #fafafa;">
                    <h3 style="font-size: 15px; font-weight: 700; color: #475569; margin-bottom: 6px;">${escapeHtml(chapterLabels[key] || curH.title)}</h3>
                    <p style="font-size: 12.5px; color: #94a3b8; margin-bottom: 12px;">Bagian ini belum ditulis.</p>
                    <button type="button" class="btn-top primary" onclick="selectChapterView('${key}')">Buka & Tulis ${escapeHtml(curH.num || curH.title)}</button>
                </div>
            `;
                    return;
                }

                sheetsHtml += paginateChapterToSheetsHtml(key, '', chInfo.startPage, chInfo.pages);
            });

            // Lampiran A4 Sheets di akhir tampilan Lihat Semua Bab (Full) jika sudah ada lampiran
            const allLampiran = getLampiranWithPages(pageMap);
            if (allLampiran.length > 0) {
                sheetsHtml += buildLampiranSheetsHtml(allLampiran);
            }

            container.innerHTML = sheetsHtml;
            applyCurrentDocFormatting();

            container.querySelectorAll('.paper-editor[data-chapter]').forEach((editor) => {
                editor.addEventListener('input', () => {
                    const chKey = editor.getAttribute('data-chapter');
                    const allPages = container.querySelectorAll(`.paper-editor[data-chapter="${chKey}"]`);
                    chapterContents[chKey] = Array.from(allPages).map(p => p.innerText.trim()).filter(Boolean).join('\n\n');
                    isDirty = true;
                    document.getElementById('btn-save-edit').style.display = 'inline-flex';
                });
                editor.addEventListener('keydown', (e) => {
                    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
                        e.preventDefault();
                        saveActiveEdit();
                    }
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
                container.innerHTML = `<div id="front-sheets-wrap">${savedFront || buildCoverAndFrontMatterHtml()}</div>`;
                applyCurrentDocFormatting();
                container.querySelectorAll('[contenteditable="true"]').forEach((el) => {
                    el.addEventListener('input', () => {
                        isDirty = true;
                        const saveBtn = document.getElementById('btn-save-edit');
                        if (saveBtn) saveBtn.style.display = 'inline-flex';
                    });
                });
                return;
            }
            if (activeView === 'lampiran') {
                renderLampiranView();
                return;
            }
            if (activeView === 'revisi') {
                renderLabRevisi();
                return;
            }

            // Header Bab Standar Skripsi Indonesia (BAB I PENDAHULUAN, dll.)
            const chapterHeaders = {
                'bab1': { num: 'BAB I', title: 'PENDAHULUAN' },
                'bab2': { num: 'BAB II', title: 'TINJAUAN PUSTAKA' },
                'bab3': { num: 'BAB III', title: 'METODOLOGI PENELITIAN' },
                'bab4': { num: 'BAB IV', title: 'HASIL PENELITIAN DAN PEMBAHASAN' },
                'bab5': { num: 'BAB V', title: 'PENUTUP' },
                'daftar_pustaka': { num: '', title: 'DAFTAR PUSTAKA' }
            };
            const curH = chapterHeaders[activeView] || { num: '', title: chapterLabels[activeView] || 'DOKUMEN' };

            const raw = (chapterContents[activeView] || '').trim();
            if (!raw) {
                const shortLabel = curH.num ? curH.num : curH.title;
                container.innerHTML = `
            <div class="a4-sheet empty-sheet-premium" style="min-height: 500px; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; padding: 48px 32px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 4px 20px rgba(15, 23, 42, 0.04); margin-bottom: 24px;">
                <div style="width: 56px; height: 56px; border-radius: 14px; background: #f8fafc; border: 1px solid #e2e8f0; color: #1e3a8a; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 18px;">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                    </svg>
                </div>
                <div style="display: inline-block; padding: 4px 12px; border-radius: 999px; background: #f1f5f9; color: #475569; font-size: 11px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; margin-bottom: 12px;">
                    ${escapeHtml(curH.num ? (curH.num + ' · ' + curH.title) : curH.title)}
                </div>
                <h3 style="font-size: 19px; font-weight: 800; color: #0f172a; margin-bottom: 8px; letter-spacing: -0.01em;">
                    Draf ${escapeHtml(shortLabel)} Belum Diisi
                </h3>
                <p style="font-size: 13.5px; color: #64748b; max-width: 450px; margin: 0 auto 22px; line-height: 1.6;">
                    Susun draf awal ${escapeHtml(shortLabel)} secara terurut sesuai judul, metode, dan pustaka penelitian Anda.
                </p>
                <button type="button" class="btn-primary" onclick="generateCurrentView()"
                    style="height: 44px; padding: 0 24px; font-size: 13.5px; font-weight: 700; border-radius: 10px; background: #2563eb; color: #ffffff; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25); transition: background 0.15s;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 20h9"/>
                        <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                    </svg>
                    <span>Tulis ${escapeHtml(shortLabel)}</span>
                </button>
                <div style="margin-top: 20px; font-size: 12px; color: #94a3b8;">
                    Format A4 &middot; Margin 4-4-3-3 &middot; Referensi Ber-DOI
                </div>
            </div>
        `;
                return;
            }

            const pageMap = computeDocumentPagination();
            const chInfo = pageMap[activeView];
            const chStartPage = chInfo ? chInfo.startPage : 1;

            container.innerHTML = (chInfo && chInfo.pages && chInfo.pages.length)
                ? paginateChapterToSheetsHtml(activeView, '', chStartPage, chInfo.pages)
                : paginateChapterToSheetsHtml(activeView, buildChapterFullHtml(activeView), chStartPage);
            applyCurrentDocFormatting();

            container.querySelectorAll(`.paper-editor[data-chapter="${activeView}"]`).forEach(editor => {
                editor.addEventListener('input', () => {
                    isDirty = true;
                    const saveBtn = document.getElementById('btn-save-edit');
                    if (saveBtn) saveBtn.style.display = 'inline-flex';
                    const allPages = container.querySelectorAll(`.paper-editor[data-chapter="${activeView}"]`);
                    chapterContents[activeView] = Array.from(allPages).map(p => p.innerText.trim()).filter(Boolean).join('\n\n');
                });
                editor.addEventListener('keydown', (e) => {
                    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
                        e.preventDefault();
                        saveActiveEdit();
                    }
                });
            });
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

            renderLoadingSheet(`Menyusun ${chapterLabels[activeView]}...`, 'Menyusun draf naskah sesuai struktur dan referensi penelitian Anda.');
            try {
                const res = await fetch(`/project/${projectId}/generate`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ chapter: activeView, model, data_riil: researchDataRiil })
                });
                const data = await res.json();
                if (res.status === 402 || data.status === 'upgrade_required') {
                    openUpgradeModal(data.message || 'Buka seluruh bab dengan Paket Kuota mulai Rp15.000.', data.reason);
                    return;
                }
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
                renderLoadingSheet(`Menyusun Berurutan: ${chapterLabels[key]}...`, 'Menyusun setiap bab secara berurutan agar tetap konsisten.');
                try {
                    const res = await fetch(`/project/${projectId}/generate`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                        body: JSON.stringify({ chapter: key, model, data_riil: researchDataRiil })
                    });
                    const data = await res.json();
                    if (res.status === 402 || data.status === 'upgrade_required') {
                        finishLoadingSheet();
                        if (dot) dot.classList.remove('generating');
                        openUpgradeModal(data.message || 'Buka seluruh bab dengan Paket Kuota mulai Rp15.000.', data.reason);
                        break;
                    }
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

        async function upgradeToFullSkripsi() {
            allowBab45 = true;
            projectMeta.tahap_pengerjaan = 'Skripsi Lengkap (Bab I–V)';
            delete projectMeta.front_matter_html;

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

            const modeBadge = document.querySelector('.sidebar-header .project-mode-badge');
            if (modeBadge) modeBadge.textContent = 'Skripsi Penuh (Bab I–V)';
            const genAllBtn = document.getElementById('btn-generate-all');
            if (genAllBtn) genAllBtn.textContent = 'Generate Skripsi (Bab I–V)';
            const upgradeBtn = document.querySelector('.btn-auto-secondary');
            if (upgradeBtn) upgradeBtn.style.display = 'none';

            try {
                await fetch(`/project/${projectId}/guideline`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ tahap_pengerjaan: 'Skripsi Lengkap (Bab I–V)' })
                });
            } catch (e) { }

            updateProgressUI();
            selectChapterView('bab4');
            showToast('Mode diperluas & disimpan ke Full Skripsi (Bab I–V).', 'success');
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
                if (res.status === 402 || data.status === 'upgrade_required') {
                    finishLoadingSheet();
                    openUpgradeModal(data.message || 'Revisi bagian ini tersedia di Paket Starter mulai Rp15.000.', data.reason);
                    renderLabRevisi();
                    return;
                }
                if (!res.ok || data.status !== 'success') {
                    throw new Error(data.message || data.error || 'Gagal memproses revisi.');
                }
                chapterContents[chapterKey] = data.content || chapterContents[chapterKey];
                finishLoadingSheet();
                updateProgressUI();
                showToast(`Revisi ${subLabel} berhasil diterapkan!`, 'success');
                selectChapterView(chapterKey);
            } catch (err) {
                finishLoadingSheet();
                showToast(err.message || 'Terjadi kesalahan saat revisi.', 'error');
                renderLabRevisi();
            }
        }

        function extractCleanChapterTextFromPages(pagesNodeList) {
            clearActiveSentenceHighlight();
            const blocks = [];
            Array.from(pagesNodeList).forEach((pageEl) => {
                const kids = Array.from(pageEl.children);
                if (!kids.length) {
                    const t = pageEl.innerText.trim();
                    if (t) blocks.push(t);
                    return;
                }
                kids.forEach((child) => {
                    const t = (child.innerText || '').trim();
                    if (!t) return;
                    // Gabungkan kembali paragraf lanjutan lintas halaman (data-split-cont="1") agar tidak terpecah jadi 2 paragraf saat disimpan
                    if (child.getAttribute && child.getAttribute('data-split-cont') === '1' && blocks.length > 0) {
                        blocks[blocks.length - 1] = blocks[blocks.length - 1] + ' ' + t;
                    } else {
                        blocks.push(t);
                    }
                });
            });
            return blocks.join('\n\n');
        }

        async function saveActiveEdit() {
            const container = document.getElementById('sheets-container');
            if (!container || activeView === 'revisi') return;
            const btn = document.getElementById('btn-save-edit');
            const origBtnHtml = btn ? btn.innerHTML : '';
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="btn-mini-spinner"></span><span>Menyimpan...</span>';
            }

            try {
                if (activeView === 'front') {
                    const frontWrap = document.getElementById('front-sheets-wrap');
                    const htmlContent = frontWrap ? frontWrap.innerHTML : container.innerHTML;
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

                if (activeView === 'all') {
                    const keys = allowBab45
                        ? ['bab1', 'bab2', 'bab3', 'bab4', 'bab5', 'daftar_pustaka']
                        : ['bab1', 'bab2', 'bab3', 'daftar_pustaka'];
                    let savedCount = 0;
                    for (const chKey of keys) {
                        const pages = container.querySelectorAll(`.paper-editor[data-chapter="${chKey}"]`);
                        if (!pages.length) continue;
                        const textVal = extractCleanChapterTextFromPages(pages);
                        if (!textVal) continue;
                        const res = await fetch(`/project/${projectId}/save-chapter`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                            body: JSON.stringify({ chapter: chKey, content: textVal })
                        });
                        const data = await res.json();
                        if (res.ok && data.status === 'success') {
                            chapterContents[chKey] = data.content || textVal;
                            savedCount++;
                        }
                    }
                    isDirty = false;
                    if (btn) btn.style.display = 'none';
                    showToast(`Berhasil menyimpan perubahan pada ${savedCount} bagian naskah.`, 'success');
                    return;
                }

                const allPages = container.querySelectorAll(`.paper-editor[data-chapter="${activeView}"]`);
                if (!allPages.length) return;
                const htmlContent = extractCleanChapterTextFromPages(allPages);
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
                renderActiveSheet();
                showToast('Perubahan naskah disimpan & halaman otomatis dirapikan.', 'success');
            } catch (err) {
                showToast(err.message || 'Gagal menyimpan.', 'error');
            } finally {
                if (btn) {
                    btn.disabled = false;
                    if (origBtnHtml) btn.innerHTML = origBtnHtml;
                }
            }
        }

        const DOC_FONT_MAP = {
            times: { css: "'Times New Roman', Times, serif", size: '12pt', label: 'Times New Roman 12pt' },
            arial: { css: "Arial, Helvetica, sans-serif", size: '11pt', label: 'Arial 11pt' },
            georgia: { css: "Georgia, 'Times New Roman', serif", size: '11pt', label: 'Georgia 11pt' }
        };
        let activeDocFontKey = 'times';

        function applyCurrentDocFormatting() {
            const cfg = DOC_FONT_MAP[activeDocFontKey] || DOC_FONT_MAP.times;
            const top = document.getElementById('m-top')?.value || '4';
            const left = document.getElementById('m-left')?.value || '4';
            const bottom = document.getElementById('m-bottom')?.value || '3';
            const right = document.getElementById('m-right')?.value || '3';
            const isCompactViewport = window.innerWidth <= 900;

            document.querySelectorAll('.a4-sheet, .paper-editor').forEach(el => {
                el.style.fontFamily = cfg.css;
                if (el.classList.contains('paper-editor')) {
                    el.style.fontSize = isCompactViewport ? '14px' : cfg.size;
                }
                if (el.classList.contains('a4-sheet') && !el.classList.contains('empty-sheet') && !el.classList.contains('empty-sheet-premium')) {
                    el.style.padding = isCompactViewport ? '' : `${top}cm ${right}cm ${bottom}cm ${left}cm`;
                }
            });
        }

        window.addEventListener('resize', applyCurrentDocFormatting);

        function updateDocFont(val, silent = false) {
            const cfg = DOC_FONT_MAP[val] || DOC_FONT_MAP.times;
            activeDocFontKey = DOC_FONT_MAP[val] ? val : 'times';
            try { localStorage.setItem(`skripsi_font_${projectId}`, activeDocFontKey); } catch (e) { }

            const dlForm = document.getElementById('quick-download-form');
            const fontInp = dlForm ? dlForm.querySelector('input[name="font_family"]') : null;
            if (fontInp) fontInp.value = activeDocFontKey;

            applyCurrentDocFormatting();
            if (!silent) {
                showToast(`Font naskah & ekspor Word/PDF diubah ke ${cfg.label}`, 'info');
            }
        }

        function updateMargins() {
            const top = document.getElementById('m-top')?.value || document.getElementById('setting-margin-top')?.value || '4';
            const left = document.getElementById('m-left')?.value || document.getElementById('setting-margin-left')?.value || '4';
            const bottom = document.getElementById('m-bottom')?.value || document.getElementById('setting-margin-bottom')?.value || '3';
            const right = document.getElementById('m-right')?.value || document.getElementById('setting-margin-right')?.value || '3';

            const badge = document.getElementById('margin-badge-label');
            if (badge) badge.textContent = `${left}-${top}-${right}-${bottom} cm`;

            ['quick-download-form'].forEach(fId => {
                const f = document.getElementById(fId);
                if (f) {
                    if (f.querySelector('input[name="margin_top"]')) f.querySelector('input[name="margin_top"]').value = top;
                    if (f.querySelector('input[name="margin_left"]')) f.querySelector('input[name="margin_left"]').value = left;
                    if (f.querySelector('input[name="margin_bottom"]')) f.querySelector('input[name="margin_bottom"]').value = bottom;
                    if (f.querySelector('input[name="margin_right"]')) f.querySelector('input[name="margin_right"]').value = right;
                }
            });

            applyCurrentDocFormatting();
        }

        function handleSidebarLogoUpload(input) {
            if (!input.files || !input.files[0]) return;
            const file = input.files[0];
            const reader = new FileReader();
            reader.onload = function (e) {
                const img = new Image();
                img.onload = function () {
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
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, w, h);
                    ctx.drawImage(img, 0, 0, w, h);
                    const dataUrl = canvas.toDataURL('image/jpeg', 0.92);

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
            if (!@json($projectUnlocked ?? false)) {
                openUpgradeModal('Download Word (.docx) & PDF (.pdf) siap cetak tersedia untuk Paket Starter mulai Rp15.000. Pekerjaanmu sudah tersimpan aman!', 'download_locked');
                return;
            }
            updateMargins();
            const form = document.getElementById('quick-download-form');
            const coverImg = document.querySelector('.cover-logo-img');
            const activeLogo = (projectMeta && projectMeta.logo_base64) || (coverImg && coverImg.src && coverImg.src.startsWith('data:image/') ? coverImg.src : '') || localStorage.getItem(`skripsi_logo_${projectId}`) || '';
            if (activeLogo) {
                const logoInp = form.querySelector('input[name="logo_base64"]');
                if (logoInp) logoInp.value = activeLogo;
            }
            form.action = format === 'pdf'
                ? @json(route('project.download.pdf', ['id' => $project->id]))
                : @json(route('app.downloadDocx', ['id' => $project->id]));
            form.submit();
        }

        // =========================================================================
        // FITUR LAMPIRAN (UPLOAD GAMBAR / DOKUMEN / TEKS -> OTOMATIS DAFTAR ISI, WORD & PDF)
        // =========================================================================
        let projectLampiran = Array.isArray(projectMeta.lampiran) ? [...projectMeta.lampiran] : [];
        let pendingLampiranImage = '';
        let pendingLampiranFileName = '';

        function updateLampiranSidebarBadge() {
            const badge = document.getElementById('lampiran-count-badge');
            const dot = document.getElementById('dot-lampiran');
            const count = projectLampiran.length;
            if (badge) {
                badge.textContent = String(count);
                badge.style.background = count > 0 ? '#dbeafe' : '#f1f5f9';
                badge.style.color = count > 0 ? '#1d4ed8' : '#64748b';
            }
            if (dot) {
                dot.classList.toggle('done', count > 0);
            }
        }

        function getLampiranWithPages(pageMap) {
            if (!Array.isArray(projectLampiran) || projectLampiran.length === 0) return [];
            let nextPage = (pageMap && typeof pageMap._nextPageAfterMain === 'number' && pageMap._nextPageAfterMain > 0)
                ? pageMap._nextPageAfterMain
                : 1;
            if (!(pageMap && typeof pageMap._nextPageAfterMain === 'number')) {
                Object.entries(pageMap || {}).forEach(([k, pm]) => {
                    if (k === 'lampiran' || k.startsWith('_')) return;
                    if (pm && pm.pageCount > 0 && pm.endPage >= nextPage) {
                        nextPage = pm.endPage + 1;
                    }
                });
            }
            return projectLampiran.map((item, idx) => {
                const rawTitle = (item.title || '').trim() || `Dokumen Lampiran ${idx + 1}`;
                const label = /^lampiran\s+\d+/i.test(rawTitle) ? rawTitle : `Lampiran ${idx + 1} ${rawTitle}`;
                const imgHtml = item.image_base64
                    ? `<div style="text-align: center; margin: 12px 0 18px;"><img src="${item.image_base64}" alt="${escapeHtml(label)}" style="max-width: 100%; max-height: 760px; object-fit: contain; border: 1px solid #cbd5e1; border-radius: 4px;"></div>`
                    : '';
                const textHtml = item.content ? markdownToAcademicHtml(item.content, false) : '';
                const innerHtml = `
                    ${idx === 0 ? `<div class="academic-chapter-header" style="text-align: center; margin-bottom: 22px;"><div style="font-size: 14pt; font-weight: 700; letter-spacing: 0.05em;">LAMPIRAN</div></div>` : ''}
                    <h3 style="margin-top: 0; margin-bottom: 14px; font-size: 12pt; font-weight: 700;">${escapeHtml(label)}</h3>
                    ${imgHtml}
                    ${textHtml}
                `;
                const pages = splitBodyHtmlToPages(innerHtml);
                const pageCount = Math.max(1, pages.length);
                const pg = nextPage;
                nextPage += pageCount;
                return Object.assign({}, item, { index: idx + 1, label, page: pg, pageCount, pages });
            });
        }

        function buildLampiranSheetsHtml(lampiranItems) {
            if (!lampiranItems || !lampiranItems.length) return '';
            let out = '';
            lampiranItems.forEach((lmp) => {
                const pages = Array.isArray(lmp.pages) && lmp.pages.length ? lmp.pages : [''];
                pages.forEach((pageContent, pIdx) => {
                    const thisPageNum = lmp.page + pIdx;
                    out += `
                        <div class="a4-sheet" style="min-height: 1123px; display: flex; flex-direction: column; justify-content: flex-start; margin-bottom: 24px; position: relative;">
                            <div class="paper-editor">
                                ${pageContent}
                            </div>
                            <div class="sheet-footer">${thisPageNum}</div>
                        </div>
                    `;
                });
            });
            return out;
        }

        async function handleLampiranFileSelect(input) {
            if (!input.files || !input.files[0]) return;
            const file = input.files[0];
            if (file.size > 3.5 * 1024 * 1024) {
                showToast('Ukuran file lampiran maksimal 3,5 MB agar aman untuk ekspor Word & PDF.', 'error');
                input.value = '';
                return;
            }

            pendingLampiranFileName = file.name;
            const titleEl = document.getElementById('lampiran-title-input');
            if (titleEl && !titleEl.value.trim()) {
                titleEl.value = file.name.replace(/\.[^/.]+$/, '').replace(/[_-]+/g, ' ');
            }

            const statusEl = document.getElementById('lampiran-file-status');
            if (file.type && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    const img = new Image();
                    img.onload = function () {
                        const canvas = document.createElement('canvas');
                        const maxDim = 820;
                        let w = img.width;
                        let h = img.height;
                        if (w > maxDim || h > maxDim) {
                            if (w > h) { h = Math.round((h * maxDim) / w); w = maxDim; }
                            else { w = Math.round((w * maxDim) / h); h = maxDim; }
                        }
                        canvas.width = w;
                        canvas.height = h;
                        const ctx = canvas.getContext('2d');
                        ctx.fillStyle = '#ffffff';
                        ctx.fillRect(0, 0, w, h);
                        ctx.drawImage(img, 0, 0, w, h);
                        pendingLampiranImage = canvas.toDataURL('image/jpeg', 0.78);
                        if (statusEl) {
                            statusEl.innerHTML = `<div style="display:flex; align-items:center; gap:10px; padding:8px 10px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px;"><img src="${pendingLampiranImage}" style="height:44px; width:44px; object-fit:cover; border-radius:5px; border:1px solid #cbd5e1;"><span style="font-size:12px; color:#166534; font-weight:600;">✓ Gambar siap dilampirkan: ${escapeHtml(file.name)}</span></div>`;
                        }
                    };
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);
            } else {
                pendingLampiranImage = '';
                if (statusEl) {
                    statusEl.innerHTML = `<div style="padding:8px 10px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; font-size:12px; color:#1d4ed8;">⏳ Membaca isi dokumen ${escapeHtml(file.name)}...</div>`;
                }
                try {
                    const fd = new FormData();
                    fd.append('file', file);
                    const res = await fetch(`/project/${projectId}/lampiran/extract`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken },
                        body: fd
                    });
                    const data = await res.json();
                    if (!res.ok || data.status !== 'success') throw new Error(data.message || 'Gagal membaca dokumen.');
                    const contentEl = document.getElementById('lampiran-content-input');
                    if (contentEl) contentEl.value = data.content || '';
                    if (statusEl) {
                        statusEl.innerHTML = `<div style="padding:8px 10px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; font-size:12px; color:#166534; font-weight:600;">✓ Dokumen ${escapeHtml(file.name)} berhasil dibaca dan dimuat ke teks lampiran!</div>`;
                    }
                } catch (err) {
                    if (statusEl) {
                        statusEl.innerHTML = `<div style="padding:8px 10px; background:#fef2f2; border:1px solid #fecaca; border-radius:8px; font-size:12px; color:#dc2626;">⚠ ${escapeHtml(err.message || 'Gagal membaca dokumen.')}</div>`;
                    }
                }
            }
        }

        async function persistLampiranList(successMsg) {
            // Kirim raw base64 tanpa prefix data:image/... agar aman dari WAF Vercel & lebih ringan
            const payloadItems = projectLampiran.map(it => ({
                id: it.id || ('lmp_' + Date.now()),
                title: it.title || '',
                image_base64: it.image_base64 ? String(it.image_base64).replace(/^data:image\/[^;]+;base64,/i, '') : '',
                content: it.content || '',
                file_name: it.file_name || ''
            }));
            const res = await fetch(`/project/${projectId}/save-chapter`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ chapter: 'lampiran', lampiran: payloadItems })
            });
            const data = await res.json();
            if (!res.ok || data.status !== 'success') {
                throw new Error(data.message || 'Gagal menyimpan lampiran.');
            }
            projectLampiran = Array.isArray(data.lampiran) ? data.lampiran : projectLampiran;
            projectMeta.lampiran = projectLampiran;
            delete projectMeta.front_matter_html;
            updateLampiranSidebarBadge();
            if (successMsg) showToast(successMsg, 'success');
        }

        async function addLampiranItem() {
            const titleEl = document.getElementById('lampiran-title-input');
            const contentEl = document.getElementById('lampiran-content-input');
            const btn = document.getElementById('btn-add-lampiran');
            const saveLoader = document.getElementById('lampiran-save-loading');

            const title = (titleEl?.value || '').trim();
            const content = (contentEl?.value || '').trim();
            if (!title) {
                showToast('Masukkan judul lampiran terlebih dahulu (mis. Kuesioner Penelitian).', 'error');
                return;
            }
            if (!pendingLampiranImage && !content) {
                showToast('Unggah file gambar/dokumen lampiran atau isi teks lampiran terlebih dahulu.', 'error');
                return;
            }

            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="btn-mini-spinner"></span><span>Menyimpan Lampiran...</span>';
            }
            if (saveLoader) saveLoader.style.display = 'flex';
            try {
                projectLampiran.push({
                    id: 'lmp_' + Date.now(),
                    title,
                    image_base64: pendingLampiranImage,
                    content,
                    file_name: pendingLampiranFileName
                });
                await persistLampiranList('Lampiran disimpan! Daftar Isi, Daftar Lampiran, Word & PDF otomatis diperbarui.');
                pendingLampiranImage = '';
                pendingLampiranFileName = '';
                renderLampiranView();
            } catch (err) {
                projectLampiran.pop();
                showToast(err.message || 'Gagal menyimpan lampiran.', 'error');
                if (saveLoader) saveLoader.style.display = 'none';
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '+ Simpan &amp; Tambahkan Lampiran';
                }
            }
        }

        async function moveLampiranItem(idx, dir) {
            const target = idx + dir;
            if (target < 0 || target >= projectLampiran.length) return;
            const tmp = projectLampiran[idx];
            projectLampiran[idx] = projectLampiran[target];
            projectLampiran[target] = tmp;
            try {
                await persistLampiranList('Urutan lampiran diperbarui.');
                renderLampiranView();
            } catch (err) {
                showToast(err.message || 'Gagal memperbarui urutan.', 'error');
            }
        }

        async function deleteLampiranItem(idx) {
            if (!confirm('Hapus lampiran ini dari naskah?')) return;
            const removed = projectLampiran.splice(idx, 1);
            try {
                await persistLampiranList('Lampiran dihapus & Daftar Isi diperbarui.');
                renderLampiranView();
            } catch (err) {
                projectLampiran.splice(idx, 0, ...removed);
                showToast(err.message || 'Gagal menghapus lampiran.', 'error');
            }
        }

        function renderLampiranView() {
            const container = document.getElementById('sheets-container');
            const pageMap = computeDocumentPagination();
            const itemsWithPages = getLampiranWithPages(pageMap);

            const listHtml = itemsWithPages.length > 0
                ? itemsWithPages.map((item, idx) => `
                    <div class="lampiran-list-item">
                        <div class="lampiran-item-main">
                            ${item.image_base64
                                ? `<img src="${item.image_base64}" alt="${escapeHtml(item.label)}" style="width:42px; height:42px; object-fit:cover; border-radius:6px; border:1px solid #cbd5e1; flex-shrink:0;">`
                                : `<div style="width:42px; height:42px; border-radius:6px; background:#dbeafe; color:#1d4ed8; font-weight:800; font-size:13px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">L${idx + 1}</div>`}
                            <div class="lampiran-item-body">
                                <div class="lampiran-item-title">${escapeHtml(item.label)}</div>
                                <div class="lampiran-item-meta">
                                    <span class="lampiran-type-pill">${item.image_base64 ? 'Gambar' : 'Teks / Tabel'}</span>
                                    <span>Est. Hal. ${item.page}</span>
                                </div>
                                ${item.file_name ? `<div class="lampiran-filename" title="${escapeHtml(item.file_name)}">${escapeHtml(item.file_name)}</div>` : ''}
                            </div>
                        </div>
                        <div class="lampiran-item-actions">
                            <button type="button" class="btn-top" style="height:30px; padding:0 9px;" onclick="moveLampiranItem(${idx}, -1)" ${idx === 0 ? 'disabled' : ''} title="Naikkan urutan">▲</button>
                            <button type="button" class="btn-top" style="height:30px; padding:0 9px;" onclick="moveLampiranItem(${idx}, 1)" ${idx === itemsWithPages.length - 1 ? 'disabled' : ''} title="Turunkan urutan">▼</button>
                            <button type="button" class="btn-top" style="height:30px; padding:0 11px; color:#dc2626; border-color:#fecaca;" onclick="deleteLampiranItem(${idx})">Hapus</button>
                        </div>
                    </div>
                `).join('')
                : `<div style="padding:18px; text-align:center; background:#f8fafc; border:1px dashed #cbd5e1; border-radius:10px; font-size:13px; color:#64748b;">
                        Belum ada lampiran yang diunggah. Jika kamu mengunggah lampiran di atas, <strong>Daftar Lampiran</strong> otomatis muncul di Daftar Isi, Preview A4, Word (<code>.docx</code>), dan PDF (<code>.pdf</code>).
                   </div>`;

            container.innerHTML = `
                <div class="revisi-card" style="max-width: 794px; margin: 0 auto 24px;">
                    <div class="revisi-header">
                        <div class="revisi-title" style="display:flex; align-items:center; gap:8px;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                            <span>Lampiran Penelitian (${itemsWithPages.length})</span>
                        </div>
                        <span class="project-mode-badge">Auto-Sync Daftar Isi, Word &amp; PDF</span>
                    </div>

                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:16px; margin-bottom:18px;">
                        <div style="font-size:13px; font-weight:700; color:#0f172a; margin-bottom:10px;">+ Tambah Lampiran Baru</div>

                        <label class="form-label" for="lampiran-title-input">Judul Lampiran <span style="color:#dc2626;">*</span></label>
                        <input type="text" id="lampiran-title-input" class="olah-input" style="margin-bottom:12px;"
                            placeholder="cth: Kuesioner Penelitian / Output Uji SPSS / Surat Izin Penelitian / Dokumentasi Sistem">

                        <label class="form-label" for="lampiran-file-input">Unggah Berkas Lampiran (Gambar PNG/JPG/WEBP atau Dokumen Word/PDF/TXT — maks 3,5 MB)</label>
                        <input type="file" id="lampiran-file-input" class="revisi-file" accept=".jpg,.jpeg,.png,.webp,.pdf,.docx,.doc,.txt,.csv" onchange="handleLampiranFileSelect(this)" style="margin-bottom:8px;">
                        <div id="lampiran-file-status" style="margin-bottom:10px;"></div>

                        <label class="form-label" for="lampiran-content-input">Isi Teks / Tabel Lampiran (Opsional — terisi otomatis jika mengunggah dokumen Word/PDF/TXT)</label>
                        <textarea id="lampiran-content-input" class="revisi-textarea" style="min-height:95px; margin-bottom:12px;"
                            placeholder="Bisa ketik atau tempel daftar pertanyaan kuesioner, transkrip wawancara, tabel data mentah, atau kode program di sini..."></textarea>

                        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                            <span style="font-size:11.5px; color:#64748b;">Lampiran akan otomatis tercatat di <strong>Daftar Isi</strong> &amp; <strong>Daftar Lampiran</strong>, serta ikut tercetak saat unduh Word &amp; PDF.</span>
                            <button type="button" id="btn-add-lampiran" class="btn-top primary" onclick="addLampiranItem()">+ Simpan &amp; Tambahkan Lampiran</button>
                        </div>
                        <div id="lampiran-save-loading" style="display:none; align-items:center; gap:10px; margin-top:12px; padding:10px 14px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:10px; font-size:12.5px; font-weight:600; color:#1d4ed8;">
                            <span class="btn-mini-spinner-dark"></span>
                            <span>Sedang menyimpan lampiran &amp; memperbarui Daftar Isi otomatis...</span>
                        </div>
                    </div>

                    <div style="font-size:12px; font-weight:700; color:#475569; text-transform:uppercase; letter-spacing:0.04em; margin-bottom:8px;">Daftar Lampiran Tersimpan</div>
                    <div style="display:flex; flex-direction:column; gap:8px;">
                        ${listHtml}
                    </div>
                </div>
                ${itemsWithPages.length > 0 ? buildLampiranSheetsHtml(itemsWithPages) : ''}
            `;
            applyCurrentDocFormatting();
        }

        try {
            const savedFont = localStorage.getItem(`skripsi_font_${projectId}`);
            if (savedFont && DOC_FONT_MAP[savedFont]) {
                const fontSel = document.getElementById('doc-font-select');
                if (fontSel) fontSel.value = savedFont;
                updateDocFont(savedFont, true);
            }
        } catch (e) { }
        updateLampiranSidebarBadge();
        updateOlahDataIndicator();
        updateProgressUI();
        selectChapterView('all');
    </script>
    @include('partials.upgrade_checkout_modal', ['plans' => $plans ?? []])
</body>

</html>