<?php

declare(strict_types=1);

/**
 * Eigentümer- & Mieterportal: Meine Dokumente
 *
 * @var array<int, array<string, mixed>> $documents
 * @var array<string, mixed> $filters
 */

$title = 'Meine Dokumente (Portal)';
$currentRoute = 'portal/documents';

ob_start();
?>

<!-- Breadcrumbs & Zurück -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="?route=portal/dashboard" class="text-decoration-none">Portal</a></li>
            <li class="breadcrumb-item active" aria-current="page">Meine Dokumente</li>
        </ol>
    </nav>
    <a href="?route=portal/dashboard" class="btn btn-sm btn-outline-secondary">
        &larr; Zurück zum Dashboard
    </a>
</div>

<!-- Kopfzeile -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Meine Dokumente &amp; Akten</h4>
        <p class="text-muted small mb-0">Einladungen, Protokolle, Teilungserklärungen, Abrechnungen und Pläne zu Ihren Liegenschaften.</p>
    </div>
</div>

<!-- Liste der Dokumente -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0 fw-semibold text-dark">
            Verfügbare Dokumente 
            <span class="badge bg-light text-muted border ms-1 fw-normal"><?= count($documents) ?> Dateien</span>
        </h6>
    </div>

    <div class="card-body p-0">
        <?php if (empty($documents)): ?>
            <div class="text-center py-5">
                <div class="p-3 bg-light rounded-circle d-inline-flex mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" fill="currentColor" class="bi bi-file-earmark-text text-muted" viewBox="0 0 16 16">
                        <path d="M5.5 7a.5.5 0 0 0 0 1h5a.5.5 0 0 0 0-1zM5 9.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5"/>
                        <path d="M9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.5zm0 1v3.5A1.5 1.5 0 0 0 11 6h3.5z"/>
                    </svg>
                </div>
                <h6 class="fw-semibold text-dark">Keine Dokumente gefunden</h6>
                <p class="text-muted small mb-0">Aktuell wurden noch keine Dokumente für Ihre Einheiten im Portal freigegeben.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Dokumenten-Titel</th>
                            <th>Zugeordnet zu</th>
                            <th>Typ</th>
                            <th>Datum</th>
                            <th>Dateigröße</th>
                            <th class="pe-3 text-end" style="width: 160px;">Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents as $doc): ?>
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars((string) $doc['title'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php if (!empty($doc['original_filename'])): ?>
                                        <div class="text-muted small" style="font-size: 0.72rem;"><?= htmlspecialchars((string) $doc['original_filename'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-secondary border">
                                        <?= htmlspecialchars((string) ($doc['target_label'] ?? 'Liegenschaft'), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars((string) ($doc['doc_type'] ?? 'MISC'), ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                                <td class="text-muted"><?= date('d.m.Y', strtotime((string) ($doc['document_date'] ?? $doc['created_at']))) ?></td>
                                <td class="text-muted"><?= Upload::formatBytes((int) ($doc['file_size'] ?? 0)) ?></td>
                                <td class="pe-3 text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="?route=admin/documents/download&id=<?= (int) $doc['id'] ?>&inline=1" target="_blank" class="btn btn-outline-primary" title="In neuem Tab ansehen">
                                            Vorschau
                                        </a>
                                        <a href="?route=admin/documents/download&id=<?= (int) $doc['id'] ?>&inline=0" class="btn btn-outline-secondary" title="Herunterladen">
                                            Herunterladen ↓
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
