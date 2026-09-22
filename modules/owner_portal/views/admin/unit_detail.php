<?php

declare(strict_types=1);

/**
 * Admin: Einheiten Detailansicht (Zuweisung von Eigentümern/Mietern & Dokumente)
 *
 * @var array<string, mixed> $unit
 * @var array<int, array<string, mixed>> $unitUsers
 * @var array<int, array<string, mixed>> $allUsers
 * @var array<int, array<string, mixed>> $documents
 * @var array<int, array<string, mixed>> $allDmsDocuments
 * @var array<int, array<string, mixed>> $cases
 */

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$title = 'Einheit: ' . htmlspecialchars((string) $unit['unit_number'], ENT_QUOTES, 'UTF-8');
$currentRoute = 'admin/portal/properties';

$canManageUnits = PortalService::canManageUnits();
$typeLabel = PortalService::UNIT_TYPES[$unit['type'] ?? 'apartment'] ?? 'Wohnung';

ob_start();
?>

<!-- Breadcrumbs & Zurück -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="?route=admin/portal/properties" class="text-decoration-none">Liegenschaften</a></li>
            <li class="breadcrumb-item"><a href="?route=admin/portal/property&id=<?= (int) $unit['property_id'] ?>" class="text-decoration-none"><?= htmlspecialchars((string) $unit['property_name'], ENT_QUOTES, 'UTF-8') ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars((string) $unit['unit_number'], ENT_QUOTES, 'UTF-8') ?></li>
        </ol>
    </nav>
    <div class="d-flex align-items-center gap-2">
        <a href="?route=admin/portal/property&id=<?= (int) $unit['property_id'] ?>" class="btn btn-sm btn-outline-secondary">
            &larr; Zurück zur Liegenschaft
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

