<?php

declare(strict_types=1);

/**
 * MigrationManager
 *
 * Verwaltet Datenbank-Migrationen für den CMS-Core und modulare Erweiterungen.
 * Liest Migrations-Dateien aus /migrations, gleicht sie mit der Tabelle `migrations` ab,
 * ordnet sie dem Core bzw. den einzelnen Modulen zu und filtert sie nach aktiven Modulen.
 *
 * WICHTIG: Gemäß Sicherheits- und Architektur-Vorgaben werden DDL-Statements
 * nicht automatisch via PHP ausgeführt, sondern als Textblock für phpMyAdmin bereitgestellt.
 */
final class MigrationManager
{
    private static ?bool $tableExists = null;

    /**
     * Privater Konstruktor: rein statische Utility-Klasse.
     */
    private function __construct()
    {
    }

    /**
     * Prüft, ob die Tabelle `migrations` in der Datenbank existiert.
     */
    public static function isTableCreated(): bool
    {
        if (self::$tableExists !== null) {
            return self::$tableExists;
        }

        try {
            $stmt = DB::query("SHOW TABLES LIKE 'migrations'");
            self::$tableExists = ($stmt->fetch() !== false);
        } catch (\Throwable $e) {
            error_log('MigrationManager::isTableCreated Fehler: ' . $e->getMessage());
            self::$tableExists = false;
        }

        return self::$tableExists;
    }

