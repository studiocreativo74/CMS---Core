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
                <!-- Nautisches Emblem Logo im abgerundeten Kasten (Coaching Boot) -->
                <div class="text-center mb-4 mt-2">
                    <a href="?route=bootschule/me/appointments" class="d-inline-flex align-items-center justify-content-center text-decoration-none" title="Zum Coaching Boot Kundenportal">
                        <svg viewBox="0 0 400 400" style="width: 80px; height: 80px; border-radius: 16px; box-shadow: 0 6px 18px rgba(0, 0, 0, 0.4); border: 1px solid rgba(255, 255, 255, 0.15);" role="img" aria-label="Coaching Boot Logo">
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
                        <div class="fw-black text-uppercase small text-warning" style="letter-spacing: 1px; font-size: 0.72rem;">COACHING BOOT</div>
                        <div class="text-white-50" style="font-size: 0.65rem;">Vierwaldstättersee • Kundenbereich</div>
                    </div>
                </div>

                <!-- Navigation Menu Items -->
                <nav class="d-flex flex-column gap-1.5 small font-medium">
                    <a href="?route=bootschule/me/appointments" class="sidebar-nav-link active">
                        <span class="d-flex align-items-center gap-2.5">
                            <i class="bi bi-grid-fill nav-icon" style="color: #fbbf24 !important;"></i>
                            <span>Dashboard</span>
                        </span>
                        <span class="rounded-circle" style="width: 7px; height: 7px; background-color: #fbbf24; box-shadow: 0 0 6px #fbbf24;"></span>
                    </a>

                    <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#requestAppointmentModal" class="sidebar-nav-link">
                        <span class="d-flex align-items-center gap-2.5">
                            <i class="bi bi-calendar3 nav-icon" style="color: #38bdf8 !important;"></i>
                            <span>Termine &amp; Kalender</span>
                        </span>
                    </a>

                    <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#selfCheckinModal" class="sidebar-nav-link">
                        <span class="d-flex align-items-center gap-2.5">
                            <i class="bi bi-qr-code nav-icon" style="color: #34d399 !important;"></i>
                            <span>Self Checkin (Miete)</span>
                        </span>
                        <span class="badge rounded-pill fw-bold" style="background-color: #064e3b; color: #a7f3d0; font-size: 0.6rem; padding: 2px 6px;">Miete</span>
                    </a>

                    <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#storeOffersModal" class="sidebar-nav-link">
                        <span class="d-flex align-items-center gap-2.5">
                            <i class="bi bi-award-fill nav-icon" style="color: #fbbf24 !important;"></i>
                            <span>Store &amp; Abos</span>
                        </span>
                    </a>

                    <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#elearningModal" class="sidebar-nav-link">
                        <span class="d-flex align-items-center gap-2.5">
                            <i class="bi bi-play-circle-fill nav-icon" style="color: #22d3ee !important;"></i>
                            <span>E-Learning &amp; Videos</span>
                        </span>
                        <span class="badge rounded-pill fw-bold" style="background-color: #164e63; color: #67e8f9; font-size: 0.6rem; padding: 2px 6px;">Neu</span>
                    </a>

                    <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#invoicesModal" class="sidebar-nav-link">
                        <span class="d-flex align-items-center gap-2.5">
                            <i class="bi bi-file-earmark-text-fill nav-icon" style="color: #c084fc !important;"></i>
                            <span>Meine Rechnungen</span>
                        </span>
                    </a>

                    <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#legalModal" class="sidebar-nav-link">
                        <span class="d-flex align-items-center gap-2.5">
                            <i class="bi bi-shield-check nav-icon" style="color: #818cf8 !important;"></i>
                            <span>Rechtliches &amp; Nachweise</span>
                        </span>
                    </a>

                    <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#profileModal" class="sidebar-nav-link">
                        <span class="d-flex align-items-center gap-2.5">
                            <i class="bi bi-person-circle nav-icon" style="color: #cbd5e1 !important;"></i>
                            <span>Mein Profil</span>
                        </span>
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
        <div class="flex-grow-1 p-3 p-md-4 p-lg-5 overflow-y-auto" style="background-color: #ede5dc;">
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
                        <span>Hier ist deine Kunden-Übersicht für heute.</span>
                        <span>&bull;</span>
                        <span class="d-flex align-items-center gap-1">
                            <i class="bi bi-envelope text-muted"></i>
                            <?= htmlspecialchars((string) ($customer['email'] ?? 'office@studiocreativo.ch')) ?>
                        </span>
                    </p>
                </div>

                <div class="d-flex align-items-center gap-2.5">
                    <button type="button" class="btn btn-light border rounded-3 px-3.5 py-2 small fw-semibold shadow-xs d-flex align-items-center gap-1.5" style="background-color: #f6f0e7; border-color: #e3d6c5;" data-bs-toggle="modal" data-bs-target="#profileModal">
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
                    <div class="card border rounded-4 p-3.5 shadow-xs d-flex flex-row align-items-center justify-content-between" style="background-color: #f6f0e7; border: 1.5px solid #e3d6c5;">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-3 d-flex align-items-center justify-content-center text-secondary" style="width: 50px; height: 50px; background-color: #eee4d5; border: 1px solid #dfd0bc;">
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
                            <div class="d-flex align-items-center gap-1 justify-content-end mb-1">
                                <button type="button" class="btn btn-sm rounded-2 py-0.5 px-2 fw-bold text-uppercase border shadow-2xs" style="font-size: 0.65rem; background-color: #ffffff; border-color: #ded0be; color: #5a3e32;" data-bs-toggle="modal" data-bs-target="#lakeWeatherModal" onclick="switchLakeStation('luzern');" title="Nautische Seewetter-Details & Sturmwarnung öffnen">
                                    DETAILS
                                </button>
                                <a href="https://search.ch/meteo/luzern" target="_blank" rel="noopener noreferrer" class="btn btn-sm rounded-2 py-0.5 px-2 text-uppercase fw-bold border shadow-2xs" style="font-size: 0.65rem; letter-spacing: 0.5px; background-color: #fcecd7; border-color: #edd5ba; color: #431407;" title="Wetterportal search.ch Meteo für Luzern öffnen">
                                    PROGNOSE <i class="bi bi-box-arrow-up-right ms-0.5" style="font-size: 0.6rem;"></i>
                                </a>
                            </div>
                            <div class="d-flex align-items-center gap-1.5 text-secondary small fw-medium mt-1 justify-content-end">
                                <i class="bi bi-wind text-muted"></i>
                                <span>7.7 km/h</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kachel Hergiswil -->
                <div class="col-12 col-md-6">
                    <div class="card border rounded-4 p-3.5 shadow-xs d-flex flex-row align-items-center justify-content-between" style="background-color: #f6f0e7; border: 1.5px solid #e3d6c5;">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-3 d-flex align-items-center justify-content-center text-secondary" style="width: 50px; height: 50px; background-color: #eee4d5; border: 1px solid #dfd0bc;">
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
                            <div class="d-flex align-items-center gap-1 justify-content-end mb-1">
                                <button type="button" class="btn btn-sm rounded-2 py-0.5 px-2 fw-bold text-uppercase border shadow-2xs" style="font-size: 0.65rem; background-color: #ffffff; border-color: #ded0be; color: #5a3e32;" data-bs-toggle="modal" data-bs-target="#lakeWeatherModal" onclick="switchLakeStation('hergiswil');" title="Nautische Seewetter-Details & Sturmwarnung öffnen">
                                    DETAILS
                                </button>
                                <a href="https://search.ch/meteo/hergiswil" target="_blank" rel="noopener noreferrer" class="btn btn-sm rounded-2 py-0.5 px-2 text-uppercase fw-bold border shadow-2xs" style="font-size: 0.65rem; letter-spacing: 0.5px; background-color: #fcecd7; border-color: #edd5ba; color: #431407;" title="Wetterportal search.ch Meteo für Hergiswil (NW) öffnen">
                                    PROGNOSE <i class="bi bi-box-arrow-up-right ms-0.5" style="font-size: 0.6rem;"></i>
                                </a>
                            </div>
                            <div class="d-flex align-items-center gap-1.5 text-secondary small fw-medium mt-1 justify-content-end">
                                <i class="bi bi-wind text-muted"></i>
                                <span>8.9 km/h</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- 3. Gelbes Warnbanner: Profil vervollständigen & AGB bestätigen -->
            <div class="rounded-4 p-3.5 mb-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 shadow-xs" style="background-color: #f5eedf; border: 1.5px solid #edd5a3;">
                <div class="d-flex align-items-start gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px; background-color: #fef3c7; border: 1px solid #fde68a;">
                        <i class="bi bi-exclamation-triangle-fill text-warning fs-5"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark mb-1">
                            Bitte vervollständige deine Profil-Angaben und bestätige die AGB
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
                    <button type="button" class="btn text-white fw-bold btn-sm rounded-3 px-3 py-2 shadow-xs d-flex align-items-center gap-1.5" style="background-color: #2d2421;" onclick="this.innerHTML='✓ AGB bestätigt'; this.style.backgroundColor='#059669';">
                        <i class="bi bi-check-lg"></i>
                        <span>AGB bestätigen</span>
                    </button>
                </div>
            </div>

            <!-- 4. Drei Quick Feature Cards (Self Check-in, Fahrguthaben, Rechnungen) -->
            <div class="row g-3 mb-4">
                
                <!-- Card 1: Self Check-in -->
                <div class="col-12 col-md-4">
                    <div class="card border rounded-4 p-3.5 h-100 shadow-xs d-flex flex-column justify-content-between" style="background-color: #f6f0e7; border: 1.5px solid #e3d6c5;">
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="rounded-3 d-flex align-items-center justify-content-center text-success" style="width: 40px; height: 40px; background-color: #ecfdf5; border: 1px solid #a7f3d0;">
                                    <i class="bi bi-qr-code fs-5"></i>
                                </div>
                                <span class="badge rounded-pill fw-bold text-uppercase px-2.5 py-1" style="background-color: #ecfdf5; color: #047857; font-size: 0.65rem; letter-spacing: 0.5px;">BOOT MIETEN</span>
                            </div>
                            <div class="d-flex align-items-center gap-1.5 mb-1">
                                <h6 class="fw-bold text-dark mb-0">Self Check-in</h6>
                                <span class="badge rounded px-1.5 py-0.5 fw-semibold" style="background-color: #d1fae5; color: #065f46; font-size: 0.65rem;">Nur Miete</span>
                            </div>
                            <p class="small text-muted mb-3" style="font-size: 0.78rem;">
                                Dein digitaler Check-in, Schiffsübernahme &amp; Telemetrie vor dem Ablegen bei Bootsmieten.
                            </p>
                        </div>
                        <button type="button" class="btn w-100 fw-bold btn-sm rounded-3 py-2 text-success border d-flex align-items-center justify-content-center gap-1 shadow-2xs" style="background-color: #f0fdf4; border-color: #bbf7d0;" data-bs-toggle="modal" data-bs-target="#selfCheckinModal">
                            <span>Zum Self Check-in (Bootsmiete)</span> &rarr;
                        </button>
                    </div>
                </div>

                <!-- Card 2: Fahrguthaben & VIP -->
                <div class="col-12 col-md-4">
                    <div class="card border rounded-4 p-3.5 h-100 shadow-xs d-flex flex-column justify-content-between" style="background-color: #f6f0e7; border: 1.5px solid #e3d6c5;">
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="rounded-3 d-flex align-items-center justify-content-center text-warning" style="width: 40px; height: 40px; background-color: #fffbeb; border: 1px solid #fde68a;">
                                    <i class="bi bi-award fs-5 text-warning-emphasis"></i>
                                </div>
                                <span class="badge rounded-pill fw-bold text-uppercase px-2.5 py-1" style="background-color: #fffbeb; color: #b45309; font-size: 0.65rem; letter-spacing: 0.5px;">
                                    0 AKTIVE ABOS
                                </span>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Fahrguthaben &amp; VIP</h6>
                            <p class="small text-muted mb-3" style="font-size: 0.78rem;">
                                Deine 10er &amp; 5er Abos, Fahrstunden oder Spezial-Coachings erwerben &amp; verwalten.
                            </p>
                        </div>
                        <button type="button" class="btn w-100 fw-bold btn-sm rounded-3 py-2 text-warning-emphasis border d-flex align-items-center justify-content-center gap-1 shadow-2xs" style="background-color: #fffbeb; border-color: #fde68a;" data-bs-toggle="modal" data-bs-target="#storeOffersModal">
                            <span>Store &amp; Pakete öffnen</span> &rarr;
                        </button>
                    </div>
                </div>

                <!-- Card 3: Rechnungen & Belege -->
                <div class="col-12 col-md-4">
                    <div class="card border rounded-4 p-3.5 h-100 shadow-xs d-flex flex-column justify-content-between" style="background-color: #f6f0e7; border: 1.5px solid #e3d6c5;">
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="rounded-3 d-flex align-items-center justify-content-center text-secondary" style="width: 40px; height: 40px; background-color: #ede4d5; border: 1px solid #dfd0bc;">
                                    <i class="bi bi-file-earmark-text fs-5 text-dark"></i>
                                </div>
                                <span class="badge rounded-pill fw-bold text-uppercase px-2.5 py-1" style="background-color: #ede4d5; color: #475569; font-size: 0.65rem; letter-spacing: 0.5px;">SWISS QR-BILL</span>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Rechnungen &amp; Belege</h6>
                            <p class="small text-muted mb-3" style="font-size: 0.78rem;">
                                Übersicht all deiner Quittungen, Lektionen und Treibstoffkosten mit PDF-Druck.
                            </p>
                        </div>
                        <button type="button" class="btn w-100 fw-bold btn-sm rounded-3 py-2 text-dark border d-flex align-items-center justify-content-center gap-1 shadow-2xs" style="background-color: #eee4d5; border-color: #dfd0bc;" data-bs-toggle="modal" data-bs-target="#invoicesModal">
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
                    <div class="card border rounded-4 p-4 shadow-xs" style="background-color: #f6f0e7; border: 1.5px solid #e3d6c5;">
                        <div class="d-flex justify-content-between align-items-center pb-3 border-bottom mb-4" style="border-color: #e5dacb !important;">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-calendar3 text-muted"></i>
                                <h6 class="fw-bold text-dark mb-0">Anstehende Termine (<?= count($upcomingAppointments) ?>)</h6>
                            </div>
                            <button type="button" class="btn btn-light border rounded-3 px-3 py-1.5 small fw-semibold d-flex align-items-center gap-1.5" style="background-color: #eee4d5; border-color: #dfd0bc;" data-bs-toggle="modal" data-bs-target="#requestAppointmentModal">
                                <i class="bi bi-calendar-plus text-warning"></i>
                                <span>Termin buchen</span>
                            </button>
                        </div>

                        <?php if (empty($upcomingAppointments)): ?>
                            <!-- Empty State wie im Screenshot -->
                            <div class="py-5 px-3 text-center">
                                <div class="rounded-4 d-inline-flex align-items-center justify-content-center text-muted mb-3" style="width: 52px; height: 52px; background-color: #eee4d5; border: 1px solid #dfd0bc;">
                                    <i class="bi bi-calendar3 fs-3 text-secondary"></i>
                                </div>
                                <h6 class="fw-bold text-dark mb-1">
                                    Aktuell keine anstehenden Termine eingetragen.
                                </h6>
                                <p class="text-muted small mb-3 max-w-500 mx-auto" style="max-width: 440px;">
                                    Reserviere deine nächste Fahrstunde direkt im Kalender – Bezahlung erfolgt flexibel später.
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
                    <div class="card border rounded-4 p-4 shadow-xs" style="background-color: #f6f0e7; border: 1.5px solid #e3d6c5;">
                        <div class="d-flex align-items-center gap-2 pb-3 border-bottom mb-3" style="border-color: #e5dacb !important;">
                            <i class="bi bi-clock-history text-muted"></i>
                            <h6 class="fw-bold text-dark mb-0">
                                Vergangene Termine (<?= !empty($pastAppointments) ? count($pastAppointments) : 3 ?>)
                            </h6>
                        </div>

                        <div class="divide-y">
                            <?php if (!empty($pastAppointments)): ?>
                                <?php foreach ($pastAppointments as $p): ?>
                                    <div class="py-3 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center border-bottom gap-2 small" style="border-color: #e5dacb !important;">
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
                                <div class="py-3 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center border-bottom gap-2 small" style="border-color: #e5dacb !important;">
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

                                <div class="py-3 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center border-bottom gap-2 small" style="border-color: #e5dacb !important;">
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

                </div>

                <!-- RECHTE SPALTE (1/3): PROFIL-STATUS, KALENDER-SYNC & SKIPPER SUPPORT -->
                <div class="col-12 col-lg-4 d-flex flex-column gap-4">
                    
                    <!-- Profil- & Kontostatus Box -->
                    <div class="card border rounded-4 p-4 shadow-xs" style="background-color: #f6f0e7; border: 1.5px solid #e3d6c5;">
                        <h6 class="fw-bold text-dark pb-2 border-bottom mb-3" style="border-color: #e5dacb !important;">
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
                                <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#invoicesModal" class="text-dark fw-semibold text-decoration-none d-flex align-items-center gap-1">
                                    <span>Belege ansehen</span>
                                    <i class="bi bi-chevron-right" style="font-size: 0.75rem;"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Kalender-Abonnement (iCal / Webcal) Information -->
                    <div class="card border rounded-4 p-4 shadow-xs" style="background-color: #f6f0e7; border: 1.5px solid #e3d6c5;">
                        <div class="d-flex align-items-center gap-3 pb-3 border-bottom mb-3" style="border-color: #e5dacb !important;">
                            <span class="p-2.5 rounded-3 d-inline-flex text-primary flex-shrink-0" style="background-color: #e0f2fe; border: 1px solid #bae6fd;">
                                <i class="bi bi-calendar2-check fs-5"></i>
                            </span>
                            <div>
                                <h6 class="fw-bold text-dark mb-0 small">Termine im Smartphone-Kalender abonnieren</h6>
                                <span class="text-muted" style="font-size: 0.75rem;">Live-Sync für Apple, Google &amp; Outlook</span>
                            </div>
                        </div>

                        <div class="d-flex flex-column gap-2">
                            <div class="font-monospace text-dark text-truncate bg-white px-2.5 py-2 rounded border user-select-all" style="font-size: 0.72rem; border-color: #d5c5b2 !important;" id="calendarFeedUrl">
                                webcal://<?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'dein.coaching-boot.ch') ?>/?route=bootschule/me/calendar.ics&token=<?= htmlspecialchars((string) ($token ?? '')) ?>
                            </div>
                            <button type="button" class="btn text-white rounded-3 py-2 px-3 btn-sm fw-bold w-100 shadow-xs d-flex align-items-center justify-content-center gap-1.5" style="background-color: #2d2421;" onclick="copyCalendarFeedUrl(this)">
                                <i class="bi bi-clipboard"></i>
                                <span>Kalender-Link kopieren</span>
                            </button>
                        </div>
                    </div>

                    <!-- Skipper Support & Zentrale Box -->
                    <div class="card border rounded-4 p-4 shadow-xs" style="background-color: #f6f0e7; border: 1.5px solid #e3d6c5;">
                        <div class="d-flex align-items-center gap-2 text-dark fw-bold small mb-2">
                            <i class="bi bi-telephone text-secondary"></i>
                            <span>Skipper Support &amp; Zentrale</span>
                        </div>

                        <p class="small text-muted mb-3" style="font-size: 0.78rem;">
                            Fragen zu Terminen oder Prüfungsvorbereitung? Dein Coach-Team ist für dich erreichbar.
                        </p>

                        <div class="small text-dark pt-2 border-top d-flex flex-column gap-2" style="border-color: #e5dacb !important; font-size: 0.8rem;">
                            <div>
                                <span class="fw-bold d-block text-dark mb-1.5">Stützpunkte &amp; Treffpunkte:</span>
                                <div class="d-flex flex-column gap-1.5">
                                    <a href="https://www.google.com/maps/search/?api=1&query=Alpenquai+12+6005+Luzern" target="_blank" rel="noopener noreferrer" class="text-decoration-none text-dark d-flex align-items-center justify-content-between p-2 rounded-3 bg-white shadow-xs" style="border: 1px solid #d5c5b2;">
                                        <div class="d-flex align-items-center gap-1.5 min-w-0">
                                            <i class="bi bi-geo-alt-fill text-danger flex-shrink-0" style="font-size: 0.85rem;"></i>
                                            <div class="text-truncate">
                                                <span class="fw-bold text-dark">Luzern</span>
                                                <span class="text-muted small ms-1">Alpenquai 12</span>
                                            </div>
                                        </div>
                                        <span class="badge text-white px-2 py-1 rounded-2 flex-shrink-0" style="background-color: #2d2421; font-size: 0.65rem;">Maps &nearr;</span>
                                    </a>
                                    <a href="https://www.google.com/maps/search/?api=1&query=Seestrasse+48+6374+Buochs" target="_blank" rel="noopener noreferrer" class="text-decoration-none text-dark d-flex align-items-center justify-content-between p-2 rounded-3 bg-white shadow-xs" style="border: 1px solid #d5c5b2;">
                                        <div class="d-flex align-items-center gap-1.5 min-w-0">
                                            <i class="bi bi-geo-alt-fill text-danger flex-shrink-0" style="font-size: 0.85rem;"></i>
                                            <div class="text-truncate">
                                                <span class="fw-bold text-dark">Buochs</span>
                                                <span class="text-muted small ms-1">Seestrasse 48</span>
                                            </div>
                                        </div>
                                        <span class="badge text-white px-2 py-1 rounded-2 flex-shrink-0" style="background-color: #2d2421; font-size: 0.65rem;">Maps &nearr;</span>
                                    </a>
                                    <a href="https://www.google.com/maps/search/?api=1&query=Seestrasse+20+6052+Hergiswil" target="_blank" rel="noopener noreferrer" class="text-decoration-none text-dark d-flex align-items-center justify-content-between p-2 rounded-3 bg-white shadow-xs" style="border: 1px solid #d5c5b2;">
                                        <div class="d-flex align-items-center gap-1.5 min-w-0">
                                            <i class="bi bi-geo-alt-fill text-danger flex-shrink-0" style="font-size: 0.85rem;"></i>
                                            <div class="text-truncate">
                                                <span class="fw-bold text-dark">Hergiswil</span>
                                                <span class="text-muted small ms-1">Seestrasse 20</span>
                                            </div>
                                        </div>
                                        <span class="badge text-white px-2 py-1 rounded-2 flex-shrink-0" style="background-color: #2d2421; font-size: 0.65rem;">Maps &nearr;</span>
                                    </a>
                                </div>
                            </div>
                            <div class="pt-1">
                                <strong>Telefon:</strong> <a href="tel:+41790000000" class="text-dark fw-bold text-decoration-none">+41 79 000 00 00</a>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

            </div> <!-- /.portal-inner-container -->
        </div>

    </div>



            <!-- ============================================================== -->
            <!-- MODAL: STORE, VIP-PAKETE & ANGEBOTSÜBERSICHT                    -->
            <!-- ============================================================== -->
            <div class="modal fade" id="storeOffersModal" tabindex="-1" aria-labelledby="storeOffersModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
                    <div class="modal-content rounded-4 border-0 shadow-2xl overflow-hidden" style="background-color: #faf6f0; border: 1.5px solid #e2d5c3;">
                        <div class="modal-header border-bottom px-4 py-3.5" style="background-color: #2d2421; color: #ffffff;">
                            <div class="d-flex align-items-center gap-3">
                                <span class="rounded-3 d-inline-flex align-items-center justify-content-center text-warning" style="width: 44px; height: 44px; background-color: rgba(245, 158, 11, 0.2); border: 1px solid rgba(245, 158, 11, 0.35);">
                                    <i class="bi bi-shop fs-4"></i>
                                </span>
                                <div>
                                    <h5 class="modal-title fw-bold text-white mb-0" id="storeOffersModalLabel">
                                        Store, VIP-Pakete &amp; Ausbildungs-Angebote
                                    </h5>
                                    <span class="text-light text-opacity-75 small">Übersicht aller Lektionen, Mehrfachkarten, Bootsmieten &amp; Coachings</span>
                                </div>
                            </div>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schließen"></button>
                        </div>

                        <div class="modal-body p-4" style="background-color: #faf6f0;">
                            
                            <!-- MWST & UID Information Box -->
                            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 p-3 rounded-3 mb-4 bg-white border" style="border-color: #e2d5c3 !important;">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge rounded-pill fw-bold text-dark px-2.5 py-1" style="background-color: #eee4d5; font-size: 0.7rem;">
                                        MWST-Nr.: CHE-110.497.163 MWST
                                    </span>
                                    <span class="small text-muted" style="font-size: 0.78rem;">
                                        Fahrschule <strong>0.0% MWST</strong> (Art. 21 MWSTG Bildungsleistung) &bull; Bootsvermietung <strong>8.1% MWST</strong>
                                    </span>
                                </div>
                                <div class="text-secondary small fw-medium" style="font-size: 0.75rem;">
                                    Standorte: Luzern Alpenquai &amp; Hergiswil (NW)
                                </div>
                            </div>

                            <!-- Filter-Leiste -->
                            <div class="d-flex flex-wrap gap-2 mb-4 pb-2 border-bottom" style="border-color: #e2d5c3 !important;">
                                <button type="button" class="btn btn-sm rounded-pill px-3 py-1.5 fw-bold bg-dark text-white border-0 store-filter-btn" onclick="filterStoreOffers('all', this);">
                                    Alle Angebote (7)
                                </button>
                                <button type="button" class="btn btn-sm rounded-pill px-3 py-1.5 fw-bold bg-white text-secondary border store-filter-btn" style="border-color: #ded0be !important;" onclick="filterStoreOffers('abo', this);">
                                    VIP &amp; Mehrfach-Abos
                                </button>
                                <button type="button" class="btn btn-sm rounded-pill px-3 py-1.5 fw-bold bg-white text-secondary border store-filter-btn" style="border-color: #ded0be !important;" onclick="filterStoreOffers('fahrschule', this);">
                                    Fahrstunden Kat. A
                                </button>
                                <button type="button" class="btn btn-sm rounded-pill px-3 py-1.5 fw-bold bg-white text-secondary border store-filter-btn" style="border-color: #ded0be !important;" onclick="filterStoreOffers('miete', this);">
                                    Bootsvermietung (Miete)
                                </button>
                                <button type="button" class="btn btn-sm rounded-pill px-3 py-1.5 fw-bold bg-white text-secondary border store-filter-btn" style="border-color: #ded0be !important;" onclick="filterStoreOffers('coaching', this);">
                                    Spezial-Coaching
                                </button>
                                <button type="button" class="btn btn-sm rounded-pill px-3 py-1.5 fw-bold bg-white text-secondary border store-filter-btn" style="border-color: #ded0be !important;" onclick="filterStoreOffers('digital', this);">
                                    E-Learning (1-Jahres-Abo)
                                </button>
                            </div>

                            <!-- Angebote Grid -->
                            <div class="row g-4" id="storeOffersGrid">
                                
                                <!-- Angebot 1: 10er VIP Abo Motorboot Kat. A -->
                                <div class="col-12 col-md-6 col-lg-4 store-offer-card" data-category="abo fahrschule">
                                    <div class="card h-100 rounded-4 border bg-white shadow-2xs overflow-hidden d-flex flex-column justify-content-between" style="border-color: #e2d5c3 !important;">
                                        <div class="p-3.5">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="badge rounded-pill text-dark fw-bold text-uppercase px-2.5 py-1" style="background-color: #fde68a; font-size: 0.65rem;">
                                                    10er VIP Abo
                                                </span>
                                                <span class="small text-muted fw-semibold" style="font-size: 0.72rem;">
                                                    <i class="bi bi-clock me-1"></i>10 x 90 Min.
                                                </span>
                                            </div>
                                            <h6 class="fw-bold text-dark mb-1">10er VIP Abo Motorboot Kat. A</h6>
                                            <p class="text-muted small mb-3" style="font-size: 0.78rem;">
                                                Das Rundum-Sorglos-Paket für deine amtliche Schiffsführerprüfung Kat. A. Beinhaltet 10 intensive Praxisfahrstunden, Hafen- &amp; Boxenmanöver, Boje über Bord sowie Prüfungsbegleitung. Inkl. Treibstoff &amp; Versicherung.
                                            </p>
                                            <div class="d-flex flex-wrap gap-1 mb-2">
                                                <span class="badge rounded-pill bg-light text-secondary border" style="font-size: 0.65rem;">Luzern &amp; Hergiswil</span>
                                                <span class="badge rounded-pill bg-light text-secondary border" style="font-size: 0.65rem;">Gültig 24 Monate</span>
                                            </div>
                                        </div>
                                        <div class="p-3.5 pt-2 border-top bg-light bg-opacity-50" style="border-color: #f1e9dd !important;">
                                            <div class="d-flex justify-content-between align-items-center mb-2.5">
                                                <div>
                                                    <div class="h5 fw-extrabold text-dark mb-0">CHF 1'800.00</div>
                                                    <span class="text-success fw-bold" style="font-size: 0.68rem;">0.0% MWST (Art. 21 MWSTG)</span>
                                                </div>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.68rem;">
                                                    Spare CHF 150.00
                                                </span>
                                            </div>
                                            <button type="button" class="btn w-100 fw-bold btn-sm rounded-3 py-2 text-white shadow-2xs" style="background-color: #2d2421;" onclick="selectStoreOfferAndBook('10er VIP');">
                                                Diesen Service wählen &rarr;
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Angebot 2: 5er Abo Hafen- & Boxentraining Kat. A -->
                                <div class="col-12 col-md-6 col-lg-4 store-offer-card" data-category="abo fahrschule">
                                    <div class="card h-100 rounded-4 border bg-white shadow-2xs overflow-hidden d-flex flex-column justify-content-between" style="border-color: #e2d5c3 !important;">
                                        <div class="p-3.5">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="badge rounded-pill text-dark fw-bold text-uppercase px-2.5 py-1" style="background-color: #fef3c7; font-size: 0.65rem;">
                                                    5er Abo Manöver
                                                </span>
                                                <span class="small text-muted fw-semibold" style="font-size: 0.72rem;">
                                                    <i class="bi bi-clock me-1"></i>5 x 90 Min.
                                                </span>
                                            </div>
                                            <h6 class="fw-bold text-dark mb-1">5er Abo Hafen- &amp; Boxentraining Kat. A</h6>
                                            <p class="text-muted small mb-3" style="font-size: 0.78rem;">
                                                Gezieltes Manövertraining für höchste Präzision: Rückwärts einparken in enge Boxen, Anlegen bei Seitenwind, Festmachen an Bojen und sicheres Verhalten bei Wellengang.
                                            </p>
                                            <div class="d-flex flex-wrap gap-1 mb-2">
                                                <span class="badge rounded-pill bg-light text-secondary border" style="font-size: 0.65rem;">Prüfungsfeinschliff</span>
                                                <span class="badge rounded-pill bg-light text-secondary border" style="font-size: 0.65rem;">Alle Stützpunkte</span>
                                            </div>
                                        </div>
                                        <div class="p-3.5 pt-2 border-top bg-light bg-opacity-50" style="border-color: #f1e9dd !important;">
                                            <div class="d-flex justify-content-between align-items-center mb-2.5">
                                                <div>
                                                    <div class="h5 fw-extrabold text-dark mb-0">CHF 925.00</div>
                                                    <span class="text-success fw-bold" style="font-size: 0.68rem;">0.0% MWST (Art. 21 MWSTG)</span>
                                                </div>
                                                <span class="badge bg-light text-secondary border" style="font-size: 0.68rem;">
                                                    CHF 185 / Std.
                                                </span>
                                            </div>
                                            <button type="button" class="btn w-100 fw-bold btn-sm rounded-3 py-2 text-white shadow-2xs" style="background-color: #2d2421;" onclick="selectStoreOfferAndBook('5er Abo');">
                                                Diesen Service wählen &rarr;
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Angebot 3: Motorboot Einzellektion Kat. A -->
                                <div class="col-12 col-md-6 col-lg-4 store-offer-card" data-category="fahrschule">
                                    <div class="card h-100 rounded-4 border bg-white shadow-2xs overflow-hidden d-flex flex-column justify-content-between" style="border-color: #e2d5c3 !important;">
                                        <div class="p-3.5">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="badge rounded-pill text-dark fw-bold text-uppercase px-2.5 py-1" style="background-color: #e0f2fe; color: #0369a1 !important; font-size: 0.65rem;">
                                                    Praxis Kat. A
                                                </span>
                                                <span class="small text-muted fw-semibold" style="font-size: 0.72rem;">
                                                    <i class="bi bi-clock me-1"></i>90 Minuten
                                                </span>
                                            </div>
                                            <h6 class="fw-bold text-dark mb-1">Motorboot Einzellektion Kat. A</h6>
                                            <p class="text-muted small mb-3" style="font-size: 0.78rem;">
                                                Flexible Praxisausbildung auf modernen Fahrschulbooten mit Doppelsteuerstand. Wähle dein Wunsch-Zeitfenster und deinen bevorzugten Stützpunkt frei nach Bedarf.
                                            </p>
                                            <div class="d-flex flex-wrap gap-1 mb-2">
                                                <span class="badge rounded-pill bg-light text-secondary border" style="font-size: 0.65rem;">Einzellektion</span>
                                                <span class="badge rounded-pill bg-light text-secondary border" style="font-size: 0.65rem;">Luzern / Hergiswil</span>
                                            </div>
                                        </div>
                                        <div class="p-3.5 pt-2 border-top bg-light bg-opacity-50" style="border-color: #f1e9dd !important;">
                                            <div class="d-flex justify-content-between align-items-center mb-2.5">
                                                <div>
                                                    <div class="h5 fw-extrabold text-dark mb-0">CHF 195.00</div>
                                                    <span class="text-success fw-bold" style="font-size: 0.68rem;">0.0% MWST (Art. 21 MWSTG)</span>
                                                </div>
                                                <span class="badge bg-light text-secondary border" style="font-size: 0.68rem;">
                                                    Pro 90 Min.
                                                </span>
                                            </div>
                                            <button type="button" class="btn w-100 fw-bold btn-sm rounded-3 py-2 text-white shadow-2xs" style="background-color: #2d2421;" onclick="selectStoreOfferAndBook('Einzellektion');">
                                                Diesen Service wählen &rarr;
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Angebot 4: Bootsmiete Boesch 620 Bimini -->
                                <div class="col-12 col-md-6 col-lg-4 store-offer-card" data-category="miete">
                                    <div class="card h-100 rounded-4 border bg-white shadow-2xs overflow-hidden d-flex flex-column justify-content-between" style="border-color: #e2d5c3 !important;">
                                        <div class="p-3.5">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="badge rounded-pill text-dark fw-bold text-uppercase px-2.5 py-1" style="background-color: #d1fae5; color: #065f46 !important; font-size: 0.65rem;">
                                                    Bootsvermietung
                                                </span>
                                                <span class="small text-muted fw-semibold" style="font-size: 0.72rem;">
                                                    <i class="bi bi-clock me-1"></i>Halbtag / Ganztag
                                                </span>
                                            </div>
                                            <h6 class="fw-bold text-dark mb-1">Bootsmiete Boesch 620 Bimini</h6>
                                            <p class="text-muted small mb-3" style="font-size: 0.78rem;">
                                                Erlebe Schweizer Edel-Mahagoni auf dem Vierwaldstättersee. Inklusive vollständiger Sicherheitsausrüstung, Badeleiter, Sonnenverdeck und digitalem Self-Check-in via Smartphone.
                                            </p>
                                            <div class="d-flex flex-wrap gap-1 mb-2">
                                                <span class="badge rounded-pill bg-light text-secondary border" style="font-size: 0.65rem;">Mahagoni Boot</span>
                                                <span class="badge rounded-pill bg-light text-secondary border" style="font-size: 0.65rem;">Patent Kat. A erforderlich</span>
                                            </div>
                                        </div>
                                        <div class="p-3.5 pt-2 border-top bg-light bg-opacity-50" style="border-color: #f1e9dd !important;">
                                            <div class="d-flex justify-content-between align-items-center mb-2.5">
                                                <div>
                                                    <div class="h5 fw-extrabold text-dark mb-0">ab CHF 480.00</div>
                                                    <span class="text-secondary fw-semibold" style="font-size: 0.68rem;">inkl. 8.1% MWST (Normalsatz)</span>
                                                </div>
                                                <span class="badge bg-light text-secondary border" style="font-size: 0.68rem;">
                                                    Halbtag (4 Std.)
                                                </span>
                                            </div>
                                            <button type="button" class="btn w-100 fw-bold btn-sm rounded-3 py-2 text-white shadow-2xs" style="background-color: #065f46;" onclick="selectStoreOfferAndBook('Boesch 620');">
                                                Miettermin anfragen &rarr;
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Angebot 5: Spezial-Skipper Coaching & Nachtfahrt -->
                                <div class="col-12 col-md-6 col-lg-4 store-offer-card" data-category="coaching">
                                    <div class="card h-100 rounded-4 border bg-white shadow-2xs overflow-hidden d-flex flex-column justify-content-between" style="border-color: #e2d5c3 !important;">
                                        <div class="p-3.5">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="badge rounded-pill text-dark fw-bold text-uppercase px-2.5 py-1" style="background-color: #ede9fe; color: #5b21b6 !important; font-size: 0.65rem;">
                                                    Skipper Coaching
                                                </span>
                                                <span class="small text-muted fw-semibold" style="font-size: 0.72rem;">
                                                    <i class="bi bi-clock me-1"></i>120 Minuten
                                                </span>
                                            </div>
                                            <h6 class="fw-bold text-dark mb-1">Spezial-Skipper Coaching &amp; Nachtfahrt</h6>
                                            <p class="text-muted small mb-3" style="font-size: 0.78rem;">
                                                Für Patentinhaber und Skipper, die ihre Sicherheit bei Dunkelheit, Radar- und GPS-Navigation, Starkwind oder schwierigen Hafenverhältnissen vertiefen möchten.
                                            </p>
                                            <div class="d-flex flex-wrap gap-1 mb-2">
                                                <span class="badge rounded-pill bg-light text-secondary border" style="font-size: 0.65rem;">Nachtfahrt</span>
                                                <span class="badge rounded-pill bg-light text-secondary border" style="font-size: 0.65rem;">Radar &amp; GPS</span>
                                            </div>
                                        </div>
                                        <div class="p-3.5 pt-2 border-top bg-light bg-opacity-50" style="border-color: #f1e9dd !important;">
                                            <div class="d-flex justify-content-between align-items-center mb-2.5">
                                                <div>
                                                    <div class="h5 fw-extrabold text-dark mb-0">CHF 290.00</div>
                                                    <span class="text-success fw-bold" style="font-size: 0.68rem;">0.0% MWST (Art. 21 MWSTG)</span>
                                                </div>
                                                <span class="badge bg-light text-secondary border" style="font-size: 0.68rem;">
                                                    Individuell
                                                </span>
                                            </div>
                                            <button type="button" class="btn w-100 fw-bold btn-sm rounded-3 py-2 text-white shadow-2xs" style="background-color: #2d2421;" onclick="selectStoreOfferAndBook('Coaching');">
                                                Diesen Service wählen &rarr;
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Angebot 6: Digitales Theorie- & Videopaket (1-Jahres-Abo) -->
                                <div class="col-12 col-md-6 col-lg-4 store-offer-card" data-category="digital abo">
                                    <div class="card h-100 rounded-4 border bg-white shadow-2xs overflow-hidden d-flex flex-column justify-content-between" style="border-color: #e2d5c3 !important;">
                                        <div class="p-3.5">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="badge rounded-pill text-dark fw-bold text-uppercase px-2.5 py-1" style="background-color: #cffafe; color: #155e75 !important; font-size: 0.65rem;">
                                                    365 Tage Flatrate
                                                </span>
                                                <span class="small text-muted fw-semibold" style="font-size: 0.72rem;">
                                                    <i class="bi bi-clock me-1"></i>1 Jahr Zugriff
                                                </span>
                                            </div>
                                            <h6 class="fw-bold text-dark mb-1">Digitales Theorie- &amp; Videopaket</h6>
                                            <p class="text-muted small mb-3" style="font-size: 0.78rem;">
                                                Uneingeschränkter 365-Tage-Zugriff auf über 25 HD-Manöver-Lehrfilme (Hafenmanöver bei Seitenwind, Boje über Bord, Radeffekt), amtliche Schifffahrts-Bildtafeln und den interaktiven Prüfungstrainer.
                                            </p>
                                            <div class="d-flex flex-wrap gap-1 mb-2">
                                                <span class="badge rounded-pill bg-light text-secondary border" style="font-size: 0.65rem;">Theorie &amp; Praxis</span>
                                                <span class="badge rounded-pill bg-light text-secondary border" style="font-size: 0.65rem;">Smartphone &amp; Tablet</span>
                                            </div>
                                        </div>
                                        <div class="p-3.5 pt-2 border-top bg-light bg-opacity-50" style="border-color: #f1e9dd !important;">
                                            <div class="d-flex justify-content-between align-items-center mb-2.5">
                                                <div>
                                                    <div class="h5 fw-extrabold text-dark mb-0">CHF 149.00</div>
                                                    <span class="text-success fw-bold" style="font-size: 0.68rem;">0.0% MWST (Art. 21 MWSTG)</span>
                                                </div>
                                                <span class="badge bg-light text-secondary border" style="font-size: 0.68rem;">
                                                    1-Jahres-Abo
                                                </span>
                                            </div>
                                            <button type="button" class="btn w-100 fw-bold btn-sm rounded-3 py-2 text-white shadow-2xs" style="background-color: #0e7490;" onclick="selectStoreOfferAndBook('Theorie');">
                                                Abo freischalten &rarr;
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Angebot 7: Wertgutschein Nautic -->
                                <div class="col-12 col-md-6 col-lg-4 store-offer-card" data-category="coaching">
                                    <div class="card h-100 rounded-4 border bg-white shadow-2xs overflow-hidden d-flex flex-column justify-content-between" style="border-color: #e2d5c3 !important;">
                                        <div class="p-3.5">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="badge rounded-pill text-dark fw-bold text-uppercase px-2.5 py-1" style="background-color: #fce7f3; color: #9d174d !important; font-size: 0.65rem;">
                                                    Gutschein
                                                </span>
                                                <span class="small text-muted fw-semibold" style="font-size: 0.72rem;">
                                                    <i class="bi bi-gift me-1"></i>Geschenkidee
                                                </span>
                                            </div>
                                            <h6 class="fw-bold text-dark mb-1">Exklusiver Wertgutschein Nautic</h6>
                                            <p class="text-muted small mb-3" style="font-size: 0.78rem;">
                                                Verschenke unvergessliche Erlebnisse auf dem Vierwaldstättersee. Einlösbar für alle Ausbildungen, Einzellektionen, Fahrstunden oder Bootsmieten.
                                            </p>
                                            <div class="d-flex flex-wrap gap-1 mb-2">
                                                <span class="badge rounded-pill bg-light text-secondary border" style="font-size: 0.65rem;">Frei wählbar</span>
                                                <span class="badge rounded-pill bg-light text-secondary border" style="font-size: 0.65rem;">PDF-Gutschein</span>
                                            </div>
                                        </div>
                                        <div class="p-3.5 pt-2 border-top bg-light bg-opacity-50" style="border-color: #f1e9dd !important;">
                                            <div class="d-flex justify-content-between align-items-center mb-2.5">
                                                <div>
                                                    <div class="h5 fw-extrabold text-dark mb-0">ab CHF 100.00</div>
                                                    <span class="text-success fw-bold" style="font-size: 0.68rem;">0.0% MWST (Art. 21 MWSTG)</span>
                                                </div>
                                                <span class="badge bg-light text-secondary border" style="font-size: 0.68rem;">
                                                    Wertgutschein
                                                </span>
                                            </div>
                                            <button type="button" class="btn w-100 fw-bold btn-sm rounded-3 py-2 text-white shadow-2xs" style="background-color: #2d2421;" onclick="selectStoreOfferAndBook('Gutschein');">
                                                Gutschein anfragen &rarr;
                                            </button>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div class="modal-footer px-4 py-3" style="background-color: #eee4d5; border-top: 1px solid #dfd0bc;">
                            <span class="small text-muted me-auto">
                                Alle Angebote mit Treibstoff, Versicherung und Schweizer Prüfungszertifizierung.
                            </span>
                            <button type="button" class="btn btn-outline-secondary rounded-3 btn-sm px-3" data-bs-dismiss="modal">Schließen</button>
                            <a href="?route=bootschule/services" class="btn btn-sm text-white fw-bold rounded-3 px-3 shadow-2xs" style="background-color: #2d2421;">
                                Voller Katalog (Webseite) &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================================== -->
            <!-- MODAL 1: TERMINANFRAGE STELLEN (Fahrlehrer-Style)                -->
            <!-- ============================================================== -->
            <div class="modal fade" id="requestAppointmentModal" tabindex="-1" aria-labelledby="requestAppointmentModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden" style="background-color: #f6f0e7;">
                        <div class="modal-header border-bottom px-4 pt-4 pb-3" style="background-color: #2d2421; color: #ffffff;">
                            <div class="d-flex align-items-center gap-3">
                                <span class="p-2.5 rounded-3 d-inline-flex" style="background-color: rgba(245, 158, 11, 0.2); color: #fbbf24;">
                                    <i class="bi bi-calendar2-plus fs-4 text-warning"></i>
                                </span>
                                <div>
                                    <h5 class="modal-title fw-bold text-white mb-0" id="requestAppointmentModalLabel">Termin buchen</h5>
                                    <span class="text-light text-opacity-75 small">Wunschtermin für deine Praxisfahrstunde oder Coaching buchen</span>
                                </div>
                            </div>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schließen"></button>
                        </div>

                        <form method="POST" action="?route=bootschule/me/request-appointment">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                            <div class="modal-body p-4" style="background-color: #f6f0e7;">
                                <div class="row g-3">
                                    
                                    <!-- Leistung / Ausbildungsart -->
                                    <div class="col-12">
                                        <label class="form-label small fw-bold text-dark mb-1">Ausbildungs- oder Mietleistung * (nur erfasste Angebote)</label>
                                        <select name="service_id" class="form-select rounded-3 py-2 bg-white" style="border: 1.5px solid #d5c5b2;" required>
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
                                        <div class="p-3 rounded-3" style="background-color: #eee4d5; border: 1.5px solid #dfd0bc;">
                                            <div class="row g-3">
                                                <div class="col-12 col-md-6">
                                                    <label class="form-label small fw-bold text-dark mb-1">Wunschdatum *</label>
                                                    <input type="date" name="date" class="form-control rounded-3 bg-white" style="border: 1.5px solid #d5c5b2;" required min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                                                </div>
                                                <div class="col-12 col-md-6">
                                                    <label class="form-label small fw-bold text-dark mb-1">Bevorzugtes Zeitfenster</label>
                                                    <select name="time_preference" class="form-select rounded-3 bg-white" style="border: 1.5px solid #d5c5b2;">
                                                        <option value="morning">Vormittag (09:00 – 10:30 Uhr)</option>
                                                        <option value="afternoon" selected>Nachmittag (14:00 – 15:30 Uhr)</option>
                                                        <option value="evening">Feierabend / Abend (17:30 – 19:00 Uhr)</option>
                                                        <option value="custom">Genaue Startzeit wählen &darr;</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Standort / Treffpunkt mit Google Maps Verknüpfung -->
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <label class="form-label small fw-bold text-dark mb-0">Wunsch-Standort / Hafen *</label>
                                            <a id="portalLocationHeaderMapsLink" href="https://www.google.com/maps/search/?api=1&query=Alpenquai+12+6005+Luzern" target="_blank" rel="noopener noreferrer" class="text-decoration-none fw-bold small text-dark d-inline-flex align-items-center gap-1 hover-underline" style="font-size: 0.72rem;">
                                                <i class="bi bi-geo-alt-fill text-danger"></i>
                                                <span>Auf Maps öffnen &nearr;</span>
                                            </a>
                                        </div>
                                        <select name="location_id" id="portalLocationSelect" class="form-select rounded-3 bg-white" style="border: 1.5px solid #d5c5b2;" onchange="updateLocationMapLink(this)">
                                            <?php if (!empty($availableLocations)): ?>
                                                <?php foreach ($availableLocations as $loc): ?>
                                                    <?php
                                                        $locAddr = !empty($loc['address']) ? $loc['address'] : ($loc['name'] ?? '');
                                                    ?>
                                                    <option value="<?= (int) $loc['id'] ?>" data-address="<?= htmlspecialchars((string) $locAddr, ENT_QUOTES, 'UTF-8') ?>" data-name="<?= htmlspecialchars((string) $loc['name'], ENT_QUOTES, 'UTF-8') ?>" data-notes="<?= htmlspecialchars((string) ($loc['notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                        <?= htmlspecialchars((string) $loc['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <option value="1" data-address="Alpenquai 12, 6005 Luzern" data-name="Luzern (Alpenquai)" data-notes="Steg 3 beim KKL / Alpenquai">Luzern – Alpenquai Steg 3 (Hauptstandort)</option>
                                                <option value="2" data-address="Seestrasse 48, 6374 Buochs" data-name="Buochs - Ennerberg" data-notes="Ennerberg Steganlage">Buochs – Ennerberg Steganlage</option>
                                                <option value="3" data-address="Seestrasse 20, 6052 Hergiswil" data-name="Hergiswil (Bootshafen)" data-notes="Bootshafen Hergiswil / Steg 2">Hergiswil NW – Bootshafen</option>
                                                <option value="4" data-address="Hafenstrasse 4, 6362 Stansstad" data-name="Stansstad (Hafenanlage)" data-notes="Öffentliche Hafenanlage">Stansstad NW – Hafenanlage</option>
                                            <?php endif; ?>
                                        </select>

                                        <!-- Treffpunkt Detailbox mit direkter Maps Verknüpfung -->
                                        <div id="portalLocationAddressBox" class="mt-2 p-2 rounded-3 small d-flex align-items-center justify-content-between gap-2 shadow-xs" style="background-color: #ffffff; border: 1.5px solid #d5c5b2;">
                                            <div class="d-flex align-items-center gap-1.5 min-w-0">
                                                <i class="bi bi-geo-alt-fill text-danger flex-shrink-0"></i>
                                                <span class="text-dark fw-bold small text-truncate" id="portalLocationAddressText">Alpenquai 12, 6005 Luzern</span>
                                            </div>
                                            <a id="portalLocationMapsLink" href="https://www.google.com/maps/search/?api=1&query=Alpenquai+12+6005+Luzern" target="_blank" rel="noopener noreferrer" class="btn btn-sm py-1 px-2.5 text-white fw-bold d-inline-flex align-items-center gap-1 flex-shrink-0 rounded-2" style="background-color: #2d2421; font-size: 0.72rem;">
                                                <span>Maps</span>
                                                <i class="bi bi-box-arrow-up-right text-warning" style="font-size: 0.65rem;"></i>
                                            </a>
                                        </div>
                                    </div>

                                    <!-- Fahrlehrer / Coach Präferenz -->
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-bold text-dark mb-1">Wunsch-Fahrlehrer (optional)</label>
                                        <select name="instructor_id" class="form-select rounded-3 bg-white" style="border: 1.5px solid #d5c5b2;">
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
                                        <div class="p-3 rounded-3" style="background-color: #eee4d5; border: 1.5px solid #dfd0bc;">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="use_balance" value="1" id="useBalanceCheck" <?= $prepaidBalance['remaining_units'] > 0 ? 'checked' : '' ?>>
                                                <label class="form-check-label fw-bold text-dark" for="useBalanceCheck">
                                                    Mit bestehendem Stunden-Guthaben / Abonnement verrechnen
                                                </label>
                                                <div class="text-dark text-opacity-75 small">
                                                    Aktuell verfügbar: <strong><?= (int) ($prepaidBalance['remaining_units'] ?? 0) ?> bezahlte Fahrstunden</strong>. Falls aktiviert, wird nach Bestätigung automatisch eine Einheit von deinem Guthaben abgebucht.
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Notizen / Wünsche an den Fahrlehrer (Grösseres Textfeld) -->
                                    <div class="col-12">
                                        <label class="form-label small fw-bold text-dark mb-1">
                                            Notizen &amp; Ausbildungsschwerpunkte (optional)
                                        </label>
                                        <textarea name="notes" rows="4" class="form-control rounded-3 bg-white" style="border: 1.5px solid #d5c5b2; color: #1c1917;" placeholder="z. B. Schwerpunkt auf Boxenmanöver rückwärts, Anlegen bei Seitenwind oder Prüfungsvorbereitung..."></textarea>
                                    </div>

                                </div>
                            </div>

                            <div class="modal-footer px-4 py-3" style="background-color: #eee4d5; border-top: 1px solid #dfd0bc;">
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
                    <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden" style="background-color: #f6f0e7;">
                        <div class="modal-header border-bottom px-4 pt-4 pb-3" style="background-color: #2d2421; color: #ffffff;">
                            <div class="d-flex align-items-center gap-3">
                                <span class="p-2.5 rounded-3 d-inline-flex" style="background-color: rgba(245, 158, 11, 0.2); color: #fbbf24;">
                                    <i class="bi bi-person-gear fs-4 text-warning"></i>
                                </span>
                                <div>
                                    <h5 class="modal-title fw-bold text-white mb-0" id="profileModalLabel">Mein Kundenprofil bearbeiten</h5>
                                    <span class="text-light text-opacity-75 small">Kontaktdaten, Notfallkontakt, Allergien &amp; Ausbildungsdaten</span>
                                </div>
                            </div>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schließen"></button>
                        </div>

                        <form method="POST" action="?route=bootschule/me/profile">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                            <div class="modal-body p-4" style="background-color: #f6f0e7;">
                                <div class="row g-3">
                                    
                                    <!-- Profilbild hinzufügen (keine Pflicht) -->
                                    <div class="col-12">
                                        <div class="p-3 rounded-3 d-flex align-items-center gap-3" style="background-color: #eee4d5; border: 1.5px solid #dfd0bc;">
                                            <div class="rounded-circle bg-secondary bg-opacity-25 text-dark fw-bold d-flex align-items-center justify-content-center" style="width: 58px; height: 58px; font-size: 1.25rem;">
                                                <?= strtoupper(substr((string) ($customer['first_name'] ?? 'R'), 0, 1) . substr((string) ($customer['last_name'] ?? 'G'), 0, 1)) ?>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center justify-content-between mb-1">
                                                    <span class="fw-bold text-dark small">Profilbild hinzufügen</span>
                                                    <span class="badge bg-secondary-subtle text-secondary small">Keine Pflicht</span>
                                                </div>
                                                <p class="text-muted small mb-1" style="font-size: 0.75rem;">
                                                    Freiwillig – erleichtert deinem Fahrlehrer das Wiedererkennen am Steg oder Schiff.
                                                </p>
                                                <input type="url" name="avatar_url" class="form-control form-control-sm rounded-2 font-monospace bg-white" style="border: 1.5px solid #d5c5b2;" placeholder="https://... (Foto-URL oder Avatar)" value="<?= htmlspecialchars((string) ($customer['avatar_url'] ?? '')) ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Vorname & Nachname (Info) -->
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-semibold text-muted mb-1">Vorname</label>
                                        <input type="text" class="form-control rounded-3" style="background-color: #eee4d5; border: 1.5px solid #dfd0bc;" value="<?= htmlspecialchars((string) ($customer['first_name'] ?? '')) ?>" disabled>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-semibold text-muted mb-1">Nachname</label>
                                        <input type="text" class="form-control rounded-3" style="background-color: #eee4d5; border: 1.5px solid #dfd0bc;" value="<?= htmlspecialchars((string) ($customer['last_name'] ?? '')) ?>" disabled>
                                    </div>

                                    <!-- E-Mail (Info) -->
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-semibold text-muted mb-1">E-Mail-Adresse</label>
                                        <input type="email" class="form-control rounded-3" style="background-color: #eee4d5; border: 1.5px solid #dfd0bc;" value="<?= htmlspecialchars((string) ($customer['email'] ?? '')) ?>" disabled>
                                    </div>

                                    <!-- Telefonnummer (Zwingend) -->
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-bold text-dark mb-1">
                                            Mobiltelefonnummer <span class="text-danger">*</span>
                                        </label>
                                        <input type="tel" name="phone" class="form-control rounded-3 bg-white" style="border: 1.5px solid #d5c5b2;" placeholder="+41 79 123 45 67" required value="<?= htmlspecialchars((string) ($customer['phone'] ?? '')) ?>">
                                        <div class="form-text" style="font-size: 0.72rem;">Für kurzfristige wetterbedingte Verschiebungen oder Erreichbarkeit am Steg.</div>
                                    </div>

                                    <!-- Geburtsdatum (für Schifffahrtsamt) -->
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-bold text-dark mb-1">
                                            Geburtsdatum <span class="text-danger">*</span>
                                        </label>
                                        <input type="date" name="birthdate" class="form-control rounded-3 bg-white" style="border: 1.5px solid #d5c5b2;" value="<?= htmlspecialchars((string) ($customer['birthdate'] ?? '')) ?>">
                                        <div class="form-text" style="font-size: 0.72rem;">Vom Schifffahrtsamt für die Prüfungsanmeldung verlangt.</div>
                                    </div>

                                    <!-- Notfallkontakt -->
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-bold text-dark mb-1">Notfallkontakt (Name &amp; Telefonnummer)</label>
                                        <input type="text" name="emergency_contact" class="form-control rounded-3 bg-white" style="border: 1.5px solid #d5c5b2;" placeholder="z. B. Anna Muster, +41 78 987 65 43" value="<?= htmlspecialchars((string) ($customer['emergency_contact'] ?? '')) ?>">
                                    </div>

                                    <!-- Allergien & medizinische Besonderheiten -->
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-bold text-dark mb-1">
                                            <i class="bi bi-shield-exclamation text-warning me-1"></i>
                                            Allergien &amp; medizinische Besonderheiten
                                        </label>
                                        <input type="text" name="allergies" class="form-control rounded-3 bg-white" style="border: 1.5px solid #d5c5b2;" placeholder="z. B. Wespen- / Bienenstich-Allergie, Laktose, Medikamente..." value="<?= htmlspecialchars((string) ($customer['allergies'] ?? 'Keine bekannten Allergien')) ?>">
                                    </div>

                                    <!-- Strasse & Nr. -->
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-bold text-dark mb-1">Strasse &amp; Hausnummer</label>
                                        <input type="text" name="street" class="form-control rounded-3 bg-white" style="border: 1.5px solid #d5c5b2;" placeholder="Seestrasse 12" value="<?= htmlspecialchars((string) ($customer['street'] ?? '')) ?>">
                                    </div>

                                    <!-- PLZ & Ort -->
                                    <div class="col-12 col-md-6">
                                        <div class="row g-2">
                                            <div class="col-4">
                                                <label class="form-label small fw-bold text-dark mb-1">PLZ</label>
                                                <input type="text" name="zip" class="form-control rounded-3 bg-white" style="border: 1.5px solid #d5c5b2;" placeholder="6000" value="<?= htmlspecialchars((string) ($customer['zip'] ?? '')) ?>">
                                            </div>
                                            <div class="col-8">
                                                <label class="form-label small fw-bold text-dark mb-1">Wohnort</label>
                                                <input type="text" name="city" class="form-control rounded-3 bg-white" style="border: 1.5px solid #d5c5b2;" placeholder="Luzern" value="<?= htmlspecialchars((string) ($customer['city'] ?? '')) ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Notizen & Wünsche: Grösseres Textfeld -->
                                    <div class="col-12">
                                        <label class="form-label small fw-bold text-dark mb-1">Notizen &amp; Wünsche (grösseres Textfeld)</label>
                                        <textarea name="notes" rows="4" class="form-control rounded-3 bg-white" style="border: 1.5px solid #d5c5b2;" placeholder="Deine Notizen, Ausbildungsziele, zeitliche Präferenzen oder Vorkenntnisse..."><?= htmlspecialchars((string) ($customer['notes'] ?? '')) ?></textarea>
                                    </div>

                                    <!-- AUFKLAPPBARER BEREICH AM SCHLUSS: THEORIE BESTANDEN (MIT DATUMSANGABE) -->
                                    <div class="col-12">
                                        <div class="accordion" id="theoryAccordion">
                                            <div class="accordion-item rounded-3 border overflow-hidden" style="border-color: #dfd0bc !important;">
                                                <h2 class="accordion-header" id="headingTheory">
                                                    <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTheory" aria-expanded="false" aria-controls="collapseTheory" style="background-color: #eee4d5;">
                                                        <i class="bi bi-patch-check-fill text-warning me-2 fs-5"></i>
                                                        Theorie Bestanden (mit Datumsangabe)
                                                        <?php if (!empty($customer['theory_passed'])): ?>
                                                            <span class="badge bg-success ms-2 small">Bestanden</span>
                                                        <?php endif; ?>
                                                    </button>
                                                </h2>
                                                <div id="collapseTheory" class="accordion-collapse collapse" aria-labelledby="headingTheory" data-bs-parent="#theoryAccordion">
                                                    <div class="accordion-body p-3 p-md-4" style="background-color: #f6f0e7;">
                                                        <div class="row g-3">
                                                            <!-- Checkbox & Datumsangabe -->
                                                            <div class="col-12 col-md-6">
                                                                <div class="form-check pt-2">
                                                                    <input class="form-check-input" type="checkbox" name="theory_passed" value="1" id="theoryPassedCheck" <?= !empty($customer['theory_passed']) ? 'checked' : '' ?>>
                                                                    <label class="form-check-label fw-bold text-dark" for="theoryPassedCheck">
                                                                        Theorieprüfung Schifffahrtsamt bestanden
                                                                    </label>
                                                                </div>
                                                            </div>
                                                            <div class="col-12 col-md-6">
                                                                <label class="form-label small fw-bold text-dark mb-1">
                                                                    Datum Theorieprüfung (mit Datumsangabe)
                                                                </label>
                                                                <input type="date" name="theory_passed_at" class="form-control rounded-3 bg-white" style="border: 1.5px solid #d5c5b2;" value="<?= htmlspecialchars((string) ($customer['theory_passed_at'] ?? '')) ?>">
                                                            </div>

                                                            <!-- FABER: Fahrberechtigungs-Register -->
                                                            <div class="col-12 col-md-4">
                                                                <label class="form-label small fw-bold text-dark mb-1">
                                                                    FABER: <span class="theory-req-star text-danger" style="display: <?= !empty($customer['theory_passed']) ? 'inline' : 'none' ?>;">*</span>
                                                                </label>
                                                                <input type="text" name="faber" class="form-control rounded-3 font-monospace bg-white" style="border: 1.5px solid #d5c5b2;" placeholder="z. B. 123.456.789" value="<?= htmlspecialchars((string) ($customer['faber'] ?? $customer['license_number'] ?? '')) ?>">
                                                                <div class="form-text" style="font-size: 0.70rem;">Fahrberechtigungs-Register (nur bei bestandener Theorie)</div>
                                                            </div>

                                                            <!-- PIN: Persönliche Identifikationsnummer -->
                                                            <div class="col-12 col-md-4">
                                                                <label class="form-label small fw-bold text-dark mb-1">
                                                                    PIN: <span class="theory-req-star text-danger" style="display: <?= !empty($customer['theory_passed']) ? 'inline' : 'none' ?>;">*</span>
                                                                </label>
                                                                <input type="text" name="pin" class="form-control rounded-3 font-monospace bg-white" style="border: 1.5px solid #d5c5b2;" placeholder="z. B. 7841" value="<?= htmlspecialchars((string) ($customer['pin'] ?? '')) ?>">
                                                                <div class="form-text" style="font-size: 0.70rem;">Persönliche Identifikationsnummer (nur bei bestandener Theorie)</div>
                                                            </div>

                                                            <!-- PID: Personen-Identifikator -->
                                                            <div class="col-12 col-md-4">
                                                                <label class="form-label small fw-bold text-dark mb-1">
                                                                    PID: <span class="theory-req-star text-danger" style="display: <?= !empty($customer['theory_passed']) ? 'inline' : 'none' ?>;">*</span>
                                                                </label>
                                                                <input type="text" name="pid" class="form-control rounded-3 font-monospace bg-white" style="border: 1.5px solid #d5c5b2;" placeholder="z. B. CHE-123.456.7" value="<?= htmlspecialchars((string) ($customer['pid'] ?? '')) ?>">
                                                                <div class="form-text" style="font-size: 0.70rem;">Personen-Identifikator (nur bei bestandener Theorie)</div>
                                                            </div>

                                                            <div class="col-12">
                                                                <div class="alert border small text-muted mb-0" style="background-color: #eee4d5; border-color: #dfd0bc;">
                                                                    <i class="bi bi-info-circle me-1"></i>
                                                                    <strong>Hinweis zur Prüfungsanmeldung:</strong> Die ASA-Daten (FABER, PIN und PID) werden erst benötigt und verlangt, sobald du die Theorieprüfung beim Schifffahrtsamt erfolgreich bestanden hast.
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

                            <div class="modal-footer px-4 py-3" style="background-color: #eee4d5; border-top: 1px solid #dfd0bc;">
                                <button type="button" class="btn btn-outline-secondary rounded-3 btn-sm px-3" data-bs-dismiss="modal">Abbrechen</button>
                                <button type="submit" class="btn text-white rounded-3 btn-sm px-4 fw-bold shadow-sm" style="background-color: #2d2421;">
                                    <i class="bi bi-save me-1"></i> Profilangaben speichern &rarr;
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            </div>

            <!-- Modal: Seewetter & Sturmwarnung Vierwaldstättersee -->
            <div class="modal fade" id="lakeWeatherModal" tabindex="-1" aria-labelledby="lakeWeatherModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden" style="background-color: #faf6f0; border: 1.5px solid #e2d5c3;">
                        <div class="modal-header px-4 py-3.5 text-white" style="background-color: #2d2421; border-bottom: 1.5px solid #3f3531;">
                            <div class="d-flex align-items-center gap-3">
                                <span class="rounded-3 d-inline-flex align-items-center justify-content-center text-info" style="width: 44px; height: 44px; background-color: rgba(2, 132, 199, 0.2); border: 1px solid rgba(2, 132, 199, 0.35);">
                                    <i class="bi bi-water fs-4"></i>
                                </span>
                                <div>
                                    <h5 class="modal-title fw-bold text-white mb-0" id="lakeWeatherModalLabel">
                                        Seewetter &amp; Wetterportale Vierwaldstättersee
                                    </h5>
                                    <span class="text-light text-opacity-75 small">Live-Nautik-Bedingungen mit direkter Verlinkung zu Schweizer Wetterdiensten</span>
                                </div>
                            </div>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schließen"></button>
                        </div>

                        <div class="modal-body p-4" style="background-color: #faf6f0;">
                            <!-- Station Switcher Buttons -->
                            <div class="d-flex p-1 rounded-3 mb-4" style="background-color: #eee4d5; border: 1px solid #ded0be;">
                                <button type="button" id="btnStationLuzern" class="btn btn-sm flex-fill fw-bold rounded-2 py-1.5 shadow-xs bg-white text-dark" onclick="switchLakeStation('luzern');">
                                    Luzerner Bucht &amp; Alpenquai
                                </button>
                                <button type="button" id="btnStationHergiswil" class="btn btn-sm flex-fill fw-bold rounded-2 py-1.5 text-secondary" onclick="switchLakeStation('hergiswil');">
                                    Hergiswil &amp; Alpnachersee
                                </button>
                            </div>

                            <!-- Live Grid Metrics -->
                            <div class="row g-3 mb-4">
                                <div class="col-6 col-md-3">
                                    <div class="card border rounded-3 p-3 bg-white h-100" style="border-color: #e2d5c3 !important;">
                                        <div class="text-secondary small fw-medium d-flex align-items-center gap-1">
                                            <i class="bi bi-thermometer-half text-danger"></i> Lufttemperatur
                                        </div>
                                        <div class="h3 fw-extrabold text-dark mt-1 mb-0" id="weatherAirTemp">7°C</div>
                                        <span class="text-muted" style="font-size: 0.75rem;">Teils sonnig</span>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="card border rounded-3 p-3 bg-white h-100" style="border-color: #e2d5c3 !important;">
                                        <div class="text-secondary small fw-medium d-flex align-items-center gap-1">
                                            <i class="bi bi-water text-primary"></i> Wassertemperatur
                                        </div>
                                        <div class="h3 fw-extrabold text-primary mt-1 mb-0" id="weatherWaterTemp">11.4°C</div>
                                        <span class="text-muted" style="font-size: 0.75rem;">Vierwaldstättersee</span>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="card border rounded-3 p-3 bg-white h-100" style="border-color: #e2d5c3 !important;">
                                        <div class="text-secondary small fw-medium d-flex align-items-center gap-1">
                                            <i class="bi bi-wind text-muted"></i> Wind &amp; Beaufort
                                        </div>
                                        <div class="h3 fw-extrabold text-dark mt-1 mb-0" id="weatherWindSpeed">7.7 <span class="fs-6 fw-normal">km/h</span></div>
                                        <span class="text-muted" style="font-size: 0.75rem;" id="weatherWindKnots">4.2 kn · 2 Bft (NNO)</span>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="card border rounded-3 p-3 bg-white h-100" style="border-color: #e2d5c3 !important;">
                                        <div class="text-secondary small fw-medium d-flex align-items-center gap-1">
                                            <i class="bi bi-shield-check text-success"></i> Sturmwarndienst
                                        </div>
                                        <div class="fw-extrabold text-success mt-2 mb-0 d-flex align-items-center gap-1.5" style="font-size: 0.85rem;">
                                            <span class="spinner-grow spinner-grow-sm text-success" style="width: 8px; height: 8px;"></span> Keine Warnung
                                        </div>
                                        <span class="text-muted" style="font-size: 0.75rem;">Normaler Seebetrieb</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Sturmwarnung Info Box -->
                            <div class="p-3.5 rounded-3 mb-4" style="background-color: #fff9eb; border: 1.5px solid #f5dfa8;">
                                <div class="fw-bold text-dark small d-flex align-items-center gap-2 mb-2">
                                    <i class="bi bi-exclamation-triangle-fill text-warning"></i>
                                    Amtliche Sturmwarnung für Schweizer Seen (Vierwaldstättersee):
                                </div>
                                <div class="row g-2 text-dark" style="font-size: 0.8rem;">
                                    <div class="col-12 col-md-6">
                                        <div class="p-2.5 rounded-2 bg-white border" style="border-color: #f0ddb2 !important;">
                                            <strong class="text-warning-emphasis">40 Blitze / Minute (Starkwindwarnung):</strong>
                                            <p class="text-muted mb-0 mt-0.5">Kündigt Winde mit Böenspitzen von 25 bis 33 Knoten (ca. 45–60 km/h) an. Uferzone beachten!</p>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="p-2.5 rounded-2 bg-white border" style="border-color: #f0ddb2 !important;">
                                            <strong class="text-danger">90 Blitze / Minute (Sturmwarnung):</strong>
                                            <p class="text-muted mb-0 mt-0.5">Kündigt Sturmwindböen ab 34 Knoten (ab 60 km/h) an. Sofort sicheren Hafen anlaufen und Schwimmwesten anlegen!</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Direkte Wetterportale Links -->
                            <h6 class="fw-bold text-dark mb-3">Offizielle Schweizer Wetterportale für mehr Informationen</h6>
                            <div class="d-flex flex-column gap-2.5">
                                <a id="linkSearchCh" href="https://search.ch/meteo/luzern" target="_blank" rel="noopener noreferrer" class="card border rounded-3 p-3 text-decoration-none text-dark bg-white shadow-2xs d-flex flex-row align-items-center justify-content-between hover-shadow">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-2 d-flex align-items-center justify-content-center text-white fw-black" style="width: 42px; height: 42px; background-color: #0284c7; font-size: 0.8rem;">
                                            Meteo
                                        </div>
                                        <div>
                                            <div class="fw-bold small text-dark d-flex align-items-center gap-2">
                                                <span id="titleSearchCh">search.ch Meteo – Lokalprognose Luzern</span>
                                                <span class="badge rounded-pill text-bg-primary" style="font-size: 0.65rem;">Direktlink</span>
                                            </div>
                                            <p class="text-muted small mb-0">Stündliche Vorhersage, Niederschlagsradar, Windkurven und lokale 5-Tages-Prognose.</p>
                                        </div>
                                    </div>
                                    <div class="btn btn-sm btn-outline-primary fw-bold rounded-2 px-3 flex-shrink-0">
                                        Öffnen <i class="bi bi-box-arrow-up-right ms-1"></i>
                                    </div>
                                </a>

                                <a href="https://www.srf.ch/meteo" target="_blank" rel="noopener noreferrer" class="card border rounded-3 p-3 text-decoration-none text-dark bg-white shadow-2xs d-flex flex-row align-items-center justify-content-between hover-shadow">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-2 d-flex align-items-center justify-content-center text-white fw-black" style="width: 42px; height: 42px; background-color: #dc2626; font-size: 0.8rem;">
                                            SRF
                                        </div>
                                        <div>
                                            <div class="fw-bold small text-dark d-flex align-items-center gap-2">
                                                SRF Meteo – Offizielles Schweizer Wetterportal
                                                <span class="badge rounded-pill text-bg-danger" style="font-size: 0.65rem;">Schweiz</span>
                                            </div>
                                            <p class="text-muted small mb-0">Schweizweite Wetterlage, Unwetterwarnungen, Satellitenbilder und Prognose-Videos.</p>
                                        </div>
                                    </div>
                                    <div class="btn btn-sm btn-outline-danger fw-bold rounded-2 px-3 flex-shrink-0">
                                        Öffnen <i class="bi bi-box-arrow-up-right ms-1"></i>
                                    </div>
                                </a>

                                <a href="https://www.meteoschweiz.admin.ch" target="_blank" rel="noopener noreferrer" class="card border rounded-3 p-3 text-decoration-none text-dark bg-white shadow-2xs d-flex flex-row align-items-center justify-content-between hover-shadow">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-2 d-flex align-items-center justify-content-center text-white fw-bold" style="width: 42px; height: 42px; background-color: #0f172a; font-size: 0.75rem;">
                                            + CH
                                        </div>
                                        <div>
                                            <div class="fw-bold small text-dark d-flex align-items-center gap-2">
                                                MeteoSchweiz – Bundesamt für Meteorologie
                                                <span class="badge rounded-pill text-bg-dark" style="font-size: 0.65rem;">Bund &amp; Seen</span>
                                            </div>
                                            <p class="text-muted small mb-0">Amtlicher Sturmwarndienst Schweizer Seen, Messstationen und Isobarenkarte.</p>
                                        </div>
                                    </div>
                                    <div class="btn btn-sm btn-outline-dark fw-bold rounded-2 px-3 flex-shrink-0">
                                        Öffnen <i class="bi bi-box-arrow-up-right ms-1"></i>
                                    </div>
                                </a>

                                <a href="https://www.windy.com/?47.050,8.307,11" target="_blank" rel="noopener noreferrer" class="card border rounded-3 p-3 text-decoration-none text-dark bg-white shadow-2xs d-flex flex-row align-items-center justify-content-between hover-shadow">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-2 d-flex align-items-center justify-content-center text-white fw-bold" style="width: 42px; height: 42px; background-color: #0d9488; font-size: 0.8rem;">
                                            <i class="bi bi-wind fs-5"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold small text-dark d-flex align-items-center gap-2">
                                                Windy Live-Radar – Vierwaldstättersee
                                                <span class="badge rounded-pill text-bg-info text-white" style="font-size: 0.65rem;">Skipper &amp; Nautik</span>
                                            </div>
                                            <p class="text-muted small mb-0">Live-Windströmungen, Böenspitzen in Knoten und Wellenradar für den gesamten See.</p>
                                        </div>
                                    </div>
                                    <div class="btn btn-sm btn-outline-secondary fw-bold rounded-2 px-3 flex-shrink-0">
                                        Öffnen <i class="bi bi-box-arrow-up-right ms-1"></i>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <div class="modal-footer px-4 py-3" style="background-color: #eee4d5; border-top: 1px solid #dfd0bc;">
                            <span class="small text-muted me-auto">Tipp: Vor Fahrstunden oder Miete stets Sturmwarnleuchten am Ufer prüfen.</span>
                            <button type="button" class="btn btn-dark rounded-3 btn-sm px-4 fw-bold" data-bs-dismiss="modal">Schließen</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================================== -->
            <!-- MODAL 4: DIGITALER SELF CHECK-IN & SCHIFFSÜBERGABE (MIETE)     -->
            <!-- ============================================================== -->
            <div class="modal fade" id="selfCheckinModal" tabindex="-1" aria-labelledby="selfCheckinModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
                    <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden" style="background-color: #f6f0e7;">
                        <div class="modal-header border-bottom px-4 pt-4 pb-3" style="background-color: #2d2421; color: #ffffff;">
                            <div class="d-flex align-items-center gap-3">
                                <span class="p-2.5 rounded-3 d-inline-flex" style="background-color: rgba(52, 211, 153, 0.2); color: #34d399;">
                                    <i class="bi bi-qr-code fs-4"></i>
                                </span>
                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        <h5 class="modal-title fw-bold text-white mb-0" id="selfCheckinModalLabel">Digitaler Self Check-in &amp; Schiffsübergabe</h5>
                                        <span class="badge rounded-pill fw-bold text-uppercase" style="background-color: #065f46; color: #a7f3d0; font-size: 0.65rem;">Bootsmiete</span>
                                    </div>
                                    <span class="text-light text-opacity-75 small">Schiffsübernahme, Sicherheitsausrüstung gem. BSV &amp; Treibstoffprotokoll Vierwaldstättersee</span>
                                </div>
                            </div>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schließen"></button>
                        </div>

                        <div class="modal-body p-4" style="background-color: #f6f0e7;">
                            
                            <!-- Tab-Umschaltung: Übernahme vs. Rückgabe vs. Notfall -->
                            <div class="d-flex flex-wrap gap-2 mb-4 p-1.5 rounded-3 bg-white border" style="border-color: #dfd0bc !important;">
                                <button type="button" class="btn btn-sm fw-bold px-3 py-2 rounded-2 checkin-mode-btn bg-dark text-white" id="btnCheckinTakeover" onclick="switchCheckinMode('takeover')">
                                    <i class="bi bi-box-arrow-right me-1.5 text-warning"></i> 1. Schiffsübernahme (Auslaufen)
                                </button>
                                <button type="button" class="btn btn-sm fw-bold px-3 py-2 rounded-2 checkin-mode-btn text-secondary" id="btnCheckinReturn" onclick="switchCheckinMode('return')">
                                    <i class="bi bi-box-arrow-in-left me-1.5 text-success"></i> 2. Schiffsrückgabe (Einlaufen)
                                </button>
                                <button type="button" class="btn btn-sm fw-bold px-3 py-2 rounded-2 checkin-mode-btn text-secondary ms-auto" id="btnCheckinEmergency" onclick="switchCheckinMode('emergency')">
                                    <i class="bi bi-telephone-outbound me-1.5 text-danger"></i> Notfall &amp; Papiere
                                </button>
                            </div>

                            <!-- Panel 1: Schiffsübernahme (Vor dem Auslaufen) -->
                            <div id="checkinPanelTakeover" class="checkin-panel">
                                
                                <!-- Erfolgsbestätigung nach Aktivierung (anfänglich ausgeblendet) -->
                                <div id="checkinSuccessBox" class="p-4 rounded-4 mb-4 bg-white border shadow-sm" style="border-color: #a7f3d0 !important; display: none;">
                                    <div class="d-flex align-items-center justify-content-between mb-3 pb-3 border-bottom">
                                        <div class="d-flex align-items-center gap-2.5">
                                            <span class="p-2 rounded-circle bg-success text-white d-inline-flex">
                                                <i class="bi bi-check2-circle fs-4"></i>
                                            </span>
                                            <div>
                                                <h6 class="fw-bold text-success mb-0">Schiffsübernahme erfolgreich aktiviert!</h6>
                                                <span class="text-muted small">Freigabe für Boesch 620 Bimini auf dem Vierwaldstättersee erteilt.</span>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <div class="badge bg-success-subtle text-success border border-success fw-bold px-3 py-1.5 fs-6" id="checkinCodeDisplay">
                                                CB-CHK-74921
                                            </div>
                                            <div class="text-muted" style="font-size: 0.7rem;">Digitaler Check-in Code</div>
                                        </div>
                                    </div>
                                    <div class="row g-3 small">
                                        <div class="col-12 col-md-4">
                                            <div class="p-2.5 rounded-3 bg-light border">
                                                <span class="text-muted d-block">Startzeitpunkt:</span>
                                                <strong class="text-dark" id="checkinStartTime"><?= date('d.m.Y · H:i') ?> Uhr</strong>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <div class="p-2.5 rounded-3 bg-light border">
                                                <span class="text-muted d-block">Liegeplatz &amp; Stützpunkt:</span>
                                                <strong class="text-dark">Luzern Alpenquai (Steg A)</strong>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <div class="p-2.5 rounded-3 bg-light border">
                                                <span class="text-muted d-block">Treibstoffstand Übernahme:</span>
                                                <strong class="text-success" id="checkinStartFuelDisplay">1/1 Voll (100%)</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div id="checkinFormContainer">
                                    <div class="row g-4">
                                        
                                        <!-- Linke Spalte: Boot & Sicherheits-Checkliste -->
                                        <div class="col-12 col-lg-7">
                                            
                                            <!-- Boot Auswahl -->
                                            <div class="card border rounded-4 p-3.5 mb-3 bg-white" style="border-color: #dfd0bc !important;">
                                                <div class="d-flex align-items-center justify-content-between mb-3">
                                                    <div class="fw-bold text-dark small d-flex align-items-center gap-2">
                                                        <i class="bi bi-water text-primary"></i>
                                                        1. Gemietetes Schiff wählen
                                                    </div>
                                                    <span class="badge bg-dark text-white" style="font-size: 0.65rem;">Vierwaldstättersee</span>
                                                </div>
                                                <div class="d-flex flex-column gap-2">
                                                    <label class="p-3 rounded-3 border d-flex align-items-center justify-content-between cursor-pointer" style="background-color: #faf7f2; border-color: #d5c5b2 !important;">
                                                        <div class="d-flex align-items-center gap-3">
                                                            <input type="radio" name="checkin_boat" value="boesch_620" checked class="form-check-input mt-0">
                                                            <div>
                                                                <strong class="text-dark d-block">Boesch 620 Bimini De Luxe (Edelholz-Mahagoni)</strong>
                                                                <span class="text-muted small">V8 Innenborder 260 PS · 6 Personen · Liegeplatz Alpenquai Luzern</span>
                                                            </div>
                                                        </div>
                                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning fw-bold">VIP Charter</span>
                                                    </label>
                                                    <label class="p-3 rounded-3 border d-flex align-items-center justify-content-between cursor-pointer" style="background-color: #faf7f2; border-color: #dfd0bc !important;">
                                                        <div class="d-flex align-items-center gap-3">
                                                            <input type="radio" name="checkin_boat" value="training_boat" class="form-check-input mt-0">
                                                            <div>
                                                                <strong class="text-dark d-block">Fahrschulboot Kat. A (Alpenquai Luzern / Stansstad)</strong>
                                                                <span class="text-muted small">Aussenborder 70 PS · Schulungs- &amp; Prüfungsboot</span>
                                                            </div>
                                                        </div>
                                                        <span class="badge bg-secondary-subtle text-secondary border">Fahrschule</span>
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- Amtliche BSV Sicherheitsausrüstung -->
                                            <div class="card border rounded-4 p-3.5 mb-3 bg-white" style="border-color: #dfd0bc !important;">
                                                <div class="d-flex align-items-center justify-content-between mb-2">
                                                    <div class="fw-bold text-dark small d-flex align-items-center gap-2">
                                                        <i class="bi bi-shield-check text-success"></i>
                                                        2. Gesetzliche Ausrüstungskontrolle (Art. 132 ff. BSV)
                                                    </div>
                                                    <span class="badge bg-success-subtle text-success border border-success" style="font-size: 0.65rem;">Pflicht</span>
                                                </div>
                                                <p class="text-muted small mb-3">Bitte überprüfe die vorgeschriebene Sicherheitsausrüstung vor dem Ablegen an Bord:</p>

                                                <div class="d-flex flex-column gap-2 small text-dark">
                                                    <label class="form-check p-2.5 rounded-2 bg-light border d-flex align-items-center gap-2 mb-0">
                                                        <input class="form-check-input ms-0 mt-0 checkin-req-item" type="checkbox" checked id="chkLifejackets">
                                                        <span><strong>Rettungswesten (150N EN ISO 12402):</strong> Für jede Person an Bord griffbereit vorhanden</span>
                                                    </label>
                                                    <label class="form-check p-2.5 rounded-2 bg-light border d-flex align-items-center gap-2 mb-0">
                                                        <input class="form-check-input ms-0 mt-0 checkin-req-item" type="checkbox" checked id="chkDistress">
                                                        <span><strong>Notflagge (rot, 60x60cm) &amp; Signalhorn:</strong> Voll funktionsfähig und griffbereit</span>
                                                    </label>
                                                    <label class="form-check p-2.5 rounded-2 bg-light border d-flex align-items-center gap-2 mb-0">
                                                        <input class="form-check-input ms-0 mt-0 checkin-req-item" type="checkbox" checked id="chkLines">
                                                        <span><strong>Festmacher &amp; Fender:</strong> 4 Fender sowie Vor-, Achter- und Springleinen an Bord</span>
                                                    </label>
                                                    <label class="form-check p-2.5 rounded-2 bg-light border d-flex align-items-center gap-2 mb-0">
                                                        <input class="form-check-input ms-0 mt-0 checkin-req-item" type="checkbox" checked id="chkAnchor">
                                                        <span><strong>Anker mit Ankerleine:</strong> Mindestens 15m Leine mit Kettenvorlauf einsatzbereit</span>
                                                    </label>
                                                    <label class="form-check p-2.5 rounded-2 bg-light border d-flex align-items-center gap-2 mb-0">
                                                        <input class="form-check-input ms-0 mt-0 checkin-req-item" type="checkbox" checked id="chkPump">
                                                        <span><strong>Handlenzpumpe / Bilge:</strong> Bilge ist sauber und trocken, Handlenzpumpe geprüft</span>
                                                    </label>
                                                </div>
                                            </div>

                                        </div>

                                        <!-- Rechte Spalte: Treibstoff, Zustand & Erklärung -->
                                        <div class="col-12 col-lg-5">
                                            
                                            <!-- Treibstoffstand bei Übernahme -->
                                            <div class="card border rounded-4 p-3.5 mb-3 bg-white" style="border-color: #dfd0bc !important;">
                                                <div class="fw-bold text-dark small d-flex align-items-center gap-2 mb-2">
                                                    <i class="bi bi-fuel-pump text-warning"></i>
                                                    3. Treibstoff-Füllstand bei Übernahme
                                                </div>
                                                <p class="text-muted small mb-2.5">Boote werden vollgetankt (1/1) übergeben. Bitte Füllstand ablesen:</p>
                                                
                                                <div class="row g-1.5 mb-2">
                                                    <div class="col-3">
                                                        <button type="button" class="btn btn-sm w-100 fw-bold fuel-select-btn bg-dark text-white rounded-2" onclick="selectFuelTakeover('1/1 Voll', this)">
                                                            1/1 Voll
                                                        </button>
                                                    </div>
                                                    <div class="col-3">
                                                        <button type="button" class="btn btn-sm w-100 fw-bold fuel-select-btn btn-outline-secondary rounded-2" onclick="selectFuelTakeover('3/4', this)">
                                                            3/4
                                                        </button>
                                                    </div>
                                                    <div class="col-3">
                                                        <button type="button" class="btn btn-sm w-100 fw-bold fuel-select-btn btn-outline-secondary rounded-2" onclick="selectFuelTakeover('1/2', this)">
                                                            1/2
                                                        </button>
                                                    </div>
                                                    <div class="col-3">
                                                        <button type="button" class="btn btn-sm w-100 fw-bold fuel-select-btn btn-outline-secondary rounded-2" onclick="selectFuelTakeover('1/4', this)">
                                                            1/4
                                                        </button>
                                                    </div>
                                                </div>
                                                <input type="hidden" id="selectedTakeoverFuel" value="1/1 Voll">
                                                <div class="text-success small fw-medium mt-1">
                                                    <i class="bi bi-info-circle me-1"></i> Tankregel: Rückgabe erfolgt im gleichen Füllstand oder Abrechnung nach Verbrauch (CHF 2.50 / Liter).
                                                </div>
                                            </div>

                                            <!-- Rumpf & Propeller Status -->
                                            <div class="card border rounded-4 p-3.5 mb-3 bg-white" style="border-color: #dfd0bc !important;">
                                                <div class="fw-bold text-dark small d-flex align-items-center gap-2 mb-2">
                                                    <i class="bi bi-search text-secondary"></i>
                                                    4. Rumpf- &amp; Mahagoni-Kontrolle
                                                </div>
                                                <div class="d-flex flex-column gap-2 small">
                                                    <label class="form-check p-2 rounded-2 bg-light border d-flex align-items-center gap-2 mb-0">
                                                        <input type="radio" name="hull_condition" value="perfect" checked class="form-check-input mt-0">
                                                        <span><strong>Makelloser Zustand:</strong> Keine Kratzer, Scheuerstellen oder Grundberührung</span>
                                                    </label>
                                                    <label class="form-check p-2 rounded-2 bg-light border d-flex align-items-center gap-2 mb-0">
                                                        <input type="radio" name="hull_condition" value="damage" class="form-check-input mt-0" onchange="toggleDamageNote(this)">
                                                        <span><strong>Vorschaden / Notiz:</strong> Bestehende Kratzer oder Makel vorhanden</span>
                                                    </label>
                                                </div>
                                                <div id="damageNoteBox" class="mt-2" style="display: none;">
                                                    <textarea class="form-control form-control-sm rounded-2 bg-white" rows="2" placeholder="Schaden beschreiben (z. B. leichte Scheuerstelle an Steuerbord-Badeleiter)..."></textarea>
                                                </div>
                                            </div>

                                            <!-- Gesetzliche Bestätigung Schiffsführer -->
                                            <div class="card border rounded-4 p-3.5 mb-3 bg-white" style="border-color: #dfd0bc !important;">
                                                <div class="fw-bold text-dark small d-flex align-items-center gap-2 mb-2">
                                                    <i class="bi bi-pen text-dark"></i>
                                                    5. Schiffsführer-Erklärung (Schweizer Recht)
                                                </div>
                                                <div class="d-flex flex-column gap-2 small text-dark">
                                                    <label class="form-check mb-0">
                                                        <input class="form-check-input checkin-legal-req" type="checkbox" checked id="chkLegalLicense">
                                                        <span>Ich besitze einen gültigen <strong>Schiffsführerausweis Kategorie A</strong>.</span>
                                                    </label>
                                                    <label class="form-check mb-0">
                                                        <input class="form-check-input checkin-legal-req" type="checkbox" checked id="chkLegalAlcohol">
                                                        <span><strong>Nulltoleranz (0.00 ‰ Alkohol):</strong> Schiffsführung erfolgt strikt nüchtern (Art. 78a BSV).</span>
                                                    </label>
                                                    <label class="form-check mb-0">
                                                        <input class="form-check-input checkin-legal-req" type="checkbox" checked id="chkLegalStorm">
                                                        <span><strong>Sturmwarndienst:</strong> Bei 90 Blitzen/Min (Sturmwarnung) laufe ich unverzüglich den nächsten Schutzhafen an.</span>
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- Digitaler Freigabe-Button -->
                                            <button type="button" class="btn w-100 fw-bold py-2.5 rounded-3 text-white shadow-sm d-flex align-items-center justify-content-center gap-2" style="background-color: #065f46;" onclick="submitTakeoverCheckin()">
                                                <i class="bi bi-qr-code fs-5"></i>
                                                <span>Schiffsübernahme digital freigeben &amp; auslaufen &rarr;</span>
                                            </button>

                                        </div>

                                    </div>
                                </div>
                            </div>

                            <!-- Panel 2: Schiffsrückgabe (Nach dem Einlaufen) -->
                            <div id="checkinPanelReturn" class="checkin-panel" style="display: none;">
                                
                                <div id="checkoutSuccessBox" class="p-4 rounded-4 mb-4 bg-white border shadow-sm" style="border-color: #a7f3d0 !important; display: none;">
                                    <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
                                        <span class="p-2 rounded-circle bg-success text-white d-inline-flex">
                                            <i class="bi bi-check2-all fs-3"></i>
                                        </span>
                                        <div>
                                            <h5 class="fw-bold text-success mb-0">Schiffsrückgabe erfolgreich protokolliert!</h5>
                                            <span class="text-muted small">Das Boot wurde ordnungsgemäss zurückgegeben und das Übergabeprotokoll gespeichert.</span>
                                        </div>
                                    </div>
                                    <p class="text-muted small mb-0">
                                        Vielen Dank für deine Fahrt mit Coaching Boot. Die Kautionsfreigabe bzw. Treibstoffabrechnung wird automatisch verarbeitet.
                                    </p>
                                </div>

                                <div id="checkoutFormContainer" class="row g-4">
                                    <div class="col-12 col-lg-6">
                                        <div class="card border rounded-4 p-3.5 bg-white h-100" style="border-color: #dfd0bc !important;">
                                            <h6 class="fw-bold text-dark mb-2 d-flex align-items-center gap-2">
                                                <i class="bi bi-box-arrow-in-left text-success"></i>
                                                Rückgabe-Checkliste
                                            </h6>
                                            <div class="d-flex flex-column gap-2 small text-dark mt-3">
                                                <label class="form-check p-2.5 rounded-2 bg-light border d-flex align-items-center gap-2 mb-0">
                                                    <input class="form-check-input ms-0 mt-0 checkout-req" type="checkbox" checked>
                                                    <span><strong>Liegeplatz Alpenquai:</strong> Boot sauber festgemacht (Vor-, Achterleine, Spring auf Klampe belegt)</span>
                                                </label>
                                                <label class="form-check p-2.5 rounded-2 bg-light border d-flex align-items-center gap-2 mb-0">
                                                    <input class="form-check-input ms-0 mt-0 checkout-req" type="checkbox" checked>
                                                    <span><strong>Fender:</strong> Alle 4 Fender an der Stegseite korrekt auf Steghöhe positioniert</span>
                                                </label>
                                                <label class="form-check p-2.5 rounded-2 bg-light border d-flex align-items-center gap-2 mb-0">
                                                    <input class="form-check-input ms-0 mt-0 checkout-req" type="checkbox" checked>
                                                    <span><strong>Verdeck &amp; Sauberkeit:</strong> Bimini-Verdeck montiert, kein Müll oder Wasser an Bord</span>
                                                </label>
                                                <label class="form-check p-2.5 rounded-2 bg-light border d-flex align-items-center gap-2 mb-0">
                                                    <input class="form-check-input ms-0 mt-0 checkout-req" type="checkbox" checked>
                                                    <span><strong>Hauptschalter &amp; Schlüssel:</strong> Batterie-Hauptschalter auf OFF, Zündschlüssel im Schlüsselsafe</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <div class="card border rounded-4 p-3.5 bg-white h-100 d-flex flex-column justify-content-between" style="border-color: #dfd0bc !important;">
                                            <div>
                                                <h6 class="fw-bold text-dark mb-2 d-flex align-items-center gap-2">
                                                    <i class="bi bi-fuel-pump text-warning"></i>
                                                    End-Tankfüllstand erfassen
                                                </h6>
                                                <p class="text-muted small mb-3">Wähle den aktuellen Füllstand bei Rückgabe:</p>
                                                <div class="row g-2 mb-3">
                                                    <div class="col-3">
                                                        <button type="button" class="btn btn-sm w-100 fw-bold fuel-return-btn bg-dark text-white rounded-2" onclick="selectFuelReturn('1/1 Voll', this)">1/1 Voll</button>
                                                    </div>
                                                    <div class="col-3">
                                                        <button type="button" class="btn btn-sm w-100 fw-bold fuel-return-btn btn-outline-secondary rounded-2" onclick="selectFuelReturn('3/4', this)">3/4</button>
                                                    </div>
                                                    <div class="col-3">
                                                        <button type="button" class="btn btn-sm w-100 fw-bold fuel-return-btn btn-outline-secondary rounded-2" onclick="selectFuelReturn('1/2', this)">1/2</button>
                                                    </div>
                                                    <div class="col-3">
                                                        <button type="button" class="btn btn-sm w-100 fw-bold fuel-return-btn btn-outline-secondary rounded-2" onclick="selectFuelReturn('1/4', this)">1/4</button>
                                                    </div>
                                                </div>

                                                <label class="form-label small fw-bold text-dark mb-1">Bemerkungen / Vorfälle während der Miete (optional)</label>
                                                <textarea class="form-control form-control-sm rounded-3 bg-light" rows="3" placeholder="Besonderheiten, Wellengang oder Treibgut..."></textarea>
                                            </div>

                                            <button type="button" class="btn w-100 fw-bold py-2.5 rounded-3 text-white shadow-sm mt-3 d-flex align-items-center justify-content-center gap-2" style="background-color: #2d2421;" onclick="submitReturnCheckin()">
                                                <i class="bi bi-clipboard-check text-warning"></i>
                                                <span>Schiffsrückgabe jetzt digital abschliessen &rarr;</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Panel 3: Schiffspapiere & Notfall -->
                            <div id="checkinPanelEmergency" class="checkin-panel" style="display: none;">
                                <div class="row g-4">
                                    <div class="col-12 col-md-6">
                                        <div class="card border rounded-4 p-4 bg-white h-100" style="border-color: #dfd0bc !important;">
                                            <h6 class="fw-bold text-danger mb-3 d-flex align-items-center gap-2">
                                                <i class="bi bi-telephone-fill"></i>
                                                Wichtige Notfallnummern (Vierwaldstättersee)
                                            </h6>
                                            <div class="d-flex flex-column gap-2.5 small">
                                                <div class="p-3 rounded-3 bg-danger-subtle border border-danger-subtle d-flex align-items-center justify-content-between">
                                                    <div>
                                                        <strong class="text-danger d-block">Schifffahrtspolizei &amp; Seepolizei Luzern</strong>
                                                        <span class="text-muted">Rund um die Uhr Einsatzleitzentrale</span>
                                                    </div>
                                                    <a href="tel:+41412488117" class="btn btn-sm btn-danger fw-bold rounded-2 px-3">
                                                        +41 41 248 81 17
                                                    </a>
                                                </div>
                                                <div class="p-3 rounded-3 bg-light border d-flex align-items-center justify-content-between">
                                                    <div>
                                                        <strong class="text-dark d-block">Allgemeine Notrufe Schweiz</strong>
                                                        <span class="text-muted">Polizei 117 · Sanität 144 · Euro-Notruf 112</span>
                                                    </div>
                                                    <span class="badge bg-dark fs-6 px-3 py-2">112 / 117</span>
                                                </div>
                                                <div class="p-3 rounded-3 bg-light border d-flex align-items-center justify-content-between">
                                                    <div>
                                                        <strong class="text-dark d-block">Coaching Boot Hotline (Fahrlehrer / Charter)</strong>
                                                        <span class="text-muted">Technischer Support &amp; Rückfragen</span>
                                                    </div>
                                                    <a href="tel:+41793400505" class="btn btn-sm btn-outline-dark fw-bold rounded-2 px-3">
                                                        079 340 05 05
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <div class="card border rounded-4 p-4 bg-white h-100" style="border-color: #dfd0bc !important;">
                                            <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                                                <i class="bi bi-file-earmark-check text-primary"></i>
                                                Digitale Schiffspapiere &amp; Zulassung
                                            </h6>
                                            <div class="d-flex flex-column gap-2 small">
                                                <div class="p-3 rounded-3 bg-light border">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <strong>Schiffsausweis (Kanton Luzern)</strong>
                                                        <span class="badge bg-success-subtle text-success">Gültig bis 2027</span>
                                                    </div>
                                                    <span class="text-muted">Immatrikulation LU 88412 · Typ: Boesch 620 Bimini De Luxe</span>
                                                </div>
                                                <div class="p-3 rounded-3 bg-light border">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <strong>Abgasnachweis &amp; Lärmzertifikat</strong>
                                                        <span class="badge bg-success-subtle text-success">Stufe BSO II</span>
                                                    </div>
                                                    <span class="text-muted">Zugelassen für alle Schweizer Seen inkl. Bodensee</span>
                                                </div>
                                                <div class="p-3 rounded-3 bg-light border">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <strong>Kaskoversicherung &amp; Haftpflicht</strong>
                                                        <span class="badge bg-primary-subtle text-primary">Vollkasko</span>
                                                    </div>
                                                    <span class="text-muted">Deckung CHF 5'000'000.- · Selbstbehalt CHF 1'000.-</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="modal-footer px-4 py-3" style="background-color: #eee4d5; border-top: 1px solid #dfd0bc;">
                            <span class="small text-muted me-auto">
                                <i class="bi bi-shield-lock me-1"></i> Verschlüsselte Telemetrie- und Schiffsübergabe nach BSV Richtlinien.
                            </span>
                            <button type="button" class="btn btn-outline-secondary rounded-3 btn-sm px-3" data-bs-dismiss="modal">Schließen</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================================== -->
            <!-- MODAL 5: E-LEARNING VIDEOPORTAL & PRÜFUNGSTRAINER               -->
            <!-- ============================================================== -->
            <div class="modal fade" id="elearningModal" tabindex="-1" aria-labelledby="elearningModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
                    <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden" style="background-color: #f6f0e7;">
                        <div class="modal-header border-bottom px-4 pt-4 pb-3" style="background-color: #2d2421; color: #ffffff;">
                            <div class="d-flex align-items-center gap-3">
                                <span class="p-2.5 rounded-3 d-inline-flex" style="background-color: rgba(34, 211, 238, 0.2); color: #22d3ee;">
                                    <i class="bi bi-play-circle-fill fs-4"></i>
                                </span>
                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        <h5 class="modal-title fw-bold text-white mb-0" id="elearningModalLabel">Nautik E-Learning &amp; Prüfungstrainer</h5>
                                        <span class="badge rounded-pill fw-bold text-uppercase" style="background-color: #164e63; color: #67e8f9; font-size: 0.65rem;">Kat. A</span>
                                    </div>
                                    <span class="text-light text-opacity-75 small">Prüfungsstoff Schifffahrtsamt Kat. A &amp; Manöver-Lehrfilme Vierwaldstättersee</span>
                                </div>
                            </div>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schließen"></button>
                        </div>

                        <div class="modal-body p-4" style="background-color: #f6f0e7;">
                            
                            <!-- Tab-Leiste -->
                            <div class="d-flex flex-wrap gap-2 mb-4 p-1.5 rounded-3 bg-white border" style="border-color: #dfd0bc !important;">
                                <button type="button" class="btn btn-sm fw-bold px-3 py-2 rounded-2 elearning-tab-btn bg-dark text-white" id="tabBtnVideos" onclick="switchElearningTab('videos', this)">
                                    <i class="bi bi-camera-video-fill me-1.5 text-info"></i> 1. Manöver-Lehrfilme (5)
                                </button>
                                <button type="button" class="btn btn-sm fw-bold px-3 py-2 rounded-2 elearning-tab-btn text-secondary" id="tabBtnCharts" onclick="switchElearningTab('charts', this)">
                                    <i class="bi bi-card-image me-1.5 text-warning"></i> 2. Bildtafeln &amp; Seezeichen
                                </button>
                                <button type="button" class="btn btn-sm fw-bold px-3 py-2 rounded-2 elearning-tab-btn text-secondary" id="tabBtnQuiz" onclick="switchElearningTab('quiz', this)">
                                    <i class="bi bi-patch-question-fill me-1.5 text-success"></i> 3. Theoriefragen &amp; Quiz
                                </button>
                                <button type="button" class="btn btn-sm fw-bold px-3 py-2 rounded-2 elearning-tab-btn text-secondary ms-auto" id="tabBtnDownloads" onclick="switchElearningTab('downloads', this)">
                                    <i class="bi bi-file-earmark-arrow-down-fill me-1.5 text-primary"></i> 4. Merkblätter &amp; BSV
                                </button>
                            </div>

                            <!-- Tab 1: Manöver-Lehrfilme -->
                            <div id="elearningSectionVideos" class="elearning-section">
                                
                                <!-- Grosser Video-Player Mockup & Details zum aktiven Video -->
                                <div class="card border rounded-4 p-4 mb-4 bg-white shadow-xs" style="border-color: #dfd0bc !important;">
                                    <div class="row g-4">
                                        <div class="col-12 col-lg-7">
                                            <div class="position-relative rounded-3 overflow-hidden bg-dark d-flex align-items-center justify-content-center shadow-sm" style="min-height: 280px; background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
                                                <div class="text-center p-4">
                                                    <span class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3 shadow-lg" style="width: 64px; height: 64px; background-color: #0284c7; color: #ffffff;">
                                                        <i class="bi bi-play-fill fs-1 ms-1"></i>
                                                    </span>
                                                    <h6 class="text-white fw-bold mb-1" id="activeVideoTitleDisplay">Hafenbox rückwärts anlegen (Pfahlbox)</h6>
                                                    <span class="badge bg-info text-dark fw-bold mb-2" id="activeVideoCategoryDisplay">Hafenmanöver</span>
                                                    <div class="text-light text-opacity-75 small" id="activeVideoDurationDisplay">Dauer: 12:40 Min · 4K Praxisaufnahme Vierwaldstättersee</div>
                                                </div>
                                                <div class="position-absolute bottom-0 start-0 end-0 p-3 d-flex align-items-center justify-content-between" style="background: linear-gradient(to top, rgba(0,0,0,0.85), transparent);">
                                                    <div class="d-flex align-items-center gap-2 text-white small">
                                                        <i class="bi bi-broadcast text-danger animate-pulse"></i>
                                                        <span id="activeVideoQuality">4K Ultra HD · Instruktor-Kommentar</span>
                                                    </div>
                                                    <span class="badge bg-dark bg-opacity-75 border text-light small" id="activeVideoBsv">Art. 44 BSV</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12 col-lg-5 d-flex flex-column justify-content-between">
                                            <div>
                                                <div class="d-flex align-items-center justify-content-between mb-2">
                                                    <span class="badge bg-warning-subtle text-warning-emphasis fw-bold">Prüfungsrelevant</span>
                                                    <span class="text-muted small" id="activeVideoDifficulty">Niveau: Fortgeschritten</span>
                                                </div>
                                                <h5 class="fw-bold text-dark mb-2" id="activeVideoHeadline">Hafenbox rückwärts anlegen mit Luv-Pfahl</h5>
                                                <p class="text-muted small mb-3" id="activeVideoDesc">
                                                    Präzises Einsteuern zwischen die Dalben bei Seitenwind. Nutzung des Radeffekts beim Aufstoppen und dosierter Gaseinsatz für kontrollierten hydrodynamischen Ruderdruck.
                                                </p>
                                                
                                                <h6 class="fw-bold text-dark small mb-2">Kapitelmarken:</h6>
                                                <div class="d-flex flex-column gap-1.5 small mb-3" id="activeVideoChapters">
                                                    <div class="p-2 rounded bg-light border d-flex justify-content-between">
                                                        <span>00:00 – Anfahrt &amp; Windanalyse</span>
                                                        <span class="badge bg-secondary">00:00</span>
                                                    </div>
                                                    <div class="p-2 rounded bg-light border d-flex justify-content-between">
                                                        <span>03:45 – Eindrehen &amp; Luv-Pfahl zuerst</span>
                                                        <span class="badge bg-secondary">03:45</span>
                                                    </div>
                                                    <div class="p-2 rounded bg-light border d-flex justify-content-between">
                                                        <span>08:30 – Radeffekt beim Aufstoppen</span>
                                                        <span class="badge bg-secondary">08:30</span>
                                                    </div>
                                                    <div class="p-2 rounded bg-light border d-flex justify-content-between">
                                                        <span>11:45 – Festmachen &amp; Heckleine belegen</span>
                                                        <span class="badge bg-secondary">11:45</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="p-2.5 rounded-3 bg-warning-subtle border border-warning-subtle text-warning-emphasis small">
                                                <strong>Tipp vom Chef-Instruktor:</strong> Immer gegen den Wind eindrehen und dem Luv-Pfahl strikt den Vorrang geben. Kurze, dosierte Gasstösse statt Dauergas!
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Liste aller Lehrfilme zum Auswählen -->
                                <h6 class="fw-bold text-dark mb-3">Alle Lehrfilme für die Schiffsführerprüfung Kat. A:</h6>
                                <div class="row g-3">
                                    
                                    <!-- Video 1 -->
                                    <div class="col-12 col-md-6 col-lg-4">
                                        <div class="card border rounded-3 p-3 bg-white h-100 hover-shadow cursor-pointer border-primary" style="border-color: #0284c7 !important;" onclick="selectVideo(1)">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="badge bg-info-subtle text-info-emphasis fw-bold">Hafenmanöver</span>
                                                <span class="text-muted small">12:40 Min</span>
                                            </div>
                                            <h6 class="fw-bold text-dark mb-1">Hafenbox rückwärts anlegen</h6>
                                            <p class="text-muted small mb-3">Luv-Pfahl Technik, Radeffekt und punktgenaues Abstoppen.</p>
                                            <button type="button" class="btn btn-sm btn-primary w-100 fw-bold rounded-2">
                                                <i class="bi bi-play-fill me-1"></i> Video auswählen
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Video 2 -->
                                    <div class="col-12 col-md-6 col-lg-4">
                                        <div class="card border rounded-3 p-3 bg-white h-100 hover-shadow cursor-pointer" style="border-color: #dfd0bc !important;" onclick="selectVideo(2)">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="badge bg-danger-subtle text-danger fw-bold">Sicherheit &amp; Notfall</span>
                                                <span class="text-muted small">09:45 Min</span>
                                            </div>
                                            <h6 class="fw-bold text-dark mb-1">Boje über Bord (Mann über Bord)</h6>
                                            <p class="text-muted small mb-3">Sofortmassnahmen: Auskuppeln, Heck abwenden, Rettungsring und Anfahrt gegen den Wind.</p>
                                            <button type="button" class="btn btn-sm btn-outline-dark w-100 fw-bold rounded-2">
                                                <i class="bi bi-play-fill me-1"></i> Video auswählen
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Video 3 -->
                                    <div class="col-12 col-md-6 col-lg-4">
                                        <div class="card border rounded-3 p-3 bg-white h-100 hover-shadow cursor-pointer" style="border-color: #dfd0bc !important;" onclick="selectVideo(3)">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="badge bg-success-subtle text-success fw-bold">Anlegen</span>
                                                <span class="text-muted small">08:15 Min</span>
                                            </div>
                                            <h6 class="fw-bold text-dark mb-1">Anlegen längsseits bei Seitenwind</h6>
                                            <p class="text-muted small mb-3">Vorspring setzen, Ruder zur Pier einschlagen und sanftes Eindampfen in die Spring.</p>
                                            <button type="button" class="btn btn-sm btn-outline-dark w-100 fw-bold rounded-2">
                                                <i class="bi bi-play-fill me-1"></i> Video auswählen
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Video 4 -->
                                    <div class="col-12 col-md-6 col-lg-4">
                                        <div class="card border rounded-3 p-3 bg-white h-100 hover-shadow cursor-pointer" style="border-color: #dfd0bc !important;" onclick="selectVideo(4)">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="badge bg-warning-subtle text-warning-emphasis fw-bold">Schiffsführung</span>
                                                <span class="text-muted small">06:30 Min</span>
                                            </div>
                                            <h6 class="fw-bold text-dark mb-1">Radeffekt &amp; Drehen am Ort</h6>
                                            <p class="text-muted small mb-3">Rechtsdrehender Propeller: Versetzen des Hecks beim Rückwärtsfahren gezielt ausnutzen.</p>
                                            <button type="button" class="btn btn-sm btn-outline-dark w-100 fw-bold rounded-2">
                                                <i class="bi bi-play-fill me-1"></i> Video auswählen
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Video 5 -->
                                    <div class="col-12 col-md-6 col-lg-4">
                                        <div class="card border rounded-3 p-3 bg-white h-100 hover-shadow cursor-pointer" style="border-color: #dfd0bc !important;" onclick="selectVideo(5)">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="badge bg-secondary-subtle text-secondary fw-bold">Seemannschaft</span>
                                                <span class="text-muted small">07:10 Min</span>
                                            </div>
                                            <h6 class="fw-bold text-dark mb-1">Nautische Knoten für die Prüfung</h6>
                                            <p class="text-muted small mb-3">Palstek (Rettungsschlinge), Webleinstek, Belegen auf der Klampe und Schotstek.</p>
                                            <button type="button" class="btn btn-sm btn-outline-dark w-100 fw-bold rounded-2">
                                                <i class="bi bi-play-fill me-1"></i> Video auswählen
                                            </button>
                                        </div>
                                    </div>

                                </div>

                            </div>

                            <!-- Tab 2: Bildtafeln & Seezeichen -->
                            <div id="elearningSectionCharts" class="elearning-section" style="display: none;">
                                <div class="row g-4">
                                    
                                    <!-- Tafel 1: BSV Lichterführung -->
                                    <div class="col-12 col-md-6">
                                        <div class="card border rounded-4 p-4 bg-white h-100" style="border-color: #dfd0bc !important;">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <h6 class="fw-bold text-dark mb-0">BSV Lichterführung auf Schweizer Seen</h6>
                                                <span class="badge bg-dark">Art. 18–35 BSV</span>
                                            </div>
                                            <p class="text-muted small mb-3">Gesetzliche Nachtbeleuchtung für Motorboote und Vorrangschiffe:</p>
                                            
                                            <div class="p-3 rounded-3 bg-dark text-white small mb-3">
                                                <div class="d-flex justify-content-between mb-1.5 pb-1.5 border-bottom border-secondary">
                                                    <span><strong>Topplicht (weiss):</strong> 225° Bogen (nach vorne)</span>
                                                    <span class="badge bg-light text-dark">Mind. 2 m über Kiel</span>
                                                </div>
                                                <div class="d-flex justify-content-between mb-1.5 pb-1.5 border-bottom border-secondary">
                                                    <span><strong>Steuerbordlicht (grün):</strong> 112.5° Sektor</span>
                                                    <span class="badge bg-success">Rechts</span>
                                                </div>
                                                <div class="d-flex justify-content-between mb-1.5 pb-1.5 border-bottom border-secondary">
                                                    <span><strong>Backbordlicht (rot):</strong> 112.5° Sektor</span>
                                                    <span class="badge bg-danger">Links</span>
                                                </div>
                                                <div class="d-flex justify-content-between">
                                                    <span><strong>Hecklicht (weiss):</strong> 135° Bogen (nach hinten)</span>
                                                    <span class="badge bg-secondary">Heck</span>
                                                </div>
                                            </div>
                                            <div class="p-2.5 rounded-2 bg-light border text-muted small">
                                                <strong>Vorrangschiffe (Kursschiffe):</strong> Weisses Topplicht + grünes 360° Rundumlicht mindestens 1m über dem Topplicht!
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Tafel 2: Sturmwarnsystem Schweizer Seen -->
                                    <div class="col-12 col-md-6">
                                        <div class="card border rounded-4 p-4 bg-white h-100" style="border-color: #dfd0bc !important;">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <h6 class="fw-bold text-dark mb-0">Schweizer Sturmwarnsystem (40 / 90)</h6>
                                                <span class="badge bg-warning text-dark fw-bold">Art. 40 BSV</span>
                                            </div>
                                            <p class="text-muted small mb-3">Rundum-Blinklichter an den Ufern des Vierwaldstättersees:</p>

                                            <div class="p-3 rounded-3 bg-warning-subtle border border-warning-subtle mb-3">
                                                <div class="d-flex align-items-center justify-content-between mb-1">
                                                    <strong class="text-warning-emphasis">40 Blitze / Minute (Starkwindwarnung)</strong>
                                                    <span class="badge bg-warning text-dark">25–33 kn</span>
                                                </div>
                                                <p class="text-dark small mb-0">Kündigt Winde mit Böenspitzen von ca. 45–60 km/h an. Gefahrenstellen meiden, Uferzone beachten.</p>
                                            </div>

                                            <div class="p-3 rounded-3 bg-danger-subtle border border-danger-subtle mb-3">
                                                <div class="d-flex align-items-center justify-content-between mb-1">
                                                    <strong class="text-danger">90 Blitze / Minute (Sturmwarnung)</strong>
                                                    <span class="badge bg-danger text-white">ab 34 kn (60+ km/h)</span>
                                                </div>
                                                <p class="text-dark small mb-0">Akute Sturmgefahr! Rettungswesten anlegen und unverzüglich den nächsten Schutzhafen anlaufen.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Tafel 3: Kardinalzeichen IALA-A -->
                                    <div class="col-12 col-md-6">
                                        <div class="card border rounded-4 p-4 bg-white h-100" style="border-color: #dfd0bc !important;">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <h6 class="fw-bold text-dark mb-0">Seezeichen &amp; Betonnung (Kardinalsystem)</h6>
                                                <span class="badge bg-dark">BSV Anhang 1</span>
                                            </div>
                                            <p class="text-muted small mb-3">Kardinaltonnen kennzeichnen die sichere Seite einer Untiefe:</p>

                                            <div class="row g-2 small">
                                                <div class="col-6">
                                                    <div class="p-2.5 rounded-2 bg-light border">
                                                        <strong class="text-dark d-block">▲▲ NORD:</strong>
                                                        <span class="text-muted">Schwarz über Gelb · Spitzen nach oben · Nördlich passieren</span>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="p-2.5 rounded-2 bg-light border">
                                                        <strong class="text-dark d-block">▼▼ SÜD:</strong>
                                                        <span class="text-muted">Gelb über Schwarz · Spitzen nach unten · Südlich passieren</span>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="p-2.5 rounded-2 bg-light border">
                                                        <strong class="text-dark d-block">▲▼ OST:</strong>
                                                        <span class="text-muted">Schwarz-Gelb-Schwarz · Spitzen voneinander weg</span>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="p-2.5 rounded-2 bg-light border">
                                                        <strong class="text-dark d-block">▼▲ WEST:</strong>
                                                        <span class="text-muted">Gelb-Schwarz-Gelb · Spitzen zueinander gerichtet</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Tafel 4: BSV Vortrittspyramide -->
                                    <div class="col-12 col-md-6">
                                        <div class="card border rounded-4 p-4 bg-white h-100" style="border-color: #dfd0bc !important;">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <h6 class="fw-bold text-dark mb-0">Die amtliche BSV Vortrittspyramide</h6>
                                                <span class="badge bg-primary text-white">Art. 44 BSV</span>
                                            </div>
                                            <p class="text-muted small mb-3">Verbindliche Rangordnung auf Schweizer Binnengewässern:</p>

                                            <div class="d-flex flex-column gap-1.5 small text-dark">
                                                <div class="p-2 rounded-2 text-white fw-bold d-flex justify-content-between" style="background-color: #047857;">
                                                    <span>1. Kursschiffe (Vorrangschiffe)</span>
                                                    <span>Grüner Ball / Licht</span>
                                                </div>
                                                <div class="p-2 rounded-2 text-white fw-semibold d-flex justify-content-between" style="background-color: #0d9488;">
                                                    <span>2. Güterschiffe &amp; Berufsfischer</span>
                                                    <span>Gelber Ball</span>
                                                </div>
                                                <div class="p-2 rounded-2 text-white fw-semibold d-flex justify-content-between" style="background-color: #0284c7;">
                                                    <span>3. Segelschiffe (unter Segel)</span>
                                                    <span>Windregeln</span>
                                                </div>
                                                <div class="p-2 rounded-2 text-white fw-semibold d-flex justify-content-between" style="background-color: #64748b;">
                                                    <span>4. Ruderboote &amp; Paddler</span>
                                                    <span>Muskelkraft</span>
                                                </div>
                                                <div class="p-2 rounded-2 text-white fw-semibold d-flex justify-content-between" style="background-color: #334155;">
                                                    <span>5. Motorboote &amp; Mietboote</span>
                                                    <span>Weichen allen aus</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- Tab 3: Theoriefragen & Quiz -->
                            <div id="elearningSectionQuiz" class="elearning-section" style="display: none;">
                                <div class="card border rounded-4 p-4 bg-white mb-4 shadow-xs" style="border-color: #dfd0bc !important;">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div>
                                            <h5 class="fw-bold text-dark mb-0">Amtlicher BSV Theorie-Trainer (Kategorie A)</h5>
                                            <span class="text-muted small">Originalgetreue Multiple-Choice Prüfungsfragen der Schweizer Schifffahrtsämter</span>
                                        </div>
                                        <span class="badge bg-success-subtle text-success border border-success fw-bold px-3 py-1.5">
                                            Interaktives Quiz
                                        </span>
                                    </div>

                                    <!-- Frage 1 -->
                                    <div class="p-3.5 rounded-3 bg-light border mb-3 quiz-card" id="quizCard1">
                                        <div class="fw-bold text-dark mb-2">
                                            1. Wie viele orange Blitze pro Minute sendet das Warnlicht bei einer akuten STURMWARNUNG auf Schweizer Seen aus?
                                        </div>
                                        <div class="row g-2 mb-2">
                                            <div class="col-12 col-md-6">
                                                <button type="button" class="btn btn-sm btn-outline-secondary w-100 text-start p-2.5 rounded-2 quiz-opt" onclick="answerQuiz(1, 0, false, 'Falsch: 20 Blitze existieren im Schweizer System nicht.')">
                                                    A) 20 Blitze pro Minute
                                                </button>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <button type="button" class="btn btn-sm btn-outline-secondary w-100 text-start p-2.5 rounded-2 quiz-opt" onclick="answerQuiz(1, 1, false, 'Falsch: 40 Blitze signalisieren lediglich eine Starkwindwarnung (Böen 25-33 kn).')">
                                                    B) 40 Blitze pro Minute (Starkwind)
                                                </button>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <button type="button" class="btn btn-sm btn-outline-secondary w-100 text-start p-2.5 rounded-2 quiz-opt" onclick="answerQuiz(1, 2, true, 'Richtig! Gemäss Art. 40 Abs. 2 BSV signalisieren 90 Blitze pro Minute die Sturmwarnung (Böen ab 34 kn / 60 km/h). Sofort Hafen anlaufen!')">
                                                    C) 90 Blitze pro Minute (Sturmwarnung)
                                                </button>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <button type="button" class="btn btn-sm btn-outline-secondary w-100 text-start p-2.5 rounded-2 quiz-opt" onclick="answerQuiz(1, 3, false, 'Falsch: 120 Blitze gibt es nicht.')">
                                                    D) 120 Blitze pro Minute
                                                </button>
                                            </div>
                                        </div>
                                        <div class="quiz-feedback small mt-2" id="quizFeedback1" style="display: none;"></div>
                                    </div>

                                    <!-- Frage 2 -->
                                    <div class="p-3.5 rounded-3 bg-light border mb-3 quiz-card" id="quizCard2">
                                        <div class="fw-bold text-dark mb-2">
                                            2. Zwei motorisierte Sportboote nähern sich auf dem Vierwaldstättersee auf direktem Gegenkurs. Wie müssen die Schiffsführer ausweichen?
                                        </div>
                                        <div class="row g-2 mb-2">
                                            <div class="col-12 col-md-6">
                                                <button type="button" class="btn btn-sm btn-outline-secondary w-100 text-start p-2.5 rounded-2 quiz-opt" onclick="answerQuiz(2, 0, true, 'Richtig! Gemäss Art. 44 Abs. 2 BSV müssen beide Motorboote bei direktem Gegenkurs nach Steuerbord (nach rechts) ausweichen, sodass sie einander an Backbord (rot an rot) passieren.')">
                                                    A) Beide Schiffe weichen nach Steuerbord (nach rechts) aus
                                                </button>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <button type="button" class="btn btn-sm btn-outline-secondary w-100 text-start p-2.5 rounded-2 quiz-opt" onclick="answerQuiz(2, 1, false, 'Falsch: Bei Gegenkurs gilt kein Windvortritt für Motorboote.')">
                                                    B) Das leeseitige Schiff weicht aus
                                                </button>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <button type="button" class="btn btn-sm btn-outline-secondary w-100 text-start p-2.5 rounded-2 quiz-opt" onclick="answerQuiz(2, 2, false, 'Falsch: Beide Boote müssen aktiv nach Steuerbord ausweichen.')">
                                                    C) Beide weichen nach Backbord (links) aus
                                                </button>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <button type="button" class="btn btn-sm btn-outline-secondary w-100 text-start p-2.5 rounded-2 quiz-opt" onclick="answerQuiz(2, 3, false, 'Falsch: Die Motorleistung spielt beim Ausweichen keine Rolle.')">
                                                    D) Das schwächere Motorboot stoppt
                                                </button>
                                            </div>
                                        </div>
                                        <div class="quiz-feedback small mt-2" id="quizFeedback2" style="display: none;"></div>
                                    </div>

                                    <!-- Frage 3 -->
                                    <div class="p-3.5 rounded-3 bg-light border quiz-card" id="quizCard3">
                                        <div class="fw-bold text-dark mb-2">
                                            3. Welche Promillegrenze gilt für Schiffsführer von motorisierten Sport- und Mietbooten in der Schweiz?
                                        </div>
                                        <div class="row g-2 mb-2">
                                            <div class="col-12 col-md-6">
                                                <button type="button" class="btn btn-sm btn-outline-secondary w-100 text-start p-2.5 rounded-2 quiz-opt" onclick="answerQuiz(3, 0, false, 'Falsch: 0.5‰ gilt nur auf der Strasse, nicht für Schiffsführer.')">
                                                    A) 0.50 Promille wie im Strassenverkehr
                                                </button>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <button type="button" class="btn btn-sm btn-outline-secondary w-100 text-start p-2.5 rounded-2 quiz-opt" onclick="answerQuiz(3, 1, false, 'Falsch: Es gibt keine 0.2‰ Toleranzgrenze auf Schweizer Seen.')">
                                                    B) 0.20 Promille Toleranzgrenze
                                                </button>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <button type="button" class="btn btn-sm btn-outline-secondary w-100 text-start p-2.5 rounded-2 quiz-opt" onclick="answerQuiz(3, 2, true, 'Richtig! Seit dem 1. Januar 2020 gilt auf allen Schweizer Gewässern eine strikte 0.00‰-Promillegrenze (Nulltoleranz, Art. 78a BSV). Zuwiderhandlung führt zum sofortigen Entzug des Schiffsführerausweises.')">
                                                    C) Strikt 0.00 Promille (Nulltoleranz nach Art. 78a BSV)
                                                </button>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <button type="button" class="btn btn-sm btn-outline-secondary w-100 text-start p-2.5 rounded-2 quiz-opt" onclick="answerQuiz(3, 3, false, 'Falsch: 0.8‰ ist veraltet und unzulässig.')">
                                                    D) 0.80 Promille bei Tagfahrten
                                                </button>
                                            </div>
                                        </div>
                                        <div class="quiz-feedback small mt-2" id="quizFeedback3" style="display: none;"></div>
                                    </div>

                                </div>
                            </div>

                            <!-- Tab 4: Merkblätter & BSV -->
                            <div id="elearningSectionDownloads" class="elearning-section" style="display: none;">
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <div class="card border rounded-3 p-3.5 bg-white h-100" style="border-color: #dfd0bc !important;">
                                            <div class="d-flex align-items-center gap-3 mb-2">
                                                <div class="p-2.5 rounded-2 bg-primary text-white">
                                                    <i class="bi bi-file-earmark-pdf fs-4"></i>
                                                </div>
                                                <div>
                                                    <strong class="text-dark d-block">BSV – Binnenschifffahrtsverordnung Schweiz</strong>
                                                    <span class="text-muted small">Bundesrecht SR 747.201.1 · Volltext</span>
                                                </div>
                                            </div>
                                            <p class="text-muted small mb-3">Die offizielle Bundesverordnung über die Schifffahrt auf schweizerischen Gewässern.</p>
                                            <a href="https://www.fedlex.admin.ch/eli/cc/1979/337_337_337/de" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-dark fw-bold rounded-2">
                                                Gesetzestext auf Fedlex öffnen &rarr;
                                            </a>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <div class="card border rounded-3 p-3.5 bg-white h-100" style="border-color: #dfd0bc !important;">
                                            <div class="d-flex align-items-center gap-3 mb-2">
                                                <div class="p-2.5 rounded-2 bg-success text-white">
                                                    <i class="bi bi-compass fs-4"></i>
                                                </div>
                                                <div>
                                                    <strong class="text-dark d-block">Seerettung &amp; Notfall-Leitfaden</strong>
                                                    <span class="text-muted small">Kanton Luzern / Vierwaldstättersee</span>
                                                </div>
                                            </div>
                                            <p class="text-muted small mb-3">Checkliste bei Havarie, Wassereinbruch, Motorpanne oder Person über Bord.</p>
                                            <button type="button" class="btn btn-sm btn-outline-success fw-bold rounded-2" onclick="alert('Notfallblatt aufgerufen: Seerettung 112 / Seepolizei 041 248 81 17.')">
                                                Notfallblatt anzeigen &rarr;
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="modal-footer px-4 py-3" style="background-color: #eee4d5; border-top: 1px solid #dfd0bc;">
                            <span class="small text-muted me-auto">
                                Lerninhalte zertifiziert nach den Ausbildungsrichtlinien des Schweizerischen Schifffahrtsamtes (asa).
                            </span>
                            <button type="button" class="btn btn-dark rounded-3 btn-sm px-4 fw-bold" data-bs-dismiss="modal">Schließen</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================================== -->
            <!-- MODAL 6: MEINE RECHNUNGEN & SWISS QR-BILL                       -->
            <!-- ============================================================== -->
            <div class="modal fade" id="invoicesModal" tabindex="-1" aria-labelledby="invoicesModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                    <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden" style="background-color: #f6f0e7;">
                        <div class="modal-header border-bottom px-4 pt-4 pb-3" style="background-color: #2d2421; color: #ffffff;">
                            <div class="d-flex align-items-center gap-3">
                                <span class="p-2.5 rounded-3 d-inline-flex" style="background-color: rgba(192, 132, 252, 0.2); color: #c084fc;">
                                    <i class="bi bi-file-earmark-text-fill fs-4"></i>
                                </span>
                                <div>
                                    <h5 class="modal-title fw-bold text-white mb-0" id="invoicesModalLabel">Meine Rechnungen &amp; Swiss QR-Bill</h5>
                                    <span class="text-light text-opacity-75 small">Übersicht aller Ausbildungsrechnungen, Abos &amp; Mietquittungen</span>
                                </div>
                            </div>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schließen"></button>
                        </div>

                        <div class="modal-body p-4" style="background-color: #f6f0e7;">
                            
                            <!-- MWST-Hinweis gemäss Gesetz -->
                            <div class="p-3.5 rounded-3 mb-4 bg-white border" style="border-color: #dfd0bc !important;">
                                <div class="d-flex align-items-start gap-2.5">
                                    <i class="bi bi-info-circle-fill text-primary fs-5 mt-0.5"></i>
                                    <div>
                                        <strong class="text-dark small d-block">Schweizer Mehrwertsteuer &amp; Unternehmensidentifikation:</strong>
                                        <p class="text-muted small mb-1">
                                            <strong>Fahrschul-Lektionen</strong> und nautische Ausbildungen sind gemäss <strong>Art. 21 Abs. 2 Ziff. 11 MWSTG</strong> von der Schweizer Mehrwertsteuer befreit (0.0%).
                                        </p>
                                        <p class="text-muted small mb-0">
                                            Für <strong>Bootsvermietung &amp; Charter</strong> (z. B. Boesch 620 Bimini) wird der reguläre Satz von <strong>8.1% MWST</strong> erhoben. Unsere MWST-Nummer lautet: <strong class="text-dark">CHE-110.497.163 MWST</strong>.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Rechnungsliste -->
                            <h6 class="fw-bold text-dark mb-3">Rechnungsarchiv (Swiss QR-Bill):</h6>
                            <div class="d-flex flex-column gap-3 mb-4">
                                
                                <!-- Rechnung 1 -->
                                <div class="card border rounded-3 p-3.5 bg-white shadow-2xs" style="border-color: #dfd0bc !important;">
                                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-2">
                                        <div>
                                            <div class="d-flex align-items-center gap-2">
                                                <strong class="text-dark">Rechnung #INV-2026-089</strong>
                                                <span class="badge bg-success-subtle text-success border border-success fw-bold">Bezahlt</span>
                                            </div>
                                            <span class="text-muted small">10er VIP Ausbildungs-Abo Motorboot Kat. A</span>
                                        </div>
                                        <div class="text-md-end">
                                            <div class="h5 fw-extrabold text-dark mb-0">CHF 1'750.00</div>
                                            <span class="text-success small fw-medium">0.0% MWST (Art. 21 MWSTG)</span>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between pt-2 border-top small text-muted">
                                        <span>Datum: <?= date('d.m.Y', strtotime('-14 days')) ?> · Valuta: sofort bezahlt</span>
                                        <button type="button" class="btn btn-sm btn-outline-dark fw-bold rounded-2 px-3" onclick="window.print()">
                                            <i class="bi bi-printer me-1"></i> QR-Quittung drucken
                                        </button>
                                    </div>
                                </div>

                                <!-- Rechnung 2 -->
                                <div class="card border rounded-3 p-3.5 bg-white shadow-2xs" style="border-color: #dfd0bc !important;">
                                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-2">
                                        <div>
                                            <div class="d-flex align-items-center gap-2">
                                                <strong class="text-dark">Rechnung #INV-2026-104</strong>
                                                <span class="badge bg-success-subtle text-success border border-success fw-bold">Bezahlt</span>
                                            </div>
                                            <span class="text-muted small">Boesch 620 Bimini Miete (Halbtag 4 Std.)</span>
                                        </div>
                                        <div class="text-md-end">
                                            <div class="h5 fw-extrabold text-dark mb-0">CHF 486.45</div>
                                            <span class="text-muted small">Inkl. 8.1% MWST (CHF 36.45) · CHE-110.497.163</span>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between pt-2 border-top small text-muted">
                                        <span>Datum: <?= date('d.m.Y', strtotime('-3 days')) ?> · Treibstoff inbegriffen</span>
                                        <button type="button" class="btn btn-sm btn-outline-dark fw-bold rounded-2 px-3" onclick="window.print()">
                                            <i class="bi bi-printer me-1"></i> QR-Quittung drucken
                                        </button>
                                    </div>
                                </div>

                            </div>

                            <!-- Bank- & QR-Zahlungsangaben -->
                            <div class="p-3.5 rounded-3 bg-light border" style="border-color: #dfd0bc !important;">
                                <div class="row g-2 small text-dark">
                                    <div class="col-12 col-md-6">
                                        <span class="text-muted d-block">Bankverbindung / IBAN:</span>
                                        <strong>CH58 0077 7000 1234 5678 9 (Luzerner Kantonalbank)</strong>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <span class="text-muted d-block">Zahlungsempfänger:</span>
                                        <strong>Coaching Boot GmbH, Alpenquai, 6005 Luzern</strong>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="modal-footer px-4 py-3" style="background-color: #eee4d5; border-top: 1px solid #dfd0bc;">
                            <span class="small text-muted me-auto">
                                Schweizer QR-Bill konform nach SIX Interbank Standards.
                            </span>
                            <button type="button" class="btn btn-dark rounded-3 btn-sm px-4 fw-bold" data-bs-dismiss="modal">Schließen</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================================== -->
            <!-- MODAL 7: RECHTLICHES, AUSBILDUNGSRICHTLINIEN & AGB             -->
            <!-- ============================================================== -->
            <div class="modal fade" id="legalModal" tabindex="-1" aria-labelledby="legalModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                    <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden" style="background-color: #f6f0e7;">
                        <div class="modal-header border-bottom px-4 pt-4 pb-3" style="background-color: #2d2421; color: #ffffff;">
                            <div class="d-flex align-items-center gap-3">
                                <span class="p-2.5 rounded-3 d-inline-flex" style="background-color: rgba(129, 140, 248, 0.2); color: #818cf8;">
                                    <i class="bi bi-shield-check fs-4"></i>
                                </span>
                                <div>
                                    <h5 class="modal-title fw-bold text-white mb-0" id="legalModalLabel">Rechtliches &amp; Ausbildungsnachweis</h5>
                                    <span class="text-light text-opacity-75 small">Binnenschifffahrt, Schifffahrtsamt Kat. A &amp; MWST-Auskunft</span>
                                </div>
                            </div>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schließen"></button>
                        </div>

                        <div class="modal-body p-4" style="background-color: #f6f0e7;">
                            <div class="d-flex flex-column gap-3 small text-dark">
                                
                                <div class="p-3.5 rounded-3 bg-white border">
                                    <h6 class="fw-bold text-dark mb-1">1. Schiffsführerprüfung Motorboot (Kategorie A)</h6>
                                    <p class="text-muted mb-0">
                                        Die praktische Ausbildung richtet sich nach den verbindlichen Richtlinien der Vereinigung der Schifffahrtsämter (asa) und dem Schifffahrtsamt des Kantons Luzern. Vor Antritt der praktischen Prüfung muss die Basistheorieprüfung erfolgreich bestanden sein.
                                    </p>
                                </div>

                                <div class="p-3.5 rounded-3 bg-white border">
                                    <h6 class="fw-bold text-dark mb-1">2. Nulltoleranz für Alkohol (Art. 78a BSV)</h6>
                                    <p class="text-muted mb-0">
                                        Auf Schweizer Gewässern gilt für Fahrschüler, Fahrlehrer und Schiffsführer von Charterbooten eine strikte Grenze von <strong>0.00 Promille</strong>. Bei Zuwiderhandlung wird der Ausbildungsbetrieb unverzüglich eingestellt.
                                    </p>
                                </div>

                                <div class="p-3.5 rounded-3 bg-white border">
                                    <h6 class="fw-bold text-dark mb-1">3. Versicherungsschutz &amp; Selbstbehalt</h6>
                                    <p class="text-muted mb-0">
                                        Fahrschulboote und Mietboote (Boesch 620 Bimini) sind haftpflicht- und vollkaskoversichert. Bei grobfahrlässiger Beschädigung oder Grundberührung haftet der Schiffsführer im Rahmen des vereinbarten Selbstbehalts von CHF 1'000.-.
                                    </p>
                                </div>

                                <div class="p-3.5 rounded-3 bg-white border">
                                    <h6 class="fw-bold text-dark mb-1">4. Unternehmensangaben &amp; Mehrwertsteuer</h6>
                                    <p class="text-muted mb-0">
                                        <strong>Coaching Boot GmbH</strong> · Alpenquai, 6005 Luzern<br>
                                        Schweizer MWST-Nummer: <strong class="text-dark">CHE-110.497.163 MWST</strong><br>
                                        (Fahrschulunterricht von der MWST befreit gemäss Art. 21 Abs. 2 Ziff. 11 MWSTG · Bootsmiete unterliegt 8.1% MWST).
                                    </p>
                                </div>

                            </div>
                        </div>

                        <div class="modal-footer px-4 py-3" style="background-color: #eee4d5; border-top: 1px solid #dfd0bc;">
                            <button type="button" class="btn btn-dark rounded-3 btn-sm px-4 fw-bold" data-bs-dismiss="modal">Schließen</button>
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

    // Initialisiere Treffpunkt-Adresse & Google Maps Link
    const locSel = document.getElementById('portalLocationSelect');
    if (locSel) {
        updateLocationMapLink(locSel);
    }
});

