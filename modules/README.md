# CMS Module

In diesem Verzeichnis liegen alle CMS-Module. Jedes Modul befindet sich in einem eigenen Unterordner, benannt nach seinem Modul-Key (z.B. `contact_form`).

## Modulstruktur

Ein typisches Modul sieht beispielsweise so aus:

```text
modules/
└── contact_form/
    ├── module.php        (Metadaten: name, description, version)
    ├── bootstrap.php     (Routen-Registrierung via $router, Admin-Menüpunkte)
    └── views/            (Modulspezifische Views)
```

### 1. `module.php` (Metadaten)

```php
<?php
return [
    'name' => 'Kontaktformular',
    'description' => 'Einfaches Kontaktformular mit E-Mail-Versand und Admin-Übersicht.',
    'version' => '1.0.0',
];
```

### 2. `bootstrap.php` (Routen & Admin-Menü)

```php
<?php
declare(strict_types=1);

/** @var Router|null $router */

// Frontend-Routen registrieren
if ($router !== null) {
    $router->get('/kontakt', function (): void {
        echo "Kontaktformular";
    });
}

// Admin-Menüpunkt registrieren
ModuleManager::addAdminMenuItem([
    'label' => 'Kontaktanfragen',
    'route' => 'admin/contact',
    'url' => '?route=admin/contact',
]);
```
