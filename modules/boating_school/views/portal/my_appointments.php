<?php

declare(strict_types=1);

/**
 * Portal: Nautic Kunden-Dashboard
 * Routen: /bootschule/me/appointments, /bootschule, /bootschule/dashboard & /nautic/dashboard
 *
 * @var string|null $token
 * @var bool $tokenInvalid
 * @var bool $noSession
 * @var string|null $email
 * @var array<string, mixed>|null $customer
 * @var array<int, array<string, mixed>> $upcomingAppointments
 * @var array<int, array<string, mixed>> $pastAppointments
 * @var array<int, array<string, mixed>> $subscriptions
 * @var array<string, mixed> $prepaidBalance
 * @var array<string, mixed> $profileCheck
 * @var array<string, mixed> $lakeWeather
 * @var array<int, array<string, mixed>> $availableInstructors
 * @var array<int, array<string, mixed>> $availableLocations
 * @var array<int, array<string, mixed>> $availableServices
 * @var array<int, array<string, mixed>> $availableBoats
 * @var string $csrfToken
 */

$title = 'Kunden-Dashboard - Nautic Bootsausbildung & Vermietung';
$currentRoute = 'bootschule/me/appointments';
$csrfToken = class_exists('Csrf') ? Csrf::getToken() : ($csrfToken ?? '');

$statusBadges = AppointmentService::STATUS_BADGES ?? [
    'requested'          => 'warning',
    'confirmed'          => 'success',
    'cancelled_customer' => 'danger',
    'cancelled_system'   => 'danger',
    'completed'          => 'primary',
];

$statusLabels = AppointmentService::STATUS_LABELS ?? [
    'requested'          => 'Angefragt (in Prüfung)',
    'confirmed'          => 'Bestätigt',
    'cancelled_customer' => 'Storniert durch Kunde',
    'cancelled_system'   => 'Storniert durch Schule',
    'completed'          => 'Abgeschlossen',
];

$cancelMinHours = 24;
if (class_exists('Settings')) {
    $minH = Settings::get('boating_cancel_min_hours_before');
    if ($minH !== null && is_numeric($minH)) {
        $cancelMinHours = (int) $minH;
    }
}

$flashSuccess = $_SESSION['flash_portal_success'] ?? null;
$flashError   = $_SESSION['flash_portal_error'] ?? null;
$flashInfo    = $_SESSION['flash_portal_info'] ?? null;
unset($_SESSION['flash_portal_success'], $_SESSION['flash_portal_error'], $_SESSION['flash_portal_info']);

ob_start();
?>

