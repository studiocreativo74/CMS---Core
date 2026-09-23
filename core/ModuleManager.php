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
     * Cache zur Prüfung, ob die Spalte `requires_core` in der DB existiert.
     */
    private static ?bool $hasRequiresCoreCol = null;

    /**
     * Privater Konstruktor: rein statische Utility-Klasse.
     */
    private function __construct()
    {
    }

    /**
     * Prüft, ob die Spalte `requires_core` in der Tabelle `modules` vorhanden ist.
     */
    public static function hasRequiresCoreColumn(): bool
    {
        if (self::$hasRequiresCoreCol !== null) {
            return self::$hasRequiresCoreCol;
        }

        try {
            $cols = DB::fetchAll("SHOW COLUMNS FROM `modules` LIKE 'requires_core'");
            self::$hasRequiresCoreCol = !empty($cols);
        } catch (\Throwable) {
            self::$hasRequiresCoreCol = false;
        }

        return self::$hasRequiresCoreCol;
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
            $hasReqCol = self::hasRequiresCoreColumn();
            $selectCols = '`id`, `key`, `name`, `description`, `version`, `is_enabled`, `installed_at`, `updated_at`';
            if ($hasReqCol) {
                $selectCols .= ', `requires_core`';
            }

            $sql = "SELECT {$selectCols} FROM `modules`";

            if ($onlyEnabled) {
                $sql .= ' WHERE `is_enabled` = 1';
            }

            $sql .= ' ORDER BY `name` ASC, `id` ASC';

            $rows = DB::fetchAll($sql);

            // Falls die DB-Spalte noch nicht existiert, prüfen wir auf vorhandene module.php/module.json
            $baseDir = dirname(__DIR__) . '/modules';
            foreach ($rows as &$row) {
                $key = (string) ($row['key'] ?? '');
                if (!$hasReqCol) {
                    $row['requires_core'] = self::getManifestRequiresCore($baseDir, $key);
                }
                if (!isset($row['module_key'])) {
                    $row['module_key'] = $key;
                }
            }
            unset($row);

            return $rows;
        } catch (\Throwable $e) {
            // Falls Tabelle in phpMyAdmin noch nicht angelegt wurde
            error_log('ModuleManager::all Fehler: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Liest requires_core aus dem Dateisystem-Manifest eines Moduls aus.
     */
    public static function getManifestRequiresCore(string $baseDir, string $key): ?string
    {
        $dir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $key;
        $phpFile = $dir . DIRECTORY_SEPARATOR . 'module.php';
        if (file_exists($phpFile)) {
            $data = @include $phpFile;
            if (is_array($data) && !empty($data['requires_core'])) {
                return (string) $data['requires_core'];
            }
        }

        $jsonFile = $dir . DIRECTORY_SEPARATOR . 'module.json';
        if (file_exists($jsonFile)) {
            $jsonData = json_decode((string) @file_get_contents($jsonFile), true);
            if (is_array($jsonData) && !empty($jsonData['requires_core'])) {
                return (string) $jsonData['requires_core'];
            }
        }

        return null;
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
            $hasReqCol = self::hasRequiresCoreColumn();
            $selectCols = '`id`, `key`, `name`, `description`, `version`, `is_enabled`, `installed_at`, `updated_at`';
            if ($hasReqCol) {
                $selectCols .= ', `requires_core`';
            }

            $sql = "SELECT {$selectCols} FROM `modules` WHERE `key` = :key LIMIT 1";

            $row = DB::fetchOne($sql, ['key' => $key]);
            if ($row !== null) {
                if (!$hasReqCol) {
                    $baseDir = dirname(__DIR__) . '/modules';
                    $row['requires_core'] = self::getManifestRequiresCore($baseDir, $key);
                }
                if (!isset($row['module_key'])) {
                    $row['module_key'] = (string) ($row['key'] ?? '');
                }
            }

            return $row;
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
     * Prüft die Kompatibilität eines Moduls zur aktuellen Core-Version.
     *
     * @param array<string, mixed>|string $moduleOrKey Moduldaten-Array oder Modul-Key
     * @return bool
     */
    public static function isCompatible(array|string $moduleOrKey): bool
    {
        $info = self::getCompatibilityInfo($moduleOrKey);
        return (bool) $info['is_compatible'];
    }

    /**
     * Liefert detaillierte Informationen zur Core-Kompatibilität eines Moduls.
     *
     * @param array<string, mixed>|string $moduleOrKey Moduldaten-Array oder Modul-Key
     * @return array{is_compatible: bool, requires_core: string, core_version: string, status: string, message: string}
     */
    public static function getCompatibilityInfo(array|string $moduleOrKey): array
    {
        $module = is_array($moduleOrKey) ? $moduleOrKey : self::findByKey($moduleOrKey);
        $req = (string) ($module['requires_core'] ?? '');
        $req = trim($req);
        $coreVer = class_exists('CoreVersion') ? CoreVersion::VERSION : '1.0.0';

        if ($req === '' || !class_exists('CoreVersion')) {
            return [
                'is_compatible' => true,
                'requires_core' => $req !== '' ? $req : '*',
                'core_version'  => $coreVer,
                'status'        => 'ok',
                'message'       => 'Vollständig kompatibel',
            ];
        }

        $compatible = CoreVersion::satisfies($req);
        return [
            'is_compatible' => $compatible,
            'requires_core' => $req,
            'core_version'  => $coreVer,
            'status'        => $compatible ? 'ok' : 'incompatible',
            'message'       => $compatible
                ? 'Kompatibel mit Core v' . $coreVer
                : 'Inkompatibel: Erfordert Core ' . $req . ' (Aktuell: v' . $coreVer . ')',
        ];
    }

    /**
     * Registriert ein Modul in der DB, falls noch nicht vorhanden (z.B. bei Neuinstallation).
     * Aktualisiert bei bereits vorhandenem Modul Metadaten wie Name, Beschreibung, Version und requires_core,
     * behält jedoch den vom Administrator gesetzten Aktivierungsstatus bei.
     *
     * @param string $key Eindeutiger Bezeichner (z.B. 'contact_form')
     * @param array<string, mixed> $meta Metadaten (name, description, version, requires_core, is_enabled)
     */
    public static function register(string $key, array $meta): void
    {
        $name = (string) ($meta['name'] ?? $key);
        $description = isset($meta['description']) ? (string) $meta['description'] : null;
        $version = (string) ($meta['version'] ?? '1.0.0');
        $requiresCore = isset($meta['requires_core']) && trim((string) $meta['requires_core']) !== '' 
            ? trim((string) $meta['requires_core']) 
            : null;
        $defaultEnabled = isset($meta['is_enabled']) ? ((bool) $meta['is_enabled'] ? 1 : 0) : 0;

        try {
            $existing = self::findByKey($key);
            $hasReqCol = self::hasRequiresCoreColumn();

            if ($existing === null) {
                if ($hasReqCol) {
                    DB::execute(
                        'INSERT INTO `modules` (`key`, `name`, `description`, `version`, `requires_core`, `is_enabled`, `installed_at`, `updated_at`) 
                         VALUES (:key, :name, :description, :version, :requires_core, :is_enabled, NOW(), NOW())',
                        [
                            'key'           => $key,
                            'name'          => $name,
                            'description'   => $description,
                            'version'       => $version,
                            'requires_core' => $requiresCore,
                            'is_enabled'    => $defaultEnabled,
                        ]
                    );
                } else {
                    DB::execute(
                        'INSERT INTO `modules` (`key`, `name`, `description`, `version`, `is_enabled`, `installed_at`, `updated_at`) 
                         VALUES (:key, :name, :description, :version, :is_enabled, NOW(), NOW())',
                        [
                            'key'         => $key,
                            'name'        => $name,
                            'description' => $description,
                            'version'     => $version,
                            'is_enabled'  => $defaultEnabled,
                        ]
                    );
                }
            } else {
                // Bei bestehenden Modulen aktualisieren wir Name, Beschreibung, Version & requires_core,
                // lassen den bestehenden is_enabled Status des Admins unberührt.
                if ($hasReqCol) {
                    DB::execute(
                        'UPDATE `modules` 
                         SET `name` = :name, 
                             `description` = :description, 
                             `version` = :version, 
                             `requires_core` = :requires_core,
                             `updated_at` = NOW() 
                         WHERE `key` = :key',
                        [
                            'key'           => $key,
                            'name'          => $name,
                            'description'   => $description,
                            'version'       => $version,
                            'requires_core' => $requiresCore,
                        ]
                    );
                } else {
                    DB::execute(
                        'UPDATE `modules` 
                         SET `name` = :name, 
                             `description` = :description, 
                             `version` = :version, 
                             `updated_at` = NOW() 
                         WHERE `key` = :key',
                        [
                            'key'         => $key,
                            'name'        => $name,
                            'description' => $description,
                            'version'     => $version,
                        ]
                    );
                }
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
                'requires_core' => null,
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
        $loadedKeys = [];
        foreach ($activeModules as $m) {
            $loadedKeys[(string) ($m['key'] ?? '')] = true;
        }

        // Automatische Erkennung und Registrierung neu hinzugefügter Modulordner mit module.php
        $items = @scandir($baseDir);
        if ($items !== false) {
            foreach ($items as $item) {
                if ($item === '.' || $item === '..' || isset($loadedKeys[$item])) {
                    continue;
                }
                $mDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $item;
                $phpMetaFile = $mDir . DIRECTORY_SEPARATOR . 'module.php';
                if (is_dir($mDir) && file_exists($phpMetaFile)) {
                    $mMeta = @require $phpMetaFile;
                    if (is_array($mMeta) && !empty($mMeta['is_enabled'])) {
                        self::register($item, $mMeta);
                        $activeModules[] = array_merge(['key' => $item], $mMeta);
                        $loadedKeys[$item] = true;
                    }
                }
            }
        }

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