    /**
     * Liefert das SQL-Statement zum Erstellen der Migrations-Tabelle.
     */
    public static function getCreateTableSql(): string
    {
        return <<<'SQL'
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `migration` VARCHAR(191) NOT NULL UNIQUE,
  `batch` INT UNSIGNED NOT NULL DEFAULT 1,
  `executed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
    }

    /**
     * Liefert eine Liste aller bereits in der Datenbank registrierten Migrationen.
     *
     * @return array<string, array{migration: string, batch: int, executed_at: string}>
     */
    public static function getExecutedMigrations(): array
    {
        if (!self::isTableCreated()) {
            return [];
        }

        try {
            $rows = DB::fetchAll('SELECT `migration`, `batch`, `executed_at` FROM `migrations` ORDER BY `id` ASC');
            $map = [];
            foreach ($rows as $row) {
                $name = (string) $row['migration'];
                $map[$name] = [
                    'migration'   => $name,
                    'batch'       => (int) ($row['batch'] ?? 1),
                    'executed_at' => (string) ($row['executed_at'] ?? ''),
                ];
            }
            return $map;
        } catch (\Throwable $e) {
            error_log('MigrationManager::getExecutedMigrations Fehler: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Ermittelt den Modulschlüssel anhand von Metadaten im SQL-Kopf oder Dateinamen.
     */
    public static function detectModuleKey(string $filename, string $content): string
    {
        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (preg_match('/^--\s*Module(?:\s*Key)?:\s*([a-zA-Z0-9_\-]+)/i', $trimmed, $m)) {
                return trim(strtolower($m[1]));
            }
            if (preg_match('/^--\s*Modul:\s*(.+)$/i', $trimmed, $m)) {
                $header = trim($m[1]);
                if (stripos($header, 'Bootsfahrschule') !== false || stripos($header, 'Nautic') !== false) {
                    return 'boating_school';
                }
                if (stripos($header, 'Dokumente') !== false || stripos($header, 'DMS') !== false) {
                    return 'documents';
                }
                if (stripos($header, 'Eigentümerportal') !== false || stripos($header, 'Liegenschaft') !== false) {
                    return 'owner_portal';
                }
                if (stripos($header, 'Sicherungsportal') !== false) {
                    return 'secure_portal';
                }
                if (preg_match('/\b(?:modul(?:_key)?|key):\s*([a-zA-Z0-9_\-]+)/i', $header, $mm)) {
                    return trim(strtolower($mm[1]));
                }
                if (preg_match('/\(([a-zA-Z0-9_\-]+)\)/', $header, $mm)) {
                    $cand = trim(strtolower($mm[1]));
                    if (in_array($cand, ['boating_school', 'documents', 'owner_portal', 'secure_portal', 'core'], true)) {
                        return $cand;
                    }
                }
            }
        }

        // Fallback-Erkennung über den Dateinamen
        if (str_starts_with($filename, '001_core') || str_starts_with($filename, '002_create_modules') || str_starts_with($filename, '003_add_homepage') || str_starts_with($filename, '004_add_requires_core')) {
            return 'core';
        }
        if (str_contains($filename, 'boating_school') || str_contains($filename, 'coach_')) {
            return 'boating_school';
        }
        if (str_contains($filename, 'documents')) {
            return 'documents';
        }
        if (str_contains($filename, 'owner_portal') || str_contains($filename, 'properties')) {
            return 'owner_portal';
        }
        if (str_contains($filename, 'secure_portal') || str_contains($filename, 'secure_cases')) {
            return 'secure_portal';
        }

        return 'core';
    }

    /**
     * Ermittelt alle im Dateisystem unter /migrations vorhandenen SQL-Dateien
     * und verknüpft sie mit dem aktuellen Ausführungsstatus aus der Datenbank.
     *
     * @param string|null $dir Optionaler Pfad zum Migrationsordner
     * @param bool $onlyActiveModules Wenn true (Standard), werden nur Core-Migrationen und Migrationen für aktivierte Module geladen
     * @return array<int, array{
     *     filename: string,
     *     key: string,
     *     title: string,
     *     description: string,
     *     version: string,
     *     module_key: string,
     *     module_name: string,
     *     is_core: bool,
     *     is_module_active: bool,
     *     is_applied: bool,
     *     executed_at: ?string,
     *     batch: ?int,
     *     sql: string,
     *     tracking_sql: string
     * }>
     */
    public static function getAll(?string $dir = null, bool $onlyActiveModules = true): array
    {
        $dir = $dir ?? dirname(__DIR__) . '/migrations';
        $list = [];

        if (!is_dir($dir)) {
            return $list;
        }

        $files = scandir($dir);
        if ($files === false) {
            return $list;
        }

        sort($files, SORT_NATURAL);

        $executed = self::getExecutedMigrations();

        // Moduldaten cachen
        $modulesCache = [];
        if (class_exists('ModuleManager')) {
            foreach (ModuleManager::all(false) as $mod) {
                $mKey = (string) ($mod['key'] ?? '');
                if ($mKey !== '') {
                    $modulesCache[$mKey] = $mod;
                }
            }
        }

        foreach ($files as $file) {
            if (!str_ends_with($file, '.sql') || $file === '.' || $file === '..') {
                continue;
            }

            $filePath = $dir . DIRECTORY_SEPARATOR . $file;
            $content = (string) @file_get_contents($filePath);
            $key = pathinfo($file, PATHINFO_FILENAME);

            // Metadaten aus Dateikopf parsen
            $title = ucfirst(str_replace(['_', '-'], ' ', $key));
            $description = '';
            $version = class_exists('CoreVersion') ? CoreVersion::VERSION : '1.0.0';

            $lines = explode("\n", $content);
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if (preg_match('/^--\s*Title:\s*(.+)$/i', $trimmed, $m)) {
                    $title = trim($m[1]);
                } elseif (preg_match('/^--\s*Description:\s*(.+)$/i', $trimmed, $m)) {
                    $description = trim($m[1]);
                } elseif (preg_match('/^--\s*Version:\s*(.+)$/i', $trimmed, $m)) {
                    $version = trim($m[1]);
                }
            }

            // Modulschlüssel ermitteln
            $moduleKey = self::detectModuleKey($file, $content);
            $isCore = ($moduleKey === 'core');
            $moduleName = 'Core System';
            $isModuleActive = true;

            if (!$isCore) {
                if (isset($modulesCache[$moduleKey])) {
                    $moduleName = (string) ($modulesCache[$moduleKey]['name'] ?? ucfirst($moduleKey));
                    $isModuleActive = (int) ($modulesCache[$moduleKey]['is_enabled'] ?? 0) === 1;
                } else {
                    // Fallback auf Manifest in /modules/<key>/module.php
                    $manifestFile = dirname(__DIR__) . '/modules/' . $moduleKey . '/module.php';
                    if (file_exists($manifestFile)) {
                        $manifest = @include $manifestFile;
                        if (is_array($manifest)) {
                            $moduleName = (string) ($manifest['name'] ?? ucfirst($moduleKey));
                            $isModuleActive = (int) ($manifest['is_enabled'] ?? 1) === 1;
                        }
                    } else {
                        $isModuleActive = false;
                        $moduleName = ucfirst($moduleKey);
                    }
                }

                if ($moduleKey === 'boating_school' && ($moduleName === 'Bootsfahrschule' || $moduleName === 'boating_school')) {
                    $moduleName = 'Nautic';
                }
            }

            // Filterung nach aktiven Modulen
            if ($onlyActiveModules && !$isModuleActive) {
                continue;
            }

            $isApplied = isset($executed[$key]) || isset($executed[$file]);
            $execInfo = $executed[$key] ?? ($executed[$file] ?? null);

            // Fertig vorbereitetes SQL zur Protokollierung in phpMyAdmin
            $trackingSql = sprintf(
                "INSERT INTO `migrations` (`migration`, `batch`, `executed_at`) VALUES ('%s', 1, NOW())\nON DUPLICATE KEY UPDATE `executed_at` = NOW();",
                addslashes($key)
            );

            $list[] = [
                'filename'         => $file,
                'key'              => $key,
                'title'            => $title,
                'description'      => $description,
                'version'          => $version,
                'module_key'       => $moduleKey,
                'module_name'      => $moduleName,
                'is_core'          => $isCore,
                'is_module_active' => $isModuleActive,
                'is_applied'       => $isApplied,
                'executed_at'      => $execInfo ? $execInfo['executed_at'] : null,
                'batch'            => $execInfo ? $execInfo['batch'] : null,
                'sql'              => trim($content),
                'tracking_sql'     => $trackingSql,
            ];
        }

        return $list;
    }

    /**
     * Prüft, ob es noch nicht eingespielte Migrationen gibt.
     */
    public static function hasPending(?string $dir = null, bool $onlyActiveModules = true): bool
    {
        return self::getPendingCount($dir, $onlyActiveModules) > 0;
    }

    /**
     * Liefert die Anzahl noch nicht eingespielter Migrationen.
     */
    public static function getPendingCount(?string $dir = null, bool $onlyActiveModules = true): int
    {
        $all = self::getAll($dir, $onlyActiveModules);
        $pending = 0;
        foreach ($all as $item) {
            if (!$item['is_applied']) {
                $pending++;
            }
        }
        return $pending;
    }

    /**
     * Liefert das kombinierte SQL aller noch ausstehenden Migrationen.
     */
    public static function getCombinedPendingSql(?string $dir = null, bool $onlyActiveModules = true, ?string $moduleKey = null): string
    {
        $all = self::getAll($dir, $onlyActiveModules);
        $parts = [];

        foreach ($all as $item) {
            if ($item['is_applied']) {
                continue;
            }
            if ($moduleKey !== null && $item['module_key'] !== $moduleKey) {
                continue;
            }

            $banner = "-- =====================================================================\n"
                    . "-- Migration: " . $item['filename'] . " (" . $item['module_name'] . ")\n"
                    . "-- =====================================================================\n";
            $parts[] = $banner . $item['sql'] . "\n\n" . $item['tracking_sql'];
        }

        return implode("\n\n", $parts);
    }

    /**
     * Markiert eine Migration in der `migrations`-Tabelle manuell als eingespielt.
     */
    public static function markAsApplied(string $key): bool
    {
        try {
            if (!self::isTableCreated()) {
                DB::execute(self::getCreateTableSql());
                self::$tableExists = true;
            }

            $cleanKey = pathinfo($key, PATHINFO_FILENAME);

            DB::execute(
                'INSERT INTO `migrations` (`migration`, `batch`, `executed_at`) 
                 VALUES (:migration, 1, NOW()) 
                 ON DUPLICATE KEY UPDATE `executed_at` = NOW()',
                ['migration' => $cleanKey]
            );

            return true;
        } catch (\Throwable $e) {
            error_log('MigrationManager::markAsApplied Fehler: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Entfernt den Ausführungsstatus einer Migration aus der `migrations`-Tabelle.
     */
    public static function markAsUnapplied(string $key): bool
    {
        try {
            if (!self::isTableCreated()) {
                return false;
            }

            $cleanKey = pathinfo($key, PATHINFO_FILENAME);

            DB::execute(
                'DELETE FROM `migrations` WHERE `migration` = :key1 OR `migration` = :key2',
                [
                    'key1' => $cleanKey,
                    'key2' => $cleanKey . '.sql',
                ]
            );

            return true;
        } catch (\Throwable $e) {
            error_log('MigrationManager::markAsUnapplied Fehler: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Alias für markAsUnapplied (zur Kompatibilität mit Controllern).
     */
    public static function unmarkApplied(string $key): bool
    {
        return self::markAsUnapplied($key);
    }
}
