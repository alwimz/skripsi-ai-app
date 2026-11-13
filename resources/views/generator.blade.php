<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Studio Penulisan — {{ $project->judul }}</title>
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
            --bg-canvas: #f1f5f9;
            --bg-sidebar: #f8fafc;
            --bg-white: #ffffff;
            --text-main: #0f172a;
            --text-secondary: #475569;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --blue: #2563eb;
            --blue-hover: #1d4ed8;
            --dark-active: #0f172a;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-canvas);
            color: var(--text-main);
            display: flex;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        /* Left Studio Sidebar (Matches Mantra Riset Studio Screenshot) */
        .studio-sidebar {
            width: 260px;
            background: var(--bg-sidebar);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 40;
            overflow-y: auto;
        }

        .sidebar-top {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12.5px;
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 12px;
        }

        .back-link:hover {
            color: var(--text-main);
        }

        .proj-title-side {
            font-size: 12px;
            font-weight: 700;
            color: var(--text-main);
            text-transform: uppercase;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .proj-type-side {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            margin-top: 3px;
        }

        .progress-box {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border);
        }

        .progress-top {
            display: flex;
            justify-content: space-between;
            font-size: 11.5px;
            font-weight: 500;
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
            background: var(--blue);
            border-radius: 999px;
            transition: width 0.3s ease;
        }

        .chapter-nav {
            padding: 12px 10px;
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .chapter-item {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 9px 12px;
            border-radius: 8px;
            border: none;
            background: transparent;
            color: var(--text-secondary);
            font-size: 13px;
            font-weight: 500;
            font-family: inherit;
            cursor: pointer;
            text-align: left;
            transition: all 0.12s ease;
        }

        .chapter-item:hover {
            background: #e2e8f0;
            color: var(--text-main);
        }

        .chapter-item.active {
            background: var(--dark-active);
            color: #ffffff;
            font-weight: 600;
        }

        .ch-status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #cbd5e1;
            flex-shrink: 0;
        }

        .ch-status-dot.done {
            background: #22c55e;
        }

        /* Callout Box: Lanjutkan ke Bab IV-V */
        .continue-box {
            margin: 8px 12px;
            padding: 12px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 10px;
        }

        .continue-title {
            font-size: 12.5px;
            font-weight: 700;
            color: #1e40af;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .continue-desc {
            font-size: 11.5px;
            color: #3b82f6;
            line-height: 1.45;
            margin-bottom: 8px;
        }

        .sidebar-tools {
            margin-top: auto;
            padding: 12px 10px;
            border-top: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        /* Main Studio Area */
        .studio-main {
            flex: 1;
            margin-left: 260px;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Top Studio Toolbar */
        .studio-toolbar {
            height: 54px;
            background: var(--bg-white);
            border-bottom: 1px solid var(--border);
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 30;
            gap: 12px;
        }

        .toolbar-title {
            font-size: 12.5px;
            font-weight: 600;
            color: var(--text-main);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 420px;
            text-transform: uppercase;
        }

        .toolbar-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        .tool-pill {
            font-family: inherit;
            font-size: 12px;
            font-weight: 500;
            color: var(--text-secondary);
            background: var(--bg-sidebar);
            border: 1px solid var(--border);
            border-radius: 7px;
            padding: 6px 10px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .tool-pill:hover {
            background: #e2e8f0;
            color: var(--text-main);
        }

        .btn-blue {
            font-family: inherit;
            font-size: 12.5px;
            font-weight: 600;
            color: #ffffff;
            background: var(--blue);
            border: none;
            border-radius: 7px;
            padding: 7px 14px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-blue:hover {
            background: var(--blue-hover);
        }

        /* Document Action Strip above A4 Paper */
        .paper-subbar {
            max-width: 820px;
            width: 100%;
            margin: 20px auto 12px;
            padding: 10px 16px;
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }

        /* Realistic A4 Microsoft Word Paper */
        .word-paper-wrap {
            flex: 1;
            padding: 0 20px 80px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .word-paper {
            width: 100%;
            max-width: 820px;
            min-height: 980px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.06);
            padding: 64px 76px;
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 1.75;
            color: #0f172a;
            text-align: justify;
        }

        .word-paper h2.bab-heading {
            font-family: 'Times New Roman', Times, serif;
            font-size: 14pt;
            font-weight: bold;
            text-align: center;
            margin-bottom: 24px;
            line-height: 1.4;
            text-transform: uppercase;
        }

        .word-paper h3.subbab-heading {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            font-weight: bold;
            text-align: left;
            margin: 20px 0 8px;
            text-indent: 0;
        }

        .word-paper p {
            text-indent: 1.27cm;
            margin-bottom: 12px;
            text-align: justify;
        }

        /* Blue Academic Citation Link Highlight (Matches Mantra Riset Screenshot) */
        .cite-link {
            color: #2563eb;
            text-decoration: underline;
            text-underline-offset: 2px;
            cursor: pointer;
        }

        /* Floating Progress Toast (Bottom-Left — Matches Screenshot) */
        .floating-progress {
            position: fixed;
            bottom: 20px;
            left: 280px;
            width: 420px;
            background: #ffffff;
            border: 1px solid #bfdbfe;
            border-radius: 12px;
            box-shadow: 0 15px 35px -5px rgba(15, 23, 42, 0.15);
            padding: 14px 18px;
            z-index: 60;
            display: none;
        }

        /* Modal Overlay (Pedoman Kampus, Revisi Dosen, & Download Sampul) */
        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(3px);
            z-index: 80;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-backdrop.open {
            display: flex;
        }

        .modal-card {
            background: #ffffff;
            border-radius: 14px;
            border: 1px solid var(--border);
            width: 100%;
            max-width: 560px;
            max-height: 90vh;
            overflow-y: auto;
            padding: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border);
        }

        .modal-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-main);
        }

        .form-label {
            display: block;
            font-size: 12.5px;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 6px;
        }

        .form-input, .form-select, .form-textarea {
            width: 100%;
            font-family: inherit;
            font-size: 13.5px;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            outline: none;
            margin-bottom: 14px;
        }

        .form-textarea {
            min-height: 95px;
            resize: vertical;
        }

        @media (max-width: 900px) {
            .studio-sidebar {
                transform: translateX(-100%);
                transition: transform 0.2s ease;
            }
            .studio-sidebar.open {
                transform: translateX(0);
            }
            .studio-main {
                margin-left: 0;
            }
            .word-paper {
                padding: 32px 24px;
            }
            .floating-progress {
                left: 16px;
                right: 16px;
                width: auto;
            }
        }
    </style>
</head>
<body>

    @php
        $chaptersList = [
            'bab1' => ['num' => 'I', 'label' => 'Bab I Pendahuluan', 'full' => 'PENDAHULUAN'],
            'bab2' => ['num' => 'II', 'label' => 'Bab II Tinjauan Pustaka', 'full' => 'TINJAUAN PUSTAKA'],
            'bab3' => ['num' => 'III', 'label' => 'Bab III Metodologi', 'full' => 'METODOLOGI PENELITIAN'],
            'bab4' => ['num' => 'IV', 'label' => 'Bab IV Hasil Penelitian', 'full' => 'HASIL DAN PEMBAHASAN'],
            'bab5' => ['num' => 'V', 'label' => 'Bab V Penutup', 'full' => 'PENUTUP'],
        ];
        $doneCount = 0;
        foreach (array_keys($chaptersList) as $ck) {
            if (!empty($project->{$ck . '_content'})) $doneCount++;
        }
        $pctDone = round(($doneCount / 5) * 100);
        $tahap = $meta['tahap_pengerjaan'] ?? 'Skripsi Penuh (Bab I–V)';
    @endphp

    <!-- Left Studio Sidebar -->
    <aside class="studio-sidebar" id="studio-sidebar">
        <div class="sidebar-top">
            <a href="{{ route('app.create', ['tab' => 'projects']) }}" class="back-link">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                <span>Kembali ke Proyek</span>
            </a>
            <div class="proj-title-side">{{ $project->judul }}</div>
            <div class="proj-type-side">{{ $tahap }}</div>
        </div>

        <div class="progress-box">
            <div class="progress-top">
                <span id="sidebar-done-label">{{ $doneCount }} dari 5 Bab</span>
                <span id="sidebar-pct-label">{{ $pctDone }}% selesai</span>
            </div>
            <div class="progress-track">
                <div class="progress-fill" id="sidebar-progress-fill" style="width: {{ $pctDone }}%;"></div>
            </div>
        </div>

        <!-- Daftar Bab -->
        <div class="chapter-nav">
            @foreach($chaptersList as $key => $info)
                @php $hasContent = !empty($project->{$key . '_content'}); @endphp
                <button type="button" class="chapter-item {{ $key === 'bab1' ? 'active' : '' }}" id="nav-item-{{ $key }}" onclick="selectChapterView('{{ $key }}')">
                    <span>{{ $info['label'] }}</span>
                    <span class="ch-status-dot {{ $hasContent ? 'done' : '' }}" id="dot-{{ $key }}"></span>
                </button>
            @endforeach

            <button type="button" class="chapter-item" id="nav-item-daftar_pustaka" onclick="selectChapterView('daftar_pustaka')">
                <span>Daftar Pustaka</span>
                <span class="ch-status-dot {{ !empty($project->daftar_pustaka) ? 'done' : '' }}" id="dot-daftar_pustaka"></span>
            </button>
        </div>

        <!-- Callout: Lanjutkan Proposal Bab 1-3 atau Bab 4-5 -->
        <div class="continue-box">
            <div class="continue-title">
                <span>⚡ Susun Otomatis</span>
            </div>
            <div class="continue-desc">
                Susun berurutan Bab I–III untuk Seminar Proposal, atau lanjutkan ke Bab IV–V untuk Skripsi penuh.
            </div>
            <button type="button" class="btn-blue" style="width: 100%; justify-content: center; font-size: 12px; padding: 7px 10px;" onclick="generateSequentialProposal()">
                Generate Proposal (Bab I–III)
            </button>
        </div>

        <!-- Bottom Tools: Pedoman Kampus & Revisi Dosen -->
        <div class="sidebar-tools">
            <button type="button" class="chapter-item" onclick="openModal('modal-guideline')">
                <span>📄 Pedoman Kampus</span>
                <span style="font-size: 11px; color: var(--blue);">{{ !empty($meta['pedoman_text']) ? 'Aktif' : 'Atur' }}</span>
            </button>
            <button type="button" class="chapter-item" onclick="openModal('modal-revise')">
                <span>✏️ Revisi Dosen (AI)</span>
                <span style="font-size: 11px; color: var(--text-muted);">Foto/Doc</span>
            </button>
            <button type="button" class="chapter-item" onclick="openModal('modal-download')">
                <span>🎓 Logo & Unduh File</span>
                <span style="font-size: 11px; color: var(--text-muted);">.docx/.pdf</span>
            </button>
        </div>
    </aside>

    <!-- Main Studio Area -->
    <div class="studio-main">
        <!-- Top Studio Toolbar (Matches Mantra Riset Topbar) -->
        <header class="studio-toolbar">
            <div style="display: flex; align-items: center; gap: 10px; min-width: 0;">
                <button type="button" class="tool-pill" style="display: none;" id="mobile-sidebar-btn" onclick="document.getElementById('studio-sidebar').classList.toggle('open')">☰</button>
                <div class="toolbar-title">{{ $project->judul }}</div>
            </div>

            <div class="toolbar-actions">
                <select id="studio-model" class="tool-pill">
                    <option value="auto">AI: Otomatis</option>
                    <option value="gemini">Gemini AI</option>
                    <option value="groq">Groq Llama 3.3</option>
                </select>

                <span class="tool-pill">Nomor 1.1 / 1.1.1</span>
                <span class="tool-pill">Sitasi: {{ $meta['sitasi']['gaya'] ?? 'APA 7th' }}</span>

                <button type="button" class="tool-pill" onclick="openModal('modal-guideline')">
                    Pedoman Kampus
                </button>

                <button type="button" class="tool-pill" onclick="openModal('modal-revise')">
                    Revisi Bab
                </button>

                <button type="button" class="btn-blue" onclick="openModal('modal-download')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    <span>Unduh Proposal / Skripsi</span>
                </button>
            </div>
        </header>

        <!-- Center Document Canvas -->
        <div class="word-paper-wrap">
            <!-- Sub-bar Aksi Bab Aktif -->
            <div class="paper-subbar">
                <div>
                    <span style="font-size: 13px; font-weight: 700; color: var(--text-main);" id="active-chapter-title">BAB I · PENDAHULUAN</span>
                    <span style="font-size: 12px; color: var(--text-muted); margin-left: 8px;" id="active-chapter-status">Pratinjau Kertas A4 (Times New Roman 12pt)</span>
                </div>

                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <button type="button" class="tool-pill" id="btn-write-chapter" onclick="generateActiveChapter()">
                        ✨ Tulis / Susun Ulang Bab Ini
                    </button>
                    <button type="button" class="tool-pill" onclick="openModal('modal-revise')">
                        ✏️ Revisi Dosen (Ketik / Upload Coretan)
                    </button>
                    <button type="button" class="tool-pill" id="btn-save-manual" onclick="saveManualEdits()" style="display: none; background: #dcfce7; border-color: #86efac; color: #166534; font-weight: 600;">
                        💾 Simpan Editan Teks
                    </button>
                </div>
            </div>

            <!-- Realistic A4 Microsoft Word Paper -->
            <div class="word-paper" id="word-paper-canvas" contenteditable="true" oninput="document.getElementById('btn-save-manual').style.display = 'inline-flex';">
                <!-- Diisi oleh JavaScript sesuai Bab yang dipilih -->
            </div>
        </div>
    </div>

    <!-- Floating Progress Toast (Bottom-Left — Matches Mantra Riset Screenshot) -->
    <div class="floating-progress" id="floating-progress">
        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 12.5px; font-weight: 600; color: var(--blue); margin-bottom: 6px;">
            <span id="fp-label">Menulis Latar Belakang - Rumusan Masalah - Tujuan Penelitian...</span>
            <span id="fp-pct">35%</span>
        </div>
        <div class="progress-track" style="height: 7px; margin-bottom: 6px;">
            <div class="progress-fill" id="fp-bar" style="width: 35%;"></div>
        </div>
        <div style="font-size: 11px; color: var(--text-muted);">
            Tunggu beberapa saat, jangan tutup tab ini sampai proses penulisan selesai.
        </div>
    </div>

    <!-- ================================================================= -->
    <!-- MODAL 1: UPLOAD PEDOMAN SKRIPSI KAMPUS (DI ATAS STRUKTUR NASKAH) -->
    <!-- ================================================================= -->
    <div class="modal-backdrop" id="modal-guideline">
        <div class="modal-card">
            <div class="modal-header">
                <div class="modal-title">Pedoman Skripsi Kampus & Data Awal</div>
                <button type="button" class="tool-pill" onclick="closeModal('modal-guideline')">✕</button>
            </div>
            <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 14px;">
                Setiap kampus memiliki buku pedoman penulisan skripsi masing-masing. Unggah berkas pedoman kampusmu (.pdf / .docx) atau tuliskan aturan sub-bab khusus agar AI menyesuaikan naskah Bab I–V.
            </p>
            <form id="form-guideline" onsubmit="submitGuideline(event)">
                <label class="form-label">Pilih Berkas Pedoman Kampus (.pdf / .docx / .txt)</label>
                <input type="file" name="pedoman_file" id="guideline-file" class="form-input" accept=".pdf,.docx,.doc,.txt">

                <label class="form-label">Aturan Sub-bab / Catatan Pedoman Kampus (Opsional)</label>
                <textarea name="pedoman_text" id="guideline-text" class="form-textarea" placeholder="Contoh: Bab I wajib memuat 1.1 Konteks Penelitian, 1.2 Fokus Penelitian...">{{ $meta['pedoman_text'] ?? '' }}</textarea>

                <label class="form-label">Data Awal Penelitian / Fenomena Objek (Opsional)</label>
                <textarea name="data_awal" id="guideline-data-awal" class="form-textarea" style="min-height: 70px;" placeholder="Contoh: Data observasi awal di lokasi penelitian...">{{ $meta['data_awal'] ?? '' }}</textarea>

                <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 8px;">
                    <button type="button" class="tool-pill" onclick="closeModal('modal-guideline')">Batal</button>
                    <button type="submit" class="btn-blue" id="btn-save-guideline">Simpan Pedoman Kampus</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ================================================================= -->
    <!-- MODAL 2: REVISI BAB (KETIK LANGSUNG + UPLOAD GAMBAR CORETAN / DOC) -->
    <!-- ================================================================= -->
    <div class="modal-backdrop" id="modal-revise">
        <div class="modal-card">
            <div class="modal-header">
                <div class="modal-title">Revisi Bab Bersama AI (<span id="revise-modal-bab-label">BAB I</span>)</div>
                <button type="button" class="tool-pill" onclick="closeModal('modal-revise')">✕</button>
            </div>
            <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 14px;">
                Ketik instruksi revisi dari dosen pembimbing, atau unggah <strong>foto coretan dosen (JPG/PNG)</strong> maupun <strong>dokumen revisi (.docx / .pdf)</strong>. AI akan membaca konteksnya dan memperbaiki bab ini.
            </p>
            <form id="form-revise" onsubmit="submitRevision(event)">
                <label class="form-label">Pilih Bab yang Direvisi</label>
                <select id="revise-chapter-select" class="form-select">
                    <option value="bab1">Bab I · Pendahuluan</option>
                    <option value="bab2">Bab II · Tinjauan Pustaka</option>
                    <option value="bab3">Bab III · Metodologi Penelitian</option>
                    <option value="bab4">Bab IV · Hasil dan Pembahasan</option>
                    <option value="bab5">Bab V · Penutup</option>
                </select>

                <label class="form-label">Catatan / Instruksi Revisi (Ketik Langsung)</label>
                <textarea id="revise-instruction" class="form-textarea" placeholder="Contoh: Tambahkan data empiris terbaru di paragraf ke-2 latar belakang, dan perjelas rumusan masalah menjadi 3 poin sesuai coretan dosen..."></textarea>

                <label class="form-label">Unggah Foto Coretan Dosen (JPG/PNG) atau Dokumen Revisi (.docx / .pdf) — Opsional</label>
                <input type="file" id="revise-file" class="form-input" accept="image/*,.pdf,.docx,.doc,.txt">

                <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 8px;">
                    <button type="button" class="tool-pill" onclick="closeModal('modal-revise')">Batal</button>
                    <button type="submit" class="btn-blue" id="btn-submit-revise">Proses Revisi dengan AI</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ================================================================= -->
    <!-- MODAL 3: UPLOAD LOGO KAMPUS (DI ATAS NAMA) & DOWNLOAD WORD / PDF -->
    <!-- ================================================================= -->
    <div class="modal-backdrop" id="modal-download">
        <div class="modal-card">
            <div class="modal-header">
                <div class="modal-title">Pengaturan Sampul (Cover) & Unduh Naskah</div>
                <button type="button" class="tool-pill" onclick="closeModal('modal-download')">✕</button>
            </div>

            <form id="form-download" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="logo_base64" id="download-logo-base64" value="{{ $meta['logo_base64'] ?? '' }}">

                <!-- Jenis Dokumen Sampul -->
                <label class="form-label">Format Dokumen yang Diunduh</label>
                <select name="jenis_dokumen" class="form-select">
                    <option value="PROPOSAL SKRIPSI" {{ str_contains($tahap, 'Proposal') ? 'selected' : '' }}>Proposal Skripsi (Cover Proposal + Bab I–III + Daftar Pustaka)</option>
                    <option value="SKRIPSI" {{ !str_contains($tahap, 'Proposal') ? 'selected' : '' }}>Skripsi Lengkap (Cover Skripsi + Bab I–V + Daftar Pustaka)</option>
                </select>

                <!-- UPLOAD LOGO KAMPUS DI ATAS NAMA LENGKAP -->
                <label class="form-label">Upload Logo Kampus (Dicetak pada Halaman Sampul / Cover)</label>
                <div style="display: flex; align-items: center; gap: 14px; padding: 12px; border: 1px dashed var(--border); border-radius: 10px; background: var(--bg-sidebar); margin-bottom: 14px;">
                    <img id="cover-logo-preview" src="{{ $meta['logo_base64'] ?? '' }}" alt="Logo" style="width: 52px; height: 52px; object-fit: contain; border-radius: 6px; background: #fff; border: 1px solid var(--border); {{ empty($meta['logo_base64']) ? 'display:none;' : '' }}">
                    <div style="flex: 1;">
                        <input type="file" name="logo_kampus" accept="image/png,image/jpeg,image/webp" style="font-size: 12.5px;" onchange="previewCoverLogo(this)">
                        <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 4px;">Format PNG/JPG transparan disarankan agar rapi di tengah sampul.</div>
                    </div>
                </div>

                <!-- Identitas Mahasiswa -->
                <label class="form-label">Nama Lengkap Mahasiswa</label>
                <input type="text" name="nama" class="form-input" value="{{ $meta['nama'] ?? auth()->user()->name }}" placeholder="Cth: Budi Santoso">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label class="form-label">NIM / NPM</label>
                        <input type="text" name="npm" class="form-input" value="{{ $meta['nim'] ?? '' }}" placeholder="Cth: 2021041082">
                    </div>
                    <div>
                        <label class="form-label">Fakultas</label>
                        <input type="text" name="fakultas" class="form-input" value="{{ $meta['fakultas'] ?? '' }}" placeholder="Cth: Fakultas Ekonomi dan Bisnis">
                    </div>
                </div>

                <label class="form-label">Nama Kampus / Universitas</label>
                <input type="text" name="kampus" class="form-input" value="{{ $meta['kampus'] ?? '' }}" placeholder="Cth: Universitas Indonesia">

                <!-- Pengaturan Margin -->
                <label class="form-label">Pengaturan Margin Kertas A4 (cm)</label>
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 18px;">
                    <div>
                        <span style="font-size: 11px; color: var(--text-muted);">Atas (Top)</span>
                        <input type="number" step="0.5" name="margin_top" value="4" class="form-input" style="margin-bottom: 0;">
                    </div>
                    <div>
                        <span style="font-size: 11px; color: var(--text-muted);">Kiri (Left)</span>
                        <input type="number" step="0.5" name="margin_left" value="4" class="form-input" style="margin-bottom: 0;">
                    </div>
                    <div>
                        <span style="font-size: 11px; color: var(--text-muted);">Bawah (Bottom)</span>
                        <input type="number" step="0.5" name="margin_bottom" value="3" class="form-input" style="margin-bottom: 0;">
                    </div>
                    <div>
                        <span style="font-size: 11px; color: var(--text-muted);">Kanan (Right)</span>
                        <input type="number" step="0.5" name="margin_right" value="3" class="form-input" style="margin-bottom: 0;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <button type="submit" formaction="{{ route('app.downloadDocx', ['id' => $project->id]) }}" class="btn-blue" style="justify-content: center; padding: 11px; background: #0f172a;">
                        📄 Unduh Word (.docx)
                    </button>
                    <button type="submit" formaction="{{ route('project.download.pdf', ['id' => $project->id]) }}" class="btn-blue" style="justify-content: center; padding: 11px;">
                        📕 Unduh PDF (.pdf)
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const projectId = {{ $project->id }};

        const chapterMeta = {
            bab1: { num: 'I', title: 'PENDAHULUAN', stepText: 'Menulis Latar Belakang - Rumusan Masalah - Tujuan Penelitian...' },
            bab2: { num: 'II', title: 'TINJAUAN PUSTAKA', stepText: 'Menyusun Landasan Teori - Penelitian Terdahulu - Hipotesis...' },
            bab3: { num: 'III', title: 'METODOLOGI PENELITIAN', stepText: 'Menyusun Desain Penelitian - Populasi/Sampel - Teknik Analisis Data...' },
            bab4: { num: 'IV', title: 'HASIL DAN PEMBAHASAN', stepText: 'Menyusun Hasil Analisis Data - Pengujian - Pembahasan Temuan...' },
            bab5: { num: 'V', title: 'PENUTUP', stepText: 'Menyusun Kesimpulan - Saran - Ekstraksi Daftar Pustaka APA...' },
            daftar_pustaka: { num: '', title: 'DAFTAR PUSTAKA', stepText: 'Memperbarui Sitasi & Daftar Pustaka APA 7th Edition...' }
        };

        const chapterContents = {
            bab1: @json($project->bab1_content ?? ''),
            bab2: @json($project->bab2_content ?? ''),
            bab3: @json($project->bab3_content ?? ''),
            bab4: @json($project->bab4_content ?? ''),
            bab5: @json($project->bab5_content ?? ''),
            daftar_pustaka: @json($project->daftar_pustaka ?? '')
        };

        let activeChapter = 'bab1';

        function formatAcademicPaperHtml(chapterKey, rawText) {
            const info = chapterMeta[chapterKey];
            const headingHtml = info.num
                ? `<h2 class="bab-heading">BAB ${info.num}<br>${info.title}</h2>`
                : `<h2 class="bab-heading">${info.title}</h2>`;

            if (!rawText || !rawText.trim()) {
                return `
                    ${headingHtml}
                    <div contenteditable="false" style="text-align: center; padding: 64px 20px; font-family: 'Inter', sans-serif; color: #64748b;">
                        <p style="text-indent: 0; text-align: center; font-size: 14px; margin-bottom: 16px;">
                            Naskah <strong>${info.title}</strong> belum ditulis. Klik tombol di bawah untuk menyusun bab ini sesuai judul dan pedoman kampusmu.
                        </p>
                        <button type="button" class="btn-blue" style="padding: 10px 20px; font-size: 13.5px;" onclick="generateActiveChapter()">
                            ✨ Tulis ${info.num ? 'Bab ' + info.num : info.title} Sekarang
                        </button>
                    </div>
                `;
            }

            // Format baris menjadi subbab dan paragraf dengan highlight sitasi biru ala Mantra Riset
            const lines = rawText.replace(/<\/p>/gi, '\n').replace(/<br\s*\/?>/gi, '\n').split('\n');
            let bodyHtml = '';

            lines.forEach(line => {
                const clean = line.replace(/<[^>]+>/g, '').trim();
                if (!clean) return;

                if (/^\d+\.\d+(\.\d+)?\s+/.test(clean)) {
                    bodyHtml += `<h3 class="subbab-heading">${clean}</h3>`;
                } else {
                    // Highlight in-text citations: e.g. (Wijaya et al., 2023) atau Kusuma (2024)
                    const highlighted = clean
                        .replace(/(\([A-Z][A-Za-z\s\&\.,]+,\s*\d{4}[a-z]?\))/g, '<span class="cite-link">$1</span>')
                        .replace(/([A-Z][a-zA-Z]+(?:\s+(?:&|dan|et\s+al\.)\s+[A-Z][a-zA-Z]+)?\s*\(\d{4}[a-z]?\))/g, '<span class="cite-link">$1</span>');

                    if (chapterKey === 'daftar_pustaka') {
                        bodyHtml += `<p style="text-indent: -1.27cm; padding-left: 1.27cm; margin-bottom: 10px;">${highlighted}</p>`;
                    } else {
                        bodyHtml += `<p>${highlighted}</p>`;
                    }
                }
            });

            return headingHtml + bodyHtml;
        }

        function selectChapterView(chKey) {
            activeChapter = chKey;
            document.querySelectorAll('.chapter-nav .chapter-item').forEach(b => b.classList.remove('active'));
            const navBtn = document.getElementById('nav-item-' + chKey);
            if (navBtn) navBtn.classList.add('active');

            const info = chapterMeta[chKey];
            document.getElementById('active-chapter-title').innerText = info.num ? `BAB ${info.num} · ${info.title}` : info.title;
            document.getElementById('revise-modal-bab-label').innerText = info.num ? `BAB ${info.num}` : info.title;
            if (chKey !== 'daftar_pustaka') {
                document.getElementById('revise-chapter-select').value = chKey;
            }

            document.getElementById('btn-save-manual').style.display = 'none';
            document.getElementById('word-paper-canvas').innerHTML = formatAcademicPaperHtml(chKey, chapterContents[chKey]);
        }

        function updateProgressCounts() {
            let count = 0;
            ['bab1', 'bab2', 'bab3', 'bab4', 'bab5'].forEach(k => {
                const has = !!(chapterContents[k] && chapterContents[k].trim());
                if (has) count++;
                const dot = document.getElementById('dot-' + k);
                if (dot) dot.classList.toggle('done', has);
            });
            const dpDot = document.getElementById('dot-daftar_pustaka');
            if (dpDot) dpDot.classList.toggle('done', !!(chapterContents.daftar_pustaka && chapterContents.daftar_pustaka.trim()));

            const pct = Math.round((count / 5) * 100);
            document.getElementById('sidebar-done-label').innerText = `${count} dari 5 Bab`;
            document.getElementById('sidebar-pct-label').innerText = `${pct}% selesai`;
            document.getElementById('sidebar-progress-fill').style.width = `${pct}%`;
        }

        let progressTimer = null;
        function showFloatingProgress(text) {
            const box = document.getElementById('floating-progress');
            const lbl = document.getElementById('fp-label');
            const pctEl = document.getElementById('fp-pct');
            const bar = document.getElementById('fp-bar');

            lbl.innerText = text;
            let p = 12;
            pctEl.innerText = p + '%';
            bar.style.width = p + '%';
            box.style.display = 'block';

            clearInterval(progressTimer);
            progressTimer = setInterval(() => {
                if (p < 92) {
                    p += Math.floor(Math.random() * 7) + 2;
                    if (p > 92) p = 92;
                    pctEl.innerText = p + '%';
                    bar.style.width = p + '%';
                }
            }, 900);
        }

        function hideFloatingProgress() {
            clearInterval(progressTimer);
            document.getElementById('fp-pct').innerText = '100%';
            document.getElementById('fp-bar').style.width = '100%';
            setTimeout(() => {
                document.getElementById('floating-progress').style.display = 'none';
            }, 500);
        }

        async function generateActiveChapter() {
            if (activeChapter === 'daftar_pustaka') {
                await refreshReferences();
                return;
            }
            await runGenerateChapter(activeChapter);
        }

        async function runGenerateChapter(chKey) {
            const info = chapterMeta[chKey];
            const btn = document.getElementById('btn-write-chapter');
            btn.disabled = true;
            showFloatingProgress(info.stepText);

            try {
                const res = await fetch(`/project/${projectId}/generate`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({
                        chapter: chKey,
                        model: document.getElementById('studio-model').value
                    })
                });
                const data = await res.json();
                if (res.ok && data.status === 'success') {
                    chapterContents[chKey] = data.content || '';
                    if (data.daftar_pustaka) {
                        chapterContents.daftar_pustaka = data.daftar_pustaka;
                    }
                    updateProgressCounts();
                    selectChapterView(chKey);
                } else {
                    alert(data.error || 'Gagal menyusun bab.');
                }
            } catch (e) {
                alert('Terjadi kesalahan jaringan saat menyusun bab.');
            } finally {
                btn.disabled = false;
                hideFloatingProgress();
            }
        }

        async function generateSequentialProposal() {
            for (const ch of ['bab1', 'bab2', 'bab3']) {
                selectChapterView(ch);
                await runGenerateChapter(ch);
            }
        }

        async function refreshReferences() {
            showFloatingProgress(chapterMeta.daftar_pustaka.stepText);
            try {
                const res = await fetch(`/project/${projectId}/refine-references`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ model: document.getElementById('studio-model').value })
                });
                const data = await res.json();
                if (data.status === 'success') {
                    chapterContents.daftar_pustaka = data.daftar_pustaka || '';
                    updateProgressCounts();
                    selectChapterView('daftar_pustaka');
                }
            } catch (e) {} finally {
                hideFloatingProgress();
            }
        }

        async function saveManualEdits() {
            const paper = document.getElementById('word-paper-canvas');
            const clone = paper.cloneNode(true);
            const h2 = clone.querySelector('h2.bab-heading');
            if (h2) h2.remove();

            const paragraphs = [];
            clone.querySelectorAll('h3, p').forEach(el => {
                if (el.innerText.trim()) paragraphs.push(el.innerText.trim());
            });
            const newText = paragraphs.length ? paragraphs.join('\n\n') : clone.innerText.trim();

            const btn = document.getElementById('btn-save-manual');
            btn.innerText = 'Menyimpan...';

            try {
                const res = await fetch(`/project/${projectId}/save-chapter`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ chapter: activeChapter, content: newText })
                });
                const data = await res.json();
                if (data.status === 'success') {
                    chapterContents[activeChapter] = data.content;
                    updateProgressCounts();
                    btn.innerText = '✓ Tersimpan';
                    setTimeout(() => {
                        btn.style.display = 'none';
                        btn.innerText = '💾 Simpan Editan Teks';
                    }, 1200);
                }
            } catch (e) {
                alert('Gagal menyimpan perubahan.');
                btn.innerText = '💾 Simpan Editan Teks';
            }
        }

        function openModal(id) {
            document.getElementById(id).classList.add('open');
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('open');
        }

        async function submitGuideline(e) {
            e.preventDefault();
            const btn = document.getElementById('btn-save-guideline');
            btn.disabled = true;
            btn.innerText = 'Menyimpan...';

            const fd = new FormData(document.getElementById('form-guideline'));
            try {
                const res = await fetch(`/project/${projectId}/guideline`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    body: fd
                });
                const data = await res.json();
                if (data.status === 'success') {
                    alert(data.message);
                    closeModal('modal-guideline');
                } else {
                    alert(data.message || 'Gagal menyimpan pedoman.');
                }
            } catch (err) {
                alert('Gagal mengunggah pedoman kampus.');
            } finally {
                btn.disabled = false;
                btn.innerText = 'Simpan Pedoman Kampus';
            }
        }

        async function submitRevision(e) {
            e.preventDefault();
            const ch = document.getElementById('revise-chapter-select').value;
            const instruction = document.getElementById('revise-instruction').value.trim();
            const fileInput = document.getElementById('revise-file');

            if (!instruction && (!fileInput.files || !fileInput.files.length)) {
                alert('Tuliskan catatan revisi atau unggah foto coretan / dokumen revisi dosen.');
                return;
            }

            const btn = document.getElementById('btn-submit-revise');
            btn.disabled = true;
            btn.innerText = 'Memproses Revisi...';
            closeModal('modal-revise');
            showFloatingProgress('Merevisi ' + (chapterMeta[ch]?.title || 'Bab') + ' sesuai arahan dosen...');

            const fd = new FormData();
            fd.append('chapter', ch);
            fd.append('instruction', instruction);
            fd.append('model', document.getElementById('studio-model').value);
            if (fileInput.files && fileInput.files[0]) {
                fd.append('revision_file', fileInput.files[0]);
            }

            try {
                const res = await fetch(`/project/${projectId}/revise`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    body: fd
                });
                const data = await res.json();
                if (res.ok && data.status === 'success') {
                    chapterContents[ch] = data.content;
                    updateProgressCounts();
                    selectChapterView(ch);
                    document.getElementById('revise-instruction').value = '';
                    fileInput.value = '';
                } else {
                    alert(data.error || 'Gagal merevisi bab.');
                }
            } catch (err) {
                alert('Terjadi kesalahan saat merevisi bab.');
            } finally {
                btn.disabled = false;
                btn.innerText = 'Proses Revisi dengan AI';
                hideFloatingProgress();
            }
        }

        function previewCoverLogo(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = e => {
                    const img = document.getElementById('cover-logo-preview');
                    img.src = e.target.result;
                    img.style.display = 'block';
                    document.getElementById('download-logo-base64').value = e.target.result;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Render Bab I saat pertama kali dibuka
        selectChapterView('bab1');
    </script>
</body>
</html>
