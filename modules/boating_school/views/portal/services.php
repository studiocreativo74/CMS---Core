<?php

declare(strict_types=1);

/**
 * Portal: Übersicht buchbarer Leistungen & Tarife (/bootschule/services)
 *
 * @var array<string, array<int, array<string, mixed>>> $groupedServices
 * @var array<string, string> $categoryLabels
 * @var string|null $selectedCategory
 */

$title = 'Leistungen & Tarife - Bootsfahrschule';
$currentRoute = 'bootschule/services';

ob_start();
?>

<div class="container mb-5">
    <!-- Header -->
    <div class="text-center max-w-700 mx-auto mb-5">
        <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-semibold mb-2">
            <i class="bi bi-tag-fill me-1"></i> Kursangebote &amp; Tarife
        </span>
        <h1 class="h2 fw-bold text-dark">Unsere Ausbildungsangebote &amp; Charter</h1>
        <p class="text-muted">
            Transparente Preise ohne versteckte Gebühren. Wählen Sie das passende Modul für Ihre Ausbildung oder Bootsausfahrt.
        </p>

        <!-- Kategorie-Filter-Tabs -->
        <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
            <a href="?route=bootschule/services" class="btn btn-sm rounded-pill px-3 <?= empty($selectedCategory) ? 'btn-primary' : 'btn-outline-secondary' ?>">
                Alle Kategorien
            </a>
            <?php foreach ($categoryLabels as $catKey => $catLabel): ?>
                <?php if (!empty($groupedServices[$catKey])): ?>
                    <a href="?route=bootschule/services&category=<?= urlencode($catKey) ?>" class="btn btn-sm rounded-pill px-3 <?= ($selectedCategory === $catKey) ? 'btn-primary' : 'btn-outline-secondary' ?>">
                        <?= htmlspecialchars($catLabel, ENT_QUOTES, 'UTF-8') ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Gruppierte Liste -->
    <?php if (empty($groupedServices)): ?>
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
            <i class="bi bi-info-circle text-muted fs-1 mb-3"></i>
            <h5 class="fw-bold">Keine Leistungen verfügbar</h5>
            <p class="text-muted mb-0">Aktuell sind in dieser Kategorie keine buchbaren Angebote hinterlegt.</p>
        </div>
    <?php else: ?>
        <?php foreach ($categoryLabels as $catKey => $catLabel): ?>
            <?php if (!empty($groupedServices[$catKey])): ?>
                <div class="mb-5">
                    <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                        <span class="p-2 rounded-3 bg-primary bg-opacity-10 text-primary">
                            <?php if ($catKey === 'practical'): ?>
                                <i class="bi bi-life-preserver fs-5"></i>
                            <?php elseif ($catKey === 'theory'): ?>
                                <i class="bi bi-book fs-5"></i>
                            <?php elseif ($catKey === 'rental'): ?>
                                <i class="bi bi-anchor fs-5"></i>
                            <?php elseif ($catKey === 'exam'): ?>
                                <i class="bi bi-award fs-5"></i>
                            <?php elseif ($catKey === 'package'): ?>
                                <i class="bi bi-box-seam fs-5"></i>
                            <?php else: ?>
                                <i class="bi bi-stars fs-5"></i>
                            <?php endif; ?>
                        </span>
                        <h3 class="h4 fw-bold text-dark mb-0">
                            <?= htmlspecialchars($catLabel, ENT_QUOTES, 'UTF-8') ?>
                        </h3>
                        <span class="badge bg-secondary-subtle text-muted ms-auto">
                            <?= count($groupedServices[$catKey]) ?> <?= count($groupedServices[$catKey]) === 1 ? 'Angebot' : 'Angebote' ?>
                        </span>
                    </div>

                    <div class="row g-4">
                        <?php foreach ($groupedServices[$catKey] as $srv): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden d-flex flex-column bg-white transition hover-shadow">
                                    <div class="card-body p-4 d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <span class="badge bg-light text-primary border fw-semibold px-2 py-1 rounded">
                                                <?= htmlspecialchars((string) ($srv['code'] ?: 'LEISTUNG'), ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                            <span class="text-muted small">
                                                <i class="bi bi-clock me-1"></i><?= (int) ($srv['duration_minutes'] ?? 60) ?> Min.
                                            </span>
                                        </div>

                                        <h5 class="fw-bold text-dark mb-2">
                                            <?= htmlspecialchars((string) $srv['name'], ENT_QUOTES, 'UTF-8') ?>
                                        </h5>

                                        <?php if (!empty($srv['boat_type']) && $srv['boat_type'] !== 'any'): ?>
                                            <div class="mb-2">
                                                <span class="badge bg-info-subtle text-info small">
                                                    Bootstyp: <?= htmlspecialchars((string) $srv['boat_type'], ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>

                                        <p class="text-muted small mb-4 flex-grow-1">
                                            <?= nl2br(htmlspecialchars((string) ($srv['description'] ?: 'Individuelle Praxisausbildung oder Bootsmiete.'), ENT_QUOTES, 'UTF-8')) ?>
                                        </p>

                                        <div class="pt-3 border-top d-flex align-items-center justify-content-between mt-auto">
                                            <div>
                                                <div class="small text-muted">Tarif</div>
                                                <div class="h5 fw-bold text-primary mb-0">
                                                    CHF <?= number_format((float) ($srv['price'] ?? 0), 2, '.', "'") ?>
                                                    <?php if (!empty($srv['tax_rate']) && (float) $srv['tax_rate'] > 0): ?>
                                                        <span class="small text-muted fw-normal" style="font-size: 0.75rem;">inkl. <?= number_format((float) $srv['tax_rate'], 1) ?>% MwSt.</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <a href="?route=bootschule/book&service_id=<?= (int) $srv['id'] ?>" class="btn btn-primary rounded-pill px-3 fw-semibold">
                                                <i class="bi bi-calendar-check me-1"></i> Slot buchen
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../../../views/layouts/boating_portal.php';
