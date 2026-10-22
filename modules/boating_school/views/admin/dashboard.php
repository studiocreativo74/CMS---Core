<?php

declare(strict_types=1);

/**
 * Bootsfahrschule - Dashboard & Stammdaten-Zentrale
 *
 * @var array<string, int> $stats
 * @var array<int, array<string, mixed>> $recentBoats
 * @var array<int, array<string, mixed>> $recentInstructors
 * @var array<int, array<string, mixed>> $recentCustomers
 * @var bool $tablesCreated
 */

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$title = 'Bootsfahrschule - Stammdaten';
$currentRoute = 'admin/boating';

$canManage = BoatingSchoolService::canManage();

ob_start();
?>

<?php if ($flashSuccess): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-check-circle-fill text-success me-2" viewBox="0 0 16 16">
                <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
            </svg>
            <div><?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>
    </div>
<?php endif; ?>

<?php if ($flashError): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-exclamation-triangle-fill text-danger me-2" viewBox="0 0 16 16">
                <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5m.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/>
            </svg>
            <div><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>
    </div>
<?php endif; ?>

<?php if (!$tablesCreated): ?>
    <div class="card border-warning border-start border-4 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex align-items-start gap-3">
                <div class="p-2 bg-warning bg-opacity-10 text-warning rounded flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor" class="bi bi-database-exclamation" viewBox="0 0 16 16">
                        <path d="M8 1c-1.573 0-3.022.289-4.096.777C2.823 2.269 2 2.99 2 4s.823 1.731 1.904 2.223C4.978 6.711 6.427 7 8 7s3.022-.289 4.096-.777C13.177 5.731 14 5.01 14 4s-.823-1.731-1.904-2.223C11.022 1.289 9.573 1 8 1"/>
                        <path d="M1.904 6.223C2.823 6.731 4.978 7 8 7s5.177-.269 6.096-.777C13.177 5.731 14 5.01 14 4M14 6v4c0 1.01-.823 1.731-1.904 2.223C11.177 12.731 9.022 13 8 13s-5.177-.269-6.096-.777C2.823 11.731 2 11.01 2 10V6"/>
                    </svg>
                </div>
                <div class="flex-grow-1">
                    <h5 class="fw-bold text-dark mb-1">Datenbanktabellen noch nicht initialisiert</h5>
                    <p class="text-muted small mb-2">
                        Die Tabellen <code>bs_boats</code>, <code>bs_instructors</code>, <code>bs_services</code> und <code>bs_customers</code>
                        wurden in der MySQL-Datenbank noch nicht angelegt. Gemäß CMS-Architektur werden DDL-Statements nicht automatisch ausgeführt,
                        sondern stehen als Migrations-Datei bereit.
                    </p>
                    <div class="d-flex align-items-center gap-2">
                        <a href="?route=admin/system" class="btn btn-sm btn-outline-warning">
                            <i class="bi bi-gear-fill me-1"></i> Migrationen ansehen
                        </a>
                        <span class="small text-muted">Datei: <code>migrations/008_create_boating_school_schema.sql</code></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Header mit Titel und Navigation -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">
            <i class="bi bi-water text-primary me-2"></i>Bootsfahrschule &amp; Bootsflotte
        </h1>
        <p class="text-muted small mb-0">
            Phase 1: Stammdatenverwaltung für Boote, Fahrlehrer, Dienstleistungen und Kunden/Fahrschüler.
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="?route=bootschule" target="_blank" class="btn btn-outline-info btn-sm">
            <i class="bi bi-box-arrow-up-right me-1"></i> Kundenportal
        </a>
        <a href="?route=admin/boating/calendar" class="btn btn-primary btn-sm">
            <i class="bi bi-calendar3 me-1"></i> Kalender &amp; Termine
        </a>
        <a href="?route=admin/boating/boats" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-tsunami me-1"></i> Boote
        </a>
        <a href="?route=admin/boating/instructors" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-person-badge me-1"></i> Fahrlehrer
        </a>
        <a href="?route=admin/boating/services" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-tag-fill me-1"></i> Leistungen
        </a>
        <a href="?route=admin/boating/customers" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-people-fill me-1"></i> Schüler &amp; Kunden
        </a>
        <a href="?route=admin/boating/settings" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-envelope-paper-fill me-1"></i> E-Mail &amp; Benachrichtigungen
        </a>
    </div>
