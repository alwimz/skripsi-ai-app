<?php

namespace App\Http\Controllers;

use App\Services\AIManagerService;
use App\Services\StatisticsEngineService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\Style\Table as TableStyle;

class StatisticsController extends Controller
{
    protected StatisticsEngineService $engine;
    protected AIManagerService $ai;

    public function __construct(StatisticsEngineService $engine, AIManagerService $ai)
    {
        $this->engine = $engine;
        $this->ai = $ai;
    }

    /**
     * Jalankan analisis statistik deterministik setara IBM SPSS.
     */
    public function analyze(Request $request)
    {
        $columns = $request->input('columns', []);
        $rows = $request->input('rows', []);
        $testType = (string) $request->input('test_type', 'descriptive');

        if (is_string($columns)) {
            $columns = json_decode($columns, true) ?: [];
        }
        if (is_string($rows)) {
            $rows = json_decode($rows, true) ?: [];
        }

        // Dukungan unggah file CSV langsung jika dikirim via multipart
        if (empty($rows) && $request->hasFile('file')) {
            [$columns, $rows] = $this->parseCsvFile($request->file('file'));
        }

        if (empty($columns) || empty($rows)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data tabel kosong. Unggah file Excel (.xlsx/.csv), tempel data, atau pilih dataset contoh terlebih dahulu.',
            ], 422);
        }

        if (count($rows) > 5000) {
            return response()->json([
                'status' => 'error',
                'message' => 'Maksimal 5.000 baris observasi per analisis agar komputasi tetap instan.',
            ], 422);
        }

        $options = [
            'alpha' => (float) $request->input('alpha', 0.05),
            'cols' => $request->input('cols', []),
            'x_cols' => $request->input('x_cols', []),
            'y_col' => $request->input('y_col', ''),
            'group_col' => $request->input('group_col', ''),
            'value_col' => $request->input('value_col', ''),
            'pair_a' => $request->input('pair_a', ''),
            'pair_b' => $request->input('pair_b', ''),
            'cat_a' => $request->input('cat_a', ''),
            'cat_b' => $request->input('cat_b', ''),
            'nonparam_mode' => $request->input('nonparam_mode', 'mann_whitney'),
            'auto_group' => $request->input('auto_group', true),
        ];

        try {
            $result = $this->engine->analyze($columns, $rows, $testType, $options);
            $result['dataset_title'] = trim((string) $request->input('dataset_title', '')) ?: 'Data Penelitian Mahasiswa';

            return response()->json([
                'status' => 'success',
                'result' => $result,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menghitung statistik: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Buat Narasi Interpretasi Bab IV Akademik dari hasil hitung valid (TANPA mengubah angka!)
     */
    public function interpret(Request $request)
    {
        $result = $request->input('result');
        if (is_string($result)) {
            $result = json_decode($result, true);
        }
        if (!is_array($result) || empty($result['tables'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Belum ada hasil perhitungan statistik untuk diinterpretasikan.',
            ], 422);
        }

        $topic = trim((string) $request->input('topic', 'Penelitian Skripsi'));
        $major = trim((string) $request->input('major', 'Umum'));

        // Ringkas seluruh tabel hasil eksak untuk dikirim ke AI
        $tablesText = '';
        foreach ($result['tables'] as $tbl) {
            $tablesText .= "\n### " . ($tbl['title'] ?? 'Tabel') . "\n";
            $tablesText .= implode(' | ', $tbl['headers'] ?? []) . "\n";
            foreach (($tbl['rows'] ?? []) as $r) {
                $tablesText .= implode(' | ', array_map('strval', $r)) . "\n";
            }
        }
        $conclusionsText = implode("\n- ", $result['conclusions'] ?? []);

        $domainHint = $this->resolveMajorTerminologyHint($major);

        $prompt = "Anda adalah Pembimbing Statistik & Metodologi Riset Universitas di Indonesia yang ahli di bidang {$major}.\n"
            . "Tuliskan narasi **Interpretasi Hasil Pengujian Statistik untuk Bab IV Skripsi/Tesis** yang siap ditempel ke Microsoft Word.\n\n"
            . "KONTEKS PENELITIAN:\n"
            . "- Judul/Topik: {$topic}\n"
            . "- Jurusan/Bidang Ilmu: {$major}\n"
            . "- Panduan Istilah Akademik Bidang Ini: {$domainHint}\n"
            . "- Jenis Pengujian: " . ($result['title'] ?? 'Uji Statistik') . "\n"
            . "- Keterangan: " . ($result['subtitle'] ?? '') . "\n\n"
            . "HASIL PERHITUNGAN VALID (SETARA IBM SPSS — DILARANG KERAS MENGUBAH ATAU MENGARANG ANGKA):\n"
            . $tablesText . "\n\n"
            . "RINGKASAN KEPUTUSAN MATEMATIS:\n- {$conclusionsText}\n\n"
            . "ATURAN PENULISAN KETAT:\n"
            . "1. Setiap angka yang Anda sebut (t, F, H, R², r, Sig., Cronbach's Alpha, Mean, SD, VIF, DW, Chi-Square, Shapiro-Wilk, K-S Lilliefors, dll.) HARUS 100% SAMA PERSIS dengan tabel di atas.\n"
            . "2. Gunakan bahasa Indonesia ilmiah baku (EYD V, formal, objektif, orang ketiga, tanpa kata 'saya') dan wajib memakai kosakata teknis/akademik yang tepat untuk bidang {$major} (bukan istilah generik).\n"
            . "3. Bagi menjadi 3 sub-bagian ringkas bernomor bersih: 4.1.1 Deskripsi Hasil Pengujian, 4.1.2 Pengujian Hipotesis dan Asumsi Statistik (sebutkan kriteria α = " . ($result['alpha'] ?? 0.05) . "), dan 4.1.3 Pembahasan dan Implikasi Ilmiah sesuai bidang {$major}.\n"
            . "4. DILARANG KERAS memakai simbol markdown seperti **, __, ###, *, atau bullet tanda bintang! Tulis judul sub-bab sebagai teks biasa tanpa tanda bintang, dan seluruh isi dalam paragraf narasi akademik yang mengalir rapi siap tempel langsung ke Microsoft Word.\n"
            . "5. Jangan menambahkan tabel baru atau angka asumsi fiktif. Tulis langsung isi narasinya (tanpa kalimat pembuka 'Tentu, berikut...').";

        try {
            $narration = $this->cleanAcademicNarration($this->ai->generateText($prompt));
            return response()->json([
                'status' => 'success',
                'narration' => $narration,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyusun interpretasi AI: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function cleanAcademicNarration(string $raw): string
    {
        $txt = str_replace(["\r\n", "\r"], "\n", trim($raw));
        // Hapus markdown headings (###, ##, #) dan tanda bold/italic (** , __ , *)
        $txt = (string) preg_replace('/^\s*#{1,6}\s*/m', '', $txt);
        $txt = str_replace(['**', '__'], '', $txt);
        $txt = (string) preg_replace('/^\s*[\*\-]\s+/m', '', $txt);
        $txt = (string) preg_replace('/\n{3,}/', "\n\n", $txt);
        return trim($txt);
    }

    private function resolveMajorTerminologyHint(string $major): string
    {
        $m = strtolower($major);
        if (str_contains($m, 'sipil') || str_contains($m, 'mesin') || str_contains($m, 'material') || str_contains($m, 'teknik')) {
            return 'Gunakan terminologi rekayasa & pengujian laboratorium seperti benda uji, variasi perlakuan/campuran, mutu material/beton, satuan teknis (MPa, cm, mm), standar SNI/ASTM, performa optimum, dan implikasi konstruksi/rekayasa.';
        }
        if (str_contains($m, 'kesehatan') || str_contains($m, 'kedokteran') || str_contains($m, 'keperawatan') || str_contains($m, 'farmasi') || str_contains($m, 'gizi')) {
            return 'Gunakan terminologi klinis & kesehatan seperti subjek/pasien/sampel uji, kelompok intervensi dan kontrol, efektivitas perlakuan/terapi, signifikansi statistik serta relevansi klinis.';
        }
        if (str_contains($m, 'pendidikan') || str_contains($m, 'pgsd') || str_contains($m, 'guru') || str_contains($m, 'kurikulum')) {
            return 'Gunakan terminologi pendidikan seperti peserta didik/siswa, kelas eksperimen dan kontrol, capaian hasil belajar, efektivitas model/media pembelajaran, dan implikasi pedagogis.';
        }
        if (str_contains($m, 'informatika') || str_contains($m, 'komputer') || str_contains($m, 'sistem informasi') || str_contains($m, 'it')) {
            return 'Gunakan terminologi teknologi informasi seperti pengguna sistem, performa/evaluasi aplikasi, variabel penerimaan teknologi, keandalan instrumen, dan implikasi pengembangan sistem.';
        }
        if (str_contains($m, 'manajemen') || str_contains($m, 'ekonomi') || str_contains($m, 'akuntansi') || str_contains($m, 'bisnis')) {
            return 'Gunakan terminologi bisnis & manajemen seperti responden, determinan kinerja/kepuasan/loyalitas, signifikansi parsial dan simultan, serta implikasi manajerial yang konkret.';
        }
        return 'Sesuaikan istilah objek penelitian, satuan pengukuran, dan implikasi praktis secara spesifik dengan disiplin ilmu ' . ($major ?: 'penelitian terkait') . '.';
    }

    /**
     * Generator Instrumen Pengumpulan Data bagi mahasiswa yang BELUM punya kuesioner / lembar eksperimen lab.
     * Sekaligus menghasilkan struktur kolom CSV/Excel yang langsung kompatibel dengan kalkulator Olah Data!
     */
    public function generateInstrument(Request $request)
    {
        $request->validate([
            'mode' => 'required|in:kuesioner,eksperimen',
            'topic' => 'required|string|max:300',
            'major' => 'nullable|string|max:120',
            'variables' => 'required|string|max:500',
            'items_per_var' => 'nullable|integer|min:2|max:12',
            'scale_type' => 'nullable|string|max:80',
        ]);

        $mode = (string) $request->input('mode');
        $topic = trim((string) $request->input('topic'));
        $major = trim((string) $request->input('major', 'Umum'));
        $variables = trim((string) $request->input('variables'));
        $itemsPerVar = (int) $request->input('items_per_var', 5);
        $scaleType = trim((string) $request->input('scale_type', 'Likert 1-5'));

        if ($mode === 'kuesioner') {
            $prompt = "Anda adalah Pakar Metodologi Penelitian & Penyusunan Instrumen Akademik Indonesia.\n"
                . "Mahasiswa jurusan {$major} membutuhkan **Kisi-Kisi Instrumen Bab III + Kuesioner Siap Sebar** untuk topik:\n"
                . "Judul/Topik: \"{$topic}\"\n"
                . "Variabel: {$variables}\n"
                . "Jumlah butir per variabel: {$itemsPerVar} pernyataan\n"
                . "Skala pengukuran: {$scaleType}\n\n"
                . "KELUARKAN HANYA JSON VALID TANPA TEKS TAMBAHAN / MARKDOWN FENCE dengan struktur persis:\n"
                . "{\n"
                . "  \"instrument_title\": \"Kuesioner Penelitian ...\",\n"
                . "  \"scale_guide\": \"1 = Sangat Tidak Setuju, 2 = Tidak Setuju, 3 = Netral, 4 = Setuju, 5 = Sangat Setuju\",\n"
                . "  \"recommended_test\": \"Uji Validitas & Reliabilitas -> Uji Asumsi Klasik & Regresi Linier Berganda\",\n"
                . "  \"variables\": [\n"
                . "    {\n"
                . "      \"code\": \"X1\",\n"
                . "      \"name\": \"Nama Variabel Lengkap\",\n"
                . "      \"role\": \"Independen / Dependen / Mediasi\",\n"
                . "      \"indicators\": [\"Indikator 1\", \"Indikator 2\"],\n"
                . "      \"items\": [\n"
                . "        {\"code\": \"X1_1\", \"indicator\": \"Indikator 1\", \"statement\": \"Pernyataan operasional konkret siap dijawab responden...\"}\n"
                . "      ]\n"
                . "    }\n"
                . "  ]\n"
                . "}\n"
                . "Pastikan setiap kode item unik dalam format KODEVAR_ANGKA (contoh: X1_1, X1_2, X2_1, Y_1) agar tabulasi Excel-nya langsung terbaca otomatis saat Uji Validitas & Reliabilitas!";
        } else {
            $prompt = "Anda adalah Ahli Metodologi Riset Eksperimen & Laboratorium ({$major}, Teknik Sipil, Teknik Mesin, Pertanian, Kesehatan, IT).\n"
                . "Susun **Lembar Pencatatan Data Eksperimen / Uji Laboratorium (Observation Logsheet)** untuk topik:\n"
                . "Judul/Topik: \"{$topic}\"\n"
                . "Faktor Perlakuan & Parameter Ukur: {$variables}\n"
                . "Jumlah ulangan / sampel per perlakuan: {$itemsPerVar}\n\n"
                . "KELUARKAN HANYA JSON VALID TANPA TEKS TAMBAHAN / MARKDOWN FENCE dengan struktur persis:\n"
                . "{\n"
                . "  \"instrument_title\": \"Lembar Pencatatan Data Uji Laboratorium / Eksperimen ...\",\n"
                . "  \"scale_guide\": \"Catat hasil pengukuran aktual sesuai satuan standar (mis. MPa, mm, gram, detik, %).\",\n"
                . "  \"recommended_test\": \"One-Way ANOVA + Uji Homogenitas Levene & Post-Hoc Bonferroni\",\n"
                . "  \"treatments\": [\"Perlakuan_A\", \"Perlakuan_B\", \"Perlakuan_C\"],\n"
                . "  \"replications\": {$itemsPerVar},\n"
                . "  \"parameters\": [\n"
                . "    {\"code\": \"Kuat_Tekan_MPa\", \"name\": \"Kuat Tekan Beton 28 Hari\", \"unit\": \"MPa\", \"method\": \"Compression Testing Machine (SNI 1974:2011)\"}\n"
                . "  ],\n"
                . "  \"protocol_steps\": [\"Langkah pencatatan 1\", \"Langkah pencatatan 2\", \"Langkah pencatatan 3\"]\n"
                . "}\n"
                . "Pastikan kode parameter singkat tanpa spasi (pakai underscore) agar langsung menjadi header tabel Excel/CSV yang siap diolah dengan One-Way ANOVA / t-Test / Regresi.";
        }

        $parsed = null;
        try {
            $raw = $this->ai->generateText($prompt);
            $parsed = $this->extractJsonObject($raw);
        } catch (\Throwable $e) {
            $parsed = null;
        }

        if (!is_array($parsed)) {
            $parsed = $this->buildFallbackInstrument($mode, $topic, $major, $variables, $itemsPerVar, $scaleType);
        }

        // Bangun header & baris kosong template Excel/CSV yang langsung kompatibel dengan Kalkulator Olah Data
        [$headers, $templateRows] = $this->buildTemplateSheet($mode, $parsed, $itemsPerVar);

        return response()->json([
            'status' => 'success',
            'mode' => $mode,
            'instrument' => $parsed,
            'template' => [
                'headers' => $headers,
                'rows' => $templateRows,
            ],
        ]);
    }

    /**
     * Ekspor Laporan Hasil Olah Data Statistik ke PDF A4 (Gaya Laporan Output SPSS & APA) lengkap dengan Grafik & Narasi.
     */
    public function downloadReportPdf(Request $request)
    {
        $result = $request->input('result');
        if (is_string($result)) {
            $result = json_decode($result, true);
        }
        $narration = trim((string) $request->input('narration', ''));
        $chartsImages = $this->sanitizeChartImages($request->input('charts_images', []));

        if (!is_array($result) || empty($result['tables'])) {
            return response()->json(['status' => 'error', 'message' => 'Data laporan statistik tidak valid.'], 422);
        }

        $title = e((string) ($result['title'] ?? 'Laporan Hasil Olah Data Statistik'));
        $subtitle = e((string) ($result['subtitle'] ?? ''));
        $datasetTitle = e((string) ($result['dataset_title'] ?? 'Data Penelitian'));
        $majorLabel = e(trim((string) $request->input('major', '')));
        $computedAt = e((string) ($result['computed_at'] ?? now('Asia/Jakarta')->format('d M Y, H:i') . ' WIB'));
        $userName = e(Auth::user()->name ?? 'Mahasiswa');

        $guideHtml = '';
        if (!empty($result['academic_guide']) && is_array($result['academic_guide'])) {
            $g = $result['academic_guide'];
            $guideHtml .= '<div class="guide-box">'
                . '<div><strong>Tujuan Pengujian:</strong> ' . e((string) ($g['tujuan'] ?? '')) . '</div>'
                . '<div><strong>Hipotesis:</strong> ' . e((string) ($g['h0'] ?? '')) . ' | ' . e((string) ($g['h1'] ?? '')) . '</div>'
                . '<div><strong>Dasar Keputusan:</strong> ' . e((string) ($g['kriteria'] ?? '')) . '</div>'
                . '<div style="color:#64748b; margin-top:2px;"><strong>Referensi Metode:</strong> ' . e((string) ($g['referensi'] ?? '')) . '</div>'
                . '</div>';
        }

        $hlHtml = '';
        if (!empty($result['highlights'])) {
            $hlHtml .= '<table class="hl-table"><tr>';
            foreach ($result['highlights'] as $h) {
                $hlHtml .= '<td><div class="hl-lbl">' . e((string) ($h['label'] ?? '')) . '</div>'
                    . '<div class="hl-val">' . e((string) ($h['value'] ?? '-')) . '</div></td>';
            }
            $hlHtml .= '</tr></table>';
        }

        $tablesHtml = '';
        foreach ($result['tables'] as $tbl) {
            $headers = $tbl['headers'] ?? [];
            $colAligns = $this->detectColumnAlignments($headers);

            $tablesHtml .= '<div class="tbl-block">';
            $tablesHtml .= '<div class="tbl-title">' . e((string) ($tbl['title'] ?? 'Tabel')) . '</div>';
            $tablesHtml .= '<table class="stat-table"><thead><tr>';
            foreach ($headers as $idx => $th) {
                $align = $colAligns[$idx] ?? 'center';
                $tablesHtml .= '<th style="text-align:' . $align . ';">' . e((string) $th) . '</th>';
            }
            $tablesHtml .= '</tr></thead><tbody>';
            foreach (($tbl['rows'] ?? []) as $tr) {
                $tablesHtml .= '<tr>';
                foreach ($tr as $idx => $cell) {
                    $align = $colAligns[$idx] ?? 'center';
                    $tablesHtml .= '<td style="text-align:' . $align . ';">' . e((string) $cell) . '</td>';
                }
                $tablesHtml .= '</tr>';
            }
            $tablesHtml .= '</tbody></table>';
            if (!empty($tbl['footnote'])) {
                $tablesHtml .= '<div class="tbl-note">' . e((string) $tbl['footnote']) . '</div>';
            }
            $tablesHtml .= '</div>';
        }

        $chartsHtml = '';
        if (!empty($chartsImages)) {
            $chartsHtml .= '<div class="sec-title">Visualisasi Grafik Hasil Pengujian</div>';
            foreach ($chartsImages as $idx => $img) {
                // Dompdf di Vercel tanpa ekstensi GD hanya mendukung JPEG dengan aman (PNG butuh GD untuk alpha channel)
                if ($img['ext'] === 'png' && !extension_loaded('gd')) {
                    continue;
                }
                $num = $idx + 1;
                $chartsHtml .= '<div class="chart-block">'
                    . '<img src="' . $img['data_url'] . '" style="width:100%; max-width:500px; height:auto; display:block; margin:0 auto;" />'
                    . '<div class="chart-cap">Gambar ' . $num . '. ' . e($img['title']) . '</div>'
                    . '</div>';
            }
        }

        $conclusionsHtml = '';
        if (!empty($result['conclusions'])) {
            $conclusionsHtml .= '<div class="conc-block"><div class="sec-title">Ringkasan Keputusan Statistik</div><ul class="conc-list">';
            foreach ($result['conclusions'] as $c) {
                $conclusionsHtml .= '<li>' . e((string) $c) . '</li>';
            }
            $conclusionsHtml .= '</ul></div>';
        }

        $narrationHtml = '';
        if ($narration !== '') {
            $cleanNarr = $this->cleanAcademicNarration($narration);
            $paras = preg_split('/\n{2,}/', $cleanNarr) ?: [];
            $formatted = '';
            foreach ($paras as $p) {
                $p = trim($p);
                if ($p === '') {
                    continue;
                }
                if (preg_match('/^(?:4\.\d+(?:\.\d+)?|[A-C]\.)\s+.{3,95}$/', $p)) {
                    $formatted .= '<div style="font-weight:700; color:#0f172a; margin:10px 0 4px;">' . e($p) . '</div>';
                } else {
                    $formatted .= '<p style="margin:0 0 8px; text-align:justify;">' . nl2br(e($p)) . '</p>';
                }
            }
            $narrationHtml = '<div class="sec-title">Narasi Interpretasi Bab IV</div><div class="narr-box">' . $formatted . '</div>';
        }

        $html = '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><style>'
            . '@page { margin: 16mm 15mm 16mm 15mm; }'
            . 'body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 8.8pt; line-height: 1.5; }'
            . '.header { border-bottom: 2px solid #1d4ed8; padding-bottom: 10px; margin-bottom: 14px; }'
            . '.brand { font-size: 13pt; font-weight: 700; color: #1d4ed8; }'
            . '.sub { font-size: 8pt; color: #475569; margin-top: 3px; }'
            . '.hl-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; background: #f8fafc; }'
            . '.hl-table td { border: 1px solid #cbd5e1; padding: 8px 10px; text-align: center; }'
            . '.hl-lbl { font-size: 7.4pt; color: #64748b; font-weight: 700; }'
            . '.hl-val { font-size: 10pt; font-weight: 700; color: #0f172a; margin-top: 2px; }'
            . '.guide-box { background: #f8fafc; border: 0.75pt solid #cbd5e1; border-left: 3pt solid #2563eb; padding: 8px 10px; margin-bottom: 12px; font-size: 7.8pt; line-height: 1.5; }'
            . '.tbl-block { page-break-inside: avoid; margin-bottom: 14px; }'
            . '.tbl-title { font-size: 9pt; font-weight: 700; color: #0f172a; margin: 10px 0 5px; }'
            . '.stat-table { width: 100%; border-collapse: collapse; font-size: 7.6pt; table-layout: auto; }'
            . '.stat-table th { background: #eff6ff; color: #1e3a8a; border: 0.75pt solid #bfdbfe; border-top: 1.5pt solid #1e3a8a; border-bottom: 1.2pt solid #1e3a8a; padding: 6px 6px; font-weight: 700; vertical-align: middle; }'
            . '.stat-table td { border: 0.6pt solid #cbd5e1; padding: 5px 6px; color: #1e293b; vertical-align: middle; }'
            . '.stat-table tbody tr:nth-child(even) td { background: #f8fafc; }'
            . '.stat-table tbody tr:last-child td { border-bottom: 1.5pt solid #1e3a8a; }'
            . '.tbl-note { font-size: 7.3pt; color: #475569; font-style: italic; margin-top: 4px; }'
            . '.chart-block { page-break-inside: avoid; border: 0.8pt solid #cbd5e1; padding: 10px 12px; margin-bottom: 12px; text-align: center; background: #ffffff; }'
            . '.chart-cap { font-size: 8pt; font-weight: 700; color: #334155; margin-top: 6px; }'
            . '.sec-title { font-size: 10pt; font-weight: 700; color: #1e293b; border-left: 3.5px solid #2563eb; padding-left: 8px; margin: 16px 0 8px; page-break-after: avoid; }'
            . '.conc-block { page-break-inside: avoid; }'
            . '.conc-list { margin: 0 0 10px 18px; padding: 0; font-size: 8.5pt; }'
            . '.conc-list li { margin-bottom: 4px; }'
            . '.narr-box { border: 1px solid #e2e8f0; background: #ffffff; padding: 10px 12px; text-align: justify; font-size: 8.8pt; line-height: 1.65; }'
            . '</style></head><body>'
            . '<div class="header">'
            . '<div class="brand">' . $title . '</div>'
            . '<div class="sub">Dataset: <strong>' . $datasetTitle . '</strong>' . ($majorLabel !== '' ? ' · Bidang Ilmu: <strong>' . $majorLabel . '</strong>' : '') . ' · Peneliti: ' . $userName . ' · Waktu Hitung: ' . $computedAt . '</div>'
            . ($subtitle !== '' ? '<div class="sub">' . $subtitle . '</div>' : '')
            . '</div>'
            . $hlHtml
            . $guideHtml
            . $tablesHtml
            . $chartsHtml
            . $conclusionsHtml
            . $narrationHtml
            . '</body></html>';

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('tempDir', sys_get_temp_dir());
        $dompdf = new Dompdf($options);
        try {
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
        } catch (\Throwable $e) {
            // Fallback tanpa blok gambar jika runtime tidak mendukung dekode gambar tertentu
            $htmlNoImg = preg_replace('/<div class="chart-block">[\s\S]*?<\/div>/', '', $html);
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml((string) $htmlNoImg, 'UTF-8');
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
        }

        $slug = Str::slug(Str::limit((string) ($result['dataset_title'] ?? 'statistik'), 40, '')) ?: 'statistik';
        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="Laporan_Olah_Data_' . $slug . '.pdf"',
        ]);
    }

    /**
     * Ekspor Laporan Hasil Olah Data Statistik ke Microsoft Word (.docx) Asli & Dapat Diedit (Lengkap Tabel SPSS, Grafik HD & Narasi Bab IV).
     */
    public function downloadReportDocx(Request $request)
    {
        $result = $request->input('result');
        if (is_string($result)) {
            $result = json_decode($result, true);
        }
        $narration = trim((string) $request->input('narration', ''));
        $chartsImages = $this->sanitizeChartImages($request->input('charts_images', []));

        if (!is_array($result) || empty($result['tables'])) {
            return response()->json(['status' => 'error', 'message' => 'Data laporan statistik tidak valid.'], 422);
        }

        // WAJIB di PHPWord: aktifkan XML escaping agar karakter '<', '>', '&' (cth: "Sig. < 0.001", "VIF < 10")
        // tidak merusak word/document.xml saat dibuka di Microsoft Word!
        Settings::setOutputEscapingEnabled(true);
        Settings::setTempDir(sys_get_temp_dir());

        $safeTxt = static function (mixed $v): string {
            $s = (string) ($v ?? '');
            return trim((string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $s));
        };

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(11);

        // Margin standar skripsi Indonesia (A4: 11906 x 16838 twips; Kiri 4cm=2268, Atas/Kanan/Bawah 3cm=1701 -> lebar konten = 7937 twips)
        $contentWidthTwips = 7937;
        $section = $phpWord->addSection([
            'pageSizeW' => 11906,
            'pageSizeH' => 16838,
            'marginTop' => 1701,
            'marginBottom' => 1701,
            'marginLeft' => 2268,
            'marginRight' => 1701,
        ]);

        $title = $safeTxt($result['title'] ?? 'Laporan Hasil Olah Data Statistik');
        $subtitle = $safeTxt($result['subtitle'] ?? '');
        $datasetTitle = $safeTxt($result['dataset_title'] ?? 'Data Penelitian');
        $majorLabel = $safeTxt($request->input('major', ''));
        $computedAt = $safeTxt($result['computed_at'] ?? now('Asia/Jakarta')->format('d M Y, H:i') . ' WIB');
        $userName = $safeTxt(Auth::user()->name ?? 'Mahasiswa');

        // Judul Laporan
        $section->addText(
            $title,
            ['name' => 'Times New Roman', 'size' => 14, 'bold' => true, 'color' => '1E3A8A'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 80]
        );
        $section->addText(
            "Dataset: {$datasetTitle}" . ($majorLabel !== '' ? " | Bidang: {$majorLabel}" : '') . " | Peneliti: {$userName} | {$computedAt}",
            ['name' => 'Times New Roman', 'size' => 10, 'color' => '475569'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 60]
        );
        if ($subtitle !== '') {
            $section->addText(
                $subtitle,
                ['name' => 'Times New Roman', 'size' => 10, 'italic' => true, 'color' => '475569'],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 180]
            );
        }

        if (!empty($result['equation'])) {
            $section->addText(
                'Persamaan Regresi: ' . $safeTxt($result['equation']),
                ['name' => 'Times New Roman', 'size' => 11, 'bold' => true, 'color' => '1D4ED8'],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 140]
            );
        }

        if (!empty($result['academic_guide']) && is_array($result['academic_guide'])) {
            $g = $result['academic_guide'];
            $section->addText(
                'Kerangka Pengujian & Hipotesis',
                ['name' => 'Times New Roman', 'size' => 11, 'bold' => true, 'color' => '0F172A'],
                ['spaceBefore' => 100, 'spaceAfter' => 60, 'keepNext' => true]
            );
            $section->addText('Tujuan: ' . $safeTxt($g['tujuan'] ?? ''), ['name' => 'Times New Roman', 'size' => 10.5], ['alignment' => Jc::BOTH, 'spaceAfter' => 40]);
            $section->addText('Hipotesis: ' . $safeTxt($g['h0'] ?? '') . ' | ' . $safeTxt($g['h1'] ?? ''), ['name' => 'Times New Roman', 'size' => 10.5], ['alignment' => Jc::BOTH, 'spaceAfter' => 40]);
            $section->addText('Dasar Pengambilan Keputusan: ' . $safeTxt($g['kriteria'] ?? ''), ['name' => 'Times New Roman', 'size' => 10.5], ['alignment' => Jc::BOTH, 'spaceAfter' => 40]);
            $section->addText('Referensi Metode: ' . $safeTxt($g['referensi'] ?? ''), ['name' => 'Times New Roman', 'size' => 9.5, 'italic' => true, 'color' => '475569'], ['spaceAfter' => 140]);
        }

        // Tabel-tabel Output SPSS Asli (100% Editable, dengan lebar kolom TWIP eksplisit agar kompatibel Word macOS & Windows)
        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '94A3B8',
            'cellMarginTop' => 65,
            'cellMarginBottom' => 65,
            'cellMarginLeft' => 75,
            'cellMarginRight' => 75,
            'width' => $contentWidthTwips,
            'unit' => TblWidth::TWIP,
            'layout' => TableStyle::LAYOUT_FIXED,
        ];

        foreach ($result['tables'] as $tbl) {
            $headers = $tbl['headers'] ?? [];
            $rows = $tbl['rows'] ?? [];
            $colCount = count($headers);
            if ($colCount === 0) {
                continue;
            }
            $colAligns = $this->detectColumnAlignments($headers);
            $colWidths = $this->computeWordColWidths($headers, $rows, $contentWidthTwips);
            $fontSz = $colCount >= 9 ? 8.0 : ($colCount >= 7 ? 8.5 : 9.5);

            $section->addText(
                $safeTxt($tbl['title'] ?? 'Tabel'),
                ['name' => 'Times New Roman', 'size' => 11, 'bold' => true, 'color' => '0F172A'],
                ['spaceBefore' => 160, 'spaceAfter' => 80, 'keepNext' => true]
            );

            $table = $section->addTable($tableStyle);
            $table->addRow(null, ['tblHeader' => true, 'cantSplit' => true]);
            foreach ($headers as $idx => $th) {
                $jc = ($colAligns[$idx] ?? 'center') === 'left' ? Jc::START : Jc::CENTER;
                $cell = $table->addCell($colWidths[$idx], ['bgColor' => 'EFF6FF', 'valign' => 'center']);
                $cell->addText(
                    $safeTxt($th),
                    ['name' => 'Times New Roman', 'size' => $fontSz, 'bold' => true, 'color' => '1E3A8A'],
                    ['alignment' => $jc, 'spaceAfter' => 0]
                );
            }

            foreach ($rows as $rIdx => $tr) {
                $table->addRow(null, ['cantSplit' => true]);
                $bg = ($rIdx % 2 === 1) ? 'F8FAFC' : 'FFFFFF';
                for ($idx = 0; $idx < $colCount; $idx++) {
                    $val = $tr[$idx] ?? '';
                    $jc = ($colAligns[$idx] ?? 'center') === 'left' ? Jc::START : Jc::CENTER;
                    $cell = $table->addCell($colWidths[$idx], ['bgColor' => $bg, 'valign' => 'center']);
                    $cell->addText(
                        $safeTxt($val),
                        ['name' => 'Times New Roman', 'size' => $fontSz, 'color' => '0F172A'],
                        ['alignment' => $jc, 'spaceAfter' => 0]
                    );
                }
            }

            if (!empty($tbl['footnote'])) {
                $section->addText(
                    'Catatan: ' . $safeTxt($tbl['footnote']),
                    ['name' => 'Times New Roman', 'size' => 9, 'italic' => true, 'color' => '475569'],
                    ['spaceBefore' => 60, 'spaceAfter' => 160]
                );
            }
        }

        // Grafik Statistik HD
        $tempFiles = [];
        try {
            if (!empty($chartsImages)) {
                $section->addText(
                    'Visualisasi Grafik Hasil Pengujian',
                    ['name' => 'Times New Roman', 'size' => 12, 'bold' => true, 'color' => '0F172A'],
                    ['spaceBefore' => 200, 'spaceAfter' => 120, 'keepNext' => true]
                );
                foreach ($chartsImages as $idx => $img) {
                    $rawBase64 = preg_replace('/^data:image\/(?:png|jpeg|jpg);base64,/', '', $img['data_url']);
                    $bin = base64_decode((string) $rawBase64, true);
                    if ($bin === false || strlen($bin) < 200) {
                        continue;
                    }
                    $ext = $img['ext'] === 'png' ? 'png' : 'jpg';
                    $tmpImg = sys_get_temp_dir() . '/odchart_' . uniqid('', true) . '.' . $ext;
                    file_put_contents($tmpImg, $bin);
                    $tempFiles[] = $tmpImg;

                    $section->addImage($tmpImg, [
                        'width' => 390,
                        'alignment' => Jc::CENTER,
                        'wrappingStyle' => 'inline',
                    ]);
                    $section->addText(
                        'Gambar ' . ($idx + 1) . '. ' . $safeTxt($img['title']),
                        ['name' => 'Times New Roman', 'size' => 10, 'bold' => true, 'color' => '334155'],
                        ['alignment' => Jc::CENTER, 'spaceBefore' => 60, 'spaceAfter' => 180]
                    );
                }
            }

            // Ringkasan Keputusan Statistik
            if (!empty($result['conclusions'])) {
                $section->addText(
                    'Ringkasan Keputusan Statistik',
                    ['name' => 'Times New Roman', 'size' => 12, 'bold' => true, 'color' => '0F172A'],
                    ['spaceBefore' => 180, 'spaceAfter' => 100, 'keepNext' => true]
                );
                foreach ($result['conclusions'] as $idx => $c) {
                    $section->addText(
                        ($idx + 1) . '. ' . $safeTxt($c),
                        ['name' => 'Times New Roman', 'size' => 11, 'color' => '0F172A'],
                        ['alignment' => Jc::BOTH, 'spaceAfter' => 70, 'lineHeight' => 1.35]
                    );
                }
            }

            // Narasi Interpretasi Bab IV (Bersih Tanpa Markdown)
            if ($narration !== '') {
                $section->addText(
                    'Narasi Interpretasi Bab IV',
                    ['name' => 'Times New Roman', 'size' => 12, 'bold' => true, 'color' => '0F172A'],
                    ['spaceBefore' => 220, 'spaceAfter' => 100, 'keepNext' => true]
                );
                $cleanNarr = $this->cleanAcademicNarration($narration);
                $paras = preg_split('/\n{2,}/', $cleanNarr) ?: [];
                foreach ($paras as $p) {
                    $p = $safeTxt($p);
                    if ($p === '') {
                        continue;
                    }
                    if (preg_match('/^(?:4\.\d+(?:\.\d+)?|[A-C]\.)\s+.{3,100}$/', $p)) {
                        $section->addText(
                            $p,
                            ['name' => 'Times New Roman', 'size' => 11.5, 'bold' => true, 'color' => '0F172A'],
                            ['spaceBefore' => 140, 'spaceAfter' => 80, 'keepNext' => true]
                        );
                    } else {
                        $section->addText(
                            (string) preg_replace('/\s*\n\s*/', ' ', $p),
                            ['name' => 'Times New Roman', 'size' => 12, 'color' => '0F172A'],
                            ['alignment' => Jc::BOTH, 'spaceAfter' => 120, 'lineHeight' => 1.5, 'indentation' => ['firstLine' => 567]]
                        );
                    }
                }
            }

            $tmpDocx = sys_get_temp_dir() . '/odreport_' . uniqid('', true) . '.docx';
            $tempFiles[] = $tmpDocx;
            $writer = IOFactory::createWriter($phpWord, 'Word2007');
            $writer->save($tmpDocx);
            $bytes = (string) file_get_contents($tmpDocx);

            $slug = Str::slug(Str::limit((string) ($result['dataset_title'] ?? 'statistik'), 40, '')) ?: 'statistik';
            return response($bytes, 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'Content-Disposition' => 'attachment; filename="Laporan_Olah_Data_' . $slug . '.docx"',
            ]);
        } finally {
            foreach ($tempFiles as $f) {
                if (is_file($f)) {
                    @unlink($f);
                }
            }
        }
    }

    private function detectColumnAlignments(array $headers): array
    {
        $aligns = [];
        foreach ($headers as $idx => $h) {
            $lh = strtolower(trim((string) $h));
            if (
                $idx === 0
                || str_contains($lh, 'keputusan')
                || str_contains($lh, 'status')
                || str_contains($lh, 'kategori')
                || str_contains($lh, 'kriteria')
                || str_contains($lh, 'nilai statistik')
                || str_contains($lh, 'arah')
                || str_contains($lh, 'distribusi')
                || str_contains($lh, 'perbandingan')
                || str_contains($lh, 'pasangan')
            ) {
                $aligns[$idx] = 'left';
            } else {
                $aligns[$idx] = 'center';
            }
        }
        return $aligns;
    }

    private function computeWordColWidths(array $headers, array $rows, int $totalTwips): array
    {
        $weights = [];
        foreach ($headers as $idx => $h) {
            $maxLen = mb_strlen((string) $h);
            foreach (array_slice($rows, 0, 25) as $r) {
                $maxLen = max($maxLen, mb_strlen((string) ($r[$idx] ?? '')));
            }
            $weights[$idx] = max(6, min(32, $maxLen));
        }
        $sum = max(1, array_sum($weights));
        $widths = [];
        $allocated = 0;
        $last = count($headers) - 1;
        foreach ($weights as $idx => $w) {
            if ($idx === $last) {
                $widths[$idx] = max(450, $totalTwips - $allocated);
            } else {
                $tw = max(450, (int) round(($w / $sum) * $totalTwips));
                $widths[$idx] = $tw;
                $allocated += $tw;
            }
        }
        return $widths;
    }

    private function sanitizeChartImages(mixed $rawImages): array
    {
        if (is_string($rawImages)) {
            $rawImages = json_decode($rawImages, true) ?: [];
        }
        if (!is_array($rawImages)) {
            return [];
        }
        $clean = [];
        foreach (array_slice($rawImages, 0, 5) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $url = (string) ($item['data_url'] ?? '');
            $ext = null;
            if (str_starts_with($url, 'data:image/jpeg;base64,') || str_starts_with($url, 'data:image/jpg;base64,')) {
                $ext = 'jpg';
            } elseif (str_starts_with($url, 'data:image/png;base64,')) {
                $ext = 'png';
            }
            if ($ext === null || strlen($url) > 2500000) {
                continue;
            }
            $clean[] = [
                'title' => trim((string) ($item['title'] ?? 'Grafik Statistik')),
                'data_url' => $url,
                'ext' => $ext,
            ];
        }
        return $clean;
    }


    // =========================================================================
    // HELPERS
    // =========================================================================

    private function parseCsvFile($file): array
    {
        if (!$file || !$file->isValid()) {
            return [[], []];
        }
        $raw = (string) file_get_contents($file->getRealPath());
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
        $lines = preg_split('/\r\n|\r|\n/', trim($raw));
        if (!$lines || count($lines) < 2) {
            return [[], []];
        }

        // Deteksi delimiter (; , atau tab)
        $first = $lines[0];
        $delim = ',';
        $counts = [',' => substr_count($first, ','), ';' => substr_count($first, ';'), "\t" => substr_count($first, "\t")];
        arsort($counts);
        $top = array_key_first($counts);
        if ($top !== null && $counts[$top] > 0) {
            $delim = $top;
        }

        $headers = array_map(fn($h) => trim((string) $h), str_getcsv($first, $delim));
        $headers = array_values(array_filter($headers, fn($h) => $h !== ''));
        $rows = [];
        for ($i = 1; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if ($line === '') {
                continue;
            }
            $vals = str_getcsv($line, $delim);
            $assoc = [];
            foreach ($headers as $idx => $h) {
                $assoc[$h] = isset($vals[$idx]) ? trim((string) $vals[$idx]) : '';
            }
            $rows[] = $assoc;
        }
        return [$headers, $rows];
    }

    private function extractJsonObject(string $raw): ?array
    {
        $clean = trim($raw);
        $clean = preg_replace('/^```(?:json)?\s*/i', '', $clean);
        $clean = preg_replace('/\s*```$/', '', (string) $clean);
        $decoded = json_decode((string) $clean, true);
        if (is_array($decoded)) {
            return $decoded;
        }
        $start = strpos((string) $clean, '{');
        $end = strrpos((string) $clean, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $slice = substr((string) $clean, $start, $end - $start + 1);
            $decoded = json_decode($slice, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return null;
    }

    private function buildFallbackInstrument(string $mode, string $topic, string $major, string $variables, int $itemsPerVar, string $scaleType): array
    {
        $parts = array_values(array_filter(array_map('trim', preg_split('/[,;\n]+/', $variables))));
        if (empty($parts)) {
            $parts = ['Variabel X1', 'Variabel Y'];
        }

        if ($mode === 'eksperimen') {
            $params = [];
            foreach ($parts as $idx => $p) {
                $code = Str::slug($p, '_');
                if ($code === '') {
                    $code = 'Parameter_' . ($idx + 1);
                }
                $params[] = [
                    'code' => $code,
                    'name' => $p,
                    'unit' => 'Satuan Ukur',
                    'method' => 'Pengukuran Laboratorium / Alat Ukur Terkalibrasi',
                ];
            }
            return [
                'instrument_title' => "Lembar Pencatatan Data Eksperimen: {$topic}",
                'scale_guide' => 'Catat hasil pengukuran numerik riil untuk setiap perlakuan dan ulangan sampel.',
                'recommended_test' => 'One-Way ANOVA + Uji Homogenitas Levene & Post-Hoc Bonferroni',
                'treatments' => ['Kontrol_0%', 'Variasi_1', 'Variasi_2', 'Variasi_3'],
                'replications' => max(3, $itemsPerVar),
                'parameters' => $params,
                'protocol_steps' => [
                    'Siapkan benda uji / sampel sesuai variasi perlakuan dan beri kode identifikasi.',
                    'Lakukan pengujian sesuai standar (SNI / ASTM / SOP Lab) dengan alat terkalibrasi.',
                    'Catat angka hasil pengukuran pada template Excel/CSV, lalu unggah kembali ke tab Olah Data.',
                ],
            ];
        }

        $varBlocks = [];
        $count = count($parts);
        foreach ($parts as $idx => $vName) {
            $isLast = ($idx === $count - 1 && $count >= 2);
            $code = $isLast ? 'Y' : ('X' . ($idx + 1));
            $items = [];
            for ($k = 1; $k <= $itemsPerVar; $k++) {
                $items[] = [
                    'code' => "{$code}_{$k}",
                    'indicator' => "Indikator {$k} {$vName}",
                    'statement' => "Saya menilai aspek indikator {$k} pada {$vName} dalam konteks {$topic} telah berjalan dengan baik.",
                ];
            }
            $varBlocks[] = [
                'code' => $code,
                'name' => $vName,
                'role' => $isLast ? 'Dependen' : 'Independen',
                'indicators' => ["Indikator 1 {$vName}", "Indikator 2 {$vName}"],
                'items' => $items,
            ];
        }

        return [
            'instrument_title' => "Kuesioner Penelitian ({$major}): {$topic}",
            'scale_guide' => $scaleType . ' (1 = Sangat Tidak Setuju s.d. 5 = Sangat Setuju)',
            'recommended_test' => 'Uji Validitas & Reliabilitas -> Uji Asumsi Klasik & Regresi Linier',
            'variables' => $varBlocks,
        ];
    }

    private function buildTemplateSheet(string $mode, array $parsed, int $itemsPerVar): array
    {
        if ($mode === 'eksperimen') {
            $headers = ['Kode_Sampel', 'Perlakuan'];
            $params = $parsed['parameters'] ?? [];
            foreach ($params as $p) {
                $c = preg_replace('/[^A-Za-z0-9_]/', '_', (string) ($p['code'] ?? 'Nilai_Ukur'));
                $headers[] = $c ?: 'Nilai_Ukur';
            }
            if (count($headers) < 3) {
                $headers[] = 'Hasil_Pengukuran';
            }
            $treatments = !empty($parsed['treatments']) ? (array) $parsed['treatments'] : ['Kontrol', 'Variasi_1', 'Variasi_2'];
            $reps = max(3, min(15, (int) ($parsed['replications'] ?? $itemsPerVar)));
            $rows = [];
            $no = 1;
            foreach ($treatments as $tr) {
                for ($r = 1; $r <= $reps; $r++) {
                    $row = ['S-' . str_pad((string) $no++, 2, '0', STR_PAD_LEFT), (string) $tr];
                    for ($c = 2; $c < count($headers); $c++) {
                        $row[] = '';
                    }
                    $rows[] = $row;
                }
            }
            return [$headers, $rows];
        }

        // Kuesioner Likert
        $headers = ['Responden'];
        foreach (($parsed['variables'] ?? []) as $var) {
            foreach (($var['items'] ?? []) as $it) {
                $code = preg_replace('/[^A-Za-z0-9_]/', '_', (string) ($it['code'] ?? ''));
                if ($code !== '') {
                    $headers[] = $code;
                }
            }
        }
        $rows = [];
        for ($i = 1; $i <= 30; $i++) {
            $row = ['R-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT)];
            for ($c = 1; $c < count($headers); $c++) {
                $row[] = '';
            }
            $rows[] = $row;
        }
        return [$headers, $rows];
    }
}
