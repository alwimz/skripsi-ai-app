<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Layanan konversi dokumen 1:1 Word (.docx) ⇄ PDF menggunakan CloudConvert API v2.
 * Gratis 25 konversi/hari (~750/bulan) tanpa server tambahan.
 * Jika CLOUDCONVERT_API_KEY kosong atau kuota habis, mengembalikan null agar controller otomatis fallback ke engine lokal.
 */
class DocumentConverterService
{
    public function hasRemoteEngine(): bool
    {
        return trim((string) config('services.converter.cloudconvert_key', '')) !== '';
    }

    public function convertDocxToPdf(string $docxPath, string $originalName = 'document.docx'): ?string
    {
        if (!is_file($docxPath)) {
            return null;
        }

        $ccKey = trim((string) config('services.converter.cloudconvert_key', ''));
        if ($ccKey === '') {
            return null;
        }

        $bytes = $this->callCloudConvert($ccKey, $docxPath, $originalName, 'docx', 'pdf');
        return ($bytes !== null && str_starts_with($bytes, '%PDF')) ? $bytes : null;
    }

    public function convertPdfToDocx(string $pdfPath, string $originalName = 'document.pdf'): ?string
    {
        if (!is_file($pdfPath)) {
            return null;
        }

        $ccKey = trim((string) config('services.converter.cloudconvert_key', ''));
        if ($ccKey === '') {
            return null;
        }

        $bytes = $this->callCloudConvert($ccKey, $pdfPath, $originalName, 'pdf', 'docx');
        return ($bytes !== null && str_starts_with($bytes, 'PK')) ? $bytes : null;
    }

    protected function callCloudConvert(
        string $apiKey,
        string $filePath,
        string $originalName,
        string $inputFormat,
        string $outputFormat
    ): ?string {
        try {
            $jobRes = Http::withToken($apiKey)
                ->timeout(25)
                ->post('https://api.cloudconvert.com/v2/jobs', [
                    'tasks' => [
                        'import-file' => ['operation' => 'import/upload'],
                        'convert-file' => [
                            'operation' => 'convert',
                            'input' => 'import-file',
                            'input_format' => $inputFormat,
                            'output_format' => $outputFormat,
                        ],
                        'export-file' => [
                            'operation' => 'export/url',
                            'input' => 'convert-file',
                        ],
                    ],
                ]);

            if (!$jobRes->successful()) {
                Log::warning('CloudConvert create job failed', [
                    'status' => $jobRes->status(),
                    'body' => substr($jobRes->body(), 0, 240),
                ]);
                return null;
            }

            $job = $jobRes->json('data');
            $jobId = $job['id'] ?? null;
            $importTask = collect($job['tasks'] ?? [])->firstWhere('name', 'import-file');
            $uploadUrl = $importTask['result']['form']['url'] ?? null;
            $uploadParams = $importTask['result']['form']['parameters'] ?? [];

            // Retry singkat bila import/upload form belum langsung siap
            if ($jobId && !$uploadUrl) {
                usleep(400000);
                $pollRes = Http::withToken($apiKey)->timeout(15)->get("https://api.cloudconvert.com/v2/jobs/{$jobId}");
                if ($pollRes->successful()) {
                    $importTask = collect($pollRes->json('data.tasks') ?? [])->firstWhere('name', 'import-file');
                    $uploadUrl = $importTask['result']['form']['url'] ?? null;
                    $uploadParams = $importTask['result']['form']['parameters'] ?? [];
                }
            }

            if (!$jobId || !$uploadUrl) {
                return null;
            }

            $raw = @file_get_contents($filePath);
            if ($raw === false || $raw === '') {
                return null;
            }

            $req = Http::timeout(45);
            foreach ($uploadParams as $k => $v) {
                $req = $req->attach((string) $k, (string) $v);
            }
            $upRes = $req->attach('file', $raw, $originalName)->post($uploadUrl);
            if (!$upRes->successful()) {
                Log::warning('CloudConvert upload failed', ['status' => $upRes->status()]);
                return null;
            }

            // Tunggu job selesai lewat endpoint sync wait CloudConvert
            $waitRes = Http::withToken($apiKey)
                ->timeout(65)
                ->get("https://sync.api.cloudconvert.com/v2/jobs/{$jobId}");

            if (!$waitRes->successful()) {
                Log::warning('CloudConvert wait failed', ['status' => $waitRes->status()]);
                return null;
            }

            $exportTask = collect($waitRes->json('data.tasks') ?? [])->firstWhere('name', 'export-file');
            $downloadUrl = $exportTask['result']['files'][0]['url'] ?? null;
            if (!$downloadUrl) {
                return null;
            }

            $fileRes = Http::timeout(45)->get($downloadUrl);
            if ($fileRes->successful() && strlen($fileRes->body()) > 200) {
                return $fileRes->body();
            }
        } catch (\Throwable $e) {
            Log::warning('CloudConvert API fallback: ' . $e->getMessage());
        }

        return null;
    }
}
