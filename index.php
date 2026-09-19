<?php

declare(strict_types=1);

require_once __DIR__ . '/DB.php';
require_once __DIR__ . '/core/Router.php';
require_once __DIR__ . '/core/MagicCode.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Mailer.php';
require_once __DIR__ . '/core/Csrf.php';
require_once __DIR__ . '/core/ModuleManager.php';
require_once __DIR__ . '/core/User.php';
require_once __DIR__ . '/core/Rbac.php';

Auth::startSession();

$router = new Router();

// GET / - Startseite mit Magic-Code-Flow
$router->get('/', function (): void {
    require __DIR__ . '/views/home.php';
});

// POST /magic-start - Speichert geprüfte E-Mail in der Session
$router->post('/magic-start', function (): void {
    $email = trim((string) ($_POST['email'] ?? ''));

    if ($email === '' || strlen($email) > 191 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['flash_home_error'] = 'Bitte eine gültige E-Mail-Adresse eingeben (maximal 191 Zeichen).';
        header('Location: ?route=/');
        exit;
    }

    $_SESSION['magic_input_email'] = strtolower($email);
    header('Location: ?route=/');
    exit;
});

// POST /magic-reset-email - Löscht die Session-E-Mail ("E-Mail ändern")
$router->post('/magic-reset-email', function (): void {
    unset($_SESSION['magic_input_email']);
    header('Location: ?route=/');
    exit;
});

// POST /magic-request-code - Generiert & versendet neuen Code für die Session-E-Mail
$router->post('/magic-request-code', function (): void {
    $email = $_SESSION['magic_input_email'] ?? null;

    if (empty($email)) {
        $_SESSION['flash_home_error'] = 'Bitte zuerst E-Mail eingeben.';
        header('Location: ?route=/');
        exit;
    }

    try {
        $created = MagicCode::createCodeForEmail($email, 'admin_login', 1, null);
        MagicCode::sendEmailNotification($created['code'], $email, 'admin_login', 1, 'office@studiocreativo.ch');

        $_SESSION['flash_home_success'] = 'Ein neuer 10-stelliger Code wurde an ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . ' gesendet (Kopie an office@studiocreativo.ch).';
    } catch (\Throwable $e) {
        $_SESSION['flash_home_error'] = 'Fehler beim Erstellen des Magic-Codes: ' . $e->getMessage();
    }

    header('Location: ?route=/');
    exit;
});

// POST /magic-login - Prüft Magic-Code für die Session-E-Mail
$router->post('/magic-login', function (): void {
    $email = $_SESSION['magic_input_email'] ?? null;
    $code = trim((string) ($_POST['magic_code'] ?? ''));

    if (empty($email)) {
        $_SESSION['flash_home_error'] = 'Bitte zuerst deine E-Mail-Adresse angeben.';
        header('Location: ?route=/');
        exit;
    }

    if (Auth::loginWithMagicCodeForEmail($email, $code)) {
        header('Location: ?route=admin');
        exit;
    }

    $_SESSION['flash_home_error'] = 'Ungültiger oder abgelaufener Magic-Code für diese E-Mail.';
    header('Location: ?route=/');
    exit;
});

// GET /db-test
$router->get('/db-test', function (): void {
    $row = DB::fetchOne('SELECT NOW() AS now');
    require __DIR__ . '/views/db-test.php';
});

// Standard-Login Routen (optional / Fallback)
$router->get('/login', function (): void {
    if (Auth::check()) {
        header('Location: ?route=admin');
        exit;
    }

    $error = $_GET['error'] ?? null;
    require __DIR__ . '/views/auth/login.php';
});

