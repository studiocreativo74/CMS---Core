<?php

declare(strict_types=1);

/**
 * Portal: Buchungsbestätigung (/bootschule/book/success)
 *
 * @var array<string, mixed> $appointment
 * @var array<string, mixed> $customer
 * @var array<string, mixed> $service
 * @var array<string, mixed>|null $location
 */

$title = 'Buchungsanfrage erfolgreich übermittelt - Bootsfahrschule';
$currentRoute = 'bootschule/book';

ob_start();
?>

<div class="container mb-5">
    <div class="max-w-700 mx-auto text-center py-4">
        <!-- Erfolgs-Icon -->
        <div class="rounded-circle bg-success bg-opacity-10 text-success d-inline-flex align-items-center justify-content-center mb-4 shadow-sm" style="width: 80px; height: 80px;">
            <i class="bi bi-check2-circle fs-1"></i>
        </div>

        <h1 class="h2 fw-bold text-dark mb-2">Vielen Dank für Ihre Anfrage!</h1>
        <p class="text-muted lead mb-4">
            Ihre Buchung wurde erfolgreich im Schulungssystem registriert und befindet sich in Prüfung.
        </p>

        <!-- Buchungs-Details Card -->
        <div class="card border-0 shadow-sm rounded-4 text-start p-4 bg-white mb-4">
            <h5 class="fw-bold pb-2 mb-3 border-bottom d-flex align-items-center justify-content-between">
                <span><i class="bi bi-receipt text-primary me-2"></i>Ihre Buchungsübersicht</span>
                <span class="badge bg-warning text-dark fw-semibold">Status: Angefragt</span>
            </h5>

            <div class="row g-3">
                <div class="col-sm-6">
                    <div class="small text-muted">Ausbildungsmodul / Service:</div>
                    <div class="fw-semibold text-dark">
                        <?= htmlspecialchars((string) ($service['name'] ?? 'Fahrstunde'), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>

                <div class="col-sm-6">
                    <div class="small text-muted">Datum &amp; Uhrzeit:</div>
                    <div class="fw-semibold text-dark">
                        <?= date('d.m.Y', strtotime((string) $appointment['date'])) ?> &bull; 
                        <?= htmlspecialchars((string) substr((string) $appointment['start_time'], 0, 5)) ?> - 
                        <?= htmlspecialchars((string) substr((string) $appointment['end_time'], 0, 5)) ?> Uhr
                    </div>
                </div>

                <div class="col-sm-6">
                    <div class="small text-muted">Schüler / Kunde:</div>
                    <div class="fw-semibold text-dark">
                        <?= htmlspecialchars((string) ($customer['first_name'] . ' ' . $customer['last_name']), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div class="small text-muted"><?= htmlspecialchars((string) ($customer['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                </div>

                <div class="col-sm-6">
                    <div class="small text-muted">Tarif:</div>
                    <div class="h5 fw-bold text-primary mb-0">
                        CHF <?= number_format((float) ($appointment['price'] ?? 0), 2, '.', "'") ?>
                    </div>
                </div>

                <?php if (!empty($location)): ?>
                    <div class="col-12 pt-2 border-top">
                        <div class="small text-muted">Treffpunkt / Hafen:</div>
                        <div class="fw-semibold text-dark">
                            <i class="bi bi-geo-alt-fill text-danger me-1"></i>
                            <?= htmlspecialchars((string) $location['name'], ENT_QUOTES, 'UTF-8') ?>
                            <?php if (!empty($location['address'])): ?>
                                (<?= htmlspecialchars((string) $location['address'], ENT_QUOTES, 'UTF-8') ?>)
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- TODO: Mail-Benachrichtigungen einbinden -->
            <?php
            /*
             * TODO (Phase 4):
             * 1. Automatische Bestätigungs-Mail an den Kunden ($customer['email']) senden:
             *    Mailer::send($customer['email'], 'Buchungsanfrage erhalten', $bodyHtml);
             * 2. Benachrichtigungs-Mail an Fahrschulleitung / Dispo:
             *    Mailer::send(Settings::get('school_email'), 'Neue Buchungsanfrage: ' . $service['name'], ...);
             */
            ?>
        </div>

        <!-- Weiterführende Aktionen -->
        <div class="d-flex flex-wrap justify-content-center gap-3">
            <a href="?route=bootschule/me/appointments&email=<?= urlencode((string) $customer['email']) ?>" class="btn btn-primary rounded-pill px-4">
                <i class="bi bi-calendar-check me-2"></i> Meine Termine ansehen
            </a>
            <a href="?route=bootschule/book" class="btn btn-outline-secondary rounded-pill px-4">
                <i class="bi bi-plus-lg me-1"></i> Weiteren Termin anfragen
            </a>
            <a href="?route=bootschule" class="btn btn-link text-muted text-decoration-none">
                Zurück zur Startseite
            </a>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../../../views/layouts/boating_portal.php';
