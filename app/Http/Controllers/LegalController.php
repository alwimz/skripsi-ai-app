<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LegalController extends Controller
{
    private const LAST_UPDATED = '24 November 2026';

    private function getCommonMeta(): array
    {
        return [
            'last_updated' => self::LAST_UPDATED,
            'owner_name' => 'Muh Alwi Syahrir',
            'location' => 'Makassar, Sulawesi Selatan, Indonesia',
            'brand_name' => 'SkripsiLab AI',
            'whatsapp_number' => '6282195036971',
            'whatsapp_display' => '+62 821-9503-6971',
        ];
    }

    public function privacy()
    {
        $page = [
            'key' => 'privacy',
            'slug' => '/kebijakan-privasi',
            'badge' => 'Dokumen Legal Resmi',
            'title' => 'Kebijakan Privasi SkripsiLab AI',
            'meta_title' => 'Kebijakan Privasi — SkripsiLab AI | Perlindungan Data & Keamanan Akun',
            'meta_description' => 'Kebijakan privasi resmi SkripsiLab AI (muhalwisyahrir.tech) mengenai pengelolaan data akun, Google Sign-In OAuth 2.0, kerahasiaan naskah proposal dan skripsi, serta hak pengguna.',
            'subtitle' => 'Komitmen kami dalam melindungi privasi akun, keamanan autentikasi Google Sign-In, serta kerahasiaan penuh atas draf proposal dan skripsi mahasiswa.',
            'sections' => [
                [
                    'num' => '1',
                    'title' => 'Identitas Pengelola Layanan',
                    'paragraphs' => [
                        'SkripsiLab AI adalah aplikasi web workspace penulisan akademik berbasis kecerdasan buatan yang beroperasi secara resmi pada domain muhalwisyahrir.tech, dikembangkan dan dikelola oleh Muh Alwi Syahrir (berdomisili di Makassar, Sulawesi Selatan, Indonesia).',
                        'Dengan mendaftar, masuk, atau menggunakan fitur apa pun di SkripsiLab AI, Anda memahami dan menyetujui tata cara pemrosesan data sebagaimana dijelaskan dalam Kebijakan Privasi ini.',
                    ],
                ],
                [
                    'num' => '2',
                    'title' => 'Data yang Kami Kumpulkan',
                    'paragraphs' => [
                        'Kami hanya mengumpulkan informasi yang benar-benar dibutuhkan untuk menjalankan workspace penulisan proposal dan skripsi Anda secara aman:',
                    ],
                    'bullets' => [
                        'Data Identitas Akun: nama lengkap, alamat email, foto profil (jika masuk melalui Google), serta kata sandi yang selalu disimpan dalam bentuk hash terenkripsi satu arah (bcrypt).',
                        'Data Profil Akademik Opsional: nama perguruan tinggi, fakultas, program studi, dan NIM yang Anda isi secara sukarela untuk membantu pemformatan halaman sampul dan dokumen Word/PDF.',
                        'Data Naskah & Proyek Riset: judul penelitian, topik, metodologi, daftar referensi jurnal, dan teks bab yang Anda susun di dalam workspace.',
                        'Data Teknis Minimal: log sesi login aman dan token keamanan CSRF untuk mencegah akses tidak sah.',
                    ],
                ],
                [
                    'num' => '3',
                    'title' => 'Penggunaan Google Sign-In (OAuth 2.0)',
                    'paragraphs' => [
                        'SkripsiLab AI menyediakan tombol Masuk/Daftar dengan Google menggunakan protokol resmi Google OAuth 2.0.',
                        'Saat Anda memilih masuk dengan Google, kami hanya meminta izin akses profil dasar (nama tampilan, alamat email terverifikasi, dan URL avatar). Kami tidak pernah meminta kata sandi akun Google Anda, tidak membaca isi Gmail, tidak mengakses Google Drive pribadi Anda, dan tidak pernah meminta kode OTP perbankan atau layanan pihak ketiga.',
                    ],
                ],
                [
                    'num' => '4',
                    'title' => 'Pemrosesan AI & Kerahasiaan Naskah Skripsi',
                    'paragraphs' => [
                        'Seluruh ide judul, kerangka penelitian, naskah bab proposal maupun skripsi, serta hasil parafrase dan olah data Anda bersifat rahasia untuk akun Anda sendiri.',
                    ],
                    'bullets' => [
                        'Hak Milik Penuh: Anda memegang kendali penuh atas naskah yang Anda buat, sunting, dan ekspor ke format Word (.docx) atau PDF.',
                        'Tidak Dijual ke Pihak Ketiga: SkripsiLab AI tidak pernah menjual, menyewakan, atau memperdagangkan data pribadi maupun naskah mahasiswa kepada pihak pengiklan atau broker data.',
                        'Berkas Alat Bantu Publik: Dokumen yang diproses melalui alat bantu seperti konverter PDF/Word atau perapi format hanya diproses untuk keperluan sesi pengerjaan Anda.',
                    ],
                ],
                [
                    'num' => '5',
                    'title' => 'Keamanan Sistem & Penyimpanan Data',
                    'paragraphs' => [
                        'Seluruh pertukaran data antara peramban Anda dan server SkripsiLab AI dilindungi oleh enkripsi HTTPS/TLS serta header keamanan HTTP standar industri (seperti proteksi XSS, frame-ancestors/SAMEORIGIN, dan X-Content-Type-Options).',
                        'Kata sandi pengguna tidak pernah disimpan dalam teks biasa (plain text). Token sesi dikelola secara aman oleh server aplikasi.',
                    ],
                ],
                [
                    'num' => '6',
                    'title' => 'Cookie & Pelacakan',
                    'paragraphs' => [
                        'SkripsiLab AI menggunakan cookie fungsional esensial semata-mata untuk menjaga status login Anda, preferensi workspace, dan perlindungan token CSRF formulir. Kami tidak memasang jaringan iklan pihak ketiga yang melacak aktivitas browsing Anda di luar situs ini.',
                    ],
                ],
                [
                    'num' => '7',
                    'title' => 'Hak Pengguna & Penghapusan Data',
                    'paragraphs' => [
                        'Anda berhak memperbarui data profil, menghapus proyek skripsi langsung dari dashboard workspace, atau mengajukan penghapusan akun beserta seluruh data terkait.',
                        'Untuk pertanyaan privasi, laporan keamanan, atau permohonan penghapusan akun permanen, silakan hubungi pengelola resmi melalui WhatsApp Support (+62 821-9503-6971) atau lihat rincian lengkap di halaman Tentang & Pengembang.',
                    ],
                ],
            ],
        ];

        return view('legal.page', array_merge($this->getCommonMeta(), [
            'page' => $page,
        ]));
    }

    public function terms()
    {
        $page = [
            'key' => 'terms',
            'slug' => '/syarat-ketentuan',
            'badge' => 'Ketentuan Layanan',
            'title' => 'Syarat & Ketentuan SkripsiLab AI',
            'meta_title' => 'Syarat & Ketentuan — SkripsiLab AI | Aturan Penggunaan & Etika Akademik',
            'meta_description' => 'Syarat dan ketentuan penggunaan platform SkripsiLab AI termasuk ruang lingkup layanan AI akademik, integritas ilmiah, keamanan akun, dan batasan tanggung jawab.',
            'subtitle' => 'Panduan hak, kewajiban, serta prinsip etika akademik dalam memanfaatkan workspace SkripsiLab AI secara bertanggung jawab.',
            'sections' => [
                [
                    'num' => '1',
                    'title' => 'Penerimaan Ketentuan',
                    'paragraphs' => [
                        'Syarat dan Ketentuan ini mengatur akses dan penggunaan situs web serta layanan SkripsiLab AI di alamat resmi muhalwisyahrir.tech.',
                        'Dengan membuat akun atau menggunakan tools publik maupun workspace berlangganan di SkripsiLab AI, Anda menyatakan telah membaca dan setuju untuk mematuhi ketentuan berikut.',
                    ],
                ],
                [
                    'num' => '2',
                    'title' => 'Deskripsi Layanan',
                    'paragraphs' => [
                        'SkripsiLab AI menyediakan perangkat lunak asisten penulisan akademik untuk mahasiswa tingkat akhir, meliputi:',
                    ],
                    'bullets' => [
                        'Generator ide judul penelitian dan pencarian referensi jurnal ilmiah ber-DOI.',
                        'Penyusunan draf terstruktur Proposal (Bab 1–3) dan Skripsi (Bab 1–5) beserta ekspor otomatis Word (.docx) dan PDF sesuai standar tata letak kampus.',
                        'Fitur pendukung seperti Pembuat PPT Sidang, Simulasi Sidang AI, Parafrase Akademik, Asisten Dosen AI, Olah Data Statistik, dan Hub Tools Mahasiswa Gratis.',
                    ],
                ],
                [
                    'num' => '3',
                    'title' => 'Etika Akademik & Tanggung Jawab Ilmiah',
                    'paragraphs' => [
                        'SkripsiLab AI berfungsi sebagai alat bantu penyusunan kerangka, referensi, dan penulisan akademik — bukan pengganti proses bimbingan maupun integritas ilmiah mahasiswa.',
                    ],
                    'bullets' => [
                        'Verifikasi Mandiri: Pengguna wajib membaca ulang, menyunting, serta memastikan seluruh kutipan jurnal, angka penelitian, analisis data lapangan, dan kesesuaian pedoman kampus masing-masing sebelum dikumpulkan.',
                        'Kepatuhan Regulasi Kampus: Pengguna bertanggung jawab penuh mematuhi kebijakan penggunaan AI generatif yang berlaku pada universitas, fakultas, atau dosen pembimbing masing-masing.',
                    ],
                ],
                [
                    'num' => '4',
                    'title' => 'Keamanan Akun & Larangan Penyalahgunaan',
                    'paragraphs' => [
                        'Pengguna bertanggung jawab menjaga kerahasiaan kredensial login masing-masing dan tidak membagikan akun secara tidak wajar.',
                        'Pengguna dilarang melakukan tindakan peretasan, scraping otomatis berlebih, serangan beban (DDoS), penyisipan skrip berbahaya, atau penyalahgunaan endpoint API untuk tujuan selain penggunaan normal aplikasi.',
                    ],
                ],
                [
                    'num' => '5',
                    'title' => 'Hak Kekayaan Intelektual',
                    'paragraphs' => [
                        'Desain antarmuka, kode sumber aplikasi, logo, materi panduan, serta struktur perangkat lunak SkripsiLab AI merupakan hak milik intelektual pengembang (Muh Alwi Syahrir).',
                        'Sementara itu, seluruh ide penelitian, judul, dan dokumen tugas akhir yang Anda susun di workspace tetap menjadi hak guna dan tanggung jawab Anda.',
                    ],
                ],
                [
                    'num' => '6',
                    'title' => 'Ketersediaan Layanan & Pembaruan',
                    'paragraphs' => [
                        'Kami terus meningkatkan akurasi pencarian referensi, template tata letak dokumen, dan stabilitas server. Fitur atau ketentuan layanan dapat diperbarui sewaktu-waktu demi keamanan dan peningkatan kualitas layanan.',
                    ],
                ],
            ],
        ];

        return view('legal.page', array_merge($this->getCommonMeta(), [
            'page' => $page,
        ]));
    }

    public function about()
    {
        $page = [
            'key' => 'about',
            'slug' => '/tentang',
            'badge' => 'Profil Resmi & Transparansi',
            'title' => 'Tentang SkripsiLab AI & Pengembang',
            'meta_title' => 'Tentang Kami & Pengembang — SkripsiLab AI (muhalwisyahrir.tech)',
            'meta_description' => 'Informasi resmi tentang SkripsiLab AI, profil pengembang Muh Alwi Syahrir di Makassar, komitmen keamanan domain muhalwisyahrir.tech, serta kontak dukungan resmi.',
            'subtitle' => 'Mengenal misi platform, identitas pengembang resmi, standar keamanan domain muhalwisyahrir.tech, dan kanal kontak bantuan langsung.',
            'sections' => [
                [
                    'num' => '1',
                    'title' => 'Apa itu SkripsiLab AI?',
                    'paragraphs' => [
                        'SkripsiLab AI adalah workspace penulisan ilmiah berbasis AI yang dirancang khusus untuk kebutuhan format akademik mahasiswa di Indonesia.',
                        'Platform ini membantu proses riset awal hingga persiapan sidang — mulai dari eksplorasi judul, pencarian referensi jurnal ilmiah nyata ber-DOI (terhubung ke basis data ilmiah seperti Crossref, OpenAlex, Semantic Scholar, dan DOAJ), penyusunan bab terstruktur, format halaman otomatis 4-4-3-3 ke Word/PDF, hingga simulasi tanya-jawab sidang.',
                    ],
                ],
                [
                    'num' => '2',
                    'title' => 'Profil Pengembang & Pengelola',
                    'paragraphs' => [
                        'Domain resmi muhalwisyahrir.tech dikelola langsung oleh Muh Alwi Syahrir, seorang pengembang perangkat lunak (Full-Stack Web & Mobile Developer) asal Makassar, Sulawesi Selatan, Indonesia, yang berpengalaman dalam ekosistem Laravel, React Native, dan Flutter.',
                        'Seluruh pemeliharaan server, pembaruan fitur, serta dukungan teknis pengguna ditangani secara langsung oleh tim pengembang tanpa perantara pihak ketiga yang tidak jelas.',
                    ],
                ],
                [
                    'num' => '3',
                    'title' => 'Komitmen Keamanan & Keaslian Situs',
                    'paragraphs' => [
                        'Demi kenyamanan dan keamanan seluruh pengguna, kami menegaskan standar operasional berikut:',
                    ],
                    'bullets' => [
                        'Satu Domain Resmi: Layanan SkripsiLab AI hanya beroperasi pada HTTPS resmi muhalwisyahrir.tech.',
                        'Autentikasi Aman: Login mendukung email/kata sandi terenkripsi bcrypt serta Google OAuth 2.0 resmi.',
                        'Bebas Penipuan & OTP Pihak Ketiga: SkripsiLab AI tidak pernah meminta PIN perbankan, nomor kartu kredit, ataupun kode OTP aplikasi lain dalam bentuk apa pun.',
                    ],
                ],
                [
                    'num' => '4',
                    'title' => 'Kontak Resmi & Dukungan Pengguna',
                    'paragraphs' => [
                        'Jika Anda membutuhkan bantuan teknis, ingin melaporkan bug, mengajukan klarifikasi keamanan domain, atau meminta penghapusan data akun, silakan hubungi kanal resmi kami:',
                    ],
                    'bullets' => [
                        'Pengelola: Muh Alwi Syahrir — Makassar, Sulawesi Selatan, Indonesia',
                        'WhatsApp Dukungan Resmi: +62 821-9503-6971 (https://wa.me/6282195036971)',
                        'Dokumentasi Penggunaan: tersedia terbuka di halaman Panduan Lengkap (/panduan) dan Hub Tools Gratis (/tools).',
                    ],
                ],
            ],
        ];

        return view('legal.page', array_merge($this->getCommonMeta(), [
            'page' => $page,
        ]));
    }
}
