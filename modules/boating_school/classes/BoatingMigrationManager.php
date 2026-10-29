<?php

declare(strict_types=1);

/**
 * BoatingMigrationManager
 *
 * Zentraler, idempotenter Migrations- und Update-Manager für das Modul boating_school.
 *
 * Verwaltet:
 * - Schema-Versionierung via Settings (boating_schema_version)
 * - Migration von bs_coach_timesheets nach bs_coach_timesheet_closures (Single Source of Truth)
 * - Sichere Archivierung alter Tabellen als *_legacy (ohne Datenverlust)
 * - Konsistenz aller Fremdschlüssel (immer instructor_id)
 * - Standardisierung von location_id (INT UNSIGNED NULL)
 * - Zentrale Schweizer Payroll-Settings (payroll_*)
 * - Idempotente Ausführung bei jedem Modul-Start
 */
final class BoatingMigrationManager
{
    public const CURRENT_SCHEMA_VERSION = '2.0.0';

    private static bool $migrated = false;

    /**
     * Führt alle anstehenden Migrationen idempotent aus.
     */
    public static function run(): void
    {
        if (self::$migrated || !class_exists('DB')) {
            return;
        }
        self::$migrated = true;

        try {
            // 1. Tabelle bs_coach_timesheet_closures anlegen (Single Source of Truth)
            self::ensureTimesheetClosuresTable();

            // 2. Daten von bs_coach_timesheets migrieren und alte Tabelle umbenennen
            self::migrateCoachTimesheetsToClosures();

            // 3. bs_instructor_payroll_runs als Legacy markieren
            self::handlePayrollRunsLegacy();

            // 4. Konsistenz der Spaltennamen: Überall instructor_id
            self::ensureInstructorIdNaming();

            // 5. Konsistenz location_id (INT UNSIGNED NULL, NULL = alle Standorte)
            self::ensureLocationIdConsistency();

            // 6. Schweizer Payroll-Settings sicherstellen
            self::ensurePayrollSettings();

            // 7. Schüler-Magic-Link Tabelle sicherstellen
            self::ensureMagicLinksTable();

            // 8. RBAC Berechtigungen registrieren
            if (class_exists('BoatingPermissions')) {
                BoatingPermissions::registerAll();
            }

            // 9. Modul "boating_school" in Tabelle modules als aktiv sicherstellen
            self::ensureModuleEnabledInDatabase();

            // 10. Schema-Version in Settings aktualisieren
            if (class_exists('Settings')) {
                Settings::set('boating_schema_version', self::CURRENT_SCHEMA_VERSION);
            }
        } catch (\Throwable $e) {
            error_log('BoatingMigrationManager::run Fehler: ' . $e->getMessage());
        }
    }

    /**
     * Stellt sicher, dass das Modul in der CMS modules-Tabelle als aktiviert (is_enabled = 1) geführt wird.
     */
    public static function ensureModuleEnabledInDatabase(): void
    {
        try {
            if (self::tableExists('modules')) {
                DB::execute("UPDATE `modules` SET `is_enabled` = 1, `updated_at` = NOW() WHERE `key` = 'boating_school'");
            }
        } catch (\Throwable $e) {
            // Ignorieren falls nicht vorhanden
        }
    }

