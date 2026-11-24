# Pedoman Standar Penulisan Proposal & Skripsi S1 Perguruan Tinggi Indonesia
Terkurasi komprehensif mengacu pada buku pedoman skripsi perguruan tinggi nasional (PTN/PTS) serta repositori riset akademik open-source (`FRCHN-GSTN/MenulisCep4t`, `apissaj/humanizer-id`, `nothinx/naskah-id`, `santifs/thesis-writing-skill`).

---

## 1. Kaidah Bahasa Ilmiah Indonesia & Anti-Deteksi AI (Humanizer & Rendah Plagiat)
1. **DILARANG KERAS** menggunakan frasa klise pembuka robotik khas AI:
   - ❌ "Di era digital / era globalisasi / modernisasi saat ini..."
   - ❌ "Kondisi ideal dalam..." atau "Pada hakikatnya..."
   - ❌ "Perkembangan teknologi yang pesat telah mengubah berbagai aspek kehidupan..."
   - ❌ "Tidak dapat dipungkiri bahwa..." atau "Memegang peranan yang sangat krusial..."
   - ❌ "Merupakan tonggak penting dalam..." atau "Hal ini tentu saja sangat berdampak..."
   - ❌ "Sebagai kesimpulan, dapat dikatakan bahwa..." atau "Berdasarkan uraian di atas..."
2. **Karakter Bahasa Akademik Alami (Human-Written Style)**:
   - **Langsung menukik ke substansi**: Kalimat pertama paragraf wajib langsung memuat fakta empiris, data kuantitatif, atau konsep spesifik yang menjadi fokus penelitian.
   - **Suara Pasif Ilmiah Baku (Passive Academic Voice)**: Khusus pada metodologi dan pembahasan temuan, gunakan kalimat pasif ilmiah (contoh: *"Data dikumpulkan melalui instrumen penelitian terhadap seluruh sampel..."*, *"Analisis data dilakukan untuk menguji signifikansi hipotesis..."*), hindari penggunaan kata ganti orang pertama (*saya, penulis, kami*).
   - **Variasi Sintaksis & Ritme Kalimat (Burstiness & Perplexity)**: Kombinasikan kalimat pendek dan tegas (8-14 kata) untuk penegasan temuan dengan kalimat majemuk eksplanatif (18-28 kata) untuk elaborasi teoretis. Hindari struktur kalimat yang berulang secara monoton di awal paragraf (hindari membuka beberapa paragraf berturut-turut dengan *"Selain itu..."* atau *"Hal ini..."*).
   - **Pola Penalaran Deduktif Ilmiah**: Setiap paragraf wajib memuat alur *Claim* (pernyataan gagasan pokok) – *Evidence* (data observasi, angka statistik, atau rujukan sitasi) – *Reasoning* (analisis logis mengapa data tersebut mendukung judul penelitian).
   - **Konjungsi Antarkalimat Ilmiah Baku**: *Kendati demikian*, *Oleh karena itu*, *Konsekuensinya*, *Sebaliknya*, *Dalam konteks empiris*, *Secara konseptual*, *Sejalan dengan hal tersebut*.

---

## 2. Standar Notasi Matematika & Rumus Statistik (Bebas Kode LaTeX Mentah)
1. **DILARANG KERAS** menghasilkan kode sintaks LaTeX mentah seperti `\frac{}{}`, `\hat{}`, `{,}`, `\approx`, atau pembungkus tanda dollar `$ ... $` maupun `$$ ... $$` di dalam naskah.
2. Gunakan format teks akademik bersih berbasis karakter Unicode standar:
   - **Notasi Pecahan**: Gunakan pembagian garis miring (`/`), contoh:
     `n = N / (1 + N(e)²)` (sesuaikan nilai `N`, `e`, dan hasil `n` sepenuhnya dengan topik & data riil proyek)
   - **Simbol Prediksi / Regresi**: Gunakan karakter topi Unicode (`Ŷ` atau `Ŷ`), contoh:
     `Ŷ = a + bX` atau `Ŷ = a + b₁X₁ + b₂X₂`
   - **Pangkat / Eksponen**: Gunakan karakter superskrip Unicode (`²`, `³`, `ᵗ`), contoh:
     `R²`, `e² = (0,05)² = 0,0025`
   - **Angka Desimal Indonesia**: Gunakan koma baku Indonesia untuk seluruh pecahan desimal sesuai konteks riset (bukan `{,}` atau titik desimal ala format asing jika menulis dalam bahasa Indonesia).
   - **Simbol Operator Standar**: Gunakan `×` (bukan `\times`), `≈` (bukan `\approx`), `≤` (bukan `\le`), `≥` (bukan `\ge`), `±` (bukan `\pm`), `∑` (bukan `\sum`).

