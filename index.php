<?php

declare(strict_types=1);

require_once __DIR__ . '/DB.php';
require_once __DIR__ . '/core/Router.php';
require_once __DIR__ . '/core/Auth.php';

Auth::startSession();

$router = new Router();

$router->get('/', function (): void {
    $error = $_GET['error'] ?? null;
    require __DIR__ . '/views/home.php';
});

$router->post('/magic-login', function (): void {
    $code = (string) ($_POST['magic_code'] ?? '');

    if (Auth::loginWithMagicCode($code)) {
        header('Location: ?route=admin');
        exit;
    }

    $error = 'Ungültiger oder abgelaufener Magic-Code.';
    require __DIR__ . '/views/home.php';
});

$router->get('/db-test', function (): void {
    $row = DB::fetchOne('SELECT NOW() AS now');
    require __DIR__ . '/views/db-test.php';
});

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

$router->get('/logout', function (): void {
    Auth::logout();
    header('Location: ?route=/');
    exit;
});

$router->get('/admin', function (): void {
    $user = Auth::user();
    if ($user === null) {
        header('Location: ?route=/');
        exit;
    }

    require __DIR__ . '/views/admin/dashboard.php';
});

$route = $_GET['route'] ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$router->dispatch($method, (string) $route);
