-- =====================================================================
-- Migration: 010_create_boating_school_crm_subscriptions.sql
-- Modul: Bootsfahrschule (boating_school) - CRM & Subscriptions (Phase 3/4)
-- =====================================================================
-- Hinweis: Idempotente DDL-Befehle für MySQL 8.
-- Erweitert bs_customers um CRM- & nautische Felder,
-- fügt bs_subscriptions und bs_subscription_usages hinzu,
-- sowie die subscription_id-Verknüpfung in bs_appointments.
-- =====================================================================

-- 1. Tabelle: bs_customers um CRM- & Nautik-Felder erweitern
ALTER TABLE `bs_customers`
    ADD COLUMN IF NOT EXISTS `licenses_held` VARCHAR(255) NULL AFTER `target_license`,
    ADD COLUMN IF NOT EXISTS `license_number` VARCHAR(128) NULL AFTER `licenses_held`,
    ADD COLUMN IF NOT EXISTS `experience_level` VARCHAR(64) NULL DEFAULT 'beginner' AFTER `license_number`,
    ADD COLUMN IF NOT EXISTS `company` VARCHAR(191) NULL AFTER `last_name`,
    ADD COLUMN IF NOT EXISTS `preferred_contact` VARCHAR(32) NULL DEFAULT 'email' AFTER `phone`,
    ADD COLUMN IF NOT EXISTS `lead_source` VARCHAR(64) NULL AFTER `preferred_contact`,
    ADD COLUMN IF NOT EXISTS `crm_stage` VARCHAR(32) NOT NULL DEFAULT 'student' AFTER `lead_source`,
    ADD COLUMN IF NOT EXISTS `tags` VARCHAR(255) NULL AFTER `crm_stage`,
    ADD COLUMN IF NOT EXISTS `internal_notes` TEXT NULL AFTER `notes`;

-- Indizes auf CRM-Felder für schnelle Filterung
ALTER TABLE `bs_customers`
    ADD INDEX IF NOT EXISTS `idx_bs_cust_crm_stage` (`crm_stage`),
    ADD INDEX IF NOT EXISTS `idx_bs_cust_company` (`company`);

-- 2. Tabelle: bs_subscriptions (Abo- & Punktekarten für Fahrschüler & Mieter)
CREATE TABLE IF NOT EXISTS `bs_subscriptions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `customer_id` INT UNSIGNED NOT NULL,              -- Verknüpfung bs_customers.id
    `service_id` INT UNSIGNED NULL,                   -- Optionale Verknüpfung bs_services.id
    `title` VARCHAR(255) NOT NULL,                    -- z.B. „10er-Abo Fahrstunden Kat. A“, „5er-Karte Motorboot“
    `code` VARCHAR(64) NULL,                          -- Kartennummer / Gutschein-Code z.B. „ABO-2026-0001“
    `type` ENUM('units', 'hours', 'unlimited') NOT NULL DEFAULT 'units', -- Verbrauchsmodus
    `total_units` INT NOT NULL DEFAULT 10,            -- Gesamt-Guthaben an Einheiten / Stunden
    `used_units` INT NOT NULL DEFAULT 0,              -- Bereits entwertete / verbrauchte Einheiten
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,      -- Verkaufspreis der Karte
    `valid_from` DATE NULL,                           -- Beginn der Gültigkeit
    `valid_until` DATE NULL,                          -- Ablaufdatum
    `status` ENUM('active', 'depleted', 'expired', 'cancelled') NOT NULL DEFAULT 'active',
    `notes` TEXT NULL,                                -- Interne Vermerke zum Abonnement
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_bs_sub_customer` (`customer_id`),
    INDEX `idx_bs_sub_service` (`service_id`),
    INDEX `idx_bs_sub_status` (`status`),
    INDEX `idx_bs_sub_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabelle: bs_subscription_usages (Verbrauchsjournal / Entwertungen von Abo-Einheiten)
CREATE TABLE IF NOT EXISTS `bs_subscription_usages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `subscription_id` INT UNSIGNED NOT NULL,          -- Verknüpfung bs_subscriptions.id
    `appointment_id` INT UNSIGNED NULL,               -- Optionale Verknüpfung bs_appointments.id
    `units` INT NOT NULL DEFAULT 1,                   -- Abgebuchte Einheiten (z.B. 1 oder 2)
    `used_date` DATE NOT NULL,                        -- Datum der Entwertung
    `note` VARCHAR(255) NULL,                         -- Bemerkung (z.B. „Fahrstunde Hafenmanöver“)
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_bs_sub_usage_sub` (`subscription_id`),
    INDEX `idx_bs_sub_usage_app` (`appointment_id`),
    INDEX `idx_bs_sub_usage_date` (`used_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabelle: bs_appointments um subscription_id erweitern
ALTER TABLE `bs_appointments`
    ADD COLUMN IF NOT EXISTS `subscription_id` INT UNSIGNED NULL AFTER `price`,
    ADD INDEX IF NOT EXISTS `idx_bs_app_sub` (`subscription_id`);
