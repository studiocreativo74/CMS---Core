<?php

declare(strict_types=1);

/**
 * Bootstrap für das Dokumente / DMS-Modul (Paperless-Style)
 *
 * Registriert Menüpunkte, Autoloader/Klassen und Routen.
 *
 * @var Router|null $router
 * @var array<string, mixed> $module
 * @var string $moduleDir
 */

// Klassen des Moduls laden
require_once __DIR__ . '/classes/DocumentRepository.php';
require_once __DIR__ . '/classes/DocumentService.php';

// Admin-Menüpunkt registrieren
if (class_exists('ModuleManager')) {
    ModuleManager::addAdminMenuItem([
        'label' => 'Dokumente',
        'route' => 'admin/documents',
        'url'   => '?route=admin/documents',
        'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-text-fill me-2" viewBox="0 0 16 16"><path d="M9.293 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.707A1 1 0 0 0 13.707 4L10 .293A1 1 0 0 0 9.293 0M9.5 3.5v-2l3 3h-2a1 1 0 0 1-1-1M4.5 9a.5.5 0 0 1 0-1h7a.5.5 0 0 1 0 1zM4 10.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5m.5 2.5a.5.5 0 0 1 0-1h4a.5.5 0 0 1 0 1z"/></svg>',
    ]);
}

