<?php

declare(strict_types=1);

/**
 * Naming
 *
 * Verwaltet konfigurierbare Namens-Templates und sichere Bereinigungen
 * für Ordnerstrukturen und Dateinamen im Sicherungsportal (secure_portal).
 *
 * Unterstützte Platzhalter:
 * - {CASE_NUMBER}   -> Offizielle Vorgangs-ID (z. B. POL-2026-000123)
 * - {YEAR}          -> Erfassungsjahr JJJJ (z. B. 2026)
 * - {DATE_Y}        -> Alias für {YEAR}
 * - {CITY}          -> Ort der Dienststelle / Falldaten bereinigt (z. B. Zuerich)
 * - {ORT}           -> Synonym für {CITY}
 * - {DATE}          -> Erfassungsdatum JJJJ-MM-TT (z. B. 2026-09-26)
 * - {DATE_YMD}      -> Kompaktes Erfassungsdatum JJJJMMTT (z. B. 20260926)
 * - {AKTENZEICHEN}  -> Behörden-Aktenzeichen / Fall Nr bereinigt (z. B. 2026_5678)
 * - {FALL_NR}       -> Alias für {AKTENZEICHEN}
 * - {DEPARTMENT}    -> Dienststelle bereinigt (z. B. Kantonspolizei_Zuerich)
 */
final class Naming
{
    public const DEFAULT_FOLDER_TEMPLATE = '{YEAR}/{CITY}/{CASE_NUMBER}';
    public const DEFAULT_FILENAME_TEMPLATE = '{CASE_NUMBER}_Editionsverfuegung_{DATE}_v1.pdf';

    /**
     * Erzeugt den Ordnernamen (relativ zum Basis-Verzeichnis) auf Basis des Templates und der Case-Daten.
     * Template aus Settings: secure_case_folder_template (Standard: '{YEAR}/{CITY}/{CASE_NUMBER}')
     *
     * @param string $caseNumber Vorgangsnummer (z. B. POL-2026-000123)
     * @param array<string, mixed> $caseData Zusätzliche Vorgangsdaten (city, police_department, created_at, etc.)
     * @return string Relativer Ordnerpfad (z. B. '2026/Zuerich/POL-2026-000123' oder '2026/POL-2026-000123')
     */
    public static function buildCaseFolder(string $caseNumber, array $caseData = []): string
    {
        $template = self::DEFAULT_FOLDER_TEMPLATE;
        if (class_exists('Settings')) {
            $custom = (string) Settings::get('secure_case_folder_template', self::DEFAULT_FOLDER_TEMPLATE);
            if (trim($custom) !== '') {
                $template = trim($custom);
            }
        }

        $resolved = self::resolveTemplate($template, $caseNumber, $caseData);
        $clean = self::sanitizeName($resolved, true);

        // Fallback, falls Template-Auflösung leer war
        if ($clean === '') {
            $clean = self::sanitizeName($caseNumber, false);
            if ($clean === '') {
                $clean = 'CASE_UNKNOWN';
            }
        }

        return $clean;
    }

    /**
     * Erzeugt den Dateinamen der Editionsverfügung auf Basis des Templates und der Case-Daten.
     * Template aus Settings: secure_case_warrant_filename_template (Standard: '{CASE_NUMBER}_Editionsverfuegung_{DATE}_v1.pdf')
     *
     * @param string $caseNumber Vorgangsnummer (z. B. POL-2026-000123)
     * @param array<string, mixed> $caseData Zusätzliche Vorgangsdaten
     * @return string Sicherer Dateiname (z. B. 'POL-2026-000123_Editionsverfuegung_2026-09-26_v1.pdf')
     */
    public static function buildWarrantFileName(string $caseNumber, array $caseData = []): string
    {
        $template = self::DEFAULT_FILENAME_TEMPLATE;
        if (class_exists('Settings')) {
            $custom = (string) Settings::get('secure_case_warrant_filename_template', self::DEFAULT_FILENAME_TEMPLATE);
            if (trim($custom) !== '') {
                $template = trim($custom);
            }
        }

        $resolved = self::resolveTemplate($template, $caseNumber, $caseData);

        // Dateinamen dürfen niemals Slashes oder Pfadseparatoren enthalten
        $clean = self::sanitizeName($resolved, false);

        // Sicherstellen, dass die Dateiendung .pdf erhalten bleibt
        if (!preg_match('/\.pdf$/i', $clean)) {
            $clean .= '.pdf';
        }

        // Falls das Ergebnis rein ".pdf" oder leer ist, sicheren Fallback anwenden
        if ($clean === '.pdf' || $clean === '') {
            $safeNum = self::sanitizeName($caseNumber, false);
            $clean = ($safeNum !== '' ? $safeNum : 'CASE') . '_Editionsverfuegung_v1.pdf';
        }

        return $clean;
    }

