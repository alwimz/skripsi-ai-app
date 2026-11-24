<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\BillingService;
use App\Services\PlagiarismCheckService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use ZipArchive;

class PlagiarismController extends Controller
{
    protected PlagiarismCheckService $plagiarism;
    protected BillingService $billing;

    public function __construct(PlagiarismCheckService $plagiarism, BillingService $billing)
    {
        $this->plagiarism = $plagiarism;
        $this->billing = $billing;
    }

    /**
     * Endpoint Cek Plagiasi / Similarity Checker (mendukung teks langsung, file .docx/.pdf/.txt, atau proyek tersimpan)
     */
    public function check(Request $request)
    {
        $request->validate([
            'text' => 'nullable|string|max:180000',
            'project_id' => 'nullable|integer',
            'file' => 'nullable|file|max:8192',
            'compare_text' => 'nullable|string|max:180000',
            'compare_file' => 'nullable|file|max:8192',
            'doc_title' => 'nullable|string|max:240',
            'exclude_bibliography' => 'nullable',
            'exclude_quotes' => 'nullable',
            'exclude_small_matches' => 'nullable',
        ]);

        $docTitle = trim((string) $request->input('doc_title', ''));
        $rawText = trim((string) $request->input('text', ''));

        if ($request->filled('project_id')) {
            $proj = Project::where('user_id', Auth::id())->find($request->input('project_id'));
            if (!$proj) {
                return response()->json(['status' => 'error', 'message' => 'Proyek tidak ditemukan.'], 404);
            }
            $docTitle = $proj->judul ?: 'Proyek Skripsi';
            $parts = array_filter([
                $proj->bab1_content,
                $proj->bab2_content,
                $proj->bab3_content,
                $proj->bab4_content,
                $proj->bab5_content,
            ], fn($p) => trim((string) $p) !== '');
            $rawText = implode("\n\n", $parts);
        } elseif ($request->hasFile('file')) {
            $file = $request->file('file');
            $rawText = $this->extractFileText($file);
            if ($docTitle === '') {
                $docTitle = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            }
        }

        if (mb_strlen($rawText) < 80) {
            return response()->json([
                'status' => 'error',
                'message' => 'Teks naskah minimal 80 karakter (sekitar 15 kata) agar dapat diperiksa kemiripannya.',
            ], 422);
        }

        $user = Auth::user();
        $toolReserved = false;
        if ($user) {
            $access = $this->billing->reserveToolAccess($user, 'plagiarism', 'Cek Plagiarisme');
            if (!$access['allowed']) {
                return response()->json([
                    'status' => 'upgrade_required',
                    'reason' => $access['reason'] ?? 'free_tool_limit',
                    'message' => $access['message'],
                ], 402);
            }
            $toolReserved = !empty($access['reserved']);
        }

        $compareText = trim((string) $request->input('compare_text', ''));
        $compareTitle = 'Dokumen Pembanding';
        if ($request->hasFile('compare_file')) {
            $cf = $request->file('compare_file');
            $compareText = $this->extractFileText($cf);
            $compareTitle = pathinfo($cf->getClientOriginalName(), PATHINFO_FILENAME);
        }

        $toBool = fn($val, $default = true) => $val === null
            ? $default
            : !in_array(strtolower((string) $val), ['0', 'false', 'off', 'no'], true);

        try {
            $report = $this->plagiarism->analyze($rawText, [
                'exclude_bibliography' => $toBool($request->input('exclude_bibliography'), true),
                'exclude_quotes' => $toBool($request->input('exclude_quotes'), true),
                'exclude_small_matches' => $toBool($request->input('exclude_small_matches'), true),
                'compare_text' => $compareText,
                'compare_title' => $compareTitle,
            ]);

            $report['doc_title'] = $docTitle !== '' ? $docTitle : 'Naskah Skripsi / Proposal';
            $report['checked_at'] = now()->format('d M Y, H:i') . ' WIB';

            return response()->json([
                'status' => 'success',
                'report' => $report,
            ]);
        } catch (\Throwable $e) {
            if ($user && $toolReserved) {
                $this->billing->releaseToolReservation($user, 'plagiarism');
            }
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menganalisis kemiripan naskah: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Ekspor Laporan Hasil Cek Plagiasi ke PDF A4 (Gaya Laporan Similarity Turnitin)
     */
    public function downloadReportPdf(Request $request)
    {
        $user = Auth::user();
        if ($user && !$this->billing->hasActivePaidAccess($user)) {
            return response()->json([
                'status' => 'upgrade_required',
                'reason' => 'download_locked',
                'message' => 'Download laporan PDF Cek Plagiarisme tersedia untuk Paket Starter mulai Rp15.000.',
            ], 402);
        }
        $payload = $request->input('report');
        if (is_string($payload)) {
            $payload = json_decode($payload, true);
        }
        if (!is_array($payload) || empty($payload['sentences'])) {
            return response()->json(['status' => 'error', 'message' => 'Data laporan tidak valid.'], 422);
        }

        $title = e((string) ($payload['doc_title'] ?? 'Naskah Skripsi'));
        $checkedAt = e((string) ($payload['checked_at'] ?? now()->format('d M Y, H:i')));
        $userName = e(Auth::user()->name ?? 'Mahasiswa');
        $overall = (float) ($payload['overall_score'] ?? 0);
        $academic = (float) ($payload['academic_score'] ?? 0);
        $web = (float) ($payload['web_score'] ?? 0);
        $local = (float) ($payload['local_score'] ?? 0);
        $totalWords = (int) ($payload['total_words'] ?? 0);
        $statusLabel = e((string) ($payload['status_label'] ?? 'Selesai'));

        $scoreColor = $overall <= 15 ? '#059669' : ($overall <= 25 ? '#d97706' : '#dc2626');

        $bodyHtml = '';
        foreach ($payload['sentences'] as $s) {
            $txt = e((string) ($s['text'] ?? ''));
            if (!empty($s['is_heading'])) {
                $bodyHtml .= '<div class="doc-heading">' . $txt . '</div>';
                continue;
            }
            if (!empty($s['matched']) && !empty($s['color'])) {
                $bg = e((string) ($s['color']['bg'] ?? '#fee2e2'));
                $badgeBg = e((string) ($s['color']['badge'] ?? '#dc2626'));
                $num = (int) ($s['source_number'] ?? 1);
                $bodyHtml .= '<span class="hl" style="background:' . $bg . ';"><span class="hl-num" style="background:' . $badgeBg . ';">' . $num . '</span> ' . $txt . '</span> ';
            } else {
                $bodyHtml .= $txt . ' ';
            }
        }

        $sourcesHtml = '';
        foreach (($payload['sources'] ?? []) as $src) {
            $num = (int) ($src['number'] ?? 1);
            $badgeBg = e((string) ($src['color']['badge'] ?? '#2563eb'));
            $sTitle = e((string) ($src['title'] ?? 'Sumber'));
            $sOrigin = e((string) ($src['origin'] ?? 'Internet'));
            $sUrl = e((string) ($src['url'] ?? ''));
            $sPct = number_format((float) ($src['percent'] ?? 0), 1);
            $urlLine = $sUrl !== '' ? '<div class="src-url">' . $sUrl . '</div>' : '';
            $sourcesHtml .= '<tr>'
                . '<td style="width:28px; vertical-align:top;"><span class="hl-num" style="background:' . $badgeBg . ';">' . $num . '</span></td>'
                . '<td><div class="src-title">' . $sTitle . '</div><div class="src-meta">' . $sOrigin . '</div>' . $urlLine . '</td>'
                . '<td style="width:62px; text-align:right; font-weight:700; color:' . $badgeBg . '; vertical-align:top;">' . $sPct . '%</td>'
                . '</tr>';
        }
        if ($sourcesHtml === '') {
            $sourcesHtml = '<tr><td colspan="3" style="text-align:center; color:#64748b; padding:12px;">Tidak ditemukan kecocokan signifikan pada database jurnal terbuka &amp; web.</td></tr>';
        }

        $html = '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><style>'
            . '@page { margin: 18mm 18mm 18mm 18mm; }'
            . 'body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 10pt; line-height: 1.55; }'
            . '.header { border-bottom: 2px solid #2563eb; padding-bottom: 10px; margin-bottom: 14px; }'
            . '.brand { font-size: 13pt; font-weight: 700; color: #1d4ed8; }'
            . '.sub { font-size: 8.5pt; color: #64748b; margin-top: 2px; }'
            . '.summary-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; background: #f8fafc; border: 1px solid #e2e8f0; }'
            . '.summary-table td { padding: 10px 12px; vertical-align: middle; border: 1px solid #e2e8f0; }'
            . '.big-score { font-size: 26pt; font-weight: 700; color: ' . $scoreColor . '; line-height: 1; }'
            . '.metric-lbl { font-size: 8pt; color: #64748b; text-transform: uppercase; }'
            . '.metric-val { font-size: 12pt; font-weight: 700; color: #0f172a; }'
            . '.sec-title { font-size: 10.5pt; font-weight: 700; color: #1e293b; border-left: 3.5px solid #2563eb; padding-left: 8px; margin: 16px 0 8px; }'
            . '.doc-box { border: 1px solid #e2e8f0; padding: 12px 14px; text-align: justify; font-size: 9.5pt; line-height: 1.65; }'
            . '.doc-heading { font-weight: 700; margin: 10px 0 4px; color: #0f172a; }'
            . '.hl { padding: 1px 3px; border-radius: 3px; }'
            . '.hl-num { display: inline-block; color: #fff; font-size: 7.5pt; font-weight: 700; padding: 1px 5px; border-radius: 3px; }'
            . '.src-table { width: 100%; border-collapse: collapse; margin-top: 6px; }'
            . '.src-table td { padding: 7px 6px; border-bottom: 1px solid #e2e8f0; font-size: 8.8pt; }'
            . '.src-title { font-weight: 700; color: #0f172a; }'
            . '.src-meta { font-size: 7.8pt; color: #475569; margin-top: 1px; }'
            . '.src-url { font-size: 7.5pt; color: #2563eb; word-break: break-all; }'
            . '.note { margin-top: 14px; font-size: 7.8pt; color: #64748b; background: #f8fafc; border: 1px solid #e2e8f0; padding: 8px 10px; }'
            . '</style></head><body>'
            . '<div class="header">'
            . '<div class="brand">SkripsiLab AI — Laporan Cek Kemiripan (Similarity Report)</div>'
            . '<div class="sub">Dokumen: <strong>' . $title . '</strong> · Penulis: ' . $userName . ' · Diperiksa: ' . $checkedAt . '</div>'
            . '</div>'
            . '<table class="summary-table"><tr>'
            . '<td style="width:28%; text-align:center;"><div class="big-score">' . number_format($overall, 1) . '%</div><div class="metric-lbl" style="margin-top:4px;">SIMILARITY INDEX</div></td>'
            . '<td style="width:18%; text-align:center;"><div class="metric-val">' . number_format($academic, 1) . '%</div><div class="metric-lbl">Jurnal Ilmiah</div></td>'
            . '<td style="width:18%; text-align:center;"><div class="metric-val">' . number_format($web, 1) . '%</div><div class="metric-lbl">Internet / Web</div></td>'
            . '<td style="width:18%; text-align:center;"><div class="metric-val">' . number_format($local, 1) . '%</div><div class="metric-lbl">Pembanding</div></td>'
            . '<td style="width:18%; text-align:center;"><div class="metric-val">' . number_format($totalWords) . '</div><div class="metric-lbl">Total Kata</div></td>'
            . '</tr><tr><td colspan="5" style="font-size:8.5pt; color:#334155;">Status Evaluasi: <strong>' . $statusLabel . '</strong></td></tr></table>'
            . '<div class="sec-title">Sumber Utama yang Terdeteksi (Primary Sources)</div>'
            . '<table class="src-table">' . $sourcesHtml . '</table>'
            . '<div class="sec-title">Naskah dengan Penandaan Kemiripan</div>'
            . '<div class="doc-box">' . $bodyHtml . '</div>'
            . '<div class="note">Catatan: Laporan ini merupakan Pre-Check Similarity berbasis database ilmiah terbuka (OpenAlex, Crossref, Wikipedia, Web) &amp; pencocokan frasa Shingling untuk membantu menurunkan tingkat kemiripan sebelum pemeriksaan Turnitin resmi kampus.</div>'
            . '</body></html>';

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $slug = Str::slug(Str::limit((string) ($payload['doc_title'] ?? 'naskah'), 45, '')) ?: 'naskah';
        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="Laporan_Similarity_' . $slug . '.pdf"',
        ]);
    }

