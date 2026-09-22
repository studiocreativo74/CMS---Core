<?php

declare(strict_types=1);

/**
 * Eigentümer- & Mieterportal: Dashboard
 *
 * @var array<string, mixed> $user
 * @var array<int, array<string, mixed>> $myUnits
 * @var array<int, array<string, mixed>> $myProperties
 * @var array<int, array<string, mixed>> $myCases
 * @var array<int, array<string, mixed>> $myDocuments
 * @var array<string, int> $stats
 */

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$title = 'Eigentümer- &amp; Mieterportal';
$currentRoute = 'portal/dashboard';

ob_start();
?>

<?php if ($flashSuccess): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-check-circle-fill text-success me-2" viewBox="0 0 16 16">
                <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
            </svg>
            <div><?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>
    </div>
<?php endif; ?>

<?php if ($flashError): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-exclamation-triangle-fill text-danger me-2" viewBox="0 0 16 16">
                <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5m.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/>
            </svg>
            <div><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>
    </div>
<?php endif; ?>

<!-- Begrüßungs-Banner mit Schnellauswahl -->
<div class="card border-0 shadow-sm mb-4 text-white" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);">
    <div class="card-body p-4 p-md-5">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <span class="badge bg-white text-primary fw-semibold px-3 py-1 rounded-pill mb-2">Eigentümer- &amp; Mieterportal</span>
                <h2 class="fw-bold mb-1">
                    Guten Tag, <?= htmlspecialchars((string) ($user['name'] ?? 'Nutzer'), ENT_QUOTES, 'UTF-8') ?>!
                </h2>
                <p class="text-white-50 mb-0">
                    Hier finden Sie Ihre Liegenschaften, Einheiten, aktuelle Vorgänge und hinterlegte Dokumente.
                </p>
            </div>
            <div>
                <a href="?route=portal/damage/report" class="btn btn-warning text-dark fw-bold px-4 py-2 shadow-sm d-inline-flex align-items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-tools" viewBox="0 0 16 16">
                        <path d="M1 0 0 1l2.2 3.081a1 1 0 0 0 .815.419h.07a1 1 0 0 1 .708.293l2.675 2.675-2.617 2.654A3.003 3.003 0 0 0 0 13a3 3 0 1 0 5.878-.851l2.654-2.617.968.968-.305.914a1 1 0 0 0 .242 1.023l3.27 3.27a.997.997 0 0 0 1.414 0l1.586-1.586a.997.997 0 0 0 0-1.414l-3.27-3.27a1 1 0 0 0-1.023-.242L10.5 9.5l-.969-.969 2.675-2.675a1 1 0 0 1 .708-.293h.07a1 1 0 0 0 .815-.419L16 1 15 0 11.919 2.2a1 1 0 0 0-.419.815v.07a1 1 0 0 1-.293.708L8.532 6.468 5.857 3.793l.676-.676a1 1 0 0 1 .708-.293h.07a1 1 0 0 0 .815-.419z"/>
                    </svg>
                    Schaden melden
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Kennzahlen-Kacheln -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <div class="p-3 bg-primary bg-opacity-10 text-primary rounded">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-door-closed" viewBox="0 0 16 16">
                        <path d="M3 2a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1v13h1.5a.5.5 0 0 1 0 1h-13a.5.5 0 0 1 0-1H3zm1 13h8V2H4z"/>
                    </svg>
                </div>
                <div>
                    <div class="text-muted small">Meine Einheiten</div>
                    <div class="h4 fw-bold text-dark mb-0"><?= count($myUnits) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <div class="p-3 bg-info bg-opacity-10 text-info rounded">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-building" viewBox="0 0 16 16">
                        <path d="M4 2.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5z"/>
                        <path d="M2 1a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1zm11 0H3v14h3v-2.5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5V15h3z"/>
                    </svg>
                </div>
                <div>
                    <div class="text-muted small">Liegenschaften</div>
                    <div class="h4 fw-bold text-dark mb-0"><?= count($myProperties) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <div class="p-3 bg-danger bg-opacity-10 text-danger rounded">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-tools" viewBox="0 0 16 16">
                        <path d="M1 0 0 1l2.2 3.081a1 1 0 0 0 .815.419h.07a1 1 0 0 1 .708.293l2.675 2.675-2.617 2.654A3.003 3.003 0 0 0 0 13a3 3 0 1 0 5.878-.851l2.654-2.617.968.968-.305.914a1 1 0 0 0 .242 1.023l3.27 3.27a.997.997 0 0 0 1.414 0l1.586-1.586a.997.997 0 0 0 0-1.414l-3.27-3.27a1 1 0 0 0-1.023-.242L10.5 9.5l-.969-.969 2.675-2.675a1 1 0 0 1 .708-.293h.07a1 1 0 0 0 .815-.419L16 1 15 0 11.919 2.2a1 1 0 0 0-.419.815v.07a1 1 0 0 1-.293.708L8.532 6.468 5.857 3.793l.676-.676a1 1 0 0 1 .708-.293h.07a1 1 0 0 0 .815-.419z"/>
                    </svg>
                </div>
                <div>
                    <div class="text-muted small">Aktive Vorgänge</div>
                    <div class="h4 fw-bold text-dark mb-0"><?= count($myCases) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <div class="p-3 bg-success bg-opacity-10 text-success rounded">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-file-earmark-check" viewBox="0 0 16 16">
                        <path d="M10.854 7.854a.5.5 0 0 0-.708-.708L7.5 9.793 6.354 8.646a.5.5 0 1 0-.708.708l1.5 1.5a.5.5 0 0 0 .708 0z"/>
                        <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2M9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5z"/>
                    </svg>
                </div>
                <div>
                    <div class="text-muted small">Dokumente</div>
                    <div class="h4 fw-bold text-dark mb-0"><?= count($myDocuments) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Linke Spalte: Meine Einheiten -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0 fw-semibold text-dark d-flex align-items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-house-door text-primary" viewBox="0 0 16 16">
                        <path d="M8.354 1.146a.5.5 0 0 0-.708 0l-6 6A.5.5 0 0 0 1.5 7.5v7a.5.5 0 0 0 .5.5h4.5a.5.5 0 0 0 .5-.5v-4h2v4a.5.5 0 0 0 .5.5H14a.5.5 0 0 0 .5-.5v-7a.5.5 0 0 0-.146-.354L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293zM2.5 14V7.707l5.5-5.5 5.5 5.5V14H10v-4a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5v4z"/>
                    </svg>
                    Meine Liegenschaften &amp; Einheiten
                </h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($myUnits)): ?>
                    <p class="text-muted small p-4 mb-0">Ihnen sind derzeit noch keine Einheiten in der Hausverwaltung zugewiesen.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($myUnits as $mu): 
                            $relLabel = PortalService::RELATION_TYPES[$mu['relation_type'] ?? 'owner'] ?? 'Eigentümer';
                        ?>
                            <div class="list-group-item p-3">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <div>
                                        <div class="fw-bold text-dark">
                                            Einheit <?= htmlspecialchars((string) $mu['unit_number'], ENT_QUOTES, 'UTF-8') ?>
                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 ms-1 fw-normal">
                                                <?= htmlspecialchars($relLabel, ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </div>
                                        <div class="text-muted small">
                                            <?= htmlspecialchars((string) $mu['property_name'], ENT_QUOTES, 'UTF-8') ?> &bull; 
                                            <?= htmlspecialchars((string) ($mu['property_street'] ?? ''), ENT_QUOTES, 'UTF-8') ?>, 
                                            <?= htmlspecialchars((string) ($mu['property_zip'] ?? ''), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string) ($mu['property_city'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <?php if (!empty($mu['size_sqm'])): ?>
                                            <div class="small fw-semibold text-dark"><?= number_format((float) $mu['size_sqm'], 2, ',', '.') ?> m²</div>
                                        <?php endif; ?>
                                        <?php if (!empty($mu['mea'])): ?>
                                            <div class="text-muted" style="font-size: 0.72rem;"><?= number_format((float) $mu['mea'], 4, ',', '.') ?> / 1.000 MEA</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Rechte Spalte: Aktuelle Vorgänge & Schadensfälle -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0 fw-semibold text-dark d-flex align-items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clock-history text-danger" viewBox="0 0 16 16">
                        <path d="M8.515 1.019A7 7 0 0 0 8 1V0a8 8 0 0 1 .589.022zm2.004.45a7 7 0 0 0-.985-.299l.219-.976q.576.129 1.126.342zm1.37.71a7 7 0 0 0-.439-.27l.493-.87a8 8 0 0 1 .979.654l-.615.789a7 7 0 0 0-.418-.302zm1.834 1.79a7 7 0 0 0-.653-.796l.724-.69q.406.429.747.91zm.744 1.352a7 7 0 0 0-.214-.468l.893-.45a8 8 0 0 1 .45 1.088l-.95.313a7 7 0 0 0-.179-.483m.53 2.507a7 7 0 0 0-.1-1.025l.985-.17q.1.58.116 1.17zm-.131 1.538q.05-.254.081-.51l.993.123a8 8 0 0 1-.23 1.155l-.964-.267q.069-.247.12-.501m-.952 2.379q.276-.436.486-.908l.914.405q-.24.54-.555 1.038zm-.964 1.205q.183-.183.35-.378l.758.653a8 8 0 0 1-.408.455zm-1.084.777q.392-.213.742-.475l.59.807q-.416.313-.883.56zm-1.528.618q.441-.115.859-.277l.353.935q-.495.192-1.017.327zm-1.802.324q.5-.044.985-.145l.2 1q-.56.113-1.134.161zM8 15a7 7 0 1 0 0-14 7 7 0 0 0 0 14m0 1A8 8 0 1 1 8 0a8 8 0 0 1 0 16"/>
                        <path d="M7.5 3a.5.5 0 0 1 .5.5v5.21l3.248 1.856a.5.5 0 0 1-.496.868l-3.5-2A.5.5 0 0 1 7 9V3.5a.5.5 0 0 1 .5-.5"/>
                    </svg>
                    Aktuelle Vorgänge &amp; Schäden
                </h6>
                <a href="?route=portal/damage/report" class="btn btn-sm btn-outline-danger">
                    + Melden
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($myCases)): ?>
                    <p class="text-muted small p-4 mb-0">Keine offenen Schadensfälle oder Vorgänge vorhanden.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($myCases as $c): 
                            $statusMeta = PortalService::CASE_STATUSES[$c['status']] ?? PortalService::CASE_STATUSES['new'];
                        ?>
                            <a href="?route=portal/case&id=<?= (int) $c['id'] ?>" class="list-group-item list-group-item-action p-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="badge <?= $statusMeta['badge'] ?> rounded-pill fw-normal" style="font-size: 0.72rem;">
                                        <?= htmlspecialchars($statusMeta['label'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                    <span class="text-muted" style="font-size: 0.75rem;"><?= date('d.m.Y', strtotime((string) $c['created_at'])) ?></span>
                                </div>
                                <div class="fw-semibold text-dark mb-1">
                                    <?= htmlspecialchars((string) $c['title'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <div class="text-muted small">
                                    <?= htmlspecialchars((string) $c['property_name'], ENT_QUOTES, 'UTF-8') ?> 
                                    <?= !empty($c['unit_number']) ? ' &bull; WE ' . htmlspecialchars((string) $c['unit_number'], ENT_QUOTES, 'UTF-8') : '' ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Neueste Dokumente & Pläne (DMS) -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0 fw-semibold text-dark d-flex align-items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-folder-check text-success" viewBox="0 0 16 16">
                <path d="m.5 3 .04.87a2 2 0 0 0-.342 1.311l.637 7A2 2 0 0 0 2.826 14H9v-1H2.826a1 1 0 0 1-.995-.91l-.637-7A1 1 0 0 1 2.19 4h11.62a1 1 0 0 1 .996 1.09L14.54 8h1.005l.256-2.819A2 2 0 0 0 13.81 3H9.828a2 2 0 0 1-1.414-.586l-.828-.828A2 2 0 0 0 6.172 1H2.5a2 2 0 0 0-2 2m5.672-1a1 1 0 0 1 .707.293L7.586 3H2.19q-.362.002-.683.12L1.5 2.98a1 1 0 0 1 1-.98z"/>
                <path d="M15.854 10.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 0 1 .708-.708l1.146 1.147 2.646-2.647a.5.5 0 0 1 .708 0"/>
            </svg>
            Meine Dokumente &amp; Mitteilungen
        </h6>
        <a href="?route=portal/documents" class="btn btn-sm btn-outline-secondary">
            Alle Dokumente ansehen &rarr;
        </a>
    </div>
    <div class="card-body p-0">
        <?php if (empty($myDocuments)): ?>
            <p class="text-muted small p-4 mb-0">Aktuell sind keine Dokumente (z.B. Abrechnungen, Protokolle, Hausordnung) hinterlegt.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Titel</th>
                            <th>Typ</th>
                            <th>Datum</th>
                            <th>Größe</th>
                            <th class="pe-3 text-end" style="width: 140px;">Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($myDocuments, 0, 8) as $doc): ?>
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars((string) $doc['title'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="text-muted small" style="font-size: 0.72rem;"><?= htmlspecialchars((string) ($doc['target_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars((string) ($doc['doc_type'] ?? 'MISC'), ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td class="text-muted"><?= date('d.m.Y', strtotime((string) ($doc['document_date'] ?? $doc['created_at']))) ?></td>
                                <td class="text-muted"><?= Upload::formatBytes((int) ($doc['file_size'] ?? 0)) ?></td>
                                <td class="pe-3 text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="?route=admin/documents/download&id=<?= (int) $doc['id'] ?>&inline=1" target="_blank" class="btn btn-outline-secondary">
                                            Vorschau
                                        </a>
                                        <a href="?route=admin/documents/download&id=<?= (int) $doc['id'] ?>&inline=0" class="btn btn-outline-secondary">
                                            ↓
                                        </a>
                                    </div>
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
