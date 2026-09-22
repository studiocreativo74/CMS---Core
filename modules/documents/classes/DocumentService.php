<?php

declare(strict_types=1);

/**
 * DocumentService
 *
 * Fachlogik & Workflow-Orchestrierung für das DMS-Modul:
 * - Sichere Upload-Verarbeitung via core/Upload.php
 * - Integritäts- und Deduplizierungsprüfung (SHA-256)
 * - Autorisierungsprüfung via Rbac & Magic-Admin
 * - Sicheres Streaming von Dateien (Download / Inline-Vorschau)
 */
final class DocumentService
{
    /**
     * Bekannte Dokumenttypen mit deutschsprachiger Bezeichnung und Farb-Zuordnung.
     */
    public const DOC_TYPES = [
        'INVOICE'        => ['label' => 'Rechnung / Beleg', 'color' => '#198754', 'icon' => 'receipt'],
        'CONTRACT'       => ['label' => 'Vertrag / Vereinbarung', 'color' => '#0d6efd', 'icon' => 'file-earmark-lock'],
        'MINUTES'        => ['label' => 'Protokoll / Niederschrift', 'color' => '#6f42c1', 'icon' => 'journal-text'],
        'REGULATION'     => ['label' => 'Hausordnung / Beschluss', 'color' => '#e83e8c', 'icon' => 'clipboard-check'],
        'PLAN'           => ['label' => 'Bauplan / Skizze / Zeichnung', 'color' => '#fd7e14', 'icon' => 'rulers'],
        'CERTIFICATE'    => ['label' => 'Bescheinigung / Zertifikat', 'color' => '#20c997', 'icon' => 'patch-check'],
        'CORRESPONDENCE' => ['label' => 'Schriftverkehr / Brief', 'color' => '#0dcaf0', 'icon' => 'envelope-paper'],
        'STATEMENT'      => ['label' => 'Abrechnung / Wirtschaftsplan', 'color' => '#3d5a80', 'icon' => 'calculator'],
        'MISC'           => ['label' => 'Sonstiges Dokument', 'color' => '#6c757d', 'icon' => 'file-earmark-text'],
    ];

    /**
     * Sichtbarkeits-Optionen (insbesondere für das Eigentümerportal).
     */
    public const VISIBILITIES = [
        'internal'      => 'Nur Intern (Verwaltung & Admins)',
        'owner_portal'  => 'Eigentümerportal (Sichtbar für berechtigte Eigentümer)',
        'tenant_portal' => 'Mieterportal (Sichtbar für Mieter)',
        'public'        => 'Öffentlich (Für alle einsehbar)',
        'admin_only'    => 'Streng Vertraulich (Nur Administratoren)',
    ];

    /**
     * Ermittelt die ID des aktuell handelnden Benutzers.
     */
    public static function getCurrentUserId(): ?int
    {
        if (class_exists('Auth')) {
            $user = Auth::user();
            if ($user && !empty($user['id'])) {
                return (int) $user['id'];
            }
        }

        if (!empty($_SESSION['user_id'])) {
            return (int) $_SESSION['user_id'];
        }

        return null;
    }

    /**
     * Prüft, ob der aktuelle Benutzer Dokumente einsehen darf.
     */
    public static function canView(): bool
    {
        if (!empty($_SESSION['magic_authenticated']) || (class_exists('Auth') && Auth::checkMagic())) {
            return true;
        }

        if (class_exists('Rbac')) {
            return Rbac::can('admin.documents.view') || Rbac::can('admin.documents.manage');
        }

        return !empty($_SESSION['user_id']);
    }

    /**
     * Prüft, ob der aktuelle Benutzer Dokumente verwalten (erstellen, bearbeiten, löschen) darf.
     */
    public static function canManage(): bool
    {
        if (!empty($_SESSION['magic_authenticated']) || (class_exists('Auth') && Auth::checkMagic())) {
            return true;
        }

        if (class_exists('Rbac')) {
            return Rbac::can('admin.documents.manage');
        }

        return !empty($_SESSION['user_id']);
    }