    /**
     * Ekstrak teks bersih dari file .docx, .txt, atau .pdf sederhana
     */
    private function extractFileText($file): string
    {
        if (!$file || !$file->isValid()) {
            return '';
        }
        $ext = strtolower($file->getClientOriginalExtension());
        $path = $file->getRealPath();

        try {
            if ($ext === 'docx' && class_exists(ZipArchive::class)) {
                $zip = new ZipArchive();
                if ($zip->open($path) === true) {
                    $xml = $zip->getFromName('word/document.xml');
                    $zip->close();
                    if ($xml !== false) {
                        $xml = str_replace(
                            ['</w:p>', '</w:tr>', '<w:br/>', '<w:br />', '<w:cr/>', '</w:tc>', '<w:tab/>'],
                            ["\n", "\n", "\n", "\n", "\n", " ", " "],
                            $xml
                        );
                        $text = html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');
                        $text = str_replace(["\r\n", "\r"], "\n", $text);
                        $text = preg_replace('/[ \t]+/', ' ', $text);
                        $text = preg_replace('/\n{3,}/', "\n\n", $text);
                        return trim((string) $text);
                    }
                }
            }

            if (in_array($ext, ['txt', 'md'], true)) {
                return trim((string) file_get_contents($path));
            }

            if ($ext === 'pdf') {
                $raw = (string) file_get_contents($path);
                $chunks = [];
                if (preg_match_all('/stream[\r\n]+(.*?)[\r\n]+endstream/s', $raw, $matches)) {
                    foreach ($matches[1] as $stream) {
                        $dec = @gzuncompress($stream);
                        if ($dec === false) {
                            $dec = @gzinflate($stream);
                        }
                        $buf = $dec !== false ? $dec : $stream;
                        if (preg_match_all('/\(([^()]{2,500})\)/', $buf, $tm)) {
                            foreach ($tm[1] as $t) {
                                $t = str_replace(['\\(', '\\)', '\\\\', '\\n', '\\r'], ['(', ')', '\\', ' ', ' '], $t);
                                $t = preg_replace('/[^\x20-\x7E\xA0-\xFF]/u', ' ', $t);
                                if (trim((string) $t) !== '') {
                                    $chunks[] = trim((string) $t);
                                }
                            }
                        }
                    }
                }
                return trim((string) preg_replace('/\s+/', ' ', implode(' ', $chunks)));
            }
        } catch (\Throwable $e) {
            return '';
        }

        return '';
    }
}
