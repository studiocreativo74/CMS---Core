<?php

declare(strict_types=1);

/**
 * Bootsfahrschule - Kalender & Termin-Disposition (/admin/boating/calendar)
 *
 * Unterstützt:
 * - Wochenansicht (Grid mit Tagen & Zeitfenstern)
 * - Tages- & Ressourcenansicht (Spalten pro Fahrlehrer / Boot)
 * - Listen- / Agendaansicht
 * - Drag&Drop (HTML5) mit nativer Konfliktprüfung & Ladeindikator
 * - Kopiermodus (Termin duplizieren statt verschieben per Toggle)
 * - Bootstrap 5 Toasts für Erfolgs- und Konfliktmeldungen
 *
 * @var array<int, array<string, mixed>> $appointments
 * @var array<int, array<string, mixed>> $instructors
 * @var array<int, array<string, mixed>> $boats
 * @var array<int, array<string, mixed>> $services
 * @var array<int, array<string, mixed>> $customers
 * @var array<int, array<string, mixed>> $locations
 * @var array<string, mixed> $filters
 */

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$title = 'Kalender & Termin-Disposition - Bootsfahrschule';
$currentRoute = 'admin/boating/calendar';
$canManage = BoatingSchoolService::canManageCalendar();
$csrfToken = class_exists('Csrf') ? Csrf::getToken() : '';

$statusBadges = AppointmentService::STATUS_BADGES;
$statusLabels = AppointmentService::STATUS_LABELS;

// Ansichtsmodus und Datum ermitteln
$viewMode = !empty($_GET['view']) && in_array($_GET['view'], ['week', 'day_resources', 'list'], true)
    ? (string) $_GET['view']
    : 'week';

$selectedDate = !empty($_GET['date']) ? trim((string) $_GET['date']) : date('Y-m-d');
$selectedTimestamp = strtotime($selectedDate) ?: time();

// Wochenberechnung (Montag bis Sonntag)
$mondayTs = strtotime('monday this week', $selectedTimestamp);
$sundayTs = strtotime('sunday this week', $selectedTimestamp);
$mondayDate = date('Y-m-d', $mondayTs);
$sundayDate = date('Y-m-d', $sundayTs);

$prevWeekDate = date('Y-m-d', strtotime('-1 week', $selectedTimestamp));
$nextWeekDate = date('Y-m-d', strtotime('+1 week', $selectedTimestamp));

$prevDayDate = date('Y-m-d', strtotime('-1 day', $selectedTimestamp));
$nextDayDate = date('Y-m-d', strtotime('+1 day', $selectedTimestamp));

// Tage der Woche generieren
$weekDays = [];
for ($i = 0; $i < 7; $i++) {
    $ts = strtotime("+{$i} days", $mondayTs);
    $weekDays[] = [
        'date'       => date('Y-m-d', $ts),
        'day_name'   => ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'][$i],
        'day_full'   => ['Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag', 'Sonntag'][$i],
        'formatted'  => date('d.m.', $ts),
        'is_today'   => (date('Y-m-d', $ts) === date('Y-m-d')),
        'is_weekend' => ($i >= 5),
    ];
}

// Zeitraster für den Tag (Fahrstunden-Blöcke)
$timeSlots = [
    ['start' => '08:00', 'end' => '09:30', 'label' => '08:00 - 09:30'],
    ['start' => '09:30', 'end' => '11:00', 'label' => '09:30 - 11:00'],
    ['start' => '11:00', 'end' => '12:30', 'label' => '11:00 - 12:30'],
    ['start' => '12:30', 'end' => '14:00', 'label' => '12:30 - 14:00'],
    ['start' => '14:00', 'end' => '15:30', 'label' => '14:00 - 15:30'],
    ['start' => '15:30', 'end' => '17:00', 'label' => '15:30 - 17:00'],
    ['start' => '17:00', 'end' => '18:30', 'label' => '17:00 - 18:30'],
    ['start' => '18:30', 'end' => '20:00', 'label' => '18:30 - 20:00'],
];

// Termine nach Datum und Slot indizieren
$appointmentsByDateAndSlot = [];
$appointmentsByInstructorAndSlot = [];

foreach ($appointments as $app) {
    $appDate  = (string) $app['date'];
    $appStart = substr((string) $app['start_time'], 0, 5);
    $instId   = !empty($app['instructor_id']) ? (int) $app['instructor_id'] : 0;

    // Finde am besten passenden Slot
    $matchedSlotIndex = null;
    foreach ($timeSlots as $idx => $slot) {
        if ($appStart >= $slot['start'] && $appStart < $slot['end']) {
            $matchedSlotIndex = $idx;
            break;
        }
    }
    if ($matchedSlotIndex === null) {
        // Fallback: erster Slot wenn davor, letzter wenn danach
        $matchedSlotIndex = ($appStart < $timeSlots[0]['start']) ? 0 : (count($timeSlots) - 1);
    }

    $slotKey = "{$appDate}_{$matchedSlotIndex}";
    if (!isset($appointmentsByDateAndSlot[$slotKey])) {
        $appointmentsByDateAndSlot[$slotKey] = [];
    }
    $appointmentsByDateAndSlot[$slotKey][] = $app;

    // Für Ressourcen-Ansicht
    $instKey = "{$instId}_{$matchedSlotIndex}";
    if (!isset($appointmentsByInstructorAndSlot[$instKey])) {
        $appointmentsByInstructorAndSlot[$instKey] = [];
    }
    $appointmentsByInstructorAndSlot[$instKey][] = $app;
}

