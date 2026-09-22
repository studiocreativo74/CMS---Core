<?php

declare(strict_types=1);

/**
 * Admin: Vorgangsverwaltung (Cases: Gebäudeakte, Versammlungen, Schadenfälle)
 *
 * @var array<int, array<string, mixed>> $cases
 * @var int $totalCases
 * @var array<int, array<string, mixed>> $properties
 * @var array<string, mixed> $filters
 */

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$title = 'Vorgänge & Akten (Eigentümerportal)';
$currentRoute = 'admin/portal/cases';

$canManageCases = PortalService::canManageCases();
$currentType = $filters['case_type'] ?? '';

ob_start();
?>

<?php if ($flashSuccess): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
        <div><?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>
    </div>
<?php endif; ?>

<?php if ($flashError): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
        <div><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>
    </div>
<?php endif; ?>

<!-- Obere Navigations- & Aktionsleiste -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Vorgänge &amp; Gebäudeakten</h4>
        <p class="text-muted small mb-0">Zentrale Verwaltung von allgemeinen Akten, Eigentümerversammlungen und Schadensmeldungen.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="?route=admin/portal/properties" class="btn btn-sm btn-outline-secondary">
            Liegenschaften
        </a>
        <?php if ($canManageCases): ?>
            <button type="button" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#createGeneralCaseModal">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-plus-circle" viewBox="0 0 16 16">
                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                    <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4"/>
                </svg>
                Neuen Vorgang anlegen
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Schnellauswahl nach Case-Typ -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <a href="?route=admin/portal/cases" class="card border-0 shadow-sm text-decoration-none <?= $currentType === '' ? 'border-primary border-start border-4' : '' ?>">
            <div class="card-body p-3">
                <div class="text-muted small">Alle Vorgänge</div>
                <div class="h5 fw-bold text-dark mb-0"><?= $totalCases ?></div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="?route=admin/portal/cases&case_type=property" class="card border-0 shadow-sm text-decoration-none <?= $currentType === 'property' ? 'border-primary border-start border-4' : '' ?>">
            <div class="card-body p-3">
                <div class="text-muted small">Gebäudeakte / Allgemein</div>
                <div class="h5 fw-bold text-primary mb-0">Objektakten</div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="?route=admin/portal/cases&case_type=meeting" class="card border-0 shadow-sm text-decoration-none <?= $currentType === 'meeting' ? 'border-purple border-start border-4' : '' ?>">
            <div class="card-body p-3">
                <div class="text-muted small">Eigentümerversammlungen</div>
                <div class="h5 fw-bold text-purple mb-0">Versammlungen</div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="?route=admin/portal/cases&case_type=damage" class="card border-0 shadow-sm text-decoration-none <?= $currentType === 'damage' ? 'border-danger border-start border-4' : '' ?>">
            <div class="card-body p-3">
                <div class="text-muted small">Schadenfälle &amp; Reparaturen</div>
                <div class="h5 fw-bold text-danger mb-0">Schadensmeldungen</div>
            </div>
        </a>
    </div>
</div>

