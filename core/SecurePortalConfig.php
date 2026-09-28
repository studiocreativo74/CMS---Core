<?php

declare(strict_types=1);

/**
 * SecurePortalConfig
 *
 * Verwaltet zentrale Pfade, Namenskonventionen und Verzeichnisstrukturen
 * für das behördliche Sicherungsportal (secure_portal).
 *
 * Vorgaben & Konvention:
 * - Basis-Verzeichnis: uploads/secure/cases
 * - Fall-Verzeichnis:  uploads/secure/cases/{case_number}/
 * - Dateiname:         {case_number}_Editionsverfuegung_v{version}.pdf
 *   (z. B. POL-2026-000123_Editionsverfuegung_v1.pdf)
 */
final class SecurePortalConfig
{
    public const DEFAULT_DOWNLOAD_DAYS_ACTIVE = 30;
    public const DEFAULT_DOWNLOAD_DAYS_DELETE = 60;

    /**
     * Liefert die Anzahl Tage nach Bereitstellung, bis der externe Download-Zugang gesperrt wird.
     * Standard: 30 Tage (T+30)
     */
    public static function getDownloadDaysActive(): int
    {
        if (class_exists('Settings')) {
            $val = Settings::get('secure_download_days_active');
            if ($val !== null && is_numeric($val) && (int)$val > 0) {
                return (int)$val;
            }
        }
        return self::DEFAULT_DOWNLOAD_DAYS_ACTIVE;
    }

    /**
     * Liefert die Anzahl Tage nach Bereitstellung, bis die Sicherungsdaten auf dem Server gelöscht werden.
     * Standard: 60 Tage (T+60)
     */
    public static function getDownloadDaysDelete(): int
    {
        if (class_exists('Settings')) {
            $val = Settings::get('secure_download_days_delete');
            if ($val !== null && is_numeric($val) && (int)$val > 0) {
                return (int)$val;
            }
        }
        return self::DEFAULT_DOWNLOAD_DAYS_DELETE;
    }

    /**
     * Gibt den absoluten Basispfad für alle Sicherungsfälle auf dem Webspace zurück.
     * Standard: <Projekt-Root>/uploads/secure/cases
     */
    public static function getCaseBaseDir(): string
    {
        // 1. Optionale Überschreibung via Settings (Datenbank-Tabelle `settings`)
        if (class_exists('Settings')) {
            try {
                $custom = Settings::get('secure_case_base_dir');
                if ($custom !== null && trim((string)$custom) !== '' && is_dir(trim((string)$custom))) {
                    return rtrim(trim((string)$custom), '/');
                }
            } catch (\Throwable $e) {
                // Bei DB-Ausfall Fallback verwenden
            }
        }

        // 2. Standard-Pfad relativ zum ermittelten Projekt-Root
        $root = self::detectProjectRoot();
        return $root . '/uploads/secure/cases';
    }

    /**
     * Gibt den relativen Basis-URL-Pfad für Fall-Ablagen zurück.
     * Standard: '/uploads/secure/cases'
     */
    public static function getCaseBaseUrl(): string
    {
        return '/uploads/secure/cases';
    }

    /**
     * Bereinigt eine Vorgangsnummer (case_number) für sichere Ordner- und Dateinamen.
     * Erlaubt: A-Z, a-z, 0-9, Bindestrich, Unterstrich.
     */
    public static function sanitizeCaseNumber(string $caseNumber): string
    {
        $clean = preg_replace('/[^a-zA-Z0-9_\-]/', '_', trim($caseNumber));
        return !empty($clean) ? $clean : 'CASE_UNKNOWN';
    }

    /**
     * Gibt den absoluten Pfad zum Ordner eines bestimmten Vorgangs zurück.
     * z. B. /uploads/secure/cases/POL-2026-000123
     *
     * @param string $caseNumber Vorgangs-ID (z. B. 'POL-2026-000123')
     * @param bool   $create     Falls true, wird der Ordner automatisch mit Schutzdateien angelegt
     * @param array<string, mixed> $caseData Optionale Vorgangsdaten für Namens-Templates
     */
    public static function getCaseDir(string $caseNumber, bool $create = false, array $caseData = []): string
    {
        if (class_exists('Naming')) {
            $folder = Naming::buildCaseFolder($caseNumber, $caseData);
        } else {
            $folder = self::sanitizeCaseNumber($caseNumber);
        }

        $baseDir = self::getCaseBaseDir();
        $caseDir = $baseDir . '/' . $folder;

        if ($create && !is_dir($caseDir)) {
            self::ensureDirectoryExists($caseDir);
        }

        return $caseDir;
    }

    /**
     * Erzeugt den standardisierten Dateinamen der Editionsverfügung aus der Vorgangs-ID.
     * Schema: Standardmäßig über Naming::buildWarrantFileName oder Fallback:
     * {case_number}_Editionsverfuegung_v{version}.pdf
     *
     * @param string $caseNumber Vorgangs-ID (z. B. 'POL-2026-000123')
     * @param int    $version    Versionsnummer (Fallback)
     * @param array<string, mixed> $caseData Optionale Vorgangsdaten für Namens-Templates
     */
    public static function buildWarrantFileName(string $caseNumber, int $version = 1, array $caseData = []): string
    {
        if (class_exists('Naming')) {
            return Naming::buildWarrantFileName($caseNumber, $caseData);
        }

        $safeNumber = self::sanitizeCaseNumber($caseNumber);
        $v = max(1, $version);
        return "{$safeNumber}_Editionsverfuegung_v{$v}.pdf";
    }

