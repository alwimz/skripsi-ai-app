<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\BillingService;
use App\Services\MetaConversionsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class PaymentController extends Controller
{
    public function __construct(
        private BillingService $billing,
        private MetaConversionsService $metaCapi
    ) {
    }

    public function plans(Request $request)
    {
        $this->billing->ensureSchema();
        $user = Auth::user();
        $summary = $user ? $this->billing->getUserSummary($user) : null;
        $plans = $this->billing->getPlans(true);

        return view('billing.plans', compact('plans', 'summary', 'user'));
    }

    public function createTransaction(Request $request)
    {
        $this->billing->ensureSchema();
        $request->validate([
            'plan_code' => 'required|string|max:32',
            'payment_method' => 'nullable|string|in:qris,bni_va,bri_va,cimb_niaga_va,permata_va,maybank_va',
        ]);

        $planCode = trim((string) $request->input('plan_code'));
        $plan = $this->billing->getPlan($planCode);
        if (!$plan || $planCode === 'free' || (int) ($plan['price'] ?? 0) <= 0) {
            return response()->json(['status' => 'error', 'message' => 'Paket berbayar tidak ditemukan.'], 422);
        }

        $user = Auth::user();
        $method = (string) $request->input('payment_method', 'qris');
        $amount = (int) $plan['price'];
        $orderId = 'SL-' . date('ymdHi') . '-' . strtoupper(Str::random(5));

        $gatewayPref = strtolower(trim((string) config('services.payment_gateway', 'auto')));
        $mayarApiKey = trim((string) config('services.mayar.api_key'));
        $pakasirProject = trim((string) config('services.pakasir.project'));
        $pakasirApiKey = trim((string) config('services.pakasir.api_key'));

        $useMayar = $mayarApiKey !== '' && in_array($gatewayPref, ['auto', 'mayar'], true);
        $usePakasir = !$useMayar && $pakasirProject !== '' && $pakasirApiKey !== '' && in_array($gatewayPref, ['auto', 'pakasir'], true);

        if ($useMayar) {
            return $this->createMayarTransaction($request, $user, $plan, $orderId, $amount, $mayarApiKey);
        }

        if (!$usePakasir) {
            $this->metaCapi->sendEvent('InitiateCheckout', $user, [
                'content_name' => $plan['name'],
                'content_ids' => [$plan['code']],
                'content_type' => 'product',
                'value' => $amount,
                'currency' => 'IDR',
                'num_items' => 1,
            ], 'ic_' . $orderId, $request, route('billing.plans'));

            $waMsg = urlencode("Halo Admin SkripsiLab AI, saya ingin konfirmasi pembayaran {$plan['name']} (Rp " . number_format($amount, 0, ',', '.') . ") untuk akun {$user->email} — Kode Invoice: {$orderId}.");
            return response()->json([
                'status' => 'pakasir_unconfigured',
                'gateway' => 'whatsapp',
                'message' => 'Silakan selesaikan pembayaran sesuai invoice berikut lalu kirim bukti ke WhatsApp Admin.',
                'order_id' => $orderId,
                'plan' => $plan,
                'whatsapp_url' => "https://wa.me/6282195036971?text={$waMsg}",
                'invoice' => [
                    'order_id' => $orderId,
                    'account_email' => (string) $user->email,
                    'plan_name' => (string) $plan['name'],
                    'amount' => $amount,
                    'qris_image_url' => asset('images/qris-payment.jpg'),
                    'methods' => [
                        [
                            'id' => 'qris',
                            'label' => 'QRIS',
                            'title' => 'QRIS (Semua E-Wallet & M-Banking)',
                            'holder' => 'MUH ALWI SYAHRIR - TELECOMMUNICATION',
                            'nmid' => 'ID1026628042909',
                        ],
                        [
                            'id' => 'dana',
                            'label' => 'DANA',
                            'title' => 'DANA',
                            'account' => '082195036971',
                            'holder' => 'Muh Alwi Syahrir',
                        ],
                        [
                            'id' => 'seabank',
                            'label' => 'SeaBank',
                            'title' => 'Transfer Bank SeaBank',
                            'account' => '901889425034',
                            'holder' => 'Muh Alwi Syahrir',
                        ],
                    ],
                ],
            ], 200);
        }

        $baseUrl = rtrim((string) config('services.pakasir.base_url', 'https://app.pakasir.com'), '/');

        try {
            $res = Http::timeout(15)->post("{$baseUrl}/api/transactioncreate/{$method}", [
                'project' => $pakasirProject,
                'order_id' => $orderId,
                'amount' => $amount,
                'api_key' => $pakasirApiKey,
            ]);

            $json = $res->json() ?: [];
            if (!$res->successful()) {
                $errMsg = $json['message'] ?? $json['error'] ?? ('HTTP ' . $res->status());
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal membuat tagihan Pakasir: ' . $errMsg,
                ], 422);
            }

            $paymentObj = is_array($json['payment'] ?? null) ? $json['payment'] : $json;
            $paymentNumber = (string) ($paymentObj['payment_number'] ?? '');
            $totalPayment = (int) ($paymentObj['total_payment'] ?? $amount);
            $fee = (int) ($paymentObj['fee'] ?? max(0, $totalPayment - $amount));
            $expiredAtRaw = $paymentObj['expired_at'] ?? null;
            $expiredAt = $expiredAtRaw ? date('Y-m-d H:i:s', strtotime((string) $expiredAtRaw)) : now()->addMinutes(30)->format('Y-m-d H:i:s');
            $checkoutUrl = "{$baseUrl}/pay/{$pakasirProject}/{$amount}?order_id={$orderId}" . ($method === 'qris' ? '&qris_only=1' : '');

            DB::table('transactions')->insert([
                'user_id' => $user->id,
                'order_id' => $orderId,
                'plan_code' => $plan['code'],
                'plan_name' => $plan['name'],
                'amount' => $amount,
                'quota_granted' => (int) ($plan['quota_projects'] ?? 0),
                'duration_days' => (int) ($plan['duration_days'] ?? 30),
                'is_unlimited' => !empty($plan['is_unlimited']),
                'payment_method' => $method,
                'payment_number' => $paymentNumber,
                'checkout_url' => $checkoutUrl,
                'status' => 'pending',
                'expired_at' => $expiredAt,
                'raw_response' => json_encode($json, JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->metaCapi->sendEvent('InitiateCheckout', $user, [
                'content_name' => $plan['name'],
                'content_ids' => [$plan['code']],
                'content_type' => 'product',
                'value' => $amount,
                'currency' => 'IDR',
                'num_items' => 1,
            ], 'ic_' . $orderId, $request, route('billing.plans'));

            return response()->json([
                'status' => 'success',
                'transaction' => [
                    'gateway' => 'pakasir',
                    'order_id' => $orderId,
                    'plan_code' => $plan['code'],
                    'plan_name' => $plan['name'],
                    'amount' => $amount,
                    'fee' => $fee,
                    'total_payment' => $totalPayment,
                    'payment_method' => $method,
                    'payment_number' => $paymentNumber,
                    'checkout_url' => $checkoutUrl,
                    'expired_at' => $expiredAt,
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Pakasir createTransaction error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kendala menghubungi Pakasir: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function checkStatus(Request $request, string $orderId)
    {
        $this->billing->ensureSchema();
        $user = Auth::user();
        $tx = DB::table('transactions')
            ->where('order_id', $orderId)
            ->where('user_id', $user->id)
            ->first();

        if (!$tx) {
            return response()->json(['status' => 'error', 'message' => 'Transaksi tidak ditemukan.'], 404);
        }

        if ($tx->status === 'completed') {
            return response()->json([
                'status' => 'completed',
                'order_id' => $tx->order_id,
                'plan_code' => $tx->plan_code,
                'plan_name' => $tx->plan_name,
                'amount' => (int) $tx->amount,
                'message' => 'Pembayaran berhasil dikonfirmasi! Paket Anda sudah aktif.',
                'summary' => $this->billing->getUserSummary($user->fresh()),
            ]);
        }

        if ($tx->payment_method === 'mayar') {
            $mayarId = $this->extractMayarId($tx);
            $verification = $this->billing->verifyMayarTransaction($mayarId, (int) $tx->amount);
        } else {
            $verification = $this->billing->verifyPakasirTransaction($tx->order_id, (int) $tx->amount);
        }

        if (!empty($verification['verified'])) {
            $this->completePendingTransaction($tx, $user, $request);

            return response()->json([
                'status' => 'completed',
                'order_id' => $tx->order_id,
                'plan_code' => $tx->plan_code,
                'plan_name' => $tx->plan_name,
                'amount' => (int) $tx->amount,
                'message' => 'Pembayaran berhasil dikonfirmasi! Paket Anda langsung aktif.',
                'summary' => $this->billing->getUserSummary($user->fresh()),
            ]);
        }

        return response()->json([
            'status' => $tx->status,
            'remote_status' => $verification['status'] ?? 'pending',
        ]);
    }

    public function webhook(Request $request)
    {
        $this->billing->ensureSchema();
        $payload = $request->all();
        Log::info('Pakasir webhook received', $payload);

        $orderId = trim((string) ($payload['order_id'] ?? ''));
        $status = strtolower(trim((string) ($payload['status'] ?? '')));
        $amount = (int) ($payload['amount'] ?? 0);
        $projectSlug = trim((string) ($payload['project'] ?? ''));

        if ($orderId === '') {
            return response()->json(['message' => 'Missing order_id'], 400);
        }

        $configuredProject = trim((string) config('services.pakasir.project'));
        if ($configuredProject !== '' && $projectSlug !== '' && $projectSlug !== $configuredProject) {
            return response()->json(['message' => 'Project mismatch'], 403);
        }

        $tx = DB::table('transactions')->where('order_id', $orderId)->first();
        if (!$tx) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        if ($tx->status === 'completed') {
            return response()->json(['status' => 'already_completed'], 200);
        }

        if ($status !== 'completed') {
            return response()->json(['status' => 'ignored'], 200);
        }

        if ($amount > 0 && $amount !== (int) $tx->amount) {
            return response()->json(['message' => 'Amount mismatch'], 422);
        }

        // Double verification ke server Pakasir untuk mencegah webhook palsu
        $verification = $this->billing->verifyPakasirTransaction($tx->order_id, (int) $tx->amount);
        if (empty($verification['verified'])) {
            return response()->json(['message' => 'Verification failed'], 422);
        }

        $this->completePendingTransaction(
            $tx,
            User::find($tx->user_id),
            $request,
            json_encode($payload, JSON_UNESCAPED_UNICODE)
        );

        return response()->json(['status' => 'success'], 200);
    }

    public function mayarWebhook(Request $request)
    {
        $this->billing->ensureSchema();

        $configuredToken = trim((string) config('services.mayar.webhook_token'));
        if ($configuredToken !== '') {
            $providedToken = trim((string) (
                $request->query('token')
                ?: $request->header('x-callback-token')
                ?: $request->header('x-mayar-token')
                ?: $request->bearerToken()
                ?: ''
            ));
            if ($providedToken === '' || !hash_equals($configuredToken, $providedToken)) {
                return response()->json(['message' => 'Unauthorized webhook token'], 403);
            }
        }

        $payload = $request->all();
        $event = strtolower(trim((string) ($payload['event'] ?? ($payload['eventName'] ?? ''))));
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : $payload;

        Log::info('Mayar webhook received', [
            'event' => $event,
            'id' => $data['id'] ?? null,
            'transactionId' => $data['transactionId'] ?? null,
            'paymentLinkId' => $data['paymentLinkId'] ?? null,
            'status' => $data['status'] ?? null,
        ]);

        if ($event !== '' && $event !== 'payment.received') {
            return response()->json(['status' => 'ignored'], 200);
        }

        $candidateIds = array_values(array_filter(array_unique([
            trim((string) ($data['paymentLinkId'] ?? '')),
            trim((string) ($data['productId'] ?? '')),
            trim((string) ($data['id'] ?? '')),
            trim((string) ($data['transactionId'] ?? '')),
        ])));
        $orderIdHint = trim((string) ($data['extraData']['order_id'] ?? ($payload['order_id'] ?? '')));

        if (empty($candidateIds) && $orderIdHint === '') {
            return response()->json(['message' => 'Missing Mayar transaction identifier'], 400);
        }

        $tx = $this->findMayarTransaction($candidateIds, $orderIdHint);
        if (!$tx) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        if ($tx->status === 'completed') {
            return response()->json(['status' => 'already_completed'], 200);
        }

        if (!$this->billing->isMayarPaidStatus($data['status'] ?? null)) {
            return response()->json(['status' => 'ignored'], 200);
        }

        $amount = isset($data['amount']) && is_numeric($data['amount']) ? (int) $data['amount'] : 0;
        if ($amount > 0 && $amount < (int) $tx->amount) {
            return response()->json(['message' => 'Amount mismatch'], 422);
        }

        $mayarId = $this->extractMayarId($tx) ?: ($candidateIds[0] ?? '');
        $verification = $this->billing->verifyMayarTransaction($mayarId, (int) $tx->amount);
        if (empty($verification['verified'])) {
            return response()->json(['message' => 'Verification failed'], 422);
        }

        $existingRaw = json_decode((string) ($tx->raw_response ?? ''), true);
        $mergedRaw = is_array($existingRaw) ? $existingRaw : [];
        $mergedRaw['gateway'] = 'mayar';
        $mergedRaw['mayar_id'] = $mayarId;
        $mergedRaw['webhook'] = $payload;

        $this->completePendingTransaction(
            $tx,
            User::find($tx->user_id),
            $request,
            json_encode($mergedRaw, JSON_UNESCAPED_UNICODE)
        );

        return response()->json(['status' => 'success'], 200);
    }

    private function createMayarTransaction(
        Request $request,
        User $user,
        array $plan,
        string $orderId,
        int $amount,
        string $apiKey
    ) {
        $baseUrl = rtrim((string) config('services.mayar.base_url', 'https://api.mayar.id/hl/v1'), '/');

        try {
            $res = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout(15)
                ->post("{$baseUrl}/payment/create", [
                    'name' => trim((string) ($user->name ?: 'Pengguna SkripsiLab AI')),
                    'email' => trim((string) $user->email),
                    'amount' => $amount,
                    'mobile' => '081200000000',
                    'redirectUrl' => route('billing.plans', ['order_id' => $orderId]),
                    'description' => "{$plan['name']} ({$orderId})",
                    'expiredAt' => now()->addHours(24)->toIso8601String(),
                ]);

            $json = $res->json() ?: [];
            $dataObj = is_array($json['data'] ?? null) ? $json['data'] : $json;
            $mayarId = trim((string) ($dataObj['id'] ?? ''));
            $mayarTxId = trim((string) ($dataObj['transactionId'] ?? ''));
            $checkoutUrl = trim((string) ($dataObj['link'] ?? ($dataObj['payment_url'] ?? '')));

            if (!$res->successful() || $checkoutUrl === '') {
                $errMsg = $json['messages'] ?? $json['message'] ?? $json['error'] ?? ('HTTP ' . $res->status());
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal membuat tagihan Mayar: ' . (is_string($errMsg) ? $errMsg : json_encode($errMsg)),
                ], 422);
            }

            $expiredAt = now()->addHours(24)->format('Y-m-d H:i:s');

            DB::table('transactions')->insert([
                'user_id' => $user->id,
                'order_id' => $orderId,
                'plan_code' => $plan['code'],
                'plan_name' => $plan['name'],
                'amount' => $amount,
                'quota_granted' => (int) ($plan['quota_projects'] ?? 0),
                'duration_days' => (int) ($plan['duration_days'] ?? 30),
                'is_unlimited' => !empty($plan['is_unlimited']),
                'payment_method' => 'mayar',
                'payment_number' => '',
                'checkout_url' => $checkoutUrl,
                'status' => 'pending',
                'expired_at' => $expiredAt,
                'raw_response' => json_encode([
                    'gateway' => 'mayar',
                    'mayar_id' => $mayarId,
                    'mayar_transaction_id' => $mayarTxId,
                    'response' => $json,
                ], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->metaCapi->sendEvent('InitiateCheckout', $user, [
                'content_name' => $plan['name'],
                'content_ids' => [$plan['code']],
                'content_type' => 'product',
                'value' => $amount,
                'currency' => 'IDR',
                'num_items' => 1,
            ], 'ic_' . $orderId, $request, route('billing.plans'));

            return response()->json([
                'status' => 'success',
                'transaction' => [
                    'gateway' => 'mayar',
                    'order_id' => $orderId,
                    'plan_code' => $plan['code'],
                    'plan_name' => $plan['name'],
                    'amount' => $amount,
                    'fee' => 0,
                    'total_payment' => $amount,
                    'payment_method' => 'mayar',
                    'payment_number' => '',
                    'checkout_url' => $checkoutUrl,
                    'expired_at' => $expiredAt,
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mayar createTransaction error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kendala menghubungi Mayar: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function completePendingTransaction(object $tx, ?User $user, ?Request $request = null, ?string $rawResponseJson = null): bool
    {
        $update = [
            'status' => 'completed',
            'paid_at' => now(),
            'updated_at' => now(),
        ];
        if ($rawResponseJson !== null) {
            $update['raw_response'] = $rawResponseJson;
        }

        $updated = DB::table('transactions')
            ->where('id', $tx->id)
            ->where('status', 'pending')
            ->update($update);

        if ($updated > 0 && $user) {
            $freshTx = DB::table('transactions')->where('id', $tx->id)->first();
            $this->billing->activatePackageForUser($user, $tx->plan_code, $freshTx);
            $this->metaCapi->sendEvent('Purchase', $user, [
                'content_name' => $tx->plan_name,
                'content_ids' => [$tx->plan_code],
                'content_type' => 'product',
                'value' => (int) $tx->amount,
                'currency' => 'IDR',
                'num_items' => 1,
            ], 'pur_' . $tx->order_id, $request, route('billing.plans'));
        }

        return $updated > 0;
    }

    private function extractMayarId(object $tx): string
    {
        $raw = json_decode((string) ($tx->raw_response ?? ''), true);
        if (!is_array($raw)) {
            return '';
        }

        return trim((string) (
            $raw['mayar_id']
            ?? $raw['response']['data']['id']
            ?? $raw['mayar_transaction_id']
            ?? $raw['response']['data']['transactionId']
            ?? ''
        ));
    }

    private function findMayarTransaction(array $candidateIds, string $orderIdHint = ''): ?object
    {
        if ($orderIdHint !== '') {
            $byOrder = DB::table('transactions')
                ->where('order_id', $orderIdHint)
                ->where('payment_method', 'mayar')
                ->first();
            if ($byOrder) {
                return $byOrder;
            }
        }

        if (empty($candidateIds)) {
            return null;
        }

        $rows = DB::table('transactions')
            ->where('payment_method', 'mayar')
            ->orderByDesc('id')
            ->limit(250)
            ->get();

        foreach ($rows as $row) {
            $raw = json_decode((string) ($row->raw_response ?? ''), true);
            if (!is_array($raw)) {
                continue;
            }
            $storedIds = array_filter([
                trim((string) ($raw['mayar_id'] ?? '')),
                trim((string) ($raw['mayar_transaction_id'] ?? '')),
                trim((string) ($raw['response']['data']['id'] ?? '')),
                trim((string) ($raw['response']['data']['transactionId'] ?? '')),
            ]);
            if (!empty(array_intersect($candidateIds, $storedIds))) {
                return $row;
            }
        }

        return null;
    }

    public function referralAction(Request $request)
    {
        $this->billing->ensureSchema();
        $user = Auth::user();
        $action = trim((string) $request->input('action', 'withdraw'));

        if ($action === 'redeem') {
            $planCode = trim((string) $request->input('plan_code', 'starter'));
            $plan = $this->billing->getPlan($planCode);
            if (!$plan || $planCode === 'free' || (int) ($plan['price'] ?? 0) <= 0) {
                return response()->json(['status' => 'error', 'message' => 'Paket tidak valid.'], 422);
            }
            $price = (int) $plan['price'];

            $redeemed = DB::transaction(function () use ($user, $planCode, $price): bool {
                $affected = DB::table('users')
                    ->where('id', $user->id)
                    ->where('referral_balance', '>=', $price)
                    ->decrement('referral_balance', $price);
                if ($affected < 1) {
                    return false;
                }
                $freshUser = User::find($user->id);
                if ($freshUser) {
                    $this->billing->activatePackageForUser($freshUser, $planCode, null);
                }
                return true;
            });

            if (!$redeemed) {
                $balance = (int) ($user->fresh()->referral_balance ?? 0);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Saldo komisi Anda (Rp' . number_format($balance, 0, ',', '.') . ') belum mencukupi untuk menukar ' . $plan['name'] . ' (Rp' . number_format($price, 0, ',', '.') . ').',
                ], 422);
            }

            return response()->json([
                'status' => 'success',
                'message' => "Saldo komisi berhasil ditukar menjadi {$plan['name']}!",
                'summary' => $this->billing->getUserSummary($user->fresh()),
            ]);
        }

        $request->validate([
            'channel' => 'required|string|max:40',
            'account_number' => 'required|string|max:80',
            'account_name' => 'required|string|max:120',
            'amount' => 'required|integer|min:10000',
        ], [
            'amount.min' => 'Minimal pencairan komisi adalah Rp10.000.',
        ]);

        $amount = (int) $request->input('amount');
        $withdrawn = DB::transaction(function () use ($request, $user, $amount): bool {
            $affected = DB::table('users')
                ->where('id', $user->id)
                ->where('referral_balance', '>=', $amount)
                ->decrement('referral_balance', $amount);
            if ($affected < 1) {
                return false;
            }

            DB::table('referral_withdrawals')->insert([
                'user_id' => $user->id,
                'amount' => $amount,
                'channel' => trim((string) $request->input('channel')),
                'account_number' => trim((string) $request->input('account_number')),
                'account_name' => trim((string) $request->input('account_name')),
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return true;
        });

        if (!$withdrawn) {
            $balance = (int) ($user->fresh()->referral_balance ?? 0);
            return response()->json([
                'status' => 'error',
                'message' => 'Nominal penarikan melebihi saldo komisi tersedia (Rp' . number_format($balance, 0, ',', '.') . ').',
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Permintaan pencairan Rp' . number_format($amount, 0, ',', '.') . ' berhasil dikirim dan sedang diproses admin.',
            'summary' => $this->billing->getUserSummary($user->fresh()),
        ]);
    }
}
