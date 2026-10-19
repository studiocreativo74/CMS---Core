<?php

declare(strict_types=1);

/**
 * ActivityLog - Zentraler Service zum Erfassen von System-, Sicherheits- und Fach-Ereignissen
 *
 * Unterstützt flexible Schemas (Tabelle `activity_log` oder `activity_logs`,
 * Spalten `event_type`/`action`, `email`/`user_email`, `details`, `ip_address`, `user_agent`).
 */
final class ActivityLog
{
    private static ?string $cachedTable = null;
    private static ?array $cachedColumns = null;

    /**
     * Schreibt ein Ereignis in die Audit-Log-Tabelle.
     *
     * @param string $eventType Eindeutiger Ereignisschlüssel (z. B. 'secure_case.created', 'auth.login')
     * @param string|null $email E-Mail des ausführenden Benutzers (falls null, wird die aktive Session ermittelt)
     * @param array<string, mixed> $details Zusätzliche Kontext- und Metadaten
     * @return bool True bei Erfolg, False bei Fehler
     */
    public static function log(string $eventType, ?string $email = null, array $details = []): bool
    {
        try {
            $table = self::resolveTable();
            $cols = self::resolveColumns($table);

            // 1. E-Mail ermitteln, falls nicht explizit übergeben
            $effectiveEmail = $email !== null && trim($email) !== '' ? trim($email) : self::resolveCurrentEmail();

            // 2. IP-Adresse ermitteln
            $ipAddress = self::resolveClientIp();

            // 3. User Agent ermitteln
            $userAgent = self::resolveUserAgent();

            // 4. Details als sauberes JSON kodieren
            $detailsJson = !empty($details) ? json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;

            // 5. Spalten dynamisch zuordnen
            $insertCols = [];
            $placeholders = [];
            $params = [];

            // Event-Typ / Action
            if (in_array('event_type', $cols, true)) {
                $insertCols[] = '`event_type`';
                $placeholders[] = ':event_type';
                $params['event_type'] = $eventType;
            } elseif (in_array('action', $cols, true)) {
                $insertCols[] = '`action`';
                $placeholders[] = ':action';
                $params['action'] = $eventType;
            }

            // E-Mail / User-E-Mail
            if (in_array('email', $cols, true)) {
                $insertCols[] = '`email`';
                $placeholders[] = ':email';
                $params['email'] = $effectiveEmail;
            } elseif (in_array('user_email', $cols, true)) {
                $insertCols[] = '`user_email`';
                $placeholders[] = ':user_email';
                $params['user_email'] = $effectiveEmail;
            }

            // Details
            if (in_array('details', $cols, true)) {
                $insertCols[] = '`details`';
                $placeholders[] = ':details';
                $params['details'] = $detailsJson;
            }

            // IP-Adresse
            if (in_array('ip_address', $cols, true)) {
                $insertCols[] = '`ip_address`';
                $placeholders[] = ':ip_address';
                $params['ip_address'] = $ipAddress !== null ? substr($ipAddress, 0, 45) : null;
            }

            // User Agent (falls Spalte existiert)
            if (in_array('user_agent', $cols, true)) {
                $insertCols[] = '`user_agent`';
                $placeholders[] = ':user_agent';
                $params['user_agent'] = $userAgent !== null ? substr($userAgent, 0, 512) : null;
            }

            // Created At
            if (in_array('created_at', $cols, true)) {
                $insertCols[] = '`created_at`';
                $placeholders[] = 'NOW()';
            }

            if (empty($insertCols)) {
                return false;
            }

            $sql = sprintf(
                'INSERT INTO `%s` (%s) VALUES (%s)',
                $table,
                implode(', ', $insertCols),
                implode(', ', $placeholders)
            );

            DB::execute($sql, $params);
            return true;
        } catch (\Throwable $e) {
            // Unaufdringliches Error-Logging, damit Fachprozesse nie an fehlerhaften Logs abbrechen
            error_log('ActivityLog::log Fehler: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ermittelt den Tabellennamen (bevorzugt `activity_log`, sonst `activity_logs`).
     */
    public static function resolveTable(): string
    {
        if (self::$cachedTable !== null) {
            return self::$cachedTable;
        }

        try {
            $stmt = DB::query("SHOW TABLES LIKE 'activity_log'");
            if ($stmt->fetch()) {
                return self::$cachedTable = 'activity_log';
            }

            $stmt2 = DB::query("SHOW TABLES LIKE 'activity_logs'");
            if ($stmt2->fetch()) {
                return self::$cachedTable = 'activity_logs';
            }
        } catch (\Throwable $e) {
            // DB evtl. noch nicht initialisiert
        }

        return self::$cachedTable = 'activity_log';
    }

    /**
     * Ermittelt die Spalten der Zieltabelle.
     *
     * @return array<string>
     */
    public static function resolveColumns(?string $table = null): array
    {
        if (self::$cachedColumns !== null) {
            return self::$cachedColumns;
        }

        $table = $table ?? self::resolveTable();
        $cols = [];

        try {
            $rows = DB::fetchAll("SHOW COLUMNS FROM `{$table}`");
            foreach ($rows as $r) {
                if (isset($r['Field'])) {
                    $cols[] = (string) $r['Field'];
                }
            }
        } catch (\Throwable $e) {
            // Fallback auf Standardfelder
            $cols = ['id', 'event_type', 'email', 'ip_address', 'user_agent', 'created_at', 'details'];
        }

        return self::$cachedColumns = $cols;
    }

    /**
     * Ermittelt die Client-IP-Adresse unter Berücksichtigung gängiger Proxies.
     */
    public static function resolveClientIp(): ?string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $raw = (string) $_SERVER[$header];
                // Falls X-Forwarded-For mehrere IPs enthält (Client, Proxy1, Proxy2):
                $parts = explode(',', $raw);
                $ip = trim($parts[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? null;
    }

    /**
     * Ermittelt den Browser/Client User-Agent.
     */
    public static function resolveUserAgent(): ?string
    {
        if (!empty($_SERVER['HTTP_USER_AGENT'])) {
            return trim((string) $_SERVER['HTTP_USER_AGENT']);
        }
        return null;
    }

    /**
     * Ermittelt die E-Mail des aktuell angemeldeten Benutzers aus Auth oder Session.
     */
    public static function resolveCurrentEmail(): ?string
    {
        if (!empty($_SESSION['magic_email'])) {
            return (string) $_SESSION['magic_email'];
        }

        if (!empty($_SESSION['user_email'])) {
            return (string) $_SESSION['user_email'];
        }

        if (class_exists('Auth') && method_exists('Auth', 'currentUser')) {
            $user = Auth::currentUser();
            if (!empty($user['email'])) {
                return (string) $user['email'];
            }
        }

        return null;
    }
}
