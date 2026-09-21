<?php

declare(strict_types=1);

/**
 * MigrationManager
 *
 * Verwaltet Datenbank-Migrationen für den CMS-Core.
 * Liest Migrations-Dateien aus /migrations, gleicht sie mit der Tabelle `migrations` ab
 * und stellt saubere SQL-Statements zur manuellen Ausführung in phpMyAdmin bereit.
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
     * Ermittelt alle im Dateisystem unter /migrations vorhandenen SQL-Dateien
     * und verknüpft sie mit dem aktuellen Ausführungsstatus aus der Datenbank.
     *
     * @param string|null $dir Optionaler Pfad zum Migrationsordner
     * @return array<int, array{
     *     filename: string,
     *     key: string,
     *     title: string,
     *     description: string,
     *     version: string,
     *     is_applied: bool,
     *     executed_at: ?string,
     *     batch: ?int,
     *     sql: string,
     *     tracking_sql: string
     * }>
     */
    public static function getAll(string $dir = null): array
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

        foreach ($files as $file) {
            if (!str_ends_with($file, '.sql') || $file === '.' || $file === '..') {
                continue;
            }

            $filePath = $dir . DIRECTORY_SEPARATOR . $file;
            $content = (string) @file_get_contents($filePath);
            $key = pathinfo($file, PATHINFO_FILENAME);

            // Metadaten aus Dateikopf parsen (z.B. -- Title: ..., -- Version: ..., -- Description: ...)
            $title = ucfirst(str_replace(['_', '-'], ' ', $key));
            $description = '';
            $version = CoreVersion::VERSION;

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

            $isApplied = isset($executed[$key]) || isset($executed[$file]);
            $execInfo = $executed[$key] ?? ($executed[$file] ?? null);

            // Fertig vorbereitetes SQL zur Protokollierung in phpMyAdmin
            $trackingSql = sprintf(
                "INSERT INTO `migrations` (`migration`, `batch`, `executed_at`) VALUES ('%s', 1, NOW())\nON DUPLICATE KEY UPDATE `executed_at` = NOW();",
                addslashes($key)
            );

            $list[] = [
                'filename'     => $file,
                'key'          => $key,
                'title'        => $title,
                'description'  => $description,
                'version'      => $version,
                'is_applied'   => $isApplied,
                'executed_at'  => $execInfo ? $execInfo['executed_at'] : null,
                'batch'        => $execInfo ? $execInfo['batch'] : null,
                'sql'          => trim($content),
                'tracking_sql' => $trackingSql,
            ];
        }

        return $list;
    }

    /**
     * Prüft, ob es noch nicht eingespielte Migrationen gibt.
     */
    public static function hasPending(): bool
    {
        return self::getPendingCount() > 0;
    }

    /**
     * Liefert die Anzahl noch nicht eingespielter Migrationen.
     */
    public static function getPendingCount(): int
    {
        $all = self::getAll();
        $pending = 0;
        foreach ($all as $item) {
            if (!$item['is_applied']) {
                $pending++;
            }
        }
        return $pending;
    }

    /**
     * Markiert eine Migration in der `migrations`-Tabelle manuell als eingespielt.
     * (Wird genutzt, wenn der Admin das SQL in phpMyAdmin ausgeführt hat und die UI synchronisieren möchte).
     */
    public static function markAsApplied(string $key): void
    {
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
    }

    /**
     * Entfernt den Ausführungsstatus einer Migration aus der `migrations`-Tabelle.
     */
    public static function markAsUnapplied(string $key): void
    {
        if (!self::isTableCreated()) {
            return;
        }

        $cleanKey = pathinfo($key, PATHINFO_FILENAME);

        DB::execute(
            'DELETE FROM `migrations` WHERE `migration` = :key1 OR `migration` = :key2',
            [
                'key1' => $cleanKey,
                'key2' => $cleanKey . '.sql',
            ]
        );
    }
}
