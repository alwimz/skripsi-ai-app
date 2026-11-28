<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Layanan konversi dokumen 1:1 Word (.docx) ⇄ PDF.
 * Prioritas:
 * 1. Self-hosted gratis Unlimited (CONVERTER_SERVICE_URL: Docker LibreOffice + pdf2docx di Hugging Face Spaces / VPS)
 * 2. CloudConvert Free Tier API (CLOUDCONVERT_API_KEY: 25 konversi/hari gratis)
 * 3. Mengembalikan null agar controller otomatis fallback ke engine lokal PHP (Dompdf / PhpWord).
 */
class DocumentConverterService
{
    public function hasRemoteEngine(): bool
    {
        return trim((string) config('services.converter.url', '')) !== ''
            || trim((string) config('services.converter.cloudconvert_key', '')) !== '';
    }

    public function convertDocxToPdf(string $docxPath, string $originalName = 'document.docx'): ?string
    {
        if (!is_file($docxPath)) {
            return null;
        }

        $serviceUrl = rtrim(trim((string) config('services.converter.url', '')), '/');
        if ($serviceUrl !== '') {
            $bytes = $this->callSelfHosted($serviceUrl . '/convert/docx-to-pdf', $docxPath, $originalName);
            if ($bytes !== null && str_starts_with($bytes, '%PDF')) {
                return $bytes;
            }
        }

        $ccKey = trim((string) config('services.converter.cloudconvert_key', ''));
        if ($ccKey !== '') {
            $bytes = $this->callCloudConvert($ccKey, $docxPath, $originalName, 'docx', 'pdf');
            if ($bytes !== null && str_starts_with($bytes, '%PDF')) {
                return $bytes;
            }
        }

        return null;
    }

    public function convertPdfToDocx(string $pdfPath, string $originalName = 'document.pdf'): ?string
    {
        if (!is_file($pdfPath)) {
            return null;
        }

        $serviceUrl = rtrim(trim((string) config('services.converter.url', '')), '/');
        if ($serviceUrl !== '') {
            $bytes = $this->callSelfHosted($serviceUrl . '/convert/pdf-to-docx', $pdfPath, $originalName);
            if ($bytes !== null && str_starts_with($bytes, 'PK')) {
                return $bytes;
            }
        }

        $ccKey = trim((string) config('services.converter.cloudconvert_key', ''));
        if ($ccKey !== '') {
            $bytes = $this->callCloudConvert($ccKey, $pdfPath, $originalName, 'pdf', 'docx');
            if ($bytes !== null && str_starts_with($bytes, 'PK')) {
                return $bytes;
            }
        }

        return null;
    }

    protected function callSelfHosted(string $endpoint, string $filePath, string $originalName): ?string
    {
        try {
            $headers = [];
            $secret = trim((string) config('services.converter.secret', ''));
            if ($secret !== '') {
                $headers['X-Converter-Secret'] = $secret;
            }

            $raw = @file_get_contents($filePath);
            if ($raw === false || $raw === '') {
                return null;
            }

            $res = Http::withHeaders($headers)
                ->timeout(65)
                ->connectTimeout(12)
                ->attach('file', $raw, $originalName)
                ->post($endpoint);

            if ($res->successful() && strlen($res->body()) > 200) {
                return $res->body();
            }

            Log::warning('Self-hosted converter non-200 response', [
                'endpoint' => $endpoint,
                'status' => $res->status(),
                'body' => substr($res->body(), 0, 200),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Self-hosted converter failed, falling back: ' . $e->getMessage());
        }

        return null;
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
                return null;
            }

            $job = $jobRes->json('data');
            $jobId = $job['id'] ?? null;
            $importTask = collect($job['tasks'] ?? [])->firstWhere('name', 'import-file');
            $uploadUrl = $importTask['result']['form']['url'] ?? null;
            $uploadParams = $importTask['result']['form']['parameters'] ?? [];

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
                return null;
            }

            // Tunggu job selesai lewat endpoint sync wait CloudConvert
            $waitRes = Http::withToken($apiKey)
                ->timeout(65)
                ->get("https://sync.api.cloudconvert.com/v2/jobs/{$jobId}");

            if (!$waitRes->successful()) {
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
