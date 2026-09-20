<?php
declare(strict_types=1);

/**
 * Zentrales Admin-Layout mit Bootstrap 5 (CDN)
 *
 * Erwartete Variablen:
 * - string $title (Seitentitel)
 * - string $content (HTML-Block des Seiteninhalts)
 * - ?string $currentRoute (aktuelle Route zur Navigation-Hervorhebung)
 */

$title = $title ?? 'CMS Admin';
$currentRoute = $currentRoute ?? (string) ($_GET['route'] ?? 'admin');
$currentRouteNormalized = ltrim($currentRoute, '/');

// Benutzer-E-Mail aus Session oder Auth ermitteln
$activeEmail = $_SESSION['magic_email'] 
    ?? ($_SESSION['user_email'] 
    ?? (isset($user['email']) ? (string) $user['email'] : 'Admin'));

// Theme-Ermittlung für den aktuellen Benutzer (light, dark, system)
$currentUser = class_exists('Auth') ? Auth::currentUser() : null;
$userTheme = 'system';
if ($currentUser !== null && !empty($currentUser['theme_mode'])) {
    $userTheme = (string) $currentUser['theme_mode'];
} elseif (!empty($_SESSION['theme_mode'])) {
    $userTheme = (string) $_SESSION['theme_mode'];
}
if (!in_array($userTheme, ['light', 'dark', 'system'], true)) {
    $userTheme = 'system';
}
$csrfToken = class_exists('Csrf') ? Csrf::getToken() : '';

// =========================================================================
// Berechtigungs- und Sichtbarkeitsprüfung für Navigation:
// - Wenn $_SESSION['magic_authenticated'] === true ist, hat der Benutzer
//   als Superadmin vollen Zugriff und sieht ausnahmslos alle Menüpunkte.
// - Bei regulären User-Sessions greift Rbac::can().
// - Fallback: Falls Rbac nicht verfügbar ist, wird der Menüpunkt angezeigt.
// =========================================================================
$isMagicAdmin = !empty($_SESSION['magic_authenticated']);

$canViewNav = static function (array|string $permissions) use ($isMagicAdmin): bool {
    // 1. Superadmin-Bypass: Magic-Code-Admin sieht immer alle Menüpunkte
    if ($isMagicAdmin) {
        return true;
    }

    // 2. RBAC-Prüfung für reguläre Benutzer
    if (class_exists('Rbac') && method_exists('Rbac', 'can')) {
        $list = (array) $permissions;
        foreach ($list as $perm) {
            if (Rbac::can((string) $perm)) {
                return true;
            }
        }
        return false;
    }

    // 3. Fallback während der Entwicklung (falls Rbac nicht initialisiert ist)
    return true;
};

// Prüfung, ob das Kontaktformular-Modul aktiv geschaltet ist
$isContactFormEnabled = class_exists('ModuleManager') && ModuleManager::isEnabled('contact_form');

// Prüfung, ob die Route für Activity/Logs registriert ist
$hasActivityRoute = (class_exists('Router') && Router::hasRoute('/admin/activity'))
    || (isset($_GET['route']) && str_starts_with((string) $_GET['route'], 'admin/activity'));