// Aktualisiert genaue Treffpunkt-Adresse & Google Maps Verknüpfung im Buchungsmodal
function updateLocationMapLink(selectElem) {
    if (!selectElem) return;
    const opt = selectElem.options[selectElem.selectedIndex];
    if (!opt) return;

    const address = opt.getAttribute('data-address') || '';
    const name = opt.getAttribute('data-name') || '';
    const notes = opt.getAttribute('data-notes') || '';
    const query = address || name || 'Luzern Alpenquai';

    const textElem = document.getElementById('portalLocationAddressText');
    const linkElem = document.getElementById('portalLocationMapsLink');
    const headerLinkElem = document.getElementById('portalLocationHeaderMapsLink');
    const mapsUrl = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(query);

    if (textElem) {
        textElem.textContent = address ? address : name;
        if (notes) {
            textElem.title = notes;
        }
    }
    if (linkElem) {
        linkElem.href = mapsUrl;
    }
    if (headerLinkElem) {
        headerLinkElem.href = mapsUrl;
    }
}

// Umschalten der Wetterstation im Seewetter-Modal (Luzern vs. Hergiswil)
function switchLakeStation(station) {
    const btnLu = document.getElementById('btnStationLuzern');
    const btnHw = document.getElementById('btnStationHergiswil');
    const airTemp = document.getElementById('weatherAirTemp');
    const waterTemp = document.getElementById('weatherWaterTemp');
    const windSpeed = document.getElementById('weatherWindSpeed');
    const windKnots = document.getElementById('weatherWindKnots');
    const linkSearch = document.getElementById('linkSearchCh');
    const titleSearch = document.getElementById('titleSearchCh');

    if (station === 'hergiswil') {
        if (btnLu) {
            btnLu.classList.remove('bg-white', 'text-dark', 'shadow-xs');
            btnLu.classList.add('text-secondary');
        }
        if (btnHw) {
            btnHw.classList.add('bg-white', 'text-dark', 'shadow-xs');
            btnHw.classList.remove('text-secondary');
        }
        if (airTemp) airTemp.textContent = '7°C';
        if (waterTemp) waterTemp.textContent = '11.2°C';
        if (windSpeed) windSpeed.innerHTML = '8.9 <span class="fs-6 fw-normal">km/h</span>';
        if (windKnots) windKnots.textContent = '4.8 kn · 2 Bft (NW)';
        if (linkSearch) linkSearch.href = 'https://search.ch/meteo/hergiswil';
        if (titleSearch) titleSearch.textContent = 'search.ch Meteo – Lokalprognose Hergiswil (NW)';
    } else {
        if (btnHw) {
            btnHw.classList.remove('bg-white', 'text-dark', 'shadow-xs');
            btnHw.classList.add('text-secondary');
        }
        if (btnLu) {
            btnLu.classList.add('bg-white', 'text-dark', 'shadow-xs');
            btnLu.classList.remove('text-secondary');
        }
        if (airTemp) airTemp.textContent = '7°C';
        if (waterTemp) waterTemp.textContent = '11.4°C';
        if (windSpeed) windSpeed.innerHTML = '7.7 <span class="fs-6 fw-normal">km/h</span>';
        if (windKnots) windKnots.textContent = '4.2 kn · 2 Bft (NNO)';
        if (linkSearch) linkSearch.href = 'https://search.ch/meteo/luzern';
    }
}

