<?php

declare(strict_types=1);

/**
 * Bootstrap für das Kontaktformular-Modul.
 * Registriert Admin-Menüpunkte und Frontend-Routen, sofern vorhanden.
 *
 * @var Router|null $router
 * @var array<string, mixed> $module
 * @var string $moduleDir
 */

// Admin-Menüpunkt registrieren
if (class_exists('ModuleManager')) {
    ModuleManager::addAdminMenuItem([
        'label' => 'Kontaktformular',
        'route' => 'admin/contact-form',
        'url'   => '?route=admin/contact-form',
        'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-envelope-paper-fill me-2" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M6.5 9.5 3 7.5v-6A1.5 1.5 0 0 1 4.5 0h7A1.5 1.5 0 0 1 13 1.5v6l-3.5 2L8 8.75zM1.059 3.635 2 3.133v3.753L0 5.713V4.5a1.5 1.5 0 0 1 1.059-.865M16 5.713l-2 1.173V3.133l.941.502A1.5 1.5 0 0 1 16 4.5zm0 2.115-3.5 2.05-1.848-1.082L8 10.222l-2.652-1.441L3.5 9.878 0 7.828V14.5A1.5 1.5 0 0 0 1.5 16h13a1.5 1.5 0 0 0 1.5-1.5z"/></svg>',
    ]);
}

// Frontend-Route registrieren, falls Router vorhanden
if (isset($router) && $router !== null) {
    $router->get('/kontakt', function (): void {
        echo '<h1>Kontaktformular</h1><p>Modul aktiv.</p>';
    });
}
