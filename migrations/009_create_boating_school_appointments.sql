-- =====================================================================
-- Migration: 009_create_boating_school_appointments.sql
-- Modul: Bootsfahrschule (boating_school) - Phase 2 & 3:
-- Standorte, Termine/Kalender & Kunden-Buchungsportal
-- =====================================================================
-- Hinweis: Gemäß Vorgaben nicht automatisch per PHP ausführen,
-- sondern bei Bedarf manuell in phpMyAdmin ausführen.
-- =====================================================================

-- 1. Tabelle: bs_locations (Orte, Stege, Häfen für Treffpunkt)
CREATE TABLE IF NOT EXISTS `bs_locations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,              -- z.B. „Luzern (LU)“, „Buochs (NW)“
    `code` VARCHAR(64) NULL,                   -- Kurzcode, z.B. „LU“, „NW“
    `address` VARCHAR(255) NULL,               -- Stegadresse / Hafenbecken
    `notes` TEXT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_bs_loc_active` (`is_active`),
    INDEX `idx_bs_loc_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabelle: bs_appointments (Termine, Buchungen, Fahrstunden & Mieten)
CREATE TABLE IF NOT EXISTS `bs_appointments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `date` DATE NOT NULL,                      -- Buchungstag (YYYY-MM-DD)
    `start_time` TIME NOT NULL,                -- Beginn (HH:MM:SS)
    `end_time` TIME NOT NULL,                  -- Ende (HH:MM:SS)
    `service_id` INT UNSIGNED NOT NULL,        -- Verknüpfung bs_services.id
    `customer_id` INT UNSIGNED NOT NULL,       -- Verknüpfung bs_customers.id
    `instructor_id` INT UNSIGNED NULL,         -- Verknüpfung bs_instructors.id (optional für Dispo)
    `boat_id` INT UNSIGNED NULL,               -- Verknüpfung bs_boats.id (optional für Dispo)
    `location_id` INT UNSIGNED NULL,           -- Verknüpfung bs_locations.id
    `status` ENUM('requested', 'confirmed', 'cancelled_customer', 'cancelled_system', 'completed') NOT NULL DEFAULT 'requested',
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00, -- Vereinbarter / kalkulierter Preis
    `notes` TEXT NULL,                         -- Bemerkungen des Kunden oder Fahrlehrers
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_bs_app_date` (`date`),
    INDEX `idx_bs_app_datetime` (`date`, `start_time`, `end_time`),
    INDEX `idx_bs_app_customer` (`customer_id`),
    INDEX `idx_bs_app_instructor` (`instructor_id`),
    INDEX `idx_bs_app_boat` (`boat_id`),
    INDEX `idx_bs_app_location` (`location_id`),
    INDEX `idx_bs_app_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optionale Standard-Standorte (Beispiel)
INSERT IGNORE INTO `bs_locations` (`id`, `name`, `code`, `address`, `notes`, `is_active`) VALUES
(1, 'Luzern - Hafenbecken Alpenquai', 'LU', 'Alpenquai 12, 6005 Luzern', 'Hauptsteg Bootsfahrschule Motorboot & Segel', 1),
(2, 'Buochs - Ennerberg Steganlage', 'NW', 'Seestrasse 48, 6374 Buochs', 'Stützpunkt Vierwaldstättersee Süd', 1);