// Routen registrieren
if (isset($router) && $router !== null) {
    // -------------------------------------------------------------------------
    // 1. DOKUMENTEN-ÜBERSICHT (PAPERLESS-DASHBOARD)
    // -------------------------------------------------------------------------
    $router->get('/admin/documents', static function (): void {
        if (!DocumentService::canView()) {
            $_SESSION['flash_error'] = 'Zugriff verweigert: Sie haben keine Berechtigung für das Dokumentenarchiv.';
            header('Location: ?route=admin/dashboard');
            exit;
        }

        $tablesCreated = DocumentRepository::isTableCreated();

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $filters = [
            'search'      => trim((string) ($_GET['search'] ?? '')),
            'doc_type'    => trim((string) ($_GET['doc_type'] ?? '')),
            'status'      => trim((string) ($_GET['status'] ?? 'active')),
            'tag_id'      => !empty($_GET['tag_id']) ? (int) $_GET['tag_id'] : null,
            'target_type' => trim((string) ($_GET['target_type'] ?? '')),
            'target_id'   => trim((string) ($_GET['target_id'] ?? '')),
        ];

        $documents = DocumentRepository::getDocuments($filters, $limit, $offset);
        $totalDocuments = DocumentRepository::countDocuments($filters);
        $totalPages = (int) ceil($totalDocuments / $limit);
        $tags = DocumentRepository::getTags();
        $stats = DocumentRepository::getStats();

        $currentRoute = 'admin/documents';
        require __DIR__ . '/views/index.php';
    });

    // -------------------------------------------------------------------------
    // 2. DOKUMENT ERSTELLEN (UPLOAD)
    // -------------------------------------------------------------------------
    $router->post('/admin/documents/create', static function (): void {
        if (!DocumentService::canManage()) {
            $_SESSION['flash_error'] = 'Zugriff verweigert.';
            header('Location: ?route=admin/documents');
            exit;
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF). Bitte versuchen Sie es erneut.';
            header('Location: ?route=admin/documents');
            exit;
        }

        if (empty($_FILES['document_file'])) {
            $_SESSION['flash_error'] = 'Es wurde keine Dokumentdatei übergeben.';
            header('Location: ?route=admin/documents');
            exit;
        }

        try {
            $docId = DocumentService::handleUploadNewDocument($_POST, $_FILES['document_file']);
            $_SESSION['flash_success'] = 'Das Dokument wurde erfolgreich hochgeladen und archiviert.';
            header('Location: ?route=admin/documents/view&id=' . $docId);
            exit;
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Fehler beim Dokument-Upload: ' . $e->getMessage();
            header('Location: ?route=admin/documents');
            exit;
        }
    });

    // -------------------------------------------------------------------------
    // 3. DOKUMENT DETAILANSICHT & VORSCHAU
    // -------------------------------------------------------------------------
    $router->get('/admin/documents/view', static function (): void {
        if (!DocumentService::canView()) {
            $_SESSION['flash_error'] = 'Zugriff verweigert.';
            header('Location: ?route=admin/documents');
            exit;
        }

        $id = (int) ($_GET['id'] ?? 0);
        $document = DocumentRepository::getDocument($id);

        if (!$document) {
            $_SESSION['flash_error'] = 'Das angeforderte Dokument konnte nicht gefunden werden.';
            header('Location: ?route=admin/documents');
            exit;
        }

        $versions = DocumentRepository::getVersions($id);
        $tags = DocumentRepository::getTagsForDocument($id);
        $allTags = DocumentRepository::getTags();
        $relations = DocumentRepository::getRelations($id);
        $activityLogs = DocumentRepository::getActivityLogs($id);

        $currentRoute = 'admin/documents';
        require __DIR__ . '/views/view.php';
    });

    // -------------------------------------------------------------------------
    // 4. METADATEN AKTUALISIEREN
    // -------------------------------------------------------------------------
    $router->post('/admin/documents/edit', static function (): void {
        if (!DocumentService::canManage()) {
            $_SESSION['flash_error'] = 'Zugriff verweigert.';
            header('Location: ?route=admin/documents');
            exit;
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/documents');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['flash_error'] = 'Ungültige Dokument-ID.';
            header('Location: ?route=admin/documents');
            exit;
        }

        $userId = DocumentService::getCurrentUserId();
        $success = DocumentRepository::updateDocument($id, $_POST, $userId);

        if ($success) {
            $_SESSION['flash_success'] = 'Die Metadaten wurden erfolgreich aktualisiert.';
        } else {
            $_SESSION['flash_error'] = 'Fehler beim Aktualisieren der Metadaten.';
        }

        header('Location: ?route=admin/documents/view&id=' . $id);
        exit;
    });

    // -------------------------------------------------------------------------
    // 5. NEUE VERSION HOCHLADEN
    // -------------------------------------------------------------------------
    $router->post('/admin/documents/upload-version', static function (): void {
        if (!DocumentService::canManage()) {
            $_SESSION['flash_error'] = 'Zugriff verweigert.';
            header('Location: ?route=admin/documents');
            exit;
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/documents');
            exit;
        }

        $documentId = (int) ($_POST['document_id'] ?? 0);
        if ($documentId <= 0 || empty($_FILES['document_file'])) {
            $_SESSION['flash_error'] = 'Fehlerhafte Angaben zum Upload der neuen Version.';
            header('Location: ?route=admin/documents');
            exit;
        }

        try {
            $notes = !empty($_POST['change_notes']) ? trim((string) $_POST['change_notes']) : null;
            $newV = DocumentService::handleUploadNewVersion($documentId, $_FILES['document_file'], $notes);
            $_SESSION['flash_success'] = "Neue Version v{$newV} wurde erfolgreich hinzugefügt.";
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Fehler beim Hochladen der neuen Version: ' . $e->getMessage();
        }

        header('Location: ?route=admin/documents/view&id=' . $documentId);
        exit;
    });

    // -------------------------------------------------------------------------
    // 6. SICHERER DOWNLOAD & INLINE-VORSCHAU
    // -------------------------------------------------------------------------
    $router->get('/admin/documents/download', static function (): void {
        $id = (int) ($_GET['id'] ?? 0);
        $version = !empty($_GET['version']) ? (int) $_GET['version'] : null;
        $inline = isset($_GET['inline']) && (string) $_GET['inline'] === '1';

        DocumentService::streamDocument($id, $version, $inline);
    });

    // -------------------------------------------------------------------------
    // 7. DOKUMENT ARCHIVIEREN / LÖSCHEN
    // -------------------------------------------------------------------------
    $router->post('/admin/documents/delete', static function (): void {
        if (!DocumentService::canManage()) {
            $_SESSION['flash_error'] = 'Zugriff verweigert.';
            header('Location: ?route=admin/documents');
            exit;
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken.';
            header('Location: ?route=admin/documents');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $userId = DocumentService::getCurrentUserId();

        if ($id > 0 && DocumentRepository::deleteDocument($id, false, $userId)) {
            $_SESSION['flash_success'] = 'Das Dokument wurde erfolgreich ins Archiv verschoben.';
        } else {
            $_SESSION['flash_error'] = 'Fehler beim Archivieren des Dokuments.';
        }

        header('Location: ?route=admin/documents');
        exit;
    });

    // -------------------------------------------------------------------------
    // 8. SCHLAGWORTE (TAGS) VERWALTUNG
    // -------------------------------------------------------------------------
    $router->get('/admin/documents/tags', static function (): void {
        if (!DocumentService::canView()) {
            $_SESSION['flash_error'] = 'Zugriff verweigert.';
            header('Location: ?route=admin/documents');
            exit;
        }

        $tags = DocumentRepository::getTags();
        $currentRoute = 'admin/documents';
        require __DIR__ . '/views/tags.php';
    });

    $router->post('/admin/documents/tags/save', static function (): void {
        if (!DocumentService::canManage()) {
            $_SESSION['flash_error'] = 'Zugriff verweigert.';
            header('Location: ?route=admin/documents/tags');
            exit;
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges CSRF-Token.';
            header('Location: ?route=admin/documents/tags');
            exit;
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        $color = trim((string) ($_POST['color'] ?? '#0d6efd'));

        if ($name !== '') {
            DocumentRepository::findOrCreateTag($name, $color);
            $_SESSION['flash_success'] = 'Schlagwort wurde angelegt.';
        }

        header('Location: ?route=admin/documents/tags');
        exit;
    });

    $router->post('/admin/documents/tags/delete', static function (): void {
        if (!DocumentService::canManage()) {
            $_SESSION['flash_error'] = 'Zugriff verweigert.';
            header('Location: ?route=admin/documents/tags');
            exit;
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges CSRF-Token.';
            header('Location: ?route=admin/documents/tags');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0 && DocumentRepository::deleteTag($id)) {
            $_SESSION['flash_success'] = 'Schlagwort wurde gelöscht.';
        }

        header('Location: ?route=admin/documents/tags');
        exit;
    });

    // -------------------------------------------------------------------------
    // 9. EIGENTÜMERPORTAL-VERKNÜPFUNG (RELATIONEN)
    // -------------------------------------------------------------------------
    $router->post('/admin/documents/relation/add', static function (): void {
        if (!DocumentService::canManage()) {
            $_SESSION['flash_error'] = 'Zugriff verweigert.';
            header('Location: ?route=admin/documents');
            exit;
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges CSRF-Token.';
            header('Location: ?route=admin/documents');
            exit;
        }

        $docId = (int) ($_POST['document_id'] ?? 0);
        $targetType = trim((string) ($_POST['target_type'] ?? ''));
        $targetId = trim((string) ($_POST['target_id'] ?? ''));
        $targetLabel = !empty($_POST['target_label']) ? trim((string) $_POST['target_label']) : null;
        $role = !empty($_POST['relation_role']) ? trim((string) $_POST['relation_role']) : 'attachment';

        if ($docId > 0 && $targetType !== '' && $targetId !== '') {
            DocumentRepository::addRelation($docId, $targetType, $targetId, $targetLabel, $role);
            $_SESSION['flash_success'] = 'Objekt-Verknüpfung wurde erfolgreich hinzugefügt.';
        } else {
            $_SESSION['flash_error'] = 'Unvollständige Angaben zur Verknüpfung.';
        }

        header('Location: ?route=admin/documents/view&id=' . $docId);
        exit;
    });

    $router->post('/admin/documents/relation/delete', static function (): void {
        if (!DocumentService::canManage()) {
            $_SESSION['flash_error'] = 'Zugriff verweigert.';
            header('Location: ?route=admin/documents');
            exit;
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges CSRF-Token.';
            header('Location: ?route=admin/documents');
            exit;
        }

        $docId = (int) ($_POST['document_id'] ?? 0);
        $relId = (int) ($_POST['relation_id'] ?? 0);

        if ($relId > 0) {
            DocumentRepository::removeRelation($relId);
            $_SESSION['flash_success'] = 'Verknüpfung wurde entfernt.';
        }

        header('Location: ?route=admin/documents/view&id=' . $docId);
        exit;
    });
}
