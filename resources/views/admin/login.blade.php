<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kunci Akses Admin · SkripsiLab</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #090d16;
            background-image: 
                radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(168, 85, 247, 0.15) 0px, transparent 50%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #f8fafc;
        }
        .card {
            background: rgba(15, 23, 42, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(16px);
            border-radius: 20px;
            width: 100%;
            max-width: 420px;
            padding: 36px 32px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            text-align: center;
        }
        .shield-icon {
            width: 58px;
            height: 58px;
            border-radius: 16px;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            box-shadow: 0 10px 20px -5px rgba(99, 102, 241, 0.5);
        }
        h1 {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 8px;
            color: #ffffff;
        }
        p.desc {
            font-size: 13px;
            color: #94a3b8;
            line-height: 1.5;
            margin-bottom: 24px;
        }
        .alert {
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 20px;
            text-align: left;
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }
        .alert-info {
            background: rgba(59, 130, 246, 0.15);
            border: 1px solid rgba(59, 130, 246, 0.3);
            color: #93c5fd;
        }
        .input-group {
            text-align: left;
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #cbd5e1;
            margin-bottom: 8px;
        }
        input[type="password"],
        input[type="text"] {
            width: 100%;
            background: rgba(30, 41, 59, 0.8);
            border: 1.5px solid rgba(255, 255, 255, 0.12);
            border-radius: 12px;
            padding: 13px 16px;
            font-size: 15px;
            color: #ffffff;
            font-family: inherit;
            outline: none;
            transition: all 0.2s;
            text-align: center;
            letter-spacing: 2px;
        }
        input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25);
            background: rgba(30, 41, 59, 1);
        }
        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            border: none;
            border-radius: 12px;
            padding: 13px;
            color: #ffffff;
            font-size: 14.5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 14px 0 rgba(99, 102, 241, 0.4);
        }
        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px 0 rgba(99, 102, 241, 0.5);
        }
        .btn-submit:active {
            transform: translateY(0);
        }
        .back-link {
            display: inline-block;
            margin-top: 22px;
            font-size: 12.5px;
            color: #64748b;
            text-decoration: none;
            transition: color 0.15s;
        }
        .back-link:hover {
            color: #cbd5e1;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="shield-icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
        </div>

        <h1>Panel Admin Khusus</h1>
        <p class="desc">Halaman monitoring data dan jumlah pengguna SkripsiLab. Masukkan kode akses pemilik (Admin PIN) untuk melanjutkan.</p>

        @if(!empty($error))
            <div class="alert alert-error">{{ $error }}</div>
        @endif

        @if(!empty($info))
            <div class="alert alert-info">{{ $info }}</div>
        @endif

        <form action="{{ route('admin.login') }}" method="POST">
            @csrf
            <div class="input-group">
                <label for="pin">Kode Akses Admin</label>
                <input type="password" id="pin" name="pin" autofocus placeholder="••••••••" required autocomplete="current-password">
            </div>

            <button type="submit" class="btn-submit">Buka Data Pengguna &rarr;</button>
        </form>

        <a href="{{ route('home') }}" class="back-link">&larr; Kembali ke Beranda Utama</a>
    </div>
</body>
</html>