---

## 3. Konsistensi Benang Merah Lintas Bab (Red Thread Matrix)
1. **Rumusan Masalah (Bab I)** ⇄ **Analisis Data (Bab IV)** ⇄ **Kesimpulan (Bab V)**:
   - Jika terdapat 3 rumusan masalah pada Sub-bab 1.4, maka Sub-bab 4.3/4.4 wajib memuat pembahasan ketiga butir tersebut, dan Sub-bab 5.1 Kesimpulan wajib memuat tepat 3 butir jawaban konklusif yang memuat angka-angka metrik capaian nyata (koefisien regresi, akurasi, p-value, dll.).
2. **Definisi Variabel (Bab II & III)** ⇄ **Tabel & Hasil Olah Data (Bab IV)**:
   - Nama variabel, dimensi, dan indikator pada Tabel Operasionalisasi Variabel (Bab III) wajib sama persis dengan variabel pada tabel statistik deskriptif dan tabel uji hipotesis di Bab IV.

---

## 4. Karakteristik & Standar Spesifik Berdasarkan Program Studi / Rumpun Ilmu

### A. Rumpun Informatika, Sistem Informasi, & Ilmu Komputer (Teknik/TI)
- **Tabel Wajib di Bab II**: Tabel Sintesis Penelitian Terdahulu (Kolom: No | Peneliti & Tahun | Judul Penelitian | Metode/Algoritma | Hasil/Akurasi | Persamaan, Perbedaan & Research Gap).
- **Bab III (Metodologi)**:
  - Uraikan arsitektur sistem dan alur alur komputasi secara terperinci.
  - Tahapan pra-pemrosesan data (*crawling/scraping*, *data cleaning*, *case folding*, *tokenizing*, *slang normalization*, *stopword removal*, *stemming Sastrawi*).
  - Pembobotan fitur (TF-IDF / Word2Vec) dan skema pembagian data (*Data Splitting* 80:20 atau *K-Fold Cross Validation*).
  - Algoritma spesifik yang dikaji beserta formulasi matematis dasarnya dalam teks bersih.
  - Metrik evaluasi kinerja sistem (*Confusion Matrix*: *Accuracy*, *Precision*, *Recall*, *F1-Score*).
- **Bab IV (Hasil & Pembahasan)**:
  - Diagram alur prosedur / flowchart dalam blok kode bersih (`Gambar 3.1 Flowchart Sistem`).
  - Tabel pembagian dataset (*training* vs *testing*).
  - Tabel contoh hasil setiap tahapan text preprocessing.
  - Tabel *Confusion Matrix* dan tabel perbandingan metrik evaluasi.
  - Analisis kritis penyebab kesalahan klasifikasi (*error analysis* / *false positive & false negative*).

