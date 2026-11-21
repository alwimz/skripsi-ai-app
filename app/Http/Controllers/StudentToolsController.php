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

        $prompt = "Buat rancangan {$kindLabel} untuk judul/topik skripsi mahasiswa Indonesia berikut:\n"
            . "Topik: \"{$topic}\"\n\n"
            . "Kembalikan HANYA JSON array valid berisi 6 sampai 8 objek langkah berurutan (TANPA markdown ```json, TANPA penjelasan tambahan).\n"
            . "Format tiap objek:\n"
            . "{\"type\": \"start|io|process|decision|end\", \"label\": \"Teks singkat maksimal 55 karakter\", \"no_label\": \"opsional khusus type decision\"}\n\n"
            . "Aturan wajib:\n"
            . "1. Langkah pertama wajib type \"start\" (label \"Mulai\" atau fenomena awal jika Kerangka Berpikir).\n"
            . "2. Langkah terakhir wajib type \"end\" (label \"Selesai\" atau kesimpulan/luaran akhir).\n"
            . "3. Gunakan minimal 1 langkah type \"io\" (input/data/instrumen) dan maksimal 1 langkah type \"decision\" (validasi/uji kelayakan/akurasi) dengan \"no_label\" singkat (cth: \"Revisi / Ulangi\").\n"
            . "4. Bahasa Indonesia akademik ringkas, jelas, dan spesifik sesuai topik \"{$topic}\".";

        $steps = [];
        try {
            $raw = trim((string) $this->ai->generateText($prompt, 'auto'));
            $raw = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $raw);
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
                            $step['no_label'] = Str::limit(trim((string) ($row['no_label'] ?? 'Revisi / Ulangi')), 30, '');
                        }
                        $steps[] = $step;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Fallback deterministik di bawah
        }

        if (count($steps) < 4) {
            $shortTopic = Str::limit($topic, 38, '');
            if ($kind === 'kerangka') {
                $steps = [
                    ['type' => 'start', 'label' => 'Fenomena & Masalah Penelitian'],
                    ['type' => 'process', 'label' => 'Kajian Teori & Penelitian Terdahulu'],
                    ['type' => 'io', 'label' => 'Identifikasi Variabel: ' . $shortTopic],
                    ['type' => 'process', 'label' => 'Pengumpulan & Pengolahan Data Empiris'],
                    ['type' => 'process', 'label' => 'Analisis Hubungan / Evaluasi Model'],
                    ['type' => 'end', 'label' => 'Temuan, Simpulan & Rekomendasi'],
                ];
            } elseif ($kind === 'sistem') {
                $steps = [
                    ['type' => 'start', 'label' => 'Mulai'],
                    ['type' => 'io', 'label' => 'Input Data / Kebutuhan Pengguna'],
                    ['type' => 'process', 'label' => 'Pra-Pemrosesan & Validasi Input'],
                    ['type' => 'process', 'label' => 'Pemrosesan Inti: ' . $shortTopic],
                    ['type' => 'decision', 'label' => 'Hasil Pengujian Sesuai Target?', 'no_label' => 'Perbaikan / Tuning'],
                    ['type' => 'io', 'label' => 'Tampilkan Output & Laporan Hasil'],
                    ['type' => 'end', 'label' => 'Selesai'],
                ];
            } else {
                $steps = [
                    ['type' => 'start', 'label' => 'Mulai'],
                    ['type' => 'process', 'label' => 'Identifikasi Masalah & Studi Literatur'],
                    ['type' => 'io', 'label' => 'Pengumpulan Data: ' . $shortTopic],
                    ['type' => 'decision', 'label' => 'Data Valid & Reliabel?', 'no_label' => 'Revisi Instrumen'],
                    ['type' => 'process', 'label' => 'Pengolahan & Analisis Data Penelitian'],
                    ['type' => 'process', 'label' => 'Pembahasan Hasil & Penarikan Kesimpulan'],
                    ['type' => 'end', 'label' => 'Selesai'],
                ];
            }
        }

        return response()->json([
            'status' => 'success',
            'caption' => $defaultCaption,
            'steps' => array_slice($steps, 0, 10),
        ]);
    }

    /**
     * Konversi Word (.docx) -> PDF (.pdf) ukuran A4 via ZipArchive XML Parser + Dompdf
     * Ringan, cepat, dan 100% kompatibel dengan Vercel PHP serverless tanpa LibreOffice/GD.
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
        if (trim(strip_tags($bodyHtml)) === '') {
            return response()->json([
                'status' => 'error',
                'message' => 'Isi dokumen Word kosong atau tidak dapat dibaca.',
            ], 422);
        }

        $marginPreset = $request->input('margin', 'standard');
        $marginCss = $marginPreset === 'skripsi'
            ? '4cm 3cm 3cm 4cm'
            : '2.54cm 2.54cm 2.54cm 2.54cm';

        $html = "<!DOCTYPE html><html lang='id'><head><meta charset='UTF-8'><style>
            @page { size: A4; margin: {$marginCss}; }
            body {
                font-family: 'DejaVu Serif', 'Times New Roman', serif;
                font-size: 11.5pt;
                line-height: 1.5;
                color: #0f172a;
                text-align: justify;
            }
            h1 { font-size: 15pt; font-weight: bold; margin: 0 0 10pt 0; text-align: center; page-break-after: avoid; }
            h2 { font-size: 13pt; font-weight: bold; margin: 14pt 0 6pt 0; text-align: left; page-break-after: avoid; }
            h3 { font-size: 11.5pt; font-weight: bold; margin: 10pt 0 4pt 0; text-align: left; page-break-after: avoid; }
            p { margin: 0 0 7pt 0; }
            p.align-center { text-align: center; }
            p.align-right { text-align: right; }
            p.align-left { text-align: left; }
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
            th { background: #f1f5f9; font-weight: bold; }
            .page-break { page-break-before: always; }
        </style></head><body>{$bodyHtml}</body></html>";

        $dompdf = new Dompdf();
        $dompdf->set_option('isHtml5ParserEnabled', true);
        $dompdf->set_option('isRemoteEnabled', false);
        $dompdf->set_option('tempDir', '/tmp');
        $dompdf->set_option('fontCache', '/tmp');
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $safeBase . '.pdf"',
            'Cache-Control' => 'no-store',
        ]);
    }

    /**
     * Konversi PDF -> Word (.docx) dari hasil ekstraksi halaman PDF.js di browser
     * Mendukung PDF modern/besar tanpa kena batas ukuran payload Vercel.
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

        Settings::setTempDir('/tmp');
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(12);

        // 1 cm = 566.93 twips
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
        $lineSpacing = $academic ? 360 : 276; // 1.5 vs 1.15 spasi

        foreach ($pages as $pIdx => $page) {
            if ($pIdx > 0) {
                $section->addPageBreak();
            }

            $paragraphs = is_array($page['paragraphs'] ?? null) ? $page['paragraphs'] : [];
            if (empty($paragraphs) && !empty($page['text'])) {
                $paragraphs = array_values(array_filter(
                    array_map('trim', preg_split('/\R{2,}/', (string) $page['text']))
                ));
            }

            foreach ($paragraphs as $rawPara) {
                $clean = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string) $rawPara));
                if ($clean === '') {
                    continue;
                }

                // Deteksi heading bab atau sub-bab sederhana
                $isMainHeading = preg_match('/^(?:BAB\s+[IVX]+|DAFTAR\s+[A-Z]+|KATA\s+PENGANTAR|ABSTRAK|ABSTRACT|LAMPIRAN)\b/i', $clean)
                    && mb_strlen($clean) <= 90;
                $isSubHeading = !$isMainHeading
                    && preg_match('/^(?:\d+\.\d+(?:\.\d+)?\.?\s+|[A-Z]\.\s+)/', $clean)
                    && mb_strlen($clean) <= 110;

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
        $tmpPath = '/tmp/tool_pdf2docx_' . Str::random(10) . '.docx';
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

        $html = $this->parseDocxToHtml($file->getRealPath());
        $text = html_entity_decode(strip_tags(str_replace(['</p>', '</h1>', '</h2>', '</h3>', '</tr>'], "\n\n", $html)), ENT_QUOTES, 'UTF-8');
        $text = preg_replace("/\n{3,}/", "\n\n", trim($text));

        return response()->json([
            'status' => 'success',
            'text' => $text,
        ]);
    }

    /**
     * Parser ringan word/document.xml -> HTML bersih (Heading, Paragraf, Bold/Italic/Underline, Tabel, Page Break)
     */
    protected function parseDocxToHtml(string $filePath): string
    {
        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            return '';
        }

        $xmlContent = $zip->getFromName('word/document.xml');
        $zip->close();
        if (!$xmlContent) {
            return '';
        }

        $dom = new \DOMDocument();
        $prevErr = libxml_use_internal_errors(true);
        $dom->loadXML($xmlContent);
        libxml_clear_errors();
        libxml_use_internal_errors($prevErr);

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $body = $xpath->query('/w:document/w:body')->item(0);
        if (!$body) {
            return '';
        }

        $html = '';
        foreach ($body->childNodes as $node) {
            if ($node->localName === 'p') {
                $html .= $this->renderWordParagraphNode($xpath, $node);
            } elseif ($node->localName === 'tbl') {
                $html .= $this->renderWordTableNode($xpath, $node);
            }
        }

        return $html;
    }

    protected function renderWordParagraphNode(\DOMXPath $xpath, \DOMNode $pNode): string
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

        $hasPageBreak = $xpath->query('.//w:br[@w:type="page"]', $pNode)->length > 0
            || $xpath->query('.//w:pPr/w:pageBreakBefore', $pNode)->length > 0;

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
        if (trim(strip_tags($runsHtml)) === '') {
            return $prefix;
        }

        if (str_contains($styleId, 'heading1') || $styleId === 'title') {
            return "{$prefix}<h1>{$runsHtml}</h1>";
        }
        if (str_contains($styleId, 'heading2')) {
            return "{$prefix}<h2>{$runsHtml}</h2>";
        }
        if (str_contains($styleId, 'heading3')) {
            return "{$prefix}<h3>{$runsHtml}</h3>";
        }

        $alignClass = match ($align) {
            'center' => ' class="align-center"',
            'right' => ' class="align-right"',
            'left' => ' class="align-left"',
            default => '',
        };

        return "{$prefix}<p{$alignClass}>{$runsHtml}</p>";
    }

    protected function renderWordTableNode(\DOMXPath $xpath, \DOMNode $tblNode): string
    {
        $rows = $xpath->query('./w:tr', $tblNode);
        if ($rows->length === 0) {
            return '';
        }

        $html = '<table>';
        foreach ($rows as $rIdx => $trNode) {
            $html .= '<tr>';
            $tag = $rIdx === 0 ? 'th' : 'td';
            foreach ($xpath->query('./w:tc', $trNode) as $tcNode) {
                $cellParts = [];
                foreach ($xpath->query('./w:p', $tcNode) as $pNode) {
                    $pTxt = trim(strip_tags($this->renderWordParagraphNode($xpath, $pNode), '<strong><em><u><br>'));
                    if ($pTxt !== '') {
                        $cellParts[] = $pTxt;
                    }
                }
                $html .= "<{$tag}>" . implode('<br>', $cellParts) . "</{$tag}>";
            }
            $html .= '</tr>';
        }
        $html .= '</table>';

        return $html;
    }
}
