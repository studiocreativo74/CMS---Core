<?php

declare(strict_types=1);

/**
 * SecurePortalRepository
 *
 * Data-Access-Layer für das Sicherungsportal (Polizei / Staatsanwaltschaft).
 * Verwaltet Anträge, Editionsverfügungen, Statusverläufe und Zugriffscodes.
 */
final class SecurePortalRepository
{
    private static ?bool $tableExists = null;

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
     * Erstellt Tabellen bei Bedarf automatisch (Zero-Friction-Bootstrap).
     */
    public static function ensureTables(): bool
    {
        if (self::isTableCreated()) {
            return true;
        }

        try {
            DB::query("
                CREATE TABLE IF NOT EXISTS `secure_cases` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `case_number` VARCHAR(32) NOT NULL UNIQUE,
                    `access_code` CHAR(12) NOT NULL,
                    `status` VARCHAR(32) NOT NULL DEFAULT 'new',
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
                    `assigned_user_id` INT UNSIGNED NULL,
                    `internal_notes` TEXT NULL,
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX `idx_sc_case_num` (`case_number`),
                    INDEX `idx_sc_status` (`status`),
                    INDEX `idx_sc_dept` (`police_department`),
                    INDEX `idx_sc_ref` (`reference_number`),
                    INDEX `idx_sc_type` (`securing_type`)
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
}
