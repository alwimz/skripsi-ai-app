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
                if (empty($identity['nama']) && !empty($m['nama'])) $identity['nama'] = trim($m['nama']);
                if (empty($identity['nim']) && !empty($m['nim'])) $identity['nim'] = trim($m['nim']);
                if (empty($identity['kampus']) && !empty($m['kampus'])) $identity['kampus'] = trim($m['kampus']);
                if (empty($identity['fakultas']) && !empty($m['fakultas'])) $identity['fakultas'] = trim($m['fakultas']);
                if (empty($identity['prodi']) && !empty($proj->prodi)) $identity['prodi'] = trim($proj->prodi);
                if (empty($identity['logo_base64']) && !empty($m['logo_base64'])) $identity['logo_base64'] = $m['logo_base64'];
            }
        } catch (Throwable $e) {}

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
            if (empty($query)) return [];

            $res = Http::timeout(8)->get('https://api.openalex.org/works', [
                'search' => $query,
                'filter' => 'from_publication_date:2020-01-01',
                'per-page' => 10,
            ]);

            if ($res->successful()) {
                foreach ($res->json()['results'] ?? [] as $w) {
                    $title = trim($w['title'] ?? '');
                    if (empty($title)) continue;
                    $authors = [];
                    foreach (array_slice($w['authorships'] ?? [], 0, 2) as $a) {
                        if (!empty($a['author']['display_name'])) {
                            $parts = explode(' ', trim($a['author']['display_name']));
                            $authors[] = end($parts);
                        }
                    }
                    $authorLabel = !empty($authors) ? implode(' & ', $authors) : 'Peneliti';
                    if (count($w['authorships'] ?? []) > 2) $authorLabel = $authors[0] . ' et al.';
                    $year = $w['publication_year'] ?? date('Y');
                    $venue = $w['primary_location']['source']['display_name'] ?? 'Jurnal Ilmiah';
                    $refs[] = "{$authorLabel} ({$year}) - \"{$title}\" [{$venue}]";
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
            ['label' => 'HALAMAN SAMPUL', 'page' => 'i', 'level' => 1],
            ['label' => 'KATA PENGANTAR', 'page' => 'ii', 'level' => 1],
            ['label' => 'DAFTAR ISI', 'page' => 'iii', 'level' => 1],
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
            if (empty($content)) continue;

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
        } catch (Throwable $e) {}

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
                    if ($provider === 'groq') $provider = 'deepseek';
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
            if (empty($title)) return null;

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
            if ($scope === 'all' || $scope === 'id') {
                $oaId = Http::timeout(10)->get('https://api.openalex.org/works', [
                    'search' => $keyword,
                    'filter' => 'is_oa:true,from_publication_date:2019-01-01,institutions.country_code:ID',
                    'per-page' => $scope === 'id' ? 10 : 5,
                ]);
                if ($oaId->successful()) {
                    foreach ($oaId->json()['results'] ?? [] as $w) {
                        $item = $mapOpenAlexWork($w, 'Indonesia');
                        if ($item) $journalsId[] = $item;
                    }
                }
                if (count($journalsId) < 3) {
                    $oaIdLang = Http::timeout(10)->get('https://api.openalex.org/works', [
                        'search' => $keyword . ' Indonesia',
                        'filter' => 'is_oa:true,from_publication_date:2019-01-01,language:id',
                        'per-page' => 5,
                    ]);
                    if ($oaIdLang->successful()) {
                        foreach ($oaIdLang->json()['results'] ?? [] as $w) {
                            $item = $mapOpenAlexWork($w, 'Indonesia');
                            if ($item) $journalsId[] = $item;
                        }
                    }
                }
            }

            if ($scope === 'all' || $scope === 'int') {
                $oaInt = Http::timeout(10)->get('https://api.openalex.org/works', [
                    'search' => $keyword,
                    'filter' => 'is_oa:true,from_publication_date:2019-01-01,language:en',
                    'per-page' => $scope === 'int' ? 10 : 5,
                ]);
                if ($oaInt->successful()) {
                    foreach ($oaInt->json()['results'] ?? [] as $w) {
                        $item = $mapOpenAlexWork($w, 'Internasional');
                        if ($item) $journalsInt[] = $item;
                    }
                }
            }
        } catch (Throwable $e) {
            Log::warning('OpenAlex search fallback: ' . $e->getMessage());
        }

        // Lengkapi via AI jika hasil OpenAlex masih kurang
        if (($scope === 'all' && (count($journalsId) < 3 || count($journalsInt) < 3))
            || ($scope === 'id' && count($journalsId) < 5)
            || ($scope === 'int' && count($journalsInt) < 5)) {
            try {
                $prompt = "Berikan referensi jurnal ilmiah (2020-2026) yang relevan dengan topik '{$keyword}'.
Bagi menjadi Jurnal Indonesia (terindeks Sinta/Garuda) dan Jurnal Internasional (Scopus/IEEE/Springer/Elsevier).
Format WAJIB per baris: Kategori(Indonesia/Internasional)|Judul Artikel|Nama Penulis|Nama Jurnal|Tahun
Berikan masing-masing 4 artikel Indonesia dan 4 artikel Internasional tanpa nomor urut dan tanpa kalimat pengantar.";

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
                        $jYear = isset($parts[4]) ? trim($parts[4]) : '2024';

                        $entry = [
                            'title' => $jTitle,
                            'author' => $jAuthor,
                            'journal' => $jVenue,
                            'year' => $jYear,
                            'pdf_url' => 'https://scholar.google.com/scholar?q=' . urlencode($jTitle . ' ' . $jAuthor . ' filetype:pdf'),
                            'doi_url' => 'https://scholar.google.com/scholar?q=' . urlencode($jTitle),
                            'citation' => "{$jAuthor} ({$jYear}). {$jTitle}. {$jVenue}.",
                            'scope' => $scopeLabel,
                            'is_real_oa' => false,
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
                if (isset($journalsId[$i])) $merged[] = $journalsId[$i];
                if (isset($journalsInt[$i])) $merged[] = $journalsInt[$i];
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
{$extraContext}{$verifiedRefText}
PEDOMAN STANDAR PENULISAN SKRIPSI INDONESIA (GITHUB ACADEMIC SKILL):
{$skillRules}

ATURAN PENULISAN AKADEMIK (SANGAT KETAT):
1. Gaya Bahasa: Gunakan Bahasa {$bahasa} ilmiah baku sesuai EYD V/KBBI, kohesif, objektif, dan bebas klise AI (DILARANG membuka kalimat dengan \"Kondisi ideal dalam...\", \"Di era globalisasi saat ini...\", atau \"Pada era modern...\").
2. Kedalaman Materi: Setiap sub-bab wajib ditulis mendalam minimal 3-5 paragraf utuh (450-750 kata per sub-bab) dengan argumentasi berbasis fenomena, teori, dan bukti empiris.
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
        if ($bab == 'bab2') $context = "KONTEKS BAB 1: " . Str::limit(strip_tags($project->bab1_content), 1500);
        elseif ($bab == 'bab3') $context = "KONTEKS BAB 1 & 2: " . Str::limit(strip_tags($project->bab1_content . ' ' . $project->bab2_content), 1500);
        elseif ($bab == 'bab4') $context = "KONTEKS BAB 1 & 3: " . Str::limit(strip_tags($project->bab1_content . ' ' . $project->bab3_content), 1800);
        elseif ($bab == 'bab5') {
            $context = "HASIL PENELITIAN BAB 4: " . Str::limit(strip_tags($project->bab4_content), 1800);
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
TUGAS: Tulis Isi BAB I (PENDAHULUAN) secara lengkap dan sistematis sesuai standar skripsi Indonesia.
Sub-bab WAJIB berurutan (7 sub-bab):
1.1 Latar Belakang Masalah (Uraikan fenomena makro, data empiris / isu terkini, ketimpangan praktik vs teori, penelitian terdahulu yang relevan, research gap, dan urgensi pemilihan judul - minimal 5 paragraf mendalam; JANGAN gunakan kalimat pembuka 'Kondisi ideal dalam...')
1.2 Identifikasi Masalah (Daftar permasalahan konkret yang teramati pada objek penelitian dalam format poin bernomor 1., 2., 3., dst.)
1.3 Batasan Masalah (Batasi ruang lingkup penelitian secara tegas: variabel/algoritma yang dikaji, objek/populasi, periode waktu, dan pendekatan metode yang digunakan)
1.4 Rumusan Masalah (Pertanyaan penelitian spesifik yang konsisten dengan judul dan batasan masalah dalam poin bernomor 1., 2., 3.)
1.5 Tujuan Penelitian (Pernyataan tujuan operasional yang menjawab langsung setiap poin Rumusan Masalah)
1.6 Manfaat Penelitian (Uraikan dalam dua bagian eksplisit: 1.6.1 Manfaat Teoretis dan 1.6.2 Manfaat Praktis bagi pihak terkait)
1.7 Sistematika Penulisan (Ringkasan isi masing-masing bab yang akan dibahas)",
            'bab2' => $master . "\n$context\n
TUGAS: Tulis Isi BAB II (TINJAUAN PUSTAKA) secara lengkap.
Sub-bab WAJIB berurutan:
2.1 Landasan Teori (Uraikan Grand Theory, Middle Theory, definisi para ahli, dimensi, dan indikator setiap variabel/konsep dalam judul secara mendalam)
2.2 Penelitian Terdahulu (Bahas minimal 5 penelitian jurnal terbaru 2020-2026 beserta metode, hasil, persamaan, dan perbedaannya dengan penelitian ini)
2.3 Kerangka Pemikiran (Alur logika hubungan antarvariabel/tahapan penelitian)
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
5.2 Saran (Saran akademis dan praktis yang spesifik)

Setelah sub-bab 5.2 selesai, buat baris baru bertuliskan persis:
===DAFTAR PUSTAKA===
Lalu tuliskan minimal 15 referensi ilmiah urut abjad A-Z (format APA 7th) yang mencakup seluruh nama penulis yang dikutip pada Bab 1 hingga Bab 4 tanpa kalimat pengantar dan tanpa placeholder.",
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
                            $dpPrompt = "Tulis DAFTAR PUSTAKA APA 7th Edition berdasarkan sitasi nama penulis dan tahun yang dikutip pada naskah skripsi berjudul '{$project->judul}' (Prodi: {$project->prodi}).\nATURAN KETAT:\n1. LANGSUNG tuliskan baris referensi pertama (A-Z) TANPA kalimat pembuka/pengantar seperti 'Berikut adalah daftar pustaka...'.\n2. DILARANG menulis placeholder 'Judul artikel tentang...' atau 'Nama Jurnal, Volume(Issue), Halaman'. Setiap referensi WAJIB memiliki judul artikel ilmiah lengkap dan spesifik, nama jurnal nyata, volume, nomor, dan halaman spesifik (contoh: 14(2), 112-125).\n3. Tanpa tanda bintang markdown (*).\n\nNASKAH:\n" . Str::limit($allText, 5500);
                            $dpRaw = $this->ai->generateText($dpPrompt, $model);
                            $cleanedDp = $this->cleanDaftarPustaka($dpRaw);
                            if (!empty($cleanedDp)) {
                                $project->daftar_pustaka = $cleanedDp;
                            }
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

            return response()->json([
                'status' => 'success',
                'content' => $project->{$chapter . '_content'},
                'daftar_pustaka' => $project->daftar_pustaka,
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => 'AI Error: ' . $e->getMessage()], 500);
        }
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

        $targetScopeRule = !empty($subchapter) && !str_starts_with(strtolower($subchapter), 'semua')
            ? "FOKUS REVISI KHUSUS SUB-BAB: \"{$subchapter}\". Perbaiki dan perdalam bagian \"{$subchapter}\" sesuai instruksi/catatan dosen, sementara sub-bab lainnya pada {$chapterName} HARUS TETAP DIPERTAHANKAN UTUH agar tidak hilang."
            : "FOKUS REVISI PADA: Seluruh {$chapterName}.";

        $modeRule = $revisionMode === 'format'
            ? "MODE REVISI FORMAT PENULISAN: Perbaiki struktur kalimat sesuai EYD V & KBBI, hilangkan kalimat pembuka klise AI, rapikan penomoran sub-bab desimal & poin bernomor, serta pastikan format sitasi dalam teks konsisten."
            : "MODE REVISI ISI SKRIPSI: Perdalam substansi akademik, tambahkan argumen empiris/sitasi terbaru, perbaiki celah logika, dan terapkan koreksi substansi dari dosen pembimbing.";

        $prompt = "PERAN: Anda adalah Profesor Pembimbing Skripsi Senior.
TUGAS: Lakukan REVISI MENDALAM pada {$chapterName} untuk skripsi berjudul \"{$project->judul}\" (Prodi: {$project->prodi}, Metode: {$project->metode_penelitian}).
{$targetScopeRule}
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

ATURAN REVISI:
1. Jika terdapat gambar coretan dosen terlampir, baca seluruh catatan/koreksi pada gambar tersebut dan terapkan perbaikannya ke dalam naskah.
2. Pertahankan sub-bab yang sudah benar dan tidak diminta diubah secara utuh.
3. Gunakan format penomoran sub-bab desimal (misal 1.1, 1.2) pada baris tersendiri, tanpa tanda bintang markdown (**) atau pagar (###), dan tanpa kata pengantar AI.
4. Kembalikan naskah {$chapterName} yang sudah direvisi secara utuh.";

        try {
            $revised = $this->ai->generateWithImage($prompt, $request->image_base64, $model);
            $cleanContent = $this->cleanTextKeepFormat($revised);
            $cleanContent = $this->processChapterContent($cleanContent, $chapter);

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
                    ['spaceBefore' => 120, 'spaceAfter' => 100, 'indentation' => ['firstLine' => 0]]
                );
            } elseif (preg_match('/^\d+\.\s+/', $cleanLine)) {
                $section->addText(
                    $cleanLine,
                    ['name' => 'Times New Roman', 'size' => 12],
                    ['alignment' => Jc::BOTH, 'lineHeight' => 1.5, 'indentation' => ['left' => 420, 'hanging' => 360], 'spaceAfter' => 60]
                );
            } else {
                $pStyle = ['alignment' => Jc::BOTH, 'lineHeight' => 1.5, 'indentation' => ['firstLine' => 720], 'spaceAfter' => 80];
                $section->addText($cleanLine, ['name' => 'Times New Roman', 'size' => 12], $pStyle);
            }
        }
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
                    $section->addText(trim($ref), ['name' => 'Times New Roman', 'size' => 12], ['alignment' => Jc::BOTH, 'indentation' => ['left' => 720, 'hanging' => 720], 'spaceAfter' => 80]);
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

        if ($logoPath && file_exists($logoPath)) {
            @unlink($logoPath);
        }

        return response()->download($path, $filename)->deleteFileAfterSend(true);
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

        $logoHtml = "<div style='margin: 36px 0;'>[ LOGO UNIVERSITAS ]</div>";
        if ($logoPath && file_exists($logoPath)) {
            $mime = mime_content_type($logoPath) ?: 'image/png';
            $b64 = base64_encode(file_get_contents($logoPath));
            $logoHtml = "<div style='margin: 28px 0; text-align:center;'><img src='data:{$mime};base64,{$b64}' style='width:115px; height:auto;'></div>";
            @unlink($logoPath);
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

        $html .= "<h2>KATA PENGANTAR</h2>
            <p>Puji syukur penulis panjatkan ke hadirat Tuhan Yang Maha Esa atas segala rahmat dan karunia-Nya sehingga penyusunan {$jenisDokumen} berjudul \"" . e($project->judul) . "\" dapat diselesaikan pada Program Studi " . e($project->prodi) . ($kampus ? ", " . e($kampus) : "") . ".</p>
            <div style='page-break-after:always;'></div>
            <h2>DAFTAR ISI</h2>
            <div style='margin-top:16px;'>{$tocRowsHtml}</div>
            <div style='page-break-after:always;'></div>";

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
                $html .= "<h2>BAB " . $nums[$k] . "<br>$v</h2>" . nl2br(e($c));
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

        if (ob_get_length()) ob_end_clean();
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
            if (strlen($l) < 12) continue;
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
                ->orderBy('updated_at', 'desc')
                ->take(12)
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

            $logoHtml = '';
            if ($logoPath && file_exists($logoPath)) {
                $mime = mime_content_type($logoPath) ?: 'image/png';
                $b64 = base64_encode(file_get_contents($logoPath));
                $logoHtml = "<div style='margin: 24px 0; text-align:center;'><img src='data:{$mime};base64,{$b64}' style='width:105px; height:auto;'></div>";
                @unlink($logoPath);
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
                    $html .= "<h2>BAB " . $nums[$k] . "<br>$v</h2>" . nl2br(e($c));
                    $html .= "<div style='page-break-after:always;'></div>";
                }
            }
            if (!empty($project->daftar_pustaka)) {
                $html .= "<h2>DAFTAR PUSTAKA</h2><p style='text-indent:0; padding-left:1.27cm; text-indent:-1.27cm;'>" . nl2br(e($project->daftar_pustaka)) . "</p>";
            }
            $html .= "</body></html>";

            $dompdf = new Dompdf();
            $dompdf->set_option('isHtml5ParserEnabled', true);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4');
            $dompdf->render();
            $inline = $request->boolean('inline');
            return $dompdf->stream('Dokumen_' . Str::slug(substr($project->judul, 0, 28), '_') . '.pdf', ['Attachment' => !$inline]);
        }

        if (ob_get_length()) ob_end_clean();
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

        $allText = strip_tags($project->bab1_content . ' ' . $project->bab2_content . ' ' . $project->bab3_content . ' ' . $project->bab4_content . ' ' . $project->bab5_content);
        $prompt = "Tulis DAFTAR PUSTAKA (Format APA 7th Edition) lengkap berdasarkan naskah skripsi berjudul '{$project->judul}' (Prodi: {$project->prodi}) berikut ini.
ATURAN WAJIB:
1. LANGSUNG tulis baris daftar pustaka pertama (urut A-Z) TANPA kalimat pengantar seperti 'Berikut adalah daftar pustaka...'.
2. DILARANG menulis placeholder 'Judul artikel tentang...' atau 'Nama Jurnal, Volume(Issue), Halaman'. Setiap entri WAJIB memiliki judul artikel ilmiah yang spesifik dan lengkap, nama jurnal akademik nyata, volume, nomor, dan halaman spesifik (misal: 12(2), 45-58).
3. Jangan gunakan tanda bintang markdown (*).

NASKAH:
" . substr($allText, 0, 8000);

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
            'image'    => 'nullable|string',
            'model'    => 'nullable|string'
        ]);

        try {
            $question = $request->question;
            $image = $request->image;
            $model = $request->input('model', 'auto');

            $prompt = "PERAN: Anda adalah Dosen Pembimbing Akademik.
Jawab pertanyaan berikut secara sistematis, jelas, dan mudah dipahami:
PERTANYAAN: {$question}";

            $answer = $this->ai->generateWithImage($prompt, $image, $model);

            return response()->json([
                'status' => 'success',
                'answer' => $answer,
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

            return response()->json([
                'status' => 'success',
                'title' => $docTitle,
                'slides_data' => $slidesArray,
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function simulateDefense(Request $request)
    {
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

                return response()->json([
                    'status' => 'success',
                    'title' => $docTitle,
                    'questions' => array_slice($questions, 0, 5),
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

            return response()->json([
                'status' => 'success',
                'evaluation' => $evalData,
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
