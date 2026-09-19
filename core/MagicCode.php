<?php

declare(strict_types=1);

/**
 * MagicCode Management
 *
 * Unterstützt das Erstellen, Verifizieren, Filtern, Deaktivieren und Soft-Deleten
 * von Einmal-Zugangscodes für das CMS.
 *
 * Empfohlenes SQL für die Tabelle `magic_codes`:
 * -------------------------------------------------------------------------------------
 * CREATE TABLE IF NOT EXISTS `magic_codes` (
 *   `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 *   `email` VARCHAR(191) NOT NULL,
 *   `code_hash` VARCHAR(255) NOT NULL,
 *   `usage_type` VARCHAR(50) NOT NULL DEFAULT 'admin_login',
 *   `max_uses` INT UNSIGNED NOT NULL DEFAULT 1,
 *   `used_count` INT UNSIGNED NOT NULL DEFAULT 0,
 *   `is_active` TINYINT(1) NOT NULL DEFAULT 1,
 *   `expires_at` DATETIME NULL,
 *   `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 *   `used_at` DATETIME NULL,
 *   `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
 *   INDEX `idx_magic_email` (`email`),
 *   INDEX `idx_magic_status` (`is_active`, `is_deleted`, `expires_at`)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 *
 * ALTER TABLE für bestehende Tabellen:
 * -------------------------------------------------------------------------------------
 * ALTER TABLE `magic_codes`
 *   ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `used_count`,
 *   ADD COLUMN `is_deleted` TINYINT(1) NOT NULL DEFAULT 0 AFTER `used_at`;
 */
final class MagicCode
{
    public const DEFAULT_LENGTH = 10;
    private const CODE_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    // =========================================================================
    // TODO: Dev-Magic-Code vor Livegang deaktivieren/entfernen.
    // Temporärer Developer-Backdoor-Code während der Entwicklungsphase.
    // Ermöglicht Admin-Login ohne DB-Code und ohne E-Mail-Versand.
    // =========================================================================
    private const DEV_MAGIC_CODE_ENABLED = true;
    private const DEV_MAGIC_CODE_VALUE   = 'ROLAND1234';          // 10-stellig, A-Z0-9
    private const DEV_MAGIC_CODE_EMAIL   = 'office@studiocreativo.ch';

    /**
     * Prüft, ob der Entwickler-Magic-Code aktiviert ist.
     * TODO: Dev-Magic-Code vor Livegang deaktivieren/entfernen.
     */
    public static function isDevMagicCodeEnabled(): bool
    {
        return self::DEV_MAGIC_CODE_ENABLED;
    }

    /**
     * Liefert die für den Dev-Magic-Code berechtigte E-Mail-Adresse.
     * TODO: Dev-Magic-Code vor Livegang deaktivieren/entfernen.
     */
    public static function getDevMagicCodeEmail(): string
    {
        return self::DEV_MAGIC_CODE_EMAIL;
    }

    /**
     * Prüft, ob es sich um den gültigen Developer-Magic-Code für die hinterlegte E-Mail handelt.
     * Wird vor der DB-Prüfung ausgewertet und funktioniert auch ohne DB-Eintrag.
     * TODO: Dev-Magic-Code vor Livegang deaktivieren/entfernen.
     */
    public static function isDevMagicCodeValid(string $email, string $code): bool
    {
        if (!self::DEV_MAGIC_CODE_ENABLED) {
            return false;
        }

        $normalizedEmail = strtolower(trim($email));
        $devEmail = strtolower(trim(self::DEV_MAGIC_CODE_EMAIL));
        $cleanCode = strtoupper(trim($code));
        $devCode = strtoupper(trim(self::DEV_MAGIC_CODE_VALUE));

        return ($normalizedEmail === $devEmail && $cleanCode === $devCode);
    }

    /** @var array<string>|null Gecachte Spalten der Tabelle magic_codes */
    private static ?array $columns = null;

