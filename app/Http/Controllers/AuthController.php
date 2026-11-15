<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function loginForm() {
        return view('auth.login');
    }

    public function authenticate(Request $request) {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->route('app.create');
        }

        return back()->withErrors(['email' => 'Email atau password salah.']);
    }

    public function registerForm() {
        return view('auth.register');
    }

    public function register(Request $request) {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
            'referral_code' => 'nullable|string|max:50',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        Auth::login($user);
        
        return redirect()->route('app.create');
    }

    public function redirectToGoogle() {
        $clientId = env('GOOGLE_CLIENT_ID');
        if (empty($clientId)) {
            // Demo Instant Google Sign-in Fallback jika Google OAuth credentials belum diset di server
            $demoGoogleEmail = 'mahasiswa.google@skripsi-ai.com';
            $user = User::firstOrCreate(
                ['email' => $demoGoogleEmail],
                [
                    'name' => 'Mahasiswa Google Demo',
                    'password' => Hash::make(Str::random(16)),
                ]
            );
            Auth::login($user);
            return redirect()->route('app.create')->with('info', 'Masuk berhasil via Akun Google Demo.');
        }

        $redirectUri = route('auth.google.callback');
        $url = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid profile email',
            'access_type' => 'offline',
            'prompt' => 'select_account',
        ]);

        return redirect()->away($url);
    }

    public function handleGoogleCallback(Request $request) {
        $code = $request->input('code');
        if (empty($code)) {
            return redirect()->route('login')->withErrors(['email' => 'Gagal mendapatkan otorisasi dari Google.']);
        }

        try {
            $clientId = env('GOOGLE_CLIENT_ID');
            $clientSecret = env('GOOGLE_CLIENT_SECRET');
            $redirectUri = route('auth.google.callback');

            $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'code' => $code,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
            ]);

            if (!$tokenResponse->successful()) {
                return redirect()->route('login')->withErrors(['email' => 'Gagal verifikasi token dari Google.']);
            }

            $accessToken = $tokenResponse->json()['access_token'] ?? null;
            if (!$accessToken) {
                return redirect()->route('login')->withErrors(['email' => 'Token Google tidak valid.']);
            }

            $userProfile = Http::withToken($accessToken)->get('https://www.googleapis.com/oauth2/v3/userinfo')->json();
            $email = $userProfile['email'] ?? null;
            $name = $userProfile['name'] ?? 'Mahasiswa';

            if (!$email) {
                return redirect()->route('login')->withErrors(['email' => 'Email Google tidak ditemukan.']);
            }

            $user = User::where('email', $email)->first();
            if (!$user) {
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make(Str::random(24)),
                ]);
            }

            Auth::login($user);
            return redirect()->route('app.create');
        } catch (\Throwable $e) {
            return redirect()->route('login')->withErrors(['email' => 'Gagal login via Google: ' . $e->getMessage()]);
        }
    }

    public function logout(Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
