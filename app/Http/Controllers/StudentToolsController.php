<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\AIManagerService;
use Dompdf\Dompdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\SimpleType\Jc;

class StudentToolsController extends Controller
{
    protected AIManagerService $ai;

    public function __construct(AIManagerService $ai)
    {
        $this->ai = $ai;
    }

    public function index()
    {
        $projectCount = 0;
        if (Auth::check()) {
            try {
                $projectCount = Project::where('user_id', Auth::id())->count();
            } catch (\Throwable $e) {
                $projectCount = 0;
            }
        }

        return view('tools', compact('projectCount'));
    }

    /**
     * Generate langkah-langkah Flowchart Skripsi / Proposal terstruktur menggunakan AI
     */
    public function generateFlowchart(Request $request)
    {
        $request->validate([
            'topic' => 'required|string|max:300',
            'kind' => 'nullable|string|in:prosedur,kerangka,sistem,analisis',
        ]);

        $topic = trim((string) $request->input('topic'));
        $kind = (string) $request->input('kind', 'prosedur');

        $kindLabelMap = [
            'prosedur' => 'Flowchart Prosedur / Tahapan Penelitian (Bab III)',
            'kerangka' => 'Bagan Kerangka Berpikir / Konseptual (Bab II)',
            'sistem' => 'Flowchart Alur Kerja Sistem / Aplikasi (R&D / Informatika)',
            'analisis' => 'Flowchart Pengumpulan & Analisis Data Penelitian',
        ];
        $kindLabel = $kindLabelMap[$kind] ?? $kindLabelMap['prosedur'];

        $captionMap = [
            'prosedur' => 'Gambar 3.1 Diagram Alur Prosedur Penelitian',
            'kerangka' => 'Gambar 2.1 Bagan Kerangka Berpikir Penelitian',
            'sistem' => 'Gambar 3.2 Flowchart Alur Kerja Sistem yang Diusulkan',
            'analisis' => 'Gambar 3.1 Alur Pengumpulan dan Analisis Data',
        ];
        $defaultCaption = $captionMap[$kind] ?? 'Gambar 3.1 Diagram Alur Penelitian';

        $prompt = '';
        if ($kind === 'kerangka') {
            $prompt = "Buat rancangan Diagram Kerangka Berpikir / Kerangka Konseptual (Bab II) untuk judul/topik skripsi mahasiswa Indonesia berikut:\n"
                . "Topik: \"{$topic}\"\n\n"
                . "Kembalikan HANYA JSON objek valid (TANPA markdown ```json, TANPA penjelasan tambahan) dengan struktur:\n"
                . "{\n"
                . "  \"layout\": \"regresi|mediasi|bertingkat\",\n"
                . "  \"x_vars\": [{\"code\": \"X1\", \"name\": \"Nama Variabel Independen 1\", \"indicators\": \"Indikator singkat (opsional)\"}],\n"
                . "  \"mediator\": {\"code\": \"Z\", \"name\": \"Nama Variabel Mediasi (isi null jika layout bukan mediasi)\"},\n"
                . "  \"y_var\": {\"code\": \"Y\", \"name\": \"Nama Variabel Dependen / Output\", \"indicators\": \"Indikator singkat\"},\n"
                . "  \"hypotheses\": [\"H1\", \"H2\", \"H3\"],\n"
                . "  \"simultaneous\": true,\n"
                . "  \"stages\": [\"Kondisi Awal / Fenomena\", \"Landasan Teori & Kajian\", \"Penerapan Metode / Model\", \"Hasil & Luaran Akhir\"]\n"
                . "}\n"
                . "Aturan: Pilih layout \"regresi\" jika ada 1-4 variabel X ke Y; \"mediasi\" jika ada variabel intervening/mediasi Z; \"bertingkat\" jika topik kualitatif/perancangan teknik/eksperimen non-variabel X-Y. Semua nama variabel wajib spesifik sesuai topik \"{$topic}\".";
        } elseif ($kind === 'sistem') {
            $prompt = "Buat rancangan Diagram Arsitektur Sistem / Aplikasi (IT & R&D) untuk topik skripsi:\n"
                . "Topik: \"{$topic}\"\n\n"
                . "Kembalikan HANYA JSON objek valid (TANPA markdown ```json) dengan struktur:\n"
                . "{\n"
                . "  \"actor\": \"Nama Pengguna / Aktor Utama (maks 28 karakter)\",\n"
                . "  \"frontend\": \"Nama Antarmuka / Client App (maks 34 karakter)\",\n"
                . "  \"frontend_sub\": \"Teknologi / fitur singkat (maks 38 karakter)\",\n"
                . "  \"backend\": \"Nama Server / Engine / Model AI (maks 34 karakter)\",\n"
                . "  \"backend_sub\": \"Proses inti / algoritma (maks 38 karakter)\",\n"
                . "  \"database\": \"Nama Database / Dataset (maks 32 karakter)\",\n"
                . "  \"database_sub\": \"Data yang disimpan (maks 36 karakter)\",\n"
                . "  \"req_label\": \"Label panah User ke Frontend (cth: Input Data)\",\n"
                . "  \"api_label\": \"Label panah Frontend ke Backend (cth: HTTP Request)\",\n"
                . "  \"db_label\": \"Label panah Backend ke Database (cth: Query / Simpan)\",\n"
                . "  \"output\": \"Hasil Akhir ke Pengguna (maks 34 karakter)\"\n"
                . "}";
        } elseif ($kind === 'analisis') {
            $prompt = "Buat rancangan Diagram Pipeline Pengumpulan & Analisis Data (Bab III) untuk topik skripsi:\n"
                . "Topik: \"{$topic}\"\n\n"
                . "Kembalikan HANYA JSON array valid berisi 4 atau 5 objek tahap berurutan (TANPA markdown ```json):\n"
                . "[{\"title\": \"Judul Tahap (maks 28 karakter)\", \"details\": [\"Poin metode 1\", \"Poin metode 2\", \"Poin metode 3\"]}]\n"
                . "Sesuaikan 100% dengan bidang topik \"{$topic}\" (misal Teknik Sipil: Pengambilan Sampel Material -> Uji Lab -> Analisis Kuat Tekan -> Evaluasi SNI; Manajemen: Kuesioner -> Uji Validitas -> Regresi SPSS -> Kesimpulan).";
        } else {
            $prompt = "Buat rancangan {$kindLabel} untuk judul/topik skripsi mahasiswa Indonesia berikut:\n"
                . "Topik: \"{$topic}\"\n\n"
                . "Kembalikan HANYA JSON array valid berisi 6 sampai 8 objek langkah berurutan (TANPA markdown ```json, TANPA penjelasan tambahan).\n"
                . "Format tiap objek:\n"
                . "{\"type\": \"start|io|process|decision|end\", \"label\": \"Teks singkat maksimal 55 karakter\", \"no_label\": \"opsional khusus type decision\"}\n\n"
                . "Aturan wajib:\n"
                . "1. Langkah pertama wajib type \"start\" (label \"Mulai\").\n"
                . "2. Langkah terakhir wajib type \"end\" (label \"Selesai\").\n"
                . "3. Gunakan minimal 1 langkah type \"io\" dan maksimal 1 langkah type \"decision\" dengan \"no_label\" singkat.\n"
                . "4. Sebutkan variabel, metode, atau objek spesifik dari topik \"{$topic}\" agar tidak generik.";
        }

        $diagramData = null;
        $steps = [];
        try {
            $raw = trim((string) $this->ai->generateText($prompt, 'auto'));
            $raw = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $raw);

            if ($kind === 'kerangka' || $kind === 'sistem') {
                if (preg_match('/\{[\s\S]*\}/', $raw, $m)) {
                    $decoded = json_decode($m[0], true);
                    if (is_array($decoded)) {
                        $diagramData = $decoded;
                    }
                }
            } elseif ($kind === 'analisis') {
                if (preg_match('/\[[\s\S]*\]/', $raw, $m)) {
                    $decoded = json_decode($m[0], true);
                    if (is_array($decoded) && count($decoded) >= 3) {
                        $diagramData = array_slice($decoded, 0, 5);
                    }
                }
            } else {
                if (preg_match('/\[[\s\S]*\]/', $raw, $m)) {
                    $decoded = json_decode($m[0], true);
                    if (is_array($decoded)) {
                        foreach ($decoded as $row) {
                            if (!is_array($row)) {
                                continue;
                            }
                            $type = strtolower(trim((string) ($row['type'] ?? 'process')));
                            if (!in_array($type, ['start', 'io', 'process', 'decision', 'end'], true)) {
                                $type = 'process';
                            }
                            $label = trim((string) ($row['label'] ?? ''));
                            if ($label === '') {
                                continue;
                            }
                            $step = [
                                'type' => $type,
                                'label' => Str::limit($label, 72, ''),
                            ];
                            if ($type === 'decision') {
                                $step['no_label'] = Str::limit(trim((string) ($row['no_label'] ?? 'Revisi / Ulangi')), 28, '');
                            }
                            $steps[] = $step;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // Fallback deterministik di bawah
        }

        $shortTopic = Str::limit($topic, 38, '');
        if ($kind === 'kerangka' && !$diagramData) {
            $diagramData = [
                'layout' => 'regresi',
                'x_vars' => [
                    ['code' => 'X1', 'name' => 'Faktor Utama 1 (' . Str::limit($topic, 24, '') . ')', 'indicators' => 'Indikator 1, Indikator 2'],
                    ['code' => 'X2', 'name' => 'Faktor Pendukung 2', 'indicators' => 'Indikator 3, Indikator 4'],
                ],
                'mediator' => null,
                'y_var' => ['code' => 'Y', 'name' => 'Variabel Terikat / Kinerja', 'indicators' => 'Hasil & Evaluasi'],
                'hypotheses' => ['H1', 'H2'],
                'simultaneous' => true,
                'stages' => ['Fenomena Lapangan', 'Kajian Teori & Literatur', 'Analisis ' . $shortTopic, 'Temuan & Rekomendasi'],
            ];
        } elseif ($kind === 'sistem' && !$diagramData) {
            $diagramData = [
                'actor' => 'Pengguna / Peneliti',
                'frontend' => 'Antarmuka Aplikasi Web/Mobile',
                'frontend_sub' => 'Input & Visualisasi Hasil',
                'backend' => 'Mesin Proses: ' . Str::limit($topic, 24, ''),
                'backend_sub' => 'API, Logika & Pemodelan',
                'database' => 'Basis Data / Dataset',
                'database_sub' => 'MySQL / Repositori Data',
                'req_label' => 'Input Data',
                'api_label' => 'Request / Proses',
                'db_label' => 'Simpan / Query',
                'output' => 'Laporan & Hasil Analisis',
            ];
        } elseif ($kind === 'analisis' && !$diagramData) {
            $diagramData = [
                ['title' => '1. Sumber & Objek Data', 'details' => ['Data Primer / Lapangan', 'Data Sekunder / Literatur', 'Topik: ' . Str::limit($topic, 26, '')]],
                ['title' => '2. Pengumpulan Data', 'details' => ['Instrumen / Pengujian Lab', 'Dokumentasi & Observasi', 'Tabulasi Awal']],
                ['title' => '3. Uji Validitas & Asumsi', 'details' => ['Pembersihan Data', 'Uji Validitas / Kalibrasi', 'Uji Asumsi Dasar']],
                ['title' => '4. Analisis & Evaluasi', 'details' => ['Perhitungan Statistik / Model', 'Uji Hipotesis / Standar', 'Interpretasi & Kesimpulan']],
            ];
        } elseif ($kind === 'prosedur' && count($steps) < 4) {
            $steps = [
                ['type' => 'start', 'label' => 'Mulai'],
                ['type' => 'process', 'label' => 'Identifikasi Masalah & Studi Literatur'],
                ['type' => 'io', 'label' => 'Pengumpulan Data: ' . $shortTopic],
                ['type' => 'decision', 'label' => 'Data Valid & Memenuhi Syarat?', 'no_label' => 'Revisi / Uji Ulang'],
                ['type' => 'process', 'label' => 'Pengolahan & Analisis Data Penelitian'],
                ['type' => 'process', 'label' => 'Pembahasan Hasil & Penarikan Kesimpulan'],
                ['type' => 'end', 'label' => 'Selesai'],
            ];
        }

        return response()->json([
            'status' => 'success',
            'kind' => $kind,
            'caption' => $defaultCaption,
            'steps' => array_slice($steps, 0, 10),
            'diagram' => $diagramData,
        ]);
    }

    /**
     * Konversi Word (.docx) -> PDF (.pdf) ukuran A4 via ZipArchive XML Parser + Dompdf
     * Mendukung heading, paragraf, bold/italic/underline, list, tabel (colspan), gambar tertanam, dan nomor halaman opsional.
     */
    public function docxToPdf(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:4096',
        ]);

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());
        if ($ext !== 'docx') {
            return response()->json([
                'status' => 'error',
                'message' => 'Format file harus .docx (Microsoft Word).',
            ], 422);
        }

        $origName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) ?: 'Dokumen';
        $safeBase = Str::slug(substr($origName, 0, 40), '_') ?: 'Dokumen';

