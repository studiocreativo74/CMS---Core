<?php

declare(strict_types=1);

/**
 * Eigentümer- & Mieterportal: Meine Vorgänge & Schäden
 *
 * @var array<int, array<string, mixed>> $cases
 */

$title = 'Meine Vorgänge &amp; Schäden';
$currentRoute = 'portal/cases';

ob_start();
?>

<!-- Breadcrumbs & Zurück -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="?route=portal/dashboard" class="text-decoration-none">Portal</a></li>
            <li class="breadcrumb-item active" aria-current="page">Meine Vorgänge</li>
        </ol>
    </nav>
    <div class="d-flex align-items-center gap-2">
        <a href="?route=portal/dashboard" class="btn btn-sm btn-outline-secondary">
            &larr; Zurück zum Dashboard
        </a>
        <a href="?route=portal/damage/report" class="btn btn-sm btn-danger fw-semibold">
            + Schaden melden
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0 fw-semibold text-dark">
            Vorgänge &amp; Schadensmeldungen (<?= count($cases) ?>)
        </h6>
    </div>

    <div class="card-body p-0">
        <?php if (empty($cases)): ?>
            <div class="text-center py-5">
                <p class="text-muted small mb-3">Aktuell liegen keine Vorgänge oder Schadensmeldungen für Ihre Einheiten vor.</p>
                <a href="?route=portal/damage/report" class="btn btn-sm btn-outline-danger">
                    Neue Schadenmeldung erfassen
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Art</th>
                            <th>Titel / Anliegen</th>
                            <th>Liegenschaft / Einheit</th>
                            <th>Status</th>
                            <th>Datum</th>
                            <th class="pe-3 text-end">Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cases as $c): 
                            $typeMeta = PortalService::CASE_TYPES[$c['case_type']] ?? PortalService::CASE_TYPES['property'];
                            $statusMeta = PortalService::CASE_STATUSES[$c['status']] ?? PortalService::CASE_STATUSES['new'];
                        ?>
                            <tr>
                                <td class="ps-3">
                                    <span class="badge rounded-pill px-2 py-1" style="background-color: <?= $typeMeta['color'] ?>15; color: <?= $typeMeta['color'] ?>; border: 1px solid <?= $typeMeta['color'] ?>30;">
                                        <?= htmlspecialchars($typeMeta['label'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="?route=portal/case&id=<?= (int) $c['id'] ?>" class="fw-semibold text-dark text-decoration-none">
                                        <?= htmlspecialchars((string) $c['title'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                    <?php if (!empty($c['damage_location'])): ?>
                                        <div class="text-muted small">Ort: <?= htmlspecialchars((string) $c['damage_location'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars((string) $c['property_name'], ENT_QUOTES, 'UTF-8') ?>
                                    <?= !empty($c['unit_number']) ? ' &bull; WE ' . htmlspecialchars((string) $c['unit_number'], ENT_QUOTES, 'UTF-8') : '' ?>
                                </td>
                                <td>
                                    <span class="badge <?= $statusMeta['badge'] ?> rounded-pill fw-normal px-2 py-1">
                                        <?= htmlspecialchars($statusMeta['label'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td class="text-muted"><?= date('d.m.Y', strtotime((string) $c['created_at'])) ?></td>
                                <td class="pe-3 text-end">
                                    <a href="?route=portal/case&id=<?= (int) $c['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        Details &amp; Verlauf &rarr;
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../../../views/layouts/admin.php';