    /**
     * Gibt den relativen Pfad (ausgehend vom Webroot) zur Editionsverfügung zurück.
     * z. B. 'uploads/secure/cases/POL-2026-000123/POL-2026-000123_Editionsverfuegung_v1.pdf'
     *
     * @param string $caseNumber Vorgangs-ID
     * @param int    $version    Versionsnummer (Fallback)
     * @param array<string, mixed> $caseData Optionale Vorgangsdaten für Namens-Templates
     */
    public static function buildWarrantRelativePath(string $caseNumber, int $version = 1, array $caseData = []): string
    {
        if (class_exists('Naming')) {
            $folder   = Naming::buildCaseFolder($caseNumber, $caseData);
            $fileName = Naming::buildWarrantFileName($caseNumber, $caseData);
        } else {
            $folder   = self::sanitizeCaseNumber($caseNumber);
            $fileName = self::buildWarrantFileName($caseNumber, $version);
        }

        return "uploads/secure/cases/{$folder}/{$fileName}";
    }

    /**
     * Gibt das Verzeichnis für Sicherungsdaten (Archive, Beweismitteldateien) des Falls zurück.
     * z. B. uploads/secure/cases/{FALLORDNER}/data
     */
    public static function getCaseDataDir(string $caseNumber, bool $create = false, array $caseData = []): string
    {
        $caseDir = self::getCaseDir($caseNumber, $create, $caseData);
        $dataDir = $caseDir . '/data';
        if ($create && !is_dir($dataDir)) {
            self::ensureDirectoryExists($dataDir);
        }
        return $dataDir;
    }

    /**
     * Ermittelt den relativen Pfad (ausgehend vom Webroot) zu einem Fallordner oder einer Datei.
     */
    public static function getCaseRelativePath(string $caseNumber, string $subPath = '', array $caseData = []): string
    {
        if (class_exists('Naming')) {
            $folder = Naming::buildCaseFolder($caseNumber, $caseData);
        } else {
            $folder = self::sanitizeCaseNumber($caseNumber);
        }

        $rel = "uploads/secure/cases/{$folder}";
        if ($subPath !== '') {
            $rel .= '/' . ltrim($subPath, '/');
        }
        return $rel;
    }

    /**
     * Gibt den absoluten Pfad zur Editionsverfügung im Fallordner zurück.
     */
    public static function getCaseWarrantFullPath(string $caseNumber, int $version = 1, array $caseData = []): string
    {
        return self::getCaseDir($caseNumber, false, $caseData) . '/' . self::buildWarrantFileName($caseNumber, $version, $caseData);
    }

    /**
     * Löst einen relativen oder absoluten Pfad zur Datei zuverlässig im Dateisystem auf.
     */
    public static function resolveLocalFilePath(string $relPath): ?string
    {
        $clean = trim($relPath);
        if ($clean === '') {
            return null;
        }

        // 1. Direkt vorhanden (absolut oder relatives CWD)
        if (file_exists($clean)) {
            return $clean;
        }

        // 2. Ausgehend vom Projekt-Root
        $root = self::detectProjectRoot();
        $candidate1 = $root . '/' . ltrim($clean, '/');
        if (file_exists($candidate1)) {
            return $candidate1;
        }

        // 3. Fallback: Suche ausgehend vom Basis-Ordner
        $caseBase = self::getCaseBaseDir();
        $candidate2 = $caseBase . '/' . ltrim($clean, '/');
        if (file_exists($candidate2)) {
            return $candidate2;
        }

        return null;
    }

    /**
     * Stellt sicher, dass das Basis-Verzeichnis existiert und geschützt ist.
     */
    public static function ensureBaseDirectory(): void
    {
        $baseDir = self::getCaseBaseDir();
        if (!is_dir($baseDir)) {
            self::ensureDirectoryExists($baseDir);
        }
    }

    /**
     * Erstellt ein Verzeichnis sicher und platziert Schutzdateien (.htaccess und index.html).
     */
    public static function ensureDirectoryExists(string $dir): void
    {
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        // Leere index.html gegen Verzeichnis-Listing
        $indexFile = $dir . '/index.html';
        if (!file_exists($indexFile)) {
            @file_put_contents($indexFile, "<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body>Directory access is forbidden.</body></html>\n");
        }

        // .htaccess im Basisordner uploads/secure/cases hinterlegen
        $baseDir = self::getCaseBaseDir();
        if (is_dir($baseDir) && !file_exists($baseDir . '/.htaccess')) {
            $htContent = "# StudioCreativo CMS - Secure Cases Protection\n"
                       . "Options -Indexes -ExecCGI\n"
                       . "<FilesMatch \"(?i)\\.(php|phtml|php3|php4|php5|php7|php8|phps|cgi|pl|sh|bash|py)$\">\n"
                       . "    Order Deny,Allow\n"
                       . "    Deny from all\n"
                       . "</FilesMatch>\n";
            @file_put_contents($baseDir . '/.htaccess', $htContent);
        }
    }

    /**
     * Ermittelt den absoluten Projekt-Root.
     */
    private static function detectProjectRoot(): string
    {
        if (defined('APP_ROOT')) {
            return (string) constant('APP_ROOT');
        }

        if (file_exists(__DIR__ . '/../uploads') || file_exists(__DIR__ . '/../index.php')) {
            return realpath(__DIR__ . '/..') ?: dirname(__DIR__);
        }

        if (file_exists(__DIR__ . '/../../../uploads') || file_exists(__DIR__ . '/../../../index.php')) {
            return realpath(__DIR__ . '/../../..') ?: dirname(__DIR__, 3);
        }

        return dirname(__DIR__);
    }
}
