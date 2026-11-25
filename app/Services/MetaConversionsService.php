<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class MetaConversionsService
{
    public function sendEvent(
        string $eventName,
        ?User $user = null,
        array $customData = [],
        ?string $eventId = null,
        ?Request $request = null,
        ?string $eventSourceUrl = null
    ): bool {
        $pixelId = preg_replace('/\D+/', '', (string) config('services.meta.pixel_id', ''));
        $token = trim((string) config('services.meta.capi_token', ''));

        if ($pixelId === '' || $token === '' || $eventName === '') {
            return false;
        }

        $req = $request ?: (app()->bound('request') ? request() : null);

        $userData = [];
        if ($user) {
            $email = strtolower(trim((string) ($user->email ?? '')));
            if ($email !== '') {
                $userData['em'] = [hash('sha256', $email)];
            }

            $name = strtolower(trim((string) ($user->name ?? '')));
            if ($name !== '') {
                $parts = preg_split('/\s+/', $name, -1, PREG_SPLIT_NO_EMPTY);
                if (!empty($parts[0])) {
                    $userData['fn'] = [hash('sha256', $parts[0])];
                }
                if (count($parts) > 1) {
                    $userData['ln'] = [hash('sha256', $parts[count($parts) - 1])];
                }
            }

            $userData['external_id'] = [hash('sha256', (string) $user->id)];
        }

        if ($req) {
            $ip = trim((string) $req->ip());
            if ($ip !== '') {
                $userData['client_ip_address'] = $ip;
            }
            $ua = trim((string) $req->userAgent());
            if ($ua !== '') {
                $userData['client_user_agent'] = $ua;
            }
            $fbp = trim((string) $req->cookie('_fbp', ''));
            if ($fbp !== '') {
                $userData['fbp'] = $fbp;
            }
            $fbc = trim((string) $req->cookie('_fbc', ''));
            if ($fbc !== '') {
                $userData['fbc'] = $fbc;
            }
        }

        $eventItem = [
            'event_name' => $eventName,
            'event_time' => time(),
            'action_source' => 'website',
            'event_source_url' => $eventSourceUrl ?: ($req ? $req->fullUrl() : url('/')),
            'user_data' => $userData,
        ];

        if (!empty($customData)) {
            $eventItem['custom_data'] = $customData;
        }

        if ($eventId !== null && $eventId !== '') {
            $eventItem['event_id'] = $eventId;
        }

        try {
            $res = Http::timeout(4)->post("https://graph.facebook.com/v21.0/{$pixelId}/events", [
                'data' => [$eventItem],
                'access_token' => $token,
            ]);

            if (!$res->successful()) {
                Log::warning('Meta CAPI event error', [
                    'event' => $eventName,
                    'status' => $res->status(),
                    'body' => $res->body(),
                ]);
                return false;
            }

            return true;
        } catch (Throwable $e) {
            Log::warning('Meta CAPI exception: ' . $e->getMessage(), ['event' => $eventName]);
            return false;
        }
    }
}
