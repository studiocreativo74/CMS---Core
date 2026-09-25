<?php

declare(strict_types=1);

/**
 * Bootstrap für das Modul "Sicherungsportal"
 *
 * Registriert Menüpunkte im CMS-Admin sowie alle öffentlichen Routen
 * für die Startseite, das mehrteilige Antragsformular, den Fallzugang
 * und das Admin-Backend.
 *
 * @var Router|null $router
 * @var array<string, mixed> $module
 * @var string $moduleDir
 */

require_once __DIR__ . '/classes/SecurePortalRepository.php';
require_once __DIR__ . '/classes/SecurePortalService.php';

// Menüpunkt im Admin-Bereich registrieren
if (class_exists('ModuleManager')) {
    ModuleManager::addAdminMenuItem([
        'label' => 'Sicherungsportal',
        'route' => 'admin/secure/cases',
        'url'   => '?route=admin/secure/cases',
        'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-shield-shaded me-2" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 0c-.69 0-1.843.265-2.928.56-1.11.3-2.229.655-2.887.87a1.54 1.54 0 0 0-1.044 1.262c-.596 4.477.787 7.795 2.465 9.99a11.8 11.8 0 0 0 2.517 2.453c.386.273.744.482 1.048.625.28.132.581.24.829.24s.548-.108.829-.24a7 7 0 0 0 1.048-.625 11.8 11.8 0 0 0 2.517-2.453c1.678-2.195 3.061-5.513 2.465-9.99a1.54 1.54 0 0 0-1.044-1.263 63 63 0 0 0-2.887-.87C9.843.266 8.69 0 8 0m0 1.072c.557.25 1.139.49 1.708.705 1.128.426 2.193.767 3.034.972.614 3.73-.39 6.577-1.88 8.528A10.87 10.87 0 0 1 8 13.782z"/></svg>',
    ]);
}

