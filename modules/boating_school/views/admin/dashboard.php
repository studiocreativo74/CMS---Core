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

$title = 'Nautic - Übersicht & Flotte';
$currentRoute = 'admin/boating';

$canManage = BoatingSchoolService::canManage();

ob_start();
?>

<?php 
$breadcrumbCurrent = 'Übersicht & Flotte';
$navTitle = 'Nautic – Fahrschule, Vermietung & Flotte';
$navSubtitle = 'Ganzheitliche Verwaltung für Bootsfahrschule, Bootsvermietung & Charter, Flotte und Fahrlehrer/Skipper.';
require __DIR__ . '/_nautic_nav.php'; 
?>

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
