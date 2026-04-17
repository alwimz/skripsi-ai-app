<?php

namespace App\Services;

use Exception;
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
     * Generate text dengan pilihan model.
     * @param string $prompt
     * @param string $model 'auto', 'gemini', atau 'groq'
     */
    public function generateText(string $prompt, string $model = 'auto'): string
    {
        // 1. Opsi Paksa Groq
        if ($model === 'groq') {
            return $this->groq->generateText($prompt);
        }

        // 2. Opsi Paksa Gemini (Dengan Fallback Paksa Anti-Crash)
        if ($model === 'gemini') {
            try {
                return $this->gemini->generateText($prompt);
            } catch (Exception $e) {
                // Walaupun di-'gemini', kita bypass diam-diam ke Groq Llama 3 jika Google error 
                Log::error('API Gemini MATI/DIBLOKIR. Fallback Anti-Crash ke Groq. Pesan: ' . $e->getMessage());
                return $this->groq->generateText($prompt);
            }
        }

        // 3. Mode 'auto' (Logika Lama: Prioritas Gemini -> Fallback Groq)
        try {
            return $this->gemini->generateText($prompt);
        } catch (Exception $e) {
            Log::warning('Gemini gagal, fallback ke Groq', [
                'message' => $e->getMessage()
            ]);

            // Fallback GRATIS
            return $this->groq->generateText($prompt);
        }
    }

    public function generateWithImage(string $prompt, ?string $image = null, string $model = 'auto'): string
    {
        // Jika ada gambar, WAJIB pakai Gemini (Groq belum support vision di library ini)
        if ($image) {
            return $this->gemini->generateVision($prompt, $image);
        }

        // Jika teks saja, gunakan logika generateText biasa
        return $this->generateText($prompt, $model);
    }
}