</div>

<!-- 4 KPI-Karten -->
<div class="row g-3 mb-4">
    <!-- Boote -->
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold px-2 py-1">
                        <i class="bi bi-water me-1"></i>Bootsflotte
                    </span>
                    <a href="?route=admin/boating/boats" class="small text-muted text-decoration-none">Verwalten &rarr;</a>
                </div>
                <div class="d-flex align-items-baseline gap-2">
                    <div class="h3 fw-bold mb-0 text-dark"><?= (int) ($stats['boats_total'] ?? 0) ?></div>
                    <div class="small text-muted">Boote</div>
                </div>
                <div class="small text-success mt-2">
                    <i class="bi bi-check-circle-fill me-1"></i><?= (int) ($stats['boats_active'] ?? 0) ?> aktiv in Betrieb
                </div>
            </div>
        </div>
    </div>

    <!-- Fahrlehrer -->
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge bg-info bg-opacity-10 text-info fw-semibold px-2 py-1">
                        <i class="bi bi-person-badge-fill me-1"></i>Fahrlehrer
                    </span>
                    <a href="?route=admin/boating/instructors" class="small text-muted text-decoration-none">Verwalten &rarr;</a>
                </div>
                <div class="d-flex align-items-baseline gap-2">
                    <div class="h3 fw-bold mb-0 text-dark"><?= (int) ($stats['instructors_total'] ?? 0) ?></div>
                    <div class="small text-muted">Instruktoren</div>
                </div>
                <div class="small text-success mt-2">
                    <i class="bi bi-check-circle-fill me-1"></i><?= (int) ($stats['instructors_active'] ?? 0) ?> aktiv verfügbar
                </div>
            </div>
        </div>
    </div>

    <!-- Leistungen -->
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge bg-warning bg-opacity-10 text-warning fw-semibold px-2 py-1">
                        <i class="bi bi-tag-fill me-1"></i>Leistungen
                    </span>
                    <a href="?route=admin/boating/services" class="small text-muted text-decoration-none">Verwalten &rarr;</a>
                </div>
                <div class="d-flex align-items-baseline gap-2">
                    <div class="h3 fw-bold mb-0 text-dark"><?= (int) ($stats['services_total'] ?? 0) ?></div>
                    <div class="small text-muted">Tarife &amp; Kurse</div>
                </div>
                <div class="small text-muted mt-2">
                    Praxis, Theorie, Miete &amp; Prüfungen
                </div>
            </div>
        </div>
    </div>

    <!-- Schüler / Kunden -->
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge bg-success bg-opacity-10 text-success fw-semibold px-2 py-1">
                        <i class="bi bi-people-fill me-1"></i>Schüler &amp; Kunden
                    </span>
                    <a href="?route=admin/boating/customers" class="small text-muted text-decoration-none">Verwalten &rarr;</a>
                </div>
                <div class="d-flex align-items-baseline gap-2">
                    <div class="h3 fw-bold mb-0 text-dark"><?= (int) ($stats['customers_total'] ?? 0) ?></div>
                    <div class="small text-muted">Erfasst</div>
                </div>
                <div class="small text-muted mt-2">
                    <?= (int) ($stats['students_total'] ?? 0) ?> Schüler &bull; <?= (int) ($stats['renters_total'] ?? 0) ?> Mieter
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Flotte & Fahrlehrer Teaser Tabellen -->
<div class="row g-4 mb-4">
    <!-- Boote Übersicht -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-tsunami text-primary me-2"></i>Aktuelle Flotte (Auszug)
                </h6>
                <a href="?route=admin/boating/boats" class="btn btn-sm btn-outline-secondary">Alle Boote</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentBoats)): ?>
                    <div class="p-4 text-center text-muted small">
                        Keine Boote erfasst.
                        <?php if ($canManage): ?>
                            <br><a href="?route=admin/boating/boats" class="btn btn-sm btn-primary mt-2">Jetzt Boot anlegen</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Boot</th>
                                    <th>Typ / Reg.</th>
                                    <th>Kapazität</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentBoats as $boat): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars((string) $boat['name'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <?php if (!empty($boat['engine_power'])): ?>
                                                <div class="text-muted small"><?= (int) $boat['engine_power'] ?> PS/kW</div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= BoatingSchoolService::getBoatTypeBadge((string) ($boat['type'] ?? '')) ?>
                                            <?php if (!empty($boat['registration'])): ?>
                                                <div class="text-muted font-monospace small"><?= htmlspecialchars((string) $boat['registration'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= !empty($boat['capacity']) ? (int) $boat['capacity'] . ' Pers.' : '-' ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($boat['is_active'])): ?>
                                                <span class="badge bg-success-subtle text-success">Aktiv</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-muted">Inaktiv</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Fahrlehrer Übersicht -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-person-badge text-primary me-2"></i>Fahrlehrer-Team (Auszug)
                </h6>
                <a href="?route=admin/boating/instructors" class="btn btn-sm btn-outline-secondary">Alle Fahrlehrer</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentInstructors)): ?>
                    <div class="p-4 text-center text-muted small">
                        Keine Fahrlehrer erfasst.
                        <?php if ($canManage): ?>
                            <br><a href="?route=admin/boating/instructors" class="btn btn-sm btn-primary mt-2">Jetzt Fahrlehrer anlegen</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Fahrlehrer</th>
                                    <th>Qualifikation / Lizenz</th>
                                    <th>Kontakt</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentInstructors as $inst): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="rounded-circle d-inline-block flex-shrink-0" style="width: 12px; height: 12px; background-color: <?= htmlspecialchars((string) ($inst['color_code'] ?? '#0d6efd'), ENT_QUOTES, 'UTF-8') ?>;"></span>
                                                <div class="fw-semibold text-dark">
                                                    <?= htmlspecialchars((string) $inst['first_name'] . ' ' . (string) $inst['last_name'], ENT_QUOTES, 'UTF-8') ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="text-truncate" style="max-width: 180px;">
                                                <?= htmlspecialchars((string) (!empty($inst['title']) ? $inst['title'] : (!empty($inst['qualifications']) ? $inst['qualifications'] : 'Fahrcoach')), ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (!empty($inst['phone'])): ?>
                                                <div><?= htmlspecialchars((string) $inst['phone'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($inst['email'])): ?>
                                                <div class="text-muted small"><?= htmlspecialchars((string) $inst['email'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($inst['is_active'])): ?>
                                                <span class="badge bg-success-subtle text-success">Aktiv</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-muted">Inaktiv</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Schnelleinstieg & Hinweis -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <h6 class="fw-bold text-dark mb-2">Phase 1 Ausblick &amp; Datenarchitektur</h6>
        <p class="text-muted small mb-0">
            In dieser ersten Phase stehen die sauberen <strong>Stammdaten</strong> im Vordergrund: Boote mit Spezifikationen, Fahrlehrer mit Lizenzen &amp; Farbkennzeichnung, flexible Leistungen/Produkte mit Preisen und Kunden bzw. Fahrschüler mit Prüfungstracking.
            In einer späteren Phase können auf diesem Fundament der Kalender, Fahrstundenbuchungen, Online-Anmeldungen und die Abrechnung aufsetzen.
        </p>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../../../views/layouts/admin.php';
