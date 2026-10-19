<?php

declare(strict_types=1);

/**
 * Portal: Meine Termine (Schüler-Magic-Link & Terminübersicht)
 * Routen: /bootschule/me/appointments & /bootschule/me/link?token=...
 *
 * @var string|null $token
 * @var bool $tokenInvalid
 * @var bool $noSession
 * @var string|null $email
 * @var array<string, mixed>|null $customer
 * @var array<int, array<string, mixed>> $upcomingAppointments
 * @var array<int, array<string, mixed>> $pastAppointments
 * @var array<int, array<string, mixed>> $subscriptions
 * @var string $csrfToken
 */

$title = 'Meine Termine - Bootsfahrschule';
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
    'requested'          => 'Angefragt',
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
$flashError = $_SESSION['flash_portal_error'] ?? null;
unset($_SESSION['flash_portal_success'], $_SESSION['flash_portal_error']);

ob_start();
?>

<div class="container py-4 mb-5">
    <div class="max-w-900 mx-auto" style="max-width: 960px;">

        <!-- Flash Meldungen -->
        <?php if ($flashSuccess): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm rounded-4 mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-check-circle-fill text-success fs-5 me-2"></i>
                    <div><?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>
            </div>
        <?php endif; ?>

        <?php if ($flashError): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm rounded-4 mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill text-danger fs-5 me-2"></i>
                    <div><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>
            </div>
        <?php endif; ?>

        <!-- Kopfzeile -->
        <div class="text-center mb-4">
            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-semibold mb-2">
                <i class="bi bi-shield-check me-1"></i> Schüler Self-Service
            </span>
            <h1 class="h2 fw-bold text-dark mb-2">Meine Termine &amp; Buchungen</h1>
            <p class="text-muted">
                Überblick über deine bevorstehenden Fahrstunden, absolvierten Einheiten und Abonnemente.
            </p>
        </div>

        <?php if (!empty($tokenInvalid)): ?>
            <!-- Zustand 1: Fehler bei ungültigem oder abgelaufenem Magic Link -->
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white text-center mb-4">
                <div class="mb-3">
                    <span class="p-3 bg-danger bg-opacity-10 text-danger rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                        <i class="bi bi-link-45deg fs-2"></i>
                    </span>
                </div>
                <h4 class="fw-bold text-dark mb-2">Link ist ungültig oder abgelaufen</h4>
                <p class="text-muted mb-4 max-w-600 mx-auto" style="max-width: 540px;">
                    Aus Sicherheitsgründen sind unsere Zugangslinks zeitlich begrenzt (7 Tage gültig). Du kannst dir jederzeit kostenlos und sekundenschnell einen neuen Zugangslink per E-Mail zusenden lassen.
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
            <!-- Zustand 2: Authentifizierter Schüler-Bereich via Magic Link -->
            
            <!-- Schüler-Profilkarte mit Begrüssung "Hallo {{customer_first_name}}" und "Abmelden"-Button -->
            <div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 bg-white mb-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold fs-5 shadow-sm" style="width: 52px; height: 52px;">
                            <?= htmlspecialchars(mb_substr((string) ($customer['first_name'] ?? 'S'), 0, 1) . mb_substr((string) ($customer['last_name'] ?? 'C'), 0, 1)) ?>
                        </div>
                        <div>
                            <!-- Vorgabe: Begrüssung „Hallo {{customer_first_name}}“ -->
                            <h2 class="h4 fw-bold text-dark mb-1">
                                Hallo <?= htmlspecialchars((string) ($customer['first_name'] ?? 'Schüler'), ENT_QUOTES, 'UTF-8') ?>!
                                <?php if (!empty($customer['customer_number'])): ?>
                                    <span class="badge bg-secondary-subtle text-muted ms-2 fw-normal small">Nr. <?= htmlspecialchars((string) $customer['customer_number']) ?></span>
                                <?php endif; ?>
                            </h2>
                            <div class="small text-muted d-flex flex-wrap gap-3">
                                <span><i class="bi bi-envelope me-1"></i><?= htmlspecialchars((string) $customer['email']) ?></span>
                                <?php if (!empty($customer['phone'])): ?>
                                    <span><i class="bi bi-telephone me-1"></i><?= htmlspecialchars((string) $customer['phone']) ?></span>
                                <?php endif; ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check2-circle me-1"></i>Angemeldet per Magic-Link</span>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a href="?route=bootschule/book" class="btn btn-primary rounded-pill shadow-sm fw-semibold btn-sm px-3">
                            <i class="bi bi-plus-circle me-1"></i> Stunde buchen
                        </a>
                        <!-- Vorgabe: Button „Abmelden“ (Session beenden) -->
                        <a href="?route=bootschule/me/logout" class="btn btn-outline-secondary rounded-pill btn-sm fw-semibold px-3" title="Sitzung sicher beenden">
                            <i class="bi bi-box-arrow-right me-1"></i> Abmelden
                        </a>
                    </div>
                </div>

                <!-- Abos / Mehrfachkarten Hinweis (falls aktiv) -->
                <?php if (!empty($subscriptions)): ?>
                    <div class="mt-3 pt-3 border-top">
                        <div class="small fw-semibold text-muted text-uppercase mb-2">Aktive Abonnemente &amp; Mehrfachkarten:</div>
                        <div class="row g-2">
                            <?php foreach ($subscriptions as $sub): ?>
                                <div class="col-md-6">
                                    <div class="p-2 px-3 rounded-3 border bg-light d-flex align-items-center justify-content-between">
                                        <div>
                                            <span class="fw-semibold text-dark small"><?= htmlspecialchars((string) ($sub['name'] ?? 'Abonnement')) ?></span>
                                            <span class="badge bg-secondary-subtle text-muted ms-1"><?= htmlspecialchars((string) ($sub['code'] ?? '')) ?></span>
                                        </div>
                                        <div class="small fw-bold text-primary">
                                            <?= (int) ($sub['units_remaining'] ?? 0) ?> von <?= (int) ($sub['units_total'] ?? 0) ?> Einheiten übrig
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tab Navigation: Bevorstehend vs. Vergangen -->
            <ul class="nav nav-pills nav-fill bg-white p-1 rounded-4 shadow-sm mb-4 border" id="appointmentsTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active rounded-pill fw-semibold py-2" id="upcoming-tab" data-bs-toggle="tab" data-bs-target="#upcoming" type="button" role="tab">
                        <i class="bi bi-calendar-check me-2"></i>Bevorstehende Termine
                        <span class="badge bg-primary text-white ms-1 rounded-pill"><?= count($upcomingAppointments) ?></span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill fw-semibold py-2" id="past-tab" data-bs-toggle="tab" data-bs-target="#past" type="button" role="tab">
                        <i class="bi bi-clock-history me-2"></i>Vergangene Fahrten (12 Monate)
                        <span class="badge bg-secondary text-white ms-1 rounded-pill"><?= count($pastAppointments) ?></span>
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="appointmentsTabContent">
                <!-- 1. Bevorstehende Termine (Datum ab heute, nach Startzeit sortiert) -->
                <div class="tab-pane fade show active" id="upcoming" role="tabpanel">
                    <?php if (empty($upcomingAppointments)): ?>
                        <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
                            <i class="bi bi-calendar-x text-muted fs-1 mb-3"></i>
                            <h5 class="fw-bold mb-2">Keine anstehenden Termine</h5>
                            <p class="text-muted mb-4">
                                Du hast aktuell keine anstehenden Fahrstunden reserviert. Buche dir jetzt deinen nächsten Wunschtermin!
                            </p>
                            <div>
                                <a href="?route=bootschule/book" class="btn btn-primary rounded-pill px-4 fw-semibold">
                                    <i class="bi bi-calendar-plus me-1"></i> Jetzt Fahrstunde buchen
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Responsives Bootstrap-Layout: Mobil als Karten -->
                        <div class="d-flex flex-column gap-3">
                            <?php foreach ($upcomingAppointments as $app): ?>
                                <?php
                                $badgeClass = $statusBadges[$app['status']] ?? 'secondary';
                                $statusText = $statusLabels[$app['status']] ?? $app['status'];

                                // Stornierbar nur bei requested/confirmed und Frist eingehalten
                                $isFuture = ($app['date'] > date('Y-m-d')) || ($app['date'] === date('Y-m-d') && $app['start_time'] > date('H:i:s'));
                                $appStartTimestamp = strtotime((string) $app['date'] . ' ' . (string) $app['start_time']);
                                $hoursUntil = ($appStartTimestamp - time()) / 3600;
                                $isWithinCancelDeadline = $hoursUntil >= $cancelMinHours;
                                $isCancellable = in_array($app['status'], ['requested', 'confirmed'], true) && $isFuture && $isWithinCancelDeadline;
                                ?>
                                <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                                    <div class="card-body p-4">
                                        <div class="row g-3 align-items-center">
                                            <!-- Datum & Uhrzeit -->
                                            <div class="col-12 col-md-3">
                                                <div class="h5 fw-bold text-primary mb-1">
                                                    <i class="bi bi-calendar-event me-1"></i>
                                                    <?= date('d.m.Y', strtotime((string) $app['date'])) ?>
                                                </div>
                                                <div class="text-dark fw-semibold small">
                                                    <i class="bi bi-clock me-1 text-muted"></i>
                                                    <?= htmlspecialchars(substr((string) $app['start_time'], 0, 5)) ?> – <?= htmlspecialchars(substr((string) $app['end_time'], 0, 5)) ?> Uhr
                                                </div>
                                                <div class="mt-2">
                                                    <span class="badge bg-<?= $badgeClass ?>-subtle text-<?= $badgeClass ?> border border-<?= $badgeClass ?>-subtle px-2 py-1 rounded-pill small fw-semibold">
                                                        <?= htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8') ?>
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Dienstleistung, Fahrlehrer, Boot & Standort -->
                                            <div class="col-12 col-md-5">
                                                <h6 class="fw-bold text-dark mb-2">
                                                    <?= htmlspecialchars((string) ($app['service_name'] ?? 'Bootsausbildung'), ENT_QUOTES, 'UTF-8') ?>
                                                </h6>
                                                <div class="small text-muted d-flex flex-column gap-1">
                                                    <!-- Fahrlehrer (Vorname und Nachname) -->
                                                    <div>
                                                        <i class="bi bi-person-badge text-primary me-1"></i>
                                                        <strong>Fahrlehrer:</strong>
                                                        <?= !empty($app['instructor_first_name']) ? htmlspecialchars((string) ($app['instructor_first_name'] . ' ' . ($app['instructor_last_name'] ?? ''))) : '<span class="text-muted">Wird zugewiesen</span>' ?>
                                                    </div>

                                                    <!-- Boot -->
                                                    <div>
                                                        <i class="bi bi-life-preserver text-info me-1"></i>
                                                        <strong>Boot:</strong>
                                                        <?= !empty($app['boat_name']) ? htmlspecialchars((string) $app['boat_name']) : '<span class="text-muted">Schulungsboot</span>' ?>
                                                    </div>

                                                    <!-- Standort mit Adresse -->
                                                    <div>
                                                        <i class="bi bi-geo-alt-fill text-danger me-1"></i>
                                                        <strong>Standort:</strong>
                                                        <?= htmlspecialchars((string) ($app['location_name'] ?? 'Hafen / Steg')) ?>
                                                        <?php if (!empty($app['location_address'])): ?>
                                                            <div class="text-muted ms-3 small"><?= htmlspecialchars((string) $app['location_address']) ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Preis & Stornierungs-Möglichkeit -->
                                            <div class="col-12 col-md-4 text-md-end">
                                                <div class="mb-2">
                                                    <span class="text-muted small">Preis:</span>
                                                    <div class="h5 fw-bold text-dark mb-0">CHF <?= number_format((float) ($app['price'] ?? 0), 2, '.', "'") ?></div>
                                                </div>

                                                <?php if ($isCancellable): ?>
                                                    <!-- Vorgabe d: POST /bootschule/me/appointments/{id}/cancel (CSRF-geschützt) -->
                                                    <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#cancelModal<?= (int) $app['id'] ?>">
                                                        <i class="bi bi-x-circle me-1"></i> Termin absagen
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
                                                                            <textarea name="cancel_reason" rows="3" class="form-control" placeholder="z. B. Kurzfristige Verhinderung, Terminkonflikt..."></textarea>
                                                                        </div>

                                                                        <div class="alert alert-light border small text-muted mb-0">
                                                                            <i class="bi bi-info-circle me-1"></i> Storno-Frist: Kostenfreie Stornierung ist bis zu <strong><?= $cancelMinHours ?> Stunden</strong> vor Beginn möglich. Eine Bestätigung wird per E-Mail versendet.
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
                                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill small" title="Storno nur bis <?= $cancelMinHours ?> Stunden vor Termin möglich">
                                                        <i class="bi bi-clock-history me-1"></i>Stornofrist abgelaufen (&lt; <?= $cancelMinHours ?>h)
                                                    </span>
                                                <?php elseif ($app['status'] === 'cancelled_customer'): ?>
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill small">Von dir storniert</span>
                                                <?php elseif ($app['status'] === 'cancelled_system'): ?>
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill small">Durch Schule storniert</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- 2. Vergangene Termine (die letzten 12 Monate, absteigend sortiert) -->
                <div class="tab-pane fade" id="past" role="tabpanel">
                    <?php if (empty($pastAppointments)): ?>
                        <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
                            <i class="bi bi-clock-history text-muted fs-1 mb-3"></i>
                            <h5 class="fw-bold mb-2">Keine vergangenen Termine</h5>
                            <p class="text-muted mb-0">
                                In den letzten 12 Monaten wurden keine absolvierten oder stornierten Fahrstunden erfasst.
                            </p>
                        </div>
                    <?php else: ?>
                        <!-- Desktop: Tabelle -->
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white d-none d-md-block">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light small text-muted text-uppercase">
                                        <tr>
                                            <th class="ps-4">Datum &amp; Zeit</th>
                                            <th>Dienstleistung</th>
                                            <th>Fahrlehrer</th>
                                            <th>Boot &amp; Standort</th>
                                            <th>Status</th>
                                            <th class="text-end pe-4">Preis</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pastAppointments as $app): ?>
                                            <?php
                                            $badgeClass = $statusBadges[$app['status']] ?? 'secondary';
                                            $statusText = $statusLabels[$app['status']] ?? $app['status'];
                                            ?>
                                            <tr>
                                                <td class="ps-4 py-3">
                                                    <div class="fw-bold text-dark"><?= date('d.m.Y', strtotime((string) $app['date'])) ?></div>
                                                    <div class="small text-muted"><?= substr((string) $app['start_time'], 0, 5) ?> – <?= substr((string) $app['end_time'], 0, 5) ?> Uhr</div>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold text-dark"><?= htmlspecialchars((string) ($app['service_name'] ?? 'Fahrstunde')) ?></div>
                                                </td>
                                                <td>
                                                    <?= !empty($app['instructor_first_name']) ? htmlspecialchars((string) ($app['instructor_first_name'] . ' ' . ($app['instructor_last_name'] ?? ''))) : '<span class="text-muted">-</span>' ?>
                                                </td>
                                                <td>
                                                    <div class="small fw-semibold text-dark"><?= !empty($app['boat_name']) ? htmlspecialchars((string) $app['boat_name']) : 'Schulungsboot' ?></div>
                                                    <div class="small text-muted"><?= htmlspecialchars((string) ($app['location_name'] ?? '')) ?></div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?= $badgeClass ?>-subtle text-<?= $badgeClass ?> rounded-pill small">
                                                        <?= htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8') ?>
                                                    </span>
                                                </td>
                                                <td class="text-end pe-4 fw-semibold text-dark">
                                                    CHF <?= number_format((float) ($app['price'] ?? 0), 2, '.', "'") ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Mobil: Kartenansicht -->
                        <div class="d-md-none d-flex flex-column gap-3">
                            <?php foreach ($pastAppointments as $app): ?>
                                <?php
                                $badgeClass = $statusBadges[$app['status']] ?? 'secondary';
                                $statusText = $statusLabels[$app['status']] ?? $app['status'];
                                ?>
                                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="fw-bold text-primary">
                                            <?= date('d.m.Y', strtotime((string) $app['date'])) ?>
                                            <span class="text-muted fw-normal small">(<?= substr((string) $app['start_time'], 0, 5) ?> Uhr)</span>
                                        </div>
                                        <span class="badge bg-<?= $badgeClass ?>-subtle text-<?= $badgeClass ?> rounded-pill small">
                                            <?= htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </div>
                                    <div class="fw-semibold text-dark mb-1"><?= htmlspecialchars((string) ($app['service_name'] ?? 'Fahrstunde')) ?></div>
                                    <div class="small text-muted mb-2">
                                        <div><i class="bi bi-person me-1"></i><?= !empty($app['instructor_first_name']) ? htmlspecialchars((string) ($app['instructor_first_name'] . ' ' . ($app['instructor_last_name'] ?? ''))) : '-' ?></div>
                                        <div><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars((string) ($app['location_name'] ?? '')) ?></div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center border-top pt-2">
                                        <span class="small text-muted">Preis:</span>
                                        <span class="fw-bold text-dark">CHF <?= number_format((float) ($app['price'] ?? 0), 2, '.', "'") ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php else: ?>
            <!-- Zustand 3: Ohne boating_customer_id in der Session (Infoseite mit Hinweis auf Magic-Link) -->
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white text-center mb-4">
                <div class="mb-3">
                    <span class="p-3 bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                        <i class="bi bi-magic fs-2"></i>
                    </span>
                </div>
                <h3 class="fw-bold text-dark mb-2">Zugangslink anfordern</h3>
                <p class="text-muted mb-4 max-w-600 mx-auto" style="max-width: 520px;">
                    Der Zugang zu deinen gebuchten Fahrstunden erfolgt über einen sicheren, zeitlich begrenzten Magic-Link ohne Passwort.
                    Gib deine bei der Buchung hinterlegte E-Mail-Adresse ein, um einen neuen Zugangslink zu erhalten.
                </p>

                <div class="card border bg-light rounded-4 p-4 max-w-600 mx-auto text-start" style="max-width: 480px;">
                    <form method="post" action="?route=bootschule/me/request-link">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                        <label class="form-label small fw-semibold text-dark mb-1">Deine registrierte E-Mail-Adresse:</label>
                        <div class="input-group mb-3">
                            <span class="input-group-text bg-white"><i class="bi bi-envelope text-muted"></i></span>
                            <input type="email" name="email" class="form-control" placeholder="name@beispiel.ch" required value="<?= htmlspecialchars((string) ($email ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <button type="submit" class="btn btn-primary w-100 rounded-pill fw-semibold shadow-sm">
                            <i class="bi bi-send me-1"></i> Magischen Zugangslink senden
                        </button>
                    </form>
                    <div class="text-center text-muted small mt-3">
                        <i class="bi bi-lock me-1"></i> Aus Sicherheitsgründen beantworten wir Anfragen neutral („Falls die Adresse bekannt ist, haben wir einen neuen Link gesendet.“).
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../../../views/layouts/boating_portal.php';
