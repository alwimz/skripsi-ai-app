-- ============================================================================
-- SkripsiLab AI - MySQL / MariaDB Schema for cPanel (phpMyAdmin Import)
-- Cocok untuk MySQL 5.7+ / 8.0+ & MariaDB 10.3+
-- ============================================================================

SET NAMES utf8mb4;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- 1. Tabel Laravel Core (users, password_reset_tokens, sessions, cache, jobs)
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(191) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `credits` int NOT NULL DEFAULT 3,
  `plan_code` varchar(32) NOT NULL DEFAULT 'free',
  `project_quota_limit` int NOT NULL DEFAULT 0,
  `project_quota_used` int NOT NULL DEFAULT 0,
  `free_project_used` int NOT NULL DEFAULT 0,
  `plan_expires_at` timestamp NULL DEFAULT NULL,
  `free_tool_usage` text,
  `paid_tool_limits` text,
  `paid_tool_used` text,
  `referral_code` varchar(32) DEFAULT NULL,
  `referred_by_user_id` bigint unsigned DEFAULT NULL,
  `referral_balance` int NOT NULL DEFAULT 0,
  `canonical_email` varchar(191) DEFAULT NULL,
  `signup_ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_referral_code_index` (`referral_code`),
  KEY `users_referred_by_user_id_index` (`referred_by_user_id`),
  KEY `users_canonical_email_index` (`canonical_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email` varchar(191) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(191) NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `payload` longtext NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cache` (
  `key` varchar(191) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key` varchar(191) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(191) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `job_batches` (
  `id` varchar(191) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(191) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2. Tabel Aplikasi SkripsiLab AI (projects, api_keys, saved_ppts)
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `projects` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `judul` varchar(255) NOT NULL,
  `prodi` varchar(255) DEFAULT NULL,
  `metode_penelitian` varchar(255) DEFAULT NULL,
  `variabel_penelitian` text,
  `sumber_data` varchar(255) DEFAULT NULL,
  `session_id` varchar(191) DEFAULT NULL,
  `bab1_content` longtext,
  `bab2_content` longtext,
  `bab3_content` longtext,
  `bab4_content` longtext,
  `bab5_content` longtext,
  `daftar_pustaka` longtext,
  `citation_validation` longtext,
  `bab2_tabel_penelitian` longtext,
  `status` enum('draft','processing','completed') NOT NULL DEFAULT 'draft',
  `is_full_unlocked` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `projects_user_id_index` (`user_id`),
  KEY `projects_session_id_index` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `api_keys` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `provider` varchar(32) NOT NULL DEFAULT 'gemini',
  `key` varchar(191) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `usage_count` bigint unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `api_keys_user_id_foreign` (`user_id`),
  CONSTRAINT `api_keys_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `saved_ppts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `project_id` bigint unsigned DEFAULT NULL,
  `title` varchar(500) NOT NULL,
  `theme` varchar(64) NOT NULL DEFAULT 'royal_academic',
  `slide_count` smallint unsigned NOT NULL DEFAULT 8,
  `slides_json` longtext NOT NULL,
  `cover_meta_json` longtext,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `saved_ppts_user_id_index` (`user_id`),
  KEY `saved_ppts_project_id_index` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. Tabel Billing, Transaksi & Referral
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `order_id` varchar(64) NOT NULL,
  `plan_code` varchar(32) NOT NULL,
  `plan_name` varchar(120) NOT NULL,
  `amount` int unsigned NOT NULL,
  `quota_granted` int unsigned NOT NULL DEFAULT 0,
  `duration_days` int unsigned NOT NULL DEFAULT 30,
  `is_unlimited` tinyint(1) NOT NULL DEFAULT 0,
  `payment_method` varchar(32) NOT NULL DEFAULT 'qris',
  `payment_number` text,
  `checkout_url` varchar(500) DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'pending',
  `expired_at` timestamp NULL DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `raw_response` longtext,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transactions_order_id_unique` (`order_id`),
  KEY `transactions_user_id_index` (`user_id`),
  KEY `transactions_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `referral_commissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `referrer_id` bigint unsigned NOT NULL,
  `referred_user_id` bigint unsigned NOT NULL,
  `transaction_id` bigint unsigned NOT NULL,
  `plan_code` varchar(32) NOT NULL,
  `transaction_amount` int unsigned NOT NULL,
  `commission_amount` int unsigned NOT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'approved',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `referral_commissions_transaction_id_unique` (`transaction_id`),
  KEY `referral_commissions_referrer_id_index` (`referrer_id`),
  KEY `referral_commissions_referred_user_id_index` (`referred_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `referral_withdrawals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `amount` int unsigned NOT NULL,
  `channel` varchar(40) NOT NULL,
  `account_number` varchar(80) NOT NULL,
  `account_name` varchar(120) NOT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `referral_withdrawals_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 4. Riwayat Migrasi Laravel
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(191) NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2025_12_14_022934_create_projects_table', 1),
(5, '2025_12_14_123054_create_api_keys_table', 1),
(6, '2025_12_14_125028_add_credits_to_users_table', 1),
(7, '2025_12_14_130214_add_user_id_to_projects_table', 1),
(8, '2025_12_14_130451_...', 1),
(9, '2025_12_14_140743_add_parameters_to_projects_table', 1),
(10, '2025_12_15_075435_add_daftar_pustaka_to_projects', 1),
(11, '2025_12_15_175548_create_api_keys_table', 1),
(12, '2025_12_16_091253_add_citation_validation_to_projects', 1),
(13, '2025_12_16_135122_add_bab2_tabel_penelitian_to_projects', 1),
(14, '2026_11_24_000001_add_billing_quota_referral_tables', 1)
ON DUPLICATE KEY UPDATE `batch` = VALUES(`batch`);

SET FOREIGN_KEY_CHECKS = 1;
