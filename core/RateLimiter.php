<?php

declare(strict_types=1);

/**
 * RateLimiter - Zentraler Schutz gegen Brute-Force, Spam und Abuse
 *
 * Verwaltet Anfragelimits (z. B. 5 Anforderungen pro IP und Stunde)
 * für sensible Endpunkte wie Magic-Link-Anforderungen oder Login-Versuche.
 */
class RateLimiter
{
    private static bool $tableEnsured = false;

    /**
     * Stellt sicher, dass die Tabelle für Rate-Limiting existiert (idempotent).
     */
    public static function ensureTable(): void
    {
        if (self::$tableEnsured) {
            return;
        }
        self::$tableEnsured = true;

        try {
            DB::execute("
                CREATE TABLE IF NOT EXISTS `core_rate_limits` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `rate_key` VARCHAR(191) NOT NULL,
                    `action` VARCHAR(64) NOT NULL,
                    `created_at` INT UNSIGNED NOT NULL,
                    INDEX `idx_rl_lookup` (`rate_key`, `action`, `created_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } catch (\Throwable $e) {
            // DB nicht verfügbar -> Session-Fallback greift
        }
    }

    /**
     * Prüft, ob die Aktion für den Schlüssel erlaubt ist (Limit noch nicht erreicht).
     *
     * @param string $key Z. B. Client-IP oder E-Mail
     * @param string $action Z. B. 'boating_magic_link_request'
     * @param int $maxAttempts Maximale Anzahl Versuche
     * @param int $decaySeconds Zeitfenster in Sekunden (Standard: 3600 = 1 Stunde)
     */
    public static function isAllowed(string $key, string $action, int $maxAttempts = 5, int $decaySeconds = 3600): bool
    {
        self::ensureTable();
        $cutoff = time() - $decaySeconds;

        try {
            $count = (int) DB::fetchValue(
                "SELECT COUNT(*) FROM `core_rate_limits` WHERE `rate_key` = :k AND `action` = :a AND `created_at` > :c",
                ['k' => $key, 'a' => $action, 'c' => $cutoff]
            );
            return $count < $maxAttempts;
        } catch (\Throwable $e) {
            // Fallback auf Session
            $sessionKey = "_rl_{$action}_" . md5($key);
            $attempts = isset($_SESSION[$sessionKey]) && is_array($_SESSION[$sessionKey]) ? $_SESSION[$sessionKey] : [];
            $valid = array_filter($attempts, static fn($ts): bool => is_int($ts) && $ts > $cutoff);
            return count($valid) < $maxAttempts;
        }
    }

    /**
     * Registriert einen Versuch für den Schlüssel.
     *
     * @param string $key
     * @param string $action
     * @param int $decaySeconds
     */
    public static function hit(string $key, string $action, int $decaySeconds = 3600): void
    {
        self::ensureTable();
        $now = time();

        try {
            DB::execute(
                "INSERT INTO `core_rate_limits` (`rate_key`, `action`, `created_at`) VALUES (:k, :a, :t)",
                ['k' => $key, 'a' => $action, 't' => $now]
            );

            // Gelegentliche Bereinigung veralteter Einträge (ca. 2% der Hits)
            if (mt_rand(1, 50) === 1) {
                DB::execute("DELETE FROM `core_rate_limits` WHERE `created_at` < :old", ['old' => $now - ($decaySeconds * 2)]);
            }
        } catch (\Throwable $e) {
            $sessionKey = "_rl_{$action}_" . md5($key);
            $attempts = isset($_SESSION[$sessionKey]) && is_array($_SESSION[$sessionKey]) ? $_SESSION[$sessionKey] : [];
            $attempts[] = $now;
            $_SESSION[$sessionKey] = $attempts;
        }
    }

    /**
     * Setzt die gezählten Versuche für einen Schlüssel zurück.
     */
    public static function clear(string $key, string $action): void
    {
        try {
            DB::execute(
                "DELETE FROM `core_rate_limits` WHERE `rate_key` = :k AND `action` = :a",
                ['k' => $key, 'a' => $action]
            );
        } catch (\Throwable $e) {
            $sessionKey = "_rl_{$action}_" . md5($key);
            unset($_SESSION[$sessionKey]);
        }
    }
}