    /**
     * Ermittelt den Ort aus den Vorgangsdaten.
     * Prüft zuerst direkte Felder (city, ort, location, stadt)
     * und leitet andernfalls den Ort aus der Dienststelle (police_department) ab.
     *
     * @param array<string, mixed> $caseData
     * @return string Unbereinigter Ortsname (oder leer)
     */
    public static function extractCity(array $caseData): string
    {
        // 1. Direkte Ortsfelder prüfen
        foreach (['city', 'ort', 'location', 'stadt'] as $key) {
            if (!empty($caseData[$key]) && is_string($caseData[$key])) {
                $trimmed = trim($caseData[$key]);
                if ($trimmed !== '') {
                    return $trimmed;
                }
            }
        }

        // 2. Aus Dienststelle (police_department) ableiten
        if (!empty($caseData['police_department']) && is_string($caseData['police_department'])) {
            $dept = trim((string) $caseData['police_department']);
            if ($dept === '') {
                return '';
            }

            // Fall a: Mit Komma (z. B. "Polizei, Zürich" oder "Dienststelle, Bern")
            if (strpos($dept, ',') !== false) {
                $parts = explode(',', $dept);
                $candidate = trim(end($parts));
                if ($candidate !== '') {
                    return $candidate;
                }
            }

            // Fall b: Bindestrich getrennt (z. B. "Kantonspolizei Bern - Posten Thun")
            if (strpos($dept, ' - ') !== false) {
                $parts = explode(' - ', $dept);
                $candidate = trim(end($parts));
                if (preg_match('/^(?:posten|revier|dienststelle|station|inspektion|abschnitt)\s+(.+)$/ui', $candidate, $m)) {
                    return trim($m[1]);
                }
                if ($candidate !== '') {
                    return $candidate;
                }
            }

            // Fall c: Behörden-Präfixe entfernen (z. B. "Kantonspolizei Zürich", "Stadtpolizei Winterthur",
            // "Polizeipräsidium München", "Staatsanwaltschaft St. Gallen", "Kapo ZH", "Stapo Zürich", "PP Frankfurt")
            $pattern = '/^(?:kantonspolizei|stadtpolizei|landespolizei|bundespolizei|polizeipr[aä]sidium|polizeiinspektion|polizeidirektion|polizeirevier|polizeiposten|polizei|staatsanwaltschaft|kapo|stapo|pp)\s+(?:der\s+stadt\s+|in\s+|f[uü]r\s+|von\s+)?([a-zA-ZäöüÄÖÜß\.\-\s]+)$/ui';
            if (preg_match($pattern, $dept, $matches)) {
                $candidate = trim($matches[1]);
                if ($candidate !== '') {
                    return $candidate;
                }
            }

            // Fall d: Falls die Dienststelle selbst keine behördlichen Schlüsselwörter enthält (z. B. "Zürich" oder "Basel")
            if (!preg_match('/\b(?:polizei|dienststelle|beh[oö]rde|amt|staatsanwaltschaft|revier|posten)\b/ui', $dept)) {
                return $dept;
            }
        }

        return '';
    }

