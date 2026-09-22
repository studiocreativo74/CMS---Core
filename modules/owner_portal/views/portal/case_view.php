<?php

declare(strict_types=1);

/**
 * Eigentümer- & Mieterportal: Vorgangs- & Schadensansicht
 *
 * @var array<string, mixed> $case
 * @var array<int, array<string, mixed>> $messages
 * @var array<int, array<string, mixed>> $documents
 */

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$title = 'Vorgang: ' . htmlspecialchars((string) $case['title'], ENT_QUOTES, 'UTF-8');
$currentRoute = 'portal/cases';

$typeMeta = PortalService::CASE_TYPES[$case['case_type']] ?? PortalService::CASE_TYPES['property'];
$statusMeta = PortalService::CASE_STATUSES[$case['status']] ?? PortalService::CASE_STATUSES['new'];

ob_start();
?>

<!-- Breadcrumbs & Zurück -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="?route=portal/dashboard" class="text-decoration-none">Portal</a></li>
            <li class="breadcrumb-item"><a href="?route=portal/cases" class="text-decoration-none">Meine Vorgänge</a></li>
            <li class="breadcrumb-item active" aria-current="page">#<?= (int) $case['id'] ?></li>
        </ol>
    </nav>
    <a href="?route=portal/cases" class="btn btn-sm btn-outline-secondary">
        &larr; Zurück zur Übersicht
    </a>
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

<!-- Vorgangs-Details -->
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
                    <span class="text-muted small">Vorgangs-Nr. #<?= (int) $case['id'] ?></span>
                </div>

                <h3 class="fw-bold text-dark mb-1"><?= htmlspecialchars((string) $case['title'], ENT_QUOTES, 'UTF-8') ?></h3>

                <div class="row g-3 mt-1 text-muted small">
                    <div class="col-auto"><strong>Liegenschaft:</strong> <?= htmlspecialchars((string) $case['property_name'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php if (!empty($case['unit_number'])): ?>
                        <div class="col-auto"><strong>Einheit:</strong> WE <?= htmlspecialchars((string) $case['unit_number'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                    <?php if (!empty($case['damage_location'])): ?>
                        <div class="col-auto"><strong>Ort des Schadens:</strong> <?= htmlspecialchars((string) $case['damage_location'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                    <div class="col-auto"><strong>Gemeldet am:</strong> <?= date('d.m.Y H:i', strtotime((string) $case['created_at'])) ?></div>
                </div>

                <?php if (!empty($case['description'])): ?>
                    <div class="mt-3 p-3 bg-light rounded text-dark small">
                        <strong>Beschreibung:</strong><br>
                        <?= nl2br(htmlspecialchars((string) $case['description'], ENT_QUOTES, 'UTF-8')) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Nachrichten- & Kommunikationshistorie -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-semibold text-dark">
                    Nachrichten &amp; Statusverlauf
                </h6>
            </div>
            <div class="card-body p-4">
                <?php if (empty($messages)): ?>
                    <p class="text-muted small text-center my-3">Noch keine Nachrichten in diesem Vorgang.</p>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3 mb-4">
                        <?php foreach ($messages as $msg): ?>
                            <div class="p-3 rounded bg-light border">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-bold text-dark small"><?= htmlspecialchars((string) ($msg['user_name'] ?? 'Hausverwaltung'), ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="text-muted" style="font-size: 0.75rem;"><?= date('d.m.Y H:i', strtotime((string) $msg['created_at'])) ?></span>
                                </div>
                                <div class="small text-secondary mb-0">
                                    <?= nl2br(htmlspecialchars((string) $msg['message'], ENT_QUOTES, 'UTF-8')) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Antwort senden -->
                <div class="border-top pt-3">
                    <form method="POST" action="?route=portal/case/message">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="case_id" value="<?= (int) $case['id'] ?>">

                        <div class="mb-3">
                            <label for="userReply" class="form-label fw-semibold small">Nachricht an die Hausverwaltung verfassen</label>
                            <textarea name="message" id="userReply" rows="3" class="form-control form-control-sm" placeholder="Geben Sie hier Rückfragen, Terminwünsche oder Ergänzungen ein..." required></textarea>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-sm btn-primary fw-semibold px-4">
                                Antwort absenden
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Angehängte Dateien & Fotos -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-semibold text-dark">
                    Fotos &amp; Unterlagen (<?= count($documents) ?>)
                </h6>
            </div>
            <div class="card-body p-3">
                <?php if (empty($documents)): ?>
                    <p class="text-muted small text-center mb-0">Keine Dateien hinterlegt.</p>
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

<?php
$content = ob_get_clean();
require __DIR__ . '/../../../../views/layouts/admin.php';