### B. Rumpun Ekonomi, Manajemen, Akuntansi, & Bisnis
- **Tabel Wajib di Bab III**: Tabel Operasionalisasi Variabel (Variabel | Definisi Operasional | Dimensi | Indikator Empiris | Skala Pengukuran Likert 1-5).
- **Bab III (Metode)**: Populasi dan teknik penentuan sampel (rumus Slovin / Purposive Sampling), uji instrumen (validitas Pearson Product Moment & reliabilitas Cronbach's Alpha), uji asumsi klasik (normalitas Kolmogorov-Smirnov, multikolinearitas VIF & Tolerance, heteroskedastisitas Glejser), serta teknik analisis (Regresi Linier Berganda / Sederhana / SEM-PLS).
- **Bab IV (Hasil & Pembahasan)**:
  - Tabel karakteristik/demografi responden (Jenis Kelamin, Usia, Pendidikan, Pengeluaran).
  - Tabel statistik deskriptif variabel (N, Minimum, Maximum, Mean, Std. Deviation).
  - Tabel uji validitas dan reliabilitas.
  - Tabel uji asumsi klasik lengkap.
  - Tabel ringkasan regresi (R, R², Uji F ANOVA, dan Uji t parsial dengan koefisien B, Std. Error, t-hitung, dan nilai Sig.).
  - Pembahasan kritis penerimaan/penolakan hipotesis dan implikasi manajerial konkret.

### C. Rumpun Pendidikan & Keguruan (FKIP / Tarbiyah)
- **Bab III (Metode)**: Desain eksperimen semu (*Quasi-Experimental: Nonequivalent Pretest-Posttest Control Group Design*) atau Penelitian Tindakan Kelas (PTK siklus I & II), uji normalitas & homogenitas, uji validitas & reliabilitas tes kognitif.
- **Bab IV (Hasil & Pembahasan)**: Tabel perbandingan nilai pretest-posttest, perhitungan N-Gain Score, uji-t independen efektivitas model pembelajaran, dan pembahasan pedagogis.

### D. Rumpun Hukum, Sosial, Politik, & Humaniora (Kualitatif / Normatif)
- **Bab III (Metode)**: Pendekatan penelitian (yuridis normatif / sosiologis empiris atau studi kasus deskriptif), teknik penentuan informan kunci (*snowball / purposive*), teknik triangulasi data (sumber dan metode), serta tahapan analisis data model Miles, Huberman & Saldana.
- **Bab IV (Hasil & Pembahasan)**: Tabel matriks sinkronisasi peraturan perundang-undangan / tabel kategorisasi tema hasil wawancara mendalam, dilengkapi interpretasi sosiologis/yuridis yang tajam dan kontekstual.

### E. Rumpun Kesehatan, Keperawatan, Farmasi, & Kedokteran
- **Bab III (Metode)**: Desain studi observasional analitik / eksperimental laboratorium, populasi target & terjangkau, kriteria inklusi & eksklusi, instrumen uji / kuesioner baku, serta *Ethical Clearance* (kelaikan etik).
- **Bab IV (Hasil & Pembahasan)**: Tabel distribusi frekuensi (univariat), tabel tabulasi silang bivariat (Chi-Square / Fisher Exact dengan nilai p dan *Odds Ratio*), serta komparasi dengan standar klinis Kemenkes / WHO.

---

## 5. Struktur Wajib 5 Bab Naskah Skripsi
- **BAB I PENDAHULUAN**: 1.1 Latar Belakang Masalah, 1.2 Identifikasi Masalah, 1.3 Batasan Masalah, 1.4 Rumusan Masalah, 1.5 Tujuan Penelitian, 1.6 Manfaat Penelitian (1.6.1 Teoretis, 1.6.2 Praktis), 1.7 Sistematika Penulisan.
- **BAB II TINJAUAN PUSTAKA**: 2.1 Landasan Teori (Grand Theory, konsep utama, dan algoritma/metode), 2.2 Penelitian Terdahulu (lengkap dengan tabel komparasi), 2.3 Kerangka Pemikiran / Bagan Konseptual, 2.4 Hipotesis / Proposisi Penelitian.
- **BAB III METODOLOGI PENELITIAN**: 3.1 Jenis dan Desain Penelitian, 3.2 Lokasi dan Waktu Penelitian, 3.3 Populasi dan Sampel (Objek/Bahan), 3.4 Definisi Operasional & Pengukuran Variabel (dengan tabel indikator), 3.5 Metode Pengumpulan Data, 3.6 Uji Instrumen / Validasi, 3.7 Metode Analisis Data.
- **BAB IV HASIL PENELITIAN DAN PEMBAHASAN**: 4.1 Gambaran Umum Objek Penelitian, 4.2 Hasil Penelitian / Olah Data Empiris (dengan tabel data dan alur flowchart), 4.3 Pengujian Hipotesis, 4.4 Pembahasan Kritis & Komparasi Teoretis.
- **BAB V PENUTUP**: 5.1 Kesimpulan (menjawab rumusan masalah Bab I secara tegas dengan angka terukur), 5.2 Saran (rekomendasi terapan praktis dan akademis untuk riset lanjutan).

