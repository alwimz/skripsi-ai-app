<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\User;
use App\Services\AIManagerService;
// Library Word
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\Shared\Html;
use PhpOffice\PhpWord\Style\TOC;
use PhpOffice\PhpWord\Settings;

// Library PDF
use Dompdf\Dompdf;
use Dompdf\Options;
// Facades
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; 
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;
use Throwable;

class GeneratorController extends Controller
{
    protected $ai;

    public function __construct(AIManagerService $ai)
    {
        $this->ai = $ai;
    }

    // =========================================================================
    // 1. DASHBOARD & DATA
    // =========================================================================

    public function preGeneratorForm() 
    { 
        $user = Auth::user();
        $history = Project::where('user_id', $user->id)->orderBy('updated_at', 'desc')->get();
        try {
            $apiKeys = DB::table('api_keys')->where('user_id', $user->id)->orderBy('created_at', 'desc')->get();
        } catch (\Exception $e) { $apiKeys = collect([]); }

        return view('pre_generator', compact('history', 'apiKeys', 'user')); 
    }

    public function addApiKey(Request $request)
    {
        $request->validate(['key' => 'required|string|min:10', 'provider' => 'required|in:gemini,groq']);
        try {
            if (DB::table('api_keys')->where('key', $request->key)->exists()) return redirect()->back()->with('error', 'API Key sudah ada.');
            DB::table('api_keys')->insert([
                'user_id' => Auth::id(), 'key' => $request->key, 'provider' => $request->provider, 
                'is_active' => true, 'usage_count' => 0, 'created_at' => now(), 'updated_at' => now()
            ]);
            return redirect()->back()->with('success', "API Key {$request->provider} disimpan!");
        } catch (\Exception $e) { return redirect()->back()->with('error', 'Gagal: ' . $e->getMessage()); }
    }

    public function deleteApiKey($id)
    {
        try {
            DB::table('api_keys')->where('id', $id)->where('user_id', Auth::id())->delete();
            return redirect()->back()->with('success', 'API Key dihapus.');
        } catch (\Exception $e) { return redirect()->back()->with('error', 'Gagal menghapus.'); }
    }

    public function claimReward(Request $request)
    {
        $type = $request->type; $user = User::find(Auth::id());
        $reward = ($type === 'login') ? 2 : 5;
        if ($reward > 0) {
            $user->credits += $reward; $user->save();
            return response()->json(['status' => 'success', 'message' => "+$reward Token!", 'new_credits' => $user->credits]);
        }
        return response()->json(['status' => 'error', 'message' => 'Gagal klaim.']);
    }

    // =========================================================================
    // 2. PROJECT MANAGEMENT
    // =========================================================================

    public function startProject(Request $request)
    {
        $request->validate(['judul' => 'required','prodi' => 'required','metode_penelitian' => 'required']);
        $project = Project::create([
            'judul' => $request->judul, 'prodi' => $request->prodi, 'metode_penelitian' => $request->metode_penelitian,
            'variabel_penelitian' => $request->variabel_penelitian ?? 'Sesuai Judul', 'sumber_data' => $request->sumber_data ?? 'Studi Pustaka',
            'user_id' => Auth::id(), 'status' => 'draft'
        ]);
        return redirect()->route('app.index', ['id' => $project->id]);
    }

    public function generatorIndex($id)
    {
        $project = Project::where('user_id', Auth::id())->findOrFail($id);
        return view('generator', compact('project'));
    }

    // =========================================================================
    // 3. AI TOOLS
    // =========================================================================

    public function generateTitles(Request $request)
    {
        $user = Auth::user();
        if ($user->credits < 1) return response()->json(['status' => 'error', 'message' => 'Token habis! Klaim reward dulu.']);

        $request->validate(['prodi' => 'required', 'topik' => 'required']);
        $model = $request->input('model', 'auto'); 

        $prompt = "Sebagai Dosen Pembimbing Senior, berikan 10 judul skripsi S1 untuk prodi '{$request->prodi}' dengan minat topik '{$request->topik}'.
        Syarat Judul:
        1. SPESIFIK & TERBARU (Tren akademik saat ini).
        2. Memuat variabel jelas (X dan Y) atau objek studi kasus yang spesifik.
        3. Hindari judul pasaran atau terlalu umum.
        4. Bahasa Indonesia Formal Akademik.
        5. Output hanya list judul tanpa penomoran.
        6. jangan masukkan kata Berikut beberapa contoh judul skripsi S1";

        try {
            $content = $this->ai->generateText($prompt, $model);
            $titles = array_filter(explode("\n", $content), fn($t) => !empty(trim($t)));
            $cleanTitles = array_map(function($t) { return trim(preg_replace('/^[\d\-\.\s]+/', '', $t)); }, $titles);
            
            $user->decrement('credits'); 

            return response()->json(['status' => 'success', 'data' => array_values($cleanTitles), 'new_credits' => $user->credits]);
        } catch (Exception $e) { return response()->json(['status' => 'error', 'message' => $e->getMessage()]); }
    }

