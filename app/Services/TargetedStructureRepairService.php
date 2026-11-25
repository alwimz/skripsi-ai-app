<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;

class TargetedStructureRepairService
{
    public const TARGET_EMAIL = 'mhdandre22@gmail.com';
    public const META_FLAG = 'repaired_mhd_andre_structures_v1';

    /**
     * Pulihkan hanya blok flowchart dan tabel Markdown yang rusak akibat bug innerText lama
     * khusus untuk proyek milik akun mhdandre22@gmail.com, satu kali saja tanpa mengubah teks lain.
     */
    public function repairIfTargetProject(Project $project, ?User $owner = null): bool
    {
        $owner ??= $project->user_id ? User::find($project->user_id) : null;
        if (!$owner || strtolower(trim((string) $owner->email)) !== self::TARGET_EMAIL) {
            return false;
        }

        if (!str_contains(strtolower((string) $project->judul), 'berdiferensiasi')) {
            return false;
        }

        $meta = json_decode((string) $project->citation_validation, true);
        if (!is_array($meta)) {
            $meta = [];
        }
        if (!empty($meta[self::META_FLAG])) {
            return false;
        }

        $changed = false;

        if (!empty($project->bab2_content)) {
            $repairedBab2 = $this->repairBab2((string) $project->bab2_content);
            if ($repairedBab2 !== (string) $project->bab2_content) {
                $project->bab2_content = $repairedBab2;
                $changed = true;
            }
        }

        if (!empty($project->bab3_content)) {
            $repairedBab3 = $this->repairBab3((string) $project->bab3_content);
            if ($repairedBab3 !== (string) $project->bab3_content) {
                $project->bab3_content = $repairedBab3;
                $changed = true;
            }
        }

        $meta[self::META_FLAG] = true;
        $project->citation_validation = json_encode($meta, JSON_UNESCAPED_UNICODE);
        $project->save();

        return $changed;
    }

