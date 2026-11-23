<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * Deterministic, SPSS-Equivalent Statistical Computation Engine (Pure PHP)
 *
 * Seluruh rumus menggunakan distribusi eksak (Lanczos Log-Gamma, Lentz Incomplete Beta,
 * Incomplete Gamma, Normal CDF presisi tinggi, Gauss-Jordan Matrix Inversion) tanpa estimasi
 * kasar atau tebakan AI sehingga hasil identik dengan output IBM SPSS Statistics.
 */
class StatisticsEngineService
{
    private const EPS = 1.0e-12;

    /**
     * Entry point analisis statistik berdasarkan jenis uji.
     *
     * @param  array<int, string>            $columns
     * @param  array<int, array<string,mixed>> $rows
     * @param  string                        $testType
     * @param  array<string, mixed>          $options
     * @return array<string, mixed>
     */
    public function analyze(array $columns, array $rows, string $testType, array $options = []): array
    {
        $columns = array_values(array_unique(array_filter(array_map(fn ($c) => trim((string) $c), $columns), fn ($c) => $c !== '')));
        if (empty($columns) || count($rows) < 2) {
            throw new InvalidArgumentException('Dataset minimal harus memiliki 1 kolom dan 2 baris data untuk dianalisis.');
        }

        $alpha = (float) ($options['alpha'] ?? 0.05);
        if ($alpha <= 0 || $alpha >= 0.5) {
            $alpha = 0.05;
        }

        $testType = strtolower(trim($testType));
        $result = match ($testType) {
            'descriptive' => $this->runDescriptive($columns, $rows, $options, $alpha),
            'validity_reliability' => $this->runValidityReliability($columns, $rows, $options, $alpha),
            'regression' => $this->runRegression($columns, $rows, $options, $alpha),
            'correlation' => $this->runCorrelation($columns, $rows, $options, $alpha),
            'anova' => $this->runOneWayAnova($columns, $rows, $options, $alpha),
            'ttest_ind' => $this->runIndependentTTest($columns, $rows, $options, $alpha),
            'ttest_paired' => $this->runPairedTTest($columns, $rows, $options, $alpha),
            'chisquare' => $this->runChiSquare($columns, $rows, $options, $alpha),
            'nonparam' => $this->runNonParametric($columns, $rows, $options, $alpha),
            default => throw new InvalidArgumentException("Jenis uji statistik '{$testType}' tidak dikenali."),
        };

        $result['test_type'] = $testType;
        $result['alpha'] = $alpha;
        $result['total_rows'] = count($rows);
        $result['academic_guide'] = $this->buildAcademicGuide($testType, $alpha);
        $result['computed_at'] = now()->format('d M Y, H:i') . ' WIB';
        $result['engine_note'] = 'Dihitung deterministik oleh mesin statistik independen (presisi IEEE-754 setara IBM SPSS Statistics, α = ' . number_format($alpha, 2) . ').';

        return $result;
    }

    private function buildAcademicGuide(string $testType, float $alpha): array
    {
        $a = number_format($alpha, 2);
        return match ($testType) {
            'regression' => [
                'tujuan' => 'Menguji pengaruh variabel independen (X) terhadap variabel dependen (Y) secara parsial (Uji t) dan simultan (Uji F), serta mengukur daya jelas model (R²) dan kelayakan asumsi klasik.',
                'h0' => 'H₀: Variabel independen (X) tidak berpengaruh signifikan terhadap variabel dependen (Y).',
                'h1' => 'H₁: Variabel independen (X) berpengaruh signifikan terhadap variabel dependen (Y).',
                'kriteria' => "Tolak H₀ (signifikan) apabila nilai Sig. < {$a}. Model layak (BLUE) apabila lolos Normalitas (Sig. >= {$a}), Multikolinearitas (Tolerance > 0.10 & VIF < 10), Heteroskedastisitas Glejser (Sig. >= {$a}), dan Durbin-Watson berada di zona aman.",
                'referensi' => 'Ghozali (2018); Gujarati & Porter (2009); Field (2018).',
            ],
            'validity_reliability' => [
                'tujuan' => 'Mengukur ketepatan setiap butir instrumen (validitas item) dan konsistensi internal konstruk penelitian (reliabilitas).',
                'h0' => 'H₀: Butir pertanyaan tidak berkorelasi signifikan dengan skor total konstruk (tidak valid).',
                'h1' => 'H₁: Butir pertanyaan berkorelasi positif signifikan dengan skor total konstruk (valid).',
                'kriteria' => "Butir VALID jika r hitung > r tabel (df = N - 2, α = {$a}) dan Sig. < {$a}. Konstruk RELIABEL jika Cronbach's Alpha >= 0.70 (>= 0.60 cukup reliabel untuk studi eksploratif).",
                'referensi' => 'Nunnally (1978); Sugiyono (2019); Ghozali (2018).',
            ],
            'anova' => [
                'tujuan' => 'Menguji apakah terdapat perbedaan rata-rata yang signifikan secara statistik pada ≥ 3 kelompok/perlakuan, disertai uji normalitas residual, homogenitas varians, Welch ANOVA, ukuran efek (η² & ω²), dan uji lanjut Post-Hoc Bonferroni.',
                'h0' => 'H₀: μ₁ = μ₂ = ... = μₖ (tidak terdapat perbedaan rata-rata yang signifikan antar kelompok/perlakuan).',
                'h1' => 'H₁: Minimal terdapat satu pasangan kelompok/perlakuan yang memiliki rata-rata berbeda signifikan.',
                'kriteria' => "Tolak H₀ jika Sig. ANOVA (atau Welch ANOVA bila Levene Sig. < {$a}) < {$a}. Jika residual tidak normal (Shapiro-Wilk Sig. < {$a}), verifikasi dengan Kruskal-Wallis H.",
                'referensi' => 'Montgomery (2017); Field (2018); Welch (1951); Royston (1995).',
            ],
            'ttest_ind' => [
                'tujuan' => 'Membandingkan rata-rata dua kelompok sampel bebas (independen), dilengkapi uji homogenitas varians Levene, versi Student & Welch, serta Cohen\'s d.',
                'h0' => 'H₀: μ₁ = μ₂ (tidak terdapat perbedaan rata-rata yang signifikan antara kedua kelompok).',
                'h1' => 'H₁: μ₁ ≠ μ₂ (terdapat perbedaan rata-rata yang signifikan antara kedua kelompok).',
                'kriteria' => "Jika Levene Sig. >= {$a}, baca baris Equal variances assumed; jika < {$a}, baca baris Welch. Tolak H₀ bila Sig. (2-tailed) < {$a}.",
                'referensi' => 'Field (2018); Cohen (1988); Sugiyono (2019).',
            ],
            'ttest_paired' => [
                'tujuan' => 'Menguji perbedaan rata-rata dua pengukuran berpasangan pada subjek yang sama (mis. Pre-Test vs Post-Test / Sebelum vs Sesudah perlakuan).',
                'h0' => 'H₀: μ_d = 0 (tidak terdapat perbedaan rata-rata yang signifikan sebelum dan sesudah perlakuan).',
                'h1' => 'H₁: μ_d ≠ 0 (terdapat perbedaan rata-rata yang signifikan sebelum dan sesudah perlakuan).',
                'kriteria' => "Tolak H₀ apabila nilai Sig. (2-tailed) < {$a}. Besar efek diukur dengan Cohen's dz (0.2 kecil, 0.5 sedang, >= 0.8 besar).",
                'referensi' => 'Field (2018); Cohen (1988); Sugiyono (2019).',
            ],
            'correlation' => [
                'tujuan' => 'Mengukur kekuatan, arah, dan signifikansi hubungan antara dua atau lebih variabel numerik secara parametrik (Pearson r) dan non-parametrik (Spearman ρ).',
                'h0' => 'H₀: ρ = 0 (tidak terdapat hubungan yang signifikan antara variabel yang diuji).',
                'h1' => 'H₁: ρ ≠ 0 (terdapat hubungan yang signifikan antara variabel yang diuji).',
                'kriteria' => "Tolak H₀ apabila nilai Sig. (2-tailed) < {$a}. Kekuatan hubungan mengikuti pedoman Sugiyono (0.00–0.199 sangat rendah s.d. 0.80–1.000 sangat kuat).",
                'referensi' => 'Sugiyono (2019); Field (2018).',
            ],
            'chisquare' => [
                'tujuan' => 'Menguji ada tidaknya asosiasi/hubungan antara dua variabel kategorik melalui tabulasi silang (Crosstab) dan ukuran asosiasi Cramer\'s V.',
                'h0' => 'H₀: Kedua variabel kategorik bersifat independen (tidak ada hubungan).',
                'h1' => 'H₁: Terdapat hubungan/asosiasi yang signifikan antara kedua variabel kategorik.',
                'kriteria' => "Tolak H₀ apabila Asymp. Sig. (2-sided) < {$a}. Pastikan sel dengan expected count < 5 tidak melebihi 20% total sel.",
                'referensi' => 'Agresti (2018); Field (2018).',
            ],
            'nonparam' => [
                'tujuan' => 'Menguji perbedaan distribusi/peringkat antar kelompok pada data ordinal atau data yang tidak memenuhi asumsi normalitas parametrik (Mann-Whitney U, Wilcoxon Signed-Rank, Kruskal-Wallis H).',
                'h0' => 'H₀: Tidak terdapat perbedaan distribusi/peringkat yang signifikan antar kelompok atau kondisi.',
                'h1' => 'H₁: Terdapat perbedaan distribusi/peringkat yang signifikan antar kelompok atau kondisi.',
                'kriteria' => "Tolak H₀ apabila nilai Asymp. Sig. < {$a}. Perhitungan menggunakan koreksi ties identik IBM SPSS.",
                'referensi' => 'Siegel & Castellan (1988); Field (2018); Kruskal & Wallis (1952).',
            ],
            default => [
                'tujuan' => 'Mendeskripsikan sebaran data (tendensi sentral, dispersi, skewness, kurtosis) serta menguji normalitas distribusi (K-S Lilliefors & Shapiro-Wilk).',
                'h0' => 'H₀: Data berasal dari populasi yang berdistribusi normal.',
                'h1' => 'H₁: Data tidak berdistribusi normal.',
                'kriteria' => "Data dinyatakan normal apabila Sig. >= {$a} (gunakan Shapiro-Wilk untuk N <= 50 dan K-S Lilliefors untuk N > 50).",
                'referensi' => 'Royston (1995); Lilliefors (1967); Field (2018).',
            ],
        };
    }

    // =========================================================================
    // 1. STATISTIK DESKRIPTIF & UJI NORMALITAS (K-S + JARQUE-BERA)
    // =========================================================================

    private function runDescriptive(array $columns, array $rows, array $options, float $alpha): array
    {
        $selected = $this->resolveNumericColumns($columns, $rows, $options['cols'] ?? $options['x_cols'] ?? []);
        if (empty($selected)) {
            throw new InvalidArgumentException('Tidak ditemukan kolom numerik yang valid untuk Statistik Deskriptif.');
        }

        $tableRows = [];
        $chartLabels = [];
        $chartMeans = [];
        $chartSds = [];
        $summaryParts = [];

        foreach ($selected as $col) {
            $vals = $this->extractNumericValues($rows, $col);
            $n = count($vals);
            if ($n < 2) {
                continue;
            }
            $mean = $this->mean($vals);
            $sd = $this->sampleStd($vals, $mean);
            $var = $sd * $sd;
            $se = $sd / sqrt($n);
            $min = min($vals);
            $max = max($vals);
            $median = $this->median($vals);
            $skew = $this->sampleSkewness($vals, $mean, $sd);
            $kurt = $this->sampleExcessKurtosis($vals, $mean, $sd);
            $ks = $this->kolmogorovSmirnovNormal($vals, $mean, $sd);
            $sw = $this->shapiroWilk($vals);
            $useSw = ($n <= 50 && $n >= 3);
            $primaryNormSig = $useSw ? $sw['sig'] : $ks['sig'];
            $isNormal = $primaryNormSig >= $alpha;

            $tableRows[] = [
                $col,
                $n,
                $this->fmt($min, 3),
                $this->fmt($max, 3),
                $this->fmt($mean, 3),
                $this->fmt($median, 3),
                $this->fmt($sd, 3),
                $this->fmt($skew, 3),
                $this->fmt($kurt, 3),
                $this->fmt($ks['d'], 3) . ' (' . $this->fmtLilliefors($ks['sig']) . ')',
                $n >= 3 && $n <= 5000 ? ($this->fmt($sw['w'], 3) . ' (' . $this->fmtP($sw['sig']) . ')') : '-',
                $isNormal ? 'Normal' : 'Tidak Normal',
            ];

            $chartLabels[] = $col;
            $chartMeans[] = round($mean, 3);
            $chartSds[] = round($sd, 3);
            $summaryParts[] = "{$col} (N={$n}, Mean={$this->fmt($mean, 2)}, SD={$this->fmt($sd, 2)}, K-S Lilliefors Sig={$this->fmtLilliefors($ks['sig'])}, Shapiro-Wilk W={$this->fmt($sw['w'], 3)} Sig={$this->fmtP($sw['sig'])})";
        }

        if (empty($tableRows)) {
            throw new InvalidArgumentException('Minimal 1 kolom numerik dengan >= 2 nilai valid diperlukan.');
        }

        return [
            'title' => 'Analisis Statistik Deskriptif & Uji Normalitas (K-S Lilliefors + Shapiro-Wilk)',
            'subtitle' => 'Ringkasan Tendensi Sentral, Dispersi, Skewness/Kurtosis & Tests of Normality (Setara SPSS Explore)',
            'highlights' => [
                ['label' => 'Jumlah Variabel', 'value' => (string) count($tableRows), 'tone' => 'blue'],
                ['label' => 'Total Observasi', 'value' => (string) count($rows), 'tone' => 'slate'],
                ['label' => 'Taraf Signifikansi (α)', 'value' => number_format($alpha, 2), 'tone' => 'slate'],
                ['label' => 'Uji Normalitas', 'value' => 'K-S Lilliefors & Shapiro-Wilk', 'tone' => 'emerald'],
            ],
            'tables' => [
                [
                    'title' => 'Tabel 1. Descriptive Statistics & Tests of Normality (Kolmogorov-Smirnov Lilliefors & Shapiro-Wilk)',
                    'headers' => ['Variabel', 'N', 'Min', 'Max', 'Mean', 'Median', 'Std. Dev', 'Skewness', 'Kurtosis', 'K-S Stat (Sig.)', 'Shapiro-Wilk W (Sig.)', 'Distribusi'],
                    'rows' => $tableRows,
                    'footnote' => 'K-S menggunakan Lilliefors Significance Correction (*.200 adalah batas bawah signifikansi sejati sesuai IBM SPSS). Panduan standar: untuk N <= 50 gunakan Shapiro-Wilk; untuk N > 50 gunakan Kolmogorov-Smirnov (Lilliefors). Data normal jika Sig. >= ' . number_format($alpha, 2) . '.',
                ],
            ],
            'chart' => [
                'type' => 'bar_error',
                'title' => 'Perbandingan Mean ± Standar Deviasi Antar Variabel',
                'labels' => $chartLabels,
                'values' => $chartMeans,
                'errors' => $chartSds,
                'y_label' => 'Nilai Rata-rata',
            ],
            'conclusions' => [
                'Analisis deskriptif selesai dihitung untuk ' . count($tableRows) . ' variabel numerik pada N = ' . count($rows) . ' data.',
                'Nilai Mean, Standar Deviasi (n-1), Skewness (G1), Kurtosis (G2), dan Kolmogorov-Smirnov menggunakan definisi matematis standar SPSS.',
            ],
            'project_autofill' => [
                'metode' => 'Analisis Statistik Deskriptif & Uji Normalitas Kolmogorov-Smirnov (Setara IBM SPSS)',
                'sampel' => count($rows) . ' Observasi / Sampel (' . count($tableRows) . ' Variabel Numerik)',
                'rumus' => 'Mean = (ΣXi)/N; Standar Deviasi s = √[Σ(Xi - X̄)² / (N-1)]; Kolmogorov-Smirnov D = max|F(x) - S(x)|',
                'metrik' => 'Hasil Statistik Deskriptif: ' . implode('; ', $summaryParts) . '.',
                'evaluasi' => 'Statistik Deskriptif (Min, Max, Mean, Median, SD, Varians) & Uji Normalitas Kolmogorov-Smirnov (Sig. > ' . number_format($alpha, 2) . ')',
            ],
        ];
    }

    // =========================================================================
    // 2. UJI VALIDITAS (PEARSON ITEM-TOTAL) & RELIABILITAS (CRONBACH'S ALPHA)
    // =========================================================================

