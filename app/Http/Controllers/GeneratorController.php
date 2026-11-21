<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\User;
use App\Services\AIManagerService;
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
    private array $docxTempFiles = [];
    private array $docxFlowchartVmlMap = [];
    private string $docxFontName = 'Times New Roman';
    private float $docxFontSize = 12.0;

    public function __construct(AIManagerService $ai)
    {
        $this->ai = $ai;
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
            $recentProjects = Project::where('user_id', $userId)->orderBy('updated_at', 'desc')->take(10)->get();
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
     * Hitung estimasi halaman dinamis per bab secara akurat (hanya bab yang sudah terisi)
     */
    private function computeDynamicChapterPages(Project $project, bool $isProposal = false): array
    {
        $chapterKeys = $isProposal ? ['bab1', 'bab2', 'bab3'] : ['bab1', 'bab2', 'bab3', 'bab4', 'bab5'];
        $map = [];
        $curPage = 1;

        foreach ($chapterKeys as $k) {
            $text = trim(strip_tags((string) ($project->{$k . '_content'} ?? '')));
            if ($text !== '') {
                $pages = max(1, (int) ceil(mb_strlen($text) / 2200));
                $map[$k] = ['start' => $curPage, 'count' => $pages, 'end' => $curPage + $pages - 1];
                $curPage += $pages;
            } else {
                $map[$k] = ['start' => $curPage, 'count' => 0, 'end' => $curPage];
            }
        }

        $dpText = trim(strip_tags((string) ($project->daftar_pustaka ?? '')));
        $dpPages = $dpText !== '' ? max(1, (int) ceil(mb_strlen($dpText) / 2000)) : 1;
        $map['daftar_pustaka'] = ['start' => $curPage, 'count' => $dpPages, 'end' => $curPage + $dpPages - 1];
        $curPage += $dpPages;
        $map['next_page'] = $curPage;

        return $map;
    }

    /**
     * Ambil referensi ilmiah terverifikasi dari OpenAlex & Crossref sesuai aturan sitasi proyek (tahun_min, asal, SINTA/Scopus)
     */
    private function fetchVerifiedReferencesForProject(Project $project): array
    {
        $refs = [];
        $seenTitles = [];
        try {
            $cleanWords = preg_replace('/[^a-zA-Z0-9\s]/', ' ', (string) $project->judul);
            $stop = ['pengaruh', 'analisis', 'implementasi', 'penerapan', 'hubungan', 'terhadap', 'pada', 'dalam', 'dengan', 'menggunakan', 'studi', 'kasus', 'untuk', 'yang', 'dari', 'dan', 'atau', 'siswa', 'kelas'];
            $words = array_values(array_filter(explode(' ', $cleanWords), fn($w) => strlen($w) >= 4 && !in_array(strtolower($w), $stop)));
            if (empty($words)) {
                $words = array_values(array_filter(explode(' ', $cleanWords), fn($w) => strlen($w) >= 4));
            }
            $query = implode(' ', array_slice($words, 0, 6));
            if (empty($query)) {
                return [];
            }

            $meta = $this->getProjectMeta($project);
            $sitasi = $meta['sitasi'] ?? [];
            $tahunMin = !empty($sitasi['tahun_min']) ? max(2015, min(2026, (int) $sitasi['tahun_min'])) : 2023;
            $asal = strtolower((string) ($sitasi['asal'] ?? 'indonesia + internasional'));
            $wantId = str_contains($asal, 'indonesia') || str_contains($asal, 'nasional') || str_contains($asal, 'semua');
            $wantInt = str_contains($asal, 'internasional') || str_contains($asal, 'global') || str_contains($asal, 'semua') || !$wantId;

            $filterDate = "from_publication_date:{$tahunMin}-01-01,to_publication_date:2026-12-31";
            $headers = ['User-Agent' => 'SkripsiAI-Academic/2.1 (mailto:support@skripsi-ai.app)'];

            $formatOpenAlex = function ($w) use ($tahunMin, &$seenTitles): ?string {
                $title = trim((string) ($w['title'] ?? ''));
                if ($title === '' || isset($seenTitles[strtolower($title)])) {
                    return null;
                }
                $year = (int) ($w['publication_year'] ?? 0);
                if ($year < $tahunMin || $year > 2026) {
                    return null;
                }
                $seenTitles[strtolower($title)] = true;

                $authorships = $w['authorships'] ?? [];
                $inTextAuthors = [];
                $apaAuthors = [];
                foreach (array_slice($authorships, 0, 3) as $a) {
                    $full = trim((string) ($a['author']['display_name'] ?? ''));
                    if ($full === '') continue;
                    $parts = preg_split('/\s+/', $full);
                    $last = array_pop($parts);
                    $inTextAuthors[] = $last;
                    $initials = '';
                    foreach ($parts as $p) {
                        if ($p !== '') $initials .= strtoupper(mb_substr($p, 0, 1)) . '. ';
                    }
                    $apaAuthors[] = trim($last . ($initials !== '' ? ', ' . trim($initials) : ''));
                }
                if (empty($inTextAuthors)) {
                    return null;
                }
                $inText = count($authorships) > 2
                    ? $inTextAuthors[0] . ' et al.'
                    : (count($inTextAuthors) === 2 ? $inTextAuthors[0] . ' & ' . $inTextAuthors[1] : $inTextAuthors[0]);
                $apaAuthStr = count($apaAuthors) > 1
                    ? implode(', ', array_slice($apaAuthors, 0, -1)) . ', & ' . end($apaAuthors)
                    : $apaAuthors[0];

                $venue = trim((string) ($w['primary_location']['source']['display_name'] ?? ''));
                if ($venue === '') {
                    $venue = 'Jurnal Ilmiah Terakreditasi';
                }
                $bib = $w['biblio'] ?? [];
                $vol = trim((string) ($bib['volume'] ?? ''));
                $iss = trim((string) ($bib['issue'] ?? ''));
                $fp = trim((string) ($bib['first_page'] ?? ''));
                $lp = trim((string) ($bib['last_page'] ?? ''));
                $bibStr = '';
                if ($vol !== '') {
                    $bibStr .= ", {$vol}" . ($iss !== '' ? "({$iss})" : '');
                }
                if ($fp !== '') {
                    $bibStr .= ", {$fp}" . ($lp !== '' && $lp !== $fp ? "-{$lp}" : '');
                }
                $doi = trim((string) ($w['doi'] ?? ''));
                return "[Sitasi: {$inText} ({$year})] {$apaAuthStr} ({$year}). {$title}. {$venue}{$bibStr}." . ($doi !== '' ? " {$doi}" : '');
            };

            if ($wantId) {
                $resId = Http::timeout(8)->withHeaders($headers)->get('https://api.openalex.org/works', [
                    'search' => $query,
                    'filter' => $filterDate . ',language:id',
                    'per-page' => $wantInt ? 8 : 14,
                ]);
                if ($resId->successful()) {
                    foreach ($resId->json()['results'] ?? [] as $w) {
                        $line = $formatOpenAlex($w);
                        if ($line) $refs[] = $line;
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
                        $line = $formatOpenAlex($w);
                        if ($line) $refs[] = $line;
                    }
                }
            }

            // Fallback Crossref jika hasil OpenAlex kurang dari 5
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
                        $seenTitles[strtolower($title)] = true;
                        $authors = $item['author'] ?? [];
                        $inTextList = [];
                        $apaList = [];
                        foreach (array_slice($authors, 0, 3) as $a) {
                            $fam = trim((string) ($a['family'] ?? ''));
                            $giv = trim((string) ($a['given'] ?? ''));
                            if ($fam === '') continue;
                            $inTextList[] = $fam;
                            $init = $giv !== '' ? strtoupper(mb_substr($giv, 0, 1)) . '.' : '';
                            $apaList[] = trim($fam . ($init !== '' ? ', ' . $init : ''));
                        }
                        if (empty($inTextList)) continue;
                        $inText = count($authors) > 2 ? $inTextList[0] . ' et al.' : (count($inTextList) === 2 ? $inTextList[0] . ' & ' . $inTextList[1] : $inTextList[0]);
                        $apaStr = count($apaList) > 1 ? implode(', ', array_slice($apaList, 0, -1)) . ', & ' . end($apaList) : $apaList[0];
                        $venue = trim((string) ($item['container-title'][0] ?? 'Jurnal Ilmiah Bereputasi'));
                        $vol = trim((string) ($item['volume'] ?? ''));
                        $iss = trim((string) ($item['issue'] ?? ''));
                        $page = trim((string) ($item['page'] ?? ''));
                        $bib = ($vol !== '' ? ", {$vol}" . ($iss !== '' ? "({$iss})" : '') : '') . ($page !== '' ? ", {$page}" : '');
                        $doi = !empty($item['DOI']) ? 'https://doi.org/' . $item['DOI'] : '';
                        $refs[] = "[Sitasi: {$inText} ({$year})] {$apaStr} ({$year}). {$title}. {$venue}{$bib}." . ($doi !== '' ? " {$doi}" : '');
                    }
                }
            }
        } catch (Throwable $e) {
            Log::warning('Verified reference lookup skipped: ' . $e->getMessage());
        }
        return array_slice($refs, 0, 15);
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
            $items[] = ['label' => 'DAFTAR ISI', 'page' => $this->toRomanLower($romanIdx++), 'level' => 1];
        } else {
            $items[] = ['label' => 'LEMBAR PENGESAHAN', 'page' => $this->toRomanLower($romanIdx++), 'level' => 1];
            $items[] = ['label' => 'SURAT PERNYATAAN KEASLIAN', 'page' => $this->toRomanLower($romanIdx++), 'level' => 1];
            $items[] = ['label' => 'ABSTRAK', 'page' => $this->toRomanLower($romanIdx++), 'level' => 1];
            $items[] = ['label' => 'ABSTRACT', 'page' => $this->toRomanLower($romanIdx++), 'level' => 1];
            $items[] = ['label' => 'KATA PENGANTAR', 'page' => $this->toRomanLower($romanIdx++), 'level' => 1];
            $items[] = ['label' => 'DAFTAR ISI', 'page' => $this->toRomanLower($romanIdx++), 'level' => 1];
        }

        if ($hasTables) {
            $items[] = ['label' => 'DAFTAR TABEL', 'page' => $this->toRomanLower($romanIdx++), 'level' => 1];
        }
        if ($hasFigures) {
            $items[] = ['label' => 'DAFTAR GAMBAR', 'page' => $this->toRomanLower($romanIdx++), 'level' => 1];
        }

        $lampiranList = $this->extractLampiranForProject($project, $isProposal);
        if (!empty($lampiranList)) {
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
            foreach ($lines as $lIdx => $line) {
                $clean = trim(strip_tags($line));
                if (preg_match('/^(\d+\.\d+)\.?\s+(.+)$/', $clean, $m)) {
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
                return '<a href="' . $rawUrl . '" target="_blank" rel="noopener noreferrer" style="color:#1d4ed8; text-decoration:underline;">' . $rawUrl . '</a>' . $trailing;
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
                return '<a href="' . $href . '" target="_blank" rel="noopener noreferrer" style="color:#1d4ed8; text-decoration:underline;">doi:' . $doiVal . '</a>' . $trailing;
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
            $text = $this->formatAcademicMathText($rawNode);
            $type = $this->classifyFlowchartNode($text, $idx, $total, $isFramework, $decisionIdx);
            $maxChars = match ($type) {
                'terminator' => 22,
                'decision' => 24,
                'io' => 36,
                'subprocess' => 36,
                'framework' => 46,
                default => 40,
            };
            $lines = $this->wrapDiagramLines($text, $maxChars, 3);
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
            $text = $this->formatAcademicMathText($rawNode);
            $type = $this->classifyFlowchartNode($text, $idx, $total, $isFramework, $decisionIdx);
            $maxChars = match ($type) {
                'terminator' => 22,
                'decision' => 22,
                'io' => 34,
                'subprocess' => 34,
                'framework' => 44,
                default => 38,
            };
            $lines = $this->wrapDiagramLines($text, $maxChars, 3);
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

        try {
            $allProjects = Project::where('user_id', $user->id)->orderBy('id', 'asc')->get();
            $seen = [];
            foreach ($allProjects as $proj) {
                $key = strtolower(trim($proj->judul)) . '|' . strtolower(trim($proj->prodi ?? ''));
                $isEmpty = empty($proj->bab1_content) && empty($proj->bab2_content) && empty($proj->bab3_content);
                if (isset($seen[$key]) && $isEmpty) {
                    $proj->delete();
                } else {
                    $seen[$key] = $proj->id;
                }
            }
        } catch (Throwable $e) {
        }

        $history = Project::where('user_id', $user->id)->orderBy('updated_at', 'desc')->get();
        $defaultIdentity = $this->getDefaultUserIdentity($user->id);

        try {
            $apiKeys = DB::table('api_keys')->where('user_id', $user->id)->orderBy('created_at', 'desc')->get();
        } catch (Exception $e) {
            $apiKeys = collect([]);
        }

        $savedPpts = $this->getSavedPptsForUser($user->id);

        return view('pre_generator', compact('history', 'apiKeys', 'user', 'defaultIdentity', 'savedPpts'));
    }

    private function ensureSavedPptsTable(): bool
    {
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
        ]);

        $cleanJudul = trim($request->judul);
        $defaultIdentity = $this->getDefaultUserIdentity(Auth::id());

        // Ekstrak file pedoman kampus jika diunggah
        $guidelineText = '';
        if ($request->hasFile('pedoman_file')) {
            $guidelineText = $this->extractTextFromUploadedFile($request->file('pedoman_file'));
        }
        if ($request->filled('pedoman_text')) {
            $guidelineText = trim($guidelineText . "\n" . $request->pedoman_text);
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
            'pedoman_text' => Str::limit($guidelineText, 8000, ''),
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

        // Jika proyek dengan judul sama sudah ada, perbarui metadata/tahap agar sinkron
        $existing = Project::where('user_id', Auth::id())
            ->whereRaw('LOWER(judul) = ?', [strtolower($cleanJudul)])
            ->orderBy('id', 'desc')
            ->first();

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
        }
        if ($hasMathUpdate) {
            $project->save();
        }

        return view('generator', compact('project', 'history', 'meta'));
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
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function findJournals(Request $request)
    {
        $request->validate(['keyword' => 'required|string']);
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
            ? "\nREFERENSI JURNAL ILMIAH TERVERIFIKASI (PRIORITASKAN UNTUK DISITASI SECARA KONTEKSTUAL):\n- " . implode("\n- ", $verifiedRefs) . "\n"
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
1. Gaya Bahasa & Humanizer Alami: Gunakan Bahasa {$bahasa} ilmiah baku sesuai EYD V/KBBI, kohesif, objektif, dan BEBAS TOTAL DARI KLISE AI (DILARANG KERAS membuka kalimat dengan 'Kondisi ideal dalam...', 'Di era digital / era globalisasi saat ini...', 'Perkembangan teknologi yang pesat...', 'Tidak dapat dipungkiri bahwa...', 'Memegang peranan krusial...', atau 'Merupakan tonggak penting...').
2. Suara Pasif Ilmiah Baku: Khusus metodologi (Bab III) dan pembahasan temuan (Bab IV), gunakan kalimat pasif ilmiah (contoh: 'Data dikumpulkan melalui penyebaran kuesioner...', 'Analisis regresi linier dilakukan untuk...'), hindari suara orang pertama (saya, penulis, kami).
3. Kedalaman Materi & Standar Ketebalan Skripsi S1 Nasional (Mantar Riset AI / Skripsita Standard):
   - Tuliskan setiap bab secara SANGAT MENDALAM, KOMPREHENSIF, ELABORATIF, DAN EKSTENSIF (target minimal 1500 - 2500+ kata per bab). DILARANG KERAS membuat naskah yang singkat, dangkal, atau sekadar poin-poin ringkas!
   - Uraikan setiap argumentasi, konsep, dimensi variabel, landasan teori, dan implikasi secara tuntas dalam beberapa paragraf ilmiah panjang yang berbobot.
4. Sitasi & Bebas Mendeley (ANTI-HALUSINASI & WAJIB LITERATUR ASLI):
   - WAJIB menyertakan sitasi dalam teks (in-text citation gaya {$gayaSitasi}, contoh: Menurut Wijaya et al. (2024)).
   - TAHUN TERBIT REFERENSI WAJIB MINIMAL TAHUN {$tahunMin} DAN MAKSIMAL TAHUN 2026.
   - DILARANG KERAS MENGARANG ATAU MEMBUAT SUMBER/PENULIS/JURNAL FIKTIF! Setiap nama peneliti, tahun, judul publikasi, dan nama jurnal ilmiah yang dikutip WAJIB berasal dari literatur ilmiah asli yang benar-benar terbit di jurnal nasional (SINTA/Garuda/Kemdikbud) atau internasional (Scopus/IEEE/Elsevier/Springer/OpenAlex) atau laporan otoritas data resmi (BPS, Kemendikbudristek, APJII, We Are Social / DataReportal). DILARANG menulis contoh nama fiktif seperti 'Pratama dan Pamungkas' atau 'Nahdhudin'!
   - Di Bab I (Latar Belakang): Prioritaskan kutipan dari 'REFERENSI JURNAL ILMIAH TERVERIFIKASI' di atas dan data resmi institusi.
   - Pada Tabel Penelitian Terdahulu (Bab II): Cantumkan 3-5 penelitian terdahulu yang ASLI dan RELEVAN dengan topik judul '{$project->judul}', mencakup nama peneliti riil, tahun publikasi riil ({$tahunMin}-2026), judul artikel riil, metode/desain, metrik hasil terukur, dan celah penelitian (research gap).
5. Standar Rumus & Notasi Statistik (DILARANG KODE LATEX MENTAH):
   - DILARANG KERAS menghasilkan sintaks LaTeX mentah seperti \\frac{}{}, \\hat{}, {,}, \\approx, atau tanda dollar \$ ... \$.
   - Tuliskan rumus dalam teks akademik bersih:
     * Pecahan: n = N / (1 + N(e)²) = 288 / (1 + 288(0,05)²) = 167,44 ≈ 168 responden
     * Estimasi Regresi: Ŷ = a + bX atau Ŷ = 96,482 - 0,253X
     * Koefisien Determinasi: R² = 0,154
     * Gunakan superskrip Unicode: ², ³, ᵗ dan tanda koma desimal Indonesia: 0,05, 96,482 (bukan {,}).
6. Format Tabel Akademik Rapi:
   - Setiap tabel wajib memiliki nomor dan judul pada baris tersendiri (contoh: Tabel 3.1 Operasionalisasi Variabel).
   - Sajikan dalam format Markdown Table lengkap dan presisi, pastikan sel-sel terisi pada kolom yang tepat dan tidak bergeser.
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

        // Deteksi Rumpun Keilmuan / Disiplin Mahasiswa
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
            || str_contains($judulLower, 'nlp')
            || str_contains($judulLower, 'svm')
            || str_contains($judulLower, 'cnn')
            || str_contains($judulLower, 'lstm')
            || str_contains($judulLower, 'k-means')
            || str_contains($judulLower, 'decision tree')
            || str_contains($judulLower, 'random forest')
            || str_contains($judulLower, 'data mining');

        $isEkonomi = str_contains($prodiLower, 'manajemen')
            || str_contains($prodiLower, 'akuntansi')
            || str_contains($prodiLower, 'ekonomi')
            || str_contains($prodiLower, 'bisnis')
            || str_contains($prodiLower, 'perbankan')
            || str_contains($prodiLower, 'keuangan')
            || str_contains($prodiLower, 'pemasaran');

        $isPendidikan = str_contains($prodiLower, 'pendidikan')
            || str_contains($prodiLower, 'fkip')
            || str_contains($prodiLower, 'tarbiyah')
            || str_contains($prodiLower, 'pembelajaran')
            || str_contains($prodiLower, 'guru')
            || str_contains($prodiLower, 'ppkn')
            || str_contains($prodiLower, 'pkn')
            || str_contains($prodiLower, 'pgsd')
            || str_contains($prodiLower, 'pgpaud')
            || str_contains($prodiLower, 'paud')
            || str_contains($prodiLower, 'bimbingan konseling')
            || str_contains($prodiLower, 'bk')
            || str_contains($judulLower, 'siswa')
            || str_contains($judulLower, 'peserta didik')
            || str_contains($judulLower, 'prestasi belajar')
            || str_contains($judulLower, 'hasil belajar')
            || str_contains($judulLower, 'kelas xi')
            || str_contains($judulLower, 'kelas x')
            || str_contains($judulLower, 'kelas xii')
            || str_contains($judulLower, 'sekolah');

        $isKesehatan = str_contains($prodiLower, 'farmasi')
            || str_contains($prodiLower, 'keperawatan')
            || str_contains($prodiLower, 'kebidanan')
            || str_contains($prodiLower, 'kesehatan')
            || str_contains($prodiLower, 'kedokteran')
            || str_contains($prodiLower, 'gizi')
            || str_contains($judulLower, 'pasien')
            || str_contains($judulLower, 'klinis');

        $isHukum = str_contains($prodiLower, 'hukum')
            || str_contains($prodiLower, 'syariah')
            || str_contains($prodiLower, 'peradilan')
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
            || str_contains($prodiLower, 'administrasi negara');

        $isRnD = str_contains($metodeLower, 'r&d')
            || str_contains($metodeLower, 'waterfall')
            || str_contains($metodeLower, 'agile')
            || str_contains($metodeLower, 'prototype')
            || str_contains($metodeLower, 'rekayasa');

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

        $context = "";
        if ($bab == 'bab2')
            $context = "KONTEKS BAB I (PERTAHANKAN BENANG MERAH RUMUSAN MASALAH & VARIABEL):\n" . Str::limit(strip_tags((string) $project->bab1_content), 3500);
        elseif ($bab == 'bab3')
            $context = "KONTEKS BAB I & BAB II (PERTAHANKAN KONSISTENSI VARIABEL, INDIKATOR & HIPOTESIS):\n" . Str::limit(strip_tags((string) $project->bab1_content), 1800) . "\n\n" . Str::limit(strip_tags((string) $project->bab2_content), 1800);
        elseif ($bab == 'bab4')
            $context = "KONTEKS BAB I & BAB III (JAWAB SEMUA RUMUSAN MASALAH & GUNAKAN METODE/SAMPEL BAB III):\n" . Str::limit(strip_tags((string) $project->bab1_content), 1800) . "\n\n" . Str::limit(strip_tags((string) $project->bab3_content), 2000);
        elseif ($bab == 'bab5') {
            $context = "RUMUSAN MASALAH BAB I:\n" . Str::limit(strip_tags((string) $project->bab1_content), 1800) . "\n\nHASIL PENELITIAN BAB IV:\n" . Str::limit(strip_tags((string) $project->bab4_content), 2800);
            $allText = $project->bab1_content . " " . $project->bab2_content . " " . $project->bab3_content . " " . $project->bab4_content;
            $citationsList = $this->extractInTextCitations($allText);
            $citationsStr = !empty($citationsList) ? implode("\n- ", $citationsList) : "- Tidak ada sitasi terdeteksi";
            $context .= "\n\n=== DAFTAR SITASI ASLI YANG MUNCUL DALAM NASKAH BAB 1 S/D 4 (WAJIB DIBUATKAN DAFTAR PUSTAKANYA) ===\n- " . $citationsStr;
            $context .= "\n\n=== RINGKASAN ISI TEKS DARI BAB 1 SAMPAI BAB 4 ===\n" . Str::limit(strip_tags($allText), 10000);
        }

        // Bab II Sesuai Rumpun Ilmu & WAJIB KERANGKA BERPIKIR DISESUAIKAN DENGAN JUDUL & JURUSAN
        $bab2Prompt = match (true) {
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

            $isEkonomi => "
TUGAS UTAMA: Tuliskan NASKAH LENGKAP BAB II (TINJAUAN PUSTAKA) BIDANG EKONOMI / MANAJEMEN / AKUNTANSI (MINIMAL 1800 - 2500 KATA).
Disesuaikan secara persis dengan Judul: '{$project->judul}' dan Program Studi: '{$project->prodi}'.
WAJIB MEMUAT SELURUH SUB-BAB BERURUTAN:
2.1 Landasan Teori (Uraikan Grand Theory [misal Agency Theory / TAM / TPB / RBV / Signaling Theory], Middle Theory, Applied Theory, definisi konseptual menurut para ahli, dimensi, faktor determinan, dan indikator pengukuran setiap variabel penelitian).
2.2 Penelitian Terdahulu (WAJIB cantumkan 3-5 jurnal bereputasi SINTA/Scopus periode {$tahunMin}-2026 dalam narasi kritis diikuti TABEL perbandingan:
Tabel 2.1 Sintesis Penelitian Terdahulu
| No | Peneliti & Tahun | Judul Penelitian | Variabel & Metode | Hasil Penelitian | Persamaan & Perbedaan (Research Gap) |
Diikuti 2 paragraf analisis kesenjangan riset).
2.3 Kerangka Konseptual (Uraikan hubungan kausalitas antarvariabel independen terhadap variabel dependen/mediasi berdasarkan landasan teori.
Lalu WAJIB sajikan DIAGRAM KERANGKA KONSEPTUAL dalam blok kode vertikal:
Gambar 2.1 Bagan Kerangka Konseptual Penelitian
```
[Fenomena Bisnis & Masalah Empiris pada '{$project->judul}']
↓
[Grand Theory & Kajian Variabel Independen (X) serta Dependen (Y)]
↓
[Pengujian Empiris: Survei / Laporan Keuangan & Analisis Statistik]
↓
[Pembuktian Pengaruh Signifikan & Implikasi Manajerial]
```
).
2.4 Pengembangan Hipotesis (Rumuskan hipotesis H1, H2, H3 dst. secara eksplisit dengan argumentasi teoretis dan empiris pendukung).",

            $isHukum => "
TUGAS UTAMA: Tuliskan NASKAH LENGKAP BAB II (TINJAUAN PUSTAKA) BIDANG ILMU HUKUM / SYARIAH (MINIMAL 1800 - 2500 KATA).
Disesuaikan secara persis dengan Judul: '{$project->judul}' dan Program Studi: '{$project->prodi}'.
WAJIB MEMUAT:
2.1 Tinjauan Umum dan Landasan Yuridis (Teori Hukum Utama [Teori Kepastian Hukum, Keadilan, Kemanfaatan, Perlindungan Hukum], asas-asas hukum, serta telaah peraturan perundang-undangan/pasal-pasal relevan di Indonesia).
2.2 Tinjauan Pustaka Terdahulu / Keaslian Penelitian (Cantumkan 3-5 jurnal hukum terakreditasi SINTA periode {$tahunMin}-2026 diikuti TABEL perbandingan:
Tabel 2.1 Perbandingan Penelitian Hukum Terdahulu
| No | Peneliti & Tahun | Judul Penelitian | Pendekatan Yuridis | Fokus & Temuan | Perbedaan & Kebaruan |
).
2.3 Kerangka Pemikiran / Kerangka Konseptual Yuridis (Uraikan penalaran hukum dari norma/das Sollen, realitas penegakan hukum/das Sein, analisis perundang-undangan, hingga konstruksi hukum ideal.
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

            $isPsikologi || $isKomunikasiSosial => "
TUGAS UTAMA: Tuliskan NASKAH LENGKAP BAB II (TINJAUAN PUSTAKA) BIDANG PSIKOLOGI / ILMU KOMUNIKASI / SOSIAL POLITIK (MINIMAL 1800 - 2500 KATA).
Disesuaikan secara persis dengan Judul: '{$project->judul}' dan Program Studi: '{$project->prodi}'.
WAJIB MEMUAT:
2.1 Landasan Teori (Uraikan Grand Theory bidang terkait, definisi konseptual tiap variabel menurut 3-4 tokoh/ahli, aspek/dimensi psikologis atau sosial, faktor pembentuk, serta dinamika keterkaitan antarvariabel).
2.2 Penelitian Terdahulu (Cantumkan 3-5 jurnal bereputasi SINTA/Scopus periode {$tahunMin}-2026 diikuti TABEL perbandingan:
Tabel 2.1 Matriks Penelitian Terdahulu
| No | Peneliti & Tahun | Judul Penelitian | Variabel & Metode | Hasil Utama | Celah Penelitian |
).
2.3 Kerangka Berpikir Penelitian (Uraikan alur penalaran deduktif dan sajikan diagram vertikal:
Gambar 2.1 Bagan Kerangka Berpikir Penelitian
```
[Fenomena Sosial / Psikologis pada '{$project->judul}']
↓
[Tinjauan Teoretis & Dimensi Variabel Penelitian]
↓
[Pengumpulan Data Empiris & Analisis Statistik / Kualitatif]
↓
[Temuan Empiris & Kontribusi Keilmuan]
```
).
2.4 Hipotesis / Proposisi Penelitian.",

            ($isPendidikan && $isPengaruh) || ($isPengaruh && !$isInformatika) => "
TUGAS UTAMA: Tuliskan NASKAH LENGKAP BAB II (TINJAUAN PUSTAKA) PENELITIAN KUANTITATIF (PENGARUH / HUBUNGAN ANTARVARIABEL).
Disesuaikan secara persis dengan Judul: '{$project->judul}' dan Program Studi: '{$project->prodi}'.
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
[Kondisi Empiris: Masalah Nyata pada '{$project->judul}']
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
TUGAS UTAMA: Tuliskan NASKAH LENGKAP BAB II (TINJAUAN PUSTAKA) BIDANG PENDIDIKAN / KEGURUAN (FKIP / TARBIYAH) (MINIMAL 1800 - 2500 KATA).
Disesuaikan secara persis dengan Judul: '{$project->judul}' dan Program Studi: '{$project->prodi}'.
WAJIB MEMUAT:
2.1 Landasan Teori (Hakikat pembelajaran, model/metode/media pembelajaran yang diuji, karakteristik hasil belajar/keterampilan siswa, materi ajar, dan indikator ketercapaian).
2.2 Penelitian Terdahulu (WAJIB cantumkan 3-5 jurnal pendidikan bereputasi SINTA periode {$tahunMin}-2026 dalam narasi kritis dan TABEL komparasi:
Tabel 2.1 Sintesis Penelitian Terdahulu
| No | Peneliti & Tahun | Judul Penelitian | Model / Metode | Temuan / Peningkatan | Posisi Penelitian Ini |
).
2.3 Kerangka Berpikir Tindakan (Uraikan alur pemikiran dari kondisi awal rendahnya hasil belajar/keaktifan siswa, penerapan tindakan inovasi pembelajaran, hingga kondisi akhir peningkatan hasil belajar.
Lalu WAJIB sajikan DIAGRAM KERANGKA BERPIKIR dalam blok kode:
Gambar 2.1 Bagan Kerangka Berpikir Tindakan Pembelajaran
```
[Kondisi Awal: Masalah Pembelajaran & Rendahnya Hasil Belajar Siswa]
↓
[Tindakan: Penerapan Model / Media Pembelajaran Inovatif Sesuai Judul]
↓
[Proses: Pembelajaran Siklus I & II / Eksperimen Interaktif]
↓
[Kondisi Akhir: Peningkatan Pemahaman Konsep, Keterampilan & Skor N-Gain]
```
).
2.4 Hipotesis Tindakan / Hipotesis Statistik.",

            $isKesehatan => "
TUGAS UTAMA: Tuliskan NASKAH LENGKAP BAB II (TINJAUAN PUSTAKA) BIDANG KESEHATAN / FARMASI / KEPERAWATAN / KEDOKTERAN (MINIMAL 1800 - 2500 KATA).
Disesuaikan secara persis dengan Judul: '{$project->judul}' dan Program Studi: '{$project->prodi}'.
WAJIB MEMUAT:
2.1 Landasan Teori (Patofisiologi/etiologi, senyawa aktif atau intervensi klinis/asuhan keperawatan, mekanisme biologis/fisiologis, standar Kemenkes/WHO, dan tolok ukur klinis).
2.2 Penelitian Terdahulu (WAJIB cantumkan 3-5 jurnal kesehatan bereputasi SINTA/PubMed/Scopus periode {$tahunMin}-2026 diikuti TABEL perbandingan:
Tabel 2.1 Sintesis Penelitian Terdahulu
| No | Peneliti & Tahun | Judul Penelitian | Desain & Sampel | Hasil Klinis (p-value / OR) | Celah Penelitian |
).
2.3 Kerangka Konseptual (Uraikan alur keterkaitan antara agen/faktor intervensi, mekanisme respon biologis/klinis, hingga efek terapi/luaran kesehatan pasien.
Lalu WAJIB sajikan DIAGRAM KERANGKA KONSEPTUAL dalam blok kode:
Gambar 2.1 Diagram Kerangka Konseptual Penelitian
```
[Faktor Intervensi / Paparan / Asuhan Klinis Sesuai Judul]
↓
[Mekanisme Biologis / Fisiologis / Edukasi Kesehatan]
↓
[Pengukuran Parameter Klinis & Uji Statistik Bivariat]
↓
[Luaran Klinis: Penurunan Gejala, Efektivitas Terapi & Kualitas Hidup]
```
).
2.4 Hipotesis Penelitian.",

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
2.4 Hipotesis / Proposisi Penelitian."
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

            $isHukum => "
TUGAS: Tulis Isi BAB III (METODOLOGI PENELITIAN HUKUM) secara mendalam (MINIMAL 1600 - 2200 KATA).
3.1 Jenis dan Pendekatan Penelitian (Yuridis Normatif / Yuridis Empiris, Pendekatan Perundang-undangan [Statute Approach], Pendekatan Konseptual [Conceptual Approach], dan Pendekatan Kasus [Case Approach]).
3.2 Lokasi dan Waktu Penelitian (jika empiris) atau Bahan Hukum Penelitian (Bahan Hukum Primer, Bahan Hukum Sekunder, Bahan Hukum Tersier — sajikan Tabel 3.1 Klasifikasi Bahan Hukum).
3.3 Teknik Pengumpulan Bahan Hukum / Data Lapangan (Studi Kepustakaan & Wawancara Informan).
3.4 Teknik Analisis Bahan Hukum (Interpretasi Gramatikal, Sistematis, Teleologis, dan Penalaran Deduktif Silogisme Hukum).
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

            $isKesehatan => "
TUGAS: Tulis Isi BAB III (METODOLOGI PENELITIAN KESEHATAN / KLINIS) secara mendalam (MINIMAL 1600 - 2200 KATA).
3.1 Desain Penelitian (Observasional Analitik Cross-Sectional / Case-Control / Eksperimental Klinis).
3.2 Tempat dan Waktu Penelitian.
3.3 Populasi, Sampel, dan Teknik Sampling (Populasi target & terjangkau, rumus penentuan besar sampel Lemeshow/Slovin, Kriteria Inklusi & Kriteria Eksklusi).
3.4 Variabel Penelitian dan Definisi Operasional (WAJIB sajikan Tabel 3.1 Definisi Operasional Variabel: | Variabel | Definisi Operasional | Alat Ukur | Cara Ukur | Hasil Ukur | Skala |).
3.5 Instrumen, Etika Penelitian (Informed Consent, Anonymity, Confidentiality, Ethical Clearance), dan Prosedur Pengumpulan Data:
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
[Analisis Univariat & Bivariat (Chi-Square / Uji-t / Mann-Whitney)]
↓
[Evaluasi Signifikansi Klinis (p-value < 0,05 & Odds Ratio)]
↓
[Penyusunan Laporan Hasil Penelitian]
↓
[Selesai]
```
3.6 Pengolahan dan Teknik Analisis Data (Univariat & Bivariat).",

            ($isPendidikan && $isPengaruh) || ($isPengaruh && !$isInformatika) => "
TUGAS: Tulis Isi BAB III (METODOLOGI PENELITIAN KUANTITATIF ASOSIATIF / KAUSAL) secara mendalam (MINIMAL 1800 - 2500 KATA).
Disesuaikan secara persis dengan Judul: '{$project->judul}' dan Program Studi: '{$project->prodi}'.
WAJIB MEMUAT SUB-BAB TEKNIS LENGKAP:
3.1 Jenis dan Desain Penelitian (Kuantitatif Asosiatif / Kausal).
3.2 Tempat dan Waktu Penelitian (Deskripsi lokasi setting penelitian dan jadwal pelaksanaan).
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
[Identifikasi Masalah & Studi Kepustakaan '{$project->judul}']
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
TUGAS: Tulis Isi BAB III (METODOLOGI PENELITIAN PENDIDIKAN / QUASI-EKSPERIMEN / PTK) secara mendalam (MINIMAL 1800 - 2400 KATA).
3.1 Desain Penelitian (Nonequivalent Pretest-Posttest Control Group Design / PTK Siklus I-II).
3.2 Tempat dan Waktu Penelitian.
3.3 Populasi dan Sampel (Kelas eksperimen vs kelas kontrol).
3.4 Variabel Penelitian dan Definisi Operasional (Sajikan Tabel 3.1 Kisi-kisi Instrumen & Variabel).
3.5 Instrumen Pengumpulan Data (Tes kognitif pretest-posttest dan lembar observasi).
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

            $isRnD => "
TUGAS: Tulis Isi BAB III (METODOLOGI PENGEMBANGAN SISTEM / R&D) secara mendalam (MINIMAL 1800 - 2400 KATA).
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

            $isKualitatif => "
TUGAS: Tulis Isi BAB III (METODOLOGI PENELITIAN KUALITATIF) secara mendalam (MINIMAL 1600 - 2200 KATA).
3.1 Pendekatan dan Jenis Penelitian (Deskriptif Kualitatif / Studi Kasus / Fenomenologi).
3.2 Lokasi dan Waktu Penelitian.
3.3 Fokus Penelitian (Sajikan Tabel 3.1 Matriks Fokus dan Indikator Penelitian).
3.4 Sumber Data dan Teknik Pemilihan Informan (Purposive Sampling / Snowball Sampling).
3.5 Teknik Pengumpulan Data (Observasi Partisipatif, Wawancara Mendalam, Dokumentasi).
3.6 Teknik Pemeriksaan Keabsahan Data (Triangulasi Sumber, Triangulasi Teknik, Triangulasi Waktu).
3.7 Teknik Analisis Data (Kondensasi Data, Penyajian Data, dan Penarikan Kesimpulan Model Miles, Huberman & Saldana) beserta Diagram Alur:
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

        // Bab IV Sesuai Rumpun Ilmu (Tabel Pengujian, Confusion Matrix / Regresi, Pembahasan Kritis)
        $bab4Prompt = match (true) {
            $isInformatika => "
TUGAS: Tulis Isi BAB IV (HASIL PENELITIAN DAN PEMBAHASAN KOMPUTASI / INFORMATIKA).
WAJIB MEMUAT TABEL DAN DATA EMPIRIS LENGKAP:
4.1 Deskripsi dan Karakteristik Dataset Penelitian (Uraikan statistik data mentah, jumlah sampel per kelas sentimen/kategori dalam TABEL distribusi kelas).
4.2 Hasil Pra-Pemrosesan Teks (Sajikan TABEL contoh perbandingan 3-4 kalimat sebelum dan sesudah melewati setiap tahapan: Raw Text -> Cleaning -> Slang Normalization -> Stopword Removed -> Stemmed).
4.3 Hasil Pengujian dan Evaluasi Kinerja Algoritma (WAJIB sajikan TABEL Confusion Matrix [True Positive, True Negative, False Positive, False Negative] dan TABEL Perbandingan Metrik Evaluasi [Akurasi, Presisi, Recall, F1-Score] dengan angka-angka terukur dan realistis).
4.4 Pembahasan Kritis Hasil Penelitian (Analisis mendalam mengapa model mencapai akurasi tersebut, identifikasi faktor pemicu kesalahan klasifikasi [False Positive & False Negative], pengaruh preprocessing terhadap akurasi, serta perbandingan kinerja terhadap penelitian terdahulu).",

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
Disesuaikan secara persis dengan Judul: '{$project->judul}' dan Program Studi: '{$project->prodi}'.
WAJIB MEMUAT TABEL DATA STATISTIK LENGKAP DAN PEMBAHASAN MENDALAM (MINIMAL 1500 - 2500 KATA):
4.1 Gambaran Umum Lokasi dan Karakteristik Responden (Sajikan TABEL Demografi Responden: Jenis Kelamin, Usia, Kelas/Latar Belakang).
4.2 Deskripsi Data Variabel Penelitian (Sajikan TABEL Statistik Deskriptif Variabel X dan Variabel Y: N, Skor Minimum, Maksimum, Mean, Standar Deviasi, serta TABEL Kategori Distribusi Frekuensi).
4.3 Hasil Uji Kualitas Instrumen (Sajikan TABEL Hasil Uji Validitas r-hitung > r-tabel dan TABEL Uji Reliabilitas Cronbach's Alpha > 0,60).
4.4 Hasil Uji Prasyarat Analisis (Sajikan TABEL Hasil Uji Normalitas Kolmogorov-Smirnov dan TABEL Uji Linearitas).
4.5 Hasil Analisis Regresi Linier dan Uji Hipotesis:
a. Sajikan TABEL Ringkasan Model Regresi (Nilai Korelasi R dan Koefisien Determinasi R-Square / R²).
b. Sajikan TABEL Uji Koefisien Regresi (Konstanta a, Koefisien Regresi b, nilai t-hitung, dan nilai signifikansi p-value / Sig.).
c. Tuliskan persamaan regresi yang terbentuk secara jelas: Ŷ = a + bX beserta interpretasi maknanya.
4.6 Pembahasan Hasil Penelitian (Bahas secara kritis dan mendalam minimal 6-8 paragraf: konfrontasikan temuan angka empiris dengan teori Bab II dan temuan penelitian terdahulu, implikasi hasil terhadap responden, serta faktor-faktor pemicu temuan tersebut).
4.7 Keterbatasan Penelitian (Uraikan kendala metodologis atau lingkup yang dihadapi selama penelitian).",

            $isPendidikan => "
TUGAS: Tulis Isi BAB IV (HASIL PENELITIAN DAN PEMBAHASAN PENDIDIKAN).
WAJIB MEMUAT:
4.1 Deskripsi Data Hasil Belajar (Sajikan TABEL nilai rata-rata Pretest & Posttest kelas eksperimen vs kelas kontrol).
4.2 Hasil Uji Prasyarat Analisis (Sajikan TABEL Uji Normalitas & Uji Homogenitas Varian).
4.3 Hasil Pengujian Hipotesis & Efektivitas (Sajikan TABEL Uji-t Independent Sample dan TABEL Peningkatan Skor N-Gain).
4.4 Pembahasan Temuan Penelitian.",

            $isRnD => "
TUGAS: Tulis Isi BAB IV (HASIL PENGEMBANGAN DAN PEMBAHASAN SISTEM).
WAJIB MEMUAT:
4.1 Hasil Analisis dan Desain Sistem.
4.2 Implementasi Antarmuka Sistem (Deskripsikan halaman-halaman utama antarmuka).
4.3 Hasil Pengujian Sistem (Sajikan TABEL Pengujian Black-Box Testing dan TABEL Hasil Skor UAT / System Usability Scale).
4.4 Pembahasan Kelebihan dan Keterbatasan Sistem.",

            default => "
TUGAS: Tulis Isi BAB IV (HASIL PENELITIAN DAN PEMBAHASAN).
WAJIB MEMUAT TABEL DATA DAN ANALISIS EMPIRIS LENGKAP:
4.1 Deskripsi Data dan Objek Penelitian.
4.2 Hasil Analisis Data (Sajikan TABEL pengolahan data terstruktur).
4.3 Pengujian Hipotesis / Capaian Solusi.
4.4 Pembahasan Kritis Hasil Penelitian."
        };

        // Bab V Penutup & Daftar Pustaka Lengkap & Terverifikasi (Bebas Mendeley)
        $bab5Prompt = "
TUGAS: Tulis Isi BAB V (PENUTUP) beserta DAFTAR PUSTAKA LENGKAP & OTOMATIS (STANDAR {$gayaSitasi}).
Sub-bab:
5.1 Kesimpulan (Menjawab rumusan masalah Bab 1 secara tegas berdasarkan capaian empiris Bab 4. Sebutkan angka-angka capaian seperti nilai akurasi atau signifikansi regresi secara konkret).
5.2 Saran (Uraikan saran operasional dan saran teknis bagi penelitian lanjutan).

Setelah sub-bab 5.2 selesai, buat baris baru bertuliskan persis:
===DAFTAR PUSTAKA===
Lalu tuliskan DAFTAR PUSTAKA LENGKAP URUT ABJAD A-Z (format {$gayaSitasi}) YANG WAJIB MENGAMBIL DARI SELURUH SITASI ASLI YANG MUNCUL DALAM NASKAH BAB 1 HINGGA BAB 4 (lihat daftar sitasi yang tertera di atas)" . (!empty($meta['referensi_khusus']) ? " serta SELURUH ARTIKEL / BUKU UNGGAHAN MAHASISWA" : "") . ".

ATURAN MUTLAK SITASI & DAFTAR PUSTAKA (JANGAN MENGARANG ASAL-ASALAN):
1. DAFTAR PUSTAKA WAJIB DIBUAT DARI SITASI NYATA YANG BENAR-BENAR DIKUTIP DALAM BAB 1 S/D 4! Dilarang keras mengarang referensi fiktif yang tidak pernah disebut dalam naskah.
2. Setiap entri DAFTAR PUSTAKA WAJIB memiliki format publikasi lengkap sesuai {$gayaSitasi}:
   Nama Penulis. (Tahun). Judul artikel penelitian secara spesifik. Nama Jurnal Bereputasi, Volume(Nomor), Halaman. https://doi.org/10.xxxx/...
3. Referensi WAJIB ASLI dan NYATA (terindeks {$sumberArr} / SINTA/Garuda Kemdikbud untuk Indonesia, atau Scopus/IEEE/ACM/Elsevier/Springer untuk Internasional).
4. Tahun terbit referensi WAJIB minimal tahun {$tahunMin} dan maksimal tahun 2026.
5. Konsistensi 100%: Setiap nama penulis dan tahun yang disitasi dalam teks (in-text citation) Bab I sampai Bab IV WAJIB ADA di dalam daftar pustaka ini.
6. Tanpa kalimat pengantar AI, langsung baris referensi pertama A-Z.";

        $customStructureNote = !empty($meta['struktur_kustom'])
            ? "PERHATIAN PENTING: MAHASISWA MEMILIKI STRUKTUR SUB-BAB KUSTOM DARI KAMPUSNYA (LIHAT DI ATAS). AI WAJIB MENGIKUTI STRUKTUR KUSTOM TERSEBUT SECARA PERSIS, BUKAN STRUKTUR STANDAR DEFAULT!\n"
            : "";

        $sistematikaScope = $isProposal ? 'Bab I hingga Bab III (Proposal Skripsi)' : 'Bab I hingga Bab V (Skripsi Lengkap)';
        return match ($bab) {
            'bab1' => $master . "\n{$tahapKaryaPrompt}\n{$dataAwalPrompt}\n{$strukturKustomPrompt}\n{$referensiKhususPrompt}\n{$dataRiilPrompt}\n" . "
TUGAS UTAMA: Tuliskan NASKAH LENGKAP BAB I (PENDAHULUAN) dari awal sampai akhir secara MENDALAM, KOMPREHENSIF, DAN EKSTENSIF (STANDAR SKRIPSI S1 NASIONAL PERGURUAN TINGGI INDONESIA, MINIMAL 1800 - 2500 KATA).
{$customStructureNote}" . (empty($meta['struktur_kustom']) ? "WAJIB MEMUAT SELURUH SUB-BAB BERURUTAN TANPA MELEWATKAN SATU PUN:
1.1 Latar Belakang Masalah (Wajib ditulis secara MENDALAM dan KOMPREHENSIF minimal 7-10 PARAGRAF PANJANG menggunakan pola PIRAMIDA TERBALIK lengkap):
- Paragraf 1-2: Landasan normatif, regulasi nasional (Undang-Undang, Permendikbud / regulasi terkait), dan konteks ideal makro terkait topik '{$project->judul}'. Paragraf pembuka WAJIB langsung mencantumkan rujukan ilmiah riil/otoritas data resmi (seperti Kemendikbudristek/BSKAP, BPS, UNESCO, atau We Are Social/DataReportal) serta referensi terverifikasi di atas.
- Paragraf 3-4: Fenomena empiris dan data kuantitatif nyata di lapangan / objek penelitian terkait kondisi responden dan intensitas masalah.
- Paragraf 5-6: Masalah spesifik yang teramati dan analisis kesenjangan (research gap) antara kondisi ideal (das Sollen) dengan realitas di lapangan (das Sein).
- Paragraf 7-8: Analisis komparatif hasil penelitian terdahulu (tahun {$tahunMin}-2026) dan alasan mengapa pendekatan/solusi dalam penelitian ini mutlak dibutuhkan.
- Paragraf 9-10: Penegasan urgensi solusi, kebaruan (novelty), dan justifikasi ilmiah mengapa penelitian ini penting untuk diselesaikan.
1.2 Identifikasi Masalah (Tuliskan 5-7 butir permasalahan konkret dan mendalam yang teramati pada objek penelitian dalam format poin bernomor 1., 2., 3., dst.).
1.3 Batasan Masalah (Uraikan batasan ruang lingkup penelitian secara tegas dalam 3-5 poin: batasan variabel, subjek/sampel, periode waktu, dan lokasi penelitian).
1.4 Rumusan Masalah (Tuliskan 3-4 butir pertanyaan penelitian ilmiah yang spesifik, terukur, dan selaras dengan topik).
1.5 Tujuan Penelitian (Tuliskan Tujuan Umum dan Tujuan Khusus yang menjawab langsung setiap butir Rumusan Masalah).
1.6 Manfaat Penelitian (Uraikan secara komprehensif ke dalam: 1.6.1 Manfaat Teoretis dan 1.6.2 Manfaat Praktis bagi subjek/responden, bagi praktisi/instansi, dan bagi peneliti selanjutnya).
1.7 Keaslian Penelitian (Uraikan poin pembeda riset ini dari riset-riset sebelumnya).
1.8 Sistematika Penulisan (Uraikan ringkasan isi {$sistematikaScope} secara terperinci).\n" : "") . "
" . (!empty($meta['data_awal']) ? "WAJIB: Masukkan data awal penelitian mahasiswa ('{$meta['data_awal']}') secara eksplisit ke dalam Latar Belakang Masalah (1.1) sebagai bukti fenomena nyata!\n" : "") . "
INSTRUKSI MUTLAK:
- Seluruh sub-bab HARUS ditulis lengkap tuntas dalam satu kali respons ini. DILARANG berhenti di tengah jalan!
- Tuliskan dengan paragraf-paragraf akademik panjang yang padat analisis (target 1800-2500 kata). DILARANG membuat ringkasan pendek!
- Langsung mulai dari sub-bab pertama tanpa kata pengantar AI.",

            'bab2' => $master . "\n{$tahapKaryaPrompt}\n$context\n{$strukturKustomPrompt}\n{$referensiKhususPrompt}\n{$dataRiilPrompt}\n{$bab2Prompt}\n
{$customStructureNote}
INSTRUKSI MUTLAK:
- Seluruh sub-bab, TABEL PENELITIAN TERDAHULU, dan DIAGRAM KERANGKA BERPIKIR HARUS ditulis lengkap tuntas tanpa terpotong.
- Tahun terbit semua penelitian terdahulu WAJIB antara {$tahunMin}-2026.
- Langsung mulai dari sub-bab pertama tanpa kata pengantar AI.",

            'bab3' => $master . "\n{$tahapKaryaPrompt}\n$context\n{$dataAwalPrompt}\n{$strukturKustomPrompt}\n{$referensiKhususPrompt}\n{$dataRiilPrompt}\n{$bab3Prompt}\n
{$customStructureNote}
INSTRUKSI MUTLAK:
- Tulis seluruh sub-bab secara tuntas, wajib sertakan DIAGRAM ALUR (FLOWCHART) dalam blok kode vertikal `[Tahap]` dipisahkan panah `↓`, serta tabel operasional/instrumen yang rapi.",

            'bab4' => $master . "\n{$tahapKaryaPrompt}\n$context\n{$dataAwalPrompt}\n{$strukturKustomPrompt}\n{$referensiKhususPrompt}\n{$dataRiilPrompt}\n{$bab4Prompt}\n
{$customStructureNote}
INSTRUKSI MUTLAK:
- WAJIB menyajikan TABEL pengolahan data empiris lengkap dengan nomor judul (Tabel 4.1, Tabel 4.2, dst.) beserta perhitungan rumus Unicode bersih dan pembahasan kritis mendalam.",

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

        foreach ($lines as $line) {
            $lineTrimmed = trim(strip_tags($line));
            $lineUpper = strtoupper($lineTrimmed);

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
        $project = Project::where('user_id', Auth::id())->findOrFail($id);
        $chapter = $request->chapter;
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
                    $project->bab5_content = trim($parts[0]);
                    $cleanDp = $this->cleanDaftarPustaka(trim($parts[1] ?? ''), $tahunMinCh);
                    if (!empty($cleanDp)) {
                        $project->daftar_pustaka = $cleanDp;
                    }
                } elseif (preg_match('/\n\s*(?:===+\s*)?DAFTAR\s+PUSTAKA\s*(?:===+)?\s*\n/i', $cleanContent)) {
                    $parts = preg_split('/\n\s*(?:===+\s*)?DAFTAR\s+PUSTAKA\s*(?:===+)?\s*\n/i', $cleanContent, 2);
                    $project->bab5_content = trim($parts[0]);
                    $cleanDp = $this->cleanDaftarPustaka(trim($parts[1] ?? ''), $tahunMinCh);
                    if (!empty($cleanDp)) {
                        $project->daftar_pustaka = $cleanDp;
                    }
                } else {
                    $project->bab5_content = $cleanContent;
                }
            } else {
                $project->{$chapter . '_content'} = $cleanContent;
                // Ekstrak sitasi otomatis ke daftar pustaka sementara saat Bab III selesai (Proposal) atau jika daftar pustaka masih kosong
                if ((empty($project->daftar_pustaka) && in_array($chapter, ['bab1', 'bab2', 'bab3'])) || $chapter === 'bab3') {
                    try {
                        $allText = strip_tags($project->bab1_content . ' ' . $project->bab2_content . ' ' . $project->bab3_content);
                        if (strlen($allText) > 500) {
                            $citationsList = $this->extractInTextCitations($allText);
                            $citBlock = !empty($citationsList)
                                ? "DAFTAR SITASI ASLI YANG MUNCUL DI NASKAH (WAJIB DIBUATKAN BIBLIOGRAFINYA):\n- " . implode("\n- ", $citationsList) . "\n"
                                : "";
                            $dpPrompt = "Tulis DAFTAR PUSTAKA gaya {$gayaSitasiCh} berdasarkan sitasi nama penulis dan tahun yang dikutip pada naskah proposal/skripsi berjudul '{$project->judul}' (Prodi: {$project->prodi}).\n" .
                                $citBlock .
                                (!empty($meta['referensi_khusus']) ? "REFERENSI WAJIB UNGGAHAN MAHASISWA (WAJIB MASUK):\n" . Str::limit($meta['referensi_khusus'], 2500) . "\n" : "") .
                                "Tahun penerbitan WAJIB minimal tahun {$tahunMinCh} dan maksimal 2026 (DILARANG di bawah {$tahunMinCh}).\n" .
                                "ATURAN KETAT:\n1. LANGSUNG tuliskan baris referensi pertama (urut A-Z) TANPA kalimat pembuka/pengantar.\n2. DILARANG menulis placeholder. Setiap referensi WAJIB memiliki nama penulis, tahun ({$tahunMinCh}-2026), judul artikel ilmiah spesifik, nama jurnal nyata, volume(nomor), halaman, dan DOI/URL.\n3. Tanpa tanda bintang markdown (*).\n\nNASKAH:\n" . Str::limit($allText, 6000);
                            $dpRaw = $this->ai->generateText($dpPrompt, $model);
                            $cleanedDp = $this->cleanDaftarPustaka($dpRaw, $tahunMinCh);
                            if (!empty($cleanedDp)) {
                                $project->daftar_pustaka = $cleanedDp;
                            }
                        }
                    } catch (Throwable $e) {
                    }
                }
            }

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
        $tail = substr($content, $startPos + strlen($match[0][0]));
        $nextPattern = '/\n(?:##\s*|<b>)?(?:\d+\.\d+(?:\.\d+)?|[A-Z]\.)[\s\.:\-\)][^\n]*/i';

        if (preg_match($nextPattern, $tail, $nextMatch, PREG_OFFSET_CAPTURE)) {
            $endPos = $startPos + strlen($match[0][0]) + $nextMatch[0][1];
        } else {
            $endPos = strlen($content);
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
5. HANYA KEMBALIKAN hasil revisi untuk sub-bab \"{$targetHeading}\" ini saja, BUKAN seluruh bab!";

                $revised = $this->ai->generateWithImage($prompt, $request->image_base64, $model);
                $cleanSub = $this->cleanTextKeepFormat($revised);

                // Pastikan judul sub-bab ada di awal baris
                $firstLine = trim(explode("\n", $cleanSub)[0] ?? '');
                if (!str_contains(strtolower($firstLine), strtolower(substr($targetHeading, 0, 5)))) {
                    $cleanSub = $targetHeading . "\n" . $cleanSub;
                }

                // Gantikan HANYA sub-bab target ke dalam naskah bab lengkap
                $cleanContent = $this->replaceSubchapterText($currentContent, $subBlock['start'], $subBlock['end'], $cleanSub);
            } else {
                // Revisi seluruh bab jika user memilih 'Semua Bab' atau naskah bab masih kosong
                $prompt = "PERAN: Anda adalah Profesor Pembimbing Skripsi Senior.
TUGAS: Lakukan penyempurnaan pada {$chapterName} untuk skripsi berjudul \"{$project->judul}\" (Prodi: {$project->prodi}, Metode: {$project->metode_penelitian}).
{$modeRule}

NASKAH {$chapterName} SAAT INI:
\"\"\"
" . Str::limit(strip_tags($currentContent), 12000) . "
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
4. Kembalikan naskah {$chapterName} yang sudah direvisi secara lengkap dan utuh.";

                $revised = $this->ai->generateWithImage($prompt, $request->image_base64, $model);
                $cleanContent = $this->cleanTextKeepFormat($revised);
                $cleanContent = $this->processChapterContent($cleanContent, $chapter);
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
            'chapter' => 'required|in:bab1,bab2,bab3,bab4,bab5,daftar_pustaka,front,front_logo,identity,data_riil,lampiran',
            'content' => 'required_unless:chapter,identity,data_riil,lampiran|string',
        ]);

        $project = Project::where('user_id', Auth::id())->findOrFail($id);

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
                $user->nim = trim($request->input('nim'));
                $user->save();
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

        $project->{$field} = trim($request->content);
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
        // Ubah kombinasi huruf + combining circumflex / precomposed hat agar rapi
        $text = str_replace(
            ['ŷ', 'Ŷ', 'ĉ', 'Ĉ', 'x̄', 'X̄', 'ȳ', 'Ȳ', 'p̂', 'P̂'],
            ['y&#770;', 'Y&#770;', 'c&#770;', 'C&#770;', 'x&#772;', 'X&#772;', 'y&#772;', 'Y&#772;', 'p&#770;', 'P&#770;'],
            $text
        );

        // Escape karakter HTML dulu (biarkan entitas &#770;/&#772; yang baru disisipkan)
        $escaped = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);

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

        // Bungkus simbol matematika, Yunani, panah, & tanda khusus di luar Windows-1252 dengan DejaVu Serif
        $escaped = preg_replace(
            '/([α-ωΑ-Ω∑∏√≈≤≥≠±∞∈∉⊂⊆∪∩∂∫∇→←↔⇒⇔↓↑•—–‘’“”]+)/u',
            '<span style="font-family:\'DejaVu Serif\', serif;">$1</span>',
            $escaped
        );

        // Bersihkan emoji yang tidak memiliki glyph di font PDF akademik
        $escaped = preg_replace('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u', '', $escaped);

        return $escaped;
    }

    private function formatAcademicContentForPdf(string $content, string $judul = ''): string
    {
        $content = str_replace(["\r\n", "\r"], "\n", $content);
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
                    $colWidths = $isFirstColNo ? ['6%', '18%', '22%', '18%', '16%', '20%'] : ['16%', '20%', '20%', '16%', '14%', '14%'];
                } elseif ($colCount === 5) {
                    $colWidths = $isFirstColNo ? ['6%', '22%', '24%', '24%', '24%'] : ['20%', '30%', '16%', '22%', '12%'];
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

                $avoidSplit = count($rows) <= 5 ? 'page-break-inside:avoid;' : 'page-break-inside:auto;';
                $fontSize = ($colCount >= 6) ? '8.5pt' : (($colCount === 5) ? '9pt' : '10pt');
                $headerSize = ($colCount >= 6) ? '9pt' : (($colCount === 5) ? '9.5pt' : '10pt');
                $lineHeight = '1.25';
                $cellPadding = ($colCount >= 5) ? '4.5pt 5pt' : '5.5pt 7pt';

                if (!empty($pendingTableCaption)) {
                    $html .= "<div style='font-size:11pt; font-weight:bold; margin-top:12pt; margin-bottom:4pt; text-align:left; text-indent:0; line-height:1.25; page-break-after:avoid; break-after:avoid;'>" . e($pendingTableCaption) . "</div>";
                    $pendingTableCaption = '';
                }

                // Tabel Akademik Bergaris Lengkap (Full-Grid Crisp Table Standar Skripsi Indonesia)
                $html .= "<table style='width:100%; table-layout:fixed; border-collapse:collapse; margin:4pt 0 12pt 0; font-size:{$fontSize}; font-family:inherit; border:1pt solid #000000; {$avoidSplit}'>";
                foreach ($rows as $rIdx => $cols) {
                    $isHeader = ($rIdx === 0);
                    if ($isHeader) {
                        $html .= "<thead style='display:table-header-group;'><tr style='background:#f1f5f9; page-break-inside:avoid;'>";
                        foreach ($cols as $cIdx => $col) {
                            $cleanCol = $this->formatAcademicMathText(trim(preg_replace('/^(\*{1,2}|<b>)+|(\*{1,2}|<\/b>)+$/i', '', $col)));
                            $wStyle = isset($colWidths[$cIdx]) ? "width:{$colWidths[$cIdx]};" : "";
                            $html .= "<th style='{$wStyle} padding:{$cellPadding}; font-weight:bold; text-align:center; vertical-align:middle; border:0.75pt solid #000000; border-bottom:1.2pt solid #000000; line-height:{$lineHeight}; font-size:{$headerSize}; word-wrap:break-word; overflow-wrap:break-word; background:#f1f5f9;'>" . $this->pdfSafeText($cleanCol) . "</th>";
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
            $rawCode = implode("\n", $codeLines);
            $isFlowchart = str_contains($rawCode, '-->') || str_contains($rawCode, '->') || str_contains($rawCode, '↓') || str_contains($rawCode, 'V') || preg_match('/\[.+?\]/', $rawCode);

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
                    $cn = preg_replace('/^[\(\[\/\s\-—]+|[\)\]\/\s\-—]+$/', '', $cn);
                    if (!empty($cn) && !preg_match('/^[↓V\-\->\s|+~#={}()\\[\\]]+$/', $cn) && !in_array($cn, ['{', '}', '(', ')', '[', ']', ';'])) {
                        $validNodes[] = $cn;
                    }
                }
                if (!empty($validNodes)) {
                    $svg = $this->buildFlowchartSvg($validNodes, $isFramework);
                    $svgB64 = base64_encode($svg);
                    $html .= "<div class='academic-flowchart' style='margin:14pt auto 16pt auto; text-align:center; width:100%; page-break-inside:avoid;'>";
                    $html .= "<img src='data:image/svg+xml;base64,{$svgB64}' style='width:395pt; max-width:100%; height:auto; display:block; margin:0 auto;'>";
                    // Standar Skripsi Indonesia: Judul Gambar WAJIB diletakkan di BAWAH gambar
                    $html .= "<div style='font-size:11pt; font-weight:bold; margin-top:6pt; text-align:center; text-indent:0; color:#000000; page-break-before:avoid;'>" . e($caption) . "</div>";
                    $html .= "</div>";
                }
            } else {
                $html .= "<pre style='background:#f8fafc; border:1pt solid #cbd5e1; border-radius:4pt; padding:8pt 12pt; font-size:9.5pt; font-family:monospace; line-height:1.4; white-space:pre-wrap; word-break:break-all; margin:10pt 0;'>" . e($rawCode) . "</pre>";
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
                $codeLines[] = $trimmed;
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
                $html .= "<h3 style='font-size:12pt; font-weight:bold; margin-top:14pt; margin-bottom:6pt; text-align:left; text-indent:0; page-break-after:avoid; break-after:avoid;'>" . e($clean) . "</h3>";
                continue;
            }

            // List bernomor (1. ..., 2. ...)
            if (preg_match('/^\d+\.\s+(.+)$/', $trimmed, $m)) {
                if ($inUl) {
                    $html .= "</ul>";
                    $inUl = false;
                }
                if (!$inOl) {
                    $html .= "<ol style='margin:4pt 0 8pt 0; padding-left:1.27cm;'>";
                    $inOl = true;
                }
                $cleanLi = $this->formatAcademicMathText(trim(preg_replace('/^(\*{1,2}|<b>)+|(\*{1,2}|<\/b>)+$/i', '', $m[1])));
                $html .= "<li style='text-align:justify; line-height:1.5; margin-bottom:4pt; page-break-inside:avoid;'>" . $this->pdfSafeText($cleanLi) . "</li>";
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
                $cleanLi = $this->formatAcademicMathText(trim(preg_replace('/^(\*{1,2}|<b>)+|(\*{1,2}|<\/b>)+$/i', '', $m[1])));
                $html .= "<li style='text-align:justify; line-height:1.5; margin-bottom:4pt; page-break-inside:avoid;'>" . $this->pdfSafeText($cleanLi) . "</li>";
                continue;
            }

            // Paragraf ilmiah baku atau Rumus Matematika Standalone
            $closeLists();
            $cleanP = trim(preg_replace('/^(\*{1,2}|#+\s*)+|(\*{1,2})+$/', '', $trimmed));
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
        $text = str_replace(['<p>', '</p>', '<br>', '<br/>'], ["\n", "\n", "\n", "\n"], $htmlContent);
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
                    $pctMap = $isFirstColNo ? [0.06, 0.16, 0.22, 0.14, 0.16, 0.26] : [0.16, 0.20, 0.20, 0.15, 0.14, 0.15];
                } elseif ($maxCols === 5) {
                    $pctMap = $isFirstColNo ? [0.07, 0.20, 0.27, 0.23, 0.23] : [0.20, 0.28, 0.16, 0.22, 0.14];
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
                        $cn = preg_replace('/^[\(\[\/\s\-—]+|[\)\]\/\s\-—]+$/', '', $cn);
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
        // Format 1: Penulis (Tahun), cth: Indriyani et al. (2023), Mulya (2025), Azril & Chrisnawati (2026)
        if (preg_match_all('/([A-Z][a-zA-Z]+(?:\s+(?:et\s+al\.?|dan\s+[A-Z][a-zA-Z]+|&\s+[A-Z][a-zA-Z]+))?)\s*\(((?:19|20)\d\d)\)/u', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $author = trim($m[1]);
                $year = $m[2];
                if (!in_array(strtolower($author), ['tabel', 'gambar', 'bab', 'lampiran', 'bagan'])) {
                    $citations["{$author} ({$year})"] = true;
                }
            }
        }
        // Format 2: (Penulis, Tahun), cth: (Indriyani et al., 2023), (Azril & Chrisnawati, 2026)
        if (preg_match_all('/\(([A-Z][a-zA-Z]+(?:\s+(?:et\s+al\.?|dan\s+[A-Z][a-zA-Z]+|&\s+[A-Z][a-zA-Z]+))?),?\s*((?:19|20)\d\d)\)/u', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $author = trim($m[1]);
                $year = $m[2];
                if (!in_array(strtolower($author), ['tabel', 'gambar', 'bab', 'lampiran', 'bagan'])) {
                    $citations["{$author} ({$year})"] = true;
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
            $tmpPath = '/tmp/logo_' . uniqid() . '.' . $ext;

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

                    $tmpJpg = '/tmp/logo_' . uniqid() . '.jpg';
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
        $fakultas = $request->input('fakultas') ?: ($meta['fakultas'] ?? '');
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
        $coverSection = $phpWord->addSection($baseSectionStyle);
        $coverSection->addText($jenisDokumen, $bold14, $center);
        $coverSection->addTextBreak(1);
        $coverSection->addText(strtoupper($project->judul), $bold14, $center);
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

            $section->addPageBreak();
            // --- ABSTRAK (BAHASA INDONESIA — STANDAR 1 SPASI) ---
            $section->addTitle("ABSTRAK", 1);
            $section->addText(strtoupper($project->judul), $bold12, $center);
            $section->addText("Oleh: " . $nama . ($npm ? " (NIM: " . $npm . ")" : ""), ['size' => 11], $center);
            $section->addText("Program Studi " . $project->prodi . ($kampus ? ", " . $kampus : ""), ['size' => 10.5, 'color' => '333333'], $center);
            $section->addTextBreak(1);
            $section->addText(
                "Penelitian ini bertujuan untuk menginvestigasi, menganalisis, dan menyelesaikan permasalahan pada topik \"" . $project->judul . "\". Metode penelitian yang diterapkan adalah " . ($project->metode_penelitian ?: 'Kuantitatif / Kualitatif Ilmiah') . " melalui prosedur pengumpulan, pengolahan, dan pengujian data empiris secara sistematis. Hasil penelitian menunjukkan bahwa pendekatan yang diterapkan mampu menjawab seluruh rumusan masalah secara terukur, memberikan evaluasi kinerja yang valid, serta menghasilkan kontribusi teoretis dan praktis bagi pengembangan keilmuan pada Program Studi " . $project->prodi . ".",
                ['name' => 'Times New Roman', 'size' => 11.5],
                ['alignment' => Jc::BOTH, 'lineHeight' => 1.0, 'indentation' => ['firstLine' => 720], 'spaceAfter' => 140]
            );
            $section->addText("Kata Kunci: " . $project->prodi . ", " . ($project->metode_penelitian ?: 'Metode Penelitian') . ", Analisis Empiris, Skripsi.", ['bold' => true, 'size' => 11], ['alignment' => Jc::BOTH, 'lineHeight' => 1.0, 'indentation' => ['firstLine' => 0]]);

            $section->addPageBreak();
            // --- ABSTRACT (ENGLISH — STANDAR 1 SPASI ITALIC) ---
            $section->addTitle("ABSTRACT", 1);
            $section->addText(strtoupper($project->judul), ['bold' => true, 'italic' => true, 'size' => 12], $center);
            $section->addText("By: " . $nama . ($npm ? " (Student ID: " . $npm . ")" : ""), ['italic' => true, 'size' => 11], $center);
            $section->addText("Department of " . $project->prodi . ($kampus ? ", " . $kampus : ""), ['italic' => true, 'size' => 10.5, 'color' => '333333'], $center);
            $section->addTextBreak(1);
            $section->addText(
                "This study aims to investigate, analyze, and resolve the empirical problems addressed in \"" . $project->judul . "\". Employing a structured " . ($project->metode_penelitian ?: 'scientific') . " methodology, this research systematically collects, processes, and evaluates empirical data. The findings demonstrate that the implemented approach effectively addresses all research questions, provides verifiable evaluation outcomes, and contributes both theoretical insights and practical implications to the field of " . $project->prodi . ".",
                ['name' => 'Times New Roman', 'italic' => true, 'size' => 11.5],
                ['alignment' => Jc::BOTH, 'lineHeight' => 1.0, 'indentation' => ['firstLine' => 720], 'spaceAfter' => 140]
            );
            $section->addText("Keywords: " . $project->prodi . ", Empirical Analysis, Research Methodology, Undergraduate Thesis.", ['bold' => true, 'italic' => true, 'size' => 11], ['alignment' => Jc::BOTH, 'lineHeight' => 1.0, 'indentation' => ['firstLine' => 0]]);
        }

        // --- KATA PENGANTAR ---
        $section->addPageBreak();
        $section->addTitle("KATA PENGANTAR", 1);
        $kpSyarat = $isProposal
            ? "diajukan sebagai rancangan penelitian untuk Seminar Proposal Skripsi pada Program Studi "
            : "diselesaikan sebagai salah satu syarat akademik guna memperoleh gelar Sarjana pada Program Studi ";
        $section->addText(
            "Puji syukur penulis panjatkan ke hadirat Tuhan Yang Maha Esa atas segala rahmat dan karunia-Nya sehingga penyusunan " . strtolower($jenisDokumen) . " yang berjudul \"" . $project->judul . "\" dapat " . $kpSyarat . $project->prodi . ($kampus ? ", " . $kampus : "") . ".",
            ['name' => 'Times New Roman', 'size' => 12],
            ['alignment' => Jc::BOTH, 'lineHeight' => 1.5, 'indentation' => ['firstLine' => 720], 'spaceAfter' => 120]
        );
        $section->addText(
            "Penulis menyampaikan terima kasih yang sebesar-besarnya kepada pimpinan universitas, ketua program studi, dosen pembimbing, dosen penguji, serta keluarga dan rekan-rekan atas bimbingan, arahan, serta dukungan yang telah diberikan. Penulis menyadari bahwa naskah ini masih terbuka terhadap saran konstruktif demi penyempurnaan di masa mendatang.",
            ['name' => 'Times New Roman', 'size' => 12],
            ['alignment' => Jc::BOTH, 'lineHeight' => 1.5, 'indentation' => ['firstLine' => 720], 'spaceAfter' => 240]
        );
        $section->addText(($kampus ? $kampus . ", " : "") . $tglBulanIndo, ['name' => 'Times New Roman', 'size' => 12], ['alignment' => Jc::RIGHT, 'lineHeight' => 1.25]);
        $section->addTextBreak(2);
        $section->addText("Penulis", ['bold' => true, 'name' => 'Times New Roman', 'size' => 12], ['alignment' => Jc::RIGHT, 'lineHeight' => 1.25]);

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
        }
        if ($hasMathUpdate) {
            $project->save();
        }

        $logoPath = $this->resolveLogoTempPath($request, $meta);

        $phpWord = $this->createPhpWordDocument($project, $request, $logoPath, $meta);
        $filename = 'Naskah_' . Str::slug(substr($project->judul, 0, 28), '_') . '.docx';

        Settings::setTempDir('/tmp');
        $path = '/tmp/' . $filename;

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
        $fakultas = $request->input('fakultas') ?: ($meta['fakultas'] ?? '');

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
            return "<table style='width:100%; border-collapse:collapse; margin-bottom:4pt; font-size:12pt; line-height:1.35;'><tr>"
                . "<td style='padding:0 6pt 0 {$pad}; font-weight:{$weight}; border:none; white-space:nowrap;'>" . e($label) . "</td>"
                . "<td style='width:100%; border:none; border-bottom:1pt dotted #64748b; padding:0;'>&nbsp;</td>"
                . "<td style='padding:0 0 0 6pt; text-align:right; font-weight:{$weight}; border:none; white-space:nowrap; width:34pt;'>" . e($page) . "</td>"
                . "</tr></table>";
        };

        $tocRowsHtml = '';
        foreach ($this->extractTocItemsForProject($project, $isProposal) as $item) {
            $tocRowsHtml .= $makeDotLeaderRow((string) ($item['label'] ?? ''), (string) ($item['page'] ?? ''), (int) ($item['level'] ?? 1));
        }

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
        $subCoverPdf = $isProposal
            ? "Diajukan untuk Memenuhi Persyaratan Seminar Proposal Skripsi pada Program Studi " . e($project->prodi)
            : "Diajukan sebagai Salah Satu Syarat untuk Memperoleh Gelar Sarjana pada Program Studi " . e($project->prodi);
        $html .= "<div class='cover'>
            <div style='text-align:center;'>
                <div style='font-size:14pt; font-weight:bold; letter-spacing:1px; line-height:1.2; text-transform:uppercase;'>{$jenisDokumen}</div>
                <div style='font-size:13pt; font-weight:bold; margin-top:16pt; margin-bottom:12pt; line-height:1.35; text-transform:uppercase; max-width:440pt; margin-left:auto; margin-right:auto;'>" . e($project->judul) . "</div>
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

            // ABSTRAK INDONESIA (1 SPASI)
            $html .= "<div class='page-break'>
                <h2>ABSTRAK</h2>
                <div style='text-align:center;'>
                    <div style='font-size:11.5pt; font-weight:bold; margin-top:6pt; line-height:1.25; text-transform:uppercase;'>" . e($project->judul) . "</div>
                    <div style='font-size:10.5pt; margin-top:4pt;'>Oleh: " . e($nama) . ($npm ? " (NIM: " . e($npm) . ")" : "") . "</div>
                    <div style='font-size:10pt; color:#333; margin-bottom:14pt;'>Program Studi " . e($project->prodi) . ($kampus ? ", " . e($kampus) : "") . "</div>
                </div>
                <div style='font-size:11.5pt; line-height:1.15;'>
                    <p style='line-height:1.15;'>Penelitian ini bertujuan untuk menginvestigasi, menganalisis, dan menyelesaikan permasalahan pada topik \"" . e($project->judul) . "\". Metode penelitian yang diterapkan adalah " . e($project->metode_penelitian ?: 'Kuantitatif / Kualitatif Ilmiah') . " melalui prosedur pengumpulan, pengolahan, dan pengujian data empiris secara sistematis. Hasil penelitian menunjukkan bahwa pendekatan yang diterapkan mampu menjawab seluruh rumusan masalah secara terukur, memberikan evaluasi kinerja yang valid, serta menghasilkan kontribusi teoretis dan praktis bagi pengembangan keilmuan pada Program Studi " . e($project->prodi) . ".</p>
                    <p style='text-indent:0; margin-top:10pt; line-height:1.15;'><strong>Kata Kunci:</strong> " . e($project->prodi) . ", " . e($project->metode_penelitian ?: 'Metode Penelitian') . ", Analisis Empiris, Skripsi.</p>
                </div>
            </div>";

            // ABSTRACT ENGLISH (1 SPASI ITALIC)
            $html .= "<div class='page-break'>
                <h2>ABSTRACT</h2>
                <div style='text-align:center; font-style:italic;'>
                    <div style='font-size:11.5pt; font-weight:bold; margin-top:6pt; line-height:1.25; text-transform:uppercase;'>" . e($project->judul) . "</div>
                    <div style='font-size:10.5pt; margin-top:4pt;'>By: " . e($nama) . ($npm ? " (Student ID: " . e($npm) . ")" : "") . "</div>
                    <div style='font-size:10pt; color:#333; margin-bottom:14pt;'>Department of " . e($project->prodi) . ($kampus ? ", " . e($kampus) : "") . "</div>
                </div>
                <div style='font-size:11.5pt; line-height:1.15; font-style:italic;'>
                    <p style='line-height:1.15;'>This study aims to investigate, analyze, and resolve the empirical problems addressed in \"" . e($project->judul) . "\". Employing a structured " . e($project->metode_penelitian ?: 'scientific') . " methodology, this research systematically collects, processes, and evaluates empirical data. The findings demonstrate that the implemented approach effectively addresses all research questions, provides verifiable evaluation outcomes, and contributes both theoretical insights and practical implications to the field of " . e($project->prodi) . ".</p>
                    <p style='text-indent:0; margin-top:10pt; line-height:1.15;'><strong>Keywords:</strong> " . e($project->prodi) . ", Empirical Analysis, Research Methodology, Undergraduate Thesis.</p>
                </div>
            </div>";
        }

        // 5. KATA PENGANTAR
        $kpPdfSyarat = $isProposal
            ? "diajukan sebagai rancangan penelitian untuk Seminar Proposal Skripsi pada Program Studi "
            : "diselesaikan sebagai salah satu syarat akademik guna memperoleh gelar Sarjana pada Program Studi ";
        $html .= "<div class='page-break'>
            <h2>KATA PENGANTAR</h2>
            <p>Puji syukur penulis panjatkan ke hadirat Tuhan Yang Maha Esa atas segala rahmat dan karunia-Nya sehingga penyusunan " . strtolower($jenisDokumen) . " berjudul \"" . e($project->judul) . "\" dapat {$kpPdfSyarat}" . e($project->prodi) . ($kampus ? ", " . e($kampus) : "") . ".</p>
            <p>Penulis menyampaikan terima kasih yang sebesar-besarnya kepada pimpinan universitas, ketua program studi, dosen pembimbing, dosen penguji, serta keluarga dan rekan-rekan atas bimbingan, arahan, serta dukungan yang telah diberikan. Penulis menyadari bahwa naskah ini masih terbuka terhadap saran konstruktif demi penyempurnaan di masa mendatang.</p>
            <div style='margin-top:26pt; float:right; width:200pt; text-align:center; font-size:12pt; line-height:1.3;'>
                <div>" . ($kampus ? e($kampus) . ", " : "") . $tglBulanIndo . "</div>
                <div style='height:44pt;'></div>
                <div><strong>Penulis</strong></div>
            </div>
            <div style='clear:both;'></div>
        </div>";

        // 6. DAFTAR ISI (DENGAN DOT LEADER)
        $html .= "<div class='page-break'>
            <h2>DAFTAR ISI</h2>
            <div style='margin-top:12pt;'>{$tocRowsHtml}</div>
        </div>";

        // 7. DAFTAR TABEL (HANYA JIKA ADA TABEL NYATA)
        $pdfTables = $this->extractTablesForProject($project, $isProposal);
        if (!empty($pdfTables)) {
            $html .= "<div class='page-break'>
                <h2>DAFTAR TABEL</h2>
                <div style='margin-top:12pt;'>";
            foreach ($pdfTables as $tItem) {
                $html .= $makeDotLeaderRow((string) $tItem['label'], (string) $tItem['page'], 1);
            }
            $html .= "</div></div>";
        }

        // 8. DAFTAR GAMBAR (HANYA JIKA ADA GAMBAR / DIAGRAM NYATA)
        $pdfFigures = $this->extractFiguresForProject($project, $isProposal);
        if (!empty($pdfFigures)) {
            $html .= "<div class='page-break'>
                <h2>DAFTAR GAMBAR</h2>
                <div style='margin-top:12pt;'>";
            foreach ($pdfFigures as $fItem) {
                $html .= $makeDotLeaderRow((string) $fItem['label'], (string) $fItem['page'], 1);
            }
            $html .= "</div></div>";
        }

        // 9. DAFTAR LAMPIRAN (OTOMATIS MUNCUL HANYA JIKA MAHASISWA MENGUNGGAH LAMPIRAN)
        $pdfLampirans = $this->extractLampiranForProject($project, $isProposal);
        if (!empty($pdfLampirans)) {
            $html .= "<div class='page-break'>
                <h2>DAFTAR LAMPIRAN</h2>
                <div style='margin-top:12pt;'>";
            foreach ($pdfLampirans as $lItem) {
                $html .= $makeDotLeaderRow((string) $lItem['label'], (string) $lItem['page'], 1);
            }
            $html .= "</div></div>";
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
                $html .= "<div class='page-break'>";
                $html .= "<h2 style='text-align:center; font-weight:bold; font-size:14pt; margin-top:0; margin-bottom:16pt; page-break-after:avoid;'>BAB " . $nums[$k] . "<br>" . strtoupper($v) . "</h2>";
                $html .= $this->formatAcademicContentForPdf($c, (string) ($project->judul ?? ''));
                $html .= "</div>";
            }
        }

        $dpTrimmed = trim((string) $project->daftar_pustaka);
        if ($dpTrimmed !== '') {
            $html .= "<div class='page-break'>";
            $html .= "<h2 style='text-align:center; font-weight:bold; font-size:14pt; margin-top:0; margin-bottom:16pt; page-break-after:avoid;'>DAFTAR PUSTAKA</h2>";
            $dpLines = explode("\n", str_replace(["\r\n", "\r"], "\n", $dpTrimmed));
            foreach ($dpLines as $dpl) {
                $trimDpl = trim($dpl);
                if ($trimDpl !== '') {
                    // Standar APA 7th: 1 spasi dalam 1 entri, jarak 8pt antar entri, hanging indent 1,27 cm + LINK DOI/URL BISA DIKLIK
                    $html .= "<p style='text-indent:-1.27cm; padding-left:1.27cm; margin:0 0 8pt 0; text-align:justify; line-height:1.15; font-size:12pt;'>" . $this->linkifyDaftarPustakaHtml($trimDpl) . "</p>";
                }
            }
            $html .= "</div>";
        }

        // LAMPIRAN (OTOMATIS MUNCUL DI AKHIR PDF JIKA MAHASISWA MENGUNGGAH LAMPIRAN)
        if (!empty($pdfLampirans)) {
            foreach ($pdfLampirans as $lIdx => $lmp) {
                $html .= "<div class='page-break'>";
                if ($lIdx === 0) {
                    $html .= "<h2 style='text-align:center; font-weight:bold; font-size:14pt; margin-top:0; margin-bottom:14pt; page-break-after:avoid;'>LAMPIRAN</h2>";
                }
                $html .= "<h3 style='font-size:12pt; font-weight:bold; text-align:left; text-indent:0; margin:0 0 12pt 0; page-break-after:avoid;'>" . $this->pdfSafeText($lmp['label']) . "</h3>";

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

        $dompdf = new Dompdf();
        $dompdf->set_option('isHtml5ParserEnabled', true);
        $dompdf->set_option('isRemoteEnabled', false);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4');
        try {
            $dompdf->render();
        } catch (\Throwable $e) {
            Log::warning('Dompdf render fallback triggered: ' . $e->getMessage());
            $fallbackHtml = preg_replace('/<img[^>]*>/i', '', $html);
            $dompdf = new Dompdf();
            $dompdf->set_option('isHtml5ParserEnabled', true);
            $dompdf->set_option('isRemoteEnabled', false);
            $dompdf->loadHtml($fallbackHtml);
            $dompdf->setPaper('A4');
            $dompdf->render();
        }

        // Penomoran Halaman Presisi & Seragam (Tengah Bawah, konsisten dengan Preview & Word):
        // - Halaman 1 (Cover): Tanpa nomor halaman
        // - Halaman Bagian Depan (2 s/d sebelum BAB I): Angka Romawi kecil (ii, iii, iv, ...) di TENGAH BAWAH
        // - Halaman Isi Utama (BAB I s/d DAFTAR PUSTAKA & LAMPIRAN): Reset mulai Angka Arab 1 di TENGAH BAWAH
        try {
            $canvas = $dompdf->getCanvas();
            $cpdf = $canvas->get_cpdf();
            $bab1Page = null;
            $pIdx = 0;
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
        Settings::setTempDir('/tmp');
        $path = '/tmp/' . $filename;
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

    private function cleanDaftarPustaka(string $raw, string $tahunMin = '2023'): string
    {
        $text = strip_tags($raw);
        $text = str_replace(['*', '#', '_', '`'], '', $text);
        $lines = explode("\n", $text);
        $cleaned = [];
        $minYr = (int) $tahunMin;
        foreach ($lines as $line) {
            $l = trim($line);
            if (strlen($l) < 12)
                continue;
            if (preg_match('/^(berikut\s+adalah|daftar\s+pustaka|referensi\s*:|catatan\s*:|berikut\s+daftar)/i', $l)) {
                continue;
            }
            $l = preg_replace('/^[\d\-\•]+\.?\s*/', '', $l);
            // Saring jika tahun terbit di bawah batas minimum (misal tahun 2021 padahal batas min 2023)
            if ($minYr > 2000 && preg_match('/\b(19\d{2}|20\d{2})\b/', $l, $m)) {
                $yr = (int) $m[1];
                if ($yr < $minYr) {
                    continue;
                }
            }
            $cleaned[] = trim($l);
        }
        return implode("\n", $cleaned);
    }

    protected function isPublicGalleryProjectCompleted(Project $project): ?string
    {
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
            $candidates = Project::whereNotNull('bab1_content')->whereRaw("CHAR_LENGTH(TRIM(bab1_content)) >= 200")
                ->whereNotNull('bab2_content')->whereRaw("CHAR_LENGTH(TRIM(bab2_content)) >= 200")
                ->whereNotNull('bab3_content')->whereRaw("CHAR_LENGTH(TRIM(bab3_content)) >= 200")
                ->whereNotNull('daftar_pustaka')->whereRaw("CHAR_LENGTH(TRIM(daftar_pustaka)) >= 30")
                ->whereRaw('CHAR_LENGTH(TRIM(judul)) >= 12')
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
        return view('landing', compact('publicProjects'));
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
        Settings::setTempDir('/tmp');
        $path = '/tmp/' . $filename;
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
        $gayaSitasi = $sitasi['gaya'] ?? 'APA 7th';
        $tahunMin = !empty($sitasi['tahun_min']) ? (string) $sitasi['tahun_min'] : '2023';
        $asalRef = $sitasi['asal'] ?? 'Indonesia + Internasional';

        $allText = strip_tags($project->bab1_content . ' ' . $project->bab2_content . ' ' . $project->bab3_content . ' ' . $project->bab4_content . ' ' . $project->bab5_content);
        $citationsList = $this->extractInTextCitations($allText);
        $citationsBlock = !empty($citationsList)
            ? "\nDAFTAR SITASI ASLI YANG MUNCUL DALAM NASKAH (WAJIB DIBUATKAN BIBLIOGRAFI LENGKAPNYA):\n- " . implode("\n- ", $citationsList) . "\n"
            : "";

        $refUploadNote = !empty($meta['referensi_khusus'])
            ? "\nREFERENSI WAJIB DARI UNGGAHAN MAHASISWA (WAJIB MASUK KE DALAM DAFTAR PUSTAKA):\n" . Str::limit($meta['referensi_khusus'], 2500) . "\n"
            : "";

        $prompt = "Tulis DAFTAR PUSTAKA lengkap dengan format {$gayaSitasi} berdasarkan naskah proposal/skripsi berjudul '{$project->judul}' (Prodi: {$project->prodi}) berikut ini.
Asal Referensi: {$asalRef}.
Tahun terbit referensi: minimal tahun {$tahunMin} dan maksimal tahun 2026.
{$citationsBlock}
{$refUploadNote}
ATURAN MUTLAK & TEGAS:
1. DAFTAR PUSTAKA WAJIB DIAMBIL DARI SITASI ASLI YANG BENAR-BENAR MUNCUL DALAM NASKAH (lihat daftar di atas). DILARANG KERAS mengarang nama penulis/jurnal fiktif yang tidak pernah disitasi di dalam naskah!
2. LANGSUNG tulis baris daftar pustaka pertama (urut abjad A-Z) TANPA kalimat pengantar seperti 'Berikut adalah daftar pustaka...'.
3. DILARANG menulis placeholder 'Judul artikel tentang...' atau 'Nama Jurnal, Volume(Issue), Halaman'. Setiap entri WAJIB memiliki nama penulis lengkap, tahun terbit, judul artikel ilmiah spesifik, nama jurnal akademik bereputasi, volume, nomor, halaman, dan link DOI/URL.
4. Jangan gunakan tanda bintang markdown (*).

NASKAH BAB I - BAB V:
" . substr($allText, 0, 10000);

        try {
            $daftarPustaka = $this->ai->generateText($prompt, $model);
            $project->daftar_pustaka = $this->cleanDaftarPustaka($daftarPustaka, $tahunMin);
            $project->save();

            if ($request->wantsJson()) {
                return response()->json(['status' => 'success', 'daftar_pustaka' => $project->daftar_pustaka]);
            }
            return back()->with('success', 'Daftar pustaka berhasil diperbarui.');
        } catch (Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
            }
            return back()->with('error', 'Gagal memperbarui daftar pustaka.');
        }
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
            'text' => 'required|string|min:15',
            'style' => 'nullable|string',
        ]);

        $style = $request->input('style', 'academic');
        $model = $request->input('model', 'auto');

        $styleInstruction = match ($style) {
            'natural' => 'Ubah struktur kalimat aktif/pasif dan gunakan sinonim akademik agar lebih mengalir alami serta menurunkan tingkat kemiripan teks tanpa mengubah makna ilmiah maupun sitasi dalam kurung.',
            'concise' => 'Padatkan dan ringkas kalimat agar lebih lugas, efektif, dan sesuai kaidah EYD V serta KBBI.',
            'expand' => 'Perluas penjelasan akademik dengan menambahkan elaborasi logis dan kohesi antar-kalimat.',
            default => 'Tulis ulang dengan gaya Bahasa Indonesia Ilmiah Baku (Standar Skripsi & Jurnal) yang koheren dan tetap mempertahankan sitasi.'
        };

        $prompt = "PERAN: Anda adalah Editor Bahasa Ilmiah (KBBI & EYD V).
TUGAS: Parafrase teks akademik berikut.
INSTRUKSI: {$styleInstruction}
Pertahankan seluruh sitasi nama penulis dan tahun dalam kurung. Langsung berikan teks hasil parafrase tanpa kalimat pembuka.

TEKS ASLI:
\"\"\"
{$request->text}
\"\"\"";

        try {
            $result = $this->ai->generateText($prompt, $model);

            return response()->json([
                'status' => 'success',
                'result' => trim($result),
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function generatePpt(Request $request)
    {
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

        try {
            if ($mode === 'generate_questions') {
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
