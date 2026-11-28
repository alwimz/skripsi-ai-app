<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Layanan pemrosesan dokumen & gambar 1:1 berbasis iLoveAPI (iLovePDF + iLoveIMG) & CloudConvert.
 * - Word -> PDF: iLovePDF (`officepdf`) -> CloudConvert -> fallback lokal Dompdf
 * - Kompres PDF: iLovePDF (`compress`) -> fallback lokal browser
 * - Gabung PDF: iLovePDF (`merge`) -> fallback lokal browser
 * - Hapus Background AI: iLoveIMG (`removebackground`) -> fallback lokal browser
 * - PDF -> Word: CloudConvert -> fallback lokal PhpWord
 */
class DocumentConverterService
{
    public function hasIloveKey(): bool
    {
        return trim((string) config('services.ilovepdf.public_key', '')) !== '';
    }

    public function hasRemoteEngine(): bool
    {
        return $this->hasIloveKey()
            || trim((string) config('services.converter.cloudconvert_key', '')) !== '';
    }

    public function convertDocxToPdf(string $docxPath, string $originalName = 'document.docx'): ?string
    {
        if (!is_file($docxPath)) {
            return null;
        }

        if ($this->hasIloveKey()) {
            $bytes = $this->runIloveTask('api.ilovepdf.com', 'officepdf', [
                ['path' => $docxPath, 'name' => $originalName],
            ]);
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

        $ccKey = trim((string) config('services.converter.cloudconvert_key', ''));
        if ($ccKey !== '') {
            $bytes = $this->callCloudConvert($ccKey, $pdfPath, $originalName, 'pdf', 'docx');
            if ($bytes !== null && str_starts_with($bytes, 'PK')) {
                return $bytes;
            }
        }

        return null;
    }

    public function compressPdf(string $pdfPath, string $originalName = 'document.pdf', string $level = 'recommended'): ?string
    {
        if (!is_file($pdfPath) || !$this->hasIloveKey()) {
            return null;
        }

        $validLevel = in_array($level, ['low', 'recommended', 'extreme'], true) ? $level : 'recommended';
        $bytes = $this->runIloveTask('api.ilovepdf.com', 'compress', [
            ['path' => $pdfPath, 'name' => $originalName],
        ], [
            'compression_level' => $validLevel,
        ]);

        return ($bytes !== null && str_starts_with($bytes, '%PDF')) ? $bytes : null;
    }

    /**
     * @param array<int, array{path:string, name:string}> $files
     */
    public function mergePdfs(array $files): ?string
    {
        if (count($files) < 2 || !$this->hasIloveKey()) {
            return null;
        }

        $bytes = $this->runIloveTask('api.ilovepdf.com', 'merge', $files);
        return ($bytes !== null && str_starts_with($bytes, '%PDF')) ? $bytes : null;
    }

    public function removeImageBackground(string $imgPath, string $originalName = 'image.png'): ?string
    {
        if (!is_file($imgPath) || !$this->hasIloveKey()) {
            return null;
        }

        $bytes = $this->runIloveTask('api.iloveimg.com', 'removebackground', [
            ['path' => $imgPath, 'name' => $originalName],
        ]);

        // Validasi header PNG atau WebP/JPEG non-kosong
        if ($bytes !== null && strlen($bytes) > 200) {
            return $bytes;
        }

        return null;
    }

    /**
     * Alur standar iLoveAPI REST v1: auth -> start -> upload -> process -> download
     *
     * @param array<int, array{path:string, name:string}> $files
     * @param array<string, mixed> $extraParams
     */
    protected function runIloveTask(string $baseHost, string $tool, array $files, array $extraParams = []): ?string
    {
        $publicKey = trim((string) config('services.ilovepdf.public_key', ''));
        $secretKey = trim((string) config('services.ilovepdf.secret_key', ''));
        if ($publicKey === '') {
            return null;
        }

        try {
            // 1. Auth -> Bearer JWT Token
            $token = null;
            $authRes = Http::timeout(15)
                ->post("https://{$baseHost}/v1/auth", ['public_key' => $publicKey]);
            if ($authRes->successful() && is_string($authRes->json('token')) && $authRes->json('token') !== '') {
                $token = $authRes->json('token');
            } elseif ($secretKey !== '') {
                $token = $this->buildIloveJwt($publicKey, $secretKey);
            }

            if (!$token) {
                Log::warning("iLoveAPI auth failed for {$tool}", ['status' => $authRes->status()]);
                return null;
            }

            // 2. Start Task
            $startRes = Http::withToken($token)
                ->timeout(15)
                ->get("https://{$baseHost}/v1/start/{$tool}");
            if (!$startRes->successful()) {
                Log::warning("iLoveAPI start failed for {$tool}", [
                    'status' => $startRes->status(),
                    'body' => substr($startRes->body(), 0, 200),
                ]);
                return null;
            }

            $server = trim((string) $startRes->json('server', ''));
            $taskId = trim((string) $startRes->json('task', ''));
            if ($server === '' || $taskId === '') {
                return null;
            }
            $serverOrigin = str_starts_with($server, 'http') ? rtrim($server, '/') : "https://{$server}";

            // 3. Upload Files
            $uploadedFiles = [];
            foreach ($files as $idx => $f) {
                $raw = @file_get_contents($f['path']);
                if ($raw === false || $raw === '') {
                    return null;
                }
                $fname = $f['name'] ?: ("file_{$idx}");
                $upRes = Http::withToken($token)
                    ->timeout(45)
                    ->attach('task', $taskId)
                    ->attach('file', $raw, $fname)
                    ->post("{$serverOrigin}/v1/upload");

                if (!$upRes->successful()) {
                    Log::warning("iLoveAPI upload failed for {$tool}", ['status' => $upRes->status()]);
                    return null;
                }
                $serverFilename = trim((string) $upRes->json('server_filename', ''));
                if ($serverFilename === '') {
                    return null;
                }
                $uploadedFiles[] = [
                    'server_filename' => $serverFilename,
                    'filename' => $fname,
                ];
            }

            // 4. Process Task
            $payload = array_merge([
                'task' => $taskId,
                'tool' => $tool,
                'files' => $uploadedFiles,
            ], $extraParams);

            $procRes = Http::withToken($token)
                ->timeout(65)
                ->post("{$serverOrigin}/v1/process", $payload);

            if (!$procRes->successful()) {
                Log::warning("iLoveAPI process failed for {$tool}", [
                    'status' => $procRes->status(),
                    'body' => substr($procRes->body(), 0, 200),
                ]);
                return null;
            }

            // 5. Download Output
            $dlRes = Http::withToken($token)
                ->timeout(45)
                ->get("{$serverOrigin}/v1/download/{$taskId}");

            if ($dlRes->successful() && strlen($dlRes->body()) > 100) {
                return $dlRes->body();
            }
        } catch (\Throwable $e) {
            Log::warning("iLoveAPI ({$tool}) fallback: " . $e->getMessage());
        }

        return null;
    }

    protected function buildIloveJwt(string $publicKey, string $secretKey): string
    {
        $b64 = static fn (string $s): string => rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
        $header = $b64(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $now = time();
        $payload = $b64(json_encode([
            'iss' => '',
            'aud' => '',
            'iat' => $now - 60,
            'nbf' => $now - 60,
            'exp' => $now + 3600,
            'jti' => $publicKey,
        ]));
        $sig = $b64(hash_hmac('sha256', "{$header}.{$payload}", $secretKey, true));
        return "{$header}.{$payload}.{$sig}";
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
                return null;
            }

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
