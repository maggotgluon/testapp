-- ============================================================
-- TicketFlow Full Database Schema (MySQL)
-- Generated from all migration files + current model state
-- ============================================================

CREATE TABLE IF NOT EXISTS `users` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `username` VARCHAR(255) NULL UNIQUE AFTER `name`,
    `email` VARCHAR(255) NULL UNIQUE,
    `phone` VARCHAR(255) NULL INDEX,
    `role` VARCHAR(255) NOT NULL DEFAULT 'customer' INDEX,
    `provider` VARCHAR(255) NULL,
    `provider_id` VARCHAR(255) NULL,
    `avatar` VARCHAR(255) NULL,
    `line_friend_status` VARCHAR(255) NULL AFTER `avatar`,
    `line_followed_at` TIMESTAMP NULL AFTER `line_friend_status`,
    `line_blocked_at` TIMESTAMP NULL AFTER `line_followed_at`,
    `email_verified_at` TIMESTAMP NULL,
    `password` VARCHAR(255) NULL,
    `remember_token` VARCHAR(100) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    UNIQUE INDEX `users_provider_unique` (`provider`, `provider_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
    `email` VARCHAR(255) PRIMARY KEY,
    `token` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sessions` (
    `id` VARCHAR(255) PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NULL INDEX,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `payload` LONGTEXT NOT NULL,
    `last_activity` INT NOT NULL INDEX
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cache` (
    `key` VARCHAR(255) PRIMARY KEY,
    `value` MEDIUMTEXT NOT NULL,
    `expiration` BIGINT NOT NULL INDEX
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cache_locks` (
    `key` VARCHAR(255) PRIMARY KEY,
    `owner` VARCHAR(255) NOT NULL,
    `expiration` BIGINT NOT NULL INDEX
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `jobs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `queue` VARCHAR(255) NOT NULL INDEX,
    `payload` LONGTEXT NOT NULL,
    `attempts` SMALLINT UNSIGNED NOT NULL,
    `reserved_at` INT UNSIGNED NULL,
    `available_at` INT UNSIGNED NOT NULL,
    `created_at` INT UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `job_batches` (
    `id` VARCHAR(255) PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `total_jobs` INT NOT NULL,
    `pending_jobs` INT NOT NULL,
    `failed_jobs` INT NOT NULL,
    `failed_job_ids` LONGTEXT NOT NULL,
    `options` MEDIUMTEXT NULL,
    `cancelled_at` INT NULL,
    `created_at` INT NOT NULL,
    `finished_at` INT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `failed_jobs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uuid` VARCHAR(255) NOT NULL UNIQUE,
    `connection` VARCHAR(255) NOT NULL,
    `queue` VARCHAR(255) NOT NULL,
    `payload` LONGTEXT NOT NULL,
    `exception` LONGTEXT NOT NULL,
    `failed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `failed_jobs_connection_queue_failed_at` (`connection`, `queue`, `failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `events` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `created_by` BIGINT UNSIGNED NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `description_format` VARCHAR(255) NOT NULL DEFAULT 'html' AFTER `description`,
    `social_description` TEXT NULL AFTER `description`,
    `venue` VARCHAR(255) NOT NULL,
    `location` VARCHAR(255) NULL,
    `location_url` VARCHAR(255) NULL AFTER `location`,
    `hosted_by` VARCHAR(255) NULL,
    `hosted_by_url` VARCHAR(255) NULL AFTER `hosted_by`,
    `starts_at` DATETIME NOT NULL,
    `ends_at` DATETIME NOT NULL,
    `poster_path` VARCHAR(255) NULL,
    `ticket_image_path` VARCHAR(255) NULL AFTER `poster_path`,
    `social_image_path` VARCHAR(255) NULL AFTER `ticket_image_path`,
    `bank_name` VARCHAR(255) NULL AFTER `ticket_image_path`,
    `bank_account_name` VARCHAR(255) NULL AFTER `bank_name`,
    `bank_account_number` VARCHAR(255) NULL AFTER `bank_account_name`,
    `qr_payment_account_name` VARCHAR(255) NULL AFTER `bank_account_number`,
    `qr_payment_account` VARCHAR(255) NULL AFTER `qr_payment_account_name`,
    `qr_payment_image_path` VARCHAR(255) NULL AFTER `qr_payment_account`,
    `payment_instructions` TEXT NULL AFTER `qr_payment_image_path`,
    `payment_methods` JSON NULL AFTER `payment_instructions`,
    `payment_accounts` JSON NULL AFTER `payment_methods`,
    `beam_enabled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `payment_accounts`,
    `beam_fee_behavior` VARCHAR(20) NOT NULL DEFAULT 'merchant_absorb' AFTER `beam_enabled`,
    `beam_fee_percent` DECIMAL(5,2) NULL AFTER `beam_fee_behavior`,
    `is_published` TINYINT(1) NOT NULL DEFAULT 1,
    `show_countdown` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_published`,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    CONSTRAINT `events_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `event_user` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `event_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    UNIQUE INDEX `event_user_event_id_user_id_unique` (`event_id`, `user_id`),
    CONSTRAINT `event_user_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
    CONSTRAINT `event_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ticket_types` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `event_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `price_thb` INT UNSIGNED NOT NULL,
    `full_price_thb` INT UNSIGNED NULL AFTER `price_thb`,
    `capacity` INT UNSIGNED NOT NULL DEFAULT 0,
    `sold_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `sale_starts_at` DATETIME NULL,
    `sale_ends_at` DATETIME NULL,
    `status` VARCHAR(255) NOT NULL DEFAULT 'active' INDEX,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    CONSTRAINT `ticket_types_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `coupons` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `event_id` BIGINT UNSIGNED NULL INDEX,
    `ticket_type_id` BIGINT UNSIGNED NULL AFTER `event_id`,
    `name` VARCHAR(255) NULL AFTER `event_id`,
    `code` VARCHAR(255) NOT NULL UNIQUE,
    `discount_type` VARCHAR(255) NOT NULL DEFAULT 'fixed',
    `discount_scope` VARCHAR(255) NOT NULL DEFAULT 'order' AFTER `discount_type`,
    `discount_value` INT UNSIGNED NOT NULL,
    `usage_limit` INT UNSIGNED NULL,
    `used_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `starts_at` DATETIME NULL,
    `expires_at` DATETIME NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `show_on_checkout` TINYINT(1) NOT NULL DEFAULT 1 AFTER `is_active`,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    CONSTRAINT `coupons_ticket_type_id_foreign` FOREIGN KEY (`ticket_type_id`) REFERENCES `ticket_types`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `promotions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `event_id` BIGINT UNSIGNED NULL,
    `ticket_type_id` BIGINT UNSIGNED NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `promotion_type` VARCHAR(255) NOT NULL DEFAULT 'buy_x_get_y',
    `discount_scope` VARCHAR(255) NOT NULL DEFAULT 'order',
    `buy_quantity` INT UNSIGNED NULL,
    `get_quantity` INT UNSIGNED NULL,
    `min_quantity` INT UNSIGNED NULL,
    `discount_value` INT UNSIGNED NULL,
    `max_discount_thb` INT UNSIGNED NULL,
    `usage_limit` INT UNSIGNED NULL,
    `used_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `starts_at` DATETIME NULL,
    `expires_at` DATETIME NULL,
    `combines_with_coupons` TINYINT(1) NOT NULL DEFAULT 1,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `show_on_event_page` TINYINT(1) NOT NULL DEFAULT 1 AFTER `is_active`,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    CONSTRAINT `promotions_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE SET NULL,
    CONSTRAINT `promotions_ticket_type_id_foreign` FOREIGN KEY (`ticket_type_id`) REFERENCES `ticket_types`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ticket_orders` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_number` VARCHAR(255) NOT NULL UNIQUE,
    `user_id` BIGINT UNSIGNED NULL,
    `coupon_id` BIGINT UNSIGNED NULL,
    `customer_name` VARCHAR(255) NOT NULL,
    `customer_phone` VARCHAR(255) NOT NULL INDEX,
    `customer_email` VARCHAR(255) NULL,
    `status` VARCHAR(255) NOT NULL DEFAULT 'pending' INDEX,
    `subtotal_thb` INT UNSIGNED NOT NULL DEFAULT 0,
    `discount_thb` INT UNSIGNED NOT NULL DEFAULT 0,
    `beam_fee_thb` INT NULL AFTER `discount_thb`,
    `total_thb` INT UNSIGNED NOT NULL DEFAULT 0,
    `payment_method` VARCHAR(255) NOT NULL DEFAULT 'bank_transfer',
    `beam_charge_id` VARCHAR(80) NULL AFTER `payment_method`,
    `payment_note` TEXT NULL,
    `payment_slip_path` VARCHAR(255) NULL,
    `approved_at` TIMESTAMP NULL,
    `approved_by` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    CONSTRAINT `ticket_orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    CONSTRAINT `ticket_orders_coupon_id_foreign` FOREIGN KEY (`coupon_id`) REFERENCES `coupons`(`id`) ON DELETE SET NULL,
    CONSTRAINT `ticket_orders_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_items` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ticket_order_id` BIGINT UNSIGNED NOT NULL,
    `event_id` BIGINT UNSIGNED NOT NULL,
    `ticket_type_id` BIGINT UNSIGNED NOT NULL,
    `quantity` INT UNSIGNED NOT NULL,
    `unit_price_thb` INT UNSIGNED NOT NULL,
    `line_total_thb` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    CONSTRAINT `order_items_ticket_order_id_foreign` FOREIGN KEY (`ticket_order_id`) REFERENCES `ticket_orders`(`id`) ON DELETE CASCADE,
    CONSTRAINT `order_items_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
    CONSTRAINT `order_items_ticket_type_id_foreign` FOREIGN KEY (`ticket_type_id`) REFERENCES `ticket_types`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tickets` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uuid` CHAR(36) NOT NULL UNIQUE,
    `ticket_order_id` BIGINT UNSIGNED NOT NULL,
    `order_item_id` BIGINT UNSIGNED NOT NULL,
    `event_id` BIGINT UNSIGNED NOT NULL,
    `ticket_type_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NULL,
    `holder_name` VARCHAR(255) NOT NULL,
    `holder_phone` VARCHAR(255) NULL,
    `status` VARCHAR(255) NOT NULL DEFAULT 'pending' INDEX,
    `checked_in_at` TIMESTAMP NULL,
    `checked_out_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    CONSTRAINT `tickets_ticket_order_id_foreign` FOREIGN KEY (`ticket_order_id`) REFERENCES `ticket_orders`(`id`) ON DELETE CASCADE,
    CONSTRAINT `tickets_order_item_id_foreign` FOREIGN KEY (`order_item_id`) REFERENCES `order_items`(`id`) ON DELETE CASCADE,
    CONSTRAINT `tickets_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
    CONSTRAINT `tickets_ticket_type_id_foreign` FOREIGN KEY (`ticket_type_id`) REFERENCES `ticket_types`(`id`) ON DELETE CASCADE,
    CONSTRAINT `tickets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `check_in_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ticket_id` BIGINT UNSIGNED NOT NULL,
    `scanned_by` BIGINT UNSIGNED NULL,
    `action` VARCHAR(255) NOT NULL,
    `gate` VARCHAR(255) NULL,
    `note` TEXT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    CONSTRAINT `check_in_logs_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE,
    CONSTRAINT `check_in_logs_scanned_by_foreign` FOREIGN KEY (`scanned_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payments` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ticket_order_id` BIGINT UNSIGNED NOT NULL,
    `method` VARCHAR(255) NOT NULL DEFAULT 'bank_transfer',
    `payment_account_key` VARCHAR(255) NULL AFTER `method`,
    `payment_account_label` VARCHAR(255) NULL AFTER `payment_account_key`,
    `payment_account_name` VARCHAR(255) NULL AFTER `payment_account_label`,
    `payment_account_number` VARCHAR(255) NULL AFTER `payment_account_name`,
    `amount_thb` INT UNSIGNED NOT NULL,
    `expected_amount_thb` DECIMAL(10,2) NULL AFTER `amount_thb`,
    `expected_promptpay_id` VARCHAR(255) NULL AFTER `expected_amount_thb`,
    `status` VARCHAR(255) NOT NULL DEFAULT 'submitted',
    `slip_path` VARCHAR(255) NULL,
    `slip_archived_path` VARCHAR(255) NULL AFTER `slip_path`,
    `slip_archived_at` TIMESTAMP NULL AFTER `slip_archived_path`,
    `slip_deleted_at` TIMESTAMP NULL AFTER `slip_archived_at`,
    `slip_image_sha256` VARCHAR(64) NULL AFTER `slip_path` INDEX,
    `note` TEXT NULL,
    `beam_charge_id` VARCHAR(80) NULL AFTER `note`,
    `beam_qr_image` TEXT NULL AFTER `beam_charge_id`,
    `slip_qr_status` VARCHAR(255) NULL AFTER `note`,
    `slip_qr_payload` TEXT NULL AFTER `slip_qr_status`,
    `slip_qr_payload_sha256` VARCHAR(64) NULL AFTER `slip_qr_payload` INDEX,
    `slip_qr_data` JSON NULL AFTER `slip_qr_payload_sha256`,
    `slip_qr_amount_thb` DECIMAL(10,2) NULL AFTER `slip_qr_data`,
    `slip_qr_paid_at` TIMESTAMP NULL AFTER `slip_qr_amount_thb`,
    `slip_qr_reference` VARCHAR(255) NULL AFTER `slip_qr_paid_at`,
    `slip_qr_reference_normalized` VARCHAR(255) NULL AFTER `slip_qr_reference` INDEX,
    `slip_qr_receiver` VARCHAR(255) NULL AFTER `slip_qr_reference_normalized`,
    `slip_review_status` VARCHAR(255) NULL AFTER `slip_qr_receiver` INDEX,
    `slip_review_flags` JSON NULL AFTER `slip_review_status`,
    `slip_reviewed_at` TIMESTAMP NULL AFTER `slip_review_flags`,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    CONSTRAINT `payments_ticket_order_id_foreign` FOREIGN KEY (`ticket_order_id`) REFERENCES `ticket_orders`(`id`) ON DELETE CASCADE,
    INDEX `payments_slip_image_sha256_index` (`slip_image_sha256`),
    INDEX `payments_slip_qr_payload_sha256_index` (`slip_qr_payload_sha256`),
    INDEX `payments_slip_qr_reference_normalized_index` (`slip_qr_reference_normalized`),
    INDEX `payments_slip_review_status_index` (`slip_review_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `surveys` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `event_id` BIGINT UNSIGNED NULL,
    `created_by` BIGINT UNSIGNED NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `placement` VARCHAR(255) NOT NULL DEFAULT 'before_event_view',
    `questions` JSON NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `starts_at` TIMESTAMP NULL,
    `ends_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    INDEX `surveys_event_id_placement_is_active_index` (`event_id`, `placement`, `is_active`),
    CONSTRAINT `surveys_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE SET NULL,
    CONSTRAINT `surveys_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `survey_responses` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `survey_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NULL,
    `session_id` VARCHAR(255) NULL,
    `status` VARCHAR(255) NOT NULL DEFAULT 'draft',
    `answers` JSON NULL,
    `meta` JSON NULL,
    `started_at` TIMESTAMP NULL,
    `completed_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    INDEX `survey_responses_survey_id_user_id_status_index` (`survey_id`, `user_id`, `status`),
    INDEX `survey_responses_survey_id_session_id_status_index` (`survey_id`, `session_id`, `status`),
    CONSTRAINT `survey_responses_survey_id_foreign` FOREIGN KEY (`survey_id`) REFERENCES `surveys`(`id`) ON DELETE CASCADE,
    CONSTRAINT `survey_responses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `push_subscriptions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `subscribable_type` VARCHAR(255) NOT NULL,
    `subscribable_id` BIGINT UNSIGNED NOT NULL,
    `endpoint` VARCHAR(500) NOT NULL UNIQUE,
    `public_key` VARCHAR(255) NULL,
    `auth_token` VARCHAR(255) NULL,
    `content_encoding` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    INDEX `push_subscriptions_subscribable_morph_idx` (`subscribable_type`, `subscribable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