        $bodyHtml = $this->parseDocxToHtml($file->getRealPath());
        if (trim(strip_tags($bodyHtml, '<img>')) === '') {
            return response()->json([
                'status' => 'error',
                'message' => 'Isi dokumen Word kosong atau tidak dapat dibaca.',
            ], 422);
        }

        $marginPreset = $request->input('margin', 'standard');
        $addPageNumbers = $request->boolean('page_numbers', false);
        $marginCss = $marginPreset === 'skripsi'
            ? '4cm 3cm 3cm 4cm'
            : '2.54cm 2.54cm 2.54cm 2.54cm';
        $lineHeightCss = $marginPreset === 'skripsi' ? '1.5' : '1.4';

        $html = "<!DOCTYPE html><html lang='id'><head><meta charset='UTF-8'><style>
            @page { size: A4; margin: {$marginCss}; }
            body {
                font-family: 'DejaVu Serif', 'Times New Roman', serif;
                font-size: 11.5pt;
                line-height: {$lineHeightCss};
                color: #0f172a;
                text-align: justify;
            }
            h1 { font-size: 14pt; font-weight: bold; margin: 0 0 10pt 0; text-align: center; page-break-after: avoid; }
            h2 { font-size: 12.5pt; font-weight: bold; margin: 14pt 0 6pt 0; text-align: left; page-break-after: avoid; }
            h3 { font-size: 11.5pt; font-weight: bold; margin: 10pt 0 4pt 0; text-align: left; page-break-after: avoid; }
            p { margin: 0 0 6pt 0; }
            p.align-center { text-align: center; text-indent: 0; }
            p.align-right { text-align: right; text-indent: 0; }
            p.align-left { text-align: left; }
            p.indent-first { text-indent: 1.27cm; }
            p.spacer { margin: 0 0 8pt 0; height: 10pt; }
            p.list-item { margin: 0 0 4pt 0; padding-left: 18pt; text-indent: -10pt; }
            .doc-img-wrap { text-align: center; margin: 10pt 0; page-break-inside: avoid; }
            .doc-img-wrap img { max-width: 100%; max-height: 18cm; width: auto; height: auto; }
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 8pt 0 12pt 0;
                font-size: 10pt;
                table-layout: fixed;
            }
            th, td {
                border: 0.75pt solid #334155;
                padding: 5pt 6pt;
                vertical-align: top;
                word-wrap: break-word;
                overflow-wrap: break-word;
                text-align: left;
            }
            th { background: #f1f5f9; font-weight: bold; text-align: center; }
            table.borderless, table.borderless tr, table.borderless td, table.borderless th {
                border: none !important;
                background: transparent !important;
                padding: 4pt 6pt;
            }
            .td-center { text-align: center !important; }
            .td-right { text-align: right !important; }
            .page-break { page-break-before: always; height: 0; margin: 0; padding: 0; }
        </style></head><body>{$bodyHtml}</body></html>";

        $tmpDir = rtrim(sys_get_temp_dir(), '/\\');
        $dompdf = new Dompdf();
        $dompdf->set_option('isHtml5ParserEnabled', true);
        $dompdf->set_option('isRemoteEnabled', false);
        $dompdf->set_option('tempDir', $tmpDir);
        $dompdf->set_option('fontCache', $tmpDir);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        try {
            $dompdf->render();
        } catch (\Throwable $e) {
            // Fallback jika gambar non-JPEG memicu kendala tanpa ekstensi GD
            $fallbackHtml = preg_replace('/<img[^>]*>/i', '', $html);
            $dompdf = new Dompdf();
            $dompdf->set_option('isHtml5ParserEnabled', true);
            $dompdf->set_option('isRemoteEnabled', false);
            $dompdf->set_option('tempDir', $tmpDir);
            $dompdf->set_option('fontCache', $tmpDir);
            $dompdf->loadHtml($fallbackHtml);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
        }

        if ($addPageNumbers) {
            try {
                $canvas = $dompdf->getCanvas();
                $canvas->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) {
                    $font = $fontMetrics->getFont('Times-Roman', 'normal');
                    $txt = (string) $pageNumber;
                    $tw = $fontMetrics->getTextWidth($txt, $font, 11);
                    $canvas->text((595.28 - $tw) / 2, 800, $txt, $font, 11, [0, 0, 0]);
                });
            } catch (\Throwable $e) {
            }
        }

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $safeBase . '.pdf"',
            'Cache-Control' => 'no-store',
        ]);
    }

    /**
     * Konversi PDF -> Word (.docx) dari hasil ekstraksi halaman PDF.js di browser
     * Mendukung blok heading, sub-heading, daftar bernomor/bullet, paragraf akademik, dan batas halaman.
     */
    public function pdfToDocx(Request $request)
    {
        $request->validate([
            'filename' => 'nullable|string|max:160',
            'pages' => 'required|array|min:1|max:250',
            'academic_format' => 'nullable|boolean',
        ]);

        $origName = pathinfo((string) $request->input('filename', 'Dokumen'), PATHINFO_FILENAME) ?: 'Dokumen';
        $safeBase = Str::slug(substr($origName, 0, 40), '_') ?: 'Dokumen';
        $academic = $request->boolean('academic_format', false);
        $pages = $request->input('pages', []);

        if (ob_get_length()) {
            ob_end_clean();
        }
        $tmpDir = rtrim(sys_get_temp_dir(), '/\\');
        Settings::setTempDir($tmpDir);
        Settings::setOutputEscapingEnabled(true);
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(12);

        $sectionStyle = $academic
            ? [
                'paperSize' => 'A4',
                'marginTop' => 2268,    // 4 cm
                'marginLeft' => 2268,   // 4 cm
                'marginBottom' => 1701, // 3 cm
                'marginRight' => 1701,  // 3 cm
            ]
            : [
                'paperSize' => 'A4',
                'marginTop' => 1440,    // 2.54 cm
                'marginLeft' => 1440,
                'marginBottom' => 1440,
                'marginRight' => 1440,
            ];

        $section = $phpWord->addSection($sectionStyle);

        foreach ($pages as $pIdx => $page) {
            if ($pIdx > 0) {
                $section->addPageBreak();
            }

            $blocks = is_array($page['blocks'] ?? null) ? $page['blocks'] : [];
            if (empty($blocks) && is_array($page['paragraphs'] ?? null)) {
                foreach ($page['paragraphs'] as $rawPara) {
                    $blocks[] = ['type' => 'paragraph', 'text' => (string) $rawPara];
                }
            }
            if (empty($blocks) && !empty($page['text'])) {
                foreach (array_filter(array_map('trim', preg_split('/\R{2,}/', (string) $page['text']))) as $rawPara) {
                    $blocks[] = ['type' => 'paragraph', 'text' => $rawPara];
                }
            }

            foreach ($blocks as $blk) {
                $rawText = (string) ($blk['text'] ?? '');
                if (function_exists('iconv')) {
                    $rawText = (string) (@iconv('UTF-8', 'UTF-8//IGNORE', $rawText) ?: $rawText);
                }
                // Hapus karakter kontrol & non-karakter XML 1.0 agar file .docx selalu valid dibuka di Microsoft Word
                $clean = trim(preg_replace('/[^\x{0009}\x{000A}\x{000D}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $rawText));
                if ($clean === '') {
                    continue;
                }

                $type = (string) ($blk['type'] ?? 'paragraph');
                $isMainHeading = $type === 'heading'
                    || (preg_match('/^(?:BAB\s+[IVX]+|DAFTAR\s+[A-Z]+|KATA\s+PENGANTAR|ABSTRAK|ABSTRACT|LAMPIRAN)\b/i', $clean) && mb_strlen($clean) <= 95);
                $isSubHeading = !$isMainHeading && (
                    $type === 'subheading'
                    || (preg_match('/^(?:\d+\.\d+(?:\.\d+)?\.?\s+|[A-Z]\.\s+)/', $clean) && mb_strlen($clean) <= 115)
                );
                $isList = !$isMainHeading && !$isSubHeading && ($type === 'list' || preg_match('/^(?:\d+[\.\)]\s+|[•\-–]\s+)/u', $clean));

                if ($isMainHeading) {
                    $section->addText(
                        $clean,
                        ['name' => 'Times New Roman', 'size' => 14, 'bold' => true],
                        ['alignment' => Jc::CENTER, 'spaceBefore' => 120, 'spaceAfter' => 160, 'lineHeight' => 1.15]
                    );
                } elseif ($isSubHeading) {
                    $section->addText(
                        $clean,
                        ['name' => 'Times New Roman', 'size' => 12, 'bold' => true],
                        ['alignment' => Jc::LEFT, 'spaceBefore' => 140, 'spaceAfter' => 80, 'lineHeight' => 1.25]
                    );
                } elseif ($isList) {
                    $section->addText(
                        $clean,
                        ['name' => 'Times New Roman', 'size' => 12],
                        [
                            'alignment' => Jc::BOTH,
                            'spaceAfter' => 60,
                            'lineHeight' => $academic ? 1.5 : 1.2,
                            'indentation' => ['left' => 420, 'hanging' => 240],
                        ]
                    );
                } else {
                    $section->addText(
                        $clean,
                        ['name' => 'Times New Roman', 'size' => 12],
                        [
                            'alignment' => Jc::BOTH,
                            'spaceAfter' => 100,
                            'lineHeight' => $academic ? 1.5 : 1.2,
                            'indentation' => $academic ? ['firstLine' => 720] : [],
                        ]
                    );
                }
            }
        }

        $filename = $safeBase . '.docx';
        $tmpPath = $tmpDir . '/tool_pdf2docx_' . Str::random(10) . '.docx';
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tmpPath);

        return response()->download($tmpPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Cache-Control' => 'no-store',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Ekstrak teks dari file .docx untuk fitur Hitung Kata / Estimasi Halaman A4
     */
    public function extractDocxText(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:4096',
        ]);

        $file = $request->file('file');
        if (strtolower($file->getClientOriginalExtension()) !== 'docx') {
            return response()->json(['status' => 'error', 'message' => 'File harus berformat .docx'], 422);
        }

        $html = $this->parseDocxToHtml($file->getRealPath(), false);
        $text = html_entity_decode(strip_tags(str_replace(['</p>', '</h1>', '</h2>', '</h3>', '</tr>'], "\n\n", $html)), ENT_QUOTES, 'UTF-8');
        $text = preg_replace("/\n{3,}/", "\n\n", trim($text));

        return response()->json([
            'status' => 'success',
            'text' => $text,
        ]);
    }

    /**
     * Parser word/document.xml -> HTML bersih (Heading, Paragraf, Bold/Italic/Underline, List, Tabel + Colspan, Gambar Tertanam, Page Break)
     */
    protected function parseDocxToHtml(string $filePath, bool $includeImages = true): string
    {
        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            return '';
        }

        $xmlContent = $zip->getFromName('word/document.xml');
        if (!$xmlContent) {
            $zip->close();
            return '';
        }

        // Peta relasi gambar (rId -> data URI) agar logo kampus / foto lampiran di .docx ikut tampil di PDF
        $imageMap = [];
        if ($includeImages) {
            $relsXml = $zip->getFromName('word/_rels/document.xml.rels');
            if ($relsXml) {
                $relsDom = new \DOMDocument();
                $prevErr = libxml_use_internal_errors(true);
                $relsDom->loadXML($relsXml);
                libxml_clear_errors();
                libxml_use_internal_errors($prevErr);

                $hasGd = extension_loaded('gd') && function_exists('imagecreatefrompng');
                foreach ($relsDom->getElementsByTagName('Relationship') as $rel) {
                    $type = (string) $rel->getAttribute('Type');
                    if (!str_contains($type, '/image')) {
                        continue;
                    }
                    $rId = (string) $rel->getAttribute('Id');
                    $target = ltrim((string) $rel->getAttribute('Target'), '/');
                    $mediaPath = str_starts_with($target, 'word/') ? $target : 'word/' . $target;
                    $imgBytes = $zip->getFromName($mediaPath);
                    if (!$imgBytes || strlen($imgBytes) > 2 * 1024 * 1024) {
                        continue;
                    }
                    $ext = strtolower(pathinfo($mediaPath, PATHINFO_EXTENSION));
                    $isJpeg = in_array($ext, ['jpg', 'jpeg'], true) || str_starts_with($imgBytes, "\xFF\xD8\xFF");
                    if ($isJpeg) {
                        $imageMap[$rId] = 'data:image/jpeg;base64,' . base64_encode($imgBytes);
                    } elseif ($hasGd && in_array($ext, ['png', 'gif', 'webp'], true)) {
                        $mime = $ext === 'png' ? 'image/png' : ($ext === 'gif' ? 'image/gif' : 'image/webp');
                        $imageMap[$rId] = "data:{$mime};base64," . base64_encode($imgBytes);
                    }
                }
            }
        }
        $zip->close();

        $dom = new \DOMDocument();
        $prevErr = libxml_use_internal_errors(true);
        $dom->loadXML($xmlContent);
        libxml_clear_errors();
        libxml_use_internal_errors($prevErr);

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $xpath->registerNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');
        $xpath->registerNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');

        $body = $xpath->query('/w:document/w:body')->item(0);
        if (!$body) {
            return '';
        }

        $html = '';
        foreach ($body->childNodes as $node) {
            if ($node->localName === 'p') {
                $html .= $this->renderWordParagraphNode($xpath, $node, $imageMap);
            } elseif ($node->localName === 'tbl') {
                $html .= $this->renderWordTableNode($xpath, $node, $imageMap);
            }
        }

        // Batasi paragraf kosong beruntun (maks 2) dan hindari spacer menggantung tepat sebelum/sesudah page-break
        $html = (string) preg_replace('/(<p class=\'spacer\'>&nbsp;<\/p>){3,}/', '<p class=\'spacer\'>&nbsp;</p><p class=\'spacer\'>&nbsp;</p>', $html);
        $html = (string) preg_replace('/(?:<p class=\'spacer\'>&nbsp;<\/p>)+\s*<div class=\'page-break\'><\/div>/', '<div class=\'page-break\'></div>', $html);
        $html = (string) preg_replace('/<div class=\'page-break\'><\/div>\s*(?:<p class=\'spacer\'>&nbsp;<\/p>)+/', '<div class=\'page-break\'></div>', $html);
        $html = (string) preg_replace('/(<div class=\'page-break\'><\/div>){2,}/', '<div class=\'page-break\'></div>', $html);
        $html = (string) preg_replace('/(?:<div class=\'page-break\'><\/div>|<p class=\'spacer\'>&nbsp;<\/p>)+$/', '', $html);

        return $html;
    }

    protected function renderWordParagraphNode(\DOMXPath $xpath, \DOMNode $pNode, array $imageMap = []): string
    {
        $styleId = '';
        $styleNode = $xpath->query('.//w:pPr/w:pStyle/@w:val', $pNode)->item(0);
        if ($styleNode) {
            $styleId = strtolower(trim($styleNode->nodeValue));
        }

        $align = '';
        $jcNode = $xpath->query('.//w:pPr/w:jc/@w:val', $pNode)->item(0);
        if ($jcNode) {
            $align = strtolower(trim($jcNode->nodeValue));
        }

        $isListPara = $xpath->query('./w:pPr/w:numPr', $pNode)->length > 0;
        $hasPageBreak = $xpath->query('.//w:br[@w:type="page"]', $pNode)->length > 0
            || $xpath->query('.//w:pPr/w:pageBreakBefore', $pNode)->length > 0;
        // Section break pada akhir paragraf (pemisah Sampul, Pengesahan, Daftar Isi, Bab I-V di Word)
        $sectPrNode = $xpath->query('./w:pPr/w:sectPr', $pNode)->item(0);
        $sectType = $sectPrNode ? strtolower(trim((string) ($xpath->query('./w:type/@w:val', $sectPrNode)->item(0)?->nodeValue ?? ''))) : '';
        $hasSectionBreakAfter = $sectPrNode !== null && $sectType !== 'continuous';
        $firstLineIndentNode = $xpath->query('./w:pPr/w:ind/@w:firstLine', $pNode)->item(0);
        $hasFirstLineIndent = $firstLineIndentNode && (int) $firstLineIndentNode->nodeValue >= 360;

        $imgsHtml = '';
        if (!empty($imageMap)) {
            foreach ($xpath->query('.//a:blip/@r:embed', $pNode) as $embedAttr) {
                $rId = (string) $embedAttr->nodeValue;
                if (isset($imageMap[$rId])) {
                    $imgsHtml .= "<div class='doc-img-wrap'><img src='{$imageMap[$rId]}' alt='Gambar Dokumen'></div>";
                }
            }
        }

        $runsHtml = '';
        foreach ($xpath->query('./w:r | ./w:hyperlink/w:r', $pNode) as $runNode) {
            $text = '';
            foreach ($xpath->query('./w:t | ./w:tab | ./w:br', $runNode) as $part) {
                if ($part->localName === 't') {
                    $text .= e($part->nodeValue);
                } elseif ($part->localName === 'tab') {
                    $text .= '&nbsp;&nbsp;&nbsp;&nbsp;';
                } elseif ($part->localName === 'br' && $part->getAttribute('w:type') !== 'page') {
                    $text .= '<br>';
                }
            }
            if ($text === '') {
                continue;
            }

            $isBold = $xpath->query('./w:rPr/w:b[not(@w:val="0") and not(@w:val="false")]', $runNode)->length > 0;
            $isItalic = $xpath->query('./w:rPr/w:i[not(@w:val="0") and not(@w:val="false")]', $runNode)->length > 0;
            $isUnderline = $xpath->query('./w:rPr/w:u[not(@w:val="none")]', $runNode)->length > 0;

            if ($isBold) {
                $text = "<strong>{$text}</strong>";
            }
            if ($isItalic) {
                $text = "<em>{$text}</em>";
            }
            if ($isUnderline) {
                $text = "<u>{$text}</u>";
            }
            $runsHtml .= $text;
        }

        $prefix = $hasPageBreak ? "<div class='page-break'></div>" : '';
        $suffix = $hasSectionBreakAfter ? "<div class='page-break'></div>" : '';
        if (trim(strip_tags($runsHtml)) === '') {
            if ($imgsHtml !== '' || $prefix !== '' || $suffix !== '') {
                return $prefix . $imgsHtml . $suffix;
            }
            return "<p class='spacer'>&nbsp;</p>";
        }

        if (str_contains($styleId, 'heading1') || $styleId === 'title') {
            return "{$prefix}{$imgsHtml}<h1>{$runsHtml}</h1>{$suffix}";
        }
        if (str_contains($styleId, 'heading2')) {
            return "{$prefix}{$imgsHtml}<h2>{$runsHtml}</h2>{$suffix}";
        }
        if (str_contains($styleId, 'heading3')) {
            return "{$prefix}{$imgsHtml}<h3>{$runsHtml}</h3>{$suffix}";
        }

        if ($isListPara) {
            return "{$prefix}{$imgsHtml}<p class='list-item'>&bull;&nbsp;{$runsHtml}</p>{$suffix}";
        }

        $classes = [];
        if ($align === 'center') {
            $classes[] = 'align-center';
        } elseif ($align === 'right') {
            $classes[] = 'align-right';
        } elseif ($align === 'left') {
            $classes[] = 'align-left';
        }
        if ($hasFirstLineIndent && $align !== 'center' && $align !== 'right') {
            $classes[] = 'indent-first';
        }
        $classAttr = !empty($classes) ? ' class="' . implode(' ', $classes) . '"' : '';

        return "{$prefix}{$imgsHtml}<p{$classAttr}>{$runsHtml}</p>{$suffix}";
    }

    protected function renderWordTableNode(\DOMXPath $xpath, \DOMNode $tblNode, array $imageMap = []): string
    {
        $rows = $xpath->query('./w:tr', $tblNode);
        if ($rows->length === 0) {
            return '';
        }

        // Deteksi tabel tanpa garis (mis. tabel tanda tangan Lembar Pengesahan / Dosen Pembimbing)
        $isBorderless = false;
        $tblBorders = $xpath->query('./w:tblPr/w:tblBorders', $tblNode)->item(0);
        if ($tblBorders) {
            $noneSides = 0;
            $checkedSides = 0;
            foreach (['top', 'bottom', 'left', 'right', 'insideH', 'insideV'] as $side) {
                $bNode = $xpath->query("./w:{$side}", $tblBorders)->item(0);
                if ($bNode) {
                    $checkedSides++;
                    $val = strtolower(trim((string) $bNode->getAttribute('w:val')));
                    $sz = (string) $bNode->getAttribute('w:sz');
                    if (in_array($val, ['none', 'nil'], true) || $sz === '0') {
                        $noneSides++;
                    }
                }
            }
            if ($checkedSides > 0 && $noneSides === $checkedSides) {
                $isBorderless = true;
            }
        } else {
            // Jika tidak ada tblBorders sama sekali dan tidak ada border pada cell pertama -> tabel layout tanpa border
            $anyVisibleCellBorder = $xpath->query('.//w:tcPr/w:tcBorders/*[@w:val and not(@w:val="none") and not(@w:val="nil") and not(@w:sz="0")]', $tblNode)->length;
            $tblStyleNode = $xpath->query('./w:tblPr/w:tblStyle/@w:val', $tblNode)->item(0);
            if ($anyVisibleCellBorder === 0 && !$tblStyleNode) {
                $isBorderless = true;
            }
        }

        $tblClass = $isBorderless ? " class='borderless'" : '';
        $html = "<table{$tblClass}>";
        foreach ($rows as $rIdx => $trNode) {
            $html .= '<tr>';
            $isHeaderRow = !$isBorderless && ($rIdx === 0 || $xpath->query('./w:trPr/w:tblHeader', $trNode)->length > 0);
            $tag = $isHeaderRow ? 'th' : 'td';
            foreach ($xpath->query('./w:tc', $trNode) as $tcNode) {
                $gridSpanNode = $xpath->query('./w:tcPr/w:gridSpan/@w:val', $tcNode)->item(0);
                $colspanAttr = ($gridSpanNode && (int) $gridSpanNode->nodeValue > 1)
                    ? " colspan='" . (int) $gridSpanNode->nodeValue . "'"
                    : '';

                $cellAlign = '';
                $firstJc = $xpath->query('./w:p/w:pPr/w:jc/@w:val', $tcNode)->item(0);
                if ($firstJc) {
                    $jcVal = strtolower(trim($firstJc->nodeValue));
                    if ($jcVal === 'center') {
                        $cellAlign = " class='td-center'";
                    } elseif ($jcVal === 'right') {
                        $cellAlign = " class='td-right'";
                    }
                }

                $cellParts = [];
                foreach ($xpath->query('./w:p', $tcNode) as $pNode) {
                    $pTxt = trim(strip_tags($this->renderWordParagraphNode($xpath, $pNode, $imageMap), '<strong><em><u><br><img>'));
                    if ($pTxt !== '') {
                        $cellParts[] = $pTxt;
                    }
                }
                $html .= "<{$tag}{$colspanAttr}{$cellAlign}>" . implode('<br>', $cellParts) . "</{$tag}>";
            }
            $html .= '</tr>';
        }
        $html .= '</table>';

        return $html;
    }
}
