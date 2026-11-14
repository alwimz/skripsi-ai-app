# Pedoman Standar Penulisan Proposal & Skripsi S1 Perguruan Tinggi Indonesia
Terkurasi komprehensif mengacu pada buku pedoman skripsi perguruan tinggi nasional (PTN/PTS) serta repositori skill riset akademik open-source (`FRCHN-GSTN/MenulisCep4t`, `nothinx/naskah-id`, `apissaj/humanizer-id`, `santifs/thesis-writing-skill`).

---

## 1. Kaidah Bahasa Ilmiah Indonesia & Anti-Deteksi AI (Rendah Plagiat & Humanized)
1. **DILARANG KERAS** menggunakan frasa klise pembuka robotik khas AI:
   - ❌ "Di era digital / globalisasi / modern saat ini..."
   - ❌ "Kondisi ideal dalam..."
   - ❌ "Perkembangan teknologi yang pesat telah mengubah berbagai aspek kehidupan..."
   - ❌ "Penting untuk dicatat bahwa..." atau "Sebagai kesimpulan, dapat dikatakan bahwa..."
2. **Karakter Bahasa Akademik Alami (Human-Written Style)**:
   - Langsung menukik ke substansi permasalahan pada kalimat pertama.
   - Variasi sintaksis: Kombinasikan kalimat sederhana, majemuk setara, dan majemuk bertingkat dengan variasi subjek (kalimat pasif ilmiah dan kalimat aktif beralasan).
   - Gunakan konjungsi antarkalimat ilmiah baku: *Kendati demikian*, *Oleh karena itu*, *Konsekuensinya*, *Sebaliknya*, *Dalam konteks empiris*, *Secara konseptual*.
   - Setiap paragraf wajib memuat pola penalaran ilmiah deduktif (*Claim – Evidence – Reasoning*).

---

## 2. Karakteristik & Standar Spesifik Berdasarkan Program Studi / Rumpun Ilmu

### A. Rumpun Informatika, Sistem Informasi, & Ilmu Komputer (Teknik/TI)
- **Tabel Wajib di Bab II**: Tabel Sintesis Penelitian Terdahulu (Kolom: Peneliti & Tahun, Judul, Metode/Algoritma, Temuan/Akurasi, dan Kelebihan/Kekurangan/Research Gap).
- **Bab III (Metodologi)**:
  - Uraikan arsitektur sistem dan alur pemodelan computational secara terperinci.
  - Tahapan pra-pemrosesan data (*crawling/scraping*, *data cleaning*, *case folding*, *tokenizing*, *stopword removal*, *stemming*, *weighting* seperti TF-IDF / Word2Vec).
  - Algoritma spesifik yang dikaji beserta formulasi matematis dasarnya.
  - Metrik evaluasi kinerja sistem (*Confusion Matrix*: *Accuracy*, *Precision*, *Recall*, *F1-Score*, atau *Execution Time*).
- **Bab IV (Hasil & Pembahasan)**:
  - Wajib memuat alur flowchart sistem / tahapan komputasi dalam format teks terstruktur.
  - Tabel pembagian dataset (*training* vs *testing*).
  - Tabel *Confusion Matrix* dan grafik perbandingan performa.
  - Analisis kritis penyebab kesalahan klasifikasi (*error analysis* / *false positive & false negative*).

### B. Rumpun Ekonomi, Manajemen, Akuntansi, & Bisnis
- **Tabel Wajib di Bab III**: Tabel Operasionalisasi Variabel (Variabel, Definisi Operasional, Dimensi, Indikator Empiris, dan Skala Pengukuran Likert 1-5).
- **Bab III (Metode)**: Populasi dan teknik penentuan sampel (rumus Slovin / Purposive Sampling), uji instrumen (validitas Pearson Product Moment & reliabilitas Cronbach's Alpha), uji asumsi klasik (normalitas Kolmogorov-Smirnov, multikolinieritas VIF, heteroskedastisitas Glejser), serta teknik analisis (Regresi Berganda / SEM-PLS).
- **Bab IV (Hasil & Pembahasan)**: Tabel statistik deskriptif responden, tabel uji hipotesis (koefisien regresi, t-hitung vs t-tabel, p-value < 0.05, $R^2$), serta implikasi manajerial nyata.

### C. Rumpun Hukum, Sosial, Politik, & Humaniora (Kualitatif / Normatif)
- **Bab III (Metode)**: Pendekatan penelitian (yuridis normatif / empiris atau studi kasus deskriptif), teknik penentuan informan kunci (*snowball/purposive*), teknik triangulasi data (sumber dan metode), serta tahapan analisis data model Miles, Huberman & Saldana.
- **Bab IV (Hasil & Pembahasan)**: Tabel matriks sinkronisasi peraturan perundang-undangan / tabel kategorisasi tema hasil wawancara mendalam, dilengkapi interpretasi sosiologis/yuridis yang tajam.

### D. Rumpun Kesehatan, Keperawatan, Farmasi, & Kedokteran
- **Bab III (Metode)**: Desain studi observasional analitik / eksperimental, populasi target & terjangkau, kriteria inklusi & eksklusi yang tegas, instrumen uji laboratorium / kuesioner baku, serta *Ethical Clearance* (kelaikan etik).
- **Bab IV (Hasil & Pembahasan)**: Tabel distribusi frekuensi (univariat), tabel tabulasi silang bivariat (Chi-Square / Fisher Exact dengan nilai p dan *Odds Ratio*), serta komparasi dengan pedoman klinis Kemenkes / WHO.

### E. Rumpun Pendidikan & Keguruan (FKIP)
- **Bab III (Metode)**: Desain eksperimen semu (*Quasi-Experimental: Pretest-Posttest Non-equivalent Control Group Design*) atau Penelitian Tindakan Kelas (PTK siklus I & II), uji normalitas & homogenitas.
- **Bab IV (Hasil & Pembahasan)**: Tabel perbandingan nilai pretest-posttest, perhitungan N-Gain Score, dan uji-t independen efektivitas model pembelajaran.

---

## 3. Struktur Wajib 5 Bab Naskah Skripsi
- **BAB I PENDAHULUAN**: 1.1 Latar Belakang Masalah, 1.2 Identifikasi Masalah, 1.3 Batasan Masalah, 1.4 Rumusan Masalah, 1.5 Tujuan Penelitian, 1.6 Manfaat Penelitian (1.6.1 Teoretis, 1.6.2 Praktis), 1.7 Sistematika Penulisan.
- **BAB II TINJAUAN PUSTAKA**: 2.1 Landasan Teori (Grand Theory, konsep utama, dan algoritma/metode), 2.2 Penelitian Terdahulu (lengkap dengan tabel komparasi), 2.3 Kerangka Pemikiran, 2.4 Hipotesis / Proposisi Penelitian.
- **BAB III METODOLOGI PENELITIAN**: 3.1 Jenis dan Desain Penelitian, 3.2 Lokasi dan Waktu Penelitian, 3.3 Populasi dan Sampel (Objek/Bahan), 3.4 Metode Pengumpulan Data, 3.5 Metode Analisis Data & Validasi.
- **BAB IV HASIL PENELITIAN DAN PEMBAHASAN**: 4.1 Gambaran Umum Objek Penelitian, 4.2 Hasil Penelitian / Pengujian Sistem (dengan tabel data dan alur flowchart), 4.3 Pembahasan Kritis & Komparasi Teoretis.
- **BAB V PENUTUP**: 5.1 Kesimpulan (menjawab rumusan masalah Bab I secara tegas), 5.2 Saran (rekomendasi terapan dan akademis untuk riset lanjutan).
