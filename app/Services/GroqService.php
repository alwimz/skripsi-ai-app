<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class GroqService
{
    private array $models = [
        'llama-3.3-70b-versatile',
        'llama-3.1-8b-instant',
    ];

    private function getApiKeys(): array
    {
        $keys = [];
        try {
            $dbKeys = DB::table('api_keys')
                ->where('provider', 'groq')
                ->where('is_active', true)
                ->orderBy('usage_count', 'asc')
                ->pluck('key')
                ->toArray();
            $keys = array_merge($keys, $dbKeys);
        } catch (Exception $e) {
            Log::warning('Groq DB key lookup skipped: ' . $e->getMessage());
        }

        $envKey = config('services.groq.key') ?: env('GROQ_API_KEY');
        if (!empty($envKey)) {
            $keys[] = $envKey;
        }

        return array_values(array_unique(array_filter($keys)));
    }

    public function generateText(string $prompt): string
    {
        $keys = $this->getApiKeys();

        if (empty($keys)) {
            throw new Exception('API Key Gratis belum diatur. Silakan tambahkan API Key Gemini atau Groq di menu "Atur API Key" atau di file .env (GEMINI_API_KEY / GROQ_API_KEY).');
        }

        $lastError = '';

        foreach ($keys as $apiKey) {
            foreach ($this->models as $model) {
                try {
                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $apiKey,
                        'Content-Type'  => 'application/json',
                    ])
                    ->timeout(90)
                    ->post('https://api.groq.com/openai/v1/chat/completions', [
                        'model' => $model,
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => 'Anda adalah Profesor Pembimbing Akademik Senior di Universitas Indonesia yang ahli menyusun naskah skripsi S1 sesuai standar DIKTI, KBBI, dan EYD V.'
                            ],
                            [
                                'role' => 'user',
                                'content' => $prompt
                            ]
                        ],
                        'temperature' => 0.6,
                        'max_tokens' => 6000,
                    ]);

                    if ($response->successful()) {
                        $content = $response->json()['choices'][0]['message']['content'] ?? '';
                        if (trim($content) !== '') {
                            return $content;
                        }
                    }

                    $lastError = 'HTTP ' . $response->status() . ': ' . $response->body();
                } catch (Exception $e) {
                    $lastError = $e->getMessage();
                }
            }
        }

        Log::error('Groq Error', ['error' => $lastError]);
        throw new Exception('Gagal memproses AI (Groq): ' . $lastError);
    }
}
