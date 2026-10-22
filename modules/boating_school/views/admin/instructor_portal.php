<?php

declare(strict_types=1);

/**
 * Fahrlehrer-Portal & Monatsabschluss (/admin/boating/instructor-portal)
 *
 * Beinhaltet:
 * - Fahrten-Übersicht & Monatsabschluss
 * - Bevorstehende Termine
 * - Absenzen & Sperrzeiten (mit Standort location_id & Feiertags-Overrides)
 * - Zeiterfassung & Schweizer Lohn-Timesheet mit Status (draft, submitted, approved, rejected, reopened)
 * - Wochen-Arbeitsplan (bs_coach_weekly_templates)
 *
 * @var array<string, mixed>|null $instructor
 * @var array<int, array<string, mixed>> $allInstructors
 * @var bool $canSwitchInstructor
 * @var string $selectedMonth Format 'YYYY-MM'
 * @var array<string, mixed> $monthStats
 * @var array<int, array<string, mixed>> $monthAppointments
 * @var array<int, array<string, mixed>> $upcomingAppointments
 * @var array<int, array<string, mixed>> $coachAvailabilities
 * @var array<int, array<string, mixed>> $coachWorkingHours
 * @var array<string, mixed> $timesheetSummary
 * @var array<int, array<string, mixed>> $weeklyTemplates
 * @var array<int, array<string, mixed>> $locations
 * @var string $csrfToken
 */

$title = 'Mein Fahrlehrer-Portal & Monatsabschluss';
$currentRoute = 'admin/boating/instructor-portal';

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$statusBadges = AppointmentService::STATUS_BADGES ?? [
    'requested'          => 'warning',
    'confirmed'          => 'success',
    'cancelled_customer' => 'danger',
    'cancelled_system'   => 'danger',
    'completed'          => 'primary',
];

$payoutBadges = [
    'open'     => 'warning',
    'exported' => 'info',
    'paid'     => 'success',
];

$payoutLabels = [
    'open'     => 'Offen (Zur Übergabe bereit)',
    'exported' => 'An Admin übergeben',
    'paid'     => 'Ausbezahlt',
];

$locationsMap = [];
if (!empty($locations)) {
    foreach ($locations as $l) {
        $locationsMap[(int) $l['id']] = $l['name'];
    }
}

$monthTimestamp = strtotime($selectedMonth . '-01');
$monthsDe = [
    'January'   => 'Januar',
    'February'  => 'Februar',
    'March'     => 'März',
    'April'     => 'April',
    'May'       => 'Mai',
    'June'      => 'Juni',
    'July'      => 'Juli',
    'August'    => 'August',
    'September' => 'September',
    'October'   => 'Oktober',
    'November'  => 'November',
    'December'  => 'Dezember',
];
$englishMonth = date('F', $monthTimestamp);
$germanMonthName = $monthsDe[$englishMonth] ?? $englishMonth;
$monthDisplay = $germanMonthName . ' ' . date('Y', $monthTimestamp);

$tsStatus = (string) ($timesheetSummary['timesheet_status'] ?? 'draft');
$pdfVersion = (int) ($timesheetSummary['pdf_version'] ?? 1);

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

