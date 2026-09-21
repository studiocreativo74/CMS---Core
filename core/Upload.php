<?php

declare(strict_types=1);

/**
 * Upload Management & Hardening
 *
 * Verwaltet sichere Datei- und Bild-Uploads im CMS:
 * - Strikte MIME-Type-Validierung über Server-MIME (finfo, kein Vertrauen in $_FILES['type'])
 * - Generierung kollisionssicherer, zufälliger Dateinamen
 * - Erweiterungen werden ausschließlich aus dem verifizierten MIME-Typ abgeleitet
 * - Bildintegritätsprüfung (getimagesize) für Rastergrafiken
 * - XSS- & XXE-Schutz für SVG-Vektorgrafiken
 * - Schutz des Upload-Verzeichnisses vor Skript-Ausführung (.htaccess / index.html)
 * - Sichere relative Pfadrückgabe für Webroot und Datenbank
 */
final class Upload
{
    /**
     * Zuordnung von verifizierten MIME-Types zu sicheren Dateiendungen.
     */
    private const MIME_EXTENSION_MAP = [
        'image/jpeg'    => 'jpg',
        'image/pjpeg'   => 'jpg',
        'image/png'     => 'png',
        'image/x-png'   => 'png',
        'image/webp'    => 'webp',
        'image/gif'     => 'gif',
        'image/svg+xml' => 'svg',
    ];

    /**
     * Privater Konstruktor: Statische Hilfsklasse.
     */
    private function __construct()
    {
    }

    /**
     * Speichert ein hochgeladenes Bild (Logo, Medien) sicher im Dateisystem.
     *
     * @param array<string, mixed> $file         Eintrag aus $_FILES['...']
     * @param string               $targetDir    Absoluter Pfad zum Ziel-Uploadverzeichnis
     * @param array<string>        $allowedMime  Erlaubte MIME-Types (z.B. ['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'])
     * @param int                  $maxBytes     Maximal erlaubte Größe in Bytes (Standard: 2 MB)
     * @param string               $prefix       Präfix für den erzeugten Dateinamen (z.B. 'logo_')
     *
     * @return string Pfad relativ zum Webroot (z.B. 'uploads/homepage/logo_20260921_abc123.png')
     *
     * @throws RuntimeException bei Fehlern mit klar verständlicher deutscher Meldung
     */
    public static function saveImage(
        array $file,
        string $targetDir,
        array $allowedMime = ['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml', 'image/gif'],
        int $maxBytes = 2097152, // 2 MB
        string $prefix = 'img_'
    ): string {
        // 1. Upload-Array und Fehlercode prüfen
        self::validateUploadArray($file, $maxBytes);

        $tmpName = (string) ($file['tmp_name'] ?? '');

        // 2. Sicherheitsprüfung: Handelt es sich um einen echten HTTP-Upload?
        if (!is_uploaded_file($tmpName)) {
            throw new RuntimeException('Sicherheitswarnung: Die angegebene Datei stammt nicht aus einem regulären Upload.');
        }

        // 3. Echten MIME-Type serverseitig ermitteln (niemals $_FILES['type'] vertrauen)
        $detectedMime = self::detectMimeType($tmpName);

        // MIME-Typ gegen die Whitelist abgleichen
        if (!in_array($detectedMime, $allowedMime, true)) {
            $readableAllowed = self::humanReadableMimes($allowedMime);
            throw new RuntimeException(
                "Ungültiges Dateiformat (erkannt: '{$detectedMime}'). Erlaubt sind ausschließlich: {$readableAllowed}."
            );
        }

        // 4. Erweiterte Bild-Integritätsprüfung
        if ($detectedMime === 'image/svg+xml') {
            // SVG auf gefährliche Skripte, Event-Handler und XXE prüfen
            if (!self::isSvgSafe($tmpName)) {
                throw new RuntimeException(
                    'Die SVG-Grafik enthält potenziell unsichere Inhalte (Skripte, Event-Handler oder externe Entitäten) und wurde abgewiesen.'
                );
            }
            $extension = 'svg';
        } else {
            // Rastergrafiken mit getimagesize validieren
            $imageInfo = @getimagesize($tmpName);
            if ($imageInfo === false) {
                throw new RuntimeException('Die hochgeladene Datei enthält keine gültigen Bilddaten.');
            }

            $extension = self::MIME_EXTENSION_MAP[$detectedMime] ?? 'png';
        }

        // 5. Zielverzeichnis vorbereiten und absichern
        $targetDir = rtrim(str_replace('\\', '/', $targetDir), '/');
        if (!is_dir($targetDir)) {
            if (!@mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
                throw new RuntimeException("Das Upload-Verzeichnis '{$targetDir}' konnte nicht erstellt werden. Bitte Schreibrechte prüfen.");
            }
        }

        // Verzeichnis vor Skript-Ausführung und Directory-Browsing absichern
        self::secureUploadDirectory($targetDir);

        // 6. Eindeutigen, kollisionssicheren Dateinamen generieren (ohne Benutzereinfluss)
        $cleanPrefix = preg_replace('/[^a-zA-Z0-9_\-]/', '', $prefix);
        if ($cleanPrefix === '') {
            $cleanPrefix = 'img_';
        }

        $filename = sprintf(
            '%s%s_%s.%s',
            $cleanPrefix,
            date('Ymd_His'),
            bin2hex(random_bytes(6)),
            $extension
        );

        $destinationPath = $targetDir . '/' . $filename;

        // 7. Datei an den endgültigen Ort verschieben
        if (!@move_uploaded_file($tmpName, $destinationPath)) {
            throw new RuntimeException('Die hochgeladene Datei konnte nicht im Zielverzeichnis gespeichert werden. Bitte Serverberechtigungen prüfen.');
        }

        // Dateirechte restriktiv setzen (nicht ausführbar)
        @chmod($destinationPath, 0644);

        // 8. Relativen Webroot-Pfad für Speicherung in der Datenbank berechnen
        return self::calculateRelativeWebPath($destinationPath);
    }

