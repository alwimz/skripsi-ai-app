<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun — SkripsiAI</title>
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
            align-items: center;
            justify-content: center;
            padding: 20px;
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
            gap: 9px;
            font-size: 16px;
            font-weight: 700;
            color: #111827;
            text-decoration: none;
            margin-bottom: 24px;
        }
        .brand-mark {
            width: 28px;
            height: 28px;
            border-radius: 7px;
            background: #2e2a5a;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
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
        input:focus { border-color: #2e2a5a; }
        .btn {
            width: 100%;
            padding: 11px;
            border: none;
            border-radius: 9px;
            background: #2e2a5a;
            color: #ffffff;
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            margin-top: 4px;
        }
        .btn:hover { background: #231f46; }
        .footer-text { margin-top: 20px; text-align: center; font-size: 13px; color: #6b7280; }
        .footer-text a { color: #2e2a5a; font-weight: 600; text-decoration: none; }
        .alert { padding: 10px 12px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; color: #991b1b; font-size: 13px; margin-bottom: 16px; }
    </style>
</head>
<body>
    <div class="auth-card">
        <a href="/" class="brand">
            <span class="brand-mark">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/></svg>
            </span>
            SkripsiAI
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

            <button type="submit" class="btn">Daftar & Masuk</button>
        </form>

        <div class="footer-text">
            Sudah punya akun? <a href="{{ route('login') }}">Masuk di sini</a>
        </div>
    </div>
</body>
</html>
