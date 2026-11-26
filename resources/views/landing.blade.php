<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SkripsiLab AI — Proposal &amp; Skripsi Otomatis Referensi Jurnal yang Relevan</title>
    <meta name="description"
        content="Susun proposal Bab 1–3 &amp; skripsi Bab 1–5 otomatis menggunakan AI dengan referensi ilmiah dan jurnal yang relevan ber-DOI. Ekspor Word/PDF, PPT &amp; simulasi sidang.">
    <meta name="keywords"
        content="skripsilab ai, skripsilab, skripsi lab, skripsi ai, buat skripsi otomatis ai, buat proposal skripsi otomatis, referensi ilmiah jurnal relevan, cari jurnal doi relevan, ai pembuat skripsi, generator skripsi bab 1-5, contoh proposal skripsi, cari judul skripsi, daftar pustaka apa 7th otomatis, ppt sidang skripsi otomatis, simulasi sidang skripsi, parafrase skripsi, dosen ai, format skripsi 4-4-3-3, ekspor word pdf skripsi">
    <meta name="author" content="SkripsiLab AI">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    @if(!empty(env('GOOGLE_SITE_VERIFICATION')))
        <meta name="google-site-verification" content="{{ env('GOOGLE_SITE_VERIFICATION') }}">
    @endif
    @if(!empty(config('services.meta.domain_verification')))
        <meta name="facebook-domain-verification" content="{{ config('services.meta.domain_verification') }}">
    @endif
    <meta name="theme-color" content="#2563eb">
    <link rel="canonical" href="{{ url('/') }}">
    <link rel="icon" type="image/png" sizes="512x512" href="/images/logo.png?v=3">
    <link rel="shortcut icon" href="/favicon.ico?v=3">
    <link rel="apple-touch-icon" href="/images/logo.png?v=3">

    <!-- Open Graph / WhatsApp / LinkedIn / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:locale" content="id_ID">
    <meta property="og:site_name" content="SkripsiLab AI">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:title" content="SkripsiLab AI — Proposal &amp; Skripsi Otomatis Referensi Jurnal yang Relevan">
    <meta property="og:description"
        content="Susun proposal Bab 1–3 &amp; skripsi Bab 1–5 otomatis menggunakan AI dengan referensi ilmiah &amp; jurnal yang relevan ber-DOI, ekspor Word/PDF, PPT sidang, dan simulasi sidang.">
    <meta property="og:image" content="{{ url('/images/logo.png') }}">
    <meta property="og:image:alt"
        content="Logo SkripsiLab AI — Workspace Penulisan Proposal dan Skripsi Otomatis Menggunakan AI">

    <!-- Twitter / X Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="SkripsiLab AI — Proposal &amp; Skripsi Otomatis Referensi Jurnal yang Relevan">
    <meta name="twitter:description"
        content="Susun proposal Bab 1–3 &amp; skripsi Bab 1–5 otomatis menggunakan AI dengan referensi ilmiah &amp; jurnal yang relevan ber-DOI, ekspor Word/PDF, PPT sidang, dan simulasi sidang.">
    <meta name="twitter:image" content="{{ url('/images/logo.png') }}">

    <!-- Structured Data (JSON-LD) untuk Google Rich Results -->
    @php
        $seoSchema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'WebSite',
                    '@id' => url('/') . '#website',
                    'url' => url('/'),
                    'name' => 'SkripsiLab AI',
                    'alternateName' => ['SkripsiLab', 'Skripsi Lab'],
                    'description' => 'SkripsiLab AI adalah workspace penulisan akademik otomatis menggunakan AI untuk membantu mahasiswa Indonesia menyusun proposal skripsi Bab 1–3 dan skripsi Bab 1–5 dengan referensi ilmiah serta jurnal yang relevan terverifikasi DOI, PPT sidang, dan simulasi sidang.',
                    'inLanguage' => 'id-ID',
                ],
                [
                    '@type' => 'SoftwareApplication',
                    '@id' => url('/') . '#app',
                    'name' => 'SkripsiLab AI',
                    'alternateName' => 'SkripsiLab',
                    'applicationCategory' => 'EducationalApplication',
                    'operatingSystem' => 'Web Browser',
                    'url' => url('/'),
                    'inLanguage' => 'id-ID',
                    'description' => 'SkripsiLab AI adalah workspace AI untuk menyusun proposal skripsi Bab 1–3 dan skripsi penuh Bab 1–5 secara otomatis menggunakan AI dengan referensi ilmiah dan jurnal yang relevan terverifikasi DOI, cari judul, parafrase akademik, Dosen AI, PPT sidang 13 tema, simulasi sidang, lampiran otomatis, serta ekspor Word (.docx) dan PDF (.pdf).',
                    'offers' => [
                        '@type' => 'Offer',
                        'price' => '0',
                        'priceCurrency' => 'IDR',
                    ],
                    'featureList' => [
                        'Penyusunan Proposal (Bab I–III) dan Skripsi Lengkap (Bab I–V) Otomatis Menggunakan AI',
                        'Referensi Ilmiah & Jurnal Relevan Nasional/Internasional Terverifikasi DOI (Crossref & OpenAlex)',
                        'Format Kertas A4 Margin 4-4-3-3, Daftar Isi, Daftar Tabel, Daftar Gambar & Daftar Lampiran Otomatis',
                        'Daftar Pustaka APA 7th, Harvard, IEEE, Chicago & Vancouver Otomatis dengan Tautan DOI Aktif',
                        'Ekspor Dokumen Word (.docx) dan PDF (.pdf) Siap Cetak',
                        'Cari Judul Penelitian untuk 9 Metode (Kuantitatif, Kualitatif, R&D, PTK, Hukum Normatif, Mixed Method, dll.)',
                        'Generator PPT Sidang 13 Tema Akademik & Ekspor PowerPoint (.pptx)',
                        'Simulasi Sidang Skripsi Otomatis dari Naskah Bab I–V',
                        'Parafrase Akademik & Konsultasi Dosen AI 24 Jam',
                    ],
                ],
                [
                    '@type' => 'FAQPage',
                    '@id' => url('/') . '#faq',
                    'mainEntity' => [
                        [
                            '@type' => 'Question',
                            'name' => 'Apa itu SkripsiLab AI?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'SkripsiLab AI adalah workspace penulisan akademik berbasis AI untuk membantu mahasiswa Indonesia menyusun proposal skripsi (Bab I–III) dan skripsi penuh (Bab I–V) secara terstruktur, lengkap dengan pencarian jurnal ber-DOI, parafrase akademik, olah data, PPT sidang, dan simulasi sidang.',
                            ],
                        ],
                        [
                            '@type' => 'Question',
                            'name' => 'Apakah boleh pakai AI untuk skripsi?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'Boleh, selama AI digunakan sebagai asisten riset, penyusunan kerangka, perapian bahasa ilmiah, dan eksplorasi referensi—bukan untuk menyalin mentah tanpa memahami isi. Mahasiswa tetap memegang kendali penuh untuk memeriksa, menyunting, dan mempertanggungjawabkan naskah kepada dosen pembimbing.',
                            ],
                        ],
                        [
                            '@type' => 'Question',
                            'name' => 'Apakah SkripsiLab AI termasuk joki skripsi?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'Bukan. SkripsiLab AI adalah alat bantu dan workspace produktivitas mandiri, bukan layanan joki. Kamu sendiri yang menentukan topik, metodologi, data penelitian, sitasi, serta melakukan revisi sesuai arahan dosen pembimbing.',
                            ],
                        ],
                        [
                            '@type' => 'Question',
                            'name' => 'Apakah kampus bisa mendeteksi penggunaan AI?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'Pemeriksaan di kampus umumnya menggunakan detektor kemiripan teks seperti Turnitin dan evaluasi pemahaman mahasiswa saat bimbingan atau sidang. SkripsiLab AI menyediakan fitur Parafrase Akademik sesuai EYD V, sitasi jurnal asli ber-DOI, serta integrasi data riset milikmu sendiri agar tulisan lebih natural.',
                            ],
                        ],
                        [
                            '@type' => 'Question',
                            'name' => 'Bagaimana cara pakai SkripsiLab AI yang aman dan tetap karya sendiri?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'Gunakan secara bertahap: tentukan judul dan rumusan masalah sendiri, unggah pedoman kampus, pilih jurnal terverifikasi DOI sebagai rujukan, masukkan data penelitian riil melalui Lab Revisi & Olah Data, lalu baca ulang dan sesuaikan tiap sub-bab sebelum dikonsultasikan ke pembimbing.',
                            ],
                        ],
                        [
                            '@type' => 'Question',
                            'name' => 'Fitur apa saja yang ada di SkripsiLab AI?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'Tersedia 9 modul utama dalam satu dashboard: Workspace Proposal & Skripsi (Bab I–V), Cari Judul (9 metode), Cari Jurnal & Referensi DOI (Crossref & OpenAlex), Parafrase Akademik, Dosen AI, Lab Revisi & Olah Data, Lampiran Otomatis, Generate PPT Sidang (13 tema .pptx), dan Simulasi Sidang, ditambah 10 Tools Mahasiswa gratis.',
                            ],
                        ],
                        [
                            '@type' => 'Question',
                            'name' => 'Untuk siapa SkripsiLab AI?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'SkripsiLab AI dirancang untuk mahasiswa D3, S1, maupun S2 dari seluruh program studi (Informatika, Manajemen, Akuntansi, Ilmu Hukum, Pendidikan, Kedokteran, Keperawatan, Teknik, Psikologi, Komunikasi, dan lainnya) dengan dukungan 9 metode penelitian.',
                            ],
                        ],
                        [
                            '@type' => 'Question',
                            'name' => 'Apakah SkripsiLab AI gratis?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'Ya, tersedia uji coba gratis 1 proyek (Sampul, Bab I & Daftar Pustaka + AI Tools 1x) serta akses gratis ke 10 Tools Mahasiswa di workspace. Untuk naskah lengkap Bab I–V dan ekspor Word/PDF penuh, tersedia paket mulai Rp15.000 tanpa sistem kredit.',
                            ],
                        ],
                        [
                            '@type' => 'Question',
                            'name' => 'Bagaimana cara kerja auto format skripsi (Word & PDF)?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'Saat mengekspor ke Word (.docx) atau PDF (.pdf), sistem otomatis menata ukuran kertas A4, margin 4-4-3-3 cm, halaman sampul dengan logo kampus, lembar pengesahan, abstrak, kata pengantar, daftar isi, daftar tabel, daftar gambar, daftar lampiran, penomoran halaman Romawi & Arab di tengah bawah, serta daftar pustaka otomatis.',
                            ],
                        ],
                        [
                            '@type' => 'Question',
                            'name' => 'Apakah data dan naskah saya aman?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'Aman. Seluruh data akun, proyek, dan draf naskah bersifat privat untuk akunmu sendiri, dilindungi enkripsi HTTPS/TLS serta autentikasi aman, dan tidak pernah dibagikan atau dijual ke pihak ketiga.',
                            ],
                        ],
                        [
                            '@type' => 'Question',
                            'name' => 'Apakah SkripsiLab AI bisa dipakai di HP?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'Bisa. Seluruh antarmuka SkripsiLab AI—mulai dari membuat proyek, menyusun bab, mempratinjau dokumen, menggunakan 10 Tools Mahasiswa, hingga mengunduh file Word, PDF, dan PPT—responsif dan dapat diakses langsung melalui browser HP maupun laptop.',
                            ],
                        ],
                        [
                            '@type' => 'Question',
                            'name' => 'Bagaimana cara menghubungi support?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'Kamu bisa langsung menghubungi tim support melalui menu Bantuan di dalam dashboard workspace, lewat kontak WhatsApp Support di halaman Tentang & Pengembang, atau melalui akun Instagram dan TikTok @skripsilabai.',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    @endphp
    <script
        type="application/ld+json">{!! json_encode($seoSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --bg-main: #ffffff;
            --bg-subtle: #f8fafc;
            --bg-muted: #f1f5f9;
            --text-primary: #0f172a;
            --text-secondary: #334155;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --border-hover: #cbd5e1;
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --primary-soft: #eff6ff;
            --primary-border: #bfdbfe;
            --shadow-card: 0 1px 3px rgba(15, 23, 42, 0.04), 0 8px 24px -14px rgba(15, 23, 42, 0.08);
            --shadow-hover: 0 14px 30px -14px rgba(37, 99, 235, 0.18), 0 2px 6px rgba(15, 23, 42, 0.04);
            --ease-out: cubic-bezier(0.22, 1, 0.36, 1);
        }

        html {
            accent-color: var(--primary);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg-main);
            color: var(--text-primary);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        :focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }

        @media (prefers-reduced-motion: reduce) {

            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .container {
            width: 100%;
            max-width: 1140px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* Navbar */
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
            gap: 12px;
        }

        .brand {
            font-size: 17px;
            font-weight: 800;
            color: var(--text-primary);
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }

        .brand-mark {
            width: 32px;
            height: 32px;
            border-radius: 9px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 4px;
            list-style: none;
        }

        .nav-item {
            position: relative;
        }

        .nav-link,
        .nav-trigger {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 13px;
            font-size: 14px;
            font-weight: 500;
            color: var(--text-secondary);
            border-radius: 8px;
            border: none;
            background: transparent;
            cursor: pointer;
            font-family: inherit;
            transition: background 0.15s ease, color 0.15s ease;
            white-space: nowrap;
        }

        .nav-link:hover,
        .nav-trigger:hover,
        .nav-item:hover .nav-trigger {
            background: var(--primary-soft);
            color: var(--primary-hover);
        }

        .dropdown-panel {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            width: 560px;
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 16px;
            box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.12);
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
            background: var(--primary-soft);
        }

        .dropdown-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: var(--primary-soft);
            color: var(--primary);
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
            gap: 8px;
            flex-shrink: 0;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            min-height: 42px;
            font-size: 13.5px;
            font-weight: 600;
            padding: 9px 18px;
            border-radius: 10px;
            cursor: pointer;
            transition: transform 0.16s var(--ease-out), box-shadow 0.16s var(--ease-out), background 0.16s ease, border-color 0.16s ease, color 0.16s ease;
            font-family: inherit;
            white-space: nowrap;
            border: 1px solid transparent;
        }

        .btn:active {
            transform: scale(0.98);
        }

        .btn-ghost {
            color: var(--text-secondary);
            background: transparent;
        }

        .btn-ghost:hover {
            color: var(--primary);
            background: var(--primary-soft);
        }

        .btn-primary {
            background: linear-gradient(180deg, #2563eb 0%, #1d4ed8 100%);
            color: #ffffff;
            border-color: var(--primary);
            box-shadow: 0 6px 16px -6px rgba(37, 99, 235, 0.45);
        }

        .btn-primary:hover {
            background: linear-gradient(180deg, #1d4ed8 0%, #1e40af 100%);
            border-color: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 10px 22px -6px rgba(37, 99, 235, 0.52);
        }

        .btn-outline {
            background: #ffffff;
            color: var(--text-primary);
            border-color: #cbd5e1;
        }

        .btn-outline:hover {
            background: var(--primary-soft);
            border-color: #93c5fd;
            color: var(--primary-hover);
            transform: translateY(-1px);
        }

        /* Mobile Nav Toggle & Drawer */
        .nav-mobile-toggle {
            display: none;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            border: 1px solid var(--border-color);
            background: #ffffff;
            color: var(--text-primary);
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.15s ease, border-color 0.15s ease;
        }

        .nav-mobile-toggle:hover {
            background: var(--primary-soft);
            border-color: var(--primary-border);
            color: var(--primary);
        }

        .mobile-nav-drawer {
            display: none;
            position: sticky;
            top: 66px;
            z-index: 95;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-color);
            box-shadow: 0 14px 30px -15px rgba(15, 23, 42, 0.15);
            padding: 12px 16px 16px;
        }

        .mobile-nav-drawer.open {
            display: block;
        }

        .mobile-nav-list {
            display: flex;
            flex-direction: column;
            gap: 4px;
            max-width: 1140px;
            margin: 0 auto;
        }

        .mobile-nav-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 11px 14px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-secondary);
            transition: background 0.15s ease, color 0.15s ease;
        }

        .mobile-nav-link:hover {
            background: var(--primary-soft);
            color: var(--primary-hover);
        }

        .mobile-nav-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-top: 10px;
            padding-top: 12px;
            border-top: 1px solid var(--border-color);
        }

        .mobile-nav-actions.single {
            grid-template-columns: 1fr;
        }

        .mobile-nav-actions .btn {
            width: 100%;
            min-height: 42px;
            font-size: 13.5px;
        }

        /* Hero Section */
        .hero {
            padding: clamp(3.2rem, 2.4rem + 3vw, 4.8rem) 0 clamp(2.8rem, 2rem + 2.5vw, 4rem);
            text-align: center;
            border-bottom: 1px solid var(--border-color);
            background: radial-gradient(ellipse 80% 70% at 50% 0%, #eff6ff 0%, #ffffff 72%);
        }

        .hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 12.5px;
            font-weight: 700;
            color: var(--primary);
            background: #ffffff;
            border: 1px solid var(--primary-border);
            padding: 5px 14px;
            border-radius: 999px;
            margin-bottom: 16px;
            box-shadow: 0 2px 10px -4px rgba(37, 99, 235, 0.22);
        }

        .hero h1 {
            font-size: clamp(1.9rem, 1.35rem + 2.5vw, 3.05rem);
            font-weight: 800;
            letter-spacing: -0.03em;
            line-height: 1.16;
            color: var(--text-primary);
            max-width: 760px;
            margin: 0 auto 16px;
            text-wrap: balance;
        }

        .hero p {
            font-size: clamp(0.95rem, 0.9rem + 0.25vw, 1.05rem);
            color: var(--text-secondary);
            max-width: 640px;
            margin: 0 auto 28px;
            line-height: 1.65;
            text-wrap: pretty;
        }

        .hero-cta {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .hero-trust-strip {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 26px;
        }

        .hero-trust-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12.5px;
            font-weight: 600;
            color: var(--text-secondary);
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid var(--border-color);
            border-radius: 999px;
            padding: 6px 13px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
        }

        .hero-trust-pill svg {
            color: #059669;
            flex-shrink: 0;
        }

        /* Section Generic */
        .section {
            padding: clamp(3.2rem, 2.5rem + 2vw, 4.5rem) 0;
            border-bottom: 1px solid var(--border-color);
        }

        .section-subtle {
            background: var(--bg-subtle);
        }

        .section-header {
            max-width: 680px;
            margin: 0 auto 36px;
            text-align: center;
        }

        .section-label {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--primary);
            margin-bottom: 8px;
            display: block;
        }

        .section-title {
            font-size: clamp(1.38rem, 1.1rem + 1.4vw, 2.05rem);
            font-weight: 800;
            letter-spacing: -0.025em;
            line-height: 1.24;
            color: var(--text-primary);
            margin-bottom: 10px;
            text-wrap: balance;
        }

        .section-desc {
            font-size: 14.5px;
            color: var(--text-secondary);
            line-height: 1.65;
            text-wrap: pretty;
        }

        /* Workspace Preview */
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
            transition: all 0.15s;
        }

        .major-pill:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .major-pill.active {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
        }

        .studio-window {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 24px 54px -18px rgba(15, 23, 42, 0.16);
            pointer-events: none;
            user-select: none;
        }

        .sf-window-bar {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 10px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .sf-mac-left {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .sf-mac-dots {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            flex-shrink: 0;
        }

        .sf-mac-dot {
            width: 11px;
            height: 11px;
            border-radius: 999px;
            display: inline-block;
        }

        .sf-mac-dot.red { background: #ff5f56; }
        .sf-mac-dot.yellow { background: #ffbd2e; }
        .sf-mac-dot.green { background: #27c93f; }

        .sf-win-brand {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 13px;
            font-weight: 800;
            color: #0f172a;
            white-space: nowrap;
        }

        .sf-win-url {
            flex: 1;
            max-width: 420px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            padding: 5px 12px;
            font-size: 11.5px;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sf-win-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11.5px;
            font-weight: 700;
            color: #047857;
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            padding: 4px 10px;
            border-radius: 999px;
            white-space: nowrap;
        }

        .studio-frame {
            background: #eef2f7;
            display: grid;
            grid-template-columns: 265px 1fr;
            min-height: 580px;
        }

        .studio-frame-sidebar {
            background: #ffffff;
            border-right: 1px solid #e2e8f0;
            padding: 14px 12px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .sf-back-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11.5px;
            font-weight: 600;
            color: #64748b;
        }

        .sf-mode-badge {
            display: inline-block;
            width: fit-content;
            font-size: 10.5px;
            font-weight: 700;
            color: #1d4ed8;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            padding: 2px 8px;
            border-radius: 999px;
        }

        .sf-progress-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 11px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 5px;
        }

        .sf-sub {
            font-size: 10px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            margin-top: 4px;
            letter-spacing: 0.04em;
        }

        .sf-nav-list {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .sf-nav-btn {
            width: 100%;
            text-align: left;
            padding: 7px 9px;
            border-radius: 8px;
            border: 1px solid transparent;
            background: transparent;
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            cursor: pointer;
            font-family: inherit;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 6px;
            transition: background 0.14s ease, color 0.14s ease;
        }

        .sf-nav-btn:hover {
            background: #f1f5f9;
            color: #0f172a;
        }

        .sf-nav-btn.active {
            background: var(--primary-soft);
            color: var(--primary-hover);
            border-color: var(--primary-border);
        }

        .sf-side-format {
            margin-top: auto;
            padding: 9px 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            font-size: 11px;
            color: #475569;
            line-height: 1.45;
        }

        .sf-side-format-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 4px;
        }

        .sf-margin-tag {
            font-size: 10px;
            font-weight: 700;
            color: #1d4ed8;
            background: #dbeafe;
            padding: 1px 6px;
            border-radius: 4px;
        }

        .sf-side-dl {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
        }

        .sf-dl-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 8px;
            border-radius: 7px;
            font-size: 11px;
            font-weight: 700;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #0f172a;
            text-align: center;
        }

        .sf-dl-pill.primary {
            background: var(--primary);
            border-color: var(--primary);
            color: #ffffff;
        }

        .studio-frame-main {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .sf-topbar {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 9px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
        }

        .sf-chapter-bar {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 8px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
        }

        .sf-chapter-bar-title {
            font-size: 11.5px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        .sf-chapter-bar-tools {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        .sf-tool-chip {
            font-size: 11px;
            font-weight: 600;
            color: #334155;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            padding: 4px 8px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        @media (max-width: 720px) {
            .sf-win-url,
            .sf-win-status {
                display: none;
            }
        }

        .sf-topbar-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            min-width: 0;
        }

        .sf-live-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: #10b981;
            flex-shrink: 0;
        }

        .sf-paper-area {
            padding: 22px;
            flex: 1;
            overflow-y: auto;
            max-height: 540px;
        }

        .sf-a4-paper {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            max-width: 720px;
            margin: 0 auto;
            padding: 36px 46px 46px;
            font-family: 'Times New Roman', Times, serif;
            font-size: 11.5pt;
            line-height: 1.65;
            color: #0f172a;
            text-align: justify;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
            position: relative;
        }

        .sf-chapter-head {
            text-align: center;
            font-weight: 700;
            margin-bottom: 18px;
            line-height: 1.35;
        }

        .sf-chapter-num {
            font-size: 13pt;
            letter-spacing: 0.03em;
        }

        .sf-chapter-name {
            font-size: 13pt;
            text-transform: uppercase;
        }

        .sf-a4-paper h4 {
            font-size: 11.5pt;
            font-weight: bold;
            margin: 14px 0 6px;
            text-indent: 0;
            text-align: left;
        }

        .sf-a4-paper p {
            text-indent: 1.1cm;
            margin-bottom: 9px;
        }

        .sf-a4-paper p.sf-list-line {
            text-indent: 0;
            padding-left: 16px;
            margin-bottom: 6px;
        }

        .sf-a4-paper p.sf-dp-entry {
            text-indent: -1.1cm;
            padding-left: 1.1cm;
            margin-bottom: 8px;
            line-height: 1.4;
            word-break: break-word;
        }

        .sf-caption {
            font-size: 10.5pt;
            font-weight: 700;
            margin: 12px 0 6px;
            text-align: left;
            text-indent: 0;
        }

        .sf-table-wrap {
            overflow-x: auto;
            margin: 8px 0 14px;
        }

        .sf-preview-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5pt;
            line-height: 1.35;
            text-align: left;
        }

        .sf-preview-table th,
        .sf-preview-table td {
            border: 1px solid #334155;
            padding: 5px 7px;
            vertical-align: top;
        }

        .sf-preview-table th {
            background: #f1f5f9;
            font-weight: 700;
            text-align: center;
        }

        .sf-cover-box {
            text-align: center;
            padding: 16px 8px 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 14px;
        }

        .sf-cover-kind {
            font-size: 13pt;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .sf-cover-title {
            font-size: 12.5pt;
            font-weight: 700;
            text-transform: uppercase;
            line-height: 1.45;
            max-width: 520px;
        }

        .sf-cover-sub {
            font-size: 10.5pt;
            line-height: 1.45;
            max-width: 460px;
            color: #334155;
        }

        .sf-cover-seal {
            width: 78px;
            height: 78px;
            border-radius: 50%;
            border: 2px solid #1e3a8a;
            background: #eff6ff;
            color: #1e3a8a;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 0.06em;
            margin: 6px 0;
        }

        .sf-cover-author {
            font-size: 11pt;
            line-height: 1.5;
        }

        .sf-cover-campus {
            font-size: 11.5pt;
            font-weight: 700;
            text-transform: uppercase;
            line-height: 1.45;
            margin-top: 10px;
        }

        .sf-more-note {
            margin-top: 16px;
            padding: 10px 14px;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            color: #475569;
            text-align: center;
            text-indent: 0;
            line-height: 1.5;
        }

        .sf-page-footer {
            margin-top: 22px;
            text-align: center;
            font-size: 10.5pt;
            color: #475569;
        }

        .cite-blue {
            color: #2563eb;
            text-decoration: underline;
            text-underline-offset: 2px;
        }

        /* Sample Showcase Cards Gallery */
        .sample-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }

        .sample-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 16px;
            transition: transform 0.16s ease, box-shadow 0.16s ease, border-color 0.16s ease;
        }

        .sample-card:hover {
            transform: translateY(-2px);
            border-color: var(--primary-border);
            box-shadow: 0 12px 28px rgba(37, 99, 235, 0.08);
        }

        .sample-badges {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 9px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
        }

        .badge-blue {
            background: var(--primary-soft);
            color: var(--primary-hover);
            border: 1px solid var(--primary-border);
        }

        .badge-slate {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        .sample-card h3 {
            font-size: 14.5px;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1.45;
            margin-bottom: 8px;
        }

        .sample-meta {
            font-size: 12.5px;
            color: var(--text-muted);
            line-height: 1.5;
        }

        .sample-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            padding-top: 12px;
            border-top: 1px solid #f1f5f9;
        }

        .sample-btn {
            padding: 7px 11px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid var(--border-color);
            background: #ffffff;
            color: var(--text-primary);
            cursor: pointer;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
        }

        .sample-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: var(--primary-soft);
        }

        .sample-btn.primary {
            background: var(--primary);
            border-color: var(--primary);
            color: #ffffff;
        }

        .sample-btn.primary:hover {
            background: var(--primary-hover);
            color: #ffffff;
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
            border-radius: 16px;
            padding: 24px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: var(--shadow-card);
            transition: border-color 0.18s var(--ease-out), box-shadow 0.18s var(--ease-out), transform 0.18s var(--ease-out);
        }

        .feature-card:hover {
            border-color: #93c5fd;
            box-shadow: var(--shadow-hover);
            transform: translateY(-3px);
        }

        .feature-icon {
            width: 42px;
            height: 42px;
            border-radius: 11px;
            background: var(--primary-soft);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
            transition: background 0.18s var(--ease-out), color 0.18s var(--ease-out);
        }

        .feature-card:hover .feature-icon {
            background: var(--primary);
            color: #ffffff;
        }

        .feature-card h3 {
            font-size: 15.5px;
            font-weight: 700;
            margin-bottom: 7px;
            text-wrap: balance;
        }

        .feature-card p {
            font-size: 13.5px;
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 16px;
            text-wrap: pretty;
        }

        .feature-link {
            font-size: 13px;
            font-weight: 700;
            color: var(--primary);
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: gap 0.15s ease, color 0.15s ease;
        }

        .feature-card:hover .feature-link {
            gap: 7px;
            color: var(--primary-hover);
        }

        /* FAQ Accordion (Clean Minimalist Divider Style) */
        .faq-list {
            max-width: 820px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
        }

        .faq-item {
            border-bottom: 1px solid var(--border-color);
        }

        .faq-item:first-child {
            border-top: 1px solid var(--border-color);
        }

        .faq-item summary {
            list-style: none;
            padding: 22px 4px;
            font-size: clamp(1rem, 0.96rem + 0.25vw, 1.125rem);
            font-weight: 700;
            color: var(--text-primary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            user-select: none;
            line-height: 1.45;
            transition: color 0.15s ease;
        }

        .faq-item summary:hover {
            color: var(--primary);
        }

        .faq-item summary::-webkit-details-marker {
            display: none;
        }

        .faq-item summary::after {
            content: '+';
            font-size: 23px;
            font-weight: 400;
            color: #334155;
            line-height: 1;
            flex-shrink: 0;
            transition: transform 0.2s var(--ease-out), color 0.16s ease;
        }

        .faq-item[open] summary::after {
            content: '−';
            color: var(--primary);
        }

        .faq-item::details-content {
            interpolate-size: allow-keywords;
            overflow: clip;
            contain: layout;
        }

        .faq-item:not([open])::details-content {
            block-size: 0;
        }

        @media (prefers-reduced-motion: no-preference) {
            .faq-item::details-content {
                transition: block-size 0.28s ease-in-out, content-visibility 0.28s ease-in-out allow-discrete;
            }
        }

        .faq-answer {
            padding: 0 4px 22px;
            font-size: 14.5px;
            color: var(--text-secondary);
            line-height: 1.72;
            text-wrap: pretty;
        }

        /* Final CTA Banner */
        .cta-banner {
            background: linear-gradient(135deg, #eff6ff 0%, #ffffff 55%, #dbeafe 100%);
            border: 1px solid #bfdbfe;
            border-radius: 20px;
            padding: clamp(2rem, 1.5rem + 2vw, 3rem) clamp(1.25rem, 1rem + 2vw, 2.5rem);
            text-align: center;
            box-shadow: var(--shadow-card);
        }

        .cta-banner h2 {
            font-size: clamp(1.35rem, 1.05rem + 1.3vw, 1.95rem);
            font-weight: 800;
            letter-spacing: -0.025em;
            color: var(--text-primary);
            margin-bottom: 10px;
            text-wrap: balance;
        }

        .cta-banner p {
            font-size: 14.5px;
            color: var(--text-secondary);
            max-width: 580px;
            margin: 0 auto 22px;
            text-wrap: pretty;
        }

        /* Native PDF Modal Dialog */
        dialog.pdf-dialog {
            margin: auto;
            width: min(94vw, 960px);
            height: 88vh;
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 0;
            overflow: hidden;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.25);
        }

        dialog.pdf-dialog::backdrop {
            background: rgba(15, 23, 42, 0.55);
            backdrop-filter: blur(3px);
        }

        .pdf-dialog-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 18px;
            border-bottom: 1px solid var(--border-color);
            background: #ffffff;
        }

        .pdf-dialog-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .pdf-dialog-frame {
            width: 100%;
            height: calc(88vh - 56px);
            border: none;
            background: #f1f5f9;
        }

        /* Footer */
        .footer {
            padding: 28px 0;
            background: var(--bg-subtle);
            font-size: 13px;
            color: var(--text-muted);
            border-top: 1px solid var(--border);
        }

        .footer-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }

        @media (max-width: 960px) {
            .studio-frame {
                grid-template-columns: 1fr;
            }

            .studio-frame-sidebar {
                border-right: none;
                border-bottom: 1px solid #e2e8f0;
            }

            .sample-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .nav-links {
                display: none;
            }

            .nav-mobile-toggle {
                display: inline-flex;
            }
        }

        @media (max-width: 640px) {
            .container {
                padding: 0 14px;
            }

            .navbar {
                height: 60px;
            }

            .mobile-nav-drawer {
                top: 60px;
            }

            .nav-inner {
                gap: 8px;
            }

            .brand {
                font-size: 14.5px;
                gap: 7px;
            }

            .brand img {
                width: 32px !important;
                height: 32px !important;
            }

            .nav-actions {
                gap: 6px;
            }

            .nav-login-desktop {
                display: none !important;
            }

            .nav-actions .btn {
                min-height: 36px;
                padding: 7px 12px;
                font-size: 12.5px;
                border-radius: 9px;
            }

            .nav-mobile-toggle {
                width: 36px;
                height: 36px;
                border-radius: 9px;
                flex-shrink: 0;
            }

            .hero {
                padding: 38px 0 34px;
            }

            .hero-eyebrow {
                font-size: 11.5px;
                padding: 4px 11px;
            }

            .hero-trust-strip {
                gap: 7px;
                margin-top: 20px;
            }

            .hero-trust-pill {
                font-size: 11.5px;
                padding: 5px 10px;
            }

            .section {
                padding: 44px 0;
            }

            .sample-grid {
                grid-template-columns: 1fr;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .sf-paper-area {
                padding: 12px;
            }

            .sf-a4-paper {
                padding: 22px 16px;
                font-size: 11pt;
            }

            .btn {
                padding: 9px 14px;
                font-size: 13px;
            }
        }

        /* Landing Tools Mahasiswa Showcase */
        .tools-showcase-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 12px;
            margin-top: 8px;
        }
        .tool-showcase-card {
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            padding: 16px 14px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            text-decoration: none;
            color: inherit;
            position: relative;
            transition: transform 0.16s ease, border-color 0.16s ease, box-shadow 0.16s ease;
        }
        .tool-showcase-card:hover {
            transform: translateY(-3px);
            border-color: #93c5fd;
            box-shadow: 0 12px 26px -14px rgba(37, 99, 235, 0.32);
        }
        .tool-sc-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: #eff6ff;
            color: #2563eb;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .tool-sc-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 999px;
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #a7f3d0;
        }
        .tool-sc-title {
            font-size: 13.5px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.3;
        }
        .tool-sc-desc {
            font-size: 12px;
            color: #64748b;
            line-height: 1.45;
            flex: 1;
        }
        .tool-sc-cta {
            font-size: 12px;
            font-weight: 600;
            color: #2563eb;
            margin-top: 2px;
        }
        .tools-showcase-footer {
            margin-top: 24px;
            text-align: center;
        }
        @media (max-width: 1024px) {
            .tools-showcase-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        @media (max-width: 640px) {
            .tools-showcase-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 9px; }
            .tool-showcase-card { padding: 13px 11px; }
            .tool-sc-title { font-size: 12.5px; }
            .tool-sc-desc { font-size: 11.5px; }
        }

        .demo-title-wrap {
            max-width: 920px;
            margin: 0 auto;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            border: 1px solid #dbeafe;
            border-radius: 20px;
            padding: 28px 30px;
            box-shadow: 0 20px 44px -24px rgba(37, 99, 235, 0.28), 0 4px 12px -6px rgba(15, 23, 42, 0.06);
        }
        .demo-head {
            text-align: center;
            max-width: 640px;
            margin: 0 auto 20px;
        }
        .demo-head h2 {
            font-size: clamp(1.25rem, 1.05rem + 0.9vw, 1.65rem);
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.025em;
            margin: 8px 0 6px;
        }
        .demo-head p {
            font-size: 13.5px;
            color: #64748b;
            line-height: 1.55;
            margin: 0;
        }
        .demo-form-well {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 18px;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .demo-row-split {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) auto;
            gap: 12px;
            align-items: end;
        }
        .demo-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-width: 0;
        }
        .demo-field label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 6px;
            font-size: 13px;
            font-weight: 700;
            color: #1e293b;
            line-height: 1.3;
        }
        .demo-label-opt {
            font-size: 11.5px;
            font-weight: 500;
            color: #64748b;
        }
        .demo-hint {
            font-size: 12px;
            color: #64748b;
        }
        .demo-control {
            width: 100%;
            min-height: 46px;
            padding: 10px 13px;
            font-family: inherit;
            font-size: max(1rem, 0.9375rem);
            color: #0f172a;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            transition: border-color 0.16s ease, box-shadow 0.16s ease, background-color 0.16s ease;
        }
        .demo-control:hover {
            border-color: #94a3b8;
        }
        .demo-control:focus {
            background: #ffffff;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.14);
            outline: none;
        }
        .demo-control:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }
        .demo-control:is(:user-invalid, .user-invalid-fallback) {
            border-color: #dc2626;
            background-color: #fef2f2;
        }
        .demo-err-msg {
            display: none;
            font-size: 12px;
            font-weight: 600;
            color: #dc2626;
            margin-top: 2px;
        }
        .demo-control:is(:user-invalid, .user-invalid-fallback) + .demo-err-msg {
            display: block;
        }
        .demo-submit-btn {
            min-height: 46px;
            padding: 10px 22px;
            white-space: nowrap;
        }
        .demo-results {
            margin-top: 18px;
            padding-top: 16px;
            border-top: 1px solid #e2e8f0;
            display: none;
        }
        .demo-result-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 16px;
            border-radius: 12px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }
        .demo-result-title {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.45;
            flex: 1 1 320px;
        }
        .demo-result-actions {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .copy-tip {
            font-size: 11.5px;
            font-weight: 700;
            color: #047857;
            opacity: 0;
            transition: opacity 0.15s ease;
        }
        button[data-copied] + .copy-tip, button[data-failed] + .copy-tip {
            opacity: 1;
        }
        @media (max-width: 760px) {
            .demo-title-wrap { padding: 20px 16px; border-radius: 16px; }
            .demo-form-well { padding: 14px; }
            .demo-row-split { grid-template-columns: 1fr; align-items: stretch; }
            .demo-submit-btn { width: 100%; }
        }

        @media (max-width: 440px) {
            .hero-cta {
                flex-direction: column;
                align-items: stretch;
            }

            .hero-cta .btn {
                width: 100%;
            }
        }
    </style>
    @include('partials.meta_pixel', ['metaViewEvent' => ['name' => 'ViewContent', 'params' => ['content_name' => 'SkripsiLab AI Landing', 'content_category' => 'landing']]])
</head>

<body>

    <!-- Navbar -->
    <header class="navbar">
        <div class="container nav-inner">
            <a href="/" class="brand">
                <img src="/images/logo.png" alt="SkripsiLab AI"
                    style="width: 38px; height: 38px; display: block; object-fit: contain;">
                SkripsiLab AI
            </a>

            <ul class="nav-links">
                <li class="nav-item">
                    <button type="button" class="nav-trigger">
                        Fitur
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <polyline points="6 9 12 15 18 9" />
                        </svg>
                    </button>
                    <div class="dropdown-panel">
                        <a href="{{ auth()->check() ? route('app.create', ['tab' => 'titles']) : route('login') }}"
                            class="dropdown-item">
                            <div class="dropdown-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="1.8">
                                    <circle cx="11" cy="11" r="8" />
                                    <line x1="21" y1="21" x2="16.65" y2="16.65" />
                                </svg>
                            </div>
                            <div>
                                <div class="dropdown-title">Cari Judul Skripsi</div>
                                <div class="dropdown-desc">10 saran judul sesuai jurusan & metodologi</div>
                            </div>
                        </a>

                        <a href="{{ auth()->check() ? route('app.create', ['tab' => 'projects']) : route('login') }}"
                            class="dropdown-item">
                            <div class="dropdown-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="1.8">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                    <polyline points="14 2 14 8 20 8" />
                                </svg>
                            </div>
                            <div>
                                <div class="dropdown-title">Workspace Proposal & Skripsi</div>
                                <div class="dropdown-desc">Susun Bab 1–3 atau Bab 1–5 + Lab Revisi</div>
                            </div>
                        </a>

                        <a href="{{ auth()->check() ? route('app.create', ['tab' => 'paraphrase']) : route('login') }}"
                            class="dropdown-item">
                            <div class="dropdown-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="1.8">
                                    <path d="M12 20h9" />
                                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" />
                                </svg>
                            </div>
                            <div>
                                <div class="dropdown-title">Parafrase Online</div>
                                <div class="dropdown-desc">Parafrase akademik, hasil natural & bebas plagiasi</div>
                            </div>
                        </a>

                        <a href="{{ auth()->check() ? route('app.create', ['tab' => 'journals']) : route('login') }}"
                            class="dropdown-item">
                            <div class="dropdown-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="1.8">
                                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
                                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />
                                </svg>
                            </div>
                            <div>
                                <div class="dropdown-title">Cari Jurnal Nasional & Internasional</div>
                                <div class="dropdown-desc">Referensi OpenAlex & DOI siap sitasi APA/IEEE</div>
                            </div>
                        </a>

                        <a href="{{ auth()->check() ? route('app.create', ['tab' => 'ppt']) : route('login') }}"
                            class="dropdown-item">
                            <div class="dropdown-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="1.8">
                                    <rect x="2" y="3" width="20" height="14" rx="2" />
                                    <line x1="8" y1="21" x2="16" y2="21" />
                                </svg>
                            </div>
                            <div>
                                <div class="dropdown-title">PPT Sidang &amp; Slide Show</div>
                                <div class="dropdown-desc">13 tema akademik profesional &amp; ekspor .pptx</div>
                            </div>
                        </a>

                        <a href="{{ auth()->check() ? route('app.create', ['tab' => 'defense']) : route('login') }}"
                            class="dropdown-item">
                            <div class="dropdown-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="1.8">
                                    <path d="M22 10v6M2 10l10-5 10 5-10 5z" />
                                    <path d="M6 12v5c3 3 9 3 12 0v-5" />
                                </svg>
                            </div>
                            <div>
                                <div class="dropdown-title">Simulasi Sidang</div>
                                <div class="dropdown-desc">Latihan tanya-jawab penguji &amp; skor evaluasi</div>
                            </div>
                        </a>

                        <a href="{{ auth()->check() ? route('app.create', ['tab' => 'qa']) : route('login') }}"
                            class="dropdown-item">
                            <div class="dropdown-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="1.8">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                                </svg>
                            </div>
                            <div>
                                <div class="dropdown-title">Dosen AI (Konsultasi)</div>
                                <div class="dropdown-desc">Bedah metodologi, analisis data &amp; tanya jawab soal</div>
                            </div>
                        </a>
                    </div>
                </li>
                <li><a href="#workspace-preview" class="nav-link">Pratinjau Workspace</a></li>
                <li><a href="#contoh-hasil" class="nav-link">Contoh Hasil</a></li>
                <li><a href="#tools-gratis" class="nav-link">Tools</a></li>
                <li><a href="#harga-paket" class="nav-link">Harga Paket</a></li>
                <li><a href="{{ auth()->check() ? route('app.create', ['tab' => 'panduan']) . '#panduan' : url('/panduan') }}" class="nav-link">Panduan</a></li>
                <li><a href="#faq" class="nav-link">FAQ</a></li>
            </ul>

            <div class="nav-actions">
                @auth
                    <a href="{{ route('app.create') }}" class="btn btn-primary">Workspace</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-ghost nav-login-desktop">Masuk</a>
                    <a href="{{ route('register') }}" class="btn btn-primary">Mulai</a>
                @endauth
                <button type="button" class="nav-mobile-toggle" id="landing-mobile-toggle" aria-expanded="false"
                    aria-controls="landing-mobile-drawer" aria-label="Buka menu navigasi"
                    onclick="toggleLandingMobileNav()">
                    <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        aria-hidden="true">
                        <line x1="3" y1="6" x2="21" y2="6" />
                        <line x1="3" y1="12" x2="21" y2="12" />
                        <line x1="3" y1="18" x2="21" y2="18" />
                    </svg>
                </button>
            </div>
        </div>
    </header>

    <nav class="mobile-nav-drawer" id="landing-mobile-drawer" aria-label="Navigasi Seluler">
        <div class="mobile-nav-list">
            <a href="#workspace-preview" class="mobile-nav-link" onclick="closeLandingMobileNav()">Pratinjau Workspace
                A4 <span>→</span></a>
            <a href="#contoh-hasil" class="mobile-nav-link" onclick="closeLandingMobileNav()">Contoh Proposal &amp;
                Skripsi <span>→</span></a>
            <a href="#tools-gratis" class="mobile-nav-link" onclick="closeLandingMobileNav()">10 Tools Gratis
                <span>→</span></a>
            <a href="#harga-paket" class="mobile-nav-link" onclick="closeLandingMobileNav()">Harga Paket (Mulai Rp15rb)
                <span>→</span></a>
            <a href="{{ auth()->check() ? route('app.create', ['tab' => 'panduan']) . '#panduan' : url('/panduan') }}" class="mobile-nav-link" onclick="closeLandingMobileNav()">Panduan Skripsi (7 Modul)
                <span>→</span></a>
            <a href="#fitur-lengkap" class="mobile-nav-link" onclick="closeLandingMobileNav()">Semua 9 Modul SkripsiLab
                AI
                <span>→</span></a>
            <a href="#faq" class="mobile-nav-link" onclick="closeLandingMobileNav()">Pertanyaan Umum (FAQ)
                <span>→</span></a>
            @auth
                <div class="mobile-nav-actions single">
                    <a href="{{ route('app.create') }}" class="btn btn-primary" onclick="closeLandingMobileNav()">Buka
                        Workspace →</a>
                </div>
            @else
                <div class="mobile-nav-actions">
                    <a href="{{ route('login') }}" class="btn btn-outline" onclick="closeLandingMobileNav()">Masuk</a>
                    <a href="{{ route('register') }}" class="btn btn-primary" onclick="closeLandingMobileNav()">Daftar
                        Gratis</a>
                </div>
            @endauth
        </div>
    </nav>

    <main>
        <!-- Hero -->
        <section class="hero">
            <div class="container">
                <div class="hero-eyebrow">SkripsiLab AI · Workspace Akademik Mahasiswa</div>
                <h1>Susun Proposal &amp; Skripsi Otomatis dengan AI</h1>
                <p>Buat proposal Bab 1–3 &amp; skripsi Bab 1–5 otomatis menggunakan AI dengan <strong>referensi ilmiah
                        dan jurnal yang relevan ber-DOI</strong>, ekspor Word/PDF, PPT sidang, hingga simulasi sidang.
                </p>

                <div class="hero-cta">
                    @auth
                        <a href="{{ route('app.create', ['tab' => 'projects']) }}" class="btn btn-primary">Buat Proyek Baru
                            →</a>
                    @else
                        <a href="{{ route('register') }}" class="btn btn-primary" data-meta-lead="Hero Coba Gratis">Coba Gratis Sekarang →</a>
                    @endauth
                    <a href="#contoh-hasil" class="btn btn-outline">Lihat Contoh Proposal &amp; Skripsi</a>
                </div>

                <div class="hero-trust-strip" aria-label="Keunggulan utama SkripsiLab AI">
                    <span class="hero-trust-pill">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.5" aria-hidden="true">
                            <polyline points="20 6 9 17 4 12" />
                        </svg>
                        Referensi Ilmiah &amp; Jurnal Relevan (DOI)
                    </span>
                    <span class="hero-trust-pill">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.5" aria-hidden="true">
                            <polyline points="20 6 9 17 4 12" />
                        </svg>
                        Otomatis AI · Margin 4-4-3-3
                    </span>
                    <span class="hero-trust-pill">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.5" aria-hidden="true">
                            <polyline points="20 6 9 17 4 12" />
                        </svg>
                        Ekspor Word (.docx) &amp; PDF
                    </span>
                    <span class="hero-trust-pill">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.5" aria-hidden="true">
                            <polyline points="20 6 9 17 4 12" />
                        </svg>
                        9 Metode &amp; Semua Jurusan
                    </span>
                </div>
            </div>
        </section>

        <!-- Widget Interaktif: Coba Buat 3 Ide Judul Gratis Langsung di Beranda -->
        <section id="coba-judul" class="section" style="padding: 20px 0 46px;">
            <div class="container">
                <div class="demo-title-wrap">
                    <div class="demo-head">
                        <span class="section-label">Coba Langsung Tanpa Daftar · Gratis</span>
                        <h2>Buat 3 Ide Judul &amp; Rumusan Masalah Sekarang</h2>
                        <p>Tulis topik penelitianmu di bawah ini — AI langsung menyusun 3 opsi judul spesifik jurusanmu lengkap dengan contoh rumusan masalahnya.</p>
                    </div>

                    <form id="demo-title-form" class="demo-form-well" novalidate onsubmit="submitDemoTitles(event)">
                        <input type="text" name="website_hp" value="" tabindex="-1" aria-hidden="true" autocomplete="off" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0;">

                        <div class="demo-field">
                            <label for="demo-topik">
                                <span>Topik / Minat Penelitian <span aria-hidden="true" style="color:#dc2626;">*</span></span>
                                <span id="demo-topik-hint" class="demo-label-opt">Minimal 3 karakter</span>
                            </label>
                            <input type="text" id="demo-topik" name="topik" class="demo-control" required minlength="3" maxlength="160" aria-describedby="demo-topik-hint" data-error="demo-topik-error" placeholder="Contoh: Pengaruh TikTok Live terhadap keputusan pembelian UMKM atau Prediksi kelulusan mahasiswa">
                            <div id="demo-topik-error" class="demo-err-msg">Error: Masukkan topik penelitian minimal 3 karakter terlebih dahulu.</div>
                        </div>

                        <div class="demo-row-split">
                            <div class="demo-field">
                                <label for="demo-prodi">
                                    <span>Jurusan / Prodi</span>
                                    <span class="demo-label-opt">Opsional</span>
                                </label>
                                <input type="text" id="demo-prodi" name="prodi" class="demo-control" maxlength="80" placeholder="Contoh: Manajemen, Informatika, PGSD">
                            </div>

                            <div class="demo-field">
                                <label for="demo-metode"><span>Metode Penelitian</span></label>
                                <select id="demo-metode" name="metode" class="demo-control">
                                    <option value="Kuantitatif (Survei / Regresi)" selected>Kuantitatif (Survei / Regresi)</option>
                                    <option value="Kualitatif (Studi Kasus / Deskriptif)">Kualitatif (Studi Kasus)</option>
                                    <option value="R&D / Pengembangan Sistem">R&amp;D / Pengembangan Sistem</option>
                                    <option value="PTK (Penelitian Tindakan Kelas)">PTK (Tindakan Kelas)</option>
                                    <option value="Hukum Normatif">Hukum Normatif</option>
                                    <option value="Mixed Method">Mixed Method</option>
                                </select>
                            </div>

                            <button type="submit" id="demo-submit-btn" class="btn btn-primary demo-submit-btn">Buat 3 Ide Judul →</button>
                        </div>
                    </form>

                    <div id="demo-status-live" class="demo-hint" role="status" aria-live="polite" style="margin-top:10px;text-align:center;"></div>
                    <div id="demo-results-box" class="demo-results" aria-live="polite">
                        <div id="demo-rumusan-card" style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:11px;padding:12px 14px;font-size:13.5px;color:#1e3a8a;margin-bottom:12px;"></div>
                        <div id="demo-titles-list"></div>
                        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-top:12px;font-size:13px;color:#475569;">
                            <span>Ingin <strong>10+ ide judul lainnya &amp; Cari Jurnal Ber-DOI</strong> tanpa batas? <strong>Gratis Unlimited</strong> di akun SkripsiLab AI.</span>
                            <a href="{{ auth()->check() ? route('app.create', ['tab' => 'titles']) : route('register') }}" class="btn btn-outline" style="min-height:38px;" data-meta-lead="Demo Hasil Buka Unlimited Judul">Buka Cari Judul &amp; Jurnal Unlimited →</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 1: Pratinjau Workspace Generator (Static Capture) -->
        <section id="workspace-preview" class="section section-subtle">
            <div class="container">
                <div class="section-header">
                    <span class="section-label">Pratinjau Workspace Generate</span>
                    <h2 class="section-title">Tampilan Ruang Kerja Penyusunan Skripsi di Dalam Aplikasi</h2>
                    <p class="section-desc">Gambaran nyata antarmuka Workspace Generate SkripsiLab AI — mulai dari navigasi
                        Bab I–V, pengatur margin 4-4-3-3, Olah Data Penelitian, Lab Revisi, hingga kertas A4 siap ekspor Word &amp; PDF.</p>
                </div>

                <div class="studio-window" aria-hidden="true">
                    <div class="sf-window-bar">
                        <div class="sf-mac-left">
                            <div class="sf-mac-dots">
                                <span class="sf-mac-dot red"></span>
                                <span class="sf-mac-dot yellow"></span>
                                <span class="sf-mac-dot green"></span>
                            </div>
                            <div class="sf-win-brand">
                                <img src="/images/logo.png" alt="" width="24" height="24" style="display:block;object-fit:contain;">
                                <span>SkripsiLab AI</span>
                            </div>
                        </div>

                        <div class="sf-win-url">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                            <span>skripsilabai.com/project/workspace</span>
                        </div>

                        <div class="sf-win-status">
                            <span class="sf-live-dot"></span>
                            <span>Auto-Format A4 Aktif</span>
                        </div>
                    </div>

                    <div class="studio-frame">
                        <div class="studio-frame-sidebar">
                            <div class="sf-back-chip">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                    <line x1="19" y1="12" x2="5" y2="12"></line>
                                    <polyline points="12 19 5 12 12 5"></polyline>
                                </svg>
                                <span>Kembali ke Proyek Saya</span>
                            </div>
                            <span class="sf-mode-badge">Skripsi Penuh (Bab I–V)</span>

                            <div>
                                <div class="sf-progress-meta">
                                    <span>5 dari 5 Bab</span>
                                    <span>100%</span>
                                </div>
                                <div style="height: 6px; background: #e2e8f0; border-radius: 999px; overflow: hidden;">
                                    <div style="width: 100%; height: 100%; background: linear-gradient(90deg, #2563eb, #10b981);"></div>
                                </div>
                            </div>

                            <div class="sf-sub">Struktur Naskah</div>
                            <div class="sf-nav-list">
                                <div class="sf-nav-btn">
                                    <span>Lihat Semua Bab (Full)</span>
                                    <span style="font-size:9.5px;font-weight:700;color:#2563eb;background:#dbeafe;padding:1px 6px;border-radius:999px;">A4 Full</span>
                                </div>
                                <div class="sf-nav-btn">
                                    <span>Sampul &amp; Bagian Depan</span>
                                    <span style="font-size:9.5px;font-weight:700;color:#16a34a;background:#dcfce7;padding:1px 6px;border-radius:999px;">Lengkap</span>
                                </div>
                                <div class="sf-nav-btn active">
                                    <span>Bab I Pendahuluan</span>
                                    <span style="color: #10b981;">●</span>
                                </div>
                                <div class="sf-nav-btn">
                                    <span>Bab II Tinjauan Pustaka</span>
                                    <span style="color: #10b981;">●</span>
                                </div>
                                <div class="sf-nav-btn">
                                    <span>Bab III Metodologi</span>
                                    <span style="color: #10b981;">●</span>
                                </div>
                                <div class="sf-nav-btn">
                                    <span>Bab IV Hasil Penelitian</span>
                                    <span style="color: #10b981;">●</span>
                                </div>
                                <div class="sf-nav-btn">
                                    <span>Bab V Penutup</span>
                                    <span style="color: #10b981;">●</span>
                                </div>
                                <div class="sf-nav-btn">
                                    <span>Daftar Pustaka</span>
                                    <span style="color: #10b981;">●</span>
                                </div>
                                <div class="sf-nav-btn">
                                    <span>Lampiran &amp; Revisi</span>
                                    <span style="font-size:9.5px;font-weight:700;color:#2563eb;background:#dbeafe;padding:1px 6px;border-radius:999px;">Sub-bab</span>
                                </div>
                            </div>

                            <div class="sf-side-format">
                                <div class="sf-side-format-head">
                                    <span>Format &amp; Margin</span>
                                    <span class="sf-margin-tag">4-4-3-3 cm</span>
                                </div>
                                <div>Font: Times New Roman 12pt &bull; Penomoran Romawi/Arab otomatis.</div>
                            </div>

                            <div class="sf-side-dl">
                                <span class="sf-dl-pill primary">⬇ Word (.docx)</span>
                                <span class="sf-dl-pill">⬇ PDF (.pdf)</span>
                            </div>
                        </div>

                        <div class="studio-frame-main">
                            <div class="sf-topbar">
                                <div class="sf-topbar-meta">
                                    <span class="sf-live-dot"></span>
                                    <span>Workspace Penulisan &bull; Manajemen &bull; A4 Margin 4-4-3-3</span>
                                </div>
                            </div>

                            <div class="sf-chapter-bar">
                                <div class="sf-chapter-bar-title">BAB I PENDAHULUAN</div>
                                <div class="sf-chapter-bar-tools">
                                    <span class="sf-tool-chip">Lihat Semua Bab</span>
                                    <span class="sf-tool-chip">Olah Data Penelitian</span>
                                    <span class="sf-tool-chip">Buka Revisi</span>
                                </div>
                            </div>

                            <div class="sf-paper-area">
                                <div class="sf-a4-paper">
                                    <div class="sf-chapter-head">
                                        <div class="sf-chapter-num">BAB I</div>
                                        <div class="sf-chapter-name">PENDAHULUAN</div>
                                    </div>
                                    <h4>1.1 Latar Belakang Masalah</h4>
                                    <p>Transformasi digital pada sektor industri telah menggeser standar kompetensi yang dibutuhkan dari lulusan perguruan tinggi. Menurut <span class="cite-blue">Naufal &amp; Wijaya (2024)</span>, penguasaan teori akademik saja tidak lagi cukup tanpa dibarengi kecakapan literasi digital serta regulasi diri yang matang dalam menyelesaikan target kerja.</p>
                                    <p>Hasil observasi awal menunjukkan bahwa meskipun mahasiswa menggunakan perangkat digital lebih dari 7 jam per hari, pemanfaatannya untuk riset karier dan produktivitas profesional masih rendah (<span class="cite-blue">Kusuma et al., 2023</span>). Oleh karena itu, kajian empiris mengenai peran literasi digital, regulasi diri, dan dukungan institusi terhadap kesiapan kerja menjadi penting dilakukan.</p>
                                    <h4>1.2 Rumusan Masalah</h4>
                                    <p class="sf-list-line">1. Apakah literasi digital berpengaruh positif dan signifikan terhadap kesiapan kerja mahasiswa?</p>
                                    <p class="sf-list-line">2. Apakah regulasi diri berpengaruh positif dan signifikan terhadap kesiapan kerja mahasiswa?</p>
                                    <p class="sf-list-line">3. Apakah dukungan institusi memoderasi pengaruh literasi digital terhadap kesiapan kerja?</p>
                                    <div class="sf-page-footer">1</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 2: Contoh Hasil Proposal & Skripsi -->
        <section id="contoh-hasil" class="section">
            <div class="container">
                @php
                    $realProjects = collect($publicProjects ?? []);
                    $proposalCount = $realProjects->filter(function ($p) {
                        return trim((string) $p->bab4_content) === '' || trim((string) $p->bab5_content) === '';
                    })->count();
                    $skripsiCount = $realProjects->filter(function ($p) {
                        return trim((string) $p->bab4_content) !== '' && trim((string) $p->bab5_content) !== '';
                    })->count();
                @endphp
                <div class="section-header">
                    <span class="section-label">Contoh Hasil Proposal &amp; Skripsi</span>
                    <h2 class="section-title">Galeri Naskah Asli Hasil Generate</h2>
                    <p class="section-desc">Daftar proyek nyata yang telah selesai disusun di SkripsiLab AI — lengkap
                        Bab I–III untuk Proposal dan Bab I–V untuk Full Skripsi, siap baca PDF &amp; unduh Word.</p>
                </div>

                <div class="major-selector" id="gallery-filter-bar">
                    <button type="button" class="major-pill active" onclick="filterGallery('all', this)">Semua
                        ({{ $realProjects->count() }})</button>
                    <button type="button" class="major-pill" onclick="filterGallery('proposal', this)">Proposal Bab
                        1–3 ({{ $proposalCount }})</button>
                    <button type="button" class="major-pill" onclick="filterGallery('skripsi', this)">Full Skripsi Bab
                        1–5 ({{ $skripsiCount }})</button>
                </div>

                <div class="sample-grid" id="sample-cards-grid">
                    @forelse ($realProjects as $proj)
                        @php
                            $projHas45 = trim((string) $proj->bab4_content) !== '' && trim((string) $proj->bab5_content) !== '';
                            $projStage = $projHas45 ? 'skripsi' : 'proposal';
                            $projPdfUrl = route('public.project.download', ['id' => $proj->id, 'format' => 'pdf']);
                            $projDocxUrl = route('public.project.download', ['id' => $proj->id, 'format' => 'docx']);
                        @endphp
                        <article class="sample-card" data-stage="{{ $projStage }}">
                            <div>
                                <div class="sample-badges">
                                    <span
                                        class="badge badge-blue">{{ $projHas45 ? 'Full Skripsi (Bab 1–5)' : 'Proposal (Bab 1–3)' }}</span>
                                    <span class="badge badge-slate">{{ $proj->prodi ?: 'Skripsi S1' }}</span>
                                </div>
                                <h3>{{ $proj->judul }}</h3>
                                <div class="sample-meta">Metode: {{ $proj->metode_penelitian ?: 'Akademik' }}</div>
                            </div>
                            <div class="sample-actions">
                                <a href="{{ $projPdfUrl . '?inline=1' }}" target="_blank" rel="noopener"
                                    class="sample-btn primary">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                        <circle cx="12" cy="12" r="3" />
                                    </svg>
                                    <span>Lihat PDF</span>
                                </a>
                                <a href="{{ $projDocxUrl }}" class="sample-btn" download>
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                        <polyline points="14 2 14 8 20 8" />
                                    </svg>
                                    <span>.docx</span>
                                </a>
                                <a href="{{ $projPdfUrl }}" class="sample-btn" download>
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                        <polyline points="14 2 14 8 20 8" />
                                    </svg>
                                    <span>.pdf</span>
                                </a>
                            </div>
                        </article>
                    @empty
                        <div
                            style="grid-column: 1 / -1; text-align: center; padding: 36px 20px; background: var(--bg-subtle); border: 1px dashed var(--border-strong); border-radius: 14px; color: var(--text-muted); font-size: 13.5px;">
                            Belum ada proyek Proposal (Bab 1–3) atau Full Skripsi (Bab 1–5) yang selesai di-generate.
                        </div>
                    @endforelse
                </div>
                <div id="gallery-empty-filter"
                    style="display: none; text-align: center; padding: 28px 18px; margin-top: 12px; background: var(--bg-subtle); border: 1px dashed var(--border-strong); border-radius: 12px; color: var(--text-muted); font-size: 13.5px;">
                    Belum ada proyek selesai pada kategori ini.
                </div>
            </div>
        </section>

        <!-- Section 3: Semua Fitur -->
        <section id="fitur-lengkap" class="section section-subtle">
            <div class="container">
                <div class="section-header">
                    <span class="section-label">Semua Modul SkripsiLab AI</span>
                    <h2 class="section-title">Pendamping Lengkap dari Ide Judul hingga Sidang</h2>
                    <p class="section-desc">Sembilan modul akademik terintegrasi dalam satu workspace untuk membantu
                        menyusun proposal, skripsi penuh, referensi, presentasi, hingga persiapan sidang.</p>
                </div>

                <div class="features-grid">
                    <div class="feature-card">
                        <div>
                            <div class="feature-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="1.8">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                    <polyline points="14 2 14 8 20 8" />
                                </svg>
                            </div>
                            <h3>Workspace Proposal &amp; Skripsi</h3>
                            <p>Atur pedoman kampus, logo universitas, font &amp; margin 4-4-3-3, lalu susun Bab I–III
                                atau Bab I–V lengkap dengan Daftar Isi otomatis, Daftar Pustaka berlink DOI, serta
                                ekspor Word (.docx) &amp; PDF (.pdf).</p>
                        </div>
                        <a href="{{ auth()->check() ? route('app.create', ['tab' => 'projects']) : route('login') }}"
                            class="feature-link">Buka Workspace →</a>
                    </div>

                    <div class="feature-card">
                        <div>
                            <div class="feature-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="1.8">
                                    <circle cx="11" cy="11" r="8" />
                                    <line x1="21" y1="21" x2="16.65" y2="16.65" />
                                </svg>
                            </div>
                            <h3>Cari Judul Penelitian</h3>
                            <p>Hasilkan 10 rekomendasi judul skripsi terbaru beserta rumusan masalah yang selaras dengan
                                program studi dan 9 pilihan metode penelitian (Kuantitatif, Kualitatif, R&amp;D, PTK,
                                Normatif, Mixed Method, dll.).</p>
                        </div>
                        <a href="{{ auth()->check() ? route('app.create', ['tab' => 'titles']) : route('login') }}"
                            class="feature-link">Cari Judul →</a>
                    </div>

                    <div class="feature-card">
                        <div>
                            <div class="feature-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="1.8">
                                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
                                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />
                                </svg>
                            </div>
                            <h3>Cari Jurnal &amp; Referensi DOI</h3>
                            <p>Temukan artikel jurnal nasional &amp; internasional terverifikasi Crossref/OpenAlex tahun
                                2021–2026 lengkap dengan tautan DOI aktif, filter cakupan, dan tombol salin sitasi
                                sesuai gaya pilihanmu (APA 7th, Harvard, IEEE, Chicago, Vancouver).</p>
                        </div>
                        <a href="{{ auth()->check() ? route('app.create', ['tab' => 'journals']) : route('login') }}"
                            class="feature-link">Cari Jurnal →</a>
                    </div>

                    <div class="feature-card">
                        <div>
                            <div class="feature-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="1.8">
                                    <rect x="2" y="3" width="20" height="14" rx="2" />
                                    <line x1="8" y1="21" x2="16" y2="21" />
                                </svg>
                            </div>
                            <h3>PPT Sidang Otomatis</h3>
                            <p>Pilih proyek skripsimu atau unggah file Word/PDF, pilih dari 13 tema akademik
                                profesional, presentasikan Fullscreen Slide Show lengkap dengan catatan bicara, atau
                                ekspor PowerPoint (.pptx).</p>
                        </div>
                        <a href="{{ auth()->check() ? route('app.create', ['tab' => 'ppt']) : route('login') }}"
                            class="feature-link">Buat Slide PPT →</a>
                    </div>

                    <div class="feature-card">
                        <div>
                            <div class="feature-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="1.8">
                                    <path d="M22 10v6M2 10l10-5 10 5-10 5z" />
                                    <path d="M6 12v5c3 3 9 3 12 0v-5" />
                                </svg>
                            </div>
                            <h3>Simulasi Sidang Skripsi</h3>
                            <p>Latihan menjawab 5 pertanyaan penguji yang dibuat langsung dari isi naskah Bab I–V atau
                                unggahan Word/PDF, lengkap dengan skor per pertanyaan, grade akhir, dan contoh jawaban
                                terbaik.</p>
                        </div>
                        <a href="{{ auth()->check() ? route('app.create', ['tab' => 'defense']) : route('login') }}"
                            class="feature-link">Mulai Latihan →</a>
                    </div>

                    <div class="feature-card">
                        <div>
                            <div class="feature-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="1.8">
                                    <path d="M12 20h9" />
                                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" />
                                </svg>
                            </div>
                            <h3>Parafrase Akademik</h3>
                            <p>Tulis ulang paragraf agar gaya bahasanya lebih ilmiah, kohesif, dan natural sesuai kaidah
                                EYD V tanpa mengubah makna kutipan untuk menurunkan tingkat kemiripan teks.</p>
                        </div>
                        <a href="{{ auth()->check() ? route('app.create', ['tab' => 'paraphrase']) : route('login') }}"
                            class="feature-link">Buka Parafrase →</a>
                    </div>

                    <div class="feature-card">
                        <div>
                            <div class="feature-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="1.8">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                                </svg>
                            </div>
                            <h3>Dosen AI (Konsultasi &amp; Bedah Soal)</h3>
                            <p>Konsultasi interaktif seputar metodologi penelitian, pemilihan uji statistik, kerangka
                                teori, hingga unggah foto soal, tabel, atau output pengujian untuk dibedah langkah demi
                                langkah.</p>
                        </div>
                        <a href="{{ auth()->check() ? route('app.create', ['tab' => 'qa']) : route('login') }}"
                            class="feature-link">Tanya Dosen AI →</a>
                    </div>

                    <div class="feature-card">
                        <div>
                            <div class="feature-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="1.8">
                                    <path
                                        d="M10 2v7.31L4.62 17.6A2 2 0 0 0 6.3 20.5h11.4a2 2 0 0 0 1.68-2.9L14 9.31V2z" />
                                </svg>
                            </div>
                            <h3>Lab Revisi &amp; Olah Data Penelitian</h3>
                            <p>Perbaiki sub-bab spesifik sesuai catatan dosen pembimbing serta masukkan angka olah data
                                riil &amp; rumus (SPSS, SmartPLS, Machine Learning, Eksperimen) agar langsung
                                terintegrasi ke Bab III &amp; IV.</p>
                        </div>
                        <a href="{{ auth()->check() ? route('app.create', ['tab' => 'projects']) : route('login') }}"
                            class="feature-link">Kelola Proyek →</a>
                    </div>

                    <div class="feature-card">
                        <div>
                            <div class="feature-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="1.8">
                                    <path
                                        d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48" />
                                </svg>
                            </div>
                            <h3>Lampiran Otomatis (Word &amp; PDF)</h3>
                            <p>Unggah kuesioner, surat izin, dokumentasi sistem, atau dokumen Word/PDF/gambar sebagai
                                lampiran yang otomatis tercatat di Daftar Isi &amp; Daftar Lampiran serta ikut tercetak
                                di Word &amp; PDF.</p>
                        </div>
                        <a href="{{ auth()->check() ? route('app.create', ['tab' => 'projects']) : route('login') }}"
                            class="feature-link">Tambah Lampiran →</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 3B: 10 Tools Mahasiswa Gratis (Penjelasan Modul Tools — sama seperti Semua Modul) -->
        <section id="tools-gratis" class="section">
            <div class="container">
                <div class="section-header">
                    <span class="section-label">Gratis di Workspace · 10 Alat Akademik</span>
                    <h2 class="section-title">10 Tools Mahasiswa Lengkap dalam Satu Tempat</h2>
                    <p class="section-desc">Semua alat harian yang paling sering dibutuhkan saat menyusun proposal, skripsi, dan berkas SIAKAD — tersedia gratis begitu kamu masuk ke Workspace SkripsiLab AI.</p>
                </div>

                <div class="tools-showcase-grid">
                    <div class="tool-showcase-card">
                        <span class="tool-sc-badge">AI</span>
                        <span class="tool-sc-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="7" y="2" width="10" height="5" rx="2.5"/><path d="M12 7v3"/><rect x="5" y="10" width="14" height="5" rx="1"/><path d="M12 15v3"/><rect x="7" y="18" width="10" height="4" rx="2"/></svg>
                        </span>
                        <div class="tool-sc-title">Diagram &amp; Flowchart AI</div>
                        <div class="tool-sc-desc">Buat diagram alur penelitian (Bab III), kerangka berpikir (Bab II), &amp; alur sistem otomatis lalu unduh PNG HD/SVG.</div>
                        <a href="{{ auth()->check() ? route('tools.hub') . '#flowchart' : route('login') }}" class="tool-sc-cta">Buka di Workspace →</a>
                    </div>

                    <div class="tool-showcase-card">
                        <span class="tool-sc-badge">Baru</span>
                        <span class="tool-sc-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l2.4 7.4L22 12l-7.6 2.6L12 22l-2.4-7.4L2 12l7.6-2.6z"/></svg>
                        </span>
                        <div class="tool-sc-title">Rapikan Skripsi Word</div>
                        <div class="tool-sc-desc">Tata ulang margin 4-4-3-3, font, spasi, pindah halaman BAB &amp; tabel file .docx tanpa mengubah 1 kata pun isi.</div>
                        <a href="{{ auth()->check() ? route('tools.hub') . '#tidy-docx' : route('login') }}" class="tool-sc-cta">Buka di Workspace →</a>
                    </div>

                    <div class="tool-showcase-card">
                        <span class="tool-sc-badge">Gratis</span>
                        <span class="tool-sc-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 15h6"/><path d="M12 12l3 3-3 3"/></svg>
                        </span>
                        <div class="tool-sc-title">Konversi PDF ⇄ Word</div>
                        <div class="tool-sc-desc">Ubah PDF skripsi/jurnal jadi Word (.docx) siap edit, atau ubah file Word ke PDF ukuran A4 rapi.</div>
                        <a href="{{ auth()->check() ? route('tools.hub') . '#pdf-word' : route('login') }}" class="tool-sc-cta">Buka di Workspace →</a>
                    </div>

                    <div class="tool-showcase-card">
                        <span class="tool-sc-badge">Gratis</span>
                        <span class="tool-sc-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="8" height="10" rx="1.5"/><rect x="13" y="3" width="8" height="10" rx="1.5"/><path d="M7 13v4a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-4"/></svg>
                        </span>
                        <div class="tool-sc-title">Gabung PDF</div>
                        <div class="tool-sc-desc">Satukan Cover, Lembar Pengesahan, Bab I–V &amp; Lampiran jadi 1 PDF utuh dengan pratinjau halaman.</div>
                        <a href="{{ auth()->check() ? route('tools.hub') . '#merge-pdf' : route('login') }}" class="tool-sc-cta">Buka di Workspace →</a>
                    </div>

                    <div class="tool-showcase-card">
                        <span class="tool-sc-badge">Gratis</span>
                        <span class="tool-sc-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><line x1="20" y1="4" x2="8.12" y2="15.88"/><line x1="14.47" y1="14.48" x2="20" y2="20"/><line x1="8.12" y1="8.12" x2="12" y2="12"/></svg>
                        </span>
                        <div class="tool-sc-title">Pisah &amp; Hapus PDF</div>
                        <div class="tool-sc-desc">Klik langsung halaman PDF dari pratinjau visual untuk mengambil atau menghapus halaman tertentu.</div>
                        <a href="{{ auth()->check() ? route('tools.hub') . '#split-pdf' : route('login') }}" class="tool-sc-cta">Buka di Workspace →</a>
                    </div>

                    <div class="tool-showcase-card">
                        <span class="tool-sc-badge">3-in-1</span>
                        <span class="tool-sc-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 14 10 14 10 20"/><polyline points="20 10 14 10 14 4"/><line x1="14" y1="10" x2="21" y2="3"/><line x1="3" y1="21" x2="10" y2="14"/></svg>
                        </span>
                        <div class="tool-sc-title">Kompres PDF, Word &amp; Foto</div>
                        <div class="tool-sc-desc">Kecilkan ukuran PDF, file Word (.docx) berat, &amp; gambar agar lolos batas upload portal SIAKAD.</div>
                        <a href="{{ auth()->check() ? route('tools.hub') . '#compress' : route('login') }}" class="tool-sc-cta">Buka di Workspace →</a>
                    </div>

                    <div class="tool-showcase-card">
                        <span class="tool-sc-badge">Baru</span>
                        <span class="tool-sc-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 15l5-5 4 4 3-3 6 6"/><circle cx="9" cy="8.5" r="1.5"/></svg>
                        </span>
                        <div class="tool-sc-title">Hapus Background</div>
                        <div class="tool-sc-desc">Buat tanda tangan transparan Word, bersihkan logo kampus, &amp; ganti latar pas foto merah/biru.</div>
                        <a href="{{ auth()->check() ? route('tools.hub') . '#remove-bg' : route('login') }}" class="tool-sc-cta">Buka di Workspace →</a>
                    </div>

                    <div class="tool-showcase-card">
                        <span class="tool-sc-badge">Gratis</span>
                        <span class="tool-sc-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        </span>
                        <div class="tool-sc-title">JPG / PNG → PDF A4</div>
                        <div class="tool-sc-desc">Gabungkan foto berkas, surat izin, atau scan lembar pengesahan jadi dokumen PDF A4 rapi.</div>
                        <a href="{{ auth()->check() ? route('tools.hub') . '#img-pdf' : route('login') }}" class="tool-sc-cta">Buka di Workspace →</a>
                    </div>

                    <div class="tool-showcase-card">
                        <span class="tool-sc-badge">Tanpa Login</span>
                        <span class="tool-sc-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="6" x2="16" y2="6"/><line x1="8" y1="11" x2="10" y2="11"/><line x1="14" y1="11" x2="16" y2="11"/><line x1="8" y1="16" x2="10" y2="16"/><line x1="14" y1="16" x2="16" y2="16"/></svg>
                        </span>
                        <div class="tool-sc-title">Kalkulator Slovin &amp; Lemeshow</div>
                        <div class="tool-sc-desc">Hitung sampel minimal otomatis lengkap dengan langkah &amp; paragraf siap salin ke Bab III.</div>
                        <a href="{{ auth()->check() ? route('tools.hub') . '#slovin' : url('/kalkulator-slovin') }}" class="tool-sc-cta">Pakai Gratis Sekarang →</a>
                    </div>

                    <div class="tool-showcase-card">
                        <span class="tool-sc-badge">Tanpa Login</span>
                        <span class="tool-sc-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="14" y2="12"/><line x1="4" y1="18" x2="18" y2="18"/></svg>
                        </span>
                        <div class="tool-sc-title">Hitung Kata &amp; Cek Abstrak</div>
                        <div class="tool-sc-desc">Cek batas 150–250 kata abstrak, kata kunci tersering, &amp; estimasi halaman A4 dari .docx/.pdf.</div>
                        <a href="{{ auth()->check() ? route('tools.hub') . '#word-count' : url('/hitung-kata-abstrak') }}" class="tool-sc-cta">Pakai Gratis Sekarang →</a>
                    </div>
                </div>

                <div class="tools-showcase-footer">
                    <a href="{{ auth()->check() ? route('tools.hub') : route('register') }}" class="btn btn-primary">{{ auth()->check() ? 'Buka Semua 10 Tools Mahasiswa →' : 'Daftar Gratis & Gunakan Semua 10 Tools →' }}</a>
                </div>
            </div>
        </section>

        <!-- Section 4: Harga Paket Mahasiswa -->
        <section id="harga-paket" class="section section-subtle">
            <div class="container">
                <div style="text-align:center; max-width:700px; margin:0 auto 24px;">
                    <span class="section-label">Tanpa Kredit · Bayar Pakai Kuota atau Unlimited</span>
                    <h2 style="font-size: clamp(1.5rem, 2.8vw, 2.05rem); font-weight: 800; color: #0f172a; margin: 10px 0 8px;">
                        Harga Paling Ramah Mahasiswa Indonesia
                    </h2>
                    <p style="font-size: 14.5px; color: #475569; margin: 0;">
                        Boleh coba gratis 1 proyek dulu (Sampul, Bab I &amp; Daftar Pustaka + AI Tools 1x). Butuh naskah lengkap + Download Word/PDF? Mulai <strong>Rp15.000</strong> saja!
                    </p>
                </div>
                @include('partials.pricing_catalog', ['plans' => $plans ?? []])
            </div>
        </section>

        <!-- Section 5: FAQ Mahasiswa (SEO Rich Results + Bantuan Cepat) -->
        <section id="faq" class="section" aria-labelledby="faq-heading">
            <div class="container">
                <div class="section-header">
                    <span class="section-label">Pertanyaan Umum</span>
                    <h2 id="faq-heading" class="section-title">Hal yang Sering Ditanyakan Mahasiswa</h2>
                    <p class="section-desc">Jawaban ringkas seputar penggunaan SkripsiLab AI, keamanan naskah, referensi jurnal ber-DOI, auto format Word/PDF, hingga dukungan tim kami.</p>
                </div>

                <div class="faq-list">
                    <details class="faq-item">
                        <summary>Apa itu SkripsiLab AI?</summary>
                        <div class="faq-answer">
                            SkripsiLab AI adalah workspace penulisan akademik berbasis AI untuk membantu mahasiswa Indonesia menyusun proposal skripsi (Bab I–III) dan skripsi penuh (Bab I–V) secara terstruktur, lengkap dengan pencarian jurnal terverifikasi DOI, parafrase akademik, olah data, PPT sidang, dan simulasi sidang.
                        </div>
                    </details>

                    <details class="faq-item">
                        <summary>Apakah boleh pakai AI untuk skripsi?</summary>
                        <div class="faq-answer">
                            Boleh, selama AI digunakan sebagai asisten riset, penyusunan kerangka, perapian bahasa ilmiah, dan eksplorasi referensi—bukan untuk menyalin mentah tanpa memahami isi. Mahasiswa tetap memegang kendali penuh untuk memeriksa, menyunting, dan mempertanggungjawabkan naskah kepada dosen pembimbing.
                        </div>
                    </details>

                    <details class="faq-item">
                        <summary>Apakah SkripsiLab AI termasuk joki skripsi?</summary>
                        <div class="faq-answer">
                            Bukan. SkripsiLab AI adalah alat bantu dan workspace produktivitas mandiri, bukan layanan joki. Kamu sendiri yang menentukan judul, metodologi, data penelitian, sitasi, serta melakukan revisi sesuai arahan dosen pembimbing.
                        </div>
                    </details>

                    <details class="faq-item">
                        <summary>Apakah kampus bisa mendeteksi penggunaan AI?</summary>
                        <div class="faq-answer">
                            Pemeriksaan di kampus umumnya menggunakan detektor kemiripan teks (seperti Turnitin) dan evaluasi pemahaman mahasiswa saat bimbingan atau sidang. Untuk meminimalkan tulisan yang terasa kaku atau generik, SkripsiLab AI menyediakan fitur <strong>Parafrase Akademik</strong> sesuai EYD V, sitasi jurnal asli ber-DOI, serta integrasi data riset milikmu sendiri.
                        </div>
                    </details>

                    <details class="faq-item">
                        <summary>Bagaimana cara pakai SkripsiLab AI yang aman dan tetap karya sendiri?</summary>
                        <div class="faq-answer">
                            Gunakan secara bertahap: tentukan judul dan rumusan masalah sendiri, unggah pedoman kampus, pilih jurnal terverifikasi DOI sebagai rujukan, masukkan data penelitian riil (kuesioner, observasi, atau hasil SPSS/SmartPLS) lewat <strong>Lab Revisi &amp; Olah Data</strong>, lalu baca ulang dan sesuaikan tiap sub-bab sebelum diajukan ke pembimbing.
                        </div>
                    </details>

                    <details class="faq-item">
                        <summary>Fitur apa saja yang ada di SkripsiLab AI?</summary>
                        <div class="faq-answer">
                            Tersedia 9 modul utama dalam satu dashboard: <strong>Workspace Proposal &amp; Skripsi (Bab I–V)</strong>, <strong>Cari Judul (9 metode)</strong>, <strong>Cari Jurnal &amp; Referensi DOI (Crossref &amp; OpenAlex)</strong>, <strong>Parafrase Akademik</strong>, <strong>Dosen AI</strong>, <strong>Lab Revisi &amp; Olah Data</strong>, <strong>Lampiran Otomatis</strong>, <strong>Generate PPT Sidang (13 tema .pptx)</strong>, dan <strong>Simulasi Sidang</strong>, ditambah <strong>10 Tools Mahasiswa gratis</strong>.
                        </div>
                    </details>

                    <details class="faq-item">
                        <summary>Untuk siapa SkripsiLab AI?</summary>
                        <div class="faq-answer">
                            SkripsiLab AI dirancang untuk mahasiswa D3, S1, maupun S2 dari seluruh program studi (Informatika, Manajemen, Akuntansi, Ilmu Hukum, Pendidikan, Kedokteran, Keperawatan, Teknik, Psikologi, Komunikasi, dan lainnya) dengan dukungan <strong>9 metode penelitian</strong>: Kuantitatif Survei/Regresi, Data Sekunder, Kualitatif, R&amp;D, PTK, Studi Pustaka, Mixed Method, Hukum Normatif, dan Hukum Empiris.
                        </div>
                    </details>

                    <details class="faq-item">
                        <summary>Apakah SkripsiLab AI gratis?</summary>
                        <div class="faq-answer">
                            Ya, kamu bisa mencoba <strong>gratis 1 proyek</strong> (Sampul, Bab I &amp; Daftar Pustaka + AI Tools 1x) serta menggunakan <strong>10 Tools Mahasiswa</strong> secara gratis di dalam workspace. Untuk naskah lengkap Bab I–V dan ekspor Word/PDF penuh, tersedia paket ramah mahasiswa mulai <strong>Rp15.000</strong> tanpa sistem kredit.
                        </div>
                    </details>

                    <details class="faq-item">
                        <summary>Bagaimana cara kerja auto format skripsi (Word &amp; PDF)?</summary>
                        <div class="faq-answer">
                            Saat mengekspor ke Word (<code>.docx</code>) atau PDF (<code>.pdf</code>), sistem otomatis menata kertas A4, margin <strong>4-4-3-3 cm</strong>, halaman sampul dengan logo kampus, lembar pengesahan, abstrak, kata pengantar, Daftar Isi, Daftar Tabel, Daftar Gambar, Daftar Lampiran, penomoran halaman Romawi &amp; Arab di tengah bawah, serta Daftar Pustaka (APA 7th, Harvard, IEEE, Chicago, atau Vancouver).
                        </div>
                    </details>

                    <details class="faq-item">
                        <summary>Apakah data dan naskah saya aman?</summary>
                        <div class="faq-answer">
                            Aman. Seluruh data akun, proyek, dan draf naskahmu bersifat privat untuk akunmu sendiri, dilindungi enkripsi HTTPS/TLS serta autentikasi aman, dan tidak pernah dibagikan atau dijual ke pihak ketiga.
                        </div>
                    </details>

                    <details class="faq-item">
                        <summary>Apakah SkripsiLab AI bisa dipakai di HP?</summary>
                        <div class="faq-answer">
                            Bisa. Seluruh antarmuka SkripsiLab AI—mulai dari membuat proyek, menyusun bab, mempratinjau dokumen A4, memakai 10 Tools Mahasiswa, hingga mengunduh file Word, PDF, dan PPT—responsif dan dapat diakses langsung melalui browser HP maupun laptop.
                        </div>
                    </details>

                    <details class="faq-item">
                        <summary>Bagaimana cara menghubungi support?</summary>
                        <div class="faq-answer">
                            Kamu bisa langsung menghubungi tim support lewat tombol <strong>Butuh Bantuan?</strong> di pojok kanan bawah (Telepon/WhatsApp), melalui menu Bantuan di dalam dashboard workspace, atau lewat Instagram &amp; TikTok resmi <strong>@skripsilabai</strong>.
                        </div>
                    </details>
                </div>

                <div class="cta-banner" style="margin-top: 44px;">
                    <h2>Susun Proposal &amp; Skripsi Lebih Cepat</h2>
                    <p>Otomatis menggunakan AI dengan referensi ilmiah &amp; jurnal yang relevan ber-DOI dalam satu
                        workspace.</p>
                    <div class="hero-cta">
                        @auth
                            <a href="{{ route('app.create', ['tab' => 'projects']) }}" class="btn btn-primary">Buka
                                Workspace Saya →</a>
                        @else
                            <a href="{{ route('register') }}" class="btn btn-primary" data-meta-lead="CTA Banner Mulai Gratis">Mulai Gratis →</a>
                            <a href="{{ route('login') }}" class="btn btn-outline">Masuk</a>
                        @endauth
                    </div>
                </div>
            </div>
        </section>
    </main>

    @include('partials.public_footer')

    <!-- Modal Preview PDF -->
    <dialog id="pdf-preview-dialog" class="pdf-dialog">
        <div class="pdf-dialog-head">
            <div class="pdf-dialog-title" id="pdf-dialog-title">Pratinjau Dokumen PDF</div>
            <div style="display: inline-flex; align-items: center; gap: 6px; flex-shrink: 0;">
                <a id="pdf-dialog-newtab" href="#" target="_blank" rel="noopener" class="sample-btn">Buka Tab Baru ↗</a>
                <button type="button" class="sample-btn" onclick="closePdfModal()">✕ Tutup</button>
            </div>
        </div>
        <iframe id="pdf-dialog-frame" class="pdf-dialog-frame" title="Pratinjau PDF Skripsi"></iframe>
    </dialog>

    <script>
        function toggleLandingMobileNav() {
            const drawer = document.getElementById('landing-mobile-drawer');
            const btn = document.getElementById('landing-mobile-toggle');
            if (!drawer || !btn) return;
            const isOpen = drawer.classList.toggle('open');
            btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        }
        function closeLandingMobileNav() {
            const drawer = document.getElementById('landing-mobile-drawer');
            const btn = document.getElementById('landing-mobile-toggle');
            if (drawer) drawer.classList.remove('open');
            if (btn) btn.setAttribute('aria-expanded', 'false');
        }

        function filterGallery(stage, btn) {
            document.querySelectorAll('#gallery-filter-bar .major-pill').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            let visibleCount = 0;
            const cards = document.querySelectorAll('#sample-cards-grid .sample-card');
            cards.forEach(card => {
                const show = stage === 'all' || card.dataset.stage === stage;
                card.style.display = show ? 'flex' : 'none';
                if (show) visibleCount++;
            });
            const emptyEl = document.getElementById('gallery-empty-filter');
            if (emptyEl) {
                emptyEl.style.display = (cards.length > 0 && visibleCount === 0) ? 'block' : 'none';
            }
        }

        function openPdfModal(title, url) {
            const dialog = document.getElementById('pdf-preview-dialog');
            const newTabEl = document.getElementById('pdf-dialog-newtab');
            document.getElementById('pdf-dialog-title').textContent = title || 'Pratinjau Dokumen PDF';
            document.getElementById('pdf-dialog-frame').src = url;
            if (newTabEl) newTabEl.href = url;
            if (typeof dialog.showModal === 'function') {
                dialog.showModal();
            } else {
                window.open(url, '_blank');
            }
        }

        function closePdfModal() {
            const dialog = document.getElementById('pdf-preview-dialog');
            document.getElementById('pdf-dialog-frame').src = 'about:blank';
            dialog.close();
        }

        const demoTopikEl = document.getElementById('demo-topik');
        if (demoTopikEl) {
            demoTopikEl.setAttribute('aria-invalid', 'false');
            demoTopikEl.addEventListener('blur', () => {
                if (demoTopikEl.value.trim().length > 0 && !demoTopikEl.checkValidity()) {
                    demoTopikEl.classList.add('user-invalid-fallback');
                    demoTopikEl.setAttribute('aria-invalid', 'true');
                }
            });
            demoTopikEl.addEventListener('input', () => {
                if (demoTopikEl.checkValidity()) {
                    demoTopikEl.classList.remove('user-invalid-fallback');
                    demoTopikEl.setAttribute('aria-invalid', 'false');
                }
            });
        }

        const demoCopyTimers = new WeakMap();
        function legacyCopyText(text) {
            const prev = document.activeElement;
            const field = Object.assign(document.createElement('textarea'), { value: text });
            field.setAttribute('readonly', '');
            field.style.cssText = 'position:fixed;inset-block-start:0;opacity:0';
            document.body.append(field);
            field.select();
            try { return document.execCommand('copy'); }
            finally { field.remove(); prev?.focus({ preventScroll: true }); }
        }
        function showCopyFeedback(btn, ok) {
            const tip = btn.nextElementSibling;
            if (!tip) return;
            clearTimeout(demoCopyTimers.get(btn));
            btn.removeAttribute('data-copied');
            btn.removeAttribute('data-failed');
            void btn.offsetWidth;
            btn.setAttribute(ok ? 'data-copied' : 'data-failed', '');
            requestAnimationFrame(() => { tip.textContent = ok ? 'Tersalin!' : 'Gagal salin'; });
            demoCopyTimers.set(btn, setTimeout(() => {
                btn.removeAttribute('data-copied');
                btn.removeAttribute('data-failed');
                tip.textContent = '';
            }, 1800));
        }
        async function handleDemoCopy(btn, target) {
            const text = target.innerText.trim();
            if (!text) return;
            try {
                await navigator.clipboard.writeText(text);
                showCopyFeedback(btn, true);
            } catch {
                showCopyFeedback(btn, legacyCopyText(text));
            }
        }
        if ('CommandEvent' in window) {
            document.addEventListener('command', (ev) => {
                if (ev.command && ev.command.startsWith('--copy') && ev.source && ev.target) {
                    handleDemoCopy(ev.source, ev.target);
                }
            }, true);
        } else {
            document.addEventListener('click', (ev) => {
                const btn = ev.target.closest('button[command^="--copy"]');
                if (!btn) return;
                const target = document.getElementById(btn.getAttribute('commandfor'));
                if (target) handleDemoCopy(btn, target);
            });
        }

        async function submitDemoTitles(ev) {
            ev.preventDefault();
            const form = ev.currentTarget;
            const topikInput = document.getElementById('demo-topik');
            const prodiInput = document.getElementById('demo-prodi');
            const metodeSelect = document.getElementById('demo-metode');
            const statusLive = document.getElementById('demo-status-live');
            const submitBtn = document.getElementById('demo-submit-btn');
            const resBox = document.getElementById('demo-results-box');
            const listBox = document.getElementById('demo-titles-list');
            const rumusanBox = document.getElementById('demo-rumusan-card');

            if (!topikInput.checkValidity()) {
                topikInput.classList.add('user-invalid-fallback');
                topikInput.setAttribute('aria-invalid', 'true');
                topikInput.focus();
                return;
            }

            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const payload = {
                topik: topikInput.value.trim(),
                prodi: (prodiInput?.value || '').trim(),
                metode: metodeSelect?.value || 'Kuantitatif (Survei / Regresi)',
                website_hp: form.querySelector('input[name="website_hp"]')?.value || ''
            };

            submitBtn.disabled = true;
            submitBtn.textContent = 'Merumuskan Judul...';
            if (statusLive) statusLive.textContent = 'Sedang menyusun 3 rekomendasi judul dan rumusan masalah...';

            try {
                const res = await fetch(@json(route('demo.titles')), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (!res.ok || data.status !== 'success') {
                    throw new Error(data.message || 'Gagal memproses permintaan.');
                }

                const titles = Array.isArray(data.titles) ? data.titles : [];
                rumusanBox.innerHTML = '';
                const rumLabel = document.createElement('strong');
                rumLabel.textContent = 'Contoh Rumusan Masalah: ';
                const rumText = document.createElement('span');
                rumText.textContent = data.rumusan || '';
                rumusanBox.appendChild(rumLabel);
                rumusanBox.appendChild(rumText);

                listBox.innerHTML = '';
                const baseStartUrl = @json(auth()->check() ? route('app.create', ['tab' => 'projects', 'create' => 1]) : route('register'));
                titles.forEach((title, idx) => {
                    const card = document.createElement('div');
                    card.className = 'demo-result-card';

                    const numBadge = document.createElement('span');
                    numBadge.style.cssText = 'width:26px;height:26px;border-radius:999px;background:#dbeafe;color:#1d4ed8;font-size:12px;font-weight:800;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;';
                    numBadge.textContent = String(idx + 1);

                    const titleSpan = document.createElement('div');
                    titleSpan.className = 'demo-result-title';
                    titleSpan.id = `demo-gen-title-${idx}`;
                    titleSpan.textContent = title;

                    const actions = document.createElement('div');
                    actions.className = 'demo-result-actions';

                    const copyBtn = document.createElement('button');
                    copyBtn.type = 'button';
                    copyBtn.className = 'btn btn-outline';
                    copyBtn.style.minHeight = '36px';
                    copyBtn.style.padding = '6px 12px';
                    copyBtn.setAttribute('command', '--copy');
                    copyBtn.setAttribute('commandfor', titleSpan.id);
                    copyBtn.textContent = 'Salin';

                    const tip = document.createElement('span');
                    tip.className = 'copy-tip';
                    tip.setAttribute('role', 'status');

                    const qs = new URLSearchParams({
                        prefill_judul: title,
                        prefill_prodi: data.prodi || payload.prodi || '',
                        prefill_metode: data.metode || payload.metode || ''
                    });
                    const sep = baseStartUrl.includes('?') ? '&' : '?';
                    const cta = document.createElement('a');
                    cta.href = `${baseStartUrl}${sep}${qs.toString()}`;
                    cta.className = 'btn btn-primary';
                    cta.style.minHeight = '36px';
                    cta.style.padding = '6px 14px';
                    cta.setAttribute('data-meta-lead', 'Widget Coba Judul Pakai');
                    cta.textContent = 'Susun Bab I–V Judul Ini →';

                    actions.appendChild(copyBtn);
                    actions.appendChild(tip);
                    actions.appendChild(cta);
                    card.appendChild(numBadge);
                    card.appendChild(titleSpan);
                    card.appendChild(actions);
                    listBox.appendChild(card);
                });

                resBox.style.display = 'block';
                if (statusLive) statusLive.textContent = `${titles.length} ide judul skripsi siap dipilih.`;
                if (window.slTrackMetaEvent) {
                    window.slTrackMetaEvent('Lead', { content_name: 'Demo Generate Judul Landing' });
                }
            } catch (err) {
                if (statusLive) statusLive.textContent = err.message || 'Terjadi kendala saat membuat ide judul.';
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Buat 3 Ide Judul →';
            }
        }
    </script>
    @auth
        @include('partials.upgrade_checkout_modal', ['plans' => $plans ?? []])
    @endauth
</body>

</html>