    /**
     * Validiert das $_FILES-Array und verarbeitet PHP-Upload-Fehlercodes.
     *
     * @param array<string, mixed> $file
     * @param int                  $maxBytes
     * @throws RuntimeException
     */
    private static function validateUploadArray(array $file, int $maxBytes): void
    {
        if (empty($file) || !isset($file['tmp_name']) || !isset($file['error'])) {
            throw new RuntimeException('Keine Datei oder fehlerhaftes Upload-Formular übergeben.');
        }

        $errorCode = (int) $file['error'];
        switch ($errorCode) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                throw new RuntimeException('Es wurde keine Datei für den Upload ausgewählt.');
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new RuntimeException('Die Datei überschreitet das vom Server erlaubte maximale Upload-Limit.');
            case UPLOAD_ERR_PARTIAL:
                throw new RuntimeException('Die Datei wurde nur teilweise übertragen. Bitte versuchen Sie es erneut.');
            case UPLOAD_ERR_NO_TMP_DIR:
                throw new RuntimeException('Serverfehler: Das temporäre Upload-Verzeichnis fehlt.');
            case UPLOAD_ERR_CANT_WRITE:
                throw new RuntimeException('Serverfehler: Die Datei konnte nicht auf das Speichermedium geschrieben werden.');
            default:
                throw new RuntimeException("Fehler beim Datei-Upload (PHP-Code {$errorCode}).");
        }

        $fileSize = (int) ($file['size'] ?? 0);
        if ($fileSize <= 0) {
            throw new RuntimeException('Die hochgeladene Datei ist leer (0 Bytes).');
        }