// Store & Angebote: Filterung nach Kategorien
function filterStoreOffers(category, btnElem) {
    const buttons = document.querySelectorAll('.store-filter-btn');
    buttons.forEach(function(b) {
        b.classList.remove('bg-dark', 'text-white');
        b.classList.add('bg-white', 'text-secondary');
    });
    if (btnElem) {
        btnElem.classList.add('bg-dark', 'text-white');
        btnElem.classList.remove('bg-white', 'text-secondary');
    }

    const cards = document.querySelectorAll('.store-offer-card');
    cards.forEach(function(card) {
        const cardCat = card.getAttribute('data-category') || '';
        if (category === 'all' || cardCat.indexOf(category) !== -1) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// Aus Store ein Angebot wählen und im Buchungsmodal vorauswählen
function selectStoreOfferAndBook(serviceNameKeyword) {
    const storeModalEl = document.getElementById('storeOffersModal');
    if (storeModalEl && typeof bootstrap !== 'undefined') {
        const storeModal = bootstrap.Modal.getInstance(storeModalEl) || new bootstrap.Modal(storeModalEl);
        storeModal.hide();
    }

    const sel = document.querySelector('select[name="service_id"]');
    if (sel) {
        let found = false;
        const kw = serviceNameKeyword.toLowerCase();
        for (let i = 0; i < sel.options.length; i++) {
            const txt = sel.options[i].text.toLowerCase();
            if (txt.indexOf(kw) !== -1) {
                sel.selectedIndex = i;
                found = true;
                break;
            }
        }
        if (!found && sel.options.length > 0) {
            sel.selectedIndex = 0;
        }
    }

    setTimeout(function() {
        const bookModalEl = document.getElementById('requestAppointmentModal');
        if (bookModalEl && typeof bootstrap !== 'undefined') {
            const bookModal = new bootstrap.Modal(bookModalEl);
            bookModal.show();
        }
    }, 250);
}

// ==========================================
// SELF CHECK-IN & SCHIFFSÜBERGABE LOGIK
// ==========================================
function switchCheckinMode(mode) {
    const btnTake = document.getElementById('btnCheckinTakeover');
    const btnRet = document.getElementById('btnCheckinReturn');
    const btnEmerg = document.getElementById('btnCheckinEmergency');
    const pnlTake = document.getElementById('checkinPanelTakeover');
    const pnlRet = document.getElementById('checkinPanelReturn');
    const pnlEmerg = document.getElementById('checkinPanelEmergency');

    [btnTake, btnRet, btnEmerg].forEach(function(b) {
        if (b) {
            b.classList.remove('bg-dark', 'text-white');
            b.classList.add('text-secondary');
        }
    });

    if (pnlTake) pnlTake.style.display = 'none';
    if (pnlRet) pnlRet.style.display = 'none';
    if (pnlEmerg) pnlEmerg.style.display = 'none';

    if (mode === 'return') {
        if (btnRet) {
            btnRet.classList.add('bg-dark', 'text-white');
            btnRet.classList.remove('text-secondary');
        }
        if (pnlRet) pnlRet.style.display = 'block';
    } else if (mode === 'emergency') {
        if (btnEmerg) {
            btnEmerg.classList.add('bg-dark', 'text-white');
            btnEmerg.classList.remove('text-secondary');
        }
        if (pnlEmerg) pnlEmerg.style.display = 'block';
    } else {
        if (btnTake) {
            btnTake.classList.add('bg-dark', 'text-white');
            btnTake.classList.remove('text-secondary');
        }
        if (pnlTake) pnlTake.style.display = 'block';
    }
}

function selectFuelTakeover(level, btn) {
    document.querySelectorAll('.fuel-select-btn').forEach(function(b) {
        b.classList.remove('bg-dark', 'text-white');
        b.classList.add('btn-outline-secondary');
    });
    if (btn) {
        btn.classList.add('bg-dark', 'text-white');
        btn.classList.remove('btn-outline-secondary');
    }
    const inp = document.getElementById('selectedTakeoverFuel');
    if (inp) inp.value = level;
}

function selectFuelReturn(level, btn) {
    document.querySelectorAll('.fuel-return-btn').forEach(function(b) {
        b.classList.remove('bg-dark', 'text-white');
        b.classList.add('btn-outline-secondary');
    });
    if (btn) {
        btn.classList.add('bg-dark', 'text-white');
        btn.classList.remove('btn-outline-secondary');
    }
}

function toggleDamageNote(radio) {
    const box = document.getElementById('damageNoteBox');
    if (box) {
        box.style.display = radio.value === 'damage' ? 'block' : 'none';
    }
}

function submitTakeoverCheckin() {
    const items = document.querySelectorAll('.checkin-req-item, .checkin-legal-req');
    let allChecked = true;
    items.forEach(function(item) {
        if (!item.checked) allChecked = false;
    });

    if (!allChecked) {
        alert('Bitte bestätige alle gesetzlichen Sicherheits- und Schiffsführer-Punkte (Rettungswesten, Ausweis, 0.0‰ Nulltoleranz), bevor das Schiff freigegeben werden kann.');
        return;
    }

    const code = 'CB-CHK-' + Math.floor(10000 + Math.random() * 90000);
    const codeDisplay = document.getElementById('checkinCodeDisplay');
    if (codeDisplay) codeDisplay.textContent = code;

    const fuel = document.getElementById('selectedTakeoverFuel')?.value || '1/1 Voll';
    const fuelDisplay = document.getElementById('checkinStartFuelDisplay');
    if (fuelDisplay) fuelDisplay.textContent = fuel;

    const successBox = document.getElementById('checkinSuccessBox');
    if (successBox) successBox.style.display = 'block';

    const formContainer = document.getElementById('checkinFormContainer');
    if (formContainer) {
        formContainer.scrollIntoView({ behavior: 'smooth' });
    }
}

function submitReturnCheckin() {
    const items = document.querySelectorAll('.checkout-req');
    let allChecked = true;
    items.forEach(function(item) {
        if (!item.checked) allChecked = false;
    });

    if (!allChecked) {
        alert('Bitte bestätige alle Rückgabepunkte (Festmacher, Fender, Bimini-Verdeck, Hauptschalter).');
        return;
    }

    const successBox = document.getElementById('checkoutSuccessBox');
    if (successBox) successBox.style.display = 'block';

    const form = document.getElementById('checkoutFormContainer');
    if (form) {
        form.style.display = 'none';
    }
}

// ==========================================
// E-LEARNING & VIDEOPORTAL LOGIK
// ==========================================
const ELEARNING_VIDEO_DATA = {
    1: {
        title: 'Hafenbox rückwärts anlegen (Pfahlbox)',
        category: 'Hafenmanöver',
        duration: '12:40 Min · 4K Praxisaufnahme Vierwaldstättersee',
        headline: 'Hafenbox rückwärts anlegen mit Luv-Pfahl',
        desc: 'Präzises Einsteuern zwischen die Dalben bei Seitenwind. Nutzung des Radeffekts beim Aufstoppen und dosierter Gaseinsatz für kontrollierten hydrodynamischen Ruderdruck.',
        difficulty: 'Niveau: Fortgeschritten',
        bsv: 'Art. 44 BSV',
        chapters: [
            { title: '00:00 – Anfahrt & Windanalyse', time: '00:00' },
            { title: '03:45 – Eindrehen & Luv-Pfahl zuerst', time: '03:45' },
            { title: '08:30 – Radeffekt beim Aufstoppen', time: '08:30' },
            { title: '11:45 – Festmachen & Heckleine belegen', time: '11:45' }
        ]
    },
    2: {
        title: 'Boje über Bord (Mann über Bord Manöver)',
        category: 'Sicherheit & Notfall',
        duration: '09:45 Min · 4K Praxisaufnahme Vierwaldstättersee',
        headline: 'Lebensrettende Sofortmassnahmen bei Person über Bord',
        desc: 'Sofortiges Auskuppeln des Antriebs, Wegdrehen des Heckes zur Personabwendung, ständiger Sichtkontakt und standardisierte Anfahrt gegen den Wind mit Lee-Bergung.',
        difficulty: 'Niveau: Pflichtmanöver',
        bsv: 'Art. 77 BSV',
        chapters: [
            { title: '00:00 – Ausruf & Heck wegdrehen', time: '00:00' },
            { title: '02:15 – Rettungsring werfen & Ausguck', time: '02:15' },
            { title: '05:30 – Halbwind-Kurs & Wende gegen Wind', time: '05:30' },
            { title: '08:10 – Aufstoppen auf Lee-Seite der Boje', time: '08:10' }
        ]
    },
    3: {
        title: 'Längsseits anlegen bei Seitenwind',
        category: 'Anlegen',
        duration: '08:15 Min · 4K Praxisaufnahme Vierwaldstättersee',
        headline: 'Längsseits an Steg mit Vorspring einwehen lassen',
        desc: 'Kontrolliertes Anlegen bei ablandigem oder auflandigem Wind. Vorspring auf Stegpoller belegen, Ruder zur Pier legen und mit Vorwärtsschub das Heck sanft an die Kante ziehen.',
        difficulty: 'Niveau: Praxisfahrstunde',
        bsv: 'Art. 44 BSV',
        chapters: [
            { title: '00:00 – Windrichtung & Fenderhöhe', time: '00:00' },
            { title: '02:40 – 45° Anfahrt an den Steg', time: '02:40' },
            { title: '05:10 – Vorspring belegen & eindampfen', time: '05:10' },
            { title: '07:30 – Heckleine festmachen & Stillstand', time: '07:30' }
        ]
    },
    4: {
        title: 'Radeffekt & Drehen auf engstem Raum',
        category: 'Schiffsführung',
        duration: '06:30 Min · 4K Praxisaufnahme Vierwaldstättersee',
        headline: 'Ausnutzung des rechtsdrehenden Propellers',
        desc: 'Physikalische Wirkung des Radeffekts beim Rückwärtsgang. Gezielte kurze Vorwärts- und Rückwärts-Gasstösse zum Drehen des Bootes auf dem Teller ohne Fahrtrille.',
        difficulty: 'Niveau: Einsteiger bis Profi',
        bsv: 'Art. 16 BSV',
        chapters: [
            { title: '00:00 – Physik des Radeffekts', time: '00:00' },
            { title: '02:00 – Schubimpuls statt Dauergas', time: '02:00' },
            { title: '04:15 – 360° Drehung auf der Stelle', time: '04:15' }
        ]
    },
    5: {
        title: 'Nautische Knoten für die Schiffsprüfung',
        category: 'Seemannschaft',
        duration: '07:10 Min · 4K HD Detailaufnahme',
        headline: 'Palstek, Webleinstek, Belegen auf Klampe & Schotstek',
        desc: 'Schritt-für-Schritt Anleitung der vier prüfungsentscheidenden Knoten. Erklärung der Bruchlast, Anwendung und schnelles Lösen unter Zuglast.',
        difficulty: 'Niveau: Basisanforderung',
        bsv: 'Prüfungsprotokoll asa',
        chapters: [
            { title: '00:00 – Der Palstek (Rettungsschlinge)', time: '00:00' },
            { title: '02:20 – Webleinstek auf Pfahl & Reling', time: '02:20' },
            { title: '04:40 – Belegen einer Klampe mit Kopfschlag', time: '04:40' },
            { title: '06:00 – Kreuzknoten & Schotstek', time: '06:00' }
        ]
    }
};

function switchElearningTab(tabId, btnElem) {
    const buttons = document.querySelectorAll('.elearning-tab-btn');
    buttons.forEach(function(b) {
        b.classList.remove('bg-dark', 'text-white');
        b.classList.add('text-secondary');
    });
    if (btnElem) {
        btnElem.classList.add('bg-dark', 'text-white');
        btnElem.classList.remove('text-secondary');
    }

    const sections = {
        videos: document.getElementById('elearningSectionVideos'),
        charts: document.getElementById('elearningSectionCharts'),
        quiz: document.getElementById('elearningSectionQuiz'),
        downloads: document.getElementById('elearningSectionDownloads')
    };

    Object.keys(sections).forEach(function(key) {
        if (sections[key]) {
            sections[key].style.display = (key === tabId) ? 'block' : 'none';
        }
    });
}

function selectVideo(videoId) {
    const data = ELEARNING_VIDEO_DATA[videoId];
    if (!data) return;

    const titleEl = document.getElementById('activeVideoTitleDisplay');
    const catEl = document.getElementById('activeVideoCategoryDisplay');
    const durEl = document.getElementById('activeVideoDurationDisplay');
    const headEl = document.getElementById('activeVideoHeadline');
    const descEl = document.getElementById('activeVideoDesc');
    const diffEl = document.getElementById('activeVideoDifficulty');
    const bsvEl = document.getElementById('activeVideoBsv');
    const chapEl = document.getElementById('activeVideoChapters');

    if (titleEl) titleEl.textContent = data.title;
    if (catEl) catEl.textContent = data.category;
    if (durEl) durEl.textContent = 'Dauer: ' + data.duration;
    if (headEl) headEl.textContent = data.headline;
    if (descEl) descEl.textContent = data.desc;
    if (diffEl) diffEl.textContent = data.difficulty;
    if (bsvEl) bsvEl.textContent = data.bsv;

    if (chapEl) {
        let html = '';
        data.chapters.forEach(function(ch) {
            html += '<div class="p-2 rounded bg-light border d-flex justify-content-between">' +
                    '<span>' + ch.title + '</span>' +
                    '<span class="badge bg-secondary">' + ch.time + '</span>' +
                    '</div>';
        });
        chapEl.innerHTML = html;
    }

    const modalBody = document.querySelector('#elearningModal .modal-body');
    if (modalBody) {
        modalBody.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

function answerQuiz(qId, selectedIdx, isCorrect, explanation) {
    const card = document.getElementById('quizCard' + qId);
    if (!card) return;

    const buttons = card.querySelectorAll('.quiz-opt');
    buttons.forEach(function(btn, idx) {
        btn.classList.remove('btn-success', 'btn-danger', 'text-white');
        btn.classList.add('btn-outline-secondary');
        if (idx === selectedIdx) {
            if (isCorrect) {
                btn.classList.remove('btn-outline-secondary');
                btn.classList.add('btn-success', 'text-white');
            } else {
                btn.classList.remove('btn-outline-secondary');
                btn.classList.add('btn-danger', 'text-white');
            }
        }
    });

    const feed = document.getElementById('quizFeedback' + qId);
    if (feed) {
        feed.style.display = 'block';
        if (isCorrect) {
            feed.className = 'quiz-feedback small mt-2 p-2.5 rounded-2 bg-success-subtle text-success border border-success fw-medium';
            feed.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> ' + explanation;
        } else {
            feed.className = 'quiz-feedback small mt-2 p-2.5 rounded-2 bg-danger-subtle text-danger border border-danger fw-medium';
            feed.innerHTML = '<i class="bi bi-x-circle-fill me-1"></i> ' + explanation;
        }
    }
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../../../views/layouts/boating_portal.php';