$router->post('/login', function (): void {
    $email = (string) ($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if (Auth::login($email, $password)) {
        header('Location: ?route=admin');
        exit;
    }

    $error = 'Login fehlgeschlagen. Bitte E-Mail und Passwort prüfen.';
    require __DIR__ . '/views/auth/login.php';
});

// GET /logout
$router->get('/logout', function (): void {
    Auth::logout();
    header('Location: ?route=/');
    exit;
});

// GET /admin - Admin Dashboard
$router->get('/admin', function (): void {
    if (!Auth::checkMagic()) {
        header('Location: ?route=/');
        exit;
    }

    $user = Auth::user();
    $magicCodes = MagicCode::getAll();
    $currentRoute = 'admin';
    require __DIR__ . '/views/admin/dashboard.php';
});

// GET /admin/magic-codes - Vollständige Magic-Code-Verwaltung mit Filtern
$router->get('/admin/magic-codes', function (): void {
    if (!Auth::checkMagic()) {
        header('Location: ?route=/');
        exit;
    }

    $filters = [
        'email' => (string) ($_GET['email'] ?? ''),
        'status' => (string) ($_GET['status'] ?? 'all'),
        'usage_type' => (string) ($_GET['usage_type'] ?? 'all'),
        'date_from' => (string) ($_GET['date_from'] ?? ''),
        'date_to' => (string) ($_GET['date_to'] ?? ''),
    ];

    $user = Auth::user();
    $magicCodes = MagicCode::list($filters);
    $currentRoute = 'admin/magic-codes';
    require __DIR__ . '/views/admin/magic_codes.php';
});

// Helper zur CSRF- und Auth-Prüfung in POST-Routen
$requireAdminAuthAndCsrf = function (string $redirectRoute = 'admin/magic-codes'): void {
    if (!Auth::checkMagic()) {
        header('Location: ?route=/');
        exit;
    }

    if (!Csrf::validateRequest()) {
        $_SESSION['flash_error'] = 'Ungültiges oder abgelaufenes CSRF-Sicherheitstoken. Bitte die Seite neu laden und die Aktion wiederholen.';
        header('Location: ?route=' . $redirectRoute);
        exit;
    }
};

// POST /admin/magic-codes/create (und Alias /admin/magic-code/create)
$createCodeHandler = function () use ($requireAdminAuthAndCsrf): void {
    $requireAdminAuthAndCsrf('admin/magic-codes');

    $email = trim((string) ($_POST['email'] ?? 'office@studiocreativo.ch'));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['flash_error'] = 'Bitte eine gültige E-Mail-Adresse für den Magic-Code eingeben.';
        header('Location: ?route=admin/magic-codes');
        exit;
    }

    $usageType = in_array($_POST['usage_type'] ?? '', ['admin_login', 'frontend'], true)
        ? (string) $_POST['usage_type']
        : 'admin_login';

    $maxUses = max(1, (int) ($_POST['max_uses'] ?? 1));

    $expiresAt = null;
    if (!empty($_POST['expires_at'])) {
        try {
            $expiresAt = new DateTimeImmutable((string) $_POST['expires_at']);
        } catch (\Throwable $e) {
            $expiresAt = null;
        }
    }

    try {
        $created = MagicCode::createCodeForEmail($email, $usageType, $maxUses, $expiresAt);
        MagicCode::sendEmailNotification($created['code'], $email, $usageType, $maxUses, 'office@studiocreativo.ch');

        $_SESSION['flash_success'] = 'Neuer Magic-Code erfolgreich für ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . ' generiert und versendet.';
        $_SESSION['flash_created_code'] = $created['code'];
    } catch (\Throwable $e) {
        $_SESSION['flash_error'] = 'Fehler beim Erstellen des Magic-Codes: ' . $e->getMessage();
    }

    header('Location: ?route=admin/magic-codes');
    exit;
};

$router->post('/admin/magic-codes/create', $createCodeHandler);
$router->post('/admin/magic-code/create', $createCodeHandler);
$router->post('/admin-create-code', $createCodeHandler);

// POST /admin/magic-codes/deactivate - Deaktiviert/Sperrt einen Code
$router->post('/admin/magic-codes/deactivate', function () use ($requireAdminAuthAndCsrf): void {
    $requireAdminAuthAndCsrf('admin/magic-codes');

    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        $_SESSION['flash_error'] = 'Ungültige Magic-Code-ID.';
        header('Location: ?route=admin/magic-codes');
        exit;
    }

    try {
        MagicCode::deactivate($id);
        $_SESSION['flash_success'] = "Magic-Code #{$id} wurde erfolgreich deaktiviert und gesperrt.";
    } catch (\Throwable $e) {
        $_SESSION['flash_error'] = 'Fehler beim Deaktivieren des Codes: ' . $e->getMessage();
    }

    header('Location: ?route=admin/magic-codes');
    exit;
});

// POST /admin/magic-codes/delete - Soft-Delete für einen Code
$router->post('/admin/magic-codes/delete', function () use ($requireAdminAuthAndCsrf): void {
    $requireAdminAuthAndCsrf('admin/magic-codes');

    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        $_SESSION['flash_error'] = 'Ungültige Magic-Code-ID.';
        header('Location: ?route=admin/magic-codes');
        exit;
    }

    try {
        MagicCode::softDelete($id);
        $_SESSION['flash_success'] = "Magic-Code #{$id} wurde erfolgreich archiviert (Soft-Delete).";
    } catch (\Throwable $e) {
        $_SESSION['flash_error'] = 'Fehler beim Löschen des Codes: ' . $e->getMessage();
    }

    header('Location: ?route=admin/magic-codes');
    exit;
});

// POST /admin/magic-codes/resend - Neuen Code erzeugen & versenden
$router->post('/admin/magic-codes/resend', function () use ($requireAdminAuthAndCsrf): void {
    $requireAdminAuthAndCsrf('admin/magic-codes');

    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        $_SESSION['flash_error'] = 'Ungültige Magic-Code-ID.';
        header('Location: ?route=admin/magic-codes');
        exit;
    }

    try {
        $result = MagicCode::resendCode($id);
        if ($result !== null) {
            $_SESSION['flash_success'] = 'Ein frischer Magic-Code wurde generiert und erfolgreich an ' . htmlspecialchars($result['email'], ENT_QUOTES, 'UTF-8') . ' gesendet.';
            $_SESSION['flash_created_code'] = $result['code'];
        } else {
            $_SESSION['flash_error'] = "Der Magic-Code #{$id} konnte nicht gefunden werden.";
        }
    } catch (\Throwable $e) {
        $_SESSION['flash_error'] = 'Fehler beim erneuten Senden des Codes: ' . $e->getMessage();
    }

    header('Location: ?route=admin/magic-codes');
    exit;
});

// Aktive Module laden (Routen & Hooks registrieren)
ModuleManager::loadActiveModules(__DIR__ . '/modules', $router);

$route = $_GET['route'] ?? '/';
$currentRoute = (string) $route;
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$router->dispatch($method, (string) $route);
