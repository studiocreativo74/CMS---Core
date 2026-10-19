<?php

declare(strict_types=1);

/**
 * Portal: Startseite der Bootsfahrschule (/bootschule)
 *
 * @var array<int, array<string, mixed>> $featuredServices
 * @var array<int, array<string, mixed>> $boats
 * @var array<int, array<string, mixed>> $instructors
 */

$title = 'Bootsfahrschule - Fahrstunden & Bootsmiete online buchen';
$currentRoute = 'bootschule';

ob_start();
?>

<!-- Hero-Sektion -->
<section class="py-5 mb-5 rounded-4 text-white position-relative overflow-hidden shadow-sm" style="background: linear-gradient(135deg, #072a45 0%, #0c4a6e 60%, #0369a1 100%);">
    <div class="container py-4">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <span class="badge bg-info bg-opacity-25 text-info px-3 py-2 rounded-pill fw-semibold mb-3 border border-info border-opacity-25">
                    <i class="bi bi-water me-1"></i> Offiziell anerkannte Bootsfahrschule &amp; Charter
                </span>
                <h1 class="display-5 fw-bold text-white mb-3">
                    Ihr direkter Kurs zum Schweizer Bootsführerschein.
                </h1>
                <p class="lead text-light opacity-90 mb-4">
                    Professionelle Praxisausbildung für Motorboot (Kat. A) und Segelschiff (Kat. D).
                    Wählen Sie Ihren Wunschtermin online und buchen Sie Ihre Fahrstunde in wenigen Klicks.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="?route=bootschule/book" class="btn btn-info btn-lg px-4 py-2 rounded-pill fw-bold shadow">
                        <i class="bi bi-calendar-check me-2"></i> Fahrstunde buchen
                    </a>
                    <a href="?route=bootschule/book&category=rental" class="btn btn-outline-light btn-lg px-4 py-2 rounded-pill fw-semibold">
                        <i class="bi bi-compass me-2"></i> Bootsmiete buchen
                    </a>
                    <a href="?route=bootschule/me/appointments" class="btn btn-link text-white-50 text-decoration-none px-3 py-2">
                        <i class="bi bi-person-check me-1"></i> Meine Termine ansehen &rarr;
                    </a>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card border-0 bg-white bg-opacity-10 backdrop-blur rounded-4 p-4 text-white border border-white border-opacity-10 shadow-lg">
                    <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-speedometer2 text-info"></i> Schnellstart Buchung
                    </h5>
                    <p class="small text-light opacity-80 mb-3">
                        Wählen Sie eine Kategorie und prüfen Sie direkt die nächsten freien Terminslots:
                    </p>
                    <div class="d-grid gap-2">
                        <a href="?route=bootschule/book&category=practical" class="btn btn-light text-start py-2 px-3 rounded-3 d-flex align-items-center justify-content-between">
                            <span><i class="bi bi-life-preserver text-primary me-2"></i> Motorboot Praxis (Kat. A)</span>
                            <i class="bi bi-chevron-right text-muted"></i>
                        </a>
                        <a href="?route=bootschule/book&category=practical" class="btn btn-light text-start py-2 px-3 rounded-3 d-flex align-items-center justify-content-between">
                            <span><i class="bi bi-wind text-primary me-2"></i> Segelausbildung (Kat. D)</span>
                            <i class="bi bi-chevron-right text-muted"></i>
                        </a>
                        <a href="?route=bootschule/book&category=exam" class="btn btn-light text-start py-2 px-3 rounded-3 d-flex align-items-center justify-content-between">
                            <span><i class="bi bi-award text-primary me-2"></i> Prüfungsvorbereitung &amp; Prüfungsfahrt</span>
                            <i class="bi bi-chevron-right text-muted"></i>
                        </a>
                        <a href="?route=bootschule/book&category=rental" class="btn btn-light text-start py-2 px-3 rounded-3 d-flex align-items-center justify-content-between">
                            <span><i class="bi bi-anchor text-primary me-2"></i> Bootsmiete &amp; Skipper-Ausfahrt</span>
                            <i class="bi bi-chevron-right text-muted"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Vorteile / Qualitätsmerkmale -->
