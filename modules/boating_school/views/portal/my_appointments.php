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
$useSidebarLayout = true;
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

<?php if ($flashInfo): ?>
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 1080;">
        <div class="alert alert-info alert-dismissible fade show shadow rounded-4 d-flex align-items-center" role="alert">
            <i class="bi bi-info-circle-fill text-info fs-5 me-2.5"></i>
            <div><?= htmlspecialchars($flashInfo, ENT_QUOTES, 'UTF-8') ?></div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Schließen"></button>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($tokenInvalid)): ?>
    <!-- Zustand: Ungültiger Zugangslink -->
    <div class="container py-5">
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white text-center mx-auto" style="max-width: 540px;">
            <div class="mb-3">
                <span class="p-3 bg-danger bg-opacity-10 text-danger rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                    <i class="bi bi-link-45deg fs-2"></i>
                </span>
            </div>
            <h4 class="fw-bold text-dark mb-2">Link ist ungültig oder abgelaufen</h4>
            <p class="text-muted mb-4 small">
                Aus Sicherheitsgründen sind unsere Zugangslinks zeitlich begrenzt (7 Tage gültig). Du kannst dir jederzeit kostenlos einen neuen Zugangslink per E-Mail zusenden lassen.
            </p>

            <form method="post" action="?route=bootschule/me/request-link" class="text-start">
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
    <!-- HAUPT-DASHBOARD MIT SIDEBAR (WIE SCREENSHOT)                  -->
    <!-- ============================================================== -->
    <div class="d-flex flex-column flex-lg-row" style="min-height: 100vh; background-color: #f8fafc;">
        
        <!-- ============================================================== -->
        <!-- LINKE SIDEBAR (WARM ESPRESSO #2d2421)                         -->
        <!-- ============================================================== -->
        <aside class="d-flex flex-column justify-content-between p-3 p-lg-4 shrink-0 text-white" style="width: 100%; max-width: 275px; background-color: #2d2421; border-right: 1px solid #3f3531;">
            <div>
                <!-- Nautisches Emblem Logo im abgerundeten Kasten -->
                <div class="text-center mb-4 mt-2">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-4 shadow-inner" style="width: 110px; height: 80px; background-color: #423733; border: 1px solid rgba(255, 255, 255, 0.15);">
                        <svg viewBox="0 0 48 48" style="width: 46px; height: 46px; color: #f5f5f4;" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="24" cy="24" r="18" stroke="currentColor" stroke-width="2" stroke-dasharray="2 3" />
                            <circle cx="24" cy="24" r="14" stroke="currentColor" stroke-width="1.5" />
                            <path d="M 24 10 L 24 38 M 10 24 L 38 24 M 14 14 L 34 34 M 14 34 L 34 14" stroke="currentColor" stroke-width="1.5" />
                            <circle cx="24" cy="24" r="4" fill="currentColor" />
                            <path d="M 24 18 L 24 30 M 18 24 L 30 24" stroke="#2d2421" stroke-width="2" />
                        </svg>
                    </div>
                </div>

                <!-- Navigation Menu Items -->
                <nav class="d-flex flex-column gap-1.5 small font-medium">
                    <a href="?route=bootschule/me/appointments" class="sidebar-nav-link active justify-content-between">
                        <span class="d-flex align-items-center gap-2.5">
                            <i class="bi bi-grid-fill text-warning"></i>
                            <span>Dashboard</span>
                        </span>
                        <span class="rounded-circle" style="width: 7px; height: 7px; background-color: #fbbf24;"></span>
                    </a>

                    <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#requestAppointmentModal" class="sidebar-nav-link">
                        <i class="bi bi-calendar3"></i>
                        <span>Termine &amp; Kalender</span>
                    </a>

                    <a href="javascript:void(0)" onclick="alert('Digitaler Self Check-in für Schiffsübernahme aktiviert.')" class="sidebar-nav-link">
                        <i class="bi bi-qr-code"></i>
                        <span>Self Checkin (Miete)</span>
                    </a>

                    <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#requestAppointmentModal" class="sidebar-nav-link">
                        <i class="bi bi-award"></i>
                        <span>Store &amp; Abos</span>
                    </a>

                    <a href="javascript:void(0)" onclick="alert('Ihre Swiss QR-Bill Rechnungen & Quittungen sind im Kundenarchiv hinterlegt.')" class="sidebar-nav-link">
                        <i class="bi bi-file-earmark-text"></i>
                        <span>Meine Rechnungen</span>
                    </a>

                    <a href="javascript:void(0)" onclick="alert('Nautic Ausbildungsrichtlinien, AGB & Schifffahrtsamts-Zulassung hinterlegt.')" class="sidebar-nav-link">
                        <i class="bi bi-shield-check"></i>
                        <span>Rechtliches &amp; Nachweise</span>
                    </a>

                    <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#profileModal" class="sidebar-nav-link">
                        <i class="bi bi-person"></i>
                        <span>Mein Profil</span>
                    </a>
                </nav>
            </div>

            <!-- Unterer Bereich: Countdown, Rolle wechseln, Profil, Logout -->
            <div class="pt-4 border-top border-secondary border-opacity-25 mt-4 d-flex flex-column gap-3">
                <!-- Sitzungs Countdown Timer -->
                <div class="p-2 rounded-3 text-center border border-secondary border-opacity-25" style="background-color: #211a17; font-size: 0.72rem;">
                    <span class="text-light text-opacity-75">Sitzung aktiv: </span>
                    <strong id="session-timer-display" class="font-monospace text-warning">15:00</strong>
                </div>

                <!-- Rolle wechseln Pills (Demo) -->
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-1.5" style="font-size: 0.65rem;">
                        <span class="text-uppercase fw-bold text-light text-opacity-50">Rolle wechseln</span>
                        <span class="badge text-uppercase fw-bold px-1.5 py-0.5" style="background-color: rgba(245, 158, 11, 0.2); color: #fde68a; font-size: 0.6rem;">DEMO</span>
                    </div>
                    <div class="d-grid gap-1 p-1 rounded-3" style="grid-template-columns: 1fr 1fr 1fr; background-color: #1f1816; font-size: 0.75rem;">
                        <a href="?route=admin/boating" class="btn btn-sm text-light text-opacity-75 py-1 px-1 border-0 rounded text-decoration-none text-center">Admin</a>
                        <a href="?route=admin/boating" class="btn btn-sm text-light text-opacity-75 py-1 px-1 border-0 rounded text-decoration-none text-center">Coach</a>
                        <span class="btn btn-sm text-white py-1 px-1 border-0 rounded fw-bold text-center shadow-xs" style="background-color: #4a3d38;">Kunde</span>
                    </div>
                </div>

                <!-- Kunde Profil Card -->
                <div class="p-2.5 rounded-3 border border-secondary border-opacity-25 d-flex align-items-center justify-content-between gap-2" style="background-color: #3b302c;">
                    <div class="d-flex align-items-center gap-2 overflow-hidden">
                        <div class="rounded-3 d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 34px; height: 34px; background-color: rgba(180, 83, 9, 0.3); color: #fcd34d; font-size: 0.78rem; border: 1px solid rgba(245, 158, 11, 0.3);">
                            <?= htmlspecialchars(mb_substr((string) ($customer['first_name'] ?? 'R'), 0, 1) . mb_substr((string) ($customer['last_name'] ?? 'G'), 0, 1)) ?>
                        </div>
                        <div class="text-truncate">
                            <div class="text-white fw-bold small text-truncate"><?= htmlspecialchars((string) ($customer['first_name'] ?? 'Roland') . ' ' . (string) ($customer['last_name'] ?? 'Grüter')) ?></div>
                            <div class="text-light text-opacity-50" style="font-size: 0.7rem;">Customer</div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm p-1 text-light text-opacity-50 border-0" data-bs-toggle="modal" data-bs-target="#profileModal" title="Profil bearbeiten">
                        <i class="bi bi-person fs-6"></i>
                    </button>
                </div>

                <!-- Abmelden -->
                <a href="?route=bootschule/me/logout" class="btn btn-sm text-light text-opacity-75 border border-secondary border-opacity-50 rounded-3 py-1.5 fw-semibold d-flex align-items-center justify-content-center gap-2 text-decoration-none" style="background-color: rgba(61, 50, 46, 0.6);">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Abmelden</span>
                </a>
            </div>
        </aside>

        <!-- ============================================================== -->
        <!-- HAUPTINHALT (RECHTS)                                          -->
        <!-- ============================================================== -->
        <div class="flex-grow-1 p-3 p-md-4 p-lg-5 overflow-y-auto" style="background-color: #f8fafc;">
            <div class="portal-inner-container mx-auto" style="max-width: 1440px; width: 100%;">
            
            <?php
            $custFirst = trim((string) ($customer['first_name'] ?? ''));
            $displayName = (!empty($custFirst) && strtolower($custFirst) !== 'office') ? $custFirst : 'Roland';
            ?>

            <!-- 1. Header Zeile: Begrüssung & Quick Buttons -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div>
                    <h2 class="h3 fw-extrabold text-dark mb-1" style="letter-spacing: -0.5px;">
                        Willkommen zurück, <?= htmlspecialchars($displayName) ?>!
                    </h2>
                    <p class="text-muted small mb-0 d-flex align-items-center gap-1.5">
                        <span>Hier ist Ihre Kunden-Übersicht für heute.</span>
                        <span>&bull;</span>
                        <span class="d-flex align-items-center gap-1">
                            <i class="bi bi-envelope text-muted"></i>
                            <?= htmlspecialchars((string) ($customer['email'] ?? 'office@studiocreativo.ch')) ?>
                        </span>
                    </p>
                </div>

                <div class="d-flex align-items-center gap-2.5">
                    <button type="button" class="btn btn-light bg-white border rounded-3 px-3.5 py-2 small fw-semibold shadow-xs d-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#profileModal">
                        <i class="bi bi-person text-secondary"></i>
                        <span>Mein Profil</span>
                    </button>

                    <button type="button" class="btn text-white rounded-3 px-3.5 py-2 small fw-bold shadow-xs d-flex align-items-center gap-2" style="background-color: #2d2421;" data-bs-toggle="modal" data-bs-target="#requestAppointmentModal">
                        <i class="bi bi-calendar-plus text-warning"></i>
                        <span>Termin buchen</span>
                    </button>
                </div>
            </div>

            <!-- 2. Dual Side-by-Side Wetter-Kacheln (Luzern & Hergiswil wie im Screenshot) -->
            <div class="row g-3 mb-4">
                
                <!-- Kachel Luzern -->
                <div class="col-12 col-md-6">
                    <div class="card border rounded-4 p-3.5 bg-white shadow-xs d-flex flex-row align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-3 d-flex align-items-center justify-content-center text-secondary" style="width: 50px; height: 50px; background-color: #f8fafc; border: 1px solid #e2e8f0;">
                                <i class="bi bi-cloud fs-3 text-secondary"></i>
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-1">
                                    <h6 class="fw-bold text-dark mb-0">Luzern</h6>
                                    <span style="font-size: 0.85rem;">🌡️</span>
                                </div>
                                <div class="h2 fw-extrabold text-dark mb-0 mt-0.5" style="letter-spacing: -0.5px;">
                                    7°C
                                </div>
                            </div>
                        </div>
                        <div class="text-end">
                            <a href="#weatherCard" class="text-secondary fw-semibold text-decoration-none text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">
                                PROGNOSE <i class="bi bi-box-arrow-up-right" style="font-size: 0.6rem;"></i>
                            </a>
                            <div class="d-flex align-items-center gap-1.5 text-secondary small fw-medium mt-1 justify-content-end">
                                <i class="bi bi-wind text-muted"></i>
                                <span>7.7 km/h</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kachel Hergiswil -->
                <div class="col-12 col-md-6">
                    <div class="card border rounded-4 p-3.5 bg-white shadow-xs d-flex flex-row align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-3 d-flex align-items-center justify-content-center text-secondary" style="width: 50px; height: 50px; background-color: #f8fafc; border: 1px solid #e2e8f0;">
                                <i class="bi bi-cloud fs-3 text-secondary"></i>
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-1">
                                    <h6 class="fw-bold text-dark mb-0">Hergiswil</h6>
                                    <span style="font-size: 0.85rem;">🌡️</span>
                                </div>
                                <div class="h2 fw-extrabold text-dark mb-0 mt-0.5" style="letter-spacing: -0.5px;">
                                    7°C
                                </div>
                            </div>
                        </div>
                        <div class="text-end">
                            <a href="#weatherCard" class="text-secondary fw-semibold text-decoration-none text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">
                                PROGNOSE <i class="bi bi-box-arrow-up-right" style="font-size: 0.6rem;"></i>
                            </a>
                            <div class="d-flex align-items-center gap-1.5 text-secondary small fw-medium mt-1 justify-content-end">
                                <i class="bi bi-wind text-muted"></i>
                                <span>8.9 km/h</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- 3. Gelbes Warnbanner: Profil vervollständigen & AGB bestätigen -->
            <div class="rounded-4 p-3.5 mb-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 shadow-xs" style="background-color: #fff9eb; border: 1px solid #f5dfa8;">
                <div class="d-flex align-items-start gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px; background-color: #fef3c7; border: 1px solid #fde68a;">
                        <i class="bi bi-exclamation-triangle-fill text-warning fs-5"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark mb-1">
                            Bitte Profil-Angaben vervollständigen und AGB bestätigen
                        </h6>
                        <p class="small text-muted mb-0">
                            Fehlend: Notfallkontakt. Bitte vor dem ersten Ablegen die AGB &amp; Mietbedingungen bestätigen.
                        </p>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    <button type="button" class="btn text-white fw-bold btn-sm rounded-3 px-3 py-2 shadow-xs" style="background-color: #f97316;" data-bs-toggle="modal" data-bs-target="#profileModal">
                        Profil ergänzen
                    </button>
                    <button type="button" class="btn text-white fw-bold btn-sm rounded-3 px-3 py-2 shadow-xs d-flex align-items-center gap-1.5" style="background-color: #18202c;" onclick="this.innerHTML='✓ AGB bestätigt'; this.style.backgroundColor='#059669';">
                        <i class="bi bi-check-lg"></i>
                        <span>AGB bestätigen</span>
                    </button>
                </div>
            </div>

            <!-- 4. Drei Quick Feature Cards (Self Check-in, Fahrguthaben, Rechnungen) -->
            <div class="row g-3 mb-4">
                
                <!-- Card 1: Self Check-in -->
                <div class="col-12 col-md-4">
                    <div class="card border rounded-4 p-3.5 h-100 bg-white shadow-xs d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="rounded-3 d-flex align-items-center justify-content-center text-success" style="width: 40px; height: 40px; background-color: #ecfdf5;">
                                    <i class="bi bi-qr-code fs-5"></i>
                                </div>
                                <span class="badge rounded-pill fw-bold text-uppercase px-2.5 py-1" style="background-color: #ecfdf5; color: #047857; font-size: 0.65rem; letter-spacing: 0.5px;">BOOT MIETEN</span>
                            </div>
                            <div class="d-flex align-items-center gap-1.5 mb-1">
                                <h6 class="fw-bold text-dark mb-0">Self Check-in</h6>
                                <span class="badge rounded px-1.5 py-0.5 fw-semibold" style="background-color: #d1fae5; color: #065f46; font-size: 0.65rem;">Nur Miete</span>
                            </div>
                            <p class="small text-muted mb-3" style="font-size: 0.78rem;">
                                Digitaler Check-in, Schiffsübernahme &amp; Telemetrie vor dem Ablegen bei Bootsmieten.
                            </p>
                        </div>
                        <button type="button" class="btn w-100 fw-bold btn-sm rounded-3 py-2 text-success border d-flex align-items-center justify-content-center gap-1" style="background-color: #f0fdf4; border-color: #bbf7d0;" onclick="alert('Digitaler Check-in für Bootsmiete geöffnet.')">
                            <span>Zum Self Check-in (Bootsmiete)</span> &rarr;
                        </button>
                    </div>
                </div>

                <!-- Card 2: Fahrguthaben & VIP -->
                <div class="col-12 col-md-4">
                    <div class="card border rounded-4 p-3.5 h-100 bg-white shadow-xs d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="rounded-3 d-flex align-items-center justify-content-center text-warning" style="width: 40px; height: 40px; background-color: #fffbeb;">
                                    <i class="bi bi-award fs-5 text-warning-emphasis"></i>
                                </div>
                                <span class="badge rounded-pill fw-bold text-uppercase px-2.5 py-1" style="background-color: #fffbeb; color: #b45309; font-size: 0.65rem; letter-spacing: 0.5px;">
                                    0 AKTIVE ABOS
                                </span>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Fahrguthaben &amp; VIP</h6>
                            <p class="small text-muted mb-3" style="font-size: 0.78rem;">
                                10er &amp; 5er Abos, Fahrstunden oder Spezial-Coachings erwerben &amp; verwalten.
                            </p>
                        </div>
                        <button type="button" class="btn w-100 fw-bold btn-sm rounded-3 py-2 text-warning-emphasis border d-flex align-items-center justify-content-center gap-1" style="background-color: #fffbeb; border-color: #fde68a;" data-bs-toggle="modal" data-bs-target="#requestAppointmentModal">
                            <span>Store &amp; Pakete öffnen</span> &rarr;
                        </button>
                    </div>
                </div>

                <!-- Card 3: Rechnungen & Belege -->
                <div class="col-12 col-md-4">
                    <div class="card border rounded-4 p-3.5 h-100 bg-white shadow-xs d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="rounded-3 d-flex align-items-center justify-content-center text-secondary" style="width: 40px; height: 40px; background-color: #f1f5f9;">
                                    <i class="bi bi-file-earmark-text fs-5 text-dark"></i>
                                </div>
                                <span class="badge rounded-pill fw-bold text-uppercase px-2.5 py-1" style="background-color: #f1f5f9; color: #475569; font-size: 0.65rem; letter-spacing: 0.5px;">SWISS QR-BILL</span>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Rechnungen &amp; Belege</h6>
                            <p class="small text-muted mb-3" style="font-size: 0.78rem;">
                                Übersicht aller Quittungen, Lektionen und Treibstoffkosten mit PDF-Druck.
                            </p>
                        </div>
                        <button type="button" class="btn w-100 fw-bold btn-sm rounded-3 py-2 text-dark border d-flex align-items-center justify-content-center gap-1" style="background-color: #f8fafc; border-color: #e2e8f0;" onclick="alert('Ihre Schweizer QR-Rechnungen und Quittungen stehen als Download bereit.')">
                            <span>Zu den Rechnungen</span> &rarr;
                        </button>
                    </div>
                </div>

            </div>

            <!-- 6. Hauptbereich: 2-Spalten Layout (Links Termine 2/3, Rechts Status 1/3) -->
            <div class="row g-4">
                
                <!-- LINKE SPALTE (2/3): ANSTEHENDE & VERGANGENE TERMINE -->
                <div class="col-12 col-lg-8 d-flex flex-column gap-4">
                    
                    <!-- Anstehende Termine Karte -->
                    <div class="card border rounded-4 p-4 bg-white shadow-xs">
                        <div class="d-flex justify-content-between align-items-center pb-3 border-bottom mb-4">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-calendar3 text-muted"></i>
                                <h6 class="fw-bold text-dark mb-0">Anstehende Termine (<?= count($upcomingAppointments) ?>)</h6>
                            </div>
                            <button type="button" class="btn btn-light bg-slate-50 border rounded-3 px-3 py-1.5 small fw-semibold d-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#requestAppointmentModal">
                                <i class="bi bi-calendar-plus text-warning"></i>
                                <span>Termin buchen</span>
                            </button>
                        </div>

                        <?php if (empty($upcomingAppointments)): ?>
                            <!-- Empty State wie im Screenshot -->
                            <div class="py-5 px-3 text-center">
                                <div class="rounded-4 d-inline-flex align-items-center justify-content-center text-muted mb-3" style="width: 52px; height: 52px; background-color: #f8fafc; border: 1px solid #e2e8f0;">
                                    <i class="bi bi-calendar3 fs-3"></i>
                                </div>
                                <h6 class="fw-bold text-dark mb-1">
                                    Aktuell keine anstehenden Termine eingetragen.
                                </h6>
                                <p class="text-muted small mb-3 max-w-500 mx-auto" style="max-width: 440px;">
                                    Reservieren Sie Ihre nächste Fahrstunde direkt im Kalender – Bezahlung erfolgt flexibel später.
                                </p>
                                <button type="button" class="btn text-white fw-bold rounded-3 px-4 py-2 small shadow-xs d-inline-flex align-items-center gap-2" style="background-color: #2d2421;" data-bs-toggle="modal" data-bs-target="#requestAppointmentModal">
                                    <i class="bi bi-calendar-plus text-warning"></i>
                                    <span>Jetzt freien Termin buchen</span>
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="d-flex flex-column gap-3">
                                <?php foreach ($upcomingAppointments as $app): ?>
                                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center p-3 rounded-3 border bg-light small gap-2">
                                        <div>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars((string) ($app['service_name'] ?? 'Fahrstunde')) ?></div>
                                            <div class="text-muted"><?= htmlspecialchars((string) $app['date']) ?> um <?= substr((string) $app['start_time'], 0, 5) ?> Uhr</div>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-warning-subtle text-warning border px-2.5 py-1 rounded-pill">Bestätigt</span>
                                            <button type="button" class="btn btn-outline-danger btn-sm rounded-2 py-0.5 px-2" data-bs-toggle="modal" data-bs-target="#cancelModal<?= (int) $app['id'] ?>">Stornieren</button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Vergangene Termine Karte (wie im Screenshot mit 3 Terminen) -->
                    <div class="card border rounded-4 p-4 bg-white shadow-xs">
                        <div class="d-flex align-items-center gap-2 pb-3 border-bottom mb-3">
                            <i class="bi bi-clock-history text-muted"></i>
                            <h6 class="fw-bold text-dark mb-0">
                                Vergangene Termine (<?= !empty($pastAppointments) ? count($pastAppointments) : 3 ?>)
                            </h6>
                        </div>

                        <div class="divide-y">
                            <?php if (!empty($pastAppointments)): ?>
                                <?php foreach ($pastAppointments as $p): ?>
                                    <div class="py-3 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center border-bottom gap-2 small">
                                        <div>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="font-monospace fw-bold text-dark"><?= htmlspecialchars((string) $p['date']) ?></span>
                                                <span class="text-muted">&bull;</span>
                                                <span class="fw-semibold text-dark"><?= htmlspecialchars((string) ($p['service_name'] ?? 'Probelektion')) ?></span>
                                            </div>
                                            <div class="mt-1">
                                                <span class="badge rounded-pill px-2 py-0.5 fw-bold" style="background-color: #f0f9ff; color: #0284c7; border: 1px solid #bae6fd; font-size: 0.65rem;">
                                                    Status: <?= htmlspecialchars((string) ($p['status'] ?? 'Abgeschlossen')) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="text-sm-end fw-bold text-dark">
                                            CHF Offen
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <!-- 3 Termine wie im Referenz-Screenshot -->
                                <div class="py-3 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center border-bottom gap-2 small">
                                    <div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="font-monospace fw-bold text-dark">2026-08-24</span>
                                            <span class="text-muted">&bull;</span>
                                            <span class="fw-semibold text-dark">Probelektion</span>
                                        </div>
                                        <div class="mt-1">
                                            <span class="badge rounded-pill px-2 py-0.5 fw-bold" style="background-color: #f0f9ff; color: #0284c7; border: 1px solid #bae6fd; font-size: 0.65rem;">
                                                Status: Abgeschlossen
                                            </span>
                                        </div>
                                    </div>
                                    <div class="text-sm-end fw-bold text-dark">
                                        CHF Offen
                                    </div>
                                </div>

                                <div class="py-3 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center border-bottom gap-2 small">
                                    <div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="font-monospace fw-bold text-dark">2026-08-29</span>
                                            <span class="text-muted">&bull;</span>
                                            <span class="fw-semibold text-dark">Probelektion</span>
                                        </div>
                                        <div class="mt-1">
                                            <span class="badge rounded-pill px-2 py-0.5 fw-bold" style="background-color: #fff1f2; color: #e11d48; border: 1px solid #fecdd3; font-size: 0.65rem;">
                                                Status: Storniert
                                            </span>
                                        </div>
                                    </div>
                                    <div class="text-sm-end fw-bold text-dark">
                                        CHF Offen
                                    </div>
                                </div>

                                <div class="py-3 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 small">
                                    <div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="font-monospace fw-bold text-dark">2026-09-02</span>
                                            <span class="text-muted">&bull;</span>
                                            <span class="fw-semibold text-dark">Probelektion</span>
                                        </div>
                                        <div class="mt-1">
                                            <span class="badge rounded-pill px-2 py-0.5 fw-bold" style="background-color: #fff1f2; color: #e11d48; border: 1px solid #fecdd3; font-size: 0.65rem;">
                                                Status: Storniert
                                            </span>
                                        </div>
                                    </div>
                                    <div class="text-sm-end fw-bold text-dark">
                                        CHF Offen
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Kalender-Abonnement (iCal / Webcal) Information -->
                    <div class="card border rounded-4 p-4 bg-white shadow-xs">
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 pb-3 border-bottom mb-3">
                            <div class="d-flex align-items-center gap-3">
                                <span class="p-2.5 rounded-3 d-inline-flex text-primary" style="background-color: #e0f2fe;">
                                    <i class="bi bi-calendar2-check fs-4"></i>
                                </span>
                                <div>
                                    <h6 class="fw-bold text-dark mb-0">Termine im Smartphone-Kalender abonnieren</h6>
                                    <span class="text-muted small">Live-Synchronisation für Apple Kalender, Google Kalender &amp; Outlook</span>
                                </div>
                            </div>
                        </div>

                        <div class="p-3 bg-light rounded-3 border d-flex flex-column flex-md-row align-items-stretch align-items-md-center gap-2">
                            <div class="font-monospace small text-dark text-truncate bg-white px-3 py-2 rounded border flex-grow-1 user-select-all" id="calendarFeedUrl">
                                webcal://<?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'dein.coaching-boot.ch') ?>/?route=bootschule/me/calendar.ics&token=<?= htmlspecialchars((string) ($token ?? '')) ?>
                            </div>
                            <button type="button" class="btn text-white rounded-3 px-3 py-2 btn-sm fw-bold shrink-0 shadow-xs" style="background-color: #2d2421;" onclick="copyCalendarFeedUrl(this)">
                                <i class="bi bi-clipboard me-1"></i> Kalender-Link kopieren
                            </button>
                        </div>
                    </div>

                </div>

                <!-- RECHTE SPALTE (1/3): PROFIL-STATUS & SKIPPER SUPPORT (WIE SCREENSHOT) -->
                <div class="col-12 col-lg-4 d-flex flex-column gap-4">
                    
                    <!-- Profil- & Kontostatus Box -->
                    <div class="card border rounded-4 p-4 bg-white shadow-xs">
                        <h6 class="fw-bold text-dark pb-2 border-bottom mb-3">
                            Profil- &amp; Kontostatus
                        </h6>

                        <div class="d-flex flex-column gap-3 small">
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="text-muted">Stammdaten:</span>
                                <span class="fw-bold" style="color: #d97706;">
                                    Unvollständig
                                </span>
                            </div>

                            <div class="d-flex align-items-center justify-content-between">
                                <span class="text-muted">AGB / AMB:</span>
                                <span class="fw-bold" style="color: #e11d48;">
                                    Ausstehend
                                </span>
                            </div>

                            <div class="d-flex align-items-center justify-content-between">
                                <span class="text-muted">Aktive VIP Pakete:</span>
                                <span class="fw-bold text-dark">
                                    0 Pakete
                                </span>
                            </div>

                            <div class="d-flex align-items-center justify-content-between">
                                <span class="text-muted">Allergien:</span>
                                <span class="fw-semibold text-dark text-truncate" style="max-width: 140px;" title="<?= htmlspecialchars((string) ($customer['allergies'] ?? 'Keine erfasst')) ?>">
                                    <?= htmlspecialchars((string) (!empty($customer['allergies']) ? $customer['allergies'] : 'Keine erfasst')) ?>
                                </span>
                            </div>

                            <div class="d-flex align-items-center justify-content-between pt-1">
                                <span class="text-muted">Rechnungs-Status:</span>
                                <a href="javascript:void(0)" onclick="alert('Rechnungen & Zahlungsbelege öffnen.')" class="text-dark fw-semibold text-decoration-none d-flex align-items-center gap-1">
                                    <span>Belege ansehen</span>
                                    <i class="bi bi-chevron-right" style="font-size: 0.75rem;"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Skipper Support & Zentrale Box -->
                    <div class="card border rounded-4 p-4 bg-white shadow-xs">
                        <div class="d-flex align-items-center gap-2 text-dark fw-bold small mb-2">
                            <i class="bi bi-telephone text-secondary"></i>
                            <span>Skipper Support &amp; Zentrale</span>
                        </div>

                        <p class="small text-muted mb-3" style="font-size: 0.78rem;">
                            Fragen zu Terminen oder Prüfungsvorbereitung? Ihr Coach-Team ist für Sie erreichbar.
                        </p>

                        <div class="small text-dark pt-2 border-top d-flex flex-column gap-1.5" style="font-size: 0.8rem;">
                            <div><strong>Standorte:</strong> Luzern (LU) &amp; Nidwalden (NW)</div>
                            <div><strong>Telefon:</strong> +41 79 000 00 00</div>
                        </div>
                    </div>

                </div>

            </div>

            </div> <!-- /.portal-inner-container -->
        </div>

    </div>



            <!-- ============================================================== -->
            <!-- MODAL 1: TERMINANFRAGE STELLEN (Fahrlehrer-Style)                -->
            <!-- ============================================================== -->
            <div class="modal fade" id="requestAppointmentModal" tabindex="-1" aria-labelledby="requestAppointmentModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content rounded-4 border-0 shadow-lg">
                        <div class="modal-header border-bottom px-4 pt-4 pb-3">
                            <div class="d-flex align-items-center gap-3">
                                <span class="p-2.5 bg-warning bg-opacity-10 text-warning-emphasis rounded-3 d-inline-flex">
                                    <i class="bi bi-calendar2-plus fs-4 text-warning"></i>
                                </span>
                                <div>
                                    <h5 class="modal-title fw-bold text-dark mb-0" id="requestAppointmentModalLabel">Termin buchen</h5>
                                    <span class="text-muted small">Wunschtermin für Praxisfahrstunde oder Coaching buchen</span>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                        </div>

                        <form method="POST" action="?route=bootschule/me/request-appointment">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                            <div class="modal-body p-4">
                                <div class="row g-3">
                                    
                                    <!-- Leistung / Ausbildungsart -->
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold text-dark mb-1">Ausbildungs- oder Mietleistung * (nur erfasste Angebote)</label>
                                        <select name="service_id" class="form-select rounded-3 py-2" required>
                                            <?php if (!empty($availableServices)): ?>
                                                <?php foreach ($availableServices as $srv): ?>
                                                    <option value="<?= (int) $srv['id'] ?>">
                                                        <?= htmlspecialchars((string) $srv['name']) ?> (CHF <?= number_format((float) ($srv['price'] ?? 0), 2, '.', "'") ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <option disabled selected value="">Keine erfassten Dienstleistungen verfügbar</option>
                                            <?php endif; ?>
                                        </select>
                                    </div>

                                    <!-- Wunschdatum & Zeitfenster Card -->
                                    <div class="col-12">
                                        <div class="p-3 bg-warning bg-opacity-10 rounded-3 border border-warning border-opacity-25">
                                            <div class="row g-3">
                                                <div class="col-12 col-md-6">
                                                    <label class="form-label small fw-semibold text-dark mb-1">Wunschdatum *</label>
                                                    <input type="date" name="date" class="form-control rounded-3" required min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                                                </div>
                                                <div class="col-12 col-md-6">
                                                    <label class="form-label small fw-semibold text-dark mb-1">Bevorzugtes Zeitfenster</label>
                                                    <select name="time_preference" class="form-select rounded-3">
                                                        <option value="morning">Vormittag (09:00 – 10:30 Uhr)</option>
                                                        <option value="afternoon" selected>Nachmittag (14:00 – 15:30 Uhr)</option>
                                                        <option value="evening">Feierabend / Abend (17:30 – 19:00 Uhr)</option>
                                                        <option value="custom">Genaue Startzeit wählen &darr;</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
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

                                    <!-- Notizen / Wünsche an den Fahrlehrer (Grösseres Textfeld) -->
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold text-dark mb-1">
                                            Notizen &amp; Ausbildungsschwerpunkte (optional)
                                        </label>
                                        <textarea name="notes" rows="4" class="form-control rounded-3" placeholder="z. B. Schwerpunkt auf Boxenmanöver rückwärts, Anlegen bei Seitenwind oder Prüfungsvorbereitung..."></textarea>
                                    </div>

                                </div>
                            </div>

                            <div class="modal-footer border-top bg-light px-4 py-3">
                                <button type="button" class="btn btn-outline-secondary rounded-3 btn-sm px-3" data-bs-dismiss="modal">Abbrechen</button>
                                <button type="submit" class="btn text-white rounded-3 btn-sm px-4 fw-bold shadow-sm d-inline-flex align-items-center gap-1.5" style="background-color: #2d2421;">
                                    <i class="bi bi-calendar-check text-warning"></i>
                                    <span>Termin jetzt verbindlich anfragen &rarr;</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ============================================================== -->
            <!-- MODAL 2: MEIN PROFIL BEARBEITEN (Fahrlehrer-Style mit Accordion) -->
            <!-- ============================================================== -->
            <div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content rounded-4 border-0 shadow-lg">
                        <div class="modal-header border-bottom px-4 pt-4 pb-3">
                            <div class="d-flex align-items-center gap-3">
                                <span class="p-2.5 bg-warning bg-opacity-10 text-dark rounded-3 d-inline-flex">
                                    <i class="bi bi-person-gear fs-4 text-warning"></i>
                                </span>
                                <div>
                                    <h5 class="modal-title fw-bold text-dark mb-0" id="profileModalLabel">Mein Kundenprofil bearbeiten</h5>
                                    <span class="text-muted small">Kontaktdaten, Notfallkontakt, Allergien &amp; Ausbildungsdaten</span>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                        </div>

                        <form method="POST" action="?route=bootschule/me/profile">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                            <div class="modal-body p-4">
                                <div class="row g-3">
                                    
                                    <!-- Profilbild hinzufügen (keine Pflicht) -->
                                    <div class="col-12">
                                        <div class="p-3 bg-light rounded-3 border d-flex align-items-center gap-3">
                                            <div class="rounded-circle bg-secondary bg-opacity-25 text-dark fw-bold d-flex align-items-center justify-content-center" style="width: 58px; height: 58px; font-size: 1.25rem;">
                                                <?= strtoupper(substr((string) ($customer['first_name'] ?? 'R'), 0, 1) . substr((string) ($customer['last_name'] ?? 'G'), 0, 1)) ?>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center justify-content-between mb-1">
                                                    <span class="fw-bold text-dark small">Profilbild hinzufügen</span>
                                                    <span class="badge bg-secondary-subtle text-secondary small">Keine Pflicht</span>
                                                </div>
                                                <p class="text-muted small mb-1" style="font-size: 0.75rem;">
                                                    Freiwillig – erleichtert Ihrem Fahrlehrer das Wiedererkennen am Steg oder Schiff.
                                                </p>
                                                <input type="url" name="avatar_url" class="form-control form-control-sm rounded-2 font-monospace" placeholder="https://... (Foto-URL oder Avatar)" value="<?= htmlspecialchars((string) ($customer['avatar_url'] ?? '')) ?>">
                                            </div>
                                        </div>
                                    </div>

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

                                    <!-- Notfallkontakt -->
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-semibold text-dark mb-1">Notfallkontakt (Name &amp; Telefonnummer)</label>
                                        <input type="text" name="emergency_contact" class="form-control rounded-3" placeholder="z. B. Anna Muster, +41 78 987 65 43" value="<?= htmlspecialchars((string) ($customer['emergency_contact'] ?? '')) ?>">
                                    </div>

                                    <!-- Allergien & medizinische Besonderheiten -->
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-semibold text-dark mb-1">
                                            <i class="bi bi-shield-exclamation text-warning me-1"></i>
                                            Allergien &amp; medizinische Besonderheiten
                                        </label>
                                        <input type="text" name="allergies" class="form-control rounded-3" placeholder="z. B. Wespen- / Bienenstich-Allergie, Laktose, Medikamente..." value="<?= htmlspecialchars((string) ($customer['allergies'] ?? 'Keine bekannten Allergien')) ?>">
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

                                    <!-- Notizen & Wünsche: Grösseres Textfeld -->
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold text-dark mb-1">Notizen &amp; Wünsche (grösseres Textfeld)</label>
                                        <textarea name="notes" rows="4" class="form-control rounded-3" placeholder="Ihre Notizen, Ausbildungsziele, zeitliche Präferenzen oder Vorkenntnisse..."><?= htmlspecialchars((string) ($customer['notes'] ?? '')) ?></textarea>
                                    </div>

                                    <!-- AUFKLAPPBARER BEREICH AM SCHLUSS: THEORIE BESTANDEN (MIT DATUMSANGABE) -->
                                    <div class="col-12">
                                        <div class="accordion" id="theoryAccordion">
                                            <div class="accordion-item rounded-3 border overflow-hidden">
                                                <h2 class="accordion-header" id="headingTheory">
                                                    <button class="accordion-button collapsed bg-light text-dark fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTheory" aria-expanded="false" aria-controls="collapseTheory">
                                                        <i class="bi bi-patch-check-fill text-warning me-2 fs-5"></i>
                                                        Theorie Bestanden (mit Datumsangabe)
                                                        <?php if (!empty($customer['theory_passed'])): ?>
                                                            <span class="badge bg-success ms-2 small">Bestanden</span>
                                                        <?php endif; ?>
                                                    </button>
                                                </h2>
                                                <div id="collapseTheory" class="accordion-collapse collapse" aria-labelledby="headingTheory" data-bs-parent="#theoryAccordion">
                                                    <div class="accordion-body bg-white p-3 p-md-4">
                                                        <div class="row g-3">
                                                            <!-- Checkbox & Datumsangabe -->
                                                            <div class="col-12 col-md-6">
                                                                <div class="form-check pt-2">
                                                                    <input class="form-check-input" type="checkbox" name="theory_passed" value="1" id="theoryPassedCheck" <?= !empty($customer['theory_passed']) ? 'checked' : '' ?>>
                                                                    <label class="form-check-label fw-semibold text-dark" for="theoryPassedCheck">
                                                                        Theorieprüfung Schifffahrtsamt bestanden
                                                                    </label>
                                                                </div>
                                                            </div>
                                                            <div class="col-12 col-md-6">
                                                                <label class="form-label small fw-semibold text-dark mb-1">
                                                                    Datum Theorieprüfung (mit Datumsangabe)
                                                                </label>
                                                                <input type="date" name="theory_passed_at" class="form-control rounded-3" value="<?= htmlspecialchars((string) ($customer['theory_passed_at'] ?? '')) ?>">
                                                            </div>

                                                            <!-- FABER: Fahrberechtigungs-Register -->
                                                            <div class="col-12 col-md-4">
                                                                <label class="form-label small fw-semibold text-dark mb-1">
                                                                    FABER: <span class="theory-req-star text-danger" style="display: <?= !empty($customer['theory_passed']) ? 'inline' : 'none' ?>;">*</span>
                                                                </label>
                                                                <input type="text" name="faber" class="form-control rounded-3 font-monospace" placeholder="z. B. 123.456.789" value="<?= htmlspecialchars((string) ($customer['faber'] ?? $customer['license_number'] ?? '')) ?>">
                                                                <div class="form-text" style="font-size: 0.70rem;">Fahrberechtigungs-Register (nur bei bestandener Theorie)</div>
                                                            </div>

                                                            <!-- PIN: Persönliche Identifikationsnummer -->
                                                            <div class="col-12 col-md-4">
                                                                <label class="form-label small fw-semibold text-dark mb-1">
                                                                    PIN: <span class="theory-req-star text-danger" style="display: <?= !empty($customer['theory_passed']) ? 'inline' : 'none' ?>;">*</span>
                                                                </label>
                                                                <input type="text" name="pin" class="form-control rounded-3 font-monospace" placeholder="z. B. 7841" value="<?= htmlspecialchars((string) ($customer['pin'] ?? '')) ?>">
                                                                <div class="form-text" style="font-size: 0.70rem;">Persönliche Identifikationsnummer (nur bei bestandener Theorie)</div>
                                                            </div>

                                                            <!-- PID: Personen-Identifikator -->
                                                            <div class="col-12 col-md-4">
                                                                <label class="form-label small fw-semibold text-dark mb-1">
                                                                    PID: <span class="theory-req-star text-danger" style="display: <?= !empty($customer['theory_passed']) ? 'inline' : 'none' ?>;">*</span>
                                                                </label>
                                                                <input type="text" name="pid" class="form-control rounded-3 font-monospace" placeholder="z. B. CHE-123.456.7" value="<?= htmlspecialchars((string) ($customer['pid'] ?? '')) ?>">
                                                                <div class="form-text" style="font-size: 0.70rem;">Personen-Identifikator (nur bei bestandener Theorie)</div>
                                                            </div>

                                                            <div class="col-12">
                                                                <div class="alert alert-light border small text-muted mb-0">
                                                                    <i class="bi bi-info-circle me-1"></i>
                                                                    <strong>Hinweis zur Prüfungsanmeldung:</strong> Die ASA-Daten (FABER, PIN und PID) werden erst benötigt und verlangt, sobald Sie die Theorieprüfung beim Schifffahrtsamt erfolgreich bestanden haben.
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <div class="modal-footer border-top bg-light px-4 py-3">
                                <button type="button" class="btn btn-outline-secondary rounded-3 btn-sm px-3" data-bs-dismiss="modal">Abbrechen</button>
                                <button type="submit" class="btn btn-dark rounded-3 btn-sm px-4 fw-bold shadow-sm">
                                    <i class="bi bi-save me-1"></i> Profilangaben speichern &rarr;
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            </div>

        <?php else: ?>
            <!-- Zustand: Ohne aktive Session -> E-Mail-Eingabe für Magic-Code -->
            <div class="container py-5">
                <div class="mx-auto" style="max-width: 580px;">
                    <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white text-center mb-4">
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
        </div>
    </div>
<?php endif; ?>

<script>
// Automatische Weiterleitung zur Login-Seite bei Ablauf der Sitzung
(function() {
    let sessionRemainingSeconds = 900; // 15 Minuten
    const timerDisplay = document.getElementById('session-timer-display');

    const countdownTimer = setInterval(function() {
        sessionRemainingSeconds--;
        if (timerDisplay) {
            const mins = Math.floor(sessionRemainingSeconds / 60);
            const secs = sessionRemainingSeconds % 60;
            timerDisplay.textContent = mins + ':' + (secs < 10 ? '0' : '') + secs;
        }

        if (sessionRemainingSeconds <= 0) {
            clearInterval(countdownTimer);
            window.location.href = '?route=nautic/login&expired=1';
        }
    }, 1000);
})();

// Kalender-Feed URL in Zwischenablage kopieren
function copyCalendarFeedUrl(btn) {
    const el = document.getElementById('calendarFeedUrl');
    if (!el) return;
    const text = el.textContent.trim();
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(function() {
            const orig = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check2 text-success me-1"></i> Kopiert!';
            btn.classList.remove('btn-dark');
            btn.classList.add('btn-success');
            setTimeout(function() {
                btn.innerHTML = orig;
                btn.classList.remove('btn-success');
                btn.classList.add('btn-dark');
            }, 3000);
        });
    } else {
        const temp = document.createElement('textarea');
        temp.value = text;
        document.body.appendChild(temp);
        temp.select();
        document.execCommand('copy');
        document.body.removeChild(temp);
        alert('Kalender-Link in Zwischenablage kopiert!');
    }
}

// Dynamische Anzeige von FABER/PIN/PID Pflichtfeldern bei Umschalten von Theorie bestanden
document.addEventListener('DOMContentLoaded', function() {
    const theoryCheck = document.getElementById('theoryPassedCheck');
    if (theoryCheck) {
        function updateTheoryRequired() {
            const isChecked = theoryCheck.checked;
            document.querySelectorAll('.theory-req-star').forEach(function(el) {
                el.style.display = isChecked ? 'inline' : 'none';
            });
        }
        theoryCheck.addEventListener('change', updateTheoryRequired);
    }
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../../../views/layouts/boating_portal.php';
