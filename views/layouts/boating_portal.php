<?php

declare(strict_types=1);

/**
 * Portal-Layout für die Bootsfahrschule (Kunden-Buchungsportal)
 *
 * Eigenständiges Frontend-Layout ohne Admin-Sidebar mit maritimem Design,
 * intuitiver Navigation und optimierter Mobilansicht.
 *
 * @var string $content
 * @var string|null $title
 * @var string|null $currentRoute
 */

$settings = class_exists('Settings') ? Settings::all() : [];
$siteTitle = $title ?? 'Bootsfahrschule - Fahrstunden & Bootsmiete buchen';
$companyName = class_exists('Settings')
    ? (string) (Settings::get('customer_name') ?: Settings::get('company_name') ?: Settings::get('site_name') ?: 'Bootsfahrschule & Motorboot-Akademie')
    : 'Bootsfahrschule & Motorboot-Akademie';

$currentRoute = $currentRoute ?? ($_GET['route'] ?? 'bootschule');

// Flash-Meldungen abfangen
$portalFlashSuccess = $_SESSION['flash_portal_success'] ?? ($_SESSION['flash_success'] ?? null);
$portalFlashError   = $_SESSION['flash_portal_error'] ?? ($_SESSION['flash_error'] ?? null);
$portalFlashInfo    = $_SESSION['flash_portal_info'] ?? null;
unset($_SESSION['flash_portal_success'], $_SESSION['flash_portal_error'], $_SESSION['flash_portal_info']);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($siteTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --bs-navy-primary: #0a3656;
            --bs-navy-dark: #062338;
            --bs-cyan-accent: #0284c7;
            --bs-cyan-light: #e0f2fe;
            --bs-body-bg: #fbfbfa;
            --bs-card-border: #e2e8f0;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--bs-body-bg);
            color: #0f172a;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            -webkit-font-smoothing: antialiased;
        }

        /* Modern UI Utilities & Enhancements */
        .portal-main-fluid {
            min-height: 100vh;
            background-color: #fbfbfa;
        }
        .portal-inner-container {
            max-width: 1440px;
            width: 100%;
            margin-left: auto;
            margin-right: auto;
        }
        .shadow-xs {
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
        }
        .shadow-sm {
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.08), 0 1px 2px -1px rgba(0, 0, 0, 0.08) !important;
        }
        .shadow-md {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.08), 0 2px 4px -2px rgba(0, 0, 0, 0.08) !important;
        }
        .rounded-4 {
            border-radius: 1.25rem !important; /* 20px */
        }
        .rounded-3xl {
            border-radius: 1.5rem !important; /* 24px */
        }
        .fw-extrabold {
            font-weight: 800 !important;
        }
        .p-3\.5 {
            padding: 1.15rem !important;
        }
        .gap-1\.5 {
            gap: 0.375rem !important;
        }
        .gap-2\.5 {
            gap: 0.625rem !important;
        }
        .shrink-0 {
            flex-shrink: 0 !important;
        }
        .bg-slate-50 {
            background-color: #f8fafc !important;
        }
        
        /* Light brown box backgrounds for high contrast and readability */
        .card, .card.bg-white, .portal-box {
            background-color: #f6f0e7 !important; /* Warm light-brown */
            border: 1px solid #e5dacb !important;
            border-radius: 1.25rem !important;
            box-shadow: 0 1px 3px rgba(45, 36, 33, 0.05);
            transition: all 0.2s ease-in-out;
        }
        .card:hover {
            border-color: #d8c7b2 !important;
            box-shadow: 0 4px 12px rgba(45, 36, 33, 0.08);
        }

        /* Sidebar Navigation Item Styling */
        .sidebar-nav-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.7rem 0.95rem;
            border-radius: 0.75rem;
            color: #d6d3d1 !important;
            text-decoration: none !important;
            font-size: 0.8125rem;
            font-weight: 500;
            transition: all 0.15s ease-in-out;
        }
        .sidebar-nav-link:hover {
            background-color: #3d322e !important;
            color: #ffffff !important;
        }
        .sidebar-nav-link.active {
            background-color: #4a3d38 !important;
            color: #ffffff !important;
            font-weight: 700;
            box-shadow: 0 1px 3px rgba(0,0,0,0.25);
            border-left: 3px solid #fbbf24;
            padding-left: 0.8rem;
        }
        .sidebar-nav-link .nav-icon {
            font-size: 1.05rem;
            line-height: 1;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.15s ease;
        }
        .sidebar-nav-link:hover .nav-icon {
            transform: scale(1.1);
        }
        .sidebar-nav-badge {
            font-size: 0.625rem;
            font-weight: 700;
            padding: 0.18rem 0.45rem;
            border-radius: 9999px;
            letter-spacing: 0.4px;
            line-height: 1;
            flex-shrink: 0;
        }

        /* Modal Dialog Styling with Light Brown Background & High Contrast */
        .modal-content {
            border-radius: 1.5rem !important;
            overflow: hidden;
            border: 1px solid #ded0be !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35) !important;
            background-color: #f6f0e7 !important;
        }
        .modal-header {
            background-color: #2d2421 !important;
            color: #ffffff !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.12) !important;
        }
        .modal-header .modal-title, .modal-header h1, .modal-header h2, .modal-header h3, .modal-header h4, .modal-header h5 {
            color: #ffffff !important;
        }
        .modal-header .text-muted, .modal-header .text-secondary, .modal-header span.small {
            color: rgba(255, 255, 255, 0.75) !important;
        }
        .modal-header .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%) !important;
        }
        .modal-body {
            background-color: #f6f0e7 !important; /* Light-brown for modal body */
            color: #1c1917 !important;
        }
        .modal-footer {
            background-color: #eee4d5 !important;
            border-top: 1px solid #e2d4c3 !important;
        }

        /* Crisp white form controls inside the light-brown boxes for optimal contrast */
        .form-control, .form-select {
            background-color: #ffffff !important;
            border: 1.5px solid #d5c5b2 !important;
            color: #1c1917 !important;
            font-weight: 500;
        }
        .form-control:focus, .form-select:focus {
            background-color: #ffffff !important;
            border-color: #b45309 !important;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.25) !important;
            color: #1c1917 !important;
        }
        label.form-label, .form-label {
            color: #292524 !important;
            font-weight: 600;
        }

        .portal-navbar {
            background: linear-gradient(135deg, var(--bs-navy-primary) 0%, var(--bs-navy-dark) 100%);
            box-shadow: 0 4px 15px rgba(10, 54, 86, 0.15);
        }

        .navbar-brand-logo {
            font-weight: 700;
            letter-spacing: -0.5px;
            color: #ffffff !important;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
        }

        .nav-link.portal-nav-link {
            color: rgba(255, 255, 255, 0.85) !important;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            border-radius: 0.375rem;
            transition: all 0.2s ease;
        }

        .nav-link.portal-nav-link:hover {
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .nav-link.portal-nav-link.active {
            color: #ffffff !important;
            background-color: rgba(2, 132, 199, 0.35);
            border-bottom: 2px solid #38bdf8;
        }

        .portal-btn-book {
            background-color: #38bdf8;
            color: #082f49 !important;
            font-weight: 600;
            border-radius: 9999px;
            padding: 0.45rem 1.25rem;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(56, 189, 248, 0.3);
        }

        .portal-btn-book:hover {
            background-color: #7dd3fc;
            transform: translateY(-1px);
        }

        main.portal-main {
            flex: 1 0 auto;
        }

        .portal-footer {
            background-color: #0f172a;
            color: #94a3b8;
            font-size: 0.9rem;
            margin-top: auto;
        }

        .portal-footer a {
            color: #cbd5e1;
            text-decoration: none;
            transition: color 0.15s ease;
        }

        .portal-footer a:hover {
            color: #38bdf8;
        }

        .badge-slot {
            font-weight: 600;
            font-size: 0.85rem;
            padding: 0.5rem 0.75rem;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.15s ease;
        }
    </style>
</head>
<body>
    <!-- Top-Navigation -->
    <?php if (empty($useSidebarLayout)): ?>
    <nav class="navbar navbar-expand-lg navbar-dark portal-navbar sticky-top">
        <div class="container">
            <a class="navbar-brand navbar-brand-logo" href="?route=bootschule">
                <img src="public/assets/nautic-logo-white.svg" 
                     alt="Nautic" 
                     height="34" 
                     style="height: 34px; width: auto;"
                     onerror="this.style.display='none'; document.getElementById('navbarTextLogo').style.display='inline-flex';">
                <span id="navbarTextLogo" style="display: none;" class="align-items-center gap-2">
                    <span class="p-1 bg-white bg-opacity-10 rounded-circle text-info d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <i class="bi bi-compass fs-5"></i>
                    </span>
                    <span><?= htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') ?></span>
                </span>
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#portalNav" aria-controls="portalNav" aria-expanded="false" aria-label="Navigation umschalten">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="portalNav">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center gap-1">
                    <li class="nav-item">
                        <a class="nav-link portal-nav-link <?= in_array($currentRoute, ['bootschule/me/appointments', '/bootschule/me/appointments', 'bootschule'], true) ? 'active' : '' ?>" href="?route=bootschule/me/appointments">
                            <i class="bi bi-speedometer2 me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link portal-nav-link" href="?route=bootschule/me/appointments#upcoming">
                            <i class="bi bi-calendar-check me-1"></i> Termine
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link portal-nav-link" href="?route=bootschule/me/appointments#weatherCard">
                            <i class="bi bi-cloud-sun me-1"></i> Wetter (LU &amp; NW)
                        </a>
                    </li>
                    <?php if (!empty($_SESSION['boating_customer_id']) || !empty($_SESSION['bs_customer_id']) || !empty($_SESSION['magic_authenticated'])): ?>
                        <li class="nav-item">
                            <button type="button" class="nav-link portal-nav-link btn btn-link text-decoration-none border-0" data-bs-toggle="modal" data-bs-target="#profileModal">
                                <i class="bi bi-person-circle me-1"></i> Mein Profil
                            </button>
                        </li>
                        <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                            <button type="button" class="btn portal-btn-book" data-bs-toggle="modal" data-bs-target="#requestAppointmentModal">
                                <i class="bi bi-calendar-plus me-1"></i> Terminanfrage
                            </button>
                        </li>
                        <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                            <a class="btn btn-sm btn-outline-light rounded-pill px-3" href="?route=bootschule/me/logout" title="Sitzung sicher beenden">
                                <i class="bi bi-box-arrow-right me-1"></i> Abmelden
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                            <a class="btn portal-btn-book" href="?route=nautic/login">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Anmelden
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if (class_exists('Auth') && Auth::check() && ((string) (Auth::user()['role'] ?? '') === 'admin' || (string) (Auth::user()['role'] ?? '') === 'super_admin')): ?>
                        <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                            <a class="btn btn-sm btn-outline-warning rounded-pill px-2.5" href="?route=admin/boating">
                                <i class="bi bi-shield-lock me-1"></i> Admin
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Global Alerts -->
    <?php if ($portalFlashSuccess || $portalFlashError || $portalFlashInfo): ?>
        <div class="container mt-4">
            <?php if ($portalFlashSuccess): ?>
                <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 d-flex align-items-center" role="alert">
                    <i class="bi bi-check-circle-fill fs-5 text-success me-2"></i>
                    <div><?= htmlspecialchars($portalFlashSuccess, ENT_QUOTES, 'UTF-8') ?></div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Schließen"></button>
                </div>
            <?php endif; ?>

            <?php if ($portalFlashError): ?>
                <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 d-flex align-items-center" role="alert">
                    <i class="bi bi-exclamation-triangle-fill fs-5 text-danger me-2"></i>
                    <div><?= htmlspecialchars($portalFlashError, ENT_QUOTES, 'UTF-8') ?></div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Schließen"></button>
                </div>
            <?php endif; ?>

            <?php if ($portalFlashInfo): ?>
                <div class="alert alert-info alert-dismissible fade show shadow-sm border-0 d-flex align-items-center" role="alert">
                    <i class="bi bi-info-circle-fill fs-5 text-info me-2"></i>
                    <div><?= htmlspecialchars($portalFlashInfo, ENT_QUOTES, 'UTF-8') ?></div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Schließen"></button>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Hauptinhalt -->
    <main class="portal-main py-4">
        <?= $content ?>
    </main>

    <!-- Footer (nur Copyrights) -->
    <footer class="portal-footer py-4 border-top mt-auto bg-white">
        <div class="container text-center small text-muted">
            &copy; <?= date('Y') ?> <?= htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') ?>. Alle Rechte vorbehalten.
        </div>
    </footer>
    <?php else: ?>
    <!-- Full-screen Sidebar Layout -->
    <main class="portal-main-fluid p-0 m-0" style="min-height: 100vh; background-color: #f8fafc;">
        <?= $content ?>
    </main>
    <?php endif; ?>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
