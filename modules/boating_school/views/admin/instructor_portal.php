<?php

declare(strict_types=1);

/**
 * Coach Dashboard & Fahrlehrer-Cockpit (/admin/boating/instructor-portal)
 *
 * Eigenständiges Coach-Portal im Nautic-Design mit:
 * - Übersicht «Heute auf dem See», Live-Seewetter (Luzern & Hergiswil), Sturmwarnung & nächstem Einsatz
 * - Fahrten & Einsätze inkl. 1-Klick «Lektion abschliessen» & Schüler-Kontakt
 * - Absenzen, Sperrzeiten & Wochen-Arbeitsplan (bs_coach_weekly_templates)
 * - CH-Zeiterfassung, Timesheets & Schweizer Lohn-Vorschau (AHV/ALV/NBU)
 * - Bootsmängel / Vorfall / Fehler melden Modal ans Backoffice
 * - Admin-Rücklink & Coach-Wechsler (für Administratoren)
 *
 * @var array<string, mixed>|null $instructor
 * @var array<int, array<string, mixed>> $allInstructors
 * @var bool $canSwitchInstructor
 * @var string $selectedMonth Format 'YYYY-MM'
 * @var array<string, mixed> $monthStats
 * @var array<int, array<string, mixed>> $monthAppointments
 * @var array<int, array<string, mixed>> $upcomingAppointments
 * @var array<int, array<string, mixed>> $todayAppointments
 * @var array<int, array<string, mixed>> $coachAvailabilities
 * @var array<int, array<string, mixed>> $coachWorkingHours
 * @var array<string, mixed> $timesheetSummary
 * @var array<int, array<string, mixed>> $weeklyTemplates
 * @var array<int, array<string, mixed>> $locations
 * @var array<int, array<string, mixed>> $boats
 * @var string $csrfToken
 */

$title = 'Coach Dashboard - Coaching Boot Nautic';
$currentRoute = 'admin/boating/instructor-portal';
$useSidebarLayout = true;

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError   = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$statusBadges = AppointmentService::STATUS_BADGES ?? [
    'requested'          => 'warning',
    'confirmed'          => 'success',
    'cancelled_customer' => 'danger',
    'cancelled_system'   => 'danger',
    'completed'          => 'primary',
];

$statusLabels = AppointmentService::STATUS_LABELS ?? [
    'requested'          => 'Angefragt',
    'confirmed'          => 'Bestätigt',
    'cancelled_customer' => 'Storniert (Kunde)',
    'cancelled_system'   => 'Storniert (Schule)',
    'completed'          => 'Abgeschlossen',
];

$payoutBadges = [
    'open'     => 'warning',
    'exported' => 'info',
    'paid'     => 'success',
];

$payoutLabels = [
    'open'     => 'Offen (Bereit)',
    'exported' => 'Übergeben',
    'paid'     => 'Ausbezahlt',
];

$locationsMap = [];
if (!empty($locations)) {
    foreach ($locations as $l) {
        $locationsMap[(int) $l['id']] = $l['name'];
    }
}

$todayAppointments = $todayAppointments ?? [];
$boats = $boats ?? [];

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
$payroll = $timesheetSummary['payroll'] ?? [];

$openCount = (int) ($monthStats['open_count'] ?? 0);
$totalMonthApps = (int) ($monthStats['total_appointments'] ?? 0);
$nextAppointment = !empty($upcomingAppointments[0]) ? $upcomingAppointments[0] : null;

$coachFirstName = (string) ($instructor['first_name'] ?? 'Coach');
$coachLastName  = (string) ($instructor['last_name'] ?? '');
$coachFullName  = trim($coachFirstName . ' ' . $coachLastName);
$coachInitials  = mb_substr($coachFirstName, 0, 1) . mb_substr($coachLastName, 0, 1);

ob_start();
?>

<!-- Flash Meldungen -->
<?php if ($flashSuccess): ?>
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 1080;">
        <div class="alert alert-success alert-dismissible fade show shadow rounded-4 d-flex align-items-center" role="alert">
            <i class="bi bi-check-circle-fill text-success fs-5 me-2.5"></i>
            <div><?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?></div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Schließen"></button>
        </div>
    </div>
<?php endif; ?>

<?php if ($flashError): ?>
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 1080;">
        <div class="alert alert-danger alert-dismissible fade show shadow rounded-4 d-flex align-items-center" role="alert">
            <i class="bi bi-exclamation-triangle-fill text-danger fs-5 me-2.5"></i>
            <div><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Schließen"></button>
        </div>
    </div>
<?php endif; ?>

