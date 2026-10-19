<?php

declare(strict_types=1);

/**
 * Bootsfahrschule - Auswertungen & Fahrlehrer-Payroll
 *
 * @var array<string, mixed> $schoolSummary
 * @var array<int, array<string, mixed>> $instructorSummaries
 * @var array<int, array<string, mixed>> $detailedAppointments
 * @var array<int, array<string, mixed>> $instructors
 * @var array<string, mixed> $filters
 * @var string $csrfToken
 * @var bool $canManage
 */

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$title = 'Auswertungen & Fahrlehrer-Payroll';
$currentRoute = 'admin/boating/reports';

$dateFrom = $filters['date_from'] ?? date('Y-m-01');
$dateTo   = $filters['date_to'] ?? date('Y-m-t');
$activeTab = $filters['tab'] ?? 'payroll';

ob_start();
?>

<!-- Flash Meldungen -->
<?php if ($flashSuccess): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-center">
            <i class="bi bi-check-circle-fill text-success fs-5 me-2"></i>
            <div><?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>
    </div>
<?php endif; ?>

<?php if ($flashError): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-center">
            <i class="bi bi-exclamation-triangle-fill text-danger fs-5 me-2"></i>
            <div><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>
    </div>
<?php endif; ?>

<!-- Kopfzeile -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="?route=admin/boating">Bootsfahrschule</a></li>
                <li class="breadcrumb-item active" aria-current="page">Auswertungen &amp; Payroll</li>
            </ol>
        </nav>
        <h1 class="h3 fw-bold text-dark mb-0">
            <i class="bi bi-graph-up-arrow text-primary me-2"></i>Auswertungen &amp; Fahrlehrer-Payroll
        </h1>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <div class="dropdown">
            <button class="btn btn-outline-success btn-sm dropdown-toggle shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> CSV-Export
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow">
                <li>
                    <a class="dropdown-item py-2" href="?route=admin/boating/reports/export-payroll&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>&instructor_id=<?= (int) ($filters['instructor_id'] ?? 0) ?>&payout_status=<?= urlencode($filters['payout_status'] ?? '') ?>">
                        <i class="bi bi-list-check text-success me-2"></i><strong>Einzeltermine &amp; Fahrtenjournal</strong>
                        <div class="small text-muted ps-4">Alle Fahrten mit Honorar &amp; Status</div>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item py-2" href="?route=admin/boating/reports/export-payroll&mark_as_exported=1&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>&instructor_id=<?= (int) ($filters['instructor_id'] ?? 0) ?>&payout_status=<?= urlencode($filters['payout_status'] ?? '') ?>" onclick="return confirm('Möchten Sie den Export herunterladen und offene Termine direkt als «Exportiert» markieren?');">
                        <i class="bi bi-box-arrow-up-right text-primary me-2"></i><strong>Exportieren &amp; als «Exportiert» markieren</strong>
                        <div class="small text-muted ps-4">Setzt offene Termine auf Status 'exported'</div>
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item py-2" href="?route=admin/boating/reports/export-summary&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>&instructor_id=<?= (int) ($filters['instructor_id'] ?? 0) ?>">
                        <i class="bi bi-people text-info me-2"></i><strong>Fahrlehrer-Zusammenfassung (Kompakt)</strong>
                        <div class="small text-muted ps-4">Summen pro Instruktor für Lohnrunde</div>
                    </a>
                </li>
            </ul>
        </div>

        <?php if ($canManage): ?>
            <button type="button" class="btn btn-primary btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#bulkRecalculateModal">
                <i class="bi bi-calculator me-1"></i> Honorare neu berechnen
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Filterleiste & Zeitraum-Presets -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="get" action="" class="row g-2 align-items-end" id="reportFilterForm">
            <input type="hidden" name="route" value="admin/boating/reports">
            <input type="hidden" name="tab" id="activeTabInput" value="<?= htmlspecialchars($activeTab, ENT_QUOTES, 'UTF-8') ?>">

            <!-- Presets -->
            <div class="col-12 d-flex flex-wrap align-items-center gap-2 mb-2 pb-2 border-bottom">
                <span class="small text-muted fw-semibold me-1"><i class="bi bi-calendar-event me-1"></i>Schnellwahl:</span>
                <button type="button" class="btn btn-xs btn-outline-secondary date-preset-btn" data-from="<?= date('Y-m-01') ?>" data-to="<?= date('Y-m-t') ?>">
                    Dieser Monat
                </button>
                <button type="button" class="btn btn-xs btn-outline-secondary date-preset-btn" data-from="<?= date('Y-m-01', strtotime('first day of last month')) ?>" data-to="<?= date('Y-m-t', strtotime('last day of last month')) ?>">
                    Letzter Monat
                </button>
                <button type="button" class="btn btn-xs btn-outline-secondary date-preset-btn" data-from="<?= date('Y-01-01') ?>" data-to="<?= date('Y-12-31') ?>">
                    Dieses Jahr
                </button>
                <button type="button" class="btn btn-xs btn-outline-secondary date-preset-btn" data-from="<?= date('Y-m-d', strtotime('-30 days')) ?>" data-to="<?= date('Y-m-d') ?>">
                    Letzte 30 Tage
                </button>
            </div>

            <div class="col-md-3 col-sm-6">
                <label class="form-label small fw-semibold mb-1">Von Datum</label>
                <input type="date" name="date_from" id="filterDateFrom" class="form-control form-control-sm" value="<?= htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8') ?>" required>
            </div>

            <div class="col-md-3 col-sm-6">
                <label class="form-label small fw-semibold mb-1">Bis Datum</label>
                <input type="date" name="date_to" id="filterDateTo" class="form-control form-control-sm" value="<?= htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8') ?>" required>
            </div>

            <div class="col-md-3 col-sm-6">
                <label class="form-label small fw-semibold mb-1">Fahrlehrer</label>
                <select name="instructor_id" class="form-select form-select-sm">
                    <option value="">-- Alle Fahrlehrer --</option>
                    <?php foreach ($instructors as $inst): ?>
                        <option value="<?= (int) $inst['id'] ?>" <?= ((int) ($filters['instructor_id'] ?? 0) === (int) $inst['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string) ($inst['first_name'] . ' ' . $inst['last_name'])) ?>
                            <?= !empty($inst['payroll_external_code']) ? ' (' . htmlspecialchars((string) $inst['payroll_external_code']) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2 col-sm-6">
                <label class="form-label small fw-semibold mb-1">Abrechnungsstatus</label>
                <select name="payout_status" class="form-select form-select-sm">
                    <option value="">-- Alle Status --</option>
                    <option value="open" <?= (($filters['payout_status'] ?? '') === 'open') ? 'selected' : '' ?>>Offen</option>
                    <option value="exported" <?= (($filters['payout_status'] ?? '') === 'exported') ? 'selected' : '' ?>>Exportiert</option>
                    <option value="paid" <?= (($filters['payout_status'] ?? '') === 'paid') ? 'selected' : '' ?>>Bezahlt</option>
                </select>
            </div>

            <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm w-100" title="Filter anwenden">
                    <i class="bi bi-funnel-fill"></i>
                </button>
                <a href="?route=admin/boating/reports" class="btn btn-outline-secondary btn-sm" title="Zurücksetzen">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- KPI Metrik-Karten -->
<div class="row g-3 mb-4">
    <!-- Gesamtumsatz -->
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Kundenumsatz Brutto</span>
                    <div class="p-2 bg-success bg-opacity-10 text-success rounded">
                        <i class="bi bi-currency-dollar fs-5"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-dark mb-1">CHF <?= number_format((float) ($schoolSummary['total_revenue'] ?? 0.0), 2, '.', '\'') ?></h3>
                <div class="small text-muted">
                    <i class="bi bi-check-circle text-success me-1"></i><?= (int) ($schoolSummary['total_appointments'] ?? 0) ?> Termine gesamt
                    (<?= (int) ($schoolSummary['completed'] ?? 0) ?> abgeschlossen)
                </div>
            </div>
        </div>
    </div>

    <!-- Geleistete Stunden -->
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Ausbildungsstunden</span>
                    <div class="p-2 bg-primary bg-opacity-10 text-primary rounded">
                        <i class="bi bi-clock-history fs-5"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-dark mb-1"><?= number_format((float) ($schoolSummary['total_hours'] ?? 0.0), 1) ?> Std.</h3>
                <div class="small text-muted">
                    <i class="bi bi-calendar3 text-primary me-1"></i>Im gewählten Zeitraum
                </div>
            </div>
        </div>
    </div>

    <!-- Fahrlehrer-Honorare Total -->
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Fahrlehrer-Honorare</span>
                    <div class="p-2 bg-warning bg-opacity-10 text-warning rounded">
                        <i class="bi bi-cash-stack fs-5"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-dark mb-1">CHF <?= number_format((float) ($schoolSummary['total_payout'] ?? 0.0), 2, '.', '\'') ?></h3>
                <div class="d-flex gap-2 small flex-wrap">
                    <span class="badge bg-warning-subtle text-dark border">Offen: CHF <?= number_format((float) ($schoolSummary['payout_open'] ?? 0.0), 2) ?></span>
                    <span class="badge bg-info-subtle text-dark border">Export: CHF <?= number_format((float) ($schoolSummary['payout_exported'] ?? 0.0), 2) ?></span>
                    <span class="badge bg-success-subtle text-dark border">Bezahlt: CHF <?= number_format((float) ($schoolSummary['payout_paid'] ?? 0.0), 2) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Deckungsbeitrag / Rohertrag -->
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Deckungsbeitrag Schule</span>
                    <div class="p-2 bg-info bg-opacity-10 text-info rounded">
                        <i class="bi bi-pie-chart-fill fs-5"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-dark mb-1">CHF <?= number_format((float) ($schoolSummary['gross_margin'] ?? 0.0), 2, '.', '\'') ?></h3>
                <div class="small text-muted">
                    <span class="fw-semibold text-success"><i class="bi bi-arrow-up-right me-1"></i><?= number_format((float) ($schoolSummary['margin_percent'] ?? 0.0), 1) ?>%</span>
                    Marge nach Fahrlehrerkosten
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tab-Navigation -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom p-0">
        <ul class="nav nav-tabs card-header-tabs m-0 px-3 pt-2" id="reportTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold <?= $activeTab === 'payroll' ? 'active' : '' ?>" id="tab-payroll-btn" data-bs-toggle="tab" data-bs-target="#tab-payroll" type="button" role="tab">
                    <i class="bi bi-person-badge-fill text-primary me-2"></i>Fahrlehrer-Payroll (Lohnbasis)
                    <span class="badge bg-secondary-subtle text-dark rounded-pill ms-1"><?= count($instructorSummaries) ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold <?= $activeTab === 'appointments' ? 'active' : '' ?>" id="tab-appointments-btn" data-bs-toggle="tab" data-bs-target="#tab-appointments" type="button" role="tab">
                    <i class="bi bi-journal-text text-success me-2"></i>Einzelfahrten &amp; Journal
                    <span class="badge bg-secondary-subtle text-dark rounded-pill ms-1"><?= count($detailedAppointments) ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold <?= $activeTab === 'school' ? 'active' : '' ?>" id="tab-school-btn" data-bs-toggle="tab" data-bs-target="#tab-school" type="button" role="tab">
                    <i class="bi bi-bar-chart-line-fill text-info me-2"></i>Schul-Auslastung &amp; Services
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold <?= $activeTab === 'timesheets' ? 'active' : '' ?>" id="tab-timesheets-btn" data-bs-toggle="tab" data-bs-target="#tab-timesheets" type="button" role="tab">
                    <i class="bi bi-clock-history text-warning me-2"></i>Monatsrapporte (Timesheets)
                    <span class="badge bg-secondary-subtle text-dark rounded-pill ms-1"><?= count($coachTimesheets ?? []) ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold <?= $activeTab === 'settings' ? 'active' : '' ?>" id="tab-settings-btn" data-bs-toggle="tab" data-bs-target="#tab-settings" type="button" role="tab">
                    <i class="bi bi-sliders text-secondary me-2"></i>Lohnabzugssätze &amp; Audit-Log
                </button>
            </li>
        </ul>
    </div>

    <div class="card-body p-0">
        <div class="tab-content" id="reportTabsContent">

            <!-- ========================================================================= -->
            <!-- TAB 1: FAHRLEHRER-PAYROLL -->
            <!-- ========================================================================= -->
            <div class="tab-pane fade <?= $activeTab === 'payroll' ? 'show active' : '' ?> p-3" id="tab-payroll" role="tabpanel">
                <?php if (empty($instructorSummaries)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-people text-muted fs-1 d-block mb-2"></i>
                        <h6 class="text-muted">Keine Fahrlehrer oder Termine im Zeitraum gefunden</h6>
                        <p class="text-muted small">Bitte passen Sie den Datumsfilter an.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th>Fahrlehrer</th>
                                    <th>Lohn-Code</th>
                                    <th>Lohnmodell &amp; Satz</th>
                                    <th class="text-center">Fahrten</th>
                                    <th class="text-end">Stunden</th>
                                    <th class="text-end">Umsatz Kunde</th>
                                    <th class="text-end">Honorar Offen</th>
                                    <th class="text-end">Exportiert</th>
                                    <th class="text-end">Bezahlt</th>
                                    <th class="text-end">Honorar Total</th>
                                    <th class="text-end">Marge</th>
                                    <th class="text-end" style="width: 100px;">Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($instructorSummaries as $row): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="rounded-circle d-inline-block border" style="width: 14px; height: 14px; background-color: <?= htmlspecialchars((string) ($row['color_code'] ?? '#0d6efd')) ?>;"></span>
                                                <div>
                                                    <span class="fw-bold text-dark"><?= htmlspecialchars((string) $row['instructor_name']) ?></span>
                                                    <?php if (empty($row['is_active'])): ?>
                                                        <span class="badge bg-secondary-subtle text-muted ms-1 small">Inaktiv</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (!empty($row['payroll_external_code'])): ?>
                                                <span class="font-monospace small text-dark bg-light px-2 py-1 rounded border">
                                                    <?= htmlspecialchars((string) $row['payroll_external_code']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted small">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="small fw-semibold">
                                                CHF <?= number_format((float) $row['pay_rate_amount'], 2) ?>
                                                <span class="badge bg-light text-muted border fw-normal">
                                                    <?= ($row['pay_rate_type'] === 'per_appointment') ? 'Pauschale/Termin' : 'pro Stunde' ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark border"><?= (int) $row['total_trips'] ?></span>
                                            <div class="small text-muted" style="font-size: 0.75rem;"><?= (int) $row['completed_trips'] ?> absolviert</div>
                                        </td>
                                        <td class="text-end fw-semibold">
                                            <?= number_format((float) $row['total_hours'], 1) ?> h
                                        </td>
                                        <td class="text-end">
                                            CHF <?= number_format((float) $row['total_revenue'], 2) ?>
                                        </td>
                                        <td class="text-end">
                                            <?php if ($row['payout_open'] > 0): ?>
                                                <span class="badge bg-warning-subtle text-dark border">CHF <?= number_format((float) $row['payout_open'], 2) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted small">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <?php if ($row['payout_exported'] > 0): ?>
                                                <span class="badge bg-info-subtle text-dark border">CHF <?= number_format((float) $row['payout_exported'], 2) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted small">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <?php if ($row['payout_paid'] > 0): ?>
                                                <span class="badge bg-success-subtle text-dark border">CHF <?= number_format((float) $row['payout_paid'], 2) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted small">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end fw-bold text-dark">
                                            CHF <?= number_format((float) $row['total_payout'], 2) ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="small fw-semibold <?= $row['gross_margin'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                                CHF <?= number_format((float) $row['gross_margin'], 2) ?>
                                            </div>
                                            <div class="text-muted" style="font-size: 0.75rem;"><?= number_format((float) $row['margin_percent'], 1) ?>%</div>
                                        </td>
                                        <td class="text-end">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary py-0 px-2" type="button" data-bs-toggle="dropdown">
                                                    <i class="bi bi-three-dots"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                    <li>
                                                        <a class="dropdown-item small" href="?route=admin/boating/reports&tab=appointments&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>&instructor_id=<?= (int) $row['instructor_id'] ?>">
                                                            <i class="bi bi-list-task me-2 text-primary"></i>Fahrten dieses Fahrlehrers
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item small" href="?route=admin/boating/instructors/edit&id=<?= (int) $row['instructor_id'] ?>">
                                                            <i class="bi bi-pencil me-2 text-secondary"></i>Konditionen bearbeiten
                                                        </a>
                                                    </li>
                                                    <?php if ($canManage): ?>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form method="post" action="?route=admin/boating/reports/recalculate" class="d-inline">
                                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                            <input type="hidden" name="date_from" value="<?= htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8') ?>">
                                                            <input type="hidden" name="date_to" value="<?= htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8') ?>">
                                                            <input type="hidden" name="instructor_id" value="<?= (int) $row['instructor_id'] ?>">
                                                            <button type="submit" class="dropdown-item small text-warning">
                                                                <i class="bi bi-arrow-clockwise me-2"></i>Honorare neu berechnen
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ========================================================================= -->
            <!-- TAB 2: EINZELFAHRTEN & ABRECHNUNGS-JOURNAL -->
            <!-- ========================================================================= -->
            <div class="tab-pane fade <?= $activeTab === 'appointments' ? 'show active' : '' ?> p-3" id="tab-appointments" role="tabpanel">

                <!-- Batch Actions Toolbar -->
                <?php if ($canManage && !empty($detailedAppointments)): ?>
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 p-2 mb-3 bg-light rounded border">
                        <div class="d-flex align-items-center gap-2">
                            <input class="form-check-input" type="checkbox" id="selectAllCheckbox">
                            <label class="form-check-label small fw-semibold" for="selectAllCheckbox">Alle auswählen</label>
                            <span class="badge bg-secondary-subtle text-dark" id="selectedCountBadge">0 gewählt</span>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <form method="post" action="?route=admin/boating/reports/batch-status" id="batchActionForm" class="d-flex gap-2">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="date_from" value="<?= htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="date_to" value="<?= htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="tab" value="appointments">
                                <input type="hidden" name="selected_ids" id="batchSelectedIds" value="">

                                <button type="submit" name="new_status" value="exported" class="btn btn-outline-primary btn-sm batch-btn" disabled>
                                    <i class="bi bi-box-arrow-up-right me-1"></i> Als «Exportiert» markieren
                                </button>
                                <button type="submit" name="new_status" value="paid" class="btn btn-outline-success btn-sm batch-btn" disabled>
                                    <i class="bi bi-check-all me-1"></i> Als «Bezahlt» markieren
                                </button>
                                <button type="submit" name="new_status" value="open" class="btn btn-outline-warning btn-sm batch-btn" disabled>
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Auf «Offen» setzen
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (empty($detailedAppointments)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-journal-x text-muted fs-1 d-block mb-2"></i>
                        <h6 class="text-muted">Keine Termine für die gewählten Filterkriterien vorhanden</h6>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="appointmentsPayrollTable">
                            <thead class="table-light small">
                                <tr>
                                    <?php if ($canManage): ?>
                                        <th style="width: 36px;"></th>
                                    <?php endif; ?>
                                    <th>ID</th>
                                    <th>Datum &amp; Zeit</th>
                                    <th>Fahrlehrer</th>
                                    <th>Kunde</th>
                                    <th>Leistung / Boot</th>
                                    <th class="text-end">Umsatz</th>
                                    <th class="text-end">Honorar (CHF)</th>
                                    <th>Payout-Status</th>
                                    <th>Notiz</th>
                                    <?php if ($canManage): ?>
                                        <th class="text-end" style="width: 80px;">Aktion</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detailedAppointments as $app): ?>
                                    <?php
                                        $durMin = InstructorPayoutService::resolveDurationMinutes($app);
                                        $pStatus = (string) ($app['instructor_payout_status'] ?? 'open');
                                        $pAmount = (float) ($app['instructor_payout_amount'] ?? 0.00);
                                    ?>
                                    <tr data-id="<?= (int) $app['id'] ?>">
                                        <?php if ($canManage): ?>
                                            <td>
                                                <input class="form-check-input item-checkbox" type="checkbox" value="<?= (int) $app['id'] ?>">
                                            </td>
                                        <?php endif; ?>
                                        <td class="font-monospace small text-muted">
                                            #<?= (int) $app['id'] ?>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark">
                                                <?= date('d.m.Y', strtotime((string) $app['date'])) ?>
                                            </div>
                                            <div class="small text-muted">
                                                <?= substr((string) $app['start_time'], 0, 5) ?> - <?= substr((string) $app['end_time'], 0, 5) ?>
                                                (<?= $durMin ?> Min)
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (!empty($app['instructor_first_name'])): ?>
                                                <div class="d-flex align-items-center gap-1">
                                                    <span class="rounded-circle border" style="width: 10px; height: 10px; background-color: <?= htmlspecialchars((string) ($app['instructor_color'] ?? '#0d6efd')) ?>;"></span>
                                                    <span class="fw-semibold small text-dark">
                                                        <?= htmlspecialchars((string) ($app['instructor_first_name'] . ' ' . $app['instructor_last_name'])) ?>
                                                    </span>
                                                </div>
                                                <?php if (!empty($app['payroll_external_code'])): ?>
                                                    <div class="font-monospace text-muted small" style="font-size: 0.72rem;">
                                                        <?= htmlspecialchars((string) $app['payroll_external_code']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted small fst-italic">Kein Fahrlehrer</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="fw-semibold small text-dark">
                                                <?= htmlspecialchars((string) ($app['customer_first_name'] . ' ' . $app['customer_last_name'])) ?>
                                            </div>
                                            <div class="text-muted small" style="font-size: 0.75rem;">
                                                <?= htmlspecialchars((string) ($app['customer_email'] ?? '')) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="small fw-semibold text-dark"><?= htmlspecialchars((string) ($app['service_name'] ?? 'Termin')) ?></div>
                                            <?php if (!empty($app['boat_name'])): ?>
                                                <div class="text-muted small"><i class="bi bi-water me-1"></i><?= htmlspecialchars((string) $app['boat_name']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end small">
                                            CHF <?= number_format((float) ($app['price'] ?? 0.00), 2) ?>
                                        </td>
                                        <td class="text-end">
                                            <span class="fw-bold text-dark">CHF <?= number_format($pAmount, 2) ?></span>
                                            <div class="text-muted small" style="font-size: 0.72rem;">
                                                <?= ($app['pay_rate_type'] ?? 'per_hour') === 'per_appointment' ? 'Pauschale' : 'CHF ' . number_format((float) ($app['pay_rate_amount'] ?? 0.0), 2) . '/h' ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($pStatus === 'open'): ?>
                                                <span class="badge bg-warning-subtle text-dark border"><i class="bi bi-hourglass-split me-1"></i>Offen</span>
                                            <?php elseif ($pStatus === 'exported'): ?>
                                                <span class="badge bg-info-subtle text-dark border"><i class="bi bi-box-arrow-up-right me-1"></i>Exportiert</span>
                                            <?php elseif ($pStatus === 'paid'): ?>
                                                <span class="badge bg-success-subtle text-dark border"><i class="bi bi-check-circle me-1"></i>Bezahlt</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small text-muted" style="max-width: 160px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <?= htmlspecialchars((string) ($app['instructor_payout_note'] ?? '')) ?>
                                        </td>
                                        <?php if ($canManage): ?>
                                            <td class="text-end">
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-secondary py-0 px-2 edit-payout-btn" 
                                                        data-id="<?= (int) $app['id'] ?>"
                                                        data-amount="<?= htmlspecialchars(number_format($pAmount, 2, '.', '')) ?>"
                                                        data-status="<?= htmlspecialchars($pStatus) ?>"
                                                        data-note="<?= htmlspecialchars((string) ($app['instructor_payout_note'] ?? '')) ?>"
                                                        data-instructor="<?= htmlspecialchars((string) ($app['instructor_first_name'] . ' ' . $app['instructor_last_name'])) ?>"
                                                        data-date="<?= htmlspecialchars((string) $app['date']) ?>"
                                                        title="Honorar &amp; Status bearbeiten">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ========================================================================= -->
            <!-- TAB 3: SCHUL-AUSWERTUNG & AUSLASTUNG -->
            <!-- ========================================================================= -->
            <div class="tab-pane fade <?= $activeTab === 'school' ? 'show active' : '' ?> p-3" id="tab-school" role="tabpanel">
                <div class="row g-4">
                    <!-- Beliebteste Services -->
                    <div class="col-lg-6">
                        <div class="card border shadow-none h-100">
                            <div class="card-header bg-light py-2">
                                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-tag-fill text-primary me-2"></i>Leistungen nach Umsatz &amp; Fahrten</h6>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($schoolSummary['services'])): ?>
                                    <div class="p-3 text-muted small text-center">Keine Daten im Zeitraum</div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover mb-0">
                                            <thead class="small text-muted">
                                                <tr>
                                                    <th>Leistung</th>
                                                    <th class="text-center">Fahrten</th>
                                                    <th class="text-end">Stunden</th>
                                                    <th class="text-end">Umsatz (CHF)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($schoolSummary['services'] as $srv): ?>
                                                    <tr>
                                                        <td class="fw-semibold"><?= htmlspecialchars((string) $srv['name']) ?></td>
                                                        <td class="text-center"><span class="badge bg-light text-dark border"><?= (int) $srv['count'] ?></span></td>
                                                        <td class="text-end"><?= number_format((float) $srv['hours'], 1) ?> h</td>
                                                        <td class="text-end fw-bold">CHF <?= number_format((float) $srv['revenue'], 2) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Boot-Auslastung -->
                    <div class="col-lg-6">
                        <div class="card border shadow-none h-100">
                            <div class="card-header bg-light py-2">
                                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-water text-info me-2"></i>Boot-Auslastung (Stunden &amp; Einsätze)</h6>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($schoolSummary['boats'])): ?>
                                    <div class="p-3 text-muted small text-center">Keine Boote im Einsatz im Zeitraum</div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover mb-0">
                                            <thead class="small text-muted">
                                                <tr>
                                                    <th>Schulungsboot</th>
                                                    <th class="text-center">Einsätze</th>
                                                    <th class="text-end">Stunden auf dem Wasser</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($schoolSummary['boats'] as $boat): ?>
                                                    <tr>
                                                        <td class="fw-semibold"><?= htmlspecialchars((string) $boat['name']) ?></td>
                                                        <td class="text-center"><span class="badge bg-light text-dark border"><?= (int) $boat['count'] ?></span></td>
                                                        <td class="text-end fw-bold"><?= number_format((float) $boat['hours'], 1) ?> h</td>
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
            </div>

            <!-- ========================================================================= -->
            <!-- TAB 4: MONATSRAPPORTE & TIMESHEET-FREIGABE (COACH) -->
            <!-- ========================================================================= -->
            <div class="tab-pane fade <?= $activeTab === 'timesheets' ? 'show active' : '' ?> p-3" id="tab-timesheets" role="tabpanel">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                    <div>
                        <h5 class="fw-bold text-dark mb-1">
                            <i class="bi bi-clock-history text-warning me-2"></i>Monatsrapporte &amp; Timesheets der Fahrlehrer
                        </h5>
                        <div class="small text-muted">
                            Prüfe eingereichte Monatszeiten, gib sie zur Lohnabrechnung frei, weise sie mit Begründung zurück oder öffne sie für Korrekturen wieder (Reopen).
                        </div>
                    </div>
                </div>

                <?php if (empty($coachTimesheets)): ?>
                    <div class="text-center py-5 bg-light rounded-4 border">
                        <i class="bi bi-calendar2-check text-muted fs-1 d-block mb-2"></i>
                        <h6 class="text-muted">Keine Monatsrapporte vorhanden</h6>
                        <p class="text-muted small mb-0">Fahrlehrer können ihre Monatszeiten im Fahrlehrer-Portal einreichen.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th>Monat</th>
                                    <th>Fahrlehrer</th>
                                    <th>Status</th>
                                    <th>Version</th>
                                    <th class="text-end">Stunden</th>
                                    <th class="text-end">Brutto (CHF)</th>
                                    <th class="text-end">Abzüge (CHF)</th>
                                    <th class="text-end">Netto (CHF)</th>
                                    <th>Eingereicht / Genehmigt</th>
                                    <th class="text-end" style="min-width: 160px;">Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $tsBadges = [
                                    'draft'     => 'secondary',
                                    'submitted' => 'warning',
                                    'approved'  => 'success',
                                    'rejected'  => 'danger',
                                    'reopened'  => 'info',
                                ];
                                $tsLabels = [
                                    'draft'     => 'Entwurf',
                                    'submitted' => 'Eingereicht',
                                    'approved'  => 'Freigegeben',
                                    'rejected'  => 'Zurückgewiesen',
                                    'reopened'  => 'Wiedereröffnet',
                                ];
                                ?>
                                <?php foreach ($coachTimesheets as $ts): ?>
                                    <?php
                                    $st = (string) ($ts['status'] ?? 'draft');
                                    $badge = $tsBadges[$st] ?? 'secondary';
                                    $label = $tsLabels[$st] ?? ucfirst($st);
                                    $instName = trim((string) (($ts['first_name'] ?? '') . ' ' . ($ts['last_name'] ?? '')));
                                    $deductions = (float) ($ts['gross_amount'] ?? 0.0) - (float) ($ts['net_amount'] ?? 0.0);
                                    ?>
                                    <tr>
                                        <td class="fw-bold font-monospace text-dark">
                                            <?= htmlspecialchars((string) $ts['year_month']) ?>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="rounded-circle border" style="width: 12px; height: 12px; background-color: <?= htmlspecialchars((string) ($ts['color_code'] ?? '#0d6efd')) ?>;"></span>
                                                <span class="fw-semibold text-dark"><?= htmlspecialchars($instName) ?></span>
                                            </div>
                                            <?php if (!empty($ts['payroll_external_code'])): ?>
                                                <small class="text-muted font-monospace"><?= htmlspecialchars((string) $ts['payroll_external_code']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $badge ?>-subtle text-<?= $badge ?> rounded-pill">
                                                <?= htmlspecialchars($label) ?>
                                            </span>
                                            <?php if ($st === 'rejected' && !empty($ts['rejection_reason'])): ?>
                                                <div class="small text-danger mt-1" style="font-size: 0.72rem;" title="<?= htmlspecialchars((string) $ts['rejection_reason']) ?>">
                                                    <i class="bi bi-info-circle me-1"></i><?= htmlspecialchars(mb_substr((string) $ts['rejection_reason'], 0, 30)) ?>...
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                v<?= (int) ($ts['pdf_version'] ?? 1) ?>
                                            </span>
                                        </td>
                                        <td class="text-end fw-semibold">
                                            <?= number_format((float) ($ts['total_hours'] ?? 0.0), 2) ?> h
                                        </td>
                                        <td class="text-end">
                                            CHF <?= number_format((float) ($ts['gross_amount'] ?? 0.0), 2, '.', "'") ?>
                                        </td>
                                        <td class="text-end text-danger small">
                                            - CHF <?= number_format(max(0, $deductions), 2, '.', "'") ?>
                                        </td>
                                        <td class="text-end fw-bold text-success">
                                            CHF <?= number_format((float) ($ts['net_amount'] ?? 0.0), 2, '.', "'") ?>
                                        </td>
                                        <td class="small text-muted">
                                            <?php if (!empty($ts['submitted_at'])): ?>
                                                <div><i class="bi bi-send me-1"></i><?= date('d.m.Y H:i', strtotime((string) $ts['submitted_at'])) ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($ts['approved_at'])): ?>
                                                <div class="text-success"><i class="bi bi-check-circle me-1"></i><?= date('d.m.Y H:i', strtotime((string) $ts['approved_at'])) ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($ts['reopened_at'])): ?>
                                                <div class="text-warning"><i class="bi bi-arrow-repeat me-1"></i>Reopened <?= date('d.m.Y H:i', strtotime((string) $ts['reopened_at'])) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <?php if ($canManage && $st === 'submitted'): ?>
                                                    <!-- Genehmigen -->
                                                    <form method="post" action="?route=admin/boating/timesheet/approve" class="d-inline" onsubmit="return confirm('Möchten Sie das Timesheet von <?= htmlspecialchars($instName) ?> für <?= htmlspecialchars((string) $ts['year_month']) ?> genehmigen? Der Fahrlehrer erhält eine Bestätigungs-E-Mail.');">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                        <input type="hidden" name="instructor_id" value="<?= (int) $ts['instructor_id'] ?>">
                                                        <input type="hidden" name="month" value="<?= htmlspecialchars((string) $ts['year_month']) ?>">
                                                        <input type="hidden" name="redirect" value="?route=admin/boating/reports&tab=timesheets">
                                                        <button type="submit" class="btn btn-outline-success btn-sm" title="Genehmigen">
                                                            <i class="bi bi-check-lg me-1"></i>Freigeben
                                                        </button>
                                                    </form>

                                                    <!-- Zurückweisen mit Pflichtbegründung -->
                                                    <button type="button" class="btn btn-outline-danger btn-sm reject-ts-btn" 
                                                            data-inst-id="<?= (int) $ts['instructor_id'] ?>"
                                                            data-inst-name="<?= htmlspecialchars($instName) ?>"
                                                            data-month="<?= htmlspecialchars((string) $ts['year_month']) ?>"
                                                            title="Zurückweisen">
                                                        <i class="bi bi-x-lg me-1"></i>Zurückweisen
                                                    </button>
                                                <?php endif; ?>

                                                <?php if ($canManage && in_array($st, ['approved', 'rejected'], true)): ?>
                                                    <!-- Wiedereröffnen (Reopen) -->
                                                    <form method="post" action="?route=admin/boating/timesheet/reopen" class="d-inline" onsubmit="return confirm('Monatsrapport <?= htmlspecialchars((string) $ts['year_month']) ?> für <?= htmlspecialchars($instName) ?> wirklich wiedereröffnen?\n\nAlle zugehörigen Arbeitszeiten werden wieder auf bearbeitbar gesetzt. Bei erneuter Einreichung wird automatisch Version <?= ((int) $ts['pdf_version'] + 1) ?> erstellt.');">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                        <input type="hidden" name="instructor_id" value="<?= (int) $ts['instructor_id'] ?>">
                                                        <input type="hidden" name="month" value="<?= htmlspecialchars((string) $ts['year_month']) ?>">
                                                        <input type="hidden" name="redirect" value="?route=admin/boating/reports&tab=timesheets">
                                                        <button type="submit" class="btn btn-outline-warning btn-sm" title="Wiedereröffnen für Korrekturen">
                                                            <i class="bi bi-arrow-repeat me-1"></i>Wiedereröffnen
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ========================================================================= -->
            <!-- TAB 5: LOHNABZUGSSÄTZE (SCHWEIZ) & ZENTRALES AUDIT-LOG -->
            <!-- ========================================================================= -->
            <div class="tab-pane fade <?= $activeTab === 'settings' ? 'show active' : '' ?> p-3" id="tab-settings" role="tabpanel">
                <div class="row g-4">
                    <!-- Schweizer Lohnabzugssätze Konfiguration -->
                    <div class="col-lg-6">
                        <div class="card border rounded-4 shadow-sm h-100">
                            <div class="card-header bg-white py-3 border-bottom">
                                <h6 class="fw-bold mb-0 text-dark">
                                    <i class="bi bi-sliders text-primary me-2"></i>Zentrale Schweizer Lohn-Abzugssätze
                                </h6>
                                <div class="small text-muted mt-1">
                                    Standard-Abzugssätze für die AHV/IV/EO-, ALV-, NBU- und KTG-Berechnung. Individuelle Abweichungen können in der HR-Akte des Coaches übersteuert werden.
                                </div>
                            </div>
                            <div class="card-body p-4">
                                <form method="post" action="?route=admin/boating/payroll/settings">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                                    <div class="row g-3">
                                        <div class="col-sm-6">
                                            <label class="form-label small fw-semibold">AHV / IV / EO Satz (%)</label>
                                            <div class="input-group input-group-sm">
                                                <input type="number" step="0.01" min="0" max="100" name="payroll_ahv_rate" class="form-control" value="<?= htmlspecialchars((string) ($payrollSettings['payroll_ahv_rate'] ?? '5.30')) ?>" required>
                                                <span class="input-group-text bg-light">%</span>
                                            </div>
                                            <small class="text-muted">CH-Standard: 5.30 %</small>
                                        </div>

                                        <div class="col-sm-6">
                                            <label class="form-label small fw-semibold">ALV Satz (%)</label>
                                            <div class="input-group input-group-sm">
                                                <input type="number" step="0.01" min="0" max="100" name="payroll_alv_rate" class="form-control" value="<?= htmlspecialchars((string) ($payrollSettings['payroll_alv_rate'] ?? '1.10')) ?>" required>
                                                <span class="input-group-text bg-light">%</span>
                                            </div>
                                            <small class="text-muted">CH-Standard: 1.10 %</small>
                                        </div>

                                        <div class="col-sm-6">
                                            <label class="form-label small fw-semibold">NBU Nichtberufsunfall (%)</label>
                                            <div class="input-group input-group-sm">
                                                <input type="number" step="0.01" min="0" max="100" name="payroll_nbu_rate" class="form-control" value="<?= htmlspecialchars((string) ($payrollSettings['payroll_nbu_rate'] ?? '1.30')) ?>" required>
                                                <span class="input-group-text bg-light">%</span>
                                            </div>
                                            <small class="text-muted">Je nach Prämie: ca. 1.20 - 1.50 %</small>
                                        </div>

                                        <div class="col-sm-6">
                                            <label class="form-label small fw-semibold">BVG Pensionskasse (%)</label>
                                            <div class="input-group input-group-sm">
                                                <input type="number" step="0.01" min="0" max="100" name="payroll_bvg_rate" class="form-control" value="<?= htmlspecialchars((string) ($payrollSettings['payroll_bvg_rate'] ?? '0.00')) ?>">
                                                <span class="input-group-text bg-light">%</span>
                                            </div>
                                            <small class="text-muted">0.00 % wenn coach-spezifisch</small>
                                        </div>

                                        <div class="col-sm-6">
                                            <label class="form-label small fw-semibold">KTG Krankentaggeld (%)</label>
                                            <div class="input-group input-group-sm">
                                                <input type="number" step="0.01" min="0" max="100" name="payroll_ktg_rate" class="form-control" value="<?= htmlspecialchars((string) ($payrollSettings['payroll_ktg_rate'] ?? '1.00')) ?>">
                                                <span class="input-group-text bg-light">%</span>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <label class="form-label small fw-semibold">Quellensteuer Standard (%)</label>
                                            <div class="input-group input-group-sm">
                                                <input type="number" step="0.01" min="0" max="100" name="payroll_withholding_tax" class="form-control" value="<?= htmlspecialchars((string) ($payrollSettings['payroll_withholding_tax'] ?? '0.00')) ?>">
                                                <span class="input-group-text bg-light">%</span>
                                            </div>
                                            <small class="text-muted">Nur für quellensteuerpflichtige Coaches</small>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label small fw-semibold">E-Mail Buchhaltung (Benachrichtigungen)</label>
                                            <input type="email" name="payroll_notification_email" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($payrollSettings['payroll_notification_email'] ?? 'payroll@bootschule.ch')) ?>" placeholder="payroll@bootschule.ch">
                                            <small class="text-muted">Erhält automatisch eine Meldung bei Einreichung von Timesheets.</small>
                                        </div>

                                        <?php if ($canManage): ?>
                                            <div class="col-12 pt-2">
                                                <button type="submit" class="btn btn-primary btn-sm fw-semibold px-4 rounded-pill">
                                                    <i class="bi bi-save me-1"></i> Abzugssätze speichern
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Audit-Log & Sicherheits-Protokoll -->
                    <div class="col-lg-6">
                        <div class="card border rounded-4 shadow-sm h-100">
                            <div class="card-header bg-white py-3 border-bottom">
                                <h6 class="fw-bold mb-0 text-dark">
                                    <i class="bi bi-shield-check text-success me-2"></i>Sicherheits- &amp; Audit-Protokoll
                                </h6>
                                <div class="small text-muted mt-1">
                                    Revisionssicheres Protokoll aller Statuswechsel, Reopenings und Genehmigungen.
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($coachAuditLogs)): ?>
                                    <div class="p-4 text-center text-muted small">
                                        Noch keine Audit-Einträge vorhanden.
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive" style="max-height: 480px; overflow-y: auto;">
                                        <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.82rem;">
                                            <thead class="table-light sticky-top">
                                                <tr>
                                                    <th>Zeitpunkt</th>
                                                    <th>Aktion</th>
                                                    <th>Fahrlehrer</th>
                                                    <th>Statuswechsel</th>
                                                    <th>IP</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($coachAuditLogs as $log): ?>
                                                    <tr>
                                                        <td class="text-muted font-monospace">
                                                            <?= date('d.m. H:i', strtotime((string) $log['created_at'])) ?>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-light text-dark border">
                                                                <?= htmlspecialchars((string) $log['action']) ?>
                                                            </span>
                                                        </td>
                                                        <td class="fw-semibold text-dark">
                                                            <?= htmlspecialchars((string) (($log['instructor_first_name'] ?? '') . ' ' . ($log['instructor_last_name'] ?? ''))) ?>
                                                        </td>
                                                        <td>
                                                            <?php if (!empty($log['old_status']) || !empty($log['new_status'])): ?>
                                                                <span class="badge bg-secondary-subtle text-muted"><?= htmlspecialchars((string) $log['old_status']) ?></span>
                                                                <i class="bi bi-arrow-right mx-1 small text-muted"></i>
                                                                <span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars((string) $log['new_status']) ?></span>
                                                            <?php else: ?>
                                                                <span class="text-muted">-</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-muted font-monospace" style="font-size: 0.72rem;">
                                                            <?= htmlspecialchars((string) ($log['ip_address'] ?? '')) ?>
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
            </div>

        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: EINZEL-HONORAR ANPASSEN -->
<!-- ========================================================================= -->
<div class="modal fade" id="editPayoutModal" tabindex="-1" aria-labelledby="editPayoutModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content shadow">
            <form method="post" action="?route=admin/boating/reports/update-payout">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="appointment_id" id="editPayoutAppId" value="">
                <input type="hidden" name="date_from" value="<?= htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="date_to" value="<?= htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="tab" value="appointments">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="editPayoutModalLabel">
                        <i class="bi bi-cash-coin text-success me-2"></i>Fahrlehrer-Honorar anpassen
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3 p-2 bg-light rounded border small">
                        <div><strong>Termin:</strong> <span id="modalAppInfo">-</span></div>
                        <div><strong>Fahrlehrer:</strong> <span id="modalInstructorInfo">-</span></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Honorar-Auszahlungsbetrag (CHF)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white">CHF</span>
                            <input type="number" step="0.05" min="0" name="payout_amount" id="modalPayoutAmount" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Abrechnungsstatus</label>
                        <select name="payout_status" id="modalPayoutStatus" class="form-select">
                            <option value="open">Offen (für nächste Lohnrunde)</option>
                            <option value="exported">Exportiert (an Lohnsystem übergeben)</option>
                            <option value="paid">Bezahlt (abgeschlossen)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Abrechnungsnotiz / Bemerkung</label>
                        <input type="text" name="payout_note" id="modalPayoutNote" class="form-control" placeholder="z. B. Manuelle Zulage oder Abzug...">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-sm btn-primary fw-semibold px-3">Änderungen speichern</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: BULK NEUBERECHNUNG -->
<!-- ========================================================================= -->
<div class="modal fade" id="bulkRecalculateModal" tabindex="-1" aria-labelledby="bulkRecalculateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content shadow">
            <form method="post" action="?route=admin/boating/reports/recalculate">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="bulkRecalculateModalLabel">
                        <i class="bi bi-calculator text-primary me-2"></i>Honorare im Zeitraum neu berechnen
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3">
                        Berechnet die Honorare aller offenen Fahrten im gewählten Datumsbereich anhand der aktuellen Fahrlehrer-Konditionen neu (Stunden x Satz oder Pauschale).
                    </p>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Von</label>
                            <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Bis</label>
                            <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Fahrlehrer einschränken (optional)</label>
                        <select name="instructor_id" class="form-select form-select-sm">
                            <option value="">-- Alle Fahrlehrer --</option>
                            <?php foreach ($instructors as $inst): ?>
                                <option value="<?= (int) $inst['id'] ?>">
                                    <?= htmlspecialchars((string) ($inst['first_name'] . ' ' . $inst['last_name'])) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="only_open" id="onlyOpenSwitch" value="1" checked>
                        <label class="form-check-label small" for="onlyOpenSwitch">
                            Nur Termine mit Status «Offen» neuberechnen (bereits exportierte/bezahlte nicht überschreiben)
                        </label>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-sm btn-primary fw-semibold px-3">Jetzt neu berechnen</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: TIMESHEET ZURÜCKWEISEN MIT PFLICHTBEGRÜNDUNG -->
<!-- ========================================================================= -->
<div class="modal fade" id="rejectTimesheetModal" tabindex="-1" aria-labelledby="rejectTimesheetModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content shadow border-danger">
            <form method="post" action="?route=admin/boating/timesheet/reject">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="instructor_id" id="rejectTsInstructorId" value="">
                <input type="hidden" name="month" id="rejectTsMonth" value="">
                <input type="hidden" name="redirect" value="?route=admin/boating/reports&tab=timesheets">

                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold" id="rejectTimesheetModalLabel">
                        <i class="bi bi-x-circle me-2"></i>Monatsrapport zurückweisen
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="p-3 bg-light rounded border mb-3 small">
                        <div><strong>Fahrlehrer:</strong> <span id="rejectTsInstructorName">-</span></div>
                        <div><strong>Monat:</strong> <span id="rejectTsMonthLabel">-</span></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-danger">Begründung für die Zurückweisung <span class="text-danger">* (Pflichtfeld)</span></label>
                        <textarea name="rejection_reason" id="rejectTsReason" rows="4" class="form-control" placeholder="z. B. 2 Fahrstunden am 14.10. fehlen oder Bootswartung muss detailliert nachgewiesen werden..." required></textarea>
                        <div class="form-text small">Diese Begründung wird dem Fahrlehrer per E-Mail zugestellt und im Timesheet hinterlegt.</div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-sm btn-danger fw-semibold px-3">
                        <i class="bi bi-send-x me-1"></i>Zurückweisung absenden
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Vanilla JS für Tabs, Presets, Modal und Batch-Aktionen -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Tab State Tracking
    const activeTabInput = document.getElementById('activeTabInput');
    const tabBtns = document.querySelectorAll('#reportTabs button[data-bs-toggle="tab"]');
    tabBtns.forEach(function (btn) {
        btn.addEventListener('shown.bs.tab', function (e) {
            const targetId = e.target.getAttribute('data-bs-target').replace('#tab-', '');
            if (activeTabInput) {
                activeTabInput.value = targetId;
            }
        });
    });

    // 2. Datum-Presets
    const presetBtns = document.querySelectorAll('.date-preset-btn');
    const fromInput = document.getElementById('filterDateFrom');
    const toInput   = document.getElementById('filterDateTo');
    const filterForm = document.getElementById('reportFilterForm');

    presetBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            const f = this.getAttribute('data-from');
            const t = this.getAttribute('data-to');
            if (fromInput && toInput) {
                fromInput.value = f;
                toInput.value = t;
                filterForm.submit();
            }
        });
    });

    // 3. Edit Payout Modal
    const editModalEl = document.getElementById('editPayoutModal');
    const editModal = editModalEl && window.bootstrap ? new bootstrap.Modal(editModalEl) : null;
    const editButtons = document.querySelectorAll('.edit-payout-btn');

    editButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            const amount = this.getAttribute('data-amount');
            const status = this.getAttribute('data-status');
            const note = this.getAttribute('data-note');
            const instructor = this.getAttribute('data-instructor');
            const date = this.getAttribute('data-date');

            document.getElementById('editPayoutAppId').value = id;
            document.getElementById('modalPayoutAmount').value = amount;
            document.getElementById('modalPayoutStatus').value = status;
            document.getElementById('modalPayoutNote').value = note;
            document.getElementById('modalAppInfo').textContent = '#' + id + ' (' + date + ')';
            document.getElementById('modalInstructorInfo').textContent = instructor;

            if (editModal) {
                editModal.show();
            }
        });
    });

    // 4. Batch Checkbox Selection
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    const itemCheckboxes = document.querySelectorAll('.item-checkbox');
    const batchButtons = document.querySelectorAll('.batch-btn');
    const selectedCountBadge = document.getElementById('selectedCountBadge');
    const batchSelectedIdsInput = document.getElementById('batchSelectedIds');

    function updateBatchSelection() {
        const selectedIds = [];
        itemCheckboxes.forEach(function (cb) {
            if (cb.checked) {
                selectedIds.push(cb.value);
            }
        });

        if (batchSelectedIdsInput) {
            batchSelectedIdsInput.value = selectedIds.join(',');
        }

        if (selectedCountBadge) {
            selectedCountBadge.textContent = selectedIds.length + ' gewählt';
        }

        const hasSelected = selectedIds.length > 0;
        batchButtons.forEach(function (b) {
            b.disabled = !hasSelected;
        });

        if (selectAllCheckbox) {
            selectAllCheckbox.checked = (selectedIds.length === itemCheckboxes.length && itemCheckboxes.length > 0);
        }
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            const checked = this.checked;
            itemCheckboxes.forEach(function (cb) {
                cb.checked = checked;
            });
            updateBatchSelection();
        });
    }

    itemCheckboxes.forEach(function (cb) {
        cb.addEventListener('change', updateBatchSelection);
    });

    // 5. Reject Timesheet Modal
    const rejectModalEl = document.getElementById('rejectTimesheetModal');
    const rejectModal = rejectModalEl && window.bootstrap ? new bootstrap.Modal(rejectModalEl) : null;
    const rejectButtons = document.querySelectorAll('.reject-ts-btn');

    rejectButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            const instId = this.getAttribute('data-inst-id');
            const instName = this.getAttribute('data-inst-name');
            const month = this.getAttribute('data-month');

            document.getElementById('rejectTsInstructorId').value = instId;
            document.getElementById('rejectTsMonth').value = month;
            document.getElementById('rejectTsInstructorName').textContent = instName;
            document.getElementById('rejectTsMonthLabel').textContent = month;
            document.getElementById('rejectTsReason').value = '';

            if (rejectModal) {
                rejectModal.show();
            }
        });
    });
});
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__, 3) . '/views/layouts/admin.php';
