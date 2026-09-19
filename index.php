<?php

declare(strict_types=1);

require_once __DIR__ . '/DB.php';
require_once __DIR__ . '/core/Router.php';

$router = new Router();

$router->get('/', function (): void {
    require __DIR__ . '/views/home.php';
});

$router->get('/db-test', function (): void {
    $row = DB::fetchOne('SELECT NOW() AS now');
    require __DIR__ . '/views/db-test.php';
});

$route = $_GET['route'] ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$router->dispatch($method, (string) $route);
