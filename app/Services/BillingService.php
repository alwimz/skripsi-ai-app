<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class BillingService
{
    private static bool $schemaChecked = false;

    /**
     * Daftar resmi paket SkripsiLab AI (berbasis kuota naskah & durasi unlimited, tanpa sistem kredit).
     */
    public function getPlans(bool $includeFree = true): array
    {
        $soldCounts = [];
        try {
            $this->ensureSchema();
            $rows = DB::table('transactions')
                ->select('plan_code', DB::raw('COUNT(*) as total'))
                ->where('status', 'completed')
                ->groupBy('plan_code')
                ->get();
            foreach ($rows as $r) {
                $soldCounts[$r->plan_code] = (int) $r->total;
            }
        } catch (Throwable $e) {
        }

        $plans = [
            'free' => [
                'code' => 'free',
                'name' => 'Free (Coba Dulu)',
                'group' => 'free',
                'badge' => 'Gratis',
                'badge_color' => '#64748b',
                'price' => 0,
                'original_price' => 0,
                'discount_label' => '',
                'period_label' => 'Selamanya',
                'quota_projects' => 0,
                'duration_days' => 0,
                'is_unlimited' => false,
                'sold_count' => 12400,
                'tagline' => 'Coba kualitas penulisan AI & sitasi DOI asli sebelum membeli paket.',
                'features' => [
                    '1 proyek uji coba (Sampul, Bab I, & Daftar Pustaka)',
                    'Semua AI Tools workspace 1x coba gratis per fitur',
                    'Akses penuh 10 Tools Mahasiswa Gratis (/tools)',
                    'Bab II–V & Download Word/PDF terkunci (Upgrade kapan saja)',
                ],
                'locked_features' => [
                    'Bab II, III, IV, & V terkunci',
                    'Download Word (.docx) & PDF (.pdf) terkunci',
                ],
            ],
            'starter' => [
                'code' => 'starter',
                'name' => 'Paket Starter (1 Naskah)',
                'group' => 'quota',
                'badge' => 'Paling Murah',
                'badge_color' => '#059669',
                'price' => 15000,
                'original_price' => 29000,
                'discount_label' => 'Diskon 48%',
                'period_label' => '1 Proposal / Skripsi Full · 30 Hari',
                'quota_projects' => 1,
                'duration_days' => 30,
                'is_unlimited' => false,
                'sold_count' => 2840 + ($soldCounts['starter'] ?? 0),
                'tagline' => 'Paling hemat untuk menuntaskan 1 Proposal atau 1 Skripsi full sampai cetak.',
                'features' => [
                    'Kuota 1 Proposal atau Skripsi Full (Bab I–V lengkap)',
                    'Download Word (.docx) & PDF (.pdf) 4-4-3-3 tanpa batas',
                    'Bebas revisi AI & susun ulang bab tanpa token',
                    'Akses AI Tools (PPT Sidang, Simulasi, Parafrase, Olah Data, Dosen AI)',
                    'Referensi jurnal ber-DOI asli otomatis',
                ],
            ],
            'hemat' => [
                'code' => 'hemat',
                'name' => 'Paket Hemat (3 Naskah)',
                'group' => 'quota',
                'badge' => 'Paling Laris',
                'badge_color' => '#2563eb',
                'price' => 29000,
                'original_price' => 59000,
                'discount_label' => 'Diskon 51%',
                'period_label' => '3 Proposal / Skripsi Full · 90 Hari',
                'quota_projects' => 3,
                'duration_days' => 90,
                'is_unlimited' => false,
                'sold_count' => 4190 + ($soldCounts['hemat'] ?? 0),
                'tagline' => 'Ideal untuk 1 Proposal + 1 Skripsi Full + 1 cadangan jika ganti judul.',
                'features' => [
                    'Kuota 3 Proposal / Skripsi Full (Bab I–V lengkap)',
                    'Download Word (.docx) & PDF (.pdf) siap cetak sepuasnya',
                    'Tanpa sistem kredit — bebas revisi & susun ulang bab',
                    'Akses penuh PPT Sidang, Simulasi Sidang, Parafrase, Plagiasi & Olah Data',
                    'Masa aktif panjang 90 hari (3 bulan)',
                ],
            ],
            'unlimited_1m' => [
                'code' => 'unlimited_1m',
                'name' => 'Unlimited 1 Bulan',
                'group' => 'unlimited',
                'badge' => 'Unlimited',
                'badge_color' => '#4f46e5',
                'price' => 50000,
                'original_price' => 99000,
                'discount_label' => 'Diskon 49%',
                'period_label' => '1 Bulan (30 Hari) Unlimited',
                'quota_projects' => 0,
                'duration_days' => 30,
                'is_unlimited' => true,
                'sold_count' => 1960 + ($soldCounts['unlimited_1m'] ?? 0),
                'tagline' => 'Generate proposal & skripsi tanpa batas jumlah proyek selama 1 bulan penuh.',
                'features' => [
                    'Unlimited Proposal & Skripsi Full (Bab I–V) selama 30 hari',
                    'Download Word (.docx) & PDF (.pdf) tanpa batas',
                    'Semua AI Tools tanpa batas pemakaian harian wajar',
                    'Bebas revisi dosen AI & susun ulang bab sepuasnya',
                    'Cocok untuk kebut sempro hingga sidang dalam 1 bulan',
                ],
            ],
            'unlimited_2m' => [
                'code' => 'unlimited_2m',
                'name' => 'Unlimited 2 Bulan',
                'group' => 'unlimited',
                'badge' => 'Hemat Rp 10rb',
                'badge_color' => '#7c3aed',
                'price' => 90000,
                'original_price' => 180000,
                'discount_label' => 'Diskon 50%',
                'period_label' => '2 Bulan (60 Hari) Unlimited',
                'quota_projects' => 0,
                'duration_days' => 60,
                'is_unlimited' => true,
                'sold_count' => 870 + ($soldCounts['unlimited_2m'] ?? 0),
                'tagline' => 'Pendamping intensif 2 bulan dari pengajuan judul sampai revisi pasca-sempro.',
                'features' => [
                    'Unlimited Proposal & Skripsi Full (Bab I–V) selama 60 hari',
                    'Download Word (.docx) & PDF (.pdf) tanpa batas',
                    'Semua AI Tools (PPT, Simulasi, Parafrase, Olah Data, Plagiasi)',
                    'Lebih hemat dibanding perpanjangan bulanan',
                ],
            ],
            'unlimited_3m' => [
                'code' => 'unlimited_3m',
                'name' => 'Unlimited 3 Bulan',
                'group' => 'unlimited',
                'badge' => 'Populer',
                'badge_color' => '#0284c7',
                'price' => 129000,
                'original_price' => 270000,
                'discount_label' => 'Diskon 52%',
                'period_label' => '3 Bulan (90 Hari) Unlimited',
                'quota_projects' => 0,
                'duration_days' => 90,
                'is_unlimited' => true,
                'sold_count' => 1540 + ($soldCounts['unlimited_3m'] ?? 0),
                'tagline' => 'Pilihan favorit mahasiswa: kawal lengkap 3 bulan sampai sidang skripsi.',
                'features' => [
                    'Unlimited Proposal & Skripsi Full (Bab I–V) selama 90 hari',
                    'Download Word (.docx) & PDF (.pdf) tanpa batas',
                    'Semua AI Tools aktif penuh tanpa kredit/token',
                    'Harga 3 bulan masih lebih hemat dari paket bulanan kompetitor',
                ],
            ],
            'unlimited_6m' => [
                'code' => 'unlimited_6m',
                'name' => 'Unlimited 6 Bulan',
                'group' => 'unlimited',
                'badge' => '1 Semester',
                'badge_color' => '#0d9488',
                'price' => 229000,
                'original_price' => 480000,
                'discount_label' => 'Diskon 52%',
                'period_label' => '6 Bulan (180 Hari) Unlimited',
                'quota_projects' => 0,
                'duration_days' => 180,
                'is_unlimited' => true,
                'sold_count' => 640 + ($soldCounts['unlimited_6m'] ?? 0),
                'tagline' => 'Tenang 1 semester penuh dari Bab I sampai revisi sidang akhir.',
                'features' => [
                    'Unlimited Proposal & Skripsi Full (Bab I–V) selama 180 hari',
                    'Download Word (.docx) & PDF (.pdf) tanpa batas',
                    'Semua AI Tools aktif penuh sepanjang semester',
                    'Prioritas dukungan & pembaruan fitur',
                ],
            ],
            'unlimited_1y' => [
                'code' => 'unlimited_1y',
                'name' => 'Unlimited 1 Tahun',
                'group' => 'unlimited',
                'badge' => 'Super Hemat',
                'badge_color' => '#b45309',
                'price' => 399000,
                'original_price' => 890000,
                'discount_label' => 'Diskon 55%',
                'period_label' => '1 Tahun (365 Hari) Unlimited',
                'quota_projects' => 0,
                'duration_days' => 365,
                'is_unlimited' => true,
                'sold_count' => 420 + ($soldCounts['unlimited_1y'] ?? 0),
                'tagline' => 'Akses paling hemat untuk 1 tahun penuh — cocok juga untuk riset berkelanjutan.',
                'features' => [
                    'Unlimited Proposal & Skripsi Full (Bab I–V) selama 365 hari',
                    'Download Word (.docx) & PDF (.pdf) tanpa batas',
                    'Semua AI Tools aktif penuh sepanjang tahun',
                    'Setara Rp33.000/bulan untuk akses Unlimited penuh',
                ],
            ],
        ];

        if (!$includeFree) {
            unset($plans['free']);
        }

        return $plans;
    }

    public function getPlan(string $code): ?array
    {
        $plans = $this->getPlans(true);
        return $plans[$code] ?? null;
    }

    public function ensureSchema(): void
    {
        if (self::$schemaChecked) {
            return;
        }
        self::$schemaChecked = true;

        try {
            if (Schema::hasTable('users')) {
                Schema::table('users', function (Blueprint $table) {
                    if (!Schema::hasColumn('users', 'plan_code')) {
                        $table->string('plan_code', 32)->default('free');
                    }
                    if (!Schema::hasColumn('users', 'project_quota_limit')) {
                        $table->integer('project_quota_limit')->default(0);
                    }
                    if (!Schema::hasColumn('users', 'project_quota_used')) {
                        $table->integer('project_quota_used')->default(0);
                    }
                    if (!Schema::hasColumn('users', 'free_project_used')) {
                        $table->integer('free_project_used')->default(0);
                    }
                    if (!Schema::hasColumn('users', 'plan_expires_at')) {
                        $table->timestamp('plan_expires_at')->nullable();
                    }
                    if (!Schema::hasColumn('users', 'free_tool_usage')) {
                        $table->text('free_tool_usage')->nullable();
                    }
                    if (!Schema::hasColumn('users', 'referral_code')) {
                        $table->string('referral_code', 32)->nullable();
                    }
                    if (!Schema::hasColumn('users', 'referred_by_user_id')) {
                        $table->unsignedBigInteger('referred_by_user_id')->nullable();
                    }
                    if (!Schema::hasColumn('users', 'referral_balance')) {
                        $table->integer('referral_balance')->default(0);
                    }
                    if (!Schema::hasColumn('users', 'canonical_email')) {
                        $table->string('canonical_email')->nullable();
                    }
                    if (!Schema::hasColumn('users', 'signup_ip')) {
                        $table->string('signup_ip', 45)->nullable();
                    }
                });
            }

            if (Schema::hasTable('projects') && !Schema::hasColumn('projects', 'is_full_unlocked')) {
                Schema::table('projects', function (Blueprint $table) {
                    $table->boolean('is_full_unlocked')->default(false);
                });
            }

            if (!Schema::hasTable('transactions')) {
                Schema::create('transactions', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('user_id')->index();
                    $table->string('order_id', 64)->unique();
                    $table->string('plan_code', 32);
                    $table->string('plan_name', 120);
                    $table->unsignedInteger('amount');
                    $table->unsignedInteger('quota_granted')->default(0);
                    $table->unsignedInteger('duration_days')->default(30);
                    $table->boolean('is_unlimited')->default(false);
                    $table->string('payment_method', 32)->default('qris');
                    $table->text('payment_number')->nullable();
                    $table->string('checkout_url', 500)->nullable();
                    $table->string('status', 32)->default('pending')->index();
                    $table->timestamp('expired_at')->nullable();
                    $table->timestamp('paid_at')->nullable();
                    $table->longText('raw_response')->nullable();
                    $table->timestamps();
                });
            }

            if (!Schema::hasTable('referral_commissions')) {
                Schema::create('referral_commissions', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('referrer_id')->index();
                    $table->unsignedBigInteger('referred_user_id')->index();
                    $table->unsignedBigInteger('transaction_id')->unique();
                    $table->string('plan_code', 32);
                    $table->unsignedInteger('transaction_amount');
                    $table->unsignedInteger('commission_amount');
                    $table->string('status', 32)->default('approved');
                    $table->timestamps();
                });
            }

            if (!Schema::hasTable('referral_withdrawals')) {
                Schema::create('referral_withdrawals', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('user_id')->index();
                    $table->unsignedInteger('amount');
                    $table->string('channel', 40);
                    $table->string('account_number', 80);
                    $table->string('account_name', 120);
                    $table->string('status', 32)->default('pending');
                    $table->timestamps();
                });
            }
        } catch (Throwable $e) {
            Log::warning('BillingService ensureSchema warning: ' . $e->getMessage());
        }
    }

    public function isAdminUser(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        $adminEmails = ['alwi@gmail.com', 'muhalwisyahrir@gmail.com'];
        return in_array(strtolower(trim((string) $user->email)), $adminEmails, true);
    }

    public function canonicalizeEmail(string $email): string
    {
        $email = strtolower(trim($email));
        if (!str_contains($email, '@')) {
            return $email;
        }
        [$local, $domain] = explode('@', $email, 2);
        if (in_array($domain, ['gmail.com', 'googlemail.com'], true)) {
            $local = explode('+', $local, 2)[0];
            $local = str_replace('.', '', $local);
            $domain = 'gmail.com';
        }
        return $local . '@' . $domain;
    }

    public function isDisposableEmail(string $email): bool
    {
        $email = strtolower(trim($email));
        if (!str_contains($email, '@')) {
            return true;
        }
        [, $domain] = explode('@', $email, 2);
        $blocked = [
            'mailinator.com', '10minutemail.com', '10minutemail.net', 'guerrillamail.com',
            'guerrillamail.info', 'guerrillamail.biz', 'sharklasers.com', 'grr.la',
            'tempmail.com', 'temp-mail.org', 'temp-mail.io', 'yopmail.com', 'yopmail.fr',
            'getnada.com', 'maildrop.cc', 'throwawaymail.com', 'trashmail.com',
            'dispostable.com', 'fakeinbox.com', 'mintemail.com', 'mohmal.com',
            'emailondeck.com', 'mailnesia.com', 'tempr.email', 'burnermail.io',
            'inboxbear.com', 'dropmail.me', 'mytemp.email', 'tmpmail.org',
        ];
        if (in_array($domain, $blocked, true)) {
            return true;
        }
        foreach (['tempmail', 'temp-mail', '10minute', 'yopmail', 'guerrilla', 'trashmail', 'mailinator'] as $kw) {
            if (str_contains($domain, $kw)) {
                return true;
            }
        }
        return false;
    }

    public function ensureUserReferralCode(User $user): string
    {
        $this->ensureSchema();
        $code = trim((string) ($user->referral_code ?? ''));
        if ($code !== '') {
            return $code;
        }
        $code = 'SKRIPSI-' . strtoupper(substr(md5(($user->id ?? '1') . ($user->email ?? 'user')), 0, 6));
        try {
            $user->referral_code = $code;
            $user->save();
        } catch (Throwable $e) {
        }
        return $code;
    }

    public function resolveReferrer(?string $rawCode, ?string $excludeEmail = null): ?User
    {
        $this->ensureSchema();
        $code = strtoupper(trim((string) $rawCode));
        if ($code === '') {
            return null;
        }

        $owner = User::where('referral_code', $code)->first();
        if (!$owner && preg_match('/^SKRIPSI-([A-F0-9]{6})$/', $code, $m)) {
            $hashPrefix = $m[1];
            $owner = User::select('id', 'name', 'email', 'referral_code')->get()->first(function ($u) use ($hashPrefix) {
                $expected = strtoupper(substr(md5(($u->id ?? '1') . ($u->email ?? 'user')), 0, 6));
                return $expected === $hashPrefix;
            });
            if ($owner) {
                try {
                    $owner->referral_code = $code;
                    $owner->save();
                } catch (Throwable $e) {
                }
            }
        }

        if ($owner && $excludeEmail && $this->canonicalizeEmail($owner->email) === $this->canonicalizeEmail($excludeEmail)) {
            return null;
        }

        return $owner;
    }

    public function hasActivePaidAccess(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        $this->ensureSchema();
        if ($this->isAdminUser($user)) {
            return true;
        }

        $planCode = (string) ($user->plan_code ?? 'free');
        if ($planCode === '' || $planCode === 'free') {
            return false;
        }

        if (!empty($user->plan_expires_at)) {
            try {
                if (Carbon::parse($user->plan_expires_at)->isPast()) {
                    return false;
                }
            } catch (Throwable $e) {
            }
        }

        $plan = $this->getPlan($planCode);
        if ($plan && !empty($plan['is_unlimited'])) {
            return true;
        }

        return ((int) ($user->project_quota_limit ?? 0) - (int) ($user->project_quota_used ?? 0)) > 0
            || $this->userHasUnlockedProject($user);
    }

    public function isUnlimitedActive(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        if ($this->isAdminUser($user)) {
            return true;
        }
        $planCode = (string) ($user->plan_code ?? 'free');
        $plan = $this->getPlan($planCode);
        if (!$plan || empty($plan['is_unlimited'])) {
            return false;
        }
        if (!empty($user->plan_expires_at)) {
            try {
                return !Carbon::parse($user->plan_expires_at)->isPast();
            } catch (Throwable $e) {
                return false;
            }
        }
        return true;
    }

    public function remainingProjectQuota(User $user): int
    {
        if ($this->isUnlimitedActive($user)) {
            return 9999;
        }
        if (!empty($user->plan_expires_at)) {
            try {
                if (Carbon::parse($user->plan_expires_at)->isPast()) {
                    return 0;
                }
            } catch (Throwable $e) {
            }
        }
        return max(0, (int) ($user->project_quota_limit ?? 0) - (int) ($user->project_quota_used ?? 0));
    }

    private function userHasUnlockedProject(User $user): bool
    {
        try {
            return Project::where('user_id', $user->id)->where('is_full_unlocked', true)->exists();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function canStartProject(User $user): array
    {
        $this->ensureSchema();
        if ($this->isAdminUser($user) || $this->isUnlimitedActive($user)) {
            return ['allowed' => true, 'mode' => 'unlimited'];
        }

        if ($this->remainingProjectQuota($user) > 0) {
            return ['allowed' => true, 'mode' => 'quota'];
        }

        // Tier Free: hanya boleh buat 1 proyek uji coba (tidak reset meski proyek dihapus)
        $freeUsed = (int) ($user->free_project_used ?? 0);
        if ($freeUsed === 0) {
            $existingCount = Project::where('user_id', $user->id)->count();
            if ($existingCount === 0) {
                return ['allowed' => true, 'mode' => 'free'];
            }
        }

        return [
            'allowed' => false,
            'upgrade_required' => true,
            'reason' => 'free_project_limit',
            'message' => 'Akun Free hanya mencakup 1 proyek uji coba. Pilih Paket Starter (Rp15.000) atau Paket Hemat (Rp29.000) untuk membuat proyek baru dan generate full Bab I–V.',
        ];
    }

    public function markProjectStarted(User $user, Project $project): void
    {
        $this->ensureSchema();
        if ($this->isAdminUser($user) || $this->isUnlimitedActive($user)) {
            $project->is_full_unlocked = true;
            $project->save();
            return;
        }

        if ($this->remainingProjectQuota($user) > 0) {
            $project->is_full_unlocked = true;
            $project->save();
            $user->increment('project_quota_used');
            return;
        }

        // Konsumsi jatah 1 proyek Free
        if ((int) ($user->free_project_used ?? 0) < 1) {
            $user->free_project_used = 1;
            $user->save();
        }
    }

    public function canGenerateChapter(User $user, Project $project, string $chapter): array
    {
        $this->ensureSchema();
        $chapter = strtolower(trim($chapter));

        if ($this->isAdminUser($user) || $this->isUnlimitedActive($user) || !empty($project->is_full_unlocked)) {
            return ['allowed' => true];
        }

        // Jika user punya kuota berbayar tersedia, buka kunci proyek ini otomatis saat masuk Bab II-V
        if ($this->remainingProjectQuota($user) > 0) {
            $project->is_full_unlocked = true;
            $project->save();
            $user->increment('project_quota_used');
            return ['allowed' => true];
        }

        // Tier Free hanya boleh generate Sampul/Bagian Depan, Bab I, dan Daftar Pustaka
        if (in_array($chapter, ['bab1', 'front', 'daftar_pustaka'], true)) {
            return ['allowed' => true];
        }

        return [
            'allowed' => false,
            'upgrade_required' => true,
            'reason' => 'chapter_locked',
            'message' => 'Akun Free hanya dapat menyusun Sampul, Bab I, dan Daftar Pustaka. Aktifkan Paket Starter (Rp15.000) atau Paket Hemat (Rp29.000) untuk membuka Bab II–V secara penuh.',
        ];
    }

    public function canDownloadProject(User $user, Project $project): array
    {
        $this->ensureSchema();
        if ($this->isAdminUser($user) || $this->isUnlimitedActive($user) || !empty($project->is_full_unlocked)) {
            return ['allowed' => true];
        }

        if ($this->remainingProjectQuota($user) > 0) {
            $project->is_full_unlocked = true;
            $project->save();
            $user->increment('project_quota_used');
            return ['allowed' => true];
        }

        return [
            'allowed' => false,
            'upgrade_required' => true,
            'reason' => 'download_locked',
            'message' => 'Download Word (.docx) & PDF (.pdf) siap cetak tersedia untuk Paket Starter mulai Rp15.000. Pekerjaan Anda tetap tersimpan dan langsung bisa diunduh setelah aktif.',
        ];
    }

    public function getFreeToolUsage(User $user): array
    {
        $raw = (string) ($user->free_tool_usage ?? '');
        $decoded = $raw !== '' ? json_decode($raw, true) : [];
        return is_array($decoded) ? $decoded : [];
    }

    public function checkToolAccess(User $user, string $toolKey, string $toolLabel): array
    {
        $this->ensureSchema();
        if ($this->isAdminUser($user) || $this->hasActivePaidAccess($user)) {
            return ['allowed' => true];
        }

        $usage = $this->getFreeToolUsage($user);
        if ((int) ($usage[$toolKey] ?? 0) >= 1) {
            return [
                'allowed' => false,
                'upgrade_required' => true,
                'reason' => 'free_tool_limit',
                'message' => "Jatah coba gratis 1x untuk fitur {$toolLabel} sudah terpakai. Upgrade ke Paket Starter (Rp15.000) atau Unlimited untuk akses bebas.",
            ];
        }

        return ['allowed' => true];
    }

    public function markToolUsed(User $user, string $toolKey): void
    {
        $this->ensureSchema();
        if ($this->isAdminUser($user) || $this->hasActivePaidAccess($user)) {
            return;
        }
        $usage = $this->getFreeToolUsage($user);
        $usage[$toolKey] = ((int) ($usage[$toolKey] ?? 0)) + 1;
        $user->free_tool_usage = json_encode($usage);
        $user->save();
    }

    public function getUserSummary(User $user): array
    {
        $this->ensureSchema();
        $isAdmin = $this->isAdminUser($user);
        $planCode = $isAdmin ? 'unlimited_1y' : (string) ($user->plan_code ?? 'free');
        $plan = $this->getPlan($planCode) ?? $this->getPlan('free');
        $isUnlimited = $this->isUnlimitedActive($user);
        $remainingQuota = $this->remainingProjectQuota($user);
        $expiresAt = !empty($user->plan_expires_at) ? Carbon::parse($user->plan_expires_at) : null;
        $isExpired = !$isAdmin && $planCode !== 'free' && $expiresAt !== null && $expiresAt->isPast();
        if ($isExpired) {
            $plan = $this->getPlan('free');
            $planCode = 'free';
        }
        $isPaidActive = $isAdmin || $isUnlimited || ($planCode !== 'free' && $remainingQuota > 0) || ($planCode !== 'free' && $this->userHasUnlockedProject($user));

        $refCode = $this->ensureUserReferralCode($user);
        $invitedUsers = User::where('referred_by_user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(15)
            ->get(['id', 'name', 'email', 'plan_code', 'created_at']);
        $invitedCount = User::where('referred_by_user_id', $user->id)->count();
        $commissions = DB::table('referral_commissions')
            ->where('referrer_id', $user->id)
            ->orderByDesc('created_at')
            ->get();
        $withdrawals = DB::table('referral_withdrawals')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
        $totalEarned = (int) $commissions->sum('commission_amount');

        $freeUsage = $this->getFreeToolUsage($user);
        $recentTransactions = DB::table('transactions')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return [
            'is_admin' => $isAdmin,
            'plan_code' => $planCode,
            'plan' => $plan,
            'is_paid_active' => $isPaidActive,
            'is_unlimited' => $isUnlimited,
            'quota_limit' => (int) ($user->project_quota_limit ?? 0),
            'quota_used' => (int) ($user->project_quota_used ?? 0),
            'remaining_quota' => $remainingQuota,
            'free_project_used' => (int) ($user->free_project_used ?? 0),
            'expires_at' => $expiresAt,
            'expires_at_label' => $isAdmin ? 'Selamanya (Admin)' : ($expiresAt ? $expiresAt->timezone('Asia/Jakarta')->format('d M Y, H:i') . ' WIB' : 'Selamanya'),
            'free_tool_usage' => $freeUsage,
            'referral_code' => $refCode,
            'referral_url' => url('/register?ref=' . $refCode),
            'referral_stats' => [
                'invited_count' => $invitedCount,
                'invited_users' => $invitedUsers,
                'converted_count' => $commissions->count(),
                'total_earned' => $totalEarned,
                'balance' => (int) ($user->referral_balance ?? $totalEarned),
                'history' => $commissions->take(10),
                'withdrawals' => $withdrawals,
            ],
            'recent_transactions' => $recentTransactions,
        ];
    }

    /**
     * Aktivasi paket setelah pembayaran sukses (atau manual oleh Admin) + catat komisi referral 10%.
     */
    public function activatePackageForUser(User $user, string $planCode, ?object $transaction = null): void
    {
        $this->ensureSchema();
        $plan = $this->getPlan($planCode);
        if (!$plan || $planCode === 'free') {
            return;
        }

        $now = Carbon::now();
        $days = (int) ($plan['duration_days'] ?? 30);
        $currentExp = !empty($user->plan_expires_at) ? Carbon::parse($user->plan_expires_at) : null;
        $baseExp = ($currentExp && $currentExp->isFuture()) ? $currentExp : $now;
        $newExp = $baseExp->copy()->addDays($days);

        if (!empty($plan['is_unlimited'])) {
            $user->plan_code = $planCode;
            $user->plan_expires_at = $newExp;
        } else {
            $user->plan_code = $planCode;
            $user->project_quota_limit = (int) ($user->project_quota_limit ?? 0) + (int) ($plan['quota_projects'] ?? 1);
            $user->plan_expires_at = $newExp;
        }
        $user->save();

        // Buka kunci proyek Free milik user jika ada yang sudah terlanjur dibuat agar tidak mengulang dari nol
        if (!empty($plan['is_unlimited'])) {
            Project::where('user_id', $user->id)->update(['is_full_unlocked' => true]);
        } else {
            $lockedProj = Project::where('user_id', $user->id)
                ->where(function ($q) {
                    $q->where('is_full_unlocked', false)->orWhereNull('is_full_unlocked');
                })
                ->orderBy('updated_at', 'desc')
                ->first();
            if ($lockedProj && $this->remainingProjectQuota($user) > 0) {
                $lockedProj->is_full_unlocked = true;
                $lockedProj->save();
                $user->increment('project_quota_used');
            }
        }

        // Catat komisi referral 10% jika user didaftarkan melalui referral dan punya transaksi valid
        if ($transaction && !empty($user->referred_by_user_id) && (int) $user->referred_by_user_id !== (int) $user->id) {
            $alreadyRecorded = DB::table('referral_commissions')
                ->where('transaction_id', $transaction->id)
                ->exists();
            if (!$alreadyRecorded) {
                $commissionAmount = (int) floor(((int) $transaction->amount) * 0.10);
                if ($commissionAmount > 0) {
                    DB::table('referral_commissions')->insert([
                        'referrer_id' => $user->referred_by_user_id,
                        'referred_user_id' => $user->id,
                        'transaction_id' => $transaction->id,
                        'plan_code' => $planCode,
                        'transaction_amount' => (int) $transaction->amount,
                        'commission_amount' => $commissionAmount,
                        'status' => 'approved',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    User::where('id', $user->referred_by_user_id)->increment('referral_balance', $commissionAmount);
                }
            }
        }
    }

    public function verifyPakasirTransaction(string $orderId, int $amount): array
    {
        $project = trim((string) config('services.pakasir.project'));
        $apiKey = trim((string) config('services.pakasir.api_key'));
        $baseUrl = rtrim((string) config('services.pakasir.base_url', 'https://app.pakasir.com'), '/');

        if ($project === '' || $apiKey === '') {
            return ['verified' => false, 'reason' => 'Pakasir credentials missing'];
        }

        try {
            $res = Http::timeout(12)->get("{$baseUrl}/api/transactiondetail", [
                'project' => $project,
                'order_id' => $orderId,
                'amount' => $amount,
                'api_key' => $apiKey,
            ]);

            if ($res->successful()) {
                $json = $res->json();
                $txStatus = strtolower((string) ($json['transaction']['status'] ?? ($json['status'] ?? '')));
                return [
                    'verified' => $txStatus === 'completed',
                    'status' => $txStatus,
                    'data' => $json,
                ];
            }
        } catch (Throwable $e) {
            Log::warning('Pakasir verify failed: ' . $e->getMessage());
        }

        return ['verified' => false, 'reason' => 'API verification failed'];
    }
}
