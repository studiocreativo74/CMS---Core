<?php
/**
 * Nautic Modul-Navigation & Schnellzugriff (Header mit Hamburger-Menü)
 *
 * @var string $currentRoute Aktuelle Route (z.B. 'admin/boating', 'admin/boating/boats', etc.)
 * @var string|null $navTitle Optionaler Haupttitel
 * @var string|null $navSubtitle Optionaler Untertitel
 * @var string|null $activeTab Aktiver Tab ('dashboard', 'calendar', 'boats', 'instructors', 'services', 'customers', 'reports', 'portal', 'settings')
 */

$routeClean = ltrim((string) ($currentRoute ?? 'admin/boating'), '/');
$titleText = $navTitle ?? 'Nautic – Fahrschule, Vermietung & Flotte';
$subtitleText = $navSubtitle ?? 'Zentrales Management für Praxisfahrstunden, Bootsvermietung & Charter, Flotte und Instruktoren.';
?>

<div class="card border-0 shadow-sm rounded-4 mb-4 bg-white overflow-hidden">
    <div class="card-body p-3 p-md-4">
        <!-- Obere Zeile: Breadcrumb & Schnellzugriff-Hamburger-Menü -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item">
                        <a href="?route=admin" class="text-decoration-none text-muted">
                            <i class="bi bi-house-door me-1"></i>CMS Admin
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="?route=admin/boating" class="text-decoration-none fw-semibold text-primary">
                            <i class="bi bi-compass me-1"></i>Nautic
                        </a>
                    </li>
                    <?php if (!empty($breadcrumbCurrent)): ?>
                        <li class="breadcrumb-item active text-dark fw-bold" aria-current="page">
                            <?= htmlspecialchars($breadcrumbCurrent, ENT_QUOTES, 'UTF-8') ?>
                        </li>
                    <?php endif; ?>
                </ol>
            </nav>

            <!-- Hamburger-Dropdown: Alle Nautic-Module übersichtlich an einem Ort -->
            <div class="d-flex align-items-center gap-2">
                <div class="dropdown">
                    <button class="btn btn-sm btn-dark d-flex align-items-center gap-2 shadow-sm px-3 py-2 rounded-3" 
                            type="button" 
                            id="nauticHamburgerMenu" 
                            data-bs-toggle="dropdown" 
                            aria-expanded="false"
                            title="Nautic Modul-Menü öffnen">
                        <i class="bi bi-list fs-5"></i>
                        <span class="fw-semibold">Nautic Menü</span>
                        <i class="bi bi-chevron-down small ms-1 opacity-75"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3 p-2" aria-labelledby="nauticHamburgerMenu" style="min-width: 280px; z-index: 1060;">
                        <li class="dropdown-header text-uppercase small fw-bold text-muted px-3 pt-2 pb-1" style="font-size: 0.70rem; letter-spacing: 0.05em;">
                            Flotte & Disposition
                        </li>
                        <li>
                            <a class="dropdown-item rounded-2 py-2 d-flex align-items-center gap-2 <?= $routeClean === 'admin/boating' ? 'active fw-bold' : '' ?>" href="?route=admin/boating">
                                <i class="bi bi-compass text-primary"></i>
                                <span>Übersicht &amp; Dashboard</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item rounded-2 py-2 d-flex align-items-center gap-2 <?= str_starts_with($routeClean, 'admin/boating/calendar') ? 'active fw-bold' : '' ?>" href="?route=admin/boating/calendar">
                                <i class="bi bi-calendar3 text-info"></i>
                                <span>Kalender &amp; Disposition</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item rounded-2 py-2 d-flex align-items-center gap-2 <?= str_starts_with($routeClean, 'admin/boating/boats') ? 'active fw-bold' : '' ?>" href="?route=admin/boating/boats">
                                <i class="bi bi-tsunami text-primary"></i>
                                <span>Boote &amp; Flotte (Vermietung)</span>
                            </a>
                        </li>

                        <li><hr class="dropdown-divider my-2"></li>
                        <li class="dropdown-header text-uppercase small fw-bold text-muted px-3 pt-1 pb-1" style="font-size: 0.70rem; letter-spacing: 0.05em;">
                            Coaches, Kunden &amp; Tarife
                        </li>
                        <li>
                            <a class="dropdown-item rounded-2 py-2 d-flex align-items-center gap-2 <?= str_starts_with($routeClean, 'admin/boating/instructors') ? 'active fw-bold' : '' ?>" href="?route=admin/boating/instructors">
                                <i class="bi bi-person-badge text-warning"></i>
                                <span>Fahrlehrer &amp; Skipper</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item rounded-2 py-2 d-flex align-items-center gap-2 <?= str_starts_with($routeClean, 'admin/boating/customers') ? 'active fw-bold' : '' ?>" href="?route=admin/boating/customers">
                                <i class="bi bi-people text-success"></i>
                                <span>Schüler &amp; Kunden (CRM)</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item rounded-2 py-2 d-flex align-items-center gap-2 <?= str_starts_with($routeClean, 'admin/boating/services') ? 'active fw-bold' : '' ?>" href="?route=admin/boating/services">
                                <i class="bi bi-tag text-danger"></i>
                                <span>Leistungen &amp; Tarife</span>
                            </a>
                        </li>

                        <li><hr class="dropdown-divider my-2"></li>
                        <li class="dropdown-header text-uppercase small fw-bold text-muted px-3 pt-1 pb-1" style="font-size: 0.70rem; letter-spacing: 0.05em;">
                            Abrechnung &amp; Verwaltung
                        </li>
                        <li>
                            <a class="dropdown-item rounded-2 py-2 d-flex align-items-center gap-2 <?= str_starts_with($routeClean, 'admin/boating/reports') ? 'active fw-bold' : '' ?>" href="?route=admin/boating/reports">
                                <i class="bi bi-graph-up-arrow text-secondary"></i>
                                <span>Auswertungen &amp; Payroll</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item rounded-2 py-2 d-flex align-items-center gap-2 <?= str_starts_with($routeClean, 'admin/boating/instructor-portal') ? 'active fw-bold' : '' ?>" href="?route=admin/boating/instructor-portal">
                                <i class="bi bi-person-workspace text-primary"></i>
                                <span>Fahrlehrer-Portal</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item rounded-2 py-2 d-flex align-items-center gap-2 <?= str_starts_with($routeClean, 'admin/boating/settings') ? 'active fw-bold' : '' ?>" href="?route=admin/boating/settings">
                                <i class="bi bi-gear text-secondary"></i>
                                <span>E-Mail &amp; Einstellungen</span>
                            </a>
                        </li>

                        <li><hr class="dropdown-divider my-2"></li>
                        <li>
                            <a class="dropdown-item rounded-2 py-2 d-flex align-items-center gap-2 text-info" href="?route=bootschule" target="_blank" rel="noopener">
                                <i class="bi bi-box-arrow-up-right"></i>
                                <span>Kundenportal (Vorschau)</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <a href="?route=bootschule" target="_blank" rel="noopener" class="btn btn-sm btn-outline-info d-none d-sm-inline-flex align-items-center gap-1 rounded-3 px-3 py-2" title="Öffentliches Kundenportal in neuem Tab öffnen">
                    <i class="bi bi-box-arrow-up-right"></i>
                    <span>Kundenportal</span>
                </a>
            </div>
        </div>

        <!-- Titel & Modul-Subzeile -->
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-primary text-white rounded-pill px-3 py-1 font-monospace small" style="letter-spacing: 0.05em;">NAUTIC</span>
                    <span class="badge bg-light text-muted border rounded-pill px-2 py-0 small">Fahrschule &amp; Vermietung</span>
                </div>
                <h1 class="h3 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                    <i class="bi bi-water text-primary"></i>
                    <?= htmlspecialchars($titleText, ENT_QUOTES, 'UTF-8') ?>
                </h1>
                <p class="text-muted small mb-0">
                    <?= htmlspecialchars($subtitleText, ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>
        </div>

        <!-- Schnellzugriffs-Tabs / Pill-Navigation -->
        <div class="d-flex flex-wrap align-items-center gap-2 mt-3 pt-3 border-top">
            <a href="?route=admin/boating" class="btn btn-sm <?= $routeClean === 'admin/boating' ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill px-3">
                <i class="bi bi-compass me-1"></i> Übersicht
            </a>
            <a href="?route=admin/boating/calendar" class="btn btn-sm <?= str_starts_with($routeClean, 'admin/boating/calendar') ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill px-3">
                <i class="bi bi-calendar3 me-1"></i> Kalender
            </a>
            <a href="?route=admin/boating/boats" class="btn btn-sm <?= str_starts_with($routeClean, 'admin/boating/boats') ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill px-3">
                <i class="bi bi-tsunami me-1"></i> Boote &amp; Flotte
            </a>
            <a href="?route=admin/boating/instructors" class="btn btn-sm <?= str_starts_with($routeClean, 'admin/boating/instructors') ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill px-3">
                <i class="bi bi-person-badge me-1"></i> Fahrlehrer / Skipper
            </a>
            <a href="?route=admin/boating/services" class="btn btn-sm <?= str_starts_with($routeClean, 'admin/boating/services') ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill px-3">
                <i class="bi bi-tag me-1"></i> Leistungen
            </a>
            <a href="?route=admin/boating/customers" class="btn btn-sm <?= str_starts_with($routeClean, 'admin/boating/customers') ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill px-3">
                <i class="bi bi-people me-1"></i> Kunden (CRM)
            </a>
            <a href="?route=admin/boating/reports" class="btn btn-sm <?= str_starts_with($routeClean, 'admin/boating/reports') ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill px-3">
                <i class="bi bi-graph-up-arrow me-1"></i> Reports &amp; Payroll
            </a>
            <a href="?route=admin/boating/settings" class="btn btn-sm <?= str_starts_with($routeClean, 'admin/boating/settings') ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill px-3 ms-auto">
                <i class="bi bi-gear me-1"></i> Einstellungen
            </a>
        </div>
    </div>
</div>