if (isset($router) && $router !== null) {

    // =========================================================================
    // 1. ÖFFENTLICHE STARTSEITE: GET /sicherung
    // =========================================================================
    $router->get('/sicherung', static function (): void {
        require __DIR__ . '/views/public/index.php';
    });

    // =========================================================================
    // 2. MEHRTEILIGES ANTRAGSFORMULAR (POLIZEI / STAATSANWALTSCHAFT)
    // =========================================================================
    $router->get('/sicherung/antrag', static function (): void {
        $stepParam = (int) ($_GET['step'] ?? 1);
        $currentStep = in_array($stepParam, [1, 2, 3], true) ? $stepParam : 1;

        $formData = $_SESSION['secure_antrag_draft'] ?? [];
        $errors = $_SESSION['secure_antrag_errors'] ?? [];
        unset($_SESSION['secure_antrag_errors']);

        $createdCase = null;
        if (!empty($_SESSION['secure_antrag_success_case'])) {
            $createdCase = $_SESSION['secure_antrag_success_case'];
            unset($_SESSION['secure_antrag_success_case']);
            $currentStep = 4;
        }

        require __DIR__ . '/views/public/antrag.php';
    });

    $router->post('/sicherung/antrag', static function (): void {
        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_secure_error'] = 'Sicherheitsprüfung fehlgeschlagen (CSRF-Token ungültig). Bitte versuchen Sie es erneut.';
            header('Location: ?route=sicherung/antrag');
            exit;
        }

        $step = (int) ($_POST['step'] ?? 1);
        $draft = $_SESSION['secure_antrag_draft'] ?? [];

        if ($step === 1) {
            // Schritt 1 verarbeiten: Basisdaten
            $step1Data = [
                'police_department' => trim((string) ($_POST['police_department'] ?? '')),
                'contact_name'      => trim((string) ($_POST['contact_name'] ?? '')),
                'contact_email'     => trim((string) ($_POST['contact_email'] ?? '')),
                'contact_phone'     => trim((string) ($_POST['contact_phone'] ?? '')),
                'reference_number'  => trim((string) ($_POST['reference_number'] ?? '')),
                'description'       => trim((string) ($_POST['description'] ?? '')),
                'desired_date'      => trim((string) ($_POST['desired_date'] ?? '')),
                'remarks'           => trim((string) ($_POST['remarks'] ?? '')),
            ];

            $errors = SecurePortalService::validateStep1($step1Data);
            if (!empty($errors)) {
                $_SESSION['secure_antrag_draft'] = array_merge($draft, $step1Data);
                $_SESSION['secure_antrag_errors'] = $errors;
                header('Location: ?route=sicherung/antrag&step=1');
                exit;
            }

            $_SESSION['secure_antrag_draft'] = array_merge($draft, $step1Data);
            header('Location: ?route=sicherung/antrag&step=2');
            exit;
        }

        if ($step === 2) {
            // Schritt 2 verarbeiten: PDF-Upload Editionsverfügung
            if (empty($_FILES['warrant_file']) || ($_FILES['warrant_file']['error'] === UPLOAD_ERR_NO_FILE)) {
                // Prüfen, ob bereits eine Datei im Draft vorhanden ist
                if (empty($draft['warrant_file_path'])) {
                    $_SESSION['secure_antrag_errors'] = ['warrant_file' => 'Bitte wählen Sie eine Editionsverfügung als PDF-Datei aus.'];
                    header('Location: ?route=sicherung/antrag&step=2');
                    exit;
                }
                // Bereits hochgeladene Datei bleibt erhalten
                header('Location: ?route=sicherung/antrag&step=3');
                exit;
            }

            try {
                $uploadRes = SecurePortalService::processWarrantUpload($_FILES['warrant_file']);
                $draft['warrant_file_path']     = $uploadRes['file_path'];
                $draft['warrant_mime']          = $uploadRes['mime'];
                $draft['warrant_file_size']     = $uploadRes['file_size'];
                $draft['warrant_original_name'] = $uploadRes['original_filename'];
                $_SESSION['secure_antrag_draft'] = $draft;

                header('Location: ?route=sicherung/antrag&step=3');
                exit;
            } catch (\Throwable $e) {
                $_SESSION['secure_antrag_errors'] = ['warrant_file' => 'Fehler beim Upload: ' . $e->getMessage()];
                header('Location: ?route=sicherung/antrag&step=2');
                exit;
            }
        }

        if ($step === 3) {
            // Schritt 3 verarbeiten: Typ & Spezifikation
            $securingType = trim((string) ($_POST['securing_type'] ?? 'VIDEO'));
            $securingMeta = (array) ($_POST['securing_meta'] ?? []);

            $errors = SecurePortalService::validateStep3($securingType, $securingMeta);
            if (!empty($errors)) {
                $draft['securing_type'] = $securingType;
                $draft['securing_meta'] = $securingMeta;
                $_SESSION['secure_antrag_draft'] = $draft;
                $_SESSION['secure_antrag_errors'] = $errors;
                header('Location: ?route=sicherung/antrag&step=3');
                exit;
            }

            // Vollständige Daten zusammenführen und Vorgang in DB erstellen
            $caseData = array_merge($draft, [
                'securing_type' => $securingType,
                'securing_meta' => $securingMeta,
            ]);

            try {
                $created = SecurePortalRepository::createCase($caseData);

                // Draft leeren
                unset($_SESSION['secure_antrag_draft']);

                // Isolierte Fall-Session für sofortigen Lesezugriff setzen
                $_SESSION['secure_case_auth'] = [
                    'case_id'     => $created['id'],
                    'case_number' => $created['case_number'],
                    'auth_time'   => time(),
                ];

                $_SESSION['secure_antrag_success_case'] = $created;
                header('Location: ?route=sicherung/antrag&step=success');
                exit;
            } catch (\Throwable $e) {
                $_SESSION['secure_antrag_errors'] = ['general' => 'Fehler beim Speichern des Antrags: ' . $e->getMessage()];
                header('Location: ?route=sicherung/antrag&step=3');
                exit;
            }
        }

        header('Location: ?route=sicherung/antrag');
        exit;
    });

    // =========================================================================
    // 3. FALLZUGANG (ANTRAGSTELLER-LOGIN & LOGOUT)
    // =========================================================================
    $router->get('/sicherung/fallzugang', static function (): void {
        // Falls bereits angemeldet, direkt zum Fall weiterleiten
        $authenticatedCase = SecurePortalService::getAuthenticatedCase();
        if ($authenticatedCase !== null) {
            header('Location: ?route=sicherung/fall');
            exit;
        }

        $error = $_SESSION['flash_secure_error'] ?? null;
        unset($_SESSION['flash_secure_error']);
        $caseNumberInput = (string) ($_GET['id'] ?? '');

        require __DIR__ . '/views/public/fallzugang.php';
    });

    $router->post('/sicherung/fallzugang', static function (): void {
        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_secure_error'] = 'Sicherheitsprüfung fehlgeschlagen (CSRF).';
            header('Location: ?route=sicherung/fallzugang');
            exit;
        }

        $caseNumber = (string) ($_POST['case_number'] ?? '');
        $accessCode = (string) ($_POST['access_code'] ?? '');

        $res = SecurePortalService::loginCase($caseNumber, $accessCode);

        if (!$res['success']) {
            $_SESSION['flash_secure_error'] = $res['error'] ?? 'Zugangsdaten ungültig.';
            header('Location: ?route=sicherung/fallzugang&id=' . urlencode($caseNumber));
            exit;
        }

        header('Location: ?route=sicherung/fall');
        exit;
    });

    // GET /sicherung/fall - Geschützte Detailansicht des eigenen Vorgangs
    $router->get('/sicherung/fall', static function (): void {
        $case = SecurePortalService::getAuthenticatedCase();
        if ($case === null) {
            $_SESSION['flash_secure_error'] = 'Bitte geben Sie Ihre Vorgangs-ID und den Zugangscode ein, um Ihren Fall aufzurufen.';
            header('Location: ?route=sicherung/fallzugang');
            exit;
        }

        // Protokolleinträge (nur öffentlich sichtbare für den Antragsteller)
        $caseLogs = SecurePortalRepository::getCaseLogs((int) $case['id'], false);

        require __DIR__ . '/views/public/fall.php';
    });

    // GET /sicherung/abmelden - Beendet die Fall-Session
    $router->get('/sicherung/abmelden', static function (): void {
        SecurePortalService::logoutCase();
        $_SESSION['flash_secure_info'] = 'Sie haben den Fallzugang erfolgreich beendet.';
        header('Location: ?route=sicherung');
        exit;
    });

    // =========================================================================
    // 4. BACKEND / ADMIN-ROUTEN (TECHNIKER, VERWALTUNG, ADMIN)
    // =========================================================================

    // GET /admin/secure/cases - Liste aller Sicherungsvorgänge
    $router->get('/admin/secure/cases', static function (): void {
        if (!SecurePortalService::canViewCases()) {
            $_SESSION['flash_error'] = 'Zugriff verweigert: Sie haben keine Berechtigung für das Sicherungsportal.';
            header('Location: ?route=admin');
            exit;
        }

        $filters = [
            'search'        => trim((string) ($_GET['search'] ?? '')),
            'status'        => trim((string) ($_GET['status'] ?? '')),
            'securing_type' => trim((string) ($_GET['securing_type'] ?? '')),
        ];

        $cases = SecurePortalRepository::getCases($filters);
        $totalCases = SecurePortalRepository::countCases($filters);
        $stats = SecurePortalRepository::getStats();

        require __DIR__ . '/views/admin/cases_index.php';
    });

    // GET /admin/secure/cases/view - Detailansicht eines Vorgangs
    $router->get('/admin/secure/cases/view', static function (): void {
        if (!SecurePortalService::canViewCases()) {
            $_SESSION['flash_error'] = 'Zugriff verweigert.';
            header('Location: ?route=admin');
            exit;
        }

        $id = (int) ($_GET['id'] ?? 0);
        $case = SecurePortalRepository::getCaseById($id);

        if (!$case) {
            $_SESSION['flash_error'] = 'Der angeforderte Sicherungsvorgang wurde nicht gefunden.';
            header('Location: ?route=admin/secure/cases');
            exit;
        }

        // Für Admins/Techniker: alle Logs inklusive interner Vermerke
        $caseLogs = SecurePortalRepository::getCaseLogs($id, true);
        $canManage = SecurePortalService::canManageCases();

        require __DIR__ . '/views/admin/case_view.php';
    });

    // POST /admin/secure/cases/update - Status ändern, Bearbeiter zuweisen, Notiz verfassen
    $router->post('/admin/secure/cases/update', static function (): void {
        if (!SecurePortalService::canManageCases()) {
            $_SESSION['flash_error'] = 'Zugriff verweigert: Keine Schreibberechtigung für Sicherungsvorgänge.';
            header('Location: ?route=admin/secure/cases');
            exit;
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/secure/cases');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $case = SecurePortalRepository::getCaseById($id);

        if (!$case) {
            $_SESSION['flash_error'] = 'Vorgang nicht gefunden.';
            header('Location: ?route=admin/secure/cases');
            exit;
        }

        $status = trim((string) ($_POST['status'] ?? $case['status']));
        $internalNotes = trim((string) ($_POST['internal_notes'] ?? ''));
        $logMessage = trim((string) ($_POST['log_message'] ?? ''));
        $isPublic = !empty($_POST['log_is_public']);

        $user = SecurePortalService::getCurrentAdminUser();
        $author = (string) ($user['username'] ?? ($user['email'] ?? 'Techniker/Admin'));

        $success = SecurePortalRepository::updateCase(
            $id,
            $status,
            $internalNotes,
            null,
            $logMessage !== '' ? $logMessage : null,
            $author,
            !$isPublic
        );

        if ($success) {
            $_SESSION['flash_success'] = 'Vorgang #' . $case['case_number'] . ' wurde erfolgreich aktualisiert.';
        } else {
            $_SESSION['flash_error'] = 'Fehler beim Aktualisieren des Vorgangs.';
        }

        header('Location: ?route=admin/secure/cases/view&id=' . $id);
        exit;
    });

    // GET /admin/secure/cases/download-warrant - Geschützter Download der Editionsverfügung
    $router->get('/admin/secure/cases/download-warrant', static function (): void {
        if (!SecurePortalService::canViewCases()) {
            http_response_code(403);
            die('Zugriff verweigert.');
        }

        $id = (int) ($_GET['id'] ?? 0);
        $case = SecurePortalRepository::getCaseById($id);

        if (!$case || empty($case['warrant_file_path'])) {
            http_response_code(404);
            die('Editionsverfügung nicht gefunden.');
        }

        $relPath = ltrim((string) $case['warrant_file_path'], '/');
        $baseDir = dirname(__DIR__, 2);
        $fullPath = $baseDir . '/' . $relPath;

        if (!file_exists($fullPath) || !is_file($fullPath)) {
            http_response_code(404);
            die('Datei existiert nicht im Dateisystem.');
        }

        $filename = 'Editionsverfuegung_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', (string) $case['case_number']) . '.pdf';

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Length: ' . (string) filesize($fullPath));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        readfile($fullPath);
        exit;
    });
}
