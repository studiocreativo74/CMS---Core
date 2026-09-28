<?php

declare(strict_types=1);

/**
 * SecurePortalRepository
 *
 * Data-Access-Layer für das Sicherungsportal (Polizei / Staatsanwaltschaft).
 * Verwaltet Anträge, Editionsverfügungen, Statusverläufe und Zugriffscodes.
 *
 * SQL-Schema für externen Datenraum (Polizei-Download & Fristenverwaltung):
 * <code>
 * ALTER TABLE secure_cases
 *   ADD COLUMN available_at DATETIME NULL AFTER status,
 *   ADD COLUMN download_token CHAR(40) NULL AFTER access_code,
 *   ADD COLUMN download_expires_at DATETIME NULL AFTER download_token,
 *   ADD COLUMN download_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER download_expires_at;
 *
 * CREATE TABLE secure_download_logs (
 *     id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 *     case_id INT UNSIGNED NOT NULL,
 *     file_id INT UNSIGNED NOT NULL,
 *     download_token CHAR(40) NOT NULL,
 *     ip_address VARCHAR(45) NOT NULL,
 *     user_agent VARCHAR(512) NULL,
 *     downloaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 *     INDEX (case_id),
 *     INDEX (file_id)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 * </code>
 */
final class SecurePortalRepository
{
    private static ?bool $tableExists = null;
    private static ?bool $caseFilesTableExists = null;
    private static ?bool $downloadLogsTableExists = null;

