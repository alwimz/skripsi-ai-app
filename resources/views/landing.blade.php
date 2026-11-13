<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkripsiAI — Studio Penulisan Skripsi & Proposal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --bg-main: #ffffff;
            --bg-subtle: #f8fafc;
            --bg-muted: #f3f4f6;
            --text-primary: #111827;
            --text-secondary: #4b5563;
            --text-muted: #6b7280;
            --border-color: #e5e7eb;
            --border-hover: #d1d5db;
            --primary: #2e2a5a;
            --blue: #2563eb;
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
            max-width: 1120px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* Navbar — Clean Skripsita Style */
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
        }

        .brand {
            font-size: 17px;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .brand-mark {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            background: var(--primary);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 6px;
            list-style: none;
        }

        .nav-item {
            position: relative;
        }

        .nav-link, .nav-trigger {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            font-size: 14px;
            font-weight: 500;
            color: var(--text-secondary);
            border-radius: 8px;
            border: none;
            background: transparent;
            cursor: pointer;
            font-family: inherit;
            transition: background 0.15s ease, color 0.15s ease;
        }

        .nav-link:hover, .nav-trigger:hover, .nav-item:hover .nav-trigger {
            background: var(--bg-muted);
            color: var(--text-primary);
        }

        /* Dropdown Panel (Matches Reference Image 1) */
        .dropdown-panel {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            width: 560px;
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 16px;
            box-shadow: 0 20px 40px -15px rgba(17, 24, 39, 0.08);
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
            background: var(--bg-subtle);
        }

        .dropdown-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: var(--bg-muted);
            color: #374151;
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
            gap: 10px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 13.5px;
            font-weight: 600;
            padding: 9px 16px;
            border-radius: 9px;
            cursor: pointer;
            transition: all 0.15s ease;
            font-family: inherit;
            white-space: nowrap;
        }

        .btn-ghost {
            color: var(--text-secondary);
            background: transparent;
        }

        .btn-ghost:hover {
            color: var(--text-primary);
            background: var(--bg-muted);
        }

        .btn-primary {
            background: var(--primary);
            color: #ffffff;
            border: 1px solid var(--primary);
        }

        .btn-primary:hover {
            background: #231f46;
        }

        .btn-outline {
            background: #ffffff;
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        .btn-outline:hover {
            background: var(--bg-subtle);
            border-color: var(--border-hover);
        }

        /* Hero Section */
        .hero {
            padding: 68px 0 56px;
            text-align: center;
            border-bottom: 1px solid var(--border-color);
        }

        .hero-eyebrow {
            font-size: 12.5px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 12px;
        }

        .hero h1 {
            font-size: clamp(2rem, 3.8vw + 0.5rem, 3.1rem);
            font-weight: 700;
            letter-spacing: -0.03em;
            line-height: 1.18;
            color: var(--text-primary);
            max-width: 720px;
            margin: 0 auto 16px;
        }

        .hero p {
            font-size: 16px;
            color: var(--text-secondary);
            max-width: 580px;
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
            padding: 72px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .section-subtle {
            background: var(--bg-subtle);
        }

        .section-header {
            max-width: 640px;
            margin: 0 auto 36px;
            text-align: center;
        }

        .section-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 6px;
            display: block;
        }

        .section-title {
            font-size: clamp(1.45rem, 2.4vw, 2rem);
            font-weight: 700;
            letter-spacing: -0.025em;
            color: var(--text-primary);
            margin-bottom: 10px;
        }

        .section-desc {
            font-size: 14.5px;
            color: var(--text-secondary);
        }

        /* Studio Workspace Preview (Matches Mantra Riset Studio Screenshot) */
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
        }

        .major-pill.active {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
        }

        .studio-frame {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 20px 45px -15px rgba(15, 23, 42, 0.1);
            display: grid;
            grid-template-columns: 230px 1fr;
            min-height: 580px;
        }

        .studio-frame-sidebar {
            background: #f8fafc;
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
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
        }

        .sf-nav-btn {
            width: 100%;
            text-align: left;
            padding: 8px 10px;
            border-radius: 7px;
            border: none;
            background: transparent;
            font-size: 12.5px;
            font-weight: 500;
            color: #475569;
            cursor: pointer;
            font-family: inherit;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .sf-nav-btn:hover {
            background: #e2e8f0;
        }

        .sf-nav-btn.active {
            background: #0f172a;
            color: #ffffff;
            font-weight: 600;
        }

        .studio-frame-main {
            display: flex;
            flex-direction: column;
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
            max-height: 540px;
        }

        .sf-a4-paper {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            max-width: 720px;
            margin: 0 auto;
            padding: 44px 52px;
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

        /* Features Grid */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }

        .feature-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 22px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .feature-icon {
            width: 38px;
            height: 38px;
            border-radius: 9px;
            background: var(--bg-muted);
            color: var(--text-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
        }

        .feature-card h3 {
            font-size: 15.5px;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .feature-card p {
            font-size: 13.5px;
            color: var(--text-secondary);
            margin-bottom: 14px;
        }

        .feature-link {
            font-size: 13px;
            font-weight: 600;
            color: var(--primary);
        }

        /* Footer */
        .footer {
            padding: 40px 0;
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

        @media (max-width: 900px) {
            .studio-frame {
                grid-template-columns: 1fr;
            }
            .studio-frame-sidebar {
                border-right: none;
                border-bottom: 1px solid #e2e8f0;
            }
            .features-grid {
                grid-template-columns: 1fr;
            }
            .nav-links {
                display: none;
            }
            .sf-a4-paper {
                padding: 24px 20px;
            }
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <header class="navbar">
        <div class="container nav-inner">
            <a href="/" class="brand">
                <span class="brand-mark">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/></svg>
                </span>
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
                                <div class="dropdown-desc">Ide judul sesuai jurusan & metodologi</div>
                            </div>
                        </a>

                        <a href="{{ auth()->check() ? route('app.create', ['tab' => 'projects']) : route('login') }}" class="dropdown-item">
                            <div class="dropdown-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            </div>
                            <div>
                                <div class="dropdown-title">Studio Proposal & Skripsi</div>
                                <div class="dropdown-desc">Susun Bab 1–3 atau Bab 1–5 sesuai pedoman</div>
                            </div>
                        </a>

                        <a href="{{ auth()->check() ? route('app.create', ['tab' => 'paraphrase']) : route('login') }}" class="dropdown-item">
                            <div class="dropdown-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                            </div>
                            <div>
                                <div class="dropdown-title">Parafrase Online</div>
                                <div class="dropdown-desc">Parafrase akademik, hasil natural</div>
                            </div>
                        </a>

                        <a href="{{ auth()->check() ? route('app.create', ['tab' => 'journals']) : route('login') }}" class="dropdown-item">
                            <div class="dropdown-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                            </div>
                            <div>
                                <div class="dropdown-title">Cari Jurnal & Unduh PDF</div>
                                <div class="dropdown-desc">Jurnal open-access & sitasi APA, IEEE</div>
                            </div>
                        </a>

                        <a href="{{ auth()->check() ? route('app.create', ['tab' => 'ppt']) : route('login') }}" class="dropdown-item">
                            <div class="dropdown-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/></svg>
                            </div>
                            <div>
                                <div class="dropdown-title">PPT Sidang</div>
                                <div class="dropdown-desc">Slide sidang otomatis dari isi skripsimu</div>
                            </div>
                        </a>

                        <a href="{{ auth()->check() ? route('app.create', ['tab' => 'defense']) : route('login') }}" class="dropdown-item">
                            <div class="dropdown-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                            </div>
                            <div>
                                <div class="dropdown-title">Simulasi Sidang</div>
                                <div class="dropdown-desc">Latihan tanya-jawab & evaluasi jawaban</div>
                            </div>
                        </a>
                    </div>
                </li>
                <li><a href="#studio-contoh" class="nav-link">Contoh Hasil & Unduh</a></li>
                <li><a href="#fitur-lengkap" class="nav-link">Fitur</a></li>
            </ul>

            <div class="nav-actions">
                @auth
                    <a href="{{ route('app.create') }}" class="btn btn-primary">Buka Ruang Kerja</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-ghost">Masuk</a>
                    <a href="{{ route('register') }}" class="btn btn-primary">Mulai Gratis</a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Hero -->
    <section class="hero">
        <div class="container">
            <div class="hero-eyebrow">Studio Penulisan Akademik Mahasiswa</div>
            <h1>Susun Proposal & Skripsi Lebih Terstruktur</h1>
            <p>Mulai dari mencari judul, menyusun proposal Bab 1–3 dan skripsi Bab 1–5 sesuai pedoman kampus, hingga membuat slide PPT dan latihan simulasi sidang.</p>

            <div class="hero-cta">
                @auth
                    <a href="{{ route('app.create', ['tab' => 'projects']) }}" class="btn btn-primary">Buat Proyek Baru →</a>
                @else
                    <a href="{{ route('register') }}" class="btn btn-primary">Mulai Menulis →</a>
                @endauth
                <a href="#studio-contoh" class="btn btn-outline">Lihat & Unduh Contoh Hasil</a>
            </div>
        </div>
    </section>

    <!-- Contoh Studio & Download File Word/PDF Asli -->
    <section id="studio-contoh" class="section section-subtle">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Pratinjau Studio & File Hasil</span>
                <h2 class="section-title">Lihat Tampilan Ruang Kerja & Unduh Contoh Asli</h2>
                <p class="section-desc">Pilih program studi di bawah untuk melihat hasil penyusunan di atas kertas A4, atau langsung unduh file Word (.docx) dan PDF aslinya.</p>
            </div>

            <!-- Pilihan Prodi -->
            <div class="major-selector">
                <button type="button" class="major-pill active" onclick="switchSampleMajor('manajemen', this)">Manajemen (Kuantitatif)</button>
                <button type="button" class="major-pill" onclick="switchSampleMajor('it', this)">Teknik Informatika (R&D)</button>
                <button type="button" class="major-pill" onclick="switchSampleMajor('hukum', this)">Ilmu Hukum (Normatif)</button>
            </div>

            <!-- Studio Mockup (Matches Mantra Riset Studio Screenshot) -->
            <div class="studio-frame">
                <!-- Left Studio Sidebar -->
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
                            <span style="color: #22c55e;">●</span>
                        </button>
                        <button type="button" class="sf-nav-btn" id="sf-btn-bab2" onclick="switchSampleChapter('bab2')">
                            <span>Bab II Tinjauan Pustaka</span>
                            <span style="color: #22c55e;">●</span>
                        </button>
                        <button type="button" class="sf-nav-btn" id="sf-btn-bab3" onclick="switchSampleChapter('bab3')">
                            <span>Bab III Metodologi</span>
                            <span style="color: #22c55e;">●</span>
                        </button>
                        <button type="button" class="sf-nav-btn" id="sf-btn-dp" onclick="switchSampleChapter('dp')">
                            <span>Daftar Pustaka</span>
                            <span style="color: #22c55e;">●</span>
                        </button>
                    </div>

                    <div style="margin-top: auto; padding: 10px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; font-size: 11.5px; color: #1e40af; line-height: 1.45;">
                        <strong>Lanjutkan ke Bab IV–V</strong><br>
                        Sudah selesai proposal? Buka 2 bab terakhir di proyek yang sama.
                    </div>
                </div>

                <!-- Right Studio Main -->
                <div class="studio-frame-main">
                    <!-- Topbar dengan Tombol Download Word (.docx) & PDF Asli -->
                    <div class="sf-topbar">
                        <div style="font-size: 12px; font-weight: 600; color: #475569;">
                            Nomor 1.1 / 1.1.1 · Sitasi APA 7th · Times New Roman 12pt
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            <a id="sample-dl-docx" href="{{ route('sample.download', ['major' => 'manajemen', 'format' => 'docx']) }}" class="btn btn-outline" style="padding: 6px 12px; font-size: 12px;">
                                📄 Unduh Contoh Word (.docx)
                            </a>
                            <a id="sample-dl-pdf" href="{{ route('sample.download', ['major' => 'manajemen', 'format' => 'pdf']) }}" class="btn btn-primary" style="padding: 6px 12px; font-size: 12px; background: #2563eb; border-color: #2563eb;">
                                📕 Unduh Contoh PDF (.pdf)
                            </a>
                        </div>
                    </div>

                    <!-- Kertas A4 Word Preview -->
                    <div class="sf-paper-area">
                        <div class="sf-a4-paper" id="sf-paper-content">
                            <!-- Diisi oleh JS -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Semua Fitur -->
    <section id="fitur-lengkap" class="section">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Fitur Lengkap</span>
                <h2 class="section-title">Pendamping dari Ide Judul hingga Sidang</h2>
            </div>

            <div class="features-grid">
                <div class="feature-card">
                    <div>
                        <div class="feature-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        </div>
                        <h3>Studio Proposal & Skripsi</h3>
                        <p>Mendukung unggah buku pedoman kampus (.pdf/.docx), logo universitas untuk sampul, pengaturan sitasi Sinta/Scopus, serta revisi bab dari foto coretan dosen.</p>
                    </div>
                    <a href="{{ auth()->check() ? route('app.create', ['tab' => 'projects']) : route('login') }}" class="feature-link">Buka Studio →</a>
                </div>

                <div class="feature-card">
                    <div>
                        <div class="feature-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/></svg>
                        </div>
                        <h3>PPT Sidang Otomatis</h3>
                        <p>Pilih proyek skripsimu atau unggah file PDF/Word, sesuaikan tema slide dan isinya langsung di layar, lalu unduh file PowerPoint (.pptx).</p>
                    </div>
                    <a href="{{ auth()->check() ? route('app.create', ['tab' => 'ppt']) : route('login') }}" class="feature-link">Buat Slide PPT →</a>
                </div>

                <div class="feature-card">
                    <div>
                        <div class="feature-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                        </div>
                        <h3>Simulasi Sidang</h3>
                        <p>Jawab 5 pertanyaan penguji yang dibuat otomatis dari naskah skripsimu dan lihat rincian skor per pertanyaan beserta saran jawaban terbaik.</p>
                    </div>
                    <a href="{{ auth()->check() ? route('app.create', ['tab' => 'defense']) : route('login') }}" class="feature-link">Mulai Latihan →</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container footer-inner">
            <div class="brand" style="font-size: 15px;">
                <span class="brand-mark" style="width: 24px; height: 24px;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/></svg>
                </span>
                SkripsiAI
            </div>
            <div>&copy; {{ date('Y') }} SkripsiAI. Studio penulisan akademik mahasiswa Indonesia.</div>
        </div>
    </footer>

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
            document.querySelectorAll('.major-pill').forEach(b => b.classList.remove('active'));
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

        renderSampleStudio();
    </script>
</body>
</html>
