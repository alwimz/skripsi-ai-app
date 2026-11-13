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
        $metodeLower = strtolower($project->metode_penelitian ?? '');
        $isRnD = str_contains($metodeLower, 'r&d') || str_contains($metodeLower, 'waterfall') || str_contains($metodeLower, 'agile') || str_contains($metodeLower, 'prototype');
        $isKualitatif = str_contains($metodeLower, 'kualitatif');

        $context = "";
        if ($bab == 'bab2') $context = "KONTEKS BAB 1 (RUMUSAN & TUJUAN): " . Str::limit(strip_tags($project->bab1_content), 1200);
        elseif ($bab == 'bab3') $context = "KONTEKS BAB 1 & 2: " . Str::limit(strip_tags($project->bab1_content . ' ' . $project->bab2_content), 1200);
        elseif ($bab == 'bab4') $context = "KONTEKS RUMUSAN MASALAH & METODOLOGI BAB 3: " . Str::limit(strip_tags($project->bab1_content . ' ' . $project->bab3_content), 1500);
        elseif ($bab == 'bab5') {
            $context = "HASIL PENELITIAN BAB 4: " . Str::limit(strip_tags($project->bab4_content), 1500);
            $allText = $project->bab1_content . " " . $project->bab2_content . " " . $project->bab3_content . " " . $project->bab4_content;
            $context .= "\n\n=== TEKS DARI BAB 1 SAMPAI BAB 4 ===\n" . Str::limit(strip_tags($allText), 15000);
        }

        // SKILL KHUSUS BAB 3 BERDASARKAN METODE PENELITIAN KAMPUS INDONESIA
        $bab3Skill = match (true) {
            $isRnD => "
Sub-bab wajib untuk Metode R&D / Rekayasa Sistem ({$project->metode_penelitian}):
3.1 Metode Pengembangan Sistem
   - Jelaskan tahapan pengembangan sistem secara sistematis sesuai model {$project->metode_penelitian} (Analisis Kebutuhan, Desain Sistem, Implementasi Pengkodean, Pengujian, dan Pemeliharaan).
3.2 Tempat dan Waktu Penelitian
   - Uraikan lokasi objek penelitian dan rincian tahapan waktu pelaksanaan penelitian.
3.3 Teknik Pengumpulan Data
   - Jelaskan teknik Observasi sistem berjalan, Wawancara dengan stakeholder/pengguna, dan Studi Pustaka.
3.4 Analisis Sistem Berjalan dan Kebutuhan Sistem
   - Uraikan identifikasi masalah sistem berjalan (analisis PIECES), Kebutuhan Fungsional (fitur utama sistem), serta Kebutuhan Non-Fungsional (spesifikasi perangkat keras dan perangkat lunak).
3.5 Perancangan Sistem
   - Jelaskan rancangan arsitektur sistem, pemodelan Unified Modeling Language (Use Case Diagram, Activity Diagram, Sequence Diagram, Class Diagram), perancangan struktur basis data (Entity Relationship Diagram / ERD), serta rancangan antarmuka pengguna (User Interface).
3.6 Metode Pengujian Sistem
   - Uraikan skenario pengujian fungsional menggunakan metode Black-Box Testing (Equivalence Partitioning) dan pengujian kelayakan pengguna (User Acceptance Test / System Usability Scale).",

            $isKualitatif => "
Sub-bab wajib untuk Metode Kualitatif ({$project->metode_penelitian}):
3.1 Pendekatan dan Jenis Penelitian
   - Argumentasikan pemilihan pendekatan kualitatif deskriptif/studi kasus untuk membedah fenomena penelitian secara holistik dan mendalam.
3.2 Lokasi dan Waktu Penelitian
   - Paparkan alasan akademik dan empiris pemilihan lokasi penelitian.
3.3 Fokus Penelitian
   - Tetapkan batasan dan indikator fokus penelitian agar eksplorasi lapangan terarah.
3.4 Sumber Data dan Teknik Penentuan Informan
   - Bedakan Data Primer dan Data Sekunder. Jelaskan pemilihan Informan Kunci (Key Informant), Informan Utama, dan Informan Pendukung menggunakan teknik Purposive Sampling atau Snowball Sampling.
3.5 Teknik Pengumpulan Data
   - Uraikan pelaksanaan Observasi Partisipatif, Wawancara Mendalam (In-depth Interview) semi-terstruktur, dan Studi Dokumentasi.
3.6 Teknik Pemeriksaan Keabsahan Data
   - Jelaskan uji kredibilitas data melalui Triangulasi Sumber, Triangulasi Teknik, Triangulasi Waktu, serta Member Check menurut Lincoln & Guba / Sugiyono.
3.7 Teknik Analisis Data
   - Uraikan tahapan analisis model interaktif Miles, Huberman, dan Saldana: Kondensasi Data (Data Condensation), Penyajian Data (Data Display), dan Penarikan Kesimpulan/Verifikasi (Conclusion Drawing).",

            default => "
Sub-bab wajib untuk Metode Kuantitatif ({$project->metode_penelitian}):
3.1 Jenis dan Desain Penelitian
   - Jelaskan pendekatan kuantitatif asosiatif/kausal atau eksperimen beserta rasionalitas ilmiahnya.
3.2 Lokasi dan Waktu Penelitian
   - Uraikan tempat dan jadwal tahapan riset.
3.3 Populasi dan Sampel Penelitian
   - Jelaskan karakteristik populasi target, teknik sampling (Probability/Non-Probability Sampling), dan penentuan ukuran sampel minimum (misalnya menggunakan Rumus Slovin atau Hair et al.).
3.4 Definisi Operasional dan Pengukuran Variabel
   - Jabarkan definisi operasional setiap variabel (Variabel Independen dan Dependen), dimensi, indikator pengukuran, serta penggunaan Skala Likert 1-5.
3.5 Teknik dan Instrumen Pengumpulan Data
   - Uraikan penyusunan kuesioner/angket, studi dokumentasi, dan kisi-kisi instrumen penelitian.
3.6 Uji Instrumen Penelitian
   - Jelaskan prosedur Uji Validitas (Pearson Product Moment r-hitung > r-tabel) dan Uji Reliabilitas (Cronbach's Alpha > 0,70).
3.7 Teknik Analisis Data dan Uji Hipotesis
   - Uraikan tahapan Analisis Statistik Deskriptif, Uji Asumsi Klasik (Uji Normalitas Kolmogorov-Smirnov, Uji Multikolinearitas Tolerance & VIF, Uji Heteroskedastisitas Glejser), Analisis Regresi Linear, Uji Parsial (Uji t), Uji Simultan (Uji F), dan Koefisien Determinasi (R-Square)."
        };

        // SKILL KHUSUS BAB 4 BERDASARKAN METODE PENELITIAN
        $bab4Skill = match (true) {
            $isRnD => "
Sub-bab wajib untuk Hasil & Pembahasan R&D / Sistem ({$project->metode_penelitian}):
4.1 Gambaran Umum Objek Penelitian dan Analisis Sistem Berjalan
   - Deskripsikan profil instansi/objek riset dan alur kerja sistem yang sedang berjalan beserta kendalanya.
4.2 Hasil Perancangan dan Implementasi Sistem
   - Uraikan realisasi perancangan basis data, arsitektur modul, spesifikasi lingkungan implementasi, serta penjelasan detail setiap fitur/halaman utama yang telah dibangun.
4.3 Hasil Pengujian Sistem
   - Sajikan hasil pengujian Black-Box pada setiap modul utama (semua skenario valid) dan hasil rekapitulasi kuesioner pengujian pengguna (User Acceptance Test) dengan persentase kelayakan tinggi.
4.4 Pembahasan Hasil Penelitian
   - Analisis secara kritis bagaimana sistem yang dibangun menjawab rumusan masalah di Bab 1, kelebihan arsitektur sistem, serta perbandingannya dengan penelitian terdahulu di Bab 2. MINIMAL 5 PARAGRAF EKSTENSIF.",

            $isKualitatif => "
Sub-bab wajib untuk Hasil & Pembahasan Kualitatif ({$project->metode_penelitian}):
4.1 Gambaran Umum Lokasi Penelitian dan Karakteristik Informan
   - Deskripsikan konteks sosial/institusional objek penelitian dan profil informan penelitian secara mendalam.
4.2 Paparan Data dan Temuan Penelitian
   - Sajikan temuan lapangan secara naratif untuk setiap fokus penelitian, dilengkapi kutipan wawancara konseptual dari informan kunci dan hasil observasi dokumentasi.
4.3 Pembahasan Temuan Penelitian
   - Lakukan analisis sintesis yang mempertemukan temuan empiris lapangan dengan Landasan Teori (Grand/Middle Theory) dan penelitian terdahulu pada Bab 2. MINIMAL 6 PARAGRAF EKSTENSIF.",

            default => "
Sub-bab wajib untuk Hasil & Pembahasan Kuantitatif ({$project->metode_penelitian}):
4.1 Gambaran Umum Objek Penelitian dan Karakteristik Responden
   - Deskripsikan profil objek penelitian serta distribusi demografis responden (usia, jenis kelamin, tingkat pendidikan/lama bekerja).
4.2 Hasil Analisis Data
   - Sajikan narasi lengkap hasil Analisis Statistik Deskriptif, hasil Uji Validitas dan Reliabilitas instrumen (seluruh item valid dan reliabel dengan Cronbach's Alpha > 0,70), hasil Uji Asumsi Klasik (data berdistribusi normal, bebas multikolinearitas dan heteroskedastisitas), serta hasil persamaan Regresi, Uji t (parsial), Uji F (simultan), dan nilai Koefisien Determinasi (R2).
4.3 Pembahasan Hasil Penelitian
   - Interpretasikan secara mendalam makna statistik dari setiap hipotesis yang diuji. Jelaskan mekanisme kausalitas mengapa variabel independen berpengaruh signifikan terhadap variabel dependen, lalu bandingkan kesesuaiannya dengan teori para ahli dan jurnal penelitian terdahulu di Bab 2. MINIMAL 6 PARAGRAF EKSTENSIF."
        };

        return match ($bab) {
            'bab1' => $master . "
TUGAS: Tulis Isi BAB I (PENDAHULUAN) secara komprehensif sesuai pedoman skripsi universitas di Indonesia (DIKTI). LANGSUNG DIMULAI DARI SUB-BAB PERTAMA (Tanpa judul Bab besar).

Sub-bab wajib:
1.1 Latar Belakang Masalah
   - Gunakan struktur Piramida Terbalik 5 lapis:
     (1) Konteks makro/perkembangan global dan regulasi nasional terkait topik;
     (2) Kondisi ideal (Das Sollen) menurut teori dan standar keilmuan;
     (3) Fenomena/masalah aktual di lapangan (Das Sein) beserta bukti empiris dan kutipan jurnal 5 tahun terakhir;
     (4) Research Gap (kesenjangan temuan dari penelitian-penelitian terdahulu);
     (5) Urgensi penelitian dan alasan pemilihan judul '{$project->judul}'. MINIMAL 5-6 PARAGRAF PANJANG.
1.2 Identifikasi dan Batasan Masalah
   - Rincikan permasalahan yang teridentifikasi di lapangan lalu tetapkan batasan masalah agar ruang lingkup penelitian terfokus.
1.3 Rumusan Masalah
   - Susun 3 pertanyaan penelitian akademis yang tajam, terukur, dan selaras dengan variabel judul (poin 1., 2., 3.).
1.4 Tujuan Penelitian
   - Susun kalimat afirmatif yang menjawab secara presisi ketiga poin pada Rumusan Masalah.
1.5 Manfaat Penelitian
   - Uraikan Manfaat Teoretis (kontribusi bagi pengembangan ilmu di prodi {$project->prodi}) dan Manfaat Praktis (bagi objek penelitian, praktisi, dan peneliti selanjutnya).
1.6 Sistematika Penulisan
   - Jelaskan rincian isi Bab I Pendahuluan, Bab II Tinjauan Pustaka, Bab III Metodologi Penelitian, Bab IV Hasil dan Pembahasan, hingga Bab V Penutup.
",
            'bab2' => $master . "
$context
TUGAS: Tulis Isi BAB II (TINJAUAN PUSTAKA) secara komprehensif sesuai standar skripsi Indonesia.

Sub-bab wajib:
2.1 Landasan Teori
   - Bedah Grand Theory, Middle Range Theory, dan Applied Theory untuk seluruh variabel/konsep dalam judul '{$project->judul}'. Jabarkan pengertian menurut minimal 4-5 ahli (sertakan sitasi tahun), dimensi, indikator, serta faktor-faktor yang memengaruhi setiap variabel secara mendalam. MINIMAL 7-8 PARAGRAF EKSTENSIF.
2.2 Penelitian Terdahulu
   - Bahas minimal 5 penelitian jurnal terdahulu (rentang 2020-2025) secara naratif: sebutkan Nama Peneliti (Tahun), judul/fokus topik, metode yang dipakai, hasil temuan utama, serta persamaan dan perbedaan (letak kebaruan / state of the art) dengan penelitian ini.
2.3 Kerangka Pemikiran
   - Jelaskan alur logika teoretis yang menghubungkan antar-variabel atau tahapan pemecahan masalah dari awal hingga akhir secara sistematis.
2.4 Hipotesis Penelitian / Proposisi Penelitian
   - Rumuskan hipotesis penelitian (H0 dan Ha) secara jelas jika kuantitatif/eksperimen, atau fokus proposisi kerja jika kualitatif/R&D.
",
            'bab3' => $master . "
$context
TUGAS: Tulis Isi BAB III (METODOLOGI PENELITIAN) yang presisi dan sesuai pedoman skripsi Indonesia untuk metode: {$project->metode_penelitian}.
$bab3Skill
",
            'bab4' => $master . "
$context
TUGAS: Tulis Isi BAB IV (HASIL PENELITIAN DAN PEMBAHASAN) secara mendalam, akademis, dan selaras dengan rumusan masalah serta metode {$project->metode_penelitian}.
$bab4Skill
",
            'bab5' => $master . "
$context
TUGAS: Tulis Isi BAB V (PENUTUP) yang koheren dengan Bab 1 hingga Bab 4.

Sub-bab wajib:
5.1 Kesimpulan
   - Jawab secara tegas, lugas, dan sistematis setiap poin Rumusan Masalah di Bab 1 berdasarkan sintesis hasil temuan di Bab 4.
5.2 Saran
   - Berikan rekomendasi aplikatif yang konkret bagi instansi/objek penelitian dan saran metodologis bagi peneliti selanjutnya.

INSTRUKSI KHUSUS DAFTAR PUSTAKA:
1. Selesaikan seluruh sub-bab Saran.
2. BUAT BARIS BARU DENGAN TULISAN EXACT BERIKUT:
===DAFTAR PUSTAKA===
3. Di bawah pemisah tersebut, susun minimal 15-20 REFERENSI ilmiah dengan format APA Style 7th Edition yang baku.
4. SUMBER REFERENSI: Wajib mencakup SELURUH nama penulis dan tahun sitasi yang muncul di dalam teks Bab 1 hingga Bab 4, ditambah buku metodologi penelitian standar Indonesia (seperti Sugiyono, Creswell, Arikunto, atau Pressman) dan jurnal bereputasi sesuai bidang '{$project->prodi}'.
5. Urutkan secara alfabetis (A-Z).
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

    // =========================================================================
    // 7. FITUR BARU: GENERATOR PROPOSAL, PARAFRASE, PPT SIDANG, SIMULASI SIDANG
    // =========================================================================

    public function generateProposal(Request $request)
    {
        $user = Auth::user();
        if ($user->credits < 1) {
            return response()->json(['status' => 'error', 'message' => 'Token habis! Klaim reward harian terlebih dahulu.']);
        }

        $request->validate([
            'judul' => 'required|string',
            'prodi' => 'required|string',
            'metode' => 'required|string',
            'masalah' => 'nullable|string',
        ]);

        $model = $request->input('model', 'auto');

        $prompt = "PERAN: Anda adalah Profesor Pembimbing Skripsi Senior di Universitas Indonesia.
TUGAS: Susun Draf PROPOSAL SKRIPSI (BAB I, BAB II, dan BAB III) yang sangat terstruktur, mendalam, dan sesuai standar buku pedoman skripsi perguruan tinggi Indonesia (DIKTI, KBBI, EYD V, APA 7th Edition).

DETAIL PENELITIAN:
- Judul Proposal: {$request->judul}
- Program Studi: {$request->prodi}
- Metode Penelitian: {$request->metode}
- Fenomena / Masalah Utama: " . ($request->masalah ?: 'Sesuaikan secara empiris dengan judul penelitian') . "

STRUKTUR WAJIB YANG HARUS DITULIS LENGKAP:

## BAB I PENDAHULUAN
### 1.1 Latar Belakang Masalah
(Tulis minimal 4 paragraf mendalam dengan pendekatan Piramida Terbalik: Fenomena Makro -> Das Sollen vs Das Sein -> Kutipan Jurnal 2020-2025 -> Research Gap -> Urgensi Judul)
### 1.2 Identifikasi dan Batasan Masalah
### 1.3 Rumusan Masalah (3 Pertanyaan Penelitian)
### 1.4 Tujuan Penelitian
### 1.5 Manfaat Penelitian (Teoretis & Praktis)

## BAB II TINJAUAN PUSTAKA
### 2.1 Landasan Teori (Grand Theory, Middle Theory, & Indikator Variabel menurut para ahli)
### 2.2 Penelitian Terdahulu (3-5 Jurnal Relevan beserta letak State of the Art / kebaruan)
### 2.3 Kerangka Pemikiran
### 2.4 Hipotesis / Proposisi Penelitian

## BAB III METODOLOGI PENELITIAN
### 3.1 Pendekatan dan Desain Penelitian ({$request->metode})
### 3.2 Objek, Populasi/Sampel atau Subjek/Informan Penelitian
### 3.3 Teknik Pengumpulan Data & Instrumen
### 3.4 Teknik Analisis Data & Pengujian

## DAFTAR PUSTAKA AWAL (APA Style 7th Edition - Minimal 8 Referensi)

INSTRUKSI: Gunakan bahasa Indonesia ilmiah yang baku dan kohesif. Jangan beri kalimat pembuka basa-basi AI.";

        try {
            $proposal = $this->ai->generateText($prompt, $model);
            $user->decrement('credits');

            return response()->json([
                'status' => 'success',
                'proposal' => $proposal,
                'new_credits' => $user->credits
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function paraphraseText(Request $request)
    {
        $user = Auth::user();
        if ($user->credits < 1) {
            return response()->json(['status' => 'error', 'message' => 'Token habis! Klaim reward harian terlebih dahulu.']);
        }

        $request->validate([
            'text' => 'required|string|min:20',
            'style' => 'nullable|string',
        ]);

        $style = $request->input('style', 'academic');
        $model = $request->input('model', 'auto');

        $styleInstruction = match ($style) {
            'anti_turnitin' => 'Ubah struktur kalimat aktif/pasif dan gunakan sinonim akademik tingkat lanjut agar lolos uji kemiripan Turnitin (<10% similarity) tanpa mengubah makna ilmiah maupun sitasi dalam kurung.',
            'concise' => 'Padatkan dan ringkas kalimat agar lebih lugas, efektif, dan tidak bertele-tele sesuai kaidah EYD V dan KBBI.',
            'expand' => 'Perluas dan perdalam penjelasan akademik dengan menambahkan elaborasi logis dan kohesi antar-kalimat yang elegan.',
            default => 'Tulis ulang dengan gaya Bahasa Indonesia Ilmiah Baku (Standar Skripsi/Jurnal SINTA) yang mengalir alami, koheren, dan bebas plagiarisme.'
        };

        $prompt = "PERAN: Anda adalah Editor Bahasa Ilmiah & Pakar Parafrase Akademik (KBBI & EYD V).
TUGAS: Lakukan parafrase pada teks akademik berikut.
GAYA PARAFRASE: {$styleInstruction}

ATURAN KETAT:
1. Pertahankan seluruh sitasi nama penulis dan tahun (misal: Menurut Sugiyono (2022) atau (Wijaya, 2023)) agar tidak hilang.
2. Jangan tambahkan kata pengantar seperti 'Berikut hasil parafrasenya:'. Langsung berikan teks hasil parafrase saja.

TEKS ASLI:
\"\"\"
{$request->text}
\"\"\"

HASIL PARAFRASE:";

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
            return response()->json(['status' => 'error', 'message' => 'Token habis! Klaim reward harian terlebih dahulu.']);
        }

        $request->validate([
            'judul' => 'required|string',
            'prodi' => 'nullable|string',
            'metode' => 'nullable|string',
            'ringkasan' => 'nullable|string',
        ]);

        $model = $request->input('model', 'auto');

        $prompt = "PERAN: Anda adalah Konsultan Presentasi Sidang Skripsi Akademik.
TUGAS: Buatkan struktur materi SLIDE PPT SIDANG SKRIPSI (9 Slide Lengkap) beserta NASKAH BICARA PRESENTER (Speaker Notes) yang memukau Dosen Penguji.

DATA SKRIPSI:
- Judul: {$request->judul}
- Program Studi: " . ($request->prodi ?: 'Umum') . "
- Metode Penelitian: " . ($request->metode ?: 'Ilmiah') . "
- Ringkasan / Poin Tambahan: " . ($request->ringkasan ?: 'Sesuaikan secara logis dengan judul') . "

FORMAT OUTPUT WAJIB (Gunakan Markdown rapi per Slide):
Buat tepat 9 Slide:
- **Slide 1: Cover & Identitas Penelitian**
- **Slide 2: Latar Belakang & Urgensi Masalah (Fenomena & Data)**
- **Slide 3: Research Gap & Rumusan Masalah**
- **Slide 4: Tujuan & Manfaat Penelitian**
- **Slide 5: Landasan Teori & Kerangka Pemikiran**
- **Slide 6: Penelitian Terdahulu (State of the Art)**
- **Slide 7: Metodologi Penelitian & Tahapan Analisis**
- **Slide 8: Hasil Penelitian & Pembahasan Utama**
- **Slide 9: Kesimpulan, Saran & Penutup**

Pada SETIAP Slide, wajib tuliskan:
1. **Poin Visual Slide** (3-4 bullet pendek & padat untuk ditampilkan di layar PPT)
2. **🎙️ Naskah Bicara (Script Presentasi):** Kalimat yang diucapkan mahasiswa saat menjelaskan slide tersebut di depan dosen penguji.";

        try {
            $slides = $this->ai->generateText($prompt, $model);
            $user->decrement('credits');

            return response()->json([
                'status' => 'success',
                'slides' => $slides,
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
            return response()->json(['status' => 'error', 'message' => 'Token habis! Klaim reward harian terlebih dahulu.']);
        }

        $request->validate([
            'judul' => 'required|string',
            'metode' => 'nullable|string',
            'mode' => 'required|in:start,answer',
            'question' => 'nullable|string',
            'answer' => 'nullable|string',
        ]);

        $model = $request->input('model', 'auto');

        if ($request->mode === 'start') {
            $prompt = "PERAN: Anda adalah Ketua Dewan Dosen Penguji Sidang Skripsi S1 yang kritis, objektif, namun konstruktif.
KONTEKS MAHASISWA:
- Judul Skripsi: {$request->judul}
- Metode Penelitian: " . ($request->metode ?: 'Sesuai judul') . "

TUGAS:
1. Sambut mahasiswa di ruang sidang secara singkat dan berwibawa.
2. Ajukan 1 PERTANYAAN PENGUJI YANG PALING KRITIS & SERING DITANYAKAN saat sidang skripsi terkait urgensi pemilihan judul, fenomena masalah (research gap), atau ketepatan metode penelitian di atas.
3. Berikan juga *Hint / Kisi-kisi singkat* di bagian bawah tentang poin apa yang sebaiknya mahasiswa masukkan dalam jawabannya.";
        } else {
            $prompt = "PERAN: Anda adalah Dewan Dosen Penguji Sidang Skripsi S1.
KONTEKS SKRIPSI:
- Judul: {$request->judul}
- Metode: " . ($request->metode ?: 'Sesuai judul') . "
- Pertanyaan Dosen Sebelumnya: \"{$request->question}\"
- Jawaban Mahasiswa: \"{$request->answer}\"

TUGAS EVALUASI SIDANG:
1. **🎯 Skor Jawaban:** Berikan nilai (0-100) untuk kualitas argumen jawaban mahasiswa tersebut.
2. **📋 Evaluasi Dosen Penguji:** Berikan ulasan apa yang sudah bagus dari jawaban mahasiswa dan bagian mana yang masih kurang tajam/kurang landasan ilmiah.
3. **💡 Contoh Jawaban Terbaik (Standar Nilai A):** Tuliskan contoh kalimat jawaban lisan yang paling ideal dan meyakinkan untuk menjawab pertanyaan tadi.
4. **❓ Pertanyaan Penguji Berikutnya:** Ajukan 1 pertanyaan lanjutan baru yang menguji penguasaan Bab 2 (Teori/Variabel), Bab 3 (Instrumen/Metode), atau Bab 4 (Hasil & Implikasi).";
        }

        try {
            $reply = $this->ai->generateText($prompt, $model);
            $user->decrement('credits');

            return response()->json([
                'status' => 'success',
                'reply' => $reply,
                'new_credits' => $user->credits
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}