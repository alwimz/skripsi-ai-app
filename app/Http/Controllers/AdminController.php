<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Kode Akses Admin (Passcode).
     * Dapat diatur via .env (ADMIN_PASSWORD), default: 'admin123'
     */
    protected function getAdminPasscode(): string
    {
        return env('ADMIN_PASSWORD', 'admin123');
    }

    /**
     * Periksa apakah akses admin sah (via session, query key ?key=..., atau email admin terdaftar).
     */
    protected function isAdminAuthorized(Request $request): bool
    {
        // 1. Cek jika menyertakan parameter URL langsung (misal: /admin?key=admin123 atau /admin?pin=admin123)
        $queryKey = $request->query('key') ?? $request->query('pin');
        if (!empty($queryKey) && $queryKey === $this->getAdminPasscode()) {
            session(['admin_authenticated' => true]);
            return true;
        }

        // 2. Cek session admin
        if (session('admin_authenticated') === true) {
            return true;
        }

        // 3. Cek jika sedang login sebagai email admin
        if (auth()->check()) {
            $adminEmails = ['alwi@gmail.com', 'muhalwisyahrir@gmail.com'];
            if (in_array(strtolower(trim(auth()->user()->email)), $adminEmails)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Halaman Dashboard Admin & Statistik Pengguna
     */
    public function index(Request $request)
    {
        if (!$this->isAdminAuthorized($request)) {
            return view('admin.login', [
                'error' => session('error'),
                'info' => session('info')
            ]);
        }

        // Hitung metrik
        $now = Carbon::now();
        $totalUsers = User::count();
        $usersToday = User::whereDate('created_at', Carbon::today())->count();
        $usersThisWeek = User::where('created_at', '>=', Carbon::now()->subDays(7))->count();
        $usersThisMonth = User::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->count();
        $totalProjects = Project::count();

        // Pencarian pengguna
        $q = trim($request->query('q', ''));
        $usersQuery = User::withCount('projects')->orderBy('created_at', 'desc');

        if (!empty($q)) {
            $usersQuery->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%");
            });
        }

        $users = $usersQuery->get();

        return view('admin.users', compact(
            'totalUsers',
            'usersToday',
            'usersThisWeek',
            'usersThisMonth',
            'totalProjects',
            'users',
            'q'
        ));
    }

    /**
     * Verifikasi Passcode PIN Admin
     */
    public function login(Request $request)
    {
        $pin = trim($request->input('pin', ''));
        if ($pin === $this->getAdminPasscode()) {
            session(['admin_authenticated' => true]);
            return redirect()->route('admin.dashboard')->with('success', 'Akses Admin Berhasil Diberikan.');
        }

        return back()->with('error', 'Kode Akses Admin salah. Silakan coba lagi.');
    }

    /**
     * Kunci / Logout Sesi Admin
     */
    public function logout(Request $request)
    {
        session()->forget('admin_authenticated');
        return redirect()->route('admin.dashboard')->with('info', 'Sesi admin telah dikunci.');
    }
}
