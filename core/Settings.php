<?php
declare(strict_types=1);

/**
 * Settings Management
 *
 * Verwaltet systemweite Konfigurationseinstellungen in der `settings`-Tabelle.
 *
 * Empfohlenes SQL für die Tabelle `settings`:
 * -------------------------------------------------------------------------------------
 * CREATE TABLE IF NOT EXISTS `settings` (
 *   `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 *   `key` VARCHAR(191) NOT NULL UNIQUE,
 *   `value` TEXT NULL,
 *   `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 *   `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 *
 * Initiale Standard-Einträge für die Startseite:
 * -------------------------------------------------------------------------------------
 * INSERT INTO `settings` (`key`, `value`) VALUES
 *   ('homepage_title', 'Willkommen im CMS-Prototype'),
 *   ('homepage_subtitle', ''),
 *   ('homepage_description', ''),
 *   ('homepage_theme', 'standard'),
 *   ('after_login_redirect', 'admin'),
 *   ('after_login_custom_url', ''),
 *   ('homepage_mode', 'blocks'),
 *   ('homepage_module_key', ''),
 *   ('homepage_module_route', '')
 * ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);
 */
final class Settings
{
    /** @var array<string, ?string>|null */
    private static ?array $cache = null;

    /**
     * Standardwerte für bekannte System-Einstellungen.
     */
    private const DEFAULTS = [
        'homepage_title'            => 'Willkommen im CMS-Prototype',
        'homepage_subtitle'         => '',
        'homepage_description'      => '',
        'homepage_theme'            => 'standard',
        'homepage_primary_color'    => '#0d6efd',
        'homepage_secondary_color'  => '#6c757d',
        'homepage_background_color' => '#f8fafc',
        'homepage_text_color'       => '#222222',
        'homepage_logo_path'        => '',
        'homepage_layout'           => 'contained',
        'after_login_redirect'      => 'admin',
        'after_login_custom_url'    => '',
        'homepage_mode'             => 'blocks',
        'homepage_module_key'       => '',
        'homepage_module_route'     => '',
        'admin_brand_color'         => '#0d6efd',
        'admin_accent_color'        => '#0ea5e9',
        'admin_default_theme'       => 'system',
    ];

    private function __construct()
    {
    }

    /**
     * Lädt einen Einstellungswert anhand des Schlüssels.
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        $fallback = $default ?? (self::DEFAULTS[$key] ?? null);

        if (self::$cache !== null) {
            return self::$cache[$key] ?? $fallback;
        }

        try {
            self::all();
            return self::$cache[$key] ?? $fallback;
        } catch (\Throwable $e) {
            error_log('Settings::get Fehler bei Key "' . $key . '": ' . $e->getMessage());
            return $fallback;
        }
    }

    /**
     * Speichert oder aktualisiert einen Einstellungswert.
     */
    public static function set(string $key, ?string $value): void
    {
        $key = trim($key);
        if ($key === '') {
            return;
        }

        try {
            DB::execute(
                'INSERT INTO `settings` (`key`, `value`, `updated_at`) 
                 VALUES (:key, :value, NOW()) 
                 ON DUPLICATE KEY UPDATE `value` = :update_value, `updated_at` = NOW()',
                [
                    'key'          => $key,
                    'value'        => $value,
                    'update_value' => $value,
                ]
            );

            if (self::$cache !== null) {
                self::$cache[$key] = $value;
            }
        } catch (\Throwable $e) {
            error_log('Settings::set Fehler bei Key "' . $key . '": ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Liefert alle Einstellungen als assoziatives Array [key => value].
     *
     * @return array<string, ?string>
     */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        self::$cache = self::DEFAULTS;

        try {
            $rows = DB::fetchAll('SELECT `key`, `value` FROM `settings`');
            foreach ($rows as $row) {
                $k = (string) ($row['key'] ?? '');
                if ($k !== '') {
                    self::$cache[$k] = isset($row['value']) ? (string) $row['value'] : null;
                }
            }
        } catch (\Throwable $e) {
            error_log('Settings::all Fehler: ' . $e->getMessage());
        }

        return self::$cache;
    }

    /**
     * Leert den internen Laufzeit-Cache (z. B. für Tests oder nach Aktualisierungen).
     */
    public static function clearCache(): void
    {
        self::$cache = null;
    }
}