<div class="container py-4 mb-5">
    <div class="mx-auto" style="max-width: 1140px;">

        <!-- Flash Meldungen -->
        <?php if ($flashSuccess): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm rounded-4 mb-4 d-flex align-items-center" role="alert">
                <i class="bi bi-check-circle-fill text-success fs-5 me-2.5"></i>
                <div><?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Schließen"></button>
            </div>
        <?php endif; ?>

        <?php if ($flashError): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm rounded-4 mb-4 d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-triangle-fill text-danger fs-5 me-2.5"></i>
                <div><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Schließen"></button>
            </div>
        <?php endif; ?>

        <?php if ($flashInfo): ?>
            <div class="alert alert-info alert-dismissible fade show shadow-sm rounded-4 mb-4 d-flex align-items-center" role="alert">
                <i class="bi bi-info-circle-fill text-info fs-5 me-2.5"></i>
                <div><?= htmlspecialchars($flashInfo, ENT_QUOTES, 'UTF-8') ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Schließen"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($tokenInvalid)): ?>
            <!-- Zustand: Ungültiger Zugangslink -->
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white text-center mb-4">
                <div class="mb-3">
                    <span class="p-3 bg-danger bg-opacity-10 text-danger rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                        <i class="bi bi-link-45deg fs-2"></i>
                    </span>
                </div>
                <h4 class="fw-bold text-dark mb-2">Link ist ungültig oder abgelaufen</h4>
                <p class="text-muted mb-4 max-w-600 mx-auto" style="max-width: 540px;">
                    Aus Sicherheitsgründen sind unsere Zugangslinks zeitlich begrenzt (7 Tage gültig). Du kannst dir jederzeit kostenlos einen neuen Zugangslink per E-Mail zusenden lassen.
                </p>

                <div class="card border bg-light rounded-4 p-4 max-w-600 mx-auto text-start" style="max-width: 500px;">
                    <form method="post" action="?route=bootschule/me/request-link">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                        <label class="form-label small fw-semibold text-dark mb-1">Deine registrierte E-Mail-Adresse:</label>
                        <div class="input-group mb-3">
                            <span class="input-group-text bg-white"><i class="bi bi-envelope text-muted"></i></span>
                            <input type="email" name="email" class="form-control" placeholder="name@beispiel.ch" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 rounded-pill fw-semibold">
                            <i class="bi bi-send me-1"></i> Neuen Zugangslink anfordern
                        </button>
                    </form>
                </div>
            </div>

        <?php elseif (!empty($customer)): ?>
            <!-- ============================================================== -->
            <!-- HAUPT-DASHBOARD (AUTHENTIFIZIERTER KUNDE)                      -->
            <!-- ============================================================== -->

            <!-- 1. HINWEIS-BANNER WENN NOCH DATEN IM PROFIL FEHLEN -->
            <?php if (!empty($profileCheck['is_incomplete'])): ?>
                <div class="alert bg-warning-subtle border-warning border-opacity-50 text-dark shadow-sm rounded-4 p-3 p-md-4 mb-4">
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                        <div class="d-flex align-items-start gap-3">
                            <span class="p-2.5 bg-warning text-dark rounded-circle d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                                <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                            </span>
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                    <h5 class="fw-bold mb-0 text-dark">Dein Profil ist noch nicht vollständig</h5>
                                    <span class="badge bg-warning text-dark fw-bold rounded-pill">
                                        <?= (int) ($profileCheck['completeness_percent'] ?? 60) ?>% ausgefüllt
                                    </span>
                                </div>
                                <p class="small text-muted mb-2">
                                    Für die reibungslose Ausbildungsabwicklung und die Prüfungsanmeldung beim Schifffahrtsamt fehlen noch wichtige Angaben in deiner Akte:
                                </p>
                                <div class="d-flex flex-wrap gap-1.5">
                                    <?php foreach (($profileCheck['missing_fields'] ?? []) as $fieldKey => $fieldLabel): ?>
                                        <span class="badge bg-white text-danger border border-danger-subtle rounded-pill py-1 px-2.5 small fw-medium">
                                            <i class="bi bi-dash-circle me-1"></i><?= htmlspecialchars($fieldLabel, ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            <button type="button" class="btn btn-warning text-dark fw-bold rounded-pill px-3.5 shadow-sm btn-sm" data-bs-toggle="modal" data-bs-target="#profileModal">
                                <i class="bi bi-pencil-square me-1"></i> Profil jetzt vervollständigen &rarr;
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 2. BEGRÜSSUNGS- & STATUS-KOPFZEILE -->
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-primary bg-gradient text-white d-flex align-items-center justify-content-center fw-bold fs-4 shadow-sm" style="width: 58px; height: 58px;">
                            <?= htmlspecialchars(mb_substr((string) ($customer['first_name'] ?? 'S'), 0, 1) . mb_substr((string) ($customer['last_name'] ?? 'K'), 0, 1)) ?>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <h1 class="h3 fw-bold text-dark mb-0">
                                    Hallo <?= htmlspecialchars((string) ($customer['first_name'] ?? 'Schüler'), ENT_QUOTES, 'UTF-8') ?>!
                                </h1>
                                <?php if (!empty($customer['customer_number'])): ?>
                                    <span class="badge bg-light text-muted border px-2 py-1 rounded-pill small fw-normal">
                                        <?= htmlspecialchars((string) $customer['customer_number']) ?>
                                    </span>
                                <?php endif; ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill small">
                                    <i class="bi bi-shield-check me-1"></i>Aktiv
                                </span>
                            </div>
                            <div class="small text-muted mt-1 d-flex flex-wrap gap-3 align-items-center">
                                <span><i class="bi bi-envelope me-1 text-primary"></i><?= htmlspecialchars((string) $customer['email']) ?></span>
                                <?php if (!empty($customer['phone'])): ?>
                                    <span><i class="bi bi-telephone me-1 text-primary"></i><?= htmlspecialchars((string) $customer['phone']) ?></span>
                                <?php else: ?>
                                    <span class="text-warning"><i class="bi bi-telephone-x me-1"></i>Keine Telefonnummer</span>
                                <?php endif; ?>
                                <span><i class="bi bi-compass me-1 text-info"></i><?= htmlspecialchars((string) ($customer['target_license'] ?: 'Motorboot Kat. A')) ?></span>
                                <?php if (!empty($customer['faber'])): ?>
                                    <span class="badge bg-light text-dark border font-monospace" title="Fahrberechtigungs-Register"><strong class="text-primary">FABER:</strong> <?= htmlspecialchars((string) $customer['faber']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($customer['pin'])): ?>
                                    <span class="badge bg-light text-dark border font-monospace" title="Persönliche Identifikationsnummer"><strong class="text-primary">PIN:</strong> <?= htmlspecialchars((string) $customer['pin']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($customer['pid'])): ?>
                                    <span class="badge bg-light text-dark border font-monospace" title="Personen-Identifikator"><strong class="text-primary">PID:</strong> <?= htmlspecialchars((string) $customer['pid']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <button type="button" class="btn btn-primary rounded-pill px-3.5 fw-semibold shadow-sm btn-sm" data-bs-toggle="modal" data-bs-target="#requestAppointmentModal">
                            <i class="bi bi-calendar-plus me-1"></i> Terminanfrage stellen
                        </button>
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-3 fw-semibold btn-sm" data-bs-toggle="modal" data-bs-target="#profileModal" title="Profil bearbeiten">
                            <i class="bi bi-person-gear me-1"></i> Profil
                        </button>
                        <a href="?route=bootschule/me/logout" class="btn btn-outline-danger rounded-pill px-3 fw-semibold btn-sm" title="Sitzung sicher beenden">
                            <i class="bi bi-box-arrow-right me-1"></i> Abmelden
                        </a>
                    </div>
                </div>
            </div>

            <!-- 3. TOP KACHELN: KONTOSTAND (GUTHABEN) & SEE-WETTER (LUZERN & NW) -->
            <div class="row g-4 mb-4">
                
                <!-- KACHEL A: KONTOSTAND & GUTHABEN -->
                <div class="col-12 col-lg-5">
                    <div class="card border-0 shadow-sm rounded-4 h-100 bg-white overflow-hidden">
                        <div class="card-header bg-transparent border-0 pt-3.5 px-4 pb-0 d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <span class="p-2 bg-primary bg-opacity-10 text-primary rounded-3">
                                    <i class="bi bi-wallet2 fs-5"></i>
                                </span>
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">Kontostand &amp; Guthaben</h6>
                                    <span class="text-muted small" style="font-size: 0.76rem;">Bereits bezahlte Einheiten</span>
                                </div>
                            </div>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill small">
                                <?= $prepaidBalance['has_active_plan'] ? 'Abo aktiv' : 'Kein Abo' ?>
                            </span>
                        </div>

                        <div class="card-body p-4">
                            <!-- Grosser Saldo-Counter -->
                            <div class="p-3 bg-light rounded-4 border mb-3">
                                <div class="row text-center g-2 align-items-center">
                                    <div class="col-6 border-end">
                                        <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem;">Fahrstunden</div>
                                        <div class="h2 fw-extrabold text-primary mb-0 mt-1">
                                            <?= (int) ($prepaidBalance['remaining_units'] ?? 0) ?>
                                        </div>
                                        <div class="text-muted small" style="font-size: 0.75rem;">
                                            <?= number_format((float) ($prepaidBalance['prepaid_hours'] ?? 0), 1) ?> Std. bezahlt
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-muted small text-uppercase fw-semibold" style="font-size: 0.72rem;">Bootsmieten</div>
                                        <div class="h2 fw-extrabold text-dark mb-0 mt-1">
                                            <?= (int) ($prepaidBalance['prepaid_rentals'] ?? 0) ?>
                                        </div>
                                        <div class="text-muted small" style="font-size: 0.75rem;">
                                            Tage verfügbar
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Fortschritt & Ausbildungs-Stand -->
                            <?php if ($prepaidBalance['has_active_plan']): ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between small text-muted mb-1">
                                        <span><?= htmlspecialchars((string) $prepaidBalance['active_sub_title']) ?>:</span>
                                        <strong><?= (int) $prepaidBalance['used_units'] ?> von <?= (int) $prepaidBalance['total_units'] ?> absolviert</strong>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?= (int) $prepaidBalance['progress_percent'] ?>%;" aria-valuenow="<?= (int) $prepaidBalance['progress_percent'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-light border small text-muted mb-3 py-2">
                                    <i class="bi bi-info-circle me-1 text-primary"></i> Aktuell ist kein Mehrfach-Abo hinterlegt. Terminanfragen werden einzeln oder über bestehende Vereinbarungen abgerechnet.
                                </div>
                            <?php endif; ?>

                            <!-- Liste der Abonnements -->
                            <?php if (!empty($subscriptions)): ?>
                                <div class="small fw-semibold text-muted text-uppercase mb-1.5" style="font-size: 0.7rem;">Deine Karten:</div>
                                <div class="d-flex flex-column gap-1.5 mb-3">
                                    <?php foreach ($subscriptions as $sub): ?>
                                        <?php 
                                        $subRemaining = max(0, (int) ($sub['total_units'] ?? 0) - (int) ($sub['used_units'] ?? 0));
                                        ?>
                                        <div class="d-flex justify-content-between align-items-center p-2 rounded-3 bg-light border small">
                                            <div>
                                                <strong><?= htmlspecialchars((string) ($sub['title'] ?? 'Abonnement')) ?></strong>
                                                <?php if (!empty($sub['code'])): ?>
                                                    <span class="text-muted font-monospace ms-1">(<?= htmlspecialchars((string) $sub['code']) ?>)</span>
                                                <?php endif; ?>
                                            </div>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                <?= $subRemaining ?> Einheiten übrig
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <button type="button" class="btn btn-outline-primary w-100 rounded-pill btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#requestAppointmentModal">
                                <i class="bi bi-calendar2-plus me-1"></i> Stunde buchen &amp; Guthaben einlösen
                            </button>
                        </div>
                    </div>
                </div>

                <!-- KACHEL B: SEE-WETTER IN LUZERN & NIDWALDEN (NW) -->
                <div class="col-12 col-lg-7" id="weatherCard">
                    <div class="card border-0 shadow-sm rounded-4 h-100 bg-white overflow-hidden">
                        <div class="card-header bg-transparent border-0 pt-3.5 px-4 pb-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="p-2 bg-info bg-opacity-10 text-info rounded-3">
                                    <i class="bi bi-cloud-sun fs-5"></i>
                                </span>
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">See-Wetter Vierwaldstättersee</h6>
                                    <span class="text-muted small" style="font-size: 0.76rem;">Stationen Luzern &amp; Nidwalden (NW) &bull; Stand <?= htmlspecialchars((string) $lakeWeather['updated_at']) ?> Uhr</span>
                                </div>
                            </div>

                            <!-- Umschalter Luzern / Nidwalden -->
                            <ul class="nav nav-pills nav-fill bg-light p-1 rounded-pill border small" id="weatherTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active py-1 px-3 rounded-pill fw-semibold" id="weather-lu-tab" data-bs-toggle="pill" data-bs-target="#weather-lu" type="button" role="tab" style="font-size: 0.78rem;">
                                        Luzern (LU)
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link py-1 px-3 rounded-pill fw-semibold" id="weather-nw-tab" data-bs-toggle="pill" data-bs-target="#weather-nw" type="button" role="tab" style="font-size: 0.78rem;">
                                        Nidwalden (NW)
                                    </button>
                                </li>
                            </ul>
                        </div>

                        <div class="card-body p-4">
                            <div class="tab-content" id="weatherTabContent">
                                
                                <!-- STATION 1: LUZERN -->
                                <div class="tab-pane fade show active" id="weather-lu" role="tabpanel">
                                    <?php $wLu = $lakeWeather['luzern']; ?>
                                    <div class="p-3 bg-light rounded-4 border mb-3">
                                        <div class="row g-3 align-items-center">
                                            <div class="col-sm-5 text-center text-sm-start border-sm-end">
                                                <div class="d-flex align-items-center justify-content-center justify-content-sm-start gap-2">
                                                    <i class="bi bi-sun text-warning fs-1"></i>
                                                    <div>
                                                        <div class="h2 fw-extrabold text-dark mb-0"><?= htmlspecialchars($wLu['temp']) ?></div>
                                                        <div class="text-muted small"><?= htmlspecialchars($wLu['condition']) ?></div>
                                                    </div>
                                                </div>
                                                <div class="small text-muted mt-2">
                                                    <i class="bi bi-geo-alt me-1 text-primary"></i><strong><?= htmlspecialchars($wLu['name']) ?></strong>
                                                </div>
                                            </div>

                                            <div class="col-sm-7">
                                                <div class="row g-2 text-center text-sm-start">
                                                    <div class="col-6">
                                                        <span class="text-muted small d-block" style="font-size: 0.72rem;">Windstärke</span>
                                                        <strong class="text-dark fs-6"><?= $wLu['wind_speed_kmh'] ?> km/h</strong>
                                                        <span class="badge bg-primary-subtle text-primary ms-1 small"><?= $wLu['beaufort'] ?> Bft</span>
                                                    </div>
                                                    <div class="col-6">
                                                        <span class="text-muted small d-block" style="font-size: 0.72rem;">Windrichtung</span>
                                                        <strong class="text-dark fs-6"><i class="bi bi-compass text-info me-1"></i><?= htmlspecialchars($wLu['wind_direction']) ?></strong>
                                                    </div>
                                                    <div class="col-6">
                                                        <span class="text-muted small d-block" style="font-size: 0.72rem;">Wassertemperatur</span>
                                                        <strong class="text-primary fs-6"><i class="bi bi-water me-1"></i><?= htmlspecialchars($wLu['water_temp']) ?></strong>
                                                    </div>
                                                    <div class="col-6">
                                                        <span class="text-muted small d-block" style="font-size: 0.72rem;">Seegang</span>
                                                        <span class="small fw-semibold text-dark"><?= htmlspecialchars($wLu['wave_condition']) ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Sturmwarnung & Empfehlung -->
                                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 p-2.5 bg-success bg-opacity-10 border border-success-subtle rounded-3 small">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="p-1.5 bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 22px; height: 22px;">
                                                <i class="bi bi-check-lg" style="font-size: 0.7rem;"></i>
                                            </span>
                                            <div>
                                                <strong>Sturmwarnung Seebecken:</strong> <?= htmlspecialchars($wLu['storm_warning_desc']) ?>
                                            </div>
                                        </div>
                                        <span class="badge bg-white text-success border border-success-subtle rounded-pill">
                                            <i class="bi bi-check2-circle me-1"></i><?= htmlspecialchars($wLu['maneuver_rating']) ?>
                                        </span>
                                    </div>
                                </div>

                                <!-- STATION 2: NIDWALDEN (NW) -->
                                <div class="tab-pane fade" id="weather-nw" role="tabpanel">
                                    <?php $wNw = $lakeWeather['nidwalden']; ?>
                                    <div class="p-3 bg-light rounded-4 border mb-3">
                                        <div class="row g-3 align-items-center">
                                            <div class="col-sm-5 text-center text-sm-start border-sm-end">
                                                <div class="d-flex align-items-center justify-content-center justify-content-sm-start gap-2">
                                                    <i class="bi bi-sun-fill text-warning fs-1"></i>
                                                    <div>
                                                        <div class="h2 fw-extrabold text-dark mb-0"><?= htmlspecialchars($wNw['temp']) ?></div>
                                                        <div class="text-muted small"><?= htmlspecialchars($wNw['condition']) ?></div>
                                                    </div>
                                                </div>
                                                <div class="small text-muted mt-2">
                                                    <i class="bi bi-geo-alt me-1 text-primary"></i><strong><?= htmlspecialchars($wNw['name']) ?></strong>
                                                </div>
                                            </div>

                                            <div class="col-sm-7">
                                                <div class="row g-2 text-center text-sm-start">
                                                    <div class="col-6">
                                                        <span class="text-muted small d-block" style="font-size: 0.72rem;">Windstärke</span>
                                                        <strong class="text-dark fs-6"><?= $wNw['wind_speed_kmh'] ?> km/h</strong>
                                                        <span class="badge bg-primary-subtle text-primary ms-1 small"><?= $wNw['beaufort'] ?> Bft</span>
                                                    </div>
                                                    <div class="col-6">
                                                        <span class="text-muted small d-block" style="font-size: 0.72rem;">Windrichtung</span>
                                                        <strong class="text-dark fs-6"><i class="bi bi-compass text-info me-1"></i><?= htmlspecialchars($wNw['wind_direction']) ?></strong>
                                                    </div>
                                                    <div class="col-6">
                                                        <span class="text-muted small d-block" style="font-size: 0.72rem;">Wassertemperatur</span>
                                                        <strong class="text-primary fs-6"><i class="bi bi-water me-1"></i><?= htmlspecialchars($wNw['water_temp']) ?></strong>
                                                    </div>
                                                    <div class="col-6">
                                                        <span class="text-muted small d-block" style="font-size: 0.72rem;">Seegang</span>
                                                        <span class="small fw-semibold text-dark"><?= htmlspecialchars($wNw['wave_condition']) ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Sturmwarnung & Empfehlung -->
                                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 p-2.5 bg-success bg-opacity-10 border border-success-subtle rounded-3 small">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="p-1.5 bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 22px; height: 22px;">
                                                <i class="bi bi-check-lg" style="font-size: 0.7rem;"></i>
                                            </span>
                                            <div>
                                                <strong>Sturmwarnung Stansstad &amp; Hergiswil:</strong> <?= htmlspecialchars($wNw['storm_warning_desc']) ?>
                                            </div>
                                        </div>
                                        <span class="badge bg-white text-success border border-success-subtle rounded-pill">
                                            <i class="bi bi-check2-circle me-1"></i><?= htmlspecialchars($wNw['maneuver_rating']) ?>
                                        </span>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- 4. TERMINE: KOMMENDE & VERGANGENE TERMINE -->
            <div class="card border-0 shadow-sm rounded-4 bg-white mb-4 overflow-hidden">
                <div class="card-header bg-transparent border-bottom p-3 p-md-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                        <div>
                            <h4 class="fw-bold text-dark mb-1">
                                <i class="bi bi-calendar3 me-2 text-primary"></i>Deine Termine &amp; Ausbildungsfahrten
                            </h4>
                            <p class="text-muted small mb-0">
                                Bevorstehende Fahrstunden, Terminanfragen sowie deine Historie absolvierter Fahrten auf einen Blick.
                            </p>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-primary rounded-pill px-3.5 btn-sm fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#requestAppointmentModal">
                                <i class="bi bi-plus-circle me-1"></i> Neuen Termin anfragen
                            </button>
                        </div>
                    </div>

                    <!-- Tabs: Kommende vs. Vergangene Termine -->
                    <ul class="nav nav-pills mt-3.5 bg-light p-1 rounded-pill border d-inline-flex" id="appointmentsTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active rounded-pill fw-semibold py-1.5 px-3.5 small" id="upcoming-tab" data-bs-toggle="tab" data-bs-target="#upcoming" type="button" role="tab">
                                <i class="bi bi-calendar-check me-1.5"></i>Kommende Termine
                                <span class="badge bg-primary text-white ms-1 rounded-pill"><?= count($upcomingAppointments) ?></span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-pill fw-semibold py-1.5 px-3.5 small" id="past-tab" data-bs-toggle="tab" data-bs-target="#past" type="button" role="tab">
                                <i class="bi bi-clock-history me-1.5"></i>Vergangene Fahrten
                                <span class="badge bg-secondary text-white ms-1 rounded-pill"><?= count($pastAppointments) ?></span>
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="card-body p-3 p-md-4">
                    <div class="tab-content" id="appointmentsTabContent">
                        
                        <!-- TAB A: KOMMENDE TERMINE -->
                        <div class="tab-pane fade show active" id="upcoming" role="tabpanel">
                            <?php if (empty($upcomingAppointments)): ?>
                                <div class="text-center py-5">
                                    <div class="mb-3">
                                        <span class="p-3 bg-light text-muted rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                                            <i class="bi bi-calendar-plus fs-2"></i>
                                        </span>
                                    </div>
                                    <h5 class="fw-bold text-dark mb-1">Keine anstehenden Termine eingetragen</h5>
                                    <p class="text-muted small mb-4 max-w-500 mx-auto" style="max-width: 480px;">
                                        Möchtest du eine neue Fahrstunde, Manövertraining oder eine Bootsmiete buchen? Stelle jetzt deine Anfrage direkt an die Flottenleitung.
                                    </p>
                                    <button type="button" class="btn btn-primary rounded-pill px-4 fw-semibold" data-bs-toggle="modal" data-bs-target="#requestAppointmentModal">
                                        <i class="bi bi-calendar2-plus me-1"></i> Jetzt Wunschtermin anfragen &rarr;
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="d-flex flex-column gap-3">
                                    <?php foreach ($upcomingAppointments as $app): ?>
                                        <?php
                                        $badgeClass = $statusBadges[$app['status']] ?? 'secondary';
                                        $statusText = $statusLabels[$app['status']] ?? $app['status'];

                                        $isFuture = ($app['date'] > date('Y-m-d')) || ($app['date'] === date('Y-m-d') && $app['start_time'] > date('H:i:s'));
                                        $appStartTimestamp = strtotime((string) $app['date'] . ' ' . (string) $app['start_time']);
                                        $hoursUntil = ($appStartTimestamp - time()) / 3600;
                                        $isWithinCancelDeadline = $hoursUntil >= $cancelMinHours;
                                        $isCancellable = in_array($app['status'], ['requested', 'confirmed'], true) && $isFuture && $isWithinCancelDeadline;

                                        $daysUntil = (int) ceil(($appStartTimestamp - time()) / 86400);
                                        $countdownText = $daysUntil <= 0 ? 'Heute!' : ($daysUntil === 1 ? 'Morgen' : "in {$daysUntil} Tagen");
                                        ?>
                                        <div class="card border rounded-4 shadow-xs bg-white overflow-hidden">
                                            <div class="card-body p-3.5">
                                                <div class="row g-3 align-items-center">
                                                    
                                                    <!-- Datum, Uhrzeit & Countdown -->
                                                    <div class="col-12 col-md-3">
                                                        <div class="d-flex align-items-center gap-2 mb-1">
                                                            <div class="h5 fw-bold text-primary mb-0">
                                                                <i class="bi bi-calendar-event me-1"></i>
                                                                <?= date('d.m.Y', strtotime((string) $app['date'])) ?>
                                                            </div>
                                                            <span class="badge bg-light text-primary border small rounded-pill">
                                                                <?= $countdownText ?>
                                                            </span>
                                                        </div>
                                                        <div class="text-dark fw-semibold small">
                                                            <i class="bi bi-clock me-1 text-muted"></i>
                                                            <?= htmlspecialchars(substr((string) $app['start_time'], 0, 5)) ?> – <?= htmlspecialchars(substr((string) $app['end_time'], 0, 5)) ?> Uhr
                                                        </div>
                                                        <div class="mt-2">
                                                            <span class="badge bg-<?= $badgeClass ?>-subtle text-<?= $badgeClass ?> border border-<?= $badgeClass ?>-subtle px-2.5 py-1 rounded-pill small fw-semibold">
                                                                <i class="bi bi-record-circle me-1"></i><?= htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8') ?>
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <!-- Leistung, Fahrlehrer, Boot & Hafen -->
                                                    <div class="col-12 col-md-5">
                                                        <h6 class="fw-bold text-dark mb-1.5">
                                                            <?= htmlspecialchars((string) ($app['service_name'] ?? 'Bootsausbildung Kat. A'), ENT_QUOTES, 'UTF-8') ?>
                                                        </h6>
                                                        <div class="small text-muted d-flex flex-column gap-1">
                                                            <div>
                                                                <i class="bi bi-person-badge text-primary me-1"></i>
                                                                <strong>Fahrlehrer:</strong>
                                                                <?= !empty($app['instructor_first_name']) ? htmlspecialchars((string) ($app['instructor_first_name'] . ' ' . ($app['instructor_last_name'] ?? ''))) : '<span class="text-muted">Wird von Leitung eingeteilt</span>' ?>
                                                            </div>
                                                            <div>
                                                                <i class="bi bi-life-preserver text-info me-1"></i>
                                                                <strong>Boot:</strong>
                                                                <?= !empty($app['boat_name']) ? htmlspecialchars((string) $app['boat_name']) : '<span class="text-muted">Schulungsboot Flotte</span>' ?>
                                                            </div>
                                                            <div>
                                                                <i class="bi bi-geo-alt-fill text-danger me-1"></i>
                                                                <strong>Treffpunkt:</strong>
                                                                <?= htmlspecialchars((string) ($app['location_name'] ?? 'Hafen / Steg')) ?>
                                                                <?php if (!empty($app['location_address'])): ?>
                                                                    <span class="text-muted small ms-1">(<?= htmlspecialchars((string) $app['location_address']) ?>)</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Preis & Aktionen / Storno -->
                                                    <div class="col-12 col-md-4 text-md-end">
                                                        <div class="mb-2">
                                                            <?php if (!empty($app['subscription_id'])): ?>
                                                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill small">
                                                                    <i class="bi bi-check-circle me-1"></i>Bezahlt mit Abo
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="text-muted small">Preis:</span>
                                                                <div class="h5 fw-bold text-dark mb-0">CHF <?= number_format((float) ($app['price'] ?? 0), 2, '.', "'") ?></div>
                                                            <?php endif; ?>
                                                        </div>

                                                        <?php if ($isCancellable): ?>
                                                            <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#cancelModal<?= (int) $app['id'] ?>">
                                                                <i class="bi bi-x-circle me-1"></i> Absagen
                                                            </button>

                                                            <!-- Storno-Modal -->
                                                            <div class="modal fade text-start" id="cancelModal<?= (int) $app['id'] ?>" tabindex="-1" aria-hidden="true">
                                                                <div class="modal-dialog modal-dialog-centered">
                                                                    <div class="modal-content rounded-4 border-0 shadow">
                                                                        <div class="modal-header border-bottom">
                                                                            <h5 class="modal-title fw-bold text-danger">
                                                                                <i class="bi bi-exclamation-triangle-fill me-2"></i>Termin stornieren
                                                                            </h5>
                                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                                                                        </div>
                                                                        <form method="post" action="?route=bootschule/me/appointments/<?= (int) $app['id'] ?>/cancel">
                                                                            <div class="modal-body p-4">
                                                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                                                <input type="hidden" name="appointment_id" value="<?= (int) $app['id'] ?>">

                                                                                <p class="mb-3">
                                                                                    Möchtest du die gebuchte Fahrstunde am <strong><?= date('d.m.Y', strtotime((string) $app['date'])) ?></strong> um <strong><?= substr((string) $app['start_time'], 0, 5) ?> Uhr</strong> wirklich stornieren?
                                                                                </p>

                                                                                <div class="mb-3">
                                                                                    <label class="form-label small fw-semibold text-muted">Grund für die Absage (optional):</label>
                                                                                    <textarea name="cancel_reason" rows="3" class="form-control rounded-3" placeholder="z. B. Kurzfristiger Terminkonflikt, Krankheit..."></textarea>
                                                                                </div>

                                                                                <div class="alert alert-light border small text-muted mb-0">
                                                                                    <i class="bi bi-info-circle me-1"></i> Stornierungsfrist: Kostenfreie Stornierung bis <strong><?= $cancelMinHours ?> Stunden</strong> vor Beginn.
                                                                                </div>
                                                                            </div>
                                                                            <div class="modal-footer border-top bg-light">
                                                                                <button type="button" class="btn btn-secondary rounded-pill btn-sm" data-bs-dismiss="modal">Abbrechen</button>
                                                                                <button type="submit" class="btn btn-danger rounded-pill btn-sm fw-semibold">Ja, Termin verbindlich stornieren</button>
                                                                            </div>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php elseif (in_array($app['status'], ['requested', 'confirmed'], true) && !$isWithinCancelDeadline && $isFuture): ?>
                                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill small">
                                                                <i class="bi bi-clock-history me-1"></i>Frist &lt; <?= $cancelMinHours ?>h
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- TAB B: VERGANGENE TERMINE -->
                        <div class="tab-pane fade" id="past" role="tabpanel">
                            <?php if (empty($pastAppointments)): ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-clock-history text-muted fs-1 mb-2"></i>
                                    <h6 class="fw-bold mb-1">Keine vergangenen Fahrten</h6>
                                    <p class="text-muted small mb-0">In den vergangenen 12 Monaten wurden noch keine absolvierten Fahrten archiviert.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light small text-muted text-uppercase">
                                            <tr>
                                                <th class="ps-3">Datum &amp; Zeit</th>
                                                <th>Dienstleistung</th>
                                                <th>Fahrlehrer &amp; Boot</th>
                                                <th>Standort</th>
                                                <th>Status</th>
                                                <th class="text-end pe-3">Abrechnung</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($pastAppointments as $app): ?>
                                                <?php
                                                $badgeClass = $statusBadges[$app['status']] ?? 'secondary';
                                                $statusText = $statusLabels[$app['status']] ?? $app['status'];
                                                ?>
                                                <tr>
                                                    <td class="ps-3 py-3">
                                                        <div class="fw-bold text-dark"><?= date('d.m.Y', strtotime((string) $app['date'])) ?></div>
                                                        <div class="small text-muted"><?= substr((string) $app['start_time'], 0, 5) ?> – <?= substr((string) $app['end_time'], 0, 5) ?> Uhr</div>
                                                    </td>
                                                    <td>
                                                        <div class="fw-semibold text-dark"><?= htmlspecialchars((string) ($app['service_name'] ?? 'Fahrstunde')) ?></div>
                                                    </td>
                                                    <td>
                                                        <div class="small text-dark fw-semibold"><?= !empty($app['instructor_first_name']) ? htmlspecialchars((string) ($app['instructor_first_name'] . ' ' . ($app['instructor_last_name'] ?? ''))) : '-' ?></div>
                                                        <div class="small text-muted"><?= !empty($app['boat_name']) ? htmlspecialchars((string) $app['boat_name']) : 'Schulungsboot' ?></div>
                                                    </td>
                                                    <td>
                                                        <div class="small text-muted"><?= htmlspecialchars((string) ($app['location_name'] ?? 'Hafen')) ?></div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?= $badgeClass ?>-subtle text-<?= $badgeClass ?> rounded-pill small">
                                                            <?= htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8') ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-end pe-3 fw-semibold text-dark">
                                                        <?php if (!empty($app['subscription_id'])): ?>
                                                            <span class="badge bg-light text-muted border">Abo-Einheit</span>
                                                        <?php else: ?>
                                                            CHF <?= number_format((float) ($app['price'] ?? 0), 2, '.', "'") ?>
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

            <!-- ============================================================== -->
            <!-- MODAL 1: TERMINANFRAGE STELLEN                                  -->
            <!-- ============================================================== -->
            <div class="modal fade" id="requestAppointmentModal" tabindex="-1" aria-labelledby="requestAppointmentModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content rounded-4 border-0 shadow">
                        <div class="modal-header border-bottom px-4 pt-4 pb-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="p-2 bg-primary bg-opacity-10 text-primary rounded-circle">
                                    <i class="bi bi-calendar2-plus fs-5"></i>
                                </span>
                                <div>
                                    <h5 class="modal-title fw-bold text-dark" id="requestAppointmentModalLabel">Neue Terminanfrage stellen</h5>
                                    <span class="text-muted small">Wunschtermin für Fahrstunde, Manövertraining oder Bootsmiete einreichen</span>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                        </div>

                        <form method="POST" action="?route=bootschule/me/request-appointment">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                            <div class="modal-body p-4">
                                <div class="row g-3">
                                    
                                    <!-- Leistung / Ausbildungsart -->
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-semibold text-dark mb-1">Ausbildungs- oder Mietleistung *</label>
                                        <select name="service_id" class="form-select rounded-3" required>
                                            <?php if (!empty($availableServices)): ?>
                                                <?php foreach ($availableServices as $srv): ?>
                                                    <option value="<?= (int) $srv['id'] ?>">
                                                        <?= htmlspecialchars((string) $srv['name']) ?> (CHF <?= number_format((float) ($srv['price'] ?? 0), 2, '.', "'") ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <option value="0">Motorboot Kat. A – Einzelfahrstunde (90 Min.)</option>
                                                <option value="0">Motorboot Kat. A – Intensiv-Doppelstunde (180 Min.)</option>
                                                <option value="0">Hafen- &amp; Boxen-Manövertraining (90 Min.)</option>
                                                <option value="0">Prüfungsvorbereitung / Generalprobe</option>
                                                <option value="0">Nachtfahrt &amp; Befeuerung Navigation</option>
                                                <option value="0">Bootsmiete / Charter (Halbtag)</option>
                                            <?php endif; ?>
                                        </select>
                                    </div>

                                    <!-- Wunschdatum -->
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-semibold text-dark mb-1">Wunschdatum *</label>
                                        <input type="date" name="date" class="form-control rounded-3" required min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                                    </div>

                                    <!-- Bevorzugtes Zeitfenster -->
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-semibold text-dark mb-1">Bevorzugtes Zeitfenster</label>
                                        <select name="time_preference" class="form-select rounded-3">
                                            <option value="morning">Vormittag (09:00 – 10:30 Uhr)</option>
                                            <option value="afternoon" selected>Nachmittag (14:00 – 15:30 Uhr)</option>
                                            <option value="evening">Feierabend / Abend (17:30 – 19:00 Uhr)</option>
                                            <option value="custom">Genaue Startzeit wählen &darr;</option>
                                        </select>
                                    </div>

                                    <!-- Optionale spezifische Startzeit -->
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-semibold text-muted mb-1">Oder exakte Startzeit (optional)</label>
                                        <input type="time" name="custom_time" class="form-control rounded-3" step="900" placeholder="z. B. 10:00">
                                    </div>

                                    <!-- Standort / Treffpunkt -->
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-semibold text-dark mb-1">Wunsch-Standort / Hafen</label>
                                        <select name="location_id" class="form-select rounded-3">
                                            <?php if (!empty($availableLocations)): ?>
                                                <?php foreach ($availableLocations as $loc): ?>
                                                    <option value="<?= (int) $loc['id'] ?>">
                                                        <?= htmlspecialchars((string) $loc['name']) ?> (<?= htmlspecialchars((string) ($loc['city'] ?? 'Vierwaldstättersee')) ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <option value="">Luzern (Alpenquai / Tribschen)</option>
                                                <option value="">Stansstad (NW)</option>
                                                <option value="">Hergiswil (NW)</option>
                                                <option value="">Buochs (NW)</option>
                                            <?php endif; ?>
                                        </select>
                                    </div>

                                    <!-- Fahrlehrer / Coach Präferenz -->
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-semibold text-dark mb-1">Wunsch-Fahrlehrer (optional)</label>
                                        <select name="instructor_id" class="form-select rounded-3">
                                            <option value="">Beliebig (Nächster freier Fahrlehrer)</option>
                                            <?php if (!empty($availableInstructors)): ?>
                                                <?php foreach ($availableInstructors as $inst): ?>
                                                    <option value="<?= (int) $inst['id'] ?>">
                                                        <?= htmlspecialchars((string) ($inst['first_name'] . ' ' . $inst['last_name'])) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>

                                    <!-- Verrechnung mit bestehendem Guthaben -->
                                    <div class="col-12">
                                        <div class="p-3 bg-light rounded-3 border">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="use_balance" value="1" id="useBalanceCheck" <?= $prepaidBalance['remaining_units'] > 0 ? 'checked' : '' ?>>
                                                <label class="form-check-label fw-semibold text-dark" for="useBalanceCheck">
                                                    Mit bestehendem Stunden-Guthaben / Abonnement verrechnen
                                                </label>
                                                <div class="text-muted small">
                                                    Aktuell verfügbar: <strong><?= (int) ($prepaidBalance['remaining_units'] ?? 0) ?> bezahlte Fahrstunden</strong>. Falls aktiviert, wird nach Bestätigung automatisch eine Einheit abgebucht.
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Notizen / Wünsche an den Fahrlehrer -->
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold text-dark mb-1">Notizen &amp; Ausbildungsschwerpunkte (optional)</label>
                                        <textarea name="notes" rows="2" class="form-control rounded-3" placeholder="z. B. Möchte Bugstrahlruder, Vorwärts- und Rückwärtsanleger in die Boxe intensiv üben..."></textarea>
                                    </div>

                                </div>
                            </div>

                            <div class="modal-footer border-top bg-light px-4 py-3">
                                <button type="button" class="btn btn-secondary rounded-pill btn-sm px-3" data-bs-dismiss="modal">Abbrechen</button>
                                <button type="submit" class="btn btn-primary rounded-pill btn-sm px-4 fw-semibold shadow-sm">
                                    <i class="bi bi-send me-1"></i> Terminanfrage verbindlich absenden &rarr;
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ============================================================== -->
            <!-- MODAL 2: MEIN PROFIL BEARBEITEN / VERVOLLSTÄNDIGEN              -->
            <!-- ============================================================== -->
            <div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content rounded-4 border-0 shadow">
                        <div class="modal-header border-bottom px-4 pt-4 pb-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="p-2 bg-warning bg-opacity-10 text-dark rounded-circle">
                                    <i class="bi bi-person-gear fs-5"></i>
                                </span>
                                <div>
                                    <h5 class="modal-title fw-bold text-dark" id="profileModalLabel">Mein Schülerprofil bearbeiten</h5>
                                    <span class="text-muted small">Kontaktdaten &amp; Angaben für die Schifffahrtsamts-Prüfung</span>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                        </div>

                        <form method="POST" action="?route=bootschule/me/profile">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                            <div class="modal-body p-4">
                                <div class="row g-3">
                                    
                                    <!-- Vorname & Nachname (Info) -->
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-semibold text-muted mb-1">Vorname</label>
                                        <input type="text" class="form-control rounded-3 bg-light" value="<?= htmlspecialchars((string) ($customer['first_name'] ?? '')) ?>" disabled>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-semibold text-muted mb-1">Nachname</label>
                                        <input type="text" class="form-control rounded-3 bg-light" value="<?= htmlspecialchars((string) ($customer['last_name'] ?? '')) ?>" disabled>
                                    </div>

                                    <!-- E-Mail (Info) -->
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-semibold text-muted mb-1">E-Mail-Adresse</label>
                                        <input type="email" class="form-control rounded-3 bg-light" value="<?= htmlspecialchars((string) ($customer['email'] ?? '')) ?>" disabled>
                                    </div>

                                    <!-- Telefonnummer (Zwingend) -->
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-semibold text-dark mb-1">
                                            Mobiltelefonnummer <span class="text-danger">*</span>
                                        </label>
                                        <input type="tel" name="phone" class="form-control rounded-3" placeholder="+41 79 123 45 67" required value="<?= htmlspecialchars((string) ($customer['phone'] ?? '')) ?>">
                                        <div class="form-text" style="font-size: 0.72rem;">Für kurzfristige wetterbedingte Verschiebungen oder Erreichbarkeit am Steg.</div>
                                    </div>

                                    <!-- Geburtsdatum (für Schifffahrtsamt) -->
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-semibold text-dark mb-1">
                                            Geburtsdatum <span class="text-danger">*</span>
                                        </label>
                                        <input type="date" name="birthdate" class="form-control rounded-3" value="<?= htmlspecialchars((string) ($customer['birthdate'] ?? '')) ?>">
                                        <div class="form-text" style="font-size: 0.72rem;">Vom Schifffahrtsamt für die Prüfungsanmeldung verlangt.</div>
                                    </div>

                                    <!-- FABER: Fahrberechtigungs-Register -->
                                    <div class="col-12 col-md-4">
                                        <label class="form-label small fw-semibold text-dark mb-1">
                                            FABER: <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" name="faber" class="form-control rounded-3 font-monospace" placeholder="z. B. 123.456.789" required value="<?= htmlspecialchars((string) ($customer['faber'] ?? $customer['license_number'] ?? '')) ?>">
                                        <div class="form-text" style="font-size: 0.70rem;">Fahrberechtigungs-Register Nr.</div>
                                    </div>

                                    <!-- PIN: Persönliche Identifikationsnummer -->
                                    <div class="col-12 col-md-4">
                                        <label class="form-label small fw-semibold text-dark mb-1">
                                            PIN: <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" name="pin" class="form-control rounded-3 font-monospace" placeholder="z. B. 7841" required value="<?= htmlspecialchars((string) ($customer['pin'] ?? '')) ?>">
                                        <div class="form-text" style="font-size: 0.70rem;">Persönliche Identifikationsnummer</div>
                                    </div>

                                    <!-- PID: Personen-Identifikator -->
                                    <div class="col-12 col-md-4">
                                        <label class="form-label small fw-semibold text-dark mb-1">
                                            PID: <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" name="pid" class="form-control rounded-3 font-monospace" placeholder="z. B. CHE-123.456.7" required value="<?= htmlspecialchars((string) ($customer['pid'] ?? '')) ?>">
                                        <div class="form-text" style="font-size: 0.70rem;">Personen-Identifikator (Schifffahrtsamt)</div>
                                    </div>

                                    <!-- Strasse & Nr. -->
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-semibold text-dark mb-1">Strasse &amp; Hausnummer</label>
                                        <input type="text" name="street" class="form-control rounded-3" placeholder="Seestrasse 12" value="<?= htmlspecialchars((string) ($customer['street'] ?? '')) ?>">
                                    </div>

                                    <!-- PLZ & Ort -->
                                    <div class="col-12 col-md-6">
                                        <div class="row g-2">
                                            <div class="col-4">
                                                <label class="form-label small fw-semibold text-dark mb-1">PLZ</label>
                                                <input type="text" name="zip" class="form-control rounded-3" placeholder="6000" value="<?= htmlspecialchars((string) ($customer['zip'] ?? '')) ?>">
                                            </div>
                                            <div class="col-8">
                                                <label class="form-label small fw-semibold text-dark mb-1">Wohnort</label>
                                                <input type="text" name="city" class="form-control rounded-3" placeholder="Luzern" value="<?= htmlspecialchars((string) ($customer['city'] ?? '')) ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Notfallkontakt -->
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-semibold text-dark mb-1">Notfallkontakt (Name &amp; Telefonnummer)</label>
                                        <input type="text" name="emergency_contact" class="form-control rounded-3" placeholder="z. B. Anna Muster, +41 78 987 65 43" value="<?= htmlspecialchars((string) ($customer['emergency_contact'] ?? '')) ?>">
                                    </div>

                                    <!-- Persönliche Wünsche / Bemerkungen -->
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-semibold text-dark mb-1">Notizen &amp; Wünsche</label>
                                        <input type="text" name="notes" class="form-control rounded-3" placeholder="z. B. Bevorzugt Fahrstunden am Vormittag" value="<?= htmlspecialchars((string) ($customer['notes'] ?? '')) ?>">
                                    </div>

                                </div>
                            </div>

                            <div class="modal-footer border-top bg-light px-4 py-3">
                                <button type="button" class="btn btn-secondary rounded-pill btn-sm px-3" data-bs-dismiss="modal">Abbrechen</button>
                                <button type="submit" class="btn btn-warning text-dark rounded-pill btn-sm px-4 fw-bold shadow-sm">
                                    <i class="bi bi-save me-1"></i> Profilangaben speichern &rarr;
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Zustand: Ohne aktive Session -> E-Mail-Eingabe für Magic-Code -->
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white text-center mb-4 max-w-600 mx-auto" style="max-width: 580px;">
                <div class="mb-3">
                    <span class="p-3 bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                        <i class="bi bi-compass fs-2"></i>
                    </span>
                </div>
                <h3 class="fw-bold text-dark mb-2">Nautic Kunden-Dashboard</h3>
                <p class="text-muted mb-4">
                    Bitte melde dich an, um deine anstehenden Fahrstunden, dein Abo-Guthaben und das aktuelle See-Wetter einzusehen.
                </p>

                <div class="d-flex justify-content-center gap-2">
                    <a href="?route=nautic/login" class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm">
                        <i class="bi bi-magic me-1"></i> Zum Magic-Code Login &rarr;
                    </a>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../../../views/layouts/boating_portal.php';
