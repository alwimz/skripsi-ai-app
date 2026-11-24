<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct(private BillingService $billing)
    {
    }

    public function loginForm()
    {
        return view('auth.login');
    }

    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->route('app.create');
        }

        return back()->withErrors(['email' => 'Email atau password salah.']);
    }

    public function registerForm(Request $request)
    {
        $ref = trim((string) $request->query('ref', ''));
        if ($ref !== '') {
            session(['pending_referral_code' => strtoupper($ref)]);
        }

        return view('auth.register');
    }

    public function register(Request $request)
    {
        $this->billing->ensureSchema();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
            'referral_code' => 'nullable|string|max:50',
        ]);

        $rawEmail = strtolower(trim((string) $request->input('email')));
        if ($this->billing->isDisposableEmail($rawEmail)) {
            return back()->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['email' => 'Email sementara (temp-mail) tidak diizinkan. Gunakan email pribadi atau email kampus yang aktif.']);
        }

        $canonical = $this->billing->canonicalizeEmail($rawEmail);
        if (User::where('canonical_email', $canonical)->orWhereRaw('LOWER(email) = ?', [$canonical])->exists()) {
            return back()->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['email' => 'Alamat email utama atau alias Gmail ini sudah terdaftar. Silakan masuk menggunakan akun yang sudah ada.']);
        }

        $ip = (string) ($request->ip() ?? '');
        if ($ip !== '' && !in_array($ip, ['127.0.0.1', '::1'], true)) {
            $recentIpCount = User::where('signup_ip', $ip)
                ->where('created_at', '>=', now()->subDay())
                ->count();
            if ($recentIpCount >= 3) {
                return back()->withInput($request->except('password', 'password_confirmation'))
                    ->withErrors(['email' => 'Batas pendaftaran akun baru dari jaringan ini telah tercapai untuk hari ini. Silakan login atau coba lagi besok.']);
            }
        }

        $refInput = trim((string) ($request->input('referral_code') ?: session('pending_referral_code', '')));
        $referrer = $this->billing->resolveReferrer($refInput, $rawEmail);

        $user = User::create([
            'name' => trim((string) $request->input('name')),
            'email' => $rawEmail,
            'password' => Hash::make((string) $request->input('password')),
        ]);

        $user->canonical_email = $canonical;
        $user->signup_ip = $ip !== '' ? $ip : null;
        $user->plan_code = 'free';
        $user->referred_by_user_id = $referrer?->id;
        $user->save();
        $this->billing->ensureUserReferralCode($user);

        session()->forget('pending_referral_code');
        Auth::login($user);

        return redirect()->route('app.create');
    }

    public function quickGoogleLogin(Request $request)
    {
        return redirect()->route('login')->withErrors([
            'email' => 'Login cepat tanpa verifikasi dinonaktifkan demi keamanan kuota akun. Gunakan tombol Masuk dengan Google resmi atau formulir login.',
        ]);
    }

    public function redirectToGoogle(Request $request)
    {
        $ref = trim((string) $request->query('ref', ''));
        if ($ref !== '') {
            session(['pending_referral_code' => strtoupper($ref)]);
        }

        $clientId = config('services.google.client_id') ?: env('GOOGLE_CLIENT_ID');
        if (empty($clientId)) {
            return redirect()->route('login')->withErrors([
                'email' => 'Login Google OAuth belum dikonfigurasi di server. Silakan masuk atau daftar menggunakan email & kata sandi.',
            ]);
        }

        $redirectUri = config('services.google.redirect') ?: (env('GOOGLE_REDIRECT_URI') ?: route('auth.google.callback'));
        $url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid profile email',
            'access_type' => 'offline',
            'prompt' => 'select_account',
        ]);

        return redirect()->away($url);
    }

    public function handleGoogleCallback(Request $request)
    {
        $this->billing->ensureSchema();
        $code = $request->input('code');
        if (empty($code)) {
            return redirect()->route('login')->withErrors(['email' => 'Gagal mendapatkan otorisasi dari Google.']);
        }

        try {
            $clientId = config('services.google.client_id') ?: env('GOOGLE_CLIENT_ID');
            $clientSecret = config('services.google.client_secret') ?: env('GOOGLE_CLIENT_SECRET');
            $redirectUri = config('services.google.redirect') ?: (env('GOOGLE_REDIRECT_URI') ?: route('auth.google.callback'));

            $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'code' => $code,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
            ]);

            if (!$tokenResponse->successful()) {
                $errDetail = $tokenResponse->json()['error_description'] ?? $tokenResponse->body();
                return redirect()->route('login')->withErrors(['email' => 'Gagal verifikasi token dari Google: ' . $errDetail]);
            }

            $accessToken = $tokenResponse->json()['access_token'] ?? null;
            if (!$accessToken) {
                return redirect()->route('login')->withErrors(['email' => 'Token Google tidak valid.']);
            }

            $userProfile = Http::withToken($accessToken)->get('https://www.googleapis.com/oauth2/v3/userinfo')->json();
            $email = strtolower(trim((string) ($userProfile['email'] ?? '')));
            $name = trim((string) ($userProfile['name'] ?? 'Mahasiswa'));

            if ($email === '') {
                return redirect()->route('login')->withErrors(['email' => 'Email Google tidak ditemukan.']);
            }

            if ($this->billing->isDisposableEmail($email)) {
                return redirect()->route('login')->withErrors(['email' => 'Domain email ini tidak diizinkan.']);
            }

            $canonical = $this->billing->canonicalizeEmail($email);
            $user = User::where('email', $email)
                ->orWhere('canonical_email', $canonical)
                ->first();

            if (!$user) {
                $refCode = trim((string) session('pending_referral_code', ''));
                $referrer = $this->billing->resolveReferrer($refCode, $email);
                $user = User::create([
                    'name' => $name ?: 'Mahasiswa',
                    'email' => $email,
                    'password' => Hash::make(Str::random(24)),
                ]);
                $user->canonical_email = $canonical;
                $user->signup_ip = (string) ($request->ip() ?? '');
                $user->plan_code = 'free';
                $user->referred_by_user_id = $referrer?->id;
                $user->email_verified_at = now();
                $user->save();
            } else {
                if (empty($user->canonical_email)) {
                    $user->canonical_email = $canonical;
                }
                if (empty($user->email_verified_at)) {
                    $user->email_verified_at = now();
                }
                $user->save();
            }

            $this->billing->ensureUserReferralCode($user);
            session()->forget('pending_referral_code');

            Auth::login($user, true);
            return redirect()->route('app.create')->with('info', "Selamat datang, {$user->name}! Masuk via Google berhasil.");
        } catch (\Throwable $e) {
            return redirect()->route('login')->withErrors(['email' => 'Gagal login via Google: ' . $e->getMessage()]);
        }
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            Auth::logout();
        }
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
        return redirect('/');
    }
}
