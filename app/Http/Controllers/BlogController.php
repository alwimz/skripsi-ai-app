<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BlogController extends Controller
{
    /**
     * Daftar artikel SEO SkripsiLab AI.
     */
    public static function getArticles(): array
    {
        return [
            [
                'slug' => 'cara-menentukan-judul-skripsi-yang-cepat-acc-dosen',
                'category' => 'Ide & Judul Skripsi',
                'reading_time' => '5 Menit Baca',
                'published_at' => '2026-11-25',
                'published_label' => '25 November 2026',
                'title' => 'Cara Menentukan Judul Skripsi yang Cepat ACC Dosen Pembimbing (Semua Jurusan)',
                'meta_title' => 'Cara Menentukan Judul Skripsi Cepat ACC Dosen Pembimbing | Blog SkripsiLab AI',
                'meta_description' => 'Panduan praktis memilih judul skripsi yang spesifik, relevan dengan tren riset terbaru, punya referensi jurnal DOI, dan mudah disetujui dosen pembimbing.',
                'keywords' => 'cara menentukan judul skripsi, judul skripsi cepat acc, ide judul skripsi semua jurusan, judul skripsi kuantitatif kualitatif r&d, skripsilab ai',
                'excerpt' => 'Banyak mahasiswa tertahan berbulan-bulan di tahap pengajuan judul. Pelajari pola merangkai variabel, objek, dan metode penelitian agar proposal langsung dilirik dosen pembimbing.',
                'sections' => [
                    [
                        'heading' => '1. Mengapa Judul Skripsi Sering Ditolak Dosen?',
                        'paragraphs' => [
                            'Alasan paling umum judul skripsi ditolak bukan karena topik tidak menarik, melainkan terlalu luas, sulit diukur secara metodologis, atau belum memiliki gap penelitian yang jelas. Judul seperti "Analisis Pemasaran Digital pada UMKM" misalnya, tidak menjelaskan variabel, objek lokasi, maupun metode analisisnya.',
                            'Dosen pembimbing biasanya langsung memeriksa tiga hal: apakah variabelnya jelas, apakah datanya bisa diambil mahasiswa dalam waktu satu semester, dan apakah ada jurnal rujukan terbaru (5 tahun terakhir) sebagai pembanding.',
                        ],
                    ],
                    [
                        'heading' => '2. Rumus Praktis Menyusun Judul yang Tajam',
                        'paragraphs' => [
                            'Agar lebih mudah diterima, susun judul menggunakan pola tiga komponen: variabel/solusi utama + hubungan/metode + objek atau konteks spesifik.',
                        ],
                        'bullets' => [
                            'Kuantitatif Survei/Regresi: "Pengaruh [Variabel X1] dan [Variabel X2] terhadap [Variabel Y] pada [Objek/Populasi]".',
                            'R&D / Teknik Informatika: "Implementasi [Algoritma/Framework] untuk [Tujuan/Masalah] Berbasis [Platform] pada [Studi Kasus]".',
                            'Kualitatif / Hukum / Pendidikan: "Analisis [Fenomena/Kebijakan/Model Pembelajaran] dalam Meningkatkan [Fokus Kajian] di [Lokasi]".',
                        ],
                    ],
                    [
                        'heading' => '3. Pastikan Referensi Jurnalnya Tersedia Sejak Awal',
                        'paragraphs' => [
                            'Sebelum mengajukan judul ke dosen, siapkan minimal 3 sampai 5 jurnal ber-DOI terbitan 2021–2026 yang membahas variabel serupa. Ini membuktikan bahwa penelitianmu punya pijakan teoretis yang kuat.',
                            'Di dalam workspace SkripsiLab AI, fitur Cari Judul langsung menghasilkan 10 rekomendasi judul terstruktur sesuai program studi dan 9 metode penelitian, yang bisa langsung dipadukan dengan fitur Cari Jurnal ber-DOI dari Crossref & OpenAlex.',
                        ],
                    ],
                ],
                'cta_title' => 'Butuh 10 Ide Judul Skripsi Spesifik Jurusanmu?',
                'cta_desc' => 'Gunakan fitur Cari Judul di Workspace SkripsiLab AI untuk mendapatkan judul lengkap dengan rumusan masalah sesuai 9 metode penelitian.',
            ],
            [
                'slug' => 'cara-mencari-jurnal-nasional-dan-internasional-doi-untuk-skripsi',
                'category' => 'Referensi & DOI',
                'reading_time' => '5 Menit Baca',
                'published_at' => '2026-11-24',
                'published_label' => '24 November 2026',
                'title' => 'Cara Mencari Jurnal Nasional Sinta & Internasional Ber-DOI untuk Referensi Skripsi',
                'meta_title' => 'Cara Mencari Jurnal Skripsi Terverifikasi DOI (Sinta & Internasional) | Blog SkripsiLab AI',
                'meta_description' => 'Langkah menemukan jurnal ilmiah asli dengan tautan DOI aktif serta menyusun sitasi APA 7th, Harvard, IEEE, Chicago, dan Vancouver secara rapi.',
                'keywords' => 'cara mencari jurnal skripsi, jurnal terverifikasi doi, referensi skripsi sinta scopus, daftar pustaka apa 7th otomatis, skripsilab ai',
                'excerpt' => 'Referensi fiktif atau tanpa DOI sering membuat naskah dicoret saat bimbingan. Ketahui cara cepat menemukan jurnal resmi dan menyusun daftar pustaka otomatis.',
                'sections' => [
                    [
                        'heading' => '1. Mengapa Jurnal Harus Memiliki DOI Aktif?',
                        'paragraphs' => [
                            'DOI (Digital Object Identifier) adalah identitas permanen sebuah artikel ilmiah. Ketika dosen atau penguji mengklik tautan DOI di Daftar Pustaka, mereka dapat langsung memverifikasi penulis, tahun terbit, nama jurnal, hingga abstrak aslinya.',
                            'Menggunakan referensi tanpa sumber terverifikasi berisiko menimbulkan sitasi palsu. Karena itu, pastikan setiap sitasi di Bab I dan Bab II berasal dari indeks ilmiah kredibel seperti Crossref, OpenAlex, Sinta, atau Scopus.',
                        ],
                    ],
                    [
                        'heading' => '2. Strategi Kata Kunci Pencarian Jurnal yang Akurat',
                        'paragraphs' => [
                            'Gunakan kombinasi 2–4 kata kunci utama daripada kalimat panjang. Jika topikmu berbahasa Indonesia, cari juga padanan istilah bahasa Inggris untuk memperkaya literatur internasional pada tinjauan pustaka.',
                        ],
                        'bullets' => [
                            'Filter rentang tahun 2021–2026 agar literatur tetap mutakhir sesuai syarat mayoritas kampus.',
                            'Simpan tautan DOI dan metadata lengkap (penulis, volume, edisi, halaman) sejak awal membaca abstrak.',
                            'Sesuaikan gaya sitasi dengan pedoman kampus: APA 7th, Harvard, IEEE, Chicago, atau Vancouver.',
                        ],
                    ],
                    [
                        'heading' => '3. Integrasi Referensi Langsung ke Naskah Skripsi',
                        'paragraphs' => [
                            'Di SkripsiLab AI, setiap proyek terhubung langsung dengan pencarian artikel Crossref dan OpenAlex. Sitasi di dalam paragraf dan entri Daftar Pustaka disusun otomatis sesuai gaya sitasi pilihanmu lengkap dengan tautan DOI aktif.',
                        ],
                    ],
                ],
                'cta_title' => 'Susun Daftar Pustaka Ber-DOI Tanpa Ribet',
                'cta_desc' => 'Coba Workspace SkripsiLab AI gratis untuk mencari jurnal nasional & internasional sekaligus menata sitasi otomatis.',
            ],
            [
                'slug' => 'panduan-format-margin-4-4-3-3-daftar-isi-dan-penomoran-halaman-skripsi',
                'category' => 'Format & Penulisan',
                'reading_time' => '6 Menit Baca',
                'published_at' => '2026-11-23',
                'published_label' => '23 November 2026',
                'title' => 'Panduan Format Skripsi Margin 4-4-3-3, Penomoran Romawi-Arab, dan Daftar Isi Otomatis',
                'meta_title' => 'Panduan Format Skripsi Margin 4-4-3-3 & Penomoran Halaman | Blog SkripsiLab AI',
                'meta_description' => 'Aturan standar format skripsi Indonesia: kertas A4, margin kiri-atas 4 cm & kanan-bawah 3 cm, halaman Romawi-Arab, serta cara merapikan Word tanpa mengubah isi.',
                'keywords' => 'format margin 4-4-3-3 skripsi, penomoran halaman romawi arab skripsi, cara membuat daftar isi skripsi otomatis, rapikan skripsi word',
                'excerpt' => 'Format Word yang berantakan sering memakan waktu berhari-hari menjelang deadline pengumpulan. Pahami standar tata letak A4 kampus di Indonesia beserta solusi cepatnya.',
                'sections' => [
                    [
                        'heading' => '1. Standar Ukuran Kertas dan Margin 4-4-3-3 cm',
                        'paragraphs' => [
                            'Sebagian besar perguruan tinggi di Indonesia menetapkan ukuran kertas A4 (21 x 29,7 cm) dengan margin 4 cm di sisi kiri dan atas (untuk ruang penjilidan) serta 3 cm di sisi kanan dan bawah.',
                            'Untuk tipografi, font standar yang umum dipakai adalah Times New Roman ukuran 12pt dengan jarak antarbaris 1,5 spasi pada isi bab dan 1 spasi pada halaman abstrak serta daftar pustaka.',
                        ],
                    ],
                    [
                        'heading' => '2. Aturan Penomoran Halaman Romawi dan Angka Arab',
                        'paragraphs' => [
                            'Kesalahan yang paling sering terjadi saat mengatur Word secara manual adalah nomor halaman ikut muncul di halaman sampul atau nomor Romawi terbawa hingga Bab I.',
                        ],
                        'bullets' => [
                            'Halaman Sampul (Cover): Tidak memakai nomor halaman yang tercetak.',
                            'Bagian Depan (Lembar Pengesahan, Kata Pengantar, Abstrak, Daftar Isi/Tabel/Gambar): Memakai angka Romawi kecil (i, ii, iii, iv...) di bagian bawah tengah.',
                            'Bagian Isi (Bab I sampai Bab V & Daftar Pustaka): Memakai angka Arab (1, 2, 3...) mulai dari halaman 1 pada Bab I.',
                        ],
                    ],
                    [
                        'heading' => '3. Otomatisasi Format di SkripsiLab AI & Tools Word',
                        'paragraphs' => [
                            'Saat mengekspor proyek di SkripsiLab AI ke Word (.docx) atau PDF (.pdf), sampul, daftar isi, daftar tabel, daftar gambar, daftar lampiran, hingga nomor halaman sudah tertata otomatis. Jika kamu sudah punya file .docx terpisah, tersedia juga tool Rapikan Skripsi Word di Workspace untuk menata ulang margin 4-4-3-3 dan pindah halaman bab tanpa mengubah satu kata pun isi naskah.',
                        ],
                    ],
                ],
                'cta_title' => 'Ekspor Naskah A4 Margin 4-4-3-3 Siap Cetak',
                'cta_desc' => 'Buat draf sekaligus ekspor ke Word (.docx) dan PDF yang sudah rapi sesuai standar skripsi Indonesia.',
            ],
            [
                'slug' => 'perbedaan-proposal-skripsi-bab-1-3-dan-skripsi-penuh-bab-1-5',
                'category' => 'Metodologi Riset',
                'reading_time' => '4 Menit Baca',
                'published_at' => '2026-11-22',
                'published_label' => '22 November 2026',
                'title' => 'Perbedaan Proposal Skripsi (Bab I–III) dan Skripsi Penuh (Bab I–V): Struktur & Tips Menyusun',
                'meta_title' => 'Perbedaan Proposal Skripsi Bab 1-3 dan Skripsi Penuh Bab 1-5 | Blog SkripsiLab AI',
                'meta_description' => 'Pahami isi dan fungsi setiap bab pada Proposal Skripsi (Bab I–III) dan Skripsi Lengkap (Bab I–V) serta cara melanjutkannya tanpa menulis ulang dari awal.',
                'keywords' => 'perbedaan proposal skripsi dan skripsi, struktur bab 1-3 proposal, struktur bab 1-5 skripsi, lanjut bab 4 dan 5 skripsi',
                'excerpt' => 'Belum tahu harus mulai dari Proposal Bab I–III atau langsung Skripsi Bab I–V? Kenali batas pembahasan tiap bab agar alur penulisan tetap konsisten dari awal hingga sidang.',
                'sections' => [
                    [
                        'heading' => '1. Fokus Proposal Skripsi (Bab I–III)',
                        'paragraphs' => [
                            'Proposal skripsi diajukan untuk seminar proposal (sempro) sebagai rencana penelitian sebelum terjun mengambil data lapangan atau membangun sistem. Fokus utamanya menjawab "mengapa penelitian ini penting", "teori apa yang mendasarinya", dan "bagaimana metode pengerjaannya".',
                        ],
                        'bullets' => [
                            'Bab I Pendahuluan: Latar belakang, identifikasi & rumusan masalah, tujuan, serta manfaat penelitian.',
                            'Bab II Tinjauan Pustaka: Landasan teori, penelitian terdahulu yang relevan, kerangka berpikir, dan hipotesis (jika kuantitatif).',
                            'Bab III Metodologi Penelitian: Desain riset, populasi & sampel, teknik pengumpulan data, definisi operasional variabel, serta teknik analisis.',
                        ],
                    ],
                    [
                        'heading' => '2. Pengembangan ke Skripsi Penuh (Bab IV & Bab V)',
                        'paragraphs' => [
                            'Setelah seminar proposal disetujui dan data lapangan/pengujian terkumpul, penelitian dilanjutkan ke Bab IV (Hasil Penelitian dan Pembahasan) dan Bab V (Penutup: Kesimpulan & Saran).',
                            'Kunci agar Bab IV dan Bab V kuat adalah konsistensi: jumlah kesimpulan di Bab V harus menjawab secara presisi setiap poin rumusan masalah di Bab I, dan angka di Bab IV wajib berasal dari hasil olah data riil (SPSS, SmartPLS, atau evaluasi sistem).',
                        ],
                    ],
                ],
                'cta_title' => 'Mulai dari Proposal Bab I–III, Lanjut ke Bab IV–V Kapan Saja',
                'cta_desc' => 'Di SkripsiLab AI kamu bisa memilih tahap Proposal atau Skripsi Penuh serta memasukkan hasil olah data riil langsung ke Bab IV.',
            ],
            [
                'slug' => 'cara-parafrase-skripsi-yang-aman-agar-lolos-cek-kemiripan-turnitin',
                'category' => 'Parafrase & Etika',
                'reading_time' => '5 Menit Baca',
                'published_at' => '2026-11-21',
                'published_label' => '21 November 2026',
                'title' => 'Cara Parafrase Skripsi yang Aman Sesuai EYD V agar Lolos Uji Kemiripan Turnitin',
                'meta_title' => 'Cara Parafrase Skripsi Aman Sesuai EYD V & Lolos Cek Kemiripan | Blog SkripsiLab AI',
                'meta_description' => 'Teknik parafrase akademik yang benar tanpa mengubah makna kutipan maupun merusak sitasi ilmiah agar skor similarity tetap aman.',
                'keywords' => 'cara parafrase skripsi, parafrase akademik eyd v, turunkan similarity turnitin skripsi, etika parafrase kutipan',
                'excerpt' => 'Sekadar mengganti beberapa kata dengan sinonim sering membuat kalimat terasa aneh dan tetap terdeteksi mirip. Pelajari teknik parafrase struktur yang tepat.',
                'sections' => [
                    [
                        'heading' => '1. Kesalahan Umum Saat Melakukan Parafrase Manual',
                        'paragraphs' => [
                            'Banyak mahasiswa hanya mengganti satu atau dua kata dengan sinonim sementara urutan kalimat tetap sama persis dengan sumber aslinya. Cara ini mudah terbaca oleh alat deteksi kemiripan teks sekaligus berisiko merusak istilah teknis di bidang studi.',
                        ],
                    ],
                    [
                        'heading' => '2. Prinsip Parafrase Akademik yang Tepat',
                        'paragraphs' => [
                            'Parafrase yang baik mengubah struktur kalimat (misalnya dari kalimat aktif menjadi pasif atau menggabungkan dua gagasan) sambil tetap mempertahankan makna inti dan wajib mencantumkan sumber kutipan aslinya.',
                        ],
                        'bullets' => [
                            'Baca paragraf sumber sampai paham intinya, lalu tulis ulang dengan sudut pandang sintesis.',
                            'Pertahankan istilah ilmiah baku dan sitasi penulis (misal: Naufal & Wijaya, 2024).',
                            'Periksa kohesi antarparagraf serta ejaan sesuai kaidah EYD V.',
                        ],
                    ],
                    [
                        'heading' => '3. Memakai Fitur Parafrase Akademik SkripsiLab AI',
                        'paragraphs' => [
                            'Menu Parafrase Akademik di SkripsiLab AI dirancang khusus untuk teks ilmiah berbahasa Indonesia agar hasilnya tetap formal, mengalir natural sesuai EYD V, dan menjaga sitasi tetap utuh.',
                        ],
                    ],
                ],
                'cta_title' => 'Rapikan Kalimat Skripsimu Jadi Lebih Ilmiah',
                'cta_desc' => 'Coba fitur Parafrase Akademik & Lab Revisi Sub-bab langsung di Workspace SkripsiLab AI.',
            ],
            [
                'slug' => 'tips-menjawab-pertanyaan-dosen-penguji-saat-simulasi-sidang-skripsi',
                'category' => 'Persiapan Sidang',
                'reading_time' => '6 Menit Baca',
                'published_at' => '2026-11-20',
                'published_label' => '20 November 2026',
                'title' => '10 Pertanyaan Sidang Skripsi Paling Sering Ditanyakan Dosen Penguji & Cara Menjawabnya',
                'meta_title' => '10 Pertanyaan Sidang Skripsi & Cara Menjawabnya | Blog SkripsiLab AI',
                'meta_description' => 'Daftar pertanyaan wajib saat seminar proposal maupun sidang akhir skripsi beserta strategi menjawab dengan tenang, berbasis data, dan menyiapkan PPT sidang.',
                'keywords' => 'pertanyaan sidang skripsi, cara menjawab dosen penguji, simulasi sidang skripsi ai, ppt sidang skripsi',
                'excerpt' => 'Gugup menghadapi pertanyaan penguji di ruang sidang? Kenali 10 pertanyaan klasik yang hampir selalu muncul dan berlatih langsung lewat Simulasi Sidang AI.',
                'sections' => [
                    [
                        'heading' => '1. Pertanyaan Kritis yang Hampir Selalu Muncul di Sidang',
                        'paragraphs' => [
                            'Dosen penguji tidak mencari kesempurnaan, melainkan memastikan kamu benar-benar memahami alasan di balik setiap keputusan riset dalam naskahmu.',
                        ],
                        'bullets' => [
                            'Mengapa kamu memilih judul dan lokasi penelitian ini? Apa fenomena atau gap masalahnya?',
                            'Mengapa menggunakan metode penelitian dan teknik analisis data tersebut (misal Regresi/PLS-SEM/Naive Bayes)?',
                            'Bagaimana cara menentukan jumlah sampel dan mengapa memakai rumus Slovin/Lemeshow?',
                            'Apa keterbatasan penelitianmu dan saran konkret untuk penelitian selanjutnya?',
                        ],
                    ],
                    [
                        'heading' => '2. Siapkan PPT Sidang yang Ringkas & Latihan Tanya-Jawab',
                        'paragraphs' => [
                            'Hindari memindahkan seluruh paragraf Word ke slide presentasi. Cukup tampilkan poin inti, tabel ringkas, diagram alur, dan angka hasil pengujian pada 8–12 slide yang bersih.',
                            'Di SkripsiLab AI, kamu bisa membuat PPT Sidang otomatis dari naskah skripsimu dengan pilihan 13 tema akademik (.pptx), lalu berlatih di fitur Simulasi Sidang yang menghasilkan 5 pertanyaan penguji langsung dari isi Bab I–V naskahmu lengkap dengan skor dan contoh jawaban terbaik.',
                        ],
                    ],
                ],
                'cta_title' => 'Latihan Simulasi Sidang & Buat PPT dari Naskahmu',
                'cta_desc' => 'Masuk ke SkripsiLab AI untuk menguji kesiapanmu menjawab pertanyaan penguji dan mengekspor PPT sidang otomatis.',
            ],
        ];
    }

    public function index()
    {
        $articles = self::getArticles();
        return view('blog.index', compact('articles'));
    }

    public function show(string $slug)
    {
        $articles = self::getArticles();
        $article = collect($articles)->firstWhere('slug', $slug);

        if (!$article) {
            abort(404);
        }

        $related = collect($articles)->where('slug', '!=', $slug)->take(3)->values()->all();

        return view('blog.show', compact('article', 'related'));
    }
}
