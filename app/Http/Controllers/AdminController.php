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

        // Hitung metrik (gunakan zona waktu WIB / Asia/Jakarta)
        $nowWib = Carbon::now('Asia/Jakarta');
        $todayStartUtc = $nowWib->copy()->startOfDay()->setTimezone('UTC');
        $weekStartUtc = $nowWib->copy()->subDays(7)->setTimezone('UTC');
        $monthStartUtc = $nowWib->copy()->startOfMonth()->setTimezone('UTC');

        $totalUsers = User::count();
        $usersToday = User::where('created_at', '>=', $todayStartUtc)->count();
        $usersThisWeek = User::where('created_at', '>=', $weekStartUtc)->count();
        $usersThisMonth = User::where('created_at', '>=', $monthStartUtc)->count();
        $totalProjects = Project::count();
        // Estimasi Pendapatan Langganan Paket (Dummy Jutaan Sesuai Permintaan)
        $totalRevenue = 4850000;

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

        // Daftar seluruh proyek beserta pemilik akunnya
        $projectsQuery = Project::orderBy('updated_at', 'desc');
        if (!empty($q)) {
            $matchedUserIds = $users->pluck('id')->all();
            $projectsQuery->where(function ($query) use ($q, $matchedUserIds) {
                $query->where('judul', 'like', "%{$q}%")
                    ->orWhere('prodi', 'like', "%{$q}%");
                if (!empty($matchedUserIds)) {
                    $query->orWhereIn('user_id', $matchedUserIds);
                }
            });
        }
        $projectsList = $projectsQuery->take(50)->get();
        $userMap = User::whereIn('id', $projectsList->pluck('user_id')->filter()->unique()->all())
            ->get()
            ->keyBy('id');

        $projects = $projectsList->map(function ($p) use ($userMap) {
            $owner = $userMap->get($p->user_id);
            $filled = 0;
            foreach (['bab1_content', 'bab2_content', 'bab3_content', 'bab4_content', 'bab5_content'] as $col) {
                if (trim((string) $p->{$col}) !== '') {
                    $filled++;
                }
            }
            $hasDp = trim((string) $p->daftar_pustaka) !== '';
            $p->owner_name = $owner->name ?? 'Tidak diketahui';
            $p->owner_email = $owner->email ?? '-';
            $p->filled_chapters = $filled;
            $p->has_dp = $hasDp;
            return $p;
        });

        return response()
            ->view('admin.users', compact(
                'totalUsers',
                'usersToday',
                'usersThisWeek',
                'usersThisMonth',
                'totalProjects',
                'totalRevenue',
                'users',
                'projects',
                'q'
            ))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
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
