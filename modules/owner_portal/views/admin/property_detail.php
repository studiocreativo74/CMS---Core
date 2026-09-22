<?php

declare(strict_types=1);

/**
 * Admin: Liegenschaft Detailansicht (Einheiten, Cases & DMS-Dokumente)
 *
 * @var array<string, mixed> $property
 * @var array<int, array<string, mixed>> $units
 * @var array<int, array<string, mixed>> $cases
 * @var array<int, array<string, mixed>> $documents
 * @var array<int, array<string, mixed>> $allDmsDocuments
 */

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$title = htmlspecialchars((string) $property['name'], ENT_QUOTES, 'UTF-8');
$currentRoute = 'admin/portal/properties';

$canManage = PortalService::canManageProperties();
$canManageUnits = PortalService::canManageUnits();
$canManageCases = PortalService::canManageCases();

ob_start();
?>

<!-- Breadcrumbs & Zurück -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="?route=admin/portal/properties" class="text-decoration-none">Liegenschaften</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars((string) $property['name'], ENT_QUOTES, 'UTF-8') ?></li>
        </ol>
    </nav>
    <div class="d-flex align-items-center gap-2">
        <a href="?route=admin/portal/properties" class="btn btn-sm btn-outline-secondary">
            &larr; Zurück zur Übersicht
        </a>
    </div>
</div>

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