        if ($fileSize > $maxBytes) {
            $formattedLimit = self::formatBytes($maxBytes);
            $formattedSize = self::formatBytes($fileSize);
            throw new RuntimeException(
                "Die Datei ist zu groß ({$formattedSize}). Die maximal erlaubte Dateigröße beträgt {$formattedLimit}."
            );
        }
    }

    /**
     * Ermittelt den echten MIME-Type einer Datei über finfo.
     */
    public static function detectMimeType(string $filePath): string
    {
        if (!file_exists($filePath)) {
            throw new RuntimeException('Die zu prüfende Datei existiert nicht.');
        }

        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($filePath);
            if (is_string($mime) && $mime !== '') {
                // Manche WebP-Dateien werden als image/x-webp erkannt
                if ($mime === 'image/x-webp') {
                    return 'image/webp';
                }
                return strtolower($mime);
            }
        }

        if (function_exists('mime_content_type')) {
            $mime = mime_content_type($filePath);
            if (is_string($mime) && $mime !== '') {
                return strtolower($mime);
            }
        }

        return 'application/octet-stream';
    }

    /**
     * Prüft eine SVG-Datei auf gefährliche Skripte, Event-Handler und externe Entitäten (XSS-Schutz).
     */
    public static function isSvgSafe(string $filePath): bool
    {
        $content = @file_get_contents($filePath);
        if ($content === false || trim($content) === '') {
            return false;
        }

        // 1. XML-Entity-Expansion (XXE) / DOCTYPE-Attacken
        if (preg_match('/<!ENTITY|SYSTEM|PUBLIC/i', $content)) {
            return false;
        }

        // 2. Gefährliche HTML/SVG-Tags
        if (preg_match('/<\s*(script|foreignobject|iframe|embed|object|meta|link|base)/i', $content)) {
            return false;
        }

        // 3. Inline-Event-Handler (onload, onerror, onclick, onmouseover etc.)
        if (preg_match('/\s+on[a-z]+\s*=/i', $content)) {
            return false;
        }

        // 4. Javascript-Pseudoprotokolle (href="javascript:...", xlink:href="javascript:...")
        if (preg_match('/(href|src|data)\s*=\s*["\']\s*(javascript|vbscript|data):/i', $content)) {
            return false;
        }

        return true;
    }

    /**
     * Schützt ein Upload-Verzeichnis durch Anlegen einer .htaccess- und index.html-Datei.
     */
    public static function secureUploadDirectory(string $targetDir): void
    {
        // 1. .htaccess: PHP- und Skript-Ausführung im Upload-Ordner strikt verbieten
        $htaccessPath = $targetDir . '/.htaccess';
        if (!file_exists($htaccessPath)) {
            $htaccessContent = <<<EOT
# StudioCreativo CMS - Upload Protection
# Verhindert jegliche Skriptausführung im Upload-Verzeichnis
<FilesMatch "(?i)\.(php|phtml|php3|php4|php5|php7|php8|phps|phar|inc|cgi|pl|py|sh|bash|js|jsp|asp|aspx|exe|shtml)$">
    Order Deny,Allow
    Deny from all
</FilesMatch>

Options -ExecCGI -Indexes

<IfModule mod_php.c>
    php_flag engine off
</IfModule>
<IfModule mod_php7.c>
    php_flag engine off
</IfModule>
<IfModule mod_php8.c>
    php_flag engine off
</IfModule>
EOT;
            @file_put_contents($htaccessPath, $htaccessContent);
        }

        // 2. Leere index.html gegen Directory Listing
        $indexPath = $targetDir . '/index.html';
        if (!file_exists($indexPath)) {
            @file_put_contents($indexPath, '<!DOCTYPE html><html><head><meta charset="utf-8"><title>403 Forbidden</title></head><body>Directory listing is prohibited.</body></html>');
        }
    }

    /**
     * Löscht eine zuvor hochgeladene Datei sicher aus dem Dateisystem.
     *
     * @param string $relativeOrAbsolutePath Relativer oder absoluter Pfad
     * @return bool True bei erfolgreicher Löschung
     */
    public static function deleteFile(string $relativeOrAbsolutePath): bool
    {
        $path = trim($relativeOrAbsolutePath);
        if ($path === '' || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return false;
        }

        // Verhindere Path Traversal
        if (str_contains($path, '..')) {
            return false;
        }

        $baseDir = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');
        $effectiveBase = $baseDir !== '' ? $baseDir : '/';

        if (str_starts_with($path, '/') || (strlen($path) > 1 && $path[1] === ':')) {
            $absolutePath = str_replace('\\', '/', $path);
        } else {
            $absolutePath = rtrim($effectiveBase, '/') . '/' . ltrim($path, '/');
        }

        // Prüfen, ob die Datei innerhalb des Projektverzeichnisses liegt
        if ($effectiveBase !== '/' && !str_starts_with($absolutePath, $effectiveBase)) {
            return false;
        }

        if (is_file($absolutePath) && file_exists($absolutePath)) {
            return @unlink($absolutePath);
        }

        return false;
    }

    /**
     * Berechnet den relativen Pfad der Datei bezogen auf das CMS-Wurzelverzeichnis.
     */
    private static function calculateRelativeWebPath(string $absoluteFilePath): string
    {
        $normalizedFile = str_replace('\\', '/', $absoluteFilePath);
        $baseDir = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');

        if ($baseDir !== '' && str_starts_with($normalizedFile, $baseDir)) {
            return ltrim(substr($normalizedFile, strlen($baseDir)), '/');
        }

        return ltrim($normalizedFile, '/');
    }

    /**
     * Formatiert eine Byte-Angabe in eine lesbare Zeichenkette (z.B. "2 MB").
     */
    public static function formatBytes(int $bytes, int $precision = 1): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min((int) $pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Erzeugt eine lesbare Liste von Dateiendungen aus MIME-Types für Fehlermeldungen.
     *
     * @param array<string> $mimes
     * @return string
     */
    private static function humanReadableMimes(array $mimes): string
    {
        $extensions = [];
        foreach ($mimes as $mime) {
            $ext = self::MIME_EXTENSION_MAP[$mime] ?? null;
            if ($ext !== null) {
                $extensions[] = strtoupper($ext);
            }
        }
        $extensions = array_unique($extensions);

        return !empty($extensions) ? implode(', ', $extensions) : implode(', ', $mimes);
    }
}
