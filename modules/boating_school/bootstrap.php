<?php

declare(strict_types=1);

/**
 * Bootstrap für das Modul "Bootsfahrschule" (boating_school)
 *
 * Registriert Menüpunkte, bindet Repositories/Services ein
 * und definiert alle Admin-Routen für Boote, Fahrlehrer, Leistungen und Kunden.
 *
 * @var Router|null $router
 * @var array<string, mixed> $module
 * @var string $moduleDir
 */

// Klassen des Moduls einbinden
require_once __DIR__ . '/classes/BoatingSchoolRepository.php';
require_once __DIR__ . '/classes/BoatingSchoolService.php';
require_once __DIR__ . '/classes/BoatService.php';
require_once __DIR__ . '/classes/InstructorService.php';
require_once __DIR__ . '/classes/ServiceService.php';
require_once __DIR__ . '/classes/CustomerService.php';
require_once __DIR__ . '/classes/StudentService.php';
require_once __DIR__ . '/classes/LocationService.php';
require_once __DIR__ . '/classes/AppointmentService.php';
require_once __DIR__ . '/classes/BoatingNotificationService.php';
require_once __DIR__ . '/classes/InstructorPayoutService.php';
require_once __DIR__ . '/classes/BoatingCustomerMagicLinkService.php';
require_once __DIR__ . '/classes/BoatingMagicLinkService.php';
require_once __DIR__ . '/classes/BoatingPermissions.php';
require_once __DIR__ . '/classes/CoachService.php';
require_once __DIR__ . '/classes/CoachAvailabilityService.php';
require_once __DIR__ . '/classes/BoatingMigrationManager.php';
require_once __DIR__ . '/classes/NauticAccountRequestService.php';

// Idempotente Schema- & Berechtigungsinitialisierung
if (class_exists('BoatingMigrationManager')) {
    BoatingMigrationManager::run();
}
if (class_exists('NauticAccountRequestService')) {
    NauticAccountRequestService::ensureTable();
}

// Menüpunkte im Admin-Bereich registrieren (gemäß Anforderung 4)
if (class_exists('ModuleManager')) {
    // 1. Dashboard Nautic
    ModuleManager::addAdminMenuItem([
        'group' => 'nautic',
        'label' => 'Übersicht & Flotte',
        'route' => 'admin/boating',
        'url'   => '?route=admin/boating',
        'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-compass-fill me-2" viewBox="0 0 16 16"><path d="M15.5 8.516a7.5 7.5 0 1 1-9.462-7.24A1 1 0 0 1 7 0h2a1 1 0 0 1 .962 1.276 7.5 7.5 0 0 1 5.538 7.24m-3.61-3.905L6.94 7.439 4.11 12.39l4.95-2.828 2.83-4.95z"/></svg>',
    ]);

    // 2. Kalender & Termine -> ?route=admin/boating/calendar
    ModuleManager::addAdminMenuItem([
        'label' => 'Kalender & Termine',
        'route' => 'admin/boating/calendar',
        'url'   => '?route=admin/boating/calendar',
        'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-calendar3 me-2" viewBox="0 0 16 16"><path d="M14 0H2a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zM1 3.857C1 3.384 1.448 3 2 3h12c.552 0 1 .384 1 .857v10.286c0 .473-.448.857-1 .857H2c-.552 0-1-.384-1-.857V3.857z"/><path d="M6.5 7a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm-9 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm-9 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"/></svg>',
    ]);

    // 3. Boote -> ?route=admin/boating/boats
    ModuleManager::addAdminMenuItem([
        'label' => 'Boote',
        'route' => 'admin/boating/boats',
        'url'   => '?route=admin/boating/boats',
        'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-tsunami me-2" viewBox="0 0 16 16"><path d="M.036 12.252a2.5 2.5 0 0 1 .465-.583l.053-.053.018-.017.008-.008.002-.003.001-.001a.5.5 0 0 1 .707.708l-.003.002-.01.01-.035.035a1.5 1.5 0 0 0-.279.35 1.5 1.5 0 0 0 2.072 2.071 1.5 1.5 0 0 0 .35-.279l.035-.035.01-.01.002-.003a.5.5 0 0 1 .708.707l-.001.002-.008.008-.017.018-.053.053a2.5 2.5 0 0 1-3.536-3.536zm4.242-4.243a2.5 2.5 0 0 1 .465-.583l.053-.053.018-.017.008-.008.002-.003.001-.001a.5.5 0 0 1 .707.708l-.003.002-.01.01-.035.035a1.5 1.5 0 0 0-.279.35 1.5 1.5 0 0 0 2.072 2.071 1.5 1.5 0 0 0 .35-.279l.035-.035.01-.01.002-.003a.5.5 0 0 1 .708.707l-.001.002-.008.008-.017.018-.053.053a2.5 2.5 0 0 1-3.536-3.536zm4.243-4.242a2.5 2.5 0 0 1 .465-.583l.053-.053.018-.017.008-.008.002-.003.001-.001a.5.5 0 0 1 .708.707l-.001.002-.008.008-.017.018-.053.053a2.5 2.5 0 0 1-3.536-3.536z"/></svg>',
    ]);

    // 4. Fahrlehrer -> ?route=admin/boating/instructors
    ModuleManager::addAdminMenuItem([
        'label' => 'Fahrlehrer',
        'route' => 'admin/boating/instructors',
        'url'   => '?route=admin/boating/instructors',
        'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-badge-fill me-2" viewBox="0 0 16 16"><path d="M2 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2zm4.5 0a.5.5 0 0 0 0 1h3a.5.5 0 0 0 0-1zM8 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6m5 2.755C12.146 12.825 10.623 12 8 12s-4.146.826-5 1.755V14a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1z"/></svg>',
    ]);

    // 5. Leistungen -> ?route=admin/boating/services
    ModuleManager::addAdminMenuItem([
        'label' => 'Leistungen',
        'route' => 'admin/boating/services',
        'url'   => '?route=admin/boating/services',
        'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-tag-fill me-2" viewBox="0 0 16 16"><path d="M2 1a1 1 0 0 0-1 1v4.586a1 1 0 0 0 .293.707l7 7a1 1 0 0 0 1.414 0l4.586-4.586a1 1 0 0 0 0-1.414l-7-7A1 1 0 0 0 6.586 1zm4 3.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0"/></svg>',
    ]);

    // 6. Kunden/Schüler -> ?route=admin/boating/customers
    ModuleManager::addAdminMenuItem([
        'label' => 'Kunden/Schüler',
        'route' => 'admin/boating/customers',
        'url'   => '?route=admin/boating/customers',
        'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-people-fill me-2" viewBox="0 0 16 16"><path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.24 2.24 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.3 6.3 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5"/></svg>',
    ]);

    // 7. E-Mail & Benachrichtigungen -> ?route=admin/boating/settings
    ModuleManager::addAdminMenuItem([
        'label' => 'E-Mail & Einstellungen',
        'route' => 'admin/boating/settings',
        'url'   => '?route=admin/boating/settings',
        'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-envelope-paper-fill me-2" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M6.5 1A1.5 1.5 0 0 0 5 2.5v2.784l-2.03.948A1 1 0 0 0 2.4 7.126l.492 5.412A2 2 0 0 0 4.88 14.4h6.24a2 2 0 0 0 1.988-1.862l.492-5.412a1 1 0 0 0-.57-.894L11 5.284V2.5A1.5 1.5 0 0 0 9.5 1zm-.5 1.5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v2.247l-2 1-2-1z"/></svg>',
    ]);

    // 8. Auswertungen & Fahrlehrer-Payroll -> ?route=admin/boating/reports
    ModuleManager::addAdminMenuItem([
        'label' => 'Auswertungen & Payroll',
        'route' => 'admin/boating/reports',
        'url'   => '?route=admin/boating/reports',
        'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-graph-up-arrow me-2" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M0 0h1v15h15v1H0zm10 3.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 .5.5v4a.5.5 0 0 1-1 0V4.707l-4.146 4.147a.5.5 0 0 1-.708 0L7 6.707l-3.646 3.647a.5.5 0 0 1-.708-.708l4-4a.5.5 0 0 1 .708 0L9.5 7.793l3.646-3.647H10.5a.5.5 0 0 1-.5-.5"/></svg>',
    ]);

    // 9. Fahrlehrer-Portal & Monatsabschluss -> ?route=admin/boating/instructor-portal
    ModuleManager::addAdminMenuItem([
        'label' => 'Fahrlehrer-Portal',
        'route' => 'admin/boating/instructor-portal',
        'url'   => '?route=admin/boating/instructor-portal',
        'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-workspace me-2" viewBox="0 0 16 16"><path d="M4 16s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-9a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.24 2.24 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.3 6.3 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1zM2 1a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2z"/></svg>',
    ]);

    // 10. Account-Anfragen & Freischaltungen -> ?route=admin/boating/account-requests
    ModuleManager::addAdminMenuItem([
        'label' => 'Account-Anfragen',
        'route' => 'admin/boating/account-requests',
        'url'   => '?route=admin/boating/account-requests',
        'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-check-fill me-2" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M15.854 5.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 0 1 .708-.708L12.5 7.793l2.646-2.647a.5.5 0 0 1 .708 0z"/><path d="M1 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg>',
    ]);
}

