<?php

declare(strict_types=1);

/**
 * Security Management & HTTP-Header
 *
 * Verwaltet Sicherheitsheader für Frontend und Admin-Bereich:
 * - X-Content-Type-Options (MIME-Sniffing verhindern)
 * - Referrer-Policy (sensible Parameter in Referrern schützen)
 * - Permissions-Policy (Browser-Features einschränken)
 * - Anti-Clickjacking / Frame-Ancestors
 * - Cache-Control für Admin-Bereiche
 */
final class Security
{
    private static bool $headersSent = false;

    /**
     * Privater Konstruktor: Statische Hilfsklasse.
     */
    private function __construct()
    {
    }

    /**
     * Sendet empfohlene Sicherheitsheader je nach Bereich (Frontend vs. Admin).
     *
     * @param string|null $route Aktuelle Route (z. B. 'admin' oder 'admin/users')
     * @return void
     */
    public static function sendHeaders(?string $route = null): void
    {
        if (headers_sent() || self::$headersSent) {
            return;
        }

        self::$headersSent = true;

        // 1. MIME-Type Sniffing unterbinden
        header('X-Content-Type-Options: nosniff');

        // 2. Referrer-Policy absichern
        header('Referrer-Policy: strict-origin-when-cross-origin');

        // 3. Veralteten / fehleranfälligen XSS-Filter moderner Browser deaktivieren (wird durch sauberes Escaping ersetzt)
        header('X-XSS-Protection: 0');

        // 4. Ungenutzte Browser-Sensoren & APIs einschränken
        header('Permissions-Policy: accelerometer=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=()');

        // 5. Clickjacking-Schutz & Iframe-Kompatibilität
        // Hinweis: AI Studio und Preview-Umgebungen rendern das System in einem Iframe.
        // Daher erlauben wir spezifische Hosts in frame-ancestors, verhindern aber fremdes Einbetten.
        header("Content-Security-Policy: frame-ancestors 'self' https://ai.studio https://*.google.com https://*.googleusercontent.com https://*.run.app;");

        // 6. Admin-Bereich absichern: Browser-Caching für sensible Administrationsdaten deaktivieren
        $normalizedRoute = ltrim($route ?? (string)($_GET['route'] ?? ''), '/');
        if (str_starts_with($normalizedRoute, 'admin')) {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');
        }
    }
}
