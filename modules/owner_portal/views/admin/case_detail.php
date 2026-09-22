<?php

declare(strict_types=1);

/**
 * Admin: Vorgangs-Detailansicht (Kommunikation, Status & Dokumente)
 *
 * @var array<string, mixed> $case
 * @var array<int, array<string, mixed>> $messages
 * @var array<int, array<string, mixed>> $documents
 * @var array<int, array<string, mixed>> $allDmsDocuments
 */

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$title = 'Vorgang #' . (int) $case['id'] . ': ' . htmlspecialchars((string) $case['title'], ENT_QUOTES, 'UTF-8');
$currentRoute = 'admin/portal/cases';

$typeMeta = PortalService::CASE_TYPES[$case['case_type']] ?? PortalService::CASE_TYPES['property'];
$statusMeta = PortalService::CASE_STATUSES[$case['status']] ?? PortalService::CASE_STATUSES['new'];
$prioMeta = PortalService::PRIORITIES[$case['priority']] ?? PortalService::PRIORITIES['normal'];
$canManageCases = PortalService::canManageCases();

ob_start();
?>

<!-- Breadcrumbs & Zurück -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="?route=admin/portal/cases" class="text-decoration-none">Vorgänge</a></li>
            <li class="breadcrumb-item"><a href="?route=admin/portal/property&id=<?= (int) $case['property_id'] ?>" class="text-decoration-none"><?= htmlspecialchars((string) $case['property_name'], ENT_QUOTES, 'UTF-8') ?></a></li>
            <li class="breadcrumb-item active" aria-current="page">#<?= (int) $case['id'] ?></li>
        </ol>
    </nav>
    <div class="d-flex align-items-center gap-2">
        <a href="?route=admin/portal/cases" class="btn btn-sm btn-outline-secondary">
            &larr; Zurück zu allen Vorgängen
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

