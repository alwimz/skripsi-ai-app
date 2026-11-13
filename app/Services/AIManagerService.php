<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIManagerService
{
    protected GeminiService $gemini;
    protected GroqService $groq;

    public function __construct(
        GeminiService $gemini,
        GroqService $groq
    ) {
        $this->gemini = $gemini;
        $this->groq   = $groq;
    }

    /**
     * Fallback AI Gratis tanpa API Key (OpenAI-compatible endpoint)
     * Menjamin aplikasi tetap berjalan 100% meskipun API Key Gemini/Groq kadaluarsa atau terkena limit.
     */
    protected function generateFreeFallback(string $prompt, ?string $imageBase64 = null): string
    {
        $messages = [
            [
                'role' => 'system',
                'content' => 'Anda adalah Profesor Pembimbing Akademik Senior di Perguruan Tinggi Indonesia yang ahli menyusun dan merevisi naskah skripsi S1 sesuai standar DIKTI, KBBI, EYD V, dan sitasi APA 7th Edition.'
            ]
        ];

        if ($imageBase64) {
            $imageUrl = str_starts_with($imageBase64, 'data:image')
                ? $imageBase64
                : 'data:image/jpeg;base64,' . $imageBase64;

            $messages[] = [
                'role' => 'user',
                'content' => [
                    ['type' => 'text', 'text' => $prompt],
                    ['type' => 'image_url', 'image_url' => ['url' => $imageUrl]]
                ]
            ];
        } else {
            $messages[] = [
                'role' => 'user',
                'content' => $prompt
            ];
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])
        ->timeout(120)
        ->post('https://text.pollinations.ai/openai', [
            'model' => 'openai',
            'messages' => $messages,
            'temperature' => 0.65,
        ]);

        if ($response->successful()) {
            $json = $response->json();
            $content = $json['choices'][0]['message']['content'] ?? '';
            if (trim($content) !== '') {
                return $content;
            }
            if (is_string($response->body()) && strlen(trim($response->body())) > 20 && !str_starts_with(trim($response->body()), '{')) {
                return trim($response->body());
            }
        }

        throw new Exception('Seluruh layanan AI (Gemini, Groq, dan Backup Server) sedang sibuk. Silakan tambahkan API Key Gemini/Groq baru di menu API Key.');
    }

    /**
     * Generate text dengan pilihan model ('auto', 'gemini', atau 'groq') + multi-layer fallback
     */
    public function generateText(string $prompt, string $model = 'auto'): string
    {
        if ($model === 'groq') {
            try {
                return $this->groq->generateText($prompt);
            } catch (Exception $groqEx) {
                Log::warning('Groq gagal, mencoba Gemini & Backup AI: ' . $groqEx->getMessage());
                try {
                    return $this->gemini->generateText($prompt);
                } catch (Exception $geminiEx) {
                    return $this->generateFreeFallback($prompt);
                }
            }
        }

        // Default ('auto' atau 'gemini'): Prioritas Gemini -> Fallback Groq -> Fallback Free AI
        try {
            return $this->gemini->generateText($prompt);
        } catch (Exception $geminiEx) {
            Log::warning('Gemini gagal, mencoba fallback ke Groq: ' . $geminiEx->getMessage());
            try {
                return $this->groq->generateText($prompt);
            } catch (Exception $groqEx) {
                Log::warning('Groq juga gagal, menggunakan Free AI Fallback: ' . $groqEx->getMessage());
                return $this->generateFreeFallback($prompt);
            }
        }
    }

    /**
     * Generate dengan dukungan Gambar (Revisi coretan dosen / soal bergambar)
     */
    public function generateWithImage(string $prompt, ?string $image = null, string $model = 'auto'): string
    {
        if ($image) {
            try {
                return $this->gemini->generateVision($prompt, $image);
            } catch (Exception $e) {
                Log::warning('Gemini Vision gagal, menggunakan Vision Fallback: ' . $e->getMessage());
                return $this->generateFreeFallback($prompt, $image);
            }
        }

        return $this->generateText($prompt, $model);
    }
}
