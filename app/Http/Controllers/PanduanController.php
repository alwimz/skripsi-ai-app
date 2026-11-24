<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PanduanController extends Controller
{
    /**
     * Daftar 7 panduan resmi SkripsiLab AI (berfungsi ganda sebagai Dokumentasi Pengguna & Halaman SEO Publik).
     */
    public static function getGuides(): array
    {
        return [
            [
                'slug' => 'cari-judul-skripsi',
                'num' => '01',
                'category' => 'Ide & Topik Riset',
                'reading_time' => '3 Menit Baca',
                'short_title' => 'Cari Judul Skripsi',
                'title' => 'Panduan Cari Judul Skripsi Otomatis Sesuai Jurusan & 9 Metode Penelitian',
                'meta_title' => 'Cara Cari Judul Skripsi Otomatis (9 Metode & Semua Jurusan) | SkripsiLab AI',
                'meta_description' => 'Panduan lengkap menggunakan fitur Cari Judul SkripsiLab AI untuk mendapatkan 10 ide judul skripsi terbaru, spesifik variabel, dan langsung siap dipakai membuat proyek proposal/skripsi.',
                'keywords' => 'cari judul skripsi, generator judul skripsi ai, ide judul skripsi manajemen informatika hukum pendidikan, skripsi kuantitatif kualitatif r&d, skripsilab ai',
                'hero_subtitle' => 'Temukan 10 rekomendasi judul skripsi spesifik dan layak uji (feasible) dalam hitungan detik sesuai program studi dan metodologi pilihanmu.',
                'tab' => 'titles',
                'cta_label' => 'Buka Fitur Cari Judul →',
                'steps' => [
                    [
                        'title' => 'Buka Menu "Cari Judul" di Sidebar Workspace',
                        'desc' => 'Setelah masuk ke akun SkripsiLab AI, klik menu Cari Judul pada sidebar kiri atau kartu Cari Judul Skripsi di Dashboard. Kamu juga bisa menuju ke sini langsung dari tombol "Belum punya judul? Cari Judul dulu →" di form Buat Proyek.',
                        'tip' => 'Kamu tidak perlu mengarang judul dari nol jika belum punya gambaran variabel.',
                    ],
                    [
                        'title' => 'Isi Topik atau Bidang Penelitian yang Diminati',
                        'desc' => 'Ketik topik utama pada kolom Topik atau Bidang Penelitian, misalnya "analisis sentimen komentar TikTok", "stres kerja dan turnover intention karyawan", "pembelajaran berdiferensiasi SD", atau "perlindungan data pribadi pinjaman online".',
                        'tip' => 'Semakin spesifik objek/lokasi atau fokus bidangmu, semakin tajam judul yang dihasilkan.',
                    ],
                    [
                        'title' => 'Pilih 1 dari 9 Metodologi Penelitian & Program Studi',
                        'desc' => 'Pilih metodologi yang sesuai rencana penelitianmu: Kuantitatif (Survei / Regresi / SEM), Kuantitatif Data Sekunder (BPS/BEI/Panel), Kualitatif, R&D / Pengembangan Sistem IT/AI, PTK, Studi Pustaka, Mixed Method, Hukum Normatif, atau Hukum Empiris. Isi kolom Program Studi agar istilah judul selaras dengan disiplin ilmumu.',
                        'tip' => 'Pastikan metode selaras dengan ketersediaan data di lapangan atau objek penelitianmu.',
                    ],
                    [
                        'title' => 'Klik "Temukan 10 Judul →" dan Pilih Judul Terbaik',
                        'desc' => 'Sistem menghasilkan 10 rekomendasi judul terstruktur lengkap dengan variabel dan objek penelitian. Tinjau daftar judul yang muncul, lalu klik tombol "Gunakan Judul →" di sebelah judul pilihanmu untuk langsung membawanya ke formulir Buat Proyek lengkap dengan prodi & metode.',
                        'tip' => 'Kamu bisa menyimpan 2–3 alternatif judul untuk dikonsultasikan lebih dulu kepada dosen pembimbing.',
                    ],
                ],
                'mistakes' => [
                    'Topik terlalu umum (misal hanya menulis "manajemen" tanpa konteks masalah atau variabel).',
                    'Memilih metodologi Kuantitatif Survei padahal topikmu lebih cocok R&D (pembuatan aplikasi/produk).',
                ],
                'faqs' => [
                    ['q' => 'Apakah judul yang dihasilkan aman dari duplikasi kata kunci persis di kampus?', 'a' => 'Setiap kali menekan Temukan 10 Judul, AI menyusun variasi variabel, objek, dan fokus analisis baru agar relevan sekaligus mudah disesuaikan dengan lokasi penelitianmu.'],
                    ['q' => 'Apakah judul hasil generator masih bisa diedit?', 'a' => 'Ya. Setelah klik Gunakan Judul, teks judul otomatis masuk ke kolom editable pada form Buat Proyek sehingga bebas kamu ubah sesuai saran dosen pembimbing.'],
                ],
            ],
            [
                'slug' => 'cari-jurnal-skripsi',
                'num' => '02',
                'category' => 'Referensi & DOI',
                'reading_time' => '4 Menit Baca',
                'short_title' => 'Cari Jurnal & Referensi DOI',
                'title' => 'Panduan Cari Jurnal Nasional & Internasional Terverifikasi DOI (Sinta & Scopus)',
                'meta_title' => 'Cara Cari Jurnal Skripsi Terverifikasi DOI & Sitasi APA 7th | SkripsiLab AI',
                'meta_description' => 'Langkah mencari jurnal ilmiah asli dari OpenAlex & Crossref, mengunduh PDF open-access, menyalin sitasi APA 7th, dan menyimpannya langsung ke proyek skripsimu.',
                'keywords' => 'cari jurnal skripsi gratis, download jurnal pdf doi, referensi jurnal sinta scopus, daftar pustaka apa 7th otomatis, skripsilab ai',
                'hero_subtitle' => 'Temukan artikel jurnal asli lengkap dengan DOI aktif, abstrak ringkas, link unduh PDF open-access, dan integrasi 1-klik ke Daftar Pustaka proyek.',
                'tab' => 'journals',
                'cta_label' => 'Buka Fitur Cari Jurnal →',
                'steps' => [
                    [
                        'title' => 'Masuk ke Menu "Cari Jurnal" dan Tentukan Kata Kunci',
                        'desc' => 'Buka menu Cari Jurnal di sidebar Workspace. Masukkan kata kunci variabel atau algoritma penelitianmu (misalnya "naive bayes sentimen tiktok", "beban kerja turnover intention", atau "model pbl hasil belajar").',
                        'tip' => 'Gunakan kombinasi 2–4 kata kunci utama agar hasil pencarian sangat relevan.',
                    ],
                    [
                        'title' => 'Filter Cakupan Jurnal (Indonesia, Internasional, atau Semua)',
                        'desc' => 'Pilih dropdown Sumber Jurnal: Semua (Indonesia + Internasional), Khusus Jurnal Indonesia (Nasional / Sinta), atau Khusus Jurnal Internasional (Global / Scopus). Setelah hasil muncul, kamu juga bisa menyaring ulang dengan tombol filter cepat Indonesia / Internasional.',
                        'tip' => 'Kombinasikan jurnal Indonesia terbaru (>= 2023) dan jurnal Internasional sebagai pembanding di Bab II & Bab IV.',
                    ],
                    [
                        'title' => 'Cek DOI, Unduh PDF Asli, atau Salin Sitasi',
                        'desc' => 'Setiap kartu artikel menampilkan judul, penulis, tahun terbit, nama jurnal, tautan DOI resmi, serta tombol Unduh PDF (untuk artikel open-access). Kamu juga bisa mengunduh seluruh daftar sitasi dalam format .txt.',
                        'tip' => 'Selalu buka atau simpan PDF sumber aslinya agar kamu siap jika dosen pembimbing meminta bukti referensi.',
                    ],
                    [
                        'title' => 'Klik "Simpan ke Proyek" & Gunakan Fitur Ganti Sitasi di Workspace',
                        'desc' => 'Pilih proyek tujuan pada dropdown kanan atas hasil pencarian lalu klik Simpan ke Proyek. Jurnal otomatis terkunci ke bank referensi proyek tersebut, sehingga ketika kamu generate Bab atau mengklik sitasi biru di Pratinjau A4 dan memilih Ganti Sitasi, sumbernya diambil dari jurnal terverifikasi.',
                        'tip' => 'Ini memastikan Daftar Pustaka Bab V cocok 100% dengan kutipan in-text di Bab I–IV.',
                    ],
                ],
                'mistakes' => [
                    'Menggunakan kata kunci berbentuk kalimat pertanyaan panjang sehingga hasil pencarian kurang spesifik.',
                    'Mengutip sumber tanpa menyimpan PDF atau mengecek kesesuaian tahun minimum pedoman kampus.',
                ],
                'faqs' => [
                    ['q' => 'Apakah jurnal yang muncul benar-benar ada dan bukan karangan AI?', 'a' => 'Ya. Pencarian jurnal ditarik langsung dari indeks metadata ilmiah nyata (OpenAlex & Crossref) lengkap dengan tautan DOI aktif yang bisa diklik dan diverifikasi.'],
                    ['q' => 'Format sitasi apa saja yang didukung saat masuk ke proyek?', 'a' => 'SkripsiLab AI mendukung 5 gaya sitasi standar kampus: APA 7th, IEEE, Harvard, Chicago, dan Vancouver.'],
                ],
            ],
            [
                'slug' => 'buat-proyek-dan-generate-bab',
                'num' => '03',
                'category' => 'Penulisan Bab I–V',
                'reading_time' => '5 Menit Baca',
                'short_title' => 'Buat Proyek & Generate Bab',
                'title' => 'Panduan Membuat Proyek & Generate Bab I–V Proposal/Skripsi Standar Kampus',
                'meta_title' => 'Cara Buat Proyek Skripsi & Generate Bab 1–5 Otomatis (Word/PDF) | SkripsiLab AI',
                'meta_description' => 'Tutorial menyusun Proposal (Bab I–III) dan Skripsi Lengkap (Bab I–V) di SkripsiLab AI: unggah pedoman kampus, atur sitasi, pratinjau A4 1:1, tambah lampiran, hingga ekspor Word & PDF.',
                'keywords' => 'generator skripsi bab 1-5, buat proposal skripsi otomatis ai, format skripsi 4-4-3-3 word pdf, upload pedoman kampus skripsi, skripsilab ai',
                'hero_subtitle' => 'Susun Proposal (Bab I–III) maupun Skripsi Penuh (Bab I–V) lengkap dengan Cover, Abstrak dinamis, Kata Pengantar 9 poin, Daftar Isi otomatis, dan ekspor Word/PDF siap cetak.',
                'tab' => 'projects',
                'cta_label' => 'Buat Proyek Skripsi Sekarang →',
                'steps' => [
                    [
                        'title' => 'Pilih Target Penulisan & Isi Judul Serta Identitas Mahasiswa',
                        'desc' => 'Buka Proyek Saya → + Buat Proyek Baru. Pilih target Proposal Skripsi (Bab 1–3) atau Full Skripsi Lengkap (Bab 1–5). Isi Judul (atau klik Cari Judul dulu), unggah Logo Kampus (.png/.jpg), lalu lengkapi Nama, NIM/NPM, Kampus, Program Studi, dan Fakultas.',
                        'tip' => 'Identitas dan logo ini otomatis tercetak pada Halaman Sampul, Lembar Pengesahan, Surat Keaslian, dan Kata Pengantar.',
                    ],
                    [
                        'title' => 'Pilih Metodologi, Unggah Pedoman Kampus & Data Awal (Opsional)',
                        'desc' => 'Pilih 1 dari 9 metode penelitian sesuai topikmu. Jika kampus memiliki buku pedoman khusus, unggah PDF/DOCX Pedoman Kampus atau aktifkan Struktur Bab Kustom untuk mengatur sub-bab manual. Masukkan juga Data Awal Penelitian dan file referensi wajib jika ada.',
                        'tip' => 'Sistem membaca isi PDF pedoman kampus (termasuk file besar via PDF.js) sehingga urutan sub-bab mengikuti aturan kampusmu.',
                    ],
                    [
                        'title' => 'Atur Sitasi & Pilih Minimal 1 Sumber Referensi',
                        'desc' => 'Tentukan Tahun Terbit Minimum (misal 2023), Asal Referensi, Gaya Sitasi (APA 7th, IEEE, Harvard, Chicago, atau Vancouver), Bahasa Penulisan, Format Penomoran (Desimal 1.1 atau Huruf A.), dan centang minimal 1 Sumber Referensi (Umum, Sinta, atau Scopus) lalu klik Buat Proyek & Buka Workspace →.',
                        'tip' => 'Pengaturan ini mengunci seluruh sitasi yang masuk ke Bab I–V dan Daftar Pustaka.',
                    ],
                    [
                        'title' => 'Generate Bab Berurutan, Cek Sitasi Interaktif, Tambah Lampiran & Ekspor Word/PDF',
                        'desc' => 'Di dalam Workspace, susun Bab I → Bab II → Bab III → Bab IV → Bab V secara berurutan agar angka dataset/sampel dan rumusan masalah terkunci konsisten lintas bab. Klik kutipan biru di Pratinjau A4 jika ingin memeriksa sumber atau mengganti sitasi, unggah Lampiran (gambar/dokumen), lalu unduh Word (.docx) atau PDF (.pdf).',
                        'tip' => 'Pratinjau A4 sudah dikalibrasi 1:1 dengan hasil unduhan PDF & Word (termasuk penomoran Romawi i–xi dan angka Arab 1–dst.).',
                    ],
                ],
                'mistakes' => [
                    'Melompati urutan generate (misal langsung Bab V sebelum Bab IV selesai) sehingga angka kesimpulan belum merujuk hasil Bab IV.',
                    'Lupa mencentang minimal 1 Sumber Referensi di Card Pengaturan Sitasi.',
                ],
                'faqs' => [
                    ['q' => 'Bagaimana kalau hasil Bab tertentu ingin direvisi sesuai coretan dosen?', 'a' => 'Di dalam Workspace tersedia fitur Revisi (Sub-bab) serta editor langsung pada Pratinjau A4. Kamu bahkan bisa mengunggah foto coretan atau catatan revisi dosen untuk memperbaiki sub-bab spesifik.'],
                    ['q' => 'Apakah Daftar Isi, Daftar Tabel, Daftar Gambar, dan Daftar Lampiran terbuat otomatis?', 'a' => 'Ya. Semua daftar tersebut beserta titik-titik pemandu (dot leader) dan nomor halaman Romawi/Arab dibuat otomatis di Pratinjau, Word (.docx), dan PDF (.pdf).'],
                ],
            ],
            [
                'slug' => 'buat-ppt-sidang-skripsi',
                'num' => '04',
                'category' => 'Presentasi Sidang',
                'reading_time' => '3 Menit Baca',
                'short_title' => 'PPT Sidang Skripsi',
                'title' => 'Panduan Buat PPT Sidang Skripsi Otomatis 13 Tema & Ekspor PowerPoint (.pptx)',
                'meta_title' => 'Cara Membuat PPT Sidang Skripsi Otomatis (.pptx) 13 Tema | SkripsiLab AI',
                'meta_description' => 'Langkah membuat slide presentasi seminar proposal & sidang akhir skripsi otomatis dari proyek Bab I–V atau upload Word/PDF, lengkap catatan presenter & ekspor .pptx.',
                'keywords' => 'cara buat ppt sidang skripsi otomatis, template ppt sidang skripsi, generator powerpoint skripsi ai, catatan presenter sidang, skripsilab ai',
                'hero_subtitle' => 'Ubah naskah Bab I–III atau Bab I–V menjadi 6–12 slide presentasi sidang profesional lengkap dengan catatan bicara presenter dan file .pptx siap tampil.',
                'tab' => 'ppt',
                'cta_label' => 'Buka Generate PPT Sidang →',
                'steps' => [
                    [
                        'title' => 'Buka Menu "Generate PPT Sidang" & Atur Parameter Slide',
                        'desc' => 'Pada Step 1, pilih jumlah slide yang diinginkan (6 hingga 12 slide—ideal 8–10 slide untuk durasi presentasi 10–15 menit) serta pilih proyek skripsimu yang tersimpan atau unggah langsung file Word (.docx) / PDF skripsimu.',
                        'tip' => 'Untuk Seminar Proposal gunakan 6–8 slide; untuk Sidang Skripsi Akhir gunakan 8–12 slide agar Bab IV & V terbahas tuntas.',
                    ],
                    [
                        'title' => 'Pilih 1 dari 13 Tema Desain Akademik',
                        'desc' => 'Pada Step 2, pilih tema visual sesuai karakter kampus atau fakultasmu: mulai dari Royal Academic, Sidang Crimson, Canva Editorial, Emerald Executive, Swiss Minimal, Tech Dark, hingga Classic Gold.',
                        'tip' => 'Tema berlatar terang seperti Swiss Minimal atau Royal Academic paling mudah dibaca pada proyektor ruang sidang.',
                    ],
                    [
                        'title' => 'Edit Isi Slide & Catatan Bicara Presenter Secara Langsung',
                        'desc' => 'Setelah AI menyusun ringkasan setiap slide, masuk ke Step 3 untuk mengedit judul slide, poin penting, maupun Speaker Notes (naskah ucapan presenter per slide) agar sesuai gaya komunikasimu saat berdiri di depan penguji.',
                        'tip' => 'Pastikan setiap slide hanya berisi poin inti padat (maks 4–5 butir) dan simpan detail penjelasan di Speaker Notes.',
                    ],
                    [
                        'title' => 'Uji dengan Mode Slide Show Fullscreen lalu Unduh .pptx',
                        'desc' => 'Masuk ke Step 4 untuk mencoba Presentasi Layar Penuh langsung dari browser, atau simpan ke Riwayat PPT dan unduh sebagai file PowerPoint asli (.pptx) yang bisa dibuka di Microsoft PowerPoint maupun Canva/Google Slides.',
                        'tip' => 'Periksa kembali slide Metodologi (Bab III) dan Kesimpulan (Bab V) sebelum mencetak atau menampilkan di proyektor.',
                    ],
                ],
                'mistakes' => [
                    'Memasukkan paragraf panjang ke dalam kotak slide sehingga tulisan menjadi terlalu kecil saat diproyeksikan.',
                    'Tidak berlatih membaca Speaker Notes sehingga durasi presentasi melewati batas waktu penguji.',
                ],
                'faqs' => [
                    ['q' => 'Apakah saya bisa bikin PPT dari file skripsi Word buatan sendiri yang tidak dibuat di SkripsiLab AI?', 'a' => 'Bisa! Kamu cukup mengunggah file .docx atau .pdf skripsimu pada kotak upload di Step 1, dan AI akan mengekstrak poin pentingnya menjadi slide.'],
                    ['q' => 'Apakah file .pptx hasil unduhan bisa diedit lagi di PowerPoint?', 'a' => 'Ya, hasil unduhan berupa format Office Open XML (.pptx) asli sehingga seluruh teks, tata letak, dan catatan pembicara tetap dapat diedit bebas di Microsoft PowerPoint.'],
                ],
            ],
            [
                'slug' => 'simulasi-sidang-skripsi',
                'num' => '05',
                'category' => 'Persiapan Ujian',
                'reading_time' => '4 Menit Baca',
                'short_title' => 'Simulasi Sidang Skripsi',
                'title' => 'Panduan Latihan Simulasi Sidang Skripsi dengan Dosen Penguji AI',
                'meta_title' => 'Cara Latihan Simulasi Sidang Skripsi dengan Penguji AI & Skor | SkripsiLab AI',
                'meta_description' => 'Panduan menggunakan fitur Simulasi Sidang SkripsiLab AI: atur karakter dosen penguji (Kritis/Tegas/Killer), jawab pertanyaan berbasis isi Bab I–V aslimu, dan dapatkan evaluasi skor.',
                'keywords' => 'simulasi sidang skripsi online, contoh pertanyaan sidang skripsi dan jawabannya, latihan tanya jawab penguji skripsi ai, skripsilab ai',
                'hero_subtitle' => 'Latih mental dan argumen ilmiahmu sebelum hari H ujian dengan pertanyaan yang digali langsung dari isi Bab I–V naskahmu.',
                'tab' => 'defense',
                'cta_label' => 'Mulai Simulasi Sidang →',
                'steps' => [
                    [
                        'title' => 'Atur Karakter Dosen Penguji, Jumlah Soal & Passing Grade',
                        'desc' => 'Di Step 1 menu Simulasi Sidang, atur tiga parameter: Karakter Dosen Penguji (Kritis & Metodologis, Santai & Bimbingan, Tegas & Formal, atau Galak / Killer untuk uji mental), Jumlah Pertanyaan (4 sampai 12 soal), dan Standar Kelulusan (skor minimal 6.0 hingga 8.5 / 10).',
                        'tip' => 'Mulailah dari mode "Kritis & Metodologis" (5–6 soal), lalu ulangi dengan mode "Galak / Killer" mendekati hari sidang.',
                    ],
                    [
                        'title' => 'Pilih Proyek Tersimpan atau Unggah File Naskah (.pdf / .docx)',
                        'desc' => 'Klik salah satu proyek skripsimu dari daftar atau unggah file Word/PDF naskahmu sendiri (maks 3,5 MB). Sistem membaca keseluruhan konteks Bab I–V agar pertanyaan penguji benar-benar menanyakan variabel, sampel, rumus, dan temuan spesifik milikmu.',
                        'tip' => 'Jika sudah di tahap Sidang Akhir, pastikan file yang dipilih sudah memuat Bab IV dan Bab V lengkap.',
                    ],
                    [
                        'title' => 'Jawab Pertanyaan Penguji Satu per Satu dengan Argumen Metodologis',
                        'desc' => 'Pada Step 2, sistem menampilkan kategori bab (misal "Bab III · Metodologi"), pertanyaan penguji, dan petunjuk fokus jawaban. Ketik jawaban terbaikmu dengan menyertakan alasan ilmiah dan data/angka dari skripsimu, lalu klik Simpan & Pertanyaan Berikutnya →.',
                        'tip' => 'Biasakan menjawab dalam struktur: Klaim langsung → Dasar metodologi/teori → Bukti angka dari Bab IV.',
                    ],
                    [
                        'title' => 'Pelajari Skor Per Soal, Grade Kelulusan & Saran Jawaban Terbaik',
                        'desc' => 'Pada Step 3, lihat skor akhir, predikat kelulusan, grafik batang skor per pertanyaan, serta buka tab Detail Jawaban & Saran untuk membandingkan jawabanmu dengan contoh jawaban ideal yang direkomendasikan penguji.',
                        'tip' => 'Catat pertanyaan dengan skor di bawah passing grade dan ulangi simulasi hingga skormu konsisten di atas 8.0/10.',
                    ],
                ],
                'mistakes' => [
                    'Menjawab pertanyaan penguji dengan kalimat terlalu normatif/umum tanpa menyebut angka sampel, nilai p-value, akurasi, atau alasan pemilihan metode di naskahmu.',
                ],
                'faqs' => [
                    ['q' => 'Apakah pertanyaan yang keluar sama untuk semua mahasiswa?', 'a' => 'Tidak. AI menyusun pertanyaan khusus berdasarkan judul, rumusan masalah, instrumen/dataset Bab III, dan hasil Bab IV dari naskah yang kamu pilih atau unggah.'],
                ],
            ],
            [
                'slug' => 'parafrase-skripsi',
                'num' => '06',
                'category' => 'Bebas Plagiasi',
                'reading_time' => '4 Menit Baca',
                'short_title' => 'Parafrase Skripsi',
                'title' => 'Panduan Parafrase Skripsi Anti-Turnitin Sesuai KBBI & EYD V',
                'meta_title' => 'Cara Parafrase Skripsi Anti-Turnitin & Cek Penurunan Kemiripan | SkripsiLab AI',
                'meta_description' => 'Tutorial lengkap menggunakan Parafrase Akademik Pro di SkripsiLab AI: 6 mode parafrase, 3 tingkat intensitas, kunci istilah & sitasi, bandingkan diff kata, serta lanjut ke Cek Plagiasi.',
                'keywords' => 'parafrase skripsi anti turnitin, cara menurunkan turnitin skripsi, parafrase online bahasa indonesia ilmiah eyd v, cek plagiasi skripsi, skripsilab ai',
                'hero_subtitle' => 'Rombak struktur kalimat kutipan secara mendalam untuk menekan skor similaritas Turnitin sambil mengunci 100% sitasi, istilah ilmiah, dan angka empiris.',
                'tab' => 'paraphrase',
                'cta_label' => 'Buka Parafrase Akademik Pro →',
                'steps' => [
                    [
                        'title' => 'Masukkan Sumber Teks: Ketik Langsung, Upload File, atau Impor dari Proyek',
                        'desc' => 'Buka menu Parafrase di sidebar. Pilih sumber masukan: ketik/tempel paragraf langsung, unggah file (.docx, .pdf, .txt), atau pilih Ambil dari Proyek Bab I–V untuk memuat teks bab skripsimu otomatis ke editor.',
                        'tip' => 'Untuk hasil paling rapi dan tajam, proses 1–4 paragraf sekaligus per sesi (~150–500 kata).',
                    ],
                    [
                        'title' => 'Pilih Mode Parafrase, Intensitas Perubahan & Istilah Terkunci',
                        'desc' => 'Tentukan Mode Parafrase: pilih Anti-Turnitin (Rombak Struktur Kalimat) untuk menekan kemiripan maksimal, Pasif Ilmiah untuk Bab III/IV, atau Humanizer Natural untuk menghilangkan gaya kalimat kaku AI. Atur Intensitas (Ringan / Sedang / Maksimal) dan isi Istilah Terkunci (misal nama algoritma, undang-undang, atau variabel agar tidak berubah).',
                        'tip' => 'Sitasi in-text seperti (Apandi et al., 2025) serta angka/rumus otomatis dikunci sistem tanpa perlu ditulis ulang.',
                    ],
                    [
                        'title' => 'Klik "Parafrase Sekarang →" dan Evaluasi Metrik & Diff Perubahan',
                        'desc' => 'Setelah selesai, sistem menampilkan badge Keunikan (%), Kemiripan Tersisa (%) berbasis perbandingan frasa 3-gram, perubahan jumlah kata, dan jumlah sitasi yang terjaga. Buka tab Bandingkan Perubahan untuk melihat kata lama vs kata baru dalam sorotan merah/hijau.',
                        'tip' => 'Jika kemiripan tersisa masih di atas 25%, klik Parafrase Ulang ↻ dengan intensitas Maksimal.',
                    ],
                    [
                        'title' => 'Edit Manual bila Perlu & Lanjutkan ke "Cek Kemiripan Turnitin →"',
                        'desc' => 'Kamu dapat menyempurnakan kalimat langsung di kotak Hasil Bersih, menyalin atau mengunduhnya (.txt), lalu klik tombol Cek Kemiripan Turnitin → untuk memindai paragraf hasil parafrase tersebut terhadap database jurnal OpenAlex, Crossref, dan web.',
                        'tip' => 'Selalu baca ulang hasil parafrase agar alur transisi antar-paragraf di naskah skripsimu tetap menyatu.',
                    ],
                ],
                'mistakes' => [
                    'Hanya mengganti 1–2 sinonim kata secara acak (cara ini mudah terdeteksi sebagai plagiasi struktural oleh Turnitin).',
                    'Lupa mengunci nama variabel, istilah hukum/medis, atau singkatan resmi sehingga ikut diterjemahkan.',
                ],
                'faqs' => [
                    ['q' => 'Apakah sitasi dalam kurung dan angka tabel ikut berubah saat diparafrase?', 'a' => 'Tidak. Sistem menginstruksikan penguncian ketat pada seluruh sitasi penulis-tahun, nomor urut [1], angka persentase, koefisien regresi, dan istilah yang kamu kunci.'],
                ],
            ],
            [
                'slug' => 'olah-data-statistik-skripsi',
                'num' => '07',
                'category' => 'Bab III & IV Statistik',
                'reading_time' => '5 Menit Baca',
                'short_title' => 'Olah Data Statistik (SPSS)',
                'title' => 'Panduan Olah Data Statistik Skripsi Setara SPSS (9 Uji Valid + Kuesioner)',
                'meta_title' => 'Panduan Olah Data Skripsi Online Setara SPSS (Regresi, ANOVA, Uji t) | SkripsiLab AI',
                'meta_description' => 'Cara menggunakan modul Olah Data Statistik SkripsiLab AI untuk menguji Validitas, Reliabilitas, Regresi Linier & Asumsi Klasik, ANOVA, Uji t, dan membuat kuesioner otomatis.',
                'keywords' => 'olah data skripsi online, alternatif spss gratis mahasiswa, uji regresi asumsi klasik online, uji validitas reliabilitas cronbach alpha, one way anova uji t, skripsilab ai',
                'hero_subtitle' => 'Hitung uji statistik penelitian kuantitatif maupun eksperimen laboratorium secara deterministik presisi SPSS, lengkap dengan interpretasi Bab IV & ekspor Word/PDF.',
                'tab' => 'olahdata',
                'cta_label' => 'Buka Modul Olah Data SPSS →',
                'steps' => [
                    [
                        'title' => 'Pilih Jalur: Sudah Punya Data vs Belum Punya Kuesioner / Form Lab',
                        'desc' => 'Jika sudah memiliki data lapangan atau uji lab, tetap di tab Sudah Punya Data, lalu unggah file Excel (.xlsx/.xls) / CSV atau tempel (copy-paste) langsung dari Excel/Google Sheets. Kamu juga bisa menguji fitur dengan 1-klik Dataset Contoh (Kuesioner Likert N=30, Kuat Tekan Beton N=20, Pre-Post, atau Kontrol vs Eksperimen). Jika belum punya instrumen, buka tab kedua untuk membuat Kuesioner/Form Lab + Template Excel siap isi.',
                        'tip' => 'Pastikan baris pertama tabel berisi nama kolom variabel yang jelas tanpa spasi ganda (mis. X1_1, Skor_Posttest, Kuat_Tekan_MPa).',
                    ],
                    [
                        'title' => 'Pilih 1 dari 9 Jenis Uji Statistik & Petakan Variabel',
                        'desc' => 'Pilih uji yang sesuai desain penelitianmu: Regresi Linier & Asumsi Klasik (R², Uji F, Uji t, VIF/Tolerance, K-S, Glejser, Durbin-Watson), Validitas & Reliabilitas (Pearson r & Cronbach’s Alpha), One-Way ANOVA + Post-Hoc Bonferroni, Independent/Paired t-Test, Korelasi Pearson & Spearman, Deskriptif & Normalitas, Chi-Square, atau Non-Parametrik (Mann-Whitney, Wilcoxon, Kruskal-Wallis).',
                        'tip' => 'Pilih taraf signifikansi α = 0.05 (standar skripsi umum) atau α = 0.01 (ketat/medis).',
                    ],
                    [
                        'title' => 'Jalankan Analisis & Tinjau Tabel Keputusan + Interpretasi Bab IV',
                        'desc' => 'Klik Jalankan Analisis. Sistem melakukan komputasi matematika deterministik (bukan tebakan angka AI!) untuk menghasilkan tabel ala output SPSS, keputusan H0/H1 (berdasarkan Sig. < α), dan interpretasi naratif Bab IV.',
                        'tip' => 'Semua angka p-value, F-hitung, t-hitung, hingga derajat kebebasan (df) dihitung eksak dari dataset yang kamu masukkan.',
                    ],
                    [
                        'title' => 'Unduh Laporan PDF/Word atau Sisipkan Langsung ke Bab IV Proyek',
                        'desc' => 'Kamu dapat mengunduh laporan hasil uji statistik resmi dalam format Word (.docx) atau PDF (.pdf), maupun mengirim ringkasan angka validnya langsung sebagai bahan Data Riil saat men-generate Bab IV proyek skripsimu.',
                        'tip' => 'Selalu simpan laporan PDF/Word olah data ini sebagai lampiran resmi pada sidang skripsimu.',
                    ],
                ],
                'mistakes' => [
                    'Menggunakan uji parametrik (t-Test/ANOVA) tanpa mengecek asumsi normalitas atau homogenitas Levene terlebih dahulu.',
                    'Mencampur teks huruf di dalam kolom angka skor (misal menulis "85%" bukan "85" atau "25,4 kg" dalam sel numerik).',
                ],
                'faqs' => [
                    ['q' => 'Apakah angka statistik pada modul Olah Data dikarang oleh AI?', 'a' => 'Tidak sama sekali. Seluruh perhitungan regresi, korelasi, Cronbach’s Alpha, ANOVA, uji t, hingga uji non-parametrik dijalankan secara matematis deterministik dari datamu, baru kemudian AI membantu menyusun narasi pembahasan Bab IV berdasarkan angka eksak tersebut.'],
                ],
            ],
        ];
    }

    public function index()
    {
        if (auth()->check()) {
            return redirect()->to(route('app.create', ['tab' => 'panduan']) . '#panduan');
        }

        $guides = self::getGuides();
        return view('panduan.index', compact('guides'));
    }

    public function show(string $slug)
    {
        $guides = self::getGuides();
        $currentIndex = null;
        foreach ($guides as $idx => $g) {
            if ($g['slug'] === $slug) {
                $currentIndex = $idx;
                break;
            }
        }
        if ($currentIndex === null) {
            abort(404);
        }

        if (auth()->check()) {
            return redirect()->to(route('app.create', ['tab' => 'panduan', 'guide' => $slug]) . '#panduan');
        }

        $guide = $guides[$currentIndex];
        $prevGuide = $currentIndex > 0 ? $guides[$currentIndex - 1] : null;
        $nextGuide = $currentIndex < count($guides) - 1 ? $guides[$currentIndex + 1] : null;

        return view('panduan.show', compact('guide', 'guides', 'prevGuide', 'nextGuide'));
    }
}
