<?php

declare(strict_types=1);

require_once __DIR__ . '/DB.php';
require_once __DIR__ . '/core/Router.php';
require_once __DIR__ . '/core/MagicCode.php';
require_once __DIR__ . '/core/Auth.php';

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

// GET /admin - Streng geschützt: NUR erreichbar wenn magic_authenticated true ist
$router->get('/admin', function (): void {
    if (!Auth::checkMagic()) {
        header('Location: ?route=/');
        exit;
    }

    $user = Auth::user();
    $magicCodes = MagicCode::getAll();
    require __DIR__ . '/views/admin/dashboard.php';
});

// POST /admin/magic-code/create - Manueller Code-Generator im Dashboard
$createCodeHandler = function (): void {
    if (!Auth::checkMagic()) {
        header('Location: ?route=/');
        exit;
    }

    $email = trim((string) ($_POST['email'] ?? 'office@studiocreativo.ch'));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $email = 'office@studiocreativo.ch';
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

    header('Location: ?route=admin');
    exit;
};

$router->post('/admin/magic-code/create', $createCodeHandler);
$router->post('/admin-create-code', $createCodeHandler);

$route = $_GET['route'] ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$router->dispatch($method, (string) $route);
