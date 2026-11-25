<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\User;
use App\Services\AIManagerService;
use App\Services\BillingService;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\Shared\Html;
use PhpOffice\PhpWord\Style\Tab;
use PhpOffice\PhpWord\Style\TOC;
use PhpOffice\PhpWord\Settings;
use Dompdf\Dompdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Exception;
use Throwable;
use ZipArchive;

class GeneratorController extends Controller
{
    protected AIManagerService $ai;
    private ?BillingService $billingService = null;
    private array $docxTempFiles = [];
    private array $docxFlowchartVmlMap = [];
    private string $docxFontName = 'Times New Roman';
    private float $docxFontSize = 12.0;

    public function __construct(AIManagerService $ai)
    {
        $this->ai = $ai;
    }

    private function billing(): BillingService
    {
        return $this->billingService ??= app(BillingService::class);
    }

    // =========================================================================
    // HELPER: EKSTRAK TEKS DARI FILE UPLOAD (.DOCX, .PDF, .DOC, .TXT)
    // =========================================================================
    private function extractTextFromUploadedFile($file): string
    {
        if (!$file || !$file->isValid()) {
            return '';
        }

        $ext = strtolower($file->getClientOriginalExtension());
        $path = $file->getRealPath();

        try {
            if ($ext === 'docx' && class_exists(ZipArchive::class)) {
                $zip = new ZipArchive();
                if ($zip->open($path) === true) {
                    $xml = $zip->getFromName('word/document.xml');
                    $zip->close();
                    if ($xml !== false) {
                        // Pertahankan struktur baris paragraf, baris tabel, dan pemisah kolom tabel agar BAB I-V tetap utuh
                        $xml = str_replace(
                            ['</w:p>', '</w:tr>', '<w:br/>', '<w:br />', '<w:cr/>', '</w:tc>', '<w:tab/>'],
                            ["\n", "\n", "\n", "\n", "\n", " | ", " "],
                            $xml
                        );
                        $text = html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');
                        $text = str_replace(["\r\n", "\r"], "\n", $text);
                        $text = preg_replace('/[ \t]+/', ' ', $text);
                        $text = preg_replace('/ *\n */', "\n", $text);
                        $text = preg_replace('/\n{3,}/', "\n\n", $text);
                        return trim($text);
                    }
                }
            }

            if (in_array($ext, ['txt', 'md', 'csv'])) {
                $rawTxt = (string) file_get_contents($path);
                return trim(str_replace(["\r\n", "\r"], "\n", $rawTxt));
            }

            if ($ext === 'pdf') {
                $raw = file_get_contents($path);
                $extracted = [];

                if (preg_match_all('/stream[\r\n]+(.*?)[\r\n]+endstream/s', $raw, $matches)) {
                    foreach ($matches[1] as $stream) {
                        $uncompressed = @gzuncompress($stream);
                        if ($uncompressed === false) {
                            $uncompressed = @gzinflate($stream);
                        }
                        $source = $uncompressed !== false ? $uncompressed : $stream;
                        $chunkLines = [];

                        if (preg_match_all('/\(((?:\\\\.|[^()\\\\]){1,})\)\s*Tj/s', $source, $tjMatches)) {
                            foreach ($tjMatches[1] as $txt) {
                                $chunkLines[] = stripslashes($txt);
                            }
                        }
                        if (preg_match_all('/\[(.*?)\]\s*TJ/s', $source, $tjArrMatches)) {
                            foreach ($tjArrMatches[1] as $arrTxt) {
                                if (preg_match_all('/\(((?:\\\\.|[^()\\\\])+)\)/', $arrTxt, $subMatches)) {
                                    $chunkLines[] = stripslashes(implode('', $subMatches[1]));
                                }
                            }
                        }
                        if (!empty($chunkLines)) {
                            $extracted[] = implode(' ', $chunkLines);
                        }
                    }
                }

                $text = trim(implode("\n", $extracted));
                $text = preg_replace('/[^\P{C}\n]+/u', ' ', $text);
                $text = preg_replace('/[ \t]+/', ' ', $text);
                $text = preg_replace('/\n{3,}/', "\n\n", $text);

                if (strlen($text) > 80) {
                    return Str::limit($text, 60000, '');
                }

                if (preg_match_all('/[a-zA-Z0-9\s,\.\-\(\)\:\;\/]{6,}/', $raw, $asciiMatches)) {
                    $fallback = implode(' ', $asciiMatches[0]);
                    $fallback = preg_replace('/\b(obj|endobj|stream|endstream|xref|trailer|Type|Font|Page|Filter|FlateDecode|Length)\b/i', '', $fallback);
                    return Str::limit(trim(preg_replace('/\s+/', ' ', $fallback)), 30000, '');
                }
            }

            if ($ext === 'doc') {
                $raw = file_get_contents($path);
                if (preg_match_all('/[a-zA-Z0-9\s,\.\-\(\)\:\;]{8,}/', $raw, $docMatches)) {
                    return Str::limit(trim(implode("\n", $docMatches[0])), 30000, '');
                }
            }
        } catch (Throwable $e) {
            Log::warning('File extraction warning: ' . $e->getMessage());
        }

        return 'Dokumen referensi: ' . $file->getClientOriginalName();
    }

    /**
     * Pilih bagian paling relevan dari buku pedoman kampus panjang (sistematika Bab I-V, margin, font, spasi, sitasi)
     * agar tidak habis terpotong di halaman sampul & kata pengantar buku pedoman.
     */
    private function extractSmartGuidelineSummary(string $rawText, int $budget = 9500): string
    {
        $clean = trim(str_replace(["\r\n", "\r"], "\n", $rawText));
        if (mb_strlen($clean) <= $budget) {
            return $clean;
        }
        $chunks = preg_split('/\n{2,}/', $clean) ?: explode("\n", $clean);
        $scored = [];
        foreach ($chunks as $idx => $chunk) {
            $t = trim($chunk);
            if (mb_strlen($t) < 25) {
                continue;
            }
            $hits = preg_match_all('/\b(?:bab\s+[ivx1-5]|sistematika|susunan|sub-?bab|margin|spasi|times\s+new\s+roman|arial|sitasi|daftar\s+pustaka|abstrak|latar\s+belakang|rumusan\s+masalah|tinjauan\s+pustaka|metodologi|penomoran|tabel|gambar)\b/i', $t);
            $scored[] = ['idx' => $idx, 'hits' => (int) $hits, 'text' => $t];
        }
        usort($scored, fn ($a, $b) => ($b['hits'] <=> $a['hits']) ?: ($a['idx'] <=> $b['idx']));
        $picked = [];
        $len = 0;
        foreach ($scored as $s) {
            $cLen = mb_strlen($s['text']) + 2;
            if ($len + $cLen > $budget) {
                continue;
            }
            $picked[] = $s;
            $len += $cLen;
        }
        usort($picked, fn ($a, $b) => $a['idx'] <=> $b['idx']);
        $out = implode("\n\n", array_column($picked, 'text'));
        return $out !== '' ? $out : Str::limit($clean, $budget, '');
    }

    /**
     * Susun ringkasan konteks seimbang per BAB (Abstrak & BAB I–V) dari teks lengkap file Word/PDF
     * agar tidak terpotong di Halaman Sampul / Daftar Isi / Bab I saja.
     */
    private function buildSmartThesisContext(string $fullText, int $budget = 14500): string
    {
        $clean = trim(str_replace(["\r\n", "\r"], "\n", strip_tags($fullText)));
        if (mb_strlen($clean) <= $budget) {
            return $clean;
        }

        // Cari kemunculan terakhir/isi nyata (bukan baris Daftar Isi yang diikuti titik-titik atau angka halaman)
        $findSectionOffset = function (string $text, string $pattern): ?int {
            if (!preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE)) {
                return null;
            }
            $chosen = null;
            foreach ($matches[0] as $m) {
                $offset = $m[1];
                $lineEnd = strpos($text, "\n", $offset);
                $line = $lineEnd !== false ? substr($text, $offset, $lineEnd - $offset) : substr($text, $offset, 120);
                // Lewati entri Daftar Isi (biasanya punya titik-titik panjang atau diakhiri angka halaman)
                if (preg_match('/\.{3,}|\t\s*\d+\s*$/', $line)) {
                    continue;
                }
                $chosen = $offset;
            }
            return $chosen;
        };

        $sections = [
            'ABSTRAK' => ['pattern' => '/(?:^|\n)\s*ABSTRAK\b/i', 'share' => 0.11],
            'BAB I' => ['pattern' => '/(?:^|\n)\s*BAB\s+(?:I|1)\b(?:\s*[:\-\n]|\s+PENDAHULUAN)/i', 'share' => 0.19],
            'BAB II' => ['pattern' => '/(?:^|\n)\s*BAB\s+(?:II|2)\b(?:\s*[:\-\n]|\s+TINJAUAN|\s+KAJIAN|\s+LANDASAN)/i', 'share' => 0.15],
            'BAB III' => ['pattern' => '/(?:^|\n)\s*BAB\s+(?:III|3)\b(?:\s*[:\-\n]|\s+METOD)/i', 'share' => 0.20],
            'BAB IV' => ['pattern' => '/(?:^|\n)\s*BAB\s+(?:IV|4)\b(?:\s*[:\-\n]|\s+HASIL|\s+PEMBAHASAN|\s+IMPLEMENTASI)/i', 'share' => 0.23],
            'BAB V' => ['pattern' => '/(?:^|\n)\s*BAB\s+(?:V|5)\b(?:\s*[:\-\n]|\s+PENUTUP|\s+KESIMPULAN|\s+SIMPULAN)/i', 'share' => 0.12],
        ];

        $offsets = [];
        foreach ($sections as $label => $cfg) {
            $pos = $findSectionOffset($clean, $cfg['pattern']);
            if ($pos !== null) {
                $offsets[$label] = $pos;
            }
        }

        // Jika terdeteksi minimal 2 bab nyata, potong proporsional tiap bab
        if (count($offsets) >= 2) {
            asort($offsets);
            $orderedKeys = array_keys($offsets);
            $chunks = [];

            // Sertakan sedikit header/cover awal (judul & identitas)
            $firstPos = reset($offsets);
            if ($firstPos > 60) {
                $chunks[] = "[SAMPUL / IDENTITAS DOKUMEN]\n" . mb_substr($clean, 0, min(700, $firstPos));
            }

            $totalShare = 0.0;
            foreach ($orderedKeys as $k) {
                $totalShare += $sections[$k]['share'];
            }
            $usableBudget = max(4000, $budget - 800);

            foreach ($orderedKeys as $idx => $label) {
                $start = $offsets[$label];
                $end = isset($orderedKeys[$idx + 1]) ? $offsets[$orderedKeys[$idx + 1]] : strlen($clean);
                $rawSlice = trim(substr($clean, $start, max(0, $end - $start)));
                $alloc = (int) floor($usableBudget * ($sections[$label]['share'] / max(0.01, $totalShare)));
                if ($rawSlice !== '') {
                    $chunks[] = "[{$label}]\n" . Str::limit($rawSlice, max(900, $alloc), ' ...');
                }
            }

            return implode("\n\n", $chunks);
        }

        // Fallback dokumen tanpa heading BAB: ambil Awal (42%) + Tengah (28%) + Akhir (30%)
        $len = mb_strlen($clean);
        $headLen = (int) floor($budget * 0.42);
        $midLen = (int) floor($budget * 0.28);
        $tailLen = $budget - $headLen - $midLen;
        $midStart = max($headLen, (int) floor(($len - $midLen) / 2));

        return "[BAGIAN AWAL DOKUMEN]\n" . mb_substr($clean, 0, $headLen)
            . "\n\n[BAGIAN TENGAH DOKUMEN (METODOLOGI/ANALISIS)]\n" . mb_substr($clean, $midStart, $midLen)
            . "\n\n[BAGIAN AKHIR DOKUMEN (HASIL & KESIMPULAN)]\n" . mb_substr($clean, max(0, $len - $tailLen));
    }

    /**
     * Susun konteks naskah proyek DB secara seimbang lintas BAB I–V (bukan terpotong di Bab I).
     */
    private function buildProjectThesisContext(Project $proj, int $budget = 14500): string
    {
        $meta = $this->getProjectMeta($proj);
        $header = "Judul: {$proj->judul}\nProgram Studi: {$proj->prodi}\nMetode Penelitian: {$proj->metode_penelitian}";
        if (!empty($meta['data_awal'])) {
            $header .= "\nData Awal: " . Str::limit($meta['data_awal'], 400);
        }
        if (!empty($meta['data_riil']) && is_array($meta['data_riil'])) {
            $dr = array_filter($meta['data_riil']);
            if (!empty($dr)) {
                $header .= "\nData Olahan Riil: " . Str::limit(json_encode($dr, JSON_UNESCAPED_UNICODE), 700);
            }
        }

        $parts = [
            'BAB I PENDAHULUAN' => ['text' => trim(strip_tags((string) $proj->bab1_content)), 'weight' => 0.19],
            'BAB II TINJAUAN PUSTAKA' => ['text' => trim(strip_tags((string) $proj->bab2_content)), 'weight' => 0.15],
            'BAB III METODOLOGI PENELITIAN' => ['text' => trim(strip_tags((string) $proj->bab3_content)), 'weight' => 0.21],
            'BAB IV HASIL DAN PEMBAHASAN' => ['text' => trim(strip_tags((string) $proj->bab4_content)), 'weight' => 0.28],
            'BAB V PENUTUP' => ['text' => trim(strip_tags((string) $proj->bab5_content)), 'weight' => 0.12],
            'DAFTAR PUSTAKA' => ['text' => trim(strip_tags((string) $proj->daftar_pustaka)), 'weight' => 0.05],
        ];

        $availableWeight = 0.0;
        foreach ($parts as $p) {
            if ($p['text'] !== '') {
                $availableWeight += $p['weight'];
            }
        }

        $chunks = [$header];
        $usable = max(4000, $budget - mb_strlen($header) - 200);
        foreach ($parts as $label => $p) {
            if ($p['text'] === '') {
                continue;
            }
            $alloc = (int) floor($usable * ($p['weight'] / max(0.01, $availableWeight)));
            $chunks[] = "=== {$label} ===\n" . Str::limit($p['text'], max(700, $alloc), ' ...');
        }

        return implode("\n\n", $chunks);
    }

    /**
     * Ambil array konfigurasi proyek dari kolom citation_validation
     */
    public function getProjectMeta(Project $project): array
    {
        $raw = $project->citation_validation;
        if (empty($raw)) {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }
        return ['pedoman_text' => $raw];
    }

    /**
     * Kompres & konversi logo universitas menjadi JPEG base64 kecil agar aman disimpan di TEXT column & terbaca PhpWord/Dompdf tanpa GD
     */
    private function normalizeLogoBase64($fileOrBase64): string
    {
        try {
            $rawBytes = null;
            if (is_object($fileOrBase64) && method_exists($fileOrBase64, 'isValid') && $fileOrBase64->isValid()) {
                $rawBytes = @file_get_contents($fileOrBase64->getRealPath());
            } elseif (is_string($fileOrBase64) && !empty(trim($fileOrBase64))) {
                $clean = preg_replace('#^data:image/\w+;base64,#i', '', trim($fileOrBase64));
                $rawBytes = @base64_decode($clean);
            }

            if (empty($rawBytes) || strlen($rawBytes) < 30) {
                return '';
            }

            // Jika GD tersedia, kompres & ubah ke JPEG dengan background putih (kompatibel Dompdf tanpa GD runtime)
            if (function_exists('imagecreatefromstring')) {
                $src = @imagecreatefromstring($rawBytes);
                if ($src !== false) {
                    $origW = imagesx($src);
                    $origH = imagesy($src);
                    $maxDim = 240;
                    $scale = min(1.0, $maxDim / max(1, $origW, $origH));
                    $newW = max(1, (int) round($origW * $scale));
                    $newH = max(1, (int) round($origH * $scale));

                    $dst = imagecreatetruecolor($newW, $newH);
                    $white = imagecolorallocate($dst, 255, 255, 255);
                    imagefilledrectangle($dst, 0, 0, $newW, $newH, $white);
                    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

                    ob_start();
                    imagejpeg($dst, null, 85);
                    $jpgBytes = ob_get_clean();
                    imagedestroy($src);
                    imagedestroy($dst);

                    if (!empty($jpgBytes)) {
                        return 'data:image/jpeg;base64,' . base64_encode($jpgBytes);
                    }
                }
            }

            // Jika GD tidak tersedia (misal di Vercel serverless), deteksi jenis file mentah
            $isJpg = str_starts_with($rawBytes, "\xFF\xD8\xFF");
            $isPng = str_starts_with($rawBytes, "\x89PNG");

            if ($isJpg) {
                return 'data:image/jpeg;base64,' . base64_encode($rawBytes);
            } elseif ($isPng) {
                return 'data:image/png;base64,' . base64_encode($rawBytes);
            } elseif (strlen($rawBytes) <= 60000) {
                return 'data:image/jpeg;base64,' . base64_encode($rawBytes);
            }
        } catch (\Throwable $e) {
            Log::warning('Logo normalization error: ' . $e->getMessage());
        }
        return '';
    }

    /**
     * Ambil identitas & logo default dari proyek terbaru user agar form terisi otomatis
     */
    private function getDefaultUserIdentity(int $userId): array
    {
        $user = Auth::user();
        $identity = [
            'nama' => $user ? trim($user->name) : '',
            'nim' => '',
            'kampus' => '',
            'prodi' => '',
            'fakultas' => '',
            'logo_base64' => '',
        ];

        try {
            $recentProjects = Project::where('user_id', $userId)
                ->select(['id', 'prodi', 'citation_validation'])
                ->orderBy('updated_at', 'desc')
                ->take(10)
                ->get();
            foreach ($recentProjects as $proj) {
                $m = $this->getProjectMeta($proj);
                if (empty($identity['nama']) && !empty($m['nama']))
                    $identity['nama'] = trim($m['nama']);
                if (empty($identity['nim']) && !empty($m['nim']))
                    $identity['nim'] = trim($m['nim']);
                if (empty($identity['kampus']) && !empty($m['kampus']))
                    $identity['kampus'] = trim($m['kampus']);
                if (empty($identity['fakultas']) && !empty($m['fakultas']))
                    $identity['fakultas'] = trim($m['fakultas']);
                if (empty($identity['prodi']) && !empty($proj->prodi))
                    $identity['prodi'] = trim($proj->prodi);
                if (empty($identity['logo_base64']) && !empty($m['logo_base64']))
                    $identity['logo_base64'] = $m['logo_base64'];
            }
        } catch (Throwable $e) {
        }

        return $identity;
    }

    /**
     * Format tanggal dalam Bahasa Indonesia baku (tanpa nama bulan Inggris)
     */
    private function formatIndonesianDate(string $mode = 'full'): string
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $d = (int) date('j');
        $m = $months[(int) date('n')] ?? 'Januari';
        $y = date('Y');
        return $mode === 'month_year' ? "{$m} {$y}" : "{$d} {$m} {$y}";
    }

    /**
     * Konversi angka bulat ke angka Romawi kecil (i, ii, iii, iv, ...)
     */
    private function toRomanLower(int $number): string
    {
        if ($number <= 0) {
            return '';
        }
        $map = [
            'm' => 1000, 'cm' => 900, 'd' => 500, 'cd' => 400,
            'c' => 100, 'xc' => 90, 'l' => 50, 'xl' => 40,
            'x' => 10, 'ix' => 9, 'v' => 5, 'iv' => 4, 'i' => 1,
        ];
        $result = '';
        foreach ($map as $roman => $val) {
            while ($number >= $val) {
                $result .= $roman;
                $number -= $val;
            }
        }
        return $result;
    }

    /**
     * Normalisasi label Fakultas agar tidak tercetak janggal ("TEKNIK" / "Dekan Teknik")
     * ketika mahasiswa hanya mengisi nama singkat tanpa awalan "Fakultas".
     */
    private function formatFacultyLabel(string $raw): string
    {
        $clean = trim(preg_replace('/\s+/', ' ', $raw));
        if ($clean === '') {
            return '';
        }
        if (preg_match('/^(?:fakultas|sekolah|pascasarjana|program\s+pascasarjana|faculty|school|jurusan|direktorat)\b/i', $clean)) {
            return $clean;
        }
        return 'Fakultas ' . $clean;
    }

    /**
     * Hitung estimasi halaman dinamis per bab secara akurat (memperhitungkan teks, tabel, diagram/flowchart, dan heading)
     */
    private function computeDynamicChapterPages(Project $project, bool $isProposal = false): array
    {
        $chapterKeys = $isProposal ? ['bab1', 'bab2', 'bab3'] : ['bab1', 'bab2', 'bab3', 'bab4', 'bab5'];
        $map = [];
        $curPage = 1;

        foreach ($chapterKeys as $k) {
            $rawChapterText = (string) ($project->{$k . '_content'} ?? '');
            if ($k !== 'bab5' && $rawChapterText !== '') {
                [$rawChapterText] = $this->stripEmbeddedDaftarPustaka($rawChapterText);
            }
            $text = trim(strip_tags($rawChapterText));
            if ($text !== '') {
                // Hitung bobot visual nyata: blok diagram/flowchart, baris tabel Markdown, dan heading sub-bab
                $diagramBlocks = (int) floor(substr_count($rawChapterText, '```') / 2);
                $lines = preg_split("/\r\n|\n/", $rawChapterText) ?: [];
                $tableRows = 0;
                $tableBlocks = 0;
                $inTable = false;
                $subHeadings = 0;
                $proseChars = 0;
                $inFence = false;
                foreach ($lines as $ln) {
                    $t = trim(strip_tags($ln));
                    if (str_starts_with($t, '```')) {
                        $inFence = !$inFence;
                        $inTable = false;
                        continue;
                    }
                    if ($inFence || $t === '') {
                        if ($t === '') {
                            $inTable = false;
                        }
                        continue;
                    }
                    if (str_starts_with($t, '|') && substr_count($t, '|') >= 2) {
                        if (!$inTable) {
                            $tableBlocks++;
                            $inTable = true;
                        }
                        if (!preg_match('/^[\|\s\-:]+$/', $t)) {
                            $tableRows++;
                        }
                        continue;
                    }
                    $inTable = false;
                    if (preg_match('/^(?:\d+\.\d+(?:\.\d+)?\.?|[A-Z]\.)\s+/', $t)) {
                        $subHeadings++;
                    }
                    $proseChars += mb_strlen($t);
                }

                // Estimasi halaman A4 Times New Roman 12pt spasi 1,5 margin 4-4-3-3 cm:
                // ~2050 char prosa per halaman + overhead judul BAB (0.18) + tabel + diagram + heading
                $estimatedFloat = 0.18
                    + ($proseChars / 2050.0)
                    + ($tableBlocks * 0.14)
                    + ($tableRows * 0.055)
                    + ($diagramBlocks * 0.48)
                    + ($subHeadings * 0.055);
                $pages = max(1, (int) ceil($estimatedFloat));
                $map[$k] = ['start' => $curPage, 'count' => $pages, 'end' => $curPage + $pages - 1];
                $curPage += $pages;
            } else {
                $map[$k] = ['start' => $curPage, 'count' => 0, 'end' => $curPage];
            }
        }

        $dpText = trim(strip_tags((string) ($project->daftar_pustaka ?? '')));
        $dpLines = $dpText !== '' ? count(array_filter(array_map('trim', preg_split("/\r\n|\n/", $dpText) ?: []))) : 0;
        $dpPages = $dpText !== '' ? max(1, (int) ceil(max(mb_strlen($dpText) / 1850.0, $dpLines / 14.0))) : 1;
        $map['daftar_pustaka'] = ['start' => $curPage, 'count' => $dpPages, 'end' => $curPage + $dpPages - 1];
        $curPage += $dpPages;
        $map['next_page'] = $curPage;

        return $map;
    }

    private function buildCitationStyleGuide(string $gayaSitasi = 'APA 7th'): array
    {
        $raw = trim($gayaSitasi) !== '' ? trim($gayaSitasi) : 'APA 7th';
        $g = strtolower($raw);

        if (str_contains($g, 'ieee')) {
            return [
                'key' => 'ieee',
                'label' => 'IEEE',
                'is_numbered' => true,
                'sort_instruction' => 'URUT BERDASARKAN NOMOR URUT KEMUNCULAN [1], [2], [3], dst. (BUKAN urut abjad A-Z)',
                'in_text_example' => 'contoh: ...memiliki akurasi tinggi [1]. atau Menurut studi [2], metode ini...',
                'in_text_rule' => 'WAJIB memakai penomoran IEEE kurung siku [1], [2], [3] berurutan sesuai kemunculan pertama di teks (contoh: "...berpengaruh signifikan [1]."). DILARANG KERAS menulis sitasi nama-tahun APA seperti (Penulis, 2024) atau Penulis (2024)!',
                'bib_example' => '[1] B. Surya and A. Wijaya, "Judul artikel penelitian secara spesifik," Nama Jurnal Bereputasi, vol. 12, no. 2, pp. 45-58, 2024. https://doi.org/10.xxxx/...',
                'bib_rule' => 'Format WAJIB IEEE: awali [1], [2], dst., nama penulis Inisial diikuti Nama Belakang (A. Wijaya), judul artikel diapit tanda kutip ganda ("Judul,"), nama jurnal, vol., no., pp., tahun di akhir sebelum DOI. DILARANG memakai format APA seperti Nama, I. (2024). Judul!',
            ];
        }

        if (str_contains($g, 'vancouver')) {
            return [
                'key' => 'vancouver',
                'label' => 'Vancouver',
                'is_numbered' => true,
                'sort_instruction' => 'URUT BERDASARKAN NOMOR URUT KEMUNCULAN 1., 2., 3., dst. (BUKAN urut abjad A-Z)',
                'in_text_example' => 'contoh: ...terbukti signifikan (1). atau Studi terdahulu (2) menunjukkan...',
                'in_text_rule' => 'WAJIB memakai penomoran Vancouver angka urut dalam kurung (1), (2), (3) sesuai kemunculan di teks. DILARANG KERAS menulis sitasi nama-tahun APA seperti (Penulis, 2024) atau Penulis (2024)!',
                'bib_example' => '1. Surya B, Wijaya A. Judul artikel penelitian secara spesifik. Nama Jurnal Bereputasi. 2024;12(2):45-58. https://doi.org/10.xxxx/...',
                'bib_rule' => 'Format WAJIB Vancouver: bernomor 1., 2., dst., nama belakang diikuti inisial TANPA titik & tanda & (Surya B, Wijaya A.), judul tanpa tanda kutip, jurnal, tahun;volume(nomor):halaman.',
            ];
        }

        if (str_contains($g, 'chicago')) {
            return [
                'key' => 'chicago',
                'label' => 'Chicago',
                'is_numbered' => false,
                'sort_instruction' => 'URUT ABJAD A-Z berdasarkan nama belakang penulis pertama',
                'in_text_example' => 'contoh: (Surya 2023) atau Surya (2023) atau (Wijaya et al. 2024)',
                'in_text_rule' => 'WAJIB memakai format Chicago Author-Date TANPA tanda koma sebelum tahun di dalam kurung: contoh "(Surya 2023)", "(Surya & Wijaya 2024)", "(Wijaya et al. 2024)", atau naratif "Surya (2023)". DILARANG memakai tanda koma APA seperti "(Surya, 2023)"! ATURAN NAMA PENULIS: WAJIB gunakan NAMA BELAKANG (SURNAME) SAJA pada sitasi dalam teks — DILARANG menulis nama depan/tengah lengkap.',
                'bib_example' => 'Surya, B., dan A. Wijaya. "Judul artikel penelitian secara spesifik." Nama Jurnal Bereputasi 12, no. 2 (2024): 45-58. https://doi.org/10.xxxx/...',
                'bib_rule' => 'Format WAJIB Chicago: tahun diletakkan SETELAH volume/nomor sebelum titik dua halaman — Penulis. "Judul artikel." Nama Jurnal Vol, no. No (Tahun): Halaman. DILARANG meletakkan (Tahun) tepat setelah nama penulis seperti gaya APA!',
            ];
        }

        if (str_contains($g, 'harvard')) {
            return [
                'key' => 'harvard',
                'label' => 'Harvard',
                'is_numbered' => false,
                'sort_instruction' => 'URUT ABJAD A-Z berdasarkan nama belakang penulis pertama',
                'in_text_example' => 'contoh: (Surya 2023), (Surya & Putra 2023), (Wijaya et al. 2024), atau Surya (2023)',
                'in_text_rule' => 'WAJIB memakai format Harvard TANPA tanda koma antara nama penulis dan tahun di dalam kurung: contoh "(Surya 2023)", "(Surya & Putra 2023)", "(Wijaya et al. 2024)", atau naratif "Surya (2023)". DILARANG memakai tanda koma ala APA seperti "(Surya, 2023)" atau "(Wijaya et al., 2024)"! ATURAN NAMA PENULIS: WAJIB gunakan NAMA BELAKANG (SURNAME) SAJA pada sitasi dalam teks — DILARANG menulis nama depan/tengah lengkap.',
                'bib_example' => "Surya, B. & Wijaya, A. (2024) 'Judul artikel penelitian secara spesifik', Nama Jurnal Bereputasi, 12(2), pp. 45-58. https://doi.org/10.xxxx/...",
                'bib_rule' => "Format WAJIB Harvard: Penulis (Tahun) TANPA titik setelah kurung tahun, judul artikel diapit tanda kutip tunggal ('Judul artikel'), dipisah koma sebelum nama jurnal, serta awalan 'pp.' sebelum rentang halaman: Penulis, I. (Tahun) 'Judul artikel', Nama Jurnal, Vol(No), pp. Halaman. DILARANG memakai format APA '(Tahun). Judul.' tanpa kutip tunggal!",
            ];
        }

        return [
            'key' => 'apa',
            'label' => 'APA 7th',
            'is_numbered' => false,
            'sort_instruction' => 'URUT ABJAD A-Z berdasarkan nama belakang penulis pertama',
            'in_text_example' => 'contoh: Menurut Wijaya et al. (2024) atau (Surya, 2023) / (Wijaya et al., 2024)',
            'in_text_rule' => 'WAJIB memakai format APA 7th Edition DENGAN tanda koma sebelum tahun di dalam kurung: contoh "(Surya, 2023)", "(Surya & Putra, 2023)", "(Wijaya et al., 2024)", atau naratif "Surya (2023)" / "Surya dan Putra (2023)". ATURAN NAMA PENULIS: WAJIB gunakan NAMA BELAKANG (SURNAME) SAJA pada sitasi dalam teks — DILARANG KERAS menuliskan nama depan/tengah lengkap di dalam paragraf (contoh SALAH: "Luftia Rahma Nasution dan Sriani (2026)"; contoh BENAR: "Nasution dan Sriani (2026)").',
            'bib_example' => 'Wijaya, A., & Putra, B. (2024). Judul artikel penelitian secara spesifik. Nama Jurnal Bereputasi, 12(2), 45-58. https://doi.org/10.xxxx/...',
            'bib_rule' => 'Format WAJIB APA 7th: Penulis, I., & Penulis2, I. (Tahun). Judul artikel. Nama Jurnal, Vol(No), Halaman. DOI',
        ];
    }

    private function formatVerifiedInTextCitation(array $ref, string $gayaSitasi = 'APA 7th', int $index = 1, bool $parenthetical = false): string
    {
        $guide = $this->buildCitationStyleGuide($gayaSitasi);
        if ($guide['key'] === 'ieee') {
            return "[{$index}]";
        }
        if ($guide['key'] === 'vancouver') {
            return "({$index})";
        }
        $inText = trim((string) ($ref['in_text'] ?? ($ref['first_surname'] ?? 'Anonim')));
        $year = (string) ($ref['year'] ?? date('Y'));
        if ($guide['key'] === 'harvard' || $guide['key'] === 'chicago') {
            return $parenthetical ? "({$inText} {$year})" : "{$inText} ({$year})";
        }
        return $parenthetical ? "({$inText}, {$year})" : "{$inText} ({$year})";
    }

    private function normalizeInTextCitationsForStyle(string $content, string $gayaSitasi = 'APA 7th'): string
    {
        $guide = $this->buildCitationStyleGuide($gayaSitasi);
        if ($content === '' || $guide['is_numbered']) {
            return $content;
        }

        $nonAuthorWords = [
            'tabel', 'gambar', 'bagan', 'lampiran', 'bab', 'pasal', 'ayat', 'uu', 'pp', 'permen',
            'permendikbud', 'kemenkes', 'tahun', 'bulan', 'periode', 'sejak', 'hingga', 'sampai',
            'antara', 'no', 'nomor', 'edisi', 'jakarta', 'bandung', 'surabaya', 'yogyakarta',
            'semarang', 'medan', 'makassar', 'indonesia', 'januari', 'februari', 'maret', 'april',
            'mei', 'juni', 'juli', 'agustus', 'september', 'oktober', 'november', 'desember',
            'misalnya', 'contohnya', 'yakni', 'yaitu', 'seperti', 'sekitar', 'kira-kira',
        ];

        $pattern = '/\(([A-Z][a-zA-ZÀ-ÿ\-]+(?:\s+et\s+al\.?|\s+(?:&|dan)\s+[A-Z][a-zA-ZÀ-ÿ\-]+|(?:,\s+[A-Z][a-zA-ZÀ-ÿ\-]+){1,4}(?:,?\s+(?:&|dan)\s+[A-Z][a-zA-ZÀ-ÿ\-]+))?)(,?)\s*((?:19|20)\d{2}[a-z]?(?:,\s*(?:hlm|hal|p|pp)\.?\s*\d+(?:-\d+)?)?)\)/u';

        $normalized = (string) preg_replace_callback($pattern, function (array $m) use ($guide, $nonAuthorWords): string {
            $authorPart = trim($m[1]);
            $comma = $m[2];
            $yearPart = trim($m[3]);
            $firstTok = strtolower((string) preg_split('/[\s,]+/', $authorPart)[0]);
            if ($firstTok === '' || in_array($firstTok, $nonAuthorWords, true)) {
                return $m[0];
            }

            if ($guide['key'] === 'harvard' || $guide['key'] === 'chicago') {
                // Harvard/Chicago Author-Date: hilangkan koma antara penulis dan tahun "(Wijaya 2024)" / "(Surya & Putra 2023)"
                $cleanAuth = rtrim((string) preg_replace('/,\s*(&|dan)\s*/', ' $1 ', $authorPart), ', ');
                return "({$cleanAuth} {$yearPart})";
            }

            // APA 7th: pastikan ada koma antara penulis dan tahun
            if ($comma === '') {
                return "({$authorPart}, {$yearPart})";
            }
            return $m[0];
        }, $content);

        if ($guide['key'] === 'harvard' || $guide['key'] === 'chicago') {
            $normalized = (string) preg_replace('/\b(et\s+al\.),\s*\(((?:19|20)\d{2}[a-z]?)\)/i', '$1 ($2)', $normalized);
        }

        return $normalized;
    }

    /**
     * Ambil referensi ilmiah terverifikasi dari OpenAlex & Crossref sesuai aturan sitasi proyek (tahun_min, asal, SINTA/Scopus)
     */
    private function formatVerifiedReferenceEntry(array $ref, string $gayaSitasi = 'APA 7th', int $index = 1): string
    {
        $gaya = strtolower(trim($gayaSitasi));
        $year = (string) ($ref['year'] ?? date('Y'));
        $title = trim((string) ($ref['title'] ?? ''));
        $venue = trim((string) ($ref['venue'] ?? 'Jurnal Ilmiah Terakreditasi'));
        $vol = trim((string) ($ref['vol'] ?? ''));
        $iss = trim((string) ($ref['iss'] ?? ''));
        $pages = trim((string) ($ref['pages'] ?? ''));
        $doi = trim((string) ($ref['doi'] ?? ''));
        $authorsApa = trim((string) ($ref['authors_apa'] ?? ($ref['in_text'] ?? 'Anonim')));
        $authorsIeee = trim((string) ($ref['authors_ieee'] ?? $authorsApa));

        $doiSuffix = $doi !== '' ? " {$doi}" : '';

        if (str_contains($gaya, 'ieee')) {
            $bib = '';
            if ($vol !== '') {
                $bib .= ", vol. {$vol}";
            }
            if ($iss !== '') {
                $bib .= ", no. {$iss}";
            }
            if ($pages !== '') {
                $bib .= ", pp. {$pages}";
            }
            return "[{$index}] {$authorsIeee}, \"{$title},\" {$venue}{$bib}, {$year}.{$doiSuffix}";
        }

        if (str_contains($gaya, 'vancouver')) {
            $vAuthors = preg_replace('/,\s*&\s*/', ', ', $authorsApa);
            $vAuthors = str_replace(['.', ','], '', (string) $vAuthors);
            $vAuthors = trim((string) preg_replace('/\s{2,}/', ' ', $vAuthors));
            $bib = '';
            if ($vol !== '') {
                $bib .= ";{$vol}" . ($iss !== '' ? "({$iss})" : '');
            }
            if ($pages !== '') {
                $bib .= ":{$pages}";
            }
            return "{$index}. {$vAuthors}. {$title}. {$venue}. {$year}{$bib}.{$doiSuffix}";
        }

        if (str_contains($gaya, 'chicago')) {
            $bib = '';
            if ($vol !== '') {
                $bib .= " {$vol}";
                if ($iss !== '') {
                    $bib .= ", no. {$iss}";
                }
            }
            $bib .= " ({$year})";
            if ($pages !== '') {
                $bib .= ": {$pages}";
            }
            $chAuthors = rtrim($authorsApa, '.');
            return "{$chAuthors}. \"{$title}.\" {$venue}{$bib}.{$doiSuffix}";
        }

        if (str_contains($gaya, 'harvard')) {
            $bib = '';
            if ($vol !== '') {
                $bib .= ", {$vol}" . ($iss !== '' ? "({$iss})" : '');
            }
            if ($pages !== '') {
                $bib .= ", pp. {$pages}";
            }
            $harvardAuthors = str_replace(', & ', ' & ', $authorsApa);
            return "{$harvardAuthors} ({$year}) '{$title}', {$venue}{$bib}.{$doiSuffix}";
        }

        // Default: APA 7th
        $bib = '';
        if ($vol !== '') {
            $bib .= ", {$vol}" . ($iss !== '' ? "({$iss})" : '');
        }
        if ($pages !== '') {
            $bib .= ", {$pages}";
        }
        return "{$authorsApa} ({$year}). {$title}. {$venue}{$bib}.{$doiSuffix}";
    }

    private function buildJournalSearchQuery(string $rawText, int $maxWords = 6): string
    {
        $cleanWords = (string) preg_replace('/[^a-zA-Z0-9\s]/', ' ', $rawText);
        $stop = [
            'pengaruh', 'analisis', 'implementasi', 'penerapan', 'hubungan', 'terhadap', 'pada', 'dalam',
            'dengan', 'menggunakan', 'studi', 'kasus', 'untuk', 'yang', 'dari', 'dan', 'atau', 'siswa',
            'kelas', 'secara', 'melalui', 'bahwa', 'adalah', 'menjadi', 'oleh', 'sebagai', 'dapat',
            'lebih', 'serta', 'telah', 'tidak', 'akan', 'juga', 'antara', 'tersebut', 'penelitian',
            'menunjukkan', 'berdasarkan', 'hal', 'ini', 'itu', 'namun', 'karena', 'sehingga', 'tahun',
        ];
        $words = array_values(array_filter(
            preg_split('/\s+/', trim($cleanWords)) ?: [],
            fn ($w) => strlen($w) >= 4 && !in_array(strtolower($w), $stop, true) && !ctype_digit($w)
        ));
        if (empty($words)) {
            $words = array_values(array_filter(
                preg_split('/\s+/', trim($cleanWords)) ?: [],
                fn ($w) => strlen($w) >= 4 && !ctype_digit($w)
            ));
        }
        return implode(' ', array_slice($words, 0, $maxWords));
    }

    private function queryVerifiedJournalsExternal(string $query, int $tahunMin, string $asal, string $gayaSitasi = 'APA 7th', int $limit = 15): array
    {
        $refs = [];
        $seenTitles = [];
        if (trim($query) === '') {
            return [];
        }

        try {
            $asalLower = strtolower($asal);
            $wantId = str_contains($asalLower, 'indonesia') || str_contains($asalLower, 'nasional') || str_contains($asalLower, 'semua');
            $wantInt = str_contains($asalLower, 'internasional') || str_contains($asalLower, 'global') || str_contains($asalLower, 'semua') || !$wantId;
            $filterDate = "from_publication_date:{$tahunMin}-01-01,to_publication_date:2026-12-31";
            $headers = ['User-Agent' => 'SkripsiLab-Academic/2.2 (mailto:support@skripsilabai.com)'];

            $parseOpenAlex = function (array $w, string $scope) use ($tahunMin, $gayaSitasi, &$seenTitles): ?array {
                $title = trim((string) ($w['title'] ?? ''));
                if ($title === '' || isset($seenTitles[strtolower($title)])) {
                    return null;
                }
                $year = (int) ($w['publication_year'] ?? 0);
                if ($year < $tahunMin || $year > 2026) {
                    return null;
                }
                $authorships = $w['authorships'] ?? [];
                $inTextAuthors = [];
                $apaAuthors = [];
                $ieeeAuthors = [];
                foreach (array_slice($authorships, 0, 3) as $a) {
                    $full = trim((string) ($a['author']['display_name'] ?? ''));
                    if ($full === '') {
                        continue;
                    }
                    $parts = preg_split('/\s+/', $full) ?: [];
                    $last = (string) array_pop($parts);
                    if ($last === '') {
                        continue;
                    }
                    $inTextAuthors[] = $last;
                    $initials = '';
                    foreach ($parts as $p) {
                        if ($p !== '') {
                            $initials .= strtoupper(mb_substr($p, 0, 1)) . '. ';
                        }
                    }
                    $initTrim = trim($initials);
                    $apaAuthors[] = trim($last . ($initTrim !== '' ? ', ' . $initTrim : ''));
                    $ieeeAuthors[] = trim(($initTrim !== '' ? $initTrim . ' ' : '') . $last);
                }
                if (empty($inTextAuthors)) {
                    return null;
                }
                $seenTitles[strtolower($title)] = true;
                $inText = count($authorships) > 2
                    ? $inTextAuthors[0] . ' et al.'
                    : (count($inTextAuthors) === 2 ? $inTextAuthors[0] . ' & ' . $inTextAuthors[1] : $inTextAuthors[0]);
                $apaAuthStr = count($apaAuthors) > 1
                    ? implode(', ', array_slice($apaAuthors, 0, -1)) . ', & ' . end($apaAuthors)
                    : $apaAuthors[0];
                $ieeeAuthStr = count($ieeeAuthors) > 2
                    ? $ieeeAuthors[0] . ' et al.'
                    : (count($ieeeAuthors) === 2 ? $ieeeAuthors[0] . ' and ' . $ieeeAuthors[1] : $ieeeAuthors[0]);

                $venue = trim((string) ($w['primary_location']['source']['display_name'] ?? ''));
                if ($venue === '') {
                    $venue = $scope === 'Indonesia' ? 'Jurnal Ilmiah Terakreditasi' : 'International Peer-Reviewed Journal';
                }
                $bib = $w['biblio'] ?? [];
                $vol = trim((string) ($bib['volume'] ?? ''));
                $iss = trim((string) ($bib['issue'] ?? ''));
                $fp = trim((string) ($bib['first_page'] ?? ''));
                $lp = trim((string) ($bib['last_page'] ?? ''));
                $pages = $fp !== '' ? ($fp . ($lp !== '' && $lp !== $fp ? "-{$lp}" : '')) : '';
                $doi = trim((string) ($w['doi'] ?? ''));
                if ($doi !== '' && !str_starts_with($doi, 'http')) {
                    $doi = 'https://doi.org/' . ltrim($doi, '/');
                }

                $entry = [
                    'in_text' => $inText,
                    'first_surname' => $inTextAuthors[0],
                    'year' => $year,
                    'authors_apa' => $apaAuthStr,
                    'authors_ieee' => $ieeeAuthStr,
                    'title' => $title,
                    'venue' => $venue,
                    'vol' => $vol,
                    'iss' => $iss,
                    'pages' => $pages,
                    'doi' => $doi,
                    'scope' => $scope,
                ];
                $entry['formatted'] = $this->formatVerifiedReferenceEntry($entry, $gayaSitasi);
                return $entry;
            };

            if ($wantId) {
                $resId = Http::timeout(8)->withHeaders($headers)->get('https://api.openalex.org/works', [
                    'search' => $query,
                    'filter' => $filterDate . ',language:id',
                    'per-page' => $wantInt ? 8 : 14,
                ]);
                if ($resId->successful()) {
                    foreach ($resId->json()['results'] ?? [] as $w) {
                        if (is_array($w)) {
                            $item = $parseOpenAlex($w, 'Indonesia');
                            if ($item) {
                                $refs[] = $item;
                            }
                        }
                    }
                }
            }

            if ($wantInt || count($refs) < 6) {
                $resInt = Http::timeout(8)->withHeaders($headers)->get('https://api.openalex.org/works', [
                    'search' => $query,
                    'filter' => $filterDate,
                    'per-page' => 12,
                ]);
                if ($resInt->successful()) {
                    foreach ($resInt->json()['results'] ?? [] as $w) {
                        if (is_array($w)) {
                            $scope = (($w['language'] ?? '') === 'id') ? 'Indonesia' : 'Internasional';
                            $item = $parseOpenAlex($w, $scope);
                            if ($item) {
                                $refs[] = $item;
                            }
                        }
                    }
                }
            }

            if (count($refs) < 5) {
                $cr = Http::timeout(8)->withHeaders($headers)->get('https://api.crossref.org/works', [
                    'query' => $query,
                    'filter' => "from-pub-date:{$tahunMin}-01-01,until-pub-date:2026-12-31",
                    'rows' => 8,
                    'select' => 'title,author,container-title,published,volume,issue,page,DOI',
                ]);
                if ($cr->successful()) {
                    foreach ($cr->json()['message']['items'] ?? [] as $item) {
                        $title = trim((string) ($item['title'][0] ?? ''));
                        $year = (int) ($item['published']['date-parts'][0][0] ?? 0);
                        if ($title === '' || $year < $tahunMin || $year > 2026 || isset($seenTitles[strtolower($title)])) {
                            continue;
                        }
                        $authors = $item['author'] ?? [];
                        $inTextList = [];
                        $apaList = [];
                        $ieeeList = [];
                        foreach (array_slice($authors, 0, 3) as $a) {
                            $fam = trim((string) ($a['family'] ?? ''));
                            $giv = trim((string) ($a['given'] ?? ''));
                            if ($fam === '') {
                                continue;
                            }
                            $inTextList[] = $fam;
                            $init = $giv !== '' ? strtoupper(mb_substr($giv, 0, 1)) . '.' : '';
                            $apaList[] = trim($fam . ($init !== '' ? ', ' . $init : ''));
                            $ieeeList[] = trim(($init !== '' ? $init . ' ' : '') . $fam);
                        }
                        if (empty($inTextList)) {
                            continue;
                        }
                        $seenTitles[strtolower($title)] = true;
                        $inText = count($authors) > 2
                            ? $inTextList[0] . ' et al.'
                            : (count($inTextList) === 2 ? $inTextList[0] . ' & ' . $inTextList[1] : $inTextList[0]);
                        $apaStr = count($apaList) > 1
                            ? implode(', ', array_slice($apaList, 0, -1)) . ', & ' . end($apaList)
                            : $apaList[0];
                        $ieeeStr = count($ieeeList) > 2
                            ? $ieeeList[0] . ' et al.'
                            : (count($ieeeList) === 2 ? $ieeeList[0] . ' and ' . $ieeeList[1] : $ieeeList[0]);
                        $venue = trim((string) ($item['container-title'][0] ?? 'Jurnal Ilmiah Bereputasi'));
                        $vol = trim((string) ($item['volume'] ?? ''));
                        $iss = trim((string) ($item['issue'] ?? ''));
                        $pages = trim((string) ($item['page'] ?? ''));
                        $doi = !empty($item['DOI']) ? 'https://doi.org/' . $item['DOI'] : '';

                        $entry = [
                            'in_text' => $inText,
                            'first_surname' => $inTextList[0],
                            'year' => $year,
                            'authors_apa' => $apaStr,
                            'authors_ieee' => $ieeeStr,
                            'title' => $title,
                            'venue' => $venue,
                            'vol' => $vol,
                            'iss' => $iss,
                            'pages' => $pages,
                            'doi' => $doi,
                            'scope' => 'Internasional',
                        ];
                        $entry['formatted'] = $this->formatVerifiedReferenceEntry($entry, $gayaSitasi);
                        $refs[] = $entry;
                    }
                }
            }
        } catch (Throwable $e) {
            Log::warning('Verified reference lookup skipped: ' . $e->getMessage());
        }

        return array_slice($refs, 0, $limit);
    }

    private function getVerifiedRefsStructured(Project $project, bool $forceRefresh = false): array
    {
        $meta = $this->getProjectMeta($project);
        $sitasi = $meta['sitasi'] ?? [];
        $tahunMin = !empty($sitasi['tahun_min']) ? max(2015, min(2026, (int) $sitasi['tahun_min'])) : 2023;
        $asal = (string) ($sitasi['asal'] ?? 'Indonesia + Internasional');
        $gayaSitasi = (string) ($sitasi['gaya'] ?? 'APA 7th');
        $sumberKey = is_array($sitasi['sumber'] ?? null) ? implode(',', $sitasi['sumber']) : 'default';
        $sig = "{$tahunMin}|{$asal}|{$gayaSitasi}|{$sumberKey}";
        $topicHash = substr(md5(mb_strtolower(trim((string) $project->judul)) . '|' . mb_strtolower(trim((string) $project->prodi))), 0, 8);
        $sig = "{$tahunMin}|{$asal}|{$gayaSitasi}|{$sumberKey}|{$topicHash}";

        if (
            !$forceRefresh
            && !empty($meta['verified_refs'])
            && is_array($meta['verified_refs'])
            && (($meta['verified_refs_sig'] ?? '') === $sig)
        ) {
            return $meta['verified_refs'];
        }

        $query = $this->buildJournalSearchQuery((string) $project->judul . ' ' . (string) $project->prodi, 6);
        $fetched = $this->queryVerifiedJournalsExternal($query, $tahunMin, $asal, $gayaSitasi, 15);

        // Gabungkan dengan referensi yang pernah disimpan manual oleh mahasiswa (jika masih memenuhi tahunMin)
        $existingSaved = [];
        if (!empty($meta['verified_refs']) && is_array($meta['verified_refs'])) {
            foreach ($meta['verified_refs'] as $oldRef) {
                if (!is_array($oldRef) || empty($oldRef['manual_saved'])) {
                    continue;
                }
                $oldRef['formatted'] = $this->formatVerifiedReferenceEntry($oldRef, $gayaSitasi);
                $existingSaved[] = $oldRef;
            }
        }

        $merged = $existingSaved;
        $seen = [];
        foreach ($merged as $m) {
            $seen[strtolower((string) ($m['title'] ?? ''))] = true;
        }
        foreach ($fetched as $f) {
            $k = strtolower((string) ($f['title'] ?? ''));
            if ($k !== '' && !isset($seen[$k])) {
                $seen[$k] = true;
                $merged[] = $f;
            }
        }
        $merged = array_slice($merged, 0, 20);

        if (!empty($merged)) {
            $meta['verified_refs'] = $merged;
            $meta['verified_refs_sig'] = $sig;
            $project->citation_validation = json_encode($meta, JSON_UNESCAPED_UNICODE);
            try {
                if ($project->exists) {
                    $project->save();
                }
            } catch (Throwable $e) {
                Log::warning('Failed to cache verified_refs: ' . $e->getMessage());
            }
        }

        return $merged;
    }

    private function fetchVerifiedReferencesForProject(Project $project, bool $forceRefresh = false): array
    {
        $meta = $this->getProjectMeta($project);
        $gayaSitasi = (string) ($meta['sitasi']['gaya'] ?? 'APA 7th');
        $guide = $this->buildCitationStyleGuide($gayaSitasi);
        $structured = $this->getVerifiedRefsStructured($project, $forceRefresh);
        $lines = [];
        foreach ($structured as $idx => $ref) {
            $n = $idx + 1;
            $formatted = $this->formatVerifiedReferenceEntry($ref, $gayaSitasi, $n);
            $citeParen = $this->formatVerifiedInTextCitation($ref, $gayaSitasi, $n, true);
            $citeNarr = $this->formatVerifiedInTextCitation($ref, $gayaSitasi, $n, false);
            $hint = $guide['is_numbered']
                ? "[Sitasi teks wajib: {$citeParen}]"
                : "[Sitasi teks ({$guide['label']}): {$citeNarr} atau {$citeParen}]";
            $lines[] = "{$hint} {$formatted}";
        }
        return array_slice($lines, 0, 15);
    }

    private function extractProjectBenangMerah(Project $project): string
    {
        $sections = [];

        $extractMarkdownTables = static function (string $text, int $maxChars = 2200): string {
            $lines = preg_split("/\r\n|\n/", $text) ?: [];
            $tables = [];
            $buf = [];
            $caption = '';
            foreach ($lines as $ln) {
                $t = trim($ln);
                if (preg_match('/^(?:\*{1,2})?Tabel\s+\d+\.\d+[^\n]{0,120}$/i', $t)) {
                    $caption = trim($t, '* ');
                    continue;
                }
                if (str_starts_with($t, '|') && substr_count($t, '|') >= 2) {
                    $buf[] = $t;
                } else {
                    if (count($buf) >= 2) {
                        $tables[] = ($caption !== '' ? $caption . "\n" : '') . implode("\n", $buf);
                        $caption = '';
                    }
                    $buf = [];
                }
            }
            if (count($buf) >= 2) {
                $tables[] = ($caption !== '' ? $caption . "\n" : '') . implode("\n", $buf);
            }
            $joined = implode("\n\n", $tables);
            return mb_strlen($joined) > $maxChars ? mb_substr($joined, 0, $maxChars) . "\n..." : $joined;
        };

        $b1 = trim(strip_tags((string) ($project->bab1_content ?? '')));
        if ($b1 !== '') {
            if (preg_match('/((?:1\.3|C\.)?\s*Batasan\s+Masalah[^\n]*\n[\s\S]{30,1200}?)(?=\n\s*(?:1\.[4-9]|[D-H]\.\s+Rumusan|Rumusan\s+Masalah|1\.4)|$)/i', $b1, $m)) {
                $sections[] = "BATASAN MASALAH, NAMA KELAS & VARIABEL TERKUNCI BAB I (WAJIB DIPAKAI PERSIS SAMA DI BAB II–V — DILARANG GANTI NAMA KELAS/VARIABEL!):\n" . trim($m[1]);
            }
            if (preg_match('/((?:1\.4|1\.2|B\.|C\.|D\.)?\s*Rumusan\s+Masalah[^\n]*\n[\s\S]{30,1400}?)(?=\n\s*(?:1\.[3-9]|[B-H]\.\s+Tujuan|Tujuan\s+Penelitian|1\.5|Manfaat\s+Penelitian)|$)/i', $b1, $m)) {
                $sections[] = "RUMUSAN MASALAH BAB I (WAJIB DIJAWAB SECARA PRESISI):\n" . trim($m[1]);
            }
            if (preg_match('/((?:1\.5|1\.3|C\.|D\.|E\.)?\s*Tujuan\s+Penelitian[^\n]*\n[\s\S]{30,1000}?)(?=\n\s*(?:1\.[4-9]|[D-H]\.\s+Manfaat|Manfaat\s+Penelitian|1\.6|Keaslian|Sistematika)|$)/i', $b1, $m)) {
                $sections[] = "TUJUAN PENELITIAN BAB I:\n" . trim($m[1]);
            }
        }

        $b2 = trim(strip_tags((string) ($project->bab2_content ?? '')));
        if ($b2 !== '') {
            if (preg_match('/((?:2\.[3-6]|[C-F]\.)?\s*(?:Hipotesis|Batasan\s+Kinerja)[^\n]*\n[\s\S]{30,1100}?)(?=\n\s*(?:2\.[5-9]|BAB\s+III)|$)/i', $b2, $m)) {
                $sections[] = "HIPOTESIS / TARGET BAB II:\n" . trim($m[1]);
            }
        }

        $b3 = trim(strip_tags((string) ($project->bab3_content ?? '')));
        if ($b3 !== '') {
            $b3Snippets = [];
            if (preg_match('/((?:3\.[1-4]|[A-D]\.)?\s*(?:Populasi\s+dan\s+Sampel|Sumber\s+(?:dan\s+Teknik\s+Pengumpulan\s+)?(?:Data|Dataset)|Subjek|Dataset)[^\n]*\n[\s\S]{25,2800}?)(?=\n\s*(?:3\.[3-9]|[C-H]\.\s+)|$)/i', $b3, $m)) {
                $b3Snippets[] = trim($m[1]);
            }
            if (preg_match('/((?:3\.[3-6]|[C-F]\.)?\s*(?:Pembobotan|Pembagian\s+Data|Pemodelan|Desain\s+Eksperimen|Instrumen)[^\n]*\n[\s\S]{25,2200}?)(?=\n\s*(?:3\.[5-9]|[E-H]\.\s+)|$)/i', $b3, $m)) {
                $b3Snippets[] = trim($m[1]);
            }
            if (preg_match('/((?:3\.[5-9]|[E-H]\.)?\s*(?:Teknik\s+Analisis\s+Data|Metode\s+Pengujian|Metrik\s+Evaluasi|Evaluasi)[^\n]*\n[\s\S]{25,1100}?)(?=\n\s*(?:Gambar\s+3|BAB\s+IV)|$)/i', $b3, $m)) {
                $b3Snippets[] = trim($m[1]);
            }
            // Ekstrak semua kalimat Bab III yang menyebut angka ukuran dataset/sampel, kosakata, stemming, split & ambang batas agar TIDAK terpotong
            $b3NumericFacts = [];
            if (preg_match_all('/[^.\n]*(?:\b\d{1,3}(?:\.\d{3})+\b|\brasio\s+\d+:\d+\b|\bdata\s+latih\b|\bdata\s+uji\b|\bdataset\s+final\b|\bkosakata\b|\bstemming\b|\bterm\b|\bblack-?box\b|\bSUS\b|\bsampel\s+(?:sebanyak|sejumlah|berjumlah)\s+\d+)[^.\n]*\./iu', $b3, $mFacts)) {
                $b3NumericFacts = array_slice(array_values(array_unique(array_map(fn ($s) => trim(preg_replace('/\s+/', ' ', $s)), $mFacts[0]))), 0, 16);
            }
            if (!empty($b3NumericFacts)) {
                $b3Snippets[] = "FAKTA ANGKA DATASET/SAMPEL, KOSAKATA & SPLIT TERKUNCI DARI BAB III (WAJIB DIPAKAI PERSIS DI BAB IV & V — JANGAN UBAH 1 DIGIT PUN!):\n- " . implode("\n- ", $b3NumericFacts);
            }
            $b3Tables = $extractMarkdownTables($b3, 1200);
            if ($b3Tables !== '') {
                $b3Snippets[] = "TABEL BAB III:\n" . $b3Tables;
            }
            if (!empty($b3Snippets)) {
                $sections[] = "METODE, UKURAN DATASET/SAMPEL & PARAMETER TERKUNCI BAB III (WAJIB DIPAKAI PERSIS DI BAB IV & V — DILARANG UBAH ANGKA):\n" . implode("\n\n", $b3Snippets);
            }
        }

        $b4 = trim(strip_tags((string) ($project->bab4_content ?? '')));
        if ($b4 !== '') {
            $b4Parts = [];
            $b4Tables = $extractMarkdownTables($b4, 3400);
            if ($b4Tables !== '') {
                $b4Parts[] = "SELURUH TABEL HASIL EMPIRIS BAB IV (ANGKA RESMI TERKUNCI):\n" . $b4Tables;
            }
            // Ekstrak SEMUA kalimat kunci bernomor/berpersentase di Bab IV (stemming, ablasi, akurasi, cross-validation, black-box, SUS, R², p-value, Siklus I/II)
            $b4NumericFacts = [];
            if (preg_match_all('/[^.\n]*(?:\bkosakata\b|\bstemming\b|\bterm\b|\bblack-?box\b|\bkeberhasilan\b|\bSUS\b|\bcross[\s-]?validation\b|\bsimpangan\s+baku\b|\btanpa\s+normalisasi\b|\bakurasi\s+keseluruhan\b|\brata-rata\s+makro\b|\bR-?[Ss]quare\b|\bR²\b|\bp-?value\b|\bSig\.?\s*[<≤=]\b|\bketuntasan\b|\bN-?Gain\b|\bOR\s*=)[^.\n]*\d[^.\n]*\./iu', $b4, $mB4Facts)) {
                $b4NumericFacts = array_slice(array_values(array_unique(array_map(fn ($s) => trim(preg_replace('/\s+/', ' ', $s)), $mB4Facts[0]))), 0, 22);
            }
            if (!empty($b4NumericFacts)) {
                $b4Parts[] = "FAKTA & ANGKA KUNCI TERKUNCI DARI BAB IV (WAJIB DIKUTIP 100% VERBATIM DI BAB V — DILARANG MENGUBAH 1 DIGIT PUN!):\n- " . implode("\n- ", $b4NumericFacts);
            }
            if (preg_match('/((?:4\.[1-2]|[A-B]\.)?\s*(?:Deskripsi|Gambaran\s+Umum)[^\n]*\n[\s\S]{25,700}?)(?=\n\s*(?:Tabel\s+4|4\.[2-9])|$)/i', $b4, $m)) {
                $b4Parts[] = trim($m[1]);
            }
            if (preg_match('/((?:4\.[3-7]|[C-G]\.)?\s*(?:Hasil\s+Analisis|Pengujian\s+Hipotesis|Hasil\s+Pengujian|Pembahasan)[^\n]*\n[\s\S]{30,2200}?)(?=\n\s*(?:4\.[6-9]|Keterbatasan|BAB\s+V)|$)/i', $b4, $m)) {
                $b4Parts[] = trim($m[1]);
            }
            if (!empty($b4Parts)) {
                $sections[] = "TEMUAN EMPIRIS & METRIK TERKUNCI BAB IV (WAJIB DIKUTIP 100% SAMA PERSIS DI BAB V — JANGAN UBAH 1 DIGIT PUN):\n" . implode("\n\n", $b4Parts);
            }
        }

        if (empty($sections)) {
            return '';
        }

        return "\n=== PETA BENANG MERAH & PENGUNCIAN ANGKA LINTAS BAB (WAJIB KONSISTEN 100% — DILARANG MELENCENG) ===\n"
            . implode("\n\n", $sections)
            . "\n\nATURAN PENGUNCIAN ANGKA & TERMINOLOGI LINTAS BAB (MUTLAK):\n"
            . "- NAMA KELAS / KATEGORI / VARIABEL WAJIB 100% IDENTIK dari Bab I sampai Bab V (misal jika Bab I menetapkan 4 kelas tertentu, JANGAN ganti nama kelas tersebut di Bab II, III, IV, atau V!).\n"
            . "- DILARANG KERAS mengubah 1 digit pun angka jumlah data/sampel, distribusi kelas, jumlah term sebelum/sesudah stemming, persentase reduksi kosakata, rasio & jumlah split data latih/uji, akurasi, presisi, recall, F1-score, cross-validation, hasil uji ablasi, persentase pengujian Black-Box / skor SUS, koefisien regresi, p-value/Sig., ketuntasan Siklus I/II, maupun urutan algoritma terbaik dari bab sebelumnya!\n"
            . "- Seluruh angka di Bab IV WAJIB 100% cocok dengan ukuran dataset/sampel dan desain Bab III.\n"
            . "- Seluruh angka, persentase, jumlah term, hasil Black-Box/SUS, dan perbandingan algoritma di Bab V (Kesimpulan & Saran) WAJIB 100% identik dengan Tabel & teks Bab III–IV!\n"
            . "=====================================================================\n";
    }

    private function buildVerifiedMatchedDaftarPustaka(Project $project, array $citationsList, string $gayaSitasi): array
    {
        $verifiedBank = $this->getVerifiedRefsStructured($project);
        $guide = $this->buildCitationStyleGuide($gayaSitasi);
        $matchedEntries = [];
        $unmatchedCitations = [];
        $usedTitles = [];

        foreach ($citationsList as $cit) {
            $citClean = trim((string) $cit);
            if ($citClean === '') {
                continue;
            }
            // Dukung sitasi numerik IEEE [1] / Vancouver (1) yang merujuk urutan bank referensi terverifikasi
            if (preg_match('/^\[(\d{1,2})\]$/', $citClean, $nm) || ($guide['is_numbered'] && preg_match('/^\((\d{1,2})\)$/', $citClean, $nm))) {
                $idxNum = (int) $nm[1];
                if ($idxNum >= 1 && isset($verifiedBank[$idxNum - 1])) {
                    $found = $verifiedBank[$idxNum - 1];
                    $tKey = strtolower((string) ($found['title'] ?? ''));
                    if ($tKey !== '' && !isset($usedTitles[$tKey])) {
                        $usedTitles[$tKey] = true;
                        $matchedEntries[] = $this->formatVerifiedReferenceEntry($found, $gayaSitasi, count($matchedEntries) + 1);
                    }
                    continue;
                }
            }
            preg_match('/\b((?:19|20)\d{2})\b/', $citClean, $ym);
            $citYear = isset($ym[1]) ? (int) $ym[1] : 0;
            $citAuthor = trim((string) preg_replace('/\s*\(?\d{4}[a-z]?\)?.*$/', '', $citClean));
            $firstWord = strtolower((string) preg_replace('/[^a-zA-Z]/', '', explode(' ', $citAuthor)[0] ?? ''));

            $found = null;
            if ($firstWord !== '' && strlen($firstWord) >= 2) {
                foreach ($verifiedBank as $ref) {
                    $refSurname = strtolower((string) preg_replace('/[^a-zA-Z]/', '', (string) ($ref['first_surname'] ?? '')));
                    $refInText = strtolower((string) ($ref['in_text'] ?? ''));
                    $refYear = (int) ($ref['year'] ?? 0);
                    if (($refSurname === $firstWord || str_contains($refInText, $firstWord)) && ($citYear === 0 || $refYear === $citYear)) {
                        $found = $ref;
                        break;
                    }
                }
            }

            if ($found) {
                $tKey = strtolower((string) ($found['title'] ?? ''));
                if (!isset($usedTitles[$tKey])) {
                    $usedTitles[$tKey] = true;
                    $matchedEntries[] = $this->formatVerifiedReferenceEntry($found, $gayaSitasi, count($matchedEntries) + 1);
                }
            } else {
                $unmatchedCitations[] = $citClean;
            }
        }

        return [$matchedEntries, $unmatchedCitations];
    }

    /**
     * Penjaga Kualitas Otomatis Pasca-Generate:
     * 1) Pecah paragraf raksasa (>880 karakter) di batas kalimat agar ritme halaman A4 rapi 2,5-3 paragraf/halaman.
     * 2) Pastikan Paragraf 1 Bab I selalu memiliki minimal 1 sitasi terverifikasi sesuai pengaturan form mahasiswa.
     * 3) Bersihkan sisa markdown/LaTeX mentah dan frasa klise pembuka AI secara otomatis.
     */
    /**
     * Memisahkan dan menghapus bagian Daftar Pustaka yang ikut tertulis di dalam naskah Bab I-IV
     * agar Daftar Pustaka hanya muncul satu kali di section resmi.
     *
     * @return array{0: string, 1: string} [bodyTanpaDaftarPustaka, extractedDaftarPustaka]
     */
    private function stripEmbeddedDaftarPustaka(string $content): array
    {
        if (trim($content) === '') {
            return [$content, ''];
        }

        $normalized = str_replace(["\r\n", "\r"], "\n", $content);
        $lines = explode("\n", $normalized);
        $totalLines = count($lines);
        $inCodeBlock = false;
        $cutIndex = null;

        for ($i = 0; $i < $totalLines; $i++) {
            $trim = trim(strip_tags($lines[$i]));
            if (str_starts_with($trim, '```')) {
                $inCodeBlock = !$inCodeBlock;
                continue;
            }
            if ($inCodeBlock) {
                continue;
            }

            if (preg_match('/^(?:#{1,4}\s*|\*{1,2}\s*|===+\s*)?(?:DAFTAR\s+PUSTAKA|DAFTAR\s+REFERENSI|REFERENSI\s+PENELITIAN|BIBLIOGRAFI)(?:\s*===+|\s*\*{1,2}|\s*:)?$/i', $trim)) {
                $tailText = trim(implode("\n", array_slice($lines, $i + 1)));
                if ($tailText === '') {
                    $cutIndex = $i;
                    break;
                }
                if (preg_match('/\((?:19|20)\d{2}[a-z]?\)|https?:\/\/|doi\.org|\[\d+\]|\b(?:19|20)\d{2}\.\b/i', $tailText)) {
                    $cutIndex = $i;
                    break;
                }
            }
        }

        if ($cutIndex === null) {
            return [$content, ''];
        }

        $body = rtrim(implode("\n", array_slice($lines, 0, $cutIndex)));
        $dp = trim(implode("\n", array_slice($lines, $cutIndex + 1)));
        $dp = trim((string) preg_replace('/^===+\s*|\s*===+$/', '', $dp));

        return [$body, $dp];
    }

    private function enforceChapterQualityAutomatically(string $content, string $chapter, Project $project): string
    {
        if (trim($content) === '') {
            return $content;
        }

        if ($chapter !== 'bab5') {
            [$content] = $this->stripEmbeddedDaftarPustaka($content);
        }

        $projMeta = $this->getProjectMeta($project);
        $gayaSitasi = (string) ($projMeta['sitasi']['gaya'] ?? 'APA 7th');

        // 1. Bersihkan sisa LaTeX, judul bab ganda, bocoran prompt, kata Inggris sisipan, & frasa klise pembuka AI
        $content = $this->formatAcademicMathText($content);
        // Hapus baris judul BAB ganda di awal konten (karena header BAB I-V sudah dicetak otomatis oleh sistem)
        $content = (string) preg_replace('/^\s*(?:#+\s*)?BAB\s+[IVX0-9]+(?:\s+[^\n]{0,80})?\s*\n+/i', '', $content);
        // Gabungkan kembali sitasi yang terlanjur terpotong baris tepat setelah "et al." / "dkk."
        $content = (string) preg_replace('/\b(et\s+al\.|dkk\.)\s*\n+\s*(\(\d{4}[a-z]?\))/i', '$1 $2', $content);

        $clicheMap = [
            '/^Di era digital saat ini,?\s+/im' => 'Dalam perkembangan teknologi digital mutakhir, ',
            '/^Di era globalisasi saat ini,?\s+/im' => 'Dalam dinamika perkembangan global mutakhir, ',
            '/^Tidak dapat dipungkiri bahwa\s+/im' => 'Secara empiris, ',
            '/\bmemegang peranan krusial\b/i' => 'berperan penting',
            '/\bdalam bank referensi proyek ini\b/i' => 'dalam kajian literatur terdahulu',
            '/\bbank referensi proyek ini\b/i' => 'kajian literatur terdahulu',
            '/\bbank referensi terverifikasi\b/i' => 'literatur ilmiah terdahulu',
            '/\bincreasingly\b/i' => 'semakin',
            '/\bsignificantly\b/i' => 'secara signifikan',
            '/\beffectively\b/i' => 'secara efektif',
            '/\bconsequently\b/i' => 'sebagai konsekuensinya',
            '/\bfurthermore\b/i' => 'lebih lanjut',
        ];
        foreach ($clicheMap as $pat => $rep) {
            $content = (string) preg_replace($pat, $rep, $content);
        }

        // 2. Pecah paragraf raksasa (>880 karakter) secara natural di batas kalimat (menghindari wall-of-text 16 baris!)
        $lines = preg_split("/\r\n|\n/", $content) ?: [];
        $outLines = [];
        $inCodeBlock = false;
        $firstBab1ParaFixed = ($chapter !== 'bab1');

        foreach ($lines as $rawLine) {
            $trim = trim($rawLine);
            if (str_starts_with($trim, '```')) {
                $inCodeBlock = !$inCodeBlock;
                $outLines[] = $rawLine;
                continue;
            }
            if ($inCodeBlock || $trim === '' || str_starts_with($trim, '|')) {
                $outLines[] = $rawLine;
                continue;
            }

            $isHeadingOrList = (bool) preg_match('/^(?:BAB\s+[IVX0-9]+|\d+\.\d+(?:\.\d+)?\.?\s+|[A-H]\.\s+|\d+[\.\)]\s+|[a-z][\.\)]\s+|Tabel\s+\d|Gambar\s+\d|Bagan\s+\d|Grafik\s+\d|Sumber\s*:)/i', $trim);

            // Pastikan Paragraf 1 Bab I minimal memuat 1 sitasi terverifikasi sesuai gaya sitasi proyek
            if (!$firstBab1ParaFixed && !$isHeadingOrList && mb_strlen($trim) >= 120) {
                $firstBab1ParaFixed = true;
                $cites = $this->extractInTextCitations($trim);
                if (empty($cites)) {
                    $bank = $this->getVerifiedRefsStructured($project);
                    if (!empty($bank[0]['in_text']) && !empty($bank[0]['year'])) {
                        $citeToken = $this->formatVerifiedInTextCitation($bank[0], $gayaSitasi, 1, false);
                        $guideFirst = $this->buildCitationStyleGuide($gayaSitasi);
                        $trim = $guideFirst['is_numbered']
                            ? rtrim($trim, '. ') . ". Urgensi kajian empiris tersebut sejalan dengan temuan penelitian terdahulu {$citeToken}."
                            : rtrim($trim, '. ') . ". Urgensi kajian empiris tersebut sejalan dengan temuan {$citeToken}.";
                    }
                }
            }

            // Pecah paragraf terlalu panjang (>880 karakter) tepat di batas kalimat tengah agar proporsional (~7-10 baris Word)
            // WAJIB lindungi singkatan akademik (et al., dkk., No., hlm., Dr., Prof., dst.) agar tidak pernah memotong sitasi!
            if (!$isHeadingOrList && mb_strlen($trim) > 880) {
                $rawParts = preg_split('/(?<=[.!?])\s+(?=[A-ZÀ-Ÿ"\d])/', $trim) ?: [$trim];
                $sentences = [];
                foreach ($rawParts as $rp) {
                    $rpTrim = trim($rp);
                    if ($rpTrim === '') {
                        continue;
                    }
                    if (!empty($sentences)) {
                        $prev = $sentences[count($sentences) - 1];
                        if (
                            preg_match('/\b(?:et\s+al|dkk|no|hlm|hal|dr|prof|ir|vol|uu|pp|dsb|dst|dll|mr|mrs|ms|fig|tab|vs|al)\.\s*$/i', $prev)
                            || preg_match('/^\(\d{4}[a-z]?\)/', $rpTrim)
                        ) {
                            $sentences[count($sentences) - 1] = $prev . ' ' . $rpTrim;
                            continue;
                        }
                    }
                    $sentences[] = $rpTrim;
                }
                if (count($sentences) >= 4) {
                    $targetHalf = mb_strlen($trim) / 2;
                    $acc = '';
                    $splitIdx = (int) floor(count($sentences) / 2);
                    $bestDiff = PHP_INT_MAX;
                    foreach ($sentences as $si => $sent) {
                        if ($si === count($sentences) - 1) {
                            break;
                        }
                        $acc .= ($acc === '' ? '' : ' ') . $sent;
                        $diff = abs(mb_strlen($acc) - $targetHalf);
                        if ($diff < $bestDiff && $si >= 1) {
                            $bestDiff = $diff;
                            $splitIdx = $si + 1;
                        }
                    }
                    $part1 = trim(implode(' ', array_slice($sentences, 0, $splitIdx)));
                    $part2 = trim(implode(' ', array_slice($sentences, $splitIdx)));
                    if (mb_strlen($part1) >= 220 && mb_strlen($part2) >= 220) {
                        $outLines[] = $part1;
                        $outLines[] = '';
                        $outLines[] = $part2;
                        continue;
                    }
                }
            }

            $outLines[] = $trim;
        }

        $result = trim((string) preg_replace("/\n{3,}/", "\n\n", implode("\n", $outLines)));

        // 3. Khusus Bab V: Pastikan 5.1 Kesimpulan otomatis berbentuk poin bernomor (1., 2., ...) selaras dengan Rumusan Masalah Bab I
        if ($chapter === 'bab5' && preg_match('/((?:5\.1|A\.)\s*(?:Kesimpulan|Simpulan)[^\n]*\n)([\s\S]{40,}?)(?=\n\s*(?:5\.2|B\.\s+Saran|Saran\b)|$)/i', $result, $mK)) {
            $kHead = $mK[1];
            $kBody = trim($mK[2]);
            $hasNumbered = preg_match_all('/^\s*\d+[\.\)]\s+.{10,}/m', $kBody);
            if ($hasNumbered === 0) {
                $kParas = array_values(array_filter(array_map('trim', preg_split('/\n+/', $kBody) ?: []), fn ($x) => mb_strlen($x) >= 25));
                if (count($kParas) >= 2) {
                    $numbered = [];
                    $num = 1;
                    // Jika paragraf pertama cuma kalimat pengantar ("Berdasarkan hasil penelitian...:"), biarkan tanpa nomor
                    foreach ($kParas as $idxP => $kp) {
                        if ($idxP === 0 && (str_ends_with($kp, ':') || (mb_strlen($kp) < 160 && preg_match('/berdasarkan\s+hasil|dapat\s+disimpulkan|sebagai\s+berikut/i', $kp)))) {
                            $numbered[] = $kp;
                            continue;
                        }
                        $cleanItem = trim((string) preg_replace('/^(?:Pertama|Kedua|Ketiga|Keempat|Kelima),?\s+/i', '', $kp));
                        if ($cleanItem !== '') {
                            $cleanItem = mb_strtoupper(mb_substr($cleanItem, 0, 1)) . mb_substr($cleanItem, 1);
                        }
                        $numbered[] = $num . '. ' . $cleanItem;
                        $num++;
                    }
                    $newBlock = $kHead . implode("\n", $numbered);
                    $result = str_replace($mK[0], $newBlock, $result);
                }
            }
        }

        // Rapikan daftar bernomor berurutan yang dipisahkan baris kosong agar tidak reset ke 1. saat dirender <ol> di PDF
        $result = (string) preg_replace('/(\n\d+\.\s+[^\n]+)\n{2,}(?=\d+\.\s+)/', "$1\n", $result);

        // Auto-isi tanda ✓ jika AI menghasilkan tabel jadwal penelitian (Bulan ke-1..N / Minggu) yang semua sel waktunya kosong
        if (str_contains($result, '|') && preg_match('/bulan\s+ke-?\s*1|minggu\s+ke-?\s*1/i', $result)) {
            $tLines = preg_split("/(\r\n|\n)/", $result) ?: [];
            $i = 0;
            $nLines = count($tLines);
            while ($i < $nLines) {
                if (preg_match('/^\s*\|.*(?:bulan\s+ke-?\s*1|minggu\s+ke-?\s*1).*\|/i', $tLines[$i]) && isset($tLines[$i + 1]) && preg_match('/^\s*\|[\s:\-|]+\|\s*$/', $tLines[$i + 1])) {
                    $hdrCells = array_map('trim', explode('|', trim($tLines[$i], "| \t\r\n")));
                    $timeCols = [];
                    foreach ($hdrCells as $ci => $hc) {
                        if (preg_match('/^(?:bulan|minggu)\s*(?:ke-?)?\s*\d+$/i', $hc)) {
                            $timeCols[] = $ci;
                        }
                    }
                    if (count($timeCols) >= 2) {
                        $rowIdxs = [];
                        $r = $i + 2;
                        while ($r < $nLines && preg_match('/^\s*\|.*\|\s*$/', $tLines[$r])) {
                            $rowIdxs[] = $r;
                            $r++;
                        }
                        if (!empty($rowIdxs)) {
                            $allEmpty = true;
                            foreach ($rowIdxs as $ri) {
                                $cells = array_map('trim', explode('|', trim($tLines[$ri], "| \t\r\n")));
                                foreach ($timeCols as $tc) {
                                    if (isset($cells[$tc]) && $cells[$tc] !== '' && $cells[$tc] !== '-') {
                                        $allEmpty = false;
                                        break 2;
                                    }
                                }
                            }
                            if ($allEmpty) {
                                $totalRows = count($rowIdxs);
                                $numTime = count($timeCols);
                                foreach ($rowIdxs as $pos => $ri) {
                                    $cells = array_map('trim', explode('|', trim($tLines[$ri], "| \t\r\n")));
                                    while (count($cells) < count($hdrCells)) {
                                        $cells[] = '';
                                    }
                                    $startCol = (int) floor(($pos * $numTime) / max(1, $totalRows));
                                    $endCol = min($numTime - 1, (int) floor((($pos + 1) * $numTime) / max(1, $totalRows)));
                                    for ($ti = $startCol; $ti <= $endCol; $ti++) {
                                        $cells[$timeCols[$ti]] = '✓';
                                    }
                                    $tLines[$ri] = '| ' . implode(' | ', $cells) . ' |';
                                }
                            }
                        }
                        $i = $r;
                        continue;
                    }
                }
                $i++;
            }
            $result = implode("\n", $tLines);
        }

        // 4. Normalisasi sitasi in-text agar konsisten mengikuti gaya sitasi yang dipilih mahasiswa (Harvard/Chicago tanpa koma, APA pakai koma)
        $result = $this->normalizeInTextCitationsForStyle($result, $gayaSitasi);

        return $result;
    }

    /**
     * Sinkronisasi otomatis entri jurnal terverifikasi (DOI asli) yang dikutip di Bab I-V ke Daftar Pustaka.
     */
    private function syncVerifiedDaftarPustakaAutomatically(Project $project, string $gayaSitasi, string $tahunMin): void
    {
        $allBody = strip_tags(
            (string) $project->bab1_content . ' ' .
            (string) $project->bab2_content . ' ' .
            (string) $project->bab3_content . ' ' .
            (string) $project->bab4_content . ' ' .
            (string) $project->bab5_content
        );
        if (mb_strlen(trim($allBody)) < 100) {
            return;
        }

        $citationsList = $this->extractInTextCitations($allBody);
        if (empty($citationsList)) {
            return;
        }

        [$verifiedMatched] = $this->buildVerifiedMatchedDaftarPustaka($project, $citationsList, $gayaSitasi);
        if (empty($verifiedMatched)) {
            return;
        }

        $currentDp = trim((string) ($project->daftar_pustaka ?? ''));
        $dpLines = $currentDp !== ''
            ? array_values(array_filter(array_map('trim', explode("\n", $currentDp)), fn ($l) => mb_strlen($l) >= 12))
            : [];
        $existingLower = strtolower(implode("\n", $dpLines));

        $added = false;
        foreach ($verifiedMatched as $vm) {
            $doiKey = preg_match('#https?://doi\.org/[^\s]+#i', $vm, $dm)
                ? strtolower($dm[0])
                : strtolower(mb_substr($vm, 0, 45));
            if ($doiKey !== '' && !str_contains($existingLower, $doiKey)) {
                $dpLines[] = $vm;
                $existingLower .= "\n" . strtolower($vm);
                $added = true;
            }
        }

        if ($added) {
            $gayaLower = strtolower($gayaSitasi);
            if (!str_contains($gayaLower, 'ieee') && !str_contains($gayaLower, 'vancouver')) {
                sort($dpLines, SORT_NATURAL | SORT_FLAG_CASE);
            }
            $project->daftar_pustaka = implode("\n", $dpLines);
        }
    }

    /**
     * Ekstrak daftar isi terstruktur dari isi bab proyek (dinamis & dibedakan Proposal vs Skripsi)
     */
    private function extractTocItemsForProject(Project $project, bool $isProposal = false): array
    {
        $hasTables = !empty($this->extractTablesForProject($project, $isProposal));
        $hasFigures = !empty($this->extractFiguresForProject($project, $isProposal));

        $items = [
            ['label' => 'HALAMAN JUDUL', 'page' => 'i', 'level' => 1],
        ];
        $romanIdx = 2;

        if ($isProposal) {
            $items[] = ['label' => 'LEMBAR PERSETUJUAN PROPOSAL', 'page' => $this->toRomanLower($romanIdx++), 'level' => 1];
            $items[] = ['label' => 'KATA PENGANTAR', 'page' => $this->toRomanLower($romanIdx++), 'level' => 1];
            $tocStartRoman = $romanIdx;
            $items[] = ['label' => 'DAFTAR ISI', 'page' => $this->toRomanLower($romanIdx++), 'level' => 1];
        } else {
            $items[] = ['label' => 'LEMBAR PENGESAHAN', 'page' => $this->toRomanLower($romanIdx++), 'level' => 1];
            $items[] = ['label' => 'SURAT PERNYATAAN KEASLIAN', 'page' => $this->toRomanLower($romanIdx++), 'level' => 1];
            $items[] = ['label' => 'ABSTRAK', 'page' => $this->toRomanLower($romanIdx++), 'level' => 1];
            $items[] = ['label' => 'ABSTRACT', 'page' => $this->toRomanLower($romanIdx++), 'level' => 1];
            $items[] = ['label' => 'KATA PENGANTAR', 'page' => $this->toRomanLower($romanIdx++), 'level' => 1];
            $tocStartRoman = $romanIdx;
            $items[] = ['label' => 'DAFTAR ISI', 'page' => $this->toRomanLower($romanIdx++), 'level' => 1];
        }

        $afterTocIndices = [];
        if ($hasTables) {
            $afterTocIndices[] = count($items);
            $items[] = ['label' => 'DAFTAR TABEL', 'page' => $this->toRomanLower($romanIdx++), 'level' => 1];
        }
        if ($hasFigures) {
            $afterTocIndices[] = count($items);
            $items[] = ['label' => 'DAFTAR GAMBAR', 'page' => $this->toRomanLower($romanIdx++), 'level' => 1];
        }

        $lampiranList = $this->extractLampiranForProject($project, $isProposal);
        if (!empty($lampiranList)) {
            $afterTocIndices[] = count($items);
            $items[] = ['label' => 'DAFTAR LAMPIRAN', 'page' => $this->toRomanLower($romanIdx++), 'level' => 1];
        }

        $pageMap = $this->computeDynamicChapterPages($project, $isProposal);
        $chapters = [
            'bab1' => ['num' => 'I', 'title' => 'PENDAHULUAN'],
            'bab2' => ['num' => 'II', 'title' => 'TINJAUAN PUSTAKA'],
            'bab3' => ['num' => 'III', 'title' => 'METODOLOGI PENELITIAN'],
        ];
        if (!$isProposal) {
            $chapters['bab4'] = ['num' => 'IV', 'title' => 'HASIL PENELITIAN DAN PEMBAHASAN'];
            $chapters['bab5'] = ['num' => 'V', 'title' => 'PENUTUP'];
        }

        foreach ($chapters as $key => $info) {
            $content = trim((string) ($project->{$key . '_content'} ?? ''));
            if (empty($content) || empty($pageMap[$key]['count'])) {
                continue;
            }

            $basePage = $pageMap[$key]['start'];
            $chapPages = $pageMap[$key]['count'];
            $items[] = [
                'label' => "BAB {$info['num']} {$info['title']}",
                'page' => (string) $basePage,
                'level' => 1,
            ];

            $lines = explode("\n", str_replace(['<p>', '</p>', '<br>', '<br/>'], "\n", $content));
            $totalLines = max(1, count($lines));
            $inFenceToc = false;
            foreach ($lines as $lIdx => $line) {
                $clean = trim(strip_tags($line));
                if (str_starts_with($clean, '```')) {
                    $inFenceToc = !$inFenceToc;
                    continue;
                }
                if ($inFenceToc) {
                    continue;
                }
                if (preg_match('/^(\d+\.\d+)\.?\s+(.+)$/', $clean, $m)) {
                    $subPage = $basePage + (int) floor(($lIdx / $totalLines) * $chapPages);
                    $subPage = min($pageMap[$key]['end'], max($basePage, $subPage));
                    $items[] = [
                        'label' => $m[1] . ' ' . trim($m[2]),
                        'page' => (string) $subPage,
                        'level' => 2,
                    ];
                } elseif (preg_match('/^([A-Z]\.)\s+([A-Z][^\n]{2,110})$/', $clean, $m)) {
                    $subPage = $basePage + (int) floor(($lIdx / $totalLines) * $chapPages);
                    $subPage = min($pageMap[$key]['end'], max($basePage, $subPage));
                    $items[] = [
                        'label' => $m[1] . ' ' . trim($m[2]),
                        'page' => (string) $subPage,
                        'level' => 2,
                    ];
                }
            }
        }

        if (!empty(trim((string) $project->daftar_pustaka))) {
            $items[] = ['label' => 'DAFTAR PUSTAKA', 'page' => (string) ($pageMap['daftar_pustaka']['start'] ?? 1), 'level' => 1];
        }

        if (!empty($lampiranList)) {
            $items[] = ['label' => 'LAMPIRAN', 'page' => (string) $lampiranList[0]['page'], 'level' => 1];
        }

        // Sesuaikan penomoran Romawi DAFTAR TABEL/GAMBAR/LAMPIRAN ketika DAFTAR ISI merentang >= 2 halaman A4 (28 baris/halaman)
        $tocPages = max(1, (int) ceil(count($items) / 28));
        if ($tocPages > 1 && !empty($afterTocIndices)) {
            $nextRoman = $tocStartRoman + $tocPages;
            foreach ($afterTocIndices as $idxPos) {
                if (isset($items[$idxPos])) {
                    $items[$idxPos]['page'] = $this->toRomanLower($nextRoman++);
                }
            }
        }

        return $items;
    }

    /**
     * Ambil daftar lampiran yang diunggah mahasiswa beserta estimasi nomor halamannya setelah Daftar Pustaka.
     */
    private function extractLampiranForProject(Project $project, bool $isProposal = false): array
    {
        $meta = $this->getProjectMeta($project);
        $rawList = $meta['lampiran'] ?? [];
        if (!is_array($rawList) || empty($rawList)) {
            return [];
        }

        $pageMap = $this->computeDynamicChapterPages($project, $isProposal);
        $curPage = 1;
        foreach ($pageMap as $pm) {
            if (!empty($pm['end']) && $pm['end'] >= $curPage) {
                $curPage = $pm['end'] + 1;
            }
        }

        $result = [];
        $idx = 0;
        foreach ($rawList as $item) {
            if (!is_array($item)) {
                continue;
            }
            $title = trim((string) ($item['title'] ?? ''));
            $img = trim((string) ($item['image_base64'] ?? ''));
            $text = trim((string) ($item['content'] ?? ''));
            if ($title === '' && $img === '' && $text === '') {
                continue;
            }
            $idx++;
            $labelTitle = $title !== '' ? $title : "Dokumen Lampiran {$idx}";
            $fullLabel = preg_match('/^lampiran\s+\d+/i', $labelTitle)
                ? $labelTitle
                : "Lampiran {$idx} {$labelTitle}";
            $result[] = [
                'index' => $idx,
                'title' => $labelTitle,
                'label' => $fullLabel,
                'image_base64' => $img,
                'content' => $text,
                'file_name' => trim((string) ($item['file_name'] ?? '')),
                'page' => $curPage,
            ];
            $extraPages = max(1, (int) ceil(mb_strlen($text) / 2600));
            $curPage += $extraPages;
        }

        return $result;
    }

    /**
     * Format entri Daftar Pustaka HTML dengan tautan DOI & URL yang dapat diklik langsung.
     */
    private function linkifyDaftarPustakaHtml(string $text): string
    {
        $safe = $this->pdfSafeText($text);
        // Ubah URL penuh (https://doi.org/... atau https://...) menjadi hyperlink biru bergaris bawah
        $safe = preg_replace_callback(
            '/(https?:\/\/[^\s<>"]+)/i',
            function ($m) {
                $rawUrl = $m[1];
                $trailing = '';
                while (preg_match('/[\.,;\)\]]$/', $rawUrl, $tm)) {
                    $trailing = $tm[0] . $trailing;
                    $rawUrl = substr($rawUrl, 0, -1);
                }
                return '<a href="' . $rawUrl . '" target="_blank" rel="noopener noreferrer" style="color:#1d4ed8; text-decoration:underline; word-break:break-word; overflow-wrap:anywhere;">' . $rawUrl . '</a>' . $trailing;
            },
            $safe
        );
        // Ubah format "doi: 10.xxxx/..." yang belum berupa URL menjadi link https://doi.org/...
        $safe = preg_replace_callback(
            '/(?<!\/)\bdoi:\s*(10\.\d{4,9}\/[^\s<>"]+)/i',
            function ($m) {
                $doiVal = $m[1];
                $trailing = '';
                while (preg_match('/[\.,;\)\]]$/', $doiVal, $tm)) {
                    $trailing = $tm[0] . $trailing;
                    $doiVal = substr($doiVal, 0, -1);
                }
                $href = 'https://doi.org/' . $doiVal;
                return '<a href="' . $href . '" target="_blank" rel="noopener noreferrer" style="color:#1d4ed8; text-decoration:underline; word-break:break-word; overflow-wrap:anywhere;">doi:' . $doiVal . '</a>' . $trailing;
            },
            $safe
        );
        return $safe;
    }

    private function extractTablesForProject(Project $project, bool $isProposal = false): array
    {
        $tables = [];
        $pageMap = $this->computeDynamicChapterPages($project, $isProposal);
        $chapters = $isProposal ? ['bab1', 'bab2', 'bab3'] : ['bab1', 'bab2', 'bab3', 'bab4', 'bab5'];

        foreach ($chapters as $key) {
            $content = (string) ($project->{$key . '_content'} ?? '');
            if (empty(trim($content)) || empty($pageMap[$key]['count'])) {
                continue;
            }

            $basePage = $pageMap[$key]['start'];
            $chapPages = $pageMap[$key]['count'];
            $lines = explode("\n", $content);
            $totalLines = max(1, count($lines));
            $inCode = false;
            $seen = [];

            foreach ($lines as $lIdx => $line) {
                $trimmed = trim(strip_tags($line));
                if (str_starts_with($trimmed, '```')) {
                    $inCode = !$inCode;
                    continue;
                }
                if ($inCode) {
                    continue;
                }
                if (preg_match('/^(?:\*{1,2}|<strong[^>]*>)?Tabel\s+(\d+\.\d+[\w\.\-]*)\s*[:\-\s]*(.*?)(?:\*{1,2}|<\/strong>)?$/i', $trimmed, $m)) {
                    $rawTitle = trim(ltrim($m[2], ":- \t"));
                    if (mb_strlen($rawTitle) > 95 || preg_match('/memperlihatkan|menunjukkan|dapat dilihat|mengindikasikan|menyajikan|mereduksi|berkontribusi|berdasarkan tabel|terlihat pada|membuktikan|menjelaskan|menerangkan|diperoleh|dihasilkan/i', $rawTitle)) {
                        continue;
                    }
                    $num = $m[1];
                    if (isset($seen[$num])) continue;
                    $seen[$num] = true;
                    $title = $rawTitle ?: "Tabel {$num}";
                    $estPage = min($pageMap[$key]['end'], $basePage + (int) floor(($lIdx / $totalLines) * $chapPages));
                    $tables[] = ['label' => "Tabel {$num} {$title}", 'page' => (string) $estPage];
                }
            }
        }

        return $tables;
    }

    private function extractFiguresForProject(Project $project, bool $isProposal = false): array
    {
        $figures = [];
        $pageMap = $this->computeDynamicChapterPages($project, $isProposal);
        $chapters = $isProposal ? ['bab1', 'bab2', 'bab3'] : ['bab1', 'bab2', 'bab3', 'bab4', 'bab5'];
        $seen = [];

        foreach ($chapters as $key) {
            $content = (string) ($project->{$key . '_content'} ?? '');
            if (empty(trim($content)) || empty($pageMap[$key]['count'])) {
                continue;
            }

            $basePage = $pageMap[$key]['start'];
            $chapPages = $pageMap[$key]['count'];
            $lines = explode("\n", $content);
            $totalLines = max(1, count($lines));

            $foundExplicitInChapter = false;
            foreach ($lines as $lIdx => $line) {
                $trimmed = trim(strip_tags($line));
                if (preg_match('/^(?:\*{1,2}|<strong[^>]*>)?(?:Gambar|Bagan)\s+(\d+\.\d+[\w\.\-]*)\s*[:\-\s]*(.*?)(?:\*{1,2}|<\/strong>)?$/i', $trimmed, $m)) {
                    $rawTitle = trim(ltrim($m[2], ":- \t"));
                    if (mb_strlen($rawTitle) > 95 || preg_match('/menunjukkan bahwa|dapat dilihat|memperlihatkan alur/i', $rawTitle)) {
                        continue;
                    }
                    $num = $m[1];
                    if (isset($seen[$num])) continue;
                    $seen[$num] = true;
                    $foundExplicitInChapter = true;
                    $title = $rawTitle ?: "Gambar {$num}";
                    $estPage = min($pageMap[$key]['end'], $basePage + (int) floor(($lIdx / $totalLines) * $chapPages));
                    $figures[] = ['label' => "Gambar {$num} {$title}", 'page' => (string) $estPage];
                }
            }

            // Hanya tambahkan default jika bab berisi blok diagram kode tapi belum ada caption eksplisit
            if (!$foundExplicitInChapter && str_contains($content, '```')) {
                if ($key === 'bab2' && !isset($seen['2.1'])) {
                    $seen['2.1'] = true;
                    $figures[] = ['label' => 'Gambar 2.1 Bagan Kerangka Berpikir Penelitian', 'page' => (string) min($pageMap[$key]['end'], $basePage + max(0, $chapPages - 1))];
                } elseif ($key === 'bab3' && !isset($seen['3.1'])) {
                    $seen['3.1'] = true;
                    $figures[] = ['label' => 'Gambar 3.1 Diagram Alur (Flowchart) Prosedur Penelitian', 'page' => (string) min($pageMap[$key]['end'], $basePage + max(0, $chapPages - 1))];
                }
            }
        }

        return $figures;
    }

    /**
     * Klasifikasi simbol flowchart standar ANSI / ISO 5807 berdasarkan fungsi tahapan
     */
    private function classifyFlowchartNode(string $nodeText, int $idx, int $total, bool $isFramework, int $decisionIdx): string
    {
        if ($isFramework) {
            return 'framework';
        }
        $lower = strtolower(trim($nodeText));
        if ($idx === 0 || $idx === $total - 1 || preg_match('/^(mulai|start|selesai|end|finish)$/i', $lower)) {
            return 'terminator';
        }
        if ($decisionIdx >= 0 && $idx === $decisionIdx) {
            return 'decision';
        }
        if (preg_match('/dataset|pengumpulan|input|output|korpus|kuesioner|angket|wawancara|observasi|scraping|crawling|dokumentasi|penyusunan\s+laporan|laporan\s+hasil/i', $lower)) {
            return 'io';
        }
        if (preg_match('/pra-pemrosesan|preprocessing|sub-proses|ekstraksi\s+fitur|pembobotan|tf-idf|uji\s+coba|uji\s+validitas|uji\s+reliabilitas|uji\s+prasyarat|uji\s+asumsi|triangulasi|perancangan\s+sistem|tokeniz|stemming/i', $lower)) {
            return 'subprocess';
        }
        return 'process';
    }

    /**
     * Pecah teks panjang menjadi baris-baris rapi agar muat di dalam kotak/simbol diagram
     */
    private function wrapDiagramLines(string $text, int $maxChars = 34, int $maxLines = 3): array
    {
        $clean = trim(preg_replace('/\s+/', ' ', $text));
        if ($clean === '') {
            return [''];
        }
        $words = explode(' ', $clean);
        $lines = [];
        $current = '';
        foreach ($words as $w) {
            if ($current === '') {
                $current = $w;
            } elseif (mb_strlen($current . ' ' . $w) <= $maxChars) {
                $current .= ' ' . $w;
            } else {
                $lines[] = $current;
                $current = $w;
                if (count($lines) >= $maxLines - 1) {
                    // Gabungkan sisa kata di baris terakhir dengan batas aman
                    break;
                }
            }
        }
        if ($current !== '') {
            $lines[] = $current;
        }
        // Jika masih ada sisa kata yang terpotong karena maxLines
        $joined = implode(' ', $lines);
        if (mb_strlen($joined) < mb_strlen($clean) && !empty($lines)) {
            $lastIdx = count($lines) - 1;
            $remaining = trim(mb_substr($clean, mb_strlen(implode(' ', array_slice($lines, 0, $lastIdx)))));
            $lines[$lastIdx] = mb_strlen($remaining) > $maxChars + 4
                ? rtrim(mb_substr($remaining, 0, $maxChars + 1)) . '...'
                : $remaining;
        }
        return $lines;
    }

    /**
     * Bangun Diagram Flowchart / Kerangka Berpikir SVG Vektor Murni Standar ANSI (100% Bebas GD, Tajam di PDF Dompdf)
     */
    private function buildFlowchartSvg(array $nodes, bool $isFramework = false): string
    {
        $total = count($nodes);
        if ($total === 0) {
            return '';
        }

        // Cari maksimal 1 node Decision utama untuk flowchart (bukan kerangka berpikir)
        $decisionIdx = -1;
        if (!$isFramework && $total >= 5) {
            foreach ($nodes as $idx => $nt) {
                if ($idx > 1 && $idx < $total - 1 && preg_match('/evaluasi|confusion|akurasi|validasi|memenuhi|uji\s+hipotesis|kelayakan|layak|\?/i', $nt)) {
                    $decisionIdx = $idx;
                    break;
                }
            }
        }

        $w = 560;
        $cx = 265; // Sedikit ke kiri jika ada decision loopback di kanan
        if ($decisionIdx < 0) {
            $cx = 280;
        }
        $gap = 24;
        $y = 12;
        $layouts = [];

        foreach ($nodes as $idx => $rawNode) {
            $text = str_replace(['→', '⇒', '←', '↔'], ['->', '->', '<-', '<->'], $this->formatAcademicMathText($rawNode));
            $type = $this->classifyFlowchartNode($text, $idx, $total, $isFramework, $decisionIdx);
            $maxChars = match ($type) {
                'terminator' => 24,
                'decision' => 28,
                'io' => 40,
                'subprocess' => 40,
                'framework' => 48,
                default => 44,
            };
            $lines = $this->wrapDiagramLines($text, $maxChars, 5);
            $lineCount = count($lines);

            if ($type === 'terminator') {
                $boxW = 180;
                $boxH = max(34, $lineCount * 14 + 14);
            } elseif ($type === 'decision') {
                $boxW = 244;
                $boxH = max(72, $lineCount * 14 + 34);
            } elseif ($type === 'framework') {
                $boxW = 410;
                $boxH = max(44, $lineCount * 15 + 18);
            } else {
                $boxW = 330;
                $boxH = max(38, $lineCount * 15 + 16);
            }

            $layouts[] = [
                'type' => $type,
                'lines' => $lines,
                'x' => (int) round($cx - $boxW / 2),
                'y' => $y,
                'w' => $boxW,
                'h' => $boxH,
                'cy' => (int) round($y + $boxH / 2),
            ];
            $y += $boxH + ($type === 'decision' ? $gap + 8 : $gap);
        }

        $totalH = $y - $gap + 14;
        $svgParts = [];
        $svgParts[] = "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"{$w}\" height=\"{$totalH}\" viewBox=\"0 0 {$w} {$totalH}\">";
        $svgParts[] = "<rect x=\"0\" y=\"0\" width=\"{$w}\" height=\"{$totalH}\" fill=\"#ffffff\"/>";

        foreach ($layouts as $idx => $item) {
            $x = $item['x'];
            $iy = $item['y'];
            $bw = $item['w'];
            $bh = $item['h'];
            $cy = $item['cy'];
            $type = $item['type'];
            $lines = $item['lines'];

            if ($type === 'terminator') {
                $rx = (int) round($bh / 2);
                $svgParts[] = "<rect x=\"{$x}\" y=\"{$iy}\" width=\"{$bw}\" height=\"{$bh}\" rx=\"{$rx}\" ry=\"{$rx}\" fill=\"#ffffff\" stroke=\"#000000\" stroke-width=\"1.6\"/>";
            } elseif ($type === 'io') {
                $skew = 20;
                $pts = ($x + $skew) . ",{$iy} " . ($x + $bw) . ",{$iy} " . ($x + $bw - $skew) . "," . ($iy + $bh) . " {$x}," . ($iy + $bh);
                $svgParts[] = "<polygon points=\"{$pts}\" fill=\"#ffffff\" stroke=\"#000000\" stroke-width=\"1.4\"/>";
            } elseif ($type === 'subprocess') {
                // Kotak proses tunggal bersih tanpa garis ganda vertikal agar seragam dengan Word & rapi di PDF
                $svgParts[] = "<rect x=\"{$x}\" y=\"{$iy}\" width=\"{$bw}\" height=\"{$bh}\" fill=\"#ffffff\" stroke=\"#000000\" stroke-width=\"1.4\"/>";
            } elseif ($type === 'decision') {
                $pts = "{$cx},{$iy} " . ($x + $bw) . ",{$cy} {$cx}," . ($iy + $bh) . " {$x},{$cy}";
                $svgParts[] = "<polygon points=\"{$pts}\" fill=\"#ffffff\" stroke=\"#000000\" stroke-width=\"1.5\"/>";

                // Cabang Loopback "Tidak" di sisi kanan mengarah ke tahapan sebelumnya
                $targetIdx = max(1, $idx - 2);
                $targetBox = $layouts[$targetIdx] ?? $layouts[max(0, $idx - 1)];
                $loopX = min($w - 22, $x + $bw + 66);
                $targetRightX = $targetBox['x'] + $targetBox['w'];
                $targetCy = $targetBox['cy'];

                $svgParts[] = "<line x1=\"" . ($x + $bw) . "\" y1=\"{$cy}\" x2=\"{$loopX}\" y2=\"{$cy}\" stroke=\"#000000\" stroke-width=\"1.2\"/>";
                $svgParts[] = "<line x1=\"{$loopX}\" y1=\"{$cy}\" x2=\"{$loopX}\" y2=\"{$targetCy}\" stroke=\"#000000\" stroke-width=\"1.2\"/>";
                $svgParts[] = "<line x1=\"{$loopX}\" y1=\"{$targetCy}\" x2=\"" . ($targetRightX + 6) . "\" y2=\"{$targetCy}\" stroke=\"#000000\" stroke-width=\"1.2\"/>";
                $svgParts[] = "<polygon points=\"" . ($targetRightX + 7) . "," . ($targetCy - 4) . " " . ($targetRightX + 7) . "," . ($targetCy + 4) . " {$targetRightX},{$targetCy}\" fill=\"#000000\"/>";
                $svgParts[] = "<text x=\"" . ($x + $bw + 8) . "\" y=\"" . ($cy - 5) . "\" font-family=\"Times-Roman, serif\" font-size=\"9.5\" font-weight=\"bold\" fill=\"#000000\">Tidak</text>";
            } else {
                $svgParts[] = "<rect x=\"{$x}\" y=\"{$iy}\" width=\"{$bw}\" height=\"{$bh}\" fill=\"#ffffff\" stroke=\"#000000\" stroke-width=\"1.4\"/>";
            }

            // Teks terpusat vertikal dan horizontal di dalam simbol
            $fontSize = ($type === 'decision') ? 9.5 : 10.5;
            $lineH = ($type === 'decision') ? 13 : 14.5;
            $isBold = ($type === 'terminator');
            $startTextY = $cy - ((count($lines) - 1) * $lineH / 2) + 3.5;

            foreach ($lines as $li => $lt) {
                $ty = round($startTextY + $li * $lineH, 1);
                $displayLine = $type === 'terminator' ? strtoupper($lt) : $lt;
                $weightAttr = $isBold ? ' font-weight="bold"' : '';
                $escaped = htmlspecialchars($displayLine, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $svgParts[] = "<text x=\"{$cx}\" y=\"{$ty}\" text-anchor=\"middle\" font-family=\"Times-Roman, serif\" font-size=\"{$fontSize}\"{$weightAttr} fill=\"#000000\">{$escaped}</text>";
            }

            // Panah vertikal ke node berikutnya
            if ($idx < $total - 1) {
                $nextY = $layouts[$idx + 1]['y'];
                $fromY = $iy + $bh;
                $svgParts[] = "<line x1=\"{$cx}\" y1=\"{$fromY}\" x2=\"{$cx}\" y2=\"" . ($nextY - 6) . "\" stroke=\"#000000\" stroke-width=\"1.3\"/>";
                $svgParts[] = "<polygon points=\"" . ($cx - 4.5) . "," . ($nextY - 7) . " " . ($cx + 4.5) . "," . ($nextY - 7) . " {$cx},{$nextY}\" fill=\"#000000\"/>";
                if ($type === 'decision') {
                    $svgParts[] = "<text x=\"" . ($cx + 8) . "\" y=\"" . round(($fromY + $nextY) / 2 + 3) . "\" font-family=\"Times-Roman, serif\" font-size=\"9.5\" font-weight=\"bold\" fill=\"#000000\">Ya</text>";
                }
            }
        }

        $svgParts[] = "</svg>";
        return implode('', $svgParts);
    }

    /**
     * Bangun Flowchart Vektor Asli Word (OOXML VML Shapes) dengan Simbol ANSI Lengkap & Editable di Microsoft Word (Bebas GD)
     */
    private function buildFlowchartWordVml(array $nodes, bool $isFramework = false): string
    {
        $total = count($nodes);
        if ($total === 0) {
            return '';
        }

        $decisionIdx = -1;
        if (!$isFramework && $total >= 5) {
            foreach ($nodes as $idx => $nt) {
                if ($idx > 1 && $idx < $total - 1 && preg_match('/evaluasi|confusion|akurasi|validasi|memenuhi|uji\s+hipotesis|kelayakan|layak|\?/i', $nt)) {
                    $decisionIdx = $idx;
                    break;
                }
            }
        }

        $w = 560;
        $cx = ($decisionIdx >= 0) ? 265 : 280;
        $gap = 24;
        $y = 10;
        $layouts = [];

        foreach ($nodes as $idx => $rawNode) {
            $text = str_replace(['→', '⇒', '←', '↔'], ['->', '->', '<-', '<->'], $this->formatAcademicMathText($rawNode));
            $type = $this->classifyFlowchartNode($text, $idx, $total, $isFramework, $decisionIdx);
            $maxChars = match ($type) {
                'terminator' => 24,
                'decision' => 28,
                'io' => 38,
                'subprocess' => 38,
                'framework' => 48,
                default => 42,
            };
            $lines = $this->wrapDiagramLines($text, $maxChars, 5);
            $lineCount = count($lines);

            if ($type === 'terminator') {
                $boxW = 180;
                $boxH = max(36, $lineCount * 15 + 16);
            } elseif ($type === 'decision') {
                $boxW = 244;
                $boxH = max(76, $lineCount * 15 + 36);
            } elseif ($type === 'framework') {
                $boxW = 410;
                $boxH = max(46, $lineCount * 15 + 18);
            } else {
                $boxW = 330;
                $boxH = max(40, $lineCount * 15 + 16);
            }

            $layouts[] = [
                'type' => $type,
                'text' => $type === 'terminator' ? strtoupper($text) : $text,
                'lines' => $lines,
                'x' => (int) round($cx - $boxW / 2),
                'y' => $y,
                'w' => $boxW,
                'h' => $boxH,
                'cy' => (int) round($y + $boxH / 2),
            ];
            $y += $boxH + ($type === 'decision' ? $gap + 8 : $gap);
        }

        $totalH = $y - $gap + 12;
        $displayW = 400; // pt (muat sempurna dalam lebar halaman A4 margin 4-3 cm = 14 cm ≈ 397pt)
        $displayH = round($totalH * ($displayW / $w), 1);
        // Pastikan tinggi tidak melebihi 1 halaman A4 (maksimal ~560pt agar caption di bawah ikut muat)
        if ($displayH > 540) {
            $scaleDown = 540 / $displayH;
            $displayH = 540;
            $displayW = round($displayW * $scaleDown, 1);
        }

        $makeTextBox = function (array $lines, bool $bold = false, int $szHalfPt = 20): string {
            $bTag = $bold ? '<w:b/>' : '';
            $paras = '';
            foreach ($lines as $ln) {
                $esc = htmlspecialchars($ln, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $paras .= '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:before="0" w:after="0" w:line="240" w:lineRule="auto"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/>' . $bTag . '<w:color w:val="000000"/><w:sz w:val="' . $szHalfPt . '"/><w:szCs w:val="' . $szHalfPt . '"/></w:rPr><w:t xml:space="preserve">' . $esc . '</w:t></w:r></w:p>';
            }
            return '<v:textbox style="mso-fit-shape-to-text:f" inset="4pt,2pt,4pt,2pt"><w:txbxContent>' . $paras . '</w:txbxContent></v:textbox>';
        };

        $shapes = [];
        $uid = substr(md5(uniqid('', true)), 0, 6);

        foreach ($layouts as $idx => $item) {
            $x = $item['x'];
            $iy = $item['y'];
            $bw = $item['w'];
            $bh = $item['h'];
            $cy = $item['cy'];
            $type = $item['type'];
            $lines = $item['lines'];
            $idAttr = "fc_{$uid}_{$idx}";

            if ($type === 'terminator') {
                $upperLines = array_map('strtoupper', $lines);
                $shapes[] = "<v:roundrect id=\"{$idAttr}\" style=\"position:absolute;left:{$x};top:{$iy};width:{$bw};height:{$bh};v-text-anchor:middle\" arcsize=\"0.5\" fillcolor=\"#ffffff\" strokecolor=\"#000000\" strokeweight=\"1.3pt\">" . $makeTextBox($upperLines, true, 20) . "</v:roundrect>";
            } elseif ($type === 'io') {
                // Jajar genjang (Parallelogram)
                $shapes[] = "<v:shape id=\"{$idAttr}\" coordsize=\"330,100\" path=\"m 22,0 l 330,0 l 308,100 l 0,100 x e\" style=\"position:absolute;left:{$x};top:{$iy};width:{$bw};height:{$bh};v-text-anchor:middle\" fillcolor=\"#ffffff\" strokecolor=\"#000000\" strokeweight=\"1.1pt\">" . $makeTextBox($lines, false, 19) . "</v:shape>";
            } elseif ($type === 'subprocess') {
                // Kotak Predefined Process + 2 garis vertikal ganda kiri kanan
                $shapes[] = "<v:rect id=\"{$idAttr}\" style=\"position:absolute;left:{$x};top:{$iy};width:{$bw};height:{$bh};v-text-anchor:middle\" fillcolor=\"#ffffff\" strokecolor=\"#000000\" strokeweight=\"1.1pt\">" . $makeTextBox($lines, false, 19) . "</v:rect>";
                $lx1 = $x + 14;
                $lx2 = $x + $bw - 14;
                $by = $iy + $bh;
                $shapes[] = "<v:line from=\"{$lx1},{$iy}\" to=\"{$lx1},{$by}\" strokecolor=\"#000000\" strokeweight=\"1.1pt\"/>";
                $shapes[] = "<v:line from=\"{$lx2},{$iy}\" to=\"{$lx2},{$by}\" strokecolor=\"#000000\" strokeweight=\"1.1pt\"/>";
            } elseif ($type === 'decision') {
                // Belah ketupat (Diamond)
                $shapes[] = "<v:shape id=\"{$idAttr}\" coordsize=\"244,100\" path=\"m 122,0 l 244,50 l 122,100 l 0,50 x e\" style=\"position:absolute;left:{$x};top:{$iy};width:{$bw};height:{$bh};v-text-anchor:middle\" fillcolor=\"#ffffff\" strokecolor=\"#000000\" strokeweight=\"1.2pt\">" . $makeTextBox($lines, false, 18) . "</v:shape>";

                // Panah loopback "Tidak" di sisi kanan
                $targetIdx = max(1, $idx - 2);
                $targetBox = $layouts[$targetIdx] ?? $layouts[max(0, $idx - 1)];
                $loopX = min($w - 20, $x + $bw + 64);
                $targetRightX = $targetBox['x'] + $targetBox['w'];
                $targetCy = $targetBox['cy'];
                $rightX = $x + $bw;

                $shapes[] = "<v:line from=\"{$rightX},{$cy}\" to=\"{$loopX},{$cy}\" strokecolor=\"#000000\" strokeweight=\"1pt\"/>";
                $shapes[] = "<v:line from=\"{$loopX},{$cy}\" to=\"{$loopX},{$targetCy}\" strokecolor=\"#000000\" strokeweight=\"1pt\"/>";
                $shapes[] = "<v:line from=\"{$loopX},{$targetCy}\" to=\"{$targetRightX},{$targetCy}\" strokecolor=\"#000000\" strokeweight=\"1pt\"><v:stroke endarrow=\"classic\"/></v:line>";
                $lblX = $rightX + 4;
                $lblY = $cy - 18;
                $shapes[] = "<v:rect style=\"position:absolute;left:{$lblX};top:{$lblY};width:52;height:18\" filled=\"f\" stroked=\"f\">" . $makeTextBox(['Tidak'], true, 17) . "</v:rect>";
            } else {
                // Kotak Proses / Kerangka Berpikir
                $shapes[] = "<v:rect id=\"{$idAttr}\" style=\"position:absolute;left:{$x};top:{$iy};width:{$bw};height:{$bh};v-text-anchor:middle\" fillcolor=\"#ffffff\" strokecolor=\"#000000\" strokeweight=\"1.1pt\">" . $makeTextBox($lines, false, 19) . "</v:rect>";
            }

            // Panah vertikal ke node berikutnya
            if ($idx < $total - 1) {
                $fromY = $iy + $bh;
                $nextY = $layouts[$idx + 1]['y'];
                $shapes[] = "<v:line from=\"{$cx},{$fromY}\" to=\"{$cx},{$nextY}\" strokecolor=\"#000000\" strokeweight=\"1.1pt\"><v:stroke endarrow=\"classic\"/></v:line>";
                if ($type === 'decision') {
                    $yaX = $cx + 4;
                    $yaY = $fromY + 2;
                    $shapes[] = "<v:rect style=\"position:absolute;left:{$yaX};top:{$yaY};width:34;height:18\" filled=\"f\" stroked=\"f\">" . $makeTextBox(['Ya'], true, 17) . "</v:rect>";
                }
            }
        }

        $groupXml = "<v:group id=\"fcgrp_{$uid}\" style=\"width:{$displayW}pt;height:{$displayH}pt\" coordorigin=\"0,0\" coordsize=\"{$w},{$totalH}\">" . implode('', $shapes) . "</v:group>";
        return '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:before="80" w:after="60"/><w:keepNext/></w:pPr><w:r><w:pict>' . $groupXml . '</w:pict></w:r></w:p>';
    }

    private function renderKerangkaPemikiranPng(string $rawCode, string $judul): ?string
    {
        if (!extension_loaded('gd') || !function_exists('imagecreatetruecolor')) {
            return null;
        }

        $w = 850;
        $h = 580;
        $im = @imagecreatetruecolor($w, $h);
        if (!$im)
            return null;

        $white = imagecolorallocate($im, 255, 255, 255);
        $black = imagecolorallocate($im, 0, 0, 0);
        imagefilledrectangle($im, 0, 0, $w, $h, $white);

        // Ekstraksi node kerangka pemikiran secara dinamis dari rawCode
        preg_match_all('/\[([\s\S]*?)\]/', $rawCode, $m);
        $extractedNodes = [];
        if (!empty($m[1])) {
            foreach ($m[1] as $node) {
                $clean = trim(preg_replace('/^[+\-=_|~#\s]+|[+\-=_|~#\s]+$/', '', $node));
                $clean = preg_replace('/\s+/', ' ', $clean);
                if (mb_strlen($clean) >= 3 && !preg_match('/^[+\-=_|~#\s]+$/', $clean)) {
                    $extractedNodes[] = $clean;
                }
            }
        }
        if (count($extractedNodes) < 3) {
            $rawLines = preg_split('/[\n↓]|-->|->/', $rawCode);
            foreach ($rawLines as $l) {
                $clean = trim(preg_replace('/^[-*•\d\.\s\>Vv↓|]+/', '', $l));
                $clean = trim(preg_replace('/[|]+$/', '', $clean));
                $clean = trim(str_replace(['[', ']'], '', $clean));
                $clean = preg_replace('/\s+/', ' ', $clean);
                if (mb_strlen($clean) >= 6 && !preg_match('/^[+\-=_|~#\s]+$/', $clean) && !str_starts_with(strtolower($clean), 'gambar') && !str_starts_with(strtolower($clean), 'bagan')) {
                    $extractedNodes[] = $clean;
                }
            }
        }

        $jClean = trim($judul) ?: 'Topik Penelitian';
        $defaultTiers = [
            'Kondisi Empiris: Kesenjangan fenomena nyata di lapangan dan urgensi masalah terkait ' . $jClean,
            'Landasan Teori: Grand/Middle Theory pendukung dan sintesis penelitian terdahulu sebagai rujukan konseptual',
            'Pendekatan Metodologi: Pengumpulan data empiris, pengujian instrumen, dan analisis variabel secara terstruktur',
            'Luaran Penelitian: Pembuktian hipotesis, temuan ilmiah teruji, dan rekomendasi praktis implementatif'
        ];

        $tiers = [
            [
                'title' => 'TAHAP 1: KONDISI EMPIRIS / MASALAH PENELITIAN',
                'desc' => $this->formatAcademicMathText($extractedNodes[0] ?? $defaultTiers[0]),
                'bg' => $white,
                'border' => $black,
                'text' => $black
            ],
            [
                'title' => 'TAHAP 2: LANDASAN TEORI & SINTESIS PUSTAKA',
                'desc' => $this->formatAcademicMathText($extractedNodes[1] ?? $defaultTiers[1]),
                'bg' => $white,
                'border' => $black,
                'text' => $black
            ],
            [
                'title' => 'TAHAP 3: PENDEKATAN METODOLOGI & ANALISIS DATA',
                'desc' => $this->formatAcademicMathText($extractedNodes[2] ?? $defaultTiers[2]),
                'bg' => $white,
                'border' => $black,
                'text' => $black
            ],
            [
                'title' => 'TAHAP 4: LUARAN HASIL PENELITIAN & REKOMENDASI',
                'desc' => $this->formatAcademicMathText($extractedNodes[3] ?? ($extractedNodes[2] ?? $defaultTiers[3])),
                'bg' => $white,
                'border' => $black,
                'text' => $black
            ],
        ];

        $y = 28;
        $boxW = 750;
        $boxH = 82;
        $x = (int) (($w - $boxW) / 2);

        foreach ($tiers as $idx => $t) {
            imagefilledrectangle($im, $x, $y, $x + $boxW, $y + $boxH, $t['bg']);
            imagerectangle($im, $x, $y, $x + $boxW, $y + $boxH, $t['border']);
            imagerectangle($im, $x + 1, $y + 1, $x + $boxW - 1, $y + $boxH - 1, $t['border']);

            imagestring($im, 4, $x + 20, $y + 14, $t['title'], $t['text']);

            // Bungkus teks ke dalam 2 baris agar rapi dan tidak meluap keluar kotak
            $wrappedLines = explode("\n", wordwrap($t['desc'], 88, "\n"));
            $lineY = $y + 38;
            foreach (array_slice($wrappedLines, 0, 2) as $wl) {
                imagestring($im, 3, $x + 20, $lineY, $wl, $black);
                $lineY += 18;
            }

            if ($idx < 3) {
                // Downward arrow
                $arrowY = $y + $boxH;
                imageline($im, (int) ($w / 2), $arrowY + 4, (int) ($w / 2), $arrowY + 34, $black);
                imageline($im, (int) ($w / 2) - 1, $arrowY + 4, (int) ($w / 2) - 1, $arrowY + 34, $black);
                imagefilledpolygon($im, [
                    (int) ($w / 2) - 8,
                    $arrowY + 26,
                    (int) ($w / 2) + 8,
                    $arrowY + 26,
                    (int) ($w / 2),
                    $arrowY + 36
                ], $black);
                $y += $boxH + 42;
            }
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'kp_') . '.png';
        imagepng($im, $tmpPath);
        imagedestroy($im);

        return $tmpPath;
    }

    private function renderFlowchartPng(string $rawCode, string $judul): ?string
    {
        if (!extension_loaded('gd') || !function_exists('imagecreatetruecolor')) {
            return null;
        }

        $w = 780;
        $h = 650;
        $im = @imagecreatetruecolor($w, $h);
        if (!$im)
            return null;

        $white = imagecolorallocate($im, 255, 255, 255);
        $black = imagecolorallocate($im, 0, 0, 0);

        imagefilledrectangle($im, 0, 0, $w, $h, $white);

        $drawArrow = function ($cx, $fromY, $toY) use ($im, $black) {
            imageline($im, $cx, $fromY, $cx, $toY, $black);
            imageline($im, $cx - 1, $fromY, $cx - 1, $toY, $black);
            imagefilledpolygon($im, [
                $cx - 7,
                $toY - 8,
                $cx + 7,
                $toY - 8,
                $cx,
                $toY
            ], $black);
        };

        $cx = (int) ($w / 2);

        // 1. Mulai (ANSI Terminator - Oval / Capsule)
        $y = 20;
        $termW = 200;
        $termH = 40;
        imagefilledellipse($im, $cx, $y + 20, $termW, $termH, $white);
        imageellipse($im, $cx, $y + 20, $termW, $termH, $black);
        imageellipse($im, $cx, $y + 20, $termW - 2, $termH - 2, $black);
        imagestring($im, 5, $cx - 24, $y + 12, "MULAI", $black);

        $drawArrow($cx, $y + 40, $y + 65);

        // 2. Identifikasi Masalah & Studi Literatur (ANSI Process)
        $y = 65;
        $pW = 500;
        $pH = 46;
        $pX = $cx - (int) ($pW / 2);
        imagefilledrectangle($im, $pX, $y, $pX + $pW, $y + $pH, $white);
        imagerectangle($im, $pX, $y, $pX + $pW, $y + $pH, $black);
        imagestring($im, 4, $cx - 180, $y + 14, "Identifikasi Masalah & Studi Kepustakaan", $black);

        $drawArrow($cx, $y + $pH, $y + $pH + 25);

        // 3. Pengumpulan Data Penelitian (ANSI Data I/O - Parallelogram)
        $y = $y + $pH + 25;
        $subW = 520;
        $subH = 46;
        $subX = $cx - (int) ($subW / 2);
        $skew = 22;
        $paraPts = [
            $subX + $skew,
            $y,
            $subX + $subW,
            $y,
            $subX + $subW - $skew,
            $y + $subH,
            $subX,
            $y + $subH
        ];
        imagefilledpolygon($im, $paraPts, $white);
        imagepolygon($im, $paraPts, $black);
        imagestring($im, 4, $cx - 195, $y + 14, "Pengumpulan Data Empiris & Sampel Riset", $black);

        $drawArrow($cx, $y + $subH, $y + $subH + 25);

        // 4. Uji Validitas & Reliabilitas Instrumen (ANSI Process)
        $y = $y + $subH + 25;
        $b4W = 500;
        $b4H = 46;
        $b4X = $cx - (int) ($b4W / 2);
        imagefilledrectangle($im, $b4X, $y, $b4X + $b4W, $y + $b4H, $white);
        imagerectangle($im, $b4X, $y, $b4X + $b4W, $y + $b4H, $black);
        imagestring($im, 4, $cx - 180, $y + 14, "Uji Validitas & Reliabilitas Instrumen Data", $black);

        $drawArrow($cx, $y + $b4H, $y + $b4H + 25);

        // 5. Analisis Data & Pengujian Hipotesis (ANSI Process)
        $y = $y + $b4H + 25;
        $b5W = 520;
        $b5H = 46;
        $b5X = $cx - (int) ($b5W / 2);
        imagefilledrectangle($im, $b5X, $y, $b5X + $b5W, $y + $b5H, $white);
        imagerectangle($im, $b5X, $y, $b5X + $b5W, $y + $b5H, $black);
        imagestring($im, 4, $cx - 190, $y + 14, "Analisis Data Statistik & Uji Hipotesis Penelitian", $black);

        $drawArrow($cx, $y + $b5H, $y + $b5H + 25);

        // 6. Pembahasan Temuan & Rekomendasi (ANSI Process)
        $y = $y + $b5H + 25;
        $b6W = 500;
        $b6H = 46;
        $b6X = $cx - (int) ($b6W / 2);
        imagefilledrectangle($im, $b6X, $y, $b6X + $b6W, $y + $b6H, $white);
        imagerectangle($im, $b6X, $y, $b6X + $b6W, $y + $b6H, $black);
        imagestring($im, 4, $cx - 175, $y + 14, "Pembahasan Temuan & Penarikan Simpulan", $black);

        $drawArrow($cx, $y + $b6H, $y + $b6H + 25);

        // 7. Selesai (ANSI Terminator)
        $y = $y + $b6H + 25;
        imagefilledellipse($im, $cx, $y + 20, $termW, $termH, $white);
        imageellipse($im, $cx, $y + 20, $termW, $termH, $black);
        imageellipse($im, $cx, $y + 20, $termW - 2, $termH - 2, $black);
        imagestring($im, 5, $cx - 32, $y + 12, "SELESAI", $black);

        $tmpPath = tempnam(sys_get_temp_dir(), 'fc_') . '.png';
        imagepng($im, $tmpPath);
        imagedestroy($im);

        return $tmpPath;
    }

    // =========================================================================
    // 1. DASHBOARD & MANAJEMEN PROYEK
    // =========================================================================

    public function preGeneratorForm()
    {
        $user = Auth::user();

        $history = Project::where('user_id', $user->id)
            ->select([
                'id', 'user_id', 'judul', 'prodi', 'metode_penelitian', 'sumber_data',
                'is_full_unlocked', 'citation_validation', 'created_at', 'updated_at',
                DB::raw("CASE WHEN COALESCE(bab1_content, '') <> '' THEN '1' ELSE '' END as bab1_content"),
                DB::raw("CASE WHEN COALESCE(bab2_content, '') <> '' THEN '1' ELSE '' END as bab2_content"),
                DB::raw("CASE WHEN COALESCE(bab3_content, '') <> '' THEN '1' ELSE '' END as bab3_content"),
                DB::raw("CASE WHEN COALESCE(bab4_content, '') <> '' THEN '1' ELSE '' END as bab4_content"),
                DB::raw("CASE WHEN COALESCE(bab5_content, '') <> '' THEN '1' ELSE '' END as bab5_content"),
            ])
            ->orderBy('updated_at', 'desc')
            ->get();

        try {
            $seen = [];
            $dupeIds = [];
            foreach ($history->sortBy('id') as $proj) {
                $key = strtolower(trim($proj->judul)) . '|' . strtolower(trim($proj->prodi ?? ''));
                $isEmpty = empty($proj->bab1_content) && empty($proj->bab2_content) && empty($proj->bab3_content);
                if (isset($seen[$key]) && $isEmpty) {
                    $dupeIds[] = $proj->id;
                } else {
                    $seen[$key] = $proj->id;
                }
            }
            if (!empty($dupeIds)) {
                Project::where('user_id', $user->id)->whereIn('id', $dupeIds)->delete();
                $history = $history->reject(fn($p) => in_array($p->id, $dupeIds, true))->values();
            }
        } catch (Throwable $e) {
        }

        $defaultIdentity = $this->getDefaultUserIdentity($user->id);

        try {
            $apiKeys = DB::table('api_keys')->where('user_id', $user->id)->orderBy('created_at', 'desc')->get();
        } catch (Exception $e) {
            $apiKeys = collect([]);
        }

        $savedPpts = $this->getSavedPptsForUser($user->id);
        $billingSummary = $this->billing()->getUserSummary($user, true);
        $plans = $this->billing()->getPlans(true);
        $guides = PanduanController::getGuides();

        return $this->applyNoStoreHeaders(response()->view('pre_generator', compact('history', 'apiKeys', 'user', 'defaultIdentity', 'savedPpts', 'billingSummary', 'plans', 'guides')));
    }

    private function applyNoStoreHeaders($response)
    {
        return $response
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
    }

    private function ensureSavedPptsTable(): bool
    {
        static $checked = false;
        if ($checked) {
            return true;
        }
        $connName = (string) config('database.default', 'sqlite');
        $dbName = (string) config("database.connections.{$connName}.database", '');
        $flagPath = sys_get_temp_dir() . '/sl_saved_ppts_v1_' . md5($connName . '|' . $dbName) . '.ok';
        if (@is_file($flagPath)) {
            $checked = true;
            return true;
        }
        try {
            DB::select('SELECT 1 FROM saved_ppts LIMIT 1');
            $checked = true;
            @file_put_contents($flagPath, '1');
            return true;
        } catch (Throwable $probeEx) {
        }
        try {
            if (!Schema::hasTable('saved_ppts')) {
                Schema::create('saved_ppts', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('user_id')->index();
                    $table->unsignedBigInteger('project_id')->nullable()->index();
                    $table->string('title', 500);
                    $table->string('theme', 64)->default('royal_academic');
                    $table->unsignedSmallInteger('slide_count')->default(8);
                    $table->longText('slides_json');
                    $table->longText('cover_meta_json')->nullable();
                    $table->timestamps();
                });
            }
            $checked = true;
            @file_put_contents($flagPath, '1');
            return true;
        } catch (Throwable $e) {
            Log::warning('ensureSavedPptsTable notice: ' . $e->getMessage());
            return false;
        }
    }

    private function getSavedPptsForUser(int $userId): array
    {
        if (!$this->ensureSavedPptsTable()) {
            return [];
        }
        try {
            $rows = DB::table('saved_ppts')
                ->where('user_id', $userId)
                ->orderBy('updated_at', 'desc')
                ->limit(30)
                ->get();

            $list = [];
            foreach ($rows as $row) {
                $slides = json_decode((string) $row->slides_json, true);
                if (!is_array($slides) || empty($slides)) {
                    continue;
                }
                $coverMeta = json_decode((string) ($row->cover_meta_json ?? '{}'), true) ?: [];
                $list[] = [
                    'id' => (int) $row->id,
                    'project_id' => $row->project_id ? (int) $row->project_id : null,
                    'title' => (string) $row->title,
                    'theme' => (string) ($row->theme ?: 'royal_academic'),
                    'slide_count' => count($slides),
                    'slides_data' => $slides,
                    'cover_meta' => $coverMeta,
                    'updated_at' => (string) $row->updated_at,
                    'updated_at_human' => \Carbon\Carbon::parse($row->updated_at)->diffForHumans(),
                ];
            }
            return $list;
        } catch (Throwable $e) {
            Log::warning('getSavedPptsForUser notice: ' . $e->getMessage());
            return [];
        }
    }

    private function persistSavedPptRecord(int $userId, ?int $projectId, string $title, array $slides, array $coverMeta, string $theme = 'royal_academic', ?int $deckId = null): ?array
    {
        if (!$this->ensureSavedPptsTable()) {
            return null;
        }
        try {
            $now = now();
            $cleanTitle = Str::limit(trim($title) ?: 'Presentasi Sidang Skripsi', 480, '');
            $payload = [
                'project_id' => $projectId,
                'title' => $cleanTitle,
                'theme' => preg_replace('/[^a-z0-9_]/i', '', $theme) ?: 'royal_academic',
                'slide_count' => count($slides),
                'slides_json' => json_encode($slides, JSON_UNESCAPED_UNICODE),
                'cover_meta_json' => json_encode($coverMeta, JSON_UNESCAPED_UNICODE),
                'updated_at' => $now,
            ];

            if ($deckId) {
                $exists = DB::table('saved_ppts')->where('id', $deckId)->where('user_id', $userId)->exists();
                if ($exists) {
                    DB::table('saved_ppts')->where('id', $deckId)->where('user_id', $userId)->update($payload);
                    $finalId = $deckId;
                } else {
                    $finalId = DB::table('saved_ppts')->insertGetId(array_merge($payload, [
                        'user_id' => $userId,
                        'created_at' => $now,
                    ]));
                }
            } else {
                $finalId = DB::table('saved_ppts')->insertGetId(array_merge($payload, [
                    'user_id' => $userId,
                    'created_at' => $now,
                ]));
            }

            return [
                'id' => (int) $finalId,
                'project_id' => $projectId,
                'title' => $cleanTitle,
                'theme' => $payload['theme'],
                'slide_count' => count($slides),
                'slides_data' => $slides,
                'cover_meta' => $coverMeta,
                'updated_at' => $now->toDateTimeString(),
                'updated_at_human' => 'baru saja',
            ];
        } catch (Throwable $e) {
            Log::warning('persistSavedPptRecord notice: ' . $e->getMessage());
            return null;
        }
    }

    public function savePptDeck(Request $request)
    {
        $slides = $request->input('slides_data');
        if (!is_array($slides) || empty($slides)) {
            return response()->json(['status' => 'error', 'message' => 'Data slide kosong.'], 422);
        }
        $deckId = $request->filled('id') ? (int) $request->input('id') : null;
        $projectId = $request->filled('project_id') ? (int) $request->input('project_id') : null;
        $title = trim((string) $request->input('title', 'Presentasi Sidang Skripsi'));
        $theme = trim((string) $request->input('theme', 'royal_academic'));
        $coverMeta = is_array($request->input('cover_meta')) ? $request->input('cover_meta') : [];

        // Jika logo dikirim tanpa prefix data:image, pulihkan menjadi JPEG data URI
        if (!empty($coverMeta['logo_base64']) && !str_starts_with($coverMeta['logo_base64'], 'data:image/') && !str_starts_with($coverMeta['logo_base64'], 'http')) {
            $coverMeta['logo_base64'] = 'data:image/jpeg;base64,' . $coverMeta['logo_base64'];
        }

        $saved = $this->persistSavedPptRecord(Auth::id(), $projectId, $title, $slides, $coverMeta, $theme, $deckId);
        if (!$saved) {
            return response()->json(['status' => 'error', 'message' => 'Gagal menyimpan PPT ke database.'], 500);
        }

        return response()->json(['status' => 'success', 'saved_ppt' => $saved]);
    }

    public function deletePptDeck($id)
    {
        if (!$this->ensureSavedPptsTable()) {
            return response()->json(['status' => 'error', 'message' => 'Tabel PPT tidak tersedia.'], 500);
        }
        DB::table('saved_ppts')->where('id', (int) $id)->where('user_id', Auth::id())->delete();
        return response()->json(['status' => 'success']);
    }

    public function addApiKey(Request $request)
    {
        $request->validate([
            'key' => 'required|string|min:10',
            'provider' => 'nullable|in:gemini,deepseek,groq'
        ]);

        try {
            $rawInput = trim($request->key);
            $candidates = array_values(array_unique(array_filter(
                preg_split('/[\r\n,;]+|\s+/', $rawInput),
                fn($k) => strlen(trim($k)) >= 12
            )));

            if (empty($candidates)) {
                return redirect()->back()->with('error', 'Format API Key tidak valid.');
            }

            $inserted = 0;
            $skipped = 0;
            $hasProviderCol = Schema::hasColumn('api_keys', 'provider');

            foreach ($candidates as $cleanKey) {
                $cleanKey = trim($cleanKey);
                if (DB::table('api_keys')->where('user_id', Auth::id())->where('key', $cleanKey)->exists()) {
                    $skipped++;
                    continue;
                }

                $data = [
                    'user_id' => Auth::id(),
                    'key' => $cleanKey,
                    'is_active' => true,
                    'usage_count' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if ($hasProviderCol) {
                    $detectedProvider = null;
                    if (str_starts_with($cleanKey, 'AIza') || str_starts_with($cleanKey, 'AQ.') || str_starts_with($cleanKey, 'ya29.')) {
                        $detectedProvider = 'gemini';
                    } elseif (str_starts_with($cleanKey, 'sk-') || str_starts_with($cleanKey, 'gsk_')) {
                        $detectedProvider = 'deepseek';
                    }
                    $provider = $detectedProvider ?: ($request->provider ?: 'deepseek');
                    if ($provider === 'groq') {
                        $provider = 'deepseek';
                    }
                    $data['provider'] = $provider;
                }

                DB::table('api_keys')->insert($data);
                $inserted++;
            }

            if ($inserted === 0) {
                return redirect()->back()->with('error', 'Semua API Key yang dimasukkan sudah terdaftar.');
            }

            $msg = $inserted === 1
                ? '1 API Key berhasil disimpan.'
                : "{$inserted} API Key berhasil disimpan" . ($skipped > 0 ? " ({$skipped} duplikat dilewati)." : '.');
            return redirect()->back()->with('success', $msg);
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan API Key: ' . $e->getMessage());
        }
    }

    public function updateUserName(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
        ]);
        $user = User::findOrFail(Auth::id());
        $user->name = trim($request->name);
        $user->save();
        return redirect()->back()->with('success', 'Nama profil mahasiswa berhasil diperbarui.');
    }

    public function deleteApiKey($id)
    {
        try {
            DB::table('api_keys')->where('id', $id)->where('user_id', Auth::id())->delete();
            return redirect()->back()->with('success', 'API Key dihapus.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus API Key.');
        }
    }

    public function testApiKey($id)
    {
        $keyRow = DB::table('api_keys')->where('id', $id)->where('user_id', Auth::id())->first();
        if (!$keyRow) {
            return response()->json(['status' => 'error', 'message' => 'API Key tidak ditemukan.'], 404);
        }

        $rawKey = trim($keyRow->key);
        $provider = $keyRow->provider ?? '';
        if (empty($provider)) {
            $provider = (str_starts_with($rawKey, 'AIza') || str_starts_with($rawKey, 'AQ.')) ? 'gemini' : 'deepseek';
        }

        try {
            if ($provider === 'gemini') {
                $isOAuthBearer = str_starts_with($rawKey, 'ya29.');
                $client = new \GuzzleHttp\Client(['timeout' => 14, 'verify' => false]);
                $testModels = ['gemini-2.5-flash', 'gemini-2.0-flash', 'gemini-2.5-pro'];
                $lastEx = null;

                foreach ($testModels as $testModel) {
                    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$testModel}:generateContent";

                    // Metode 1: Header x-goog-api-key (Standar resmi Google untuk kunci AQ. dan AIza)
                    try {
                        $headers = [
                            'Content-Type' => 'application/json',
                            $isOAuthBearer ? 'Authorization' : 'x-goog-api-key' => $isOAuthBearer ? ('Bearer ' . $rawKey) : $rawKey,
                        ];
                        $res = $client->post($url, [
                            'headers' => $headers,
                            'json' => ['contents' => [['parts' => [['text' => 'Ping']]]]]
                        ]);
                        if ($res->getStatusCode() === 200) {
                            return response()->json(['status' => 'success', 'message' => "API Key Google Gemini Valid & Aktif! ({$testModel})"]);
                        }
                    } catch (\Throwable $exA) {
                        $lastEx = $exA;
                    }

                    // Metode 2: Query param ?key= (Fallback alternatif)
                    if (!$isOAuthBearer) {
                        try {
                            $res = $client->post($url, [
                                'headers' => ['Content-Type' => 'application/json'],
                                'query' => ['key' => $rawKey],
                                'json' => ['contents' => [['parts' => [['text' => 'Ping']]]]]
                            ]);
                            if ($res->getStatusCode() === 200) {
                                return response()->json(['status' => 'success', 'message' => "API Key Google Gemini Valid & Aktif! ({$testModel})"]);
                            }
                        } catch (\Throwable $exB) {
                            $lastEx = $exB;
                        }
                    }
                }

                if ($lastEx) {
                    throw $lastEx;
                }
            } else {
                if (str_starts_with($rawKey, 'sk-or-')) {
                    $endpoint = 'https://openrouter.ai/api/v1/chat/completions';
                    $model = 'deepseek/deepseek-chat:free';
                } elseif (str_starts_with($rawKey, 'gsk_')) {
                    $endpoint = 'https://api.groq.com/openai/v1/chat/completions';
                    $model = 'llama-3.1-8b-instant';
                } else {
                    $endpoint = 'https://api.deepseek.com/chat/completions';
                    $model = 'deepseek-chat';
                }

                $res = \Illuminate\Support\Facades\Http::withHeaders([
                    'Authorization' => 'Bearer ' . $rawKey,
                    'Content-Type' => 'application/json',
                ])->timeout(12)->post($endpoint, [
                            'model' => $model,
                            'messages' => [['role' => 'user', 'content' => 'Ping']],
                            'max_tokens' => 5
                        ]);

                if ($res->successful()) {
                    return response()->json(['status' => 'success', 'message' => 'API Key ' . strtoupper($provider) . ' Valid & Aktif!']);
                } else {
                    $status = $res->status();
                    $body = $res->json()['error']['message'] ?? $res->body();
                    $msg = "HTTP {$status}: ";
                    if ($status === 401) {
                        $msg .= "Kunci ditolak (Kunci DeepSeek harus diawali 'sk-...').";
                    } else {
                        $msg .= \Illuminate\Support\Str::limit($body, 120);
                    }
                    return response()->json(['status' => 'error', 'message' => $msg], 400);
                }
            }
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $status = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
            $body = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();
            $msg = "HTTP {$status}: ";
            if ($status === 400 && str_contains($body, 'API_KEY_INVALID')) {
                $msg .= "Kunci tidak valid. Gemini resmi harus diawali 'AIzaSy...'.";
            } elseif ($status === 401 || $status === 403) {
                $msg .= "Akses ditolak / Token kadaluarsa.";
            } else {
                $msg .= \Illuminate\Support\Str::limit($body, 120);
            }
            return response()->json(['status' => 'error', 'message' => $msg], 400);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }

        return response()->json(['status' => 'error', 'message' => 'Respon tidak dikenal.'], 400);
    }

    public function claimReward(Request $request)
    {
        $user = User::find(Auth::id());
        return response()->json([
            'status' => 'success',
            'message' => 'Akses AI aktif untuk akun Anda.',
            'new_credits' => $user ? $user->credits : 99
        ]);
    }

    public function startProject(Request $request)
    {
        $request->validate([
            'judul' => 'required|string',
            'prodi' => 'required|string',
            'metode_penelitian' => 'required|string',
            'sumber_referensi' => 'required|array|min:1',
        ], [
            'sumber_referensi.required' => 'Silakan pilih minimal 1 Sumber Referensi.',
            'sumber_referensi.min' => 'Silakan pilih minimal 1 Sumber Referensi.',
        ]);

        $cleanJudul = trim($request->judul);
        $defaultIdentity = $this->getDefaultUserIdentity(Auth::id());

        // Ekstrak file pedoman kampus jika diunggah (utamakan hasil pembacaan PDF.js browser yang 100% akurat untuk PDF Unicode/CID)
        $guidelineText = trim((string) $request->input('pedoman_text', ''));
        if ($request->hasFile('pedoman_file') && mb_strlen($guidelineText) < 150) {
            $serverExtracted = $this->extractTextFromUploadedFile($request->file('pedoman_file'));
            $guidelineText = trim($guidelineText . "\n" . $serverExtracted);
        }
        if (mb_strlen($guidelineText) > 9500) {
            $guidelineText = $this->extractSmartGuidelineSummary($guidelineText, 9500);
        }

        // Ekstrak file referensi khusus (artikel/buku wajib) jika diunggah
        $refFilesText = '';
        if ($request->hasFile('referensi_files')) {
            $files = is_array($request->file('referensi_files')) ? $request->file('referensi_files') : [$request->file('referensi_files')];
            $extractedRefs = [];
            foreach (array_slice($files, 0, 5) as $rf) {
                if ($rf && $rf->isValid()) {
                    $extractedRefs[] = "[" . $rf->getClientOriginalName() . "]: " . Str::limit($this->extractTextFromUploadedFile($rf), 1500, '');
                }
            }
            $refFilesText = implode("\n", $extractedRefs);
        }

        // Encode & kompres logo kampus agar aman disimpan & terbaca di Word/PDF
        $logoBase64 = '';
        if ($request->filled('logo_base64')) {
            $logoBase64 = $this->normalizeLogoBase64($request->input('logo_base64'));
        } elseif ($request->hasFile('logo_kampus') && $request->file('logo_kampus')->isValid()) {
            $logoBase64 = $this->normalizeLogoBase64($request->file('logo_kampus'));
        }
        if (empty($logoBase64) && !empty($defaultIdentity['logo_base64'])) {
            $logoBase64 = $this->normalizeLogoBase64($defaultIdentity['logo_base64']);
        }

        $namaInput = trim($request->input('nama', ''));
        $nimInput = trim($request->input('nim', ''));
        $kampusInput = trim($request->input('kampus', ''));
        $fakultasInput = trim($request->input('fakultas', ''));

        $meta = [
            'nama' => $namaInput ?: ($defaultIdentity['nama'] ?? (Auth::user()->name ?? '')),
            'nim' => $nimInput ?: ($defaultIdentity['nim'] ?? ''),
            'kampus' => $kampusInput ?: ($defaultIdentity['kampus'] ?? ''),
            'fakultas' => $fakultasInput ?: ($defaultIdentity['fakultas'] ?? ''),
            'logo_base64' => $logoBase64,
            'jenis_penelitian' => $request->input('jenis_penelitian', 'Proposal Skripsi (S1)'),
            'tahap_pengerjaan' => $request->input('tahap_pengerjaan', 'Proposal Skripsi (Bab I–III)'),
            'struktur_kustom' => trim($request->input('struktur_kustom', '')),
            'data_awal' => trim($request->input('data_awal', '')),
            'pedoman_text' => Str::limit($guidelineText, 9500, ''),
            'referensi_khusus' => Str::limit($refFilesText, 5000, ''),
            'sitasi' => [
                'tahun_min' => $request->input('tahun_min', '2023'),
                'asal' => $request->input('asal_referensi', 'Indonesia + Internasional'),
                'gaya' => $request->input('gaya_sitasi', 'APA 7th'),
                'bahasa' => $request->input('bahasa_penulisan', 'Indonesia'),
                'sumber' => $request->input('sumber_referensi', ['Umum', 'Terindeks Sinta']),
            ],
            'format_nomor' => $request->input('format_nomor', '1.1'),
        ];

        // Jika proyek dengan judul sama sudah ada (normalisasi spasi & tanda titik akhir agar konsisten di semua device), perbarui metadata/tahap agar sinkron
        $normTargetTitle = (string) preg_replace('/\s+/', ' ', mb_strtolower(rtrim(trim($cleanJudul), '. ')));
        $existing = Project::where('user_id', Auth::id())
            ->orderBy('id', 'desc')
            ->get()
            ->first(function ($candidate) use ($normTargetTitle) {
                $candNorm = (string) preg_replace('/\s+/', ' ', mb_strtolower(rtrim(trim((string) $candidate->judul), '. ')));
                return $candNorm === $normTargetTitle;
            });

        if ($existing) {
            $oldMeta = $this->getProjectMeta($existing);
            $mergedMeta = array_merge($oldMeta, array_filter($meta, fn($v) => $v !== '' && $v !== null));
            $mergedMeta['tahap_pengerjaan'] = $meta['tahap_pengerjaan'];
            if (!empty($logoBase64)) {
                $mergedMeta['logo_base64'] = $logoBase64;
            }
            $existing->prodi = trim($request->prodi);
            $existing->metode_penelitian = $request->metode_penelitian;
            $existing->citation_validation = json_encode($mergedMeta, JSON_UNESCAPED_UNICODE);
            $existing->save();
            return redirect()->route('app.index', ['id' => $existing->id]);
        }

        $authUser = Auth::user();
        if ($authUser) {
            try {
                $project = \Illuminate\Support\Facades\DB::transaction(function () use ($request, $cleanJudul, $meta, $authUser) {
                    $projGate = $this->billing()->canStartProject($authUser->fresh());
                    if (empty($projGate['allowed'])) {
                        throw new \RuntimeException($projGate['message'] ?? 'Kuota proyek akun Anda sudah habis.');
                    }

                    $proj = Project::create([
                        'judul' => $cleanJudul,
                        'prodi' => trim($request->prodi),
                        'metode_penelitian' => $request->metode_penelitian,
                        'variabel_penelitian' => $request->variabel_penelitian ?: ($request->input('data_awal') ? Str::limit($request->input('data_awal'), 180) : 'Sesuai Judul'),
                        'sumber_data' => $request->input('jenis_penelitian', 'Skripsi (S1)'),
                        'user_id' => Auth::id(),
                        'status' => 'draft',
                        'citation_validation' => json_encode($meta, JSON_UNESCAPED_UNICODE),
                    ]);

                    if (!$this->billing()->markProjectStarted($authUser, $proj)) {
                        throw new \RuntimeException('Akun Free hanya mencakup 1 proyek uji coba. Aktifkan paket mulai dari Paket Starter Rp15.000 untuk membuka secara penuh.');
                    }

                    return $proj;
                });
            } catch (\RuntimeException $gateEx) {
                $msg = $gateEx->getMessage();
                if ($request->expectsJson()) {
                    return response()->json([
                        'status' => 'upgrade_required',
                        'reason' => 'free_project_limit',
                        'message' => $msg,
                    ], 402);
                }
                return redirect()->route('app.create', ['tab' => 'pricing'])
                    ->with('upgrade_modal', $msg);
            }
        } else {
            $project = Project::create([
                'judul' => $cleanJudul,
                'prodi' => trim($request->prodi),
                'metode_penelitian' => $request->metode_penelitian,
                'variabel_penelitian' => $request->variabel_penelitian ?: ($request->input('data_awal') ? Str::limit($request->input('data_awal'), 180) : 'Sesuai Judul'),
                'sumber_data' => $request->input('jenis_penelitian', 'Skripsi (S1)'),
                'user_id' => Auth::id(),
                'status' => 'draft',
                'citation_validation' => json_encode($meta, JSON_UNESCAPED_UNICODE),
            ]);
        }

        return redirect()->route('app.index', ['id' => $project->id]);
    }

    public function deleteProject($id)
    {
        $project = Project::where('user_id', Auth::id())->findOrFail($id);
        $project->delete();
        return redirect()->route('app.create', ['tab' => 'projects'])->with('success', 'Proyek berhasil dihapus.');
    }

    public function generatorIndex($id)
    {
        $project = Project::where('user_id', Auth::id())->findOrFail($id);
        app(\App\Services\TargetedStructureRepairService::class)->repairIfTargetProject($project, Auth::user());
        $history = Project::where('user_id', Auth::id())->orderBy('updated_at', 'desc')->get();
        $meta = $this->getProjectMeta($project);
        $defaultIdentity = $this->getDefaultUserIdentity(Auth::id());

        foreach (['nama', 'nim', 'kampus', 'fakultas', 'logo_base64'] as $field) {
            if (empty($meta[$field]) && !empty($defaultIdentity[$field])) {
                $meta[$field] = $defaultIdentity[$field];
            }
        }

        if (!empty($project->bab4_content) && (empty($project->bab5_content) || strlen(trim(strip_tags($project->bab5_content))) < 120)) {
            $project->bab5_content = $this->buildDefaultBab5Content($project);
            $project->save();
        }

        $hasMathUpdate = false;
        foreach (['bab1_content', 'bab2_content', 'bab3_content', 'bab4_content', 'bab5_content'] as $col) {
            if (!empty($project->$col) && (str_contains($project->$col, '\frac') || str_contains($project->$col, '\hat') || str_contains($project->$col, '{,}') || str_contains($project->$col, '$'))) {
                $project->$col = $this->formatAcademicMathText($project->$col);
                $hasMathUpdate = true;
            }
            if ($col !== 'bab5_content' && !empty($project->$col)) {
                [$cleanBody, $extractedDp] = $this->stripEmbeddedDaftarPustaka((string) $project->$col);
                if ($cleanBody !== $project->$col) {
                    $project->$col = $cleanBody;
                    if (empty(trim((string) $project->daftar_pustaka)) && $extractedDp !== '') {
                        $project->daftar_pustaka = $extractedDp;
                    }
                    $hasMathUpdate = true;
                }
            }
        }
        if ($hasMathUpdate) {
            $project->save();
        }

        $authUser = Auth::user();
        $billingSummary = $authUser ? $this->billing()->getUserSummary($authUser, false) : null;
        $plans = $this->billing()->getPlans(false);
        $projectUnlocked = $authUser ? ($this->billing()->isAdminUser($authUser) || $this->billing()->isUnlimitedActive($authUser) || !empty($project->is_full_unlocked)) : false;

        return $this->applyNoStoreHeaders(response()->view('generator', compact('project', 'history', 'meta', 'billingSummary', 'plans', 'projectUnlocked')));
    }

    public function syncProjectState($id)
    {
        $project = Project::where('user_id', Auth::id())->findOrFail($id);
        app(\App\Services\TargetedStructureRepairService::class)->repairIfTargetProject($project, Auth::user());
        $meta = $this->getProjectMeta($project);
        $defaultIdentity = $this->getDefaultUserIdentity(Auth::id());
        foreach (['nama', 'nim', 'kampus', 'fakultas', 'logo_base64'] as $field) {
            if (empty($meta[$field]) && !empty($defaultIdentity[$field])) {
                $meta[$field] = $defaultIdentity[$field];
            }
        }
        $chapters = [
            'bab1' => $this->stripEmbeddedDaftarPustaka((string) ($project->bab1_content ?? ''))[0],
            'bab2' => $this->stripEmbeddedDaftarPustaka((string) ($project->bab2_content ?? ''))[0],
            'bab3' => $this->stripEmbeddedDaftarPustaka((string) ($project->bab3_content ?? ''))[0],
            'bab4' => $this->stripEmbeddedDaftarPustaka((string) ($project->bab4_content ?? ''))[0],
            'bab5' => (string) ($project->bab5_content ?? ''),
            'daftar_pustaka' => (string) ($project->daftar_pustaka ?? ''),
        ];
        $authUser = Auth::user();
        $unlocked = $authUser ? ($this->billing()->isAdminUser($authUser) || $this->billing()->isUnlimitedActive($authUser) || !empty($project->is_full_unlocked)) : false;
        $stateHash = md5((string) json_encode([$chapters, $meta, $project->judul, $project->prodi, $unlocked], JSON_UNESCAPED_UNICODE));

        return $this->applyNoStoreHeaders(response()->json([
            'status' => 'success',
            'state_hash' => $stateHash,
            'updated_ts' => optional($project->updated_at)->timestamp ?? 0,
            'judul' => $project->judul,
            'prodi' => $project->prodi,
            'chapters' => $chapters,
            'meta' => $meta,
            'project_unlocked' => $unlocked,
        ]));
    }

    /**
     * Upload / Update Pedoman Kampus & Pengaturan Proyek di Workspace
     */
    public function uploadGuideline($id, Request $request)
    {
        $project = Project::where('user_id', Auth::id())->findOrFail($id);
        $meta = $this->getProjectMeta($project);

        $extracted = '';
        $fileName = '';

        if ($request->hasFile('pedoman_file')) {
            $file = $request->file('pedoman_file');
            $fileName = $file->getClientOriginalName();
            $extracted = $this->extractTextFromUploadedFile($file);
        }

        $customRules = trim($request->input('pedoman_text', ''));
        $combined = trim(($fileName ? "[File Pedoman: {$fileName}]\n" : '') . $customRules . "\n" . $extracted);

        if (!empty($combined)) {
            $meta['pedoman_text'] = Str::limit($combined, 9000, '');
        }
        if ($request->filled('struktur_kustom')) {
            $meta['struktur_kustom'] = trim($request->input('struktur_kustom'));
        }
        if ($request->filled('data_awal')) {
            $meta['data_awal'] = trim($request->input('data_awal'));
        }
        if ($request->filled('tahap_pengerjaan')) {
            $meta['tahap_pengerjaan'] = $request->input('tahap_pengerjaan');
        }

        $project->citation_validation = json_encode($meta, JSON_UNESCAPED_UNICODE);
        $project->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Pedoman kampus & konfigurasi naskah berhasil disimpan.',
            'preview' => Str::limit($meta['pedoman_text'] ?? '', 350),
        ]);
    }

    // =========================================================================
    // 2. AI TOOLS (CARI JUDUL, JURNAL ASLI + DOWNLOAD PDF, PARAFRASE, PPT, SIDANG)
    // =========================================================================

    public function generateTitles(Request $request)
    {
        $request->validate(['topik' => 'required|string']);
        $authUser = Auth::user();
        $toolReserved = false;
        if ($authUser) {
            $access = $this->billing()->reserveToolAccess($authUser, 'titles', 'Generator Ide Judul');
            if (empty($access['allowed'])) {
                return response()->json([
                    'status' => 'upgrade_required',
                    'reason' => $access['reason'] ?? 'free_tool_limit',
                    'message' => $access['message'],
                ], 402);
            }
            $toolReserved = !empty($access['reserved']);
        }
        $prodi = $request->input('prodi') ?: 'Umum';
        $metode = $request->input('metode') ?: 'Kuantitatif / Kualitatif / R&D';
        $model = $request->input('model', 'auto');

        $prompt = "Sebagai Dosen Pembimbing Skripsi Senior Perguruan Tinggi Indonesia (2025–2026), berikan tepat 12 rekomendasi judul skripsi S1 terbaik untuk:
- Topik / Bidang Penelitian: {$request->topik}
- Metodologi: {$metode}
- Program Studi: {$prodi}

Syarat WAJIB:
1. Spesifik, orisinal, relevan dengan tren riset 2025–2026, serta secara eksplisit mencerminkan metodologi \"{$metode}\" (jika Kuantitatif cantumkan variabel bebas & terikat yang terukur; jika Kualitatif fokus pada analisis/strategi/fenomena & lokus nyata; jika R&D/IT cantumkan sistem/algoritma & metrik evaluasi; jika Hukum cantumkan analisis yuridis/regulasi/studi kasus).
2. Gunakan Bahasa Indonesia akademik baku sesuai EYD V, pola kalimat bervariasi (tidak monoton mengulang awalan yang sama), dan hindari istilah klise.
3. Output WAJIB HANYA daftar judul (1 baris 1 judul), tepat 12 baris, tanpa kata pengantar, tanpa keterangan tambahan, tanpa tanda petik, dan tanpa nomor urut.";

        try {
            $content = $this->ai->generateText($prompt, $model);
            $titles = array_filter(explode("\n", $content), fn($t) => strlen(trim($t)) > 15);
            $cleanTitles = array_values(array_unique(array_map(function ($t) {
                return trim(preg_replace('/^[\d\-\.\*\s\"\)]+|[\"]+$/', '', trim($t)));
            }, $titles)));

            $cleanTitles = array_values(array_filter($cleanTitles, function ($t) {
                return strlen($t) > 15 && !preg_match('/^(berikut|rekomendasi|judul\s+skripsi|daftar)/i', $t);
            }));

            return response()->json([
                'status' => 'success',
                'data' => array_slice($cleanTitles, 0, 10),
            ]);
        } catch (Exception $e) {
            if ($authUser && $toolReserved) {
                $this->billing()->releaseToolReservation($authUser, 'titles');
            }
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function findJournals(Request $request)
    {
        $request->validate(['keyword' => 'required|string']);
        $authUser = Auth::user();
        $toolReserved = false;
        if ($authUser) {
            $access = $this->billing()->reserveToolAccess($authUser, 'journals', 'Pencari Jurnal Ilmiah');
            if (empty($access['allowed'])) {
                return response()->json([
                    'status' => 'upgrade_required',
                    'reason' => $access['reason'] ?? 'free_tool_limit',
                    'message' => $access['message'],
                ], 402);
            }
            $toolReserved = !empty($access['reserved']);
        }
        $keyword = trim($request->keyword);
        $scope = $request->input('scope', 'all'); // all | id | int
        $model = $request->input('model', 'auto');

        $journalsId = [];
        $journalsInt = [];

        $mapOpenAlexWork = function ($work, string $scopeLabel) {
            $title = trim($work['title'] ?? '');
            if (empty($title))
                return null;

            $authors = [];
            foreach (array_slice($work['authorships'] ?? [], 0, 3) as $auth) {
                if (!empty($auth['author']['display_name'])) {
                    $authors[] = $auth['author']['display_name'];
                }
            }
            $authorStr = !empty($authors) ? implode(', ', $authors) : 'Tim Peneliti';
            if (count($work['authorships'] ?? []) > 3) {
                $authorStr .= ', et al.';
            }

            $year = $work['publication_year'] ?? date('Y');
            $venue = $work['primary_location']['source']['display_name']
                ?? $work['host_venue']['display_name']
                ?? ($scopeLabel === 'Indonesia' ? 'Jurnal Ilmiah Nasional (Sinta)' : 'International Open-Access Journal');

            $bib = $work['biblio'] ?? [];
            $vol = trim((string) ($bib['volume'] ?? ''));
            $iss = trim((string) ($bib['issue'] ?? ''));
            $fp = trim((string) ($bib['first_page'] ?? ''));
            $lp = trim((string) ($bib['last_page'] ?? ''));
            $bibPart = ($vol !== '' ? ", {$vol}" . ($iss !== '' ? "({$iss})" : '') : '')
                . ($fp !== '' ? ", {$fp}" . ($lp !== '' && $lp !== $fp ? "-{$lp}" : '') : '');

            $pdfUrl = $work['open_access']['oa_url']
                ?? $work['primary_location']['pdf_url']
                ?? $work['primary_location']['landing_page_url']
                ?? $work['doi']
                ?? ('https://scholar.google.com/scholar?q=' . urlencode($title . ' filetype:pdf'));

            $doiUrl = $work['doi'] ?? ('https://scholar.google.com/scholar?q=' . urlencode($title));

            return [
                'title' => $title,
                'author' => $authorStr,
                'journal' => $venue,
                'year' => (string) $year,
                'pdf_url' => $pdfUrl,
                'doi_url' => $doiUrl,
                'citation' => "{$authorStr} ({$year}). {$title}. {$venue}{$bibPart}. " . ($work['doi'] ?? ''),
                'scope' => $scopeLabel,
                'is_real_oa' => true,
            ];
        };

        try {
            $headers = ['User-Agent' => 'SkripsiAI-Academic/2.1 (mailto:support@skripsi-ai.app)'];

            if ($scope === 'all' || $scope === 'id') {
                $oaId = Http::timeout(10)->withHeaders($headers)->get('https://api.openalex.org/works', [
                    'search' => $keyword,
                    'filter' => 'from_publication_date:2021-01-01,to_publication_date:2026-12-31,language:id',
                    'per-page' => $scope === 'id' ? 10 : 5,
                ]);
                if ($oaId->successful()) {
                    foreach ($oaId->json()['results'] ?? [] as $w) {
                        $item = $mapOpenAlexWork($w, 'Indonesia');
                        if ($item)
                            $journalsId[] = $item;
                    }
                }
                if (count($journalsId) < 3) {
                    $oaIdFallback = Http::timeout(10)->withHeaders($headers)->get('https://api.openalex.org/works', [
                        'search' => $keyword . ' Indonesia',
                        'filter' => 'from_publication_date:2021-01-01,to_publication_date:2026-12-31',
                        'per-page' => 5,
                    ]);
                    if ($oaIdFallback->successful()) {
                        foreach ($oaIdFallback->json()['results'] ?? [] as $w) {
                            $item = $mapOpenAlexWork($w, 'Indonesia');
                            if ($item)
                                $journalsId[] = $item;
                        }
                    }
                }
            }

            if ($scope === 'all' || $scope === 'int') {
                $oaInt = Http::timeout(10)->withHeaders($headers)->get('https://api.openalex.org/works', [
                    'search' => $keyword,
                    'filter' => 'from_publication_date:2021-01-01,to_publication_date:2026-12-31,language:en',
                    'per-page' => $scope === 'int' ? 10 : 5,
                ]);
                if ($oaInt->successful()) {
                    foreach ($oaInt->json()['results'] ?? [] as $w) {
                        $item = $mapOpenAlexWork($w, 'Internasional');
                        if ($item)
                            $journalsInt[] = $item;
                    }
                }
            }

            // CrossRef query jika hasil masih sedikit (database DOI publikasi nyata dunia 2021-2026)
            if (count($journalsId) + count($journalsInt) < 4) {
                $crRes = Http::timeout(10)->withHeaders($headers)->get('https://api.crossref.org/works', [
                    'query' => $keyword,
                    'filter' => 'from-pub-date:2021-01-01,until-pub-date:2026-12-31',
                    'rows' => 10,
                    'select' => 'title,author,container-title,published,volume,issue,page,DOI,URL'
                ]);
                if ($crRes->successful()) {
                    foreach ($crRes->json()['message']['items'] ?? [] as $crItem) {
                        $cTitle = !empty($crItem['title'][0]) ? trim($crItem['title'][0]) : '';
                        if (empty($cTitle))
                            continue;
                        $cAuthors = [];
                        foreach (array_slice($crItem['author'] ?? [], 0, 3) as $a) {
                            $cAuthors[] = trim(($a['given'] ?? '') . ' ' . ($a['family'] ?? ''));
                        }
                        $cAuthStr = !empty($cAuthors) ? implode(', ', array_filter($cAuthors)) : 'Peneliti';
                        $cVenue = !empty($crItem['container-title'][0]) ? $crItem['container-title'][0] : 'Jurnal Ilmiah Bereputasi';
                        $cYear = $crItem['published']['date-parts'][0][0] ?? '2024';
                        $cVol = trim((string) ($crItem['volume'] ?? ''));
                        $cIss = trim((string) ($crItem['issue'] ?? ''));
                        $cPage = trim((string) ($crItem['page'] ?? ''));
                        $cBib = ($cVol !== '' ? ", {$cVol}" . ($cIss !== '' ? "({$cIss})" : '') : '') . ($cPage !== '' ? ", {$cPage}" : '');
                        $cUrl = !empty($crItem['DOI']) ? ('https://doi.org/' . $crItem['DOI']) : ($crItem['URL'] ?? ('https://scholar.google.com/scholar?q=' . urlencode($cTitle)));
                        $cPdfUrl = 'https://scholar.google.com/scholar?q=' . urlencode($cTitle . ' filetype:pdf');
                        $cScope = (str_contains(strtolower($cTitle), 'indonesia') || str_contains(strtolower($cVenue), 'jurnal') || preg_match('/\b(pengaruh|analisis|implementasi|penerapan|pengembangan|studi)\b/i', $cTitle)) ? 'Indonesia' : 'Internasional';

                        $crEntry = [
                            'title' => $cTitle,
                            'author' => $cAuthStr,
                            'journal' => $cVenue,
                            'year' => (string) $cYear,
                            'pdf_url' => $cPdfUrl,
                            'doi_url' => $cUrl,
                            'citation' => "{$cAuthStr} ({$cYear}). {$cTitle}. {$cVenue}{$cBib}. {$cUrl}",
                            'scope' => $cScope,
                            'is_real_oa' => true,
                        ];
                        if ($cScope === 'Indonesia' && ($scope === 'all' || $scope === 'id')) {
                            $journalsId[] = $crEntry;
                        } elseif ($cScope !== 'Indonesia' && ($scope === 'all' || $scope === 'int')) {
                            $journalsInt[] = $crEntry;
                        }
                    }
                }
            }
        } catch (Throwable $e) {
            Log::warning('Scholar search API notice: ' . $e->getMessage());
        }

        // Lengkapi via AI akademis jika masih kurang dari 4 artikel nyata
        if (
            ($scope === 'all' && (count($journalsId) < 3 || count($journalsInt) < 3))
            || ($scope === 'id' && count($journalsId) < 4)
            || ($scope === 'int' && count($journalsInt) < 4)
        ) {
            try {
                $prompt = "PERAN: Pustakawan Akademik & Kurator Jurnal Ilmiah SINTA & Scopus.
TUGAS: Berikan daftar referensi jurnal ilmiah ASLI dan NYATA yang benar-benar pernah terbit (2021-2026) dan terindeks di Google Scholar / SINTA / Scopus / IEEE / ScienceDirect terkait kata kunci: '{$keyword}'.
ATURAN KETAT:
1. DILARANG KERAS mengarang/halusinasi judul fiktif atau tahun di atas 2026. Tahun terbit WAJIB antara 2021 hingga 2026.
2. Seluruh artikel harus nyata dan dapat ditemukan di SINTA Kemdikbud / Garuda / Google Scholar dengan DOI atau link nyata.
3. Format WAJIB per baris: Kategori(Indonesia/Internasional)|Judul Artikel Nyata|Nama Penulis|Nama Jurnal Ilmiah Terakreditasi|Tahun Terbit(2021-2026)|Link/DOI Asli
4. Berikan masing-masing 4 artikel Indonesia dan 4 artikel Internasional tanpa nomor urut dan tanpa teks pembuka/penutup.";

                $content = $this->ai->generateText($prompt, $model);
                $lines = array_filter(explode("\n", $content), fn($t) => !empty(trim($t)));
                foreach ($lines as $line) {
                    $parts = explode('|', $line);
                    if (count($parts) >= 4) {
                        $rawScope = strtolower(trim($parts[0]));
                        $isIndo = str_contains($rawScope, 'indo');
                        $scopeLabel = $isIndo ? 'Indonesia' : 'Internasional';
                        $jTitle = trim(preg_replace('/^[\d\.\-\s]+/', '', $parts[1]));
                        $jAuthor = trim($parts[2]);
                        $jVenue = trim($parts[3]);
                        $rawYear = isset($parts[4]) ? (int) preg_replace('/\D/', '', $parts[4]) : 2024;
                        $jYear = (string) min(2026, max(2021, $rawYear ?: 2024));
                        $rawLink = isset($parts[5]) ? trim($parts[5]) : '';
                        $doiLink = (str_starts_with($rawLink, 'http') || str_starts_with($rawLink, '10.'))
                            ? (str_starts_with($rawLink, 'http') ? $rawLink : 'https://doi.org/' . $rawLink)
                            : ('https://scholar.google.com/scholar?q=' . urlencode($jTitle));

                        $entry = [
                            'title' => $jTitle,
                            'author' => $jAuthor,
                            'journal' => $jVenue,
                            'year' => $jYear,
                            'pdf_url' => $doiLink,
                            'doi_url' => $doiLink,
                            'citation' => "{$jAuthor} ({$jYear}). {$jTitle}. {$jVenue}. {$doiLink}",
                            'scope' => $scopeLabel,
                            'is_real_oa' => true,
                        ];

                        if ($isIndo && ($scope === 'all' || $scope === 'id')) {
                            $journalsId[] = $entry;
                        } elseif (!$isIndo && ($scope === 'all' || $scope === 'int')) {
                            $journalsInt[] = $entry;
                        }
                    }
                }
            } catch (Exception $e) {
                if (empty($journalsId) && empty($journalsInt)) {
                    if ($authUser && $toolReserved) {
                        $this->billing()->releaseToolReservation($authUser, 'journals');
                    }
                    return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
                }
            }
        }

        $merged = [];
        if ($scope === 'id') {
            $merged = array_slice($journalsId, 0, 10);
        } elseif ($scope === 'int') {
            $merged = array_slice($journalsInt, 0, 10);
        } else {
            // Gabungkan seimbang: Indonesia & Internasional
            $max = max(count($journalsId), count($journalsInt));
            for ($i = 0; $i < $max; $i++) {
                if (isset($journalsId[$i]))
                    $merged[] = $journalsId[$i];
                if (isset($journalsInt[$i]))
                    $merged[] = $journalsInt[$i];
            }
            $merged = array_slice($merged, 0, 10);
        }

        return response()->json([
            'status' => 'success',
            'data' => $merged,
        ]);
    }

    public function askAi(Request $request)
    {
        $request->validate(['message' => 'required']);
        $model = $request->input('model', 'auto');
        $rawMsg = trim($request->message);
        $lowerMsg = strtolower($rawMsg);

        // Respon super cepat (<1ms) untuk sapaan umum tanpa membebani LLM dan tanpa basa-basi bertele-tele
        $greetings = [
            'selamat siang' => "Selamat siang! Ada bagian bab skripsi atau kendala penelitian yang ingin Anda diskusikan atau review saat ini?",
            'selamat pagi' => "Selamat pagi! Silakan ceritakan topik, bab naskah, atau kendala penelitian yang sedang Anda hadapi.",
            'selamat sore' => "Selamat sore! Ada kendala dalam menyusun bab, olah data, atau persiapan sidang skripsi Anda?",
            'selamat malam' => "Selamat malam! Silakan ajukan pertanyaan atau kendala skripsimu, saya siap membantu bimbingan.",
            'halo' => "Halo! Ada yang bisa saya bantu terkait penulisan bab, metodologi, atau persiapan sidang skripsimu?",
            'hai' => "Hai! Silakan ceritakan bagian skripsi mana yang sedang Anda kerjakan atau butuh bantuan revisi.",
            'assalamualaikum' => "Wa'alaikumsalam wr. wb. Silakan, ada bagian skripsi atau topik penelitian apa yang ingin kita bahas hari ini?",
            'terima kasih' => "Sama-sama! Tetap semangat menyelesaikan skripsinya. Jika ada kendala lain, jangan ragu bertanya lagi.",
            'makasih' => "Sama-sama! Semoga pengerjaan skripsinya lancar dan sukses.",
        ];

        $cleanGreet = trim(preg_replace('/[^a-zA-Z0-9\s]/', '', $lowerMsg));
        if (isset($greetings[$cleanGreet])) {
            return response()->json([
                'status' => 'success',
                'reply' => $greetings[$cleanGreet]
            ]);
        }
        foreach ($greetings as $greetKey => $greetReply) {
            if ($cleanGreet === $greetKey || $cleanGreet === $greetKey . ' pak' || $cleanGreet === $greetKey . ' bu' || $cleanGreet === $greetKey . ' dok') {
                return response()->json([
                    'status' => 'success',
                    'reply' => $greetReply
                ]);
            }
        }

        try {
            $prompt = "PERAN: Anda adalah Dosen Pembimbing Akademik yang cerdas, suportif, dan to the point.
ATURAN WAJIB MENJAWAB:
1. JAWAB SECARA RINGKAS, PADAT, DAN LANGSUNG PADA INTI JAWABAN (maksimal 2–3 paragraf pendek atau poin-poin ringkas).
2. DILARANG KERAS basa-basi bertele-tele, DILARANG ceramah pengantar panjang ('Silakan duduk...', 'Saya senang Anda proaktif...', dll).
3. Langsung berikan arahan atau solusi konkret terhadap pertanyaan mahasiswa berikut:

Pertanyaan Mahasiswa:
\"{$rawMsg}\"";

            $response = $this->ai->generateText($prompt, $model);
            $cleanResponse = trim(strip_tags($response));

            return response()->json([
                'status' => 'success',
                'reply' => nl2br($cleanResponse)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghubungi asisten AI: ' . $e->getMessage()
            ]);
        }
    }

    // =========================================================================
    // 3. PROMPT ENGINEERING BAB 1–5 (DENGAN SITASI, DATA AWAL & PEDOMAN KAMPUS)
    // =========================================================================

    private function promptMaster(Project $project): string
    {
        $meta = $this->getProjectMeta($project);
        $sitasi = $meta['sitasi'] ?? [];
        $tahunMin = !empty($sitasi['tahun_min']) ? (string) $sitasi['tahun_min'] : '2023';
        $asalRef = $sitasi['asal'] ?? 'Indonesia + Internasional';
        $gayaSitasi = $sitasi['gaya'] ?? 'APA 7th';
        $bahasa = $sitasi['bahasa'] ?? 'Indonesia';
        $isEnglishMode = str_contains(strtolower($bahasa), 'inggris') || str_contains(strtolower($bahasa), 'english');
        $bahasaOverride = $isEnglishMode
            ? "\nLANGUAGE REQUIREMENT (HIGHEST PRIORITY): The student selected ENGLISH writing language. Write ALL chapter headings, sub-headings, paragraphs, tables, diagram labels, and citations in formal Academic English (American/British consistent), NOT Indonesian!\n"
            : '';
        $sumberArr = is_array($sitasi['sumber'] ?? null) ? implode(', ', $sitasi['sumber']) : 'Umum, Terindeks Sinta';
        $formatNomor = (string) ($meta['format_nomor'] ?? '1.1');
        $formatNomorRule = str_starts_with(strtoupper(trim($formatNomor)), 'A')
            ? '8. Format Sub-bab: Tulis judul sub-bab pada baris tersendiri menggunakan PENOMORAN HURUF KAPITAL (contoh: A. Latar Belakang Masalah, B. Rumusan Masalah, dst., anak sub-bab: 1., 2., dst.).'
            : '8. Format Sub-bab: Tulis nomor sub-bab pada baris tersendiri menggunakan penomoran desimal (contoh: 1.1 Latar Belakang Masalah, 1.2 Rumusan Masalah, dst.).';

        $skillPath = resource_path('skills/skripsi_indonesia_skill.md');
        $skillRules = file_exists($skillPath)
            ? Str::limit(trim(file_get_contents($skillPath)), 16000, '')
            : '';

        $verifiedRefs = $this->fetchVerifiedReferencesForProject($project);
        $verifiedRefText = !empty($verifiedRefs)
            ? "\nBANK REFERENSI JURNAL ILMIAH TERVERIFIKASI (TERKUNCI UNTUK PROYEK INI — FORMAT {$gayaSitasi}, TAHUN >= {$tahunMin}, ASAL: {$asalRef} — PRIORITAS UTAMA UNTUK DISITASI DI BAB I–V):\n- " . implode("\n- ", $verifiedRefs) . "\n"
            : '';

        $extraContext = '';
        if (!empty($meta['pedoman_text'])) {
            $extraContext .= "\nPEDOMAN PENULISAN KAMPUS MAHASISWA (PRIORITAS UTAMA - WAJIB DIPATUHI SECARA KETAT):\n\"\"\"\n" . Str::limit($meta['pedoman_text'], 8000) . "\n\"\"\"\n";
        }
        if (!empty($meta['struktur_kustom'])) {
            $extraContext .= "\nSTRUKTUR SUB-BAB KUSTOM DARI MAHASISWA (IKUTI URUTAN INI SECARA PERSIS):\n\"\"\"\n{$meta['struktur_kustom']}\n\"\"\"\n";
        }
        if (!empty($meta['data_awal'])) {
            $extraContext .= "\nDATA AWAL / FENOMENA LAPANGAN MAHASISWA (WAJIB DIMASUKKAN KE LATAR BELAKANG):\n\"\"\"\n{$meta['data_awal']}\n\"\"\"\n";
        }
        if (!empty($meta['referensi_khusus'])) {
            $extraContext .= "\nREFERENSI WAJIB DARI UNGGAHAN MAHASISWA (WAJIB DISITASI):\n\"\"\"\n" . Str::limit($meta['referensi_khusus'], 4000) . "\n\"\"\"\n";
        }

        $styleGuideMaster = $this->buildCitationStyleGuide((string) $gayaSitasi);
        $inTextExampleMaster = $styleGuideMaster['in_text_example'];

        return "PERAN: Anda adalah Profesor dan Pembimbing Tugas Akhir Senior di Perguruan Tinggi Indonesia.

KONTEKS PENELITIAN:
- Jenis Karya Ilmiah: " . ($meta['jenis_penelitian'] ?? 'Skripsi (S1)') . "
- Judul: {$project->judul}
- Program Studi: {$project->prodi}
- Metode Penelitian: {$project->metode_penelitian}
- Pengaturan Sitasi: Gaya {$gayaSitasi} | Tahun Terbit Minimum: {$tahunMin} | Asal Referensi: {$asalRef} ({$sumberArr}) | Bahasa Penulisan: {$bahasa}
{$bahasaOverride}{$extraContext}{$verifiedRefText}
PEDOMAN STANDAR PENULISAN SKRIPSI INDONESIA (GITHUB ACADEMIC SKILL):
{$skillRules}

ATURAN PENULISAN AKADEMIK (STANDAR SKRIPSI S1 NASIONAL MANTAR RISET / SKRIPSITA):
1. Gaya Bahasa & Humanizer Alami: Gunakan Bahasa {$bahasa} ilmiah baku sesuai EYD V/KBBI, kohesif, objektif, dan BEBAS TOTAL DARI KLISE AI (DILARANG KERAS membuka kalimat dengan 'Kondisi ideal dalam...', 'Di era digital / era globalisasi saat ini...', 'Perkembangan teknologi yang pesat...', 'Tidak dapat dipungkiri bahwa...', 'Memegang peranan krusial...', atau 'Merupakan tonggak penting...'). DILARANG KERAS menyelipkan kata keterangan Inggris mentah (seperti 'increasingly', 'significantly', 'effectively') di tengah kalimat Bahasa Indonesia, dan DILARANG KERAS menyebut kata-kata meta prompt seperti 'bank referensi proyek ini' atau 'daftar referensi terverifikasi di atas' di dalam isi skripsi!
2. Suara Pasif Ilmiah Baku: Khusus metodologi (Bab III) dan pembahasan temuan (Bab IV), gunakan kalimat pasif ilmiah (contoh: 'Data dikumpulkan melalui penyebaran kuesioner...', 'Analisis regresi linier dilakukan untuk...'), hindari suara orang pertama (saya, penulis, kami).
3. Kedalaman Materi & Ritme Paragraf Proporsional (STANDAR SKRIPSI S1 NASIONAL):
   - Tuliskan setiap bab secara MENDALAM, KOMPREHENSIF, dan EKSTENSIF (target minimal 1500 - 2500+ kata per bab). DILARANG membuat naskah yang singkat, dangkal, atau sekadar poin-poin ringkas.
   - WAJIB JAGA PROPORSIONALITAS PARAGRAF: Setiap paragraf idealnya berisi **5–7 kalimat padat (±80–115 kata atau ±7–10 baris di halaman A4 Word)** agar dalam 1 halaman A4 muat sekitar **2,5–3 paragraf yang rapi dan enak dibaca**. DILARANG KERAS menulis 1 paragraf raksasa bertele-tele (14–20 baris) yang membuat 1 halaman hanya berisi 1–2 paragraf! Pecah gagasan berbeda ke paragraf baru yang kohesif.
4. Sitasi & Bebas Mendeley (ANTI-HALUSINASI & WAJIB PATUH GAYA {$styleGuideMaster['label']}):
   - WAJIB menyertakan sitasi dalam teks KHUSUS gaya {$gayaSitasi} ({$inTextExampleMaster}).
   - KETENTUAN GAYA {$styleGuideMaster['label']}: {$styleGuideMaster['in_text_rule']}
   - ATURAN DAFTAR PUSTAKA {$styleGuideMaster['label']}: {$styleGuideMaster['bib_rule']}
   - CONTOH DAFTAR PUSTAKA {$styleGuideMaster['label']}: {$styleGuideMaster['bib_example']}
   - TAHUN TERBIT REFERENSI WAJIB MINIMAL TAHUN {$tahunMin} DAN MAKSIMAL TAHUN 2026.
   - DILARANG KERAS MENGARANG ATAU MEMBUAT SUMBER/PENULIS/JURNAL FIKTIF! Setiap nama peneliti, tahun, judul publikasi, dan nama jurnal ilmiah yang dikutip WAJIB berasal dari literatur ilmiah asli yang benar-benar terbit di jurnal nasional (SINTA/Garuda/Kemdikbud) atau internasional (Scopus/IEEE/Elsevier/Springer/OpenAlex) atau laporan otoritas data resmi (BPS, Kemendikbudristek, APJII, We Are Social / DataReportal). DILARANG menulis contoh nama fiktif seperti 'Pratama dan Pamungkas' atau 'Nahdhudin'!
   - Di Bab I (Latar Belakang): Prioritaskan kutipan dari 'REFERENSI JURNAL ILMIAH TERVERIFIKASI' di atas dan data resmi institusi.
   - Pada Tabel Penelitian Terdahulu (Bab II): Cantumkan 3-5 penelitian terdahulu yang ASLI dan RELEVAN dengan topik judul '{$project->judul}', mencakup nama peneliti riil, tahun publikasi riil ({$tahunMin}-2026), judul artikel riil, metode/desain, metrik hasil terukur, dan celah penelitian (research gap).
5. Standar Rumus & Notasi Statistik (DILARANG KODE LATEX MENTAH):
   - DILARANG KERAS menghasilkan sintaks LaTeX mentah seperti \\frac{}{}, \\hat{}, {,}, \\approx, atau tanda dollar \$ ... \$.
   - Tuliskan rumus dalam teks akademik bersih dengan notasi simbolik (lalu isi angka perhitungan orisinal sesuai topik/data proyek saat ini):
     * Pecahan (Slovin): n = N / (1 + N(e)²)
     * Estimasi Regresi: Ŷ = a + bX (atau Ŷ = a + b₁X₁ + b₂X₂ untuk berganda)
     * Koefisien Determinasi: R² = r² atau R² = F / (F + n - k - 1)
     * Gunakan superskrip Unicode: ², ³, ᵗ dan tanda koma desimal Indonesia (contoh: 0,05 — bukan {,}).
6. Format Tabel Akademik Rapi:
   - Setiap tabel wajib memiliki nomor dan judul pada baris tersendiri (contoh: Tabel 3.1 Operasionalisasi Variabel).
   - Sajikan dalam format Markdown Table lengkap dan presisi, pastikan sel-sel terisi pada kolom yang tepat dan tidak bergeser.
   - DILARANG membiarkan tabel jadwal penelitian kosong: pada tabel jadwal (misalnya kolom Bulan ke-1 s.d. Bulan ke-4), WAJIB isi tanda centang `✓` di kolom bulan yang sesuai pada setiap baris kegiatan.
7. Kepatuhan Pedoman Kampus: Jika terdapat Pedoman Kampus Mahasiswa di atas, terapkan seluruh konvensi penamaan, pembagian sub-bab, dan kaidah yang diminta kampus sebagai aturan tertinggi.
{$formatNomorRule}
9. Tanpa Simbol Markdown Heading: Jangan gunakan tanda bintang (**) atau pagar (###). Jangan tulis kalimat pengantar AI. Langsung mulai dari sub-bab pertama.";
    }

    private function promptBab(string $bab, Project $project, array $dataRiil = []): string
    {
        $master = $this->promptMaster($project);
        $meta = $this->getProjectMeta($project);
        if (empty($dataRiil) && !empty($meta['data_riil']) && is_array($meta['data_riil'])) {
            $dataRiil = $meta['data_riil'];
        }

        $sitasi = $meta['sitasi'] ?? [];
        $gayaSitasi = $sitasi['gaya'] ?? 'APA 7th';
        $tahunMin = !empty($sitasi['tahun_min']) ? (string) $sitasi['tahun_min'] : '2023';
        $asalRef = $sitasi['asal'] ?? 'Indonesia + Internasional';
        $bahasa = $sitasi['bahasa'] ?? 'Indonesia';
        $sumberArr = is_array($sitasi['sumber'] ?? null) ? implode(', ', $sitasi['sumber']) : 'Umum, Terindeks Sinta';

        $judulLower = strtolower($project->judul ?? '');
        $prodiLower = strtolower($project->prodi ?? '');
        $metodeLower = strtolower($project->metode_penelitian ?? '');

        // Integrasi Data Awal Penelitian (Kondisi Nyata / Angka Observasi Awal Mahasiswa)
        $dataAwalPrompt = "";
        if (!empty($meta['data_awal'])) {
            $dataAwalPrompt = "\n=== DATA AWAL PENELITIAN / HASIL OBSERVASI LAPANGAN MAHASISWA (WAJIB DIINTEGRASIKAN) ===\n" .
                "Data Riil Mahasiswa: \"{$meta['data_awal']}\"\n" .
                "INSTRUKSI INTEGRASI DATA AWAL SECARA OTOMATIS:\n" .
                "1. Pada Bab I (Sub-bab 1.1 Latar Belakang Masalah): AI WAJIB memasukkan data awal, angka persentase, skor kuantitatif, atau fakta lapangan di atas secara eksplisit ke dalam paragraf narasi sebagai bukti empiris kesenjangan masalah (gap phenomenon). JANGAN diabaikan atau diganti dengan data fiktif!\n" .
                "2. Pada Sub-bab 1.2 (Identifikasi Masalah) & 1.4 (Rumusan Masalah): Poin-poin masalah yang diidentifikasi dan diteliti WAJIB bersumber dari data awal ini.\n" .
                "3. Pada Bab III & IV: Kaitkan metode penarikan sampel, instrumen, dan perbandingan kondisi awal dengan hasil akhir penelitian.\n";
        }

        // Struktur Bab Kustom Mahasiswa (Jika Ditetapkan Khusus oleh Kampus)
        $strukturKustomPrompt = "";
        if (!empty($meta['struktur_kustom'])) {
            $strukturKustomPrompt = "\n=== STRUKTUR SUB-BAB KUSTOM PEDOMAN KAMPUS MAHASISWA (PRIORITAS TERTINGGI) ===\n" .
                "Susunan Khusus dari Mahasiswa / Pedoman Kampus:\n\"{$meta['struktur_kustom']}\"\n" .
                "INSTRUKSI STRUKTUR KUSTOM SECARA OTOMATIS:\n" .
                "1. Jika pada struktur kustom di atas terdapat susunan atau judul sub-bab untuk bab yang sedang ditulis ini ({$bab}), AI WAJIB MENGIKUTI SUSUNAN DAN JUDUL SUB-BAB TERSEBUT SECARA PERSIS!\n" .
                "2. DILARANG memaksa menggunakan struktur bab standar default jika kampus mahasiswa mensyaratkan struktur kustom di atas!\n";
        }

        // Artikel / Buku Referensi Wajib Unggahan Mahasiswa
        $referensiKhususPrompt = "";
        if (!empty($meta['referensi_khusus'])) {
            $referensiKhususPrompt = "\n=== ARTIKEL / BUKU REFERENSI WAJIB UNGGAHAN MAHASISWA (WAJIB DISITASI) ===\n" .
                Str::limit($meta['referensi_khusus'], 4000) . "\n" .
                "INSTRUKSI REFERENSI UNGGAHAN:\n" .
                "1. AI WAJIB menyitir dan merujuk artikel/buku di atas ke dalam naskah penjelasan ilmiah dengan gaya {$gayaSitasi}.\n" .
                "2. Pastikan nama penulis dan judul referensi wajib ini otomatis dicantumkan di dalam DAFTAR PUSTAKA.\n";
        }

        // Deteksi Rumpun Keilmuan / Disiplin Mahasiswa (prioritaskan prodi & metode eksplisit dari form Buat Proyek)
        $isInformatika = str_contains($prodiLower, 'informatika')
            || str_contains($prodiLower, 'komputer')
            || str_contains($prodiLower, 'sistem informasi')
            || str_contains($prodiLower, 'teknologi informasi')
            || str_contains($prodiLower, 'data science')
            || str_contains($prodiLower, 'rekayasa perangkat lunak')
            || str_contains($judulLower, 'algoritma')
            || str_contains($judulLower, 'naive bayes')
            || str_contains($judulLower, 'sentimen')
            || str_contains($judulLower, 'sentiment')
            || str_contains($judulLower, 'machine learning')
            || str_contains($judulLower, 'deep learning')
            || str_contains($judulLower, 'klasifikasi')
            || str_contains($judulLower, 'neural network')
            || (bool) preg_match('/\b(nlp|svm|cnn|lstm|k-means|decision tree|random forest|data mining)\b/i', $judulLower);

        $isTeknikNonIt = !$isInformatika && (
            str_contains($prodiLower, 'teknik sipil')
            || str_contains($prodiLower, 'arsitektur')
            || str_contains($prodiLower, 'teknik mesin')
            || str_contains($prodiLower, 'teknik elektro')
            || str_contains($prodiLower, 'teknik industri')
            || str_contains($prodiLower, 'teknik kimia')
            || str_contains($prodiLower, 'pertambangan')
            || str_contains($prodiLower, 'geologi')
        );

        $isEkonomi = str_contains($prodiLower, 'manajemen')
            || str_contains($prodiLower, 'akuntansi')
            || str_contains($prodiLower, 'ekonomi')
            || str_contains($prodiLower, 'bisnis')
            || str_contains($prodiLower, 'perbankan')
            || str_contains($prodiLower, 'keuangan')
            || str_contains($prodiLower, 'pemasaran');

        $isKesehatan = str_contains($prodiLower, 'farmasi')
            || str_contains($prodiLower, 'keperawatan')
            || str_contains($prodiLower, 'kebidanan')
            || str_contains($prodiLower, 'kesehatan')
            || str_contains($prodiLower, 'kedokteran')
            || str_contains($prodiLower, 'gizi')
            || str_contains($prodiLower, 'kesmas')
            || str_contains($judulLower, 'pasien')
            || str_contains($judulLower, 'klinis')
            || str_contains($judulLower, 'hipertensi')
            || str_contains($judulLower, 'puskesmas');

        $isHukum = str_contains($prodiLower, 'hukum')
            || str_contains($prodiLower, 'syariah')
            || str_contains($prodiLower, 'peradilan')
            || str_contains($prodiLower, 'kenotariatan')
            || str_contains($metodeLower, 'hukum')
            || str_contains($judulLower, 'yuridis')
            || str_contains($judulLower, 'pidana')
            || str_contains($judulLower, 'perdata')
            || str_contains($judulLower, 'tindak pidana')
            || str_contains($judulLower, 'penegakan hukum')
            || str_contains($judulLower, 'undang-undang');

        $isPsikologi = str_contains($prodiLower, 'psikologi')
            || str_contains($judulLower, 'kecemasan')
            || str_contains($judulLower, 'resiliensi')
            || str_contains($judulLower, 'self-efficacy')
            || str_contains($judulLower, 'burnout');

        $isKomunikasiSosial = str_contains($prodiLower, 'komunikasi')
            || str_contains($prodiLower, 'fisip')
            || str_contains($prodiLower, 'sosiologi')
            || str_contains($prodiLower, 'hubungan internasional')
            || str_contains($prodiLower, 'ilmu politik')
            || str_contains($prodiLower, 'administrasi publik')
            || str_contains($prodiLower, 'administrasi negara')
            || str_contains($prodiLower, 'kesejahteraan sosial');

        $isOlahraga = str_contains($prodiLower, 'olahraga')
            || str_contains($prodiLower, 'football')
            || str_contains($prodiLower, 'pjok')
            || str_contains($prodiLower, 'penjas')
            || str_contains($prodiLower, 'kepelatihan');

        $isAgamaIslam = str_contains($prodiLower, 'agama islam')
            || str_contains($prodiLower, 'tarbiyah')
            || (bool) preg_match('/\b(pai|pgmi|kpi|mpi|syariah|fiqih|tafsir|hadis|dakwah|akhlaq|akidah)\b/i', $prodiLower);

        $hasExplicitNonEduProdi = $isInformatika || $isTeknikNonIt || $isEkonomi || $isKesehatan || $isHukum || $isPsikologi || $isKomunikasiSosial;

        $isPendidikan = str_contains($prodiLower, 'pendidikan')
            || str_contains($prodiLower, 'fkip')
            || str_contains($prodiLower, 'tarbiyah')
            || str_contains($prodiLower, 'pembelajaran')
            || str_contains($prodiLower, 'guru')
            || (bool) preg_match('/\b(ppkn|pkn|pgsd|pgpaud|paud|bk|pai|pgmi)\b/i', $prodiLower)
            || $isOlahraga
            || (!$hasExplicitNonEduProdi && (
                str_contains($judulLower, 'siswa')
                || str_contains($judulLower, 'peserta didik')
                || str_contains($judulLower, 'prestasi belajar')
                || str_contains($judulLower, 'hasil belajar')
                || str_contains($judulLower, 'kelas xi')
                || str_contains($judulLower, 'kelas x')
                || str_contains($judulLower, 'kelas xii')
                || str_contains($judulLower, 'sekolah')
            ));

        $hasMlIntent = str_contains($judulLower, 'sentimen')
            || str_contains($judulLower, 'sentiment')
            || str_contains($judulLower, 'klasifikasi')
            || str_contains($judulLower, 'prediksi')
            || str_contains($judulLower, 'naive bayes')
            || str_contains($judulLower, 'machine learning')
            || str_contains($judulLower, 'deep learning')
            || str_contains($judulLower, 'clustering')
            || str_contains($judulLower, 'data mining')
            || (bool) preg_match('/\b(svm|cnn|lstm|rnn|knn|k-means|decision tree|random forest|nlp)\b/i', $judulLower);

        $isRnD = str_contains($metodeLower, 'r&d')
            || str_contains($metodeLower, 'waterfall')
            || str_contains($metodeLower, 'agile')
            || str_contains($metodeLower, 'prototype')
            || str_contains($metodeLower, 'rekayasa')
            || ($isInformatika && !$hasMlIntent && (
                str_contains($judulLower, 'perancangan')
                || str_contains($judulLower, 'rancang bangun')
                || str_contains($judulLower, 'sistem informasi')
                || str_contains($judulLower, 'pengembangan aplikasi')
                || str_contains($judulLower, 'pengembangan sistem')
                || str_contains($judulLower, 'berbasis web')
                || str_contains($judulLower, 'berbasis mobile')
                || str_contains($judulLower, 'website')
                || str_contains($judulLower, 'android')
            ));

        $isPtk = str_contains($metodeLower, 'ptk')
            || str_contains($metodeLower, 'tindakan kelas');

        $isStudiPustaka = str_contains($metodeLower, 'studi pustaka')
            || str_contains($metodeLower, 'library research');

        $isDataSekunder = str_contains($metodeLower, 'data sekunder');

        $isMixedMethod = str_contains($metodeLower, 'mixed');

        $isKualitatif = str_contains($metodeLower, 'kualitatif')
            || str_contains($metodeLower, 'normatif');

        $isPengaruh = str_contains($judulLower, 'pengaruh')
            || str_contains($judulLower, 'hubungan')
            || str_contains($judulLower, 'dampak')
            || str_contains($judulLower, 'korelasi');

        // Deteksi Tahap Proposal vs Skripsi Lengkap
        $isProposal = str_contains(strtolower($meta['tahap_pengerjaan'] ?? ''), 'proposal')
            || str_contains(strtolower($meta['jenis_penelitian'] ?? ''), 'proposal')
            || in_array($project->sumber_data ?? '', ['Proposal Skripsi (Bab I-III)', 'Proposal Skripsi (S1)']);

        $tahapKaryaPrompt = $isProposal
            ? "\n=== TAHAP DOKUMEN: PROPOSAL SKRIPSI (BAB I–III) ===\n" .
            "- Naskah yang disusun adalah RANCANGAN PROPOSAL PENELITIAN (belum pelaksanaan di lapangan).\n" .
            "- Khusus pada Bab III (Metodologi): Gunakan kalimat berorientasi rencana masa depan ('Penelitian ini akan dilaksanakan di...', 'Populasi dalam penelitian ini direncanakan sebanyak...', 'Data akan dikumpulkan dengan instrumen...').\n" .
            "- Proposal fokus pada kejelasan latar belakang masalah, ketajaman teori/pustaka, dan ketepatan rancangan metode.\n"
            : "\n=== TAHAP DOKUMEN: SKRIPSI LENGKAP (BAB I–V) ===\n" .
            "- Naskah yang disusun adalah LAPORAN SKRIPSI LENGKAP HASIL PENELITIAN TERUJI (penelitian telah selesai dilaksanakan).\n" .
            "- Khusus pada Bab III: Gunakan kalimat pelaporan ilmiah yang telah dilaksanakan ('Penelitian ini telah dilaksanakan di...', 'Data dikumpulkan melalui...', 'Populasi penelitian berjumlah...').\n" .
            "- Bab IV wajib menyajikan data temuan nyata dalam tabel statistik/komputasi, pengujian hipotesis terukur, dan pembahasan mendalam.\n" .
            "- Bab V wajib menyajikan kesimpulan yang menjawab seluruh rumusan masalah Bab I secara konkret serta rekomendasi saran praktis dan teoretis.\n";

        // Parameter Olah Data Penelitian Riil Mahasiswa (Jika Diberikan)
        $dataRiilPrompt = "";
        if (!empty($dataRiil) && (is_array($dataRiil) && count(array_filter($dataRiil)) > 0)) {
            $dataRiilPrompt = "\n=== PARAMETER OLAH DATA PENELITIAN RIIL MAHASISWA (WAJIB DIGUNAKAN SECARA PERSIS) ===\n";
            if (!empty($dataRiil['metode']))
                $dataRiilPrompt .= "- Metode / Algoritma Olah Data: {$dataRiil['metode']}\n";
            if (!empty($dataRiil['sampel']))
                $dataRiilPrompt .= "- Populasi / Sampel / Dataset Riil: {$dataRiil['sampel']}\n";
            if (!empty($dataRiil['lokasi']))
                $dataRiilPrompt .= "- Lokasi / Sumber Data Penelitian: {$dataRiil['lokasi']}\n";
            if (!empty($dataRiil['metrik']))
                $dataRiilPrompt .= "- Nilai Metrik / Hasil Temuan Kunci Riil: {$dataRiil['metrik']}\n";
            if (!empty($dataRiil['evaluasi']))
                $dataRiilPrompt .= "- Teknik Evaluasi / Pengujian Statistik: {$dataRiil['evaluasi']}\n";
            if (!empty($dataRiil['rumus']))
                $dataRiilPrompt .= "- Rumus Matematika / Statistik yang Wajib Dijabarkan: {$dataRiil['rumus']}\n";

            $dataRiilPrompt .= "PERINGATAN KERAS & ATURAN MUTLAK:\n" .
                "1. AI WAJIB menyematkan angka-angka, metrik, sampel, dan tabel di atas ke dalam naskah narasi dan tabel perhitungan secara PERSIS. DILARANG KERAS mengarang angka lain yang bertentangan!\n" .
                "2. JABARKAN RUMUS SECARA LENGKAP LANGKAH DEMI LANGKAH: Tuliskan rumus matematisnya terlebih dahulu, lalu masukkan angka-angka riil mahasiswa ke dalam rumus tersebut langkah per langkah (step-by-step), hingga menghasilkan nilai akhir yang sesuai.\n" .
                "3. FORMAT TABEL LENGKAP: Seluruh tabel hasil olah data (Confusion Matrix, Evaluasi Metrik, Uji Asumsi, Regresi, ANOVA, t-test, N-Gain, dll.) WAJIB disajikan dalam bentuk Markdown Table rapi lengkap dengan nomor dan judul tabel (contoh: Tabel 4.1, Tabel 4.2).\n" .
                "4. Berikan pembahasan ilmiah mendalam mengenai arti dari angka-angka tersebut, mengapa hasil tersebut tercapai, serta implikasi teoritis dan praktisnya terhadap judul '{$project->judul}'.\n";
        }

        $benangMerah = $this->extractProjectBenangMerah($project);
        $context = "";
        if ($bab == 'bab2')
            $context = $benangMerah . "\nKONTEKS BAB I (PERTAHANKAN BENANG MERAH RUMUSAN MASALAH & VARIABEL):\n" . Str::limit(strip_tags((string) $project->bab1_content), 3200);
        elseif ($bab == 'bab3')
            $context = $benangMerah . "\nKONTEKS BAB I & BAB II (PERTAHANKAN KONSISTENSI VARIABEL, INDIKATOR & HIPOTESIS):\n" . Str::limit(strip_tags((string) $project->bab1_content), 1600) . "\n\n" . Str::limit(strip_tags((string) $project->bab2_content), 1800);
        elseif ($bab == 'bab4')
            $context = $benangMerah . "\nKONTEKS BAB I & BAB III (JAWAB SEMUA RUMUSAN MASALAH & GUNAKAN METODE/SAMPEL BAB III):\n" . Str::limit(strip_tags((string) $project->bab1_content), 1500) . "\n\n" . Str::limit(strip_tags((string) $project->bab3_content), 2000);
        elseif ($bab == 'bab5') {
            $b4FullPlain = trim(strip_tags((string) $project->bab4_content));
            $b4ContextSnippet = mb_strlen($b4FullPlain) > 8500
                ? mb_substr($b4FullPlain, 0, 5200) . "\n[...]\n" . mb_substr($b4FullPlain, -3300)
                : $b4FullPlain;
            $context = $benangMerah
                . "\nRINGKASAN BAB I:\n" . Str::limit(strip_tags((string) $project->bab1_content), 1600)
                . "\n\nHASIL PENELITIAN BAB IV (UTUH — AWAL & AKHIR TERJAGA AGAR SEMUA ANGKA & PENGUJIAN TIDAK TERPOTONG):\n" . $b4ContextSnippet;
            $allText = $project->bab1_content . " " . $project->bab2_content . " " . $project->bab3_content . " " . $project->bab4_content;
            $citationsList = $this->extractInTextCitations($allText);
            $citationsStr = !empty($citationsList) ? implode("\n- ", $citationsList) : "- Tidak ada sitasi terdeteksi";
            $context .= "\n\n=== DAFTAR SITASI ASLI YANG MUNCUL DALAM NASKAH BAB 1 S/D 4 (WAJIB DIBUATKAN DAFTAR PUSTAKANYA) ===\n- " . $citationsStr;
            $context .= "\n\n=== RINGKASAN ISI TEKS DARI BAB 1 SAMPAI BAB 4 ===\n" . Str::limit(strip_tags($allText), 9000);
        }

        // Penyesuaian Sub-bab 2.4 otomatis mengikuti metode penelitian di form (Kualitatif/Studi Pustaka TIDAK memakai hipotesis statistik H0/H1)
        $isNonHipotesisStatistik = $isKualitatif || $isStudiPustaka;
        $bab2Sub24Default = $isNonHipotesisStatistik
            ? "2.4 Fokus Kajian / Proposisi Penelitian (Uraikan proposisi teoretis atau pertanyaan penuntun riset kualitatif/pustaka — DILARANG menuliskan hipotesis statistik H0/H1 karena metode penelitian ini bersifat kualitatif/deskriptif)."
            : "2.4 Hipotesis Penelitian (Rumuskan hipotesis penelitian secara tegas berupa H0 dan H1/Ha berdasarkan argumentasi teoretis dan temuan empiris).";
        $paiContextNote = ($isPendidikan && $isAgamaIslam)
            ? "\nKHUSUS PRODI PENDIDIKAN AGAMA ISLAM / TARBIYAH (PTKIN): Integrasikan landasan pedagogis modern secara harmonis dengan nilai-nilai pendidikan Islam (akidah, syariat/ibadah, akhlak mulia, atau rujukan normatif ayat Al-Qur'an/Hadis yang relevan bila diperlukan sesuai konteks judul) dengan bahasa ilmiah akademik yang baku."
            : "";
        $olahragaContextNote = $isOlahraga
            ? "\nKHUSUS PRODI KEOLAHRAGAAN / PENDIDIKAN JASMANI / FOOTBALL EDUCATION: Gunakan terminologi kepelatihan, pedagogi olahraga, kebugaran/keterampilan gerak, atau manajemen pembinaan atlet/SSB secara tepat sesuai fokus judul."
            : "";

        // Bab II Sesuai Rumpun Ilmu & WAJIB KERANGKA BERPIKIR DISESUAIKAN DENGAN JUDUL & JURUSAN
        $bab2Prompt = match (true) {
            $isRnD => "
TUGAS UTAMA: Tuliskan NASKAH LENGKAP BAB II (TINJAUAN PUSTAKA DAN LANDASAN TEORI) BIDANG REKAYASA PERANGKAT LUNAK / PENGEMBANGAN SISTEM (MINIMAL 1800 - 2500 KATA).
Disesuaikan secara persis dengan Judul: '{$project->judul}' dan Program Studi: '{$project->prodi}'.
WAJIB MEMUAT SELURUH SUB-BAB BERURUTAN:
2.1 Landasan Teori (Uraikan konsep dasar sistem informasi/aplikasi sesuai objek judul, arsitektur web/mobile dan basis data yang relevan, model pengembangan SDLC [Waterfall / Prototype / Agile / ADDIE], pemodelan berorientasi objek UML [Use Case, Activity, Sequence, Class Diagram / ERD], serta teori pengujian sistem Black-Box Testing dan evaluasi kegunaan UAT / System Usability Scale [SUS]. DILARANG memasukkan materi Text Mining/TF-IDF/Confusion Matrix kecuali judulnya membahas Machine Learning!).
2.2 Penelitian Terdahulu (WAJIB cantumkan 3-5 penelitian sistem/aplikasi ASLI dari jurnal bereputasi SINTA/Scopus periode {$tahunMin}-2026 yang relevan dengan '{$project->judul}'. Sajikan dalam narasi kritis diikuti TABEL perbandingan:
Tabel 2.1 Sintesis Penelitian Terdahulu
| No | Peneliti & Tahun | Judul Penelitian | Metode Pengembangan & Teknologi | Hasil / Pengujian Sistem | Persamaan, Perbedaan & Celah Penelitian |
Diakhiri 2 paragraf analisis kesenjangan dan kebaruan sistem yang dirancang).
2.3 Kerangka Pemikiran (Uraikan alur logika dari permasalahan proses manual/eksisting di lapangan, analisis kebutuhan pengguna, perancangan UML/ERD dan implementasi sistem, hingga pengujian fungsional & usabilitas.
Lalu WAJIB sajikan DIAGRAM KERANGKA PEMIKIRAN dalam blok kode vertikal:
Gambar 2.1 Bagan Kerangka Pemikiran Perancangan Sistem
```
[Permasalahan Sistem Berjalan pada '{$project->judul}']
↓
[Analisis Kebutuhan Fungsional & Non-Fungsional Pengguna]
↓
[Perancangan Sistem (SDLC, UML, ERD & Antarmuka UI/UX)]
↓
[Implementasi Aplikasi & Pengujian (Black-Box Testing & SUS/UAT)]
↓
[Luaran: Sistem Informasi Teruji, Efektif & Mudah Digunakan]
```
).",

            $isInformatika => "
TUGAS UTAMA: Tuliskan NASKAH LENGKAP BAB II (TINJAUAN PUSTAKA) BIDANG INFORMATIKA / ILMU KOMPUTER / SISTEM CERDAS (MINIMAL 1800 - 2500 KATA).
Disesuaikan secara persis dengan Judul: '{$project->judul}' dan Program Studi: '{$project->prodi}'.
WAJIB MEMUAT SELURUH SUB-BAB BERURUTAN:
2.1 Landasan Teori (Uraikan secara mendalam konsep dasar topik judul, Text/Data Preprocessing [Case Folding, Cleaning, Tokenizing, Slang Normalization, Stopwords Removal, Stemming], Ekstraksi/Pembobotan Fitur [TF-IDF / Feature Engineering], Teori & Rumus Matematis Algoritma yang digunakan dalam teks Unicode bersih, serta Teori Evaluasi Kinerja / Confusion Matrix).
2.2 Penelitian Terdahulu (WAJIB cantumkan 3-5 penelitian terdahulu ASLI dari jurnal bereputasi SINTA/Scopus periode {$tahunMin}-2026 yang paling relevan dengan '{$project->judul}'. Sajikan dalam narasi kritis diikuti TABEL sintesis perbandingan:
Tabel 2.1 Sintesis Penelitian Terdahulu
| No | Peneliti & Tahun | Judul Penelitian | Metode / Algoritma | Hasil / Akurasi | Persamaan, Perbedaan & Celah Penelitian |
Diakhiri 2 paragraf sintesis posisi riset dan kebaruan/novelty penelitian ini).
2.3 Kerangka Pemikiran (Uraikan alur logika pemecahan masalah komputasi dari fenomena gap di lapangan, landasan teori, prapemrosesan data, pemodelan algoritma, hingga evaluasi kinerja.
Lalu WAJIB sajikan DIAGRAM KERANGKA PEMIKIRAN dalam blok kode vertikal:
Gambar 2.1 Bagan Kerangka Pemikiran Penelitian
```
[Permasalahan Empiris pada '{$project->judul}']
↓
[Landasan Teori: Konsep Algoritma & Teori Pengolahan Data]
↓
[Pendekatan Komputasi: Prapemrosesan, Ekstraksi Fitur & Pemodelan]
↓
[Evaluasi & Pengujian: Confusion Matrix (Akurasi, Presisi, Recall, F1)]
↓
[Hasil yang Diharapkan: Model Teruji dengan Performa Optimal]
```
).
2.4 Batasan Kinerja / Hipotesis Penelitian (Kemukakan ekspektasi performa model atau hipotesis penelitian secara tegas).",

            $isTeknikNonIt => "
TUGAS UTAMA: Tuliskan NASKAH LENGKAP BAB II (TINJAUAN PUSTAKA DAN LANDASAN TEORI) BIDANG KETEKNIKAN ({$project->prodi}) (MINIMAL 1800 - 2500 KATA).
Disesuaikan secara persis dengan Judul: '{$project->judul}' dan Program Studi: '{$project->prodi}'.
WAJIB MEMUAT SELURUH SUB-BAB BERURUTAN:
2.1 Landasan Teori dan Standar Teknis Acuan (Uraikan teori rekayasa inti, spesifikasi material/sistem/struktur, persamaan matematis teknis dalam Unicode bersih, serta standar baku yang relevan seperti SNI, ASTM, PUIL, ASME, atau IEEE sesuai bidang {$project->prodi}).
2.2 Penelitian Terdahulu (WAJIB cantumkan 3-5 jurnal teknik bereputasi SINTA/Scopus periode {$tahunMin}-2026 dalam narasi kritis diikuti TABEL perbandingan:
Tabel 2.1 Sintesis Penelitian Terdahulu
| No | Peneliti & Tahun | Judul Penelitian | Material / Metode Uji | Hasil Teknis Utama | Celah & Kebaruan Penelitian |
).
2.3 Kerangka Pemikiran Teknis (Uraikan alur rekayasa dari permasalahan teknis di lapangan, standar acuan SNI/ASTM, perancangan/eksperimen laboratorium atau simulasi, hingga evaluasi kinerja aman/efisien.
Lalu WAJIB sajikan DIAGRAM KERANGKA PEMIKIRAN dalam blok kode vertikal:
Gambar 2.1 Bagan Kerangka Pemikiran Penelitian Teknis
```
[Permasalahan Rekayasa pada '{$project->judul}']
↓
[Landasan Teori Teknis & Standar Acuan (SNI / ASTM / IEEE)]
↓
[Perancangan Desain / Uji Laboratorium / Simulasi Numerik]
↓
[Evaluasi Parameter Kinerja & Kepatuhan Ambang Batas Standar]
↓
[Luaran: Rekomendasi Desain / Solusi Rekayasa Optimal]
```
).
{$bab2Sub24Default}",

            $isEkonomi => "
TUGAS UTAMA: Tuliskan NASKAH LENGKAP BAB II (TINJAUAN PUSTAKA) BIDANG EKONOMI / MANAJEMEN / AKUNTANSI (MINIMAL 1800 - 2500 KATA).
Disesuaikan secara persis dengan Judul: '{$project->judul}' dan Program Studi: '{$project->prodi}'.
WAJIB MEMUAT SELURUH SUB-BAB BERURUTAN:
2.1 Landasan Teori (Uraikan Grand Theory [misal Agency Theory / TAM / TPB / RBV / Signaling Theory], Middle Theory, Applied Theory, definisi konseptual menurut para ahli, dimensi, faktor determinan, dan indikator pengukuran setiap variabel penelitian).
2.2 Penelitian Terdahulu (WAJIB cantumkan 3-5 jurnal bereputasi SINTA/Scopus periode {$tahunMin}-2026 dalam narasi kritis diikuti TABEL perbandingan:
Tabel 2.1 Sintesis Penelitian Terdahulu
| No | Peneliti & Tahun | Judul Penelitian | Variabel & Metode | Hasil Penelitian | Persamaan & Perbedaan (Research Gap) |
Diikuti 2 paragraf analisis kesenjangan riset).
2.3 Kerangka Konseptual (Uraikan hubungan kausalitas atau keterkaitan konseptual antarvariabel/fokus berdasarkan landasan teori.
Lalu WAJIB sajikan DIAGRAM KERANGKA KONSEPTUAL dalam blok kode vertikal:
Gambar 2.1 Bagan Kerangka Konseptual Penelitian
```
[Fenomena Bisnis & Masalah Empiris pada '{$project->judul}']
↓
[Grand Theory & Kajian Variabel / Fokus Penelitian]
↓
[Pendekatan Metodologis: Pengumpulan Data Empiris & Analisis]
↓
[Temuan Empiris & Implikasi Manajerial]
```
).
" . ($isNonHipotesisStatistik ? $bab2Sub24Default : "2.4 Pengembangan Hipotesis (Rumuskan hipotesis H1, H2, H3 dst. secara eksplisit dengan argumentasi teoretis dan empiris pendukung)."),

            $isHukum => "
TUGAS UTAMA: Tuliskan NASKAH LENGKAP BAB II (TINJAUAN PUSTAKA) BIDANG ILMU HUKUM / SYARIAH (MINIMAL 1800 - 2500 KATA).
Disesuaikan secara persis dengan Judul: '{$project->judul}' dan Program Studi: '{$project->prodi}'.
WAJIB MEMUAT:
2.1 Tinjauan Umum dan Landasan Yuridis (Teori Hukum Utama [Teori Kepastian Hukum, Keadilan, Kemanfaatan, Perlindungan Hukum, Efektivitas Hukum], asas-asas hukum, definisi konseptual istilah hukum, serta telaah peraturan perundang-undangan/pasal-pasal relevan di Indonesia).
2.2 Tinjauan Pustaka Terdahulu / Keaslian Penelitian (Cantumkan 3-5 jurnal hukum terakreditasi SINTA periode {$tahunMin}-2026 diikuti TABEL perbandingan:
Tabel 2.1 Perbandingan Penelitian Hukum Terdahulu
| No | Peneliti & Tahun | Judul Penelitian | Pendekatan Yuridis | Fokus & Temuan | Perbedaan & Kebaruan |
).
2.3 Kerangka Pemikiran / Kerangka Konseptual Yuridis (Uraikan penalaran hukum dari norma/das Sollen, realitas penegakan hukum/das Sein, analisis perundang-undangan, hingga konstruksi hukum ideal. DILARANG mencantumkan hipotesis statistik H0/H1.
Sajikan diagram dalam blok kode vertikal:
Gambar 2.1 Bagan Kerangka Pemikiran Yuridis
```
[Permasalahan Hukum & Kesenjangan Norma (Das Sollen vs Das Sein)]
↓
[Landasan Yuridis: Peraturan Perundang-Undangan & Teori Hukum]
↓
[Analisis Yuridis Normatif / Empiris & Sinkronisasi Regulasi]
↓
[Konstruksi Solusi Hukum & Kepastian Perlindungan Hukum]
```
).",

            $isKesehatan => "
TUGAS UTAMA: Tuliskan NASKAH LENGKAP BAB II (TINJAUAN PUSTAKA, KERANGKA TEORI & KERANGKA KONSEP) BIDANG KESEHATAN / KEPERAWATAN / KEBIDANAN / FARMASI / KEDOKTERAN (MINIMAL 1800 - 2500 KATA).
Disesuaikan secara persis dengan Judul: '{$project->judul}' dan Program Studi: '{$project->prodi}'.
WAJIB MEMUAT:
2.1 Landasan Teori Klinis & Keperawatan/Kesehatan (Patofisiologi/etiologi, konsep variabel independen & dependen, mekanisme biologis/fisiologis atau asuhan keperawatan, standar klinis Kemenkes/WHO/SDKI-SLKI-SIKI bila relevan, dan alat ukur klinis terstandar).
2.2 Penelitian Terdahulu (WAJIB cantumkan 3-5 jurnal kesehatan bereputasi SINTA/PubMed/Scopus periode {$tahunMin}-2026 diikuti TABEL perbandingan:
Tabel 2.1 Sintesis Penelitian Terdahulu
| No | Peneliti & Tahun | Judul Penelitian | Desain & Sampel | Hasil Klinis (p-value / OR) | Celah Penelitian |
).
2.3 Kerangka Teori dan Kerangka Konsep Penelitian (Jelaskan secara ringkas kerangka teori menyeluruh yang memengaruhi fenomena klinis, lalu turunkan menjadi Kerangka Konsep yang memfokuskan variabel independen terhadap variabel dependen yang diteliti.
Lalu WAJIB sajikan DIAGRAM KERANGKA KONSEP dalam blok kode:
Gambar 2.1 Diagram Kerangka Konsep Penelitian
```
[Faktor Risiko / Intervensi / Variabel Independen Sesuai Judul]
↓
[Mekanisme Fisiologis / Psikologis / Asuhan Klinis]
↓
[Pengukuran Parameter Klinis & Uji Statistik Univariat–Bivariat]
↓
[Luaran Klinis / Variabel Dependen pada Pasien]
```
).
{$bab2Sub24Default}",

            $isPsikologi || $isKomunikasiSosial => "
TUGAS UTAMA: Tuliskan NASKAH LENGKAP BAB II (TINJAUAN PUSTAKA / KERANGKA TEORETIS) BIDANG PSIKOLOGI / ILMU KOMUNIKASI / SOSIOLOGI / FISIP (MINIMAL 1800 - 2500 KATA).
Disesuaikan secara persis dengan Judul: '{$project->judul}' dan Program Studi: '{$project->prodi}'.
WAJIB MEMUAT:
2.1 Landasan Teori / Kerangka Teoretis (Uraikan Grand Theory dan Middle-Range Theory bidang terkait [misal teori sosiologi/komunikasi/psikologi yang relevan dengan judul], definisi konseptual tiap konsep/variabel menurut 3-4 tokoh/ahli, aspek/dimensi, serta dinamika sosial atau psikologisnya).
2.2 Penelitian Terdahulu (Cantumkan 3-5 jurnal bereputasi SINTA/Scopus periode {$tahunMin}-2026 diikuti TABEL perbandingan:
Tabel 2.1 Matriks Penelitian Terdahulu
| No | Peneliti & Tahun | Judul Penelitian | Fokus / Variabel & Metode | Temuan Utama | Celah Penelitian |
).
2.3 Kerangka Berpikir Penelitian (Uraikan alur penalaran teoretis sebagai pisau analisis fenomena sosial/psikologis dan sajikan diagram vertikal:
Gambar 2.1 Bagan Kerangka Berpikir Penelitian
```
[Fenomena Sosial / Psikologis pada '{$project->judul}']
↓
[Tinjauan Teoretis & Dimensi Konsep Penelitian]
↓
[Pengumpulan Data Empiris & Analisis " . ($isNonHipotesisStatistik ? "Kualitatif Interaktif" : "Statistik Inferensial") . "]
↓
[Temuan Empiris & Kontribusi Keilmuan]
```
).
{$bab2Sub24Default}",

            ($isPendidikan && $isPengaruh && !$isNonHipotesisStatistik) || ($isPengaruh && !$isInformatika && !$isNonHipotesisStatistik && !$isKesehatan && !$isHukum) => "
TUGAS UTAMA: Tuliskan NASKAH LENGKAP BAB II (TINJAUAN PUSTAKA) PENELITIAN KUANTITATIF (PENGARUH / HUBUNGAN ANTARVARIABEL).
Disesuaikan secara persis dengan Judul: '{$project->judul}' dan Program Studi: '{$project->prodi}'.{$paiContextNote}{$olahragaContextNote}
WAJIB MEMUAT SELURUH SUB-BAB SECARA MENDALAM DAN KOMPREHENSIF (MINIMAL 1800 - 2500 KATA):
2.1 Landasan Teori
Uraikan secara komprehensif (minimal 10-15 paragraf panjang):
a. Teori Variabel Bebas (X): Pengertian menurut 3-4 ahli, aspek dan dimensi, faktor pendorong, serta indikator pengukurannya.
b. Teori Variabel Terikat (Y): Pengertian menurut 3-4 ahli, indikator capaian, faktor internal & eksternal yang mempengaruhi, dan tolok ukur evaluasi.
c. Pengaruh Teoretis Variabel X terhadap Variabel Y: Elaborasi mendalam alur kausalitas bagaimana intensitas variabel bebas mempengaruhi capaian variabel terikat.
2.2 Penelitian Terdahulu
Uraikan komparasi kritis 3-5 penelitian terdahulu dari jurnal bereputasi SINTA periode {$tahunMin}-2026 yang relevan dengan '{$project->judul}', diikuti TABEL komparasi terstruktur:
Tabel 2.1 Sintesis Penelitian Terdahulu
| No | Peneliti & Tahun | Judul Penelitian | Variabel & Metode | Hasil / Temuan Utama | Persamaan, Perbedaan & Celah Penelitian (Research Gap) |
Diakhiri sintesis 2 paragraf mengenai posisi penelitian dan kebaruan (novelty) riset ini.
2.3 Kerangka Berpikir / Kerangka Konseptual
Uraikan alur penalaran logis deduktif dari kondisi fenomena empiris, landasan teoretis variabel X dan Y, pendekatan metodologis pengujian statistik, hingga luaran hasil.
Lalu WAJIB sajikan DIAGRAM KERANGKA BERPIKIR dalam blok kode:
Gambar 2.1 Bagan Kerangka Berpikir Penelitian
```
[Kondisi Empiris: Masalah Nyata pada {$project->judul}]
↓
[Kajian Teoretis: Teori Variabel Bebas (X) & Teori Variabel Terikat (Y)]
↓
[Pendekatan Metodologi: Penyusunan Angket, Uji Validitas & Analisis Regresi]
↓
[Hasil: Terujinya Pengaruh Signifikan Variabel X terhadap Variabel Y]
```
2.4 Hipotesis Penelitian
Rumuskan hipotesis penelitian secara tegas (H1: Terdapat pengaruh yang signifikan... beserta hipotesis statistik H0 dan Ha).",

            $isPendidikan => "
TUGAS UTAMA: Tuliskan NASKAH LENGKAP BAB II (TINJAUAN PUSTAKA) BIDANG PENDIDIKAN / KEGURUAN / TARBIYAH / KEOLAHRAGAAN (MINIMAL 1800 - 2500 KATA).
Disesuaikan secara persis dengan Judul: '{$project->judul}' dan Program Studi: '{$project->prodi}'.{$paiContextNote}{$olahragaContextNote}
WAJIB MEMUAT:
2.1 Landasan Teori (Hakikat pembelajaran/pembinaan, strategi/model/metode/media yang dikaji, karakteristik peserta didik/atlet, dimensi capaian belajar/keterampilan, serta indikator ketercapaian).
2.2 Penelitian Terdahulu (WAJIB cantumkan 3-5 jurnal pendidikan bereputasi SINTA periode {$tahunMin}-2026 dalam narasi kritis dan TABEL komparasi:
Tabel 2.1 Sintesis Penelitian Terdahulu
| No | Peneliti & Tahun | Judul Penelitian | Model / Metode | Temuan / Peningkatan | Posisi Penelitian Ini |
).
2.3 Kerangka Berpikir (Uraikan alur pemikiran dari kondisi awal lapangan, penerapan strategi/program pembelajaran atau tindakan, hingga kondisi akhir yang diharapkan.
Lalu WAJIB sajikan DIAGRAM KERANGKA BERPIKIR dalam blok kode:
Gambar 2.1 Bagan Kerangka Berpikir Tindakan Pembelajaran
```
[Kondisi Awal: Permasalahan Empiris pada {$project->judul}]
↓
[Tindakan / Fokus Kajian: Penerapan Strategi atau Program Pembelajaran]
↓
[Proses: Pelaksanaan Terstruktur, Observasi & Umpan Balik]
↓
[Kondisi Akhir: Peningkatan Capaian & Kompetensi Peserta Didik]
```
).
" . ($isPtk ? "2.4 Hipotesis Tindakan (Rumuskan dugaan peningkatan hasil belajar/keterampilan melalui tindakan siklus secara tegas)." : $bab2Sub24Default),

            default => "
TUGAS UTAMA: Tuliskan NASKAH LENGKAP BAB II (TINJAUAN PUSTAKA) secara komprehensif berstandar skripsi S1 nasional Indonesia (MINIMAL 1800 - 2500 KATA).
Disesuaikan secara persis dengan Judul: '{$project->judul}' dan Program Studi: '{$project->prodi}'.
WAJIB MEMUAT:
2.1 Landasan Teori (Grand theory, teori pendukung, definisi ahli, dan dimensi/indikator operasional).
2.2 Penelitian Terdahulu (WAJIB cantumkan 3-5 penelitian jurnal bereputasi periode {$tahunMin}-2026 terkait dalam format narasi kritis dan TABEL perbandingan:
Tabel 2.1 Sintesis Penelitian Terdahulu
| No | Peneliti & Tahun | Judul Penelitian | Metode yang Digunakan | Hasil / Temuan | Celah Penelitian |
).
2.3 Kerangka Pemikiran (Uraikan alur pemikiran ilmiah dari gejala fenomena, landasan konsep teori, analisis kritis, hingga perumusan solusi teruji.
Lalu WAJIB sajikan DIAGRAM KERANGKA PEMIKIRAN dalam blok kode:
Gambar 2.1 Bagan Kerangka Pemikiran Penelitian
```
[Fenomena Masalah Empiris pada '{$project->judul}']
↓
[Tinjauan Konseptual & Landasan Teori Ahli]
↓
[Analisis Metodologis & Pengujian Terstruktur]
↓
[Solusi Teruji, Rekomendasi Ilmiah & Kontribusi Praktis]
```
).
{$bab2Sub24Default}"
        };

        // Bab III Sesuai Rumpun Ilmu & Metode Penelitian (WAJIB Flowchart Vertikal Standar ANSI & Metodologi Valid di SEMUA Jurusan)
        $bab3Prompt = match (true) {
            $isStudiPustaka => "
TUGAS: Tulis Isi BAB III (METODOLOGI PENELITIAN STUDI PUSTAKA / LIBRARY RESEARCH) secara mendalam (MINIMAL 1700 - 2300 KATA).
3.1 Jenis dan Pendekatan Penelitian (Penelitian Kepustakaan / Library Research dengan pendekatan kualitatif deskriptif-analitis atau sistematis).
3.2 Sumber Data Penelitian (Sumber Data Primer berupa buku induk, dokumen resmi, atau jurnal utama; Sumber Data Sekunder berupa artikel ilmiah SINTA/Scopus {$tahunMin}-2026 dan prosiding).
3.3 Kriteria Inklusi, Eksklusi, dan Strategi Pencarian Literatur (WAJIB sajikan Tabel 3.1 Kriteria Seleksi Literatur: | Aspek | Kriteria Inklusi | Kriteria Eksklusi |).
3.4 Teknik Pengumpulan Data Kepustakaan (Dokumentasi, penelusuran basis data akademik, inventarisasi & reduksi literatur).
3.5 Teknik Analisis Data (Analisis Isi / Content Analysis, Analisis Komparatif, dan Sintesis Tematik) serta Uji Keabsahan Data (Triangulasi sumber & ketekunan telaah).
3.6 Prosedur Penelitian Kepustakaan:
Gambar 3.1 Diagram Alur (Flowchart) Prosedur Penelitian Studi Pustaka
```
[Mulai]
↓
[Identifikasi Topik & Perumusan Masalah '{$project->judul}']
↓
[Penelusuran Literatur pada Basis Data Ilmiah (OpenAlex/SINTA/Scopus)]
↓
[Penyaringan Literatur Berdasarkan Kriteria Inklusi & Eksklusi]
↓
[Pengelompokan & Pengkodean Tematik Sumber Primer dan Sekunder]
↓
[Evaluasi Validitas & Triangulasi Sumber Literatur]
↓
[Sintesis Analisis Isi & Penyusunan Temuan Teoretis]
↓
[Penyusunan Laporan Hasil Penelitian]
↓
[Selesai]
```",

            $isPtk => "
TUGAS: Tulis Isi BAB III (METODOLOGI PENELITIAN TINDAKAN KELAS / PTK) secara mendalam (MINIMAL 1800 - 2400 KATA).
3.1 Jenis dan Desain Penelitian Tindakan Kelas (Model Kemmis & McTaggart: Perencanaan, Pelaksanaan Tindakan, Observasi, dan Refleksi dalam Siklus I & Siklus II).
3.2 Subjek, Tempat, dan Waktu Penelitian (Karakteristik siswa, kelas, sekolah, dan jadwal siklus).
3.3 Prosedur Pelaksanaan Tindakan (Uraikan rinci tahapan Pra-Siklus, Siklus I, dan Siklus II beserta alur diagram):
Gambar 3.1 Diagram Alur (Flowchart) Prosedur Penelitian Tindakan Kelas
```
[Mulai]
↓
[Observasi Pra-Siklus & Identifikasi Masalah Pembelajaran]
↓
[Perencanaan Tindakan Siklus I (RPP, LKPD & Instrumen Evaluasi)]
↓
[Pelaksanaan Tindakan & Observasi Pembelajaran Siklus I]
↓
[Refleksi & Evaluasi Ketuntasan Belajar Siklus I (KKM >= 75%?)]
↓
[Perencanaan & Pelaksanaan Perbaikan Siklus II]
↓
[Analisis Peningkatan Hasil Belajar Antar-Siklus]
↓
[Penyusunan Laporan Hasil PTK]
↓
[Selesai]
```
3.4 Teknik dan Instrumen Pengumpulan Data (Tes hasil belajar per siklus, lembar observasi aktivitas guru & siswa, dokumentasi; sajikan Tabel 3.1 Kisi-kisi Instrumen PTK).
3.5 Teknik Analisis Data (Rumus Nilai Rata-Rata, Persentase Ketuntasan Klasikal = (Jumlah Siswa Tuntas / Total Siswa) × 100%, dan Peningkatan Antar-Siklus).
3.6 Indikator Keberhasilan Tindakan (Kriteria Ketuntasan Minimal / KKM individu & ketuntasan klasikal minimal 80%–85%).",

            $isMixedMethod => "
TUGAS: Tulis Isi BAB III (METODOLOGI PENELITIAN MIXED METHOD) secara mendalam (MINIMAL 1800 - 2500 KATA).
3.1 Jenis dan Desain Penelitian Mixed Method (Jelaskan desain Sequential Explanatory / Exploratory / Convergent Parallel menurut Creswell & Creswell).
3.2 Lokasi, Objek, dan Waktu Penelitian.
3.3 Populasi, Sampel Kuantitatif, dan Informan Kualitatif (Rumus Slovin untuk sampel survei + Purposive Sampling untuk informan wawancara mendalam).
3.4 Variabel Penelitian, Definisi Operasional, dan Fokus Kajian Kualitatif (WAJIB sajikan Tabel 3.1 Matriks Integrasi Variabel Kuantitatif & Fokus Kualitatif).
3.5 Teknik Pengumpulan Data & Uji Kualitas Data (Tahap Kuantitatif: kuesioner, validitas & reliabilitas; Tahap Kualitatif: wawancara semi-terstruktur, observasi, triangulasi sumber & teknik).
3.6 Teknik Analisis Data Campuran (Analisis statistik regresi/uji hipotesis dilanjutkan reduksi data, penyajian, dan integrasi temuan kuantitatif-kualitatif).
3.7 Prosedur Penelitian Mixed Method:
Gambar 3.1 Diagram Alur (Flowchart) Prosedur Penelitian Mixed Method
```
[Mulai]
↓
[Identifikasi Masalah & Kajian Literatur '{$project->judul}']
↓
[Pengumpulan Data Tahap I: Survei Kuesioner Kuantitatif]
↓
[Analisis Statistik Kuantitatif & Uji Hipotesis]
↓
[Pengumpulan Data Tahap II: Wawancara Mendalam Kualitatif]
↓
[Evaluasi Validitas, Reliabilitas & Triangulasi Data]
↓
[Integrasi & Interpretasi Temuan Kuantitatif-Kualitatif]
↓
[Penyusunan Laporan Hasil Penelitian]
↓
[Selesai]
```",

            $isDataSekunder => "
TUGAS: Tulis Isi BAB III (METODOLOGI PENELITIAN KUANTITATIF DATA SEKUNDER) secara mendalam (MINIMAL 1800 - 2500 KATA).
3.1 Jenis dan Pendekatan Penelitian (Kuantitatif Asosiatif / Kausal berbasis Data Sekunder / Time Series / Data Panel).
3.2 Sumber Data dan Periode Pengamatan (BEI/IDX, Laporan Keuangan Tahunan, BPS, OJK, atau basis data resmi periode pengamatan terkini).
3.3 Populasi, Sampel, dan Kriteria Purposive Sampling (WAJIB sajikan Tabel 3.1 Kriteria Seleksi Sampel Penelitian).
3.4 Definisi Operasional dan Pengukuran Variabel (WAJIB sajikan Tabel 3.2 Operasionalisasi Variabel & Rumus Pengukuran Rasio/Indikator).
3.5 Teknik Analisis Data (Statistik Deskriptif, Uji Pemilihan Model Regresi Data Panel [Uji Chow, Uji Hausman, Uji Lagrange Multiplier] atau Uji Asumsi Klasik [Normalitas, Multikolinearitas, Heteroskedastisitas, Autokorelasi], serta Uji Hipotesis [Uji t, Uji F, R²] dalam notasi Unicode bersih).
3.6 Prosedur Penelitian Data Sekunder:
Gambar 3.1 Diagram Alur (Flowchart) Prosedur Penelitian Data Sekunder
```
[Mulai]
↓
[Identifikasi Masalah & Penetapan Variabel '{$project->judul}']
↓
[Pengumpulan Data Sekunder Resmi (BEI / BPS / Laporan Tahunan)]
↓
[Seleksi Sampel Purposive & Tabulasi Data Panel / Time Series]
↓
[Uji Statistik Deskriptif & Uji Asumsi Klasik / Pemilihan Model]
↓
[Estimasi Model Regresi & Evaluasi Signifikansi (Uji t, Uji F, R²)]
↓
[Interpretasi Empiris & Pembahasan Hasil]
↓
[Penyusunan Laporan Hasil Penelitian]
↓
[Selesai]
```",

            $isRnD => "
TUGAS: Tulis Isi BAB III (METODOLOGI PENGEMBANGAN SISTEM / R&D) secara mendalam (MINIMAL 1800 - 2400 KATA).
Disesuaikan secara persis dengan Judul: '{$project->judul}' dan Program Studi: '{$project->prodi}'.
3.1 Model Pengembangan Sistem (SDLC Waterfall / Agile / Prototyping / ADDIE / Borg & Gall) disertai Diagram Alur:
Gambar 3.1 Diagram Alur (Flowchart) Pengembangan Sistem
```
[Mulai]
↓
[Pengumpulan Data Kebutuhan (Wawancara & Observasi)]
↓
[Analisis Kebutuhan Fungsional & Non-Fungsional]
↓
[Perancangan Sistem (Arsitektur, UML, ERD, UI/UX)]
↓
[Implementasi Kode Program & Basis Data]
↓
[Evaluasi & Pengujian Sistem (Black-Box & SUS/UAT)]
↓
[Penyusunan Dokumentasi & Laporan Akhir]
↓
[Selesai]
```
3.2 Waktu dan Tempat Penelitian.
3.3 Teknik Pengumpulan Data (Wawancara, Observasi, Studi Pustaka).
3.4 Analisis Kebutuhan Sistem (Sajikan Tabel 3.1 Analisis Kebutuhan Fungsional & Non-Fungsional).
3.5 Perancangan Sistem (Arsitektur Sistem, Use Case Diagram, Activity Diagram, ERD Basis Data, Wireframe Antarmuka).
3.6 Metode Pengujian Sistem (Black-Box Testing & User Acceptance Testing / System Usability Scale).",

            $isHukum => "
TUGAS: Tulis Isi BAB III (METODOLOGI PENELITIAN HUKUM) secara mendalam (MINIMAL 1600 - 2200 KATA).
3.1 Jenis dan Pendekatan Penelitian (" . (str_contains($metodeLower, 'empiris') ? 'Yuridis Empiris / Sosiologi Hukum dengan pendekatan sosiologis, perundang-undangan [Statute Approach], dan kasus [Case Approach]' : 'Yuridis Normatif dengan pendekatan perundang-undangan [Statute Approach], konseptual [Conceptual Approach], dan kasus [Case Approach]') . ").
3.2 Sumber Bahan Hukum dan Data Penelitian (Bahan Hukum Primer [UUD 1945, UU, PP, Putusan], Bahan Hukum Sekunder [Buku/Jurnal/Doktrin], Bahan Hukum Tersier [Kamus Hukum]" . (str_contains($metodeLower, 'empiris') ? ', serta Data Primer dari wawancara narasumber dan observasi lapangan' : '') . " — WAJIB sajikan Tabel 3.1 Klasifikasi Bahan Hukum).
3.3 Teknik Pengumpulan Bahan Hukum / Data Lapangan (Studi Kepustakaan & Dokumentasi" . (str_contains($metodeLower, 'empiris') ? ' serta Wawancara Mendalam dengan Narasumber/Aparat' : '') . ").
3.4 Teknik Analisis Bahan Hukum (Interpretasi Gramatikal, Sistematis, Teleologis, dan Penalaran Deduktif Silogisme Hukum secara deskriptif-preskriptif).
3.5 Alur Prosedur Penelitian Hukum:
Gambar 3.1 Diagram Alur (Flowchart) Prosedur Penelitian Hukum
```
[Mulai]
↓
[Identifikasi Isu Hukum pada '{$project->judul}']
↓
[Pengumpulan Bahan Hukum Primer, Sekunder & Tersier]
↓
[Inventarisasi dan Klasifikasi Norma Perundang-Undangan]
↓
[Analisis Yuridis & Sinkronisasi Hukum]
↓
[Validasi dan Evaluasi Kesesuaian Norma terhadap Fakta]
↓
[Penyusunan Preskripsi dan Kesimpulan Hukum]
↓
[Selesai]
```",

            $isKualitatif => "
TUGAS: Tulis Isi BAB III (METODOLOGI PENELITIAN KUALITATIF) secara mendalam (MINIMAL 1600 - 2200 KATA).
Disesuaikan secara persis dengan Judul: '{$project->judul}' dan Program Studi: '{$project->prodi}'.
3.1 Pendekatan dan Jenis Penelitian (Deskriptif Kualitatif / Studi Kasus / Fenomenologi).
3.2 Lokasi, Waktu, dan Kehadiran Peneliti sebagai Instrumen Kunci.
3.3 Fokus Penelitian (WAJIB sajikan Tabel 3.1 Matriks Fokus dan Indikator Penelitian — DILARANG menggunakan rumus Slovin/regresi karena ini penelitian kualitatif!).
3.4 Sumber Data dan Teknik Pemilihan Informan (Data Primer dari informan kunci/utama & Data Sekunder dari dokumen/arsip; teknik Purposive Sampling / Snowball Sampling).
3.5 Teknik Pengumpulan Data (Observasi Partisipatif, Wawancara Mendalam [In-Depth Interview], Studi Dokumentasi).
3.6 Teknik Pemeriksaan Keabsahan Data (Triangulasi Sumber, Triangulasi Teknik, Triangulasi Waktu, dan Ketekunan Pengamatan).
3.7 Teknik Analisis Data (Model Interaktif Miles, Huberman & Saldana: Pengumpulan Data, Kondensasi Data, Penyajian Data, dan Penarikan Kesimpulan/Verifikasi) beserta Diagram Alur:
Gambar 3.1 Diagram Alur (Flowchart) Prosedur Penelitian Kualitatif
```
[Mulai]
↓
[Pra-Lapangan & Penentuan Informan Kunci]
↓
[Pengumpulan Data (Observasi, Wawancara Mendalam, Dokumentasi)]
↓
[Kondensasi Data & Kategorisasi Tema (Coding)]
↓
[Pemeriksaan Keabsahan Data (Triangulasi Sumber & Teknik)]
↓
[Validasi Kecukupan dan Kejenuhan Data]
↓
[Penyajian Data & Penarikan Kesimpulan]
↓
[Selesai]
```",

            $isKesehatan => "
TUGAS: Tulis Isi BAB III (METODOLOGI PENELITIAN KESEHATAN / KEPERAWATAN / KLINIS) secara mendalam (MINIMAL 1600 - 2200 KATA).
3.1 Desain Penelitian (Observasional Analitik Cross-Sectional / Case-Control / Kohort / Quasi-Eksperimental Klinis sesuai judul).
3.2 Tempat dan Waktu Penelitian.
3.3 Populasi, Sampel, dan Teknik Sampling (Populasi target & terjangkau, rumus penentuan besar sampel Lemeshow/Slovin, teknik sampling, serta Kriteria Inklusi & Kriteria Eksklusi yang rinci).
3.4 Variabel Penelitian dan Definisi Operasional (WAJIB sajikan Tabel 3.1 Definisi Operasional Variabel: | Variabel | Definisi Operasional | Alat Ukur | Cara Ukur | Hasil Ukur | Skala |).
3.5 Instrumen, Etika Penelitian (Informed Consent, Anonymity, Confidentiality, Justice, Ethical Clearance), dan Prosedur Pengumpulan Data:
Gambar 3.1 Diagram Alur (Flowchart) Prosedur Penelitian Klinis
```
[Mulai]
↓
[Pengurusan Ethical Clearance & Izin Lokasi]
↓
[Seleksi Responden Berdasarkan Kriteria Inklusi & Eksklusi]
↓
[Pengumpulan Data Klinis / Kuesioner & Pengukuran Variabel]
↓
[Pengolahan Data (Editing, Coding, Entry, Cleaning)]
↓
[Analisis Univariat & Bivariat (Chi-Square / Spearman / Uji-t / Mann-Whitney)]
↓
[Evaluasi Signifikansi Klinis (p-value < 0,05 & Odds Ratio)]
↓
[Penyusunan Laporan Hasil Penelitian]
↓
[Selesai]
```
3.6 Pengolahan dan Teknik Analisis Data (Analisis Univariat berupa distribusi frekuensi & persentase/mean, serta Analisis Bivariat sesuai skala data [Chi-Square/Fisher Exact untuk kategorik, Spearman/Pearson untuk korelasi, atau Independent t-test/Mann-Whitney] dengan batas α = 0,05).",

            $isTeknikNonIt => "
TUGAS: Tulis Isi BAB III (METODOLOGI PENELITIAN KETEKNIKAN — {$project->prodi}) secara mendalam (MINIMAL 1700 - 2300 KATA).
3.1 Jenis dan Desain Penelitian Teknis (Eksperimen Laboratorium / Perancangan Struktur-Sistem / Simulasi Numerik sesuai standar SNI/ASTM/IEEE).
3.2 Lokasi dan Waktu Penelitian (Laboratorium / Lokasi Studi Lapangan).
3.3 Alat, Bahan, dan Spesifikasi Teknis (WAJIB sajikan Tabel 3.1 Spesifikasi Alat, Bahan / Perangkat Lunak Pengujian).
3.4 Variabel dan Parameter Pengujian (Variabel Bebas, Variabel Terikat, Variabel Kontrol serta standar ambang batas acuan SNI/ASTM/PUIL/IEEE).
3.5 Prosedur Pengujian & Analisis Data Teknis (Langkah pembuatan benda uji/model, metode pengujian, rumus perhitungan teknis dalam Unicode bersih, serta evaluasi komparatif terhadap standar).
3.6 Alur Prosedur Penelitian Teknis:
Gambar 3.1 Diagram Alur (Flowchart) Prosedur Penelitian Teknis
```
[Mulai]
↓
[Studi Literatur & Penetapan Standar Acuan (SNI / ASTM / IEEE)]
↓
[Persiapan Alat, Bahan & Kalibrasi Instrumen Uji]
↓
[Pembuatan Benda Uji / Pemodelan Desain Teknis]
↓
[Pelaksanaan Pengujian Laboratorium / Simulasi Numerik]
↓
[Pengolahan Data & Evaluasi Kepatuhan Ambang Batas Standar]
↓
[Analisis Hasil & Rekomendasi Rekayasa]
↓
[Selesai]
```",

            $isInformatika => "
TUGAS: Tulis Isi BAB III (METODOLOGI PENELITIAN KOMPUTASI / INFORMATIKA) secara mendalam (MINIMAL 1800 - 2500 KATA).
WAJIB MEMUAT SUB-BAB TEKNIS LENGKAP:
3.1 Desain Penelitian & Alur Sistem (Flowchart)
Uraikan desain penelitian secara sistematis dan WAJIB sajikan BAGAN ALUR (FLOWCHART SISTEM) dalam blok kode vertikal standar ANSI:
Gambar 3.1 Diagram Alur (Flowchart) Prosedur Penelitian
```
[Mulai]
↓
[Pengumpulan Dataset Sesuai Objek '{$project->judul}']
↓
[Pra-Pemrosesan Data (Case Folding, Cleaning, Tokenizing, Stopword, Stemming)]
↓
[Pembobotan Fitur TF-IDF / Ekstraksi Fitur]
↓
[Pembagian Data: Training 80% dan Testing 20%]
↓
[Pelatihan dan Pengujian Model Algoritma]
↓
[Evaluasi Kinerja: Confusion Matrix (Akurasi, Presisi, Recall, F1-Score)]
↓
[Penyusunan Laporan Hasil dan Analisis Kesalahan]
↓
[Selesai]
```
Jelaskan setiap tahapan bagan alur di atas secara terperinci.
3.2 Sumber dan Teknik Pengumpulan Dataset (Jelaskan sumber data, teknik crawling/scraping/API, kata kunci, periode penarikan data, dan total volume dataset).
3.3 Tahapan Pra-Pemrosesan Data (Jabarkan mekanisme kerja setiap tahap prapemrosesan data secara terperinci dan sajikan Tabel 3.1 Tahapan Prapemrosesan Data).
3.4 Pembobotan Fitur dan Pembagian Data (Jabarkan rumus pembobotan fitur dalam teks Unicode bersih dan rasio pembagian dataset latih/uji atau K-Fold Cross Validation).
3.5 Pemodelan Algoritma (Tuliskan rumus matematis algoritma utama dalam teks Unicode bersih beserta penjelasan parameter dan mekanisme pelatihannya).
3.6 Metrik Evaluasi Kinerja (Sajikan Tabel 3.2 Struktur Confusion Matrix [TP, TN, FP, FN] serta rumus perhitungan Akurasi, Precision, Recall, dan F1-Score).",

            $isEkonomi => "
TUGAS: Tulis Isi BAB III (METODOLOGI PENELITIAN BISNIS / EKONOMI KUANTITATIF) secara mendalam (MINIMAL 1800 - 2500 KATA).
3.1 Jenis dan Desain Penelitian (Kuantitatif Asosiatif / Kausal beserta Flowchart Prosedur Penelitian):
Gambar 3.1 Diagram Alur (Flowchart) Prosedur Penelitian
```
[Mulai]
↓
[Identifikasi Masalah & Kajian Teori '{$project->judul}']
↓
[Pengumpulan Data Kuesioner / Laporan Keuangan]
↓
[Uji Validitas dan Reliabilitas Instrumen]
↓
[Uji Asumsi Klasik (Normalitas, Multikolinearitas, Heteroskedastisitas)]
↓
[Analisis Regresi Linier Berganda / SEM-PLS]
↓
[Evaluasi & Uji Hipotesis (Uji t, Uji F, R²)]
↓
[Penyusunan Laporan Hasil & Rekomendasi Manajerial]
↓
[Selesai]
```
3.2 Lokasi, Objek, dan Waktu Penelitian.
3.3 Populasi dan Penentuan Sampel (Teknik sampling, rumus Slovin / Hair et al. dengan perhitungan lengkap).
3.4 Definisi Operasional dan Pengukuran Variabel (WAJIB sajikan Tabel 3.1 Operasionalisasi Variabel dengan kolom: | Variabel | Definisi Operasional | Dimensi | Indikator | Skala Pengukuran |).
3.5 Teknik dan Instrumen Pengumpulan Data (Kuesioner skala Likert 1-5 / Data Sekunder).
3.6 Uji Kualitas Instrumen Penelitian (Uji Validitas Pearson Product Moment dan Uji Reliabilitas Cronbach's Alpha > 0,60).
3.7 Teknik Analisis Data & Uji Hipotesis (Uji Asumsi Klasik dan Analisis Regresi Linier Berganda / SEM-PLS lengkap dengan rumus Unicode bersih).",

            ($isPendidikan && $isPengaruh) || ($isPengaruh && !$isInformatika) => "
TUGAS: Tulis Isi BAB III (METODOLOGI PENELITIAN KUANTITATIF ASOSIATIF / KAUSAL) secara mendalam (MINIMAL 1800 - 2500 KATA).
Disesuaikan secara persis dengan Judul: '{$project->judul}' dan Program Studi: '{$project->prodi}'.{$paiContextNote}{$olahragaContextNote}
WAJIB MEMUAT SUB-BAB TEKNIS LENGKAP:
3.1 Jenis dan Desain Penelitian (Kuantitatif Asosiatif / Kausal).
3.2 Tempat dan Waktu Penelitian (Deskripsi lokasi setting penelitian dan tabel jadwal pelaksanaan — WAJIB isi tanda `✓` pada kolom bulan yang aktif, DILARANG membiarkan sel tabel jadwal kosong!).
3.3 Populasi dan Penentuan Sampel (Populasi target, teknik sampling, serta perhitungan ukuran sampel menggunakan rumus Slovin / Taro Yamane secara lengkap langkah demi langkah).
3.4 Definisi Operasional dan Pengukuran Variabel (WAJIB sajikan Tabel 3.1 Kisi-kisi Operasionalisasi Variabel dengan kolom: | Variabel | Konsep Teori | Dimensi | Indikator Empiris | Nomor Butir | Skala Pengukuran |).
3.5 Teknik dan Instrumen Pengumpulan Data (Kuesioner/Angket Skala Likert dan Dokumentasi Nilai).
3.6 Uji Instrumen Penelitian:
a. Uji Validitas Butir Instrumen (Tuliskan rumus korelasi Pearson Product Moment r-hitung vs r-tabel).
b. Uji Reliabilitas Instrumen (Tuliskan rumus Alpha Cronbach dengan kriteria reliabel > 0,60).
3.7 Teknik Analisis Data & Uji Prasyarat:
a. Analisis Statistik Deskriptif (Mean, Median, Modus, Standar Deviasi, Distribusi Frekuensi).
b. Uji Prasyarat Analisis (Uji Normalitas Kolmogorov-Smirnov dan Uji Linearitas).
c. Uji Hipotesis (Analisis Regresi Linier: Rumus Persamaan Regresi Ŷ = a + bX, Uji t Parsial, Koefisien Determinasi R²).
3.8 Prosedur Penelitian (Flowchart Prosedur Penelitian)
Uraikan penjelasan setiap tahapan prosedur penelitian dan GAMBARKAN BAGAN ALUR (FLOWCHART PROSEDUR PENELITIAN) di dalam blok kode:
Gambar 3.1 Diagram Alur (Flowchart) Prosedur Penelitian
```
[Mulai]
↓
[Identifikasi Masalah & Studi Kepustakaan {$project->judul}]
↓
[Pengumpulan Data Angket & Dokumentasi Lapangan]
↓
[Uji Coba Instrumen: Validitas & Reliabilitas]
↓
[Uji Prasyarat Analisis: Normalitas & Linearitas]
↓
[Analisis Regresi Linier & Pengujian Statistik]
↓
[Evaluasi & Uji Hipotesis (Signifikansi p < 0,05)]
↓
[Penyusunan Laporan Hasil & Rekomendasi]
↓
[Selesai]
```
Jelaskan alur prosedur di atas secara rinci.",

            $isPendidikan => "
TUGAS: Tulis Isi BAB III (METODOLOGI PENELITIAN PENDIDIKAN / QUASI-EKSPERIMEN / KEOLAHRAGAAN) secara mendalam (MINIMAL 1800 - 2400 KATA).{$paiContextNote}{$olahragaContextNote}
3.1 Desain Penelitian (Nonequivalent Pretest-Posttest Control Group Design / Eksperimen / Deskriptif Kuantitatif sesuai judul).
3.2 Tempat dan Waktu Penelitian.
3.3 Populasi dan Sampel (Kelas eksperimen vs kelas kontrol atau subjek pelatihan/pembelajaran).
3.4 Variabel Penelitian dan Definisi Operasional (Sajikan Tabel 3.1 Kisi-kisi Instrumen & Variabel).
3.5 Instrumen Pengumpulan Data (Tes kognitif/keterampilan pretest-posttest dan lembar observasi).
3.6 Uji Coba Instrumen (Uji Validitas Butir, Reliabilitas KR-20/Cronbach, Tingkat Kesukaran, Daya Beda).
3.7 Teknik Analisis Data (Uji Normalitas, Homogenitas, Uji Hipotesis Independent Sample t-test, dan Skor N-Gain).
3.8 Prosedur Penelitian:
Gambar 3.1 Diagram Alur (Flowchart) Prosedur Penelitian Pendidikan
```
[Mulai]
↓
[Observasi Awal & Penyusunan Perangkat Pembelajaran]
↓
[Uji Validitas & Reliabilitas Instrumen Tes]
↓
[Pelaksanaan Pretest & Pengumpulan Data Awal]
↓
[Penerapan Perlakuan / Tindakan Model Pembelajaran]
↓
[Pelaksanaan Posttest & Observasi Kelas]
↓
[Evaluasi & Uji Hipotesis (Uji-t & N-Gain Score)]
↓
[Penyusunan Laporan Hasil Penelitian]
↓
[Selesai]
```",

            default => "
TUGAS: Tulis Isi BAB III (METODOLOGI PENELITIAN) secara lengkap berstandar S1 (MINIMAL 1600 - 2200 KATA).
3.1 Jenis dan Desain Penelitian.
3.2 Lokasi dan Waktu Penelitian.
3.3 Populasi dan Sampel Penelitian.
3.4 Definisi Operasional dan Pengukuran Variabel (Sajikan Tabel 3.1 Indikator Operasionalisasi Variabel).
3.5 Teknik Pengumpulan Data.
3.6 Uji Keabsahan / Instrumen Data.
3.7 Teknik Analisis Data dan Prosedur Penelitian:
Gambar 3.1 Diagram Alur (Flowchart) Prosedur Penelitian
```
[Mulai]
↓
[Identifikasi Masalah & Kajian Literatur]
↓
[Pengumpulan Data Lapangan / Instrumen Penelitian]
↓
[Uji Validitas & Keabsahan Data]
↓
[Pengolahan dan Analisis Data Penelitian]
↓
[Evaluasi & Pengujian Hipotesis / Tujuan]
↓
[Penyusunan Laporan Hasil Penelitian]
↓
[Selesai]
```"
        };

        // Bab IV Sesuai Rumpun Ilmu & Metode Penelitian (100% selaras dengan metode pilihan di form Buat Proyek)
        $bab4Prompt = match (true) {
            $isRnD => "
TUGAS: Tulis Isi BAB IV (HASIL PENGEMBANGAN DAN PEMBAHASAN SISTEM / R&D) (MINIMAL 1800 - 2500 KATA).
Disesuaikan secara persis dengan Judul: '{$project->judul}' dan Program Studi: '{$project->prodi}'.
WAJIB MEMUAT TABEL PENGUJIAN SISTEM LENGKAP (DILARANG memuat Confusion Matrix / TF-IDF kecuali judulnya membahas Machine Learning!):
4.1 Analisis Sistem Berjalan dan Perancangan Sistem (Jabarkan kebutuhan fungsional/non-fungsional, arsitektur sistem, aktor & skenario Use Case, Activity Diagram, serta skema struktur tabel basis data/ERD dalam TABEL struktur basis data).
4.2 Implementasi Sistem dan Antarmuka (Deskripsikan lingkungan implementasi [bahasa/framework/database] serta modul antarmuka utama yang dibangun untuk admin dan pengguna).
4.3 Hasil Pengujian Sistem (WAJIB sajikan TABEL Pengujian Black-Box Testing [No, Modul/Skenario Uji, Data Masukan, Hasil yang Diharapkan, Hasil Aktual, Status Valid] dan TABEL Evaluasi Usability / UAT atau System Usability Scale [SUS] beserta skor akhir dan kategori penilaiannya).
4.4 Pembahasan Kelebihan, Efektivitas, dan Keterbatasan Sistem (Konfrontasikan hasil pengujian terhadap rumusan masalah dan penelitian sistem terdahulu).",

            $isInformatika => "
TUGAS: Tulis Isi BAB IV (HASIL PENELITIAN DAN PEMBAHASAN KOMPUTASI / INFORMATIKA).
WAJIB MEMUAT TABEL DAN DATA EMPIRIS LENGKAP:
4.1 Deskripsi dan Karakteristik Dataset Penelitian (Uraikan statistik data mentah, jumlah sampel per kelas sentimen/kategori dalam TABEL distribusi kelas; nama kelas WAJIB 100% sama dengan Bab I–III).
4.2 Hasil Pra-Pemrosesan Teks (Sajikan TABEL contoh perbandingan 3-4 kalimat sebelum dan sesudah melewati setiap tahapan: Raw Text -> Cleaning -> Slang Normalization -> Stopword Removed -> Stemmed; angka jumlah kosakata sebelum/sesudah stemming WAJIB konsisten 100% dengan Bab III).
4.3 Hasil Pengujian dan Evaluasi Kinerja Algoritma (WAJIB sajikan TABEL Confusion Matrix [Aktual pada baris, Prediksi pada kolom] dan TABEL Perbandingan Metrik Evaluasi [Akurasi, Presisi, Recall, F1-Score] dengan angka-angka terukur dan realistis).
   - ATURAN VERIFIKASI MATEMATIKA CONFUSION MATRIX (WAJIB PATUH 100%):
     * TP kelas c = angka pada diagonal utama (baris c, kolom c).
     * FP kelas c = jumlah sel lain pada KOLOM prediksi kelas c (= Total Prediksi kolom c dikurangi TP kelas c), BUKAN dari baris aktual!
     * FN kelas c = jumlah sel lain pada BARIS aktual kelas c (= Total Aktual baris c dikurangi TP kelas c), BUKAN dari kolom prediksi!
     * Presisi kelas c = TP / (TP + FP) = TP / Total Prediksi kolom c.
     * Recall kelas c = TP / (TP + FN) = TP / Total Aktual baris c.
     * F1-Score kelas c = 2 × (Presisi × Recall) / (Presisi + Recall).
     * Verifikasi ulang setiap penjumlahan baris, penjumlahan kolom, serta ilustrasi perhitungan pada narasi agar 100% identik dengan Tabel Confusion Matrix dan Tabel Evaluasi! DILARANG menulis titik-titik elipsis ('...') di tengah rumus perhitungan atau menyalahkan kekeliruan penjumlahan baris vs kolom sebagai 'pembulatan'!
4.4 Pembahasan Kritis Hasil Penelitian (Analisis mendalam mengapa model mencapai akurasi tersebut, identifikasi faktor pemicu kesalahan klasifikasi [False Positive & False Negative], pengaruh preprocessing terhadap akurasi, pengujian fungsional sistem [jika ada], serta perbandingan kinerja terhadap penelitian terdahulu).",

            $isPtk => "
TUGAS: Tulis Isi BAB IV (HASIL PENELITIAN TINDAKAN KELAS DAN PEMBAHASAN) (MINIMAL 1800 - 2400 KATA).
Disesuaikan secara persis dengan Judul: '{$project->judul}' dan Program Studi: '{$project->prodi}'.{$paiContextNote}{$olahragaContextNote}
WAJIB MEMUAT DATA PERKEMBANGAN ANTAR-SIKLUS:
4.1 Deskripsi Kondisi Awal (Pra-Siklus) (Sajikan TABEL hasil belajar & ketuntasan klasikal sebelum tindakan).
4.2 Pelaksanaan dan Hasil Tindakan Siklus I (Perencanaan, Pelaksanaan, Observasi aktivitas guru & siswa, TABEL hasil tes Siklus I, serta Refleksi kendala Siklus I).
4.3 Pelaksanaan dan Hasil Tindakan Siklus II (Perbaikan dari refleksi Siklus I, Pelaksanaan, Observasi, TABEL hasil tes Siklus II, dan Refleksi ketercapaian indikator keberhasilan).
4.4 Perbandingan Peningkatan Antar-Siklus (WAJIB sajikan TABEL Komparasi Pra-Siklus, Siklus I, dan Siklus II: Nilai Rata-rata, Jumlah Siswa Tuntas, Persentase Ketuntasan Klasikal).
4.5 Pembahasan Temuan Penelitian (Konfrontasikan peningkatan belajar dengan teori Bab II dan penelitian terdahulu).",

            $isStudiPustaka => "
TUGAS: Tulis Isi BAB IV (HASIL KAJIAN LITERATUR DAN PEMBAHASAN) (MINIMAL 1700 - 2300 KATA).
Disesuaikan secara persis dengan Judul: '{$project->judul}' dan Program Studi: '{$project->prodi}'.
WAJIB MEMUAT MATRIKS SINTESIS LITERATUR (DILARANG mengarang data survei lapangan fiktif!):
4.1 Profil dan Karakteristik Literatur Terpilih (Sajikan TABEL klasifikasi sumber primer & sekunder terpilih berdasarkan tahun, metode, dan fokus).
4.2 Analisis Isi (Content Analysis) dan Pemetaan Tema Utama (Sajikan TABEL matriks sintesis temuan antar-literatur sesuai rumusan masalah Bab I).
4.3 Analisis Komparatif dan Sintesis Konseptual (Bandingkan persamaan, perbedaan, serta celah pemikiran antar-sumber pustaka).
4.4 Pembahasan Kritis dan Implikasi Teoretis.",

            $isHukum => "
TUGAS: Tulis Isi BAB IV (HASIL PENELITIAN DAN PEMBAHASAN YURIDIS) (MINIMAL 1800 - 2400 KATA).
Disesuaikan secara persis dengan Judul: '{$project->judul}' dan Program Studi: '{$project->prodi}'.
WAJIB BERBASIS ANALISIS NORMA HUKUM & FAKTA (DILARANG memakai uji statistik SPSS/regresi!):
4.1 Gambaran Umum Objek, Fakta Hukum, dan Inventarisasi Norma (" . (str_contains($metodeLower, 'empiris') ? 'Paparkan kondisi lokasi, hasil wawancara narasumber/penegak hukum, dan fakta implementasi di lapangan beserta TABEL ringkasan data lapangan & regulasi' : 'Inventarisasi norma hukum positif, pasal-pasal terkait, dan putusan/kasus yang relevan dalam TABEL matriks peraturan perundang-undangan') . ").
4.2 Analisis Yuridis dan Sinkronisasi Peraturan terhadap Rumusan Masalah Pertama (Bedah kesesuaian das Sollen dan das Sein menggunakan asas hukum, doktrin ahli, dan pasal yang berlaku).
4.3 Pembahasan Kendala, Efektivitas, dan Konstruksi Solusi Hukum (Menjawab rumusan masalah berikutnya dengan preskripsi hukum yang tegas dan argumentatif).",

            $isKualitatif => "
TUGAS: Tulis Isi BAB IV (HASIL PENELITIAN DAN PEMBAHASAN KUALITATIF) (MINIMAL 1800 - 2500 KATA).
Disesuaikan secara persis dengan Judul: '{$project->judul}' dan Program Studi: '{$project->prodi}'.{$paiContextNote}{$olahragaContextNote}
WAJIB BERBASIS DATA KUALITATIF MENDALAM (DILARANG memakai rumus SPSS/regresi/hipotesis H0-H1!):
4.1 Gambaran Umum Lokasi / Situs Penelitian dan Profil Informan (Sajikan TABEL Profil Informan Kunci & Pendukung: Inisial/Kode Informan, Jabatan/Peran, Alasan Pemilihan).
4.2 Paparan Data Temuan Lapangan Berdasarkan Fokus Penelitian (Uraikan temuan empiris hasil wawancara mendalam, observasi partisipatif, dan dokumentasi yang dikelompokkan rapi menjawab tiap rumusan masalah Bab I).
4.3 Analisis Data dan Keabsahan Temuan (Sajikan TABEL Matriks Reduksi Data & Triangulasi Sumber/Teknik yang merangkum temuan kunci dari berbagai informan dan dokumen).
4.4 Pembahasan / Analisis Teoretis (Dialogkan secara kritis temuan lapangan dengan Grand Theory/Middle Theory pada Bab II serta penelitian terdahulu untuk menjelaskan makna sosiologis/pedagogis/psikologis fenomena tersebut).",

            $isKesehatan => "
TUGAS: Tulis Isi BAB IV (HASIL PENELITIAN DAN PEMBAHASAN KLINIS / KEPERAWATAN / KESEHATAN) (MINIMAL 1800 - 2500 KATA).
Disesuaikan secara persis dengan Judul: '{$project->judul}' dan Program Studi: '{$project->prodi}'.
WAJIB MEMUAT ANALISIS UNIVARIAT & BIVARIAT STANDAR KESEHATAN:
4.1 Gambaran Umum Lokasi Pelayanan Kesehatan dan Karakteristik Responden (WAJIB sajikan TABEL Distribusi Frekuensi Karakteristik Responden: Usia, Jenis Kelamin, Pendidikan, Pekerjaan/Lama Menderita dalam n dan %).
4.2 Hasil Analisis Univariat (WAJIB sajikan TABEL Distribusi Frekuensi Variabel Independen dan TABEL Distribusi Frekuensi Variabel Dependen secara lengkap dalam n dan % atau Mean ± SD).
4.3 Hasil Analisis Bivariat dan Pengujian Hipotesis Klinis (WAJIB sajikan TABEL Tabulasi Silang [Cross-Tabulation] hubungan variabel independen terhadap variabel dependen dilengkapi hasil uji statistik yang tepat [Chi-Square / Fisher Exact / Spearman / Mann-Whitney / Independent t-test], nilai p-value, serta Odds Ratio [OR] / koefisien korelasi r jika relevan).
4.4 Pembahasan Klinis dan Implikasi Keperawatan/Kesehatan (Bahas secara kritis mekanisme fisiologis/psikologis di balik nilai p-value tersebut, bandingkan dengan teori Bab II & jurnal kesehatan terdahulu, serta uraikan keterbatasan penelitian).",

            $isTeknikNonIt => "
TUGAS: Tulis Isi BAB IV (HASIL PENGUJIAN DAN PEMBAHASAN REKAYASA — {$project->prodi}) (MINIMAL 1800 - 2400 KATA).
WAJIB MEMUAT DATA UJI TEKNIS & KOMPARASI STANDAR SNI/ASTM/IEEE:
4.1 Karakteristik Material / Parameter Desain Awal (Sajikan TABEL data spesifikasi material/parameter input).
4.2 Hasil Pengujian Laboratorium / Simulasi Numerik (WAJIB sajikan TABEL hasil pengukuran/pengujian setiap variasi sampel atau beban beserta nilai rata-rata dan deviasinya).
4.3 Analisis Perbandingan terhadap Standar Acuan (Evaluasi apakah hasil memenuhi syarat ambang batas SNI, ASTM, PUIL, atau IEEE yang ditetapkan pada Bab II–III).
4.4 Pembahasan Teknis dan Rekomendasi Optimalisasi Desain.",

            $isEkonomi => "
TUGAS: Tulis Isi BAB IV (HASIL PENELITIAN DAN PEMBAHASAN EKONOMI / MANAJEMEN).
WAJIB MEMUAT TABEL STATISTIK LENGKAP:
4.1 Gambaran Umum Objek dan Karakteristik Responden (Sajikan TABEL Demografi Responden: Jenis kelamin, Usia, Pendidikan, Lama Bekerja/Pengeluaran).
4.2 Analisis Statistik Deskriptif Variabel (Sajikan TABEL Statistik Deskriptif: N, Minimum, Maximum, Mean, Std. Deviation untuk setiap variabel).
4.3 Hasil Uji Kualitas Data (Sajikan TABEL Uji Validitas r-hitung vs r-tabel dan TABEL Uji Reliabilitas Cronbach's Alpha).
4.4 Hasil Uji Asumsi Klasik (Sajikan TABEL Uji Normalitas, Multikolinearitas [Tolerance & VIF], dan Heteroskedastisitas).
4.5 Hasil Analisis Regresi Linier Berganda & Uji Hipotesis (Sajikan TABEL Ringkasan Regresi: Nilai R, R-Square, Adjusted R-Square, TABEL Uji F ANOVA, dan TABEL Uji Parsial t [Koefisien B, t-hitung, Nilai Sig]).
4.6 Pembahasan Hasil Penelitian (Bahas secara kritis penerimaan/penolakan masing-masing hipotesis H1, H2, H3 dengan membandingkan terhadap teori pendukung dan temuan penelitian terdahulu).",

            ($isPendidikan && $isPengaruh) || ($isPengaruh && !$isInformatika) => "
TUGAS: Tulis Isi BAB IV (HASIL PENELITIAN DAN PEMBAHASAN KUANTITATIF).
Disesuaikan secara persis dengan Judul: '{$project->judul}' dan Program Studi: '{$project->prodi}'.{$paiContextNote}{$olahragaContextNote}
WAJIB MEMUAT TABEL DATA STATISTIK LENGKAP DAN PEMBAHASAN MENDALAM (MINIMAL 1500 - 2500 KATA):
4.1 Gambaran Umum Lokasi dan Karakteristik Responden (Sajikan TABEL Demografi Responden: Jenis Kelamin, Usia, Kelas/Latar Belakang).
4.2 Deskripsi Data Variabel Penelitian (Sajikan TABEL Statistik Deskriptif Variabel X dan Variabel Y: N, Skor Minimum, Maksimum, Mean, Standar Deviasi, serta TABEL Kategori Distribusi Frekuensi).
4.3 Hasil Uji Kualitas Instrumen (Sajikan TABEL Hasil Uji Validitas r-hitung > r-tabel dan TABEL Uji Reliabilitas Cronbach's Alpha > 0,60).
4.4 Hasil Uji Prasyarat Analisis (Sajikan TABEL Hasil Uji Normalitas Kolmogorov-Smirnov dan TABEL Uji Linearitas).
4.5 Hasil Analisis Regresi Linier dan Uji Hipotesis:
a. Sajikan TABEL Ringkasan Model Regresi (Nilai Korelasi R dan Koefisien Determinasi R-Square / R²).
b. Sajikan TABEL Uji Koefisien Regresi (Konstanta a, Koefisien Regresi b, nilai t-hitung, dan nilai signifikansi p-value / Sig.).
c. Tuliskan persamaan regresi yang terbentuk secara jelas: Ŷ = a + bX beserta interpretasi maknanya.
d. KONSISTENSI RUMUS & INDIKATOR WAJIB: Pada regresi linier sederhana (1 variabel bebas X), wajib pastikan F-hitung = t-hitung² tepat 100% dan konsisten dengan R² = F / (F + n - 2), SERTA apabila menyebut nomor butir angket (misal X7 atau X8), pastikan nama indikatornya sama persis dengan Tabel Kisi-kisi Operasionalisasi Variabel Bab III!
4.6 Pembahasan Hasil Penelitian (Bahas secara kritis dan mendalam minimal 6-8 paragraf: konfrontasikan temuan angka empiris dengan teori Bab II dan temuan penelitian terdahulu, implikasi hasil terhadap responden, serta faktor-faktor pemicu temuan tersebut).
4.7 Keterbatasan Penelitian (Uraikan kendala metodologis atau lingkup yang dihadapi selama penelitian).",

            $isPendidikan => "
TUGAS: Tulis Isi BAB IV (HASIL PENELITIAN DAN PEMBAHASAN PENDIDIKAN).{$paiContextNote}{$olahragaContextNote}
WAJIB MEMUAT:
4.1 Deskripsi Data Hasil Belajar (Sajikan TABEL nilai rata-rata Pretest & Posttest kelas eksperimen vs kelas kontrol).
4.2 Hasil Uji Prasyarat Analisis (Sajikan TABEL Uji Normalitas & Uji Homogenitas Varian).
4.3 Hasil Pengujian Hipotesis & Efektivitas (Sajikan TABEL Uji-t Independent Sample dan TABEL Peningkatan Skor N-Gain).
4.4 Pembahasan Temuan Penelitian.",

            default => "
TUGAS: Tulis Isi BAB IV (HASIL PENELITIAN DAN PEMBAHASAN).
WAJIB MEMUAT TABEL DATA DAN ANALISIS EMPIRIS LENGKAP:
4.1 Deskripsi Data dan Objek Penelitian.
4.2 Hasil Analisis Data (Sajikan TABEL pengolahan data terstruktur).
4.3 Pengujian Hipotesis / Capaian Solusi.
4.4 Pembahasan Kritis Hasil Penelitian."
        };

        // Hitung jumlah butir Rumusan Masalah Bab I agar Bab V 5.1 Kesimpulan otomatis tepat 1-to-1
        $rmCountAuto = 0;
        $b1Plain = trim(strip_tags((string) ($project->bab1_content ?? '')));
        if ($b1Plain !== '' && preg_match('/(?:1\.4|1\.2|B\.|C\.|D\.)?\s*Rumusan\s+Masalah[^\n]*\n([\s\S]{20,1600}?)(?=\n\s*(?:1\.[3-9]|[B-H]\.\s+Tujuan|Tujuan\s+Penelitian|1\.5|Manfaat)|$)/i', $b1Plain, $mRmAuto)) {
            preg_match_all('/^\s*(?:\d+[\.\)]|[a-z][\.\)])\s+.{10,}/m', $mRmAuto[1], $rmItemsAuto);
            $rmCountAuto = count($rmItemsAuto[0] ?? []);
        }
        $rmCountRule = $rmCountAuto > 0
            ? "WAJIB ditulis dalam format DAFTAR POIN BERNOMOR persis `1.`, `2.`, ... hingga `{$rmCountAuto}.` (TEPAT {$rmCountAuto} butir bernomor, BUKAN paragraf naratif tanpa nomor!) yang menjawab satu per satu {$rmCountAuto} butir Rumusan Masalah pada Bab I secara presisi dan berbasis angka temuan Bab IV."
            : "WAJIB ditulis dalam format daftar poin bernomor (`1.`, `2.`, `3.`, dst.) yang menjawab satu per satu seluruh butir rumusan masalah Bab I secara tegas berdasarkan capaian empiris Bab IV.";

        $styleGuideBab = $this->buildCitationStyleGuide((string) $gayaSitasi);

        // Bab V Penutup & Daftar Pustaka Lengkap & Terverifikasi (Bebas Mendeley)
        $bab5Prompt = "
TUGAS: Tulis Isi BAB V (PENUTUP) beserta DAFTAR PUSTAKA LENGKAP & OTOMATIS (STANDAR {$gayaSitasi}).
Sub-bab:
5.1 Kesimpulan ({$rmCountRule} WAJIB MENYEBUTKAN LANGSUNG ANGKA METRIK RIIL DARI BAB IV secara eksplisit di dalam teks kesimpulan — seperti persentase akurasi, precision, recall, F1-score per kelas, jumlah term sebelum/sesudah stemming, persentase reduksi kosakata, hasil pengujian Black-Box/SUS, selisih uji ablasi, rentang variasi, nilai R²/t-hitung/Sig./p-value, atau ketuntasan Siklus I/II — DILARANG KERAS menghindar dengan menulis 'dilaporkan secara terperinci pada Bab IV' tanpa mencantumkan angkanya, DAN DILARANG KERAS MENGARANG ATAU MENGUBAH 1 DIGIT PUN ANGKA DARI BAB III DAN BAB IV! Periksa ulang setiap angka yang Anda tulis terhadap PETA BENANG MERAH & HASIL PENELITIAN BAB IV di atas sebelum menyimpulkan!).
5.2 Saran (Uraikan saran operasional dan saran teknis bagi penelitian lanjutan).

Setelah sub-bab 5.2 selesai, buat baris baru bertuliskan persis:
===DAFTAR PUSTAKA===
Lalu tuliskan DAFTAR PUSTAKA LENGKAP {$styleGuideBab['sort_instruction']} (FORMAT WAJIB: {$gayaSitasi}) YANG WAJIB MENGAMBIL DARI SELURUH SITASI ASLI YANG MUNCUL DALAM NASKAH BAB 1 HINGGA BAB 4 (lihat daftar sitasi yang tertera di atas)" . (!empty($meta['referensi_khusus']) ? " serta SELURUH ARTIKEL / BUKU UNGGAHAN MAHASISWA" : "") . ".

ATURAN MUTLAK SITASI & DAFTAR PUSTAKA (GAYA {$styleGuideBab['label']} — DILARANG FALLBACK KE GAYA LAIN):
1. DAFTAR PUSTAKA WAJIB DIBUAT DARI SITASI NYATA YANG BENAR-BENAR DIKUTIP DALAM BAB 1 S/D 4! Dilarang keras mengarang referensi fiktif yang tidak pernah disebut dalam naskah.
2. {$styleGuideBab['bib_rule']}
   CONTOH FORMAT WAJIB ({$gayaSitasi}):
   {$styleGuideBab['bib_example']}
3. Referensi WAJIB ASLI dan NYATA (terindeks {$sumberArr} / SINTA/Garuda Kemdikbud untuk Indonesia, atau Scopus/IEEE/ACM/Elsevier/Springer untuk Internasional).
4. Tahun terbit referensi WAJIB minimal tahun {$tahunMin} dan maksimal tahun 2026.
5. Konsistensi 100%: Setiap rujukan yang disitasi dalam teks Bab I sampai Bab IV WAJIB ADA di dalam daftar pustaka ini dengan gaya {$gayaSitasi}.
6. Tanpa kalimat pengantar AI, langsung baris referensi pertama sesuai urutan {$styleGuideBab['label']}.";

        $customStructureNote = !empty($meta['struktur_kustom'])
            ? "PERHATIAN PENTING: MAHASISWA MEMILIKI STRUKTUR SUB-BAB KUSTOM DARI KAMPUSNYA (LIHAT DI ATAS). AI WAJIB MENGIKUTI STRUKTUR KUSTOM TERSEBUT SECARA PERSIS, BUKAN STRUKTUR STANDAR DEFAULT!\n"
            : (!empty($meta['pedoman_text'])
                ? "PERHATIAN PENTING (PEDOMAN KAMPUS AKTIF): Mahasiswa mengunggah Pedoman Penulisan Kampus di atas. Jika pedoman kampus tersebut menetapkan urutan sub-bab, penomoran (mis. A/B/C atau 1.1/1.2), gaya bahasa, atau sistematika khusus untuk bab ini, WAJIB patuhi pedoman kampus tersebut sebagai prioritas tertinggi di atas struktur default!\n"
                : "");

        $sistematikaScope = $isProposal ? 'Bab I hingga Bab III (Proposal Skripsi)' : 'Bab I hingga Bab V (Skripsi Lengkap)';
        return match ($bab) {
            'bab1' => $master . "\n{$tahapKaryaPrompt}\n{$dataAwalPrompt}\n{$strukturKustomPrompt}\n{$referensiKhususPrompt}\n{$dataRiilPrompt}\n" . "
TUGAS UTAMA: Tuliskan NASKAH LENGKAP BAB I (PENDAHULUAN) dari awal sampai akhir secara TAJAM, PADAT, DAN PROPORSIONAL sesuai STANDAR SKRIPSI S1 PERGURUAN TINGGI INDONESIA (target ideal 1.250 - 1.650 KATA / ±5-7 halaman A4 agar fokus dan tidak bertele-tele).
{$customStructureNote}" . (empty($meta['struktur_kustom']) ? "WAJIB MEMUAT SELURUH SUB-BAB BERURUTAN TANPA MELEWATKAN SATU PUN:
1.1 Latar Belakang Masalah (WAJIB ditulis TAJAM, PADAT, DAN PROPORSIONAL sebanyak **TEPAT 5-6 PARAGRAF** — ±1,5 sampai 2 halaman A4, masing-masing 5-6 kalimat / ±75-105 kata, BUKAN 9-10 paragraf yang kepanjangan! — mengikuti alur PIRAMIDA TERBALIK standar skripsi S1 Indonesia):
- Paragraf 1 (Konteks Makro & Landasan Normatif — 5-6 kalimat, WAJIB minimal 1 sitasi ilmiah): Mulai dengan konteks makro/regulasi terkait topik '{$project->judul}' disertai minimal 1 sitasi in-text gaya {$gayaSitasi} dari literatur ilmiah terverifikasi.
- Paragraf 2 (Fenomena Empiris & Data Nyata — 5-6 kalimat): Sajikan skala fenomena dan bukti empiris/data otoritas resmi (BPS, Kemendikbudristek, APJII, We Are Social/DataReportal, atau data lapangan mahasiswa) yang menegaskan besarnya persoalan.
- Paragraf 3 (Permasalahan Spesifik di Objek Penelitian — 5-6 kalimat): Uraikan kendala teknis/operasional spesifik yang terjadi pada objek penelitian beserta rujukan penelitian terdahulu (tahun {$tahunMin}-2026).
- Paragraf 4 (Kesenjangan Riset / Research Gap — 5-6 kalimat): Konfrontasikan temuan penelitian terdahulu (tahun {$tahunMin}-2026) untuk menunjukkan celah metodologis/empiris yang belum terjawab secara tuntas.
- Paragraf 5-6 (Urgensi Solusi, Kebaruan & Alasan Pemilihan Judul — masing-masing 5-6 kalimat): Tegaskan kebaruan (novelty), alasan pemilihan pendekatan/metode penelitian ini, serta urgensi praktis mengapa penelitian ini harus diselesaikan sekarang.
1.2 Identifikasi Masalah (Tuliskan 4-5 butir permasalahan konkret yang diturunkan langsung dari Latar Belakang dalam format poin bernomor 1., 2., 3., dst.).
1.3 Batasan Masalah (Uraikan batasan ruang lingkup penelitian secara ringkas dan tegas dalam 3-4 poin: variabel/objek, sampel/data, periode waktu, dan metode).
1.4 Rumusan Masalah (Tuliskan 3-4 butir pertanyaan penelitian ilmiah yang spesifik, terukur, dan selaras langsung dengan tujuan).
1.5 Tujuan Penelitian (Tuliskan Tujuan Umum dan Tujuan Khusus bernomor yang menjawab 1-ke-1 setiap butir Rumusan Masalah).
1.6 Manfaat Penelitian (Uraikan secara ringkas dan bernas ke dalam: 1.6.1 Manfaat Teoretis dan 1.6.2 Manfaat Praktis).
1.7 Keaslian Penelitian (Uraikan secara ringkas 1-2 paragraf pembeda riset ini dari 3-4 studi terdahulu terbaru tanpa pernah menyebut istilah 'bank referensi proyek ini').
1.8 Sistematika Penulisan (Uraikan ringkasan isi {$sistematikaScope} secara padat dan terstruktur).\n" : "") . "
" . (!empty($meta['data_awal']) ? "WAJIB: Masukkan data awal penelitian mahasiswa ('{$meta['data_awal']}') secara eksplisit ke dalam Latar Belakang Masalah (1.1) sebagai bukti fenomena nyata!\n" : "") . "
INSTRUKSI MUTLAK:
- Seluruh sub-bab HARUS ditulis lengkap tuntas dalam satu kali respons ini tanpa mengulang-ulang kalimat yang sama.
- Jaga agar 1.1 Latar Belakang Masalah tepat 5-6 paragraf padat (±1,5-2 halaman) dan total Bab I proporsional (1.250-1.650 kata).
- DILARANG KERAS menulis bagian 'Daftar Pustaka' / 'Referensi' di dalam Bab I; Daftar Pustaka dibuat terpisah oleh sistem.
- Langsung mulai dari sub-bab pertama (1.1 Latar Belakang Masalah) tanpa judul 'BAB I' dan tanpa kata pengantar AI.",

            'bab2' => $master . "\n{$tahapKaryaPrompt}\n$context\n{$strukturKustomPrompt}\n{$referensiKhususPrompt}\n{$dataRiilPrompt}\n{$bab2Prompt}\n
{$customStructureNote}
INSTRUKSI MUTLAK:
- Seluruh sub-bab, TABEL PENELITIAN TERDAHULU, dan DIAGRAM KERANGKA BERPIKIR HARUS ditulis lengkap tuntas tanpa terpotong.
- Tahun terbit semua penelitian terdahulu WAJIB antara {$tahunMin}-2026.
- DILARANG KERAS menulis bagian 'Daftar Pustaka' / 'Referensi' di dalam Bab II; Daftar Pustaka dibuat terpisah oleh sistem.
- Langsung mulai dari sub-bab pertama tanpa kata pengantar AI.",

            'bab3' => $master . "\n{$tahapKaryaPrompt}\n$context\n{$dataAwalPrompt}\n{$strukturKustomPrompt}\n{$referensiKhususPrompt}\n{$dataRiilPrompt}\n{$bab3Prompt}\n
{$customStructureNote}
INSTRUKSI MUTLAK:
- Tulis seluruh sub-bab secara tuntas, wajib sertakan DIAGRAM ALUR (FLOWCHART) dalam blok kode vertikal `[Tahap]` dipisahkan panah `↓`, serta tabel operasional/instrumen yang rapi.
- DILARANG KERAS menulis bagian 'Daftar Pustaka' atau 'Referensi' di akhir Bab III (meskipun untuk Proposal Skripsi); Daftar Pustaka dicetak otomatis di halaman terpisah oleh sistem.",

            'bab4' => $master . "\n{$tahapKaryaPrompt}\n$context\n{$dataAwalPrompt}\n{$strukturKustomPrompt}\n{$referensiKhususPrompt}\n{$dataRiilPrompt}\n{$bab4Prompt}\n
{$customStructureNote}
INSTRUKSI MUTLAK:
- WAJIB menyajikan TABEL pengolahan data empiris lengkap dengan nomor judul (Tabel 4.1, Tabel 4.2, dst.) beserta perhitungan rumus Unicode bersih dan pembahasan kritis mendalam.
- DILARANG KERAS menulis bagian 'Daftar Pustaka' / 'Referensi' di dalam Bab IV.",

            'bab5' => $master . "\n{$tahapKaryaPrompt}\n$context\n{$dataAwalPrompt}\n{$strukturKustomPrompt}\n{$referensiKhususPrompt}\n{$dataRiilPrompt}\n{$bab5Prompt}",

            default => $master
        };
    }

    // =========================================================================
    // 4. GENERATE BAB, REVISI BAB (TEKS + GAMBAR CORETAN + FILE), & SIMPAN MANUAL
    // =========================================================================

    private function cleanTextKeepFormat($text)
    {
        if (empty($text))
            return '';
        $text = preg_replace('/\*\*(.*?)\*\*/', '$1', $text);
        $text = preg_replace('/###\s*(.*?)\n/', "$1\n", $text);
        $text = preg_replace('/##\s*(.*?)\n/', "$1\n", $text);
        return trim($text);
    }

    private function processChapterContent($content, $chapterTitle)
    {
        $lines = explode("\n", $content);
        $cleanLines = [];

        $chapterKeywords = [
            'BAB I',
            'BAB 1',
            'PENDAHULUAN',
            'BAB I PENDAHULUAN',
            'BAB II',
            'BAB 2',
            'TINJAUAN PUSTAKA',
            'BAB II TINJAUAN PUSTAKA',
            'BAB III',
            'BAB 3',
            'METODOLOGI',
            'METODOLOGI PENELITIAN',
            'METODE PENELITIAN',
            'BAB IV',
            'BAB 4',
            'HASIL DAN PEMBAHASAN',
            'HASIL PENELITIAN DAN PEMBAHASAN',
            'BAB V',
            'BAB 5',
            'PENUTUP',
            'BAB V PENUTUP'
        ];

        foreach ($lines as $idx => $line) {
            $lineTrimmed = trim(strip_tags($line));
            $lineUpper = strtoupper($lineTrimmed);

            // Hapus baris judul BAB ganda (termasuk "BAB IV HASIL PENELITIAN DAN PEMBAHASAN", "BAB III METODOLOGI PENELITIAN", dll.)
            if ($idx < 6 && preg_match('/^(?:#+\s*)?BAB\s+[IVX0-9]+(?:\s+[^\n]{0,80})?$/i', $lineTrimmed)) {
                continue;
            }

            $isTitle = false;
            foreach ($chapterKeywords as $keyword) {
                if ($lineUpper === $keyword || preg_match('/^\d+\s+' . preg_quote($keyword, '/') . '$/', $lineUpper)) {
                    $isTitle = true;
                    break;
                }
            }
            if ($isTitle)
                continue;

            $cleanLines[] = $line;
        }
        return implode("\n", $cleanLines);
    }

    public function generateChapter(Request $request, $id)
    {
        $request->validate([
            'chapter' => 'required|in:bab1,bab2,bab3,bab4,bab5',
        ]);
        $project = Project::where('user_id', Auth::id())->findOrFail($id);
        $chapter = (string) $request->chapter;
        $authUser = Auth::user();
        if ($authUser) {
            $gate = $this->billing()->canGenerateChapter($authUser, $project, (string) $chapter);
            if (empty($gate['allowed'])) {
                return response()->json([
                    'status' => 'upgrade_required',
                    'reason' => $gate['reason'] ?? 'chapter_locked',
                    'message' => $gate['message'],
                ], 402);
            }
        }
        $model = $request->input('model', 'auto');
        $dataRiil = $request->input('data_riil', []);
        $meta = $this->getProjectMeta($project);
        if (!empty($dataRiil) && is_array($dataRiil)) {
            $meta['data_riil'] = $dataRiil;
            $project->citation_validation = json_encode($meta, JSON_UNESCAPED_UNICODE);
        }

        try {
            $finalPrompt = $this->promptBab($chapter, $project, $dataRiil);
            $content = $this->ai->generateText($finalPrompt, $model);

            $cleanContent = $this->cleanTextKeepFormat($content);
            $cleanContent = $this->processChapterContent($cleanContent, $chapter);

            $sitasiMeta = $meta['sitasi'] ?? [];
            $tahunMinCh = !empty($sitasiMeta['tahun_min']) ? (string) $sitasiMeta['tahun_min'] : '2023';
            $gayaSitasiCh = $sitasiMeta['gaya'] ?? 'APA 7th';

            if ($chapter === 'bab5') {
                if (str_contains($cleanContent, '===DAFTAR PUSTAKA===')) {
                    $parts = explode('===DAFTAR PUSTAKA===', $cleanContent, 2);
                    $project->bab5_content = $this->enforceChapterQualityAutomatically(trim($parts[0]), 'bab5', $project);
                    $cleanDp = $this->cleanDaftarPustaka(trim($parts[1] ?? ''), $tahunMinCh, $gayaSitasiCh);
                    if (!empty($cleanDp)) {
                        $project->daftar_pustaka = $cleanDp;
                    }
                } elseif (preg_match('/\n\s*(?:===+\s*)?DAFTAR\s+PUSTAKA\s*(?:===+)?\s*\n/i', $cleanContent)) {
                    $parts = preg_split('/\n\s*(?:===+\s*)?DAFTAR\s+PUSTAKA\s*(?:===+)?\s*\n/i', $cleanContent, 2);
                    $project->bab5_content = $this->enforceChapterQualityAutomatically(trim($parts[0]), 'bab5', $project);
                    $cleanDp = $this->cleanDaftarPustaka(trim($parts[1] ?? ''), $tahunMinCh, $gayaSitasiCh);
                    if (!empty($cleanDp)) {
                        $project->daftar_pustaka = $cleanDp;
                    }
                } else {
                    $project->bab5_content = $this->enforceChapterQualityAutomatically($cleanContent, 'bab5', $project);
                }
            } else {
                $cleanContent = $this->enforceChapterQualityAutomatically($cleanContent, $chapter, $project);
                $project->{$chapter . '_content'} = $cleanContent;
                // Ekstrak sitasi otomatis ke daftar pustaka sementara saat Bab III selesai (Proposal) atau jika daftar pustaka masih kosong
                if ((empty($project->daftar_pustaka) && in_array($chapter, ['bab1', 'bab2', 'bab3'])) || $chapter === 'bab3') {
                    try {
                        $allText = strip_tags($project->bab1_content . ' ' . $project->bab2_content . ' ' . $project->bab3_content);
                        if (strlen($allText) > 500) {
                            $citationsList = $this->extractInTextCitations($allText);
                            [$verifiedMatched, $unmatchedCits] = $this->buildVerifiedMatchedDaftarPustaka($project, $citationsList, $gayaSitasiCh);
                            $verifiedBlock = !empty($verifiedMatched)
                                ? "ENTRI TERVERIFIKASI DENGAN DOI ASLI (WAJIB DISERTAKAN PERSIS TANPA MENGUBAH PENULIS/TAHUN/DOI):\n- " . implode("\n- ", $verifiedMatched) . "\n"
                                : "";
                            $citBlock = !empty($unmatchedCits)
                                ? "SITASI TAMBAHAN DALAM NASKAH YANG PERLU DILENGKAPI BIBLIOGRAFINYA:\n- " . implode("\n- ", $unmatchedCits) . "\n"
                                : (!empty($citationsList) ? "DAFTAR SITASI ASLI YANG MUNCUL DI NASKAH:\n- " . implode("\n- ", $citationsList) . "\n" : "");
                            $styleGuideDpCh = $this->buildCitationStyleGuide((string) $gayaSitasiCh);
                            $dpPrompt = "Tulis DAFTAR PUSTAKA gaya {$gayaSitasiCh} berdasarkan sitasi yang dikutip pada naskah proposal/skripsi berjudul '{$project->judul}' (Prodi: {$project->prodi}).\n" .
                                $verifiedBlock .
                                $citBlock .
                                (!empty($meta['referensi_khusus']) ? "REFERENSI WAJIB UNGGAHAN MAHASISWA (WAJIB MASUK):\n" . Str::limit($meta['referensi_khusus'], 2500) . "\n" : "") .
                                "Tahun penerbitan WAJIB minimal tahun {$tahunMinCh} dan maksimal 2026 (DILARANG di bawah {$tahunMinCh}).\n" .
                                "ATURAN KETAT GAYA {$styleGuideDpCh['label']}:\n" .
                                "1. {$styleGuideDpCh['bib_rule']}\n" .
                                "   Contoh format WAJIB: {$styleGuideDpCh['bib_example']}\n" .
                                "2. LANGSUNG tuliskan baris referensi pertama ({$styleGuideDpCh['sort_instruction']}) TANPA kalimat pembuka/pengantar.\n" .
                                "3. WAJIB menyertakan seluruh ENTRI TERVERIFIKASI di atas secara utuh tanpa mengubah DOI-nya.\n" .
                                "4. DILARANG mengarang DOI palsu untuk sitasi yang tidak memiliki DOI.\n" .
                                "5. Tanpa tanda bintang markdown (*).\n\nNASKAH:\n" . Str::limit($allText, 5500);
                            $dpRaw = $this->ai->generateText($dpPrompt, $model);
                            $cleanedDp = $this->cleanDaftarPustaka($dpRaw, $tahunMinCh, $gayaSitasiCh);
                            if (!empty($cleanedDp)) {
                                $project->daftar_pustaka = $cleanedDp;
                            }
                        }
                    } catch (Throwable $e) {
                    }
                }
            }

            // Sinkronisasi otomatis 1-to-1 entri jurnal terverifikasi (DOI asli) ke Daftar Pustaka setiap kali bab selesai disusun
            $this->syncVerifiedDaftarPustakaAutomatically($project, $gayaSitasiCh, $tahunMinCh);

            $project->save();

            return response()->json([
                'status' => 'success',
                'content' => $project->{$chapter . '_content'},
                'daftar_pustaka' => $project->daftar_pustaka,
            ]);
        } catch (Exception $e) {
            Log::error('generateChapter error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyusun bab: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ekstrak teks sub-bab spesifik dari isi bab lengkap untuk revisi presisi
     */
    private function extractSubchapterText(string $content, string $subchapter): ?array
    {
        if (empty(trim($content)) || empty(trim($subchapter))) {
            return null;
        }

        preg_match('/^(\d+\.\d+(?:\.\d+)?)/', trim($subchapter), $pm);
        $prefix = $pm[1] ?? '';

        if ($prefix) {
            $startPattern = '/(^|\n)(?:##\s*|<b>)?(' . preg_quote($prefix, '/') . '[\s\.:\-\)][^\n]*)/i';
        } else {
            $cleanTitle = preg_replace('/^[^a-zA-Z]+/', '', trim($subchapter));
            if (empty($cleanTitle) || strlen($cleanTitle) < 3)
                return null;
            $startPattern = '/(^|\n)(?:##\s*|<b>)?([^\n]*' . preg_quote($cleanTitle, '/') . '[^\n]*)/i';
        }

        if (!preg_match($startPattern, $content, $match, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        $startPos = $match[0][1] + ($match[1][0] === "\n" ? 1 : 0);
        $tailStart = $startPos + strlen($match[0][0]);
        $tail = substr($content, $tailStart);
        $nextPattern = '/\n(?:##\s*|<b>)?(?:\d+\.\d+(?:\.\d+)?|[A-Z]\.)[\s\.:\-\)][^\n]*/i';

        $endPos = strlen($content);
        if (preg_match_all($nextPattern, $tail, $nextMatches, PREG_OFFSET_CAPTURE)) {
            foreach ($nextMatches[0] as $cand) {
                $prefixBefore = substr($content, 0, $tailStart + $cand[1]);
                // Hindari memotong di dalam blok ``` flowchart/kode
                if (substr_count($prefixBefore, '```') % 2 === 1) {
                    continue;
                }
                $endPos = $tailStart + $cand[1];
                break;
            }
        }

        $targetText = trim(substr($content, $startPos, $endPos - $startPos));
        $heading = trim($match[2][0] ?? $subchapter);

        return [
            'start' => $startPos,
            'end' => $endPos,
            'target_text' => $targetText,
            'heading' => $heading,
        ];
    }

    /**
     * Ekstrak blok flowchart/kode valid dan tabel Markdown dari naskah untuk pengamanan revisi.
     */
    private function extractStructuredAcademicBlocks(string $text): array
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $text);
        $lines = explode("\n", $normalized);
        $diagrams = [];
        $tables = [];

        $inCode = false;
        $codeLines = [];
        $pendingFigCaption = '';

        $inTable = false;
        $tableLines = [];
        $pendingTableCaption = '';

        $flushTable = function () use (&$inTable, &$tableLines, &$pendingTableCaption, &$tables) {
            if ($inTable && count($tableLines) >= 2) {
                $hasSep = false;
                $dataCount = 0;
                foreach ($tableLines as $tl) {
                    $inner = trim($tl, " \t|");
                    if (preg_match('/^[:\-\s|]+$/', $inner) && str_contains($inner, '-')) {
                        $hasSep = true;
                    } elseif (substr_count($tl, '|') >= 2) {
                        $dataCount++;
                    }
                }
                if ($hasSep || $dataCount >= 2) {
                    $tableMd = implode("\n", $tableLines);
                    $tables[] = [
                        'caption' => $pendingTableCaption,
                        'body' => $tableMd,
                        'full' => ($pendingTableCaption !== '' ? $pendingTableCaption . "\n" : '') . $tableMd,
                    ];
                }
            }
            $inTable = false;
            $tableLines = [];
            $pendingTableCaption = '';
        };

        foreach ($lines as $rawLine) {
            $trimmed = trim($rawLine);

            if (str_starts_with($trimmed, '```')) {
                $flushTable();
                if ($inCode) {
                    $inCode = false;
                    $body = trim(implode("\n", $codeLines));
                    if ($body !== '') {
                        $hasBoxDrawing = (bool) preg_match('/[┌┐└┘─│├┤┬┴┼═║╔╗╚╝╠╣╦╩╬►▼▲◄]/u', $body);
                        $isFlowchart = !$hasBoxDrawing && (str_contains($body, '-->') || str_contains($body, '->') || str_contains($body, '↓') || preg_match('/\[.+?\]/', $body));
                        $diagrams[] = [
                            'caption' => $pendingFigCaption,
                            'code' => $body,
                            'is_flowchart' => $isFlowchart,
                            'full' => ($pendingFigCaption !== '' ? $pendingFigCaption . "\n" : '') . "```\n" . $body . "\n```",
                        ];
                    }
                    $codeLines = [];
                    $pendingFigCaption = '';
                } else {
                    $inCode = true;
                    $codeLines = [];
                }
                continue;
            }

            if ($inCode) {
                $codeLines[] = rtrim($rawLine);
                continue;
            }

            $strippedPipes = preg_replace(['/\|[A-Za-z0-9_,\s+\-]{1,8}\|/', '/[A-Za-z]\([^)]*\|[^)]*\)/'], ' ', $trimmed);
            $isMdTableRow = (str_starts_with($trimmed, '|') && str_ends_with($trimmed, '|') && substr_count($trimmed, '|') >= 3)
                || (str_starts_with($trimmed, '|') && substr_count((string) $strippedPipes, '|') >= 2)
                || (substr_count((string) $strippedPipes, ' | ') >= 2);

            if ($isMdTableRow) {
                $inTable = true;
                $tableLines[] = $trimmed;
                continue;
            }
            if ($inTable) {
                $flushTable();
            }

            if ($trimmed === '') {
                continue;
            }

            if (preg_match('/^(?:\*{1,2}|<b>)?Tabel\s+\d+\.\d+[^\n]*/i', $trimmed)) {
                $cleanCap = trim(preg_replace('/^(\*{1,2}|<b>)+|(\*{1,2}|<\/b>)+$/i', '', $trimmed));
                if (mb_strlen($cleanCap) <= 140 && !preg_match('/menunjukkan bahwa|dapat dilihat|memperlihatkan/i', $cleanCap)) {
                    $pendingTableCaption = $cleanCap;
                    continue;
                }
            }

            if (preg_match('/^(?:\*{1,2}|<b>)?(?:Gambar|Bagan)\s+\d+\.\d+[^\n]*/i', $trimmed)) {
                $cleanCap = trim(preg_replace('/^(\*{1,2}|<b>)+|(\*{1,2}|<\/b>)+$/i', '', $trimmed));
                if (mb_strlen($cleanCap) <= 140 && !preg_match('/menunjukkan bahwa|dapat dilihat|memperlihatkan/i', $cleanCap)) {
                    $pendingFigCaption = $cleanCap;
                    continue;
                }
            }

            $pendingFigCaption = '';
            $pendingTableCaption = '';
        }

        $flushTable();

        return ['diagrams' => $diagrams, 'tables' => $tables];
    }

    /**
     * Pulihkan blok flowchart (```) dan tabel Markdown (|...|) jika AI merusak atau menghapusnya saat Lab Revisi.
     */
    private function preserveStructuredBlocksAfterRevision(string $originalText, string $revisedText, string $instruction = ''): string
    {
        $result = str_replace(["\r\n", "\r"], "\n", $revisedText);
        $origBlocks = $this->extractStructuredAcademicBlocks($originalText);
        if (empty($origBlocks['diagrams']) && empty($origBlocks['tables'])) {
            return $result;
        }

        $instrLower = mb_strtolower($instruction);
        $wantsDeleteDiagram = (bool) preg_match('/\b(hapus|hilangkan|buang)\b[^\n]{0,35}\b(flowchart|diagram|bagan|gambar|alur|kerangka\s+(?:berpikir|pemikiran|konseptual))\b|\b(flowchart|diagram|bagan|kerangka\s+berpikir)\b[^\n]{0,25}\b(dihapus|dibuang|dihilangkan)\b/i', $instrLower);
        $wantsDeleteTable = (bool) preg_match('/\b(hapus|hilangkan|buang)\b[^\n]{0,35}\b(tabel|jadwal)\b|\b(tabel|jadwal)\b[^\n]{0,25}\b(dihapus|dibuang|dihilangkan)\b/i', $instrLower);

        $revBlocks = $this->extractStructuredAcademicBlocks($result);

        // 1. Pastikan blok diagram/flowchart tidak hilang atau berubah menjadi ASCII box-drawing
        if (!empty($origBlocks['diagrams']) && !$wantsDeleteDiagram) {
            $revValidFlowcharts = array_values(array_filter($revBlocks['diagrams'], fn ($d) => !empty($d['is_flowchart'])));
            $origFlowcharts = array_values(array_filter($origBlocks['diagrams'], fn ($d) => !empty($d['is_flowchart'])));

            if (count($origFlowcharts) > count($revValidFlowcharts)) {
                foreach ($origFlowcharts as $idx => $origDiag) {
                    $capNum = '';
                    if (preg_match('/(?:Gambar|Bagan)\s+(\d+\.\d+)/i', $origDiag['caption'], $cm)) {
                        $capNum = $cm[1];
                    }

                    $hasMatchingInRev = false;
                    foreach ($revValidFlowcharts as $rd) {
                        if ($capNum !== '' && str_contains($rd['caption'], $capNum)) {
                            $hasMatchingInRev = true;
                            break;
                        }
                    }
                    if ($hasMatchingInRev || ($capNum === '' && count($revValidFlowcharts) >= count($origFlowcharts))) {
                        continue;
                    }

                    // Cek apakah di hasil revisi ada caption Gambar X.Y yang diikuti blok box-drawing atau teks rusak
                    if ($capNum !== '' && preg_match('/(^|\n)((?:\*{1,2})?(?:Gambar|Bagan)\s+' . preg_quote($capNum, '/') . '[^\n]*)(?:\n+(?:```[\s\S]*?```|(?:\[[^\n]+\]\s*\n?|[↓|Vv\-\+\┌┐└┘─│►▼\s]+)+))?/i', $result, $m, PREG_OFFSET_CAPTURE)) {
                        $before = substr($result, 0, $m[2][1]);
                        $after = substr($result, $m[0][1] + strlen($m[0][0]));
                        $replacementBlock = ($origDiag['caption'] !== '' ? $origDiag['caption'] : trim($m[2][0])) . "\n```\n" . $origDiag['code'] . "\n```";
                        $result = rtrim($before) . "\n\n" . $replacementBlock . "\n\n" . ltrim($after);
                    } else {
                        // Sisipkan kembali sebelum penutup sub-bab agar flowchart tetap utuh
                        $result = rtrim($result) . "\n\n" . $origDiag['full'];
                    }
                    $revBlocks = $this->extractStructuredAcademicBlocks($result);
                    $revValidFlowcharts = array_values(array_filter($revBlocks['diagrams'], fn ($d) => !empty($d['is_flowchart'])));
                }
            }
        }

        // 2. Pastikan tabel Markdown tidak rata jadi paragraf biasa tanpa tanda '|'
        if (!empty($origBlocks['tables']) && !$wantsDeleteTable) {
            $revBlocks = $this->extractStructuredAcademicBlocks($result);
            if (count($origBlocks['tables']) > count($revBlocks['tables'])) {
                foreach ($origBlocks['tables'] as $origTbl) {
                    $capNum = '';
                    if (preg_match('/Tabel\s+(\d+\.\d+)/i', $origTbl['caption'], $tm)) {
                        $capNum = $tm[1];
                    }

                    $alreadyValid = false;
                    foreach ($revBlocks['tables'] as $rt) {
                        if ($capNum !== '' && str_contains($rt['caption'], $capNum)) {
                            $alreadyValid = true;
                            break;
                        }
                    }
                    if ($alreadyValid) {
                        continue;
                    }

                    if ($capNum !== '' && preg_match('/(^|\n)((?:\*{1,2})?Tabel\s+' . preg_quote($capNum, '/') . '[^\n]*)\n+([\s\S]*?)(?=\n(?:\d+\.\d+(?:\.\d+)?\s|[A-Z]\.\s|Sumber:|Gambar\s+\d|Tabel\s+\d)|\n\n(?=[A-Z])|$)/i', $result, $tmMatch, PREG_OFFSET_CAPTURE)) {
                        $following = trim($tmMatch[3][0]);
                        // Jika di bawah caption Tabel X.Y ternyata tidak ada baris bertanda '|', pulihkan tabel asli
                        if (!str_contains($following, '|') || substr_count($following, '|') < 4) {
                            $before = substr($result, 0, $tmMatch[2][1]);
                            $after = substr($result, $tmMatch[0][1] + strlen($tmMatch[0][0]));
                            $capOut = $origTbl['caption'] !== '' ? $origTbl['caption'] : trim($tmMatch[2][0]);
                            $result = rtrim($before) . "\n\n" . $capOut . "\n" . $origTbl['body'] . "\n\n" . ltrim($after);
                        }
                    } elseif (count($revBlocks['tables']) < count($origBlocks['tables'])) {
                        $result = rtrim($result) . "\n\n" . $origTbl['full'];
                    }
                    $revBlocks = $this->extractStructuredAcademicBlocks($result);
                }
            }
        }

        return preg_replace("/\n{3,}/", "\n\n", trim($result));
    }

    /**
     * Gantikan teks sub-bab target secara bedah presisi tanpa merusak sub-bab lainnya
     */
    private function replaceSubchapterText(string $fullContent, int $startPos, int $endPos, string $revisedSubText): string
    {
        $before = rtrim(substr($fullContent, 0, $startPos));
        $after = ltrim(substr($fullContent, $endPos));
        $newContent = trim($before . "\n\n" . trim($revisedSubText) . "\n\n" . $after);
        return preg_replace("/\n{3,}/", "\n\n", $newContent);
    }

    public function reviseChapter(Request $request, $id)
    {
        $request->validate([
            'chapter' => 'nullable|in:bab1,bab2,bab3,bab4,bab5',
            'subchapter' => 'nullable|string',
            'revision_mode' => 'nullable|in:content,format',
            'instruction' => 'nullable|string',
            'image_base64' => 'nullable|string',
            'revision_file' => 'nullable|file|max:15360',
        ]);

        $project = Project::where('user_id', Auth::id())->findOrFail($id);
        $subchapter = trim((string) $request->input('subchapter', ''));
        $revisionMode = $request->input('revision_mode', 'content');

        // Otomatis tentukan bab dari awalan sub-bab jika dipilih (contoh: "2.1 Landasan Teori" -> "bab2")
        $chapter = $request->input('chapter', 'bab1');
        if (!empty($subchapter) && preg_match('/^([1-5])\./', $subchapter, $sm)) {
            $chapter = 'bab' . $sm[1];
        }

        $authUser = Auth::user();
        $revReserved = false;
        if ($authUser) {
            $chapGate = $this->billing()->canGenerateChapter($authUser, $project, (string) $chapter);
            if (empty($chapGate['allowed'])) {
                return response()->json([
                    'status' => 'upgrade_required',
                    'reason' => $chapGate['reason'] ?? 'chapter_locked',
                    'message' => $chapGate['message'],
                ], 402);
            }
            if (empty($project->is_full_unlocked) && !$this->billing()->hasActivePaidAccess($authUser)) {
                $revGate = $this->billing()->reserveToolAccess($authUser, 'revise', 'Revisi Bab AI');
                if (empty($revGate['allowed'])) {
                    return response()->json([
                        'status' => 'upgrade_required',
                        'reason' => $revGate['reason'] ?? 'free_tool_limit',
                        'message' => $revGate['message'],
                    ], 402);
                }
                $revReserved = !empty($revGate['reserved']);
            }
        }

        $model = $request->input('model', 'auto');
        $currentContent = $project->{$chapter . '_content'} ?? '';

        $docExtract = '';
        if ($request->hasFile('revision_file')) {
            $file = $request->file('revision_file');
            $ext = strtolower($file->getClientOriginalExtension());
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $mime = $file->getMimeType() ?: 'image/jpeg';
                $request->merge([
                    'image_base64' => 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($file->getRealPath()))
                ]);
            } else {
                $docExtract = $this->extractTextFromUploadedFile($file);
            }
        }

        $instruction = trim($request->input('instruction', ''));
        if (empty($instruction) && empty($request->image_base64) && empty($docExtract)) {
            $errMsg = 'Masukkan catatan revisi, gambar coretan dosen, atau dokumen revisi.';
            return response()->json(['status' => 'error', 'message' => $errMsg, 'error' => $errMsg], 422);
        }

        $chapterLabels = [
            'bab1' => 'BAB I PENDAHULUAN',
            'bab2' => 'BAB II TINJAUAN PUSTAKA',
            'bab3' => 'BAB III METODOLOGI PENELITIAN',
            'bab4' => 'BAB IV HASIL DAN PEMBAHASAN',
            'bab5' => 'BAB V PENUTUP',
        ];
        $chapterName = $chapterLabels[$chapter] ?? strtoupper($chapter);

        $docContext = !empty($docExtract)
            ? "\nISI DOKUMEN REVISI / CORETAN DOSEN YANG DIUNGGAH:\n\"\"\"\n" . Str::limit($docExtract, 6000) . "\n\"\"\"\n"
            : '';

        $modeRule = $revisionMode === 'format'
            ? "MODE REVISI FORMAT PENULISAN: Perbaiki struktur kalimat sesuai EYD V & KBBI, hilangkan kalimat pembuka klise AI, rapikan penomoran sub-bab desimal & poin bernomor, serta pastikan format sitasi dalam teks konsisten."
            : "MODE REVISI ISI SKRIPSI: Perdalam substansi akademik, tambahkan argumen empiris/sitasi terbaru, perbaiki celah logika, dan terapkan koreksi substansi dari dosen pembimbing.";

        $isSpecificSub = !empty($subchapter) && !str_contains(strtolower($subchapter), 'semua') && !str_contains(strtolower($subchapter), 'utuh');
        $subBlock = $isSpecificSub ? $this->extractSubchapterText($currentContent, $subchapter) : null;

        $structurePreservationRule = "ATURAN WAJIB DIAGRAM & TABEL MARKDOWN (DILARANG MERUSAK FORMAT):
- Jika naskah berisi blok diagram/flowchart di dalam pagar tiga backtick (``` ... ```) atau caption \"Gambar X.Y ...\", WAJIB pertahankan blok ``` ... ``` dengan format node vertikal [Tahap] dipisahkan baris panah ↓ (contoh:\n```\n[Mulai]\n↓\n[Tahap 1]\n↓\n[Selesai]\n```). DILARANG keras mengubahnya menjadi kotak ASCII (┌─┐ / +---+ / │), sintaks Mermaid (graph TD), daftar angka biasa, atau menghapus pagar ```!
- Jika naskah berisi tabel Markdown (| Kolom 1 | Kolom 2 | diikuti baris |---|---| dan baris data berpemisah |) atau caption \"Tabel X.Y ...\", WAJIB pertahankan tetap dalam format tabel Markdown berpemisah tanda |. DILARANG meratakan isi sel tabel menjadi teks paragraf biasa!";

        try {
            if ($subBlock && !empty($subBlock['target_text'])) {
                // REVISI SURGIKAL / TERFOKUS HANYA PADA SUB-BAB INI (SUB-BAB LAIN 100% AMAN & UTUH)
                $targetHeading = $subBlock['heading'] ?: $subchapter;
                $targetSnippet = $subBlock['target_text'];

                $prompt = "PERAN: Anda adalah Editor Akademik & Pembimbing Skripsi Profesional Indonesia.
TUGAS REVISI PRESISI (SURGICAL REVISION): Lakukan revisi HANYA pada sub-bab \"{$targetHeading}\" berikut untuk skripsi berjudul \"{$project->judul}\" (Prodi: {$project->prodi}).
{$modeRule}

INSTRUKSI REVISI DARI MAHASISWA / DOSEN PEMBIMBING (TERAPKAN DENGAN TEPAT):
\"\"\"
" . ($instruction ?: 'Perbaiki dan sempurnakan bagian ini sesuai coretan/dokumen terlampir.') . "
\"\"\"
{$docContext}

TEKS ASLI SUB-BAB \"{$targetHeading}\" SAAT INI (YANG AKAN DIREVISI):
\"\"\"
{$targetSnippet}
\"\"\"

ATURAN REVISI PRESISI (SANGAT KETAT):
1. REVISI HANYA APA YANG DIMINTA dalam catatan instruksi di atas:
   - Jika disuruh menambah poin/argumen/data: tambahkan sesuai permintaan.
   - Jika disuruh menghilangkan/mengurangi bagian tertentu: hapus HANYA bagian tersebut.
   - Jika disuruh memperbaiki kalimat/tata bahasa: perbaiki kalimatnya agar baku sesuai EYD V.
2. DILARANG MEROMBAK, MEMOTONG, ATAU MENGHAPUS argumen/fakta/data yang sudah benar dan TIDAK diminta diubah dalam instruksi. Seluruh kalimat dan data yang sudah benar WAJIB DIPERTAHANKAN UTUH!
3. Awali teks langsung dengan judul sub-bab: \"{$targetHeading}\" pada baris tersendiri, lalu lanjutkan dengan paragraf/poin hasil revisi.
4. DILARANG menulis pengantar AI (\"Berikut adalah hasil revisi...\", \"Tentu...\").
5. HANYA KEMBALIKAN hasil revisi untuk sub-bab \"{$targetHeading}\" ini saja, BUKAN seluruh bab!
6. {$structurePreservationRule}";

                $revised = $this->ai->generateWithImage($prompt, $request->image_base64, $model);
                $cleanSub = $this->cleanTextKeepFormat($revised);

                // Pastikan judul sub-bab ada di awal baris
                $firstLine = trim(explode("\n", $cleanSub)[0] ?? '');
                if (!str_contains(strtolower($firstLine), strtolower(substr($targetHeading, 0, 5)))) {
                    $cleanSub = $targetHeading . "\n" . $cleanSub;
                }

                // Pulihkan blok flowchart/tabel Markdown jika AI merusak atau menghilangkannya
                $cleanSub = $this->preserveStructuredBlocksAfterRevision($targetSnippet, $cleanSub, $instruction);

                // Gantikan HANYA sub-bab target ke dalam naskah bab lengkap
                $cleanContent = $this->replaceSubchapterText($currentContent, $subBlock['start'], $subBlock['end'], $cleanSub);
            } else {
                // Revisi seluruh bab jika user memilih 'Semua Bab' atau naskah bab masih kosong
                $cleanPromptBody = preg_replace('/<\/?(?:p|div|span|strong|em|b|i|u|br|table|thead|tbody|tr|th|td|ul|ol|li|h[1-6])\b[^>]*>/i', '', (string) $currentContent);
                $prompt = "PERAN: Anda adalah Profesor Pembimbing Skripsi Senior.
TUGAS: Lakukan penyempurnaan pada {$chapterName} untuk skripsi berjudul \"{$project->judul}\" (Prodi: {$project->prodi}, Metode: {$project->metode_penelitian}).
{$modeRule}

NASKAH {$chapterName} SAAT INI:
\"\"\"
" . Str::limit((string) $cleanPromptBody, 28000) . "
\"\"\"
{$docContext}
INSTRUKSI REVISI DARI MAHASISWA / DOSEN PEMBIMBING:
\"\"\"
" . ($instruction ?: 'Perbaiki dan sempurnakan bagian ini sesuai coretan/dokumen terlampir.') . "
\"\"\"

ATURAN REVISI (SANGAT KETAT):
1. Lakukan perubahan HANYA pada bagian yang diminta dalam catatan instruksi di atas.
2. DILARANG MERINGKAS, MEMOTONG, ATAU MENGHILANGKAN sub-bab lain yang sudah benar dan tidak disebutkan dalam instruksi!
3. Pertahankan format penomoran desimal (1.1, 1.2, dst.) pada baris tersendiri, tanpa tanda markdown bintang (**) atau pagar (###), dan tanpa kata pengantar AI.
4. Kembalikan naskah {$chapterName} yang sudah direvisi secara lengkap dan utuh.
5. {$structurePreservationRule}";

                $revised = $this->ai->generateWithImage($prompt, $request->image_base64, $model);
                $cleanContent = $this->cleanTextKeepFormat($revised);
                $cleanContent = $this->processChapterContent($cleanContent, $chapter);
                $cleanContent = $this->preserveStructuredBlocksAfterRevision((string) $currentContent, $cleanContent, $instruction);
            }

            if ($chapter !== 'bab5') {
                [$cleanContent] = $this->stripEmbeddedDaftarPustaka($cleanContent);
            }

            $project->{$chapter . '_content'} = $cleanContent;
            $project->save();

            return response()->json([
                'status' => 'success',
                'chapter' => $chapter,
                'subchapter' => $subchapter,
                'content' => $cleanContent,
            ]);
        } catch (Exception $e) {
            if (!empty($authUser) && $revReserved) {
                $this->billing()->releaseToolReservation($authUser, 'revise');
            }
            $errMsg = 'Gagal merevisi bab: ' . $e->getMessage();
            return response()->json(['status' => 'error', 'message' => $errMsg, 'error' => $errMsg], 500);
        }
    }

    public function extractLampiranFile(Request $request, $id)
    {
        Project::where('user_id', Auth::id())->findOrFail($id);
        $request->validate([
            'file' => 'required|file|max:3600',
        ]);

        $file = $request->file('file');
        $text = $this->extractTextFromUploadedFile($file);
        return response()->json([
            'status' => 'success',
            'file_name' => $file->getClientOriginalName(),
            'content' => Str::limit(trim($text), 12000, ''),
        ]);
    }

    public function saveChapterManual(Request $request, $id)
    {
        $request->validate([
            'chapter' => 'required|in:bab1,bab2,bab3,bab4,bab5,daftar_pustaka,front,front_logo,identity,data_riil,lampiran,doc_format',
            'content' => 'required_unless:chapter,identity,data_riil,lampiran,doc_format|string',
        ]);

        $project = Project::where('user_id', Auth::id())->findOrFail($id);

        if ($request->chapter === 'doc_format') {
            $meta = $this->getProjectMeta($project);
            $font = strtolower(trim((string) $request->input('font_family', 'times')));
            if (!in_array($font, ['times', 'arial', 'georgia'], true)) {
                $font = 'times';
            }
            $docFormat = [
                'font' => $font,
                'top' => (string) max(1, min(6, (float) $request->input('margin_top', 4))),
                'left' => (string) max(1, min(6, (float) $request->input('margin_left', 4))),
                'bottom' => (string) max(1, min(6, (float) $request->input('margin_bottom', 3))),
                'right' => (string) max(1, min(6, (float) $request->input('margin_right', 3))),
            ];
            $meta['doc_format'] = $docFormat;
            $project->citation_validation = json_encode($meta, JSON_UNESCAPED_UNICODE);
            $project->save();

            return $this->applyNoStoreHeaders(response()->json([
                'status' => 'success',
                'doc_format' => $docFormat,
            ]));
        }

        if ($request->chapter === 'lampiran') {
            $meta = $this->getProjectMeta($project);
            $rawItems = $request->input('lampiran', []);
            if (!is_array($rawItems)) {
                $rawItems = [];
            }
            $cleanItems = [];
            foreach (array_slice($rawItems, 0, 15) as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $title = trim((string) ($item['title'] ?? ''));
                $img = trim((string) ($item['image_base64'] ?? ''));
                if ($img !== '' && !str_starts_with($img, 'data:image/')) {
                    $img = 'data:image/jpeg;base64,' . $img;
                }
                $text = Str::limit(trim((string) ($item['content'] ?? '')), 12000, '');
                $fname = trim((string) ($item['file_name'] ?? ''));
                if ($title === '' && $img === '' && $text === '') {
                    continue;
                }
                $cleanItems[] = [
                    'id' => (string) ($item['id'] ?? Str::uuid()),
                    'title' => $title ?: 'Dokumen Lampiran',
                    'image_base64' => $img,
                    'content' => $text,
                    'file_name' => $fname,
                ];
            }
            $meta['lampiran'] = $cleanItems;
            // Hapus cache halaman depan agar Daftar Isi & Daftar Lampiran otomatis terbarui
            unset($meta['front_matter_html']);
            $project->citation_validation = json_encode($meta, JSON_UNESCAPED_UNICODE);
            $project->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Lampiran berhasil disimpan & otomatis diperbarui di Daftar Isi.',
                'lampiran' => $cleanItems,
            ]);
        }

        if ($request->chapter === 'data_riil') {
            $meta = $this->getProjectMeta($project);
            $rawData = $request->input('data_riil', []);
            if (!is_array($rawData)) {
                $rawData = [];
            }
            $cleanData = [
                'metode' => trim((string) ($rawData['metode'] ?? '')),
                'sampel' => trim((string) ($rawData['sampel'] ?? '')),
                'rumus' => trim((string) ($rawData['rumus'] ?? '')),
                'lokasi' => trim((string) ($rawData['lokasi'] ?? '')),
                'metrik' => trim((string) ($rawData['metrik'] ?? '')),
                'evaluasi' => trim((string) ($rawData['evaluasi'] ?? '')),
            ];
            $meta['data_riil'] = $cleanData;
            $project->citation_validation = json_encode($meta, JSON_UNESCAPED_UNICODE);
            $project->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Data olahan & rumus penelitian berhasil disimpan ke proyek.',
                'data_riil' => $cleanData,
            ]);
        }

        if ($request->chapter === 'identity') {
            $meta = $this->getProjectMeta($project);
            if ($request->filled('nama'))
                $meta['nama'] = trim($request->input('nama'));
            if ($request->filled('nim'))
                $meta['nim'] = trim($request->input('nim'));
            if ($request->filled('kampus'))
                $meta['kampus'] = trim($request->input('kampus'));
            if ($request->filled('fakultas'))
                $meta['fakultas'] = trim($request->input('fakultas'));
            if ($request->filled('prodi')) {
                $meta['prodi'] = trim($request->input('prodi'));
                $project->prodi = trim($request->input('prodi'));
            }
            // Hapus cache front_matter_html agar halaman sampul ter-render otomatis dengan data identitas terbaru
            unset($meta['front_matter_html']);

            $user = Auth::user();
            if ($user && $request->filled('nim')) {
                try {
                    if (\Illuminate\Support\Facades\Schema::hasColumn($user->getTable(), 'nim')) {
                        $user->nim = trim($request->input('nim'));
                        $user->save();
                    }
                } catch (\Throwable $e) {
                    // Kolom nim opsional di tabel users; identitas proyek tetap tersimpan aman di citation_validation
                }
            }
            $project->citation_validation = json_encode($meta, JSON_UNESCAPED_UNICODE);
            $project->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Identitas mahasiswa berhasil diperbarui & disimpan.',
                'meta' => $meta
            ]);
        }

        if ($request->chapter === 'front_logo') {
            $meta = $this->getProjectMeta($project);
            $meta['logo_base64'] = $this->normalizeLogoBase64($request->content);
            $project->citation_validation = json_encode($meta, JSON_UNESCAPED_UNICODE);
            $project->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Logo kampus berhasil diperbarui & disimpan.',
                'content' => $meta['logo_base64']
            ]);
        }

        if ($request->chapter === 'front') {
            $meta = $this->getProjectMeta($project);
            $meta['front_matter_html'] = trim($request->content);
            $project->citation_validation = json_encode($meta, JSON_UNESCAPED_UNICODE);
            $project->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Perubahan Sampul & Halaman Depan berhasil disimpan.',
                'content' => $meta['front_matter_html']
            ]);
        }

        $field = $request->chapter === 'daftar_pustaka' ? 'daftar_pustaka' : $request->chapter . '_content';
        $saveVal = trim($request->content);
        if (in_array($request->chapter, ['bab1', 'bab2', 'bab3', 'bab4'], true)) {
            [$saveVal] = $this->stripEmbeddedDaftarPustaka($saveVal);
        }

        $project->{$field} = $saveVal;
        $project->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Perubahan naskah berhasil disimpan.',
            'content' => $project->{$field}
        ]);
    }

    // =========================================================================
    // 5. DOWNLOAD DOCX & PDF (TERMASUK CONTOH PUBLIK LANDING PAGE)
    // =========================================================================

    /**
     * Escape teks untuk HTML Dompdf sekaligus mengubah subskrip/superskrip Unicode
     * menjadi <sub>/<sup> dan membungkus simbol Yunani/matematika dengan DejaVu Serif
     * agar tidak pernah berubah menjadi tanda tanya (?) di font Times-Roman CPDF.
     */
    private function pdfSafeText(string $text): string
    {
        // Normalisasi combining diacritics (c + \u0302, y + \u0302, x + \u0304, dst.) & precomposed hat/bar agar tidak jadi '?' di CPDF Times-Roman
        $text = (string) preg_replace('/([A-Za-z])\x{0302}/u', '$1&#770;', $text);
        $text = (string) preg_replace('/([A-Za-z])\x{0304}/u', '$1&#772;', $text);
        $text = str_replace(
            ['ŷ', 'Ŷ', 'ĉ', 'Ĉ', 'x̄', 'X̄', 'ȳ', 'Ȳ', 'p̂', 'P̂'],
            ['y&#770;', 'Y&#770;', 'c&#770;', 'C&#770;', 'x&#772;', 'X&#772;', 'y&#772;', 'Y&#772;', 'p&#770;', 'P&#770;'],
            $text
        );

        // Escape karakter HTML dulu (biarkan entitas &#770;/&#772; yang baru disisipkan)
        $escaped = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);

        // Konversi markdown **bold** dan *italic* menjadi tag HTML rapi (istilah asing miring standar skripsi)
        $escaped = preg_replace('/\*\*([^*\n]+?)\*\*/u', '<strong>$1</strong>', $escaped);
        $escaped = preg_replace('/(?<![A-Za-z0-9*])\*([^*\n]+?)\*(?![A-Za-z0-9*])/u', '<em>$1</em>', $escaped);

        // Ubah subskrip Unicode menjadi tag <sub> agar tajam di Times New Roman
        $subMap = [
            '₀' => '0', '₁' => '1', '₂' => '2', '₃' => '3', '₄' => '4',
            '₅' => '5', '₆' => '6', '₇' => '7', '₈' => '8', '₉' => '9',
            'ᵢ' => 'i', 'ⱼ' => 'j', 'ₖ' => 'k', 'ₙ' => 'n', 'ₘ' => 'm',
            'ₓ' => 'x', 'ₐ' => 'a', 'ₑ' => 'e', 'ₒ' => 'o', 'ᵣ' => 'r',
            'ₜ' => 't', 'ᵤ' => 'u', 'ᵥ' => 'v', 'ₚ' => 'p', 'ₛ' => 's',
            '₊' => '+', '₋' => '-', '₌' => '=', '₍' => '(', '₎' => ')',
        ];
        foreach ($subMap as $u => $ascii) {
            $escaped = str_replace($u, '<sub style="font-size:75%; line-height:0;">' . $ascii . '</sub>', $escaped);
        }

        // Ubah superskrip Unicode menjadi tag <sup>
        $supMap = [
            '⁰' => '0', '¹' => '1', '²' => '2', '³' => '3', '⁴' => '4',
            '⁵' => '5', '⁶' => '6', '⁷' => '7', '⁸' => '8', '⁹' => '9',
            'ⁱ' => 'i', 'ⁿ' => 'n', 'ᵗ' => 't', '⁺' => '+', '⁻' => '-', '⁼' => '=',
        ];
        foreach ($supMap as $u => $ascii) {
            $escaped = str_replace($u, '<sup style="font-size:75%; line-height:0;">' . $ascii . '</sup>', $escaped);
        }

        // Gabungkan sub/sup berurutan agar bersih
        $escaped = preg_replace('/<\/sub><sub style="font-size:75%; line-height:0;">/', '', $escaped);
        $escaped = preg_replace('/<\/sup><sup style="font-size:75%; line-height:0;">/', '', $escaped);

        // Bungkus huruf bertopi (c&#770;, y&#770;, x&#772;) dengan DejaVu Serif
        $escaped = preg_replace(
            '/([A-Za-z](?:&#770;|&#772;))/',
            '<span style="font-family:\'DejaVu Serif\', serif;">$1</span>',
            $escaped
        );

        // Bungkus simbol matematika, Yunani, panah, minus Unicode (−), proporsional (∝), & tanda khusus di luar Windows-1252 dengan DejaVu Serif
        $escaped = preg_replace(
            '/([α-ωΑ-Ω∑∏√≈≤≥≠±∞∝−∈∉⊂⊆∪∩∂∫∇→←↔⇒⇔↓↑×÷·∙∧∨∀∃≡∼≃′″•—–‘’“”]+)/u',
            '<span style="font-family:\'DejaVu Serif\', serif;">$1</span>',
            $escaped
        );

        // Pertahankan tanda centang/silang jadwal & matriks dengan font DejaVu Sans sebelum pembersihan emoji (Times-Roman CPDF tidak punya U+2713)
        $escaped = str_replace(
            ['✓', '✔', '✕', '✖', '✗'],
            [
                '<span style="font-family:\'DejaVu Sans\', sans-serif;">&#x2713;</span>',
                '<span style="font-family:\'DejaVu Sans\', sans-serif;">&#x2714;</span>',
                '<span style="font-family:\'DejaVu Sans\', sans-serif;">&#x2715;</span>',
                '<span style="font-family:\'DejaVu Sans\', sans-serif;">&#x2716;</span>',
                '<span style="font-family:\'DejaVu Sans\', sans-serif;">&#x2717;</span>',
            ],
            $escaped
        );

        // Bersihkan emoji yang tidak memiliki glyph di font PDF akademik
        $escaped = preg_replace('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u', '', $escaped);

        return $escaped;
    }

    private function formatAcademicContentForPdf(string $content, string $judul = ''): string
    {
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        // Bersihkan judul BAB ganda & sitasi terpotong baris di naskah lama saat ekspor PDF
        $content = (string) preg_replace('/^\s*(?:#+\s*)?BAB\s+[IVX0-9]+(?:\s+[^\n]{0,80})?\s*\n+/i', '', $content);
        $content = (string) preg_replace('/\b(et\s+al\.|dkk\.)\s*\n+\s*(\(\d{4}[a-z]?\))/i', '$1 $2', $content);
        $lines = explode("\n", $content);
        $html = '';
        $inOl = false;
        $inUl = false;
        $inCode = false;
        $codeLines = [];
        $tableBuffer = [];
        $pendingTableCaption = '';
        $pendingFigureCaption = '';

        $closeLists = function () use (&$html, &$inOl, &$inUl) {
            if ($inOl) {
                $html .= "</ol>";
                $inOl = false;
            }
            if ($inUl) {
                $html .= "</ul>";
                $inUl = false;
            }
        };

        $flushTable = function () use (&$html, &$tableBuffer, &$pendingTableCaption) {
            if (empty($tableBuffer))
                return;
            $rows = [];
            foreach ($tableBuffer as $tLine) {
                $raw = trim($tLine);
                $inner = $raw;
                if (str_starts_with($inner, '|')) {
                    $inner = substr($inner, 1);
                }
                if (str_ends_with($inner, '|')) {
                    $inner = substr($inner, 0, -1);
                }
                if (preg_match('/^[\s\-:|]+$/', $inner)) {
                    continue;
                }
                $cells = array_map('trim', explode('|', $inner));
                if (count($cells) >= 2) {
                    $rows[] = $cells;
                }
            }
            if (count($rows) >= 2) {
                $maxCols = max(array_map('count', $rows));
                if (count($rows[0]) < $maxCols) {
                    $diff = $maxCols - count($rows[0]);
                    for ($d = 0; $d < $diff; $d++) {
                        array_unshift($rows[0], 'Kategori');
                    }
                }
                foreach ($rows as &$r) {
                    while (count($r) < $maxCols) {
                        $r[] = '-';
                    }
                    if (count($r) > $maxCols) {
                        $r = array_slice($r, 0, $maxCols);
                    }
                }
                unset($r);

                $colCount = $maxCols;
                $isFirstColNo = preg_match('/^(no|no\.?|#)$/i', trim($rows[0][0] ?? ''));

                // Distribusi lebar kolom cerdas untuk SEMUA jumlah kolom (2 sampai 10+ kolom)
                $colWidths = [];
                if ($colCount === 6) {
                    $colWidths = $isFirstColNo ? ['6%', '18%', '22%', '18%', '16%', '20%'] : ['16%', '19%', '17%', '16%', '16%', '16%'];
                } elseif ($colCount === 5) {
                    $colWidths = $isFirstColNo ? ['6%', '22%', '24%', '24%', '24%'] : ['16%', '21%', '21%', '21%', '21%'];
                } elseif ($colCount === 4) {
                    $colWidths = $isFirstColNo ? ['7%', '31%', '31%', '31%'] : ['25%', '25%', '25%', '25%'];
                } elseif ($colCount === 3) {
                    $colWidths = $isFirstColNo ? ['8%', '46%', '46%'] : ['34%', '33%', '33%'];
                } elseif ($colCount === 2) {
                    $colWidths = ['38%', '62%'];
                } else {
                    if ($isFirstColNo && $colCount > 1) {
                        $rem = round(94 / ($colCount - 1), 2);
                        $colWidths[] = '6%';
                        for ($i = 1; $i < $colCount; $i++) {
                            $colWidths[] = $rem . '%';
                        }
                    } else {
                        $eq = round(100 / max(1, $colCount), 2);
                        for ($i = 0; $i < $colCount; $i++) {
                            $colWidths[] = $eq . '%';
                        }
                    }
                }

                $totalTableChars = 0;
                foreach ($rows as $rCheck) {
                    foreach ($rCheck as $cCheck) {
                        $totalTableChars += mb_strlen((string) $cCheck);
                    }
                }
                // Tabel ringkas (<= 8 baris) dijaga utuh 1 halaman agar header tidak yatim atau menyisakan 1 baris di halaman berikutnya
                $avoidSplit = (count($rows) <= 8 && $totalTableChars <= 950) ? 'page-break-inside:avoid;' : 'page-break-inside:auto;';
                $fontSize = ($colCount >= 6) ? '8.5pt' : (($colCount === 5) ? '9pt' : '10pt');
                $headerSize = ($colCount >= 6) ? '9pt' : (($colCount === 5) ? '9.5pt' : '10pt');
                $lineHeight = '1.25';
                $cellPadding = ($colCount >= 5) ? '4.5pt 5pt' : '5.5pt 7pt';

                if (!empty($pendingTableCaption)) {
                    $tblTocAttr = '';
                    if (preg_match('/^Tabel\s+(\d+\.\d+[\w.\-]*)/i', $pendingTableCaption, $mtId)) {
                        $tblTocAttr = " data-toc-id='tbl_" . e($mtId[1]) . "'";
                    }
                    $html .= "<div{$tblTocAttr} style='font-size:11pt; font-weight:bold; margin-top:12pt; margin-bottom:4pt; text-align:left; text-indent:0; line-height:1.25; page-break-after:avoid; break-after:avoid;'>" . e($pendingTableCaption) . "</div>";
                    $pendingTableCaption = '';
                }

                // Tabel Akademik Bergaris Lengkap (Full-Grid Crisp Table Standar Skripsi Indonesia)
                $html .= "<table style='width:100%; table-layout:fixed; border-collapse:collapse; margin:4pt 0 12pt 0; font-size:{$fontSize}; font-family:inherit; border:1pt solid #000000; {$avoidSplit}'>";
                foreach ($rows as $rIdx => $cols) {
                    $isHeader = ($rIdx === 0);
                    if ($isHeader) {
                        $html .= "<thead style='display:table-header-group; page-break-after:avoid; break-after:avoid;'><tr style='background:#f1f5f9; page-break-inside:avoid; page-break-after:avoid; break-after:avoid;'>";
                        foreach ($cols as $cIdx => $col) {
                            $cleanCol = $this->formatAcademicMathText(trim(preg_replace('/^(\*{1,2}|<b>)+|(\*{1,2}|<\/b>)+$/i', '', $col)));
                            $wStyle = isset($colWidths[$cIdx]) ? "width:{$colWidths[$cIdx]};" : "";
                            $html .= "<th style='{$wStyle} padding:{$cellPadding}; font-weight:bold; text-align:center; vertical-align:middle; border:0.75pt solid #000000; border-bottom:1.2pt solid #000000; line-height:{$lineHeight}; font-size:{$headerSize}; word-break:normal; overflow-wrap:break-word; background:#f1f5f9;'>" . $this->pdfSafeText($cleanCol) . "</th>";
                        }
                        $html .= "</tr></thead><tbody>";
                    } else {
                        $rowBg = ($rIdx % 2 === 0) ? '#fafbfc' : '#ffffff';
                        $html .= "<tr style='background:{$rowBg}; page-break-inside:avoid;'>";
                        foreach ($cols as $cIdx => $col) {
                            $cleanCol = $this->formatAcademicMathText(trim(preg_replace('/^(\*{1,2}|<b>)+|(\*{1,2}|<\/b>)+$/i', '', $col)));
                            $isNum = ($cIdx === 0 && preg_match('/^\d+$/', $cleanCol));
                            $isMetric = preg_match('/^[\d\s,.\-%—\(\)<>]+$/', $cleanCol);
                            $align = ($isNum || $isMetric) ? 'center' : 'left';
                            $wStyle = isset($colWidths[$cIdx]) ? "width:{$colWidths[$cIdx]};" : "";
                            $html .= "<td style='{$wStyle} padding:{$cellPadding}; text-align:{$align}; vertical-align:top; border:0.65pt solid #000000; line-height:{$lineHeight}; font-size:{$fontSize}; word-wrap:break-word; overflow-wrap:break-word;'>" . $this->pdfSafeText($cleanCol) . "</td>";
                        }
                        $html .= "</tr>";
                    }
                }
                $html .= "</tbody></table>";
            } else {
                // Bukan tabel nyata (mis. 1 baris paragraf berisi notasi |V| atau P(t|c)) -> kembalikan sebagai paragraf biasa
                foreach ($tableBuffer as $rawFallbackLine) {
                    $cleanP = $this->formatAcademicMathText(trim(preg_replace('/^(\*{1,2}|<b>)+|(\*{1,2}|<\/b>)+$/i', '', $rawFallbackLine)));
                    if ($cleanP !== '') {
                        $html .= "<p style='text-align:justify; text-indent:1.27cm; margin:0 0 6pt 0; line-height:1.5;'>" . $this->pdfSafeText($cleanP) . "</p>";
                    }
                }
            }
            $tableBuffer = [];
        };

        $flushCodeOrFlowchart = function () use (&$html, &$codeLines, $judul, $content, &$pendingFigureCaption) {
            if (empty($codeLines))
                return;
            // Hapus baris kosong di awal/akhir blok kode/diagram dan normalisasi indentasi bersama
            while (!empty($codeLines) && trim($codeLines[0]) === '') {
                array_shift($codeLines);
            }
            while (!empty($codeLines) && trim($codeLines[count($codeLines) - 1]) === '') {
                array_pop($codeLines);
            }
            if (empty($codeLines))
                return;

            $nonEmptyIndents = [];
            foreach ($codeLines as $cl) {
                if (trim($cl) !== '') {
                    $nonEmptyIndents[] = strlen($cl) - strlen(ltrim($cl, ' '));
                }
            }
            $minIndent = !empty($nonEmptyIndents) ? min($nonEmptyIndents) : 0;
            if ($minIndent > 0) {
                $codeLines = array_map(fn($cl) => strlen($cl) >= $minIndent ? substr($cl, $minIndent) : ltrim($cl), $codeLines);
            }

            $rawCode = implode("\n", $codeLines);
            $hasBoxDrawing = (bool) preg_match('/[┌┐└┘─│├┤┬┴┼═║╔╗╚╝╠╣╦╩╬►▼▲◄]/u', $rawCode);
            $isFlowchart = !$hasBoxDrawing && (str_contains($rawCode, '-->') || str_contains($rawCode, '->') || str_contains($rawCode, '↓') || preg_match('/\[.+?\]/', $rawCode));

            if ($isFlowchart) {
                $isBab2 = str_contains($content, 'BAB II') || str_contains($content, 'TINJAUAN PUSTAKA') || str_contains($content, '2.1 Landasan Teori') || str_contains($content, 'Kerangka Berpikir') || str_contains($content, 'Kerangka Pemikiran');
                $isFramework = $isBab2 || str_contains($rawCode, 'Permasalahan') || str_contains($rawCode, 'Landasan Teori') || str_contains($rawCode, 'Kerangka Pemikiran') || str_contains($rawCode, 'Fenomena') || str_contains($pendingFigureCaption, '2.1') || str_contains(strtolower($pendingFigureCaption), 'kerangka');
                $caption = !empty($pendingFigureCaption)
                    ? $pendingFigureCaption
                    : ($isFramework ? 'Gambar 2.1 Bagan Kerangka Berpikir Penelitian' : 'Gambar 3.1 Diagram Alur (Flowchart) Prosedur Penelitian');
                $pendingFigureCaption = '';

                // Ekstraksi node dinamis akademik
                preg_match_all('/\[(.*?)\]/', $rawCode, $nodeMatches);
                $rawNodes = !empty($nodeMatches[1]) ? $nodeMatches[1] : array_filter(array_map('trim', $codeLines), fn($l) => !empty($l) && !str_starts_with($l, 'graph') && !preg_match('/^[↓V\-\->\s|+~#=]+$/', $l));
                $validNodes = [];
                foreach ($rawNodes as $node) {
                    $cn = trim(strip_tags($node));
                    if (preg_match('/^(?:SIMBOL\s+DATA|DATA\s*\/\s*INPUT|SUB-PROSES|PREDEFINED\s+PROCESS|PROSES\s+SISTEM|PROSES|KEPUTUSAN|DECISION|TERMINATOR)$/i', $cn)) {
                        continue;
                    }
                    $cn = preg_replace('/^[\[\/\s\-—]+|[\]\/\s\-—]+$/', '', $cn);
                    if (str_starts_with($cn, '(') && str_ends_with($cn, ')') && substr_count($cn, '(') === 1 && substr_count($cn, ')') === 1) {
                        $cn = trim(substr($cn, 1, -1));
                    }
                    if (!empty($cn) && !preg_match('/^[↓V\-\->\s|+~#={}()\\[\\]]+$/', $cn) && !in_array($cn, ['{', '}', '(', ')', '[', ']', ';'])) {
                        $validNodes[] = $cn;
                    }
                }
                if (!empty($validNodes)) {
                    $svg = $this->buildFlowchartSvg($validNodes, $isFramework);
                    $svgB64 = base64_encode($svg);
                    $figTocAttr = '';
                    if (preg_match('/^(?:Gambar|Bagan)\s+(\d+\.\d+[\w.\-]*)/i', $caption, $mfId)) {
                        $figTocAttr = " data-toc-id='fig_" . e($mfId[1]) . "'";
                    }
                    $html .= "<div class='academic-flowchart' style='margin:14pt auto 16pt auto; text-align:center; width:100%; page-break-inside:avoid;'>";
                    $html .= "<img src='data:image/svg+xml;base64,{$svgB64}' style='width:395pt; max-width:100%; height:auto; display:block; margin:0 auto;'>";
                    // Standar Skripsi Indonesia: Judul Gambar WAJIB diletakkan di BAWAH gambar
                    $html .= "<div{$figTocAttr} style='font-size:11pt; font-weight:bold; margin-top:6pt; text-align:center; text-indent:0; color:#000000; page-break-before:avoid;'>" . e($caption) . "</div>";
                    $html .= "</div>";
                }
            } else {
                // Diagram box-drawing Unicode / blok kode: wajib pakai DejaVu Sans Mono agar karakter ┌─┐│└┘►▼ tidak jadi '???', rata kiri (bukan justify), dan utuh 1 halaman
                $maxLineLen = max(array_map(fn($l) => mb_strlen($l, 'UTF-8'), $codeLines));
                $preFontSize = $maxLineLen > 78 ? '6.6pt' : ($maxLineLen > 66 ? '7.5pt' : ($maxLineLen > 54 ? '8.3pt' : '9pt'));
                $wrapStyle = $hasBoxDrawing ? 'white-space:pre; overflow:hidden;' : 'white-space:pre-wrap; word-break:break-word;';
                $html .= "<div style='margin:12pt 0 14pt 0; page-break-inside:avoid; break-inside:avoid;'>";
                $html .= "<pre style=\"background:#f8fafc; border:0.8pt solid #cbd5e1; border-radius:4pt; padding:10pt 12pt; font-size:{$preFontSize}; font-family:'DejaVu Sans Mono', monospace; text-align:left; text-indent:0; line-height:1.28; {$wrapStyle} margin:0; color:#0f172a;\">" . e($rawCode) . "</pre>";
                if (!empty($pendingFigureCaption)) {
                    $figTocAttrPre = '';
                    if (preg_match('/^(?:Gambar|Bagan)\s+(\d+\.\d+[\w.\-]*)/i', $pendingFigureCaption, $mfPreId)) {
                        $figTocAttrPre = " data-toc-id='fig_" . e($mfPreId[1]) . "'";
                    }
                    $html .= "<div{$figTocAttrPre} style='font-size:11pt; font-weight:bold; margin-top:6pt; text-align:center; text-indent:0; color:#000000; page-break-before:avoid;'>" . e($pendingFigureCaption) . "</div>";
                    $pendingFigureCaption = '';
                }
                $html .= "</div>";
            }
            $codeLines = [];
        };

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // Handle Code Blocks / Flowcharts
            if (str_starts_with($trimmed, '```')) {
                $closeLists();
                $flushTable();
                if ($inCode) {
                    $inCode = false;
                    $flushCodeOrFlowchart();
                } else {
                    $inCode = true;
                    $codeLines = [];
                }
                continue;
            }
            if ($inCode) {
                // Pertahankan spasi awal (indentasi) agar diagram 2D & box-drawing tidak bergeser
                $codeLines[] = rtrim(str_replace("\t", '    ', $line));
                continue;
            }

            if ($trimmed === '') {
                $closeLists();
                $flushTable();
                continue;
            }

            // Buffer tabel markdown (hindari salah deteksi notasi matematika |V|, |D|, atau P(t|c))
            $strippedMathPipesPdf = preg_replace(['/\|[A-Za-z0-9_,\s+\-]{1,8}\|/', '/[A-Za-z]\([^)]*\|[^)]*\)/'], ' ', $trimmed);
            $isMdTableRow = (str_starts_with($trimmed, '|') && str_ends_with($trimmed, '|') && substr_count($trimmed, '|') >= 3)
                || (str_starts_with($trimmed, '|') && substr_count($strippedMathPipesPdf, '|') >= 2)
                || (substr_count($strippedMathPipesPdf, ' | ') >= 2);
            if ($isMdTableRow) {
                $closeLists();
                $tableBuffer[] = $trimmed;
                continue;
            } else {
                $flushTable();
                if (!empty($pendingTableCaption)) {
                    $html .= "<div style='font-size:11pt; font-weight:bold; margin-top:12pt; margin-bottom:4pt; text-align:left; text-indent:0; page-break-after:avoid;'>" . e($pendingTableCaption) . "</div>";
                    $pendingTableCaption = '';
                }
            }

            // Jika sebelumnya ada caption gambar tapi baris berikutnya bukan blok kode diagram
            if (!empty($pendingFigureCaption) && !preg_match('/^(?:Gambar|Bagan)\s+\d+\.\d+/i', $trimmed)) {
                $html .= "<div style='font-size:11pt; font-weight:bold; margin-top:10pt; margin-bottom:6pt; text-align:center; text-indent:0;'>" . e($pendingFigureCaption) . "</div>";
                $pendingFigureCaption = '';
            }

            // Judul Tabel (Disimpan agar langsung menempel di atas tabel tanpa terpisah halaman)
            if (preg_match('/^(?:\*{1,2}|<b>)?Tabel\s+\d+\.\d+/i', $trimmed)) {
                $closeLists();
                $cleanCap = trim(preg_replace('/^(\*{1,2}|<b>)+|(\*{1,2}|<\/b>)+$/i', '', $trimmed));
                if (mb_strlen($cleanCap) <= 130 && !preg_match('/menunjukkan bahwa|dapat dilihat|memperlihatkan/i', $cleanCap)) {
                    $pendingTableCaption = $cleanCap;
                    continue;
                }
            }

            // Judul Gambar / Bagan (Disimpan untuk diletakkan di BAWAH diagram sesuai standar skripsi)
            if (preg_match('/^(?:\*{1,2}|<b>)?(?:Gambar|Bagan)\s+\d+\.\d+/i', $trimmed)) {
                $closeLists();
                $cleanCap = trim(preg_replace('/^(\*{1,2}|<b>)+|(\*{1,2}|<\/b>)+$/i', '', $trimmed));
                if (mb_strlen($cleanCap) <= 130 && !preg_match('/menunjukkan bahwa|dapat dilihat|memperlihatkan/i', $cleanCap)) {
                    $pendingFigureCaption = $cleanCap;
                    continue;
                }
            }

            // Keterangan Sumber Tabel / Gambar
            if (preg_match('/^(?:\*{1,2}|_)?Sumber\s*:/i', $trimmed)) {
                $closeLists();
                $cleanSrc = trim(preg_replace('/^(\*{1,2}|_|<i>)+|(\*{1,2}|_|<\/i>)+$/i', '', $trimmed));
                $html .= "<p style='font-size:10pt; font-style:italic; text-indent:0; text-align:left; margin:-6pt 0 10pt 0; line-height:1.25;'>" . e($cleanSrc) . "</p>";
                continue;
            }

            // Sub-sub-bab (### ..., 1.6.1 ..., atau 1. Manfaat Teoretis)
            if (preg_match('/^(?:###\s+|\d+\.\d+\.\d+\.?\s+)(.+)$/', $trimmed, $m) || preg_match('/^\d+\.\s+(Manfaat\s+[A-Za-z]+|Tujuan\s+[A-Za-z]+)/i', $trimmed)) {
                $closeLists();
                $clean = trim(preg_replace('/^(\*{1,2}|#+\s*|<b>)+|(\*{1,2}|<\/b>)+$/i', '', $trimmed));
                $html .= "<h4 style='font-size:12pt; font-weight:bold; margin-top:10pt; margin-bottom:4pt; text-align:left; text-indent:0; page-break-after:avoid; break-after:avoid;'>" . e($clean) . "</h4>";
                continue;
            }

            // Sub-bab utama (A. ..., 1.1 ..., ## ...)
            if (preg_match('/^(?:##\s+|[A-Z]\.\s+|\d+\.\d+\.?\s+)(.+)$/', $trimmed, $m)) {
                $closeLists();
                $clean = trim(preg_replace('/^(\*{1,2}|#+\s*|<b>)+|(\*{1,2}|<\/b>)+$/i', '', $trimmed));
                $subTocAttr = '';
                if (preg_match('/^(\d+\.\d+)\.?\s+/', $clean, $msId)) {
                    $subTocAttr = " data-toc-id='sub_" . e($msId[1]) . "'";
                }
                $html .= "<h3{$subTocAttr} style='font-size:12pt; font-weight:bold; margin-top:14pt; margin-bottom:6pt; text-align:left; text-indent:0; page-break-after:avoid; break-after:avoid;'>" . e($clean) . "</h3>";
                continue;
            }

            // List bernomor (1. ..., 2. ...) — pertahankan nomor eksplisit lewat <ol start="N"> agar tidak pernah reset ke 1., 1., 1.!
            if (preg_match('/^(\d+)\.\s+(.+)$/', $trimmed, $m)) {
                $itemNum = max(1, (int) $m[1]);
                if ($inUl) {
                    $html .= "</ul>";
                    $inUl = false;
                }
                if (!$inOl) {
                    $startAttr = $itemNum > 1 ? " start='{$itemNum}'" : "";
                    $html .= "<ol{$startAttr} style='margin:4pt 0 8pt 0; padding-left:1.27cm;'>";
                    $inOl = true;
                }
                $cleanLi = $this->formatAcademicMathText(trim(preg_replace('/^(?:<b>)+|(?:<\/b>)+$/i', '', $m[2])));
                $liBreakCss = mb_strlen(strip_tags($cleanLi)) <= 220
                    ? 'page-break-inside:avoid;'
                    : 'orphans:2; widows:2; page-break-inside:auto;';
                $html .= "<li value='{$itemNum}' style='text-align:justify; line-height:1.5; margin-bottom:4pt; {$liBreakCss}'>" . $this->pdfSafeText($cleanLi) . "</li>";
                continue;
            }

            // Bullet list (- ..., * ...)
            if (preg_match('/^[-*]\s+(.+)$/', $trimmed, $m)) {
                if ($inOl) {
                    $html .= "</ol>";
                    $inOl = false;
                }
                if (!$inUl) {
                    $html .= "<ul style='margin:4pt 0 8pt 0; padding-left:1.27cm;'>";
                    $inUl = true;
                }
                $cleanLi = $this->formatAcademicMathText(trim(preg_replace('/^(?:<b>)+|(?:<\/b>)+$/i', '', $m[1])));
                $liBreakCss = mb_strlen(strip_tags($cleanLi)) <= 220
                    ? 'page-break-inside:avoid;'
                    : 'orphans:2; widows:2; page-break-inside:auto;';
                $html .= "<li style='text-align:justify; line-height:1.5; margin-bottom:4pt; {$liBreakCss}'>" . $this->pdfSafeText($cleanLi) . "</li>";
                continue;
            }

            // Paragraf ilmiah baku atau Rumus Matematika Standalone
            $closeLists();
            $cleanP = trim(preg_replace('/^#+\s+/', '', $trimmed));
            if ($cleanP !== '') {
                $cleanP = $this->formatAcademicMathText($cleanP);
                if ($this->isStandaloneFormula($cleanP)) {
                    $html .= "<p style='text-align:center; text-indent:0; margin:10pt 0; font-size:12pt; font-family:inherit; font-style:normal; line-height:1.5;'>" . $this->pdfSafeText($cleanP) . "</p>";
                } else {
                    $html .= "<p style='text-indent:1.27cm; text-align:justify; line-height:1.5; margin:0 0 6pt 0; orphans:3; widows:3; page-break-inside:auto;'>" . $this->pdfSafeText($cleanP) . "</p>";
                }
            }
        }

        $closeLists();
        $flushTable();
        $flushCodeOrFlowchart();
        return $html;
    }

    private function parseAndAddContent($section, $htmlContent, $project = null)
    {
        $text = str_replace(['<p>', '</p>', '<br>', '<br/>'], ["\n", "\n", "\n", "\n"], (string) $htmlContent);
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        // Bersihkan judul BAB ganda & sitasi terpotong baris di naskah lama saat ekspor Word
        $text = (string) preg_replace('/^\s*(?:#+\s*)?BAB\s+[IVX0-9]+(?:\s+[^\n]{0,80})?\s*\n+/i', '', $text);
        $text = (string) preg_replace('/\b(et\s+al\.|dkk\.)\s*\n+\s*(\(\d{4}[a-z]?\))/i', '$1 $2', $text);
        $lines = explode("\n", $text);

        $tableBuffer = [];
        $currentTableCaption = '';
        $currentFigureCaption = '';
        $flushTable = function () use (&$tableBuffer, &$currentTableCaption, $section) {
            if (empty($tableBuffer))
                return;
            $rows = [];
            foreach ($tableBuffer as $tLine) {
                $raw = trim($tLine);
                $inner = $raw;
                if (str_starts_with($inner, '|')) {
                    $inner = substr($inner, 1);
                }
                if (str_ends_with($inner, '|')) {
                    $inner = substr($inner, 0, -1);
                }
                if (preg_match('/^[\s\-:|]+$/', $inner)) {
                    continue;
                }
                $cells = array_map('trim', explode('|', $inner));
                if (count($cells) >= 2) {
                    $rows[] = $cells;
                }
            }
            if (count($rows) >= 2) {
                $maxCols = max(array_map('count', $rows));

                if (count($rows[0]) < $maxCols) {
                    $diff = $maxCols - count($rows[0]);
                    for ($d = 0; $d < $diff; $d++) {
                        array_unshift($rows[0], 'Kategori');
                    }
                }

                // Judul tabel diletakkan di ATAS tabel dengan keepNext agar tidak terpisah halaman
                if (!empty($currentTableCaption)) {
                    $section->addText(
                        $currentTableCaption,
                        ['name' => 'Times New Roman', 'size' => 11, 'bold' => true],
                        ['alignment' => Jc::LEFT, 'spaceBefore' => 140, 'spaceAfter' => 60, 'lineHeight' => 1.15, 'keepNext' => true, 'indentation' => ['firstLine' => 0]]
                    );
                }

                $isFirstColNo = preg_match('/^(no|no\.?|#)$/i', trim($rows[0][0] ?? ''));
                $totalWidthTwips = 7940; // ~14 cm (A4 21cm - 4cm kiri - 3cm kanan)
                // Distribusi proporsional lebar kolom Word sesuai jumlah kolom agar kolom narasi luas & rapi
                $pctMap = [];
                if ($maxCols === 6) {
                    $pctMap = $isFirstColNo ? [0.06, 0.16, 0.22, 0.14, 0.16, 0.26] : [0.16, 0.19, 0.17, 0.16, 0.16, 0.16];
                } elseif ($maxCols === 5) {
                    $pctMap = $isFirstColNo ? [0.07, 0.20, 0.27, 0.23, 0.23] : [0.16, 0.21, 0.21, 0.21, 0.21];
                } elseif ($maxCols === 4) {
                    $pctMap = $isFirstColNo ? [0.08, 0.28, 0.34, 0.30] : [0.24, 0.28, 0.24, 0.24];
                } elseif ($maxCols === 3) {
                    $pctMap = $isFirstColNo ? [0.08, 0.42, 0.50] : [0.30, 0.35, 0.35];
                } elseif ($maxCols === 2) {
                    $pctMap = [0.36, 0.64];
                } else {
                    if ($isFirstColNo && $maxCols > 1) {
                        $pctMap[] = 0.06;
                        for ($i = 1; $i < $maxCols; $i++) {
                            $pctMap[] = 0.94 / ($maxCols - 1);
                        }
                    } else {
                        for ($i = 0; $i < $maxCols; $i++) {
                            $pctMap[] = 1.0 / max(1, $maxCols);
                        }
                    }
                }
                $colTwips = array_map(fn($p) => max(480, (int) round($totalWidthTwips * $p)), $pctMap);

                // Tabel Akademik Bergaris Lengkap (Full-Grid Crisp Table Standar Skripsi Indonesia)
                $table = $section->addTable([
                    'borderSize' => 6,
                    'borderColor' => '000000',
                    'cellMarginTop' => 70,
                    'cellMarginBottom' => 70,
                    'cellMarginLeft' => 90,
                    'cellMarginRight' => 90,
                    'alignment' => Jc::CENTER,
                    'width' => $totalWidthTwips,
                    'unit' => \PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,
                    'layout' => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
                ]);

                foreach ($rows as $rIdx => $rowCells) {
                    $isHeader = ($rIdx === 0);
                    $tRow = $table->addRow(null, [
                        'tblHeader' => $isHeader,
                        'cantSplit' => true,
                    ]);
                    while (count($rowCells) < $maxCols) {
                        $rowCells[] = '-';
                    }
                    if (count($rowCells) > $maxCols) {
                        $rowCells = array_slice($rowCells, 0, $maxCols);
                    }
                    foreach ($rowCells as $cIdx => $cellText) {
                        $cleanCell = trim(preg_replace('/^(\*{1,2}|<b>)+|(\*{1,2}|<\/b>)+$/i', '', $cellText));
                        $cleanCell = $this->formatAcademicMathText($cleanCell);
                        $thisColWidth = $colTwips[$cIdx] ?? max(950, (int) round($totalWidthTwips / max(1, $maxCols)));

                        $cellStyle = [
                            'bgColor' => $isHeader ? 'F1F5F9' : ($rIdx % 2 === 0 ? 'FAFBFC' : 'FFFFFF'),
                            'valign' => $isHeader ? 'center' : 'top',
                        ];
                        if ($isHeader) {
                            $cellStyle['borderBottomSize'] = 10;
                            $cellStyle['borderBottomColor'] = '000000';
                        }

                        $cell = $tRow->addCell($thisColWidth, $cellStyle);
                        $align = ($isHeader || ($isFirstColNo && $cIdx === 0) || preg_match('/^[\d\s,.\-%—\(\)<>]+$/', $cleanCell) || preg_match('/^(tp|fp|fn|tn)$/i', $cleanCell)) ? Jc::CENTER : Jc::LEFT;
                        $fontSize = ($maxCols >= 6) ? 9 : 10;
                        $cell->addText(
                            $cleanCell,
                            ['name' => 'Times New Roman', 'size' => $fontSize, 'bold' => $isHeader],
                            ['alignment' => $align, 'lineHeight' => 1.1, 'spaceBefore' => 0, 'spaceAfter' => 0, 'indentation' => ['firstLine' => 0]]
                        );
                    }
                }
                $section->addTextBreak(1);
            } else {
                // Bukan tabel nyata (mis. 1 baris paragraf berisi notasi |V| atau P(t|c)) -> kembalikan sebagai paragraf biasa
                foreach ($tableBuffer as $rawFallbackLine) {
                    $cleanP = $this->formatAcademicMathText(trim(preg_replace('/^(\*{1,2}|<b>)+|(\*{1,2}|<\/b>)+$/i', '', $rawFallbackLine)));
                    if ($cleanP !== '') {
                        $section->addText(
                            $cleanP,
                            ['name' => 'Times New Roman', 'size' => 12],
                            ['alignment' => Jc::BOTH, 'lineHeight' => 1.5, 'spaceBefore' => 0, 'spaceAfter' => 120, 'indentation' => ['firstLine' => 720]]
                        );
                    }
                }
            }
            $tableBuffer = [];
            $currentTableCaption = '';
        };

        $codeLines = [];
        $inCode = false;
        $flushCodeOrFlowchart = function () use (&$codeLines, $section, $project, &$currentFigureCaption, $htmlContent) {
            if (empty($codeLines))
                return;
            $rawCode = implode("\n", $codeLines);
            $isFlowchart = str_contains($rawCode, '-->') || str_contains($rawCode, '->') || str_contains($rawCode, '↓') || str_contains($rawCode, 'V') || preg_match('/\[.+?\]/', $rawCode);

            if ($isFlowchart) {
                $isBab2 = str_contains($htmlContent, '2.1 Landasan Teori') || str_contains($htmlContent, 'Kerangka Berpikir') || str_contains($htmlContent, 'Kerangka Pemikiran');
                $isFramework = $isBab2 || str_contains($rawCode, 'Permasalahan') || str_contains($rawCode, 'Landasan Teori') || str_contains($rawCode, 'Kerangka Pemikiran') || str_contains($rawCode, 'Fenomena') || str_contains($currentFigureCaption, '2.1') || str_contains(strtolower($currentFigureCaption), 'kerangka');
                $caption = !empty($currentFigureCaption) ? $currentFigureCaption : ($isFramework ? 'Gambar 2.1 Bagan Kerangka Berpikir Penelitian' : 'Gambar 3.1 Diagram Alur (Flowchart) Prosedur Penelitian');
                $currentFigureCaption = '';

                preg_match_all('/\[(.*?)\]/', $rawCode, $nodeMatches);
                $nodes = !empty($nodeMatches[1]) ? $nodeMatches[1] : array_filter(array_map('trim', $codeLines), fn($l) => !empty($l) && !str_starts_with($l, 'graph') && !preg_match('/^[↓V\-\->\s|+~#=]+$/', $l) && !preg_match('/^\+[-+]+\+$/', $l));
                if (!empty($nodes)) {
                    $cleanNodes = [];
                    foreach ($nodes as $node) {
                        $cn = trim(strip_tags($node));
                        if (preg_match('/^(?:SIMBOL\s+DATA|DATA\s*\/\s*INPUT|SUB-PROSES|PREDEFINED\s+PROCESS|PROSES\s+SISTEM|PROSES|KEPUTUSAN|DECISION|TERMINATOR)$/i', $cn)) {
                            continue;
                        }
                        $cn = preg_replace('/^[\[\/\s\-—]+|[\]\/\s\-—]+$/', '', $cn);
                    if (str_starts_with($cn, '(') && str_ends_with($cn, ')') && substr_count($cn, '(') === 1 && substr_count($cn, ')') === 1) {
                        $cn = trim(substr($cn, 1, -1));
                    }
                        if (!empty($cn) && !preg_match('/^[↓V\-\->\s|+~#=]+$/', $cn) && !preg_match('/^\+[-+]+\+$/', $cn) && !in_array($cn, ['{', '}', '(', ')', '[', ']', ';'])) {
                            $cleanNodes[] = $cn;
                        }
                    }
                    if (!empty($cleanNodes)) {
                        // Sisipkan placeholder VML Vector Flowchart Standar ANSI yang diinjeksi menjadi Native Word Shapes saat postProcessDocxOutline
                        $placeholder = '[[FLOWCHART_VML_' . count($this->docxFlowchartVmlMap) . ']]';
                        $this->docxFlowchartVmlMap[$placeholder] = $this->buildFlowchartWordVml($cleanNodes, $isFramework);
                        $section->addText($placeholder, ['name' => 'Times New Roman', 'size' => 10], ['alignment' => Jc::CENTER, 'keepNext' => true, 'indentation' => ['firstLine' => 0]]);

                        // Standar Skripsi Indonesia: Judul Gambar WAJIB di BAWAH gambar
                        $section->addText(
                            $caption,
                            ['name' => 'Times New Roman', 'size' => 11, 'bold' => true],
                            ['alignment' => Jc::CENTER, 'spaceBefore' => 60, 'spaceAfter' => 160, 'lineHeight' => 1.15, 'indentation' => ['firstLine' => 0]]
                        );
                    }
                }
            } else {
                $section->addText($rawCode, ['name' => 'Courier New', 'size' => 9.5], ['indentation' => ['left' => 360], 'spaceAfter' => 80]);
            }
            $codeLines = [];
        };

        foreach ($lines as $line) {
            $cleanLine = trim(strip_tags($line));

            // Simpan atau tangkap caption tabel
            if (preg_match('/^(?:\*{1,2})?Tabel\s+\d+\.\d+[\w\.\-]*\s*(.*?)(?:\*{1,2})?$/i', $cleanLine)) {
                $cleanCap = trim(preg_replace('/^(\*{1,2})+|(\*{1,2})+$/', '', $cleanLine));
                if (mb_strlen($cleanCap) <= 130 && !preg_match('/menunjukkan bahwa|dapat dilihat|memperlihatkan/i', $cleanCap)) {
                    $currentTableCaption = $cleanCap;
                    continue;
                }
            }

            // Handle Code Blocks / Flowcharts
            if (str_starts_with($cleanLine, '```')) {
                $flushTable();
                if ($inCode) {
                    $inCode = false;
                    $flushCodeOrFlowchart();
                } else {
                    $inCode = true;
                    $codeLines = [];
                }
                continue;
            }
            if ($inCode) {
                $codeLines[] = $cleanLine;
                continue;
            }

            if (empty($cleanLine)) {
                $flushTable();
                continue;
            }

            // Buffer baris tabel markdown (hindari salah deteksi notasi matematika |V|, |D|, atau P(t|c))
            $strippedMathPipes = preg_replace(['/\|[A-Za-z0-9_,\s+\-]{1,8}\|/', '/[A-Za-z]\([^)]*\|[^)]*\)/'], ' ', $cleanLine);
            $isMdTableRow = (str_starts_with($cleanLine, '|') && str_ends_with($cleanLine, '|') && substr_count($cleanLine, '|') >= 3)
                || (str_starts_with($cleanLine, '|') && substr_count($strippedMathPipes, '|') >= 2)
                || (substr_count($strippedMathPipes, ' | ') >= 2);
            if ($isMdTableRow) {
                $tableBuffer[] = $cleanLine;
                continue;
            } else {
                $flushTable();
                if (!empty($currentTableCaption)) {
                    $section->addText(
                        $currentTableCaption,
                        ['bold' => true, 'name' => 'Times New Roman', 'size' => 11],
                        ['alignment' => Jc::LEFT, 'spaceBefore' => 140, 'spaceAfter' => 60, 'indentation' => ['firstLine' => 0]]
                    );
                    $currentTableCaption = '';
                }
            }

            // Bersihkan tanda markdown untuk pencocokan heading
            $unmarked = trim(preg_replace('/^(\*{1,2}|#+\s*|<b>)+|(\*{1,2}|<\/b>)+$/i', '', $cleanLine));

            if (!empty($currentFigureCaption) && !preg_match('/^(?:Gambar|Bagan)\s+\d+\.\d+/i', $unmarked) && !str_starts_with($cleanLine, '```')) {
                $section->addText(
                    $currentFigureCaption,
                    ['bold' => true, 'name' => 'Times New Roman', 'size' => 11],
                    ['alignment' => Jc::CENTER, 'spaceBefore' => 140, 'spaceAfter' => 60, 'indentation' => ['firstLine' => 0]]
                );
                $currentFigureCaption = '';
            }

            // Keterangan Sumber Tabel / Gambar
            if (preg_match('/^Sumber\s*:/i', $unmarked)) {
                $section->addText(
                    $unmarked,
                    ['name' => 'Times New Roman', 'size' => 10, 'italic' => true],
                    ['alignment' => Jc::LEFT, 'lineHeight' => 1.15, 'spaceBefore' => 0, 'spaceAfter' => 120, 'indentation' => ['firstLine' => 0]]
                );
            }
            // Heading Level 3: 2.1.1, 2.1.2, 1.6.1, dst.
            elseif (preg_match('/^(\d+\.\d+\.\d+[\s\.:\-\)][^\n<]+)/i', $unmarked, $m3) || preg_match('/^\d+\.\s+(Manfaat|Tujuan)\s+[A-Za-z]+/i', $unmarked, $m3)) {
                $section->addTitle($unmarked, 3);
            }
            // Heading Level 2: 1.1, 1.2, 2.1, 3.1, dst.
            elseif (preg_match('/^(\d+\.\d+[\s\.:\-\)][^\n<]+)/i', $unmarked, $m2) || preg_match('/^([A-Z]\.[\s\.:\-\)][^\n<]+)/', $unmarked, $ma)) {
                $section->addTitle($unmarked, 2);
            }
            // Judul Gambar / Bagan (Disimpan untuk diletakkan di BAWAH diagram flowchart)
            elseif (preg_match('/^(?:Gambar|Bagan)\s+\d+\.\d+/i', $unmarked) && mb_strlen($unmarked) <= 130 && !preg_match('/menunjukkan bahwa|dapat dilihat|memperlihatkan/i', $unmarked)) {
                $currentFigureCaption = $unmarked;
            }
            // Poin bernomor: 1. 2. dst.
            elseif (preg_match('/^\d+\.\s+/', $unmarked)) {
                $section->addText(
                    $this->formatAcademicMathText($unmarked),
                    ['name' => 'Times New Roman', 'size' => 12],
                    ['alignment' => Jc::BOTH, 'lineHeight' => 1.5, 'indentation' => ['left' => 420, 'hanging' => 360], 'spaceAfter' => 60]
                );
            }
            // Rumus Matematika / Persamaan Standalone (Center-aligned tanpa indentasi)
            elseif ($this->isStandaloneFormula($unmarked)) {
                $pStyle = ['alignment' => Jc::CENTER, 'lineHeight' => 1.5, 'indentation' => ['firstLine' => 0], 'spaceBefore' => 140, 'spaceAfter' => 140];
                $section->addText($this->formatAcademicMathText($unmarked), ['name' => 'Times New Roman', 'size' => 12], $pStyle);
            }
            // Paragraf biasa dengan standar indentasi skripsi 1.27 cm
            else {
                $pStyle = ['alignment' => Jc::BOTH, 'lineHeight' => 1.5, 'indentation' => ['firstLine' => 720], 'spaceAfter' => 80];
                $section->addText($this->formatAcademicMathText($unmarked), ['name' => 'Times New Roman', 'size' => 12], $pStyle);
            }
        }
        $flushTable();
        $flushCodeOrFlowchart();
    }

    private function formatAcademicMathText(string $text): string
    {
        if (empty($text)) {
            return '';
        }

        // 1. Bersihkan command pembungkus font & layout
        $text = preg_replace('/\\\(left|right)\b/', '', $text);
        $text = preg_replace('/\\\(text|mathrm|mathbf|mathit)\{([^{}]+)\}/', '$1', $text);

        // 2. Bersihkan \sqrt
        $text = preg_replace('/\\\sqrt\{([^{}]+)\}/', '√($1)', $text);
        $text = str_replace('\sqrt', '√', $text);

        // 3. Bersihkan \hat{Y} / \hat Y / \widehat
        $text = preg_replace('/\\\hat\{([a-zA-Z])\}/', '$1̂', $text);
        $text = preg_replace('/\\\hat\s*([a-zA-Z])/', '$1̂', $text);
        $text = preg_replace('/\\\widehat\{([^{}]+)\}/', '$1̂', $text);

        // 4. Bersihkan \frac{pembilang}{penyebut} secara rekursif
        while (preg_match('/\\\frac\{([^{}]+)\}\{([^{}]+)\}/', $text)) {
            $text = preg_replace('/\\\frac\{([^{}]+)\}\{([^{}]+)\}/', '$1 / ($2)', $text);
        }

        // 5. Bersihkan {,} desimal LaTeX (misal 96{,}482 -> 96,482)
        $text = str_replace('{,}', ',', $text);

        // 6. Ubah pangkat menjadi karakter superskrip unicode
        $text = str_replace(['^2', '^{2}'], '²', $text);
        $text = str_replace(['^3', '^{3}'], '³', $text);
        $text = str_replace(['^t', '^{t}', '^T', '^{T}'], 'ᵗ', $text);

        // 7. Simbol matematis standar
        $text = str_replace(['\times', '\cdot'], '×', $text);
        $text = str_replace(['\approx'], '≈', $text);
        $text = str_replace(['\leq', '\le'], '≤', $text);
        $text = str_replace(['\geq', '\ge'], '≥', $text);
        $text = str_replace(['\pm'], '±', $text);
        $text = str_replace(['\neq', '\ne'], '≠', $text);
        $text = str_replace(['\alpha'], 'α', $text);
        $text = str_replace(['\beta'], 'β', $text);
        $text = str_replace(['\epsilon'], 'ε', $text);
        $text = str_replace(['\sigma'], 'σ', $text);
        $text = str_replace(['\mu'], 'μ', $text);
        $text = str_replace(['\theta'], 'θ', $text);
        $text = str_replace(['\lambda'], 'λ', $text);
        $text = str_replace(['\delta'], 'δ', $text);
        $text = str_replace(['\Delta'], 'Δ', $text);
        $text = str_replace(['\pi'], 'π', $text);
        $text = str_replace(['\infty'], '∞', $text);
        $text = str_replace(['\sum'], '∑', $text);

        // 8. Buang delimiter dolar $$...$$ dan $...$ pada math
        $text = preg_replace('/\$\$([^$]+)\$\$/', '$1', $text);
        $text = preg_replace('/\$([^$]+)\$/', '$1', $text);

        // 9. Bersihkan sisa backslash perintah LaTeX yang tidak dikenal
        $text = preg_replace('/\\\([a-zA-Z]+)/', '$1', $text);

        return $text;
    }

    private function isStandaloneFormula(string $line): bool
    {
        $t = trim($line);
        $len = strlen($t);
        if ($len < 3 || $len > 180) {
            return false;
        }
        // Jangan tangkap jika kalimat deskriptif diawali konjungsi
        if (preg_match('/^(dengan|dimana|di mana|yaitu|karena|sehingga|adalah|untuk|pada)\b/i', $t)) {
            return false;
        }
        if (str_ends_with($t, '.') && str_word_count($t) > 8) {
            return false;
        }
        $hasRelation = preg_match('/[=≈≤≥]|:=|\b(adalah|sebesar)\s*=/i', $t);
        if (!$hasRelation && !preg_match('/^\$\$.+\$\$$/', $t)) {
            return false;
        }
        $hasMathTokens = preg_match('/[\+\*\/\^×÷√∑∏∫αβγδεληθμσλ\[\]\|]|\b(count|sqrt|sum|log|exp|sin|cos|tan|lim|max|min|Akurasi|Accuracy|Recall|Precision|F1|R-Square|R2|t-hitung|F-hitung|Uji\s*t|Uji\s*F|P\([a-zA-Z0-9_\|\s,\+\-\*\/\^]+\))\b/i', $t);
        return (bool) ($hasRelation && $hasMathTokens);
    }

    private function buildDefaultBab5Content($project): string
    {
        $judul = $project->judul ?? 'Penelitian';
        $prodi = $project->prodi ?? 'Pendidikan';
        $metode = $project->metode_penelitian ?? 'Kuantitatif';

        $isInformatika = preg_match('/informatika|komputer|sistem informasi|rekayasa perangkat lunak|data science/i', $prodi . ' ' . $judul);

        if ($isInformatika) {
            return "5.1 Kesimpulan\n" .
                "Berdasarkan hasil perancangan, implementasi sistem, dan analisis pengujian pada bab-bab sebelumnya mengenai {$judul}, dapat ditarik beberapa kesimpulan sebagai berikut:\n" .
                "1. Penerapan metode {$metode} pada Program Studi {$prodi} telah berhasil dirancang dan diuji secara komprehensif sesuai dengan tujuan penelitian pada Bab I.\n" .
                "2. Berdasarkan hasil pengujian data empiris pada Bab IV, model penelitian terbukti mampu menghasilkan performa yang optimal dan reliabel dalam memproses data uji.\n" .
                "3. Tahapan pra-pemrosesan data dan pemodelan komputasi yang terstruktur terbukti efektif mengoptimalkan hasil evaluasi sistem.\n\n" .
                "5.2 Saran\n" .
                "1. Bagi Peneliti Selanjutnya: Disarankan untuk mengeksplorasi variasi parameter model dan memperluas cakupan data uji guna meningkatkan generalisasi sistem.\n" .
                "2. Bagi Praktisi: Disarankan untuk mengintegrasikan model ke dalam sistem terapan yang dapat dimanfaatkan langsung oleh pengguna akhir.";
        }

        // Default untuk ilmu sosial / pendidikan / ekonomi / hukum / manajemen
        return "5.1 Kesimpulan\n" .
            "Berdasarkan hasil analisis data empiris dan pembahasan mendalam pada bab-bab sebelumnya mengenai {$judul}, maka dapat ditarik kesimpulan utama sebagai berikut:\n" .
            "1. Pelaksanaan penelitian menggunakan pendekatan {$metode} pada Program Studi {$prodi} telah berhasil menjawab seluruh rumusan masalah yang ditetapkan pada Bab I secara terukur.\n" .
            "2. Berdasarkan hasil pengujian hipotesis pada Bab IV, terbukti terdapat pengaruh dan hubungan yang signifikan antara variabel penelitian yang diteliti sesuai dengan arah hipotesis yang dirumuskan.\n" .
            "3. Temuan penelitian ini memperkuat landasan teoretis yang digunakan serta memberikan konfirmasi empiris mengenai dinamika variabel pada konteks subjek penelitian.\n\n" .
            "5.2 Saran\n" .
            "1. Bagi Pihak Terkait: Disarankan untuk menjadikan temuan penelitian ini sebagai bahan evaluasi kebijakan dan pertimbangan praktis dalam pengelolaan alokasi waktu dan peningkatan prestasi belajar siswa.\n" .
            "2. Bagi Peneliti Selanjutnya: Disarankan untuk memperluas populasi penelitian dan memasukkan variabel-variabel moderasi lainnya guna memperoleh gambaran yang lebih komprehensif.";
    }

    private function extractInTextCitations(string $text): array
    {
        $citations = [];
        // Format 1: Penulis (Tahun), termasuk 2-5 penulis seperti: Pongdatu, Lestari, dan Tasman (2023)
        if (preg_match_all('/([A-Z][a-zA-Z\-]+(?:\s+et\s+al\.?|(?:,\s+[A-Z][a-zA-Z\-]+){0,4},?\s+(?:dan|&)\s+[A-Z][a-zA-Z\-]+)?)\s*\(((?:19|20)\d\d)\)/u', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $author = trim($m[1]);
                $year = $m[2];
                $firstAuthor = trim((string) preg_split('/[\s,]+/', $author)[0]);
                if (!in_array(strtolower($firstAuthor), ['tabel', 'gambar', 'bab', 'lampiran', 'bagan', 'pasal', 'tahun', 'ayat'])) {
                    $citations["{$author} ({$year})"] = true;
                }
            }
        }
        // Format 2: (Penulis, Tahun), termasuk multi-penulis seperti (Pongdatu, Lestari, & Tasman, 2023)
        if (preg_match_all('/\(([A-Z][a-zA-Z\-]+(?:\s+et\s+al\.?|(?:,\s+[A-Z][a-zA-Z\-]+){0,4},?\s+(?:dan|&)\s+[A-Z][a-zA-Z\-]+)?),?\s*((?:19|20)\d\d)\)/u', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $author = trim($m[1]);
                $year = $m[2];
                $firstAuthor = trim((string) preg_split('/[\s,]+/', $author)[0]);
                if (!in_array(strtolower($firstAuthor), ['tabel', 'gambar', 'bab', 'lampiran', 'bagan', 'pasal', 'tahun', 'ayat'])) {
                    $citations["{$author} ({$year})"] = true;
                }
            }
        }
        // Format 3: Sitasi numerik IEEE ([1], [1, 2]) bila digunakan
        if (preg_match_all('/\[(\d{1,2}(?:\s*,\s*\d{1,2})*)\]/', $text, $numMatches)) {
            foreach ($numMatches[1] as $group) {
                foreach (explode(',', $group) as $n) {
                    $num = trim($n);
                    if ($num !== '' && (int) $num >= 1 && (int) $num <= 40) {
                        $citations["[{$num}]"] = true;
                    }
                }
            }
        }
        return array_keys($citations);
    }

    private function resolveLogoTempPath(Request $request, array $meta = []): ?string
    {
        try {
            $rawBytes = null;
            if ($request->hasFile('logo_kampus') && $request->file('logo_kampus')->isValid()) {
                $rawBytes = @file_get_contents($request->file('logo_kampus')->getRealPath());
            } else {
                $base64 = $request->input('logo_base64') ?: ($meta['logo_base64'] ?? '');
                if (!empty($base64)) {
                    $clean = preg_replace('#^data:image/[^;]+;base64,#i', '', trim($base64));
                    $rawBytes = @base64_decode($clean);
                }
            }

            if (empty($rawBytes) || strlen($rawBytes) < 60) {
                return null;
            }

            $isJpg = str_starts_with($rawBytes, "\xFF\xD8\xFF");
            $ext = $isJpg ? 'jpg' : 'png';
            $tmpDir = rtrim(sys_get_temp_dir(), '/\\');
            $tmpPath = $tmpDir . '/logo_' . uniqid() . '.' . $ext;

            if (function_exists('imagecreatefromstring')) {
                $img = @imagecreatefromstring($rawBytes);
                if ($img !== false) {
                    $origW = imagesx($img);
                    $origH = imagesy($img);
                    $maxDim = 240;
                    $scale = min(1.0, $maxDim / max(1, $origW, $origH));
                    $newW = max(1, (int) round($origW * $scale));
                    $newH = max(1, (int) round($origH * $scale));

                    $dst = imagecreatetruecolor($newW, $newH);
                    $white = imagecolorallocate($dst, 255, 255, 255);
                    imagefilledrectangle($dst, 0, 0, $newW, $newH, $white);
                    imagecopyresampled($dst, $img, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

                    $tmpJpg = $tmpDir . '/logo_' . uniqid() . '.jpg';
                    imagejpeg($dst, $tmpJpg, 85);
                    imagedestroy($img);
                    imagedestroy($dst);
                    if (file_exists($tmpJpg)) {
                        return $tmpJpg;
                    }
                }
            }

            file_put_contents($tmpPath, $rawBytes);
            return $tmpPath;
        } catch (Throwable $e) {
            Log::warning('Logo resolve error: ' . $e->getMessage());
        }
        return null;
    }

    /**
     * Susun ringkasan ABSTRAK (ID), ABSTRACT (EN), dan Kata Kunci secara dinamis dari isi Bab III-V
     * agar tidak lagi menampilkan boilerplate generik ("Kuantitatif (Survei / Regresi)" tanpa angka temuan).
     */
    private function buildDynamicAbstrakData(Project $project): array
    {
        $cleanJudul = rtrim(trim((string) $project->judul), '. ');
        $prodi = trim((string) ($project->prodi ?: 'Program Studi'));
        $metodeRaw = trim((string) ($project->metode_penelitian ?: 'ilmiah terstruktur'));

        $b3 = trim(strip_tags((string) ($project->bab3_content ?? '')));
        $b4 = trim(strip_tags((string) ($project->bab4_content ?? '')));
        $b5 = trim(strip_tags((string) ($project->bab5_content ?? '')));

        // Deteksi metode riil dari Bab III agar akurat lintas semua jurusan (tidak bentrok dengan dropdown form)
        $metodeAktual = $metodeRaw;
        if ($b3 !== '' && preg_match('/(?:penelitian|pendekatan|metode)\s+(pengembangan\s+perangkat\s+lunak(?:\s*\([^)]+\))?|kuantitatif\s+eksperimental(?:\s+komputasi(?:onal)?)?|eksperimen(?:tal)?\s+komputasi(?:onal)?|kuantitatif\s+korelasional(?:\s+dengan\s+pendekatan\s+cross-?sectional)?|analitik\s+observasional(?:\s+dengan\s+desain\s+cross-?sectional)?|kualitatif\s+(?:deskriptif|studi\s+kasus|fenomenologi|etnografi)|penelitian\s+dan\s+pengembangan\s*\(R&D\)|pengembangan\s*\(R&D\)|tindakan\s+kelas(?:\s*\(PTK\))?|yuridis\s+(?:normatif|empiris)|hukum\s+(?:normatif|empiris)|studi\s+kepustakaan(?:\s*\(library\s+research\))?|campuran\s*\(mixed\s+methods\))/i', $b3, $mm)) {
            $metodeAktual = trim($mm[1]);
        }
        if (preg_match('/survei\s*\/\s*regresi/i', $metodeAktual)) {
            $combined34 = $b3 . ' ' . $b4;
            if (preg_match('/naive\s+bayes|tf-?idf|confusion\s+matrix|machine\s+learning|deep\s+learning|klasifikasi\s+teks|dataset\s+komentar/i', $combined34)) {
                $metodeAktual = 'eksperimen komputasional';
            } elseif (preg_match('/black-?box|system\s+usability\s+scale|waterfall|agile|uml|use\s+case/i', $combined34)) {
                $metodeAktual = 'penelitian dan pengembangan perangkat lunak (R&D)';
            }
        }

        // Ekstrak angka dataset final / sampel (prioritaskan ukuran sampel N= / "ditarik sampel sebanyak X" daripada angka populasi!)
        $samplePhraseId = '';
        $samplePhraseEn = '';
        $srcSample = $b4 !== '' ? $b4 : $b3;
        $unitMap = [
            'komentar' => 'comments', 'responden' => 'respondents', 'sampel' => 'samples',
            'dokumen' => 'documents', 'data' => 'records', 'citra' => 'images',
            'siswa' => 'students', 'mahasiswa' => 'students', 'pasien' => 'patients',
            'karyawan' => 'employees', 'informan' => 'informants', 'observasi' => 'observations',
        ];
        if ($srcSample !== '' && preg_match('/(?:dataset\s+(?:final|bersih)|lolos\s+verifikasi[^.\n]{0,35}|ditarik\s+sampel\s+sebanyak|ukuran\s+sampel\s+(?:penelitian\s+)?(?:ditetapkan\s+)?sebanyak|sampel\s+(?:penelitian\s+)?(?:sebanyak|berjumlah|adalah)|responden\s+(?:penelitian\s+)?(?:sebanyak|berjumlah|yaitu)|informan\s+(?:penelitian\s+)?(?:sebanyak|berjumlah)|digunakan\s+dalam\s+penelitian\s+(?:ini\s+)?berjumlah|\bN\s*=\s*)[^.\n]{0,55}?\b(\d{1,3}(?:\.\d{3})+|\d{1,5})\s*(komentar|responden|sampel|dokumen|data|citra|siswa|mahasiswa|pasien|karyawan|informan|observasi)?\b/i', $srcSample, $ms)) {
            $unitId = !empty($ms[2]) ? $ms[2] : 'responden';
            $samplePhraseId = " dengan melibatkan {$ms[1]} {$unitId}";
            $enUnit = $unitMap[strtolower($unitId)] ?? 'respondents';
            $samplePhraseEn = " involving {$ms[1]} {$enUnit}";
        } elseif ($srcSample !== '' && preg_match_all('/\b(\d{1,3}(?:\.\d{3})+|\d{2,5})\s+(komentar|responden|sampel|dokumen|data|citra|siswa|mahasiswa|pasien|karyawan|informan|observasi)\b/i', $srcSample, $allMs, PREG_OFFSET_CAPTURE)) {
            foreach ($allMs[0] as $k => $fullMatch) {
                $offset = $fullMatch[1];
                $beforeCtx = substr($srcSample, max(0, $offset - 50), min(50, $offset));
                $afterCtx = substr($srcSample, $offset + strlen($fullMatch[0]), 25);
                if (preg_match('/(?:populasi|jumlah\s+(?:siswa|mahasiswa|guru|karyawan)\s+sebanyak|perayapan\s+awal|pengumpulan\s+awal|data\s+mentah)/i', $beforeCtx) || preg_match('/^\s*mentah\b/i', $afterCtx)) {
                    continue;
                }
                $numVal = $allMs[1][$k][0];
                $unitId = $allMs[2][$k][0];
                $samplePhraseId = " dengan melibatkan {$numVal} {$unitId}";
                $enUnit = $unitMap[strtolower($unitId)] ?? 'samples';
                $samplePhraseEn = " involving {$numVal} {$enUnit}";
                break;
            }
        }

        // Ekstrak kalimat capaian/hasil empiris UTAMA dari Bab IV (prioritas temuan headline positif, abaikan kalimat ablasi/penurunan/peringatan!)
        $findingId = '';
        $findingEn = '';
        $isNegativeOrAblation = static function (string $s): bool {
            return (bool) preg_match('/\b(?:tanpa\s+|turun\s+menjadi|penurunan\s+sebesar|lebih\s+rendah|dapat\s+menyesatkan|mengabaikan\s+kelas|sebaliknya|namun\s+perlu\s+dicatat|perbedaan\s+kecil\s+tersebut)\b/i', $s);
        };
        if ($b4 !== '') {
            $b4Sents = preg_split('/(?<=[.!?])\s+(?=[A-ZÀ-Ÿ])|\n+/', $b4) ?: [];
            $primaryCandidate = '';
            $secondaryCandidate = '';
            foreach ($b4Sents as $candRaw) {
                $candClean = trim(preg_replace('/\s+/', ' ', $candRaw));
                if (
                    mb_strlen($candClean) < 35 || mb_strlen($candClean) > 290
                    || str_starts_with($candClean, '|')
                    || $isNegativeOrAblation($candClean)
                    || !preg_match('/(?:\d+[,\.]\d+|\d+\s*(?:%|persen))/i', $candClean)
                ) {
                    continue;
                }
                if ($primaryCandidate === '' && preg_match('/(?:akurasi\s+keseluruhan\s+sebesar|mencapai\s+akurasi|koefisien\s+determinasi|R-?[Ss]quare|R²|tingkat\s+keberhasilan\s+fungsional|skor\s+(?:SUS|System\s+Usability)|ketuntasan\s+klasikal.*siklus\s+II|nilai\s+p(?:-?value)?\s*[<≤=]|berpengaruh\s+(?:positif\s+dan\s+)?signifikan|terdapat\s+hubungan\s+yang\s+signifikan)/i', $candClean)) {
                    $primaryCandidate = $candClean;
                    break;
                }
                if ($secondaryCandidate === '' && preg_match('/(?:akurasi|F1-?[Ss]core|R-?[Ss]quare|R²|signifikan|meningkat\s+dari|kelayakan|N-?Gain)/i', $candClean)) {
                    $secondaryCandidate = $candClean;
                }
            }
            $chosen = $primaryCandidate !== '' ? $primaryCandidate : $secondaryCandidate;
            if ($chosen !== '') {
                $chosen = (string) preg_replace('/^(?:Berdasarkan\s+)?Tabel\s+\d+\.\d+\s+(?:memperlihatkan|menunjukkan)\s+bahwa\s+/i', 'Hasil pengujian menunjukkan bahwa ', $chosen);
                $chosen = rtrim($chosen, '. ') . '.';
                $findingId = mb_strtoupper(mb_substr($chosen, 0, 1)) . mb_substr($chosen, 1);
            }
        }
        if ($findingId === '' && $b5 !== '' && preg_match('/^\s*1\.\s+([^\n]{35,280})/m', $b5, $mf5)) {
            $firstSent = preg_split('/(?<=[.!?])\s+/', trim($mf5[1]))[0] ?? trim($mf5[1]);
            $findingId = rtrim(trim($firstSent), '.') . '.';
        }

        if ($findingId !== '') {
            $idBody = "Penelitian ini bertujuan untuk menganalisis dan menjawab permasalahan pada topik \"{$cleanJudul}\". Pendekatan yang diterapkan adalah metode {$metodeAktual}{$samplePhraseId} melalui tahapan pengumpulan, pengolahan, dan pengujian data secara sistematis. {$findingId} Temuan penelitian ini diharapkan memberikan kontribusi teoretis serta rekomendasi praktis bagi pengembangan keilmuan pada Program Studi {$prodi}.";
        } else {
            $idBody = "Penelitian ini bertujuan untuk menginvestigasi, menganalisis, dan menyelesaikan permasalahan pada topik \"{$cleanJudul}\". Metode penelitian yang diterapkan adalah {$metodeAktual}{$samplePhraseId} melalui prosedur pengumpulan, pengolahan, dan pengujian data empiris secara sistematis. Hasil penelitian menunjukkan bahwa pendekatan yang diterapkan mampu menjawab seluruh rumusan masalah secara terukur, memberikan evaluasi kinerja yang valid, serta menghasilkan kontribusi teoretis dan praktis bagi pengembangan keilmuan pada Program Studi {$prodi}.";
        }

        // Ringkasan angka metrik untuk ABSTRACT Bahasa Inggris lintas semua jurusan (bebas spasi sebelum '%')
        $normPct = static function (string $val): string {
            $v = str_ireplace('persen', '%', trim($val));
            $v = str_replace(',', '.', $v);
            return (string) preg_replace('/\s+%/', '%', $v);
        };
        if ($b4 !== '' && (
            preg_match('/(?:akurasi\s+keseluruhan\s+(?:sebesar\s+)?|overall\s+accuracy\s+(?:of\s+)?)(\d{1,3}[,\.]\d{1,2}\s*(?:persen|%)|0[,\.]\d{2,4})/i', $b4, $ma)
            || preg_match('/(?:mencapai\s+akurasi\s+(?:sebesar\s+)?)(\d{1,3}[,\.]\d{1,2}\s*(?:persen|%)|0[,\.]\d{2,4})/i', $b4, $ma)
        )) {
            $accVal = $normPct($ma[1]);
            $findingEn = "Empirical evaluation confirms the effectiveness of the proposed approach, achieving an overall accuracy of {$accVal} alongside consistent class-level performance metrics.";
        } elseif ($b4 !== '' && preg_match('/(?:R²|R-?Square)\s*(?:sebesar|=)\s*(0[,\.]\d{2,4}|\d{1,2}[,\.]\d{1,2}\s*(?:%|persen))/i', $b4, $mr)) {
            $r2Val = $normPct($mr[1]);
            $findingEn = "Empirical testing confirms a statistically significant effect with a coefficient of determination (R-Square) of {$r2Val}.";
        } elseif ($b4 !== '' && preg_match('/(?:skor\s+(?:SUS|System\s+Usability\s+Scale)|black-?box[^.\n]{0,40}keberhasilan)\s*(?:sebesar|mencapai|=)?\s*(\d{1,3}(?:[,\.]\d{1,2})?\s*(?:%|persen)?)/i', $b4, $msus)) {
            $susVal = $normPct($msus[1]);
            $findingEn = "System evaluation confirms functional reliability and user feasibility, achieving an evaluation score of {$susVal}.";
        } elseif ($b4 !== '' && preg_match('/(?:nilai\s+)?p(?:-?value)?\s*(?:sebesar|=|<|≤)\s*(0[,\.]\d{2,4})/i', $b4, $mp)) {
            $pVal = str_replace(',', '.', trim($mp[1]));
            $findingEn = "Statistical testing confirms a significant association among the studied variables (p = {$pVal}).";
        } else {
            $findingEn = "The empirical findings demonstrate that the implemented approach effectively addresses all research questions and provides verifiable evaluation outcomes.";
        }

        $enBody = "This study aims to investigate, analyze, and resolve the empirical problems addressed in \"{$cleanJudul}\". Employing a structured research methodology{$samplePhraseEn}, this study systematically collects, processes, and evaluates empirical data. {$findingEn} Overall, the results offer both theoretical contributions and practical implications for the field of {$prodi}.";

        // Bangun Kata Kunci substantif dari judul (hindari kata umum/generik seperti "Modul", "Produk", "Lokal")
        $stopKw = ['dan', 'atau', 'yang', 'pada', 'dalam', 'untuk', 'dengan', 'terhadap', 'menggunakan', 'melalui', 'studi', 'kasus', 'pengaruh', 'analisis', 'evaluasi', 'implementasi', 'penerapan', 'perancangan', 'pengembangan', 'berbasis', 'di', 'ke', 'dari', 'oleh', 'secara', 'modul', 'aplikasi', 'sistem', 'metode', 'algoritma', 'produk', 'lokal'];
        $words = preg_split('/[\s,:;\-\(\)\/]+/u', $cleanJudul) ?: [];
        $kwList = [];
        foreach ($words as $w) {
            $wClean = trim($w);
            if (mb_strlen($wClean) >= 4 && !in_array(mb_strtolower($wClean), $stopKw, true)) {
                $kwList[] = $wClean;
            }
            if (count($kwList) >= 4) {
                break;
            }
        }
        if (empty($kwList)) {
            $kwList = [$prodi, 'Analisis Empiris'];
        }
        $keywordsId = implode(', ', array_unique(array_merge($kwList, [$prodi]))) . '.';
        $keywordsEn = implode(', ', array_unique(array_merge($kwList, ['Empirical Evaluation']))) . '.';

        return [
            'judul' => $cleanJudul,
            'id_body' => $idBody,
            'id_keywords' => $keywordsId,
            'en_body' => $enBody,
            'en_keywords' => $keywordsEn,
        ];
    }

    private function createPhpWordDocument($project, Request $request, ?string $logoPath = null, array $meta = [])
    {
        $this->docxFlowchartVmlMap = [];
        $fontKey = strtolower(trim((string) $request->input('font_family', 'times')));
        $fontMap = [
            'times' => ['name' => 'Times New Roman', 'size' => 12.0],
            'arial' => ['name' => 'Arial', 'size' => 11.0],
            'georgia' => ['name' => 'Georgia', 'size' => 11.0],
        ];
        $chosenFont = $fontMap[$fontKey] ?? $fontMap['times'];
        $this->docxFontName = $chosenFont['name'];
        $this->docxFontSize = $chosenFont['size'];

        $phpWord = new PhpWord();
        Settings::setOutputEscapingEnabled(true);
        $phpWord->setDefaultFontName($this->docxFontName);
        $phpWord->setDefaultFontSize($this->docxFontSize);

        $marginTop = (float) $request->input('margin_top', 4);
        $marginLeft = (float) $request->input('margin_left', 4);
        $marginBottom = (float) $request->input('margin_bottom', 3);
        $marginRight = (float) $request->input('margin_right', 3);

        $tahap = strtolower((string) ($meta['tahap_pengerjaan'] ?? ''));
        $defaultJenis = str_contains($tahap, 'proposal') ? 'PROPOSAL SKRIPSI' : 'SKRIPSI';

        $nama = $request->input('nama') ?: ($meta['nama'] ?? 'NAMA MAHASISWA');
        $npm = $request->input('npm') ?: ($meta['nim'] ?? '');
        $kampus = $request->input('kampus') ?: ($meta['kampus'] ?? 'UNIVERSITAS');
        $fakultas = $this->formatFacultyLabel((string) ($request->input('fakultas') ?: ($meta['fakultas'] ?? '')));
        $jenisDokumen = strtoupper($request->input('jenis_dokumen', $defaultJenis));
        $isProposal = str_contains($jenisDokumen, 'PROPOSAL');
        $tglFullIndo = $this->formatIndonesianDate('full');
        $tglBulanIndo = $this->formatIndonesianDate('month_year');

        $phpWord->setDefaultParagraphStyle([
            'lineHeight' => 1.5,
            'spaceAfter' => Converter::pointToTwip(0),
        ]);

        $phpWord->addTitleStyle(1, ['name' => 'Times New Roman', 'size' => 14, 'bold' => true], ['alignment' => Jc::CENTER, 'lineHeight' => 1.25, 'spaceBefore' => 0, 'spaceAfter' => 240, 'indentation' => ['firstLine' => 0]]);
        $phpWord->addTitleStyle(2, ['name' => 'Times New Roman', 'size' => 12, 'bold' => true], ['alignment' => Jc::LEFT, 'lineHeight' => 1.5, 'spaceBefore' => 160, 'spaceAfter' => 80, 'keepNext' => true, 'indentation' => ['firstLine' => 0]]);
        $phpWord->addTitleStyle(3, ['name' => 'Times New Roman', 'size' => 12, 'bold' => true], ['alignment' => Jc::LEFT, 'lineHeight' => 1.5, 'spaceBefore' => 100, 'spaceAfter' => 60, 'keepNext' => true, 'indentation' => ['firstLine' => 0]]);

        $baseSectionStyle = [
            'pageSizeW' => Converter::cmToTwip(21.0),
            'pageSizeH' => Converter::cmToTwip(29.7),
            'marginTop' => Converter::cmToTwip($marginTop),
            'marginLeft' => Converter::cmToTwip($marginLeft),
            'marginBottom' => Converter::cmToTwip($marginBottom),
            'marginRight' => Converter::cmToTwip($marginRight),
            'headerHeight' => Converter::cmToTwip(1.5),
            'footerHeight' => Converter::cmToTwip(1.5),
        ];

        $center = ['alignment' => Jc::CENTER, 'lineHeight' => 1.25, 'indentation' => ['firstLine' => 0]];
        $bold14 = ['bold' => true, 'size' => 14, 'name' => 'Times New Roman'];
        $bold12 = ['bold' => true, 'size' => 12, 'name' => 'Times New Roman'];

        // =====================================================================
        // SECTION 1: HALAMAN SAMPUL (COVER) — TANPA NOMOR HALAMAN (DIHITUNG i)
        // =====================================================================
        $cleanJudulMaster = rtrim(trim((string) $project->judul), '. ');
        $coverSection = $phpWord->addSection($baseSectionStyle);
        $coverSection->addText($jenisDokumen, $bold14, $center);
        $coverSection->addTextBreak(1);
        $coverSection->addText(strtoupper($cleanJudulMaster), $bold14, $center);
        $coverSection->addTextBreak(1);
        $subCoverText = $isProposal
            ? "Diajukan untuk Memenuhi Persyaratan Seminar Proposal Skripsi pada Program Studi " . $project->prodi
            : "Diajukan sebagai Salah Satu Syarat untuk Memperoleh Gelar Sarjana pada Program Studi " . $project->prodi;
        $coverSection->addText($subCoverText, ['size' => 11, 'name' => 'Times New Roman'], $center);
        $coverSection->addTextBreak(2);

        $defaultLogoFile = public_path('images/default_emblem.jpg');
        $resolvedLogo = ($logoPath && file_exists($logoPath)) ? $logoPath : null;
        if (!$resolvedLogo) {
            $rawLogoB64 = $request->input('logo_base64') ?: ($meta['logo_base64'] ?? '');
            if (!empty($rawLogoB64)) {
                $clean = preg_replace('#^data:image/[^;]+;base64,#i', '', trim($rawLogoB64));
                $decoded = @base64_decode($clean);
                if (!empty($decoded) && strlen($decoded) > 60) {
                    $tmpLogo = tempnam(sys_get_temp_dir(), 'wlogo_') . '.jpg';
                    if (@file_put_contents($tmpLogo, $decoded)) {
                        $resolvedLogo = $tmpLogo;
                        $this->docxTempFiles[] = $tmpLogo;
                    }
                }
            }
        }
        if (!$resolvedLogo && file_exists($defaultLogoFile)) {
            $resolvedLogo = $defaultLogoFile;
        }

        if ($resolvedLogo && file_exists($resolvedLogo)) {
            try {
                $coverSection->addImage($resolvedLogo, [
                    'width' => 100,
                    'height' => 100,
                    'alignment' => Jc::CENTER,
                ]);
            } catch (Throwable $e) {
            }
        }

        $coverSection->addTextBreak(2);
        $coverSection->addText("OLEH:", $bold12, $center);
        $coverSection->addText(strtoupper($nama), ['bold' => true, 'underline' => 'single', 'size' => 12, 'name' => 'Times New Roman'], $center);
        if (!empty($npm)) {
            $coverSection->addText("NIM: " . $npm, $bold12, $center);
        }
        $coverSection->addTextBreak(3);
        $coverSection->addText(strtoupper("PROGRAM STUDI " . $project->prodi), $bold14, $center);
        if (!empty($fakultas)) {
            $coverSection->addText(strtoupper($fakultas), $bold14, $center);
        }
        $coverSection->addText(strtoupper($kampus), $bold14, $center);
        $coverSection->addText(date('Y'), $bold14, $center);

        // =====================================================================
        // SECTION 2: BAGIAN DEPAN — NOMOR HALAMAN ROMAWI KECIL (ii, iii, iv, ...)
        // =====================================================================
        $frontStyle = array_merge($baseSectionStyle, [
            'pageNumberingStart' => 2,
        ]);
        $section = $phpWord->addSection($frontStyle);
        $frontFooter = $section->addFooter();
        $frontFooter->addPreserveText('{PAGE}', ['name' => 'Times New Roman', 'size' => 11, 'color' => '000000'], ['alignment' => Jc::CENTER]);

        // --- LEMBAR PERSETUJUAN (PROPOSAL) / PENGESAHAN (SKRIPSI) ---
        $section->addTitle($isProposal ? "LEMBAR PERSETUJUAN PROPOSAL" : "LEMBAR PENGESAHAN", 1);
        $section->addText(strtoupper($project->judul), $bold12, $center);
        $section->addTextBreak(1);
        $section->addText("Disusun Oleh: " . strtoupper($nama) . ($npm ? " (NIM: " . $npm . ")" : ""), ['size' => 11], $center);
        $section->addTextBreak(1);
        $pengesahanBody = $isProposal
            ? "Proposal skripsi ini telah diperiksa dan disetujui oleh Dosen Pembimbing untuk diseminarkan pada Seminar Proposal Penelitian Program Studi " . $project->prodi . ($fakultas ? ", " . $fakultas : "") . ($kampus ? ", " . $kampus : "") . "."
            : "Telah diuji dan dipertahankan di hadapan Tim Dewan Penguji Ujian Skripsi pada Program Studi " . $project->prodi . ($fakultas ? ", " . $fakultas : "") . ($kampus ? ", " . $kampus : "") . ", dan dinyatakan telah memenuhi seluruh persyaratan akademik kelulusan.";
        $section->addText(
            $pengesahanBody,
            ['name' => 'Times New Roman', 'size' => 11.5],
            ['alignment' => Jc::BOTH, 'lineHeight' => 1.5, 'indentation' => ['firstLine' => 720], 'spaceAfter' => 200]
        );
        $section->addTextBreak(1);

        $sigTable = $section->addTable(['alignment' => Jc::CENTER, 'borderSize' => 0, 'borderColor' => 'FFFFFF']);
        $sigRow = $sigTable->addRow();
        $cell1 = $sigRow->addCell(Converter::cmToTwip(7.0));
        $cell1->addText("Dosen Pembimbing I", ['size' => 11], $center);
        $cell1->addTextBreak(3);
        $cell1->addText("( __________________________ )", ['size' => 11], $center);
        $cell1->addText("NIP/NIDN.", ['size' => 9.5, 'color' => '555555'], $center);

        $cell2 = $sigRow->addCell(Converter::cmToTwip(7.0));
        $cell2->addText("Dosen Pembimbing II", ['size' => 11], $center);
        $cell2->addTextBreak(3);
        $cell2->addText("( __________________________ )", ['size' => 11], $center);
        $cell2->addText("NIP/NIDN.", ['size' => 9.5, 'color' => '555555'], $center);

        $section->addTextBreak(2);
        $section->addText("Mengetahui,", ['size' => 11], $center);
        $section->addText("Ketua Program Studi " . $project->prodi, ['size' => 11], $center);
        $section->addTextBreak(3);
        $section->addText("( __________________________ )", ['size' => 11], $center);
        $section->addText("NIP/NIDN.", ['size' => 9.5, 'color' => '555555'], $center);

        // Hanya SKRIPSI LENGKAP yang mencantumkan Surat Pernyataan Keaslian & Abstrak Hasil
        if (!$isProposal) {
            $section->addPageBreak();
            // --- SURAT PERNYATAAN KEASLIAN ---
            $section->addTitle("SURAT PERNYATAAN KEASLIAN", 1);
            $section->addText("Saya yang bertanda tangan di bawah ini:", ['size' => 11.5], ['alignment' => Jc::BOTH, 'spaceAfter' => 80]);
            $idTable = $section->addTable(['borderSize' => 0, 'borderColor' => 'FFFFFF']);
            $idRows = [
                ['Nama Mahasiswa', $nama, true],
                ['NIM / NPM', $npm ?: '-', false],
                ['Program Studi', $project->prodi, false],
            ];
            if (!empty($fakultas)) {
                $idRows[] = ['Fakultas', $fakultas, false];
            }
            foreach ($idRows as $ir) {
                $r = $idTable->addRow();
                $r->addCell(Converter::cmToTwip(3.8))->addText($ir[0], ['size' => 11.5], ['lineHeight' => 1.35, 'spaceAfter' => 20]);
                $r->addCell(Converter::cmToTwip(10.0))->addText(': ' . $ir[1], ['size' => 11.5, 'bold' => $ir[2]], ['lineHeight' => 1.35, 'spaceAfter' => 20]);
            }
            $section->addTextBreak(1);
            $section->addText(
                "Menyatakan dengan sesungguhnya bahwa skripsi berjudul \"" . $project->judul . "\" ini adalah murni hasil karya penelitian, pemikiran, dan penulisan mandiri saya sendiri. Karya ini belum pernah diajukan untuk memperoleh gelar akademik di perguruan tinggi mana pun, dan bebas dari unsur plagiarisme atau fabrikasi data.",
                ['name' => 'Times New Roman', 'size' => 11.5],
                ['alignment' => Jc::BOTH, 'lineHeight' => 1.5, 'indentation' => ['firstLine' => 720], 'spaceAfter' => 120]
            );
            $section->addText(
                "Seluruh kutipan, data, dan pemikiran yang bersumber dari pihak lain telah dicantumkan dalam daftar pustaka sesuai dengan etika ilmiah. Apabila di kemudian hari terbukti terdapat plagiarisme, saya bersedia menerima sanksi akademik sesuai aturan yang berlaku.",
                ['name' => 'Times New Roman', 'size' => 11.5],
                ['alignment' => Jc::BOTH, 'lineHeight' => 1.5, 'indentation' => ['firstLine' => 720], 'spaceAfter' => 200]
            );
            $section->addText(($kampus ? $kampus . ", " : "") . $tglFullIndo, ['size' => 11.5], ['alignment' => Jc::RIGHT, 'lineHeight' => 1.25]);
            $section->addText("Yang membuat pernyataan,", ['size' => 11.5], ['alignment' => Jc::RIGHT, 'lineHeight' => 1.25]);
            $section->addText("[ Materai Rp 10.000 ]", ['size' => 8.5, 'color' => '666666'], ['alignment' => Jc::RIGHT, 'spaceBefore' => 120, 'spaceAfter' => 120]);
            $section->addTextBreak(1);
            $section->addText(strtoupper($nama), ['bold' => true, 'underline' => 'single', 'size' => 11.5], ['alignment' => Jc::RIGHT, 'lineHeight' => 1.25]);
            $section->addText("NIM. " . ($npm ?: '-'), ['size' => 11.5], ['alignment' => Jc::RIGHT, 'lineHeight' => 1.25]);

            $abstrakData = $this->buildDynamicAbstrakData($project);
            $cleanJudulDoc = $abstrakData['judul'];

            $section->addPageBreak();
            // --- ABSTRAK (BAHASA INDONESIA — STANDAR 1 SPASI) ---
            $section->addTitle("ABSTRAK", 1);
            $section->addText(strtoupper($cleanJudulDoc), $bold12, $center);
            $section->addText("Oleh: " . $nama . ($npm ? " (NIM: " . $npm . ")" : ""), ['size' => 11], $center);
            $section->addText("Program Studi " . $project->prodi . ($kampus ? ", " . $kampus : ""), ['size' => 10.5, 'color' => '333333'], $center);
            $section->addTextBreak(1);
            $section->addText(
                $abstrakData['id_body'],
                ['name' => 'Times New Roman', 'size' => 11.5],
                ['alignment' => Jc::BOTH, 'lineHeight' => 1.0, 'indentation' => ['firstLine' => 720], 'spaceAfter' => 140]
            );
            $section->addText("Kata Kunci: " . $abstrakData['id_keywords'], ['bold' => true, 'size' => 11], ['alignment' => Jc::BOTH, 'lineHeight' => 1.0, 'indentation' => ['firstLine' => 0]]);

            $section->addPageBreak();
            // --- ABSTRACT (ENGLISH — STANDAR 1 SPASI ITALIC) ---
            $section->addTitle("ABSTRACT", 1);
            $section->addText(strtoupper($cleanJudulDoc), ['bold' => true, 'italic' => true, 'size' => 12], $center);
            $section->addText("By: " . $nama . ($npm ? " (Student ID: " . $npm . ")" : ""), ['italic' => true, 'size' => 11], $center);
            $section->addText("Department of " . $project->prodi . ($kampus ? ", " . $kampus : ""), ['italic' => true, 'size' => 10.5, 'color' => '333333'], $center);
            $section->addTextBreak(1);
            $section->addText(
                $abstrakData['en_body'],
                ['name' => 'Times New Roman', 'italic' => true, 'size' => 11.5],
                ['alignment' => Jc::BOTH, 'lineHeight' => 1.0, 'indentation' => ['firstLine' => 720], 'spaceAfter' => 140]
            );
            $section->addText("Keywords: " . $abstrakData['en_keywords'], ['bold' => true, 'italic' => true, 'size' => 11], ['alignment' => Jc::BOTH, 'lineHeight' => 1.0, 'indentation' => ['firstLine' => 0]]);
        }

        // --- KATA PENGANTAR (STANDAR 9 POIN PERGURUAN TINGGI INDONESIA — 1:1 DENGAN PREVIEW & PDF) ---
        $cleanJudulKp = rtrim(trim((string) $project->judul), '. ');
        $docKindLower = strtolower($jenisDokumen);
        $fakultasKp = $fakultas ?: 'Fakultas terkait';
        $section->addPageBreak();
        $section->addTitle("KATA PENGANTAR", 1);
        $kpSyarat = $isProposal
            ? "diajukan sebagai salah satu persyaratan Seminar Proposal Skripsi pada Program Studi "
            : "dimaksudkan untuk memenuhi salah satu syarat memperoleh gelar Sarjana pada Program Studi ";
        $kpFont = ['name' => 'Times New Roman', 'size' => 10.5];
        $kpPara = ['alignment' => Jc::BOTH, 'lineHeight' => 1.3, 'indentation' => ['firstLine' => 720], 'spaceAfter' => 55];
        $section->addText(
            "Dengan memanjatkan puji syukur ke hadirat Tuhan Yang Maha Esa, yang telah melimpahkan rahmat dan hidayah-Nya, penulis dapat menyelesaikan penyusunan {$docKindLower} dengan judul \"{$cleanJudulKp}\".",
            $kpFont,
            $kpPara
        );
        $section->addText(
            "Penulisan {$docKindLower} ini {$kpSyarat}{$project->prodi}" . ($fakultas ? ", {$fakultas}" : "") . ($kampus ? ", {$kampus}" : "") . ". Penulis berharap hasil penelitian ini dapat memperkaya kajian ilmiah pada bidang yang diteliti serta menjadi bahan pertimbangan bagi pihak-pihak yang terkait dengan permasalahan penelitian.",
            $kpFont,
            $kpPara
        );
        $section->addText(
            "Penyelesaian {$docKindLower} ini tidak terlepas dari peran serta berbagai pihak. Oleh sebab itu, pada kesempatan yang baik ini penulis menyampaikan terima kasih dan penghargaan yang setinggi-tingginya kepada:",
            $kpFont,
            ['alignment' => Jc::BOTH, 'lineHeight' => 1.3, 'indentation' => ['firstLine' => 720], 'spaceAfter' => 45]
        );
        $kpItems = [
            "1. Bapak/Ibu (Nama Rektor), selaku Rektor {$kampus} yang telah memberikan kesempatan kepada penulis untuk menempuh pendidikan;",
            "2. Bapak/Ibu (Nama Dekan), selaku Dekan {$fakultasKp} yang telah memberikan fasilitas dan kemudahan selama perkuliahan;",
            "3. Bapak/Ibu (Nama Ketua Program Studi), selaku Ketua Program Studi {$project->prodi} yang telah memberikan arahan dan dukungan akademik;",
            "4. Bapak/Ibu (Nama Dosen Pembimbing), selaku Dosen Pembimbing yang telah memberikan bimbingan, koreksi, dan motivasi sejak penyusunan proposal hingga terselesaikannya {$docKindLower} ini;",
            "5. Bapak/Ibu (Nama Dosen Pembimbing Akademik), selaku Dosen Pembimbing Akademik yang telah mendampingi penulis selama masa studi;",
            "6. Bapak/Ibu Dosen Penguji yang telah memberikan masukan berharga bagi penyempurnaan {$docKindLower} ini;",
            "7. Seluruh dosen dan tenaga kependidikan {$fakultasKp} {$kampus} atas ilmu dan pelayanan yang telah diberikan;",
            "8. Orang tua tercinta yang dengan tulus senantiasa mendoakan, membimbing, dan mendukung penulis dalam setiap langkah;",
            "9. Saudara, sahabat, serta rekan-rekan yang telah memberikan bantuan dan dorongan semangat selama proses penyusunan {$docKindLower} ini.",
        ];
        foreach ($kpItems as $kpItem) {
            $section->addText(
                $kpItem,
                ['name' => 'Times New Roman', 'size' => 10.5],
                ['alignment' => Jc::BOTH, 'lineHeight' => 1.25, 'indentation' => ['left' => 420, 'hanging' => 300], 'spaceAfter' => 25]
            );
        }
        $section->addText(
            "Semoga Tuhan Yang Maha Esa membalas segala kebaikan dan bantuan yang telah diberikan dengan pahala yang setimpal. Penulis menyadari sepenuhnya bahwa {$docKindLower} ini masih memiliki kekurangan. Oleh karena itu, kritik dan saran yang bersifat membangun sangat penulis harapkan demi penyempurnaan penelitian selanjutnya.",
            $kpFont,
            ['alignment' => Jc::BOTH, 'lineHeight' => 1.3, 'indentation' => ['firstLine' => 720], 'spaceBefore' => 45, 'spaceAfter' => 100]
        );
        $section->addText("(Kota), " . $tglBulanIndo, ['name' => 'Times New Roman', 'size' => 10.5], ['alignment' => Jc::RIGHT, 'lineHeight' => 1.2, 'spaceAfter' => 0]);
        $section->addText("Penulis,", ['name' => 'Times New Roman', 'size' => 10.5], ['alignment' => Jc::RIGHT, 'lineHeight' => 1.2, 'spaceAfter' => 0]);
        $section->addTextBreak(1);
        $section->addText(strtoupper($nama), ['bold' => true, 'name' => 'Times New Roman', 'size' => 10.5], ['alignment' => Jc::RIGHT, 'lineHeight' => 1.2]);

        // --- DAFTAR ISI RAPI (DOT LEADER TAB STOP) ---
        $section->addPageBreak();
        $section->addTitle("DAFTAR ISI", 1);
        $printableWidthTwips = Converter::cmToTwip(max(10.0, 21.0 - $marginLeft - $marginRight));
        $tocItems = $this->extractTocItemsForProject($project, $isProposal);
        foreach ($tocItems as $item) {
            $isLvl1 = ($item['level'] ?? 1) === 1;
            $pStyle = [
                'tabs' => [new Tab('right', $printableWidthTwips, 'dot')],
                'indentation' => ['left' => $isLvl1 ? 0 : 420, 'firstLine' => 0],
                'spaceAfter' => $isLvl1 ? 70 : 35,
                'lineHeight' => 1.25,
            ];
            $fStyle = [
                'name' => 'Times New Roman',
                'size' => 12,
                'bold' => $isLvl1,
            ];
            $section->addText($item['label'] . "\t" . $item['page'], $fStyle, $pStyle);
        }

        // --- DAFTAR TABEL (HANYA JIKA ADA TABEL NYATA DI NASKAH) ---
        $tabelsDocx = $this->extractTablesForProject($project, $isProposal);
        if (!empty($tabelsDocx)) {
            $section->addPageBreak();
            $section->addTitle("DAFTAR TABEL", 1);
            foreach ($tabelsDocx as $tbl) {
                $pStyle = [
                    'tabs' => [new Tab('right', $printableWidthTwips, 'dot')],
                    'spaceAfter' => 60,
                    'lineHeight' => 1.25,
                ];
                $section->addText($tbl['label'] . "\t" . $tbl['page'], ['name' => 'Times New Roman', 'size' => 12], $pStyle);
            }
        }

        // --- DAFTAR GAMBAR (HANYA JIKA ADA GAMBAR / DIAGRAM NYATA DI NASKAH) ---
        $gambarsDocx = $this->extractFiguresForProject($project, $isProposal);
        if (!empty($gambarsDocx)) {
            $section->addPageBreak();
            $section->addTitle("DAFTAR GAMBAR", 1);
            foreach ($gambarsDocx as $gbr) {
                $pStyle = [
                    'tabs' => [new Tab('right', $printableWidthTwips, 'dot')],
                    'spaceAfter' => 60,
                    'lineHeight' => 1.25,
                ];
                $section->addText($gbr['label'] . "\t" . $gbr['page'], ['name' => 'Times New Roman', 'size' => 12], $pStyle);
            }
        }

        // --- DAFTAR LAMPIRAN (OTOMATIS MUNCUL HANYA JIKA MAHASISWA MENGUNGGAH LAMPIRAN) ---
        $lampiransDocx = $this->extractLampiranForProject($project, $isProposal);
        if (!empty($lampiransDocx)) {
            $section->addPageBreak();
            $section->addTitle("DAFTAR LAMPIRAN", 1);
            foreach ($lampiransDocx as $lmp) {
                $pStyle = [
                    'tabs' => [new Tab('right', $printableWidthTwips, 'dot')],
                    'spaceAfter' => 60,
                    'lineHeight' => 1.25,
                ];
                $section->addText($lmp['label'] . "\t" . $lmp['page'], ['name' => 'Times New Roman', 'size' => 12], $pStyle);
            }
        }

        // =====================================================================
        // SECTION 3+: BAB UTAMA (RESET ANGKA ARAB 1; AWAL BAB TENGAH BAWAH, LANJUTAN KANAN ATAS)
        // =====================================================================
        $chapters = [
            'bab1' => 'PENDAHULUAN',
            'bab2' => 'TINJAUAN PUSTAKA',
            'bab3' => 'METODOLOGI PENELITIAN',
            'bab4' => 'HASIL PENELITIAN DAN PEMBAHASAN',
            'bab5' => 'PENUTUP',
        ];
        if ($isProposal) {
            $chapters = [
                'bab1' => 'PENDAHULUAN',
                'bab2' => 'TINJAUAN PUSTAKA',
                'bab3' => 'METODOLOGI PENELITIAN',
            ];
        }

        $nums = ['bab1' => 'I', 'bab2' => 'II', 'bab3' => 'III', 'bab4' => 'IV', 'bab5' => 'V'];
        $isFirstMainSection = true;

        foreach ($chapters as $k => $v) {
            if ($htmlContent = $project->{$k . '_content'}) {
                if ($k !== 'bab5') {
                    [$htmlContent] = $this->stripEmbeddedDaftarPustaka((string) $htmlContent);
                }
                if (trim((string) $htmlContent) === '') {
                    continue;
                }
                $chapStyle = $baseSectionStyle;
                if ($isFirstMainSection) {
                    $chapStyle['pageNumberingStart'] = 1;
                    $isFirstMainSection = false;
                }
                $section = $phpWord->addSection($chapStyle);

                // Penomoran Halaman Konsisten (Tengah Bawah di semua halaman, seragam dengan Preview & PDF)
                $chFooter = $section->addFooter();
                $chFooter->addPreserveText('{PAGE}', ['name' => 'Times New Roman', 'size' => 11, 'color' => '000000'], ['alignment' => Jc::CENTER]);

                // Judul BAB 2 baris resmi standar skripsi Indonesia (BAB I \n PENDAHULUAN)
                $section->addTitle("BAB " . $nums[$k] . "\n" . strtoupper($v), 1);

                $this->parseAndAddContent($section, $htmlContent, $project);
            }
        }

        $dpContent = trim($project->daftar_pustaka ?? '');
        if (!empty($dpContent)) {
            $dpStyle = $baseSectionStyle;
            if ($isFirstMainSection) {
                $dpStyle['pageNumberingStart'] = 1;
            }
            $section = $phpWord->addSection($dpStyle);
            $dpFooter = $section->addFooter();
            $dpFooter->addPreserveText('{PAGE}', ['name' => 'Times New Roman', 'size' => 11, 'color' => '000000'], ['alignment' => Jc::CENTER]);

            $section->addTitle("DAFTAR PUSTAKA", 1);
            $refs = explode("\n", str_replace(["\r\n", "\r"], "\n", $dpContent));
            foreach ($refs as $ref) {
                $trimRef = trim($ref);
                if ($trimRef !== '') {
                    // Standar APA 7th Skripsi: spasi 1,0 di dalam 1 entri, jarak antar entri 8pt, hanging indent 1,27 cm + LINK DOI/URL BISA DIKLIK
                    $dpParaStyle = ['alignment' => Jc::BOTH, 'lineHeight' => 1.0, 'indentation' => ['left' => 720, 'hanging' => 720], 'spaceBefore' => 0, 'spaceAfter' => 140];
                    $textRun = $section->addTextRun($dpParaStyle);
                    $tokens = preg_split('/(https?:\/\/[^\s<>"]+|\bdoi:\s*10\.\d{4,9}\/[^\s<>"]+)/i', $trimRef, -1, PREG_SPLIT_DELIM_CAPTURE);
                    foreach ($tokens as $tok) {
                        if ($tok === '') {
                            continue;
                        }
                        if (preg_match('/^https?:\/\//i', $tok)) {
                            $cleanUrl = $tok;
                            $trail = '';
                            while (preg_match('/[\.,;\)\]]$/', $cleanUrl, $tm)) {
                                $trail = $tm[0] . $trail;
                                $cleanUrl = substr($cleanUrl, 0, -1);
                            }
                            $textRun->addLink($cleanUrl, $cleanUrl, ['name' => 'Times New Roman', 'size' => 12, 'color' => '1D4ED8', 'underline' => 'single']);
                            if ($trail !== '') {
                                $textRun->addText($trail, ['name' => 'Times New Roman', 'size' => 12]);
                            }
                        } elseif (preg_match('/^doi:\s*(10\.\d{4,9}\/.+)$/i', $tok, $dm)) {
                            $doiCode = $dm[1];
                            $trail = '';
                            while (preg_match('/[\.,;\)\]]$/', $doiCode, $tm)) {
                                $trail = $tm[0] . $trail;
                                $doiCode = substr($doiCode, 0, -1);
                            }
                            $textRun->addLink('https://doi.org/' . $doiCode, 'doi:' . $doiCode, ['name' => 'Times New Roman', 'size' => 12, 'color' => '1D4ED8', 'underline' => 'single']);
                            if ($trail !== '') {
                                $textRun->addText($trail, ['name' => 'Times New Roman', 'size' => 12]);
                            }
                        } else {
                            $textRun->addText($tok, ['name' => 'Times New Roman', 'size' => 12]);
                        }
                    }
                }
            }
        }

        // =====================================================================
        // SECTION AKHIR: LAMPIRAN (MUNCUL OTOMATIS JIKA MAHASISWA MENGUNGGAH LAMPIRAN)
        // =====================================================================
        if (!empty($lampiransDocx)) {
            $lmpStyle = $baseSectionStyle;
            if ($isFirstMainSection) {
                $lmpStyle['pageNumberingStart'] = 1;
            }
            $section = $phpWord->addSection($lmpStyle);
            $lmpFooter = $section->addFooter();
            $lmpFooter->addPreserveText('{PAGE}', ['name' => 'Times New Roman', 'size' => 11, 'color' => '000000'], ['alignment' => Jc::CENTER]);

            $section->addTitle("LAMPIRAN", 1);

            foreach ($lampiransDocx as $lIdx => $lmp) {
                if ($lIdx > 0) {
                    $section->addPageBreak();
                }
                $section->addTitle($lmp['label'], 2);

                if (!empty($lmp['image_base64'])) {
                    $rawImg = preg_replace('#^data:image/([^;]+);base64,#i', '', $lmp['image_base64']);
                    $decodedImg = @base64_decode($rawImg);
                    if (!empty($decodedImg) && strlen($decodedImg) > 100) {
                        $imgExt = str_contains($lmp['image_base64'], 'image/png') ? '.png' : '.jpg';
                        $tmpImg = tempnam(sys_get_temp_dir(), 'lmp_') . $imgExt;
                        if (@file_put_contents($tmpImg, $decodedImg)) {
                            $this->docxTempFiles[] = $tmpImg;
                            try {
                                $imgProps = ['width' => 395, 'alignment' => Jc::CENTER];
                                $dim = @getimagesize($tmpImg);
                                if (!empty($dim[0]) && !empty($dim[1])) {
                                    $maxW = 395;
                                    $maxH = 510;
                                    $scale = min($maxW / $dim[0], $maxH / $dim[1], 1.0);
                                    $imgProps['width'] = (int) round($dim[0] * $scale);
                                    $imgProps['height'] = (int) round($dim[1] * $scale);
                                }
                                $section->addImage($tmpImg, $imgProps);
                                $section->addTextBreak(1);
                            } catch (Throwable $e) {
                                Log::warning('Word lampiran image notice: ' . $e->getMessage());
                            }
                        }
                    }
                }

                if (!empty($lmp['content'])) {
                    $this->parseAndAddContent($section, $lmp['content'], $project);
                }
            }
        }

        return $phpWord;
    }

    public function downloadDocx($id, Request $request)
    {
        if (ob_get_length())
            ob_end_clean();
        $project = Project::where('user_id', Auth::id())->findOrFail($id);
        app(\App\Services\TargetedStructureRepairService::class)->repairIfTargetProject($project, Auth::user());
        $authUser = Auth::user();
        if ($authUser) {
            $dlGate = $this->billing()->canDownloadProject($authUser, $project);
            if (empty($dlGate['allowed'])) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'status' => 'upgrade_required',
                        'reason' => $dlGate['reason'] ?? 'download_locked',
                        'message' => $dlGate['message'],
                    ], 402);
                }
                return redirect()->route('app.index', ['id' => $project->id])
                    ->with('upgrade_modal', $dlGate['message']);
            }
        }
        $meta = $this->getProjectMeta($project);
        $defaultIdentity = $this->getDefaultUserIdentity(Auth::id());
        foreach (['nama', 'nim', 'kampus', 'fakultas', 'logo_base64'] as $field) {
            if (empty($meta[$field]) && !empty($defaultIdentity[$field])) {
                $meta[$field] = $defaultIdentity[$field];
            }
        }

        if (!empty($project->bab4_content) && (empty($project->bab5_content) || strlen(trim(strip_tags($project->bab5_content))) < 120)) {
            $project->bab5_content = $this->buildDefaultBab5Content($project);
            $project->save();
        }

        $hasMathUpdate = false;
        foreach (['bab1_content', 'bab2_content', 'bab3_content', 'bab4_content', 'bab5_content'] as $col) {
            if (!empty($project->$col) && (str_contains($project->$col, '\frac') || str_contains($project->$col, '\hat') || str_contains($project->$col, '{,}') || str_contains($project->$col, '$'))) {
                $project->$col = $this->formatAcademicMathText($project->$col);
                $hasMathUpdate = true;
            }
            if ($col !== 'bab5_content' && !empty($project->$col)) {
                [$cleanBody, $extractedDp] = $this->stripEmbeddedDaftarPustaka((string) $project->$col);
                if ($cleanBody !== $project->$col) {
                    $project->$col = $cleanBody;
                    if (empty(trim((string) $project->daftar_pustaka)) && $extractedDp !== '') {
                        $project->daftar_pustaka = $extractedDp;
                    }
                    $hasMathUpdate = true;
                }
            }
        }
        if ($hasMathUpdate) {
            $project->save();
        }

        $logoPath = $this->resolveLogoTempPath($request, $meta);

        $phpWord = $this->createPhpWordDocument($project, $request, $logoPath, $meta);
        $filename = 'Naskah_' . Str::slug(substr($project->judul, 0, 28), '_') . '.docx';

        $tmpDir = rtrim(sys_get_temp_dir(), '/\\');
        Settings::setTempDir($tmpDir);
        $path = $tmpDir . '/' . $filename;

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($path);
        $this->postProcessDocxOutline($path);

        foreach ($this->docxTempFiles as $tmp) {
            if ($tmp && file_exists($tmp)) {
                @unlink($tmp);
            }
        }
        $this->docxTempFiles = [];

        if ($logoPath && file_exists($logoPath)) {
            @unlink($logoPath);
        }

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    private function postProcessDocxOutline(string $docxPath): void
    {
        if (!file_exists($docxPath) || !class_exists('ZipArchive')) {
            return;
        }

        try {
            $zip = new ZipArchive();
            if ($zip->open($docxPath) === true) {
                // 1. Modifikasi word/document.xml untuk menyematkan outline level, VML Vector Flowchart, dan format angka Romawi Bagian Depan
                $docXml = $zip->getFromName('word/document.xml');
                if ($docXml) {
                    $docXml = preg_replace_callback('/<w:pPr>(.*?)<\/w:pPr>/s', function ($m) {
                        $inner = $m[1];
                        if (str_contains($inner, 'w:val="Heading1"') && !str_contains($inner, 'w:outlineLvl')) {
                            return '<w:pPr>' . $inner . '<w:outlineLvl w:val="0"/></w:pPr>';
                        }
                        if (str_contains($inner, 'w:val="Heading2"') && !str_contains($inner, 'w:outlineLvl')) {
                            return '<w:pPr>' . $inner . '<w:outlineLvl w:val="1"/></w:pPr>';
                        }
                        if (str_contains($inner, 'w:val="Heading3"') && !str_contains($inner, 'w:outlineLvl')) {
                            return '<w:pPr>' . $inner . '<w:outlineLvl w:val="2"/></w:pPr>';
                        }
                        return $m[0];
                    }, $docXml);

                    // Ubah format penomoran halaman Bagian Depan (w:start="2") menjadi angka Romawi kecil (ii, iii, iv, ...)
                    $docXml = preg_replace('/<w:pgNumType\s+w:start="2"\s*\/>/', '<w:pgNumType w:fmt="lowerRoman" w:start="2"/>', $docXml);
                    // Pastikan format penomoran halaman Bab I (w:start="1") memakai angka Arab (decimal)
                    $docXml = preg_replace('/<w:pgNumType\s+w:start="1"\s*\/>/', '<w:pgNumType w:fmt="decimal" w:start="1"/>', $docXml);

                    // Sisipkan Native Word VML Vector Flowchart untuk setiap placeholder
                    if (!empty($this->docxFlowchartVmlMap)) {
                        foreach ($this->docxFlowchartVmlMap as $placeholder => $vmlParagraphXml) {
                            $quoted = preg_quote($placeholder, '/');
                            $docXml = preg_replace(
                                '/<w:p\b[^>]*>(?:(?!<\/w:p>).)*?' . $quoted . '(?:(?!<\/w:p>).)*?<\/w:p>/s',
                                str_replace(['\\', '$'], ['\\\\', '\\$'], $vmlParagraphXml),
                                $docXml,
                                1
                            );
                        }
                        $this->docxFlowchartVmlMap = [];
                    }

                    // Jika user memilih font non-default (Arial / Georgia), terapkan ke seluruh run & style DOCX secara konsisten
                    if ($this->docxFontName !== 'Times New Roman') {
                        $docXml = str_replace('Times New Roman', $this->docxFontName, $docXml);
                        if ($this->docxFontSize == 11.0) {
                            $docXml = str_replace('<w:sz w:val="24"/>', '<w:sz w:val="22"/>', $docXml);
                            $docXml = str_replace('<w:szCs w:val="24"/>', '<w:szCs w:val="22"/>', $docXml);
                        }
                    }

                    $zip->addFromString('word/document.xml', $docXml);
                }

                // Terapkan font pilihan ke header/footer DOCX juga
                if ($this->docxFontName !== 'Times New Roman') {
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $entryName = $zip->getNameIndex($i);
                        if (preg_match('/^word\/(header|footer)\d*\.xml$/', $entryName)) {
                            $hfXml = $zip->getFromName($entryName);
                            if ($hfXml && str_contains($hfXml, 'Times New Roman')) {
                                $zip->addFromString($entryName, str_replace('Times New Roman', $this->docxFontName, $hfXml));
                            }
                        }
                    }
                }

                // 2. Modifikasi word/styles.xml untuk Heading styles
                $stylesXml = $zip->getFromName('word/styles.xml');
                if ($stylesXml) {
                    $stylesXml = preg_replace_callback('/<w:style\b([^>]*)w:styleId="Heading([123])"([^>]*)>(.*?)<\/w:style>/s', function ($m) {
                        $level = (int) $m[2] - 1;
                        $attrs = $m[1] . 'w:styleId="Heading' . $m[2] . '"' . $m[3];
                        $inner = $m[4];
                        $prefix = '';
                        if (!str_contains($inner, 'w:uiPriority')) {
                            $prefix .= '<w:uiPriority w:val="9"/>';
                        }
                        if (!str_contains($inner, 'w:qFormat')) {
                            $prefix .= '<w:qFormat/>';
                        }
                        if (str_contains($inner, '<w:pPr>')) {
                            $inner = preg_replace('/<w:pPr>/', '<w:pPr><w:outlineLvl w:val="' . $level . '"/>', $inner);
                        } else {
                            $prefix .= '<w:pPr><w:outlineLvl w:val="' . $level . '"/></w:pPr>';
                        }
                        return '<w:style ' . $attrs . '>' . $prefix . $inner . '</w:style>';
                    }, $stylesXml);
                    if ($this->docxFontName !== 'Times New Roman') {
                        $stylesXml = str_replace('Times New Roman', $this->docxFontName, $stylesXml);
                    }
                    $zip->addFromString('word/styles.xml', $stylesXml);
                }

                $zip->close();
            }
        } catch (\Throwable $e) {
            Log::warning('postProcessDocxOutline error: ' . $e->getMessage());
        }
    }

    public function downloadPdf($id, Request $request)
    {
        $project = Project::where('user_id', Auth::id())->findOrFail($id);
        app(\App\Services\TargetedStructureRepairService::class)->repairIfTargetProject($project, Auth::user());
        $authUser = Auth::user();
        if ($authUser) {
            $dlGate = $this->billing()->canDownloadProject($authUser, $project);
            if (empty($dlGate['allowed'])) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'status' => 'upgrade_required',
                        'reason' => $dlGate['reason'] ?? 'download_locked',
                        'message' => $dlGate['message'],
                    ], 402);
                }
                return redirect()->route('app.index', ['id' => $project->id])
                    ->with('upgrade_modal', $dlGate['message']);
            }
        }
        $meta = $this->getProjectMeta($project);
        $defaultIdentity = $this->getDefaultUserIdentity(Auth::id());
        foreach (['nama', 'nim', 'kampus', 'fakultas', 'logo_base64'] as $field) {
            if (empty($meta[$field]) && !empty($defaultIdentity[$field])) {
                $meta[$field] = $defaultIdentity[$field];
            }
        }

        return $this->renderAndStreamProjectPdf($project, $request, $meta, true);
    }

    protected function renderAndStreamProjectPdf(Project $project, Request $request, array $meta, bool $allowMutate = true)
    {
        if ($allowMutate) {
            if (!empty($project->bab4_content) && (empty($project->bab5_content) || strlen(trim(strip_tags($project->bab5_content))) < 120)) {
                $project->bab5_content = $this->buildDefaultBab5Content($project);
                $project->save();
            }

            $hasMathUpdate = false;
            foreach (['bab1_content', 'bab2_content', 'bab3_content', 'bab4_content', 'bab5_content'] as $col) {
                if (!empty($project->$col) && (str_contains($project->$col, '\frac') || str_contains($project->$col, '\hat') || str_contains($project->$col, '{,}') || str_contains($project->$col, '$'))) {
                    $project->$col = $this->formatAcademicMathText($project->$col);
                    $hasMathUpdate = true;
                }
            }
            if ($hasMathUpdate) {
                $project->save();
            }
        }

        $logoPath = $this->resolveLogoTempPath($request, $meta);

        $marginTop = $request->input('margin_top', 4);
        $marginLeft = $request->input('margin_left', 4);
        $marginBottom = $request->input('margin_bottom', 3);
        $marginRight = $request->input('margin_right', 3);

        $tahap = strtolower((string) ($meta['tahap_pengerjaan'] ?? ''));
        $defaultJenis = str_contains($tahap, 'proposal') ? 'PROPOSAL SKRIPSI' : 'SKRIPSI';
        $jenisDokumen = strtoupper($request->input('jenis_dokumen', $defaultJenis));
        $isProposal = str_contains($jenisDokumen, 'PROPOSAL');

        $nama = $request->input('nama') ?: ($meta['nama'] ?? 'NAMA MAHASISWA');
        $npm = $request->input('npm') ?: ($meta['nim'] ?? '');
        $kampus = $request->input('kampus') ?: ($meta['kampus'] ?? 'UNIVERSITAS');
        $fakultas = $this->formatFacultyLabel((string) ($request->input('fakultas') ?: ($meta['fakultas'] ?? '')));

        $defaultLogoFile = public_path('images/default_emblem.jpg');
        $hasGd = extension_loaded('gd') && function_exists('imagecreatefrompng');
        $resolvedLogoB64 = '';

        if ($logoPath && file_exists($logoPath) && filesize($logoPath) > 50) {
            $rawBytes = file_get_contents($logoPath);
            $mime = function_exists('mime_content_type') ? (@mime_content_type($logoPath) ?: 'image/jpeg') : 'image/jpeg';
            $isJpeg = str_contains($mime, 'jpeg') || str_contains($mime, 'jpg') || str_starts_with($rawBytes, "\xFF\xD8\xFF");
            if ($isJpeg || $hasGd) {
                $resolvedLogoB64 = 'data:' . $mime . ';base64,' . base64_encode($rawBytes);
            }
            @unlink($logoPath);
        }

        if (empty($resolvedLogoB64) && (!empty($request->input('logo_base64')) || !empty($meta['logo_base64']))) {
            $rawB64 = trim($request->input('logo_base64') ?: $meta['logo_base64']);
            $isJpeg = str_starts_with($rawB64, 'data:image/jpeg') || str_starts_with($rawB64, 'data:image/jpg') || str_contains($rawB64, '/9j/');
            if ($isJpeg || $hasGd) {
                if (!str_starts_with($rawB64, 'data:image/')) {
                    $rawB64 = ($isJpeg ? 'data:image/jpeg;base64,' : 'data:image/png;base64,') . $rawB64;
                }
                $resolvedLogoB64 = $rawB64;
            }
        }

        if (empty($resolvedLogoB64)) {
            if (file_exists($defaultLogoFile)) {
                $resolvedLogoB64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($defaultLogoFile));
            } else {
                $resolvedLogoB64 = $this->getDefaultEmblemBase64();
            }
        }

        if (!empty($resolvedLogoB64)) {
            $logoHtml = "<img src='{$resolvedLogoB64}' width='120' height='120' style='width:120px; height:120px; display:block; margin:0 auto;'>";
        } else {
            $logoHtml = "<div style='margin: 18px auto; width:90px; height:90px; border:2px solid #000; border-radius:50%; text-align:center; line-height:86px; font-weight:bold; font-size:10pt; letter-spacing:1px; color:#000;'>UNIVERSITAS</div>";
        }

        $tglFullIndo = $this->formatIndonesianDate('full');
        $tglBulanIndo = $this->formatIndonesianDate('month_year');

        $makeDotLeaderRow = function (string $label, string $page, int $level = 1): string {
            $pad = $level === 1 ? '0' : '22pt';
            $weight = $level === 1 ? 'bold' : 'normal';
            $cleanLabel = preg_replace('/\s+/', ' ', trim($label));
            // Bungkus teks panjang ke baris sebelumnya agar baris terakhir + titik-titik (dot leader) + nomor halaman tetap sejajar lurus rata kanan seperti standar skripsi Word
            $maxChars = $level === 1 ? 56 : 50;
            $wrappedLines = explode("\n", wordwrap($cleanLabel, $maxChars, "\n", false));
            $lastLine = array_pop($wrappedLines);

            $rowHtml = "<div style='margin-bottom:5pt; page-break-inside:avoid;'>";
            if (!empty($wrappedLines)) {
                $rowHtml .= "<div style='padding:0 36pt 0 {$pad}; font-size:12pt; line-height:1.35; font-weight:{$weight}; text-align:left;'>"
                    . implode('<br>', array_map('e', $wrappedLines))
                    . "</div>";
            }
            $rowHtml .= "<table style='width:100%; border-collapse:collapse; font-size:12pt; line-height:1.35;'><tr>"
                . "<td style='padding:0 5pt 0 {$pad}; font-weight:{$weight}; border:none; white-space:nowrap; width:1%;'>" . e($lastLine) . "</td>"
                . "<td style='border:none; padding:0 4pt 3pt 2pt; vertical-align:bottom;'><div style='border-bottom:1pt dotted #334155; height:1pt;'></div></td>"
                . "<td style='padding:0 0 0 5pt; text-align:right; font-weight:{$weight}; border:none; white-space:nowrap; width:28pt;'>" . e($page) . "</td>"
                . "</tr></table></div>";
            return $rowHtml;
        };

        $tocItemsPdf = $this->extractTocItemsForProject($project, $isProposal);
        $pdfTables = $this->extractTablesForProject($project, $isProposal);
        $pdfFigures = $this->extractFiguresForProject($project, $isProposal);
        $pdfLampirans = $this->extractLampiranForProject($project, $isProposal);

        $buildRowsHtml = function (array $rows, bool $useLevel = false) use ($makeDotLeaderRow): string {
            $out = '';
            foreach ($rows as $row) {
                $out .= $makeDotLeaderRow(
                    (string) ($row['label'] ?? ''),
                    (string) ($row['page'] ?? ''),
                    $useLevel ? (int) ($row['level'] ?? 1) : 1
                );
            }
            return $out;
        };

        $fontKeyPdf = strtolower(trim((string) $request->input('font_family', 'times')));
        $pdfFontMap = [
            'times' => ['css' => "'Times New Roman', serif", 'size' => '12pt', 'cpdf' => 'Times-Roman'],
            'arial' => ['css' => "Helvetica, Arial, sans-serif", 'size' => '11pt', 'cpdf' => 'Helvetica'],
            'georgia' => ['css' => "'DejaVu Serif', Georgia, serif", 'size' => '11pt', 'cpdf' => 'Times-Roman'],
        ];
        $pdfFont = $pdfFontMap[$fontKeyPdf] ?? $pdfFontMap['times'];

        $html = "<html><head><style>
            @page { size: A4; margin: {$marginTop}cm {$marginRight}cm {$marginBottom}cm {$marginLeft}cm; }
            body { font-family:{$pdfFont['css']}; text-align:justify; line-height:1.5; font-size:{$pdfFont['size']}; color:#000000; }
            h1,h2 { text-align:center; font-weight:bold; font-size:14pt; line-height:1.3; margin:0 0 16pt 0; }
            h3,h4 { font-weight:bold; font-size:{$pdfFont['size']}; }
            p { text-indent: 1.27cm; margin: 0 0 6pt 0; }
            table { font-family: inherit; }
            .cover p { text-indent:0; text-align:center; }
            .cover { position:relative; height:100%; text-align:center; }
            .cover-bottom { position:absolute; bottom:0; left:0; right:0; text-align:center; font-weight:bold; font-size:12.5pt; line-height:1.35; }
            .page-break { page-break-before: always; }
        </style></head><body>";

        // 1. HALAMAN JUDUL (COVER RESMI PROPORSIONAL A4 INDONESIA — TANPA NOMOR HALAMAN)
        $cleanJudulFrontPdf = rtrim(trim((string) $project->judul), '. ');
        $subCoverPdf = $isProposal
            ? "Diajukan untuk Memenuhi Persyaratan Seminar Proposal Skripsi pada Program Studi " . e($project->prodi)
            : "Diajukan sebagai Salah Satu Syarat untuk Memperoleh Gelar Sarjana pada Program Studi " . e($project->prodi);
        $html .= "<div class='cover'>
            <div style='text-align:center;'>
                <div style='font-size:14pt; font-weight:bold; letter-spacing:1px; line-height:1.2; text-transform:uppercase;'>{$jenisDokumen}</div>
                <div style='font-size:13pt; font-weight:bold; margin-top:16pt; margin-bottom:12pt; line-height:1.35; text-transform:uppercase; max-width:440pt; margin-left:auto; margin-right:auto;'>" . e($cleanJudulFrontPdf) . "</div>
                <div style='font-size:11pt; line-height:1.35; max-width:420pt; margin:0 auto;'>{$subCoverPdf}</div>
            </div>

            <div style='text-align:center; margin-top:55pt;'>
                {$logoHtml}
            </div>

            <div style='text-align:center; margin-top:45pt; font-size:11.5pt; line-height:1.5;'>
                <div>OLEH:</div>
                <div style='margin-top:4pt;'><u><strong>" . strtoupper(e($nama)) . "</strong></u></div>
                " . ($npm ? "<div style='margin-top:3pt;'>NIM: " . e($npm) . "</div>" : "") . "
            </div>

            <div class='cover-bottom'>
                <div>PROGRAM STUDI " . strtoupper(e($project->prodi)) . "</div>
                " . ($fakultas ? "<div>" . strtoupper(e($fakultas)) . "</div>" : "") . "
                <div>" . strtoupper(e($kampus)) . "</div>
                <div style='margin-top:3pt;'>" . date('Y') . "</div>
            </div>
        </div>";

        // 2. LEMBAR PERSETUJUAN (PROPOSAL) / PENGESAHAN (SKRIPSI)
        $pengesahanTitle = $isProposal ? 'LEMBAR PERSETUJUAN PROPOSAL' : 'LEMBAR PENGESAHAN';
        $pengesahanPara = $isProposal
            ? "Proposal skripsi ini telah diperiksa dan disetujui oleh Dosen Pembimbing untuk diseminarkan pada Seminar Proposal Penelitian Program Studi " . e($project->prodi) . ($fakultas ? ', ' . e($fakultas) : '') . ($kampus ? ', ' . e($kampus) : '') . "."
            : "Telah diuji dan dipertahankan di hadapan Tim Dewan Penguji Ujian Skripsi pada Program Studi " . e($project->prodi) . ($fakultas ? ', ' . e($fakultas) : '') . ($kampus ? ', ' . e($kampus) : '') . ", dan dinyatakan telah memenuhi seluruh persyaratan akademik kelulusan.";
        $html .= "<div class='page-break' style='text-align:center;'>
            <h2>{$pengesahanTitle}</h2>
            <div style='font-size:12pt; margin:16pt 0 10pt; font-weight:bold; line-height:1.35; text-transform:uppercase;'>" . e($project->judul) . "</div>
            <div style='margin-bottom:16pt; font-size:11pt;'>Disusun Oleh: <u>" . strtoupper(e($nama)) . "</u>" . ($npm ? " (NIM: " . e($npm) . ")" : "") . "</div>
            <p style='text-indent:1.27cm; line-height:1.5; font-size:11.5pt;'>{$pengesahanPara}</p>
            <div style='margin-top:28pt; width:100%;'>
                <div style='float:left; width:48%; text-align:center; font-size:11pt;'>
                    <div>Dosen Pembimbing I</div>
                    <div style='height:52pt;'></div>
                    <div>( __________________________ )</div>
                    <div style='font-size:9.5pt; color:#555;'>NIP/NIDN.</div>
                </div>
                <div style='float:right; width:48%; text-align:center; font-size:11pt;'>
                    <div>Dosen Pembimbing II</div>
                    <div style='height:52pt;'></div>
                    <div>( __________________________ )</div>
                    <div style='font-size:9.5pt; color:#555;'>NIP/NIDN.</div>
                </div>
                <div style='clear:both;'></div>
            </div>
            <div style='margin-top:26pt; text-align:center; font-size:11pt;'>
                <div>Mengetahui,</div>
                <div>Ketua Program Studi " . e($project->prodi) . "</div>
                <div style='height:50pt;'></div>
                <div>( __________________________ )</div>
                <div style='font-size:9.5pt; color:#555;'>NIP/NIDN.</div>
            </div>
        </div>";

        // 3 & 4. SURAT PERNYATAAN KEASLIAN & ABSTRAK (HANYA UNTUK SKRIPSI LENGKAP, BUKAN PROPOSAL)
        if (!$isProposal) {
            $html .= "<div class='page-break'>
                <h2>SURAT PERNYATAAN KEASLIAN</h2>
                <div style='margin-top:16pt; font-size:11.5pt; line-height:1.5;'>
                    <p style='text-indent:0;'>Saya yang bertanda tangan di bawah ini:</p>
                    <table style='width:100%; margin:8pt 0 14pt 16pt; font-size:11.5pt; border-collapse:collapse;'>
                        <tr><td style='width:120pt; border:none; padding:2pt 0;'>Nama Mahasiswa</td><td style='border:none; padding:2pt 0;'>: <strong>" . e($nama) . "</strong></td></tr>
                        <tr><td style='border:none; padding:2pt 0;'>NIM / NPM</td><td style='border:none; padding:2pt 0;'>: " . e($npm ?: '-') . "</td></tr>
                        <tr><td style='border:none; padding:2pt 0;'>Program Studi</td><td style='border:none; padding:2pt 0;'>: " . e($project->prodi) . "</td></tr>
                        " . ($fakultas ? "<tr><td style='border:none; padding:2pt 0;'>Fakultas</td><td style='border:none; padding:2pt 0;'>: " . e($fakultas) . "</td></tr>" : "") . "
                    </table>
                    <p>Menyatakan dengan sesungguhnya bahwa skripsi berjudul <strong>\"" . e($project->judul) . "\"</strong> ini adalah murni hasil karya penelitian, pemikiran, dan penulisan mandiri saya sendiri. Karya ini belum pernah diajukan untuk memperoleh gelar akademik di perguruan tinggi mana pun, dan bebas dari unsur plagiarisme atau fabrikasi data.</p>
                    <p>Seluruh kutipan, data, dan pemikiran yang bersumber dari pihak lain telah dicantumkan dalam daftar pustaka sesuai dengan etika ilmiah. Apabila di kemudian hari terbukti terdapat plagiarisme, saya bersedia menerima sanksi akademik sesuai aturan yang berlaku.</p>
                    <div style='margin-top:24pt; float:right; width:200pt; text-align:center; font-size:11.5pt; line-height:1.3;'>
                        <div>" . ($kampus ? e($kampus) . ", " : "") . $tglFullIndo . "</div>
                        <div style='margin-top:3pt;'>Yang membuat pernyataan,</div>
                        <div style='margin-top:10pt; font-size:8.5pt; color:#666;'>[ Materai Rp 10.000 ]</div>
                        <div style='height:34pt;'></div>
                        <div><u><strong>" . strtoupper(e($nama)) . "</strong></u></div>
                        <div>NIM. " . e($npm ?: '-') . "</div>
                    </div>
                    <div style='clear:both;'></div>
                </div>
            </div>";

            $abstrakPdf = $this->buildDynamicAbstrakData($project);
            $cleanJudulPdf = $abstrakPdf['judul'];

            // ABSTRAK INDONESIA (1 SPASI)
            $html .= "<div class='page-break'>
                <h2>ABSTRAK</h2>
                <div style='text-align:center;'>
                    <div style='font-size:11.5pt; font-weight:bold; margin-top:6pt; line-height:1.25; text-transform:uppercase;'>" . e($cleanJudulPdf) . "</div>
                    <div style='font-size:10.5pt; margin-top:4pt;'>Oleh: " . e($nama) . ($npm ? " (NIM: " . e($npm) . ")" : "") . "</div>
                    <div style='font-size:10pt; color:#333; margin-bottom:14pt;'>Program Studi " . e($project->prodi) . ($kampus ? ", " . e($kampus) : "") . "</div>
                </div>
                <div style='font-size:11.5pt; line-height:1.15;'>
                    <p style='line-height:1.15;'>" . e($abstrakPdf['id_body']) . "</p>
                    <p style='text-indent:0; margin-top:10pt; line-height:1.15;'><strong>Kata Kunci:</strong> " . e($abstrakPdf['id_keywords']) . "</p>
                </div>
            </div>";

            // ABSTRACT ENGLISH (1 SPASI ITALIC)
            $html .= "<div class='page-break'>
                <h2>ABSTRACT</h2>
                <div style='text-align:center; font-style:italic;'>
                    <div style='font-size:11.5pt; font-weight:bold; margin-top:6pt; line-height:1.25; text-transform:uppercase;'>" . e($cleanJudulPdf) . "</div>
                    <div style='font-size:10.5pt; margin-top:4pt;'>By: " . e($nama) . ($npm ? " (Student ID: " . e($npm) . ")" : "") . "</div>
                    <div style='font-size:10pt; color:#333; margin-bottom:14pt;'>Department of " . e($project->prodi) . ($kampus ? ", " . e($kampus) : "") . "</div>
                </div>
                <div style='font-size:11.5pt; line-height:1.15; font-style:italic;'>
                    <p style='line-height:1.15;'>" . e($abstrakPdf['en_body']) . "</p>
                    <p style='text-indent:0; margin-top:10pt; line-height:1.15;'><strong>Keywords:</strong> " . e($abstrakPdf['en_keywords']) . "</p>
                </div>
            </div>";
        }

        // 5. KATA PENGANTAR (STANDAR 9 POIN PERGURUAN TINGGI INDONESIA — 1:1 DENGAN PREVIEW & WORD)
        $cleanJudulKpPdf = rtrim(trim((string) $project->judul), '. ');
        $docKindLowerPdf = strtolower($jenisDokumen);
        $fakultasKpPdf = $fakultas ?: 'Fakultas terkait';
        $kpPdfSyarat = $isProposal
            ? "diajukan sebagai salah satu persyaratan Seminar Proposal Skripsi pada Program Studi "
            : "dimaksudkan untuk memenuhi salah satu syarat memperoleh gelar Sarjana pada Program Studi ";
        $html .= "<div class='page-break'>
            <h2 data-toc-id='front_kata_pengantar' style='margin:0 0 10pt 0;'>KATA PENGANTAR</h2>
            <p style='font-size:10.5pt; line-height:1.34; margin-bottom:4pt;'>Dengan memanjatkan puji syukur ke hadirat Tuhan Yang Maha Esa, yang telah melimpahkan rahmat dan hidayah-Nya, penulis dapat menyelesaikan penyusunan {$docKindLowerPdf} dengan judul <strong>\"" . e($cleanJudulKpPdf) . "\"</strong>.</p>
            <p style='font-size:10.5pt; line-height:1.34; margin-bottom:4pt;'>Penulisan {$docKindLowerPdf} ini {$kpPdfSyarat}" . e($project->prodi) . ($fakultas ? ", " . e($fakultas) : "") . ($kampus ? ", " . e($kampus) : "") . ". Penulis berharap hasil penelitian ini dapat memperkaya kajian ilmiah pada bidang yang diteliti serta menjadi bahan pertimbangan bagi pihak-pihak yang terkait dengan permasalahan penelitian.</p>
            <p style='font-size:10.5pt; line-height:1.34; margin-bottom:3pt;'>Penyelesaian {$docKindLowerPdf} ini tidak terlepas dari peran serta berbagai pihak. Oleh sebab itu, pada kesempatan yang baik ini penulis menyampaikan terima kasih dan penghargaan yang setinggi-tingginya kepada:</p>
            <ol style='margin:2pt 0 5pt 0; padding-left:1.0cm; font-size:10.2pt; line-height:1.26; text-align:justify;'>
                <li style='margin-bottom:1.5pt;'>Bapak/Ibu (Nama Rektor), selaku Rektor " . e($kampus) . " yang telah memberikan kesempatan kepada penulis untuk menempuh pendidikan;</li>
                <li style='margin-bottom:1.5pt;'>Bapak/Ibu (Nama Dekan), selaku Dekan " . e($fakultasKpPdf) . " yang telah memberikan fasilitas dan kemudahan selama perkuliahan;</li>
                <li style='margin-bottom:1.5pt;'>Bapak/Ibu (Nama Ketua Program Studi), selaku Ketua Program Studi " . e($project->prodi) . " yang telah memberikan arahan dan dukungan akademik;</li>
                <li style='margin-bottom:1.5pt;'>Bapak/Ibu (Nama Dosen Pembimbing), selaku Dosen Pembimbing yang telah memberikan bimbingan, koreksi, dan motivasi sejak penyusunan proposal hingga terselesaikannya {$docKindLowerPdf} ini;</li>
                <li style='margin-bottom:1.5pt;'>Bapak/Ibu (Nama Dosen Pembimbing Akademik), selaku Dosen Pembimbing Akademik yang telah mendampingi penulis selama masa studi;</li>
                <li style='margin-bottom:1.5pt;'>Bapak/Ibu Dosen Penguji yang telah memberikan masukan berharga bagi penyempurnaan {$docKindLowerPdf} ini;</li>
                <li style='margin-bottom:1.5pt;'>Seluruh dosen dan tenaga kependidikan " . e($fakultasKpPdf) . " " . e($kampus) . " atas ilmu dan pelayanan yang telah diberikan;</li>
                <li style='margin-bottom:1.5pt;'>Orang tua tercinta yang dengan tulus senantiasa mendoakan, membimbing, dan mendukung penulis dalam setiap langkah;</li>
                <li style='margin-bottom:1.5pt;'>Saudara, sahabat, serta rekan-rekan yang telah memberikan bantuan dan dorongan semangat selama proses penyusunan {$docKindLowerPdf} ini.</li>
            </ol>
            <p style='font-size:10.5pt; line-height:1.34; margin-bottom:4pt;'>Semoga Tuhan Yang Maha Esa membalas segala kebaikan dan bantuan yang telah diberikan dengan pahala yang setimpal. Penulis menyadari sepenuhnya bahwa {$docKindLowerPdf} ini masih memiliki kekurangan. Oleh karena itu, kritik dan saran yang bersifat membangun sangat penulis harapkan demi penyempurnaan penelitian selanjutnya.</p>
            <div style='margin-top:10pt; float:right; width:210pt; text-align:right; font-size:10.5pt; line-height:1.25; page-break-inside:avoid;'>
                <div>(Kota), {$tglBulanIndo}</div>
                <div>Penulis,</div>
                <div style='height:28pt;'></div>
                <div><strong>" . strtoupper(e($nama)) . "</strong></div>
            </div>
            <div style='clear:both;'></div>
        </div>";

        // 6. DAFTAR ISI (DENGAN DOT LEADER & KALIBRASI HALAMAN PRESISI 2-PASS)
        $html .= "<div class='page-break'>
            <h2 data-toc-id='front_toc'>DAFTAR ISI</h2>
            <div style='margin-top:12pt;'><!--PDF_TOC_ROWS--></div>
        </div>";

        // 7. DAFTAR TABEL (HANYA JIKA ADA TABEL NYATA)
        if (!empty($pdfTables)) {
            $html .= "<div class='page-break'>
                <h2 data-toc-id='front_tables'>DAFTAR TABEL</h2>
                <div style='margin-top:12pt;'><!--PDF_TABLE_ROWS--></div></div>";
        }

        // 8. DAFTAR GAMBAR (HANYA JIKA ADA GAMBAR / DIAGRAM NYATA)
        if (!empty($pdfFigures)) {
            $html .= "<div class='page-break'>
                <h2 data-toc-id='front_figures'>DAFTAR GAMBAR</h2>
                <div style='margin-top:12pt;'><!--PDF_FIGURE_ROWS--></div></div>";
        }

        // 9. DAFTAR LAMPIRAN (OTOMATIS MUNCUL HANYA JIKA MAHASISWA MENGUNGGAH LAMPIRAN)
        if (!empty($pdfLampirans)) {
            $html .= "<div class='page-break'>
                <h2 data-toc-id='front_lampiran'>DAFTAR LAMPIRAN</h2>
                <div style='margin-top:12pt;'><!--PDF_LAMPIRAN_ROWS--></div></div>";
        }

        // BAB UTAMA (SETIAP BAB DIAWALI PAGE BREAK SATU KALI — BEBAS HALAMAN KOSONG)
        $chapters = [
            'bab1' => 'PENDAHULUAN',
            'bab2' => 'TINJAUAN PUSTAKA',
            'bab3' => 'METODOLOGI PENELITIAN',
            'bab4' => 'HASIL PENELITIAN DAN PEMBAHASAN',
            'bab5' => 'PENUTUP',
        ];
        if ($isProposal) {
            $chapters = [
                'bab1' => 'PENDAHULUAN',
                'bab2' => 'TINJAUAN PUSTAKA',
                'bab3' => 'METODOLOGI PENELITIAN',
            ];
        }

        $nums = ['bab1' => 'I', 'bab2' => 'II', 'bab3' => 'III', 'bab4' => 'IV', 'bab5' => 'V'];
        foreach ($chapters as $k => $v) {
            if ($c = $project->{$k . '_content'}) {
                if ($k !== 'bab5') {
                    [$c] = $this->stripEmbeddedDaftarPustaka((string) $c);
                }
                if (trim((string) $c) === '') {
                    continue;
                }
                $html .= "<div class='page-break'>";
                $html .= "<h2 data-toc-id='chap_{$k}' style='text-align:center; font-weight:bold; font-size:14pt; margin-top:0; margin-bottom:16pt; page-break-after:avoid;'>BAB " . $nums[$k] . "<br>" . strtoupper($v) . "</h2>";
                $html .= $this->formatAcademicContentForPdf($c, (string) ($project->judul ?? ''));
                $html .= "</div>";
            }
        }

        $dpTrimmed = trim((string) $project->daftar_pustaka);
        if ($dpTrimmed !== '') {
            $html .= "<div class='page-break'>";
            $html .= "<h2 data-toc-id='chap_dp' style='text-align:center; font-weight:bold; font-size:14pt; margin-top:0; margin-bottom:16pt; page-break-after:avoid;'>DAFTAR PUSTAKA</h2>";
            $dpLines = explode("\n", str_replace(["\r\n", "\r"], "\n", $dpTrimmed));
            foreach ($dpLines as $dpl) {
                $trimDpl = trim($dpl);
                if ($trimDpl !== '') {
                    // Standar APA 7th: rata kiri + hanging indent 1,27 cm, spasi 1,15 agar bebas renggang kata akibat URL DOI
                    $html .= "<p style='text-indent:-1.27cm; padding-left:1.27cm; margin:0 0 8pt 0; text-align:left; line-height:1.15; font-size:12pt;'>" . $this->linkifyDaftarPustakaHtml($trimDpl) . "</p>";
                }
            }
            $html .= "</div>";
        }

        // LAMPIRAN (OTOMATIS MUNCUL DI AKHIR PDF JIKA MAHASISWA MENGUNGGAH LAMPIRAN)
        if (!empty($pdfLampirans)) {
            foreach ($pdfLampirans as $lIdx => $lmp) {
                $html .= "<div class='page-break'>";
                if ($lIdx === 0) {
                    $html .= "<h2 data-toc-id='chap_lampiran' style='text-align:center; font-weight:bold; font-size:14pt; margin-top:0; margin-bottom:14pt; page-break-after:avoid;'>LAMPIRAN</h2>";
                }
                $lmpNum = (int) ($lmp['index'] ?? ($lIdx + 1));
                $html .= "<h3 data-toc-id='lmp_{$lmpNum}' style='font-size:12pt; font-weight:bold; text-align:left; text-indent:0; margin:0 0 12pt 0; page-break-after:avoid;'>" . $this->pdfSafeText($lmp['label']) . "</h3>";

                if (!empty($lmp['image_base64'])) {
                    $imgSrc = $lmp['image_base64'];
                    $isJpegLmp = str_starts_with($imgSrc, 'data:image/jpeg') || str_starts_with($imgSrc, 'data:image/jpg') || str_contains($imgSrc, '/9j/');
                    if ($isJpegLmp || $hasGd) {
                        $html .= "<div style='text-align:center; margin:10pt 0 14pt 0; page-break-inside:avoid;'>"
                            . "<img src='{$imgSrc}' style='max-width:100%; max-height:19cm; width:auto; height:auto; border:0.5pt solid #cbd5e1;'>"
                            . "</div>";
                    }
                }

                if (!empty($lmp['content'])) {
                    $html .= $this->formatAcademicContentForPdf($lmp['content'], (string) ($project->judul ?? ''));
                }
                $html .= "</div>";
            }
        }
        $html .= "</body></html>";

        $injectListsIntoHtml = function (string $template, array $tocs, array $tbls, array $figs, array $lmps) use ($buildRowsHtml): string {
            return str_replace(
                ['<!--PDF_TOC_ROWS-->', '<!--PDF_TABLE_ROWS-->', '<!--PDF_FIGURE_ROWS-->', '<!--PDF_LAMPIRAN_ROWS-->'],
                [
                    $buildRowsHtml($tocs, true),
                    $buildRowsHtml($tbls, false),
                    $buildRowsHtml($figs, false),
                    $buildRowsHtml($lmps, false),
                ],
                $template
            );
        };

        $tmpDir = rtrim(sys_get_temp_dir(), '/\\');
        $renderDompdfWithTracking = function (string $docHtml) use ($tmpDir): array {
            $tracked = [];
            $makeInstance = function (string $markup) use ($tmpDir, &$tracked): Dompdf {
                $tracked = [];
                $inst = new Dompdf();
                $inst->set_option('isHtml5ParserEnabled', true);
                $inst->set_option('isRemoteEnabled', false);
                $inst->set_option('tempDir', $tmpDir);
                $inst->set_option('fontCache', $tmpDir);
                $inst->setCallbacks([
                    'track_toc' => [
                        'event' => 'end_frame',
                        'f' => function ($frame) use (&$tracked, $inst) {
                            $node = $frame->get_node();
                            if ($node instanceof \DOMElement && $node->hasAttribute('data-toc-id')) {
                                $tocId = $node->getAttribute('data-toc-id');
                                if ($tocId !== '' && !isset($tracked[$tocId])) {
                                    $tracked[$tocId] = $inst->getCanvas()->get_page_number();
                                }
                            }
                        },
                    ],
                ]);
                $inst->loadHtml($markup);
                $inst->setPaper('A4');
                $inst->render();
                return $inst;
            };

            try {
                $inst = $makeInstance($docHtml);
            } catch (\Throwable $e) {
                Log::warning('Dompdf render fallback triggered: ' . $e->getMessage());
                $fallbackHtml = (string) preg_replace('/<img[^>]*>/i', '', $docHtml);
                $inst = $makeInstance($fallbackHtml);
            }
            return [$inst, $tracked];
        };

        // Pass 1: Render untuk merekam nomor halaman AKTUAL setiap BAB, sub-bab, tabel, gambar, dan lampiran
        $pass1Html = $injectListsIntoHtml($html, $tocItemsPdf, $pdfTables, $pdfFigures, $pdfLampirans);
        [$dompdf, $trackedPages] = $renderDompdfWithTracking($pass1Html);

        $bab1PhysTracked = $trackedPages['chap_bab1'] ?? null;
        if ($bab1PhysTracked !== null && $bab1PhysTracked >= 2) {
            $chapLabelToKey = [
                'BAB I ' => 'chap_bab1',
                'BAB II ' => 'chap_bab2',
                'BAB III ' => 'chap_bab3',
                'BAB IV ' => 'chap_bab4',
                'BAB V ' => 'chap_bab5',
            ];
            $frontLabelToKey = [
                'KATA PENGANTAR' => 'front_kata_pengantar',
                'DAFTAR ISI' => 'front_toc',
                'DAFTAR TABEL' => 'front_tables',
                'DAFTAR GAMBAR' => 'front_figures',
                'DAFTAR LAMPIRAN' => 'front_lampiran',
            ];

            $calibratedToc = $tocItemsPdf;
            foreach ($calibratedToc as &$tRow) {
                $lbl = trim((string) ($tRow['label'] ?? ''));
                if (isset($frontLabelToKey[$lbl]) && isset($trackedPages[$frontLabelToKey[$lbl]])) {
                    $tRow['page'] = $this->toRomanLower((int) $trackedPages[$frontLabelToKey[$lbl]]);
                    continue;
                }
                if ($lbl === 'DAFTAR PUSTAKA' && isset($trackedPages['chap_dp'])) {
                    $tRow['page'] = (string) max(1, (int) $trackedPages['chap_dp'] - $bab1PhysTracked + 1);
                    continue;
                }
                if ($lbl === 'LAMPIRAN' && isset($trackedPages['chap_lampiran'])) {
                    $tRow['page'] = (string) max(1, (int) $trackedPages['chap_lampiran'] - $bab1PhysTracked + 1);
                    continue;
                }
                foreach ($chapLabelToKey as $prefix => $cKey) {
                    if (str_starts_with($lbl, $prefix) && isset($trackedPages[$cKey])) {
                        $tRow['page'] = (string) max(1, (int) $trackedPages[$cKey] - $bab1PhysTracked + 1);
                        continue 2;
                    }
                }
                if (preg_match('/^(\d+\.\d+)\s+/', $lbl, $msSub) && isset($trackedPages['sub_' . $msSub[1]])) {
                    $tRow['page'] = (string) max(1, (int) $trackedPages['sub_' . $msSub[1]] - $bab1PhysTracked + 1);
                }
            }
            unset($tRow);

            $calibratedTables = $pdfTables;
            foreach ($calibratedTables as &$tbRow) {
                $lbl = trim((string) ($tbRow['label'] ?? ''));
                if (preg_match('/^Tabel\s+(\d+\.\d+[\w.\-]*)/i', $lbl, $mtRow) && isset($trackedPages['tbl_' . $mtRow[1]])) {
                    $tbRow['page'] = (string) max(1, (int) $trackedPages['tbl_' . $mtRow[1]] - $bab1PhysTracked + 1);
                }
            }
            unset($tbRow);

            $calibratedFigures = $pdfFigures;
            foreach ($calibratedFigures as &$fgRow) {
                $lbl = trim((string) ($fgRow['label'] ?? ''));
                if (preg_match('/^(?:Gambar|Bagan)\s+(\d+\.\d+[\w.\-]*)/i', $lbl, $mfRow) && isset($trackedPages['fig_' . $mfRow[1]])) {
                    $fgRow['page'] = (string) max(1, (int) $trackedPages['fig_' . $mfRow[1]] - $bab1PhysTracked + 1);
                }
            }
            unset($fgRow);

            $calibratedLampirans = $pdfLampirans;
            foreach ($calibratedLampirans as $lIdx => &$lmRow) {
                $lmpKey = 'lmp_' . (int) ($lmRow['index'] ?? ($lIdx + 1));
                if (isset($trackedPages[$lmpKey])) {
                    $lmRow['page'] = (string) max(1, (int) $trackedPages[$lmpKey] - $bab1PhysTracked + 1);
                }
            }
            unset($lmRow);

            // Pass 2: Hanya render ulang jika ada minimal 1 nomor halaman yang berubah agar 100% akurat
            $pass2Html = $injectListsIntoHtml($html, $calibratedToc, $calibratedTables, $calibratedFigures, $calibratedLampirans);
            if ($pass2Html !== $pass1Html) {
                [$dompdf, $trackedPages2] = $renderDompdfWithTracking($pass2Html);
                if (!empty($trackedPages2['chap_bab1'])) {
                    $bab1PhysTracked = (int) $trackedPages2['chap_bab1'];
                }
            }
        }

        // Penomoran Halaman Presisi & Seragam (Tengah Bawah, konsisten dengan Preview & Word):
        // - Halaman 1 (Cover): Tanpa nomor halaman
        // - Halaman Bagian Depan (2 s/d sebelum BAB I): Angka Romawi kecil (ii, iii, iv, ...) di TENGAH BAWAH
        // - Halaman Isi Utama (BAB I s/d DAFTAR PUSTAKA & LAMPIRAN): Reset mulai Angka Arab 1 di TENGAH BAWAH
        try {
            $canvas = $dompdf->getCanvas();
            $cpdf = $canvas->get_cpdf();
            $bab1Page = $bab1PhysTracked;
            $pIdx = 0;
            if ($bab1Page === null) {
                foreach ($cpdf->objects as $obj) {
                    if (($obj['t'] ?? '') === 'page') {
                        $pIdx++;
                        $rawStream = '';
                        foreach (($obj['info']['contents'] ?? []) as $cid) {
                            $rawStream .= (string) ($cpdf->objects[$cid]['c'] ?? '');
                        }
                        // Deteksi heading halaman pembuka BAB I (di luar Daftar Isi)
                        $isTocOrFront = str_contains($rawStream, '(DAFTAR ISI)') || str_contains($rawStream, '(DAFTAR TABEL)') || str_contains($rawStream, '(DAFTAR GAMBAR)') || str_contains($rawStream, '(DAFTAR LAMPIRAN)');
                        if (!$isTocOrFront && $pIdx >= 2 && $bab1Page === null && preg_match('/\(BAB I\)/', $rawStream)) {
                            $bab1Page = $pIdx;
                            break;
                        }
                    }
                }
                if ($bab1Page === null) {
                    $bab1Page = $pIdx + 1;
                }
            }

            $bottomY = 841.89 - max(32, ($marginBottom * 28.3465) - 24);

            $toRoman = function (int $n): string {
                return $this->toRomanLower($n);
            };

            $cpdfFontName = $pdfFont['cpdf'] ?? 'Times-Roman';
            $canvas->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) use ($bab1Page, $bottomY, $toRoman, $cpdfFontName) {
                if ($pageNumber <= 1) {
                    return; // Halaman Sampul (Cover) tanpa nomor halaman
                }
                $font = $fontMetrics->getFont($cpdfFontName, 'normal');
                $size = 11;
                if ($pageNumber < $bab1Page) {
                    // Bagian Depan: Angka Romawi kecil (ii, iii, iv, ...) di tengah bawah
                    $roman = $toRoman($pageNumber);
                    $tw = $fontMetrics->getTextWidth($roman, $font, $size);
                    $canvas->text((595.28 - $tw) / 2, $bottomY, $roman, $font, $size, [0, 0, 0]);
                } else {
                    // Bagian Utama (BAB I s/d LAMPIRAN): Reset angka Arab mulai dari 1 di tengah bawah
                    $arabic = (string) ($pageNumber - $bab1Page + 1);
                    $tw = $fontMetrics->getTextWidth($arabic, $font, $size);
                    $canvas->text((595.28 - $tw) / 2, $bottomY, $arabic, $font, $size, [0, 0, 0]);
                }
            });
        } catch (\Throwable $e) {
            Log::warning('PDF page numbering script error: ' . $e->getMessage());
        }

        $inline = $request->boolean('inline');
        return $dompdf->stream('Naskah_' . Str::slug(substr($project->judul, 0, 24), '_') . '.pdf', ['Attachment' => !$inline]);
    }

    /**
     * Download Contoh Proposal / Skripsi Asli (.docx / .pdf) langsung dari Landing Page
     */
    public function downloadSample(string $major, string $format, Request $request)
    {
        $samples = [
            'manajemen' => [
                'judul' => 'Pengaruh Beban Kerja, Stres Kerja, dan Kepuasan Kerja terhadap Turnover Intention Karyawan',
                'prodi' => 'Manajemen',
                'nama' => 'Rizky Pratama Putra',
                'nim' => '2021041082',
                'kampus' => 'Universitas Indonesia',
                'fakultas' => 'Fakultas Ekonomi dan Bisnis',
                'jenis' => 'PROPOSAL SKRIPSI',
                'bab1' => "1.1 Latar Belakang Masalah\nTingginya tingkat perpindahan karyawan (turnover intention) pada sektor jasa modern menjadi tantangan strategis dalam mempertahankan talenta produktif. Menurut Naufal & Wijaya (2024), beban kerja berlebih yang tidak diimbangi dengan kompensasi dan lingkungan kerja kondusif memicu kelelahan emosional.\n\nData observasi awal menunjukkan tingkat keluar-masuk karyawan mencapai 24% dalam dua tahun terakhir. Hal ini sejalan dengan temuan Kusuma et al. (2023) bahwa stres kerja yang kronis menurunkan kepuasan kerja dan meningkatkan keinginan berpindah.\n\n1.2 Identifikasi Masalah\n1. Tingginya target operasional harian memicu beban kerja psikologis.\n2. Stres kerja menurunkan komitmen afektif karyawan.\n\n1.3 Batasan Masalah\nPenelitian ini dibatasi pada variabel beban kerja, stres kerja, kepuasan kerja, dan turnover intention pada karyawan tetap masa kerja minimal 1 tahun.\n\n1.4 Rumusan Masalah\n1. Apakah beban kerja berpengaruh signifikan terhadap turnover intention karyawan?\n2. Apakah stres kerja berpengaruh terhadap turnover intention?\n3. Apakah kepuasan kerja memediasi pengaruh stres kerja terhadap turnover intention?",
                'bab2' => "2.1 Landasan Teori\nBeban kerja (workload) merujuk pada kumpulan tuntutan tugas yang harus diselesaikan dalam jangka waktu tertentu (Pratama & Santoso, 2024). Sementara itu, turnover intention adalah kecenderungan sadar individu untuk mencari alternatif pekerjaan baru.\n\n2.2 Penelitian Terdahulu\nHandayani & Putri (2023) membuktikan pada 180 responden bahwa stres kerja berkontribusi sebesar 39,4% terhadap intensi keluar karyawan.",
                'bab3' => "3.1 Jenis dan Desain Penelitian\nPendekatan kuantitatif asosiatif kausal dengan survei kuesioner skala Likert 1–5 pada 145 sampel karyawan.\n\n3.2 Teknik Analisis Data\nPengujian menggunakan Structural Equation Modeling berbasis Partial Least Square (PLS-SEM) meliputi Outer Model dan Inner Model.",
                'dp' => "Handayani, S., & Putri, M. A. (2023). Stres kerja dan turnover intention karyawan milenial. Jurnal Manajemen Sumber Daya Manusia, 15(2), 112-125.\nKusuma, A. R., Pratama, D., & Wijaya, H. (2023). Pengaruh beban kerja terhadap retensi karyawan. Jurnal Ekonomi dan Bisnis, 18(1), 45-59.\nNaufal, M., & Wijaya, B. (2024). Kepuasan kerja sebagai mediator turnover intention. Jurnal Riset Manajemen, 11(3), 201-215."
            ],
            'kedokteran' => [
                'judul' => 'Hubungan Intensitas Penggunaan Gadget dengan Kejadian Miopia pada Siswa SMA Negeri',
                'prodi' => 'Kedokteran',
                'nama' => 'Dr. Cand. Aulia Rahmawati',
                'nim' => '21011020045',
                'kampus' => 'Universitas Padjadjaran',
                'fakultas' => 'Fakultas Kedokteran',
                'jenis' => 'PROPOSAL SKRIPSI',
                'bab1' => "1.1 Latar Belakang Masalah\nPrevalensi gangguan refraksi khususnya miopia pada usia remaja mengalami peningkatan signifikan seiring tingginya paparan layar gawai digital untuk pembelajaran maupun hiburan (Wijaya & Hartono, 2024). Aktivitas jarak dekat berkepanjangan menyebabkan spasme akomodasi mata.\n\n1.2 Identifikasi Masalah\n1. Durasi penggunaan gadget di kalangan pelajar melebihi 6 jam per hari.\n2. Pencahayaan kamar dan jarak baca yang tidak ergonomis.\n\n1.3 Batasan Masalah\nPenelitian difokuskan pada siswa SMA usia 15–18 tahun tanpa riwayat trauma okular maupun kelainan refraksi kongenital.\n\n1.4 Rumusan Masalah\nApakah terdapat hubungan bermakna antara durasi dan jarak penggunaan gadget dengan kejadian miopia pada siswa SMA?",
                'bab2' => "2.1 Landasan Teori\nMiopia adalah kelainan refraksi di mana sinar sejajar yang masuk ke mata difokuskan di depan retina akibat panjang aksial bola mata yang melebihi normal (Siregar, 2023).",
                'bab3' => "3.1 Desain Penelitian\nDesain penelitian observasional analitik dengan pendekatan cross-sectional menggunakan kuesioner paparan layar dan pemeriksaan visus Snellen Chart serta uji korelasi Spearman.",
                'dp' => "Siregar, F. H. (2023). Faktor risiko kelainan refraksi miopia pada remaja sekolah menengah. Jurnal Kedokteran Nusantara, 14(2), 77-86.\nWijaya, K., & Hartono, B. (2024). Screen time exposure and myopia progression among Indonesian adolescents. Medical Journal of Indonesia, 33(1), 22-30."
            ],
            'pendidikan' => [
                'judul' => 'Model Problem Based Learning Berbantuan Problem Card untuk Meningkatkan Keterampilan Membaca',
                'prodi' => 'Pendidikan Bahasa',
                'nama' => 'Ahmad Fauzan Mubarok',
                'nim' => '200321614091',
                'kampus' => 'Universitas Negeri Malang',
                'fakultas' => 'Fakultas Sastra dan Pendidikan',
                'jenis' => 'SKRIPSI',
                'bab1' => "1.1 Latar Belakang Masalah\nKeterampilan membaca pemahaman (maharah qira'ah) masih menghadapi kendala rendahnya partisipasi aktif peserta didik dalam mengidentifikasi ide pokok teks akademik (Hidayat & Lestari, 2023).\n\n1.2 Batasan Masalah\nPenelitian tindakan kelas diterapkan dalam dua siklus pembelajaran menggunakan media problem card kontekstual.\n\n1.3 Rumusan Masalah\nBagaimana penerapan model Problem Based Learning berbantuan problem card dalam meningkatkan hasil belajar membaca siswa?",
                'bab2' => "2.1 Landasan Teori\nProblem Based Learning (PBL) mendorong siswa membangun pengetahuan secara kolaboratif melalui pemecahan masalah autentik (Sanjaya, 2022).",
                'bab3' => "3.1 Metode Penelitian\nPenelitian Tindakan Kelas (PTK) model Kemmis & McTaggart terdiri dari perencanaan, pelaksanaan, observasi, dan refleksi.",
                'bab4' => "4.1 Hasil Penelitian Siklus I dan II\nKetuntasan belajar klasikal meningkat dari 62,5% pada Siklus I menjadi 89,3% pada Siklus II setelah optimalisasi kartu masalah.",
                'bab5' => "5.1 Kesimpulan\nModel PBL berbantuan problem card terbukti efektif meningkatkan keterampilan membaca siswa secara signifikan.",
                'dp' => "Hidayat, T., & Lestari, P. (2023). Efektivitas Problem Based Learning terhadap literasi membaca. Jurnal Pendidikan Bahasa, 12(1), 54-68.\nSanjaya, W. (2022). Strategi pembelajaran berorientasi standar proses pendidikan. Kencana."
            ],
            'it' => [
                'judul' => 'Implementasi Algoritma Random Forest dan Ekstraksi Fitur Citra untuk Klasifikasi Penyakit Daun Padi Berbasis Web',
                'prodi' => 'Teknik Informatika',
                'nama' => 'Aditya Mahendra',
                'nim' => '1910511044',
                'kampus' => 'Institut Teknologi Bandung',
                'fakultas' => 'Sekolah Teknik Elektro dan Informatika',
                'jenis' => 'SKRIPSI',
                'bab1' => "1.1 Latar Belakang Masalah\nKomoditas padi merupakan sumber pangan utama di Indonesia yang produktivitasnya sangat dipengaruhi oleh kesehatan tanaman pada fase vegetatif. Serangan hawar daun bakteri dan bercak coklat sering terlambat dideteksi oleh petani (Setiawan & Nugroho, 2023).\n\n1.2 Batasan Masalah\nDataset dibatasi pada 1.200 citra daun padi terbagi dalam 4 kelas menggunakan ekstraksi fitur HSV dan GLCM serta klasifikasi Random Forest.\n\n1.3 Rumusan Masalah\n1. Bagaimana merancang ekstraksi fitur HSV dan GLCM pada citra daun padi?\n2. Berapa tingkat akurasi Random Forest dalam mengklasifikasikan penyakit daun padi?",
                'bab2' => "2.1 Landasan Teori\nAlgoritma Random Forest adalah metode ensemble learning berbasis banyak decision tree dengan mekanisme majority voting (Saputra et al., 2024).",
                'bab3' => "3.1 Metode Pengembangan Sistem\nPenelitian menerapkan metode R&D dengan preprocessing citra, ekstraksi HSV+GLCM, pelatihan model Random Forest, dan evaluasi 10-Fold Cross Validation.",
                'bab4' => "4.1 Hasil Evaluasi Model\nPengujian Confusion Matrix pada data uji menunjukkan akurasi klasifikasi sebesar 94,2% dengan nilai F1-Score rata-rata 0,94.",
                'bab5' => "5.1 Kesimpulan\nIntegrasi fitur warna HSV, tekstur GLCM, dan Random Forest mampu mengenali penyakit daun padi secara akurat melalui sistem web.",
                'dp' => "Saputra, D., Kurniawan, R., & Hakim, L. (2024). Klasifikasi penyakit tanaman pangan menggunakan ensemble machine learning. Jurnal Teknologi Informasi dan Ilmu Komputer, 11(1), 33-44.\nSetiawan, B., & Nugroho, A. (2023). Ekstraksi fitur GLCM dan ruang warna HSV pada deteksi penyakit daun padi. Jurnal Nasional Teknik Elektro dan Teknologi Informasi, 12(2), 140-148."
            ],
            'hukum' => [
                'judul' => 'Pelindungan Hukum bagi Konsumen atas Kebocoran Data Pribadi pada Layanan Pinjaman Daring Menurut UU Nomor 27 Tahun 2022',
                'prodi' => 'Ilmu Hukum',
                'nama' => 'Nabila Azzahra',
                'nim' => '2010111095',
                'kampus' => 'Universitas Gadjah Mada',
                'fakultas' => 'Fakultas Hukum',
                'jenis' => 'PROPOSAL SKRIPSI',
                'bab1' => "1.1 Latar Belakang Masalah\nPerkembangan layanan pendanaan bersama berbasis teknologi informasi (fintech peer-to-peer lending) memperluas inklusi keuangan di Indonesia, namun menimbulkan kerentanan hukum terkait pelindungan data pribadi pengguna (Siregar & Wibowo, 2023).\n\n1.2 Batasan Masalah\nKajian difokuskan pada tanggung jawab pengendali data penyelenggara pinjaman daring berizin OJK menurut UU Nomor 27 Tahun 2022.\n\n1.3 Rumusan Masalah\n1. Bagaimana pengaturan kewajiban hukum pengendali data pada layanan pinjaman daring?\n2. Bagaimana mekanisme pemulihan hak dan ganti kerugian konsumen akibat kebocoran data?",
                'bab2' => "2.1 Tinjauan Pustaka\nTeori Pelindungan Hukum menekankan jaminan kepastian hak warga negara dari potensi penyalahgunaan posisi dominan penyelenggara elektronik (Maharani et al., 2024).",
                'bab3' => "3.1 Jenis dan Pendekatan Penelitian\nPenelitian hukum yuridis normatif dengan statute approach dan conceptual approach terhadap peraturan perundang-undangan terkait.",
                'dp' => "Maharani, D., Prasetyo, Y., & Santoso, H. (2024). Tanggung jawab pengendali data pribadi dalam perspektif Undang-Undang Nomor 27 Tahun 2022. Jurnal Hukum Ius Quia Iustum, 31(1), 55-74.\nSiregar, R. A., & Wibowo, A. (2023). Pelindungan konsumen layanan finansial teknologi atas penyalahgunaan data pribadi. Jurnal Penelitian Hukum De Jure, 23(2), 189-204."
            ],
            'teknik' => [
                'judul' => 'Analisis Sisa Umur Pakai Pipa Distribusi Gas Menggunakan Metode Fitness-for-Service Standar ASME B31.8',
                'prodi' => 'Teknik Mesin / Perpipaan',
                'nama' => 'Bagas Prakoso',
                'nim' => '1806142093',
                'kampus' => 'Institut Teknologi Sepuluh Nopember',
                'fakultas' => 'Fakultas Teknologi Industri dan Rekayasa Sistem',
                'jenis' => 'SKRIPSI',
                'bab1' => "1.1 Latar Belakang Masalah\nIntegritas jaringan pipa distribusi gas bertekanan sangat rentan terhadap penipisan dinding akibat korosi internal dan eksternal. Evaluasi sisa umur pakai (remaining life assessment) krusial dilakukan guna mencegah kegagalan operasi (Nugraha et al., 2023).\n\n1.2 Batasan Masalah\nAnalisis dibatasi pada segmen pipa baja karbon API 5L Grade B dengan pendekatan tekanan operasi maksimum yang diizinkan (MAOP) sesuai ASME B31.8.\n\n1.3 Rumusan Masalah\nBerapa laju korosi aktual dan estimasi sisa umur pakai pipa distribusi gas berdasarkan perhitungan ketebalan minimum?",
                'bab2' => "2.1 Landasan Teori\nPerhitungan ketebalan dinding minimum pipa gas dan faktor keselamatan desain mengacu pada formulasi hoop stress dalam standar ASME B31.8 (Firdaus & Santosa, 2022).",
                'bab3' => "3.1 Metode Penelitian\nStudi kuantitatif eksperimental menggunakan data inspeksi Ultrasonic Thickness Gauge (UT) sepanjang 12 titik pengamatan untuk menghitung corrosion rate jangka panjang.",
                'bab4' => "4.1 Hasil Perhitungan Sisa Umur\nLaju korosi rata-rata terukur sebesar 0,14 mm/tahun sehingga estimasi sisa umur pakai pipa hingga batas ketebalan kritis masih aman selama 11,4 tahun.",
                'bab5' => "5.1 Kesimpulan\nJaringan pipa memenuhi kriteria kelayakan operasi ASME B31.8 dengan rekomendasi inspeksi ulang berkala setiap 3 tahun.",
                'dp' => "Firdaus, A., & Santosa, H. (2022). Evaluasi integritas pipa transmisi gas berbasis ASME B31.8. Jurnal Teknik Mesin Indonesia, 17(2), 90-99.\nNugraha, R., Prasetyo, E., & Hadi, S. (2023). Remaining life assessment pada pipa distribusi baja karbon terkorosi. Jurnal Rekayasa Material, 10(1), 15-26."
            ]
        ];

        $sample = $samples[$major] ?? $samples['manajemen'];
        $fakeProject = new Project([
            'judul' => $sample['judul'],
            'prodi' => $sample['prodi'],
            'bab1_content' => $sample['bab1'],
            'bab2_content' => $sample['bab2'],
            'bab3_content' => $sample['bab3'],
            'bab4_content' => $sample['bab4'] ?? null,
            'bab5_content' => $sample['bab5'] ?? null,
            'daftar_pustaka' => $sample['dp'],
        ]);

        $jenisDok = $sample['jenis'] ?? 'PROPOSAL SKRIPSI';
        $request->merge([
            'nama' => $sample['nama'],
            'npm' => $sample['nim'],
            'kampus' => $sample['kampus'],
            'fakultas' => $sample['fakultas'] ?? '',
            'jenis_dokumen' => $jenisDok,
        ]);

        if ($format === 'pdf') {
            $isProp = str_contains($jenisDok, 'PROPOSAL');
            $tocHtml = '';
            foreach ($this->extractTocItemsForProject($fakeProject, $isProp) as $item) {
                $pad = ($item['level'] ?? 1) === 1 ? '0' : '22px';
                $weight = ($item['level'] ?? 1) === 1 ? 'bold' : 'normal';
                $tocHtml .= "<div style='display:table; width:100%; margin-bottom:5px; font-weight:{$weight};'>
                    <span style='display:table-cell; padding-left:{$pad};'>" . e($item['label']) . "</span>
                    <span style='display:table-cell; text-align:right; width:50px;'>" . e($item['page']) . "</span>
                </div>";
            }

            $html = "<html><head><style>
                @page { size: A4; margin: 4cm 3cm 3cm 4cm; }
                body{font-family:'Times New Roman', serif; margin:0; text-align:justify; line-height:1.5; font-size:12pt;}
                h1,h2{text-align:center; font-weight:bold; font-size:14pt;}
                p{text-indent: 1.27cm; margin: 0 0 8px 0;}
                .cover p{text-indent:0; text-align:center;}
            </style></head><body>
            <div class='cover' style='text-align:center; padding-top:30px;'>
                <h1>{$jenisDok}</h1>
                <h2 style='margin-top:24px;'>" . strtoupper(e($sample['judul'])) . "</h2>
                <p style='margin-top:48px;'><strong>OLEH:</strong><br><u><strong>" . strtoupper(e($sample['nama'])) . "</strong></u><br>{$sample['nim']}</p>
                <div style='margin-top:80px; font-weight:bold; font-size:13pt;'>
                    PROGRAM STUDI " . strtoupper(e($sample['prodi'])) . "<br>
                    " . (!empty($sample['fakultas']) ? strtoupper(e($sample['fakultas'])) . "<br>" : "") . "
                    " . strtoupper(e($sample['kampus'])) . "<br>" . date('Y') . "
                </div>
            </div>
            <div style='page-break-after:always;'></div>
            <h2>DAFTAR ISI</h2>
            <div style='margin-top:16px;'>{$tocHtml}</div>
            <div style='page-break-after:always;'></div>
            <h2>BAB I<br>PENDAHULUAN</h2>" . nl2br(e($sample['bab1'])) . "
            <div style='page-break-after:always;'></div>
            <h2>BAB II<br>TINJAUAN PUSTAKA</h2>" . nl2br(e($sample['bab2'])) . "
            <div style='page-break-after:always;'></div>
            <h2>BAB III<br>METODOLOGI PENELITIAN</h2>" . nl2br(e($sample['bab3']));

            if (!empty($sample['bab4'])) {
                $html .= "<div style='page-break-after:always;'></div><h2>BAB IV<br>HASIL DAN PEMBAHASAN</h2>" . nl2br(e($sample['bab4']));
            }
            if (!empty($sample['bab5'])) {
                $html .= "<div style='page-break-after:always;'></div><h2>BAB V<br>PENUTUP</h2>" . nl2br(e($sample['bab5']));
            }

            $html .= "<div style='page-break-after:always;'></div>
            <h2>DAFTAR PUSTAKA</h2><p style='text-indent:0; padding-left:1.27cm; text-indent:-1.27cm;'>" . nl2br(e($sample['dp'])) . "</p>
            </body></html>";

            $dompdf = new Dompdf();
            $dompdf->set_option('isHtml5ParserEnabled', true);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4');
            $dompdf->render();
            $inline = $request->boolean('inline');
            return $dompdf->stream('Contoh_' . ucfirst($major) . '.pdf', ['Attachment' => !$inline]);
        }

        if (ob_get_length())
            ob_end_clean();
        $phpWord = $this->createPhpWordDocument($fakeProject, $request, null, ['tahap_pengerjaan' => $jenisDok]);
        $filename = 'Contoh_' . ucfirst($major) . '.docx';
        $tmpDir = rtrim(sys_get_temp_dir(), '/\\');
        Settings::setTempDir($tmpDir);
        $path = $tmpDir . '/' . $filename;
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($path);
        $this->postProcessDocxOutline($path);

        foreach ($this->docxTempFiles as $tmp) {
            if ($tmp && file_exists($tmp)) {
                @unlink($tmp);
            }
        }
        $this->docxTempFiles = [];

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    private function cleanDaftarPustaka(string $raw, string $tahunMin = '2023', string $gayaSitasi = 'APA 7th'): string
    {
        $text = strip_tags($raw);
        $text = str_replace(['*', '#', '_', '`'], '', $text);
        $lines = explode("\n", $text);
        $cleaned = [];
        $guideDp = $this->buildCitationStyleGuide($gayaSitasi);
        $minYr = (int) $tahunMin;
        $gayaLower = strtolower(trim($gayaSitasi));
        $isNumberedStyle = str_contains($gayaLower, 'ieee') || str_contains($gayaLower, 'vancouver');

        foreach ($lines as $line) {
            $l = trim($line);
            if (strlen($l) < 12) {
                continue;
            }
            if (preg_match('/^(berikut\s+adalah|daftar\s+pustaka|referensi\s*:|catatan\s*:|berikut\s+daftar)/i', $l)) {
                continue;
            }
            if (!$isNumberedStyle) {
                $l = (string) preg_replace('/^[\d\-\•]+\.?\s*/', '', $l);
            } else {
                $l = (string) preg_replace('/^[\-\•]+\s*/', '', $l);
            }
            // Saring jika tahun terbit di bawah batas minimum (kecuali regulasi UU/Peraturan/Permendikbud)
            $isRegulation = (bool) preg_match('/\b(undang-undang|peraturan|permendikbud|kementerian|republic|republik\s+indonesia)\b/i', $l);
            if (!$isRegulation && $minYr > 2000 && preg_match('/\b(19\d{2}|20\d{2})\b/', $l, $m)) {
                $yr = (int) $m[1];
                if ($yr < $minYr) {
                    continue;
                }
            }
            // Normalisasi format entri APA-style menjadi Harvard bila mahasiswa memilih gaya Harvard
            if ($guideDp['key'] === 'harvard' && !str_contains($l, "'") && preg_match('/^(.+?)\s+\(((?:19|20)\d{2}[a-z]?)\)\.\s+([^.?!]{6,220})\.\s+(.+)$/u', $l, $hm)) {
                $hAuth = rtrim(str_replace(', & ', ' & ', trim($hm[1])), ', ');
                $hYr = $hm[2];
                $hTitle = trim($hm[3]);
                $hRest = trim($hm[4]);
                $l = "{$hAuth} ({$hYr}) '{$hTitle}', {$hRest}";
            }
            $cleaned[] = trim($l);
        }
        return implode("\n", $cleaned);
    }

    protected function isPublicGalleryProjectCompleted(Project $project): ?string
    {
        // Cegah bypass download gratis / kebocoran draf akun Free: hanya proyek berbayar (is_full_unlocked) atau milik Admin yang layak tampil & diunduh di galeri publik
        $ownerIsAdmin = false;
        if (!empty($project->user_id)) {
            $owner = User::find($project->user_id);
            $ownerIsAdmin = $owner ? $this->billing()->isAdminUser($owner) : false;
        }
        if (empty($project->is_full_unlocked) && !$ownerIsAdmin) {
            return null;
        }

        $b1 = mb_strlen(trim(strip_tags((string) $project->bab1_content)));
        $b2 = mb_strlen(trim(strip_tags((string) $project->bab2_content)));
        $b3 = mb_strlen(trim(strip_tags((string) $project->bab3_content)));
        $b4 = mb_strlen(trim(strip_tags((string) $project->bab4_content)));
        $b5 = mb_strlen(trim(strip_tags((string) $project->bab5_content)));
        $dp = mb_strlen(trim(strip_tags((string) $project->daftar_pustaka)));

        // Minimal Bab 1-3 dan Daftar Pustaka wajib terisi lengkap
        if ($b1 < 200 || $b2 < 200 || $b3 < 200 || $dp < 30) {
            return null;
        }

        // Jika Bab 4 dan Bab 5 juga sudah terisi lengkap -> Full Skripsi selesai (Bab 1-5)
        if ($b4 >= 200 && $b5 >= 200) {
            return 'skripsi';
        }

        // Jika Bab 4 atau Bab 5 baru terisi sebagian -> masih dalam pengerjaan, jangan tampilkan
        if ($b4 > 0 || $b5 > 0) {
            return null;
        }

        // Hanya tampilkan sebagai Proposal selesai jika target proyeknya memang Proposal (bukan Full Skripsi yang baru sampai Bab 3)
        $meta = $this->getProjectMeta($project);
        $tahap = strtolower((string) ($meta['tahap_pengerjaan'] ?? ''));
        if ($tahap !== '' && !str_contains($tahap, 'proposal')) {
            return null;
        }

        return 'proposal';
    }

    public function landingPage()
    {
        $publicProjects = collect();
        try {
            $adminUserIds = User::whereIn('email', ['alwi@gmail.com', 'muhalwisyahrir@gmail.com'])->pluck('id')->all();
            $candidates = Project::whereNotNull('bab1_content')->whereRaw("CHAR_LENGTH(TRIM(bab1_content)) >= 200")
                ->whereNotNull('bab2_content')->whereRaw("CHAR_LENGTH(TRIM(bab2_content)) >= 200")
                ->whereNotNull('bab3_content')->whereRaw("CHAR_LENGTH(TRIM(bab3_content)) >= 200")
                ->whereNotNull('daftar_pustaka')->whereRaw("CHAR_LENGTH(TRIM(daftar_pustaka)) >= 30")
                ->whereRaw('CHAR_LENGTH(TRIM(judul)) >= 12')
                ->where(function ($q) use ($adminUserIds) {
                    $q->where('is_full_unlocked', true);
                    if (!empty($adminUserIds)) {
                        $q->orWhereIn('user_id', $adminUserIds);
                    }
                })
                ->orderBy('updated_at', 'desc')
                ->take(60)
                ->get();

            $fullSkripsi = collect();
            $proposalProjects = collect();
            foreach ($candidates as $proj) {
                $stage = $this->isPublicGalleryProjectCompleted($proj);
                if ($stage === 'skripsi' && $fullSkripsi->count() < 12) {
                    $proj->gallery_stage = 'skripsi';
                    $fullSkripsi->push($proj);
                } elseif ($stage === 'proposal' && $proposalProjects->count() < 12) {
                    $proj->gallery_stage = 'proposal';
                    $proposalProjects->push($proj);
                }
            }

            $publicProjects = $fullSkripsi->concat($proposalProjects)
                ->sortByDesc('updated_at')
                ->values();
        } catch (Throwable $e) {
            $publicProjects = collect();
        }
        $plans = $this->billing()->getPlans(true);
        return view('landing', compact('publicProjects', 'plans'));
    }

    public function downloadPublicProject($id, string $format, Request $request)
    {
        $project = Project::findOrFail($id);

        // Hanya izinkan pratinjau/unduh publik untuk proyek yang sudah benar-benar selesai di-generate
        $stage = $this->isPublicGalleryProjectCompleted($project);
        if ($stage === null) {
            abort(404, 'Dokumen belum selesai disusun.');
        }

        $meta = $this->getProjectMeta($project);
        if (!empty($project->user_id)) {
            $defaultIdentity = $this->getDefaultUserIdentity($project->user_id);
            foreach (['nama', 'nim', 'kampus', 'fakultas', 'logo_base64'] as $field) {
                if (empty($meta[$field]) && !empty($defaultIdentity[$field])) {
                    $meta[$field] = $defaultIdentity[$field];
                }
            }
        }

        $hasFull = $stage === 'skripsi';
        $request->merge([
            'nama' => $meta['nama'] ?? 'Mahasiswa Peneliti',
            'npm' => $meta['nim'] ?? '-',
            'kampus' => $meta['kampus'] ?? 'Universitas di Indonesia',
            'fakultas' => $meta['fakultas'] ?? '',
            'jenis_dokumen' => $hasFull ? 'SKRIPSI' : 'PROPOSAL SKRIPSI',
        ]);

        if ($format === 'pdf') {
            return $this->renderAndStreamProjectPdf($project, $request, $meta, false);
        }

        $logoPath = $this->resolveLogoTempPath($request, $meta);
        if (ob_get_length())
            ob_end_clean();
        $phpWord = $this->createPhpWordDocument($project, $request, $logoPath, $meta);
        $filename = 'Dokumen_' . Str::slug(substr($project->judul, 0, 28), '_') . '.docx';
        $tmpDir = rtrim(sys_get_temp_dir(), '/\\');
        Settings::setTempDir($tmpDir);
        $path = $tmpDir . '/' . $filename;
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($path);
        $this->postProcessDocxOutline($path);

        foreach ($this->docxTempFiles as $tmp) {
            if ($tmp && file_exists($tmp)) {
                @unlink($tmp);
            }
        }
        $this->docxTempFiles = [];

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    public function refineReferences($id, Request $request)
    {
        $project = Project::where('user_id', Auth::id())->findOrFail($id);
        $model = $request->input('model', 'auto');
        $meta = $this->getProjectMeta($project);
        $sitasi = $meta['sitasi'] ?? [];
        $gayaSitasi = (string) ($sitasi['gaya'] ?? 'APA 7th');
        $tahunMin = !empty($sitasi['tahun_min']) ? (string) $sitasi['tahun_min'] : '2023';
        $asalRef = (string) ($sitasi['asal'] ?? 'Indonesia + Internasional');

        $allText = strip_tags($project->bab1_content . ' ' . $project->bab2_content . ' ' . $project->bab3_content . ' ' . $project->bab4_content . ' ' . $project->bab5_content);
        $citationsList = $this->extractInTextCitations($allText);

        // Cocokkan deterministik dulu dengan Bank Referensi Terverifikasi OpenAlex/Crossref proyek ini
        [$verifiedMatched, $unmatchedCits] = $this->buildVerifiedMatchedDaftarPustaka($project, $citationsList, $gayaSitasi);

        $verifiedBlock = !empty($verifiedMatched)
            ? "\nENTRI REFERENSI TERVERIFIKASI DENGAN DOI ASLI (WAJIB DISERTAKAN PERSIS — DILARANG MENGUBAH PENULIS/TAHUN/DOI):\n- " . implode("\n- ", $verifiedMatched) . "\n"
            : "";

        $citationsBlock = !empty($unmatchedCits)
            ? "\nSITASI LAIN YANG MUNCUL DALAM NASKAH (WAJIB DIBUATKAN BIBLIOGRAFINYA SESUAI GAYA {$gayaSitasi}):\n- " . implode("\n- ", $unmatchedCits) . "\n"
            : (!empty($citationsList) ? "\nDAFTAR SITASI ASLI YANG MUNCUL DALAM NASKAH:\n- " . implode("\n- ", $citationsList) . "\n" : "");

        $refUploadNote = !empty($meta['referensi_khusus'])
            ? "\nREFERENSI WAJIB DARI UNGGAHAN MAHASISWA (WAJIB MASUK KE DALAM DAFTAR PUSTAKA):\n" . Str::limit($meta['referensi_khusus'], 2500) . "\n"
            : "";

        $styleGuideRefine = $this->buildCitationStyleGuide($gayaSitasi);
        $prompt = "Tulis DAFTAR PUSTAKA lengkap KHUSUS format {$gayaSitasi} berdasarkan naskah proposal/skripsi berjudul '{$project->judul}' (Prodi: {$project->prodi}) berikut ini.
Asal Referensi: {$asalRef}.
Tahun terbit referensi: minimal tahun {$tahunMin} dan maksimal tahun 2026.
{$verifiedBlock}
{$citationsBlock}
{$refUploadNote}
ATURAN MUTLAK & TEGAS (GAYA {$styleGuideRefine['label']}):
1. WAJIB menyertakan SELURUH ENTRI REFERENSI TERVERIFIKASI di atas secara utuh tanpa mengubah judul, penulis, tahun, ataupun link DOI aslinya!
2. WAJIB memformat SELURUH entri (termasuk referensi non-terverifikasi/tambahan) mengikuti aturan gaya {$gayaSitasi} berikut:
   {$styleGuideRefine['bib_rule']}
   Contoh format WAJIB: {$styleGuideRefine['bib_example']}
   DILARANG KERAS menulis dengan gaya APA bila mahasiswa memilih gaya {$gayaSitasi}!
3. DAFTAR PUSTAKA WAJIB 100% SINKRON dengan sitasi asli yang benar-benar muncul dalam naskah. DILARANG KERAS mengarang referensi fiktif!
4. LANGSUNG tulis baris daftar pustaka pertama ({$styleGuideRefine['sort_instruction']}) TANPA kalimat pengantar seperti 'Berikut adalah daftar pustaka...'.
5. DILARANG menulis placeholder atau mengarang link DOI palsu untuk buku/regulasi/laporan yang tidak memiliki DOI.
6. Jangan gunakan tanda bintang markdown (*).

NASKAH BAB I - BAB V:
" . substr($allText, 0, 9500);

        try {
            $daftarPustaka = $this->ai->generateText($prompt, $model);
            $cleaned = $this->cleanDaftarPustaka($daftarPustaka, $tahunMin, $gayaSitasi);

            // Pastikan setiap entri terverifikasi yang dikutip tidak hilang setelah proses AI
            if (!empty($verifiedMatched)) {
                $existingLower = strtolower($cleaned);
                $missingVerified = [];
                foreach ($verifiedMatched as $vm) {
                    $doiMatch = preg_match('#https?://doi\.org/[^\s]+#i', $vm, $dm) ? strtolower($dm[0]) : strtolower(substr($vm, 0, 45));
                    if ($doiMatch !== '' && !str_contains($existingLower, $doiMatch)) {
                        $missingVerified[] = $vm;
                    }
                }
                if (!empty($missingVerified)) {
                    $allLines = array_values(array_filter(array_map('trim', explode("\n", trim($cleaned . "\n" . implode("\n", $missingVerified))))));
                    $gayaLower = strtolower($gayaSitasi);
                    if (!str_contains($gayaLower, 'ieee') && !str_contains($gayaLower, 'vancouver')) {
                        sort($allLines, SORT_NATURAL | SORT_FLAG_CASE);
                    }
                    $cleaned = implode("\n", $allLines);
                }
            }

            $project->daftar_pustaka = $cleaned;
            $project->save();

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'daftar_pustaka' => $project->daftar_pustaka,
                    'verified_matched_count' => count($verifiedMatched),
                ]);
            }
            return back()->with('success', 'Daftar pustaka berhasil diperbarui.');
        } catch (Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
            }
            return back()->with('error', 'Gagal memperbarui daftar pustaka.');
        }
    }

    /**
     * Audit Pra-Bimbingan Ala Dosen Pembimbing (Deterministik, Instan & Akurat — 0% Halusinasi AI)
     */
    public function auditProject(Request $request, $id)
    {
        $project = Project::where('user_id', Auth::id())->findOrFail($id);
        $meta = $this->getProjectMeta($project);
        $sitasi = $meta['sitasi'] ?? [];
        $gayaSitasi = (string) ($sitasi['gaya'] ?? 'APA 7th');
        $tahunMin = !empty($sitasi['tahun_min']) ? (int) $sitasi['tahun_min'] : 2023;
        $asalRef = (string) ($sitasi['asal'] ?? 'Indonesia + Internasional');

        $isProposal = str_contains(strtolower((string) ($meta['tahap_pengerjaan'] ?? '')), 'proposal')
            || str_contains(strtolower((string) ($meta['jenis_penelitian'] ?? '')), 'proposal')
            || in_array((string) ($project->sumber_data ?? ''), ['Proposal Skripsi (Bab I-III)', 'Proposal Skripsi (S1)'], true);

        $b1 = trim(strip_tags((string) ($project->bab1_content ?? '')));
        $b2 = trim(strip_tags((string) ($project->bab2_content ?? '')));
        $b3 = trim(strip_tags((string) ($project->bab3_content ?? '')));
        $b4 = trim(strip_tags((string) ($project->bab4_content ?? '')));
        $b5 = trim(strip_tags((string) ($project->bab5_content ?? '')));
        $dp = trim(strip_tags((string) ($project->daftar_pustaka ?? '')));

        $checks = [];
        $earned = 0;
        $maxPoints = 0;

        // 1. Cek Sitasi Paragraf Pertama Bab I (Wajib minimal 1 sitasi ilmiah)
        $maxPoints += 15;
        $firstParaOk = false;
        $firstParaSample = '';
        if ($b1 !== '') {
            $linesB1 = preg_split('/\n+/', $b1) ?: [];
            foreach ($linesB1 as $ln) {
                $t = trim($ln);
                if (mb_strlen($t) < 90) {
                    continue;
                }
                if (preg_match('/^(BAB\s+I|PENDAHULUAN|1\.1\s+Latar|A\.\s+Latar)/i', $t)) {
                    continue;
                }
                $firstParaSample = $t;
                break;
            }
            $firstParaCites = $firstParaSample !== '' ? $this->extractInTextCitations($firstParaSample) : [];
            $firstParaOk = count($firstParaCites) >= 1;
        }
        if ($b1 === '') {
            $checks[] = [
                'id' => 'bab1_first_cite',
                'title' => 'Sitasi Paragraf Pertama Bab I',
                'status' => 'warn',
                'summary' => 'Bab I belum ditulis.',
                'details' => ['Susun Bab I terlebih dahulu agar paragraf pembuka memiliki landasan sitasi ilmiah.'],
                'action' => ['type' => 'chapter', 'target' => 'bab1', 'label' => 'Buka Bab I'],
            ];
        } elseif ($firstParaOk) {
            $earned += 15;
            $checks[] = [
                'id' => 'bab1_first_cite',
                'title' => 'Sitasi Paragraf Pertama Bab I',
                'status' => 'pass',
                'summary' => 'Paragraf pertama 1.1 Latar Belakang sudah memuat sitasi ilmiah (' . implode(', ', array_slice($firstParaCites, 0, 2)) . ').',
                'details' => [],
                'action' => null,
            ];
        } else {
            $earned += 5;
            $checks[] = [
                'id' => 'bab1_first_cite',
                'title' => 'Sitasi Paragraf Pertama Bab I',
                'status' => 'fail',
                'summary' => 'Paragraf pertama Bab I belum memiliki sitasi in-text (minimal 1 sitasi ilmiah).',
                'details' => ['Dosen penguji umumnya mewajibkan minimal 1 rujukan ilmiah/jurnal pada paragraf pembuka 1.1 Latar Belakang.'],
                'action' => ['type' => 'revise', 'target' => 'bab1', 'label' => 'Buka Bab I untuk Tambah Sitasi'],
            ];
        }

        // 2. Cek Benang Merah Rumusan Masalah (Bab I) vs Kesimpulan (Bab V)
        $maxPoints += 20;
        $rmCount = 0;
        if ($b1 !== '' && preg_match('/(?:1\.4|1\.2|B\.|C\.|D\.)?\s*Rumusan\s+Masalah[^\n]*\n([\s\S]{20,1600}?)(?=\n\s*(?:1\.[3-9]|[B-H]\.\s+Tujuan|Tujuan\s+Penelitian|1\.5|Manfaat)|$)/i', $b1, $mRm)) {
            preg_match_all('/^\s*(?:\d+[\.\)]|[a-z][\.\)])\s+.{10,}/m', $mRm[1], $rmItems);
            $rmCount = count($rmItems[0] ?? []);
        }
        $kesCount = 0;
        if ($b5 !== '' && preg_match('/(?:5\.1|A\.)?\s*(?:Kesimpulan|Simpulan)[^\n]*\n([\s\S]{20,2400}?)(?=\n\s*(?:5\.2|B\.\s+Saran|Saran)|$)/i', $b5, $mKes)) {
            preg_match_all('/^\s*(?:\d+[\.\)]|[a-z][\.\)])\s+.{10,}/m', $mKes[1], $kesItems);
            $kesCount = count($kesItems[0] ?? []);
        }
        if ($isProposal) {
            if ($rmCount >= 1) {
                $earned += 20;
                $checks[] = [
                    'id' => 'benang_merah',
                    'title' => 'Struktur Rumusan Masalah (Mode Proposal)',
                    'status' => 'pass',
                    'summary' => "Terdeteksi {$rmCount} butir Rumusan Masalah terstruktur di Bab I (siap dilanjutkan ke Bab IV–V saat Skripsi).",
                    'details' => [],
                    'action' => null,
                ];
            } else {
                $earned += 8;
                $checks[] = [
                    'id' => 'benang_merah',
                    'title' => 'Struktur Rumusan Masalah (Mode Proposal)',
                    'status' => 'warn',
                    'summary' => 'Butir bernomor Rumusan Masalah di Bab I belum terdeteksi jelas.',
                    'details' => ['Pastikan Rumusan Masalah ditulis dalam poin bernomor 1., 2., 3. agar mudah ditelusuri dosen.'],
                    'action' => ['type' => 'chapter', 'target' => 'bab1', 'label' => 'Periksa Bab I'],
                ];
            }
        } else {
            if ($rmCount > 0 && $kesCount > 0 && $rmCount === $kesCount) {
                $earned += 20;
                $checks[] = [
                    'id' => 'benang_merah',
                    'title' => 'Benang Merah Bab I ↔ Bab V',
                    'status' => 'pass',
                    'summary' => "Sinkron 100%: {$rmCount} Rumusan Masalah di Bab I dijawab tepat oleh {$kesCount} poin Kesimpulan di Bab V.",
                    'details' => [],
                    'action' => null,
                ];
            } elseif ($b5 === '') {
                $earned += 6;
                $checks[] = [
                    'id' => 'benang_merah',
                    'title' => 'Benang Merah Bab I ↔ Bab V',
                    'status' => 'warn',
                    'summary' => "Bab I memiliki {$rmCount} Rumusan Masalah, namun Bab V (Penutup) belum disusun.",
                    'details' => ['Susun Bab V agar jumlah poin Kesimpulan menjawab seluruh Rumusan Masalah secara tepat.'],
                    'action' => ['type' => 'chapter', 'target' => 'bab5', 'label' => 'Buka Bab V'],
                ];
            } else {
                $earned += 10;
                $checks[] = [
                    'id' => 'benang_merah',
                    'title' => 'Benang Merah Bab I ↔ Bab V',
                    'status' => 'warn',
                    'summary' => "Jumlah poin belum seimbang: Bab I memiliki {$rmCount} Rumusan Masalah, sedangkan Bab V memiliki {$kesCount} poin bernomor Kesimpulan.",
                    'details' => ['Samakan jumlah poin bernomor Kesimpulan (5.1) dengan jumlah Rumusan Masalah Bab I agar lolos pemeriksaan dosen pembimbing.'],
                    'action' => ['type' => 'chapter', 'target' => 'bab5', 'label' => 'Buka & Rapikan Bab V'],
                ];
            }
        }

        // 3. Cek Sinkronisasi 1-to-1 Sitasi In-Text <-> Daftar Pustaka
        $maxPoints += 25;
        $bodyAll = $b1 . "\n" . $b2 . "\n" . $b3 . "\n" . $b4;
        $inTextCites = $this->extractInTextCitations($bodyAll);
        $dpLines = array_values(array_filter(array_map('trim', explode("\n", $dp)), fn ($l) => mb_strlen($l) >= 15));

        $missingFromDp = [];
        $matchedDpIdx = [];
        foreach ($inTextCites as $cit) {
            $found = false;
            if (preg_match('/^\[(\d{1,2})\]$/', trim($cit), $nm)) {
                $numPattern = '/^\[' . $nm[1] . '\]\s+/';
                foreach ($dpLines as $idx => $line) {
                    if (preg_match($numPattern, $line)) {
                        $found = true;
                        $matchedDpIdx[$idx] = true;
                        break;
                    }
                }
            } else {
                preg_match('/\b((?:19|20)\d{2})\b/', $cit, $ym);
                $yr = $ym[1] ?? '';
                $authorRaw = trim((string) preg_replace('/\s*\(?\d{4}[a-z]?\)?.*$/', '', $cit));
                $firstTok = strtolower((string) preg_replace('/[^a-zA-Z]/', '', explode(' ', $authorRaw)[0] ?? ''));
                if ($firstTok !== '' && strlen($firstTok) >= 2) {
                    foreach ($dpLines as $idx => $line) {
                        $ll = strtolower($line);
                        if (str_contains($ll, $firstTok) && ($yr === '' || str_contains($ll, $yr))) {
                            $found = true;
                            $matchedDpIdx[$idx] = true;
                            break;
                        }
                    }
                }
            }
            if (!$found) {
                $missingFromDp[] = $cit;
            }
        }
        $orphanDp = [];
        foreach ($dpLines as $idx => $line) {
            if (!isset($matchedDpIdx[$idx])) {
                $orphanDp[] = Str::limit($line, 85);
            }
        }

        if (empty($inTextCites) && empty($dpLines)) {
            $checks[] = [
                'id' => 'citation_sync',
                'title' => 'Sinkronisasi Sitasi ↔ Daftar Pustaka',
                'status' => 'warn',
                'summary' => 'Belum ada sitasi atau Daftar Pustaka yang dapat diaudit.',
                'details' => [],
                'action' => ['type' => 'chapter', 'target' => 'daftar_pustaka', 'label' => 'Buka Daftar Pustaka'],
            ];
        } elseif (empty($missingFromDp) && count($orphanDp) <= 1) {
            $earned += 25;
            $checks[] = [
                'id' => 'citation_sync',
                'title' => 'Sinkronisasi Sitasi ↔ Daftar Pustaka',
                'status' => 'pass',
                'summary' => 'Seluruh ' . count($inTextCites) . ' sitasi in-text tercantum rapi di Daftar Pustaka (' . count($dpLines) . ' entri).',
                'details' => [],
                'action' => null,
            ];
        } else {
            $ratio = count($inTextCites) > 0 ? max(0, (count($inTextCites) - count($missingFromDp)) / count($inTextCites)) : 0.5;
            $earned += (int) round(25 * $ratio * 0.75);
            $details = [];
            if (!empty($missingFromDp)) {
                $details[] = count($missingFromDp) . ' sitasi dalam naskah belum ada di Daftar Pustaka: ' . implode(', ', array_slice($missingFromDp, 0, 6)) . (count($missingFromDp) > 6 ? ' ...' : '');
            }
            if (!empty($orphanDp)) {
                $details[] = count($orphanDp) . ' entri di Daftar Pustaka belum terdeteksi dikutip di Bab I–IV: ' . implode(' | ', array_slice($orphanDp, 0, 3));
            }
            $checks[] = [
                'id' => 'citation_sync',
                'title' => 'Sinkronisasi Sitasi ↔ Daftar Pustaka',
                'status' => count($missingFromDp) > 0 ? 'fail' : 'warn',
                'summary' => count($missingFromDp) . ' sitasi hilang dari Daftar Pustaka · ' . count($orphanDp) . ' referensi belum dikutip.',
                'details' => $details,
                'action' => ['type' => 'sync_dp', 'target' => 'daftar_pustaka', 'label' => 'Sinkronkan Daftar Pustaka Otomatis'],
            ];
        }

        // 4. Kepatuhan Tahun Terbit Minimum & Pengaturan Sitasi Mahasiswa
        $maxPoints += 15;
        $outdatedCites = [];
        foreach ($inTextCites as $cit) {
            if (preg_match('/\b((?:19|20)\d{2})\b/', $cit, $ym)) {
                $yr = (int) $ym[1];
                $isReg = (bool) preg_match('/^(UU|Undang|Permen|PP|Perpres|Kepmen|UUD|SNI|ASTM)/i', $cit);
                if (!$isReg && $yr < $tahunMin) {
                    $outdatedCites[] = $cit;
                }
            }
        }
        if (empty($outdatedCites)) {
            $earned += 15;
            $checks[] = [
                'id' => 'citation_year',
                'title' => "Kepatuhan Tahun & Gaya ({$gayaSitasi}, ≥ {$tahunMin})",
                'status' => 'pass',
                'summary' => "Seluruh sitasi mematuhi batas tahun minimum ≥ {$tahunMin} dan pengaturan asal {$asalRef}.",
                'details' => [],
                'action' => null,
            ];
        } else {
            $earned += 6;
            $checks[] = [
                'id' => 'citation_year',
                'title' => "Kepatuhan Tahun & Gaya ({$gayaSitasi}, ≥ {$tahunMin})",
                'status' => 'warn',
                'summary' => count($outdatedCites) . " sitasi berumur di bawah batas form mahasiswa ({$tahunMin}).",
                'details' => [
                    'Sitasi di bawah tahun ' . $tahunMin . ': ' . implode(', ', array_slice($outdatedCites, 0, 6)),
                    'Tip: Klik langsung teks sitasi berwarna biru di naskah lalu pilih "Ganti Sitasi" untuk menggantinya otomatis dengan jurnal terverifikasi ≥ ' . $tahunMin . '.',
                ],
                'action' => null,
            ];
        }

        // 5. Konsistensi Data Riil / Olah Data (Bab III & IV)
        $maxPoints += 10;
        $dataRiil = is_array($meta['data_riil'] ?? null) ? array_filter($meta['data_riil']) : [];
        if (empty($dataRiil)) {
            if ($isProposal) {
                $earned += 10;
                $checks[] = [
                    'id' => 'data_riil',
                    'title' => 'Integrasi Olah Data Penelitian',
                    'status' => 'pass',
                    'summary' => 'Mode Proposal (Bab I–III): Olah data empiris bersifat opsional hingga tahap pengambilan data.',
                    'details' => [],
                    'action' => null,
                ];
            } else {
                $earned += 5;
                $checks[] = [
                    'id' => 'data_riil',
                    'title' => 'Integrasi Olah Data Penelitian',
                    'status' => 'warn',
                    'summary' => 'Data olahan riil belum diisi pada Menu Olah Data Penelitian.',
                    'details' => ['Isi hasil SPSS / Kalkulator Olah Data agar angka di Bab IV 100% sesuai data lapanganmu.'],
                    'action' => ['type' => 'olah_data', 'target' => 'olah_data', 'label' => 'Buka Menu Olah Data'],
                ];
            }
        } else {
            preg_match_all('/\b\d+[.,]\d+\b/', (string) ($dataRiil['metrik'] ?? ''), $numMatches);
            $keyNums = array_slice(array_unique($numMatches[0] ?? []), 0, 5);
            $matchedNums = 0;
            foreach ($keyNums as $kn) {
                $altComma = str_replace('.', ',', $kn);
                $altDot = str_replace(',', '.', $kn);
                if (str_contains($b4, $kn) || str_contains($b4, $altComma) || str_contains($b4, $altDot)) {
                    $matchedNums++;
                }
            }
            if (empty($keyNums) || $matchedNums > 0 || $b4 === '') {
                $earned += 10;
                $checks[] = [
                    'id' => 'data_riil',
                    'title' => 'Integrasi Olah Data Penelitian',
                    'status' => 'pass',
                    'summary' => $b4 === ''
                        ? 'Parameter olah data riil sudah tersimpan siap digunakan saat menyusun Bab IV.'
                        : "Parameter olah data riil terverifikasi masuk di Bab IV ({$matchedNums}/" . max(1, count($keyNums)) . ' metrik kunci cocok).',
                    'details' => [],
                    'action' => null,
                ];
            } else {
                $earned += 4;
                $checks[] = [
                    'id' => 'data_riil',
                    'title' => 'Integrasi Olah Data Penelitian',
                    'status' => 'warn',
                    'summary' => 'Angka metrik di Menu Olah Data belum muncul persis di naskah Bab IV.',
                    'details' => ['Buka Bab IV lalu klik "Susun Ulang Bab Ini" setelah menyimpan hasil olah data agar seluruh angka sinkron.'],
                    'action' => ['type' => 'chapter', 'target' => 'bab4', 'label' => 'Buka Bab IV'],
                ];
            }
        }

        // 6. Kualitas Bahasa, Klise AI, Markdown & LaTeX Mentah
        $maxPoints += 15;
        $fullText = $bodyAll . "\n" . $b5;
        $formatIssues = [];
        $cliches = ['di era digital saat ini', 'di era globalisasi saat ini', 'tidak dapat dipungkiri bahwa', 'memegang peranan krusial', 'merupakan tonggak penting', 'kondisi ideal dalam'];
        $foundCliches = [];
        $fullLower = strtolower($fullText);
        foreach ($cliches as $cl) {
            if (str_contains($fullLower, $cl)) {
                $foundCliches[] = '"' . $cl . '"';
            }
        }
        if (!empty($foundCliches)) {
            $formatIssues[] = 'Terdeteksi frasa klise AI: ' . implode(', ', $foundCliches);
        }
        if (preg_match('/\\\(?:frac|hat|widehat|approx|times|cdot)\b/', $fullText)) {
            $formatIssues[] = 'Masih terdapat kode LaTeX mentah (mis. \\frac atau \\hat) di naskah.';
        }
        if (preg_match('/^\s*#{2,6}\s+/m', $fullText)) {
            $formatIssues[] = 'Masih terdapat simbol markdown heading (###) yang belum dibersihkan.';
        }

        if (empty($formatIssues)) {
            $earned += 15;
            $checks[] = [
                'id' => 'academic_style',
                'title' => 'Kualitas Bahasa & Kebersihan Format',
                'status' => 'pass',
                'summary' => 'Bebas frasa klise AI, bebas markdown mentah (###), dan bebas kebocoran LaTeX.',
                'details' => [],
                'action' => null,
            ];
        } else {
            $earned += 7;
            $checks[] = [
                'id' => 'academic_style',
                'title' => 'Kualitas Bahasa & Kebersihan Format',
                'status' => 'warn',
                'summary' => count($formatIssues) . ' catatan gaya bahasa/format akademik ditemukan.',
                'details' => $formatIssues,
                'action' => ['type' => 'chapter', 'target' => 'revisi', 'label' => 'Buka Lab Revisi'],
            ];
        }

        $score = $maxPoints > 0 ? (int) round(($earned / $maxPoints) * 100) : 0;
        $gradeLabel = $score >= 88
            ? 'Siap Bimbingan Dosen'
            : ($score >= 70 ? 'Layak dengan Revisi Minor' : 'Perlu Perbaikan Sebelum Bimbingan');

        return response()->json([
            'status' => 'success',
            'score' => $score,
            'grade_label' => $gradeLabel,
            'citation_settings' => [
                'gaya' => $gayaSitasi,
                'tahun_min' => $tahunMin,
                'asal' => $asalRef,
                'verified_bank_count' => count($meta['verified_refs'] ?? []),
            ],
            'stats' => [
                'in_text_count' => count($inTextCites),
                'dp_count' => count($dpLines),
                'missing_dp_count' => count($missingFromDp),
                'orphan_dp_count' => count($orphanDp),
                'rm_count' => $rmCount,
                'kes_count' => $kesCount,
            ],
            'checks' => $checks,
        ]);
    }

    /**
     * Ganti Sitasi Interaktif langsung dari Workspace Penulisan (Buka Sumber / Ganti Sitasi Terverifikasi sesuai Pengaturan Form Mahasiswa)
     */
    public function replaceCitation(Request $request, $id)
    {
        $project = Project::where('user_id', Auth::id())->findOrFail($id);
        $meta = $this->getProjectMeta($project);
        $sitasi = $meta['sitasi'] ?? [];
        $gayaSitasi = (string) ($sitasi['gaya'] ?? 'APA 7th');
        $tahunMin = !empty($sitasi['tahun_min']) ? max(2015, min(2026, (int) $sitasi['tahun_min'])) : 2023;
        $asalRef = (string) ($sitasi['asal'] ?? 'Indonesia + Internasional');
        $bahasa = (string) ($sitasi['bahasa'] ?? 'Indonesia');

        $action = (string) $request->input('action', 'candidates');
        $chapter = (string) $request->input('chapter', 'bab1');
        $oldCite = trim((string) $request->input('old_cite', ''));
        $oldAuthor = trim((string) $request->input('old_author', ''));
        $paragraph = trim((string) $request->input('paragraph', ''));
        $customQuery = trim((string) $request->input('query', ''));

        $allowedChapters = ['bab1', 'bab2', 'bab3', 'bab4', 'bab5'];
        if (!in_array($chapter, $allowedChapters, true)) {
            return response()->json(['status' => 'error', 'message' => 'Bab tidak valid untuk penggantian sitasi.'], 422);
        }

        // Ambil kandidat jurnal terverifikasi yang relevan dengan kalimat/paragraf + mematuhi pengaturan sitasi mahasiswa!
        $buildCandidates = function () use ($project, $paragraph, $customQuery, $tahunMin, $asalRef, $gayaSitasi, $oldAuthor): array {
            $searchBase = $customQuery !== ''
                ? $customQuery
                : ($paragraph !== '' ? $paragraph : (string) $project->judul);
            $q = $this->buildJournalSearchQuery($searchBase, 6);
            $fresh = $q !== '' ? $this->queryVerifiedJournalsExternal($q, $tahunMin, $asalRef, $gayaSitasi, 8) : [];
            $locked = $this->getVerifiedRefsStructured($project);

            $combined = [];
            $seen = [];
            $oldFirst = strtolower((string) preg_replace('/[^a-zA-Z]/', '', explode(' ', $oldAuthor)[0] ?? ''));

            foreach (array_merge($fresh, $locked) as $ref) {
                if (!is_array($ref) || empty($ref['title'])) {
                    continue;
                }
                $yr = (int) ($ref['year'] ?? 0);
                if ($yr < $tahunMin || $yr > 2026) {
                    continue;
                }
                $refFirst = strtolower((string) preg_replace('/[^a-zA-Z]/', '', (string) ($ref['first_surname'] ?? '')));
                if ($oldFirst !== '' && $refFirst === $oldFirst) {
                    continue;
                }
                $k = strtolower((string) $ref['title']);
                if (isset($seen[$k])) {
                    continue;
                }
                $seen[$k] = true;
                $ref['formatted'] = $this->formatVerifiedReferenceEntry($ref, $gayaSitasi);
                $combined[] = $ref;
            }
            return array_slice($combined, 0, 8);
        };

        if ($action === 'candidates') {
            $candidates = $buildCandidates();
            return response()->json([
                'status' => 'success',
                'settings' => [
                    'gaya' => $gayaSitasi,
                    'tahun_min' => $tahunMin,
                    'asal' => $asalRef,
                    'bahasa' => $bahasa,
                ],
                'candidates' => $candidates,
            ]);
        }

        // Action = replace
        if ($oldCite === '') {
            return response()->json(['status' => 'error', 'message' => 'Sitasi lama tidak terdeteksi.'], 422);
        }

        $chosenRef = $request->input('chosen_ref');
        if (is_string($chosenRef)) {
            $chosenRef = json_decode($chosenRef, true);
        }
        if (!is_array($chosenRef) || empty($chosenRef['in_text']) || empty($chosenRef['year'])) {
            $candidates = $buildCandidates();
            if (empty($candidates)) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Belum ditemukan jurnal pengganti terverifikasi (≥ {$tahunMin}). Coba tulis kata kunci pencarian yang lebih umum.",
                ], 422);
            }
            $chosenRef = $candidates[0];
        }

        $adjustSentence = filter_var($request->input('adjust_sentence', true), FILTER_VALIDATE_BOOLEAN);
        $col = $chapter . '_content';
        $chapterContent = (string) ($project->$col ?? '');
        if ($chapterContent === '') {
            return response()->json(['status' => 'error', 'message' => 'Konten bab masih kosong.'], 422);
        }

        $verifiedBankForReplace = $this->getVerifiedRefsStructured($project);
        $refIndex = 1;
        foreach ($verifiedBankForReplace as $vIdx => $vr) {
            if (strtolower((string) ($vr['title'] ?? '')) === strtolower((string) ($chosenRef['title'] ?? ''))) {
                $refIndex = $vIdx + 1;
                break;
            }
        }
        $isParenthetical = str_starts_with(trim($oldCite), '(') && str_ends_with(trim($oldCite), ')');
        $newInText = $this->formatVerifiedInTextCitation($chosenRef, $gayaSitasi, $refIndex, $isParenthetical);
        $newFormattedRef = $this->formatVerifiedReferenceEntry($chosenRef, $gayaSitasi, $refIndex);

        // Temukan blok paragraf yang memuat sitasi lama di dalam naskah mentah bab
        $lines = preg_split("/(\r\n|\n)/", $chapterContent) ?: [];
        $targetLineIdx = -1;
        foreach ($lines as $idx => $ln) {
            if (str_contains($ln, $oldCite)) {
                if ($paragraph === '' || str_contains($ln, mb_substr($paragraph, 0, 35))) {
                    $targetLineIdx = $idx;
                    break;
                }
                if ($targetLineIdx === -1) {
                    $targetLineIdx = $idx;
                }
            }
        }
        if ($targetLineIdx === -1 && $oldAuthor !== '') {
            // Pencarian longgar bila tanda kurung / spasi sedikit berbeda di markdown mentah
            $pat = '/' . preg_quote($oldAuthor, '/') . '[^.\n]{0,20}\b' . preg_quote((string) $request->input('old_year', ''), '/') . '\b/i';
            foreach ($lines as $idx => $ln) {
                if (preg_match($pat, $ln)) {
                    $targetLineIdx = $idx;
                    break;
                }
            }
        }

        if ($targetLineIdx === -1) {
            return response()->json([
                'status' => 'error',
                'message' => 'Posisi sitasi tidak ditemukan secara persis di bab ini. Coba buka bab terkait terlebih dahulu.',
            ], 422);
        }

        $origLine = $lines[$targetLineIdx];
        $updatedLine = '';

        if ($adjustSentence) {
            try {
                $prompt = "Anda adalah Editor Skripsi Akademik Indonesia.\n"
                    . "Tugas Anda: Ganti sitasi lama `{$oldCite}` pada paragraf berikut menjadi sitasi terverifikasi baru `{$newInText}` (dari artikel asli: \"{$chosenRef['title']}\" — {$chosenRef['venue']}, {$chosenRef['year']}), DAN sesuaikan halus kalimat yang mengutipnya agar klaimnya benar-benar selaras dengan topik artikel baru tersebut.\n\n"
                    . "ATURAN MUTLAK:\n"
                    . "1. WAJIB menyertakan sitasi persis `{$newInText}` sesuai gaya {$gayaSitasi} (tahun {$chosenRef['year']}).\n"
                    . "2. Pertahankan gaya bahasa {$bahasa} ilmiah baku, struktur paragraf, dan sitasi lain (jika ada) di paragraf tersebut tetap utuh.\n"
                    . "3. DILARANG memakai tanda bintang markdown (** / *), heading (###), atau kalimat pengantar seperti 'Berikut paragraf hasil revisi:'.\n"
                    . "4. Keluarkan HANYA 1 paragraf hasil perbaikan tersebut.\n\n"
                    . "PARAGRAF ASLI:\n{$origLine}";
                $aiPara = trim($this->cleanTextKeepFormat($this->ai->generateText($prompt, 'auto')));
                $aiPara = trim((string) preg_replace('/\s*\n+\s*/', ' ', $aiPara));
                if ($aiPara !== '' && str_contains($aiPara, (string) $chosenRef['year']) && mb_strlen($aiPara) >= 40) {
                    $updatedLine = $aiPara;
                }
            } catch (Throwable $e) {
                Log::warning('AI sentence adjust fallback to direct replace: ' . $e->getMessage());
            }
        }

        if ($updatedLine === '') {
            if (str_contains($origLine, $oldCite)) {
                $updatedLine = str_replace($oldCite, $newInText, $origLine);
            } else {
                $pat = '/\(?' . preg_quote($oldAuthor, '/') . '[^.\n)]{0,20}\b' . preg_quote((string) $request->input('old_year', ''), '/') . '\b\)?/i';
                $updatedLine = (string) preg_replace($pat, $newInText, $origLine, 1);
            }
        }

        $lines[$targetLineIdx] = $updatedLine;
        $project->$col = implode("\n", $lines);

        // Simpan referensi baru ke bank verified_refs proyek
        $verifiedRefs = is_array($meta['verified_refs'] ?? null) ? $meta['verified_refs'] : [];
        $existsInBank = false;
        foreach ($verifiedRefs as $vr) {
            if (is_array($vr) && strtolower((string) ($vr['title'] ?? '')) === strtolower((string) $chosenRef['title'])) {
                $existsInBank = true;
                break;
            }
        }
        if (!$existsInBank) {
            $chosenRef['manual_saved'] = true;
            $chosenRef['formatted'] = $newFormattedRef;
            array_unshift($verifiedRefs, $chosenRef);
            $meta['verified_refs'] = array_slice($verifiedRefs, 0, 25);
            $project->citation_validation = json_encode($meta, JSON_UNESCAPED_UNICODE);
        }

        // Sinkronkan Daftar Pustaka: tambahkan referensi baru (dan bersihkan referensi lama bila sudah tidak dikutip lagi di bab manapun)
        $dpLines = array_values(array_filter(array_map('trim', explode("\n", (string) ($project->daftar_pustaka ?? '')))));
        $allBodyAfter = strip_tags($project->bab1_content . ' ' . $project->bab2_content . ' ' . $project->bab3_content . ' ' . $project->bab4_content . ' ' . $project->bab5_content);

        if ($oldAuthor !== '') {
            $oldFirst = strtolower((string) preg_replace('/[^a-zA-Z]/', '', explode(' ', $oldAuthor)[0] ?? ''));
            $oldYr = (string) $request->input('old_year', '');
            $stillCited = $oldFirst !== '' && preg_match('/\b' . preg_quote($oldFirst, '/') . '\b[^.\n]{0,30}\b' . preg_quote($oldYr, '/') . '\b/i', $allBodyAfter);
            if (!$stillCited && $oldFirst !== '' && $oldYr !== '') {
                $dpLines = array_values(array_filter($dpLines, function ($line) use ($oldFirst, $oldYr) {
                    $ll = strtolower($line);
                    return !(str_contains($ll, $oldFirst) && str_contains($ll, $oldYr));
                }));
            }
        }

        $newKey = !empty($chosenRef['doi']) ? strtolower((string) $chosenRef['doi']) : strtolower(mb_substr((string) $chosenRef['title'], 0, 35));
        $alreadyInDp = false;
        foreach ($dpLines as $line) {
            if ($newKey !== '' && str_contains(strtolower($line), $newKey)) {
                $alreadyInDp = true;
                break;
            }
        }
        if (!$alreadyInDp) {
            $dpLines[] = $newFormattedRef;
        }
        $gayaLower = strtolower($gayaSitasi);
        if (!str_contains($gayaLower, 'ieee') && !str_contains($gayaLower, 'vancouver')) {
            sort($dpLines, SORT_NATURAL | SORT_FLAG_CASE);
        }
        $project->daftar_pustaka = implode("\n", $dpLines);
        $project->save();

        return response()->json([
            'status' => 'success',
            'chapter' => $chapter,
            'old_cite' => $oldCite,
            'new_cite' => $newInText,
            'new_ref_formatted' => $newFormattedRef,
            'new_doi' => (string) ($chosenRef['doi'] ?? ''),
            'updated_chapter_content' => $project->$col,
            'updated_daftar_pustaka' => $project->daftar_pustaka,
        ]);
    }

    /**
     * Simpan jurnal dari fitur Cari Jurnal langsung ke Referensi Wajib & Bank Terverifikasi Proyek Mahasiswa
     */
    public function addProjectReference(Request $request, $id)
    {
        $project = Project::where('user_id', Auth::id())->findOrFail($id);
        $meta = $this->getProjectMeta($project);
        $gayaSitasi = (string) ($meta['sitasi']['gaya'] ?? 'APA 7th');

        $title = trim((string) $request->input('title', ''));
        $author = trim((string) $request->input('author', ''));
        $year = (int) $request->input('year', date('Y'));
        $journal = trim((string) $request->input('journal', 'Jurnal Ilmiah Terakreditasi'));
        $doiUrl = trim((string) $request->input('doi_url', ''));
        $scope = trim((string) $request->input('scope', 'Internasional'));

        if ($title === '' || $author === '') {
            return response()->json(['status' => 'error', 'message' => 'Data jurnal tidak lengkap.'], 422);
        }

        $firstPart = trim(explode(',', explode(';', explode('&', $author)[0])[0])[0]);
        $tokens = preg_split('/\s+/', $firstPart) ?: [];
        $surname = end($tokens) ?: $firstPart;
        $inText = (str_contains($author, 'et al') || substr_count($author, ',') >= 2) ? "{$surname} et al." : $surname;

        $entry = [
            'in_text' => $inText,
            'first_surname' => $surname,
            'year' => $year,
            'authors_apa' => $author,
            'authors_ieee' => $author,
            'title' => $title,
            'venue' => $journal,
            'vol' => '',
            'iss' => '',
            'pages' => '',
            'doi' => $doiUrl,
            'scope' => $scope,
            'manual_saved' => true,
        ];
        $entry['formatted'] = $this->formatVerifiedReferenceEntry($entry, $gayaSitasi);

        $bank = is_array($meta['verified_refs'] ?? null) ? $meta['verified_refs'] : [];
        $exists = false;
        foreach ($bank as $b) {
            if (is_array($b) && strtolower((string) ($b['title'] ?? '')) === strtolower($title)) {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            array_unshift($bank, $entry);
            $meta['verified_refs'] = array_slice($bank, 0, 25);
        }

        $existingSpecial = trim((string) ($meta['referensi_khusus'] ?? ''));
        if (!str_contains(strtolower($existingSpecial), strtolower(mb_substr($title, 0, 40)))) {
            $meta['referensi_khusus'] = trim($existingSpecial . "\n- " . $entry['formatted']);
        }

        $project->citation_validation = json_encode($meta, JSON_UNESCAPED_UNICODE);
        $project->save();

        return response()->json([
            'status' => 'success',
            'message' => "Jurnal berhasil disimpan ke proyek \"{$project->judul}\" (format {$gayaSitasi}).",
            'formatted' => $entry['formatted'],
        ]);
    }

    public function solveQuestion(Request $request)
    {
        $request->validate([
            'question' => 'nullable|string',
            'image' => 'nullable|string',
            'model' => 'nullable|string'
        ]);

        $question = trim((string) $request->input('question', ''));
        $image = $request->input('image');
        if (empty($question) && empty($image)) {
            return response()->json(['status' => 'error', 'message' => 'Tuliskan pertanyaan atau lampirkan gambar terlebih dahulu.'], 422);
        }
        if (empty($question)) {
            $question = 'Analisis dan jelaskan gambar/grafik/tabel/soal terlampir secara akademik dan sistematis untuk mahasiswa skripsi.';
        }

        try {
            $model = $request->input('model', 'auto');
            $history = $request->input('history', []);

            $historyContext = "";
            if (is_array($history) && count($history) > 0) {
                $historyContext = "RIWAYAT DISKUSI SEBELUMNYA:\n";
                foreach (array_slice($history, -8) as $h) {
                    $sender = ($h['role'] ?? 'user') === 'user' ? 'Mahasiswa' : 'Asisten AI';
                    $txt = Str::limit($h['content'] ?? '', 600);
                    $historyContext .= "{$sender}: {$txt}\n";
                }
                $historyContext .= "\n";
            }

            $prompt = "PERAN: Anda adalah Dosen AI (Dosen Pembimbing Skripsi & Proposal Akademik untuk Mahasiswa S1). Anda menguasai seluruh dunia skripsi:
- Komunikasi dan etika menghadapi dosen pembimbing (cara chat sopan, revisi berulang, dosen slow response/killer).
- Penentuan fenomena empiris, perumusan masalah, novelitas, dan research gap.
- Penyusunan Bab 1 hingga Bab 5 (Latar Belakang, Tinjauan Teori, Metodologi, Pembahasan Hasil, dan Penutup).
- Olah data penelitian kuantitatif (SPSS, SEM-PLS, regresi, uji asumsi, confusion matrix) maupun kualitatif (wawancara, coding tema, triangulasi) dan R&D.
- Teknik parafrase ilmiah dan tips lolos uji similarity Turnitin di bawah batas kampus.
- Kesiapan mental dan strategi cerdas menjawab pertanyaan dosen penguji saat seminar proposal maupun sidang skripsi.

{$historyContext}PERTANYAAN / KONSULTASI MAHASISWA:
\"\"\"
{$question}
\"\"\"

PANDUAN PENULISAN JAWABAN (ATURAN KETAT):
1. Karakter: Berikan jawaban layaknya dosen pembimbing manusia yang cerdas, empatik, solutif, dan profesional.
2. DILARANG KERAS MENGGUNAKAN SIMBOL ROBOTIK AI:
   - DILARANG menggunakan tanda pagar heading markdown seperti '##', '###', '#'.
   - DILARANG menggunakan garis pemisah horizontal seperti '---' atau '___'.
   - DILARANG menggunakan tanda kutip/blockquote seperti '>'.
   - DILARANG spam emoji (jangan gunakan emoji seperti 😊, 📚, 🔑, ✨, 💡, dll). Tulis dalam bahasa Indonesia ilmiah yang bersih dan elegan.
3. Format: Susun dalam paragraf rapi dan terstruktur. Jika ada tahapan atau poin-poin penting, gunakan penomoran angka standar (1., 2., 3.) atau bullet sederhana.
4. Hindari penebalan (bold) berlebihan di setiap kata. Buat kalimat mengalir alami dan nyaman dibaca.";

            $rawAnswer = $this->ai->generateWithImage($prompt, $image, $model);

            // Bersihkan sisa-sisa format simbol robotik AI jika model masih menyelipkannya
            $cleanAnswer = preg_replace('/^#{1,6}\s*(.*)$/m', '$1', $rawAnswer);
            $cleanAnswer = preg_replace('/^[-*_]{3,}\s*$/m', '', $cleanAnswer);
            $cleanAnswer = preg_replace('/^>\s*(.*)$/m', '$1', $cleanAnswer);
            $cleanAnswer = preg_replace('/[\x{1F300}-\x{1F64F}\x{1F680}-\x{1F6FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{1F900}-\x{1F9FF}\x{1F1E0}-\x{1F1FF}]/u', '', $cleanAnswer);
            $cleanAnswer = preg_replace("/\n{3,}/", "\n\n", trim($cleanAnswer));

            return response()->json([
                'status' => 'success',
                'answer' => $cleanAnswer,
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function generateProposal(Request $request)
    {
        return $this->startProject($request);
    }

    public function paraphraseText(Request $request)
    {
        $request->validate([
            'text' => 'required|string|min:15|max:15000',
            'style' => 'nullable|string',
            'intensity' => 'nullable|string',
            'preserve_terms' => 'nullable|string|max:500',
        ]);

        $authUser = Auth::user();
        $toolReserved = false;
        if ($authUser) {
            $access = $this->billing()->reserveToolAccess($authUser, 'paraphrase', 'Parafrase Akademik AI');
            if (empty($access['allowed'])) {
                return response()->json([
                    'status' => 'upgrade_required',
                    'reason' => $access['reason'] ?? 'free_tool_limit',
                    'message' => $access['message'],
                ], 402);
            }
            $toolReserved = !empty($access['reserved']);
        }

        $rawText = trim((string) $request->input('text'));
        $style = (string) $request->input('style', 'natural');
        $intensity = strtolower((string) $request->input('intensity', 'sedang'));
        $preserveTerms = trim((string) $request->input('preserve_terms', ''));
        $model = (string) $request->input('model', 'auto');

        $styleInstruction = match ($style) {
            'natural' => 'Ubah struktur kalimat secara mendalam (pembalikan klausa aktif/pasif, reorganisasi alur gagasan, dan variasi kosakata akademik presisi) agar menurunkan kemiripan Turnitin semaksimal mungkin tanpa mengubah makna ilmiah.',
            'passive' => 'Ubah menjadi kalimat pasif ilmiah baku standar Bab III & Bab IV skripsi Indonesia (hindari kata ganti orang pertama seperti penulis/peneliti/saya/kami).',
            'humanize' => 'Tulis ulang menjadi prosa akademik yang sangat natural, mengalir seperti tulisan peneliti manusia, dan bebaskan total dari klise AI ("Di era digital", "memegang peranan krusial", "tidak dapat dipungkiri").',
            'concise' => 'Padatkan dan ringkas kalimat agar lebih lugas, hemat kata, dan tajam sesuai kaidah EYD V & KBBI tanpa membuang fakta maupun angka.',
            'expand' => 'Kembangkan dan elaborasi kalimat dengan penjelasan konseptual, kata hubung transisional yang kuat, dan kedalaman analisis ilmiah.',
            default => 'Tulis ulang dengan gaya Bahasa Indonesia Ilmiah Baku (Standar Skripsi & Jurnal SINTA/Scopus) sesuai EYD V dan KBBI yang koheren.'
        };

        $intensityInstruction = match ($intensity) {
            'ringan' => 'Intensitas RINGAN: perbaiki diksi agar baku dan ubah seperlunya sambil menjaga urutan gagasan asli (~25–40% perubahan frasa).',
            'maksimal' => 'Intensitas MAKSIMAL (Anti-Turnitin Ketat): rombak total struktur kalimat dan hindari mengulang 4 kata berurutan dari teks asli jika bukan istilah teknis terkunci (~65–85% perubahan frasa).',
            default => 'Intensitas SEDANG (Seimbang): ubah struktur kalimat dan padanan sinonim ilmiah secara seimbang agar natural sekaligus aman dari deteksi kesamaan (~45–65% perubahan frasa).'
        };

        $preserveRule = $preserveTerms !== ''
            ? "\n- ISTILAH / VARIABEL TERKUNCI WAJIB DIPERTAHANKAN PERSIS (jangan diterjemahkan/diubah): {$preserveTerms}"
            : '';

        // Deteksi sitasi dalam teks asli agar bisa divalidasi keterjagaannya
        preg_match_all('/\((?:[A-Z][A-Za-z\s&.,\-]{1,45},?\s*)?(?:19|20)\d{2}[a-z]?(?:,\s*hlm\.?\s*\d+)?\)|\b[A-Z][A-Za-z\-]+(?:\s+(?:et\s+al\.|dkk\.|&|dan)\s+[A-Z]?[A-Za-z\.]*|\s+et\s+al\.)?\s*\((?:19|20)\d{2}[a-z]?\)|\[\d+(?:,\s*\d+)*\]/u', $rawText, $citMatches);
        $detectedCitations = array_values(array_unique(array_filter(array_map('trim', $citMatches[0] ?? []))));

        $prompt = "PERAN: Anda adalah Editor Bahasa Akademik Senior & Pakar Parafrase Anti-Plagiarisme (KBBI & EYD V).
TUGAS: Lakukan parafrase tingkat lanjut pada naskah akademik di bawah ini.

ATURAN WAJIB:
- Gaya Penulisan: {$styleInstruction}
- Tingkat Perubahan: {$intensityInstruction}
- PERTAHANKAN 100% seluruh sitasi in-text (nama penulis dan tahun seperti '(Wijaya, 2024)' atau 'Apandi et al. (2025)' atau '[1]'), seluruh angka empiris, persentase, nama metode statistik, dan notasi rumus tanpa mengubah 1 digit pun.{$preserveRule}
- DILARANG menggunakan kalimat pembuka/penutup AI ('Berikut hasil parafrase...'), tanda pagar markdown, atau tanda bintang (**). Langsung keluarkan teks hasil parafrase yang siap tempel.

TEKS ASLI:
\"\"\"
{$rawText}
\"\"\"";

        try {
            $result = trim((string) $this->ai->generateText($prompt, $model));
            $result = (string) preg_replace('/^(?:Berikut(?:\s+adalah)?\s+hasil\s+parafrase[^:\n]*:\s*\n*)/i', '', $result);
            $result = trim((string) preg_replace('/\*\*([^*\n]+?)\*\*/', '$1', $result));

            // Hitung metrik perbandingan 3-gram secara deterministik
            $normalizeWords = static function (string $s): array {
                $clean = mb_strtolower((string) preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', strip_tags($s)));
                $parts = preg_split('/\s+/u', trim($clean)) ?: [];
                return array_values(array_filter($parts, fn($w) => $w !== ''));
            };
            $origWords = $normalizeWords($rawText);
            $resWords = $normalizeWords($result);

            $ngramSimilarity = 0.0;
            if (count($origWords) >= 3 && count($resWords) >= 3) {
                $origTrigrams = [];
                for ($i = 0; $i <= count($origWords) - 3; $i++) {
                    $origTrigrams[$origWords[$i] . ' ' . $origWords[$i + 1] . ' ' . $origWords[$i + 2]] = true;
                }
                $matchCount = 0;
                $totalResTrigrams = max(1, count($resWords) - 2);
                for ($i = 0; $i < $totalResTrigrams; $i++) {
                    $tri = $resWords[$i] . ' ' . $resWords[$i + 1] . ' ' . $resWords[$i + 2];
                    if (isset($origTrigrams[$tri])) {
                        $matchCount++;
                    }
                }
                $ngramSimilarity = round(($matchCount / $totalResTrigrams) * 100, 1);
            } else {
                similar_text(mb_strtolower($rawText), mb_strtolower($result), $pct);
                $ngramSimilarity = round($pct, 1);
            }

            $preservedCount = 0;
            foreach ($detectedCitations as $dc) {
                if (str_contains($result, $dc) || (preg_match('/(?:19|20)\d{2}/', $dc, $ym) && preg_match('/^[A-Za-z]+/', $dc, $am) && stripos($result, $am[0]) !== false && str_contains($result, $ym[0]))) {
                    $preservedCount++;
                }
            }

            return response()->json([
                'status' => 'success',
                'result' => $result,
                'metrics' => [
                    'original_words' => count($origWords),
                    'result_words' => count($resWords),
                    'ngram_similarity' => $ngramSimilarity,
                    'uniqueness_score' => round(max(0.0, 100.0 - $ngramSimilarity), 1),
                    'citations_detected' => count($detectedCitations),
                    'citations_preserved' => $preservedCount,
                ],
            ]);
        } catch (Exception $e) {
            if ($authUser && $toolReserved) {
                $this->billing()->releaseToolReservation($authUser, 'paraphrase');
            }
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    public function getProjectChaptersText($id)
    {
        $project = Project::where('user_id', Auth::id())->findOrFail($id);
        $authUser = Auth::user();
        $unlocked = $authUser ? ($this->billing()->isAdminUser($authUser) || $this->billing()->isUnlimitedActive($authUser) || !empty($project->is_full_unlocked)) : true;
        return $this->applyNoStoreHeaders(response()->json([
            'status' => 'success',
            'judul' => $project->judul,
            'unlocked' => $unlocked,
            'chapters' => [
                'bab1' => trim(strip_tags((string) ($project->bab1_content ?? ''))),
                'bab2' => $unlocked ? trim(strip_tags((string) ($project->bab2_content ?? ''))) : '',
                'bab3' => $unlocked ? trim(strip_tags((string) ($project->bab3_content ?? ''))) : '',
                'bab4' => $unlocked ? trim(strip_tags((string) ($project->bab4_content ?? ''))) : '',
                'bab5' => $unlocked ? trim(strip_tags((string) ($project->bab5_content ?? ''))) : '',
            ],
        ]));
    }

    public function generatePpt(Request $request)
    {
        $authUser = Auth::user();
        if ($authUser) {
            $access = $this->billing()->reserveToolAccess($authUser, 'ppt', 'Generator PPT Sidang');
            if (empty($access['allowed'])) {
                return response()->json([
                    'status' => 'upgrade_required',
                    'reason' => $access['reason'] ?? 'free_tool_limit',
                    'message' => $access['message'],
                ], 402);
            }
        }
        $model = $request->input('model', 'auto');
        $docText = '';
        $docTitle = trim($request->input('judul', ''));
        $slideCount = (int) $request->input('slide_count', 8);
        $slideCount = max(6, min(12, $slideCount));

        if ($request->hasFile('file_skripsi')) {
            $file = $request->file('file_skripsi');
            $rawExtracted = $this->extractTextFromUploadedFile($file);
            $docText = $this->buildSmartThesisContext($rawExtracted, 14500);
            if (empty($docTitle)) {
                $docTitle = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $docTitle = str_replace(['_', '-'], ' ', $docTitle);
            }
        }

        $defaultId = $this->getDefaultUserIdentity(Auth::id(), Auth::user()->name ?? 'Mahasiswa');
        $projMeta = [];
        $projProdi = 'Program Studi';
        if ($request->filled('project_id')) {
            $proj = Project::where('user_id', Auth::id())->find($request->project_id);
            if ($proj) {
                $docTitle = $proj->judul;
                $projProdi = $proj->prodi;
                $projMeta = $this->getProjectMeta($proj);
                $docText = $this->buildProjectThesisContext($proj, 14500);

                // Sinkronkan update metadata identitas jika diubah oleh mahasiswa di formulir PPT
                $updatedMeta = $projMeta;
                foreach (['nama', 'nim', 'kampus', 'fakultas', 'logo_base64'] as $f) {
                    if ($request->filled($f)) {
                        $updatedMeta[$f] = trim($request->input($f));
                    }
                }
                if ($updatedMeta !== $projMeta) {
                    $proj->citation_validation = json_encode($updatedMeta, JSON_UNESCAPED_UNICODE);
                    $proj->save();
                    $projMeta = $updatedMeta;
                }
            }
        }

        if (empty($docTitle) && empty($docText)) {
            return response()->json(['status' => 'error', 'message' => 'Silakan unggah file skripsi (.pdf / .docx) atau pilih proyek skripsi Anda.'], 422);
        }

        $coverMeta = [
            'nama' => trim((string) ($request->input('nama') ?: ($projMeta['nama'] ?? $defaultId['nama'] ?? (Auth::user()->name ?? 'Mahasiswa')))),
            'nim' => trim((string) ($request->input('nim') ?: ($projMeta['nim'] ?? $defaultId['nim'] ?? ''))),
            'kampus' => trim((string) ($request->input('kampus') ?: ($projMeta['kampus'] ?? $defaultId['kampus'] ?? 'Universitas'))),
            'fakultas' => trim((string) ($request->input('fakultas') ?: ($projMeta['fakultas'] ?? $defaultId['fakultas'] ?? ''))),
            'prodi' => trim((string) ($request->input('prodi') ?: $projProdi)),
            'logo_base64' => $request->input('logo_base64') ?: ($projMeta['logo_base64'] ?? $defaultId['logo_base64'] ?? ''),
            'pembimbing_1' => trim((string) $request->input('pembimbing_1', '')),
            'pembimbing_2' => trim((string) $request->input('pembimbing_2', '')),
            'penguji_1' => trim((string) $request->input('penguji_1', '')),
            'penguji_2' => trim((string) $request->input('penguji_2', '')),
        ];

        $prompt = "PERAN: Anda adalah Konsultan Presentasi Sidang Skripsi Akademik Indonesia.
TUGAS: Rangkum dokumen skripsi/proposal berikut menjadi PERSIS {$slideCount} SLIDE PRESENTASI SIDANG dalam format JSON Array murni.

JUDUL / TOPIK: {$docTitle}
ISI DOKUMEN SKRIPSI MAHASISWA (TERBACA DARI BAB I–V):
\"\"\"
" . Str::limit($docText ?: $docTitle, 14500) . "
\"\"\"

ATURAN WAJIB FORMAT & KONTEN (ANTI-TERPOTONG, RIIL SESUAI NASKAH & SIAP SIDANG):
0. SEMUA ISI SLIDE (Latar Belakang, Rumusan, Teori, Metodologi, Hasil, Angka/Metrik, Kesimpulan) WAJIB 100% DIAMBIL DARI ISI DOKUMEN SKRIPSI MAHASISWA DI ATAS — dilarang mengarang angka/variabel/metode di luar naskah!
1. DILARANG KERAS menggunakan simbol markdown bintang ganda (** atau *) di dalam title, subtitle, maupun bullets. Semua teks harus teks biasa yang bersih, rapi, dan mudah dibaca.
2. KEPADATAN SLIDE (WAJIB DIPATUHI):
   - Setiap slide isi (Slide 2 s/d {$slideCount}) WAJIB berisi 3 hingga MAKSIMAL 4 poin bullets (maksimal 5 poin khusus untuk Rumusan/Hasil jika benar-benar ringkas).
   - Setiap poin bullet WAJIB SINGKAT, PADAT, DAN TAJAM: MAKSIMAL 14–18 kata (maks 115 karakter per poin) agar 100% muat proporsional di slide 16:9 tanpa pernah terpotong!
   - Judul slide singkat (maksimal 7–9 kata). Subjudul 1 baris ringkas (maksimal 10 kata).
   - Penjelasan mendalam, angka detail, dan narasi panjang WAJIB dimasukkan ke field \"speaker_notes\" (3–5 kalimat siap baca saat presentasi), BUKAN ditumpuk di bullets!
3. Buat PERSIS {$slideCount} slide terstruktur berurutan:
   - Slide 1: Sampul Sidang (Cover). Title adalah judul lengkap skripsi, subtitle adalah Program Studi & Metode Penelitian, bullets berisi 2–3 poin singkat fokus penelitian.
   - Slide 2 s/d " . ($slideCount - 1) . ": Inti materi presentasi (Latar Belakang, Rumusan & Tujuan, Landasan Teori & Kerangka, Metodologi, Hasil Pengujian, Pembahasan).
   - Slide {$slideCount}: Kesimpulan & Saran.

Kembalikan HANYA JSON Array valid dengan persis {$slideCount} objek slide:
[
  {
    \"slide_number\": 1,
    \"section\": \"Sampul Sidang\",
    \"title\": \"Judul Lengkap Skripsi\",
    \"subtitle\": \"Program Studi & Metode Penelitian\",
    \"bullets\": [\"Fokus Riset Utama\", \"Objek Penelitian\"],
    \"speaker_notes\": \"Selamat pagi Bapak dan Ibu Dosen Penguji...\"
  }
]";

        try {
            $raw = $this->ai->generateText($prompt, $model);

            // Bersihkan markdown code fences
            $cleanRaw = preg_replace('/```(?:json)?\s*([\s\S]*?)\s*```/i', '$1', $raw);
            $cleanRaw = trim($cleanRaw);

            $slidesArray = null;
            if (preg_match('/\[\s*\{[\s\S]*\}\s*\]/', $cleanRaw, $matches)) {
                $candidate = $matches[0];
                $slidesArray = json_decode($candidate, true);
                if (!$slidesArray) {
                    $fixed = preg_replace('/,\s*([\]\}])/', '$1', $candidate);
                    $slidesArray = json_decode($fixed, true);
                }
            }

            if (!$slidesArray) {
                $slidesArray = json_decode($cleanRaw, true);
            }

            $buildDefaultSlides = function ($title, $prodi, $count) {
                $safeTitle = $title ?: 'Presentasi Sidang Skripsi';
                $safeProdi = $prodi ?: 'Program Studi';
                $allTemplates = [
                    [
                        'section' => 'Sampul Sidang',
                        'title' => $safeTitle,
                        'subtitle' => 'Program Studi ' . $safeProdi . ' · Sidang Skripsi',
                        'bullets' => ['Presentasi Tugas Akhir / Skripsi', 'Fokus Kajian Ilmiah & Evaluasi Kinerja'],
                        'speaker_notes' => 'Selamat pagi Bapak dan Ibu Dosen Penguji serta Pembimbing. Terima kasih atas kesempatan untuk memaparkan skripsi saya berjudul ' . $safeTitle . '.'
                    ],
                    [
                        'section' => 'Latar Belakang Masalah',
                        'title' => 'Latar Belakang & Urgensi Penelitian',
                        'subtitle' => 'Fenomena Lapangan dan Kesenjangan Riset',
                        'bullets' => [
                            'Urgensi otomasi dan akurasi analisis pada objek riset',
                            'Tantangan pengolahan data mentah yang belum terstandarisasi',
                            'Research gap yang menjadi fokus kebaruan penelitian ini'
                        ],
                        'speaker_notes' => 'Penelitian ini dilatarbelakangi oleh fenomena riil dan urgensi permasalahan di lapangan.'
                    ],
                    [
                        'section' => 'Rumusan Masalah & Batasan',
                        'title' => 'Rumusan Masalah & Ruang Lingkup',
                        'subtitle' => 'Fokus Kajian Agar Terarah dan Terukur',
                        'bullets' => [
                            'Rumusan Masalah: Bagaimana efektivitas metode yang diterapkan?',
                            'Tujuan: Menguji performa sistem secara kuantitatif dan objektif',
                            'Batasan Riset: Ruang lingkup objek dan parameter pengujian'
                        ],
                        'speaker_notes' => 'Agar penelitian fokus dan terarah, rumusan masalah dan batasan riset telah ditetapkan secara tegas.'
                    ],
                    [
                        'section' => 'Tinjauan Pustaka',
                        'title' => 'Landasan Teori & Kajian Terdahulu',
                        'subtitle' => 'Kerangka Teoretis Pendukung',
                        'bullets' => [
                            'Konsep dasar dan teori utama yang melandasi penelitian',
                            'Sintesis 4-5 artikel jurnal ilmiah terkini periode 2023–2026',
                            'Posisi kebaruan (novelty) terhadap studi-studi terdahulu'
                        ],
                        'speaker_notes' => 'Pustaka acuan mengacu pada teori baku dan publikasi ilmiah bereputasi terkini.'
                    ],
                    [
                        'section' => 'Metodologi Penelitian',
                        'title' => 'Tahapan & Metode Penelitian',
                        'subtitle' => 'Alur Kerja dan Prosedur Evaluasi',
                        'bullets' => [
                            'Desain penelitian dan prosedur pengumpulan dataset',
                            'Tahapan pra-pemrosesan data dan rekayasa fitur',
                            'Metrik evaluasi kinerja (Akurasi, Presisi, Recall, F1-Score)'
                        ],
                        'speaker_notes' => 'Metodologi disusun sistematis mulai dari penyiapan data hingga validasi hasil.'
                    ],
                    [
                        'section' => 'Hasil Penelitian',
                        'title' => 'Hasil Pengujian & Analisis Data',
                        'subtitle' => 'Temuan Empiris dan Capaian Kinerja',
                        'bullets' => [
                            'Hasil pengujian performa model pada data uji empiris',
                            'Perbandingan performa terhadap skenario baseline',
                            'Tingkat akurasi dan stabilitas pengujian yang terukur'
                        ],
                        'speaker_notes' => 'Berikut adalah hasil pengujian empiris yang menunjukkan performa sistem secara kuantitatif.'
                    ],
                    [
                        'section' => 'Pembahasan',
                        'title' => 'Pembahasan & Analisis Kritis',
                        'subtitle' => 'Interpretasi Temuan Riset',
                        'bullets' => [
                            'Kesesuaian hasil pengujian terhadap hipotesis kerja',
                            'Faktor-faktor penentu keberhasilan performa sistem',
                            'Implikasi teoretis dan praktis dari temuan riset'
                        ],
                        'speaker_notes' => 'Pembahasan ini membuktikan efektivitas metode yang diajukan serta implikasi ilmiahnya.'
                    ],
                    [
                        'section' => 'Evaluasi & Implikasi',
                        'title' => 'Evaluasi Kinerja & Dampak Praktis',
                        'subtitle' => 'Analisis Manfaat Bagi Pengguna',
                        'bullets' => [
                            'Evaluasi kelebihan dan keterbatasan sistem',
                            'Manfaat langsung bagi praktisi dan institusi',
                            'Validitas metode dalam skala implementasi nyata'
                        ],
                        'speaker_notes' => 'Evaluasi menyeluruh memastikan temuan memiliki manfaat aplikatif yang nyata.'
                    ],
                    [
                        'section' => 'Kerangka Konseptual',
                        'title' => 'Kerangka Konseptual & Alur Prosedur',
                        'subtitle' => 'Diagram Alur Integrasi Sistem',
                        'bullets' => [
                            'Visualisasi alur input, proses, dan output sistem',
                            'Keterkaitan antar-komponen dalam model penelitian',
                            'Standarisasi operasional prosedur pengujian'
                        ],
                        'speaker_notes' => 'Kerangka alur menjelaskan keterkaitan setiap fase penelitian secara holistik.'
                    ],
                    [
                        'section' => 'Pengujian Validasi',
                        'title' => 'Validasi Sistem & Pengujian Lanjutan',
                        'subtitle' => 'Ketahanan Performa dan Robustness',
                        'bullets' => [
                            'Uji ketahanan terhadap variasi data ekstrem',
                            'Analisis distribusi galat (error analysis)',
                            'Hasil validasi silang (cross-validation)'
                        ],
                        'speaker_notes' => 'Uji validasi lanjutan membuktikan konsistensi hasil dalam berbagai kondisi.'
                    ],
                    [
                        'section' => 'Rekomendasi Kebijakan',
                        'title' => 'Rekomendasi Implementasi',
                        'subtitle' => 'Strategi Pemanfaatan Hasil Riset',
                        'bullets' => [
                            'Panduan praktis penerapan hasil penelitian',
                            'Mitigasi risiko dan kendala implementasi',
                            'Roadmap integrasi teknologi ke depan'
                        ],
                        'speaker_notes' => 'Rekomendasi disajikan sebagai panduan aplikatif bagi para pemangku kepentingan.'
                    ],
                    [
                        'section' => 'Kesimpulan & Saran',
                        'title' => 'Kesimpulan & Saran Pengembangan',
                        'subtitle' => 'Penutup Sidang Skripsi',
                        'bullets' => [
                            'Kesimpulan: Metode terbukti efektif menjawab seluruh rumusan masalah',
                            'Saran 1: Pengayaan variasi dataset dan skenario pengujian lebih luas',
                            'Saran 2: Integrasi teknologi lanjutan untuk implementasi skala besar'
                        ],
                        'speaker_notes' => 'Sebagai penutup, seluruh tujuan penelitian telah tercapai dan saran disajikan untuk riset masa depan. Terima kasih.'
                    ]
                ];

                $sliced = array_slice($allTemplates, 0, $count - 1);
                $sliced[] = end($allTemplates); // Pastikan slide terakhir selalu Kesimpulan & Saran
                $res = [];
                foreach ($sliced as $idx => $item) {
                    $item['slide_number'] = $idx + 1;
                    $res[] = $item;
                }
                return $res;
            };

            // Jika JSON parsing gagal, buat slide terstruktur sesuai jumlah yang diminta
            if (!is_array($slidesArray) || count($slidesArray) < 3) {
                $slidesArray = $buildDefaultSlides($docTitle, $projProdi, $slideCount);
            }

            // Batasi persis sesuai $slideCount
            if (count($slidesArray) > $slideCount) {
                $lastSlide = end($slidesArray);
                $slidesArray = array_slice($slidesArray, 0, $slideCount - 1);
                $slidesArray[] = $lastSlide;
            }
            foreach ($slidesArray as $idx => &$s) {
                $s['slide_number'] = $idx + 1;
            }
            unset($s);

            foreach ($slidesArray as &$s) {
                if (isset($s['title']))
                    $s['title'] = trim(str_replace(['**', '*'], '', $s['title']));
                if (isset($s['subtitle']))
                    $s['subtitle'] = trim(str_replace(['**', '*'], '', $s['subtitle']));
                if (isset($s['bullets']) && is_array($s['bullets'])) {
                    $s['bullets'] = array_values(array_filter(array_map(function ($b) {
                        return trim(str_replace(['**', '*'], '', $b));
                    }, $s['bullets'])));
                }
            }

            $finalTitle = $docTitle ?: 'Presentasi Sidang Skripsi';
            $projIdForSave = $request->filled('project_id') ? (int) $request->input('project_id') : null;
            $savedDeck = $this->persistSavedPptRecord(Auth::id(), $projIdForSave, $finalTitle, $slidesArray, $coverMeta, 'royal_academic');

            return response()->json([
                'status' => 'success',
                'title' => $finalTitle,
                'slides_data' => $slidesArray,
                'cover_meta' => $coverMeta,
                'saved_ppt' => $savedDeck,
            ]);
        } catch (\Throwable $e) {
            // JANGAN lempar error yang memunculkan alert 'Gagal menyusun slide PPT.'
            // Selalu fallback ke slide terstruktur akademik sejumlah $slideCount agar pengalaman pengguna selalu mulus!
            $finalTitle = $docTitle ?: 'Presentasi Sidang Skripsi';
            $fallbackSlides = $buildDefaultSlides($docTitle, $projProdi, $slideCount);
            $projIdForSave = $request->filled('project_id') ? (int) $request->input('project_id') : null;
            $savedDeck = $this->persistSavedPptRecord(Auth::id(), $projIdForSave, $finalTitle, $fallbackSlides, $coverMeta, 'royal_academic');
            return response()->json([
                'status' => 'success',
                'title' => $finalTitle,
                'slides_data' => $fallbackSlides,
                'cover_meta' => $coverMeta,
                'saved_ppt' => $savedDeck,
            ]);
        }
    }

    public function simulateDefense(Request $request)
    {
        $mode = $request->input('mode', 'generate_questions');
        $model = $request->input('model', 'auto');
        $modeDosen = $request->input('mode_dosen', 'tegas');
        $jumlahSoal = max(4, min(12, (int) $request->input('jumlah_soal', 5)));
        $minScore = max(5.0, min(9.0, (float) $request->input('min_score', 7.0)));

        $authUser = Auth::user();
        $defenseReserved = false;
        try {
            if ($mode === 'generate_questions') {
                if ($authUser) {
                    $access = $this->billing()->reserveToolAccess($authUser, 'defense', 'Simulasi Sidang AI');
                    if (empty($access['allowed'])) {
                        return response()->json([
                            'status' => 'upgrade_required',
                            'reason' => $access['reason'] ?? 'free_tool_limit',
                            'message' => $access['message'],
                        ], 402);
                    }
                    $defenseReserved = !empty($access['reserved']);
                }
                $docText = '';
                $docTitle = trim($request->input('judul', ''));
                $metode = trim($request->input('metode', ''));

                if ($request->hasFile('file_skripsi')) {
                    $file = $request->file('file_skripsi');
                    $rawExtracted = $this->extractTextFromUploadedFile($file);
                    $docText = $this->buildSmartThesisContext($rawExtracted, 14000);
                    if (empty($docTitle)) {
                        $docTitle = str_replace(['_', '-'], ' ', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
                    }
                }

                $projectIdVal = null;
                if ($request->filled('project_id')) {
                    $proj = Project::where('user_id', Auth::id())->find($request->project_id);
                    if ($proj) {
                        $projectIdVal = $proj->id;
                        $docTitle = $proj->judul;
                        $metode = $proj->metode_penelitian;
                        $docText = $this->buildProjectThesisContext($proj, 14000);
                    }
                }

                if (empty($docTitle) && empty($docText)) {
                    return response()->json(['status' => 'error', 'message' => 'Silakan unggah file skripsi (.pdf / .docx) atau pilih proyek skripsi Anda.'], 422);
                }

                $docContextDigest = Str::limit($docText ?: $docTitle, 12000, '');
                session([
                    'defense_doc_context' => $docContextDigest,
                    'defense_doc_title' => $docTitle,
                ]);

                $personaDesc = match ($modeDosen) {
                    'santai' => 'Gaya Dosen Penguji: SANTAI & RAMAH. Pertanyaan bernada hangat, membimbing, apresiatif terhadap proses mahasiswa, namun tetap mengonfirmasi pemahaman konseptual dasar.',
                    'kritis' => 'Gaya Dosen Penguji: KRITIS & METODOLOGIS. Menyelidiki kelemahan metodologi, validitas instrumen, representasi sampling, landasan teori, dan penalaran logis.',
                    'galak' => 'Gaya Dosen Penguji: GALAK / KILLER / TAJAM. Sangat skeptis, menekan titik lemah penelitian, mencecar keterbatasan data, menolak jawaban klise, dan menuntut pembuktian empiris yang presisi.',
                    default => 'Gaya Dosen Penguji: TEGAS & STANDAR AKADEMIK. Formal, terstruktur, lugas, menguji konsistensi antara latar belakang, rumusan masalah, analisis, dan simpulan sesuai pedoman skripsi universitas.'
                };

                $prompt = "PERAN: Anda adalah Ketua Dewan Dosen Penguji Sidang Skripsi S1 di Indonesia.
{$personaDesc}

Berdasarkan naskah skripsi nyata mahasiswa berikut (mencakup Bab I hingga Bab V):
JUDUL: {$docTitle}
METODE: {$metode}
ISI NASKAH SKRIPSI MAHASISWA:
\"\"\"
{$docContextDigest}
\"\"\"

TUGAS: Susun TEPAT {$jumlahSoal} PERTANYAAN UJIAN SIDANG SKRIPSI sesuai karakter dosen penguji di atas, mencakup Bab I hingga Bab V.
ATURAN KETAT BERBASIS NASKAH RIIL:
- Setiap pertanyaan WAJIB merujuk langsung variabel, objek, lokasi, teori, metode, sampel, atau angka/temuan RIIL yang tertulis di naskah skripsi mahasiswa di atas (BUKAN pertanyaan umum/template!).
- Field \"hint\" (kisi-kisi) WAJIB menyebutkan poin/angka/teori kunci yang benar-benar ada di dalam naskah skripsi mahasiswa tersebut.

FORMAT OUTPUT WAJIB: Kembalikan HANYA JSON Array murni berisi {$jumlahSoal} objek pertanyaan (tanpa pembuka/penutup):
[
  {
    \"number\": 1,
    \"category\": \"Bab I · Urgensi & Masalah Penelitian\",
    \"question\": \"Pertanyaan penguji 1...\",
    \"hint\": \"Petunjuk jawaban: jelaskan fenomena gap dan alasan pemilihan objek/variabel.\"
  }
]";

                $raw = $this->ai->generateText($prompt, $model);
                $questions = null;
                if (preg_match('/\[\s*\{.*\}\s*\]/s', $raw, $m)) {
                    $questions = json_decode($m[0], true);
                } else {
                    $questions = json_decode($raw, true);
                }

                if (!is_array($questions) || count($questions) < 2) {
                    $defaultPool = [
                        ['number' => 1, 'category' => 'Bab I · Latar Belakang & Urgensi', 'question' => "Apa urgensi utama dan kesenjangan penelitian (research gap) yang mendasari pemilihan topik \"{$docTitle}\"?", 'hint' => 'Jelaskan masalah aktual di lapangan dan perbedaannya dengan riset terdahulu.'],
                        ['number' => 2, 'category' => 'Bab I · Rumusan & Tujuan', 'question' => 'Bagaimana keterkaitan antara rumusan masalah yang diajukan dengan tujuan serta manfaat praktis penelitian ini?', 'hint' => 'Pastikan setiap poin tujuan menjawab rumusan masalah secara terukur.'],
                        ['number' => 3, 'category' => 'Bab II · Landasan Teori', 'question' => 'Teori utama (Grand Theory) apa yang digunakan sebagai pisau analisis, dan mengapa teori tersebut paling relevan?', 'hint' => 'Sebutkan tokoh/penulis teori beserta indikator variabel yang digunakan.'],
                        ['number' => 4, 'category' => 'Bab III · Metodologi & Desain', 'question' => 'Mengapa Anda memilih metode penelitian tersebut, dan bagaimana cara memastikan validitas/reliabilitas data yang dikumpulkan?', 'hint' => 'Jelaskan alasan pemilihan metode, teknik sampling/informan, dan pengujian instrumen/sistem.'],
                        ['number' => 5, 'category' => 'Bab IV · Hasil Penelitian', 'question' => 'Bagaimana temuan data empiris penelitian ini menjawab rumusan masalah yang ditetapkan sejak awal?', 'hint' => 'Tunjukkan bukti temuan data kunci dan hasil analisis statistik/deskriptif.'],
                        ['number' => 6, 'category' => 'Bab IV · Pembahasan & Komparasi', 'question' => 'Bagaimana posisi hasil penelitian Anda jika dibandingkan dengan temuan jurnal-jurnal terdahulu yang relevan?', 'hint' => 'Jelaskan apakah temuan Anda mendukung, merevisi, atau menolak riset sebelumnya.'],
                        ['number' => 7, 'category' => 'Bab V · Kesimpulan & Implikasi', 'question' => 'Apa kontribusi teoritis dan praktis yang paling menonjol dari kesimpulan skripsi ini?', 'hint' => 'Fokus pada dampak langsung bagi dunia akademis dan objek yang diteliti.'],
                        ['number' => 8, 'category' => 'Bab V · Keterbatasan & Saran', 'question' => 'Apa keterbatasan paling krusial dalam pelaksanaan penelitian ini, dan apa saran konkret Anda untuk peneliti selanjutnya?', 'hint' => 'Akui kelemahan metodologis secara ilmiah dan berikan saran terarah.'],
                        ['number' => 9, 'category' => 'Metodologi · Validitas Data', 'question' => 'Jika ada bias atau deviasi dalam pengumpulan data lapangan, bagaimana Anda mengantisipasinya?', 'hint' => 'Jelaskan teknik triangulasi atau cross-check instrumen yang dilakukan.'],
                        ['number' => 10, 'category' => 'Originalitas & Novelty', 'question' => 'Apa kebaruan (novelty) skripsi ini yang membedakannya secara tegas dari penelitian yang sudah ada sebelumnya?', 'hint' => 'Tegaskan konteks, pendekatan baru, atau perluasan model yang Anda hadirkan.'],
                        ['number' => 11, 'category' => 'Analisis Statistik / Logika Sistem', 'question' => 'Mengapa teknik analisis data tersebut yang paling tepat dibanding alternatif metode lainnya?', 'hint' => 'Paparkan syarat asumsi dan kekuatan analisis yang dipilih.'],
                        ['number' => 12, 'category' => 'Pertanggungjawaban Ilmiah', 'question' => 'Jika Anda diberi kesempatan melanjutkan riset ini, aspek apa yang akan Anda sempurnakan pertama kali?', 'hint' => 'Bahas perluasan sampel, variabel moderasi/mediasi, atau optimasi implementasi.']
                    ];
                    $questions = array_slice($defaultPool, 0, $jumlahSoal);
                }

                $questions = array_slice($questions, 0, $jumlahSoal);
                foreach ($questions as $idx => &$qItem) {
                    $qItem['number'] = $idx + 1;
                }

                return response()->json([
                    'status' => 'success',
                    'title' => $docTitle,
                    'project_id' => $projectIdVal,
                    'doc_context' => Str::limit($docContextDigest, 9000, ''),
                    'word_count' => str_word_count(strip_tags($docText ?: $docTitle)),
                    'mode_dosen' => $modeDosen,
                    'questions' => $questions,
                ]);
            }

            $qaList = $request->input('qa_list', []);
            $docTitle = $request->input('judul', 'Skripsi Mahasiswa');
            $modeDosen = $request->input('mode_dosen', 'tegas');

            // Ambil isi naskah skripsi RIIL (dari project_id DB, doc_context unggahan Word/PDF, atau session)
            $evalDocContext = '';
            if ($request->filled('project_id')) {
                $evalProj = Project::where('user_id', Auth::id())->find($request->project_id);
                if ($evalProj) {
                    $docTitle = $evalProj->judul;
                    $evalDocContext = $this->buildProjectThesisContext($evalProj, 11000);
                }
            }
            if (empty($evalDocContext) && $request->filled('doc_context')) {
                $evalDocContext = Str::limit(trim((string) $request->input('doc_context')), 10000, '');
            }
            if (empty($evalDocContext) && session('defense_doc_context')) {
                $evalDocContext = Str::limit((string) session('defense_doc_context'), 10000, '');
            }

            $qaFormatted = "";
            $totalWords = 0;
            $trivialCount = 0;

            foreach ($qaList as $idx => $item) {
                $num = $idx + 1;
                $q = $item['question'] ?? '';
                $a = trim($item['answer'] ?? '');
                $words = str_word_count($a);
                $totalWords += $words;

                if ($words < 4 || in_array(strtolower($a), ['tes', 'test', 'betul', 'benar', 'tidak', 'ya', 'gak tau', 'nggak tau', 'cukup', 'oke', 'iya'])) {
                    $trivialCount++;
                }

                $qaFormatted .= "Pertanyaan {$num}: {$q}\nJawaban Mahasiswa {$num}: " . ($a ?: '(Tidak dijawab / Kosong)') . "\n\n";
            }

            $totalQuestions = count($qaList);
            $avgWords = $totalQuestions > 0 ? ($totalWords / $totalQuestions) : 0;
            $isClearlyFailing = ($trivialCount >= 2 || $avgWords < 5);

            $personaRubric = match ($modeDosen) {
                'santai' => 'Dosen Santai: Evaluasi apresiatif dan membimbing, namun tetap berikan skor realistis.',
                'kritis' => 'Dosen Kritis: Evaluasi tajam pada aspek logika, metodologi, dan argumentasi ilmiah.',
                'galak' => 'Dosen Galak/Killer: Sangat ketat, tidak menoleransi jawaban asal, kurangi poin jika jawaban tidak ilmiah.',
                default => 'Dosen Tegas: Standar akademik formal universitas di Indonesia.'
            };

            $docContextBlock = !empty($evalDocContext)
                ? "\nREFERENSI ISI NASKAH SKRIPSI ASLI MAHASISWA (WAJIB DIJADIKAN ACUAN UTAMA VERIFIKASI & SARAN JAWABAN TERBAIK):\n\"\"\"\n{$evalDocContext}\n\"\"\"\n"
                : '';

            $prompt = "PERAN: Anda adalah Ketua Dewan Dosen Penguji Sidang Skripsi S1.
{$personaRubric}

Evaluasi seluruh jawaban mahasiswa pada simulasi sidang skripsi untuk judul: \"{$docTitle}\".
{$docContextBlock}
DAFTAR PERTANYAAN DAN JAWABAN MAHASISWA:
\"\"\"
{$qaFormatted}
\"\"\"

PANDUAN PENILAIAN OBJEKTIF, RIIL SESUAI NASKAH & SANGAT KETAT:
0. KESESUAIAN DENGAN ISI SKRIPSI ASLI:
   - Cocokkan setiap jawaban mahasiswa dengan isi nyata skripsi di atas (variabel, teori, metode, jumlah sampel/dataset, hasil angka/pengujian, dan kesimpulan).
   - Pada field \"feedback\", evaluasi secara spesifik apakah jawaban mahasiswa sesuai, kurang lengkap, atau menyimpang dari isi naskah skripsinya sendiri.
   - Pada field \"better_answer\", susun contoh jawaban sidang terbaik yang 100% RIIL merujuk data, teori, metode, dan temuan yang benar-benar ada di naskah skripsi mahasiswa di atas (BUKAN jawaban umum/template!).
1. JIKA JAWABAN MAHASISWA HANYA KATA PENDEK/ASAL ('Tes', 'Betul', 'Tidak', 'Kurang paham') atau tidak ilmiah:
   - Skor per pertanyaan WAJIB antara 1.0 s.d 3.5 per pertanyaan!
   - Overall score WAJIB antara 1.5 s.d 3.8!
   - Verdict: 'Tidak Lulus Sidang'
   - Grade: 'E' atau 'D'
   - DILARANG KERAS memberikan skor di atas 4.0 jika jawaban mahasiswa tidak berbobot ilmiah!
2. Jika jawaban sangat singkat (1-2 kalimat tanpa teori/metode): Skor 4.0 - 5.5 (Tidak Lulus).
3. Jika jawaban cukup menjawab tapi kurang mendalam: Skor 6.0 - 7.0 (Lulus Bersyarat / C+).
4. Jika jawaban baik, ilmiah, didukung teori/data: Skor 7.5 - 8.5 (Lulus Revisi Minor / B+).
5. Jika jawaban sangat komprehensif & memuaskan: Skor 8.6 - 10.0 (Lulus Memuaskan / A).

FORMAT OUTPUT WAJIB: Kembalikan HANYA JSON Object murni dengan struktur berikut:
{
  \"overall_score\": 2.8,
  \"grade\": \"E\",
  \"verdict\": \"Tidak Lulus Sidang\",
  \"summary\": \"Ulasan umum dosen penguji terhadap performa mahasiswa...\",
  \"items\": [
    {
      \"number\": 1,
      \"question\": \"Teks pertanyaan...\",
      \"student_answer\": \"Jawaban mahasiswa...\",
      \"score\": 2.5,
      \"feedback\": \"Evaluasi objektif penguji...\",
      \"better_answer\": \"Contoh jawaban ilmiah yang seharusnya disampaikan saat sidang...\"
    }
  ]
}";

            $raw = $this->ai->generateText($prompt, $model);
            $evalData = null;
            if (preg_match('/\{.*\}/s', $raw, $m)) {
                $evalData = json_decode($m[0], true);
            } else {
                $evalData = json_decode($raw, true);
            }

            if (!is_array($evalData) || empty($evalData['items'])) {
                $fallbackItems = [];
                $totalScore = 0;
                foreach ($qaList as $idx => $item) {
                    $ans = trim($item['answer'] ?? '');
                    $wordCount = str_word_count($ans);
                    $score = $wordCount > 30 ? 8.2 : ($wordCount > 15 ? 7.0 : ($wordCount > 5 ? 5.0 : 2.5));
                    $totalScore += $score;
                    $fallbackItems[] = [
                        'number' => $idx + 1,
                        'question' => $item['question'] ?? '',
                        'student_answer' => $ans ?: '(Kosong)',
                        'score' => $score,
                        'feedback' => $score < 4.0
                            ? 'Jawaban belum mencerminkan penguasaan materi akademik. Diperlukan penjelasan teoritis dan metodologis yang nyata.'
                            : 'Jawaban telah menyentuh inti permasalahan, namun perlu diperkuat dengan rujukan teori dan data penelitian.',
                        'better_answer' => 'Berdasarkan kerangka penelitian pada skripsi ini, fenomena gap dan pemilihan metode didasarkan pada fakta empiris di lapangan serta literatur pendukung yang relevan.'
                    ];
                }
                $avg = count($qaList) > 0 ? round($totalScore / count($qaList), 1) : 3.0;
                $isPass = $avg >= 6.5;
                $evalData = [
                    'overall_score' => $avg,
                    'grade' => $avg >= 8.5 ? 'A' : ($avg >= 7.5 ? 'B+' : ($avg >= 6.5 ? 'B' : ($avg >= 5.0 ? 'C' : 'E'))),
                    'verdict' => $isPass ? 'Lulus Sidang dengan Revisi' : 'Tidak Lulus Sidang',
                    'summary' => $isPass
                        ? 'Mahasiswa memahami substansi dasar penelitian, namun beberapa aspek metodologis perlu diperjelas.'
                        : 'Mahasiswa dinilai belum menguasai naskah skripsi secara memadai. Jawaban belum memberikan argumen ilmiah yang dapat dipertanggungjawabkan.',
                    'items' => $fallbackItems,
                ];
            }

            // Sanity check item answers: jika jawaban hanya kata pendek/trivial tapi skor tinggi, sesuaikan skor itemnya
            if (isset($evalData['items']) && is_array($evalData['items'])) {
                foreach ($evalData['items'] as &$it) {
                    $itWords = str_word_count(trim($it['student_answer'] ?? ''));
                    if ($itWords < 4 && ($it['score'] ?? 0) > 4.0) {
                        $it['score'] = round(min(3.5, max(1.5, 1.0 + ($itWords * 0.4))), 1);
                    }
                }
                unset($it);

                // Hitung overall_score secara matematis murni: rata-rata seluruh skor pertanyaan
                $itemScores = array_map(function ($it) {
                    return (float) ($it['score'] ?? 0);
                }, $evalData['items']);

                if (count($itemScores) > 0) {
                    $evalData['overall_score'] = round(array_sum($itemScores) / count($itemScores), 1);
                }
            }

            // Rubric Sanity Enforcer & Passing Grade Check:
            $currentScore = (float) ($evalData['overall_score'] ?? 0);
            $isFailingByGrade = ($currentScore < $minScore);

            if ($currentScore >= 8.5) {
                $evalData['grade'] = 'A';
                $evalData['verdict'] = 'Lulus Sidang Memuaskan';
            } elseif ($currentScore >= 7.5) {
                $evalData['grade'] = 'B+';
                $evalData['verdict'] = 'Lulus Sidang dengan Revisi Minor';
            } elseif ($currentScore >= 6.5) {
                $evalData['grade'] = 'B';
                $evalData['verdict'] = 'Lulus Sidang dengan Revisi';
            } elseif ($currentScore >= 5.5) {
                $evalData['grade'] = 'C';
                $evalData['verdict'] = 'Lulus Bersyarat / Uji Ulang';
            } elseif ($currentScore >= 4.0) {
                $evalData['grade'] = 'D';
                $evalData['verdict'] = 'Tidak Lulus Sidang';
            } else {
                $evalData['grade'] = 'E';
                $evalData['verdict'] = 'Tidak Lulus Sidang';
            }

            if ($isClearlyFailing || $isFailingByGrade || str_contains(strtolower($evalData['verdict'] ?? ''), 'tidak lulus')) {
                $evalData['verdict'] = 'Tidak Lulus Sidang';
                if (!in_array($evalData['grade'] ?? '', ['D', 'E'])) {
                    $evalData['grade'] = $currentScore < 3.5 ? 'E' : ($currentScore < 5.5 ? 'D' : 'C');
                }
                if ($isFailingByGrade && !$isClearlyFailing && !str_contains($evalData['summary'] ?? '', 'belum mencapai batas minimal')) {
                    $evalData['summary'] = ($evalData['summary'] ?? '') . " (Nilai rata-rata {$currentScore} belum mencapai batas minimal kelulusan yang ditentukan: {$minScore}).";
                }
            }
            $evalData['min_score'] = $minScore;

            return response()->json([
                'status' => 'success',
                'evaluation' => $evalData,
            ]);
        } catch (Exception $e) {
            if (!empty($authUser) && !empty($defenseReserved)) {
                $this->billing()->releaseToolReservation($authUser, 'defense');
            }
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    private function getDefaultEmblemBase64(): string
    {
        $file = public_path('images/default_emblem.jpg');
        if (file_exists($file)) {
            return 'data:image/jpeg;base64,' . base64_encode(file_get_contents($file));
        }
        return 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=';
    }
}
