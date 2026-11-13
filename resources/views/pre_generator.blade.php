<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Workspace - SkripsiAI</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        :root {
            --sidebar-bg: #0f172a;
            --sidebar-hover: #1e293b;
            --sidebar-active: #2563eb;
            --main-bg: #f8fafc;
            --card-bg: #ffffff;
            --text-primary: #0f172a;
            --text-secondary: #64748b;
            --accent: #2563eb;
            --border-color: #e2e8f0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--main-bg);
            color: var(--text-primary);
            overflow: hidden;
            margin: 0;
        }

        /* --- SIDEBAR KIRI DENGAN SUB-MENU --- */
        .sidebar {
            width: 280px;
            height: 100dvh;
            background: var(--sidebar-bg);
            color: #e2e8f0;
            position: fixed;
            top: 0;
            left: 0;
            display: flex;
            flex-direction: column;
            padding: 20px 16px;
            z-index: 1050;
            transition: transform 0.3s ease;
            border-right: 1px solid rgba(255,255,255,0.08);
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #fff;
            text-decoration: none;
            font-weight: 800;
            font-size: 1.3rem;
            letter-spacing: -0.5px;
        }

        .brand-logo-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1rem;
        }

        .btn-new-project {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #ffffff;
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 12px;
            padding: 12px 16px;
            font-weight: 600;
            font-size: 0.92rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
            transition: all 0.2s ease;
            box-shadow: 0 8px 20px -4px rgba(37, 99, 235, 0.45);
        }

        .btn-new-project:hover {
            transform: translateY(-1px);
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: #fff;
        }

        .sidebar-section-label {
            text-transform: uppercase;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 1px;
            color: #64748b;
            padding: 8px 12px 6px;
        }

        .nav-submenu {
            display: flex;
            flex-direction: column;
            gap: 4px;
            overflow-y: auto;
            flex: 1;
            padding-right: 4px;
        }

        .nav-submenu::-webkit-scrollbar { width: 4px; }
        .nav-submenu::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 4px; }

        .submenu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            border-radius: 10px;
            color: #cbd5e1;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            border: none;
            background: transparent;
            width: 100%;
            text-align: left;
            transition: all 0.2s ease;
        }

        .submenu-item i {
            width: 20px;
            text-align: center;
            font-size: 0.95rem;
            color: #94a3b8;
            transition: color 0.2s ease;
        }

        .submenu-item:hover {
            background: var(--sidebar-hover);
            color: #ffffff;
        }

        .submenu-item:hover i {
            color: #60a5fa;
        }

        .submenu-item[aria-current="page"],
        .submenu-item.active {
            background: rgba(37, 99, 235, 0.18);
            color: #ffffff;
            font-weight: 600;
            border-left: 3px solid #3b82f6;
        }

        .submenu-item[aria-current="page"] i,
        .submenu-item.active i {
            color: #60a5fa;
        }

        .submenu-badge {
            margin-left: auto;
            font-size: 0.68rem;
            padding: 2px 7px;
            border-radius: 50px;
            font-weight: 700;
            background: rgba(59, 130, 246, 0.2);
            color: #93c5fd;
        }

        /* --- MAIN AREA --- */
        .main-content {
            margin-left: 280px;
            height: 100dvh;
            overflow-y: auto;
            position: relative;
            transition: margin-left 0.3s ease;
        }

        .top-nav {
            padding: 14px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            background: rgba(248, 250, 252, 0.88);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-color);
            z-index: 50;
        }

        .profile-btn {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-weight: 700;
            color: #1d4ed8;
        }

        .mobile-toggle {
            display: none;
            border: none;
            background: none;
            font-size: 1.35rem;
            color: var(--text-primary);
        }

        /* --- SCREENS --- */
        .welcome-area, .workspace-container {
            max-width: 1080px;
            margin: 0 auto;
            padding: 36px 28px 80px;
            animation: fadeIn 0.25s ease;
        }

        .workspace-container { display: none; }

        .greeting {
            font-size: clamp(1.75rem, 1.2rem + 2vw, 2.6rem);
            font-weight: 800;
            letter-spacing: -0.8px;
            background: linear-gradient(135deg, #1e293b 0%, #2563eb 60%, #7c3aed 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 6px;
        }

        .option-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 22px;
            cursor: pointer;
            height: 100%;
            transition: all 0.25s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            text-align: left;
            border: 1px solid var(--border-color);
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.03);
        }

        .option-card:hover {
            border-color: #93c5fd;
            transform: translateY(-4px);
            box-shadow: 0 14px 28px -8px rgba(37, 99, 235, 0.14);
        }

        .card-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
            font-size: 1.15rem;
        }

        .project-header {
            background: #ffffff;
            border-radius: 16px;
            padding: 24px 28px;
            margin-bottom: 24px;
            border: 1px solid var(--border-color);
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }

        .panel-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 26px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03);
        }

        .result-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 12px;
            transition: all 0.2s;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }

        .result-card:hover {
            border-color: var(--accent);
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.08);
        }

        .form-control-custom {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 11px 14px;
            font-size: 0.93rem;
        }

        .form-control-custom:focus {
            background: #ffffff;
            border-color: var(--accent);
            outline: none;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .markdown-output {
            line-height: 1.75;
            font-size: 0.95rem;
            color: #1e293b;
        }
        .markdown-output h2, .markdown-output h3 {
            font-weight: 700;
            margin-top: 1.2rem;
            margin-bottom: 0.6rem;
            color: #0f172a;
        }

        /* --- CHAT FLOATING & SOLVER --- */
        .btn-chat-float {
            position: fixed;
            bottom: 28px;
            right: 28px;
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            border: none;
            border-radius: 50%;
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.4);
            z-index: 1060;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .chat-window {
            position: fixed;
            bottom: 96px;
            right: 28px;
            width: 360px;
            height: 500px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.16);
            z-index: 1060;
            display: none;
            flex-direction: column;
            border: 1px solid var(--border-color);
            overflow: hidden;
        }

        .chat-header {
            background: linear-gradient(135deg, #1e293b, #2563eb);
            padding: 14px 18px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chat-body {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .chat-footer { padding: 12px; background: white; border-top: 1px solid var(--border-color); }
        .chat-bubble { max-width: 82%; padding: 10px 14px; border-radius: 12px; font-size: 0.88rem; line-height: 1.45; }
        .ai-bubble { background: white; border: 1px solid var(--border-color); align-self: flex-start; }
        .user-bubble { background: #2563eb; color: white; align-self: flex-end; }

        .chat-container-solver {
            height: 440px;
            overflow-y: auto;
            background: #f8fafc;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            margin-bottom: 15px;
        }
        .bubble-solver { padding: 14px 18px; border-radius: 12px; max-width: 88%; font-size: 0.93rem; line-height: 1.6; }
        .bubble-user-solver { align-self: flex-end; background: #2563eb; color: #ffffff; }
        .bubble-ai-solver { align-self: flex-start; background: #ffffff; border: 1px solid var(--border-color); color: #1e293b; }

        .img-preview-box { display: none; width: 56px; height: 56px; border-radius: 8px; border: 1px dashed #94a3b8; margin-right: 10px; background-size: cover; background-position: center; position: relative; }
        .img-preview-close { position: absolute; top: -6px; right: -6px; background: #ef4444; color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 10px; display: flex; align-items: center; justify-content: center; cursor: pointer; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

        @media (max-width: 991px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .mobile-toggle { display: block; }
            .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.55); z-index: 1040; }
            .sidebar-overlay.active { display: block; }
            .chat-window { width: 92%; right: 4%; bottom: 90px; height: 62dvh; }
        }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- SIDEBAR KIRI DENGAN SUB-MENU LENGKAP -->
<aside class="sidebar" id="sidebar">
    <div class="d-flex align-items-center justify-content-between mb-4 px-2">
        <a href="{{ route('home') }}" class="brand-logo">
            <div class="brand-logo-icon"><i class="fas fa-feather-alt"></i></div>
            <span>SkripsiAI</span>
        </a>
        <button class="btn text-white d-lg-none p-1" onclick="toggleSidebar()"><i class="fas fa-times"></i></button>
    </div>

    <button type="button" class="btn-new-project" onclick="navigateMenu('formScreen', this)">
        <i class="fas fa-plus-circle"></i>
        <span>Proyek Skripsi Baru</span>
    </button>

    <div class="nav-submenu" role="navigation" aria-label="Menu Utama">
        <div class="sidebar-section-label">Menu Utama</div>

        <button type="button" class="submenu-item active" id="nav-welcomeScreen" aria-current="page" onclick="navigateMenu('welcomeScreen', this)">
            <i class="fas fa-th-large"></i>
            <span>Beranda AI</span>
        </button>

        <button type="button" class="submenu-item" id="nav-myProjectsScreen" onclick="navigateMenu('myProjectsScreen', this)">
            <i class="fas fa-folder-open"></i>
            <span>Proyek Saya</span>
            <span class="submenu-badge">{{ count($history) }}</span>
        </button>

        <div class="sidebar-section-label mt-2">Penulisan & Riset</div>

        <button type="button" class="submenu-item" id="nav-proposalScreen" onclick="navigateMenu('proposalScreen', this)">
            <i class="fas fa-file-signature"></i>
            <span>Generator Proposal</span>
            <span class="submenu-badge">Bab 1-3</span>
        </button>

        <button type="button" class="submenu-item" id="nav-titleScreen" onclick="navigateMenu('titleScreen', this)">
            <i class="fas fa-lightbulb"></i>
            <span>Cari Judul Skripsi</span>
        </button>

        <button type="button" class="submenu-item" id="nav-journalScreen" onclick="navigateMenu('journalScreen', this)">
            <i class="fas fa-book-open"></i>
            <span>Cari Jurnal Ilmiah</span>
        </button>

        <button type="button" class="submenu-item" id="nav-paraphraseScreen" onclick="navigateMenu('paraphraseScreen', this)">
            <i class="fas fa-wand-magic-sparkles"></i>
            <span>Parafrase Online</span>
        </button>

        <div class="sidebar-section-label mt-2">Persiapan Sidang</div>

        <button type="button" class="submenu-item" id="nav-pptScreen" onclick="navigateMenu('pptScreen', this)">
            <i class="fas fa-desktop"></i>
            <span>AI PPT Sidang</span>
        </button>

        <button type="button" class="submenu-item" id="nav-defenseScreen" onclick="navigateMenu('defenseScreen', this)">
            <i class="fas fa-user-tie"></i>
            <span>Simulasi Sidang</span>
            <span class="submenu-badge" style="background:rgba(16,185,129,0.2);color:#6ee7b7;">Live</span>
        </button>

        <button type="button" class="submenu-item" id="nav-solverScreen" onclick="navigateMenu('solverScreen', this)">
            <i class="fas fa-graduation-cap"></i>
            <span>Tanya Jawab & Soal</span>
        </button>

        @if(count($history) > 0)
            <div class="sidebar-section-label mt-3">Terakhir Dikerjakan</div>
            @foreach($history->take(4) as $h)
                <a href="{{ route('app.index', ['id' => $h->id]) }}" class="submenu-item py-2" title="{{ $h->judul }}">
                    <i class="far fa-file-word text-info"></i>
                    <span class="text-truncate">{{ Str::limit($h->judul, 22) }}</span>
                </a>
            @endforeach
        @endif
    </div>

    <div class="mt-3 pt-3 border-top border-secondary border-opacity-25">
        <button type="button" class="submenu-item py-2" data-bs-toggle="modal" data-bs-target="#modalApiKey">
            <i class="fas fa-key text-warning"></i>
            <span>API Key Gratisan</span>
        </button>
        <form action="{{ route('logout') }}" method="POST" class="mt-1">
            @csrf
            <button type="submit" class="submenu-item py-2 text-danger">
                <i class="fas fa-sign-out-alt text-danger"></i>
                <span>Keluar (Logout)</span>
            </button>
        </form>
    </div>
</aside>

<!-- MAIN CONTENT -->
<div class="main-content">
    <div class="top-nav">
        <div class="d-flex align-items-center gap-3">
            <button class="mobile-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
            <div class="d-none d-sm-flex align-items-center gap-2 text-secondary small fw-medium">
                <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-1">Standar DIKTI & APA 7th</span>
            </div>
        </div>

        <div class="d-flex align-items-center ms-auto gap-3">
            <div class="d-none d-md-flex align-items-center bg-white rounded-pill px-3 py-1 border shadow-sm">
                <i class="fas fa-microchip text-primary me-2"></i>
                <select id="globalAiModel" class="border-0 bg-transparent fw-semibold text-dark" style="outline:none; cursor:pointer; font-size: 0.85rem;">
                    <option value="auto" selected>Mode Auto (Gemini + Groq Fallback)</option>
                    <option value="gemini">Google Gemini 2.0 Flash</option>
                    <option value="groq">Groq Llama 3.3 70B (Ultra Cepat)</option>
                </select>
            </div>

            <button class="btn btn-light rounded-pill border px-3 py-1 d-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalMission" title="Tambah Token Gratis">
                <i class="fas fa-coins text-warning"></i>
                <span class="fw-bold text-dark" id="creditDisplay">{{ Auth::user()->credits }}</span>
                <span class="small text-primary fw-bold">+</span>
            </button>

            <div class="dropdown">
                <button class="profile-btn" type="button" data-bs-toggle="dropdown">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 mt-2 p-3" style="width: 260px;">
                    <li class="text-center mb-3">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2 fw-bold" style="width: 48px; height: 48px; font-size: 1.2rem;">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </div>
                        <h6 class="fw-bold mb-0">{{ Auth::user()->name }}</h6>
                        <small class="text-muted">{{ Auth::user()->email }}</small>
                    </li>
                    <li><button class="dropdown-item rounded-3 py-2 gap-2 d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#modalMission"><i class="fas fa-gift text-success width-20"></i> Klaim Token Gratis</button></li>
                    <li><button class="dropdown-item rounded-3 py-2 gap-2 d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#modalApiKey"><i class="fas fa-key text-primary width-20"></i> Atur API Key Gratis</button></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button class="dropdown-item rounded-3 text-danger py-2 gap-2 d-flex align-items-center"><i class="fas fa-power-off"></i> Logout</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- 1. BERANDA DASHBOARD -->
    <div id="welcomeScreen" class="welcome-area">
        <div class="mb-4">
            <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-2 mb-2 fw-semibold">
                <i class="fas fa-sparkles me-1"></i> Asisten Akademik Terpadu
            </span>
            <h1 class="greeting">Halo, {{ explode(' ', Auth::user()->name)[0] }}!</h1>
            <p class="text-secondary fs-5 mb-0">Pilih asisten AI untuk mempercepat penyusunan proposal, skripsi Bab 1–5, hingga latihan sidang.</p>
        </div>

        <div class="row g-3 mb-5">
            <div class="col-12 col-sm-6 col-lg-4">
                <div class="option-card" onclick="navigateMenu('formScreen')">
                    <div>
                        <div class="card-icon bg-primary bg-opacity-10 text-primary"><i class="fas fa-book"></i></div>
                        <h5 class="fw-bold text-dark mb-1">Full Skripsi Bab 1–5</h5>
                        <p class="text-secondary small mb-0">Susun naskah lengkap Bab 1 sampai Bab 5 + Daftar Pustaka APA & Export Word/PDF.</p>
                    </div>
                    <div class="mt-3 text-primary small fw-bold">Mulai Menulis <i class="fas fa-arrow-right ms-1"></i></div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-lg-4">
                <div class="option-card" onclick="navigateMenu('proposalScreen')">
                    <div>
                        <div class="card-icon bg-indigo bg-opacity-10" style="background:#eef2ff;color:#4f46e5;"><i class="fas fa-file-signature"></i></div>
                        <h5 class="fw-bold text-dark mb-1">Generator Proposal</h5>
                        <p class="text-secondary small mb-0">Generate cepat draf Proposal Seminar (Bab 1, Bab 2, Bab 3) sesuai metode riset kampus.</p>
                    </div>
                    <div class="mt-3 small fw-bold" style="color:#4f46e5;">Buat Proposal <i class="fas fa-arrow-right ms-1"></i></div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-lg-4">
                <div class="option-card" onclick="navigateMenu('titleScreen')">
                    <div>
                        <div class="card-icon bg-success bg-opacity-10 text-success"><i class="fas fa-lightbulb"></i></div>
                        <h5 class="fw-bold text-dark mb-1">Cari Judul Skripsi</h5>
                        <p class="text-secondary small mb-0">Rekomendasi ide judul spesifik lengkap dengan variabel X/Y dan objek penelitian terbaru.</p>
                    </div>
                    <div class="mt-3 text-success small fw-bold">Cari Ide Judul <i class="fas fa-arrow-right ms-1"></i></div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-lg-4">
                <div class="option-card" onclick="navigateMenu('paraphraseScreen')">
                    <div>
                        <div class="card-icon" style="background:#fdf2f8;color:#db2777;"><i class="fas fa-wand-magic-sparkles"></i></div>
                        <h5 class="fw-bold text-dark mb-1">Parafrase Online</h5>
                        <p class="text-secondary small mb-0">Ubah struktur kalimat menjadi bahasa ilmiah baku KBBI & turunkan skor Turnitin.</p>
                    </div>
                    <div class="mt-3 small fw-bold" style="color:#db2777;">Mulai Parafrase <i class="fas fa-arrow-right ms-1"></i></div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-lg-4">
                <div class="option-card" onclick="navigateMenu('pptScreen')">
                    <div>
                        <div class="card-icon bg-warning bg-opacity-10 text-warning"><i class="fas fa-desktop"></i></div>
                        <h5 class="fw-bold text-dark mb-1">AI PPT Sidang</h5>
                        <p class="text-secondary small mb-0">Susun otomatis 9 slide presentasi sempro/sidang lengkap dengan script bicara presenter.</p>
                    </div>
                    <div class="mt-3 text-warning small fw-bold">Buat Slide PPT <i class="fas fa-arrow-right ms-1"></i></div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-lg-4">
                <div class="option-card" onclick="navigateMenu('defenseScreen')">
                    <div>
                        <div class="card-icon" style="background:#ecfdf5;color:#059669;"><i class="fas fa-user-tie"></i></div>
                        <h5 class="fw-bold text-dark mb-1">Simulasi Sidang AI</h5>
                        <p class="text-secondary small mb-0">Latihan tanya-jawab dengan Dosen Penguji AI lengkap dengan skor evaluasi & saran jawaban A.</p>
                    </div>
                    <div class="mt-3 small fw-bold" style="color:#059669;">Mulai Simulasi <i class="fas fa-arrow-right ms-1"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. PROYEK SAYA (RIWAYAT SKRIPSI) -->
    <div id="myProjectsScreen" class="workspace-container">
        <div class="project-header d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <span class="badge bg-primary bg-opacity-10 text-primary mb-2">Workspace Tersimpan</span>
                <h2 class="fw-bold text-dark mb-0">Proyek Skripsi Saya</h2>
                <small class="text-muted">Daftar draf skripsi Bab 1–5 yang pernah Anda kerjakan.</small>
            </div>
            <button class="btn btn-primary rounded-pill px-4 fw-semibold" onclick="navigateMenu('formScreen')">
                <i class="fas fa-plus me-2"></i> Buat Proyek Baru
            </button>
        </div>

        <div class="row g-3">
            @forelse($history as $item)
                @php
                    $doneCount = 0;
                    foreach(['bab1_content','bab2_content','bab3_content','bab4_content','bab5_content'] as $col) {
                        if (!empty($item->$col)) $doneCount++;
                    }
                    $pct = $doneCount * 20;
                @endphp
                <div class="col-12">
                    <div class="panel-card d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-primary bg-opacity-10 text-primary">{{ $item->prodi }}</span>
                                <span class="badge bg-light text-secondary border">{{ $item->metode_penelitian }}</span>
                                <small class="text-muted ms-2"><i class="far fa-clock me-1"></i> {{ $item->updated_at->diffForHumans() }}</small>
                            </div>
                            <h5 class="fw-bold text-dark mb-2">{{ $item->judul }}</h5>
                            <div class="d-flex align-items-center gap-3">
                                <div class="progress" style="width: 160px; height: 8px;">
                                    <div class="progress-bar bg-success" style="width: {{ $pct }}%"></div>
                                </div>
                                <small class="fw-semibold text-secondary">{{ $doneCount }}/5 Bab Selesai ({{ $pct }}%)</small>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('app.index', ['id' => $item->id]) }}" class="btn btn-primary rounded-pill px-4 fw-semibold">
                                <i class="fas fa-pen-fancy me-2"></i> Buka Workspace
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="panel-card text-center py-5">
                        <i class="far fa-folder-open fa-3x text-secondary opacity-50 mb-3"></i>
                        <h5 class="fw-bold">Belum Ada Proyek Skripsi</h5>
                        <p class="text-muted small mb-4">Mulai proyek pertama Anda untuk menyusun Bab 1 hingga Bab 5 secara otomatis.</p>
                        <button class="btn btn-primary rounded-pill px-4 fw-semibold" onclick="navigateMenu('formScreen')">
                            <i class="fas fa-plus me-2"></i> Buat Proyek Sekarang
                        </button>
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    <!-- 3. FORM PROYEK BARU (BAB 1-5 WORKSPACE) -->
    <div id="formScreen" class="workspace-container">
        <div class="project-header d-flex justify-content-between align-items-center">
            <div>
                <span class="badge bg-primary bg-opacity-10 text-primary mb-2">Workspace Bab 1–5</span>
                <h2 class="fw-bold text-dark mb-0">Mulai Proyek Skripsi Baru</h2>
                <small class="text-muted">Sistem otomatis menyesuaikan struktur sub-bab dengan metode penelitian (Kuantitatif, Kualitatif, atau R&D/IT).</small>
            </div>
        </div>
        <div class="panel-card">
            <form action="{{ route('project.start') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="fw-bold small text-secondary text-uppercase mb-2">Judul Skripsi <span class="text-danger">*</span></label>
                    <textarea id="formJudulInput" name="judul" class="form-control form-control-custom" rows="2" placeholder="Contoh: Pengaruh Kualitas Layanan Digital dan Kepercayaan Terhadap Loyalitas Nasabah..." required></textarea>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="fw-bold small text-secondary text-uppercase mb-2">Program Studi / Jurusan <span class="text-danger">*</span></label>
                        <input id="formProdiInput" type="text" name="prodi" class="form-control form-control-custom" placeholder="Cth: Manajemen / Teknik Informatika / Hukum" required>
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="fw-bold small text-secondary text-uppercase mb-2">Metode Penelitian (Skill Adaptif) <span class="text-danger">*</span></label>
                        <select name="metode_penelitian" class="form-select form-control-custom" required>
                            <option value="" disabled selected>Pilih Metode Penelitian...</option>
                            <option value="Kuantitatif (Survei / Regresi)">Kuantitatif - Survei / Regresi / SEM-PLS</option>
                            <option value="Kuantitatif (Eksperimen)">Kuantitatif - Eksperimen (Pre-test / Post-test)</option>
                            <option value="Kualitatif (Deskriptif / Studi Kasus)">Kualitatif - Deskriptif / Studi Kasus (Miles & Huberman)</option>
                            <option value="R&D (Waterfall)">R&D / IT - SDLC Waterfall (UML & Blackbox)</option>
                            <option value="R&D (Agile)">R&D / IT - Agile / Scrum Development</option>
                            <option value="R&D (Prototype)">R&D / IT - Prototyping Model</option>
                            <option value="Mixed Methods">Mixed Methods (Kombinasi Kuantitatif & Kualitatif)</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="fw-bold small text-secondary text-uppercase mb-2">Variabel / Fokus Kajian</label>
                        <input type="text" name="variabel_penelitian" class="form-control form-control-custom" placeholder="Cth: Variabel X1, X2 terhadap Y atau Algoritma Random Forest">
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="fw-bold small text-secondary text-uppercase mb-2">Objek / Sumber Data Penelitian</label>
                        <input type="text" name="sumber_data" class="form-control form-control-custom" placeholder="Cth: Studi Kasus pada PT X / Mahasiswa Kampus Y / Dataset Publik">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow-sm">
                    <i class="fas fa-rocket me-2"></i> Buat Proyek & Masuk ke Editor Bab 1–5
                </button>
            </form>
        </div>
    </div>

    <!-- 4. GENERATOR PROPOSAL (BAB 1-3 INSTAN) -->
    <div id="proposalScreen" class="workspace-container">
        <div class="project-header d-flex justify-content-between align-items-center">
            <div>
                <span class="badge bg-primary bg-opacity-10 text-primary mb-2">Seminar Proposal</span>
                <h2 class="fw-bold text-dark mb-0">Generator Proposal Skripsi (Bab 1–3)</h2>
                <small class="text-muted">Susun draf proposal lengkap (Pendahuluan, Tinjauan Pustaka, Metodologi & Referensi Awal) dalam sekali klik.</small>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="panel-card">
                    <div class="mb-3">
                        <label class="fw-bold small text-secondary mb-1">Judul Proposal Skripsi</label>
                        <textarea id="propJudul" class="form-control form-control-custom" rows="3" placeholder="Masukkan judul proposal Anda..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold small text-secondary mb-1">Program Studi</label>
                        <input id="propProdi" class="form-control form-control-custom" placeholder="Cth: Sistem Informasi / Akuntansi">
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold small text-secondary mb-1">Metode Penelitian</label>
                        <select id="propMetode" class="form-select form-control-custom">
                            <option value="Kuantitatif (Survei & Regresi)">Kuantitatif (Survei & Regresi)</option>
                            <option value="Kualitatif (Studi Kasus & Miles Huberman)">Kualitatif (Studi Kasus)</option>
                            <option value="R&D (Pengembangan Sistem Waterfall)">R&D / Pengembangan Sistem (Waterfall/Agile)</option>
                            <option value="Kuantitatif Eksperimen">Kuantitatif Eksperimen</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold small text-secondary mb-1">Fenomena / Masalah di Lapangan (Opsional)</label>
                        <textarea id="propMasalah" class="form-control form-control-custom" rows="2" placeholder="Cth: Proses pencatatan masih manual atau penurunan kepuasan pelanggan..."></textarea>
                    </div>
                    <button class="btn btn-primary w-100 rounded-pill py-2 fw-bold" onclick="generateProposal()">
                        <i class="fas fa-file-signature me-2"></i> Generate Proposal Lengkap
                    </button>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="panel-card h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                        <h6 class="fw-bold mb-0"><i class="fas fa-file-alt text-primary me-2"></i> Hasil Naskah Proposal (Bab I – III)</h6>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="copyTextFromElement('proposalOutput')">
                                <i class="far fa-copy me-1"></i> Salin Naskah
                            </button>
                            <button class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="transferProposalToFullProject()">
                                <i class="fas fa-arrow-right me-1"></i> Lanjut ke Bab 1–5 Penuh
                            </button>
                        </div>
                    </div>
                    <div id="proposalOutput" class="markdown-output" style="max-height: 560px; overflow-y: auto;">
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-file-signature fa-3x mb-3 opacity-25"></i>
                            <p class="mb-0">Isi judul dan klik <strong>Generate Proposal Lengkap</strong> untuk menyusun draf Bab 1, Bab 2, dan Bab 3 sesuai standar kampus Indonesia.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. CARI JUDUL SKRIPSI -->
    <div id="titleScreen" class="workspace-container">
        <div class="project-header d-flex justify-content-between align-items-center">
            <div>
                <span class="badge bg-success bg-opacity-10 text-success mb-2">Eksplorasi Topik</span>
                <h2 class="fw-bold text-dark mb-0">Cari Judul Skripsi Spesifik</h2>
                <small class="text-muted">Rekomendasi 10 judul skripsi terbaru yang memiliki variabel jelas dan layak disetujui dosen pembimbing.</small>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="panel-card">
                    <div class="mb-3">
                        <label class="fw-bold small text-secondary mb-1">Jurusan / Program Studi</label>
                        <input id="inputProdi" class="form-control form-control-custom" placeholder="Cth: Teknik Informatika / Manajemen">
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold small text-secondary mb-1">Minat Topik / Metode / Masalah</label>
                        <textarea id="inputTopik" class="form-control form-control-custom" rows="3" placeholder="Cth: Machine Learning klasifikasi penyakit, atau Pengaruh Digital Marketing UMKM"></textarea>
                    </div>
                    <button class="btn btn-success w-100 rounded-pill py-2 fw-bold" onclick="generateTitles()">
                        <i class="fas fa-magic me-2"></i> Cari 10 Ide Judul
                    </button>
                </div>
            </div>
            <div class="col-md-8">
                <div id="titleResults">
                    <div class="panel-card text-center py-5 text-muted">
                        <i class="far fa-lightbulb fa-3x mb-3 text-success opacity-50"></i>
                        <p class="mb-0">Masukkan program studi dan topik minat Anda untuk melihat rekomendasi judul skripsi.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 6. CARI JURNAL ILMIAH -->
    <div id="journalScreen" class="workspace-container">
        <div class="project-header d-flex justify-content-between align-items-center">
            <div>
                <span class="badge bg-warning bg-opacity-10 text-warning text-dark mb-2">Literatur & Sitasi</span>
                <h2 class="fw-bold text-dark mb-0">Pencari Jurnal Ilmiah (2020–2025)</h2>
                <small class="text-muted">Temukan referensi jurnal nasional (SINTA) dan internasional untuk memperkuat Bab 1 dan Bab 2.</small>
            </div>
        </div>
        <div class="panel-card mb-4">
            <div class="d-flex flex-column flex-sm-row gap-2">
                <input id="inputJournalKeyword" class="form-control form-control-custom flex-grow-1" placeholder="Masukkan topik atau variabel penelitian (Cth: Pengaruh Beban Kerja terhadap Kinerja Karyawan)...">
                <button class="btn btn-warning text-dark fw-bold rounded-pill px-4 py-2" onclick="findJournals()">
                    <i class="fas fa-search me-2"></i> Cari Jurnal
                </button>
            </div>
        </div>
        <div id="journalResults">
            <div class="panel-card text-center py-5 text-muted">
                <i class="fas fa-book-open fa-3x mb-3 text-warning opacity-50"></i>
                <p class="mb-0">Daftar referensi jurnal beserta tautan pencarian PDF akan muncul di sini.</p>
            </div>
        </div>
    </div>

    <!-- 7. PARAFRASE ONLINE AKADEMIK -->
    <div id="paraphraseScreen" class="workspace-container">
        <div class="project-header d-flex justify-content-between align-items-center">
            <div>
                <span class="badge bg-danger bg-opacity-10 text-danger mb-2">Anti-Plagiasi & Turnitin</span>
                <h2 class="fw-bold text-dark mb-0">Parafrase Online Akademik</h2>
                <small class="text-muted">Tulis ulang kutipan atau paragraf skripsi dengan bahasa ilmiah baku KBBI tanpa menghilangkan sitasi.</small>
            </div>
        </div>

        <div class="panel-card mb-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="small fw-bold text-secondary">Mode Gaya Bahasa:</span>
                    <select id="paraphraseStyle" class="form-select form-select-sm rounded-pill px-3" style="width: auto;">
                        <option value="academic">Formal Akademik Baku (Standar Skripsi/SINTA)</option>
                        <option value="anti_turnitin">Penurun Skor Turnitin (Ubah Struktur Aktif/Pasif)</option>
                        <option value="concise">Ringkas, Padat & Lugas (EYD V)</option>
                        <option value="expand">Perdalam & Perluas Elaborasi Ilmiah</option>
                    </select>
                </div>
                <button class="btn btn-primary rounded-pill px-4 fw-bold" onclick="runParaphrase()">
                    <i class="fas fa-wand-magic-sparkles me-2"></i> Parafrase Sekarang
                </button>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="small fw-bold text-secondary mb-1">Teks Asli (Sebelum Parafrase)</label>
                    <textarea id="paraphraseInput" class="form-control form-control-custom" rows="10" placeholder="Tempelkan paragraf skripsi atau kutipan jurnal di sini..."></textarea>
                </div>
                <div class="col-md-6">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="small fw-bold text-success mb-0">Hasil Parafrase Ilmiah</label>
                        <button class="btn btn-sm btn-light border rounded-pill px-3 py-0" onclick="copyTextFromElement('paraphraseOutput')">
                            <i class="far fa-copy me-1"></i> Copy
                        </button>
                    </div>
                    <div id="paraphraseOutput" class="form-control form-control-custom bg-white" style="height: 235px; overflow-y: auto; line-height: 1.7;">
                        <span class="text-muted">Hasil parafrase akan tampil di sini...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 8. AI PPT SIDANG -->
    <div id="pptScreen" class="workspace-container">
        <div class="project-header d-flex justify-content-between align-items-center">
            <div>
                <span class="badge bg-warning bg-opacity-10 text-dark mb-2">Presentasi Sidang</span>
                <h2 class="fw-bold text-dark mb-0">Generator AI PPT Sidang Skripsi</h2>
                <small class="text-muted">Susun otomatis 9 slide presentasi sidang beserta naskah bicara (speaker notes) tiap slide.</small>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="panel-card">
                    <div class="mb-3">
                        <label class="fw-bold small text-secondary mb-1">Judul Skripsi</label>
                        <textarea id="pptJudul" class="form-control form-control-custom" rows="3" placeholder="Masukkan judul skripsi Anda..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold small text-secondary mb-1">Program Studi</label>
                        <input id="pptProdi" class="form-control form-control-custom" placeholder="Cth: Ilmu Komunikasi / Teknik Sipil">
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold small text-secondary mb-1">Metode Penelitian</label>
                        <input id="pptMetode" class="form-control form-control-custom" placeholder="Cth: Kuantitatif Regresi / R&D Waterfall">
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold small text-secondary mb-1">Ringkasan Hasil Temuan (Opsional)</label>
                        <textarea id="pptRingkasan" class="form-control form-control-custom" rows="2" placeholder="Cth: Variabel X berpengaruh positif sebesar 74,5% terhadap Y..."></textarea>
                    </div>
                    <button class="btn btn-warning text-dark w-100 rounded-pill py-2 fw-bold" onclick="generatePptSlides()">
                        <i class="fas fa-desktop me-2"></i> Buat 9 Slide PPT + Script Bicara
                    </button>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="panel-card h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                        <h6 class="fw-bold mb-0"><i class="fas fa-layer-group text-warning me-2"></i> Struktur Slide & Script Presentasi</h6>
                        <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="copyTextFromElement('pptOutput')">
                            <i class="far fa-copy me-1"></i> Salin Semua Slide
                        </button>
                    </div>
                    <div id="pptOutput" class="markdown-output" style="max-height: 550px; overflow-y: auto;">
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-desktop fa-3x mb-3 opacity-25"></i>
                            <p class="mb-0">Masukkan judul skripsi Anda untuk menghasilkan poin tayangan 9 slide lengkap dengan kalimat pembuka hingga penutup sidang.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 9. SIMULASI SIDANG SKRIPSI -->
    <div id="defenseScreen" class="workspace-container">
        <div class="project-header d-flex justify-content-between align-items-center">
            <div>
                <span class="badge bg-success bg-opacity-10 text-success mb-2">Ujian Lisan Interaktif</span>
                <h2 class="fw-bold text-dark mb-0">Simulasi Sidang Skripsi bersama Dosen Penguji AI</h2>
                <small class="text-muted">Latihan menjawab pertanyaan kritis dosen penguji dan dapatkan skor penilaian serta saran jawaban terbaik.</small>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="panel-card">
                    <h6 class="fw-bold mb-3"><i class="fas fa-id-card-clip text-success me-2"></i> Data Sidang Mahasiswa</h6>
                    <div class="mb-3">
                        <label class="fw-bold small text-secondary mb-1">Judul Skripsi yang Diujikan</label>
                        <textarea id="defJudul" class="form-control form-control-custom" rows="3" placeholder="Masukkan judul skripsi Anda..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold small text-secondary mb-1">Metode Penelitian</label>
                        <input id="defMetode" class="form-control form-control-custom" placeholder="Cth: Kuantitatif / Kualitatif / R&D">
                    </div>
                    <button class="btn btn-success w-100 rounded-pill py-2 fw-bold" onclick="startDefenseSimulation()">
                        <i class="fas fa-play me-2"></i> Mulai Sesi Ujian Sidang
                    </button>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="panel-card">
                    <div class="chat-container-solver" id="defenseChatBody">
                        <div class="bubble-solver bubble-ai-solver">
                            <strong>👨‍🏫 Dewan Dosen Penguji AI:</strong><br>
                            Selamat datang di ruang Simulasi Sidang Skripsi. Silakan isi <strong>Judul Skripsi</strong> dan <strong>Metode Penelitian</strong> Anda di sebelah kiri, lalu klik <em>"Mulai Sesi Ujian Sidang"</em>. Saya akan mengajukan pertanyaan penguji satu per satu dan menilai kualitas jawaban Anda.
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <textarea id="defenseAnswerInput" class="form-control form-control-custom" rows="2" placeholder="Ketik jawaban argumen sidang Anda di sini..."></textarea>
                        <button class="btn btn-primary rounded-4 px-4 fw-bold" onclick="submitDefenseAnswer()">
                            <i class="fas fa-paper-plane me-1"></i> Jawab
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 10. TANYA JAWAB & SOAL (SOLVER VISION) -->
    <div id="solverScreen" class="workspace-container">
        <div class="project-header d-flex justify-content-between align-items-center">
            <div>
                <span class="badge bg-info bg-opacity-10 text-info text-dark mb-2">AI Tutor Multimodal</span>
                <h2 class="fw-bold text-dark mb-0">Tanya Jawab & Bedah Soal</h2>
                <small class="text-muted">Upload foto soal statistik/koding/teori atau tanyakan materi kuliah apa pun.</small>
            </div>
        </div>

        <div class="panel-card">
            <div class="chat-container-solver" id="solverChatBody">
                <div class="bubble-solver bubble-ai-solver">
                    👋 Halo! Silakan unggah foto soal/tabel statistik atau ketik pertanyaan akademik Anda. Saya siap membantu menjelaskan langkah demi langkah!
                </div>
            </div>

            <div class="d-flex align-items-end gap-2 bg-light p-2 rounded-3 border">
                <div id="solverImgPreview" class="img-preview-box">
                    <div class="img-preview-close" onclick="clearSolverImage()"><i class="fas fa-times"></i></div>
                </div>
                <input type="file" id="solverFileInput" accept="image/*" style="display: none;" onchange="handleSolverFile(this)">
                <button class="btn btn-white border text-secondary rounded-circle" style="width: 42px; height: 42px;" onclick="document.getElementById('solverFileInput').click()" title="Upload Gambar">
                    <i class="fas fa-camera"></i>
                </button>
                <textarea id="solverInput" class="form-control border-0 bg-transparent" rows="1" placeholder="Ketik pertanyaan Anda..." style="resize: none;"></textarea>
                <button class="btn btn-primary rounded-circle" style="width: 42px; height: 42px;" onclick="sendSolver()">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>

</div>

<!-- FLOATING DOSEN AI CHAT -->
<button class="btn-chat-float" onclick="toggleChat()" title="Konsultasi Dosen AI"><i class="fas fa-comments fa-lg"></i></button>
<div class="chat-window" id="chatWindow">
    <div class="chat-header">
        <div class="d-flex align-items-center gap-2"><i class="fas fa-user-graduate"></i><h6 class="mb-0 fw-bold">Konsultasi Dosen AI</h6></div>
        <button class="btn btn-sm text-white" onclick="toggleChat()"><i class="fas fa-times"></i></button>
    </div>
    <div class="chat-body" id="chatBody">
        <div class="chat-bubble ai-bubble">Halo! Ada yang ingin dikonsultasikan mengenai bab skripsi atau metodologi Anda hari ini?</div>
    </div>
    <div class="chat-footer">
        <form onsubmit="event.preventDefault(); sendChat();">
            <div class="input-group">
                <input type="text" id="chatInput" class="form-control rounded-pill" placeholder="Tulis pertanyaan..." autocomplete="off">
                <button class="btn btn-primary rounded-circle ms-2" type="submit"><i class="fas fa-paper-plane"></i></button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL API KEY GRATISAN -->
<div class="modal fade" id="modalApiKey" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold"><i class="fas fa-key text-primary me-2"></i> Pengaturan API Key Gratisan</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-info small rounded-3 mb-4">
                    <i class="fas fa-info-circle me-1"></i>
                    <strong>Tips Build Gratisan:</strong> Anda dapat menggunakan API Key gratis dari <strong>Google AI Studio (Gemini)</strong> di <a href="https://aistudio.google.com/app/apikey" target="_blank" class="fw-bold">aistudio.google.com</a> atau <strong>Groq Cloud</strong> di <a href="https://console.groq.com/keys" target="_blank" class="fw-bold">console.groq.com</a>. Sistem akan merotasi key secara otomatis!
                </div>
                <div class="row g-4">
                    <div class="col-md-5 border-end">
                        <h6 class="fw-bold mb-3 text-secondary">Tambah API Key Baru</h6>
                        <ul class="nav nav-pills mb-3">
                            <li class="nav-item"><button class="nav-link active btn-sm rounded-pill" data-bs-toggle="pill" data-bs-target="#pills-gemini">Gemini (Gratis)</button></li>
                            <li class="nav-item"><button class="nav-link btn-sm rounded-pill" data-bs-toggle="pill" data-bs-target="#pills-groq">Groq (Gratis)</button></li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="pills-gemini">
                                <form action="{{ route('api.key.add') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="provider" value="gemini">
                                    <div class="mb-3">
                                        <input type="text" name="key" class="form-control form-control-custom" placeholder="Paste AIzaSy... (Gemini Key)" required>
                                    </div>
                                    <button class="btn btn-primary w-100 rounded-pill fw-semibold">Simpan Key Gemini</button>
                                </form>
                            </div>
                            <div class="tab-pane fade" id="pills-groq">
                                <form action="{{ route('api.key.add') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="provider" value="groq">
                                    <div class="mb-3">
                                        <input type="text" name="key" class="form-control form-control-custom" placeholder="Paste gsk_... (Groq Key)" required>
                                    </div>
                                    <button class="btn btn-dark w-100 rounded-pill fw-semibold">Simpan Key Groq</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <h6 class="fw-bold mb-3 text-secondary">Daftar API Key Aktif</h6>
                        <div style="max-height: 240px; overflow-y: auto;">
                            @forelse($apiKeys as $k)
                                <div class="card border shadow-sm mb-2 rounded-3">
                                    <div class="card-body p-2 px-3 d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center gap-2">
                                            @if($k->provider == 'groq')
                                                <span class="badge bg-dark rounded-pill">GROQ</span>
                                            @else
                                                <span class="badge bg-primary rounded-pill">GEMINI</span>
                                            @endif
                                            <span class="fw-semibold small font-monospace">{{ Str::limit($k->key, 16) }}••••</span>
                                        </div>
                                        <form action="{{ route('api.key.delete', $k->id) }}" method="POST">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-light btn-sm text-danger border"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <div class="text-muted small py-3 text-center bg-light rounded-3">Belum ada API key di database (menggunakan fallback .env jika tersedia).</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL MISI REWARD TOKEN -->
<div class="modal fade" id="modalMission" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0">
                <h5 class="fw-bold">🎁 Klaim Token AI Gratis</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 pt-2">
                <div class="card border bg-light mb-3 rounded-3">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div><h6 class="fw-bold mb-0">Bonus Login Harian</h6><small class="text-muted">+2 Token Generate</small></div>
                        <button class="btn btn-sm btn-primary rounded-pill px-3 fw-semibold" onclick="claimReward('login')">Klaim +2</button>
                    </div>
                </div>
                <div class="card border bg-light rounded-3">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div><h6 class="fw-bold mb-0">Bonus Ekstra Mahasiswa</h6><small class="text-muted">+5 Token Generate</small></div>
                        <button class="btn btn-sm btn-warning rounded-pill px-3 fw-semibold" onclick="claimReward('extra')">Klaim +5</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    function navigateMenu(screenId, btnEl = null) {
        document.querySelectorAll('.workspace-container, .welcome-area').forEach(el => el.style.display = 'none');
        const target = document.getElementById(screenId);
        if (target) target.style.display = 'block';

        document.querySelectorAll('.submenu-item').forEach(item => {
            item.classList.remove('active');
            item.removeAttribute('aria-current');
        });

        const navBtn = btnEl || document.getElementById('nav-' + screenId);
        if (navBtn && navBtn.classList.contains('submenu-item')) {
            navBtn.classList.add('active');
            navBtn.setAttribute('aria-current', 'page');
        }

        if (window.innerWidth < 992) {
            document.getElementById('sidebar').classList.remove('active');
            document.getElementById('sidebarOverlay').classList.remove('active');
        }
    }

    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('active');
        document.getElementById('sidebarOverlay').classList.toggle('active');
    }

    function copyTextFromElement(elId) {
        const text = document.getElementById(elId).innerText;
        navigator.clipboard.writeText(text);
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Teks berhasil disalin!', showConfirmButton: false, timer: 1500 });
    }

    // Buka tab otomatis jika ada query ?tab=... dari halaman depan
    document.addEventListener('DOMContentLoaded', () => {
        const params = new URLSearchParams(window.location.search);
        const tab = params.get('tab');
        if (tab && document.getElementById(tab)) {
            navigateMenu(tab);
        }
    });

    @if(session('success')) Swal.fire({icon:'success',title:'Berhasil!',text:"{{ session('success') }}",timer:1800,showConfirmButton:false}); @endif
    @if(session('error')) Swal.fire({icon:'error',title:'Perhatian',text:"{{ session('error') }}"}); @endif

    // 1. CARI JUDUL SKRIPSI
    function generateTitles() {
        const prodi = document.getElementById('inputProdi').value.trim();
        const topik = document.getElementById('inputTopik').value.trim();
        const model = document.getElementById('globalAiModel').value;

        if (!prodi || !topik) return Swal.fire('Info', 'Mohon isi Program Studi dan Minat Topik terlebih dahulu.', 'warning');

        document.getElementById('titleResults').innerHTML = '<div class="panel-card text-center py-5 text-primary"><div class="spinner-border mb-3"></div><p class="mb-0 fw-semibold">Dosen AI sedang merumuskan 10 judul skripsi...</p></div>';

        fetch("{{ route('tools.titles') }}", {
            method: "POST",
            headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": csrfToken },
            body: JSON.stringify({ prodi, topik, model })
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                let html = '';
                res.data.forEach(judul => {
                    const safeJudul = judul.replace(/'/g, "\\'");
                    html += `
                    <div class="result-card">
                        <div class="fw-semibold text-dark">${judul}</div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <button class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="useTitleForProject('${safeJudul}', '${prodi.replace(/'/g, "\\'")}')">
                                Pakai Judul
                            </button>
                            <button class="btn btn-sm btn-light border rounded-circle" onclick="navigator.clipboard.writeText('${safeJudul}');Swal.fire({toast:true,position:'top-end',icon:'success',title:'Disalin!',showConfirmButton:false,timer:1000})">
                                <i class="far fa-copy"></i>
                            </button>
                        </div>
                    </div>`;
                });
                document.getElementById('titleResults').innerHTML = html;
                document.getElementById('creditDisplay').innerText = res.new_credits;
            } else {
                Swal.fire('Gagal', res.message, 'error');
            }
        });
    }

    function useTitleForProject(judul, prodi) {
        document.getElementById('formJudulInput').value = judul;
        document.getElementById('formProdiInput').value = prodi;
        document.getElementById('propJudul').value = judul;
        document.getElementById('propProdi').value = prodi;
        navigateMenu('formScreen');
        Swal.fire({ toast: true, position: 'top-end', icon: 'info', title: 'Judul dimasukkan ke form proyek!', showConfirmButton: false, timer: 1800 });
    }

    // 2. CARI JURNAL
    function findJournals() {
        const keyword = document.getElementById('inputJournalKeyword').value.trim();
        const model = document.getElementById('globalAiModel').value;
        if (!keyword) return Swal.fire('Info', 'Masukkan topik jurnal terlebih dahulu.', 'warning');

        document.getElementById('journalResults').innerHTML = '<div class="panel-card text-center py-5 text-warning"><div class="spinner-border mb-3"></div><p class="mb-0 text-dark fw-semibold">Mencari referensi jurnal ilmiah relevan...</p></div>';

        fetch("{{ route('tools.journals') }}", {
            method: "POST",
            headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": csrfToken },
            body: JSON.stringify({ keyword, model })
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                let html = '';
                res.data.forEach(j => {
                    const scholarUrl = `https://scholar.google.com/scholar?q=${encodeURIComponent(j.title)}`;
                    html += `
                    <div class="result-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-3 bg-warning bg-opacity-10 p-3 text-warning"><i class="fas fa-book-journal-whills fa-lg"></i></div>
                            <div>
                                <h6 class="fw-bold mb-1 text-dark">${j.title}</h6>
                                <small class="text-secondary">${j.author} • <strong>${j.journal || 'Jurnal Ilmiah'}</strong> (${j.year})</small>
                            </div>
                        </div>
                        <a href="${scholarUrl}" target="_blank" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-semibold flex-shrink-0">
                            <i class="fas fa-external-link-alt me-1"></i> Google Scholar
                        </a>
                    </div>`;
                });
                document.getElementById('journalResults').innerHTML = html;
                document.getElementById('creditDisplay').innerText = res.new_credits;
            } else {
                Swal.fire('Gagal', res.message, 'error');
            }
        });
    }

    // 3. GENERATOR PROPOSAL (BAB 1-3)
    function generateProposal() {
        const judul = document.getElementById('propJudul').value.trim();
        const prodi = document.getElementById('propProdi').value.trim();
        const metode = document.getElementById('propMetode').value;
        const masalah = document.getElementById('propMasalah').value.trim();
        const model = document.getElementById('globalAiModel').value;

        if (!judul || !prodi) return Swal.fire('Info', 'Mohon lengkapi Judul Proposal dan Program Studi.', 'warning');

        document.getElementById('proposalOutput').innerHTML = '<div class="text-center py-5 text-primary"><div class="spinner-border mb-3"></div><p class="fw-semibold mb-0">Menyusun Proposal Skripsi Bab I, Bab II, dan Bab III sesuai standar DIKTI...</p></div>';

        fetch("{{ route('tools.proposal') }}", {
            method: "POST",
            headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": csrfToken },
            body: JSON.stringify({ judul, prodi, metode, masalah, model })
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                document.getElementById('proposalOutput').innerHTML = marked.parse(res.proposal);
                document.getElementById('creditDisplay').innerText = res.new_credits;
            } else {
                Swal.fire('Gagal', res.message, 'error');
            }
        });
    }

    function transferProposalToFullProject() {
        document.getElementById('formJudulInput').value = document.getElementById('propJudul').value;
        document.getElementById('formProdiInput').value = document.getElementById('propProdi').value;
        navigateMenu('formScreen');
    }

    // 4. PARAFRASE ONLINE
    function runParaphrase() {
        const text = document.getElementById('paraphraseInput').value.trim();
        const style = document.getElementById('paraphraseStyle').value;
        const model = document.getElementById('globalAiModel').value;

        if (text.length < 20) return Swal.fire('Info', 'Masukkan minimal 1 kalimat/paragraf (20 karakter) untuk diparafrase.', 'warning');

        document.getElementById('paraphraseOutput').innerHTML = '<div class="text-center py-5 text-primary"><div class="spinner-border spinner-border-sm me-2"></div> Memparafrase kalimat secara ilmiah...</div>';

        fetch("{{ route('tools.paraphrase') }}", {
            method: "POST",
            headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": csrfToken },
            body: JSON.stringify({ text, style, model })
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                document.getElementById('paraphraseOutput').innerHTML = marked.parse(res.result);
                document.getElementById('creditDisplay').innerText = res.new_credits;
            } else {
                Swal.fire('Gagal', res.message, 'error');
            }
        });
    }

    // 5. AI PPT SIDANG
    function generatePptSlides() {
        const judul = document.getElementById('pptJudul').value.trim();
        const prodi = document.getElementById('pptProdi').value.trim();
        const metode = document.getElementById('pptMetode').value.trim();
        const ringkasan = document.getElementById('pptRingkasan').value.trim();
        const model = document.getElementById('globalAiModel').value;

        if (!judul) return Swal.fire('Info', 'Masukkan Judul Skripsi terlebih dahulu.', 'warning');

        document.getElementById('pptOutput').innerHTML = '<div class="text-center py-5 text-warning"><div class="spinner-border mb-3"></div><p class="fw-semibold text-dark mb-0">Menyusun 9 Slide PPT Sidang beserta Naskah Bicara Presenter...</p></div>';

        fetch("{{ route('tools.ppt') }}", {
            method: "POST",
            headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": csrfToken },
            body: JSON.stringify({ judul, prodi, metode, ringkasan, model })
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                document.getElementById('pptOutput').innerHTML = marked.parse(res.slides);
                document.getElementById('creditDisplay').innerText = res.new_credits;
            } else {
                Swal.fire('Gagal', res.message, 'error');
            }
        });
    }

    // 6. SIMULASI SIDANG SKRIPSI
    let lastDefenseQuestion = '';

    function startDefenseSimulation() {
        const judul = document.getElementById('defJudul').value.trim();
        const metode = document.getElementById('defMetode').value.trim();
        const model = document.getElementById('globalAiModel').value;
        const chatBody = document.getElementById('defenseChatBody');

        if (!judul) return Swal.fire('Info', 'Masukkan Judul Skripsi Anda di sebelah kiri untuk memulai sidang.', 'warning');

        chatBody.innerHTML += `<div class="bubble-solver bubble-ai-solver"><i class="fas fa-spinner fa-spin me-2"></i> Dewan Penguji sedang mempelajari naskah skripsi Anda...</div>`;
        chatBody.scrollTop = chatBody.scrollHeight;

        fetch("{{ route('tools.defense') }}", {
            method: "POST",
            headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": csrfToken },
            body: JSON.stringify({ judul, metode, mode: 'start', model })
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                lastDefenseQuestion = res.reply;
                chatBody.innerHTML += `<div class="bubble-solver bubble-ai-solver">${marked.parse(res.reply)}</div>`;
                document.getElementById('creditDisplay').innerText = res.new_credits;
            } else {
                Swal.fire('Gagal', res.message, 'error');
            }
            chatBody.scrollTop = chatBody.scrollHeight;
        });
    }

    function submitDefenseAnswer() {
        const judul = document.getElementById('defJudul').value.trim();
        const metode = document.getElementById('defMetode').value.trim();
        const answer = document.getElementById('defenseAnswerInput').value.trim();
        const model = document.getElementById('globalAiModel').value;
        const chatBody = document.getElementById('defenseChatBody');

        if (!judul) return Swal.fire('Info', 'Klik "Mulai Sesi Ujian Sidang" terlebih dahulu.', 'warning');
        if (!answer) return Swal.fire('Info', 'Ketik jawaban argumen Anda terlebih dahulu.', 'warning');

        chatBody.innerHTML += `<div class="bubble-solver bubble-user-solver">${answer}</div>`;
        document.getElementById('defenseAnswerInput').value = '';
        const loadId = 'def-load-' + Date.now();
        chatBody.innerHTML += `<div id="${loadId}" class="bubble-solver bubble-ai-solver"><i class="fas fa-spinner fa-spin me-2"></i> Dosen Penguji sedang mengevaluasi jawaban Anda...</div>`;
        chatBody.scrollTop = chatBody.scrollHeight;

        fetch("{{ route('tools.defense') }}", {
            method: "POST",
            headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": csrfToken },
            body: JSON.stringify({ judul, metode, mode: 'answer', question: lastDefenseQuestion, answer, model })
        })
        .then(r => r.json())
        .then(res => {
            const loader = document.getElementById(loadId);
            if (loader) loader.remove();
            if (res.status === 'success') {
                lastDefenseQuestion = res.reply;
                chatBody.innerHTML += `<div class="bubble-solver bubble-ai-solver">${marked.parse(res.reply)}</div>`;
                document.getElementById('creditDisplay').innerText = res.new_credits;
            } else {
                Swal.fire('Gagal', res.message, 'error');
            }
            chatBody.scrollTop = chatBody.scrollHeight;
        });
    }

    // 7. REWARD & CHAT FLOATING & SOLVER
    function claimReward(type) {
        fetch("{{ route('reward.claim') }}", {
            method: "POST",
            headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": csrfToken },
            body: JSON.stringify({ type })
        })
        .then(r => r.json())
        .then(d => {
            if (d.status === 'success') {
                document.getElementById('creditDisplay').innerText = d.new_credits;
                Swal.fire('Berhasil!', d.message, 'success');
            }
        });
    }

    function toggleChat() {
        const w = document.getElementById('chatWindow');
        w.style.display = w.style.display === 'flex' ? 'none' : 'flex';
    }

    function sendChat() {
        const input = document.getElementById('chatInput');
        const message = input.value.trim();
        const model = document.getElementById('globalAiModel').value;
        if (!message) return;

        const body = document.getElementById('chatBody');
        body.innerHTML += `<div class="chat-bubble user-bubble">${message}</div>`;
        input.value = '';
        body.scrollTop = body.scrollHeight;

        fetch("{{ route('chat.ask') }}", {
            method: "POST",
            headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": csrfToken },
            body: JSON.stringify({ message, model })
        })
        .then(r => r.json())
        .then(d => {
            body.innerHTML += `<div class="chat-bubble ai-bubble">${d.reply}</div>`;
            body.scrollTop = body.scrollHeight;
        });
    }

    let solverImageBase64 = null;
    function handleSolverFile(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                solverImageBase64 = e.target.result;
                const preview = document.getElementById('solverImgPreview');
                preview.style.backgroundImage = `url(${solverImageBase64})`;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function clearSolverImage() {
        solverImageBase64 = null;
        document.getElementById('solverFileInput').value = "";
        document.getElementById('solverImgPreview').style.display = 'none';
    }

    function sendSolver() {
        const txt = document.getElementById('solverInput').value.trim();
        const model = document.getElementById('globalAiModel').value;
        const bodyChat = document.getElementById('solverChatBody');

        if (!txt && !solverImageBase64) return Swal.fire('Info', 'Ketik pertanyaan atau upload gambar soal.', 'warning');

        let userHtml = `<div class="bubble-solver bubble-user-solver">`;
        if (solverImageBase64) userHtml += `<img src="${solverImageBase64}" style="max-height:110px; border-radius:8px; display:block; margin-bottom:6px;">`;
        userHtml += `${txt}</div>`;
        bodyChat.innerHTML += userHtml;

        const loadingId = 'load-' + Date.now();
        bodyChat.innerHTML += `<div id="${loadingId}" class="bubble-solver bubble-ai-solver"><i class="fas fa-spinner fa-spin me-2"></i> Menganalisis jawaban...</div>`;
        bodyChat.scrollTop = bodyChat.scrollHeight;

        const imgToSend = solverImageBase64;
        document.getElementById('solverInput').value = '';
        clearSolverImage();

        fetch("{{ route('tools.solve') }}", {
            method: "POST",
            headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": csrfToken },
            body: JSON.stringify({ question: txt || "Jelaskan dan selesaikan soal pada gambar ini", image: imgToSend, model })
        })
        .then(r => r.json())
        .then(res => {
            const loader = document.getElementById(loadingId);
            if (loader) loader.remove();
            if (res.status === 'success') {
                bodyChat.innerHTML += `<div class="bubble-solver bubble-ai-solver">${marked.parse(res.answer)}</div>`;
                document.getElementById('creditDisplay').innerText = res.new_credits;
                if (window.MathJax) MathJax.typesetPromise([bodyChat]);
            } else {
                bodyChat.innerHTML += `<div class="bubble-solver bubble-ai-solver text-danger">Error: ${res.message}</div>`;
            }
            bodyChat.scrollTop = bodyChat.scrollHeight;
        });
    }
</script>
</body>
</html>
