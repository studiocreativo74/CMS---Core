-- =====================================================================
-- Migration: 014_create_coach_management_schema.sql
-- Modul: Bootsfahrschule (boating_school) - Coach & Fahrlehrer-Management
-- Portierung des HR-, Verfügbarkeits- und Zeiterfassungs-Moduls
-- =====================================================================

-- 1. bs_instructors behutsam um zusätzliche Coach-Felder erweitern (idempotent)
ALTER TABLE `bs_instructors`
    ADD COLUMN IF NOT EXISTS `title` VARCHAR(100) NOT NULL DEFAULT 'Fahrcoach' AFTER `last_name`,
    ADD COLUMN IF NOT EXISTS `description` TEXT NULL AFTER `qualifications`,
    ADD COLUMN IF NOT EXISTS `image_url` VARCHAR(255) NULL AFTER `image_path`,
    ADD COLUMN IF NOT EXISTS `public_mobile` VARCHAR(50) NULL AFTER `phone`,
    ADD COLUMN IF NOT EXISTS `is_mobile_public` TINYINT(1) NOT NULL DEFAULT 0 AFTER `public_mobile`;

-- 2. bs_instructor_details (HR-Daten / sensible Personaldaten)
CREATE TABLE IF NOT EXISTS `bs_instructor_details` (
    `instructor_id` INT UNSIGNED NOT NULL PRIMARY KEY,
    `phone`         VARCHAR(50) NULL,
    `email`         VARCHAR(191) NULL,
    `birth_date`    DATE NULL,
    `entry_date`    DATE NULL,
    `ahv_number`    VARCHAR(50) NULL COMMENT 'CH AHV-Nr: 756.xxxx.xxxx.xx',
    `bank_iban`     VARCHAR(50) NULL,
    `bank_name`     VARCHAR(100) NULL,
    `bank_city`     VARCHAR(100) NULL,
    `address`       VARCHAR(255) NULL,
    `hourly_rate`   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `created_at`    DATETIME NOT NULL,
    `updated_at`    DATETIME NOT NULL,
    CONSTRAINT `fk_bs_inst_details` FOREIGN KEY (`instructor_id`)
        REFERENCES `bs_instructors`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. bs_coach_availabilities (Verfügbarkeiten & Abwesenheiten / Sperrzeiten)
CREATE TABLE IF NOT EXISTS `bs_coach_availabilities` (
    `id`                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `instructor_id`      INT UNSIGNED NOT NULL,
    `date`               DATE NOT NULL,
    `start_time`         TIME NULL,
    `end_time`           TIME NULL,
    `type`               ENUM('available','unavailable','vacation','sick','military','other') NOT NULL DEFAULT 'unavailable',
    `note`               VARCHAR(255) NULL,
    `is_recurring`       TINYINT(1) NOT NULL DEFAULT 0,
    `recurring_weekday`  TINYINT NULL COMMENT '1=Mo, 2=Di, ..., 7=So',
    `created_at`         DATETIME NOT NULL,
    `updated_at`         DATETIME NOT NULL,
    CONSTRAINT `fk_bs_coach_avail` FOREIGN KEY (`instructor_id`)
        REFERENCES `bs_instructors`(`id`) ON DELETE CASCADE,
    INDEX `idx_coach_avail_inst_date` (`instructor_id`, `date`),
    INDEX `idx_coach_avail_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. bs_coach_working_hours (Zeiterfassung / Timesheets für Schweizer Lohnabrechnung)
CREATE TABLE IF NOT EXISTS `bs_coach_working_hours` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `instructor_id`  INT UNSIGNED NOT NULL,
    `appointment_id` INT UNSIGNED NULL COMMENT 'Verknüpfung zu bs_appointments falls Fahrstunde',
    `date`           DATE NOT NULL,
    `start_time`     TIME NOT NULL,
    `end_time`       TIME NOT NULL,
    `break_minutes`  INT UNSIGNED NOT NULL DEFAULT 0,
    `total_hours`    DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `rate_amount`    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `total_amount`   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `category`       ENUM('lesson','exam','admin','maintenance','other') NOT NULL DEFAULT 'lesson',
    `status`         ENUM('draft','submitted','approved','rejected','exported') NOT NULL DEFAULT 'draft',
    `note`           TEXT NULL,
    `created_at`     DATETIME NOT NULL,
    `updated_at`     DATETIME NOT NULL,
    CONSTRAINT `fk_bs_coach_hours_inst` FOREIGN KEY (`instructor_id`)
        REFERENCES `bs_instructors`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_bs_coach_hours_app` FOREIGN KEY (`appointment_id`)
        REFERENCES `bs_appointments`(`id`) ON DELETE SET NULL,
    INDEX `idx_coach_hours_inst_date` (`instructor_id`, `date`),
    INDEX `idx_coach_hours_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
