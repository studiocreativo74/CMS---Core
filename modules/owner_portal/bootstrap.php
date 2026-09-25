<?php

declare(strict_types=1);

/**
 * Bootstrap für das Modul "Eigentümerportal"
 *
 * Registriert Menüpunkte, Autoloader/Klassen und alle Admin- sowie Portal-Routen.
 *
 * @var Router|null $router
 * @var array<string, mixed> $module
 * @var string $moduleDir
 */

// Klassen des Moduls einbinden
require_once __DIR__ . '/classes/PortalRepository.php';
require_once __DIR__ . '/classes/PortalService.php';

// Menüpunkte im Admin-Bereich registrieren
if (class_exists('ModuleManager')) {
    // 1. Liegenschaften & Einheiten (Verwaltung)
    ModuleManager::addAdminMenuItem([
        'label' => 'Liegenschaften',
        'route' => 'admin/portal/properties',
        'url'   => '?route=admin/portal/properties',
        'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-buildings-fill me-2" viewBox="0 0 16 16"><path d="M15 .5a.5.5 0 0 0-.724-.447l-8 4A.5.5 0 0 0 6 4.5v3.14L.342 9.526A.5.5 0 0 0 0 10v5.5a.5.5 0 0 0 .5.5h15a.5.5 0 0 0 .5-.5V.5ZM2 11h1v1H2zm2 0h1v1H4zm-2 2h1v1H2zm2 0h1v1H4zm4-4h1v1H8zm2 0h1v1h-1zm-2 2h1v1H8zm2 0h1v1h-1zm-2 2h1v1H8zm2 0h1v1h-1zM8 7h1v1H8zm2 0h1v1h-1zM8 5h1v1H8zm2 0h1v1h-1zm2 2h1v1h-1zm0 2h1v1h-1zm0 2h1v1h-1zm0 2h1v1h-1z"/></svg>',
    ]);

    // 2. Vorgänge & Gebäudeakten (Verwaltung)
    ModuleManager::addAdminMenuItem([
        'label' => 'Vorgänge & Akten',
        'route' => 'admin/portal/cases',
        'url'   => '?route=admin/portal/cases',
        'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-folder2-open me-2" viewBox="0 0 16 16"><path d="M1 3.5A1.5 1.5 0 0 1 2.5 2h2.764c.958 0 1.76.56 2.311 1.184C7.985 3.648 8.48 4 9 4h4.5A1.5 1.5 0 0 1 15 5.5v.64c.57.265.94.876.856 1.546l-.64 5.124A2.5 2.5 0 0 1 12.733 15H3.266a2.5 2.5 0 0 1-2.481-2.19l-.64-5.124A1.5 1.5 0 0 1 1 6.14zM2 6h12v-.5a.5.5 0 0 0-.5-.5H9c-.964 0-1.71-.629-2.174-1.154C6.374 3.334 5.82 3 5.264 3H2.5a.5.5 0 0 0-.5.5zm-.367 1a.5.5 0 0 0-.496.562l.64 5.124A1.5 1.5 0 0 0 3.266 14h9.468a1.5 1.5 0 0 0 1.489-1.314l.64-5.124A.5.5 0 0 0 14.367 7z"/></svg>',
    ]);

    // 3. Eigentümerportal Dashboard (Für Eigentümer, Mieter, Beiräte & Admins)
    ModuleManager::addAdminMenuItem([
        'label' => 'Eigentümerportal',
        'route' => 'portal/dashboard',
        'url'   => '?route=portal/dashboard',
        'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-badge-fill me-2" viewBox="0 0 16 16"><path d="M2 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2zm4.5 0a.5.5 0 0 0 0 1h3a.5.5 0 0 0 0-1zM8 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6m5 2.755C12.146 12.825 10.623 12 8 12s-4.146.826-5 1.755V14a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1z"/></svg>',
    ]);
}

