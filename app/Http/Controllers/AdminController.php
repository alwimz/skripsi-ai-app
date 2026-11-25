<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use App\Services\BillingService;
use App\Services\MetaConversionsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function __construct(
        private BillingService $billing,
        private MetaConversionsService $metaCapi
    ) {
    }
    /**
     * Kode Akses Admin (Passcode).
     * Dapat diatur via .env (ADMIN_PASSWORD), default: 'admin123'
     */
    protected function getAdminPasscode(): string
    {
        $configured = trim((string) config('services.admin_password', ''));

        return $configured !== '' ? $configured : 'admin123';
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
            return response()
                ->view('admin.login', [
                    'error' => session('error'),
                    'info' => session('info')
                ])
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        $this->billing->ensureSchema();

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
        $realRevenue = (int) DB::table('transactions')->where('status', 'completed')->sum('amount');
        $totalRevenue = $realRevenue;

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

        $transactions = DB::table('transactions')
            ->orderByDesc('created_at')
            ->limit(30)
            ->get()
            ->map(function ($tx) use ($userMap) {
                $owner = $userMap->get($tx->user_id) ?? User::find($tx->user_id);
                $tx->user_name = $owner->name ?? 'User #' . $tx->user_id;
                $tx->user_email = $owner->email ?? '-';
                return $tx;
            });

        $withdrawals = DB::table('referral_withdrawals')
            ->orderByDesc('created_at')
            ->limit(30)
            ->get()
            ->map(function ($w) {
                $owner = User::find($w->user_id);
                $w->user_name = $owner->name ?? 'User #' . $w->user_id;
                $w->user_email = $owner->email ?? '-';
                return $w;
            });

        $plans = $this->billing->getPlans(true);

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
                'transactions',
                'withdrawals',
                'plans',
                'q'
            ))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function updateUserPlan(Request $request, $id)
    {
        if (!$this->isAdminAuthorized($request)) {
            abort(403);
        }
        $this->billing->ensureSchema();
        $user = User::findOrFail($id);
        $planCode = trim((string) $request->input('plan_code', 'free'));

        if ($planCode === 'free') {
            $user->plan_code = 'free';
            $user->project_quota_limit = 0;
            $user->project_quota_used = 0;
            $user->paid_tool_limits = null;
            $user->paid_tool_used = null;
            $user->plan_expires_at = null;
            $user->save();
            return back()->with('success', "Tier {$user->name} dikembalikan ke Free.");
        }

        $plan = $this->billing->getPlan($planCode);
        if (!$plan) {
            return back()->with('error', 'Kode paket tidak valid.');
        }

        $this->billing->activatePackageForUser($user, $planCode, null);
        if ((int) ($plan['price'] ?? 0) > 0) {
            $this->metaCapi->sendEvent('Purchase', $user, [
                'content_name' => $plan['name'],
                'content_ids' => [$plan['code']],
                'content_type' => 'product',
                'value' => (int) $plan['price'],
                'currency' => 'IDR',
                'num_items' => 1,
            ], 'adm_' . $user->id . '_' . $plan['code'] . '_' . date('YmdHi'), $request, route('billing.plans'));
        }
        return back()->with('success', "Paket {$plan['name']} berhasil diaktifkan untuk {$user->name}.");
    }

    public function markWithdrawalPaid(Request $request, $id)
    {
        if (!$this->isAdminAuthorized($request)) {
            abort(403);
        }
        $this->billing->ensureSchema();
        DB::table('referral_withdrawals')
            ->where('id', $id)
            ->where('status', 'pending')
            ->update([
                'status' => 'paid',
                'updated_at' => now(),
            ]);
        return back()->with('success', 'Status pencairan referral ditandai sudah dibayar.');
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