<?php if (!$instructor): ?>
    <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
        <i class="bi bi-person-x text-muted fs-1 mb-3"></i>
        <h4 class="fw-bold mb-2">Kein Fahrlehrerprofil zugeordnet</h4>
        <p class="text-muted max-w-600 mx-auto mb-4" style="max-width: 500px;">
            Dein Benutzerkonto ist aktuell noch keinem Fahrlehrer-Datensatz im Modul zugewiesen. Bitte wende dich an die Administration oder wähle oben einen Fahrlehrer aus.
        </p>
        <?php if (!empty($allInstructors)): ?>
            <div class="max-w-400 mx-auto" style="max-width: 360px;">
                <form method="get" action="">
                    <input type="hidden" name="route" value="admin/boating/instructor-portal">
                    <label class="form-label small fw-semibold text-muted">Fahrlehrer zur Ansicht wählen:</label>
                    <select name="instructor_id" class="form-select mb-3" onchange="this.form.submit()">
                        <option value="">-- Fahrlehrer wählen --</option>
                        <?php foreach ($allInstructors as $inst): ?>
                            <option value="<?= (int) $inst['id'] ?>">
                                <?= htmlspecialchars((string) ($inst['first_name'] . ' ' . $inst['last_name'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        <?php endif; ?>
    </div>
<?php else: ?>

    <!-- Kopfzeile -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="?route=admin/boating">Bootsfahrschule</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Fahrlehrer-Portal</li>
                </ol>
            </nav>
            <h1 class="h3 fw-bold text-dark mb-0">
                <i class="bi bi-person-workspace text-primary me-2"></i>Fahrlehrer-Portal: <?= htmlspecialchars((string) ($instructor['first_name'] . ' ' . $instructor['last_name'])) ?>
            </h1>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <?php if ($canSwitchInstructor && count($allInstructors) > 1): ?>
                <!-- Admin-Wechsler -->
                <form method="get" action="" class="d-flex align-items-center gap-2">
                    <input type="hidden" name="route" value="admin/boating/instructor-portal">
                    <input type="hidden" name="month" value="<?= htmlspecialchars($selectedMonth, ENT_QUOTES, 'UTF-8') ?>">
                    <span class="small text-muted fw-semibold d-none d-sm-inline">Fahrlehrer:</span>
                    <select name="instructor_id" class="form-select form-select-sm shadow-sm" onchange="this.form.submit()">
                        <?php foreach ($allInstructors as $inst): ?>
                            <option value="<?= (int) $inst['id'] ?>" <?= ((int) $inst['id'] === (int) $instructor['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) ($inst['first_name'] . ' ' . $inst['last_name'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            <?php endif; ?>

            <a href="?route=admin/boating/calendar" class="btn btn-outline-primary btn-sm shadow-sm">
                <i class="bi bi-calendar3 me-1"></i> Kalender öffnen
            </a>
        </div>
    </div>

    <!-- Fahrlehrer Profil & Vergütungs-Banner -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
        <div class="card-body p-3 p-md-4">
            <div class="row g-3 align-items-center">
                <div class="col-md-7 d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold fs-4 shadow-sm" style="width: 56px; height: 56px;">
                        <?= htmlspecialchars(mb_substr((string) $instructor['first_name'], 0, 1) . mb_substr((string) $instructor['last_name'], 0, 1)) ?>
                    </div>
                    <div>
                        <div class="h5 fw-bold text-dark mb-1">
                            <?= htmlspecialchars((string) ($instructor['first_name'] . ' ' . $instructor['last_name'])) ?>
                            <span class="badge bg-success-subtle text-success ms-2 small fw-normal">Aktiv</span>
                        </div>
                        <div class="small text-muted d-flex flex-wrap gap-3">
                            <?php if (!empty($instructor['license_number'])): ?>
                                <span><i class="bi bi-award me-1 text-primary"></i>Lizenz: <?= htmlspecialchars((string) $instructor['license_number']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($instructor['email'])): ?>
                                <span><i class="bi bi-envelope me-1"></i><?= htmlspecialchars((string) $instructor['email']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($instructor['payroll_external_code'])): ?>
                                <span><i class="bi bi-hash me-1"></i>Lohn-Code: <?= htmlspecialchars((string) $instructor['payroll_external_code']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-5 text-md-end">
                    <div class="p-2 px-3 bg-light rounded-3 d-inline-block text-start border">
                        <div class="small text-muted fw-semibold text-uppercase">Vereinbartes Honorarmodell:</div>
                        <div class="fw-bold text-dark">
                            <?php if (($instructor['pay_rate_type'] ?? 'per_hour') === 'per_appointment'): ?>
                                <i class="bi bi-cash me-1 text-success"></i>CHF <?= number_format((float) ($instructor['pay_rate_amount'] ?? 0), 2, '.', "'") ?> Pauschale pro Termin
                            <?php else: ?>
                                <i class="bi bi-clock-history me-1 text-primary"></i>CHF <?= number_format((float) ($instructor['pay_rate_amount'] ?? 0), 2, '.', "'") ?> pro Stunde
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Monatsauswahl & Monatsabschluss Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
        <div class="card-header bg-white border-bottom p-3 p-md-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h5 class="fw-bold text-dark mb-1">
                    <i class="bi bi-calendar2-month text-primary me-2"></i>Monatsauswertung &amp; Abschluss: <?= htmlspecialchars($monthDisplay) ?>
                </h5>
                <div class="small text-muted">
                    Prüfe deine Fahrten und übergebe den Monat an die Buchhaltung für die Lohnabrechnung.
                </div>
            </div>

            <!-- Monats-Filter Formular -->
            <form method="get" action="" class="d-flex align-items-center gap-2">
                <input type="hidden" name="route" value="admin/boating/instructor-portal">
                <?php if ($canSwitchInstructor): ?>
                    <input type="hidden" name="instructor_id" value="<?= (int) $instructor['id'] ?>">
                <?php endif; ?>
                <label class="small text-muted fw-semibold me-1 d-none d-sm-inline">Monat:</label>
                <input type="month" name="month" class="form-control form-control-sm" value="<?= htmlspecialchars($selectedMonth, ENT_QUOTES, 'UTF-8') ?>" onchange="this.form.submit()">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-arrow-repeat"></i>
                </button>
            </form>
        </div>

        <div class="card-body p-3 p-md-4">
            <!-- KPI-Karten für den Monat -->
            <div class="row g-3 mb-4">
                <div class="col-xl-3 col-sm-6">
                    <div class="p-3 bg-light rounded-3 border h-100">
                        <div class="small text-muted fw-semibold text-uppercase mb-1">Fahrten / Termine</div>
                        <div class="h3 fw-bold text-dark mb-0"><?= (int) ($monthStats['total_appointments'] ?? 0) ?></div>
                        <div class="small text-muted mt-1">
                            <?= (int) ($monthStats['completed_appointments'] ?? 0) ?> abgeschlossen
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-sm-6">
                    <div class="p-3 bg-light rounded-3 border h-100">
                        <div class="small text-muted fw-semibold text-uppercase mb-1">Ausbildungsstunden</div>
                        <div class="h3 fw-bold text-primary mb-0"><?= number_format((float) ($monthStats['total_hours'] ?? 0.0), 1) ?> h</div>
                        <div class="small text-muted mt-1">effektive Wasserzeit</div>
                    </div>
                </div>

                <div class="col-xl-3 col-sm-6">
                    <div class="p-3 bg-light rounded-3 border h-100">
                        <div class="small text-muted fw-semibold text-uppercase mb-1">Honorar Offen</div>
                        <div class="h3 fw-bold text-warning mb-0">CHF <?= number_format((float) ($monthStats['open_amount'] ?? 0.0), 2, '.', "'") ?></div>
                        <div class="small text-muted mt-1">
                            <?= (int) ($monthStats['open_count'] ?? 0) ?> Termin(e) noch nicht eingereicht
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-sm-6">
                    <div class="p-3 bg-light rounded-3 border h-100">
                        <div class="small text-muted fw-semibold text-uppercase mb-1">Honorar Gesamt</div>
                        <div class="h3 fw-bold text-success mb-0">CHF <?= number_format((float) ($monthStats['total_amount'] ?? 0.0), 2, '.', "'") ?></div>
                        <div class="small text-muted mt-1">
                            CHF <?= number_format((float) ($monthStats['exported_amount'] ?? 0.0), 2, '.', "'") ?> eingereicht /
                            CHF <?= number_format((float) ($monthStats['paid_amount'] ?? 0.0), 2, '.', "'") ?> bezahlt
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monatsabschluss Aktion & Banner -->
            <?php $openCount = (int) ($monthStats['open_count'] ?? 0); ?>
            <?php $totalMonthApps = (int) ($monthStats['total_appointments'] ?? 0); ?>

            <?php if ($openCount > 0): ?>
                <div class="p-3 p-md-4 rounded-4 border border-warning bg-warning bg-opacity-10 mb-2">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div>
                            <div class="h5 fw-bold text-dark mb-1">
                                <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>Monatsabschluss bereit für <?= htmlspecialchars($monthDisplay) ?>
                            </div>
                            <div class="text-dark small">
                                Du hast <strong><?= $openCount ?> offene Termin(e)</strong> im Gesamtwert von <strong>CHF <?= number_format((float) ($monthStats['open_amount'] ?? 0.0), 2, '.', "'") ?></strong>.
                                Mit dem Abschluss markierst du alle deine Fahrten dieses Monats als geprüft und übergibst sie an die Administration zur Lohnabrechnung.
                            </div>
                        </div>

                        <form method="post" action="?route=admin/boating/instructor-portal/close-month" onsubmit="return confirm('Möchtest du den Monat <?= htmlspecialchars($monthDisplay) ?> wirklich abschließen und alle <?= $openCount ?> offenen Termine zur Abrechnung an den Admin übergeben?');">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="instructor_id" value="<?= (int) $instructor['id'] ?>">
                            <input type="hidden" name="month" value="<?= htmlspecialchars($selectedMonth, ENT_QUOTES, 'UTF-8') ?>">
                            <button type="submit" class="btn btn-warning fw-bold px-4 rounded-pill shadow-sm">
                                <i class="bi bi-check2-all me-1"></i> Monat <?= htmlspecialchars($monthDisplay) ?> abschließen
                            </button>
                        </form>
                    </div>
                </div>
            <?php elseif ($totalMonthApps > 0): ?>
                <div class="p-3 rounded-4 border border-success bg-success bg-opacity-10 mb-2 d-flex align-items-center gap-3">
                    <i class="bi bi-check-circle-fill text-success fs-3"></i>
                    <div>
                        <div class="fw-bold text-dark">Monat <?= htmlspecialchars($monthDisplay) ?> ist vollständig abgeschlossen &amp; übergeben</div>
                        <div class="small text-muted">
                            Alle Fahrten dieses Monats wurden erfolgreich eingereicht oder bereits ausbezahlt. Keine offenen Aktionen erforderlich.
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="p-3 rounded-4 border bg-light text-muted small d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-info-circle text-primary"></i>
                    In diesem Monat wurden keine Termine oder Fahrstunden für diesen Fahrlehrer eingetragen.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tabs: Termine im Monat, Bevorstehende Termine, Absenzen & Timesheet, Wochen-Plan -->
    <ul class="nav nav-tabs mb-4 border-bottom" id="portalTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-semibold" id="month-tab" data-bs-toggle="tab" data-bs-target="#tab-month" type="button" role="tab">
                <i class="bi bi-list-check me-2"></i>Fahrten im <?= htmlspecialchars($monthDisplay) ?>
                <span class="badge bg-secondary ms-1 rounded-pill"><?= count($monthAppointments) ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold" id="upcoming-tab" data-bs-toggle="tab" data-bs-target="#tab-upcoming" type="button" role="tab">
                <i class="bi bi-calendar-event me-2"></i>Bevorstehende Termine
                <span class="badge bg-primary ms-1 rounded-pill"><?= count($upcomingAppointments) ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold" id="avail-tab" data-bs-toggle="tab" data-bs-target="#tab-avail" type="button" role="tab">
                <i class="bi bi-calendar-x me-2"></i>Absenzen &amp; Sperrzeiten
                <span class="badge bg-warning text-dark ms-1 rounded-pill"><?= count($coachAvailabilities ?? []) ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold" id="timesheet-tab" data-bs-toggle="tab" data-bs-target="#tab-timesheet" type="button" role="tab">
                <i class="bi bi-clock-history me-2"></i>Zeiterfassung &amp; Timesheets
                <span class="badge bg-info text-dark ms-1 rounded-pill"><?= count($coachWorkingHours ?? []) ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold" id="templates-tab" data-bs-toggle="tab" data-bs-target="#tab-templates" type="button" role="tab">
                <i class="bi bi-calendar-week me-2"></i>Wochen-Arbeitsplan
                <span class="badge bg-success-subtle text-success ms-1 rounded-pill"><?= count($weeklyTemplates ?? []) ?></span>
            </button>
        </li>
    </ul>

    <div class="tab-content" id="portalTabsContent">
        <!-- Tab 1: Monats-Fahrten -->
        <div class="tab-pane fade show active" id="tab-month" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-muted text-uppercase">
                            <tr>
                                <th class="ps-4">Datum &amp; Zeit</th>
                                <th>Schüler / Kunde</th>
                                <th>Boot &amp; Leistung</th>
                                <th>Dauer</th>
                                <th>Honorar</th>
                                <th>Abrechnungsstatus</th>
                                <th class="pe-4">Bemerkung</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($monthAppointments)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        Keine Termine im Monat <?= htmlspecialchars($monthDisplay) ?> gefunden.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($monthAppointments as $app): ?>
                                    <?php
                                    $pStatus = (string) ($app['instructor_payout_status'] ?? 'open');
                                    $pBadge = $payoutBadges[$pStatus] ?? 'secondary';
                                    $pLabel = $payoutLabels[$pStatus] ?? $pStatus;
                                    $duration = InstructorPayoutService::resolveDurationMinutes($app);
                                    ?>
                                    <tr>
                                        <td class="ps-4 py-3">
                                            <div class="fw-bold text-dark"><?= date('d.m.Y', strtotime((string) $app['date'])) ?></div>
                                            <div class="small text-muted"><?= substr((string) $app['start_time'], 0, 5) ?> – <?= substr((string) $app['end_time'], 0, 5) ?> Uhr</div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark">
                                                <?= htmlspecialchars((string) (($app['customer_first_name'] ?? '') . ' ' . ($app['customer_last_name'] ?? ''))) ?>
                                            </div>
                                            <?php if (!empty($app['customer_phone'])): ?>
                                                <div class="small text-muted"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars((string) $app['customer_phone']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="text-dark small fw-semibold"><?= htmlspecialchars((string) ($app['service_name'] ?? 'Fahrstunde')) ?></div>
                                            <div class="small text-muted"><i class="bi bi-life-preserver me-1"></i><?= htmlspecialchars((string) ($app['boat_name'] ?? '-')) ?></div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border"><?= $duration ?> Min</span>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark">CHF <?= number_format((float) ($app['instructor_payout_amount'] ?? 0), 2, '.', "'") ?></div>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $pBadge ?>-subtle text-<?= $pBadge ?> border border-<?= $pBadge ?>-subtle px-2 py-1 rounded-pill small">
                                                <?= htmlspecialchars($pLabel) ?>
                                            </span>
                                        </td>
                                        <td class="pe-4 small text-muted">
                                            <?= htmlspecialchars((string) ($app['instructor_payout_note'] ?? '')) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab 2: Bevorstehende Termine -->
        <div class="tab-pane fade" id="tab-upcoming" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-muted text-uppercase">
                            <tr>
                                <th class="ps-4">Datum &amp; Uhrzeit</th>
                                <th>Schüler / Kontakt</th>
                                <th>Leistung &amp; Schulungsboot</th>
                                <th>Treffpunkt / Hafen</th>
                                <th>Status</th>
                                <th class="pe-4 text-end">Honorar (Vorschau)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($upcomingAppointments)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        Keine bevorstehenden Termine eingetragen.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($upcomingAppointments as $app): ?>
                                    <?php
                                    $appStatus = (string) ($app['status'] ?? 'requested');
                                    $appBadge = $statusBadges[$appStatus] ?? 'secondary';
                                    ?>
                                    <tr>
                                        <td class="ps-4 py-3">
                                            <div class="fw-bold text-primary"><?= date('d.m.Y', strtotime((string) $app['date'])) ?></div>
                                            <div class="small text-dark fw-semibold"><?= substr((string) $app['start_time'], 0, 5) ?> – <?= substr((string) $app['end_time'], 0, 5) ?> Uhr</div>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark">
                                                <?= htmlspecialchars((string) (($app['customer_first_name'] ?? '') . ' ' . ($app['customer_last_name'] ?? ''))) ?>
                                            </div>
                                            <div class="small text-muted">
                                                <?php if (!empty($app['customer_phone'])): ?>
                                                    <span class="me-2"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars((string) $app['customer_phone']) ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($app['customer_email'])): ?>
                                                    <span><i class="bi bi-envelope me-1"></i><?= htmlspecialchars((string) $app['customer_email']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars((string) ($app['service_name'] ?? 'Termin')) ?></div>
                                            <div class="small text-muted"><?= htmlspecialchars((string) ($app['boat_name'] ?? 'Boot wird zugewiesen')) ?></div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                <i class="bi bi-geo-alt me-1 text-danger"></i><?= htmlspecialchars((string) ($app['location_name'] ?? 'Hafen')) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $appBadge ?>-subtle text-<?= $appBadge ?> border border-<?= $appBadge ?>-subtle rounded-pill">
                                                <?= htmlspecialchars(ucfirst($appStatus)) ?>
                                            </span>
                                        </td>
                                        <td class="pe-4 text-end fw-bold text-dark">
                                            CHF <?= number_format((float) ($app['instructor_payout_amount'] ?? 0), 2, '.', "'") ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab 3: Absenzen & Sperrzeiten (mit Standort & Feiertagsoverrides) -->
        <div class="tab-pane fade" id="tab-avail" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                <div class="card-header bg-white p-3 p-md-4 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <h6 class="fw-bold text-dark mb-1">
                            <i class="bi bi-calendar-x text-warning me-2"></i>Eingetragene Absenzen &amp; Sperrzeiten (<?= htmlspecialchars($monthDisplay) ?>)
                        </h6>
                        <div class="small text-muted">
                            Trage Ferien, Sperrzeiten oder Ausnahmen für Feiertage ein.
                        </div>
                    </div>
                    <button type="button" class="btn btn-warning btn-sm fw-semibold shadow-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#modalAddAbsence">
                        <i class="bi bi-plus-circle me-1"></i> Absenz erfassen
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-muted text-uppercase">
                            <tr>
                                <th class="ps-4">Datum</th>
                                <th>Zeitfenster</th>
                                <th>Typ</th>
                                <th>Standort / Hafen</th>
                                <th>Bemerkung</th>
                                <th class="pe-4 text-end">Aktion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($coachAvailabilities)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        Keine Absenzen oder Sperrzeiten für diesen Monat hinterlegt.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php
                                $availTypeLabels = [
                                    'vacation'         => ['Ferien / Urlaub', 'success'],
                                    'sick'             => ['Krankheit / Unfall', 'danger'],
                                    'military'         => ['Militär / Zivildienst', 'info'],
                                    'unavailable'      => ['Sperrzeit / Privat', 'warning'],
                                    'holiday_override' => ['Feiertags-Ausnahme (Verfügbar)', 'primary'],
                                    'available'        => ['Verfügbar', 'primary'],
                                    'other'            => ['Sonstige Absenz', 'secondary'],
                                ];
                                ?>
                                <?php foreach ($coachAvailabilities as $av): ?>
                                    <?php
                                    $aType = (string) ($av['type'] ?? 'unavailable');
                                    $aInfo = $availTypeLabels[$aType] ?? ['Absenz', 'secondary'];
                                    $locName = !empty($av['location_id']) ? ($locationsMap[(int) $av['location_id']] ?? ('Hafen #' . $av['location_id'])) : null;
                                    ?>
                                    <tr>
                                        <td class="ps-4 py-3 fw-bold text-dark">
                                            <?= date('d.m.Y', strtotime((string) $av['date'])) ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($av['start_time']) && !empty($av['end_time'])): ?>
                                                <span class="badge bg-light text-dark border">
                                                    <?= substr((string) $av['start_time'], 0, 5) ?> – <?= substr((string) $av['end_time'], 0, 5) ?> Uhr
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border">Ganztägig</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $aInfo[1] ?>-subtle text-<?= $aInfo[1] ?> rounded-pill">
                                                <?= htmlspecialchars($aInfo[0]) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($locName): ?>
                                                <span class="badge bg-light text-dark border">
                                                    <i class="bi bi-geo-alt-fill text-danger me-1"></i><?= htmlspecialchars($locName) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border">Alle Standorte</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-dark small">
                                            <?= htmlspecialchars((string) ($av['note'] ?? '-')) ?>
                                        </td>
                                        <td class="pe-4 text-end">
                                            <form method="post" action="?route=admin/boating/instructor-portal/delete-availability" onsubmit="return confirm('Eintrag wirklich löschen?');" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="id" value="<?= (int) $av['id'] ?>">
                                                <input type="hidden" name="instructor_id" value="<?= (int) $instructor['id'] ?>">
                                                <input type="hidden" name="month" value="<?= htmlspecialchars($selectedMonth, ENT_QUOTES, 'UTF-8') ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-2" title="Löschen">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab 4: Zeiterfassung & Schweizer Timesheet -->
        <div class="tab-pane fade" id="tab-timesheet" role="tabpanel">

            <!-- Timesheet Status Banner & Aktionen -->
            <?php if ($tsStatus === 'rejected'): ?>
                <div class="alert alert-danger rounded-4 shadow-sm mb-4 border-danger">
                    <div class="d-flex align-items-start gap-3">
                        <i class="bi bi-exclamation-octagon-fill text-danger fs-3"></i>
                        <div class="flex-grow-1">
                            <h6 class="fw-bold mb-1">Timesheet von der Buchhaltung zurückgewiesen</h6>
                            <p class="mb-2 small">
                                <strong>Begründung der Administration:</strong>
                                <span class="fst-italic text-dark bg-white px-2 py-1 rounded border d-inline-block mt-1">
                                    <?= htmlspecialchars((string) ($timesheetSummary['rejection_reason'] ?? 'Keine Begründung angegeben'), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </p>
                            <p class="mb-0 small text-muted">
                                Bitte passe deine erfassten Zeiten unten an und reiche das Timesheet erneut ein.
                            </p>
                        </div>
                    </div>
                </div>
            <?php elseif ($tsStatus === 'reopened'): ?>
                <div class="alert alert-warning rounded-4 shadow-sm mb-4 border-warning">
                    <div class="d-flex align-items-start gap-3">
                        <i class="bi bi-arrow-repeat text-warning fs-3"></i>
                        <div class="flex-grow-1">
                            <h6 class="fw-bold mb-1">Timesheet wiedereröffnet zur Überarbeitung</h6>
                            <p class="mb-0 small">
                                Die Administration hat diesen Monatsrapport wieder zur Bearbeitung freigegeben. Deine Einträge sind bearbeitbar. Bei erneuter Einreichung wird automatisch <strong>Version v<?= $pdfVersion + 1 ?></strong> erstellt.
                            </p>
                        </div>
                    </div>
                </div>
            <?php elseif ($tsStatus === 'submitted'): ?>
                <div class="alert alert-info rounded-4 shadow-sm mb-4 border-info">
                    <div class="d-flex align-items-center gap-3">
                        <i class="bi bi-hourglass-split text-info fs-3"></i>
                        <div>
                            <h6 class="fw-bold mb-0">Timesheet eingereicht (Version v<?= $pdfVersion ?>)</h6>
                            <div class="small">Wartet auf Prüfung und Freigabe durch die Buchhaltung / Administration.</div>
                        </div>
                    </div>
                </div>
            <?php elseif ($tsStatus === 'approved'): ?>
                <div class="alert alert-success rounded-4 shadow-sm mb-4 border-success">
                    <div class="d-flex align-items-center gap-3">
                        <i class="bi bi-check-circle-fill text-success fs-3"></i>
                        <div>
                            <h6 class="fw-bold mb-0">Timesheet genehmigt (Version v<?= $pdfVersion ?>)</h6>
                            <div class="small">Der Monatsrapport wurde erfolgreich durch die Administration freigegeben.</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Schweizer Lohnabrechnungs-Zusammenfassung (CH Payroll Card) -->
            <?php $payroll = $timesheetSummary['payroll'] ?? []; ?>
            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                <div class="card-body p-3 p-md-4">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 border-bottom pb-3">
                        <div>
                            <h6 class="fw-bold text-dark mb-1">
                                <i class="bi bi-receipt text-primary me-2"></i>Schweizer Lohnübersicht &amp; Abzüge (<?= htmlspecialchars($monthDisplay) ?>)
                            </h6>
                            <div class="small text-muted">
                                Version: <span class="badge bg-light text-dark border">v<?= $pdfVersion ?></span> |
                                Status: <span class="badge bg-secondary rounded-pill"><?= htmlspecialchars(ucfirst($tsStatus)) ?></span>
                            </div>
                        </div>

                        <?php if ($tsStatus !== 'approved' && $tsStatus !== 'submitted'): ?>
                            <form method="post" action="?route=admin/boating/instructor-portal/submit-timesheet" onsubmit="return confirm('Möchtest du das Timesheet für <?= htmlspecialchars($monthDisplay) ?> jetzt verbindlich einreichen?');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="instructor_id" value="<?= (int) $instructor['id'] ?>">
                                <input type="hidden" name="month" value="<?= htmlspecialchars($selectedMonth, ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="btn btn-primary fw-semibold rounded-pill px-4 shadow-sm">
                                    <i class="bi bi-send me-1"></i> Timesheet jetzt einreichen
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded-3 border">
                                <div class="small text-muted fw-semibold">Brutto-Honorar</div>
                                <div class="h4 fw-bold text-dark mb-1">CHF <?= number_format((float) ($payroll['gross_amount'] ?? $timesheetSummary['total_amount'] ?? 0), 2, '.', "'") ?></div>
                                <div class="small text-muted"><?= number_format((float) ($timesheetSummary['total_hours'] ?? 0), 2) ?> Stunden erfasst</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded-3 border">
                                <div class="small text-muted fw-semibold">Gesamtabzüge (CH Sozialversicherung)</div>
                                <div class="h4 fw-bold text-danger mb-1">- CHF <?= number_format((float) ($payroll['deductions_total'] ?? 0), 2, '.', "'") ?></div>
                                <div class="small text-muted">
                                    AHV (<?= number_format((float) ($payroll['ahv_rate'] ?? 5.3), 2) ?>%), ALV (<?= number_format((float) ($payroll['alv_rate'] ?? 1.1), 2) ?>%), NBU (<?= number_format((float) ($payroll['nbu_rate'] ?? 1.3), 2) ?>%)
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-success bg-opacity-10 border border-success rounded-3">
                                <div class="small text-success fw-semibold">Auszahlungsbetrag Netto</div>
                                <div class="h4 fw-bold text-success mb-1">CHF <?= number_format((float) ($payroll['net_amount'] ?? 0), 2, '.', "'") ?></div>
                                <div class="small text-muted">Zur Auszahlung an Bankkonto</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
                <div class="card-header bg-white p-3 p-md-4 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <h6 class="fw-bold text-dark mb-1">
                            <i class="bi bi-stopwatch text-info me-2"></i>Erfasste Arbeitszeiten (<?= htmlspecialchars($monthDisplay) ?>)
                        </h6>
                        <div class="small text-muted">
                            Fahrstunden, Prüfungstage, Bootswartung und Büroarbeiten.
                        </div>
                    </div>
                    <?php if ($tsStatus !== 'approved'): ?>
                        <button type="button" class="btn btn-primary btn-sm fw-semibold shadow-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#modalAddTimesheet">
                            <i class="bi bi-plus-circle me-1"></i> Arbeitszeit erfassen
                        </button>
                    <?php endif; ?>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-muted text-uppercase">
                            <tr>
                                <th class="ps-4">Datum</th>
                                <th>Kategorie</th>
                                <th>Zeit &amp; Pause</th>
                                <th>Stunden</th>
                                <th>Ansatz / Std.</th>
                                <th>Honorar (CHF)</th>
                                <th>Status</th>
                                <th class="pe-4">Bemerkung</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($coachWorkingHours)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        Keine separaten Zeiterfassungen für diesen Monat hinterlegt.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php
                                $catLabels = [
                                    'lesson'      => 'Fahrstunde',
                                    'exam'        => 'Führerprüfung',
                                    'admin'       => 'Administration',
                                    'maintenance' => 'Bootswartung / Bereitstellung',
                                    'other'       => 'Sonstiges',
                                ];
                                $tsStatusBadges = [
                                    'draft'     => 'secondary',
                                    'submitted' => 'warning',
                                    'approved'  => 'success',
                                    'exported'  => 'info',
                                    'rejected'  => 'danger',
                                    'reopened'  => 'warning',
                                ];
                                ?>
                                <?php foreach ($coachWorkingHours as $wh): ?>
                                    <?php
                                    $cat = (string) ($wh['category'] ?? 'lesson');
                                    $st  = (string) ($wh['status'] ?? 'draft');
                                    $stBadge = $tsStatusBadges[$st] ?? 'secondary';
                                    ?>
                                    <tr>
                                        <td class="ps-4 py-3 fw-bold text-dark">
                                            <?= date('d.m.Y', strtotime((string) $wh['date'])) ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                <?= htmlspecialchars($catLabels[$cat] ?? $cat) ?>
                                            </span>
                                        </td>
                                        <td class="small text-muted">
                                            <?= substr((string) $wh['start_time'], 0, 5) ?> – <?= substr((string) $wh['end_time'], 0, 5) ?>
                                            <?php if (!empty($wh['break_minutes'])): ?>
                                                <small class="text-muted">(<?= (int) $wh['break_minutes'] ?> Min Pause)</small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="fw-bold text-dark">
                                            <?= number_format((float) $wh['total_hours'], 2) ?> h
                                        </td>
                                        <td class="small text-muted">
                                            CHF <?= number_format((float) $wh['rate_amount'], 2) ?>
                                        </td>
                                        <td class="fw-bold text-success">
                                            CHF <?= number_format((float) $wh['total_amount'], 2, '.', "'") ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $stBadge ?>-subtle text-<?= $stBadge ?> rounded-pill small">
                                                <?= htmlspecialchars(ucfirst($st)) ?>
                                            </span>
                                        </td>
                                        <td class="pe-4 small text-muted">
                                            <?= htmlspecialchars((string) ($wh['note'] ?? '-')) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab 5: Wochen-Arbeitsplan & Verfügbarkeits-Templates -->
        <div class="tab-pane fade" id="tab-templates" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                <div class="card-header bg-white p-3 p-md-4 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <h6 class="fw-bold text-dark mb-1">
                            <i class="bi bi-calendar-week text-success me-2"></i>Wochen-Arbeitsplan &amp; Buchungszeitfenster
                        </h6>
                        <div class="small text-muted">
                            Definiere deine regulären Arbeitszeiten für jeden Wochentag. Das Buchungsportal bietet nur Termine innerhalb dieser Fenster an.
                        </div>
                    </div>
                    <button type="button" class="btn btn-success btn-sm fw-semibold shadow-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#modalAddTemplate">
                        <i class="bi bi-plus-circle me-1"></i> Zeitfenster hinzufügen
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-muted text-uppercase">
                            <tr>
                                <th class="ps-4">Wochentag</th>
                                <th>Arbeitszeit</th>
                                <th>Standort / Hafen</th>
                                <th>Status</th>
                                <th class="pe-4 text-end">Aktion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($weeklyTemplates)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        Noch keine regulären Wochenzeitfenster hinterlegt (Standardzeiten 08:00 – 19:00 Mo–Sa aktiv).
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php
                                $dayNames = [
                                    1 => 'Montag',
                                    2 => 'Dienstag',
                                    3 => 'Mittwoch',
                                    4 => 'Donnerstag',
                                    5 => 'Freitag',
                                    6 => 'Samstag',
                                    7 => 'Sonntag',
                                ];
                                ?>
                                <?php foreach ($weeklyTemplates as $tpl): ?>
                                    <?php
                                    $dayNum = (int) ($tpl['weekday'] ?? $tpl['day_of_week'] ?? 1);
                                    $locName = !empty($tpl['location_id']) ? ($locationsMap[(int) $tpl['location_id']] ?? ('Hafen #' . $tpl['location_id'])) : null;
                                    ?>
                                    <tr>
                                        <td class="ps-4 py-3 fw-bold text-dark">
                                            <?= $dayNames[$dayNum] ?? ('Tag ' . $dayNum) ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                <?= substr((string) $tpl['start_time'], 0, 5) ?> – <?= substr((string) $tpl['end_time'], 0, 5) ?> Uhr
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($locName): ?>
                                                <span class="badge bg-light text-dark border">
                                                    <i class="bi bi-geo-alt-fill text-danger me-1"></i><?= htmlspecialchars($locName) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border">Alle Standorte</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($tpl['is_active'])): ?>
                                                <span class="badge bg-success-subtle text-success rounded-pill">Aktiv buchbar</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-muted rounded-pill">Pausiert</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="pe-4 text-end">
                                            <form method="post" action="?route=admin/boating/instructor-portal/weekly-templates/delete" onsubmit="return confirm('Wochenfenster wirklich löschen?');" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="id" value="<?= (int) $tpl['id'] ?>">
                                                <input type="hidden" name="instructor_id" value="<?= (int) $instructor['id'] ?>">
                                                <input type="hidden" name="month" value="<?= htmlspecialchars($selectedMonth, ENT_QUOTES, 'UTF-8') ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-2" title="Löschen">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Absenz erfassen (mit location_id und holiday_override) -->
    <div class="modal fade" id="modalAddAbsence" tabindex="-1" aria-labelledby="modalAddAbsenceLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content rounded-4 border-0 shadow">
                <form method="post" action="?route=admin/boating/instructor-portal/add-availability">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="instructor_id" value="<?= (int) $instructor['id'] ?>">
                    <input type="hidden" name="month" value="<?= htmlspecialchars($selectedMonth, ENT_QUOTES, 'UTF-8') ?>">

                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold" id="modalAddAbsenceLabel">
                            <i class="bi bi-calendar-x text-warning me-2"></i>Absenz / Sperrzeit erfassen
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Datum <span class="text-danger">*</span></label>
                            <input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Art der Absenz <span class="text-danger">*</span></label>
                            <select name="type" class="form-select" required>
                                <option value="vacation">Ferien / Urlaub</option>
                                <option value="sick">Krankheit / Unfall</option>
                                <option value="military">Militär / Zivildienst</option>
                                <option value="unavailable" selected>Sperrzeit / Nicht verfügbar</option>
                                <option value="holiday_override">Feiertags-Ausnahme (Trotz Feiertag buchbar)</option>
                                <option value="other">Sonstiges</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Geltungsbereich / Hafen</label>
                            <select name="location_id" class="form-select">
                                <option value="">Alle Standorte / Häfen (Standard)</option>
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?= (int) $loc['id'] ?>">
                                        <?= htmlspecialchars((string) $loc['name']) ?> (<?= htmlspecialchars((string) ($loc['code'] ?? '')) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Gilt die Sperrzeit für alle Häfen oder nur einen bestimmten Standort?</small>
                        </div>
                        <div class="p-3 bg-light rounded-3 border mb-3">
                            <label class="form-label small fw-semibold text-dark mb-2">
                                <i class="bi bi-clock-history me-1 text-warning"></i>Sperrzeit-Fenster (stundenweise, von – bis) <span class="text-danger">*</span>
                            </label>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label small text-muted mb-1" style="font-size: 11px;">Von Uhrzeit (Startzeit) <span class="text-danger">*</span></label>
                                    <input type="time" name="start_time" id="absStartTime" class="form-control" value="08:00" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small text-muted mb-1" style="font-size: 11px;">Bis Uhrzeit (Endzeit) <span class="text-danger">*</span></label>
                                    <input type="time" name="end_time" id="absEndTime" class="form-control" value="12:00" required>
                                </div>
                            </div>
                            <!-- Schnellauswahl Buttons -->
                            <div class="d-flex flex-wrap gap-1 mt-1">
                                <span class="text-muted small align-self-center" style="font-size: 10px;">Schnellwahl:</span>
                                <button type="button" class="btn btn-sm btn-white bg-white border py-0 px-1 text-muted" style="font-size: 10px;" onclick="document.getElementById('absStartTime').value='08:00';document.getElementById('absEndTime').value='12:00';">Vormittag (08–12)</button>
                                <button type="button" class="btn btn-sm btn-white bg-white border py-0 px-1 text-muted" style="font-size: 10px;" onclick="document.getElementById('absStartTime').value='13:00';document.getElementById('absEndTime').value='17:00';">Nachmittag (13–17)</button>
                                <button type="button" class="btn btn-sm btn-white bg-white border py-0 px-1 text-muted" style="font-size: 10px;" onclick="document.getElementById('absStartTime').value='17:00';document.getElementById('absEndTime').value='20:00';">Abend (17–20)</button>
                                <button type="button" class="btn btn-sm btn-white bg-white border py-0 px-1 text-muted" style="font-size: 10px;" onclick="document.getElementById('absStartTime').value='08:00';document.getElementById('absEndTime').value='18:00';">Ganzer Tag (08–18)</button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Bemerkung / Grund</label>
                            <input type="text" name="note" class="form-control" placeholder="z. B. Arzttermin oder Sommerurlaub">
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                        <button type="submit" class="btn btn-sm btn-warning fw-semibold px-4">Absenz speichern</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Wochen-Template Zeitfenster hinzufügen -->
    <div class="modal fade" id="modalAddTemplate" tabindex="-1" aria-labelledby="modalAddTemplateLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content rounded-4 border-0 shadow">
                <form method="post" action="?route=admin/boating/instructor-portal/weekly-templates/save">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="instructor_id" value="<?= (int) $instructor['id'] ?>">
                    <input type="hidden" name="month" value="<?= htmlspecialchars($selectedMonth, ENT_QUOTES, 'UTF-8') ?>">

                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold" id="modalAddTemplateLabel">
                            <i class="bi bi-calendar-week text-success me-2"></i>Wochen-Arbeitsfenster anlegen
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Wochentag <span class="text-danger">*</span></label>
                            <select name="weekday" id="day_of_week" class="form-select" required>
                                <option value="1">Montag</option>
                                <option value="2">Dienstag</option>
                                <option value="3">Mittwoch</option>
                                <option value="4">Donnerstag</option>
                                <option value="5">Freitag</option>
                                <option value="6">Samstag</option>
                                <option value="7">Sonntag</option>
                            </select>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Startzeit <span class="text-danger">*</span></label>
                                <input type="time" name="start_time" class="form-control" value="08:00" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Endzeit <span class="text-danger">*</span></label>
                                <input type="time" name="end_time" class="form-control" value="18:00" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Hafen / Standort</label>
                            <select name="location_id" class="form-select">
                                <option value="">Alle Häfen (Standortunabhängig)</option>
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?= (int) $loc['id'] ?>">
                                        <?= htmlspecialchars((string) $loc['name']) ?> (<?= htmlspecialchars((string) ($loc['code'] ?? '')) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="is_active" id="tplActiveSwitch" value="1" checked>
                            <label class="form-check-label small" for="tplActiveSwitch">Aktiv für Kundenbuchungen</label>
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                        <button type="submit" class="btn btn-sm btn-success fw-semibold px-4">Zeitfenster speichern</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Arbeitszeit erfassen -->
    <div class="modal fade" id="modalAddTimesheet" tabindex="-1" aria-labelledby="modalAddTimesheetLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content rounded-4 border-0 shadow">
                <form method="post" action="?route=admin/boating/instructor-portal/add-timesheet">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="instructor_id" value="<?= (int) $instructor['id'] ?>">
                    <input type="hidden" name="month" value="<?= htmlspecialchars($selectedMonth, ENT_QUOTES, 'UTF-8') ?>">

                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold" id="modalAddTimesheetLabel">
                            <i class="bi bi-stopwatch text-info me-2"></i>Arbeitszeit / Timesheet erfassen
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                    </div>
                    <div class="modal-body p-4">
                        <!-- Betreff / Tätigkeit -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Betreff / Tätigkeit <span class="text-danger">*</span></label>
                            <input type="text" name="subject" id="portalTimesheetSubject" class="form-control" placeholder="z. B. Bootswartung Boesch 620, Vorbereitung Prüfung, Theorieunterricht..." required>
                            <div class="d-flex flex-wrap gap-1 mt-1">
                                <span class="text-muted small align-self-center" style="font-size: 11px;">Vorschläge:</span>
                                <button type="button" class="btn btn-sm btn-light border py-0 px-1 text-muted" style="font-size: 10px;" onclick="document.getElementById('portalTimesheetSubject').value='Bootswartung & Tanken';">+ Wartung & Tanken</button>
                                <button type="button" class="btn btn-sm btn-light border py-0 px-1 text-muted" style="font-size: 10px;" onclick="document.getElementById('portalTimesheetSubject').value='Führerprüfung Begleitung';">+ Prüfung</button>
                                <button type="button" class="btn btn-sm btn-light border py-0 px-1 text-muted" style="font-size: 10px;" onclick="document.getElementById('portalTimesheetSubject').value='Theorieunterricht Kat. A';">+ Theorie</button>
                                <button type="button" class="btn btn-sm btn-light border py-0 px-1 text-muted" style="font-size: 10px;" onclick="document.getElementById('portalTimesheetSubject').value='Administration & Planung';">+ Admin</button>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Datum <span class="text-danger">*</span></label>
                                <input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Kategorie <span class="text-danger">*</span></label>
                                <select name="category" class="form-select" required>
                                    <option value="maintenance">Bootswartung &amp; Tanken</option>
                                    <option value="lesson">Fahrstunde (manuell)</option>
                                    <option value="exam">Führerprüfung</option>
                                    <option value="admin">Administration / Planung</option>
                                    <option value="other">Sonstiges</option>
                                </select>
                            </div>
                        </div>

                        <!-- Arbeitsdauer frei wählbar: h & min -->
                        <div class="p-3 bg-light rounded-3 border mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label small fw-semibold mb-0">
                                    <i class="bi bi-clock me-1 text-info"></i>Dauer frei wählen (h &amp; min):
                                </label>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-secondary py-0 px-1" style="font-size: 10px;" onclick="document.getElementById('tsHours').value=0; document.getElementById('tsMinutes').value=30; recalcTs();">30m</button>
                                    <button type="button" class="btn btn-outline-secondary py-0 px-1" style="font-size: 10px;" onclick="document.getElementById('tsHours').value=1; document.getElementById('tsMinutes').value=0; recalcTs();">1h</button>
                                    <button type="button" class="btn btn-outline-secondary py-0 px-1" style="font-size: 10px;" onclick="document.getElementById('tsHours').value=1; document.getElementById('tsMinutes').value=30; recalcTs();">1.5h</button>
                                    <button type="button" class="btn btn-outline-secondary py-0 px-1" style="font-size: 10px;" onclick="document.getElementById('tsHours').value=2; document.getElementById('tsMinutes').value=0; recalcTs();">2h</button>
                                    <button type="button" class="btn btn-outline-secondary py-0 px-1" style="font-size: 10px;" onclick="document.getElementById('tsHours').value=3; document.getElementById('tsMinutes').value=0; recalcTs();">3h</button>
                                    <button type="button" class="btn btn-outline-secondary py-0 px-1" style="font-size: 10px;" onclick="document.getElementById('tsHours').value=4; document.getElementById('tsMinutes').value=0; recalcTs();">4h</button>
                                </div>
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="small text-muted mb-1">Stunden (h)</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" id="tsHours" name="hours" min="0" max="24" step="1" class="form-control fw-bold" value="3" oninput="recalcTs()">
                                        <span class="input-group-text">h</span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <label class="small text-muted mb-1">Minuten (min)</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" id="tsMinutes" name="minutes" min="0" max="59" step="5" class="form-control fw-bold" value="0" oninput="recalcTs()">
                                        <span class="input-group-text">min</span>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-2 pt-2 border-top">
                                <div class="col-4">
                                    <label class="form-label small text-muted mb-0" style="font-size: 11px;">Startzeit</label>
                                    <input type="time" id="tsStart" name="start_time" class="form-control form-control-sm" value="09:00" oninput="recalcTs()" required>
                                </div>
                                <div class="col-4">
                                    <label class="form-label small text-muted mb-0" style="font-size: 11px;">Pause (Min)</label>
                                    <input type="number" id="tsBreak" min="0" step="5" name="break_minutes" class="form-control form-control-sm" value="0" oninput="recalcTs()">
                                </div>
                                <div class="col-4">
                                    <label class="form-label small text-muted mb-0" style="font-size: 11px;">Endzeit</label>
                                    <input type="time" id="tsEnd" name="end_time" class="form-control form-control-sm bg-light" value="12:00" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold d-flex align-items-center justify-content-between">
                                <span><i class="bi bi-lock-fill text-muted me-1"></i>Stundensatz (vom Admin hinterlegt)</span>
                                <span class="badge bg-secondary-subtle text-secondary small fw-normal">Fest hinterlegt</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted">CHF</span>
                                <input type="text" class="form-control bg-light text-dark fw-bold" value="<?= htmlspecialchars((string) (isset($instructor['pay_rate_amount']) ? number_format((float) $instructor['pay_rate_amount'], 2, '.', '') : '65.00')) ?>" readonly>
                                <span class="input-group-text bg-light text-muted">/ Std.</span>
                            </div>
                            <input type="hidden" name="rate_amount" value="<?= htmlspecialchars((string) (isset($instructor['pay_rate_amount']) ? number_format((float) $instructor['pay_rate_amount'], 2, '.', '') : '65.00')) ?>">
                            <small class="text-muted d-block mt-1">Dieser Lohnansatz ist vertraglich vom Administrator hinterlegt und kann vom Fahrlehrer nicht abgeändert werden.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Zusätzliche Notiz (optional)</label>
                            <textarea name="note" rows="2" class="form-control" placeholder="Ergänzende Anmerkungen zur Arbeitszeit..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                        <button type="submit" class="btn btn-sm btn-primary fw-semibold px-4">Zeit erfassen</button>
                    </div>

                    <script>
                    function recalcTs() {
                        const h = parseInt(document.getElementById('tsHours').value, 10) || 0;
                        const m = parseInt(document.getElementById('tsMinutes').value, 10) || 0;
                        const br = parseInt(document.getElementById('tsBreak').value, 10) || 0;
                        const start = document.getElementById('tsStart').value || '09:00';
                        const parts = start.split(':');
                        const sH = parseInt(parts[0], 10) || 0;
                        const sM = parseInt(parts[1], 10) || 0;
                        const totalMin = sH * 60 + sM + (h * 60 + m);
                        const endH = Math.floor(totalMin / 60) % 24;
                        const endM = totalMin % 60;
                        document.getElementById('tsEnd').value = String(endH).padStart(2, '0') + ':' + String(endM).padStart(2, '0');
                    }
                    </script>
                </form>
            </div>
        </div>
    </div>

<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../../../views/layouts/admin.php';
