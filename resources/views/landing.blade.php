<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkripsiAI - Asisten Penulisan Akademik Cerdas</title>
    <!-- Fonts: Inter for reading, Playfair Display for academic feel -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,600;0,700;1,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-bg: #f8fafc;
            --accent-color: #2563eb;
            --accent-hover: #1d4ed8;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --card-bg: rgba(255, 255, 255, 0.8);
            --border-color: #e2e8f0;
        }

        body {
            font-family: 'Inter', sans-serif;
            color: var(--text-dark);
            background-color: var(--primary-bg);
            background-image: 
                radial-gradient(at 0% 0%, hsla(217, 100%, 96%, 1) 0px, transparent 50%),
                radial-gradient(at 100% 0%, hsla(210, 100%, 96%, 1) 0px, transparent 50%);
            background-repeat: no-repeat;
            background-attachment: fixed;
            overflow-x: hidden;
        }

        h1, h2, h3, .brand-font {
            font-family: 'Playfair Display', serif;
        }

        /* --- NAVBAR --- */
        .navbar {
            padding: 15px 0;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-bottom: 1px solid rgba(255,255,255,0.3);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        .navbar.scrolled {
            padding: 10px 0;
            background: rgba(255, 255, 255, 0.95);
        }
        .navbar-brand { font-weight: 700; font-size: 1.5rem; color: var(--text-dark) !important; letter-spacing: -0.5px; }
        .nav-link { font-weight: 500; color: var(--text-muted) !important; transition: 0.3s; font-size: 0.95rem; margin: 0 10px; }
        .nav-link:hover { color: var(--accent-color) !important; transform: translateY(-1px); }
        .btn-nav-cta {
            background: var(--text-dark); color: white; border-radius: 8px;
            padding: 10px 24px; font-weight: 600; font-size: 0.9rem; transition: 0.3s;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .btn-nav-cta:hover { background: var(--accent-color); color: white; transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.3); }

        /* --- HERO SECTION --- */
        .hero-section {
            padding: 160px 0 100px;
            text-align: center;
            position: relative;
        }
        .hero-badge {
            background: linear-gradient(135deg, #eef2ff 0%, #ffffff 100%);
            color: var(--accent-color); font-weight: 600; font-size: 0.85rem;
            padding: 8px 20px; border-radius: 50px; display: inline-block; margin-bottom: 30px;
            border: 1px solid rgba(37, 99, 235, 0.2);
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            animation: fadeInDown 0.8s ease-out;
        }
        .hero-title {
            font-size: 4.5rem; font-weight: 700; line-height: 1.15; margin-bottom: 24px;
            color: var(--text-dark);
            animation: fadeInUp 1s ease-out 0.2s both;
        }
        .hero-title span {
            /* Text highlight or gradient */
            background: linear-gradient(135deg, var(--accent-color) 0%, #60a5fa 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-style: italic;
        }
        .hero-desc { font-size: 1.15rem; color: var(--text-muted); max-width: 700px; margin: 0 auto 40px; line-height: 1.8; animation: fadeInUp 1s ease-out 0.4s both; font-family: 'Inter', sans-serif;}
        .btn-hero {
            padding: 16px 45px; font-size: 1.05rem; border-radius: 12px; font-weight: 600;
            transition: all 0.3s ease; border: none; letter-spacing: 0.5px;
        }
        .btn-hero-primary { background: var(--accent-color); color: white; box-shadow: 0 10px 25px -5px rgba(37, 99, 235, 0.4); }
        .btn-hero-primary:hover { background: var(--accent-hover); color: white; transform: translateY(-3px); box-shadow: 0 20px 35px -5px rgba(37, 99, 235, 0.5); }
        
        .hero-actions { animation: fadeInUp 1s ease-out 0.6s both; }

        /* --- DASHBOARD PREVIEW --- */
        .dashboard-preview {
            margin-top: 80px;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255,255,255,0.6);
            overflow: hidden;
            background: rgba(255,255,255,0.5);
            backdrop-filter: blur(20px);
            animation: zoomIn 1s ease-out 0.8s both;
        }
        .mockup-header {
            background: rgba(248, 250, 252, 0.8);
            border-bottom: 1px solid var(--border-color);
            padding: 12px 20px;
        }
        
        /* --- FEATURES --- */
        .features-section { padding: 100px 0; background: white;  position: relative;}
        .section-title { font-size: 3rem; font-weight: 700; color: var(--text-dark); margin-bottom: 1rem; }
        .section-subtitle { text-transform: uppercase; letter-spacing: 2px; font-weight: 700; color: var(--accent-color); font-size: 0.85rem; margin-bottom: 0.5rem; font-family: 'Inter', sans-serif;}
        
        .feature-card {
            padding: 40px 30px; border-radius: 16px; background: #ffffff; 
            border: 1px solid var(--border-color);
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1); height: 100%;
            position: relative;
            z-index: 1;
        }
        .feature-card::after {
            content: ""; position: absolute; inset: 0; border-radius: 16px;
            box-shadow: 0 20px 40px -10px rgba(0,0,0,0.08); opacity: 0; transition: opacity 0.4s ease; z-index: -1;
        }
        .feature-card:hover { transform: translateY(-8px); border-color: transparent; }
        .feature-card:hover::after { opacity: 1; }
        
        .feature-icon-box {
            width: 60px; height: 60px; border-radius: 14px; display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; margin-bottom: 25px; transition: transform 0.3s ease;
        }
        .feature-card:hover .feature-icon-box { transform: scale(1.1) rotate(5deg); }
        .feature-title { font-weight: 700; margin-bottom: 15px; font-size: 1.25rem; font-family: 'Inter', sans-serif; color: #1e293b;}
        .feature-desc { color: var(--text-muted); line-height: 1.7; font-size: 0.95rem; }

        /* --- WORKFLOW --- */
        .workflow-section { padding: 100px 0; background: #f8fafc; }
        .step-card {
            background: #ffffff; padding: 40px 30px; border-radius: 20px;
            box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.03);
            height: 100%; position: relative; overflow: hidden;
            transition: all 0.3s ease;
        }
        .step-card:hover { transform: translateY(-5px); box-shadow: 0 20px 40px -10px rgba(0,0,0,0.08); }
        .step-number {
            font-size: 6rem; font-weight: 700; color: #f1f5f9; position: absolute; top: -10px; right: 10px; z-index: 0;
            line-height: 1; font-family: 'Playfair Display', serif; font-style: italic;
        }
        .step-content { position: relative; z-index: 1; }

        /* --- FAQ --- */
        .accordion-button { font-weight: 600; color: var(--text-dark); padding: 20px; font-family: 'Inter', sans-serif;}
        .accordion-button:not(.collapsed) { background-color: #f8fafc; color: var(--accent-color); box-shadow: none; }
        .accordion-button:focus { box-shadow: none; border-color: rgba(0,0,0,0.1); }
        .accordion-body { color: var(--text-muted); line-height: 1.8; padding: 0 20px 20px; background-color: #f8fafc;}
        .accordion-item { border: 1px solid var(--border-color); border-radius: 12px !important; margin-bottom: 15px; overflow: hidden; }

        /* --- FOOTER --- */
        footer { border-top: 1px solid var(--border-color); padding: 80px 0 40px; background: #ffffff; }
        .footer-logo { font-weight: 700; font-size: 1.4rem; color: var(--text-dark); text-decoration: none; }
        .footer-link { color: var(--text-muted); text-decoration: none; transition: 0.2s; }
        .footer-link:hover { color: var(--accent-color); }
        .social-icon { width: 40px; height: 40px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; background: #f1f5f9; color: var(--text-muted); transition: all 0.3s ease; }
        .social-icon:hover { background: var(--accent-color); color: white; transform: translateY(-3px); }

        /* Animations */
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeInDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes zoomIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }

        @media (max-width: 768px) {
            .hero-title { font-size: 3rem; }
            .hero-section { padding: 120px 0 60px; }
            .section-title { font-size: 2.2rem; }
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg fixed-top" id="navbar">
        <div class="container">
            <a class="navbar-brand brand-font" href="#">
                <i class="fas fa-feather-alt text-primary me-2"></i>SkripsiAI
            </a>
            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="fas fa-bars fs-4 text-dark"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link" href="#fitur">Fitur Unggulan</a></li>
                    <li class="nav-item"><a class="nav-link" href="#cara-kerja">Metodologi</a></li>
                    <li class="nav-item"><a class="nav-link" href="#faq">FAQ</a></li>
                </ul>
                <div class="d-flex gap-3 align-items-center mt-3 mt-lg-0">
                    @auth
                        <a href="{{ route('app.create') }}" class="btn btn-nav-cta px-4">Ke Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="text-decoration-none fw-semibold text-dark hover-primary" style="transition:0.3s">Masuk</a>
                        <a href="{{ route('register') }}" class="btn btn-nav-cta">Daftar Sekarang</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <section class="hero-section">
        <div class="container">
            <div class="hero-badge">
                <i class="fas fa-bolt text-warning me-2"></i> Era Baru Penulisan Skripsi dengan AI
            </div>
            <h1 class="hero-title">Selesaikan Skripsi Anda<br>Lebih Cerdas & <span>Sistematis</span>.</h1>
            <p class="hero-desc">
                Pendamping penulisan akademik ultimate Anda. Dirancang khusus untuk membantu mahasiswa menyusun Bab 1-5, mencari literatur relevan, dan menstrukturkan ide sesuai standar akademik kampus.
            </p>
            <div class="d-flex gap-3 justify-content-center hero-actions flex-wrap">
                <a href="{{ route('register') }}" class="btn btn-hero btn-hero-primary d-inline-flex align-items-center gap-2">
                    Mulai Menulis <i class="fas fa-arrow-right"></i>
                </a>
                <a href="#demo" class="btn btn-hero btn-light border bg-white d-inline-flex align-items-center gap-2 text-dark">
                    <i class="fas fa-play-circle text-primary"></i> Lihat Cara Kerja
                </a>
            </div>

            <!-- Mockup Dashboard -->
            <div class="dashboard-preview mx-auto" style="max-width: 1040px;">
                <div class="mockup-header d-flex gap-2 align-items-center">
                    <div class="rounded-circle" style="width:12px;height:12px; background:#ff5f56;"></div>
                    <div class="rounded-circle" style="width:12px;height:12px; background:#ffbd2e;"></div>
                    <div class="rounded-circle" style="width:12px;height:12px; background:#27c93f;"></div>
                    <div class="ms-auto text-muted small fw-medium" style="font-family: monospace;">SkripsiAI Workspace</div>
                </div>
                <div style="background: center/cover url('https://images.unsplash.com/photo-1499750310107-5fef28a66643?auto=format&fit=crop&q=80&w=1200&h=600'); position:relative;">
                    <div style="position: absolute; inset:0; background: rgba(255,255,255,0.9); backdrop-filter: blur(5px);"></div>
                    
                    <!-- Fake UI Elements overlay to look like app -->
                    <div class="p-4 p-md-5 position-relative text-start d-flex flex-column flex-md-row gap-4">
                        <div class="d-none d-md-block" style="width: 200px; border-right: 1px solid #eee;">
                             <div class="mb-4 fw-bold text-dark"><i class="fas fa-pen-nib me-2"></i> Draft Skripsi</div>
                             <div class="p-2 bg-primary bg-opacity-10 rounded text-primary fw-medium mb-2 small"><i class="fas fa-file-alt me-2"></i> Bab 1: Pendahuluan</div>
                             <div class="p-2 text-muted fw-medium mb-2 small hover-bg"><i class="fas fa-file-alt me-2"></i> Bab 2: Tinjauan Pustaka</div>
                             <div class="p-2 text-muted fw-medium mb-2 small hover-bg"><i class="fas fa-file-alt me-2"></i> Bab 3: Metodologi</div>
                        </div>
                        <div class="flex-grow-1">
                             <div class="h3 brand-font mb-4">Pengaruh Artificial Intelligence Terhadap Efisiensi Waktu...</div>
                             <div class="p-4 bg-white rounded-3 shadow-sm border mb-3">
                                 <h6 class="fw-bold mb-3">1.1 Latar Belakang</h6>
                                 <div class="bg-light rounded p-2 mb-2 w-100" style="height: 12px;"></div>
                                 <div class="bg-light rounded p-2 mb-2 w-100" style="height: 12px;"></div>
                                 <div class="bg-light rounded p-2 w-75" style="height: 12px;"></div>
                             </div>
                             <div class="d-flex gap-2 flex-wrap">
                                 <div class="btn btn-sm btn-primary px-3 rounded-pill shadow-sm"><i class="fas fa-magic me-1"></i> Generate Paragraf</div>
                                 <div class="btn btn-sm btn-outline-secondary px-3 rounded-pill bg-white"><i class="fas fa-search me-1"></i> Cari Referensi</div>
                             </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="fitur" class="features-section">
        <div class="container">
            <div class="text-center mb-5 pb-3">
                <h6 class="section-subtitle">Kapabilitas Sistem</h6>
                <h2 class="section-title">Fitur Akademik Komprehensif</h2>
                <p class="text-muted mx-auto" style="max-width: 600px; font-size: 1.1rem;">Rangkaian alat mutakhir yang didesain khusus untuk mengatasi *writer's block* dan mempercepat proses riset Anda.</p>
            </div>

            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon-box bg-primary bg-opacity-10 text-primary">
                            <i class="fas fa-brain"></i>
                        </div>
                        <h4 class="feature-title">Generator Analitis Bab 1-5</h4>
                        <p class="feature-desc">Membangun narasi akademik yang kohesif. Mulai dari latar belakang masalah empiris hingga sintesis kesimpulan berbasis data.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon-box" style="background: #ecfdf5; color: #10b981;">
                            <i class="fas fa-book-journal-whills"></i>
                        </div>
                        <h4 class="feature-title">Ekstraksi Jurnal Mutakhir</h4>
                        <p class="feature-desc">Terintegrasi dengan basis data publikasi untuk mencarikan referensi jurnal (2019-2024) relevan guna memperkuat *state of the art* riset Anda.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon-box" style="background: #fef3c7; color: #d97706;">
                            <i class="fas fa-microscope"></i>
                        </div>
                        <h4 class="feature-title">Eksplorasi Judul Variabel</h4>
                        <p class="feature-desc">Kalkulasi dan pemetaan ide. Dapatkan rekomendasi judul skripsi orisinal yang disesuaikan dengan tren keilmuan prodi Anda.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon-box" style="background: #f3e8ff; color: #9333ea;">
                            <i class="fas fa-quote-left"></i>
                        </div>
                        <h4 class="feature-title">Automasi Manajemen Sitasi</h4>
                        <p class="feature-desc">Injeksi in-text citation dan penyusunan daftar pustaka berstandar APA/IEEE secara otomatis tanpa perlu software pihak ketiga.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon-box" style="background: #fee2e2; color: #ef4444;">
                            <i class="fas fa-file-export"></i>
                        </div>
                        <h4 class="feature-title">Eksport Manuskrip Instan</h4>
                        <p class="feature-desc">Konversi hasil generate ke format Microsoft Word (.docx) atau PDF yang rapi, terstruktur, dan siap dilampirkan untuk bimbingan.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon-box" style="background: #f1f5f9; color: #334155;">
                            <i class="fas fa-key"></i>
                        </div>
                        <h4 class="feature-title">Infrastruktur API Dedikasi</h4>
                        <p class="feature-desc">Gunakan Google Gemini API Key Anda sendiri untuk kontrol penuh, akses tak terbatas, dan jaminan kerahasiaan data riset.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="cara-kerja" class="workflow-section">
        <div class="container">
            <div class="row align-items-center mb-5 pb-2">
                <div class="col-lg-6">
                    <h6 class="section-subtitle">Alur Orkestrasi</h6>
                    <h2 class="section-title">Metodologi Kerja Platform</h2>
                </div>
                <div class="col-lg-6 text-lg-end mt-3 mt-lg-0">
                    <p class="text-muted mb-0 fs-5">Pendekatan empat tahap yang menyederhanakan kompleksitas penyusunan tugas akhir.</p>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="step-card">
                        <div class="step-number">01</div>
                        <div class="step-content">
                            <div class="text-primary mb-3"><i class="fas fa-keyboard fs-3"></i></div>
                            <h5 class="fw-bold text-dark">Inisialisasi Variabel</h5>
                            <p class="text-muted small mt-2">Definisikan judul penelitian, program studi, serta pendekatan metodologis (Kualitatif/Kuantitatif/R&D).</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="step-card">
                        <div class="step-number">02</div>
                        <div class="step-content">
                            <div class="text-primary mb-3"><i class="fas fa-cogs fs-3"></i></div>
                            <h5 class="fw-bold text-dark">Proses Generasi AI</h5>
                            <p class="text-muted small mt-2">Mesin AI akan memproses instruksi dan mensintesis kerangka berpikir serta draf konten yang relevan dan terstruktur.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="step-card">
                        <div class="step-number">03</div>
                        <div class="step-content">
                            <div class="text-primary mb-3"><i class="fas fa-edit fs-3"></i></div>
                            <h5 class="fw-bold text-dark">Redaksi & Kurasi</h5>
                            <p class="text-muted small mt-2">Anda bertindak sebagai *Editor in Chief*. Baca, perbaiki, dan sesuaikan gaya bahasa AI dengan preferensi pembimbing.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="step-card">
                        <div class="step-number">04</div>
                        <div class="step-content">
                            <div class="text-primary mb-3"><i class="fas fa-check-double fs-3"></i></div>
                            <h5 class="fw-bold text-dark">Finalisasi Dokumen</h5>
                            <p class="text-muted small mt-2">Ekspor ke format standar institusi (.docx) siap pakai untuk pengajuan proposal atau sidang tugas akhir.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="faq" class="features-section">
        <div class="container max-width-800" style="max-width: 800px;">
            <div class="text-center mb-5">
                <h6 class="section-subtitle">Pusat Bantuan</h6>
                <h2 class="section-title">Frequently Asked Questions</h2>
            </div>
            <div class="accordion" id="accordionFAQ">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true">
                            Mengenai Otentisitas & Plagiarisme (Turnitin)
                        </button>
                    </h2>
                    <div id="collapseOne" class="accordion-collapse collapse show" data-bs-parent="#accordionFAQ">
                        <div class="accordion-body">
                            Platform kami merangkai kalimat secara generatif, sehingga meminimalisir persentase kesamaan teks langsung (plagiarisme). Meskipun demikian, untuk menjaga integritas akademik, Anda diwajibkan untuk memparafrase ulang, melakukan triangulasi data lapangan, dan memvalidasi kebenaran setiap teori yang dihasilkan sebelum uji Turnitin.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo">
                            Apakah saya tetap butuh observasi lapangan?
                        </button>
                    </h2>
                    <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#accordionFAQ">
                        <div class="accordion-body">
                            <strong>Sangat wajib.</strong> AI membantu dalam penyusunan struktural, tata bahasa, dan kerangka teoritis (Bab 1, 2, dan 3 awal). Namun, untuk hasil penelitian dan pembahasan (Bab 4), Anda harus menyuntikkan data real hasil observasi, kuesioner, atau wawancara yang Anda lakukan sendiri agar laporan memiliki validitas empiris.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree">
                            Integrasi API Key Gemini
                        </button>
                    </h2>
                    <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#accordionFAQ">
                        <div class="accordion-body">
                            Untuk skalabilitas tinggi, kami memungkinkan Anda menyambungkan API Key Gemini (dari Google AI Studio) milik Anda sendiri ke dalam akun Anda. Ini menjamin Anda terhindar dari *rate limit* global kami dan memberikan keleluasaan dalam frekuensi *generate*.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 md-mb-4">
                    <a href="#" class="footer-logo brand-font mb-3 d-inline-block">
                        <i class="fas fa-feather-alt text-primary me-2"></i>SkripsiAI
                    </a>
                    <p class="text-muted mt-2 pe-lg-4" style="font-size: 0.95rem; line-height: 1.6;">
                        Platform cerdas berbasis Artificial Intelligence yang memberdayakan mahasiswa Indonesia untuk mereset dan menyusun draf tugas akhir secara lebih sistematis dan terstruktur.
                    </p>
                    <div class="d-flex gap-3 mt-4">
                        <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-github"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4">
                    <h6 class="fw-bold mb-4 text-dark">Modul App</h6>
                    <ul class="list-unstyled text-muted d-flex flex-column gap-3">
                        <li><a href="#" class="footer-link">Automasi Bab</a></li>
                        <li><a href="#" class="footer-link">Mesin Jurnal</a></li>
                        <li><a href="#" class="footer-link">Eksplorasi Ide</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-4">
                    <h6 class="fw-bold mb-4 text-dark">Informasi Legal</h6>
                    <ul class="list-unstyled text-muted d-flex flex-column gap-3">
                        <li><a href="#" class="footer-link">Syarat & Ketentuan</a></li>
                        <li><a href="#" class="footer-link">Kebijakan Privasi</a></li>
                        <li><a href="#" class="footer-link">Etika Akademik</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 col-md-4">
                    <h6 class="fw-bold mb-4 text-dark">Bantuan & Dukungan</h6>
                    <p class="text-muted small mb-2">Mengalami kendala atau butuh kustomisasi kampus tertentu?</p>
                    <a href="mailto:support@skripsiai.com" class="text-primary fw-medium text-decoration-none d-inline-flex align-items-center gap-2">
                        <i class="fas fa-envelope"></i> support@skripsiai.com
                    </a>
                </div>
            </div>
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center pt-4 mt-5 border-top">
                <small class="text-muted">&copy; 2025 SkripsiAI Platform. All rights reserved.</small>
                <small class="text-muted mt-2 mt-md-0">Engineered with <i class="fas fa-heart text-danger mx-1"></i> by Muh Alwi Syahrir.</small>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Navbar Scrolled Effect
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                document.getElementById('navbar').classList.add('scrolled');
            } else {
                document.getElementById('navbar').classList.remove('scrolled');
            }
        });
    </script>
</body>
</html>