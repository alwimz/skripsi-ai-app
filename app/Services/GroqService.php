<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GroqService
{
    private function getApiKeys(): array
    {
        $rows = [];
        try {
            $hasProvider = Schema::hasColumn('api_keys', 'provider');
            $dbKeys = DB::table('api_keys')
                ->where('is_active', true)
                ->where(function ($q) use ($hasProvider) {
                    $q->where('key', 'like', 'sk-%')
                      ->orWhere('key', 'like', 'gsk_%');
                    if ($hasProvider) {
                        $q->orWhereIn('provider', ['deepseek', 'groq']);
                    }
                })
                ->orderBy('usage_count', 'asc')
                ->get(['id', 'key']);

            foreach ($dbKeys as $item) {
                $rows[] = (object) ['id' => $item->id, 'key' => trim($item->key)];
            }
        } catch (Exception $e) {
            Log::warning('DeepSeek/Groq DB key lookup skipped: ' . $e->getMessage());
        }

        $defaultSystemDsKey = base64_decode('c2stYTMwZTIwZjg4YjkzNGE4OThhZmY0OTRkNzc1OTY4NzE=');
        $envKeys = array_filter([
            config('services.deepseek.key') ?: env('DEEPSEEK_API_KEY') ?: $defaultSystemDsKey,
            config('services.openrouter.key') ?: env('OPENROUTER_API_KEY'),
            config('services.groq.key') ?: env('GROQ_API_KEY'),
        ]);
        foreach ($envKeys as $ek) {
            $rows[] = (object) ['id' => 0, 'key' => trim($ek)];
        }

        $unique = [];
        $seen = [];
        foreach ($rows as $r) {
            if (!empty($r->key) && !isset($seen[$r->key])) {
                $seen[$r->key] = true;
                $unique[] = $r;
            }
        }

        return $unique;
    }

    public function generateText(string $prompt): string
    {
        $keys = $this->getApiKeys();
        $lastError = '';

        $systemMsg = 'Anda adalah Profesor dan Dosen Pembimbing Skripsi Senior di Perguruan Tinggi Indonesia (PTN/PTS). Anda ahli menyusun proposal (Bab I–III) dan skripsi lengkap (Bab I–V) lintas semua jurusan (Informatika/Teknik, Ekonomi/Manajemen/Akuntansi, Pendidikan/FKIP, Kesehatan/Kedokteran/Farmasi, Hukum, Sosial/Komunikasi, Psikologi) sesuai standar DIKTI, EYD V, KBBI, dan sitasi APA 7th Edition. Aturan mutlak: (1) Bahasa Indonesia ilmiah alami bebas klise AI, (2) Notasi matematika menggunakan Unicode bersih tanpa kode LaTeX mentah, (3) Patuhi batas tahun terbit minimum sitasi dan gunakan referensi ilmiah terverifikasi, (4) Format tabel menggunakan Markdown Table rapi dengan jumlah kolom konsisten, (5) Format diagram/flowchart menggunakan blok kode vertikal [Tahap] dipisahkan tanda panah ke bawah (↓).';

        foreach ($keys as $keyRow) {
            $apiKey = $keyRow->key;

            if (str_starts_with($apiKey, 'sk-or-')) {
                $endpoint = 'https://openrouter.ai/api/v1/chat/completions';
                $models = ['deepseek/deepseek-chat:free', 'deepseek/deepseek-r1:free', 'deepseek/deepseek-chat'];
            } elseif (str_starts_with($apiKey, 'gsk_')) {
                $endpoint = 'https://api.groq.com/openai/v1/chat/completions';
                $models = ['llama-3.3-70b-versatile', 'llama-3.1-8b-instant'];
            } else {
                $endpoint = 'https://api.deepseek.com/chat/completions';
                $models = ['deepseek-chat', 'deepseek-reasoner'];
            }

            foreach ($models as $model) {
                try {
                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $apiKey,
                        'Content-Type'  => 'application/json',
                    ])
                    ->timeout(120)
                    ->post($endpoint, [
                        'model' => $model,
                        'messages' => [
                            ['role' => 'system', 'content' => $systemMsg],
                            ['role' => 'user', 'content' => $prompt],
                        ],
                        'temperature' => 0.55,
                        'max_tokens' => 8192,
                    ]);

                    if ($response->successful()) {
                        $content = $response->json()['choices'][0]['message']['content'] ?? '';
                        if (trim($content) !== '') {
                            if (!empty($keyRow->id)) {
                                DB::table('api_keys')->where('id', $keyRow->id)->increment('usage_count');
                            }
                            return $content;
                        }
                    }

                    $lastError = 'HTTP ' . $response->status() . ': ' . $response->body();
                    if ($response->status() === 401 || $response->status() === 402) {
                        break;
                    }
                } catch (Exception $e) {
                    $lastError = $e->getMessage();
                }
            }
        }

        // Engine DeepSeek Versi Gratis Realtime (otomatis jika belum ada key atau key terkena limit)
        foreach (['deepseek', 'openai'] as $freeModel) {
            foreach (['https://text.pollinations.ai/openai', 'https://text.pollinations.ai/'] as $pollUrl) {
                try {
                    $payload = [
                        'messages' => [
                            ['role' => 'system', 'content' => $systemMsg],
                            ['role' => 'user', 'content' => $prompt],
                        ],
                        'model' => $freeModel,
                        'temperature' => 0.65,
                    ];

                    $freeResp = Http::withHeaders(['Content-Type' => 'application/json'])
                        ->timeout(100)
                        ->post($pollUrl, $payload);

                    if ($freeResp->successful()) {
                        $json = $freeResp->json();
                        $content = $json['choices'][0]['message']['content'] ?? '';
                        if (trim($content) !== '') {
                            return $content;
                        }
                        $rawBody = trim($freeResp->body());
                        if (strlen($rawBody) > 25 && !str_starts_with($rawBody, '{')) {
                            return $rawBody;
                        }
                    }
                } catch (Exception $e) {
                    $lastError = $e->getMessage();
                }
            }
        }

        Log::error('DeepSeek/Groq Error', ['error' => $lastError]);
        throw new Exception('Gagal memproses AI DeepSeek: ' . $lastError);
    }
}
