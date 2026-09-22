<?php

declare(strict_types=1);

/**
 * DMS - Detailansicht, Vorschau, Versionen & Relationen
 *
 * @var array<string, mixed> $document
 * @var array<int, array<string, mixed>> $versions
 * @var array<int, array<string, mixed>> $tags
 * @var array<int, array<string, mixed>> $allTags
 * @var array<int, array<string, mixed>> $relations
 * @var array<int, array<string, mixed>> $activityLogs
 */

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$title = htmlspecialchars((string) $document['title'], ENT_QUOTES, 'UTF-8');
$currentRoute = 'admin/documents';

$canManage = DocumentService::canManage();

$typeInfo = DocumentService::getTypeInfo((string) $document['doc_type']);
$isPdf = str_contains((string) ($document['mime_type'] ?? ''), 'pdf');
$isImage = str_starts_with((string) ($document['mime_type'] ?? ''), 'image/');

ob_start();
?>

<!-- Breadcrumb & Kopfzeile -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="?route=admin/documents" class="text-decoration-none">Dokumente</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars((string) $document['title'], ENT_QUOTES, 'UTF-8') ?></li>
        </ol>
    </nav>
    <div class="d-flex align-items-center gap-2">
        <a href="?route=admin/documents" class="btn btn-sm btn-outline-secondary">
            &larr; Zurück zur Übersicht
        </a>
        <a href="?route=admin/documents/download&id=<?= (int) $document['id'] ?>&inline=0" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-download" viewBox="0 0 16 16">
                <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5"/>
                <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/>
            </svg>
            Herunterladen
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

