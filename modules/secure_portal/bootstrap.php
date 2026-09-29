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
require_once __DIR__ . '/classes/SecurePortalConfig.php';
require_once __DIR__ . '/classes/Naming.php';
require_once __DIR__ . '/classes/SharePointConfig.php';
require_once __DIR__ . '/classes/SharePointService.php';

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
                'city'              => trim((string) ($_POST['city'] ?? ($_POST['ort'] ?? ''))),
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

                // Editionsverfügung im dedizierten Fallordner ablegen:
                // uploads/secure/cases/{case_number}/{case_number}_Editionsverfuegung_v1.pdf
                $caseNumber = (string) $created['case_number'];
                $finalWarrantPath = SecurePortalService::finalizeCaseWarrant(
                    (int) $created['id'],
                    $caseNumber,
                    (string) ($created['warrant_file_path'] ?? ($caseData['warrant_file_path'] ?? '')),
                    1,
                    $created
                );
                $created['warrant_file_path'] = $finalWarrantPath;
                $caseData['warrant_file_path'] = $finalWarrantPath;

                // Phase 1: Automatische SharePoint-Archivierung (sofern konfiguriert)
                // Fehler werden intern geloggt und unterbrechen niemals den erfolgreichen Vorgang
                if (class_exists('SharePointService') && class_exists('SharePointConfig') && SharePointConfig::isConfigured()) {
                    try {
                        SharePointService::archiveCaseWarrant($created, $caseData);
                    } catch (\Throwable $spEx) {
                        error_log('SharePoint Archivierungs-Ausnahme nach Case-Anlage: ' . $spEx->getMessage());
                    }
                }

                // Benachrichtigungs-Mail an Sammeladresse auslösen (sofern konfiguriert)
                // Keine Editionsverfügung oder Sicherungsdaten im Anhang, nur Metadaten
                try {
                    SecurePortalService::sendNewCaseNotification($created);
                } catch (\Throwable $mailEx) {
                    error_log('SecurePortalService::sendNewCaseNotification Ausnahme: ' . $mailEx->getMessage());
                }

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
    // 3b. EXTERNER DATENRAUM (POLIZEI-DOWNLOAD VIA TOKEN)
    // =========================================================================

    // GET /sicherung/download - Externer Datenraum für die Polizei (Token-geschützt)
    $router->get('/sicherung/download', static function (): void {
        $token = trim((string) ($_GET['token'] ?? ''));
        require __DIR__ . '/views/public/download_room.php';
    });

    // GET /sicherung/download/file - Geschützter Download einer Sicherungsdatei über Token
    $router->get('/sicherung/download/file', static function (): void {
        $token = trim((string) ($_GET['token'] ?? ''));
        $fileId = (int) ($_GET['file_id'] ?? 0);

        if ($token === '' || strlen($token) !== 40 || $fileId <= 0) {
            http_response_code(400);
            die('Ungültige Download-Anforderung.');
        }

        $case = SecurePortalRepository::findCaseByDownloadToken($token);
        if (!$case) {
            http_response_code(403);
            die('Ungültiger oder nicht mehr existierender Download-Token.');
        }

        $validity = SecurePortalRepository::checkDownloadValidity($case);
        if (!$validity['valid']) {
            http_response_code(403);
            die('Download nicht zulässig: ' . htmlspecialchars((string) ($validity['reason'] ?? 'Ungültig'), ENT_QUOTES, 'UTF-8'));
        }

        $file = SecurePortalRepository::getCaseFileById($fileId, (int) $case['id']);
        if (!$file || empty($file['is_active'])) {
            http_response_code(404);
            die('Die angeforderte Sicherungsdatei wurde nicht gefunden oder ist nicht mehr verfügbar.');
        }

        $relPath = (string) $file['file_path'];
        $fullPath = class_exists('SecurePortalConfig')
            ? SecurePortalConfig::resolveLocalFilePath($relPath)
            : (dirname(__DIR__, 2) . '/' . ltrim($relPath, '/'));

        if ($fullPath === null || !file_exists($fullPath) || !is_file($fullPath)) {
            http_response_code(404);
            die('Die Sicherungsdatei existiert nicht auf dem Server.');
        }

        // Audit-Logging (sowohl secure_download_logs als auch Aktivitäten-Protokoll)
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unbekannt');
        $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? 'unbekannt');
        SecurePortalRepository::recordFileDownload(
            (int) $case['id'],
            $fileId,
            $token,
            (string) $file['file_name'],
            $ip,
            $ua
        );

        $fileName = (string) $file['file_name'];
        $mime = !empty($file['mime_type']) ? (string) $file['mime_type'] : 'application/octet-stream';
        $fileSize = (int) filesize($fullPath);

        // Download-Header senden
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . addslashes($fileName) . '"');
        header('Content-Length: ' . (string) $fileSize);
        header('Cache-Control: private, no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        if (ob_get_level() > 0) {
            @ob_end_clean();
        }

        readfile($fullPath);
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

        $relPath = (string) $case['warrant_file_path'];
        $fullPath = class_exists('SecurePortalConfig')
            ? SecurePortalConfig::resolveLocalFilePath($relPath)
            : (dirname(__DIR__, 2) . '/' . ltrim($relPath, '/'));

        if ($fullPath === null || !file_exists($fullPath) || !is_file($fullPath)) {
            http_response_code(404);
            die('Datei existiert nicht im Dateisystem.');
        }

        $filename = class_exists('Naming')
            ? Naming::buildWarrantFileName((string) $case['case_number'], $case)
            : (class_exists('SecurePortalConfig')
                ? SecurePortalConfig::buildWarrantFileName((string) $case['case_number'], 1, $case)
                : ('Editionsverfuegung_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', (string) $case['case_number']) . '.pdf'));

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Length: ' . (string) filesize($fullPath));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        readfile($fullPath);
        exit;
    });

    // POST /admin/secure/cases/sync-sharepoint - Manuelle SharePoint-Synchronisation für Admins
    $router->post('/admin/secure/cases/sync-sharepoint', static function (): void {
        if (!SecurePortalService::canManageCases()) {
            $_SESSION['flash_error'] = 'Zugriff verweigert.';
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
            $_SESSION['flash_error'] = 'Sicherungsvorgang nicht gefunden.';
            header('Location: ?route=admin/secure/cases');
            exit;
        }

        // Sicherstellen, dass die Datei im standardisierten Fallordner liegt
        $organizedPath = SecurePortalService::finalizeCaseWarrant(
            (int) $case['id'],
            (string) $case['case_number'],
            (string) $case['warrant_file_path'],
            1,
            $case
        );
        $case['warrant_file_path'] = $organizedPath;

        if (!class_exists('SharePointConfig') || !SharePointConfig::isConfigured()) {
            $_SESSION['flash_error'] = 'SharePoint ist noch nicht vollständig konfiguriert (Tenant-ID, Client-ID, Secret und Drive-/Site-ID prüfen).';
            header('Location: ?route=admin/secure/cases/view&id=' . $id);
            exit;
        }

        $result = SharePointService::archiveCaseWarrant($case, $case);

        if ($result !== null && !empty($result['success'])) {
            $_SESSION['flash_success'] = 'Vorgang #' . $case['case_number'] . ' wurde erfolgreich in SharePoint archiviert.';
        } else {
            $errMsg = $result['error'] ?? 'Unbekannter Fehler bei der Übertragung.';
            $_SESSION['flash_error'] = 'SharePoint-Archivierung fehlgeschlagen: ' . $errMsg;
        }

        header('Location: ?route=admin/secure/cases/view&id=' . $id);
        exit;
    });

    // POST /admin/secure/cases/upload-data - Interner Upload von Sicherungsdaten (ZIP/TAR/Archive)
    $router->post('/admin/secure/cases/upload-data', static function (): void {
        if (!SecurePortalService::canManageCases()) {
            $_SESSION['flash_error'] = 'Zugriff verweigert.';
            header('Location: ?route=admin/secure/cases');
            exit;
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            $id = (int) ($_POST['id'] ?? 0);
            header('Location: ?route=admin/secure/cases/view&id=' . $id);
            exit;
        }

        $caseId = (int) ($_POST['id'] ?? 0);
        $setAvailable = !empty($_POST['set_available']);

        if (empty($_FILES['securing_file']) || ($_FILES['securing_file']['error'] === UPLOAD_ERR_NO_FILE)) {
            $_SESSION['flash_error'] = 'Bitte wählen Sie eine Sicherungsdatei zum Hochladen aus.';
            header('Location: ?route=admin/secure/cases/view&id=' . $caseId);
            exit;
        }

        $user = SecurePortalService::getCurrentAdminUser();
        $author = (string) ($user['username'] ?? ($user['email'] ?? 'Techniker/Admin'));
        $userId = !empty($user['id']) ? (int) $user['id'] : null;

        $uploadResult = SecurePortalService::processSecuringDataUpload(
            $caseId,
            $_FILES['securing_file'],
            $setAvailable,
            $author,
            $userId
        );

        if (!empty($uploadResult['success'])) {
            $msg = sprintf(
                'Sicherungsdatei „%s“ (%s MB) erfolgreich hochgeladen und SHA-256 Prüfsumme dokumentiert.',
                $uploadResult['file_name'],
                number_format(((int) $uploadResult['file_size']) / 1024 / 1024, 2)
            );
            if ($setAvailable) {
                $msg .= ' Der Fall wurde gleichzeitig auf „Bereitgestellt“ gesetzt.';
            }
            $_SESSION['flash_success'] = $msg;
        } else {
            $_SESSION['flash_error'] = 'Fehler beim Upload der Sicherungsdaten: ' . ($uploadResult['error'] ?? 'Unbekannter Fehler.');
        }

        header('Location: ?route=admin/secure/cases/view&id=' . $caseId);
        exit;
    });

    // POST /admin/secure/cases/set-available - Schnelle Bereitstellung der Sicherungsdaten (Status: Bereitgestellt)
    $router->post('/admin/secure/cases/set-available', static function (): void {
        if (!SecurePortalService::canManageCases()) {
            $_SESSION['flash_error'] = 'Zugriff verweigert.';
            header('Location: ?route=admin/secure/cases');
            exit;
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            $id = (int) ($_POST['id'] ?? 0);
            header('Location: ?route=admin/secure/cases/view&id=' . $id);
            exit;
        }

        $caseId = (int) ($_POST['id'] ?? 0);
        $user = SecurePortalService::getCurrentAdminUser();
        $author = (string) ($user['username'] ?? ($user['email'] ?? 'Techniker/Admin'));
        $customMessage = trim((string) ($_POST['message'] ?? ''));

        $success = SecurePortalRepository::setCaseAvailable($caseId, $author, $customMessage !== '' ? $customMessage : null);

        if ($success) {
            $_SESSION['flash_success'] = 'Vorgang wurde erfolgreich auf „Bereitgestellt“ gesetzt. Der Antragsteller sieht den Status ab sofort im Fallzugang.';
        } else {
            $_SESSION['flash_error'] = 'Fehler beim Setzen des Fallstatus.';
        }

        header('Location: ?route=admin/secure/cases/view&id=' . $caseId);
        exit;
    });

    // POST /admin/secure/cases/verify-file-hash - SHA-256 Prüfsumme auf dem Datenträger re-verifizieren
    $router->post('/admin/secure/cases/verify-file-hash', static function (): void {
        if (!SecurePortalService::canManageCases()) {
            $_SESSION['flash_error'] = 'Zugriff verweigert.';
            header('Location: ?route=admin/secure/cases');
            exit;
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            $id = (int) ($_POST['case_id'] ?? 0);
            header('Location: ?route=admin/secure/cases/view&id=' . $id);
            exit;
        }

        $caseId = (int) ($_POST['case_id'] ?? 0);
        $fileId = (int) ($_POST['file_id'] ?? 0);

        $user = SecurePortalService::getCurrentAdminUser();
        $author = (string) ($user['username'] ?? ($user['email'] ?? 'Techniker/Admin'));

        $res = SecurePortalService::verifyFileHash($caseId, $fileId, $author);

        if (!empty($res['success'])) {
            if ($res['match']) {
                $_SESSION['flash_success'] = sprintf(
                    'SHA-256 Integritätsprüfung für „%s“ erfolgreich bestätigt: Prüfsumme (%s) stimmt exakt überein.',
                    $res['file_name'],
                    $res['actual_sha256']
                );
            } else {
                $_SESSION['flash_error'] = sprintf(
                    'WARNUNG: SHA-256 Abweichung bei „%s“ festgestellt! Gespeicherter Hash: %s vs. Tatsächlicher Hash auf Datenträger: %s',
                    $res['file_name'],
                    $res['stored_sha256'],
                    $res['actual_sha256']
                );
            }
        } else {
            $_SESSION['flash_error'] = 'Fehler bei der Integritätsprüfung: ' . ($res['error'] ?? 'Unbekannter Fehler.');
        }

        header('Location: ?route=admin/secure/cases/view&id=' . $caseId);
        exit;
    });

    // GET /admin/secure/cases/download-file - Geschützter Download einer Sicherungsdatei (Admin)
    $router->get('/admin/secure/cases/download-file', static function (): void {
        if (!SecurePortalService::canViewCases()) {
            http_response_code(403);
            die('Zugriff verweigert.');
        }

        $caseId = (int) ($_GET['case_id'] ?? 0);
        $fileId = (int) ($_GET['file_id'] ?? 0);

        $file = SecurePortalRepository::getCaseFileById($fileId, $caseId);
        if (!$file) {
            http_response_code(404);
            die('Sicherungsdatei nicht gefunden.');
        }

        $relPath = (string) $file['file_path'];
        $fullPath = SecurePortalConfig::resolveLocalFilePath($relPath);

        if ($fullPath === null || !file_exists($fullPath) || !is_file($fullPath)) {
            http_response_code(404);
            die('Datei existiert nicht auf dem Server.');
        }

        $fileName = (string) $file['file_name'];
        $mime = !empty($file['mime_type']) ? (string) $file['mime_type'] : 'application/octet-stream';

        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . addslashes($fileName) . '"');
        header('Content-Length: ' . (string) filesize($fullPath));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        readfile($fullPath);
        exit;
    });

    // POST /admin/secure/cases/delete-file - Sicherungsdatei entfernen
    $router->post('/admin/secure/cases/delete-file', static function (): void {
        if (!SecurePortalService::canManageCases()) {
            $_SESSION['flash_error'] = 'Zugriff verweigert.';
            header('Location: ?route=admin/secure/cases');
            exit;
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            $id = (int) ($_POST['case_id'] ?? 0);
            header('Location: ?route=admin/secure/cases/view&id=' . $id);
            exit;
        }

        $caseId = (int) ($_POST['case_id'] ?? 0);
        $fileId = (int) ($_POST['file_id'] ?? 0);

        $user = SecurePortalService::getCurrentAdminUser();
        $author = (string) ($user['username'] ?? ($user['email'] ?? 'Techniker/Admin'));

        $deleted = SecurePortalService::deleteSecuringFile($caseId, $fileId, $author);

        if ($deleted) {
            $_SESSION['flash_success'] = 'Sicherungsdatei wurde erfolgreich entfernt.';
        } else {
            $_SESSION['flash_error'] = 'Fehler beim Entfernen der Sicherungsdatei.';
        }

        header('Location: ?route=admin/secure/cases/view&id=' . $caseId);
        exit;
    });

    // POST /admin/secure/cases/download-token/update - Externen Datenraum aktivieren/deaktivieren & Gültigkeit anpassen
    $router->post('/admin/secure/cases/download-token/update', static function (): void {
        if (!SecurePortalService::canManageCases()) {
            $_SESSION['flash_error'] = 'Zugriff verweigert.';
            header('Location: ?route=admin/secure/cases');
            exit;
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            $id = (int) ($_POST['id'] ?? 0);
            header('Location: ?route=admin/secure/cases/view&id=' . $id);
            exit;
        }

        $caseId = (int) ($_POST['id'] ?? 0);
        $enabled = !empty($_POST['download_enabled']);
        $expiresAt = trim((string) ($_POST['download_expires_at'] ?? ''));
        if ($expiresAt === '') {
            $expiresAt = null;
        } else {
            // Normalisiere ggf. datetime-local (z. B. "2026-09-27T18:00" -> "2026-09-27 18:00:00")
            $expiresAt = str_replace('T', ' ', $expiresAt);
            if (strlen($expiresAt) === 16) {
                $expiresAt .= ':00';
            }
        }

        $user = SecurePortalService::getCurrentAdminUser();
        $author = (string) ($user['username'] ?? ($user['email'] ?? 'Admin'));

        $updated = SecurePortalRepository::updateDownloadAccess($caseId, $enabled, $expiresAt, $author);

        if ($updated) {
            $_SESSION['flash_success'] = 'Freigabeeinstellungen für den externen Datenraum wurden erfolgreich aktualisiert.';
        } else {
            $_SESSION['flash_error'] = 'Fehler beim Aktualisieren der Freigabeeinstellungen.';
        }

        header('Location: ?route=admin/secure/cases/view&id=' . $caseId);
        exit;
    });

    // POST /admin/secure/cases/download-token/regenerate - Neuen Download-Token generieren (altes Token ungültig machen)
    $router->post('/admin/secure/cases/download-token/regenerate', static function (): void {
        if (!SecurePortalService::canManageCases()) {
            $_SESSION['flash_error'] = 'Zugriff verweigert.';
            header('Location: ?route=admin/secure/cases');
            exit;
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            $id = (int) ($_POST['id'] ?? 0);
            header('Location: ?route=admin/secure/cases/view&id=' . $id);
            exit;
        }

        $caseId = (int) ($_POST['id'] ?? 0);
        $case = SecurePortalRepository::getCaseById($caseId);
        if (!$case) {
            $_SESSION['flash_error'] = 'Vorgang nicht gefunden.';
            header('Location: ?route=admin/secure/cases');
            exit;
        }

        $user = SecurePortalService::getCurrentAdminUser();
        $author = (string) ($user['username'] ?? ($user['email'] ?? 'Admin'));
        $enabled = !empty($case['download_enabled']);
        $expiresAt = !empty($case['download_expires_at']) ? (string)$case['download_expires_at'] : null;

        $newToken = SecurePortalRepository::issueDownloadToken($caseId, $enabled, $expiresAt, $author);

        if ($newToken) {
            $_SESSION['flash_success'] = 'Ein neuer Download-Token wurde erzeugt. Bisherige Freigabelinks sind ab sofort ungültig.';
        } else {
            $_SESSION['flash_error'] = 'Fehler beim Erzeugen des neuen Download-Tokens.';
        }

        header('Location: ?route=admin/secure/cases/view&id=' . $caseId);
        exit;
    });

    // =========================================================================
    // 5. ADMIN-ROUTEN: EINSTELLUNGEN & NAMENS-TEMPLATES
    // =========================================================================

    // GET /admin/secure/settings - Konfiguration von Namensmustern & SharePoint
    $router->get('/admin/secure/settings', static function (): void {
        if (!SecurePortalService::canManageCases()) {
            $_SESSION['flash_error'] = 'Zugriff verweigert: Sie haben keine Berechtigung für Einstellungen des Sicherungsportals.';
            header('Location: ?route=admin/secure/cases');
            exit;
        }

        require __DIR__ . '/views/admin/settings.php';
    });

    // POST /admin/secure/settings - Speichert Templates & Anbindungsparameter
    $router->post('/admin/secure/settings', static function (): void {
        if (!SecurePortalService::canManageCases()) {
            $_SESSION['flash_error'] = 'Zugriff verweigert: Keine Schreibberechtigung.';
            header('Location: ?route=admin/secure/cases');
            exit;
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/secure/settings');
            exit;
        }

        $folderTemplate   = trim((string) ($_POST['secure_case_folder_template'] ?? ''));
        $filenameTemplate = trim((string) ($_POST['secure_case_warrant_filename_template'] ?? ''));

        // 1. Längenprüfung (max 255 Zeichen)
        if (mb_strlen($folderTemplate) > 255 || mb_strlen($filenameTemplate) > 255) {
            $_SESSION['flash_error'] = 'Die Namens-Templates dürfen jeweils maximal 255 Zeichen lang sein.';
            header('Location: ?route=admin/secure/settings');
            exit;
        }

        // 2. Sicherheitsprüfung: Dateinamen-Template darf KEINE Slashes (/ oder \) enthalten
        if (str_contains($filenameTemplate, '/') || str_contains($filenameTemplate, '\\')) {
            $_SESSION['flash_error'] = 'Ungültiges Dateinamen-Template: Innerhalb des Dateinamens sind keine Schrägstriche (/ oder \\) erlaubt.';
            header('Location: ?route=admin/secure/settings');
            exit;
        }

        // Standard-Werte bei leerer Eingabe
        if ($folderTemplate === '') {
            $folderTemplate = Naming::DEFAULT_FOLDER_TEMPLATE;
        }
        if ($filenameTemplate === '') {
            $filenameTemplate = Naming::DEFAULT_FILENAME_TEMPLATE;
        } elseif (!preg_match('/\.pdf$/i', $filenameTemplate)) {
            $filenameTemplate .= '.pdf';
        }

        if (class_exists('Settings')) {
            Settings::set('secure_case_folder_template', $folderTemplate);
            Settings::set('secure_case_warrant_filename_template', $filenameTemplate);

            // Sammel-E-Mail für neue Sicherungsfälle
            if (isset($_POST['secure_notification_email'])) {
                $rawNotificationEmail = trim((string) $_POST['secure_notification_email']);
                if ($rawNotificationEmail !== '' && !filter_var($rawNotificationEmail, FILTER_VALIDATE_EMAIL)) {
                    $_SESSION['flash_error'] = 'Die angegebene Sammel-E-Mail-Adresse ist ungültig.';
                    header('Location: ?route=admin/secure/settings');
                    exit;
                }
                Settings::set('secure_notification_email', $rawNotificationEmail);
            }

            // Fristen für externen Datenraum (T+30 Zugangssperre, T+60 Dateilöschung)
            if (isset($_POST['secure_download_days_active']) || isset($_POST['secure_download_days_delete'])) {
                $rawDaysActive = filter_var($_POST['secure_download_days_active'] ?? 30, FILTER_VALIDATE_INT);
                $rawDaysDelete = filter_var($_POST['secure_download_days_delete'] ?? 60, FILTER_VALIDATE_INT);

                if ($rawDaysActive === false || $rawDaysActive <= 0 || $rawDaysDelete === false || $rawDaysDelete <= 0) {
                    $_SESSION['flash_error'] = 'Die Fristen für den Datenraum müssen positive ganze Zahlen sein (z. B. 30 und 60).';
                    header('Location: ?route=admin/secure/settings');
                    exit;
                }

                if ($rawDaysDelete < $rawDaysActive) {
                    $_SESSION['flash_error'] = 'Die Frist bis zur Datenlöschung (T+' . $rawDaysDelete . ') darf nicht kürzer sein als die Frist bis zur Zugangssperre (T+' . $rawDaysActive . ').';
                    header('Location: ?route=admin/secure/settings');
                    exit;
                }

                Settings::set('secure_download_days_active', (string) $rawDaysActive);
                Settings::set('secure_download_days_delete', (string) $rawDaysDelete);
            }

            // Videoüberwachungs-Optionen für Antragsformular (Objekt, Stockwerk, Farbe, Parkplatz)
            if (isset($_POST['secure_video_objects'])) {
                Settings::set('secure_video_objects', trim((string) $_POST['secure_video_objects']));
            }
            if (isset($_POST['secure_video_floors'])) {
                Settings::set('secure_video_floors', trim((string) $_POST['secure_video_floors']));
            }
            if (isset($_POST['secure_video_colors'])) {
                Settings::set('secure_video_colors', trim((string) $_POST['secure_video_colors']));
            }
            if (isset($_POST['secure_video_parking_spaces'])) {
                Settings::set('secure_video_parking_spaces', trim((string) $_POST['secure_video_parking_spaces']));
            }

            // Optionale SharePoint-Settings aktualisieren
            if (isset($_POST['sp_tenant_id'])) {
                Settings::set('sp_tenant_id', trim((string) $_POST['sp_tenant_id']));
            }
            if (isset($_POST['sp_client_id'])) {
                Settings::set('sp_client_id', trim((string) $_POST['sp_client_id']));
            }
            if (isset($_POST['sp_client_secret']) && trim((string)$_POST['sp_client_secret']) !== '') {
                Settings::set('sp_client_secret', trim((string) $_POST['sp_client_secret']));
            }
            if (isset($_POST['sp_base_folder'])) {
                Settings::set('sp_base_folder', trim((string) $_POST['sp_base_folder']));
            }
            if (isset($_POST['sp_site_id'])) {
                Settings::set('sp_site_id', trim((string) $_POST['sp_site_id']));
            }
            if (isset($_POST['sp_drive_id'])) {
                Settings::set('sp_drive_id', trim((string) $_POST['sp_drive_id']));
            }
        }

        $_SESSION['flash_success'] = 'Die Einstellungen und Namens-Templates wurden erfolgreich aktualisiert.';
        header('Location: ?route=admin/secure/settings');
        exit;
    });

    // POST /admin/secure/retention/run - Manuelle Ausführung der Fristenprüfung & Bereinigung
    $router->post('/admin/secure/retention/run', static function (): void {
        if (!SecurePortalService::canManageCases()) {
            $_SESSION['flash_error'] = 'Zugriff verweigert: Keine Berechtigung zur Fristen-Bereinigung.';
            header('Location: ?route=admin/secure/cases');
            exit;
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/secure/settings');
            exit;
        }

        $user = SecurePortalService::getCurrentAdminUser();
        $author = (string) ($user['username'] ?? ($user['email'] ?? 'Admin'));

        $res = SecurePortalService::runRetentionCleanup(null, null, $author);

        if (!empty($res['errors'])) {
            $_SESSION['flash_error'] = 'Fristen-Bereinigung mit Fehlern abgeschlossen: ' . implode('; ', $res['errors']);
        } else {
            $_SESSION['flash_success'] = sprintf(
                'Fristenprüfung erfolgreich ausgeführt: %d Download-Zugänge nach Fristablauf (T+%d Tage) gesperrt, %d Sicherungsdateien in %d Vorgängen nach Aufbewahrungsfrist (T+%d Tage) gelöscht.',
                $res['locked_count'],
                $res['days_active'],
                $res['purged_files_count'],
                $res['purged_cases_count'],
                $res['days_delete']
            );
        }

        $redirect = trim((string) ($_POST['return_to'] ?? ''));
        if ($redirect !== '' && str_starts_with($redirect, '?route=admin/secure/')) {
            header('Location: ' . $redirect);
        } else {
            header('Location: ?route=admin/secure/settings');
        }
        exit;
    });
}