    /**
     * Prüft, ob eine Spalte in `magic_codes` existiert (kompatibel vor/nach Migration).
     */
    public static function hasColumn(string $column): bool
    {
        if (self::$columns === null) {
            try {
                $cols = DB::fetchAll('SHOW COLUMNS FROM magic_codes');
                self::$columns = array_map(
                    static fn(array $c): string => strtolower((string) ($c['Field'] ?? '')),
                    $cols
                );
            } catch (\Throwable $e) {
                self::$columns = [];
            }
        }

        return in_array(strtolower($column), self::$columns, true);
    }

    /**
     * Generiert einen zufälligen Code aus A-Z und 0-9 mit gegebener Länge.
     */
    public static function generateCode(int $length = self::DEFAULT_LENGTH): string
    {
        $code = '';
        $maxIndex = strlen(self::CODE_ALPHABET) - 1;

        for ($i = 0; $i < $length; $i++) {
            $code .= self::CODE_ALPHABET[random_int(0, $maxIndex)];
        }

        return $code;
    }

    /**
     * Legt einen neuen Magic-Code für eine E-Mail an.
     *
     * @return array{id: int, code: string, email: string}
     */
    public static function createCodeForEmail(
        string $email,
        string $usageType = 'admin_login',
        int $maxUses = 1,
        ?\DateTimeInterface $expiresAt = null
    ): array {
        $normalizedEmail = strtolower(trim($email));
        $code = self::generateCode(self::DEFAULT_LENGTH);
        $codeHash = password_hash($code, PASSWORD_DEFAULT);
        $expiresFormatted = $expiresAt !== null ? $expiresAt->format('Y-m-d H:i:s') : null;

        $hasIsActive = self::hasColumn('is_active');
        $hasIsDeleted = self::hasColumn('is_deleted');

        $cols = ['email', 'code_hash', 'usage_type', 'max_uses', 'used_count', 'expires_at', 'created_at'];
        $vals = [':email', ':code_hash', ':usage_type', ':max_uses', '0', ':expires_at', 'NOW()'];
        $params = [
            'email' => $normalizedEmail,
            'code_hash' => $codeHash,
            'usage_type' => $usageType,
            'max_uses' => $maxUses,
            'expires_at' => $expiresFormatted,
        ];

        if ($hasIsActive) {
            $cols[] = 'is_active';
            $vals[] = '1';
        }
        if ($hasIsDeleted) {
            $cols[] = 'is_deleted';
            $vals[] = '0';
        }

        $sql = sprintf(
            'INSERT INTO magic_codes (%s) VALUES (%s)',
            implode(', ', $cols),
            implode(', ', $vals)
        );

        DB::execute($sql, $params);

        $id = (int) DB::lastInsertId();

        return [
            'id' => $id,
            'code' => $code,
            'email' => $normalizedEmail,
        ];
    }