    private function repairBab2(string $content): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $content);

        // 1. Pulihkan Tabel 2.1 Sintesis Penelitian Terdahulu jika tanda '|' sudah hilang
        $tabel21 = "Tabel 2.1 Sintesis Penelitian Terdahulu\n"
            . "| No | Peneliti & Tahun | Judul Penelitian | Model / Metode | Temuan / Peningkatan | Posisi Penelitian Ini |\n"
            . "|---|---|---|---|---|---|\n"
            . "| 1 | Trinela et al. (2026) | Penerapan Model Pembelajaran Berdiferensiasi pada Mata Pelajaran PAI dan Budi Pekerti di SMP Negeri 2 Limboto | Kualitatif deskriptif | Suasana kelas lebih inklusif dan partisipasi siswa dalam kegiatan keagamaan meningkat | Penelitian ini menguji pengaruh diferensiasi terhadap hasil belajar secara kuantitatif pada konteks sekolah yang berbeda |\n"
            . "| 2 | Haryaty et al. (2025) | Implementasi Model Pembelajaran Berdiferensiasi dalam Kurikulum Merdeka pada Mata Pelajaran PAI dan Budi Pekerti di SMAN 1 Ponorogo | Kualitatif | Siswa belajar sesuai ritme masing-masing tanpa kehilangan esensi materi | Penelitian ini menyasar jenjang SMP kelas VII yang memiliki karakteristik perkembangan berbeda |\n"
            . "| 3 | Ramadhan et al. (2025) | Implementasi Pembelajaran Berdiferensiasi dalam Meningkatkan Motivasi dan Prestasi Belajar pada Mata Pelajaran PAI dan Budi Pekerti di SMPN Kota Batam | Kuantitatif | Motivasi dan prestasi belajar siswa meningkat setelah pemetaan kesiapan belajar | Penelitian ini menguji pengaruh terhadap hasil belajar pada konteks sekolah dengan latar belakang siswa yang lebih beragam |\n"
            . "| 4 | Aini et al. (2025) | Strategi Pembelajaran Berdiferensiasi untuk Meningkatkan Motivasi Belajar Siswa pada Mata Pelajaran Fiqih Jinayah kelas XI MA Ma'arif Roudlotut Tholibin Metro | Kualitatif | Motivasi belajar siswa meningkat pada materi aplikatif | Penelitian ini memfokuskan pada hasil belajar kognitif dan afektif, bukan hanya motivasi |\n"
            . "| 5 | Lisnawati et al. (2024) | Pengaruh Strategi Pembelajaran Berdiferensiasi terhadap Minat Belajar Siswa SD pada Mata Pelajaran Matematika | Kuantitatif | Minat belajar siswa meningkat secara signifikan | Penelitian ini menguji pengaruh pada mata pelajaran PAI dan Budi Pekerti di jenjang SMP |";

        if (!$this->hasMarkdownTableNearCaption($text, 'Tabel\s+2\.1')) {
            if (preg_match('/Tabel\s+2\.1[^\n]*\n+[\s\S]*?(?=\n+(?:##\s*)?2\.3\b|$)/i', $text)) {
                $text = (string) preg_replace(
                    '/Tabel\s+2\.1[^\n]*\n+[\s\S]*?(?=\n+(?:##\s*)?2\.3\b|$)/i',
                    $tabel21 . "\n",
                    $text,
                    1
                );
            }
        }

        // 2. Pulihkan Gambar 2.1 Bagan Kerangka Berpikir Tindakan Pembelajaran jika blok ``` sudah hilang
        $gambar21 = "Gambar 2.1 Bagan Kerangka Berpikir Tindakan Pembelajaran\n"
            . "```\n"
            . "[Kondisi Awal: Hasil Belajar PAI dan Budi Pekerti Rendah (49% Tuntas, Rata-rata 70)]\n"
            . "↓\n"
            . "[Tindakan: Penerapan Strategi Pembelajaran Berdiferensiasi (Konten, Proses, Produk, Lingkungan)]\n"
            . "↓\n"
            . "[Proses: Pemetaan Kebutuhan Belajar, Pelaksanaan Diferensiasi, Umpan Balik Individual]\n"
            . "↓\n"
            . "[Kondisi Akhir: Peningkatan Hasil Belajar Kognitif, Afektif, dan Psikomotorik Siswa]\n"
            . "```";

        if (!preg_match('/```[\s\S]*?Kondisi Awal[\s\S]*?```/i', $text)) {
            if (preg_match('/(?:^|\n)Kondisi Awal\b[\s\S]{10,1400}?(?:Gambar|Bagan)\s+2\.1[^\n]*/i', $text)) {
                $text = (string) preg_replace(
                    '/(?:^|\n)Kondisi Awal\b[\s\S]{10,1400}?(?:Gambar|Bagan)\s+2\.1[^\n]*/i',
                    "\n\n" . $gambar21,
                    $text,
                    1
                );
            } elseif (preg_match('/(?:Gambar|Bagan)\s+2\.1[^\n]*\n+Kondisi Awal\b[\s\S]{10,1400}?Psikomotorik Siswa[^\n]*/i', $text)) {
                $text = (string) preg_replace(
                    '/(?:Gambar|Bagan)\s+2\.1[^\n]*\n+Kondisi Awal\b[\s\S]{10,1400}?Psikomotorik Siswa[^\n]*/i',
                    $gambar21,
                    $text,
                    1
                );
            } elseif (preg_match('/(?:Gambar|Bagan)\s+2\.1[^\n]*/i', $text)) {
                $text = (string) preg_replace(
                    '/(?:Gambar|Bagan)\s+2\.1[^\n]*/i',
                    $gambar21,
                    $text,
                    1
                );
            }
        }

        return trim((string) preg_replace("/\n{3,}/", "\n\n", $text));
    }

    private function repairBab3(string $content): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $content);

        // 1. Tabel 3.1 Jadwal Pelaksanaan Penelitian
        $tabel31 = "Tabel 3.1 Jadwal Pelaksanaan Penelitian\n"
            . "| No | Kegiatan Penelitian | Bulan ke-1 | Bulan ke-2 | Bulan ke-3 | Bulan ke-4 |\n"
            . "|---|---|---|---|---|---|\n"
            . "| 1 | Penyusunan dan konsultasi proposal | ✓ | | | |\n"
            . "| 2 | Penelaahan literatur dan penyusunan instrumen | ✓ | ✓ | | |\n"
            . "| 3 | Uji coba instrumen dan analisis validitas-reliabilitas | | ✓ | | |\n"
            . "| 4 | Pengambilan data (angket dan dokumentasi nilai) | | ✓ | ✓ | |\n"
            . "| 5 | Pengolahan data dan uji hipotesis | | | ✓ | |\n"
            . "| 6 | Penyusunan draf skripsi dan bimbingan | | | ✓ | ✓ |\n"
            . "| 7 | Ujian sidang dan perbaikan akhir | | | | ✓ |";

        if (!$this->hasMarkdownTableNearCaption($text, 'Tabel\s+3\.1')) {
            if (preg_match('/Tabel\s+3\.1[^\n]*\n+[\s\S]*?(?=\n+(?:##\s*)?3\.3\b|$)/i', $text)) {
                $text = (string) preg_replace(
                    '/Tabel\s+3\.1[^\n]*\n+[\s\S]*?(?=\n+(?:##\s*)?3\.3\b|$)/i',
                    $tabel31 . "\n",
                    $text,
                    1
                );
            }
        }

        // 2. Tabel 3.2 Distribusi Populasi dan Sampel Penelitian
        $tabel32 = "Tabel 3.2 Distribusi Populasi dan Sampel Penelitian\n"
            . "| No | Rombongan Belajar | Jumlah Populasi (Nᵢ) | Perhitungan Sampel | Jumlah Sampel (nᵢ) |\n"
            . "|---|---|---|---|---|\n"
            . "| 1 | Kelas VII-A | 30 | (30 / 120) × 92 | 23 |\n"
            . "| 2 | Kelas VII-B | 30 | (30 / 120) × 92 | 23 |\n"
            . "| 3 | Kelas VII-C | 30 | (30 / 120) × 92 | 23 |\n"
            . "| 4 | Kelas VII-D | 30 | (30 / 120) × 92 | 23 |\n"
            . "| | Jumlah | 120 | | 92 |";

        if (!$this->hasMarkdownTableNearCaption($text, 'Tabel\s+3\.2')) {
            if (preg_match('/Tabel\s+3\.2[^\n]*\n+[\s\S]*?(?=\n+(?:##\s*)?3\.4\b|$)/i', $text)) {
                $text = (string) preg_replace(
                    '/Tabel\s+3\.2[^\n]*\n+[\s\S]*?(?=\n+(?:##\s*)?3\.4\b|$)/i',
                    $tabel32 . "\n",
                    $text,
                    1
                );
            }
        }

        // 3. Tabel 3.3 Kisi-kisi Instrumen dan Definisi Operasional Variabel
        $tabel33 = "Tabel 3.3 Kisi-kisi Instrumen dan Definisi Operasional Variabel\n"
            . "| Variabel | Definisi Operasional | Dimensi | Indikator Empiris | Butir | Skala |\n"
            . "|---|---|---|---|---|---|\n"
            . "| Implementasi strategi pembelajaran berdiferensiasi (X) | Tingkat penerapan penyesuaian pembelajaran PAI dan Budi Pekerti terhadap kesiapan, minat, dan gaya belajar siswa sebagaimana dipersepsikan siswa | Diferensiasi konten | Guru menyesuaikan kedalaman materi dengan kemampuan siswa; guru menyediakan sumber belajar beragam | 1–4 | Likert 1–5 |\n"
            . "| | | Diferensiasi proses | Guru memvariasikan kegiatan belajar; guru membentuk kelompok berdasarkan kesiapan; guru memberi pendampingan bagi siswa yang tertinggal | 5–9 | Likert 1–5 |\n"
            . "| | | Diferensiasi produk | Guru memberi pilihan bentuk tugas; guru menilai sesuai kemampuan awal siswa; guru memberi umpan balik individual | 10–13 | Likert 1–5 |\n"
            . "| | | Diferensiasi lingkungan belajar | Guru menata kelas yang mendukung kerja mandiri dan kelompok; guru menciptakan suasana aman untuk bertanya | 14–16 | Likert 1–5 |\n"
            . "| Hasil belajar PAI dan Budi Pekerti (Y) | Capaian kompetensi kognitif siswa pada mata pelajaran PAI dan Budi Pekerti yang diperoleh dari nilai tes akhir semester ganjil tahun ajaran 2025/2026 | Kognitif (C1–C4) | Nilai tes akhir semester pada ranah mengingat, memahami, menerapkan, dan menganalisis | Dokumentasi nilai | Interval 0–100 |";

        if (!$this->hasMarkdownTableNearCaption($text, 'Tabel\s+3\.3')) {
            if (preg_match('/Tabel\s+3\.3[^\n]*\n+[\s\S]*?(?=\n+(?:##\s*)?3\.5\b|$)/i', $text)) {
                $text = (string) preg_replace(
                    '/Tabel\s+3\.3[^\n]*\n+[\s\S]*?(?=\n+(?:##\s*)?3\.5\b|$)/i',
                    $tabel33 . "\n",
                    $text,
                    1
                );
            }
        }

        // 4. Gambar 3.1 Diagram Alur Prosedur Penelitian
        $gambar31 = "Gambar 3.1 Diagram Alur Prosedur Penelitian\n"
            . "```\n"
            . "[MULAI]\n"
            . "↓\n"
            . "[Observasi Awal & Penyusunan Perangkat Pembelajaran]\n"
            . "↓\n"
            . "[Uji Validitas & Reliabilitas Instrumen Tes]\n"
            . "↓\n"
            . "[Pelaksanaan Pretest & Pengumpulan Data Awal]\n"
            . "↓\n"
            . "[Penerapan Perlakuan / Tindakan Model Pembelajaran]\n"
            . "↓\n"
            . "[Pelaksanaan Posttest & Observasi Kelas]\n"
            . "↓\n"
            . "[Evaluasi & Uji Hipotesis (Uji-t & N-Gain Score)]\n"
            . "↓\n"
            . "[Penyusunan Laporan Hasil Penelitian]\n"
            . "↓\n"
            . "[SELESAI]\n"
            . "```";

        if (!preg_match('/```[\s\S]*?(?:MULAI|Observasi Awal)[\s\S]*?```/i', $text)) {
            if (preg_match('/(?:^|\n)(?:MULAI|Mulai)\b[\s\S]{10,1800}?(?:SELESAI|Selesai)[\s\n]+Gambar\s+3\.1[^\n]*/i', $text)) {
                $text = (string) preg_replace(
                    '/(?:^|\n)(?:MULAI|Mulai)\b[\s\S]{10,1800}?(?:SELESAI|Selesai)[\s\n]+Gambar\s+3\.1[^\n]*/i',
                    "\n\n" . $gambar31,
                    $text,
                    1
                );
            } elseif (preg_match('/Gambar\s+3\.1[^\n]*\n+(?:MULAI|Mulai)\b[\s\S]{10,1800}?(?:SELESAI|Selesai)[^\n]*/i', $text)) {
                $text = (string) preg_replace(
                    '/Gambar\s+3\.1[^\n]*\n+(?:MULAI|Mulai)\b[\s\S]{10,1800}?(?:SELESAI|Selesai)[^\n]*/i',
                    $gambar31,
                    $text,
                    1
                );
            } elseif (preg_match('/Gambar\s+3\.1[^\n]*/i', $text)) {
                $text = (string) preg_replace(
                    '/Gambar\s+3\.1[^\n]*/i',
                    $gambar31,
                    $text,
                    1
                );
            }
        }

        return trim((string) preg_replace("/\n{3,}/", "\n\n", $text));
    }

    private function hasMarkdownTableNearCaption(string $text, string $captionRegex): bool
    {
        if (!preg_match('/' . $captionRegex . '[^\n]*\n+([^\n]+\n[^\n]+)/i', $text, $m)) {
            return false;
        }
        $nextTwoLines = $m[1];
        return substr_count($nextTwoLines, '|') >= 4;
    }
}
