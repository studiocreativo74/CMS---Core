<?php

declare(strict_types=1);

/**
 * CoachService
 *
 * Verwaltet das Modul «Coach & Fahrlehrer-Management» der Bootsfahrschule:
 * - Stammdaten & HR-Kartei (bs_instructor_details) mit Schweizer Sozialversicherungs-/Bankdaten
 * - Zentrale Konfiguration für Schweizer Lohnabzüge (bs_payroll_settings) & individuelle Overrides
 * - Verfügbarkeiten, Arbeitszeiten & Absenzen (bs_coach_availabilities) mit Standortbezug (location_id)
 * - Zeiterfassung & Schweizer Monatsrapporte / Timesheets (bs_coach_working_hours & bs_coach_timesheets)
 * - Timesheet-Workflow mit Statusübergängen (draft -> submitted -> approved -> reopened -> paid)
 * - Versionierte Snapshots (snapshot_json, pdf_version) bei erneuter Einreichung nach reopened
 * - Zentrales Audit-Logging (bs_audit_logs & ActivityLog)
 */
final class CoachService
{
    // Absenz- & Verfügbarkeitstypen
    public const AVAIL_AVAILABLE        = 'available';
    public const AVAIL_UNAVAILABLE      = 'unavailable';
    public const AVAIL_VACATION         = 'vacation';
    public const AVAIL_SICK             = 'sick';
    public const AVAIL_MILITARY         = 'military';
    public const AVAIL_OTHER            = 'other';
    public const AVAIL_HOLIDAY_OVERRIDE = 'holiday_override';

    // Zeiterfassungs-Kategorien
    public const CAT_LESSON      = 'lesson';
    public const CAT_EXAM        = 'exam';
    public const CAT_ADMIN       = 'admin';
    public const CAT_MAINTENANCE = 'maintenance';
    public const CAT_OTHER       = 'other';

    // Timesheet Status
    public const STATUS_DRAFT     = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED  = 'approved';
    public const STATUS_REJECTED  = 'rejected';
    public const STATUS_REOPENED  = 'reopened';
    public const STATUS_EXPORTED  = 'exported';
    public const STATUS_PAID      = 'paid';