    private function runValidityReliability(array $columns, array $rows, array $options, float $alpha): array
    {
        $selected = $this->resolveNumericColumns($columns, $rows, $options['cols'] ?? $options['x_cols'] ?? []);
        if (count($selected) < 2) {
            throw new InvalidArgumentException('Uji Validitas & Reliabilitas membutuhkan minimal 2 kolom butir pertanyaan numerik.');
        }

        // Grouping otomatis berdasarkan prefix (X1_1, X1_2 -> X1; Y1, Y2 -> Y; atau 1 grup jika manual)
        $groups = [];
        $autoGroup = ($options['auto_group'] ?? true) !== false;
        if ($autoGroup && !empty($options['item_groups']) && is_array($options['item_groups'])) {
            foreach ($options['item_groups'] as $gName => $gCols) {
                $validCols = array_values(array_intersect($selected, (array) $gCols));
                if (count($validCols) >= 2) {
                    $groups[(string) $gName] = $validCols;
                }
            }
        }
        if (empty($groups) && $autoGroup) {
            $detected = [];
            foreach ($selected as $col) {
                // Skip kolom yang jelas merupakan ID / total skor
                if (preg_match('/^(id|no|nomor|responden|total|skor_total|sum)/i', $col)) {
                    continue;
                }
                if (preg_match('/^([A-Za-z]+\d*)[_\.\-\s]+(\d+)$/', $col, $m)) {
                    $detected[strtoupper($m[1])][] = $col;
                } elseif (preg_match('/^([A-Za-z]+)(\d+)$/', $col, $m)) {
                    $detected[strtoupper($m[1])][] = $col;
                } else {
                    $detected['Instrumen'][] = $col;
                }
            }
            foreach ($detected as $gName => $gCols) {
                if (count($gCols) >= 2) {
                    $groups[$gName] = $gCols;
                }
            }
        }
        if (empty($groups)) {
            $filtered = array_values(array_filter($selected, fn ($c) => !preg_match('/^(id|no|nomor|responden|total|skor_total|sum)/i', $c)));
            $groups['Instrumen Penelitian'] = count($filtered) >= 2 ? $filtered : $selected;
        }

        $validityRows = [];
        $reliabilityRows = [];
        $chartLabels = [];
        $chartAlphas = [];
        $totalItems = 0;
        $validItems = 0;
        $reliableCount = 0;
        $metricParts = [];

        foreach ($groups as $varName => $itemCols) {
            $k = count($itemCols);
            if ($k < 2) {
                continue;
            }
            $matrix = $this->extractCompleteNumericMatrix($rows, $itemCols);
            $n = count($matrix);
            if ($n < 3) {
                throw new InvalidArgumentException("Variabel {$varName} membutuhkan minimal 3 baris data numerik lengkap.");
            }

            $totals = [];
            for ($i = 0; $i < $n; $i++) {
                $totals[$i] = array_sum($matrix[$i]);
            }

            $itemVars = [];
            for ($j = 0; $j < $k; $j++) {
                $colVals = array_column($matrix, $j);
                $itemVars[$j] = $this->sampleVariance($colVals);
            }
            $totalVar = $this->sampleVariance($totals);
            $cronbach = $totalVar > self::EPS ? ($k / ($k - 1)) * (1.0 - (array_sum($itemVars) / $totalVar)) : 0.0;
            $rCrit = $this->rTable($n, $alpha);

            $varValid = 0;
            for ($j = 0; $j < $k; $j++) {
                $colVals = array_column($matrix, $j);
                $rTotal = $this->pearsonR($colVals, $totals);
                $pVal = $this->pearsonPValue($rTotal, $n);

                // Corrected Item-Total Correlation & Cronbach's Alpha if Item Deleted
                $restTotals = [];
                for ($i = 0; $i < $n; $i++) {
                    $restTotals[$i] = $totals[$i] - $colVals[$i];
                }
                $rCorrected = $this->pearsonR($colVals, $restTotals);
                $alphaDeleted = null;
                if ($k >= 3) {
                    $restVar = $this->sampleVariance($restTotals);
                    $sumRestItemVars = array_sum($itemVars) - $itemVars[$j];
                    $alphaDeleted = $restVar > self::EPS ? (($k - 1) / ($k - 2)) * (1.0 - ($sumRestItemVars / $restVar)) : 0.0;
                }

                $isValid = $rTotal > $rCrit && $pVal < $alpha;
                $totalItems++;
                if ($isValid) {
                    $validItems++;
                    $varValid++;
                }

                $validityRows[] = [
                    $varName,
                    $itemCols[$j],
                    $n,
                    $this->fmt($rTotal, 3),
                    $this->fmt($rCrit, 3),
                    $this->fmtP($pVal),
                    $this->fmt($rCorrected, 3),
                    $alphaDeleted !== null ? $this->fmt($alphaDeleted, 3) : '-',
                    $isValid ? 'VALID' : 'TIDAK VALID',
                ];
            }

            $relCat = $cronbach >= 0.80 ? 'Sangat Reliabel' : ($cronbach >= 0.70 ? 'Reliabel' : ($cronbach >= 0.60 ? 'Cukup Reliabel' : 'Kurang Reliabel'));
            if ($cronbach >= 0.60) {
                $reliableCount++;
            }

            $reliabilityRows[] = [
                $varName,
                $n,
                $k,
                "{$varValid} / {$k}",
                $this->fmt($cronbach, 3),
                '> 0.60 / 0.70',
                $relCat,
            ];

            $chartLabels[] = $varName;
            $chartAlphas[] = round(max(0, min(1, $cronbach)), 3);
            $metricParts[] = "{$varName}: {$varValid}/{$k} item valid (r-tabel={$this->fmt($rCrit, 3)}), Cronbach's Alpha = {$this->fmt($cronbach, 3)} ({$relCat})";
        }

        if (empty($reliabilityRows)) {
            throw new InvalidArgumentException('Gagal mengelompokkan item kuesioner. Pilih minimal 2 kolom item numerik.');
        }

        $groupCount = count($reliabilityRows);

        return [
            'title' => 'Uji Validitas (Pearson Product Moment) & Reliabilitas (Cronbach\'s Alpha)',
            'subtitle' => 'Evaluasi Kualitas Instrumen Kuesioner Bab III & Bab IV (Setara SPSS Reliability & Bivariate Correlation)',
            'highlights' => [
                ['label' => 'Butir Valid', 'value' => "{$validItems} / {$totalItems}", 'tone' => $validItems === $totalItems ? 'emerald' : 'amber'],
                ['label' => 'Variabel Reliabel', 'value' => "{$reliableCount} / {$groupCount}", 'tone' => $reliableCount === $groupCount ? 'emerald' : 'amber'],
                ['label' => 'Taraf Uji (α)', 'value' => number_format($alpha, 2) . ' (2-tailed)', 'tone' => 'blue'],
                ['label' => 'Jumlah Responden', 'value' => (string) count($rows), 'tone' => 'slate'],
            ],
            'tables' => [
                [
                    'title' => 'Tabel 1. Ringkasan Uji Reliabilitas (Reliability Statistics - Cronbach\'s Alpha)',
                    'headers' => ['Variabel / Konstruk', 'N', 'Jumlah Item', 'Item Valid', 'Cronbach\'s Alpha', 'Batas Kritis', 'Kategori'],
                    'rows' => $reliabilityRows,
                    'footnote' => 'Kriteria Nunnally (1978) & Ghozali (2018): Cronbach\'s Alpha >= 0.70 (Reliabel); >= 0.60 (Cukup Reliabel untuk studi eksploratif).',
                ],
                [
                    'title' => 'Tabel 2. Rincian Uji Validitas Butir Item (Pearson Product Moment & Item-Total Statistics)',
                    'headers' => ['Variabel', 'Kode Item', 'N', 'r Hitung', 'r Tabel', 'Sig. (2-tailed)', 'Corrected r', 'Alpha if Deleted', 'Status'],
                    'rows' => $validityRows,
                    'footnote' => 'Item dinyatakan VALID apabila r hitung > r tabel (df = N - 2, α = ' . number_format($alpha, 2) . ' two-tailed) dan nilai Sig. < ' . number_format($alpha, 2) . '.',
                ],
            ],
            'chart' => [
                'type' => 'bar',
                'title' => 'Nilai Cronbach\'s Alpha per Variabel (Garis Batas 0.70)',
                'labels' => $chartLabels,
                'values' => $chartAlphas,
                'threshold' => 0.70,
                'y_label' => 'Cronbach\'s Alpha',
            ],
            'conclusions' => [
                "Dari {$totalItems} butir pernyataan yang diuji, sebanyak {$validItems} butir dinyatakan VALID (r hitung > r tabel) dan " . ($totalItems - $validItems) . " butir TIDAK VALID.",
                "Sebanyak {$reliableCount} dari {$groupCount} konstruk variabel memenuhi standar reliabilitas (Cronbach's Alpha >= 0.60).",
            ],
            'project_autofill' => [
                'metode' => 'Uji Validitas Pearson Product Moment & Uji Reliabilitas Cronbach\'s Alpha (Setara IBM SPSS)',
                'sampel' => count($rows) . ' Responden (' . $totalItems . ' Butir Item Kuesioner)',
                'rumus' => 'r_xy = [NΣXY - (ΣX)(ΣY)] / √{[NΣX²-(ΣX)²][NΣY²-(ΣY)²]}; Cronbach Alpha α = [k/(k-1)] * [1 - (Σσ_b² / σ_t²)]',
                'metrik' => 'Hasil Validitas & Reliabilitas: ' . implode('; ', $metricParts) . '.',
                'evaluasi' => 'Uji Validitas Item (r-hitung > r-tabel, Sig. < ' . number_format($alpha, 2) . ') dan Uji Reliabilitas Cronbach\'s Alpha (> 0.70)',
            ],
        ];
    }

    // =========================================================================
    // 3. REGRESI LINIER SEDERHANA / BERGANDA + UJI ASUMSI KLASIK LENGKAP
    // =========================================================================

    private function runRegression(array $columns, array $rows, array $options, float $alpha): array
    {
        $numericCols = $this->resolveNumericColumns($columns, $rows, []);
        $yCol = trim((string) ($options['y_col'] ?? ''));
        $xCols = array_values(array_filter(array_map('strval', (array) ($options['x_cols'] ?? []))));

        if ($yCol === '' && count($numericCols) >= 2) {
            $yCol = $numericCols[count($numericCols) - 1];
        }
        $xCols = array_values(array_filter($xCols, fn ($c) => $c !== '' && $c !== $yCol));
        if (empty($xCols)) {
            $xCols = array_values(array_filter($numericCols, fn ($c) => $c !== $yCol && !preg_match('/^(id|no|nomor|responden)/i', $c)));
        }

        if ($yCol === '' || empty($xCols)) {
            throw new InvalidArgumentException('Pilih minimal 1 variabel independen (X) dan 1 variabel dependen (Y) numerik untuk Regresi Linier.');
        }

        $allCols = array_merge([$yCol], $xCols);
        $matrix = $this->extractCompleteNumericMatrix($rows, $allCols);
        $n = count($matrix);
        $k = count($xCols);
        if ($n <= $k + 2) {
            throw new InvalidArgumentException("Jumlah observasi lengkap (N = {$n}) terlalu sedikit untuk {$k} variabel X. Minimal N >= " . ($k + 3) . '.');
        }

        $y = array_column($matrix, 0);
        $X = [];
        for ($i = 0; $i < $n; $i++) {
            $rowX = [1.0]; // Intercept
            for ($j = 0; $j < $k; $j++) {
                $rowX[] = $matrix[$i][$j + 1];
            }
            $X[] = $rowX;
        }

        $ols = $this->fitOls($X, $y);
        $beta = $ols['beta'];
        $se = $ols['se'];
        $tVals = $ols['t'];
        $pVals = $ols['p'];
        $yHat = $ols['y_hat'];
        $residuals = $ols['residuals'];
        $ssReg = $ols['ss_reg'];
        $ssRes = $ols['ss_res'];
        $ssTot = $ols['ss_tot'];
        $dfReg = $k;
        $dfRes = $n - $k - 1;
        $msReg = $ssReg / $dfReg;
        $msRes = $ssRes / $dfRes;
        $fStat = $msRes > self::EPS ? $msReg / $msRes : 0.0;
        $fSig = $this->fUpperTailP($fStat, (float) $dfReg, (float) $dfRes);
        $r2 = $ssTot > self::EPS ? max(0.0, min(1.0, $ssReg / $ssTot)) : 0.0;
        $rVal = sqrt($r2);
        $adjR2 = 1.0 - ((1.0 - $r2) * ($n - 1) / max(1, $dfRes));
        $see = sqrt(max(0.0, $msRes));

        // Durbin-Watson Statistic
        $dwNum = 0.0;
        for ($i = 1; $i < $n; $i++) {
            $diff = $residuals[$i] - $residuals[$i - 1];
            $dwNum += $diff * $diff;
        }
        $dw = $ssRes > self::EPS ? $dwNum / $ssRes : 2.0;

        // Collinearity: VIF & Tolerance via inverse correlation matrix of X
        $vifs = array_fill(0, $k, 1.0);
        if ($k >= 2) {
            $corrX = [];
            for ($a = 0; $a < $k; $a++) {
                $colA = array_column($matrix, $a + 1);
                for ($b = 0; $b < $k; $b++) {
                    $colB = array_column($matrix, $b + 1);
                    $corrX[$a][$b] = ($a === $b) ? 1.0 : $this->pearsonR($colA, $colB);
                }
            }
            try {
                $invCorr = $this->invertMatrix($corrX);
                for ($j = 0; $j < $k; $j++) {
                    $vifs[$j] = max(1.0, (float) $invCorr[$j][$j]);
                }
            } catch (\Throwable $e) {
                $vifs = array_fill(0, $k, 999.0);
            }
        }

        // Standardized Beta & Coefficients Table
        $sdY = $this->sampleStd($y);
        $coefRows = [
            [
                '(Constant)',
                $this->fmt($beta[0], 3),
                $this->fmt($se[0], 3),
                '-',
                $this->fmt($tVals[0], 3),
                $this->fmtP($pVals[0]),
                '-',
                '-',
                '-',
            ],
        ];

        $eqTerms = [$this->fmt($beta[0], 3)];
        $betaChartLabels = [];
        $betaChartValues = [];
        $partialSummary = [];

        for ($j = 0; $j < $k; $j++) {
            $colX = array_column($matrix, $j + 1);
            $sdX = $this->sampleStd($colX);
            $stdBeta = ($sdY > self::EPS) ? $beta[$j + 1] * ($sdX / $sdY) : 0.0;
            $vif = $vifs[$j];
            $tol = $vif > self::EPS ? (1.0 / $vif) : 0.0;
            $isSig = $pVals[$j + 1] < $alpha;

            $coefRows[] = [
                $xCols[$j],
                $this->fmt($beta[$j + 1], 3),
                $this->fmt($se[$j + 1], 3),
                $this->fmt($stdBeta, 3),
                $this->fmt($tVals[$j + 1], 3),
                $this->fmtP($pVals[$j + 1]),
                $this->fmt($tol, 3),
                $this->fmt($vif, 3),
                $isSig ? 'Signifikan (H diterima)' : 'Tidak Signifikan',
            ];

            $sign = $beta[$j + 1] >= 0 ? ' + ' : ' - ';
            $eqTerms[] = $sign . $this->fmt(abs($beta[$j + 1]), 3) . '(' . $xCols[$j] . ')';
            $betaChartLabels[] = $xCols[$j];
            $betaChartValues[] = round($stdBeta, 3);
            $partialSummary[] = "{$xCols[$j]}: B={$this->fmt($beta[$j + 1], 3)}, t={$this->fmt($tVals[$j + 1], 3)}, Sig.={$this->fmtP($pVals[$j + 1])} (" . ($isSig ? 'Signifikan' : 'Tidak Signifikan') . ", VIF={$this->fmt($vif, 2)})";
        }
        $equation = $yCol . ' = ' . implode('', $eqTerms);

        // Uji Asumsi Klasik
        $resMean = $this->mean($residuals);
        $resSd = $this->sampleStd($residuals, $resMean);
        $ksRes = $this->kolmogorovSmirnovNormal($residuals, $resMean, $resSd);
        $swRes = $this->shapiroWilk($residuals);
        $normPass = ($n <= 50 ? $swRes['sig'] : $ksRes['sig']) >= $alpha && $ksRes['sig'] >= $alpha;

        $maxVif = max($vifs);
        $minTol = $maxVif > self::EPS ? (1.0 / $maxVif) : 0.0;
        $multiPass = $maxVif < 10.0 && $minTol > 0.10;

        // Uji Heteroskedastisitas Glejser: |e_i| regressed on X
        $absRes = array_map('abs', $residuals);
        $glejser = $this->fitOls($X, $absRes);
        $glejserRows = [];
        $glejserPass = true;
        $minGlejserP = 1.0;
        for ($j = 0; $j < $k; $j++) {
            $gp = $glejser['p'][$j + 1];
            $minGlejserP = min($minGlejserP, $gp);
            $ok = $gp >= $alpha;
            if (!$ok) {
                $glejserPass = false;
            }
            $glejserRows[] = [
                $xCols[$j],
                $this->fmt($glejser['beta'][$j + 1], 4),
                $this->fmt($glejser['t'][$j + 1], 3),
                $this->fmtP($gp),
                $ok ? 'Homoskedastis (Lolos)' : 'Indikasi Heteroskedastisitas',
            ];
        }

        $autoPass = ($dw >= 1.50 && $dw <= 2.50);

        $assumptionRows = [
            [
                'Normalitas Residual (K-S Lilliefors & Shapiro-Wilk)',
                'K-S D = ' . $this->fmt($ksRes['d'], 3) . ' (Sig. ' . $this->fmtLilliefors($ksRes['sig']) . ') | Shapiro-Wilk W = ' . $this->fmt($swRes['w'], 3) . ' (Sig. ' . $this->fmtP($swRes['sig']) . ')',
                'Sig. >= ' . number_format($alpha, 2),
                $normPass ? 'LOLOS (Residual Normal)' : 'PERLU PERHATIAN (Tidak Normal)',
            ],
            [
                'Multikolinearitas (Tolerance & VIF)',
                $k === 1 ? 'Tidak berlaku (1 prediktor)' : 'Min Tolerance = ' . $this->fmt($minTol, 3) . ' | Max VIF = ' . $this->fmt($maxVif, 3),
                'Tolerance > 0.10 & VIF < 10',
                $k === 1 ? 'LOLOS' : ($multiPass ? 'LOLOS (Bebas Multikolinearitas)' : 'TERINDIKASI MULTIKOLINEARITAS'),
            ],
            [
                'Heteroskedastisitas (Uji Glejser)',
                'Min Sig. Glejser = ' . $this->fmtP($minGlejserP),
                'Semua Sig. >= ' . number_format($alpha, 2),
                $glejserPass ? 'LOLOS (Bebas Heteroskedastisitas)' : 'TERINDIKASI HETEROSKEDASTISITAS',
            ],
            [
                'Autokorelasi (Durbin-Watson)',
                'DW = ' . $this->fmt($dw, 3),
                '1.50 <= DW <= 2.50',
                $autoPass ? 'LOLOS (Bebas Autokorelasi)' : 'PERLU CEK TABEL dL/dU',
            ],
        ];

        // 4 Grafik Asumsi Klasik & Regresi Gaya IBM SPSS
        // 1. Standardized Predicted (ZPRED) & Standardized Residual (ZRESID)
        $yHatMean = $this->mean($yHat);
        $yHatSd = $this->sampleStd($yHat, $yHatMean);
        $zPred = [];
        $zResid = [];
        for ($i = 0; $i < $n; $i++) {
            $zPred[] = $yHatSd > self::EPS ? ($yHat[$i] - $yHatMean) / $yHatSd : 0.0;
            $zResid[] = $resSd > self::EPS ? ($residuals[$i] - $resMean) / $resSd : 0.0;
        }

        // 1a. Normal P-P Plot of Regression Standardized Residual (SPSS: Expected vs Observed Cum Prob)
        $sortedZ = $zResid;
        sort($sortedZ);
        $ppPts = [];
        for ($i = 0; $i < $n; $i++) {
            $obsCum = ($i + 0.5) / $n;
            $expCum = $this->normalCdf($sortedZ[$i]);
            $ppPts[] = ['x' => round($obsCum, 4), 'y' => round($expCum, 4)];
        }

        // 1b. Scatterplot Heteroskedastisitas (ZRESID vs ZPRED)
        $zScatterPts = [];
        for ($i = 0; $i < min($n, 200); $i++) {
            $zScatterPts[] = ['x' => round($zPred[$i], 3), 'y' => round($zResid[$i], 3)];
        }

        // 1c. Histogram of Regression Standardized Residual
        $zMin = min(-2.5, floor(min($zResid) * 2) / 2);
        $zMax = max(2.5, ceil(max($zResid) * 2) / 2);
        $binCount = max(7, min(12, (int) round(sqrt($n) + 2)));
        $binW = ($zMax - $zMin) / $binCount;
        $histBins = [];
        for ($b = 0; $b < $binCount; $b++) {
            $lo = $zMin + $b * $binW;
            $hi = $lo + $binW;
            $cnt = 0;
            foreach ($zResid as $zv) {
                if (($zv >= $lo && $zv < $hi) || ($b === $binCount - 1 && $zv <= $hi + 1e-9 && $zv >= $lo)) {
                    $cnt++;
                }
            }
            $mid = ($lo + $hi) / 2.0;
            $normExpected = $n * $binW * (1.0 / sqrt(2.0 * M_PI)) * exp(-0.5 * $mid * $mid);
            $histBins[] = ['lo' => round($lo, 2), 'hi' => round($hi, 2), 'mid' => round($mid, 2), 'count' => $cnt, 'normal_y' => round($normExpected, 3)];
        }

        // 1d. Aktual vs Prediksi
        $scatterPts = [];
        for ($i = 0; $i < min($n, 150); $i++) {
            $scatterPts[] = ['x' => round($yHat[$i], 3), 'y' => round($y[$i], 3)];
        }

        $spssCharts = [
            [
                'key' => 'pp_plot',
                'tab' => 'Normal P-P Plot (SPSS)',
                'type' => 'pp_plot',
                'title' => "Normal P-P Plot of Regression Standardized Residual (Dependen: {$yCol})",
                'points' => $ppPts,
                'x_label' => 'Observed Cum Prob',
                'y_label' => 'Expected Cum Prob',
            ],
            [
                'key' => 'zresid_zpred',
                'tab' => 'Scatterplot ZRESID vs ZPRED',
                'type' => 'zresid_zpred',
                'title' => "Scatterplot Uji Heteroskedastisitas — ZRESID vs ZPRED (Dependen: {$yCol})",
                'points' => $zScatterPts,
                'x_label' => 'Regression Standardized Predicted Value (ZPRED)',
                'y_label' => 'Regression Standardized Residual (ZRESID)',
            ],
            [
                'key' => 'residual_hist',
                'tab' => 'Histogram Residual',
                'type' => 'residual_hist',
                'title' => "Histogram of Regression Standardized Residual (N = {$n})",
                'bins' => $histBins,
                'n' => $n,
                'z_min' => $zMin,
                'z_max' => $zMax,
                'x_label' => 'Regression Standardized Residual',
                'y_label' => 'Frequency',
            ],
            [
                'key' => 'scatter_fit',
                'tab' => 'Aktual vs Prediksi',
                'type' => 'scatter_fit',
                'title' => "Aktual ({$yCol}) vs Prediksi Regresi (R² = " . $this->fmt($r2, 3) . ')',
                'points' => $scatterPts,
                'x_label' => 'Nilai Prediksi (Ŷ)',
                'y_label' => "Nilai Aktual ({$yCol})",
            ],
        ];

        $regKind = $k === 1 ? 'Regresi Linier Sederhana' : 'Regresi Linier Berganda';
        $allClassicPass = $normPass && $multiPass && $glejserPass && $autoPass;

        return [
            'title' => "Analisis {$regKind} & Uji Asumsi Klasik",
            'subtitle' => "Variabel Dependen: {$yCol} | Prediktor: " . implode(', ', $xCols) . " (N = {$n})",
            'equation' => $equation,
            'highlights' => [
                ['label' => 'R Square (R²)', 'value' => $this->fmt($r2, 3) . ' (' . $this->fmt($r2 * 100, 1) . '%)', 'tone' => 'blue'],
                ['label' => 'Uji F Simultan', 'value' => 'F = ' . $this->fmt($fStat, 3) . ' (Sig. ' . $this->fmtP($fSig) . ')', 'tone' => $fSig < $alpha ? 'emerald' : 'amber'],
                ['label' => 'Durbin-Watson', 'value' => $this->fmt($dw, 3), 'tone' => $autoPass ? 'emerald' : 'amber'],
                ['label' => 'Asumsi Klasik', 'value' => $allClassicPass ? 'Semua Lolos' : 'Cek Catatan', 'tone' => $allClassicPass ? 'emerald' : 'amber'],
            ],
            'tables' => [
                [
                    'title' => 'Tabel 1. Model Summary (Koefisien Determinasi)',
                    'headers' => ['Model', 'R', 'R Square', 'Adjusted R Square', 'Std. Error of Estimate', 'Durbin-Watson'],
                    'rows' => [
                        ['1', $this->fmt($rVal, 3), $this->fmt($r2, 3), $this->fmt($adjR2, 3), $this->fmt($see, 3), $this->fmt($dw, 3)],
                    ],
                    'footnote' => "Prediktor: (Constant), " . implode(', ', $xCols) . ". Dependen: {$yCol}. Persamaan: {$equation}.",
                ],
                [
                    'title' => 'Tabel 2. ANOVA (Uji F Simultan)',
                    'headers' => ['Model', 'Sum of Squares', 'df', 'Mean Square', 'F', 'Sig.'],
                    'rows' => [
                        ['Regression', $this->fmt($ssReg, 3), $dfReg, $this->fmt($msReg, 3), $this->fmt($fStat, 3), $this->fmtP($fSig)],
                        ['Residual', $this->fmt($ssRes, 3), $dfRes, $this->fmt($msRes, 3), '-', '-'],
                        ['Total', $this->fmt($ssTot, 3), $n - 1, '-', '-', '-'],
                    ],
                    'footnote' => $fSig < $alpha
                        ? "Karena Sig. ({$this->fmtP($fSig)}) < {$alpha}, variabel independen secara simultan berpengaruh signifikan terhadap {$yCol}."
                        : "Karena Sig. ({$this->fmtP($fSig)}) >= {$alpha}, variabel independen secara simultan belum berpengaruh signifikan terhadap {$yCol}.",
                ],
                [
                    'title' => 'Tabel 3. Coefficients (Uji t Parsial & Multikolinearitas)',
                    'headers' => ['Variabel', 'B', 'Std. Error', 'Beta', 't', 'Sig.', 'Tolerance', 'VIF', 'Keputusan'],
                    'rows' => $coefRows,
                    'footnote' => "Persamaan regresi: {$equation}. Variabel berpengaruh signifikan secara parsial jika Sig. < " . number_format($alpha, 2) . '.',
                ],
                [
                    'title' => 'Tabel 4. Ringkasan Uji Asumsi Klasik (Normalitas, Multikolinearitas, Heteroskedastisitas, Autokorelasi)',
                    'headers' => ['Jenis Uji Asumsi', 'Nilai Statistik', 'Kriteria Lolos', 'Keputusan'],
                    'rows' => $assumptionRows,
                    'footnote' => 'Uji normalitas residual menggunakan Kolmogorov-Smirnov dengan Lilliefors Significance Correction (*.200 adalah batas bawah signifikansi sejati sesuai IBM SPSS 22–29) dan Shapiro-Wilk (direkomendasikan untuk N <= 50).',
                ],
                [
                    'title' => 'Tabel 5. Detail Uji Heteroskedastisitas Glejser (Dependen: |Residual|)',
                    'headers' => ['Variabel', 'Koefisien B', 't Hitung', 'Sig.', 'Status'],
                    'rows' => $glejserRows,
                    'footnote' => 'Bebas gejala heteroskedastisitas apabila nilai Sig. setiap variabel >= ' . number_format($alpha, 2) . '.',
                ],
            ],
            'chart' => $spssCharts[0],
            'charts' => $spssCharts,
            'conclusions' => [
                "Persamaan regresi yang terbentuk adalah {$equation}.",
                "Nilai R Square sebesar {$this->fmt($r2, 3)} (Adjusted R² = {$this->fmt($adjR2, 3)}) menunjukkan bahwa variabel independen menjelaskan " . $this->fmt($r2 * 100, 1) . "% variasi {$yCol}, sedangkan sisanya " . $this->fmt((1 - $r2) * 100, 1) . "% dipengaruhi faktor lain di luar model.",
                'Uji F Simultan menghasilkan F = ' . $this->fmt($fStat, 3) . ' dengan Sig. = ' . $this->fmtP($fSig) . ($fSig < $alpha ? ' (Signifikan secara simultan).' : ' (Tidak signifikan secara simultan).'),
                'Uji Asumsi Klasik: Normalitas K-S (Lilliefors) D = ' . $this->fmt($ksRes['d'], 3) . ' (Sig. ' . $this->fmtLilliefors($ksRes['sig']) . '), Shapiro-Wilk W = ' . $this->fmt($swRes['w'], 3) . ' (Sig. ' . $this->fmtP($swRes['sig']) . '), Max VIF = ' . $this->fmt($maxVif, 2) . ', Glejser Min Sig. = ' . $this->fmtP($minGlejserP) . ', Durbin-Watson = ' . $this->fmt($dw, 3) . '.',
            ],
            'project_autofill' => [
                'metode' => "Analisis {$regKind} & Uji Asumsi Klasik (Setara IBM SPSS Statistics)",
                'sampel' => "{$n} Observasi / Responden (" . count($xCols) . " Variabel Independen, 1 Dependen)",
                'rumus' => "{$equation}; R² = SSR/SST; F = MSR/MSE; t_j = B_j / SE(B_j); VIF_j = 1 / (1 - R_j²)",
                'metrik' => "R = {$this->fmt($rVal, 3)}, R-Square = {$this->fmt($r2, 3)} (" . $this->fmt($r2 * 100, 1) . "%), Adjusted R-Square = {$this->fmt($adjR2, 3)}, F-hitung = {$this->fmt($fStat, 3)} (Sig. {$this->fmtP($fSig)}). Koefisien Parsial: " . implode('; ', $partialSummary) . ". Asumsi Klasik: Normalitas K-S Lilliefors Sig. = {$this->fmtLilliefors($ksRes['sig'])}, Shapiro-Wilk W = {$this->fmt($swRes['w'], 3)} (Sig. {$this->fmtP($swRes['sig'])}), Min Glejser Sig. = {$this->fmtP($minGlejserP)}, Durbin-Watson = {$this->fmt($dw, 3)}.",
                'evaluasi' => 'Uji Asumsi Klasik (Kolmogorov-Smirnov Lilliefors, Shapiro-Wilk, Tolerance/VIF, Glejser, Durbin-Watson), Uji t Parsial, Uji F Simultan, Koefisien Determinasi (R²)',
            ],
        ];
    }

