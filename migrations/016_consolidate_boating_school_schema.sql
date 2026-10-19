-- =====================================================================
-- Migration: 016_consolidate_boating_school_schema.sql
-- Modul: Bootsfahrschule (boating_school) - Konsolidierung & Konsistenz
-- =====================================================================
-- 1. Single Source of Truth für Monatsabrechnung:
--    bs_coach_timesheet_closures anlegen
--    Alte bs_coach_timesheets migrieren und umbenennen in bs_coach_timesheets_legacy
-- 2. bs_instructor_payroll_runs in bs_instructor_payroll_runs_legacy umbenennen
-- 3. Einheitliche Bezeichner: Überall instructor_id
-- 4. location_id INT UNSIGNED NULL (NULL = alle Standorte)
-- 5. Zentrale Schweizer Payroll Settings (bs_payroll_settings)
-- 6. Schüler-Magic-Link Tabelle (bs_customer_magic_links) mit last_used_at
-- =====================================================================

-- 1. Tabelle: bs_coach_timesheet_closures (Single Source of Truth)
CREATE TABLE IF NOT EXISTS `bs_coach_timesheet_closures` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `instructor_id`    INT UNSIGNED NOT NULL,
    `year_month`       VARCHAR(7) NOT NULL COMMENT 'Format YYYY-MM',
    `status`           ENUM('draft','submitted','approved','rejected','reopened','paid') NOT NULL DEFAULT 'draft',
    `pdf_version`      INT UNSIGNED NOT NULL DEFAULT 1,
    `total_hours`      DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    `gross_amount`     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `net_amount`       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `deductions_json`  TEXT NULL COMMENT 'AHV, ALV, NBU, BVG, KTG, Quellensteuer etc.',
    `snapshot_json`    MEDIUMTEXT NULL COMMENT 'Vollständiger Snapshot aller Fahrten & Lohnzeilen',
    `rejection_reason` TEXT NULL COMMENT 'Begründung bei Status rejected',
    `submitted_at`     DATETIME NULL,
    `submitted_by`     INT UNSIGNED NULL,
    `approved_at`      DATETIME NULL,
    `approved_by`      INT UNSIGNED NULL,
    `reopened_at`      DATETIME NULL,
    `reopened_by`      INT UNSIGNED NULL,
    `paid_at`          DATETIME NULL,
    `paid_by`          INT UNSIGNED NULL,
    `created_at`       DATETIME NOT NULL,
    `updated_at`       DATETIME NOT NULL,
    UNIQUE KEY `uniq_closure_inst_month` (`instructor_id`, `year_month`),
    INDEX `idx_closure_status` (`status`),
    INDEX `idx_closure_inst` (`instructor_id`),
    CONSTRAINT `fk_bs_closure_inst` FOREIGN KEY (`instructor_id`)
        REFERENCES `bs_instructors`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datenübernahme aus alter bs_coach_timesheets Tabelle falls vorhanden
SET @has_old_ts = (
    SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bs_coach_timesheets'
);