    /**
     * Interne Helper-Funktion: Platzhalter ersetzen & Ergebnis bereinigen.
     *
     * @param string $template
     * @param string $caseNumber
     * @param array<string, mixed> $caseData
     * @return string
     */
    private static function resolveTemplate(string $template, string $caseNumber, array $caseData): string
    {
        // 1. Datum ermitteln (aus created_at des Falls oder aktuelle Serverzeit)
        $timestamp = time();
        if (!empty($caseData['created_at'])) {
            $parsed = strtotime((string) $caseData['created_at']);
            if ($parsed !== false && $parsed > 0) {
                $timestamp = $parsed;
            }
        }

        $dateFormatted = date('Y-m-d', $timestamp);
        $dateY         = date('Y', $timestamp);
        $dateYmd       = date('Ymd', $timestamp);

        // 2. Fall-Attribute bereinigen
        $cleanCaseNumber = self::sanitizeName($caseNumber, false);
        $rawRef          = (string) ($caseData['reference_number'] ?? '');
        $cleanRef        = self::sanitizeName($rawRef, false);
        $rawDept         = (string) ($caseData['police_department'] ?? '');
        $cleanDept       = self::sanitizeName($rawDept, false);

        // 3. Ort ermitteln und bereinigen (synonym für {CITY} und {ORT})
        $rawCity         = self::extractCity($caseData);
        $cleanCity       = self::sanitizeName($rawCity, false);

        // 4. Platzhalter-Ersetzungen durchführen
        $replacements = [
            '{CASE_NUMBER}'  => $cleanCaseNumber,
            '{YEAR}'         => $dateY,
            '{DATE_Y}'       => $dateY,
            '{CITY}'         => $cleanCity,
            '{ORT}'          => $cleanCity,
            '{DATE}'         => $dateFormatted,
            '{DATE_YMD}'     => $dateYmd,
            '{AKTENZEICHEN}' => $cleanRef !== '' ? $cleanRef : 'REF_UNKNOWN',
            '{FALL_NR}'      => $cleanRef !== '' ? $cleanRef : 'REF_UNKNOWN',
            '{DEPARTMENT}'   => $cleanDept !== '' ? $cleanDept : 'BEHOERDE',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * Interne Helper-Funktion: „Sanitizer“ für Ordner- und Dateinamen.
     *
     * @param string $name Zu bereinigender String
     * @param bool $allowSlashes Ob Schrägstriche für Verzeichnishierarchien erlaubt sind
     * @return string Bereinigter Name
     */
    private static function sanitizeName(string $name, bool $allowSlashes = false): string
    {
        $clean = trim($name);
        if ($clean === '') {
            return '';
        }

        // Umlaute und Sonderzeichen leserlich transliterieren
        $clean = str_replace(
            ['ä', 'ö', 'ü', 'Ä', 'Ö', 'Ü', 'ß', ' '],
            ['ae', 'oe', 'ue', 'Ae', 'Oe', 'Ue', 'ss', '_'],
            $clean
        );

        if ($allowSlashes) {
            // Backslashes zu Forward-Slashes vereinheitlichen
            $clean = str_replace('\\', '/', $clean);

            // Directory-Traversal-Schutz: ".." und Null-Bytes entfernen
            $clean = str_replace(["\0", '..'], '', $clean);

            // Mehrfache Slashes zusammenfassen
            $clean = preg_replace('#/{2,}#', '/', $clean) ?? $clean;

            // Führende und abschließende Slashes entfernen
            $clean = trim($clean, '/');

            // Segmente einzeln prüfen und bereinigen
            $parts = explode('/', $clean);
            $sanitizedParts = [];
            foreach ($parts as $p) {
                // Erlaubte Zeichen in Verzeichnispfaden: A-Z, a-z, 0-9, Bindestrich, Unterstrich, Punkt
                $sp = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', trim($p));
                $sp = trim($sp, '. _');
                if ($sp !== '') {
                    $sanitizedParts[] = $sp;
                }
            }

            return implode('/', $sanitizedParts);
        }

        // Für Dateinamen: Slashes, Backslashes, Nullbytes und ".." strikt entfernen
        $clean = str_replace(['/', '\\', "\0", '..'], '_', $clean);
        // Erlaubt: A-Z, a-z, 0-9, Bindestrich, Unterstrich, Punkt
        $clean = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $clean) ?? $clean;
        // Mehrfache Unterstriche auf einen einzelnen reduzieren
        $clean = preg_replace('/_+/', '_', $clean) ?? $clean;
        // Trennzeichen vor .pdf bereinigen (z. B. aus leeren Platzhaltern wie "name_.pdf")
        $clean = preg_replace('/[_\-]+(\.pdf)$/i', '$1', $clean) ?? $clean;

        return trim($clean, '._ ');
    }
}

