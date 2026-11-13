<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkripsiAI — Platform AI Penulisan Skripsi, Proposal & Simulasi Sidang #1 Indonesia</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,700;1,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --bg-light: #f8fafc;
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --text-main: #0f172a;
            --text-muted: #475569;
            --border-subtle: #e2e8f0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-main);
            background-color: #ffffff;
            overflow-x: hidden;
        }

        .serif-italic {
            font-family: 'Playfair Display', serif;
            font-style: italic;
        }

        /* --- NAVBAR & MEGA DROPDOWN FITUR --- */
        .site-navbar {
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border-subtle);
            padding: 14px 0;
            transition: all 0.25s ease;
        }

        .brand-title {
            font-weight: 800;
            font-size: 1.35rem;
            color: var(--text-main);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            letter-spacing: -0.5px;
        }

        .brand-badge-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: linear-gradient(135deg, #2563eb, #4f46e5);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .nav-link {
            font-weight: 600;
            color: #334155 !important;
            font-size: 0.94rem;
            padding: 8px 16px !important;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .nav-link:hover {
            color: var(--primary) !important;
            background: #f1f5f9;
        }

        /* Mega Menu Fitur ala Skripsita */
        .mega-dropdown-menu {
            width: min(92dvw, 680px);
            padding: 20px;
            border-radius: 20px;
            border: 1px solid var(--border-subtle);
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.16);
            margin-top: 10px !important;
        }

        .mega-item {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 12px 14px;
            border-radius: 12px;
            text-decoration: none;
            color: var(--text-main);
            transition: all 0.2s ease;
        }

        .mega-item:hover {
            background: #f1f5f9;
            transform: translateX(3px);
        }

        .mega-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            background: #f1f5f9;
            color: #334155;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 1.05rem;
            transition: all 0.2s ease;
        }

        .mega-item:hover .mega-icon {
            background: var(--primary);
            color: #ffffff;
        }

        .mega-title {
            font-weight: 700;
            font-size: 0.93rem;
            margin-bottom: 2px;
            color: #0f172a;
        }

        .mega-desc {
            font-size: 0.8rem;
            color: #64748b;
            margin-bottom: 0;
            line-height: 1.35;
        }

        /* --- HERO SECTION --- */
        .hero-section {
            padding: 140px 0 80px;
            background: radial-gradient(circle at 15% 20%, rgba(219, 234, 254, 0.7) 0%, transparent 45%),
                        radial-gradient(circle at 85% 15%, rgba(237, 233, 254, 0.7) 0%, transparent 45%),
                        linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
        }

        .hero-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 18px;
            background: #ffffff;
            border: 1px solid #bfdbfe;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--primary);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.08);
            margin-bottom: 24px;
        }

        .hero-headline {
            font-size: clamp(2.2rem, 1.5rem + 3vw, 4.1rem);
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -1.2px;
            margin-bottom: 22px;
            color: #0f172a;
        }

        .hero-headline .highlight {
            background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-subtext {
            font-size: clamp(1rem, 0.95rem + 0.3vw, 1.18rem);
            color: var(--text-muted);
            max-width: 720px;
            margin: 0 auto 36px;
            line-height: 1.75;
        }

        .btn-cta-main {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff;
            padding: 15px 34px;
            border-radius: 14px;
            font-weight: 700;
            font-size: 1rem;
            border: none;
            box-shadow: 0 12px 28px -6px rgba(37, 99, 235, 0.45);
            transition: all 0.25s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-cta-main:hover {
            transform: translateY(-2px);
            color: #fff;
            box-shadow: 0 18px 34px -6px rgba(37, 99, 235, 0.55);
        }

        .btn-cta-secondary {
            background: #ffffff;
            color: #0f172a;
            padding: 15px 28px;
            border-radius: 14px;
            font-weight: 700;
            font-size: 1rem;
            border: 1px solid #cbd5e1;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-cta-secondary:hover {
            background: #f8fafc;
            border-color: #94a3b8;
            color: #0f172a;
        }

        /* --- FEATURE PILLS BAR --- */
        .quick-tools-strip {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
            margin-top: 42px;
        }

        .tool-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background: #ffffff;
            border: 1px solid var(--border-subtle);
            border-radius: 50px;
            font-size: 0.88rem;
            font-weight: 600;
            color: #334155;
            text-decoration: none;
            box-shadow: 0 2px 6px rgba(0,0,0,0.03);
            transition: all 0.2s ease;
        }

        .tool-chip:hover {
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-2px);
        }

        /* --- SHOWCASE CONTOH GENERATE PROPOSAL USER --- */
        .showcase-section {
            padding: 90px 0;
            background: #f8fafc;
            border-top: 1px solid var(--border-subtle);
            border-bottom: 1px solid var(--border-subtle);
        }

        .proposal-browser-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid #cbd5e1;
            box-shadow: 0 24px 48px -12px rgba(15, 23, 42, 0.1);
            overflow: hidden;
        }

        .proposal-browser-top {
            background: #0f172a;
            color: #e2e8f0;
            padding: 14px 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        .window-dots {
            display: flex;
            gap: 7px;
        }

        .window-dots span {
            width: 11px;
            height: 11px;
            border-radius: 50%;
        }

        .sample-selector-btn {
            border: 1px solid #e2e8f0;
            background: #ffffff;
            color: #334155;
            padding: 10px 18px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.88rem;
            transition: all 0.2s ease;
        }

        .sample-selector-btn.active {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.25);
        }

        .chapter-tab-btn {
            border: none;
            background: transparent;
            padding: 10px 16px;
            font-weight: 700;
            font-size: 0.88rem;
            color: #64748b;
            border-bottom: 3px solid transparent;
            transition: all 0.2s ease;
        }

        .chapter-tab-btn.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
            background: rgba(37, 99, 235, 0.05);
        }

        .paper-preview-area {
            font-family: 'Times New Roman', Times, serif;
            font-size: 1.05rem;
            line-height: 1.8;
            color: #1e293b;
            background: #ffffff;
            padding: 32px 38px;
            max-height: 460px;
            overflow-y: auto;
            text-align: justify;
        }

        .paper-preview-area h4 {
            font-family: 'Times New Roman', Times, serif;
            font-weight: bold;
            font-size: 1.15rem;
            margin-top: 1rem;
            margin-bottom: 0.6rem;
            color: #000;
        }

        .paper-preview-area p {
            text-indent: 1.27cm;
            margin-bottom: 1rem;
        }

        /* --- FEATURE CARDS --- */
        .feature-box {
            background: #ffffff;
            border: 1px solid var(--border-subtle);
            border-radius: 18px;
            padding: 30px 26px;
            height: 100%;
            transition: all 0.25s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .feature-box:hover {
            border-color: #93c5fd;
            transform: translateY(-6px);
            box-shadow: 0 20px 36px -12px rgba(37, 99, 235, 0.14);
        }

        .feature-icon-lg {
            width: 54px;
            height: 54px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            margin-bottom: 20px;
        }

        /* --- SKILL TIAP BAB CARD --- */
        .skill-step-card {
            background: #ffffff;
            border: 1px solid var(--border-subtle);
            border-radius: 16px;
            padding: 24px;
            height: 100%;
            position: relative;
        }

        .testimonial-card {
            background: #ffffff;
            border: 1px solid var(--border-subtle);
            border-radius: 16px;
            padding: 26px;
            height: 100%;
            box-shadow: 0 4px 12px rgba(0,0,0,0.02);
        }

        footer {
            background: #0f172a;
            color: #94a3b8;
            padding: 70px 0 36px;
        }
    </style>
</head>
<body>

<!-- NAVBAR DENGAN MEGA DROPDOWN FITUR PERSIS REFERENSI -->
<nav class="navbar navbar-expand-lg fixed-top site-navbar">
    <div class="container">
        <a class="brand-title" href="#">
            <span class="brand-badge-icon"><i class="fas fa-feather-alt"></i></span>
            <span>SkripsiAI</span>
        </a>

        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <i class="fas fa-bars fs-4 text-dark"></i>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav mx-auto align-items-lg-center gap-lg-1 mt-3 mt-lg-0">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Fitur <i class="fas fa-chevron-down ms-1 small"></i>
                    </a>
                    <div class="dropdown-menu mega-dropdown-menu">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <a href="{{ route('app.create', ['tab' => 'titleScreen']) }}" class="mega-item">
                                    <div class="mega-icon"><i class="far fa-lightbulb"></i></div>
                                    <div>
                                        <div class="mega-title">Cari Judul Skripsi</div>
                                        <p class="mega-desc">Ide judul sesuai jurusan, variabel & metode riset</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="{{ route('app.create', ['tab' => 'proposalScreen']) }}" class="mega-item">
                                    <div class="mega-icon"><i class="far fa-file-lines"></i></div>
                                    <div>
                                        <div class="mega-title">Generator Proposal</div>
                                        <p class="mega-desc">Susun proposal Bab 1–3 dengan struktur DIKTI</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="{{ route('app.create', ['tab' => 'paraphraseScreen']) }}" class="mega-item">
                                    <div class="mega-icon"><i class="fas fa-wand-magic-sparkles"></i></div>
                                    <div>
                                        <div class="mega-title">Parafrase Online</div>
                                        <p class="mega-desc">Parafrase akademik KBBI & penurun skor Turnitin</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="{{ route('app.create', ['tab' => 'formScreen']) }}" class="mega-item">
                                    <div class="mega-icon"><i class="fas fa-book"></i></div>
                                    <div>
                                        <div class="mega-title">Full Skripsi Bab 1–5 Otomatis</div>
                                        <p class="mega-desc">Format margin 4-4-3-3, Times New Roman & TOC</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="{{ route('app.create', ['tab' => 'journalScreen']) }}" class="mega-item">
                                    <div class="mega-icon"><i class="fas fa-quote-right"></i></div>
                                    <div>
                                        <div class="mega-title">Cari Jurnal & Sitasi APA</div>
                                        <p class="mega-desc">Jurnal SINTA/Scopus 2020–2025 & Daftar Pustaka</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="{{ route('app.create', ['tab' => 'solverScreen']) }}" class="mega-item">
                                    <div class="mega-icon"><i class="fas fa-chart-column"></i></div>
                                    <div>
                                        <div class="mega-title">Olah Data & Konsultasi</div>
                                        <p class="mega-desc">Validitas, reliabilitas, regresi & tanya jawab soal</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="{{ route('app.create', ['tab' => 'pptScreen']) }}" class="mega-item">
                                    <div class="mega-icon"><i class="fas fa-desktop"></i></div>
                                    <div>
                                        <div class="mega-title">AI PPT Sidang</div>
                                        <p class="mega-desc">Slide sidang otomatis + script bicara presenter</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="{{ route('app.create', ['tab' => 'defenseScreen']) }}" class="mega-item">
                                    <div class="mega-icon"><i class="fas fa-user-tie"></i></div>
                                    <div>
                                        <div class="mega-title">Simulasi Sidang</div>
                                        <p class="mega-desc">Latihan tanya-jawab dengan Dosen Penguji AI</p>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </li>
                <li class="nav-item"><a class="nav-link" href="#contoh-proposal">Contoh Proposal User</a></li>
                <li class="nav-item"><a class="nav-link" href="#skill-bab">Standar Bab 1–5</a></li>
                <li class="nav-item"><a class="nav-link" href="#fitur-lengkap">Semua Fitur</a></li>
                <li class="nav-item"><a class="nav-link" href="#faq">FAQ</a></li>
            </ul>

            <div class="d-flex gap-2 align-items-center mt-3 mt-lg-0">
                @auth
                    <a href="{{ route('app.create') }}" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
                        <i class="fas fa-th-large me-1"></i> Buka Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-light rounded-pill px-4 fw-bold border">Masuk</a>
                    <a href="{{ route('register') }}" class="btn btn-dark rounded-pill px-4 fw-bold">Daftar Gratis</a>
                @endauth
            </div>
        </div>
    </div>
</nav>

<!-- HERO SECTION -->
<section class="hero-section text-center">
    <div class="container">
        <div class="hero-pill">
            <i class="fas fa-bolt text-warning"></i>
            <span>Dilengkapi Skill Standar Pedoman Skripsi DIKTI Indonesia</span>
        </div>

        <h1 class="hero-headline">
            Susun Proposal, Skripsi Bab 1–5,<br>
            hingga <span class="highlight serif-italic">Lulus Sidang</span> Lebih Cepat.
        </h1>

        <p class="hero-subtext">
            Satu platform AI akademik lengkap untuk mahasiswa Indonesia: mulai dari <strong>Cari Judul</strong>, <strong>Generator Proposal Bab 1–3</strong>, <strong>Full Skripsi Bab 1–5 Adaptif Metode</strong>, <strong>Parafrase Anti-Turnitin</strong>, <strong>AI PPT Sidang</strong>, hingga <strong>Simulasi Sidang Dosen AI</strong>.
        </p>

        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="{{ auth()->check() ? route('app.create') : route('register') }}" class="btn-cta-main">
                <span>Mulai Buat Skripsi Gratis</span>
                <i class="fas fa-arrow-right"></i>
            </a>
            <a href="#contoh-proposal" class="btn-cta-secondary">
                <i class="fas fa-eye text-primary"></i>
                <span>Lihat Contoh Hasil Proposal</span>
            </a>
        </div>

        <!-- PINTASAN FITUR LANGSUNG -->
        <div class="quick-tools-strip">
            <a href="{{ route('app.create', ['tab' => 'titleScreen']) }}" class="tool-chip"><i class="far fa-lightbulb text-success"></i> Cari Judul</a>
            <a href="{{ route('app.create', ['tab' => 'proposalScreen']) }}" class="tool-chip"><i class="fas fa-file-signature text-primary"></i> Generator Proposal</a>
            <a href="{{ route('app.create', ['tab' => 'journalScreen']) }}" class="tool-chip"><i class="fas fa-book-open text-warning"></i> Cari Jurnal</a>
            <a href="{{ route('app.create', ['tab' => 'paraphraseScreen']) }}" class="tool-chip"><i class="fas fa-wand-magic-sparkles text-danger"></i> Parafrase Online</a>
            <a href="{{ route('app.create', ['tab' => 'pptScreen']) }}" class="tool-chip"><i class="fas fa-desktop text-info"></i> Generate PPT Sidang</a>
            <a href="{{ route('app.create', ['tab' => 'defenseScreen']) }}" class="tool-chip"><i class="fas fa-user-tie text-success"></i> Simulasi Sidang</a>
        </div>
    </div>
</section>

<!-- SECTION BARU: CONTOH HASIL GENERATE PROPOSAL DARI USER (INTERAKTIF) -->
<section id="contoh-proposal" class="showcase-section">
    <div class="container">
        <div class="text-center mb-5">
            <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-2 fw-bold mb-2">LIVE OUTPUT SHOWCASE</span>
            <h2 class="fw-bold display-6 mb-2">Contoh Nyata Hasil Generate Proposal dari Pengguna</h2>
            <p class="text-secondary mx-auto" style="max-width: 680px;">
                Lihat bagaimana SkripsiAI menyusun naskah proposal dengan gaya bahasa akademik baku, piramida terbalik, sitasi jurnal nyata, dan metodologi yang presisi.
            </p>

            <!-- Pilihan Sampel Prodi User -->
            <div class="d-flex justify-content-center gap-2 flex-wrap mt-4">
                <button type="button" class="sample-selector-btn active" onclick="switchProposalSample(0, this)">
                    <i class="fas fa-laptop-code me-1"></i> Teknik Informatika (R&D - Waterfall)
                </button>
                <button type="button" class="sample-selector-btn" onclick="switchProposalSample(1, this)">
                    <i class="fas fa-chart-line me-1"></i> Manajemen Bisnis (Kuantitatif - Regresi)
                </button>
                <button type="button" class="sample-selector-btn" onclick="switchProposalSample(2, this)">
                    <i class="fas fa-scale-balanced me-1"></i> Ilmu Komunikasi / Sosial (Kualitatif)
                </button>
            </div>
        </div>

        <div class="proposal-browser-card mx-auto" style="max-width: 980px;">
            <div class="proposal-browser-top">
                <div class="d-flex align-items-center gap-3">
                    <div class="window-dots">
                        <span style="background:#ef4444;"></span>
                        <span style="background:#f59e0b;"></span>
                        <span style="background:#10b981;"></span>
                    </div>
                    <span class="small fw-semibold font-monospace text-white-50" id="sampleMetaBadge">
                        User: Andi Pratama • Prodi: Teknik Informatika • Metode: R&D (SDLC Waterfall)
                    </span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25">
                        <i class="fas fa-check-circle me-1"></i> Skor Turnitin &lt; 12%
                    </span>
                    <span class="badge bg-primary bg-opacity-25 text-info border border-info border-opacity-25">
                        Format Times New Roman 12pt
                    </span>
                </div>
            </div>

            <!-- Bar Judul & Tab Bab -->
            <div class="bg-light px-4 pt-3 border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-2">
                <div class="pb-2">
                    <small class="text-uppercase fw-bold text-primary" style="font-size: 0.72rem;">Judul Proposal Skripsi:</small>
                    <h5 class="fw-bold text-dark mb-0" id="sampleProposalTitle">
                        Rancang Bangun Sistem Deteksi Dini Penyakit Tanaman Padi Menggunakan Algoritma Convolutional Neural Network (CNN) Berbasis Web
                    </h5>
                </div>
                <div class="d-flex gap-1 flex-shrink-0">
                    <button type="button" class="chapter-tab-btn active" onclick="switchSampleChapter('bab1', this)">BAB I Pendahuluan</button>
                    <button type="button" class="chapter-tab-btn" onclick="switchSampleChapter('bab2', this)">BAB II Tinjauan Pustaka</button>
                    <button type="button" class="chapter-tab-btn" onclick="switchSampleChapter('bab3', this)">BAB III Metodologi</button>
                </div>
            </div>

            <!-- Area Kertas Skripsi -->
            <div class="paper-preview-area" id="samplePaperContent">
                <!-- Diisi otomatis oleh JS -->
            </div>

            <div class="bg-light px-4 py-3 border-top d-flex flex-wrap justify-content-between align-items-center gap-2">
                <small class="text-secondary">
                    <i class="fas fa-file-word text-primary me-1"></i> Siap diunduh dalam format <strong>.DOCX (Microsoft Word)</strong> &amp; <strong>.PDF</strong> lengkap dengan Daftar Isi &amp; Daftar Pustaka APA 7th.
                </small>
                <a href="{{ route('app.create', ['tab' => 'proposalScreen']) }}" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold">
                    Buat Proposal Seperti Ini <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- SECTION SKILL PER BAB STANDAR SKRIPSI INDONESIA -->
<section id="skill-bab" class="py-5 my-4">
    <div class="container">
        <div class="text-center mb-5">
            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-2 fw-bold mb-2">ARSITEKTUR SKILL TIAP BAB</span>
            <h2 class="fw-bold display-6 mb-2">Mengapa Hasil Generate SkripsiAI Sesuai Standar Dosen Indonesia?</h2>
            <p class="text-secondary mx-auto" style="max-width: 700px;">
                Berbeda dari ChatGPT biasa yang menghasilkan tulisan pendek dan seragam, SkripsiAI ditanamkan <strong>Skill Khusus per Bab &amp; Adaptif Metode Penelitian</strong> sesuai buku pedoman universitas di Indonesia.
            </p>
        </div>

        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <div class="skill-step-card">
                    <span class="badge bg-primary mb-3">SKILL BAB I</span>
                    <h5 class="fw-bold">Piramida Terbalik &amp; Research Gap</h5>
                    <p class="text-secondary small mb-0">
                        Menyusun Latar Belakang 5 lapis: fenomena makro, kondisi ideal (<em>Das Sollen</em>) vs realita lapangan (<em>Das Sein</em>), kutipan jurnal terbaru, <em>research gap</em>, hingga sinkronisasi Rumusan &amp; Tujuan Penelitian.
                    </p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="skill-step-card">
                    <span class="badge bg-primary mb-3">SKILL BAB II</span>
                    <h5 class="fw-bold">Grand, Middle &amp; Applied Theory</h5>
                    <p class="text-secondary small mb-0">
                        Membedah definisi variabel menurut para ahli, dimensi &amp; indikator, sintesis minimal 5 Penelitian Terdahulu lengkap dengan tabel matriks komparasi (<em>State of the Art</em>), Kerangka Pikir, dan Hipotesis.
                    </p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="skill-step-card">
                    <span class="badge bg-primary mb-3">SKILL BAB III (ADAPTIF)</span>
                    <h5 class="fw-bold">Otomatis Sesuai Metode Riset</h5>
                    <p class="text-secondary small mb-0">
                        Otomatis membedakan struktur sub-bab: <strong>Kuantitatif</strong> (Slovin, Validitas, Asumsi Klasik, Regresi), <strong>Kualitatif</strong> (Informan Kunci, Triangulasi, Miles &amp; Huberman), atau <strong>R&amp;D IT</strong> (SDLC Waterfall/Agile, UML, Black-Box).
                    </p>
                </div>
            </div>
            <div class="col-md-6 col-lg-6">
                <div class="skill-step-card">
                    <span class="badge bg-success mb-3">SKILL BAB IV</span>
                    <h5 class="fw-bold">Hasil Analisis &amp; Dialog Teoretis Mendalam</h5>
                    <p class="text-secondary small mb-0">
                        Bukan sekadar ringkasan, melainkan menginterpretasikan temuan lapangan/pengujian sistem dan membandingkannya secara ilmiah dengan teori para ahli serta jurnal terdahulu di Bab II.
                    </p>
                </div>
            </div>
            <div class="col-md-12 col-lg-6">
                <div class="skill-step-card">
                    <span class="badge bg-dark mb-3">SKILL BAB V &amp; SITASI</span>
                    <h5 class="fw-bold">Kesimpulan Presisi &amp; Ekstraksi Daftar Pustaka APA 7th</h5>
                    <p class="text-secondary small mb-0">
                        Menjawab rumusan masalah poin demi poin secara lugas, lalu mengekstrak seluruh sitasi <code>(Nama, Tahun)</code> yang muncul di Bab I–IV menjadi Daftar Pustaka format APA Style 7th Edition berurutan A–Z.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SECTION SEMUA FITUR UNGGULAN -->
<section id="fitur-lengkap" class="py-5 bg-light border-top border-bottom">
    <div class="container py-3">
        <div class="text-center mb-5">
            <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-2 fw-bold mb-2">EKOSISTEM LENGKAP</span>
            <h2 class="fw-bold display-6 mb-2">Semua Alat yang Anda Butuhkan dari Sempro hingga Wisuda</h2>
            <p class="text-secondary mx-auto" style="max-width: 640px;">
                Dirancang agar ringan, cepat, dan terintegrasi dalam satu workspace.
            </p>
        </div>

        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <div class="feature-box">
                    <div>
                        <div class="feature-icon-lg bg-primary bg-opacity-10 text-primary"><i class="fas fa-file-signature"></i></div>
                        <h5 class="fw-bold">Generator Proposal (Bab 1–3)</h5>
                        <p class="text-secondary small">Susun draf proposal skripsi untuk Seminar Proposal lengkap dengan latar belakang piramida terbalik, kajian pustaka, dan metodologi.</p>
                    </div>
                    <a href="{{ route('app.create', ['tab' => 'proposalScreen']) }}" class="text-primary fw-bold small text-decoration-none mt-2">Coba Generator Proposal <i class="fas fa-arrow-right ms-1"></i></a>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="feature-box">
                    <div>
                        <div class="feature-icon-lg bg-success bg-opacity-10 text-success"><i class="far fa-lightbulb"></i></div>
                        <h5 class="fw-bold">Cari Judul &amp; Topik Skripsi</h5>
                        <p class="text-secondary small">Hasilkan 10 ide judul skripsi spesifik yang memiliki kebaruan (novelty), variabel X/Y yang terukur, dan objek penelitian yang realistis.</p>
                    </div>
                    <a href="{{ route('app.create', ['tab' => 'titleScreen']) }}" class="text-success fw-bold small text-decoration-none mt-2">Cari Ide Judul <i class="fas fa-arrow-right ms-1"></i></a>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="feature-box">
                    <div>
                        <div class="feature-icon-lg bg-warning bg-opacity-10 text-warning"><i class="fas fa-book-open"></i></div>
                        <h5 class="fw-bold">Pencari Jurnal Ilmiah</h5>
                        <p class="text-secondary small">Temukan rekomendasi jurnal nasional terakreditasi SINTA dan jurnal internasional 5 tahun terakhir lengkap dengan tautan Google Scholar.</p>
                    </div>
                    <a href="{{ route('app.create', ['tab' => 'journalScreen']) }}" class="text-warning text-dark fw-bold small text-decoration-none mt-2">Cari Referensi Jurnal <i class="fas fa-arrow-right ms-1"></i></a>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="feature-box">
                    <div>
                        <div class="feature-icon-lg bg-danger bg-opacity-10 text-danger"><i class="fas fa-wand-magic-sparkles"></i></div>
                        <h5 class="fw-bold">Parafrase Online Anti-Turnitin</h5>
                        <p class="text-secondary small">Ubah struktur kalimat secara cerdas dengan bahasa ilmiah baku sesuai KBBI &amp; EYD V untuk menurunkan persentase kemiripan plagiarisme.</p>
                    </div>
                    <a href="{{ route('app.create', ['tab' => 'paraphraseScreen']) }}" class="text-danger fw-bold small text-decoration-none mt-2">Buka Alat Parafrase <i class="fas fa-arrow-right ms-1"></i></a>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="feature-box">
                    <div>
                        <div class="feature-icon-lg bg-info bg-opacity-10 text-info"><i class="fas fa-desktop"></i></div>
                        <h5 class="fw-bold">Generator AI PPT Sidang</h5>
                        <p class="text-secondary small">Otomatis mengubah judul dan isi skripsi menjadi 9 slide presentasi sidang yang ringkas beserta naskah bicara presenter tiap slide.</p>
                    </div>
                    <a href="{{ route('app.create', ['tab' => 'pptScreen']) }}" class="text-info text-dark fw-bold small text-decoration-none mt-2">Buat PPT Sidang <i class="fas fa-arrow-right ms-1"></i></a>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="feature-box">
                    <div>
                        <div class="feature-icon-lg" style="background:#ede9fe;color:#7c3aed;"><i class="fas fa-user-tie"></i></div>
                        <h5 class="fw-bold">Simulasi Sidang Skripsi AI</h5>
                        <p class="text-secondary small">Hadapi pertanyaan tajam dari Dosen Penguji AI, dapatkan evaluasi skor penguasaan (0–100), dan pelajari contoh jawaban standar nilai A.</p>
                    </div>
                    <a href="{{ route('app.create', ['tab' => 'defenseScreen']) }}" class="fw-bold small text-decoration-none mt-2" style="color:#7c3aed;">Mulai Simulasi Sidang <i class="fas fa-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- TESTIMONI MAHASISWA -->
<section class="py-5 my-4">
    <div class="container">
        <div class="text-center mb-5">
            <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-2 fw-bold mb-2">CERITA MAHASISWA</span>
            <h2 class="fw-bold display-6">Dipercaya Mahasiswa dari Berbagai Kampus</h2>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="testimonial-card">
                    <div class="text-warning mb-2"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                    <p class="text-secondary small mb-4">"Fitur Generator Proposal dan Skill Bab 3 R&amp;D-nya beneran paham struktur SDLC Waterfall dan Black-Box testing. Dosen pembimbing saya langsung ACC Bab 1-3 dengan revisi minor!"</p>
                    <div class="fw-bold text-dark">Rizky Ramadhan</div>
                    <small class="text-muted">Mahasiswa S1 Teknik Informatika</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="testimonial-card">
                    <div class="text-warning mb-2"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                    <p class="text-secondary small mb-4">"Simulasi Sidang dan AI PPT Sidang-nya sangat membantu malam sebelum sempro. Pertanyaan yang diajukan Dosen AI ternyata keluar persis waktu sidang beneran!"</p>
                    <div class="fw-bold text-dark">Nabila Putri</div>
                    <small class="text-muted">Mahasiswa S1 Manajemen</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="testimonial-card">
                    <div class="text-warning mb-2"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                    <p class="text-secondary small mb-4">"Export Word-nya rapi banget, sudah otomatis Times New Roman 12, margin 4-4-3-3, dan daftar pustaka APA-nya urut abjad. Hemat waktu berminggu-minggu."</p>
                    <div class="fw-bold text-dark">Fajar Pratama</div>
                    <small class="text-muted">Mahasiswa S1 Ilmu Hukum</small>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ SECTION -->
<section id="faq" class="py-5 bg-light border-top">
    <div class="container" style="max-width: 820px;">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Pertanyaan yang Sering Diajukan (FAQ)</h2>
        </div>
        <div class="accordion" id="faqAcc">
            <div class="accordion-item rounded-4 mb-3 border overflow-hidden">
                <h2 class="accordion-header">
                    <button class="accordion-button fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#q1">
                        Apakah bisa menggunakan API Key gratisan (Gemini / Groq)?
                    </button>
                </h2>
                <div id="q1" class="accordion-collapse collapse show" data-bs-parent="#faqAcc">
                    <div class="accordion-body text-secondary">
                        Ya! SkripsiAI mendukung penuh API Key gratis dari <strong>Google AI Studio (Gemini 2.0 Flash / 1.5 Flash)</strong> dan <strong>Groq Cloud (Llama 3.3 70B)</strong>. Anda bisa menambahkannya kapan saja di menu pengaturan API Key pada dashboard.
                    </div>
                </div>
            </div>
            <div class="accordion-item rounded-4 mb-3 border overflow-hidden">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#q2">
                        Apakah struktur Bab 1 sampai Bab 5 menyesuaikan metode penelitian?
                    </button>
                </h2>
                <div id="q2" class="accordion-collapse collapse" data-bs-parent="#faqAcc">
                    <div class="accordion-body text-secondary">
                        Benar. Sistem kami memiliki skill adaptif: jika Anda memilih metode <strong>Kuantitatif</strong>, Bab 3 &amp; 4 otomatis membahas populasi Slovin, uji validitas/reliabilitas, asumsi klasik, dan regresi. Jika memilih <strong>R&amp;D/IT</strong>, otomatis membahas SDLC, UML, ERD, dan Black-Box testing. Jika <strong>Kualitatif</strong>, membahas informan, triangulasi, dan analisis Miles &amp; Huberman.
                    </div>
                </div>
            </div>
            <div class="accordion-item rounded-4 mb-3 border overflow-hidden">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#q3">
                        Apakah hasil dokumen bisa langsung diunduh ke Microsoft Word (.docx)?
                    </button>
                </h2>
                <div id="q3" class="accordion-collapse collapse" data-bs-parent="#faqAcc">
                    <div class="accordion-body text-secondary">
                        Bisa! Seluruh naskah dapat diunduh dalam format <strong>.DOCX (Word)</strong> dan <strong>.PDF</strong> lengkap dengan halaman cover, daftar isi otomatis, penomoran sub-bab rapi, dan daftar pustaka APA Style.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer>
    <div class="container">
        <div class="row g-4 justify-content-between align-items-center">
            <div class="col-md-6">
                <a class="brand-title text-white mb-2" href="#">
                    <span class="brand-badge-icon"><i class="fas fa-feather-alt"></i></span>
                    <span>SkripsiAI</span>
                </a>
                <p class="small mb-0 mt-2" style="max-width: 420px;">
                    Platform AI Asisten Penulisan Skripsi, Generator Proposal, Parafrase Akademik, dan Simulasi Sidang untuk Mahasiswa Indonesia.
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <small>&copy; {{ date('Y') }} SkripsiAI Indonesia. All rights reserved.</small>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // DATA CONTOH GENERATE PROPOSAL NYATA DARI USER
    const proposalSamples = [
        {
            meta: "User: Andi Pratama • Prodi: Teknik Informatika • Metode: R&D (SDLC Waterfall)",
            title: "Rancang Bangun Sistem Deteksi Dini Penyakit Tanaman Padi Menggunakan Algoritma Convolutional Neural Network (CNN) Berbasis Web",
            bab1: `
                <h4>1.1 Latar Belakang Masalah</h4>
                <p>Sektor pertanian memegang peranan strategis dalam menjaga ketahanan pangan nasional di Indonesia, di mana komoditas padi (Oryza sativa) menjadi sumber karbohidrat utama bagi lebih dari 95% penduduk (Badan Pusat Statistik, 2024). Namun, produktivitas hasil panen padi sering mengalami penurunan signifikan akibat serangan patogen penyakit daun seperti hawar daun bakteri (bacterial leaf blight), bercak coklat (brown spot), dan penyakit blas (Magnaporthe oryzae). Menurut penelitian Rahmawati dan Nugroho (2023), keterlambatan identifikasi gejala visual pada daun padi dapat menyebabkan gagal panen hingga mencapai 35% sampai 50% pada satu musim tanam.</p>
                <p>Secara ideal (Das Sollen), proses diagnosis penyakit tanaman padi harus dilakukan secara cepat, objektif, dan akurat sejak gejala awal muncul pada permukaan helai daun. Akan tetapi, kondisi faktual di lapangan (Das Sein) menunjukkan bahwa sebagian besar petani masih mengandalkan pengamatan kasat mata secara konvensional yang bersifat subjektif dan terbatas oleh ketersediaan penyuluh pertanian lapangan. Kesenjangan (research gap) dari studi terdahulu yang dilakukan oleh Pratama et al. (2022) menunjukkan bahwa model klasifikasi citra konvensional berbasis ekstraksi fitur warna manual masih memiliki akurasi rendah ketika dihadapkan pada variasi pencahayaan lapangan.</p>
                <h4>1.2 Rumusan Masalah</h4>
                <p>Berdasarkan latar belakang masalah di atas, maka rumusan masalah dalam penelitian ini adalah: (1) Bagaimana merancang dan membangun arsitektur model Convolutional Neural Network (CNN) untuk mengklasifikasikan citra penyakit daun padi secara akurat? (2) Bagaimana mengintegrasikan model CNN ke dalam sistem berbasis web yang responsif dan mudah diakses oleh petani maupun penyuluh pertanian?</p>
            `,
            bab2: `
                <h4>2.1 Landasan Teori</h4>
                <p>Convolutional Neural Network (CNN) merupakan salah satu arsitektur Deep Learning yang dirancang khusus untuk memproses data berformat grid dua dimensi seperti citra digital (Goodfellow et al., 2021). Arsitektur CNN terdiri atas tiga lapisan utama, yaitu Convolutional Layer untuk mengekstraksi peta fitur lokal (feature map), Pooling Layer untuk mereduksi dimensi spasial citra, serta Fully Connected Layer untuk menghasilkan probabilitas klasifikasi melalui fungsi aktivasi Softmax (Suryani & Hidayat, 2023).</p>
                <h4>2.2 Penelitian Terdahulu</h4>
                <p>Penelitian pertama oleh Kusuma dan Wijaya (2023) berjudul "Klasifikasi Penyakit Daun Padi Menggunakan Arsitektur MobileNetV2" berhasil memperoleh akurasi pengujian sebesar 92,4%, namun belum diimplementasikan ke dalam antarmuka sistem informasi berbasis web. Penelitian kedua oleh Setiawan et al. (2024) menerapkan metode Support Vector Machine (SVM) dengan akurasi 84,5%. Kebaruan (state of the art) pada penelitian ini terletak pada optimasi hiperparameter arsitektur CNN yang terintegrasi langsung dengan platform web real-time untuk diagnosis lapangan.</p>
            `,
            bab3: `
                <h4>3.1 Metode Pengembangan Sistem</h4>
                <p>Penelitian ini menggunakan metode Research and Development (R&D) dengan model pengembangan perangkat lunak System Development Life Cycle (SDLC) Waterfall menurut Pressman (2020). Tahapan penelitian meliputi: (1) Requirement Analysis, yaitu pengumpulan dataset 2.400 citra daun padi; (2) System & Model Design, yakni perancangan arsitektur CNN, Use Case Diagram, Activity Diagram, dan Entity Relationship Diagram (ERD); (3) Implementation, yaitu pelatihan model dan pengembangan web; serta (4) System Testing.</p>
                <h4>3.2 Metode Pengujian Sistem</h4>
                <p>Evaluasi performa model klasifikasi dilakukan menggunakan Confusion Matrix untuk menghitung nilai Accuracy, Precision, Recall, dan F1-Score. Sementara itu, pengujian fungsionalitas aplikasi web dilakukan menggunakan metode Black-Box Testing (Equivalence Partitioning) untuk memastikan seluruh modul unggah citra dan laporan hasil diagnosis berjalan valid tanpa galat.</p>
            `
        },
        {
            meta: "User: Siti Rahma • Prodi: Manajemen Bisnis • Metode: Kuantitatif (Regresi Linear Berganda)",
            title: "Pengaruh Content Marketing, Live Streaming, dan Online Customer Review Terhadap Keputusan Pembelian Produk Fashion Lokal di TikTok Shop",
            bab1: `
                <h4>1.1 Latar Belakang Masalah</h4>
                <p>Transformasi digital telah mengubah lanskap perdagangan elektronik (social commerce) di Indonesia secara fundamental, di mana integrasi konten hiburan pendek dengan fitur transaksi langsung mendorong pertumbuhan nilai transaksi ritel daring secara eksponensial (Kotler et al., 2022). Fenomena ini menuntut pelaku usaha merek fashion lokal untuk mengoptimalkan strategi komunikasi pemasaran interaktif guna mempertahankan daya saing pasar.</p>
                <p>Meskipun adopsi fitur Live Streaming dan Content Marketing meningkat pesat, data survei Asosiasi E-Commerce Indonesia (2024) mengindikasikan bahwa tingkat konversi penjualan pada merek lokal masih mengalami fluktuasi tajam akibat keraguan konsumen terhadap kualitas produk aktual. Penelitian Saputra dan Lestari (2023) menemukan bahwa Content Marketing berpengaruh signifikan terhadap minat beli, sedangkan studi Handayani (2022) justru menyatakan bahwa konten promosi tanpa dukungan Online Customer Review yang kredibel tidak mampu mendorong keputusan pembelian nyata. Adanya inkonsistensi temuan tersebut (research gap) mendorong perlunya pengujian empiris secara simultan.</p>
                <h4>1.2 Rumusan Masalah</h4>
                <p>Rumusan masalah dalam penelitian ini meliputi: (1) Apakah Content Marketing berpengaruh positif dan signifikan terhadap Keputusan Pembelian? (2) Apakah Live Streaming berpengaruh positif dan signifikan terhadap Keputusan Pembelian? (3) Apakah Online Customer Review berpengaruh positif dan signifikan secara parsial maupun simultan terhadap Keputusan Pembelian?</p>
            `,
            bab2: `
                <h4>2.1 Landasan Teori</h4>
                <p>Menurut Kotler dan Armstrong (2021), Keputusan Pembelian merupakan tahapan evaluasi dalam proses pengambilan keputusan konsumen di mana individu benar-benar memilih dan membeli suatu produk di antara berbagai alternatif merek yang tersedia. Indikator keputusan pembelian mencakup kemantapan pada sebuah produk, kebiasaan dalam membeli produk, kecepatan dalam memutuskan pembelian, serta kesediaan memberikan rekomendasi kepada orang lain (Tjiptono, 2022).</p>
                <h4>2.2 Kerangka Pemikiran dan Hipotesis</h4>
                <p>Berdasarkan landasan teori Stimulus-Organism-Response (S-O-R), paparan Content Marketing (X1) dan interaksi sinkron pada Live Streaming (X2) berperan sebagai stimulus eksternal, sementara Online Customer Review (X3) memperkuat persepsi kepercayaan kognitif konsumen yang bermuara pada respon perilaku berupa Keputusan Pembelian (Y). Dengan demikian, diajukan hipotesis H1, H2, H3, dan H4 bahwa ketiga variabel independen berpengaruh positif dan signifikan baik secara parsial maupun simultan.</p>
            `,
            bab3: `
                <h4>3.1 Populasi dan Sampel Penelitian</h4>
                <p>Pendekatan penelitian yang digunakan adalah kuantitatif dengan desain asosiatif kausal. Populasi dalam penelitian ini adalah konsumen generasi Z yang pernah melakukan pembelian produk fashion lokal melalui platform TikTok Shop. Teknik pengambilan sampel menggunakan Non-Probability Sampling dengan metode Purposive Sampling. Mengacu pada rumus Hair et al. (2021), ukuran sampel minimum ditetapkan sebanyak 150 responden dengan skala pengukuran Likert 1 sampai 5.</p>
                <h4>3.2 Teknik Analisis Data dan Uji Hipotesis</h4>
                <p>Tahapan analisis data dilakukan menggunakan perangkat lunak statistik yang mencakup: (1) Uji Instrumen (Uji Validitas Pearson Product Moment dan Uji Reliabilitas Cronbach's Alpha > 0,70); (2) Uji Asumsi Klasik (Uji Normalitas Kolmogorov-Smirnov, Uji Multikolinearitas VIF < 10, dan Uji Heteroskedastisitas Glejser); serta (3) Analisis Regresi Linear Berganda, Uji Parsial (Uji t), Uji Kelayakan Model (Uji F), dan Koefisien Determinasi (R-Square).</p>
            `
        },
        {
            meta: "User: Dimas Aditya • Prodi: Ilmu Komunikasi • Metode: Kualitatif (Studi Kasus Deskriptif)",
            title: "Strategi Komunikasi Krisis Public Relations dalam Memulihkan Reputasi Korporasi Pasca Insiden Kebocoran Data Pelanggan",
            bab1: `
                <h4>1.1 Latar Belakang Masalah</h4>
                <p>Di era ekonomi digital, keamanan data pribadi pelanggan merupakan aset kepercayaan paling krusial bagi keberlangsungan reputasi sebuah institusi korporasi. Insiden kebocoran data siber tidak hanya menimbulkan kerugian operasional, tetapi juga memicu krisis kepercayaan publik yang teramplifikasi secara masif melalui opini media sosial dalam hitungan jam (Coombs, 2022).</p>
                <p>Kajian ini berfokus pada bagaimana praktisi Public Relations merumuskan strategi respons krisis yang transparan, empatik, dan akuntabel untuk meredam sentimen negatif publik serta memulihkan legitimasi institusi di mata pemangku kepentingan (stakeholders).</p>
            `,
            bab2: `
                <h4>2.1 Landasan Teori (Situational Crisis Communication Theory)</h4>
                <p>Penelitian ini menggunakan pijakan utama Situational Crisis Communication Theory (SCCT) yang dikembangkan oleh W. Timothy Coombs (2021). Teori SCCT memetakan strategi respons komunikasi krisis ke dalam empat klaster utama, yaitu Denial Posture (penyangkalan), Diminish Posture (pengurangan atribusi), Rebuild Posture (pembangunan kembali kepercayaan melalui kompensasi dan permintaan maaf terbuka), serta Bolstering Posture.</p>
            `,
            bab3: `
                <h4>3.1 Pendekatan Penelitian dan Teknik Penentuan Informan</h4>
                <p>Penelitian ini menggunakan pendekatan kualitatif dengan metode Studi Kasus (Yin, 2020). Pemilihan informan dilakukan secara Purposive Sampling yang terdiri atas Informan Kunci (Head of Corporate Communications), Informan Utama (Tim Manajemen Krisis & Keamanan Siber), serta Informan Pendukung (perwakilan nasabah dan pengamat komunikasi publik).</p>
                <h4>3.2 Uji Keabsahan dan Analisis Data</h4>
                <p>Keabsahan data diuji menggunakan teknik Triangulasi Sumber dan Triangulasi Teknik untuk memastikan kredibilitas temuan lapangan. Seluruh transkrip wawancara mendalam dianalisis menggunakan model interaktif Miles, Huberman, dan Saldana (2019) yang meliputi tahapan Kondensasi Data (Data Condensation), Penyajian Data (Data Display), serta Penarikan Kesimpulan dan Verifikasi.</p>
            `
        }
    ];

    let currentSampleIdx = 0;
    let currentChapterKey = 'bab1';

    function renderProposalSample() {
        const sample = proposalSamples[currentSampleIdx];
        document.getElementById('sampleMetaBadge').innerText = sample.meta;
        document.getElementById('sampleProposalTitle').innerText = sample.title;
        document.getElementById('samplePaperContent').innerHTML = sample[currentChapterKey];
    }

    function switchProposalSample(idx, btn) {
        currentSampleIdx = idx;
        document.querySelectorAll('.sample-selector-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        renderProposalSample();
    }

    function switchSampleChapter(chapterKey, btn) {
        currentChapterKey = chapterKey;
        document.querySelectorAll('.chapter-tab-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        renderProposalSample();
    }

    document.addEventListener('DOMContentLoaded', () => {
        renderProposalSample();
    });
</script>
</body>
</html>