<div class="container mb-5">
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 rounded-4 p-3 bg-white">
                <div class="card-body">
                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-inline-flex p-3 mb-3">
                        <i class="bi bi-shield-check fs-4"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Erfahrene Instruktoren</h5>
                    <p class="text-muted small mb-0">
                        Zertifizierte Fahrlehrer mit langjähriger Erfahrung auf Binnengewässern begleiten Sie sicher bis zum Patent.
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 rounded-4 p-3 bg-white">
                <div class="card-body">
                    <div class="rounded-circle bg-info bg-opacity-10 text-info d-inline-flex p-3 mb-3">
                        <i class="bi bi-calendar4-week fs-4"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Flexible Slot-Buchung</h5>
                    <p class="text-muted small mb-0">
                        Echtzeit-Verfügbarkeitskalender für Fahrstunden. Buchen oder stornieren Sie Termine unkompliziert online.
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 rounded-4 p-3 bg-white">
                <div class="card-body">
                    <div class="rounded-circle bg-success bg-opacity-10 text-success d-inline-flex p-3 mb-3">
                        <i class="bi bi-tsunami fs-4"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Moderne Schulungsflotte</h5>
                    <p class="text-muted small mb-0">
                        Top gewartete Fahrschulboote mit moderner Navigations- und Steuerungstechnik für optimalen Lernerfolg.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ausgewählte Leistungen -->
<div class="container mb-5">
    <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
        <div>
            <span class="text-primary fw-semibold small text-uppercase letter-spacing-1">Ausbildungsangebote</span>
            <h2 class="h3 fw-bold text-dark mb-0">Beliebte Fahrstunden &amp; Kurse</h2>
        </div>
        <a href="?route=bootschule/services" class="btn btn-outline-primary btn-sm rounded-pill px-3">
            Alle Leistungen anzeigen &rarr;
        </a>
    </div>

    <?php if (empty($featuredServices)): ?>
        <div class="alert alert-light border text-center py-4">
            Aktuell sind noch keine öffentlichen Leistungen hinterlegt.
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($featuredServices as $srv): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden d-flex flex-column bg-white">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold px-2 py-1 rounded">
                                    <?= htmlspecialchars((string) ($srv['code'] ?? 'KURS'), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <span class="text-muted small">
                                    <i class="bi bi-clock me-1"></i><?= (int) ($srv['duration_minutes'] ?? 60) ?> Min.
                                </span>
                            </div>

                            <h5 class="fw-bold text-dark mb-2">
                                <?= htmlspecialchars((string) $srv['name'], ENT_QUOTES, 'UTF-8') ?>
                            </h5>

                            <p class="text-muted small mb-4 flex-grow-1">
                                <?= nl2br(htmlspecialchars((string) ($srv['description'] ?: 'Praxisnahe Ausbildung mit individueller Betreuung.'), ENT_QUOTES, 'UTF-8')) ?>
                            </p>

                            <div class="pt-3 border-top d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="small text-muted">Tarif</div>
                                    <div class="h5 fw-bold text-primary mb-0">
                                        CHF <?= number_format((float) ($srv['price'] ?? 0), 2, '.', "'") ?>
                                    </div>
                                </div>
                                <a href="?route=bootschule/book&service_id=<?= (int) $srv['id'] ?>" class="btn btn-primary btn-sm rounded-pill px-3">
                                    <i class="bi bi-calendar-plus me-1"></i> Buchen
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- CTA-Banner -->
<div class="container mb-5">
    <div class="p-5 rounded-4 text-center bg-light border shadow-sm position-relative">
        <h3 class="fw-bold mb-2">Haben Sie bereits einen Termin gebucht?</h3>
        <p class="text-muted mx-auto mb-4" style="max-width: 600px;">
            Geben Sie einfach Ihre E-Mail-Adresse ein, um den Status Ihrer Buchungsanfragen einzusehen oder Termine bei Bedarf zu verwalten.
        </p>
        <a href="?route=bootschule/me/appointments" class="btn btn-outline-dark rounded-pill px-4">
            <i class="bi bi-calendar-check me-2"></i> Zu meinen Terminen
        </a>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../../../views/layouts/boating_portal.php';
