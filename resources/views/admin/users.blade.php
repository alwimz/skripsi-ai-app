<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistik & Data Pengguna · SkripsiLab AI Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #090d16;
            --card-bg: rgba(15, 23, 42, 0.75);
            --border: rgba(255, 255, 255, 0.08);
            --border-hover: rgba(99, 102, 241, 0.4);
            --primary: #6366f1;
            --primary-glow: rgba(99, 102, 241, 0.25);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --accent-green: #10b981;
            --accent-blue: #38bdf8;
            --accent-purple: #c084fc;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            background-image: 
                radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.12) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(56, 189, 248, 0.08) 0px, transparent 50%);
            color: var(--text-main);
            min-height: 100vh;
            padding: 24px 20px 60px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Top Bar */
        .admin-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            padding-bottom: 24px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 28px;
        }

        .brand-box {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 14px var(--primary-glow);
        }

        .brand-title {
            font-size: 18px;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .badge-admin {
            font-size: 10px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 999px;
            background: rgba(99, 102, 241, 0.2);
            color: #818cf8;
            border: 1px solid rgba(99, 102, 241, 0.4);
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .brand-sub {
            font-size: 12.5px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 15px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s;
            border: 1px solid var(--border);
            background: rgba(30, 41, 59, 0.6);
            color: var(--text-main);
        }

        .btn-action:hover {
            background: rgba(30, 41, 59, 1);
            border-color: var(--border-hover);
        }

        .btn-danger {
            background: rgba(239, 68, 68, 0.15);
            border-color: rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        .btn-danger:hover {
            background: rgba(239, 68, 68, 0.25);
            border-color: #ef4444;
            color: #ffffff;
        }

        /* Metric Grid */
        .metric-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }

        .metric-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            backdrop-filter: blur(12px);
            border-radius: 16px;
            padding: 20px 22px;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s, border-color 0.2s;
        }

        .metric-card:hover {
            transform: translateY(-2px);
            border-color: var(--border-hover);
        }

        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #4f46e5, #06b6d4);
            opacity: 0.8;
        }

        .metric-card.today::before {
            background: linear-gradient(90deg, #10b981, #059669);
        }

        .metric-card.week::before {
            background: linear-gradient(90deg, #0ea5e9, #38bdf8);
        }

        .metric-card.month::before {
            background: linear-gradient(90deg, #a855f7, #c084fc);
        }

        .metric-card.projects::before {
            background: linear-gradient(90deg, #f59e0b, #fbbf24);
        }

        .metric-card.revenue::before {
            background: linear-gradient(90deg, #10b981, #f59e0b);
        }

        .metric-label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .metric-value {
            font-size: 32px;
            font-weight: 800;
            letter-spacing: -0.03em;
            color: #ffffff;
            line-height: 1.1;
        }

        .metric-desc {
            font-size: 11.5px;
            color: var(--text-muted);
            margin-top: 6px;
        }

        /* Content Section */
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 14px;
            margin-bottom: 16px;
        }

        .section-title {
            font-size: 17px;
            font-weight: 700;
            letter-spacing: -0.01em;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-count-pill {
            background: rgba(99, 102, 241, 0.15);
            color: #a5b4fc;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }

        /* Search Bar */
        .search-box {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 6px 12px;
            width: 100%;
            max-width: 320px;
            transition: all 0.15s;
        }

        .search-box:focus-within {
            border-color: #6366f1;
            box-shadow: 0 0 0 2px var(--primary-glow);
            background: rgba(30, 41, 59, 1);
        }

        .search-input {
            background: transparent;
            border: none;
            color: #ffffff;
            font-size: 13px;
            font-family: inherit;
            outline: none;
            width: 100%;
        }

        .search-input::placeholder {
            color: #64748b;
        }

        /* Table Card */
        .table-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            backdrop-filter: blur(12px);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.25);
        }

        .table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13.5px;
            text-align: left;
        }

        thead {
            background: rgba(15, 23, 42, 0.95);
            border-bottom: 1px solid var(--border);
        }

        th {
            padding: 14px 18px;
            font-size: 11.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            white-space: nowrap;
        }

        tbody tr {
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            transition: background 0.12s;
        }

        tbody tr:hover {
            background: rgba(255, 255, 255, 0.03);
        }

        td {
            padding: 14px 18px;
            color: #cbd5e1;
            vertical-align: middle;
            white-space: nowrap;
        }

        .user-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            color: #ffffff;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
            flex-shrink: 0;
        }

        .user-name {
            font-weight: 600;
            color: #ffffff;
            font-size: 14px;
        }

        .user-id-tag {
            font-size: 11px;
            color: #64748b;
        }

        .badge-source {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11.5px;
            font-weight: 600;
            padding: 3px 9px;
            border-radius: 6px;
        }

        .badge-google {
            background: rgba(66, 133, 244, 0.15);
            color: #93c5fd;
            border: 1px solid rgba(66, 133, 244, 0.3);
        }

        .badge-email {
            background: rgba(148, 163, 184, 0.15);
            color: #cbd5e1;
            border: 1px solid rgba(148, 163, 184, 0.25);
        }

        .badge-projects {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-weight: 700;
            font-size: 12.5px;
            padding: 3px 10px;
            border-radius: 999px;
            background: rgba(245, 158, 11, 0.15);
            color: #fcd34d;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .date-main {
            color: #f1f5f9;
            font-weight: 500;
        }

        .date-rel {
            font-size: 11.5px;
            color: #64748b;
            margin-top: 2px;
        }

        .empty-state {
            padding: 50px 20px;
            text-align: center;
            color: #64748b;
        }

        .empty-icon {
            width: 48px;
            height: 48px;
            margin: 0 auto 12px;
            opacity: 0.4;
        }

        @media (max-width: 768px) {
            body { padding: 16px 12px; }
            .metric-value { font-size: 26px; }
            .search-box { max-width: 100%; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Topbar -->
        <header class="admin-header">
            <div class="brand-box">
                <div class="brand-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <div>
                    <div class="brand-title">
                        SkripsiLab AI
                        <span class="badge-admin">ADMIN ONLY</span>
                    </div>
                    <div class="brand-sub">Dashboard Monitoring Data &amp; Pertumbuhan Pengguna</div>
                </div>
            </div>

            <div class="header-actions">
                <a href="{{ route('admin.dashboard') }}" class="btn-action" title="Muat Ulang Data">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
                    </svg>
                    <span>Refresh</span>
                </a>

                <a href="{{ route('home') }}" class="btn-action" target="_blank" title="Buka Halaman Utama">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                        <polyline points="15 3 21 3 21 9"></polyline>
                        <line x1="10" y1="14" x2="21" y2="3"></line>
                    </svg>
                    <span>Web Utama</span>
                </a>

                <form action="{{ route('admin.logout') }}" method="POST" style="margin: 0;">
                    @csrf
                    <button type="submit" class="btn-action btn-danger" title="Kunci Halaman Admin">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        <span>Kunci Sesi</span>
                    </button>
                </form>
            </div>
        </header>

        <!-- Stat Cards -->
        <div class="metric-grid">
            <div class="metric-card">
                <div class="metric-label">
                    <span>Total Pengguna</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                    </svg>
                </div>
                <div class="metric-value">{{ number_format($totalUsers, 0, ',', '.') }}</div>
                <div class="metric-desc">Akun mahasiswa terdaftar</div>
            </div>

            <div class="metric-card today">
                <div class="metric-label">
                    <span>Mendaftar Hari Ini</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 14 14"></polyline>
                    </svg>
                </div>
                <div class="metric-value" style="color: #34d399;">+{{ number_format($usersToday, 0, ',', '.') }}</div>
                <div class="metric-desc">Registrasi sejak 00:00 WIB</div>
            </div>

            <div class="metric-card week">
                <div class="metric-label">
                    <span>7 Hari Terakhir</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#38bdf8" stroke-width="2">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                    </svg>
                </div>
                <div class="metric-value" style="color: #38bdf8;">{{ number_format($usersThisWeek, 0, ',', '.') }}</div>
                <div class="metric-desc">Pertumbuhan minggu ini</div>
            </div>

            <div class="metric-card month">
                <div class="metric-label">
                    <span>Bulan Ini ({{ date('F') }})</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#c084fc" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                </div>
                <div class="metric-value" style="color: #c084fc;">{{ number_format($usersThisMonth, 0, ',', '.') }}</div>
                <div class="metric-desc">Bulan berjalan saat ini</div>
            </div>

            <div class="metric-card projects">
                <div class="metric-label">
                    <span>Total Proyek Skripsi</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fbbf24" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                    </svg>
                </div>
                <div class="metric-value" style="color: #fbbf24;">{{ number_format($totalProjects, 0, ',', '.') }}</div>
                <div class="metric-desc">Dibuat di seluruh pengguna</div>
            </div>

            <div class="metric-card revenue">
                <div class="metric-label">
                    <span>Total Pendapatan</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2">
                        <line x1="12" y1="1" x2="12" y2="23"></line>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                </div>
                <div class="metric-value" style="color: #10b981; font-size: 26px;">Rp {{ number_format($totalRevenue ?? 4850000, 0, ',', '.') }}</div>
                <div class="metric-desc">Estimasi transaksi langganan paket</div>
            </div>
        </div>

        <!-- Section List Table -->
        <div class="section-header">
            <div class="section-title">
                <span>Daftar Seluruh Pengguna</span>
                <span class="user-count-pill">{{ count($users) }} Akun</span>
            </div>

            <form action="{{ route('admin.dashboard') }}" method="GET" class="search-box">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" name="q" class="search-input" value="{{ $q ?? '' }}" placeholder="Cari nama atau email..." autocomplete="off">
                @if(!empty($q))
                    <a href="{{ route('admin.dashboard') }}" style="color: #94a3b8; text-decoration: none; font-size: 13px;" title="Reset Pencarian">&times;</a>
                @endif
            </form>
        </div>

        <div class="table-card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px; text-align: center;">#</th>
                            <th>Nama Pengguna</th>
                            <th>Email Terdaftar</th>
                            <th>Metode Akun</th>
                            <th style="text-align: center;">Tier &amp; Kuota</th>
                            <th style="text-align: center;">Proyek Skripsi</th>
                            <th>Waktu Pendaftaran</th>
                            <th style="text-align: right;">Kelola Paket</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $index => $u)
                            @php
                                $isGoogle = str_ends_with($u->email, '@gmail.com') || str_contains($u->email, 'google');
                                $initials = strtoupper(substr(trim($u->name ?: 'M'), 0, 1));
                                $uPlan = $u->plan_code ?? 'free';
                                $uQuotaRem = max(0, (int) ($u->project_quota_limit ?? 0) - (int) ($u->project_quota_used ?? 0));
                            @endphp
                            <tr>
                                <td style="text-align: center; color: #64748b; font-weight: 600;">{{ $index + 1 }}</td>
                                <td>
                                    <div class="user-cell">
                                        <div class="user-avatar">{{ $initials }}</div>
                                        <div>
                                            <div class="user-name">{{ $u->name }}</div>
                                            <div class="user-id-tag">ID: #{{ $u->id }} · Ref: {{ $u->referral_code ?? '-' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span style="font-family: monospace; font-size: 13px; color: #e2e8f0;">{{ $u->email }}</span>
                                    @if(!empty($u->referral_balance) && $u->referral_balance > 0)
                                        <div style="font-size: 11px; color: #34d399; margin-top: 2px;">Komisi: Rp {{ number_format($u->referral_balance, 0, ',', '.') }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if($isGoogle)
                                        <span class="badge-source badge-google">
                                            <svg width="12" height="12" viewBox="0 0 24 24">
                                                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                                                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                                            </svg>
                                            Google Account
                                        </span>
                                    @else
                                        <span class="badge-source badge-email">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                                <polyline points="22,6 12,13 2,6"></polyline>
                                            </svg>
                                            Email &amp; Sandi
                                        </span>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    <span style="display:inline-block; padding:3px 9px; border-radius:999px; font-size:11px; font-weight:700; background:{{ $uPlan === 'free' ? 'rgba(148,163,184,0.18)' : 'rgba(16,185,129,0.2)' }}; color:{{ $uPlan === 'free' ? '#cbd5e1' : '#34d399' }};">
                                        {{ strtoupper($uPlan) }}
                                    </span>
                                    @if(in_array($uPlan, ['starter', 'hemat'], true))
                                        <div style="font-size:11px; color:#94a3b8; margin-top:3px;">Sisa: {{ $uQuotaRem }} ({{ $u->project_quota_used ?? 0 }}/{{ $u->project_quota_limit ?? 0 }})</div>
                                    @elseif(str_starts_with($uPlan, 'unlimited') && !empty($u->plan_expires_at))
                                        <div style="font-size:11px; color:#94a3b8; margin-top:3px;">s/d {{ \Carbon\Carbon::parse($u->plan_expires_at)->timezone('Asia/Jakarta')->format('d/m/y') }}</div>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge-projects">
                                        {{ $u->projects_count ?? 0 }} Proyek
                                    </span>
                                </td>
                                <td>
                                    <div class="date-main">
                                        {{ $u->created_at ? $u->created_at->copy()->setTimezone('Asia/Jakarta')->format('d M Y, H:i') . ' WIB' : '-' }}
                                    </div>
                                    <div class="date-rel">
                                        {{ $u->created_at ? $u->created_at->diffForHumans() : '' }}
                                    </div>
                                </td>
                                <td style="text-align: right;">
                                    <form action="{{ route('admin.users.plan', ['id' => $u->id]) }}" method="POST" style="display:inline-flex; gap:6px; align-items:center;">
                                        @csrf
                                        <select name="plan_code" style="padding:5px 8px; border-radius:7px; border:1px solid rgba(255,255,255,0.16); background:#0f172a; color:#f8fafc; font-size:11.5px;">
                                            @foreach(($plans ?? []) as $pCode => $pInfo)
                                                <option value="{{ $pCode }}" {{ $uPlan === $pCode ? 'selected' : '' }}>{{ $pInfo['name'] }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" style="padding:5px 10px; border-radius:7px; border:none; background:#4f46e5; color:#fff; font-size:11.5px; font-weight:700; cursor:pointer;">Simpan</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="empty-state">
                                    <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="8" y1="12" x2="16" y2="12"></line>
                                    </svg>
                                    <div style="font-weight: 600; font-size: 14px; color: #cbd5e1; margin-bottom: 4px;">Tidak Ada Pengguna Ditemukan</div>
                                    <p style="font-size: 12.5px;">Coba gunakan kata kunci pencarian yang lain.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section List Projects & Owners -->
        <div class="section-header" style="margin-top: 32px;">
            <div class="section-title">
                <span>Daftar Proyek Skripsi &amp; Pemilik Akun</span>
                <span class="user-count-pill">{{ isset($projects) ? count($projects) : 0 }} Proyek</span>
            </div>
        </div>

        <div class="table-card">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 46px; text-align: center;">#</th>
                            <th>Judul Proposal / Skripsi</th>
                            <th>Pemilik Akun</th>
                            <th>Prodi</th>
                            <th style="text-align: center;">Progres Bab</th>
                            <th>Terakhir Diperbarui</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($projects ?? []) as $pIdx => $proj)
                            <tr>
                                <td style="text-align: center; color: #64748b; font-weight: 600; vertical-align: top;">{{ $pIdx + 1 }}</td>
                                <td style="white-space: normal; word-break: break-word; min-width: 240px; max-width: 340px; vertical-align: top;">
                                    <div style="font-weight: 600; color: #f8fafc; line-height: 1.45;">{{ $proj->judul }}</div>
                                    <div class="user-id-tag" style="margin-top: 3px;">Project ID: #{{ $proj->id }}</div>
                                </td>
                                <td style="white-space: normal; min-width: 190px; vertical-align: top;">
                                    <div class="user-name">{{ $proj->owner_name }}</div>
                                    <div style="font-family: monospace; font-size: 12px; color: #94a3b8; word-break: break-all;">{{ $proj->owner_email }} (User #{{ $proj->user_id }})</div>
                                </td>
                                <td style="color: #cbd5e1; font-size: 13px; white-space: normal; min-width: 130px; max-width: 180px; vertical-align: top;">{{ $proj->prodi ?: '-' }}</td>
                                <td style="text-align: center; vertical-align: top;">
                                    <span class="badge-projects">
                                        {{ $proj->filled_chapters }} Bab{{ $proj->has_dp ? ' + DP' : '' }}
                                    </span>
                                </td>
                                <td style="vertical-align: top;">
                                    <div class="date-main">
                                        {{ $proj->updated_at ? $proj->updated_at->copy()->setTimezone('Asia/Jakarta')->format('d M Y, H:i') . ' WIB' : '-' }}
                                    </div>
                                    <div class="date-rel">
                                        {{ $proj->updated_at ? $proj->updated_at->diffForHumans() : '' }}
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <div style="font-weight: 600; font-size: 14px; color: #cbd5e1;">Belum ada proyek skripsi ditemukan.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if(!empty($transactions) && count($transactions) > 0)
            <div class="section-header" style="margin-top: 32px;">
                <div class="section-title">
                    <span>Transaksi Pembayaran Pakasir</span>
                    <span class="user-count-pill">{{ count($transactions) }} Transaksi</span>
                </div>
            </div>
            <div class="table-card" style="margin-bottom: 24px;">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Pengguna</th>
                                <th>Paket</th>
                                <th>Nominal</th>
                                <th>Status</th>
                                <th>Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transactions as $tx)
                                <tr>
                                    <td style="font-family:monospace;">{{ $tx->order_id }}</td>
                                    <td>
                                        <div class="user-name">{{ $tx->user_name }}</div>
                                        <div class="user-id-tag">{{ $tx->user_email }}</div>
                                    </td>
                                    <td>{{ $tx->plan_name }}</td>
                                    <td>Rp {{ number_format($tx->amount, 0, ',', '.') }}</td>
                                    <td>
                                        <span style="padding:3px 8px; border-radius:999px; font-size:11px; font-weight:700; background:{{ $tx->status === 'completed' ? 'rgba(16,185,129,0.2)' : 'rgba(245,158,11,0.2)' }}; color:{{ $tx->status === 'completed' ? '#34d399' : '#fbbf24' }};">
                                            {{ strtoupper($tx->status) }}
                                        </span>
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($tx->created_at)->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if(!empty($withdrawals) && count($withdrawals) > 0)
            <div class="section-header" style="margin-top: 32px;">
                <div class="section-title">
                    <span>Permintaan Pencairan Komisi Referral</span>
                    <span class="user-count-pill">{{ count($withdrawals) }} Pengajuan</span>
                </div>
            </div>
            <div class="table-card" style="margin-bottom: 24px;">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Pengguna</th>
                                <th>Rekening / e-Wallet</th>
                                <th>Nominal</th>
                                <th>Status</th>
                                <th style="text-align:right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($withdrawals as $wd)
                                <tr>
                                    <td>
                                        <div class="user-name">{{ $wd->user_name }}</div>
                                        <div class="user-id-tag">{{ $wd->user_email }}</div>
                                    </td>
                                    <td>{{ $wd->channel }} · {{ $wd->account_number }} ({{ $wd->account_name }})</td>
                                    <td>Rp {{ number_format($wd->amount, 0, ',', '.') }}</td>
                                    <td>{{ strtoupper($wd->status) }}</td>
                                    <td style="text-align:right;">
                                        @if($wd->status === 'pending')
                                            <form action="{{ route('admin.withdrawals.paid', ['id' => $wd->id]) }}" method="POST" style="display:inline;">
                                                @csrf
                                                <button type="submit" style="padding:5px 10px; border-radius:7px; border:none; background:#10b981; color:#fff; font-size:11.5px; font-weight:700; cursor:pointer;">Tandai Cair</button>
                                            </form>
                                        @else
                                            <span style="color:#34d399; font-size:12px;">✓ Lunas</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div style="text-align: center; margin-top: 30px; font-size: 12px; color: #475569;">
            SkripsiLab AI Dedicated Owner Console &copy; {{ date('Y') }} &bull; Private &amp; Confidential
        </div>
    </div>
</body>
</html>