?>
<!doctype html>
<html lang="de" data-bs-theme="<?= $userTheme === 'dark' ? 'dark' : ($userTheme === 'light' ? 'light' : 'auto') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?> - CMS Admin</title>

    <!-- Bootstrap 5 CSS via CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <script>
        (function() {
            var theme = <?= json_encode($userTheme) ?>;
            function applyTheme(t) {
                var effective = t;
                if (t === 'system') {
                    effective = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
                }
                document.documentElement.setAttribute('data-bs-theme', effective);
            }
            applyTheme(theme);
            if (theme === 'system' && window.matchMedia) {
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function() {
                    applyTheme('system');
                });
            }
        })();
    </script>

    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8f9fa;
        }
        .sidebar,
        #sidebarMenu,
        .sidebar.offcanvas-md {
            min-height: calc(100vh - 56px);
            background-color: #212529 !important;
            color: #f8f9fa !important;
        }
        @media (max-width: 767.98px) {
            .sidebar,
            #sidebarMenu,
            .sidebar.offcanvas-md {
                min-height: 100vh;
                background-color: #212529 !important;
            }
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.85) !important;
            padding: 0.65rem 1rem;
            border-radius: 0.375rem;
            margin-bottom: 0.2rem;
            display: flex;
            align-items: center;
            font-size: 0.95rem;
            text-decoration: none;
            transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out;
        }
        .sidebar .nav-link:hover {
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.15) !important;
        }
        .sidebar .nav-link.active {
            color: #ffffff !important;
            background-color: #0d6efd !important;
            font-weight: 600;
        }
        .sidebar .nav-link svg {
            flex-shrink: 0;
            color: rgba(255, 255, 255, 0.85);
        }
        .sidebar .nav-link:hover svg,
        .sidebar .nav-link.active svg {
            color: #ffffff;
        }
        .code-highlight {
            font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 1.15rem;
            letter-spacing: 2px;
        }

        /* ------------------------------------------------------------- */
        /* PRO-USER-THEME STYLING (Hell, Dunkel, System)                 */
        /* ------------------------------------------------------------- */
        body.admin-theme-dark,
        [data-bs-theme="dark"] body {
            background-color: #121212 !important;
            color: #f5f5f5 !important;
        }
        body.admin-theme-dark main,
        [data-bs-theme="dark"] main {
            background-color: #16181b !important;
            color: #f5f5f5 !important;
        }
        body.admin-theme-dark .card,
        [data-bs-theme="dark"] .card {
            background-color: #212529 !important;
            color: #f5f5f5 !important;
            border-color: #343a40 !important;
        }
        body.admin-theme-dark .card-header,
        [data-bs-theme="dark"] .card-header {
            background-color: #282c31 !important;
            color: #ffffff !important;
            border-bottom-color: #343a40 !important;
        }
        body.admin-theme-dark .list-group-item,
        [data-bs-theme="dark"] .list-group-item {
            background-color: #212529 !important;
            color: #e0e0e0 !important;
            border-color: #343a40 !important;
        }
        body.admin-theme-dark .text-dark,
        [data-bs-theme="dark"] .text-dark {
            color: #f8f9fa !important;
        }
        body.admin-theme-dark .text-muted,
        [data-bs-theme="dark"] .text-muted {
            color: #9da5af !important;
        }
        body.admin-theme-dark .border-bottom,
        [data-bs-theme="dark"] .border-bottom {
            border-color: #343a40 !important;
        }
        body.admin-theme-dark .border-top,
        [data-bs-theme="dark"] .border-top {
            border-color: #343a40 !important;
        }
        body.admin-theme-dark .table,
        [data-bs-theme="dark"] .table {
            --bs-table-bg: #212529;
            --bs-table-color: #f5f5f5;
            --bs-table-border-color: #343a40;
        }
        body.admin-theme-dark .table-light,
        [data-bs-theme="dark"] .table-light {
            --bs-table-bg: #2a2e33;
            --bs-table-color: #ffffff;
        }
        body.admin-theme-dark footer,
        [data-bs-theme="dark"] footer {
            color: #9da5af !important;
        }
        body.admin-theme-dark .navbar,
        body.admin-theme-dark .sidebar {
            background-color: #0d0e11 !important;
        }
        body.admin-theme-light {
            background-color: #f8f9fa !important;
            color: #212529 !important;
        }
    </style>
