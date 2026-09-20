<?php

declare(strict_types=1);

/**
 * Zentrale Verwaltung für modulare CMS-Erweiterungen.
 *
 * Verwaltet die Registrierung, Aktivierung und das Laden
 * von Modulen aus der Datenbank und dem Dateisystem.
 */
final class ModuleManager
{
    /**
     * Registrierte Admin-Menüpunkte aus aktiven Modulen.
     * @var array<int, array{label: string, route: string, url: string, icon?: string}>
     */
    private static array $adminMenuItems = [];

    /**
     * Cache für die Liste geladener Module (Schlüssel => Moduldaten).
     * @var array<string, array<string, mixed>>|null
     */
    private static ?array $loadedModules = null;

    /**
     * Privater Konstruktor: rein statische Utility-Klasse.
     */
    private function __construct()
    {
    }

    /**
     * Liefert die Liste aller bekannten Module aus der DB.
     * Optional mit Filter: nur aktive Module (`is_enabled = 1`).
     *
     * @param bool $onlyEnabled Wenn true, werden nur aktive Module zurückgegeben
     * @return array<int, array<string, mixed>>
     */
    public static function all(bool $onlyEnabled = false): array
    {
        try {
            $sql = 'SELECT `id`, `key`, `name`, `description`, `version`, `is_enabled`, `installed_at`, `updated_at` FROM `modules`';

            if ($onlyEnabled) {
                $sql .= ' WHERE `is_enabled` = 1';
            }

            $sql .= ' ORDER BY `name` ASC, `id` ASC';

            return DB::fetchAll($sql);
        } catch (\Throwable $e) {
            // Falls Tabelle in phpMyAdmin noch nicht angelegt wurde
            error_log('ModuleManager::all Fehler: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Alias für `all()` zur Rückwärtskompatibilität.
     *
     * @param bool $onlyEnabled
     * @return array<int, array<string, mixed>>
     */
    public static function getAll(bool $onlyEnabled = false): array
    {
        return self::all($onlyEnabled);
    }

    /**
     * Liefert ein einzelnes Modul nach `key` oder null, falls nicht gefunden.
     *
     * @param string $key Eindeutiger Modulschlüssel (z.B. 'contact_form')
     * @return array<string, mixed>|null
     */
    public static function findByKey(string $key): ?array
    {
        try {
            $sql = 'SELECT `id`, `key`, `name`, `description`, `version`, `is_enabled`, `installed_at`, `updated_at` 
                    FROM `modules` 
                    WHERE `key` = :key 
                    LIMIT 1';

            return DB::fetchOne($sql, ['key' => $key]);
        } catch (\Throwable $e) {
            error_log('ModuleManager::findByKey Fehler: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Prüft, ob ein bestimmtes Modul existiert und aktiv geschaltet ist.
     *
     * @param string $key Eindeutiger Modulschlüssel
     * @return bool
     */
    public static function isEnabled(string $key): bool
    {
        $module = self::findByKey($key);
        return $module !== null && (int) ($module['is_enabled'] ?? 0) === 1;
    }

    /**
     * Registriert ein Modul in der DB, falls noch nicht vorhanden (z.B. bei Neuinstallation).
     * Aktualisiert bei bereits vorhandenem Modul Metadaten wie Name, Beschreibung und Version,
     * behält jedoch den vom Administrator gesetzten Aktivierungsstatus bei.
     *
     * @param string $key Eindeutiger Bezeichner (z.B. 'contact_form')
     * @param array<string, mixed> $meta Metadaten (name, description, version, is_enabled)
     */
    public static function register(string $key, array $meta): void
    {
        $name = (string) ($meta['name'] ?? $key);
        $description = isset($meta['description']) ? (string) $meta['description'] : null;
        $version = (string) ($meta['version'] ?? '1.0.0');
        $defaultEnabled = isset($meta['is_enabled']) ? ((bool) $meta['is_enabled'] ? 1 : 0) : 0;

        try {
            $existing = self::findByKey($key);

            if ($existing === null) {
                DB::execute(
                    'INSERT INTO `modules` (`key`, `name`, `description`, `version`, `is_enabled`, `installed_at`, `updated_at`) 
                     VALUES (:key, :name, :description, :version, :is_enabled, NOW(), NOW())',
                    [
                        'key' => $key,
                        'name' => $name,
                        'description' => $description,
                        'version' => $version,
                        'is_enabled' => $defaultEnabled,
                    ]
                );
            } else {
                // Bei bestehenden Modulen aktualisieren wir Name, Beschreibung & Version,
                // lassen den bestehenden is_enabled Status des Admins unberührt.
                DB::execute(
                    'UPDATE `modules` 
                     SET `name` = :name, 
                         `description` = :description, 
                         `version` = :version, 
                         `updated_at` = NOW() 
                     WHERE `key` = :key',
                    [
                        'key' => $key,
                        'name' => $name,
                        'description' => $description,
                        'version' => $version,
                    ]
                );
            }

            self::$loadedModules = null;
        } catch (\Throwable $e) {
            error_log('ModuleManager::register Fehler bei Modul "' . $key . '": ' . $e->getMessage());
        }
    }

    /**
     * Aktiviert oder deaktiviert ein Modul anhand seines Keys.
     *
     * @param string $key Eindeutiger Modulschlüssel
     * @param bool $enabled True = aktivieren, False = deaktivieren
     */
    public static function setEnabled(string $key, bool $enabled): void
    {
        try {
            DB::execute(
                'UPDATE `modules` SET `is_enabled` = :is_enabled, `updated_at` = NOW() WHERE `key` = :key',
                [
                    'is_enabled' => $enabled ? 1 : 0,
                    'key' => $key,
                ]
            );

            self::$loadedModules = null;
        } catch (\Throwable $e) {
            error_log('ModuleManager::setEnabled Fehler bei Modul "' . $key . '": ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Durchsucht ein Basisverzeichnis (z.B. /modules) nach Modulordnern.
     * Erkennt Metadaten entweder aus einer `module.php` oder `module.json` Datei im Modulordner.
     * Registriert neu gefundene Module automatisch in der Datenbank.
     *
     * @param string $baseDir Absoluter Pfad zum Modul-Ordner (z.B. __DIR__ . '/../modules')
     * @return array<string, array<string, mixed>> Gefundene Module
     */
    public static function discover(string $baseDir): array
    {
        $discovered = [];

        if (!is_dir($baseDir)) {
            return $discovered;
        }

        $items = scandir($baseDir);
        if ($items === false) {
            return $discovered;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $moduleDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $item;
            if (!is_dir($moduleDir)) {
                continue;
            }

            $key = $item;
            $meta = [
                'key' => $key,
                'name' => ucfirst(str_replace(['_', '-'], ' ', $key)),
                'description' => null,
                'version' => '1.0.0',
            ];

            // 1. Prüfung auf module.php
            $phpMetaFile = $moduleDir . DIRECTORY_SEPARATOR . 'module.php';
            if (file_exists($phpMetaFile)) {
                $phpData = require $phpMetaFile;
                if (is_array($phpData)) {
                    $meta = array_merge($meta, $phpData);
                }
            } 
            // 2. Prüfung auf module.json
            else {
                $jsonMetaFile = $moduleDir . DIRECTORY_SEPARATOR . 'module.json';
                if (file_exists($jsonMetaFile)) {
                    $jsonData = json_decode((string) file_get_contents($jsonMetaFile), true);
                    if (is_array($jsonData)) {
                        $meta = array_merge($meta, $jsonData);
                    }
                }
            }

            $discovered[$key] = $meta;

            // In DB registrieren / synchronisieren
            self::register($key, $meta);
        }

        return $discovered;
    }

    /**
     * Lädt alle aktiven Module und führt deren "Bootstrap"-Dateien aus.
     * Unterstützt als Bootstrap-Datei entweder `bootstrap.php` oder `routes.php` im jeweiligen Modulordner.
     *
     * Übergibt den aktuellen Router und Modul-Informationen an den Scope des Bootstrappers.
     *
     * @param string $baseDir Basisverzeichnis der Module (z.B. __DIR__ . '/modules')
     * @param Router|null $router Optionale Router-Instanz zur Registrierung von Modul-Routen
     */
    public static function loadActiveModules(string $baseDir, ?Router $router = null): void
    {
        if (!is_dir($baseDir)) {
            return;
        }

        $activeModules = self::all(true);

        foreach ($activeModules as $module) {
            $key = (string) ($module['key'] ?? '');
            if ($key === '') {
                continue;
            }

            $moduleDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $key;
            if (!is_dir($moduleDir)) {
                continue;
            }

            // Mögliche Bootstrap-Einstiegspunkte
            $bootstrapFile = $moduleDir . DIRECTORY_SEPARATOR . 'bootstrap.php';
            if (!file_exists($bootstrapFile)) {
                $bootstrapFile = $moduleDir . DIRECTORY_SEPARATOR . 'routes.php';
            }

            if (file_exists($bootstrapFile)) {
                // Ausführung in isolierter Closure mit $router, $module, $moduleDir
                (static function (string $file, ?Router $router, array $module, string $moduleDir): void {
                    require_once $file;
                })($bootstrapFile, $router, $module, $moduleDir);
            }
        }
    }

    /**
     * Registriert einen Admin-Menüeintrag für die Sidebar-Navigation.
     * Kann von einem Modul in seiner bootstrap.php aufgerufen werden.
     *
     * Format von $item:
     * [
     *     'label' => 'Kontaktanfragen',
     *     'route' => 'admin/contact',
     *     'url'   => '?route=admin/contact',
     *     'icon'  => '<svg ...>...</svg>', // optionales SVG oder Icon
     * ]
     *
     * @param array{label: string, route: string, url: string, icon?: string} $item
     */
    public static function addAdminMenuItem(array $item): void
    {
        if (isset($item['label'], $item['route'], $item['url'])) {
            self::$adminMenuItems[] = $item;
        }
    }

    /**
     * Liefert alle von aktiven Modulen registrierten Admin-Menüpunkte.
     *
     * @return array<int, array{label: string, route: string, url: string, icon?: string}>
     */
    public static function getAdminMenuItems(): array
    {
        return self::$adminMenuItems;
    }
}
