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

        $fullImportantFeatures = [
            'Unlimited Proposal & Skripsi Bab 1–5',
            'Unduh Word & PDF (Unlimited)',
            'Sitasi otomatis ber-DOI',
            'Parafrase Bab 1–5 (Unlimited)',
            'Lab Revisi AI (Unlimited)',
            'Generate PPT Sidang (Unlimited)',
            'Simulasi Sidang AI (Unlimited)',
            'Olah Data SPSS & Bab IV (Unlimited)',
            'Cari Ide Judul & Jurnal (Unlimited)',
            'Dosen AI & 10 Tools (Unlimited)',
        ];

        $starterFeatures = [
            '1 Proposal / Skripsi Bab 1–5',
            'Unduh Word & PDF (Unlimited)',
            'Lab Revisi & Sitasi DOI (Unlimited)',
            'Cari Ide Judul & Jurnal (2x kuota)',
            'Parafrase Bab 1–5 (2x kuota)',
            'Generate PPT Sidang (2x kuota)',
            'Simulasi Sidang AI (2x kuota)',
            'Olah Data SPSS & Bab IV (2x kuota)',
            'Dosen AI & 10 Tools (Unlimited)',
        ];

        $hematFeatures = [
            '3 Proposal / Skripsi Bab 1–5',
            'Unduh Word & PDF (Unlimited)',
            'Lab Revisi & Sitasi DOI (Unlimited)',
            'Cari Ide Judul & Jurnal (5x kuota)',
            'Parafrase Bab 1–5 (5x kuota)',
            'Generate PPT Sidang (5x kuota)',
            'Simulasi Sidang AI (5x kuota)',
            'Olah Data SPSS & Bab IV (5x kuota)',
            'Dosen AI & 10 Tools (Unlimited)',
        ];

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
                'tool_quota' => 1,
                'duration_days' => 0,
                'is_unlimited' => false,
                'sold_count' => 12400,
                'summary_title' => '1 Proyek Uji Coba + Sampul, Bab I & Pustaka',
                'summary_desc' => 'Coba Bab I, sitasi DOI & 1x semua fitur AI.',
                'tagline' => 'Coba gratis sebelum memilih paket.',
                'features' => [
                    '1 proyek (Sampul, Bab 1 & Pustaka)',
                    'Sitasi otomatis ber-DOI',
                    'Cari Judul & Jurnal (1x coba)',
                    'Parafrase AI (1x coba)',
                    'PPT & Simulasi Sidang (1x)',
                    'Olah Data SPSS AI (1x coba)',
                    'Dosen AI 24 jam (Gratis)',
                    '10 Tools Mahasiswa gratis',
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
                'period_label' => '1 Proposal / Skripsi Full · 1 Bulan (30 Hari)',
                'quota_projects' => 1,
                'tool_quota' => 2,
                'duration_days' => 30,
                'is_unlimited' => false,
                'sold_count' => 2840 + ($soldCounts['starter'] ?? 0),
                'summary_title' => '1 Naskah Full + 2x Kuota Fitur AI',
                'summary_desc' => 'Download Word/PDF, Revisi & 10 Tools Unlimited 30 hari.',
                'tagline' => 'Hemat untuk 1 Proposal atau Skripsi full sampai cetak.',
                'features' => $starterFeatures,
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
                'period_label' => '3 Proposal / Skripsi Full · 1 Bulan (30 Hari)',
                'quota_projects' => 3,
                'tool_quota' => 5,
                'duration_days' => 30,
                'is_unlimited' => false,
                'sold_count' => 4190 + ($soldCounts['hemat'] ?? 0),
                'summary_title' => '3 Naskah Full + 5x Kuota Fitur AI',
                'summary_desc' => 'Download Word/PDF, Revisi & 10 Tools Unlimited 30 hari.',
                'tagline' => 'Pas untuk Proposal + Skripsi Full + cadangan.',
                'features' => $hematFeatures,
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
                'tool_quota' => null,
                'duration_days' => 30,
                'is_unlimited' => true,
                'sold_count' => 1960 + ($soldCounts['unlimited_1m'] ?? 0),
                'summary_title' => 'Unlimited Proposal & Skripsi (30 Hari)',
                'summary_desc' => 'Naskah & seluruh fitur AI tanpa batas 30 hari.',
                'tagline' => 'Bebas buat banyak naskah selama 1 bulan.',
                'features' => $fullImportantFeatures,
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
                'tool_quota' => null,
                'duration_days' => 60,
                'is_unlimited' => true,
                'sold_count' => 870 + ($soldCounts['unlimited_2m'] ?? 0),
                'summary_title' => 'Unlimited Proposal & Skripsi (60 Hari)',
                'summary_desc' => 'Naskah & seluruh fitur AI tanpa batas 60 hari.',
                'tagline' => 'Kawal dari pengajuan judul sampai revisi sempro.',
                'features' => $fullImportantFeatures,
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
                'tool_quota' => null,
                'duration_days' => 90,
                'is_unlimited' => true,
                'sold_count' => 1540 + ($soldCounts['unlimited_3m'] ?? 0),
                'summary_title' => 'Unlimited Proposal & Skripsi (90 Hari)',
                'summary_desc' => 'Naskah & seluruh fitur AI tanpa batas 90 hari.',
                'tagline' => 'Favorit mahasiswa untuk 3 bulan sampai sidang.',
                'features' => $fullImportantFeatures,
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
                'tool_quota' => null,
                'duration_days' => 180,
                'is_unlimited' => true,
                'sold_count' => 640 + ($soldCounts['unlimited_6m'] ?? 0),
                'summary_title' => 'Unlimited Proposal & Skripsi (180 Hari)',
                'summary_desc' => 'Naskah & seluruh fitur AI tanpa batas 180 hari.',
                'tagline' => 'Tenang 1 semester penuh sampai revisi sidang.',
                'features' => $fullImportantFeatures,
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
                'tool_quota' => null,
                'duration_days' => 365,
                'is_unlimited' => true,
                'sold_count' => 420 + ($soldCounts['unlimited_1y'] ?? 0),
                'summary_title' => 'Unlimited Proposal & Skripsi (1 Tahun Penuh)',
                'summary_desc' => 'Naskah & fitur tanpa batas · setara Rp33rb/bln.',
                'tagline' => 'Paling hemat untuk akses Unlimited 1 tahun.',
                'features' => $fullImportantFeatures,
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

        $connName = (string) config('database.default', 'sqlite');
        $dbName = (string) config("database.connections.{$connName}.database", '');
        $flagPath = sys_get_temp_dir() . '/sl_billing_schema_v3_' . md5($connName . '|' . $dbName) . '.ok';
        if (@is_file($flagPath)) {
            return;
        }

        try {
            DB::select('SELECT plan_code, project_quota_limit, project_quota_used, free_project_used, plan_expires_at, free_tool_usage, paid_tool_limits, paid_tool_used, referral_code, referred_by_user_id, referral_balance, canonical_email, signup_ip FROM users LIMIT 1');
            DB::select('SELECT is_full_unlocked FROM projects LIMIT 1');
            DB::select('SELECT 1 FROM transactions LIMIT 1');
            DB::select('SELECT 1 FROM referral_commissions LIMIT 1');
            DB::select('SELECT 1 FROM referral_withdrawals LIMIT 1');
            @file_put_contents($flagPath, '1');
            return;
        } catch (Throwable $probeEx) {
            // Lanjutkan DDL jika ada kolom/tabel yang belum tersedia
        }

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
                    if (!Schema::hasColumn('users', 'paid_tool_limits')) {
                        $table->text('paid_tool_limits')->nullable();
                    }
                    if (!Schema::hasColumn('users', 'paid_tool_used')) {
                        $table->text('paid_tool_used')->nullable();
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
            @file_put_contents($flagPath, '1');
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
            'mailinator.com',
            '10minutemail.com',
            '10minutemail.net',
            'guerrillamail.com',
            'guerrillamail.info',
            'guerrillamail.biz',
            'sharklasers.com',
            'grr.la',
            'tempmail.com',
            'temp-mail.org',
            'temp-mail.io',
            'yopmail.com',
            'yopmail.fr',
            'getnada.com',
            'maildrop.cc',
            'throwawaymail.com',
            'trashmail.com',
            'dispostable.com',
            'fakeinbox.com',
            'mintemail.com',
            'mohmal.com',
            'emailondeck.com',
            'mailnesia.com',
            'tempr.email',
            'burnermail.io',
            'inboxbear.com',
            'dropmail.me',
            'mytemp.email',
            'tmpmail.org',
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

        return (int) ($user->project_quota_limit ?? 0) > 0
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
            'message' => 'Akun Free hanya mencakup 1 proyek uji coba. Aktifkan paket mulai dari Paket Starter Rp15.000 untuk membuka proyek baru secara penuh.',
        ];
    }

    /**
     * Klaim jatah pembuatan proyek baru secara atomik agar permintaan paralel tidak menembus batas Free/Kuota.
     */
    public function markProjectStarted(User $user, Project $project): bool
    {
        $this->ensureSchema();
        if ($this->isAdminUser($user) || $this->isUnlimitedActive($user)) {
            // Unlimited selalu dievaluasi live berdasarkan plan_expires_at (tidak dikunci permanen di proyek)
            return true;
        }

        if ($this->tryUnlockProjectWithPaidQuota($user, $project)) {
            return true;
        }

        return DB::transaction(function () use ($user) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();
            if (!$lockedUser) {
                return false;
            }
            if ((int) ($lockedUser->free_project_used ?? 0) >= 1) {
                return false;
            }
            $otherProjectsCount = Project::where('user_id', $lockedUser->id)->count();
            if ($otherProjectsCount > 1) {
                return false;
            }
            $affected = DB::table('users')
                ->where('id', $lockedUser->id)
                ->whereRaw('COALESCE(free_project_used, 0) = 0')
                ->update(['free_project_used' => 1]);
            $user->free_project_used = 1;
            return $affected > 0;
        });
    }

    private function tryUnlockProjectWithPaidQuota(User $user, Project $project): bool
    {
        if (!empty($project->is_full_unlocked)) {
            return true;
        }

        return DB::transaction(function () use ($user, $project): bool {
            $lockedProj = Project::where('id', $project->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();
            if (!$lockedProj) {
                return false;
            }
            if (!empty($lockedProj->is_full_unlocked)) {
                $project->is_full_unlocked = true;
                return true;
            }

            $now = Carbon::now();
            $claimed = DB::table('users')
                ->where('id', $user->id)
                ->whereRaw('COALESCE(project_quota_used, 0) < COALESCE(project_quota_limit, 0)')
                ->where(function ($q) use ($now) {
                    $q->whereNull('plan_expires_at')->orWhere('plan_expires_at', '>', $now);
                })
                ->increment('project_quota_used');

            if ($claimed < 1) {
                return false;
            }

            $flipped = DB::table('projects')
                ->where('id', $project->id)
                ->where('user_id', $user->id)
                ->where(function ($q) {
                    $q->where('is_full_unlocked', false)->orWhereNull('is_full_unlocked');
                })
                ->update(['is_full_unlocked' => true]);

            if ($flipped < 1) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->whereRaw('COALESCE(project_quota_used, 0) > 0')
                    ->decrement('project_quota_used');
            }

            $project->is_full_unlocked = true;
            $user->refresh();
            return true;
        });
    }

    public function canGenerateChapter(User $user, Project $project, string $chapter): array
    {
        $this->ensureSchema();
        $chapter = strtolower(trim($chapter));

        if (!in_array($chapter, ['bab1', 'bab2', 'bab3', 'bab4', 'bab5', 'front', 'daftar_pustaka'], true)) {
            return [
                'allowed' => false,
                'upgrade_required' => false,
                'reason' => 'invalid_chapter',
                'message' => 'Bagian bab yang diminta tidak valid.',
            ];
        }

        if ($this->isAdminUser($user) || $this->isUnlimitedActive($user) || !empty($project->is_full_unlocked)) {
            return ['allowed' => true];
        }

        // Jika user punya kuota berbayar tersedia, klaim 1 slot proyek secara atomik saat masuk Bab II-V
        if ($this->tryUnlockProjectWithPaidQuota($user, $project)) {
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
            'message' => 'Akun Free hanya dapat menyusun Sampul, Bab I, dan Daftar Pustaka. Aktifkan paket mulai dari Paket Starter Rp15.000 untuk membuka Bab II–V secara penuh.',
        ];
    }

    public function canDownloadProject(User $user, Project $project): array
    {
        $this->ensureSchema();
        if ($this->isAdminUser($user) || $this->isUnlimitedActive($user)) {
            return ['allowed' => true];
        }

        $isNotExpired = empty($user->plan_expires_at) || !Carbon::parse($user->plan_expires_at)->isPast();
        if (!empty($project->is_full_unlocked)) {
            if ($isNotExpired) {
                return ['allowed' => true];
            }
            return [
                'allowed' => false,
                'upgrade_required' => true,
                'reason' => 'plan_expired',
                'message' => 'Masa aktif paket 1 bulan Anda telah berakhir. Aktifkan paket mulai dari Paket Starter Rp15.000 untuk mengunduh Word & PDF kembali — draf Anda tetap tersimpan.',
            ];
        }

        if ($this->tryUnlockProjectWithPaidQuota($user, $project)) {
            return ['allowed' => true];
        }

        return [
            'allowed' => false,
            'upgrade_required' => true,
            'reason' => 'download_locked',
            'message' => 'Download Word (.docx) & PDF (.pdf) siap cetak tersedia. Aktifkan paket mulai dari Paket Starter Rp15.000 untuk membuka secara penuh — pekerjaan Anda tetap tersimpan.',
        ];
    }

    public const METERED_TOOLS = [
        'titles',
        'journals',
        'paraphrase',
        'ppt',
        'defense',
        'stats_analyze',
        'stats_interpret',
        'plagiarism',
    ];

    public function getFreeToolUsage(User $user): array
    {
        $raw = (string) ($user->free_tool_usage ?? '');
        $decoded = $raw !== '' ? json_decode($raw, true) : [];
        return is_array($decoded) ? $decoded : [];
    }

    private function decodeJsonMap(?string $raw): array
    {
        $s = (string) ($raw ?? '');
        $decoded = $s !== '' ? json_decode($s, true) : [];
        return is_array($decoded) ? $decoded : [];
    }

    public function getPaidToolStatus(User $user): array
    {
        $planCode = (string) ($user->plan_code ?? 'free');
        $plan = $this->getPlan($planCode);
        $defaultQuota = $plan && !empty($plan['tool_quota']) ? (int) $plan['tool_quota'] : 0;

        $limits = $this->decodeJsonMap($user->paid_tool_limits ?? null);
        $used = $this->decodeJsonMap($user->paid_tool_used ?? null);
        $out = [];
        foreach (self::METERED_TOOLS as $key) {
            $lim = array_key_exists($key, $limits) ? (int) $limits[$key] : $defaultQuota;
            $u = max(0, (int) ($used[$key] ?? 0));
            $out[$key] = [
                'limit' => $lim,
                'used' => $u,
                'remaining' => max(0, $lim - $u),
            ];
        }
        return $out;
    }

    public function checkToolAccess(User $user, string $toolKey, string $toolLabel): array
    {
        $this->ensureSchema();
        if ($this->isAdminUser($user) || $this->isUnlimitedActive($user)) {
            return ['allowed' => true];
        }

        if ($this->hasActivePaidAccess($user)) {
            if (!in_array($toolKey, self::METERED_TOOLS, true)) {
                return ['allowed' => true];
            }
            $status = $this->getPaidToolStatus($user)[$toolKey] ?? ['limit' => 2, 'used' => 0, 'remaining' => 2];
            if ($status['remaining'] < 1) {
                return [
                    'allowed' => false,
                    'upgrade_required' => true,
                    'reason' => 'paid_tool_limit',
                    'message' => "Kuota fitur {$toolLabel} pada paket Anda ({$status['used']}/{$status['limit']}x) sudah habis. Tambah kuota atau pilih Unlimited untuk akses bebas.",
                ];
            }
            return ['allowed' => true];
        }

        $usage = $this->getFreeToolUsage($user);
        if ((int) ($usage[$toolKey] ?? 0) >= 1) {
            return [
                'allowed' => false,
                'upgrade_required' => true,
                'reason' => 'free_tool_limit',
                'message' => "Jatah coba gratis 1x untuk fitur {$toolLabel} sudah terpakai. Aktifkan paket mulai dari Paket Starter Rp15.000 untuk membuka secara penuh.",
            ];
        }

        return ['allowed' => true];
    }

    /**
     * Cek dan reservasi atomik jatah fitur AI (Free 1x, Starter 2x, Hemat 5x) agar request paralel tidak menembus kuota.
     */
    public function reserveToolAccess(User $user, string $toolKey, string $toolLabel): array
    {
        $this->ensureSchema();
        if ($this->isAdminUser($user) || $this->isUnlimitedActive($user)) {
            return ['allowed' => true, 'reserved' => false, 'reserved_mode' => null];
        }

        return DB::transaction(function () use ($user, $toolKey, $toolLabel): array {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();
            if (!$lockedUser) {
                return ['allowed' => false, 'upgrade_required' => true, 'reason' => 'free_tool_limit', 'message' => 'Akun tidak ditemukan.'];
            }
            if ($this->isAdminUser($lockedUser) || $this->isUnlimitedActive($lockedUser)) {
                return ['allowed' => true, 'reserved' => false, 'reserved_mode' => null];
            }

            if ($this->hasActivePaidAccess($lockedUser)) {
                if (!in_array($toolKey, self::METERED_TOOLS, true)) {
                    return ['allowed' => true, 'reserved' => false, 'reserved_mode' => null];
                }
                $status = $this->getPaidToolStatus($lockedUser)[$toolKey] ?? ['limit' => 2, 'used' => 0, 'remaining' => 2];
                if ($status['remaining'] < 1) {
                    return [
                        'allowed' => false,
                        'upgrade_required' => true,
                        'reason' => 'paid_tool_limit',
                        'message' => "Kuota fitur {$toolLabel} pada paket Anda ({$status['used']}/{$status['limit']}x) sudah habis. Tambah kuota atau pilih Unlimited untuk akses bebas.",
                    ];
                }
                $limits = $this->decodeJsonMap($lockedUser->paid_tool_limits ?? null);
                if (!array_key_exists($toolKey, $limits)) {
                    $limits[$toolKey] = $status['limit'];
                    $lockedUser->paid_tool_limits = json_encode($limits);
                }
                $usedMap = $this->decodeJsonMap($lockedUser->paid_tool_used ?? null);
                $usedMap[$toolKey] = $status['used'] + 1;
                $lockedUser->paid_tool_used = json_encode($usedMap);
                $lockedUser->save();
                $user->paid_tool_limits = $lockedUser->paid_tool_limits;
                $user->paid_tool_used = $lockedUser->paid_tool_used;

                return ['allowed' => true, 'reserved' => true, 'reserved_mode' => 'paid'];
            }

            $usage = $this->getFreeToolUsage($lockedUser);
            if ((int) ($usage[$toolKey] ?? 0) >= 1) {
                return [
                    'allowed' => false,
                    'upgrade_required' => true,
                    'reason' => 'free_tool_limit',
                    'message' => "Jatah coba gratis 1x untuk fitur {$toolLabel} sudah terpakai. Aktifkan paket mulai dari Paket Starter Rp15.000 untuk membuka secara penuh.",
                ];
            }

            $usage[$toolKey] = 1;
            $lockedUser->free_tool_usage = json_encode($usage);
            $lockedUser->save();
            $user->free_tool_usage = $lockedUser->free_tool_usage;

            return ['allowed' => true, 'reserved' => true, 'reserved_mode' => 'free'];
        });
    }

    /**
     * Kembalikan reservasi jatah fitur jika panggilan AI eksternal gagal teknis.
     */
    public function releaseToolReservation(User $user, string $toolKey, ?string $reservedMode = null): void
    {
        $this->ensureSchema();
        DB::transaction(function () use ($user, $toolKey, $reservedMode): void {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();
            if (!$lockedUser) {
                return;
            }
            $mode = $reservedMode ?? ($this->hasActivePaidAccess($lockedUser) ? 'paid' : 'free');
            if ($mode === 'paid') {
                $usedMap = $this->decodeJsonMap($lockedUser->paid_tool_used ?? null);
                if ((int) ($usedMap[$toolKey] ?? 0) > 0) {
                    $usedMap[$toolKey] = max(0, (int) $usedMap[$toolKey] - 1);
                    $lockedUser->paid_tool_used = json_encode($usedMap);
                    $lockedUser->save();
                    $user->paid_tool_used = $lockedUser->paid_tool_used;
                }
                return;
            }
            $usage = $this->getFreeToolUsage($lockedUser);
            if ((int) ($usage[$toolKey] ?? 0) > 0) {
                $usage[$toolKey] = max(0, (int) $usage[$toolKey] - 1);
                $lockedUser->free_tool_usage = json_encode($usage);
                $lockedUser->save();
                $user->free_tool_usage = $lockedUser->free_tool_usage;
            }
        });
    }

    public function markToolUsed(User $user, string $toolKey): void
    {
        $this->ensureSchema();
        if ($this->isAdminUser($user) || $this->isUnlimitedActive($user)) {
            return;
        }
        DB::transaction(function () use ($user, $toolKey): void {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();
            if (!$lockedUser || $this->isAdminUser($lockedUser) || $this->isUnlimitedActive($lockedUser)) {
                return;
            }
            if ($this->hasActivePaidAccess($lockedUser)) {
                if (!in_array($toolKey, self::METERED_TOOLS, true)) {
                    return;
                }
                $status = $this->getPaidToolStatus($lockedUser)[$toolKey] ?? ['limit' => 2, 'used' => 0];
                $limits = $this->decodeJsonMap($lockedUser->paid_tool_limits ?? null);
                if (!array_key_exists($toolKey, $limits)) {
                    $limits[$toolKey] = $status['limit'];
                    $lockedUser->paid_tool_limits = json_encode($limits);
                }
                $usedMap = $this->decodeJsonMap($lockedUser->paid_tool_used ?? null);
                $usedMap[$toolKey] = $status['used'] + 1;
                $lockedUser->paid_tool_used = json_encode($usedMap);
                $lockedUser->save();
                $user->paid_tool_limits = $lockedUser->paid_tool_limits;
                $user->paid_tool_used = $lockedUser->paid_tool_used;
                return;
            }
            $usage = $this->getFreeToolUsage($lockedUser);
            $usage[$toolKey] = max(1, (int) ($usage[$toolKey] ?? 0));
            $lockedUser->free_tool_usage = json_encode($usage);
            $lockedUser->save();
            $user->free_tool_usage = $lockedUser->free_tool_usage;
        });
    }

    public function getUserSummary(User $user, bool $includeDetails = true): array
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

        $projStats = Project::where('user_id', $user->id)
            ->selectRaw('COUNT(*) as total_cnt, SUM(CASE WHEN is_full_unlocked = 1 THEN 1 ELSE 0 END) as unlocked_cnt')
            ->first();
        $totalProjectsCount = (int) ($projStats->total_cnt ?? 0);
        $unlockedProjectsCount = (int) ($projStats->unlocked_cnt ?? 0);

        $isPaidActive = $isAdmin || $isUnlimited || ($planCode !== 'free' && !$isExpired && (((int) ($user->project_quota_limit ?? 0)) > 0 || $unlockedProjectsCount > 0));
        $freeUsage = $this->getFreeToolUsage($user);
        $toolQuotaStatus = $this->getPaidToolStatus($user);

        $remainingSeconds = $expiresAt ? Carbon::now()->diffInSeconds($expiresAt, false) : 0;
        $remainingDays = (!$isAdmin && $planCode !== 'free' && $expiresAt)
            ? max(0, (int) ceil($remainingSeconds / 86400))
            : null;
        $totalDays = (!$isAdmin && $planCode !== 'free' && $expiresAt)
            ? max((int) ($plan['duration_days'] ?? 30), (int) ($remainingDays ?? 0))
            : null;

        $refCode = '';
        $invitedUsers = collect();
        $invitedCount = 0;
        $commissions = collect();
        $withdrawals = collect();
        $totalEarned = 0;
        $recentTransactions = collect();

        if ($includeDetails) {
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
            $recentTransactions = DB::table('transactions')
                ->where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();
        }

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
            'remaining_days' => $remainingDays,
            'total_days' => $totalDays,
            'unlocked_projects_count' => $unlockedProjectsCount,
            'total_projects_count' => $totalProjectsCount,
            'expires_at' => $expiresAt,
            'expires_at_label' => $isAdmin ? 'Selamanya (Admin)' : ($expiresAt ? $expiresAt->timezone('Asia/Jakarta')->format('d M Y, H:i') . ' WIB' : 'Selamanya'),
            'free_tool_usage' => $freeUsage,
            'tool_quota_status' => $toolQuotaStatus,
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
        $hasActivePrevious = $currentExp && $currentExp->isFuture();
        $baseExp = $hasActivePrevious ? $currentExp : $now;
        $newExp = $baseExp->copy()->addDays($days);

        // Bersihkan sisa kuota lama yang sudah hangus agar tidak hidup kembali saat membeli paket baru
        if (!$hasActivePrevious) {
            $user->project_quota_limit = (int) ($user->project_quota_used ?? 0);
            $user->paid_tool_limits = null;
            $user->paid_tool_used = null;
        }

        if (!empty($plan['is_unlimited'])) {
            $user->plan_code = $planCode;
            $user->plan_expires_at = $newExp;
        } else {
            if (!$this->isUnlimitedActive($user)) {
                $user->plan_code = $planCode;
            }
            $user->project_quota_limit = (int) ($user->project_quota_limit ?? 0) + (int) ($plan['quota_projects'] ?? 1);
            $user->plan_expires_at = $newExp;

            // Berikan kuota fitur AI (Starter = 2x, Hemat = 5x) + akumulasi sisa yang masih aktif
            $addToolQuota = (int) ($plan['tool_quota'] ?? 2);
            $prevStatus = $hasActivePrevious ? $this->getPaidToolStatus($user) : [];
            $newLimits = [];
            $newUsed = [];
            foreach (self::METERED_TOOLS as $tKey) {
                $carryOver = $hasActivePrevious ? (int) ($prevStatus[$tKey]['remaining'] ?? 0) : 0;
                $newLimits[$tKey] = $carryOver + $addToolQuota;
                $newUsed[$tKey] = 0;
            }
            $user->paid_tool_limits = json_encode($newLimits);
            $user->paid_tool_used = json_encode($newUsed);
        }
        $user->save();

        // Khusus paket kuota: otomatis buka 1 naskah Free yang sudah terlanjur dibuat memakai slot kuota yang baru dibeli
        if (empty($plan['is_unlimited'])) {
            $lockedProj = Project::where('user_id', $user->id)
                ->where(function ($q) {
                    $q->where('is_full_unlocked', false)->orWhereNull('is_full_unlocked');
                })
                ->orderBy('updated_at', 'desc')
                ->first();
            if ($lockedProj) {
                $this->tryUnlockProjectWithPaidQuota($user, $lockedProj);
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
