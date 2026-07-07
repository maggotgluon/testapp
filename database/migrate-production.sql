-- ============================================================
-- Production migration: cc6d1fd -> 24fe4c1
-- Run this on your production MySQL server.
-- ============================================================

-- 1. New tables: surveys & survey_responses
-- ============================================================

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
    `started_at` TIMESTAMP NULL,
    `completed_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    INDEX `survey_responses_survey_id_user_id_status_index` (`survey_id`, `user_id`, `status`),
    INDEX `survey_responses_survey_id_session_id_status_index` (`survey_id`, `session_id`, `status`),
    CONSTRAINT `survey_responses_survey_id_foreign` FOREIGN KEY (`survey_id`) REFERENCES `surveys`(`id`) ON DELETE CASCADE,
    CONSTRAINT `survey_responses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. New columns on existing tables (Beam Checkout)
-- ============================================================

ALTER TABLE `events`
    ADD COLUMN `beam_enabled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `payment_accounts`,
    ADD COLUMN `beam_fee_behavior` VARCHAR(20) NOT NULL DEFAULT 'merchant_absorb' AFTER `beam_enabled`,
    ADD COLUMN `beam_fee_percent` DECIMAL(5,2) NULL AFTER `beam_fee_behavior`;

ALTER TABLE `ticket_orders`
    ADD COLUMN `beam_fee_thb` INT NULL AFTER `discount_thb`,
    ADD COLUMN `beam_charge_id` VARCHAR(80) NULL AFTER `payment_method`;

ALTER TABLE `payments`
    ADD COLUMN `beam_charge_id` VARCHAR(80) NULL AFTER `note`,
    ADD COLUMN `beam_qr_image` TEXT NULL AFTER `beam_charge_id`;

-- 3. Survey response metadata
-- ============================================================

ALTER TABLE `survey_responses`
    ADD COLUMN `meta` JSON NULL AFTER `answers`;
