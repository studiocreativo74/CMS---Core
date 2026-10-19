-- =====================================================================
-- Migration: 008_create_boating_school_schema.sql
-- Modul: Bootsfahrschule (boating_school) - Phase 1: Stammdaten
-- =====================================================================
-- Hinweis: Gemäß Vorgaben nicht automatisch per PHP ausführen,
-- sondern bei Bedarf manuell in phpMyAdmin ausführen.
-- =====================================================================

-- 1. Tabelle: bs_boats (Boote der Fahrschule und Mietflotte)
CREATE TABLE IF NOT EXISTS `bs_boats` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,              -- Anzeigename des Boots
    `registration` VARCHAR(64) NULL,           -- Kennzeichen / Schiffsnummer
    `type` VARCHAR(64) NULL,                   -- Bootstyp (z.B. motor, sail, rib, electric)
    `capacity` INT UNSIGNED NULL,              -- Anzahl Personen
    `engine_power` INT UNSIGNED NULL,          -- PS/kW
    `color` VARCHAR(64) NULL,
    `image_path` VARCHAR(255) NULL,            -- Optionales Bild des Boots (Upload)
    `notes` TEXT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_bs_boats_active` (`is_active`),
    INDEX `idx_bs_boats_type` (`type`),
    INDEX `idx_bs_boats_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabelle: bs_instructors (Fahrlehrer & Instruktoren)
CREATE TABLE IF NOT EXISTS `bs_instructors` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NULL,                -- Optionaler CMS-Benutzerbezug (users.id)
    `first_name` VARCHAR(128) NOT NULL,
    `last_name` VARCHAR(128) NOT NULL,
    `email` VARCHAR(191) NULL,
    `phone` VARCHAR(64) NULL,
    `license_number` VARCHAR(128) NULL,         -- Fahrlehrer-Ausweisnr. / Schiffsführerpatent
    `qualifications` TEXT NULL,                 -- Kategorien (z.B. Kat. A Motor, Kat. D Segel, Hochsee, Funk)
    `color_code` VARCHAR(16) NULL DEFAULT '#0d6efd', -- Farbcodierung für Kalender & Kennzeichnung
    `image_path` VARCHAR(255) NULL,             -- Profilbild des Fahrlehrers (Upload)
    `notes` TEXT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_bs_inst_active` (`is_active`),
    INDEX `idx_bs_inst_user` (`user_id`),
    INDEX `idx_bs_inst_name` (`last_name`, `first_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabelle: bs_services (Dienstleistungen, Kurse, Produkte, Miete)
CREATE TABLE IF NOT EXISTS `bs_services` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,              -- z.B. Praxisfahrstunde Motorboot Kat. A
    `code` VARCHAR(64) NULL,                   -- Artikel- / Kurskürzel (z.B. PRAX-MOT-90)
    `category` VARCHAR(64) NOT NULL DEFAULT 'practical', -- practical, theory, rental, exam, package, other
    `boat_type` VARCHAR(64) NOT NULL DEFAULT 'any',      -- any, motor, sail, rib, electric
    `duration_minutes` INT UNSIGNED NULL DEFAULT 60, -- Dauer in Minuten (z.B. 60, 90, 120)
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,    -- Preis brutto
    `tax_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,  -- MwSt.-Satz in Prozent (z.B. 8.10)
    `description` TEXT NULL,                   -- Leistungsbeschreibung
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_bs_srv_category` (`category`),
    INDEX `idx_bs_srv_boat_type` (`boat_type`),
    INDEX `idx_bs_srv_active` (`is_active`),
    INDEX `idx_bs_srv_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabelle: bs_customers (Schüler & Kunden: Fahrschüler + Mieter)
CREATE TABLE IF NOT EXISTS `bs_customers` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `customer_number` VARCHAR(64) NULL,        -- Kundennummer (z.B. KD-2026-001)
    `type` VARCHAR(32) NOT NULL DEFAULT 'student', -- student (Fahrschüler), renter (Bootsmieter), both (Fahrschüler & Mieter)
    `user_id` INT UNSIGNED NULL,                -- Optional verknüpft mit users.id
    `first_name` VARCHAR(128) NOT NULL,
    `last_name` VARCHAR(128) NOT NULL,
    `email` VARCHAR(191) NULL,
    `phone` VARCHAR(64) NULL,
    `street` VARCHAR(255) NULL,
    `zip` VARCHAR(32) NULL,
    `city` VARCHAR(128) NULL,
    `birthdate` DATE NULL,
    `target_license` VARCHAR(128) NULL,        -- Zielkategorie (z.B. Kat. A Motorboot, Kat. D Segelschiff)
    `theory_passed` TINYINT(1) NOT NULL DEFAULT 0,
    `theory_passed_at` DATE NULL,
    `practical_passed` TINYINT(1) NOT NULL DEFAULT 0,
    `practical_passed_at` DATE NULL,
    `emergency_contact` VARCHAR(255) NULL,     -- Notfallkontakt (Name & Telefon)
    `notes` TEXT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_bs_cust_num` (`customer_number`),
    INDEX `idx_bs_cust_type` (`type`),
    INDEX `idx_bs_cust_email` (`email`),
    INDEX `idx_bs_cust_active` (`is_active`),
    INDEX `idx_bs_cust_name` (`last_name`, `first_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Berechtigungen (Permissions) registrieren
INSERT IGNORE INTO `permissions` (`key`, `name`, `description`, `created_at`) VALUES
('boating_school.view', 'Bootsfahrschule ansehen', 'Einsicht in Stammdaten der Bootsfahrschule (Boote, Fahrlehrer, Leistungen, Kunden)', NOW()),
('boating_school.manage', 'Bootsfahrschule verwalten', 'Erstellen, Bearbeiten und Löschen von Booten, Fahrlehrern, Leistungen und Kunden', NOW());

-- 6. Rolle 'instructor' (Fahrlehrer) anlegen, falls noch nicht vorhanden
INSERT IGNORE INTO `roles` (`key`, `name`, `description`, `created_at`) VALUES
('instructor', 'Fahrlehrer', 'Instruktor der Bootsfahrschule mit Einsicht in Schüler und Flotte', NOW());
