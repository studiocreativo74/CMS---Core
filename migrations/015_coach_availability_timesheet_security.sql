-- =====================================================================
-- Migration: 015_coach_availability_timesheet_security.sql
-- Modul: Bootsfahrschule (boating_school)
-- Ergänzung Coach-Verfügbarkeiten, Buchungsintegration,
-- Timesheet-Versionierung, Schweizer Lohnabzüge & Security/Audit
-- =====================================================================

-- 1. Bezeichner vereinheitlichen: immer instructor_id & location_id (INT UNSIGNED NULL)
-- Prüfen und Spalten in bs_coach_availabilities idempotent erweitern
ALTER TABLE `bs_coach_availabilities`
    ADD COLUMN IF NOT EXISTS `location_id` INT UNSIGNED NULL AFTER `end_time`,
    ADD COLUMN IF NOT EXISTS `is_holiday_active` TINYINT(1) NOT NULL DEFAULT 0 AFTER `type`;

-- Fremdschlüssel für location_id falls Tabelle bs_locations existiert
SET @fk_check = (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'bs_coach_availabilities'
      AND CONSTRAINT_NAME = 'fk_bs_coach_avail_loc'
);
SET @sql = IF(@fk_check = 0,
    'ALTER TABLE `bs_coach_availabilities` ADD CONSTRAINT `fk_bs_coach_avail_loc` FOREIGN KEY (`location_id`) REFERENCES `bs_locations`(`id`) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. bs_coach_weekly_templates (Wochen-Templates für Fahrlehrer-Verfügbarkeiten)
CREATE TABLE IF NOT EXISTS `bs_coach_weekly_templates` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `instructor_id` INT UNSIGNED NOT NULL,
    `weekday`       TINYINT UNSIGNED NOT NULL COMMENT '1=Mo, 2=Di, 3=Mi, 4=Do, 5=Fr, 6=Sa, 7=So',
    `start_time`    TIME NOT NULL,
    `end_time`      TIME NOT NULL,
    `location_id`   INT UNSIGNED NULL COMMENT 'NULL = alle Standorte / standortunabhängig',
    `is_active`     TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`    DATETIME NOT NULL,
    `updated_at`    DATETIME NOT NULL,
    CONSTRAINT `fk_bs_coach_tpl_inst` FOREIGN KEY (`instructor_id`)
        REFERENCES `bs_instructors`(`id`) ON DELETE CASCADE,
    INDEX `idx_coach_tpl_inst_wd` (`instructor_id`, `weekday`, `is_active`),
    INDEX `idx_coach_tpl_loc` (`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Zentrale Lohnabzugssätze (Schweizer Payroll Settings)
CREATE TABLE IF NOT EXISTS `bs_payroll_settings` (
    `setting_key`   VARCHAR(64) NOT NULL PRIMARY KEY,
    `setting_value` VARCHAR(255) NOT NULL,
    `description`   VARCHAR(255) NULL,
    `updated_at`    DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Standardwerte für Schweizer Sozialversicherungs- und Lohnabzüge initial einfügen (idempotent)
INSERT INTO `bs_payroll_settings` (`setting_key`, `setting_value`, `description`, `updated_at`)
VALUES
    ('payroll_ahv_rate', '5.30', 'AHV/IV/EO Beitragssatz Arbeitnehmer in % (Standard 5.30 %)', NOW()),
    ('payroll_alv_rate', '1.10', 'ALV Arbeitslosenversicherung Arbeitnehmer in % (Standard 1.10 %)', NOW()),
    ('payroll_nbu_rate', '1.30', 'NBU Nichtbetriebsunfallversicherung in % (z.B. 1.20 - 1.50 %)', NOW()),
    ('payroll_bvg_rate', '0.00', 'BVG Pensionskasse Arbeitnehmer Standard in % (coach-spezifisch überschreibbar)', NOW()),
    ('payroll_ktg_rate', '1.00', 'KTG Krankentaggeld-Versicherung in % (z.B. 0.80 - 1.20 %)', NOW()),
    ('payroll_withholding_tax', '0.00', 'Quellensteuer Standard in % (Default 0.00 %, für Grenzgänger/B-Bewilligung)', NOW()),
    ('payroll_misc_fixed', '0.00', 'Pauschale Abzüge in CHF (z.B. Spesenabzug / Material)', NOW()),
    ('payroll_notification_email', 'payroll@bootschule.ch', 'E-Mail-Adresse für Benachrichtigungen bei Timesheet-Einreichung', NOW())
ON DUPLICATE KEY UPDATE `updated_at` = `updated_at`;

-- 4. Individuelle Lohnabzugs-Overrides in bs_instructor_details ergänzen
ALTER TABLE `bs_instructor_details`
    ADD COLUMN IF NOT EXISTS `ahv_rate_override` DECIMAL(5,2) NULL COMMENT 'Individueller AHV-Satz in % falls abweichend' AFTER `hourly_rate`,
    ADD COLUMN IF NOT EXISTS `alv_rate_override` DECIMAL(5,2) NULL COMMENT 'Individueller ALV-Satz in %' AFTER `ahv_rate_override`,
    ADD COLUMN IF NOT EXISTS `nbu_rate_override` DECIMAL(5,2) NULL COMMENT 'Individueller NBU-Satz in %' AFTER `alv_rate_override`,
    ADD COLUMN IF NOT EXISTS `bvg_rate_override` DECIMAL(5,2) NULL COMMENT 'Individueller BVG-Satz in %' AFTER `nbu_rate_override`,
    ADD COLUMN IF NOT EXISTS `ktg_rate_override` DECIMAL(5,2) NULL COMMENT 'Individueller KTG-Satz in %' AFTER `bvg_rate_override`,
    ADD COLUMN IF NOT EXISTS `withholding_tax_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Quellensteuer in %' AFTER `ktg_rate_override`,
    ADD COLUMN IF NOT EXISTS `misc_fixed_deduction` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Pauschalabzug monatlich in CHF' AFTER `withholding_tax_rate`;

-- 5. bs_coach_timesheets (Monatsrapporte mit Versionierung, Reopened-Status & Snapshots)
CREATE TABLE IF NOT EXISTS `bs_coach_timesheets` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `instructor_id`    INT UNSIGNED NOT NULL,
    `year_month`       VARCHAR(7) NOT NULL COMMENT 'Format YYYY-MM',
    `status`           ENUM('draft','submitted','approved','rejected','reopened','paid') NOT NULL DEFAULT 'draft',
    `pdf_version`      INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Wird bei erneuter Einreichung nach reopened inkrementiert',
    `total_hours`      DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    `gross_amount`     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `net_amount`       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `deductions_json`  TEXT NULL COMMENT 'Berechnete Abzüge: AHV, ALV, NBU, BVG, KTG, Quellensteuer',
    `snapshot_json`    MEDIUMTEXT NULL COMMENT 'Historische Snapshots aller eingereichten Versionen',
    `rejection_reason` TEXT NULL COMMENT 'Pflichtbegründung bei Statuswechsel zu rejected',
    `submitted_at`     DATETIME NULL,
    `submitted_by`     INT UNSIGNED NULL,
    `approved_at`      DATETIME NULL,
    `approved_by`      INT UNSIGNED NULL,
    `reopened_at`      DATETIME NULL,
    `reopened_by`      INT UNSIGNED NULL,
    `created_at`       DATETIME NOT NULL,
    `updated_at`       DATETIME NOT NULL,
    UNIQUE KEY `uniq_inst_month` (`instructor_id`, `year_month`),
    CONSTRAINT `fk_bs_timesheet_inst` FOREIGN KEY (`instructor_id`)
        REFERENCES `bs_instructors`(`id`) ON DELETE CASCADE,
    INDEX `idx_timesheet_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Audit-Logging für Coach- und Payroll-Aktionen
CREATE TABLE IF NOT EXISTS `bs_audit_logs` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`       INT UNSIGNED NULL,
    `user_role`     VARCHAR(50) NULL,
    `action`        VARCHAR(100) NOT NULL,
    `instructor_id` INT UNSIGNED NULL,
    `reference_id`  VARCHAR(64) NULL,
    `old_status`    VARCHAR(50) NULL,
    `new_status`    VARCHAR(50) NULL,
    `details_json`  TEXT NULL,
    `ip_address`    VARCHAR(45) NULL,
    `created_at`    DATETIME NOT NULL,
    INDEX `idx_bs_audit_act` (`action`),
    INDEX `idx_bs_audit_inst` (`instructor_id`),
    INDEX `idx_bs_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