    public function findJournals(Request $request)
    {
        $user = Auth::user();
        if ($user->credits < 1) return response()->json(['status' => 'error', 'message' => 'Token habis! Klaim reward dulu.']);

        $request->validate(['keyword' => 'required']);
        $model = $request->input('model', 'auto'); 

        $prompt = "Carikan 5 jurnal (Judul|Penulis|Jurnal|Tahun) topik '{$request->keyword}'. Indo=Nasional, Ingg=Internasional. Prioritas 2019-2024. Output baris per baris.";

        try {
            $content = $this->ai->generateText($prompt, $model);
            $lines = array_filter(explode("\n", $content), fn($t) => !empty(trim($t)));
            $journals = [];
            foreach ($lines as $line) {
                $parts = explode('|', $line);
                if (count($parts) >= 3) {
                    $journals[] = ['title' => trim($parts[0]), 'author' => trim($parts[1]), 'journal' => trim($parts[2]), 'year' => isset($parts[3]) ? trim($parts[3]) : 'N/A'];
                }
            }
            
            $user->decrement('credits');

            return response()->json(['status' => 'success', 'data' => $journals, 'new_credits' => $user->credits]);
        } catch (Exception $e) { return response()->json(['status' => 'error', 'message' => $e->getMessage()]); }
    }

  public function askAi(Request $request)
    {
        $request->validate(['message' => 'required']);
        $model = $request->input('model', 'auto'); 
        
        try {
            // PROMPT DOSEN (Tetap sama)
            $prompt = "PERAN: Anda adalah Dosen Pembimbing Akademik Senior yang cerdas, berwawasan luas, dan suportif.
            
            KONTEKS: Seorang mahasiswa bertanya kepada Anda terkait materi kuliah, skripsi, atau tugas akademik.
            
            INSTRUKSI MENJAWAB:
            1. GAYA BAHASA: Akademis, Formal, namun tetap Ramah dan Membimbing (seperti dosen yang baik).
            2. JIKA PERTANYAAN SOAL/TEORI: Jelaskan konsep dasarnya terlebih dahulu sebelum memberikan jawaban akhir. Tujuannya agar mahasiswa paham (edukatif).
            3. JIKA PERTANYAAN SKRIPSI: Berikan saran metodologi yang konkret, logis, dan sesuai kaidah penulisan ilmiah.
            4. FORMAT: Gunakan paragraf yang rapi. Jika perlu poin-poin, gunakan list yang jelas.
            5. Hindari jawaban yang bertele-tele atau terlalu robotik. Langsung ke inti permasalahan (To The Point).
            
            PERTANYAAN MAHASISWA: \"{$request->message}\"
            
            JAWABAN DOSEN:";

            $response = $this->ai->generateText($prompt, $model);
            
            // PERBAIKAN DISINI: Langsung bersihkan teks tanpa memanggil method luar yang hilang
            $cleanResponse = trim(strip_tags($response));
            
            return response()->json([
                'status' => 'success', 
                'reply' => nl2br($cleanResponse)
            ]); 
            
        } catch (Exception $e) { 
            return response()->json([
                'status' => 'error', 
                'message' => 'Maaf, Dosen sedang sibuk (Error AI).'
            ]); 
        }
    }

    // =========================================================================
    // 4. PROMPT ENGINEERING (DIPERBAIKI: SUMBER JURNAL ASLI)
    // =========================================================================