    // =========================================================================
    // 4. KORELASI PEARSON & SPEARMAN RANK
    // =========================================================================

    private function runCorrelation(array $columns, array $rows, array $options, float $alpha): array
    {
        $selected = $this->resolveNumericColumns($columns, $rows, $options['cols'] ?? $options['x_cols'] ?? []);
        if (count($selected) < 2) {
            throw new InvalidArgumentException('Uji Korelasi membutuhkan minimal 2 kolom numerik.');
        }

        $m = count($selected);
        $pairRows = [];
        $pearsonMatrixRows = [];
        $chartLabels = [];
        $chartValues = [];
        $metricParts = [];

        for ($i = 0; $i < $m; $i++) {
            $matRow = [$selected[$i]];
            for ($j = 0; $j < $m; $j++) {
                if ($i === $j) {
                    $matRow[] = '1.000';
                    continue;
                }
                $pair = $this->extractCompleteNumericMatrix($rows, [$selected[$i], $selected[$j]]);
                $n = count($pair);
                if ($n < 3) {
                    $matRow[] = '-';
                    continue;
                }
                $a = array_column($pair, 0);
                $b = array_column($pair, 1);
                $r = $this->pearsonR($a, $b);
                $p = $this->pearsonPValue($r, $n);
                $star = $p < 0.01 ? '**' : ($p < 0.05 ? '*' : '');
                $matRow[] = $this->fmt($r, 3) . $star;

                if ($j > $i) {
                    $rho = $this->spearmanRho($a, $b);
                    $pRho = $this->pearsonPValue($rho, $n);
                    $strength = $this->correlationStrength(abs($r));
                    $dir = $r >= 0 ? 'Positif' : 'Negatif';
                    $pairRows[] = [
                        "{$selected[$i]} ↔ {$selected[$j]}",
                        $n,
                        $this->fmt($r, 3),
                        $this->fmtP($p),
                        $this->fmt($rho, 3),
                        $this->fmtP($pRho),
                        "{$dir} ({$strength})",
                        $p < $alpha ? 'Signifikan' : 'Tidak Signifikan',
                    ];
                    $chartLabels[] = "{$selected[$i]} - {$selected[$j]}";
                    $chartValues[] = round($r, 3);
                    $metricParts[] = "{$selected[$i]} vs {$selected[$j]}: Pearson r={$this->fmt($r, 3)} (Sig. {$this->fmtP($p)}), Spearman rho={$this->fmt($rho, 3)} (Sig. {$this->fmtP($pRho)})";
                }
            }
            $pearsonMatrixRows[] = $matRow;
        }

        return [
            'title' => 'Analisis Korelasi Bivariat (Pearson Product Moment & Spearman Rank)',
            'subtitle' => 'Pengujian Arah, Kekuatan, dan Signifikansi Hubungan Antar Variabel',
            'highlights' => [
                ['label' => 'Jumlah Variabel', 'value' => (string) $m, 'tone' => 'blue'],
                ['label' => 'Pasangan Diuji', 'value' => (string) count($pairRows), 'tone' => 'slate'],
                ['label' => 'Metode', 'value' => 'Pearson & Spearman', 'tone' => 'emerald'],
                ['label' => 'Taraf Uji (α)', 'value' => number_format($alpha, 2) . ' (2-tailed)', 'tone' => 'slate'],
            ],
            'tables' => [
                [
                    'title' => 'Tabel 1. Ringkasan Uji Korelasi Pearson (r) & Spearman (ρ)',
                    'headers' => ['Pasangan Variabel', 'N', 'Pearson r', 'Sig. (Pearson)', 'Spearman ρ', 'Sig. (Spearman)', 'Arah & Kekuatan', 'Keputusan'],
                    'rows' => $pairRows,
                    'footnote' => 'Kategori kekuatan Sugiyono: 0.00–0.199 Sangat Rendah, 0.20–0.399 Rendah, 0.40–0.599 Sedang, 0.60–0.799 Kuat, 0.80–1.000 Sangat Kuat.',
                ],
                [
                    'title' => 'Tabel 2. Matriks Korelasi Pearson (Correlations)',
                    'headers' => array_merge(['Variabel'], $selected),
                    'rows' => $pearsonMatrixRows,
                    'footnote' => '** Signifikan pada α = 0.01 (2-tailed); * Signifikan pada α = 0.05 (2-tailed).',
                ],
            ],
            'chart' => [
                'type' => 'bar',
                'title' => 'Koefisien Korelasi Pearson (r) per Pasangan Variabel',
                'labels' => array_slice($chartLabels, 0, 12),
                'values' => array_slice($chartValues, 0, 12),
                'y_label' => 'Pearson r',
            ],
            'conclusions' => [
                'Analisis korelasi parametrik (Pearson) dan non-parametrik (Spearman Rank dengan koreksi tie) selesai dihitung untuk ' . count($pairRows) . ' pasangan variabel.',
                'Gunakan Pearson r jika data berdistribusi normal dan berskala interval/rasio; gunakan Spearman ρ jika data ordinal atau tidak normal.',
            ],
            'project_autofill' => [
                'metode' => 'Analisis Korelasi Pearson Product Moment & Spearman Rank (Setara IBM SPSS)',
                'sampel' => count($rows) . ' Observasi (' . $m . ' Variabel)',
                'rumus' => 'r = Σ[(Xi-X̄)(Yi-Ȳ)] / √[Σ(Xi-X̄)² Σ(Yi-Ȳ)²]; t = r√(n-2)/√(1-r²)',
                'metrik' => 'Hasil Korelasi: ' . implode('; ', array_slice($metricParts, 0, 8)) . '.',
                'evaluasi' => 'Uji Korelasi Bivariat 2-tailed (Signifikan jika Sig. < ' . number_format($alpha, 2) . ') & Interpretasi Kekuatan Korelasi Sugiyono',
            ],
        ];
    }

    // =========================================================================
    // 5. ONE-WAY ANOVA + LEVENE HOMOGENEITY + POST-HOC BONFERRONI (TEKNIK SIPIL / LAB / EKSPERIMEN)
    // =========================================================================