ob_start();
?>

<style>
/* Kalender Styling & Drag and Drop */
.calendar-card {
    transition: transform 0.15s ease, box-shadow 0.15s ease, opacity 0.15s ease;
}
.calendar-card[draggable="true"] {
    cursor: grab;
}
.calendar-card[draggable="true"]:active {
    cursor: grabbing;
}
.calendar-card.is-dragging {
    opacity: 0.45;
    transform: scale(0.96);
    border: 2px dashed #0d6efd !important;
}

/* Kopiermodus Styling */
.calendar-copy-mode .calendar-card[draggable="true"] {
    cursor: copy !important;
}
.calendar-copy-mode .calendar-drop-zone.drop-target-active {
    background-color: rgba(255, 193, 7, 0.22) !important;
    border-color: #ffc107 !important;
}

/* Drop-Zonen Feedback */
.calendar-drop-zone {
    min-height: 96px;
    transition: background-color 0.2s ease, border-color 0.2s ease;
    background-color: #ffffff;
}
.calendar-drop-zone:hover {
    background-color: #f8faff;
}
.calendar-drop-zone.drop-target-active {
    background-color: rgba(13, 110, 253, 0.12) !important;
    border: 2px dashed #0d6efd !important;
}
.calendar-drop-zone.drop-target-conflict {
    background-color: rgba(220, 53, 69, 0.12) !important;
    border: 2px dashed #dc3545 !important;
}