    /**
     * Stellt sicher, dass die Tabellen des Coach-Moduls idempotent existieren.
     */
    public static function ensureTables(): void
    {
        if (!class_exists('DB')) {
            return;
        }

        try {
            // 1. bs_instructors Felder
            if (BoatingSchoolRepository::isTableCreated('bs_instructors')) {
                DB::execute("ALTER TABLE `bs_instructors`
                    ADD COLUMN IF NOT EXISTS `title` VARCHAR(100) NOT NULL DEFAULT 'Fahrcoach' AFTER `last_name`,
                    ADD COLUMN IF NOT EXISTS `description` TEXT NULL AFTER `qualifications`,
                    ADD COLUMN IF NOT EXISTS `image_url` VARCHAR(255) NULL AFTER `image_path`,
                    ADD COLUMN IF NOT EXISTS `public_mobile` VARCHAR(50) NULL AFTER `phone`,
                    ADD COLUMN IF NOT EXISTS `is_mobile_public` TINYINT(1) NOT NULL DEFAULT 0 AFTER `public_mobile`");
            }

            // 2. bs_instructor_details (HR-Daten inkl. individueller Abzugs-Overrides)
            DB::execute("CREATE TABLE IF NOT EXISTS `bs_instructor_details` (
                `instructor_id`         INT UNSIGNED NOT NULL PRIMARY KEY,
                `phone`                 VARCHAR(50) NULL,
                `email`                 VARCHAR(191) NULL,
                `birth_date`            DATE NULL,
                `entry_date`            DATE NULL,
                `ahv_number`            VARCHAR(50) NULL COMMENT 'CH AHV-Nr: 756.xxxx.xxxx.xx',
                `bank_iban`             VARCHAR(50) NULL,
                `bank_name`             VARCHAR(100) NULL,
                `bank_city`             VARCHAR(100) NULL,
                `address`               VARCHAR(255) NULL,
                `hourly_rate`           DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `ahv_rate_override`     DECIMAL(5,2) NULL,
                `alv_rate_override`     DECIMAL(5,2) NULL,
                `nbu_rate_override`     DECIMAL(5,2) NULL,
                `bvg_rate_override`     DECIMAL(5,2) NULL,
                `ktg_rate_override`     DECIMAL(5,2) NULL,
                `withholding_tax_rate`  DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                `misc_fixed_deduction`  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `created_at`            DATETIME NOT NULL,
                `updated_at`            DATETIME NOT NULL,
                CONSTRAINT `fk_bs_inst_details` FOREIGN KEY (`instructor_id`)
                    REFERENCES `bs_instructors`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // 3. bs_coach_availabilities (mit instructor_id, location_id und is_holiday_active)
            DB::execute("CREATE TABLE IF NOT EXISTS `bs_coach_availabilities` (
                `id`                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `instructor_id`      INT UNSIGNED NOT NULL,
                `date`               DATE NOT NULL,
                `start_time`         TIME NULL,
                `end_time`           TIME NULL,
                `location_id`        INT UNSIGNED NULL COMMENT 'NULL = alle Standorte / standortunabhängig',
                `type`               ENUM('available','unavailable','vacation','sick','military','other','holiday_override') NOT NULL DEFAULT 'unavailable',
                `is_holiday_active`  TINYINT(1) NOT NULL DEFAULT 0,
                `note`               VARCHAR(255) NULL,
                `is_recurring`       TINYINT(1) NOT NULL DEFAULT 0,
                `recurring_weekday`  TINYINT NULL,
                `created_at`         DATETIME NOT NULL,
                `updated_at`         DATETIME NOT NULL,
                INDEX `idx_coach_avail_inst_date` (`instructor_id`, `date`),
                INDEX `idx_coach_avail_date` (`date`),
                INDEX `idx_coach_avail_loc` (`location_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // 4. bs_coach_weekly_templates
            if (class_exists('CoachAvailabilityService')) {
                CoachAvailabilityService::ensureTables();
            }

            // 5. bs_coach_working_hours (einzelne Arbeitszeit-Einträge)
            DB::execute("CREATE TABLE IF NOT EXISTS `bs_coach_working_hours` (
                `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `instructor_id`  INT UNSIGNED NOT NULL,
                `appointment_id` INT UNSIGNED NULL,
                `date`           DATE NOT NULL,
                `start_time`     TIME NOT NULL,
                `end_time`       TIME NOT NULL,
                `break_minutes`  INT UNSIGNED NOT NULL DEFAULT 0,
                `total_hours`    DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                `rate_amount`    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `total_amount`   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `category`       ENUM('lesson','exam','admin','maintenance','other') NOT NULL DEFAULT 'lesson',
                `status`         ENUM('draft','submitted','approved','rejected','reopened','exported','paid') NOT NULL DEFAULT 'draft',
                `note`           TEXT NULL,
                `created_at`     DATETIME NOT NULL,
                `updated_at`     DATETIME NOT NULL,
                INDEX `idx_coach_hours_inst_date` (`instructor_id`, `date`),
                INDEX `idx_coach_hours_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // 6. bs_payroll_settings (Zentrale Schweizer Abzugssätze)
            DB::execute("CREATE TABLE IF NOT EXISTS `bs_payroll_settings` (
                `setting_key`   VARCHAR(64) NOT NULL PRIMARY KEY,
                `setting_value` VARCHAR(255) NOT NULL,
                `description`   VARCHAR(255) NULL,
                `updated_at`    DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // Initialisiere Standardwerte falls leer
            self::seedDefaultPayrollSettings();

            // 7. bs_coach_timesheets (Monatsrapporte mit Versionierung & Snapshots)
            DB::execute("CREATE TABLE IF NOT EXISTS `bs_coach_timesheets` (
                `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `instructor_id`    INT UNSIGNED NOT NULL,
                `year_month`       VARCHAR(7) NOT NULL COMMENT 'YYYY-MM',
                `status`           ENUM('draft','submitted','approved','rejected','reopened','paid') NOT NULL DEFAULT 'draft',
                `pdf_version`      INT UNSIGNED NOT NULL DEFAULT 1,
                `total_hours`      DECIMAL(6,2) NOT NULL DEFAULT 0.00,
                `gross_amount`     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `net_amount`       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `deductions_json`  TEXT NULL,
                `snapshot_json`    MEDIUMTEXT NULL,
                `rejection_reason` TEXT NULL,
                `submitted_at`     DATETIME NULL,
                `submitted_by`     INT UNSIGNED NULL,
                `approved_at`      DATETIME NULL,
                `approved_by`      INT UNSIGNED NULL,
                `reopened_at`      DATETIME NULL,
                `reopened_by`      INT UNSIGNED NULL,
                `created_at`       DATETIME NOT NULL,
                `updated_at`       DATETIME NOT NULL,
                UNIQUE KEY `uniq_inst_month` (`instructor_id`, `year_month`),
                INDEX `idx_timesheet_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // 8. bs_audit_logs
            DB::execute("CREATE TABLE IF NOT EXISTS `bs_audit_logs` (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (\Throwable $e) {
            error_log('CoachService::ensureTables Fehler: ' . $e->getMessage());
        }
    }

    /**
     * Befüllt die Standardwerte für Schweizer Sozialversicherungs- und Lohnabzüge.
     */
    private static function seedDefaultPayrollSettings(): void
    {
        $defaults = [
            'payroll_ahv_rate'           => '5.30', // Standard 5.30 %
            'payroll_alv_rate'           => '1.10', // Standard 1.10 %
            'payroll_nbu_rate'           => '1.30', // 1.20 - 1.50 %
            'payroll_bvg_rate'           => '0.00', // Standard 0.00 % (oder coach-spezifisch)
            'payroll_ktg_rate'           => '1.00', // 0.80 - 1.20 %
            'payroll_withholding_tax'    => '0.00', // Quellensteuer Standard 0.00 %
            'payroll_misc_fixed'         => '0.00', // Pauschalabzug CHF
            'payroll_notification_email' => 'payroll@bootschule.ch',
        ];

        foreach ($defaults as $key => $val) {
            try {
                DB::execute(
                    'INSERT IGNORE INTO `bs_payroll_settings` (`setting_key`, `setting_value`, `description`, `updated_at`) VALUES (:k, :v, :d, NOW())',
                    ['k' => $key, 'v' => $val, 'd' => 'Standardwert ' . $key]
                );
            } catch (\Throwable $e) {
                // Ignore
            }
        }
    }

    // =========================================================================
    // 1. ZENTRALE LOHN-ABZUGSSÄTZE (bs_payroll_settings)
    // =========================================================================

    /**
     * Liest die zentralen Schweizer Lohnabzugssätze aus.
     *
     * @return array<string, mixed>
     */
    public static function getPayrollSettings(): array
    {
        self::ensureTables();

        $settings = [
            'payroll_ahv_rate'           => 5.30,
            'payroll_alv_rate'           => 1.10,
            'payroll_nbu_rate'           => 1.30,
            'payroll_bvg_rate'           => 0.00,
            'payroll_ktg_rate'           => 1.00,
            'payroll_withholding_tax'    => 0.00,
            'payroll_misc_fixed'         => 0.00,
            'payroll_notification_email' => 'payroll@bootschule.ch',
        ];

        try {
            $rows = DB::fetchAll('SELECT `setting_key`, `setting_value` FROM `bs_payroll_settings`');
            foreach ($rows as $r) {
                $k = (string) $r['setting_key'];
                $v = (string) $r['setting_value'];
                if ($k === 'payroll_notification_email') {
                    $settings[$k] = $v;
                } else {
                    $settings[$k] = (float) $v;
                }
            }
        } catch (\Throwable $e) {
            error_log('CoachService::getPayrollSettings Fehler: ' . $e->getMessage());
        }

        // Falls Core-Settings-Klasse existiert, auch dortige Overrides prüfen
        if (class_exists('Settings')) {
            foreach ($settings as $k => $defVal) {
                $coreVal = Settings::get($k, null);
                if ($coreVal !== null && $coreVal !== '') {
                    $settings[$k] = is_numeric($coreVal) ? (float) $coreVal : (string) $coreVal;
                }
            }
        }

        return $settings;
    }

    /**
     * Speichert die zentralen Lohnabzugssätze.
     *
     * @param array<string, mixed> $data
     */
    public static function savePayrollSettings(array $data): bool
    {
        self::ensureTables();

        $keys = [
            'payroll_ahv_rate',
            'payroll_alv_rate',
            'payroll_nbu_rate',
            'payroll_bvg_rate',
            'payroll_ktg_rate',
            'payroll_withholding_tax',
            'payroll_misc_fixed',
            'payroll_notification_email',
        ];

        $now = date('Y-m-d H:i:s');
        try {
            foreach ($keys as $k) {
                if (isset($data[$k])) {
                    $val = trim((string) $data[$k]);
                    DB::execute(
                        'INSERT INTO `bs_payroll_settings` (`setting_key`, `setting_value`, `updated_at`)
                         VALUES (:k, :v, :now)
                         ON DUPLICATE KEY UPDATE `setting_value` = :v2, `updated_at` = :now2',
                        [
                            'k'    => $k,
                            'v'    => $val,
                            'now'  => $now,
                            'v2'   => $val,
                            'now2' => $now,
                        ]
                    );

                    if (class_exists('Settings')) {
                        Settings::set($k, $val);
                    }
                }
            }
            return true;
        } catch (\Throwable $e) {
            error_log('CoachService::savePayrollSettings Fehler: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Berechnet die Schweizer Lohnabzüge (AHV, ALV, NBU, BVG, KTG, Quellensteuer)
     * für einen Bruttobetrag, unter Berücksichtigung individueller Coach-Overrides.
     *
     * @return array{
     *   gross_amount: float,
     *   ahv_rate: float,
     *   ahv_amount: float,
     *   alv_rate: float,
     *   alv_amount: float,
     *   nbu_rate: float,
     *   nbu_amount: float,
     *   bvg_rate: float,
     *   bvg_amount: float,
     *   ktg_rate: float,
     *   ktg_amount: float,
     *   withholding_tax_rate: float,
     *   withholding_tax_amount: float,
     *   misc_fixed: float,
     *   deductions_total: float,
     *   net_amount: float
     * }
     */
    public static function calculateSwissPayroll(int $instructorId, float $grossAmount): array
    {
        $settings = self::getPayrollSettings();
        $hr = self::getHrDetails($instructorId);

        // Abzugssätze ermitteln: Individueller Override hat Vorrang vor globalem Standard
        $ahvRate = ($hr && isset($hr['ahv_rate_override']) && $hr['ahv_rate_override'] !== null)
            ? (float) $hr['ahv_rate_override']
            : (float) $settings['payroll_ahv_rate'];

        $alvRate = ($hr && isset($hr['alv_rate_override']) && $hr['alv_rate_override'] !== null)
            ? (float) $hr['alv_rate_override']
            : (float) $settings['payroll_alv_rate'];

        $nbuRate = ($hr && isset($hr['nbu_rate_override']) && $hr['nbu_rate_override'] !== null)
            ? (float) $hr['nbu_rate_override']
            : (float) $settings['payroll_nbu_rate'];

        $bvgRate = ($hr && isset($hr['bvg_rate_override']) && $hr['bvg_rate_override'] !== null)
            ? (float) $hr['bvg_rate_override']
            : (float) $settings['payroll_bvg_rate'];

        $ktgRate = ($hr && isset($hr['ktg_rate_override']) && $hr['ktg_rate_override'] !== null)
            ? (float) $hr['ktg_rate_override']
            : (float) $settings['payroll_ktg_rate'];

        $withholdingTaxRate = ($hr && isset($hr['withholding_tax_rate']))
            ? (float) $hr['withholding_tax_rate']
            : (float) $settings['payroll_withholding_tax'];

        $miscFixed = ($hr && isset($hr['misc_fixed_deduction']) && (float) $hr['misc_fixed_deduction'] > 0)
            ? (float) $hr['misc_fixed_deduction']
            : (float) $settings['payroll_misc_fixed'];

        // Abzüge berechnen (gerundet auf 5 Rappen bzw. 2 Dezimalstellen)
        $ahvAmount = round($grossAmount * ($ahvRate / 100.0), 2);
        $alvAmount = round($grossAmount * ($alvRate / 100.0), 2);
        $nbuAmount = round($grossAmount * ($nbuRate / 100.0), 2);
        $bvgAmount = round($grossAmount * ($bvgRate / 100.0), 2);
        $ktgAmount = round($grossAmount * ($ktgRate / 100.0), 2);
        $withholdingTaxAmount = round($grossAmount * ($withholdingTaxRate / 100.0), 2);

        $deductionsTotal = round(
            $ahvAmount + $alvAmount + $nbuAmount + $bvgAmount + $ktgAmount + $withholdingTaxAmount + $miscFixed,
            2
        );
        $netAmount = max(0.00, round($grossAmount - $deductionsTotal, 2));

        return [
            'gross_amount'           => round($grossAmount, 2),
            'ahv_rate'               => $ahvRate,
            'ahv_amount'             => $ahvAmount,
            'alv_rate'               => $alvRate,
            'alv_amount'             => $alvAmount,
            'nbu_rate'               => $nbuRate,
            'nbu_amount'             => $nbuAmount,
            'bvg_rate'               => $bvgRate,
            'bvg_amount'             => $bvgAmount,
            'ktg_rate'               => $ktgRate,
            'ktg_amount'             => $ktgAmount,
            'withholding_tax_rate'   => $withholdingTaxRate,
            'withholding_tax_amount' => $withholdingTaxAmount,
            'misc_fixed'             => $miscFixed,
            'deductions_total'       => $deductionsTotal,
            'net_amount'             => $netAmount,
        ];
    }

    // =========================================================================
    // 2. HR-DATEN & PERSONALAKTE (bs_instructor_details)
    // =========================================================================

    /**
     * Lädt die sensiblen HR-Daten eines Fahrlehrers.
     *
     * @return array<string, mixed>|null
     */
    public static function getHrDetails(int $instructorId): ?array
    {
        if ($instructorId <= 0) {
            return null;
        }

        self::ensureTables();

        try {
            $row = DB::fetchOne('SELECT * FROM `bs_instructor_details` WHERE `instructor_id` = :id', ['id' => $instructorId]);
            return $row ?: null;
        } catch (\Throwable $e) {
            error_log('CoachService::getHrDetails Fehler: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Speichert oder aktualisiert die HR-Daten eines Fahrlehrers (Upsert).
     *
     * @param array<string, mixed> $data
     */
    public static function saveHrDetails(int $instructorId, array $data): bool
    {
        if ($instructorId <= 0) {
            return false;
        }

        self::ensureTables();

        $now = date('Y-m-d H:i:s');
        $params = [
            'instructor_id'        => $instructorId,
            'phone'                => !empty($data['phone']) ? trim((string) $data['phone']) : null,
            'email'                => !empty($data['email']) ? trim((string) $data['email']) : null,
            'birth_date'           => !empty($data['birth_date']) ? trim((string) $data['birth_date']) : null,
            'entry_date'           => !empty($data['entry_date']) ? trim((string) $data['entry_date']) : null,
            'ahv_number'           => !empty($data['ahv_number']) ? trim((string) $data['ahv_number']) : null,
            'bank_iban'            => !empty($data['bank_iban']) ? strtoupper(str_replace(' ', '', trim((string) $data['bank_iban']))) : null,
            'bank_name'            => !empty($data['bank_name']) ? trim((string) $data['bank_name']) : null,
            'bank_city'            => !empty($data['bank_city']) ? trim((string) $data['bank_city']) : null,
            'address'              => !empty($data['address']) ? trim((string) $data['address']) : null,
            'hourly_rate'          => isset($data['hourly_rate']) ? (float) $data['hourly_rate'] : 0.00,
            'ahv_rate_override'    => (isset($data['ahv_rate_override']) && $data['ahv_rate_override'] !== '') ? (float) $data['ahv_rate_override'] : null,
            'alv_rate_override'    => (isset($data['alv_rate_override']) && $data['alv_rate_override'] !== '') ? (float) $data['alv_rate_override'] : null,
            'nbu_rate_override'    => (isset($data['nbu_rate_override']) && $data['nbu_rate_override'] !== '') ? (float) $data['nbu_rate_override'] : null,
            'bvg_rate_override'    => (isset($data['bvg_rate_override']) && $data['bvg_rate_override'] !== '') ? (float) $data['bvg_rate_override'] : null,
            'ktg_rate_override'    => (isset($data['ktg_rate_override']) && $data['ktg_rate_override'] !== '') ? (float) $data['ktg_rate_override'] : null,
            'withholding_tax_rate' => isset($data['withholding_tax_rate']) ? (float) $data['withholding_tax_rate'] : 0.00,
            'misc_fixed_deduction' => isset($data['misc_fixed_deduction']) ? (float) $data['misc_fixed_deduction'] : 0.00,
            'updated_at'           => $now,
        ];

        try {
            $existing = self::getHrDetails($instructorId);
            if ($existing) {
                $sql = 'UPDATE `bs_instructor_details` SET
                    `phone` = :phone, `email` = :email, `birth_date` = :birth_date, `entry_date` = :entry_date,
                    `ahv_number` = :ahv_number, `bank_iban` = :bank_iban, `bank_name` = :bank_name,
                    `bank_city` = :bank_city, `address` = :address, `hourly_rate` = :hourly_rate,
                    `ahv_rate_override` = :ahv_rate_override, `alv_rate_override` = :alv_rate_override,
                    `nbu_rate_override` = :nbu_rate_override, `bvg_rate_override` = :bvg_rate_override,
                    `ktg_rate_override` = :ktg_rate_override, `withholding_tax_rate` = :withholding_tax_rate,
                    `misc_fixed_deduction` = :misc_fixed_deduction, `updated_at` = :updated_at
                    WHERE `instructor_id` = :instructor_id';
            } else {
                $params['created_at'] = $now;
                $sql = 'INSERT INTO `bs_instructor_details` (
                    `instructor_id`, `phone`, `email`, `birth_date`, `entry_date`, `ahv_number`,
                    `bank_iban`, `bank_name`, `bank_city`, `address`, `hourly_rate`,
                    `ahv_rate_override`, `alv_rate_override`, `nbu_rate_override`, `bvg_rate_override`,
                    `ktg_rate_override`, `withholding_tax_rate`, `misc_fixed_deduction`,
                    `created_at`, `updated_at`
                ) VALUES (
                    :instructor_id, :phone, :email, :birth_date, :entry_date, :ahv_number,
                    :bank_iban, :bank_name, :bank_city, :address, :hourly_rate,
                    :ahv_rate_override, :alv_rate_override, :nbu_rate_override, :bvg_rate_override,
                    :ktg_rate_override, :withholding_tax_rate, :misc_fixed_deduction,
                    :created_at, :updated_at
                )';
            }

            DB::execute($sql, $params);

            // Falls hourly_rate gesetzt wurde und pay_rate_amount im Fahrlehrer noch 0 ist, synchronisieren
            if ($params['hourly_rate'] > 0) {
                DB::execute('UPDATE `bs_instructors` SET `pay_rate_amount` = :rate, `updated_at` = :now WHERE `id` = :id AND (`pay_rate_amount` = 0 OR `pay_rate_amount` IS NULL)', [
                    'rate' => $params['hourly_rate'],
                    'now'  => $now,
                    'id'   => $instructorId,
                ]);
            }

            return true;
        } catch (\Throwable $e) {
            error_log('CoachService::saveHrDetails Fehler: ' . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // 3. VERFÜGBARKEITEN & ABSENZEN (bs_coach_availabilities mit location_id)
    // =========================================================================

    /**
     * Holt Verfügbarkeiten / Absenzen für einen Fahrlehrer oder alle im Zeitraum.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getAvailabilities(array $filters = []): array
    {
        self::ensureTables();

        $where = [];
        $params = [];

        if (!empty($filters['instructor_id'])) {
            $where[] = 'a.`instructor_id` = :inst_id';
            $params['inst_id'] = (int) $filters['instructor_id'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'a.`date` >= :date_from';
            $params['date_from'] = (string) $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'a.`date` <= :date_to';
            $params['date_to'] = (string) $filters['date_to'];
        }

        if (!empty($filters['type'])) {
            $where[] = 'a.`type` = :type';
            $params['type'] = (string) $filters['type'];
        }

        if (isset($filters['location_id']) && $filters['location_id'] !== '') {
            $where[] = '(a.`location_id` = :loc_id OR a.`location_id` IS NULL)';
            $params['loc_id'] = (int) $filters['location_id'];
        }

        $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT a.*, i.first_name, i.last_name, i.color_code, l.name AS location_name
                FROM `bs_coach_availabilities` a
                JOIN `bs_instructors` i ON i.id = a.instructor_id
                LEFT JOIN `bs_locations` l ON l.id = a.location_id
                {$whereSql}
                ORDER BY a.`date` ASC, a.`start_time` ASC";

        try {
            return DB::fetchAll($sql, $params);
        } catch (\Throwable $e) {
            error_log('CoachService::getAvailabilities Fehler: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Trägt eine neue Verfügbarkeit, Absenz oder Feiertags-Override ein.
     *
     * @param array<string, mixed> $data
     */
    public static function addAvailability(array $data): int
    {
        self::ensureTables();

        $now = date('Y-m-d H:i:s');
        $type = (string) ($data['type'] ?? self::AVAIL_UNAVAILABLE);
        $isHolidayActive = !empty($data['is_holiday_active']) || $type === self::AVAIL_HOLIDAY_OVERRIDE ? 1 : 0;

        // location_id: INT UNSIGNED NULL (NULL = alle Standorte / standortunabhängig)
        $locationId = null;
        if (!empty($data['location_id']) && is_numeric($data['location_id']) && (int) $data['location_id'] > 0) {
            $locationId = (int) $data['location_id'];
        }

        $params = [
            'instructor_id'     => (int) ($data['instructor_id'] ?? 0),
            'date'              => (string) ($data['date'] ?? date('Y-m-d')),
            'start_time'        => !empty($data['start_time']) ? (string) $data['start_time'] : null,
            'end_time'          => !empty($data['end_time']) ? (string) $data['end_time'] : null,
            'location_id'       => $locationId,
            'type'              => $type,
            'is_holiday_active' => $isHolidayActive,
            'note'              => !empty($data['note']) ? trim((string) $data['note']) : null,
            'is_recurring'      => !empty($data['is_recurring']) ? 1 : 0,
            'recurring_weekday' => !empty($data['recurring_weekday']) ? (int) $data['recurring_weekday'] : null,
            'created_at'        => $now,
            'updated_at'        => $now,
        ];

        if ($params['instructor_id'] <= 0 || empty($params['date'])) {
            return 0;
        }

        try {
            $sql = 'INSERT INTO `bs_coach_availabilities` (
                `instructor_id`, `date`, `start_time`, `end_time`, `location_id`, `type`, `is_holiday_active`,
                `note`, `is_recurring`, `recurring_weekday`, `created_at`, `updated_at`
            ) VALUES (
                :instructor_id, :date, :start_time, :end_time, :location_id, :type, :is_holiday_active,
                :note, :is_recurring, :recurring_weekday, :created_at, :updated_at
            )';
            DB::execute($sql, $params);
            $newId = (int) DB::lastInsertId();

            // Audit-Logging
            self::logAudit('availability_created', $params['instructor_id'], null, $type, [
                'availability_id' => $newId,
                'date'            => $params['date'],
                'start_time'      => $params['start_time'],
                'end_time'        => $params['end_time'],
                'location_id'     => $params['location_id'],
                'note'            => $params['note'],
            ], (string) $newId);

            return $newId;
        } catch (\Throwable $e) {
            error_log('CoachService::addAvailability Fehler: ' . $e->getMessage());
            return 0;
        }
    }

    public static function deleteAvailability(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }
        try {
            $record = DB::fetchOne('SELECT * FROM `bs_coach_availabilities` WHERE `id` = :id', ['id' => $id]);
            DB::execute('DELETE FROM `bs_coach_availabilities` WHERE `id` = :id', ['id' => $id]);

            if ($record) {
                self::logAudit('availability_deleted', (int) $record['instructor_id'], (string) $record['type'], null, [
                    'availability_id' => $id,
                    'date'            => $record['date'],
                ], (string) $id);
            }

            return true;
        } catch (\Throwable $e) {
            error_log('CoachService::deleteAvailability Fehler: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Prüft, ob ein Fahrlehrer an einem bestimmten Datum und Zeitfenster abwesend / gesperrt ist.
     *
     * @return array{is_blocked: bool, reason: string|null, type: string|null}
     */
    public static function checkCoachAbsence(int $instructorId, string $date, string $startTime, string $endTime, ?int $locationId = null): array
    {
        if (class_exists('CoachAvailabilityService')) {
            $dtStart = \DateTimeImmutable::createFromFormat('Y-m-d H:i', "{$date} {$startTime}");
            $dtEnd   = \DateTimeImmutable::createFromFormat('Y-m-d H:i', "{$date} {$endTime}");
            if ($dtStart && $dtEnd) {
                $res = CoachAvailabilityService::isInstructorAvailableAt($instructorId, $dtStart, $dtEnd, $locationId);
                return [
                    'is_blocked' => !$res['available'],
                    'reason'     => $res['reason'],
                    'type'       => $res['code'],
                ];
            }
        }

        return ['is_blocked' => false, 'reason' => null, 'type' => null];
    }

    // =========================================================================
    // 4. ZEITERFASSUNG & ARBEITSSTUNDEN (bs_coach_working_hours)
    // =========================================================================

    /**
     * Lädt Zeiterfassungs-Einträge mit Filter nach Fahrlehrer, Zeitraum und Status.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getWorkingHours(array $filters = []): array
    {
        self::ensureTables();

        $where = [];
        $params = [];

        if (!empty($filters['instructor_id'])) {
            $where[] = 'w.`instructor_id` = :inst_id';
            $params['inst_id'] = (int) $filters['instructor_id'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'w.`date` >= :date_from';
            $params['date_from'] = (string) $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'w.`date` <= :date_to';
            $params['date_to'] = (string) $filters['date_to'];
        }

        if (!empty($filters['status'])) {
            $where[] = 'w.`status` = :status';
            $params['status'] = (string) $filters['status'];
        }

        if (!empty($filters['category'])) {
            $where[] = 'w.`category` = :cat';
            $params['cat'] = (string) $filters['category'];
        }

        $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT w.*, i.first_name, i.last_name, i.payroll_external_code
                FROM `bs_coach_working_hours` w
                JOIN `bs_instructors` i ON i.id = w.instructor_id
                {$whereSql}
                ORDER BY w.`date` ASC, w.`start_time` ASC";

        try {
            return DB::fetchAll($sql, $params);
        } catch (\Throwable $e) {
            error_log('CoachService::getWorkingHours Fehler: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Trägt eine Arbeitszeit ein und berechnet Dauer und Betrag.
     *
     * @param array<string, mixed> $data
     */
    public static function logWorkingHours(array $data): int
    {
        self::ensureTables();

        $instId    = (int) ($data['instructor_id'] ?? 0);
        $date      = (string) ($data['date'] ?? date('Y-m-d'));
        $startTime = substr(trim((string) ($data['start_time'] ?? '08:00')), 0, 5);
        $endTime   = substr(trim((string) ($data['end_time'] ?? '09:00')), 0, 5);
        $breakMin  = (int) ($data['break_minutes'] ?? 0);

        // Dauer in Stunden berechnen (entweder aus Start/Endzeit oder direkt aus hours & minutes)
        if (isset($data['hours']) || isset($data['minutes'])) {
            $h = max(0, (int) ($data['hours'] ?? 0));
            $m = max(0, min(59, (int) ($data['minutes'] ?? 0)));
            $rawMin = $h * 60 + $m;
            $diffMinutes = max(0, $rawMin - $breakMin);
            $totalHours = round($diffMinutes / 60.0, 2);

            // Falls end_time nicht gesetzt oder automatisch berechnen:
            $t1 = strtotime($date . ' ' . $startTime);
            $endTime = date('H:i', $t1 + ($rawMin * 60));
        } else {
            $t1 = strtotime($date . ' ' . $startTime);
            $t2 = strtotime($date . ' ' . $endTime);
            $diffMinutes = max(0, (int) round(($t2 - $t1) / 60) - $breakMin);
            $totalHours = round($diffMinutes / 60.0, 2);
        }

        // Stundensatz ermitteln: Vom Admin hinterlegt (in HR-Details oder Instructor-Stammdaten)
        $rate = 0.00;
        $hr = self::getHrDetails($instId);
        if ($hr && !empty($hr['hourly_rate']) && (float) $hr['hourly_rate'] > 0) {
            $rate = (float) $hr['hourly_rate'];
        } else {
            $inst = InstructorService::find($instId);
            if ($inst && isset($inst['pay_rate_amount']) && (float) $inst['pay_rate_amount'] > 0) {
                $rate = (float) $inst['pay_rate_amount'];
            } elseif (isset($data['rate_amount']) && (float) $data['rate_amount'] > 0) {
                $rate = (float) $data['rate_amount'];
            }
        }

        $totalAmount = round($totalHours * $rate, 2);
        $now = date('Y-m-d H:i:s');

        $subject = !empty($data['subject']) ? trim((string) $data['subject']) : '';
        $noteRaw = !empty($data['note']) ? trim((string) $data['note']) : '';
        $finalNote = $subject !== '' ? ($noteRaw !== '' ? "{$subject} – {$noteRaw}" : $subject) : ($noteRaw !== '' ? $noteRaw : null);

        $params = [
            'instructor_id'  => $instId,
            'appointment_id' => !empty($data['appointment_id']) ? (int) $data['appointment_id'] : null,
            'date'           => $date,
            'start_time'     => $startTime . ':00',
            'end_time'       => $endTime . ':00',
            'break_minutes'  => $breakMin,
            'total_hours'    => $totalHours,
            'rate_amount'    => $rate,
            'total_amount'   => $totalAmount,
            'category'       => (string) ($data['category'] ?? self::CAT_LESSON),
            'status'         => (string) ($data['status'] ?? self::STATUS_DRAFT),
            'note'           => $finalNote,
            'created_at'     => $now,
            'updated_at'     => $now,
        ];

        try {
            $sql = 'INSERT INTO `bs_coach_working_hours` (
                `instructor_id`, `appointment_id`, `date`, `start_time`, `end_time`, `break_minutes`,
                `total_hours`, `rate_amount`, `total_amount`, `category`, `status`, `note`, `created_at`, `updated_at`
            ) VALUES (
                :instructor_id, :appointment_id, :date, :start_time, :end_time, :break_minutes,
                :total_hours, :rate_amount, :total_amount, :category, :status, :note, :created_at, :updated_at
            )';
            DB::execute($sql, $params);
            return (int) DB::lastInsertId();
        } catch (\Throwable $e) {
            error_log('CoachService::logWorkingHours Fehler: ' . $e->getMessage());
            return 0;
        }
    }

    // =========================================================================
    // 5. TIMESHEET-WORKFLOW, MONATSABSCHLUSS & REOPENED VERSIONIERUNG
    // =========================================================================

    /**
     * Lädt den Monats-Kopfdatensatz eines Timesheets (bs_coach_timesheets).
     *
     * @return array<string, mixed>|null
     */
    public static function getTimesheet(int $instructorId, string $yearMonth): ?array
    {
        self::ensureTables();
        if ($instructorId <= 0 || empty($yearMonth)) {
            return null;
        }

        try {
            $row = DB::fetchOne(
                'SELECT * FROM `bs_coach_timesheets` WHERE `instructor_id` = :id AND `year_month` = :ym LIMIT 1',
                ['id' => $instructorId, 'ym' => $yearMonth]
            );
            return $row ?: null;
        } catch (\Throwable $e) {
            error_log('CoachService::getTimesheet Fehler: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Reicht den Monatsrapport eines Fahrlehrers ein (draft / reopened -> submitted).
     *
     * Versionsverhalten:
     * - War der Monat zuvor im Status 'reopened' (oder bereits genehmigt gewesen),
     *   wird pdf_version um 1 erhöht.
     * - Ein vollständiger Daten-Snapshot (Arbeitszeiten, Stunden, Brutto, Abzüge, Netto)
     *   wird in `snapshot_json` unter der neuen Version archiviert.
     * - Alle offenen Arbeitszeit-Einträge des Monats wechseln auf `submitted`.
     * - E-Mail an die Buchhaltung wird ausgelöst.
     *
     * @return array{success: bool, count: int, version: int, message: string}
     */
    public static function submitMonthTimesheet(int $instructorId, string $yearMonth, ?int $submittedBy = null): array
    {
        self::ensureTables();

        $fromDate = $yearMonth . '-01';
        $toDate   = date('Y-m-t', strtotime($fromDate));
        $now      = date('Y-m-d H:i:s');

        try {
            // 1. Bestehendes Timesheet prüfen
            $timesheet = self::getTimesheet($instructorId, $yearMonth);
            $currentStatus = $timesheet ? (string) $timesheet['status'] : self::STATUS_DRAFT;
            $currentVersion = $timesheet ? (int) $timesheet['pdf_version'] : 1;

            // Version erhöhen bei Neueinreichung nach reopened oder rejected
            $newVersion = $currentVersion;
            if ($timesheet && in_array($currentStatus, [self::STATUS_REOPENED, self::STATUS_REJECTED], true)) {
                $newVersion = $currentVersion + 1;
            }

            // 2. Arbeitszeiten für diesen Monat aktualisieren (draft/reopened -> submitted)
            $updateSql = "UPDATE `bs_coach_working_hours`
                          SET `status` = 'submitted', `updated_at` = :now
                          WHERE `instructor_id` = :inst_id
                            AND `date` >= :from AND `date` <= :to
                            AND `status` IN ('draft', 'reopened', 'rejected')";
            DB::execute($updateSql, [
                'inst_id' => $instructorId,
                'from'    => $fromDate,
                'to'      => $toDate,
                'now'     => $now,
            ]);
            $updatedRows = (int) DB::rowCount();

            // 3. Zusammenfassung und Lohnabzüge berechnen
            $summary = self::getMonthlyTimesheetSummary($instructorId, $yearMonth);
            $payroll = self::calculateSwissPayroll($instructorId, (float) $summary['total_amount']);

            // 4. Snapshot erstellen und Historie fortschreiben
            $history = [];
            if ($timesheet && !empty($timesheet['snapshot_json'])) {
                $decoded = json_decode((string) $timesheet['snapshot_json'], true);
                if (is_array($decoded)) {
                    $history = $decoded;
                }
            }

            $currentSnapshot = [
                'version'      => $newVersion,
                'year_month'   => $yearMonth,
                'submitted_at' => $now,
                'submitted_by' => $submittedBy,
                'total_hours'  => $summary['total_hours'],
                'gross_amount' => $payroll['gross_amount'],
                'deductions'   => $payroll,
                'net_amount'   => $payroll['net_amount'],
                'entries'      => self::getWorkingHours([
                    'instructor_id' => $instructorId,
                    'date_from'     => $fromDate,
                    'date_to'       => $toDate,
                ]),
            ];
            $history['v' . $newVersion] = $currentSnapshot;

            // 5. Timesheet-Kopfdatensatz upserten
            if ($timesheet) {
                DB::execute(
                    "UPDATE `bs_coach_timesheets` SET
                        `status`          = 'submitted',
                        `pdf_version`     = :version,
                        `total_hours`     = :hours,
                        `gross_amount`    = :gross,
                        `net_amount`      = :net,
                        `deductions_json` = :deductions,
                        `snapshot_json`   = :snapshots,
                        `rejection_reason`= NULL,
                        `submitted_at`    = :now,
                        `submitted_by`    = :sub_by,
                        `updated_at`      = :now2
                     WHERE `id` = :id",
                    [
                        'version'    => $newVersion,
                        'hours'      => $summary['total_hours'],
                        'gross'      => $payroll['gross_amount'],
                        'net'        => $payroll['net_amount'],
                        'deductions' => json_encode($payroll, JSON_UNESCAPED_UNICODE),
                        'snapshots'  => json_encode($history, JSON_UNESCAPED_UNICODE),
                        'now'        => $now,
                        'sub_by'     => $submittedBy,
                        'now2'       => $now,
                        'id'         => (int) $timesheet['id'],
                    ]
                );
            } else {
                DB::execute(
                    "INSERT INTO `bs_coach_timesheets` (
                        `instructor_id`, `year_month`, `status`, `pdf_version`, `total_hours`,
                        `gross_amount`, `net_amount`, `deductions_json`, `snapshot_json`,
                        `submitted_at`, `submitted_by`, `created_at`, `updated_at`
                    ) VALUES (
                        :inst_id, :ym, 'submitted', :version, :hours,
                        :gross, :net, :deductions, :snapshots,
                        :now, :sub_by, :now2, :now3
                    )",
                    [
                        'inst_id'    => $instructorId,
                        'ym'         => $yearMonth,
                        'version'    => $newVersion,
                        'hours'      => $summary['total_hours'],
                        'gross'      => $payroll['gross_amount'],
                        'net'        => $payroll['net_amount'],
                        'deductions' => json_encode($payroll, JSON_UNESCAPED_UNICODE),
                        'snapshots'  => json_encode($history, JSON_UNESCAPED_UNICODE),
                        'now'        => $now,
                        'sub_by'     => $submittedBy,
                        'now2'       => $now,
                        'now3'       => $now,
                    ]
                );
            }

            // 6. Audit-Logging
            self::logAudit('timesheet_submitted', $instructorId, $currentStatus, self::STATUS_SUBMITTED, [
                'year_month'   => $yearMonth,
                'version'      => $newVersion,
                'total_hours'  => $summary['total_hours'],
                'gross_amount' => $payroll['gross_amount'],
            ], $yearMonth);

            // 7. E-Mail an Buchhaltung / Admin
            if (class_exists('BoatingNotificationService')) {
                BoatingNotificationService::sendTimesheetSubmittedNotification($instructorId, $yearMonth, array_merge($summary, $payroll));
            }

            return [
                'success' => true,
                'count'   => $updatedRows,
                'version' => $newVersion,
                'message' => "Monatsrapport {$yearMonth} (Version {$newVersion}) erfolgreich eingereicht.",
            ];
        } catch (\Throwable $e) {
            error_log('CoachService::submitMonthTimesheet Fehler: ' . $e->getMessage());
            return [
                'success' => false,
                'count'   => 0,
                'version' => 1,
                'message' => 'Fehler beim Einreichen: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Genehmigt ein eingereichtes Timesheet (submitted -> approved).
     *
     * Nur für Benutzer mit boating.timesheets.approve / Admin.
     *
     * @return array{success: bool, message: string}
     */
    public static function approveMonthTimesheet(int $instructorId, string $yearMonth, ?int $approvedBy = null): array
    {
        self::ensureTables();

        $fromDate = $yearMonth . '-01';
        $toDate   = date('Y-m-t', strtotime($fromDate));
        $now      = date('Y-m-d H:i:s');

        try {
            $timesheet = self::getTimesheet($instructorId, $yearMonth);
            $oldStatus = $timesheet ? (string) $timesheet['status'] : 'none';

            // Arbeitsstunden auf approved setzen
            DB::execute(
                "UPDATE `bs_coach_working_hours` SET `status` = 'approved', `updated_at` = :now
                 WHERE `instructor_id` = :id AND `date` >= :from AND `date` <= :to",
                ['id' => $instructorId, 'from' => $fromDate, 'to' => $toDate, 'now' => $now]
            );

            // Timesheet Kopf aktualisieren
            DB::execute(
                "UPDATE `bs_coach_timesheets` SET
                    `status` = 'approved', `approved_at` = :now, `approved_by` = :by, `updated_at` = :now2
                 WHERE `instructor_id` = :id AND `year_month` = :ym",
                ['id' => $instructorId, 'ym' => $yearMonth, 'now' => $now, 'by' => $approvedBy, 'now2' => $now]
            );

            // Audit-Logging
            self::logAudit('timesheet_approved', $instructorId, $oldStatus, self::STATUS_APPROVED, [
                'year_month'  => $yearMonth,
                'approved_by' => $approvedBy,
            ], $yearMonth);

            // E-Mail an Coach
            if (class_exists('BoatingNotificationService')) {
                $summary = self::getMonthlyTimesheetSummary($instructorId, $yearMonth);
                $payroll = self::calculateSwissPayroll($instructorId, (float) $summary['total_amount']);
                BoatingNotificationService::sendTimesheetApprovedNotification($instructorId, $yearMonth, array_merge($summary, $payroll));
            }

            return ['success' => true, 'message' => "Monatsrapport {$yearMonth} erfolgreich genehmigt."];
        } catch (\Throwable $e) {
            error_log('CoachService::approveMonthTimesheet Fehler: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Fehler bei der Genehmigung: ' . $e->getMessage()];
        }
    }

    /**
     * Weist ein eingereichtes Timesheet zurück (submitted -> rejected).
     *
     * Das Begründungsfeld $rejectionReason ist ein Pflichtfeld.
     *
     * @return array{success: bool, message: string}
     */
    public static function rejectMonthTimesheet(
        int $instructorId,
        string $yearMonth,
        string $rejectionReason,
        ?int $rejectedBy = null
    ): array {
        self::ensureTables();

        $reason = trim($rejectionReason);
        if ($reason === '') {
            return [
                'success' => false,
                'message' => 'Bitte geben Sie eine Begründung für die Zurückweisung an (Pflichtfeld).',
            ];
        }

        $fromDate = $yearMonth . '-01';
        $toDate   = date('Y-m-t', strtotime($fromDate));
        $now      = date('Y-m-d H:i:s');

        try {
            $timesheet = self::getTimesheet($instructorId, $yearMonth);
            $oldStatus = $timesheet ? (string) $timesheet['status'] : 'none';

            // Arbeitsstunden auf rejected setzen
            DB::execute(
                "UPDATE `bs_coach_working_hours` SET `status` = 'rejected', `updated_at` = :now
                 WHERE `instructor_id` = :id AND `date` >= :from AND `date` <= :to",
                ['id' => $instructorId, 'from' => $fromDate, 'to' => $toDate, 'now' => $now]
            );

            // Timesheet Kopf aktualisieren
            DB::execute(
                "UPDATE `bs_coach_timesheets` SET
                    `status` = 'rejected', `rejection_reason` = :reason, `updated_at` = :now
                 WHERE `instructor_id` = :id AND `year_month` = :ym",
                ['id' => $instructorId, 'ym' => $yearMonth, 'reason' => $reason, 'now' => $now]
            );

            // Audit-Logging
            self::logAudit('timesheet_rejected', $instructorId, $oldStatus, self::STATUS_REJECTED, [
                'year_month'       => $yearMonth,
                'rejection_reason' => $reason,
                'rejected_by'      => $rejectedBy,
            ], $yearMonth);

            // E-Mail an Coach
            if (class_exists('BoatingNotificationService')) {
                $summary = self::getMonthlyTimesheetSummary($instructorId, $yearMonth);
                BoatingNotificationService::sendTimesheetRejectedNotification($instructorId, $yearMonth, $reason, $summary);
            }

            return ['success' => true, 'message' => "Monatsrapport {$yearMonth} wurde zurückgewiesen. Coach wurde per E-Mail informiert."];
        } catch (\Throwable $e) {
            error_log('CoachService::rejectMonthTimesheet Fehler: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Fehler beim Zurückweisen: ' . $e->getMessage()];
        }
    }

    /**
     * Öffnet ein bereits genehmigtes (oder abgewickeltes) Timesheet wieder für Korrekturen (approved -> reopened).
     *
     * Anforderung Spezifikation Punkt 6:
     * - Erlaubt nur für Admin/Buchhaltung (Permission boating.timesheets.approve).
     * - Der bestehende Daten-Snapshot in snapshot_json wird NICHT gelöscht oder überschrieben!
     * - Stattdessen bleibt der alte Snapshot historisch erhalten.
     * - Arbeitszeit-Einträge des Monats erhalten Status 'draft' (oder 'reopened'), damit der Coach Korrekturen vornehmen kann.
     * - Bei erneuter Einreichung wird pdf_version automatisch um 1 erhöht (Version +1) und ein neuer Snapshot hinzugefügt.
     *
     * @return array{success: bool, message: string}
     */
    public static function reopenMonthTimesheet(int $instructorId, string $yearMonth, ?int $reopenedBy = null): array
    {
        self::ensureTables();

        $fromDate = $yearMonth . '-01';
        $toDate   = date('Y-m-t', strtotime($fromDate));
        $now      = date('Y-m-d H:i:s');

        try {
            $timesheet = self::getTimesheet($instructorId, $yearMonth);
            if (!$timesheet) {
                return ['success' => false, 'message' => 'Kein Timesheet für diesen Monat gefunden.'];
            }

            $oldStatus = (string) $timesheet['status'];

            // 1. Arbeitsstunden wieder freigeben (zurück auf draft)
            DB::execute(
                "UPDATE `bs_coach_working_hours` SET `status` = 'draft', `updated_at` = :now
                 WHERE `instructor_id` = :id AND `date` >= :from AND `date` <= :to",
                ['id' => $instructorId, 'from' => $fromDate, 'to' => $toDate, 'now' => $now]
            );

            // 2. Timesheet Kopf auf reopened setzen (bestehender snapshot_json bleibt unverändert erhalten!)
            DB::execute(
                "UPDATE `bs_coach_timesheets` SET
                    `status`      = 'reopened',
                    `reopened_at` = :now,
                    `reopened_by` = :by,
                    `updated_at`  = :now2
                 WHERE `id` = :id",
                [
                    'now'  => $now,
                    'by'   => $reopenedBy,
                    'now2' => $now,
                    'id'   => (int) $timesheet['id'],
                ]
            );

            // 3. Audit-Logging
            self::logAudit('timesheet_reopened', $instructorId, $oldStatus, self::STATUS_REOPENED, [
                'year_month'  => $yearMonth,
                'reopened_by' => $reopenedBy,
                'pdf_version' => $timesheet['pdf_version'],
            ], $yearMonth);

            return [
                'success' => true,
                'message' => "Monatsrapport {$yearMonth} wurde wiedereröffnet. Der Fahrlehrer kann seine Einträge nun überarbeiten. "
                           . "Bei erneuter Einreichung wird automatisch Version " . ((int) $timesheet['pdf_version'] + 1) . " erstellt.",
            ];
        } catch (\Throwable $e) {
            error_log('CoachService::reopenMonthTimesheet Fehler: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Fehler beim Wiedereröffnen: ' . $e->getMessage()];
        }
    }

    /**
     * Erstellt eine Monatsübersicht für die Schweizer Lohnabrechnung (CH Timesheet Summary)
     * inklusive Timesheet-Kopfstatus, Versionierung und detaillierten Lohnabzügen.
     *
     * @return array<string, mixed>
     */
    public static function getMonthlyTimesheetSummary(int $instructorId, string $yearMonth): array
    {
        self::ensureTables();

        $fromDate = $yearMonth . '-01';
        $toDate   = date('Y-m-t', strtotime($fromDate));

        $hours = self::getWorkingHours([
            'instructor_id' => $instructorId,
            'date_from'     => $fromDate,
            'date_to'       => $toDate,
        ]);

        $timesheet = self::getTimesheet($instructorId, $yearMonth);

        $summary = [
            'month'            => $yearMonth,
            'from'             => $fromDate,
            'to'               => $toDate,
            'total_entries'    => count($hours),
            'total_hours'      => 0.0,
            'total_amount'     => 0.00,
            'timesheet_status' => $timesheet ? (string) $timesheet['status'] : self::STATUS_DRAFT,
            'pdf_version'      => $timesheet ? (int) $timesheet['pdf_version'] : 1,
            'rejection_reason' => $timesheet ? (string) ($timesheet['rejection_reason'] ?? '') : '',
            'submitted_at'     => $timesheet ? ($timesheet['submitted_at'] ?? null) : null,
            'approved_at'      => $timesheet ? ($timesheet['approved_at'] ?? null) : null,
            'reopened_at'      => $timesheet ? ($timesheet['reopened_at'] ?? null) : null,
            'status_counts'    => [
                'draft'     => 0,
                'submitted' => 0,
                'approved'  => 0,
                'rejected'  => 0,
                'reopened'  => 0,
                'exported'  => 0,
                'paid'      => 0,
            ],
            'category_hours'   => [
                'lesson'      => 0.0,
                'exam'        => 0.0,
                'admin'       => 0.0,
                'maintenance' => 0.0,
                'other'       => 0.0,
            ],
        ];

        foreach ($hours as $h) {
            $th  = (float) ($h['total_hours'] ?? 0.0);
            $ta  = (float) ($h['total_amount'] ?? 0.0);
            $st  = (string) ($h['status'] ?? 'draft');
            $cat = (string) ($h['category'] ?? 'lesson');

            $summary['total_hours']  += $th;
            $summary['total_amount'] += $ta;

            if (isset($summary['status_counts'][$st])) {
                $summary['status_counts'][$st]++;
            }
            if (isset($summary['category_hours'][$cat])) {
                $summary['category_hours'][$cat] += $th;
            }
        }

        $summary['total_hours']  = round($summary['total_hours'], 2);
        $summary['total_amount'] = round($summary['total_amount'], 2);

        // Schweizer Lohnabzüge hinzufügen
        $payroll = self::calculateSwissPayroll($instructorId, $summary['total_amount']);
        $summary['payroll'] = $payroll;
        $summary['gross_amount'] = $payroll['gross_amount'];
        $summary['net_amount'] = $payroll['net_amount'];
        $summary['deductions_total'] = $payroll['deductions_total'];

        return $summary;
    }

    /**
     * Lädt alle Timesheet-Datensätze mit Instructor-Informationen.
     *
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public static function getAllTimesheets(array $filters = []): array
    {
        self::ensureTables();

        $sql = "SELECT ts.*, i.first_name, i.last_name, i.email, i.payroll_external_code, i.color_code
                FROM `bs_coach_timesheets` ts
                JOIN `bs_instructors` i ON ts.`instructor_id` = i.`id`
                WHERE 1=1";
        $params = [];

        if (!empty($filters['instructor_id'])) {
            $sql .= " AND ts.`instructor_id` = :inst_id";
            $params['inst_id'] = (int) $filters['instructor_id'];
        }
        if (!empty($filters['year_month'])) {
            $sql .= " AND ts.`year_month` = :ym";
            $params['ym'] = (string) $filters['year_month'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND ts.`status` = :st";
            $params['st'] = (string) $filters['status'];
        }

        $sql .= " ORDER BY ts.`year_month` DESC, ts.`instructor_id` ASC";

        try {
            return DB::fetchAll($sql, $params);
        } catch (\Throwable $e) {
            error_log('CoachService::getAllTimesheets Fehler: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Lädt Audit-Log Einträge.
     *
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public static function getAuditLogs(array $filters = [], int $limit = 50): array
    {
        self::ensureTables();

        $sql = "SELECT al.*, i.first_name AS instructor_first_name, i.last_name AS instructor_last_name
                FROM `bs_audit_logs` al
                LEFT JOIN `bs_instructors` i ON al.`instructor_id` = i.`id`
                WHERE 1=1";
        $params = [];

        if (!empty($filters['instructor_id'])) {
            $sql .= " AND al.`instructor_id` = :inst_id";
            $params['inst_id'] = (int) $filters['instructor_id'];
        }
        if (!empty($filters['action'])) {
            $sql .= " AND al.`action` = :act";
            $params['act'] = (string) $filters['action'];
        }

        $sql .= " ORDER BY al.`created_at` DESC LIMIT " . max(1, min(200, $limit));

        try {
            return DB::fetchAll($sql, $params);
        } catch (\Throwable $e) {
            error_log('CoachService::getAuditLogs Fehler: ' . $e->getMessage());
            return [];
        }
    }

    // =========================================================================
    // 6. ZENTRALES AUDIT-LOGGING (bs_audit_logs & ActivityLog)
    // =========================================================================

    /**
     * Schreibt einen sicherheitsrelevanten Eintrag in das zentrale Audit-Log.
     *
     * Protokolliert insbesondere:
     * - Anlage/Änderung von Abwesenheiten & Konfliktüberschreibungen
     * - Statuswechsel im Timesheet (draft->submitted, submitted->approved, etc.)
     *
     * @param array<string, mixed> $details
     */
    public static function logAudit(
        string $action,
        ?int $instructorId,
        ?string $oldStatus,
        ?string $newStatus,
        array $details = [],
        ?string $refId = null
    ): void {
        self::ensureTables();

        $userId = null;
        $userRole = null;
        $userEmail = null;

        if (class_exists('BoatingSchoolService')) {
            $user = BoatingSchoolService::getCurrentUser();
            if ($user) {
                $userId    = !empty($user['id']) ? (int) $user['id'] : null;
                $userRole  = (string) ($user['role'] ?? 'user');
                $userEmail = (string) ($user['email'] ?? null);
            }
        } elseif (!empty($_SESSION['user_id'])) {
            $userId = (int) $_SESSION['user_id'];
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $now = date('Y-m-d H:i:s');

        try {
            DB::execute(
                'INSERT INTO `bs_audit_logs` (
                    `user_id`, `user_role`, `action`, `instructor_id`, `reference_id`,
                    `old_status`, `new_status`, `details_json`, `ip_address`, `created_at`
                ) VALUES (
                    :uid, :role, :act, :inst_id, :ref_id,
                    :old_st, :new_st, :details, :ip, :now
                )',
                [
                    'uid'      => $userId,
                    'role'     => $userRole,
                    'act'      => $action,
                    'inst_id'  => $instructorId,
                    'ref_id'   => $refId,
                    'old_st'   => $oldStatus,
                    'new_st'   => $newStatus,
                    'details'  => !empty($details) ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
                    'ip'       => $ip,
                    'now'      => $now,
                ]
            );
        } catch (\Throwable $e) {
            error_log('CoachService::logAudit DB Fehler: ' . $e->getMessage());
        }

        // Auch im Core-ActivityLog registrieren
        if (class_exists('ActivityLog')) {
            try {
                ActivityLog::log('boating_coach.' . $action, $userEmail, array_merge($details, [
                    'instructor_id' => $instructorId,
                    'old_status'    => $oldStatus,
                    'new_status'    => $newStatus,
                    'reference_id'  => $refId,
                ]));
            } catch (\Throwable $e) {
                // Ignore
            }
        }
    }
}

// Namespace-Alias für modulares CMS
if (!class_exists('Modules\\BoatingSchool\\CoachService', false)) {
    class_alias(CoachService::class, 'Modules\\BoatingSchool\\CoachService');
}
