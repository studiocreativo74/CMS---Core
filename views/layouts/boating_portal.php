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
            --bs-body-bg: #f8fafc;
            --bs-card-border: #e2e8f0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--bs-body-bg);
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
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
    <nav class="navbar navbar-expand-lg navbar-dark portal-navbar sticky-top">
        <div class="container">
            <a class="navbar-brand navbar-brand-logo" href="?route=bootschule">
                <span class="p-2 bg-white bg-opacity-10 rounded-circle text-info d-inline-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                    <i class="bi bi-compass fs-5"></i>
                </span>
                <span>
                    <?= htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') ?>
                </span>
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#portalNav" aria-controls="portalNav" aria-expanded="false" aria-label="Navigation umschalten">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="portalNav">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center gap-1">
                    <li class="nav-item">
                        <a class="nav-link portal-nav-link <?= in_array($currentRoute, ['bootschule', '/bootschule'], true) ? 'active' : '' ?>" href="?route=bootschule">
                            <i class="bi bi-house-door me-1"></i> Startseite
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link portal-nav-link <?= in_array($currentRoute, ['bootschule/services', '/bootschule/services'], true) ? 'active' : '' ?>" href="?route=bootschule/services">
                            <i class="bi bi-tag me-1"></i> Angebote &amp; Tarife
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link portal-nav-link <?= in_array($currentRoute, ['bootschule/me/appointments', '/bootschule/me/appointments'], true) ? 'active' : '' ?>" href="?route=bootschule/me/appointments">
                            <i class="bi bi-calendar-check me-1"></i> Meine Termine
                        </a>
                    </li>
                    <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                        <a class="btn portal-btn-book" href="?route=bootschule/book">
                            <i class="bi bi-calendar-plus me-1"></i> Slot buchen
                        </a>
                    </li>
                    <?php if (class_exists('Auth') && Auth::check()): ?>
                        <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                            <a class="btn btn-sm btn-outline-light rounded-pill px-3" href="?route=admin/boating">
                                <i class="bi bi-gear-fill me-1"></i> Admin
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

    <!-- Footer -->
    <footer class="portal-footer py-5">
        <div class="container">
            <div class="row g-4 justify-content-between">
                <div class="col-lg-4">
                    <div class="d-flex align-items-center gap-2 mb-3 text-white fw-bold fs-5">
                        <i class="bi bi-compass text-info"></i>
                        <span><?= htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <p class="small text-muted mb-3">
                        Ihre professionelle Bootsfahrschule für Motorboot (Kat. A), Segelschiff (Kat. D),
                        Prüfungsvorbereitung und exklusive Bootsmiete.
                    </p>
                    <div class="d-flex gap-3 text-white-50 fs-5">
                        <i class="bi bi-geo-alt"></i> Vierwaldstättersee / Region
                    </div>
                </div>

                <div class="col-6 col-lg-2">
                    <h6 class="text-white fw-semibold mb-3">Ausbildung</h6>
                    <ul class="list-unstyled mb-0 d-flex flex-column gap-2 small">
                        <li><a href="?route=bootschule/services&category=practical">Motorboot Praxis (Kat. A)</a></li>
                        <li><a href="?route=bootschule/services&category=practical">Segelausbildung (Kat. D)</a></li>
                        <li><a href="?route=bootschule/services&category=theory">Theorie &amp; Navigation</a></li>
                        <li><a href="?route=bootschule/services&category=exam">Prüfungstraining</a></li>
                    </ul>
                </div>

                <div class="col-6 col-lg-2">
                    <h6 class="text-white fw-semibold mb-3">Kundenportal</h6>
                    <ul class="list-unstyled mb-0 d-flex flex-column gap-2 small">
                        <li><a href="?route=bootschule/book">Termin &amp; Slot buchen</a></li>
                        <li><a href="?route=bootschule/services&category=rental">Bootsmiete anfragen</a></li>
                        <li><a href="?route=bootschule/me/appointments">Meine Termine verwalten</a></li>
                        <li><a href="?route=contact">Kontakt &amp; Standort</a></li>
                    </ul>
                </div>

                <div class="col-lg-3">
                    <h6 class="text-white fw-semibold mb-3">Schnellkontakt</h6>
                    <p class="small text-muted mb-2">
                        Haben Sie Fragen zur Führerscheinprüfung oder wünschen Sie eine individuelle Beratung?
                    </p>
                    <a href="?route=bootschule/book" class="btn btn-sm btn-outline-info rounded-pill px-3">
                        <i class="bi bi-calendar-check me-1"></i> Direkt online anfragen
                    </a>
                </div>
            </div>

            <hr class="border-secondary border-opacity-25 my-4">

            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 small text-muted">
                <div>
                    &copy; <?= date('Y') ?> <?= htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') ?>. Alle Rechte vorbehalten.
                </div>
                <div class="d-flex gap-3">
                    <a href="?route=login" class="text-secondary text-decoration-none">Internes Login</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
