<?php

namespace App\Services;

use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PlagiarismCheckService
{
    private const SOURCE_COLORS = [
        ['bg' => '#fee2e2', 'border' => '#ef4444', 'text' => '#991b1b', 'badge' => '#dc2626'],
        ['bg' => '#ffedd5', 'border' => '#f97316', 'text' => '#9a3412', 'badge' => '#ea580c'],
        ['bg' => '#f3e8ff', 'border' => '#a855f7', 'text' => '#6b21a8', 'badge' => '#7c3aed'],
        ['bg' => '#ccfbf1', 'border' => '#14b8a6', 'text' => '#115e59', 'badge' => '#0d9488'],
        ['bg' => '#dbeafe', 'border' => '#3b82f6', 'text' => '#1e40af', 'badge' => '#2563eb'],
        ['bg' => '#fce7f3', 'border' => '#ec4899', 'text' => '#9d174d', 'badge' => '#db2777'],
        ['bg' => '#fef3c7', 'border' => '#f59e0b', 'text' => '#92400e', 'badge' => '#d97706'],
        ['bg' => '#e0e7ff', 'border' => '#6366f1', 'text' => '#3730a3', 'badge' => '#4f46e5'],
    ];

    private const STOPWORDS = [
        // Indonesian
        'yang', 'dan', 'di', 'ke', 'dari', 'pada', 'dalam', 'untuk', 'dengan', 'adalah', 'ini', 'itu',
        'atau', 'sebagai', 'oleh', 'akan', 'juga', 'dapat', 'telah', 'tidak', 'tersebut', 'secara',
        'serta', 'karena', 'bahwa', 'lebih', 'sehingga', 'namun', 'tetapi', 'merupakan', 'antara',
        'melalui', 'menjadi', 'terhadap', 'bagi', 'agar', 'saat', 'suatu', 'atas', 'sebuah', 'hal',
        'jika', 'bila', 'maka', 'masih', 'sudah', 'belum', 'hanya', 'sangat', 'harus', 'bisa',
        'para', 'setiap', 'semua', 'banyak', 'lain', 'lainnya', 'yaitu', 'yakni', 'seperti', 'ada',
        // English
        'the', 'and', 'of', 'to', 'in', 'a', 'is', 'that', 'for', 'it', 'as', 'was', 'with', 'be',
        'by', 'on', 'not', 'are', 'this', 'from', 'at', 'or', 'an', 'have', 'which', 'their', 'can',
        'has', 'were', 'been', 'also', 'more', 'into', 'than', 'using', 'based', 'used', 'between',
    ];

    /**
     * Jalankan pemeriksaan kemiripan teks (Similarity Check ala Turnitin)
     * menggunakan OpenAlex, Crossref, Wikipedia (ID/EN), Web Search (Serper/DuckDuckGo),
     * serta perbandingan langsung terhadap dokumen pembanding lokal (jika diberikan).
     */
    public function analyze(string $rawText, array $options = []): array
    {
        $excludeBib = (bool) ($options['exclude_bibliography'] ?? true);
        $excludeQuotes = (bool) ($options['exclude_quotes'] ?? true);
        $excludeSmall = (bool) ($options['exclude_small_matches'] ?? true);
        $minContiguous = $excludeSmall ? 6 : 4;
        $compareText = trim((string) ($options['compare_text'] ?? ''));
        $compareTitle = trim((string) ($options['compare_title'] ?? 'Dokumen Pembanding Lokal'));

        $cleanText = $this->normalizeNewlines($rawText);
        $bibExcluded = false;

        if ($excludeBib) {
            [$cleanText, $bibExcluded] = $this->stripBibliography($cleanText);
        }

        $sentences = $this->splitIntoSentences($cleanText);
        $totalWordsAll = $this->countWords($cleanText);

        if (empty($sentences) || $totalWordsAll < 15) {
            return [
                'overall_score' => 0.0,
                'academic_score' => 0.0,
                'web_score' => 0.0,
                'local_score' => 0.0,
                'lexical_diversity' => 0.0,
                'total_words' => $totalWordsAll,
                'checked_words' => 0,
                'matched_words' => 0,
                'checked_sentences_count' => 0,
                'total_sentences_count' => count($sentences),
                'bib_excluded' => $bibExcluded,
                'quotes_excluded' => $excludeQuotes,
                'serper_active' => !empty(config('services.serper.key')),
                'sources' => [],
                'sentences' => [],
                'status_label' => 'Teks Terlalu Pendek',
                'status_level' => 'info',
            ];
        }

        // 1. Siapkan kandidat kalimat substantif (>= 8 kata, bukan heading/angka saja)
        $sentenceMeta = [];
        $candidateIndices = [];

        foreach ($sentences as $idx => $rawSentence) {
            $forCheck = $excludeQuotes ? $this->removeQuotedText($rawSentence) : $rawSentence;
            $tokens = $this->tokenize($forCheck);
            $wordCount = count($tokens);
            $isHeading = $this->looksLikeHeading($rawSentence);

            $sentenceMeta[$idx] = [
                'index' => $idx,
                'text' => $rawSentence,
                'clean_for_check' => $forCheck,
                'tokens' => $tokens,
                'word_count' => $wordCount,
                'is_heading' => $isHeading,
                'checked' => false,
                'match_percent' => 0.0,
                'contiguous_words' => 0,
                'source_key' => null,
                'matched_excerpt' => '',
            ];

            if (!$isHeading && $wordCount >= 8) {
                $candidateIndices[] = $idx;
            }
        }

        // 2. Bandingkan SEMUA kalimat dengan Dokumen Pembanding Lokal (jika pengguna memasukkan dokumen referensi)
        $sourcesPool = [];
        if ($compareText !== '' && $this->countWords($compareText) >= 15) {
            $localNorm = $this->normalizeForCompare($compareText);
            $localKey = 'local_compare_doc';
            $sourcesPool[$localKey] = [
                'key' => $localKey,
                'title' => $compareTitle !== '' ? $compareTitle : 'Dokumen Pembanding Lokal',
                'url' => '',
                'type' => 'local',
                'origin' => 'Dokumen Pembanding',
                'year' => null,
                'text' => $localNorm,
                'matched_words' => 0,
                'matched_sentences' => 0,
            ];

            foreach ($candidateIndices as $idx) {
                $m = $this->compareSentenceToCorpus($sentenceMeta[$idx]['tokens'], $localNorm, $minContiguous);
                if ($m['is_match']) {
                    $sentenceMeta[$idx]['checked'] = true;
                    $sentenceMeta[$idx]['match_percent'] = $m['score'];
                    $sentenceMeta[$idx]['contiguous_words'] = $m['contiguous'];
                    $sentenceMeta[$idx]['source_key'] = $localKey;
                    $sentenceMeta[$idx]['matched_excerpt'] = $m['excerpt'];
                }
            }
        }

        // 3. Pilih hingga 10 kalimat terdistribusi merata dari awal-tengah-akhir untuk dicek ke database Online (cepat & aman untuk serverless!)
        $maxOnlineQueries = 10;
        $onlineIndices = $this->pickDistributedIndices($candidateIndices, $maxOnlineQueries);

        // Kumpulkan query frasa unik per kalimat terpilih
        $queryPlans = [];
        foreach ($onlineIndices as $idx) {
            $phrase = $this->buildSearchPhrase($sentenceMeta[$idx]['tokens']);
            if ($phrase !== '') {
                $queryPlans[$idx] = $phrase;
                $sentenceMeta[$idx]['checked'] = true;
            }
        }

        // 4. Jalankan Pencarian Paralel ke OpenAlex, Crossref, Wikipedia (ID/EN), dan Web (Serper / DuckDuckGo)
        if (!empty($queryPlans)) {
            $fetchedSources = $this->fetchOnlineCandidates($queryPlans);
            foreach ($fetchedSources as $src) {
                $k = $src['key'];
                if (!isset($sourcesPool[$k])) {
                    $sourcesPool[$k] = $src;
                } else {
                    // Gabungkan teks potongan bila sumber yang sama muncul di beberapa query
                    if (!str_contains($sourcesPool[$k]['text'], $src['text'])) {
                        $sourcesPool[$k]['text'] .= ' ' . $src['text'];
                    }
                }
            }

            // Cocokkan SEMUA kalimat kandidat terhadap seluruh sumber yang berhasil diambil (hemat query namun cakupan penuh!)
            foreach ($candidateIndices as $idx) {
                $sentenceMeta[$idx]['checked'] = true;
                $bestScore = $sentenceMeta[$idx]['match_percent'];
                $bestContig = $sentenceMeta[$idx]['contiguous_words'];
                $bestKey = $sentenceMeta[$idx]['source_key'];
                $bestExcerpt = $sentenceMeta[$idx]['matched_excerpt'];

                foreach ($sourcesPool as $sKey => $src) {
                    if ($sKey === 'local_compare_doc') {
                        continue;
                    }
                    $m = $this->compareSentenceToCorpus($sentenceMeta[$idx]['tokens'], $src['text'], $minContiguous);
                    if ($m['is_match'] && ($m['score'] > $bestScore || ($m['score'] === $bestScore && $m['contiguous'] > $bestContig))) {
                        $bestScore = $m['score'];
                        $bestContig = $m['contiguous'];
                        $bestKey = $sKey;
                        $bestExcerpt = $m['excerpt'];
                    }
                }

                $sentenceMeta[$idx]['match_percent'] = $bestScore;
                $sentenceMeta[$idx]['contiguous_words'] = $bestContig;
                $sentenceMeta[$idx]['source_key'] = $bestKey;
                $sentenceMeta[$idx]['matched_excerpt'] = $bestExcerpt;
            }
        }

        // 5. Hitung statistik & peringkat sumber (persis gaya Turnitin Match Overview)
        $checkedWords = 0;
        $matchedWordsTotal = 0;
        $academicWords = 0;
        $webWords = 0;
        $localWords = 0;
        $checkedSentCount = 0;

        foreach ($sentenceMeta as $meta) {
            if ($meta['checked'] && $meta['word_count'] > 0) {
                $checkedWords += $meta['word_count'];
                $checkedSentCount++;
                if ($meta['source_key'] !== null && isset($sourcesPool[$meta['source_key']])) {
                    $contribWords = (int) round($meta['word_count'] * min(1.0, max(0.35, $meta['match_percent'] / 100.0)));
                    $matchedWordsTotal += $contribWords;
                    $sourcesPool[$meta['source_key']]['matched_words'] += $contribWords;
                    $sourcesPool[$meta['source_key']]['matched_sentences']++;

                    $sType = $sourcesPool[$meta['source_key']]['type'];
                    if ($sType === 'academic') {
                        $academicWords += $contribWords;
                    } elseif ($sType === 'local') {
                        $localWords += $contribWords;
                    } else {
                        $webWords += $contribWords;
                    }
                }
            }
        }

        $denom = max(1, $checkedWords);
        $overallScore = round(min(100.0, ($matchedWordsTotal / $denom) * 100.0), 1);
        $academicScore = round(min(100.0, ($academicWords / $denom) * 100.0), 1);
        $webScore = round(min(100.0, ($webWords / $denom) * 100.0), 1);
        $localScore = round(min(100.0, ($localWords / $denom) * 100.0), 1);

        // Urutkan sumber yang benar-benar cocok berdasarkan persentase terbesar -> beri nomor [1], [2], ... dan warna
        $rankedSources = array_values(array_filter($sourcesPool, fn($s) => ($s['matched_words'] ?? 0) > 0));
        usort($rankedSources, fn($a, $b) => $b['matched_words'] <=> $a['matched_words']);

        $sourceNumberMap = [];
        $sourceColorMap = [];
        $finalSources = [];

        foreach ($rankedSources as $rankIdx => $src) {
            $num = $rankIdx + 1;
            $palette = self::SOURCE_COLORS[$rankIdx % count(self::SOURCE_COLORS)];
            $pct = round(min(100.0, ($src['matched_words'] / $denom) * 100.0), 1);

            $sourceNumberMap[$src['key']] = $num;
            $sourceColorMap[$src['key']] = $palette;

            $finalSources[] = [
                'number' => $num,
                'title' => Str::limit($src['title'], 140),
                'url' => $src['url'],
                'type' => $src['type'],
                'origin' => $src['origin'],
                'year' => $src['year'],
                'percent' => max(0.5, $pct),
                'matched_words' => $src['matched_words'],
                'matched_sentences' => $src['matched_sentences'],
                'color' => $palette,
            ];
        }

        // Susun ulang daftar kalimat lengkap dengan nomor sumber & warna highlight
        $finalSentences = [];
        foreach ($sentenceMeta as $meta) {
            $sKey = $meta['source_key'];
            $isMatched = $sKey !== null && isset($sourceNumberMap[$sKey]);
            $finalSentences[] = [
                'index' => $meta['index'],
                'text' => $meta['text'],
                'word_count' => $meta['word_count'],
                'is_heading' => $meta['is_heading'],
                'matched' => $isMatched,
                'match_percent' => $isMatched ? round($meta['match_percent'], 1) : 0.0,
                'contiguous_words' => $isMatched ? $meta['contiguous_words'] : 0,
                'source_number' => $isMatched ? $sourceNumberMap[$sKey] : null,
                'source_title' => $isMatched ? Str::limit($sourcesPool[$sKey]['title'], 110) : null,
                'source_url' => $isMatched ? $sourcesPool[$sKey]['url'] : null,
                'source_origin' => $isMatched ? $sourcesPool[$sKey]['origin'] : null,
                'color' => $isMatched ? $sourceColorMap[$sKey] : null,
            ];
        }

        // Indikator keragaman kosakata (Type-Token Ratio berbasis jendela 150 kata agar adil untuk naskah panjang)
        $lexicalDiversity = $this->computeLexicalDiversity($cleanText);

        // Status kelayakan standar kampus Indonesia (umumnya batas lolos <= 20% - 25%)
        if ($overallScore <= 15.0) {
            $statusLabel = 'Aman · Lolos Standar Kampus (< 15%)';
            $statusLevel = 'safe';
        } elseif ($overallScore <= 25.0) {
            $statusLabel = 'Cukup Aman · Periksa Kalimat Berwarna (15–25%)';
            $statusLevel = 'warn';
        } else {
            $statusLabel = 'Kemiripan Tinggi · Disarankan Parafrase (> 25%)';
            $statusLevel = 'high';
        }

        return [
            'overall_score' => $overallScore,
            'academic_score' => $academicScore,
            'web_score' => $webScore,
            'local_score' => $localScore,
            'lexical_diversity' => $lexicalDiversity,
            'total_words' => $totalWordsAll,
            'checked_words' => $checkedWords,
            'matched_words' => $matchedWordsTotal,
            'checked_sentences_count' => $checkedSentCount,
            'total_sentences_count' => count($sentences),
            'sources_scanned_count' => count($sourcesPool),
            'bib_excluded' => $bibExcluded,
            'quotes_excluded' => $excludeQuotes,
            'min_contiguous' => $minContiguous,
            'serper_active' => !empty(config('services.serper.key')),
            'sources' => $finalSources,
            'sentences' => $finalSentences,
            'status_label' => $statusLabel,
            'status_level' => $statusLevel,
        ];
    }

    /**
     * Ambil kandidat sumber ilmiah & web secara paralel (Http::pool)
     */
    private function fetchOnlineCandidates(array $queryPlans): array
    {
        $serperKey = trim((string) (config('services.serper.key') ?: env('SERPER_API_KEY', '')));
        $ua = 'SkripsiLabAI/1.0 (https://muhalwisyahrir.tech; academic-similarity-checker)';

        // Batasi jumlah query paralel agar cepat (~3-6 detik di Vercel PHP)
        $planKeys = array_keys($queryPlans);
        $oaPlans = array_slice($planKeys, 0, 6);
        $crPlans = array_slice($planKeys, 0, 4);
        $wikiPlans = array_slice($planKeys, 0, 5);
        $webPlans = array_slice($planKeys, 0, 6);

        $responses = [];
        try {
            $responses = Http::pool(function (Pool $pool) use ($queryPlans, $oaPlans, $crPlans, $wikiPlans, $webPlans, $serperKey, $ua) {
                // 1. OpenAlex (250M+ jurnal internasional & SINTA Indonesia beserta abstrak!)
                foreach ($oaPlans as $idx) {
                    $q = $queryPlans[$idx];
                    $pool->as("oa_{$idx}")
                        ->timeout(6)
                        ->withHeaders(['User-Agent' => $ua])
                        ->get('https://api.openalex.org/works', [
                            'search' => $q,
                            'per-page' => 4,
                            'select' => 'id,title,doi,publication_year,abstract_inverted_index,primary_location',
                        ]);
                }

                // 2. Crossref (150M+ metadata & abstrak jurnal DOI)
                foreach ($crPlans as $idx) {
                    $q = $queryPlans[$idx];
                    $pool->as("cr_{$idx}")
                        ->timeout(6)
                        ->withHeaders(['User-Agent' => $ua])
                        ->get('https://api.crossref.org/works', [
                            'query.bibliographic' => $q,
                            'rows' => 3,
                            'select' => 'DOI,title,abstract,published-print,published-online,URL',
                        ]);
                }

                // 3. Wikipedia Indonesia (sangat sering jadi sumber copy-paste definisi di Bab II!)
                foreach ($wikiPlans as $idx) {
                    $q = $queryPlans[$idx];
                    $pool->as("wiki_id_{$idx}")
                        ->timeout(5)
                        ->withHeaders(['User-Agent' => $ua])
                        ->get('https://id.wikipedia.org/w/api.php', [
                            'action' => 'query',
                            'list' => 'search',
                            'srsearch' => $q,
                            'srlimit' => 3,
                            'format' => 'json',
                        ]);
                }

                // 4. Web Search: Serper.dev (Google exact-phrase -> mencakup repository skripsi kampus Indonesia) atau DuckDuckGo Lite gratis
                foreach ($webPlans as $idx) {
                    $q = $queryPlans[$idx];
                    if ($serperKey !== '') {
                        $pool->as("serper_{$idx}")
                            ->timeout(6)
                            ->withHeaders([
                                'X-API-KEY' => $serperKey,
                                'Content-Type' => 'application/json',
                            ])
                            ->post('https://google.serper.dev/search', [
                                'q' => '"' . $q . '"',
                                'gl' => 'id',
                                'hl' => 'id',
                                'num' => 5,
                            ]);
                    } else {
                        $pool->as("ddg_{$idx}")
                            ->timeout(5)
                            ->withHeaders([
                                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
                            ])
                            ->get('https://html.duckduckgo.com/html/', [
                                'q' => $q,
                            ]);
                    }
                }
            });
        } catch (\Throwable $e) {
            Log::warning('PlagiarismCheckService pool warning: ' . $e->getMessage());
        }

        $sources = [];

        foreach ($responses as $key => $resp) {
            if (!$resp || $resp instanceof \Throwable) {
                continue;
            }
            try {
                if (!$resp->successful()) {
                    continue;
                }

                if (str_starts_with($key, 'oa_')) {
                    $json = $resp->json();
                    foreach (($json['results'] ?? []) as $work) {
                        $title = trim((string) ($work['title'] ?? ''));
                        $abstract = $this->reconstructOpenAlexAbstract($work['abstract_inverted_index'] ?? null);
                        $combined = $this->normalizeForCompare($title . ' ' . $abstract);
                        if (strlen($combined) < 30) {
                            continue;
                        }
                        $url = (string) ($work['doi'] ?? ($work['primary_location']['landing_page_url'] ?? ($work['id'] ?? '')));
                        $sKey = 'oa_' . md5($url ?: $title);
                        $sources[] = [
                            'key' => $sKey,
                            'title' => $title !== '' ? $title : 'Artikel Ilmiah OpenAlex',
                            'url' => $url,
                            'type' => 'academic',
                            'origin' => 'Jurnal Ilmiah (OpenAlex)',
                            'year' => $work['publication_year'] ?? null,
                            'text' => $combined,
                            'matched_words' => 0,
                            'matched_sentences' => 0,
                        ];
                    }
                } elseif (str_starts_with($key, 'cr_')) {
                    $json = $resp->json();
                    foreach (($json['message']['items'] ?? []) as $item) {
                        $title = is_array($item['title'] ?? null) ? trim((string) ($item['title'][0] ?? '')) : '';
                        $abs = strip_tags((string) ($item['abstract'] ?? ''));
                        $combined = $this->normalizeForCompare($title . ' ' . $abs);
                        if (strlen($combined) < 30) {
                            continue;
                        }
                        $doi = trim((string) ($item['DOI'] ?? ''));
                        $url = $doi !== '' ? 'https://doi.org/' . $doi : (string) ($item['URL'] ?? '');
                        $year = $item['published-print']['date-parts'][0][0]
                            ?? $item['published-online']['date-parts'][0][0]
                            ?? null;
                        $sKey = 'cr_' . md5($url ?: $title);
                        $sources[] = [
                            'key' => $sKey,
                            'title' => $title !== '' ? $title : 'Publikasi Ilmiah Crossref',
                            'url' => $url,
                            'type' => 'academic',
                            'origin' => 'Publikasi DOI (Crossref)',
                            'year' => $year,
                            'text' => $combined,
                            'matched_words' => 0,
                            'matched_sentences' => 0,
                        ];
                    }
                } elseif (str_starts_with($key, 'wiki_id_')) {
                    $json = $resp->json();
                    foreach (($json['query']['search'] ?? []) as $hit) {
                        $title = trim((string) ($hit['title'] ?? ''));
                        $snippet = html_entity_decode(strip_tags((string) ($hit['snippet'] ?? '')), ENT_QUOTES, 'UTF-8');
                        $combined = $this->normalizeForCompare($title . ' ' . $snippet);
                        if (strlen($combined) < 25) {
                            continue;
                        }
                        $url = 'https://id.wikipedia.org/wiki/' . rawurlencode(str_replace(' ', '_', $title));
                        $sKey = 'wiki_' . md5($url);
                        $sources[] = [
                            'key' => $sKey,
                            'title' => $title . ' — Wikipedia Indonesia',
                            'url' => $url,
                            'type' => 'web',
                            'origin' => 'Wikipedia Indonesia',
                            'year' => null,
                            'text' => $combined,
                            'matched_words' => 0,
                            'matched_sentences' => 0,
                        ];
                    }
                } elseif (str_starts_with($key, 'serper_')) {
                    $json = $resp->json();
                    foreach (($json['organic'] ?? []) as $org) {
                        $title = trim((string) ($org['title'] ?? ''));
                        $snippet = trim((string) ($org['snippet'] ?? ''));
                        $link = trim((string) ($org['link'] ?? ''));
                        $combined = $this->normalizeForCompare($title . ' ' . $snippet);
                        if (strlen($combined) < 25) {
                            continue;
                        }
                        $host = parse_url($link, PHP_URL_HOST) ?: 'Internet';
                        $isAcademicHost = str_contains($host, '.ac.id')
                            || str_contains($host, '.edu')
                            || str_contains($host, 'scholar')
                            || str_contains($host, 'garuda')
                            || str_contains($host, 'neliti')
                            || str_contains($host, 'researchgate')
                            || str_contains($host, 'academia');
                        $sKey = 'web_' . md5($link ?: $title);
                        $sources[] = [
                            'key' => $sKey,
                            'title' => $title !== '' ? $title : $host,
                            'url' => $link,
                            'type' => $isAcademicHost ? 'academic' : 'web',
                            'origin' => $isAcademicHost ? "Repository / Jurnal ({$host})" : "Web ({$host})",
                            'year' => null,
                            'text' => $combined,
                            'matched_words' => 0,
                            'matched_sentences' => 0,
                        ];
                    }
                } elseif (str_starts_with($key, 'ddg_')) {
                    $html = (string) $resp->body();
                    if (preg_match_all('/class="result__a"[^>]*href="([^"]+)"[^>]*>(.*?)<\/a>[\s\S]*?class="result__snippet"[^>]*>(.*?)<\/a>/i', $html, $matches, PREG_SET_ORDER)) {
                        foreach (array_slice($matches, 0, 4) as $m) {
                            $rawHref = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
                            if (preg_match('/uddg=([^&]+)/', $rawHref, $um)) {
                                $rawHref = urldecode($um[1]);
                            }
                            $title = trim(html_entity_decode(strip_tags($m[2]), ENT_QUOTES, 'UTF-8'));
                            $snippet = trim(html_entity_decode(strip_tags($m[3]), ENT_QUOTES, 'UTF-8'));
                            $combined = $this->normalizeForCompare($title . ' ' . $snippet);
                            if (strlen($combined) < 25) {
                                continue;
                            }
                            $host = parse_url($rawHref, PHP_URL_HOST) ?: 'Web';
                            $isAcademicHost = str_contains($host, '.ac.id')
                                || str_contains($host, '.edu')
                                || str_contains($host, 'neliti')
                                || str_contains($host, 'garuda')
                                || str_contains($host, 'jurnal')
                                || str_contains($host, 'journal');
                            $sKey = 'ddg_' . md5($rawHref ?: $title);
                            $sources[] = [
                                'key' => $sKey,
                                'title' => $title !== '' ? $title : $host,
                                'url' => str_starts_with($rawHref, 'http') ? $rawHref : '',
                                'type' => $isAcademicHost ? 'academic' : 'web',
                                'origin' => $isAcademicHost ? "Repository / Jurnal ({$host})" : "Web ({$host})",
                                'year' => null,
                                'text' => $combined,
                                'matched_words' => 0,
                                'matched_sentences' => 0,
                            ];
                        }
                    }
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return $sources;
    }

    /**
     * Rekonstruksi abstract_inverted_index OpenAlex menjadi teks penuh
     */
    private function reconstructOpenAlexAbstract($inverted): string
    {
        if (!is_array($inverted) || empty($inverted)) {
            return '';
        }
        $words = [];
        foreach ($inverted as $word => $positions) {
            if (!is_array($positions)) {
                continue;
            }
            foreach ($positions as $pos) {
                if (is_int($pos) && $pos >= 0 && $pos < 1200) {
                    $words[$pos] = (string) $word;
                }
            }
        }
        ksort($words);
        return implode(' ', $words);
    }

    /**
     * Algoritma inti pencocokan kalimat terhadap teks sumber:
     * 1) Longest Contiguous Word Span (deteksi salin tempel berurutan ala Turnitin)
     * 2) 4-gram Shingle Containment (deteksi frasa terpotong / dipindah)
     * 3) Content-Word Overlap (deteksi parafrase dangkal / ganti 1-2 kata)
     */
    private function compareSentenceToCorpus(array $sentTokens, string $corpusNorm, int $minContiguous = 6): array
    {
        $n = count($sentTokens);
        if ($n < 6 || $corpusNorm === '') {
            return ['is_match' => false, 'score' => 0.0, 'contiguous' => 0, 'excerpt' => ''];
        }

        $paddedCorpus = ' ' . $corpusNorm . ' ';

        // 1. Cari span kata berurutan terpanjang yang benar-benar ada di corpus
        $maxSpan = 0;
        $bestExcerpt = '';
        for ($i = 0; $i < $n; $i++) {
            if ($n - $i <= $maxSpan) {
                break;
            }
            // Cek cepat apakah 3 kata berurutan mulai di $i ada di corpus
            if ($i + 3 <= $n) {
                $tri = ' ' . $sentTokens[$i] . ' ' . $sentTokens[$i + 1] . ' ' . $sentTokens[$i + 2] . ' ';
                if (!str_contains($paddedCorpus, $tri)) {
                    continue;
                }
            }
            $span = 3;
            while ($i + $span < $n) {
                $cand = ' ' . implode(' ', array_slice($sentTokens, $i, $span + 1)) . ' ';
                if (str_contains($paddedCorpus, $cand)) {
                    $span++;
                } else {
                    break;
                }
            }
            if ($span > $maxSpan) {
                $maxSpan = $span;
                $bestExcerpt = implode(' ', array_slice($sentTokens, $i, $span));
            }
        }

        // 2. 4-gram Shingle Containment
        $shingleCount = max(0, $n - 3);
        $shingleHits = 0;
        if ($shingleCount > 0) {
            for ($i = 0; $i < $shingleCount; $i++) {
                $g4 = ' ' . $sentTokens[$i] . ' ' . $sentTokens[$i + 1] . ' ' . $sentTokens[$i + 2] . ' ' . $sentTokens[$i + 3] . ' ';
                if (str_contains($paddedCorpus, $g4)) {
                    $shingleHits++;
                }
            }
        }
        $shingleRatio = $shingleCount > 0 ? ($shingleHits / $shingleCount) : 0.0;

        // 3. Content-Word Overlap (kata bermakna non-stopword, panjang >= 4 huruf)
        $contentTokens = array_values(array_unique(array_filter($sentTokens, function ($t) {
            return strlen($t) >= 4 && !in_array($t, self::STOPWORDS, true);
        })));
        $contentHits = 0;
        foreach ($contentTokens as $ct) {
            if (str_contains($paddedCorpus, ' ' . $ct . ' ')) {
                $contentHits++;
            }
        }
        $contentRatio = count($contentTokens) >= 4 ? ($contentHits / count($contentTokens)) : 0.0;

        // Kriteria kecocokan yang adil (anti false-positive, tetapi tajam menangkap salinan & parafrase dangkal):
        $spanRatio = $maxSpan / $n;
        $isExactCopy = $maxSpan >= $minContiguous;
        $isShingleCopy = $shingleHits >= 2 && $shingleRatio >= 0.28 && $maxSpan >= 4;
        $isLightParaphrase = count($contentTokens) >= 6 && $contentRatio >= 0.72 && $maxSpan >= 3;

        if (!$isExactCopy && !$isShingleCopy && !$isLightParaphrase) {
            return ['is_match' => false, 'score' => 0.0, 'contiguous' => $maxSpan, 'excerpt' => ''];
        }

        $score = max(
            $spanRatio * 100.0,
            $shingleRatio * 100.0,
            ($isLightParaphrase ? $contentRatio * 75.0 : 0.0)
        );
        $score = round(min(100.0, max(30.0, $score)), 1);

        return [
            'is_match' => true,
            'score' => $score,
            'contiguous' => $maxSpan,
            'excerpt' => $bestExcerpt,
        ];
    }

    private function buildSearchPhrase(array $tokens): string
    {
        if (count($tokens) < 6) {
            return '';
        }
        // Pilih jendela 8 kata yang paling kaya kata kunci (paling sedikit stopword)
        $winSize = min(8, count($tokens));
        $bestPhrase = '';
        $bestScore = -1;

        for ($i = 0; $i <= count($tokens) - $winSize; $i++) {
            $slice = array_slice($tokens, $i, $winSize);
            $score = 0;
            foreach ($slice as $w) {
                if (strlen($w) >= 4 && !in_array($w, self::STOPWORDS, true)) {
                    $score += 2;
                } elseif (strlen($w) >= 3) {
                    $score += 1;
                }
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestPhrase = implode(' ', $slice);
            }
        }

        return $bestPhrase;
    }

    private function pickDistributedIndices(array $indices, int $limit): array
    {
        $total = count($indices);
        if ($total <= $limit) {
            return $indices;
        }
        $picked = [];
        $step = ($total - 1) / max(1, $limit - 1);
        for ($i = 0; $i < $limit; $i++) {
            $pos = (int) round($i * $step);
            $picked[] = $indices[min($total - 1, $pos)];
        }
        return array_values(array_unique($picked));
    }

    private function stripBibliography(string $text): array
    {
        if (preg_match('/\n\s*(?:DAFTAR\s+PUSTAKA|REFERENCES|BIBLIOGRAFI|DAFTAR\s+REFERENSI)\s*\n/i', $text, $m, PREG_OFFSET_CAPTURE)) {
            $offset = $m[0][1];
            // Hanya potong bila posisi daftar pustaka berada di paruh akhir dokumen (> 40%)
            if ($offset > strlen($text) * 0.40) {
                return [trim(substr($text, 0, $offset)), true];
            }
        }
        return [$text, false];
    }

    private function removeQuotedText(string $sentence): string
    {
        $cleaned = preg_replace('/"[^"]{10,400}"|“[^”]{10,400}”/u', ' ', $sentence);
        return trim((string) preg_replace('/\s+/', ' ', (string) $cleaned));
    }

    private function splitIntoSentences(string $text): array
    {
        $paragraphs = preg_split('/\n+/', trim($text)) ?: [];
        $out = [];
        foreach ($paragraphs as $para) {
            $para = trim($para);
            if ($para === '') {
                continue;
            }
            if ($this->looksLikeHeading($para)) {
                $out[] = $para;
                continue;
            }

            // Lindungi singkatan umum & angka desimal tanpa lookbehind (100% kompatibel semua versi PCRE / Vercel PHP)
            $protected = preg_replace(
                '/\b(dr|prof|mr|mrs|ms|no|hal|hlm|vol|pp|ed|al|dkk|dsb|dst|dll|yth|s\.kom|m\.kom|s\.pd|m\.pd|s\.e|m\.m|s\.h|m\.h)\./iu',
                '$1__DOT__',
                $para
            );
            $protected = preg_replace('/(\d)\.(\d)/', '$1__DOT__$2', (string) $protected);

            $parts = preg_split('/([.!?]+)\s+(?=[A-Z0-9“"\(])/u', (string) $protected, -1, PREG_SPLIT_DELIM_CAPTURE);
            if (!$parts) {
                $out[] = $para;
                continue;
            }
            $buf = '';
            for ($i = 0; $i < count($parts); $i++) {
                $piece = $parts[$i];
                if (preg_match('/^[.!?]+$/', $piece)) {
                    $buf .= $piece;
                    $restored = trim(str_replace('__DOT__', '.', $buf));
                    if ($restored !== '') {
                        $out[] = $restored;
                    }
                    $buf = '';
                } else {
                    if ($buf !== '') {
                        $restored = trim(str_replace('__DOT__', '.', $buf));
                        if ($restored !== '') {
                            $out[] = $restored;
                        }
                    }
                    $buf = $piece;
                }
            }
            $restored = trim(str_replace('__DOT__', '.', $buf));
            if ($restored !== '') {
                $out[] = $restored;
            }
        }
        return array_values(array_filter($out, fn($s) => trim($s) !== ''));
    }

    private function looksLikeHeading(string $line): bool
    {
        $t = trim($line);
        if (strlen($t) <= 85 && preg_match('/^(?:BAB\s+[IVX0-9]+|\d+\.\d+(?:\.\d+)?\s+[A-Z]|[A-Z]\.\s+[A-Z]|TABEL\s+\d|GAMBAR\s+\d)/u', $t)) {
            return true;
        }
        return $this->countWords($t) <= 5 && !preg_match('/[.!?]$/', $t);
    }

    private function normalizeNewlines(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+/', ' ', $text);
        return trim((string) $text);
    }

    private function normalizeForCompare(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^a-z0-9\s]/u', ' ', $text);
        $text = preg_replace('/\s+/', ' ', (string) $text);
        return trim((string) $text);
    }

    private function tokenize(string $text): array
    {
        $norm = $this->normalizeForCompare($text);
        if ($norm === '') {
            return [];
        }
        return array_values(array_filter(explode(' ', $norm), fn($w) => $w !== ''));
    }

    private function countWords(string $text): int
    {
        return count($this->tokenize($text));
    }

    private function computeLexicalDiversity(string $text): float
    {
        $tokens = $this->tokenize($text);
        if (count($tokens) < 20) {
            return 0.0;
        }
        // Moving-Average Type-Token Ratio (MATTR) dengan jendela 100 kata agar stabil untuk teks pendek maupun panjang
        $win = min(100, count($tokens));
        $steps = max(1, count($tokens) - $win + 1);
        $stride = max(1, (int) floor($steps / 25));
        $sum = 0.0;
        $cnt = 0;
        for ($i = 0; $i < $steps; $i += $stride) {
            $slice = array_slice($tokens, $i, $win);
            $sum += count(array_unique($slice)) / $win;
            $cnt++;
        }
        return round(($sum / max(1, $cnt)) * 100.0, 1);
    }
}
