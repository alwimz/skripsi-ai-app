<?php

namespace App\Services;

use App\Models\ApiKey;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;

class GeminiService
{
    private array $fallbackModels = [
        'gemini-2.0-flash',
        'gemini-2.5-flash',
        'gemini-1.5-flash',
        'gemini-1.5-pro',
    ];

    private function geminiKeyQuery()
    {
        $hasProvider = Schema::hasColumn('api_keys', 'provider');
        return ApiKey::where(function ($q) use ($hasProvider) {
            $q->where('key', 'like', 'AIza%')
              ->orWhere('key', 'like', 'AQ.%');
            if ($hasProvider) {
                $q->orWhere('provider', 'gemini');
            }
        });
    }

    /**
     * Ambil API key aktif khusus Gemini ('AIza...' / 'AQ....') atau fallback ke .env GEMINI_API_KEY
     */
    private function getWorkingApiKey(array $excludedIds = []): ?object
    {
        try {
            $query = $this->geminiKeyQuery()->where('is_active', true);

            if (!empty($excludedIds)) {
                $query->whereNotIn('id', $excludedIds);
            }

            $key = $query->orderBy('usage_count', 'asc')->first();

            if (!$key) {
                $totalGemini = $this->geminiKeyQuery()->count();
                if ($totalGemini > 0) {
                    $this->geminiKeyQuery()->update(['is_active' => true]);
                    $retryQuery = $this->geminiKeyQuery()->where('is_active', true);
                    if (!empty($excludedIds)) {
                        $retryQuery->whereNotIn('id', $excludedIds);
                    }
                    $key = $retryQuery->orderBy('usage_count', 'asc')->first()
                        ?: $this->geminiKeyQuery()->inRandomOrder()->first();
                }
            }

            if ($key) {
                return $key;
            }
        } catch (Exception $e) {
            Log::warning('DB ApiKey Gemini lookup skipped: ' . $e->getMessage());
        }

        $envKey = config('services.gemini.key') ?: env('GEMINI_API_KEY');
        if (!empty($envKey)) {
            return (object) [
                'id' => 0,
                'key' => trim($envKey),
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
            $totalKeys = $this->geminiKeyQuery()->count();
        } catch (Exception $e) {}

        $maxRetries = max($totalKeys + 2, 3);
        $excludedIds = [];
        $lastError = '';
        $modelIndex = 0;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            $apiKeyModel = null;

            try {
                $apiKeyModel = $this->getWorkingApiKey($excludedIds);

                if (!$apiKeyModel) {
                    throw new Exception('API Key Gemini belum tersedia.');
                }

                $client = new Client([
                    'timeout' => 120,
                    'verify'  => false,
                ]);

                $currentModel = $this->fallbackModels[$modelIndex] ?? 'gemini-2.0-flash';
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$currentModel}:generateContent";

                if ($attempt > 1) {
                    usleep(300000);
                }

                $cleanApiKey = trim($apiKeyModel->key);
                $isOAuthBearer = str_starts_with($cleanApiKey, 'ya29.');

                $headers = [
                    'Content-Type' => 'application/json',
                ];
                $postOptions = [
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
                ];

                if ($isOAuthBearer) {
                    $headers['Authorization'] = 'Bearer ' . $cleanApiKey;
                } else {
                    $headers['x-goog-api-key'] = $cleanApiKey;
                    $postOptions['query'] = ['key' => $cleanApiKey];
                }
                $postOptions['headers'] = $headers;

                try {
                    $response = $client->post($url, $postOptions);
                } catch (RequestException $postEx) {
                    // Fallback otomatis jika format AQ/token membutuhkan Bearer
                    if ($postEx->hasResponse() && $postEx->getResponse()->getStatusCode() === 401 && !$isOAuthBearer) {
                        $fallbackOptions = $postOptions;
                        unset($fallbackOptions['query']);
                        $fallbackOptions['headers']['Authorization'] = 'Bearer ' . $cleanApiKey;
                        unset($fallbackOptions['headers']['x-goog-api-key']);
                        $response = $client->post($url, $fallbackOptions);
                    } else {
                        throw $postEx;
                    }
                }

                $data = json_decode($response->getBody()->getContents(), true);
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

                if (trim($text) === '') {
                    throw new Exception('Konten kosong dari Gemini.');
                }

                if (empty($apiKeyModel->is_env) && !empty($apiKeyModel->id)) {
                    DB::table('api_keys')->where('id', $apiKeyModel->id)->increment('usage_count');
                }

                return $text;

            } catch (RequestException $e) {
                $status = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
                $body = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();

                if (($status === 404 || $status === 429 || $status === 503) && $modelIndex < count($this->fallbackModels) - 1) {
                    $modelIndex++;
                    $attempt--;
                    continue;
                }

                if ($apiKeyModel && empty($apiKeyModel->is_env)) {
                    $excludedIds[] = $apiKeyModel->id;
                }

                $lastError = "HTTP $status: " . $body;

            } catch (ConnectException $e) {
                if ($apiKeyModel && empty($apiKeyModel->is_env)) {
                    $excludedIds[] = $apiKeyModel->id;
                }
                $lastError = 'Koneksi ke server Gemini terputus.';

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
     * Generate dari Gambar + Teks (Multimodal Vision untuk Revisi Coretan Dosen & Tanya Soal)
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

                $mimeType = 'image/jpeg';
                if (preg_match('#^data:(image/\w+);base64,#i', $imageBase64, $matches)) {
                    $mimeType = $matches[1];
                }
                $cleanBase64 = preg_replace('#^data:image/\w+;base64,#i', '', $imageBase64);

                $cleanApiKey = trim($apiKeyModel->key);
                $isBearer = str_starts_with($cleanApiKey, 'AQ.') || str_starts_with($cleanApiKey, 'ya29.');

                $headers = [
                    'Content-Type' => 'application/json',
                ];
                $postOptions = [
                    'json' => [
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => $prompt],
                                    [
                                        'inline_data' => [
                                            'mime_type' => $mimeType,
                                            'data'      => $cleanBase64
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ];

                if ($isBearer) {
                    $headers['Authorization'] = 'Bearer ' . $cleanApiKey;
                } else {
                    $headers['x-goog-api-key'] = $cleanApiKey;
                    $postOptions['query'] = ['key' => $cleanApiKey];
                }
                $postOptions['headers'] = $headers;

                $response = $client->post($url, $postOptions);

                $data = json_decode($response->getBody()->getContents(), true);
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

                if (trim($text) === '') {
                    throw new Exception('Respon kosong dari Gemini Vision.');
                }

                if (empty($apiKeyModel->is_env) && !empty($apiKeyModel->id)) {
                    DB::table('api_keys')->where('id', $apiKeyModel->id)->increment('usage_count');
                }

                return $text;

            } catch (RequestException $e) {
                $status = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
                if (($status === 404 || $status === 429) && $modelIndex < count($this->fallbackModels) - 1) {
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
