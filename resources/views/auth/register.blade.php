<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun — SkripsiLab AI | Susun Proposal &amp; Skripsi Otomatis</title>
    <meta name="description" content="Daftar akun SkripsiLab AI gratis untuk menyusun proposal Bab 1–3 &amp; skripsi Bab 1–5 otomatis menggunakan AI dengan referensi ilmiah, jurnal yang relevan ber-DOI, PPT sidang 13 tema, dan simulasi sidang.">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url('/register') }}">
    <meta name="theme-color" content="#2563eb">
    <link rel="icon" type="image/png" href="/images/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --ease-out: cubic-bezier(0.22, 1, 0.36, 1);
        }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: radial-gradient(circle at top center, #eff6ff 0%, #f8fafc 58%, #f1f5f9 100%);
            color: var(--text-main);
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 18px 14px;
            line-height: 1.45;
            -webkit-font-smoothing: antialiased;
        }
        .auth-card {
            width: 100%;
            max-width: 480px;
            background: #ffffff;
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: 16px;
            padding: 24px 26px;
            box-shadow: 0 20px 44px -18px rgba(15, 23, 42, 0.12), 0 4px 12px -4px rgba(37, 99, 235, 0.06);
        }
        .brand {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            font-size: 1rem;
            font-weight: 800;
            color: var(--text-main);
            letter-spacing: -0.02em;
            text-decoration: none;
            margin-bottom: 14px;
        }
        .brand-logo-img {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            display: block;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.18);
        }
        h1 {
            font-size: 1.3rem;
            font-weight: 800;
            margin-bottom: 4px;
            letter-spacing: -0.025em;
            text-wrap: balance;
        }
        p.sub {
            font-size: 0.84rem;
            color: var(--text-muted);
            margin-bottom: 16px;
            text-wrap: pretty;
        }
        .field-row {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }
        .field {
            margin-bottom: 11px;
        }
        label {
            display: block;
            font-size: 0.78rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 4px;
        }
        .input-wrap {
            position: relative;
        }
        input {
            width: 100%;
            min-height: 40px;
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            font-size: 0.875rem;
            font-family: inherit;
            color: var(--text-main);
            background: #ffffff;
            transition: border-color 0.18s var(--ease-out), box-shadow 0.18s var(--ease-out);
        }
        input:hover { border-color: #94a3b8; }
        input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.14);
        }
        input:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 1px;
        }
        input:user-invalid { border-color: #dc2626; }
        .pw-input { padding-right: 38px; }
        .pw-toggle {
            position: absolute;
            right: 6px;
            top: 50%;
            transform: translateY(-50%);
            width: 30px;
            height: 30px;
            border: none;
            border-radius: 7px;
            background: transparent;
            color: var(--text-muted);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.15s ease, color 0.15s ease;
        }
        .pw-toggle:hover { background: #f1f5f9; color: var(--text-main); }
        .pw-toggle:focus-visible { outline: 2px solid var(--primary); outline-offset: 1px; }
        .btn {
            width: 100%;
            min-height: 42px;
            padding: 10px 16px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(180deg, #2563eb 0%, #1d4ed8 100%);
            color: #ffffff;
            font-size: 0.9rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            margin-top: 4px;
            box-shadow: 0 8px 18px -6px rgba(37, 99, 235, 0.45);
            transition: transform 0.16s var(--ease-out), box-shadow 0.16s var(--ease-out), filter 0.16s ease;
        }
        .btn:hover {
            filter: brightness(1.04);
            transform: translateY(-1px);
            box-shadow: 0 12px 22px -6px rgba(37, 99, 235, 0.5);
        }
        .btn:active { transform: translateY(0) scale(0.99); }
        .btn:focus-visible { outline: 2px solid var(--primary); outline-offset: 2px; }
        .divider {
            display: flex;
            align-items: center;
            margin: 14px 0 12px;
            text-align: center;
            color: #94a3b8;
            font-size: 0.74rem;
        }
        .divider::before,
        .divider::after { content: ''; flex: 1; border-bottom: 1px solid var(--border); }
        .divider span { padding: 0 10px; font-weight: 500; }
        .btn-google {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            width: 100%;
            min-height: 41px;
            padding: 9px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: #ffffff;
            color: #1e293b;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
            transition: background 0.15s ease, border-color 0.15s ease, transform 0.15s var(--ease-out);
        }
        .btn-google:hover { background: #f8fafc; border-color: #94a3b8; transform: translateY(-1px); }
        .btn-google:focus-visible { outline: 2px solid var(--primary); outline-offset: 2px; }
        .footer-text { margin-top: 14px; text-align: center; font-size: 0.82rem; color: var(--text-muted); }
        .footer-text a { color: var(--primary); font-weight: 600; text-decoration: none; }
        .footer-text a:hover { text-decoration: underline; }
        .alert { padding: 10px 12px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; color: #991b1b; font-size: 0.82rem; margin-bottom: 12px; }
        .footer-credit { margin-top: 14px; text-align: center; font-size: 0.76rem; color: var(--text-muted); line-height: 1.5; }
        .footer-credit strong { color: #334155; font-weight: 600; }
        @media (max-width: 540px) {
            body { padding: 14px 12px; justify-content: flex-start; }
            .auth-card { padding: 20px 18px; border-radius: 14px; }
            .field-row { grid-template-columns: 1fr; gap: 0; }
            input { min-height: 38px; font-size: 0.84rem; padding: 8px 11px; }
            .btn, .btn-google { min-height: 40px; font-size: 0.85rem; }
        }
    </style>
    @include('partials.meta_pixel')
</head>
<body>
    <main class="auth-card">
        <a href="/" class="brand">
            <img src="/images/logo.png" alt="SkripsiLab AI" class="brand-logo-img" width="34" height="34" style="object-fit:contain;">
            <span>SkripsiLab AI</span>
        </a>

        <h1>Buat Akun Baru</h1>
        <p class="sub">Mulai susun proposal dan skripsimu dengan lebih terarah dan rapi.</p>

        @if($errors->any())
            <div class="alert" role="alert">{{ $errors->first() }}</div>
        @endif

        <form action="{{ route('register') }}" method="POST">
            @csrf
            <div class="field">
                <label for="reg-name">Nama Lengkap</label>
                <input type="text" id="reg-name" name="name" value="{{ old('name') }}" required autocomplete="name" enterkeyhint="next" placeholder="Nama Lengkap Mahasiswa">
            </div>

            <div class="field">
                <label for="reg-email">Alamat Email</label>
                <input type="email" id="reg-email" name="email" value="{{ old('email') }}" required autocomplete="email" autocorrect="off" autocapitalize="none" spellcheck="false" enterkeyhint="next" placeholder="nama@email.com">
            </div>

            <div class="field-row">
                <div class="field">
                    <label for="reg-password">Kata Sandi</label>
                    <div class="input-wrap">
                        <input type="password" id="reg-password" name="password" class="pw-input" required minlength="6" autocomplete="new-password" enterkeyhint="next" placeholder="Minimal 6 karakter">
                        <button type="button" class="pw-toggle" aria-pressed="false" aria-label="Tampilkan kata sandi" onclick="toggleAuthPassword('reg-password', this)">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <div class="field">
                    <label for="reg-password-confirm">Konfirmasi Sandi</label>
                    <div class="input-wrap">
                        <input type="password" id="reg-password-confirm" name="password_confirmation" class="pw-input" required minlength="6" autocomplete="new-password" enterkeyhint="next" placeholder="Ulangi kata sandi">
                        <button type="button" class="pw-toggle" aria-pressed="false" aria-label="Tampilkan konfirmasi kata sandi" onclick="toggleAuthPassword('reg-password-confirm', this)">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <div class="field">
                <label for="reg-referral">Kode Referral <span style="font-weight: 400; color: #94a3b8;">(Opsional)</span></label>
                <input type="text" id="reg-referral" name="referral_code" value="{{ old('referral_code', request('ref')) }}" autocorrect="off" autocapitalize="characters" spellcheck="false" enterkeyhint="done" placeholder="Contoh: SKRIPSI-1234">
            </div>

            <button type="submit" class="btn">Daftar &amp; Masuk Workspace</button>
        </form>

        <div class="divider"><span>atau daftar dengan</span></div>

        <a href="{{ request('ref') ? route('auth.google', ['ref' => request('ref')]) : route('auth.google') }}" class="btn-google">
            <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
            </svg>
            <span>Daftar dengan Google</span>
        </a>

        <div class="footer-text">
            Sudah punya akun? <a href="{{ route('login') }}">Masuk di sini</a>
        </div>
    </main>

    <footer class="footer-credit">
        <div style="margin-bottom:6px;display:flex;justify-content:center;gap:12px;flex-wrap:wrap;">
            <a href="{{ url('/kebijakan-privasi') }}" style="color:#475569;text-decoration:none;font-weight:600;">Kebijakan Privasi</a>
            <a href="{{ url('/syarat-ketentuan') }}" style="color:#475569;text-decoration:none;font-weight:600;">Syarat &amp; Ketentuan</a>
            <a href="{{ url('/tentang') }}" style="color:#475569;text-decoration:none;font-weight:600;">Tentang SkripsiLab AI</a>
        </div>
        SkripsiLab AI &copy; {{ date('Y') }}
    </footer>

    <script>
        function toggleAuthPassword(id, btn) {
            const input = document.getElementById(id);
            if (!input) return;
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.setAttribute('aria-pressed', show ? 'true' : 'false');
            btn.setAttribute('aria-label', show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
        }
    </script>
</body>
</html>