    public const STATUS_NEW = 'new';
    public const STATUS_IN_REVIEW = 'in_review';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_CLARIFICATION = 'clarification_required';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_NEW => [
            'label' => 'Neu eingegangen',
            'badge' => 'bg-primary',
            'icon'  => 'bi-inbox',
            'description' => 'Der Antrag wurde übermittelt und wartet auf redaktionelle/technische Prüfung.'
        ],
        self::STATUS_IN_REVIEW => [
            'label' => 'In Prüfung',
            'badge' => 'bg-warning text-dark',
            'icon'  => 'bi-hourglass-split',
            'description' => 'Die Editionsverfügung und Zuständigkeiten werden formal und rechtlich geprüft.'
        ],
        self::STATUS_IN_PROGRESS => [
            'label' => 'In Bearbeitung',
            'badge' => 'bg-info text-dark',
            'icon'  => 'bi-gear-wide-connected',
            'description' => 'Die Datensicherung wird durch die IT/Technik aktiv durchgeführt.'
        ],
        self::STATUS_AVAILABLE => [
            'label' => 'Bereitgestellt',
            'badge' => 'bg-success',
            'icon'  => 'bi-check-circle-fill',
            'description' => 'Die Sicherungsdaten stehen zur Abholung bzw. Übergabe bereit.'
        ],
        self::STATUS_CLARIFICATION => [
            'label' => 'Rückfrage / Klärung',
            'badge' => 'bg-danger',
            'icon'  => 'bi-question-octagon-fill',
            'description' => 'Es bestehen Rückfragen an den Antragsteller (z. B. unvollständige Angaben oder Fristen).'
        ],
        self::STATUS_CLOSED => [
            'label' => 'Abgeschlossen',
            'badge' => 'bg-secondary',
            'icon'  => 'bi-archive-fill',
            'description' => 'Der Vorgang wurde erfolgreich übergeben und abgeschlossen.'
        ],
        self::STATUS_ARCHIVED => [
            'label' => 'Archiviert',
            'badge' => 'bg-dark',
            'icon'  => 'bi-folder-x',
            'description' => 'Der Vorgang ist nach Ablauf der Aufbewahrungsfrist archiviert.'
        ],
    ];

    public const SECURING_TYPES = [
        'VIDEO' => [
            'label' => 'Videoüberwachung (CCTV / Aufzeichnungen)',
            'short' => 'Videoüberwachung',
            'icon'  => 'bi-camera-video',
            'desc'  => 'Sicherung von Videoaufzeichnungen bestimmter Kameras und Zeiträume'
        ],
        'MAIL' => [
            'label' => 'E-Mail-Postfach / Mail-Server',
            'short' => 'E-Mail',
            'icon'  => 'bi-envelope-at',
            'desc'  => 'Sicherung von Ein- und Ausgängen bestimmter E-Mail-Konten'
        ],
        'CLOUD' => [
            'label' => 'Cloud-, Datei- & Serverdaten',
            'short' => 'Cloud / Server',
            'icon'  => 'bi-cloud-arrow-down',
            'desc'  => 'Sicherung von Verzeichnissen, Serverlogs, Backups oder Cloud-Dateien'
        ],
        'ACCESS_LOG' => [
            'label' => 'Zutritts- & Schliessprotokolle',
            'short' => 'Zutrittsprotokolle',
            'icon'  => 'bi-key',
            'desc'  => 'Sicherung elektronischer Zutrittsprotokolle, Transponder- und Schliessdaten'
        ],
        'OTHER' => [
            'label' => 'Sonstige elektronische Beweismittel',
            'short' => 'Sonstiges',
            'icon'  => 'bi-hdd-network',
            'desc'  => 'Individuelle Sicherungsanforderungen gemäss Editionsverfügung'
        ],
    ];

    private function __construct()
    {
    }

    /**
     * Prüft, ob die Tabelle `secure_cases` existiert.
     */
    public static function isTableCreated(): bool
    {
        if (self::$tableExists !== null) {
            return self::$tableExists;
        }

        try {
            $stmt = DB::query("SHOW TABLES LIKE 'secure_cases'");
            self::$tableExists = ($stmt->fetch() !== false);
        } catch (\Throwable $e) {
            error_log('SecurePortalRepository::isTableCreated Fehler: ' . $e->getMessage());
            self::$tableExists = false;
        }

        return self::$tableExists;
    }

    /**
     * Prüft, ob die Tabelle `secure_case_files` existiert.
     */
    public static function isCaseFilesTableCreated(): bool
    {
        if (self::$caseFilesTableExists !== null) {
            return self::$caseFilesTableExists;
        }

        try {
            $stmt = DB::query("SHOW TABLES LIKE 'secure_case_files'");
            self::$caseFilesTableExists = ($stmt->fetch() !== false);
        } catch (\Throwable $e) {
            self::$caseFilesTableExists = false;
        }

        return self::$caseFilesTableExists;
    }

    /**
     * Erstellt die Tabelle `secure_case_files` bei Bedarf automatisch.
     */
    public static function ensureCaseFilesTable(): bool
    {
        if (self::isCaseFilesTableCreated()) {
            return true;
        }

        try {
            DB::query("
                CREATE TABLE IF NOT EXISTS `secure_case_files` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `case_id` INT UNSIGNED NOT NULL,
                    `file_name` VARCHAR(255) NOT NULL,
                    `file_path` VARCHAR(512) NOT NULL,
                    `mime_type` VARCHAR(128) NOT NULL,
                    `file_size` BIGINT UNSIGNED NOT NULL,
                    `sha256` VARCHAR(64) NOT NULL,
                    `uploaded_by` INT UNSIGNED NULL,
                    `uploaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                    INDEX `idx_scf_case` (`case_id`),
                    CONSTRAINT `fk_scf_case` FOREIGN KEY (`case_id`) REFERENCES `secure_cases` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            self::$caseFilesTableExists = true;
            return true;
        } catch (\Throwable $e) {
            error_log('SecurePortalRepository::ensureCaseFilesTable Fehler: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Stellt sicher, dass die Spalten für den externen Datenraum (Download-Token & Fristen) vorhanden sind.
     */
    public static function ensureDownloadColumns(): void
    {
        try {
            $stmt = DB::query("SHOW COLUMNS FROM `secure_cases` LIKE 'available_at'");
            if ($stmt->fetch() === false) {
                DB::query("ALTER TABLE `secure_cases` ADD COLUMN `available_at` DATETIME NULL AFTER `status`");
            }

            $stmt2 = DB::query("SHOW COLUMNS FROM `secure_cases` LIKE 'download_token'");
            if ($stmt2->fetch() === false) {
                DB::query("
                    ALTER TABLE `secure_cases`
                    ADD COLUMN `download_token` CHAR(40) NULL AFTER `access_code`,
                    ADD COLUMN `download_expires_at` DATETIME NULL AFTER `download_token`,
                    ADD COLUMN `download_enabled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `download_expires_at`,
                    ADD INDEX `idx_sc_dl_token` (`download_token`)
                ");
            }
        } catch (\Throwable $e) {
            // Ignorieren falls Spalten bereits existieren oder DB schreibgeschützt ist
        }
    }

    /**
     * Stellt sicher, dass die Tabelle `secure_download_logs` existiert.
     */
    public static function ensureDownloadLogsTable(): bool
    {
        if (self::$downloadLogsTableExists !== null) {
            return self::$downloadLogsTableExists;
        }

        try {
            DB::query("
                CREATE TABLE IF NOT EXISTS `secure_download_logs` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `case_id` INT UNSIGNED NOT NULL,
                    `file_id` INT UNSIGNED NOT NULL,
                    `download_token` CHAR(40) NOT NULL,
                    `ip_address` VARCHAR(45) NOT NULL,
                    `user_agent` VARCHAR(512) NULL,
                    `downloaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    INDEX `idx_sdl_case` (`case_id`),
                    INDEX `idx_sdl_file` (`file_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            self::$downloadLogsTableExists = true;
            return true;
        } catch (\Throwable $e) {
            error_log('SecurePortalRepository::ensureDownloadLogsTable Fehler: ' . $e->getMessage());
            self::$downloadLogsTableExists = false;
            return false;
        }
    }

    /**
     * Erstellt Tabellen bei Bedarf automatisch (Zero-Friction-Bootstrap).
     */
    public static function ensureTables(): bool
    {
        self::ensureCaseFilesTable();
        self::ensureDownloadColumns();
        self::ensureDownloadLogsTable();

        if (self::isTableCreated()) {
            return true;
        }

        try {
            DB::query("
                CREATE TABLE IF NOT EXISTS `secure_cases` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `case_number` VARCHAR(32) NOT NULL UNIQUE,
                    `access_code` CHAR(12) NOT NULL,
                    `download_token` CHAR(40) NULL,
                    `download_expires_at` DATETIME NULL,
                    `download_enabled` TINYINT(1) NOT NULL DEFAULT 0,
                    `status` VARCHAR(32) NOT NULL DEFAULT 'new',
                    `available_at` DATETIME NULL,
                    `police_department` VARCHAR(255) NOT NULL,
                    `contact_name` VARCHAR(255) NOT NULL,
                    `contact_email` VARCHAR(255) NOT NULL,
                    `contact_phone` VARCHAR(64) NOT NULL,
                    `reference_number` VARCHAR(128) NOT NULL,
                    `description` TEXT NOT NULL,
                    `desired_date` DATE NULL,
                    `remarks` TEXT NULL,
                    `warrant_file_path` VARCHAR(512) NOT NULL,
                    `warrant_mime` VARCHAR(128) NOT NULL,
                    `warrant_file_size` INT UNSIGNED NOT NULL,
                    `warrant_uploaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `securing_type` VARCHAR(64) NOT NULL,
                    `securing_meta` JSON NULL,
                    `sharepoint_item_id` VARCHAR(255) NULL,
                    `sharepoint_web_url` VARCHAR(1024) NULL,
                    `sharepoint_folder_url` VARCHAR(1024) NULL,
                    `sharepoint_synced_at` DATETIME NULL,
                    `assigned_user_id` INT UNSIGNED NULL,
                    `internal_notes` TEXT NULL,
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX `idx_sc_case_num` (`case_number`),
                    INDEX `idx_sc_status` (`status`),
                    INDEX `idx_sc_dept` (`police_department`),
                    INDEX `idx_sc_ref` (`reference_number`),
                    INDEX `idx_sc_type` (`securing_type`),
                    INDEX `idx_sc_dl_token` (`download_token`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            DB::query("
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            self::$tableExists = true;
            return true;
        } catch (\Throwable $e) {
            error_log('SecurePortalRepository::ensureTables Fehler: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Erzeugt eine eindeutige Vorgangsnummer im Format POL-YYYY-XXXXXX
     */
    public static function generateCaseNumber(): string
    {
        self::ensureTables();
        $year = date('Y');

        for ($i = 0; $i < 20; $i++) {
            $num = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            $candidate = "POL-{$year}-{$num}";

            try {
                $existing = DB::fetchOne(
                    'SELECT `id` FROM `secure_cases` WHERE `case_number` = :case_number LIMIT 1',
                    ['case_number' => $candidate]
                );
                if (!$existing) {
                    return $candidate;
                }
            } catch (\Throwable) {
                return $candidate;
            }
        }

        return "POL-{$year}-" . bin2hex(random_bytes(3));
    }

    /**
     * Erzeugt einen sicheren 12-stelligen Zugangscode (ohne verwechslungsgefährdete Zeichen).
     */
    public static function generateAccessCode(): string
    {
        $alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $len = strlen($alphabet);
        $code = '';
        for ($i = 0; $i < 12; $i++) {
            $code .= $alphabet[random_int(0, $len - 1)];
        }
        return $code;
    }

    /**
     * Normalisiert eingegebene Zugangscodes (entfernt Leerzeichen, Bindestriche, Uppercase).
     */
    public static function normalizeAccessCode(string $code): string
    {
        return strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $code));
    }

    /**
     * Erstellt einen neuen Sicherungsvorgang (Case) und einen Log-Eintrag.
     *
     * @param array<string, mixed> $data
     * @return array{id: int, case_number: string, access_code: string}
     */
    public static function createCase(array $data): array
    {
        self::ensureTables();

        $caseNumber = self::generateCaseNumber();
        $accessCode = self::generateAccessCode();

        $securingMeta = null;
        if (!empty($data['securing_meta'])) {
            $securingMeta = is_string($data['securing_meta'])
                ? $data['securing_meta']
                : json_encode($data['securing_meta'], JSON_UNESCAPED_UNICODE);
        }

        $sql = "INSERT INTO `secure_cases` (
            `case_number`,
            `access_code`,
            `status`,
            `police_department`,
            `contact_name`,
            `contact_email`,
            `contact_phone`,
            `reference_number`,
            `description`,
            `desired_date`,
            `remarks`,
            `warrant_file_path`,
            `warrant_mime`,
            `warrant_file_size`,
            `warrant_uploaded_at`,
            `securing_type`,
            `securing_meta`,
            `created_at`,
            `updated_at`
        ) VALUES (
            :case_number,
            :access_code,
            :status,
            :police_department,
            :contact_name,
            :contact_email,
            :contact_phone,
            :reference_number,
            :description,
            :desired_date,
            :remarks,
            :warrant_file_path,
            :warrant_mime,
            :warrant_file_size,
            NOW(),
            :securing_type,
            :securing_meta,
            NOW(),
            NOW()
        )";

        $params = [
            'case_number'        => $caseNumber,
            'access_code'        => $accessCode,
            'status'             => self::STATUS_NEW,
            'police_department'  => trim((string) ($data['police_department'] ?? '')),
            'contact_name'       => trim((string) ($data['contact_name'] ?? '')),
            'contact_email'      => trim((string) ($data['contact_email'] ?? '')),
            'contact_phone'      => trim((string) ($data['contact_phone'] ?? '')),
            'reference_number'   => trim((string) ($data['reference_number'] ?? '')),
            'description'        => trim((string) ($data['description'] ?? '')),
            'desired_date'       => !empty($data['desired_date']) ? (string) $data['desired_date'] : null,
            'remarks'            => !empty($data['remarks']) ? trim((string) $data['remarks']) : null,
            'warrant_file_path'  => trim((string) ($data['warrant_file_path'] ?? '')),
            'warrant_mime'       => trim((string) ($data['warrant_mime'] ?? 'application/pdf')),
            'warrant_file_size'  => (int) ($data['warrant_file_size'] ?? 0),
            'securing_type'      => trim((string) ($data['securing_type'] ?? 'VIDEO')),
            'securing_meta'      => $securingMeta,
        ];

        DB::query($sql, $params);
        $caseId = (int) DB::getConnection()->lastInsertId();

        // Initialer Log-Eintrag
        self::addCaseLog(
            $caseId,
            'created',
            sprintf(
                'Antrag erfolgreich eingereicht durch %s (%s). Aktenzeichen: %s. Editionsverfügung hinterlegt.',
                $params['police_department'],
                $params['contact_name'],
                $params['reference_number']
            ),
            'System / Antragsteller',
            false
        );

        return [
            'id'          => $caseId,
            'case_number' => $caseNumber,
            'access_code' => $accessCode,
        ];
    }

    /**
     * Authentifiziert den Fallzugang für einen Antragsteller anhand von Vorgangs-ID und Zugangscode.
     */
    public static function findCaseByCredentials(string $caseNumber, string $accessCode): ?array
    {
        if (!self::isTableCreated()) {
            return null;
        }

        $cleanCaseNumber = strtoupper(trim($caseNumber));
        $cleanAccessCode = self::normalizeAccessCode($accessCode);

        if ($cleanCaseNumber === '' || $cleanAccessCode === '') {
            return null;
        }

        try {
            $row = DB::fetchOne(
                'SELECT * FROM `secure_cases` WHERE UPPER(`case_number`) = :case_number LIMIT 1',
                ['case_number' => $cleanCaseNumber]
            );

            if (!$row) {
                return null;
            }

            // Zugangscode vergleichen (toleriert Formatierung)
            $storedCode = self::normalizeAccessCode((string) ($row['access_code'] ?? ''));
            if ($storedCode !== $cleanAccessCode) {
                return null;
            }

            return self::hydrateCase($row);
        } catch (\Throwable $e) {
            error_log('SecurePortalRepository::findCaseByCredentials Fehler: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Lädt einen Fall anhand seiner internen ID.
     */
    public static function getCaseById(int $id): ?array
    {
        if (!self::isTableCreated() || $id <= 0) {
            return null;
        }

        try {
            $row = DB::fetchOne('SELECT * FROM `secure_cases` WHERE `id` = :id LIMIT 1', ['id' => $id]);
            return $row ? self::hydrateCase($row) : null;
        } catch (\Throwable $e) {
            error_log('SecurePortalRepository::getCaseById Fehler: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Lädt Fälle mit Filter- und Paginierungsunterstützung für das Admin-Backend.
     *
     * @param array<string, mixed> $filters
     * @param int $limit
     * @param int $offset
     * @return array<int, array<string, mixed>>
     */
    public static function getCases(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        if (!self::isTableCreated()) {
            return [];
        }

        try {
            $where = [];
            $params = [];

            if (!empty($filters['status'])) {
                $where[] = '`status` = :status';
                $params['status'] = (string) $filters['status'];
            }

            if (!empty($filters['securing_type'])) {
                $where[] = '`securing_type` = :securing_type';
                $params['securing_type'] = (string) $filters['securing_type'];
            }

            if (!empty($filters['search'])) {
                $where[] = '(`case_number` LIKE :s OR `reference_number` LIKE :s OR `police_department` LIKE :s OR `contact_name` LIKE :s OR `contact_email` LIKE :s)';
                $params['s'] = '%' . trim((string) $filters['search']) . '%';
            }

            $sql = 'SELECT * FROM `secure_cases`';
            if (!empty($where)) {
                $sql .= ' WHERE ' . implode(' AND ', $where);
            }
            $sql .= ' ORDER BY `created_at` DESC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;

            $rows = DB::fetchAll($sql, $params);
            return array_map([self::class, 'hydrateCase'], $rows);
        } catch (\Throwable $e) {
            error_log('SecurePortalRepository::getCases Fehler: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Ermittelt die Anzahl der Fälle gemäss Filter.
     *
     * @param array<string, mixed> $filters
     */
    public static function countCases(array $filters = []): int
    {
        if (!self::isTableCreated()) {
            return 0;
        }

        try {
            $where = [];
            $params = [];

            if (!empty($filters['status'])) {
                $where[] = '`status` = :status';
                $params['status'] = (string) $filters['status'];
            }

            if (!empty($filters['securing_type'])) {
                $where[] = '`securing_type` = :securing_type';
                $params['securing_type'] = (string) $filters['securing_type'];
            }

            if (!empty($filters['search'])) {
                $where[] = '(`case_number` LIKE :s OR `reference_number` LIKE :s OR `police_department` LIKE :s OR `contact_name` LIKE :s OR `contact_email` LIKE :s)';
                $params['s'] = '%' . trim((string) $filters['search']) . '%';
            }

            $sql = 'SELECT COUNT(*) AS `c` FROM `secure_cases`';
            if (!empty($where)) {
                $sql .= ' WHERE ' . implode(' AND ', $where);
            }

            $row = DB::fetchOne($sql, $params);
            return (int) ($row['c'] ?? 0);
        } catch (\Throwable $e) {
            error_log('SecurePortalRepository::countCases Fehler: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Ermittelt statistische Zähler für das Admin-Dashboard.
     *
     * @return array<string, int>
     */
    public static function getStats(): array
    {
        $default = [
            'total'                  => 0,
            'new'                    => 0,
            'in_review'              => 0,
            'in_progress'            => 0,
            'available'              => 0,
            'clarification_required' => 0,
            'closed'                 => 0,
            'archived'               => 0,
        ];

        if (!self::isTableCreated()) {
            return $default;
        }

        try {
            $rows = DB::fetchAll('SELECT `status`, COUNT(*) AS `cnt` FROM `secure_cases` GROUP BY `status`');
            $stats = $default;
            $total = 0;
            foreach ($rows as $r) {
                $st = (string) ($r['status'] ?? '');
                $cnt = (int) ($r['cnt'] ?? 0);
                if (array_key_exists($st, $stats)) {
                    $stats[$st] = $cnt;
                }
                $total += $cnt;
            }
            $stats['total'] = $total;
            return $stats;
        } catch (\Throwable $e) {
            error_log('SecurePortalRepository::getStats Fehler: ' . $e->getMessage());
            return $default;
        }
    }

    /**
     * Aktualisiert Status, Zuweisung und interne Notizen eines Falles und protokolliert die Änderung.
     */
    public static function updateCase(
        int $caseId,
        string $status,
        ?string $internalNotes = null,
        ?int $assignedUserId = null,
        ?string $logMessage = null,
        string $author = 'Admin',
        bool $logIsInternal = false
    ): bool {
        if (!self::isTableCreated() || $caseId <= 0) {
            return false;
        }

        $current = self::getCaseById($caseId);
        if (!$current) {
            return false;
        }

        $validStatuses = array_keys(self::STATUSES);
        if (!in_array($status, $validStatuses, true)) {
            $status = $current['status'];
        }

        try {
            DB::query(
                'UPDATE `secure_cases` SET
                    `status` = :status,
                    `internal_notes` = :internal_notes,
                    `assigned_user_id` = :assigned_user_id,
                    `updated_at` = NOW()
                 WHERE `id` = :id',
                [
                    'id'               => $caseId,
                    'status'           => $status,
                    'internal_notes'   => $internalNotes,
                    'assigned_user_id' => $assignedUserId,
                ]
            );

            // Logeintrag verfassen
            $statusChanged = ($current['status'] !== $status);
            $msg = '';

            if ($statusChanged) {
                $oldLabel = self::getStatusLabel($current['status']);
                $newLabel = self::getStatusLabel($status);
                $msg .= "Status geändert: '{$oldLabel}' &rarr; '{$newLabel}'. ";
            }

            if (!empty($logMessage)) {
                $msg .= trim($logMessage);
            }

            if ($msg === '' && $statusChanged) {
                $msg = "Status aktualisiert auf " . self::getStatusLabel($status);
            }

            if ($msg !== '') {
                self::addCaseLog($caseId, 'status_update', $msg, $author, $logIsInternal);
            }

            return true;
        } catch (\Throwable $e) {
            error_log('SecurePortalRepository::updateCase Fehler: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Schreibt einen Eintrag in das Fallprotokoll.
     */
    public static function addCaseLog(
        int $caseId,
        string $action,
        string $message,
        string $author = 'System',
        bool $isInternal = false
    ): bool {
        if (!self::isTableCreated() || $caseId <= 0) {
            return false;
        }

        try {
            DB::query(
                'INSERT INTO `secure_case_logs` (`case_id`, `action`, `message`, `author`, `is_internal`, `created_at`)
                 VALUES (:case_id, :action, :message, :author, :is_internal, NOW())',
                [
                    'case_id'     => $caseId,
                    'action'      => $action,
                    'message'     => trim($message),
                    'author'      => trim($author),
                    'is_internal' => $isInternal ? 1 : 0,
                ]
            );
            return true;
        } catch (\Throwable $e) {
            error_log('SecurePortalRepository::addCaseLog Fehler: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Aktualisiert den relativen Dateipfad der Editionsverfügung im Vorgangsdatensatz.
     *
     * @param int $caseId
     * @param string $filePath
     * @return bool
     */
    public static function updateWarrantFilePath(int $caseId, string $filePath): bool
    {
        if (!self::isTableCreated() || $caseId <= 0) {
            return false;
        }

        try {
            DB::query(
                'UPDATE `secure_cases` SET `warrant_file_path` = :path, `updated_at` = NOW() WHERE `id` = :id',
                [
                    'path' => trim($filePath),
                    'id'   => $caseId,
                ]
            );
            return true;
        } catch (\Throwable $e) {
            error_log('SecurePortalRepository::updateWarrantFilePath Fehler: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Speichert die SharePoint-Referenzen zu einem Fall.
     * Schreibt primär in `securing_meta` (JSON) und versucht zugleich,
     * die dedizierten SharePoint-Spalten zu aktualisieren (sofern angelegt).
     *
     * @param int $caseId
     * @param array<string, mixed> $spData
     * @return bool
     */
    public static function updateSharePointReference(int $caseId, array $spData): bool
    {
        if (!self::isTableCreated() || $caseId <= 0) {
            return false;
        }

        try {
            $case = self::getCaseById($caseId);
            if (!$case) {
                return false;
            }

            $meta = (array) ($case['securing_meta_decoded'] ?? []);
            $meta['sharepoint'] = [
                'item_id'    => (string) ($spData['item_id'] ?? ''),
                'web_url'    => (string) ($spData['web_url'] ?? ''),
                'folder_url' => (string) ($spData['folder_url'] ?? ''),
                'synced_at'  => (string) ($spData['synced_at'] ?? date('Y-m-d H:i:s')),
            ];

            $jsonMeta = json_encode($meta, JSON_UNESCAPED_UNICODE);

            // Versuch 1: Dedizierte Spalten + JSON aktualisieren
            try {
                DB::query(
                    'UPDATE `secure_cases` 
                     SET `securing_meta` = :meta,
                         `sharepoint_item_id` = :item_id,
                         `sharepoint_web_url` = :web_url,
                         `sharepoint_folder_url` = :folder_url,
                         `sharepoint_synced_at` = NOW()
                     WHERE `id` = :id',
                    [
                        'meta'       => $jsonMeta,
                        'item_id'    => (string) ($spData['item_id'] ?? ''),
                        'web_url'    => (string) ($spData['web_url'] ?? ''),
                        'folder_url' => (string) ($spData['folder_url'] ?? ''),
                        'id'         => $caseId,
                    ]
                );
            } catch (\Throwable $colErr) {
                // Fallback: Falls die Spalten in der DB-Instanz noch fehlen, sichere Speicherung in securing_meta
                DB::query(
                    'UPDATE `secure_cases` SET `securing_meta` = :meta WHERE `id` = :id',
                    [
                        'meta' => $jsonMeta,
                        'id'   => $caseId,
                    ]
                );
            }

            return true;
        } catch (\Throwable $e) {
            error_log('SecurePortalRepository::updateSharePointReference Fehler: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ruft das Aktivitätenprotokoll für einen Fall ab.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getCaseLogs(int $caseId, bool $includeInternal = false): array
    {
        if (!self::isTableCreated() || $caseId <= 0) {
            return [];
        }

        try {
            $sql = 'SELECT * FROM `secure_case_logs` WHERE `case_id` = :case_id';
            if (!$includeInternal) {
                $sql .= ' AND `is_internal` = 0';
            }
            $sql .= ' ORDER BY `created_at` DESC, `id` DESC';

            return DB::fetchAll($sql, ['case_id' => $caseId]);
        } catch (\Throwable $e) {
            error_log('SecurePortalRepository::getCaseLogs Fehler: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Hilfsfunktion: Wandelt DB-Zeile in ein angereichertes Array um.
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function hydrateCase(array $row): array
    {
        $metaRaw = $row['securing_meta'] ?? null;
        $metaDecoded = [];
        if (!empty($metaRaw)) {
            $decoded = json_decode((string) $metaRaw, true);
            if (is_array($decoded)) {
                $metaDecoded = $decoded;
            }
        }
        $row['securing_meta_decoded'] = $metaDecoded;

        // SharePoint-Referenzen aus dedizierten Spalten oder JSON-Metadaten auflösen
        $spMeta = (array) ($metaDecoded['sharepoint'] ?? []);
        $row['sharepoint_item_id'] = !empty($row['sharepoint_item_id']) 
            ? (string) $row['sharepoint_item_id'] 
            : (!empty($spMeta['item_id']) ? (string) $spMeta['item_id'] : null);

        $row['sharepoint_web_url'] = !empty($row['sharepoint_web_url']) 
            ? (string) $row['sharepoint_web_url'] 
            : (!empty($spMeta['web_url']) ? (string) $spMeta['web_url'] : null);

        $row['sharepoint_folder_url'] = !empty($row['sharepoint_folder_url']) 
            ? (string) $row['sharepoint_folder_url'] 
            : (!empty($spMeta['folder_url']) ? (string) $spMeta['folder_url'] : null);

        $row['sharepoint_synced_at'] = !empty($row['sharepoint_synced_at']) 
            ? (string) $row['sharepoint_synced_at'] 
            : (!empty($spMeta['synced_at']) ? (string) $spMeta['synced_at'] : null);

        $st = (string) ($row['status'] ?? 'new');
        $statusInfo = self::STATUSES[$st] ?? [
            'label' => ucfirst($st),
            'badge' => 'bg-secondary',
            'icon'  => 'bi-info-circle',
            'description' => ''
        ];
        $row['status_label'] = $statusInfo['label'];
        $row['status_badge'] = $statusInfo['badge'];
        $row['status_icon']  = $statusInfo['icon'];
        $row['status_desc']  = $statusInfo['description'];

        $secType = (string) ($row['securing_type'] ?? 'VIDEO');
        $typeInfo = self::SECURING_TYPES[$secType] ?? [
            'label' => $secType,
            'short' => $secType,
            'icon'  => 'bi-folder',
            'desc'  => ''
        ];
        $row['securing_type_label'] = $typeInfo['label'];
        $row['securing_type_short'] = $typeInfo['short'];
        $row['securing_type_icon']  = $typeInfo['icon'];

        return $row;
    }

    public static function getStatusLabel(string $status): string
    {
        return self::STATUSES[$status]['label'] ?? ucfirst($status);
    }

    public static function getStatusBadgeClass(string $status): string
    {
        return self::STATUSES[$status]['badge'] ?? 'bg-secondary';
    }

    public static function getSecuringTypeLabel(string $type): string
    {
        return self::SECURING_TYPES[$type]['label'] ?? $type;
    }

    // =========================================================================
    // SICHERUNGSDATEIEN (SECURE_CASE_FILES) & STATUS-VERFÜGBARKEIT
    // =========================================================================

    /**
     * Speichert einen neuen Eintrag für eine Sicherungsdatei in `secure_case_files`.
     *
     * @param int $caseId Vorgangs-ID
     * @param string $fileName Ursprünglicher oder standardisierter Dateiname
     * @param string $filePath Relativer Pfad im Webspace (z. B. uploads/secure/cases/.../data/archiv.zip)
     * @param string $mimeType MIME-Typ (z. B. application/zip)
     * @param int $fileSize Dateigrösse in Bytes
     * @param string $sha256 SHA-256 Prüfsumme in Hex
     * @param int|null $uploadedBy Optional: ID des hochladenden Admins
     * @return int|null Eingefügte ID oder null bei Fehler
     */
    public static function addCaseFile(
        int $caseId,
        string $fileName,
        string $filePath,
        string $mimeType,
        int $fileSize,
        string $sha256,
        ?int $uploadedBy = null
    ): ?int {
        self::ensureCaseFilesTable();

        try {
            DB::execute(
                'INSERT INTO `secure_case_files` (
                    `case_id`, `file_name`, `file_path`, `mime_type`, `file_size`, `sha256`, `uploaded_by`, `uploaded_at`, `is_active`
                ) VALUES (
                    :case_id, :file_name, :file_path, :mime_type, :file_size, :sha256, :uploaded_by, NOW(), 1
                )',
                [
                    'case_id'     => $caseId,
                    'file_name'   => $fileName,
                    'file_path'   => $filePath,
                    'mime_type'   => $mimeType,
                    'file_size'   => $fileSize,
                    'sha256'      => strtolower(trim($sha256)),
                    'uploaded_by' => $uploadedBy,
                ]
            );

            $id = (int) DB::lastInsertId();
            return $id > 0 ? $id : null;
        } catch (\Throwable $e) {
            error_log('SecurePortalRepository::addCaseFile Fehler: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Lädt alle Sicherungsdateien eines Vorgangs.
     *
     * @param int $caseId Vorgangs-ID
     * @param bool $activeOnly Nur aktive Dateien laden (Standard: true)
     * @return array<int, array<string, mixed>>
     */
    public static function getCaseFiles(int $caseId, bool $activeOnly = true): array
    {
        self::ensureCaseFilesTable();

        try {
            $sql = 'SELECT * FROM `secure_case_files` WHERE `case_id` = :case_id';
            if ($activeOnly) {
                $sql .= ' AND `is_active` = 1';
            }
            $sql .= ' ORDER BY `uploaded_at` DESC, `id` DESC';

            $rows = DB::fetchAll($sql, ['case_id' => $caseId]);
            return is_array($rows) ? $rows : [];
        } catch (\Throwable $e) {
            error_log('SecurePortalRepository::getCaseFiles Fehler: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Lädt eine einzelne Sicherungsdatei anhand ihrer ID und Fall-ID.
     *
     * @param int $fileId Datei-ID
     * @param int $caseId Vorgangs-ID
     * @return array<string, mixed>|null
     */
    public static function getCaseFileById(int $fileId, int $caseId): ?array
    {
        self::ensureCaseFilesTable();

        try {
            $row = DB::fetchOne(
                'SELECT * FROM `secure_case_files` WHERE `id` = :id AND `case_id` = :case_id LIMIT 1',
                ['id' => $fileId, 'case_id' => $caseId]
            );
            return $row ?: null;
        } catch (\Throwable $e) {
            error_log('SecurePortalRepository::getCaseFileById Fehler: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Deaktiviert oder löscht eine Sicherungsdatei.
     *
     * @param int $fileId Datei-ID
     * @param int $caseId Vorgangs-ID
     * @param bool $permanent Bei true wird der DB-Eintrag gelöscht, sonst auf is_active=0 gesetzt
     * @return bool
     */
    public static function deleteCaseFile(int $fileId, int $caseId, bool $permanent = false): bool
    {
        self::ensureCaseFilesTable();

        try {
            if ($permanent) {
                DB::execute(
                    'DELETE FROM `secure_case_files` WHERE `id` = :id AND `case_id` = :case_id LIMIT 1',
                    ['id' => $fileId, 'case_id' => $caseId]
                );
            } else {
                DB::execute(
                    'UPDATE `secure_case_files` SET `is_active` = 0 WHERE `id` = :id AND `case_id` = :case_id LIMIT 1',
                    ['id' => $fileId, 'case_id' => $caseId]
                );
            }
            return true;
        } catch (\Throwable $e) {
            error_log('SecurePortalRepository::deleteCaseFile Fehler: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Zählt die aktiven Sicherungsdateien eines Vorgangs.
     *
     * @param int $caseId Vorgangs-ID
     * @return int
     */
    public static function countCaseFiles(int $caseId): int
    {
        self::ensureCaseFilesTable();

        try {
            $row = DB::fetchOne(
                'SELECT COUNT(*) AS `c` FROM `secure_case_files` WHERE `case_id` = :case_id AND `is_active` = 1',
                ['case_id' => $caseId]
            );
            return (int) ($row['c'] ?? 0);
        } catch (\Throwable $e) {
            error_log('SecurePortalRepository::countCaseFiles Fehler: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Setzt den Fallstatus auf „verfügbar“ (STATUS_AVAILABLE) und dokumentiert dies im Fall-Log.
     *
     * @param int $caseId Vorgangs-ID
     * @param string $author Name des Bearbeiters / Admins
     * @param string|null $customMessage Optionale individuelle Nachricht
     * @return bool
     */
    public static function setCaseAvailable(int $caseId, string $author = 'Admin', ?string $customMessage = null): bool
    {
        $case = self::getCaseById($caseId);
        if (!$case) {
            return false;
        }

        $filesCount = self::countCaseFiles($caseId);
        $defaultMsg = sprintf(
            'Sicherungsdaten wurden verifiziert und bereitgestellt (%d Datei(en) hinterlegt, SHA-256 Prüfsummen dokumentiert). Status auf „Bereitgestellt“ gesetzt.',
            $filesCount
        );

        $msg = ($customMessage !== null && trim($customMessage) !== '') ? trim($customMessage) : $defaultMsg;

        $updated = self::updateCase(
            $caseId,
            self::STATUS_AVAILABLE,
            $case['internal_notes'],
            $case['assigned_user_id'] ? (int) $case['assigned_user_id'] : null,
            $msg,
            $author,
            false // Öffentlich im Fallzugang für Antragsteller sichtbar
        );

        if ($updated) {
            // Automatisch Download-Freigabe aktivieren & Token sicherstellen
            $dlToken = (string) ($case['download_token'] ?? '');
            if ($dlToken === '' || strlen($dlToken) !== 40) {
                $dlToken = self::generateDownloadTokenHex();
            }

            $daysActive = class_exists('SecurePortalConfig')
                ? SecurePortalConfig::getDownloadDaysActive()
                : 30;

            try {
                DB::execute(
                    'UPDATE `secure_cases` SET 
                        `available_at` = COALESCE(`available_at`, NOW()),
                        `download_token` = :token,
                        `download_enabled` = 1,
                        `download_expires_at` = COALESCE(`download_expires_at`, DATE_ADD(NOW(), INTERVAL :days DAY)),
                        `updated_at` = NOW()
                     WHERE `id` = :id LIMIT 1',
                    [
                        'id'    => $caseId,
                        'token' => $dlToken,
                        'days'  => $daysActive,
                    ]
                );
                self::addCaseLog(
                    $caseId,
                    'download_enabled_on_available',
                    sprintf(
                        'Externer Datenraum für Polizei automatisch freigegeben (Token: %s..., reguläre Frist T+%d Tage). Bereitstellungszeitpunkt dokumentiert.',
                        substr($dlToken, 0, 10),
                        $daysActive
                    ),
                    $author,
                    true
                );
            } catch (\Throwable $e) {
                error_log('SecurePortalRepository::setCaseAvailable Download-Aktivierung Fehler: ' . $e->getMessage());
            }
        }

        return $updated;
    }

    // =========================================================================
    // EXTERNER DATENRAUM (POLIZEI-DOWNLOAD) & TOKEN-MANAGEMENT
    // =========================================================================

    /**
     * Erzeugt einen kryptographisch sicheren 40-Zeichen Hex-Token.
     */
    public static function generateDownloadTokenHex(): string
    {
        return bin2hex(random_bytes(20)); // 40 hex chars
    }

    /**
     * Sucht einen Vorgang anhand seines externen Download-Tokens.
     *
     * @param string $token 40-stelliger Hex-Token
     * @return array<string, mixed>|null
     */
    public static function findCaseByDownloadToken(string $token): ?array
    {
        self::ensureTables();
        $token = strtolower(trim($token));
        if ($token === '' || strlen($token) !== 40) {
            return null;
        }

        try {
            $row = DB::fetchOne(
                'SELECT * FROM `secure_cases` WHERE `download_token` = :token LIMIT 1',
                ['token' => $token]
            );
            return $row ?: null;
        } catch (\Throwable $e) {
            error_log('SecurePortalRepository::findCaseByDownloadToken Fehler: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Erzeugt oder regeneriert einen Download-Token für einen Fall und aktiviert/aktualisiert ihn.
     *
     * @param int $caseId Vorgangs-ID
     * @param bool $enabled Freigabestatus
     * @param string|null $expiresAt Ablaufdatum (JJJJ-MM-TT HH:MM:SS) oder null für unbegrenzt
     * @param string $author Name des handelnden Benutzers
     * @return string|null Der neu generierte Token
     */
    public static function issueDownloadToken(
        int $caseId,
        bool $enabled = true,
        ?string $expiresAt = null,
        string $author = 'Admin'
    ): ?string {
        self::ensureTables();
        $token = self::generateDownloadTokenHex();

        try {
            DB::execute(
                'UPDATE `secure_cases` SET 
                    `download_token` = :token,
                    `download_enabled` = :enabled,
                    `download_expires_at` = :expires_at,
                    `updated_at` = NOW()
                 WHERE `id` = :id LIMIT 1',
                [
                    'id'         => $caseId,
                    'token'      => $token,
                    'enabled'    => $enabled ? 1 : 0,
                    'expires_at' => ($expiresAt !== null && trim($expiresAt) !== '') ? trim($expiresAt) : null,
                ]
            );

            self::addCaseLog(
                $caseId,
                'download_token_issued',
                sprintf(
                    'Neuer Download-Token generiert (%s...). Freigabe: %s, Gültig bis: %s',
                    substr($token, 0, 10),
                    $enabled ? 'Aktiviert' : 'Deaktiviert',
                    $expiresAt ?: 'Unbegrenzt'
                ),
                $author,
                true
            );

            return $token;
        } catch (\Throwable $e) {
            error_log('SecurePortalRepository::issueDownloadToken Fehler: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Aktualisiert die Freigabeeinstellungen des externen Datenraums (Aktiv/Inaktiv, Ablaufdatum).
     *
     * @param int $caseId Vorgangs-ID
     * @param bool $enabled Freigabestatus
     * @param string|null $expiresAt Ablaufdatum oder null
     * @param string $author Name des Benutzers
     * @return bool
     */
    public static function updateDownloadAccess(
        int $caseId,
        bool $enabled,
        ?string $expiresAt,
        string $author = 'Admin'
    ): bool {
        self::ensureTables();
        $case = self::getCaseById($caseId);
        if (!$case) {
            return false;
        }

        // Falls noch kein Token existiert, automatisch generieren
        $token = (string) ($case['download_token'] ?? '');
        if ($token === '' || strlen($token) !== 40) {
            $token = self::generateDownloadTokenHex();
        }

        try {
            DB::execute(
                'UPDATE `secure_cases` SET 
                    `download_token` = :token,
                    `download_enabled` = :enabled,
                    `download_expires_at` = :expires_at,
                    `updated_at` = NOW()
                 WHERE `id` = :id LIMIT 1',
                [
                    'id'         => $caseId,
                    'token'      => $token,
                    'enabled'    => $enabled ? 1 : 0,
                    'expires_at' => ($expiresAt !== null && trim($expiresAt) !== '') ? trim($expiresAt) : null,
                ]
            );

            $statusText = $enabled ? 'Aktiviert' : 'Deaktiviert';
            self::addCaseLog(
                $caseId,
                'download_access_updated',
                sprintf('Externer Datenraum %s. Gültig bis: %s', $statusText, $expiresAt ?: 'Unbegrenzt'),
                $author,
                true
            );

            return true;
        } catch (\Throwable $e) {
            error_log('SecurePortalRepository::updateDownloadAccess Fehler: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Prüft, ob ein Fall und dessen Download-Freigabe aktuell gültig sind.
     *
     * @param array<string, mixed> $case
     * @return array{valid: bool, reason: ?string}
     */
    public static function checkDownloadValidity(array $case): array
    {
        $enabled = !empty($case['download_enabled']);
        if (!$enabled) {
            return [
                'valid'  => false,
                'reason' => 'Der Download-Bereich für diesen Vorgang ist aktuell deaktiviert oder wurde noch nicht freigegeben.',
            ];
        }

        // 1. Manuell gesetztes oder berechnetes Ablaufdatum prüfen
        $expiresAt = !empty($case['download_expires_at']) ? (string)$case['download_expires_at'] : null;
        if ($expiresAt !== null) {
            $expireTs = strtotime($expiresAt);
            if ($expireTs !== false && $expireTs < time()) {
                return [
                    'valid'  => false,
                    'reason' => sprintf('Der Freigabelink ist am %s Uhr abgelaufen.', date('d.m.Y H:i', $expireTs)),
                ];
            }
        }

        // 2. Reguläre Aufbewahrungsfrist ab Bereitstellung (T+X Tage) prüfen
        $availableAt = !empty($case['available_at']) ? (string)$case['available_at'] : null;
        if ($availableAt !== null) {
            $daysActive = class_exists('SecurePortalConfig')
                ? SecurePortalConfig::getDownloadDaysActive()
                : 30;
            $maxActiveTs = strtotime($availableAt . " +{$daysActive} days");
            if ($maxActiveTs !== false && $maxActiveTs < time()) {
                return [
                    'valid'  => false,
                    'reason' => sprintf('Die reguläre Download-Frist für diesen Vorgang (T+%d Tage nach Bereitstellung) ist am %s Uhr abgelaufen.', $daysActive, date('d.m.Y H:i', $maxActiveTs)),
                ];
            }
        }

        return ['valid' => true, 'reason' => null];
    }

    /**
     * Protokolliert den Download einer Sicherungsdatei in secure_download_logs und im Fall-Log (Audit Trail).
     */
    public static function recordFileDownload(
        int $caseId,
        int $fileId,
        string $token,
        string $fileName,
        string $ip,
        string $userAgent
    ): void {
        $ip = trim($ip) ?: 'unbekannt';
        $ua = trim($userAgent) ?: 'unbekannt';
        $shortUa = strlen($ua) > 150 ? (substr($ua, 0, 147) . '...') : $ua;

        // 1. Eintrag in die dedizierte Tabelle secure_download_logs
        try {
            self::ensureDownloadLogsTable();
            DB::execute(
                'INSERT INTO `secure_download_logs` (`case_id`, `file_id`, `download_token`, `ip_address`, `user_agent`, `downloaded_at`)
                 VALUES (:case_id, :file_id, :token, :ip, :ua, NOW())',
                [
                    'case_id' => $caseId,
                    'file_id' => $fileId,
                    'token'   => $token,
                    'ip'      => substr($ip, 0, 45),
                    'ua'      => substr($ua, 0, 512),
                ]
            );
        } catch (\Throwable $e) {
            error_log('SecurePortalRepository::recordFileDownload DB-Log Fehler: ' . $e->getMessage());
        }

        // 2. Eintrag im Aktivitäten-Protokoll des Vorgangs
        self::addCaseLog(
            $caseId,
            'police_download',
            sprintf('Sicherungsdatei „%s“ (ID %d) über externen Datenraum heruntergeladen. [IP: %s, Client: %s]', $fileName, $fileId, $ip, $shortUa),
            'Polizei (Download-Token)',
            false
        );
    }

    /**
     * Gibt die Download-Historie (Audit-Logs) für einen Fall zurück.
     *
     * @param int $caseId Vorgangs-ID
     * @return array<int, array<string, mixed>>
     */
    public static function getDownloadLogs(int $caseId): array
    {
        self::ensureDownloadLogsTable();
        try {
            return DB::fetchAll(
                'SELECT sdl.*, scf.file_name, scf.file_size
                 FROM `secure_download_logs` sdl
                 LEFT JOIN `secure_case_files` scf ON scf.id = sdl.file_id
                 WHERE sdl.case_id = :case_id
                 ORDER BY sdl.downloaded_at DESC',
                ['case_id' => $caseId]
            );
        } catch (\Throwable $e) {
            error_log('SecurePortalRepository::getDownloadLogs Fehler: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Führt die automatische Fristenprüfung und Bereinigung für den externen Datenraum durch:
     * - Phase 1 (T+30 / days_active): Externen Download-Zugang sperren (download_enabled = 0)
     * - Phase 2 (T+60 / days_delete): Sicherungsdateien löschen und Fallstatus aktualisieren
     *
     * @param int|null $daysActive Anzahl Tage bis Zugangssperre (Standard aus Settings oder 30)
     * @param int|null $daysDelete Anzahl Tage bis Dateilöschung (Standard aus Settings oder 60)
     * @param string $author Ausführender Benutzer/System
     * @return array{
     *     days_active: int,
     *     days_delete: int,
     *     locked_cases: array<int, string>,
     *     locked_count: int,
     *     purged_cases: array<int, string>,
     *     purged_files_count: int,
     *     purged_cases_count: int,
     *     errors: array<int, string>
     * }
     */
    public static function processRetentionDeadlines(
        ?int $daysActive = null,
        ?int $daysDelete = null,
        string $author = 'Fristen-Cron'
    ): array {
        self::ensureTables();

        if ($daysActive === null || $daysActive <= 0) {
            $daysActive = class_exists('SecurePortalConfig')
                ? SecurePortalConfig::getDownloadDaysActive()
                : 30;
        }

        if ($daysDelete === null || $daysDelete <= 0) {
            $daysDelete = class_exists('SecurePortalConfig')
                ? SecurePortalConfig::getDownloadDaysDelete()
                : 60;
        }

        $result = [
            'days_active'        => $daysActive,
            'days_delete'        => $daysDelete,
            'locked_cases'       => [],
            'locked_count'       => 0,
            'purged_cases'       => [],
            'purged_files_count' => 0,
            'purged_cases_count' => 0,
            'errors'             => [],
        ];

        // ---------------------------------------------------------------------
        // PHASE 1: T+daysActive - Download-Zugang sperren (download_enabled = 0)
        // ---------------------------------------------------------------------
        try {
            $casesToLock = DB::fetchAll(
                'SELECT id, case_number, available_at, download_expires_at 
                 FROM `secure_cases` 
                 WHERE `download_enabled` = 1 
                   AND (
                     (`available_at` IS NOT NULL AND `available_at` <= DATE_SUB(NOW(), INTERVAL :days DAY))
                     OR (`download_expires_at` IS NOT NULL AND `download_expires_at` <= NOW())
                   )',
                ['days' => $daysActive]
            );

            foreach ($casesToLock as $c) {
                $cid = (int) $c['id'];
                $cnum = (string) $c['case_number'];

                DB::execute(
                    'UPDATE `secure_cases` SET `download_enabled` = 0, `updated_at` = NOW() WHERE `id` = :id LIMIT 1',
                    ['id' => $cid]
                );

                self::addCaseLog(
                    $cid,
                    'retention_access_locked',
                    sprintf(
                        'Externer Download-Zugang nach Fristablauf (T+%d Tage nach Bereitstellung) automatisch gesperrt.',
                        $daysActive
                    ),
                    $author,
                    true
                );

                $result['locked_cases'][] = $cnum;
            }
            $result['locked_count'] = count($result['locked_cases']);
        } catch (\Throwable $e) {
            $err = 'Fehler bei Fristprüfung Phase 1 (Zugangssperre): ' . $e->getMessage();
            error_log('SecurePortalRepository::processRetentionDeadlines: ' . $err);
            $result['errors'][] = $err;
        }

        // ---------------------------------------------------------------------
        // PHASE 2: T+daysDelete - Sicherungsdateien löschen & Fallstatus aktualisieren
        // ---------------------------------------------------------------------
        try {
            $casesToPurge = DB::fetchAll(
                'SELECT sc.id, sc.case_number, sc.status, sc.available_at 
                 FROM `secure_cases` sc
                 JOIN `secure_case_files` scf ON scf.case_id = sc.id AND scf.is_active = 1
                 WHERE sc.available_at IS NOT NULL 
                   AND sc.available_at <= DATE_SUB(NOW(), INTERVAL :days DAY)
                 GROUP BY sc.id, sc.case_number, sc.status, sc.available_at',
                ['days' => $daysDelete]
            );

            foreach ($casesToPurge as $cp) {
                $cid = (int) $cp['id'];
                $cnum = (string) $cp['case_number'];
                $files = self::getCaseFiles($cid, true);
                $deletedForCase = 0;

                foreach ($files as $file) {
                    $relPath = (string) $file['file_path'];
                    $fullPath = class_exists('SecurePortalConfig')
                        ? SecurePortalConfig::resolveLocalFilePath($relPath)
                        : (dirname(__DIR__, 2) . '/' . ltrim($relPath, '/'));

                    if ($fullPath !== null && file_exists($fullPath) && is_file($fullPath)) {
                        @unlink($fullPath);
                    }

                    DB::execute(
                        'UPDATE `secure_case_files` SET `is_active` = 0 WHERE `id` = :id LIMIT 1',
                        ['id' => (int) $file['id']]
                    );
                    $deletedForCase++;
                }

                // Fallstatus aktualisieren (von "available" auf "closed")
                $currentStatus = (string) $cp['status'];
                $newStatus = ($currentStatus === self::STATUS_AVAILABLE) ? self::STATUS_CLOSED : $currentStatus;

                DB::execute(
                    'UPDATE `secure_cases` SET 
                        `status` = :new_status,
                        `download_enabled` = 0,
                        `updated_at` = NOW()
                     WHERE `id` = :id LIMIT 1',
                    [
                        'id'         => $cid,
                        'new_status' => $newStatus,
                    ]
                );

                self::addCaseLog(
                    $cid,
                    'retention_files_purged',
                    sprintf(
                        'Sicherungsdateien wurden nach Ablauf der Aufbewahrungsfrist (T+%d Tage nach Bereitstellung) automatisch vom Server gelöscht (%d Datei(en) bereinigt). Fallstatus: %s.',
                        $daysDelete,
                        $deletedForCase,
                        self::STATUSES[$newStatus]['label'] ?? $newStatus
                    ),
                    $author,
                    true
                );

                $result['purged_cases'][] = $cnum;
                $result['purged_files_count'] += $deletedForCase;
            }

            $result['purged_cases_count'] = count($result['purged_cases']);
        } catch (\Throwable $e) {
            $err = 'Fehler bei Fristprüfung Phase 2 (Dateilöschung): ' . $e->getMessage();
            error_log('SecurePortalRepository::processRetentionDeadlines: ' . $err);
            $result['errors'][] = $err;
        }

        return $result;
    }
}
