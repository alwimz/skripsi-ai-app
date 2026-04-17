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
    /**
     * Ambil API key aktif (paling jarang dipakai)
     * Ditambahkan filter $excludedIds agar tidak mengambil key yang sedang limit
     */
    private function getWorkingApiKey(array $excludedIds = []): ?ApiKey
    {
        // Query dasar
        $query = ApiKey::where('is_active', true);

        // FITUR BARU: Jangan ambil key yang ada di daftar exclude
        if (!empty($excludedIds)) {
            $query->whereNotIn('id', $excludedIds);
        }

        $key = $query->orderBy('usage_count', 'asc')->first();

        // Jika tidak ada key (mungkin semua masuk exclude atau mati semua)
        if (!$key) {
            // Cek apakah database kosong total dari key aktif?
            $totalActive = ApiKey::where('is_active', true)->count();

            // Jika excludedIds penuh (semua key sudah dicoba), reset exclude list untuk coba lagi
            if ($totalActive > 0 && count($excludedIds) >= $totalActive) {
                return ApiKey::where('is_active', true)->inRandomOrder()->first();
            }

            // Fail-safe: aktifkan ulang semua key jika semua mati
            if ($totalActive === 0) {
                ApiKey::query()->update(['is_active' => true]);
                return ApiKey::where('is_active', true)->orderBy('usage_count', 'asc')->first();
            }
            
            return null;
        }

        return $key;
    }

    /**
     * Generate text dari Gemini (AUTO ROTATE + RETRY)
     */
    public function generateText(string $prompt): string
    {
        // Hitung jumlah key agar retry menyesuaikan jumlah key
        $totalKeys = ApiKey::where('is_active', true)->count();
        $maxRetries = $totalKeys > 0 ? $totalKeys + 2 : 5;

        $excludedIds = []; // Daftar Key yang error di request ini
        $lastError = ''; // Menyimpan pesan error asli dari API

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            $apiKeyModel = null;

            try {
                // Minta key, tapi hindari yang sudah gagal (exclude)
                $apiKeyModel = $this->getWorkingApiKey($excludedIds);

                if (!$apiKeyModel) {
                    throw new Exception('Semua API Key telah dicoba dan limit/habis.');
                }

                $client = new Client([
                    'timeout' => 120,
                    'verify'  => false,
                ]);

                // Otomatis fallback jika 404
                $currentModel = $currentModel ?? 'gemini-1.5-flash'; 
                $url = "https://generativelanguage.googleapis.com/v1/models/{$currentModel}:generateContent";

                // Jeda sedikit agar tidak spamming server
                if ($attempt > 1) sleep(1);

                $response = $client->post($url, [
                    'query' => ['key' => $apiKeyModel->key],
                    'json' => [
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => $prompt]
                                ]
                            ]
                        ]
                    ]
                ]);

                $data = json_decode($response->getBody()->getContents(), true);
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

                if (trim($text) === '') {
                    throw new Exception('Konten kosong dari Gemini.');
                }

                // Sukses -> Update penggunaan
                $apiKeyModel->increment('usage_count');

                return $text;

            } catch (RequestException $e) {
                $status = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;

                // Catat ID key ini agar tidak dipilih lagi di putaran berikutnya
                if ($apiKeyModel) {
                    $excludedIds[] = $apiKeyModel->id;
                }

                // ===== QUOTA / RATE LIMIT (429) =====
                if ($status === 429) {
                    Log::warning("Gemini 429 (Limit) pada Key ID {$apiKeyModel?->id}. Ganti ke key lain...");
                    $lastError = "HTTP 429: Rate limit dicapai. " . ($e->hasResponse() ? $e->getResponse()->getBody()->getContents() : '');
                    // PERBAIKAN UTAMA: Hapus sleep(60).
                    // Langsung continue agar fungsi getWorkingApiKey mengambil key BERIKUTNYA.
                    continue; 
                }

                // ===== MODEL / REQUEST ERROR (FATAL) =====
                if ($status === 404) {
                    Log::error("Model $currentModel tidak ditemukan. Mencoba fallback model...");
                    
                    if ($currentModel === 'gemini-1.5-flash') {
                        $currentModel = 'gemini-pro';
                        $attempt--; // Jangan hitung sebagai kegagalan limit key
                        continue;
                    }
                    
                    if ($currentModel === 'gemini-pro') {
                        $currentModel = 'gemini-1.5-pro';
                        $attempt--;
                        continue;
                    }

                    $rawBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : '';
                    Log::error("Semua model fallback gagal/404 pada Key ID {$apiKeyModel?->id}. Raw: $rawBody");
                    $lastError = "HTTP 404: $rawBody";
                    continue; 
                }

                if ($status === 400 || $status === 403) {
                    // Key rusak, matikan
                    if ($apiKeyModel) {
                        $apiKeyModel->update(['is_active' => false]);
                    }
                    $lastError = "HTTP $status: " . ($e->hasResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage());
                    continue;
                }

                $lastError = "RequestException HTTP $status: " . $e->getMessage();
                Log::error('Gemini RequestException', [
                    'status' => $status,
                    'attempt' => $attempt,
                    'message' => $e->getMessage()
                ]);

            } catch (ConnectException $e) {
                // Koneksi gagal, hindari key ini sementara
                if ($apiKeyModel) $excludedIds[] = $apiKeyModel->id;
                Log::warning('Koneksi ke Gemini gagal. Retry...', ['attempt' => $attempt]);
                sleep(2);

            } catch (Exception $e) {
                // Error umum
                if ($apiKeyModel) $excludedIds[] = $apiKeyModel->id;
                Log::error('Gemini General Error', [
                    'attempt' => $attempt,
                    'message' => $e->getMessage()
                ]);
            }
        }

        throw new Exception(
            'GAGAL: Server AI menolak permintaan. Error Terakhir: ' . $lastError
        );
    }

    /**
     * FITUR BARU: Generate dari Gambar + Teks (Multimodal)
     */
    public function generateVision(string $prompt, string $imageBase64): string
    {
        // 1. Logika Rotasi Key (Sama dengan generateText)
        $totalKeys = ApiKey::where('is_active', true)->count();
        $maxRetries = $totalKeys > 0 ? $totalKeys + 2 : 5;
        $excludedIds = [];

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            $apiKeyModel = null;

            try {
                $apiKeyModel = $this->getWorkingApiKey($excludedIds);

                if (!$apiKeyModel) {
                    throw new Exception('Semua API Key limit/habis.');
                }

                $client = new Client(['timeout' => 120, 'verify' => false]);

                // === MODEL VISION (Dengan Fallback) ===
                $currentModelVision = $currentModelVision ?? 'gemini-1.5-flash'; 
                $url = "https://generativelanguage.googleapis.com/v1/models/{$currentModelVision}:generateContent";

                // Bersihkan header base64 jika ada
                $cleanBase64 = preg_replace('#^data:image/\w+;base64,#i', '', $imageBase64);

                // Payload Khusus Gambar
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

                if (trim($text) === '') throw new Exception('Respon kosong.');

                $apiKeyModel->increment('usage_count');
                return $text;

            } catch (RequestException $e) {
                $status = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
                if ($apiKeyModel) $excludedIds[] = $apiKeyModel->id;

                if ($status === 429) { 
                    // sleep(4); // Opsional: Jeda jika limit
                    continue; 
                }
                if ($status === 404) {
                    if ($currentModelVision === 'gemini-1.5-flash') {
                        $currentModelVision = 'gemini-pro-vision';
                        $attempt--;
                        continue;
                    }
                    if ($currentModelVision === 'gemini-pro-vision') {
                        $currentModelVision = 'gemini-1.5-flash-latest';
                        $attempt--;
                        continue;
                    }
                }

                if ($status === 400 || $status === 403) {
                    if ($apiKeyModel) $apiKeyModel->update(['is_active' => false]);
                    continue;
                }
                // Error lain
                continue;

            } catch (Exception $e) {
                if ($apiKeyModel) $excludedIds[] = $apiKeyModel->id;
                Log::error('Gemini Vision Error: ' . $e->getMessage());
                continue;
            }
        }

        throw new Exception('Gagal memproses gambar setelah mencoba semua key.');
    }
}