/* Ladeindikator Overlay */
#calendarLoadingOverlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.78);
    backdrop-filter: blur(2px);
    z-index: 1050;
    display: none;
    align-items: center;
    justify-content: center;
    border-radius: 0.5rem;
}
</style>

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
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="?route=admin/boating">Bootsfahrschule</a></li>
                <li class="breadcrumb-item active" aria-current="page">Kalender &amp; Disposition</li>
            </ol>
        </nav>
        <h1 class="h3 fw-bold text-dark mb-0">
            <i class="bi bi-calendar3 text-primary me-2"></i>Kalender &amp; Termin-Disposition
        </h1>
    </div>
    <div class="d-flex gap-2">
        <a href="?route=bootschule" target="_blank" class="btn btn-outline-info btn-sm">
            <i class="bi bi-box-arrow-up-right me-1"></i> Kundenportal öffnen
        </a>
        <?php if ($canManage): ?>
            <button type="button" class="btn btn-primary btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#createAppointmentModal">
                <i class="bi bi-plus-lg me-1"></i> Neuer Termin
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Toolbar: Ansichtswechsel, Datumsnavigation & Kopiermodus-Toggle -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">

            <!-- 1. Ansichts-Tabs (Woche / Ressourcen / Liste) -->
            <div class="btn-group btn-group-sm" role="group" aria-label="Kalender-Ansichten">
                <a href="?route=admin/boating/calendar&view=week&date=<?= urlencode($selectedDate) ?>"
                   class="btn <?= $viewMode === 'week' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                    <i class="bi bi-calendar-week me-1"></i> Wochenansicht
                </a>
                <a href="?route=admin/boating/calendar&view=day_resources&date=<?= urlencode($selectedDate) ?>"
                   class="btn <?= $viewMode === 'day_resources' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                    <i class="bi bi-person-lines-fill me-1"></i> Fahrlehrer / Ressourcen
                </a>
                <a href="?route=admin/boating/calendar&view=list&date=<?= urlencode($selectedDate) ?>"
                   class="btn <?= $viewMode === 'list' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                    <i class="bi bi-list-ul me-1"></i> Listenansicht
                </a>
            </div>

            <!-- 2. Datums-Navigation -->
            <div class="d-flex align-items-center gap-2">
                <?php
                $prevUrl = '?route=admin/boating/calendar&view=' . $viewMode . '&date=' . ($viewMode === 'week' ? $prevWeekDate : $prevDayDate);
                $nextUrl = '?route=admin/boating/calendar&view=' . $viewMode . '&date=' . ($viewMode === 'week' ? $nextWeekDate : $nextDayDate);
                $todayUrl = '?route=admin/boating/calendar&view=' . $viewMode . '&date=' . date('Y-m-d');
                ?>
                <a href="<?= $prevUrl ?>" class="btn btn-outline-secondary btn-sm" title="Zurück">
                    <i class="bi bi-chevron-left"></i>
                </a>
                <a href="<?= $todayUrl ?>" class="btn btn-outline-secondary btn-sm px-2">
                    Heute
                </a>
                <a href="<?= $nextUrl ?>" class="btn btn-outline-secondary btn-sm" title="Weiter">
                    <i class="bi bi-chevron-right"></i>
                </a>

                <form method="get" class="d-flex align-items-center gap-1 mb-0 ms-1">
                    <input type="hidden" name="route" value="admin/boating/calendar">
                    <input type="hidden" name="view" value="<?= htmlspecialchars($viewMode) ?>">
                    <input type="date" name="date" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($selectedDate) ?>"
                           onchange="this.form.submit()">
                </form>

                <span class="badge bg-light text-dark border px-2 py-1 small fw-semibold">
                    <?php if ($viewMode === 'week'): ?>
                        KW <?= date('W', $mondayTs) ?>: <?= date('d.m.', $mondayTs) ?> - <?= date('d.m.Y', $sundayTs) ?>
                    <?php else: ?>
                        <?= date('d.m.Y', $selectedTimestamp) ?>
                    <?php endif; ?>
                </span>
            </div>

            <!-- 3. Kopiermodus-Toggle (Admin-Komfortfunktion) -->
            <?php if ($canManage): ?>
                <div class="d-flex align-items-center gap-2">
                    <div class="form-check form-switch d-inline-flex align-items-center gap-2 px-3 py-1 bg-light rounded-pill border">
                        <input class="form-check-input my-0" type="checkbox" id="copyModeSwitch" role="switch">
                        <label class="form-check-label small fw-bold text-dark user-select-none mb-0" for="copyModeSwitch">
                            <i class="bi bi-copy text-primary me-1"></i>Kopiermodus
                        </label>
                    </div>
                    <span id="copyModeBadge" class="badge bg-warning text-dark border border-warning shadow-sm d-none animate__animated animate__fadeIn">
                        <i class="bi bi-copy me-1"></i>Kopiermodus aktiv: Drag&amp;Drop erstellt Kopie (+)
                    </span>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- Filterleiste (ausklappbar oder kompakt) -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="get" class="row g-2 align-items-center">
            <input type="hidden" name="route" value="admin/boating/calendar">
            <input type="hidden" name="view" value="<?= htmlspecialchars($viewMode) ?>">
            <input type="hidden" name="date" value="<?= htmlspecialchars($selectedDate) ?>">

            <div class="col-md-3">
                <label class="form-label small text-muted mb-0">Status</label>
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Alle Status</option>
                    <?php foreach ($statusLabels as $sKey => $sLabel): ?>
                        <option value="<?= htmlspecialchars($sKey) ?>" <?= (($filters['status'] ?? '') === $sKey) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($sLabel) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label small text-muted mb-0">Fahrlehrer</label>
                <select name="instructor_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Alle Fahrlehrer</option>
                    <?php foreach ($instructors as $inst): ?>
                        <option value="<?= (int) $inst['id'] ?>" <?= ((int) ($filters['instructor_id'] ?? 0) === (int) $inst['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($inst['first_name'] . ' ' . $inst['last_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label small text-muted mb-0">Boot</label>
                <select name="boat_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Alle Boote</option>
                    <?php foreach ($boats as $b): ?>
                        <option value="<?= (int) $b['id'] ?>" <?= ((int) ($filters['boat_id'] ?? 0) === (int) $b['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string) $b['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3 d-flex gap-2 align-items-end pt-3">
                <button type="submit" class="btn btn-outline-primary btn-sm flex-grow-1">
                    <i class="bi bi-funnel me-1"></i> Anwenden
                </button>
                <a href="?route=admin/boating/calendar&view=<?= htmlspecialchars($viewMode) ?>&date=<?= htmlspecialchars($selectedDate) ?>"
                   class="btn btn-outline-secondary btn-sm" title="Filter zurücksetzen">
                    <i class="bi bi-x-circle"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- HAUPTBEREICH: KALENDER (WOCHE / RESSOURCEN / LISTE) -->
<!-- ========================================================================= -->
<div class="position-relative" id="calendarMainContainer">

    <!-- Lade-Overlay während Drag&Drop Ajax Request -->
    <div id="calendarLoadingOverlay">
        <div class="text-center p-3 bg-white rounded-3 shadow border">
            <div class="spinner-border text-primary mb-2" role="status">
                <span class="visually-hidden">Laden...</span>
            </div>
            <div class="small fw-bold text-dark" id="calendarLoadingText">Termin wird disponiert...</div>
            <div class="small text-muted">Konfliktprüfung für Boot &amp; Fahrlehrer läuft</div>
        </div>
    </div>

    <?php if ($viewMode === 'week'): ?>
        <!-- ================================================================= -->
        <!-- ANSICHT 1: WOCHENANSICHT (GRID) -->
        <!-- ================================================================= -->
        <div class="card border-0 shadow-sm overflow-hidden mb-4">
            <div class="table-responsive">
                <table class="table table-bordered mb-0 align-top calendar-week-table" style="table-layout: fixed; min-width: 980px;">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 110px;" class="text-center py-2 text-muted small">Zeit</th>
                            <?php foreach ($weekDays as $wDay): ?>
                                <th class="text-center py-2 <?= $wDay['is_today'] ? 'bg-primary-subtle text-primary border-primary fw-bold' : ($wDay['is_weekend'] ? 'bg-light text-muted' : '') ?>">
                                    <div class="small text-uppercase"><?= $wDay['day_full'] ?></div>
                                    <div class="fs-6"><?= $wDay['formatted'] ?></div>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($timeSlots as $slotIndex => $slot): ?>
                            <tr>
                                <td class="text-center align-middle bg-light py-2 text-muted small fw-semibold">
                                    <?= htmlspecialchars($slot['start']) ?><br>
                                    <span class="text-secondary small" style="font-size: 0.75rem;">- <?= htmlspecialchars($slot['end']) ?></span>
                                </td>
                                <?php foreach ($weekDays as $wDay): ?>
                                    <?php
                                    $slotDate = $wDay['date'];
                                    $cellKey = "{$slotDate}_{$slotIndex}";
                                    $cellAppointments = $appointmentsByDateAndSlot[$cellKey] ?? [];
                                    ?>
                                    <td class="p-1 calendar-drop-zone <?= $wDay['is_today'] ? 'bg-light-subtle' : '' ?>"
                                        data-date="<?= htmlspecialchars($slotDate) ?>"
                                        data-start="<?= htmlspecialchars($slot['start']) ?>"
                                        data-end="<?= htmlspecialchars($slot['end']) ?>">

                                        <?php foreach ($cellAppointments as $app): ?>
                                            <?php
                                            $instColor = !empty($app['instructor_color']) ? (string) $app['instructor_color'] : '#0d6efd';
                                            $bClass = $statusBadges[$app['status']] ?? 'secondary';
                                            $sLabel = $statusLabels[$app['status']] ?? $app['status'];
                                            ?>
                                            <div class="card calendar-card shadow-sm border-0 mb-1 p-2 position-relative"
                                                 style="border-left: 4px solid <?= htmlspecialchars($instColor) ?> !important; font-size: 0.82rem;"
                                                 <?= $canManage ? 'draggable="true"' : '' ?>
                                                 data-appointment-id="<?= (int) $app['id'] ?>"
                                                 data-date="<?= htmlspecialchars((string) $app['date']) ?>"
                                                 data-start="<?= htmlspecialchars(substr((string) $app['start_time'], 0, 5)) ?>"
                                                 data-end="<?= htmlspecialchars(substr((string) $app['end_time'], 0, 5)) ?>"
                                                 data-instructor-id="<?= !empty($app['instructor_id']) ? (int) $app['instructor_id'] : '' ?>"
                                                 data-boat-id="<?= !empty($app['boat_id']) ? (int) $app['boat_id'] : '' ?>"
                                                 data-location-id="<?= !empty($app['location_id']) ? (int) $app['location_id'] : '' ?>">

                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="badge bg-dark-subtle text-dark px-1 py-0 small fw-bold">
                                                        <?= htmlspecialchars(substr((string) $app['start_time'], 0, 5)) ?> - <?= htmlspecialchars(substr((string) $app['end_time'], 0, 5)) ?>
                                                    </span>
                                                    <span class="badge bg-<?= $bClass ?>-subtle text-<?= $bClass ?> px-1 py-0 small" style="font-size: 0.7rem;">
                                                        <?= htmlspecialchars($sLabel) ?>
                                                    </span>
                                                </div>

                                                <div class="fw-bold text-dark text-truncate" title="<?= htmlspecialchars((string) ($app['customer_first_name'] . ' ' . $app['customer_last_name'])) ?>">
                                                    <i class="bi bi-person me-1"></i><?= htmlspecialchars((string) ($app['customer_first_name'] . ' ' . $app['customer_last_name'])) ?>
                                                </div>

                                                <div class="text-truncate text-muted small" title="<?= htmlspecialchars((string) ($app['service_name'] ?? '')) ?>">
                                                    <?= htmlspecialchars((string) ($app['service_name'] ?? 'Fahrstunde')) ?>
                                                </div>

                                                <div class="d-flex flex-wrap gap-1 mt-1 small" style="font-size: 0.72rem;">
                                                    <?php if (!empty($app['instructor_first_name'])): ?>
                                                        <span class="badge" style="background-color: <?= htmlspecialchars($instColor) ?>; color: #fff;">
                                                            <?= htmlspecialchars((string) ($app['instructor_first_name'])) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($app['boat_name'])): ?>
                                                        <span class="badge bg-secondary-subtle text-secondary">
                                                            <i class="bi bi-life-preserver me-1"></i><?= htmlspecialchars((string) $app['boat_name']) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>

                                                <?php if ($canManage): ?>
                                                    <div class="d-flex justify-content-end gap-1 mt-1 border-top pt-1">
                                                        <?php if ($app['status'] === 'requested'): ?>
                                                            <form method="post" action="?route=admin/boating/appointments/status" class="d-inline">
                                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                                <input type="hidden" name="id" value="<?= (int) $app['id'] ?>">
                                                                <input type="hidden" name="status" value="confirmed">
                                                                <button type="submit" class="btn btn-outline-success btn-xs p-0 px-1" title="Bestätigen" style="font-size: 0.68rem;">
                                                                    <i class="bi bi-check"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        <form method="post" action="?route=admin/boating/appointments/delete" class="d-inline" onsubmit="return confirm('Termin wirklich löschen?');">
                                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                            <input type="hidden" name="id" value="<?= (int) $app['id'] ?>">
                                                            <button type="submit" class="btn btn-outline-danger btn-xs p-0 px-1" title="Löschen" style="font-size: 0.68rem;">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                <?php endif; ?>

                                            </div>
                                        <?php endforeach; ?>

                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php elseif ($viewMode === 'day_resources'): ?>
        <!-- ================================================================= -->
        <!-- ANSICHT 2: TAGES- & RESSOURCENANSICHT (PRO FAHRLEHRER) -->
        <!-- ================================================================= -->
        <div class="card border-0 shadow-sm overflow-hidden mb-4">
            <div class="card-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center">
                <span class="fw-bold small text-dark">
                    <i class="bi bi-person-badge text-primary me-2"></i>Ressourcen-Einteilung für <?= date('d.m.Y', $selectedTimestamp) ?>
                </span>
                <span class="small text-muted">Spalten = Fahrlehrer (Drop in Spalte teilt Fahrlehrer zu)</span>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered mb-0 align-top" style="table-layout: fixed; min-width: 900px;">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 110px;" class="text-center py-2 text-muted small">Zeit</th>
                            <th class="text-center py-2 bg-light text-muted small">
                                <div>Nicht zugeteilt</div>
                                <span class="badge bg-secondary-subtle text-secondary small">Offen</span>
                            </th>
                            <?php foreach ($instructors as $inst): ?>
                                <th class="text-center py-2">
                                    <div class="fw-bold text-dark">
                                        <?= htmlspecialchars($inst['first_name'] . ' ' . $inst['last_name']) ?>
                                    </div>
                                    <span class="badge" style="background-color: <?= htmlspecialchars((string) ($inst['color_code'] ?? '#0d6efd')) ?>; color: #fff; font-size: 0.7rem;">
                                        <?= htmlspecialchars((string) ($inst['title'] ?? 'Fahrcoach')) ?>
                                    </span>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($timeSlots as $slotIndex => $slot): ?>
                            <tr>
                                <td class="text-center align-middle bg-light py-2 text-muted small fw-semibold">
                                    <?= htmlspecialchars($slot['start']) ?><br>
                                    <span class="text-secondary small" style="font-size: 0.75rem;">- <?= htmlspecialchars($slot['end']) ?></span>
                                </td>

                                <!-- Spalte: Unzugeordnet (instructor_id = 0) -->
                                <?php
                                $unassignedKey = "0_{$slotIndex}";
                                $unassignedApps = $appointmentsByInstructorAndSlot[$unassignedKey] ?? [];
                                ?>
                                <td class="p-1 calendar-drop-zone"
                                    data-date="<?= htmlspecialchars($selectedDate) ?>"
                                    data-start="<?= htmlspecialchars($slot['start']) ?>"
                                    data-end="<?= htmlspecialchars($slot['end']) ?>"
                                    data-instructor-id="">
                                    <?php foreach ($unassignedApps as $app): ?>
                                        <?php if ((string) $app['date'] === $selectedDate): ?>
                                            <div class="card calendar-card shadow-sm border-0 mb-1 p-2"
                                                 style="border-left: 4px solid #6c757d !important; font-size: 0.82rem;"
                                                 <?= $canManage ? 'draggable="true"' : '' ?>
                                                 data-appointment-id="<?= (int) $app['id'] ?>"
                                                 data-date="<?= htmlspecialchars((string) $app['date']) ?>"
                                                 data-start="<?= htmlspecialchars(substr((string) $app['start_time'], 0, 5)) ?>"
                                                 data-end="<?= htmlspecialchars(substr((string) $app['end_time'], 0, 5)) ?>"
                                                 data-instructor-id=""
                                                 data-boat-id="<?= !empty($app['boat_id']) ? (int) $app['boat_id'] : '' ?>">
                                                <div class="fw-bold"><?= htmlspecialchars((string) ($app['customer_first_name'] . ' ' . $app['customer_last_name'])) ?></div>
                                                <div class="text-muted small"><?= htmlspecialchars((string) ($app['service_name'] ?? '')) ?></div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </td>

                                <!-- Spalten pro Fahrlehrer -->
                                <?php foreach ($instructors as $inst): ?>
                                    <?php
                                    $instId = (int) $inst['id'];
                                    $cellKey = "{$instId}_{$slotIndex}";
                                    $instApps = $appointmentsByInstructorAndSlot[$cellKey] ?? [];
                                    $instColor = !empty($inst['color_code']) ? (string) $inst['color_code'] : '#0d6efd';
                                    ?>
                                    <td class="p-1 calendar-drop-zone"
                                        data-date="<?= htmlspecialchars($selectedDate) ?>"
                                        data-start="<?= htmlspecialchars($slot['start']) ?>"
                                        data-end="<?= htmlspecialchars($slot['end']) ?>"
                                        data-instructor-id="<?= $instId ?>">

                                        <?php foreach ($instApps as $app): ?>
                                            <?php if ((string) $app['date'] === $selectedDate): ?>
                                                <div class="card calendar-card shadow-sm border-0 mb-1 p-2"
                                                     style="border-left: 4px solid <?= htmlspecialchars($instColor) ?> !important; font-size: 0.82rem;"
                                                     <?= $canManage ? 'draggable="true"' : '' ?>
                                                     data-appointment-id="<?= (int) $app['id'] ?>"
                                                     data-date="<?= htmlspecialchars((string) $app['date']) ?>"
                                                     data-start="<?= htmlspecialchars(substr((string) $app['start_time'], 0, 5)) ?>"
                                                     data-end="<?= htmlspecialchars(substr((string) $app['end_time'], 0, 5)) ?>"
                                                     data-instructor-id="<?= $instId ?>"
                                                     data-boat-id="<?= !empty($app['boat_id']) ? (int) $app['boat_id'] : '' ?>">

                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <span class="badge bg-dark-subtle text-dark px-1 py-0 small fw-bold">
                                                            <?= htmlspecialchars(substr((string) $app['start_time'], 0, 5)) ?>
                                                        </span>
                                                        <span class="badge bg-<?= $statusBadges[$app['status']] ?? 'secondary' ?>-subtle text-<?= $statusBadges[$app['status']] ?? 'secondary' ?> px-1 py-0 small">
                                                            <?= htmlspecialchars($statusLabels[$app['status']] ?? $app['status']) ?>
                                                        </span>
                                                    </div>

                                                    <div class="fw-bold text-dark text-truncate">
                                                        <?= htmlspecialchars((string) ($app['customer_first_name'] . ' ' . $app['customer_last_name'])) ?>
                                                    </div>
                                                    <div class="small text-muted text-truncate"><?= htmlspecialchars((string) ($app['service_name'] ?? '')) ?></div>

                                                    <?php if (!empty($app['boat_name'])): ?>
                                                        <div class="small text-secondary mt-1">
                                                            <i class="bi bi-life-preserver me-1"></i><?= htmlspecialchars((string) $app['boat_name']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>

                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php else: ?>
        <!-- ================================================================= -->
        <!-- ANSICHT 3: LISTENANSICHT (TABELLE) -->
        <!-- ================================================================= -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-0">
                <?php if (empty($appointments)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-calendar-x fs-1 d-block mb-2 text-secondary"></i>
                        Keine Termine für die gewählten Filterkriterien gefunden.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Datum &amp; Uhrzeit</th>
                                    <th>Service / Leistung</th>
                                    <th>Kunde / Schüler</th>
                                    <th>Fahrlehrer</th>
                                    <th>Boot / Steg</th>
                                    <th>Status</th>
                                    <th>Tarif</th>
                                    <th class="text-end pe-4">Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointments as $app): ?>
                                    <?php
                                    $badgeClass = $statusBadges[$app['status']] ?? 'secondary';
                                    $statusText = $statusLabels[$app['status']] ?? $app['status'];
                                    ?>
                                    <tr class="calendar-table-row"
                                        <?= $canManage ? 'draggable="true"' : '' ?>
                                        data-appointment-id="<?= (int) $app['id'] ?>"
                                        data-date="<?= htmlspecialchars((string) $app['date']) ?>"
                                        data-start="<?= htmlspecialchars(substr((string) $app['start_time'], 0, 5)) ?>"
                                        data-end="<?= htmlspecialchars(substr((string) $app['end_time'], 0, 5)) ?>">
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark">
                                                <?= date('d.m.Y', strtotime((string) $app['date'])) ?>
                                            </div>
                                            <div class="small text-muted">
                                                <?= htmlspecialchars(substr((string) $app['start_time'], 0, 5)) ?> - <?= htmlspecialchars(substr((string) $app['end_time'], 0, 5)) ?> Uhr
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark">
                                                <?= htmlspecialchars((string) ($app['service_name'] ?? 'Unbekannt'), ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                            <div class="small text-muted">
                                                <?= (int) ($app['service_duration'] ?? 60) ?> Min.
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold">
                                                <?= htmlspecialchars((string) ($app['customer_first_name'] . ' ' . $app['customer_last_name']), ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                            <div class="small text-muted">
                                                <?= htmlspecialchars((string) ($app['customer_phone'] ?: $app['customer_email']), ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (!empty($app['instructor_first_name'])): ?>
                                                <span class="badge" style="background-color: <?= htmlspecialchars((string) ($app['instructor_color'] ?? '#0d6efd')) ?>; color: #fff;">
                                                    <?= htmlspecialchars((string) ($app['instructor_first_name'] . ' ' . $app['instructor_last_name'])) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted small fst-italic">Noch nicht zugeteilt</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="small">
                                                <?php if (!empty($app['boat_name'])): ?>
                                                    <div><i class="bi bi-life-preserver text-primary me-1"></i><?= htmlspecialchars((string) $app['boat_name']) ?></div>
                                                <?php endif; ?>
                                                <?php if (!empty($app['location_name'])): ?>
                                                    <div class="text-muted"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars((string) $app['location_name']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $badgeClass ?>-subtle text-<?= $badgeClass ?> px-2 py-1 rounded-pill">
                                                <?= htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </td>
                                        <td>
                                            CHF <?= number_format((float) ($app['price'] ?? 0), 2, '.', "'") ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <?php if ($canManage): ?>
                                                <div class="btn-group btn-group-sm">
                                                    <?php if ($app['status'] === 'requested'): ?>
                                                        <form method="post" action="?route=admin/boating/appointments/status" class="d-inline">
                                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                            <input type="hidden" name="id" value="<?= (int) $app['id'] ?>">
                                                            <input type="hidden" name="status" value="confirmed">
                                                            <button type="submit" class="btn btn-outline-success" title="Bestätigen">
                                                                <i class="bi bi-check-lg"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>

                                                    <?php if ($app['status'] === 'confirmed'): ?>
                                                        <form method="post" action="?route=admin/boating/appointments/status" class="d-inline">
                                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                            <input type="hidden" name="id" value="<?= (int) $app['id'] ?>">
                                                            <input type="hidden" name="status" value="completed">
                                                            <button type="submit" class="btn btn-outline-info" title="Als abgeschlossen markieren">
                                                                <i class="bi bi-check2-all"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>

                                                    <form method="post" action="?route=admin/boating/appointments/delete" class="d-inline" onsubmit="return confirm('Termin wirklich löschen?');">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                        <input type="hidden" name="id" value="<?= (int) $app['id'] ?>">
                                                        <button type="submit" class="btn btn-outline-danger" title="Löschen">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
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
    <?php endif; ?>

</div>

<!-- Modal: Termin manuell anlegen -->
<?php if ($canManage): ?>
<div class="modal fade" id="createAppointmentModal" tabindex="-1" aria-labelledby="createAppointmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" action="?route=admin/boating/appointments/create">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="createAppointmentModalLabel">
                        <i class="bi bi-calendar-plus text-primary me-2"></i>Neuen Termin anlegen / einbuchen
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Dienstleistung / Service <span class="text-danger">*</span></label>
                            <select name="service_id" class="form-select" required>
                                <option value="">-- Bitte wählen --</option>
                                <?php foreach ($services as $srv): ?>
                                    <option value="<?= (int) $srv['id'] ?>">
                                        <?= htmlspecialchars((string) $srv['name']) ?> (<?= (int) ($srv['duration_minutes'] ?? 60) ?> Min)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Kunde / Schüler <span class="text-danger">*</span></label>
                            <select name="customer_id" class="form-select" required>
                                <option value="">-- Kunde wählen --</option>
                                <?php foreach ($customers as $c): ?>
                                    <option value="<?= (int) $c['id'] ?>">
                                        <?= htmlspecialchars((string) ($c['first_name'] . ' ' . $c['last_name'])) ?> (<?= htmlspecialchars((string) $c['email']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Datum <span class="text-danger">*</span></label>
                            <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($selectedDate) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Startzeit (HH:MM) <span class="text-danger">*</span></label>
                            <input type="time" name="start_time" class="form-control" value="09:30" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Endzeit (HH:MM) <span class="text-danger">*</span></label>
                            <input type="time" name="end_time" class="form-control" value="11:00" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Fahrlehrer</label>
                            <select name="instructor_id" class="form-select">
                                <option value="">-- Offen / Später zuweisen --</option>
                                <?php foreach ($instructors as $inst): ?>
                                    <option value="<?= (int) $inst['id'] ?>">
                                        <?= htmlspecialchars((string) ($inst['first_name'] . ' ' . $inst['last_name'])) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Schulungsboot</label>
                            <select name="boat_id" class="form-select">
                                <option value="">-- Offen / Später zuweisen --</option>
                                <?php foreach ($boats as $b): ?>
                                    <option value="<?= (int) $b['id'] ?>">
                                        <?= htmlspecialchars((string) $b['name']) ?> (<?= htmlspecialchars((string) ($b['type'] ?? 'Motor')) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Standort / Treffpunkt</label>
                            <select name="location_id" class="form-select">
                                <option value="">-- Standard --</option>
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?= (int) $loc['id'] ?>">
                                        <?= htmlspecialchars((string) $loc['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                <option value="confirmed">Bestätigt (confirmed)</option>
                                <option value="requested">Angefragt (requested)</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tarif / Preis (CHF)</label>
                            <input type="number" step="0.01" name="price" class="form-control" placeholder="180.00">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Interne Notizen</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Besonderheiten, Ausbildungsstand, etc."></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-semibold px-4">Termin anlegen</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Bootstrap Toast Container (Oben Rechts) -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1090;">
    <!-- Erfolgs-Toast -->
    <div id="calendarSuccessToast" class="toast align-items-center text-bg-success border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center">
                <i class="bi bi-check-circle-fill fs-5 me-2"></i>
                <span id="calendarSuccessToastMsg">Termin erfolgreich geändert.</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Schließen"></button>
        </div>
    </div>

    <!-- Konflikt-Toast -->
    <div id="calendarConflictToast" class="toast align-items-center text-bg-danger border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
                <div>
                    <strong class="d-block">Konflikt festgestellt!</strong>
                    <span id="calendarConflictToastMsg">Boot oder Fahrlehrer zu diesem Zeitpunkt bereits belegt.</span>
                </div>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Schließen"></button>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- VANILLA-JS: DRAG & DROP, KOPIERMODUS, AJAX & KONFLIKTPRÜFUNG -->
<!-- ========================================================================= -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = <?= json_encode($csrfToken, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const canManage = <?= $canManage ? 'true' : 'false' ?>;

    if (!canManage) {
        return;
    }

    const copySwitch = document.getElementById('copyModeSwitch');
    const copyBadge = document.getElementById('copyModeBadge');
    const calendarContainer = document.getElementById('calendarMainContainer');
    const loadingOverlay = document.getElementById('calendarLoadingOverlay');
    const loadingText = document.getElementById('calendarLoadingText');

    const successToastEl = document.getElementById('calendarSuccessToast');
    const conflictToastEl = document.getElementById('calendarConflictToast');

    const successToast = successToastEl && window.bootstrap ? new bootstrap.Toast(successToastEl, { delay: 5000 }) : null;
    const conflictToast = conflictToastEl && window.bootstrap ? new bootstrap.Toast(conflictToastEl, { delay: 7000 }) : null;

    let isCopyMode = false;
    let draggedAppointmentId = null;
    let draggedSourceData = null;

    // 1. Kopiermodus Toggle
    if (copySwitch) {
        copySwitch.addEventListener('change', function () {
            isCopyMode = this.checked;
            if (isCopyMode) {
                if (copyBadge) copyBadge.classList.remove('d-none');
                if (calendarContainer) calendarContainer.classList.add('calendar-copy-mode');
            } else {
                if (copyBadge) copyBadge.classList.add('d-none');
                if (calendarContainer) calendarContainer.classList.remove('calendar-copy-mode');
            }
        });
    }

    function showToast(type, message) {
        if (type === 'success') {
            document.getElementById('calendarSuccessToastMsg').textContent = message;
            if (successToast) successToast.show();
        } else {
            document.getElementById('calendarConflictToastMsg').textContent = message;
            if (conflictToast) conflictToast.show();
        }
    }

    function setLoading(visible, text = 'Termin wird disponiert...') {
        if (!loadingOverlay) return;
        if (visible) {
            if (loadingText) loadingText.textContent = text;
            loadingOverlay.style.display = 'flex';
        } else {
            loadingOverlay.style.display = 'none';
        }
    }

    // 2. Drag Events auf Termine registrieren
    const cards = document.querySelectorAll('.calendar-card[draggable="true"]');
    cards.forEach(card => {
        card.addEventListener('dragstart', function (e) {
            draggedAppointmentId = this.getAttribute('data-appointment-id');
            draggedSourceData = {
                id: draggedAppointmentId,
                date: this.getAttribute('data-date'),
                start: this.getAttribute('data-start'),
                end: this.getAttribute('data-end'),
                instructorId: this.getAttribute('data-instructor-id'),
                boatId: this.getAttribute('data-boat-id'),
                locationId: this.getAttribute('data-location-id')
            };

            this.classList.add('is-dragging');
            e.dataTransfer.setData('text/plain', draggedAppointmentId);
            e.dataTransfer.effectAllowed = isCopyMode ? 'copy' : 'move';
        });

        card.addEventListener('dragend', function () {
            this.classList.remove('is-dragging');
            document.querySelectorAll('.calendar-drop-zone').forEach(zone => {
                zone.classList.remove('drop-target-active');
                zone.classList.remove('drop-target-conflict');
            });
            draggedAppointmentId = null;
        });
    });

    // 3. Drop Zonen registrieren
    const dropZones = document.querySelectorAll('.calendar-drop-zone');
    dropZones.forEach(zone => {
        zone.addEventListener('dragover', function (e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = isCopyMode ? 'copy' : 'move';
            this.classList.add('drop-target-active');
        });

        zone.addEventListener('dragleave', function (e) {
            // Nur entfernen wenn wir die Zone tatsächlich verlassen
            if (!this.contains(e.relatedTarget)) {
                this.classList.remove('drop-target-active');
            }
        });

        zone.addEventListener('drop', async function (e) {
            e.preventDefault();
            this.classList.remove('drop-target-active');

            const appointmentId = e.dataTransfer.getData('text/plain') || draggedAppointmentId;
            if (!appointmentId) {
                return;
            }

            const targetDate = this.getAttribute('data-date');
            const targetStart = this.getAttribute('data-start');
            const targetEnd = this.getAttribute('data-end');
            const targetInstructorId = this.getAttribute('data-instructor-id');
            const targetBoatId = this.getAttribute('data-boat-id');
            const targetLocationId = this.getAttribute('data-location-id');

            // Wenn Ziel exakt der gleiche Slot und gleiche Ressource ist, keine Aktion erforderlich (außer Copy)
            if (!isCopyMode && draggedSourceData &&
                draggedSourceData.date === targetDate &&
                draggedSourceData.start === targetStart &&
                (!targetInstructorId || targetInstructorId === draggedSourceData.instructorId)) {
                return;
            }

            // Ladeindikator anzeigen
            const loadingMsg = isCopyMode
                ? `Dupliziere Termin #${appointmentId} auf ${targetDate} ${targetStart} Uhr...`
                : `Verschiebe Termin #${appointmentId} auf ${targetDate} ${targetStart} Uhr...`;
            setLoading(true, loadingMsg);

            const payload = {
                csrf_token: csrfToken,
                appointment_id: parseInt(appointmentId, 10),
                new_date: targetDate,
                new_start_time: targetStart,
                new_end_time: targetEnd || null,
                new_instructor_id: targetInstructorId ? parseInt(targetInstructorId, 10) : null,
                new_boat_id: targetBoatId ? parseInt(targetBoatId, 10) : null,
                new_location_id: targetLocationId ? parseInt(targetLocationId, 10) : null,
                is_copy: isCopyMode ? true : false
            };

            try {
                const response = await fetch('?route=admin/boating/calendar/move', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();
                setLoading(false);

                if (response.ok && data.success) {
                    showToast('success', data.message || 'Termin erfolgreich aktualisiert.');
                    // Sanfter Reload nach 650ms für nahtloses Re-Rendering aller Relationen
                    setTimeout(() => {
                        window.location.reload();
                    }, 650);
                } else {
                    const errorMsg = data.message || (data.error_code === 'CONFLICT'
                        ? 'Verschieben nicht möglich: Boot oder Fahrlehrer zu diesem Zeitpunkt belegt.'
                        : 'Fehler beim Verschieben des Termins.');
                    showToast('conflict', errorMsg);
                }
            } catch (err) {
                setLoading(false);
                showToast('conflict', 'Verbindungsfehler oder Server nicht erreichbar. Bitte erneut versuchen.');
                console.error('Drag&Drop Move Error:', err);
            }
        });
    });
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../../../views/layouts/admin.php';