// Routen registrieren
if (isset($router) && $router !== null) {

    // Hilfsfunktion: Berechtigungsprüfung
    $checkPermission = static function (bool $manage = false): void {
        if (!class_exists('Auth') || !Auth::check()) {
            $_SESSION['flash_error'] = 'Bitte melden Sie sich an, um diesen Bereich zu betreten.';
            header('Location: ?route=login');
            exit;
        }

        $allowed = $manage ? BoatingSchoolService::canManage() : BoatingSchoolService::canView();
        if (!$allowed) {
            $_SESSION['flash_error'] = 'Sie verfügen nicht über ausreichende Berechtigungen für das Bootsfahrschul-Modul.';
            header('Location: ?route=admin/dashboard');
            exit;
        }
    };

    // Helper zur Registrierung beider Routenpräfixe (/admin/boating und /admin/boating-school)
    $registerGet = static function (string $subPath, callable $handler) use ($router): void {
        $p1 = '/admin/boating' . ($subPath ? '/' . ltrim($subPath, '/') : '');
        $p2 = '/admin/boating-school' . ($subPath ? '/' . ltrim($subPath, '/') : '');
        $router->get($p1, $handler);
        $router->get($p2, $handler);
    };

    $registerPost = static function (string $subPath, callable $handler) use ($router): void {
        $p1 = '/admin/boating' . ($subPath ? '/' . ltrim($subPath, '/') : '');
        $p2 = '/admin/boating-school' . ($subPath ? '/' . ltrim($subPath, '/') : '');
        $router->post($p1, $handler);
        $router->post($p2, $handler);
    };

    // =========================================================================
    // 1. DASHBOARD
    // =========================================================================
    $registerGet('', static function () use ($checkPermission): void {
        $checkPermission(false);

        $tablesCreated = BoatingSchoolRepository::areAllTablesCreated();
        $stats = BoatingSchoolRepository::getDashboardStats();
        $recentBoats = BoatingSchoolRepository::getBoats(null, 5, 0);
        $recentInstructors = BoatingSchoolRepository::getInstructors(null, 5, 0);
        $recentCustomers = BoatingSchoolRepository::getCustomers(null, 5, 0);

        require __DIR__ . '/views/admin/dashboard.php';
    });

    $registerGet('dashboard', static function () use ($checkPermission): void {
        $checkPermission(false);

        $tablesCreated = BoatingSchoolRepository::areAllTablesCreated();
        $stats = BoatingSchoolRepository::getDashboardStats();
        $recentBoats = BoatingSchoolRepository::getBoats(null, 5, 0);
        $recentInstructors = BoatingSchoolRepository::getInstructors(null, 5, 0);
        $recentCustomers = BoatingSchoolRepository::getCustomers(null, 5, 0);

        require __DIR__ . '/views/admin/dashboard.php';
    });

    // =========================================================================
    // 2. BOOTE (bs_boats)
    // =========================================================================
    $registerGet('boats', static function () use ($checkPermission): void {
        $checkPermission(false);

        $filters = [
            'search'    => trim((string) ($_GET['search'] ?? '')),
            'type'      => trim((string) ($_GET['type'] ?? '')),
            'is_active' => isset($_GET['is_active']) && $_GET['is_active'] !== '' ? (string) $_GET['is_active'] : null,
        ];

        $boats = BoatService::getAll($filters);
        $totalBoats = BoatService::count($filters);
        $tablesCreated = BoatingSchoolRepository::isTableCreated('bs_boats');

        require __DIR__ . '/views/admin/boats_index.php';
    });

    $registerPost('boats/create', static function () use ($checkPermission): void {
        $checkPermission(true);

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/boating/boats');
            exit;
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') {
            $_SESSION['flash_error'] = 'Bitte geben Sie einen Namen für das Boot ein.';
            header('Location: ?route=admin/boating/boats');
            exit;
        }

        $imagePath = null;
        if (!empty($_FILES['image'])) {
            $imagePath = BoatingSchoolService::handleBoatImageUpload($_FILES['image']);
        }

        $data = [
            'name'         => $name,
            'registration' => trim((string) ($_POST['registration'] ?? '')),
            'type'         => trim((string) ($_POST['type'] ?? 'motor')),
            'capacity'     => !empty($_POST['capacity']) ? (int) $_POST['capacity'] : null,
            'engine_power' => !empty($_POST['engine_power']) ? (int) $_POST['engine_power'] : null,
            'color'        => trim((string) ($_POST['color'] ?? '')),
            'image_path'   => $imagePath,
            'notes'        => trim((string) ($_POST['notes'] ?? '')),
            'is_active'    => !empty($_POST['is_active']) ? 1 : 0,
        ];

        try {
            $boatId = BoatService::create($data);
            BoatingSchoolService::log('boat_created', ['boat_id' => $boatId, 'name' => $name]);
            $_SESSION['flash_success'] = 'Boot "' . $name . '" wurde erfolgreich angelegt.';
        } catch (\Throwable $e) {
            error_log('Boot erstellen fehlgeschlagen: ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Fehler beim Anlegen des Boots: ' . $e->getMessage();
        }

        header('Location: ?route=admin/boating/boats');
        exit;
    });

    $registerGet('boats/edit', static function () use ($checkPermission): void {
        $checkPermission(false);

        $id = (int) ($_GET['id'] ?? 0);
        $boat = BoatService::find($id);

        if (!$boat) {
            $_SESSION['flash_error'] = 'Das angeforderte Boot wurde nicht gefunden.';
            header('Location: ?route=admin/boating/boats');
            exit;
        }

        require __DIR__ . '/views/admin/boat_edit.php';
    });

    $registerPost('boats/update', static function () use ($checkPermission): void {
        $checkPermission(true);

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/boating/boats');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $existingBoat = BoatService::find($id);

        if (!$existingBoat) {
            $_SESSION['flash_error'] = 'Boot nicht gefunden.';
            header('Location: ?route=admin/boating/boats');
            exit;
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') {
            $_SESSION['flash_error'] = 'Der Bootsname darf nicht leer sein.';
            header('Location: ?route=admin/boating/boats/edit&id=' . $id);
            exit;
        }

        $imagePath = $existingBoat['image_path'] ?? null;
        if (!empty($_POST['delete_image'])) {
            $imagePath = null;
        } elseif (!empty($_FILES['image']) && is_uploaded_file($_FILES['image']['tmp_name'] ?? '')) {
            $newImage = BoatingSchoolService::handleBoatImageUpload($_FILES['image']);
            if ($newImage !== null) {
                $imagePath = $newImage;
            }
        }

        $data = [
            'name'         => $name,
            'registration' => trim((string) ($_POST['registration'] ?? '')),
            'type'         => trim((string) ($_POST['type'] ?? 'motor')),
            'capacity'     => !empty($_POST['capacity']) ? (int) $_POST['capacity'] : null,
            'engine_power' => !empty($_POST['engine_power']) ? (int) $_POST['engine_power'] : null,
            'color'        => trim((string) ($_POST['color'] ?? '')),
            'image_path'   => $imagePath,
            'notes'        => trim((string) ($_POST['notes'] ?? '')),
            'is_active'    => !empty($_POST['is_active']) ? 1 : 0,
        ];

        try {
            BoatService::update($id, $data);
            BoatingSchoolService::log('boat_updated', ['boat_id' => $id, 'name' => $name]);
            $_SESSION['flash_success'] = 'Boot "' . $name . '" wurde erfolgreich aktualisiert.';
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Fehler beim Speichern: ' . $e->getMessage();
        }

        header('Location: ?route=admin/boating/boats/edit&id=' . $id);
        exit;
    });

    $registerPost('boats/toggle-status', static function () use ($checkPermission): void {
        $checkPermission(true);

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/boating/boats');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        BoatService::toggleStatus($id);
        $_SESSION['flash_success'] = 'Status des Boots wurde umgeschaltet.';
        header('Location: ?route=admin/boating/boats');
        exit;
    });

    $registerPost('boats/delete', static function () use ($checkPermission): void {
        $checkPermission(true);

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/boating/boats');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        BoatService::delete($id);
        BoatingSchoolService::log('boat_deleted', ['boat_id' => $id]);
        $_SESSION['flash_success'] = 'Boot wurde gelöscht.';
        header('Location: ?route=admin/boating/boats');
        exit;
    });

    // =========================================================================
    // 3. FAHRLEHRER (bs_instructors)
    // =========================================================================
    $registerGet('instructors', static function () use ($checkPermission): void {
        $checkPermission(false);

        $filters = [
            'search'    => trim((string) ($_GET['search'] ?? '')),
            'is_active' => isset($_GET['is_active']) && $_GET['is_active'] !== '' ? (string) $_GET['is_active'] : null,
        ];

        $instructors = InstructorService::getAll($filters);
        $totalInstructors = InstructorService::count($filters);
        $tablesCreated = BoatingSchoolRepository::isTableCreated('bs_instructors');
        $users = class_exists('User') ? User::all() : [];
        $hrDetailsMap = [];
        if (class_exists('CoachService')) {
            foreach ($instructors as $inst) {
                $iId = (int) $inst['id'];
                $hr = CoachService::getHrDetails($iId);
                if ($hr) {
                    $hrDetailsMap[$iId] = $hr;
                }
            }
        }

        require __DIR__ . '/views/admin/instructors_index.php';
    });

    $registerPost('instructors/create', static function () use ($checkPermission): void {
        $checkPermission(true);

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/boating/instructors');
            exit;
        }

        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));

        if ($firstName === '' || $lastName === '') {
            $_SESSION['flash_error'] = 'Bitte Vor- und Nachnamen des Fahrlehrers angeben.';
            header('Location: ?route=admin/boating/instructors');
            exit;
        }

        $imagePath = null;
        if (!empty($_FILES['image'])) {
            $imagePath = BoatingSchoolService::handleInstructorImageUpload($_FILES['image']);
        }

        $data = [
            'user_id'               => !empty($_POST['user_id']) ? (int) $_POST['user_id'] : null,
            'first_name'            => $firstName,
            'last_name'             => $lastName,
            'title'                 => !empty($_POST['title']) ? trim((string) $_POST['title']) : 'Fahrcoach',
            'email'                 => trim((string) ($_POST['email'] ?? '')),
            'phone'                 => trim((string) ($_POST['phone'] ?? '')),
            'public_mobile'         => !empty($_POST['public_mobile']) ? trim((string) $_POST['public_mobile']) : null,
            'is_mobile_public'      => !empty($_POST['is_mobile_public']) ? 1 : 0,
            'license_number'        => '',
            'pay_rate_type'         => !empty($_POST['pay_rate_type']) ? (string) $_POST['pay_rate_type'] : 'per_hour',
            'pay_rate_amount'       => isset($_POST['pay_rate_amount']) ? (float) $_POST['pay_rate_amount'] : (isset($_POST['hourly_rate']) ? (float) $_POST['hourly_rate'] : 0.00),
            'payroll_external_code' => !empty($_POST['payroll_external_code']) ? trim((string) $_POST['payroll_external_code']) : null,
            'qualifications'        => trim((string) ($_POST['qualifications'] ?? ($_POST['bio'] ?? ''))),
            'color_code'            => trim((string) ($_POST['color_code'] ?? '#0d6efd')),
            'image_path'            => $imagePath,
            'image_url'             => !empty($_POST['image_url']) ? trim((string) $_POST['image_url']) : null,
            'notes'                 => trim((string) ($_POST['notes'] ?? '')),
            'is_active'             => !empty($_POST['is_active']) ? 1 : 0,
        ];

        try {
            $instId = InstructorService::create($data);
            if (class_exists('CoachService')) {
                CoachService::saveHrDetails($instId, $_POST);
            }
            BoatingSchoolService::log('instructor_created', ['instructor_id' => $instId, 'name' => $firstName . ' ' . $lastName]);
            $_SESSION['flash_success'] = 'Coach ' . $firstName . ' ' . $lastName . ' wurde erfolgreich angelegt.';
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Fehler beim Erstellen des Fahrlehrers: ' . $e->getMessage();
        }

        header('Location: ?route=admin/boating/instructors');
        exit;
    });

    $registerGet('instructors/edit', static function () use ($checkPermission): void {
        $checkPermission(false);

        $id = (int) ($_GET['id'] ?? 0);
        $instructor = InstructorService::find($id);

        if (!$instructor) {
            $_SESSION['flash_error'] = 'Fahrlehrer nicht gefunden.';
            header('Location: ?route=admin/boating/instructors');
            exit;
        }

        $users = class_exists('User') ? User::all() : [];
        $hrDetails = class_exists('CoachService') ? CoachService::getHrDetails($id) : null;

        require __DIR__ . '/views/admin/instructor_edit.php';
    });

    $registerPost('instructors/update', static function () use ($checkPermission): void {
        $checkPermission(true);

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/boating/instructors');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $existing = InstructorService::find($id);

        if (!$existing) {
            $_SESSION['flash_error'] = 'Fahrlehrer nicht gefunden.';
            header('Location: ?route=admin/boating/instructors');
            exit;
        }

        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));

        if ($firstName === '' || $lastName === '') {
            $_SESSION['flash_error'] = 'Vor- und Nachname dürfen nicht leer sein.';
            header('Location: ?route=admin/boating/instructors/edit&id=' . $id);
            exit;
        }

        $imagePath = $existing['image_path'] ?? null;
        if (!empty($_POST['delete_image'])) {
            $imagePath = null;
        } elseif (!empty($_FILES['image']) && is_uploaded_file($_FILES['image']['tmp_name'] ?? '')) {
            $newImage = BoatingSchoolService::handleInstructorImageUpload($_FILES['image']);
            if ($newImage !== null) {
                $imagePath = $newImage;
            }
        }

        $data = [
            'user_id'               => !empty($_POST['user_id']) ? (int) $_POST['user_id'] : null,
            'first_name'            => $firstName,
            'last_name'             => $lastName,
            'title'                 => !empty($_POST['title']) ? trim((string) $_POST['title']) : 'Fahrcoach',
            'email'                 => trim((string) ($_POST['email'] ?? '')),
            'phone'                 => trim((string) ($_POST['phone'] ?? '')),
            'public_mobile'         => !empty($_POST['public_mobile']) ? trim((string) $_POST['public_mobile']) : null,
            'is_mobile_public'      => !empty($_POST['is_mobile_public']) ? 1 : 0,
            'license_number'        => trim((string) ($_POST['license_number'] ?? '')),
            'pay_rate_type'         => !empty($_POST['pay_rate_type']) ? (string) $_POST['pay_rate_type'] : 'per_hour',
            'pay_rate_amount'       => isset($_POST['pay_rate_amount']) ? (float) $_POST['pay_rate_amount'] : 0.00,
            'payroll_external_code' => !empty($_POST['payroll_external_code']) ? trim((string) $_POST['payroll_external_code']) : null,
            'qualifications'        => trim((string) ($_POST['qualifications'] ?? '')),
            'color_code'            => trim((string) ($_POST['color_code'] ?? '#0d6efd')),
            'image_path'            => $imagePath,
            'image_url'             => !empty($_POST['image_url']) ? trim((string) $_POST['image_url']) : null,
            'notes'                 => trim((string) ($_POST['notes'] ?? '')),
            'is_active'             => !empty($_POST['is_active']) ? 1 : 0,
        ];

        try {
            InstructorService::update($id, $data);

            // Sensible HR-Daten in bs_instructor_details speichern
            if (class_exists('CoachService')) {
                CoachService::saveHrDetails($id, $_POST);
            }

            BoatingSchoolService::log('instructor_updated', ['instructor_id' => $id, 'name' => $firstName . ' ' . $lastName]);
            $_SESSION['flash_success'] = 'Coach ' . $firstName . ' ' . $lastName . ' wurde erfolgreich aktualisiert.';
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Fehler beim Speichern: ' . $e->getMessage();
        }

        $redirectTo = !empty($_POST['redirect_to']) ? trim((string) $_POST['redirect_to']) : ('?route=admin/boating/instructors/edit&id=' . $id);
        header('Location: ' . $redirectTo);
        exit;
    });

    $registerPost('instructors/toggle-status', static function () use ($checkPermission): void {
        $checkPermission(true);

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/boating/instructors');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        InstructorService::toggleStatus($id);
        $_SESSION['flash_success'] = 'Status des Fahrlehrers wurde umgeschaltet.';
        header('Location: ?route=admin/boating/instructors');
        exit;
    });

    $registerPost('instructors/delete', static function () use ($checkPermission): void {
        $checkPermission(true);

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/boating/instructors');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        InstructorService::delete($id);
        BoatingSchoolService::log('instructor_deleted', ['instructor_id' => $id]);
        $_SESSION['flash_success'] = 'Fahrlehrer wurde gelöscht.';
        header('Location: ?route=admin/boating/instructors');
        exit;
    });

    // =========================================================================
    // 4. LEISTUNGEN & TARIFE (bs_services)
    // =========================================================================
    $registerGet('services', static function () use ($checkPermission): void {
        $checkPermission(false);

        $filters = [
            'search'    => trim((string) ($_GET['search'] ?? '')),
            'category'  => trim((string) ($_GET['category'] ?? '')),
            'boat_type' => trim((string) ($_GET['boat_type'] ?? '')),
            'is_active' => isset($_GET['is_active']) && $_GET['is_active'] !== '' ? (string) $_GET['is_active'] : null,
        ];

        $services = ServiceService::getAll($filters);
        $totalServices = ServiceService::count($filters);
        $tablesCreated = BoatingSchoolRepository::isTableCreated('bs_services');

        require __DIR__ . '/views/admin/services_index.php';
    });

    $registerPost('services/create', static function () use ($checkPermission): void {
        $checkPermission(true);

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/boating/services');
            exit;
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') {
            $_SESSION['flash_error'] = 'Bitte geben Sie eine Bezeichnung für die Leistung ein.';
            header('Location: ?route=admin/boating/services');
            exit;
        }

        $data = [
            'name'             => $name,
            'code'             => trim((string) ($_POST['code'] ?? '')),
            'category'         => trim((string) ($_POST['category'] ?? 'practical')),
            'boat_type'        => trim((string) ($_POST['boat_type'] ?? 'any')),
            'duration_minutes' => !empty($_POST['duration_minutes']) ? (int) $_POST['duration_minutes'] : 60,
            'price'            => !empty($_POST['price']) ? (float) $_POST['price'] : 0.00,
            'tax_rate'         => isset($_POST['tax_rate']) ? (float) $_POST['tax_rate'] : 0.00,
            'description'      => trim((string) ($_POST['description'] ?? '')),
            'is_active'        => !empty($_POST['is_active']) ? 1 : 0,
        ];

        try {
            $srvId = ServiceService::create($data);
            BoatingSchoolService::log('service_created', ['service_id' => $srvId, 'name' => $name]);
            $_SESSION['flash_success'] = 'Leistung "' . $name . '" wurde erfolgreich angelegt.';
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Fehler beim Anlegen der Leistung: ' . $e->getMessage();
        }

        header('Location: ?route=admin/boating/services');
        exit;
    });

    $registerGet('services/edit', static function () use ($checkPermission): void {
        $checkPermission(false);

        $id = (int) ($_GET['id'] ?? 0);
        $service = ServiceService::find($id);

        if (!$service) {
            $_SESSION['flash_error'] = 'Leistung nicht gefunden.';
            header('Location: ?route=admin/boating/services');
            exit;
        }

        require __DIR__ . '/views/admin/service_edit.php';
    });

    $registerPost('services/update', static function () use ($checkPermission): void {
        $checkPermission(true);

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/boating/services');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));

        if ($name === '') {
            $_SESSION['flash_error'] = 'Die Bezeichnung darf nicht leer sein.';
            header('Location: ?route=admin/boating/services/edit&id=' . $id);
            exit;
        }

        $data = [
            'name'             => $name,
            'code'             => trim((string) ($_POST['code'] ?? '')),
            'category'         => trim((string) ($_POST['category'] ?? 'practical')),
            'boat_type'        => trim((string) ($_POST['boat_type'] ?? 'any')),
            'duration_minutes' => !empty($_POST['duration_minutes']) ? (int) $_POST['duration_minutes'] : 60,
            'price'            => !empty($_POST['price']) ? (float) $_POST['price'] : 0.00,
            'tax_rate'         => isset($_POST['tax_rate']) ? (float) $_POST['tax_rate'] : 0.00,
            'description'      => trim((string) ($_POST['description'] ?? '')),
            'is_active'        => !empty($_POST['is_active']) ? 1 : 0,
        ];

        try {
            ServiceService::update($id, $data);
            BoatingSchoolService::log('service_updated', ['service_id' => $id, 'name' => $name]);
            $_SESSION['flash_success'] = 'Leistung "' . $name . '" wurde erfolgreich aktualisiert.';
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Fehler beim Speichern: ' . $e->getMessage();
        }

        header('Location: ?route=admin/boating/services/edit&id=' . $id);
        exit;
    });

    $registerPost('services/toggle-status', static function () use ($checkPermission): void {
        $checkPermission(true);

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/boating/services');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        ServiceService::toggleStatus($id);
        $_SESSION['flash_success'] = 'Status der Leistung wurde umgeschaltet.';
        header('Location: ?route=admin/boating/services');
        exit;
    });

    $registerPost('services/delete', static function () use ($checkPermission): void {
        $checkPermission(true);

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/boating/services');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        ServiceService::delete($id);
        BoatingSchoolService::log('service_deleted', ['service_id' => $id]);
        $_SESSION['flash_success'] = 'Leistung wurde gelöscht.';
        header('Location: ?route=admin/boating/services');
        exit;
    });

    // =========================================================================
    // 5. SCHÜLER & KUNDEN (bs_customers)
    // =========================================================================
    $registerGet('customers', static function () use ($checkPermission): void {
        $checkPermission(false);

        $filters = [
            'search'           => trim((string) ($_GET['search'] ?? '')),
            'type'             => trim((string) ($_GET['type'] ?? '')),
            'crm_stage'        => trim((string) ($_GET['crm_stage'] ?? '')),
            'experience_level' => trim((string) ($_GET['experience_level'] ?? '')),
            'is_active'        => isset($_GET['is_active']) && $_GET['is_active'] !== '' ? (string) $_GET['is_active'] : null,
            'theory_passed'    => isset($_GET['theory_passed']) && $_GET['theory_passed'] !== '' ? (string) $_GET['theory_passed'] : null,
            'practical_passed' => isset($_GET['practical_passed']) && $_GET['practical_passed'] !== '' ? (string) $_GET['practical_passed'] : null,
        ];

        $customers = CustomerService::getAll($filters);
        $totalCustomers = CustomerService::count($filters);
        $tablesCreated = BoatingSchoolRepository::isTableCreated('bs_customers');
        $users = class_exists('User') ? User::all() : [];

        require __DIR__ . '/views/admin/customers_index.php';
    });

    // vCard-Export für einen Kunden
    $registerGet('customers/vcard', static function (): void {
        if (!BoatingSchoolService::canExportCustomers()) {
            http_response_code(403);
            die('Zugriff verweigert: Fehlende Berechtigung für vCard-Export.');
        }

        $id = (int) ($_GET['id'] ?? 0);
        $customer = CustomerService::find($id);

        if (!$customer) {
            $_SESSION['flash_error'] = 'Kunde für vCard-Export nicht gefunden.';
            header('Location: ?route=admin/boating/customers');
            exit;
        }

        $vcard = CustomerService::generateVCard($customer);
        $cleanLast = preg_replace('/[^a-zA-Z0-9_-]/', '_', (string) $customer['last_name']);
        $cleanFirst = preg_replace('/[^a-zA-Z0-9_-]/', '_', (string) $customer['first_name']);
        $filename = 'kunde_' . strtolower($cleanLast . '_' . $cleanFirst) . '.vcf';

        header('Content-Type: text/x-vcard; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($vcard));
        header('Pragma: no-cache');
        header('Expires: 0');

        echo $vcard;
        exit;
    });

    // CSV-Export für Kundenliste
    $registerGet('customers/export-csv', static function (): void {
        if (!BoatingSchoolService::canExportCustomers()) {
            http_response_code(403);
            die('Zugriff verweigert: Fehlende Berechtigung für CSV-Export.');
        }

        $filters = [
            'search'           => trim((string) ($_GET['search'] ?? '')),
            'type'             => trim((string) ($_GET['type'] ?? '')),
            'crm_stage'        => trim((string) ($_GET['crm_stage'] ?? '')),
            'experience_level' => trim((string) ($_GET['experience_level'] ?? '')),
            'is_active'        => isset($_GET['is_active']) && $_GET['is_active'] !== '' ? (string) $_GET['is_active'] : null,
            'theory_passed'    => isset($_GET['theory_passed']) && $_GET['theory_passed'] !== '' ? (string) $_GET['theory_passed'] : null,
            'practical_passed' => isset($_GET['practical_passed']) && $_GET['practical_passed'] !== '' ? (string) $_GET['practical_passed'] : null,
        ];

        CustomerService::streamCsvExport($filters);
    });

    // CSV-Import für Kundenliste
    $registerPost('customers/import-csv', static function (): void {
        if (!BoatingSchoolService::canImportCustomers()) {
            http_response_code(403);
            die('Zugriff verweigert: Fehlende Berechtigung für CSV-Import.');
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/boating/customers');
            exit;
        }

        if (empty($_FILES['csv_file']['tmp_name']) || !is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
            $_SESSION['flash_error'] = 'Bitte wählen Sie eine gültige CSV-Datei zum Hochladen aus.';
            header('Location: ?route=admin/boating/customers');
            exit;
        }

        $mode = ($_POST['duplicate_mode'] ?? 'skip') === 'update' ? 'update' : 'skip';
        $result = CustomerService::importCsv($_FILES['csv_file']['tmp_name'], $mode);

        if ($result['imported'] > 0 || $result['updated'] > 0) {
            $msg = sprintf(
                'CSV-Import abgeschlossen: %d neue Kunden angelegt, %d aktualisiert, %d übersprungen.',
                $result['imported'],
                $result['updated'],
                $result['skipped']
            );
            if (!empty($result['errors'])) {
                $msg .= ' Hinweise: ' . implode(' | ', array_slice($result['errors'], 0, 3));
            }
            $_SESSION['flash_success'] = $msg;
            BoatingSchoolService::log('customers_imported', [
                'imported' => $result['imported'],
                'updated'  => $result['updated'],
                'skipped'  => $result['skipped'],
            ]);
        } else {
            $_SESSION['flash_error'] = 'Keine Datensätze importiert. ' . implode(' | ', $result['errors']);
        }

        header('Location: ?route=admin/boating/customers');
        exit;
    });

    $registerPost('customers/create', static function () use ($checkPermission): void {
        $checkPermission(true);

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/boating/customers');
            exit;
        }

        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));

        if ($firstName === '' || $lastName === '') {
            $_SESSION['flash_error'] = 'Bitte Vor- und Nachnamen des Kunden/Schülers angeben.';
            header('Location: ?route=admin/boating/customers');
            exit;
        }

        $data = [
            'customer_number'     => trim((string) ($_POST['customer_number'] ?? '')),
            'type'                => trim((string) ($_POST['type'] ?? 'student')),
            'crm_stage'           => trim((string) ($_POST['crm_stage'] ?? 'student')),
            'user_id'             => !empty($_POST['user_id']) ? (int) $_POST['user_id'] : null,
            'first_name'          => $firstName,
            'last_name'           => $lastName,
            'company'             => trim((string) ($_POST['company'] ?? '')),
            'email'               => trim((string) ($_POST['email'] ?? '')),
            'phone'               => trim((string) ($_POST['phone'] ?? '')),
            'preferred_contact'   => trim((string) ($_POST['preferred_contact'] ?? 'email')),
            'lead_source'         => trim((string) ($_POST['lead_source'] ?? '')),
            'tags'                => trim((string) ($_POST['tags'] ?? '')),
            'street'              => trim((string) ($_POST['street'] ?? '')),
            'zip'                 => trim((string) ($_POST['zip'] ?? '')),
            'city'                => trim((string) ($_POST['city'] ?? '')),
            'birthdate'           => trim((string) ($_POST['birthdate'] ?? '')),
            'target_license'      => trim((string) ($_POST['target_license'] ?? '')),
            'licenses_held'       => trim((string) ($_POST['licenses_held'] ?? '')),
            'license_number'      => trim((string) ($_POST['license_number'] ?? '')),
            'experience_level'    => trim((string) ($_POST['experience_level'] ?? 'beginner')),
            'theory_passed'       => !empty($_POST['theory_passed']) ? 1 : 0,
            'theory_passed_at'    => trim((string) ($_POST['theory_passed_at'] ?? '')),
            'practical_passed'    => !empty($_POST['practical_passed']) ? 1 : 0,
            'practical_passed_at' => trim((string) ($_POST['practical_passed_at'] ?? '')),
            'emergency_contact'   => trim((string) ($_POST['emergency_contact'] ?? '')),
            'notes'               => trim((string) ($_POST['notes'] ?? '')),
            'internal_notes'      => trim((string) ($_POST['internal_notes'] ?? '')),
            'notify_email'        => isset($_POST['notify_email']) ? 1 : (isset($_POST['active_tab']) ? 0 : 1),
            'is_active'           => !empty($_POST['is_active']) ? 1 : 0,
        ];

        try {
            $custId = CustomerService::create($data);
            BoatingSchoolService::log('customer_created', ['customer_id' => $custId, 'name' => $firstName . ' ' . $lastName]);
            $_SESSION['flash_success'] = 'Kunde / Fahrschüler ' . $firstName . ' ' . $lastName . ' wurde erfolgreich angelegt.';
            $redir = !empty($_POST['redirect_to']) ? (string) $_POST['redirect_to'] : ('?route=admin/boating/customers/edit&id=' . $custId);
            header('Location: ' . $redir);
            exit;
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Fehler beim Anlegen: ' . $e->getMessage();
            header('Location: ?route=admin/boating/customers');
            exit;
        }
    });

    $renderCustomerDetail = static function () use ($checkPermission): void {
        $checkPermission(false);

        $id = (int) ($_GET['id'] ?? 0);
        $crmData = CustomerService::getCrmDetails($id);

        if (!$crmData) {
            $_SESSION['flash_error'] = 'Kunde / Fahrschüler nicht gefunden.';
            header('Location: ?route=admin/boating/customers');
            exit;
        }

        $customer = $crmData['customer'];
        $appointments = $crmData['appointments'];
        $subscriptions = $crmData['subscriptions'];
        $services = ServiceService::getAll(['is_active' => 1]);
        $users = class_exists('User') ? User::all() : [];
        $activeTab = trim((string) ($_GET['tab'] ?? 'overview'));

        require __DIR__ . '/views/admin/customer_edit.php';
    };

    $registerGet('customers/edit', $renderCustomerDetail);
    $registerGet('customers/view', $renderCustomerDetail);

    $registerPost('customers/update', static function () use ($checkPermission): void {
        $checkPermission(true);

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/boating/customers');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));

        if ($firstName === '' || $lastName === '') {
            $_SESSION['flash_error'] = 'Vor- und Nachname dürfen nicht leer sein.';
            header('Location: ?route=admin/boating/customers/edit&id=' . $id);
            exit;
        }

        $data = [
            'customer_number'     => trim((string) ($_POST['customer_number'] ?? '')),
            'type'                => trim((string) ($_POST['type'] ?? 'student')),
            'crm_stage'           => trim((string) ($_POST['crm_stage'] ?? 'student')),
            'user_id'             => !empty($_POST['user_id']) ? (int) $_POST['user_id'] : null,
            'first_name'          => $firstName,
            'last_name'           => $lastName,
            'company'             => trim((string) ($_POST['company'] ?? '')),
            'email'               => trim((string) ($_POST['email'] ?? '')),
            'phone'               => trim((string) ($_POST['phone'] ?? '')),
            'preferred_contact'   => trim((string) ($_POST['preferred_contact'] ?? 'email')),
            'lead_source'         => trim((string) ($_POST['lead_source'] ?? '')),
            'tags'                => trim((string) ($_POST['tags'] ?? '')),
            'street'              => trim((string) ($_POST['street'] ?? '')),
            'zip'                 => trim((string) ($_POST['zip'] ?? '')),
            'city'                => trim((string) ($_POST['city'] ?? '')),
            'birthdate'           => trim((string) ($_POST['birthdate'] ?? '')),
            'target_license'      => trim((string) ($_POST['target_license'] ?? '')),
            'licenses_held'       => trim((string) ($_POST['licenses_held'] ?? '')),
            'license_number'      => trim((string) ($_POST['license_number'] ?? '')),
            'experience_level'    => trim((string) ($_POST['experience_level'] ?? 'beginner')),
            'theory_passed'       => !empty($_POST['theory_passed']) ? 1 : 0,
            'theory_passed_at'    => trim((string) ($_POST['theory_passed_at'] ?? '')),
            'practical_passed'    => !empty($_POST['practical_passed']) ? 1 : 0,
            'practical_passed_at' => trim((string) ($_POST['practical_passed_at'] ?? '')),
            'emergency_contact'   => trim((string) ($_POST['emergency_contact'] ?? '')),
            'notes'               => trim((string) ($_POST['notes'] ?? '')),
            'internal_notes'      => trim((string) ($_POST['internal_notes'] ?? '')),
            'notify_email'        => isset($_POST['notify_email']) ? 1 : (isset($_POST['active_tab']) ? 0 : 1),
            'is_active'           => !empty($_POST['is_active']) ? 1 : 0,
        ];

        $tab = !empty($_POST['active_tab']) ? '&tab=' . urlencode((string) $_POST['active_tab']) : '';

        try {
            CustomerService::update($id, $data);
            BoatingSchoolService::log('customer_updated', ['customer_id' => $id, 'name' => $firstName . ' ' . $lastName]);
            $_SESSION['flash_success'] = 'Kunde / Fahrschüler ' . $firstName . ' ' . $lastName . ' wurde erfolgreich aktualisiert.';
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Fehler beim Speichern: ' . $e->getMessage();
        }

        $redir = !empty($_POST['redirect_to']) ? (string) $_POST['redirect_to'] : ('?route=admin/boating/customers/edit&id=' . $id . $tab);
        header('Location: ' . $redir);
        exit;
    });

    $registerPost('customers/toggle-status', static function () use ($checkPermission): void {
        $checkPermission(true);

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/boating/customers');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        CustomerService::toggleStatus($id);
        $_SESSION['flash_success'] = 'Status des Kunden wurde umgeschaltet.';
        header('Location: ?route=admin/boating/customers');
        exit;
    });

    $registerPost('customers/delete', static function () use ($checkPermission): void {
        $checkPermission(true);

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/boating/customers');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        CustomerService::delete($id);
        BoatingSchoolService::log('customer_deleted', ['customer_id' => $id]);
        $_SESSION['flash_success'] = 'Kunde / Fahrschüler wurde gelöscht.';
        header('Location: ?route=admin/boating/customers');
        exit;
    });

    // Subscriptions anlegen
    $registerPost('customers/subscriptions/create', static function (): void {
        if (!BoatingSchoolService::canManageSubscriptions()) {
            http_response_code(403);
            die('Zugriff verweigert: Fehlende Berechtigung für Abonnement-Verwaltung.');
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/boating/customers');
            exit;
        }

        $customerId = (int) ($_POST['customer_id'] ?? 0);
        $title = trim((string) ($_POST['title'] ?? ''));

        if ($customerId <= 0 || $title === '') {
            $_SESSION['flash_error'] = 'Bitte Titel und Kunden für das Abonnement angeben.';
            header('Location: ?route=admin/boating/customers/edit&id=' . $customerId . '&tab=subscriptions');
            exit;
        }

        $data = [
            'customer_id' => $customerId,
            'service_id'  => !empty($_POST['service_id']) ? (int) $_POST['service_id'] : null,
            'title'       => $title,
            'code'        => trim((string) ($_POST['code'] ?? '')),
            'type'        => trim((string) ($_POST['type'] ?? 'units')),
            'total_units' => max(1, (int) ($_POST['total_units'] ?? 10)),
            'used_units'  => max(0, (int) ($_POST['used_units'] ?? 0)),
            'price'       => (float) ($_POST['price'] ?? 0.00),
            'valid_from'  => !empty($_POST['valid_from']) ? trim((string) $_POST['valid_from']) : date('Y-m-d'),
            'valid_until' => !empty($_POST['valid_until']) ? trim((string) $_POST['valid_until']) : null,
            'status'      => trim((string) ($_POST['status'] ?? 'active')),
            'notes'       => trim((string) ($_POST['notes'] ?? '')),
        ];

        try {
            $subId = CustomerService::createSubscription($data);
            BoatingSchoolService::log('subscription_created', ['subscription_id' => $subId, 'customer_id' => $customerId]);
            $_SESSION['flash_success'] = 'Abo- / Punktekarte "' . $title . '" wurde erfolgreich angelegt.';
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Fehler beim Anlegen des Abos: ' . $e->getMessage();
        }

        header('Location: ?route=admin/boating/customers/edit&id=' . $customerId . '&tab=subscriptions');
        exit;
    });

    // Einheiten von Subscription entwerten / abbuchen
    $registerPost('customers/subscriptions/use', static function (): void {
        if (!BoatingSchoolService::canManageSubscriptions()) {
            http_response_code(403);
            die('Zugriff verweigert: Fehlende Berechtigung für Abonnement-Verwaltung.');
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/boating/customers');
            exit;
        }

        $subscriptionId = (int) ($_POST['subscription_id'] ?? 0);
        $customerId = (int) ($_POST['customer_id'] ?? 0);
        $units = max(1, (int) ($_POST['units'] ?? 1));
        $note = trim((string) ($_POST['note'] ?? ''));
        $date = trim((string) ($_POST['used_date'] ?? date('Y-m-d')));
        $appointmentId = !empty($_POST['appointment_id']) ? (int) $_POST['appointment_id'] : null;

        try {
            CustomerService::useSubscription($subscriptionId, $units, $appointmentId, $note, $date);
            BoatingSchoolService::log('subscription_units_used', [
                'subscription_id' => $subscriptionId,
                'units'           => $units,
                'customer_id'     => $customerId,
            ]);
            $_SESSION['flash_success'] = sprintf('%d Einheit(en) wurden erfolgreich vom Abonnement abgebucht.', $units);
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Fehler beim Entwerten: ' . $e->getMessage();
        }

        header('Location: ?route=admin/boating/customers/edit&id=' . $customerId . '&tab=subscriptions');
        exit;
    });

    // Subscription löschen
    $registerPost('customers/subscriptions/delete', static function (): void {
        if (!BoatingSchoolService::canManageSubscriptions()) {
            http_response_code(403);
            die('Zugriff verweigert: Fehlende Berechtigung für Abonnement-Verwaltung.');
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/boating/customers');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $customerId = (int) ($_POST['customer_id'] ?? 0);

        try {
            CustomerService::deleteSubscription($id);
            BoatingSchoolService::log('subscription_deleted', ['subscription_id' => $id, 'customer_id' => $customerId]);
            $_SESSION['flash_success'] = 'Abonnement wurde gelöscht.';
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Fehler beim Löschen des Abos: ' . $e->getMessage();
        }

        header('Location: ?route=admin/boating/customers/edit&id=' . $customerId . '&tab=subscriptions');
        exit;
    });

    // Abbuchung rückgängig machen
    $registerPost('customers/subscriptions/refund', static function (): void {
        if (!BoatingSchoolService::canManageSubscriptions()) {
            http_response_code(403);
            die('Zugriff verweigert: Fehlende Berechtigung für Abonnement-Verwaltung.');
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/boating/customers');
            exit;
        }

        $usageId = (int) ($_POST['usage_id'] ?? 0);
        $customerId = (int) ($_POST['customer_id'] ?? 0);

        try {
            CustomerService::deleteSubscriptionUsage($usageId);
            $_SESSION['flash_success'] = 'Abbuchung wurde storniert und die Einheit(en) gutgeschrieben.';
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Fehler beim Stornieren der Abbuchung: ' . $e->getMessage();
        }

        header('Location: ?route=admin/boating/customers/edit&id=' . $customerId . '&tab=subscriptions');
        exit;
    });

    // =========================================================================
    // 5. KALENDER & TERMIN-DISPOSITION (ADMIN)
    // =========================================================================

    $registerGet('calendar', static function () use ($checkPermission): void {
        $checkPermission(false);

        $viewMode = !empty($_GET['view']) ? trim((string) $_GET['view']) : 'week';
        $selectedDate = !empty($_GET['date']) ? trim((string) $_GET['date']) : date('Y-m-d');

        $filters = [];
        if (!empty($_GET['status'])) {
            $filters['status'] = trim((string) $_GET['status']);
        }
        if (!empty($_GET['instructor_id'])) {
            $filters['instructor_id'] = (int) $_GET['instructor_id'];
        }
        if (!empty($_GET['boat_id'])) {
            $filters['boat_id'] = (int) $_GET['boat_id'];
        }

        // Je nach View-Modus Datumsbereich festlegen
        if ($viewMode === 'week') {
            $monday = date('Y-m-d', strtotime('monday this week', strtotime($selectedDate)));
            $sunday = date('Y-m-d', strtotime('sunday this week', strtotime($selectedDate)));
            $filters['date_from'] = $monday;
            $filters['date_to']   = $sunday;
        } elseif ($viewMode === 'day_resources') {
            $filters['date'] = $selectedDate;
        } elseif (!empty($_GET['date'])) {
            $filters['date'] = $selectedDate;
        }

        $appointments = AppointmentService::getAll($filters, 500);
        $instructors  = InstructorService::getAll(['is_active' => 1]);
        $boats        = BoatService::getAll(['is_active' => 1]);
        $services     = ServiceService::getAll(['is_active' => 1]);
        $customers    = CustomerService::getAll(['is_active' => 1], 500);
        $locations    = LocationService::getAll(true);

        require __DIR__ . '/views/admin/calendar.php';
    });

    $registerPost('appointments/create', static function () use ($checkPermission): void {
        $checkPermission(true);

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/boating/calendar');
            exit;
        }

        $serviceId    = (int) ($_POST['service_id'] ?? 0);
        $customerId   = (int) ($_POST['customer_id'] ?? 0);
        $date         = trim((string) ($_POST['date'] ?? ''));
        $startTime    = trim((string) ($_POST['start_time'] ?? ''));
        $endTime      = trim((string) ($_POST['end_time'] ?? ''));
        $instructorId = !empty($_POST['instructor_id']) ? (int) $_POST['instructor_id'] : null;
        $boatId       = !empty($_POST['boat_id']) ? (int) $_POST['boat_id'] : null;
        $locationId   = !empty($_POST['location_id']) ? (int) $_POST['location_id'] : null;
        $status       = trim((string) ($_POST['status'] ?? 'confirmed'));
        $price        = !empty($_POST['price']) ? (float) $_POST['price'] : 0.00;
        $notes        = trim((string) ($_POST['notes'] ?? ''));

        if ($serviceId <= 0 || $customerId <= 0 || $date === '' || $startTime === '' || $endTime === '') {
            $_SESSION['flash_error'] = 'Bitte füllen Sie Service, Kunde, Datum und Uhrzeiten aus.';
            header('Location: ?route=admin/boating/calendar');
            exit;
        }

        // Konfliktprüfung (inkl. Standortbezug und Fahrlehrer-Verfügbarkeit)
        $conflict = AppointmentService::checkConflicts($date, $startTime, $endTime, $boatId, $instructorId, $customerId, null, $locationId);
        if ($conflict['has_conflict']) {
            $_SESSION['flash_error'] = 'Konflikt: ' . $conflict['reason'];
            header('Location: ?route=admin/boating/calendar');
            exit;
        }

        try {
            $appointmentId = AppointmentService::create([
                'date'          => $date,
                'start_time'    => $startTime . (strlen($startTime) === 5 ? ':00' : ''),
                'end_time'      => $endTime . (strlen($endTime) === 5 ? ':00' : ''),
                'service_id'    => $serviceId,
                'customer_id'   => $customerId,
                'instructor_id' => $instructorId,
                'boat_id'       => $boatId,
                'location_id'   => $locationId,
                'status'        => $status,
                'price'         => $price,
                'notes'         => $notes,
            ]);

            BoatingSchoolService::log('appointment_created', ['appointment_id' => $appointmentId, 'date' => $date]);

            // E-Mail-Benachrichtigungen versenden
            try {
                BoatingNotificationService::sendBookingCreatedNotifications($appointmentId);
            } catch (\Throwable $e) {
                error_log('Fehler beim E-Mail-Versand (Admin Neuer Termin): ' . $e->getMessage());
            }

            $_SESSION['flash_success'] = 'Termin wurde erfolgreich im Kalender angelegt.';
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Fehler beim Anlegen: ' . $e->getMessage();
        }

        header('Location: ?route=admin/boating/calendar');
        exit;
    });

    $registerPost('appointments/status', static function () use ($checkPermission): void {
        $checkPermission(true);

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/boating/calendar');
            exit;
        }

        $id     = (int) ($_POST['id'] ?? 0);
        $status = trim((string) ($_POST['status'] ?? ''));

        if ($id > 0 && in_array($status, ['requested', 'confirmed', 'cancelled_customer', 'cancelled_system', 'completed'], true)) {
            $prevApp = AppointmentService::find($id);
            AppointmentService::updateStatus($id, $status);
            BoatingSchoolService::log('appointment_status_changed', ['appointment_id' => $id, 'new_status' => $status]);

            // E-Mail-Benachrichtigungen versenden (Stornierung oder Bestätigung)
            try {
                if (in_array($status, ['cancelled_customer', 'cancelled_system'], true)) {
                    BoatingNotificationService::sendBookingCancelledNotifications($id, 'admin');
                } elseif ($status === 'confirmed' && ($prevApp['status'] ?? '') !== 'confirmed') {
                    BoatingNotificationService::sendBookingUpdatedNotifications($id, ['status' => 'confirmed']);
                }
            } catch (\Throwable $e) {
                error_log('Fehler beim E-Mail-Versand (Statuswechsel): ' . $e->getMessage());
            }

            $_SESSION['flash_success'] = 'Status des Termins wurde aktualisiert.';
        }

        header('Location: ?route=admin/boating/calendar');
        exit;
    });

    $registerPost('appointments/delete', static function () use ($checkPermission): void {
        $checkPermission(true);

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/boating/calendar');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            BoatingSchoolRepository::deleteAppointment($id);
            BoatingSchoolService::log('appointment_deleted', ['appointment_id' => $id]);
            $_SESSION['flash_success'] = 'Termin wurde gelöscht.';
        }

        header('Location: ?route=admin/boating/calendar');
        exit;
    });

    // =========================================================================
    // 5b. AJAX-ENDPUNKTE FÜR DRAG & DROP KALENDER (MOVE & DUPLICATE)
    // =========================================================================

    /**
     * Ajax-Endpunkt für Verschieben oder Duplizieren (Copy-Modus) per Drag&Drop
     * Beachtet Berechtigung boating.calendar.manage, prüft CSRF und führt strikte
     * Konfliktprüfung durch.
     */
    $calendarMoveHandler = static function (): void {
        header('Content-Type: application/json; charset=utf-8');

        // Berechtigungsprüfung (boating.calendar.manage)
        if (!BoatingSchoolService::canManageCalendar()) {
            http_response_code(403);
            echo json_encode([
                'success'    => false,
                'error_code' => 'FORBIDDEN',
                'message'    => 'Fehlende Berechtigung für die Termin-Disposition (boating.calendar.manage erforderlich).',
            ], JSON_THROW_ON_ERROR);
            exit;
        }

        // Payload aus $_POST oder JSON-Body lesen
        $raw = file_get_contents('php://input');
        $payload = $_POST;
        if (!empty($raw)) {
            $json = json_decode($raw, true);
            if (is_array($json)) {
                $payload = array_merge($payload, $json);
            }
        }

        // CSRF-Prüfung
        $token = $payload['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        if (class_exists('Csrf') && !Csrf::validate(is_string($token) ? $token : null)) {
            http_response_code(403);
            echo json_encode([
                'success'    => false,
                'error_code' => 'CSRF_INVALID',
                'message'    => 'Ungültiges oder abgelaufenes CSRF-Sicherheitstoken. Bitte Seite neu laden.',
            ], JSON_THROW_ON_ERROR);
            exit;
        }

        $appointmentId = (int) ($payload['appointment_id'] ?? 0);
        $newDate       = trim((string) ($payload['new_date'] ?? ''));
        $newStartTime  = trim((string) ($payload['new_start_time'] ?? ''));
        $newEndTime    = !empty($payload['new_end_time']) ? trim((string) $payload['new_end_time']) : null;

        $newInstructorId = isset($payload['new_instructor_id']) && $payload['new_instructor_id'] !== '' && $payload['new_instructor_id'] !== 'null'
            ? (int) $payload['new_instructor_id']
            : null;
        $newBoatId = isset($payload['new_boat_id']) && $payload['new_boat_id'] !== '' && $payload['new_boat_id'] !== 'null'
            ? (int) $payload['new_boat_id']
            : null;
        $newLocationId = isset($payload['new_location_id']) && $payload['new_location_id'] !== '' && $payload['new_location_id'] !== 'null'
            ? (int) $payload['new_location_id']
            : null;

        $isCopy = !empty($payload['is_copy']) && (
            $payload['is_copy'] === true ||
            $payload['is_copy'] === 'true' ||
            $payload['is_copy'] === 1 ||
            $payload['is_copy'] === '1'
        );

        if ($appointmentId <= 0 || $newDate === '' || $newStartTime === '') {
            http_response_code(400);
            echo json_encode([
                'success'    => false,
                'error_code' => 'INVALID_INPUT',
                'message'    => 'Ungültige Parameter: appointment_id, new_date und new_start_time sind erforderlich.',
            ], JSON_THROW_ON_ERROR);
            exit;
        }

        try {
            $res = AppointmentService::handleMoveOrCopy(
                $appointmentId,
                $newDate,
                $newStartTime,
                $newEndTime,
                $newInstructorId,
                $newBoatId,
                $newLocationId,
                $isCopy
            );

            if (!$res['success']) {
                $status = ($res['error_code'] === 'CONFLICT') ? 409 : 400;
                http_response_code($status);
                echo json_encode($res, JSON_THROW_ON_ERROR);
                exit;
            }

            // Activity Log
            BoatingSchoolService::log($isCopy ? 'appointment_copied' : 'appointment_moved', [
                'appointment_id' => $res['appointment_id'],
                'source_id'      => $appointmentId,
                'new_date'       => $newDate,
                'new_start_time' => $newStartTime,
                'mode'           => $res['mode'],
            ]);

            // E-Mail-Benachrichtigungen versenden (Kunde/Fahrlehrer)
            try {
                if ($isCopy && !empty($res['appointment_id'])) {
                    BoatingNotificationService::sendBookingCreatedNotifications((int) $res['appointment_id']);
                } elseif (!empty($res['appointment_id'])) {
                    BoatingNotificationService::sendBookingUpdatedNotifications((int) $res['appointment_id'], [
                        'new_date'       => $newDate,
                        'new_start_time' => $newStartTime,
                    ]);
                }
            } catch (\Throwable $e) {
                error_log('Fehler beim E-Mail-Versand (Kalender Move/Copy): ' . $e->getMessage());
            }

            http_response_code(200);
            echo json_encode($res, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'success'    => false,
                'error_code' => 'SERVER_ERROR',
                'message'    => 'Interner Serverfehler: ' . $e->getMessage(),
            ], JSON_THROW_ON_ERROR);
        }
        exit;
    };

    $registerPost('calendar/move', $calendarMoveHandler);
    $registerPost('appointments/move', $calendarMoveHandler);

    // GET /admin/boating/calendar/events (JSON-Abruf aller Termine für Kalender-Refresh)
    $calendarEventsHandler = static function (): void {
        header('Content-Type: application/json; charset=utf-8');

        if (!BoatingSchoolService::canViewCalendar()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Forbidden']);
            exit;
        }

        $filters = [];
        if (!empty($_GET['date'])) {
            $filters['date'] = trim((string) $_GET['date']);
        }
        if (!empty($_GET['status'])) {
            $filters['status'] = trim((string) $_GET['status']);
        }
        if (!empty($_GET['instructor_id'])) {
            $filters['instructor_id'] = (int) $_GET['instructor_id'];
        }

        $appointments = AppointmentService::getAll($filters, 500);
        echo json_encode(['success' => true, 'appointments' => $appointments], JSON_THROW_ON_ERROR);
        exit;
    };

    $registerGet('calendar/events', $calendarEventsHandler);
    $registerGet('appointments/events', $calendarEventsHandler);

    // =========================================================================
    // 5c. E-MAIL-KOMMUNIKATION & EINSTELLUNGEN
    // =========================================================================

    // GET /admin/boating/settings
    $settingsGetHandler = static function () use ($checkPermission): void {
        $checkPermission(false);
        if (!BoatingSchoolService::canManageSettings()) {
            $_SESSION['flash_error'] = 'Sie verfügen nicht über ausreichende Berechtigungen für die E-Mail-Einstellungen.';
            header('Location: ?route=admin/boating');
            exit;
        }

        BoatingNotificationService::ensureSettings();
        $settings = BoatingNotificationService::getAllSettings();
        $canManage = true;

        require __DIR__ . '/views/admin/settings.php';
    };
    $registerGet('settings', $settingsGetHandler);

    // POST /admin/boating/settings (Speichern)
    $settingsPostHandler = static function () use ($checkPermission): void {
        $checkPermission(true);
        if (!BoatingSchoolService::canManageSettings()) {
            $_SESSION['flash_error'] = 'Fehlende Berechtigung für Einstellungen.';
            header('Location: ?route=admin/boating');
            exit;
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/boating/settings');
            exit;
        }

        try {
            BoatingNotificationService::saveSettings($_POST);
            BoatingSchoolService::log('notification_settings_updated', []);
            $_SESSION['flash_success'] = 'E-Mail-Einstellungen und Vorlagen wurden erfolgreich gespeichert.';
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Fehler beim Speichern: ' . $e->getMessage();
        }

        $tab = !empty($_POST['active_tab']) ? urlencode(trim((string) $_POST['active_tab'])) : 'general';
        header('Location: ?route=admin/boating/settings&tab=' . $tab);
        exit;
    };
    $registerPost('settings', $settingsPostHandler);

    // POST /admin/boating/settings/test-mail (Test-E-Mail versenden)
    $testMailHandler = static function () use ($checkPermission): void {
        $checkPermission(true);
        if (!BoatingSchoolService::canManageSettings()) {
            $_SESSION['flash_error'] = 'Fehlende Berechtigung für E-Mail-Versand.';
            header('Location: ?route=admin/boating/settings');
            exit;
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/boating/settings');
            exit;
        }

        $recipient   = trim((string) ($_POST['test_recipient'] ?? ''));
        $templateKey = trim((string) ($_POST['template_key'] ?? ''));

        $res = BoatingNotificationService::sendTestEmail($recipient, $templateKey);
        if ($res['success']) {
            $_SESSION['flash_success'] = $res['message'];
        } else {
            $_SESSION['flash_error'] = $res['message'];
        }

        header('Location: ?route=admin/boating/settings&tab=general');
        exit;
    };
    $registerPost('settings/test-mail', $testMailHandler);

    // POST & GET /admin/boating/reminders/send (Terminerinnerungs-Cron manuell oder via Webhook ausführen)
    $remindersSendHandler = static function (): void {
        $isFormSubmit = !empty($_POST) && isset($_POST['csrf_token']) && !empty($_SERVER['HTTP_REFERER']);

        $result = BoatingNotificationService::sendUpcomingReminders();

        if ($isFormSubmit) {
            if ($result['success']) {
                $_SESSION['flash_success'] = $result['message'];
            } else {
                $_SESSION['flash_error'] = $result['message'];
            }
            header('Location: ?route=admin/boating/settings&tab=cron');
            exit;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    };
    $registerGet('reminders/send', $remindersSendHandler);
    $registerPost('reminders/send', $remindersSendHandler);

    // =========================================================================
    // 5.5 AUSWERTUNGEN & FAHRLEHRER-PAYROLL
    // =========================================================================

    // GET /admin/boating/reports (Auswertungen & Fahrlehrer-Payroll)
    $reportsIndexHandler = static function () use ($checkPermission): void {
        $checkPermission(false);
        if (!BoatingSchoolService::canViewReports()) {
            $_SESSION['flash_error'] = 'Keine Berechtigung für Berichte und Auswertungen.';
            header('Location: ?route=admin/boating');
            exit;
        }

        $dateFrom = !empty($_GET['date_from']) ? trim((string) $_GET['date_from']) : date('Y-m-01');
        $dateTo   = !empty($_GET['date_to']) ? trim((string) $_GET['date_to']) : date('Y-m-t');
        $instId   = !empty($_GET['instructor_id']) ? (int) $_GET['instructor_id'] : null;
        $pStatus  = !empty($_GET['payout_status']) ? trim((string) $_GET['payout_status']) : null;
        $tab      = !empty($_GET['tab']) ? trim((string) $_GET['tab']) : 'payroll';

        $filters = [
            'date_from'     => $dateFrom,
            'date_to'       => $dateTo,
            'instructor_id' => $instId,
            'payout_status' => $pStatus,
            'tab'           => $tab,
        ];

        // Schemasicherstellung (idempotent)
        BoatingSchoolRepository::ensureCrmSchema();

        $schoolSummary        = InstructorPayoutService::getSchoolReportingSummary($dateFrom, $dateTo);
        $instructorSummaries  = InstructorPayoutService::getInstructorPayrollSummary($dateFrom, $dateTo, $instId);
        
        $appFilters = [
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
        ];
        if ($instId !== null && $instId > 0) {
            $appFilters['instructor_id'] = $instId;
        }
        if ($pStatus !== null && $pStatus !== '') {
            $appFilters['instructor_payout_status'] = $pStatus;
        }
        $detailedAppointments = BoatingSchoolRepository::getAppointments($appFilters, 5000, 0);
        $instructors          = BoatingSchoolRepository::getInstructors(null, 500, 0);
        $canManage            = BoatingSchoolService::canManagePayroll();
        $csrfToken            = class_exists('Csrf') ? Csrf::getToken() : '';

        $payrollSettings = class_exists('CoachService') ? CoachService::getPayrollSettings() : [];
        $tsFilters = [];
        if ($instId !== null && $instId > 0) {
            $tsFilters['instructor_id'] = $instId;
        }
        $coachTimesheets = class_exists('CoachService') ? CoachService::getAllTimesheets($tsFilters) : [];
        $coachAuditLogs  = class_exists('CoachService') ? CoachService::getAuditLogs($tsFilters, 50) : [];

        require __DIR__ . '/views/admin/reports.php';
    };
    $registerGet('reports', $reportsIndexHandler);
    $registerGet('reports/payroll', $reportsIndexHandler);
    $registerGet('payroll', $reportsIndexHandler);

    // GET /admin/boating/reports/export-payroll (CSV-Export Einzeltermine)
    $reportsExportPayrollHandler = static function () use ($checkPermission): void {
        $checkPermission(false);
        if (!BoatingSchoolService::canViewReports()) {
            $_SESSION['flash_error'] = 'Keine Berechtigung für Berichte-Export.';
            header('Location: ?route=admin/boating');
            exit;
        }

        $dateFrom = !empty($_GET['date_from']) ? trim((string) $_GET['date_from']) : date('Y-m-01');
        $dateTo   = !empty($_GET['date_to']) ? trim((string) $_GET['date_to']) : date('Y-m-t');
        $instId   = !empty($_GET['instructor_id']) ? (int) $_GET['instructor_id'] : null;
        $pStatus  = !empty($_GET['payout_status']) ? trim((string) $_GET['payout_status']) : null;
        $markExported = !empty($_GET['mark_as_exported']) && $_GET['mark_as_exported'] === '1';

        $appFilters = [
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
        ];
        if ($instId !== null && $instId > 0) {
            $appFilters['instructor_id'] = $instId;
        }
        if ($pStatus !== null && $pStatus !== '') {
            $appFilters['instructor_payout_status'] = $pStatus;
        }

        $appointments = BoatingSchoolRepository::getAppointments($appFilters, 10000, 0);
        $csv = InstructorPayoutService::exportPayrollCsv($appointments, $markExported);

        $filename = 'Bootschule_Payroll_Detail_' . $dateFrom . '_' . $dateTo . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        echo $csv;
        exit;
    };
    $registerGet('reports/export-payroll', $reportsExportPayrollHandler);

    // GET /admin/boating/reports/export-summary (CSV-Export Fahrlehrer-Zusammenfassung)
    $reportsExportSummaryHandler = static function () use ($checkPermission): void {
        $checkPermission(false);
        if (!BoatingSchoolService::canViewReports()) {
            $_SESSION['flash_error'] = 'Keine Berechtigung für Berichte-Export.';
            header('Location: ?route=admin/boating');
            exit;
        }

        $dateFrom = !empty($_GET['date_from']) ? trim((string) $_GET['date_from']) : date('Y-m-01');
        $dateTo   = !empty($_GET['date_to']) ? trim((string) $_GET['date_to']) : date('Y-m-t');
        $instId   = !empty($_GET['instructor_id']) ? (int) $_GET['instructor_id'] : null;

        $summaries = InstructorPayoutService::getInstructorPayrollSummary($dateFrom, $dateTo, $instId);
        $csv = InstructorPayoutService::exportSummaryCsv($summaries);

        $filename = 'Bootschule_Payroll_Summary_' . $dateFrom . '_' . $dateTo . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        echo $csv;
        exit;
    };
    $registerGet('reports/export-summary', $reportsExportSummaryHandler);

    // POST /admin/boating/reports/update-payout (Einzelhonorar manuell anpassen)
    $updatePayoutHandler = static function () use ($checkPermission): void {
        $checkPermission(true);
        if (!BoatingSchoolService::canManagePayroll()) {
            $_SESSION['flash_error'] = 'Keine Berechtigung zum Bearbeiten der Honorare.';
            header('Location: ?route=admin/boating/reports');
            exit;
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/boating/reports');
            exit;
        }

        $appId  = (int) ($_POST['appointment_id'] ?? 0);
        $amount = (float) ($_POST['payout_amount'] ?? 0.00);
        $status = !empty($_POST['payout_status']) ? trim((string) $_POST['payout_status']) : null;
        $note   = isset($_POST['payout_note']) ? trim((string) $_POST['payout_note']) : null;

        $ok = InstructorPayoutService::updateAppointmentPayout($appId, $amount, $status, $note);
        if ($ok) {
            $_SESSION['flash_success'] = 'Honorar für Termin #' . $appId . ' wurde erfolgreich gespeichert.';
        } else {
            $_SESSION['flash_error'] = 'Fehler beim Aktualisieren des Honorars.';
        }

        $dateFrom = !empty($_POST['date_from']) ? urlencode(trim((string) $_POST['date_from'])) : date('Y-m-01');
        $dateTo   = !empty($_POST['date_to']) ? urlencode(trim((string) $_POST['date_to'])) : date('Y-m-t');
        $tab      = !empty($_POST['tab']) ? urlencode(trim((string) $_POST['tab'])) : 'appointments';
        header("Location: ?route=admin/boating/reports&tab={$tab}&date_from={$dateFrom}&date_to={$dateTo}");
        exit;
    };
    $registerPost('reports/update-payout', $updatePayoutHandler);

    // POST /admin/boating/reports/batch-status (Batch Statusänderung ausgewählter Termine)
    $batchStatusHandler = static function () use ($checkPermission): void {
        $checkPermission(true);
        if (!BoatingSchoolService::canManagePayroll()) {
            $_SESSION['flash_error'] = 'Keine Berechtigung für Batch-Statusänderungen.';
            header('Location: ?route=admin/boating/reports');
            exit;
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/boating/reports');
            exit;
        }

        $rawIds = trim((string) ($_POST['selected_ids'] ?? ''));
        $newStatus = trim((string) ($_POST['new_status'] ?? ''));
        $ids = array_filter(array_map('intval', explode(',', $rawIds)), static fn($i) => $i > 0);

        if (!empty($ids) && in_array($newStatus, ['open', 'exported', 'paid'], true)) {
            $count = InstructorPayoutService::bulkUpdatePayoutStatus($ids, $newStatus);
            $_SESSION['flash_success'] = $count . ' Termin(e) erfolgreich auf Status «' . $newStatus . '» gesetzt.';
        } else {
            $_SESSION['flash_error'] = 'Keine Termine ausgewählt oder ungültiger Status.';
        }

        $dateFrom = !empty($_POST['date_from']) ? urlencode(trim((string) $_POST['date_from'])) : date('Y-m-01');
        $dateTo   = !empty($_POST['date_to']) ? urlencode(trim((string) $_POST['date_to'])) : date('Y-m-t');
        $tab      = !empty($_POST['tab']) ? urlencode(trim((string) $_POST['tab'])) : 'appointments';
        header("Location: ?route=admin/boating/reports&tab={$tab}&date_from={$dateFrom}&date_to={$dateTo}");
        exit;
    };
    $registerPost('reports/batch-status', $batchStatusHandler);

    // POST /admin/boating/reports/recalculate (Bulk Neuberechnung)
    $recalculateHandler = static function () use ($checkPermission): void {
        $checkPermission(true);
        if (!BoatingSchoolService::canManagePayroll()) {
            $_SESSION['flash_error'] = 'Keine Berechtigung zur Neuberechnung.';
            header('Location: ?route=admin/boating/reports');
            exit;
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/boating/reports');
            exit;
        }

        $dateFrom = !empty($_POST['date_from']) ? trim((string) $_POST['date_from']) : date('Y-m-01');
        $dateTo   = !empty($_POST['date_to']) ? trim((string) $_POST['date_to']) : date('Y-m-t');
        $instId   = !empty($_POST['instructor_id']) ? (int) $_POST['instructor_id'] : null;
        $onlyOpen = !empty($_POST['only_open']);

        $updated = InstructorPayoutService::bulkRecalculate($dateFrom, $dateTo, $instId, $onlyOpen);
        $_SESSION['flash_success'] = 'Honorare erfolgreich neu berechnet (' . $updated . ' Termine aktualisiert).';

        header("Location: ?route=admin/boating/reports&date_from=" . urlencode($dateFrom) . "&date_to=" . urlencode($dateTo));
        exit;
    };
    $registerPost('reports/recalculate', $recalculateHandler);

    // =========================================================================
    // 5.6 FAHRLEHRER-PORTAL & MONATSABSCHLUSS
    // =========================================================================

    // GET /admin/boating/instructor-portal
    $instructorPortalGetHandler = static function () use ($checkPermission): void {
        if (!class_exists('Auth') || !Auth::check()) {
            $_SESSION['flash_error'] = 'Bitte melden Sie sich an, um diesen Bereich zu betreten.';
            header('Location: ?route=login');
            exit;
        }

        if (!BoatingSchoolService::canAccessInstructorPortal()) {
            $_SESSION['flash_error'] = 'Sie verfügen über kein verknüpftes Fahrlehrer-Profil.';
            header('Location: ?route=admin/dashboard');
            exit;
        }

        $canSwitchInstructor = BoatingSchoolService::canManage();
        $currentUser = BoatingSchoolService::getCurrentUser();
        $currentInst = BoatingSchoolService::getCurrentInstructor();
        $instructor = null;

        // Security (Punkt 7): Falls kein Admin mit Switch-Rechten, strikt das eigene Coach-Profil erzwingen
        if ($canSwitchInstructor && !empty($_GET['instructor_id'])) {
            $instructor = InstructorService::find((int) $_GET['instructor_id']);
        }

        if (!$instructor || !$canSwitchInstructor) {
            $instructor = $currentInst;
        }

        $allInstructors = $canSwitchInstructor ? BoatingSchoolRepository::getInstructors(['is_active' => 1], 100, 0) : ($instructor ? [$instructor] : []);

        if ($canSwitchInstructor && empty($allInstructors)) {
            $allInstructors = BoatingSchoolRepository::getInstructors(null, 100, 0);
        }

        if (!$instructor && $canSwitchInstructor && !empty($allInstructors[0])) {
            $instructor = $allInstructors[0];
        }

        $selectedMonth = !empty($_GET['month']) && preg_match('/^\d{4}-\d{2}$/', trim((string) $_GET['month']))
            ? trim((string) $_GET['month'])
            : date('Y-m');

        $monthStats = [
            'total_appointments'     => 0,
            'completed_appointments' => 0,
            'total_hours'            => 0.0,
            'open_amount'            => 0.0,
            'exported_amount'        => 0.0,
            'paid_amount'            => 0.0,
            'total_amount'           => 0.0,
            'open_count'             => 0,
        ];

        $monthAppointments = [];
        $upcomingAppointments = [];
        $coachAvailabilities = [];
        $coachWorkingHours = [];
        $timesheetSummary = [];
        $weeklyTemplates = [];
        $locations = class_exists('LocationService') ? LocationService::getAll(true) : [];
        $payrollSettings = class_exists('CoachService') ? CoachService::getPayrollSettings() : [];
        $canApproveTimesheets = class_exists('Rbac') ? (Rbac::can('boating.timesheets.approve') || Rbac::can('boating.payroll.manage') || Rbac::can('boating_school.manage')) : true;

        if ($instructor) {
            $instId = (int) $instructor['id'];
            $fromDate = $selectedMonth . '-01';
            $toDate   = date('Y-m-t', strtotime($fromDate));

            $monthAppointments = BoatingSchoolRepository::getAppointments([
                'instructor_id' => $instId,
                'date_from'     => $fromDate,
                'date_to'       => $toDate,
            ], 1000, 0);

            // Sortieren nach Datum aufsteigend
            usort($monthAppointments, static function (array $a, array $b): int {
                $c = strcmp((string) ($a['date'] ?? ''), (string) ($b['date'] ?? ''));
                return $c !== 0 ? $c : strcmp((string) ($a['start_time'] ?? ''), (string) ($b['start_time'] ?? ''));
            });

            $today = date('Y-m-d');
            $upcomingAppointments = BoatingSchoolRepository::getAppointments([
                'instructor_id' => $instId,
                'date_from'     => $today,
            ], 500, 0);

            usort($upcomingAppointments, static function (array $a, array $b): int {
                $c = strcmp((string) ($a['date'] ?? ''), (string) ($b['date'] ?? ''));
                return $c !== 0 ? $c : strcmp((string) ($a['start_time'] ?? ''), (string) ($b['start_time'] ?? ''));
            });

            // Kennzahlen berechnen
            $totalMinutes = 0;
            foreach ($monthAppointments as $app) {
                $monthStats['total_appointments']++;
                if (($app['status'] ?? '') === 'completed') {
                    $monthStats['completed_appointments']++;
                }

                $durationMin = InstructorPayoutService::resolveDurationMinutes($app);
                $totalMinutes += $durationMin;

                $payout = (float) ($app['instructor_payout_amount'] ?? 0.00);
                $status = (string) ($app['instructor_payout_status'] ?? 'open');

                $monthStats['total_amount'] += $payout;

                if ($status === 'open') {
                    $monthStats['open_amount'] += $payout;
                    $monthStats['open_count']++;
                } elseif ($status === 'exported') {
                    $monthStats['exported_amount'] += $payout;
                } elseif ($status === 'paid') {
                    $monthStats['paid_amount'] += $payout;
                }
            }

            $monthStats['total_hours'] = round($totalMinutes / 60.0, 1);

            // Coach-Modul: Verfügbarkeiten, Zeiterfassung, Timesheet & Wochen-Templates
            if (class_exists('CoachService')) {
                $coachAvailabilities = CoachService::getAvailabilities([
                    'instructor_id' => $instId,
                    'date_from'     => $fromDate,
                    'date_to'       => $toDate,
                ]);
                $coachWorkingHours = CoachService::getWorkingHours([
                    'instructor_id' => $instId,
                    'date_from'     => $fromDate,
                    'date_to'       => $toDate,
                ]);
                $timesheetSummary = CoachService::getMonthlyTimesheetSummary($instId, $selectedMonth);
            }

            if (class_exists('CoachAvailabilityService')) {
                $weeklyTemplates = CoachAvailabilityService::getWeeklyTemplates($instId);
            }
        }

        $locations = class_exists('LocationService') ? LocationService::getAll(true) : BoatingSchoolRepository::getLocations(true);
        $csrfToken = class_exists('Csrf') ? Csrf::getToken() : '';
        require __DIR__ . '/views/admin/instructor_portal.php';
    };
    $registerGet('instructor-portal', $instructorPortalGetHandler);
    $registerGet('instructor-dashboard', $instructorPortalGetHandler);
    $registerGet('my-dashboard', $instructorPortalGetHandler);
    $registerGet('coach', $instructorPortalGetHandler);

    // POST /admin/boating/instructor-portal/close-month & submit-month (Monatsabschluss / Timesheet einreichen)
    $instructorSubmitMonthPostHandler = static function (): void {
        if (!class_exists('Auth') || !Auth::check()) {
            $_SESSION['flash_error'] = 'Bitte anmelden.';
            header('Location: ?route=login');
            exit;
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/boating/instructor-portal');
            exit;
        }

        $canManage = BoatingSchoolService::canManage();
        $currentInst = BoatingSchoolService::getCurrentInstructor();
        $currentUser = BoatingSchoolService::getCurrentUser();
        $userId = $currentUser['id'] ?? null;

        // Security (Punkt 7): Non-Admins können nur ihr eigenes Profil einreichen
        $instructorId = (int) ($_POST['instructor_id'] ?? 0);
        if (!$canManage || $instructorId <= 0) {
            if (!$currentInst) {
                $_SESSION['flash_error'] = 'Kein Fahrlehrer-Profil zugewiesen.';
                header('Location: ?route=admin/boating/instructor-portal');
                exit;
            }
            $instructorId = (int) $currentInst['id'];
        }

        $month = trim((string) ($_POST['month'] ?? date('Y-m')));

        // 1. Timesheet mit Snapshot & Versionierung einreichen
        if (class_exists('CoachService')) {
            $tsRes = CoachService::submitMonthTimesheet($instructorId, $month, $userId ? (int) $userId : null);
            if (!empty($tsRes['success'])) {
                $_SESSION['flash_success'] = $tsRes['message'];
            }
        }

        // 2. Termine auf 'exported' setzen
        $result = InstructorPayoutService::closeMonthForInstructor($instructorId, $month);
        if ($result['count'] > 0 && empty($_SESSION['flash_success'])) {
            $_SESSION['flash_success'] = 'Monatsabschluss erfolgreich: ' . $result['count'] . ' Termin(e) im Betrag von CHF '
                . number_format((float) $result['total_amount'], 2, '.', "'")
                . ' wurden an die Administration zur Lohnabrechnung übergeben.';
        }

        $redirectUrl = '?route=admin/boating/instructor-portal&month=' . urlencode($month);
        if ($canManage) {
            $redirectUrl .= '&instructor_id=' . $instructorId;
        }
        header('Location: ' . $redirectUrl);
        exit;
    };
    $registerPost('instructor-portal/close-month', $instructorSubmitMonthPostHandler);
    $registerPost('instructor-dashboard/close-month', $instructorSubmitMonthPostHandler);
    $registerPost('instructor-portal/submit-month', $instructorSubmitMonthPostHandler);
    $registerPost('instructor-portal/submit-timesheet', $instructorSubmitMonthPostHandler);

    // POST /admin/boating/instructor-portal/add-availability (Coach Absenz eintragen mit location_id)
    $registerPost('instructor-portal/add-availability', static function (): void {
        if (!class_exists('Auth') || !Auth::check()) {
            $_SESSION['flash_error'] = 'Bitte anmelden.';
            header('Location: ?route=login');
            exit;
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges CSRF-Token.';
            header('Location: ?route=admin/boating/instructor-portal');
            exit;
        }

        $canManage = BoatingSchoolService::canManage();
        $currentInst = BoatingSchoolService::getCurrentInstructor();
        $postData = $_POST;

        // Security: Non-Admins dürfen nur für sich selbst eintragen
        $instructorId = (int) ($postData['instructor_id'] ?? 0);
        if (!$canManage || $instructorId <= 0) {
            if (!$currentInst) {
                $_SESSION['flash_error'] = 'Kein Fahrlehrer-Profil zugewiesen.';
                header('Location: ?route=admin/boating/instructor-portal');
                exit;
            }
            $instructorId = (int) $currentInst['id'];
            $postData['instructor_id'] = $instructorId;
        }

        $month = trim((string) ($postData['month'] ?? date('Y-m')));

        if (class_exists('CoachService')) {
            $availId = CoachService::addAvailability($postData);
            if ($availId > 0) {
                $_SESSION['flash_success'] = 'Absenz / Verfügbarkeit erfolgreich erfasst.';
            } else {
                $_SESSION['flash_error'] = 'Fehler beim Speichern der Absenz.';
            }
        }

        header('Location: ?route=admin/boating/instructor-portal&month=' . urlencode($month) . '&instructor_id=' . $instructorId);
        exit;
    });

    // POST /admin/boating/instructor-portal/delete-availability (Absenz löschen)
    $registerPost('instructor-portal/delete-availability', static function (): void {
        if (!class_exists('Auth') || !Auth::check()) {
            header('Location: ?route=login');
            exit;
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges CSRF-Token.';
            header('Location: ?route=admin/boating/instructor-portal');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $month = trim((string) ($_POST['month'] ?? date('Y-m')));
        $instructorId = (int) ($_POST['instructor_id'] ?? 0);

        if (class_exists('CoachService') && $id > 0) {
            CoachService::deleteAvailability($id);
            $_SESSION['flash_success'] = 'Eintrag wurde gelöscht.';
        }

        header('Location: ?route=admin/boating/instructor-portal&month=' . urlencode($month) . '&instructor_id=' . $instructorId);
        exit;
    });

    // POST /admin/boating/instructor-portal/add-timesheet (Coach Arbeitszeit eintragen)
    $registerPost('instructor-portal/add-timesheet', static function (): void {
        if (!class_exists('Auth') || !Auth::check()) {
            $_SESSION['flash_error'] = 'Bitte anmelden.';
            header('Location: ?route=login');
            exit;
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges CSRF-Token.';
            header('Location: ?route=admin/boating/instructor-portal');
            exit;
        }

        $canManage = BoatingSchoolService::canManage();
        $currentInst = BoatingSchoolService::getCurrentInstructor();
        $postData = $_POST;

        $instructorId = (int) ($postData['instructor_id'] ?? 0);
        if (!$canManage || $instructorId <= 0) {
            if (!$currentInst) {
                $_SESSION['flash_error'] = 'Kein Fahrlehrer-Profil zugewiesen.';
                header('Location: ?route=admin/boating/instructor-portal');
                exit;
            }
            $instructorId = (int) $currentInst['id'];
            $postData['instructor_id'] = $instructorId;
        }

        $month = trim((string) ($postData['month'] ?? date('Y-m')));

        if (class_exists('CoachService')) {
            $id = CoachService::logWorkingHours($postData);
            if ($id > 0) {
                $_SESSION['flash_success'] = 'Arbeitszeit erfolgreich erfasst.';
            } else {
                $_SESSION['flash_error'] = 'Fehler beim Erfassen der Arbeitszeit.';
            }
        }

        header('Location: ?route=admin/boating/instructor-portal&month=' . urlencode($month) . '&instructor_id=' . $instructorId);
        exit;
    });

    // POST /admin/boating/instructor-portal/weekly-templates/save
    $registerPost('instructor-portal/weekly-templates/save', static function (): void {
        if (!class_exists('Auth') || !Auth::check()) {
            header('Location: ?route=login');
            exit;
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges CSRF-Token.';
            header('Location: ?route=admin/boating/instructor-portal');
            exit;
        }

        $canManage = BoatingSchoolService::canManage();
        $currentInst = BoatingSchoolService::getCurrentInstructor();
        $instructorId = (int) ($_POST['instructor_id'] ?? 0);
        if (!$canManage || $instructorId <= 0) {
            $instructorId = (int) ($currentInst['id'] ?? 0);
        }

        if ($instructorId > 0 && class_exists('CoachAvailabilityService')) {
            CoachAvailabilityService::saveWeeklyTemplate($instructorId, $_POST);
            $_SESSION['flash_success'] = 'Wochen-Template erfolgreich gespeichert.';
        }

        $month = trim((string) ($_POST['month'] ?? date('Y-m')));
        header('Location: ?route=admin/boating/instructor-portal&month=' . urlencode($month) . '&instructor_id=' . $instructorId);
        exit;
    });

    // POST /admin/boating/instructor-portal/weekly-templates/delete
    $registerPost('instructor-portal/weekly-templates/delete', static function (): void {
        if (!class_exists('Auth') || !Auth::check()) {
            header('Location: ?route=login');
            exit;
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges CSRF-Token.';
            header('Location: ?route=admin/boating/instructor-portal');
            exit;
        }

        $canManage = BoatingSchoolService::canManage();
        $currentInst = BoatingSchoolService::getCurrentInstructor();
        $instructorId = (int) ($_POST['instructor_id'] ?? 0);
        if (!$canManage || $instructorId <= 0) {
            $instructorId = (int) ($currentInst['id'] ?? 0);
        }
        $tplId = (int) ($_POST['id'] ?? 0);

        if ($tplId > 0 && $instructorId > 0 && class_exists('CoachAvailabilityService')) {
            CoachAvailabilityService::deleteWeeklyTemplate($tplId, $instructorId);
            $_SESSION['flash_success'] = 'Wochen-Template wurde gelöscht.';
        }

        $month = trim((string) ($_POST['month'] ?? date('Y-m')));
        header('Location: ?route=admin/boating/instructor-portal&month=' . urlencode($month) . '&instructor_id=' . $instructorId);
        exit;
    });

    // POST /admin/boating/timesheet/approve (Admin genehmigt Monatsrapport)
    $registerPost('timesheet/approve', static function (): void {
        if (!BoatingSchoolService::canManagePayroll() && !(class_exists('Rbac') && Rbac::can('boating.timesheets.approve'))) {
            $_SESSION['flash_error'] = 'Keine Berechtigung zum Genehmigen von Monatsrapporten.';
            header('Location: ?route=admin/boating/reports');
            exit;
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges CSRF-Token.';
            header('Location: ?route=admin/boating/reports');
            exit;
        }

        $instructorId = (int) ($_POST['instructor_id'] ?? 0);
        $month = trim((string) ($_POST['month'] ?? ''));
        $currentUser = BoatingSchoolService::getCurrentUser();
        $userId = $currentUser['id'] ?? null;

        if (class_exists('CoachService') && $instructorId > 0 && $month !== '') {
            $res = CoachService::approveMonthTimesheet($instructorId, $month, $userId ? (int) $userId : null);
            $_SESSION[$res['success'] ? 'flash_success' : 'flash_error'] = $res['message'];
        }

        $back = !empty($_POST['redirect']) ? (string) $_POST['redirect'] : '?route=admin/boating/reports&tab=payroll';
        header('Location: ' . $back);
        exit;
    });

    // POST /admin/boating/timesheet/reject (Admin weist Monatsrapport mit Pflichtbegründung zurück)
    $registerPost('timesheet/reject', static function (): void {
        if (!BoatingSchoolService::canManagePayroll() && !(class_exists('Rbac') && Rbac::can('boating.timesheets.approve'))) {
            $_SESSION['flash_error'] = 'Keine Berechtigung zum Zurückweisen von Monatsrapporten.';
            header('Location: ?route=admin/boating/reports');
            exit;
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges CSRF-Token.';
            header('Location: ?route=admin/boating/reports');
            exit;
        }

        $instructorId = (int) ($_POST['instructor_id'] ?? 0);
        $month = trim((string) ($_POST['month'] ?? ''));
        $reason = trim((string) ($_POST['rejection_reason'] ?? ''));
        $currentUser = BoatingSchoolService::getCurrentUser();
        $userId = $currentUser['id'] ?? null;

        if ($reason === '') {
            $_SESSION['flash_error'] = 'Bitte geben Sie eine Begründung für die Zurückweisung an (Pflichtfeld).';
        } elseif (class_exists('CoachService') && $instructorId > 0 && $month !== '') {
            $res = CoachService::rejectMonthTimesheet($instructorId, $month, $reason, $userId ? (int) $userId : null);
            $_SESSION[$res['success'] ? 'flash_success' : 'flash_error'] = $res['message'];
        }

        $back = !empty($_POST['redirect']) ? (string) $_POST['redirect'] : '?route=admin/boating/reports&tab=payroll';
        header('Location: ' . $back);
        exit;
    });

    // POST /admin/boating/timesheet/reopen (Admin öffnet genehmigten Monatsrapport wieder -> reopened)
    $registerPost('timesheet/reopen', static function (): void {
        if (!BoatingSchoolService::canManagePayroll() && !(class_exists('Rbac') && Rbac::can('boating.timesheets.approve'))) {
            $_SESSION['flash_error'] = 'Keine Berechtigung zum Wiedereröffnen von Monatsrapporten.';
            header('Location: ?route=admin/boating/reports');
            exit;
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges CSRF-Token.';
            header('Location: ?route=admin/boating/reports');
            exit;
        }

        $instructorId = (int) ($_POST['instructor_id'] ?? 0);
        $month = trim((string) ($_POST['month'] ?? ''));
        $currentUser = BoatingSchoolService::getCurrentUser();
        $userId = $currentUser['id'] ?? null;

        if (class_exists('CoachService') && $instructorId > 0 && $month !== '') {
            $res = CoachService::reopenMonthTimesheet($instructorId, $month, $userId ? (int) $userId : null);
            $_SESSION[$res['success'] ? 'flash_success' : 'flash_error'] = $res['message'];
        }

        $back = !empty($_POST['redirect']) ? (string) $_POST['redirect'] : '?route=admin/boating/reports&tab=payroll';
        header('Location: ' . $back);
        exit;
    });

    // POST /admin/boating/payroll/settings (Zentrale Schweizer Lohnabzugssätze speichern)
    $registerPost('payroll/settings', static function (): void {
        if (!BoatingSchoolService::canManagePayroll()) {
            $_SESSION['flash_error'] = 'Keine Berechtigung zum Ändern der Lohnabzugssätze.';
            header('Location: ?route=admin/boating/reports');
            exit;
        }

        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges CSRF-Token.';
            header('Location: ?route=admin/boating/reports');
            exit;
        }

        if (class_exists('CoachService')) {
            $ok = CoachService::savePayrollSettings($_POST);
            if ($ok) {
                $_SESSION['flash_success'] = 'Schweizer Lohnabzugssätze wurden erfolgreich gespeichert.';
            } else {
                $_SESSION['flash_error'] = 'Fehler beim Speichern der Abzugssätze.';
            }
        }

        header('Location: ?route=admin/boating/reports&tab=payroll');
        exit;
    });

    // =========================================================================
    // 6. KUNDEN-BUCHUNGSPORTAL (ÖFFENTLICH / FRONTEND-ROUTEN)
    // =========================================================================

    // GET /bootschule (Portal Startseite)
    $portalStartHandler = static function (): void {
        $featuredServices = ServiceService::getAll(['is_active' => 1], 6);
        $boats = BoatService::getAll(['is_active' => 1]);
        $instructors = InstructorService::getAll(['is_active' => 1]);
        require __DIR__ . '/views/portal/start.php';
    };
    $router->get('/bootschule', $portalStartHandler);
    $router->get('bootschule', $portalStartHandler);

    // GET /bootschule/services (Leistungen & Tarife)
    $portalServicesHandler = static function (): void {
        $selectedCategory = !empty($_GET['category']) ? trim((string) $_GET['category']) : null;
        $allServices = ServiceService::getAll(['is_active' => 1], 200);

        $categoryLabels = ServiceService::CATEGORIES;
        $groupedServices = [];
        foreach ($categoryLabels as $catKey => $catLabel) {
            $groupedServices[$catKey] = [];
        }

        foreach ($allServices as $srv) {
            $cat = (string) ($srv['category'] ?? 'other');
            if (!isset($groupedServices[$cat])) {
                $groupedServices[$cat] = [];
            }
            if ($selectedCategory === null || $selectedCategory === $cat) {
                $groupedServices[$cat][] = $srv;
            }
        }

        require __DIR__ . '/views/portal/services.php';
    };
    $router->get('/bootschule/services', $portalServicesHandler);
    $router->get('bootschule/services', $portalServicesHandler);

    // GET /bootschule/book (Buchungsstart)
    $portalBookGetHandler = static function (): void {
        $services = ServiceService::getAll(['is_active' => 1], 100);
        $locations = LocationService::getAll(true);

        $selectedServiceId = !empty($_GET['service_id']) ? (int) $_GET['service_id'] : null;
        if ($selectedServiceId === null && !empty($services[0]['id'])) {
            $selectedServiceId = (int) $services[0]['id'];
        }

        $selectedDate = !empty($_GET['date']) ? trim((string) $_GET['date']) : date('Y-m-d', strtotime('+1 day'));
        $selectedLocationId = !empty($_GET['location_id']) ? (int) $_GET['location_id'] : null;

        $selectedSlotStart = !empty($_GET['slot_start']) ? trim((string) $_GET['slot_start']) : null;
        $selectedSlotEnd   = !empty($_GET['slot_end']) ? trim((string) $_GET['slot_end']) : null;

        $freeSlots = [];
        if ($selectedServiceId) {
            $freeSlots = AppointmentService::getFreeSlotsForDay($selectedDate, $selectedLocationId, $selectedServiceId);
        }

        require __DIR__ . '/views/portal/book.php';
    };
    $router->get('/bootschule/book', $portalBookGetHandler);
    $router->get('bootschule/book', $portalBookGetHandler);

    // GET /bootschule/free-slots (API für freie Slots als JSON)
    $portalFreeSlotsHandler = static function (): void {
        header('Content-Type: application/json; charset=utf-8');
        $date = !empty($_GET['date']) ? trim((string) $_GET['date']) : date('Y-m-d');
        $serviceId = !empty($_GET['service_id']) ? (int) $_GET['service_id'] : null;
        $locationId = !empty($_GET['location_id']) ? (int) $_GET['location_id'] : null;

        try {
            $slots = AppointmentService::getFreeSlotsForDay($date, $locationId, $serviceId);
            echo json_encode(['success' => true, 'date' => $date, 'slots' => $slots], JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    };
    $router->get('/bootschule/free-slots', $portalFreeSlotsHandler);
    $router->get('bootschule/free-slots', $portalFreeSlotsHandler);

    // POST /bootschule/book (Buchung absenden)
    $portalBookPostHandler = static function (): void {
        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_portal_error'] = 'Ungültiges Sicherheitstoken (CSRF). Bitte senden Sie das Formular erneut ab.';
            header('Location: ?route=bootschule/book');
            exit;
        }

        $serviceId  = (int) ($_POST['service_id'] ?? 0);
        $date       = trim((string) ($_POST['date'] ?? ''));
        $startTime  = trim((string) ($_POST['start_time'] ?? ''));
        $endTime    = trim((string) ($_POST['end_time'] ?? ''));
        $locationId = !empty($_POST['location_id']) ? (int) $_POST['location_id'] : null;

        $firstName     = trim((string) ($_POST['first_name'] ?? ''));
        $lastName      = trim((string) ($_POST['last_name'] ?? ''));
        $email         = strtolower(trim((string) ($_POST['email'] ?? '')));
        $phone         = trim((string) ($_POST['phone'] ?? ''));
        $targetLicense = trim((string) ($_POST['target_license'] ?? ''));
        $notes         = trim((string) ($_POST['notes'] ?? ''));

        if ($serviceId <= 0 || $date === '' || $startTime === '' || $endTime === '' || $firstName === '' || $lastName === '' || $email === '') {
            $_SESSION['flash_portal_error'] = 'Bitte füllen Sie alle Pflichtfelder aus und wählen Sie ein freies Zeitfenster.';
            header('Location: ?route=bootschule/book&service_id=' . $serviceId . '&date=' . urlencode($date));
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_portal_error'] = 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
            header('Location: ?route=bootschule/book&service_id=' . $serviceId . '&date=' . urlencode($date));
            exit;
        }

        $service = ServiceService::find($serviceId);
        if (!$service) {
            $_SESSION['flash_portal_error'] = 'Die gewählte Leistung ist ungültig oder nicht mehr verfügbar.';
            header('Location: ?route=bootschule/book');
            exit;
        }

        // 1. Kunde zuordnen oder neu anlegen (über E-Mail gematcht)
        $customerId = CustomerService::findOrCreateByEmail($email, [
            'first_name'     => $firstName,
            'last_name'      => $lastName,
            'phone'          => $phone,
            'target_license' => $targetLicense,
            'notes'          => 'Registriert via Online-Buchungsportal',
        ]);

        // 2. Verfügbarkeit und automatische Ressourcen-Zuteilung prüfen
        $slots = AppointmentService::getFreeSlotsForDay($date, $locationId, $serviceId);
        $matchedSlot = null;
        foreach ($slots as $s) {
            if ($s['start_time'] === $startTime) {
                $matchedSlot = $s;
                break;
            }
        }

        $instructorId = $matchedSlot['suggested_instructor_id'] ?? null;
        $boatId       = $matchedSlot['suggested_boat_id'] ?? null;

        // 3. Konfliktprüfung (inkl. Standort & Verfügbarkeit)
        $conflict = AppointmentService::checkConflicts($date, $startTime, $endTime, $boatId, $instructorId, $customerId, null, $locationId);
        if ($conflict['has_conflict']) {
            $_SESSION['flash_portal_error'] = 'Konflikt: ' . ($conflict['reason'] ?? 'Der Slot ist leider nicht mehr verfügbar.');
            header('Location: ?route=bootschule/book&service_id=' . $serviceId . '&date=' . urlencode($date));
            exit;
        }

        // 4. Appointment anlegen mit Status requested
        $price = (float) ($service['price'] ?? 0.00);
        try {
            $appointmentId = AppointmentService::create([
                'date'          => $date,
                'start_time'    => $startTime . (strlen($startTime) === 5 ? ':00' : ''),
                'end_time'      => $endTime . (strlen($endTime) === 5 ? ':00' : ''),
                'service_id'    => $serviceId,
                'customer_id'   => $customerId,
                'instructor_id' => $instructorId,
                'boat_id'       => $boatId,
                'location_id'   => $locationId,
                'status'        => AppointmentService::STATUS_REQUESTED,
                'price'         => $price,
                'notes'         => $notes,
            ]);
        } catch (\InvalidArgumentException $e) {
            $_SESSION['flash_portal_error'] = $e->getMessage();
            header('Location: ?route=bootschule/book&service_id=' . $serviceId . '&date=' . urlencode($date));
            exit;
        }

        // 5. Activity Logging
        if (class_exists('ActivityLog')) {
            ActivityLog::log('boating.booking_requested', $email, [
                'customer_id'    => $customerId,
                'appointment_id' => $appointmentId,
                'service_id'     => $serviceId,
                'date'           => $date,
                'start_time'     => $startTime,
            ]);
        }

        // 6. E-Mail-Benachrichtigungen versenden (Kunde, Admin, Fahrlehrer)
        try {
            BoatingNotificationService::sendBookingCreatedNotifications($appointmentId);
        } catch (\Throwable $e) {
            error_log('Fehler beim E-Mail-Versand (Portal Buchung): ' . $e->getMessage());
        }

        // Session-Kennung setzen für nahtlosen Zugriff auf "Meine Termine"
        $_SESSION['bs_customer_id'] = $customerId;
        $_SESSION['bs_customer_email'] = $email;

        header('Location: ?route=bootschule/book/success&id=' . $appointmentId);
        exit;
    };
    $router->post('/bootschule/book', $portalBookPostHandler);
    $router->post('bootschule/book', $portalBookPostHandler);

    // GET /bootschule/book/success (Buchungsbestätigung)
    $portalBookSuccessHandler = static function (): void {
        $id = (int) ($_GET['id'] ?? 0);
        $appointment = AppointmentService::find($id);

        if (!$appointment) {
            header('Location: ?route=bootschule');
            exit;
        }

        $customer = CustomerService::find((int) $appointment['customer_id']);
        $service  = ServiceService::find((int) $appointment['service_id']);
        $location = !empty($appointment['location_id']) ? LocationService::find((int) $appointment['location_id']) : null;

        require __DIR__ . '/views/portal/booking_success.php';
    };
    $router->get('/bootschule/book/success', $portalBookSuccessHandler);
    $router->get('bootschule/book/success', $portalBookSuccessHandler);

    // =========================================================================
    // SCHÜLER-MAGIC-LINK & MEINE TERMINE (/bootschule/me/...)
    // =========================================================================

    // a) GET /bootschule/me/link?token=...
    $portalMeLinkGetHandler = static function (): void {
        $token = trim((string) ($_GET['token'] ?? ''));

        if ($token !== '') {
            $magicData = BoatingCustomerMagicLinkService::validateToken($token);
            if ($magicData !== null) {
                // Gültig: Session-ID erneuern (Session-Fixation-Schutz)
                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_regenerate_id(true);
                }
                $customer = $magicData['customer'];
                $_SESSION['boating_customer_id'] = (int) $customer['id'];
                $_SESSION['boating_customer_email'] = (string) $customer['email'];
                $_SESSION['boating_customer_first_name'] = (string) ($customer['first_name'] ?? '');
                $_SESSION['bs_customer_id'] = (int) $customer['id'];
                $_SESSION['bs_customer_email'] = (string) $customer['email'];

                header('Location: ?route=bootschule/me/appointments');
                exit;
            }
        }

        // Ungültig oder abgelaufen: Fehlerseite mit Formular für Neuanforderung
        $tokenInvalid = true;
        $customer = null;
        $upcomingAppointments = [];
        $pastAppointments = [];
        $subscriptions = [];
        $csrfToken = class_exists('Csrf') ? Csrf::getToken() : '';
        require __DIR__ . '/views/portal/my_appointments.php';
    };

    $router->get('/bootschule/me/link', $portalMeLinkGetHandler);
    $router->get('bootschule/me/link', $portalMeLinkGetHandler);

    // b) POST /bootschule/me/request-link (CSRF-geschützt)
    $portalRequestLinkHandler = static function (): void {
        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_portal_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=bootschule/me/appointments');
            exit;
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        $result = BoatingCustomerMagicLinkService::requestLinkByEmail($email);

        // Antwort ist unabhängig vom Ergebnis neutral
        $_SESSION['flash_portal_success'] = $result['message'];

        header('Location: ?route=bootschule/me/appointments');
        exit;
    };

    $router->post('/bootschule/me/request-link', $portalRequestLinkHandler);
    $router->post('bootschule/me/request-link', $portalRequestLinkHandler);
    $router->post('/bootschule/magic-link/request', $portalRequestLinkHandler);
    $router->post('bootschule/magic-link/request', $portalRequestLinkHandler);

    // c) GET /bootschule/me/appointments
    $portalMyAppointmentsGetHandler = static function (): void {
        $token = trim((string) ($_GET['token'] ?? ''));

        // Falls ein Token direkt an diese Route übergeben wurde -> autom. einloggen
        if ($token !== '') {
            $magicData = BoatingCustomerMagicLinkService::validateToken($token);
            if ($magicData !== null) {
                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_regenerate_id(true);
                }
                $customer = $magicData['customer'];
                $_SESSION['boating_customer_id'] = (int) $customer['id'];
                $_SESSION['boating_customer_email'] = (string) $customer['email'];
                $_SESSION['boating_customer_first_name'] = (string) ($customer['first_name'] ?? '');
                $_SESSION['bs_customer_id'] = (int) $customer['id'];
                $_SESSION['bs_customer_email'] = (string) $customer['email'];

                header('Location: ?route=bootschule/me/appointments');
                exit;
            }
        }

        $sessionCustomerId = !empty($_SESSION['boating_customer_id'])
            ? (int) $_SESSION['boating_customer_id']
            : (!empty($_SESSION['bs_customer_id']) ? (int) $_SESSION['bs_customer_id'] : 0);

        $customer = null;
        $upcomingAppointments = [];
        $pastAppointments = [];
        $subscriptions = [];
        $tokenInvalid = false;
        $noSession = false;

        if ($sessionCustomerId > 0) {
            $customer = BoatingSchoolRepository::getCustomerById($sessionCustomerId);
            if ($customer && (int) ($customer['is_active'] ?? 1) === 1) {
                $grouped = BoatingCustomerMagicLinkService::getCustomerAppointments($sessionCustomerId);
                $upcomingAppointments = $grouped['upcoming'];
                $pastAppointments     = $grouped['past'];
                $subscriptions        = BoatingSchoolRepository::getCustomerSubscriptions($sessionCustomerId);
            } else {
                unset($_SESSION['boating_customer_id'], $_SESSION['bs_customer_id']);
                $customer = null;
                $noSession = true;
            }
        } else {
            $noSession = true;
        }

        $csrfToken = class_exists('Csrf') ? Csrf::getToken() : '';
        require __DIR__ . '/views/portal/my_appointments.php';
    };

    $router->get('/bootschule/me/appointments', $portalMyAppointmentsGetHandler);
    $router->get('bootschule/me/appointments', $portalMyAppointmentsGetHandler);
    $router->get('/bootschule/meine-termine', $portalMyAppointmentsGetHandler);
    $router->get('bootschule/meine-termine', $portalMyAppointmentsGetHandler);
    $router->get('/bootschule/my-appointments', $portalMyAppointmentsGetHandler);
    $router->get('bootschule/my-appointments', $portalMyAppointmentsGetHandler);

    // d) POST /bootschule/me/appointments/{id}/cancel & /bootschule/me/appointments/cancel (CSRF-geschützt)
    $portalCancelHandler = static function (?string $paramId = null): void {
        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_portal_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=bootschule/me/appointments');
            exit;
        }

        $sessionCustomerId = !empty($_SESSION['boating_customer_id'])
            ? (int) $_SESSION['boating_customer_id']
            : (!empty($_SESSION['bs_customer_id']) ? (int) $_SESSION['bs_customer_id'] : 0);

        if ($sessionCustomerId <= 0) {
            $_SESSION['flash_portal_error'] = 'Bitte melde dich über deinen persönlichen Magic-Link an.';
            header('Location: ?route=bootschule/me/appointments');
            exit;
        }

        $appointmentId = (int) (!empty($paramId) ? $paramId : ($_POST['appointment_id'] ?? $_GET['id'] ?? 0));
        $cancelReason  = !empty($_POST['cancel_reason']) ? trim((string) $_POST['cancel_reason']) : null;

        if ($appointmentId <= 0) {
            $_SESSION['flash_portal_error'] = 'Ungültige Terminauswahl.';
            header('Location: ?route=bootschule/me/appointments');
            exit;
        }

        $res = BoatingCustomerMagicLinkService::cancelAppointmentByStudent($appointmentId, $sessionCustomerId, $cancelReason);

        if ($res['success']) {
            $_SESSION['flash_portal_success'] = $res['message'];
        } else {
            $_SESSION['flash_portal_error'] = $res['message'];
        }

        header('Location: ?route=bootschule/me/appointments');
        exit;
    };

    $router->post('/bootschule/me/appointments/{id}/cancel', $portalCancelHandler);
    $router->post('bootschule/me/appointments/{id}/cancel', $portalCancelHandler);
    $router->post('/bootschule/me/appointments/cancel', $portalCancelHandler);
    $router->post('bootschule/me/appointments/cancel', $portalCancelHandler);
    $router->post('/bootschule/meine-termine/cancel', $portalCancelHandler);
    $router->post('bootschule/meine-termine/cancel', $portalCancelHandler);

    // e) Logout Route für Schüler
    $portalLogoutHandler = static function (): void {
        unset(
            $_SESSION['boating_customer_id'],
            $_SESSION['boating_customer_email'],
            $_SESSION['boating_customer_first_name'],
            $_SESSION['bs_customer_id'],
            $_SESSION['bs_customer_email']
        );
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION['flash_portal_success'] = 'Du hast dich erfolgreich abgemeldet.';
        header('Location: ?route=bootschule/me/appointments');
        exit;
    };

    $router->get('/bootschule/me/logout', $portalLogoutHandler);
    $router->get('bootschule/me/logout', $portalLogoutHandler);
    $router->post('/bootschule/me/logout', $portalLogoutHandler);
    $router->post('bootschule/me/logout', $portalLogoutHandler);

    // =========================================================================
    // 12. NAUTIC DEDICATED LOGONSEITE & ACCOUNT-REGISTRIERUNG
    // =========================================================================
    $nauticLoginGetHandler = static function (): void {
        $error = $_SESSION['flash_nautic_error'] ?? null;
        $success = $_SESSION['flash_nautic_success'] ?? null;
        $info = $_SESSION['flash_nautic_info'] ?? null;
        $sessionEmail = $_SESSION['nautic_login_email'] ?? null;
        require __DIR__ . '/views/portal/login.php';
    };

    // Einheitlicher 2-Stufen Magic-Code Login (Stufe 1: E-Mail -> Stufe 2: Code in Boxen)
    $nauticLoginPostHandler = static function (): void {
        $action = trim((string) ($_POST['action'] ?? 'login'));

        // Aktion: E-Mail zurücksetzen ("E-Mail ändern")
        if ($action === 'clear_email' || $action === 'reset_email') {
            unset($_SESSION['nautic_login_email']);
            header('Location: ?route=nautic/login');
            exit;
        }

        $sessionEmail = trim(strtolower((string) ($_SESSION['nautic_login_email'] ?? '')));
        $email = trim(strtolower((string) ($_POST['email'] ?? '')));
        if ($email === '' && $sessionEmail !== '') {
            $email = $sessionEmail;
        }

        $remember = !empty($_POST['remember']);

        // Code aus hidden Input oder aus den 10 Einzelboxen digit_0..digit_9 zusammensetzen
        $code = trim(strtoupper((string) ($_POST['code'] ?? '')));
        if ($code === '' && isset($_POST['digit_0'])) {
            $assembled = '';
            for ($d = 0; $d < 10; $d++) {
                if (isset($_POST["digit_{$d}"])) {
                    $assembled .= trim(strtoupper((string) $_POST["digit_{$d}"]));
                }
            }
            if ($assembled !== '') {
                $code = $assembled;
            }
        }

        // Validierung E-Mail
        if ($email === '') {
            $_SESSION['flash_nautic_error'] = 'Bitte gib deine E-Mail-Adresse ein.';
            header('Location: ?route=nautic/login');
            exit;
        }

        if ($remember) {
            setcookie('nautic_remember_email', $email, time() + (86400 * 30), '/');
        }

        // Profil des Nutzers erkennen (für automatische Ziel-Weiterleitung nach Login)
        $isCoach = false;
        $isAdmin = false;
        $isStudent = false;

        if (class_exists('BoatingSchoolRepository')) {
            $coachList = BoatingSchoolRepository::getInstructors(['email' => $email, 'is_active' => 1], 1, 0);
            if (!empty($coachList)) {
                $isCoach = true;
            }
            $customerList = BoatingSchoolRepository::getCustomers(['email' => $email, 'is_active' => 1], 1, 0);
            if (!empty($customerList)) {
                $isStudent = true;
            }
        }
        try {
            $user = DB::fetchOne('SELECT * FROM users WHERE email = :email AND is_active = 1 LIMIT 1', ['email' => $email]);
            if ($user && ($user['role'] === 'admin' || $user['role'] === 'super_admin')) {
                $isAdmin = true;
            }
        } catch (\Throwable $e) {}

        $targetUrl = '?route=bootschule/me/appointments';
        if ($isCoach) {
            $targetUrl = '?route=admin/boating/instructor-portal';
        } elseif ($isAdmin) {
            $targetUrl = '?route=admin/boating';
        }

        // STUFE 2: Magic-Code wurde übermittelt -> Verifizierung
        if ($code !== '') {
            if (class_exists('MagicCode') && MagicCode::isDevMagicCodeValid($email, $code)) {
                Auth::loginWithMagicCodeForEmail($email, $code);
                unset($_SESSION['nautic_login_email']);
                $_SESSION['flash_success'] = 'Erfolgreich via Magic-Code angemeldet!';
                header('Location: ' . $targetUrl);
                exit;
            }

            if (class_exists('Auth') && Auth::loginWithMagicCodeForEmail($email, $code)) {
                unset($_SESSION['nautic_login_email']);
                $_SESSION['flash_success'] = 'Erfolgreich via Magic-Code angemeldet!';
                header('Location: ' . $targetUrl);
                exit;
            }

            $_SESSION['nautic_login_email'] = $email;
            $_SESSION['flash_nautic_error'] = 'Der eingegebene Magic-Code ist ungültig oder abgelaufen.';
            header('Location: ?route=nautic/login&step=code');
            exit;
        }

        // STUFE 1: Nur E-Mail eingegeben -> Code erstellen, Session setzen und zu Stufe 2 wechseln
        $_SESSION['nautic_login_email'] = $email;

        // Sonderfall Schnell-Login im Entwicklermodus (Backdoor für ROLAND1234)
        $isDevMode = class_exists('MagicCode') && MagicCode::isDevMagicCodeEnabled();
        $isDevEmail = $isDevMode && ($email === MagicCode::getDevMagicCodeEmail() || $email === 'beat@bootschule-see.ch' || $email === 'corinne@bootschule-see.ch' || $email === 'office@studiocreativo.ch');

        if (class_exists('MagicCode')) {
            try {
                $created = MagicCode::createCodeForEmail($email, 'nautic_login', 1, 24);
                MagicCode::sendEmailNotification($created['code'], $email, 'nautic_login', 1, 'office@studiocreativo.ch');
                $_SESSION['flash_nautic_success'] = "Ein persönlicher Magic-Code wurde an {$email} gesendet. Bitte gib den Code unten in die Boxen ein.";
            } catch (\Throwable $e) {
                if ($isDevEmail) {
                    $_SESSION['flash_nautic_info'] = 'Dev-Modus aktiv: Du kannst dich direkt mit dem Code ROLAND1234 einloggen.';
                } else {
                    $_SESSION['flash_nautic_error'] = 'Fehler beim Erstellen des Magic-Codes: ' . $e->getMessage();
                }
            }
        } elseif (class_exists('BoatingCustomerMagicLinkService')) {
            $res = BoatingCustomerMagicLinkService::requestLinkByEmail($email);
            $_SESSION['flash_nautic_success'] = $res['message'] ?? 'Magic-Link wurde gesendet.';
        }

        header('Location: ?route=nautic/login&step=code');
        exit;
    };

    // Account anfordern (Registrierungs-POST)
    $nauticRequestAccountPostHandler = static function (): void {
        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));
        $email = trim(strtolower((string) ($_POST['email'] ?? '')));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $desiredRole = trim((string) ($_POST['desired_role'] ?? 'student'));
        $notes = trim((string) ($_POST['notes'] ?? ''));

        if ($firstName === '' || $lastName === '' || $email === '') {
            $_SESSION['flash_nautic_error'] = 'Bitte Vorname, Nachname und E-Mail-Adresse ausfüllen.';
            header('Location: ?route=nautic/login&action=register');
            exit;
        }

        if (class_exists('NauticAccountRequestService')) {
            try {
                NauticAccountRequestService::createRequest([
                    'first_name'   => $firstName,
                    'last_name'    => $lastName,
                    'email'        => $email,
                    'phone'        => $phone,
                    'desired_role' => $desiredRole,
                    'notes'        => $notes,
                ]);

                $_SESSION['flash_nautic_success'] = "Vielen Dank, {$firstName}! Deine Registrierung wurde eingereicht. Die Flottenleitung prüft deine Anfrage und weist dein Profil zu. Sobald du freigeschaltet wurdest, kannst du dich direkt mit deiner E-Mail per Magic-Link einloggen.";
                header('Location: ?route=nautic/login');
                exit;
            } catch (\Throwable $e) {
                $_SESSION['flash_nautic_error'] = 'Fehler bei der Registrierungsanfrage: ' . $e->getMessage();
                header('Location: ?route=nautic/login&action=register');
                exit;
            }
        }

        header('Location: ?route=nautic/login');
        exit;
    };

    // =========================================================================
    // MODUL-EINSTIEG FÜR /boating_school, /boating-school & /nautic
    // =========================================================================
    $nauticModuleEntryHandler = static function () use ($nauticLoginGetHandler, $portalStartHandler): void {
        // Falls bereits angemeldet: direkt ins passende Dashboard / Portal
        if (class_exists('Auth') && Auth::check()) {
            $user = Auth::user();
            $email = strtolower((string) ($user['email'] ?? ''));
            $role = (string) ($user['role'] ?? '');

            if ($role === 'admin' || $role === 'super_admin' || (class_exists('Rbac') && (Rbac::can('admin.boating.manage') || Rbac::can('admin.boating.view')))) {
                header('Location: ?route=admin/boating');
                exit;
            }

            if (class_exists('BoatingSchoolRepository')) {
                $coachList = BoatingSchoolRepository::getInstructors(['email' => $email, 'is_active' => 1], 1, 0);
                if (!empty($coachList)) {
                    header('Location: ?route=admin/boating/instructor-portal');
                    exit;
                }
            }

            header('Location: ?route=bootschule/me/appointments');
            exit;
        }

        // Falls explizit das Buchungsportal gewünscht ist (?portal=1):
        if (isset($_GET['portal']) || (isset($_GET['view']) && $_GET['view'] === 'booking')) {
            $portalStartHandler();
            return;
        }

        // Nautic Logonseite mit SVG-Logo und Magic-Link anzeigen
        $nauticLoginGetHandler();
    };

    $nauticModuleEntryPostHandler = static function () use ($nauticLoginPostHandler, $nauticRequestAccountPostHandler): void {
        $action = trim((string) ($_POST['action'] ?? 'login'));
        if ($action === 'register') {
            $nauticRequestAccountPostHandler();
            return;
        }
        $nauticLoginPostHandler();
    };

    // Modul-Einstiegsrouten registrieren (GET & POST)
    $router->get('/boating_school', $nauticModuleEntryHandler);
    $router->get('boating_school', $nauticModuleEntryHandler);
    $router->get('/boating-school', $nauticModuleEntryHandler);
    $router->get('boating-school', $nauticModuleEntryHandler);
    $router->get('/nautic', $nauticModuleEntryHandler);
    $router->get('nautic', $nauticModuleEntryHandler);

    $router->post('/boating_school', $nauticModuleEntryPostHandler);
    $router->post('boating_school', $nauticModuleEntryPostHandler);
    $router->post('/boating-school', $nauticModuleEntryPostHandler);
    $router->post('boating-school', $nauticModuleEntryPostHandler);
    $router->post('/nautic', $nauticModuleEntryPostHandler);
    $router->post('nautic', $nauticModuleEntryPostHandler);

    // Dedizierte Login- & Register-Aliase
    $router->get('/boating_school/login', $nauticLoginGetHandler);
    $router->get('boating_school/login', $nauticLoginGetHandler);
    $router->get('/boating-school/login', $nauticLoginGetHandler);
    $router->get('boating-school/login', $nauticLoginGetHandler);
    $router->post('/boating_school/login', $nauticLoginPostHandler);
    $router->post('boating_school/login', $nauticLoginPostHandler);
    $router->post('/boating-school/login', $nauticLoginPostHandler);
    $router->post('boating-school/login', $nauticLoginPostHandler);

    $router->post('/boating_school/request-account', $nauticRequestAccountPostHandler);
    $router->post('boating_school/request-account', $nauticRequestAccountPostHandler);
    $router->post('/boating-school/request-account', $nauticRequestAccountPostHandler);
    $router->post('boating-school/request-account', $nauticRequestAccountPostHandler);

    $router->get('/nautic/login', $nauticLoginGetHandler);
    $router->get('nautic/login', $nauticLoginGetHandler);
    $router->get('/nautic/logon', $nauticLoginGetHandler);
    $router->get('nautic/logon', $nauticLoginGetHandler);
    $router->get('/bootschule/login', $nauticLoginGetHandler);
    $router->get('bootschule/login', $nauticLoginGetHandler);
    $router->get('/bootschule/logon', $nauticLoginGetHandler);
    $router->get('bootschule/logon', $nauticLoginGetHandler);
    $router->get('/admin/boating/login', $nauticLoginGetHandler);
    $router->get('admin/boating/login', $nauticLoginGetHandler);

    $router->post('/nautic/login', $nauticLoginPostHandler);
    $router->post('nautic/login', $nauticLoginPostHandler);
    $router->post('/nautic/logon', $nauticLoginPostHandler);
    $router->post('nautic/logon', $nauticLoginPostHandler);
    $router->post('/bootschule/login', $nauticLoginPostHandler);
    $router->post('bootschule/login', $nauticLoginPostHandler);
    $router->post('/bootschule/logon', $nauticLoginPostHandler);
    $router->post('bootschule/logon', $nauticLoginPostHandler);
    $router->post('/admin/boating/login', $nauticLoginPostHandler);
    $router->post('admin/boating/login', $nauticLoginPostHandler);

    // POST /nautic/request-account (Öffentliche Registrierung)
    $router->post('/nautic/request-account', $nauticRequestAccountPostHandler);
    $router->post('nautic/request-account', $nauticRequestAccountPostHandler);
    $router->post('/bootschule/request-account', $nauticRequestAccountPostHandler);
    $router->post('bootschule/request-account', $nauticRequestAccountPostHandler);

    // =========================================================================
    // 13. ADMIN: ACCOUNT-ANFRAGEN & FREISCHALTUNGEN
    // =========================================================================
    $registerGet('account-requests', static function () use ($checkPermission): void {
        $checkPermission(false);
        $statusFilter = trim((string) ($_GET['status'] ?? 'pending'));
        if (!in_array($statusFilter, ['pending', 'approved', 'rejected', 'all'], true)) {
            $statusFilter = 'pending';
        }

        $requests = class_exists('NauticAccountRequestService') 
            ? NauticAccountRequestService::getAll($statusFilter === 'all' ? null : $statusFilter)
            : [];
        $pendingCount = class_exists('NauticAccountRequestService')
            ? NauticAccountRequestService::getPendingCount()
            : 0;

        require __DIR__ . '/views/admin/account_requests.php';
    });

    $registerPost('account-requests/approve', static function () use ($checkPermission): void {
        $checkPermission(true);
        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/boating/account-requests');
            exit;
        }

        $requestId = (int) ($_POST['request_id'] ?? 0);
        $profileType = trim((string) ($_POST['profile_type'] ?? 'student'));
        $adminUser = class_exists('Auth') && Auth::user() ? (string) (Auth::user()['name'] ?? Auth::user()['email']) : 'Administrator';

        if (class_exists('NauticAccountRequestService')) {
            $res = NauticAccountRequestService::approveAndAssignProfile($requestId, $profileType, $adminUser);
            if ($res['success']) {
                $_SESSION['flash_success'] = $res['message'];
            } else {
                $_SESSION['flash_error'] = $res['message'];
            }
        }

        header('Location: ?route=admin/boating/account-requests');
        exit;
    });

    $registerPost('account-requests/reject', static function () use ($checkPermission): void {
        $checkPermission(true);
        if (class_exists('Csrf') && !Csrf::validateRequest()) {
            $_SESSION['flash_error'] = 'Ungültiges Sicherheitstoken (CSRF).';
            header('Location: ?route=admin/boating/account-requests');
            exit;
        }

        $requestId = (int) ($_POST['request_id'] ?? 0);
        $adminUser = class_exists('Auth') && Auth::user() ? (string) (Auth::user()['name'] ?? Auth::user()['email']) : 'Administrator';

        if (class_exists('NauticAccountRequestService')) {
            NauticAccountRequestService::rejectRequest($requestId, $adminUser);
            $_SESSION['flash_success'] = 'Registrierungsanfrage wurde abgelehnt.';
        }

        header('Location: ?route=admin/boating/account-requests');
        exit;
    });
}

