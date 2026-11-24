<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class PaymentController extends Controller
{
    public function __construct(private BillingService $billing)
    {
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

        $pakasirProject = trim((string) config('services.pakasir.project'));
        $pakasirApiKey = trim((string) config('services.pakasir.api_key'));
        $baseUrl = rtrim((string) config('services.pakasir.base_url', 'https://app.pakasir.com'), '/');

        if ($pakasirProject === '' || $pakasirApiKey === '') {
            $waMsg = urlencode("Halo Admin SkripsiLab AI, saya ingin mengaktifkan {$plan['name']} (Rp " . number_format($amount, 0, ',', '.') . ") untuk akun {$user->email} (Kode Pesanan: {$orderId}).");
            return response()->json([
                'status' => 'pakasir_unconfigured',
                'message' => 'Gateway pembayaran Pakasir belum disetel di server (.env PAKASIR_PROJECT & PAKASIR_API_KEY). Anda dapat menyelesaikan aktivasi manual melalui WhatsApp Admin sementara waktu.',
                'order_id' => $orderId,
                'plan' => $plan,
                'whatsapp_url' => "https://wa.me/6282195036971?text={$waMsg}",
            ], 200);
        }

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

            return response()->json([
                'status' => 'success',
                'transaction' => [
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
                'message' => 'Pembayaran berhasil dikonfirmasi! Paket Anda sudah aktif.',
                'summary' => $this->billing->getUserSummary($user->fresh()),
            ]);
        }

        $verification = $this->billing->verifyPakasirTransaction($tx->order_id, (int) $tx->amount);
        if (!empty($verification['verified'])) {
            $updated = DB::table('transactions')
                ->where('id', $tx->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'completed',
                    'paid_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($updated > 0) {
                $freshTx = DB::table('transactions')->where('id', $tx->id)->first();
                $this->billing->activatePackageForUser($user, $tx->plan_code, $freshTx);
            }

            return response()->json([
                'status' => 'completed',
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

        $updated = DB::table('transactions')
            ->where('id', $tx->id)
            ->where('status', 'pending')
            ->update([
                'status' => 'completed',
                'paid_at' => now(),
                'raw_response' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);

        if ($updated > 0) {
            $user = User::find($tx->user_id);
            if ($user) {
                $freshTx = DB::table('transactions')->where('id', $tx->id)->first();
                $this->billing->activatePackageForUser($user, $tx->plan_code, $freshTx);
            }
        }

        return response()->json(['status' => 'success'], 200);
    }

    public function referralAction(Request $request)
    {
        $this->billing->ensureSchema();
        $user = Auth::user();
        $action = trim((string) $request->input('action', 'withdraw'));

        if ($action === 'redeem') {
            $planCode = trim((string) $request->input('plan_code', 'starter'));
            $plan = $this->billing->getPlan($planCode);
            if (!$plan || $planCode === 'free') {
                return response()->json(['status' => 'error', 'message' => 'Paket tidak valid.'], 422);
            }
            $price = (int) $plan['price'];
            $balance = (int) ($user->referral_balance ?? 0);
            if ($balance < $price) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Saldo komisi Anda (Rp' . number_format($balance, 0, ',', '.') . ') belum mencukupi untuk menukar ' . $plan['name'] . ' (Rp' . number_format($price, 0, ',', '.') . ').',
                ], 422);
            }

            $user->decrement('referral_balance', $price);
            $this->billing->activatePackageForUser($user->fresh(), $planCode, null);

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
        $balance = (int) ($user->referral_balance ?? 0);
        if ($amount > $balance) {
            return response()->json([
                'status' => 'error',
                'message' => 'Nominal penarikan melebihi saldo komisi tersedia (Rp' . number_format($balance, 0, ',', '.') . ').',
            ], 422);
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

        $user->decrement('referral_balance', $amount);

        return response()->json([
            'status' => 'success',
            'message' => 'Permintaan pencairan Rp' . number_format($amount, 0, ',', '.') . ' berhasil dikirim dan sedang diproses admin.',
            'summary' => $this->billing->getUserSummary($user->fresh()),
        ]);
    }
}