</head>
<body class="admin-theme-<?= htmlspecialchars($userTheme, ENT_QUOTES, 'UTF-8') ?>">
    <!-- Top-Navigationsleiste -->
    <header class="navbar navbar-dark bg-dark sticky-top flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3 fs-6 text-white fw-bold" href="?route=admin">
            CMS Admin
        </a>

        <!-- Mobile Burger-Menu Button -->
        <button class="navbar-toggler position-absolute end-0 d-md-none collapsed m-2" type="button" 
                data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" 
                aria-controls="sidebarMenu" aria-expanded="false" aria-label="Navigation umschalten">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Rechte Navbar-Elemente (Theme-Toggle, E-Mail & Logout) -->
        <div class="d-none d-md-flex align-items-center ms-auto px-3">
            <!-- Pro-User-Theme Switcher Dropdown -->
            <div class="dropdown me-3">
                <button class="btn btn-sm btn-outline-light dropdown-toggle d-inline-flex align-items-center" type="button" id="themeDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="Design/Theme umschalten">
                    <?php if ($userTheme === 'light'): ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" class="bi bi-sun-fill text-warning me-1" viewBox="0 0 16 16">
                            <path d="M8 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8M8 0a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 0m0 13a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 13m8-5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2a.5.5 0 0 1 .5.5M3 8a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2A.5.5 0 0 1 3 8m10.657-5.657a.5.5 0 0 1 0 .707l-1.414 1.415a.5.5 0 1 1-.707-.708l1.414-1.414a.5.5 0 0 1 .707 0m-9.193 9.193a.5.5 0 0 1 0 .707L3.05 13.657a.5.5 0 0 1-.707-.707l1.414-1.414a.5.5 0 0 1 .707 0m9.193 2.121a.5.5 0 0 1-.707 0l-1.414-1.414a.5.5 0 0 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .707M4.464 4.465a.5.5 0 0 1-.707 0L2.343 3.05a.5.5 0 1 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .708"/>
                        </svg>
                        <span>Hell</span>
                    <?php elseif ($userTheme === 'dark'): ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" class="bi bi-moon-stars-fill text-info me-1" viewBox="0 0 16 16">
                            <path d="M6 .278a.77.77 0 0 1 .08.858 7.2 7.2 0 0 0-.878 3.46c0 4.021 3.278 7.277 7.318 7.277q.792-.001 1.533-.16a.79.79 0 0 1 .81.316.73.73 0 0 1-.031.893A8.35 8.35 0 0 1 8.344 16C3.734 16 0 12.286 0 7.71 0 4.266 2.114 1.312 5.124.06A.75.75 0 0 1 6 .278"/>
                            <path d="M10.794 3.148a.217.217 0 0 1 .412 0l.387 1.162c.173.518.579.924 1.097 1.097l1.162.387a.217.217 0 0 1 0 .412l-1.162.387a1.73 1.73 0 0 0-1.097 1.097l-.387 1.162a.217.217 0 0 1-.412 0l-.387-1.162A1.73 1.73 0 0 0 9.3 6.206l-1.162-.387a.217.217 0 0 1 0-.412l1.162-.387a1.73 1.73 0 0 0 1.097-1.097zM13.863.099a.145.145 0 0 1 .274 0l.258.774c.115.346.386.617.732.732l.774.258a.145.145 0 0 1 0 .274l-.774.258a1.16 1.16 0 0 0-.732.732l-.258.774a.145.145 0 0 1-.274 0l-.258-.774a1.16 1.16 0 0 0-.732-.732l-.774-.258a.145.145 0 0 1 0-.274l.774-.258c.346-.115.617-.386.732-.732z"/>
                        </svg>
                        <span>Dunkel</span>
                    <?php else: ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" class="bi bi-display text-secondary me-1" viewBox="0 0 16 16">
                            <path d="M0 4s0-2 2-2h12s2 0 2 2v6s0 2-2 2h-4q0 1 .25 1.5H11a.5.5 0 0 1 0 1H5a.5.5 0 0 1 0-1h.75Q6 13 6 12H2s-2 0-2-2zm1.398-.855a.76.76 0 0 0-.253.538L1 4v6c0 .193.07.366.184.498l.061.054c.088.067.195.11.31.11h12.89q.166 0 .31-.11l.061-.054a.76.76 0 0 0 .184-.498V4a.76.76 0 0 0-.253-.538l-.061-.055A.76.76 0 0 0 14.39 3H1.61q-.166 0-.31.11z"/>
                        </svg>
                        <span>System</span>
                    <?php endif; ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="themeDropdown">
                    <li><h6 class="dropdown-header">Darstellung</h6></li>
                    <li>
                        <form method="POST" action="?route=admin/theme">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="redirect" value="<?= htmlspecialchars($currentRoute, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="theme" value="light">
                            <button type="submit" class="dropdown-item d-flex align-items-center <?= $userTheme === 'light' ? 'active fw-bold' : '' ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-sun-fill text-warning me-2" viewBox="0 0 16 16">
                                    <path d="M8 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8M8 0a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 0m0 13a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 13m8-5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2a.5.5 0 0 1 .5.5M3 8a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2A.5.5 0 0 1 3 8m10.657-5.657a.5.5 0 0 1 0 .707l-1.414 1.415a.5.5 0 1 1-.707-.708l1.414-1.414a.5.5 0 0 1 .707 0m-9.193 9.193a.5.5 0 0 1 0 .707L3.05 13.657a.5.5 0 0 1-.707-.707l1.414-1.414a.5.5 0 0 1 .707 0m9.193 2.121a.5.5 0 0 1-.707 0l-1.414-1.414a.5.5 0 0 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .707M4.464 4.465a.5.5 0 0 1-.707 0L2.343 3.05a.5.5 0 1 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .708"/>
                                </svg>
                                Hell
                            </button>
                        </form>
                    </li>
                    <li>
                        <form method="POST" action="?route=admin/theme">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="redirect" value="<?= htmlspecialchars($currentRoute, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="theme" value="dark">
                            <button type="submit" class="dropdown-item d-flex align-items-center <?= $userTheme === 'dark' ? 'active fw-bold' : '' ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-moon-stars-fill text-info me-2" viewBox="0 0 16 16">
                                    <path d="M6 .278a.77.77 0 0 1 .08.858 7.2 7.2 0 0 0-.878 3.46c0 4.021 3.278 7.277 7.318 7.277q.792-.001 1.533-.16a.79.79 0 0 1 .81.316.73.73 0 0 1-.031.893A8.35 8.35 0 0 1 8.344 16C3.734 16 0 12.286 0 7.71 0 4.266 2.114 1.312 5.124.06A.75.75 0 0 1 6 .278"/>
                                    <path d="M10.794 3.148a.217.217 0 0 1 .412 0l.387 1.162c.173.518.579.924 1.097 1.097l1.162.387a.217.217 0 0 1 0 .412l-1.162.387a1.73 1.73 0 0 0-1.097 1.097l-.387 1.162a.217.217 0 0 1-.412 0l-.387-1.162A1.73 1.73 0 0 0 9.3 6.206l-1.162-.387a.217.217 0 0 1 0-.412l1.162-.387a1.73 1.73 0 0 0 1.097-1.097zM13.863.099a.145.145 0 0 1 .274 0l.258.774c.115.346.386.617.732.732l.774.258a.145.145 0 0 1 0 .274l-.774.258a1.16 1.16 0 0 0-.732.732l-.258.774a.145.145 0 0 1-.274 0l-.258-.774a1.16 1.16 0 0 0-.732-.732l-.774-.258a.145.145 0 0 1 0-.274l.774-.258c.346-.115.617-.386.732-.732z"/>
                                </svg>
                                Dunkel
                            </button>
                        </form>
                    </li>
                    <li>
                        <form method="POST" action="?route=admin/theme">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="redirect" value="<?= htmlspecialchars($currentRoute, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="theme" value="system">
                            <button type="submit" class="dropdown-item d-flex align-items-center <?= $userTheme === 'system' ? 'active fw-bold' : '' ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-display text-secondary me-2" viewBox="0 0 16 16">
                                    <path d="M0 4s0-2 2-2h12s2 0 2 2v6s0 2-2 2h-4q0 1 .25 1.5H11a.5.5 0 0 1 0 1H5a.5.5 0 0 1 0-1h.75Q6 13 6 12H2s-2 0-2-2zm1.398-.855a.76.76 0 0 0-.253.538L1 4v6c0 .193.07.366.184.498l.061.054c.088.067.195.11.31.11h12.89q.166 0 .31-.11l.061-.054a.76.76 0 0 0 .184-.498V4a.76.76 0 0 0-.253-.538l-.061-.055A.76.76 0 0 0 14.39 3H1.61q-.166 0-.31.11z"/>
                                </svg>
                                System (Standard)
                            </button>
                        </form>
                    </li>
                </ul>
            </div>

            <span class="text-light small me-3">
                Angemeldet: <strong><?= htmlspecialchars($activeEmail, ENT_QUOTES, 'UTF-8') ?></strong>
            </span>
            <a class="btn btn-outline-light btn-sm" href="?route=logout">
                Abmelden
            </a>
        </div>
    </header>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar Navigation (Desktop sichtbar, Mobile als Offcanvas) -->
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar offcanvas-md offcanvas-start p-3 bg-dark text-bg-dark" 
                 data-bs-theme="dark" tabindex="-1" aria-labelledby="sidebarMenuLabel">
                <div class="offcanvas-header d-md-none border-bottom border-secondary pb-2 mb-3">
                    <h5 class="offcanvas-title text-white" id="sidebarMenuLabel">CMS Admin</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#sidebarMenu" aria-label="Schließen"></button>
                </div>

                <!-- Mobile Benutzerinfo & Theme in der Sidebar -->
                <div class="d-md-none mb-3 pb-3 border-bottom border-secondary">
                    <small class="text-white-50 d-block">Angemeldet als:</small>
                    <span class="fw-semibold text-white text-break"><?= htmlspecialchars($activeEmail, ENT_QUOTES, 'UTF-8') ?></span>
                    
                    <div class="mt-2 mb-2">
                        <small class="text-white-50 d-block mb-1">Design / Theme:</small>
                        <div class="btn-group w-100 btn-group-sm" role="group">
                            <form method="POST" action="?route=admin/theme" class="d-inline flex-fill">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="redirect" value="<?= htmlspecialchars($currentRoute, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="theme" value="light">
                                <button type="submit" class="btn btn-outline-light w-100 <?= $userTheme === 'light' ? 'active' : '' ?>" title="Hell">
                                    ☀️ Hell
                                </button>
                            </form>
                            <form method="POST" action="?route=admin/theme" class="d-inline flex-fill">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="redirect" value="<?= htmlspecialchars($currentRoute, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="theme" value="dark">
                                <button type="submit" class="btn btn-outline-light w-100 <?= $userTheme === 'dark' ? 'active' : '' ?>" title="Dunkel">
                                    🌙 Dunkel
                                </button>
                            </form>
                            <form method="POST" action="?route=admin/theme" class="d-inline flex-fill">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="redirect" value="<?= htmlspecialchars($currentRoute, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="theme" value="system">
                                <button type="submit" class="btn btn-outline-light w-100 <?= $userTheme === 'system' ? 'active' : '' ?>" title="System">
                                    💻 Auto
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="mt-2">
                        <a href="?route=logout" class="btn btn-sm btn-outline-danger w-100">Abmelden</a>
                    </div>
                </div>

                <div class="position-sticky pt-2">
                    <ul class="nav flex-column mb-auto">
                        <!-- 1. Dashboard -->
                        <?php if ($canViewNav(['admin.dashboard.view', 'admin.dashboard'])): 
                            $isDashActive = in_array($currentRouteNormalized, ['admin', ''], true);
                        ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $isDashActive ? 'active' : '' ?>" href="?route=admin">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-speedometer2 me-2" viewBox="0 0 16 16">
                                    <path d="M8 4a.5.5 0 0 1 .5.5V6a.5.5 0 0 1-1 0V4.5A.5.5 0 0 1 8 4M3.732 5.732a.5.5 0 0 1 .707 0l.915.914a.5.5 0 1 1-.708.708l-.914-.915a.5.5 0 0 1 0-.707M2 10a.5.5 0 0 1 .5-.5h1.586a.5.5 0 0 1 0 1H2.5A.5.5 0 0 1 2 10m9.5 0a.5.5 0 0 1 .5-.5h1.5a.5.5 0 0 1 0 1H12a.5.5 0 0 1-.5-.5m.754-4.246a.39.39 0 0 0-.527-.024l-.454.455a.5.5 0 0 0 .707.707l.455-.454a.39.39 0 0 0-.181-.684z"/>
                                    <path d="M0 10a8 8 0 1 1 15.547 2.661c-.442 1.253-1.845 1.602-2.932 1.25-1.026-.33-2.023-.974-3.11-1.911-.476-.41-1.002-.87-1.505-1.341v-.002A5 5 0 1 0 2.89 12.18c.386.417.804.819 1.25 1.196.447.377.934.73 1.455 1.054 1.134.704 2.455 1.194 3.738 1.464A8 8 0 0 1 0 10m7.5-6a6.5 6.5 0 1 0 0 13 6.5 6.5 0 0 0 0-13"/>
                                </svg>
                                Dashboard
                            </a>
                        </li>
                        <?php endif; ?>

                        <!-- 1.1 Startseite bearbeiten -->
                        <?php if ($canViewNav(['admin.homepage.manage', 'admin.homepage.view', 'admin.homepage', 'admin.settings'])): 
                            $isHomepageActive = str_starts_with($currentRouteNormalized, 'admin/homepage');
                        ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $isHomepageActive ? 'active' : '' ?>" href="?route=admin/homepage">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-window-sidebar me-2" viewBox="0 0 16 16">
                                    <path d="M2.5 4a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1m2-.5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0m1 .5a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1"/>
                                    <path d="M2 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2zm12 1a1 1 0 0 1 1 1v2H1V3a1 1 0 0 1 1-1zM1 13V6h4v8H2a1 1 0 0 1-1-1m5 1V6h9v7a1 1 0 0 1-1 1z"/>
                                </svg>
                                Startseite
                            </a>
                        </li>
                        <?php endif; ?>

                        <!-- 2. Magic Codes (Verwaltung) -->
                        <?php if ($canViewNav(['admin.magic_codes.manage', 'admin.magic_codes.view', 'admin.magic_codes'])): 
                            $isMagicActive = str_starts_with($currentRouteNormalized, 'admin/magic-codes') || str_starts_with($currentRouteNormalized, 'admin/magic-code');
                        ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $isMagicActive ? 'active' : '' ?>" href="?route=admin/magic-codes">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-key-fill me-2" viewBox="0 0 16 16">
                                    <path d="M3.5 11.5a3.5 3.5 0 1 1 3.163-5H14L15.5 8 14 9.5l-1-1-1 1-1-1-1 1-1.5-1.5H6.663a3.5 3.5 0 0 1-3.163 3.5M2.5 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2"/>
                                </svg>
                                Magic Codes
                            </a>
                        </li>
                        <?php endif; ?>

                        <!-- 3. Benutzer -->
                        <?php if ($canViewNav(['admin.users.manage', 'admin.users.view', 'admin.users'])): 
                            $isUsersActive = str_starts_with($currentRouteNormalized, 'admin/users') || str_starts_with($currentRouteNormalized, 'admin/user');
                        ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $isUsersActive ? 'active' : '' ?>" href="?route=admin/users">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-people-fill me-2" viewBox="0 0 16 16">
                                    <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.24 2.24 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.3 6.3 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5"/>
                                </svg>
                                Benutzer
                            </a>
                        </li>
                        <?php endif; ?>

                        <!-- 4. Rollen & Rechte -->
                        <?php if ($canViewNav(['admin.rbac.manage', 'admin.roles.manage', 'admin.roles.view', 'admin.rbac'])): 
                            $isRolesActive = str_starts_with($currentRouteNormalized, 'admin/roles') || str_starts_with($currentRouteNormalized, 'admin/role');
                        ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $isRolesActive ? 'active' : '' ?>" href="?route=admin/roles">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-shield-lock-fill me-2" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M8 0c-.69 0-1.843.265-2.928.56-1.11.3-2.229.655-2.887.87a1.54 1.54 0 0 0-1.044 1.262c-.596 4.477.787 7.795 2.465 9.99a11.8 11.8 0 0 0 2.517 2.453c.386.273.744.482 1.048.625.28.132.581.24.829.24s.548-.108.829-.24a7 7 0 0 0 1.048-.625 11.8 11.8 0 0 0 2.517-2.453c1.678-2.195 3.061-5.513 2.465-9.99a1.54 1.54 0 0 0-1.044-1.263 63 63 0 0 0-2.887-.87C9.843.266 8.69 0 8 0m0 5a1.5 1.5 0 0 1 .5 2.915l.385 1.99a.5.5 0 0 1-.491.595h-.788a.5.5 0 0 1-.49-.595l.384-1.99A1.5 1.5 0 0 1 8 5"/>
                                </svg>
                                Rollen &amp; Rechte
                            </a>
                        </li>
                        <?php endif; ?>

                        <!-- 5. Module -->
                        <?php if ($canViewNav(['admin.modules.manage', 'admin.modules.view', 'admin.modules'])): 
                            $isModulesActive = str_starts_with($currentRouteNormalized, 'admin/modules') || str_starts_with($currentRouteNormalized, 'admin/module');
                        ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $isModulesActive ? 'active' : '' ?>" href="?route=admin/modules">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-puzzle-fill me-2" viewBox="0 0 16 16">
                                    <path d="M3.112 5.112a3.105 3.105 0 0 0-.17.613H1.5A1.5 1.5 0 0 0 0 7.225v2.493c0 .828.672 1.5 1.5 1.5h1.442c.045.218.102.43.17.613a3.105 3.105 0 0 0 2.87 2.169h.105c.828 0 1.5-.672 1.5-1.5v-1.442a3.1 3.1 0 0 0 .613-.17 3.105 3.105 0 0 0 2.169-2.87v-.105c0-.828-.672-1.5-1.5-1.5h-1.442a3.1 3.1 0 0 0-.17-.613 3.105 3.105 0 0 0-2.87-2.169h-.105c-.828 0-1.5.672-1.5 1.5v1.442a3.1 3.1 0 0 0-.613.17 3.105 3.105 0 0 0-2.169 2.87v.105z"/>
                                </svg>
                                Module
                            </a>
                        </li>
                        <?php endif; ?>

                        <!-- 6. Kontaktformular (Admin) - nur sichtbar, wenn Modul contact_form aktiv ist -->
                        <?php if ($isContactFormEnabled && $canViewNav(['module.contact_form.view', 'module.contact_form.manage', 'module.contact_form', 'admin.contact_form.view'])): 
                            $isContactActive = str_starts_with($currentRouteNormalized, 'admin/contact-form') || str_starts_with($currentRouteNormalized, 'admin/contact');
                        ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $isContactActive ? 'active' : '' ?>" href="?route=admin/contact-form">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-envelope-paper-fill me-2" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M6.5 9.5 3 7.5v-6A1.5 1.5 0 0 1 4.5 0h7A1.5 1.5 0 0 1 13 1.5v6l-3.5 2L8 8.75zM1.059 3.635 2 3.133v3.753L0 5.713V4.5a1.5 1.5 0 0 1 1.059-.865M16 5.713l-2 1.173V3.133l.941.502A1.5 1.5 0 0 1 16 4.5zm0 2.115-3.5 2.05-1.848-1.082L8 10.222l-2.652-1.441L3.5 9.878 0 7.828V14.5A1.5 1.5 0 0 0 1.5 16h13a1.5 1.5 0 0 0 1.5-1.5z"/>
                                </svg>
                                Kontaktformular
                            </a>
                        </li>
                        <?php endif; ?>

                        <!-- 7. Activity / Logs - nur sichtbar, falls Route registriert ist -->
                        <?php if ($hasActivityRoute && $canViewNav(['admin.activity.view', 'admin.activity.manage', 'admin.activity', 'admin.logs.view'])): 
                            $isActivityActive = str_starts_with($currentRouteNormalized, 'admin/activity') || str_starts_with($currentRouteNormalized, 'admin/log');
                        ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $isActivityActive ? 'active' : '' ?>" href="?route=admin/activity">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-journal-text me-2" viewBox="0 0 16 16">
                                    <path d="M5 10.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5m0-2a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5m0-2a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5m0-2a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5"/>
                                    <path d="M3 0h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2v-1h1v1a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H3a1 1 0 0 0-1 1v1H1V2a2 2 0 0 1 2-2"/>
                                    <path d="M1 5v-.5a.5.5 0 0 1 1 0V5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1zm0 3v-.5a.5.5 0 0 1 1 0V8h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1zm0 3v-.5a.5.5 0 0 1 1 0v.5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1z"/>
                                </svg>
                                Activity / Logs
                            </a>
                        </li>
                        <?php endif; ?>

                        <!-- 8. Dynamisch durch aktive Module registrierte Menüpunkte -->
                        <?php if (class_exists('ModuleManager') && !empty(ModuleManager::getAdminMenuItems())): ?>
                            <?php 
                            $customMenuItems = array_filter(ModuleManager::getAdminMenuItems(), function ($item) {
                                $route = ltrim((string) ($item['route'] ?? ''), '/');
                                return $route !== 'admin/contact-form' && $route !== 'admin/contact';
                            });
                            ?>
                            <?php if (!empty($customMenuItems)): ?>
                                <li class="nav-item pt-2 pb-1 px-3">
                                    <span class="text-uppercase text-white-50 fw-semibold small" style="font-size: 0.72rem; letter-spacing: 0.5px;">Weitere Module</span>
                                </li>
                                <?php foreach ($customMenuItems as $item): 
                                    $isItemActive = ($currentRouteNormalized === ltrim((string) ($item['route'] ?? ''), '/'));
                                ?>
                                <li class="nav-item">
                                    <a class="nav-link <?= $isItemActive ? 'active' : '' ?>" 
                                       href="<?= htmlspecialchars((string) ($item['url'] ?? '#'), ENT_QUOTES, 'UTF-8') ?>">
                                        <?php if (!empty($item['icon'])): ?>
                                            <span class="me-2 d-inline-flex align-items-center"><?= $item['icon'] ?></span>
                                        <?php else: ?>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-puzzle me-2" viewBox="0 0 16 16">
                                                <path d="M3.112 5.112a3.105 3.105 0 0 0-.17.613H1.5A1.5 1.5 0 0 0 0 7.225v2.493c0 .828.672 1.5 1.5 1.5h1.442c.045.218.102.43.17.613a3.105 3.105 0 0 0 2.87 2.169h.105c.828 0 1.5-.672 1.5-1.5v-1.442a3.1 3.1 0 0 0 .613-.17 3.105 3.105 0 0 0 2.169-2.87v-.105c0-.828-.672-1.5-1.5-1.5h-1.442a3.1 3.1 0 0 0-.17-.613 3.105 3.105 0 0 0-2.87-2.169h-.105c-.828 0-1.5.672-1.5 1.5v1.442a3.1 3.1 0 0 0-.613.17 3.105 3.105 0 0 0-2.169 2.87v.105z"/>
                                            </svg>
                                        <?php endif; ?>
                                        <?= htmlspecialchars((string) ($item['label'] ?? 'Modul'), ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </ul>

                    <hr class="border-secondary my-3">

                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="?route=/">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-box-arrow-up-right me-2" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M8.636 3.5a.5.5 0 0 0-.5-.5H1.5A1.5 1.5 0 0 0 0 4.5v10A1.5 1.5 0 0 0 1.5 16h10a1.5 1.5 0 0 0 1.5-1.5V7.864a.5.5 0 0 0-1 0V14.5a.5.5 0 0 1-.5.5h-10a.5.5 0 0 1-.5-.5v-10a.5.5 0 0 1 .5-.5h6.636a.5.5 0 0 0 .5-.5"/>
                                    <path fill-rule="evenodd" d="M16 .5a.5.5 0 0 0-.5-.5h-5a.5.5 0 0 0 0 1h3.793L6.146 9.146a.5.5 0 1 0 .708.708L15 1.707V5.5a.5.5 0 0 0 1 0z"/>
                                </svg>
                                Zur Startseite
                            </a>
                        </li>
                    </ul>

                    <div class="mt-auto pt-4 pb-2 text-center text-white-50 small" style="font-size: 0.78rem;">
                        &copy; <?= date('Y') ?> StudioCreativo
                    </div>
                </div>
            </nav>

            <!-- Zentraler Inhaltsbereich -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4 bg-light min-vh-100 d-flex flex-column">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-4 border-bottom">
                    <h1 class="h2 mb-0"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
                </div>

                <div class="flex-grow-1">
                    <?= $content ?>
                </div>

                <footer class="pt-4 mt-5 border-top text-center text-muted small">
                    &copy; <?= date('Y') ?> StudioCreativo
                </footer>
            </main>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle via CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