    private function promptMaster(Project $project): string
    {
        return "PERAN: Anda adalah Profesor dan Tim Editor Ahli Jurnal Ilmiah bereputasi (SINTA/Scopus), pakar dalam menyusun kerangka penulisan tugas akhir / skripsi mahasiswa S1.
    
KONTEKS PENELITIAN:
- Judul Skripsi: {$project->judul}
- Program Studi: {$project->prodi}
- Metode Penelitian: {$project->metode_penelitian}
- Variabel Penelitian: {$project->variabel_penelitian}

ATURAN STRUKTURAL & LINGUISTIK (SANGAT KETAT):
1. Kualitas Akademik Tinggi: Gunakan gaya bahasa formal, ilmiah, kohesif, dan koheren. Gunakan struktur paragraf deduktif/induktif yang mendalam. Hindari bahasa yang klise atau bertele-tele. Setiap kalimat harus memiliki nilai substansi penelitian.
2. Panjang Teks & Kualitas: Hasilkan tulisan yang PANJANG dan MENDALAM. Satu subjudul harus berisi minimal 3-5 paragraf yang ekstensif (minimal 500-800 kata per sub-bab). Jabarkan fenomena, argumen, dan logika keilmuan.
3. Sitasi & Referensi Wajib: WAJIB memasukkan in-text citation (Contoh: Menurut Wijaya (2023)... atau (Anderson & Smith, 2022)) secara organik dalam paragraf yang membahas klaim sains atau teori. Gunakan nama penulis jurnal yang lazim di prodi tersebut.
4. Format Heading: Judul sub-bab HARUS ada pada baris mandiri dengan format desimal (contoh: '1.1 Latar Belakang Masalah').
5. Tanpa Markdown: JANGAN gunakan simbol markdown (**, ###). JANGAN memakai list bullet (<ul>) berlebihan. Jika perlu daftar, ketik angka (1., 2.).
6. Larangan AI: JANGAN MENULISKAN KATA PENGANTAR (e.g., 'Berikut adalah bab...', 'Tentu saya bantu...'). Langsung tulis isinya. Jangan pernah menulis 'BAB I PENDAHULUAN' di kalimat pertama, sistem akan membuatnya otomatis. Langsung masuk ke sub-bab.
";
    }

    private function promptBab(string $bab, Project $project): string
    {
        $master = $this->promptMaster($project);
        
        $context = "";
        if ($bab == 'bab2') $context = "KONTEKS BAB 1: " . Str::limit(strip_tags($project->bab1_content), 1000);
        elseif ($bab == 'bab3') $context = "KONTEKS TUJUAN: " . Str::limit(strip_tags($project->bab1_content), 800);
        elseif ($bab == 'bab4') $context = "KONTEKS METODE: " . Str::limit(strip_tags($project->bab3_content), 800);
        elseif ($bab == 'bab5') {
            $context = "HASIL PENELITIAN: " . Str::limit(strip_tags($project->bab4_content), 1000);
            
            // MENAIKKAN LIMIT KARAKTER AGAR LEBIH BANYAK SITASI TERBACA
            $allText = $project->bab1_content . " " . $project->bab2_content . " " . $project->bab3_content . " " . $project->bab4_content;
            $context .= "\n\n=== TEKS DARI BAB 1 SAMPAI BAB 4 ===\n" . Str::limit(strip_tags($allText), 15000); 
        }

        return match ($bab) {
            'bab1' => $master . "
TUGAS: Tulis Isi BAB I (PENDAHULUAN) secara komprehensif. LANGSUNG DIMULAI DARI SUB-BAB PERTAMA (Tanpa judul Bab besar).

Sub-bab wajib:
1.1 Latar Belakang Masalah
   - Gunakan pendekatan piramida terbalik: mulai dari konteks makro/global, turun ke fenomena/masalah empiris spesifik pada objek studi, lalu soroti research gap. Beri dukungan kutipan ahli dan uraikan mengapa skripsi ini HARUS diteliti. MINIMAL 5 PARAGRAF PANJANG.
1.2 Rumusan Masalah
   - Susun dalam bentuk pertanyaan penelitian yang tajam berdasarkan identifikasi masalah (poin 1., 2., 3.).
1.3 Tujuan Penelitian
   - Kalimat afirmatif untuk menjawab rumusan masalah.
1.4 Manfaat Penelitian
   - Uraikan kegunaan Teoritis (akademis) dan Praktis (untuk subjek riset/instansi).
1.5 Sistematika Penulisan
   - Jelaskan garis besar penulisan Bab 1 hingga 5 dalam satu paragraf per-bab.
",
            'bab2' => $master . "
$context
TUGAS: Tulis Isi BAB II (TINJAUAN PUSTAKA). INI ADALAH BAGIAN TEORETIS PALING MENDALAM.

Sub-bab wajib:
2.1 Landasan Teori
   - Bedah grand theory, middle range theory, dan applied theory dari setiap variabel dalam judul. Jabarkan dimensi, indikator, fungsionalitas teori menurut tokoh/ahli utama di bidang ini (sertakan nama dan tahun terkaan validitas akademis tinggi). MINIMAL 6-8 PARAGRAF MENDALAM.
2.2 Penelitian Terdahulu
   - Bahas 5 penelitian terdahulu yang relevan. Jangan berbentuk tabel. Bentuk teks naratif: Nama Peneliti (Tahun) meneliti mengenai... dengan metode... hasil dan implikasinya... Letak perbedaan penelitian saat ini adalah (posisi state of the art).
2.3 Kerangka Berpikir / Konseptual
   - Jelaskan narasi rasionalitas antar-variabel atau antar-fenomena dari hulu ke hilir.
2.4 Hipotesis Penelitian / Fokus Peneltian
   - Tergantung jenis riset, ajukan dugaan sementara secara jelas (Ha dan H0 jika studi kuantitatif).
",
            'bab3' => $master . "
$context
TUGAS: Tulis Isi BAB III (METODOLOGI PENELITIAN). Sesuaikan dengan ketat metode: {$project->metode_penelitian}.

Sub-bab wajib:
3.1 Pendekatan dan Jenis Penelitian
   - Argumentasikan MENGAPA metode rasionalis ini digunakan untuk mensintesis judul. 
3.2 Objek, Tempat, dan Waktu Penelitian
   - Paparkan alasan penentuan area studi.
3.3 Populasi dan Sampel (Kuantitatif) / Informan (Kualitatif)
   - Jabarkan penentuan subjek, sebut teknis purposif/random, lalu argumentasikan rumusnya jika kuantitatif (Misal Slovin).
3.4 Teknik Pengumpulan Data
   - Uraikan instrumen: Observasi, Wawancara, Kuesioner/Survei, Dokumentasi. Beri ulasan setiap instrumenya.
3.5 Uji Keabsahan / Instrumen Validitas dan Reliabilitas
3.6 Teknik Analisis Data
   - Uraikan tahap pengolahan data persis dengan metode statistik (Misal regresi linear berganda) atau Miles & Huberman (reduksi, display, penarikan kesimpulan kualitatif).
",
            'bab4' => $master . "
$context
TUGAS: Tulis Isi BAB IV (HASIL PENELITIAN DAN PEMBAHASAN). Bagian ini adalah ruh inti intelektual penelitian.

Sub-bab wajib:
4.1 Gambaran Umum Objek Penelitian
   - Buat narasi simulasi deskripsi fisik, historis, dan sosiologis lokasi/objek riset yang sesuai topik persoalan judul proyek.
4.2 Hasil Penelitian
   - Sajikan deskripsi statistik atau pemaparan bukti lapangan secara naratif dan panjang. Gunakan data numerik proksi wajar untuk meyakinkan pembaca (Jika Kuantitatif) atau kutipan transkrip wawancara konseptual.
4.3 Pembahasan
   - BAGIAN PALING PENTING. Analisis, sintesis, dan interpretasikan temuan nomor 4.2. Konfirmasi apakah temuan ini KONGRUEN dengan Landasan Teori Bab 2 dan menolak / mendukung Hipotesis. Tulis argumen saintifik setajam mungkin mengapa hasil tersebut terjadi di lapangan. MINIMAL 5 PARAGRAF EKSTENSIF.
",
            'bab5' => $master . "
$context
TUGAS: Tulis Isi BAB V (PENUTUP).

Sub-bab wajib:
5.1 Kesimpulan
   - Jangan menyalin ulang hasil/pembahasan. Konversikan temuan menjadi jawaban sintesis tegas atas seluruh rumusan masalah.
5.2 Saran
   - Berikan rekomendasi operasional untuk praktisi dan terobosan akademis untuk peneliti selanjutnya.

INSTRUKSI KHUSUS DAFTAR PUSTAKA:
1. Selesaikan paragraf Saran.
2. BUAT BARIS BARU DENGAN TULISAN EXACT BERIKUT:
===DAFTAR PUSTAKA===
3. Di bawah garis tersebut, tulis minimal 15 REFERENSI dengan format APA Style.
4. SUMBER REFERENSI: Kumpulkan dari SETIAP NAMA (SITASI) YANG TELAH MUNCUL DARI BAB 1 HINGGA BAB 4, ditambahkan referensi fundamen tambahan khusus pakar metodologi penelitian dan pakar teori inti variabel proyek ini.
5. Urutkan berdasarkan Abjad A-Z.
",
            default => $master
        };
    }

    // =========================================================================
    // 5. CORE GENERATOR FUNCTION
    // =========================================================================
    
    private function cleanTextKeepFormat($text) {
        if (empty($text)) return '';
        $text = preg_replace('/\*\*(.*?)\*\*/', '$1', $text);
        $text = preg_replace('/###\s*(.*?)\n/', '$1', $text);
        return trim($text);
    }

    private function processChapterContent($content, $chapterTitle) {
        $lines = explode("\n", $content); 
        $cleanLines = [];
        
        $chapterKeywords = [
            'BAB I', 'BAB 1', 'PENDAHULUAN', 
            'BAB II', 'BAB 2', 'TINJAUAN PUSTAKA', 
            'BAB III', 'BAB 3', 'METODOLOGI', 
            'BAB IV', 'BAB 4', 'HASIL DAN', 
            'BAB V', 'BAB 5', 'PENUTUP'
        ];

        foreach ($lines as $line) {
            $lineTrimmed = trim(strip_tags($line));
            $lineUpper = strtoupper($lineTrimmed);
            
            $isTitle = false;
            foreach ($chapterKeywords as $keyword) {
                if ($lineUpper === $keyword || preg_match('/^\d+\s+' . preg_quote($keyword, '/') . '$/', $lineUpper)) {
                    $isTitle = true; break;
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
        if ($user->credits <= 0) return response()->json(['error' => 'Token Habis!'], 403);

        $project = Project::where('user_id', Auth::id())->findOrFail($id);
        $chapter = $request->chapter;
        $model = $request->input('model', 'auto'); 

        try {
            $finalPrompt = $this->promptBab($chapter, $project);
            $content = $this->ai->generateText($finalPrompt, $model);

            $cleanContent = $this->cleanTextKeepFormat($content);
            $cleanContent = $this->processChapterContent($cleanContent, $chapter);
            
            if ($chapter === 'bab5') {
                // SEPARATOR HANDLING
                $parts = explode('===DAFTAR PUSTAKA===', $cleanContent);
                
                if (count($parts) > 1) {
                    $project->bab5_content = $parts[0];
                    $rawDP = strip_tags(end($parts));
                    $linesDP = array_filter(explode("\n", $rawDP), fn($l) => strlen(trim($l)) > 5);
                    $project->daftar_pustaka = implode("\n", array_map('trim', $linesDP));
                } else {
                    // Fallback Regex
                    $partsRegex = preg_split('/(DAFTAR PUSTAKA|REFERENSI)/i', $cleanContent);
                     if (count($partsRegex) > 1) {
                        $project->bab5_content = $partsRegex[0];
                        $rawDP = strip_tags(end($partsRegex));
                        $linesDP = array_filter(explode("\n", $rawDP), fn($l) => strlen(trim($l)) > 5);
                        $project->daftar_pustaka = implode("\n", array_map('trim', $linesDP));
                     } else {
                        $project->bab5_content = $cleanContent;
                     }
                }
            } else {
                $project->{$chapter . '_content'} = $cleanContent;
            }

            if ($chapter === 'bab2') {
                $promptTable = "Buat HTML Table sederhana <table><tr><th>Peneliti</th><th>Tahun</th><th>Judul</th><th>Hasil</th></tr>...</table> yang merangkum 'Penelitian Terdahulu' dari teks ini: " . strip_tags($cleanContent);
                $tableHtml = $this->ai->generateText($promptTable, $model);
                
                $dom = new \DOMDocument();
                libxml_use_internal_errors(true);
                $dom->loadHTML($tableHtml);
                $project->bab2_tabel_penelitian = $dom->saveHTML();
            }

            $project->save();
            $user->decrement('credits');
            return response()->json(['status' => 'success', 'remaining_credits' => $user->credits]);

        } catch (Exception $e) { return response()->json(['error' => 'AI Error: ' . $e->getMessage()], 500); }
    }

    // =========================================================================
    // 6. DOWNLOAD HANDLER (MAINTAIN FORMATTING)
    // =========================================================================

    private function parseAndAddContent($section, $htmlContent) {
        $text = str_replace(['<p>', '</p>', '<br>', '<br/>'], ["\n", "\n", "\n", "\n"], $htmlContent);
        $lines = explode("\n", $text);

        foreach ($lines as $line) {
            $cleanLine = trim(strip_tags($line));
            if (empty($cleanLine)) continue;

            // Heading Level 2 (1.1, 2.1) - Tidak Indent
            if (preg_match('/^\d+\.\d+\s+/', $cleanLine)) {
                $section->addTitle($cleanLine, 2); 
            }
            // Heading Level 3 (1.1.1) - Tidak Indent
            elseif (preg_match('/^\d+\.\d+\.\d+\s+/', $cleanLine)) {
                $section->addText($cleanLine, 
                    ['bold'=>true, 'name'=>'Times New Roman', 'size'=>12], 
                    ['spaceBefore'=>120, 'spaceAfter'=>120, 'indentation' => ['firstLine' => 0]]
                );
            }
            else {
                // Paragraf Biasa - Setel indentasi baris pertama (firstLine) 1.27cm (720 twip) secara spesifik di sini
                $pStyle = ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH, 'lineHeight' => 1.5, 'indentation' => ['firstLine' => 720]];
                
                if($line != $cleanLine) {
                     try { 
                         // Menggunakan trik CSS text-indent jika terpaksa menggunakan HTML translator PhpWord
                         $htmlFormatted = "<p style='text-align:justify; text-indent:1.27cm; line-height:1.5; font-family:\"Times New Roman\", serif; font-size:12pt;'>" . $line . "</p>";
                         Html::addHtml($section, $htmlFormatted, false, false); 
                     } 
                     catch(\Exception $e) { $section->addText($cleanLine, [], $pStyle); }
                } else {
                    $section->addText($cleanLine, [], $pStyle);
                }
            }
        }
    }

    private function createPhpWordDocument($project, $request) {
        $phpWord = new PhpWord(); 
        Settings::setOutputEscapingEnabled(true);
        $phpWord->setDefaultFontName('Times New Roman'); 
        $phpWord->setDefaultFontSize(12);

        $marginTop = $request->input('margin_top', 4);
        $marginLeft = $request->input('margin_left', 4);
        $marginBottom = $request->input('margin_bottom', 3);
        $marginRight = $request->input('margin_right', 3);

        $nama = $request->input('nama', '');
        $npm = $request->input('npm', '');
        $kampus = $request->input('kampus', '');
        
        // --- SETTING PARAGRAF GLOBAL ---
        // (Jangan letakkan FirstLine Indent di sini karena akan merusak susunan simetris TOC/Daftar Isi)
        $phpWord->setDefaultParagraphStyle([
            'lineHeight' => 1.5, 
            'spaceAfter' => Converter::pointToTwip(0)
        ]); 
        
        // --- STYLE HEADING (RESET INDENTASI JADI 0) ---
        $phpWord->addTitleStyle(1, ['name'=>'Times New Roman', 'size'=>14, 'bold'=>true], ['alignment'=>Jc::CENTER, 'spaceAfter'=>240, 'indentation' => ['firstLine' => 0]]); 
        $phpWord->addTitleStyle(2, ['name'=>'Times New Roman', 'size'=>12, 'bold'=>true], ['alignment'=>Jc::LEFT, 'spaceBefore'=>120, 'spaceAfter'=>120, 'indentation' => ['firstLine' => 0]]);   
        
        $sectionStyle = [
            'marginTop'=>Converter::cmToTwip($marginTop), 
            'marginLeft'=>Converter::cmToTwip($marginLeft), 
            'marginBottom'=>Converter::cmToTwip($marginBottom), 
            'marginRight'=>Converter::cmToTwip($marginRight)
        ];

        $section = $phpWord->addSection($sectionStyle);
        // Cover Style (Reset Indentasi)
        $center = ['alignment'=>Jc::CENTER, 'indentation' => ['firstLine' => 0]];
        $justify = ['alignment'=>Jc::BOTH];
        $bold14 = ['bold'=>true, 'size'=>14];
        $bold12 = ['bold'=>true, 'size'=>12];
        
        // --- COVER ---
        $section->addText("SKRIPSI", $bold14, $center); 
        $section->addTextBreak(2);
        $section->addText(strtoupper($project->judul), $bold14, $center);
        $section->addTextBreak(3);
        $section->addText("[ LOGO UNIVERSITAS ]", ['size'=>10], $center);
        $section->addTextBreak(3);
        $section->addText("OLEH:", $bold12, $center);
        $section->addText(strtoupper($nama), ['bold'=>true, 'underline'=>'single'], $center);
        $section->addText($npm, $bold12, $center);
        $section->addTextBreak(3);
        $section->addText(strtoupper("PROGRAM STUDI " . $project->prodi), $bold14, $center);
        $section->addText(strtoupper($kampus), $bold14, $center);
        $section->addText(date('Y'), $bold14, $center);
        $section->addPageBreak();

        // --- FRONT MATTER ---
        
        $section->addTitle("LEMBAR PENGESAHAN", 1);
        $section->addTextBreak(1);
        $section->addText("[Tempat Tanda Tangan Dosen]", [], $center);
        $section->addPageBreak();
        
        $section->addTitle("LEMBAR PERSETUJUAN", 1);
        $section->addPageBreak();

        $section->addTitle("ABSTRAK", 1);
        $section->addText("Penelitian ini berjudul \"" . $project->judul . "\".", [], $justify);
        $section->addPageBreak();
        
        $section->addTitle("KATA PENGANTAR", 1);
        $section->addText("Puji syukur kehadirat Tuhan YME...", [], $justify);
        $section->addPageBreak();

        // --- DAFTAR ISI ---
        $section->addText("DAFTAR ISI", $bold14, $center); 
        $section->addTextBreak(1);
        $section->addTOC(['font'=>'Times New Roman', 'size'=>12], ['tabLeader' => TOC::TAB_LEADER_DOT], 1, 2);
        $section->addPageBreak();
        
        // --- DAFTAR GAMBAR & TABEL ---
        $section->addTitle("DAFTAR GAMBAR", 1);
        $section->addText("(Daftar gambar akan terisi otomatis jika ada caption)", ['italic'=>true], $center);
        $section->addPageBreak();
        
        $section->addTitle("DAFTAR TABEL", 1);
        $section->addText("(Daftar tabel akan terisi otomatis jika ada caption)", ['italic'=>true], $center);
        $section->addPageBreak();

        // --- ISI BAB ---
        $chapters = ['bab1'=>'PENDAHULUAN','bab2'=>'TINJAUAN PUSTAKA','bab3'=>'METODOLOGI PENELITIAN','bab4'=>'HASIL DAN PEMBAHASAN','bab5'=>'PENUTUP'];
        $nums = ['bab1'=>'I','bab2'=>'II','bab3'=>'III','bab4'=>'IV','bab5'=>'V'];
        
        foreach ($chapters as $k => $v) {
            if ($htmlContent = $project->{$k . '_content'}) {
                if($k != 'bab1') $section = $phpWord->addSection($sectionStyle);

                $section->addTitle("BAB " . $nums[$k] . " " . strtoupper($v), 1);
                $section->addTextBreak(1);
                
                $this->parseAndAddContent($section, $htmlContent);
                
                if ($k === 'bab2' && !empty($project->bab2_tabel_penelitian)) {
                    $section->addPageBreak();
                    $section->addTitle("Tabel Penelitian Terdahulu", 2);
                    try {
                        Html::addHtml($section, $project->bab2_tabel_penelitian, false, false);
                    } catch (Throwable $e) {
                        $section->addText("Tabel tidak dapat ditampilkan.");
                    }
                }
                $section->addPageBreak();
            }
        }

        // --- DAFTAR PUSTAKA ---
        $section->addTitle("DAFTAR PUSTAKA", 1);
        
        $dpContent = trim($project->daftar_pustaka);
        if(empty($dpContent)) {
             // Jika kosong, beri pesan jelas
             $section->addText("[Daftar Pustaka belum tersedia. Pastikan Bab 1-4 sudah digenerate agar AI bisa mengekstrak sitasi]", [], ['alignment'=>Jc::LEFT, 'indentation'=>['firstLine'=>0]]);
        } else {
            $refs = explode("\n", $dpContent);
            foreach ($refs as $ref) { 
                if(trim($ref)) {
                    // Hanging Indent untuk Daftar Pustaka (Baris kedua menjorok)
                    // Reset FirstLine jadi 0 agar baris pertama mepet kiri
                    $section->addText(trim($ref), [], ['alignment'=>Jc::BOTH, 'indentation' => ['hanging' => 720, 'firstLine' => 0]]); 
                }
            }
        }
        
        return $phpWord;
    }

    public function downloadDocx($id, Request $request) {
        if(ob_get_length()) ob_end_clean();
        $project = Project::where('user_id', Auth::id())->findOrFail($id);
        $phpWord = $this->createPhpWordDocument($project, $request);
        $filename = 'Skripsi_' . str_replace(' ', '_', substr($project->judul, 0, 20)) . '.docx';
       
        // --- FIX KHUSUS VERCEL ---
        
        // 1. Paksa PHPWord pakai folder /tmp untuk proses ZIP
        \PhpOffice\PhpWord\Settings::setTempDir('/tmp');

        // 2. Tentukan lokasi simpan di /tmp (satu-satunya folder yang bisa ditulis)
        $path = '/tmp/' . $filename;

        // 3. Simpan file
        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($path);

        // 4. Download dari /tmp lalu hapus
        return response()->download($path, $filename)->deleteFileAfterSend(true);
        
    }

    public function downloadPdf($id, Request $request) {
        $project = Project::where('user_id', Auth::id())->findOrFail($id);

        $marginTop = $request->input('margin_top', 4);
        $marginLeft = $request->input('margin_left', 4);
        $marginBottom = $request->input('margin_bottom', 3);
        $marginRight = $request->input('margin_right', 3);

        $html = "<html><head><style>body{font-family:'Times New Roman';margin:{$marginTop}cm {$marginRight}cm {$marginBottom}cm {$marginLeft}cm;text-align:justify;line-height:1.5}h1,h2{text-align:center;font-weight:bold}h3,h4{font-weight:bold}table{width:100%;border-collapse:collapse;margin:10px 0}th,td{border:1px solid #000;padding:5px} p { text-indent: 1.27cm; }</style></head><body>";
        $html .= "<h1 style='font-size:14pt'>SKRIPSI<br>".strtoupper($project->judul)."</h1><br><p style='text-align:center;text-indent:0'>OLEH: {$request->nama} ({$request->npm})</p><hr>";
        $chapters = ['bab1'=>'PENDAHULUAN','bab2'=>'TINJAUAN PUSTAKA','bab3'=>'METODOLOGI PENELITIAN','bab4'=>'HASIL DAN PEMBAHASAN','bab5'=>'PENUTUP'];
        $nums = ['bab1'=>'I','bab2'=>'II','bab3'=>'III','bab4'=>'IV','bab5'=>'V'];
        foreach ($chapters as $k=>$v) { 
            if($c = $project->{$k.'_content'}) {
                $html .= "<h2>BAB ".$nums[$k]."<br>$v</h2>" . $c;
                if ($k === 'bab2' && !empty($project->bab2_tabel_penelitian)) { $html .= "<h3>Tabel Penelitian Terdahulu</h3>" . $project->bab2_tabel_penelitian; }
                $html .= "<div style='page-break-after:always;'></div>";
            }
        }
        $html .= "<h2>DAFTAR PUSTAKA</h2><p style='text-indent:0; padding-left:1.27cm; text-indent:-1.27cm'>".nl2br($project->daftar_pustaka)."</p></body></html>";
        $dompdf = new Dompdf(); $dompdf->set_option('isHtml5ParserEnabled', true); $dompdf->loadHtml($html); $dompdf->setPaper('A4'); $dompdf->render();
        return $dompdf->stream('Skripsi.pdf');
    }

    public function refineReferences($id, Request $request)
    {
        $project = Project::where('user_id', Auth::id())->findOrFail($id);
        $model = $request->input('model', 'auto'); 
        
        $allText = strip_tags($project->bab1_content . $project->bab2_content . $project->bab3_content . $project->bab4_content . $project->bab5_content);
        $prompt = "Buatkan DAFTAR PUSTAKA (APA Style) lengkap berdasarkan teks skripsi berikut ini. Ambil semua nama ahli dan tahun yang muncul dalam kurung. JIKA KURANG, tambahkan referensi jurnal nyata yang relevan. Urutkan A-Z:\n\n" . substr($allText, 0, 8000); 
        
        try {
            $daftarPustaka = $this->ai->generateText($prompt, $model);
            $cleanDP = strip_tags($daftarPustaka);
            $cleanDP = str_replace(['*', '#', '_', '`'], '', $cleanDP);
            $project->daftar_pustaka = trim($cleanDP);
            
            $project->save();
            return back()->with('success', 'Daftar pustaka berhasil diperbarui.');
        } catch (Exception $e) { return back()->with('error', 'Gagal update referensi.'); }
    }

    // =========================================================================
    // 6. FITUR TUTOR / SOLVER (BARU)
    // =========================================================================

    public function solveQuestion(Request $request)
    {
        $request->validate([
            'question' => 'required|string',
            'image'    => 'nullable|string', // Base64
            'model'    => 'nullable|string'
        ]);

        $user = Auth::user();
        if ($user->credits < 1) {
            return response()->json(['status' => 'error', 'message' => 'Token habis! Klaim reward dulu.']);
        }

        try {
            $question = $request->question;
            $image = $request->image;
            $model = $request->model; // 'gemini' or 'groq'

            // Prompt Khusus Tutor
            $prompt = "PERAN: Anda adalah Dosen/Guru Ahli.
            TUGAS: Jawab pertanyaan ini dengan langkah-langkah yang jelas, sistematis, dan mudah dimengerti.
            
            PERTANYAAN: {$question}
            
            INSTRUKSI:
            1. Jika ada gambar, analisis detailnya.
            2. Jika soal hitungan (Matematika/Fisika), tulis rumus dengan format LaTeX (contoh: $$ x^2 $$) atau teks biasa yang rapi.
            3. Gunakan Bahasa Indonesia.
            4. Berikan kesimpulan di akhir.";

            // Panggil AI Manager
            // Pastikan AIManagerService punya method generateWithImage (lihat instruksi sebelumnya)
            // Jika belum ada, kita bisa bypass langsung panggil GeminiService khusus untuk gambar
            
            $answer = "";

            if ($image) {
                // Paksa pakai Gemini Vision karena Groq belum support image di library ini
                // Pastikan class AIManagerService atau GeminiService Anda public property-nya, 
                // ATAU gunakan instance baru:
                $geminiService = app(\App\Services\GeminiService::class);
                $answer = $geminiService->generateText($prompt); // Jika text only
                // NOTE: Anda perlu update GeminiService.php seperti panduan sebelumnya untuk support vision (generateVision)
                // Jika generateVision belum ada di GeminiService Anda, gunakan generateText biasa (tapi gambar tidak akan terbaca).
                // ASUMSI: Anda sudah menambahkan generateVision di GeminiService.php sesuai panduan di chat sebelumnya.
                if (method_exists($geminiService, 'generateVision')) {
                    $answer = $geminiService->generateVision($prompt, $image);
                } else {
                    // Fallback text only
                    $answer = $geminiService->generateText($prompt . " [User mencoba kirim gambar tapi sistem belum support vision]");
                }
            } else {
                // Text Only -> Lewat AIManager
                $answer = $this->ai->generateText($prompt, $model);
            }

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
}