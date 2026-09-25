-- =====================================================================
-- Migration: 007_create_secure_portal_schema.sql
-- Modul: Sicherungsportal (Workflow Polizei / Staatsanwaltschaft)
-- =====================================================================

-- 1. Tabelle: secure_cases (Vorgänge / Tickets für Sicherungsanträge)
CREATE TABLE IF NOT EXISTS `secure_cases` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `case_number` VARCHAR(32) NOT NULL UNIQUE,         -- z.B. POL-2026-000123
    `access_code` CHAR(12) NOT NULL,                   -- geheimer Code für Fallzugang (z.B. zufällige A-Z0-9)
    `status` VARCHAR(32) NOT NULL DEFAULT 'new',       -- new, in_review, in_progress, available, archived, closed, clarification_required
    `police_department` VARCHAR(255) NOT NULL,         -- Dienststelle
    `contact_name` VARCHAR(255) NOT NULL,              -- Name/Funktion
    `contact_email` VARCHAR(255) NOT NULL,
    `contact_phone` VARCHAR(64) NOT NULL,
    `reference_number` VARCHAR(128) NOT NULL,          -- Aktenzeichen/Fallnummer
    `description` TEXT NOT NULL,                       -- Beschreibung des Sicherungsumfangs
    `desired_date` DATE NULL,                          -- gewünschter Sicherungstermin
    `remarks` TEXT NULL,
    `warrant_file_path` VARCHAR(512) NOT NULL,         -- Editionsverfügung (PDF), sicher gespeichert
    `warrant_mime` VARCHAR(128) NOT NULL,
    `warrant_file_size` INT UNSIGNED NOT NULL,
    `warrant_uploaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    `securing_type` VARCHAR(64) NOT NULL,             -- Typ der Sicherung (z.B. VIDEO, MAIL, CLOUD, etc.)
    `securing_meta` JSON NULL,                        -- typabhängige Felder (Schritt 3 des Formulars)

    `assigned_user_id` INT UNSIGNED NULL,              -- Zuständiger Bearbeiter/Techniker
    `internal_notes` TEXT NULL,                        -- Interne Notizen der Verwaltung/Technik
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_sc_case_num` (`case_number`),
    INDEX `idx_sc_status` (`status`),
    INDEX `idx_sc_dept` (`police_department`),
    INDEX `idx_sc_ref` (`reference_number`),
    INDEX `idx_sc_type` (`securing_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabelle: secure_case_logs (Aktivitäts- und Statusverlauf)
CREATE TABLE IF NOT EXISTS `secure_case_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `case_id` INT UNSIGNED NOT NULL,
    `action` VARCHAR(64) NOT NULL,
    `message` TEXT NOT NULL,
    `author` VARCHAR(255) NOT NULL DEFAULT 'System',
    `is_internal` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_scl_case` (`case_id`),
    CONSTRAINT `fk_scl_case` FOREIGN KEY (`case_id`) REFERENCES `secure_cases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Berechtigungen (Permissions) für das Sicherungsportal
INSERT IGNORE INTO `permissions` (`key`, `name`, `description`, `created_at`) VALUES
('secure_portal.cases.view', 'Sicherungsvorgänge ansehen', 'Einsicht in alle polizeilichen Sicherungsanträge', NOW()),
('secure_portal.cases.manage', 'Sicherungsvorgänge verwalten', 'Bearbeiten von Status, Zuweisung und Notizen', NOW());

-- 4. Rollen anlegen, falls noch nicht vorhanden
INSERT IGNORE INTO `roles` (`key`, `name`, `description`, `created_at`) VALUES
('technician', 'Techniker', 'Zuständig für technische Bereitstellung und Sicherungsvorgänge', NOW()),
('administration', 'Verwaltung', 'Verwaltung von Sicherungsanträgen und Behördenkorrespondenz', NOW());

-- 5. Berechtigungen den Rollen zuweisen
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`id`, p.`id` FROM `roles` r, `permissions` p
WHERE r.`key` IN ('admin', 'superadmin', 'technician', 'administration')
  AND p.`key` IN ('secure_portal.cases.view', 'secure_portal.cases.manage');
