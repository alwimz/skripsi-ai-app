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

    /**
     * Kompres & konversi logo universitas menjadi PNG base64 kecil agar aman disimpan di TEXT column & terbaca PhpWord/Dompdf
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

            if (empty($rawBytes) || strlen($rawBytes) < 60) {
                return '';
            }

            if (function_exists('imagecreatefromstring')) {
                $src = @imagecreatefromstring($rawBytes);
                if ($src !== false) {
                    $origW = imagesx($src);
                    $origH = imagesy($src);
                    $maxDim = 220;
                    $scale = min(1.0, $maxDim / max(1, $origW, $origH));
                    $newW = max(1, (int) round($origW * $scale));
                    $newH = max(1, (int) round($origH * $scale));

                    $dst = imagecreatetruecolor($newW, $newH);
                    imagealphablending($dst, false);
                    imagesavealpha($dst, true);
                    $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
                    imagefilledrectangle($dst, 0, 0, $newW, $newH, $transparent);
                    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

                    ob_start();
                    imagepng($dst, null, 7);
                    $pngBytes = ob_get_clean();
                    imagedestroy($src);
                    imagedestroy($dst);

                    if (!empty($pngBytes)) {
                        return 'data:image/png;base64,' . base64_encode($pngBytes);
                    }
                }
            }

            if (strlen($rawBytes) <= 42000) {
                return 'data:image/png;base64,' . base64_encode($rawBytes);
            }
        } catch (Throwable $e) {
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
     * Ambil referensi ilmiah terverifikasi dari OpenAlex untuk memperkuat sitasi naskah
     */
    private function fetchVerifiedReferencesForProject(Project $project): array
    {
        $refs = [];
        try {
            $cleanWords = preg_replace('/[^a-zA-Z0-9\s]/', ' ', $project->judul);
            $words = array_values(array_filter(explode(' ', $cleanWords), fn($w) => strlen($w) >= 4));
            $query = implode(' ', array_slice($words, 0, 6));
            if (empty($query))
                return [];

            $meta = $this->getProjectMeta($project);
            $tahunMin = $meta['sitasi']['tahun_min'] ?? '2020';
            $filterDate = "from_publication_date:{$tahunMin}-01-01,to_publication_date:2024-12-31";

            $res = Http::timeout(8)->get('https://api.openalex.org/works', [
                'search' => $query,
                'filter' => $filterDate,
                'per-page' => 15,
            ]);

            if ($res->successful()) {
                foreach ($res->json()['results'] ?? [] as $w) {
                    $title = trim($w['title'] ?? '');
                    if (empty($title))
                        continue;
                    $authors = [];
                    foreach (array_slice($w['authorships'] ?? [], 0, 2) as $a) {
                        if (!empty($a['author']['display_name'])) {
                            $parts = explode(' ', trim($a['author']['display_name']));
                            $authors[] = end($parts);
                        }
                    }
                    $authorLabel = !empty($authors) ? implode(' & ', $authors) : 'Peneliti';
                    if (count($w['authorships'] ?? []) > 2)
                        $authorLabel = $authors[0] . ' et al.';
                    $year = $w['publication_year'] ?? 2023;
                    if ($year > 2024)
                        $year = 2024;
                    $venue = $w['primary_location']['source']['display_name'] ?? 'Jurnal Ilmiah Terakreditasi';
                    $doi = !empty($w['doi']) ? $w['doi'] : '';
                    $refs[] = "{$authorLabel} ({$year}) - \"{$title}\" [{$venue}]" . ($doi ? " {$doi}" : "");
                }
            }
        } catch (Throwable $e) {
            Log::warning('OpenAlex project grounding skipped: ' . $e->getMessage());
        }
        return $refs;
    }

    /**
     * Ekstrak daftar isi terstruktur dari isi bab proyek
     */
    private function extractTocItemsForProject(Project $project, bool $isProposal = false): array
    {
        $items = [
            ['label' => 'HALAMAN JUDUL', 'page' => '', 'level' => 1],
            ['label' => 'LEMBAR PENGESAHAN', 'page' => 'i', 'level' => 1],
            ['label' => 'PERNYATAAN KEASLIAN', 'page' => 'ii', 'level' => 1],
            ['label' => 'ABSTRAK', 'page' => 'iii', 'level' => 1],
            ['label' => 'ABSTRACT', 'page' => 'iv', 'level' => 1],
            ['label' => 'KATA PENGANTAR', 'page' => 'v', 'level' => 1],
            ['label' => 'DAFTAR ISI', 'page' => 'vi', 'level' => 1],
        ];

        $chapters = [
            'bab1' => ['num' => 'I', 'title' => 'PENDAHULUAN'],
            'bab2' => ['num' => 'II', 'title' => 'TINJAUAN PUSTAKA'],
            'bab3' => ['num' => 'III', 'title' => 'METODOLOGI PENELITIAN'],
        ];
        if (!$isProposal) {
            $chapters['bab4'] = ['num' => 'IV', 'title' => 'HASIL DAN PEMBAHASAN'];
            $chapters['bab5'] = ['num' => 'V', 'title' => 'PENUTUP'];
        }

        $pageNum = 1;
        foreach ($chapters as $key => $info) {
            $content = trim((string) ($project->{$key . '_content'} ?? ''));
            if (empty($content))
                continue;

            $items[] = [
                'label' => "BAB {$info['num']} {$info['title']}",
                'page' => (string) $pageNum,
                'level' => 1,
            ];

            $lines = explode("\n", str_replace(['<p>', '</p>', '<br>', '<br/>'], "\n", $content));
            foreach ($lines as $line) {
                $clean = trim(strip_tags($line));
                if (preg_match('/^(\d+\.\d+)\s+(.+)$/', $clean, $m)) {
                    $items[] = [
                        'label' => $m[1] . ' ' . trim($m[2]),
                        'page' => (string) $pageNum,
                        'level' => 2,
                    ];
                    $pageNum++;
                }
            }
            $pageNum = max($pageNum + 1, $pageNum);
        }

        if (!empty(trim((string) $project->daftar_pustaka))) {
            $items[] = ['label' => 'DAFTAR PUSTAKA', 'page' => (string) $pageNum, 'level' => 1];
        }

        return $items;
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

        return view('pre_generator', compact('history', 'apiKeys', 'user', 'defaultIdentity'));
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
                    $detectedProvider = (str_starts_with($cleanKey, 'AIza') || str_starts_with($cleanKey, 'AQ.'))
                        ? 'gemini'
                        : 'deepseek';
                    $provider = $request->provider ?: $detectedProvider;
                    if ($provider === 'groq')
                        $provider = 'deepseek';
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
                $testModels = ['gemini-2.0-flash', 'gemini-2.5-flash', 'gemini-1.5-flash'];
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
        if ($request->hasFile('logo_kampus') && $request->file('logo_kampus')->isValid()) {
            $logoBase64 = $this->normalizeLogoBase64($request->file('logo_kampus'));
        } elseif ($request->filled('logo_base64')) {
            $logoBase64 = $this->normalizeLogoBase64($request->input('logo_base64'));
        }
        if (empty($logoBase64) && !empty($defaultIdentity['logo_base64'])) {
            $logoBase64 = $defaultIdentity['logo_base64'];
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
                'tahun_min' => $request->input('tahun_min', '2021'),
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

        $prompt = "Sebagai Dosen Pembimbing Skripsi Senior di Indonesia, berikan tepat 12 rekomendasi judul skripsi S1 terbaik untuk:
- Topik / Bidang Penelitian: {$request->topik}
- Metodologi: {$metode}
- Program Studi: {$prodi}

Syarat:
1. Spesifik, orisinal, relevan dengan riset terbaru, dan memiliki variabel atau objek kajian yang jelas serta terukur.
2. Gunakan Bahasa Indonesia akademik baku dan bervariasi polanya (tidak monoton).
3. Output WAJIB HANYA daftar judul (1 baris 1 judul), tepat 12 baris, tanpa kata pengantar, tanpa keterangan tambahan, dan tanpa nomor urut.";

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
                'citation' => "{$authorStr} ({$year}). {$title}. {$venue}. " . ($work['doi'] ?? ''),
                'scope' => $scopeLabel,
                'is_real_oa' => true,
            ];
        };

        try {
            $headers = ['User-Agent' => 'SkripsiAI-Academic/2.0 (mailto:support@skripsi-ai.app)'];

            if ($scope === 'all' || $scope === 'id') {
                $oaId = Http::timeout(10)->withHeaders($headers)->get('https://api.openalex.org/works', [
                    'search' => $keyword,
                    'filter' => 'from_publication_date:2019-01-01,language:id',
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
                        'filter' => 'from_publication_date:2019-01-01',
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
                    'filter' => 'from_publication_date:2019-01-01,language:en',
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

            // CrossRef query jika hasil masih sedikit (database DOI publikasi nyata dunia)
            if (count($journalsId) + count($journalsInt) < 4) {
                $crRes = Http::timeout(10)->withHeaders($headers)->get('https://api.crossref.org/works', [
                    'query' => $keyword,
                    'rows' => 8,
                    'select' => 'title,author,container-title,published,DOI,URL'
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
                        $cYear = $crItem['published']['date-parts'][0][0] ?? '2023';
                        $cUrl = $crItem['URL'] ?? ('https://doi.org/' . ($crItem['DOI'] ?? ''));
                        $cScope = (preg_match('/[a-z]/i', $keyword) && (str_contains(strtolower($cTitle), 'indonesia') || str_contains(strtolower($cVenue), 'jurnal'))) ? 'Indonesia' : 'Internasional';

                        $crEntry = [
                            'title' => $cTitle,
                            'author' => $cAuthStr,
                            'journal' => $cVenue,
                            'year' => (string) $cYear,
                            'pdf_url' => $cUrl,
                            'doi_url' => $cUrl,
                            'citation' => "{$cAuthStr} ({$cYear}). {$cTitle}. {$cVenue}. {$cUrl}",
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
TUGAS: Berikan daftar referensi jurnal ilmiah ASLI dan NYATA yang benar-benar pernah terbit (2019-2024) dan terindeks di Google Scholar / SINTA / Scopus / IEEE / ScienceDirect terkait kata kunci: '{$keyword}'.
ATURAN KETAT:
1. DILARANG KERAS mengarang/halusinasi judul fiktif atau tahun masa depan. Tahun terbit MAKSIMAL 2024.
2. Seluruh artikel harus nyata dan dapat ditemukan di SINTA Kemdikbud / Garuda / Google Scholar dengan DOI atau link nyata.
3. Format WAJIB per baris: Kategori(Indonesia/Internasional)|Judul Artikel Nyata|Nama Penulis|Nama Jurnal Ilmiah Terakreditasi|Tahun Terbit(2019-2024)|Link/DOI Asli
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
                        $rawYear = isset($parts[4]) ? (int) preg_replace('/\D/', '', $parts[4]) : 2023;
                        $jYear = (string) min(2024, max(2019, $rawYear ?: 2023));
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
        $tahunMin = $sitasi['tahun_min'] ?? '2021';
        $asalRef = $sitasi['asal'] ?? 'Indonesia + Internasional';
        $gayaSitasi = $sitasi['gaya'] ?? 'APA 7th';
        $bahasa = $sitasi['bahasa'] ?? 'Indonesia';
        $sumberArr = is_array($sitasi['sumber'] ?? null) ? implode(', ', $sitasi['sumber']) : 'Umum, Terindeks Sinta';

        $skillPath = resource_path('skills/skripsi_indonesia_skill.md');
        $skillRules = file_exists($skillPath)
            ? Str::limit(trim(file_get_contents($skillPath)), 3200, '')
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
{$extraContext}{$verifiedRefText}
PEDOMAN STANDAR PENULISAN SKRIPSI INDONESIA (GITHUB ACADEMIC SKILL):
{$skillRules}

ATURAN PENULISAN AKADEMIK (SANGAT KETAT):
1. Gaya Bahasa: Gunakan Bahasa {$bahasa} ilmiah baku sesuai EYD V/KBBI, kohesif, objektif, dan bebas klise AI (DILARANG membuka kalimat dengan \"Kondisi ideal dalam...\", \"Di era globalisasi saat ini...\", atau \"Pada era modern...\").
2. Kedalaman Materi: Tulis setiap sub-bab secara padat, proporsional, dan komprehensif (2-4 paragraf ilmiah berbobot per sub-bab penjelasan atau format poin terstruktur) agar seluruh sub-bab dari awal hingga akhir tersusun lengkap tanpa terputus di tengah jalan.
3. Sitasi & Bebas Mendeley: WAJIB menyertakan sitasi dalam teks (in-text citation gaya {$gayaSitasi}, contoh: Menurut Pratama ({$tahunMin}) atau (Wijaya et al., 2024)) dengan tahun terbit minimal {$tahunMin} dan maksimal 2024. DILARANG menggunakan tahun fiktif masa depan (> 2024). Setiap kutipan harus sesuai dengan literatur nyata agar mahasiswa tidak perlu lagi repot mengatur Mendeley.
4. Kepatuhan Pedoman Kampus: Jika terdapat Pedoman Kampus Mahasiswa di atas, terapkan seluruh konvensi penamaan, pembagian sub-bab, dan kaidah yang diminta kampus sebagai aturan tertinggi.
5. Format Sub-bab: Tulis nomor sub-bab pada baris tersendiri menggunakan penomoran desimal (contoh: 1.1 Latar Belakang Masalah).
6. Tanpa Simbol Markdown: Jangan gunakan tanda bintang (**) atau pagar (###). Jangan tulis kalimat pengantar AI. Langsung mulai dari sub-bab pertama.";
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
        $tahunMin = $sitasi['tahun_min'] ?? '2021';
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
            || str_contains($prodiLower, 'guru');

        $isKesehatan = str_contains($prodiLower, 'farmasi')
            || str_contains($prodiLower, 'keperawatan')
            || str_contains($prodiLower, 'kebidanan')
            || str_contains($prodiLower, 'kesehatan')
            || str_contains($prodiLower, 'kedokteran');

        $isRnD = str_contains($metodeLower, 'r&d')
            || str_contains($metodeLower, 'waterfall')
            || str_contains($metodeLower, 'agile')
            || str_contains($metodeLower, 'prototype')
            || str_contains($metodeLower, 'rekayasa');

        $isKualitatif = str_contains($metodeLower, 'kualitatif')
            || str_contains($metodeLower, 'normatif')
            || str_contains($metodeLower, 'pustaka');

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
            $context = "KONTEKS BAB 1: " . Str::limit(strip_tags($project->bab1_content), 1500);
        elseif ($bab == 'bab3')
            $context = "KONTEKS BAB 1 & 2: " . Str::limit(strip_tags($project->bab1_content . ' ' . $project->bab2_content), 1500);
        elseif ($bab == 'bab4')
            $context = "KONTEKS BAB 1 & 3: " . Str::limit(strip_tags($project->bab1_content . ' ' . $project->bab3_content), 1800);
        elseif ($bab == 'bab5') {
            $context = "HASIL PENELITIAN BAB 4: " . Str::limit(strip_tags($project->bab4_content), 1800);
            $allText = $project->bab1_content . " " . $project->bab2_content . " " . $project->bab3_content . " " . $project->bab4_content;
            $citationsList = $this->extractInTextCitations($allText);
            $citationsStr = !empty($citationsList) ? implode("\n- ", $citationsList) : "- Tidak ada sitasi terdeteksi";
            $context .= "\n\n=== DAFTAR SITASI ASLI YANG MUNCUL DALAM NASKAH BAB 1 S/D 4 (WAJIB DIBUATKAN DAFTAR PUSTAKANYA) ===\n- " . $citationsStr;
            $context .= "\n\n=== RINGKASAN ISI TEKS DARI BAB 1 SAMPAI BAB 4 ===\n" . Str::limit(strip_tags($allText), 10000);
        }

        // Bab II Sesuai Rumpun Ilmu & WAJIB KERANGKA BERPIKIR DISESUAIKAN DENGAN JUDUL & JURUSAN
        $bab2Prompt = match (true) {
            $isInformatika => "
TUGAS UTAMA: Tuliskan NASKAH LENGKAP BAB II (TINJAUAN PUSTAKA) BIDANG INFORMATIKA / ILMU KOMPUTER / SISTEM CERDAS.
Disesuaikan secara persis dengan Judul: '{$project->judul}' dan Program Studi: '{$project->prodi}'.
WAJIB MEMUAT SELURUH SUB-BAB BERURUTAN:
2.1 Landasan Teori (Uraikan konsep dasar topik judul, Text Preprocessing [Case Folding, Cleaning, Tokenizing, Slang Normalization, Stopwords Removal, Stemming Sastrawi], Pembobotan Kata [TF-IDF], Teori dan Rumus Matematis Algoritma Naive Bayes/SVM/Machine Learning yang dipakai, serta Teori Confusion Matrix).
2.2 Penelitian Terdahulu (WAJIB sajikan TABEL perbandingan minimal 4-5 penelitian jurnal bereputasi 2020-2024 terkait dalam format Markdown Table dengan kolom:
| No | Peneliti & Tahun | Judul Penelitian | Metode / Algoritma | Hasil / Akurasi | Persamaan, Perbedaan & Celah Penelitian |
Lalu berikan 2 paragraf sintesis posisi riset dan kebaruan (novelty) penelitian ini dibandingkan riset terdahulu).
2.3 Kerangka Pemikiran (WAJIB sesuaikan dengan judul '{$project->judul}'. Uraikan alur logika pemecahan masalah komputasi dari fenomena gap/data mentah di lapangan, landasan teori pendukung, proses preprocessing, pemodelan algoritma, hingga evaluasi akurasi.
Lalu WAJIB sajikan DIAGRAM KERANGKA PEMIKIRAN di bawah narasi dalam blok kode:
Gambar 2.1 Bagan Kerangka Pemikiran Penelitian
```
[Permasalahan: Urgensi & Masalah Nyata pada '{$project->judul}']
↓
[Landasan Teori: Konsep Algoritma & Teori Preprocessing Teks]
↓
[Pendekatan Komputasi: Ekstraksi Fitur TF-IDF & Klasifikasi Machine Learning]
↓
[Evaluasi & Pengujian: Confusion Matrix (Akurasi, Presisi, Recall, F1)]
↓
[Hasil yang Diharapkan: Model Klasifikasi Teruji dengan Performa Optimal]
```
).
2.4 Batasan Kinerja / Hipotesis Penelitian (Kemukakan ekspektasi performa model komputasi yang dirumuskan secara tegas).",

            $isEkonomi => "
TUGAS UTAMA: Tuliskan NASKAH LENGKAP BAB II (TINJAUAN PUSTAKA) BIDANG EKONOMI / MANAJEMEN / AKUNTANSI.
Disesuaikan secara persis dengan Judul: '{$project->judul}' dan Program Studi: '{$project->prodi}'.
WAJIB MEMUAT SELURUH SUB-BAB BERURUTAN:
2.1 Landasan Teori (Uraikan Grand Theory [misal Agency Theory / TAM / RBV / Signaling Theory], Middle Theory, dan Applied Theory, serta definisi konseptual dan indikator variabel penelitian secara komprehensif).
2.2 Penelitian Terdahulu (WAJIB sajikan TABEL perbandingan 5-7 jurnal bereputasi SINTA/Scopus dalam format Markdown Table:
| No | Peneliti & Tahun | Judul Penelitian | Variabel / Metode | Hasil Penelitian | Perbedaan dengan Penelitian Ini |
Diikuti 2 paragraf perbandingan dan gap penelitian).
2.3 Kerangka Konseptual (WAJIB sesuaikan dengan judul '{$project->judul}'. Uraikan hubungan keterkaitan logis antarvariabel independen terhadap variabel dependen berdasarkan landasan teori.
Lalu WAJIB sajikan DIAGRAM KERANGKA KONSEPTUAL di bawah narasi dalam blok kode:
Gambar 2.1 Diagram Jalur Kerangka Konseptual Penelitian
```
[Variabel Independen (X1)] ──┐
                             ├──> [Variabel Dependen (Y)]
[Variabel Independen (X2)] ──┘
```
).
2.4 Pengembangan Hipotesis (Rumuskan hipotesis penelitian H1, H2, H3 secara eksplisit dengan argumen teoretis pendukung).",

            $isPendidikan => "
TUGAS UTAMA: Tuliskan NASKAH LENGKAP BAB II (TINJAUAN PUSTAKA) BIDANG PENDIDIKAN / KEGURUAN (FKIP / TARBIYAH).
Disesuaikan secara persis dengan Judul: '{$project->judul}' dan Program Studi: '{$project->prodi}'.
WAJIB MEMUAT:
2.1 Landasan Teori (Hakikat pembelajaran, model/metode pembelajaran yang diuji, karakteristik hasil belajar/keterampilan siswa, materi ajar).
2.2 Penelitian Terdahulu (WAJIB sajikan TABEL komparasi 4-6 jurnal pendidikan bereputasi SINTA).
2.3 Kerangka Berpikir Tindakan (WAJIB sesuaikan dengan judul '{$project->judul}'. Uraikan alur pemikiran dari kondisi awal rendahnya hasil belajar/keaktifan siswa, penerapan tindakan inovasi pembelajaran, hingga kondisi akhir peningkatan hasil belajar.
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
TUGAS UTAMA: Tuliskan NASKAH LENGKAP BAB II (TINJAUAN PUSTAKA) BIDANG KESEHATAN / FARMASI / KEPERAWATAN / KEDOKTERAN.
Disesuaikan secara persis dengan Judul: '{$project->judul}' dan Program Studi: '{$project->prodi}'.
WAJIB MEMUAT:
2.1 Landasan Teori (Patofisiologi, senyawa aktif/intervensi medis, mekanisme biologis, tolok ukur klinis).
2.2 Penelitian Terdahulu (WAJIB sajikan TABEL perbandingan 4-6 jurnal bereputasi terkait).
2.3 Kerangka Konseptual (WAJIB sesuaikan dengan judul '{$project->judul}'. Uraikan alur keterkaitan antara agen/faktor intervensi, mekanisme respon seluler/klinis, hingga efek terapi/luaran kesehatan pasien.
Lalu WAJIB sajikan DIAGRAM KERANGKA KONSEPTUAL dalam blok kode:
Gambar 2.1 Diagram Kerangka Konseptual Penelitian
```
[Faktor Intervensi / Senyawa Aktif / Asuhan Klinis Sesuai Judul]
↓
[Mekanisme Biologis / Fisiologis / Edukasi Kesehatan]
↓
[Luaran Klinis: Penurunan Gejala, Efektivitas Terapi & Peningkatan Kualitas Hidup]
```
).
2.4 Hipotesis Penelitian.",

            default => "
TUGAS UTAMA: Tuliskan NASKAH LENGKAP BAB II (TINJAUAN PUSTAKA) secara komprehensif berstandar skripsi S1 nasional Indonesia.
Disesuaikan secara persis dengan Judul: '{$project->judul}' dan Program Studi: '{$project->prodi}'.
WAJIB MEMUAT:
2.1 Landasan Teori (Grand theory, teori pendukung, definisi ahli, dan dimensi/indikator operasional).
2.2 Penelitian Terdahulu (WAJIB sajikan TABEL perbandingan minimal 4-5 penelitian jurnal bereputasi 2020-2024 terkait dalam format Markdown Table:
| No | Peneliti & Tahun | Judul Penelitian | Metode yang Digunakan | Hasil / Temuan | Celah Penelitian |).
2.3 Kerangka Pemikiran (WAJIB sesuaikan dengan judul '{$project->judul}'. Uraikan alur pemikiran ilmiah dari gejala fenomena, landasan konsep teori, analisis kritis, hingga perumusan solusi teruji.
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

        // Bab III Sesuai Rumpun Ilmu (Flowchart Sistem & Metodologi Valid)
        $bab3Prompt = match (true) {
            $isInformatika => "
TUGAS: Tulis Isi BAB III (METODOLOGI PENELITIAN KOMPUTASI / INFORMATIKA).
WAJIB MEMUAT SUB-BAB TEKNIS LENGKAP:
3.1 Desain Penelitian & Alur Sistem (Flowchart)
Sajikan penjelasan terperinci dan GAMBARKAN BAGAN ALUR (FLOWCHART SISTEM) menggunakan diagram alur berbasis teks/kotak yang rapi di dalam blok kode, contoh:
```
[Mulai] --> [Pengumpulan Dataset] --> [Text Preprocessing]
             |--> Case Folding, Cleaning, Tokenizing
             |--> Normalisasi Slang & Stopword Removal
             |--> Stemming (Algoritma Sastrawi)
             V
[Pembobotan TF-IDF] --> [Data Splitting 80:20]
             V
[Pelatihan Model Naive Bayes] --> [Pengujian Data Uji]
             V
[Evaluasi: Confusion Matrix (Akurasi, Presisi, Recall, F1)] --> [Selesai]
```
Jelaskan setiap tahapan bagan alur di atas secara terperinci.
3.2 Sumber dan Teknik Pengumpulan Dataset (Jelaskan sumber data, teknik crawling/scraping API, kata kunci pencarian, periode penarikan data, dan total volume korpus data).
3.3 Tahapan Pra-Pemrosesan Data (Text Preprocessing) (Jelaskan mekanisme kerja Case Folding, Cleaning, Tokenizing, Slang Normalization dengan kamus baku, Filtering/Stopword Removal, dan Stemming Sastrawi).
3.4 Pembobotan Fitur dan Pembagian Data (Jabarkan rumus pembobotan TF-IDF dan rasio pembagian dataset latih dan uji, misal 80:20 atau 10-Fold Cross Validation).
3.5 Pemodelan Algoritma Klasifikasi (Tuliskan rumus matematis Teorema Bayes: P(C|X) = P(X|C)P(C) / P(X) beserta penjelasan prior probability, likelihood, dan Laplace Smoothing).
3.6 Metrik Evaluasi Kinerja (Rumuskan Confusion Matrix [TP, TN, FP, FN] serta rumus perhitungan Akurasi, Precision, Recall, dan F1-Score).",

            $isEkonomi => "
TUGAS: Tulis Isi BAB III (METODOLOGI PENELITIAN BISNIS / EKONOMI KUANTITATIF).
3.1 Jenis dan Desain Penelitian (Kuantitatif Asosiatif / Kausal).
3.2 Lokasi, Objek, dan Waktu Penelitian.
3.3 Populasi dan Penentuan Sampel (Teknik sampling, rumus Slovin / Hair et al.).
3.4 Definisi Operasional dan Pengukuran Variabel (WAJIB sajikan TABEL Operasional Variabel dengan kolom: Variabel | Dimensi/Indikator | Butir Pernyataan | Skala Pengukuran [Likert 1-5]).
3.5 Teknik dan Instrumen Pengumpulan Data (Kuesioner / Data Sekunder Laporan Keuangan).
3.6 Uji Instrumen Penelitian (Uji Validitas Corrected Item-Total Correlation dan Uji Reliabilitas Cronbach's Alpha > 0.60).
3.7 Teknik Analisis Data & Uji Hipotesis (Uji Asumsi Klasik: Normalitas, Multikolinearitas, Heteroskedastisitas; dan Analisis Regresi Linier Berganda / SEM-PLS: Uji t, Uji F, Koefisien Determinasi R-Square).",

            $isPendidikan => "
TUGAS: Tulis Isi BAB III (METODOLOGI PENELITIAN PENDIDIKAN / QUASI-EKSPERIMEN).
3.1 Desain Penelitian (Nonequivalent Pretest-Posttest Control Group Design).
3.2 Tempat dan Waktu Penelitian.
3.3 Populasi dan Sampel (Kelas eksperimen vs kelas kontrol).
3.4 Variabel Penelitian dan Definisi Operasional.
3.5 Instrumen Pengumpulan Data (Tes kognitif pretest-posttest dan lembar observasi).
3.6 Uji Coba Instrumen (Uji Validitas Butir, Reliabilitas KR-20/Cronbach, Tingkat Kesukaran, Daya Beda).
3.7 Teknik Analisis Data (Uji Normalitas, Homogenitas, Uji Hipotesis Independent Sample t-test, dan Skor N-Gain).",

            $isRnD => "
TUGAS: Tulis Isi BAB III (METODOLOGI PENGEMBANGAN SISTEM / R&D).
3.1 Model Pengembangan Sistem (SDLC Waterfall / Agile / Prototyping).
3.2 Waktu dan Tempat Penelitian.
3.3 Teknik Pengumpulan Data (Wawancara, Observasi, Studi Pustaka).
3.4 Analisis Kebutuhan Sistem (Kebutuhan Fungsional & Kebutuhan Non-Fungsional).
3.5 Perancangan Sistem (Arsitektur Sistem, Use Case Diagram, Activity Diagram, ERD Basis Data, Wireframe Antarmuka).
3.6 Metode Pengujian Sistem (Black-Box Testing & User Acceptance Testing / SUS).",

            $isKualitatif => "
TUGAS: Tulis Isi BAB III (METODOLOGI PENELITIAN KUALITATIF).
3.1 Pendekatan dan Jenis Penelitian.
3.2 Lokasi dan Waktu Penelitian.
3.3 Fokus Penelitian.
3.4 Sumber Data dan Teknik Pemilihan Informan (Purposive Sampling).
3.5 Teknik Pengumpulan Data (Observasi Partisipatif, Wawancara Mendalam, Dokumentasi).
3.6 Teknik Pemeriksaan Keabsahan Data (Triangulasi Sumber dan Triangulasi Teknik).
3.7 Teknik Analisis Data (Kondensasi Data, Penyajian Data, dan Penarikan Kesimpulan Miles & Huberman).",

            default => "
TUGAS: Tulis Isi BAB III (METODOLOGI PENELITIAN) secara lengkap berstandar S1.
3.1 Jenis dan Desain Penelitian.
3.2 Lokasi dan Waktu Penelitian.
3.3 Populasi dan Sampel Penelitian.
3.4 Definisi Operasional dan Pengukuran Variabel (Sajikan TABEL Indikator Variabel).
3.5 Teknik Pengumpulan Data.
3.6 Uji Keabsahan / Instrumen Data.
3.7 Teknik Analisis Data dan Prosedur Pengujian."
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

        return match ($bab) {
            'bab1' => $master . "\n{$dataAwalPrompt}\n{$strukturKustomPrompt}\n{$referensiKhususPrompt}\n{$dataRiilPrompt}\n" . "
TUGAS UTAMA: Tuliskan NASKAH LENGKAP BAB I (PENDAHULUAN) dari awal sampai akhir secara komprehensif berstandar skripsi S1 nasional Indonesia.
{$customStructureNote}" . (empty($meta['struktur_kustom']) ? "WAJIB MEMUAT SELURUH 7 SUB-BAB BERURUTAN TANPA MELEWATKAN SATU PUN:
1.1 Latar Belakang Masalah (Uraikan 3-4 paragraf ilmiah berbobot: fenomena makro/empiris, fakta lapangan terkini, tinjauan penelitian terdahulu yang relevan, research gap yang nyata, serta urgensi pemilihan judul. JANGAN gunakan kalimat pembuka klise seperti 'Kondisi ideal dalam...').
1.2 Identifikasi Masalah (Tuliskan 3-5 poin permasalahan konkret yang teramati pada objek penelitian dalam format poin bernomor 1., 2., 3., dst.).
1.3 Batasan Masalah (Uraikan batasan ruang lingkup penelitian secara tegas dalam 2-4 poin: batasan variabel/algoritma, objek/sampel, periode data, dan lingkungan implementasi).
1.4 Rumusan Masalah (Tuliskan 2-4 butir pertanyaan penelitian ilmiah yang spesifik, terukur, dan selaras dengan batasan masalah).
1.5 Tujuan Penelitian (Tuliskan 2-4 butir pernyataan tujuan operasional yang menjawab langsung setiap butir Rumusan Masalah).
1.6 Manfaat Penelitian (Uraikan ke dalam dua bagian terpisah yang jelas: 1.6.1 Manfaat Teoretis dan 1.6.2 Manfaat Praktis bagi industri, kampus, dan peneliti selanjutnya).
1.7 Sistematika Penulisan (Uraikan ringkasan isi Bab I hingga Bab V secara terstruktur).\n" : "") . "
" . (!empty($meta['data_awal']) ? "WAJIB: Masukkan data awal penelitian mahasiswa ('{$meta['data_awal']}') secara eksplisit ke dalam Latar Belakang Masalah (1.1) sebagai bukti fenomena nyata!\n" : "") . "
INSTRUKSI MUTLAK:
- Seluruh sub-bab HARUS ditulis lengkap tuntas dalam satu kali respons ini. DILARANG berhenti di tengah jalan!
- Langsung mulai dari sub-bab pertama tanpa kata pengantar AI.",

            'bab2' => $master . "\n$context\n{$strukturKustomPrompt}\n{$referensiKhususPrompt}\n{$dataRiilPrompt}\n{$bab2Prompt}\n
{$customStructureNote}
INSTRUKSI MUTLAK:
- Seluruh sub-bab dan TABEL PENELITIAN TERDAHULU HARUS ditulis lengkap tuntas tanpa terpotong.
- Langsung mulai dari sub-bab pertama tanpa kata pengantar AI.",

            'bab3' => $master . "\n$context\n{$dataAwalPrompt}\n{$strukturKustomPrompt}\n{$referensiKhususPrompt}\n{$dataRiilPrompt}\n{$bab3Prompt}\n
{$customStructureNote}
INSTRUKSI MUTLAK:
- Tulis seluruh sub-bab secara tuntas. Jika bidang informatika/komputer, WAJIB sertakan diagram alur flowchart sistem dan rumus matematis algoritma.",

            'bab4' => $master . "\n$context\n{$dataAwalPrompt}\n{$strukturKustomPrompt}\n{$referensiKhususPrompt}\n{$dataRiilPrompt}\n{$bab4Prompt}\n
{$customStructureNote}
INSTRUKSI MUTLAK:
- WAJIB menyajikan TABEL pengolahan data empiris (Tabel Confusion Matrix & Metrik Akurasi untuk Informatika, atau Tabel Regresi & Asumsi Klasik untuk Ekonomi) beserta perhitungan rumus langkah demi langkah.",

            'bab5' => $master . "\n$context\n{$dataAwalPrompt}\n{$strukturKustomPrompt}\n{$referensiKhususPrompt}\n{$dataRiilPrompt}\n{$bab5Prompt}",

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

            if ($chapter === 'bab5') {
                $parts = explode('===DAFTAR PUSTAKA===', $cleanContent);
                if (count($parts) > 1) {
                    $project->bab5_content = trim($parts[0]);
                    $project->daftar_pustaka = $this->cleanDaftarPustaka(end($parts));
                } else {
                    $partsRegex = preg_split('/(DAFTAR PUSTAKA|REFERENSI)/i', $cleanContent);
                    if (count($partsRegex) > 1) {
                        $project->bab5_content = trim($partsRegex[0]);
                        $project->daftar_pustaka = $this->cleanDaftarPustaka(end($partsRegex));
                    } else {
                        $project->bab5_content = $cleanContent;
                    }
                }
            } else {
                $project->{$chapter . '_content'} = $cleanContent;
                // Ekstrak sitasi otomatis ke daftar pustaka sementara saat Bab III selesai (Proposal) atau jika daftar pustaka masih kosong
                if ((empty($project->daftar_pustaka) && in_array($chapter, ['bab1', 'bab2', 'bab3'])) || $chapter === 'bab3') {
                    try {
                        $allText = strip_tags($project->bab1_content . ' ' . $project->bab2_content . ' ' . $project->bab3_content);
                        if (strlen($allText) > 500) {
                            $sitasi = $meta['sitasi'] ?? [];
                            $gayaSitasi = $sitasi['gaya'] ?? 'APA 7th';
                            $tahunMin = $sitasi['tahun_min'] ?? '2021';
                            $dpPrompt = "Tulis DAFTAR PUSTAKA gaya {$gayaSitasi} berdasarkan sitasi nama penulis dan tahun yang dikutip pada naskah proposal/skripsi berjudul '{$project->judul}' (Prodi: {$project->prodi}).\n" .
                                (!empty($meta['referensi_khusus']) ? "REFERENSI WAJIB UNGGAHAN MAHASISWA (WAJIB MASUK):\n" . Str::limit($meta['referensi_khusus'], 2500) . "\n" : "") .
                                "Tahun penerbitan minimal: {$tahunMin}.\n" .
                                "ATURAN KETAT:\n1. LANGSUNG tuliskan baris referensi pertama (A-Z) TANPA kalimat pembuka/pengantar seperti 'Berikut adalah daftar pustaka...'.\n2. DILARANG menulis placeholder 'Judul artikel tentang...' atau 'Nama Jurnal, Volume(Issue), Halaman'. Setiap referensi WAJIB memiliki judul artikel ilmiah lengkap dan spesifik, nama jurnal nyata, volume, nomor, dan halaman spesifik (contoh: 14(2), 112-125).\n3. Tanpa tanda bintang markdown (*).\n\nNASKAH:\n" . Str::limit($allText, 5500);
                            $dpRaw = $this->ai->generateText($dpPrompt, $model);
                            $cleanedDp = $this->cleanDaftarPustaka($dpRaw);
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
            return response()->json(['error' => 'Gagal merevisi bab: ' . $e->getMessage()], 500);
        }
    }

    public function saveChapterManual(Request $request, $id)
    {
        $request->validate([
            'chapter' => 'required|in:bab1,bab2,bab3,bab4,bab5,daftar_pustaka,front,front_logo',
            'content' => 'required|string',
        ]);

        $project = Project::where('user_id', Auth::id())->findOrFail($id);

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

    private function formatAcademicContentForPdf(string $content): string
    {
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $lines = explode("\n", $content);
        $html = '';
        $inOl = false;
        $inUl = false;
        $inCode = false;
        $codeLines = [];
        $tableBuffer = [];

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

        $flushTable = function () use (&$html, &$tableBuffer) {
            if (empty($tableBuffer))
                return;
            $rows = [];
            foreach ($tableBuffer as $tLine) {
                $trimmed = trim($tLine, "| \t\n\r");
                // Lewati baris separator markdown seperti |---|---|
                if (preg_match('/^[\s\-:|]+$/', $trimmed))
                    continue;
                $cells = array_map('trim', explode('|', $trimmed));
                if (!empty($cells)) {
                    $rows[] = $cells;
                }
            }
            if (!empty($rows)) {
                $html .= "<table style='width:100%; border-collapse:collapse; margin:10pt 0 16pt 0; font-size:10pt; font-family:\"Times New Roman\", serif; page-break-inside:avoid;'>";
                foreach ($rows as $rIdx => $cols) {
                    $isHeader = ($rIdx === 0);
                    if ($isHeader) {
                        $html .= "<thead><tr style='background:#f8fafc; border-top:1.5pt solid #000; border-bottom:1pt solid #000;'>";
                        foreach ($cols as $col) {
                            $cleanCol = trim(preg_replace('/^(\*{1,2}|<b>)+|(\*{1,2}|<\/b>)+$/i', '', $col));
                            $html .= "<th style='padding:5pt 7pt; font-weight:bold; text-align:center; border-left:none; border-right:none;'>" . e($cleanCol) . "</th>";
                        }
                        $html .= "</tr></thead><tbody>";
                    } else {
                        $borderStyle = ($rIdx === count($rows) - 1) ? 'border-bottom:1.5pt solid #000;' : 'border-bottom:0.5pt solid #cbd5e1;';
                        $html .= "<tr style='{$borderStyle}'>";
                        foreach ($cols as $cIdx => $col) {
                            $cleanCol = trim(preg_replace('/^(\*{1,2}|<b>)+|(\*{1,2}|<\/b>)+$/i', '', $col));
                            $align = preg_match('/^[\d\s,.\-%—\(\)]+$/', $cleanCol) ? 'center' : 'left';
                            $html .= "<td style='padding:4pt 7pt; text-align:{$align}; border-left:none; border-right:none;'>" . e($cleanCol) . "</td>";
                        }
                        $html .= "</tr>";
                    }
                }
                $html .= "</tbody></table>";
            }
            $tableBuffer = [];
        };

        $flushCodeOrFlowchart = function () use (&$html, &$codeLines) {
            if (empty($codeLines))
                return;
            $rawCode = implode("\n", $codeLines);
            $isFlowchart = str_contains($rawCode, '-->') || str_contains($rawCode, '->') || str_contains($rawCode, '↓') || str_contains($rawCode, 'V') || preg_match('/\[.+?\]/', $rawCode);

            if ($isFlowchart) {
                preg_match_all('/\[(.*?)\]/', $rawCode, $nodeMatches);
                $nodes = !empty($nodeMatches[1]) ? $nodeMatches[1] : array_filter(array_map('trim', $codeLines), fn($l) => !empty($l) && !str_starts_with($l, 'graph') && !preg_match('/^[↓V\-\->\s|]+$/', $l));
                if (!empty($nodes)) {
                    $html .= "<div class='academic-flowchart' style='margin:14pt auto 18pt auto; text-align:center; width:100%; page-break-inside:avoid;'>";
                    $nodeCount = count($nodes);
                    foreach (array_values($nodes) as $nIdx => $node) {
                        $cleanNode = trim(strip_tags($node));
                        if (empty($cleanNode) || preg_match('/^[↓V\-\->\s|]+$/', $cleanNode))
                            continue;
                        $isSE = in_array(strtolower($cleanNode), ['mulai', 'start', 'selesai', 'end', 'finish']);
                        $boxBg = $isSE ? '#eff6ff' : '#f8fafc';
                        $boxBorder = $isSE ? '#2563eb' : '#475569';
                        $textColor = $isSE ? '#1e40af' : '#0f172a';
                        $radius = $isSE ? '14pt' : '4pt';
                        $weight = $isSE ? 'bold' : 'normal';
                        $html .= "<div style='display:inline-block; padding:6pt 16pt; border:1.5pt solid {$boxBorder}; border-radius:{$radius}; background:{$boxBg}; font-size:10.5pt; color:{$textColor}; font-weight:{$weight}; max-width:400pt; text-align:center; margin:0 auto;'>" . e($cleanNode) . "</div>";
                        if ($nIdx < $nodeCount - 1) {
                            $html .= "<div style='font-size:14pt; color:#2563eb; line-height:1.2; margin:3pt 0; text-align:center;'>↓</div>";
                        }
                    }
                    $html .= "</div>";
                }
            } else {
                $html .= "<pre style='background:#f8fafc; border:1pt solid #cbd5e1; border-radius:4pt; padding:8pt 12pt; font-size:10pt; font-family:monospace; line-height:1.45; white-space:pre-wrap; word-break:break-all; margin:10pt 0;'>" . e($rawCode) . "</pre>";
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

            // Buffer tabel markdown
            if (str_starts_with($trimmed, '|') && str_ends_with($trimmed, '|')) {
                $closeLists();
                $tableBuffer[] = $trimmed;
                continue;
            } else {
                $flushTable();
            }

            // Judul Tabel / Gambar / Bagan
            if (preg_match('/^(?:Tabel|Gambar|Bagan)\s+\d+\.\d+/i', $trimmed)) {
                $closeLists();
                $align = str_starts_with(strtolower($trimmed), 'tabel') ? 'left' : 'center';
                $cleanCap = trim(preg_replace('/^(\*{1,2}|<b>)+|(\*{1,2}|<\/b>)+$/i', '', $trimmed));
                $html .= "<div style='font-size:11pt; font-weight:bold; margin-top:14pt; margin-bottom:4pt; text-align:{$align}; text-indent:0; page-break-after:avoid;'>" . e($cleanCap) . "</div>";
                continue;
            }

            // Normalisasi sub-bab tanpa titik: "A Latar Belakang" -> "A. Latar Belakang"
            if (preg_match('/^([A-Z])\s+([A-Z][a-zA-Z\s]+)/', $trimmed, $m)) {
                $trimmed = $m[1] . '. ' . $m[2];
            }
            if (preg_match('/^(\d+)\s+([A-Z][a-zA-Z\s]+)/', $trimmed, $m)) {
                $trimmed = $m[1] . '. ' . $m[2];
            }

            // Sub-bab utama (A. ..., 1.1 ..., ## ...)
            if (preg_match('/^(?:##\s+|[A-Z]\.\s+|\d+\.\d+\.?\s+)(.+)$/', $trimmed, $m)) {
                $closeLists();
                $clean = trim(preg_replace('/^(\*{1,2}|#+\s*|<b>)+|(\*{1,2}|<\/b>)+$/i', '', $trimmed));
                $html .= "<h3 style='font-size:12pt; font-weight:bold; margin-top:16pt; margin-bottom:6pt; text-align:left; text-indent:0; page-break-after:avoid; break-after:avoid;'>" . e($clean) . "</h3>";
                continue;
            }

            // Sub-sub-bab (### ..., 1.6.1 ..., atau 1. Manfaat Teoretis)
            if (preg_match('/^(?:###\s+|\d+\.\d+\.\d+\.?\s+)(.+)$/', $trimmed, $m) || preg_match('/^\d+\.\s+(Manfaat\s+[A-Za-z]+|Tujuan\s+[A-Za-z]+)/i', $trimmed)) {
                $closeLists();
                $clean = trim(preg_replace('/^(\*{1,2}|#+\s*|<b>)+|(\*{1,2}|<\/b>)+$/i', '', $trimmed));
                $html .= "<h4 style='font-size:12pt; font-weight:bold; margin-top:10pt; margin-bottom:4pt; text-align:left; text-indent:0; page-break-after:avoid; break-after:avoid;'>" . e($clean) . "</h4>";
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
                $cleanLi = trim(preg_replace('/^(\*{1,2}|<b>)+|(\*{1,2}|<\/b>)+$/i', '', $m[1]));
                $html .= "<li style='text-align:justify; line-height:1.5; margin-bottom:4pt; page-break-inside:avoid;'>" . e($cleanLi) . "</li>";
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
                $cleanLi = trim(preg_replace('/^(\*{1,2}|<b>)+|(\*{1,2}|<\/b>)+$/i', '', $m[1]));
                $html .= "<li style='text-align:justify; line-height:1.5; margin-bottom:4pt; page-break-inside:avoid;'>" . e($cleanLi) . "</li>";
                continue;
            }

            // Paragraf ilmiah baku atau Rumus Matematika Standalone
            $closeLists();
            $cleanP = trim(preg_replace('/^(\*{1,2}|#+\s*)+|(\*{1,2})+$/', '', $trimmed));
            if ($cleanP !== '') {
                if ($this->isStandaloneFormula($cleanP)) {
                    $html .= "<p style='text-align:center; text-indent:0; margin:10pt 0; font-size:12pt; font-family:\"Times New Roman\", serif; font-style:normal; line-height:1.6;'>" . e($cleanP) . "</p>";
                } else {
                    $html .= "<p style='text-indent:1.27cm; text-align:justify; line-height:1.5; margin:0 0 8pt 0; orphans:3; widows:3; page-break-inside:auto;'>" . e($cleanP) . "</p>";
                }
            }
        }

        $closeLists();
        $flushTable();
        $flushCodeOrFlowchart();
        return $html;
    }

    private function parseAndAddContent($section, $htmlContent)
    {
        $text = str_replace(['<p>', '</p>', '<br>', '<br/>'], ["\n", "\n", "\n", "\n"], $htmlContent);
        $lines = explode("\n", $text);

        $tableBuffer = [];
        $flushTable = function () use (&$tableBuffer, $section) {
            if (empty($tableBuffer))
                return;
            $rows = [];
            foreach ($tableBuffer as $tLine) {
                $trimmed = trim($tLine, "| \t\n\r");
                // Lewati baris separator markdown seperti |---|---|
                if (preg_match('/^[\s\-:|]+$/', $trimmed))
                    continue;
                $cells = array_map('trim', explode('|', $trimmed));
                if (!empty($cells)) {
                    $rows[] = $cells;
                }
            }
            if (!empty($rows)) {
                $maxCols = max(array_map('count', $rows));
                $colWidthTwips = max(1200, (int) round(9200 / max(1, $maxCols)));
                $table = $section->addTable([
                    'borderColor' => '000000',
                    'borderSize' => 6,
                    'cellMarginTop' => 80,
                    'cellMarginBottom' => 80,
                    'cellMarginLeft' => 100,
                    'cellMarginRight' => 100,
                    'alignment' => Jc::CENTER
                ]);
                foreach ($rows as $rIdx => $rowCells) {
                    $tRow = $table->addRow();
                    foreach ($rowCells as $cIdx => $cellText) {
                        $isHeader = ($rIdx === 0);
                        $cleanCell = trim(preg_replace('/^(\*{1,2}|<b>)+|(\*{1,2}|<\/b>)+$/i', '', $cellText));
                        $cell = $tRow->addCell($colWidthTwips, [
                            'bgColor' => $isHeader ? 'F1F5F9' : 'FFFFFF',
                            'valign' => 'center'
                        ]);
                        $align = ($isHeader || preg_match('/^[\d\s,.\-%—\(\)]+$/', $cleanCell)) ? Jc::CENTER : Jc::LEFT;
                        $cell->addText(
                            $cleanCell,
                            ['name' => 'Times New Roman', 'size' => 10, 'bold' => $isHeader],
                            ['alignment' => $align, 'spaceAfter' => 0]
                        );
                    }
                }
                $section->addTextBreak(1);
            }
            $tableBuffer = [];
        };

        $codeLines = [];
        $inCode = false;
        $flushCodeOrFlowchart = function () use (&$codeLines, $section) {
            if (empty($codeLines))
                return;
            $rawCode = implode("\n", $codeLines);
            $isFlowchart = str_contains($rawCode, '-->') || str_contains($rawCode, '->') || str_contains($rawCode, '↓') || str_contains($rawCode, 'V') || preg_match('/\[.+?\]/', $rawCode);

            if ($isFlowchart) {
                preg_match_all('/\[(.*?)\]/', $rawCode, $nodeMatches);
                $nodes = !empty($nodeMatches[1]) ? $nodeMatches[1] : array_filter(array_map('trim', $codeLines), fn($l) => !empty($l) && !str_starts_with($l, 'graph') && !preg_match('/^[↓V\-\->\s|]+$/', $l));
                if (!empty($nodes)) {
                    $nodeCount = count($nodes);
                    foreach (array_values($nodes) as $nIdx => $node) {
                        $cleanNode = trim(strip_tags($node));
                        if (empty($cleanNode) || preg_match('/^[↓V\-\->\s|]+$/', $cleanNode))
                            continue;
                        $isSE = in_array(strtolower($cleanNode), ['mulai', 'start', 'selesai', 'end', 'finish']);
                        $boxTable = $section->addTable(['alignment' => Jc::CENTER]);
                        $boxRow = $boxTable->addRow();
                        $boxCell = $boxRow->addCell(Converter::cmToTwip(9.5), [
                            'bgColor' => $isSE ? 'EFF6FF' : 'F8FAFC',
                            'borderColor' => $isSE ? '2563EB' : '475569',
                            'borderSize' => 10,
                            'cellMarginTop' => 70,
                            'cellMarginBottom' => 70,
                            'cellMarginLeft' => 120,
                            'cellMarginRight' => 120,
                            'valign' => 'center'
                        ]);
                        $boxCell->addText($cleanNode, [
                            'name' => 'Times New Roman',
                            'size' => 10.5,
                            'bold' => $isSE,
                            'color' => $isSE ? '1E40AF' : '0F172A'
                        ], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);

                        if ($nIdx < $nodeCount - 1) {
                            $section->addText('↓', [
                                'name' => 'Times New Roman',
                                'size' => 14,
                                'bold' => true,
                                'color' => '2563EB'
                            ], ['alignment' => Jc::CENTER, 'spaceBefore' => 30, 'spaceAfter' => 30]);
                        }
                    }
                    $section->addTextBreak(1);
                }
            } else {
                $section->addText($rawCode, ['name' => 'Courier New', 'size' => 9.5], ['indentation' => ['left' => 360], 'spaceAfter' => 80]);
            }
            $codeLines = [];
        };

        foreach ($lines as $line) {
            $cleanLine = trim(strip_tags($line));

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

            // Buffer baris tabel markdown
            if (str_starts_with($cleanLine, '|') && str_ends_with($cleanLine, '|')) {
                $tableBuffer[] = $cleanLine;
                continue;
            } else {
                $flushTable();
            }

            // Bersihkan tanda markdown untuk pencocokan heading
            $unmarked = trim(preg_replace('/^(\*{1,2}|#+\s*|<b>)+|(\*{1,2}|<\/b>)+$/i', '', $cleanLine));

            // Heading Level 3: 2.1.1, 2.1.2, 1.6.1, dst.
            if (preg_match('/^(\d+\.\d+\.\d+[\s\.:\-\)][^\n<]+)/i', $unmarked, $m3) || preg_match('/^\d+\.\s+(Manfaat|Tujuan)\s+[A-Za-z]+/i', $unmarked, $m3)) {
                $section->addTitle($unmarked, 3);
            }
            // Heading Level 2: 1.1, 1.2, 2.1, 3.1, dst.
            elseif (preg_match('/^(\d+\.\d+[\s\.:\-\)][^\n<]+)/i', $unmarked, $m2) || preg_match('/^([A-Z]\.[\s\.:\-\)][^\n<]+)/', $unmarked, $ma)) {
                $section->addTitle($unmarked, 2);
            }
            // Judul Tabel / Gambar / Bagan
            elseif (preg_match('/^(?:Tabel|Gambar|Bagan)\s+\d+\.\d+/i', $unmarked)) {
                $align = str_starts_with(strtolower($unmarked), 'tabel') ? Jc::LEFT : Jc::CENTER;
                $section->addText(
                    $unmarked,
                    ['bold' => true, 'name' => 'Times New Roman', 'size' => 11],
                    ['alignment' => $align, 'spaceBefore' => 140, 'spaceAfter' => 60, 'indentation' => ['firstLine' => 0]]
                );
            }
            // Poin bernomor: 1. 2. dst.
            elseif (preg_match('/^\d+\.\s+/', $unmarked)) {
                $section->addText(
                    $unmarked,
                    ['name' => 'Times New Roman', 'size' => 12],
                    ['alignment' => Jc::BOTH, 'lineHeight' => 1.5, 'indentation' => ['left' => 420, 'hanging' => 360], 'spaceAfter' => 60]
                );
            }
            // Rumus Matematika / Persamaan Standalone (Center-aligned tanpa indentasi)
            elseif ($this->isStandaloneFormula($unmarked)) {
                $pStyle = ['alignment' => Jc::CENTER, 'lineHeight' => 1.5, 'indentation' => ['firstLine' => 0], 'spaceBefore' => 140, 'spaceAfter' => 140];
                $section->addText($unmarked, ['name' => 'Times New Roman', 'size' => 12], $pStyle);
            }
            // Paragraf biasa dengan standar indentasi skripsi 1.27 cm
            else {
                $pStyle = ['alignment' => Jc::BOTH, 'lineHeight' => 1.5, 'indentation' => ['firstLine' => 720], 'spaceAfter' => 80];
                $section->addText($unmarked, ['name' => 'Times New Roman', 'size' => 12], $pStyle);
            }
        }
        $flushTable();
        $flushCodeOrFlowchart();
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

            $tmpPath = '/tmp/logo_' . uniqid() . '.png';
            if (function_exists('imagecreatefromstring')) {
                $img = @imagecreatefromstring($rawBytes);
                if ($img !== false) {
                    imagealphablending($img, false);
                    imagesavealpha($img, true);
                    imagepng($img, $tmpPath);
                    imagedestroy($img);
                    if (file_exists($tmpPath)) {
                        return $tmpPath;
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
        $phpWord = new PhpWord();
        Settings::setOutputEscapingEnabled(true);
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(12);

        $marginTop = (float) $request->input('margin_top', 4);
        $marginLeft = (float) $request->input('margin_left', 4);
        $marginBottom = (float) $request->input('margin_bottom', 3);
        $marginRight = (float) $request->input('margin_right', 3);

        $tahap = strtolower((string) ($meta['tahap_pengerjaan'] ?? ''));
        $defaultJenis = str_contains($tahap, 'proposal') ? 'PROPOSAL SKRIPSI' : 'SKRIPSI';

        $nama = $request->input('nama') ?: ($meta['nama'] ?? 'NAMA MAHASISWA');
        $npm = $request->input('npm') ?: ($meta['nim'] ?? 'NIM / NPM');
        $kampus = $request->input('kampus') ?: ($meta['kampus'] ?? 'UNIVERSITAS');
        $fakultas = $request->input('fakultas') ?: ($meta['fakultas'] ?? '');
        $jenisDokumen = strtoupper($request->input('jenis_dokumen', $defaultJenis));
        $isProposal = str_contains($jenisDokumen, 'PROPOSAL');

        $phpWord->setDefaultParagraphStyle([
            'lineHeight' => 1.5,
            'spaceAfter' => Converter::pointToTwip(0)
        ]);

        $phpWord->addTitleStyle(1, ['name' => 'Times New Roman', 'size' => 14, 'bold' => true], ['alignment' => Jc::CENTER, 'spaceAfter' => 240, 'indentation' => ['firstLine' => 0]]);
        $phpWord->addTitleStyle(2, ['name' => 'Times New Roman', 'size' => 12, 'bold' => true], ['alignment' => Jc::LEFT, 'spaceBefore' => 140, 'spaceAfter' => 100, 'indentation' => ['firstLine' => 0]]);
        $phpWord->addTitleStyle(3, ['name' => 'Times New Roman', 'size' => 12, 'bold' => true], ['alignment' => Jc::LEFT, 'spaceBefore' => 100, 'spaceAfter' => 60, 'indentation' => ['firstLine' => 0]]);

        $sectionStyle = [
            'pageSizeW' => Converter::cmToTwip(21.0),
            'pageSizeH' => Converter::cmToTwip(29.7),
            'marginTop' => Converter::cmToTwip($marginTop),
            'marginLeft' => Converter::cmToTwip($marginLeft),
            'marginBottom' => Converter::cmToTwip($marginBottom),
            'marginRight' => Converter::cmToTwip($marginRight)
        ];

        $section = $phpWord->addSection($sectionStyle);
        $center = ['alignment' => Jc::CENTER, 'indentation' => ['firstLine' => 0]];
        $bold14 = ['bold' => true, 'size' => 14];
        $bold12 = ['bold' => true, 'size' => 12];

        // --- HALAMAN SAMPUL (COVER) ---
        $section->addText($jenisDokumen, $bold14, $center);
        $section->addTextBreak(1);
        $section->addText(strtoupper($project->judul), $bold14, $center);
        $section->addTextBreak(1);
        $section->addText("Diajukan sebagai Salah Satu Syarat untuk Memperoleh Gelar Sarjana pada Program Studi " . $project->prodi, ['size' => 11], $center);
        $section->addTextBreak(1);

        $defaultLogoFile = public_path('images/default_emblem.png');
        $resolvedLogo = ($logoPath && file_exists($logoPath)) ? $logoPath : (file_exists($defaultLogoFile) ? $defaultLogoFile : null);

        if ($resolvedLogo && file_exists($resolvedLogo)) {
            try {
                $section->addImage($resolvedLogo, [
                    'width' => 95,
                    'height' => 95,
                    'alignment' => Jc::CENTER,
                ]);
            } catch (Throwable $e) {
            }
        }

        $section->addTextBreak(1);
        $section->addText("OLEH:", $bold12, $center);
        $section->addText(strtoupper($nama), ['bold' => true, 'underline' => 'single'], $center);
        if (!empty($npm)) {
            $section->addText("NIM: " . $npm, $bold12, $center);
        }
        $section->addTextBreak(2);
        $section->addText(strtoupper("PROGRAM STUDI " . $project->prodi), $bold14, $center);
        if (!empty($fakultas)) {
            $section->addText(strtoupper($fakultas), $bold14, $center);
        }
        $section->addText(strtoupper($kampus), $bold14, $center);
        $section->addText(date('Y'), $bold14, $center);
        $section->addPageBreak();

        // --- LEMBAR PENGESAHAN ---
        $section->addTitle("LEMBAR PENGESAHAN", 1);
        $section->addTextBreak(1);
        $section->addText(strtoupper($project->judul), $bold12, $center);
        $section->addTextBreak(1);
        $section->addText("Disusun Oleh: " . strtoupper($nama) . ($npm ? " (" . $npm . ")" : ""), ['size' => 11], $center);
        $section->addTextBreak(1);
        $section->addText(
            "Telah diuji dan dipertahankan di hadapan Tim Dewan Penguji Ujian " . $jenisDokumen . " pada Program Studi " . $project->prodi . ($fakultas ? ", " . $fakultas : "") . ($kampus ? ", " . $kampus : "") . ", dan dinyatakan telah memenuhi seluruh persyaratan akademik kelulusan.",
            ['name' => 'Times New Roman', 'size' => 11],
            ['alignment' => Jc::BOTH, 'lineHeight' => 1.5, 'indentation' => ['firstLine' => 720], 'spaceAfter' => 200]
        );
        $section->addTextBreak(1);

        $table = $section->addTable(['alignment' => Jc::CENTER]);
        $row = $table->addRow();
        $cell1 = $row->addCell(Converter::cmToTwip(7.5));
        $cell1->addText("Dosen Pembimbing I", ['size' => 11], $center);
        $cell1->addTextBreak(3);
        $cell1->addText("( __________________________ )", ['size' => 11], $center);
        $cell1->addText("NIP/NIDN.", ['size' => 9.5, 'color' => '666666'], $center);

        $cell2 = $row->addCell(Converter::cmToTwip(7.5));
        $cell2->addText("Dosen Pembimbing II", ['size' => 11], $center);
        $cell2->addTextBreak(3);
        $cell2->addText("( __________________________ )", ['size' => 11], $center);
        $cell2->addText("NIP/NIDN.", ['size' => 9.5, 'color' => '666666'], $center);

        $section->addTextBreak(2);
        $section->addText("Mengetahui,", ['size' => 11], $center);
        $section->addText("Ketua Program Studi " . $project->prodi, ['size' => 11], $center);
        $section->addTextBreak(3);
        $section->addText("( __________________________ )", ['size' => 11], $center);
        $section->addText("NIP/NIDN.", ['size' => 9.5, 'color' => '666666'], $center);
        $section->addPageBreak();

        // --- SURAT PERNYATAAN KEASLIAN ---
        $section->addTitle("SURAT PERNYATAAN KEASLIAN", 1);
        $section->addTextBreak(1);
        $section->addText("Saya yang bertanda tangan di bawah ini:", ['size' => 11], ['alignment' => Jc::BOTH]);
        $section->addText("Nama Mahasiswa\t: " . $nama, ['bold' => true, 'size' => 11], ['indentation' => ['left' => 360]]);
        $section->addText("NIM / NPM\t: " . $npm, ['size' => 11], ['indentation' => ['left' => 360]]);
        $section->addText("Program Studi\t: " . $project->prodi, ['size' => 11], ['indentation' => ['left' => 360]]);
        if (!empty($fakultas)) {
            $section->addText("Fakultas\t: " . $fakultas, ['size' => 11], ['indentation' => ['left' => 360]]);
        }
        $section->addTextBreak(1);
        $section->addText(
            "Menyatakan dengan sesungguhnya bahwa " . strtolower($jenisDokumen) . " berjudul \"" . $project->judul . "\" ini adalah murni hasil karya penelitian, pemikiran, dan penulisan mandiri saya sendiri. Karya ini belum pernah diajukan untuk memperoleh gelar akademik di perguruan tinggi mana pun, dan bebas dari unsur plagiarisme atau fabrikasi data.",
            ['name' => 'Times New Roman', 'size' => 11],
            ['alignment' => Jc::BOTH, 'lineHeight' => 1.5, 'indentation' => ['firstLine' => 720], 'spaceAfter' => 120]
        );
        $section->addText(
            "Seluruh kutipan, data, dan pemikiran yang bersumber dari pihak lain telah dicantumkan dalam daftar pustaka sesuai dengan etika ilmiah. Apabila di kemudian hari terbukti terdapat plagiarisme, saya bersedia menerima sanksi akademik sesuai aturan yang berlaku.",
            ['name' => 'Times New Roman', 'size' => 11],
            ['alignment' => Jc::BOTH, 'lineHeight' => 1.5, 'indentation' => ['firstLine' => 720], 'spaceAfter' => 160]
        );
        $section->addText(($kampus ? $kampus . ", " : "") . date('d F Y'), ['size' => 11], ['alignment' => Jc::RIGHT]);
        $section->addText("Yang membuat pernyataan,", ['size' => 11], ['alignment' => Jc::RIGHT]);
        $section->addText("[ Materai Rp 10.000 ]", ['size' => 8.5, 'color' => '666666'], ['alignment' => Jc::RIGHT]);
        $section->addTextBreak(2);
        $section->addText(strtoupper($nama), ['bold' => true, 'underline' => 'single', 'size' => 11], ['alignment' => Jc::RIGHT]);
        $section->addText("NIM. " . $npm, ['size' => 11], ['alignment' => Jc::RIGHT]);
        $section->addPageBreak();

        // --- ABSTRAK (BAHASA INDONESIA) ---
        $section->addTitle("ABSTRAK", 1);
        $section->addTextBreak(1);
        $section->addText(strtoupper($project->judul), $bold12, $center);
        $section->addText("Oleh: " . $nama . ($npm ? " (NIM: " . $npm . ")" : ""), ['size' => 10.5], $center);
        $section->addText("Program Studi " . $project->prodi . ($kampus ? ", " . $kampus : ""), ['size' => 10, 'color' => '444444'], $center);
        $section->addTextBreak(1);
        $section->addText(
            "Penelitian ini bertujuan untuk menginvestigasi dan menyelesaikan permasalahan pada topik \"" . $project->judul . "\". Metode penelitian yang diterapkan adalah " . ($project->metode_penelitian ?: 'Kuantitatif / Kualitatif Ilmiah') . " dengan prosedur sistematis dan terukur. Hasil penelitian menunjukkan bahwa pendekatan yang dirumuskan mampu memberikan solusi teruji, tolok ukur kinerja yang solid, serta kontribusi empiris bagi pengembangan ilmu pengetahuan dan praktik pada bidang " . $project->prodi . ".",
            ['name' => 'Times New Roman', 'size' => 11],
            ['alignment' => Jc::BOTH, 'lineHeight' => 1.45, 'indentation' => ['firstLine' => 720], 'spaceAfter' => 120]
        );
        $section->addText("Kata Kunci: " . $project->prodi . ", " . ($project->metode_penelitian ?: 'Metode Penelitian') . ", Evaluasi Kinerja, Skripsi S1.", ['bold' => true, 'size' => 11], ['alignment' => Jc::BOTH]);
        $section->addPageBreak();

        // --- ABSTRACT (ENGLISH) ---
        $section->addTitle("ABSTRACT", 1);
        $section->addTextBreak(1);
        $section->addText(strtoupper($project->judul), ['bold' => true, 'italic' => true, 'size' => 12], $center);
        $section->addText("By: " . $nama . ($npm ? " (Student ID: " . $npm . ")" : ""), ['italic' => true, 'size' => 10.5], $center);
        $section->addText("Department of " . $project->prodi . ($kampus ? ", " . $kampus : ""), ['italic' => true, 'size' => 10, 'color' => '444444'], $center);
        $section->addTextBreak(1);
        $section->addText(
            "This study aims to investigate and address the empirical challenges in \"" . $project->judul . "\". Utilizing a scientific methodology in " . ($project->metode_penelitian ?: 'scientific inquiry') . ", this research systematically collects and analyzes relevant data. The results demonstrate that the proposed method effectively accomplishes the research objectives, yields verifiable benchmark performance, and delivers valuable contributions to both academic literature and practical applications in " . $project->prodi . ".",
            ['name' => 'Times New Roman', 'italic' => true, 'size' => 11],
            ['alignment' => Jc::BOTH, 'lineHeight' => 1.45, 'indentation' => ['firstLine' => 720], 'spaceAfter' => 120]
        );
        $section->addText("Keywords: " . $project->prodi . ", Empirical Research, Performance Evaluation, Undergraduate Thesis.", ['bold' => true, 'italic' => true, 'size' => 11], ['alignment' => Jc::BOTH]);
        $section->addPageBreak();

        // --- KATA PENGANTAR ---
        $section->addTitle("KATA PENGANTAR", 1);
        $section->addText(
            "Puji syukur penulis panjatkan ke hadirat Tuhan Yang Maha Esa atas segala rahmat dan karunia-Nya sehingga penyusunan {$jenisDokumen} yang berjudul \"" . $project->judul . "\" dapat diselesaikan sebagai salah satu syarat akademik pada Program Studi " . $project->prodi . ($kampus ? ", " . $kampus : "") . ".",
            ['name' => 'Times New Roman', 'size' => 12],
            ['alignment' => Jc::BOTH, 'lineHeight' => 1.5, 'indentation' => ['firstLine' => 720], 'spaceAfter' => 120]
        );
        $section->addText(
            "Penulis menyadari bahwa penyusunan karya ilmiah ini masih terbuka terhadap saran dan masukan konstruktif demi penyempurnaan di masa mendatang. Semoga dokumen ini memberikan manfaat bagi pengembangan ilmu pengetahuan dan pihak yang membutuhkan.",
            ['name' => 'Times New Roman', 'size' => 12],
            ['alignment' => Jc::BOTH, 'lineHeight' => 1.5, 'indentation' => ['firstLine' => 720], 'spaceAfter' => 240]
        );
        $section->addText($kampus . ", " . date('F Y'), ['name' => 'Times New Roman', 'size' => 12], ['alignment' => Jc::RIGHT]);
        $section->addText("Penulis (" . $nama . ")", ['bold' => true, 'name' => 'Times New Roman', 'size' => 12], ['alignment' => Jc::RIGHT]);
        $section->addPageBreak();

        // --- DAFTAR ISI RAPI (DOT LEADER TAB STOP) ---
        $section->addTitle("DAFTAR ISI", 1);
        $printableWidthTwips = Converter::cmToTwip(max(10.0, 21.0 - $marginLeft - $marginRight));
        $tocItems = $this->extractTocItemsForProject($project, $isProposal);
        foreach ($tocItems as $item) {
            $isLvl1 = ($item['level'] ?? 1) === 1;
            $pStyle = [
                'tabs' => [new Tab('right', $printableWidthTwips, 'dot')],
                'indentation' => ['left' => $isLvl1 ? 0 : 420, 'firstLine' => 0],
                'spaceAfter' => $isLvl1 ? 80 : 40,
                'lineHeight' => 1.35,
            ];
            $fStyle = [
                'name' => 'Times New Roman',
                'size' => 12,
                'bold' => $isLvl1,
            ];
            $section->addText($item['label'] . "\t" . $item['page'], $fStyle, $pStyle);
        }
        $section->addPageBreak();

        // --- DAFTAR TABEL (HEADING 1) ---
        $section->addTitle("DAFTAR TABEL", 1);
        $tabelsDocx = [
            ['label' => 'Tabel 2.1 Matriks Komparasi Penelitian Terdahulu', 'page' => '14'],
            ['label' => 'Tabel 3.1 Operasional Variabel & Parameter Penelitian', 'page' => '24'],
            ['label' => 'Tabel 4.1 Statistik Karakteristik Dataset Penelitian', 'page' => '38'],
            ['label' => 'Tabel 4.2 Evaluasi Metrik Kinerja & Hasil Uji Model', 'page' => '45'],
        ];
        foreach ($tabelsDocx as $tbl) {
            $pStyle = [
                'tabs' => [new Tab('right', $printableWidthTwips, 'dot')],
                'spaceAfter' => 60,
                'lineHeight' => 1.35,
            ];
            $section->addText($tbl['label'] . "\t" . $tbl['page'], ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        }
        $section->addPageBreak();

        // --- DAFTAR GAMBAR (HEADING 1) ---
        $section->addTitle("DAFTAR GAMBAR", 1);
        $gambarsDocx = [
            ['label' => 'Gambar 2.1 Kerangka Pemikiran & Alur Logika Riset', 'page' => '18'],
            ['label' => 'Gambar 3.1 Diagram Alur (Flowchart) Prosedur Penelitian', 'page' => '28'],
            ['label' => 'Gambar 4.1 Grafik Evaluasi Metrik & Kurva Kinerja', 'page' => '49'],
        ];
        foreach ($gambarsDocx as $gbr) {
            $pStyle = [
                'tabs' => [new Tab('right', $printableWidthTwips, 'dot')],
                'spaceAfter' => 60,
                'lineHeight' => 1.35,
            ];
            $section->addText($gbr['label'] . "\t" . $gbr['page'], ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        }
        $section->addPageBreak();

        // --- DAFTAR LAMPIRAN (HEADING 1) ---
        $section->addTitle("DAFTAR LAMPIRAN", 1);
        $lampiransDocx = [
            ['label' => 'Lampiran 1 Surat Keputusan & Izin Penelitian Kampus', 'page' => '62'],
            ['label' => 'Lampiran 2 Sampel Data Mentah & Instrumen Penelitian', 'page' => '64'],
            ['label' => 'Lampiran 3 Tabel Lengkap Hasil Uji Statistik & Parameter', 'page' => '68'],
            ['label' => 'Lampiran 4 Dokumentasi Pelaksanaan & Log Bimbingan Skripsi', 'page' => '72'],
        ];
        foreach ($lampiransDocx as $lmp) {
            $pStyle = [
                'tabs' => [new Tab('right', $printableWidthTwips, 'dot')],
                'spaceAfter' => 60,
                'lineHeight' => 1.35,
            ];
            $section->addText($lmp['label'] . "\t" . $lmp['page'], ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        }
        $section->addPageBreak();

        $chapters = [
            'bab1' => 'PENDAHULUAN',
            'bab2' => 'TINJAUAN PUSTAKA',
            'bab3' => 'METODOLOGI PENELITIAN',
            'bab4' => 'HASIL DAN PEMBAHASAN',
            'bab5' => 'PENUTUP'
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
            if ($htmlContent = $project->{$k . '_content'}) {
                if ($k != 'bab1')
                    $section = $phpWord->addSection($sectionStyle);

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
                    $section->addText(trim($ref), ['name' => 'Times New Roman', 'size' => 12], ['alignment' => Jc::BOTH, 'indentation' => ['left' => 720, 'hanging' => 720], 'spaceAfter' => 80]);
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

        $logoPath = $this->resolveLogoTempPath($request, $meta);

        $phpWord = $this->createPhpWordDocument($project, $request, $logoPath, $meta);
        $filename = 'Naskah_' . Str::slug(substr($project->judul, 0, 28), '_') . '.docx';

        Settings::setTempDir('/tmp');
        $path = '/tmp/' . $filename;

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($path);
        $this->postProcessDocxOutline($path);

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
                // 1. Modifikasi word/document.xml untuk menyematkan outline level
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
                    $zip->addFromString('word/document.xml', $docXml);
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

        $defaultLogoFile = public_path('images/default_emblem.png');
        $logoHtml = '';
        if ($logoPath && file_exists($logoPath) && filesize($logoPath) > 50) {
            $mime = function_exists('mime_content_type') ? (@mime_content_type($logoPath) ?: 'image/png') : 'image/png';
            $b64 = base64_encode(file_get_contents($logoPath));
            $logoHtml = "<img src='data:{$mime};base64,{$b64}' style='width:120px; max-height:120px; height:auto; display:block; margin:0 auto;'>";
            @unlink($logoPath);
        } elseif (!empty($meta['logo_base64'])) {
            $b64 = trim($meta['logo_base64']);
            if (!str_starts_with($b64, 'data:image/')) {
                $b64 = 'data:image/png;base64,' . $b64;
            }
            $logoHtml = "<img src='{$b64}' style='width:120px; max-height:120px; height:auto; display:block; margin:0 auto;'>";
        } elseif (file_exists($defaultLogoFile)) {
            $b64 = base64_encode(file_get_contents($defaultLogoFile));
            $logoHtml = "<img src='data:image/png;base64,{$b64}' style='width:120px; max-height:120px; height:auto; display:block; margin:0 auto;'>";
        } else {
            // Elegant typographic seal (100% Vercel safe fallback)
            $logoHtml = "<div style='margin:14pt auto; width:90px; height:90px; border:2px solid #0f172a; border-radius:50%; text-align:center; line-height:86px; font-size:10.5pt; font-weight:bold; letter-spacing:1px; color:#0f172a; display:inline-block;'>UNIVERSITAS</div>";
        }

        $tocRowsHtml = '';
        foreach ($this->extractTocItemsForProject($project, $isProposal) as $item) {
            $pad = ($item['level'] ?? 1) === 1 ? '0' : '22px';
            $weight = ($item['level'] ?? 1) === 1 ? 'bold' : 'normal';
            $tocRowsHtml .= "<div style='display:table; width:100%; margin-bottom:5px; font-weight:{$weight};'>
                <span style='display:table-cell; padding-left:{$pad};'>" . e($item['label']) . "</span>
                <span style='display:table-cell; text-align:right; width:50px;'>" . e($item['page']) . "</span>
            </div>";
        }

        $html = "<html><head><style>
            @page { size: A4; margin: {$marginTop}cm {$marginRight}cm {$marginBottom}cm {$marginLeft}cm; }
            body{font-family:'Times New Roman', serif; margin:0; text-align:justify; line-height:1.5; font-size:12pt;}
            h1,h2{text-align:center; font-weight:bold; font-size:14pt;}
            h3,h4{font-weight:bold; font-size:12pt;}
            p{text-indent: 1.27cm; margin: 0 0 8px 0;}
            .cover p{text-indent:0; text-align:center;}
        </style></head><body>";

        // 1. HALAMAN JUDUL (COVER RESMI PROPORSIONAL A4 INDONESIA)
        $html .= "<div class='cover' style='width:100%; height:22.2cm; max-height:22.5cm; overflow:hidden; page-break-after:always; page-break-inside:avoid;'>
            <table style='width:100%; height:22.2cm; border-collapse:collapse; text-align:center; margin:0 auto; padding:0; border:none;'>
                <tr style='height:36%;'>
                    <td style='vertical-align:top; text-align:center; border:none; padding:0;'>
                        <div style='font-size:14pt; font-weight:bold; letter-spacing:1px; line-height:1.2; text-transform:uppercase;'>{$jenisDokumen}</div>
                        <div style='font-size:13pt; font-weight:bold; margin-top:16pt; margin-bottom:12pt; line-height:1.35; text-transform:uppercase;'>" . e($project->judul) . "</div>
                        <div style='font-size:11pt; line-height:1.35; max-width:440pt; margin:0 auto;'>Diajukan sebagai Salah Satu Syarat untuk Memperoleh Gelar Sarjana pada Program Studi " . e($project->prodi) . "</div>
                    </td>
                </tr>
                <tr style='height:28%;'>
                    <td style='vertical-align:middle; text-align:center; border:none; padding:0;'>
                        {$logoHtml}
                    </td>
                </tr>
                <tr style='height:18%;'>
                    <td style='vertical-align:middle; text-align:center; border:none; padding:0;'>
                        <div style='font-size:11.5pt; line-height:1.45;'>
                            <div>OLEH:</div>
                            <div style='margin-top:3pt;'><u><strong>" . strtoupper(e($nama)) . "</strong></u></div>
                            " . ($npm ? "<div style='margin-top:2pt;'>NIM: " . e($npm) . "</div>" : "") . "
                        </div>
                    </td>
                </tr>
                <tr style='height:18%;'>
                    <td style='vertical-align:bottom; text-align:center; border:none; padding:0 0 4pt 0;'>
                        <div style='font-weight:bold; font-size:12.5pt; line-height:1.35;'>
                            <div>PROGRAM STUDI " . strtoupper(e($project->prodi)) . "</div>
                            " . ($fakultas ? "<div>" . strtoupper(e($fakultas)) . "</div>" : "") . "
                            <div>" . strtoupper(e($kampus)) . "</div>
                            <div>" . date('Y') . "</div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        <div style='page-break-after:always;'></div>";

        // 2. LEMBAR PENGESAHAN
        $html .= "<div style='text-align:center;'>
            <h2>LEMBAR PENGESAHAN</h2>
            <div style='font-size:12pt; margin:16pt 0 10pt; font-weight:bold; line-height:1.35; text-transform:uppercase;'>" . e($project->judul) . "</div>
            <div style='margin-bottom:16pt; font-size:11pt;'>Disusun Oleh: <u>" . strtoupper(e($nama)) . "</u> (" . e($npm) . ")</div>
            <p style='text-indent:1.27cm; line-height:1.5; font-size:11pt;'>Telah diuji dan dipertahankan di hadapan Tim Dewan Penguji Ujian " . e($jenisDokumen) . " pada Program Studi " . e($project->prodi) . ", " . ($fakultas ? e($fakultas) . ', ' : '') . e($kampus) . ", dan dinyatakan telah memenuhi seluruh persyaratan akademik kelulusan.</p>
            <div style='margin-top:28pt; width:100%;'>
                <div style='float:left; width:48%; text-align:center; font-size:11pt;'>
                    <div>Dosen Pembimbing I</div>
                    <div style='height:50pt;'></div>
                    <div>( __________________________ )</div>
                    <div style='font-size:9.5pt; color:#666;'>NIP/NIDN.</div>
                </div>
                <div style='float:right; width:48%; text-align:center; font-size:11pt;'>
                    <div>Dosen Pembimbing II</div>
                    <div style='height:50pt;'></div>
                    <div>( __________________________ )</div>
                    <div style='font-size:9.5pt; color:#666;'>NIP/NIDN.</div>
                </div>
                <div style='clear:both;'></div>
            </div>
            <div style='margin-top:24pt; text-align:center; font-size:11pt;'>
                <div>Mengetahui,</div>
                <div>Ketua Program Studi " . e($project->prodi) . "</div>
                <div style='height:48pt;'></div>
                <div>( __________________________ )</div>
                <div style='font-size:9.5pt; color:#666;'>NIP/NIDN.</div>
            </div>
        </div>
        <div style='page-break-after:always;'></div>";

        // 3. SURAT PERNYATAAN KEASLIAN
        $html .= "<div style='text-align:center;'>
            <h2>SURAT PERNYATAAN KEASLIAN</h2>
        </div>
        <div style='margin-top:16pt; font-size:11pt; line-height:1.5;'>
            <p style='text-indent:0;'>Saya yang bertanda tangan di bawah ini:</p>
            <table style='width:100%; margin:8pt 0 12pt 16pt; font-size:11pt; border-collapse:collapse;'>
                <tr><td style='width:120pt; border:none; padding:2pt 0;'>Nama Mahasiswa</td><td style='border:none; padding:2pt 0;'>: <strong>" . e($nama) . "</strong></td></tr>
                <tr><td style='border:none; padding:2pt 0;'>NIM / NPM</td><td style='border:none; padding:2pt 0;'>: " . e($npm) . "</td></tr>
                <tr><td style='border:none; padding:2pt 0;'>Program Studi</td><td style='border:none; padding:2pt 0;'>: " . e($project->prodi) . "</td></tr>
                <tr><td style='border:none; padding:2pt 0;'>Fakultas</td><td style='border:none; padding:2pt 0;'>: " . e($fakultas) . "</td></tr>
            </table>
            <p>Menyatakan dengan sesungguhnya bahwa " . strtolower(e($jenisDokumen)) . " berjudul <strong>\"" . e($project->judul) . "\"</strong> ini adalah murni hasil karya penelitian, pemikiran, dan penulisan mandiri saya sendiri. Karya ini belum pernah diajukan untuk memperoleh gelar akademik di perguruan tinggi mana pun, dan bebas dari unsur plagiarisme atau fabrikasi data.</p>
            <p>Seluruh kutipan, data, dan pemikiran yang bersumber dari pihak lain telah dicantumkan dalam daftar pustaka sesuai dengan etika ilmiah. Apabila di kemudian hari terbukti terdapat plagiarisme, saya bersedia menerima sanksi akademik sesuai aturan yang berlaku.</p>
            <div style='margin-top:24pt; float:right; width:190pt; text-align:center; font-size:11pt;'>
                <div>" . ($kampus ? e($kampus) . ", " : "") . date('d F Y') . "</div>
                <div style='margin-top:3pt;'>Yang membuat pernyataan,</div>
                <div style='margin-top:8pt; font-size:8.5pt; color:#666;'>[ Materai Rp 10.000 ]</div>
                <div style='height:36pt;'></div>
                <div><u><strong>" . strtoupper(e($nama)) . "</strong></u></div>
                <div>NIM. " . e($npm) . "</div>
            </div>
            <div style='clear:both;'></div>
        </div>
        <div style='page-break-after:always;'></div>";

        // 4. ABSTRAK (BAHASA INDONESIA) & ABSTRACT (ENGLISH)
        $html .= "<div style='text-align:center;'>
            <h2>ABSTRAK</h2>
            <div style='font-size:11.5pt; font-weight:bold; margin-top:8pt; line-height:1.35; text-transform:uppercase;'>" . e($project->judul) . "</div>
            <div style='font-size:10.5pt; margin-top:4pt;'>Oleh: " . e($nama) . " (NIM: " . e($npm) . ")</div>
            <div style='font-size:10pt; color:#444; margin-bottom:12pt;'>Program Studi " . e($project->prodi) . ", " . e($kampus) . "</div>
        </div>
        <div style='font-size:11pt; line-height:1.45;'>
            <p>Penelitian ini bertujuan untuk menginvestigasi dan menyelesaikan permasalahan pada topik \"" . e($project->judul) . "\". Metode penelitian yang diterapkan adalah " . e($project->metode_penelitian) . " dengan prosedur sistematis dan terukur. Hasil penelitian menunjukkan bahwa pendekatan yang dirumuskan mampu memberikan solusi teruji, tolok ukur kinerja yang solid, serta kontribusi empiris bagi pengembangan ilmu pengetahuan dan praktik pada bidang " . e($project->prodi) . ".</p>
            <p style='text-indent:0; margin-top:8pt;'><strong>Kata Kunci:</strong> " . e($project->prodi) . ", " . e($project->metode_penelitian) . ", Evaluasi Kinerja, Skripsi S1.</p>
        </div>
        <div style='page-break-after:always;'></div>

        <div style='text-align:center;'>
            <h2>ABSTRACT</h2>
            <div style='font-size:11.5pt; font-weight:bold; margin-top:8pt; font-style:italic; line-height:1.35; text-transform:uppercase;'>" . e($project->judul) . "</div>
            <div style='font-size:10.5pt; margin-top:4pt;'>By: " . e($nama) . " (Student ID: " . e($npm) . ")</div>
            <div style='font-size:10pt; color:#444; margin-bottom:12pt;'>Department of " . e($project->prodi) . ", " . e($kampus) . "</div>
        </div>
        <div style='font-size:11pt; line-height:1.45; font-style:italic;'>
            <p>This study aims to investigate and address the empirical challenges in \"" . e($project->judul) . "\". Utilizing a scientific methodology in " . e($project->metode_penelitian) . ", this research systematically collects and analyzes relevant data. The results demonstrate that the proposed method effectively accomplishes the research objectives, yields verifiable benchmark performance, and delivers valuable contributions to both academic literature and practical applications in " . e($project->prodi) . ".</p>
            <p style='text-indent:0; margin-top:8pt;'><strong>Keywords:</strong> " . e($project->prodi) . ", Empirical Research, Performance Evaluation, Undergraduate Thesis.</p>
        </div>
        <div style='page-break-after:always;'></div>";

        // 5. KATA PENGANTAR
        $html .= "<div style='page-break-before:always; page-break-after:always; page-break-inside:avoid;'>
            <h2>KATA PENGANTAR</h2>
            <p>Puji syukur penulis panjatkan ke hadirat Tuhan Yang Maha Esa atas segala rahmat dan karunia-Nya sehingga penyusunan {$jenisDokumen} berjudul \"" . e($project->judul) . "\" dapat diselesaikan pada Program Studi " . e($project->prodi) . ($kampus ? ", " . e($kampus) : "") . ".</p>
            <p>Penyusunan naskah ini diajukan sebagai salah satu syarat akademik guna menyelesaikan studi Program Sarjana (S1). Penulis menyampaikan terima kasih yang sebesar-besarnya kepada pimpinan universitas, dosen pembimbing, dosen penguji, serta keluarga tercinta atas bimbingan dan doa yang tiada henti.</p>
            <div style='margin-top:24pt; float:right; width:190pt; text-align:center; font-size:11pt;'>
                <div>" . ($kampus ? e($kampus) . ", " : "") . date('F Y') . "</div>
                <div style='height:36pt;'></div>
                <div><u><strong>" . strtoupper(e($nama)) . "</strong></u></div>
                <div>Penulis</div>
            </div>
            <div style='clear:both;'></div>
        </div>";

        // 6. DAFTAR ISI
        $html .= "<div style='page-break-before:always; page-break-after:always; page-break-inside:avoid;'>
            <h2>DAFTAR ISI</h2>
            <div style='margin-top:16px;'>{$tocRowsHtml}</div>
        </div>";

        // 7. DAFTAR TABEL
        $html .= "<div style='page-break-before:always; page-break-after:always; page-break-inside:avoid;'>
            <h2>DAFTAR TABEL</h2>
            <div style='margin-top:16px;'>
                <div style='display:table; width:100%; font-size:12pt; line-height:1.45; margin-bottom:8px;'>
                    <span style='display:table-cell;'>Tabel 2.1 Matriks Komparasi Penelitian Terdahulu</span>
                    <span style='display:table-cell; text-align:right; width:50px;'>14</span>
                </div>
                <div style='display:table; width:100%; font-size:12pt; line-height:1.45; margin-bottom:8px;'>
                    <span style='display:table-cell;'>Tabel 3.1 Operasional Variabel &amp; Parameter Penelitian</span>
                    <span style='display:table-cell; text-align:right; width:50px;'>24</span>
                </div>
                <div style='display:table; width:100%; font-size:12pt; line-height:1.45; margin-bottom:8px;'>
                    <span style='display:table-cell;'>Tabel 4.1 Statistik Karakteristik Dataset Penelitian</span>
                    <span style='display:table-cell; text-align:right; width:50px;'>38</span>
                </div>
                <div style='display:table; width:100%; font-size:12pt; line-height:1.45; margin-bottom:8px;'>
                    <span style='display:table-cell;'>Tabel 4.2 Evaluasi Metrik Kinerja &amp; Hasil Uji Model</span>
                    <span style='display:table-cell; text-align:right; width:50px;'>45</span>
                </div>
            </div>
        </div>";

        // 8. DAFTAR GAMBAR
        $html .= "<div style='page-break-before:always; page-break-after:always; page-break-inside:avoid;'>
            <h2>DAFTAR GAMBAR</h2>
            <div style='margin-top:16px;'>
                <div style='display:table; width:100%; font-size:12pt; line-height:1.45; margin-bottom:8px;'>
                    <span style='display:table-cell;'>Gambar 2.1 Kerangka Pemikiran &amp; Alur Logika Riset</span>
                    <span style='display:table-cell; text-align:right; width:50px;'>18</span>
                </div>
                <div style='display:table; width:100%; font-size:12pt; line-height:1.45; margin-bottom:8px;'>
                    <span style='display:table-cell;'>Gambar 3.1 Diagram Alur (Flowchart) Prosedur Penelitian</span>
                    <span style='display:table-cell; text-align:right; width:50px;'>28</span>
                </div>
                <div style='display:table; width:100%; font-size:12pt; line-height:1.45; margin-bottom:8px;'>
                    <span style='display:table-cell;'>Gambar 4.1 Grafik Evaluasi Metrik &amp; Kurva Kinerja</span>
                    <span style='display:table-cell; text-align:right; width:50px;'>49</span>
                </div>
            </div>
        </div>";

        // 9. DAFTAR LAMPIRAN
        $html .= "<div style='page-break-before:always; page-break-after:always; page-break-inside:avoid;'>
            <h2>DAFTAR LAMPIRAN</h2>
            <div style='margin-top:16px;'>
                <div style='display:table; width:100%; font-size:12pt; line-height:1.45; margin-bottom:8px;'>
                    <span style='display:table-cell;'>Lampiran 1 Surat Keputusan &amp; Izin Penelitian Kampus</span>
                    <span style='display:table-cell; text-align:right; width:50px;'>62</span>
                </div>
                <div style='display:table; width:100%; font-size:12pt; line-height:1.45; margin-bottom:8px;'>
                    <span style='display:table-cell;'>Lampiran 2 Sampel Data Mentah &amp; Instrumen Penelitian</span>
                    <span style='display:table-cell; text-align:right; width:50px;'>64</span>
                </div>
                <div style='display:table; width:100%; font-size:12pt; line-height:1.45; margin-bottom:8px;'>
                    <span style='display:table-cell;'>Lampiran 3 Tabel Lengkap Hasil Uji Statistik &amp; Parameter</span>
                    <span style='display:table-cell; text-align:right; width:50px;'>68</span>
                </div>
                <div style='display:table; width:100%; font-size:12pt; line-height:1.45; margin-bottom:8px;'>
                    <span style='display:table-cell;'>Lampiran 4 Dokumentasi Pelaksanaan &amp; Log Bimbingan Skripsi</span>
                    <span style='display:table-cell; text-align:right; width:50px;'>72</span>
                </div>
            </div>
        </div>";

        $chapters = [
            'bab1' => 'PENDAHULUAN',
            'bab2' => 'TINJAUAN PUSTAKA',
            'bab3' => 'METODOLOGI PENELITIAN',
            'bab4' => 'HASIL DAN PEMBAHASAN',
            'bab5' => 'PENUTUP'
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
                $html .= "<h2 style='text-align:center; font-weight:bold; font-size:14pt; margin-top:0; margin-bottom:16pt; page-break-after:avoid;'>BAB " . $nums[$k] . "<br>" . strtoupper($v) . "</h2>";
                $html .= $this->formatAcademicContentForPdf($c);
                $html .= "<div style='page-break-after:always;'></div>";
            }
        }

        $html .= "<h2 style='text-align:center; font-weight:bold; font-size:14pt; margin-top:0; margin-bottom:16pt; page-break-after:avoid;'>DAFTAR PUSTAKA</h2>";
        $dpLines = explode("\n", str_replace(["\r\n", "\r"], "\n", (string) $project->daftar_pustaka));
        foreach ($dpLines as $dpl) {
            $trimDpl = trim($dpl);
            if ($trimDpl !== '') {
                $html .= "<p style='text-indent:-1.27cm; padding-left:1.27cm; margin:0 0 8pt 0; text-align:justify; line-height:1.5; font-size:12pt;'>" . e($trimDpl) . "</p>";
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

        try {
            $canvas = $dompdf->getCanvas();
            $canvas->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) {
                if ($pageNumber > 1) {
                    $font = $fontMetrics->getFont('Times-Roman', 'normal');
                    $canvas->text(297, 804, (string) $pageNumber, $font, 10, [0.15, 0.15, 0.15]);
                }
            });
        } catch (\Throwable $e) {
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
        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    private function cleanDaftarPustaka(string $raw): string
    {
        $text = strip_tags($raw);
        $text = str_replace(['*', '#', '_', '`'], '', $text);
        $lines = explode("\n", $text);
        $cleaned = [];
        foreach ($lines as $line) {
            $l = trim($line);
            if (strlen($l) < 12)
                continue;
            if (preg_match('/^(berikut\s+adalah|daftar\s+pustaka|referensi\s*:|catatan\s*:|berikut\s+daftar)/i', $l)) {
                continue;
            }
            $l = preg_replace('/^[\d\-\•]+\.?\s*/', '', $l);
            $cleaned[] = trim($l);
        }
        return implode("\n", $cleaned);
    }

    public function landingPage()
    {
        $publicProjects = collect();
        try {
            $publicProjects = Project::whereNotNull('bab1_content')
                ->where('bab1_content', '!=', '')
                ->whereRaw('CHAR_LENGTH(TRIM(judul)) >= 20')
                ->orderBy('updated_at', 'desc')
                ->take(6)
                ->get();
        } catch (Throwable $e) {
            $publicProjects = collect();
        }
        return view('landing', compact('publicProjects'));
    }

    public function downloadPublicProject($id, string $format, Request $request)
    {
        $project = Project::findOrFail($id);
        $meta = $this->getProjectMeta($project);
        $logoPath = $this->resolveLogoTempPath($request, $meta);

        $hasFull = !empty($project->bab4_content) || !empty($project->bab5_content);
        $request->merge([
            'nama' => $meta['nama'] ?? 'Mahasiswa Peneliti',
            'npm' => $meta['nim'] ?? '-',
            'kampus' => $meta['kampus'] ?? 'Universitas di Indonesia',
            'fakultas' => $meta['fakultas'] ?? '',
            'jenis_dokumen' => $hasFull ? 'SKRIPSI' : 'PROPOSAL SKRIPSI',
        ]);

        if ($format === 'pdf') {
            $jenisDokumen = $hasFull ? 'SKRIPSI' : 'PROPOSAL SKRIPSI';
            $nama = $meta['nama'] ?? 'Mahasiswa Peneliti';
            $npm = $meta['nim'] ?? '';
            $kampus = $meta['kampus'] ?? 'Universitas';
            $fakultas = $meta['fakultas'] ?? '';

            $hasGd = extension_loaded('gd');
            $logoHtml = '';
            if ($hasGd && $logoPath && file_exists($logoPath)) {
                $mime = mime_content_type($logoPath) ?: 'image/png';
                $b64 = base64_encode(file_get_contents($logoPath));
                $logoHtml = "<div style='margin: 20px 0; text-align:center;'><img src='data:{$mime};base64,{$b64}' style='width:100px; height:auto;'></div>";
                @unlink($logoPath);
            } else {
                $logoHtml = "<div style='margin: 18px auto; width:85px; height:85px; border:2px solid #0f172a; border-radius:50%; text-align:center; line-height:80px; font-weight:bold; font-size:10pt; letter-spacing:1px; color:#0f172a;'>UNIVERSITAS</div>";
            }

            $html = "<html><head><style>
                @page { size: A4; margin: 4cm 3cm 3cm 4cm; }
                body{font-family:'Times New Roman', serif; margin:0; text-align:justify; line-height:1.5; font-size:12pt;}
                h1,h2{text-align:center; font-weight:bold; font-size:14pt;}
                h3,h4{font-weight:bold; font-size:12pt;}
                p{text-indent: 1.27cm; margin: 0 0 8px 0;}
                .cover p{text-indent:0; text-align:center;}
            </style></head><body>
            <div class='cover' style='text-align:center; padding-top:25px;'>
                <h1>{$jenisDokumen}</h1>
                <h2 style='margin-top:24px;'>" . strtoupper(e($project->judul)) . "</h2>
                {$logoHtml}
                <p style='margin-top:36px;'><strong>OLEH:</strong><br><u><strong>" . strtoupper(e($nama)) . "</strong></u><br>" . e($npm) . "</p>
                <div style='margin-top:64px; font-weight:bold; font-size:13pt;'>
                    PROGRAM STUDI " . strtoupper(e($project->prodi)) . "<br>
                    " . ($fakultas ? strtoupper(e($fakultas)) . "<br>" : "") . "
                    " . strtoupper(e($kampus)) . "<br>" . date('Y') . "
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
            $nums = ['bab1' => 'I', 'bab2' => 'II', 'bab3' => 'III', 'bab4' => 'IV', 'bab5' => 'V'];
            foreach ($chapters as $k => $v) {
                if ($c = $project->{$k . '_content'}) {
                    $html .= "<h2 style='text-align:center; font-weight:bold; font-size:14pt; margin-top:0; margin-bottom:16pt; page-break-after:avoid;'>BAB " . $nums[$k] . "<br>" . strtoupper($v) . "</h2>";
                    $html .= $this->formatAcademicContentForPdf($c);
                    $html .= "<div style='page-break-after:always;'></div>";
                }
            }
            if (!empty($project->daftar_pustaka)) {
                $html .= "<h2 style='text-align:center; font-weight:bold; font-size:14pt; margin-top:0; margin-bottom:16pt; page-break-after:avoid;'>DAFTAR PUSTAKA</h2>";
                $dpLines = explode("
", str_replace(["
",
                        "
"
                    ], "
", (string) $project->daftar_pustaka));
                foreach ($dpLines as $dpl) {
                    $trimDpl = trim($dpl);
                    if ($trimDpl !== '') {
                        $html .= "<p style='text-indent:-1.27cm; padding-left:1.27cm; margin:0 0 8pt 0; text-align:justify; line-height:1.5; font-size:12pt;'>" . e($trimDpl) . "</p>";
                    }
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
                Log::warning('Dompdf downloadProjectDocument render fallback: ' . $e->getMessage());
                $fallbackHtml = preg_replace('/<img[^>]*>/i', '', $html);
                $dompdf = new Dompdf();
                $dompdf->set_option('isHtml5ParserEnabled', true);
                $dompdf->set_option('isRemoteEnabled', false);
                $dompdf->loadHtml($fallbackHtml);
                $dompdf->setPaper('A4');
                $dompdf->render();
            }

            try {
                $canvas = $dompdf->getCanvas();
                $font = $dompdf->getFontMetrics()->getFont('Times-Roman', 'normal');
                $canvas->page_text(297, 804, "{PAGE_NUM}", $font, 10, [0.15, 0.15, 0.15]);
            } catch (\Throwable $e) {
            }

            $inline = $request->boolean('inline');
            return $dompdf->stream('Dokumen_' . Str::slug(substr($project->judul, 0, 28), '_') . '.pdf', ['Attachment' => !$inline]);
        }

        if (ob_get_length())
            ob_end_clean();
        $phpWord = $this->createPhpWordDocument($project, $request, $logoPath, $meta);
        $filename = 'Dokumen_' . Str::slug(substr($project->judul, 0, 28), '_') . '.docx';
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
        $meta = $this->getProjectMeta($project);
        $sitasi = $meta['sitasi'] ?? [];
        $gayaSitasi = $sitasi['gaya'] ?? 'APA 7th';
        $tahunMin = $sitasi['tahun_min'] ?? '2021';
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
            $project->daftar_pustaka = $this->cleanDaftarPustaka($daftarPustaka);
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
            'image' => 'nullable|string',
            'model' => 'nullable|string'
        ]);

        try {
            $question = $request->question;
            $image = $request->image;
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

        if ($request->hasFile('file_skripsi')) {
            $file = $request->file('file_skripsi');
            $docText = $this->extractTextFromUploadedFile($file);
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
                $docText = strip_tags(
                    "Judul: {$proj->judul}\nProdi: {$proj->prodi}\nMetode: {$proj->metode_penelitian}\n" .
                    "BAB 1:\n{$proj->bab1_content}\nBAB 2:\n{$proj->bab2_content}\nBAB 3:\n{$proj->bab3_content}\nBAB 4:\n{$proj->bab4_content}\nBAB 5:\n{$proj->bab5_content}"
                );
            }
        }

        if (empty($docTitle) && empty($docText)) {
            return response()->json(['status' => 'error', 'message' => 'Silakan unggah file skripsi (.pdf / .docx) atau pilih proyek skripsi Anda.'], 422);
        }

        $coverMeta = [
            'nama' => $request->input('nama') ?: ($projMeta['nama'] ?? $defaultId['nama'] ?? (Auth::user()->name ?? 'Mahasiswa')),
            'nim' => $request->input('nim') ?: ($projMeta['nim'] ?? $defaultId['nim'] ?? ''),
            'kampus' => $request->input('kampus') ?: ($projMeta['kampus'] ?? $defaultId['kampus'] ?? 'Universitas'),
            'fakultas' => $projMeta['fakultas'] ?? $defaultId['fakultas'] ?? '',
            'prodi' => $projProdi,
            'logo_base64' => $projMeta['logo_base64'] ?? $defaultId['logo_base64'] ?? '',
            'pembimbing_1' => $request->input('pembimbing_1') ?: 'Dosen Pembimbing 1',
            'pembimbing_2' => $request->input('pembimbing_2') ?: 'Dosen Pembimbing 2',
            'penguji_1' => $request->input('penguji_1') ?: 'Dosen Penguji 1',
            'penguji_2' => $request->input('penguji_2') ?: 'Dosen Penguji 2',
        ];

        $prompt = "PERAN: Anda adalah Konsultan Presentasi Sidang Skripsi Akademik Indonesia.
TUGAS: Rangkum dokumen skripsi/proposal berikut menjadi 9 SLIDE PRESENTASI SIDANG dalam format JSON Array murni.

JUDUL / TOPIK: {$docTitle}
ISI DOKUMEN SKRIPSI MAHASISWA:
\"\"\"
" . Str::limit($docText ?: $docTitle, 12000) . "
\"\"\"

ATURAN WAJIB FORMAT & KONTEN:
1. DILARANG KERAS menggunakan simbol markdown bintang ganda (** atau *) di dalam title, subtitle, maupun bullets. Semua teks harus teks biasa yang bersih, rapi, dan mudah dibaca.
2. Slide 1: Sampul Sidang (Cover). Title adalah judul lengkap skripsi, subtitle adalah Program Studi & Metode Penelitian, bullets berisi poin ringkas fokus penelitian.
3. Slide 2 s/d 9: Isi presentasi akademik berurutan:
   - Slide 2: Latar Belakang Masalah
   - Slide 3: Identifikasi & Batasan Masalah
   - Slide 4: Rumusan Masalah & Tujuan Penelitian
   - Slide 5: Tinjauan Pustaka & Kerangka Teori
   - Slide 6: Metodologi Penelitian
   - Slide 7: Hasil Penelitian & Temuan Data
   - Slide 8: Pembahasan & Analisis Kritis
   - Slide 9: Kesimpulan & Saran

Kembalikan HANYA JSON Array valid (tanpa teks pengantar, tanpa markdown ```json) dengan struktur 9 objek slide:
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

            // Jika JSON parsing gagal, buat 9 slide terstruktur akademik yang nyata (DILARANG dump raw JSON)
            if (!is_array($slidesArray) || count($slidesArray) < 3) {
                $safeTitle = $docTitle ?: 'Presentasi Sidang Skripsi';
                $slidesArray = [
                    [
                        'slide_number' => 1,
                        'section' => 'Sampul Sidang',
                        'title' => $safeTitle,
                        'subtitle' => 'Program Studi ' . $projProdi . ' · Sidang Skripsi',
                        'bullets' => ['Presentasi Tugas Akhir / Skripsi', 'Fokus Kajian Ilmiah & Evaluasi Kinerja'],
                        'speaker_notes' => 'Selamat pagi Bapak dan Ibu Dosen Penguji serta Pembimbing. Terima kasih atas kesempatan untuk memaparkan skripsi saya berjudul ' . $safeTitle
                    ],
                    [
                        'slide_number' => 2,
                        'section' => 'Latar Belakang Masalah',
                        'title' => 'Latar Belakang & Urgensi Penelitian',
                        'subtitle' => 'Fenomena Lapangan dan Kesenjangan Riset',
                        'bullets' => [
                            'Urgensi kebutuhan otomatisasi dan efisiensi pada objek penelitian',
                            'Tantangan akurasi dan skalabilitas yang dihadapi saat ini',
                            'Peran metode komputasi dalam memberikan solusi terukur',
                            'Research gap yang menjadi fokus kebaruan penelitian ini'
                        ],
                        'speaker_notes' => 'Penelitian ini dilatarbelakangi oleh fenomena riil dan tantangan utama yang ditemukan pada objek kajian.'
                    ],
                    [
                        'slide_number' => 3,
                        'section' => 'Identifikasi & Batasan Masalah',
                        'title' => 'Ruang Lingkup & Batasan Riset',
                        'subtitle' => 'Fokus Kajian Agar Terarah dan Terukur',
                        'bullets' => [
                            'Batasan dataset, parameter, dan objek yang diuji',
                            'Ruang lingkup variabel bebas dan variabel terikat',
                            'Asumsi lingkungan implementasi dan evaluasi'
                        ],
                        'speaker_notes' => 'Agar penelitian fokus dan terukur, ruang lingkup dibatasi pada parameter dan objek yang relevan.'
                    ],
                    [
                        'slide_number' => 4,
                        'section' => 'Rumusan Masalah & Tujuan',
                        'title' => 'Rumusan Masalah & Tujuan Penelitian',
                        'subtitle' => 'Arah Solusi Akademik yang Dituju',
                        'bullets' => [
                            'Rumusan Masalah: Bagaimana implementasi dan tingkat kinerja metode yang diterapkan?',
                            'Tujuan: Menganalisis dan mengevaluasi efektivitas solusi yang dibangun',
                            'Kontribusi: Memberikan tolok ukur empiris bagi pengembangan selanjutnya'
                        ],
                        'speaker_notes' => 'Tujuan penelitian ini menjawab secara langsung rumusan masalah yang telah diidentifikasi.'
                    ],
                    [
                        'slide_number' => 5,
                        'section' => 'Tinjauan Pustaka',
                        'title' => 'Landasan Teori & Penelitian Terdahulu',
                        'subtitle' => 'Kerangka Teoretis Pendukung',
                        'bullets' => [
                            'Grand theory dan konsep dasar yang melandasi penelitian',
                            'Kajian 4-5 artikel jurnal bereputasi terkait',
                            'Posisi kebaruan (novelty) terhadap studi-studi terdahulu'
                        ],
                        'speaker_notes' => 'Pustaka acuan mengacu pada publikasi ilmiah bereputasi terkini periode 2020 hingga 2026.'
                    ],
                    [
                        'slide_number' => 6,
                        'section' => 'Metodologi Penelitian',
                        'title' => 'Tahapan & Metode Penelitian',
                        'subtitle' => 'Alur Kerja dan Prosedur Evaluasi',
                        'bullets' => [
                            'Desain penelitian dan prosedur pengumpulan data',
                            'Tahapan pra-pemrosesan dan rekayasa fitur',
                            'Metrik evaluasi kinerja (akurasi, presisi, recall, dll.)'
                        ],
                        'speaker_notes' => 'Metodologi disusun sistematis mulai dari penyiapan data hingga tahapan validasi hasil.'
                    ],
                    [
                        'slide_number' => 7,
                        'section' => 'Hasil Penelitian',
                        'title' => 'Hasil Pengujian & Analisis Data',
                        'subtitle' => 'Temuan Empiris dan Capaian Kinerja',
                        'bullets' => [
                            'Hasil pengujian algoritma/sistem pada data uji',
                            'Perbandingan performa terhadap baseline skenario',
                            'Tingkat akurasi dan stabilitas model'
                        ],
                        'speaker_notes' => 'Berikut adalah hasil pengujian empiris yang menunjukkan performa sistem.'
                    ],
                    [
                        'slide_number' => 8,
                        'section' => 'Pembahasan',
                        'title' => 'Pembahasan & Analisis Kritis',
                        'subtitle' => 'Interpretasi Temuan Riset',
                        'bullets' => [
                            'Kesesuaian hasil pengujian terhadap hipotesis kerja',
                            'Faktor-faktor penentu keberhasilan performa',
                            'Implikasi teoretis dan praktis dari temuan'
                        ],
                        'speaker_notes' => 'Pembahasan ini membuktikan efektivitas metode yang diajukan serta implikasinya.'
                    ],
                    [
                        'slide_number' => 9,
                        'section' => 'Kesimpulan & Saran',
                        'title' => 'Kesimpulan & Saran Pengembangan',
                        'subtitle' => 'Penutup Sidang Skripsi',
                        'bullets' => [
                            'Kesimpulan utama: Metode berhasil menyelesaikan rumusan masalah',
                            'Saran 1: Pengayaan dataset dan skenario pengujian lebih luas',
                            'Saran 2: Integrasi dengan arsitektur teknologi lanjutan'
                        ],
                        'speaker_notes' => 'Sebagai penutup, seluruh tujuan penelitian telah tercapai dan saran disajikan untuk riset masa depan.'
                    ]
                ];
            }

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

            return response()->json([
                'status' => 'success',
                'title' => $docTitle,
                'slides_data' => $slidesArray,
                'cover_meta' => $coverMeta,
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
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

                $personaDesc = match ($modeDosen) {
                    'santai' => 'Gaya Dosen Penguji: SANTAI & RAMAH. Pertanyaan bernada hangat, membimbing, apresiatif terhadap proses mahasiswa, namun tetap mengonfirmasi pemahaman konseptual dasar.',
                    'kritis' => 'Gaya Dosen Penguji: KRITIS & METODOLOGIS. Menyelidiki kelemahan metodologi, validitas instrumen, representasi sampling, landasan teori, dan penalaran logis.',
                    'galak' => 'Gaya Dosen Penguji: GALAK / KILLER / TAJAM. Sangat skeptis, menekan titik lemah penelitian, mencecar keterbatasan data, menolak jawaban klise, dan menuntut pembuktian empiris yang presisi.',
                    default => 'Gaya Dosen Penguji: TEGAS & STANDAR AKADEMIK. Formal, terstruktur, lugas, menguji konsistensi antara latar belakang, rumusan masalah, analisis, dan simpulan sesuai pedoman skripsi universitas.'
                };

                $prompt = "PERAN: Anda adalah Ketua Dewan Dosen Penguji Sidang Skripsi S1 di Indonesia.
{$personaDesc}

Berdasarkan naskah skripsi mahasiswa berikut:
JUDUL: {$docTitle}
METODE: {$metode}
RINGKASAN NASKAH:
\"\"\"
" . Str::limit($docText ?: $docTitle, 10000) . "
\"\"\"

TUGAS: Susun TEPAT {$jumlahSoal} PERTANYAAN UJIAN SIDANG SKRIPSI sesuai karakter dosen penguji di atas, mencakup Bab I hingga Bab V.

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
                    'mode_dosen' => $modeDosen,
                    'questions' => $questions,
                ]);
            }

            $qaList = $request->input('qa_list', []);
            $docTitle = $request->input('judul', 'Skripsi Mahasiswa');
            $modeDosen = $request->input('mode_dosen', 'tegas');

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

            $prompt = "PERAN: Anda adalah Ketua Dewan Dosen Penguji Sidang Skripsi S1.
{$personaRubric}

Evaluasi seluruh jawaban mahasiswa pada simulasi sidang skripsi untuk judul: \"{$docTitle}\".

DAFTAR PERTANYAAN DAN JAWABAN MAHASISWA:
\"\"\"
{$qaFormatted}
\"\"\"

PANDUAN PENILAIAN OBJEKTIF & SANGAT KETAT:
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
}