<!-- Einheiten-Kopfkarte -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-secondary bg-opacity-10 text-secondary border px-2 py-1"><?= htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="badge bg-light text-muted border">Liegenschaft: <?= htmlspecialchars((string) $unit['property_name'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>

                <h3 class="fw-bold text-dark mb-1">Einheit <?= htmlspecialchars((string) $unit['unit_number'], ENT_QUOTES, 'UTF-8') ?></h3>
                <div class="row g-3 mt-1 text-muted small">
                    <div class="col-auto"><strong>Etage:</strong> <?= htmlspecialchars((string) ($unit['floor'] ?: '—'), ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="col-auto"><strong>Wohnfläche:</strong> <?= !empty($unit['size_sqm']) ? number_format((float) $unit['size_sqm'], 2, ',', '.') . ' m²' : '—' ?></div>
                    <div class="col-auto"><strong>Miteigentumsanteil:</strong> <?= !empty($unit['mea']) ? number_format((float) $unit['mea'], 4, ',', '.') . ' / 1.000' : '—' ?></div>
                </div>

                <?php if (!empty($unit['notes'])): ?>
                    <div class="mt-3 text-secondary small bg-light p-2 rounded">
                        <strong>Notizen:</strong> <?= nl2br(htmlspecialchars((string) $unit['notes'], ENT_QUOTES, 'UTF-8')) ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($canManageUnits): ?>
                <button type="button" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#assignUserModal">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-person-plus" viewBox="0 0 16 16">
                        <path d="M6 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H1s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C9.516 10.68 8.289 10 6 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z"/>
                        <path fill-rule="evenodd" d="M13.5 5a.5.5 0 0 1 .5.5V7h1.5a.5.5 0 0 1 0 1H14v1.5a.5.5 0 0 1-1 0V8h-1.5a.5.5 0 0 1 0-1H13V5.5a.5.5 0 0 1 .5-.5"/>
                    </svg>
                    Eigentümer / Mieter zuordnen
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 1. ZUGEORDNETE BENUTZER (EIGENTÜMER & MIETER)                             -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0 fw-semibold text-dark d-flex align-items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-people text-primary" viewBox="0 0 16 16">
                <path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1zm-7.978-1L7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002-.014.002zM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4m3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0M6.936 9.28a6 6 0 0 0-1.23-.247A7 7 0 0 0 5 9c-4 0-5 3-5 4q0 1 1 1h4.216A2.24 2.24 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816M4.92 10A5.5 5.5 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275ZM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0m3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4"/>
            </svg>
            Zugeordnete Benutzer (<?= count($unitUsers) ?>)
        </h6>
        <?php if ($canManageUnits): ?>
            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#assignUserModal">
                + Nutzer zuweisen
            </button>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php if (empty($unitUsers)): ?>
            <div class="text-center py-4 text-muted small">
                Dieser Einheit ist aktuell noch kein Eigentümer oder Mieter zugewiesen.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Name</th>
                            <th>E-Mail</th>
                            <th>Beziehung</th>
                            <th>Gültig von</th>
                            <th>Gültig bis</th>
                            <th>Notizen</th>
                            <th class="pe-3 text-end" style="width: 100px;">Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($unitUsers as $uu): 
                            $relLabel = PortalService::RELATION_TYPES[$uu['relation_type'] ?? 'owner'] ?? 'Eigentümer';
                            $badgeColor = match($uu['relation_type'] ?? '') {
                                'owner' => 'bg-primary',
                                'tenant' => 'bg-info text-dark',
                                'advisory_board' => 'bg-purple text-white',
                                default => 'bg-secondary'
                            };
                        ?>
                            <tr>
                                <td class="ps-3 fw-semibold text-dark">
                                    <?= htmlspecialchars((string) $uu['user_name'], ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td class="text-muted"><?= htmlspecialchars((string) $uu['user_email'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge <?= $badgeColor ?> px-2 py-1 fw-normal">
                                        <?= htmlspecialchars($relLabel, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td><?= !empty($uu['valid_from']) ? date('d.m.Y', strtotime((string) $uu['valid_from'])) : '<span class="text-muted">—</span>' ?></td>
                                <td><?= !empty($uu['valid_to']) ? date('d.m.Y', strtotime((string) $uu['valid_to'])) : '<span class="text-muted">—</span>' ?></td>
                                <td class="text-muted"><?= htmlspecialchars((string) ($uu['notes'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="pe-3 text-end">
                                    <?php if ($canManageUnits): ?>
                                        <form method="POST" action="?route=admin/portal/unit/user/remove" onsubmit="return confirm('Möchten Sie diese Zuordnung wirklich aufheben?');" class="d-inline">
                                            <?= Csrf::field() ?>
                                            <input type="hidden" name="assignment_id" value="<?= (int) $uu['id'] ?>">
                                            <input type="hidden" name="unit_id" value="<?= (int) $unit['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Zuordnung entfernen">✕</button>
                                        </form>
                                    <?php endif; ?>
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
<!-- 2. DOKUMENTE & PLÄNE DER EINHEIT (DMS)                                    -->
<!-- ========================================================================= -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0 fw-semibold text-dark d-flex align-items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-text text-danger" viewBox="0 0 16 16">
                <path d="M5.5 7a.5.5 0 0 0 0 1h5a.5.5 0 0 0 0-1zM5 9.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5"/>
                <path d="M9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.5zm0 1v3.5A1.5 1.5 0 0 0 11 6h3.5z"/>
            </svg>
            Einheiten-Dokumente aus DMS (<?= count($documents) ?>)
        </h6>
        <?php if ($canManageUnits): ?>
            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#linkUnitDocModal">
                + Dokument verknüpfen
            </button>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php if (empty($documents)): ?>
            <p class="text-muted small p-4 mb-0">Keine spezifischen Dokumente (z.B. Grundriss, Mietvertrag) für diese Einheit hinterlegt.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Titel</th>
                            <th>Typ</th>
                            <th>Rolle</th>
                            <th>Sichtbarkeit</th>
                            <th>Größe</th>
                            <th class="pe-3 text-end">Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents as $doc): ?>
                            <tr>
                                <td class="ps-3 fw-semibold text-dark"><?= htmlspecialchars((string) $doc['title'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars((string) ($doc['doc_type'] ?? 'MISC'), ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td><?= htmlspecialchars((string) ($doc['relation_role'] ?? 'attachment'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="badge bg-light text-secondary border"><?= htmlspecialchars((string) ($doc['visibility'] ?? 'internal'), ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td><?= Upload::formatBytes((int) ($doc['file_size'] ?? 0)) ?></td>
                                <td class="pe-3 text-end">
                                    <a href="?route=admin/documents/download&id=<?= (int) $doc['id'] ?>&inline=1" target="_blank" class="btn btn-sm btn-outline-secondary">
                                        Vorschau
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
<!-- MODAL: BENUTZER ZUWEISEN (EIGENTÜMER / MIETER)                            -->
<!-- ========================================================================= -->
<?php if ($canManageUnits): ?>
<div class="modal fade" id="assignUserModal" tabindex="-1" aria-labelledby="assignUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="?route=admin/portal/unit/user/assign">
                <?= Csrf::field() ?>
                <input type="hidden" name="unit_id" value="<?= (int) $unit['id'] ?>">

                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark" id="assignUserModalLabel">Benutzer zu Einheit zuweisen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="assignUserId" class="form-label fw-semibold">Benutzer-Account auswählen <span class="text-danger">*</span></label>
                        <select name="user_id" id="assignUserId" class="form-select" required>
                            <option value="">-- Benutzer auswählen --</option>
                            <?php foreach ($allUsers as $u): ?>
                                <option value="<?= (int) $u['id'] ?>">
                                    <?= htmlspecialchars((string) $u['name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars((string) $u['email'], ENT_QUOTES, 'UTF-8') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="relationType" class="form-label fw-semibold">Rolle an dieser Einheit <span class="text-danger">*</span></label>
                        <select name="relation_type" id="relationType" class="form-select" required>
                            <?php foreach (PortalService::RELATION_TYPES as $rk => $rlabel): ?>
                                <option value="<?= htmlspecialchars($rk, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($rlabel, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="validFrom" class="form-label fw-semibold">Gültig ab (Einzug / Kauf)</label>
                            <input type="date" name="valid_from" id="validFrom" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label for="validTo" class="form-label fw-semibold">Gültig bis (Auszug / Verkauf)</label>
                            <input type="date" name="valid_to" id="validTo" class="form-control">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="assignNotes" class="form-label fw-semibold">Notizen zur Zuweisung</label>
                        <input type="text" name="notes" id="assignNotes" class="form-control" placeholder="z. B. Hauptmieter laut Mietvertrag">
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-sm btn-primary fw-semibold">Zuweisung speichern</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ========================================================================= -->
<!-- MODAL: DOKUMENT FÜR EINHEIT VERKNÜPFEN                                    -->
<!-- ========================================================================= -->
<?php if ($canManageUnits): ?>
<div class="modal fade" id="linkUnitDocModal" tabindex="-1" aria-labelledby="linkUnitDocModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="?route=admin/portal/document/link">
                <?= Csrf::field() ?>
                <input type="hidden" name="property_id" value="<?= (int) $unit['property_id'] ?>">
                <input type="hidden" name="unit_id" value="<?= (int) $unit['id'] ?>">
                <input type="hidden" name="target_type" value="unit">
                <input type="hidden" name="target_id" value="<?= (int) $unit['id'] ?>">

                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark" id="linkUnitDocModalLabel">Dokument mit Einheit verknüpfen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="selectUnitDoc" class="form-label fw-semibold">Dokument aus Archiv wählen</label>
                        <select name="document_id" id="selectUnitDoc" class="form-select" required>
                            <option value="">-- Dokument wählen --</option>
                            <?php foreach ($allDmsDocuments as $ad): ?>
                                <option value="<?= (int) $ad['id'] ?>">
                                    <?= htmlspecialchars((string) $ad['title'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="unitRelRole" class="form-label fw-semibold">Dokument-Art / Rolle</label>
                        <select name="relation_role" id="unitRelRole" class="form-select">
                            <option value="plan">Grundriss / Aufmaß</option>
                            <option value="contract">Mietvertrag / Übergabeprotokoll</option>
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