<!-- Titel- & Aktionsleiste -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                    <span class="badge rounded-pill px-3 py-1" style="background-color: <?= htmlspecialchars($typeInfo['color'], ENT_QUOTES, 'UTF-8') ?>20; color: <?= htmlspecialchars($typeInfo['color'], ENT_QUOTES, 'UTF-8') ?>; border: 1px solid <?= htmlspecialchars($typeInfo['color'], ENT_QUOTES, 'UTF-8') ?>50;">
                        <?= htmlspecialchars($typeInfo['label'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <span class="badge bg-light text-dark border">Version v<?= (int) ($document['latest_version'] ?? 1) ?></span>
                    <?php if (($document['status'] ?? 'active') === 'archived'): ?>
                        <span class="badge bg-secondary">Archiviert</span>
                    <?php else: ?>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Aktiv</span>
                    <?php endif; ?>

                    <?php 
                    $vis = (string) ($document['visibility'] ?? 'internal');
                    if ($vis === 'owner_portal'):
                    ?>
                        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">Eigentümerportal</span>
                    <?php elseif ($vis === 'public'): ?>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Öffentlich</span>
                    <?php endif; ?>
                </div>

                <h3 class="fw-bold text-dark mb-1"><?= htmlspecialchars((string) $document['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                
                <div class="text-muted small">
                    <?php if (!empty($document['reference_number'])): ?>
                        <span class="fw-semibold text-secondary me-3">Aktenzeichen: <?= htmlspecialchars((string) $document['reference_number'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                    <span>Datei: <code><?= htmlspecialchars((string) ($document['original_filename'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code></span>
                    <span class="ms-3">Größe: <?= Upload::formatBytes((int) ($document['file_size'] ?? 0)) ?></span>
                </div>
            </div>

            <?php if ($canManage): ?>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#editMetadataModal">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-pencil" viewBox="0 0 16 16">
                            <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325"/>
                        </svg>
                        Metadaten bearbeiten
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#addVersionModal">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-plus-circle" viewBox="0 0 16 16">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                            <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4"/>
                        </svg>
                        Neue Version (v<?= ((int) ($document['latest_version'] ?? 1)) + 1 ?>)
                    </button>
                    <form method="POST" action="?route=admin/documents/delete" onsubmit="return confirm('Möchten Sie dieses Dokument wirklich archivieren?');" class="d-inline">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="id" value="<?= (int) $document['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Archivieren">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-archive" viewBox="0 0 16 16">
                                <path d="M0 2a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1v7.5a2.5 2.5 0 0 1-2.5 2.5h-9A2.5 2.5 0 0 1 1 12.5V5a1 1 0 0 1-1-1zm2 3v7.5A1.5 1.5 0 0 0 3.5 14h9a1.5 1.5 0 0 0 1.5-1.5V5zm13-3H1v2h14zM5 7.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5"/>
                            </svg>
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Hauptinhalt: Split-Layout Vorschau & Metadaten -->
<div class="row g-4 mb-4">
    <!-- Linke Spalte: Dokument-Vorschau -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0 fw-semibold text-dark d-flex align-items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye text-primary" viewBox="0 0 16 16">
                        <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/>
                        <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/>
                    </svg>
                    Dokumentenvorschau
                </h6>
                <a href="?route=admin/documents/download&id=<?= (int) $document['id'] ?>&inline=1" target="_blank" class="btn btn-sm btn-light text-primary">
                    Im neuen Fenster öffnen &nearr;
                </a>
            </div>
            <div class="card-body p-0 d-flex flex-column" style="min-height: 520px;">
                <?php if ($isPdf): ?>
                    <iframe src="?route=admin/documents/download&id=<?= (int) $document['id'] ?>&inline=1" 
                            style="width: 100%; height: 600px; border: none;" 
                            title="PDF-Vorschau">
                    </iframe>
                <?php elseif ($isImage): ?>
                    <div class="p-3 text-center bg-light flex-grow-1 d-flex align-items-center justify-content-center">
                        <img src="?route=admin/documents/download&id=<?= (int) $document['id'] ?>&inline=1" 
                             alt="<?= htmlspecialchars((string) $document['title'], ENT_QUOTES, 'UTF-8') ?>" 
                             class="img-fluid rounded shadow-sm" 
                             style="max-height: 540px; object-fit: contain;">
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 m-auto p-4">
                        <div class="p-4 bg-light rounded-circle d-inline-flex mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" class="bi bi-file-earmark-arrow-down text-primary" viewBox="0 0 16 16">
                                <path d="M8.5 6.5a.5.5 0 0 0-1 0v3.793L6.354 9.146a.5.5 0 1 0-.708.708l2 2a.5.5 0 0 0 .708 0l2-2a.5.5 0 0 0-.708-.708L8.5 10.293z"/>
                                <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2M9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5z"/>
                            </svg>
                        </div>
                        <h6 class="fw-bold text-dark">Vorschau für dieses Format nicht direkt im Browser möglich</h6>
                        <p class="text-muted small mb-3">Dateityp: <code><?= htmlspecialchars((string) ($document['mime_type'] ?? 'application/octet-stream'), ENT_QUOTES, 'UTF-8') ?></code></p>
                        <a href="?route=admin/documents/download&id=<?= (int) $document['id'] ?>&inline=0" class="btn btn-primary btn-sm">
                            Datei herunterladen (<?= Upload::formatBytes((int) ($document['file_size'] ?? 0)) ?>)
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-light py-2 px-3 small text-muted d-flex justify-content-between">
                <span>Integrität (SHA-256): <code class="user-select-all" style="font-size: 0.72rem;"><?= htmlspecialchars((string) ($document['file_hash'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code></span>
            </div>
        </div>
    </div>

    <!-- Rechte Spalte: Metadaten, Schlagworte & Portal-Verknüpfungen -->
    <div class="col-lg-5">
        <!-- 1. Metadaten-Karte -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-semibold text-dark">Stammdaten &amp; Attribute</h6>
            </div>
            <div class="card-body p-3">
                <table class="table table-sm table-borderless small mb-0">
                    <tbody>
                        <tr>
                            <td class="text-muted" style="width: 140px;">Dokumententyp:</td>
                            <td class="fw-medium text-dark"><?= htmlspecialchars($typeInfo['label'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Aktenzeichen:</td>
                            <td class="fw-medium text-dark"><?= !empty($document['reference_number']) ? htmlspecialchars((string) $document['reference_number'], ENT_QUOTES, 'UTF-8') : '<span class="text-muted">—</span>' ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Dokumentdatum:</td>
                            <td class="fw-medium text-dark">
                                <?= !empty($document['document_date']) ? date('d.m.Y', strtotime((string) $document['document_date'])) : '<span class="text-muted">—</span>' ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Gültigkeitszeitraum:</td>
                            <td class="fw-medium text-dark">
                                <?php if (!empty($document['valid_from']) || !empty($document['valid_until'])): ?>
                                    <?= !empty($document['valid_from']) ? date('d.m.Y', strtotime((string) $document['valid_from'])) : 'unbefristet' ?>
                                    bis 
                                    <?= !empty($document['valid_until']) ? date('d.m.Y', strtotime((string) $document['valid_until'])) : 'unbefristet' ?>
                                <?php else: ?>
                                    <span class="text-muted">Nicht befristet</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Sichtbarkeit:</td>
                            <td class="fw-medium text-dark">
                                <?= htmlspecialchars(DocumentService::VISIBILITIES[$document['visibility'] ?? 'internal'] ?? 'Intern', ENT_QUOTES, 'UTF-8') ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Erstellt am:</td>
                            <td class="text-muted"><?= date('d.m.Y H:i', strtotime((string) $document['created_at'])) ?> Uhr</td>
                        </tr>
                        <?php if (!empty($document['author_name'])): ?>
                            <tr>
                                <td class="text-muted">Erstellt von:</td>
                                <td class="text-muted"><?= htmlspecialchars((string) $document['author_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php if (!empty($document['description'])): ?>
                    <hr class="my-2">
                    <label class="text-muted small fw-semibold d-block mb-1">Beschreibung / Notizen:</label>
                    <p class="small text-secondary mb-0 bg-light p-2 rounded"><?= nl2br(htmlspecialchars((string) $document['description'], ENT_QUOTES, 'UTF-8')) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- 2. Schlagworte (Tags) -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0 fw-semibold text-dark">Schlagworte</h6>
                <?php if ($canManage): ?>
                    <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none small" data-bs-toggle="modal" data-bs-target="#editMetadataModal">Bearbeiten</button>
                <?php endif; ?>
            </div>
            <div class="card-body p-3">
                <?php if (!empty($document['tags'])): ?>
                    <div class="d-flex flex-wrap gap-1">
                        <?php foreach ($document['tags'] as $t): ?>
                            <span class="badge rounded-pill px-2 py-1" style="background-color: <?= htmlspecialchars((string) ($t['color'] ?? '#6c757d'), ENT_QUOTES, 'UTF-8') ?>; color: #fff;">
                                # <?= htmlspecialchars((string) $t['name'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted small mb-0">Diesem Dokument sind keine Schlagworte zugewiesen.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- 3. Eigentümerportal-Verknüpfungen (Properties, Units, Cases) -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0 fw-semibold text-dark d-flex align-items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-link-45deg text-info" viewBox="0 0 16 16">
                        <path d="M4.715 6.542 3.343 7.914a3 3 0 1 0 4.243 4.243l1.828-1.829A3 3 0 0 0 8.586 5.5L8 6.086a1 1 0 0 0-.154.199 2 2 0 0 1 .861 3.337L6.88 11.45a2 2 0 1 1-2.83-2.83l.793-.792a4 4 0 0 1-.128-1.287z"/>
                        <path d="M6.586 4.672A3 3 0 0 0 7.414 9.5l.775-.776a2 2 0 0 1-.896-3.346L9.12 3.55a2 2 0 1 1 2.83 2.83l-.793.792c.112.42.155.855.128 1.287l1.372-1.372a3 3 0 0 0-4.243-4.243z"/>
                    </svg>
                    Verknüpfte Objekte (Eigentümerportal)
                </h6>
            </div>
            <div class="card-body p-3">
                <?php if (empty($document['relations'])): ?>
                    <p class="text-muted small mb-3">Keine Verknüpfung zu Liegenschaften, Einheiten oder Vorgängen vorhanden.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush small mb-3">
                        <?php foreach ($document['relations'] as $rel): 
                            $targetTypeName = match ((string) $rel['target_type']) {
                                'property' => 'Liegenschaft',
                                'unit'     => 'Einheit',
                                'case'     => 'Vorgang / Case',
                                'contact'  => 'Kontakt',
                                default    => ucfirst((string) $rel['target_type']),
                            };
                        ?>
                            <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-light text-dark border me-1"><?= htmlspecialchars($targetTypeName, ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="fw-semibold text-dark"><?= htmlspecialchars((string) ($rel['target_label'] ?: '#' . $rel['target_id']), ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="text-muted small ms-1">(Rolle: <?= htmlspecialchars((string) $rel['relation_role'], ENT_QUOTES, 'UTF-8') ?>)</span>
                                </div>
                                <?php if ($canManage): ?>
                                    <form method="POST" action="?route=admin/documents/relation/delete" onsubmit="return confirm('Verknüpfung aufheben?');" class="d-inline">
                                        <?= Csrf::field() ?>
                                        <input type="hidden" name="document_id" value="<?= (int) $document['id'] ?>">
                                        <input type="hidden" name="relation_id" value="<?= (int) $rel['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-link text-danger p-0 text-decoration-none">✕</button>
                                    </form>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <!-- Formular: Objekt verknüpfen -->
                <?php if ($canManage): ?>
                    <form method="POST" action="?route=admin/documents/relation/add" class="row g-2 pt-2 border-top">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="document_id" value="<?= (int) $document['id'] ?>">
                        <div class="col-6">
                            <select name="target_type" class="form-select form-select-sm" required>
                                <option value="property">Liegenschaft</option>
                                <option value="unit">Einheit / Whg.</option>
                                <option value="case">Vorgang / Case</option>
                                <option value="contact">Kontakt</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <input type="text" name="target_id" class="form-control form-control-sm" placeholder="ID (z. B. 101)" required>
                        </div>
                        <div class="col-8">
                            <input type="text" name="target_label" class="form-control form-control-sm" placeholder="Bezeichnung (z. B. WEG Parkallee 5)">
                        </div>
                        <div class="col-4">
                            <button type="submit" class="btn btn-sm btn-outline-secondary w-100">+ Verknüpfen</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Unterer Bereich: Versionshistorie & Audit-Trail -->
<div class="row g-4 mb-4">
    <!-- Versionshistorie -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0 fw-semibold text-dark d-flex align-items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clock-history text-success" viewBox="0 0 16 16">
                        <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/>
                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                    </svg>
                    Versionshistorie (<?= count($versions) ?>)
                </h6>
                <?php if ($canManage): ?>
                    <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#addVersionModal">
                        + Version hochladen
                    </button>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Version</th>
                                <th>Dateiname</th>
                                <th>Größe</th>
                                <th>Änderungsnotiz</th>
                                <th>Hochgeladen am</th>
                                <th class="pe-3 text-end">Aktion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($versions as $v): 
                                $isLatest = ((int) $v['version_number'] === (int) ($document['latest_version'] ?? 1));
                            ?>
                                <tr class="<?= $isLatest ? 'table-light bg-opacity-50' : '' ?>">
                                    <td class="ps-3">
                                        <span class="badge <?= $isLatest ? 'bg-primary' : 'bg-secondary' ?>">
                                            v<?= (int) $v['version_number'] ?>
                                        </span>
                                        <?php if ($isLatest): ?>
                                            <span class="badge bg-success bg-opacity-25 text-success ms-1">Aktuell</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-medium text-dark"><?= htmlspecialchars((string) $v['original_filename'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="text-muted small" style="font-size: 0.72rem;"><?= htmlspecialchars((string) $v['mime_type'], ENT_QUOTES, 'UTF-8') ?></div>
                                    </td>
                                    <td class="text-muted">
                                        <?= Upload::formatBytes((int) ($v['file_size'] ?? 0)) ?>
                                    </td>
                                    <td>
                                        <span class="text-secondary"><?= htmlspecialchars((string) ($v['change_notes'] ?: '—'), ENT_QUOTES, 'UTF-8') ?></span>
                                    </td>
                                    <td>
                                        <div><?= date('d.m.Y H:i', strtotime((string) $v['created_at'])) ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars((string) ($v['uploader_name'] ?? 'System'), ENT_QUOTES, 'UTF-8') ?></div>
                                    </td>
                                    <td class="pe-3 text-end">
                                        <a href="?route=admin/documents/download&id=<?= (int) $document['id'] ?>&version=<?= (int) $v['version_number'] ?>&inline=0" class="btn btn-sm btn-outline-secondary" title="Diese Version herunterladen">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="bi bi-download" viewBox="0 0 16 16">
                                                <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5"/>
                                                <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/>
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Audit Trail / Aktivitätsprotokoll -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-semibold text-dark">Audit-Trail / Protokoll</h6>
            </div>
            <div class="card-body p-3">
                <?php if (empty($activityLogs)): ?>
                    <p class="text-muted small mb-0">Keine Protokolleinträge vorhanden.</p>
                <?php else: ?>
                    <div class="timeline small">
                        <?php foreach ($activityLogs as $log): 
                            $actionLabel = match ((string) $log['action']) {
                                'create'      => 'Dokument erstellt',
                                'version_add' => 'Neue Version hinzugefügt',
                                'update'      => 'Metadaten aktualisiert',
                                'download'    => 'Heruntergeladen',
                                'view'        => 'Eingesehen',
                                'archive'     => 'Archiviert',
                                default       => ucfirst((string) $log['action']),
                            };
                        ?>
                            <div class="mb-3 border-bottom pb-2">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-semibold text-dark"><?= htmlspecialchars($actionLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="text-muted" style="font-size: 0.72rem;"><?= date('d.m.Y H:i', strtotime((string) $log['created_at'])) ?></span>
                                </div>
                                <div class="text-muted" style="font-size: 0.75rem;">
                                    User: <?= htmlspecialchars((string) ($log['user_name'] ?? 'Magic-Admin / System'), ENT_QUOTES, 'UTF-8') ?>
                                    <?php if (!empty($log['ip_address'])): ?>
                                        (<?= htmlspecialchars((string) $log['ip_address'], ENT_QUOTES, 'UTF-8') ?>)
                                    <?php endif; ?>
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
<!-- MODAL: NEUE VERSION HOCHLADEN                                             -->
<!-- ========================================================================= -->
<?php if ($canManage): ?>
<div class="modal fade" id="addVersionModal" tabindex="-1" aria-labelledby="addVersionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="?route=admin/documents/upload-version" enctype="multipart/form-data">
                <?= Csrf::field() ?>
                <input type="hidden" name="document_id" value="<?= (int) $document['id'] ?>">

                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark" id="addVersionModalLabel">Neue Version hochladen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Neue Dokumentdatei <span class="text-danger">*</span></label>
                        <input type="file" name="document_file" class="form-control" required accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.ods,.txt,.csv,.png,.jpg,.jpeg,.webp,.tiff">
                        <span class="text-muted small mt-1 d-block">Erzeugt automatisch Version v<?= ((int) ($document['latest_version'] ?? 1)) + 1 ?>.</span>
                    </div>

                    <div class="mb-3">
                        <label for="changeNotes" class="form-label fw-semibold">Änderungskommentar / Changelog</label>
                        <input type="text" name="change_notes" id="changeNotes" class="form-control" placeholder="z. B. Korrigierte Fassung mit Unterschrift">
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-sm btn-success fw-semibold">Version v<?= ((int) ($document['latest_version'] ?? 1)) + 1 ?> speichern</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: METADATEN BEARBEITEN                                               -->
<!-- ========================================================================= -->
<div class="modal fade" id="editMetadataModal" tabindex="-1" aria-labelledby="editMetadataModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="?route=admin/documents/edit">
                <?= Csrf::field() ?>
                <input type="hidden" name="id" value="<?= (int) $document['id'] ?>">

                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark" id="editMetadataModalLabel">Metadaten bearbeiten</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label for="editTitle" class="form-label fw-semibold">Titel <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="editTitle" class="form-control" value="<?= htmlspecialchars((string) $document['title'], ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="editDocType" class="form-label fw-semibold">Dokumententyp <span class="text-danger">*</span></label>
                            <select name="doc_type" id="editDocType" class="form-select" required>
                                <?php foreach (DocumentService::DOC_TYPES as $k => $info): ?>
                                    <option value="<?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?>" <?= (($document['doc_type'] ?? '') === $k) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($info['label'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="editRef" class="form-label fw-semibold">Aktenzeichen / Rechnungs-Nr.</label>
                            <input type="text" name="reference_number" id="editRef" class="form-control" value="<?= htmlspecialchars((string) ($document['reference_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="editDate" class="form-label fw-semibold">Dokumentdatum</label>
                            <input type="date" name="document_date" id="editDate" class="form-control" value="<?= htmlspecialchars((string) ($document['document_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="editVisibility" class="form-label fw-semibold">Sichtbarkeit (Portal)</label>
                            <select name="visibility" id="editVisibility" class="form-select">
                                <?php foreach (DocumentService::VISIBILITIES as $vk => $vl): ?>
                                    <option value="<?= htmlspecialchars($vk, ENT_QUOTES, 'UTF-8') ?>" <?= (($document['visibility'] ?? '') === $vk) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($vl, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="editStatus" class="form-label fw-semibold">Status</label>
                            <select name="status" id="editStatus" class="form-select">
                                <option value="active" <?= (($document['status'] ?? '') === 'active') ? 'selected' : '' ?>>Aktiv</option>
                                <option value="archived" <?= (($document['status'] ?? '') === 'archived') ? 'selected' : '' ?>>Archiviert</option>
                                <option value="draft" <?= (($document['status'] ?? '') === 'draft') ? 'selected' : '' ?>>Entwurf</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="editValidFrom" class="form-label text-muted small">Gültig ab</label>
                            <input type="date" name="valid_from" id="editValidFrom" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($document['valid_from'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="editValidUntil" class="form-label text-muted small">Gültig bis</label>
                            <input type="date" name="valid_until" id="editValidUntil" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($document['valid_until'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="editDesc" class="form-label fw-semibold">Beschreibung / Vermerk</label>
                        <textarea name="description" id="editDesc" rows="3" class="form-control"><?= htmlspecialchars((string) ($document['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <!-- Tags -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Schlagworte</label>
                        <?php 
                        $assignedTagIds = array_column($document['tags'] ?? [], 'id');
                        ?>
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <?php foreach ($allTags as $at): 
                                $checked = in_array((int) $at['id'], $assignedTagIds, true);
                            ?>
                                <div class="form-check form-check-inline m-0">
                                    <input class="form-check-input" type="checkbox" name="tags[]" value="<?= (int) $at['id'] ?>" id="edit_tag_<?= (int) $at['id'] ?>" <?= $checked ? 'checked' : '' ?>>
                                    <label class="form-check-label badge rounded-pill" for="edit_tag_<?= (int) $at['id'] ?>" style="background-color: <?= htmlspecialchars((string) ($at['color'] ?? '#6c757d'), ENT_QUOTES, 'UTF-8') ?>; color: #fff;">
                                        <?= htmlspecialchars((string) $at['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-sm btn-primary fw-semibold">Änderungen speichern</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../../views/layouts/admin.php';
