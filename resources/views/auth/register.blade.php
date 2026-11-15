<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun — SkripsiAI</title>
    <link rel="icon" type="image/svg+xml" href="/images/logo.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f9fafb;
            color: #111827;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            -webkit-font-smoothing: antialiased;
        }
        .auth-card {
            width: 100%;
            max-width: 400px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 32px 28px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.03);
        }
        .brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 16.5px;
            font-weight: 700;
            color: #111827;
            text-decoration: none;
            margin-bottom: 24px;
        }
        .brand-logo-img {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: block;
        }
        h1 { font-size: 21px; font-weight: 700; margin-bottom: 4px; letter-spacing: -0.02em; }
        p.sub { font-size: 13.5px; color: #6b7280; margin-bottom: 22px; }
        label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; }
        input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 9px;
            font-size: 14px;
            font-family: inherit;
            margin-bottom: 14px;
            outline: none;
        }
        input:focus { border-color: #2563eb; }
        .btn {
            width: 100%;
            padding: 11px;
            border: none;
            border-radius: 9px;
            background: #2563eb;
            color: #ffffff;
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            margin-top: 4px;
        }
        .btn:hover { background: #1d4ed8; }
        .footer-text { margin-top: 20px; text-align: center; font-size: 13px; color: #6b7280; }
        .footer-text a { color: #2563eb; font-weight: 600; text-decoration: none; }
        .alert { padding: 10px 12px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; color: #991b1b; font-size: 13px; margin-bottom: 16px; }
        .footer-credit {
            margin-top: 22px;
            text-align: center;
            font-size: 12.5px;
            color: #64748b;
            line-height: 1.5;
        }
        .footer-credit strong {
            color: #334155;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="auth-card">
        <a href="/" class="brand">
            <img src="/images/logo.svg" alt="SkripsiAI" class="brand-logo-img">
            <span>SkripsiAI</span>
        </a>

        <h1>Buat Akun Baru</h1>
        <p class="sub">Mulai susun proposal dan skripsimu dengan lebih terarah.</p>

        @if($errors->any())
            <div class="alert">{{ $errors->first() }}</div>
        @endif

        <form action="{{ route('register') }}" method="POST">
            @csrf
            <label>Nama Lengkap</label>
            <input type="text" name="name" value="{{ old('name') }}" required placeholder="Nama Mahasiswa">

            <label>Alamat Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required placeholder="nama@email.com">

            <label>Kata Sandi</label>
            <input type="password" name="password" required placeholder="Minimal 6 karakter">

            <label>Konfirmasi Kata Sandi</label>
            <input type="password" name="password_confirmation" required placeholder="Ulangi kata sandi">

            <label>Kode Referral (Opsional)</label>
            <input type="text" name="referral_code" value="{{ old('referral_code', request('ref')) }}" placeholder="Contoh: REF-1234 (Opsional)">

            <button type="submit" class="btn">Daftar & Masuk</button>
        </form>

        <div style="display: flex; align-items: center; margin: 18px 0 14px; text-align: center; color: #94a3b8; font-size: 12px;">
            <div style="flex: 1; border-bottom: 1px solid #e2e8f0;"></div>
            <span style="padding: 0 10px; font-weight: 500;">atau daftar dengan</span>
            <div style="flex: 1; border-bottom: 1px solid #e2e8f0;"></div>
        </div>

        <a href="{{ route('auth.google') }}" style="display: flex; align-items: center; justify-content: center; gap: 9px; width: 100%; padding: 11px 14px; border: 1.5px solid #cbd5e1; border-radius: 9px; background: #ffffff; color: #1e293b; font-size: 13.5px; font-weight: 600; text-decoration: none; box-shadow: 0 1px 2px rgba(0,0,0,0.04); transition: all 0.15s; box-sizing: border-box;" onmouseover="this.style.background='#f8fafc'; this.style.borderColor='#94a3b8';" onmouseout="this.style.background='#ffffff'; this.style.borderColor='#cbd5e1';">
            <svg width="18" height="18" viewBox="0 0 24 24">
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
    </div>

    <!-- Modal Daftar Google (Coming Soon) -->
    <div id="modal-google-coming-soon" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center; padding: 16px;">
        <div style="background: #ffffff; border-radius: 14px; width: 100%; max-width: 380px; padding: 24px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2); box-sizing: border-box; text-align: center;">
            <div style="width: 48px; height: 48px; border-radius: 50%; background: #eff6ff; display: flex; align-items: center; justify-content: center; margin: 0 auto 14px;">
                <svg width="24" height="24" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                </svg>
            </div>
            <div style="font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 6px;">Fitur Segera Hadir</div>
            <p style="font-size: 13px; color: #64748b; line-height: 1.55; margin-bottom: 20px;">Fitur Pendaftaran dengan Google sedang dalam tahap pengembangan (Coming Soon). Untuk saat ini, silakan langsung daftar menggunakan formulir di atas.</p>
            <button type="button" class="btn" style="background: #2563eb;" onclick="closeGoogleComingSoon()">Mengerti, Buat Akun Baru</button>
        </div>
    </div>

    <!-- Footer Copyright -->
    <div class="footer-credit">
        Demo by <strong>Muh Alwi Syahrir</strong> &bull; SkripsiAI &copy; {{ date('Y') }}
    </div>

    <script>
        function showGoogleComingSoon() {
            const m = document.getElementById('modal-google-coming-soon');
            if (m) m.style.display = 'flex';
        }
        function closeGoogleComingSoon() {
            const m = document.getElementById('modal-google-coming-soon');
            if (m) m.style.display = 'none';
        }
    </script>
</body>
</html>