<?php if (!$instructor): ?>
    <div class="min-vh-100 d-flex align-items-center justify-content-center p-4" style="background-color: #ede5dc;">
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white" style="max-width: 520px; background-color: #f6f0e7 !important;">
            <div class="rounded-4 d-inline-flex align-items-center justify-content-center mx-auto mb-3 text-white" style="width: 64px; height: 64px; background-color: #2d2421;">
                <i class="bi bi-person-x fs-2 text-warning"></i>
            </div>
            <h4 class="fw-bold mb-2 text-dark">Kein Coach-Profil zugeordnet</h4>
            <p class="text-muted small mb-4">
                Dein Benutzerkonto ist aktuell noch keinem Fahrlehrer-Datensatz im Modul zugewiesen. Bitte wähle oben einen Coach aus oder kehre ins Admin Dashboard zurück.
            </p>
            <?php if (!empty($allInstructors)): ?>
                <form method="get" action="" class="mb-3">
                    <input type="hidden" name="route" value="admin/boating/instructor-portal">
                    <label class="form-label small fw-semibold text-muted">Coach zur Ansicht wählen:</label>
                    <select name="instructor_id" class="form-select mb-3 bg-white" onchange="this.form.submit()">
                        <option value="">-- Coach wählen --</option>
                        <?php foreach ($allInstructors as $inst): ?>
                            <option value="<?= (int) $inst['id'] ?>">
                                <?= htmlspecialchars((string) ($inst['first_name'] . ' ' . $inst['last_name']), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            <?php endif; ?>
            <a href="?route=admin/boating" class="btn btn-dark rounded-3 fw-bold px-4">
                <i class="bi bi-arrow-left me-1"></i> Zurück zum Admin Dashboard
            </a>
        </div>
    </div>
<?php else: ?>

    <div class="d-flex flex-column flex-lg-row" style="min-height: 100vh; background-color: #ede5dc;">

        <!-- ============================================================== -->
        <!-- LINKE COACH-SIDEBAR (WARM ESPRESSO #2d2421)                    -->
        <!-- ============================================================== -->
        <aside class="d-flex flex-column justify-content-between p-3 p-lg-4 shrink-0 text-white" style="width: 100%; max-width: 280px; background-color: #2d2421; border-right: 1px solid #3f3531;">
            <div>
                <!-- Coaching Boot SVG Logo -->
                <div class="text-center mb-4 mt-2">
                    <a href="?route=admin/boating/instructor-portal" class="d-inline-flex align-items-center justify-content-center text-decoration-none" title="Coach Dashboard">
                        <svg viewBox="0 0 400 400" style="width: 78px; height: 78px; border-radius: 16px; box-shadow: 0 6px 18px rgba(0, 0, 0, 0.4); border: 1px solid rgba(255, 255, 255, 0.15);" role="img" aria-label="Coaching Boot Logo">
                            <rect fill="#5a3e32" x="16.4" y="16.4" width="367.21" height="367.21" rx="48" ry="48" />
                            <g fill="#ffffff">
                                <path d="M195.14,45.63l10.6,29.8h.12v-29.8h8.04v43.52h-10l-10.73-30.47h-.12v30.47h-8.04v-43.52h10.12Z" />
                                <path d="M110.23,261.41c-14.35-18.84-22.88-42.33-22.88-67.79,0-47.27,29.4-87.79,70.87-104.25v-19.75c-51.91,17.31-89.44,66.35-89.44,124.01,0,32.87,12.2,62.94,32.31,85.93l9.13-18.14Z" />
                                <path d="M311.58,193.62c0,25.42-8.51,48.88-22.82,67.71l9.05,18.25c20.12-22.99,32.34-53.08,32.34-85.96,0-57.65-37.53-106.7-89.44-124.01v19.75c41.47,16.46,70.87,56.99,70.87,104.25Z" />
                                <path d="M341.94,219.56l25.77-25.77-25.64-25.64c1.55,8.32,2.35,16.83,2.35,25.46s-.85,17.45-2.48,25.95Z" />
                                <path d="M199.46,331.44c-8.29,0-16.51-.74-24.6-2.21l25.13,25.13,25.38-25.38c-8.51,1.63-17.18,2.45-25.92,2.45Z" />
                                <path d="M54.5,193.62c0-8.17.71-16.24,2.11-24.14l-24.32,24.32,24.44,24.44c-1.47-8.07-2.22-16.31-2.22-24.61Z" />
                                <path d="M194.34,178.42c-.27.02.86,18.07-.9,46.69-.54,7.15-1.42,14.98-3.2,23.28-2.05,8.07-4.24,17.61-12,24.5-8.91,7.13-20.02,1.62-27-4.1-7.63-6.07-13.37-14.38-18.4-23.07.04-.04,2.47,3.73,7.43,9.83,5.01,5.63,12.97,15.13,26,17.9,6.6,1.37,13.16-3.64,16.33-11.2,3.41-7.39,5.33-16.08,6.72-25.14,2.8-18.19,3.46-38.19,5-58.7Z" />
                                <path d="M207.87,177.78c.27-.05.66,18.14,2.96,46.28.67,7.01,1.63,14.63,3.34,22.53,1.89,7.63,4.19,16.57,10.58,21.78,6.73,5.3,16.8.32,24.13-5.72,7.69-6.17,14-14.54,19.83-23.28-.05-.03-2.13,4.16-6.78,11.09-4.83,6.47-12.09,17.35-26.48,22.31-7.65,2.73-16.28-2.97-19.37-10.73-3.65-7.59-5.45-16.35-6.63-25.46-2.34-18.3-1.93-38.33-1.57-58.81Z" />
                                <path d="M161.89,177.52c.08,0-.35-1.21.25-3.49.36-1.99,3.28-6.28,8.14-4.87,4.47,1.43,6.63,8.25,3.1,13.31-1.57,2.58-5.43,4.08-8.18,2.52-2.73-1.48-3.73-4.7-3.31-7.47.06-.03.1,1.31,1.11,3.07.86,1.74,3.63,3.73,6.28,2.14,2.55-1.41,3.96-6.26,1.76-9.5-1.84-3.88-8.45-1.81-9.15,4.29Z" />
                                <path d="M230.95,174.3c.08,0-.35-1.21.25-3.49.36-1.99,3.28-6.28,8.14-4.87,4.47,1.43,6.63,8.25,3.1,13.31-1.57,2.57-5.43,4.08-8.18,2.52-2.73-1.48-3.73-4.7-3.31-7.47.06-.03.1,1.31,1.11,3.07.86,1.74,3.63,3.73,6.28,2.14,2.55-1.41,3.96-6.26,1.76-9.5-1.85-3.89-8.45-1.81-9.15,4.29Z" />
                                <path d="M180.98,147.75c.25.07-.68-3.92,2.26-10.3,2.41-6.2,12.18-14.6,24.38-11.55,5.81,1.46,11.56,6.08,13.7,13.1,2.41,6.84.89,15.69-4.74,21.93-5.43,6.27-14.8,9.25-22.66,6.38-8-2.54-13.39-10.92-12.95-19.56.23-.11-.24,4.09,3.04,9.66,2.8,5.55,12.28,11.46,22.87,7.17,10.67-3.27,17.73-21.04,7.54-31.49-4.66-4.96-12.6-7.07-19.87-4.2-7.3,2.64-13.05,10.28-13.59,18.85Z" />
                                <path d="M189.8,146.74c.15.04-.49-2.17,1.08-5.85,1.26-3.56,6.8-8.6,13.99-6.91,7.19.94,12.57,12.75,5.29,20.55-3.22,3.67-8.75,5.34-13.27,3.57-4.61-1.57-7.54-6.52-7.09-11.35.13-.07-.02,2.3,1.88,5.29,1.65,3,6.87,5.96,12.42,3.59,5.59-1.85,9.19-11.08,3.93-16.59-4.58-6.04-17.37-2.71-18.24,7.72Z" />
                                <path d="M201.37,183.5s3.1,57.7-.63,123.97c0,0-3.1-58.45.63-123.97Z" />
                                <path d="M128.13,248.89c.27-.26,1.97,12.74,13.6,27.33,5.77,7.14,14.24,14.43,25.48,18.29,5.55,1.97,11.98,2.9,18.22,5.21,6.14,2.15,12.65,6.29,14.69,13.2-.13.13-.95-3.17-5.12-6.48-4.08-3.44-11.51-5.51-20.8-7.19-19.28-1.84-43.01-21.85-46.08-50.36Z" />
                                <path d="M273.98,242.08c-.3-.28-.32,13.2-11.87,30.11-5.71,8.22-14.66,17-26.72,21.88-11.36,4.91-27.14,4.97-34.09,18.79.14.12.52-3.36,4.55-7.65,3.94-4.41,11.67-7.98,20.87-10.93,18.45-4.68,40.3-24.22,47.25-52.2Z" />
                                <path d="M174.24,173.49c-.16.3,13.05-8.02,35.7-7.4,5.59.13,11.63,1.04,17.87,2.71l2.35.65,1.19.33.07.02h.04c-.2-.29,1.89,2.68,1.01,1.44v.16s0,.32,0,.32v.63s0,1.28,0,1.28v.32s0,.16,0,.16v.08c-.5.7,1.11-1.51-1.12,1.58h-.04s-.65.21-.65.21l-2.54.69c-3.4.86-6.91,1.53-10.51,2.08-7.2,1.03-14.76,1.55-22.48,1.21-3.85-.17-7.75-.49-11.64-1.06-1.94-.31-3.9-.55-5.83-.99-1.55-.63-4.91-.02-4.29-2.79.22-.33-.31,1.61,1.02,1.82,1.67.13,4.09.52,7.23.68,6.26.45,15.32.7,26.3-.47,7.92-1.32,35.71-3.97,10.94-7.3-13.64-2.45-29.41-2-44.62,3.63Z" />
                                <path d="M145.39,242.27l-.37-.27-1.86-1.38-3.69-2.69c-4.88-3.54-9.58-6.88-14.04-9.99-2.23-1.55-4.4-3.05-6.5-4.48l-1.56-1.06-.26-.18c.08-.05.07-.05,0,0l-.35-.24c-.89.56-1.78,1.11-2.68,1.65l.47,3.36c2.7,19.14,5.01,31.33,5.01,31.33-.41-7.03-.9-13.94-1.46-20.64-.28-3.35-.58-6.65-.88-9.89l-.2-2.09.81.59c4.89,3.55,9.6,6.89,14.05,9.99,2.23,1.55,4.39,3.04,6.48,4.47l2.84,1.93c-8.23,1.36-14.49,6.01-17.04,10.39-2.86,4.55-3.06,7.75-3.12,8.14,1.86-6.87,7.22-12.16,13.2-14.31,3.01-1.09,6.11-1.78,9.2-1.87l1.08-.08c.26-.91.54-1.81.88-2.68Z" />
                                <path d="M281.99,251.81h0c.86-.19,1.72-.4,2.58-.6l.06-.73.21-2.41.39-4.75c.49-6.27.9-12.28,1.24-17.94.17-2.83.32-5.57.46-8.22l.1-1.97.02-.33c.08.04.08.03,0,0v-.26c-.85-.53-1.71-1.07-2.55-1.64l-2.64,2.46c-14.41,13.48-23.3,22.57-23.3,22.57,5.59-4.45,11.02-8.9,16.26-13.28,2.62-2.19,5.19-4.37,7.7-6.52l1.62-1.39-.08,1.04c-.49,6.29-.9,12.3-1.24,17.95-.17,2.83-.32,5.57-.46,8.2l-.18,3.57c-5.21-6.31-12.14-9.02-17.01-8.66-5.17.18-7.9,1.87-8.25,2.04,6.58-2.4,13.59-.84,18.34,3.07,2.39,1.96,4.5,4.24,6.11,6.85l.64.95Z" />
                                <path d="M136.89,260.82c.21-.24,3.53,7.92,13.54,15.6,4.93,4.03,11.4,7.24,19.41,9.47,7.65,2.68,17.48,4.14,24.19,11.06.36-.62-6.94-4.46-19.66-6.94-6.21-1.48-14.08-3.06-21.2-8.03-6.96-4.76-13.29-12.14-16.28-21.17Z" />
                                <path d="M205.19,299.44c-.16.06,5.33-6.18,16.75-9.96,10.99-3.78,25.96-9.7,36.92-23.84-.36-.36-3.11,7.48-12.82,15.28-4.78,3.86-11.29,7.21-18.6,9.58-7.2,2.4-15.27,3.79-22.25,8.93Z" />
                            </g>
                        </svg>
                    </a>
                    <div class="text-center mt-2">
                        <div class="fw-bold text-uppercase small text-warning" style="letter-spacing: 1px; font-size: 0.72rem;">COACHING BOOT</div>
                        <div class="text-white-50" style="font-size: 0.65rem;">Vierwaldstättersee &bull; Coach-Cockpit</div>
                    </div>
                </div>

                <!-- Coach Navigation -->
                <nav class="d-flex flex-column gap-1.5 small font-medium">
                    <a href="javascript:void(0)" onclick="switchCoachSection('overview')" class="sidebar-nav-link active" id="coachNavOverview">
                        <span class="d-flex align-items-center gap-2.5">
                            <i class="bi bi-compass-fill nav-icon" style="color: #fbbf24 !important;"></i>
                            <span>Dashboard (Heute)</span>
                        </span>
                    </a>

                    <a href="javascript:void(0)" onclick="switchCoachSection('lessons')" class="sidebar-nav-link" id="coachNavLessons">
                        <span class="d-flex align-items-center gap-2.5">
                            <i class="bi bi-calendar3 nav-icon" style="color: #38bdf8 !important;"></i>
                            <span>Fahrten &amp; Einsätze</span>
                        </span>
                        <?php if (count($upcomingAppointments) > 0): ?>
                            <span class="badge rounded-pill fw-bold" style="background-color: #0c4a6e; color: #7dd3fc; font-size: 0.65rem; padding: 2px 7px;"><?= count($upcomingAppointments) ?></span>
                        <?php endif; ?>
                    </a>

                    <a href="javascript:void(0)" onclick="switchCoachSection('availability')" class="sidebar-nav-link" id="coachNavAvailability">
                        <span class="d-flex align-items-center gap-2.5">
                            <i class="bi bi-calendar-week nav-icon" style="color: #34d399 !important;"></i>
                            <span>Absenzen &amp; Wochenplan</span>
                        </span>
                    </a>

                    <a href="javascript:void(0)" onclick="switchCoachSection('timesheet')" class="sidebar-nav-link" id="coachNavTimesheet">
                        <span class="d-flex align-items-center gap-2.5">
                            <i class="bi bi-clock-history nav-icon" style="color: #c084fc !important;"></i>
                            <span>Zeiterfassung &amp; Lohn</span>
                        </span>
                        <?php if ($openCount > 0): ?>
                            <span class="badge rounded-pill fw-bold" style="background-color: #78350f; color: #fde68a; font-size: 0.65rem; padding: 2px 7px;"><?= $openCount ?></span>
                        <?php endif; ?>
                    </a>

                    <a href="javascript:void(0)" onclick="switchCoachSection('hr')" class="sidebar-nav-link" id="coachNavHr">
                        <span class="d-flex align-items-center gap-2.5">
                            <i class="bi bi-person-vcard nav-icon" style="color: #818cf8 !important;"></i>
                            <span>Coach-Profil &amp; Kalender</span>
                        </span>
                    </a>

                    <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#coachReportIssueModal" class="sidebar-nav-link">
                        <span class="d-flex align-items-center gap-2.5">
                            <i class="bi bi-exclamation-octagon nav-icon" style="color: #f43f5e !important;"></i>
                            <span>Boot / Fehler melden</span>
                        </span>
                        <span class="badge rounded-pill fw-bold" style="background-color: #4c0519; color: #fda4af; font-size: 0.6rem; padding: 2px 6px;">Support</span>
                    </a>
                </nav>
            </div>

            <!-- Unterer Bereich: Admin Switcher, Rolle wechseln, Profil, Logout -->
            <div class="pt-4 border-top border-secondary border-opacity-25 mt-4 d-flex flex-column gap-3">
                <?php if ($canSwitchInstructor && count($allInstructors) > 1): ?>
                    <div>
                        <label class="text-uppercase fw-bold text-light text-opacity-50 d-block mb-1" style="font-size: 0.65rem;">Coach-Ansicht wechseln (Admin)</label>
                        <form method="get" action="">
                            <input type="hidden" name="route" value="admin/boating/instructor-portal">
                            <input type="hidden" name="month" value="<?= htmlspecialchars($selectedMonth, ENT_QUOTES, 'UTF-8') ?>">
                            <select name="instructor_id" class="form-select form-select-sm text-white border-secondary border-opacity-50" style="background-color: #1f1816; font-size: 0.78rem;" onchange="this.form.submit()">
                                <?php foreach ($allInstructors as $inst): ?>
                                    <option value="<?= (int) $inst['id'] ?>" <?= ((int) $inst['id'] === (int) $instructor['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars((string) ($inst['first_name'] . ' ' . $inst['last_name']), ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                <?php endif; ?>

                <!-- Bereiche wechseln -->
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-1.5" style="font-size: 0.65rem;">
                        <span class="text-uppercase fw-bold text-light text-opacity-50">Portal-Bereich</span>
                        <span class="badge text-uppercase fw-bold px-1.5 py-0.5" style="background-color: rgba(56, 189, 248, 0.2); color: #7dd3fc; font-size: 0.6rem;">COACH</span>
                    </div>
                    <div class="d-grid gap-1 p-1 rounded-3" style="grid-template-columns: 1fr 1fr 1fr; background-color: #1f1816; font-size: 0.75rem;">
                        <?php if ($canSwitchInstructor): ?>
                            <a href="?route=admin/boating" class="btn btn-sm text-light text-opacity-75 py-1 px-1 border-0 rounded text-decoration-none text-center">Admin</a>
                        <?php else: ?>
                            <span class="btn btn-sm text-secondary py-1 px-1 border-0 rounded text-center opacity-50">Admin</span>
                        <?php endif; ?>
                        <span class="btn btn-sm text-white py-1 px-1 border-0 rounded fw-bold text-center shadow-xs" style="background-color: #4a3d38;">Coach</span>
                        <a href="?route=bootschule" class="btn btn-sm text-light text-opacity-75 py-1 px-1 border-0 rounded text-decoration-none text-center">Kunde</a>
                    </div>
                </div>

                <!-- Coach Profil Card -->
                <div class="p-2.5 rounded-3 border border-secondary border-opacity-25 d-flex align-items-center justify-content-between gap-2" style="background-color: #3b302c;">
                    <div class="d-flex align-items-center gap-2 overflow-hidden">
                        <div class="rounded-3 d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 34px; height: 34px; background-color: rgba(56, 189, 248, 0.2); color: #7dd3fc; font-size: 0.78rem; border: 1px solid rgba(56, 189, 248, 0.35);">
                            <?= htmlspecialchars($coachInitials, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <div class="text-truncate">
                            <div class="text-white fw-bold small text-truncate"><?= htmlspecialchars($coachFullName, ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="text-light text-opacity-50" style="font-size: 0.7rem;">Fahrcoach &bull; Aktiv</div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm p-1 text-light text-opacity-50 border-0" onclick="switchCoachSection('hr')" title="Profil & Kalender-Sync">
                        <i class="bi bi-person-badge fs-6"></i>
                    </button>
                </div>

                <!-- Admin-Rücklink / Abmelden -->
                <?php if ($canSwitchInstructor): ?>
                    <a href="?route=admin/boating" class="btn btn-sm text-warning border border-warning border-opacity-50 rounded-3 py-1.5 fw-bold d-flex align-items-center justify-content-center gap-2 text-decoration-none" style="background-color: rgba(245, 158, 11, 0.12);">
                        <i class="bi bi-arrow-left"></i>
                        <span>Admin Dashboard</span>
                    </a>
                <?php endif; ?>
                <a href="?route=logout" class="btn btn-sm text-light text-opacity-75 border border-secondary border-opacity-50 rounded-3 py-1.5 fw-semibold d-flex align-items-center justify-content-center gap-2 text-decoration-none" style="background-color: rgba(61, 50, 46, 0.6);">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Abmelden</span>
                </a>
            </div>
        </aside>

        <!-- ============================================================== -->
        <!-- HAUPTINHALT (RECHTS)                                          -->
        <!-- ============================================================== -->
        <div class="flex-grow-1 p-3 p-md-4 p-lg-5 overflow-y-auto" style="background-color: #ede5dc;">
            <div class="portal-inner-container mx-auto" style="max-width: 1440px; width: 100%;">

                <!-- 1. Header Zeile: Begrüssung, Monatswahl & Quick Buttons -->
                <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3 mb-4">
                    <div>
                        <h2 class="h3 fw-extrabold text-dark mb-1" style="letter-spacing: -0.5px;">
                            Willkommen zurück, <?= htmlspecialchars($coachFirstName, ENT_QUOTES, 'UTF-8') ?>!
                        </h2>
                        <p class="text-muted small mb-0 d-flex flex-wrap align-items-center gap-1.5">
                            <span>Dein Coach-Cockpit für <?= htmlspecialchars($monthDisplay, ENT_QUOTES, 'UTF-8') ?></span>
                            <span>&bull;</span>
                            <span>
                                <?php if (($instructor['pay_rate_type'] ?? 'per_hour') === 'per_appointment'): ?>
                                    Honorar: CHF <?= number_format((float) ($instructor['pay_rate_amount'] ?? 0), 2, '.', "'") ?> Pauschale/Termin
                                <?php else: ?>
                                    Ansatz: CHF <?= number_format((float) ($instructor['pay_rate_amount'] ?? 0), 2, '.', "'") ?> / Std.
                                <?php endif; ?>
                            </span>
                            <?php if (!empty($instructor['license_number'])): ?>
                                <span>&bull;</span>
                                <span>Lizenz: <?= htmlspecialchars((string) $instructor['license_number'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </p>
                    </div>

                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <!-- Monat wechseln -->
                        <form method="get" action="" class="d-flex align-items-center gap-1.5">
                            <input type="hidden" name="route" value="admin/boating/instructor-portal">
                            <?php if ($canSwitchInstructor): ?>
                                <input type="hidden" name="instructor_id" value="<?= (int) $instructor['id'] ?>">
                            <?php endif; ?>
                            <input type="month" name="month" class="form-control form-control-sm rounded-3 bg-white" style="border: 1.5px solid #d5c5b2; max-width: 155px;" value="<?= htmlspecialchars($selectedMonth, ENT_QUOTES, 'UTF-8') ?>" onchange="this.form.submit()">
                        </form>

                        <button type="button" class="btn btn-light border rounded-3 px-3 py-2 small fw-semibold shadow-xs d-flex align-items-center gap-1.5 text-danger" style="background-color: #f6f0e7; border-color: #e3d6c5;" data-bs-toggle="modal" data-bs-target="#coachReportIssueModal">
                            <i class="bi bi-exclamation-octagon"></i>
                            <span>Vorfall / Fehler</span>
                        </button>

                        <button type="button" class="btn btn-light border rounded-3 px-3 py-2 small fw-semibold shadow-xs d-flex align-items-center gap-1.5" style="background-color: #f6f0e7; border-color: #e3d6c5;" data-bs-toggle="modal" data-bs-target="#modalAddAbsence">
                            <i class="bi bi-calendar-x text-warning"></i>
                            <span>Absenz erfassen</span>
                        </button>

                        <?php if ($tsStatus !== 'approved'): ?>
                            <button type="button" class="btn text-white rounded-3 px-3.5 py-2 small fw-bold shadow-xs d-flex align-items-center gap-1.5" style="background-color: #2d2421;" data-bs-toggle="modal" data-bs-target="#modalAddTimesheet">
                                <i class="bi bi-stopwatch text-warning"></i>
                                <span>Arbeitszeit erfassen</span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ============================================================== -->
                <!-- SECTION 1: OVERVIEW (DASHBOARD HEUTE & MONATS-KPI)             -->
                <!-- ============================================================== -->
                <div id="coachSectionOverview" class="coach-section">

                    <!-- Dual Seewetter-Kacheln (Luzern & Hergiswil) -->
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-6">
                            <div class="card border rounded-4 p-3.5 shadow-xs d-flex flex-row align-items-center justify-content-between" style="background-color: #f6f0e7; border: 1.5px solid #e3d6c5;">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-3 d-flex align-items-center justify-content-center text-secondary" style="width: 50px; height: 50px; background-color: #eee4d5; border: 1px solid #dfd0bc;">
                                        <i class="bi bi-cloud-sun fs-3 text-secondary"></i>
                                    </div>
                                    <div>
                                        <div class="d-flex align-items-center gap-1.5">
                                            <h6 class="fw-bold text-dark mb-0">Seewetter Luzern</h6>
                                            <span class="small text-muted">· Alpenquai</span>
                                        </div>
                                        <div class="h3 fw-extrabold text-dark mb-0 mt-0.5 tabular-nums">7°C</div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="d-flex align-items-center gap-1 justify-content-end mb-1">
                                        <button type="button" class="btn btn-sm rounded-2 py-0.5 px-2 fw-bold text-uppercase border shadow-2xs" style="font-size: 0.65rem; background-color: #ffffff; border-color: #ded0be; color: #5a3e32;" data-bs-toggle="modal" data-bs-target="#coachWeatherModal" onclick="switchCoachLakeStation('luzern');">
                                            DETAILS
                                        </button>
                                        <a href="https://search.ch/meteo/luzern" target="_blank" rel="noopener noreferrer" class="btn btn-sm rounded-2 py-0.5 px-2 text-uppercase fw-bold border shadow-2xs" style="font-size: 0.65rem; background-color: #fcecd7; border-color: #edd5ba; color: #431407;">
                                            PROGNOSE <i class="bi bi-box-arrow-up-right ms-0.5" style="font-size: 0.6rem;"></i>
                                        </a>
                                    </div>
                                    <div class="d-flex align-items-center gap-1.5 text-secondary small fw-medium mt-1 justify-content-end">
                                        <i class="bi bi-wind text-muted"></i>
                                        <span>7.7 km/h · Keine Sturmwarnung</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="card border rounded-4 p-3.5 shadow-xs d-flex flex-row align-items-center justify-content-between" style="background-color: #f6f0e7; border: 1.5px solid #e3d6c5;">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-3 d-flex align-items-center justify-content-center text-secondary" style="width: 50px; height: 50px; background-color: #eee4d5; border: 1px solid #dfd0bc;">
                                        <i class="bi bi-water fs-3 text-primary"></i>
                                    </div>
                                    <div>
                                        <div class="d-flex align-items-center gap-1.5">
                                            <h6 class="fw-bold text-dark mb-0">Seewetter Hergiswil (NW)</h6>
                                            <span class="small text-muted">· Buochs / Stansstad</span>
                                        </div>
                                        <div class="h3 fw-extrabold text-dark mb-0 mt-0.5 tabular-nums">7°C</div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="d-flex align-items-center gap-1 justify-content-end mb-1">
                                        <button type="button" class="btn btn-sm rounded-2 py-0.5 px-2 fw-bold text-uppercase border shadow-2xs" style="font-size: 0.65rem; background-color: #ffffff; border-color: #ded0be; color: #5a3e32;" data-bs-toggle="modal" data-bs-target="#coachWeatherModal" onclick="switchCoachLakeStation('hergiswil');">
                                            DETAILS
                                        </button>
                                        <a href="https://search.ch/meteo/hergiswil" target="_blank" rel="noopener noreferrer" class="btn btn-sm rounded-2 py-0.5 px-2 text-uppercase fw-bold border shadow-2xs" style="font-size: 0.65rem; background-color: #fcecd7; border-color: #edd5ba; color: #431407;">
                                            PROGNOSE <i class="bi bi-box-arrow-up-right ms-0.5" style="font-size: 0.6rem;"></i>
                                        </a>
                                    </div>
                                    <div class="d-flex align-items-center gap-1.5 text-secondary small fw-medium mt-1 justify-content-end">
                                        <i class="bi bi-wind text-muted"></i>
                                        <span>8.9 km/h · Sichtweite &gt;10 km</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Monatsabschluss Status Banner -->
                    <?php if ($openCount > 0): ?>
                        <div class="rounded-4 p-3.5 mb-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 shadow-xs" style="background-color: #f5eedf; border: 1.5px solid #edd5a3;">
                            <div class="d-flex align-items-start gap-3">
                                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px; background-color: #fef3c7; border: 1px solid #fde68a;">
                                    <i class="bi bi-exclamation-triangle-fill text-warning fs-5"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-1">
                                        Monatsabschluss bereit für <?= htmlspecialchars($monthDisplay, ENT_QUOTES, 'UTF-8') ?> (<?= $openCount ?> offene Fahrten)
                                    </h6>
                                    <p class="small text-muted mb-0">
                                        Offenes Honorar: <strong>CHF <?= number_format((float) ($monthStats['open_amount'] ?? 0.0), 2, '.', "'") ?></strong>. Nach Prüfung kannst du den Monat direkt an die Buchhaltung übergeben.
                                    </p>
                                </div>
                            </div>
                            <form method="post" action="?route=admin/boating/instructor-portal/close-month" onsubmit="return confirm('Möchtest du den Monat <?= htmlspecialchars($monthDisplay, ENT_QUOTES, 'UTF-8') ?> wirklich abschließen und alle <?= $openCount ?> offenen Termine zur Abrechnung übergeben?');" class="flex-shrink-0">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="instructor_id" value="<?= (int) $instructor['id'] ?>">
                                <input type="hidden" name="month" value="<?= htmlspecialchars($selectedMonth, ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="btn text-white fw-bold btn-sm rounded-3 px-3.5 py-2 shadow-xs" style="background-color: #b45309;">
                                    <i class="bi bi-check2-all me-1"></i> Monat <?= htmlspecialchars($monthDisplay, ENT_QUOTES, 'UTF-8') ?> abschliessen
                                </button>
                            </form>
                        </div>
                    <?php elseif ($totalMonthApps > 0): ?>
                        <div class="rounded-4 p-3.5 mb-4 d-flex align-items-center gap-3 shadow-xs bg-white" style="border: 1.5px solid #a7f3d0;">
                            <i class="bi bi-check-circle-fill text-success fs-4"></i>
                            <div class="small">
                                <strong class="text-dark">Monat <?= htmlspecialchars($monthDisplay, ENT_QUOTES, 'UTF-8') ?> abgeschlossen &amp; übergeben</strong>
                                <span class="text-muted ms-1">· Alle <?= $totalMonthApps ?> Fahrstunden sind bereits eingereicht oder ausbezahlt.</span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- 4 Monats-KPIs -->
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-xl-3">
                            <div class="card border rounded-4 p-3.5 h-100 shadow-xs" style="background-color: #f6f0e7; border: 1.5px solid #e3d6c5;">
                                <div class="small text-muted fw-semibold mb-1">Heutige Einsätze</div>
                                <div class="h3 fw-extrabold text-dark mb-0 tabular-nums"><?= count($todayAppointments) ?></div>
                                <div class="small text-muted mt-1"><?= count($upcomingAppointments) ?> anstehend gesamt</div>
                            </div>
                        </div>
                        <div class="col-6 col-xl-3">
                            <div class="card border rounded-4 p-3.5 h-100 shadow-xs" style="background-color: #f6f0e7; border: 1.5px solid #e3d6c5;">
                                <div class="small text-muted fw-semibold mb-1">Wasserzeit (<?= htmlspecialchars($germanMonthName, ENT_QUOTES, 'UTF-8') ?>)</div>
                                <div class="h3 fw-extrabold text-primary mb-0 tabular-nums"><?= number_format((float) ($monthStats['total_hours'] ?? 0.0), 1) ?> h</div>
                                <div class="small text-muted mt-1"><?= (int) ($monthStats['completed_appointments'] ?? 0) ?> / <?= $totalMonthApps ?> abgeschlossen</div>
                            </div>
                        </div>
                        <div class="col-6 col-xl-3">
                            <div class="card border rounded-4 p-3.5 h-100 shadow-xs" style="background-color: #f6f0e7; border: 1.5px solid #e3d6c5;">
                                <div class="small text-muted fw-semibold mb-1">Honorar Offen</div>
                                <div class="h3 fw-extrabold text-warning-emphasis mb-0 tabular-nums">CHF <?= number_format((float) ($monthStats['open_amount'] ?? 0.0), 2, '.', "'") ?></div>
                                <div class="small text-muted mt-1"><?= $openCount ?> Termin(e) offen</div>
                            </div>
                        </div>
                        <div class="col-6 col-xl-3">
                            <div class="card border rounded-4 p-3.5 h-100 shadow-xs" style="background-color: #f6f0e7; border: 1.5px solid #e3d6c5;">
                                <div class="small text-muted fw-semibold mb-1">Honorar Gesamt</div>
                                <div class="h3 fw-extrabold text-success mb-0 tabular-nums">CHF <?= number_format((float) ($monthStats['total_amount'] ?? 0.0), 2, '.', "'") ?></div>
                                <div class="small text-muted mt-1">CHF <?= number_format((float) ($monthStats['paid_amount'] ?? 0.0), 2, '.', "'") ?> ausbezahlt</div>
                            </div>
                        </div>
                    </div>

                    <!-- Hauptbereich: Links Nächster Einsatz + Heutige Termine (2/3), Rechts Flotte + Stützpunkte + Kalender-Sync (1/3) -->
                    <div class="row g-4">
                        <div class="col-12 col-lg-8 d-flex flex-column gap-4">

                            <!-- Nächster Einsatz am See -->
                            <div class="card border rounded-4 p-4 shadow-xs" style="background-color: #f6f0e7; border: 1.5px solid #e3d6c5;">
                                <div class="d-flex justify-content-between align-items-center pb-3 border-bottom mb-3" style="border-color: #e5dacb !important;">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-compass text-warning"></i>
                                        <h6 class="fw-bold text-dark mb-0">Nächster Einsatz &amp; Tagesplan</h6>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-dark rounded-3 fw-semibold" onclick="switchCoachSection('lessons')">
                                        Alle Termine (<?= count($upcomingAppointments) ?>) &rarr;
                                    </button>
                                </div>

                                <?php if (empty($upcomingAppointments)): ?>
                                    <div class="py-4 text-center">
                                        <div class="rounded-4 d-inline-flex align-items-center justify-content-center text-muted mb-2" style="width: 48px; height: 48px; background-color: #eee4d5; border: 1px solid #dfd0bc;">
                                            <i class="bi bi-calendar-check fs-4 text-secondary"></i>
                                        </div>
                                        <h6 class="fw-bold text-dark mb-1">Aktuell keine bevorstehenden Fahrstunden zugewiesen</h6>
                                        <p class="small text-muted mb-0">Neue Zuweisungen durch die Disposition erscheinen hier automatisch.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="d-flex flex-column gap-3">
                                        <?php foreach (array_slice($upcomingAppointments, 0, 5) as $app): ?>
                                            <?php
                                            $appStatus = (string) ($app['status'] ?? 'confirmed');
                                            $appBadge  = $statusBadges[$appStatus] ?? 'secondary';
                                            $appLabel  = $statusLabels[$appStatus] ?? ucfirst($appStatus);
                                            $custFull  = trim((string) (($app['customer_first_name'] ?? '') . ' ' . ($app['customer_last_name'] ?? '')));
                                            $locName   = (string) ($app['location_name'] ?? 'Luzern Alpenquai');
                                            $mapsQuery = urlencode($locName . ' Vierwaldstättersee');
                                            ?>
                                            <div class="p-3.5 rounded-3 bg-white border d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3" style="border-color: #dfd0bc !important;">
                                                <div>
                                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                                        <span class="fw-bold text-dark font-monospace">
                                                            <?= date('d.m.Y', strtotime((string) $app['date'])) ?> · <?= substr((string) $app['start_time'], 0, 5) ?>–<?= substr((string) $app['end_time'], 0, 5) ?>
                                                        </span>
                                                        <span class="text-muted">·</span>
                                                        <span class="fw-bold text-dark"><?= htmlspecialchars($custFull !== '' ? $custFull : 'Fahrschüler', ENT_QUOTES, 'UTF-8') ?></span>
                                                        <span class="badge bg-<?= $appBadge ?>-subtle text-<?= $appBadge ?> rounded-pill" style="font-size: 0.7rem;">
                                                            <?= htmlspecialchars($appLabel, ENT_QUOTES, 'UTF-8') ?>
                                                        </span>
                                                    </div>
                                                    <div class="small text-muted d-flex flex-wrap align-items-center gap-3">
                                                        <span><i class="bi bi-tag me-1"></i><?= htmlspecialchars((string) ($app['service_name'] ?? 'Fahrstunde'), ENT_QUOTES, 'UTF-8') ?></span>
                                                        <span><i class="bi bi-life-preserver me-1"></i><?= htmlspecialchars((string) ($app['boat_name'] ?? 'Boot zugewiesen'), ENT_QUOTES, 'UTF-8') ?></span>
                                                        <a href="https://www.google.com/maps/search/?api=1&query=<?= $mapsQuery ?>" target="_blank" rel="noopener noreferrer" class="text-decoration-none text-dark fw-semibold">
                                                            <i class="bi bi-geo-alt-fill text-danger me-1"></i><?= htmlspecialchars($locName, ENT_QUOTES, 'UTF-8') ?> &nearr;
                                                        </a>
                                                        <?php if (!empty($app['customer_phone'])): ?>
                                                            <a href="tel:<?= htmlspecialchars((string) $app['customer_phone'], ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none text-primary fw-semibold">
                                                                <i class="bi bi-telephone me-1"></i><?= htmlspecialchars((string) $app['customer_phone'], ENT_QUOTES, 'UTF-8') ?>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                                    <?php if ($appStatus !== 'completed' && !str_starts_with($appStatus, 'cancelled')): ?>
                                                        <button type="button" class="btn btn-sm text-white fw-bold rounded-3 px-3 py-1.5" style="background-color: #065f46;"
                                                                onclick="openCompleteLessonModal(<?= (int) $app['id'] ?>, <?= htmlspecialchars(json_encode($custFull), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode((string) ($app['service_name'] ?? 'Fahrstunde')), ENT_QUOTES, 'UTF-8') ?>)">
                                                            <i class="bi bi-check2-circle me-1"></i> Lektion abschliessen
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="small fw-bold text-success">
                                                            <i class="bi bi-check-all me-1"></i>Abgeschlossen
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Letzte Monatsfahrten Vorschau -->
                            <div class="card border rounded-4 p-4 shadow-xs" style="background-color: #f6f0e7; border: 1.5px solid #e3d6c5;">
                                <div class="d-flex justify-content-between align-items-center pb-3 border-bottom mb-3" style="border-color: #e5dacb !important;">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-journal-check text-muted"></i>
                                        <h6 class="fw-bold text-dark mb-0">Aktuelle Fahrten im <?= htmlspecialchars($monthDisplay, ENT_QUOTES, 'UTF-8') ?> (<?= count($monthAppointments) ?>)</h6>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-light border rounded-3 fw-semibold" style="background-color: #eee4d5; border-color: #dfd0bc;" onclick="switchCoachSection('timesheet')">
                                        Zum Lohnrapport &rarr;
                                    </button>
                                </div>

                                <?php if (empty($monthAppointments)): ?>
                                    <p class="small text-muted mb-0 py-3 text-center">Keine Fahrten für diesen Monat erfasst.</p>
                                <?php else: ?>
                                    <div class="table-responsive bg-white rounded-3 border" style="border-color: #dfd0bc !important;">
                                        <table class="table table-hover align-middle mb-0 small">
                                            <thead class="table-light text-muted text-uppercase" style="font-size: 0.7rem;">
                                                <tr>
                                                    <th class="ps-3">Datum</th>
                                                    <th>Schüler</th>
                                                    <th>Leistung &amp; Boot</th>
                                                    <th class="text-end">Honorar</th>
                                                    <th class="pe-3 text-end">Abrechnung</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach (array_slice($monthAppointments, 0, 6) as $app): ?>
                                                    <?php
                                                    $pStatus = (string) ($app['instructor_payout_status'] ?? 'open');
                                                    $pBadge  = $payoutBadges[$pStatus] ?? 'secondary';
                                                    $pLabel  = $payoutLabels[$pStatus] ?? $pStatus;
                                                    ?>
                                                    <tr>
                                                        <td class="ps-3 font-monospace fw-semibold text-dark">
                                                            <?= date('d.m.Y', strtotime((string) $app['date'])) ?> · <?= substr((string) $app['start_time'], 0, 5) ?>
                                                        </td>
                                                        <td class="fw-semibold text-dark">
                                                            <?= htmlspecialchars(trim((string) (($app['customer_first_name'] ?? '') . ' ' . ($app['customer_last_name'] ?? ''))), ENT_QUOTES, 'UTF-8') ?>
                                                        </td>
                                                        <td class="text-muted">
                                                            <?= htmlspecialchars((string) ($app['service_name'] ?? 'Fahrstunde'), ENT_QUOTES, 'UTF-8') ?>
                                                            · <?= htmlspecialchars((string) ($app['boat_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                                                        </td>
                                                        <td class="text-end fw-bold text-dark tabular-nums">
                                                            CHF <?= number_format((float) ($app['instructor_payout_amount'] ?? 0), 2, '.', "'") ?>
                                                        </td>
                                                        <td class="pe-3 text-end">
                                                            <span class="badge bg-<?= $pBadge ?>-subtle text-<?= $pBadge ?> rounded-pill">
                                                                <?= htmlspecialchars($pLabel, ENT_QUOTES, 'UTF-8') ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>

                        </div>

                        <!-- Rechte Spalte: Flotten-Kurzcheck, Kalender-Sync & Stützpunkte -->
                        <div class="col-12 col-lg-4 d-flex flex-column gap-4">

                            <!-- Schulungsflotte Status -->
                            <div class="card border rounded-4 p-4 shadow-xs" style="background-color: #f6f0e7; border: 1.5px solid #e3d6c5;">
                                <div class="d-flex align-items-center justify-content-between pb-2 border-bottom mb-3" style="border-color: #e5dacb !important;">
                                    <h6 class="fw-bold text-dark mb-0">Aktive Flotte &amp; Boote</h6>
                                    <button type="button" class="btn btn-sm btn-link text-danger p-0 small fw-semibold text-decoration-none" data-bs-toggle="modal" data-bs-target="#coachReportIssueModal">
                                        Schaden melden
                                    </button>
                                </div>
                                <?php if (empty($boats)): ?>
                                    <p class="small text-muted mb-0">Boesch 620 &amp; Schulungsboot Kat. A einsatzbereit.</p>
                                <?php else: ?>
                                    <div class="d-flex flex-column gap-2 small">
                                        <?php foreach (array_slice($boats, 0, 4) as $b): ?>
                                            <div class="p-2.5 rounded-3 bg-white border d-flex align-items-center justify-content-between" style="border-color: #dfd0bc !important;">
                                                <div>
                                                    <div class="fw-bold text-dark"><?= htmlspecialchars((string) ($b['name'] ?? 'Boot'), ENT_QUOTES, 'UTF-8') ?></div>
                                                    <div class="text-muted" style="font-size: 0.72rem;"><?= htmlspecialchars((string) ($b['registration_number'] ?? $b['type'] ?? 'Kat. A'), ENT_QUOTES, 'UTF-8') ?></div>
                                                </div>
                                                <span class="text-success fw-bold" style="font-size: 0.75rem;">Bereit</span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Kalender-Abo -->
                            <div class="card border rounded-4 p-4 shadow-xs" style="background-color: #f6f0e7; border: 1.5px solid #e3d6c5;">
                                <div class="d-flex align-items-center gap-3 pb-3 border-bottom mb-3" style="border-color: #e5dacb !important;">
                                    <span class="p-2.5 rounded-3 d-inline-flex text-primary flex-shrink-0" style="background-color: #e0f2fe; border: 1px solid #bae6fd;">
                                        <i class="bi bi-calendar2-check fs-5"></i>
                                    </span>
                                    <div>
                                        <h6 class="fw-bold text-dark mb-0 small">Coach-Einsatzplan im Handy</h6>
                                        <span class="text-muted" style="font-size: 0.75rem;">WebCal Live-Sync für Apple &amp; Google</span>
                                    </div>
                                </div>
                                <div class="font-monospace text-dark text-truncate bg-white px-2.5 py-2 rounded border user-select-all mb-2" style="font-size: 0.72rem; border-color: #d5c5b2 !important;" id="coachCalendarUrl">
                                    webcal://<?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'dein.coaching-boot.ch') ?>/?route=admin/boating/calendar.ics&amp;instructor_id=<?= (int) $instructor['id'] ?>
                                </div>
                                <button type="button" class="btn text-white rounded-3 py-2 px-3 btn-sm fw-bold w-100 shadow-xs" style="background-color: #2d2421;" onclick="copyCoachCalendarUrl(this)">
                                    <i class="bi bi-clipboard me-1"></i> Kalender-Link kopieren
                                </button>
                            </div>

                            <!-- Stützpunkte & Treffpunkte -->
                            <div class="card border rounded-4 p-4 shadow-xs" style="background-color: #f6f0e7; border: 1.5px solid #e3d6c5;">
                                <h6 class="fw-bold text-dark mb-2 small">Stützpunkte &amp; Anlegestellen (Maps)</h6>
                                <div class="d-flex flex-column gap-1.5 small">
                                    <a href="https://www.google.com/maps/search/?api=1&query=Alpenquai+12+6005+Luzern" target="_blank" rel="noopener noreferrer" class="text-decoration-none text-dark d-flex align-items-center justify-content-between p-2 rounded-3 bg-white border" style="border-color: #d5c5b2 !important;">
                                        <span><i class="bi bi-geo-alt-fill text-danger me-1.5"></i><strong>Luzern</strong> · Alpenquai 12</span>
                                        <span class="text-muted">Maps &nearr;</span>
                                    </a>
                                    <a href="https://www.google.com/maps/search/?api=1&query=Seestrasse+20+6052+Hergiswil" target="_blank" rel="noopener noreferrer" class="text-decoration-none text-dark d-flex align-items-center justify-content-between p-2 rounded-3 bg-white border" style="border-color: #d5c5b2 !important;">
                                        <span><i class="bi bi-geo-alt-fill text-danger me-1.5"></i><strong>Hergiswil</strong> · Seestrasse 20</span>
                                        <span class="text-muted">Maps &nearr;</span>
                                    </a>
                                    <a href="https://www.google.com/maps/search/?api=1&query=Seestrasse+48+6374+Buochs" target="_blank" rel="noopener noreferrer" class="text-decoration-none text-dark d-flex align-items-center justify-content-between p-2 rounded-3 bg-white border" style="border-color: #d5c5b2 !important;">
                                        <span><i class="bi bi-geo-alt-fill text-danger me-1.5"></i><strong>Buochs</strong> · Seestrasse 48</span>
                                        <span class="text-muted">Maps &nearr;</span>
                                    </a>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- ============================================================== -->
                <!-- SECTION 2: FAHRTEN & EINSÄTZE (ALLE TERMINE)                  -->
                <!-- ============================================================== -->
                <div id="coachSectionLessons" class="coach-section" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="fw-bold text-dark mb-0">Fahrten &amp; Einsätze</h4>
                        <button type="button" class="btn btn-sm btn-outline-dark rounded-3 fw-semibold" onclick="switchCoachSection('overview')">
                            &larr; Zurück zum Dashboard
                        </button>
                    </div>

                    <!-- Bevorstehende Termine -->
                    <div class="card border rounded-4 p-4 shadow-xs mb-4" style="background-color: #f6f0e7; border: 1.5px solid #e3d6c5;">
                        <h6 class="fw-bold text-dark mb-3">Bevorstehende Termine (<?= count($upcomingAppointments) ?>)</h6>
                        <div class="table-responsive bg-white rounded-3 border" style="border-color: #dfd0bc !important;">
                            <table class="table table-hover align-middle mb-0 small">
                                <thead class="table-light text-muted text-uppercase" style="font-size: 0.72rem;">
                                    <tr>
                                        <th class="ps-3">Datum &amp; Zeit</th>
                                        <th>Schüler &amp; Kontakt</th>
                                        <th>Leistung &amp; Boot</th>
                                        <th>Treffpunkt</th>
                                        <th>Status</th>
                                        <th class="text-end">Honorar</th>
                                        <th class="pe-3 text-end">Aktion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($upcomingAppointments)): ?>
                                        <tr><td colspan="7" class="text-center py-4 text-muted">Keine bevorstehenden Termine eingetragen.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($upcomingAppointments as $app): ?>
                                            <?php
                                            $appStatus = (string) ($app['status'] ?? 'requested');
                                            $appBadge  = $statusBadges[$appStatus] ?? 'secondary';
                                            $custFull  = trim((string) (($app['customer_first_name'] ?? '') . ' ' . ($app['customer_last_name'] ?? '')));
                                            ?>
                                            <tr>
                                                <td class="ps-3 py-3 font-monospace">
                                                    <div class="fw-bold text-dark"><?= date('d.m.Y', strtotime((string) $app['date'])) ?></div>
                                                    <div class="text-muted"><?= substr((string) $app['start_time'], 0, 5) ?> – <?= substr((string) $app['end_time'], 0, 5) ?></div>
                                                </td>
                                                <td>
                                                    <div class="fw-bold text-dark"><?= htmlspecialchars($custFull, ENT_QUOTES, 'UTF-8') ?></div>
                                                    <div class="text-muted">
                                                        <?php if (!empty($app['customer_phone'])): ?><?= htmlspecialchars((string) $app['customer_phone'], ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold text-dark"><?= htmlspecialchars((string) ($app['service_name'] ?? 'Termin'), ENT_QUOTES, 'UTF-8') ?></div>
                                                    <div class="text-muted"><?= htmlspecialchars((string) ($app['boat_name'] ?? 'Boot wird zugewiesen'), ENT_QUOTES, 'UTF-8') ?></div>
                                                </td>
                                                <td><?= htmlspecialchars((string) ($app['location_name'] ?? 'Hafen'), ENT_QUOTES, 'UTF-8') ?></td>
                                                <td><span class="badge bg-<?= $appBadge ?>-subtle text-<?= $appBadge ?> rounded-pill"><?= htmlspecialchars($statusLabels[$appStatus] ?? ucfirst($appStatus), ENT_QUOTES, 'UTF-8') ?></span></td>
                                                <td class="text-end fw-bold tabular-nums">CHF <?= number_format((float) ($app['instructor_payout_amount'] ?? 0), 2, '.', "'") ?></td>
                                                <td class="pe-3 text-end">
                                                    <?php if ($appStatus !== 'completed' && !str_starts_with($appStatus, 'cancelled')): ?>
                                                        <button type="button" class="btn btn-sm text-white fw-bold rounded-2 px-2.5" style="background-color: #065f46;"
                                                                onclick="openCompleteLessonModal(<?= (int) $app['id'] ?>, <?= htmlspecialchars(json_encode($custFull), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode((string) ($app['service_name'] ?? 'Fahrstunde')), ENT_QUOTES, 'UTF-8') ?>)">
                                                            Abschliessen
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="text-success fw-semibold">Erledigt</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Vollständiges Monatsjournal -->
                    <div class="card border rounded-4 p-4 shadow-xs" style="background-color: #f6f0e7; border: 1.5px solid #e3d6c5;">
                        <h6 class="fw-bold text-dark mb-3">Fahrten im <?= htmlspecialchars($monthDisplay, ENT_QUOTES, 'UTF-8') ?> (<?= count($monthAppointments) ?>)</h6>
                        <div class="table-responsive bg-white rounded-3 border" style="border-color: #dfd0bc !important;">
                            <table class="table table-hover align-middle mb-0 small">
                                <thead class="table-light text-muted text-uppercase" style="font-size: 0.72rem;">
                                    <tr>
                                        <th class="ps-3">Datum &amp; Zeit</th>
                                        <th>Schüler</th>
                                        <th>Boot &amp; Leistung</th>
                                        <th>Dauer</th>
                                        <th class="text-end">Honorar</th>
                                        <th>Abrechnungsstatus</th>
                                        <th class="pe-3">Notiz</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($monthAppointments)): ?>
                                        <tr><td colspan="7" class="text-center py-4 text-muted">Keine Termine im Monat <?= htmlspecialchars($monthDisplay, ENT_QUOTES, 'UTF-8') ?> gefunden.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($monthAppointments as $app): ?>
                                            <?php
                                            $pStatus  = (string) ($app['instructor_payout_status'] ?? 'open');
                                            $pBadge   = $payoutBadges[$pStatus] ?? 'secondary';
                                            $pLabel   = $payoutLabels[$pStatus] ?? $pStatus;
                                            $duration = InstructorPayoutService::resolveDurationMinutes($app);
                                            ?>
                                            <tr>
                                                <td class="ps-3 py-3 font-monospace">
                                                    <div class="fw-bold text-dark"><?= date('d.m.Y', strtotime((string) $app['date'])) ?></div>
                                                    <div class="text-muted"><?= substr((string) $app['start_time'], 0, 5) ?>–<?= substr((string) $app['end_time'], 0, 5) ?></div>
                                                </td>
                                                <td class="fw-semibold text-dark"><?= htmlspecialchars(trim((string) (($app['customer_first_name'] ?? '') . ' ' . ($app['customer_last_name'] ?? ''))), ENT_QUOTES, 'UTF-8') ?></td>
                                                <td>
                                                    <div class="text-dark fw-semibold"><?= htmlspecialchars((string) ($app['service_name'] ?? 'Fahrstunde'), ENT_QUOTES, 'UTF-8') ?></div>
                                                    <div class="text-muted"><?= htmlspecialchars((string) ($app['boat_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                                                </td>
                                                <td><?= $duration ?> Min</td>
                                                <td class="text-end fw-bold tabular-nums">CHF <?= number_format((float) ($app['instructor_payout_amount'] ?? 0), 2, '.', "'") ?></td>
                                                <td><span class="badge bg-<?= $pBadge ?>-subtle text-<?= $pBadge ?> rounded-pill"><?= htmlspecialchars($pLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                                                <td class="pe-3 text-muted"><?= htmlspecialchars((string) ($app['instructor_payout_note'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ============================================================== -->
                <!-- SECTION 3: ABSENZEN & WOCHEN-ARBEITSPLAN                       -->
                <!-- ============================================================== -->
                <div id="coachSectionAvailability" class="coach-section" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="fw-bold text-dark mb-0">Absenzen, Sperrzeiten &amp; Wochenplan</h4>
                        <button type="button" class="btn btn-sm btn-outline-dark rounded-3 fw-semibold" onclick="switchCoachSection('overview')">
                            &larr; Zurück zum Dashboard
                        </button>
                    </div>

                    <!-- Absenzen -->
                    <div class="card border rounded-4 p-4 shadow-xs mb-4" style="background-color: #f6f0e7; border: 1.5px solid #e3d6c5;">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <div>
                                <h6 class="fw-bold text-dark mb-0">Eingetragene Absenzen &amp; Sperrzeiten (<?= htmlspecialchars($monthDisplay, ENT_QUOTES, 'UTF-8') ?>)</h6>
                                <span class="small text-muted">Verhindert automatische Online-Buchungen während privater Sperrzeiten oder Ferien.</span>
                            </div>
                            <button type="button" class="btn btn-warning btn-sm fw-bold rounded-3 shadow-xs" data-bs-toggle="modal" data-bs-target="#modalAddAbsence">
                                <i class="bi bi-plus-circle me-1"></i> Absenz erfassen
                            </button>
                        </div>

                        <div class="table-responsive bg-white rounded-3 border" style="border-color: #dfd0bc !important;">
                            <table class="table table-hover align-middle mb-0 small">
                                <thead class="table-light text-muted text-uppercase" style="font-size: 0.72rem;">
                                    <tr>
                                        <th class="ps-3">Datum</th>
                                        <th>Zeitfenster</th>
                                        <th>Typ</th>
                                        <th>Standort</th>
                                        <th>Bemerkung</th>
                                        <th class="pe-3 text-end">Aktion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($coachAvailabilities)): ?>
                                        <tr><td colspan="6" class="text-center py-4 text-muted">Keine Absenzen für diesen Monat hinterlegt.</td></tr>
                                    <?php else: ?>
                                        <?php
                                        $availTypeLabels = [
                                            'vacation'         => ['Ferien / Urlaub', 'success'],
                                            'sick'             => ['Krankheit / Unfall', 'danger'],
                                            'military'         => ['Militär / Zivildienst', 'info'],
                                            'unavailable'      => ['Sperrzeit / Privat', 'warning'],
                                            'holiday_override' => ['Feiertags-Ausnahme', 'primary'],
                                            'available'        => ['Verfügbar', 'primary'],
                                            'other'            => ['Sonstige Absenz', 'secondary'],
                                        ];
                                        ?>
                                        <?php foreach ($coachAvailabilities as $av): ?>
                                            <?php
                                            $aType = (string) ($av['type'] ?? 'unavailable');
                                            $aInfo = $availTypeLabels[$aType] ?? ['Absenz', 'secondary'];
                                            $locName = !empty($av['location_id']) ? ($locationsMap[(int) $av['location_id']] ?? ('Hafen #' . $av['location_id'])) : 'Alle Standorte';
                                            ?>
                                            <tr>
                                                <td class="ps-3 py-2.5 fw-bold font-monospace"><?= date('d.m.Y', strtotime((string) $av['date'])) ?></td>
                                                <td><?= (!empty($av['start_time']) && !empty($av['end_time'])) ? (substr((string) $av['start_time'], 0, 5) . ' – ' . substr((string) $av['end_time'], 0, 5)) : 'Ganztägig' ?></td>
                                                <td><span class="badge bg-<?= $aInfo[1] ?>-subtle text-<?= $aInfo[1] ?> rounded-pill"><?= htmlspecialchars($aInfo[0], ENT_QUOTES, 'UTF-8') ?></span></td>
                                                <td><?= htmlspecialchars($locName, ENT_QUOTES, 'UTF-8') ?></td>
                                                <td class="text-muted"><?= htmlspecialchars((string) ($av['note'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                                <td class="pe-3 text-end">
                                                    <form method="post" action="?route=admin/boating/instructor-portal/delete-availability" onsubmit="return confirm('Eintrag wirklich löschen?');" class="d-inline">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                        <input type="hidden" name="id" value="<?= (int) $av['id'] ?>">
                                                        <input type="hidden" name="instructor_id" value="<?= (int) $instructor['id'] ?>">
                                                        <input type="hidden" name="month" value="<?= htmlspecialchars($selectedMonth, ENT_QUOTES, 'UTF-8') ?>">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-2"><i class="bi bi-trash"></i></button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Wochen-Arbeitsplan -->
                    <div class="card border rounded-4 p-4 shadow-xs" style="background-color: #f6f0e7; border: 1.5px solid #e3d6c5;">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <div>
                                <h6 class="fw-bold text-dark mb-0">Regulärer Wochen-Arbeitsplan</h6>
                                <span class="small text-muted">Standard-Buchungsfenster für Online-Termine.</span>
                            </div>
                            <button type="button" class="btn btn-success btn-sm fw-bold rounded-3 shadow-xs" data-bs-toggle="modal" data-bs-target="#modalAddTemplate">
                                <i class="bi bi-plus-circle me-1"></i> Zeitfenster hinzufügen
                            </button>
                        </div>

                        <div class="table-responsive bg-white rounded-3 border" style="border-color: #dfd0bc !important;">
                            <table class="table table-hover align-middle mb-0 small">
                                <thead class="table-light text-muted text-uppercase" style="font-size: 0.72rem;">
                                    <tr>
                                        <th class="ps-3">Wochentag</th>
                                        <th>Arbeitszeit</th>
                                        <th>Standort / Hafen</th>
                                        <th>Status</th>
                                        <th class="pe-3 text-end">Aktion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($weeklyTemplates)): ?>
                                        <tr><td colspan="5" class="text-center py-4 text-muted">Standardzeiten aktiv (08:00 – 19:00 Mo–Sa).</td></tr>
                                    <?php else: ?>
                                        <?php
                                        $dayNames = [1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag', 6 => 'Samstag', 7 => 'Sonntag'];
                                        ?>
                                        <?php foreach ($weeklyTemplates as $tpl): ?>
                                            <?php
                                            $dayNum = (int) ($tpl['weekday'] ?? $tpl['day_of_week'] ?? 1);
                                            $locName = !empty($tpl['location_id']) ? ($locationsMap[(int) $tpl['location_id']] ?? ('Hafen #' . $tpl['location_id'])) : 'Alle Standorte';
                                            ?>
                                            <tr>
                                                <td class="ps-3 py-2.5 fw-bold text-dark"><?= $dayNames[$dayNum] ?? ('Tag ' . $dayNum) ?></td>
                                                <td class="font-monospace"><?= substr((string) $tpl['start_time'], 0, 5) ?> – <?= substr((string) $tpl['end_time'], 0, 5) ?> Uhr</td>
                                                <td><?= htmlspecialchars($locName, ENT_QUOTES, 'UTF-8') ?></td>
                                                <td><?= !empty($tpl['is_active']) ? '<span class="badge bg-success-subtle text-success rounded-pill">Aktiv buchbar</span>' : '<span class="badge bg-secondary-subtle text-muted rounded-pill">Pausiert</span>' ?></td>
                                                <td class="pe-3 text-end">
                                                    <form method="post" action="?route=admin/boating/instructor-portal/weekly-templates/delete" onsubmit="return confirm('Wochenfenster wirklich löschen?');" class="d-inline">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                        <input type="hidden" name="id" value="<?= (int) $tpl['id'] ?>">
                                                        <input type="hidden" name="instructor_id" value="<?= (int) $instructor['id'] ?>">
                                                        <input type="hidden" name="month" value="<?= htmlspecialchars($selectedMonth, ENT_QUOTES, 'UTF-8') ?>">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-2"><i class="bi bi-trash"></i></button>
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

                <!-- ============================================================== -->
                <!-- SECTION 4: CH-ZEITERFASSUNG & LOHN-TIMESHEET                   -->
                <!-- ============================================================== -->
                <div id="coachSectionTimesheet" class="coach-section" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="fw-bold text-dark mb-0">CH-Zeiterfassung &amp; Monats-Timesheet</h4>
                        <button type="button" class="btn btn-sm btn-outline-dark rounded-3 fw-semibold" onclick="switchCoachSection('overview')">
                            &larr; Zurück zum Dashboard
                        </button>
                    </div>

                    <!-- Status Banner -->
                    <?php if ($tsStatus === 'rejected'): ?>
                        <div class="alert alert-danger rounded-4 shadow-sm mb-4">
                            <strong>Timesheet zurückgewiesen:</strong> <?= htmlspecialchars((string) ($timesheetSummary['rejection_reason'] ?? 'Bitte prüfen'), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php elseif ($tsStatus === 'submitted'): ?>
                        <div class="alert alert-info rounded-4 shadow-sm mb-4">
                            <strong>Timesheet eingereicht (Version v<?= $pdfVersion ?>)</strong> · Wartet auf Freigabe durch Administration.
                        </div>
                    <?php elseif ($tsStatus === 'approved'): ?>
                        <div class="alert alert-success rounded-4 shadow-sm mb-4">
                            <strong>Timesheet genehmigt (Version v<?= $pdfVersion ?>)</strong> · Für Lohnabrechnung freigegeben.
                        </div>
                    <?php endif; ?>

                    <!-- Schweizer Lohnübersicht -->
                    <div class="card border rounded-4 p-4 shadow-xs mb-4" style="background-color: #f6f0e7; border: 1.5px solid #e3d6c5;">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 pb-3 border-bottom mb-3" style="border-color: #e5dacb !important;">
                            <div>
                                <h6 class="fw-bold text-dark mb-0">Schweizer Lohnübersicht &amp; Abzüge (<?= htmlspecialchars($monthDisplay, ENT_QUOTES, 'UTF-8') ?>)</h6>
                                <span class="small text-muted">Version v<?= $pdfVersion ?> · Status: <?= htmlspecialchars(ucfirst($tsStatus), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <?php if ($tsStatus !== 'approved' && $tsStatus !== 'submitted'): ?>
                                <form method="post" action="?route=admin/boating/instructor-portal/submit-timesheet" onsubmit="return confirm('Möchtest du das Timesheet für <?= htmlspecialchars($monthDisplay, ENT_QUOTES, 'UTF-8') ?> jetzt verbindlich einreichen?');">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="instructor_id" value="<?= (int) $instructor['id'] ?>">
                                    <input type="hidden" name="month" value="<?= htmlspecialchars($selectedMonth, ENT_QUOTES, 'UTF-8') ?>">
                                    <button type="submit" class="btn text-white fw-bold btn-sm rounded-3 px-4 py-2" style="background-color: #2d2421;">
                                        <i class="bi bi-send me-1 text-warning"></i> Timesheet jetzt einreichen
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="p-3 bg-white rounded-3 border" style="border-color: #dfd0bc !important;">
                                    <div class="small text-muted fw-semibold">Brutto-Honorar</div>
                                    <div class="h4 fw-bold text-dark mb-1 tabular-nums">CHF <?= number_format((float) ($payroll['gross_amount'] ?? $timesheetSummary['total_amount'] ?? 0), 2, '.', "'") ?></div>
                                    <div class="small text-muted"><?= number_format((float) ($timesheetSummary['total_hours'] ?? 0), 2) ?> Stunden erfasst</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-white rounded-3 border" style="border-color: #dfd0bc !important;">
                                    <div class="small text-muted fw-semibold">Abzüge (AHV / ALV / NBU)</div>
                                    <div class="h4 fw-bold text-danger mb-1 tabular-nums">- CHF <?= number_format((float) ($payroll['deductions_total'] ?? 0), 2, '.', "'") ?></div>
                                    <div class="small text-muted">AHV <?= number_format((float) ($payroll['ahv_rate'] ?? 5.3), 1) ?>% · ALV <?= number_format((float) ($payroll['alv_rate'] ?? 1.1), 1) ?>% · NBU <?= number_format((float) ($payroll['nbu_rate'] ?? 1.3), 1) ?>%</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 rounded-3 border" style="background-color: #ecfdf5; border-color: #a7f3d0 !important;">
                                    <div class="small text-success fw-semibold">Auszahlungsbetrag Netto</div>
                                    <div class="h4 fw-bold text-success mb-1 tabular-nums">CHF <?= number_format((float) ($payroll['net_amount'] ?? 0), 2, '.', "'") ?></div>
                                    <div class="small text-success">Zur Überweisung an Bankkonto</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Erfasste Arbeitszeiten -->
                    <div class="card border rounded-4 p-4 shadow-xs" style="background-color: #f6f0e7; border: 1.5px solid #e3d6c5;">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <div>
                                <h6 class="fw-bold text-dark mb-0">Erfasste Arbeitszeiten &amp; Zusatzleistungen</h6>
                                <span class="small text-muted">Wartung, Bereitstellung, Theorieunterricht und Prüfungsbegleitung.</span>
                            </div>
                            <?php if ($tsStatus !== 'approved'): ?>
                                <button type="button" class="btn text-white btn-sm fw-bold rounded-3" style="background-color: #2d2421;" data-bs-toggle="modal" data-bs-target="#modalAddTimesheet">
                                    <i class="bi bi-plus-circle me-1 text-warning"></i> Arbeitszeit erfassen
                                </button>
                            <?php endif; ?>
                        </div>

                        <div class="table-responsive bg-white rounded-3 border" style="border-color: #dfd0bc !important;">
                            <table class="table table-hover align-middle mb-0 small">
                                <thead class="table-light text-muted text-uppercase" style="font-size: 0.72rem;">
                                    <tr>
                                        <th class="ps-3">Datum</th>
                                        <th>Kategorie</th>
                                        <th>Zeit &amp; Pause</th>
                                        <th>Stunden</th>
                                        <th class="text-end">Ansatz</th>
                                        <th class="text-end">Total</th>
                                        <th>Status</th>
                                        <th class="pe-3">Notiz</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($coachWorkingHours)): ?>
                                        <tr><td colspan="8" class="text-center py-4 text-muted">Keine zusätzlichen Arbeitszeiten für diesen Monat hinterlegt.</td></tr>
                                    <?php else: ?>
                                        <?php
                                        $catLabels = ['lesson' => 'Fahrstunde', 'exam' => 'Prüfung', 'admin' => 'Administration', 'maintenance' => 'Wartung / Boot', 'other' => 'Sonstiges'];
                                        $tsStatusBadges = ['draft' => 'secondary', 'submitted' => 'warning', 'approved' => 'success', 'exported' => 'info', 'rejected' => 'danger', 'reopened' => 'warning'];
                                        ?>
                                        <?php foreach ($coachWorkingHours as $wh): ?>
                                            <?php
                                            $cat = (string) ($wh['category'] ?? 'lesson');
                                            $st  = (string) ($wh['status'] ?? 'draft');
                                            $stBadge = $tsStatusBadges[$st] ?? 'secondary';
                                            ?>
                                            <tr>
                                                <td class="ps-3 py-2.5 fw-bold font-monospace"><?= date('d.m.Y', strtotime((string) $wh['date'])) ?></td>
                                                <td><?= htmlspecialchars($catLabels[$cat] ?? $cat, ENT_QUOTES, 'UTF-8') ?></td>
                                                <td class="text-muted"><?= substr((string) $wh['start_time'], 0, 5) ?>–<?= substr((string) $wh['end_time'], 0, 5) ?></td>
                                                <td class="fw-bold tabular-nums"><?= number_format((float) $wh['total_hours'], 2) ?> h</td>
                                                <td class="text-end tabular-nums">CHF <?= number_format((float) $wh['rate_amount'], 2) ?></td>
                                                <td class="text-end fw-bold text-success tabular-nums">CHF <?= number_format((float) $wh['total_amount'], 2, '.', "'") ?></td>
                                                <td><span class="badge bg-<?= $stBadge ?>-subtle text-<?= $stBadge ?> rounded-pill"><?= htmlspecialchars(ucfirst($st), ENT_QUOTES, 'UTF-8') ?></span></td>
                                                <td class="pe-3 text-muted"><?= htmlspecialchars((string) ($wh['note'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ============================================================== -->
                <!-- SECTION 5: COACH-PROFIL, HR-DATEN & RECHTLICHES                -->
                <!-- ============================================================== -->
                <div id="coachSectionHr" class="coach-section" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="fw-bold text-dark mb-0">Coach-Profil, HR-Daten &amp; Rechtliches</h4>
                        <button type="button" class="btn btn-sm btn-outline-dark rounded-3 fw-semibold" onclick="switchCoachSection('overview')">
                            &larr; Zurück zum Dashboard
                        </button>
                    </div>

                    <div class="row g-4">
                        <div class="col-12 col-md-6">
                            <div class="card border rounded-4 p-4 h-100 shadow-xs" style="background-color: #f6f0e7; border: 1.5px solid #e3d6c5;">
                                <h6 class="fw-bold text-dark mb-3">Coach-Stammdaten &amp; Qualifikation</h6>
                                <div class="d-flex flex-column gap-2.5 small">
                                    <div class="p-2.5 bg-white rounded-3 border d-flex justify-content-between" style="border-color: #dfd0bc !important;">
                                        <span class="text-muted">Name</span>
                                        <strong class="text-dark"><?= htmlspecialchars($coachFullName, ENT_QUOTES, 'UTF-8') ?></strong>
                                    </div>
                                    <div class="p-2.5 bg-white rounded-3 border d-flex justify-content-between" style="border-color: #dfd0bc !important;">
                                        <span class="text-muted">E-Mail</span>
                                        <strong class="text-dark"><?= htmlspecialchars((string) ($instructor['email'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong>
                                    </div>
                                    <div class="p-2.5 bg-white rounded-3 border d-flex justify-content-between" style="border-color: #dfd0bc !important;">
                                        <span class="text-muted">Telefon</span>
                                        <strong class="text-dark"><?= htmlspecialchars((string) ($instructor['phone'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong>
                                    </div>
                                    <div class="p-2.5 bg-white rounded-3 border d-flex justify-content-between" style="border-color: #dfd0bc !important;">
                                        <span class="text-muted">Fahrlehrer-Lizenz</span>
                                        <strong class="text-dark font-monospace"><?= htmlspecialchars((string) ($instructor['license_number'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong>
                                    </div>
                                    <div class="p-2.5 bg-white rounded-3 border d-flex justify-content-between" style="border-color: #dfd0bc !important;">
                                        <span class="text-muted">Lohn-Code</span>
                                        <strong class="text-dark font-monospace"><?= htmlspecialchars((string) ($instructor['payroll_external_code'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="card border rounded-4 p-4 h-100 shadow-xs" style="background-color: #f6f0e7; border: 1.5px solid #e3d6c5;">
                                <h6 class="fw-bold text-dark mb-3">Schweizer Vorgaben im Ausbildungsbetrieb</h6>
                                <div class="d-flex flex-column gap-2.5 small">
                                    <div class="p-3 bg-white rounded-3 border" style="border-color: #dfd0bc !important;">
                                        <strong class="text-danger d-block mb-1">BSV Nulltoleranz (0.00 ‰ · Art. 78a BSV)</strong>
                                        <span class="text-muted">Für Coach und Fahrschüler gilt strikt 0.00 Promille Alkohol am Steuerstand.</span>
                                    </div>
                                    <div class="p-3 bg-white rounded-3 border" style="border-color: #dfd0bc !important;">
                                        <strong class="text-dark d-block mb-1">MWST &amp; Abrechnung</strong>
                                        <span class="text-muted">Coaching Boot GmbH · CHE-110.497.163 MWST (Fahrschule 0.0% · Miete 8.1%).</span>
                                    </div>
                                    <div class="p-3 bg-white rounded-3 border" style="border-color: #dfd0bc !important;">
                                        <strong class="text-dark d-block mb-1">Notfall &amp; Seepolizei Luzern</strong>
                                        <span class="text-muted">Einsatzleitzentrale: <a href="tel:+41412488117" class="fw-bold text-danger text-decoration-none">+41 41 248 81 17</a> · Notruf 117 / 144</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL: LEKTION ABSCHLIESSEN & NOTIZ ERFASSEN                   -->
    <!-- ============================================================== -->
    <div class="modal fade" id="modalCompleteLesson" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden" style="background-color: #f6f0e7;">
                <form method="post" action="?route=admin/boating/instructor-portal/complete-appointment">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="appointment_id" id="completeLessonAppId" value="">
                    <input type="hidden" name="instructor_id" value="<?= (int) $instructor['id'] ?>">
                    <input type="hidden" name="month" value="<?= htmlspecialchars($selectedMonth, ENT_QUOTES, 'UTF-8') ?>">

                    <div class="modal-header border-bottom px-4 py-3" style="background-color: #2d2421; color: #ffffff;">
                        <h5 class="modal-title fw-bold mb-0"><i class="bi bi-check2-circle text-success me-2"></i>Lektion abschliessen</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schließen"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="p-3 bg-white rounded-3 border mb-3" style="border-color: #dfd0bc !important;">
                            <div class="small text-muted">Schüler / Einsatz:</div>
                            <div class="fw-bold text-dark" id="completeLessonStudentLabel">-</div>
                            <div class="small text-muted" id="completeLessonServiceLabel">-</div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold text-dark">Ausbildungs- / Manövernotiz (optional)</label>
                            <textarea name="coach_note" rows="3" class="form-control bg-white rounded-3" style="border: 1.5px solid #d5c5b2;" placeholder="z. B. Hafenmanöver rückwärts sicher, nächstes Mal Boje über Bord und Seitenwind üben..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer px-4 py-3" style="background-color: #eee4d5; border-top: 1px solid #dfd0bc;">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-3 px-3" data-bs-dismiss="modal">Abbrechen</button>
                        <button type="submit" class="btn btn-sm text-white fw-bold rounded-3 px-4" style="background-color: #065f46;">
                            Als durchgeführt speichern &rarr;
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL: SEEWETTER & STURMWARNUNG                                -->
    <!-- ============================================================== -->
    <div class="modal fade" id="coachWeatherModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden" style="background-color: #f6f0e7;">
                <div class="modal-header border-bottom px-4 py-3" style="background-color: #2d2421; color: #ffffff;">
                    <h5 class="modal-title fw-bold mb-0"><i class="bi bi-cloud-sun text-warning me-2"></i>Seewetter &amp; Sturmwarnung Vierwaldstättersee</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="d-flex gap-2 mb-3">
                        <button type="button" class="btn btn-sm fw-bold rounded-3 px-3 bg-dark text-white" id="coachBtnLuzern" onclick="switchCoachLakeStation('luzern')">Luzern Alpenquai</button>
                        <button type="button" class="btn btn-sm fw-bold rounded-3 px-3 bg-white text-dark border" id="coachBtnHergiswil" onclick="switchCoachLakeStation('hergiswil')">Hergiswil / Buochs</button>
                    </div>
                    <div class="p-3.5 rounded-3 bg-white border mb-3" style="border-color: #dfd0bc !important;">
                        <div class="row g-3 small">
                            <div class="col-4">
                                <span class="text-muted d-block">Luft / See</span>
                                <strong class="fs-5 text-dark" id="coachWTemp">7°C / 11.4°C</strong>
                            </div>
                            <div class="col-4">
                                <span class="text-muted d-block">Wind / Böen</span>
                                <strong class="fs-5 text-dark" id="coachWWind">7.7 km/h NO</strong>
                            </div>
                            <div class="col-4">
                                <span class="text-muted d-block">Sturmwarnleuchten</span>
                                <strong class="fs-6 text-success">Aus (0 Blitze)</strong>
                            </div>
                        </div>
                    </div>
                    <div class="p-3 rounded-3 bg-white border small mb-3" style="border-color: #dfd0bc !important;">
                        <strong>BSV-Sturmwarnung:</strong> 40 Blitze/Min = Starkwindwarnung (Vorsicht) · 90 Blitze/Min = Sturmwarnung (Ausbildungsbetrieb unterbrechen, Schutzhafen anlaufen).
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a id="coachWPortalLink" href="https://search.ch/meteo/luzern" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-dark rounded-3 fw-bold">
                            search.ch Meteo öffnen <i class="bi bi-box-arrow-up-right ms-1"></i>
                        </a>
                        <a href="https://www.windy.com/47.050/8.309?47.015,8.330,11" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-dark rounded-3 fw-bold">
                            Windy Live-Radar <i class="bi bi-box-arrow-up-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL: BOOTSSCHADEN / FEHLER MELDEN (COACH SUPPORT)            -->
    <!-- ============================================================== -->
    <div class="modal fade" id="coachReportIssueModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden" style="background-color: #f6f0e7;">
                <div class="modal-header border-bottom px-4 py-3" style="background-color: #2d2421; color: #ffffff;">
                    <h5 class="modal-title fw-bold mb-0"><i class="bi bi-exclamation-octagon text-danger me-2"></i>Vorfall, Bootsschaden oder Fehler melden</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>
                <div id="coachIssueFormWrap">
                    <form onsubmit="submitCoachIssue(event)">
                        <div class="modal-body p-4">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">Meldungsart *</label>
                                    <select class="form-select bg-white rounded-3" style="border: 1.5px solid #d5c5b2;" required>
                                        <option value="boat_damage">Bootsschaden / technischer Mangel am Schiff</option>
                                        <option value="fuel_service">Treibstoff / Service / Reinigung erforderlich</option>
                                        <option value="payroll_data">Datenkorrektur (Stunden, Honorar, Schülerdaten)</option>
                                        <option value="tech_issue">Technisches Portal-Problem</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">Dringlichkeit *</label>
                                    <select class="form-select bg-white rounded-3" id="coachIssueUrgency" style="border: 1.5px solid #d5c5b2;">
                                        <option value="Normal">Normal (Backoffice prüft heute)</option>
                                        <option value="Dringend">Dringend (Nächste Ausfahrt betroffen!)</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold text-dark">Beschreibung *</label>
                                    <textarea class="form-control bg-white rounded-3" rows="4" style="border: 1.5px solid #d5c5b2;" placeholder="z. B. Boesch 620 leichte Scheuerstelle Steuerbord oder fehlende Stunden in Timesheet..." required></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer px-4 py-3" style="background-color: #eee4d5; border-top: 1px solid #dfd0bc;">
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-3 px-3" data-bs-dismiss="modal">Abbrechen</button>
                            <button type="submit" class="btn btn-sm text-white fw-bold rounded-3 px-4" style="background-color: #be123c;">Meldung absenden &rarr;</button>
                        </div>
                    </form>
                </div>
                <div id="coachIssueSuccessWrap" class="p-4 text-center" style="display: none;">
                    <div class="p-4 bg-white rounded-4 border" style="border-color: #a7f3d0 !important;">
                        <i class="bi bi-check2-circle fs-1 text-success d-block mb-2"></i>
                        <h6 class="fw-bold text-dark">Meldung ans Backoffice übermittelt!</h6>
                        <p class="small text-muted mb-3">Ticket-Referenz: <strong class="font-monospace text-danger" id="coachIssueCode">#COACH-2026-1042</strong></p>
                        <button type="button" class="btn btn-sm btn-dark rounded-3 px-4 fw-bold" data-bs-dismiss="modal" onclick="resetCoachIssueForm()">Schließen</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Absenz erfassen -->
    <div class="modal fade" id="modalAddAbsence" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content rounded-4 border-0 shadow">
                <form method="post" action="?route=admin/boating/instructor-portal/add-availability">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="instructor_id" value="<?= (int) $instructor['id'] ?>">
                    <input type="hidden" name="month" value="<?= htmlspecialchars($selectedMonth, ENT_QUOTES, 'UTF-8') ?>">

                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold"><i class="bi bi-calendar-x text-warning me-2"></i>Absenz / Sperrzeit erfassen</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Datum *</label>
                            <input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Art der Absenz *</label>
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
                                        <?= htmlspecialchars((string) $loc['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="p-3 bg-light rounded-3 border mb-3">
                            <label class="form-label small fw-semibold text-dark mb-2">Sperrzeit-Fenster (von – bis) *</label>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <input type="time" name="start_time" id="absStartTime" class="form-control" value="08:00" required>
                                </div>
                                <div class="col-6">
                                    <input type="time" name="end_time" id="absEndTime" class="form-control" value="12:00" required>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-1 mt-1">
                                <button type="button" class="btn btn-sm bg-white border py-0 px-1 text-muted" style="font-size: 10px;" onclick="document.getElementById('absStartTime').value='08:00';document.getElementById('absEndTime').value='12:00';">Vormittag</button>
                                <button type="button" class="btn btn-sm bg-white border py-0 px-1 text-muted" style="font-size: 10px;" onclick="document.getElementById('absStartTime').value='13:00';document.getElementById('absEndTime').value='17:00';">Nachmittag</button>
                                <button type="button" class="btn btn-sm bg-white border py-0 px-1 text-muted" style="font-size: 10px;" onclick="document.getElementById('absStartTime').value='08:00';document.getElementById('absEndTime').value='18:00';">Ganzer Tag</button>
                            </div>
                        </div>
                        <div class="mb-2">
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
    <div class="modal fade" id="modalAddTemplate" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content rounded-4 border-0 shadow">
                <form method="post" action="?route=admin/boating/instructor-portal/weekly-templates/save">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="instructor_id" value="<?= (int) $instructor['id'] ?>">
                    <input type="hidden" name="month" value="<?= htmlspecialchars($selectedMonth, ENT_QUOTES, 'UTF-8') ?>">

                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold"><i class="bi bi-calendar-week text-success me-2"></i>Wochen-Arbeitsfenster anlegen</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Wochentag *</label>
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
                                <label class="form-label small fw-semibold">Startzeit *</label>
                                <input type="time" name="start_time" class="form-control" value="08:00" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Endzeit *</label>
                                <input type="time" name="end_time" class="form-control" value="18:00" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Hafen / Standort</label>
                            <select name="location_id" class="form-select">
                                <option value="">Alle Häfen</option>
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?= (int) $loc['id'] ?>"><?= htmlspecialchars((string) $loc['name'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-check form-switch">
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
    <div class="modal fade" id="modalAddTimesheet" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content rounded-4 border-0 shadow">
                <form method="post" action="?route=admin/boating/instructor-portal/add-timesheet">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="instructor_id" value="<?= (int) $instructor['id'] ?>">
                    <input type="hidden" name="month" value="<?= htmlspecialchars($selectedMonth, ENT_QUOTES, 'UTF-8') ?>">

                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold"><i class="bi bi-stopwatch text-info me-2"></i>Arbeitszeit / Timesheet erfassen</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Betreff / Tätigkeit *</label>
                            <input type="text" name="subject" id="portalTimesheetSubject" class="form-control" placeholder="z. B. Bootswartung Boesch 620, Prüfung, Theorie..." required>
                            <div class="d-flex flex-wrap gap-1 mt-1">
                                <button type="button" class="btn btn-sm btn-light border py-0 px-1 text-muted" style="font-size: 10px;" onclick="document.getElementById('portalTimesheetSubject').value='Bootswartung & Tanken';">+ Wartung &amp; Tanken</button>
                                <button type="button" class="btn btn-sm btn-light border py-0 px-1 text-muted" style="font-size: 10px;" onclick="document.getElementById('portalTimesheetSubject').value='Führerprüfung Begleitung';">+ Prüfung</button>
                                <button type="button" class="btn btn-sm btn-light border py-0 px-1 text-muted" style="font-size: 10px;" onclick="document.getElementById('portalTimesheetSubject').value='Theorieunterricht Kat. A';">+ Theorie</button>
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Datum *</label>
                                <input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Kategorie *</label>
                                <select name="category" class="form-select" required>
                                    <option value="maintenance">Bootswartung &amp; Tanken</option>
                                    <option value="lesson">Fahrstunde (manuell)</option>
                                    <option value="exam">Führerprüfung</option>
                                    <option value="admin">Administration / Planung</option>
                                    <option value="other">Sonstiges</option>
                                </select>
                            </div>
                        </div>
                        <div class="p-3 bg-light rounded-3 border mb-3">
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="small text-muted mb-1">Stunden (h)</label>
                                    <input type="number" id="tsHours" name="hours" min="0" max="24" step="1" class="form-control form-control-sm fw-bold" value="2" oninput="recalcTs()">
                                </div>
                                <div class="col-6">
                                    <label class="small text-muted mb-1">Minuten (min)</label>
                                    <input type="number" id="tsMinutes" name="minutes" min="0" max="59" step="5" class="form-control form-control-sm fw-bold" value="0" oninput="recalcTs()">
                                </div>
                            </div>
                            <div class="row g-2 pt-2 border-top">
                                <div class="col-4">
                                    <label class="form-label small text-muted mb-0" style="font-size: 11px;">Start</label>
                                    <input type="time" id="tsStart" name="start_time" class="form-control form-control-sm" value="09:00" oninput="recalcTs()" required>
                                </div>
                                <div class="col-4">
                                    <label class="form-label small text-muted mb-0" style="font-size: 11px;">Pause</label>
                                    <input type="number" id="tsBreak" min="0" step="5" name="break_minutes" class="form-control form-control-sm" value="0" oninput="recalcTs()">
                                </div>
                                <div class="col-4">
                                    <label class="form-label small text-muted mb-0" style="font-size: 11px;">Ende</label>
                                    <input type="time" id="tsEnd" name="end_time" class="form-control form-control-sm bg-light" value="11:00" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Stundensatz (Admin-Einstellung)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">CHF</span>
                                <input type="text" class="form-control bg-light fw-bold" value="<?= htmlspecialchars((string) (isset($instructor['pay_rate_amount']) ? number_format((float) $instructor['pay_rate_amount'], 2, '.', '') : '65.00'), ENT_QUOTES, 'UTF-8') ?>" readonly>
                            </div>
                            <input type="hidden" name="rate_amount" value="<?= htmlspecialchars((string) (isset($instructor['pay_rate_amount']) ? number_format((float) $instructor['pay_rate_amount'], 2, '.', '') : '65.00'), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Notiz (optional)</label>
                            <textarea name="note" rows="2" class="form-control"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                        <button type="submit" class="btn btn-sm btn-primary fw-semibold px-4">Zeit erfassen</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function switchCoachSection(section) {
        const map = {
            overview:     { sec: 'coachSectionOverview',     nav: 'coachNavOverview' },
            lessons:      { sec: 'coachSectionLessons',      nav: 'coachNavLessons' },
            availability: { sec: 'coachSectionAvailability', nav: 'coachNavAvailability' },
            timesheet:    { sec: 'coachSectionTimesheet',    nav: 'coachNavTimesheet' },
            hr:           { sec: 'coachSectionHr',           nav: 'coachNavHr' }
        };
        document.querySelectorAll('.coach-section').forEach(function(el) { el.style.display = 'none'; });
        document.querySelectorAll('.sidebar-nav-link').forEach(function(el) { el.classList.remove('active'); });
        const target = map[section] || map.overview;
        const secEl = document.getElementById(target.sec);
        const navEl = document.getElementById(target.nav);
        if (secEl) secEl.style.display = 'block';
        if (navEl) navEl.classList.add('active');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function openCompleteLessonModal(appId, studentName, serviceName) {
        document.getElementById('completeLessonAppId').value = appId;
        document.getElementById('completeLessonStudentLabel').textContent = studentName || 'Fahrschüler';
        document.getElementById('completeLessonServiceLabel').textContent = serviceName || 'Fahrstunde';
        if (typeof bootstrap !== 'undefined') {
            new bootstrap.Modal(document.getElementById('modalCompleteLesson')).show();
        }
    }

    function switchCoachLakeStation(station) {
        const bLuz = document.getElementById('coachBtnLuzern');
        const bHer = document.getElementById('coachBtnHergiswil');
        const windEl = document.getElementById('coachWWind');
        const linkEl = document.getElementById('coachWPortalLink');
        if (station === 'hergiswil') {
            bLuz.className = 'btn btn-sm fw-bold rounded-3 px-3 bg-white text-dark border';
            bHer.className = 'btn btn-sm fw-bold rounded-3 px-3 bg-dark text-white';
            if (windEl) windEl.textContent = '8.9 km/h NNW';
            if (linkEl) linkEl.href = 'https://search.ch/meteo/hergiswil';
        } else {
            bLuz.className = 'btn btn-sm fw-bold rounded-3 px-3 bg-dark text-white';
            bHer.className = 'btn btn-sm fw-bold rounded-3 px-3 bg-white text-dark border';
            if (windEl) windEl.textContent = '7.7 km/h NO';
            if (linkEl) linkEl.href = 'https://search.ch/meteo/luzern';
        }
    }

    function copyCoachCalendarUrl(btn) {
        const el = document.getElementById('coachCalendarUrl');
        if (!el) return;
        const txt = el.textContent.trim();
        if (navigator.clipboard) {
            navigator.clipboard.writeText(txt).then(function() {
                const orig = btn.innerHTML;
                btn.innerHTML = '<i class="bi bi-check2 me-1"></i> Kopiert!';
                setTimeout(function() { btn.innerHTML = orig; }, 2000);
            });
        }
    }

    function submitCoachIssue(e) {
        e.preventDefault();
        const code = '#COACH-2026-' + Math.floor(1000 + Math.random() * 9000);
        document.getElementById('coachIssueCode').textContent = code;
        document.getElementById('coachIssueFormWrap').style.display = 'none';
        document.getElementById('coachIssueSuccessWrap').style.display = 'block';
    }

    function resetCoachIssueForm() {
        document.getElementById('coachIssueFormWrap').style.display = 'block';
        document.getElementById('coachIssueSuccessWrap').style.display = 'none';
    }

    function recalcTs() {
        const h = parseInt(document.getElementById('tsHours').value, 10) || 0;
        const m = parseInt(document.getElementById('tsMinutes').value, 10) || 0;
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

<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../../../views/layouts/boating_portal.php';
