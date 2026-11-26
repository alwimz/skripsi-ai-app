<?php

declare(strict_types=1);

/**
 * Create an isolated SQLite copy for local demo captures logged in as "Winda".
 * Never modifies the workspace's live database.sqlite.
 */

$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';

$sourceDb = $root . '/database/database.sqlite';
$targetDb = '/tmp/skripsilab_promo_db.sqlite';

if (!file_exists($sourceDb)) {
    fwrite(STDERR, "Source database not found: {$sourceDb}\n");
    exit(1);
}

copy($sourceDb, $targetDb);

putenv("DB_CONNECTION=sqlite");
putenv("DB_DATABASE={$targetDb}");
$_ENV['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = $targetDb;
$_SERVER['DB_CONNECTION'] = 'sqlite';
$_SERVER['DB_DATABASE'] = $targetDb;

$app = require $root . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

$email = 'winda@skripsilabai.com';
$password = 'PromoDemo2026!';

$user = User::where('email', $email)->first();
if (!$user) {
    $user = new User();
    $user->email = $email;
}
$user->name = 'Winda';
$user->password = Hash::make($password);
$user->plan_code = 'unlimited';
$user->plan_expires_at = now()->addYear();
$user->project_quota_limit = 50;
$user->project_quota_used = 1;
$user->free_project_used = 1;
$user->credits = 99;
$user->save();

// Load default university emblem if present
$emblemPath = $root . '/public/images/default_emblem.png';
$logoBase64 = file_exists($emblemPath)
    ? 'data:image/png;base64,' . base64_encode((string) file_get_contents($emblemPath))
    : '';

// Remove stale demo project 901 in temp DB if present, then seed fresh realistic project
Project::where('id', 901)->delete();

$meta = [
    'nama' => 'Winda Putri',
    'nim' => '2210511042',
    'kampus' => 'Universitas Negeri Jakarta',
    'fakultas' => 'Fakultas Ilmu Komputer',
    'citation_style' => 'apa',
    'nama_pembimbing_1' => 'Dr. Hendra Kusuma, M.Kom.',
    'nama_pembimbing_2' => 'Siti Aminah, M.T.',
];
if ($logoBase64 !== '') {
    $meta['logo_base64'] = $logoBase64;
}

$bab1 = <<<'MD'
1.1 Latar Belakang Masalah

Perkembangan media sosial berbasis video pendek seperti TikTok telah mendorong munculnya jutaan opini publik setiap harinya mengenai berbagai kebijakan maupun produk digital (Putra & Wibowo, 2023). Volume komentar yang sangat besar membuat proses pemetaan sentimen secara manual menjadi lambat, subjektif, dan rentan terhadap ketidakkonsistenan hasil analisis.

Penelitian terdahulu oleh Rahmawati et al. (2022) menunjukkan bahwa kombinasi metode pembobotan kata TF-IDF dengan algoritma klasifikasi Naive Bayes mampu memberikan akurasi yang stabil pada teks berbahasa Indonesia. Namun, tingginya penggunaan bahasa tidak baku, singkatan, dan kata gaul pada kolom komentar TikTok masih menjadi tantangan utama yang perlu ditangani melalui tahapan prapemrosesan teks yang terstruktur (Sari & Pratama, 2024).

1.2 Identifikasi Masalah

Berdasarkan latar belakang di atas, diidentifikasi beberapa permasalahan utama sebagai berikut:
1. Tingginya jumlah komentar harian pengguna TikTok sulit dianalisis sentimennya secara manual dengan cepat dan objektif.
2. Banyaknya variasi kata tidak baku dan noise karakter pada komentar media sosial dapat menurunkan performa klasifikasi model.
3. Belum tersedia evaluasi perbandingan performa kombinasi TF-IDF n-gram dengan Multinomial Naive Bayes pada dataset komentar berbahasa Indonesia terbaru.

1.3 Rumusan Masalah

1. Bagaimana merancang alur prapemrosesan teks untuk menormalisasi komentar berbahasa Indonesia pada platform TikTok?
2. Bagaimana penerapan pembobotan TF-IDF dan algoritma Multinomial Naive Bayes dalam mengklasifikasikan sentimen positif, negatif, dan netral?
3. Berapa tingkat akurasi, precision, recall, dan F1-score yang dihasilkan oleh model pada dataset uji komentar TikTok?

1.4 Tujuan Penelitian

1. Membangun pipeline prapemrosesan komentar TikTok berbahasa Indonesia mencakup case folding, cleaning, normalisasi kata tidak baku, stopword removal, dan stemming.
2. Mengimplementasikan model klasifikasi sentimen menggunakan pembobotan TF-IDF dan algoritma Multinomial Naive Bayes.
3. Mengevaluasi kinerja model menggunakan confusion matrix dengan metrik akurasi, precision, recall, dan F1-score.
MD;

$bab2 = <<<'MD'
2.1 Landasan Teori

Analisis sentimen atau opinion mining merupakan bidang Natural Language Processing (NLP) yang bertujuan mengekstraksi orientasi opini dari teks tidak terstruktur ke dalam kelas sentimen terukur (Suryani & Hidayat, 2023). Metode Term Frequency–Inverse Document Frequency (TF-IDF) digunakan untuk merepresentasikan bobot kepentingan kata relatif terhadap keseluruhan dokumen dalam korpus penelitian.

Multinomial Naive Bayes bekerja berdasarkan teorema Bayes dengan asumsi independensi fitur antar kata sehingga sangat efisien secara komputasi dan cocok untuk klasifikasi teks berdimensi tinggi (Kusuma & Dewanto, 2024).

2.2 Penelitian Terdahulu

| Peneliti & Tahun | Judul Penelitian | Metode | Hasil Utama |
|---|---|---|---|
| Rahmawati et al. (2022) | Analisis Sentimen Media Sosial Berbasis Teks | TF-IDF + Naive Bayes | Akurasi 86,20% pada 3.000 komentar |
| Putra & Wibowo (2023) | Klasifikasi Opini Pengguna Aplikasi Digital | Naive Bayes + SMOTE | F1-Score meningkat menjadi 88,10% |
| Sari & Pratama (2024) | Normalisasi Teks Slang Bahasa Indonesia | Kamus Slang + Stemming | Peningkatan akurasi sebesar +4,35% |
MD;

$bab3 = <<<'MD'
3.1 Desain dan Tahapan Penelitian

Penelitian ini menggunakan pendekatan kuantitatif eksperimental dengan tahapan pengumpulan data komentar TikTok sebanyak 5.000 entri, pelabelan tiga kelas sentimen (positif, negatif, netral), prapemrosesan teks, ekstraksi fitur TF-IDF, pelatihan model Multinomial Naive Bayes, serta evaluasi menggunakan 10-Fold Cross Validation.

3.2 Instrumen dan Pengujian Model

Pengujian dilakukan menggunakan bahasa pemrograman Python dengan pustaka Scikit-Learn. Pembagian data menggunakan skema rasio 80% data latih dan 20% data uji serta diukur menggunakan confusion matrix.
MD;

$bab4 = <<<'MD'
4.1 Hasil Pengujian Model Naive Bayes & TF-IDF

Pengujian dilakukan terhadap 5.000 data komentar TikTok berbahasa Indonesia yang telah melewati tahapan normalisasi kata gaul dan pembobotan TF-IDF unigram-bigram. Pengujian validasi silang 10-Fold Cross Validation menunjukkan performa model yang sangat konsisten.

| Skenario Pengujian | Akurasi | Precision | Recall | F1-Score |
|---|---|---|---|---|
| Baseline (Tanpa Normalisasi) | 82,40% | 81,90% | 81,50% | 81,70% |
| TF-IDF Unigram + Naive Bayes | 87,25% | 87,10% | 86,80% | 86,95% |
| TF-IDF Unigram-Bigram + Normalisasi Slang | 89,40% | 89,15% | 88,90% | 89,02% |

4.2 Pembahasan

Penambahan tahap normalisasi kata gaul sebelum ekstraksi TF-IDF terbukti meningkatkan akurasi model sebesar 7,00% dibanding skenario baseline karena kata-kata tidak baku berhasil dipetakan ke bentuk kata dasar yang seragam.
MD;

$bab5 = <<<'MD'
5.1 Kesimpulan

1. Pipeline prapemrosesan dengan kamus normalisasi slang berhasil membersihkan noise komentar TikTok secara signifikan.
2. Kombinasi TF-IDF Unigram-Bigram dan algoritma Multinomial Naive Bayes menghasilkan performa terbaik dengan akurasi 89,40%, precision 89,15%, recall 88,90%, dan F1-score 89,02%.

5.2 Saran

Penelitian selanjutnya disarankan membandingkan model transformer IndoBERT pada dataset berskala lebih besar serta melakukan analisis aspek berbasis topik komentar.
MD;

$dp = <<<'MD'
Kusuma, A., & Dewanto, R. (2024). Evaluasi algoritma probabilistik untuk klasifikasi teks berbahasa Indonesia. Jurnal Teknologi Informasi dan Ilmu Komputer, 11(2), 145–156. https://doi.org/10.25126/jtiik.2024117892

Putra, B. H., & Wibowo, A. (2023). Klasifikasi opini pengguna media sosial menggunakan pendekatan TF-IDF dan Naive Bayes. Jurnal Sistem Informasi Bisnis, 13(1), 44–52. https://doi.org/10.21456/vol13iss1pp44-52

Rahmawati, D., Firdaus, M., & Prakoso, T. (2022). Analisis sentimen komentar publik terhadap layanan digital nasional. Jurnal Nasional Teknik Elektro dan Teknologi Informasi, 11(4), 298–306. https://doi.org/10.22146/jnteti.v11i4.4120

Sari, N. P., & Pratama, Y. (2024). Normalisasi kata tidak baku pada data komentar TikTok berbahasa Indonesia. Jurnal Rekayasa Sistem dan Teknologi Informasi, 8(1), 89–98. https://doi.org/10.29207/resti.v8i1.5432

Suryani, L., & Hidayat, R. (2023). Natural language processing untuk pemetaan sentimen publik. Penerbit Informatika Nusantara.
MD;

DB::table('projects')->insert([
    'id' => 901,
    'user_id' => $user->id,
    'judul' => 'Klasifikasi Sentimen Komentar Pengguna TikTok Menggunakan Algoritma Naive Bayes dan TF-IDF',
    'prodi' => 'Teknik Informatika',
    'metode_penelitian' => 'Kuantitatif',
    'variabel_penelitian' => 'Komentar TikTok, TF-IDF Unigram-Bigram, Multinomial Naive Bayes, Akurasi Sentimen',
    'sumber_data' => '5.000 Data Komentar Pengguna TikTok',
    'bab1_content' => $bab1,
    'bab2_content' => $bab2,
    'bab3_content' => $bab3,
    'bab4_content' => $bab4,
    'bab5_content' => $bab5,
    'daftar_pustaka' => $dp,
    'citation_validation' => json_encode($meta, JSON_UNESCAPED_UNICODE),
    'status' => 'completed',
    'is_full_unlocked' => 1,
    'created_at' => now(),
    'updated_at' => now(),
]);

// Assign existing saved PPTs to Winda in temp DB for authentic PPT preview capture
DB::table('saved_ppts')->update(['user_id' => $user->id]);

echo json_encode([
    'status' => 'ok',
    'db_path' => $targetDb,
    'email' => $email,
    'password' => $password,
    'user_id' => $user->id,
    'user_name' => $user->name,
    'project_id' => 901,
], JSON_UNESCAPED_UNICODE) . "\n";
