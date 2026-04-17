<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workspace - {{ $project->judul }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        :root {
            --sidebar-bg: #1e1f20;
            --main-bg: #ffffff;
            --text-primary: #1f1f1f;
            --text-secondary: #5f6368;
            --accent-color: #4285f4; /* Google Blue */
            --success-color: #10b981;
        }

        body { font-family: 'Inter', sans-serif; background-color: var(--main-bg); color: var(--text-primary); overflow-x: hidden; }

        /* --- SIDEBAR (Sama Persis dengan Pre-Generate) --- */
        .sidebar {
            width: 280px; height: 100vh; background: var(--sidebar-bg); color: #e3e3e3;
            position: fixed; top: 0; left: 0; display: flex; flex-direction: column; padding: 20px 15px;
            z-index: 1050; transition: transform 0.3s ease;
        }
        .btn-new-chat {
            background: #282a2c; color: #e3e3e3; border-radius: 50px; padding: 12px 20px; font-weight: 500;
            cursor: pointer; display: flex; align-items: center; gap: 12px; margin-bottom: 25px; text-decoration: none;
        }
        .btn-new-chat:hover { background: #37393b; color: white; }
        .history-item {
            display: block; padding: 10px 15px; border-radius: 50px; color: #e3e3e3; text-decoration: none;
            font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 2px;
        }
        .history-item:hover { background: #282a2c; }
        
        /* --- MAIN CONTENT --- */
        .main-content { margin-left: 280px; min-height: 100vh; display: flex; flex-direction: column; transition: margin-left 0.3s ease; }
        
        .top-nav {
            padding: 15px 30px; display: flex; justify-content: space-between; align-items: center;
            position: sticky; top: 0; background: rgba(255,255,255,0.95); backdrop-filter: blur(8px); z-index: 50;
            border-bottom: 1px solid #f0f0f0;
        }

        /* --- PROJECT WORKSPACE --- */
        .workspace-container { padding: 30px; max-width: 1200px; margin: 0 auto; width: 100%; }

        .project-header {
            background: #f8fafc; border-radius: 16px; padding: 25px; margin-bottom: 30px; border: 1px solid #e2e8f0;
        }

        .chapter-card {
            background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; margin-bottom: 15px;
            transition: all 0.2s; display: flex; justify-content: space-between; align-items: center;
        }
        .chapter-card:hover { border-color: var(--accent-color); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .chapter-card.done { border-left: 4px solid var(--success-color); background: #f0fdf4; }
        
        .status-badge { font-size: 0.75rem; padding: 4px 10px; border-radius: 20px; font-weight: 600; text-transform: uppercase; }
        .badge-empty { background: #f3f4f6; color: #6b7280; }
        .badge-done { background: #d1fae5; color: #065f46; }

        /* --- STICKY SIDE PANEL (DOWNLOAD) --- */
        .sticky-panel { position: sticky; top: 100px; }
        .panel-card {
            background: white; border: 1px solid #e5e7eb; border-radius: 16px; padding: 20px;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);
        }

        .form-control-sm-custom {
            background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px; font-size: 0.9rem;
        }

        /* Responsive */
        .mobile-toggle { display: none; border: none; background: none; font-size: 1.5rem; color: var(--text-secondary); }
        .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1040; }

        @media (max-width: 991px) {
            .sidebar { transform: translateX(-100%); } .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; } .mobile-toggle { display: block; }
            .sidebar-overlay.active { display: block; }
            .project-header h2 { font-size: 1.5rem; }
        }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<div class="sidebar" id="sidebar">
    <div class="d-flex align-items-center justify-content-between mb-4 px-2">
        <a href="{{ route('home') }}" class="text-white text-decoration-none fw-bold fs-5">SkripsiAI</a>
        <button class="btn text-white d-md-none" onclick="toggleSidebar()"><i class="fas fa-times"></i></button>
    </div>

    <a href="{{ route('app.create') }}" class="btn-new-chat">
        <i class="fas fa-arrow-left"></i> <span>Kembali ke Menu</span>
    </a>

    <div class="text-uppercase small text-muted fw-bold px-3 mb-2" style="font-size: 0.7rem;">Proyek Aktif</div>
    
    <div style="flex-grow: 1; overflow-y: auto;">
        @php
            $historySidebar = \App\Models\Project::where('user_id', Auth::id())->orderBy('updated_at', 'desc')->take(10)->get();
        @endphp

        @foreach($historySidebar as $h)
            <a href="{{ route('app.index', ['id' => $h->id]) }}" class="history-item {{ $h->id == $project->id ? 'bg-secondary bg-opacity-25 text-white' : '' }}" title="{{ $h->judul }}">
                <i class="far fa-file-alt me-2"></i> {{ Str::limit($h->judul, 20) }}
            </a>
        @endforeach
    </div>

    <div class="mt-3 pt-3 border-top border-secondary">
        <div class="d-flex align-items-center gap-2 px-2 text-white-50">
            <small>Token: {{ Auth::user()->credits }}</small>
        </div>
    </div>
</div>

<div class="main-content">
    
    <div class="top-nav">
        <button class="mobile-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
        <h6 class="mb-0 fw-bold text-secondary d-none d-md-block">Workspace Penulisan</h6>
        
        <div class="d-flex align-items-center gap-3 ms-auto">
            
            <div class="d-none d-md-flex align-items-center bg-white rounded-pill px-3 py-1 shadow-sm border">
                <i class="fas fa-robot text-accent-color me-2" style="color: var(--accent-color);"></i>
                <select id="globalAiModel" class="border-0 bg-transparent fw-bold text-secondary" style="outline:none; cursor:pointer; font-size: 0.9rem;">
                    <option value="gemini" selected>Gemini AI</option>
                    <option value="groq">Groq (Ultra Fast)</option>
                </select>
            </div>

            <div class="dropdown">
                <button class="btn btn-light rounded-circle border" type="button" data-bs-toggle="dropdown" style="width: 40px; height: 40px;">
                    {{ substr(Auth::user()->name, 0, 1) }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2">
                    <li><h6 class="dropdown-header">{{ Auth::user()->name }}</h6></li>
                    
                    <li class="d-md-none mb-3 px-3">
                        <label class="small text-muted">Model AI</label>
                        <select class="form-select form-select-sm" onchange="document.getElementById('globalAiModel').value = this.value">
                            <option value="gemini" selected>Gemini AI</option>
                            <option value="groq">Groq (Llama 3)</option>
                        </select>
                    </li>

                    <li><a class="dropdown-item" href="{{ route('app.create') }}">Dashboard Utama</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button class="dropdown-item text-danger">Logout</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="workspace-container">
        
        <div class="project-header">
            <span class="badge bg-primary bg-opacity-10 text-primary mb-2">Judul Skripsi</span>
            <h2 class="fw-bold text-dark mb-3">{{ $project->judul }}</h2>
            <div class="d-flex flex-wrap gap-3 text-muted small">
                <span><i class="fas fa-graduation-cap me-1"></i> {{ $project->prodi }}</span>
                <span><i class="fas fa-code-branch me-1"></i> {{ $project->metode_penelitian }}</span>
                <span><i class="far fa-clock me-1"></i> Updated {{ $project->updated_at->diffForHumans() }}</span>
            </div>
        </div>

        {{-- ================= VALIDASI SITASI & DAFTAR PUSTAKA ================= --}}
        @if(!empty($project->citation_validation))
            @php
                $validation = json_decode($project->citation_validation, true);
            @endphp
            
            <div class="mt-3 mb-3">
                <button type="button" onclick="refineReferences()" class="btn btn-outline-primary">
                    🔧 Rapikan Daftar Pustaka (AI)
                </button>
            </div>

            <div class="alert {{ empty($validation['missing_in_references']) && empty($validation['unused_references']) 
                    ? 'alert-success' 
                    : 'alert-warning' }} mt-3">

                <strong>Validasi Sitasi & Daftar Pustaka</strong><br>

                @if(empty($validation['missing_in_references']) && empty($validation['unused_references']))
                    ✅ Semua sitasi sudah sesuai dengan daftar pustaka.
                @endif

                @if(!empty($validation['missing_in_references']))
                    <div class="mt-2">
                        <strong>⚠️ Sitasi ada di teks tetapi tidak ada di daftar pustaka:</strong>
                        <ul>
                            @foreach($validation['missing_in_references'] as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(!empty($validation['unused_references']))
                    <div class="mt-2">
                        <strong>⚠️ Daftar pustaka tidak pernah disitasi:</strong>
                        <ul>
                            @foreach($validation['unused_references'] as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endif
        {{-- ================= END VALIDASI ================= --}}

        <div class="row g-4">
            <div class="col-lg-8">
                <h5 class="fw-bold mb-3 text-secondary">Struktur Naskah</h5>
                
                @php
                    $chapters = [
                        'bab1' => 'BAB I - Pendahuluan',
                        'bab2' => 'BAB II - Tinjauan Pustaka',
                        'bab3' => 'BAB III - Metodologi Penelitian',
                        'bab4' => 'BAB IV - Hasil dan Pembahasan',
                        'bab5' => 'BAB V - Penutup'
                    ];
                @endphp

                @foreach($chapters as $key => $label)
                    @php $hasContent = !empty($project->{$key . '_content'}); @endphp
                    
                    <div class="chapter-card {{ $hasContent ? 'done' : '' }}">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center" 
                                 style="width: 40px; height: 40px; background: {{ $hasContent ? '#d1fae5' : '#f3f4f6' }}; color: {{ $hasContent ? '#059669' : '#9ca3af' }}">
                                <i class="fas {{ $hasContent ? 'fa-check' : 'fa-pen' }}"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">{{ $label }}</h6>
                                <small class="text-muted">
                                    {{ $hasContent ? 'Tersedia. Siap unduh.' : 'Belum ditulis.' }}
                                </small>
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <span class="status-badge {{ $hasContent ? 'badge-done' : 'badge-empty' }} d-block mb-2">
                                {{ $hasContent ? 'SELESAI' : 'KOSONG' }}
                            </span>
                            <button onclick="generateChapter('{{ $key }}')" class="btn btn-sm {{ $hasContent ? 'btn-outline-primary' : 'btn-primary' }} rounded-pill px-3">
                                <i class="fas fa-magic me-1"></i> {{ $hasContent ? 'Re-Generate' : 'Tulis' }}
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="col-lg-4">
                <div class="sticky-panel">
                    <div class="panel-card">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <div class="bg-primary text-white rounded p-2"><i class="fas fa-download"></i></div>
                            <h5 class="fw-bold mb-0">Download File</h5>
                        </div>
                        <p class="text-muted small mb-4">Isi identitas diri untuk dicetak pada halaman sampul (Cover).</p>
                        
                        <form action="{{ route('app.downloadDocx', ['id' => $project->id]) }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Nama Lengkap</label>
                                <input type="text" name="nama" class="form-control form-control-sm-custom" placeholder="Cth: Budi Santoso" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">NIM / NPM</label>
                                <input type="text" name="npm" class="form-control form-control-sm-custom" placeholder="Cth: 12345678" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label small fw-bold">Nama Kampus</label>
                                <input type="text" name="kampus" class="form-control form-control-sm-custom" placeholder="Cth: Universitas Indonesia" required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label small fw-bold">Pengaturan Margin (cm)</label>
                                <div class="row g-2">
                                    <div class="col-3">
                                        <label class="small text-muted mb-1 d-block text-center" style="font-size: 0.75rem;">Top</label>
                                        <input type="number" step="0.1" name="margin_top" class="form-control form-control-sm-custom text-center" value="4">
                                    </div>
                                    <div class="col-3">
                                        <label class="small text-muted mb-1 d-block text-center" style="font-size: 0.75rem;">Left</label>
                                        <input type="number" step="0.1" name="margin_left" class="form-control form-control-sm-custom text-center" value="4">
                                    </div>
                                    <div class="col-3">
                                        <label class="small text-muted mb-1 d-block text-center" style="font-size: 0.75rem;">Bottom</label>
                                        <input type="number" step="0.1" name="margin_bottom" class="form-control form-control-sm-custom text-center" value="3">
                                    </div>
                                    <div class="col-3">
                                        <label class="small text-muted mb-1 d-block text-center" style="font-size: 0.75rem;">Right</label>
                                        <input type="number" step="0.1" name="margin_right" class="form-control form-control-sm-custom text-center" value="3">
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-dark py-2 rounded-3 fw-bold">
                                    <i class="fas fa-file-word me-2"></i> Word (.docx)
                                </button>
                                <button type="submit" formaction="{{ route('project.download.pdf', ['id' => $project->id]) }}" class="btn btn-outline-danger py-2 rounded-3 fw-bold">
                                    <i class="fas fa-file-pdf me-2"></i> PDF Document
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="text-center mt-4 text-muted small">
                        <i class="fas fa-info-circle me-1"></i> Generate membutuhkan 1 token per bab.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- LOADING OVERLAY --}}
<div id="loadingOverlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(255,255,255,0.95); z-index:9999; justify-content:center; align-items:center; flex-direction:column;">
    <div class="spinner-border text-primary mb-3" role="status"></div>
    <h5 class="fw-bold text-dark">AI Sedang Menulis...</h5>
    <p class="text-muted small text-center px-3" style="max-width:400px;">Mohon tunggu, sedang menyusun narasi akademik.</p>
</div>

{{-- FORM HIDDEN UNTUK REFINE REFERENCES (Agar bisa pakai JS & Model) --}}
<form id="refineForm" method="POST" action="{{ route('project.refine.references', $project->id) }}" style="display:none;">
    @csrf
    <input type="hidden" name="model" id="refineModelInput">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function toggleSidebar(){
        document.getElementById('sidebar').classList.toggle('active');
        document.getElementById('sidebarOverlay').classList.toggle('active');
    }

    // UPDATE: Generate Function dengan Pesan Kontekstual
    function generateChapter(chapter) {
        let selectedModel = document.getElementById('globalAiModel').value;
        
        let loadingText = document.querySelector('#loadingOverlay h5');
        let loadingDesc = document.querySelector('#loadingOverlay p');
        
        // Pesan loading dinamis biar user tidak bosan
        if(chapter === 'bab4' || chapter === 'bab5') {
            loadingText.innerText = "Menganalisis & Menjaga Benang Merah...";
            loadingDesc.innerText = "AI sedang membaca bab-bab sebelumnya (Bab 1-3) agar pembahasan dan kesimpulan tetap konsisten.";
        } else if (chapter === 'bab2') {
            loadingText.innerText = "Mencari Referensi Teori...";
            loadingDesc.innerText = "AI sedang menyusun tinjauan pustaka dan mensintesis pendapat ahli.";
        } else {
            loadingText.innerText = "AI Sedang Menulis...";
            loadingDesc.innerText = "Menyusun narasi akademik yang formal dan rapi.";
        }

        document.getElementById('loadingOverlay').style.display = 'flex';

        fetch("{{ route('project.generate', ['id' => $project->id]) }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ chapter: chapter, model: selectedModel })
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('loadingOverlay').style.display = 'none';
            if(data.status === 'success') {
                Swal.fire({
                    title: 'Selesai!', text: 'Bab berhasil ditulis.', icon: 'success', timer: 1500, showConfirmButton: false
                }).then(() => { location.reload(); });
            } else {
                Swal.fire('Gagal', data.error || 'Terjadi kesalahan.', 'error');
            }
        })
        .catch(error => {
            document.getElementById('loadingOverlay').style.display = 'none';
            Swal.fire('Error', 'Koneksi terputus.', 'error');
        });
    }

    function refineReferences() {
        let selectedModel = document.getElementById('globalAiModel').value;
        document.getElementById('refineModelInput').value = selectedModel;
        document.getElementById('loadingOverlay h5').innerText = "Merapikan Daftar Pustaka...";
        document.getElementById('loadingOverlay').style.display = 'flex'; 
        document.getElementById('refineForm').submit();
    }
</script>

</body>
</html>