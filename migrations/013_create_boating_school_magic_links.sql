-- =====================================================================
-- Migration: 013_create_boating_school_magic_links.sql
-- Modul: Bootsfahrschule (boating_school) - Schüler Magic-Link ("Meine Termine")
-- =====================================================================

CREATE TABLE IF NOT EXISTS `bs_customer_magic_links` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `customer_id`  INT UNSIGNED NOT NULL,
    `token_hash`   CHAR(64) NOT NULL,
    `purpose`      VARCHAR(32) NOT NULL DEFAULT 'appointments_view',
    `expires_at`   DATETIME NOT NULL,
    `last_used_at` DATETIME NULL,
    `created_at`   DATETIME NOT NULL,
    `created_ip`   VARCHAR(45) NULL,
    CONSTRAINT `fk_bs_magic_customer` FOREIGN KEY (`customer_id`)
        REFERENCES `bs_customers` (`id`) ON DELETE CASCADE,
    INDEX `idx_bs_magic_token` (`token_hash`),
    INDEX `idx_bs_magic_customer` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
