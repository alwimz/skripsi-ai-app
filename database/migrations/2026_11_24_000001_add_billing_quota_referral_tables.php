<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
                    $table->string('referral_code', 32)->nullable()->index();
                }
                if (!Schema::hasColumn('users', 'referred_by_user_id')) {
                    $table->unsignedBigInteger('referred_by_user_id')->nullable()->index();
                }
                if (!Schema::hasColumn('users', 'referral_balance')) {
                    $table->integer('referral_balance')->default(0);
                }
                if (!Schema::hasColumn('users', 'canonical_email')) {
                    $table->string('canonical_email')->nullable()->index();
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
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_withdrawals');
        Schema::dropIfExists('referral_commissions');
        Schema::dropIfExists('transactions');
    }
};
