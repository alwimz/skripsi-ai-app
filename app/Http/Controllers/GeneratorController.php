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
                        $xml = str_replace(['</w:p>', '</w:tr>', '<w:br/>'], ["\n", "\n", "\n"], $xml);
                        return trim(preg_replace('/\s+/', ' ', strip_tags($xml)));
                    }
                }
            }

            if (in_array($ext, ['txt', 'md', 'csv'])) {
                return trim(file_get_contents($path));
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

                        if (preg_match_all('/\(([^()]{2,})\)\s*Tj/s', $source, $tjMatches)) {
                            foreach ($tjMatches[1] as $txt) {
                                $extracted[] = $txt;
                            }
                        }
                        if (preg_match_all('/\[(.*?)\]\s*TJ/s', $source, $tjArrMatches)) {
                            foreach ($tjArrMatches[1] as $arrTxt) {
                                if (preg_match_all('/\(([^()]+)\)/', $arrTxt, $subMatches)) {
                                    $extracted[] = implode('', $subMatches[1]);
                                }
                            }
                        }
                    }
                }

                $text = trim(implode(' ', $extracted));
                $text = preg_replace('/[^\P{C}\n]+/u', ' ', $text);

                if (strlen($text) > 80) {
                    return Str::limit($text, 15000, '');
                }

                if (preg_match_all('/[a-zA-Z0-9\s,\.\-\(\)\:\;\/]{6,}/', $raw, $asciiMatches)) {
                    $fallback = implode(' ', $asciiMatches[0]);
                    $fallback = preg_replace('/\b(obj|endobj|stream|endstream|xref|trailer|Type|Font|Page|Filter|FlateDecode|Length)\b/i', '', $fallback);
                    return Str::limit(trim(preg_replace('/\s+/', ' ', $fallback)), 12000, '');
                }
            }

            if ($ext === 'doc') {
                $raw = file_get_contents($path);
                if (preg_match_all('/[a-zA-Z0-9\s,\.\-\(\)\:\;]{8,}/', $raw, $docMatches)) {
                    return Str::limit(trim(implode(' ', $docMatches[0])), 12000, '');
                }
            }
        } catch (Throwable $e) {
            Log::warning('File extraction warning: ' . $e->getMessage());
        }

        return 'Dokumen referensi: ' . $file->getClientOriginalName();
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
        } catch (Throwable $e) {}

        $history = Project::where('user_id', $user->id)->orderBy('updated_at', 'desc')->get();
        try {
            $apiKeys = DB::table('api_keys')->where('user_id', $user->id)->orderBy('created_at', 'desc')->get();
        } catch (Exception $e) {
            $apiKeys = collect([]);
        }

        return view('pre_generator', compact('history', 'apiKeys', 'user'));
    }

    public function addApiKey(Request $request)
    {
        $request->validate([
            'key' => 'required|string|min:10',
            'provider' => 'nullable|in:gemini,groq'
        ]);

        try {
            $cleanKey = trim($request->key);
            if (DB::table('api_keys')->where('key', $cleanKey)->exists()) {
                return redirect()->back()->with('error', 'API Key tersebut sudah terdaftar.');
            }

            $data = [
                'user_id' => Auth::id(),
                'key' => $cleanKey,
                'is_active' => true,
                'usage_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('api_keys', 'provider')) {
                $provider = $request->provider ?: (str_starts_with($cleanKey, 'gsk_') ? 'groq' : 'gemini');
                $data['provider'] = $provider;
            }

            DB::table('api_keys')->insert($data);
            return redirect()->back()->with('success', 'API Key berhasil disimpan.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan API Key: ' . $e->getMessage());
        }
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

    public function claimReward(Request $request)
    {
        $type = $request->type;
        $user = User::find(Auth::id());
        $reward = ($type === 'login') ? 2 : 5;
        if ($reward > 0) {
            $user->credits += $reward;
            $user->save();
            return response()->json(['status' => 'success', 'message' => "+$reward Token berhasil ditambahkan", 'new_credits' => $user->credits]);
        }
        return response()->json(['status' => 'error', 'message' => 'Gagal klaim token.']);
    }

    public function startProject(Request $request)
    {
        $request->validate([
            'judul' => 'required|string',
            'prodi' => 'required|string',
            'metode_penelitian' => 'required|string',
        ]);

        $cleanJudul = trim($request->judul);

        // Cegah duplikat proyek dengan judul yang sama
        $existing = Project::where('user_id', Auth::id())
            ->whereRaw('LOWER(judul) = ?', [strtolower($cleanJudul)])
            ->orderBy('id', 'desc')
            ->first();

        if ($existing) {
            return redirect()->route('app.index', ['id' => $existing->id]);
        }

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

        // Encode logo kampus ke base64 jika diunggah saat pembuatan proyek
        $logoBase64 = $request->input('logo_base64', '');
        if ($request->hasFile('logo_kampus') && $request->file('logo_kampus')->isValid()) {
            $lf = $request->file('logo_kampus');
            $mime = $lf->getMimeType() ?: 'image/png';
            $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($lf->getRealPath()));
        }

        $meta = [
            'nama' => trim($request->input('nama', Auth::user()->name ?? '')),
            'nim' => trim($request->input('nim', '')),
            'kampus' => trim($request->input('kampus', '')),
            'fakultas' => trim($request->input('fakultas', '')),
            'logo_base64' => $logoBase64,
            'jenis_penelitian' => $request->input('jenis_penelitian', 'Skripsi (S1)'),
            'tahap_pengerjaan' => $request->input('tahap_pengerjaan', 'Skripsi Penuh (Bab I–V)'),
            'struktur_kustom' => trim($request->input('struktur_kustom', '')),
            'data_awal' => trim($request->input('data_awal', '')),
            'pedoman_text' => Str::limit($guidelineText, 8000, ''),
            'referensi_khusus' => Str::limit($refFilesText, 5000, ''),
            'sitasi' => [
                'tahun_min' => $request->input('tahun_min', '2021'),
                'asal' => $request->input('asal_referensi', 'Indonesia + Internasional'),
                'gaya' => $request->input('gaya_sitasi', 'APA 7th'),
                'bahasa' => $request->input('bahasa_penulisan', 'Indonesia'),
                'sumber' => $request->input('sumber_referensi', ['Umum', 'Terindeks Sinta']),
            ],
            'format_nomor' => $request->input('format_nomor', '1.1'),
        ];

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
        $user = Auth::user();
        if ($user->credits < 1) {
            return response()->json(['status' => 'error', 'message' => 'Token habis! Silakan klaim token gratis di bagian atas.']);
        }

        $request->validate(['topik' => 'required|string']);
        $prodi = $request->input('prodi') ?: 'Umum';
        $metode = $request->input('metode') ?: 'Kuantitatif / Kualitatif / R&D';
        $model = $request->input('model', 'auto');

        $prompt = "Sebagai Dosen Pembimbing Skripsi Senior, berikan 6 rekomendasi judul skripsi S1 terbaik untuk:
- Topik / Bidang Penelitian: {$request->topik}
- Metodologi: {$metode}
- Program Studi: {$prodi}

Syarat:
1. Spesifik, relevan dengan riset terbaru, dan memiliki variabel atau objek kajian yang jelas.
2. Gunakan Bahasa Indonesia akademik baku.
3. Output HANYA daftar judul (1 baris 1 judul), tanpa kata pengantar dan tanpa nomor urut.";

        try {
            $content = $this->ai->generateText($prompt, $model);
            $titles = array_filter(explode("\n", $content), fn($t) => strlen(trim($t)) > 15);
            $cleanTitles = array_map(function ($t) {
                return trim(preg_replace('/^[\d\-\.\*\s\"]+|[\"]+$/', '', trim($t)));
            }, $titles);

            $user->decrement('credits');

            return response()->json([
                'status' => 'success',
                'data' => array_values(array_slice($cleanTitles, 0, 8)),
                'new_credits' => $user->credits
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function findJournals(Request $request)
    {
        $user = Auth::user();
        if ($user->credits < 1) {
            return response()->json(['status' => 'error', 'message' => 'Token habis! Silakan klaim token gratis terlebih dahulu.']);
        }

        $request->validate(['keyword' => 'required|string']);
        $keyword = trim($request->keyword);
        $model = $request->input('model', 'auto');

        $journals = [];

        try {
            $oaResponse = Http::timeout(12)->get('https://api.openalex.org/works', [
                'search' => $keyword,
                'filter' => 'is_oa:true,from_publication_date:2019-01-01',
                'per-page' => 8,
            ]);

            if ($oaResponse->successful()) {
                $results = $oaResponse->json()['results'] ?? [];
                foreach ($results as $work) {
                    $title = trim($work['title'] ?? '');
                    if (empty($title)) continue;

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
                        ?? 'Jurnal Ilmiah Open Access';

                    $pdfUrl = $work['open_access']['oa_url']
                        ?? $work['primary_location']['pdf_url']
                        ?? $work['primary_location']['landing_page_url']
                        ?? $work['doi']
                        ?? ('https://scholar.google.com/scholar?q=' . urlencode($title . ' filetype:pdf'));

                    $doiUrl = $work['doi'] ?? ('https://scholar.google.com/scholar?q=' . urlencode($title));

                    $journals[] = [
                        'title' => $title,
                        'author' => $authorStr,
                        'journal' => $venue,
                        'year' => (string) $year,
                        'pdf_url' => $pdfUrl,
                        'doi_url' => $doiUrl,
                        'citation' => "{$authorStr} ({$year}). {$title}. {$venue}. " . ($work['doi'] ?? ''),
                        'is_real_oa' => true,
                    ];
                }
            }
        } catch (Throwable $e) {
            Log::warning('OpenAlex search fallback: ' . $e->getMessage());
        }

        if (count($journals) < 5) {
            try {
                $needed = 6 - count($journals);
                $prompt = "Berikan {$needed} referensi jurnal ilmiah (2020-2025) yang relevan dengan topik '{$keyword}'.
Format wajib per baris: Judul Artikel|Nama Penulis|Nama Jurnal|Tahun
Tanpa nomor urut dan tanpa kalimat pengantar.";

                $content = $this->ai->generateText($prompt, $model);
                $lines = array_filter(explode("\n", $content), fn($t) => !empty(trim($t)));
                foreach ($lines as $line) {
                    $parts = explode('|', $line);
                    if (count($parts) >= 3) {
                        $jTitle = trim(preg_replace('/^[\d\.\-\s]+/', '', $parts[0]));
                        $jAuthor = trim($parts[1]);
                        $jVenue = trim($parts[2]);
                        $jYear = isset($parts[3]) ? trim($parts[3]) : '2023';
                        $journals[] = [
                            'title' => $jTitle,
                            'author' => $jAuthor,
                            'journal' => $jVenue,
                            'year' => $jYear,
                            'pdf_url' => 'https://scholar.google.com/scholar?q=' . urlencode($jTitle . ' ' . $jAuthor . ' filetype:pdf'),
                            'doi_url' => 'https://scholar.google.com/scholar?q=' . urlencode($jTitle),
                            'citation' => "{$jAuthor} ({$jYear}). {$jTitle}. {$jVenue}.",
                            'is_real_oa' => false,
                        ];
                    }
                }
            } catch (Exception $e) {
                if (empty($journals)) {
                    return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
                }
            }
        }

        $user->decrement('credits');

        return response()->json([
            'status' => 'success',
            'data' => array_slice($journals, 0, 8),
            'new_credits' => $user->credits
        ]);
    }

    public function askAi(Request $request)
    {
        $request->validate(['message' => 'required']);
        $model = $request->input('model', 'auto');

        try {
            $prompt = "PERAN: Anda adalah Dosen Pembimbing Akademik yang suportif, jelas, dan sistematis.
Jawab pertanyaan mahasiswa berikut secara ringkas, ilmiah, dan mudah dipahami:
\"{$request->message}\"";

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
        $tahunMin = $sitasi['tahun_min'] ?? '2020';
        $asalRef = $sitasi['asal'] ?? 'Indonesia + Internasional';
        $gayaSitasi = $sitasi['gaya'] ?? 'APA 7th';
        $bahasa = $sitasi['bahasa'] ?? 'Indonesia';
        $sumberArr = is_array($sitasi['sumber'] ?? null) ? implode(', ', $sitasi['sumber']) : 'Umum, Terindeks Sinta';

        $extraContext = '';
        if (!empty($meta['data_awal'])) {
            $extraContext .= "\nDATA AWAL / FENOMENA LAPANGAN MAHASISWA (WAJIB DIMASUKKAN KE NASKAH):\n\"\"\"\n{$meta['data_awal']}\n\"\"\"\n";
        }
        if (!empty($meta['pedoman_text'])) {
            $extraContext .= "\nPEDOMAN KAMPUS MAHASISWA:\n\"\"\"\n" . Str::limit($meta['pedoman_text'], 3000) . "\n\"\"\"\n";
        }
        if (!empty($meta['struktur_kustom'])) {
            $extraContext .= "\nSTRUKTUR SUB-BAB KUSTOM DARI MAHASISWA (IKUTI URUTAN INI):\n\"\"\"\n{$meta['struktur_kustom']}\n\"\"\"\n";
        }
        if (!empty($meta['referensi_khusus'])) {
            $extraContext .= "\nREFERENSI WAJIB DARI UNGGAHAN MAHASISWA:\n\"\"\"\n" . Str::limit($meta['referensi_khusus'], 2500) . "\n\"\"\"\n";
        }

        return "PERAN: Anda adalah Profesor dan Pembimbing Tugas Akhir Senior di Perguruan Tinggi Indonesia.

KONTEKS PENELITIAN:
- Jenis Karya Ilmiah: " . ($meta['jenis_penelitian'] ?? 'Skripsi (S1)') . "
- Judul: {$project->judul}
- Program Studi: {$project->prodi}
- Metode Penelitian: {$project->metode_penelitian}
- Pengaturan Sitasi: Gaya {$gayaSitasi} | Tahun Terbit Minimum: {$tahunMin} | Asal Referensi: {$asalRef} ({$sumberArr}) | Bahasa Penulisan: {$bahasa}
{$extraContext}
ATURAN PENULISAN AKADEMIK (SANGAT KETAT):
1. Gaya Bahasa: Gunakan Bahasa {$bahasa} ilmiah baku sesuai kaidah akademik, kohesif, objektif, dan mendalam.
2. Kedalaman Materi: Setiap sub-bab wajib ditulis minimal 3-5 paragraf panjang (450-750 kata per sub-bab).
3. Sitasi Ilmiah: WAJIB menyertakan sitasi dalam teks (in-text citation gaya {$gayaSitasi}, misalnya: Menurut Pratama ({$tahunMin}) atau (Wijaya et al., 2024)) dengan tahun terbit minimal {$tahunMin}.
4. Format Sub-bab: Tulis nomor sub-bab pada baris tersendiri menggunakan penomoran desimal (contoh: 1.1 Latar Belakang Masalah).
5. Tanpa Simbol Markdown: Jangan gunakan tanda bintang (**) atau pagar (###). Jangan tulis kalimat pengantar AI. Langsung mulai dari sub-bab pertama.";
    }

    private function promptBab(string $bab, Project $project): string
    {
        $master = $this->promptMaster($project);
        $metodeLower = strtolower($project->metode_penelitian ?? '');
        $isRnD = str_contains($metodeLower, 'r&d') || str_contains($metodeLower, 'waterfall') || str_contains($metodeLower, 'agile') || str_contains($metodeLower, 'prototype') || str_contains($metodeLower, 'sistem') || str_contains($metodeLower, 'rekayasa');
        $isKualitatif = str_contains($metodeLower, 'kualitatif') || str_contains($metodeLower, 'normatif') || str_contains($metodeLower, 'pustaka');

        $context = "";
        if ($bab == 'bab2') $context = "KONTEKS BAB 1: " . Str::limit(strip_tags($project->bab1_content), 1200);
        elseif ($bab == 'bab3') $context = "KONTEKS BAB 1 & 2: " . Str::limit(strip_tags($project->bab1_content . ' ' . $project->bab2_content), 1200);
        elseif ($bab == 'bab4') $context = "KONTEKS BAB 1 & 3: " . Str::limit(strip_tags($project->bab1_content . ' ' . $project->bab3_content), 1500);
        elseif ($bab == 'bab5') {
            $context = "HASIL PENELITIAN BAB 4: " . Str::limit(strip_tags($project->bab4_content), 1500);
            $allText = $project->bab1_content . " " . $project->bab2_content . " " . $project->bab3_content . " " . $project->bab4_content;
            $context .= "\n\n=== TEKS DARI BAB 1 SAMPAI BAB 4 ===\n" . Str::limit(strip_tags($allText), 12000);
        }

        $bab3Skill = match (true) {
            $isRnD => "
Sub-bab standar Metode R&D / Pengembangan Sistem ({$project->metode_penelitian}):
3.1 Metode Pengembangan Sistem
3.2 Tempat dan Waktu Penelitian
3.3 Teknik Pengumpulan Data
3.4 Analisis Sistem Berjalan dan Kebutuhan Sistem (Fungsional & Non-Fungsional)
3.5 Perancangan Sistem (Arsitektur, UML Use Case/Activity/Class Diagram, Basis Data/ERD, & Antarmuka)
3.6 Metode Pengujian Sistem (Black-Box Testing & User Acceptance Test)",
            $isKualitatif => "
Sub-bab standar Metode Kualitatif ({$project->metode_penelitian}):
3.1 Pendekatan dan Jenis Penelitian
3.2 Lokasi dan Waktu Penelitian
3.3 Fokus Penelitian
3.4 Sumber Data dan Teknik Penentuan Informan
3.5 Teknik Pengumpulan Data (Observasi, Wawancara Mendalam, Dokumentasi)
3.6 Teknik Pemeriksaan Keabsahan Data (Triangulasi)
3.7 Teknik Analisis Data (Kondensasi Data, Penyajian Data, Penarikan Kesimpulan)",
            default => "
Sub-bab standar Metode Kuantitatif ({$project->metode_penelitian}):
3.1 Jenis dan Desain Penelitian
3.2 Lokasi dan Waktu Penelitian
3.3 Populasi dan Sampel Penelitian
3.4 Definisi Operasional dan Pengukuran Variabel
3.5 Teknik dan Instrumen Pengumpulan Data
3.6 Uji Instrumen Penelitian (Uji Validitas dan Reliabilitas)
3.7 Teknik Analisis Data dan Uji Hipotesis (Uji Asumsi Klasik, Regresi, Uji t, Uji F, R-Square)"
        };

        $bab4Skill = match (true) {
            $isRnD => "
Sub-bab standar Hasil & Pembahasan R&D ({$project->metode_penelitian}):
4.1 Gambaran Umum Objek Penelitian dan Analisis Sistem Berjalan
4.2 Hasil Perancangan dan Implementasi Sistem
4.3 Hasil Pengujian Sistem (Black-Box & Kelayakan Pengguna)
4.4 Pembahasan Hasil Penelitian",
            $isKualitatif => "
Sub-bab standar Hasil & Pembahasan Kualitatif ({$project->metode_penelitian}):
4.1 Gambaran Umum Objek Penelitian dan Profil Informan
4.2 Paparan Data dan Temuan Penelitian
4.3 Pembahasan Temuan Penelitian",
            default => "
Sub-bab standar Hasil & Pembahasan Kuantitatif ({$project->metode_penelitian}):
4.1 Gambaran Umum Objek Penelitian dan Karakteristik Responden
4.2 Hasil Analisis Data (Statistik Deskriptif, Validitas, Reliabilitas, Asumsi Klasik, & Uji Hipotesis)
4.3 Pembahasan Hasil Penelitian"
        };

        return match ($bab) {
            'bab1' => $master . "
TUGAS: Tulis Isi BAB I (PENDAHULUAN) secara lengkap.
Sub-bab:
1.1 Latar Belakang Masalah (Fenomena Makro, Kondisi Ideal vs Aktual, Data Awal Penelitian, Kutipan Jurnal Terbaru, Research Gap, dan Alasan Pemilihan Judul - minimal 5 paragraf mendalam)
1.2 Identifikasi dan Batasan Masalah
1.3 Rumusan Masalah
1.4 Tujuan Penelitian
1.5 Manfaat Penelitian (Manfaat Teoretis dan Praktis)
1.6 Sistematika Penulisan",
            'bab2' => $master . "\n$context\n
TUGAS: Tulis Isi BAB II (TINJAUAN PUSTAKA) secara lengkap.
Sub-bab:
2.1 Landasan Teori (Uraikan Grand Theory, Middle Theory, definisi para ahli, dimensi, dan indikator setiap variabel dalam judul)
2.2 Penelitian Terdahulu (Bahas minimal 5 penelitian jurnal terbaru beserta persamaan dan perbedaannya)
2.3 Kerangka Pemikiran
2.4 Hipotesis Penelitian / Proposisi Penelitian",
            'bab3' => $master . "\n$context\n
TUGAS: Tulis Isi BAB III (METODOLOGI PENELITIAN) secara lengkap.
$bab3Skill",
            'bab4' => $master . "\n$context\n
TUGAS: Tulis Isi BAB IV (HASIL PENELITIAN DAN PEMBAHASAN) secara lengkap.
$bab4Skill",
            'bab5' => $master . "\n$context\n
TUGAS: Tulis Isi BAB V (PENUTUP) beserta DAFTAR PUSTAKA.
Sub-bab:
5.1 Kesimpulan (Menjawab rumusan masalah Bab 1 secara tegas berdasarkan hasil Bab 4)
5.2 Saran (Saran akademis dan praktis)

Setelah sub-bab 5.2 selesai, buat baris baru bertuliskan persis:
===DAFTAR PUSTAKA===
Lalu tuliskan minimal 15 referensi ilmiah urut abjad A-Z yang mencakup seluruh nama penulis yang dikutip pada Bab 1 hingga Bab 4.",
            default => $master
        };
    }

    // =========================================================================
    // 4. GENERATE BAB, REVISI BAB (TEKS + GAMBAR CORETAN + FILE), & SIMPAN MANUAL
    // =========================================================================

    private function cleanTextKeepFormat($text)
    {
        if (empty($text)) return '';
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
            'BAB I', 'BAB 1', 'PENDAHULUAN', 'BAB I PENDAHULUAN',
            'BAB II', 'BAB 2', 'TINJAUAN PUSTAKA', 'BAB II TINJAUAN PUSTAKA',
            'BAB III', 'BAB 3', 'METODOLOGI', 'METODOLOGI PENELITIAN', 'METODE PENELITIAN',
            'BAB IV', 'BAB 4', 'HASIL DAN PEMBAHASAN', 'HASIL PENELITIAN DAN PEMBAHASAN',
            'BAB V', 'BAB 5', 'PENUTUP', 'BAB V PENUTUP'
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
            if ($isTitle) continue;

            $cleanLines[] = $line;
        }
        return implode("\n", $cleanLines);
    }

    public function generateChapter(Request $request, $id)
    {
        $user = Auth::user();
        if ($user->credits <= 0) {
            return response()->json(['error' => 'Token habis! Silakan klaim token gratis terlebih dahulu.'], 403);
        }

        $project = Project::where('user_id', Auth::id())->findOrFail($id);
        $chapter = $request->chapter;
        $model = $request->input('model', 'auto');

        try {
            $finalPrompt = $this->promptBab($chapter, $project);
            $content = $this->ai->generateText($finalPrompt, $model);

            $cleanContent = $this->cleanTextKeepFormat($content);
            $cleanContent = $this->processChapterContent($cleanContent, $chapter);

            if ($chapter === 'bab5') {
                $parts = explode('===DAFTAR PUSTAKA===', $cleanContent);
                if (count($parts) > 1) {
                    $project->bab5_content = trim($parts[0]);
                    $rawDP = strip_tags(end($parts));
                    $linesDP = array_filter(explode("\n", $rawDP), fn($l) => strlen(trim($l)) > 5);
                    $project->daftar_pustaka = implode("\n", array_map('trim', $linesDP));
                } else {
                    $partsRegex = preg_split('/(DAFTAR PUSTAKA|REFERENSI)/i', $cleanContent);
                    if (count($partsRegex) > 1) {
                        $project->bab5_content = trim($partsRegex[0]);
                        $rawDP = strip_tags(end($partsRegex));
                        $linesDP = array_filter(explode("\n", $rawDP), fn($l) => strlen(trim($l)) > 5);
                        $project->daftar_pustaka = implode("\n", array_map('trim', $linesDP));
                    } else {
                        $project->bab5_content = $cleanContent;
                    }
                }
            } else {
                $project->{$chapter . '_content'} = $cleanContent;
                // Ekstrak sitasi otomatis ke daftar pustaka sementara jika daftar pustaka masih kosong
                if (empty($project->daftar_pustaka) && in_array($chapter, ['bab1', 'bab2', 'bab3'])) {
                    try {
                        $allText = strip_tags($project->bab1_content . ' ' . $project->bab2_content . ' ' . $project->bab3_content);
                        if (strlen($allText) > 500) {
                            $dpPrompt = "Buat daftar pustaka APA 7th Edition (urut A-Z, tanpa bintang markdown) dari seluruh sitasi nama & tahun yang muncul di teks berikut:\n" . Str::limit($allText, 5000);
                            $dpRaw = $this->ai->generateText($dpPrompt, $model);
                            $project->daftar_pustaka = trim(str_replace(['*', '#'], '', strip_tags($dpRaw)));
                        }
                    } catch (Throwable $e) {}
                }
            }

            if ($chapter === 'bab2') {
                try {
                    $promptTable = "Buat HTML Table sederhana <table><tr><th>Peneliti & Tahun</th><th>Judul Penelitian</th><th>Metode</th><th>Hasil Temuan</th></tr>...</table> yang merangkum Penelitian Terdahulu dari teks ini: " . Str::limit(strip_tags($cleanContent), 4000);
                    $tableHtml = $this->ai->generateText($promptTable, $model);
                    if (preg_match('/<table.*?<\/table>/is', $tableHtml, $m)) {
                        $project->bab2_tabel_penelitian = $m[0];
                    }
                } catch (Throwable $t) {}
            }

            $project->save();
            $user->decrement('credits');

            return response()->json([
                'status' => 'success',
                'content' => $project->{$chapter . '_content'},
                'daftar_pustaka' => $project->daftar_pustaka,
                'remaining_credits' => $user->credits
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => 'AI Error: ' . $e->getMessage()], 500);
        }
    }

    public function reviseChapter(Request $request, $id)
    {
        $user = Auth::user();
        if ($user->credits <= 0) {
            return response()->json(['error' => 'Token habis! Silakan klaim token gratis terlebih dahulu.'], 403);
        }

        $request->validate([
            'chapter' => 'required|in:bab1,bab2,bab3,bab4,bab5',
            'instruction' => 'nullable|string',
            'image_base64' => 'nullable|string',
            'revision_file' => 'nullable|file|max:15360',
        ]);

        $project = Project::where('user_id', Auth::id())->findOrFail($id);
        $chapter = $request->chapter;
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
            return response()->json(['error' => 'Masukkan catatan revisi, gambar coretan dosen, atau dokumen revisi.'], 422);
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

        $prompt = "PERAN: Anda adalah Profesor Pembimbing Skripsi Senior.
TUGAS: Lakukan REVISI MENDALAM pada {$chapterName} untuk skripsi berjudul \"{$project->judul}\" (Prodi: {$project->prodi}, Metode: {$project->metode_penelitian}).

NASKAH {$chapterName} SAAT INI:
\"\"\"
" . Str::limit(strip_tags($currentContent), 10000) . "
\"\"\"
{$docContext}
INSTRUKSI REVISI DARI MAHASISWA / DOSEN PEMBIMBING:
\"\"\"
" . ($instruction ?: 'Perbaiki dan sempurnakan naskah bab ini sesuai coretan/dokumen terlampir.') . "
\"\"\"

ATURAN REVISI:
1. Jika terdapat gambar coretan dosen terlampir, baca seluruh catatan/koreksi pada gambar tersebut dan terapkan perbaikannya ke dalam naskah.
2. Pertahankan sub-bab yang sudah benar dan perbaiki/tambahkan bagian yang diminta pada instruksi revisi secara lengkap dan mendalam.
3. Gunakan format penomoran sub-bab desimal (misal 1.1, 1.2) pada baris tersendiri, tanpa tanda bintang markdown (**) atau pagar (###), dan tanpa kata pengantar AI.
4. Kembalikan naskah {$chapterName} yang sudah direvisi secara utuh.";

        try {
            $revised = $this->ai->generateWithImage($prompt, $request->image_base64, $model);
            $cleanContent = $this->cleanTextKeepFormat($revised);
            $cleanContent = $this->processChapterContent($cleanContent, $chapter);

            $project->{$chapter . '_content'} = $cleanContent;
            $project->save();
            $user->decrement('credits');

            return response()->json([
                'status' => 'success',
                'content' => $cleanContent,
                'remaining_credits' => $user->credits
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => 'Gagal merevisi bab: ' . $e->getMessage()], 500);
        }
    }

    public function saveChapterManual(Request $request, $id)
    {
        $request->validate([
            'chapter' => 'required|in:bab1,bab2,bab3,bab4,bab5,daftar_pustaka',
            'content' => 'required|string',
        ]);

        $project = Project::where('user_id', Auth::id())->findOrFail($id);
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

    private function parseAndAddContent($section, $htmlContent)
    {
        $text = str_replace(['<p>', '</p>', '<br>', '<br/>'], ["\n", "\n", "\n", "\n"], $htmlContent);
        $lines = explode("\n", $text);

        foreach ($lines as $line) {
            $cleanLine = trim(strip_tags($line));
            if (empty($cleanLine)) continue;

            if (preg_match('/^\d+\.\d+\s+/', $cleanLine)) {
                $section->addTitle($cleanLine, 2);
            } elseif (preg_match('/^\d+\.\d+\.\d+\s+/', $cleanLine)) {
                $section->addText(
                    $cleanLine,
                    ['bold' => true, 'name' => 'Times New Roman', 'size' => 12],
                    ['spaceBefore' => 120, 'spaceAfter' => 120, 'indentation' => ['firstLine' => 0]]
                );
            } else {
                $pStyle = ['alignment' => Jc::BOTH, 'lineHeight' => 1.5, 'indentation' => ['firstLine' => 720]];
                $section->addText($cleanLine, [], $pStyle);
            }
        }
    }

    private function resolveLogoTempPath(Request $request, array $meta = []): ?string
    {
        try {
            if ($request->hasFile('logo_kampus') && $request->file('logo_kampus')->isValid()) {
                $file = $request->file('logo_kampus');
                $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
                $tmpPath = '/tmp/logo_' . uniqid() . '.' . $ext;
                copy($file->getRealPath(), $tmpPath);
                return $tmpPath;
            }
            $base64 = $request->input('logo_base64') ?: ($meta['logo_base64'] ?? '');
            if (!empty($base64)) {
                $clean = preg_replace('#^data:image/\w+;base64,#i', '', $base64);
                $decoded = base64_decode($clean);
                if ($decoded !== false && strlen($decoded) > 100) {
                    $tmpPath = '/tmp/logo_' . uniqid() . '.png';
                    file_put_contents($tmpPath, $decoded);
                    return $tmpPath;
                }
            }
        } catch (Throwable $e) {
            Log::warning('Logo resolve error: ' . $e->getMessage());
        }
        return null;
    }

    private function createPhpWordDocument($project, Request $request, ?string $logoPath = null, array $meta = [])
    {
        $phpWord = new PhpWord();
        Settings::setOutputEscapingEnabled(true);
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(12);

        $marginTop = (float) $request->input('margin_top', 4);
        $marginLeft = (float) $request->input('margin_left', 4);
        $marginBottom = (float) $request->input('margin_bottom', 3);
        $marginRight = (float) $request->input('margin_right', 3);

        $nama = $request->input('nama') ?: ($meta['nama'] ?? 'NAMA MAHASISWA');
        $npm = $request->input('npm') ?: ($meta['nim'] ?? 'NIM / NPM');
        $kampus = $request->input('kampus') ?: ($meta['kampus'] ?? 'UNIVERSITAS');
        $fakultas = $request->input('fakultas') ?: ($meta['fakultas'] ?? '');
        $jenisDokumen = strtoupper($request->input('jenis_dokumen', 'SKRIPSI'));

        $phpWord->setDefaultParagraphStyle([
            'lineHeight' => 1.5,
            'spaceAfter' => Converter::pointToTwip(0)
        ]);

        $phpWord->addTitleStyle(1, ['name' => 'Times New Roman', 'size' => 14, 'bold' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 240, 'indentation' => ['firstLine' => 0]]);
        $phpWord->addTitleStyle(2, ['name' => 'Times New Roman', 'size' => 12, 'bold' => true], ['alignment' => Jc::LEFT, 'spaceBefore' => 120, 'spaceAfter' => 120, 'indentation' => ['firstLine' => 0]]);

        $sectionStyle = [
            'marginTop' => Converter::cmToTwip($marginTop),
            'marginLeft' => Converter::cmToTwip($marginLeft),
            'marginBottom' => Converter::cmToTwip($marginBottom),
            'marginRight' => Converter::cmToTwip($marginRight)
        ];

        $section = $phpWord->addSection($sectionStyle);
        $center = ['alignment' => Jc::CENTER, 'indentation' => ['firstLine' => 0]];
        $justify = ['alignment' => Jc::BOTH];
        $bold14 = ['bold' => true, 'size' => 14];
        $bold12 = ['bold' => true, 'size' => 12];

        // --- HALAMAN SAMPUL (COVER) ---
        $section->addText($jenisDokumen, $bold14, $center);
        $section->addTextBreak(2);
        $section->addText(strtoupper($project->judul), $bold14, $center);
        $section->addTextBreak(2);

        if ($logoPath && file_exists($logoPath)) {
            try {
                $section->addImage($logoPath, [
                    'width' => 110,
                    'height' => 110,
                    'alignment' => Jc::CENTER,
                ]);
            } catch (Throwable $e) {
                $section->addText("[ LOGO UNIVERSITAS ]", ['size' => 10], $center);
            }
        } else {
            $section->addTextBreak(1);
            $section->addText("[ LOGO UNIVERSITAS ]", ['size' => 10], $center);
        }

        $section->addTextBreak(2);
        $section->addText("OLEH:", $bold12, $center);
        $section->addText(strtoupper($nama), ['bold' => true, 'underline' => 'single'], $center);
        $section->addText($npm, $bold12, $center);
        $section->addTextBreak(3);
        $section->addText(strtoupper("PROGRAM STUDI " . $project->prodi), $bold14, $center);
        if (!empty($fakultas)) {
            $section->addText(strtoupper($fakultas), $bold14, $center);
        }
        $section->addText(strtoupper($kampus), $bold14, $center);
        $section->addText(date('Y'), $bold14, $center);
        $section->addPageBreak();

        $section->addText("DAFTAR ISI", $bold14, $center);
        $section->addTextBreak(1);
        $section->addTOC(['font' => 'Times New Roman', 'size' => 12], ['tabLeader' => TOC::TAB_LEADER_DOT], 1, 2);
        $section->addPageBreak();

        $chapters = [
            'bab1' => 'PENDAHULUAN',
            'bab2' => 'TINJAUAN PUSTAKA',
            'bab3' => 'METODOLOGI PENELITIAN',
            'bab4' => 'HASIL DAN PEMBAHASAN',
            'bab5' => 'PENUTUP'
        ];
        if (str_contains($jenisDokumen, 'PROPOSAL')) {
            $chapters = [
                'bab1' => 'PENDAHULUAN',
                'bab2' => 'TINJAUAN PUSTAKA',
                'bab3' => 'METODOLOGI PENELITIAN',
            ];
        }

        $nums = ['bab1' => 'I', 'bab2' => 'II', 'bab3' => 'III', 'bab4' => 'IV', 'bab5' => 'V'];

        foreach ($chapters as $k => $v) {
            if ($htmlContent = $project->{$k . '_content'}) {
                if ($k != 'bab1') $section = $phpWord->addSection($sectionStyle);

                $section->addTitle("BAB " . $nums[$k] . " " . strtoupper($v), 1);
                $section->addTextBreak(1);

                $this->parseAndAddContent($section, $htmlContent);
                $section->addPageBreak();
            }
        }

        $section->addTitle("DAFTAR PUSTAKA", 1);
        $dpContent = trim($project->daftar_pustaka ?? '');
        if (!empty($dpContent)) {
            $refs = explode("\n", $dpContent);
            foreach ($refs as $ref) {
                if (trim($ref)) {
                    $section->addText(trim($ref), [], ['alignment' => Jc::BOTH, 'indentation' => ['hanging' => 720, 'firstLine' => 0]]);
                }
            }
        }

        return $phpWord;
    }

    public function downloadDocx($id, Request $request)
    {
        if (ob_get_length()) ob_end_clean();
        $project = Project::where('user_id', Auth::id())->findOrFail($id);
        $meta = $this->getProjectMeta($project);
        $logoPath = $this->resolveLogoTempPath($request, $meta);

        $phpWord = $this->createPhpWordDocument($project, $request, $logoPath, $meta);
        $filename = 'Naskah_' . Str::slug(substr($project->judul, 0, 28), '_') . '.docx';

        Settings::setTempDir('/tmp');
        $path = '/tmp/' . $filename;

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($path);

        if ($logoPath && file_exists($logoPath)) {
            @unlink($logoPath);
        }

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    public function downloadPdf($id, Request $request)
    {
        $project = Project::where('user_id', Auth::id())->findOrFail($id);
        $meta = $this->getProjectMeta($project);
        $logoPath = $this->resolveLogoTempPath($request, $meta);

        $marginTop = $request->input('margin_top', 4);
        $marginLeft = $request->input('margin_left', 4);
        $marginBottom = $request->input('margin_bottom', 3);
        $marginRight = $request->input('margin_right', 3);
        $jenisDokumen = strtoupper($request->input('jenis_dokumen', 'SKRIPSI'));

        $nama = $request->input('nama') ?: ($meta['nama'] ?? 'NAMA MAHASISWA');
        $npm = $request->input('npm') ?: ($meta['nim'] ?? '');
        $kampus = $request->input('kampus') ?: ($meta['kampus'] ?? 'UNIVERSITAS');
        $fakultas = $request->input('fakultas') ?: ($meta['fakultas'] ?? '');

        $logoHtml = "<div style='margin: 36px 0;'>[ LOGO UNIVERSITAS ]</div>";
        if ($logoPath && file_exists($logoPath)) {
            $mime = mime_content_type($logoPath) ?: 'image/png';
            $b64 = base64_encode(file_get_contents($logoPath));
            $logoHtml = "<div style='margin: 28px 0; text-align:center;'><img src='data:{$mime};base64,{$b64}' style='width:115px; height:auto;'></div>";
            @unlink($logoPath);
        }

        $html = "<html><head><style>
            body{font-family:'Times New Roman', serif; margin:{$marginTop}cm {$marginRight}cm {$marginBottom}cm {$marginLeft}cm; text-align:justify; line-height:1.5; font-size:12pt;}
            h1,h2{text-align:center; font-weight:bold; font-size:14pt;}
            h3,h4{font-weight:bold; font-size:12pt;}
            p{text-indent: 1.27cm; margin: 0 0 8px 0;}
            .cover p{text-indent:0; text-align:center;}
        </style></head><body>";

        $html .= "<div class='cover' style='text-align:center; padding-top:20px;'>
            <h1 style='font-size:14pt;'>{$jenisDokumen}</h1>
            <h2 style='font-size:14pt; margin-top:24px;'>" . strtoupper(e($project->judul)) . "</h2>
            {$logoHtml}
            <p style='margin-top:28px;'><strong>OLEH:</strong><br><u><strong>" . strtoupper(e($nama)) . "</strong></u><br>" . e($npm) . "</p>
            <div style='margin-top:60px; font-weight:bold; font-size:13pt;'>
                PROGRAM STUDI " . strtoupper(e($project->prodi)) . "<br>
                " . ($fakultas ? strtoupper(e($fakultas)) . "<br>" : "") . "
                " . strtoupper(e($kampus)) . "<br>
                " . date('Y') . "
            </div>
        </div>
        <div style='page-break-after:always;'></div>";

        $chapters = [
            'bab1' => 'PENDAHULUAN',
            'bab2' => 'TINJAUAN PUSTAKA',
            'bab3' => 'METODOLOGI PENELITIAN',
            'bab4' => 'HASIL DAN PEMBAHASAN',
            'bab5' => 'PENUTUP'
        ];
        if (str_contains($jenisDokumen, 'PROPOSAL')) {
            $chapters = [
                'bab1' => 'PENDAHULUAN',
                'bab2' => 'TINJAUAN PUSTAKA',
                'bab3' => 'METODOLOGI PENELITIAN',
            ];
        }

        $nums = ['bab1' => 'I', 'bab2' => 'II', 'bab3' => 'III', 'bab4' => 'IV', 'bab5' => 'V'];
        foreach ($chapters as $k => $v) {
            if ($c = $project->{$k . '_content'}) {
                $html .= "<h2>BAB " . $nums[$k] . "<br>$v</h2>" . nl2br($c);
                $html .= "<div style='page-break-after:always;'></div>";
            }
        }

        $html .= "<h2>DAFTAR PUSTAKA</h2><p style='text-indent:0; padding-left:1.27cm; text-indent:-1.27cm;'>" . nl2br(e($project->daftar_pustaka)) . "</p></body></html>";

        $dompdf = new Dompdf();
        $dompdf->set_option('isHtml5ParserEnabled', true);
        $dompdf->set_option('isRemoteEnabled', true);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4');
        $dompdf->render();
        return $dompdf->stream('Naskah_' . Str::slug(substr($project->judul, 0, 24), '_') . '.pdf');
    }

    /**
     * Download Contoh Proposal / Skripsi Asli (.docx / .pdf) langsung dari Landing Page
     */
    public function downloadSample(string $major, string $format, Request $request)
    {
        $samples = [
            'manajemen' => [
                'judul' => 'Pengaruh Literasi Digital, Regulasi Diri, dan Dukungan Institusi terhadap Kesiapan Kerja Mahasiswa di Era Transformasi Digital',
                'prodi' => 'Manajemen',
                'nama' => 'Rizky Pratama Putra',
                'nim' => '2021041082',
                'kampus' => 'Universitas Indonesia',
                'bab1' => "1.1 Latar Belakang Masalah\nPerkembangan teknologi informasi di era revolusi industri 4.0 telah mengubah struktur kompetensi yang dibutuhkan oleh dunia industri secara fundamental. Menurut Naufal & Wijaya (2024), lulusan perguruan tinggi tidak lagi hanya dituntut menguasai pengetahuan teoretis pada bidang keilmuannya, melainkan juga harus memiliki kecakapan literasi digital serta kemampuan adaptasi mandiri yang tinggi.\n\nHasil studi pendahuluan yang dilakukan oleh Kusuma et al. (2023) menunjukkan bahwa meskipun intensitas penggunaan perangkat digital di kalangan mahasiswa mencapai lebih dari 8 jam per hari, pemanfaatan teknologi untuk produktivitas akademik dan persiapan karier profesional masih tergolong belum optimal. Hal ini sejalan dengan temuan Pratama & Santoso (2024) yang mengungkapkan adanya kesenjangan antara penguasaan teknologi dasar dengan kesiapan kerja lulusan baru.\n\n1.2 Rumusan Masalah\n1. Apakah literasi digital berpengaruh positif dan signifikan terhadap kesiapan kerja mahasiswa?\n2. Apakah regulasi diri berpengaruh positif dan signifikan terhadap kesiapan kerja mahasiswa?\n3. Apakah dukungan institusi memoderasi pengaruh literasi digital dan regulasi diri terhadap kesiapan kerja mahasiswa?\n\n1.3 Tujuan Penelitian\nPenelitian ini bertujuan untuk menguji dan menganalisis secara empiris pengaruh literasi digital, regulasi diri, serta peran dukungan institusi terhadap kesiapan kerja mahasiswa.",
                'bab2' => "2.1 Landasan Teori\nLiterasi digital didefinisikan oleh Gilster (dalam Rahmawati & Hidayat, 2023) sebagai kemampuan individu untuk memahami, mengevaluasi, dan mengintegrasikan informasi dalam berbagai format digital secara kritis dan produktif. Indikator literasi digital mencakup pencarian informasi daring, evaluasi kredibilitas konten, serta kolaborasi digital.\n\nRegulasi diri (self-regulation) menurut Zimmerman (2020) merupakan kapasitas psikologis individu dalam merencanakan, memantau, dan mengendalikan perilaku belajarnya guna mencapai target karier yang telah ditetapkan.\n\n2.2 Penelitian Terdahulu\nPenelitian oleh Handayani & Putri (2023) pada 210 mahasiswa tingkat akhir membuktikan bahwa literasi digital memiliki kontribusi sebesar 42,6% terhadap kesiapan memasuki pasar kerja.",
                'bab3' => "3.1 Jenis dan Desain Penelitian\nPenelitian ini menggunakan pendekatan kuantitatif asosiatif kausal dengan metode survei menggunakan instrumen kuesioner skala Likert 1 sampai 5.\n\n3.2 Populasi dan Sampel\nPopulasi dalam penelitian ini adalah mahasiswa tingkat akhir angkatan 2021–2022. Penentuan jumlah sampel menggunakan rumus Slovin dengan tingkat signifikansi 5%, sehingga diperoleh sebanyak 175 responden.\n\n3.3 Teknik Analisis Data\nAnalisis data dilakukan menggunakan metode Partial Least Square - Structural Equation Modeling (PLS-SEM) melalui pengujian Outer Model (validitas konvergen dan reliabilitas komposit) serta Inner Model (uji R-Square dan bootstrapping).",
                'dp' => "Handayani, S., & Putri, M. A. (2023). Pengaruh kompetensi digital terhadap kesiapan kerja lulusan perguruan tinggi. Jurnal Manajemen Pendidikan Indonesia, 15(2), 112-125.\nKusuma, A. R., Pratama, D., & Wijaya, H. (2023). Transformasi digital dan kesiapan karier mahasiswa di Indonesia. Jurnal Ekonomi dan Bisnis, 18(1), 45-59.\nNaufal, M., & Wijaya, B. (2024). Literasi digital dan adaptabilitas karier generasi Z. Jurnal Riset Manajemen, 11(3), 201-215.\nRahmawati, L., & Hidayat, T. (2023). Evaluasi model kesiapan kerja mahasiswa berbasis regulasi diri. Jurnal Psikologi Industri, 9(2), 88-102."
            ],
            'it' => [
                'judul' => 'Implementasi Algoritma Random Forest dan Ekstraksi Fitur Citra untuk Klasifikasi Penyakit Daun Padi Berbasis Web',
                'prodi' => 'Teknik Informatika',
                'nama' => 'Aditya Mahendra',
                'nim' => '1910511044',
                'kampus' => 'Institut Teknologi Bandung',
                'bab1' => "1.1 Latar Belakang Masalah\nKomoditas padi merupakan sumber pangan utama di Indonesia yang produktivitasnya sangat dipengaruhi oleh kesehatan tanaman pada fase vegetatif. Serangan patogen seperti hawar daun bakteri (Bacterial Leaf Blight) dan bercak coklat (Brown Spot) sering kali terlambat diidentifikasi secara kasatmata oleh petani (Setiawan & Nugroho, 2023).\n\nKemajuan bidang pengolahan citra digital (digital image processing) dan pembelajaran mesin memungkinkan deteksi penyakit daun dilakukan secara otomatis melalui ekstraksi fitur warna HSV dan tekstur Gray Level Co-occurrence Matrix (GLCM). Algoritma Random Forest dipilih karena kemampuannya menggabungkan banyak pohon keputusan (decision trees) sehingga menghasilkan akurasi klasifikasi yang tinggi dan stabil (saputra et al., 2024).\n\n1.2 Rumusan Masalah\n1. Bagaimana merancang tahapan ekstraksi fitur warna HSV dan tekstur GLCM pada citra daun padi?\n2. Berapa tingkat akurasi, presisi, dan recall algoritma Random Forest dalam mengklasifikasikan penyakit daun padi?",
                'bab2' => "2.1 Landasan Teori\nPengolahan citra digital merupakan proses transformasi matriks piksel dua dimensi untuk meningkatkan kualitas visual atau mengekstraksi informasi ciri tertentu dari suatu objek (Gonzalez & Woods, 2021).\n\nAlgoritma Random Forest adalah metode ensemble learning yang membangun sejumlah pohon keputusan menggunakan teknik bootstrap aggregating (bagging) dan menentukan prediksi kelas akhir berdasarkan mekanisme pemungutan suara terbanyak (majority voting).",
                'bab3' => "3.1 Metode Pengembangan Sistem\nPenelitian ini menerapkan metode Research and Development (R&D) dengan tahapan pengumpulan dataset (1.200 citra daun padi dari 4 kelas), preprocessing citra, ekstraksi fitur HSV dan GLCM, pelatihan model Random Forest, serta pengujian akurasi menggunakan Confusion Matrix dan 10-Fold Cross Validation.",
                'dp' => "Gonzalez, R. C., & Woods, R. E. (2021). Digital image processing (4th ed.). Pearson.\nSaputra, D., Kurniawan, R., & Hakim, L. (2024). Klasifikasi penyakit tanaman pangan menggunakan ensemble machine learning. Jurnal Teknologi Informasi dan Ilmu Komputer, 11(1), 33-44.\nSetiawan, B., & Nugroho, A. (2023). Ekstraksi fitur GLCM dan ruang warna HSV pada deteksi penyakit daun padi. Jurnal Nasional Teknik Elektro dan Teknologi Informasi, 12(2), 140-148."
            ],
            'hukum' => [
                'judul' => 'Pelindungan Hukum bagi Konsumen atas Kebocoran Data Pribadi pada Layanan Pinjaman Daring Berdasarkan Undang-Undang Nomor 27 Tahun 2022',
                'prodi' => 'Ilmu Hukum',
                'nama' => 'Nabila Azzahra',
                'nim' => '2010111095',
                'kampus' => 'Universitas Gadjah Mada',
                'bab1' => "1.1 Latar Belakang Masalah\nPerkembangan layanan pendanaan bersama berbasis teknologi informasi (fintech peer-to-peer lending) telah memperluas inklusi keuangan di Indonesia, namun di sisi lain menimbulkan kerentanan hukum terkait pelindungan data pribadi pengguna. Menurut Siregar & Wibowo (2023), berbagai insiden kegagalan keamanan sistem elektronik mengakibatkan data sensitif konsumen tersebar tanpa persetujuan yang sah.\n\nPengesahan Undang-Undang Nomor 27 Tahun 2022 tentang Pelindungan Data Pribadi (UU PDP) menjadi instrumen hukum positif dalam mengatur kewajiban pengendali data. Namun demikian, mekanisme pertanggungjawaban perdata dan ganti kerugian bagi konsumen korban kebocoran data masih menghadapi kendala pembuktian (Maharani et al., 2024).\n\n1.2 Rumusan Masalah\n1. Bagaimana pengaturan kewajiban hukum penyelenggara layanan pinjaman daring sebagai pengendali data menurut UU Nomor 27 Tahun 2022?\n2. Bagaimana bentuk pertanggungjawaban hukum dan upaya pemulihan kerugian bagi konsumen apabila terjadi kebocoran data pribadi?",
                'bab2' => "2.1 Tinjauan Pustaka\nTeori Pelindungan Hukum menurut Satjipto Rahardjo menekankan bahwa hukum hadir untuk mengintegrasikan dan mengoordinasikan berbagai kepentingan dalam masyarakat dengan cara melindungi hak-hak pihak yang berada pada posisi lemah secara struktural maupun kontraktual.",
                'bab3' => "3.1 Jenis dan Pendekatan Penelitian\nPenelitian ini merupakan penelitian hukum yuridis normatif dengan menggunakan pendekatan perundang-undangan (statute approach) terhadap UU Nomor 27 Tahun 2022 tentang Pelindungan Data Pribadi dan Peraturan Otoritas Jasa Keuangan, serta pendekatan konseptual (conceptual approach).",
                'dp' => "Maharani, D., Prasetyo, Y., & Santoso, H. (2024). Tanggung jawab pengendali data pribadi dalam perspektif Undang-Undang Nomor 27 Tahun 2022. Jurnal Hukum Ius Quia Iustum, 31(1), 55-74.\nSiregar, R. A., & Wibowo, A. (2023). Pelindungan konsumen layanan finansial teknologi atas penyalahgunaan data pribadi. Jurnal Penelitian Hukum De Jure, 23(2), 189-204."
            ]
        ];

        $sample = $samples[$major] ?? $samples['manajemen'];
        $fakeProject = new Project([
            'judul' => $sample['judul'],
            'prodi' => $sample['prodi'],
            'bab1_content' => $sample['bab1'],
            'bab2_content' => $sample['bab2'],
            'bab3_content' => $sample['bab3'],
            'daftar_pustaka' => $sample['dp'],
        ]);

        $request->merge([
            'nama' => $sample['nama'],
            'npm' => $sample['nim'],
            'kampus' => $sample['kampus'],
            'jenis_dokumen' => 'PROPOSAL SKRIPSI',
        ]);

        if ($format === 'pdf') {
            $html = "<html><head><style>
                body{font-family:'Times New Roman', serif; margin:3.5cm 3cm 3cm 3.5cm; text-align:justify; line-height:1.5; font-size:12pt;}
                h1,h2{text-align:center; font-weight:bold; font-size:14pt;}
                p{text-indent: 1.27cm; margin: 0 0 8px 0;}
                .cover p{text-indent:0; text-align:center;}
            </style></head><body>
            <div class='cover' style='text-align:center; padding-top:30px;'>
                <h1>PROPOSAL SKRIPSI</h1>
                <h2 style='margin-top:24px;'>" . strtoupper(e($sample['judul'])) . "</h2>
                <p style='margin-top:48px;'><strong>OLEH:</strong><br><u><strong>" . strtoupper(e($sample['nama'])) . "</strong></u><br>{$sample['nim']}</p>
                <div style='margin-top:80px; font-weight:bold; font-size:13pt;'>
                    PROGRAM STUDI " . strtoupper(e($sample['prodi'])) . "<br>
                    " . strtoupper(e($sample['kampus'])) . "<br>" . date('Y') . "
                </div>
            </div>
            <div style='page-break-after:always;'></div>
            <h2>BAB I<br>PENDAHULUAN</h2>" . nl2br(e($sample['bab1'])) . "
            <div style='page-break-after:always;'></div>
            <h2>BAB II<br>TINJAUAN PUSTAKA</h2>" . nl2br(e($sample['bab2'])) . "
            <div style='page-break-after:always;'></div>
            <h2>BAB III<br>METODOLOGI PENELITIAN</h2>" . nl2br(e($sample['bab3'])) . "
            <div style='page-break-after:always;'></div>
            <h2>DAFTAR PUSTAKA</h2><p style='text-indent:0; padding-left:1.27cm; text-indent:-1.27cm;'>" . nl2br(e($sample['dp'])) . "</p>
            </body></html>";

            $dompdf = new Dompdf();
            $dompdf->set_option('isHtml5ParserEnabled', true);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4');
            $dompdf->render();
            return $dompdf->stream('Contoh_Proposal_' . ucfirst($major) . '.pdf');
        }

        if (ob_get_length()) ob_end_clean();
        $phpWord = $this->createPhpWordDocument($fakeProject, $request);
        $filename = 'Contoh_Proposal_' . ucfirst($major) . '.docx';
        Settings::setTempDir('/tmp');
        $path = '/tmp/' . $filename;
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($path);
        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    public function refineReferences($id, Request $request)
    {
        $project = Project::where('user_id', Auth::id())->findOrFail($id);
        $model = $request->input('model', 'auto');

        $allText = strip_tags($project->bab1_content . ' ' . $project->bab2_content . ' ' . $project->bab3_content . ' ' . $project->bab4_content . ' ' . $project->bab5_content);
        $prompt = "Buatkan DAFTAR PUSTAKA (APA Style 7th Edition) lengkap berdasarkan naskah skripsi berjudul '{$project->judul}' berikut ini. Ambil seluruh nama penulis dan tahun yang dikutip dalam kurung, lengkapi dengan jurnal ilmiah relevan, dan urutkan secara alfabetis (A-Z). Jangan gunakan tanda bintang markdown:\n\n" . substr($allText, 0, 8000);

        try {
            $daftarPustaka = $this->ai->generateText($prompt, $model);
            $cleanDP = strip_tags($daftarPustaka);
            $cleanDP = str_replace(['*', '#', '_', '`'], '', $cleanDP);
            $project->daftar_pustaka = trim($cleanDP);
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
            'question' => 'required|string',
            'image'    => 'nullable|string',
            'model'    => 'nullable|string'
        ]);

        $user = Auth::user();
        if ($user->credits < 1) {
            return response()->json(['status' => 'error', 'message' => 'Token habis! Silakan klaim token gratis terlebih dahulu.']);
        }

        try {
            $question = $request->question;
            $image = $request->image;
            $model = $request->input('model', 'auto');

            $prompt = "PERAN: Anda adalah Dosen Pembimbing Akademik.
Jawab pertanyaan berikut secara sistematis, jelas, dan mudah dipahami:
PERTANYAAN: {$question}";

            $answer = $this->ai->generateWithImage($prompt, $image, $model);
            $user->decrement('credits');

            return response()->json([
                'status' => 'success',
                'answer' => $answer,
                'new_credits' => $user->credits
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
        $user = Auth::user();
        if ($user->credits < 1) {
            return response()->json(['status' => 'error', 'message' => 'Token habis! Silakan klaim token gratis terlebih dahulu.']);
        }

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
            $user->decrement('credits');

            return response()->json([
                'status' => 'success',
                'result' => trim($result),
                'new_credits' => $user->credits
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function generatePpt(Request $request)
    {
        $user = Auth::user();
        if ($user->credits < 1) {
            return response()->json(['status' => 'error', 'message' => 'Token habis! Silakan klaim token gratis terlebih dahulu.']);
        }

        $model = $request->input('model', 'auto');
        $docText = '';
        $docTitle = trim($request->input('judul', ''));

        if ($request->hasFile('file_skripsi')) {
            $file = $request->file('file_skripsi');
            $docText = $this->extractTextFromUploadedFile($file);
            if (empty($docTitle)) {
                $docTitle = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $docTitle = str_replace(['_', '-'], ' ', $docTitle);
            }
        }

        if ($request->filled('project_id')) {
            $proj = Project::where('user_id', Auth::id())->find($request->project_id);
            if ($proj) {
                $docTitle = $proj->judul;
                $docText = strip_tags(
                    "Judul: {$proj->judul}\nProdi: {$proj->prodi}\nMetode: {$proj->metode_penelitian}\n" .
                    "BAB 1:\n{$proj->bab1_content}\nBAB 2:\n{$proj->bab2_content}\nBAB 3:\n{$proj->bab3_content}\nBAB 4:\n{$proj->bab4_content}\nBAB 5:\n{$proj->bab5_content}"
                );
            }
        }

        if (empty($docTitle) && empty($docText)) {
            return response()->json(['status' => 'error', 'message' => 'Silakan unggah file skripsi (.pdf / .docx) atau pilih proyek skripsi Anda.'], 422);
        }

        $prompt = "PERAN: Anda adalah Konsultan Presentasi Sidang Skripsi.
TUGAS: Rangkum dokumen skripsi/proposal berikut menjadi 9 SLIDE PRESENTASI SIDANG dalam format JSON Array murni.

JUDUL / TOPIK: {$docTitle}
ISI DOKUMEN SKRIPSI MAHASISWA:
\"\"\"
" . Str::limit($docText ?: $docTitle, 12000) . "
\"\"\"

INSTRUKSI OUTPUT WAJIB:
Kembalikan HANYA JSON Array valid (tanpa teks pengantar, tanpa markdown ```json) dengan struktur 9 objek slide berikut:
[
  {
    \"slide_number\": 1,
    \"section\": \"Sampul Sidang\",
    \"title\": \"Judul Lengkap Skripsi\",
    \"subtitle\": \"Program Studi & Metode Penelitian\",
    \"bullets\": [\"Disusun untuk Sidang Tugas Akhir / Seminar Proposal\", \"Fokus Riset & Objek Penelitian\"],
    \"speaker_notes\": \"Selamat pagi/siang Bapak dan Ibu Dosen Penguji, terima kasih atas kesempatan yang diberikan...\"
  }
]
Buat 9 slide berurutan (Cover, Latar Belakang, Rumusan & Tujuan, Landasan Teori, Penelitian Terdahulu, Metodologi, Hasil Penelitian, Pembahasan, Kesimpulan & Saran).";

        try {
            $raw = $this->ai->generateText($prompt, $model);

            $slidesArray = null;
            if (preg_match('/\[\s*\{.*\}\s*\]/s', $raw, $matches)) {
                $slidesArray = json_decode($matches[0], true);
            } else {
                $slidesArray = json_decode($raw, true);
            }

            if (!is_array($slidesArray) || empty($slidesArray)) {
                $slidesArray = [
                    [
                        'slide_number' => 1,
                        'section' => 'Sampul Presentasi',
                        'title' => $docTitle ?: 'Presentasi Sidang Skripsi',
                        'subtitle' => 'Sidang Skripsi / Seminar Proposal',
                        'bullets' => ['Presentasi Hasil Penelitian Tugas Akhir', 'Klik teks pada slide ini untuk mengedit langsung'],
                        'speaker_notes' => 'Selamat pagi/siang Bapak/Ibu Dosen Penguji. Pada kesempatan ini saya akan memaparkan penelitian berjudul ' . $docTitle
                    ],
                    [
                        'slide_number' => 2,
                        'section' => 'Bab I · Pendahuluan',
                        'title' => 'Latar Belakang & Urgensi Penelitian',
                        'subtitle' => 'Fenomena dan Kesenjangan Penelitian',
                        'bullets' => array_values(array_filter(array_slice(explode("\n", strip_tags($raw)), 0, 4))),
                        'speaker_notes' => 'Penelitian ini dilatarbelakangi oleh fenomena utama yang ditemukan pada objek penelitian.'
                    ]
                ];
            }

            $user->decrement('credits');

            return response()->json([
                'status' => 'success',
                'title' => $docTitle,
                'slides_data' => $slidesArray,
                'new_credits' => $user->credits
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function simulateDefense(Request $request)
    {
        $user = Auth::user();
        if ($user->credits < 1) {
            return response()->json(['status' => 'error', 'message' => 'Token habis! Silakan klaim token gratis terlebih dahulu.']);
        }

        $mode = $request->input('mode', 'generate_questions');
        $model = $request->input('model', 'auto');

        try {
            if ($mode === 'generate_questions') {
                $docText = '';
                $docTitle = trim($request->input('judul', ''));
                $metode = trim($request->input('metode', ''));

                if ($request->hasFile('file_skripsi')) {
                    $file = $request->file('file_skripsi');
                    $docText = $this->extractTextFromUploadedFile($file);
                    if (empty($docTitle)) {
                        $docTitle = str_replace(['_', '-'], ' ', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
                    }
                }

                if ($request->filled('project_id')) {
                    $proj = Project::where('user_id', Auth::id())->find($request->project_id);
                    if ($proj) {
                        $docTitle = $proj->judul;
                        $metode = $proj->metode_penelitian;
                        $docText = strip_tags(
                            "Judul: {$proj->judul}\nProdi: {$proj->prodi}\nMetode: {$proj->metode_penelitian}\n" .
                            "BAB 1:\n{$proj->bab1_content}\nBAB 2:\n{$proj->bab2_content}\nBAB 3:\n{$proj->bab3_content}\nBAB 4:\n{$proj->bab4_content}\nBAB 5:\n{$proj->bab5_content}"
                        );
                    }
                }

                if (empty($docTitle) && empty($docText)) {
                    return response()->json(['status' => 'error', 'message' => 'Silakan unggah file skripsi (.pdf / .docx) atau pilih proyek skripsi Anda.'], 422);
                }

                $prompt = "PERAN: Anda adalah Ketua Dewan Dosen Penguji Sidang Skripsi.
Berdasarkan dokumen skripsi mahasiswa berikut:
JUDUL: {$docTitle}
METODE: {$metode}
RINGKASAN NASKAH:
\"\"\"
" . Str::limit($docText ?: $docTitle, 10000) . "
\"\"\"

TUGAS: Buat 5 PERTANYAAN UJIAN SIDANG SKRIPSI yang spesifik menguji penguasaan mahasiswa terhadap naskahnya.

FORMAT OUTPUT WAJIB: Kembalikan HANYA JSON Array murni berisi 5 objek pertanyaan:
[
  {
    \"number\": 1,
    \"category\": \"Bab I · Urgensi & Masalah Penelitian\",
    \"question\": \"Pertanyaan penguji 1...\",
    \"hint\": \"Fokus jawaban: jelaskan fenomena utama dan alasan pemilihan objek/variabel.\"
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
                    $questions = [
                        ['number' => 1, 'category' => 'Bab I · Latar Belakang & Urgensi', 'question' => "Apa urgensi utama dan kesenjangan penelitian (research gap) yang mendasari pemilihan topik \"{$docTitle}\"?", 'hint' => 'Jelaskan masalah aktual di lapangan dan perbedaannya dengan riset terdahulu.'],
                        ['number' => 2, 'category' => 'Bab I · Rumusan & Tujuan', 'question' => 'Bagaimana keterkaitan antara rumusan masalah yang Anda ajukan dengan tujuan serta manfaat praktis penelitian ini?', 'hint' => 'Pastikan setiap poin tujuan menjawab rumusan masalah secara terukur.'],
                        ['number' => 3, 'category' => 'Bab II · Landasan Teori', 'question' => 'Teori utama (Grand Theory) apa yang Anda gunakan sebagai pisau analisis dalam penelitian ini, dan mengapa teori tersebut paling relevan?', 'hint' => 'Sebutkan tokoh/penulis teori beserta indikator variabel yang digunakan.'],
                        ['number' => 4, 'category' => 'Bab III · Metodologi Penelitian', 'question' => 'Mengapa Anda memilih metode penelitian tersebut, dan bagaimana cara Anda memastikan validitas/keabsahan data yang dikumpulkan?', 'hint' => 'Jelaskan alasan pemilihan metode, teknik sampling/informan, dan pengujian instrumen/sistem.'],
                        ['number' => 5, 'category' => 'Bab IV & V · Hasil & Implikasi', 'question' => 'Apa temuan paling penting dari penelitian Anda, dan apa keterbatasan serta saran konkret yang dapat diterapkan oleh objek penelitian?', 'hint' => 'Rangkum hasil utama dan berikan rekomendasi yang realistis.'],
                    ];
                }

                $user->decrement('credits');

                return response()->json([
                    'status' => 'success',
                    'title' => $docTitle,
                    'questions' => array_slice($questions, 0, 5),
                    'new_credits' => $user->credits
                ]);
            }

            $qaList = $request->input('qa_list', []);
            $docTitle = $request->input('judul', 'Skripsi Mahasiswa');

            $qaFormatted = "";
            foreach ($qaList as $idx => $item) {
                $num = $idx + 1;
                $q = $item['question'] ?? '';
                $a = $item['answer'] ?? '';
                $qaFormatted .= "Pertanyaan {$num}: {$q}\nJawaban Mahasiswa {$num}: {$a}\n\n";
            }

            $prompt = "PERAN: Anda adalah Ketua Dewan Dosen Penguji Sidang Skripsi.
Evaluasi seluruh jawaban mahasiswa pada simulasi sidang skripsi untuk judul: \"{$docTitle}\".

DAFTAR PERTANYAAN DAN JAWABAN MAHASISWA:
\"\"\"
{$qaFormatted}
\"\"\"

FORMAT OUTPUT WAJIB: Kembalikan HANYA JSON Object murni dengan struktur berikut:
{
  \"overall_score\": 8.2,
  \"grade\": \"A-\",
  \"verdict\": \"Lulus Sidang dengan Revisi Minor\",
  \"summary\": \"Ringkasan evaluasi umum performa mahasiswa...\",
  \"items\": [
    {
      \"number\": 1,
      \"question\": \"Teks pertanyaan 1...\",
      \"student_answer\": \"Jawaban mahasiswa...\",
      \"score\": 8.0,
      \"feedback\": \"Evaluasi spesifik dosen penguji...\",
      \"better_answer\": \"Contoh kalimat jawaban lisan yang lebih kuat dan ilmiah...\"
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
                $total = 0;
                foreach ($qaList as $idx => $item) {
                    $ans = trim($item['answer'] ?? '');
                    $wordCount = str_word_count($ans);
                    $score = $wordCount > 25 ? 8.2 : ($wordCount > 10 ? 7.0 : 5.5);
                    $total += $score;
                    $fallbackItems[] = [
                        'number' => $idx + 1,
                        'question' => $item['question'] ?? '',
                        'student_answer' => $ans,
                        'score' => $score,
                        'feedback' => 'Argumen sudah menjawab inti pertanyaan, namun dapat diperkuat dengan menyebutkan landasan teori atau indikator empiris.',
                        'better_answer' => 'Berdasarkan kerangka penelitian pada skripsi ini, pemilihan variabel dan metode tersebut didasarkan pada fenomena empiris di lapangan serta didukung oleh temuan penelitian terdahulu yang relevan.'
                    ];
                }
                $avg = count($qaList) > 0 ? round($total / count($qaList), 1) : 7.5;
                $evalData = [
                    'overall_score' => $avg,
                    'grade' => 'B+',
                    'verdict' => 'Layak Lanjut Sidang dengan Perbaikan Argumen',
                    'summary' => 'Secara keseluruhan mahasiswa memahami gambaran umum penelitian, namun beberapa jawaban perlu dilengkapi dengan rujukan teori dan alasan metodologis.',
                    'items' => $fallbackItems,
                ];
            }

            $user->decrement('credits');

            return response()->json([
                'status' => 'success',
                'evaluation' => $evalData,
                'new_credits' => $user->credits
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