    /**
     * Verarbeitet den Upload eines neuen Dokuments inklusive Erzeugung von Version 1.
     *
     * @param array<string, mixed> $postData
     * @param array<string, mixed> $file
     * @return int ID des erstellten Dokuments
     * @throws RuntimeException
     */
    public static function handleUploadNewDocument(array $postData, array $file): int
    {
        if (!self::canManage()) {
            throw new RuntimeException('Keine ausreichende Berechtigung zum Hochladen von Dokumenten.');
        }

        // Upload-Speicherziel im geschützten Storage-Verzeichnis
        $targetDir = dirname(__DIR__, 2) . '/storage/documents';

        // 1. Datei über das gehärtete Upload-System sichern
        $fileInfo = Upload::saveDocument($file, $targetDir, [
            'application/pdf',
            'application/x-pdf',
            'image/png',
            'image/jpeg',
            'image/webp',
            'image/tiff',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.oasis.opendocument.text',
            'application/vnd.oasis.opendocument.spreadsheet',
            'text/plain',
            'text/csv',
        ]);

        // 2. Dokument-Titel fallbacks
        $title = trim((string) ($postData['title'] ?? ''));
        if ($title === '') {
            $baseName = pathinfo($fileInfo['original_filename'], PATHINFO_FILENAME);
            $title = ucwords(str_replace(['_', '-'], ' ', $baseName));
        }

        $userId = self::getCurrentUserId();

        // 3. Tags parsen
        $tags = [];
        if (!empty($postData['tags']) && is_array($postData['tags'])) {
            $tags = $postData['tags'];
        } elseif (!empty($postData['new_tags_csv'])) {
            $tagNames = explode(',', (string) $postData['new_tags_csv']);
            foreach ($tagNames as $rawTagName) {
                $createdTag = DocumentRepository::findOrCreateTag($rawTagName);
                if ($createdTag) {
                    $tags[] = (int) $createdTag['id'];
                }
            }
        }

        // 4. In der Datenbank anlegen
        return DocumentRepository::createDocument([
            'title'            => $title,
            'description'      => $postData['description'] ?? null,
            'doc_type'         => $postData['doc_type'] ?? 'MISC',
            'status'           => $postData['status'] ?? 'active',
            'document_date'    => !empty($postData['document_date']) ? $postData['document_date'] : date('Y-m-d'),
            'valid_from'       => !empty($postData['valid_from']) ? $postData['valid_from'] : null,
            'valid_until'      => !empty($postData['valid_until']) ? $postData['valid_until'] : null,
            'reference_number' => !empty($postData['reference_number']) ? $postData['reference_number'] : null,
            'visibility'       => $postData['visibility'] ?? 'internal',
            'tags'             => $tags,
            'target_type'      => !empty($postData['target_type']) ? $postData['target_type'] : null,
            'target_id'        => !empty($postData['target_id']) ? $postData['target_id'] : null,
            'target_label'     => !empty($postData['target_label']) ? $postData['target_label'] : null,
            'relation_role'    => !empty($postData['relation_role']) ? $postData['relation_role'] : 'attachment',
        ], $fileInfo, $userId);
    }

    /**
     * Lädt eine neue Version zu einem bestehenden Dokument hoch.
     *
     * @param int $documentId
     * @param array<string, mixed> $file
     * @param string|null $changeNotes
     * @return int Neue Versionsnummer
     * @throws RuntimeException
     */
    public static function handleUploadNewVersion(int $documentId, array $file, ?string $changeNotes = null): int
    {
        if (!self::canManage()) {
            throw new RuntimeException('Keine Berechtigung zum Hochladen neuer Versionen.');
        }

        $targetDir = dirname(__DIR__, 2) . '/storage/documents';

        $fileInfo = Upload::saveDocument($file, $targetDir);
        $userId = self::getCurrentUserId();

        return DocumentRepository::addVersion($documentId, $fileInfo, $changeNotes, $userId);
    }

    /**
     * Sicheres Streamen einer Dokumentdatei zum Browser (Download oder Vorschau).
     *
     * @param int $documentId
     * @param int|null $versionNumber Wenn null, wird die aktuellste Version geliefert
     * @param bool $inline Ob die Datei im Browser geöffnet (inline) oder heruntergeladen werden soll
     */
    public static function streamDocument(int $documentId, ?int $versionNumber = null, bool $inline = true): void
    {
        if (!self::canView()) {
            http_response_code(403);
            die('Zugriff verweigert: Sie haben keine Berechtigung, dieses Dokument herunterzuladen.');
        }

        $doc = DocumentRepository::getDocument($documentId);
        if (!$doc) {
            http_response_code(404);
            die('Dokument nicht gefunden.');
        }

        if ($versionNumber !== null && $versionNumber > 0) {
            $version = DocumentRepository::getVersion($documentId, $versionNumber);
        } else {
            $versions = DocumentRepository::getVersions($documentId);
            $version = $versions[0] ?? null;
        }

        if (!$version) {
            http_response_code(404);
            die('Gewünschte Dateiversion nicht gefunden.');
        }

        $storagePath = (string) ($version['storage_path'] ?? '');

        // Pfad auflösen, falls relativ gespeichert
        if (!file_exists($storagePath)) {
            $storagePath = dirname(__DIR__, 2) . '/' . ltrim($storagePath, '/');
        }

        if (!file_exists($storagePath) || !is_readable($storagePath)) {
            http_response_code(404);
            die('Die physische Datei ist auf dem Server nicht auffindbar.');
        }

        // Audit Log schreiben
        $userId = self::getCurrentUserId();
        DocumentRepository::logActivity($documentId, $inline ? 'view' : 'download', [
            'version'   => $version['version_number'] ?? 1,
            'file_name' => $version['original_filename'] ?? '',
        ], $userId);

        $mimeType = (string) ($version['mime_type'] ?? 'application/octet-stream');
        $filename = (string) ($version['original_filename'] ?? 'dokument');
        $filesize = (int) (filesize($storagePath) ?: ($version['file_size'] ?? 0));

        // Ausgabepuffer leeren
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Header setzen
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . $filesize);
        $disposition = $inline ? 'inline' : 'attachment';
        $encodedFilename = rawurlencode($filename);
        header("Content-Disposition: {$disposition}; filename=\"{$filename}\"; filename*=UTF-8''{$encodedFilename}");
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        header('X-Content-Type-Options: nosniff');

        // Datei an Client senden
        readfile($storagePath);
        exit;
    }

    /**
     * Liefert Typ-Informationen für die Anzeige.
     *
     * @param string $typeKey
     * @return array{label: string, color: string, icon: string}
     */
    public static function getTypeInfo(string $typeKey): array
    {
        return self::DOC_TYPES[$typeKey] ?? [
            'label' => $typeKey,
            'color' => '#6c757d',
            'icon'  => 'file-earmark',
        ];
    }
}
