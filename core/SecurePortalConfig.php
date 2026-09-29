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

    public const DEFAULT_OBJECTS_CONFIG = [
        [
            'id' => 'obj_1',
            'name' => 'Hauptgebäude / Areal Nord',
            'floors' => [
                'Erdgeschoss (EG)',
                '1. Obergeschoss (+1)',
                '2. Obergeschoss (+2)',
                '3. Obergeschoss (+3)',
                'Aussenbereich / Haupteingang',
            ],
            'colors' => [
                'Keine Farbzuordnung',
                'Blau (Trakt Nord)',
                'Grün (Trakt Ost)',
                'Rot (Trakt Süd)',
            ],
            'parking_spaces' => [
                'Kein Parkplatz / Eingangsbereich',
                'Besucherparkplatz P1',
                'Besucherparkplatz P2',
                'Behindertenparkplatz',
                'Direktionsparkplatz',
                'Lieferantenzone',
            ],
        ],
        [
            'id' => 'obj_2',
            'name' => 'Parkhaus Zentrum',
            'floors' => [
                '3. Untergeschoss (-3)',
                '2. Untergeschoss (-2)',
                '1. Untergeschoss (-1)',
                'Erdgeschoss (EG / Einfahrt)',
                '1. Obergeschoss (+1)',
                '2. Obergeschoss (+2)',
            ],
            'colors' => [
                'Blau (Sektor A)',
                'Gelb (Sektor B)',
                'Rot (Sektor C)',
                'Grün (Sektor D)',
                'Orange (Sektor E)',
            ],
            'parking_spaces' => [
                'Parkplatz 01',
                'Parkplatz 02',
                'Parkplatz 03',
                'Parkplatz 04',
                'Parkplatz 05',
                'Parkplatz 06',
                'Parkplatz 07',
                'Parkplatz 08',
                'Parkplatz 09',
                'Parkplatz 10',
                'Besucherparkplatz',
                'Behindertenparkplatz',
                'Ladezone / E-Ladestation',
            ],
        ],
        [
            'id' => 'obj_3',
            'name' => 'Gewerbepark Ost',
            'floors' => [
                'Untergeschoss (UG)',
                'Erdgeschoss / Werkhalle',
                '1. Obergeschoss (Büros)',
                'Laderampe / Hof',
            ],
            'colors' => [
                'Halle 1 (Gelb)',
                'Halle 2 (Blau)',
                'Halle 3 (Rot)',
                'Verwaltung (Weiss)',
            ],
            'parking_spaces' => [
                'Kundenparkplatz 01-05',
                'Mitarbeiter P1-P20',
                'LKW-Wendeplatz',
                'Laderampe 1',
                'Laderampe 2',
            ],
        ],
        [
            'id' => 'obj_4',
            'name' => 'Wohnüberbauung Süd',
            'floors' => [
                'Einstellhalle (-1)',
                'Erdgeschoss (EG)',
                '1. Obergeschoss (+1)',
                '2. Obergeschoss (+2)',
                '3. Obergeschoss (+3)',
                'Areal / Spielplatz',
            ],
            'colors' => [
                'Haus A (Blau)',
                'Haus B (Grün)',
                'Haus C (Gelb)',
                'Keine Farbzuordnung',
            ],
            'parking_spaces' => [
                'Tiefgaragenplatz 01-15',
                'Tiefgaragenplatz 16-30',
                'Besucherparkplatz 01-06',
                'Veloraum / Veloabstellplatz',
            ],
        ],
    ];

    public const DEFAULT_VIDEO_OBJECTS = [
        'Hauptgebäude / Areal Nord',
        'Parkhaus Zentrum',
        'Gewerbepark Ost',
        'Wohnüberbauung Süd',
    ];

    public const DEFAULT_VIDEO_FLOORS = [
        '3. Untergeschoss (-3)',
        '2. Untergeschoss (-2)',
        '1. Untergeschoss (-1)',
        'Erdgeschoss (EG)',
        '1. Obergeschoss (+1)',
        '2. Obergeschoss (+2)',
        'Aussenbereich / Vorplatz',
    ];

    public const DEFAULT_VIDEO_COLORS = [
        'Blau (Sektor A)',
        'Gelb (Sektor B)',
        'Rot (Sektor C)',
        'Grün (Sektor D)',
        'Orange (Sektor E)',
        'Weiss',
        'Keine Farbzuordnung',
    ];

    public const DEFAULT_VIDEO_PARKING_SPACES = [
        'Kein Parkplatz / Fahrbahn / Gang',
        'Parkplatz 01',
        'Parkplatz 02',
        'Parkplatz 03',
        'Parkplatz 04',
        'Parkplatz 05',
        'Parkplatz 06',
        'Parkplatz 07',
        'Parkplatz 08',
        'Parkplatz 09',
        'Parkplatz 10',
        'Besucherparkplatz',
        'Behindertenparkplatz',
        'Ladezone / E-Ladestation',
    ];

    /**
     * Zerlegt einen zeilenweisen Text in ein bereinigtes Array.
     *
     * @return array<string>
     */
    public static function parseLines(string $input): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($input));
        if ($lines === false) {
            return [];
        }
        $result = [];
        foreach ($lines as $line) {
            $t = trim($line);
            if ($t !== '') {
                $result[] = $t;
            }
        }
        return array_values(array_unique($result));
    }

    /**
     * Formatiert ein Array in zeilenweisen Text für Textareas.
     *
     * @param array<string> $items
     */
    public static function formatLines(array $items): string
    {
        return implode("\n", array_filter(array_map('trim', $items)));
    }

    /**
     * Liefert die vollständige Konfiguration aller Objekte inklusive ihrer spezifischen
     * Stockwerke, Farben und Parkplatz-Nummern.
     *
     * @return array<int, array{id: string, name: string, floors: array<string>, colors: array<string>, parking_spaces: array<string>}>
     */
    public static function getVideoObjectsConfig(): array
    {
        if (class_exists('Settings')) {
            $val = Settings::get('secure_video_objects_config');
            if ($val !== null && trim((string)$val) !== '') {
                $decoded = json_decode((string)$val, true);
                if (is_array($decoded) && !empty($decoded)) {
                    $cleaned = [];
                    foreach ($decoded as $idx => $item) {
                        if (!is_array($item)) {
                            continue;
                        }
                        $name = trim((string)($item['name'] ?? ''));
                        if ($name === '') {
                            continue;
                        }
                        $floors = is_array($item['floors'] ?? null)
                            ? array_values(array_filter(array_map('trim', $item['floors']), static fn($v) => $v !== ''))
                            : self::parseLines((string)($item['floors'] ?? ''));
                        $colors = is_array($item['colors'] ?? null)
                            ? array_values(array_filter(array_map('trim', $item['colors']), static fn($v) => $v !== ''))
                            : self::parseLines((string)($item['colors'] ?? ''));
                        $spaces = is_array($item['parking_spaces'] ?? null)
                            ? array_values(array_filter(array_map('trim', $item['parking_spaces']), static fn($v) => $v !== ''))
                            : self::parseLines((string)($item['parking_spaces'] ?? ''));

                        $cleaned[] = [
                            'id' => (string)($item['id'] ?? ('obj_' . ($idx + 1))),
                            'name' => $name,
                            'floors' => !empty($floors) ? $floors : self::DEFAULT_VIDEO_FLOORS,
                            'colors' => !empty($colors) ? $colors : self::DEFAULT_VIDEO_COLORS,
                            'parking_spaces' => !empty($spaces) ? $spaces : self::DEFAULT_VIDEO_PARKING_SPACES,
                        ];
                    }
                    if (!empty($cleaned)) {
                        return $cleaned;
                    }
                }
            }
        }
        return self::DEFAULT_OBJECTS_CONFIG;
    }

    /**
     * Sucht die Konfiguration für ein bestimmtes Objekt anhand des Namens.
     *
     * @return array{id: string, name: string, floors: array<string>, colors: array<string>, parking_spaces: array<string>}|null
     */
    public static function getObjectConfigByName(string $objectName): ?array
    {
        $nameTrim = trim($objectName);
        if ($nameTrim === '') {
            return null;
        }
        foreach (self::getVideoObjectsConfig() as $c) {
            if (strcasecmp($c['name'], $nameTrim) === 0) {
                return $c;
            }
        }
        return null;
    }

    /**
     * Liefert die Namen aller konfigurierten Objekte / Liegenschaften.
     *
     * @return array<string>
     */
    public static function getVideoObjects(): array
    {
        $config = self::getVideoObjectsConfig();
        $names = [];
        foreach ($config as $c) {
            if (!empty($c['name']) && !in_array($c['name'], $names, true)) {
                $names[] = $c['name'];
            }
        }
        return !empty($names) ? $names : self::DEFAULT_VIDEO_OBJECTS;
    }

    /**
     * Liefert die konfigurierten Stockwerke / Etagen (optional spezifisch für ein Objekt).
     *
     * @return array<string>
     */
    public static function getVideoFloors(?string $objectName = null): array
    {
        if ($objectName !== null && $objectName !== '') {
            $obj = self::getObjectConfigByName($objectName);
            if ($obj !== null && !empty($obj['floors'])) {
                return $obj['floors'];
            }
        }
        return self::DEFAULT_VIDEO_FLOORS;
    }

    /**
     * Liefert die konfigurierten Farben / Farbcodierungen (optional spezifisch für ein Objekt).
     *
     * @return array<string>
     */
    public static function getVideoColors(?string $objectName = null): array
    {
        if ($objectName !== null && $objectName !== '') {
            $obj = self::getObjectConfigByName($objectName);
            if ($obj !== null && !empty($obj['colors'])) {
                return $obj['colors'];
            }
        }
        return self::DEFAULT_VIDEO_COLORS;
    }

    /**
     * Liefert die konfigurierten Parkplatz-Nummern (optional spezifisch für ein Objekt).
     *
     * @return array<string>
     */
    public static function getVideoParkingSpaces(?string $objectName = null): array
    {
        if ($objectName !== null && $objectName !== '') {
            $obj = self::getObjectConfigByName($objectName);
            if ($obj !== null && !empty($obj['parking_spaces'])) {
                return $obj['parking_spaces'];
            }
        }
        return self::DEFAULT_VIDEO_PARKING_SPACES;
    }

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