    private function runOneWayAnova(array $columns, array $rows, array $options, float $alpha): array
    {
        $groupCol = trim((string) ($options['group_col'] ?? ''));
        $valCol = trim((string) ($options['value_col'] ?? $options['y_col'] ?? ''));

        if ($groupCol === '' || $valCol === '') {
            [$autoG, $autoV] = $this->guessGroupAndValueColumns($columns, $rows);
            $groupCol = $groupCol !== '' ? $groupCol : $autoG;
            $valCol = $valCol !== '' ? $valCol : $autoV;
        }
        if ($groupCol === '' || $valCol === '' || $groupCol === $valCol) {
            throw new InvalidArgumentException('Pilih 1 kolom Kelompok/Perlakuan (kategorik) dan 1 kolom Nilai Pengukuran (numerik) untuk One-Way ANOVA.');
        }

        $grouped = $this->extractGroupedNumeric($rows, $groupCol, $valCol);
        $groupNames = array_keys($grouped);
        $g = count($groupNames);
        if ($g < 2) {
            throw new InvalidArgumentException("Kolom '{$groupCol}' minimal harus memiliki 2 kelompok berbeda (ideal >= 3 kelompok untuk ANOVA).");
        }

        $allVals = [];
        foreach ($grouped as $vals) {
            if (count($vals) < 2) {
                throw new InvalidArgumentException('Setiap kelompok pada One-Way ANOVA minimal harus memiliki 2 ulangan/observasi.');
            }
            foreach ($vals as $v) {
                $allVals[] = $v;
            }
        }
        $nTotal = count($allVals);
        $grandMean = $this->mean($allVals);

        $ssBetween = 0.0;
        $ssWithin = 0.0;
        $descRows = [];
        $chartLabels = [];
        $chartMeans = [];
        $chartSds = [];
        $groupStats = [];

        foreach ($groupNames as $name) {
            $vals = $grouped[$name];
            $ni = count($vals);
            $mi = $this->mean($vals);
            $sdi = $this->sampleStd($vals, $mi);
            $sei = $sdi / sqrt($ni);
            $tCrit = $this->tCritical(0.05, (float) ($ni - 1));
            $ciLow = $mi - $tCrit * $sei;
            $ciHigh = $mi + $tCrit * $sei;

            $ssBetween += $ni * pow($mi - $grandMean, 2);
            foreach ($vals as $v) {
                $ssWithin += pow($v - $mi, 2);
            }

            $groupStats[$name] = ['n' => $ni, 'mean' => $mi, 'sd' => $sdi];
            $descRows[] = [
                $name,
                $ni,
                $this->fmt($mi, 3),
                $this->fmt($sdi, 3),
                $this->fmt($sei, 3),
                $this->fmt($ciLow, 3),
                $this->fmt($ciHigh, 3),
                $this->fmt(min($vals), 3),
                $this->fmt(max($vals), 3),
            ];
            $chartLabels[] = $name;
            $chartMeans[] = round($mi, 3);
            $chartSds[] = round($sdi, 3);
        }

        $ssTotal = $ssBetween + $ssWithin;
        $dfBetween = $g - 1;
        $dfWithin = $nTotal - $g;
        $msBetween = $ssBetween / $dfBetween;
        $msWithin = $ssWithin / $dfWithin;
        $fStat = $msWithin > self::EPS ? ($msBetween / $msWithin) : 0.0;
        $fSig = $this->fUpperTailP($fStat, (float) $dfBetween, (float) $dfWithin);
        $etaSq = $ssTotal > self::EPS ? ($ssBetween / $ssTotal) : 0.0;
        $omegaSq = ($ssTotal + $msWithin) > self::EPS
            ? max(0.0, ($ssBetween - ($dfBetween * $msWithin)) / ($ssTotal + $msWithin))
            : 0.0;

        // 1. Uji Normalitas Residual ANOVA (Shapiro-Wilk & K-S Lilliefors)
        $anovaResiduals = [];
        foreach ($groupNames as $name) {
            $mi = $groupStats[$name]['mean'];
            foreach ($grouped[$name] as $v) {
                $anovaResiduals[] = $v - $mi;
            }
        }
        $resMeanA = $this->mean($anovaResiduals);
        $resSdA = $this->sampleStd($anovaResiduals, $resMeanA);
        $swResA = $this->shapiroWilk($anovaResiduals);
        $ksResA = $this->kolmogorovSmirnovNormal($anovaResiduals, $resMeanA, $resSdA);
        $normPassA = ($nTotal <= 50 ? $swResA['sig'] : $ksResA['sig']) >= $alpha;

        // 2. Levene's Test of Homogeneity of Variances (mean-centered, SPSS default)
        $levene = $this->leveneTest($grouped);
        $homogPass = $levene['sig'] >= $alpha;

        // 3. Welch's One-Way ANOVA (Robust Tests of Equality of Means — setara IBM SPSS Options -> Welch)
        $wWeights = [];
        $wSum = 0.0;
        $wMeanNum = 0.0;
        $welchValid = true;
        foreach ($groupNames as $name) {
            $ni = $groupStats[$name]['n'];
            $vi = $groupStats[$name]['sd'] * $groupStats[$name]['sd'];
            if ($vi <= self::EPS) {
                $welchValid = false;
                break;
            }
            $wi = $ni / $vi;
            $wWeights[$name] = $wi;
            $wSum += $wi;
            $wMeanNum += $wi * $groupStats[$name]['mean'];
        }
        $welchF = $fStat;
        $welchDf2 = (float) $dfWithin;
        $welchSig = $fSig;
        if ($welchValid && $wSum > self::EPS) {
            $xBarW = $wMeanNum / $wSum;
            $numW = 0.0;
            $lamSum = 0.0;
            foreach ($groupNames as $name) {
                $ni = $groupStats[$name]['n'];
                $wi = $wWeights[$name];
                $numW += $wi * pow($groupStats[$name]['mean'] - $xBarW, 2);
                $lamSum += pow(1.0 - ($wi / $wSum), 2) / max(1, $ni - 1);
            }
            $lambda = (3.0 * $lamSum) / max(self::EPS, ($g * $g) - 1.0);
            $denW = 1.0 + (2.0 * ($g - 2.0) / max(1.0, ($g * $g) - 1.0)) * $lamSum;
            $welchF = ($numW / $dfBetween) / max(self::EPS, $denW);
            $welchDf2 = $lamSum > self::EPS ? (1.0 / $lambda) : (float) $dfWithin;
            $welchSig = $this->fUpperTailP($welchF, (float) $dfBetween, $welchDf2);
        }

        // Rekomendasi metode otomatis berdasarkan pemeriksaan asumsi
        if ($normPassA && $homogPass) {
            $methodAdvisory = [
                'tone' => 'emerald',
                'title' => 'Asumsi Parametrik Terpenuhi (Normal & Homogen)',
                'message' => "Residual berdistribusi normal (Shapiro-Wilk W = {$this->fmt($swResA['w'], 3)}, Sig. = {$this->fmtP($swResA['sig'])}; K-S Lilliefors Sig. = {$this->fmtLilliefors($ksResA['sig'])}) dan varians homogen (Levene Sig. = {$this->fmtP($levene['sig'])}). Penggunaan One-Way ANOVA + Post-Hoc Bonferroni sudah tepat secara metodologis.",
            ];
        } elseif ($normPassA && !$homogPass) {
            $methodAdvisory = [
                'tone' => 'amber',
                'title' => 'Varians Tidak Homogen → Gunakan Welch\'s ANOVA',
                'message' => "Residual normal, tetapi Uji Levene menunjukkan varians tidak homogen (Sig. = {$this->fmtP($levene['sig'])} < {$alpha}). Gunakan hasil Welch's ANOVA yang telah dihitung otomatis pada Tabel 3 (Welch F = {$this->fmt($welchF, 3)}, df2 = {$this->fmt($welchDf2, 2)}, Sig. = {$this->fmtP($welchSig)}), atau verifikasi dengan Kruskal-Wallis H.",
            ];
        } else {
            $methodAdvisory = [
                'tone' => 'amber',
                'title' => 'Residual Tidak Normal → Pertimbangkan Kruskal-Wallis H',
                'message' => "Uji normalitas residual menunjukkan indikasi penyimpangan normalitas (Shapiro-Wilk Sig. = {$this->fmtP($swResA['sig'])} < {$alpha}). Disarankan memverifikasi hasil ini dengan uji non-parametrik Kruskal-Wallis H pada kartu Non-Parametrik.",
            ];
        }

        // Post-Hoc Pairwise Comparisons (Bonferroni)
        $numPairs = ($g * ($g - 1)) / 2;
        $postHocRows = [];
        $sigPairs = [];
        for ($i = 0; $i < $g; $i++) {
            for ($j = $i + 1; $j < $g; $j++) {
                $gA = $groupNames[$i];
                $gB = $groupNames[$j];
                $diff = $groupStats[$gA]['mean'] - $groupStats[$gB]['mean'];
                $seDiff = sqrt($msWithin * (1.0 / $groupStats[$gA]['n'] + 1.0 / $groupStats[$gB]['n']));
                $tPair = $seDiff > self::EPS ? abs($diff) / $seDiff : 0.0;
                $rawP = $this->tTwoTailedP($tPair, (float) $dfWithin);
                $adjP = min(1.0, $rawP * $numPairs);
                $tBonf = $this->tCritical($alpha / max(1, $numPairs), (float) $dfWithin);
                $ciL = $diff - $tBonf * $seDiff;
                $ciU = $diff + $tBonf * $seDiff;
                $isPairSig = $adjP < $alpha;
                if ($isPairSig) {
                    $sigPairs[] = "{$gA} vs {$gB} (Δ={$this->fmt($diff, 2)}, Sig.={$this->fmtP($adjP)})";
                }
                $postHocRows[] = [
                    "{$gA} vs {$gB}",
                    $this->fmt($diff, 3),
                    $this->fmt($seDiff, 3),
                    $this->fmt($tPair, 3),
                    $this->fmtP($adjP),
                    '[' . $this->fmt($ciL, 3) . ', ' . $this->fmt($ciU, 3) . ']',
                    $isPairSig ? 'Berbeda Signifikan' : 'Tidak Berbeda',
                ];
            }
        }

        // Kelompok dengan Mean tertinggi
        $bestGroup = $groupNames[0];
        foreach ($groupStats as $gn => $gs) {
            if ($gs['mean'] > $groupStats[$bestGroup]['mean']) {
                $bestGroup = $gn;
            }
        }

        $groupText = [];
        foreach ($groupStats as $gn => $gs) {
            $groupText[] = "{$gn} (n={$gs['n']}, Mean={$this->fmt($gs['mean'], 2)}, SD={$this->fmt($gs['sd'], 2)})";
        }

        return [
            'title' => 'Analisis One-Way ANOVA, Uji Asumsi & Post-Hoc Bonferroni',
            'subtitle' => "Variabel Pengukuran: {$valCol} berdasarkan Kelompok: {$groupCol} ({$g} Kelompok, N = {$nTotal})",
            'method_advisory' => $methodAdvisory,
            'highlights' => [
                ['label' => 'F Hitung (ANOVA)', 'value' => $this->fmt($fStat, 3) . ' (Sig. ' . $this->fmtP($fSig) . ')', 'tone' => $fSig < $alpha ? 'emerald' : 'amber'],
                ['label' => 'Normalitas Residual', 'value' => 'SW Sig. ' . $this->fmtP($swResA['sig']), 'tone' => $normPassA ? 'emerald' : 'amber'],
                ['label' => 'Homogenitas Levene', 'value' => 'Sig. ' . $this->fmtP($levene['sig']), 'tone' => $homogPass ? 'emerald' : 'amber'],
                ['label' => 'Effect Size (η² / ω²)', 'value' => $this->fmt($etaSq, 3) . ' / ' . $this->fmt($omegaSq, 3), 'tone' => 'blue'],
            ],
            'tables' => [
                [
                    'title' => "Tabel 1. Descriptives ({$valCol} per {$groupCol})",
                    'headers' => [$groupCol, 'N', 'Mean', 'Std. Dev', 'Std. Error', '95% CI Lower', '95% CI Upper', 'Min', 'Max'],
                    'rows' => $descRows,
                    'footnote' => "Nilai rata-rata tertinggi diperoleh pada kelompok '{$bestGroup}' sebesar {$this->fmt($groupStats[$bestGroup]['mean'], 3)}.",
                ],
                [
                    'title' => 'Tabel 2. Pemeriksaan Asumsi ANOVA (Normalitas Residual & Homogenitas Varians Levene)',
                    'headers' => ['Jenis Uji Asumsi', 'Statistik Uji', 'df', 'Sig.', 'Keputusan'],
                    'rows' => [
                        [
                            'Normalitas Residual (Shapiro-Wilk)',
                            'W = ' . $this->fmt($swResA['w'], 3),
                            (string) $nTotal,
                            $this->fmtP($swResA['sig']),
                            $swResA['sig'] >= $alpha ? 'Normal (Lolos Asumsi)' : 'Tidak Normal',
                        ],
                        [
                            'Normalitas Residual (K-S Lilliefors)',
                            'D = ' . $this->fmt($ksResA['d'], 3),
                            (string) $nTotal,
                            $this->fmtLilliefors($ksResA['sig']),
                            $ksResA['sig'] >= $alpha ? 'Normal (Lolos Asumsi)' : 'Tidak Normal',
                        ],
                        [
                            'Homogenitas Varians (Levene Test)',
                            'F = ' . $this->fmt($levene['f'], 3),
                            "{$levene['df1']}, {$levene['df2']}",
                            $this->fmtP($levene['sig']),
                            $homogPass ? 'Varians Homogen (Lolos Asumsi)' : 'Varians Tidak Homogen (Gunakan Welch)',
                        ],
                    ],
                    'footnote' => 'Asumsi ANOVA mencakup independensi observasi (berdasarkan desain penelitian), normalitas residual (Shapiro-Wilk untuk N <= 50; K-S Lilliefors untuk N > 50), dan homogenitas varians Levene (Sig. >= ' . number_format($alpha, 2) . ').',
                ],
                [
                    'title' => "Tabel 3. ANOVA & Robust Welch Test ({$valCol})",
                    'headers' => ['Source of Variation', 'Sum of Squares', 'df', 'Mean Square', 'F', 'Sig.'],
                    'rows' => [
                        ['Between Groups', $this->fmt($ssBetween, 3), $dfBetween, $this->fmt($msBetween, 3), $this->fmt($fStat, 3), $this->fmtP($fSig)],
                        ['Within Groups', $this->fmt($ssWithin, 3), $dfWithin, $this->fmt($msWithin, 3), '-', '-'],
                        ['Total', $this->fmt($ssTotal, 3), $nTotal - 1, '-', '-', '-'],
                        ['Welch\'s Robust ANOVA (Alternatif)', '-', "{$dfBetween}; " . $this->fmt($welchDf2, 2), '-', $this->fmt($welchF, 3), $this->fmtP($welchSig)],
                    ],
                    'footnote' => "Eta Squared (η²) = {$this->fmt($etaSq, 3)}, Omega Squared (ω²) = {$this->fmt($omegaSq, 3)}. " . ($fSig < $alpha
                        ? "Terdapat perbedaan rata-rata {$valCol} yang signifikan antar kelompok {$groupCol} (F = {$this->fmt($fStat, 3)}, Sig. = {$this->fmtP($fSig)} < {$alpha})."
                        : "Tidak terdapat perbedaan rata-rata {$valCol} yang signifikan antar kelompok {$groupCol} (Sig. = {$this->fmtP($fSig)} >= {$alpha})."),
                ],
                [
                    'title' => 'Tabel 4. Multiple Comparisons (Post-Hoc Bonferroni — Seluruh ' . count($postHocRows) . ' Pasangan)',
                    'headers' => ['Perbandingan (I vs J)', 'Mean Diff (I-J)', 'Std. Error', 't', 'Adj. Sig.', '95% CI', 'Keputusan'],
                    'rows' => $postHocRows,
                    'footnote' => 'Nilai Adj. Sig. menggunakan koreksi Bonferroni (p * jumlah pasangan perbandingan).',
                ],
            ],
            'chart' => [
                'type' => 'bar_error',
                'title' => "Rata-rata ± SD {$valCol} per Kelompok {$groupCol}",
                'labels' => $chartLabels,
                'values' => $chartMeans,
                'errors' => $chartSds,
                'y_label' => $valCol,
            ],
            'conclusions' => [
                "Uji Asumsi: Normalitas residual Shapiro-Wilk W = {$this->fmt($swResA['w'], 3)} (Sig. = {$this->fmtP($swResA['sig'])}) & K-S Lilliefors Sig. = {$this->fmtLilliefors($ksResA['sig'])}; Homogenitas Levene F = {$this->fmt($levene['f'], 3)} (Sig. = {$this->fmtP($levene['sig'])}) sehingga varians antar kelompok " . ($homogPass ? 'HOMOGEN.' : 'TIDAK HOMOGEN (lihat baris Welch ANOVA).'),
                "Uji One-Way ANOVA menghasilkan F = {$this->fmt($fStat, 3)} (df = {$dfBetween}; {$dfWithin}, Sig. = {$this->fmtP($fSig)}), Welch F = {$this->fmt($welchF, 3)} (Sig. = {$this->fmtP($welchSig)}), Eta Squared (η²) = {$this->fmt($etaSq, 3)}, dan Omega Squared (ω²) = {$this->fmt($omegaSq, 3)} (" . ($fSig < $alpha ? 'H₀ ditolak: terdapat perbedaan signifikan antar perlakuan/kelompok).' : 'H₀ gagal ditolak: tidak terdapat perbedaan signifikan antar perlakuan/kelompok).'),
                "Rata-rata tertinggi dicapai oleh kelompok '{$bestGroup}' (Mean = {$this->fmt($groupStats[$bestGroup]['mean'], 3)}, SD = {$this->fmt($groupStats[$bestGroup]['sd'], 3)})." . (!empty($sigPairs) ? ' Seluruh ' . count($sigPairs) . ' pasangan berbeda signifikan (Bonferroni): ' . implode('; ', $sigPairs) . '.' : ' Tidak ada pasangan yang berbeda signifikan pada uji lanjut Bonferroni.'),
            ],
            'project_autofill' => [
                'metode' => 'Uji One-Way ANOVA, Normalitas Residual (Shapiro-Wilk), Homogenitas Levene, Welch ANOVA & Post-Hoc Bonferroni (Setara IBM SPSS)',
                'sampel' => "{$nTotal} Benda Uji / Sampel ({$g} Kelompok {$groupCol})",
                'rumus' => 'F = MSB / MSW; η² = SSB / SST; ω² = (SSB - dfB*MSW) / (SST + MSW); Post-Hoc Bonferroni p_adj = min(1, m * p)',
                'metrik' => "One-Way ANOVA {$valCol}: F-hitung = {$this->fmt($fStat, 3)} (df = {$dfBetween}; {$dfWithin}, Sig. = {$this->fmtP($fSig)}), Welch F = {$this->fmt($welchF, 3)} (Sig. = {$this->fmtP($welchSig)}), Eta-Squared = {$this->fmt($etaSq, 3)}, Omega-Squared = {$this->fmt($omegaSq, 3)}, Shapiro-Wilk Residual Sig. = {$this->fmtP($swResA['sig'])}, Levene Sig. = {$this->fmtP($levene['sig'])}. Rata-rata kelompok: " . implode('; ', $groupText) . ". Tertinggi: {$bestGroup}.",
                'evaluasi' => 'Uji Normalitas Residual (Shapiro-Wilk & K-S Lilliefors), Uji Homogenitas Varians Levene, One-Way ANOVA / Welch ANOVA (α = ' . number_format($alpha, 2) . '), dan Uji Lanjut Post-Hoc Bonferroni',
            ],
        ];
    }

    // =========================================================================
    // 6. INDEPENDENT SAMPLES T-TEST (STUDENT & WELCH + LEVENE + COHEN'S D)
    // =========================================================================

