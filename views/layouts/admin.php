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
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?> - CMS Admin</title>

    <!-- Bootstrap 5 CSS via CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8f9fa;
        }
        .sidebar {
            min-height: calc(100vh - 56px);
            background-color: #212529;
        }
        @media (max-width: 767.98px) {
            .sidebar {
                min-height: 100vh;
            }
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.75);
            padding: 0.65rem 1rem;
            border-radius: 0.375rem;
            margin-bottom: 0.2rem;
            display: flex;
            align-items: center;
            font-size: 0.95rem;
            transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out;
        }
        .sidebar .nav-link:hover {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
        }
        .sidebar .nav-link.active {
            color: #fff;
            background-color: #0d6efd;
            font-weight: 600;
        }
        .code-highlight {
            font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 1.15rem;
            letter-spacing: 2px;
        }
    </style>
</head>
<body>
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

        <!-- Rechte Navbar-Elemente (E-Mail & Logout) -->
        <div class="d-none d-md-flex align-items-center ms-auto px-3">
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
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar offcanvas-md offcanvas-start p-3 text-white" 
                 tabindex="-1" aria-labelledby="sidebarMenuLabel">
                <div class="offcanvas-header d-md-none border-bottom border-secondary pb-2 mb-3">
                    <h5 class="offcanvas-title text-white" id="sidebarMenuLabel">CMS Admin</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#sidebarMenu" aria-label="Schließen"></button>
                </div>

                <!-- Mobile Benutzerinfo in der Sidebar -->
                <div class="d-md-none mb-3 pb-3 border-bottom border-secondary">
                    <small class="text-white-50 d-block">Angemeldet als:</small>
                    <span class="fw-semibold text-white text-break"><?= htmlspecialchars($activeEmail, ENT_QUOTES, 'UTF-8') ?></span>
                    <div class="mt-2">
                        <a href="?route=logout" class="btn btn-sm btn-outline-danger w-100">Abmelden</a>
                    </div>
                </div>

                <div class="position-sticky pt-2">
                    <ul class="nav flex-column mb-auto">
                        <li class="nav-item">
                            <a class="nav-link <?= in_array($currentRouteNormalized, ['admin', ''], true) ? 'active' : '' ?>" 
                               href="?route=admin">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-speedometer2 me-2" viewBox="0 0 16 16">
                                    <path d="M8 4a.5.5 0 0 1 .5.5V6a.5.5 0 0 1-1 0V4.5A.5.5 0 0 1 8 4M3.732 5.732a.5.5 0 0 1 .707 0l.915.914a.5.5 0 1 1-.708.708l-.914-.915a.5.5 0 0 1 0-.707M2 10a.5.5 0 0 1 .5-.5h1.586a.5.5 0 0 1 0 1H2.5A.5.5 0 0 1 2 10m9.5 0a.5.5 0 0 1 .5-.5h1.5a.5.5 0 0 1 0 1H12a.5.5 0 0 1-.5-.5m.754-4.246a.39.39 0 0 0-.527-.024l-.454.455a.5.5 0 0 0 .707.707l.455-.454a.39.39 0 0 0-.181-.684z"/>
                                    <path d="M0 10a8 8 0 1 1 15.547 2.661c-.442 1.253-1.845 1.602-2.932 1.25-1.026-.33-2.023-.974-3.11-1.911-.476-.41-1.002-.87-1.505-1.341v-.002A5 5 0 1 0 2.89 12.18c.386.417.804.819 1.25 1.196.447.377.934.73 1.455 1.054 1.134.704 2.455 1.194 3.738 1.464A8 8 0 0 1 0 10m7.5-6a6.5 6.5 0 1 0 0 13 6.5 6.5 0 0 0 0-13"/>
                                </svg>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentRouteNormalized === 'admin/magic-codes' ? 'active' : '' ?>" 
                               href="?route=admin/magic-codes">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-key-fill me-2" viewBox="0 0 16 16">
                                    <path d="M3.5 11.5a3.5 3.5 0 1 1 3.163-5H14L15.5 8 14 9.5l-1-1-1 1-1-1-1 1-1.5-1.5H6.663a3.5 3.5 0 0 1-3.163 3.5M2.5 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2"/>
                                </svg>
                                Magic Codes
                            </a>
                        </li>
                        <?php if (class_exists('ModuleManager') && !empty(ModuleManager::getAdminMenuItems())): ?>
                            <li class="nav-item pt-2 pb-1 px-3">
                                <span class="text-uppercase text-white-50 fw-semibold small" style="font-size: 0.72rem; letter-spacing: 0.5px;">Module</span>
                            </li>
                            <?php foreach (ModuleManager::getAdminMenuItems() as $item): 
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
                </div>
            </nav>

            <!-- Zentraler Inhaltsbereich -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4 bg-light min-vh-100">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-4 border-bottom">
                    <h1 class="h2 mb-0"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
                </div>

                <?= $content ?>
            </main>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle via CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