<!-- Liegenschafts-Kopfkarte -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1">Liegenschaft</span>
                    <?php if (!empty($property['external_ref'])): ?>
                        <span class="badge bg-light text-secondary border">Verw.-Nr: <?= htmlspecialchars((string) $property['external_ref'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </div>

                <h3 class="fw-bold text-dark mb-1"><?= htmlspecialchars((string) $property['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                <p class="text-muted mb-0 small">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-geo-alt me-1 text-danger" viewBox="0 0 16 16">
                        <path d="M12.166 8.94c-.524 1.062-1.234 2.12-1.96 3.07A32 32 0 0 1 8 14.58a32 32 0 0 1-2.206-2.57c-.726-.95-1.436-2.008-1.96-3.07C3.304 7.867 3 6.862 3 6a5 5 0 0 1 10 0c0 .862-.305 1.867-.834 2.94M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10"/>
                        <path d="M8 8a2 2 0 1 1 0-4 2 2 0 0 1 0 4m0 1a3 3 0 1 0 0-6 3 3 0 0 0 0 6"/>
                    </svg>
                    <?= htmlspecialchars((string) ($property['street'] ?? 'Keine Straße angegeben'), ENT_QUOTES, 'UTF-8') ?>, 
                    <?= htmlspecialchars((string) ($property['zip'] ?? ''), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string) ($property['city'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </p>
                <?php if (!empty($property['notes'])): ?>
                    <div class="mt-2 text-secondary small bg-light p-2 rounded">
                        <strong>Notizen:</strong> <?= nl2br(htmlspecialchars((string) $property['notes'], ENT_QUOTES, 'UTF-8')) ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($canManage): ?>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editPropertyModal">
                        Liegenschaft bearbeiten
                    </button>
                    <?php if ($canManageUnits): ?>
                        <button type="button" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#createUnitModal">
                            + Einheit anlegen
                        </button>
                    <?php endif; ?>
                    <?php if ($canManageCases): ?>
                        <button type="button" class="btn btn-sm btn-success d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#createCaseModal">
                            + Neuer Vorgang
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 1. EINHEITEN (UNITS) DER LIEGENSCHAFT                                     -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0 fw-semibold text-dark d-flex align-items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-door-closed text-primary" viewBox="0 0 16 16">
                <path d="M3 2a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1v13h1.5a.5.5 0 0 1 0 1h-13a.5.5 0 0 1 0-1H3zm1 13h8V2H4z"/>
                <path d="M9 9a1 1 0 1 0 2 0 1 1 0 0 0-2 0"/>
            </svg>
            Einheiten &amp; Wohnungen (<?= count($units) ?>)
        </h6>
        <?php if ($canManageUnits): ?>
            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#createUnitModal">
                + Einheit hinzufügen
            </button>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php if (empty($units)): ?>
            <p class="text-muted small p-4 mb-0">Noch keine Einheiten zu dieser Liegenschaft erfasst.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Einheit / Nr.</th>
                            <th>Typ</th>
                            <th>Etage</th>
                            <th>Fläche</th>
                            <th>MEA (Anteile)</th>
                            <th>Zugeordnete Nutzer</th>
                            <th>Offene Vorgänge</th>
                            <th class="pe-3 text-end" style="width: 140px;">Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($units as $u): 
                            $typeLabel = PortalService::UNIT_TYPES[$u['type'] ?? 'apartment'] ?? 'Wohnung';
                        ?>
                            <tr>
                                <td class="ps-3">
                                    <a href="?route=admin/portal/unit&id=<?= (int) $u['id'] ?>" class="fw-bold text-dark text-decoration-none">
                                        <?= htmlspecialchars((string) $u['unit_number'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                                <td><?= htmlspecialchars((string) ($u['floor'] ?: '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= !empty($u['size_sqm']) ? number_format((float) $u['size_sqm'], 2, ',', '.') . ' m²' : '—' ?></td>
                                <td><?= !empty($u['mea']) ? number_format((float) $u['mea'], 4, ',', '.') . ' / 1.000' : '—' ?></td>
                                <td>
                                    <span class="badge bg-light text-secondary border">
                                        <?= (int) ($u['user_count'] ?? 0) ?> Eigentümer/Mieter
                                    </span>
                                </td>
                                <td>
                                    <?php $uCases = (int) ($u['open_cases_count'] ?? 0); ?>
                                    <?php if ($uCases > 0): ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25"><?= $uCases ?> offen</span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-3 text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="?route=admin/portal/unit&id=<?= (int) $u['id'] ?>" class="btn btn-outline-primary" title="Details &amp; Eigentümer/Mieter zuweisen">
                                            Verwalten &rarr;
                                        </a>
                                        <?php if ($canManageUnits): ?>
                                            <form method="POST" action="?route=admin/portal/unit/delete" onsubmit="return confirm('Möchten Sie diese Einheit wirklich löschen?');" class="d-inline">
                                                <?= Csrf::field() ?>
                                                <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                                <input type="hidden" name="property_id" value="<?= (int) $property['id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger" title="Löschen">✕</button>
                                            </form>
                                        <?php endif; ?>
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

<!-- ========================================================================= -->
<!-- 2. CASES & VORGÄNGE (GEBÄUDEAKTE, VERSAMMLUNGEN, SCHÄDEN)                 -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0 fw-semibold text-dark d-flex align-items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-folder2-open text-warning" viewBox="0 0 16 16">
                <path d="M1 3.5A1.5 1.5 0 0 1 2.5 2h2.764c.958 0 1.76.56 2.311 1.184C7.985 3.648 8.48 4 9 4h4.5A1.5 1.5 0 0 1 15 5.5v.64c.57.265.94.876.856 1.546l-.64 5.124A2.5 2.5 0 0 1 12.733 15H3.266a2.5 2.5 0 0 1-2.481-2.19l-.64-5.124A1.5 1.5 0 0 1 1 6.14z"/>
            </svg>
            Vorgänge &amp; Gebäudeakte (<?= count($cases) ?>)
        </h6>
        <?php if ($canManageCases): ?>
            <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#createCaseModal">
                + Vorgang anlegen
            </button>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php if (empty($cases)): ?>
            <p class="text-muted small p-4 mb-0">Keine Vorgänge oder Schäden für diese Liegenschaft erfasst.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Typ</th>
                            <th>Titel / Gegenstand</th>
                            <th>Einheit</th>
                            <th>Status</th>
                            <th>Priorität</th>
                            <th>Ersteller</th>
                            <th>Datum</th>
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
                                    <span class="badge rounded-pill px-2 py-1" style="background-color: <?= $typeMeta['color'] ?>20; color: <?= $typeMeta['color'] ?>; border: 1px solid <?= $typeMeta['color'] ?>40;">
                                        <?= htmlspecialchars($typeMeta['label'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="?route=admin/portal/case&id=<?= (int) $c['id'] ?>" class="fw-semibold text-dark text-decoration-none">
                                        <?= htmlspecialchars((string) $c['title'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </td>
                                <td>
                                    <?= !empty($c['unit_number']) ? htmlspecialchars((string) $c['unit_number'], ENT_QUOTES, 'UTF-8') : '<span class="text-muted">Ganzes Objekt</span>' ?>
                                </td>
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
                                <td class="text-muted"><?= htmlspecialchars((string) ($c['creator_name'] ?? 'System'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-muted"><?= date('d.m.Y', strtotime((string) $c['created_at'])) ?></td>
                                <td class="pe-3 text-end">
                                    <a href="?route=admin/portal/case&id=<?= (int) $c['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                        Öffnen &rarr;
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
<!-- 3. VERKNÜPFTE DOKUMENTE & PLÄNE (DMS-INTEGRATION)                         -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0 fw-semibold text-dark d-flex align-items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-pdf text-danger" viewBox="0 0 16 16">
                <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2M9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5z"/>
            </svg>
            Dokumente &amp; Pläne aus DMS (<?= count($documents) ?>)
        </h6>
        <?php if ($canManage): ?>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#linkDocumentModal">
                    + Dokument verknüpfen
                </button>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php if (empty($documents)): ?>
            <div class="text-center py-4 text-muted small">
                Noch keine Dokumente oder Pläne aus dem DMS mit dieser Liegenschaft verknüpft.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Titel</th>
                            <th>Typ</th>
                            <th>Rolle</th>
                            <th>Sichtbarkeit</th>
                            <th>Datum</th>
                            <th>Größe</th>
                            <th class="pe-3 text-end" style="width: 140px;">Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents as $doc): ?>
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars((string) $doc['title'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="text-muted small" style="font-size: 0.72rem;"><?= htmlspecialchars((string) ($doc['original_filename'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars((string) ($doc['doc_type'] ?? 'MISC'), ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary"><?= htmlspecialchars((string) ($doc['relation_role'] ?? 'Anlage'), ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                                <td>
                                    <?php if (($doc['visibility'] ?? '') === 'owner_portal'): ?>
                                        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">Eigentümerportal</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border"><?= htmlspecialchars((string) ($doc['visibility'] ?? 'internal'), ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted"><?= date('d.m.Y', strtotime((string) ($doc['document_date'] ?? $doc['created_at']))) ?></td>
                                <td class="text-muted"><?= Upload::formatBytes((int) ($doc['file_size'] ?? 0)) ?></td>
                                <td class="pe-3 text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="?route=admin/documents/download&id=<?= (int) $doc['id'] ?>&inline=1" target="_blank" class="btn btn-outline-secondary" title="Vorschau">
                                            Vorschau
                                        </a>
                                        <a href="?route=admin/documents/download&id=<?= (int) $doc['id'] ?>&inline=0" class="btn btn-outline-secondary" title="Download">
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

<!-- ========================================================================= -->
<!-- MODAL: NEUE EINHEIT ANLEGEN                                               -->
<!-- ========================================================================= -->
<?php if ($canManageUnits): ?>
<div class="modal fade" id="createUnitModal" tabindex="-1" aria-labelledby="createUnitModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="?route=admin/portal/unit/create">
                <?= Csrf::field() ?>
                <input type="hidden" name="property_id" value="<?= (int) $property['id'] ?>">

                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark" id="createUnitModalLabel">Neue Einheit / Wohnung anlegen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="unitNumber" class="form-label fw-semibold">Bezeichnung / Einheiten-Nr. <span class="text-danger">*</span></label>
                        <input type="text" name="unit_number" id="unitNumber" class="form-control" placeholder="z. B. WE 04 oder 2. OG rechts" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="unitType" class="form-label fw-semibold">Typ</label>
                            <select name="type" id="unitType" class="form-select">
                                <?php foreach (PortalService::UNIT_TYPES as $tk => $tl): ?>
                                    <option value="<?= htmlspecialchars($tk, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($tl, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="unitFloor" class="form-label fw-semibold">Etage</label>
                            <input type="text" name="floor" id="unitFloor" class="form-control" placeholder="z. B. 2. OG">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="unitSize" class="form-label fw-semibold">Fläche in m²</label>
                            <input type="number" step="0.01" name="size_sqm" id="unitSize" class="form-control" placeholder="78.50">
                        </div>
                        <div class="col-md-6">
                            <label for="unitMea" class="form-label fw-semibold">MEA (Anteile / 1.000)</label>
                            <input type="number" step="0.0001" name="mea" id="unitMea" class="form-control" placeholder="125.4000">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="unitNotes" class="form-label fw-semibold">Notizen / Keller / Stellplatz-Nr.</label>
                        <textarea name="notes" id="unitNotes" rows="2" class="form-control" placeholder="z. B. inkl. Kellerabteil Nr. 4 und Tiefgaragenstellplatz TG-12"></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-sm btn-primary fw-semibold">Einheit speichern</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ========================================================================= -->
<!-- MODAL: NEUER VORGANG (CASE) ANLEGEN                                       -->
<!-- ========================================================================= -->
<?php if ($canManageCases): ?>
<div class="modal fade" id="createCaseModal" tabindex="-1" aria-labelledby="createCaseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="?route=admin/portal/case/create">
                <?= Csrf::field() ?>
                <input type="hidden" name="property_id" value="<?= (int) $property['id'] ?>">

                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark" id="createCaseModalLabel">Neuen Vorgang / Akte anlegen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="caseType" class="form-label fw-semibold">Vorgangs-Typ <span class="text-danger">*</span></label>
                            <select name="case_type" id="caseType" class="form-select" required>
                                <?php foreach (PortalService::CASE_TYPES as $k => $info): ?>
                                    <option value="<?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($info['label'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="caseUnit" class="form-label fw-semibold">Betroffene Einheit</label>
                            <select name="unit_id" id="caseUnit" class="form-select">
                                <option value="">Ganzes Gebäude / Allgemeinbereich</option>
                                <?php foreach ($units as $u): ?>
                                    <option value="<?= (int) $u['id'] ?>"><?= htmlspecialchars((string) $u['unit_number'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="caseTitle" class="form-label fw-semibold">Titel / Betreff <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="caseTitle" class="form-control" placeholder="z. B. Ordentliche Eigentümerversammlung 2024 oder Dachreparatur Nordseite" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="casePriority" class="form-label fw-semibold">Priorität</label>
                            <select name="priority" id="casePriority" class="form-select">
                                <?php foreach (PortalService::PRIORITIES as $pk => $pinfo): ?>
                                    <option value="<?= htmlspecialchars($pk, ENT_QUOTES, 'UTF-8') ?>" <?= $pk === 'normal' ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($pinfo['label'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="caseStatus" class="form-label fw-semibold">Status</label>
                            <select name="status" id="caseStatus" class="form-select">
                                <?php foreach (PortalService::CASE_STATUSES as $sk => $sinfo): ?>
                                    <option value="<?= htmlspecialchars($sk, ENT_QUOTES, 'UTF-8') ?>" <?= $sk === 'new' ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($sinfo['label'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="caseDesc" class="form-label fw-semibold">Beschreibung / Sachverhalt</label>
                        <textarea name="description" id="caseDesc" rows="3" class="form-control" placeholder="Detaillierte Informationen zum Vorgang..."></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-sm btn-success fw-semibold">Vorgang anlegen</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ========================================================================= -->
<!-- MODAL: DOKUMENT AUS DMS VERKNÜPFEN                                        -->
<!-- ========================================================================= -->
<?php if ($canManage): ?>
<div class="modal fade" id="linkDocumentModal" tabindex="-1" aria-labelledby="linkDocumentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="?route=admin/portal/document/link">
                <?= Csrf::field() ?>
                <input type="hidden" name="property_id" value="<?= (int) $property['id'] ?>">
                <input type="hidden" name="target_type" value="property">
                <input type="hidden" name="target_id" value="<?= (int) $property['id'] ?>">

                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark" id="linkDocumentModalLabel">Dokument aus DMS verknüpfen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="selectDoc" class="form-label fw-semibold">Dokument aus Archiv auswählen</label>
                        <select name="document_id" id="selectDoc" class="form-select" required>
                            <option value="">-- Bitte Dokument wählen --</option>
                            <?php foreach ($allDmsDocuments as $ad): ?>
                                <option value="<?= (int) $ad['id'] ?>">
                                    <?= htmlspecialchars((string) $ad['title'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars((string) $ad['doc_type'], ENT_QUOTES, 'UTF-8') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="relRole" class="form-label fw-semibold">Rolle des Dokuments</label>
                        <select name="relation_role" id="relRole" class="form-select">
                            <option value="plan">Plan / Grundriss</option>
                            <option value="protocol">Protokoll / Beschluss</option>
                            <option value="contract">Vertrag / Versicherung</option>
                            <option value="invoice">Abrechnung / Beleg</option>
                            <option value="attachment" selected>Allgemeine Anlage</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-sm btn-primary fw-semibold">Verknüpfen</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../../../views/layouts/admin.php';
