<?php

declare(strict_types=1);

/**
 * Session Management & Hardening
 *
 * Verwaltet PHP-Sessions zentral mit Sicherheits-Flags:
 * - Cookie HttpOnly (XSS-Schutz)
 * - Cookie SameSite=Lax (CSRF-Schutz, kompatibel mit E-Mail-Redirects)
 * - Cookie Secure (automatische HTTPS-Erkennung inkl. Reverse Proxies)
 * - Strict Mode (verhindert Session Fixation mit uninitialisierten IDs)
 * - Inaktivitäts-Timeout (automatische Session-Ungültigmachung nach Ablauf)
 * - Sichere Session-ID-Regeneration
 */
final class Session
{
    /**
     * Standard-Timeout für Inaktivität in Sekunden (z. B. 4 Stunden = 14400s).
     */
    public const DEFAULT_IDLE_TIMEOUT = 14400;

    /**
     * Intervall für periodische Session-ID-Rotation in Sekunden (z. B. 30 Minuten = 1800s).
     */
    public const REGENERATE_INTERVAL = 1800;

    /**
     * Privater Konstruktor: Statische Hilfsklasse.
     */
    private function __construct()
    {
    }

    /**
     * Initialisiert und startet die Session mit gehärteten Parametern.
     *
     * @param int $idleTimeout Maximale Inaktivitätsdauer in Sekunden
     * @return void
     */
    public static function start(int $idleTimeout = self::DEFAULT_IDLE_TIMEOUT): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            self::checkActivityTimeout($idleTimeout);
            return;
        }

        if (headers_sent()) {
            // Falls Header bereits gesendet wurden, kann kein Session-Cookie mehr gesetzt werden
            return;
        }

        // 1. Session-Direktiven absichern
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');

        // 2. HTTPS-Erkennung (inklusive gängiger Reverse-Proxies wie Cloud Run, Nginx, Cloudflare)
        $isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');

        // 3. Cookie-Parameter konfigurieren
        session_set_cookie_params([
            'lifetime' => 0,          // Gültig bis zum Schließen des Browser-Fensters
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,   // Nur verschlüsselt senden, wenn HTTPS aktiv ist
            'httponly' => true,       // JavaScript kann nicht auf das Session-Cookie zugreifen
            'samesite' => 'Lax',      // Schutz gegen CSRF, erlaubt aber Einstiege via Magic-Code-Links
        ]);

        // 4. Session starten
        session_start();

        // 5. Inaktivitäts-Timeout und Session-Alter prüfen
        self::checkActivityTimeout($idleTimeout);
    }

    /**
     * Prüft das Inaktivitäts-Timeout und rotiert die Session-ID periodisch.
     */
    private static function checkActivityTimeout(int $idleTimeout): void
    {
        $now = time();

        // Inaktivitäts-Timeout prüfen
        if (isset($_SESSION['_last_activity']) && is_int($_SESSION['_last_activity'])) {
            if (($now - $_SESSION['_last_activity']) > $idleTimeout) {
                // Session ist abgelaufen -> Bereinigen und neu starten
                self::destroy();
                session_start();
                $_SESSION['_session_expired'] = true;
            }
        }

        $_SESSION['_last_activity'] = $now;

        // Erstellungszeitpunkt festhalten
        if (!isset($_SESSION['_created_at'])) {
            $_SESSION['_created_at'] = $now;
        }

        // Periodische Session-ID-Rotation zur Abwehr von Session Hijacking
        $lastRegen = $_SESSION['_regenerated_at'] ?? $_SESSION['_created_at'];
        if (($now - (int)$lastRegen) > self::REGENERATE_INTERVAL) {
            self::regenerateId(false);
            $_SESSION['_regenerated_at'] = $now;
        }
    }

    /**
     * Erzeugt eine neue Session-ID (Schutz vor Session Fixation bei Logins/Rechteänderungen).
     *
     * @param bool $deleteOldSession Alte Session-Daten auf dem Server löschen
     * @return bool
     */
    public static function regenerateId(bool $deleteOldSession = true): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE || headers_sent()) {
            return false;
        }

        $success = session_regenerate_id($deleteOldSession);
        if ($success) {
            $_SESSION['_regenerated_at'] = time();
        }

        return $success;
    }

    /**
     * Zerstört die aktuelle Session vollständig und löscht das Session-Cookie im Browser.
     *
     * @return void
     */
    public static function destroy(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies') && !headers_sent()) {
            $params = session_get_cookie_params();
            $isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
                || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
                || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');

            setcookie(
                session_name(),
                '',
                [
                    'expires'  => time() - 42000,
                    'path'     => $params['path'] ?? '/',
                    'domain'   => $params['domain'] ?? '',
                    'secure'   => $isHttps,
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]
            );
        }

        session_destroy();
    }

    /**
     * Liest einen Wert aus der Session.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Schreibt einen Wert in die Session.
     */
    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    /**
     * Prüft, ob ein Schlüssel in der Session existiert.
     */
    public static function has(string $key): bool
    {
        self::start();
        return array_key_exists($key, $_SESSION);
    }

    /**
     * Entfernt einen Schlüssel aus der Session.
     */
    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }
}