<!-- Filterleiste -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="index.php" class="row g-2 align-items-center">
            <input type="hidden" name="route" value="admin/portal/cases">

            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm bg-light" placeholder="Titel, Beschreibung oder Schadenort..." value="<?= htmlspecialchars((string) ($filters['search'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="col-md-3">
                <select name="property_id" class="form-select form-select-sm bg-light">
                    <option value="">Alle Liegenschaften</option>
                    <?php foreach ($properties as $p): ?>
                        <option value="<?= (int) $p['id'] ?>" <?= (!empty($filters['property_id']) && (int) $filters['property_id'] === (int) $p['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string) $p['name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm bg-light">
                    <option value="all">Alle Status</option>
                    <?php foreach (PortalService::CASE_STATUSES as $sk => $sinfo): ?>
                        <option value="<?= htmlspecialchars($sk, ENT_QUOTES, 'UTF-8') ?>" <?= (!empty($filters['status']) && $filters['status'] === $sk) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($sinfo['label'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-outline-secondary flex-grow-1">Filtern</button>
                <a href="?route=admin/portal/cases" class="btn btn-sm btn-light text-muted">Zurücksetzen</a>
            </div>
        </form>
    </div>
</div>

<!-- Liste der Vorgänge -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0 fw-semibold text-dark">
            Vorgänge 
            <span class="badge bg-light text-muted border ms-1 fw-normal"><?= count($cases) ?> Treffer</span>
        </h6>
    </div>

    <div class="card-body p-0">
        <?php if (empty($cases)): ?>
            <div class="text-center py-5">
                <p class="text-muted small mb-0">Keine Vorgänge gefunden, die den gewählten Kriterien entsprechen.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Typ</th>
                            <th>Titel / Gegenstand</th>
                            <th>Liegenschaft</th>
                            <th>Einheit</th>
                            <th>Status</th>
                            <th>Priorität</th>
                            <th>Nachrichten</th>
                            <th>Erstellt am</th>
                            <th class="pe-3 text-end" style="width: 120px;">Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cases as $c): 
                            $typeMeta = PortalService::CASE_TYPES[$c['case_type']] ?? PortalService::CASE_TYPES['property'];
                            $statusMeta = PortalService::CASE_STATUSES[$c['status']] ?? PortalService::CASE_STATUSES['new'];
                            $prioMeta = PortalService::PRIORITIES[$c['priority']] ?? PortalService::PRIORITIES['normal'];
                        ?>
                            <tr>
                                <td class="ps-3">
                                    <span class="badge rounded-pill px-2 py-1" style="background-color: <?= $typeMeta['color'] ?>15; color: <?= $typeMeta['color'] ?>; border: 1px solid <?= $typeMeta['color'] ?>30;">
                                        <?= htmlspecialchars($typeMeta['label'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="?route=admin/portal/case&id=<?= (int) $c['id'] ?>" class="fw-bold text-dark text-decoration-none">
                                        <?= htmlspecialchars((string) $c['title'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                    <?php if (!empty($c['damage_location'])): ?>
                                        <div class="text-muted small">Ort: <?= htmlspecialchars((string) $c['damage_location'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars((string) $c['property_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= !empty($c['unit_number']) ? htmlspecialchars((string) $c['unit_number'], ENT_QUOTES, 'UTF-8') : '<span class="text-muted">Liegenschaft</span>' ?></td>
                                <td>
                                    <span class="badge <?= $statusMeta['badge'] ?> rounded-pill fw-normal px-2 py-1">
                                        <?= htmlspecialchars($statusMeta['label'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= $prioMeta['badge'] ?> fw-normal">
                                        <?= htmlspecialchars($prioMeta['label'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-secondary border">
                                        <?= (int) ($c['message_count'] ?? 0) ?> Beiträge
                                    </span>
                                </td>
                                <td class="text-muted"><?= date('d.m.Y H:i', strtotime((string) $c['created_at'])) ?></td>
                                <td class="pe-3 text-end">
                                    <a href="?route=admin/portal/case&id=<?= (int) $c['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        Details &rarr;
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

<!-- ========================================================================= -->
<!-- MODAL: NEUEN VORGANG ALLGEMEIN ANLEGEN                                    -->
<!-- ========================================================================= -->
<?php if ($canManageCases): ?>
<div class="modal fade" id="createGeneralCaseModal" tabindex="-1" aria-labelledby="createGeneralCaseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="?route=admin/portal/case/create">
                <?= Csrf::field() ?>

                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark" id="createGeneralCaseModalLabel">Neuen Vorgang anlegen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="modalPropertyId" class="form-label fw-semibold">Liegenschaft auswählen <span class="text-danger">*</span></label>
                            <select name="property_id" id="modalPropertyId" class="form-select" required>
                                <option value="">-- Liegenschaft wählen --</option>
                                <?php foreach ($properties as $p): ?>
                                    <option value="<?= (int) $p['id'] ?>"><?= htmlspecialchars((string) $p['name'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="modalCaseType" class="form-label fw-semibold">Vorgangs-Typ <span class="text-danger">*</span></label>
                            <select name="case_type" id="modalCaseType" class="form-select" required>
                                <?php foreach (PortalService::CASE_TYPES as $k => $info): ?>
                                    <option value="<?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($info['label'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="modalCaseTitle" class="form-label fw-semibold">Titel / Betreff <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="modalCaseTitle" class="form-control" placeholder="z. B. Ordentliche Eigentümerversammlung 2024 oder Wasserschaden Keller" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="modalCasePrio" class="form-label fw-semibold">Priorität</label>
                            <select name="priority" id="modalCasePrio" class="form-select">
                                <?php foreach (PortalService::PRIORITIES as $pk => $pinfo): ?>
                                    <option value="<?= htmlspecialchars($pk, ENT_QUOTES, 'UTF-8') ?>" <?= $pk === 'normal' ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($pinfo['label'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="modalCaseLocation" class="form-label fw-semibold">Schadenort / Raum (falls Schaden)</label>
                            <input type="text" name="damage_location" id="modalCaseLocation" class="form-control" placeholder="z. B. Kellerabteil, Fassade Südseite">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="modalCaseDesc" class="form-label fw-semibold">Beschreibung</label>
                        <textarea name="description" id="modalCaseDesc" rows="3" class="form-control" placeholder="Details zum Sachverhalt..."></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-sm btn-primary fw-semibold">Vorgang anlegen</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../../../views/layouts/admin.php';
