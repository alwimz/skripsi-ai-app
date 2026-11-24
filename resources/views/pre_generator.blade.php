<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SkripsiLab AI — Workspace Penulisan Proposal, Skripsi &amp; Sidang</title>
    <link rel="icon" type="image/svg+xml" href="/images/logo.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/pptxgenjs@3.12.0/dist/pptxgen.bundle.js"></script>
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --bg-page: #f8fafc;
            --bg-sidebar: #ffffff;
            --bg-muted: #eff6ff;
            --bg-subtle: #f8fafc;
            --text-main: #0f172a;
            --text-secondary: #334155;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --border-light: #f1f5f9;
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --primary-soft: #dbeafe;
            --shadow-card: 0 1px 3px rgba(15, 23, 42, 0.04), 0 8px 24px -14px rgba(15, 23, 42, 0.08);
            --shadow-hover: 0 12px 28px -12px rgba(37, 99, 235, 0.16), 0 2px 6px rgba(15, 23, 42, 0.04);
            --ease-out: cubic-bezier(0.22, 1, 0.36, 1);
        }

        html {
            accent-color: var(--primary);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-page);
            color: var(--text-main);
            line-height: 1.55;
            -webkit-font-smoothing: antialiased;
            display: flex;
            min-height: 100vh;
            min-height: 100dvh;
        }

        :focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        /* Sidebar — Clean Blue & White Collapsible Workspace Sidebar */
        .sidebar {
            width: 248px;
            background: var(--bg-sidebar);
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

        body.sidebar-collapsed .sidebar {
            transform: translateX(-100%);
        }

        body.sidebar-collapsed .main-wrapper {
            margin-left: 0;
        }

        .sidebar-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(2px);
            z-index: 45;
            display: none;
        }

        .sidebar-backdrop.open,
        .sidebar-backdrop.show {
            display: block;
        }

        .sidebar-header {
            padding: 15px 16px;
            border-bottom: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .sidebar-brand-left {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .sidebar-close-btn {
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

        .sidebar-close-btn:hover {
            background: var(--bg-muted);
            color: var(--primary);
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
            gap: 3px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 transparent;
        }

        .nav-btn {
            width: 100%;
            min-height: 40px;
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 9px 12px;
            border-radius: 10px;
            border: none;
            background: transparent;
            color: #334155;
            font-size: 13.5px;
            font-weight: 500;
            font-family: inherit;
            cursor: pointer;
            text-align: left;
            white-space: nowrap;
            transition: background 0.16s var(--ease-out), color 0.16s var(--ease-out), transform 0.16s var(--ease-out), box-shadow 0.16s var(--ease-out);
        }

        .nav-btn:hover {
            background: #f1f5f9;
            color: var(--text-main);
        }

        .nav-btn.active {
            background: var(--bg-muted);
            color: var(--primary-hover);
            font-weight: 600;
            box-shadow: inset 3px 0 0 var(--primary);
        }

        .nav-btn svg {
            width: 18px;
            height: 18px;
            color: #64748b;
            flex-shrink: 0;
        }

        .nav-btn.active svg {
            color: var(--primary);
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
            margin-left: 248px;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            min-width: 0;
            transition: margin-left 0.22s ease;
        }

        .nav-group-title {
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #94a3b8;
            padding: 10px 12px 4px;
        }

        /* Top Bar / Breadcrumb */
        .topbar {
            min-height: 58px;
            border-bottom: 1px solid var(--border);
            padding: 0 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(12px);
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
            max-width: 1180px;
            margin: 0 auto;
            padding: 32px 32px 80px;
        }

        .tab-pane {
            display: none;
        }

        @keyframes tabFadeIn {
            from {
                opacity: 0;
                transform: translateY(6px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .tab-pane.active {
            display: block;
            animation: tabFadeIn 0.22s var(--ease-out);
        }

        .page-eyebrow {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--primary);
            margin-bottom: 6px;
        }

        .page-title {
            font-size: clamp(1.45rem, 1.15rem + 1.2vw, 1.85rem);
            font-weight: 800;
            letter-spacing: -0.028em;
            color: var(--text-main);
            line-height: 1.22;
            text-wrap: balance;
        }

        .page-subtitle {
            font-size: 14.5px;
            color: var(--text-muted);
            margin-top: 8px;
            margin-bottom: 26px;
            line-height: 1.6;
            max-width: 72ch;
            text-wrap: pretty;
        }

        /* Clean Card */
        .card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            box-shadow: var(--shadow-card);
        }

        .card-flush {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--shadow-card);
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

        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            min-height: 44px;
            font-family: inherit;
            font-size: 14px;
            color: var(--text-main);
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 11px;
            padding: 10px 14px;
            transition: border-color 0.16s var(--ease-out), box-shadow 0.16s var(--ease-out), background 0.16s ease;
        }

        .form-input:hover,
        .form-select:hover,
        .form-textarea:hover {
            border-color: #94a3b8;
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3.5px rgba(37, 99, 235, 0.13);
        }

        .form-input:user-invalid,
        .form-select:user-invalid,
        .form-textarea:user-invalid {
            border-color: #dc2626;
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
            min-height: 42px;
            background: linear-gradient(180deg, #2563eb 0%, #1d4ed8 100%);
            color: #ffffff;
            border: none;
            border-radius: 11px;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            box-shadow: 0 6px 16px -6px rgba(37, 99, 235, 0.45);
            transition: transform 0.16s var(--ease-out), box-shadow 0.16s var(--ease-out), filter 0.16s ease;
        }

        .btn-primary:hover:not(:disabled) {
            filter: brightness(1.04);
            transform: translateY(-1px);
            box-shadow: 0 10px 22px -6px rgba(37, 99, 235, 0.5);
        }

        .btn-primary:active:not(:disabled) {
            transform: translateY(0) scale(0.985);
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
            min-height: 38px;
            background: #ffffff;
            color: var(--text-main);
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease, transform 0.15s var(--ease-out);
        }

        .btn-outline:hover {
            background: var(--bg-muted);
            border-color: #93c5fd;
            color: var(--primary-hover);
        }

        .btn-outline:active {
            transform: scale(0.985);
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
            flex: 1;
            min-width: 0;
            padding-right: 12px;
        }

        .project-row-action {
            font-size: 13px;
            font-weight: 600;
            color: var(--primary);
            white-space: nowrap;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            gap: 4px;
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
            line-height: 1.4;
            word-break: break-word;
            white-space: normal;
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
            gap: 16px;
            margin-top: 20px;
        }

        .dash-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 20px 22px;
            cursor: pointer;
            box-shadow: var(--shadow-card);
            transition: border-color 0.18s var(--ease-out), box-shadow 0.18s var(--ease-out), transform 0.18s var(--ease-out);
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }

        .dash-card:hover {
            border-color: #93c5fd;
            box-shadow: var(--shadow-hover);
            transform: translateY(-2px);
        }

        .dash-card:active {
            transform: translateY(0) scale(0.99);
        }

        .dash-icon {
            width: 42px;
            height: 42px;
            border-radius: 11px;
            background: var(--bg-muted);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: background 0.18s var(--ease-out), color 0.18s var(--ease-out);
        }

        .dash-card:hover .dash-icon {
            background: var(--primary);
            color: #ffffff;
        }

        .dash-card h3 {
            font-size: 15px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 5px;
            letter-spacing: -0.01em;
        }

        .dash-card p {
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.5;
            text-wrap: pretty;
        }

        /* Interactive PPT Studio (16:9 Slide Canvas & 13 Premium Designer Templates) */
        .ppt-canvas {
            --ppt-accent: #2563eb;
            --ppt-accent-2: #1d4ed8;
            --ppt-text: #0f172a;
            --ppt-muted: #475569;
            --ppt-badge-bg: #dbeafe;
            --ppt-badge-fg: #1d4ed8;
            --ppt-card-bg: #ffffff;
            --ppt-card-border: #cbd5e1;
            --ppt-card-text: #0f172a;
            --ppt-card-label: #2563eb;
            --ppt-bullet-bg: rgba(255, 255, 255, 0.94);
            --ppt-bullet-border: #dbeafe;
            --ppt-divider: rgba(37, 99, 235, 0.25);

            border-radius: 16px;
            padding: clamp(18px, 2.8vw, 32px) clamp(20px, 3.2vw, 38px);
            width: 100%;
            max-width: 920px;
            aspect-ratio: 16 / 9;
            margin: 0 auto;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border: 1px solid var(--border);
            position: relative;
            overflow: hidden;
            box-shadow: 0 14px 36px rgba(15, 23, 42, 0.10), 0 2px 6px rgba(15, 23, 42, 0.04);
            transition: all 0.22s ease;
            color: var(--ppt-text);
        }

        .ppt-canvas > div {
            position: relative;
            z-index: 2;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 0;
        }

        .ppt-canvas::after {
            content: '';
            position: absolute;
            width: 240px;
            height: 240px;
            border-radius: 50%;
            right: -85px;
            bottom: -95px;
            background: radial-gradient(circle, var(--ppt-accent) 0%, transparent 72%);
            opacity: 0.10;
            pointer-events: none;
            z-index: 1;
        }

        /* 1. Sidang Crimson Pro (Deep Burgundy Editorial) */
        .ppt-theme-sidang_crimson {
            --ppt-accent: #fb7185;
            --ppt-accent-2: #fda4af;
            --ppt-text: #ffffff;
            --ppt-muted: #ffe4e6;
            --ppt-badge-bg: rgba(255, 255, 255, 0.20);
            --ppt-badge-fg: #ffffff;
            --ppt-card-bg: rgba(255, 255, 255, 0.14);
            --ppt-card-border: rgba(255, 255, 255, 0.32);
            --ppt-card-text: #ffffff;
            --ppt-card-label: #fecdd3;
            --ppt-bullet-bg: rgba(255, 255, 255, 0.14);
            --ppt-bullet-border: rgba(255, 255, 255, 0.28);
            --ppt-divider: rgba(255, 255, 255, 0.35);
            background: linear-gradient(135deg, #881337 0%, #be123c 52%, #e11d48 100%);
            border: 1px solid #9f1239;
            padding-top: 28px;
        }
        .ppt-theme-sidang_crimson::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 8px;
            background: linear-gradient(90deg, #fda4af 0%, #ffffff 50%, #fb7185 100%);
            z-index: 3;
        }

        /* 2. Royal Blue Kampus (Executive Academic Blue) */
        .ppt-theme-royal_academic {
            --ppt-accent: #2563eb;
            --ppt-accent-2: #1e40af;
            --ppt-text: #0f172a;
            --ppt-muted: #475569;
            --ppt-badge-bg: #dbeafe;
            --ppt-badge-fg: #1d4ed8;
            --ppt-card-bg: #ffffff;
            --ppt-card-border: #bfdbfe;
            --ppt-card-text: #0f172a;
            --ppt-card-label: #1d4ed8;
            --ppt-bullet-bg: #ffffff;
            --ppt-bullet-border: #bfdbfe;
            --ppt-divider: #93c5fd;
            background: linear-gradient(145deg, #ffffff 0%, #f1f7ff 58%, #e6f0ff 100%);
            padding-left: clamp(30px, 4.5vw, 52px);
            border: 1px solid #bfdbfe;
        }
        .ppt-theme-royal_academic::before {
            content: '';
            position: absolute;
            top: 0; bottom: 0; left: 0;
            width: 16px;
            background: linear-gradient(180deg, #1d4ed8 0%, #2563eb 55%, #38bdf8 100%);
            z-index: 3;
        }

        /* 3. Canva Cream Editorial (Warm Ivory & Terracotta Serif) */
        .ppt-theme-canva_editorial {
            --ppt-accent: #c2410c;
            --ppt-accent-2: #ea580c;
            --ppt-text: #1c1917;
            --ppt-muted: #57534e;
            --ppt-badge-bg: #ffedd5;
            --ppt-badge-fg: #9a3412;
            --ppt-card-bg: #ffffff;
            --ppt-card-border: #fed7aa;
            --ppt-card-text: #292524;
            --ppt-card-label: #c2410c;
            --ppt-bullet-bg: #ffffff;
            --ppt-bullet-border: #f5d0a9;
            --ppt-divider: #fdba74;
            background: linear-gradient(140deg, #fdfaf5 0%, #faf3e8 100%);
            border: 1px solid #e7d8c5;
            padding-top: 28px;
        }
        .ppt-theme-canva_editorial::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 9px;
            background: linear-gradient(90deg, #9a3412 0%, #ea580c 55%, #f59e0b 100%);
            z-index: 3;
        }
        .ppt-theme-canva_editorial #slide-title-edit,
        .ppt-theme-canva_editorial #fs-slide-title {
            font-family: Georgia, 'Times New Roman', serif;
        }

        /* 4. Emerald Botanical (Forest Green & Gold Trim) */
        .ppt-theme-emerald_executive {
            --ppt-accent: #047857;
            --ppt-accent-2: #d97706;
            --ppt-text: #064e3b;
            --ppt-muted: #3f6258;
            --ppt-badge-bg: #d1fae5;
            --ppt-badge-fg: #065f46;
            --ppt-card-bg: #ffffff;
            --ppt-card-border: #a7f3d0;
            --ppt-card-text: #064e3b;
            --ppt-card-label: #047857;
            --ppt-bullet-bg: #ffffff;
            --ppt-bullet-border: #a7f3d0;
            --ppt-divider: #6ee7b7;
            background: linear-gradient(145deg, #ffffff 0%, #ecfdf5 65%, #d1fae5 100%);
            border: 1px solid #a7f3d0;
            padding-top: 28px;
        }
        .ppt-theme-emerald_executive::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 10px;
            background: linear-gradient(90deg, #065f46 0%, #059669 72%, #d97706 100%);
            z-index: 3;
        }

        /* 5. Swiss Grid Minimal (Architectural Monochrome Editorial) */
        .ppt-theme-swiss_minimal {
            --ppt-accent: #111827;
            --ppt-accent-2: #374151;
            --ppt-text: #111827;
            --ppt-muted: #4b5563;
            --ppt-badge-bg: #111827;
            --ppt-badge-fg: #ffffff;
            --ppt-card-bg: #f9fafb;
            --ppt-card-border: #d1d5db;
            --ppt-card-text: #111827;
            --ppt-card-label: #111827;
            --ppt-bullet-bg: #f9fafb;
            --ppt-bullet-border: #d1d5db;
            --ppt-divider: #111827;
            background: #ffffff;
            background-image: radial-gradient(#e5e7eb 1px, transparent 1px);
            background-size: 22px 22px;
            border: 2px solid #111827;
            padding-top: 28px;
        }
        .ppt-theme-swiss_minimal::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 9px;
            background: #111827;
            z-index: 3;
        }

        /* 6. Basic Navy (Executive Midnight Indigo) */
        .ppt-theme-basic_navy {
            --ppt-accent: #818cf8;
            --ppt-accent-2: #38bdf8;
            --ppt-text: #ffffff;
            --ppt-muted: #c7d2fe;
            --ppt-badge-bg: rgba(129, 140, 248, 0.24);
            --ppt-badge-fg: #e0e7ff;
            --ppt-card-bg: rgba(255, 255, 255, 0.09);
            --ppt-card-border: rgba(165, 180, 252, 0.34);
            --ppt-card-text: #f8fafc;
            --ppt-card-label: #a5b4fc;
            --ppt-bullet-bg: rgba(255, 255, 255, 0.09);
            --ppt-bullet-border: rgba(165, 180, 252, 0.28);
            --ppt-divider: rgba(165, 180, 252, 0.35);
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 58%, #312e81 100%);
            border: 1px solid #3730a3;
            padding-top: 28px;
        }
        .ppt-theme-basic_navy::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 8px;
            background: linear-gradient(90deg, #6366f1 0%, #818cf8 55%, #38bdf8 100%);
            z-index: 3;
        }

        /* 7. Basic Putih (Ultra-Clean Academic Projector) */
        .ppt-theme-basic_white {
            --ppt-accent: #334155;
            --ppt-accent-2: #2563eb;
            --ppt-text: #0f172a;
            --ppt-muted: #475569;
            --ppt-badge-bg: #f1f5f9;
            --ppt-badge-fg: #1e293b;
            --ppt-card-bg: #f8fafc;
            --ppt-card-border: #cbd5e1;
            --ppt-card-text: #0f172a;
            --ppt-card-label: #334155;
            --ppt-bullet-bg: #f8fafc;
            --ppt-bullet-border: #cbd5e1;
            --ppt-divider: #cbd5e1;
            background: #ffffff;
            border: 1.5px solid #cbd5e1;
            padding-top: 26px;
        }
        .ppt-theme-basic_white::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 6px;
            background: linear-gradient(90deg, #334155 0%, #64748b 100%);
            z-index: 3;
        }

        /* 8. Maroon Prestige (Burgundy Academic Heritage) */
        .ppt-theme-maroon_prestige {
            --ppt-accent: #991b1b;
            --ppt-accent-2: #b45309;
            --ppt-text: #450a0a;
            --ppt-muted: #7f1d1d;
            --ppt-badge-bg: #fee2e2;
            --ppt-badge-fg: #991b1b;
            --ppt-card-bg: #ffffff;
            --ppt-card-border: #fecaca;
            --ppt-card-text: #450a0a;
            --ppt-card-label: #991b1b;
            --ppt-bullet-bg: #ffffff;
            --ppt-bullet-border: #fecaca;
            --ppt-divider: #fca5a5;
            background: linear-gradient(145deg, #ffffff 0%, #fff5f5 62%, #ffe4e6 100%);
            border: 1px solid #fecaca;
            padding-top: 28px;
        }
        .ppt-theme-maroon_prestige::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 10px;
            background: linear-gradient(90deg, #7f1d1d 0%, #991b1b 65%, #d97706 100%);
            z-index: 3;
        }

        /* 9. Dark Tech Cyber (Modern AI / Informatika) */
        .ppt-theme-tech_dark {
            --ppt-accent: #06b6d4;
            --ppt-accent-2: #3b82f6;
            --ppt-text: #f8fafc;
            --ppt-muted: #94a3b8;
            --ppt-badge-bg: rgba(6, 182, 212, 0.18);
            --ppt-badge-fg: #38bdf8;
            --ppt-card-bg: rgba(30, 41, 59, 0.85);
            --ppt-card-border: rgba(56, 189, 248, 0.35);
            --ppt-card-text: #f8fafc;
            --ppt-card-label: #38bdf8;
            --ppt-bullet-bg: rgba(15, 23, 42, 0.82);
            --ppt-bullet-border: rgba(56, 189, 248, 0.30);
            --ppt-divider: rgba(56, 189, 248, 0.35);
            background: linear-gradient(135deg, #070b14 0%, #0f172a 60%, #111c33 100%);
            border: 1px solid #1e293b;
            padding-top: 28px;
        }
        .ppt-theme-tech_dark::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 8px;
            background: linear-gradient(90deg, #0284c7 0%, #06b6d4 50%, #818cf8 100%);
            z-index: 3;
        }

        /* 10. Lavender Academic (Modern Soft Violet) */
        .ppt-theme-pastel_lavender {
            --ppt-accent: #7c3aed;
            --ppt-accent-2: #a855f7;
            --ppt-text: #2e1065;
            --ppt-muted: #5b21b6;
            --ppt-badge-bg: #ede9fe;
            --ppt-badge-fg: #6d28d9;
            --ppt-card-bg: #ffffff;
            --ppt-card-border: #ddd6fe;
            --ppt-card-text: #2e1065;
            --ppt-card-label: #6d28d9;
            --ppt-bullet-bg: #ffffff;
            --ppt-bullet-border: #ddd6fe;
            --ppt-divider: #c4b5fd;
            background: linear-gradient(145deg, #ffffff 0%, #f5f3ff 62%, #ede9fe 100%);
            border: 1px solid #ddd6fe;
            padding-top: 28px;
        }
        .ppt-theme-pastel_lavender::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 9px;
            background: linear-gradient(90deg, #6d28d9 0%, #8b5cf6 55%, #c084fc 100%);
            z-index: 3;
        }

        /* 11. Nordic Slate Teal (Scandinavian Clean Teal) */
        .ppt-theme-nordic_teal {
            --ppt-accent: #0f766e;
            --ppt-accent-2: #14b8a6;
            --ppt-text: #134e4a;
            --ppt-muted: #3b6966;
            --ppt-badge-bg: #ccfbf1;
            --ppt-badge-fg: #0f766e;
            --ppt-card-bg: #ffffff;
            --ppt-card-border: #99f6e4;
            --ppt-card-text: #134e4a;
            --ppt-card-label: #0f766e;
            --ppt-bullet-bg: #ffffff;
            --ppt-bullet-border: #99f6e4;
            --ppt-divider: #5eead4;
            background: linear-gradient(145deg, #ffffff 0%, #f0fdfa 60%, #ccfbf1 100%);
            border: 1px solid #99f6e4;
            padding-top: 28px;
        }
        .ppt-theme-nordic_teal::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 9px;
            background: linear-gradient(90deg, #115e59 0%, #0d9488 55%, #2dd4bf 100%);
            z-index: 3;
        }

        /* 12. Warm Terracotta Sand (Amber Academic Modern) */
        .ppt-theme-warm_terracotta {
            --ppt-accent: #b45309;
            --ppt-accent-2: #ea580c;
            --ppt-text: #451a03;
            --ppt-muted: #78350f;
            --ppt-badge-bg: #fef3c7;
            --ppt-badge-fg: #b45309;
            --ppt-card-bg: #ffffff;
            --ppt-card-border: #fde68a;
            --ppt-card-text: #451a03;
            --ppt-card-label: #b45309;
            --ppt-bullet-bg: #ffffff;
            --ppt-bullet-border: #fed7aa;
            --ppt-divider: #fcd34d;
            background: linear-gradient(145deg, #fffdf7 0%, #fffbeb 62%, #fef3c7 100%);
            border: 1px solid #fde68a;
            padding-top: 28px;
        }
        .ppt-theme-warm_terracotta::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 9px;
            background: linear-gradient(90deg, #92400e 0%, #d97706 55%, #f97316 100%);
            z-index: 3;
        }

        /* 13. Oxford Navy & Gold (Luxury Academic Prestige) */
        .ppt-theme-classic_gold {
            --ppt-accent: #fbbf24;
            --ppt-accent-2: #d97706;
            --ppt-text: #f8fafc;
            --ppt-muted: #cbd5e1;
            --ppt-badge-bg: rgba(251, 191, 36, 0.18);
            --ppt-badge-fg: #fbbf24;
            --ppt-card-bg: rgba(30, 41, 59, 0.86);
            --ppt-card-border: rgba(251, 191, 36, 0.42);
            --ppt-card-text: #f8fafc;
            --ppt-card-label: #fbbf24;
            --ppt-bullet-bg: rgba(30, 41, 59, 0.85);
            --ppt-bullet-border: rgba(251, 191, 36, 0.34);
            --ppt-divider: rgba(251, 191, 36, 0.42);
            background: linear-gradient(135deg, #091124 0%, #0f172a 58%, #1e293b 100%);
            border: 1px solid #334155;
            padding-top: 28px;
        }
        .ppt-theme-classic_gold::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 9px;
            background: linear-gradient(90deg, #b45309 0%, #fbbf24 50%, #f59e0b 100%);
            z-index: 3;
        }

        /* Unified Theme-Driven Typography, Badges & Adaptive Bullet Cards */
        .ppt-canvas .ppt-badge {
            background: var(--ppt-badge-bg);
            color: var(--ppt-badge-fg);
            border: 1px solid var(--ppt-divider);
        }

        .ppt-canvas #slide-title-edit,
        .ppt-canvas #fs-slide-title {
            color: var(--ppt-text);
            margin-bottom: 4px !important;
        }

        .ppt-canvas #slide-subtitle-edit,
        .ppt-canvas #fs-slide-subtitle {
            color: var(--ppt-muted);
            margin-bottom: 12px !important;
        }

        .ppt-canvas #slide-bullets-edit,
        .ppt-canvas #fs-slide-bullets {
            list-style: none !important;
            padding-left: 0 !important;
            margin: 0 !important;
            display: grid !important;
            grid-template-columns: 1fr;
            gap: 8px;
            flex: 1;
            align-content: start;
        }

        .ppt-canvas #slide-bullets-edit.ppt-bullets-two-col,
        .ppt-canvas #fs-slide-bullets.ppt-bullets-two-col {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 9px !important;
        }

        .ppt-canvas #slide-bullets-edit li:not(.ppt-cover-pembimbing-li),
        .ppt-canvas #fs-slide-bullets li:not(.ppt-cover-pembimbing-li) {
            background: var(--ppt-bullet-bg);
            border: 1px solid var(--ppt-bullet-border);
            border-left: 3.5px solid var(--ppt-accent);
            color: var(--ppt-card-text);
            padding: 8px 12px;
            border-radius: 9px;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04);
            line-height: 1.42;
            font-size: 13px;
            word-break: break-word;
            box-sizing: border-box;
        }

        .ppt-canvas.ppt-density-compact #slide-title-edit,
        .ppt-canvas.ppt-density-compact #fs-slide-title {
            font-size: 19px !important;
            line-height: 1.24 !important;
        }
        .ppt-canvas.ppt-density-compact #slide-subtitle-edit,
        .ppt-canvas.ppt-density-compact #fs-slide-subtitle {
            font-size: 12.5px !important;
            margin-bottom: 8px !important;
        }
        .ppt-canvas.ppt-density-compact #slide-bullets-edit li:not(.ppt-cover-pembimbing-li),
        .ppt-canvas.ppt-density-compact #fs-slide-bullets li:not(.ppt-cover-pembimbing-li) {
            font-size: 11.5px !important;
            line-height: 1.32 !important;
            padding: 6px 10px !important;
        }

        .ppt-canvas.ppt-density-dense #slide-title-edit,
        .ppt-canvas.ppt-density-dense #fs-slide-title {
            font-size: 16.5px !important;
            line-height: 1.2 !important;
        }
        .ppt-canvas.ppt-density-dense #slide-subtitle-edit,
        .ppt-canvas.ppt-density-dense #fs-slide-subtitle {
            font-size: 11px !important;
            margin-bottom: 6px !important;
        }
        .ppt-canvas.ppt-density-dense #slide-bullets-edit li:not(.ppt-cover-pembimbing-li),
        .ppt-canvas.ppt-density-dense #fs-slide-bullets li:not(.ppt-cover-pembimbing-li) {
            font-size: 10.2px !important;
            line-height: 1.25 !important;
            padding: 5px 8px !important;
        }

        .ppt-canvas.ppt-density-micro #slide-title-edit,
        .ppt-canvas.ppt-density-micro #fs-slide-title {
            font-size: 14.5px !important;
            line-height: 1.18 !important;
        }
        .ppt-canvas.ppt-density-micro #slide-subtitle-edit,
        .ppt-canvas.ppt-density-micro #fs-slide-subtitle {
            font-size: 10px !important;
            margin-bottom: 5px !important;
        }
        .ppt-canvas.ppt-density-micro #slide-bullets-edit li:not(.ppt-cover-pembimbing-li),
        .ppt-canvas.ppt-density-micro #fs-slide-bullets li:not(.ppt-cover-pembimbing-li) {
            font-size: 9.2px !important;
            line-height: 1.2 !important;
            padding: 4px 7px !important;
        }

        /* Cover Slide 1 Typography & Balanced 16:9 Vertical Composition */
        .ppt-canvas.ppt-is-cover > div {
            justify-content: space-between;
        }
        .ppt-canvas.ppt-is-cover #slide-title-edit,
        .ppt-canvas.ppt-is-cover #fs-slide-title {
            margin-top: auto !important;
            margin-bottom: 10px !important;
        }
        .ppt-canvas.ppt-is-cover #slide-subtitle-edit,
        .ppt-canvas.ppt-is-cover #fs-slide-subtitle {
            margin-bottom: auto !important;
        }
        .ppt-canvas.ppt-is-cover #slide-bullets-edit,
        .ppt-canvas.ppt-is-cover #fs-slide-bullets {
            flex: 0 0 auto !important;
            margin-top: 12px !important;
        }
        .ppt-cover-title-text {
            font-size: clamp(18px, 2.4vw, 25px);
            font-weight: 800;
            line-height: 1.28;
            max-width: 90%;
            margin: 6px auto 0;
            color: var(--ppt-text);
            letter-spacing: -0.015em;
        }
        .ppt-cover-title-text.ppt-title-long {
            font-size: clamp(16px, 2.05vw, 21.5px);
            line-height: 1.28;
        }
        .ppt-cover-divider {
            width: 78px;
            height: 3.5px;
            border-radius: 999px;
            background: var(--ppt-accent);
            margin: 12px auto 6px;
        }
        .ppt-cover-oleh-box {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            padding: 8px 26px;
            border-radius: 11px;
            background: var(--ppt-card-bg);
            border: 1px solid var(--ppt-card-border);
            margin: 4px auto 6px;
        }
        .ppt-cover-oleh-tag {
            font-size: 9.5px;
            font-weight: 700;
            letter-spacing: 0.1em;
            color: var(--ppt-card-label);
            text-transform: uppercase;
        }
        .ppt-cover-author-name {
            font-size: 14.5px;
            font-weight: 800;
            color: var(--ppt-card-text);
            margin-top: 3px;
        }
        .ppt-cover-nim {
            font-size: 11.5px;
            font-weight: 600;
            color: var(--ppt-muted);
            margin-top: 2px;
        }
        .ppt-cover-footer-inst {
            text-align: center;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--ppt-muted);
            margin-top: 12px;
            padding-top: 9px;
            border-top: 1px solid var(--ppt-divider);
        }

        /* PPT Slide Smooth Animation */
        @keyframes pptSlideFadeIn {
            0% {
                opacity: 0;
                transform: translateY(10px) scale(0.985);
            }

            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .ppt-slide-anim {
            animation: pptSlideFadeIn 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes slideInFade {
            from {
                opacity: 0;
                transform: translateY(6px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .ppt-canvas>div {
            animation: slideInFade 0.25s ease-out;
        }

        .ppt-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 11px;
            border-radius: 999px;
            font-size: 11.5px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        /* Template Card Selector */
        .theme-card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(115px, 1fr));
            gap: 10px;
        }

        .theme-chip {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 6px;
            padding: 8px 10px;
            border-radius: 10px;
            border: 1.5px solid var(--border);
            background: #ffffff;
            cursor: pointer;
            font-family: inherit;
            text-align: left;
            transition: all 0.15s ease;
        }

        .theme-chip:hover {
            border-color: #94a3b8;
        }

        .theme-chip.active {
            border-color: var(--primary);
            background: #eff6ff;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
        }

        .theme-swatch {
            width: 100%;
            height: 26px;
            border-radius: 5px;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.08);
        }

        .theme-chip-name {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-main);
            line-height: 1.2;
        }

        /* Global PPT Slide 1 Cover Styles: Pembimbing & Penguji Side-by-Side */
        #slide-bullets-edit li.ppt-cover-pembimbing-li,
        #fs-slide-bullets li.ppt-cover-pembimbing-li,
        .ppt-cover-pembimbing-li {
            background: transparent !important;
            border: none !important;
            padding: 0 !important;
            margin: 6px 0 0 0 !important;
            list-style: none !important;
            list-style-type: none !important;
            width: 100% !important;
            box-shadow: none !important;
        }

        #slide-bullets-edit li.ppt-cover-pembimbing-li::marker,
        #fs-slide-bullets li.ppt-cover-pembimbing-li::marker,
        .ppt-cover-pembimbing-li::marker {
            content: "" !important;
            display: none !important;
        }

        .ppt-pembimbing-grid {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 14px !important;
            width: 92% !important;
            margin: 2px auto 0 !important;
            box-sizing: border-box !important;
        }

        .ppt-pembimbing-card {
            background: var(--ppt-card-bg) !important;
            border: 1px solid var(--ppt-card-border) !important;
            border-top: 3px solid var(--ppt-accent) !important;
            box-shadow: 0 3px 10px rgba(15, 23, 42, 0.06) !important;
            border-radius: 10px !important;
            padding: 10px 14px !important;
            box-sizing: border-box !important;
            text-align: left !important;
            color: var(--ppt-card-text) !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: center !important;
        }

        .ppt-pembimbing-label {
            font-size: 9.5px !important;
            font-weight: 800 !important;
            color: var(--ppt-card-label) !important;
            text-transform: uppercase !important;
            letter-spacing: 0.08em !important;
            margin-bottom: 5px !important;
        }

        .ppt-pembimbing-name {
            font-size: 12px !important;
            font-weight: 600 !important;
            color: var(--ppt-card-text) !important;
            line-height: 1.35 !important;
            word-break: break-word !important;
        }

        /* External Slide Navigation Bar (Outside Editor) */
        .ppt-nav-outside {
            margin-top: 14px;
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

        .ppt-nav-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 16px;
            border-radius: 9px;
            border: 1px solid var(--border);
            background: #ffffff;
            color: var(--text-main);
            font-size: 13px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .ppt-nav-btn:hover:not(:disabled) {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
        }

        .ppt-nav-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .ppt-num-strip {
            display: flex;
            align-items: center;
            gap: 5px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .ppt-num-btn {
            width: 30px;
            height: 30px;
            border-radius: 7px;
            border: 1px solid var(--border);
            background: #f8fafc;
            color: var(--text-secondary);
            font-size: 12px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
        }

        .ppt-num-btn.active {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
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

        .sidebar-toggle-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
            border: 1px solid var(--border);
            color: var(--text-secondary);
            border-radius: 8px;
            width: 34px;
            height: 34px;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .sidebar-toggle-btn:hover {
            background: var(--bg-muted);
            color: var(--primary);
            border-color: #bfdbfe;
        }

        /* Universal Loading Animation CSS */
        @keyframes aiSpin {
            to {
                transform: rotate(360deg);
            }
        }

        @keyframes aiShimmer {
            0% {
                background-position: 200% 0;
            }

            100% {
                background-position: -200% 0;
            }
        }

        .ai-spinner {
            width: 16px;
            height: 16px;
            border: 2.2px solid rgba(255, 255, 255, 0.35);
            border-top-color: #ffffff;
            border-radius: 50%;
            display: inline-block;
            animation: aiSpin 0.7s linear infinite;
            flex-shrink: 0;
        }

        .ai-spinner-blue {
            width: 20px;
            height: 20px;
            border: 2.5px solid #dbeafe;
            border-top-color: #2563eb;
            border-radius: 50%;
            display: inline-block;
            animation: aiSpin 0.75s linear infinite;
            flex-shrink: 0;
        }

        .ai-loading-card {
            background: #ffffff;
            border: 1px solid #bfdbfe;
            border-radius: 12px;
            padding: 20px;
            margin-top: 16px;
            box-shadow: 0 4px 16px rgba(37, 99, 235, 0.06);
        }

        .ai-loading-head {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;
        }

        .ai-loading-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--text-main);
        }

        .ai-loading-sub {
            font-size: 12.5px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .ai-shimmer-line {
            height: 10px;
            border-radius: 999px;
            background: linear-gradient(90deg, #eff6ff 25%, #dbeafe 50%, #eff6ff 75%);
            background-size: 200% 100%;
            animation: aiShimmer 1.4s ease-in-out infinite;
            margin-bottom: 9px;
        }

        .scope-badge-id {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 999px;
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .scope-badge-int {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 999px;
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
        }

        @media (min-width: 901px) {
            .sidebar-close-btn {
                display: none !important;
            }

            .sidebar-toggle-btn {
                display: none !important;
            }
        }

        @media (max-width: 900px) {
            .sidebar {
                transform: translateX(-100%);
                box-shadow: 0 20px 45px rgba(15, 23, 42, 0.18);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main-wrapper {
                margin-left: 0 !important;
                width: 100%;
            }

            .topbar {
                padding: 0 14px;
            }

            .page-content {
                padding: 22px 16px 64px;
            }

            .form-row,
            .dash-grid,
            .responsive-grid-2,
            .responsive-grid-3,
            .responsive-grid-title-logo {
                grid-template-columns: 1fr !important;
            }

            .target-choice-card {
                padding: 12px 14px !important;
                gap: 8px !important;
            }

            .theme-card-grid {
                display: grid !important;
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 8px !important;
            }

            @media (max-width: 440px) {
                .theme-card-grid {
                    grid-template-columns: repeat(2, 1fr) !important;
                    gap: 6px !important;
                }
            }

            .theme-chip {
                padding: 8px 6px !important;
                border-radius: 8px !important;
                align-items: center !important;
                text-align: center !important;
                box-sizing: border-box !important;
                min-width: 0 !important;
            }

            .theme-swatch {
                width: 100% !important;
                height: 24px !important;
                border-radius: 4px !important;
            }

            .theme-chip-name {
                font-size: 10px !important;
                font-weight: 600 !important;
                text-align: center !important;
                width: 100% !important;
                white-space: normal !important;
                word-break: break-word !important;
                line-height: 1.2 !important;
            }

            .ppt-canvas {
                padding: 24px 20px;
                min-height: 290px;
            }

            .ppt-nav-outside {
                display: grid !important;
                grid-template-columns: 1fr 1fr !important;
                gap: 8px !important;
                padding: 10px 12px !important;
            }

            .ppt-nav-outside #btn-ppt-prev {
                grid-column: 1 / 2 !important;
                width: 100% !important;
                justify-content: center !important;
                order: 1 !important;
            }

            .ppt-nav-outside #btn-ppt-next {
                grid-column: 2 / 3 !important;
                width: 100% !important;
                justify-content: center !important;
                order: 2 !important;
            }

            .ppt-nav-outside #ppt-num-strip {
                grid-column: 1 / -1 !important;
                width: 100% !important;
                justify-content: center !important;
                overflow-x: auto !important;
                order: 3 !important;
                padding-top: 4px !important;
                gap: 4px !important;
            }

            #ppt-grid-preview-list {
                grid-template-columns: 1fr !important;
            }
        }

        @media (max-width: 640px) {
            body {
                overflow-x: hidden;
            }

            .form-input,
            .form-select,
            .form-textarea {
                font-size: 16px;
                min-height: 46px;
                font-size: 13.5px;
                min-height: 39px;
                padding: 8px 11px;
                border-radius: 9px;
            }

            .form-label {
                font-size: 12px;
                margin-bottom: 5px;
            }

            .form-group,
            .form-row {
                margin-bottom: 12px;
                gap: 10px;
            }

            .btn-primary {
                min-height: 40px;
                padding: 9px 16px;
                font-size: 13.5px;
            }

            .topbar {
                height: 52px;
                min-height: 52px;
                padding: 0 10px;
                flex-wrap: nowrap;
                justify-content: space-between;
                gap: 6px;
            }

            .topbar-right {
                width: auto;
                justify-content: flex-end;
                gap: 6px;
                flex-shrink: 0;
            }

            .topbar-right .token-pill {
                display: none !important;
            }

            .model-select {
                max-width: 125px;
                font-size: 11px;
                padding: 4px 6px;
                flex-shrink: 0;
            }

            .breadcrumb {
                font-size: 12px;
                gap: 5px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                min-width: 0;
                flex: 1;
            }

            .breadcrumb>span:not(.breadcrumb-current) {
                display: none !important;
            }

            .breadcrumb-current {
                max-width: 100%;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                font-weight: 700;
            }

            .page-content {
                padding: 18px 12px 64px;
            }

            .page-title {
                font-size: 21px;
            }

            .card {
                padding: 15px 14px;
                border-radius: 14px;
            }

            .ppt-identity-box {
                background: transparent !important;
                border: none !important;
                padding: 0 !important;
            }

            .ppt-slide-count-head {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 6px !important;
            }

            .ppt-slide-count-controls {
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 10px !important;
            }

            #ppt-select-slide-count {
                width: 100% !important;
            }

            .card-header-bar {
                flex-wrap: wrap;
                gap: 10px;
                padding: 12px 14px;
            }

            .card-footer-bar {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }

            .card-footer-bar .btn-primary,
            .card-footer-bar .btn-outline {
                width: 100%;
                justify-content: center;
            }

            .project-row {
                flex-direction: column;
                align-items: stretch !important;
                gap: 12px;
                padding: 14px;
                border-radius: 12px;
                border: 1px solid var(--border);
                margin-bottom: 12px;
                background: #ffffff;
            }

            .project-row-left {
                width: 100%;
                display: flex;
                align-items: flex-start;
                gap: 12px;
                padding-right: 0 !important;
            }

            .project-row-title {
                font-size: 13.5px;
                font-weight: 700;
                line-height: 1.45;
                margin-bottom: 4px;
            }

            .project-row-meta {
                font-size: 11.5px;
                color: var(--text-muted);
            }

            .project-row-action {
                align-self: stretch;
                display: flex;
                align-items: center;
                justify-content: flex-end;
                gap: 8px;
                width: 100%;
                padding-top: 10px;
                border-top: 1px solid #f1f5f9;
            }

            .stepper {
                gap: 6px;
                overflow-x: auto;
                padding-bottom: 4px;
            }

            .step-pill {
                padding: 6px 10px;
                font-size: 12px;
                white-space: nowrap;
            }

            /* Interactive PPT Studio Canvas di HP — Anti-Terpotong & Tetap Rapi */
            .ppt-canvas,
            #fs-slide-canvas {
                width: 100% !important;
                aspect-ratio: auto !important;
                min-height: 240px !important;
                height: auto !important;
                max-height: none !important;
                padding: 18px 14px 16px !important;
                box-sizing: border-box !important;
                overflow: hidden !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: flex-start !important;
                gap: 8px !important;
            }

            .ppt-canvas > div,
            #fs-slide-canvas > div {
                height: auto !important;
                justify-content: flex-start !important;
                gap: 6px !important;
            }

            .ppt-theme-royal_academic {
                padding-left: 20px !important;
            }

            .ppt-theme-royal_academic::before {
                width: 7px !important;
            }

            #slide-campus-badge,
            #fs-slide-campus-badge {
                font-size: 9px !important;
                padding: 3px 8px !important;
                margin-bottom: 0 !important;
            }

            #slide-campus-badge img,
            #fs-slide-campus-badge img {
                width: 14px !important;
                height: 14px !important;
            }

            #slide-section-label,
            #fs-slide-section {
                font-size: 9px !important;
                padding: 3px 8px !important;
            }

            #slide-counter-label,
            #fs-slide-num-tag {
                font-size: 10px !important;
            }

            /* Slide 1 Cover di HP — Lengkap Tanpa Terpotong */
            .ppt-cover-title-text {
                font-size: 13.5px !important;
                line-height: 1.3 !important;
                max-width: 100% !important;
                margin: 4px auto 0 !important;
            }

            .ppt-cover-title-text.ppt-title-long {
                font-size: 12.5px !important;
                line-height: 1.28 !important;
            }

            .ppt-cover-divider {
                width: 52px !important;
                height: 2.5px !important;
                margin: 6px auto 4px !important;
            }

            .ppt-cover-oleh-box {
                padding: 6px 14px !important;
                margin: 4px auto 6px !important;
                line-height: 1.25 !important;
            }

            .ppt-cover-oleh-tag {
                font-size: 8.5px !important;
            }

            .ppt-cover-author-name {
                font-size: 11.5px !important;
                margin-top: 2px !important;
            }

            .ppt-cover-nim {
                font-size: 9.5px !important;
                margin-top: 1px !important;
            }

            .ppt-cover-pembimbing-li {
                margin-top: 4px !important;
                background: transparent !important;
                border: none !important;
                padding: 0 !important;
            }

            .ppt-pembimbing-grid {
                display: grid !important;
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                width: 100% !important;
                gap: 8px !important;
            }

            .ppt-pembimbing-card {
                padding: 7px 9px !important;
                border-radius: 8px !important;
            }

            .ppt-pembimbing-label {
                font-size: 8.5px !important;
                margin-bottom: 3px !important;
            }

            .ppt-pembimbing-name {
                font-size: 10px !important;
                line-height: 1.3 !important;
            }

            .ppt-cover-footer-inst {
                font-size: 9.5px !important;
                margin-top: 8px !important;
                padding-top: 6px !important;
            }

            /* Slides 2-12 di HP — Kartu Poin Rapi & Mudah Dibaca */
            #slide-title-edit,
            #fs-slide-title {
                font-size: 15px !important;
                line-height: 1.28 !important;
                margin-bottom: 4px !important;
            }

            #slide-subtitle-edit,
            #fs-slide-subtitle {
                font-size: 11.5px !important;
                line-height: 1.38 !important;
                margin-bottom: 8px !important;
            }

            .ppt-canvas #slide-bullets-edit,
            .ppt-canvas #fs-slide-bullets,
            .ppt-canvas #slide-bullets-edit.ppt-bullets-two-col,
            .ppt-canvas #fs-slide-bullets.ppt-bullets-two-col {
                grid-template-columns: 1fr !important;
                gap: 6px !important;
                padding-left: 0 !important;
            }

            .ppt-canvas #slide-bullets-edit li:not(.ppt-cover-pembimbing-li),
            .ppt-canvas #fs-slide-bullets li:not(.ppt-cover-pembimbing-li) {
                font-size: 11.5px !important;
                line-height: 1.42 !important;
                padding: 7px 10px !important;
                border-radius: 8px !important;
            }

            #ppt-slideshow-modal {
                padding: 12px 10px !important;
                justify-content: flex-start !important;
                overflow-y: auto !important;
            }

            #ppt-slideshow-modal .ppt-nav-btn {
                font-size: 11.5px;
                padding: 6px 10px;
            }

            #ppt-slideshow-modal>div {
                flex-wrap: wrap;
                gap: 8px;
            }

            /* Responsive Dosen AI Card di HP */
            #pane-qa .dosen-ai-card {
                height: calc(100dvh - 145px) !important;
                min-height: 430px !important;
                max-height: none !important;
                border-radius: 14px !important;
                margin-bottom: 12px !important;
            }

            #qa-chat-messages {
                padding: 14px 12px !important;
                gap: 12px !important;
            }

            #pane-qa .chat-msg.assistant {
                max-width: 100% !important;
                gap: 8px !important;
            }

            #pane-qa .chat-msg.assistant .qa-avatar {
                width: 28px !important;
                height: 28px !important;
            }

            #pane-qa .chat-msg.user {
                max-width: 100% !important;
            }

            #pane-qa .chat-msg.user > div {
                max-width: 88% !important;
                padding: 10px 13px !important;
                font-size: 13.5px !important;
            }

            #pane-qa .chat-msg.assistant .qa-bubble {
                padding: 11px 13px !important;
                font-size: 13.5px !important;
            }

            .qa-chips-bar {
                padding: 8px 10px !important;
                gap: 6px !important;
            }

            .qa-input-bar {
                padding: 10px !important;
                gap: 8px !important;
            }

            .qa-input-bar textarea {
                font-size: 16px !important;
                padding: 9px 11px !important;
                min-height: 42px !important;
            }

            .qa-input-bar .btn-primary {
                min-height: 42px !important;
                padding: 8px 13px !important;
                font-size: 13px !important;
            }
        }

        /* Dosen AI Clean Responsive Chat Layout */
        #pane-qa .dosen-ai-card {
            padding: 0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: clamp(500px, calc(100dvh - 190px), 740px);
            border: 1px solid var(--border);
            border-radius: 16px;
            background: #ffffff;
        }

        .qa-header-bar {
            flex-shrink: 0;
            padding: 12px 18px;
            background: #f8fafc;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
        }

        #qa-chat-messages {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            padding: 18px 20px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            background: #ffffff;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 transparent;
        }

        .qa-chips-bar {
            flex-shrink: 0;
            padding: 10px 16px;
            background: #ffffff;
            border-top: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            gap: 8px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }

        .qa-chips-bar::-webkit-scrollbar {
            display: none;
        }

        .qa-chip-pill {
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            padding: 6px 13px;
            min-height: 32px;
            border-radius: 999px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #334155;
            font-size: 12px;
            font-weight: 600;
            font-family: inherit;
            white-space: nowrap;
            cursor: pointer;
            transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
        }

        .qa-chip-pill:hover {
            background: var(--bg-muted);
            border-color: #93c5fd;
            color: var(--primary-hover);
        }

        .qa-input-bar {
            flex-shrink: 0;
            padding: 12px 16px;
            background: #f8fafc;
            border-top: 1px solid var(--border);
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }

        .qa-copy-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 11px;
            min-height: 28px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #475569;
            font-size: 11.5px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
        }

        .qa-copy-btn:hover {
            background: var(--bg-muted);
            border-color: #93c5fd;
            color: var(--primary-hover);
        }

        .qa-prompt-chip {
            padding: 5px 10px;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 11.5px;
            color: #1e40af;
            cursor: pointer;
            text-align: left;
            transition: all 0.15s;
            font-family: inherit;
        }

        .qa-prompt-chip:hover {
            background: #dbeafe;
            border-color: #93c5fd;
        }

        #dosen-ai-fab:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 28px -4px rgba(37, 99, 235, 0.55), 0 10px 12px -5px rgba(37, 99, 235, 0.35);
        }

        @media (max-width: 768px) {
            #dosen-ai-fab {
                bottom: 20px !important;
                right: 16px !important;
                padding: 8px 14px 8px 12px !important;
            }
        }

        /* =========================================================
           CEK PLAGIASI (SIMILARITY CHECKER ALA TURNITIN)
        ========================================================= */
        .plag-mode-tabs {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 4px;
            padding: 4px;
            background: #f1f5f9;
            border: 1px solid var(--border);
            border-radius: 11px;
            margin-bottom: 16px;
            width: 100%;
            max-width: 520px;
        }

        .plag-mode-btn {
            border: none;
            background: transparent;
            color: var(--text-muted);
            font-size: 12.5px;
            font-weight: 600;
            padding: 8px 10px;
            border-radius: 8px;
            cursor: pointer;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            white-space: nowrap;
            transition: all 0.15s ease;
        }

        .plag-mode-btn svg {
            width: 15px;
            height: 15px;
            flex-shrink: 0;
        }

        .plag-mode-btn.active {
            background: #ffffff;
            color: var(--primary);
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
        }

        .plag-filters-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px 18px;
            margin-top: 12px;
            padding: 11px 14px;
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 9px;
            font-size: 12.5px;
            color: #334155;
        }

        .plag-filters-row label {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            cursor: pointer;
            font-weight: 500;
        }

        .plag-filters-row input[type="checkbox"] {
            accent-color: var(--primary);
            width: 15px;
            height: 15px;
            flex-shrink: 0;
        }

        .plag-compare-toggle {
            margin-top: 12px;
            border: 1px dashed #cbd5e1;
            border-radius: 9px;
            padding: 10px 14px;
            background: #ffffff;
        }

        .plag-compare-toggle summary {
            cursor: pointer;
            font-size: 12.5px;
            font-weight: 600;
            color: #334155;
            display: flex;
            align-items: center;
            gap: 7px;
            list-style: none;
        }

        .plag-compare-toggle summary::-webkit-details-marker {
            display: none;
        }

        .plag-error-banner {
            display: none;
            margin-top: 12px;
            padding: 11px 14px;
            border-radius: 9px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            font-size: 13px;
            font-weight: 500;
            line-height: 1.5;
        }

        .plag-report-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.45fr) minmax(290px, 0.95fr);
            gap: 18px;
            margin-top: 18px;
            align-items: start;
        }

        .plag-doc-viewer {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px 22px;
            font-size: 14px;
            line-height: 1.85;
            color: var(--text-main);
            text-align: justify;
            max-height: 620px;
            overflow-y: auto;
            word-break: break-word;
        }

        .plag-sent-hl {
            padding: 2px 5px;
            border-radius: 5px;
            cursor: pointer;
            transition: filter 0.15s ease, box-shadow 0.15s ease;
            border-bottom: 2px solid transparent;
        }

        .plag-sent-hl:hover {
            filter: brightness(0.95);
            box-shadow: 0 1px 4px rgba(15, 23, 42, 0.12);
        }

        .plag-sent-num {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            border-radius: 4px;
            color: #ffffff;
            font-size: 10.5px;
            font-weight: 800;
            vertical-align: middle;
            margin-right: 4px;
            line-height: 1;
        }

        .plag-score-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 18px;
        }

        .plag-big-pct {
            font-size: 42px;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -0.03em;
        }

        .plag-breakdown-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
            margin-bottom: 14px;
        }

        .plag-src-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 10px 0;
            border-bottom: 1px solid var(--border-light);
        }

        .plag-src-item:last-child {
            border-bottom: none;
        }

        .plag-inline-editor {
            margin-top: 14px;
            padding: 14px 16px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 10px;
        }

        .plag-btn-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
        }

        @media (max-width: 960px) {
            .plag-report-grid {
                grid-template-columns: 1fr;
            }
            .plag-score-card {
                order: -1;
            }
        }

        @media (max-width: 640px) {
            .plag-mode-tabs {
                max-width: 100%;
                gap: 3px;
                padding: 3px;
            }
            .plag-mode-btn {
                flex-direction: column;
                gap: 4px;
                font-size: 11.5px;
                padding: 8px 4px;
                white-space: normal;
                text-align: center;
                line-height: 1.2;
            }
            .plag-filters-row {
                flex-direction: column;
                gap: 9px;
                padding: 10px 12px;
            }
            .plag-doc-viewer {
                padding: 14px;
                font-size: 13.5px;
                line-height: 1.75;
                text-align: left;
                max-height: 460px;
            }
            .plag-score-card {
                padding: 15px;
            }
            .plag-big-pct {
                font-size: 36px;
            }
            .plag-breakdown-grid {
                gap: 6px;
            }
        }

        /* =========================================================
           OLAH DATA STATISTIK VALID (SETARA SPSS) — STANDALONE MENU
           ========================================================= */
        .od-subtabs {
            display: inline-flex;
            gap: 6px;
            background: #f1f5f9;
            padding: 5px;
            border-radius: 12px;
            border: 1px solid var(--border);
            margin-bottom: 18px;
            max-width: 100%;
            flex-wrap: wrap;
        }
        .od-subtab-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 15px;
            border-radius: 9px;
            border: 1px solid transparent;
            background: transparent;
            color: var(--text-secondary);
            font-size: 13px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .od-subtab-btn:hover {
            color: var(--text-main);
            background: rgba(255, 255, 255, 0.65);
        }
        .od-subtab-btn.active {
            background: #ffffff;
            color: var(--primary);
            border-color: #bfdbfe;
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.08);
        }
        .od-badge-valid {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #047857;
            font-size: 11.5px;
            font-weight: 700;
        }
        .od-upload-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(0, 1fr);
            gap: 16px;
            margin-bottom: 16px;
        }
        .od-dropzone {
            border: 1.8px dashed #93c5fd;
            background: #f8fbff;
            border-radius: 14px;
            padding: 20px 16px;
            text-align: center;
            cursor: pointer;
            transition: all 0.16s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .od-dropzone:hover, .od-dropzone.dragover {
            border-color: var(--primary);
            background: #eff6ff;
        }
        .od-sample-row {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
            margin-top: 8px;
        }
        .od-sample-btn {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            padding: 9px 11px;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: #ffffff;
            text-align: left;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.15s ease;
        }
        .od-sample-btn:hover {
            border-color: #93c5fd;
            background: #f8fbff;
        }
        .od-sample-btn svg {
            width: 16px;
            height: 16px;
            color: var(--primary);
            flex-shrink: 0;
            margin-top: 2px;
        }
        .od-sample-title {
            font-size: 12px;
            font-weight: 700;
            color: var(--text-main);
            line-height: 1.25;
        }
        .od-sample-sub {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 2px;
            line-height: 1.3;
        }
        .od-test-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
            margin-bottom: 14px;
        }
        .od-test-card {
            padding: 10px 12px;
            border-radius: 11px;
            border: 1.5px solid var(--border);
            background: #ffffff;
            text-align: left;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.15s ease;
        }
        .od-test-card:hover {
            border-color: #93c5fd;
            background: #f8fbff;
        }
        .od-test-card.active {
            border-color: var(--primary);
            background: #eff6ff;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.12);
        }
        .od-test-name {
            font-size: 12.5px;
            font-weight: 700;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .od-test-desc {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 3px;
            line-height: 1.35;
        }
        .od-var-box {
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px;
            margin-bottom: 14px;
        }
        .od-chip-list {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            margin-top: 6px;
        }
        .od-col-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            border-radius: 999px;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-main);
            cursor: pointer;
            user-select: none;
            transition: all 0.12s;
        }
        .od-col-chip.checked {
            background: #eff6ff;
            border-color: var(--primary);
            color: #1d4ed8;
        }
        .od-col-chip input {
            margin: 0;
            accent-color: var(--primary);
        }
        .od-hl-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 16px;
        }
        .od-hl-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 12px 14px;
        }
        .od-hl-card.tone-blue { border-left: 3.5px solid #2563eb; background: #f8fbff; }
        .od-hl-card.tone-emerald { border-left: 3.5px solid #059669; background: #f0fdf4; }
        .od-hl-card.tone-amber { border-left: 3.5px solid #d97706; background: #fffbeb; }
        .od-hl-card.tone-slate { border-left: 3.5px solid #64748b; }
        .od-hl-lbl {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.02em;
            color: var(--text-muted);
        }
        .od-hl-val {
            font-size: 15px;
            font-weight: 800;
            color: var(--text-main);
            margin-top: 4px;
            word-break: break-word;
        }
        .od-table-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 14px;
        }
        .od-table-title {
            font-size: 13px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 10px;
        }
        .od-table-wrap {
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .od-spss-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12.2px;
        }
        .od-spss-table th {
            background: #eff6ff;
            color: #1e3a8a;
            font-weight: 700;
            text-align: left;
            padding: 8px 10px;
            border-top: 2px solid #1e3a8a;
            border-bottom: 1px solid #bfdbfe;
            white-space: nowrap;
        }
        .od-spss-table td {
            padding: 7px 10px;
            border-bottom: 1px solid #e2e8f0;
            color: #1e293b;
            white-space: normal;
            line-height: 1.4;
        }
        .od-spss-table tr:last-child td {
            border-bottom: 2px solid #1e3a8a;
        }
        .od-spss-table tbody tr:hover {
            background: #f8fafc;
        }
        .od-footnote {
            font-size: 11.5px;
            color: var(--text-muted);
            font-style: italic;
            margin-top: 8px;
            line-height: 1.5;
        }
        .od-chart-box {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 14px;
            overflow-x: auto;
        }
        .od-narration-box {
            background: #ffffff;
            border: 1px solid #bfdbfe;
            border-radius: 12px;
            padding: 18px 22px;
            font-size: 13.5px;
            line-height: 1.8;
            color: #1e293b;
            text-align: justify;
        }
        .od-narration-box h4 {
            font-size: 13.8px;
            font-weight: 700;
            color: #0f172a;
            margin: 14px 0 6px;
            text-align: left;
        }
        .od-narration-box h4:first-child { margin-top: 0; }
        .od-narration-box p { margin: 0 0 10px; }
        .od-narration-box p:last-child { margin-bottom: 0; }
        .od-chart-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 10px;
        }
        .od-chart-tab {
            padding: 6px 11px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: #f8fafc;
            color: var(--text-secondary);
            font-size: 11.8px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.14s ease;
        }
        .od-chart-tab:hover { border-color: #93c5fd; color: var(--primary); }
        .od-chart-tab.active { background: #eff6ff; border-color: var(--primary); color: #1d4ed8; }
        .od-inst-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.5fr) minmax(0, 0.7fr) minmax(0, 0.8fr);
            gap: 12px;
            margin-bottom: 14px;
        }
        .od-reg-grid {
            display: grid;
            grid-template-columns: minmax(0, 0.8fr) minmax(0, 1.4fr);
            gap: 14px;
        }
        @media (max-width: 900px) {
            .od-upload-grid { grid-template-columns: 1fr; }
            .od-test-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .od-hl-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .od-inst-grid, .od-reg-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 600px) {
            .od-subtabs { width: 100%; }
            .od-subtab-btn { flex: 1 1 100%; justify-content: center; font-size: 12.5px; padding: 9px 10px; }
            .od-sample-row { grid-template-columns: 1fr; }
            .od-test-grid { grid-template-columns: 1fr; }
            .od-hl-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .od-hl-val { font-size: 13.5px; }
        }
    </style>
</head>

<body>

    <div class="sidebar-backdrop" id="sidebar-backdrop" onclick="closeSidebarMobile()"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-brand-left">
                <a href="/" class="brand-avatar" style="padding: 0; background: none; border: none;">
                    <img src="/images/logo.svg" alt="SkripsiLab AI"
                        style="width: 32px; height: 32px; border-radius: 8px; display: block;">
                </a>
                <div class="brand-info">
                    <div class="brand-name">SkripsiLab AI</div>
                    <div class="brand-user">{{ $user->name }}</div>
                </div>
            </div>
            <button type="button" class="sidebar-close-btn" onclick="toggleSidebar()" title="Tutup / Buka Sidebar">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18" />
                    <line x1="6" y1="6" x2="18" y2="18" />
                </svg>
            </button>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-group-title">Utama</div>
            <button type="button" class="nav-btn active" id="nav-dashboard" onclick="openTab('dashboard', 'Dashboard')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                    <polyline points="9 22 9 12 15 12 15 22" />
                </svg>
                <span>Dashboard</span>
            </button>

            <button type="button" class="nav-btn" id="nav-projects" onclick="openTab('projects', 'Proyek Saya')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                    <polyline points="14 2 14 8 20 8" />
                    <line x1="16" y1="13" x2="8" y2="13" />
                    <line x1="16" y1="17" x2="8" y2="17" />
                </svg>
                <span style="flex: 1;">Proyek Saya</span>
                <span
                    style="font-size: 11px; font-weight: 700; padding: 1px 7px; border-radius: 999px; background: var(--primary-soft); color: var(--primary);">{{ $history->count() }}</span>
            </button>

            <div class="nav-group-title">Penulisan &amp; Riset</div>
            <button type="button" class="nav-btn" id="nav-titles" onclick="openTab('titles', 'Cari Judul')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <circle cx="11" cy="11" r="8" />
                    <line x1="21" y1="21" x2="16.65" y2="16.65" />
                </svg>
                <span>Cari Judul</span>
            </button>

            <button type="button" class="nav-btn" id="nav-ppt" onclick="openTab('ppt', 'Generate PPT Sidang')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <rect x="2" y="3" width="20" height="14" rx="2" />
                    <line x1="8" y1="21" x2="16" y2="21" />
                    <line x1="12" y1="17" x2="12" y2="21" />
                </svg>
                <span>Generate PPT Sidang</span>
            </button>

            <button type="button" class="nav-btn" id="nav-defense" onclick="openTab('defense', 'Simulasi Sidang')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M22 10v6M2 10l10-5 10 5-10 5z" />
                    <path d="M6 12v5c3 3 9 3 12 0v-5" />
                </svg>
                <span>Simulasi Sidang</span>
            </button>

            <button type="button" class="nav-btn" id="nav-journals" onclick="openTab('journals', 'Cari Jurnal')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />
                </svg>
                <span>Cari Jurnal</span>
            </button>

            <button type="button" class="nav-btn" id="nav-paraphrase" onclick="openTab('paraphrase', 'Parafrase')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M12 20h9" />
                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" />
                </svg>
                <span>Parafrase</span>
            </button>

            {{-- Menu Cek Plagiasi disembunyikan sementara hingga penyempurnaan lanjutan --}}
            <button type="button" class="nav-btn" id="nav-plagiarism" onclick="openTab('plagiarism', 'Cek Plagiasi')" style="display: none;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                    <path d="M9 12l2 2 4-4" />
                </svg>
                <span style="flex: 1;">Cek Plagiasi</span>
                <span style="font-size: 10px; font-weight: 700; padding: 1px 6px; border-radius: 999px; background: #eff6ff; color: #2563eb;">Baru</span>
            </button>

            <button type="button" class="nav-btn" id="nav-olahdata" onclick="openTab('olahdata', 'Olah Data')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M18 20V10" />
                    <path d="M12 20V4" />
                    <path d="M6 20v-6" />
                </svg>
                <span style="flex: 1;">Olah Data</span>
                <span style="font-size: 10px; font-weight: 700; padding: 1px 6px; border-radius: 999px; background: #ecfdf5; color: #059669;">SPSS</span>
            </button>

            <button type="button" class="nav-btn" id="nav-qa" onclick="openTab('qa', 'Dosen AI')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M22 10v6M2 10l10-5 10 5-10 5z" />
                    <path d="M6 12v5c0 2 3 3 6 3s6-1 6-3v-5" />
                </svg>
                <span>Dosen AI</span>
            </button>

            <a href="{{ route('tools.hub') }}" class="nav-btn" id="nav-tools-hub" style="text-decoration: none;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" />
                </svg>
                <span style="flex: 1;">Tools Mahasiswa</span>
                <span style="font-size: 10px; font-weight: 700; padding: 1px 6px; border-radius: 999px; background: #ecfdf5; color: #059669;">10</span>
            </a>

            <div class="nav-group-title">Akun &amp; Bonus</div>
            <button type="button" class="nav-btn" id="nav-pricing" onclick="openTab('pricing', 'Paket')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <rect x="2" y="5" width="20" height="14" rx="2" />
                    <line x1="2" y1="10" x2="22" y2="10" />
                </svg>
                <span style="flex: 1;">Paket</span>
                <span style="font-size: 10px; font-weight: 700; padding: 1px 6px; border-radius: 999px; background: #eff6ff; color: #2563eb;">
                    {{ !empty($billingSummary['is_paid_active']) ? 'Aktif' : '15rb' }}
                </span>
            </button>

            <button type="button" class="nav-btn" id="nav-account" onclick="openTab('account', 'Akun Saya')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                    <circle cx="12" cy="7" r="4" />
                </svg>
                <span>Akun Saya</span>
            </button>

            <button type="button" class="nav-btn" id="nav-referral" onclick="openTab('referral', 'Ajak Teman')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                    <circle cx="8.5" cy="7" r="4" />
                    <line x1="20" y1="8" x2="20" y2="14" />
                    <line x1="23" y1="11" x2="17" y2="11" />
                </svg>
                <span>Ajak Teman (Referral)</span>
            </button>

            <div class="nav-group-title">Bantuan &amp; Pengembang</div>
            <a href="{{ route('panduan.index') }}" target="_blank" rel="noopener" class="nav-btn" style="text-decoration: none;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z" />
                    <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z" />
                </svg>
                <span style="flex: 1;">Panduan Lengkap</span>
                <span style="font-size: 10px; font-weight: 700; padding: 1px 6px; border-radius: 999px; background: #eff6ff; color: #2563eb;">7</span>
            </a>
            <button type="button" class="nav-btn" id="nav-feedback" onclick="openTab('feedback', 'Saran & Laporan')"
                style="color: #0284c7;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path
                        d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z" />
                </svg>
                <span>Saran &amp; Laporan</span>
            </button>
        </nav>

        <div class="sidebar-footer">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="logout-btn" title="Keluar">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.8">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                        <polyline points="16 17 21 12 16 7" />
                        <line x1="21" y1="12" x2="9" y2="12" />
                    </svg>
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
                <button type="button" class="sidebar-toggle-btn" onclick="toggleSidebar()"
                    title="Buka / Tutup Menu Sidebar">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="3" y1="12" x2="21" y2="12" />
                        <line x1="3" y1="6" x2="21" y2="6" />
                        <line x1="3" y1="18" x2="21" y2="18" />
                    </svg>
                </button>
                <a href="{{ route('app.create', ['tab' => 'dashboard']) }}" onclick="openTab('dashboard', 'Dashboard'); return false;"
                    style="display: inline-flex; align-items: center; gap: 8px; color: var(--text-main); font-weight: 700; font-size: 14px; text-decoration: none;">
                    <img src="/images/logo.svg" alt="SkripsiLab AI" style="width: 24px; height: 24px; border-radius: 6px; display: block;">
                    <span>SkripsiLab AI</span>
                </a>
                <span class="breadcrumb-current" id="breadcrumb-label" style="display: none;">Dashboard</span>
            </div>

            @php
                $topBs = $billingSummary ?? [];
                $topPaid = !empty($topBs['is_paid_active']);
                $topAdmin = !empty($topBs['is_admin']);
                $topPlanName = $topAdmin ? 'Admin Unlimited' : ($topBs['plan']['name'] ?? 'Paket Free');
            @endphp
            <div class="topbar-right">
                <input type="hidden" id="global-model" value="auto">
                <button type="button" class="token-pill" onclick="openTab('pricing', 'Paket')"
                    style="background: {{ $topPaid ? '#f0fdf4' : '#fffbeb' }}; border-color: {{ $topPaid ? '#bbf7d0' : '#fde68a' }}; color: {{ $topPaid ? '#15803d' : '#b45309' }}; font-weight: 700;">
                    <span>{{ $topPaid ? '✓ ' . $topPlanName : 'Paket Free' }}</span>
                    @if(!$topPaid)
                        <span style="color: #2563eb; font-weight: 800;">Upgrade →</span>
                    @endif
                </button>
            </div>
        </header>

        <main class="page-content">
            @if(session('success'))
                <div
                    style="padding: 12px 16px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; color: #166534; font-size: 13.5px; margin-bottom: 20px;">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div
                    style="padding: 12px 16px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; color: #991b1b; font-size: 13.5px; margin-bottom: 20px;">
                    {{ session('error') }}
                </div>
            @endif

            <!-- ========================================================= -->
            <!-- 1. TAB DASHBOARD -->
            <!-- ========================================================= -->
            <section class="tab-pane active" id="pane-dashboard">
                <div class="page-eyebrow">Workspace Akademik</div>
                <h1 class="page-title">Halo, {{ explode(' ', trim($user->name))[0] }}</h1>
                <p class="page-subtitle">Pilih tahapan skripsi yang ingin kamu kerjakan hari ini, mulai dari mencari ide
                    judul, menulis proposal & skripsi Bab 1–5, hingga persiapan sidang.</p>

                <!-- Proyek Terbaru (Cukup 1 Terbaru agar Dashboard Rapi) -->
                @if($history->count() > 0)
                    <div class="card-flush" style="margin-bottom: 24px;">
                        <div class="card-header-bar">
                            <div>Proyek Skripsi Terbaru <span class="count-badge">{{ $history->count() }}</span></div>
                            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                <button type="button" class="btn-outline" style="padding: 6px 12px; font-size: 12px;"
                                    onclick="openTab('projects', 'Proyek Saya'); showProjectListView();">Lihat Semua
                                    ({{ $history->count() }})</button>
                                <button type="button" class="btn-primary" style="padding: 6px 12px; font-size: 12px;"
                                    onclick="openCreateProjectForm()">+ Proyek Baru</button>
                            </div>
                        </div>
                        @foreach($history->take(1) as $proj)
                            <a href="{{ route('app.index', ['id' => $proj->id]) }}" class="project-row">
                                <div class="project-row-left">
                                    <div style="min-width: 0; width: 100%;">
                                        <div class="project-row-title">{{ $proj->judul }}</div>
                                        <div class="project-row-meta">{{ $proj->prodi }} · {{ $proj->metode_penelitian }} ·
                                            {{ $proj->updated_at->diffForHumans() }}
                                        </div>
                                    </div>
                                </div>
                                <span class="project-row-action">Lanjutkan Workspace →</span>
                            </a>
                        @endforeach
                    </div>
                @endif

                <div class="dash-grid">
                    <div class="dash-card" onclick="openCreateProjectForm()">
                        <div class="dash-icon" style="background: var(--primary-soft); color: var(--primary);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <line x1="12" y1="5" x2="12" y2="19" />
                                <line x1="5" y1="12" x2="19" y2="12" />
                            </svg>
                        </div>
                        <div>
                            <h3>Buat Proyek Baru (Proposal / Skripsi)</h3>
                            <p>Susun Proposal (Bab 1–3) atau Full Skripsi (Bab 1–5) lengkap sesuai pedoman kampus.</p>
                        </div>
                    </div>

                    <div class="dash-card" onclick="openTab('titles', 'Cari Judul')">
                        <div class="dash-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.8">
                                <circle cx="11" cy="11" r="8" />
                                <line x1="21" y1="21" x2="16.65" y2="16.65" />
                            </svg>
                        </div>
                        <div>
                            <h3>Cari Judul Skripsi</h3>
                            <p>Dapatkan saran judul penelitian sesuai topik, jurusan, dan metodologi.</p>
                        </div>
                    </div>

                    <div class="dash-card" onclick="openTab('ppt', 'Generate PPT Sidang')">
                        <div class="dash-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.8">
                                <rect x="2" y="3" width="20" height="14" rx="2" />
                                <line x1="8" y1="21" x2="16" y2="21" />
                            </svg>
                        </div>
                        <div>
                            <h3>Generate PPT Sidang</h3>
                            <p>Buat slide sidang otomatis dari proyek skripsimu, edit tema, lalu unduh .pptx.</p>
                        </div>
                    </div>

                    <div class="dash-card" onclick="openTab('defense', 'Simulasi Sidang')">
                        <div class="dash-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.8">
                                <path d="M22 10v6M2 10l10-5 10 5-10 5z" />
                                <path d="M6 12v5c3 3 9 3 12 0v-5" />
                            </svg>
                        </div>
                        <div>
                            <h3>Simulasi Sidang</h3>
                            <p>Latihan tanya-jawab dengan penguji AI lengkap dengan skor dan saran jawaban.</p>
                        </div>
                    </div>

                    <div class="dash-card" onclick="openTab('journals', 'Cari Jurnal')">
                        <div class="dash-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.8">
                                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />
                            </svg>
                        </div>
                        <div>
                            <h3>Cari Jurnal & Download PDF</h3>
                            <p>Temukan jurnal open-access beserta link unduh PDF asli dan sitasi APA.</p>
                        </div>
                    </div>

                    <div class="dash-card" onclick="openTab('paraphrase', 'Parafrase')">
                        <div class="dash-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.8">
                                <path d="M12 20h9" />
                                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" />
                            </svg>
                        </div>
                        <div>
                            <h3>Parafrase Akademik</h3>
                            <p>Ubah kalimat kutipan menjadi bahasa ilmiah baku tanpa mengubah makna.</p>
                        </div>
                    </div>

                    <div class="dash-card" onclick="openTab('olahdata', 'Olah Data')">
                        <div class="dash-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.8">
                                <path d="M18 20V10" />
                                <path d="M12 20V4" />
                                <path d="M6 20v-6" />
                            </svg>
                        </div>
                        <div>
                            <h3>Olah Data Statistik (Setara SPSS)</h3>
                            <p>Hitung Validitas, Reliabilitas, Regresi, Asumsi Klasik, ANOVA (Teknik/Lab), t-Test, Chi-Square &amp; Generator Kuesioner.</p>
                        </div>
                    </div>

                    <div class="dash-card" onclick="openTab('qa', 'Dosen AI')">
                        <div class="dash-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.8">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                            </svg>
                        </div>
                        <div>
                            <h3>Dosen AI (Konsultasi 24 Jam)</h3>
                            <p>Diskusi metodologi, olah data statistik, revisi dosen, hingga unggah foto soal atau tabel.</p>
                        </div>
                    </div>

                    <div class="dash-card" onclick="openTab('projects', 'Proyek Saya'); showProjectListView();">
                        <div class="dash-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.8">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                <polyline points="14 2 14 8 20 8" />
                                <line x1="16" y1="13" x2="8" y2="13" />
                                <line x1="16" y1="17" x2="8" y2="17" />
                            </svg>
                        </div>
                        <div>
                            <h3>Proyek Saya ({{ $history->count() }})</h3>
                            <p>Kelola seluruh naskah Proposal &amp; Skripsi, unggah Lampiran, dan ekspor Word/PDF.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ========================================================= -->
            <!-- 2. TAB PROYEK SAYA (LIST PROYEK + TOMBOL BUAT PROYEK BARU) -->
            <!-- ========================================================= -->
            <section class="tab-pane" id="pane-projects">
                <!-- VIEW A: DAFTAR PROYEK SAYA (DEFAULT SAAT KLIK MENU PROYEK SAYA) -->
                <div id="projects-list-view">
                    <div
                        style="display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 24px;">
                        <div>
                            <div class="page-eyebrow">Manajemen Dokumen</div>
                            <h1 class="page-title">Proyek Saya</h1>
                            <p class="page-subtitle" style="margin-bottom: 0;">Daftar proyek Proposal (Bab 1–3) dan
                                Skripsi Lengkap (Bab 1–5) yang sedang kamu kerjakan.</p>
                        </div>
                        <button type="button" class="btn-primary" onclick="openCreateProjectForm()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.2">
                                <line x1="12" y1="5" x2="12" y2="19" />
                                <line x1="5" y1="12" x2="19" y2="12" />
                            </svg>
                            <span>Buat Proyek Baru</span>
                        </button>
                    </div>

                    <div class="card-flush">
                        <div class="card-header-bar">
                            <div>Daftar Proyek Tersimpan <span class="count-badge">{{ $history->count() }}</span></div>
                            <span style="font-size: 12px; font-weight: 400; color: var(--text-muted);">Klik proyek untuk
                                membuka Workspace Penulisan</span>
                        </div>
                        @forelse($history as $proj)
                            @php
                                $doneCount = 0;
                                foreach (['bab1', 'bab2', 'bab3', 'bab4', 'bab5'] as $b) {
                                    if (!empty($proj->{$b . '_content'}))
                                        $doneCount++;
                                }
                                $projMeta = is_string($proj->citation_validation) ? json_decode($proj->citation_validation, true) : [];
                                $projTahap = $projMeta['tahap_pengerjaan'] ?? 'Skripsi Lengkap (Bab I–V)';
                            @endphp
                            <div class="project-row"
                                style="cursor: default; align-items: center; justify-content: space-between; gap: 12px;">
                                <a href="{{ route('app.index', ['id' => $proj->id]) }}" class="project-row-left"
                                    style="flex: 1; min-width: 0;">
                                    <div style="min-width: 0; width: 100%;">
                                        <div class="project-row-title"
                                            style="white-space: normal; word-break: break-word; line-height: 1.45; margin-bottom: 4px;">
                                            {{ $proj->judul }}
                                        </div>
                                        <div class="project-row-meta">
                                            <span style="color: var(--primary); font-weight: 600;">{{ $projTahap }}</span> ·
                                            {{ $proj->prodi }} · {{ $proj->metode_penelitian }} · {{ $doneCount }}/5 Bab
                                        </div>
                                    </div>
                                </a>
                                <div class="project-row-action"
                                    style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
                                    <a href="{{ route('app.index', ['id' => $proj->id]) }}" class="btn-primary"
                                        style="padding: 6px 12px; font-size: 12px; display: inline-flex; align-items: center; gap: 4px;">
                                        <span>Buka</span>
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2.2">
                                            <polyline points="9 18 15 12 9 6" />
                                        </svg>
                                    </a>
                                    <form action="{{ route('project.delete', ['id' => $proj->id]) }}" method="POST"
                                        onsubmit="return confirm('Hapus proyek ini?');" style="margin: 0;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-outline"
                                            style="padding: 6px 8px; font-size: 12px; color: #dc2626; border-color: #fecaca;"
                                            title="Hapus Proyek">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2">
                                                <polyline points="3 6 5 6 21 6" />
                                                <path
                                                    d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div style="padding: 48px 24px; text-align: center;">
                                <div
                                    style="width: 48px; height: 48px; border-radius: 12px; background: var(--primary-soft); color: var(--primary); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                        <polyline points="14 2 14 8 20 8" />
                                    </svg>
                                </div>
                                <div
                                    style="font-size: 15px; font-weight: 600; color: var(--text-main); margin-bottom: 6px;">
                                    Belum ada proyek skripsi</div>
                                <div style="font-size: 13.5px; color: var(--text-muted); margin-bottom: 18px;">Mulai susun
                                    Proposal (Bab 1–3) atau Skripsi Lengkap (Bab 1–5) sekarang.</div>
                                <button type="button" class="btn-primary" onclick="openCreateProjectForm()">+ Buat Proyek
                                    Baru</button>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- VIEW B: FORM BUAT PROYEK BARU (FOKUS PROPOSAL BAB 1-3 & FULL SKRIPSI BAB 1-5) -->
                <div id="projects-create-view" style="display: none;">
                    <div
                        style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; flex-wrap: wrap; gap: 10px;">
                        <div>
                            <div class="page-eyebrow">Penulisan Terstruktur</div>
                            <h1 class="page-title">Buat Proyek Proposal / Skripsi</h1>
                            <p class="page-subtitle" style="margin-bottom: 0;">Fokus penyusunan untuk Proposal Skripsi
                                (Bab 1–3) atau Full Skripsi Lengkap (Bab 1–5).</p>
                        </div>
                        <button type="button" class="btn-outline" onclick="showProjectListView()">
                            ← Kembali ke Proyek Saya
                        </button>
                    </div>

                    <form action="{{ route('project.start') }}" method="POST" enctype="multipart/form-data" onsubmit="
                        if (typeof syncCustomStructureOutput === 'function') syncCustomStructureOutput();
                        const pedomanFile = this.querySelector('input[name=pedoman_file]');
                        const pedomanExtracted = document.getElementById('input-pedoman-extracted');
                        const refFiles = this.querySelector('input[name=\'referensi_files[]\']');
                        if (pedomanFile && pedomanFile.files && pedomanFile.files[0] && pedomanFile.files[0].size > 3.0 * 1024 * 1024 && pedomanExtracted && pedomanExtracted.value.trim().length > 150) {
                            pedomanFile.value = '';
                        }
                        let totalBytes = 0;
                        if (pedomanFile && pedomanFile.files && pedomanFile.files[0]) totalBytes += pedomanFile.files[0].size;
                        if (refFiles && refFiles.files) {
                            for (let i = 0; i < refFiles.files.length; i++) totalBytes += refFiles.files[i].size;
                        }
                        if (totalBytes > 3.5 * 1024 * 1024) {
                            alert('Total ukuran file pedoman/referensi melebihi batas 3.5 MB serverless Vercel. Silakan unggah berkas yang lebih ringkas atau gunakan catatan teks pedoman.');
                            return false;
                        }
                        const checkedRefs = this.querySelectorAll('input[name=&quot;sumber_referensi[]&quot;]:checked');
                        const refGroup = document.getElementById('sumber-referensi-group');
                        const refErrEl = document.getElementById('sumber-referensi-error');
                        if (!checkedRefs || checkedRefs.length < 1) {
                            if (refErrEl) refErrEl.style.display = 'block';
                            if (refGroup) {
                                refGroup.style.outline = '2px solid #ef4444';
                                refGroup.style.outlineOffset = '4px';
                                refGroup.style.borderRadius = '10px';
                                refGroup.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            }
                            alert('Silakan pilih minimal 1 Sumber Referensi (Umum, Terindeks Sinta, atau Terindeks Scopus).');
                            return false;
                        }
                        if (refErrEl) refErrEl.style.display = 'none';
                        if (refGroup) refGroup.style.outline = 'none';
                        const btn = this.querySelector('button[type=submit]');
                        if (btn) {
                            btn.disabled = true;
                            btn.innerHTML = '<span class=\'ai-spinner\'></span> Membuka Workspace Skripsi...';
                        }
                    ">
                        @csrf
                        <input type="hidden" name="jenis_penelitian" value="Skripsi (S1)">
                        <input type="hidden" name="logo_base64" id="input-logo-base64"
                            value="{{ preg_replace('#^data:image/\w+;base64,#i', '', $defaultIdentity['logo_base64'] ?? '') }}">

                        <!-- Card 1: Fokus Tahap Penulisan (Proposal Bab 1-3 vs Full Skripsi Bab 1-5) -->
                        <div class="card" style="margin-bottom: 16px;">
                            <div
                                style="font-size: 14px; font-weight: 600; color: var(--text-main); margin-bottom: 12px;">
                                Pilih Target Penulisan <span class="req">*</span></div>
                            <div class="responsive-grid-2"
                                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 12px;">
                                <label id="label-target-proposal" class="target-choice-card"
                                    style="display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; padding: 13px 15px; border: 1.5px solid var(--primary); border-radius: 11px; cursor: pointer; background: #f8fbff; transition: all 0.2s;">
                                    <div style="display: flex; align-items: center; gap: 10px; min-width: 0; flex: 1 1 170px;">
                                        <input type="radio" name="tahap_pengerjaan" value="Proposal Skripsi (Bab I–III)"
                                            checked style="accent-color: var(--primary); flex-shrink: 0;"
                                            onchange="updateTargetStyle(this)">
                                        <span id="title-target-proposal"
                                            style="font-size: 13.5px; font-weight: 700; color: var(--primary); line-height: 1.35;">Proposal Skripsi (Bab 1–3)</span>
                                    </div>
                                    <span id="badge-target-proposal"
                                        style="font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 999px; background: #dbeafe; color: #1d4ed8; white-space: nowrap; flex-shrink: 0;">Seminar Proposal</span>
                                </label>
                                <label id="label-target-full" class="target-choice-card"
                                    style="display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; padding: 13px 15px; border: 1.5px solid var(--border); border-radius: 11px; cursor: pointer; background: #fff; transition: all 0.2s;">
                                    <div style="display: flex; align-items: center; gap: 10px; min-width: 0; flex: 1 1 170px;">
                                        <input type="radio" name="tahap_pengerjaan" value="Skripsi Lengkap (Bab I–V)"
                                            style="accent-color: var(--primary); flex-shrink: 0;" onchange="updateTargetStyle(this)">
                                        <span id="title-target-full"
                                            style="font-size: 13.5px; font-weight: 700; color: var(--text-main); line-height: 1.35;">Full Skripsi Lengkap (Bab 1–5)</span>
                                    </div>
                                    <span id="badge-target-full"
                                        style="font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 999px; background: #f1f5f9; color: #475569; white-space: nowrap; flex-shrink: 0;">Sidang Akhir</span>
                                </label>
                            </div>
                        </div>

                        <!-- Card 2: Judul, Logo Kampus, & Identitas Mahasiswa -->
                        <div class="card" style="margin-bottom: 16px;">
                            <div class="responsive-grid-title-logo"
                                style="display: grid; grid-template-columns: 1fr 180px; gap: 16px; align-items: stretch; margin-bottom: 18px;">
                                <div>
                                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 6px; margin-bottom: 6px;">
                                        <label class="form-label" style="margin-bottom: 0;">Judul Proposal / Skripsi <span
                                                class="req">*</span></label>
                                        <button type="button" onclick="openTab('titles', 'Cari Judul')"
                                            style="display: inline-flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 600; color: #1d4ed8; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 999px; padding: 3px 10px; cursor: pointer; transition: all 0.15s;"
                                            onmouseover="this.style.background='#dbeafe'" onmouseout="this.style.background='#eff6ff'">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                            <span>Belum punya judul? Cari Judul dulu →</span>
                                        </button>
                                    </div>
                                    <textarea name="judul" id="input-project-judul" class="form-textarea"
                                        style="min-height: 92px;"
                                        placeholder="Gunakan judul yang sesuai dengan fokus penelitianmu (cth: Pengaruh Literasi Digital dan Regulasi Diri terhadap Kesiapan Kerja Mahasiswa...)"
                                        required></textarea>
                                </div>
                                <div>
                                    <label class="form-label">Logo Kampus <span class="opt">(opsional)</span></label>
                                    <label
                                        style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 92px; border: 1px dashed var(--border); border-radius: 10px; background: var(--bg-subtle); cursor: pointer; text-align: center; padding: 8px; overflow: hidden;">
                                        <img id="new-proj-logo-preview"
                                            src="{{ $defaultIdentity['logo_base64'] ?? '' }}" alt=""
                                            style="display:{{ !empty($defaultIdentity['logo_base64']) ? 'block' : 'none' }}; max-height:46px; max-width:90px; object-fit:contain; margin-bottom:4px;">
                                        <span id="new-proj-logo-label"
                                            style="font-size: 12px; font-weight: 600; color: var(--primary);">{{ !empty($defaultIdentity['logo_base64']) ? 'Ganti Logo Kampus' : '+ Unggah Logo' }}</span>
                                        <span style="font-size: 11px; color: var(--text-muted);">PNG/JPG, maks 2
                                            MB</span>
                                        <input type="file" id="new-proj-logo-file" name="logo_kampus"
                                            accept="image/png,image/jpeg,image/webp" style="display:none;"
                                            onchange="handleNewProjectLogoSelect(this)">
                                    </label>
                                </div>
                            </div>

                            <div class="form-row">
                                <div>
                                    <label class="form-label">Nama lengkap mahasiswa</label>
                                    <input type="text" name="nama" class="form-input"
                                        value="{{ strtoupper($defaultIdentity['nama'] ?? $user->name) }}"
                                        placeholder="Nama Lengkap Mahasiswa">
                                </div>
                                <div>
                                    <label class="form-label">NIM / NPM</label>
                                    <input type="text" name="nim" class="form-input"
                                        value="{{ $defaultIdentity['nim'] ?? '' }}" placeholder="Nomor Induk Mahasiswa">
                                </div>
                            </div>

                            <div class="responsive-grid-3"
                                style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px;">
                                <div>
                                    <label class="form-label">Nama kampus / universitas</label>
                                    <input type="text" name="kampus" class="form-input"
                                        value="{{ $defaultIdentity['kampus'] ?? '' }}"
                                        placeholder="Contoh: UNIVERSITAS INDONESIA">
                                </div>
                                <div>
                                    <label class="form-label">Jurusan / Program studi <span class="req">*</span></label>
                                    <input type="text" name="prodi" id="input-project-prodi" class="form-input"
                                        value="{{ $defaultIdentity['prodi'] ?? '' }}" placeholder="Contoh: MANAJEMEN"
                                        required>
                                </div>
                                <div>
                                    <label class="form-label">Fakultas</label>
                                    <input type="text" name="fakultas" class="form-input"
                                        value="{{ $defaultIdentity['fakultas'] ?? '' }}"
                                        placeholder="Contoh: FAKULTAS EKONOMI DAN BISNIS">
                                </div>
                            </div>
                        </div>

                        <!-- Card 3: Metode Penelitian -->
                        <div class="card" style="margin-bottom: 16px;">
                            <label class="form-label"
                                style="font-size: 14px; color: var(--text-main); margin-bottom: 10px;">Metode penelitian
                                <span class="req">*</span></label>
                            <div class="responsive-grid-3"
                                style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;">
                                @php
                                    $metodeOptions = [
                                        ['val' => 'Kualitatif (Studi Kasus / Deskriptif)', 'title' => 'Kualitatif', 'sub' => 'Wawancara, observasi, studi kasus'],
                                        ['val' => 'Kuantitatif (Survei / Regresi)', 'title' => 'Kuantitatif', 'sub' => 'Survei kuesioner, regresi, SEM'],
                                        ['val' => 'Kuantitatif — Data Sekunder', 'title' => 'Kuantitatif — Data Sekunder', 'sub' => 'BPS, deret waktu, laporan keuangan'],
                                        ['val' => 'Studi Pustaka (Library Research)', 'title' => 'Studi Pustaka', 'sub' => 'Library Research / kajian literatur'],
                                        ['val' => 'PTK (Penelitian Tindakan Kelas)', 'title' => 'PTK', 'sub' => 'Penelitian Tindakan Kelas'],
                                        ['val' => 'R&D / Pengembangan Sistem', 'title' => 'R&D / Sistem IT', 'sub' => 'Pengembangan produk / aplikasi'],
                                        ['val' => 'Mixed Method', 'title' => 'Mixed Method', 'sub' => 'Kombinasi kualitatif & kuantitatif'],
                                        ['val' => 'Hukum Normatif', 'title' => 'Hukum Normatif', 'sub' => 'Kajian perundang-undangan & doktrin'],
                                        ['val' => 'Hukum Empiris', 'title' => 'Hukum Empiris', 'sub' => 'Sosiologi hukum & efektivitas lapangan'],
                                    ];
                                @endphp
                                @foreach($metodeOptions as $mIdx => $mOpt)
                                    <label
                                        style="display: flex; align-items: flex-start; gap: 8px; padding: 10px 12px; border: 1px solid var(--border); border-radius: 9px; cursor: pointer; background: #fff;">
                                        <input type="radio" name="metode_penelitian" value="{{ $mOpt['val'] }}" {{ $mIdx === 1 ? 'checked' : '' }} style="margin-top: 3px; accent-color: var(--primary);">
                                        <div>
                                            <div style="font-size: 13px; font-weight: 600; color: var(--text-main);">
                                                {{ $mOpt['title'] }}
                                            </div>
                                            <div style="font-size: 11.5px; color: var(--text-muted); line-height: 1.35;">
                                                {{ $mOpt['sub'] }}
                                            </div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Card 4: Pedoman Kampus (Opsional) -->
                        <div class="card" style="margin-bottom: 16px;">
                            <div
                                style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                                <div style="font-size: 14px; font-weight: 600; color: var(--text-main);">Pedoman kampus
                                    <span
                                        style="font-size: 11px; font-weight: 500; color: var(--text-muted); margin-left: 6px;">OPSIONAL</span>
                                </div>
                            </div>
                            <input type="file" name="pedoman_file" id="input-pedoman-file" class="form-input" accept=".pdf,.docx,.doc,.txt"
                                style="padding: 9px 12px; font-size: 13px;" onchange="handlePedomanFileSelect(this)">
                            <input type="hidden" name="pedoman_text" id="input-pedoman-extracted" value="">
                            <div id="pedoman-extract-status" style="font-size: 12px; color: var(--text-muted); margin-top: 6px;">Pilih berkas buku
                                pedoman penulisan kampus (.pdf / .docx / .txt, maks 3,5 MB — PDF teks otomatis dibaca langsung di browser agar akurat 100%).</div>
                        </div>

                        <!-- Card 5: Struktur Bab Kustom (Accordion Builder) -->
                        <div class="card" style="margin-bottom: 16px;">
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 14px;">
                                <div>
                                    <div style="font-size: 14px; font-weight: 600; color: var(--text-main);">Struktur
                                        bab kustom <span
                                            style="font-size: 11px; font-weight: 500; color: var(--text-muted); margin-left: 6px;">OPSIONAL</span>
                                    </div>
                                    <div style="font-size: 12.5px; color: var(--text-muted); margin-top: 2px;">Atur
                                        subbab secara manual jika pedoman kampusmu memiliki urutan khusus. Jika tidak
                                        diaktifkan, sistem menggunakan struktur standar Bab I–V.</div>
                                </div>
                                <input type="checkbox" id="custom-structure-toggle"
                                    onchange="toggleCustomStructure(this.checked)"
                                    style="width: 18px; height: 18px; cursor: pointer; accent-color: var(--primary); flex-shrink: 0;">
                            </div>

                            <div id="custom-structure-wrap" style="display: none; margin-top: 16px;">
                                <textarea name="struktur_kustom" id="custom-structure-serialized"
                                    style="display: none;"></textarea>

                                <div id="custom-babs-accordion-container"
                                    style="display: flex; flex-direction: column; gap: 10px;">
                                    <!-- Di-render oleh renderCustomChapterAccordion() -->
                                </div>

                                <button type="button" class="btn-outline" onclick="addCustomBab()"
                                    style="width: 100%; border-style: dashed; padding: 10px 14px; font-size: 13px; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 12px; background: #f8fafc; border-color: #cbd5e1; border-radius: 8px; color: #334155; cursor: pointer; transition: all 0.15s;">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2.2">
                                        <line x1="12" y1="5" x2="12" y2="19" />
                                        <line x1="5" y1="12" x2="19" y2="12" />
                                    </svg>
                                    <span>Tambah Bab (mis. Bab 6 / 7)</span>
                                </button>
                            </div>
                        </div>

                        <!-- Card 6: Data Awal Penelitian (Opsional) -->
                        <div class="card" style="margin-bottom: 16px;">
                            <div style="font-size: 14px; font-weight: 600; color: var(--text-main);">Data awal
                                penelitian <span
                                    style="font-size: 11px; font-weight: 500; color: var(--text-muted); margin-left: 6px;">OPSIONAL</span>
                            </div>
                            <div style="font-size: 12.5px; color: var(--text-muted); margin: 4px 0 12px;">Tambahkan
                                kondisi nyata, angka, atau hasil observasi awal untuk mendukung latar belakang
                                penelitian.</div>
                            <textarea name="data_awal" class="form-textarea" style="min-height: 85px;"
                                placeholder="Contoh: Di SMAN 1 X, 62% siswa belum memanfaatkan e-library; rata-rata skor literasi digital 48/100 (survei 2024)."></textarea>
                        </div>

                        <!-- Card 7: Pengaturan Sitasi -->
                        <div class="card" style="margin-bottom: 16px;">
                            <div style="font-size: 14px; font-weight: 600; color: var(--text-main);">Pengaturan sitasi
                            </div>
                            <div style="font-size: 12.5px; color: var(--text-muted); margin: 2px 0 14px;">Sesuaikan
                                referensi dan format penulisan dengan kebutuhan penelitianmu.</div>

                            <div class="form-row">
                                <div>
                                    <label class="form-label">Tahun terbit minimum</label>
                                    <select name="tahun_min" class="form-select">
                                        <option value="2018">Semua tahun (2018–2026)</option>
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

                            <div class="form-group" style="margin-bottom: 14px;">
                                <label class="form-label">Format Penomoran Sub-Bab</label>
                                <select name="format_nomor" class="form-select">
                                    <option value="1.1" selected>Desimal (1.1, 1.2, 2.1, 2.2 — Standar Umum)</option>
                                    <option value="A.">Huruf Kapital (A., B., C., 1., 2. — Format Kampus Tertentu)</option>
                                </select>
                            </div>

                            <label class="form-label">Sumber referensi</label>
                            <div class="responsive-grid-3"
                            <label class="form-label">Sumber referensi <span class="req">*</span> <span
                                    style="font-size: 11.5px; font-weight: 500; color: var(--text-muted); margin-left: 4px;">(Pilih minimal 1)</span></label>
                            @php $oldSumberRef = is_array(old('sumber_referensi')) ? old('sumber_referensi') : []; @endphp
                            <div id="sumber-referensi-group" class="responsive-grid-3"
                                style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;">
                                <label
                                    style="display: flex; align-items: center; gap: 8px; padding: 10px 12px; border: 1px solid var(--border); border-radius: 9px; font-size: 13px; font-weight: 500; cursor: pointer;">
                                    <input type="checkbox" name="sumber_referensi[]" value="Umum" checked
                                    <input type="checkbox" name="sumber_referensi[]" value="Umum"
                                        {{ in_array('Umum', $oldSumberRef, true) ? 'checked' : '' }}
                                        onchange="const err=document.getElementById('sumber-referensi-error'); const grp=document.getElementById('sumber-referensi-group'); if(document.querySelectorAll('input[name=\'sumber_referensi[]\']:checked').length>0){if(err)err.style.display='none'; if(grp)grp.style.outline='none';}"
                                        style="accent-color: var(--primary);">
                                    <span>Umum</span>
                                </label>
                                <label
                                    style="display: flex; align-items: center; gap: 8px; padding: 10px 12px; border: 1px solid var(--border); border-radius: 9px; font-size: 13px; font-weight: 500; cursor: pointer;">
                                    <input type="checkbox" name="sumber_referensi[]" value="Terindeks Sinta" checked
                                    <input type="checkbox" name="sumber_referensi[]" value="Terindeks Sinta"
                                        {{ in_array('Terindeks Sinta', $oldSumberRef, true) ? 'checked' : '' }}
                                        onchange="const err=document.getElementById('sumber-referensi-error'); const grp=document.getElementById('sumber-referensi-group'); if(document.querySelectorAll('input[name=\'sumber_referensi[]\']:checked').length>0){if(err)err.style.display='none'; if(grp)grp.style.outline='none';}"
                                        style="accent-color: var(--primary);">
                                    <span>Terindeks Sinta</span>
                                </label>
                                <label
                                    style="display: flex; align-items: center; gap: 8px; padding: 10px 12px; border: 1px solid var(--border); border-radius: 9px; font-size: 13px; font-weight: 500; cursor: pointer;">
                                    <input type="checkbox" name="sumber_referensi[]" value="Terindeks Scopus"
                                        {{ in_array('Terindeks Scopus', $oldSumberRef, true) ? 'checked' : '' }}
                                        onchange="const err=document.getElementById('sumber-referensi-error'); const grp=document.getElementById('sumber-referensi-group'); if(document.querySelectorAll('input[name=\'sumber_referensi[]\']:checked').length>0){if(err)err.style.display='none'; if(grp)grp.style.outline='none';}"
                                        style="accent-color: var(--primary);">
                                    <span>Terindeks Scopus</span>
                                </label>
                            </div>
                            <div id="sumber-referensi-error"
                                style="display: {{ $errors->has('sumber_referensi') ? 'block' : 'none' }}; margin-top: 8px; font-size: 12.5px; color: #dc2626; font-weight: 500;">
                                {{ $errors->first('sumber_referensi') ?: 'Silakan pilih minimal 1 Sumber Referensi.' }}
                            </div>
                        </div>

                        <!-- Card 8: Unggah Artikel atau Buku Referensi Wajib (Opsional) -->
                        <div class="card" style="margin-bottom: 24px;">
                            <div
                                style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                                <div style="font-size: 14px; font-weight: 600; color: var(--text-main);">Unggah artikel
                                    atau buku referensi wajib <span
                                        style="font-size: 11px; font-weight: 500; color: var(--text-muted); margin-left: 6px;">OPSIONAL</span>
                                </div>
                                <span style="font-size: 12px; color: var(--text-muted);">Maks 5 berkas</span>
                            </div>
                            <div style="font-size: 12.5px; color: var(--text-muted); margin-bottom: 12px;">
                                Untuk sumber yang wajib kamu pakai — arahan pembimbing, buku teori, atau jurnal khusus.
                                Sistem membaca isinya dan memasukkannya ke dalam naskah serta Daftar Pustaka.
                            </div>
                            <input type="file" name="referensi_files[]" multiple accept=".pdf,.docx,.doc,.txt"
                                class="form-input" style="padding: 9px 12px; font-size: 13px;">

                            <div class="card-footer-bar">
                                <button type="button" class="btn-outline" onclick="showProjectListView()">Batal</button>
                                <button type="submit" class="btn-primary">Buat Proyek & Buka Workspace →</button>
                            </div>
                        </div>
                    </form>
                </div>
            </section>

            <!-- ========================================================= -->
            <!-- 3. TAB CARI JUDUL SKRIPSI (EXACT SKRIPSITA STYLE) -->
            <!-- ========================================================= -->
            <section class="tab-pane" id="pane-titles">
                <div class="page-eyebrow">Eksplorasi Topik · AI Akademik</div>
                <h1 class="page-title">Cari Judul Skripsi</h1>
                <p class="page-subtitle">Isi topik dan metodologi — sistem langsung sarankan judul terbaik sesuai
                    bidangmu dalam hitungan detik.</p>

                <div class="card">
                    <div class="form-group">
                        <label class="form-label">Topik atau Bidang Penelitian <span class="req">*</span></label>
                        <input type="text" id="title-topik" class="form-input"
                            placeholder="cth: machine learning, manajemen SDM, hukum perdata...">
                    </div>

                    <div class="form-row" style="margin-bottom: 0;">
                        <div>
                            <label class="form-label">Metodologi <span class="req">*</span></label>
                            <select id="title-metode" class="form-select">
                                <option value="">Pilih Metodologi</option>
                                <option value="Kuantitatif (Survei / Regresi)">Kuantitatif (Survei / Regresi / SEM)</option>
                                <option value="Kuantitatif — Data Sekunder">Kuantitatif — Data Sekunder (BPS / BEI / Panel)</option>
                                <option value="Kualitatif (Studi Kasus / Deskriptif)">Kualitatif (Studi Kasus / Deskriptif)</option>
                                <option value="R&D / Pengembangan Sistem">R&D / Pengembangan Sistem IT / AI</option>
                                <option value="PTK (Penelitian Tindakan Kelas)">PTK (Penelitian Tindakan Kelas)</option>
                                <option value="Studi Pustaka (Library Research)">Studi Pustaka (Library Research)</option>
                                <option value="Mixed Method">Mixed Method (Kombinasi)</option>
                                <option value="Hukum Normatif">Hukum Normatif (Perundang-Undangan)</option>
                                <option value="Hukum Empiris">Hukum Empiris (Sosiologi Hukum)</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Program Studi <span class="opt">(opsional)</span></label>
                            <input type="text" id="title-prodi" class="form-input"
                                placeholder="cth: Informatika, Manajemen...">
                        </div>
                    </div>

                    <div class="card-footer-bar">
                        <span class="footer-meta">REKOMENDASI 10 JUDUL TERSTRUKTUR</span>
                        <button type="button" id="btn-find-titles" class="btn-primary"
                            onclick="handleFindTitles()">Temukan 10 Judul →</button>
                    </div>
                </div>

                <!-- Animasi Loading Cari Judul -->
                <div id="titles-loading-box" style="display: none;"></div>

                <!-- Hasil Judul -->
                <div id="titles-result-box" style="display: none; margin-top: 20px;" class="card-flush">
                    <div class="card-header-bar">
                        <span>Rekomendasi 10 Judul Skripsi</span>
                        <span style="font-size: 12px; font-weight: 400; color: var(--text-muted);">Klik "Gunakan Judul"
                            untuk langsung menulis</span>
                    </div>
                    <div id="titles-list"></div>
                </div>
            </section>

            <!-- ========================================================= -->
            <!-- 4. TAB PPT SIDANG (EXACT 4-STEP FLOW + DESIGNER TEMPLATES + PREVIEW) -->
            <!-- ========================================================= -->
            <section class="tab-pane" id="pane-ppt">
                <h1 class="page-title">Generate PPT Sidang</h1>
                <p class="page-subtitle">Slide sidang dari isi skripsimu. Pilih desain template, rapikan isi slide,
                    preview presentasi seperti PowerPoint, lalu unduh <code>.pptx</code>.</p>

                <!-- 4-Step Indicator -->
                <div class="stepper">
                    <button type="button" class="step-pill active" id="ppt-step-btn-1" onclick="setPptStep(1)">
                        <span class="step-num-circle">1</span>
                        <span>Pilih skripsi</span>
                    </button>
                    <button type="button" class="step-pill" id="ppt-step-btn-2"
                        onclick="if(selectedPptProject.title || document.getElementById('ppt-file-input').files.length) setPptStep(2)">
                        <span class="step-num-circle">2</span>
                        <span>Buat slide</span>
                    </button>
                    <button type="button" class="step-pill" id="ppt-step-btn-3"
                        onclick="if(pptSlides.length) setPptStep(3)">
                        <span class="step-num-circle">3</span>
                        <span>Edit & desain tema</span>
                    </button>
                    <button type="button" class="step-pill" id="ppt-step-btn-4"
                        onclick="if(pptSlides.length) openPptGridPreviewStep()">
                        <span class="step-num-circle">4</span>
                        <span>Preview & unduh</span>
                    </button>
                </div>

                <!-- STEP 1: Pilih Skripsi atau Upload File -->
                <!-- STEP 1: PPT Saya + Pilih Skripsi atau Upload File -->
                <div id="ppt-view-step-1">
                    <!-- PPT Saya (Tersimpan Otomatis — Bisa Dilihat, Diedit & Diunduh Ulang Kapan Saja) -->
                    <div class="card-flush" id="saved-ppts-card" style="margin-bottom: 18px;">
                        <div class="card-header-bar">
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <span>PPT Saya</span>
                                <span class="count-badge" id="saved-ppts-count">0</span>
                            </div>
                            <span style="font-size: 12px; font-weight: 500; color: var(--text-muted);">Tersimpan otomatis setelah generate</span>
                        </div>
                        <div id="saved-ppts-list"></div>
                    </div>

                    <div class="card-flush" style="margin-bottom: 18px;">
                        <div class="card-header-bar">
                            <div>Pilih dari Proyek Saya <span class="count-badge">{{ $history->count() }}</span></div>
                        </div>
                        @forelse($history as $proj)
                            <div class="project-row" data-id="{{ $proj->id }}" data-title="{{ e($proj->judul) }}"
                                data-prodi="{{ e($proj->prodi ?? '') }}" data-kampus="{{ e($proj->kampus ?? '') }}"
                                data-nim="{{ e($proj->nim ?? '') }}" data-fakultas="{{ e($proj->fakultas ?? '') }}"
                                data-logo="{{ e($proj->cover_meta['logo_base64'] ?? $defaultIdentity['logo_base64'] ?? '') }}"
                                onclick="chooseProjectElementForPpt(this)">
                                <div class="project-row-left">
                                    <div style="min-width: 0; width: 100%;">
                                        <div class="project-row-title">{{ $proj->judul }}</div>
                                        <div class="project-row-meta">{{ $proj->prodi }} ·
                                            {{ $proj->updated_at->diffForHumans() }}
                                        </div>
                                    </div>
                                </div>
                                <span class="project-row-action">Pilih Skripsi →</span>
                            </div>
                        @empty
                            <div style="padding: 24px; text-align: center; color: var(--text-muted); font-size: 13.5px;">
                                Belum ada proyek di akunmu. Kamu bisa mengunggah file skripsi (.pdf / .docx) di bawah ini.
                            </div>
                        @endforelse
                    </div>

                    <!-- Opsi Upload File PDF/DOCX Langsung -->
                    <div class="card">
                        <div class="form-label" style="margin-bottom: 8px;">Atau unggah file skripsi / proposal (.pdf,
                            .docx)</div>
                        <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 14px;">AI akan membaca isi
                            utuh Bab I–V dari file Word (<code>.docx</code>) atau PDF skripsimu (maks 3,5 MB) lalu menyusun 6–12 slide presentasi sidang secara riil sesuai isi naskah.</p>
                        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                            <input type="file" id="ppt-file-input" class="form-input" accept=".pdf,.docx,.doc,.txt"
                                style="flex: 1; padding: 8px 12px; font-size: 13px;"
                                onchange="onUploadFileForPpt(this)">
                        </div>
                    </div>
                </div>

                <!-- STEP 2: Buat Slide -->
                <div id="ppt-view-step-2" style="display: none;">
                    <div class="card">
                        <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 20px;">
                            <div style="min-width: 0; width: 100%;">
                                <div style="font-size: 11px; font-weight: 600; letter-spacing: 0.05em; color: var(--text-muted); text-transform: uppercase;"
                                    id="ppt-selected-source">SKRIPSI</div>
                                <div style="font-size: 15.5px; font-weight: 700; color: var(--text-main);"
                                    id="ppt-selected-title">-</div>
                            </div>
                        </div>

                        <!-- Form Identitas Mahasiswa, Pembimbing, dan Penguji untuk Slide Cover Sidang -->
                        <div class="ppt-identity-box"
                            style="background: #f8fafc; border: 1px solid var(--border); border-radius: 12px; padding: 18px; margin-bottom: 20px;">
                            <div
                                style="font-size: 13.5px; font-weight: 700; color: var(--text-main); margin-bottom: 4px;">
                                Informasi Mahasiswa &amp; Sidang (Untuk Slide 1 / Cover)</div>
                            <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 14px;">Data ini akan
                                dicantumkan otomatis pada slide judul dan penutup sidang secara resmi.</div>

                            <div class="form-row">
                                <div>
                                    <label class="form-label">Nama Mahasiswa</label>
                                    <input type="text" id="ppt-input-nama" class="form-input" value="{{ $user->name }}"
                                        placeholder="Nama Lengkap Mahasiswa">
                                </div>
                                <div>
                                    <label class="form-label">NIM / NPM</label>
                                    <input type="text" id="ppt-input-nim" class="form-input"
                                        value="{{ $defaultIdentity['nim'] ?? '' }}" placeholder="Nomor Induk Mahasiswa">
                                </div>
                            </div>

                            <div class="responsive-grid-3"
                                style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 14px;">
                                <div>
                                    <label class="form-label">Nama Kampus</label>
                                    <input type="text" id="ppt-input-kampus" class="form-input"
                                        value="{{ $defaultIdentity['kampus'] ?? '' }}"
                                        placeholder="Contoh: UNIVERSITAS INDONESIA">
                                </div>
                                <div>
                                    <label class="form-label">Program Studi</label>
                                    <input type="text" id="ppt-input-prodi" class="form-input"
                                        value="{{ $defaultIdentity['prodi'] ?? '' }}"
                                        placeholder="Contoh: SISTEM INFORMASI">
                                </div>
                                <div>
                                    <label class="form-label">Fakultas</label>
                                    <input type="text" id="ppt-input-fakultas" class="form-input"
                                        value="{{ $defaultIdentity['fakultas'] ?? '' }}" placeholder="Contoh: FASILKOM">
                                </div>
                            </div>

                            <div style="margin-bottom: 14px;">
                                <label class="form-label">Logo Kampus (Opsional - Tampil di Slide Cover Sidang)</label>
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <input type="file" id="ppt-input-logo" class="form-input" accept="image/*"
                                        onchange="onUploadPptLogo(this)"
                                        style="padding: 6px 10px; font-size: 12.5px; flex: 1;">
                                    <div id="ppt-logo-preview-box"
                                        style="display: none; width: 36px; height: 36px; border-radius: 6px; border: 1px solid #cbd5e1; background: #ffffff; align-items: center; justify-content: center; overflow: hidden;">
                                        <img id="ppt-logo-preview-img" src="" alt="Logo"
                                            style="width: 100%; height: 100%; object-fit: contain;">
                                    </div>
                                </div>
                            </div>

                            <div class="responsive-grid-2"
                                style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 0;">
                                <div
                                    style="background: #ffffff; padding: 12px 14px; border: 1px solid #bfdbfe; border-radius: 10px;">
                                    <div
                                        style="font-size: 12px; font-weight: 700; color: var(--primary); margin-bottom: 8px;">
                                        Dosen Pembimbing</div>
                                    <div style="display: flex; flex-direction: column; gap: 6px;">
                                        <input type="text" id="ppt-input-pembimbing-1" class="form-input"
                                            placeholder="Pembimbing 1 (dengan gelar)">
                                        <input type="text" id="ppt-input-pembimbing-2" class="form-input"
                                            placeholder="Pembimbing 2 (opsional)">
                                    </div>
                                </div>
                                <div
                                    style="background: #ffffff; padding: 12px 14px; border: 1px solid var(--border); border-radius: 10px;">
                                    <div
                                        style="font-size: 12px; font-weight: 700; color: var(--text-main); margin-bottom: 8px;">
                                        Dosen Penguji</div>
                                    <div style="display: flex; flex-direction: column; gap: 6px;">
                                        <input type="text" id="ppt-input-penguji-1" class="form-input"
                                            placeholder="Penguji 1 (dengan gelar)">
                                        <input type="text" id="ppt-input-penguji-2" class="form-input"
                                            placeholder="Penguji 2 (opsional)">
                                    </div>
                                </div>
                            </div>

                            <!-- Pilihan Jumlah Slide Sidang (6 - 12 Slide) -->
                            <div style="margin-top: 14px; background: #ffffff; padding: 14px 16px; border: 1.5px solid #bfdbfe; border-radius: 12px; box-shadow: 0 1px 3px rgba(37,99,235,0.06);">
                                <div class="ppt-slide-count-head" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 10px;">
                                    <label for="ppt-input-slide-count" style="font-size: 13px; font-weight: 700; color: var(--text-main); display: flex; align-items: center; gap: 7px;">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" style="flex-shrink: 0;">
                                            <rect x="2" y="3" width="20" height="14" rx="2" />
                                            <line x1="8" y1="21" x2="16" y2="21" />
                                            <line x1="12" y1="17" x2="12" y2="21" />
                                        </svg>
                                        <span>Jumlah Slide Presentasi Sidang</span>
                                    </label>
                                    <span id="ppt-slide-count-badge" style="font-size: 12px; font-weight: 700; padding: 4px 11px; border-radius: 999px; background: #dbeafe; color: #1d4ed8; white-space: nowrap; display: inline-flex; align-items: center;">
                                        8 Slide (Ringkas &amp; Padat)
                                    </span>
                                </div>
                                <div class="ppt-slide-count-controls" style="display: flex; align-items: center; gap: 12px;">
                                    <input type="range" id="ppt-input-slide-count" min="6" max="12" value="8" step="1"
                                        style="flex: 1; width: 100%; accent-color: var(--primary); cursor: pointer;"
                                        oninput="updateSlideCountUI(this.value)">
                                    <select id="ppt-select-slide-count" class="form-select" style="width: 135px; font-size: 13px; font-weight: 600;"
                                        onchange="updateSlideCountUI(this.value)">
                                        <option value="6">6 Slide</option>
                                        <option value="7">7 Slide</option>
                                        <option value="8" selected>8 Slide</option>
                                        <option value="9">9 Slide</option>
                                        <option value="10">10 Slide</option>
                                        <option value="11">11 Slide</option>
                                        <option value="12">12 Slide</option>
                                    </select>
                                </div>
                                <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 6px;">
                                    Pilih 6–8 slide untuk presentasi kilat (10–15 menit), atau 9–12 slide untuk pembahasan skripsi lengkap & komprehensif.
                                </div>
                            </div>
                        </div>

                        <button type="button" id="btn-generate-ppt" class="btn-primary btn-full"
                            onclick="handleGeneratePpt()">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <rect x="2" y="3" width="20" height="14" rx="2" />
                                <line x1="8" y1="21" x2="16" y2="21" />
                                <line x1="12" y1="17" x2="12" y2="21" />
                            </svg>
                            <span>Buat slide sidang sekarang</span>
                        </button>

                        <p style="font-size: 12.5px; color: var(--text-muted); margin-top: 12px;">
                            Sekitar 10–30 detik. Slide disusun dari isi skripsimu, lengkap dengan catatan bicara, dan
                            bisa kamu ubah setelahnya.
                        </p>
                    </div>
                    <div id="ppt-loading-box" style="display: none;"></div>
                </div>

                <!-- STEP 3: Edit Slide, Pilih Desain Template Cantik / Basic, & Preview -->
                <div id="ppt-view-step-3" style="display: none;">
                    <!-- Card Pemilih Template Slide (Basic + Tema Cantik Modern) -->
                    <div class="card" style="padding: 16px 18px; margin-bottom: 16px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 12px;">
                            <div>
                                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                    <span style="font-size: 13.5px; font-weight: 700; color: var(--text-main);">Pilih Desain Template Slide</span>
                                    <span id="ppt-autosave-status" style="font-size: 11px; font-weight: 600; color: #059669; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 2px 9px; border-radius: 999px; white-space: nowrap;">✓ Tersimpan di PPT Saya</span>
                                </div>
                                <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">Tersedia tema Basic klasik maupun tema desain modern yang langsung diterapkan saat unduh <code>.pptx</code>.</div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                <button type="button" class="btn-outline" onclick="addNewSlide()">+ Tambah Slide</button>
                                <button type="button" class="btn-outline"
                                    style="border-color: var(--primary); color: var(--primary); font-weight: 600;"
                                    onclick="openFullscreenSlideshow()">
                                    ▶ Preview Slide Show
                                </button>
                                <button type="button" class="btn-primary" style="padding: 8px 15px; font-size: 13px;"
                                    onclick="openPptGridPreviewStep()">
                                    Preview Semua &amp; Unduh (.pptx) →
                                </button>
                            </div>
                        </div>

                        <div class="theme-card-grid">
                            <button type="button" class="theme-chip" onclick="setPptTheme('sidang_crimson', this)">
                                <div class="theme-swatch"
                                    style="background: linear-gradient(135deg, #be123c 0%, #881337 100%); border-top: 5px solid #fda4af;">
                                </div>
                                <span class="theme-chip-name">Sidang Crimson (Merah)</span>
                            </button>
                            <button type="button" class="theme-chip active"
                                onclick="setPptTheme('royal_academic', this)">
                                <div class="theme-swatch"
                                    style="background: linear-gradient(135deg, #ffffff 0%, #eff6ff 100%); border-left: 6px solid #2563eb;">
                                </div>
                                <span class="theme-chip-name">Royal Blue Kampus</span>
                            </button>
                            <button type="button" class="theme-chip" onclick="setPptTheme('canva_editorial', this)">
                                <div class="theme-swatch" style="background: #fbf8f3; border-top: 5px solid #ea580c;">
                                </div>
                                <span class="theme-chip-name">Canva Cream Aesthetic</span>
                            </button>
                            <button type="button" class="theme-chip" onclick="setPptTheme('emerald_executive', this)">
                                <div class="theme-swatch" style="background: #f4fbf7; border-top: 5px solid #047857;">
                                </div>
                                <span class="theme-chip-name">Emerald Botanical</span>
                            </button>
                            <button type="button" class="theme-chip" onclick="setPptTheme('swiss_minimal', this)">
                                <div class="theme-swatch"
                                    style="background: #ffffff; border-top: 5px solid #111827; border-bottom: 2px solid #111827;">
                                </div>
                                <span class="theme-chip-name">Swiss Grid Minimal</span>
                            </button>
                            <button type="button" class="theme-chip" onclick="setPptTheme('basic_navy', this)">
                                <div class="theme-swatch" style="background: #1e1b4b;"></div>
                                <span class="theme-chip-name">Basic Navy (Klasik)</span>
                            </button>
                            <button type="button" class="theme-chip" onclick="setPptTheme('basic_white', this)">
                                <div class="theme-swatch" style="background: #ffffff; border: 1px solid #cbd5e1;"></div>
                                <span class="theme-chip-name">Basic Putih (Polos)</span>
                            </button>
                            <button type="button" class="theme-chip" onclick="setPptTheme('maroon_prestige', this)">
                                <div class="theme-swatch" style="background: #fff5f5; border-top: 5px solid #991b1b;">
                                </div>
                                <span class="theme-chip-name">Maroon Prestige</span>
                            </button>
                            <button type="button" class="theme-chip" onclick="setPptTheme('tech_dark', this)">
                                <div class="theme-swatch" style="background: #0b0f19; border-top: 5px solid #06b6d4;">
                                </div>
                                <span class="theme-chip-name">Dark Tech Cyber</span>
                            </button>
                            <button type="button" class="theme-chip" onclick="setPptTheme('pastel_lavender', this)">
                                <div class="theme-swatch" style="background: #f5f3ff; border-top: 5px solid #8b5cf6;">
                                </div>
                                <span class="theme-chip-name">Lavender Academic</span>
                            </button>
                            <button type="button" class="theme-chip" onclick="setPptTheme('nordic_teal', this)">
                                <div class="theme-swatch" style="background: #f0fdfa; border-top: 5px solid #0d9488;">
                                </div>
                                <span class="theme-chip-name">Nordic Slate Teal</span>
                            </button>
                            <button type="button" class="theme-chip" onclick="setPptTheme('warm_terracotta', this)">
                                <div class="theme-swatch" style="background: #fffbeb; border-top: 5px solid #ea580c;">
                                </div>
                                <span class="theme-chip-name">Terracotta Modern</span>
                            </button>
                            <button type="button" class="theme-chip" onclick="setPptTheme('classic_gold', this)">
                                <div class="theme-swatch" style="background: #0f172a; border-top: 5px solid #fbbf24;">
                                </div>
                                <span class="theme-chip-name">Oxford Navy Gold</span>
                            </button>
                        </div>
                    </div>

                    <!-- 16:9 Editable PowerPoint Canvas (MURNI SLIDE TANPA TOMBOL DI DALAMNYA) -->
                    <div id="ppt-canvas-box" class="ppt-canvas ppt-theme-royal_academic">
                        <div>
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span id="slide-campus-badge" class="ppt-campus-pill"
                                        style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; background: rgba(255,255,255,0.92); border-radius: 999px; font-size: 11px; font-weight: 700; color: #1e293b; border: 1px solid rgba(148,163,184,0.35); box-shadow: 0 1px 3px rgba(0,0,0,0.06);">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#2563eb"
                                            stroke-width="2.2">
                                            <path d="M22 10v6M2 10l10-5 10 5-10 5z" />
                                            <path d="M6 12v5c0 2 3 3 6 3s6-1 6-3v-5" />
                                        </svg>
                                        <span id="slide-campus-name">UNIVERSITAS</span>
                                    </span>
                                    <span id="slide-section-label" class="ppt-badge" contenteditable="true"
                                        onblur="syncCurrentSlideEdit()">BAGIAN SLIDE</span>
                                </div>
                                <span id="slide-counter-label"
                                    style="font-size: 12px; font-weight: 700; opacity: 0.75;">Slide 1 / 9</span>
                            </div>
                            <h2 id="slide-title-edit" contenteditable="true" onblur="syncCurrentSlideEdit()"
                                style="font-size: 24px; font-weight: 700; line-height: 1.3; margin-bottom: 8px; outline: none;">
                                Judul Slide</h2>
                            <div id="slide-subtitle-edit" contenteditable="true" onblur="syncCurrentSlideEdit()"
                                style="font-size: 14px; opacity: 0.85; margin-bottom: 20px; font-weight: 500; outline: none;">
                                Subjudul</div>
                            <ul id="slide-bullets-edit" contenteditable="true" onblur="syncCurrentSlideEdit()"
                                style="padding-left: 18px; font-size: 14.5px; line-height: 1.7; outline: none; display: flex; flex-direction: column; gap: 8px;">
                            </ul>
                        </div>
                    </div>
                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 8px; text-align: center;">💡 Klik
                        teks pada slide di atas untuk mengedit langsung judul, poin, atau subjudul.</div>

                    <!-- TOMBOL NAVIGASI SLIDE DI LUAR EDITOR (JELAS & MUDAH DIKLIK) -->
                    <div class="ppt-nav-outside">
                        <button type="button" class="ppt-nav-btn" id="btn-ppt-prev" onclick="prevSlide()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.2">
                                <polyline points="15 18 9 12 15 6" />
                            </svg>
                            <span>Slide Sebelumnya</span>
                        </button>

                        <div class="ppt-num-strip" id="ppt-num-strip">
                            <!-- Diisi nomor slide 1..N oleh JS -->
                        </div>

                        <button type="button" class="ppt-nav-btn" id="btn-ppt-next" onclick="nextSlide()">
                            <span>Slide Selanjutnya</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.2">
                                <polyline points="9 18 15 12 9 6" />
                            </svg>
                        </button>
                    </div>

                    <!-- Catatan Bicara Presenter (Editable) -->
                    <div class="card" style="margin-top: 14px;">
                        <label class="form-label">Catatan Bicara Presenter (Speaker Notes) — bisa diedit</label>
                        <textarea id="slide-notes-edit" class="form-textarea" style="min-height: 80px;"
                            oninput="syncCurrentSlideEdit()"></textarea>
                    </div>
                </div>

                <!-- STEP 4: PRATINJAU SEMUA SLIDE (SLIDE SORTER PREVIEW) SEBELUM UNDUH -->
                <div id="ppt-view-step-4" style="display: none;">
                    <div class="card"
                        style="margin-bottom: 18px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                        <div>
                            <div style="font-size: 15px; font-weight: 700; color: var(--text-main);">Pratinjau Seluruh
                                Slide Presentasi</div>
                            <div style="font-size: 13px; color: var(--text-muted);">Periksa tampilan seluruh slide di
                                bawah ini atau putar mode layar penuh sebelum mengunduh file PowerPoint
                                (<code>.pptx</code>).</div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            <button type="button" class="btn-outline" onclick="setPptStep(3)">← Kembali Edit
                                Slide</button>
                            <button type="button" class="btn-outline"
                                style="border-color: var(--primary); color: var(--primary); font-weight: 600;"
                                onclick="openFullscreenSlideshow()">
                                ▶ Preview Layar Penuh
                            </button>
                            <button type="button" class="btn-primary" onclick="downloadPptxFile()">
                                ⬇ Unduh File PowerPoint (.pptx)
                            </button>
                        </div>
                    </div>

                    <div id="ppt-grid-preview-list"
                        style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 16px;">
                        <!-- Di-render oleh JS: semua slide tampil rapi sebelum download -->
                    </div>
                </div>

                <!-- MODAL PREVIEW PRESENTASI LAYAR PENUH (POWERPOINT SLIDE SHOW MODE) -->
                <div id="ppt-slideshow-modal" onclick="if(event.target === this) closeFullscreenSlideshow()"
                    style="display: none; position: fixed; inset: 0; background: #0b1120 !important; z-index: 99999 !important; flex-direction: column; align-items: center; justify-content: center; padding: 16px; overflow-y: auto;">
                    <div
                        style="width: 100%; max-width: 960px; display: flex; flex-direction: column; align-items: center; justify-content: center; margin-bottom: 12px; color: #ffffff; gap: 8px; text-align: center;">
                        <div style="font-size: 13.5px; font-weight: 600; text-align: center;">
                            Mode Preview Presentasi PowerPoint · <span id="fs-slide-counter"
                                style="color: #93c5fd;">Slide 1 / 9</span>
                        </div>
                        <div
                            style="display: flex; gap: 8px; align-items: center; justify-content: center; flex-wrap: wrap;">
                            <button type="button" class="btn-primary"
                                style="padding: 7px 14px; font-size: 12px; border-radius: 8px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(37,99,235,0.35);"
                                onclick="downloadPptxFile()">⬇ Unduh (.pptx)</button>
                            <button type="button" onclick="closeFullscreenSlideshow()"
                                style="padding: 7px 14px; font-size: 12px; background: #dc2626 !important; color: #ffffff !important; border: 1px solid #b91c1c; border-radius: 8px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(220,38,38,0.4);">
                                ✕ Tutup Preview
                            </button>
                        </div>
                    </div>

                    <div id="fs-slide-canvas" class="ppt-canvas ppt-theme-royal_academic"
                        style="width: 100%; max-width: 960px; min-height: auto; padding: 32px 36px; box-sizing: border-box; overflow: hidden;">
                        <div>
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 8px;">
                                <div
                                    style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; max-width: calc(100% - 60px);">
                                    <span id="fs-slide-campus-badge" class="ppt-campus-pill"
                                        style="display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; background: rgba(255,255,255,0.92); border-radius: 999px; font-size: 11px; font-weight: 700; color: #1e293b; border: 1px solid rgba(148,163,184,0.35); box-shadow: 0 1px 3px rgba(0,0,0,0.06); white-space: nowrap;">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#2563eb"
                                            stroke-width="2.2">
                                            <path d="M22 10v6M2 10l10-5 10 5-10 5z" />
                                            <path d="M6 12v5c0 2 3 3 6 3s6-1 6-3v-5" />
                                        </svg>
                                        <span id="fs-slide-campus-name">UNIVERSITAS</span>
                                    </span>
                                    <span id="fs-slide-section" class="ppt-badge" style="white-space: nowrap;">BAGIAN
                                        SLIDE</span>
                                </div>
                                <span id="fs-slide-num-tag"
                                    style="font-size: 12px; font-weight: 700; opacity: 0.75; white-space: nowrap;">1 /
                                    9</span>
                            </div>
                            <h2 id="fs-slide-title"
                                style="font-size: 24px; font-weight: 700; line-height: 1.3; margin-bottom: 8px; word-break: break-word;">
                                -</h2>
                            <div id="fs-slide-subtitle"
                                style="font-size: 14px; opacity: 0.85; margin-bottom: 18px; font-weight: 500; word-break: break-word;">
                                -</div>
                            <ul id="fs-slide-bullets"
                                style="padding-left: 20px; font-size: 14.5px; line-height: 1.65; display: flex; flex-direction: column; gap: 8px;">
                            </ul>
                        </div>
                    </div>

                    <div
                        style="width: 100%; max-width: 960px; margin-top: 12px; display: flex; align-items: center; justify-content: space-between; background: #1e293b; padding: 8px 14px; border-radius: 10px; color: #f8fafc; gap: 8px;">
                        <button type="button" class="ppt-nav-btn"
                            style="flex: 1; max-width: 160px; justify-content: center; padding: 7px 10px; font-size: 12px; white-space: nowrap;"
                            onclick="prevSlide(); renderFullscreenSlide();">← Sebelumnya</button>
                        <div id="fs-speaker-notes"
                            style="font-size: 11.5px; color: #cbd5e1; text-align: center; line-height: 1.35; flex: 2; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                        </div>
                        <button type="button" class="ppt-nav-btn"
                            style="flex: 1; max-width: 160px; justify-content: center; padding: 7px 10px; font-size: 12px; white-space: nowrap;"
                            onclick="nextSlide(); renderFullscreenSlide();">Selanjutnya →</button>
                    </div>
                </div>
            </section>

            <!-- ========================================================= -->
            <!-- 5. TAB SIMULASI SIDANG (PILIH SKRIPSI / UPLOAD PDF -> JAWAB 1-5 -> SKOR & SARAN) -->
            <!-- ========================================================= -->
            <section class="tab-pane" id="pane-defense">
                <h1 class="page-title">Simulasi Sidang</h1>
                <p class="page-subtitle">Latihan tanya-jawab berdasarkan isi skripsimu, lengkap dengan skor per
                    pertanyaan dan saran jawaban.</p>

                <div class="stepper">
                    <button type="button" class="step-pill active" id="def-step-btn-1" onclick="setDefenseStep(1)">
                        <span class="step-num-circle">1</span>
                        <span>Pilih skripsi</span>
                    </button>
                    <button type="button" class="step-pill" id="def-step-btn-2" onclick="if(defenseQuestions.length) setDefenseStep(2)">
                        <span class="step-num-circle">2</span>
                        <span>Latihan tanya-jawab</span>
                    </button>
                    <button type="button" class="step-pill" id="def-step-btn-3" onclick="if(document.getElementById('def-score-bars').children.length) setDefenseStep(3)">
                        <span class="step-num-circle">3</span>
                        <span>Evaluasi & skor</span>
                    </button>
                </div>

                <!-- STEP 1: Pilih Skripsi atau Upload File PDF/DOCX -->
                <div id="def-view-step-1">
                    <div id="defense-loading-box" style="display: none; margin-bottom: 18px;"></div>

                    <!-- Pengaturan Parameter Simulasi Sidang -->
                    <div class="card"
                        style="margin-bottom: 18px; background: #f8fafc; border: 1px solid var(--border);">
                        <div style="font-size: 13.5px; font-weight: 700; color: var(--text-main); margin-bottom: 4px;">
                            Pengaturan Karakter Penguji &amp; Parameter Sidang</div>
                        <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 14px;">Atur tingkat
                            kesulitan dosen penguji dan jumlah pertanyaan sebelum memulai ujian.</div>

                        <div class="responsive-grid-3"
                            style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                            <div>
                                <label class="form-label">Karakter Dosen Penguji</label>
                                <select id="def-mode-dosen" class="form-select">
                                    <option value="kritis" selected>Kritis &amp; Metodologis (Standar)</option>
                                    <option value="santai">Santai &amp; Bimbingan (Ramah)</option>
                                    <option value="tegas">Tegas &amp; Formal (Lugas)</option>
                                    <option value="galak">Galak / Killer (Uji Mental)</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Jumlah Pertanyaan</label>
                                <select id="def-jumlah-soal" class="form-select">
                                    <option value="4">4 Pertanyaan (Cepat)</option>
                                    <option value="5" selected>5 Pertanyaan (Standar)</option>
                                    <option value="6">6 Pertanyaan (Mendalam)</option>
                                    <option value="8">8 Pertanyaan (Komprehensif)</option>
                                    <option value="10">10 Pertanyaan (Sangat Lengkap)</option>
                                    <option value="12">12 Pertanyaan (Simulasi Penuh)</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Standar Kelulusan (Passing Grade)</label>
                                <select id="def-min-score" class="form-select">
                                    <option value="6.0">Skor Minimal 6.0 / 10</option>
                                    <option value="6.5">Skor Minimal 6.5 / 10</option>
                                    <option value="7.0" selected>Skor Minimal 7.0 / 10 (Standar B)</option>
                                    <option value="7.5">Skor Minimal 7.5 / 10 (Standar B+)</option>
                                    <option value="8.0">Skor Minimal 8.0 / 10 (Standar A)</option>
                                    <option value="8.5">Skor Minimal 8.5 / 10 (Cum Laude)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="card-flush" style="margin-bottom: 18px;">
                        <div class="card-header-bar">
                            <div>Pilih skripsi untuk diujikan <span class="count-badge">{{ $history->count() }}</span>
                            </div>
                        </div>
                        @forelse($history as $proj)
                            <div class="project-row"
                                data-id="{{ $proj->id }}"
                                data-title="{{ e($proj->judul) }}"
                                onclick="startDefenseWithProject(this.dataset.id, this.dataset.title)">
                                <div class="project-row-left">
                                    <div style="min-width: 0; width: 100%;">
                                        <div class="project-row-title">{{ $proj->judul }}</div>
                                        <div class="project-row-meta">{{ $proj->prodi }} · {{ $proj->metode_penelitian }}
                                        </div>
                                    </div>
                                </div>
                                <span class="project-row-action">Mulai Sidang →</span>
                            </div>
                        @empty
                            <div style="padding: 24px; text-align: center; color: var(--text-muted); font-size: 13.5px;">
                                Belum ada proyek tersimpan. Unggah file skripsimu di bawah ini untuk memulai simulasi.
                            </div>
                        @endforelse
                    </div>

                    <div class="card">
                        <div class="form-label">Atau unggah file skripsi / proposal (.pdf, .docx)</div>
                        <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 14px;">AI akan membaca isi
                            utuh Bab I–V dari file Word/PDF (maks 3,5 MB), menyusun pertanyaan penguji riil sesuai naskahmu, dan menilai jawaban berdasarkan data aslimu.</p>
                        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                            <input type="file" id="def-file-input" class="form-input" accept=".pdf,.docx,.doc,.txt"
                                style="flex: 1; min-width: 200px; padding: 8px 12px; font-size: 13px;">
                            <button type="button" id="btn-start-def-file" class="btn-primary"
                                onclick="startDefenseWithFile()">Mulai Simulasi →</button>
                        </div>
                    </div>
                </div>

                <!-- STEP 2: Jawab Pertanyaan Satu per Satu -->
                <div id="def-view-step-2" style="display: none;">
                    <div id="def-doc-status-pill" style="display: none; margin-bottom: 12px; padding: 9px 14px; border-radius: 10px; background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; font-size: 12.5px; font-weight: 600;"></div>
                    <div class="card">
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 12px;">
                            <span style="font-size: 12.5px; font-weight: 600; color: var(--primary);"
                                id="def-q-category">Bab I · Urgensi Penelitian</span>
                            <span style="font-size: 12.5px; color: var(--text-muted);" id="def-q-counter">Pertanyaan 1
                                dari 5</span>
                        </div>

                        <h3 id="def-q-text"
                            style="font-size: 17px; font-weight: 600; line-height: 1.5; color: var(--text-main); margin-bottom: 12px;">
                            -</h3>

                        <div id="def-q-hint"
                            style="padding: 10px 14px; background: var(--bg-subtle); border: 1px solid var(--border-light); border-radius: 9px; font-size: 13px; color: var(--text-secondary); margin-bottom: 18px;">
                        </div>

                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Jawabanmu</label>
                            <textarea id="def-answer-input" class="form-textarea"
                                placeholder="Ketik jawaban argumen sidangmu di sini..."></textarea>
                        </div>

                        <div class="card-footer-bar">
                            <span class="footer-meta" id="def-progress-label">JAWAB SATU PER SATU</span>
                            <button type="button" id="btn-next-def-q" class="btn-primary"
                                onclick="submitDefenseAnswer()">Simpan & Pertanyaan Berikutnya →</button>
                        </div>
                    </div>
                    <div id="defense-eval-loading-box" style="display: none;"></div>
                </div>

                <!-- STEP 3: Hasil Evaluasi (Ringkasan | Detail Jawaban | Saran + Skor per Pertanyaan) -->
                <div id="def-view-step-3" style="display: none;">
                    <div class="card" style="margin-bottom: 18px;">
                        <div
                            style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px; padding-bottom: 18px; border-bottom: 1px solid var(--border-light);">
                            <div>
                                <div
                                    style="font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">
                                    HASIL SIMULASI SIDANG</div>
                                <h3 id="def-eval-verdict"
                                    style="font-size: 20px; font-weight: 700; color: var(--text-main); margin-top: 2px;">
                                    -</h3>
                            </div>
                            <div style="text-align: right; display: flex; align-items: center; gap: 12px;">
                                <span id="def-eval-grade-badge" style="display: inline-flex; align-items: center; justify-content: center; min-width: 44px; height: 44px; padding: 0 12px; border-radius: 12px; background: #dbeafe; color: #1d4ed8; font-size: 18px; font-weight: 800;">-</span>
                                <div>
                                    <div style="font-size: 28px; font-weight: 700; color: var(--primary);"
                                        id="def-eval-score">0.0/10</div>
                                    <div style="font-size: 12px; color: var(--text-muted);" id="def-eval-min-label">Rata-rata Skor Jawaban</div>
                                </div>
                            </div>
                        </div>

                        <!-- Sub-tabs: Ringkasan | Detail Jawaban | Saran -->
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin: 16px 0; border-bottom: 1px solid var(--border-light); padding-bottom: 12px;">
                            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                <button type="button" class="btn-outline" id="eval-tab-summary"
                                    style="background: var(--bg-muted); font-weight: 600;"
                                    onclick="switchEvalSubtab('summary')">Ringkasan</button>
                                <button type="button" class="btn-outline" id="eval-tab-details"
                                    onclick="switchEvalSubtab('details')">Detail Jawaban & Saran</button>
                            </div>
                            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                <button type="button" class="btn-outline"
                                    style="display:inline-flex; align-items:center; gap:6px;"
                                    onclick="restartCurrentDefense()">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M23 4v6h-6" />
                                        <path d="M1 20v-6h6" />
                                        <path
                                            d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15" />
                                    </svg>
                                    <span>Ulangi Simulasi Ini</span>
                                </button>
                                <button type="button" class="btn-outline"
                                    style="display:inline-flex; align-items:center; gap:6px;"
                                    onclick="setDefenseStep(1)">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path
                                            d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z" />
                                    </svg>
                                    <span>Pilih Skripsi Lain</span>
                                </button>
                            </div>
                        </div>

                        <!-- Subtab 1: Ringkasan & Skor per Pertanyaan -->
                        <div id="eval-pane-summary">
                            <p id="def-eval-summary"
                                style="font-size: 14px; color: var(--text-secondary); line-height: 1.65; margin-bottom: 20px;">
                            </p>
                            <div
                                style="font-size: 11.5px; font-weight: 700; color: var(--text-muted); letter-spacing: 0.05em; text-transform: uppercase; margin-bottom: 12px;">
                                SKOR PER PERTANYAAN</div>
                            <div id="def-score-bars"></div>
                        </div>

                        <!-- Subtab 2: Detail Jawaban & Saran Jawaban Terbaik -->
                        <div id="eval-pane-details" style="display: none;">
                            <div id="def-details-list" style="display: flex; flex-direction: column; gap: 14px;"></div>
                        </div>

                        <div class="card-footer-bar" style="margin-top: 20px;">
                            <button type="button" class="btn-outline"
                                style="display:inline-flex; align-items:center; gap:6px;"
                                onclick="restartCurrentDefense()">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <path d="M23 4v6h-6" />
                                    <path d="M1 20v-6h6" />
                                    <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15" />
                                </svg>
                                <span>Ulangi Latihan Tanya-Jawab</span>
                            </button>
                            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                <button type="button" class="btn-outline"
                                    style="display:inline-flex; align-items:center; gap:6px;"
                                    onclick="setDefenseStep(1)">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path
                                            d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z" />
                                    </svg>
                                    <span>Pilih Skripsi Lain</span>
                                </button>
                                <button type="button" class="btn-primary"
                                    style="display:inline-flex; align-items:center; gap:6px;"
                                    onclick="openTab('dashboard', 'Dashboard')">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <polyline points="20 6 9 17 4 12" />
                                    </svg>
                                    <span>Selesai &amp; Kembali ke Dashboard</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ========================================================= -->
            <!-- 6. TAB CARI JURNAL & DOWNLOAD PDF ASLI -->
            <!-- ========================================================= -->
            <section class="tab-pane" id="pane-journals">
                <div class="page-eyebrow">Referensi Ilmiah Open-Access</div>
                <h1 class="page-title">Cari Jurnal &amp; Unduh PDF Asli</h1>
                <p class="page-subtitle">Temukan jurnal penelitian relevan dari Indonesia (Sinta/Garuda) maupun
                    Internasional (Scopus/Global) lengkap dengan tautan unduh PDF asli dan sitasi APA.</p>

                <div class="card">
                    <div class="form-row" style="margin-bottom: 0;">
                        <div>
                            <label class="form-label">Kata Kunci / Variabel Penelitian <span
                                    class="req">*</span></label>
                            <input type="text" id="journal-keyword" class="form-input"
                                placeholder="cth: naive bayes sentimen, beban kerja turnover intention...">
                        </div>
                        <div>
                            <label class="form-label">Sumber Jurnal</label>
                            <select id="journal-scope" class="form-select">
                                <option value="all">Semua (Jurnal Indonesia + Internasional)</option>
                                <option value="id">Khusus Jurnal Indonesia (Nasional / Sinta)</option>
                                <option value="int">Khusus Jurnal Internasional (Global / Scopus)</option>
                            </select>
                        </div>
                    </div>

                    <div class="card-footer-bar">
                        <span class="footer-meta">DATABASE OPENALEX (INDONESIA &amp; INTERNASIONAL) + SCHOLAR PDF</span>
                        <button type="button" id="btn-find-journals" class="btn-primary"
                            onclick="handleFindJournals()">Cari Jurnal →</button>
                    </div>
                </div>

                <div id="journals-loading-box" style="display: none;"></div>

                <div id="journals-result-box" style="display: none; margin-top: 20px;" class="card-flush">
                    <div class="card-header-bar">
                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            <span style="font-weight: 700; color: var(--text-main);">Daftar Artikel Jurnal
                                Relevan</span>
                            <div style="display: inline-flex; gap: 5px; flex-wrap: wrap;">
                                <button type="button" class="btn-outline journal-filter-btn" style="padding: 3px 10px; font-size: 11.5px; background: var(--primary-soft); color: var(--primary); border-color: #bfdbfe;" onclick="filterJournalResults('all', this)">Semua</button>
                                <button type="button" class="btn-outline journal-filter-btn" style="padding: 3px 10px; font-size: 11.5px;" onclick="filterJournalResults('Indonesia', this)">Indonesia</button>
                                <button type="button" class="btn-outline journal-filter-btn" style="padding: 3px 10px; font-size: 11.5px;" onclick="filterJournalResults('Internasional', this)">Internasional</button>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            @if($history->count() > 0)
                                <select id="journal-target-project" class="form-select"
                                    style="height: 31px; padding: 0 26px 0 9px; font-size: 11.5px; width: auto; max-width: 220px;"
                                    title="Pilih proyek tujuan untuk tombol Simpan ke Proyek">
                                    @foreach($history as $hp)
                                        <option value="{{ $hp->id }}">{{ \Illuminate\Support\Str::limit($hp->judul, 38) }}</option>
                                    @endforeach
                                </select>
                            @endif
                            <button type="button" class="btn-outline" style="padding: 5px 11px; font-size: 12px;"
                                onclick="downloadJournalListTxt()">Unduh Daftar Sitasi (.txt)</button>
                        </div>
                    </div>
                    <div id="journals-list"></div>
                </div>
            </section>

            <!-- ========================================================= -->
            <!-- 7. TAB PARAFRASE AKADEMIK PRO (ANTI-TURNITIN & DIFF CHECK) -->
            <!-- ========================================================= -->
            <section class="tab-pane" id="pane-paraphrase">
                <div class="page-eyebrow">Penulisan Akademik · Anti-Plagiarisme</div>
                <h1 class="page-title">Parafrase Akademik Pro</h1>
                <p class="page-subtitle">Ubah struktur kalimat untuk menurunkan kemiripan Turnitin tanpa merusak sitasi, angka statistik, maupun makna ilmiah sesuai KBBI &amp; EYD V.</p>

                <div class="card">
                    <!-- Sumber Input Switcher -->
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid var(--border-light);">
                        <div class="plag-mode-tabs" style="margin-bottom: 0; flex: 1 1 280px; max-width: 440px;" role="tablist">
                            <button type="button" class="plag-mode-btn active" id="para-src-btn-text" onclick="setParaSourceMode('text')">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                <span>Tempel Teks</span>
                            </button>
                            <button type="button" class="plag-mode-btn" id="para-src-btn-file" onclick="setParaSourceMode('file')">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                <span>Upload File</span>
                            </button>
                            <button type="button" class="plag-mode-btn" id="para-src-btn-project" onclick="setParaSourceMode('project')">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                                <span>Proyek Saya</span>
                            </button>
                        </div>
                        <button type="button" class="btn-outline" style="padding: 7px 13px; font-size: 12.5px; flex-shrink: 0;" onclick="resetParaphraseStudio()">Reset</button>
                    </div>

                    <!-- Impor dari File -->
                    <div id="para-import-file-box" style="display: none; margin-bottom: 14px; padding: 12px 14px; background: var(--bg-subtle); border: 1px dashed var(--border); border-radius: 10px;">
                        <label class="form-label">Pilih File Skripsi / Referensi (.docx, .pdf, atau .txt)</label>
                        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                            <input type="file" id="para-file-input" class="form-input" accept=".docx,.pdf,.txt" style="flex: 1; min-width: 220px; padding: 7px 10px; font-size: 13px;" onchange="importFileToParaphrase(this)">
                        </div>
                        <div id="para-file-status" style="font-size: 12px; color: var(--text-muted); margin-top: 6px;">Teks hasil ekstraksi langsung dimuat ke kotak di bawah (maks 15.000 karakter).</div>
                    </div>

                    <!-- Impor dari Proyek -->
                    <div id="para-import-proj-box" style="display: none; margin-bottom: 14px; padding: 12px 14px; background: var(--bg-subtle); border: 1px solid var(--border); border-radius: 10px;">
                        <div class="form-row" style="margin-bottom: 0;">
                            <div>
                                <label class="form-label">Pilih Proyek Skripsi / Proposal</label>
                                <select id="para-proj-select" class="form-select">
                                    <option value="">-- Pilih proyek --</option>
                                    @foreach($history as $item)
                                        <option value="{{ $item->id }}">{{ \Illuminate\Support\Str::limit($item->judul, 85) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Pilih Bab yang Ingin Dimuat</label>
                                <div style="display: flex; gap: 8px;">
                                    <select id="para-proj-bab" class="form-select" style="flex: 1;">
                                        <option value="bab1">Bab I — Pendahuluan</option>
                                        <option value="bab2">Bab II — Tinjauan Pustaka</option>
                                        <option value="bab3">Bab III — Metodologi</option>
                                        <option value="bab4">Bab IV — Hasil &amp; Pembahasan</option>
                                        <option value="bab5">Bab V — Penutup</option>
                                    </select>
                                    <button type="button" id="btn-load-para-proj" class="btn-outline" style="white-space: nowrap;" onclick="importProjectBabToParaphrase()">Muat Teks</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Opsi Gaya, Intensitas, & Istilah Terkunci (2 baris sejajar & 100% responsif) -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 12px; margin-bottom: 12px; align-items: end;">
                        <div>
                            <label class="form-label" for="para-style">Mode Parafrase</label>
                            <select id="para-style" class="form-select" style="height: 40px;">
                                <option value="natural" selected>Anti-Turnitin (Rombak Struktur Kalimat)</option>
                                <option value="academic">Ilmiah Baku (Standar EYD V &amp; KBBI)</option>
                                <option value="passive">Kalimat Pasif Ilmiah (Bab III &amp; IV)</option>
                                <option value="humanize">Humanizer Natural (Bebas Gaya Kaku AI)</option>
                                <option value="concise">Ringkas &amp; Padat</option>
                                <option value="expand">Elaborasi Ilmiah Mendalam</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Intensitas Perubahan</label>
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; height: 40px;">
                                <button type="button" class="btn-outline para-int-btn" data-intensity="ringan" onclick="setParaIntensity('ringan', this)" style="height: 40px; padding: 0 8px; font-size: 12.5px; justify-content: center;">Ringan</button>
                                <button type="button" class="btn-outline para-int-btn active" data-intensity="sedang" onclick="setParaIntensity('sedang', this)" style="height: 40px; padding: 0 8px; font-size: 12.5px; justify-content: center; background: #eff6ff; color: #1d4ed8; border-color: #93c5fd; font-weight: 700;">Sedang</button>
                                <button type="button" class="btn-outline para-int-btn" data-intensity="maksimal" onclick="setParaIntensity('maksimal', this)" style="height: 40px; padding: 0 8px; font-size: 12.5px; justify-content: center;">Maksimal</button>
                            </div>
                            <input type="hidden" id="para-intensity" value="sedang">
                        </div>
                    </div>

                    <div style="margin-bottom: 14px;">
                        <label class="form-label" for="para-preserve-terms">Istilah / Variabel Terkunci <span class="opt">(opsional · pisahkan dengan koma)</span></label>
                        <input type="text" id="para-preserve-terms" class="form-input" style="height: 40px;" placeholder="cth: Naive Bayes, TF-IDF, turnover intention">
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px; gap: 8px;">
                            <label class="form-label" style="margin-bottom: 0;">Naskah / Paragraf Asli <span class="req">*</span></label>
                            <span id="para-input-counter" style="font-size: 11.5px; color: var(--text-muted); font-weight: 500;">0 kata · 0 karakter</span>
                        </div>
                        <textarea id="para-input" class="form-textarea" style="min-height: 155px; line-height: 1.65;"
                            oninput="updateParaCounter()"
                            placeholder="Tempelkan paragraf yang ingin diparafrase di sini (maks 15.000 karakter)..."></textarea>
                    </div>

                    <div class="card-footer-bar">
                        <span class="footer-meta">SITASI IN-TEXT, ANGKA &amp; RUMUS OTOMATIS TERKUNCI</span>
                        <button type="button" id="btn-paraphrase" class="btn-primary"
                            onclick="handleParaphrase()">Parafrase Sekarang →</button>
                    </div>
                </div>

                <div id="para-loading-box" style="display: none; margin-top: 16px;"></div>

                <!-- Hasil Parafrase Lengkap + Metrik & Diff -->
                <div id="para-result-card" class="card" style="display: none; margin-top: 18px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; padding-bottom: 12px; border-bottom: 1px solid var(--border-light); margin-bottom: 14px;">
                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            <span class="form-label" style="margin-bottom: 0; font-size: 14px;">Hasil Parafrase</span>
                            <span id="para-metric-uniqueness" style="font-size: 11.5px; font-weight: 700; padding: 3px 10px; border-radius: 999px; background: #dcfce7; color: #166534;">Keunikan -%</span>
                            <span id="para-metric-sim" style="font-size: 11.5px; font-weight: 600; padding: 3px 10px; border-radius: 999px; background: #eff6ff; color: #1d4ed8;">Kemiripan tersisa -%</span>
                            <span id="para-metric-words" style="font-size: 11.5px; font-weight: 500; padding: 3px 9px; border-radius: 999px; background: #f1f5f9; color: #475569;">0 → 0 kata</span>
                            <span id="para-metric-cites" style="display: none; font-size: 11.5px; font-weight: 600; padding: 3px 9px; border-radius: 999px; background: #f0fdf4; color: #15803d;">0 sitasi terjaga</span>
                        </div>
                        <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                            <button type="button" class="btn-outline" id="para-view-clean-btn" style="padding: 5px 11px; font-size: 12px; background: #eff6ff; color: #1d4ed8; border-color: #93c5fd; font-weight: 600;" onclick="switchParaResultView('clean')">Hasil Bersih</button>
                            <button type="button" class="btn-outline" id="para-view-diff-btn" style="padding: 5px 11px; font-size: 12px;" onclick="switchParaResultView('diff')">Bandingkan Perubahan</button>
                        </div>
                    </div>

                    <!-- View 1: Editable clean result -->
                    <div id="para-view-clean">
                        <textarea id="para-output" class="form-textarea" style="min-height: 170px; font-size: 14px; line-height: 1.75; color: var(--text-main); background: #fff;"></textarea>
                    </div>

                    <!-- View 2: Side-by-side Diff Highlight -->
                    <div id="para-view-diff" style="display: none;">
                        <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 10px;">
                            <span style="display: inline-block; padding: 1px 6px; background: #dcfce7; color: #166534; border-radius: 4px; font-weight: 600;">Hijau</span> = frasa/kata baru hasil parafrase &nbsp;·&nbsp;
                            <span style="display: inline-block; padding: 1px 6px; background: #fee2e2; color: #991b1b; border-radius: 4px; text-decoration: line-through;">Merah</span> = kata asli yang diganti
                        </div>
                        <div class="responsive-grid-2" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 12px;">
                            <div style="padding: 12px 14px; border: 1px solid var(--border); border-radius: 10px; background: #fafafa;">
                                <div style="font-size: 11.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px;">Teks Asli</div>
                                <div id="para-diff-orig" style="font-size: 13.5px; line-height: 1.75; color: #334155;"></div>
                            </div>
                            <div style="padding: 12px 14px; border: 1px solid #bfdbfe; border-radius: 10px; background: #f8fbff;">
                                <div style="font-size: 11.5px; font-weight: 700; color: #1d4ed8; text-transform: uppercase; margin-bottom: 6px;">Hasil Parafrase Baru</div>
                                <div id="para-diff-new" style="font-size: 13.5px; line-height: 1.75; color: #0f172a;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer-bar" style="margin-top: 14px;">
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <button type="button" class="btn-outline" style="padding: 6px 12px; font-size: 12.5px;" onclick="copyParaphraseResult(this)">Salin Teks</button>
                            <button type="button" class="btn-outline" style="padding: 6px 12px; font-size: 12.5px;" onclick="downloadParaphraseTxt()">Unduh .txt</button>
                            <button type="button" class="btn-outline" style="padding: 6px 12px; font-size: 12.5px;" onclick="reParaphraseOutput()">Parafrase Ulang ↻</button>
                        </div>
                        <button type="button" class="btn-primary" style="padding: 7px 14px; font-size: 12.5px;" onclick="sendParaphraseToPlagiarism()">Cek Kemiripan Turnitin →</button>
                    </div>
                </div>
            </section>

            <!-- ========================================================= -->
            <!-- 7B. TAB CEK PLAGIASI (SIMILARITY CHECKER ALA TURNITIN) -->
            <!-- ========================================================= -->
            <section class="tab-pane" id="pane-plagiarism">
                <div class="page-eyebrow">Pre-Check Kemiripan · Ala Turnitin</div>
                <h1 class="page-title">Cek Plagiasi &amp; Kemiripan Naskah</h1>
                <p class="page-subtitle">Periksa kemiripan kalimat secara nyata terhadap database jurnal ilmiah terbuka
                    (OpenAlex, Crossref), Wikipedia, web, maupun dokumen rujukan — lengkap highlight warna, 1-klik parafrase,
                    dan laporan PDF.</p>

                <div class="card">
                    <div class="plag-mode-tabs" role="tablist">
                        <button type="button" class="plag-mode-btn active" id="plag-tab-text" onclick="setPlagInputMode('text')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            <span>Tempel Teks</span>
                        </button>
                        <button type="button" class="plag-mode-btn" id="plag-tab-file" onclick="setPlagInputMode('file')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            <span>Upload File</span>
                        </button>
                        <button type="button" class="plag-mode-btn" id="plag-tab-project" onclick="setPlagInputMode('project')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                            <span>Proyek Saya</span>
                        </button>
                    </div>

                    <!-- Mode 1: Tempel Teks -->
                    <div id="plag-box-text">
                        <div class="form-group" style="margin-bottom: 10px;">
                            <label class="form-label">Judul Dokumen (opsional, untuk header laporan PDF)</label>
                            <input type="text" id="plag-doc-title" class="form-input" placeholder="Contoh: Bab I Pendahuluan — Analisis Sentimen TikTok">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Naskah yang Ingin Diperiksa <span class="req">*</span></label>
                            <textarea id="plag-text-input" class="form-textarea" style="min-height: 165px;"
                                placeholder="Tempelkan paragraf atau bab skripsi yang ingin dicek kemiripannya di sini (minimal 15 kata)..."></textarea>
                        </div>
                    </div>

                    <!-- Mode 2: Upload File .docx / .pdf / .txt -->
                    <div id="plag-box-file" style="display: none;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Pilih File Skripsi / Proposal (.docx, .pdf, atau .txt) <span class="req">*</span></label>
                            <input type="file" id="plag-file-input" class="form-input" accept=".docx,.pdf,.txt" onchange="onPlagFileSelected(this)">
                            <div id="plag-file-info" style="font-size: 12px; color: var(--text-muted); margin-top: 6px;">
                                Mendukung file Word (<code>.docx</code>), PDF (<code>.pdf</code>), dan Teks (<code>.txt</code>) hingga 8 MB.
                            </div>
                        </div>
                    </div>

                    <!-- Mode 3: Pilih Proyek Tersimpan -->
                    <div id="plag-box-project" style="display: none;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Pilih Proyek Skripsi / Proposal Tersimpan <span class="req">*</span></label>
                            <select id="plag-project-select" class="form-select">
                                <option value="">-- Pilih proyek dari Workspace --</option>
                                @foreach($history as $item)
                                    <option value="{{ $item->id }}">{{ \Illuminate\Support\Str::limit($item->judul, 95) }}</option>
                                @endforeach
                            </select>
                            <div style="font-size: 12px; color: var(--text-muted); margin-top: 6px;">
                                Sistem otomatis membaca isi Bab I–V dari proyek pilihanmu untuk diperiksa kemiripannya.
                            </div>
                        </div>
                    </div>

                    <!-- Filter Turnitin -->
                    <div class="plag-filters-row">
                        <label>
                            <input type="checkbox" id="plag-ex-bib" checked>
                            <span>Kecualikan Daftar Pustaka</span>
                        </label>
                        <label>
                            <input type="checkbox" id="plag-ex-quotes" checked>
                            <span>Kecualikan Kutipan (&ldquo;...&rdquo;)</span>
                        </label>
                        <label>
                            <input type="checkbox" id="plag-ex-small" checked>
                            <span>Abaikan Kecocokan &lt; 6 Kata</span>
                        </label>
                    </div>

                    <!-- Opsional: Bandingkan 2 Dokumen -->
                    <details class="plag-compare-toggle">
                        <summary>
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" style="flex-shrink:0;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            <span>Opsional: Bandingkan dengan Dokumen / Jurnal Referensi Lokal (1-vs-1)</span>
                        </summary>
                        <div style="margin-top: 10px;">
                            <input type="file" id="plag-compare-file" class="form-input" accept=".docx,.pdf,.txt" style="margin-bottom: 8px;">
                            <textarea id="plag-compare-text" class="form-textarea" style="min-height: 85px;"
                                placeholder="Atau tempelkan teks artikel/jurnal sumber di sini untuk membandingkan langsung 100% kalimatmu dengan sumber tersebut..."></textarea>
                        </div>
                    </details>

                    <div id="plag-error-banner" class="plag-error-banner"></div>

                    <div class="card-footer-bar">
                        <span class="footer-meta">SHINGLING &amp; SPAN MATCHING · OPENALEX + CROSSREF + WIKIPEDIA + WEB</span>
                        <button type="button" id="btn-plag-check" class="btn-primary plag-btn-icon" onclick="handlePlagiarismCheck()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>
                            <span>Cek Plagiasi Sekarang</span>
                        </button>
                    </div>
                </div>

                <div id="plag-loading-box" style="display: none; margin-top: 16px;"></div>

                <!-- HASIL LAPORAN SIMILARITY ALA TURNITIN -->
                <div id="plag-result-wrap" style="display: none;">
                    <div class="plag-report-grid">
                        <!-- Kolom Kiri: Naskah Berwarna + 1-Klik Parafrase Inline -->
                        <div>
                            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; margin-bottom: 10px;">
                                <div>
                                    <div style="font-size: 14px; font-weight: 700; color: var(--text-main);">Naskah dengan Penandaan Kemiripan</div>
                                    <div style="font-size: 12px; color: var(--text-muted);">Klik kalimat berwarna mana saja untuk langsung <strong>Parafrase 1-Klik</strong> menurunkan skor.</div>
                                </div>
                                <button type="button" class="btn-outline plag-btn-icon" style="padding: 6px 12px; font-size: 12px;" onclick="copyUpdatedPlagDoc(this)">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                    <span>Salin Naskah Terbaru</span>
                                </button>
                            </div>

                            <div id="plag-doc-viewer" class="plag-doc-viewer"></div>

                            <!-- Kotak Parafrase Inline saat pengguna klik kalimat yang ditandai -->
                            <div id="plag-inline-editor" class="plag-inline-editor" style="display: none;">
                                <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px; margin-bottom: 8px; flex-wrap: wrap;">
                                    <div style="font-size: 12.5px; font-weight: 700; color: #1e40af;" id="plag-inline-meta">Kalimat Terdeteksi Mirip</div>
                                    <button type="button" class="btn-outline" style="padding: 3px 9px; font-size: 11.5px;" onclick="document.getElementById('plag-inline-editor').style.display='none';">Tutup</button>
                                </div>
                                <div id="plag-inline-orig" style="font-size: 13px; color: #334155; background: #ffffff; padding: 10px 12px; border-radius: 8px; border: 1px solid #cbd5e1; margin-bottom: 10px; line-height: 1.6;"></div>
                                <div id="plag-inline-source-link" style="font-size: 12px; color: #475569; margin-bottom: 10px;"></div>
                                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                    <button type="button" class="btn-primary plag-btn-icon" id="btn-plag-inline-para" style="padding: 7px 14px; font-size: 12.5px;" onclick="paraphraseFlaggedSentence()">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                                        <span>Parafrase Otomatis Kalimat Ini</span>
                                    </button>
                                </div>
                                <div id="plag-inline-result-box" style="display: none; margin-top: 10px;">
                                    <label class="form-label" style="font-size: 12px; color: #047857;">Hasil Parafrase (Bisa diedit sebelum diterapkan):</label>
                                    <textarea id="plag-inline-new-text" class="form-textarea" style="min-height: 75px; font-size: 13px;"></textarea>
                                    <div style="margin-top: 8px; display: flex; gap: 8px;">
                                        <button type="button" class="btn-primary plag-btn-icon" style="background: #059669; padding: 6px 14px; font-size: 12.5px;" onclick="applyParaphrasedSentence()">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="20 6 9 17 4 12"/></svg>
                                            <span>Terapkan &amp; Perbarui Skor</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Kolom Kanan: Similarity Index & Daftar Sumber Cocok -->
                        <div class="plag-score-card">
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap; margin-bottom: 12px;">
                                <span style="font-size: 12px; font-weight: 700; letter-spacing: 0.04em; color: var(--text-muted); text-transform: uppercase;">Similarity Index</span>
                                <span id="plag-status-pill" style="font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 999px;">-</span>
                            </div>

                            <div style="display: flex; align-items: baseline; flex-wrap: wrap; gap: 10px; margin-bottom: 14px;">
                                <div id="plag-big-pct" class="plag-big-pct">0%</div>
                                <div style="font-size: 12px; color: var(--text-muted);" id="plag-word-summary">0 kata diperiksa</div>
                            </div>

                            <!-- Breakdown Sumber -->
                            <div class="plag-breakdown-grid">
                                <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 8px; padding: 8px 6px; text-align: center;">
                                    <div id="plag-acad-pct" style="font-size: 15px; font-weight: 700; color: #1d4ed8;">0%</div>
                                    <div style="font-size: 10.5px; color: var(--text-muted);">Jurnal Ilmiah</div>
                                </div>
                                <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 8px; padding: 8px 6px; text-align: center;">
                                    <div id="plag-web-pct" style="font-size: 15px; font-weight: 700; color: #7c3aed;">0%</div>
                                    <div style="font-size: 10.5px; color: var(--text-muted);">Internet / Web</div>
                                </div>
                                <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 8px; padding: 8px 6px; text-align: center;">
                                    <div id="plag-local-pct" style="font-size: 15px; font-weight: 700; color: #0d9488;">0%</div>
                                    <div style="font-size: 10.5px; color: var(--text-muted);">Pembanding</div>
                                </div>
                            </div>

                            <div style="font-size: 12px; color: #475569; background: #f8fafc; border: 1px solid var(--border); border-radius: 8px; padding: 8px 10px; margin-bottom: 14px; display: flex; justify-content: space-between;">
                                <span>Keragaman Kosakata (TTR)</span>
                                <strong id="plag-ttr-val">0%</strong>
                            </div>

                            <button type="button" class="btn-primary btn-full plag-btn-icon" id="btn-plag-pdf" onclick="downloadPlagiarismPdf()" style="margin-bottom: 16px;">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                <span>Unduh Laporan PDF (Ala Turnitin)</span>
                            </button>

                            <div style="font-size: 12px; font-weight: 700; color: var(--text-main); margin-bottom: 6px; display: flex; justify-content: space-between;">
                                <span>Sumber Cocok (Match Overview)</span>
                                <span id="plag-src-count" style="color: var(--text-muted); font-weight: 600;">0 sumber</span>
                            </div>
                            <div id="plag-sources-list" style="max-height: 300px; overflow-y: auto;"></div>

                            <div style="margin-top: 12px; padding-top: 10px; border-top: 1px solid var(--border-light); font-size: 11px; color: var(--text-muted); line-height: 1.5; display: flex; gap: 6px; align-items: flex-start;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" style="flex-shrink:0; margin-top:2px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                                <span><strong>Pre-Check Similarity:</strong> Menggunakan pencocokan frasa Shingling terhadap database jurnal terbuka (OpenAlex, Crossref), Wikipedia &amp; web untuk membantu menurunkan kemiripan sebelum submit Turnitin resmi kampus.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ========================================================= -->
            <!-- 7C. TAB OLAH DATA STATISTIK VALID (SETARA SPSS — SEMUA JURUSAN) -->
            <!-- ========================================================= -->
            <section class="tab-pane" id="pane-olahdata">
                <div class="page-eyebrow">Statistik Valid S1 &amp; S2 · Semua Jurusan</div>
                <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; margin-bottom:6px;">
                    <h1 class="page-title" style="margin:0;">Olah Data Statistik (Setara SPSS)</h1>
                    <span class="od-badge-valid">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><polyline points="20 6 9 17 4 12"/></svg>
                        <span>Komputasi Deterministik · Presisi SPSS</span>
                    </span>
                </div>
                <p class="page-subtitle">Solusi olah data mandiri untuk mahasiswa yang sudah punya proposal/data maupun yang belum memiliki kuesioner atau lembar uji lab. Mendukung Ekonomi, Pendidikan, Kesehatan, Sosial, IT, hingga Teknik Sipil &amp; Eksperimen Laboratorium.</p>

                <!-- Sub-Tab Switcher: Sudah Punya Data vs Belum Punya Kuesioner/Form -->
                <div class="od-subtabs" role="tablist">
                    <button type="button" class="od-subtab-btn active" id="od-tab-btn-calc" onclick="odSwitchSubTab('calc')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10"/><path d="M12 20V4"/><path d="M6 20v-6"/></svg>
                        <span>Sudah Punya Data (Hitung Statistik Valid)</span>
                    </button>
                    <button type="button" class="od-subtab-btn" id="od-tab-btn-inst" onclick="odSwitchSubTab('inst')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        <span>Belum Punya Kuesioner / Form Lab (Buat + Template Excel)</span>
                    </button>
                </div>

                <!-- SUB-TAB 1: KALKULATOR OLAH DATA VALID -->
                <div id="od-subpane-calc">
                    <div class="card" style="margin-bottom: 16px;">
                        <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; margin-bottom:12px;">
                            <div>
                                <div style="font-size:14px; font-weight:700; color:var(--text-main);">1. Masukkan Data Penelitian (Excel, CSV, atau Tempel Tabel)</div>
                                <div style="font-size:12px; color:var(--text-muted); margin-top:2px;">Baris pertama otomatis dibaca sebagai nama kolom variabel (mis. <code>X1_1</code>, <code>Kuat_Tekan_MPa</code>, <code>Pretest</code>).</div>
                            </div>
                            <button type="button" class="btn-outline" id="od-btn-clear-data" onclick="odClearDataset()" style="display:none; padding:6px 11px; font-size:12px;">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                                <span>Reset Data</span>
                            </button>
                        </div>

                        <div class="od-upload-grid">
                            <!-- Dropzone Excel / CSV -->
                            <div>
                                <label class="od-dropzone" id="od-dropzone" for="od-file-input">
                                    <div style="width:40px; height:40px; border-radius:10px; background:#dbeafe; color:#1d4ed8; display:flex; align-items:center; justify-content:center;">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                    </div>
                                    <div style="font-size:13px; font-weight:700; color:var(--text-main);">Unggah File Excel (.xlsx / .xls) atau CSV</div>
                                    <div style="font-size:11.5px; color:var(--text-muted);">Klik pilih berkas atau seret ke sini (maks 5.000 baris, diproses instan)</div>
                                    <input type="file" id="od-file-input" accept=".xlsx,.xls,.csv,.tsv,.txt" style="display:none;" onchange="odHandleFileUpload(this)">
                                </label>

                                <div style="margin-top:10px;">
                                    <div style="font-size:11.5px; font-weight:700; color:var(--text-secondary); margin-bottom:4px;">Atau Coba Dataset Contoh Lintas Jurusan (1-Klik):</div>
                                    <div class="od-sample-row">
                                        <button type="button" class="od-sample-btn" onclick="odLoadSample('likert')">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2"/><line x1="7" y1="9" x2="17" y2="9"/><line x1="7" y1="13" x2="13" y2="13"/></svg>
                                            <div>
                                                <div class="od-sample-title">Kuesioner Likert (N=30)</div>
                                                <div class="od-sample-sub">Manajemen/Pendidikan · Validitas &amp; Regresi</div>
                                            </div>
                                        </button>
                                        <button type="button" class="od-sample-btn" onclick="odLoadSample('beton')">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
                                            <div>
                                                <div class="od-sample-title">Kuat Tekan Beton (N=20)</div>
                                                <div class="od-sample-sub">Teknik Sipil / Uji Lab · One-Way ANOVA</div>
                                            </div>
                                        </button>
                                        <button type="button" class="od-sample-btn" onclick="odLoadSample('prepost')">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                                            <div>
                                                <div class="od-sample-title">Pre-Test vs Post-Test</div>
                                                <div class="od-sample-sub">Pendidikan/Kesehatan · Paired t-Test</div>
                                            </div>
                                        </button>
                                        <button type="button" class="od-sample-btn" onclick="odLoadSample('eksperimen')">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 3h6"/><path d="M10 3v6l-5 8a2 2 0 0 0 1.7 3h10.6a2 2 0 0 0 1.7-3l-5-8V3"/></svg>
                                            <div>
                                                <div class="od-sample-title">Kontrol vs Eksperimen</div>
                                                <div class="od-sample-sub">Kesehatan/Sains · Independent t-Test</div>
                                            </div>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Tempel langsung dari Excel / Google Sheets -->
                            <div style="display:flex; flex-direction:column;">
                                <label class="form-label" for="od-paste-area" style="margin-bottom:6px;">Atau Tempel (Copy-Paste) Langsung dari Excel / Spreadsheet</label>
                                <textarea id="od-paste-area" class="form-textarea" rows="6" style="flex:1; min-height:142px; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size:12px;"
                                    placeholder="Contoh format CSV / Tab Excel:&#10;Campuran,Kuat_Tekan_MPa&#10;Normal_0%,25.4&#10;FlyAsh_10%,28.9&#10;FlyAsh_20%,31.8"></textarea>
                                <div style="display:flex; justify-content:flex-end; margin-top:8px;">
                                    <button type="button" class="btn-outline" onclick="odLoadFromPaste()" style="padding:7px 13px; font-size:12.5px;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                        <span>Muat Tabel dari Teks</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Preview Dataset -->
                        <div id="od-data-preview-wrap" style="display:none; margin-top:12px; padding-top:12px; border-top:1px solid var(--border-light);">
                            <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; margin-bottom:8px;">
                                <div style="font-size:12.5px; font-weight:700; color:var(--text-main);" id="od-data-summary-label">Dataset dimuat</div>
                                <div style="font-size:11.5px; color:var(--text-muted);" id="od-data-col-types"></div>
                            </div>
                            <div class="od-table-wrap">
                                <table class="od-spss-table" id="od-preview-table"></table>
                            </div>
                        </div>
                    </div>

                    <!-- Langkah 2: Pilih Uji & Variabel -->
                    <div class="card" style="margin-bottom: 16px;">
                        <div style="font-size:14px; font-weight:700; color:var(--text-main); margin-bottom:4px;">2. Pilih Jenis Uji Statistik &amp; Variabel</div>
                        <div style="font-size:12px; color:var(--text-muted); margin-bottom:12px;">Tersedia 9 uji parametrik &amp; non-parametrik lengkap yang umum diminta dosen pembimbing di S1 &amp; S2.</div>

                        <div class="od-test-grid" id="od-test-selector">
                            <button type="button" class="od-test-card active" data-test="regression" onclick="odSelectTest('regression')">
                                <div class="od-test-name">Regresi Linier &amp; Asumsi Klasik</div>
                                <div class="od-test-desc">R², Uji F, Uji t, VIF/Tolerance, Normalitas K-S, Glejser, Durbin-Watson.</div>
                            </button>
                            <button type="button" class="od-test-card" data-test="validity_reliability" onclick="odSelectTest('validity_reliability')">
                                <div class="od-test-name">Validitas &amp; Reliabilitas</div>
                                <div class="od-test-desc">Pearson r hitung vs r tabel, Sig., Cronbach's Alpha &amp; Alpha if Deleted.</div>
                            </button>
                            <button type="button" class="od-test-card" data-test="anova" onclick="odSelectTest('anova')">
                                <div class="od-test-name">One-Way ANOVA + Post-Hoc</div>
                                <div class="od-test-desc">Bandingkan ≥3 variasi perlakuan (Teknik Sipil, Lab, Eksperimen) + Levene &amp; Bonferroni.</div>
                            </button>
                            <button type="button" class="od-test-card" data-test="ttest_ind" onclick="odSelectTest('ttest_ind')">
                                <div class="od-test-name">Independent Sample t-Test</div>
                                <div class="od-test-desc">Uji beda 2 kelompok bebas (Kontrol vs Eksperimen) + Levene, Welch &amp; Cohen's d.</div>
                            </button>
                            <button type="button" class="od-test-card" data-test="ttest_paired" onclick="odSelectTest('ttest_paired')">
                                <div class="od-test-name">Paired Sample t-Test</div>
                                <div class="od-test-desc">Uji beda berpasangan Pre-Test vs Post-Test / Sebelum vs Sesudah perlakuan.</div>
                            </button>
                            <button type="button" class="od-test-card" data-test="correlation" onclick="odSelectTest('correlation')">
                                <div class="od-test-name">Korelasi Pearson &amp; Spearman</div>
                                <div class="od-test-desc">Matriks hubungan antar variabel parametrik (r) &amp; non-parametrik (ρ).</div>
                            </button>
                            <button type="button" class="od-test-card" data-test="descriptive" onclick="odSelectTest('descriptive')">
                                <div class="od-test-name">Deskriptif &amp; Normalitas</div>
                                <div class="od-test-desc">N, Min, Max, Mean, Median, SD, Varians, Skewness, Kurtosis &amp; K-S.</div>
                            </button>
                            <button type="button" class="od-test-card" data-test="chisquare" onclick="odSelectTest('chisquare')">
                                <div class="od-test-name">Chi-Square (χ²) &amp; Crosstab</div>
                                <div class="od-test-desc">Uji hubungan 2 variabel kategorik (Kesehatan/Sosial) + Cramer's V.</div>
                            </button>
                            <button type="button" class="od-test-card" data-test="nonparam" onclick="odSelectTest('nonparam')">
                                <div class="od-test-name">Non-Parametrik (MW / Wilcoxon / Kruskal)</div>
                                <div class="od-test-desc">Alternatif uji beda data tidak normal: Mann-Whitney U, Wilcoxon, &amp; Kruskal-Wallis H (≥3 kelompok).</div>
                            </button>
                        </div>

                        <!-- Dynamic Variable Picker -->
                        <div class="od-var-box" id="od-var-picker">
                            <div style="font-size:12.5px; color:var(--text-muted);">Unggah file Excel/CSV atau pilih dataset contoh di atas terlebih dahulu agar kolom variabel muncul otomatis di sini.</div>
                        </div>

                        <!-- Konteks & Alpha -->
                        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:12px; margin-bottom:14px;">
                            <div>
                                <label class="form-label" for="od-topic-input">Judul / Konteks Penelitian (Opsional)</label>
                                <input type="text" id="od-topic-input" class="form-input" placeholder="cth: Pengaruh Variasi Fly Ash terhadap Kuat Tekan Beton">
                            </div>
                            <div>
                                <label class="form-label" for="od-major-input">Jurusan / Bidang Ilmu</label>
                                <input type="text" id="od-major-input" class="form-input" placeholder="cth: Teknik Sipil / Manajemen / Pendidikan / Kesehatan">
                            </div>
                            <div>
                                <label class="form-label" for="od-alpha-select">Taraf Signifikansi (α)</label>
                                <select id="od-alpha-select" class="form-select">
                                    <option value="0.05" selected>α = 0.05 (Kepercayaan 95% · Standar Skripsi)</option>
                                    <option value="0.01">α = 0.01 (Kepercayaan 99% · Ketat / Medis)</option>
                                    <option value="0.10">α = 0.10 (Kepercayaan 90% · Eksploratif)</option>
                                </select>
                            </div>
                        </div>

                        <div id="od-error-box" style="display:none; padding:10px 14px; border-radius:10px; background:#fef2f2; border:1px solid #fecaca; color:#b91c1c; font-size:12.5px; font-weight:600; margin-bottom:12px;"></div>
                        <div id="od-loading-box" style="display:none; margin-bottom:12px;"></div>

                        <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
                            <div style="font-size:11.5px; color:var(--text-muted);">Semua angka dihitung langsung dengan rumus matematis murni (bukan tebakan AI).</div>
                            <button type="button" class="btn-primary" id="od-btn-run" onclick="odRunAnalysis()">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                <span>Hitung Statistik Valid (Setara SPSS)</span>
                            </button>
                        </div>
                    </div>

                    <!-- Hasil Analisis Statistik -->
                    <div id="od-result-section" style="display:none;">
                        <div class="card" style="margin-bottom:16px;">
                            <div style="display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:14px; padding-bottom:12px; border-bottom:1px solid var(--border-light);">
                                <div>
                                    <div style="font-size:16px; font-weight:800; color:var(--text-main);" id="od-res-title">Hasil Analisis Statistik</div>
                                    <div style="font-size:12.5px; color:var(--text-secondary); margin-top:2px;" id="od-res-subtitle"></div>
                                </div>
                                <div style="display:flex; flex-wrap:wrap; gap:8px;">
                                    <button type="button" class="btn-outline" onclick="odCopyTables(this)" style="padding:7px 11px; font-size:12px;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                        <span>Salin Tabel</span>
                                    </button>
                                    <button type="button" class="btn-primary" id="od-btn-docx" onclick="odDownloadDocx()" style="padding:7px 13px; font-size:12px; background:#059669; border-color:#059669;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M12 18v-6"/><path d="M9 15l3 3 3-3"/></svg>
                                        <span>Unduh Word (.docx)</span>
                                    </button>
                                    <button type="button" class="btn-primary" id="od-btn-pdf" onclick="odDownloadPdf()" style="padding:7px 13px; font-size:12px;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                        <span>Unduh PDF</span>
                                    </button>
                                </div>
                            </div>

                            <div class="od-hl-grid" id="od-res-highlights"></div>
                            <div id="od-res-advisory" style="display:none; padding:11px 14px; border-radius:10px; font-size:12.5px; line-height:1.55; margin-bottom:14px;"></div>
                            <div id="od-res-equation" style="display:none; padding:11px 14px; border-radius:10px; background:#eff6ff; border:1px solid #bfdbfe; color:#1e3a8a; font-size:13px; font-weight:700; margin-bottom:14px;"></div>
                            <div id="od-res-academic-guide" style="display:none; background:#f8fafc; border:1px solid var(--border); border-radius:12px; padding:12px 15px; margin-bottom:14px; font-size:12.3px; color:var(--text-secondary); line-height:1.55;"></div>
                            <div id="od-res-tables"></div>

                            <!-- Grafik Statistik SVG (Dukung Multi-Grafik Asli SPSS untuk Regresi) -->
                            <div class="od-chart-box" id="od-res-chart-wrap" style="display:none;">
                                <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; margin-bottom:10px;">
                                    <div style="font-size:13px; font-weight:700; color:var(--text-main);" id="od-chart-title">Visualisasi Grafik</div>
                                    <button type="button" class="btn-outline" onclick="odDownloadChartPng()" style="padding:5px 10px; font-size:11.5px;">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                        <span>Unduh Grafik PNG HD</span>
                                    </button>
                                </div>
                                <div id="od-chart-tabs" class="od-chart-tabs" style="display:none;"></div>
                                <div id="od-chart-svg-host" style="min-width:300px;"></div>
                            </div>

                            <!-- Keputusan Statistik -->
                            <div style="background:#f8fafc; border:1px solid var(--border); border-radius:12px; padding:14px 16px; margin-bottom:14px;">
                                <div style="font-size:13px; font-weight:700; color:var(--text-main); margin-bottom:6px;">Ringkasan Keputusan Statistik</div>
                                <ul id="od-res-conclusions" style="margin:0 0 0 18px; padding:0; font-size:12.8px; color:var(--text-secondary); line-height:1.65;"></ul>
                            </div>

                            <!-- Narasi Interpretasi Bab IV (AI Berbasis Angka Eksak) -->
                            <div style="border-top:1px solid var(--border-light); padding-top:14px;">
                                <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; margin-bottom:10px;">
                                    <div>
                                        <div style="font-size:13.5px; font-weight:700; color:var(--text-main);">Narasi Interpretasi Bab IV Siap Tempel Word</div>
                                        <div style="font-size:12px; color:var(--text-muted);">AI menerjemahkan tabel hasil hitung eksak di atas menjadi paragraf akademis baku tanpa mengubah angka satu digit pun.</div>
                                    </div>
                                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                        <button type="button" class="btn-primary" id="od-btn-narrate" onclick="odGenerateNarration()" style="padding:8px 14px; font-size:12.5px;">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                                            <span>Buat Narasi Bab IV (AI)</span>
                                        </button>
                                        <button type="button" class="btn-outline" id="od-btn-copy-narr" onclick="odCopyNarration(this)" style="display:none; padding:8px 12px; font-size:12.5px;">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                            <span>Salin Narasi</span>
                                        </button>
                                    </div>
                                </div>
                                <div id="od-narr-loading" style="display:none; margin-bottom:10px;"></div>
                                <div id="od-narr-output" class="od-narration-box" style="display:none;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SUB-TAB 2: BELUM PUNYA KUESIONER / LEMBAR PENCATATAN EKSPERIMEN LAB -->
                <div id="od-subpane-inst" style="display:none;">
                    <div class="card" style="margin-bottom:16px;">
                        <div style="font-size:14.5px; font-weight:700; color:var(--text-main); margin-bottom:4px;">Generator Instrumen Pengumpulan Data + Template Tabulasi Excel</div>
                        <div style="font-size:12.5px; color:var(--text-muted); margin-bottom:14px;">Belum punya kuesioner atau format tabel uji laboratorium? Susun otomatis kisi-kisi Bab III, daftar butir/parameter ukur, lalu unduh file Excel (.xlsx / .csv) kosong yang siap diisi dan diunggah langsung ke tab Hitung Statistik!</div>

                        <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:14px;">
                            <button type="button" class="od-test-card active" id="od-inst-mode-kuesioner" onclick="odSetInstrumentMode('kuesioner')" style="flex:1 1 240px;">
                                <div class="od-test-name">Kuesioner Survei (Skala Likert)</div>
                                <div class="od-test-desc">Cocok untuk Manajemen, Ekonomi, Pendidikan, Psikologi, Sosial, Komunikasi, Kesehatan Masyarakat.</div>
                            </button>
                            <button type="button" class="od-test-card" id="od-inst-mode-eksperimen" onclick="odSetInstrumentMode('eksperimen')" style="flex:1 1 240px;">
                                <div class="od-test-name">Lembar Data Eksperimen / Uji Lab</div>
                                <div class="od-test-desc">Cocok untuk Teknik Sipil (Beton/Aspal), Teknik Mesin, Pertanian, Biologi, Farmasi, Keperawatan &amp; IT.</div>
                            </button>
                        </div>

                        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap:12px; margin-bottom:12px;">
                            <div>
                                <label class="form-label" for="od-inst-topic">Judul / Topik Penelitian <span style="color:#dc2626;">*</span></label>
                                <input type="text" id="od-inst-topic" class="form-input" placeholder="cth: Pengaruh Motivasi & Disiplin Kerja terhadap Kinerja Karyawan">
                            </div>
                            <div>
                                <label class="form-label" for="od-inst-major">Jurusan / Program Studi</label>
                                <input type="text" id="od-inst-major" class="form-input" placeholder="cth: Manajemen / Teknik Sipil / Pendidikan / Kesehatan">
                            </div>
                        </div>

                        <div class="od-inst-grid">
                            <div>
                                <label class="form-label" for="od-inst-vars" id="od-inst-vars-label">Daftar Variabel Penelitian (pisahkan dengan koma) <span style="color:#dc2626;">*</span></label>
                                <input type="text" id="od-inst-vars" class="form-input" placeholder="cth: Motivasi Kerja (X1), Disiplin Kerja (X2), Kinerja Karyawan (Y)">
                            </div>
                            <div>
                                <label class="form-label" for="od-inst-count" id="od-inst-count-label">Item per Variabel</label>
                                <input type="number" id="od-inst-count" class="form-input" min="2" max="12" value="5">
                            </div>
                            <div>
                                <label class="form-label" for="od-inst-scale" id="od-inst-scale-label">Skala / Satuan</label>
                                <select id="od-inst-scale" class="form-select">
                                    <option value="Likert 1-5 (STS - SS)">Skala Likert 1–5 (STS – SS)</option>
                                    <option value="Likert 1-4 (STS - SS)">Skala Likert 1–4 (Tanpa Netral)</option>
                                    <option value="Guttman (Ya = 1, Tidak = 0)">Skala Guttman (Ya / Tidak)</option>
                                    <option value="Pengukuran Numerik / Satuan SI">Pengukuran Numerik / Lab (MPa, mm, g, s)</option>
                                </select>
                            </div>
                        </div>

                        <div id="od-inst-error" style="display:none; padding:10px 14px; border-radius:10px; background:#fef2f2; border:1px solid #fecaca; color:#b91c1c; font-size:12.5px; font-weight:600; margin-bottom:12px;"></div>
                        <div id="od-inst-loading" style="display:none; margin-bottom:12px;"></div>

                        <div style="display:flex; justify-content:flex-end;">
                            <button type="button" class="btn-primary" id="od-btn-gen-inst" onclick="odGenerateInstrument()">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                                <span>Buat Instrumen &amp; Template Excel</span>
                            </button>
                        </div>
                    </div>

                    <div id="od-inst-result" style="display:none;" class="card">
                        <div style="display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:10px; margin-bottom:14px; padding-bottom:12px; border-bottom:1px solid var(--border-light);">
                            <div>
                                <div style="font-size:15.5px; font-weight:800; color:var(--text-main);" id="od-inst-res-title">Instrumen Penelitian</div>
                                <div style="font-size:12px; color:var(--text-secondary); margin-top:2px;" id="od-inst-res-meta"></div>
                            </div>
                            <div style="display:flex; flex-wrap:wrap; gap:8px;">
                                <button type="button" class="btn-outline" onclick="odCopyInstrument(this)" style="padding:7px 12px; font-size:12px;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                    <span>Salin Kisi-Kisi &amp; Butir</span>
                                </button>
                                <button type="button" class="btn-outline" onclick="odDownloadTemplateCsv()" style="padding:7px 12px; font-size:12px;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                    <span>Unduh Template .CSV</span>
                                </button>
                                <button type="button" class="btn-primary" onclick="odDownloadTemplateXlsx()" style="padding:7px 13px; font-size:12px;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                    <span>Unduh Template Excel (.xlsx)</span>
                                </button>
                            </div>
                        </div>
                        <div id="od-inst-res-body"></div>
                    </div>
                </div>
            </section>

            <!-- ========================================================= -->
            <!-- 8. TAB DOSEN AI (INTERACTIVE CHAT & BIMBINGAN 24 JAM) -->
            <!-- ========================================================= -->
            <section class="tab-pane" id="pane-qa">
                <div class="page-eyebrow">Bimbingan &amp; Konsultasi 24 Jam</div>
                <h1 class="page-title">Dosen AI</h1>
                <p class="page-subtitle">Konsultasikan segala hal seputar skripsi dan proposal: cara menghadapi dosen
                    pembimbing, perumusan fenomena &amp; gap riset, revisi bab naskah, olah data statistik/kualitatif,
                    tips lolos Turnitin, hingga strategi menjawab ujian sidang.</p>

                <div class="card dosen-ai-card">
                    <!-- Header Chat Status -->
                    <div class="qa-header-bar">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div
                                style="width: 10px; height: 10px; border-radius: 50%; background: #16a34a; box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.2); flex-shrink: 0;">
                            </div>
                            <span style="font-size: 13.5px; font-weight: 700; color: var(--text-main);">Dosen Pembimbing
                                AI (Aktif 24 Jam)</span>
                        </div>
                        <button type="button" class="qa-copy-btn"
                            onclick="resetQaChat()">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" />
                                <path d="M3 3v5h5" />
                            </svg>
                            <span>Mulai Obrolan Baru</span>
                        </button>
                    </div>

                    <!-- Scrollable Messages Container -->
                    <div id="qa-chat-messages">
                        <!-- Initial Assistant Greeting -->
                        <div class="chat-msg assistant"
                            style="display: flex; gap: 12px; align-items: flex-start; max-width: 92%;">
                            <div class="qa-avatar"
                                style="width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg, #2563eb, #1d4ed8); display: flex; align-items: center; justify-content: center; color: #fff; flex-shrink: 0; box-shadow: 0 2px 6px rgba(37,99,235,0.25);">
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="1.8">
                                    <path d="M22 10v6M2 10l10-5 10 5-10 5z" />
                                    <path d="M6 12v5c0 2 3 3 6 3s6-1 6-3v-5" />
                                </svg>
                            </div>
                            <div class="qa-bubble"
                                style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 13px 16px; border-radius: 4px 14px 14px 14px; font-size: 13.5px; line-height: 1.65; color: var(--text-main);">
                                Halo! Saya adalah Dosen AI, asisten pembimbing skripsi dan proposal Anda. Anda bisa
                                menanyakan apa saja: mulai dari cara menemukan gap penelitian, menyusun latar belakang
                                masalah, olah data statistik (SPSS/PLS), tips menghadapi revisi dosen, cara menurunkan
                                similarity Turnitin, hingga latihan tanya jawab sidang skripsi. Ada kendala yang sedang
                                Anda hadapi saat ini?
                            </div>
                        </div>
                    </div>

                    <!-- Suggestion Chips -->
                    <div class="qa-chips-bar" aria-label="Contoh pertanyaan cepat">
                        <button type="button" class="qa-chip-pill"
                            onclick="sendQuickPrompt('Bagaimana cara sopan menghubungi dan menghadapi dosen pembimbing yang slow response atau sering menolak draft?')">Tips
                            Hadapi Dosen Pembimbing</button>
                        <button type="button" class="qa-chip-pill"
                            onclick="sendQuickPrompt('Bagaimana langkah praktis menemukan fenomena masalah nyata dan research gap untuk judul proposal skripsi?')">Cari
                            Fenomena &amp; Research Gap</button>
                        <button type="button" class="qa-chip-pill"
                            onclick="sendQuickPrompt('Bagaimana cara efektif menurunkan skor similarity Turnitin yang tinggi tanpa mengubah substansi ilmiah?')">Tips
                            Lolos Turnitin</button>
                        <button type="button" class="qa-chip-pill"
                            onclick="sendQuickPrompt('Bagaimana cara membaca output regresi linier, uji F, dan uji t pada SPSS beserta artinya?')">Interpretasi
                            Regresi SPSS</button>
                        <button type="button" class="qa-chip-pill"
                            onclick="sendQuickPrompt('Pertanyaan apa saja yang paling sering diajukan penguji saat ujian sidang skripsi dan bagaimana cara menjawabnya?')">Strategi
                            Sidang Skripsi</button>
                        <button type="button" class="qa-chip-pill"
                            onclick="sendQuickPrompt('Apa perbedaan mendasar antara penelitian Kuantitatif, Kualitatif, dan R&D?')">Kuantitatif
                            vs Kualitatif vs R&amp;D</button>
                    </div>

                    <!-- Attached Image Preview Bar -->
                    <div id="qa-image-preview-bar"
                        style="display: none; flex-shrink: 0; padding: 8px 16px; background: #f8fafc; border-top: 1px solid var(--border); align-items: center; gap: 10px;">
                        <img id="qa-image-preview-img" src="" alt="preview"
                            style="height: 38px; width: 38px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border);">
                        <span id="qa-image-name"
                            style="font-size: 12px; color: var(--text-muted); flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">-</span>
                        <button type="button" onclick="removeQaImage()"
                            style="border: none; background: none; font-size: 16px; color: #ef4444; cursor: pointer;">&times;</button>
                    </div>

                    <!-- Input Bar -->
                    <div class="qa-input-bar">
                        <label title="Lampirkan foto/gambar soal, grafik, atau tabel"
                            style="width: 42px; height: 42px; border-radius: 10px; border: 1px solid #cbd5e1; background: #ffffff; cursor: pointer; color: var(--text-muted); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path
                                    d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48" />
                            </svg>
                            <input type="file" id="qa-image" accept="image/*" style="display: none;"
                                onchange="handleQaImageSelect(this)">
                        </label>
                        <textarea id="qa-question" class="form-textarea" rows="1"
                            style="min-height: 42px; max-height: 120px; padding: 10px 13px; font-size: 13.5px; resize: none; flex: 1; border-radius: 10px;"
                            placeholder="Tuliskan pertanyaan atau kendala skripsimu... (Shift+Enter untuk baris baru)"
                            onkeydown="if(event.key === 'Enter' && !event.shiftKey && !event.isComposing) { event.preventDefault(); handleSolveQa(); }"></textarea>
                        <button type="button" id="btn-solve-qa" class="btn-primary"
                            style="min-height: 42px; padding: 9px 16px; border-radius: 10px; display: inline-flex; align-items: center; gap: 6px; flex-shrink: 0;"
                            onclick="handleSolveQa()">
                            <span>Kirim</span>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.2">
                                <line x1="22" y1="2" x2="11" y2="13" />
                                <polygon points="22 2 15 22 11 13 2 9 22 2" />
                            </svg>
                        </button>
                    </div>
                </div>
            </section>

            <!-- ========================================================= -->
            <!-- 10. TAB AKUN SAYA & STATUS LANGGANAN -->
            <!-- ========================================================= -->
            <section class="tab-pane" id="pane-account">
                <div class="page-eyebrow">Profil Mahasiswa &amp; Langganan</div>
                <h1 class="page-title">Akun Saya</h1>
                <p class="page-subtitle">Informasi profil mahasiswa, status akses Workspace AI, dan paket aktif.</p>

                <div class="card" style="margin-bottom: 18px;">
                    <div
                        style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap; margin-bottom: 22px; padding-bottom: 18px; border-bottom: 1px solid var(--border-light);">
                        <div
                            style="width: 56px; height: 56px; border-radius: 14px; background: var(--primary); color: #fff; font-size: 22px; font-weight: 700; display: flex; align-items: center; justify-content: center;">
                            {{ strtoupper(substr($user->name ?? 'M', 0, 1)) }}
                        </div>
                        <div style="flex: 1; min-width: 180px;">
                            <div style="font-size: 18px; font-weight: 700; color: var(--text-main);">{{ $user->name }}
                            </div>
                            <div style="font-size: 13.5px; color: var(--text-muted); margin-top: 2px;">
                                {{ $user->email }}
                            </div>
                        </div>
                        @php
                            $accPaid = !empty($billingSummary['is_paid_active']);
                            $accUnlimited = !empty($billingSummary['is_unlimited']);
                            $accPlanName = ($billingSummary['is_admin'] ?? false)
                                ? 'Administrator (Unlimited)'
                                : ($billingSummary['plan']['name'] ?? 'Free (Coba Dulu)');
                        @endphp
                        <span
                            style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 999px; background: {{ $accPaid ? '#f0fdf4' : '#f1f5f9' }}; color: {{ $accPaid ? '#16a34a' : '#475569' }}; border: 1px solid {{ $accPaid ? '#bbf7d0' : '#cbd5e1' }}; font-size: 12.5px; font-weight: 700;">
                            <span style="width: 7px; height: 7px; border-radius: 50%; background: {{ $accPaid ? '#16a34a' : '#64748b' }};"></span>
                            {{ $accPlanName }}
                        </span>
                    </div>

                    <!-- Form Edit Nama Profil Mahasiswa -->
                    <form action="{{ route('profile.updateName') }}" method="POST"
                        style="margin-bottom: 22px; padding: 18px; background: #f8fafc; border: 1px solid var(--border); border-radius: 12px;">
                        @csrf
                        <div style="font-size: 13.5px; font-weight: 700; color: var(--text-main); margin-bottom: 12px;">
                            Ubah Nama Profil Mahasiswa</div>
                        <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end;">
                            <div style="flex: 1; min-width: 220px;">
                                <label class="form-label" style="font-size: 12px; margin-bottom: 5px;">Nama Lengkap
                                    (untuk Halaman Sampul &amp; PPT)</label>
                                <input type="text" name="name" class="form-input" value="{{ $user->name }}" required
                                    placeholder="Contoh: Muhammad Alwi Syahrir">
                            </div>
                            <button type="submit" class="btn-primary" style="height: 40px; padding: 0 18px;">
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>

                    <div class="form-row" style="margin-bottom: 0;">
                        <div
                            style="padding: 16px; border: 1px solid #bfdbfe; border-radius: 12px; background: #eff6ff;">
                            <div
                                style="font-size: 12px; font-weight: 600; color: var(--primary); text-transform: uppercase; letter-spacing: 0.04em;">
                                Status Tier &amp; Kuota Naskah</div>
                            <div style="font-size: 20px; font-weight: 700; color: var(--text-main); margin-top: 6px;">
                                {{ $accPlanName }}</div>
                            <div style="font-size: 12.5px; color: var(--text-secondary); margin-top: 6px; line-height: 1.55;">
                                @if($accUnlimited)
                                    Akses <strong>Unlimited</strong> semua Proposal, Skripsi (Bab I–V), Download Word/PDF, &amp; seluruh AI Tools. Berlaku hingga: <strong>{{ $billingSummary['expires_at_label'] ?? '-' }}</strong>.
                                @elseif($accPaid)
                                    Sisa kuota: <strong>{{ $billingSummary['remaining_quota'] ?? 0 }} Proposal/Skripsi Full</strong> (Terpakai {{ $billingSummary['quota_used'] ?? 0 }}/{{ $billingSummary['quota_limit'] ?? 0 }}). Berlaku hingga: <strong>{{ $billingSummary['expires_at_label'] ?? '-' }}</strong>.
                                @else
                                    Jatah Free: <strong>1 proyek uji coba</strong> (Sampul, Bab I &amp; Daftar Pustaka), <strong>Dosen AI gratis</strong>, &amp; <strong>1x coba gratis</strong> fitur AI Tools lainnya.
                                @endif
                            </div>
                            <button type="button" class="btn-primary" style="margin-top: 12px; padding: 8px 14px; font-size: 12.5px;" onclick="openTab('pricing', 'Paket')">
                                {{ $accPaid ? 'Tambah Kuota / Perpanjang Paket →' : 'Upgrade Paket Mulai Rp15.000 →' }}
                            </button>
                        </div>

                        <div
                            style="padding: 16px; border: 1px solid var(--border); border-radius: 12px; background: #f8fafc;">
                            <div
                                style="font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em;">
                                Total Proyek Workspace</div>
                            <div style="font-size: 24px; font-weight: 700; color: var(--text-main); margin-top: 4px;">
                                {{ $history->count() }} <span
                                    style="font-size: 14px; font-weight: 500; color: var(--text-muted);">Proyek</span>
                            </div>
                            <div style="font-size: 12px; color: var(--text-muted); margin-top: 6px;">Proposal (Bab 1–3)
                                &amp; Full Skripsi (Bab 1–5) tersimpan aman.</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ========================================================= -->
            <!-- 10B. TAB PAKET & TIER AKUN (HARGA PAKET + PAKASIR QRIS) -->
            <!-- ========================================================= -->
            <section class="tab-pane" id="pane-pricing">
                @php
                    $bs = $billingSummary ?? [];
                @endphp
                <div class="page-eyebrow">Tanpa Sistem Kredit · Bayar Pakai Kuota atau Unlimited</div>
                <h1 class="page-title">Paket</h1>
                <p class="page-subtitle">Cek sisa kuota &amp; masa aktif akun Anda, atau pilih paket mulai Rp15.000 via QRIS.</p>

                <!-- Kartu Status Tier & Sisa Kuota/Fitur -->
                @include('partials.billing_status_card', ['bs' => $bs, 'inWorkspace' => true])

                <!-- Katalog Paket Lengkap (15rb, 29rb, 50rb/bln, 2bln, 3bln, 6bln, 1thn) -->
                <div style="margin-bottom: 20px;">
                    @include('partials.pricing_catalog', ['plans' => $plans ?? [], 'billingSummary' => $billingSummary ?? []])
                </div>

                @if(!empty($bs['recent_transactions']) && count($bs['recent_transactions']) > 0)
                    <div class="card">
                        <div style="font-size: 14px; font-weight: 700; color: var(--text-main); margin-bottom: 10px;">Riwayat Transaksi Paket Saya</div>
                        <div style="overflow-x: auto;">
                            <table style="width:100%; border-collapse:collapse; font-size:12.5px;">
                                <thead>
                                    <tr style="border-bottom:1px solid #e2e8f0; color:#64748b; text-align:left;">
                                        <th style="padding:8px 6px;">Order ID</th>
                                        <th style="padding:8px 6px;">Paket</th>
                                        <th style="padding:8px 6px;">Nominal</th>
                                        <th style="padding:8px 6px;">Status</th>
                                        <th style="padding:8px 6px;">Waktu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($bs['recent_transactions'] as $tx)
                                        <tr style="border-bottom:1px solid #f1f5f9;">
                                            <td style="padding:8px 6px; font-family:monospace;">{{ $tx->order_id }}</td>
                                            <td style="padding:8px 6px; font-weight:600;">{{ $tx->plan_name }}</td>
                                            <td style="padding:8px 6px;">Rp {{ number_format($tx->amount, 0, ',', '.') }}</td>
                                            <td style="padding:8px 6px;">
                                                <span style="padding:2px 8px; border-radius:999px; font-size:11px; font-weight:700; background:{{ $tx->status === 'completed' ? '#dcfce7' : '#fef3c7' }}; color:{{ $tx->status === 'completed' ? '#15803d' : '#b45309' }};">
                                                    {{ strtoupper($tx->status) }}
                                                </span>
                                            </td>
                                            <td style="padding:8px 6px; color:#64748b;">{{ \Carbon\Carbon::parse($tx->created_at)->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </section>

            <!-- ========================================================= -->
            <!-- 11. TAB AJAK TEMAN (REFERRAL) -->
            <!-- ========================================================= -->
            <section class="tab-pane" id="pane-referral">
                @php
                    $refStats = $billingSummary['referral_stats'] ?? [];
                    $refCode = $billingSummary['referral_code'] ?? ('SKRIPSI-' . strtoupper(substr(md5(($user->id ?? '1') . ($user->email ?? 'user')), 0, 6)));
                    $refUrl = $billingSummary['referral_url'] ?? url('/register?ref=' . $refCode);
                    $refBalance = (int) ($refStats['balance'] ?? 0);
                    $invitedUsers = $refStats['invited_users'] ?? collect();
                    $refHistory = $refStats['history'] ?? collect();
                    $refWithdrawals = $refStats['withdrawals'] ?? collect();
                @endphp
                <div class="page-eyebrow">Program Komunitas Mahasiswa · Aktif Otomatis</div>
                <h1 class="page-title">Ajak Teman (Referral 10%)</h1>
                <p class="page-subtitle">Setiap teman yang mendaftar lewat link Anda lalu membeli paket berbayar, Anda langsung mendapat komisi 10% yang bisa dicairkan atau ditukar menjadi paket.
                </p>

                <!-- Statistik Live Referral -->
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(175px, 1fr)); gap:12px; margin-bottom:18px;">
                    <div class="card" style="padding:14px 16px;">
                        <div style="font-size:11.5px; font-weight:600; color:#64748b;">Teman Mendaftar</div>
                        <div style="font-size:22px; font-weight:800; color:#0f172a; margin-top:4px;">{{ number_format((int) ($refStats['invited_count'] ?? 0)) }}</div>
                    </div>
                    <div class="card" style="padding:14px 16px;">
                        <div style="font-size:11.5px; font-weight:600; color:#64748b;">Transaksi Berhasil</div>
                        <div style="font-size:22px; font-weight:800; color:#2563eb; margin-top:4px;">{{ number_format((int) ($refStats['converted_count'] ?? 0)) }}x</div>
                    </div>
                    <div class="card" style="padding:14px 16px;">
                        <div style="font-size:11.5px; font-weight:600; color:#64748b;">Total Komisi Terkumpul</div>
                        <div style="font-size:22px; font-weight:800; color:#0f172a; margin-top:4px;">Rp {{ number_format((int) ($refStats['total_earned'] ?? 0), 0, ',', '.') }}</div>
                    </div>
                    <div class="card" style="padding:14px 16px; border-color:#bbf7d0; background:#f0fdf4;">
                        <div style="font-size:11.5px; font-weight:700; color:#15803d;">Saldo Komisi Tersedia</div>
                        <div id="ref-balance-text" style="font-size:22px; font-weight:800; color:#16a34a; margin-top:4px;">Rp {{ number_format($refBalance, 0, ',', '.') }}</div>
                    </div>
                </div>

                <div class="card"
                    style="margin-bottom: 18px; border-color: #bfdbfe; background: linear-gradient(135deg, #ffffff 0%, #eff6ff 100%);">
                    <div
                        style="display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                        <div style="max-width: 540px;">
                            <div
                                style="display: inline-block; padding: 4px 10px; border-radius: 999px; background: #dbeafe; color: #1d4ed8; font-size: 11.5px; font-weight: 700; margin-bottom: 10px;">
                                KOMISI AFILIASI MAHASISWA 10%</div>
                            <h3 style="font-size: 17px; font-weight: 700; color: var(--text-main); margin-bottom: 6px;">
                                Dapatkan Komisi 10% dari Setiap Pembelian Paket Teman</h3>
                            <p style="font-size: 13.5px; color: var(--text-secondary); line-height: 1.6;">Ajak teman
                                seangkatan atau tim bimbinganmu. Begitu teman membeli paket via QRIS Pakasir (Rp15rb–Rp399rb), <strong>komisi 10%</strong> otomatis masuk ke saldo Anda.</p>
                        </div>
                        <div
                            style="background: #ffffff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 14px 20px; text-align: center; min-width: 170px;">
                            <div
                                style="font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">
                                Kode Referral Kamu</div>
                            <div id="referral-code-text"
                                style="font-size: 19px; font-weight: 800; color: var(--primary); letter-spacing: 0.05em; margin-top: 4px;">
                                {{ $refCode }}
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 18px;">
                        <label class="form-label">Link Undangan Pribadi</label>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <input type="text" id="referral-link-input" class="form-input" value="{{ $refUrl }}"
                                readonly style="flex: 1; min-width: 220px; background: #fff;">
                            <button type="button" class="btn-primary" onclick="copyReferralLink()">Salin Link</button>
                            <button type="button" class="btn-outline" onclick="shareReferralWhatsapp()"
                                style="display: inline-flex; align-items: center; gap: 6px;">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <path
                                        d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z" />
                                </svg>
                                <span>Bagikan ke WhatsApp</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Pencairan atau Tukar Saldo Komisi -->
                <div class="form-row" style="margin-bottom: 18px;">
                    <div class="card">
                        <div style="font-size: 14px; font-weight: 700; color: var(--text-main); margin-bottom: 8px;">Tukar Saldo Jadi Paket Otomatis</div>
                        <p style="font-size: 12.5px; color: var(--text-secondary); margin-bottom: 12px;">Gunakan saldo komisi langsung tanpa menunggu pencairan bank.</p>
                        <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                            <select id="ref-redeem-plan" class="form-select" style="flex:1; min-width:180px;">
                                <option value="starter">Paket Starter 1 Naskah (Rp15.000)</option>
                                <option value="hemat">Paket Hemat 3 Naskah (Rp29.000)</option>
                                <option value="unlimited_1m">Unlimited 1 Bulan (Rp50.000)</option>
                            </select>
                            <button type="button" class="btn-primary" onclick="submitReferralRedeem(this)" {{ $refBalance < 15000 ? 'disabled' : '' }} style="opacity:{{ $refBalance < 15000 ? '0.55' : '1' }};">
                                Tukar Paket →
                            </button>
                        </div>
                        <div style="font-size:11.5px; color:#64748b; margin-top:8px;">Minimal saldo Rp15.000 untuk tukar ke Paket Starter.</div>
                    </div>

                    <div class="card">
                        <div style="font-size: 14px; font-weight: 700; color: var(--text-main); margin-bottom: 8px;">Ajukan Pencairan Dana / Rekening</div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:8px;">
                            <select id="ref-wd-channel" class="form-select">
                                <option value="DANA">DANA</option>
                                <option value="GoPay">GoPay</option>
                                <option value="OVO">OVO</option>
                                <option value="ShopeePay">ShopeePay</option>
                                <option value="Transfer Bank (BCA/BRI/Mandiri)">Transfer Bank</option>
                            </select>
                            <input type="number" id="ref-wd-amount" class="form-input" placeholder="Nominal (min 10000)" min="10000" value="{{ max(10000, $refBalance) }}">
                        </div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:8px;">
                            <input type="text" id="ref-wd-number" class="form-input" placeholder="Nomor HP / Rekening">
                            <input type="text" id="ref-wd-name" class="form-input" placeholder="Atas Nama Pemilik">
                        </div>
                        <button type="button" class="btn-outline" onclick="submitReferralWithdraw(this)" {{ $refBalance < 10000 ? 'disabled' : '' }} style="width:100%; opacity:{{ $refBalance < 10000 ? '0.55' : '1' }};">
                            Ajukan Pencairan Komisi (Min. Rp10.000)
                        </button>
                    </div>
                </div>

                @if(count($invitedUsers) > 0 || count($refHistory) > 0 || count($refWithdrawals) > 0)
                    <div class="card" style="margin-bottom: 18px;">
                        <div style="font-size: 14px; font-weight: 700; color: var(--text-main); margin-bottom: 10px;">Aktivitas Teman &amp; Riwayat Komisi</div>
                        @if(count($invitedUsers) > 0)
                            <div style="overflow-x:auto; margin-bottom:14px;">
                                <table style="width:100%; border-collapse:collapse; font-size:12.5px;">
                                    <thead>
                                        <tr style="border-bottom:1px solid #e2e8f0; color:#64748b; text-align:left;">
                                            <th style="padding:7px 6px;">Teman Diundang</th>
                                            <th style="padding:7px 6px;">Status Paket</th>
                                            <th style="padding:7px 6px;">Tanggal Daftar</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($invitedUsers as $iu)
                                            @php
                                                $parts = explode('@', (string) $iu->email, 2);
                                                $masked = substr($parts[0], 0, 2) . '***@' . ($parts[1] ?? '');
                                            @endphp
                                            <tr style="border-bottom:1px solid #f1f5f9;">
                                                <td style="padding:7px 6px;"><strong>{{ $iu->name }}</strong> <span style="color:#64748b;">({{ $masked }})</span></td>
                                                <td style="padding:7px 6px;">{{ strtoupper($iu->plan_code ?? 'free') }}</td>
                                                <td style="padding:7px 6px; color:#64748b;">{{ $iu->created_at ? $iu->created_at->timezone('Asia/Jakarta')->format('d M Y') : '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                        @if(count($refWithdrawals) > 0)
                            <div style="font-size:12.5px; font-weight:700; color:#334155; margin-bottom:6px;">Riwayat Penarikan Komisi</div>
                            <div style="display:flex; flex-direction:column; gap:6px;">
                                @foreach($refWithdrawals as $wd)
                                    <div style="font-size:12px; padding:8px 10px; border-radius:8px; background:#f8fafc; border:1px solid #e2e8f0; display:flex; justify-content:space-between;">
                                        <span>{{ $wd->channel }} · {{ $wd->account_number }} ({{ $wd->account_name }})</span>
                                        <span><strong>Rp {{ number_format($wd->amount, 0, ',', '.') }}</strong> · {{ strtoupper($wd->status) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                <div class="card">
                    <div style="font-size: 14px; font-weight: 700; color: var(--text-main); margin-bottom: 12px;">Cara
                        Kerja Komisi Referral SkripsiLab AI</div>
                    <div class="form-row responsive-grid-3"
                        style="grid-template-columns: repeat(3, 1fr); margin-bottom: 0;">
                        <div style="padding: 14px; border: 1px solid var(--border); border-radius: 10px;">
                            <div style="font-size: 12px; font-weight: 700; color: var(--primary); margin-bottom: 4px;">
                                1. Salin &amp; Bagikan</div>
                            <div style="font-size: 12.5px; color: var(--text-secondary); line-height: 1.55;">Kirim link
                                undanganmu ke teman kuliah atau grup angkatan.</div>
                        </div>
                        <div style="padding: 14px; border: 1px solid var(--border); border-radius: 10px;">
                            <div style="font-size: 12px; font-weight: 700; color: var(--primary); margin-bottom: 4px;">
                                2. Teman Bergabung &amp; Beli Paket</div>
                            <div style="font-size: 12.5px; color: var(--text-secondary); line-height: 1.55;">Teman
                                mendaftar lewat link Anda lalu membeli paket Starter, Hemat, atau Unlimited.</div>
                        </div>
                        <div
                            style="padding: 14px; border: 1px solid #bbf7d0; border-radius: 10px; background: #f0fdf4;">
                            <div style="font-size: 12px; font-weight: 700; color: #16a34a; margin-bottom: 4px;">3.
                                Komisi 10% Langsung Masuk</div>
                            <div style="font-size: 12.5px; color: #15803d; line-height: 1.55;">Saldo otomatis masuk dan bisa ditukar jadi paket atau dicairkan ke Dana/GoPay/Rekening.</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ========================================================= -->
            <!-- 12. TAB SARAN & LAPORAN KE WHATSAPP PENGEMBANG -->
            <!-- ========================================================= -->
            <section class="tab-pane" id="pane-feedback">
                <div class="page-eyebrow">Dukungan Pengguna Langsung · Pengembang SkripsiLab AI</div>
                <h1 class="page-title">Saran &amp; Laporan Kendala</h1>
                <p class="page-subtitle">Sampaikan saran fitur baru, kritik format penulisan kampusmu, atau laporkan
                    kendala teknis langsung ke WhatsApp developer (Muh Alwi Syahrir).</p>

                <div class="card" style="max-width: 720px; margin-bottom: 24px;">
                    <div
                        style="display: flex; align-items: center; gap: 14px; padding: 14px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; margin-bottom: 20px;">
                        <div
                            style="width: 44px; height: 44px; border-radius: 50%; background: #22c55e; display: flex; align-items: center; justify-content: center; color: #ffffff; flex-shrink: 0;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path
                                    d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z" />
                            </svg>
                        </div>
                        <div>
                            <div style="font-size: 14px; font-weight: 700; color: #166534;">WhatsApp Developer: Muh Alwi
                                Syahrir</div>
                            <div style="font-size: 13px; color: #15803d; margin-top: 2px;">Nomor WA:
                                <strong>082195036971</strong> &bull; Respon cepat untuk bantuan mahasiswa.
                            </div>
                        </div>
                    </div>

                    <form id="form-feedback-wa" onsubmit="event.preventDefault(); sendFeedbackToWhatsapp();">
                        <div class="form-row">
                            <div>
                                <label class="form-label">Kategori Pesan <span class="req">*</span></label>
                                <select id="fb-kategori" class="form-select" required>
                                    <option value="Saran Fitur Baru">Saran Fitur Baru</option>
                                    <option value="Laporan Kendala / Bug">Laporan Kendala / Bug</option>
                                    <option value="Permintaan Format Kampus Khusus">Permintaan Format Kampus Khusus
                                    </option>
                                    <option value="Pertanyaan Bimbingan Skripsi">Pertanyaan Bimbingan Skripsi</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Nama Mahasiswa <span class="req">*</span></label>
                                <input type="text" id="fb-nama" class="form-input" value="{{ $user->name ?? '' }}"
                                    placeholder="Nama Lengkapmu" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div>
                                <label class="form-label">Nama Kampus / Universitas</label>
                                <input type="text" id="fb-kampus" class="form-input"
                                    value="{{ $defaultIdentity['kampus'] ?? '' }}"
                                    placeholder="Contoh: Universitas Hasanuddin / UI">
                            </div>
                            <div>
                                <label class="form-label">Program Studi</label>
                                <input type="text" id="fb-prodi" class="form-input"
                                    value="{{ $defaultIdentity['prodi'] ?? '' }}"
                                    placeholder="Contoh: Teknik Informatika / Manajemen">
                            </div>
                        </div>

                        <div style="margin-bottom: 16px;">
                            <label class="form-label">Judul Skripsi / Topik (Opsional)</label>
                            <input type="text" id="fb-judul" class="form-input"
                                placeholder="Tulis judul skripsi yang sedang kamu susun">
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label class="form-label">Rincian Saran atau Laporan Kendala <span
                                    class="req">*</span></label>
                            <textarea id="fb-pesan" class="form-textarea" rows="4"
                                placeholder="Ceritakan secara detail saran fitur yang kamu butuhkan atau kendala yang kamu temui saat menyusun naskah..."
                                required></textarea>
                        </div>

                        <button type="submit"
                            style="display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%; padding: 12px 20px; border: none; border-radius: 10px; background: #16a34a; color: #ffffff; font-size: 14px; font-weight: 700; cursor: pointer; box-shadow: 0 2px 8px rgba(22,163,74,0.3); transition: all 0.2s;"
                            onmouseover="this.style.background='#15803d';"
                            onmouseout="this.style.background='#16a34a';">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.2">
                                <path
                                    d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z" />
                            </svg>
                            <span>Kirim ke WhatsApp Pengembang</span>
                        </button>
                    </form>
                </div>
            </section>

        </main>
    </div>

    <!-- Floating Action Button: Dosen AI Konsultasi 24 Jam (Hanya Muncul di Dashboard) -->
    <div id="dosen-ai-fab" onclick="openDosenAiFromFab()" title="Tanya Dosen AI (Bimbingan 24 Jam)"
        style="display: none !important; position: fixed; bottom: 24px; right: 24px; z-index: 999; align-items: center; gap: 10px; background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color: #ffffff; padding: 10px 18px 10px 14px; border-radius: 999px; box-shadow: 0 10px 25px -5px rgba(37,99,235,0.45), 0 8px 10px -6px rgba(37,99,235,0.3); cursor: pointer; transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1); user-select: none;">
        <div
            style="width: 30px; height: 30px; border-radius: 50%; background: rgba(255,255,255,0.22); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 10v6M2 10l10-5 10 5-10 5z" />
                <path d="M6 12v5c0 2 3 3 6 3s6-1 6-3v-5" />
            </svg>
        </div>
        <div style="display: flex; flex-direction: column; text-align: left;">
            <span style="font-size: 13px; font-weight: 700; line-height: 1.2;">Tanya Dosen AI</span>
            <span style="font-size: 10.5px; opacity: 0.9; line-height: 1.1;">Aktif 24 Jam ✦</span>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function escapeHtml(str) {
            if (str === null || str === undefined) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function getSelectedModel() {
            const el = document.getElementById('global-model');
            return el ? (el.value || 'auto') : 'auto';
        }

        function renderLoadingCard(boxId, titleText, subText) {
            const box = document.getElementById(boxId);
            if (!box) return;
            box.innerHTML = `
                <div class="ai-loading-card">
                    <div class="ai-loading-head">
                        <span class="ai-spinner-blue"></span>
                        <div>
                            <div class="ai-loading-title">${titleText}</div>
                            <div class="ai-loading-sub">${subText}</div>
                        </div>
                    </div>
                    <div class="ai-shimmer-line" style="width: 92%;"></div>
                    <div class="ai-shimmer-line" style="width: 78%;"></div>
                    <div class="ai-shimmer-line" style="width: 60%; margin-bottom: 0;"></div>
                </div>
            `;
            box.style.display = 'block';
        }

        function hideLoadingCard(boxId) {
            const box = document.getElementById(boxId);
            if (box) {
                box.style.display = 'none';
                box.innerHTML = '';
            }
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');
            if (window.innerWidth <= 900) {
                const isOpen = sidebar.classList.toggle('open');
                if (backdrop) backdrop.classList.toggle('show', isOpen);
            } else {
                sidebar.classList.toggle('collapsed');
                document.body.classList.toggle('sidebar-collapsed', sidebar.classList.contains('collapsed'));
            }
        }

        function closeSidebarMobile() {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');
            if (sidebar) sidebar.classList.remove('open');
            if (backdrop) backdrop.classList.remove('show');
        }

        function showProjectListView() {
            const listView = document.getElementById('projects-list-view');
            const createView = document.getElementById('projects-create-view');
            if (listView) listView.style.display = 'block';
            if (createView) createView.style.display = 'none';
        }

        function openCreateProjectForm() {
            document.querySelectorAll('.tab-pane').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.nav-btn').forEach(el => el.classList.remove('active'));

            const pane = document.getElementById('pane-projects');
            if (pane) pane.classList.add('active');

            const navBtn = document.getElementById('nav-projects');
            if (navBtn) navBtn.classList.add('active');

            const bc = document.getElementById('breadcrumb-label');
            if (bc) bc.innerText = 'Proyek Saya';

            const listView = document.getElementById('projects-list-view');
            const createView = document.getElementById('projects-create-view');
            if (listView) listView.style.display = 'none';
            if (createView) createView.style.display = 'block';

            closeSidebarMobile();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function showProjectCreateForm() {
            openCreateProjectForm();
        }

        function openTab(tabId, label) {
            document.querySelectorAll('.tab-pane').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.nav-btn').forEach(el => el.classList.remove('active'));

            const pane = document.getElementById('pane-' + tabId);
            if (pane) pane.classList.add('active');

            const navBtn = document.getElementById('nav-' + tabId);
            if (navBtn) navBtn.classList.add('active');

            if (tabId === 'projects') {
                showProjectListView();
            }

            if (label) {
                const bc = document.getElementById('breadcrumb-label');
                if (bc) bc.innerText = label;
            }

            // Sync URL hash & localStorage agar saat refresh halaman tetap di tab yang sedang dibuka
            try {
                if (window.history && window.history.replaceState) {
                    const cleanUrl = new URL(window.location.href);
                    cleanUrl.searchParams.delete('tab');
                    cleanUrl.searchParams.delete('create');
                    cleanUrl.hash = tabId;
                    window.history.replaceState(null, '', cleanUrl.toString());
                }
                localStorage.setItem('skripsiai_active_tab', tabId);
            } catch (e) { }

            closeSidebarMobile();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Buka tab sesuai URL hash / query param / localStorage (Refresh tetap konsisten)
        (function initTabFromUrl() {
            const map = {
                'dashboard': 'Dashboard',
                'projects': 'Proyek Saya',
                'skripsi': 'Proyek Saya',
                'proposal': 'Proyek Saya',
                'titles': 'Cari Judul',
                'ppt': 'Generate PPT Sidang',
                'defense': 'Simulasi Sidang',
                'journals': 'Cari Jurnal',
                'paraphrase': 'Parafrase',
                'olahdata': 'Olah Data',
                'qa': 'Dosen AI',
                'pricing': 'Paket',
                'account': 'Akun Saya',
                'referral': 'Ajak Teman',
                'feedback': 'Saran & Laporan'
            };

            const params = new URLSearchParams(window.location.search);
            const tabParam = params.get('tab');
            const hashTab = (window.location.hash || '').replace('#', '').trim();
            const storedTab = localStorage.getItem('skripsiai_active_tab');

            let targetTab = null;
            if (tabParam && map[tabParam]) {
                targetTab = (tabParam === 'skripsi' || tabParam === 'proposal') ? 'projects' : tabParam;
            } else if (hashTab && map[hashTab]) {
                targetTab = hashTab;
            } else if (storedTab && map[storedTab]) {
                targetTab = storedTab;
            }

            if (targetTab && map[targetTab]) {
                openTab(targetTab, map[targetTab]);
                if (params.get('create') === '1') {
                    openCreateProjectForm();
                }
            } else {
                openTab('dashboard', 'Dashboard');
            }
        })();

        function openDosenAiFromFab() {
            openTab('qa', 'Dosen AI');
            setTimeout(() => {
                const inp = document.getElementById('qa-question');
                if (inp) {
                    inp.focus();
                    inp.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }, 150);
        }

        // =====================================================================
        // STRUKTUR BAB KUSTOM (ACCORDION BUILDER SEPERTI SKRIPSITA)
        // =====================================================================
        const defaultCustomBabs = [
            {
                roman: 'I',
                title: 'Bab I Pendahuluan',
                subbabs: [
                    'Latar Belakang Masalah',
                    'Identifikasi Masalah',
                    'Batasan Masalah',
                    'Rumusan Masalah',
                    'Tujuan Penelitian',
                    'Manfaat Penelitian',
                    'Sistematika Penulisan'
                ]
            },
            {
                roman: 'II',
                title: 'Bab II Tinjauan Pustaka',
                subbabs: [
                    'Landasan Teori',
                    'Penelitian Terdahulu',
                    'Kerangka Berpikir',
                    'Hipotesis Penelitian'
                ]
            },
            {
                roman: 'III',
                title: 'Bab III Metodologi',
                subbabs: [
                    'Jenis dan Pendekatan Penelitian',
                    'Tempat dan Waktu Penelitian',
                    'Populasi dan Sampel',
                    'Variabel dan Definisi Operasional',
                    'Teknik Pengumpulan Data',
                    'Teknik Analisis Data'
                ]
            },
            {
                roman: 'IV',
                title: 'Bab IV Hasil Penelitian dan Pembahasan',
                subbabs: [
                    'Gambaran Umum Objek Penelitian',
                    'Deskripsi Data Penelitian',
                    'Analisis Data dan Hasil Pengujian',
                    'Pembahasan Temuan Penelitian'
                ]
            },
            {
                roman: 'V',
                title: 'Bab V Penutup',
                subbabs: [
                    'Kesimpulan',
                    'Saran'
                ]
            }
        ];

        let customBabsData = JSON.parse(JSON.stringify(defaultCustomBabs));
        let openCustomBabIndex = 0; // Bab 1 default terbuka

        function toggleCustomStructure(enabled) {
            const wrap = document.getElementById('custom-structure-wrap');
            if (wrap) wrap.style.display = enabled ? 'block' : 'none';
            if (enabled) {
                renderCustomChapterAccordion();
            }
            syncCustomStructureOutput();
        }

        function renderCustomChapterAccordion() {
            const container = document.getElementById('custom-babs-accordion-container');
            if (!container) return;

            let html = '';
            customBabsData.forEach((bab, bIdx) => {
                const isOpen = (openCustomBabIndex === bIdx);
                const subCount = bab.subbabs.length;

                html += `
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(15,23,42,0.03);">
                        <!-- Accordion Header -->
                        <div style="padding: 10px 14px; background: ${isOpen ? '#f8fafc' : '#ffffff'}; display: flex; align-items: center; justify-content: space-between; gap: 10px; cursor: pointer; user-select: none; border-bottom: ${isOpen ? '1px solid #e2e8f0' : 'none'};" onclick="toggleCustomBabOpen(${bIdx})">
                            <div style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0;">
                                <span style="display: inline-flex; align-items: center; justify-content: center; width: 18px; height: 18px; font-size: 10px; color: #64748b; transition: transform 0.15s; ${isOpen ? 'transform: rotate(90deg);' : ''}">
                                    ▶
                                </span>
                                <span style="font-size: 11px; font-weight: 700; color: #475569; letter-spacing: 0.05em; text-transform: uppercase; background: #e2e8f0; padding: 2px 7px; border-radius: 4px; white-space: nowrap;">BAB ${bIdx + 1}</span>
                                <input type="text" value="${escapeHtml(bab.title)}" onclick="event.stopPropagation()" oninput="updateBabTitle(${bIdx}, this.value)" style="border: 1px solid transparent; background: transparent; font-size: 13.5px; font-weight: 600; color: var(--text-main); flex: 1; min-width: 120px; padding: 3px 6px; border-radius: 4px;" onfocus="this.style.border='1px solid #cbd5e1'; this.style.background='#fff';" onblur="this.style.border='1px solid transparent'; this.style.background='transparent';">
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px;" onclick="event.stopPropagation()">
                                <span style="font-size: 11.5px; color: #64748b; background: #f1f5f9; padding: 3px 8px; border-radius: 999px; font-weight: 500; white-space: nowrap;">${subCount} sub-bab</span>
                                ${bIdx >= 5 ? `
                                    <button type="button" onclick="deleteCustomBab(${bIdx})" title="Hapus Bab" style="border: none; background: none; color: #ef4444; cursor: pointer; padding: 3px 6px; font-size: 14px;">🗑</button>
                                ` : ''}
                            </div>
                        </div>

                        <!-- Accordion Body (Sub-bab List) -->
                        <div style="display: ${isOpen ? 'block' : 'none'}; padding: 12px 14px; background: #fafafa;">
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                ${bab.subbabs.map((sub, sIdx) => `
                                    <div style="display: flex; align-items: center; gap: 6px; background: #ffffff; padding: 6px 8px; border-radius: 6px; border: 1px solid #e2e8f0; width: 100%; box-sizing: border-box;">
                                        <span style="font-size: 11.5px; font-weight: 700; color: #64748b; width: 26px; flex-shrink: 0; text-align: center;">${bIdx + 1}.${sIdx + 1}</span>
                                        <input type="text" value="${escapeHtml(sub)}" oninput="updateSubbabTitle(${bIdx}, ${sIdx}, this.value)" style="flex: 1; min-width: 0; border: 1px solid #e2e8f0; border-radius: 6px; padding: 5px 8px; font-size: 12.5px; color: var(--text-main); background: #ffffff; outline: none; box-sizing: border-box;" onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='#e2e8f0'">
                                        <div style="display: flex; align-items: center; gap: 3px; flex-shrink: 0;">
                                            <button type="button" onclick="moveSubbab(${bIdx}, ${sIdx}, -1)" ${sIdx === 0 ? 'disabled style="opacity:0.3; cursor:not-allowed;"' : 'style="cursor:pointer;"'} title="Pindah ke atas" style="border: 1px solid #cbd5e1; background: #f8fafc; border-radius: 5px; width: 25px; height: 26px; display: flex; align-items: center; justify-content: center; font-size: 10.5px; color: #475569; padding: 0;">▲</button>
                                            <button type="button" onclick="moveSubbab(${bIdx}, ${sIdx}, 1)" ${sIdx === bab.subbabs.length - 1 ? 'disabled style="opacity:0.3; cursor:not-allowed;"' : 'style="cursor:pointer;"'} title="Pindah ke bawah" style="border: 1px solid #cbd5e1; background: #f8fafc; border-radius: 5px; width: 25px; height: 26px; display: flex; align-items: center; justify-content: center; font-size: 10.5px; color: #475569; padding: 0;">▼</button>
                                            <button type="button" onclick="insertSubbabBelow(${bIdx}, ${sIdx})" title="Tambah sub-bab di bawah ini" style="border: 1px solid #cbd5e1; background: #f8fafc; border-radius: 5px; width: 25px; height: 26px; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; color: #2563eb; cursor: pointer; padding: 0;">+</button>
                                            <button type="button" onclick="deleteSubbab(${bIdx}, ${sIdx})" title="Hapus sub-bab ini" style="border: 1px solid #fecaca; background: #fff5f5; border-radius: 5px; width: 25px; height: 26px; display: flex; align-items: center; justify-content: center; font-size: 12px; color: #dc2626; cursor: pointer; padding: 0;">🗑</button>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>

                            <!-- Bottom Action Bar per Bab: Tambah sub-bab & Reset bab ini (NO "Sarankan AI") -->
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 12px; padding-top: 10px; border-top: 1px solid #e2e8f0; flex-wrap: wrap; gap: 8px;">
                                <button type="button" onclick="addSubbabToEnd(${bIdx})" style="border: none; background: none; color: #2563eb; font-size: 12.5px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; padding: 4px 6px; border-radius: 6px;">
                                    <span style="font-size: 15px; font-weight: 700;">+</span>
                                    <span>Tambah sub-bab</span>
                                </button>
                                <button type="button" onclick="resetSingleBab(${bIdx})" style="border: none; background: none; color: #64748b; font-size: 12px; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; padding: 4px 6px; border-radius: 6px;">
                                    <span>↺</span>
                                    <span>Reset bab ini</span>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;
        }

        function toggleCustomBabOpen(bIdx) {
            openCustomBabIndex = (openCustomBabIndex === bIdx) ? -1 : bIdx;
            renderCustomChapterAccordion();
        }

        function updateBabTitle(bIdx, val) {
            if (customBabsData[bIdx]) {
                customBabsData[bIdx].title = val;
                syncCustomStructureOutput();
            }
        }

        function updateSubbabTitle(bIdx, sIdx, val) {
            if (customBabsData[bIdx] && customBabsData[bIdx].subbabs[sIdx] !== undefined) {
                customBabsData[bIdx].subbabs[sIdx] = val;
                syncCustomStructureOutput();
            }
        }

        function moveSubbab(bIdx, sIdx, dir) {
            const list = customBabsData[bIdx].subbabs;
            const target = sIdx + dir;
            if (target < 0 || target >= list.length) return;
            const temp = list[sIdx];
            list[sIdx] = list[target];
            list[target] = temp;
            renderCustomChapterAccordion();
            syncCustomStructureOutput();
        }

        function insertSubbabBelow(bIdx, sIdx) {
            const list = customBabsData[bIdx].subbabs;
            list.splice(sIdx + 1, 0, 'Sub-bab Baru');
            renderCustomChapterAccordion();
            syncCustomStructureOutput();
        }

        function deleteSubbab(bIdx, sIdx) {
            const list = customBabsData[bIdx].subbabs;
            if (list.length <= 1) {
                alert('Setiap bab minimal memiliki 1 sub-bab.');
                return;
            }
            list.splice(sIdx, 1);
            renderCustomChapterAccordion();
            syncCustomStructureOutput();
        }

        function addSubbabToEnd(bIdx) {
            customBabsData[bIdx].subbabs.push('Sub-bab Baru');
            renderCustomChapterAccordion();
            syncCustomStructureOutput();
        }

        function resetSingleBab(bIdx) {
            if (defaultCustomBabs[bIdx]) {
                customBabsData[bIdx] = JSON.parse(JSON.stringify(defaultCustomBabs[bIdx]));
            } else {
                customBabsData[bIdx].subbabs = ['Sub-bab 1', 'Sub-bab 2'];
            }
            renderCustomChapterAccordion();
            syncCustomStructureOutput();
        }

        function addCustomBab() {
            const nextNum = customBabsData.length + 1;
            const romans = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X'];
            const rom = romans[nextNum - 1] || nextNum;
            customBabsData.push({
                roman: rom,
                title: `Bab ${rom} Analisis Tambahan`,
                subbabs: [`${nextNum}.1 Pendahuluan Analisis`, `${nextNum}.2 Pembahasan Khusus`]
            });
            openCustomBabIndex = customBabsData.length - 1;
            renderCustomChapterAccordion();
            syncCustomStructureOutput();
        }

        function deleteCustomBab(bIdx) {
            if (confirm(`Hapus ${customBabsData[bIdx].title}?`)) {
                customBabsData.splice(bIdx, 1);
                if (openCustomBabIndex >= customBabsData.length) {
                    openCustomBabIndex = customBabsData.length - 1;
                }
                renderCustomChapterAccordion();
                syncCustomStructureOutput();
            }
        }

        function syncCustomStructureOutput() {
            const toggle = document.getElementById('custom-structure-toggle');
            const output = document.getElementById('custom-structure-serialized');
            if (!output) return;

            if (!toggle || !toggle.checked) {
                output.value = '';
                return;
            }

            let text = '';
            customBabsData.forEach((bab, bIdx) => {
                text += `${bab.title.toUpperCase()}:\n`;
                bab.subbabs.forEach((sub, sIdx) => {
                    text += `${bIdx + 1}.${sIdx + 1} ${sub}\n`;
                });
                text += '\n';
            });

            output.value = text.trim();
        }

        function copyReferralLink() {
            const inp = document.getElementById('referral-link-input');
            if (!inp) return;
            const onCopied = () => alert('Link referral berhasil disalin! Bagikan ke teman kampusmu.');
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(inp.value).then(onCopied).catch(() => {
                    inp.select();
                    document.execCommand('copy');
                    onCopied();
                });
            } else {
                inp.select();
                document.execCommand('copy');
                onCopied();
            }
        }

        function shareReferralWhatsapp() {
            const urlEl = document.getElementById('referral-link-input');
            const refUrl = urlEl ? urlEl.value : window.location.origin;
            const text = encodeURIComponent(`Halo! Lagi nyusun proposal atau skripsi? Coba Workspace SkripsiLab AI untuk susun Bab 1-5 otomatis dengan referensi jurnal yang relevan, PPT sidang, dan simulasi penguji AI di sini: ${refUrl}`);
            window.open(`https://wa.me/?text=${text}`, '_blank');
        }

        function handleNewProjectLogoSelect(input) {
            if (!input.files || !input.files[0]) return;
            const file = input.files[0];
            const reader = new FileReader();
            reader.onload = function (e) {
                const img = new Image();
                img.onload = function () {
                    const canvas = document.createElement('canvas');
                    const maxDim = 240;
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
                    ctx.fillStyle = '#FFFFFF';
                    ctx.fillRect(0, 0, w, h);
                    ctx.drawImage(img, 0, 0, w, h);
                    const dataUrl = canvas.toDataURL('image/jpeg', 0.82);

                    const prev = document.getElementById('new-proj-logo-preview');
                    if (prev) {
                        prev.src = dataUrl;
                        prev.style.display = 'block';
                    }
                    const lbl = document.getElementById('new-proj-logo-label');
                    if (lbl) lbl.innerText = file.name.substring(0, 16);
                    
                    // Simpan raw base64 tanpa prefix data:image/... untuk menghindari pencekalan WAF Vercel (CRS rule 931130 RFI)
                    const rawB64 = dataUrl.split(',')[1] || '';
                    const hidden = document.getElementById('input-logo-base64');
                    if (hidden) hidden.value = rawB64;
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }

        function sendFeedbackToWhatsapp() {
            const kategori = document.getElementById('fb-kategori').value;
            const nama = (document.getElementById('fb-nama').value || '').trim();
            const kampus = (document.getElementById('fb-kampus').value || '').trim();
            const prodi = (document.getElementById('fb-prodi').value || '').trim();
            const judul = (document.getElementById('fb-judul').value || '').trim();
            const pesan = (document.getElementById('fb-pesan').value || '').trim();

            if (!pesan) {
                alert('Mohon tuliskan rincian saran atau kendala kamu.');
                return;
            }

            let text = `Halo Mas Alwi (Developer SkripsiLab AI),\nSaya ingin menyampaikan *${kategori}*:\n\n` +
                `*Nama Mahasiswa:* ${nama || '-'}\n` +
                `*Kampus:* ${kampus || '-'}\n` +
                `*Program Studi:* ${prodi || '-'}\n`;
            if (judul) {
                text += `*Judul Skripsi:* ${judul}\n`;
            }
            text += `\n*Pesan / Kendala:*\n${pesan}\n\n_Terkirim dari aplikasi SkripsiLab AI_`;

            const encoded = encodeURIComponent(text);
            const waUrl = `https://wa.me/6282195036971?text=${encoded}`;
            window.open(waUrl, '_blank');
        }

        // =====================================================================
        // 1. CARI JUDUL SKRIPSI (10 REKOMENDASI + ANIMASI LOADING)
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
            btn.innerHTML = '<span class="ai-spinner"></span><span>Menyusun 10 Judul...</span>';
            document.getElementById('titles-result-box').style.display = 'none';
            renderLoadingCard('titles-loading-box', 'AI sedang merumuskan 10 rekomendasi judul skripsi...', 'Menganalisis kebaruan riset, variabel penelitian, dan metodologi yang sesuai...');

            try {
                const res = await fetch("{{ route('tools.titles') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ topik, metode, prodi: prodi || topik, model: getSelectedModel() })
                });
                const data = await res.json();
                if (data.status === 'success') {
                    const list = document.getElementById('titles-list');
                    list.innerHTML = '';
                    (data.data || []).forEach((t, i) => {
                        const row = document.createElement('div');
                        row.className = 'project-row';
                        row.style.cursor = 'default';

                        const left = document.createElement('div');
                        left.className = 'project-row-left';
                        left.innerHTML = `<div class="project-initial">${i + 1}</div><div class="project-row-title">${escapeHtml(t)}</div>`;

                        const useBtn = document.createElement('button');
                        useBtn.type = 'button';
                        useBtn.className = 'btn-outline';
                        useBtn.style.whiteSpace = 'nowrap';
                        useBtn.style.flexShrink = '0';
                        useBtn.textContent = 'Gunakan Judul →';
                        useBtn.addEventListener('click', () => useTitleForProject(t, prodi, metode));

                        row.appendChild(left);
                        row.appendChild(useBtn);
                        list.appendChild(row);
                    });
                    document.getElementById('titles-result-box').style.display = 'block';
                } else {
                    alert(data.message || 'Gagal mencari judul.');
                }
            } catch (e) {
                alert('Terjadi kesalahan koneksi.');
            } finally {
                hideLoadingCard('titles-loading-box');
                btn.disabled = false;
                btn.innerHTML = 'Temukan 10 Judul →';
            }
        }

        function useTitleForProject(judul, prodi, metode) {
            openCreateProjectForm();
            const judulEl = document.getElementById('input-project-judul');
            if (judulEl) judulEl.value = judul || '';
            const prodiEl = document.getElementById('input-project-prodi');
            if (prodi && prodiEl) prodiEl.value = prodi;
            if (metode) {
                const radios = document.querySelectorAll('input[name="metode_penelitian"]');
                const mLow = metode.toLowerCase();
                radios.forEach(r => {
                    const rLow = (r.value || '').toLowerCase();
                    if (rLow === mLow || (mLow.includes('data sekunder') && rLow.includes('data sekunder')) || (mLow.includes('kualitatif') && rLow.includes('kualitatif')) || (mLow.includes('r&d') && rLow.includes('r&d')) || (mLow.includes('ptk') && rLow.includes('ptk')) || (mLow.includes('pustaka') && rLow.includes('pustaka')) || (mLow.includes('mixed') && rLow.includes('mixed')) || (mLow.includes('normatif') && rLow.includes('normatif')) || (mLow.includes('empiris') && rLow.includes('empiris')) || (mLow.includes('kuantitatif') && !mLow.includes('sekunder') && rLow === 'kuantitatif (survei / regresi)')) {
                        r.checked = true;
                    }
                });
            }
        }

        // =====================================================================
        // 2. PPT SIDANG (4-STEP WORKFLOW + 6 TEMPLATE + FULLSCREEN SLIDE SHOW)
        // =====================================================================
        let selectedPptProject = { id: null, title: '' };
        let pptSlides = [];
        let pptCoverMeta = {};
        let currentSlideIdx = 0;
        let currentPptTheme = 'royal_academic';
        let uploadedPptLogoBase64 = '';

        function onUploadPptLogo(input) {
            if (!input.files || !input.files[0]) return;
            const file = input.files[0];
            const reader = new FileReader();
            reader.onload = function (e) {
                uploadedPptLogoBase64 = e.target.result;
                const pBox = document.getElementById('ppt-logo-preview-box');
                const pImg = document.getElementById('ppt-logo-preview-img');
                if (pBox && pImg) {
                    pImg.src = uploadedPptLogoBase64;
                    pBox.style.display = 'inline-flex';
                }
            };
            reader.readAsDataURL(file);
        }

        function setPptStep(step) {
            [1, 2, 3, 4].forEach(s => {
                const view = document.getElementById('ppt-view-step-' + s);
                if (view) view.style.display = (s === step) ? 'block' : 'none';
                const pill = document.getElementById('ppt-step-btn-' + s);
                if (pill) pill.classList.toggle('active', s === step);
            });
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function chooseProjectElementForPpt(el) {
            if (!el) return;
            const id = el.getAttribute('data-id');
            const title = el.getAttribute('data-title') || '';
            const prodi = el.getAttribute('data-prodi') || '';
            const kampus = el.getAttribute('data-kampus') || '';
            const nim = el.getAttribute('data-nim') || '';
            const fakultas = el.getAttribute('data-fakultas') || '';
            const logo = el.getAttribute('data-logo') || '';

            const nimEl = document.getElementById('ppt-input-nim');
            const kampusEl = document.getElementById('ppt-input-kampus');
            const prodiEl = document.getElementById('ppt-input-prodi');
            const fakEl = document.getElementById('ppt-input-fakultas');
            if (nim && nimEl) nimEl.value = nim;
            if (kampus && kampusEl) kampusEl.value = kampus;
            if (prodi && prodiEl) prodiEl.value = prodi;
            if (fakultas && fakEl) fakEl.value = fakultas;

            if (logo) {
                uploadedPptLogoBase64 = logo;
                const pBox = document.getElementById('ppt-logo-preview-box');
                const pImg = document.getElementById('ppt-logo-preview-img');
                if (pBox && pImg) {
                    pImg.src = uploadedPptLogoBase64;
                    pBox.style.display = 'inline-flex';
                }
            }

            chooseProjectForPpt(id, title);
        }

        function chooseProjectForPpt(id, title) {
            selectedPptProject = { id, title };
            const initEl = document.getElementById('ppt-selected-initial');
            if (initEl) initEl.innerText = title.charAt(0).toUpperCase();
            const srcEl = document.getElementById('ppt-selected-source');
            if (srcEl) srcEl.innerText = 'SKRIPSI';
            const titleEl = document.getElementById('ppt-selected-title');
            if (titleEl) titleEl.innerText = title;
            setPptStep(2);
        }

        function onUploadFileForPpt(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                if (file.size > 3.5 * 1024 * 1024) {
                    alert('Ukuran file skripsi melebihi batas 3,5 MB serverless. Kompres gambar di dalam Word/PDF atau pilih file yang lebih ringkas.');
                    input.value = '';
                    return;
                }
                const fname = file.name;
                selectedPptProject = { id: null, title: fname.replace(/\.[^/.]+$/, '').replace(/[_-]+/g, ' ') };
                const initEl = document.getElementById('ppt-selected-initial');
                if (initEl) initEl.innerText = 'F';
                const srcEl = document.getElementById('ppt-selected-source');
                if (srcEl) srcEl.innerText = 'DOKUMEN UNGGAHAN (.DOCX / .PDF)';
                const titleEl = document.getElementById('ppt-selected-title');
                if (titleEl) titleEl.innerText = fname;
                setPptStep(2);
            }
        }

        function applyClientFallbackPptSlides() {
            const namaEl = document.getElementById('ppt-input-nama');
            const nimEl = document.getElementById('ppt-input-nim');
            const kampusEl = document.getElementById('ppt-input-kampus');
            const prodiEl = document.getElementById('ppt-input-prodi');
            const fakEl = document.getElementById('ppt-input-fakultas');
            const p1El = document.getElementById('ppt-input-pembimbing-1');
            const p2El = document.getElementById('ppt-input-pembimbing-2');
            const u1El = document.getElementById('ppt-input-penguji-1');
            const u2El = document.getElementById('ppt-input-penguji-2');
            const slideCountEl = document.getElementById('ppt-select-slide-count') || document.getElementById('ppt-input-slide-count');
            const reqCount = Math.max(6, Math.min(12, parseInt(slideCountEl?.value || '8', 10) || 8));

            const safeTitle = selectedPptProject.title || 'Presentasi Sidang Skripsi';
            const safeProdi = (prodiEl && prodiEl.value.trim()) ? prodiEl.value.trim() : 'Program Studi';

            pptCoverMeta = {
                nama: (namaEl && namaEl.value.trim()) ? namaEl.value.trim() : 'Nama Mahasiswa',
                nim: nimEl ? nimEl.value.trim() : '',
                kampus: (kampusEl && kampusEl.value.trim()) ? kampusEl.value.trim() : 'UNIVERSITAS',
                prodi: safeProdi,
                fakultas: fakEl ? fakEl.value.trim() : '',
                pembimbing_1: p1El ? p1El.value.trim() : '',
                pembimbing_2: p2El ? p2El.value.trim() : '',
                penguji_1: u1El ? u1El.value.trim() : '',
                penguji_2: u2El ? u2El.value.trim() : '',
            };

            const pool = [
                {
                    section: 'Sampul Sidang',
                    title: safeTitle,
                    subtitle: `Program Studi ${safeProdi} · Sidang Skripsi`,
                    bullets: ['Presentasi Tugas Akhir / Skripsi', 'Fokus Kajian Ilmiah & Evaluasi Kinerja'],
                    speaker_notes: `Selamat pagi Bapak dan Ibu Dosen Penguji serta Pembimbing. Terima kasih atas kesempatan untuk memaparkan skripsi saya berjudul ${safeTitle}.`
                },
                {
                    section: 'Latar Belakang Masalah',
                    title: 'Latar Belakang & Urgensi Penelitian',
                    subtitle: 'Fenomena Lapangan dan Kesenjangan Riset',
                    bullets: [
                        'Urgensi pemecahan masalah nyata pada objek penelitian',
                        'Keterbatasan pendekatan terdahulu dalam menjawab fenomena aktual',
                        'Kebutuhan metode analisis yang terukur, valid, dan objektif',
                        'Kebaruan (research gap) yang menjadi kontribusi utama studi ini'
                    ],
                    speaker_notes: 'Penelitian ini dilatarbelakangi oleh fenomena riil dan kesenjangan riset yang ditemukan pada objek kajian.'
                },
                {
                    section: 'Rumusan & Tujuan',
                    title: 'Rumusan Masalah & Tujuan Penelitian',
                    subtitle: 'Fokus Kajian yang Terarah dan Terukur',
                    bullets: [
                        'Rumusan Masalah: Bagaimana efektivitas metode terhadap objek penelitian?',
                        'Batasan Riset: Fokus pada variabel, populasi, dan periode pengamatan',
                        'Tujuan: Menganalisis dan membuktikan hasil secara empiris & terukur',
                        'Manfaat: Memberikan kontribusi teoretis dan solusi praktis'
                    ],
                    speaker_notes: 'Tujuan penelitian ini disusun langsung untuk menjawab seluruh rumusan masalah secara sistematis.'
                },
                {
                    section: 'Tinjauan Pustaka',
                    title: 'Landasan Teori & Kajian Terdahulu',
                    subtitle: 'Kerangka Teoretis & Posisi Kebaruan',
                    bullets: [
                        'Grand theory dan konsep utama yang melandasi variabel penelitian',
                        'Sintesis 4–5 penelitian jurnal bereputasi periode 2021–2026',
                        'Kerangka berpikir yang menghubungkan masalah, teori, dan luaran'
                    ],
                    speaker_notes: 'Pustaka acuan mengacu pada teori baku dan publikasi jurnal ilmiah bereputasi terkini.'
                },
                {
                    section: 'Metodologi Penelitian',
                    title: 'Tahapan & Metode Penelitian',
                    subtitle: 'Alur Kerja dan Prosedur Pengujian',
                    bullets: [
                        'Desain penelitian, populasi/sampel, dan teknik pengumpulan data',
                        'Tahapan pengolahan data serta uji validitas/reliabilitas instrumen',
                        'Teknik analisis data dan metrik evaluasi kinerja yang digunakan'
                    ],
                    speaker_notes: 'Metodologi disusun sistematis mulai dari pengumpulan data hingga tahapan validasi dan analisis hasil.'
                },
                {
                    section: 'Hasil Penelitian',
                    title: 'Hasil Pengujian & Analisis Data',
                    subtitle: 'Temuan Empiris dan Capaian Utama',
                    bullets: [
                        'Hasil analisis data utama yang menjawab rumusan masalah',
                        'Ringkasan capaian metrik kinerja / pengujian hipotesis penelitian',
                        'Temuan kunci lapangan berdasarkan pengolahan data empiris'
                    ],
                    speaker_notes: 'Berikut adalah hasil pengujian empiris yang menunjukkan capaian kinerja penelitian secara objektif.'
                },
                {
                    section: 'Pembahasan',
                    title: 'Pembahasan & Interpretasi Temuan',
                    subtitle: 'Komparasi Teori & Implikasi Ilmiah',
                    bullets: [
                        'Kesesuaian temuan empiris dengan hipotesis dan teori utama',
                        'Perbandingan hasil penelitian dengan studi terdahulu yang relevan',
                        'Implikasi praktis bagi objek penelitian dan pengembangan ilmu'
                    ],
                    speaker_notes: 'Pembahasan ini menguraikan makna ilmiah dari temuan serta relevansinya terhadap literatur sebelumnya.'
                },
                {
                    section: 'Evaluasi & Keterbatasan',
                    title: 'Evaluasi Kinerja & Keterbatasan Riset',
                    subtitle: 'Refleksi Kritis Pelaksanaan Penelitian',
                    bullets: [
                        'Keunggulan metode yang diterapkan pada skenario uji nyata',
                        'Keterbatasan ruang lingkup sampel, data, dan kondisi lapangan',
                        'Peluang penyempurnaan untuk penelitian lanjutan'
                    ],
                    speaker_notes: 'Evaluasi kritis menyajikan kelebihan sekaligus keterbatasan riset secara objektif dan transparan.'
                },
                {
                    section: 'Kerangka & Implementasi',
                    title: 'Kerangka Konseptual & Implementasi',
                    subtitle: 'Integrasi Alur Sistem dan Operasional',
                    bullets: [
                        'Keterkaitan input, proses analisis, dan output yang dihasilkan',
                        'Standarisasi prosedur pengujian agar hasil dapat direplikasi',
                        'Kelayakan penerapan pada lingkungan operasional nyata'
                    ],
                    speaker_notes: 'Kerangka implementasi memastikan seluruh prosedur berjalan konsisten dan terukur.'
                },
                {
                    section: 'Validasi Lanjutan',
                    title: 'Validasi Hasil & Analisis Kesalahan',
                    subtitle: 'Ketahanan dan Keandalan Pengujian',
                    bullets: [
                        'Uji konsistensi hasil pada variasi skenario pengujian',
                        'Identifikasi sumber kesalahan utama dan strategi mitigasinya',
                        'Bukti keandalan model/instrumen terhadap data uji'
                    ],
                    speaker_notes: 'Validasi lanjutan membuktikan bahwa hasil penelitian stabil dan dapat dipertanggungjawabkan.'
                },
                {
                    section: 'Rekomendasi Praktis',
                    title: 'Rekomendasi Implementasi & Kebijakan',
                    subtitle: 'Langkah Aplikatif bagi Pemangku Kepentingan',
                    bullets: [
                        'Panduan praktis penerapan temuan bagi instansi atau pengguna',
                        'Langkah peningkatan kualitas data dan proses berkelanjutan',
                        'Roadmap pengembangan sistem atau kebijakan jangka panjang'
                    ],
                    speaker_notes: 'Rekomendasi dirumuskan agar hasil skripsi memberi manfaat langsung secara aplikatif.'
                },
                {
                    section: 'Kesimpulan & Saran',
                    title: 'Kesimpulan & Saran Pengembangan',
                    subtitle: 'Penutup Presentasi Sidang Skripsi',
                    bullets: [
                        'Kesimpulan utama menjawab seluruh rumusan masalah secara tegas',
                        'Saran praktis bagi objek penelitian dan pengguna terkait',
                        'Saran akademis untuk pengembangan riset di masa mendatang'
                    ],
                    speaker_notes: 'Sebagai penutup, seluruh tujuan penelitian telah terjawab dan saran disajikan untuk riset berikutnya. Terima kasih.'
                }
            ];

            const selected = pool.slice(0, reqCount - 1);
            selected.push(pool[pool.length - 1]);
            pptSlides = selected.map((item, idx) => Object.assign({}, item, { slide_number: idx + 1 }));

            currentSlideIdx = 0;
            renderCurrentSlide();
            setPptStep(3);
        }

        async function handleGeneratePpt() {
            const btn = document.getElementById('btn-generate-ppt');
            const slideCountEl = document.getElementById('ppt-select-slide-count') || document.getElementById('ppt-input-slide-count');
            const slideCount = slideCountEl ? parseInt(slideCountEl.value) || 8 : 8;

            btn.disabled = true;
            btn.innerHTML = `<span class="ai-spinner"></span><span>Menyusun ${slideCount} Slide Presentasi...</span>`;
            renderLoadingCard('ppt-loading-box', `AI sedang merangkum naskah menjadi ${slideCount} slide sidang...`, 'Menyusun poin presentasi Bab I–V dan catatan bicara (speaker notes)...');

            const formData = new FormData();
            formData.append('slide_count', slideCount);
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

            const namaEl = document.getElementById('ppt-input-nama');
            const nimEl = document.getElementById('ppt-input-nim');
            const kampusEl = document.getElementById('ppt-input-kampus');
            const prodiEl = document.getElementById('ppt-input-prodi');
            const fakEl = document.getElementById('ppt-input-fakultas');
            const p1El = document.getElementById('ppt-input-pembimbing-1');
            const p2El = document.getElementById('ppt-input-pembimbing-2');
            const u1El = document.getElementById('ppt-input-penguji-1');
            const u2El = document.getElementById('ppt-input-penguji-2');

            if (namaEl) formData.append('nama', namaEl.value.trim());
            if (nimEl) formData.append('nim', nimEl.value.trim());
            if (kampusEl) formData.append('kampus', kampusEl.value.trim());
            if (prodiEl) formData.append('prodi', prodiEl.value.trim());
            if (fakEl) formData.append('fakultas', fakEl.value.trim());
            if (p1El) formData.append('pembimbing_1', p1El.value.trim());
            if (p2El) formData.append('pembimbing_2', p2El.value.trim());
            if (u1El) formData.append('penguji_1', u1El.value.trim());
            if (u2El) formData.append('penguji_2', u2El.value.trim());
            if (uploadedPptLogoBase64) formData.append('logo_base64', uploadedPptLogoBase64);

            try {
                const res = await fetch("{{ route('tools.ppt') }}", {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    body: formData
                });
                const data = await res.json();
                if (data.status === 'success' && Array.isArray(data.slides_data) && data.slides_data.length > 0) {
                    pptSlides = data.slides_data;
                    pptCoverMeta = data.cover_meta || {
                        nama: namaEl ? namaEl.value.trim() : '',
                        nim: nimEl ? nimEl.value.trim() : '',
                        kampus: kampusEl ? kampusEl.value.trim() : '',
                        prodi: prodiEl ? prodiEl.value.trim() : '',
                        fakultas: fakEl ? fakEl.value.trim() : '',
                        pembimbing_1: p1El ? p1El.value.trim() : '',
                        pembimbing_2: p2El ? p2El.value.trim() : '',
                        penguji_1: u1El ? u1El.value.trim() : '',
                        penguji_2: u2El ? u2El.value.trim() : '',
                    };
                    if (data.saved_ppt && data.saved_ppt.id) {
                        activeSavedPptId = data.saved_ppt.id;
                        upsertSavedPptLocal(data.saved_ppt);
                    } else {
                        activeSavedPptId = null;
                        scheduleAutoSavePptDeck(true);
                    }
                    currentSlideIdx = 0;
                    renderCurrentSlide();
                    setPptStep(3);
                } else {
                    applyClientFallbackPptSlides();
                    activeSavedPptId = null;
                    scheduleAutoSavePptDeck(true);
                }
            } catch (e) {
                applyClientFallbackPptSlides();
                activeSavedPptId = null;
                scheduleAutoSavePptDeck(true);
            } finally {
                hideLoadingCard('ppt-loading-box');
                btn.disabled = false;
                btn.innerHTML = '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg><span>Buat slide sidang sekarang</span>';
            }
        }

        function updateSlideCountUI(val) {
            const num = parseInt(val) || 8;
            const range = document.getElementById('ppt-input-slide-count');
            const select = document.getElementById('ppt-select-slide-count');
            const badge = document.getElementById('ppt-slide-count-badge');
            if (range) range.value = num;
            if (select) select.value = String(num);
            if (badge) {
                if (num <= 7) {
                    badge.innerText = `${num} Slide (Presentasi Kilat 10 Menit)`;
                } else if (num <= 9) {
                    badge.innerText = `${num} Slide (Standar Sidang Skripsi)`;
                } else {
                    badge.innerText = `${num} Slide (Lengkap & Mendalam)`;
                }
            }
        }

        function renderSlideNumberStrip() {
            const strip = document.getElementById('ppt-num-strip');
            if (!strip) return;
            strip.innerHTML = '';
            pptSlides.forEach((_, i) => {
                const dot = document.createElement('button');
                dot.type = 'button';
                dot.className = 'ppt-num-btn' + (i === currentSlideIdx ? ' active' : '');
                dot.innerText = i + 1;
                dot.onclick = () => {
                    syncCurrentSlideEdit();
                    currentSlideIdx = i;
                    renderCurrentSlide();
                };
                strip.appendChild(dot);
            });
        }

        function resolvePptPanelData(meta) {
            const m = meta || {};
            const p1 = (m.pembimbing_1 || '').trim();
            const p2 = (m.pembimbing_2 || '').trim();
            const u1 = (m.penguji_1 || '').trim();
            const u2 = (m.penguji_2 || '').trim();
            const isDefaultPlaceholder = (val) => /^dosen\s+(pembimbing|penguji)\s*[12]$/i.test(val);
            const hasRealPenguji = (u1 && !isDefaultPlaceholder(u1)) || (u2 && !isDefaultPlaceholder(u2));

            if (hasRealPenguji) {
                const leftLines = [];
                leftLines.push('1. ' + (p1 || 'Dosen Pembimbing 1'));
                if (p2) leftLines.push('2. ' + p2);

                const rightLines = [];
                rightLines.push('1. ' + (u1 || 'Dosen Penguji 1'));
                if (u2) rightLines.push('2. ' + u2);

                return {
                    leftTitle: 'Dosen Pembimbing',
                    leftLines,
                    rightTitle: 'Dosen Penguji',
                    rightLines
                };
            }

            // Jika penguji tidak diisi, tampilkan Pembimbing I & Pembimbing II (atau Pembimbing & Penguji rapi)
            if (p1 && p2 && !isDefaultPlaceholder(p2)) {
                return {
                    leftTitle: 'Dosen Pembimbing I',
                    leftLines: [p1],
                    rightTitle: 'Dosen Pembimbing II',
                    rightLines: [p2]
                };
            }

            return {
                leftTitle: 'Dosen Pembimbing',
                leftLines: ['1. ' + (p1 || 'Dosen Pembimbing 1'), ...(p2 ? ['2. ' + p2] : [])],
                rightTitle: 'Dosen Penguji',
                rightLines: ['1. ' + (u1 || 'Dosen Penguji 1'), ...(u2 ? ['2. ' + u2] : [])]
            };
        }

        function buildPptInstitutionLine(meta) {
            const m = meta || {};
            const parts = [];
            if (m.prodi) parts.push('PROGRAM STUDI ' + String(m.prodi).trim().toUpperCase());
            if (m.fakultas) parts.push(String(m.fakultas).trim().toUpperCase());
            if (m.kampus) parts.push(String(m.kampus).trim().toUpperCase());
            parts.push(String(new Date().getFullYear()));
            return parts.filter(Boolean).join(' · ');
        }

        function fitPptCanvasToFrame(canvasEl) {
            if (!canvasEl) return;
            canvasEl.classList.remove('ppt-density-compact', 'ppt-density-dense', 'ppt-density-micro');
            const steps = ['ppt-density-compact', 'ppt-density-dense', 'ppt-density-micro'];
            for (let i = 0; i < steps.length; i++) {
                if (canvasEl.scrollHeight <= canvasEl.clientHeight + 3) break;
                canvasEl.classList.remove('ppt-density-compact', 'ppt-density-dense', 'ppt-density-micro');
                canvasEl.classList.add(steps[i]);
            }
        }

        window.addEventListener('resize', () => {
            const box = document.getElementById('ppt-canvas-box');
            if (box && box.offsetParent !== null) fitPptCanvasToFrame(box);
            const fsBox = document.getElementById('fs-slide-canvas');
            if (fsBox && fsBox.offsetParent !== null) fitPptCanvasToFrame(fsBox);
        });

        function renderPptSlideIntoDom({ canvasEl, secEl, countEl, campusBadgeEl, titleEl, subEl, ulEl, s, idx, total }) {
            const activeLogo = uploadedPptLogoBase64 || (pptCoverMeta && pptCoverMeta.logo_base64) || '';
            if (countEl) countEl.innerText = `Slide ${idx + 1} / ${total}`;

            if (campusBadgeEl) {
                const campusName = escapeHtml((pptCoverMeta.kampus || 'UNIVERSITAS').toUpperCase());
                if (activeLogo) {
                    campusBadgeEl.innerHTML = `<img src="${activeLogo}" alt="Logo" style="width: 20px; height: 20px; object-fit: contain; border-radius: 3px;"><span>${campusName}</span>`;
                } else {
                    campusBadgeEl.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 2 3 3 6 3s6-1 6-3v-5"/></svg><span>${campusName}</span>`;
                }
            }

            if (canvasEl) canvasEl.classList.toggle('ppt-is-cover', idx === 0);

            if (idx === 0) {
                if (secEl) secEl.innerText = 'SLIDE 1 · COVER SIDANG';
                if (titleEl) {
                    titleEl.style.textAlign = 'center';
                    const titleRaw = (s.title || selectedPptProject.title || 'Judul Skripsi').replace(/\*\*/g, '');
                    const isLong = titleRaw.length > 68;
                    // TANPA logo dobel di atas judul karena logo resmi sudah tampil tajam di badge kampus kiri atas
                    titleEl.innerHTML = `<div class="ppt-cover-title-text ${isLong ? 'ppt-title-long' : ''}">${escapeHtml(titleRaw)}</div><div class="ppt-cover-divider"></div>`;
                }
                if (subEl) {
                    subEl.style.textAlign = 'center';
                    subEl.innerHTML = `
                        <div class="ppt-cover-oleh-box">
                            <div class="ppt-cover-oleh-tag">DISUSUN OLEH</div>
                            <div class="ppt-cover-author-name">${escapeHtml(pptCoverMeta.nama || 'Nama Mahasiswa')}</div>
                            ${pptCoverMeta.nim ? `<div class="ppt-cover-nim">NIM / NPM: ${escapeHtml(pptCoverMeta.nim)}</div>` : ''}
                        </div>
                    `;
                }
                if (ulEl) {
                    ulEl.classList.remove('ppt-bullets-two-col');
                    ulEl.style.textAlign = 'left';
                    ulEl.style.listStyle = 'none';
                    ulEl.style.paddingLeft = '0';
                    ulEl.style.marginLeft = '0';
                    const panel = resolvePptPanelData(pptCoverMeta);
                    const instLine = buildPptInstitutionLine(pptCoverMeta);
                    ulEl.innerHTML = `
                        <li class="ppt-cover-pembimbing-li">
                            <div class="ppt-pembimbing-grid">
                                <div class="ppt-pembimbing-card">
                                    <div class="ppt-pembimbing-label">${escapeHtml(panel.leftTitle)}</div>
                                    ${panel.leftLines.map(l => `<div class="ppt-pembimbing-name">${escapeHtml(l)}</div>`).join('')}
                                </div>
                                <div class="ppt-pembimbing-card">
                                    <div class="ppt-pembimbing-label">${escapeHtml(panel.rightTitle)}</div>
                                    ${panel.rightLines.map(l => `<div class="ppt-pembimbing-name">${escapeHtml(l)}</div>`).join('')}
                                </div>
                            </div>
                            <div class="ppt-cover-footer-inst">${escapeHtml(instLine)}</div>
                        </li>
                    `;
                }
            } else {
                if (titleEl) {
                    titleEl.style.textAlign = 'left';
                    titleEl.innerText = (s.title || '').replace(/\*\*/g, '');
                }
                if (subEl) {
                    subEl.style.textAlign = 'left';
                    subEl.innerText = (s.subtitle || '').replace(/\*\*/g, '');
                }
                if (secEl) secEl.innerText = (s.section || ('Slide ' + (idx + 1))).replace(/\*\*/g, '');

                if (ulEl) {
                    const cleanBullets = (s.bullets || []).map(b => String(b).replace(/\*\*/g, '').trim()).filter(Boolean);
                    const totalChars = cleanBullets.reduce((sum, b) => sum + b.length, 0);
                    // Otomatis gunakan grid 2 kolom saat poin >= 4 atau teks padat agar 100% muat di canvas 16:9 tanpa terpotong!
                    ulEl.classList.toggle('ppt-bullets-two-col', cleanBullets.length >= 4 || totalChars > 260);
                    ulEl.innerHTML = '';
                    cleanBullets.forEach((b) => {
                        const li = document.createElement('li');
                        li.innerText = b;
                        ulEl.appendChild(li);
                    });
                }
            }

            requestAnimationFrame(() => fitPptCanvasToFrame(canvasEl));
        }

        function renderCurrentSlide() {
            if (!pptSlides.length) return;
            const s = pptSlides[currentSlideIdx];
            const box = document.getElementById('ppt-canvas-box');
            const foot = document.getElementById('slide-footer-proj-title');
            if (foot) foot.innerText = selectedPptProject.title || 'Presentasi Sidang Skripsi';

            renderPptSlideIntoDom({
                canvasEl: box,
                secEl: document.getElementById('slide-section-label'),
                countEl: document.getElementById('slide-counter-label'),
                campusBadgeEl: document.getElementById('slide-campus-badge'),
                titleEl: document.getElementById('slide-title-edit'),
                subEl: document.getElementById('slide-subtitle-edit'),
                ulEl: document.getElementById('slide-bullets-edit'),
                s,
                idx: currentSlideIdx,
                total: pptSlides.length
            });

            document.getElementById('slide-notes-edit').value = s.speaker_notes || '';
            renderSlideNumberStrip();

            const prevBtn = document.getElementById('btn-ppt-prev');
            const nextBtn = document.getElementById('btn-ppt-next');
            if (prevBtn) prevBtn.disabled = currentSlideIdx === 0;
            if (nextBtn) nextBtn.disabled = currentSlideIdx >= pptSlides.length - 1;

            if (box) {
                box.classList.remove('ppt-slide-anim');
                void box.offsetWidth;
                box.classList.add('ppt-slide-anim');
            }
        }

        function syncCurrentSlideEdit() {
            if (!pptSlides.length) return;
            const s = pptSlides[currentSlideIdx];
            if (currentSlideIdx === 0) {
                const titleTxt = document.querySelector('#slide-title-edit .ppt-cover-title-text')?.innerText.trim()
                    || document.getElementById('slide-title-edit').innerText.trim();
                if (titleTxt) s.title = titleTxt;
                const authorEl = document.querySelector('#slide-subtitle-edit .ppt-cover-author-name');
                if (authorEl && authorEl.innerText.trim()) pptCoverMeta.nama = authorEl.innerText.trim();
            } else {
                s.section = document.getElementById('slide-section-label').innerText.trim();
                s.title = document.getElementById('slide-title-edit').innerText.trim();
                s.subtitle = document.getElementById('slide-subtitle-edit').innerText.trim();
                const bullets = [];
                document.querySelectorAll('#slide-bullets-edit li').forEach(li => {
                    if (li.innerText.trim()) bullets.push(li.innerText.trim());
                });
                if (bullets.length) s.bullets = bullets;
            }
            s.speaker_notes = document.getElementById('slide-notes-edit').value;
            scheduleAutoSavePptDeck();
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
            scheduleAutoSavePptDeck();
        }

        function setPptTheme(theme, btn) {
            currentPptTheme = theme;
            const box = document.getElementById('ppt-canvas-box');
            if (box) {
                box.className = 'ppt-canvas ppt-theme-' + theme;
                requestAnimationFrame(() => fitPptCanvasToFrame(box));
            }
            document.querySelectorAll('.theme-chip').forEach(d => d.classList.remove('active'));
            if (btn) btn.classList.add('active');
            const gridView = document.getElementById('ppt-view-step-4');
            if (gridView && gridView.style.display === 'block') {
                renderPptGridPreview();
            }
            scheduleAutoSavePptDeck();
        }

        // =========================================================================
        // PPT SAYA (RIWAYAT PPT TERSIMPAN — BISA DILIHAT, DIEDIT & DIUNDUH ULANG)
        // =========================================================================
        const PPT_LOCAL_STORAGE_KEY = 'skripsilab_saved_ppts_{{ $user->id }}';
        const PPT_THEME_LABELS = {
            royal_academic: 'Royal Blue',
            sidang_crimson: 'Sidang Crimson',
            canva_editorial: 'Cream Editorial',
            emerald_executive: 'Emerald Botanical',
            swiss_minimal: 'Swiss Minimal',
            basic_navy: 'Basic Navy',
            basic_white: 'Basic Putih',
            maroon_prestige: 'Maroon Prestige',
            tech_dark: 'Dark Tech',
            pastel_lavender: 'Lavender',
            nordic_teal: 'Nordic Teal',
            warm_terracotta: 'Terracotta',
            classic_gold: 'Oxford Navy Gold'
        };
        let savedPptDecks = @json($savedPpts ?? []);
        let activeSavedPptId = null;
        let pptAutoSaveTimer = null;

        (function hydrateSavedPptsFromLocal() {
            try {
                const raw = localStorage.getItem(PPT_LOCAL_STORAGE_KEY);
                const localList = raw ? JSON.parse(raw) : [];
                if (Array.isArray(localList) && localList.length) {
                    const existingIds = new Set(savedPptDecks.map(d => String(d.id)));
                    localList.forEach(item => {
                        if (item && item.slides_data && item.slides_data.length && !existingIds.has(String(item.id))) {
                            savedPptDecks.push(item);
                        }
                    });
                }
            } catch (e) {}
        })();

        function persistSavedPptsToLocal() {
            try {
                const compact = savedPptDecks.slice(0, 15).map(d => ({
                    id: d.id,
                    project_id: d.project_id || null,
                    title: d.title,
                    theme: d.theme || 'royal_academic',
                    slide_count: (d.slides_data || []).length || d.slide_count || 8,
                    slides_data: d.slides_data || [],
                    cover_meta: Object.assign({}, d.cover_meta || {}, { logo_base64: '' }),
                    updated_at_human: d.updated_at_human || 'baru saja'
                }));
                localStorage.setItem(PPT_LOCAL_STORAGE_KEY, JSON.stringify(compact));
            } catch (e) {}
        }

        function upsertSavedPptLocal(deck) {
            if (!deck || !deck.slides_data || !deck.slides_data.length) return;
            const idx = savedPptDecks.findIndex(d => String(d.id) === String(deck.id));
            if (idx >= 0) {
                savedPptDecks.splice(idx, 1);
            }
            savedPptDecks.unshift(deck);
            persistSavedPptsToLocal();
            renderSavedPptsList();
        }

        function renderSavedPptsList() {
            const listEl = document.getElementById('saved-ppts-list');
            const countEl = document.getElementById('saved-ppts-count');
            if (!listEl) return;
            if (countEl) countEl.textContent = String(savedPptDecks.length);

            if (!savedPptDecks.length) {
                listEl.innerHTML = `<div style="padding: 18px 20px; font-size: 13px; color: var(--text-muted); text-align: center;">Belum ada PPT tersimpan. Pilih proyek skripsimu atau unggah file di bawah untuk membuat PPT Sidang pertamamu — hasilnya akan otomatis tersimpan di sini.</div>`;
                return;
            }

            listEl.innerHTML = savedPptDecks.map((deck, idx) => {
                const themeName = PPT_THEME_LABELS[deck.theme] || 'Royal Blue';
                const slideCnt = (deck.slides_data || []).length || deck.slide_count || 8;
                const timeLabel = deck.updated_at_human || 'Tersimpan';
                return `
                    <div class="project-row" style="cursor: default; gap: 12px;">
                        <div class="project-row-left" style="cursor: pointer;" onclick="openSavedPptDeck(${idx}, false)">
                            <div style="min-width: 0; width: 100%;">
                                <div class="project-row-title">${escapeHtml(deck.title || 'Presentasi Sidang Skripsi')}</div>
                                <div class="project-row-meta">
                                    <span style="color: var(--primary); font-weight: 600;">${slideCnt} Slide</span>
                                    · Tema ${escapeHtml(themeName)}
                                    · ${escapeHtml(timeLabel)}
                                </div>
                            </div>
                        </div>
                        <div class="project-row-action" style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                            <button type="button" class="btn-outline" style="padding: 6px 11px; min-height: 32px; font-size: 12px;" onclick="openSavedPptDeck(${idx}, false)">Lihat &amp; Edit</button>
                            <button type="button" class="btn-primary" style="padding: 6px 11px; min-height: 32px; font-size: 12px;" onclick="openSavedPptDeck(${idx}, true)">Unduh .pptx</button>
                            <button type="button" class="btn-outline" style="padding: 6px 9px; min-height: 32px; font-size: 12px; color: #dc2626; border-color: #fecaca;" onclick="deleteSavedPptDeck(${idx})" title="Hapus PPT">Hapus</button>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function openSavedPptDeck(idx, autoDownload) {
            const deck = savedPptDecks[idx];
            if (!deck || !Array.isArray(deck.slides_data) || !deck.slides_data.length) return;
            activeSavedPptId = typeof deck.id === 'number' ? deck.id : null;
            selectedPptProject = {
                id: deck.project_id || null,
                title: deck.title || 'Presentasi Sidang Skripsi',
                prodi: (deck.cover_meta && deck.cover_meta.prodi) || ''
            };
            pptSlides = JSON.parse(JSON.stringify(deck.slides_data));
            pptCoverMeta = Object.assign({}, deck.cover_meta || {});
            uploadedPptLogoBase64 = pptCoverMeta.logo_base64 || '';
            currentSlideIdx = 0;

            const chosenTheme = deck.theme || 'royal_academic';
            const chipBtn = document.querySelector(`.theme-chip[onclick*="'${chosenTheme}'"]`);
            currentPptTheme = chosenTheme;
            renderCurrentSlide();
            setPptTheme(chosenTheme, chipBtn);
            setPptStep(3);

            if (autoDownload) {
                setTimeout(() => downloadPptxFile(), 120);
            }
        }

        async function deleteSavedPptDeck(idx) {
            const deck = savedPptDecks[idx];
            if (!deck) return;
            if (!confirm('Hapus PPT sidang ini dari daftar PPT Saya?')) return;
            savedPptDecks.splice(idx, 1);
            if (String(activeSavedPptId) === String(deck.id)) activeSavedPptId = null;
            persistSavedPptsToLocal();
            renderSavedPptsList();
            if (typeof deck.id === 'number') {
                try {
                    await fetch(`/tools/ppt/${deck.id}`, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': csrfToken }
                    });
                } catch (e) {}
            }
        }

        function scheduleAutoSavePptDeck(immediate) {
            if (!Array.isArray(pptSlides) || !pptSlides.length) return;
            const statusEl = document.getElementById('ppt-autosave-status');
            if (statusEl) statusEl.textContent = 'Menyimpan ke PPT Saya...';
            clearTimeout(pptAutoSaveTimer);
            pptAutoSaveTimer = setTimeout(async () => {
                const safeMeta = Object.assign({}, pptCoverMeta || {});
                if (safeMeta.logo_base64) {
                    safeMeta.logo_base64 = String(safeMeta.logo_base64).replace(/^data:image\/[^;]+;base64,/i, '');
                }
                const title = (selectedPptProject && selectedPptProject.title) || (pptSlides[0] && pptSlides[0].title) || 'Presentasi Sidang Skripsi';
                try {
                    const res = await fetch("{{ route('tools.ppt.save') }}", {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                        body: JSON.stringify({
                            id: activeSavedPptId,
                            project_id: selectedPptProject ? selectedPptProject.id : null,
                            title: title,
                            theme: currentPptTheme || 'royal_academic',
                            slides_data: pptSlides,
                            cover_meta: safeMeta
                        })
                    });
                    const data = await res.json();
                    if (res.ok && data.status === 'success' && data.saved_ppt) {
                        activeSavedPptId = data.saved_ppt.id;
                        upsertSavedPptLocal(data.saved_ppt);
                        if (statusEl) statusEl.textContent = '✓ Tersimpan di PPT Saya';
                        return;
                    }
                } catch (e) {}
                // Fallback simpan lokal
                const localId = activeSavedPptId || ('local_' + Date.now());
                upsertSavedPptLocal({
                    id: localId,
                    project_id: selectedPptProject ? selectedPptProject.id : null,
                    title: title,
                    theme: currentPptTheme || 'royal_academic',
                    slide_count: pptSlides.length,
                    slides_data: JSON.parse(JSON.stringify(pptSlides)),
                    cover_meta: Object.assign({}, pptCoverMeta || {}),
                    updated_at_human: 'baru saja'
                });
                if (statusEl) statusEl.textContent = '✓ Tersimpan di PPT Saya';
            }, immediate ? 80 : 650);
        }

        document.addEventListener('DOMContentLoaded', () => {
            renderSavedPptsList();
        });

        function openPptGridPreviewStep() {
            syncCurrentSlideEdit();
            renderPptGridPreview();
            setPptStep(4);
        }

        function renderPptGridPreview() {
            const container = document.getElementById('ppt-grid-preview-list');
            if (!container) return;
            container.innerHTML = '';
            const panel = resolvePptPanelData(pptCoverMeta);
            const instLine = buildPptInstitutionLine(pptCoverMeta);

            pptSlides.forEach((s, idx) => {
                const card = document.createElement('div');
                card.className = `ppt-canvas ppt-theme-${currentPptTheme}`;
                card.style.aspectRatio = '16 / 9';
                card.style.minHeight = '210px';
                card.style.padding = '16px 18px';
                card.style.cursor = 'pointer';
                card.onclick = () => {
                    currentSlideIdx = idx;
                    renderCurrentSlide();
                    setPptStep(3);
                };

                if (idx === 0) {
                    card.innerHTML = `
                        <div style="text-align: center; justify-content: space-between; gap: 6px;">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <span style="font-size: 9.5px; font-weight: 700; opacity: 0.85;">${escapeHtml((pptCoverMeta.kampus || 'UNIVERSITAS').toUpperCase())}</span>
                                <span class="ppt-badge" style="font-size:9px; padding:2px 8px;">COVER SIDANG</span>
                            </div>
                            <div style="font-size:12.5px; font-weight:800; line-height:1.25;">${escapeHtml((s.title || selectedPptProject.title || 'Judul Skripsi').replace(/\*\*/g, ''))}</div>
                            <div style="font-size:10.5px; font-weight:600; opacity:0.9;">Oleh: ${escapeHtml(pptCoverMeta.nama || 'Nama Mahasiswa')}${pptCoverMeta.nim ? ' · ' + escapeHtml(pptCoverMeta.nim) : ''}</div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px; font-size: 9px; text-align: left;">
                                <div style="padding: 5px 7px; border-radius: 6px; background: var(--ppt-card-bg); border: 1px solid var(--ppt-card-border); border-top: 2px solid var(--ppt-accent); color: var(--ppt-card-text);">
                                    <div style="font-weight:800; color: var(--ppt-card-label); text-transform:uppercase; font-size:8px;">${escapeHtml(panel.leftTitle)}</div>
                                    ${panel.leftLines.map(l => `<div>${escapeHtml(l)}</div>`).join('')}
                                </div>
                                <div style="padding: 5px 7px; border-radius: 6px; background: var(--ppt-card-bg); border: 1px solid var(--ppt-card-border); border-top: 2px solid var(--ppt-accent); color: var(--ppt-card-text);">
                                    <div style="font-weight:800; color: var(--ppt-card-label); text-transform:uppercase; font-size:8px;">${escapeHtml(panel.rightTitle)}</div>
                                    ${panel.rightLines.map(l => `<div>${escapeHtml(l)}</div>`).join('')}
                                </div>
                            </div>
                            <div style="font-size:8.5px; opacity:0.75; display:flex; justify-content:space-between; padding-top:4px; border-top:1px solid var(--ppt-divider);">
                                <span style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:75%;">${escapeHtml(instLine)}</span>
                                <span>1 / ${pptSlides.length}</span>
                            </div>
                        </div>
                    `;
                } else {
                    const cleanBullets = (s.bullets || []).map(b => String(b).replace(/\*\*/g, '').trim()).filter(Boolean);
                    const bulletsHtml = cleanBullets.slice(0, 4).map(b => `<div style="background: var(--ppt-bullet-bg); border: 1px solid var(--ppt-bullet-border); border-left: 2.5px solid var(--ppt-accent); padding: 3px 6px; border-radius: 4px; font-size: 9.5px; line-height: 1.25; color: var(--ppt-card-text); overflow:hidden; text-overflow:ellipsis; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;">${escapeHtml(b)}</div>`).join('');
                    card.innerHTML = `
                        <div style="gap: 6px;">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <span class="ppt-badge" style="font-size:9px; padding:2px 8px;">${escapeHtml((s.section || 'Slide ' + (idx + 1)).replace(/\*\*/g, ''))}</span>
                                <span style="font-size:10px; font-weight:700; opacity:0.75;">#${idx + 1}</span>
                            </div>
                            <div style="font-size:13px; font-weight:800; line-height:1.25;">${escapeHtml((s.title || '').replace(/\*\*/g, ''))}</div>
                            <div style="display:grid; grid-template-columns: ${cleanBullets.length >= 4 ? '1fr 1fr' : '1fr'}; gap: 4px; flex:1; align-content:start;">${bulletsHtml}</div>
                            <div style="padding-top:4px; border-top:1px solid var(--ppt-divider); font-size:9.5px; opacity:0.75; display:flex; justify-content:space-between;">
                                <span>Klik untuk edit</span>
                                <span>${idx + 1} / ${pptSlides.length}</span>
                            </div>
                        </div>
                    `;
                }
                container.appendChild(card);
            });
        }

        function openFullscreenSlideshow() {
            syncCurrentSlideEdit();
            if (!pptSlides.length) return;
            const modal = document.getElementById('ppt-slideshow-modal');
            modal.style.display = 'flex';
            renderFullscreenSlide();
        }

        function closeFullscreenSlideshow() {
            const modal = document.getElementById('ppt-slideshow-modal');
            if (modal) modal.style.display = 'none';
        }

        function renderFullscreenSlide() {
            if (!pptSlides.length) return;
            const s = pptSlides[currentSlideIdx];
            const canvas = document.getElementById('fs-slide-canvas');
            canvas.className = 'ppt-canvas ppt-theme-' + currentPptTheme;
            canvas.classList.remove('ppt-slide-anim');
            void canvas.offsetWidth;
            canvas.classList.add('ppt-slide-anim');

            document.getElementById('fs-slide-counter').innerText = `Slide ${currentSlideIdx + 1} / ${pptSlides.length}`;
            const notesEl = document.getElementById('fs-speaker-notes');
            if (notesEl) {
                notesEl.innerText = s.speaker_notes ? ('Catatan: ' + s.speaker_notes) : 'Gunakan tombol panah ← / → untuk berpindah slide.';
            }

            renderPptSlideIntoDom({
                canvasEl: canvas,
                secEl: document.getElementById('fs-slide-section'),
                countEl: document.getElementById('fs-slide-num-tag'),
                campusBadgeEl: document.getElementById('fs-slide-campus-badge'),
                titleEl: document.getElementById('fs-slide-title'),
                subEl: document.getElementById('fs-slide-subtitle'),
                ulEl: document.getElementById('fs-slide-bullets'),
                s,
                idx: currentSlideIdx,
                total: pptSlides.length
            });
        }

        document.addEventListener('keydown', function (e) {
            const modal = document.getElementById('ppt-slideshow-modal');
            if (modal && modal.style.display === 'flex') {
                if (e.key === 'ArrowRight') { nextSlide(); renderFullscreenSlide(); }
                if (e.key === 'ArrowLeft') { prevSlide(); renderFullscreenSlide(); }
                if (e.key === 'Escape') { closeFullscreenSlideshow(); }
            }
        });

        function downloadPptxFile() {
            syncCurrentSlideEdit();
            if (!pptSlides.length) return;

            const pptx = new PptxGenJS();
            pptx.layout = 'LAYOUT_16x9';

            const palettes = {
                sidang_crimson: { bg: '9F1239', fg: 'FFFFFF', sub: 'FFE4E6', muted: 'FECDD3', bar: 'FB7185', accent2: 'BE123C', pillBg: '881337', pillFg: 'FFFFFF', cardBg: 'B91C4A', cardSub: 'FECDD3', cardFg: 'FFFFFF', cardBorder: 'FDA4AF', footerFg: 'FECDD3', style: 'topbar', fontTitle: 'Calibri' },
                royal_academic: { bg: 'F4F8FF', fg: '0F172A', sub: '1D4ED8', muted: '475569', bar: '2563EB', accent2: 'DBEAFE', pillBg: 'DBEAFE', pillFg: '1D4ED8', cardBg: 'FFFFFF', cardSub: '1D4ED8', cardFg: '0F172A', cardBorder: 'BFDBFE', footerFg: '64748B', style: 'sidebar', fontTitle: 'Calibri' },
                canva_editorial: { bg: 'FBF8F3', fg: '1C1917', sub: 'C2410C', muted: '57534E', bar: 'EA580C', accent2: 'FFEDD5', pillBg: 'FFEDD5', pillFg: '9A3412', cardBg: 'FFFFFF', cardSub: 'C2410C', cardFg: '292524', cardBorder: 'FED7AA', footerFg: '78716C', style: 'topbar', fontTitle: 'Georgia' },
                emerald_executive: { bg: 'F1FBF6', fg: '064E3B', sub: '047857', muted: '3F6258', bar: '059669', accent2: 'D1FAE5', pillBg: 'D1FAE5', pillFg: '065F46', cardBg: 'FFFFFF', cardSub: '047857', cardFg: '064E3B', cardBorder: 'A7F3D0', footerFg: '4B766A', style: 'topbar', fontTitle: 'Calibri' },
                maroon_prestige: { bg: 'FFF6F6', fg: '450A0A', sub: '991B1B', muted: '7F1D1D', bar: '991B1B', accent2: 'FEE2E2', pillBg: 'FEE2E2', pillFg: '991B1B', cardBg: 'FFFFFF', cardSub: '991B1B', cardFg: '450A0A', cardBorder: 'FECACA', footerFg: '991B1B', style: 'topbar', fontTitle: 'Georgia' },
                tech_dark: { bg: '0B1120', fg: 'F8FAFC', sub: '38BDF8', muted: '94A3B8', bar: '06B6D4', accent2: '172554', pillBg: '164E63', pillFg: '38BDF8', cardBg: '1E293B', cardSub: '38BDF8', cardFg: 'F8FAFC', cardBorder: '334155', footerFg: '94A3B8', style: 'topbar', fontTitle: 'Calibri' },
                swiss_minimal: { bg: 'FFFFFF', fg: '111827', sub: '374151', muted: '4B5563', bar: '111827', accent2: 'F3F4F6', pillBg: '111827', pillFg: 'FFFFFF', cardBg: 'F9FAFB', cardSub: '111827', cardFg: '111827', cardBorder: 'D1D5DB', footerFg: '6B7280', style: 'topbar', fontTitle: 'Arial' },
                basic_navy: { bg: '1A1842', fg: 'FFFFFF', sub: 'A5B4FC', muted: 'C7D2FE', bar: '6366F1', accent2: '312E81', pillBg: '312E81', pillFg: 'E0E7FF', cardBg: '26235C', cardSub: 'A5B4FC', cardFg: 'FFFFFF', cardBorder: '4F46E5', footerFg: 'A5B4FC', style: 'topbar', fontTitle: 'Calibri' },
                basic_white: { bg: 'FFFFFF', fg: '0F172A', sub: '334155', muted: '475569', bar: '334155', accent2: 'F1F5F9', pillBg: 'F1F5F9', pillFg: '1E293B', cardBg: 'F8FAFC', cardSub: '1E293B', cardFg: '0F172A', cardBorder: 'CBD5E1', footerFg: '64748B', style: 'topbar', fontTitle: 'Calibri' },
                pastel_lavender: { bg: 'F6F3FF', fg: '2E1065', sub: '6D28D9', muted: '5B21B6', bar: '7C3AED', accent2: 'EDE9FE', pillBg: 'EDE9FE', pillFg: '6D28D9', cardBg: 'FFFFFF', cardSub: '6D28D9', cardFg: '2E1065', cardBorder: 'DDD6FE', footerFg: '6D28D9', style: 'topbar', fontTitle: 'Calibri' },
                nordic_teal: { bg: 'F0FDFA', fg: '134E4A', sub: '0F766E', muted: '3B6966', bar: '0D9488', accent2: 'CCFBF1', pillBg: 'CCFBF1', pillFg: '0F766E', cardBg: 'FFFFFF', cardSub: '0F766E', cardFg: '134E4A', cardBorder: '99F6E4', footerFg: '0F766E', style: 'topbar', fontTitle: 'Calibri' },
                warm_terracotta: { bg: 'FFFBEB', fg: '451A03', sub: 'B45309', muted: '78350F', bar: 'D97706', accent2: 'FEF3C7', pillBg: 'FEF3C7', pillFg: 'B45309', cardBg: 'FFFFFF', cardSub: 'B45309', cardFg: '451A03', cardBorder: 'FDE68A', footerFg: '92400E', style: 'topbar', fontTitle: 'Calibri' },
                classic_gold: { bg: '0C1427', fg: 'F8FAFC', sub: 'FBBF24', muted: 'CBD5E1', bar: 'F59E0B', accent2: '1E293B', pillBg: '2D2414', pillFg: 'FBBF24', cardBg: '172036', cardSub: 'FBBF24', cardFg: 'F8FAFC', cardBorder: 'B45309', footerFg: 'CBD5E1', style: 'topbar', fontTitle: 'Georgia' }
            };
            const pal = palettes[currentPptTheme] || palettes.royal_academic;
            const leftPad = pal.style === 'sidebar' ? 0.65 : 0.55;
            const contentW = 10.0 - leftPad - 0.55;

            const rawLogo = uploadedPptLogoBase64 || (pptCoverMeta && pptCoverMeta.logo_base64) || (selectedPptProject && selectedPptProject.logo_base64) || '';
            let pptxLogo = '';
            if (rawLogo) {
                pptxLogo = rawLogo.trim();
                if (pptxLogo.startsWith('data:')) {
                    pptxLogo = pptxLogo.replace(/^data:/, '');
                } else if (!pptxLogo.startsWith('image/')) {
                    pptxLogo = 'image/png;base64,' + pptxLogo;
                }
            }

            const meta = (typeof pptCoverMeta !== 'undefined' && pptCoverMeta) ? pptCoverMeta : {};
            const instLine = buildPptInstitutionLine(meta);
            const panel = resolvePptPanelData(meta);
            const shortProjTitle = (selectedPptProject.title || 'Presentasi Sidang Skripsi').slice(0, 68);

            pptSlides.forEach((s, idx) => {
                const slide = pptx.addSlide();
                slide.background = { color: pal.bg };

                // Ornamen geometris & bar tema premium
                if (pal.style === 'sidebar') {
                    slide.addShape(pptx.ShapeType.rect, { x: 0, y: 0, w: 0.24, h: 5.625, fill: { color: pal.bar }, line: { color: pal.bar, width: 0 } });
                    slide.addShape(pptx.ShapeType.rect, { x: 0.24, y: 0, w: 0.06, h: 5.625, fill: { color: pal.accent2 }, line: { color: pal.accent2, width: 0 } });
                } else {
                    slide.addShape(pptx.ShapeType.rect, { x: 0, y: 0, w: 10.0, h: 0.13, fill: { color: pal.bar }, line: { color: pal.bar, width: 0 } });
                }
                // Dekorasi lembut sudut kanan bawah
                slide.addShape(pptx.ShapeType.ellipse, {
                    x: 8.65, y: 4.55, w: 1.9, h: 1.9,
                    fill: { color: pal.accent2, transparency: 55 },
                    line: { color: pal.accent2, transparency: 100 }
                });

                if (idx === 0) {
                    // Header Bar Cover: Kiri = Pill Kampus + Logo Tunggal (NO dobel logo), Kanan = Pill SIDANG SKRIPSI
                    slide.addShape(pptx.ShapeType.roundRect, {
                        x: leftPad, y: 0.30, w: 3.7, h: 0.44,
                        fill: { color: pal.cardBg }, line: { color: pal.cardBorder, width: 1 }, rectRadius: 0.22
                    });

                    if (pptxLogo) {
                        try {
                            slide.addImage({ data: pptxLogo, x: leftPad + 0.12, y: 0.34, w: 0.36, h: 0.36, sizing: { type: 'contain', w: 0.36, h: 0.36 } });
                            slide.addText((meta.kampus || 'UNIVERSITAS').toUpperCase(), {
                                x: leftPad + 0.56, y: 0.30, w: 3.05, h: 0.44,
                                fontSize: 9.5, bold: true, fontFace: 'Calibri', color: pal.cardSub, valign: 'middle', fit: 'shrink'
                            });
                        } catch (e) {
                            slide.addText((meta.kampus || 'UNIVERSITAS').toUpperCase(), {
                                x: leftPad + 0.16, y: 0.30, w: 3.4, h: 0.44,
                                fontSize: 9.5, bold: true, fontFace: 'Calibri', color: pal.cardSub, valign: 'middle', fit: 'shrink'
                            });
                        }
                    } else {
                        slide.addText((meta.kampus || 'UNIVERSITAS').toUpperCase(), {
                            x: leftPad + 0.16, y: 0.30, w: 3.4, h: 0.44,
                            fontSize: 9.5, bold: true, fontFace: 'Calibri', color: pal.cardSub, valign: 'middle', fit: 'shrink'
                        });
                    }

                    slide.addShape(pptx.ShapeType.roundRect, {
                        x: 7.45, y: 0.32, w: 2.0, h: 0.38,
                        fill: { color: pal.pillBg }, line: { color: pal.cardBorder, width: 0.75 }, rectRadius: 0.19
                    });
                    slide.addText('SIDANG SKRIPSI', {
                        x: 7.45, y: 0.32, w: 2.0, h: 0.38,
                        fontSize: 9.5, bold: true, fontFace: 'Calibri', color: pal.pillFg, align: 'center', valign: 'middle'
                    });

                    // Judul Skripsi Prominen (Auto-fit shrink, tidak pernah overflow)
                    const cleanTitle = (s.title || selectedPptProject.title || 'Judul Skripsi').replace(/\*\*/g, '').toUpperCase();
                    const titleSize = cleanTitle.length > 95 ? 14.5 : (cleanTitle.length > 65 ? 16.5 : 19);
                    slide.addText(cleanTitle, {
                        x: leftPad + 0.15, y: 0.94, w: contentW - 0.3, h: 1.24,
                        fontSize: titleSize, bold: true, fontFace: pal.fontTitle, color: pal.fg,
                        align: 'center', valign: 'middle', fit: 'shrink'
                    });

                    // Garis Aksen Dekoratif Tengah
                    slide.addShape(pptx.ShapeType.roundRect, {
                        x: 4.5, y: 2.24, w: 1.0, h: 0.055,
                        fill: { color: pal.bar }, line: { color: pal.bar, width: 0 }, rectRadius: 0.03
                    });

                    // Pill Identitas Mahasiswa (Disusun Oleh + Nama + NIM)
                    slide.addShape(pptx.ShapeType.roundRect, {
                        x: 2.6, y: 2.40, w: 4.8, h: 0.80,
                        fill: { color: pal.cardBg }, line: { color: pal.cardBorder, width: 1 }, rectRadius: 0.10
                    });
                    const authorLines = [
                        { text: 'DISUSUN OLEH\n', options: { fontSize: 8.5, bold: true, color: pal.cardSub } },
                        { text: (meta.nama || 'Nama Mahasiswa').toUpperCase() + (meta.nim ? '\n' : ''), options: { fontSize: 12.5, bold: true, color: pal.cardFg } }
                    ];
                    if (meta.nim) {
                        authorLines.push({ text: 'NIM / NPM: ' + meta.nim, options: { fontSize: 10, bold: false, color: pal.muted } });
                    }
                    slide.addText(authorLines, {
                        x: 2.7, y: 2.42, w: 4.6, h: 0.76,
                        fontFace: 'Calibri', align: 'center', valign: 'middle', fit: 'shrink'
                    });

                    // 2 KARTU SIMETRIS SEJAJAR (Dosen Pembimbing | Dosen Penguji) — Rapi, Rata Kiri, Top Accent Bar
                    const cardGap = 0.32;
                    const cardW = (contentW - cardGap) / 2;
                    const cardY = 3.40;
                    const cardH = 1.38;
                    const leftX = leftPad;
                    const rightX = leftPad + cardW + cardGap;

                    [
                        { x: leftX, title: panel.leftTitle, lines: panel.leftLines },
                        { x: rightX, title: panel.rightTitle, lines: panel.rightLines }
                    ].forEach((col) => {
                        slide.addShape(pptx.ShapeType.roundRect, {
                            x: col.x, y: cardY, w: cardW, h: cardH,
                            fill: { color: pal.cardBg }, line: { color: pal.cardBorder, width: 1.1 }, rectRadius: 0.08
                        });
                        // Top Accent Bar pada kartu
                        slide.addShape(pptx.ShapeType.rect, {
                            x: col.x + 0.08, y: cardY, w: cardW - 0.16, h: 0.06,
                            fill: { color: pal.bar }, line: { color: pal.bar, width: 0 }
                        });
                        const textRuns = [
                            { text: col.title.toUpperCase() + '\n', options: { fontSize: 9, bold: true, color: pal.cardSub, breakLine: false } }
                        ];
                        col.lines.forEach((ln, li) => {
                            textRuns.push({
                                text: ln + (li < col.lines.length - 1 ? '\n' : ''),
                                options: { fontSize: 11, bold: true, color: pal.cardFg }
                            });
                        });
                        slide.addText(textRuns, {
                            x: col.x + 0.20, y: cardY + 0.14, w: cardW - 0.40, h: cardH - 0.22,
                            fontFace: 'Calibri', align: 'left', valign: 'middle', lineSpacing: 17, fit: 'shrink'
                        });
                    });

                    // Footer Institusi Bawah Cover (Seimbang, tanpa ruang kosong menganga)
                    slide.addShape(pptx.ShapeType.line, {
                        x: leftPad, y: 4.96, w: contentW, h: 0,
                        line: { color: pal.cardBorder, width: 0.8 }
                    });
                    slide.addText(instLine, {
                        x: leftPad, y: 5.02, w: contentW, h: 0.35,
                        fontSize: 9.5, bold: true, fontFace: 'Calibri', color: pal.footerFg, align: 'center', valign: 'middle', fit: 'shrink'
                    });
                } else {
                    // SLIDE 2..N: DESAIN KARTU MODERN ADAPTIF — DIJAMIN 0% TERPOTONG DI POWERPOINT!
                    const cleanSection = (s.section || `Slide ${idx + 1}`).replace(/\*\*/g, '').toUpperCase();
                    const pillW = Math.min(3.8, Math.max(1.8, cleanSection.length * 0.105 + 0.55));
                    slide.addShape(pptx.ShapeType.roundRect, {
                        x: leftPad, y: 0.28, w: pillW, h: 0.34,
                        fill: { color: pal.pillBg }, line: { color: pal.cardBorder, width: 0.75 }, rectRadius: 0.17
                    });
                    slide.addText(cleanSection, {
                        x: leftPad + 0.10, y: 0.28, w: pillW - 0.20, h: 0.34,
                        fontSize: 9.5, bold: true, fontFace: 'Calibri', color: pal.pillFg, align: 'center', valign: 'middle', fit: 'shrink'
                    });

                    // Slide number badge top-right
                    slide.addText(`${idx + 1} / ${pptSlides.length}`, {
                        x: 8.65, y: 0.28, w: 0.85, h: 0.34,
                        fontSize: 10, bold: true, fontFace: 'Calibri', color: pal.footerFg, align: 'right', valign: 'middle'
                    });

                    // Judul Slide (Locked max height 0.62", auto-shrink)
                    const cleanTitle = (s.title || '').replace(/\*\*/g, '');
                    const slideTitleSize = cleanTitle.length > 52 ? 17.5 : 20.5;
                    slide.addText(cleanTitle, {
                        x: leftPad, y: 0.70, w: contentW, h: 0.60,
                        fontSize: slideTitleSize, bold: true, fontFace: pal.fontTitle, color: pal.fg,
                        valign: 'middle', fit: 'shrink'
                    });

                    // Subjudul (Locked max height 0.30")
                    const cleanSub = (s.subtitle || '').replace(/\*\*/g, '');
                    if (cleanSub) {
                        slide.addText(cleanSub, {
                            x: leftPad, y: 1.32, w: contentW, h: 0.28,
                            fontSize: 11, italic: false, fontFace: 'Calibri', color: pal.sub,
                            valign: 'middle', fit: 'shrink'
                        });
                    }

                    // Garis tipis pemisah header & isi
                    slide.addShape(pptx.ShapeType.line, {
                        x: leftPad, y: 1.66, w: contentW, h: 0,
                        line: { color: pal.cardBorder, width: 0.9 }
                    });

                    // Area konten aman: y = 1.78 s/d 5.02 (tinggi 3.24 inci) -> dijamin TIDAK PERNAH menyentuh footer (5.18)
                    const cleanBullets = (s.bullets || []).map(b => String(b).replace(/\*\*/g, '').trim()).filter(Boolean);
                    const totalChars = cleanBullets.reduce((acc, b) => acc + b.length, 0);
                    const yStart = 1.78;
                    const availH = 3.24;

                    if (cleanBullets.length > 0) {
                        const useTwoCols = cleanBullets.length >= 4 || totalChars > 300;

                        if (useTwoCols) {
                            const colGap = 0.24;
                            const colW = (contentW - colGap) / 2;
                            const numRows = Math.ceil(cleanBullets.length / 2);
                            const rowGap = 0.16;
                            const cardH = Math.min(1.48, (availH - rowGap * (numRows - 1)) / numRows);
                            const maxCharInCell = Math.max(...cleanBullets.map(b => b.length));
                            const dynFont = numRows >= 3 ? (maxCharInCell > 110 ? 9.5 : 10.5) : (maxCharInCell > 130 ? 10.5 : 11.5);

                            cleanBullets.forEach((bText, bIdx) => {
                                const r = Math.floor(bIdx / 2);
                                const c = bIdx % 2;
                                const isLastOdd = (bIdx === cleanBullets.length - 1) && (cleanBullets.length % 2 === 1);
                                const cx = leftPad + (isLastOdd ? 0 : c * (colW + colGap));
                                const cw = isLastOdd ? contentW : colW;
                                const cy = yStart + r * (cardH + rowGap);

                                // Kartu rounded modern
                                slide.addShape(pptx.ShapeType.roundRect, {
                                    x: cx, y: cy, w: cw, h: cardH,
                                    fill: { color: pal.cardBg }, line: { color: pal.cardBorder, width: 1 }, rectRadius: 0.08
                                });
                                // Left Accent Bar
                                slide.addShape(pptx.ShapeType.rect, {
                                    x: cx, y: cy + 0.08, w: 0.07, h: cardH - 0.16,
                                    fill: { color: pal.bar }, line: { color: pal.bar, width: 0 }
                                });
                                // Badge nomor bulat
                                const badgeS = 0.28;
                                slide.addShape(pptx.ShapeType.ellipse, {
                                    x: cx + 0.16, y: cy + (cardH - badgeS) / 2, w: badgeS, h: badgeS,
                                    fill: { color: pal.pillBg }, line: { color: pal.bar, width: 0.8 }
                                });
                                slide.addText(String(bIdx + 1), {
                                    x: cx + 0.16, y: cy + (cardH - badgeS) / 2, w: badgeS, h: badgeS,
                                    fontSize: 9, bold: true, fontFace: 'Calibri', color: pal.pillFg, align: 'center', valign: 'middle'
                                });
                                // Teks isi poin (auto-shrink agar tidak pernah terpotong!)
                                slide.addText(bText, {
                                    x: cx + 0.52, y: cy + 0.08, w: cw - 0.64, h: cardH - 0.16,
                                    fontSize: dynFont, fontFace: 'Calibri', color: pal.cardFg,
                                    align: 'left', valign: 'middle', fit: 'shrink'
                                });
                            });
                        } else {
                            // 1–3 Poin: Kartu Stacked Full-Width yang Luas & Elegan
                            const rowGap = 0.18;
                            const cardH = Math.min(0.98, (availH - rowGap * (cleanBullets.length - 1)) / cleanBullets.length);
                            const maxChar = Math.max(...cleanBullets.map(b => b.length));
                            const dynFont = maxChar > 130 ? 11.5 : 12.5;

                            cleanBullets.forEach((bText, bIdx) => {
                                const cy = yStart + bIdx * (cardH + rowGap);
                                slide.addShape(pptx.ShapeType.roundRect, {
                                    x: leftPad, y: cy, w: contentW, h: cardH,
                                    fill: { color: pal.cardBg }, line: { color: pal.cardBorder, width: 1 }, rectRadius: 0.08
                                });
                                slide.addShape(pptx.ShapeType.rect, {
                                    x: leftPad, y: cy + 0.08, w: 0.08, h: cardH - 0.16,
                                    fill: { color: pal.bar }, line: { color: pal.bar, width: 0 }
                                });
                                const badgeS = 0.34;
                                slide.addShape(pptx.ShapeType.ellipse, {
                                    x: leftPad + 0.20, y: cy + (cardH - badgeS) / 2, w: badgeS, h: badgeS,
                                    fill: { color: pal.pillBg }, line: { color: pal.bar, width: 0.9 }
                                });
                                slide.addText(String(bIdx + 1), {
                                    x: leftPad + 0.20, y: cy + (cardH - badgeS) / 2, w: badgeS, h: badgeS,
                                    fontSize: 10.5, bold: true, fontFace: 'Calibri', color: pal.pillFg, align: 'center', valign: 'middle'
                                });
                                slide.addText(bText, {
                                    x: leftPad + 0.66, y: cy + 0.08, w: contentW - 0.82, h: cardH - 0.16,
                                    fontSize: dynFont, fontFace: 'Calibri', color: pal.cardFg,
                                    align: 'left', valign: 'middle', fit: 'shrink'
                                });
                            });
                        }
                    }

                    // Footer Rapi di Setiap Slide Konten
                    slide.addShape(pptx.ShapeType.line, {
                        x: leftPad, y: 5.14, w: contentW, h: 0,
                        line: { color: pal.cardBorder, width: 0.6 }
                    });
                    slide.addText(`${(meta.kampus || 'UNIVERSITAS').toUpperCase()}  ·  ${shortProjTitle}`, {
                        x: leftPad, y: 5.18, w: contentW - 1.2, h: 0.32,
                        fontSize: 8.5, fontFace: 'Calibri', color: pal.footerFg, align: 'left', valign: 'middle', fit: 'shrink'
                    });
                    slide.addText(`Slide ${idx + 1} / ${pptSlides.length}`, {
                        x: 8.35, y: 5.18, w: 1.15, h: 0.32,
                        fontSize: 8.5, bold: true, fontFace: 'Calibri', color: pal.footerFg, align: 'right', valign: 'middle'
                    });
                }

                if (s.speaker_notes) {
                    slide.addNotes(s.speaker_notes);
                }
            });

            const safeSlug = (selectedPptProject.title || 'SkripsiLab_AI').replace(/[^a-zA-Z0-9]+/g, '_').replace(/^_|_$/g, '').slice(0, 32) || 'SkripsiLab_AI';
            pptx.writeFile({ fileName: `Slide_Sidang_${safeSlug}.pptx` });
        }

        // =====================================================================
        // 3. SIMULASI SIDANG (ANIMASI LOADING PERTANYAAN & EVALUASI + ULANGI)
        // =====================================================================
        let defenseTitle = '';
        let defenseProjectId = null;
        let defenseDocContext = '';
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
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function restartCurrentDefense() {
            if (!defenseQuestions.length) {
                setDefenseStep(1);
                return;
            }
            defenseAnswers = [];
            defCurrentIdx = 0;
            renderDefenseQuestion();
            setDefenseStep(2);
        }

        async function handlePedomanFileSelect(input) {
            const statusEl = document.getElementById('pedoman-extract-status');
            const hiddenEl = document.getElementById('input-pedoman-extracted');
            if (hiddenEl) hiddenEl.value = '';
            if (!input || !input.files || !input.files[0]) {
                if (statusEl) {
                    statusEl.style.color = 'var(--text-muted)';
                    statusEl.textContent = 'Pilih berkas buku pedoman penulisan kampus (.pdf / .docx / .txt, maks 3,5 MB — PDF teks otomatis dibaca langsung di browser agar akurat 100%).';
                }
                return;
            }
            const file = input.files[0];
            const ext = (file.name.split('.').pop() || '').toLowerCase();
            if (ext !== 'pdf') {
                if (statusEl) {
                    statusEl.style.color = '#047857';
                    statusEl.textContent = `Berkas "${file.name}" siap diproses otomatis saat proyek dibuka.`;
                }
                return;
            }
            if (statusEl) {
                statusEl.style.color = '#1d4ed8';
                statusEl.textContent = `Membaca isi pedoman PDF "${file.name}" di browser...`;
            }
            try {
                if (typeof window.pdfjsLib === 'undefined') {
                    await new Promise((resolve, reject) => {
                        const s = document.createElement('script');
                        s.src = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js';
                        s.onload = resolve;
                        s.onerror = reject;
                        document.head.appendChild(s);
                    });
                    if (window.pdfjsLib && window.pdfjsLib.GlobalWorkerOptions) {
                        window.pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
                    }
                }
                const buf = await file.arrayBuffer();
                const pdf = await window.pdfjsLib.getDocument({ data: buf }).promise;
                const totalPages = pdf.numPages || 0;
                const maxRead = Math.min(totalPages, 60);
                const scoredPages = [];
                const ruleRegex = /(?:sistematika|susunan\s+bab|bab\s+[ivx1-5]|margin|batas\s+tepi|spasi|times\s+new\s+roman|sitasi|daftar\s+pustaka|abstrak|latar\s+belakang|rumusan\s+masalah|tinjauan\s+pustaka|metodologi|penomoran)/gi;
                for (let p = 1; p <= maxRead; p++) {
                    const page = await pdf.getPage(p);
                    const tc = await page.getTextContent();
                    const txt = tc.items.map(it => it.str || '').join(' ').replace(/\s+/g, ' ').trim();
                    if (txt.length < 30) continue;
                    const hits = (txt.match(ruleRegex) || []).length;
                    scoredPages.push({ page: p, hits, txt });
                }
                if (!scoredPages.length) {
                    if (statusEl) {
                        statusEl.style.color = '#b45309';
                        statusEl.textContent = `PDF "${file.name}" tampak berupa hasil scan gambar (teks tidak bisa diseleksi). Disarankan unggah PDF teks asli atau .docx, atau aktifkan "Struktur bab kustom" di bawah.`;
                    }
                    return;
                }
                // Prioritaskan halaman yang memuat aturan penulisan/sistematika Bab I-V (bukan cover/kata pengantar pedoman)
                const ranked = [...scoredPages].sort((a, b) => b.hits - a.hits || a.page - b.page);
                let combined = `[Pedoman Kampus: ${file.name} (${totalPages} hal)]\n`;
                const picked = ranked.slice(0, 14).sort((a, b) => a.page - b.page);
                for (const item of picked) {
                    if (combined.length > 10500) break;
                    combined += `\n[Hal. ${item.page}] ${item.txt}\n`;
                }
                if (hiddenEl) hiddenEl.value = combined.slice(0, 11000);
                if (statusEl) {
                    statusEl.style.color = '#047857';
                    statusEl.textContent = `Pedoman PDF berhasil dibaca (${totalPages} halaman, ${picked.length} halaman aturan inti terdeteksi) — akan langsung dipatuhi saat menulis Bab I–V.`;
                }
            } catch (e) {
                if (statusEl) {
                    statusEl.style.color = '#047857';
                    statusEl.textContent = `Berkas PDF "${file.name}" terpilih — akan diekstrak oleh server saat proyek dibuka.`;
                }
            }
        }

        function updateTargetStyle(radio) {
            const isProp = radio.value.includes('Proposal');
            const labelProp = document.getElementById('label-target-proposal');
            const labelFull = document.getElementById('label-target-full');
            const titleProp = document.getElementById('title-target-proposal');
            const titleFull = document.getElementById('title-target-full');
            const badgeProp = document.getElementById('badge-target-proposal');
            const badgeFull = document.getElementById('badge-target-full');
            if (!labelProp || !labelFull) return;

            if (isProp) {
                labelProp.style.borderColor = 'var(--primary)';
                labelProp.style.background = '#f8fbff';
                if (titleProp) titleProp.style.color = 'var(--primary)';
                if (badgeProp) {
                    badgeProp.style.background = '#dbeafe';
                    badgeProp.style.color = '#1d4ed8';
                }

                labelFull.style.borderColor = 'var(--border)';
                labelFull.style.background = '#fff';
                if (titleFull) titleFull.style.color = 'var(--text-main)';
                if (badgeFull) {
                    badgeFull.style.background = '#f1f5f9';
                    badgeFull.style.color = '#475569';
                }
            } else {
                labelFull.style.borderColor = 'var(--primary)';
                labelFull.style.background = '#f8fbff';
                if (titleFull) titleFull.style.color = 'var(--primary)';
                if (badgeFull) {
                    badgeFull.style.background = '#dbeafe';
                    badgeFull.style.color = '#1d4ed8';
                }

                labelProp.style.borderColor = 'var(--border)';
                labelProp.style.background = '#fff';
                if (titleProp) titleProp.style.color = 'var(--text-main)';
                if (badgeProp) {
                    badgeProp.style.background = '#f1f5f9';
                    badgeProp.style.color = '#475569';
                }
            }
        }

        async function startDefenseWithProject(projectId, title) {
            defenseTitle = title;
            defenseProjectId = projectId;
            defenseDocContext = '';
            const modeDosen = document.getElementById('def-mode-dosen')?.value || 'kritis';
            const jumlahSoal = document.getElementById('def-jumlah-soal')?.value || '5';
            const minScore = document.getElementById('def-min-score')?.value || '7.0';

            const formData = new FormData();
            formData.append('mode', 'generate_questions');
            formData.append('project_id', projectId);
            formData.append('judul', title);
            formData.append('mode_dosen', modeDosen);
            formData.append('jumlah_soal', jumlahSoal);
            formData.append('min_score', minScore);
            formData.append('model', getSelectedModel());
            await requestDefenseQuestions(formData, title);
        }

        async function startDefenseWithFile() {
            const fileInput = document.getElementById('def-file-input');
            if (!fileInput.files.length) {
                alert('Pilih file skripsi (.pdf / .docx) terlebih dahulu.');
                return;
            }
            if (fileInput.files[0].size > 3.5 * 1024 * 1024) {
                alert('Ukuran file skripsi melebihi batas 3,5 MB serverless. Silakan unggah file Word/PDF yang lebih ringkas.');
                return;
            }
            defenseProjectId = null;
            defenseDocContext = '';
            const btn = document.getElementById('btn-start-def-file');
            btn.disabled = true;
            btn.innerHTML = '<span class="ai-spinner"></span><span>Membuat Pertanyaan...</span>';

            const modeDosen = document.getElementById('def-mode-dosen')?.value || 'kritis';
            const jumlahSoal = document.getElementById('def-jumlah-soal')?.value || '5';
            const minScore = document.getElementById('def-min-score')?.value || '7.0';

            const formData = new FormData();
            formData.append('mode', 'generate_questions');
            formData.append('file_skripsi', fileInput.files[0]);
            formData.append('mode_dosen', modeDosen);
            formData.append('jumlah_soal', jumlahSoal);
            formData.append('min_score', minScore);
            formData.append('model', getSelectedModel());

            await requestDefenseQuestions(formData, fileInput.files[0].name);
            btn.disabled = false;
            btn.innerText = 'Mulai Simulasi →';
        }

        async function requestDefenseQuestions(formData, labelTitle) {
            const jml = formData.get('jumlah_soal') || 5;
            renderLoadingCard('defense-loading-box', `Dosen Penguji AI sedang membaca naskah & menyiapkan ${jml} pertanyaan sidang...`, `Menganalisis isi Bab I–V dari "${escapeHtml(labelTitle || 'Skripsi')}" untuk menyusun pertanyaan penguji riil...`);
            window.scrollTo({ top: 0, behavior: 'smooth' });

            try {
                const res = await fetch("{{ route('tools.defense') }}", {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    body: formData
                });
                const data = await res.json();
                if (data.status === 'success' && Array.isArray(data.questions)) {
                    defenseTitle = data.title || defenseTitle;
                    defenseProjectId = data.project_id || defenseProjectId;
                    defenseDocContext = data.doc_context || '';
                    defenseQuestions = data.questions;
                    defenseAnswers = [];
                    defCurrentIdx = 0;

                    const statusPill = document.getElementById('def-doc-status-pill');
                    if (statusPill) {
                        const wc = data.word_count ? ` (~${Number(data.word_count).toLocaleString('id-ID')} kata terbaca lintas Bab I–V)` : '';
                        statusPill.textContent = `✓ Naskah skripsi riil berhasil dibaca: "${defenseTitle}"${wc} — Pertanyaan & evaluasi mengacu langsung pada isi skripsimu.`;
                        statusPill.style.display = 'block';
                    }

                    renderDefenseQuestion();
                    setDefenseStep(2);
                } else {
                    alert(data.message || 'Gagal menyiapkan pertanyaan sidang.');
                }
            } catch (e) {
                alert('Terjadi kesalahan saat memulai simulasi sidang.');
            } finally {
                hideLoadingCard('defense-loading-box');
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

            // Semua pertanyaan selesai -> Evaluasi Akhir dengan animasi loading jelas
            const btn = document.getElementById('btn-next-def-q');
            btn.disabled = true;
            btn.innerHTML = '<span class="ai-spinner"></span><span>Menilai Jawaban Sidang...</span>';
            renderLoadingCard('defense-eval-loading-box', 'Dosen Penguji AI sedang mengevaluasi seluruh jawabanmu...', 'Menghitung skor per pertanyaan, memberikan catatan penguji, dan menyusun saran jawaban terbaik...');

            const modeDosen = document.getElementById('def-mode-dosen')?.value || 'kritis';
            const minScore = document.getElementById('def-min-score')?.value || '7.0';

            try {
                const res = await fetch("{{ route('tools.defense') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({
                        mode: 'evaluate_all',
                        project_id: defenseProjectId,
                        doc_context: defenseDocContext,
                        judul: defenseTitle,
                        qa_list: defenseAnswers,
                        mode_dosen: modeDosen,
                        min_score: minScore,
                        model: getSelectedModel()
                    })
                });
                const data = await res.json();
                if (data.status === 'success' && data.evaluation) {
                    renderDefenseEvaluation(data.evaluation);
                    setDefenseStep(3);
                } else {
                    alert(data.message || 'Gagal mengevaluasi jawaban.');
                }
            } catch (e) {
                alert('Gagal mengevaluasi sesi sidang.');
            } finally {
                hideLoadingCard('defense-eval-loading-box');
                btn.disabled = false;
                btn.innerText = 'Selesai & Lihat Skor Evaluasi →';
            }
        }

        function renderDefenseEvaluation(ev) {
            const items = ev.items || [];
            let itemScores = items.map(it => parseFloat(it.score)).filter(s => !isNaN(s));
            let overall = (itemScores.length > 0)
                ? (itemScores.reduce((a, b) => a + b, 0) / itemScores.length).toFixed(1)
                : ((ev.overall_score !== undefined && ev.overall_score !== null) ? Number(ev.overall_score).toFixed(1) : '3.0');

            document.getElementById('def-eval-verdict').innerText = ev.verdict || 'Evaluasi Selesai';
            document.getElementById('def-eval-score').innerText = overall + '/10';
            document.getElementById('def-eval-summary').innerText = ev.summary || '';

            const gradeEl = document.getElementById('def-eval-grade-badge');
            const minLbl = document.getElementById('def-eval-min-label');
            const isPass = !String(ev.verdict || '').toLowerCase().includes('tidak lulus');
            if (gradeEl) {
                gradeEl.innerText = ev.grade || (parseFloat(overall) >= 8.5 ? 'A' : parseFloat(overall) >= 7.5 ? 'B+' : parseFloat(overall) >= 6.5 ? 'B' : 'C');
                gradeEl.style.background = isPass ? '#dcfce7' : '#fee2e2';
                gradeEl.style.color = isPass ? '#15803d' : '#dc2626';
            }
            if (minLbl && ev.min_score) {
                minLbl.innerText = `Rata-rata Skor (Min. Lulus ${Number(ev.min_score).toFixed(1)})`;
            }

            const bars = document.getElementById('def-score-bars');
            bars.innerHTML = '';
            const details = document.getElementById('def-details-list');
            details.innerHTML = '';

            (ev.items || []).forEach((item, i) => {
                const sc = (item.score !== undefined && item.score !== null) ? parseFloat(item.score) : 2.5;
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
                        <div style="font-size:14px; font-weight:600; margin-bottom:8px;">${escapeHtml(item.question || '')}</div>
                        <div style="font-size:13px; color:var(--text-secondary); margin-bottom:10px;"><strong>Jawabanmu:</strong> ${escapeHtml(item.student_answer || '')}</div>
                        <div style="font-size:13px; color:var(--text-main); margin-bottom:10px;"><strong>Evaluasi Penguji:</strong> ${escapeHtml(item.feedback || '')}</div>
                        <div style="padding:10px 12px; background:var(--bg-subtle); border-radius:8px; font-size:13px; color:var(--text-main); line-height:1.55;">
                            <strong>Saran Jawaban Terbaik (Sesuai Isi Skripsimu):</strong><br>${escapeHtml(item.better_answer || '')}
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
        // 4. CARI JURNAL (INDONESIA + INTERNASIONAL + ANIMASI LOADING)
        // =====================================================================
        let lastJournals = [];

        async function handleFindJournals() {
            const keyword = document.getElementById('journal-keyword').value.trim();
            const scopeEl = document.getElementById('journal-scope');
            const scope = scopeEl ? scopeEl.value : 'all';

            if (!keyword) {
                alert('Masukkan kata kunci atau topik penelitian.');
                return;
            }

            const btn = document.getElementById('btn-find-journals');
            btn.disabled = true;
            btn.innerHTML = '<span class="ai-spinner"></span><span>Mencari Jurnal...</span>';
            document.getElementById('journals-result-box').style.display = 'none';
            renderLoadingCard('journals-loading-box', 'Sedang menelusuri database OpenAlex & Scholar...', 'Mengumpulkan artikel jurnal ilmiah Indonesia (Sinta) & Internasional beserta tautan PDF asli...');

            try {
                const res = await fetch("{{ route('tools.journals') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ keyword, scope, model: getSelectedModel() })
                });
                const data = await res.json();
                if (data.status === 'success') {
                    lastJournals = data.data || [];
                    renderJournalsList('all');
                    document.getElementById('journals-result-box').style.display = 'block';
                } else {
                    alert(data.message || 'Gagal mencari jurnal.');
                }
            } catch (e) {
                alert('Terjadi kesalahan saat mencari jurnal.');
            } finally {
                hideLoadingCard('journals-loading-box');
                btn.disabled = false;
                btn.innerText = 'Cari Jurnal →';
            }
        }

        function filterJournalResults(scopeFilter, btn) {
            document.querySelectorAll('.journal-filter-btn').forEach(b => {
                b.style.background = '#fff';
                b.style.color = 'var(--text-secondary)';
                b.style.borderColor = 'var(--border)';
            });
            if (btn) {
                btn.style.background = 'var(--primary-soft)';
                btn.style.color = 'var(--primary)';
                btn.style.borderColor = '#bfdbfe';
            }
            renderJournalsList(scopeFilter);
        }

        function renderJournalsList(scopeFilter) {
            const list = document.getElementById('journals-list');
            list.innerHTML = '';
            const items = (scopeFilter && scopeFilter !== 'all')
                ? lastJournals.filter(j => (j.scope || '').toLowerCase() === scopeFilter.toLowerCase())
                : lastJournals;

            if (!items.length) {
                list.innerHTML = '<div style="padding:24px; text-align:center; color:var(--text-muted); font-size:13.5px;">Tidak ada hasil pada kategori ini.</div>';
                return;
            }

            items.forEach((j, idx) => {
                const row = document.createElement('div');
                row.className = 'project-row';
                row.style.cursor = 'default';
                row.style.alignItems = 'flex-start';
                const directUrl = j.doi_url || j.pdf_url || '#';
                const pdfUrl = j.pdf_url || directUrl;
                const isIndo = (j.scope || '').toLowerCase().includes('indo');
                const scopeBadge = isIndo
                    ? '<span class="scope-badge-id">Indonesia · SINTA/Nasional</span>'
                    : '<span class="scope-badge-int">Internasional · Global/Scopus</span>';
                const citationStr = j.citation || `${j.author} (${j.year}). ${j.title}. ${j.journal}. ${directUrl}`;

                row.innerHTML = `
                    <div class="project-row-left" style="align-items:flex-start;">
                        <div class="project-initial" style="margin-top:2px;">${idx + 1}</div>
                        <div style="min-width:0; flex:1;">
                            <div style="margin-bottom:4px;">${scopeBadge}</div>
                            <div class="project-row-title" style="font-size:14px; line-height:1.45; font-weight:600; color:var(--text-main);">${escapeHtml(j.title || '')}</div>
                            <div class="project-row-meta" style="margin-top:4px;">${escapeHtml(j.author || '')} · <strong>${escapeHtml(j.journal || '')}</strong> (${escapeHtml(j.year || '')})</div>
                            <div style="margin-top:6px; font-size:12px; word-break:break-all;">
                                <a href="${escapeHtml(directUrl)}" target="_blank" rel="noopener" style="color:var(--primary); text-decoration:underline;">${escapeHtml(directUrl)}</a>
                            </div>
                        </div>
                    </div>
                    <div class="project-row-action" style="display:flex; flex-wrap:wrap; gap:6px; align-items:center;">
                        <button type="button" class="btn-outline btn-save-proj-ref" style="padding:6px 10px; font-size:11.5px; white-space:nowrap; border-radius:8px; display:inline-flex; align-items:center; gap:4px; border-color:#bfdbfe; color:#1d4ed8; background:#eff6ff;">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                            <span>Simpan ke Proyek</span>
                        </button>
                        <button type="button" class="btn-outline btn-copy-cite" style="padding:6px 10px; font-size:11.5px; white-space:nowrap; border-radius:8px;">Salin Sitasi</button>
                        <a href="${escapeHtml(directUrl)}" target="_blank" rel="noopener" class="btn-outline" style="padding:6px 11px; font-size:11.5px; white-space:nowrap; border-radius:8px; display:inline-flex; align-items:center; gap:5px; text-decoration:none;">
                            <span>Buka Jurnal</span>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                        </a>
                        <a href="${escapeHtml(pdfUrl)}" target="_blank" rel="noopener" class="btn-primary" style="padding:6px 11px; font-size:11.5px; white-space:nowrap; border-radius:8px; display:inline-flex; align-items:center; gap:5px; text-decoration:none;">
                            <span>Unduh PDF</span>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        </a>
                    </div>
                `;
                const saveBtn = row.querySelector('.btn-save-proj-ref');
                if (saveBtn) {
                    const targetSel = document.getElementById('journal-target-project');
                    if (!targetSel || !targetSel.value) {
                        saveBtn.style.display = 'none';
                    } else {
                        saveBtn.addEventListener('click', async () => {
                            const targetProjId = targetSel.value;
                            const orig = saveBtn.innerHTML;
                            saveBtn.disabled = true;
                            saveBtn.textContent = 'Menyimpan...';
                            try {
                                const res = await fetch(`/project/${targetProjId}/references/add`, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': csrfToken,
                                        'Accept': 'application/json'
                                    },
                                    body: JSON.stringify({
                                        title: j.title || '',
                                        author: j.author || '',
                                        year: j.year || '',
                                        journal: j.journal || '',
                                        doi_url: directUrl !== '#' ? directUrl : '',
                                        scope: j.scope || 'Internasional'
                                    })
                                });
                                const data = await res.json();
                                if (!res.ok || data.status !== 'success') {
                                    throw new Error(data.message || 'Gagal menyimpan jurnal.');
                                }
                                saveBtn.textContent = 'Tersimpan di Proyek ✓';
                                saveBtn.style.background = '#ecfdf5';
                                saveBtn.style.color = '#047857';
                                saveBtn.style.borderColor = '#a7f3d0';
                            } catch (err) {
                                saveBtn.innerHTML = orig;
                                saveBtn.disabled = false;
                                alert(err.message || 'Gagal menyimpan jurnal ke proyek.');
                            }
                        });
                    }
                }
                const copyBtn = row.querySelector('.btn-copy-cite');
                if (copyBtn) {
                    copyBtn.addEventListener('click', () => {
                        navigator.clipboard.writeText(citationStr).then(() => {
                            copyBtn.textContent = 'Sitasi Disalin ✓';
                            setTimeout(() => { copyBtn.textContent = 'Salin Sitasi'; }, 1800);
                        });
                    });
                }
                list.appendChild(row);
            });
        }

        function downloadJournalListTxt() {
            if (!lastJournals.length) return;
            const lines = lastJournals.map((j, i) => `${i + 1}. [${j.scope || 'Jurnal'}] ${j.citation || `${j.author} (${j.year}). ${j.title}. ${j.journal}.`}\n   Link PDF: ${j.pdf_url}`).join('\n\n');
            const blob = new Blob([lines], { type: 'text/plain;charset=utf-8' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'Daftar_Jurnal_Referensi.txt';
            a.click();
        }

        // =====================================================================
        // 5. PARAFRASE AKADEMIK PRO (DIFF HIGHLIGHT, FILE/PROYEK IMPORT, & METRIK)
        // =====================================================================
        function setParaSourceMode(mode) {
            ['text', 'file', 'project'].forEach(m => {
                const btn = document.getElementById('para-src-btn-' + m);
                if (btn) btn.classList.toggle('active', m === mode);
            });
            const fBox = document.getElementById('para-import-file-box');
            const pBox = document.getElementById('para-import-proj-box');
            if (fBox) fBox.style.display = mode === 'file' ? 'block' : 'none';
            if (pBox) pBox.style.display = mode === 'project' ? 'block' : 'none';
        }

        function setParaIntensity(level, btn) {
            const hidden = document.getElementById('para-intensity');
            if (hidden) hidden.value = level;
            document.querySelectorAll('.para-int-btn').forEach(b => {
                b.style.background = '#fff';
                b.style.color = '#334155';
                b.style.borderColor = '#e2e8f0';
                b.style.fontWeight = '500';
            });
            if (btn) {
                btn.style.background = '#eff6ff';
                btn.style.color = '#1d4ed8';
                btn.style.borderColor = '#93c5fd';
                btn.style.fontWeight = '700';
            }
        }

        function updateParaCounter() {
            const val = (document.getElementById('para-input')?.value || '').trim();
            const words = val ? val.split(/\s+/).filter(Boolean).length : 0;
            const el = document.getElementById('para-input-counter');
            if (el) el.textContent = `${words} kata · ${val.length} karakter`;
        }

        function resetParaphraseStudio() {
            const inp = document.getElementById('para-input');
            const terms = document.getElementById('para-preserve-terms');
            const card = document.getElementById('para-result-card');
            if (inp) inp.value = '';
            if (terms) terms.value = '';
            if (card) card.style.display = 'none';
            updateParaCounter();
        }

        async function importFileToParaphrase(input) {
            if (!input.files || !input.files[0]) return;
            const file = input.files[0];
            const status = document.getElementById('para-file-status');
            const ta = document.getElementById('para-input');
            const nameLower = file.name.toLowerCase();
            if (status) status.textContent = `Membaca teks dari ${file.name}...`;
            try {
                let extracted = '';
                if (nameLower.endsWith('.txt')) {
                    extracted = await file.text();
                } else if (nameLower.endsWith('.pdf')) {
                    extracted = await extractPdfTextInBrowser(file);
                } else if (nameLower.endsWith('.docx')) {
                    const fd = new FormData();
                    fd.append('file', file);
                    const res = await fetch("{{ route('tools.extract.docxText') }}", {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken },
                        body: fd
                    });
                    const d = await res.json();
                    if (!res.ok || d.status !== 'success') throw new Error(d.message || 'Gagal membaca .docx');
                    extracted = d.text || '';
                } else {
                    throw new Error('Format harus .docx, .pdf, atau .txt');
                }
                extracted = extracted.trim().slice(0, 14000);
                if (!extracted) throw new Error('Teks kosong atau dokumen berupa scan gambar.');
                if (ta) ta.value = extracted;
                updateParaCounter();
                if (status) status.innerHTML = `<strong style="color:#16a34a;">Berhasil dimuat:</strong> ${escapeHtml(file.name)} (${extracted.split(/\s+/).length} kata). Kamu bisa mengedit atau memilih paragraf spesifik di bawah sebelum parafrase.`;
            } catch (err) {
                if (status) status.innerHTML = `<span style="color:#dc2626;">Gagal: ${escapeHtml(err.message)}</span>`;
            }
        }

        async function importProjectBabToParaphrase() {
            const pid = document.getElementById('para-proj-select')?.value;
            const bab = document.getElementById('para-proj-bab')?.value || 'bab1';
            const btn = document.getElementById('btn-load-para-proj');
            if (!pid) {
                alert('Pilih proyek terlebih dahulu.');
                return;
            }
            if (btn) { btn.disabled = true; btn.textContent = 'Memuat...'; }
            try {
                const res = await fetch(`/project/${pid}/chapters-text`);
                const d = await res.json();
                if (!res.ok || d.status !== 'success') throw new Error(d.message || 'Gagal mengambil naskah proyek.');
                const txt = ((d.chapters && d.chapters[bab]) || '').trim();
                if (!txt) {
                    alert('Isi bab yang dipilih masih kosong. Silakan pilih bab lain yang sudah di-generate.');
                    return;
                }
                const ta = document.getElementById('para-input');
                if (ta) ta.value = txt.slice(0, 14000);
                updateParaCounter();
            } catch (e) {
                alert(e.message || 'Gagal memuat bab proyek.');
            } finally {
                if (btn) { btn.disabled = false; btn.textContent = 'Muat Teks'; }
            }
        }

        function switchParaResultView(view) {
            const cleanPane = document.getElementById('para-view-clean');
            const diffPane = document.getElementById('para-view-diff');
            const cleanBtn = document.getElementById('para-view-clean-btn');
            const diffBtn = document.getElementById('para-view-diff-btn');
            if (cleanPane) cleanPane.style.display = view === 'clean' ? 'block' : 'none';
            if (diffPane) diffPane.style.display = view === 'diff' ? 'block' : 'none';
            if (cleanBtn) {
                cleanBtn.style.background = view === 'clean' ? '#eff6ff' : '#fff';
                cleanBtn.style.color = view === 'clean' ? '#1d4ed8' : '#334155';
                cleanBtn.style.borderColor = view === 'clean' ? '#93c5fd' : '#e2e8f0';
            }
            if (diffBtn) {
                diffBtn.style.background = view === 'diff' ? '#eff6ff' : '#fff';
                diffBtn.style.color = view === 'diff' ? '#1d4ed8' : '#334155';
                diffBtn.style.borderColor = view === 'diff' ? '#93c5fd' : '#e2e8f0';
            }
        }

        function renderParaphraseWordDiff(origText, newText) {
            const norm = w => w.toLowerCase().replace(/^[^\p{L}\p{N}]+|[^\p{L}\p{N}]+$/gu, '');
            const origTokens = origText.trim().split(/\s+/).filter(Boolean);
            const newTokens = newText.trim().split(/\s+/).filter(Boolean);
            const origSet = new Set(origTokens.map(norm).filter(Boolean));
            const newSet = new Set(newTokens.map(norm).filter(Boolean));

            const origHtml = origTokens.map(tok => {
                const n = norm(tok);
                if (n && n.length > 2 && !newSet.has(n)) {
                    return `<span style="background:#fee2e2;color:#991b1b;padding:0 3px;border-radius:3px;text-decoration:line-through;">${escapeHtml(tok)}</span>`;
                }
                return escapeHtml(tok);
            }).join(' ');

            const newHtml = newTokens.map(tok => {
                const n = norm(tok);
                if (n && n.length > 2 && !origSet.has(n)) {
                    return `<span style="background:#dcfce7;color:#166534;padding:0 3px;border-radius:3px;font-weight:500;">${escapeHtml(tok)}</span>`;
                }
                return escapeHtml(tok);
            }).join(' ');

            const origEl = document.getElementById('para-diff-orig');
            const newEl = document.getElementById('para-diff-new');
            if (origEl) origEl.innerHTML = origHtml;
            if (newEl) newEl.innerHTML = newHtml;
        }

        function copyParaphraseResult(btn) {
            const out = (document.getElementById('para-output')?.value || '').trim();
            if (!out) return;
            navigator.clipboard.writeText(out).then(() => {
                if (!btn) return;
                const prev = btn.textContent;
                btn.textContent = '✓ Tersalin!';
                setTimeout(() => { btn.textContent = prev; }, 1600);
            });
        }

        function downloadParaphraseTxt() {
            const out = (document.getElementById('para-output')?.value || '').trim();
            if (!out) return;
            const blob = new Blob([out], { type: 'text/plain;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'Hasil_Parafrase_SkripsiLab.txt';
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(url);
        }

        function reParaphraseOutput() {
            const out = (document.getElementById('para-output')?.value || '').trim();
            const inp = document.getElementById('para-input');
            if (!out || !inp) return;
            inp.value = out;
            updateParaCounter();
            handleParaphrase();
        }

        function sendParaphraseToPlagiarism() {
            const out = (document.getElementById('para-output')?.value || '').trim();
            if (!out) return;
            openTab('plagiarism', 'Cek Plagiasi');
            if (typeof setPlagInputMode === 'function') setPlagInputMode('text');
            const plagTa = document.getElementById('plag-text-input');
            if (plagTa) {
                plagTa.value = out;
                plagTa.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        async function handleParaphrase() {
            const text = (document.getElementById('para-input')?.value || '').trim();
            const style = document.getElementById('para-style')?.value || 'natural';
            const intensity = document.getElementById('para-intensity')?.value || 'sedang';
            const preserve_terms = (document.getElementById('para-preserve-terms')?.value || '').trim();
            if (text.length < 15) {
                alert('Masukkan minimal 1 kalimat lengkap (15 karakter) untuk diparafrase.');
                return;
            }
            const btn = document.getElementById('btn-paraphrase');
            btn.disabled = true;
            btn.innerHTML = '<span class="ai-spinner"></span><span>Memproses Parafrase Pro...</span>';
            document.getElementById('para-result-card').style.display = 'none';
            renderLoadingCard('para-loading-box', 'AI sedang merombak struktur kalimat & mengunci sitasi...', 'Menurunkan kemiripan Turnitin tanpa mengubah makna ilmiah, angka, dan sitasi dalam kurung...');

            try {
                const res = await fetch("{{ route('tools.paraphrase') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ text, style, intensity, preserve_terms, model: getSelectedModel() })
                });
                const data = await res.json();
                if (data.status === 'success') {
                    const outEl = document.getElementById('para-output');
                    if (outEl) outEl.value = data.result;
                    const m = data.metrics || {};
                    const uEl = document.getElementById('para-metric-uniqueness');
                    const sEl = document.getElementById('para-metric-sim');
                    const wEl = document.getElementById('para-metric-words');
                    const cEl = document.getElementById('para-metric-cites');
                    if (uEl) uEl.textContent = `Keunikan ${m.uniqueness_score ?? '-'}%`;
                    if (sEl) sEl.textContent = `Kemiripan tersisa ${m.ngram_similarity ?? '-'}%`;
                    if (wEl) wEl.textContent = `${m.original_words ?? 0} → ${m.result_words ?? 0} kata`;
                    if (cEl) {
                        if ((m.citations_detected || 0) > 0) {
                            cEl.style.display = 'inline-block';
                            cEl.textContent = `${m.citations_preserved}/${m.citations_detected} sitasi terjaga`;
                        } else {
                            cEl.style.display = 'none';
                        }
                    }
                    renderParaphraseWordDiff(text, data.result);
                    switchParaResultView('clean');
                    document.getElementById('para-result-card').style.display = 'block';
                    document.getElementById('para-result-card').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                } else {
                    alert(data.message || 'Gagal memproses parafrase.');
                }
            } catch (e) {
                alert('Gagal memproses parafrase.');
            } finally {
                hideLoadingCard('para-loading-box');
                btn.disabled = false;
                btn.innerText = 'Parafrase Sekarang →';
            }
        }

        let qaChatHistory = [];
        let qaAttachedImageBase64 = null;

        function handleQaImageSelect(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const r = new FileReader();
                r.onload = e => {
                    qaAttachedImageBase64 = e.target.result;
                    const prevBar = document.getElementById('qa-image-preview-bar');
                    const prevImg = document.getElementById('qa-image-preview-img');
                    const prevName = document.getElementById('qa-image-name');
                    if (prevBar && prevImg && prevName) {
                        prevImg.src = e.target.result;
                        prevName.innerText = file.name;
                        prevBar.style.display = 'flex';
                    }
                };
                r.readAsDataURL(file);
            }
        }

        function removeQaImage() {
            qaAttachedImageBase64 = null;
            const input = document.getElementById('qa-image');
            if (input) input.value = '';
            const prevBar = document.getElementById('qa-image-preview-bar');
            if (prevBar) prevBar.style.display = 'none';
        }

        function resetQaChat() {
            qaChatHistory = [];
            removeQaImage();
            const msgBox = document.getElementById('qa-chat-messages');
            if (msgBox) {
                msgBox.innerHTML = `
                    <div class="chat-msg assistant" style="display: flex; gap: 12px; align-items: flex-start; max-width: 88%;">
                        <div style="width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg, #2563eb, #1d4ed8); display: flex; align-items: center; justify-content: center; color: #fff; flex-shrink: 0; box-shadow: 0 2px 4px rgba(37,99,235,0.25);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                                <path d="M6 12v5c0 2 3 3 6 3s6-1 6-3v-5"/>
                            </svg>
                        </div>
                        <div style="background: #f1f5f9; padding: 12px 16px; border-radius: 4px 14px 14px 14px; font-size: 13.5px; line-height: 1.6; color: var(--text-main);">
                            Halo! Obrolan baru telah dimulai. Silakan ajukan pertanyaan atau konsultasikan apa saja seputar skripsi, bimbingan dosen, olah data, hingga ujian sidang Anda.
                        </div>
                    </div>
                `;
            }
        }

        function formatMentorResponse(text) {
            if (!text) return '';
            let s = text.trim();

            // 1. Bersihkan sisa-sisa simbol robotik AI
            s = s.replace(/^#{1,6}\s*(.*)$/gm, '<div style="font-size: 14.5px; font-weight: 700; color: #0f172a; margin: 12px 0 4px 0;">$1</div>');
            s = s.replace(/^[-*_]{3,}\s*$/gm, '');
            s = s.replace(/^>\s*(.*)$/gm, '<div style="padding: 6px 12px; background: #eff6ff; border-left: 3px solid #2563eb; border-radius: 4px; color: #1e3a8a; margin: 8px 0; font-size: 13px;">$1</div>');

            // 2. Format bold & italic
            s = s.replace(/\*\*(.*?)\*\*/g, '<strong style="color: #0f172a;">$1</strong>');
            s = s.replace(/\*([^\*]+)\*/g, '<em>$1</em>');

            // 3. Format list angka (1. text, 2. text)
            s = s.replace(/^(\d+)\.\s+(.*)$/gm, '<div style="display: flex; gap: 8px; margin: 6px 0; align-items: flex-start;"><span style="font-weight: 700; color: #2563eb; min-width: 18px; flex-shrink: 0;">$1.</span><div style="flex: 1;">$2</div></div>');

            // 4. Format list bullet (- text atau • text)
            s = s.replace(/^[-•]\s+(.*)$/gm, '<div style="display: flex; gap: 8px; margin: 4px 0; align-items: flex-start;"><span style="color: #2563eb; font-weight: 700; flex-shrink: 0;">•</span><div style="flex: 1;">$1</div></div>');

            // 5. Bungkus paragraf
            const parts = s.split(/\n{2,}/);
            return parts.map(p => {
                p = p.trim();
                if (!p) return '';
                if (p.startsWith('<div')) return p;
                return `<p style="margin: 0 0 10px 0; line-height: 1.65;">${p.replace(/\n/g, '<br>')}</p>`;
            }).join('');
        }

        function sendQuickPrompt(promptText) {
            const qInput = document.getElementById('qa-question');
            if (qInput) {
                qInput.value = promptText;
                handleSolveQa();
            }
        }

        async function handleSolveQa() {
            const qInput = document.getElementById('qa-question');
            const question = (qInput?.value || '').trim();
            if (!question && !qaAttachedImageBase64) {
                alert('Tuliskan pertanyaan atau lampirkan gambar terlebih dahulu.');
                return;
            }

            const btn = document.getElementById('btn-solve-qa');
            btn.disabled = true;
            btn.innerHTML = '<span class="ai-spinner"></span>';

            const msgBox = document.getElementById('qa-chat-messages');

            // Render User Bubble (escape HTML aman)
            const userImgHtml = qaAttachedImageBase64 ? `<img src="${qaAttachedImageBase64}" style="max-height:120px; border-radius:8px; margin-bottom:8px; display:block;">` : '';
            const userHtml = `
                <div class="chat-msg user" style="display: flex; gap: 10px; align-items: flex-start; justify-content: flex-end; width: 100%;">
                    <div style="background: #2563eb; color: #ffffff; padding: 12px 16px; border-radius: 14px 4px 14px 14px; font-size: 13.5px; line-height: 1.6; max-width: 85%; word-break: break-word;">
                        ${userImgHtml}
                        ${question ? escapeHtml(question).replace(/\n/g, '<br>') : '<em>[Lampiran gambar untuk dianalisis]</em>'}
                    </div>
                </div>
            `;
            if (msgBox) {
                msgBox.innerHTML += userHtml;
                msgBox.scrollTop = msgBox.scrollHeight;
            }

            // Clear input & image
            const currentImg = qaAttachedImageBase64;
            if (qInput) qInput.value = '';
            removeQaImage();

            // Render Assistant Typing Indicator
            const typingId = 'typing-' + Date.now();
            if (msgBox) {
                msgBox.innerHTML += `
                    <div id="${typingId}" class="chat-msg assistant" style="display: flex; gap: 12px; align-items: flex-start; max-width: 88%;">
                        <div style="width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg, #2563eb, #1d4ed8); display: flex; align-items: center; justify-content: center; color: #fff; flex-shrink: 0; box-shadow: 0 2px 4px rgba(37,99,235,0.25);">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                                <path d="M6 12v5c0 2 3 3 6 3s6-1 6-3v-5"/>
                            </svg>
                        </div>
                        <div style="background: #f1f5f9; padding: 12px 16px; border-radius: 4px 14px 14px 14px; font-size: 13.5px; line-height: 1.6; color: var(--text-muted); display:flex; align-items:center; gap:6px;">
                            <span class="ai-spinner-blue" style="width:14px; height:14px;"></span>
                            <span>Dosen AI sedang menganalisis &amp; menyusun penjelasan...</span>
                        </div>
                    </div>
                `;
                msgBox.scrollTop = msgBox.scrollHeight;
            }

            try {
                // Kirim riwayat chat terstruktur ke backend solveQuestion
                const structuredHistory = [];
                qaChatHistory.slice(-4).forEach(h => {
                    if (h.q) structuredHistory.push({ role: 'user', content: h.q });
                    if (h.a) structuredHistory.push({ role: 'assistant', content: h.a });
                });

                const res = await fetch("{{ route('tools.solve') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({
                        question: question,
                        history: structuredHistory,
                        image: currentImg,
                        model: getSelectedModel()
                    })
                });
                const data = await res.json();
                const typingEl = document.getElementById(typingId);
                if (typingEl) typingEl.remove();

                if (data.status === 'success' && data.answer) {
                    qaChatHistory.push({ q: question, a: data.answer });
                    const ansId = 'ans-' + Date.now();
                    const cleanAnswerHtml = formatMentorResponse(data.answer);

                    if (msgBox) {
                        msgBox.innerHTML += `
                            <div class="chat-msg assistant" style="display: flex; gap: 12px; align-items: flex-start; max-width: 92%;">
                                <div class="qa-avatar" style="width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg, #2563eb, #1d4ed8); display: flex; align-items: center; justify-content: center; color: #fff; flex-shrink: 0; box-shadow: 0 2px 6px rgba(37,99,235,0.25);">
                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                                        <path d="M6 12v5c0 2 3 3 6 3s6-1 6-3v-5"/>
                                    </svg>
                                </div>
                                <div class="qa-bubble" style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 14px 16px; border-radius: 4px 14px 14px 14px; font-size: 13.5px; line-height: 1.68; color: var(--text-main); flex: 1; min-width: 0; word-break: break-word;">
                                    <div id="${ansId}">${cleanAnswerHtml}</div>
                                    <div style="margin-top: 10px; padding-top: 8px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end;">
                                        <button type="button" class="qa-copy-btn" onclick="navigator.clipboard.writeText(document.getElementById('${ansId}').innerText); this.textContent='✓ Tersalin'; setTimeout(() => this.textContent='Salin Teks', 1800);">Salin Teks</button>
                                    </div>
                                </div>
                            </div>
                        `;
                        msgBox.scrollTop = msgBox.scrollHeight;
                    }
                } else {
                    alert(data.message || 'Gagal memproses pertanyaan.');
                }
            } catch (e) {
                const typingEl = document.getElementById(typingId);
                if (typingEl) typingEl.remove();
                alert('Gagal memproses pertanyaan.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<span>Kirim</span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>';
            }
        }

        // ============================================================
        // CEK PLAGIASI (SIMILARITY CHECKER ALA TURNITIN)
        // ============================================================
        let plagInputMode = 'text';
        let plagCurrentReport = null;
        let plagSelectedSentenceIdx = null;
        let plagPdfJsPromise = null;

        function setPlagInputMode(mode) {
            plagInputMode = mode;
            ['text', 'file', 'project'].forEach(m => {
                const btn = document.getElementById('plag-tab-' + m);
                const box = document.getElementById('plag-box-' + m);
                if (btn) btn.classList.toggle('active', m === mode);
                if (box) box.style.display = (m === mode) ? 'block' : 'none';
            });
        }

        function onPlagFileSelected(input) {
            const info = document.getElementById('plag-file-info');
            if (input.files && input.files[0] && info) {
                const f = input.files[0];
                info.innerHTML = `File siap diperiksa: <strong>${escapeHtml(f.name)}</strong> (${(f.size / 1024).toFixed(1)} KB)`;
            }
        }

        async function extractPdfTextInBrowser(file) {
            if (!plagPdfJsPromise) {
                plagPdfJsPromise = import('https://cdn.jsdelivr.net/npm/pdfjs-dist@4.4.168/build/pdf.min.mjs').then(mod => {
                    mod.GlobalWorkerOptions.workerSrc = 'https://cdn.jsdelivr.net/npm/pdfjs-dist@4.4.168/build/pdf.worker.min.mjs';
                    return mod;
                });
            }
            const pdfjsLib = await plagPdfJsPromise;
            const buf = await file.arrayBuffer();
            const pdf = await pdfjsLib.getDocument({ data: buf }).promise;
            const maxPages = Math.min(pdf.numPages, 40);
            let out = [];
            for (let p = 1; p <= maxPages; p++) {
                const page = await pdf.getPage(p);
                const tc = await page.getTextContent();
                const pageText = tc.items.map(it => it.str || '').join(' ');
                if (pageText.trim()) out.push(pageText.trim());
            }
            return out.join('\n\n');
        }

        function showPlagError(msg) {
            const box = document.getElementById('plag-error-banner');
            if (!box) { alert(msg); return; }
            if (!msg) {
                box.style.display = 'none';
                box.textContent = '';
            } else {
                box.style.display = 'block';
                box.textContent = msg;
            }
        }

        async function handlePlagiarismCheck() {
            const btn = document.getElementById('btn-plag-check');
            const resWrap = document.getElementById('plag-result-wrap');
            const defaultBtnHtml = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg><span>Cek Plagiasi Sekarang</span>';
            showPlagError('');

            const fd = new FormData();
            fd.append('exclude_bibliography', document.getElementById('plag-ex-bib')?.checked ? '1' : '0');
            fd.append('exclude_quotes', document.getElementById('plag-ex-quotes')?.checked ? '1' : '0');
            fd.append('exclude_small_matches', document.getElementById('plag-ex-small')?.checked ? '1' : '0');

            if (plagInputMode === 'text') {
                const txt = (document.getElementById('plag-text-input')?.value || '').trim();
                if (txt.length < 80) {
                    showPlagError('Tempelkan minimal 1 paragraf (80 karakter / sekitar 15 kata) agar dapat diperiksa kemiripannya.');
                    return;
                }
                fd.append('text', txt);
                fd.append('doc_title', (document.getElementById('plag-doc-title')?.value || '').trim() || 'Naskah Skripsi');
            } else if (plagInputMode === 'file') {
                const fInput = document.getElementById('plag-file-input');
                if (!fInput || !fInput.files || !fInput.files[0]) {
                    showPlagError('Pilih file .docx, .pdf, atau .txt terlebih dahulu.');
                    return;
                }
                const f = fInput.files[0];
                fd.append('doc_title', f.name.replace(/\.[^.]+$/, ''));
                if (f.name.toLowerCase().endsWith('.pdf')) {
                    try {
                        btn.disabled = true;
                        renderLoadingCard('plag-loading-box', 'Mengekstrak teks PDF...', 'Membaca isi halaman PDF langsung di browser...');
                        const pdfTxt = await extractPdfTextInBrowser(f);
                        if (pdfTxt && pdfTxt.trim().length >= 80) {
                            fd.append('text', pdfTxt);
                        } else {
                            fd.append('file', f);
                        }
                    } catch (pdfErr) {
                        fd.append('file', f);
                    }
                } else {
                    fd.append('file', f);
                }
            } else if (plagInputMode === 'project') {
                const pid = document.getElementById('plag-project-select')?.value;
                if (!pid) {
                    showPlagError('Pilih proyek skripsi atau proposal yang ingin diperiksa.');
                    return;
                }
                fd.append('project_id', pid);
            }

            // Opsional: Dokumen pembanding lokal
            const cmpFileInput = document.getElementById('plag-compare-file');
            const cmpText = (document.getElementById('plag-compare-text')?.value || '').trim();
            if (cmpFileInput && cmpFileInput.files && cmpFileInput.files[0]) {
                const cf = cmpFileInput.files[0];
                if (cf.name.toLowerCase().endsWith('.pdf')) {
                    try {
                        const cTxt = await extractPdfTextInBrowser(cf);
                        if (cTxt && cTxt.trim().length >= 40) fd.append('compare_text', cTxt);
                        else fd.append('compare_file', cf);
                    } catch (e) {
                        fd.append('compare_file', cf);
                    }
                } else {
                    fd.append('compare_file', cf);
                }
            } else if (cmpText.length >= 40) {
                fd.append('compare_text', cmpText);
            }

            btn.disabled = true;
            btn.innerHTML = '<span class="ai-spinner"></span><span>Memeriksa Kemiripan...</span>';
            if (resWrap) resWrap.style.display = 'none';
            document.getElementById('plag-inline-editor').style.display = 'none';
            renderLoadingCard('plag-loading-box', 'Memeriksa Kemiripan Naskah (Ala Turnitin)...', 'Membandingkan frasa naskah dengan OpenAlex, Crossref, Wikipedia & Web secara paralel...');

            try {
                const res = await fetch("{{ route('tools.plagiarism.check') }}", {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: fd
                });
                const rawBody = await res.text();
                let data = {};
                try { data = JSON.parse(rawBody); } catch (_) { data = { message: 'Respon server tidak valid (' + res.status + ').' }; }
                hideLoadingCard('plag-loading-box');

                if (res.ok && data.status === 'success' && data.report) {
                    plagCurrentReport = data.report;
                    renderPlagiarismReport(plagCurrentReport);
                    if (resWrap) {
                        resWrap.style.display = 'block';
                        resWrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                } else {
                    showPlagError(data.message || 'Gagal memeriksa kemiripan naskah.');
                }
            } catch (err) {
                hideLoadingCard('plag-loading-box');
                showPlagError('Terjadi kesalahan jaringan saat memeriksa plagiasi. Silakan coba lagi.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = defaultBtnHtml;
            }
        }

        function renderPlagiarismReport(rep) {
            const overall = Number(rep.overall_score || 0);
            const pctEl = document.getElementById('plag-big-pct');
            const pillEl = document.getElementById('plag-status-pill');

            let scoreColor = '#059669';
            let pillBg = '#ecfdf5';
            let pillColor = '#047857';
            if (overall > 25) {
                scoreColor = '#dc2626'; pillBg = '#fef2f2'; pillColor = '#b91c1c';
            } else if (overall > 15) {
                scoreColor = '#d97706'; pillBg = '#fffbeb'; pillColor = '#b45309';
            }

            if (pctEl) {
                pctEl.textContent = overall.toFixed(1) + '%';
                pctEl.style.color = scoreColor;
            }
            if (pillEl) {
                pillEl.textContent = rep.status_label || 'Selesai';
                pillEl.style.background = pillBg;
                pillEl.style.color = pillColor;
            }

            const wSum = document.getElementById('plag-word-summary');
            if (wSum) {
                wSum.textContent = `${rep.checked_words || 0} kata dicek · ${rep.matched_words || 0} kata mirip`;
            }
            document.getElementById('plag-acad-pct').textContent = Number(rep.academic_score || 0).toFixed(1) + '%';
            document.getElementById('plag-web-pct').textContent = Number(rep.web_score || 0).toFixed(1) + '%';
            document.getElementById('plag-local-pct').textContent = Number(rep.local_score || 0).toFixed(1) + '%';
            document.getElementById('plag-ttr-val').textContent = Number(rep.lexical_diversity || 0).toFixed(1) + '%';

            // Render dokumen dengan highlight warna persis Turnitin
            const viewer = document.getElementById('plag-doc-viewer');
            if (viewer) {
                let html = '';
                (rep.sentences || []).forEach((s, i) => {
                    const safeText = escapeHtml(s.text || '');
                    if (s.is_heading) {
                        html += `<div style="font-weight:700; color:#0f172a; margin:12px 0 4px;">${safeText}</div>`;
                    } else if (s.matched && s.color) {
                        const tip = `Mirip ${s.match_percent}% (${s.contiguous_words} kata berurutan) · Sumber [${s.source_number}]: ${escapeHtml(s.source_title || '')}`;
                        html += `<span class="plag-sent-hl" data-sidx="${i}" title="${tip}" onclick="openPlagInlineParaphrase(${i})" style="background:${s.color.bg}; border-bottom-color:${s.color.border};"><span class="plag-sent-num" style="background:${s.color.badge};">${s.source_number}</span>${safeText}</span> `;
                    } else if (s.paraphrased) {
                        html += `<span style="background:#ecfdf5; border-bottom:2px solid #10b981; padding:2px 4px; border-radius:4px;" title="Sudah diparafrase">${safeText}</span> `;
                    } else {
                        html += `${safeText} `;
                    }
                });
                viewer.innerHTML = html || '<div style="color:#64748b;">Tidak ada teks untuk ditampilkan.</div>';
            }

            // Render daftar sumber cocok
            const srcList = document.getElementById('plag-sources-list');
            const srcCount = document.getElementById('plag-src-count');
            const sources = rep.sources || [];
            if (srcCount) srcCount.textContent = `${sources.length} sumber`;
            if (srcList) {
                if (!sources.length) {
                    srcList.innerHTML = `<div style="padding:14px; text-align:center; background:#ecfdf5; border:1px solid #a7f3d0; border-radius:9px; font-size:12.5px; color:#047857;">
                        ✓ Tidak ditemukan kemiripan signifikan pada jurnal ilmiah terbuka &amp; web!
                    </div>`;
                } else {
                    srcList.innerHTML = sources.map(src => {
                        const linkHtml = src.url
                            ? `<a href="${escapeHtml(src.url)}" target="_blank" rel="noopener" style="font-size:11.5px; color:#2563eb; text-decoration:underline; word-break:break-all;">Buka Sumber ↗</a>`
                            : `<span style="font-size:11.5px; color:#64748b;">Dokumen Pembanding Lokal</span>`;
                        return `
                            <div class="plag-src-item">
                                <span class="plag-sent-num" style="background:${src.color.badge}; margin-top:2px; flex-shrink:0;">${src.number}</span>
                                <div style="flex:1; min-width:0;">
                                    <div style="font-size:12.5px; font-weight:700; color:#0f172a; line-height:1.35;">${escapeHtml(src.title)}</div>
                                    <div style="font-size:11px; color:#64748b; margin-top:2px;">${escapeHtml(src.origin)}${src.year ? ' · ' + src.year : ''} · ${src.matched_sentences} kalimat</div>
                                    <div style="margin-top:3px;">${linkHtml}</div>
                                </div>
                                <div style="font-size:13.5px; font-weight:800; color:${src.color.badge}; flex-shrink:0;">${Number(src.percent).toFixed(1)}%</div>
                            </div>
                        `;
                    }).join('');
                }
            }
        }

        function openPlagInlineParaphrase(idx) {
            if (!plagCurrentReport || !plagCurrentReport.sentences[idx]) return;
            plagSelectedSentenceIdx = idx;
            const s = plagCurrentReport.sentences[idx];
            const box = document.getElementById('plag-inline-editor');
            document.getElementById('plag-inline-meta').textContent = `Sumber [${s.source_number}] · Kemiripan ${s.match_percent}% (${s.contiguous_words} kata berurutan)`;
            document.getElementById('plag-inline-orig').textContent = s.text;
            const srcLinkEl = document.getElementById('plag-inline-source-link');
            srcLinkEl.innerHTML = s.source_url
                ? `Mirip dengan: <strong>${escapeHtml(s.source_title || '')}</strong> — <a href="${escapeHtml(s.source_url)}" target="_blank" rel="noopener" style="color:#2563eb; text-decoration:underline;">Lihat Sumber ↗</a>`
                : `Mirip dengan: <strong>${escapeHtml(s.source_title || 'Sumber terdeteksi')}</strong>`;
            document.getElementById('plag-inline-result-box').style.display = 'none';
            document.getElementById('plag-inline-new-text').value = '';
            box.style.display = 'block';
            box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        async function paraphraseFlaggedSentence() {
            if (plagSelectedSentenceIdx === null || !plagCurrentReport) return;
            const s = plagCurrentReport.sentences[plagSelectedSentenceIdx];
            const btn = document.getElementById('btn-plag-inline-para');
            const origHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="ai-spinner"></span><span>Memparafrase kalimat...</span>';
            try {
                const res = await fetch("{{ route('tools.paraphrase') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify({ text: s.text, style: 'natural', model: getSelectedModel() })
                });
                const data = await res.json();
                if (data.status === 'success' && data.result) {
                    document.getElementById('plag-inline-new-text').value = data.result.trim();
                    document.getElementById('plag-inline-result-box').style.display = 'block';
                } else {
                    showPlagError(data.message || 'Gagal memparafrase kalimat.');
                }
            } catch (e) {
                showPlagError('Gagal menghubungi layanan parafrase.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = origHtml;
            }
        }

        function applyParaphrasedSentence() {
            if (plagSelectedSentenceIdx === null || !plagCurrentReport) return;
            const newTxt = (document.getElementById('plag-inline-new-text')?.value || '').trim();
            if (!newTxt) return;
... [truncated for diff preview]