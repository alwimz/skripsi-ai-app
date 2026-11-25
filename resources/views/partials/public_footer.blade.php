<style>
    .sl-public-footer {
        background: #ffffff;
        border-top: 1px solid #e2e8f0;
        padding: 44px 0 32px;
        color: #475569;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    .sl-public-footer-wrap {
        width: 100%;
        max-width: 1140px;
        margin: 0 auto;
        padding: 0 24px;
    }

    .sl-public-footer-grid {
        display: grid;
        grid-template-columns: minmax(190px, 1.35fr) repeat(3, minmax(130px, 1fr));
        gap: 32px 28px;
    }

    .sl-public-footer-heading {
        font-size: 15px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 14px;
        line-height: 1.35;
    }

    .sl-public-footer-links {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 11px;
    }

    .sl-public-footer-links a {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 500;
        color: #475569;
        text-decoration: none;
        line-height: 1.45;
        transition: color 0.15s ease;
    }

    .sl-public-footer-links a:hover {
        color: #2563eb;
    }

    .sl-public-footer-social-handle {
        font-size: 12.5px;
        color: #64748b;
        font-weight: 500;
    }

    .sl-public-footer-bottom {
        margin-top: 34px;
        padding-top: 22px;
        border-top: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        font-size: 13px;
        color: #64748b;
    }

    .sl-public-footer-brand {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 15px;
        font-weight: 800;
        color: #0f172a;
        text-decoration: none;
    }

    .sl-public-footer-sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }

    @media (max-width: 720px) {
        .sl-public-footer {
            padding: 34px 0 28px;
        }

        .sl-public-footer-wrap {
            padding: 0 18px;
        }

        .sl-public-footer-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 28px 20px;
        }

        .sl-public-footer-col--features {
            grid-column: 1 / -1;
        }

        .sl-public-footer-col--social {
            grid-column: 1 / -1;
        }

        .sl-public-footer-bottom {
            margin-top: 28px;
            padding-top: 18px;
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
            padding-inline-end: 68px;
        }
    }
</style>

<footer class="sl-public-footer">
    <div class="sl-public-footer-wrap">
        <h2 class="sl-public-footer-sr-only">Navigasi Footer SkripsiLab AI</h2>
        <div class="sl-public-footer-grid">
            <div class="sl-public-footer-col sl-public-footer-col--features">
                <h3 class="sl-public-footer-heading">Fitur &amp; Panduan</h3>
                <ul class="sl-public-footer-links">
                    <li><a href="{{ url('/panduan/cari-judul-skripsi') }}">Cari Judul Skripsi</a></li>
                    <li><a href="{{ url('/panduan/cari-jurnal-skripsi') }}">Cari Jurnal &amp; Referensi DOI</a></li>
                    <li><a href="{{ url('/panduan/buat-proyek-dan-generate-bab') }}">Workspace Proposal &amp; Bab I–V</a></li>
                    <li><a href="{{ url('/panduan/parafrase-skripsi') }}">Parafrase Akademik</a></li>
                    <li><a href="{{ url('/panduan/olah-data-statistik-skripsi') }}">Olah Data Statistik (SPSS)</a></li>
                    <li><a href="{{ url('/panduan/buat-ppt-sidang-skripsi') }}">PPT Sidang Skripsi (.pptx)</a></li>
                    <li><a href="{{ url('/panduan/simulasi-sidang-skripsi') }}">Simulasi Sidang Skripsi</a></li>
                    <li><a href="{{ auth()->check() ? route('tools.hub') : url('/#tools-gratis') }}">10 Tools Mahasiswa Gratis</a></li>
                </ul>
            </div>

            <div class="sl-public-footer-col sl-public-footer-col--legal">
                <h3 class="sl-public-footer-heading">Legal</h3>
                <ul class="sl-public-footer-links">
                    <li><a href="{{ url('/tentang') }}">Tentang SkripsiLab AI</a></li>
                    <li><a href="{{ url('/syarat-ketentuan') }}">Syarat &amp; Ketentuan</a></li>
                    <li><a href="{{ url('/kebijakan-privasi') }}">Kebijakan Privasi</a></li>
                </ul>
            </div>

            <div class="sl-public-footer-col sl-public-footer-col--other">
                <h3 class="sl-public-footer-heading">Lainnya</h3>
                <ul class="sl-public-footer-links">
                    <li><a href="{{ auth()->check() ? route('app.create', ['tab' => 'panduan']) . '#panduan' : url('/panduan') }}">Panduan Lengkap</a></li>
                    <li><a href="{{ url('/blog') }}">Blog</a></li>
                    <li><a href="{{ url('/paket') }}">Harga</a></li>
                    <li><a href="{{ url('/#contoh-hasil') }}">Contoh Skripsi</a></li>
                    <li><a href="{{ auth()->check() ? route('tools.hub') : url('/#tools-gratis') }}">Tools Mahasiswa</a></li>
                    @auth
                        <li><a href="{{ route('app.create') }}">Buka Workspace</a></li>
                    @else
                        <li><a href="{{ route('login') }}">Masuk</a></li>
                        <li><a href="{{ route('register') }}">Daftar Gratis</a></li>
                    @endauth
                </ul>
            </div>

            <div class="sl-public-footer-col sl-public-footer-col--social">
                <h3 class="sl-public-footer-heading">Ikuti Kami</h3>
                <ul class="sl-public-footer-links">
                    <li>
                        <a href="https://www.instagram.com/skripsilabai/" target="_blank" rel="noopener noreferrer">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                                <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                                <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                            </svg>
                            <span>Instagram</span>
                            <span class="sl-public-footer-social-handle">@skripsilabai</span>
                        </a>
                    </li>
                    <li>
                        <a href="https://www.tiktok.com/@skripsilabai" target="_blank" rel="noopener noreferrer">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5"></path>
                            </svg>
                            <span>TikTok</span>
                            <span class="sl-public-footer-social-handle">@skripsilabai</span>
                        </a>
                    </li>
                    <li>
                        <a href="https://www.facebook.com/61597023685219/" target="_blank" rel="noopener noreferrer">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path>
                            </svg>
                            <span>Facebook</span>
                            <span class="sl-public-footer-social-handle">SkripsiLab AI</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="sl-public-footer-bottom">
            <a href="{{ url('/') }}" class="sl-public-footer-brand">
                <img src="/images/logo.png?v=3" alt="SkripsiLab AI" width="30" height="30" style="display:block;object-fit:contain;">
                <span>SkripsiLab AI</span>
            </a>
            <span>&copy; {{ date('Y') }} SkripsiLab AI.</span>
        </div>
    </div>
</footer>

@include('partials.help_widget')