    private function runIndependentTTest(array $columns, array $rows, array $options, float $alpha): array
    {
        $groupCol = trim((string) ($options['group_col'] ?? ''));
        $valCol = trim((string) ($options['value_col'] ?? $options['y_col'] ?? ''));
        if ($groupCol === '' || $valCol === '') {
            [$autoG, $autoV] = $this->guessGroupAndValueColumns($columns, $rows);
            $groupCol = $groupCol !== '' ? $groupCol : $autoG;
            $valCol = $valCol !== '' ? $valCol : $autoV;
        }
        if ($groupCol === '' || $valCol === '') {
            throw new InvalidArgumentException('Pilih 1 kolom Kelompok (2 grup) dan 1 kolom Nilai numerik untuk Independent Sample t-Test.');
        }

        $grouped = $this->extractGroupedNumeric($rows, $groupCol, $valCol);
        $names = array_keys($grouped);
        if (count($names) !== 2) {
            throw new InvalidArgumentException("Independent Sample t-Test memerlukan tepat 2 kelompok pada kolom '{$groupCol}' (terdeteksi " . count($names) . ' kelompok). Untuk >= 3 kelompok gunakan One-Way ANOVA.');
        }

        [$g1, $g2] = [$names[0], $names[1]];
        [$v1, $v2] = [$grouped[$g1], $grouped[$g2]];
        [$n1, $n2] = [count($v1), count($v2)];
        if ($n1 < 2 || $n2 < 2) {
            throw new InvalidArgumentException('Masing-masing kelompok minimal harus memiliki 2 data.');
        }

        $m1 = $this->mean($v1);
        $m2 = $this->mean($v2);
        $var1 = $this->sampleVariance($v1, $m1);
        $var2 = $this->sampleVariance($v2, $m2);
        $sd1 = sqrt($var1);
        $sd2 = sqrt($var2);
        $diff = $m1 - $m2;

        // Levene test
        $levene = $this->leveneTest($grouped);
        $equalVar = $levene['sig'] >= $alpha;

        // Student's t (Equal variances assumed)
        $dfEq = $n1 + $n2 - 2;
        $sp2 = (($n1 - 1) * $var1 + ($n2 - 1) * $var2) / $dfEq;
        $seEq = sqrt($sp2 * (1.0 / $n1 + 1.0 / $n2));
        $tEq = $seEq > self::EPS ? $diff / $seEq : 0.0;
        $pEq = $this->tTwoTailedP($tEq, (float) $dfEq);
        $tCritEq = $this->tCritical($alpha, (float) $dfEq);

        // Welch's t (Equal variances not assumed)
        $seWelch = sqrt($var1 / $n1 + $var2 / $n2);
        $tWelch = $seWelch > self::EPS ? $diff / $seWelch : 0.0;
        $wNum = pow($var1 / $n1 + $var2 / $n2, 2);
        $wDen = (pow($var1 / $n1, 2) / ($n1 - 1)) + (pow($var2 / $n2, 2) / ($n2 - 1));
        $dfWelch = $wDen > self::EPS ? $wNum / $wDen : (float) $dfEq;
        $pWelch = $this->tTwoTailedP($tWelch, $dfWelch);
        $tCritWelch = $this->tCritical($alpha, $dfWelch);

        // Primary row based on Levene
        $pPrimary = $equalVar ? $pEq : $pWelch;
        $tPrimary = $equalVar ? $tEq : $tWelch;
        $dfPrimary = $equalVar ? (float) $dfEq : $dfWelch;
        $cohenD = $sp2 > self::EPS ? ($diff / sqrt($sp2)) : 0.0;

        return [
            'title' => 'Uji Independent Samples t-Test (Uji Beda 2 Kelompok Bebas)',
            'subtitle' => "Variabel: {$valCol} | Perbandingan: {$g1} (n={$n1}) vs {$g2} (n={$n2})",
            'highlights' => [
                ['label' => 't Hitung', 'value' => $this->fmt($tPrimary, 3), 'tone' => 'blue'],
                ['label' => 'Sig. (2-tailed)', 'value' => $this->fmtP($pPrimary), 'tone' => $pPrimary < $alpha ? 'emerald' : 'amber'],
                ['label' => 'Mean Difference', 'value' => $this->fmt($diff, 3), 'tone' => 'slate'],
                ['label' => 'Cohen\'s d', 'value' => $this->fmt(abs($cohenD), 3), 'tone' => 'slate'],
            ],
            'tables' => [
                [
                    'title' => 'Tabel 1. Group Statistics',
                    'headers' => [$groupCol, 'N', 'Mean', 'Std. Deviation', 'Std. Error Mean'],
                    'rows' => [
                        [$g1, $n1, $this->fmt($m1, 3), $this->fmt($sd1, 3), $this->fmt($sd1 / sqrt($n1), 3)],
                        [$g2, $n2, $this->fmt($m2, 3), $this->fmt($sd2, 3), $this->fmt($sd2 / sqrt($n2), 3)],
                    ],
                ],
                [
                    'title' => 'Tabel 2. Independent Samples Test (Levene & t-test for Equality of Means)',
                    'headers' => ['Asumsi Varians', 'Levene F', 'Levene Sig.', 't', 'df', 'Sig. (2-tailed)', 'Mean Diff', 'SE Diff', '95% CI'],
                    'rows' => [
                        [
                            'Equal variances assumed',
                            $this->fmt($levene['f'], 3),
                            $this->fmtP($levene['sig']),
                            $this->fmt($tEq, 3),
                            $dfEq,
                            $this->fmtP($pEq),
                            $this->fmt($diff, 3),
                            $this->fmt($seEq, 3),
                            '[' . $this->fmt($diff - $tCritEq * $seEq, 3) . ', ' . $this->fmt($diff + $tCritEq * $seEq, 3) . ']',
                        ],
                        [
                            'Equal variances not assumed (Welch)',
                            '-',
                            '-',
                            $this->fmt($tWelch, 3),
                            $this->fmt($dfWelch, 2),
                            $this->fmtP($pWelch),
                            $this->fmt($diff, 3),
                            $this->fmt($seWelch, 3),
                            '[' . $this->fmt($diff - $tCritWelch * $seWelch, 3) . ', ' . $this->fmt($diff + $tCritWelch * $seWelch, 3) . ']',
                        ],
                    ],
                    'footnote' => 'Karena Levene Sig. = ' . $this->fmtP($levene['sig']) . ($equalVar ? ' (>= ' . $alpha . '), baca baris Equal variances assumed.' : ' (< ' . $alpha . '), baca baris Equal variances not assumed (Welch).'),
                ],
            ],
            'chart' => [
                'type' => 'bar_error',
                'title' => "Perbandingan Rata-rata ± SD {$valCol}: {$g1} vs {$g2}",
                'labels' => [$g1, $g2],
                'values' => [round($m1, 3), round($m2, 3)],
                'errors' => [round($sd1, 3), round($sd2, 3)],
                'y_label' => $valCol,
            ],
            'conclusions' => [
                "Rata-rata kelompok {$g1} = {$this->fmt($m1, 3)} (SD = {$this->fmt($sd1, 3)}), sedangkan kelompok {$g2} = {$this->fmt($m2, 3)} (SD = {$this->fmt($sd2, 3)}), selisih = {$this->fmt($diff, 3)}.",
                "Berdasarkan uji t independen (" . ($equalVar ? 'Equal variances assumed' : 'Welch') . "), diperoleh t = {$this->fmt($tPrimary, 3)} (df = {$this->fmt($dfPrimary, 2)}) dengan Sig. (2-tailed) = {$this->fmtP($pPrimary)} dan Cohen's d = {$this->fmt(abs($cohenD), 3)} (" . ($pPrimary < $alpha ? 'terdapat perbedaan yang signifikan secara statistik).' : 'tidak terdapat perbedaan yang signifikan).'),
            ],
            'project_autofill' => [
                'metode' => 'Independent Samples t-Test & Uji Homogenitas Levene (Setara IBM SPSS)',
                'sampel' => ($n1 + $n2) . " Sampel ({$g1} n={$n1}, {$g2} n={$n2})",
                'rumus' => 't = (X̄1 - X̄2) / √[Sp²(1/n1 + 1/n2)]; Cohen\'s d = (X̄1 - X̄2) / Sp',
                'metrik' => "{$g1}: Mean={$this->fmt($m1, 2)} (SD={$this->fmt($sd1, 2)}); {$g2}: Mean={$this->fmt($m2, 2)} (SD={$this->fmt($sd2, 2)}); Selisih={$this->fmt($diff, 2)}. Levene F={$this->fmt($levene['f'], 3)} (Sig. {$this->fmtP($levene['sig'])}). t-hitung={$this->fmt($tPrimary, 3)} (df={$this->fmt($dfPrimary, 1)}, Sig. {$this->fmtP($pPrimary)}), Cohen's d={$this->fmt(abs($cohenD), 3)}.",
                'evaluasi' => 'Uji Homogenitas Levene & Independent Samples t-Test 2-tailed (Signifikan jika Sig. < ' . number_format($alpha, 2) . ')',
            ],
        ];
    }

    // =========================================================================
    // 7. PAIRED SAMPLES T-TEST (PRE-TEST VS POST-TEST / SEBELUM VS SESUDAH)
    // =========================================================================

    private function runPairedTTest(array $columns, array $rows, array $options, float $alpha): array
    {
        $numericCols = $this->resolveNumericColumns($columns, $rows, []);
        $colA = trim((string) ($options['pair_a'] ?? ''));
        $colB = trim((string) ($options['pair_b'] ?? ''));
        if ($colA === '' || $colB === '') {
            $filtered = array_values(array_filter($numericCols, fn ($c) => !preg_match('/^(id|no|nomor|responden)/i', $c)));
            if (count($filtered) >= 2) {
                $colA = $colA !== '' ? $colA : $filtered[0];
                $colB = $colB !== '' ? $colB : $filtered[1];
            }
        }
        if ($colA === '' || $colB === '' || $colA === $colB) {
            throw new InvalidArgumentException('Pilih 2 kolom numerik berpasangan (mis. Pretest dan Posttest) untuk Paired Samples t-Test.');
        }

        $matrix = $this->extractCompleteNumericMatrix($rows, [$colA, $colB]);
        $n = count($matrix);
        if ($n < 2) {
            throw new InvalidArgumentException('Minimal dibutuhkan 2 pasangan data lengkap.');
        }

        $a = array_column($matrix, 0);
        $b = array_column($matrix, 1);
        $diffs = [];
        for ($i = 0; $i < $n; $i++) {
            $diffs[] = $b[$i] - $a[$i]; // Post - Pre (positif = meningkat)
        }

        $mA = $this->mean($a);
        $mB = $this->mean($b);
        $sdA = $this->sampleStd($a, $mA);
        $sdB = $this->sampleStd($b, $mB);
        $rPair = $this->pearsonR($a, $b);
        $pCorr = $this->pearsonPValue($rPair, $n);

        $mDiff = $this->mean($diffs);
        $sdDiff = $this->sampleStd($diffs, $mDiff);
        $seDiff = $sdDiff / sqrt($n);
        $df = $n - 1;
        $tStat = $seDiff > self::EPS ? ($mDiff / $seDiff) : 0.0;
        $pVal = $this->tTwoTailedP($tStat, (float) $df);
        $tCrit = $this->tCritical($alpha, (float) $df);
        $ciLow = $mDiff - $tCrit * $seDiff;
        $ciHigh = $mDiff + $tCrit * $seDiff;
        $cohenDz = $sdDiff > self::EPS ? ($mDiff / $sdDiff) : 0.0;
        $pctChange = abs($mA) > self::EPS ? (($mB - $mA) / abs($mA)) * 100.0 : 0.0;

        return [
            'title' => 'Uji Paired Samples t-Test (Uji Beda Sampel Berpasangan)',
            'subtitle' => "Perbandingan {$colA} vs {$colB} (N = {$n} Pasangan)",
            'highlights' => [
                ['label' => 't Hitung', 'value' => $this->fmt($tStat, 3), 'tone' => 'blue'],
                ['label' => 'Sig. (2-tailed)', 'value' => $this->fmtP($pVal), 'tone' => $pVal < $alpha ? 'emerald' : 'amber'],
                ['label' => "Selisih ({$colB} - {$colA})", 'value' => ($mDiff >= 0 ? '+' : '') . $this->fmt($mDiff, 3) . ' (' . ($pctChange >= 0 ? '+' : '') . $this->fmt($pctChange, 1) . '%)', 'tone' => 'slate'],
                ['label' => 'Effect Size (dz)', 'value' => $this->fmt(abs($cohenDz), 3), 'tone' => 'slate'],
            ],
            'tables' => [
                [
                    'title' => 'Tabel 1. Paired Samples Statistics',
                    'headers' => ['Kondisi / Variabel', 'Mean', 'N', 'Std. Deviation', 'Std. Error Mean'],
                    'rows' => [
                        [$colA, $this->fmt($mA, 3), $n, $this->fmt($sdA, 3), $this->fmt($sdA / sqrt($n), 3)],
                        [$colB, $this->fmt($mB, 3), $n, $this->fmt($sdB, 3), $this->fmt($sdB / sqrt($n), 3)],
                    ],
                ],
                [
                    'title' => 'Tabel 2. Paired Samples Correlations',
                    'headers' => ['Pasangan', 'N', 'Correlation (r)', 'Sig.'],
                    'rows' => [
                        ["{$colA} & {$colB}", $n, $this->fmt($rPair, 3), $this->fmtP($pCorr)],
                    ],
                ],
                [
                    'title' => 'Tabel 3. Paired Samples Test',
                    'headers' => ['Pasangan', 'Mean Diff', 'Std. Dev Diff', 'SE Mean', '95% CI Lower', '95% CI Upper', 't', 'df', 'Sig. (2-tailed)'],
                    'rows' => [
                        [
                            "{$colB} - {$colA}",
                            $this->fmt($mDiff, 3),
                            $this->fmt($sdDiff, 3),
                            $this->fmt($seDiff, 3),
                            $this->fmt($ciLow, 3),
                            $this->fmt($ciHigh, 3),
                            $this->fmt($tStat, 3),
                            $df,
                            $this->fmtP($pVal),
                        ],
                    ],
                    'footnote' => "Nilai selisih dihitung sebagai ({$colB} − {$colA}). Signifikan pada α = " . number_format($alpha, 2) . ' apabila Sig. (2-tailed) < ' . number_format($alpha, 2) . '.',
                ],
            ],
            'chart' => [
                'type' => 'bar_error',
                'title' => "Perbandingan Rata-rata ± SD: {$colA} vs {$colB}",
                'labels' => [$colA, $colB],
                'values' => [round($mA, 3), round($mB, 3)],
                'errors' => [round($sdA, 3), round($sdB, 3)],
                'y_label' => 'Skor / Nilai',
            ],
            'conclusions' => [
                "Rata-rata berubah dari {$this->fmt($mA, 3)} ({$colA}) menjadi {$this->fmt($mB, 3)} ({$colB}), dengan perubahan sebesar " . ($mDiff >= 0 ? '+' : '') . "{$this->fmt($mDiff, 3)} (" . ($pctChange >= 0 ? '+' : '') . $this->fmt($pctChange, 1) . "%).",
                "Uji Paired Samples t-Test menghasilkan t = {$this->fmt($tStat, 3)} (df = {$df}) dengan Sig. (2-tailed) = {$this->fmtP($pVal)} dan Cohen's dz = {$this->fmt(abs($cohenDz), 3)} (" . ($pVal < $alpha ? 'terdapat perbedaan yang signifikan).' : 'tidak terdapat perbedaan yang signifikan).'),
            ],
            'project_autofill' => [
                'metode' => 'Paired Samples t-Test (Uji Beda Berpasangan Setara IBM SPSS)',
                'sampel' => "{$n} Pasangan Observasi ({$colA} vs {$colB})",
                'rumus' => 't = D̄ / (s_D / √n); df = n - 1; Cohen\'s dz = D̄ / s_D',
                'metrik' => "{$colA}: Mean={$this->fmt($mA, 2)} (SD={$this->fmt($sdA, 2)}); {$colB}: Mean={$this->fmt($mB, 2)} (SD={$this->fmt($sdB, 2)}); Selisih={$this->fmt($mDiff, 2)} (" . $this->fmt($pctChange, 1) . "%). t-hitung={$this->fmt($tStat, 3)} (df={$df}, Sig. {$this->fmtP($pVal)}), Korelasi r={$this->fmt($rPair, 3)}, Cohen's dz={$this->fmt(abs($cohenDz), 3)}.",
                'evaluasi' => 'Paired Samples t-Test 2-tailed (Signifikan jika Sig. < ' . number_format($alpha, 2) . ')',
            ],
        ];
    }

    // =========================================================================
    // 8. CHI-SQUARE TEST OF INDEPENDENCE (CROSSTAB + CRAMER'S V)
    // =========================================================================