<!-- Vorgangs-Kopf -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                    <span class="badge rounded-pill px-2 py-1" style="background-color: <?= $typeMeta['color'] ?>15; color: <?= $typeMeta['color'] ?>; border: 1px solid <?= $typeMeta['color'] ?>30;">
                        <?= htmlspecialchars($typeMeta['label'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <span class="badge <?= $statusMeta['badge'] ?> rounded-pill fw-normal px-2 py-1">
                        <?= htmlspecialchars($statusMeta['label'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <span class="badge <?= $prioMeta['badge'] ?> fw-normal">
                        Priorität: <?= htmlspecialchars($prioMeta['label'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <span class="text-muted small">#<?= (int) $case['id'] ?></span>
                </div>

                <h3 class="fw-bold text-dark mb-1"><?= htmlspecialchars((string) $case['title'], ENT_QUOTES, 'UTF-8') ?></h3>

                <div class="row g-3 mt-1 text-muted small">
                    <div class="col-auto">
                        <strong>Liegenschaft:</strong> 
                        <a href="?route=admin/portal/property&id=<?= (int) $case['property_id'] ?>" class="text-decoration-none">
                            <?= htmlspecialchars((string) $case['property_name'], ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </div>
                    <?php if (!empty($case['unit_number'])): ?>
                        <div class="col-auto"><strong>Einheit:</strong> <?= htmlspecialchars((string) $case['unit_number'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                    <?php if (!empty($case['damage_location'])): ?>
                        <div class="col-auto"><strong>Schadenort:</strong> <?= htmlspecialchars((string) $case['damage_location'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                    <div class="col-auto"><strong>Erstellt am:</strong> <?= date('d.m.Y H:i', strtotime((string) $case['created_at'])) ?></div>
                    <?php if (!empty($case['creator_name'])): ?>
                        <div class="col-auto"><strong>Erfasser:</strong> <?= htmlspecialchars((string) $case['creator_name'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($case['description'])): ?>
                    <div class="mt-3 p-3 bg-light rounded text-dark small">
                        <strong>Beschreibung:</strong><br>
                        <?= nl2br(htmlspecialchars((string) $case['description'], ENT_QUOTES, 'UTF-8')) ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Status- & Bearbeitungsformular -->
            <?php if ($canManageCases): ?>
                <div class="bg-light p-3 rounded border" style="min-width: 260px;">
                    <h6 class="fw-bold text-dark mb-2 small">Status &amp; Zuweisung</h6>
                    <form method="POST" action="?route=admin/portal/case/update-status">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="id" value="<?= (int) $case['id'] ?>">

                        <div class="mb-2">
                            <label class="form-label small mb-1 fw-semibold">Status anpassen</label>
                            <select name="status" class="form-select form-select-sm">
                                <?php foreach (PortalService::CASE_STATUSES as $sk => $sinfo): ?>
                                    <option value="<?= htmlspecialchars($sk, ENT_QUOTES, 'UTF-8') ?>" <?= $case['status'] === $sk ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($sinfo['label'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small mb-1 fw-semibold">Priorität</label>
                            <select name="priority" class="form-select form-select-sm">
                                <?php foreach (PortalService::PRIORITIES as $pk => $pinfo): ?>
                                    <option value="<?= htmlspecialchars($pk, ENT_QUOTES, 'UTF-8') ?>" <?= $case['priority'] === $pk ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($pinfo['label'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-sm btn-primary w-100 fw-semibold">
                            Aktualisieren
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Linke Spalte: Nachrichten- & Kommunikationshistorie -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0 fw-semibold text-dark d-flex align-items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chat-left-text text-primary" viewBox="0 0 16 16">
                        <path d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H4.414A2 2 0 0 0 3 11.586l-2 2V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v12.793a.5.5 0 0 0 .854.353l2.853-2.853A1 1 0 0 1 4.414 12H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z"/>
                        <path d="M3 3.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5M3 6a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9A.5.5 0 0 1 3 6m0 2.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5"/>
                    </svg>
                    Nachrichten- &amp; Statusverlauf (<?= count($messages) ?>)
                </h6>
            </div>

            <div class="card-body p-4">
                <?php if (empty($messages)): ?>
                    <p class="text-muted small text-center my-3">Noch keine Nachrichten oder Verlaufsnotizen in diesem Vorgang.</p>
                <?php else: ?>
                    <div class="timeline d-flex flex-column gap-3 mb-4">
                        <?php foreach ($messages as $msg): 
                            $isInternal = ((int) ($msg['is_internal'] ?? 0)) === 1;
                        ?>
                            <div class="p-3 rounded <?= $isInternal ? 'bg-warning bg-opacity-10 border border-warning border-opacity-25' : 'bg-light border' ?>">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-bold text-dark small"><?= htmlspecialchars((string) ($msg['user_name'] ?? 'System / Portal'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php if ($isInternal): ?>
                                            <span class="badge bg-warning text-dark border border-warning" style="font-size: 0.68rem;">Nur Intern (Verwaltung)</span>
                                        <?php else: ?>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25" style="font-size: 0.68rem;">Sichtbar im Portal</span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="text-muted" style="font-size: 0.75rem;"><?= date('d.m.Y H:i', strtotime((string) $msg['created_at'])) ?></span>
                                </div>
                                <div class="small text-secondary mb-0">
                                    <?= nl2br(htmlspecialchars((string) $msg['message'], ENT_QUOTES, 'UTF-8')) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Antwort- / Mitteilungsformular -->
                <div class="border-top pt-3">
                    <form method="POST" action="?route=admin/portal/case/message">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="case_id" value="<?= (int) $case['id'] ?>">

                        <div class="mb-3">
                            <label for="newMessage" class="form-label fw-semibold small">Neue Nachricht oder interne Notiz hinzufügen</label>
                            <textarea name="message" id="newMessage" rows="3" class="form-control form-control-sm" placeholder="Nachricht an den Ersteller verfassen oder internen Vermerk hinterlegen..." required></textarea>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_internal" value="1" id="checkInternal">
                                <label class="form-check-label small text-muted" for="checkInternal">
                                    Als <strong>interne Notiz</strong> speichern (nicht für Eigentümer/Mieter sichtbar)
                                </label>
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary fw-semibold px-3">
                                Nachricht absenden
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Rechte Spalte: Angehängte Fotos & Dokumente aus DMS -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0 fw-semibold text-dark d-flex align-items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-paperclip text-danger" viewBox="0 0 16 16">
                        <path d="M4.5 3a2.5 2.5 0 0 1 5 0v9a1.5 1.5 0 0 1-3 0V5a.5.5 0 0 1 1 0v7a.5.5 0 0 0 1 0V3a1.5 1.5 0 1 0-3 0v9a2.5 2.5 0 0 0 5 0V5a.5.5 0 0 1 1 0v7a3.5 3.5 0 1 1-7 0z"/>
                    </svg>
                    Dokumente &amp; Fotos (<?= count($documents) ?>)
                </h6>
                <?php if ($canManageCases): ?>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#linkCaseDocModal">
                        + Anhängen
                    </button>
                <?php endif; ?>
            </div>
            <div class="card-body p-3">
                <?php if (empty($documents)): ?>
                    <p class="text-muted small text-center mb-0">Keine Dateien oder Schadensfotos angehängt.</p>
                <?php else: ?>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($documents as $doc): ?>
                            <div class="p-2 border rounded bg-light d-flex justify-content-between align-items-center">
                                <div class="overflow-hidden me-2">
                                    <div class="fw-semibold text-dark text-truncate small" title="<?= htmlspecialchars((string) $doc['title'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars((string) $doc['title'], ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <div class="text-muted small" style="font-size: 0.72rem;">
                                        <?= Upload::formatBytes((int) ($doc['file_size'] ?? 0)) ?>
                                    </div>
                                </div>
                                <div class="btn-group btn-group-sm flex-shrink-0">
                                    <a href="?route=admin/documents/download&id=<?= (int) $doc['id'] ?>&inline=1" target="_blank" class="btn btn-outline-secondary" title="Vorschau">
                                        Vorschau
                                    </a>
                                    <a href="?route=admin/documents/download&id=<?= (int) $doc['id'] ?>&inline=0" class="btn btn-outline-secondary" title="Download">
                                        ↓
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: DOKUMENT MIT CASE VERKNÜPFEN                                       -->
<!-- ========================================================================= -->
<?php if ($canManageCases): ?>
<div class="modal fade" id="linkCaseDocModal" tabindex="-1" aria-labelledby="linkCaseDocModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="?route=admin/portal/document/link">
                <?= Csrf::field() ?>
                <input type="hidden" name="property_id" value="<?= (int) $case['property_id'] ?>">
                <input type="hidden" name="case_id" value="<?= (int) $case['id'] ?>">
                <input type="hidden" name="target_type" value="case">
                <input type="hidden" name="target_id" value="<?= (int) $case['id'] ?>">

                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark" id="linkCaseDocModalLabel">Dokument an Vorgang anhängen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="selectCaseDoc" class="form-label fw-semibold">Dokument aus DMS wählen</label>
                        <select name="document_id" id="selectCaseDoc" class="form-select" required>
                            <option value="">-- Dokument wählen --</option>
                            <?php foreach ($allDmsDocuments as $ad): ?>
                                <option value="<?= (int) $ad['id'] ?>">
                                    <?= htmlspecialchars((string) $ad['title'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="caseRelRole" class="form-label fw-semibold">Rolle</label>
                        <select name="relation_role" id="caseRelRole" class="form-select">
                            <option value="photo">Schadensfoto / Dokumentation</option>
                            <option value="invoice">Kostenvoranschlag / Handwerkerrechnung</option>
                            <option value="protocol">Protokoll / Bericht</option>
                            <option value="attachment" selected>Allgemeine Anlage</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-sm btn-primary fw-semibold">Anhängen</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../../../views/layouts/admin.php';