SET @sql_migrate = IF(@has_old_ts > 0,
    'INSERT IGNORE INTO `bs_coach_timesheet_closures`
     (`instructor_id`, `year_month`, `status`, `pdf_version`, `total_hours`, `gross_amount`, `net_amount`,
      `deductions_json`, `snapshot_json`, `rejection_reason`, `submitted_at`, `submitted_by`,
      `approved_at`, `approved_by`, `reopened_at`, `reopened_by`, `created_at`, `updated_at`)
     SELECT `instructor_id`, `year_month`, `status`, `pdf_version`, `total_hours`, `gross_amount`, `net_amount`,
            `deductions_json`, `snapshot_json`, `rejection_reason`, `submitted_at`, `submitted_by`,
            `approved_at`, `approved_by`, `reopened_at`, `reopened_by`, `created_at`, `updated_at`
     FROM `bs_coach_timesheets`',
    'SELECT 1'
);
PREPARE stmt FROM @sql_migrate;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Umbenennung alter Tabellen in *_legacy
SET @has_legacy_ts = (
    SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bs_coach_timesheets_legacy'
);
SET @sql_rename_ts = IF(@has_old_ts > 0 AND @has_legacy_ts = 0,
    'RENAME TABLE `bs_coach_timesheets` TO `bs_coach_timesheets_legacy`',
    'SELECT 1'
);
PREPARE stmt FROM @sql_rename_ts;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_runs = (
    SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bs_instructor_payroll_runs'
);
SET @has_legacy_runs = (
    SELECT COUNT(*) FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bs_instructor_payroll_runs_legacy'
);
SET @sql_rename_runs = IF(@has_runs > 0 AND @has_legacy_runs = 0,
    'RENAME TABLE `bs_instructor_payroll_runs` TO `bs_instructor_payroll_runs_legacy`',
    'SELECT 1'
);
PREPARE stmt FROM @sql_rename_runs;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. location_id überall als INT UNSIGNED NULL
ALTER TABLE `bs_coach_availabilities`
    MODIFY COLUMN `location_id` INT UNSIGNED NULL;

ALTER TABLE `bs_coach_weekly_templates`
    MODIFY COLUMN `location_id` INT UNSIGNED NULL;

UPDATE `bs_coach_availabilities` SET `location_id` = NULL WHERE `location_id` = 0;
UPDATE `bs_coach_weekly_templates` SET `location_id` = NULL WHERE `location_id` = 0;

-- 4. Berechtigungen registrieren
INSERT IGNORE INTO `permissions` (`key`, `name`, `description`, `created_at`) VALUES
('boating.bookings.view', 'Buchungen ansehen', 'Buchungen und Termine einsehen', NOW()),
('boating.bookings.manage', 'Buchungen verwalten', 'Buchungen erstellen, bearbeiten und stornieren', NOW()),
('boating.calendar.view', 'Kalender ansehen', 'Ausbildungs- und Belegungskalender einsehen', NOW()),
('boating.calendar.manage', 'Kalender verwalten', 'Kalendertermine verwalten und verschieben', NOW()),
('boating.customers.view', 'Kunden ansehen', 'Kunden- und Schülerkartei einsehen', NOW()),
('boating.customers.manage', 'Kunden verwalten', 'Kunden verwalten und anlegen', NOW()),
('boating.subscriptions.manage', 'Abonnemente verwalten', 'Abonnemente und Mehrfachkarten verwalten', NOW()),
('boating.reports.view', 'Berichte ansehen', 'Auswertungen und Berichte einsehen', NOW()),
('boating.payroll.view', 'Payroll einsehen', 'Fahrlehrer-Monatsrapporte und Timesheets einsehen', NOW()),
('boating.payroll.manage', 'Payroll verwalten', 'Payroll und Lohnabrechnungen verwalten und freigeben', NOW()),
('boating.coaches.view', 'Fahrlehrer ansehen', 'Fahrlehrer-Stammdaten einsehen', NOW()),
('boating.coaches.manage', 'Fahrlehrer verwalten', 'Fahrlehrer-Stammdaten verwalten', NOW()),
('boating.coaches.hr_details', 'HR-Daten einsehen/bearbeiten', 'Sensible Personaldaten (AHV-Nr., IBAN) einsehen/bearbeiten', NOW()),
('boating.coach.portal', 'Fahrlehrer-Portal', 'Zugang zum persönlichen Fahrlehrer-Portal', NOW()),
('boating.coach.availability', 'Arbeitszeiten/Absenzen', 'Eigene Arbeitszeiten und Absenzen erfassen', NOW()),
('boating.timesheets.submit', 'Timesheet einreichen', 'Eigenen Monatsrapport/Timesheet einreichen', NOW()),
('boating.timesheets.approve', 'Timesheet freigeben', 'Fahrlehrer-Monatsrapporte prüfen und genehmigen', NOW()),
('boating.settings.manage', 'Bootsfahrschule Einstellungen', 'Bootsfahrschul-Einstellungen verwalten', NOW());