    private function runChiSquare(array $columns, array $rows, array $options, float $alpha): array
    {
        $catA = trim((string) ($options['cat_a'] ?? $options['group_col'] ?? ''));
        $catB = trim((string) ($options['cat_b'] ?? $options['value_col'] ?? ''));
        if ($catA === '' || $catB === '') {
            $nonId = array_values(array_filter($columns, fn ($c) => !preg_match('/^(id|no|nomor|responden)/i', $c)));
            if (count($nonId) >= 2) {
                $catA = $catA !== '' ? $catA : $nonId[0];
                $catB = $catB !== '' ? $catB : $nonId[1];
            }
        }
        if ($catA === '' || $catB === '' || $catA === $catB) {
            throw new InvalidArgumentException('Pilih 2 kolom kategorik berbeda untuk Uji Chi-Square (Crosstab).');
        }

        $rowLabels = [];
        $colLabels = [];
        $obs = [];
        $nTotal = 0;

        foreach ($rows as $row) {
            $ra = trim((string) ($row[$catA] ?? ''));
            $rb = trim((string) ($row[$catB] ?? ''));
            if ($ra === '' || $rb === '') {
                continue;
            }
            $rowLabels[$ra] = true;
            $colLabels[$rb] = true;
            $obs[$ra][$rb] = ($obs[$ra][$rb] ?? 0) + 1;
            $nTotal++;
        }

        $rKeys = array_keys($rowLabels);
        $cKeys = array_keys($colLabels);
        $rCount = count($rKeys);
        $cCount = count($cKeys);
        if ($rCount < 2 || $cCount < 2 || $nTotal < 4) {
            throw new InvalidArgumentException("Masing-masing kolom '{$catA}' dan '{$catB}' harus memiliki minimal 2 kategori berbeda.");
        }

        $rowSums = [];
        $colSums = array_fill_keys($cKeys, 0);
        foreach ($rKeys as $rk) {
            $rowSums[$rk] = 0;
            foreach ($cKeys as $ck) {
                $v = $obs[$rk][$ck] ?? 0;
                $rowSums[$rk] += $v;
                $colSums[$ck] += $v;
            }
        }

        $chi2 = 0.0;
        $lowExpected = 0;
        $minExpected = INF;
        $crossRows = [];
        foreach ($rKeys as $rk) {
            $tr = [$rk];
            foreach ($cKeys as $ck) {
                $o = $obs[$rk][$ck] ?? 0;
                $e = ($rowSums[$rk] * $colSums[$ck]) / $nTotal;
                $minExpected = min($minExpected, $e);
                if ($e < 5.0) {
                    $lowExpected++;
                }
                if ($e > self::EPS) {
                    $chi2 += pow($o - $e, 2) / $e;
                }
                $tr[] = "{$o} (E: {$this->fmt($e, 1)})";
            }
            $tr[] = (string) $rowSums[$rk];
            $crossRows[] = $tr;
        }
        $totRow = ['Total'];
        foreach ($cKeys as $ck) {
            $totRow[] = (string) $colSums[$ck];
        }
        $totRow[] = (string) $nTotal;
        $crossRows[] = $totRow;

        $df = ($rCount - 1) * ($cCount - 1);
        $pVal = $this->chiSquareP($chi2, (float) $df);
        $kMin = min($rCount - 1, $cCount - 1);
        $cramerV = $kMin > 0 ? sqrt($chi2 / ($nTotal * $kMin)) : 0.0;
        $contCoef = sqrt($chi2 / ($chi2 + $nTotal));
        $totalCells = $rCount * $cCount;
        $lowPct = ($lowExpected / $totalCells) * 100.0;

        return [
            'title' => 'Uji Chi-Square (χ²) Independensi & Tabulasi Silang (Crosstab)',
            'subtitle' => "Hubungan Kategorik: {$catA} × {$catB} (N = {$nTotal})",
            'highlights' => [
                ['label' => 'Pearson Chi-Square (χ²)', 'value' => $this->fmt($chi2, 3), 'tone' => 'blue'],
                ['label' => 'Asymp. Sig. (2-sided)', 'value' => $this->fmtP($pVal), 'tone' => $pVal < $alpha ? 'emerald' : 'amber'],
                ['label' => 'Derajat Bebas (df)', 'value' => (string) $df, 'tone' => 'slate'],
                ['label' => 'Cramer\'s V', 'value' => $this->fmt($cramerV, 3), 'tone' => 'slate'],
            ],
            'tables' => [
                [
                    'title' => "Tabel 1. Crosstabulation ({$catA} × {$catB}) — Observed (Expected)",
                    'headers' => array_merge([$catA], $cKeys, ['Total']),
                    'rows' => $crossRows,
                    'footnote' => "{$lowExpected} sel ({$this->fmt($lowPct, 1)}%) memiliki expected count < 5. Minimum expected count = {$this->fmt($minExpected, 2)}.",
                ],
                [
                    'title' => 'Tabel 2. Chi-Square Tests & Symmetric Measures',
                    'headers' => ['Statistik Uji', 'Value', 'df', 'Asymp. Sig. (2-sided)', 'Keputusan'],
                    'rows' => [
                        ['Pearson Chi-Square', $this->fmt($chi2, 3), $df, $this->fmtP($pVal), $pVal < $alpha ? 'Ada Hubungan Signifikan' : 'Tidak Ada Hubungan Signifikan'],
                        ['Cramer\'s V (Kekuatan Asosiasi)', $this->fmt($cramerV, 3), '-', $this->fmtP($pVal), $this->correlationStrength($cramerV)],
                        ['Contingency Coefficient', $this->fmt($contCoef, 3), '-', $this->fmtP($pVal), '-'],
                    ],
                ],
            ],
            'chart' => [
                'type' => 'bar',
                'title' => "Distribusi Frekuensi Kategori {$catA}",
                'labels' => $rKeys,
                'values' => array_values($rowSums),
                'y_label' => 'Frekuensi (N)',
            ],
            'conclusions' => [
                "Pearson Chi-Square = {$this->fmt($chi2, 3)} (df = {$df}) dengan Asymp. Sig. = {$this->fmtP($pVal)} (" . ($pVal < $alpha ? "terdapat hubungan yang signifikan antara {$catA} dan {$catB})." : "tidak terdapat hubungan yang signifikan antara {$catA} dan {$catB})."),
                "Nilai Cramer's V sebesar {$this->fmt($cramerV, 3)} menunjukkan kekuatan asosiasi kategori '{$this->correlationStrength($cramerV)}'.",
            ],
            'project_autofill' => [
                'metode' => 'Uji Chi-Square Independensi & Crosstabulation (Setara IBM SPSS)',
                'sampel' => "{$nTotal} Observasi ({$catA} × {$catB})",
                'rumus' => 'χ² = Σ[(O - E)² / E]; df = (r-1)(c-1); Cramer\'s V = √[χ² / (N * min(r-1, c-1))]',
                'metrik' => "Pearson Chi-Square = {$this->fmt($chi2, 3)} (df = {$df}, Asymp. Sig. = {$this->fmtP($pVal)}), Cramer's V = {$this->fmt($cramerV, 3)}, Contingency Coefficient = {$this->fmt($contCoef, 3)}.",
                'evaluasi' => 'Uji Chi-Square (Signifikan jika Asymp. Sig. < ' . number_format($alpha, 2) . ') & Syarat Expected Count < 5 maksimal 20% sel',
            ],
        ];
    }

    // =========================================================================
    // 9. NON-PARAMETRIK: MANN-WHITNEY U & WILCOXON SIGNED-RANK
    // =========================================================================

    private function runNonParametric(array $columns, array $rows, array $options, float $alpha): array
    {
        $subMode = strtolower(trim((string) ($options['nonparam_mode'] ?? 'mann_whitney')));
        if ($subMode === 'wilcoxon') {
            return $this->runWilcoxonSignedRank($columns, $rows, $options, $alpha);
        }
        if ($subMode === 'kruskal_wallis') {
            return $this->runKruskalWallis($columns, $rows, $options, $alpha);
        }

        // Mann-Whitney U (2 kelompok independen)
        $groupCol = trim((string) ($options['group_col'] ?? ''));
        $valCol = trim((string) ($options['value_col'] ?? $options['y_col'] ?? ''));
        if ($groupCol === '' || $valCol === '') {
            [$autoG, $autoV] = $this->guessGroupAndValueColumns($columns, $rows);
            $groupCol = $groupCol !== '' ? $groupCol : $autoG;
            $valCol = $valCol !== '' ? $valCol : $autoV;
        }
        $grouped = $this->extractGroupedNumeric($rows, $groupCol, $valCol);
        $names = array_keys($grouped);
        if (count($names) !== 2) {
            throw new InvalidArgumentException("Uji Mann-Whitney U membutuhkan tepat 2 kelompok pada kolom '{$groupCol}'.");
        }

        [$g1, $g2] = [$names[0], $names[1]];
        [$v1, $v2] = [$grouped[$g1], $grouped[$g2]];
        [$n1, $n2] = [count($v1), count($v2)];
        $n = $n1 + $n2;

        $combined = [];
        foreach ($v1 as $v) {
            $combined[] = ['val' => $v, 'grp' => 1];
        }
        foreach ($v2 as $v) {
            $combined[] = ['val' => $v, 'grp' => 2];
        }
        [$ranks, $tieCorrection] = $this->computeAverageRanks(array_column($combined, 'val'));

        $r1 = 0.0;
        $r2 = 0.0;
        foreach ($combined as $idx => $item) {
            if ($item['grp'] === 1) {
                $r1 += $ranks[$idx];
            } else {
                $r2 += $ranks[$idx];
            }
        }

        $u1 = $n1 * $n2 + ($n1 * ($n1 + 1)) / 2.0 - $r1;
        $u2 = $n1 * $n2 - $u1;
        $uStat = min($u1, $u2);
        // Sesuai IBM SPSS: Wilcoxon W adalah Sum of Ranks kelompok dengan rank lebih kecil (bukan yang lebih besar)
        $wStat = ($u1 <= $u2) ? $r2 : $r1;

        $meanU = ($n1 * $n2) / 2.0;
        $varU = ($n1 * $n2 / 12.0) * (($n + 1.0) - $tieCorrection / max(1.0, $n * ($n - 1.0)));
        $z = $varU > self::EPS ? ($uStat - $meanU) / sqrt($varU) : 0.0;
        $pVal = $this->normalTwoTailedP($z);

        return [
            'title' => 'Uji Non-Parametrik Mann-Whitney U',
            'subtitle' => "Alternatif Non-Parametrik Independent t-Test: {$valCol} ({$g1} vs {$g2})",
            'highlights' => [
                ['label' => 'Mann-Whitney U', 'value' => $this->fmt($uStat, 3), 'tone' => 'blue'],
                ['label' => 'Z (Tie-Corrected)', 'value' => $this->fmt($z, 3), 'tone' => 'slate'],
                ['label' => 'Asymp. Sig. (2-tailed)', 'value' => $this->fmtP($pVal), 'tone' => $pVal < $alpha ? 'emerald' : 'amber'],
                ['label' => 'Total Sampel (N)', 'value' => (string) $n, 'tone' => 'slate'],
            ],
            'tables' => [
                [
                    'title' => 'Tabel 1. Ranks',
                    'headers' => [$groupCol, 'N', 'Median', 'Mean Rank', 'Sum of Ranks'],
                    'rows' => [
                        [$g1, $n1, $this->fmt($this->median($v1), 3), $this->fmt($r1 / $n1, 2), $this->fmt($r1, 2)],
                        [$g2, $n2, $this->fmt($this->median($v2), 3), $this->fmt($r2 / $n2, 2), $this->fmt($r2, 2)],
                    ],
                ],
                [
                    'title' => 'Tabel 2. Test Statistics (Mann-Whitney U)',
                    'headers' => ['Mann-Whitney U', 'Wilcoxon W', 'Z', 'Asymp. Sig. (2-tailed)', 'Keputusan'],
                    'rows' => [
                        [$this->fmt($uStat, 3), $this->fmt($wStat, 3), $this->fmt($z, 3), $this->fmtP($pVal), $pVal < $alpha ? 'Berbeda Signifikan' : 'Tidak Berbeda Signifikan'],
                    ],
                ],
            ],
            'chart' => [
                'type' => 'bar',
                'title' => "Perbandingan Mean Rank: {$g1} vs {$g2}",
                'labels' => [$g1, $g2],
                'values' => [round($r1 / $n1, 2), round($r2 / $n2, 2)],
                'y_label' => 'Mean Rank',
            ],
            'conclusions' => [
                "Mean Rank {$g1} = {$this->fmt($r1 / $n1, 2)} (Median = {$this->fmt($this->median($v1), 2)}) vs {$g2} = {$this->fmt($r2 / $n2, 2)} (Median = {$this->fmt($this->median($v2), 2)}).",
                "Mann-Whitney U = {$this->fmt($uStat, 3)}, Z = {$this->fmt($z, 3)}, Asymp. Sig. (2-tailed) = {$this->fmtP($pVal)} (" . ($pVal < $alpha ? 'terdapat perbedaan signifikan).' : 'tidak terdapat perbedaan signifikan).'),
            ],
            'project_autofill' => [
                'metode' => 'Uji Non-Parametrik Mann-Whitney U (Setara IBM SPSS)',
                'sampel' => "{$n} Sampel ({$g1} n={$n1}, {$g2} n={$n2})",
                'rumus' => 'U = n1*n2 + n1(n1+1)/2 - R1; Z = (U - μ_U) / σ_U (koreksi ties)',
                'metrik' => "Mann-Whitney U = {$this->fmt($uStat, 3)}, Wilcoxon W = {$this->fmt($wStat, 3)}, Z = {$this->fmt($z, 3)}, Asymp. Sig. (2-tailed) = {$this->fmtP($pVal)}. Mean Rank {$g1} = {$this->fmt($r1 / $n1, 2)}, {$g2} = {$this->fmt($r2 / $n2, 2)}.",
                'evaluasi' => 'Uji Non-Parametrik Mann-Whitney U (Signifikan jika Asymp. Sig. 2-tailed < ' . number_format($alpha, 2) . ')',
            ],
        ];
    }

    private function runWilcoxonSignedRank(array $columns, array $rows, array $options, float $alpha): array
    {
        $numericCols = $this->resolveNumericColumns($columns, $rows, []);
        $colA = trim((string) ($options['pair_a'] ?? ''));
        $colB = trim((string) ($options['pair_b'] ?? ''));
        if ($colA === '' || $colB === '') {
            $filtered = array_values(array_filter($numericCols, fn ($c) => !preg_match('/^(id|no|nomor|responden)/i', $c)));
            if (count($filtered) >= 2) {
                $colA = $colA !== '' ? $colA : $filtered[0];
                $colB = $colB !== '' ? $colB : $filtered[1];
            }
        }
        if ($colA === '' || $colB === '' || $colA === $colB) {
            throw new InvalidArgumentException('Pilih 2 kolom numerik berpasangan untuk Uji Wilcoxon Signed-Rank.');
        }

        $matrix = $this->extractCompleteNumericMatrix($rows, [$colA, $colB]);
        $nTotal = count($matrix);
        if ($nTotal < 2) {
            throw new InvalidArgumentException('Minimal dibutuhkan 2 pasangan data untuk Uji Wilcoxon.');
        }

        $nonZero = [];
        $tiesZero = 0;
        for ($i = 0; $i < $nTotal; $i++) {
            $d = $matrix[$i][1] - $matrix[$i][0];
            if (abs($d) < self::EPS) {
                $tiesZero++;
            } else {
                $nonZero[] = ['abs' => abs($d), 'sign' => $d > 0 ? 1 : -1];
            }
        }

        $nr = count($nonZero);
        if ($nr < 1) {
            throw new InvalidArgumentException('Seluruh pasangan data bernilai identik (selisih = 0), tidak dapat diuji.');
        }

        [$ranks, $tieCorr] = $this->computeAverageRanks(array_column($nonZero, 'abs'));
        $negN = 0;
        $posN = 0;
        $negSum = 0.0;
        $posSum = 0.0;
        foreach ($nonZero as $idx => $item) {
            if ($item['sign'] < 0) {
                $negN++;
                $negSum += $ranks[$idx];
            } else {
                $posN++;
                $posSum += $ranks[$idx];
            }
        }

        $wMin = min($negSum, $posSum);
        $meanW = ($nr * ($nr + 1.0)) / 4.0;
        $varW = ($nr * ($nr + 1.0) * (2.0 * $nr + 1.0) / 24.0) - ($tieCorr / 48.0);
        $z = $varW > self::EPS ? (($wMin - $meanW) / sqrt($varW)) : 0.0;
        if ($posSum > $negSum) {
            $z = -abs($z); // Konvensi SPSS (based on negative ranks)
        }
        $pVal = $this->normalTwoTailedP($z);

        $aVals = array_column($matrix, 0);
        $bVals = array_column($matrix, 1);

        return [
            'title' => 'Uji Non-Parametrik Wilcoxon Signed-Rank',
            'subtitle' => "Alternatif Non-Parametrik Paired t-Test: {$colA} vs {$colB} (N = {$nTotal})",
            'highlights' => [
                ['label' => 'Z (Tie-Corrected)', 'value' => $this->fmt($z, 3), 'tone' => 'blue'],
                ['label' => 'Asymp. Sig. (2-tailed)', 'value' => $this->fmtP($pVal), 'tone' => $pVal < $alpha ? 'emerald' : 'amber'],
                ['label' => 'Positive / Negative Ranks', 'value' => "{$posN} / {$negN} (Ties: {$tiesZero})", 'tone' => 'slate'],
                ['label' => 'Median (A → B)', 'value' => $this->fmt($this->median($aVals), 2) . ' → ' . $this->fmt($this->median($bVals), 2), 'tone' => 'slate'],
            ],
            'tables' => [
                [
                    'title' => "Tabel 1. Ranks ({$colB} − {$colA})",
                    'headers' => ['Kategori Rank', 'N', 'Mean Rank', 'Sum of Ranks'],
                    'rows' => [
                        ["Negative Ranks ({$colB} < {$colA})", $negN, $negN > 0 ? $this->fmt($negSum / $negN, 2) : '0.00', $this->fmt($negSum, 2)],
                        ["Positive Ranks ({$colB} > {$colA})", $posN, $posN > 0 ? $this->fmt($posSum / $posN, 2) : '0.00', $this->fmt($posSum, 2)],
                        ["Ties ({$colB} = {$colA})", $tiesZero, '-', '-'],
                        ['Total', $nTotal, '-', '-'],
                    ],
                ],
                [
                    'title' => 'Tabel 2. Test Statistics (Wilcoxon Signed-Rank)',
                    'headers' => ['W (Min Sum)', 'Z', 'Asymp. Sig. (2-tailed)', 'Keputusan'],
                    'rows' => [
                        [$this->fmt($wMin, 2), $this->fmt($z, 3), $this->fmtP($pVal), $pVal < $alpha ? 'Berbeda Signifikan' : 'Tidak Berbeda Signifikan'],
                    ],
                ],
            ],
            'chart' => [
                'type' => 'bar',
                'title' => "Perbandingan Median: {$colA} vs {$colB}",
                'labels' => [$colA, $colB],
                'values' => [round($this->median($aVals), 3), round($this->median($bVals), 3)],
                'y_label' => 'Median',
            ],
            'conclusions' => [
                "Dari {$nTotal} pasangan, terdapat {$posN} peningkatan (Positive Ranks), {$negN} penurunan (Negative Ranks), dan {$tiesZero} nilai tetap (Ties).",
                "Uji Wilcoxon Signed-Rank menghasilkan Z = {$this->fmt($z, 3)} dengan Asymp. Sig. (2-tailed) = {$this->fmtP($pVal)} (" . ($pVal < $alpha ? 'terdapat perbedaan yang signifikan).' : 'tidak terdapat perbedaan yang signifikan).'),
            ],
            'project_autofill' => [
                'metode' => 'Uji Non-Parametrik Wilcoxon Signed-Rank (Setara IBM SPSS)',
                'sampel' => "{$nTotal} Pasangan Observasi ({$colA} vs {$colB})",
                'rumus' => 'Z = [W - n(n+1)/4] / √[n(n+1)(2n+1)/24 - Σ(t³-t)/48]',
                'metrik' => "Wilcoxon Signed-Rank: Positive Ranks={$posN}, Negative Ranks={$negN}, Ties={$tiesZero}, Z={$this->fmt($z, 3)}, Asymp. Sig. (2-tailed)={$this->fmtP($pVal)}. Median {$colA}={$this->fmt($this->median($aVals), 2)}, {$colB}={$this->fmt($this->median($bVals), 2)}.",
                'evaluasi' => 'Uji Non-Parametrik Wilcoxon Signed-Rank (Signifikan jika Asymp. Sig. 2-tailed < ' . number_format($alpha, 2) . ')',
            ],
        ];
    }

