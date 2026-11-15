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

        foreach ($imageBase64 ? ['openai'] : ['deepseek', 'openai'] as $freeModel) {
            foreach (['https://text.pollinations.ai/openai', 'https://text.pollinations.ai/'] as $pollUrl) {
                try {
                    $response = Http::withHeaders([
                        'Content-Type' => 'application/json',
                    ])
                    ->timeout(100)
                    ->post($pollUrl, [
                        'model' => $freeModel,
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
                } catch (Exception $e) {
                    Log::warning("Free AI ($freeModel @ $pollUrl) fallback error: " . $e->getMessage());
                }
            }
        }

        throw new Exception('Seluruh layanan AI (Gemini, DeepSeek, dan Free Server) sedang sibuk. Silakan tambahkan API Key Gemini/DeepSeek di menu Pengaturan API.');
    }

    /**
     * Generate text dengan pilihan model ('auto', 'deepseek', 'groq', atau 'gemini') + multi-layer fallback
     */
    public function generateText(string $prompt, string $model = 'auto'): string
    {
        if ($model === 'deepseek' || $model === 'groq') {
            try {
                return $this->groq->generateText($prompt);
            } catch (Exception $dsEx) {
                Log::warning('DeepSeek gagal, mencoba Gemini & Backup AI: ' . $dsEx->getMessage());
                try {
                    return $this->gemini->generateText($prompt);
                } catch (Exception $geminiEx) {
                    return $this->generateFreeFallback($prompt);
                }
            }
        }

        // Default ('auto' atau 'gemini'): Prioritas Gemini -> Fallback DeepSeek -> Fallback Free AI
        try {
            return $this->gemini->generateText($prompt);
        } catch (Exception $geminiEx) {
            Log::warning('Gemini gagal, mencoba fallback ke DeepSeek: ' . $geminiEx->getMessage());
            try {
                return $this->groq->generateText($prompt);
            } catch (Exception $dsEx) {
                Log::warning('DeepSeek juga gagal, menggunakan Free AI Fallback: ' . $dsEx->getMessage());
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
                try {
                    return $this->generateFreeFallback($prompt, $image);
                } catch (Exception $freeEx) {
                    // Jika kedua layanan vision gagal, JANGAN biarkan error unhandled memunculkan alert 'Seluruh layanan AI sibuk'!
                    // Lakukan fallback ke text model (DeepSeek/Groq/Gemini) agar pengguna tetap mendapatkan jawaban konsultasi akademik!
                    Log::warning('Vision fallback gagal, menggunakan text model fallback: ' . $freeEx->getMessage());
                    $fallbackPrompt = "MAHASISWA MELAMPIRKAN GAMBAR DENGAN PERTANYAAN: \"{$prompt}\"\n\nInstruksi: Berikan jawaban bimbingan akademik yang ramah dan solutif. Sampaikan bahwa gambar/dokumen telah diterima. Jika memerlukan analisis visual mendalam terhadap logo/bagan/tabel, sarankan mahasiswa juga mengetikkan nama institusi atau ringkasan isi gambar, lalu bantu jelaskan relevansinya dengan penelitian skripsi.";
                    return $this->generateText($fallbackPrompt, $model);
                }
            }
        }

        return $this->generateText($prompt, $model);
    }
}
