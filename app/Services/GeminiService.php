<?php

namespace App\Services;

use App\Models\ApiKey;
use Exception;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;

class GeminiService
{
    private array $fallbackModels = [
        'gemini-2.0-flash',
        'gemini-1.5-flash',
        'gemini-1.5-pro',
    ];

    /**
     * Ambil API key aktif khusus provider 'gemini' (atau fallback ke .env GEMINI_API_KEY)
     */
    private function getWorkingApiKey(array $excludedIds = []): ?object
    {
        try {
            $query = ApiKey::where('provider', 'gemini')->where('is_active', true);

            if (!empty($excludedIds)) {
                $query->whereNotIn('id', $excludedIds);
            }

            $key = $query->orderBy('usage_count', 'asc')->first();

            if (!$key) {
                $totalActive = ApiKey::where('provider', 'gemini')->where('is_active', true)->count();

                if ($totalActive > 0 && count($excludedIds) >= $totalActive) {
                    return ApiKey::where('provider', 'gemini')->where('is_active', true)->inRandomOrder()->first();
                }

                if ($totalActive === 0 && ApiKey::where('provider', 'gemini')->count() > 0) {
                    ApiKey::where('provider', 'gemini')->update(['is_active' => true]);
                    return ApiKey::where('provider', 'gemini')->where('is_active', true)->orderBy('usage_count', 'asc')->first();
                }
            }

            if ($key) {
                return $key;
            }
        } catch (Exception $e) {
            Log::warning('DB ApiKey lookup skipped: ' . $e->getMessage());
        }

        // Fallback ke .env GEMINI_API_KEY untuk tahap build / gratisan
        $envKey = config('services.gemini.key') ?: env('GEMINI_API_KEY');
        if (!empty($envKey)) {
            return (object) [
                'id' => 0,
                'key' => $envKey,
                'is_env' => true,
            ];
        }

        return null;
    }

    /**
     * Generate text dari Gemini (AUTO ROTATE KEY + AUTO FALLBACK MODEL)
     */
    public function generateText(string $prompt): string
    {
        $totalKeys = 0;
        try {
            $totalKeys = ApiKey::where('provider', 'gemini')->where('is_active', true)->count();
        } catch (Exception $e) {}

        $maxRetries = $totalKeys > 0 ? $totalKeys + 2 : 3;
        $excludedIds = [];
        $lastError = '';
        $modelIndex = 0;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            $apiKeyModel = null;

            try {
                $apiKeyModel = $this->getWorkingApiKey($excludedIds);

                if (!$apiKeyModel) {
                    throw new Exception('API Key Gemini belum diatur di menu API Key maupun di .env (GEMINI_API_KEY).');
                }

                $client = new Client([
                    'timeout' => 120,
                    'verify'  => false,
                ]);

                $currentModel = $this->fallbackModels[$modelIndex] ?? 'gemini-2.0-flash';
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$currentModel}:generateContent";

                if ($attempt > 1) {
                    usleep(400000);
                }

                $response = $client->post($url, [
                    'query' => ['key' => $apiKeyModel->key],
                    'json' => [
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => $prompt]
                                ]
                            ]
                        ],
                        'generationConfig' => [
                            'temperature' => 0.65,
                            'maxOutputTokens' => 8192,
                        ]
                    ]
                ]);

                $data = json_decode($response->getBody()->getContents(), true);
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

                if (trim($text) === '') {
                    throw new Exception('Konten kosong dari Gemini.');
                }

                if (empty($apiKeyModel->is_env) && method_exists($apiKeyModel, 'increment')) {
                    $apiKeyModel->increment('usage_count');
                }

                return $text;

            } catch (RequestException $e) {
                $status = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;

                if ($status === 404 && $modelIndex < count($this->fallbackModels) - 1) {
                    $modelIndex++;
                    $attempt--;
                    continue;
                }

                if ($apiKeyModel && empty($apiKeyModel->is_env)) {
                    $excludedIds[] = $apiKeyModel->id;
                }

                if ($status === 429) {
                    $lastError = "HTTP 429: Kuota rate limit tercapai.";
                    continue;
                }

                if (($status === 400 || $status === 403) && $apiKeyModel && empty($apiKeyModel->is_env) && method_exists($apiKeyModel, 'update')) {
                    $apiKeyModel->update(['is_active' => false]);
                    $lastError = "HTTP $status: API Key tidak valid.";
                    continue;
                }

                $lastError = "HTTP $status: " . ($e->hasResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage());

            } catch (ConnectException $e) {
                if ($apiKeyModel && empty($apiKeyModel->is_env)) {
                    $excludedIds[] = $apiKeyModel->id;
                }
                $lastError = 'Koneksi ke server Gemini terputus.';
                sleep(1);

            } catch (Exception $e) {
                if ($apiKeyModel && empty($apiKeyModel->is_env)) {
                    $excludedIds[] = $apiKeyModel->id;
                }
                $lastError = $e->getMessage();
            }
        }

        throw new Exception('Gemini gagal merespons: ' . $lastError);
    }

    /**
     * Generate dari Gambar + Teks (Multimodal Vision)
     */
    public function generateVision(string $prompt, string $imageBase64): string
    {
        $excludedIds = [];
        $modelIndex = 0;

        for ($attempt = 1; $attempt <= 4; $attempt++) {
            $apiKeyModel = null;

            try {
                $apiKeyModel = $this->getWorkingApiKey($excludedIds);

                if (!$apiKeyModel) {
                    throw new Exception('API Key Gemini tidak tersedia untuk memproses gambar.');
                }

                $client = new Client(['timeout' => 120, 'verify' => false]);
                $currentModel = $this->fallbackModels[$modelIndex] ?? 'gemini-2.0-flash';
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$currentModel}:generateContent";

                $cleanBase64 = preg_replace('#^data:image/\w+;base64,#i', '', $imageBase64);

                $response = $client->post($url, [
                    'query' => ['key' => $apiKeyModel->key],
                    'json' => [
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => $prompt],
                                    [
                                        'inline_data' => [
                                            'mime_type' => 'image/jpeg',
                                            'data'      => $cleanBase64
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]);

                $data = json_decode($response->getBody()->getContents(), true);
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

                if (trim($text) === '') {
                    throw new Exception('Respon kosong dari Gemini Vision.');
                }

                if (empty($apiKeyModel->is_env) && method_exists($apiKeyModel, 'increment')) {
                    $apiKeyModel->increment('usage_count');
                }

                return $text;

            } catch (RequestException $e) {
                $status = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
                if ($status === 404 && $modelIndex < count($this->fallbackModels) - 1) {
                    $modelIndex++;
                    $attempt--;
                    continue;
                }
                if ($apiKeyModel && empty($apiKeyModel->is_env)) {
                    $excludedIds[] = $apiKeyModel->id;
                }
                continue;
            } catch (Exception $e) {
                if ($apiKeyModel && empty($apiKeyModel->is_env)) {
                    $excludedIds[] = $apiKeyModel->id;
                }
                continue;
            }
        }

        throw new Exception('Gagal memproses gambar setelah mencoba seluruh API Key Gemini.');
    }
}