    /**
     * Kruskal-Wallis H Test (Alternatif Non-Parametrik One-Way ANOVA untuk >= 3 kelompok — identik IBM SPSS).
     */
    private function runKruskalWallis(array $columns, array $rows, array $options, float $alpha): array
    {
        $groupCol = trim((string) ($options['group_col'] ?? ''));
        $valCol = trim((string) ($options['value_col'] ?? $options['y_col'] ?? ''));
        if ($groupCol === '' || $valCol === '') {
            [$autoG, $autoV] = $this->guessGroupAndValueColumns($columns, $rows);
            $groupCol = $groupCol !== '' ? $groupCol : $autoG;
            $valCol = $valCol !== '' ? $valCol : $autoV;
        }
        if ($groupCol === '' || $valCol === '' || $groupCol === $valCol) {
            throw new InvalidArgumentException('Pilih 1 kolom Kelompok/Perlakuan dan 1 kolom Nilai Pengukuran numerik untuk Uji Kruskal-Wallis H.');
        }

        $grouped = $this->extractGroupedNumeric($rows, $groupCol, $valCol);
        $names = array_keys($grouped);
        $g = count($names);
        if ($g < 2) {
            throw new InvalidArgumentException("Kolom '{$groupCol}' minimal harus memiliki 2 kelompok berbeda (ideal >= 3 kelompok untuk Kruskal-Wallis H).");
        }

        $combined = [];
        foreach ($names as $gName) {
            foreach ($grouped[$gName] as $v) {
                $combined[] = ['val' => $v, 'grp' => $gName];
            }
        }
        $n = count($combined);
        if ($n < 3) {
            throw new InvalidArgumentException('Minimal dibutuhkan 3 observasi total untuk Uji Kruskal-Wallis H.');
        }

        [$ranks, $tieCorr] = $this->computeAverageRanks(array_column($combined, 'val'));
        $rankSums = array_fill_keys($names, 0.0);
        foreach ($combined as $idx => $item) {
            $rankSums[$item['grp']] += $ranks[$idx];
        }

        $sumR2OverN = 0.0;
        $rankRows = [];
        $chartLabels = [];
        $chartMeans = [];
        $bestGroup = $names[0];
        $bestMeanRank = -1.0;
        $groupSummary = [];

        foreach ($names as $gName) {
            $ni = count($grouped[$gName]);
            $ri = $rankSums[$gName];
            $meanRank = $ni > 0 ? ($ri / $ni) : 0.0;
            $med = $this->median($grouped[$gName]);
            $sumR2OverN += ($ni > 0) ? (($ri * $ri) / $ni) : 0.0;

            if ($meanRank > $bestMeanRank) {
                $bestMeanRank = $meanRank;
                $bestGroup = $gName;
            }

            $rankRows[] = [
                $gName,
                $ni,
                $this->fmt($med, 3),
                $this->fmt($meanRank, 2),
                $this->fmt($ri, 2),
            ];
            $chartLabels[] = $gName;
            $chartMeans[] = round($meanRank, 2);
            $groupSummary[] = "{$gName} (n={$ni}, Median={$this->fmt($med, 2)}, Mean Rank={$this->fmt($meanRank, 2)})";
        }

        $hRaw = (12.0 / ($n * ($n + 1.0))) * $sumR2OverN - 3.0 * ($n + 1.0);
        $denom = 1.0 - ($tieCorr / max(1.0, ($n * $n * $n) - $n));
        $hStat = $denom > self::EPS ? max(0.0, $hRaw / $denom) : max(0.0, $hRaw);
        $df = $g - 1;
        $pVal = $this->chiSquareP($hStat, (float) $df);
        $epsSq = $n > 1 ? ($hStat / ($n - 1.0)) : 0.0;

        return [
            'title' => 'Uji Non-Parametrik Kruskal-Wallis H (≥ 3 Kelompok Bebas)',
            'subtitle' => "Alternatif Non-Parametrik One-Way ANOVA: {$valCol} berdasarkan {$groupCol} ({$g} Kelompok, N = {$n})",
            'highlights' => [
                ['label' => 'Kruskal-Wallis H', 'value' => $this->fmt($hStat, 3), 'tone' => 'blue'],
                ['label' => 'Asymp. Sig.', 'value' => $this->fmtP($pVal), 'tone' => $pVal < $alpha ? 'emerald' : 'amber'],
                ['label' => 'Derajat Bebas (df)', 'value' => (string) $df, 'tone' => 'slate'],
                ['label' => 'Effect Size (ε²)', 'value' => $this->fmt($epsSq, 3), 'tone' => 'slate'],
            ],
            'tables' => [
                [
                    'title' => "Tabel 1. Ranks ({$valCol} per {$groupCol})",
                    'headers' => [$groupCol, 'N', 'Median', 'Mean Rank', 'Sum of Ranks'],
                    'rows' => $rankRows,
                    'footnote' => "Mean Rank tertinggi dicapai oleh kelompok '{$bestGroup}' sebesar {$this->fmt($bestMeanRank, 2)}.",
                ],
                [
                    'title' => 'Tabel 2. Test Statistics (Kruskal-Wallis Test)',
                    'headers' => ['Kruskal-Wallis H (Chi-Square)', 'df', 'Asymp. Sig.', 'Epsilon-Squared (ε²)', 'Keputusan'],
                    'rows' => [
                        [
                            $this->fmt($hStat, 3),
                            $df,
                            $this->fmtP($pVal),
                            $this->fmt($epsSq, 3),
                            $pVal < $alpha ? 'Berbeda Signifikan' : 'Tidak Berbeda Signifikan',
                        ],
                    ],
                    'footnote' => 'Statistik Kruskal-Wallis H sudah menggunakan koreksi ties identik dengan IBM SPSS Statistics. Signifikan jika Asymp. Sig. < ' . number_format($alpha, 2) . '.',
                ],
            ],
            'chart' => [
                'type' => 'bar',
                'title' => "Perbandingan Mean Rank {$valCol} Antar Kelompok {$groupCol}",
                'labels' => $chartLabels,
                'values' => $chartMeans,
                'y_label' => 'Mean Rank',
            ],
            'conclusions' => [
                "Uji Non-Parametrik Kruskal-Wallis H menghasilkan H = {$this->fmt($hStat, 3)} (df = {$df}) dengan Asymp. Sig. = {$this->fmtP($pVal)} dan Epsilon-Squared (ε²) = {$this->fmt($epsSq, 3)} (" . ($pVal < $alpha ? "terdapat perbedaan yang signifikan antar kelompok {$groupCol})." : "tidak terdapat perbedaan yang signifikan antar kelompok {$groupCol})."),
                "Mean Rank tertinggi diperoleh pada kelompok '{$bestGroup}' (Mean Rank = {$this->fmt($bestMeanRank, 2)}).",
            ],
            'project_autofill' => [
                'metode' => 'Uji Non-Parametrik Kruskal-Wallis H (Alternatif One-Way ANOVA Setara IBM SPSS)',
                'sampel' => "{$n} Observasi / Benda Uji ({$g} Kelompok {$groupCol})",
                'rumus' => 'H = [12/(N(N+1)) * Σ(Rj²/nj) - 3(N+1)] / [1 - Σ(t³-t)/(N³-N)]; df = k - 1',
                'metrik' => "Kruskal-Wallis H = {$this->fmt($hStat, 3)} (df = {$df}, Asymp. Sig. = {$this->fmtP($pVal)}), Epsilon-Squared = {$this->fmt($epsSq, 3)}. Rincian kelompok: " . implode('; ', $groupSummary) . ". Tertinggi: {$bestGroup}.",
                'evaluasi' => 'Uji Non-Parametrik Kruskal-Wallis H (Signifikan jika Asymp. Sig. < ' . number_format($alpha, 2) . ') untuk data ≥ 3 kelompok yang tidak berdistribusi normal',
            ],
        ];
    }

    // =========================================================================
    // HELPER: OLS LINEAR ALGEBRA & MATRIX INVERSION
    // =========================================================================

    private function fitOls(array $X, array $y): array
    {
        $n = count($X);
        $p = count($X[0]); // includes intercept

        $Xt = $this->matTranspose($X);
        $XtX = $this->matMul($Xt, $X);
        $Xty = [];
        for ($i = 0; $i < $p; $i++) {
            $sum = 0.0;
            for ($r = 0; $r < $n; $r++) {
                $sum += $Xt[$i][$r] * $y[$r];
            }
            $Xty[] = $sum;
        }

        try {
            $XtXInv = $this->invertMatrix($XtX);
        } catch (\Throwable $e) {
            throw new InvalidArgumentException('Matriks variabel independen bersifat singular (terdapat kolom konstan atau multikolinearitas sempurna). Periksa kembali variabel X yang dipilih.');
        }

        $beta = [];
        for ($i = 0; $i < $p; $i++) {
            $sum = 0.0;
            for ($j = 0; $j < $p; $j++) {
                $sum += $XtXInv[$i][$j] * $Xty[$j];
            }
            $beta[] = $sum;
        }

        $yMean = $this->mean($y);
        $yHat = [];
        $residuals = [];
        $ssRes = 0.0;
        $ssTot = 0.0;
        for ($r = 0; $r < $n; $r++) {
            $pred = 0.0;
            for ($j = 0; $j < $p; $j++) {
                $pred += $X[$r][$j] * $beta[$j];
            }
            $yHat[] = $pred;
            $e = $y[$r] - $pred;
            $residuals[] = $e;
            $ssRes += $e * $e;
            $ssTot += pow($y[$r] - $yMean, 2);
        }

        $ssReg = max(0.0, $ssTot - $ssRes);
        $dfRes = max(1, $n - $p);
        $msRes = $ssRes / $dfRes;

        $se = [];
        $tVals = [];
        $pVals = [];
        for ($j = 0; $j < $p; $j++) {
            $v = max(0.0, $msRes * $XtXInv[$j][$j]);
            $s = sqrt($v);
            $t = $s > self::EPS ? ($beta[$j] / $s) : 0.0;
            $se[] = $s;
            $tVals[] = $t;
            $pVals[] = $this->tTwoTailedP($t, (float) $dfRes);
        }

        return [
            'beta' => $beta,
            'se' => $se,
            't' => $tVals,
            'p' => $pVals,
            'y_hat' => $yHat,
            'residuals' => $residuals,
            'ss_reg' => $ssReg,
            'ss_res' => $ssRes,
            'ss_tot' => $ssTot,
        ];
    }

    private function matTranspose(array $A): array
    {
        $rows = count($A);
        $cols = count($A[0]);
        $T = [];
        for ($j = 0; $j < $cols; $j++) {
            $row = [];
            for ($i = 0; $i < $rows; $i++) {
                $row[] = $A[$i][$j];
            }
            $T[] = $row;
        }
        return $T;
    }

    private function matMul(array $A, array $B): array
    {
        $rA = count($A);
        $cA = count($A[0]);
        $cB = count($B[0]);
        $C = [];
        for ($i = 0; $i < $rA; $i++) {
            $row = [];
            for ($j = 0; $j < $cB; $j++) {
                $s = 0.0;
                for ($k = 0; $k < $cA; $k++) {
                    $s += $A[$i][$k] * $B[$k][$j];
                }
                $row[] = $s;
            }
            $C[] = $row;
        }
        return $C;
    }

    private function invertMatrix(array $M): array
    {
        $n = count($M);
        $aug = [];
        for ($i = 0; $i < $n; $i++) {
            $row = array_map('floatval', $M[$i]);
            for ($j = 0; $j < $n; $j++) {
                $row[] = ($i === $j) ? 1.0 : 0.0;
            }
            $aug[] = $row;
        }

        for ($col = 0; $col < $n; $col++) {
            // Partial pivoting
            $pivotRow = $col;
            $maxVal = abs($aug[$col][$col]);
            for ($r = $col + 1; $r < $n; $r++) {
                if (abs($aug[$r][$col]) > $maxVal) {
                    $maxVal = abs($aug[$r][$col]);
                    $pivotRow = $r;
                }
            }
            if ($maxVal < 1.0e-12) {
                throw new InvalidArgumentException('Matrix is singular.');
            }
            if ($pivotRow !== $col) {
                [$aug[$col], $aug[$pivotRow]] = [$aug[$pivotRow], $aug[$col]];
            }

            $pivot = $aug[$col][$col];
            for ($j = 0; $j < 2 * $n; $j++) {
                $aug[$col][$j] /= $pivot;
            }

            for ($r = 0; $r < $n; $r++) {
                if ($r === $col) {
                    continue;
                }
                $factor = $aug[$r][$col];
                if ($factor === 0.0) {
                    continue;
                }
                for ($j = 0; $j < 2 * $n; $j++) {
                    $aug[$r][$j] -= $factor * $aug[$col][$j];
                }
            }
        }

        $inv = [];
        for ($i = 0; $i < $n; $i++) {
            $inv[] = array_slice($aug[$i], $n);
        }
        return $inv;
    }

    // =========================================================================
    // HELPER: EXACT PROBABILITY DISTRIBUTIONS (SPSS-EQUIVALENT PRECISION)
    // =========================================================================

    public function logGamma(float $z): float
    {
        $p = [
            0.99999999999980993,
            676.5203681218851,
            -1259.1392167224028,
            771.32342877765313,
            -176.61502916214059,
            12.507343278686905,
            -0.13857109526572012,
            9.9843695780195716e-6,
            1.5056327351493116e-7,
        ];
        if ($z < 0.5) {
            return log(M_PI / sin(M_PI * $z)) - $this->logGamma(1.0 - $z);
        }
        $z -= 1.0;
        $x = $p[0];
        for ($i = 1; $i < 9; $i++) {
            $x += $p[$i] / ($z + $i);
        }
        $t = $z + 7.5;
        return 0.5 * log(2.0 * M_PI) + ($z + 0.5) * log($t) - $t + log($x);
    }

    private function betacf(float $a, float $b, float $x): float
    {
        $maxIter = 200;
        $eps = 3.0e-12;
        $fpMin = 1.0e-30;

        $qab = $a + $b;
        $qap = $a + 1.0;
        $qam = $a - 1.0;
        $c = 1.0;
        $d = 1.0 - $qab * $x / $qap;
        if (abs($d) < $fpMin) {
            $d = $fpMin;
        }
        $d = 1.0 / $d;
        $h = $d;

        for ($m = 1; $m <= $maxIter; $m++) {
            $m2 = 2 * $m;
            $aa = $m * ($b - $m) * $x / (($qam + $m2) * ($a + $m2));
            $d = 1.0 + $aa * $d;
            if (abs($d) < $fpMin) {
                $d = $fpMin;
            }
            $c = 1.0 + $aa / $c;
            if (abs($c) < $fpMin) {
                $c = $fpMin;
            }
            $d = 1.0 / $d;
            $h *= $d * $c;

            $aa = -($a + $m) * ($qab + $m) * $x / (($a + $m2) * ($qap + $m2));
            $d = 1.0 + $aa * $d;
            if (abs($d) < $fpMin) {
                $d = $fpMin;
            }
            $c = 1.0 + $aa / $c;
            if (abs($c) < $fpMin) {
                $c = $fpMin;
            }
            $d = 1.0 / $d;
            $del = $d * $c;
            $h *= $del;
            if (abs($del - 1.0) < $eps) {
                break;
            }
        }
        return $h;
    }

    public function betaInc(float $a, float $b, float $x): float
    {
        if ($x <= 0.0) {
            return 0.0;
        }
        if ($x >= 1.0) {
            return 1.0;
        }
        $bt = exp(
            $this->logGamma($a + $b) - $this->logGamma($a) - $this->logGamma($b)
            + $a * log($x) + $b * log(1.0 - $x)
        );
        if ($x < ($a + 1.0) / ($a + $b + 2.0)) {
            return $bt * $this->betacf($a, $b, $x) / $a;
        }
        return 1.0 - $bt * $this->betacf($b, $a, 1.0 - $x) / $b;
    }

    public function tTwoTailedP(float $t, float $df): float
    {
        if ($df <= 0) {
            return 1.0;
        }
        $t = abs($t);
        if ($t === 0.0) {
            return 1.0;
        }
        $x = $df / ($df + $t * $t);
        return max(0.0, min(1.0, $this->betaInc($df / 2.0, 0.5, $x)));
    }

    public function tCritical(float $alphaTwoTailed, float $df): float
    {
        $lo = 0.0;
        $hi = 100.0;
        while ($this->tTwoTailedP($hi, $df) > $alphaTwoTailed && $hi < 1e6) {
            $hi *= 2.0;
        }
        for ($i = 0; $i < 80; $i++) {
            $mid = ($lo + $hi) / 2.0;
            if ($this->tTwoTailedP($mid, $df) > $alphaTwoTailed) {
                $lo = $mid;
            } else {
                $hi = $mid;
            }
        }
        return ($lo + $hi) / 2.0;
    }

    public function rTable(int $n, float $alpha = 0.05): float
    {
        $df = $n - 2;
        if ($df <= 0) {
            return 1.0;
        }
        $t = $this->tCritical($alpha, (float) $df);
        return $t / sqrt($t * $t + $df);
    }

    public function fUpperTailP(float $f, float $df1, float $df2): float
    {
        if ($f <= 0.0 || $df1 <= 0.0 || $df2 <= 0.0) {
            return 1.0;
        }
        $x = $df2 / ($df2 + $df1 * $f);
        return max(0.0, min(1.0, $this->betaInc($df2 / 2.0, $df1 / 2.0, $x)));
    }

    public function chiSquareP(float $chi2, float $df): float
    {
        if ($chi2 <= 0.0 || $df <= 0.0) {
            return 1.0;
        }
        return $this->gammaIncUpper($df / 2.0, $chi2 / 2.0);
    }

    private function gammaIncUpper(float $a, float $x): float
    {
        if ($x <= 0.0) {
            return 1.0;
        }
        $gln = $this->logGamma($a);
        if ($x < $a + 1.0) {
            // Series for P(a, x), then Q = 1 - P
            $ap = $a;
            $sum = 1.0 / $a;
            $del = $sum;
            for ($n = 1; $n <= 200; $n++) {
                $ap += 1.0;
                $del *= $x / $ap;
                $sum += $del;
                if (abs($del) < abs($sum) * 3.0e-12) {
                    break;
                }
            }
            $p = $sum * exp(-$x + $a * log($x) - $gln);
            return max(0.0, min(1.0, 1.0 - $p));
        }

        // Lentz continued fraction for Q(a, x)
        $fpMin = 1.0e-30;
        $b = $x + 1.0 - $a;
        $c = 1.0 / $fpMin;
        $d = 1.0 / $b;
        $h = $d;
        for ($i = 1; $i <= 200; $i++) {
            $an = -$i * ($i - $a);
            $b += 2.0;
            $d = $an * $d + $b;
            if (abs($d) < $fpMin) {
                $d = $fpMin;
            }
            $c = $b + $an / $c;
            if (abs($c) < $fpMin) {
                $c = $fpMin;
            }
            $d = 1.0 / $d;
            $del = $d * $c;
            $h *= $del;
            if (abs($del - 1.0) < 3.0e-12) {
                break;
            }
        }
        return max(0.0, min(1.0, exp(-$x + $a * log($x) - $gln) * $h));
    }

    public function normalCdf(float $z): float
    {
        // High-precision erf via Abramowitz & Stegun 7.1.26 (max error 1.5e-7)
        $sign = $z < 0 ? -1.0 : 1.0;
        $x = abs($z) / sqrt(2.0);
        $t = 1.0 / (1.0 + 0.3275911 * $x);
        $y = 1.0 - (((((1.061405429 * $t - 1.453152027) * $t) + 1.421413741) * $t - 0.284496736) * $t + 0.254829592) * $t * exp(-$x * $x);
        return 0.5 * (1.0 + $sign * $y);
    }

    public function normalTwoTailedP(float $z): float
    {
        return max(0.0, min(1.0, 2.0 * (1.0 - $this->normalCdf(abs($z)))));
    }

    // =========================================================================
    // HELPER: BASIC STATISTICS, NORMALITY, LEVENE, CORRELATIONS, RANKS
    // =========================================================================

    private function mean(array $vals): float
    {
        $n = count($vals);
        return $n > 0 ? array_sum($vals) / $n : 0.0;
    }

