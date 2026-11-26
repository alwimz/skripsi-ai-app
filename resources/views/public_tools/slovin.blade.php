<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kalkulator Sampel Slovin &amp; Lemeshow Online Gratis — Bab III Skripsi | SkripsiLab AI</title>
    <meta name="description" content="Hitung jumlah sampel penelitian minimal otomatis pakai rumus Slovin (populasi diketahui) dan Lemeshow (populasi tidak diketahui), lengkap langkah perhitungan &amp; paragraf siap salin ke Bab III.">
    <meta name="keywords" content="kalkulator slovin online, rumus slovin skripsi, kalkulator lemeshow online, hitung sampel penelitian bab 3, cara menentukan ukuran sampel skripsi, skripsilab ai">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">
    <link rel="canonical" href="{{ url('/kalkulator-slovin') }}">
    <meta name="theme-color" content="#2563eb">
    <link rel="icon" type="image/png" sizes="512x512" href="/images/logo.png?v=3">
    <link rel="shortcut icon" href="/favicon.ico?v=3">
    <link rel="apple-touch-icon" href="/images/logo.png?v=3">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @php
        $slovinSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebApplication',
            'name' => 'Kalkulator Sampel Slovin & Lemeshow — SkripsiLab AI',
            'url' => url('/kalkulator-slovin'),
            'applicationCategory' => 'EducationalApplication',
            'operatingSystem' => 'Web Browser',
            'inLanguage' => 'id-ID',
            'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'IDR'],
            'description' => 'Kalkulator sampel penelitian Slovin dan Lemeshow gratis untuk mahasiswa Indonesia lengkap dengan langkah rumus dan paragraf Bab III siap salin.',
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($slovinSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f8fafc;
            color: #0f172a;
            line-height: 1.6;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }
        a { color: inherit; text-decoration: none; }
        .container { max-width: 960px; margin: 0 auto; padding: 0 20px; }
        .topbar {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 12px 0;
            position: sticky;
            top: 0;
            z-index: 40;
        }
        .topbar-inner { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
        .brand { display: inline-flex; align-items: center; gap: 9px; font-weight: 800; font-size: 16px; }
        .brand img { width: 34px; height: 34px; object-fit: contain; display: block; }
        .nav-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 7px;
            min-height: 44px; padding: 10px 16px; border-radius: 10px;
            font-size: 13.5px; font-weight: 600; font-family: inherit; cursor: pointer;
            border: 1px solid transparent; transition: all 0.16s ease;
        }
        .btn-primary { background: #2563eb; color: #ffffff; box-shadow: 0 6px 16px -6px rgba(37, 99, 235, 0.45); }
        .btn-primary:hover { background: #1d4ed8; transform: translateY(-1px); }
        .btn-outline { background: #ffffff; color: #0f172a; border-color: #cbd5e1; }
        .btn-outline:hover { background: #f1f5f9; border-color: #94a3b8; }
        .hero {
            background: linear-gradient(180deg, #eff6ff 0%, #f8fafc 100%);
            border-bottom: 1px solid #e2e8f0;
            padding: 32px 0 24px;
        }
        .eyebrow {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 12px; font-weight: 700; color: #1d4ed8; background: #ffffff;
            border: 1px solid #bfdbfe; border-radius: 999px; padding: 4px 12px; margin-bottom: 10px;
        }
        h1 { font-size: clamp(1.5rem, 1.2rem + 1.4vw, 2.1rem); font-weight: 800; letter-spacing: -0.025em; line-height: 1.25; margin-bottom: 8px; }
        .hero p { font-size: 14.5px; color: #475569; max-width: 720px; }
        main { padding: 26px 0 54px; }
        .card {
            background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px;
            padding: 24px; box-shadow: 0 10px 26px -18px rgba(15, 23, 42, 0.16);
        }
        .mode-tabs {
            display: inline-flex; background: #f1f5f9; border-radius: 11px; padding: 4px; gap: 4px;
            flex-wrap: wrap; margin-bottom: 18px;
        }
        .mode-btn {
            min-height: 40px; padding: 8px 14px; border-radius: 8px; border: none;
            background: transparent; color: #475569; font-size: 13px; font-weight: 600;
            font-family: inherit; cursor: pointer;
        }
        .mode-btn[aria-selected="true"] {
            background: #ffffff; color: #2563eb; box-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);
        }
        .grid-2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
        .field { display: flex; flex-direction: column; gap: 5px; margin-bottom: 12px; }
        label { font-size: 13px; font-weight: 600; color: #334155; }
        .hint { font-size: 12px; color: #64748b; }
        input, select {
            width: 100%; min-height: 46px; padding: 10px 12px; font-size: max(1rem, 1em);
            font-family: inherit; color: #0f172a; background: #ffffff;
            border: 1px solid #94a3b8; border-radius: 10px;
        }
        input:focus-visible, select:focus-visible, button:focus-visible, a:focus-visible {
            outline: 2px solid #2563eb; outline-offset: 2px;
        }
        .result-box {
            margin-top: 16px; background: linear-gradient(180deg, #eff6ff 0%, #ffffff 100%);
            border: 1.5px solid #bfdbfe; border-radius: 14px; padding: 20px;
        }
        .result-top { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 12px; }
        .result-num { font-size: clamp(1.6rem, 1.3rem + 1vw, 2.2rem); font-weight: 800; color: #1d4ed8; letter-spacing: -0.02em; }
        .steps-box {
            background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px;
            padding: 12px 14px; font-size: 13.5px; color: #334155; margin-bottom: 12px;
        }
        .para-box {
            background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px;
            padding: 14px; font-size: 14px; color: #0f172a; line-height: 1.68;
        }
        .copy-wrap { position: relative; display: inline-flex; align-items: center; gap: 8px; }
        .copy-tip {
            font-size: 12px; font-weight: 700; color: #047857; background: #ecfdf5;
            border: 1px solid #a7f3d0; padding: 4px 10px; border-radius: 999px;
            opacity: 0; transition: opacity 0.16s ease; pointer-events: none;
        }
        .copy-btn[data-copied] + .copy-tip, .copy-btn[data-failed] + .copy-tip { opacity: 1; }
        .copy-btn[data-failed] + .copy-tip { color: #b91c1c; background: #fef2f2; border-color: #fecaca; }
        .cta-card {
            margin-top: 22px; background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            color: #ffffff; border-radius: 16px; padding: 22px 24px;
            display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;
        }
        .cta-card h2 { font-size: 1.2rem; font-weight: 800; margin-bottom: 4px; }
        .cta-card p { font-size: 13.5px; color: #dbeafe; max-width: 560px; }
        .cta-card .btn { background: #ffffff; color: #1d4ed8; flex-shrink: 0; }
        .cta-card .btn:hover { background: #eff6ff; }
        @media (max-width: 720px) {
            .grid-2, .grid-3 { grid-template-columns: 1fr; }
            .card { padding: 16px; }
        }
    </style>
    @include('partials.meta_pixel', ['metaViewEvent' => ['name' => 'ViewContent', 'params' => ['content_name' => 'Kalkulator Sampel Slovin & Lemeshow', 'content_category' => 'free_tool']]])
</head>
<body>
    <header class="topbar">
        <div class="container topbar-inner">
            <a href="{{ url('/') }}" class="brand">
                <img src="/images/logo.png?v=3" alt="SkripsiLab AI" width="34" height="34">
                <span>SkripsiLab AI</span>
            </a>
            <nav class="nav-actions" aria-label="Navigasi Alat Gratis">
                <a href="{{ url('/hitung-kata-abstrak') }}" class="btn btn-outline">Hitung Kata Abstrak</a>
                <a href="{{ url('/alat-skripsi-gratis') }}" class="btn btn-outline">Semua Alat Gratis</a>
                @auth
                    <a href="{{ route('app.create') }}" class="btn btn-primary">Workspace →</a>
                @else
                    <a href="{{ route('register') }}" class="btn btn-primary" data-meta-lead="Header Slovin Daftar Gratis">Daftar Gratis →</a>
                @endauth
            </nav>
        </div>
    </header>

    <section class="hero">
        <div class="container">
            <div class="eyebrow">Gratis Tanpa Login · Langsung Salin ke Bab III</div>
            <h1>Kalkulator Sampel Slovin &amp; Lemeshow Online</h1>
            <p>Hitung jumlah sampel minimal penelitian kuantitatif secara akurat lengkap dengan langkah perhitungan matematis dan paragraf siap salin untuk Bab III Metodologi Penelitian.</p>
        </div>
    </section>

    <main>
        <div class="container">
            <div class="card">
                <div class="mode-tabs" role="tablist" aria-label="Pilih rumus perhitungan sampel">
                    <button type="button" class="mode-btn" id="tab-slovin" role="tab" aria-selected="true" aria-controls="pane-slovin" onclick="setMode('slovin')">Rumus Slovin (Populasi Diketahui)</button>
                    <button type="button" class="mode-btn" id="tab-lemeshow" role="tab" aria-selected="false" aria-controls="pane-lemeshow" onclick="setMode('lemeshow')">Rumus Lemeshow (Populasi Tidak Diketahui)</button>
                </div>

                <form onsubmit="event.preventDefault(); computeSample();" aria-label="Formulir perhitungan sampel penelitian">
                    <div id="pane-slovin" role="tabpanel" aria-labelledby="tab-slovin">
                        <div class="grid-2">
                            <div class="field">
                                <label for="slovin-n">Jumlah Populasi (N)</label>
                                <span id="hint-n" class="hint">Total subjek dalam populasi penelitian (minimal 2).</span>
                                <input type="number" id="slovin-n" name="population" min="2" step="1" value="350" required aria-describedby="hint-n" oninput="computeSample()">
                            </div>
                            <div class="field">
                                <label for="slovin-e">Batas Kesalahan / Margin of Error (e)</label>
                                <span id="hint-e" class="hint">Standar skripsi ilmu sosial/ekonomi umumnya 5% (0,05) atau 10% (0,10).</span>
                                <select id="slovin-e" name="error_margin" aria-describedby="hint-e" onchange="onErrorChange()">
                                    <option value="0.05" selected>5% (0,05) — Standar Skripsi Umum</option>
                                    <option value="0.1">10% (0,10) — Populasi Kecil / Terbatas</option>
                                    <option value="0.01">1% (0,01) — Ketelitian Tinggi</option>
                                    <option value="custom">Kustom (Tulis % Sendiri)</option>
                                </select>
                                <input type="number" id="slovin-e-custom" name="error_custom" style="display:none;margin-top:8px;" min="0.5" max="25" step="0.5" value="7" placeholder="Misal: 7" aria-label="Persentase margin of error kustom" oninput="computeSample()">
                            </div>
                        </div>
                    </div>

                    <div id="pane-lemeshow" role="tabpanel" aria-labelledby="tab-lemeshow" hidden>
                        <div class="grid-3">
                            <div class="field">
                                <label for="lem-z">Tingkat Kepercayaan (Z)</label>
                                <select id="lem-z" name="z_score" onchange="computeSample()">
                                    <option value="1.96" selected>95% (Z = 1,96 — Standar Skripsi)</option>
                                    <option value="1.645">90% (Z = 1,645)</option>
                                    <option value="2.576">99% (Z = 2,576)</option>
                                </select>
                            </div>
                            <div class="field">
                                <label for="lem-p">Estimasi Proporsi Maksimal (P)</label>
                                <input type="number" id="lem-p" name="proportion" min="0.05" max="0.95" step="0.05" value="0.5" required oninput="computeSample()">
                            </div>
                            <div class="field">
                                <label for="lem-d">Sampling Error / Alpha (d)</label>
                                <select id="lem-d" name="alpha" onchange="computeSample()">
                                    <option value="0.1" selected>10% (0,10 — n ≈ 96–100)</option>
                                    <option value="0.05">5% (0,05 — n ≈ 385)</option>
                                    <option value="0.07">7% (0,07 — n ≈ 196)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="result-box" aria-live="polite">
                    <div class="result-top">
                        <div>
                            <div style="font-size:12px;font-weight:700;color:#1d4ed8;text-transform:uppercase;">Jumlah Sampel Minimal (n)</div>
                            <div class="result-num" id="sample-final">187 Responden</div>
                        </div>
                        <span class="copy-wrap">
                            <button type="button" class="btn btn-primary copy-btn" command="--copy" commandfor="sample-paragraph">Salin Paragraf Bab III</button>
                            <span class="copy-tip" role="status"></span>
                        </span>
                    </div>
                    <div class="steps-box" id="sample-steps"></div>
                    <div class="para-box" id="sample-paragraph"></div>
                </div>
            </div>

            <div class="cta-card">
                <div>
                    <h2>Butuh Lanjut Susun Bab III Lengkap + Cari Jurnal Ber-DOI?</h2>
                    <p>Di Workspace SkripsiLab AI, <strong>Cari Ide Judul &amp; Cari Jurnal Ber-DOI sekarang GRATIS UNLIMITED</strong> di semua paket — dan kamu bisa coba buat Sampul, Bab I, serta Daftar Pustaka gratis!</p>
                </div>
                <a href="{{ auth()->check() ? route('app.create', ['tab' => 'projects']) : route('register') }}" class="btn" data-meta-lead="CTA Slovin Daftar Gratis">Mulai Gratis Sekarang →</a>
            </div>
        </div>
    </main>

    @include('partials.public_footer')

    <script>
        let currentMode = 'slovin';
        const copyTimers = new WeakMap();

        function setMode(mode) {
            currentMode = mode;
            const isSlovin = mode === 'slovin';
            document.getElementById('tab-slovin').setAttribute('aria-selected', isSlovin ? 'true' : 'false');
            document.getElementById('tab-lemeshow').setAttribute('aria-selected', isSlovin ? 'false' : 'true');
            document.getElementById('pane-slovin').hidden = !isSlovin;
            document.getElementById('pane-lemeshow').hidden = isSlovin;
            computeSample();
        }

        function onErrorChange() {
            const sel = document.getElementById('slovin-e');
            const custom = document.getElementById('slovin-e-custom');
            custom.style.display = sel.value === 'custom' ? 'block' : 'none';
            computeSample();
        }

        function computeSample() {
            const finalEl = document.getElementById('sample-final');
            const stepsEl = document.getElementById('sample-steps');
            const paraEl = document.getElementById('sample-paragraph');

            if (currentMode === 'slovin') {
                const nPop = Math.max(2, parseInt(document.getElementById('slovin-n').value || '350', 10));
                const sel = document.getElementById('slovin-e').value;
                const e = sel === 'custom'
                    ? Math.min(0.25, Math.max(0.005, (parseFloat(document.getElementById('slovin-e-custom').value || '7') / 100)))
                    : parseFloat(sel || '0.05');
                const ePct = (e * 100).toFixed(e * 100 % 1 === 0 ? 0 : 1);
                const eSq = e * e;
                const denom = 1 + (nPop * eSq);
                const exact = nPop / denom;
                const rounded = Math.ceil(exact);

                finalEl.textContent = `${rounded} Responden`;
                stepsEl.innerHTML = `<strong>Rumus Slovin:</strong> n = N / (1 + N &times; e&sup2;) &rarr; ${nPop} / (1 + ${nPop} &times; (${e})&sup2;) = ${nPop} / ${denom.toFixed(4)} = <strong>${exact.toFixed(2)}</strong> (dibulatkan ke atas menjadi <strong>${rounded} responden</strong>).`;
                paraEl.textContent = `Penentuan ukuran sampel dalam penelitian ini menggunakan rumus Slovin dengan jumlah populasi sebesar N = ${nPop} dan tingkat batas kesalahan (margin of error) sebesar e = ${ePct}% (${e}). Berdasarkan perhitungan n = ${nPop} / (1 + ${nPop} (${e})²), diperoleh nilai sebesar ${exact.toFixed(2)} sehingga jumlah sampel minimal yang ditetapkan dan dibulatkan adalah sebanyak ${rounded} responden.`;
            } else {
                const z = parseFloat(document.getElementById('lem-z').value || '1.96');
                const p = Math.min(0.95, Math.max(0.05, parseFloat(document.getElementById('lem-p').value || '0.5')));
                const d = parseFloat(document.getElementById('lem-d').value || '0.1');
                const dPct = Math.round(d * 100);
                const exact = (z * z * p * (1 - p)) / (d * d);
                const rounded = Math.ceil(exact);

                finalEl.textContent = `${rounded} Responden`;
                stepsEl.innerHTML = `<strong>Rumus Lemeshow:</strong> n = (Z&sup2; &times; P &times; (1 - P)) / d&sup2; &rarr; (${z}&sup2; &times; ${p} &times; ${(1 - p).toFixed(2)}) / (${d})&sup2; = <strong>${exact.toFixed(2)}</strong> (dibulatkan menjadi <strong>${rounded} responden</strong>).`;
                paraEl.textContent = `Karena jumlah populasi dalam penelitian ini tidak diketahui secara pasti, penentuan jumlah sampel menggunakan rumus Lemeshow dengan nilai Z = ${z}, estimasi proporsi maksimal P = ${p}, dan tingkat kesalahan (alpha) d = ${dPct}% (${d}). Dari hasil perhitungan n = (${z}² × ${p} × ${(1 - p).toFixed(2)}) / ${d}², diperoleh angka ${exact.toFixed(2)} yang dibulatkan menjadi minimal ${rounded} responden.`;
            }
        }

        function legacyCopy(text) {
            const prev = document.activeElement;
            const field = Object.assign(document.createElement('textarea'), { value: text });
            field.setAttribute('readonly', '');
            field.style.cssText = 'position:fixed;inset-block-start:0;opacity:0';
            document.body.append(field);
            field.select();
            try { return document.execCommand('copy'); }
            finally { field.remove(); prev?.focus({ preventScroll: true }); }
        }

        function reportCopy(btn, ok) {
            const tip = btn.nextElementSibling;
            if (!tip) return;
            clearTimeout(copyTimers.get(btn));
            btn.removeAttribute('data-copied');
            btn.removeAttribute('data-failed');
            void btn.offsetWidth;
            btn.setAttribute(ok ? 'data-copied' : 'data-failed', '');
            requestAnimationFrame(() => { tip.textContent = ok ? 'Tersalin!' : 'Gagal menyalin'; });
            copyTimers.set(btn, setTimeout(() => {
                btn.removeAttribute('data-copied');
                btn.removeAttribute('data-failed');
                tip.textContent = '';
            }, 2000));
        }

        async function runCopy(btn, target) {
            const text = target.matches('input, textarea') ? target.value : target.innerText.trim();
            if (!text) return;
            try {
                await navigator.clipboard.writeText(text);
                reportCopy(btn, true);
            } catch {
                reportCopy(btn, legacyCopy(text));
            }
        }

        if ('CommandEvent' in window) {
            document.addEventListener('command', (ev) => {
                if (ev.command && ev.command.startsWith('--copy') && ev.source && ev.target) {
                    runCopy(ev.source, ev.target);
                }
            }, true);
        } else {
            document.addEventListener('click', (ev) => {
                if (ev.defaultPrevented) return;
                const btn = ev.target.closest('button[command^="--copy"]');
                if (!btn) return;
                const target = document.getElementById(btn.getAttribute('commandfor'));
                if (target) runCopy(btn, target);
            });
        }

        computeSample();
    </script>
</body>
</html>