    /**
     * Prüft, ob eine Tabelle in der aktuellen Datenbank existiert.
     */
    public static function tableExists(string $tableName): bool
    {
        try {
            $count = (int) DB::fetchValue(
                "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tbl",
                ['tbl' => $tableName]
            );
            return $count > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Prüft, ob eine Spalte in einer Tabelle existiert.
     */
    public static function columnExists(string $tableName, string $columnName): bool
    {
        try {
            $count = (int) DB::fetchValue(
                "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tbl AND COLUMN_NAME = :col",
                ['tbl' => $tableName, 'col' => $columnName]
            );
            return $count > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Stellt sicher, dass die zentrale Timesheet-Closures-Tabelle existiert.
     */
    private static function ensureTimesheetClosuresTable(): void
    {
        DB::execute("
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
                INDEX `idx_closure_inst` (`instructor_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Fremdschlüssel hinzufügen falls noch nicht vorhanden
        try {
            $fkExists = (int) DB::fetchValue("
                SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                WHERE CONSTRAINT_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'bs_coach_timesheet_closures'
                  AND CONSTRAINT_NAME = 'fk_bs_closure_inst'
            ");
            if ($fkExists === 0 && self::tableExists('bs_instructors')) {
                DB::execute("
                    ALTER TABLE `bs_coach_timesheet_closures`
                    ADD CONSTRAINT `fk_bs_closure_inst` FOREIGN KEY (`instructor_id`)
                    REFERENCES `bs_instructors`(`id`) ON DELETE CASCADE
                ");
            }
        } catch (\Throwable $e) {
            // Ignorieren falls FK bereits existiert
        }
    }

    /**
     * Migriert bestehende Daten von bs_coach_timesheets nach bs_coach_timesheet_closures
     * und benennt die alte Tabelle danach in bs_coach_timesheets_legacy um.
     */
    private static function migrateCoachTimesheetsToClosures(): void
    {
        if (!self::tableExists('bs_coach_timesheets')) {
            return;
        }

        try {
            // Daten kopieren (sofern in closures noch nicht vorhanden)
            DB::execute("
                INSERT IGNORE INTO `bs_coach_timesheet_closures` (
                    `instructor_id`, `year_month`, `status`, `pdf_version`,
                    `total_hours`, `gross_amount`, `net_amount`,
                    `deductions_json`, `snapshot_json`, `rejection_reason`,
                    `submitted_at`, `submitted_by`, `approved_at`, `approved_by`,
                    `reopened_at`, `reopened_by`, `created_at`, `updated_at`
                )
                SELECT
                    `instructor_id`, `year_month`, `status`, `pdf_version`,
                    `total_hours`, `gross_amount`, `net_amount`,
                    `deductions_json`, `snapshot_json`, `rejection_reason`,
                    `submitted_at`, `submitted_by`, `approved_at`, `approved_by`,
                    `reopened_at`, `reopened_by`, `created_at`, `updated_at`
                FROM `bs_coach_timesheets`
            ");

            // Alte Tabelle in bs_coach_timesheets_legacy umbenennen (nicht löschen!)
            if (!self::tableExists('bs_coach_timesheets_legacy')) {
                DB::execute("RENAME TABLE `bs_coach_timesheets` TO `bs_coach_timesheets_legacy`");
            }
        } catch (\Throwable $e) {
            error_log('BoatingMigrationManager: Fehler bei Timesheet-Migration: ' . $e->getMessage());
        }
    }

    /**
     * Behandelt alte bs_instructor_payroll_runs: Umbenennung in Legacy.
     */
    private static function handlePayrollRunsLegacy(): void
    {
        if (self::tableExists('bs_instructor_payroll_runs') && !self::tableExists('bs_instructor_payroll_runs_legacy')) {
            try {
                DB::execute("RENAME TABLE `bs_instructor_payroll_runs` TO `bs_instructor_payroll_runs_legacy`");
            } catch (\Throwable $e) {
                error_log('BoatingMigrationManager: Fehler bei PayrollRuns-Rename: ' . $e->getMessage());
            }
        }
    }

    /**
     * Stellt sicher, dass überall instructor_id statt coach_id verwendet wird.
     */
    private static function ensureInstructorIdNaming(): void
    {
        $tablesToCheck = [
            'bs_coach_availabilities',
            'bs_coach_working_hours',
            'bs_coach_weekly_templates',
            'bs_coach_timesheet_closures',
        ];

        foreach ($tablesToCheck as $tbl) {
            if (!self::tableExists($tbl)) {
                continue;
            }

            // Falls coach_id existiert aber instructor_id nicht: Umbenennen
            if (self::columnExists($tbl, 'coach_id') && !self::columnExists($tbl, 'instructor_id')) {
                try {
                    DB::execute("ALTER TABLE `{$tbl}` CHANGE COLUMN `coach_id` `instructor_id` INT UNSIGNED NOT NULL");
                } catch (\Throwable $e) {
                    error_log("BoatingMigrationManager: Rename coach_id in {$tbl} fehlgeschlagen: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Stellt sicher, dass location_id überall als INT UNSIGNED NULL modelliert ist.
     * Migriert vorhandene VARCHAR-Werte ('all') oder 0 auf NULL.
     */
    private static function ensureLocationIdConsistency(): void
    {
        $tables = ['bs_coach_availabilities', 'bs_coach_weekly_templates'];

        foreach ($tables as $tbl) {
            if (!self::tableExists($tbl)) {
                continue;
            }

            if (!self::columnExists($tbl, 'location_id')) {
                try {
                    DB::execute("ALTER TABLE `{$tbl}` ADD COLUMN `location_id` INT UNSIGNED NULL");
                } catch (\Throwable $e) {
                    // Weiter
                }
            } else {
                // Spaltentyp prüfen und absichern
                try {
                    DB::execute("ALTER TABLE `{$tbl}` MODIFY COLUMN `location_id` INT UNSIGNED NULL");
                } catch (\Throwable $e) {
                    // Weiter
                }
            }

            // Werte 0 auf NULL bereinigen
            try {
                DB::execute("UPDATE `{$tbl}` SET `location_id` = NULL WHERE `location_id` = 0");
            } catch (\Throwable $e) {
                // Weiter
            }
        }
    }

    /**
     * Stellt die Tabelle bs_payroll_settings und Standardabzugssätze sicher.
     */
    private static function ensurePayrollSettings(): void
    {
        DB::execute("
            CREATE TABLE IF NOT EXISTS `bs_payroll_settings` (
                `setting_key`   VARCHAR(64) NOT NULL PRIMARY KEY,
                `setting_value` VARCHAR(255) NOT NULL,
                `description`   VARCHAR(255) NULL,
                `updated_at`    DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $defaults = [
            'payroll_ahv_rate'           => ['5.30', 'AHV/IV/EO Beitragssatz Arbeitnehmer in % (Standard 5.30 %)'],
            'payroll_alv_rate'           => ['1.10', 'ALV Arbeitslosenversicherung Arbeitnehmer in % (Standard 1.10 %)'],
            'payroll_nbu_rate'           => ['1.30', 'NBU Nichtbetriebsunfallversicherung in % (z.B. 1.20 - 1.50 %)'],
            'payroll_bvg_rate'           => ['0.00', 'BVG Pensionskasse Arbeitnehmer in % (coach-spezifisch überschreibbar)'],
            'payroll_ktg_rate'           => ['1.00', 'KTG Krankentaggeld in % (z.B. 0.80 - 1.20 %)'],
            'payroll_withholding_tax'    => ['0.00', 'Quellensteuer Standard in % (Default 0.00 %)'],
            'payroll_misc_fixed'         => ['0.00', 'Pauschale Abzüge in CHF (monatlich)'],
            'payroll_notification_email' => ['payroll@bootschule.ch', 'E-Mail für Benachrichtigungen bei Timesheet-Einreichung'],
        ];

        foreach ($defaults as $k => [$val, $desc]) {
            try {
                DB::execute(
                    "INSERT INTO `bs_payroll_settings` (`setting_key`, `setting_value`, `description`, `updated_at`)
                     VALUES (:k, :v, :d, NOW())
                     ON DUPLICATE KEY UPDATE `description` = :d_up",
                    ['k' => $k, 'v' => $val, 'd' => $desc, 'd_up' => $desc]
                );
            } catch (\Throwable $e) {
                // Weiter
            }
        }
    }

    /**
     * Stellt die Tabelle bs_customer_magic_links sicher.
     */
    private static function ensureMagicLinksTable(): void
    {
        DB::execute("
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        if (self::columnExists('bs_customer_magic_links', 'used_at') && !self::columnExists('bs_customer_magic_links', 'last_used_at')) {
            try {
                DB::execute("ALTER TABLE `bs_customer_magic_links` CHANGE COLUMN `used_at` `last_used_at` DATETIME NULL");
            } catch (\Throwable $e) {
                // Weiter
            }
        }
    }
}