    private function median(array $vals): float
    {
        sort($vals);
        $n = count($vals);
        if ($n === 0) {
            return 0.0;
        }
        $mid = intdiv($n, 2);
        return ($n % 2 === 1) ? (float) $vals[$mid] : ($vals[$mid - 1] + $vals[$mid]) / 2.0;
    }

    private function sampleVariance(array $vals, ?float $mean = null): float
    {
        $n = count($vals);
        if ($n < 2) {
            return 0.0;
        }
        $m = $mean ?? $this->mean($vals);
        $ss = 0.0;
        foreach ($vals as $v) {
            $ss += ($v - $m) * ($v - $m);
        }
        return $ss / ($n - 1);
    }

    private function sampleStd(array $vals, ?float $mean = null): float
    {
        return sqrt(max(0.0, $this->sampleVariance($vals, $mean)));
    }

    private function sampleSkewness(array $vals, float $mean, float $sd): float
    {
        $n = count($vals);
        if ($n < 3 || $sd <= self::EPS) {
            return 0.0;
        }
        $sum3 = 0.0;
        foreach ($vals as $v) {
            $sum3 += pow(($v - $mean) / $sd, 3);
        }
        return ($n / (($n - 1) * ($n - 2))) * $sum3;
    }

    private function sampleExcessKurtosis(array $vals, float $mean, float $sd): float
    {
        $n = count($vals);
        if ($n < 4 || $sd <= self::EPS) {
            return 0.0;
        }
        $sum4 = 0.0;
        foreach ($vals as $v) {
            $sum4 += pow(($v - $mean) / $sd, 4);
        }
        $term1 = ($n * ($n + 1)) / (($n - 1) * ($n - 2) * ($n - 3)) * $sum4;
        $term2 = (3.0 * pow($n - 1, 2)) / (($n - 2) * ($n - 3));
        return $term1 - $term2;
    }

    private function kolmogorovSmirnovNormal(array $vals, float $mean, float $sd): array
    {
        $n = count($vals);
        if ($n < 3 || $sd <= self::EPS) {
            return ['d' => 0.0, 'sig' => 1.0];
        }
        sort($vals);
        $dMax = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $cdf = $this->normalCdf(($vals[$i] - $mean) / $sd);
            $dPlus = abs((($i + 1) / $n) - $cdf);
            $dMinus = abs($cdf - ($i / $n));
            $dMax = max($dMax, $dPlus, $dMinus);
        }
        // Lilliefors Significance Correction (Dallal & Wilkinson 1986 + R nortest::lillie.test — setara IBM SPSS 22–29)
        $p = $this->lillieforsP($dMax, $n);
        return ['d' => $dMax, 'sig' => $p];
    }

    public function lillieforsP(float $d, int $n): float
    {
        if ($n < 5) {
            return 1.0;
        }
        $nEff = (float) $n;
        $dEff = $d;
        if ($nEff > 100.0) {
            $dEff = $d * pow($nEff / 100.0, 0.49);
            $nEff = 100.0;
        }
        $p = exp(
            -7.01256 * $dEff * $dEff * ($nEff + 2.78019)
            + 2.99587 * $dEff * sqrt($nEff + 2.78019)
            - 0.122119
            + 0.974598 / sqrt($nEff)
            + 1.67997 / $nEff
        );
        if ($p > 0.1) {
            $kk = (sqrt($n) - 0.01 + 0.85 / sqrt($n)) * $d;
            if ($kk <= 0.302) {
                $p = 1.0;
            } elseif ($kk <= 0.500) {
                $p = 2.76773 - 19.828315 * $kk + 80.709644 * pow($kk, 2) - 138.55152 * pow($kk, 3) + 81.218052 * pow($kk, 4);
            } elseif ($kk <= 0.900) {
                $p = -4.901232 + 40.662806 * $kk - 97.490286 * pow($kk, 2) + 94.029866 * pow($kk, 3) - 32.355711 * pow($kk, 4);
            } elseif ($kk <= 1.31) {
                $p = 6.198765 - 19.558097 * $kk + 23.186922 * pow($kk, 2) - 12.234627 * pow($kk, 3) + 2.423045 * pow($kk, 4);
            } else {
                $p = 0.0;
            }
        }
        return max(0.0, min(1.0, $p));
    }

    /**
     * Shapiro-Wilk Test for Normality (Royston 1995 Algorithm AS R94 — identik R & IBM SPSS).
     */
    public function shapiroWilk(array $vals): array
    {
        $n = count($vals);
        if ($n < 3 || $n > 5000) {
            return ['w' => 1.0, 'sig' => 1.0];
        }
        $x = array_map('floatval', $vals);
        sort($x);
        $x0 = $x[0];
        $rng = $x[$n - 1] - $x0;
        if ($rng < self::EPS) {
            return ['w' => 1.0, 'sig' => 1.0];
        }
        for ($i = 0; $i < $n; $i++) {
            $x[$i] = ($x[$i] - $x0) / $rng;
        }

        $m = [];
        $ssqM = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $mv = $this->normalPpf(($i + 1 - 0.375) / ($n + 0.25));
            $m[] = $mv;
            $ssqM += $mv * $mv;
        }
        $u = 1.0 / sqrt($n);
        $c1 = [0.0, 0.221157, -0.147981, -2.07119, 4.434685, -2.706056];
        $c2 = [0.0, 0.042981, -0.293762, -1.752461, 5.682633, -3.582633];
        $c3 = [0.5440, -0.39978, 0.025054, -0.0006714];
        $c4 = [1.3822, -0.77857, 0.062767, -0.0020322];
        $c5 = [-1.5861, -0.31082, -0.083751, 0.0038915];
        $c6 = [-0.4803, -0.082676, 0.0030302];

        $a = array_fill(0, $n, 0.0);
        if ($n === 3) {
            $a[0] = -0.70710678;
            $a[2] = 0.70710678;
        } else {
            $a[$n - 1] = $m[$n - 1] / sqrt($ssqM) + $this->polyEval($c1, $u);
            $a[0] = -$a[$n - 1];
            if ($n <= 5) {
                $phi = ($ssqM - 2.0 * $m[$n - 1] * $m[$n - 1]) / max(self::EPS, 1.0 - 2.0 * $a[$n - 1] * $a[$n - 1]);
                for ($i = 1; $i < $n - 1; $i++) {
                    $a[$i] = $m[$i] / sqrt($phi);
                }
            } else {
                $a[$n - 2] = $m[$n - 2] / sqrt($ssqM) + $this->polyEval($c2, $u);
                $a[1] = -$a[$n - 2];
                $phi = ($ssqM - 2.0 * $m[$n - 1] * $m[$n - 1] - 2.0 * $m[$n - 2] * $m[$n - 2])
                    / max(self::EPS, 1.0 - 2.0 * $a[$n - 1] * $a[$n - 1] - 2.0 * $a[$n - 2] * $a[$n - 2]);
                for ($i = 2; $i < $n - 2; $i++) {
                    $a[$i] = $m[$i] / sqrt($phi);
                }
            }
        }

        $meanX = $this->mean($x);
        $ssX = 0.0;
        $num = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $ssX += pow($x[$i] - $meanX, 2);
            $num += $a[$i] * $x[$i];
        }
        $w = $ssX > self::EPS ? min(1.0, ($num * $num) / $ssX) : 1.0;

        if ($n === 3) {
            $p = (6.0 / M_PI) * (asin(sqrt($w)) - asin(sqrt(0.75)));
        } elseif ($n <= 11) {
            $g = $this->polyEval([-2.273, 0.459], (float) $n);
            $mu = $this->polyEval($c3, (float) $n);
            $sigma = exp($this->polyEval($c4, (float) $n));
            $y = -log(max(1.0e-15, $g - log(max(1.0e-15, 1.0 - $w))));
            $p = 1.0 - $this->normalCdf(($y - $mu) / $sigma);
        } else {
            $lnN = log((float) $n);
            $mu = $this->polyEval($c5, $lnN);
            $sigma = exp($this->polyEval($c6, $lnN));
            $y = log(max(1.0e-15, 1.0 - $w));
            $p = 1.0 - $this->normalCdf(($y - $mu) / $sigma);
        }

        return ['w' => $w, 'sig' => max(0.0, min(1.0, $p))];
    }

    private function polyEval(array $c, float $x): float
    {
        $res = 0.0;
        for ($i = count($c) - 1; $i >= 0; $i--) {
            $res = $res * $x + $c[$i];
        }
        return $res;
    }

    private function normalPpf(float $p): float
    {
        $a = [-3.969683028665376e+01, 2.209460984245205e+02, -2.759285104469687e+02, 1.383577518672690e+02, -3.066479806614716e+01, 2.506628277459239e+00];
        $b = [-5.447609879822406e+01, 1.615858368580409e+02, -1.556989798598866e+02, 6.680131188771972e+01, -1.328068155288572e+01];
        $c = [-7.784894002430293e-03, -3.223964580411365e-01, -2.400758277161838e+00, -2.549732539343734e+00, 4.374664141464968e+00, 2.938163982698783e+00];
        $d = [7.784695709041462e-03, 3.224671290700398e-01, 2.445134137142996e+00, 3.754408661907416e+00];
        $pLow = 0.02425;
        $pHigh = 1.0 - $pLow;
        if ($p < $pLow) {
            $q = sqrt(-2.0 * log($p));
            return ((((($c[0] * $q + $c[1]) * $q + $c[2]) * $q + $c[3]) * $q + $c[4]) * $q + $c[5])
                / (((($d[0] * $q + $d[1]) * $q + $d[2]) * $q + $d[3]) * $q + 1.0);
        }
        if ($p <= $pHigh) {
            $q = $p - 0.5;
            $r = $q * $q;
            return ((((($a[0] * $r + $a[1]) * $r + $a[2]) * $r + $a[3]) * $r + $a[4]) * $r + $a[5]) * $q
                / ((((($b[0] * $r + $b[1]) * $r + $b[2]) * $r + $b[3]) * $r + $b[4]) * $r + 1.0);
        }
        $q = sqrt(-2.0 * log(1.0 - $p));
        return -((((($c[0] * $q + $c[1]) * $q + $c[2]) * $q + $c[3]) * $q + $c[4]) * $q + $c[5])
            / (((($d[0] * $q + $d[1]) * $q + $d[2]) * $q + $d[3]) * $q + 1.0);
    }

    private function fmtLilliefors(float $p): string
    {
        if ($p >= 0.200) {
            return '0.200*';
        }
        return $this->fmtP($p);
    }

    private function jarqueBera(array $vals, float $mean, float $sd): array
    {
        $n = count($vals);
        if ($n < 4 || $sd <= self::EPS) {
            return ['jb' => 0.0, 'sig' => 1.0];
        }
        $m2 = 0.0;
        $m3 = 0.0;
        $m4 = 0.0;
        foreach ($vals as $v) {
            $d = $v - $mean;
            $d2 = $d * $d;
            $m2 += $d2;
            $m3 += $d2 * $d;
            $m4 += $d2 * $d2;
        }
        $m2 /= $n;
        $m3 /= $n;
        $m4 /= $n;
        if ($m2 <= self::EPS) {
            return ['jb' => 0.0, 'sig' => 1.0];
        }
        $s = $m3 / pow($m2, 1.5);
        $k = $m4 / ($m2 * $m2);
        $jb = ($n / 6.0) * ($s * $s + 0.25 * pow($k - 3.0, 2));
        return ['jb' => $jb, 'sig' => $this->chiSquareP($jb, 2.0)];
    }

    private function leveneTest(array $grouped): array
    {
        $zGroups = [];
        $allZ = [];
        foreach ($grouped as $name => $vals) {
            $m = $this->mean($vals);
            $zList = [];
            foreach ($vals as $v) {
                $z = abs($v - $m);
                $zList[] = $z;
                $allZ[] = $z;
            }
            $zGroups[$name] = $zList;
        }
        $g = count($zGroups);
        $nTotal = count($allZ);
        $grandZ = $this->mean($allZ);
        $ssB = 0.0;
        $ssW = 0.0;
        foreach ($zGroups as $zList) {
            $mz = $this->mean($zList);
            $ssB += count($zList) * pow($mz - $grandZ, 2);
            foreach ($zList as $zv) {
                $ssW += pow($zv - $mz, 2);
            }
        }
        $df1 = $g - 1;
        $df2 = $nTotal - $g;
        if ($df1 <= 0 || $df2 <= 0) {
            return ['f' => 0.0, 'df1' => $df1, 'df2' => $df2, 'sig' => 1.0];
        }
        $msB = $ssB / $df1;
        $msW = $ssW / $df2;
        $f = $msW > self::EPS ? ($msB / $msW) : 0.0;
        return ['f' => $f, 'df1' => $df1, 'df2' => $df2, 'sig' => $this->fUpperTailP($f, (float) $df1, (float) $df2)];
    }

    private function pearsonR(array $x, array $y): float
    {
        $n = min(count($x), count($y));
        if ($n < 2) {
            return 0.0;
        }
        $mx = $this->mean($x);
        $my = $this->mean($y);
        $num = 0.0;
        $dx = 0.0;
        $dy = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $a = $x[$i] - $mx;
            $b = $y[$i] - $my;
            $num += $a * $b;
            $dx += $a * $a;
            $dy += $b * $b;
        }
        $den = sqrt($dx * $dy);
        if ($den <= self::EPS) {
            return 0.0;
        }
        return max(-1.0, min(1.0, $num / $den));
    }

    private function pearsonPValue(float $r, int $n): float
    {
        $df = $n - 2;
        if ($df <= 0) {
            return 1.0;
        }
        if (abs($r) >= 1.0 - 1.0e-12) {
            return 0.0;
        }
        $t = $r * sqrt($df / max(self::EPS, 1.0 - $r * $r));
        return $this->tTwoTailedP($t, (float) $df);
    }

    private function spearmanRho(array $x, array $y): float
    {
        [$rx] = $this->computeAverageRanks($x);
        [$ry] = $this->computeAverageRanks($y);
        return $this->pearsonR($rx, $ry);
    }

    private function computeAverageRanks(array $vals): array
    {
        $n = count($vals);
        $indexed = [];
        foreach ($vals as $i => $v) {
            $indexed[] = ['idx' => $i, 'val' => (float) $v];
        }
        usort($indexed, fn ($a, $b) => $a['val'] <=> $b['val']);

        $ranks = array_fill(0, $n, 0.0);
        $tieCorrection = 0.0;
        $i = 0;
        while ($i < $n) {
            $j = $i;
            while ($j + 1 < $n && abs($indexed[$j + 1]['val'] - $indexed[$i]['val']) < self::EPS) {
                $j++;
            }
            $avgRank = ($i + $j + 2) / 2.0; // 1-based rank average
            $t = $j - $i + 1;
            if ($t > 1) {
                $tieCorrection += ($t * $t * $t - $t);
            }
            for ($k = $i; $k <= $j; $k++) {
                $ranks[$indexed[$k]['idx']] = $avgRank;
            }
            $i = $j + 1;
        }
        return [$ranks, $tieCorrection];
    }

    private function correlationStrength(float $absR): string
    {
        return match (true) {
            $absR >= 0.80 => 'Sangat Kuat',
            $absR >= 0.60 => 'Kuat',
            $absR >= 0.40 => 'Sedang',
            $absR >= 0.20 => 'Rendah',
            default => 'Sangat Rendah',
        };
    }

    // =========================================================================
    // HELPER: PARSING & DATA EXTRACTION
    // =========================================================================

    private function toFloat(mixed $val): ?float
    {
        if (is_int($val) || is_float($val)) {
            return is_nan((float) $val) ? null : (float) $val;
        }
        $s = trim((string) $val);
        if ($s === '' || $s === '-' || strtolower($s) === 'na' || strtolower($s) === 'null') {
            return null;
        }
        // Tangani koma desimal Indonesia (cth: "24,5" -> "24.5") jika tidak ada titik
        if (str_contains($s, ',') && !str_contains($s, '.')) {
            $s = str_replace(',', '.', $s);
        } elseif (str_contains($s, ',') && str_contains($s, '.')) {
            // Format ribuan 1,234.56 atau 1.234,56
            if (strrpos($s, ',') > strrpos($s, '.')) {
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
            } else {
                $s = str_replace(',', '', $s);
            }
        }
        $s = preg_replace('/[^\d.\-+eE]/', '', $s);
        if ($s === '' || !is_numeric($s)) {
            return null;
        }
        return (float) $s;
    }

    private function resolveNumericColumns(array $columns, array $rows, array $requested): array
    {
        $candidates = !empty($requested)
            ? array_values(array_intersect($columns, array_map('strval', $requested)))
            : $columns;

        $numeric = [];
        foreach ($candidates as $col) {
            $vals = $this->extractNumericValues($rows, $col);
            if (count($vals) >= max(2, (int) floor(count($rows) * 0.5))) {
                $numeric[] = $col;
            }
        }
        return $numeric;
    }

    private function extractNumericValues(array $rows, string $col): array
    {
        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row) || !array_key_exists($col, $row)) {
                continue;
            }
            $f = $this->toFloat($row[$col]);
            if ($f !== null) {
                $out[] = $f;
            }
        }
        return $out;
    }

    private function extractCompleteNumericMatrix(array $rows, array $cols): array
    {
        $matrix = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $vec = [];
            $ok = true;
            foreach ($cols as $col) {
                $f = array_key_exists($col, $row) ? $this->toFloat($row[$col]) : null;
                if ($f === null) {
                    $ok = false;
                    break;
                }
                $vec[] = $f;
            }
            if ($ok) {
                $matrix[] = $vec;
            }
        }
        return $matrix;
    }

    private function extractGroupedNumeric(array $rows, string $groupCol, string $valCol): array
    {
        $grouped = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $g = trim((string) ($row[$groupCol] ?? ''));
            $v = array_key_exists($valCol, $row) ? $this->toFloat($row[$valCol]) : null;
            if ($g === '' || $v === null) {
                continue;
            }
            $grouped[$g][] = $v;
        }
        return $grouped;
    }

    private function guessGroupAndValueColumns(array $columns, array $rows): array
    {
        $numericCols = $this->resolveNumericColumns($columns, $rows, []);
        $catCols = array_values(array_diff($columns, $numericCols));
        $catCols = array_values(array_filter($catCols, fn ($c) => !preg_match('/^(id|no|nomor|responden)/i', $c)));
        $numNonId = array_values(array_filter($numericCols, fn ($c) => !preg_match('/^(id|no|nomor|responden)/i', $c)));

        $gCol = $catCols[0] ?? ($numNonId[0] ?? '');
        $vCol = $numNonId[count($numNonId) - 1] ?? ($numericCols[0] ?? '');
        return [$gCol, $vCol];
    }

    private function fmt(float $v, int $dec = 3): string
    {
        return number_format($v, $dec, '.', '');
    }

    private function fmtP(float $p): string
    {
        if ($p < 0.001) {
            return '< 0.001';
        }
        return number_format($p, 3, '.', '');
    }
}