// Routen registrieren
if (isset($router) && $router !== null) {

    // =========================================================================
    // HILFSFUNKTION FÜR AUTH-CHECK
    // =========================================================================
    $ensureAuthenticated = static function (): array {
        $user = PortalService::getCurrentUser();
        if ($user === null) {
            $_SESSION['flash_error'] = 'Bitte melden Sie sich an, um auf diesen Bereich zuzugreifen.';
            header('Location: ?route=login');
            exit;
        }
        return $user;
    };

    // =========================================================================
    // ADMIN: LIEGENSCHAFTEN ÜBERSICHT
    // =========================================================================
    $router->get('/admin/portal/properties', static function (): void {
        if (!PortalService::canManageProperties() && !PortalService::canViewProperties()) {
            $_SESSION['flash_error'] = 'Zugriff verweigert: Sie haben keine Berechtigung für Liegenschaften.';
            header('Location: ?route=admin/dashboard');
            exit;
        }

        $filters = [
            'search' => trim((string) ($_GET['search'] ?? '')),
            'city'   => trim((string) ($_GET['city'] ?? '')),
        ];

        $properties = PortalRepository::getProperties($filters);
        $totalProperties = PortalRepository::countProperties($filters);
        $totalUnits = PortalRepository::countAllUnits();
        $totalCases = PortalRepository::countAllCases();
        $search = $filters['search'];
        $tablesCreated = PortalRepository::isTableCreated();

        require __DIR__ . '/views/admin/properties_index.php';
    });

    // =========================================================================
    // ADMIN: LIEGENSCHAFT ERSTELLEN
    // =========================================================================
    $router->post('/admin/portal/property/create', static function (): void {
        if (!PortalService::canManageProperties()) {
            $_SESSION['flash_error'] = 'Zugriff verweigert.';
            header('Location: ?route=admin/portal/properties');
            exit;
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/portal/properties');
            exit;
        }

        try {
            $propId = PortalService::createProperty($_POST);
            $_SESSION['flash_success'] = 'Die Liegenschaft wurde erfolgreich angelegt.';
            header('Location: ?route=admin/portal/property&id=' . $propId);
            exit;
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Fehler beim Anlegen: ' . $e->getMessage();
            header('Location: ?route=admin/portal/properties');
            exit;
        }
    });

    // =========================================================================
    // ADMIN: LIEGENSCHAFT DETAILANSICHT
    // =========================================================================
    $router->get('/admin/portal/property', static function (): void {
        $id = (int) ($_GET['id'] ?? 0);
        $property = PortalRepository::findProperty($id);

        if ($property === null) {
            $_SESSION['flash_error'] = 'Liegenschaft nicht gefunden.';
            header('Location: ?route=admin/portal/properties');
            exit;
        }

        if (!PortalService::canAccessProperty($id)) {
            $_SESSION['flash_error'] = 'Zugriff verweigert.';
            header('Location: ?route=admin/portal/properties');
            exit;
        }

        $units = PortalRepository::getUnitsByProperty($id);
        $cases = PortalRepository::getCases(['property_id' => $id]);
        $documents = PortalService::getDocumentsForEntity('property', $id);
        $allDmsDocuments = class_exists('DocumentRepository') ? DocumentRepository::getDocuments([], 100) : [];

        require __DIR__ . '/views/admin/property_detail.php';
    });

    // =========================================================================
    // ADMIN: LIEGENSCHAFT AKTUALISIEREN
    // =========================================================================
    $router->post('/admin/portal/property/update', static function (): void {
        if (!PortalService::canManageProperties()) {
            $_SESSION['flash_error'] = 'Zugriff verweigert.';
            header('Location: ?route=admin/portal/properties');
            exit;
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/portal/properties');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        try {
            PortalService::updateProperty($id, $_POST);
            $_SESSION['flash_success'] = 'Liegenschaftsdaten wurden aktualisiert.';
            header('Location: ?route=admin/portal/property&id=' . $id);
            exit;
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Fehler beim Aktualisieren: ' . $e->getMessage();
            header('Location: ?route=admin/portal/property&id=' . $id);
            exit;
        }
    });

    // =========================================================================
    // ADMIN: EINHEIT ERSTELLEN
    // =========================================================================
    $router->post('/admin/portal/unit/create', static function (): void {
        if (!PortalService::canManageProperties()) {
            $_SESSION['flash_error'] = 'Zugriff verweigert.';
            header('Location: ?route=admin/portal/properties');
            exit;
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/portal/properties');
            exit;
        }

        $propertyId = (int) ($_POST['property_id'] ?? 0);
        try {
            $unitId = PortalService::createUnit($_POST);
            $_SESSION['flash_success'] = 'Einheit wurde erfolgreich angelegt.';
            header('Location: ?route=admin/portal/unit&id=' . $unitId);
            exit;
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Fehler beim Anlegen der Einheit: ' . $e->getMessage();
            header('Location: ?route=admin/portal/property&id=' . $propertyId);
            exit;
        }
    });

    // =========================================================================
    // ADMIN: EINHEIT DETAILANSICHT
    // =========================================================================
    $router->get('/admin/portal/unit', static function (): void {
        $id = (int) ($_GET['id'] ?? 0);
        $unit = PortalRepository::findUnit($id);

        if ($unit === null) {
            $_SESSION['flash_error'] = 'Einheit nicht gefunden.';
            header('Location: ?route=admin/portal/properties');
            exit;
        }

        if (!PortalService::canAccessProperty((int) $unit['property_id'])) {
            $_SESSION['flash_error'] = 'Zugriff verweigert.';
            header('Location: ?route=admin/portal/properties');
            exit;
        }

        $assignedUsers = PortalRepository::getAssignedUsersForUnit($id);
        $unitUsers = $assignedUsers;
        $allUsers = PortalRepository::getAllUsers();
        $cases = PortalRepository::getCases(['unit_id' => $id]);
        $documents = PortalService::getDocumentsForEntity('unit', $id);
        $allDmsDocuments = class_exists('DocumentRepository') ? DocumentRepository::getDocuments([], 100) : [];

        require __DIR__ . '/views/admin/unit_detail.php';
    });

    // =========================================================================
    // ADMIN: EINHEIT AKTUALISIEREN
    // =========================================================================
    $router->post('/admin/portal/unit/update', static function (): void {
        if (!PortalService::canManageProperties()) {
            $_SESSION['flash_error'] = 'Zugriff verweigert.';
            header('Location: ?route=admin/portal/properties');
            exit;
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/portal/properties');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        try {
            PortalService::updateUnit($id, $_POST);
            $_SESSION['flash_success'] = 'Einheit wurde erfolgreich aktualisiert.';
            header('Location: ?route=admin/portal/unit&id=' . $id);
            exit;
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Fehler beim Aktualisieren: ' . $e->getMessage();
            header('Location: ?route=admin/portal/unit&id=' . $id);
            exit;
        }
    });

    // =========================================================================
    // ADMIN: BENUTZER ZU EINHEIT ZUORDNEN
    // =========================================================================
    $router->post('/admin/portal/unit/user/assign', static function (): void {
        if (!PortalService::canManageProperties()) {
            $_SESSION['flash_error'] = 'Zugriff verweigert.';
            header('Location: ?route=admin/portal/properties');
            exit;
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/portal/properties');
            exit;
        }

        $unitId = (int) ($_POST['unit_id'] ?? 0);
        $userId = (int) ($_POST['user_id'] ?? 0);
        $relationType = trim((string) ($_POST['relation_type'] ?? 'owner'));

        try {
            PortalService::assignUserToUnit($unitId, $userId, $relationType);
            $_SESSION['flash_success'] = 'Benutzer wurde der Einheit erfolgreich zugewiesen.';
            header('Location: ?route=admin/portal/unit&id=' . $unitId);
            exit;
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Fehler bei der Zuweisung: ' . $e->getMessage();
            header('Location: ?route=admin/portal/unit&id=' . $unitId);
            exit;
        }
    });

    // =========================================================================
    // ADMIN: BENUTZER-ZUORDNUNG ENTFERNEN
    // =========================================================================
    $router->post('/admin/portal/unit/user/remove', static function (): void {
        if (!PortalService::canManageProperties()) {
            $_SESSION['flash_error'] = 'Zugriff verweigert.';
            header('Location: ?route=admin/portal/properties');
            exit;
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/portal/properties');
            exit;
        }

        $assignmentId = (int) ($_POST['assignment_id'] ?? 0);
        $unitId = (int) ($_POST['unit_id'] ?? 0);

        try {
            PortalRepository::removeUserFromUnit($assignmentId);
            $_SESSION['flash_success'] = 'Zuweisung wurde entfernt.';
            header('Location: ?route=admin/portal/unit&id=' . $unitId);
            exit;
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Fehler beim Entfernen: ' . $e->getMessage();
            header('Location: ?route=admin/portal/unit&id=' . $unitId);
            exit;
        }
    });

    // =========================================================================
    // ADMIN: DOKUMENT AUS DMS MIT PROPERTY/UNIT/CASE VERKNÜPFEN
    // =========================================================================
    $router->post('/admin/portal/document/link', static function (): void {
        if (!PortalService::canManageProperties() && !PortalService::canManageCases()) {
            $_SESSION['flash_error'] = 'Zugriff verweigert.';
            header('Location: ?route=admin/portal/properties');
            exit;
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/portal/properties');
            exit;
        }

        $docId = (int) ($_POST['document_id'] ?? 0);
        $targetType = trim((string) ($_POST['target_type'] ?? 'property'));
        $targetId = (int) ($_POST['target_id'] ?? 0);
        $relationRole = trim((string) ($_POST['relation_role'] ?? 'attachment'));

        try {
            PortalService::linkDocument($docId, $targetType, $targetId, $relationRole);
            $_SESSION['flash_success'] = 'Dokument wurde erfolgreich verknüpft.';
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Fehler beim Verknüpfen: ' . $e->getMessage();
        }

        if ($targetType === 'property') {
            header('Location: ?route=admin/portal/property&id=' . $targetId);
        } elseif ($targetType === 'unit') {
            header('Location: ?route=admin/portal/unit&id=' . $targetId);
        } elseif ($targetType === 'case') {
            header('Location: ?route=admin/portal/case&id=' . $targetId);
        } else {
            header('Location: ?route=admin/portal/properties');
        }
        exit;
    });

    // =========================================================================
    // ADMIN: VORGÄNGE / CASES ÜBERSICHT
    // =========================================================================
    $router->get('/admin/portal/cases', static function (): void {
        if (!PortalService::canManageCases() && !PortalService::canViewCases()) {
            $_SESSION['flash_error'] = 'Zugriff verweigert: Sie haben keine Berechtigung für Vorgänge.';
            header('Location: ?route=admin/dashboard');
            exit;
        }

        $filters = [
            'case_type'   => trim((string) ($_GET['case_type'] ?? '')),
            'property_id' => !empty($_GET['property_id']) ? (int) $_GET['property_id'] : null,
            'status'      => trim((string) ($_GET['status'] ?? '')),
            'search'      => trim((string) ($_GET['search'] ?? '')),
        ];

        $cases = PortalRepository::getCases($filters);
        $totalCases = count($cases);
        $properties = PortalRepository::getProperties();

        require __DIR__ . '/views/admin/cases_index.php';
    });

    // =========================================================================
    // ADMIN: VORGANG ANLEGEN
    // =========================================================================
    $router->post('/admin/portal/case/create', static function (): void {
        if (!PortalService::canManageCases()) {
            $_SESSION['flash_error'] = 'Zugriff verweigert.';
            header('Location: ?route=admin/portal/cases');
            exit;
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/portal/cases');
            exit;
        }

        try {
            $caseId = PortalService::createCase($_POST);
            $_SESSION['flash_success'] = 'Vorgang wurde erfolgreich angelegt.';
            header('Location: ?route=admin/portal/case&id=' . $caseId);
            exit;
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Fehler beim Anlegen: ' . $e->getMessage();
            header('Location: ?route=admin/portal/cases');
            exit;
        }
    });

    // =========================================================================
    // ADMIN: VORGANG DETAILANSICHT
    // =========================================================================
    $router->get('/admin/portal/case', static function (): void {
        $id = (int) ($_GET['id'] ?? 0);
        $case = PortalRepository::findCase($id);

        if ($case === null) {
            $_SESSION['flash_error'] = 'Vorgang nicht gefunden.';
            header('Location: ?route=admin/portal/cases');
            exit;
        }

        if (!PortalService::canAccessProperty((int) $case['property_id'])) {
            $_SESSION['flash_error'] = 'Zugriff verweigert.';
            header('Location: ?route=admin/portal/cases');
            exit;
        }

        $messages = PortalRepository::getCaseMessages($id, true); // inkl. interne Notizen für Admin
        $documents = PortalService::getDocumentsForEntity('case', $id);
        $allDmsDocuments = class_exists('DocumentRepository') ? DocumentRepository::getDocuments([], 100) : [];

        require __DIR__ . '/views/admin/case_detail.php';
    });

    // =========================================================================
    // ADMIN: VORGANGS-STATUS AKTUALISIEREN
    // =========================================================================
    $router->post('/admin/portal/case/update-status', static function (): void {
        if (!PortalService::canManageCases()) {
            $_SESSION['flash_error'] = 'Zugriff verweigert.';
            header('Location: ?route=admin/portal/cases');
            exit;
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/portal/cases');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $status = trim((string) ($_POST['status'] ?? 'new'));
        $priority = trim((string) ($_POST['priority'] ?? 'normal'));

        try {
            PortalService::updateCaseStatus($id, $status, $priority);
            $_SESSION['flash_success'] = 'Status und Priorität wurden aktualisiert.';
            header('Location: ?route=admin/portal/case&id=' . $id);
            exit;
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Fehler beim Aktualisieren: ' . $e->getMessage();
            header('Location: ?route=admin/portal/case&id=' . $id);
            exit;
        }
    });

    // =========================================================================
    // ADMIN: NACHRICHT / INTERNE NOTIZ AN VORGANG ANHÄNGEN
    // =========================================================================
    $router->post('/admin/portal/case/message', static function (): void {
        if (!PortalService::canManageCases()) {
            $_SESSION['flash_error'] = 'Zugriff verweigert.';
            header('Location: ?route=admin/portal/cases');
            exit;
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/portal/cases');
            exit;
        }

        $caseId = (int) ($_POST['case_id'] ?? 0);
        $message = trim((string) ($_POST['message'] ?? ''));
        $isInternal = !empty($_POST['is_internal']);

        try {
            PortalService::addCaseMessage($caseId, $message, $isInternal);
            $_SESSION['flash_success'] = 'Nachricht wurde hinzugefügt.';
            header('Location: ?route=admin/portal/case&id=' . $caseId);
            exit;
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Fehler beim Speichern: ' . $e->getMessage();
            header('Location: ?route=admin/portal/case&id=' . $caseId);
            exit;
        }
    });

    // =========================================================================
    // PORTAL (EIGENTÜMER / MIETER): DASHBOARD
    // =========================================================================
    $router->get('/portal/dashboard', static function () use ($ensureAuthenticated): void {
        $user = $ensureAuthenticated();
        $userId = (int) $user['id'];

        $myUnits = PortalRepository::getUnitsForUser($userId);
        $myProperties = PortalRepository::getPropertiesForUser($userId);
        $myCases = PortalService::getUserCases($userId);
        $myDocuments = PortalService::getUserDocuments($userId);
        $stats = PortalRepository::getStats($userId);

        require __DIR__ . '/views/portal/dashboard.php';
    });

    // =========================================================================
    // PORTAL (EIGENTÜMER / MIETER): SCHADENMELDUNG ERFASSEN (FORMULAR)
    // =========================================================================
    $router->get('/portal/damage/report', static function () use ($ensureAuthenticated): void {
        $user = $ensureAuthenticated();
        $userId = (int) $user['id'];

        $userUnits = PortalRepository::getUnitsForUser($userId);
        $properties = PortalRepository::getPropertiesForUser($userId);

        // Falls Magic-Admin oder Verwalter ohne Zuweisung, alle Liegenschaften anbieten
        if (empty($properties) && (PortalService::isMagicAdmin() || PortalService::canManageProperties())) {
            $properties = PortalRepository::getProperties();
        }

        require __DIR__ . '/views/portal/report_damage.php';
    });

    // =========================================================================
    // PORTAL (EIGENTÜMER / MIETER): SCHADENMELDUNG ABSENDEN
    // =========================================================================
    $router->post('/portal/damage/submit', static function () use ($ensureAuthenticated): void {
        $user = $ensureAuthenticated();

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=portal/damage/report');
            exit;
        }

        try {
            $attachment = !empty($_FILES['attachment']) ? $_FILES['attachment'] : null;
            $caseId = PortalService::submitDamageReport($_POST, $attachment);

            $_SESSION['flash_success'] = 'Vielen Dank! Ihre Schadensmeldung wurde erfolgreich übermittelt.';
            header('Location: ?route=portal/case&id=' . $caseId);
            exit;
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Fehler bei der Schadensmeldung: ' . $e->getMessage();
            header('Location: ?route=portal/damage/report');
            exit;
        }
    });

    // =========================================================================
    // PORTAL (EIGENTÜMER / MIETER): MEINE DOKUMENTE
    // =========================================================================
    $router->get('/portal/documents', static function () use ($ensureAuthenticated): void {
        $user = $ensureAuthenticated();
        $userId = (int) $user['id'];

        $filters = [
            'doc_type' => trim((string) ($_GET['doc_type'] ?? '')),
            'search'   => trim((string) ($_GET['search'] ?? '')),
        ];

        $documents = PortalService::getUserDocuments($userId, $filters);
        require __DIR__ . '/views/portal/my_documents.php';
    });

    // =========================================================================
    // PORTAL (EIGENTÜMER / MIETER): MEINE VORGÄNGE
    // =========================================================================
    $router->get('/portal/cases', static function () use ($ensureAuthenticated): void {
        $user = $ensureAuthenticated();
        $userId = (int) $user['id'];

        $cases = PortalService::getUserCases($userId);
        require __DIR__ . '/views/portal/my_cases.php';
    });

    // =========================================================================
    // PORTAL (EIGENTÜMER / MIETER): VORGANGS-DETAILANSICHT
    // =========================================================================
    $router->get('/portal/case', static function () use ($ensureAuthenticated): void {
        $user = $ensureAuthenticated();
        $userId = (int) $user['id'];
        $caseId = (int) ($_GET['id'] ?? 0);

        if (!PortalService::canAccessCase($caseId, $userId)) {
            $_SESSION['flash_error'] = 'Zugriff auf diesen Vorgang verweigert.';
            header('Location: ?route=portal/dashboard');
            exit;
        }

        $case = PortalRepository::findCase($caseId);
        if ($case === null) {
            $_SESSION['flash_error'] = 'Vorgang nicht gefunden.';
            header('Location: ?route=portal/dashboard');
            exit;
        }

        // Für normale Portalnutzer nur nicht-interne Nachrichten laden!
        $messages = PortalRepository::getCaseMessages($caseId, false);
        $documents = PortalService::getDocumentsForEntity('case', $caseId);

        require __DIR__ . '/views/portal/case_view.php';
    });

    // =========================================================================
    // PORTAL (STARTSEITE & LOGIN): ÖFFENTLICHER ZUGANG FÜR EIGENTÜMER & MIETER
    // =========================================================================
    $portalLandingHandler = static function (): void {
        // Falls bereits eingeloggt: Direkt zum Portal-Dashboard weiterleiten
        if (PortalService::getCurrentUser() !== null) {
            header('Location: ?route=portal/dashboard');
            exit;
        }

        require __DIR__ . '/views/portal/login.php';
    };

    $router->get('/portal', $portalLandingHandler);
    $router->get('/portal/login', $portalLandingHandler);

    // Passwort-Login über das Portal
    $router->post('/portal/login', static function (): void {
        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_portal_error'] = 'Ungültiges Sicherheitstoken (CSRF). Bitte erneut versuchen.';
            header('Location: ?route=portal');
            exit;
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            $_SESSION['flash_portal_error'] = 'Bitte E-Mail-Adresse und Passwort eingeben.';
            header('Location: ?route=portal');
            exit;
        }

        if (Auth::login($email, $password)) {
            header('Location: ?route=portal/dashboard');
            exit;
        }

        $_SESSION['flash_portal_error'] = 'Login fehlgeschlagen. Bitte Zugangsdaten prüfen.';
        header('Location: ?route=portal');
        exit;
    });

    // Magic-Code E-Mail setzen (Portal)
    $router->post('/portal/magic-set-email', static function (): void {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));

        if ($email === '' || strlen($email) > 191 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_portal_error'] = 'Bitte eine gültige E-Mail-Adresse eingeben.';
            header('Location: ?route=portal');
            exit;
        }

        $_SESSION['magic_email'] = $email;
        $_SESSION['magic_input_email'] = $email;

        // Prüfen, ob User existiert
        $user = null;
        try {
            $user = DB::fetchOne('SELECT * FROM users WHERE email = :email LIMIT 1', ['email' => $email]);
        } catch (\Throwable $e) {
            $user = null;
        }

        $isDevEmail = MagicCode::isDevMagicCodeEnabled() && ($email === strtolower(MagicCode::getDevMagicCodeEmail()));

        if ($user === null && !$isDevEmail) {
            $_SESSION['flash_portal_error'] = 'Diese E-Mail-Adresse ist nicht im System registriert.';
            header('Location: ?route=portal');
            exit;
        }

        if ($user !== null && isset($user['is_active']) && (int) $user['is_active'] !== 1) {
            $_SESSION['flash_portal_error'] = 'Dieser Account ist deaktiviert oder gesperrt.';
            header('Location: ?route=portal');
            exit;
        }

        // Code generieren und versenden
        try {
            $created = MagicCode::createCodeForEmail($email, 'portal_login', 1, null);
            MagicCode::sendEmailNotification($created['code'], $email, 'portal_login', 1, 'office@studiocreativo.ch');
            $_SESSION['flash_portal_success'] = 'Ein 10-stelliger Magic Code wurde an Ihre E-Mail gesendet.';
        } catch (\Throwable $e) {
            if ($isDevEmail) {
                $_SESSION['flash_portal_info'] = 'Dev-Modus aktiv: Nutzen Sie den Code ROLAND1234.';
            } else {
                $_SESSION['flash_portal_error'] = 'Fehler beim Erstellen des Magic-Codes: ' . $e->getMessage();
            }
        }

        header('Location: ?route=portal');
        exit;
    });

    // Magic-Code E-Mail zurücksetzen (Portal)
    $router->post('/portal/magic-clear-email', static function (): void {
        unset($_SESSION['magic_email'], $_SESSION['magic_input_email']);
        header('Location: ?route=portal');
        exit;
    });

    // Magic-Code erneut anfordern (Portal)
    $router->post('/portal/magic-request', static function (): void {
        $email = strtolower(trim((string) ($_SESSION['magic_email'] ?? ($_SESSION['magic_input_email'] ?? ''))));
        if ($email === '') {
            $_SESSION['flash_portal_error'] = 'Bitte zuerst Ihre E-Mail-Adresse eingeben.';
            header('Location: ?route=portal');
            exit;
        }

        try {
            $created = MagicCode::createCodeForEmail($email, 'portal_login', 1, null);
            MagicCode::sendEmailNotification($created['code'], $email, 'portal_login', 1, 'office@studiocreativo.ch');
            $_SESSION['flash_portal_success'] = 'Ein neuer 10-stelliger Code wurde an Ihre E-Mail gesendet.';
        } catch (\Throwable $e) {
            $_SESSION['flash_portal_error'] = 'Fehler beim Senden: ' . $e->getMessage();
        }

        header('Location: ?route=portal');
        exit;
    });

    // Magic-Code Login prüfen (Portal)
    $router->post('/portal/magic-login', static function (): void {
        $email = strtolower(trim((string) ($_SESSION['magic_email'] ?? ($_SESSION['magic_input_email'] ?? ''))));
        $code = trim((string) ($_POST['magic_code'] ?? ''));

        if ($email === '') {
            $_SESSION['flash_portal_error'] = 'Bitte zuerst Ihre E-Mail-Adresse angeben.';
            header('Location: ?route=portal');
            exit;
        }

        if ($code === '') {
            $_SESSION['flash_portal_error'] = 'Bitte den Magic-Code eingeben.';
            header('Location: ?route=portal');
            exit;
        }

        if (Auth::loginWithMagicCodeForEmail($email, $code)) {
            header('Location: ?route=portal/dashboard');
            exit;
        }

        $_SESSION['flash_portal_error'] = 'Ungültiger oder abgelaufener Magic-Code.';
        header('Location: ?route=portal');
        exit;
    });

    // =========================================================================
    // PORTAL (EIGENTÜMER / MIETER): NACHRICHT AN VERWALTUNG SENDEN
    // =========================================================================
    $router->post('/portal/case/message', static function () use ($ensureAuthenticated): void {
        $user = $ensureAuthenticated();
        $userId = (int) $user['id'];

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=portal/dashboard');
            exit;
        }

        $caseId = (int) ($_POST['case_id'] ?? 0);
        if (!PortalService::canAccessCase($caseId, $userId)) {
            $_SESSION['flash_error'] = 'Zugriff verweigert.';
            header('Location: ?route=portal/dashboard');
            exit;
        }

        $message = trim((string) ($_POST['message'] ?? ''));

        try {
            PortalService::addCaseMessage($caseId, $message, false);
            $_SESSION['flash_success'] = 'Ihre Nachricht wurde übermittelt.';
            header('Location: ?route=portal/case&id=' . $caseId);
            exit;
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Fehler beim Absenden: ' . $e->getMessage();
            header('Location: ?route=portal/case&id=' . $caseId);
            exit;
        }
    });
}