    /**
     * Prüft einen Klartext-Code für eine bestimmte E-Mail für admin_login.
     * - Nur Codes mit passender E-Mail, usage_type = 'admin_login',
     *   used_count < max_uses, is_active = 1, is_deleted = 0 und nicht abgelaufen.
     */
    public static function verifyAdminCodeForEmail(string $email, string $code): ?array
    {
        $normalizedEmail = strtolower(trim($email));
        $cleanCode = strtoupper(trim($code));

        if ($normalizedEmail === '' || $cleanCode === '' || strlen($cleanCode) > self::DEFAULT_LENGTH) {
            return null;
        }

        // =====================================================================
        // TODO: Dev-Magic-Code vor Livegang deaktivieren/entfernen.
        // Vorab-Prüfung auf den festen Entwickler-Code (Developer-Backdoor)
        // Funktioniert unabhängig von DB-Inhalten.
        // =====================================================================
        if (self::isDevMagicCodeValid($normalizedEmail, $cleanCode)) {
            return [
                'id' => 0,
                'email' => strtolower(trim(self::DEV_MAGIC_CODE_EMAIL)),
                'code_hash' => '',
                'usage_type' => 'admin_login',
                'max_uses' => 999999,
                'used_count' => 0,
                'is_active' => 1,
                'is_dev_code' => true,
            ];
        }

        $hasIsActive = self::hasColumn('is_active');
        $hasIsDeleted = self::hasColumn('is_deleted');

        $whereClause = "email = :email AND usage_type = 'admin_login' AND used_count < max_uses AND (expires_at IS NULL OR expires_at > NOW())";
        if ($hasIsActive) {
            $whereClause .= ' AND is_active = 1';
        }
        if ($hasIsDeleted) {
            $whereClause .= ' AND is_deleted = 0';
        }

        try {
            $candidates = DB::fetchAll(
                "SELECT * FROM magic_codes WHERE {$whereClause}",
                ['email' => $normalizedEmail]
            );
        } catch (\Throwable $e) {
            return null;
        }

        foreach ($candidates as $row) {
            if (password_verify($cleanCode, (string) ($row['code_hash'] ?? ''))) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Markiert einen Code nach erfolgreicher Nutzung als verwendet
     * (used_count++, used_at = NOW()).
     */
    public static function markUsed(int $id): void
    {
        if ($id <= 0) {
            return;
        }

        try {
            DB::execute(
                'UPDATE magic_codes SET used_count = used_count + 1, used_at = NOW() WHERE id = :id',
                ['id' => $id]
            );
        } catch (\Throwable $e) {
            // Fehlertolerante Handhabung
        }
    }

    /**
     * Deaktiviert/sperrt einen Magic-Code.
     * - Setzt is_active = 0 (falls Spalte existiert)
     * - Setzt zusätzlich max_uses = used_count, damit der Code auch ohne
     *   die Spalte is_active sofort unbrauchbar ist (Dual-Protection).
     */
    public static function deactivate(int $id): void
    {
        $hasIsActive = self::hasColumn('is_active');

        if ($hasIsActive) {
            DB::execute(
                'UPDATE magic_codes SET is_active = 0, max_uses = LEAST(max_uses, used_count) WHERE id = :id',
                ['id' => $id]
            );
        } else {
            DB::execute(
                'UPDATE magic_codes SET max_uses = used_count WHERE id = :id',
                ['id' => $id]
            );
        }
    }

    /**
     * Führt ein Soft-Delete für einen Magic-Code durch:
     * - Setzt is_deleted = 1 (falls Spalte existiert).
     * - Setzt zusätzlich usage_type = 'deleted' und deaktiviert ihn,
     *   sodass er unter keinen Umständen mehr im Frontend oder Login nutzbar ist.
     */
    public static function softDelete(int $id): void
    {
        self::deactivate($id);

        $hasIsDeleted = self::hasColumn('is_deleted');
        if ($hasIsDeleted) {
            DB::execute(
                'UPDATE magic_codes SET is_deleted = 1 WHERE id = :id',
                ['id' => $id]
            );
        } else {
            DB::execute(
                "UPDATE magic_codes SET usage_type = 'deleted' WHERE id = :id",
                ['id' => $id]
            );
        }
    }

    /**
     * Architektur-Entscheidung zu Resend / Erneut senden:
     * In der Datenbank liegt aus Sicherheitsgründen nur der bcrypt-Hash des Codes vor.
     * Der ursprüngliche Klartext-Code kann und darf niemals rekonstruiert werden.
     *
     * Daher generiert resendCode() einen FRISCHEN 10-stelligen Code für dieselbe
     * E-Mail-Adresse, deaktiviert den vorherigen alten Code und versendet den neuen Code.
     *
     * @return array{id: int, code: string, email: string}|null
     */
    public static function resendCode(int $id): ?array
    {
        $old = self::find($id);
        if (!$old) {
            return null;
        }

        $email = (string) $old['email'];
        $usageType = (string) ($old['usage_type'] ?? 'admin_login');
        if ($usageType === 'deleted') {
            $usageType = 'admin_login';
        }

        $maxUses = max(1, (int) ($old['max_uses'] ?? 1));

        // Alten Code deaktivieren, damit kein doppelter gültiger Code zirkuliert
        self::deactivate($id);

        // Frischen Code erstellen
        $created = self::createCodeForEmail($email, $usageType, $maxUses, null);

        // Code per Mailer versenden
        if (class_exists('Mailer')) {
            Mailer::sendMagicCodeToUser($email, $created['code']);
        } else {
            self::sendEmailNotification($created['code'], $email, $usageType, $maxUses);
        }

        return $created;
    }

    /**
     * Sucht einen einzelnen Magic-Code anhand seiner ID.
     */
    public static function find(int $id): ?array
    {
        return DB::fetchOne('SELECT * FROM magic_codes WHERE id = :id', ['id' => $id]);
    }

    /**
     * Liefert gefilterte Magic-Codes mit komfortabler Suche für das Admin-Dashboard.
     *
     * Unterstützte Filter:
     * - email (Teilstring)
     * - status: 'all', 'active', 'used', 'expired', 'deactivated', 'deleted'
     * - usage_type: 'all', 'admin_login', 'frontend', etc.
     * - date_from (YYYY-MM-DD)
     * - date_to (YYYY-MM-DD)
     *
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public static function list(array $filters = []): array
    {
        $hasIsActive = self::hasColumn('is_active');
        $hasIsDeleted = self::hasColumn('is_deleted');

        $where = [];
        $params = [];

        // 1. Filter: E-Mail Teilstring
        $email = trim((string) ($filters['email'] ?? ''));
        if ($email !== '') {
            $where[] = 'email LIKE :email';
            $params['email'] = '%' . $email . '%';
        }

        // 2. Filter: Usage-Type
        $usageType = trim((string) ($filters['usage_type'] ?? ''));
        if ($usageType !== '' && $usageType !== 'all') {
            $where[] = 'usage_type = :usage_type';
            $params['usage_type'] = $usageType;
        }

        // 3. Filter: Datum ab/bis
        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        if ($dateFrom !== '') {
            $where[] = 'created_at >= :date_from';
            $params['date_from'] = $dateFrom . ' 00:00:00';
        }

        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        if ($dateTo !== '') {
            $where[] = 'created_at <= :date_to';
            $params['date_to'] = $dateTo . ' 23:59:59';
        }

        // 4. Filter: Status
        $status = trim((string) ($filters['status'] ?? 'all'));

        switch ($status) {
            case 'active':
                $where[] = 'used_count < max_uses AND (expires_at IS NULL OR expires_at > NOW())';
                if ($hasIsActive) {
                    $where[] = 'is_active = 1';
                }
                if ($hasIsDeleted) {
                    $where[] = 'is_deleted = 0';
                } else {
                    $where[] = "usage_type != 'deleted'";
                }
                break;

            case 'used':
                $where[] = 'used_count >= max_uses';
                if ($hasIsDeleted) {
                    $where[] = 'is_deleted = 0';
                } else {
                    $where[] = "usage_type != 'deleted'";
                }
                break;

            case 'expired':
                $where[] = 'expires_at IS NOT NULL AND expires_at <= NOW()';
                if ($hasIsDeleted) {
                    $where[] = 'is_deleted = 0';
                } else {
                    $where[] = "usage_type != 'deleted'";
                }
                break;

            case 'deactivated':
                if ($hasIsActive) {
                    $where[] = 'is_active = 0';
                } else {
                    $where[] = 'max_uses = used_count AND used_count = 0';
                }
                if ($hasIsDeleted) {
                    $where[] = 'is_deleted = 0';
                }
                break;

            case 'deleted':
                if ($hasIsDeleted) {
                    $where[] = 'is_deleted = 1';
                } else {
                    $where[] = "usage_type = 'deleted'";
                }
                break;

            case 'all':
            default:
                // Wenn 'all' gewählt ist, zeigen wir standardmässig alle Datensätze an
                break;
        }

        $sql = 'SELECT * FROM magic_codes';
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY created_at DESC';

        try {
            return DB::fetchAll($sql, $params);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Ermittelt den logischen Status (Badge + Label) für einen Datensatz.
     *
     * @param array<string, mixed> $row
     * @return array{key: string, label: string, badge_class: string}
     */
    public static function getStatus(array $row): array
    {
        $now = new \DateTimeImmutable();

        // 1. Gelöscht / Soft-Delete
        if ((int) ($row['is_deleted'] ?? 0) === 1 || ($row['usage_type'] ?? '') === 'deleted') {
            return [
                'key' => 'deleted',
                'label' => 'Gelöscht',
                'badge_class' => 'bg-dark',
            ];
        }

        // 2. Deaktiviert
        if (isset($row['is_active']) && (int) $row['is_active'] === 0) {
            return [
                'key' => 'deactivated',
                'label' => 'Deaktiviert',
                'badge_class' => 'bg-secondary',
            ];
        }

        // 3. Abgelaufen
        if (!empty($row['expires_at'])) {
            try {
                $expiresAt = new \DateTimeImmutable((string) $row['expires_at']);
                if ($expiresAt <= $now) {
                    return [
                        'key' => 'expired',
                        'label' => 'Abgelaufen',
                        'badge_class' => 'bg-warning text-dark',
                    ];
                }
            } catch (\Throwable $e) {
                // Bei ungültigem Datumsformat ignorieren
            }
        }

        // 4. Vollständig genutzt
        $usedCount = (int) ($row['used_count'] ?? 0);
        $maxUses = (int) ($row['max_uses'] ?? 1);
        if ($usedCount >= $maxUses) {
            return [
                'key' => 'used',
                'label' => 'Verbraucht',
                'badge_class' => 'bg-secondary',
            ];
        }

        // 5. Aktiv
        return [
            'key' => 'active',
            'label' => 'Aktiv',
            'badge_class' => 'bg-success',
        ];
    }

    /**
     * Liefert alle Magic-Codes absteigend sortiert (Fallback / Kompatibilität).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getAll(): array
    {
        return self::list(['status' => 'all']);
    }

    /**
     * Versendet eine Benachrichtigungs-E-Mail mit dem Klartext-Code an die angegebene Adresse
     * sowie als Kopie/BCC an office@studiocreativo.ch über die Mailer-Klasse.
     */
    public static function sendEmailNotification(
        string $code,
        string $recipientEmail,
        string $usageType = 'admin_login',
        int $maxUses = 1,
        string $bccEmail = 'office@studiocreativo.ch'
    ): bool {
        if (!class_exists('Mailer')) {
            require_once __DIR__ . '/Mailer.php';
        }

        return Mailer::send(
            $recipientEmail,
            'Dein Magic-Code für das CMS',
            "Guten Tag,\n\n"
            . "Du hast einen Magic-Code für das CMS angefordert:\n\n"
            . "----------------------------------------\n"
            . "Dein Magic-Code:  {$code}\n"
            . "E-Mail:           {$recipientEmail}\n"
            . "Verwendungszweck: {$usageType}\n"
            . "Max. Nutzungen:   {$maxUses}\n"
            . "Erstellt am:      " . date('d.m.Y H:i:s') . "\n"
            . "----------------------------------------\n\n"
            . "Gib diesen 10-stelligen Code auf der Startseite ein, um dich einzuloggen.\n\n"
            . "Freundliche Grüsse,\n"
            . "Dein CMS-System\n",
            true
        );
    }
}
