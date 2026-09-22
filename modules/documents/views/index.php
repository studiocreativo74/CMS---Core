<?php

declare(strict_types=1);

/**
 * DMS - Hauptübersicht & Archiv (Paperless-Style)
 *
 * @var array<string, mixed> $filters
 * @var array<int, array<string, mixed>> $documents
 * @var int $totalDocuments
 * @var int $currentPage
 * @var int $totalPages
 * @var array<int, array<string, mixed>> $tags
 * @var array<string, mixed> $stats
 * @var bool $tablesCreated
 */

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$title = 'Dokumente (DMS)';
$currentRoute = 'admin/documents';

$canManage = DocumentService::canManage();

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

<?php if (!$tablesCreated): ?>
    <div class="card border-warning border-start border-4 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex align-items-start gap-3">
                <div class="p-2 bg-warning bg-opacity-10 text-warning rounded flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor" class="bi bi-database-exclamation" viewBox="0 0 16 16">
                        <path d="M8 1c-1.573 0-3.022.289-4.096.777C2.823 2.269 2 2.99 2 4s.823 1.731 1.904 2.223C4.978 6.711 6.427 7 8 7s3.022-.289 4.096-.777C13.177 5.731 14 5.01 14 4s-.823-1.731-1.904-2.223C11.022 1.289 9.573 1 8 1"/>
                        <path d="M2 7v-.839c.457.432 1.004.751 1.49.972C4.601 7.649 6.222 8 8 8s3.399-.35 4.51-.867c.486-.22 1.033-.54 1.49-.972V7c0 .121-.012.237-.035.352a4 4 0 0 0-1.214-.464c-.787-.14-1.63-.223-2.541-.223-1.573 0-3.022.289-4.096.777C5.022 7.93 4.223 8.651 4.223 9.661V10c0 .121-.012.237-.035.352A4 4 0 0 0 2 10.352V10c0-.121.012-.237.035-.352A4 4 0 0 1 2 9.661V7z"/>
                        <path d="M16 12.5a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0m-3.5-2a.5.5 0 0 0-.5.5v1.5a.5.5 0 0 0 1 0V11a.5.5 0 0 0-.5-.5m0 4a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1"/>
                    </svg>
                </div>
                <div class="flex-grow-1">
                    <h5 class="fw-bold text-dark mb-1">DMS-Datenbanktabellen noch ausstehend</h5>
                    <p class="text-muted small mb-3">
                        Die Tabellen für Dokumente, Versionierung, Tags und Portal-Verknüpfungen (<code>005_create_documents_module_schema.sql</code>) wurden noch nicht in der Datenbank angelegt.
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="?route=admin/system" class="btn btn-sm btn-warning fw-semibold">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-arrow-right-circle me-1" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M1 8a7 7 0 1 0 14 0A7 7 0 0 0 1 8m15 0A8 8 0 1 1 0 8a8 8 0 0 1 16 0M4.5 7.5a.5.5 0 0 0 0 1h5.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3a.5.5 0 0 0 0-.708l-3-3a.5.5 0 1 0-.708.708L10.293 7.5z"/>
                            </svg>
                            Zu System &amp; Migrationen
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Dashboard Kennzahlen-Karten (Paperless-Style) -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100 p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Dokumente</span>
                    <h3 class="fw-bold text-dark mb-0 mt-1"><?= (int) ($stats['total_documents'] ?? 0) ?></h3>
                </div>
                <div class="p-2 bg-primary bg-opacity-10 text-primary rounded-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" class="bi bi-file-earmark-text" viewBox="0 0 16 16">
                        <path d="M5.5 7a.5.5 0 0 0 0 1h5a.5.5 0 0 0 0-1zM5 9.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5"/>
                        <path d="M9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.5zm0 1v3.5A1.5 1.5 0 0 0 11 6h3.5V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100 p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Aktiv im Archiv</span>
                    <h3 class="fw-bold text-success mb-0 mt-1"><?= (int) ($stats['active_documents'] ?? 0) ?></h3>
                </div>
                <div class="p-2 bg-success bg-opacity-10 text-success rounded-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" class="bi bi-check2-circle" viewBox="0 0 16 16">
                        <path d="M2.5 8a5.5 5.5 0 0 1 8.25-4.764.5.5 0 0 0 .5-.866A6.5 6.5 0 1 0 14.5 8a.5.5 0 0 0-1 0 5.5 5.5 0 1 1-11 0"/>
                        <path d="M15.354 3.354a.5.5 0 0 0-.708-.708L8 9.293 5.354 6.646a.5.5 0 1 0-.708.708l3 3a.5.5 0 0 0 .708 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100 p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Speicherbelegung</span>
                    <h3 class="fw-bold text-dark mb-0 mt-1"><?= Upload::formatBytes((int) ($stats['total_size_bytes'] ?? 0)) ?></h3>
                </div>
                <div class="p-2 bg-info bg-opacity-10 text-info rounded-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" class="bi bi-hdd-network" viewBox="0 0 16 16">
                        <path d="M4.5 5a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1M3 4.5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0m2 7a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0m-2.5.5a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1"/>
                        <path d="M2 2a2 2 0 0 0-2 2v1a2 2 0 0 0 2 2h5.5v3A1.5 1.5 0 0 0 6 11.5H5a.5.5 0 0 0 0 1h1a.5.5 0 0 1 .5.5.5.5 0 0 0 .5.5h2a.5.5 0 0 0 .5-.5.5.5 0 0 1 .5-.5h1a.5.5 0 0 0 0-1h-1A1.5 1.5 0 0 0 8.5 10V7H14a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2zm.5 3a.5.5 0 1 1 0-1 .5.5 0 0 1 0 1m10 0a.5.5 0 1 1 0-1 .5.5 0 0 1 0 1"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100 p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Schlagworte</span>
                    <h3 class="fw-bold text-secondary mb-0 mt-1"><?= (int) ($stats['total_tags'] ?? 0) ?></h3>
                </div>
                <div class="p-2 bg-secondary bg-opacity-10 text-secondary rounded-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" class="bi bi-tags" viewBox="0 0 16 16">
                        <path d="M3 2v4.586l7 7L14.586 9l-7-7zM2 2a1 1 0 0 1 1-1h5.586a1 1 0 0 1 .707.293l7 7a1 1 0 0 1 0 1.414l-4.586 4.586a1 1 0 0 1-1.414 0l-7-7A1 1 0 0 1 2 6.586z"/>
                        <path d="M4.5 5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3m0 1a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0 5m-1 7a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Obere Aktionsleiste & Paperless-Suche -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="index.php" class="row g-2 align-items-center">
            <input type="hidden" name="route" value="admin/documents">

            <!-- Volltextsuche -->
            <div class="col-12 col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-search text-muted" viewBox="0 0 16 16">
                            <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
                        </svg>
                    </span>
                    <input type="text" name="search" class="form-control bg-light border-start-0 ps-0" placeholder="Titel, Aktenzeichen, Dateiname..." value="<?= htmlspecialchars((string) ($filters['search'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>

            <!-- Dokumenttyp-Filter -->
            <div class="col-6 col-md-2">
                <select name="doc_type" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Alle Dokumenttypen</option>
                    <?php foreach (DocumentService::DOC_TYPES as $k => $info): ?>
                        <option value="<?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?>" <?= (($filters['doc_type'] ?? '') === $k) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($info['label'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Status-Filter -->
            <div class="col-6 col-md-2">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="active" <?= (($filters['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Aktiv</option>
                    <option value="archived" <?= (($filters['status'] ?? '') === 'archived') ? 'selected' : '' ?>>Archiviert</option>
                    <option value="all" <?= (($filters['status'] ?? '') === 'all') ? 'selected' : '' ?>>Alle Status</option>
                </select>
            </div>

            <!-- Tag-Filter -->
            <div class="col-6 col-md-2">
                <select name="tag_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Alle Schlagworte</option>
                    <?php foreach ($tags as $t): ?>
                        <option value="<?= (int) $t['id'] ?>" <?= ((int) ($filters['tag_id'] ?? 0) === (int) $t['id']) ? 'selected' : '' ?>>
                            # <?= htmlspecialchars((string) $t['name'], ENT_QUOTES, 'UTF-8') ?> (<?= (int) ($t['document_count'] ?? 0) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Buttons: Filter anwenden / Zurücksetzen -->
            <div class="col-6 col-md-2 d-flex gap-1 justify-content-end">
                <button type="submit" class="btn btn-sm btn-outline-secondary">Filtern</button>
                <a href="?route=admin/documents" class="btn btn-sm btn-light text-muted" title="Filter zurücksetzen">✕</a>
                <?php if ($canManage): ?>
                    <button type="button" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#uploadModal">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-cloud-arrow-up-fill" viewBox="0 0 16 16">
                            <path d="M8 2a5.53 5.53 0 0 0-3.594 1.342c-.766.66-1.321 1.52-1.464 2.383C1.266 6.095 0 7.555 0 9.318 0 11.366 1.708 13 3.781 13h8.906C14.502 13 16 11.57 16 9.773c0-1.636-1.242-2.969-2.834-3.194C12.923 3.999 10.69 2 8 2m2.354 5.146a.5.5 0 0 1-.708.708L8.5 6.707V10.5a.5.5 0 0 1-1 0V6.707L6.354 7.854a.5.5 0 1 1-.708-.708l2-2a.5.5 0 0 1 .708 0z"/>
                        </svg>
                        Upload
                    </button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Tag-Schnellfilter-Leiste (Paperless Chips) -->
<?php if (!empty($tags)): ?>
    <div class="d-flex flex-wrap align-items-center gap-1 mb-3">
        <span class="text-muted small me-2" style="font-size: 0.8rem;">Schlagworte:</span>
        <a href="?route=admin/documents" class="badge rounded-pill text-decoration-none <?= empty($filters['tag_id']) ? 'bg-dark text-white' : 'bg-light text-secondary border' ?> px-2 py-1">
            Alle
        </a>
        <?php foreach ($tags as $t): 
            $isSelected = ((int) ($filters['tag_id'] ?? 0) === (int) $t['id']);
            $tagColor = (string) ($t['color'] ?? '#6c757d');
        ?>
            <a href="?route=admin/documents&tag_id=<?= (int) $t['id'] ?>" 
               class="badge rounded-pill text-decoration-none px-2 py-1 <?= $isSelected ? 'shadow-sm' : '' ?>"
               style="background-color: <?= htmlspecialchars($tagColor, ENT_QUOTES, 'UTF-8') ?>; color: #fff; opacity: <?= $isSelected ? '1' : '0.85' ?>;">
                # <?= htmlspecialchars((string) $t['name'], ENT_QUOTES, 'UTF-8') ?>
                <span class="badge bg-black bg-opacity-25 ms-1"><?= (int) ($t['document_count'] ?? 0) ?></span>
            </a>
        <?php endforeach; ?>
        <?php if ($canManage): ?>
            <a href="?route=admin/documents/tags" class="btn btn-sm btn-link text-muted text-decoration-none py-0 px-1 small" style="font-size: 0.78rem;">
                + Tags verwalten
            </a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Dokumenten-Liste -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="card-title mb-0 fw-semibold text-dark">
            Dokumente 
            <span class="badge bg-light text-muted border ms-1 fw-normal"><?= $totalDocuments ?> Treffer</span>
        </h6>
        <?php if (!empty($filters['search']) || !empty($filters['doc_type']) || !empty($filters['tag_id'])): ?>
            <span class="small text-muted">Gefilterte Ansicht</span>
        <?php endif; ?>
    </div>

    <div class="card-body p-0">
        <?php if (empty($documents)): ?>
            <div class="text-center py-5">
                <div class="p-3 bg-light rounded-circle d-inline-flex mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" fill="currentColor" class="bi bi-file-earmark-x text-muted" viewBox="0 0 16 16">
                        <path d="M6.854 7.146a.5.5 0 1 0-.708.708L7.293 9l-1.147 1.146a.5.5 0 0 0 .708.708L8 9.707l1.146 1.147a.5.5 0 0 0 .708-.708L8.707 9l1.147-1.146a.5.5 0 0 0-.708-.708L8 8.293z"/>
                        <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2M9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5z"/>
                    </svg>
                </div>
                <h6 class="fw-semibold text-dark">Keine Dokumente gefunden</h6>
                <p class="text-muted small mb-3">Es wurden keine Dokumente gefunden, die den gewählten Such- oder Filterkriterien entsprechen.</p>
                <?php if ($canManage): ?>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                        Erstes Dokument hochladen
                    </button>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width: 320px;">Titel / Aktenzeichen</th>
                            <th>Typ</th>
                            <th>Schlagworte</th>
                            <th>Datum</th>
                            <th>Version</th>
                            <th>Größe</th>
                            <th>Sichtbarkeit</th>
                            <th class="pe-3 text-end" style="width: 140px;">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents as $doc): 
                            $typeInfo = DocumentService::getTypeInfo((string) $doc['doc_type']);
                            $isPdf = str_contains((string) ($doc['mime_type'] ?? ''), 'pdf');
                            $isImage = str_starts_with((string) ($doc['mime_type'] ?? ''), 'image/');
                        ?>
                            <tr>
                                <td class="ps-3">
                                    <div class="d-flex align-items-center">
                                        <div class="p-2 rounded me-2 flex-shrink-0" style="background-color: <?= htmlspecialchars($typeInfo['color'], ENT_QUOTES, 'UTF-8') ?>15; color: <?= htmlspecialchars($typeInfo['color'], ENT_QUOTES, 'UTF-8') ?>;">
                                            <?php if ($isPdf): ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-file-earmark-pdf-fill" viewBox="0 0 16 16">
                                                    <path d="M5.523 12.424q.21-.124.459-.238a8 8 0 0 1-.45.606c-.28.33-.598.75-1.009 1.15a1 1 0 0 1-.773.32q-.11 0-.2-.033a.6.6 0 0 1-.225-.138c-.1-.1-.13-.243-.13-.424a1.8 1.8 0 0 1 .496-1.127q.315-.357.832-.821m1.32-3.15a12.8 12.8 0 0 1-.77-1.523c.24-.04.47-.07.69-.07.46 0 .84.09 1.05.29.2.18.23.44.23.63 0 .43-.27.84-.7 1.04-.15.07-.32.11-.5.11"/>
                                                    <path d="M9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.5zm0 1v3.5A1.5 1.5 0 0 0 11 6h3.5V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1z"/>
                                                </svg>
                                            <?php elseif ($isImage): ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-file-earmark-image" viewBox="0 0 16 16">
                                                    <path d="M6.502 7a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3"/>
                                                    <path d="M14 14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h5.5L14 4.5zM4 1a1 1 0 0 0-1 1v10l2.224-2.224a.5.5 0 0 1 .61-.075L8 11l2.157-3.02a.5.5 0 0 1 .76-.063L13 10V4.5h-2A1.5 1.5 0 0 1 9.5 3V1z"/>
                                                </svg>
                                            <?php else: ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-file-earmark-text" viewBox="0 0 16 16">
                                                    <path d="M5.5 7a.5.5 0 0 0 0 1h5a.5.5 0 0 0 0-1zM5 9.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5"/>
                                                    <path d="M9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.5zm0 1v3.5A1.5 1.5 0 0 0 11 6h3.5V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1z"/>
                                                </svg>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-truncate">
                                            <a href="?route=admin/documents/view&id=<?= (int) $doc['id'] ?>" class="fw-semibold text-dark text-decoration-none">
                                                <?= htmlspecialchars((string) $doc['title'], ENT_QUOTES, 'UTF-8') ?>
                                            </a>
                                            <div class="text-muted small text-truncate" style="font-size: 0.76rem;">
                                                <?php if (!empty($doc['reference_number'])): ?>
                                                    <span class="me-2 fw-medium text-secondary">Az: <?= htmlspecialchars((string) $doc['reference_number'], ENT_QUOTES, 'UTF-8') ?></span>
                                                <?php endif; ?>
                                                <span><?= htmlspecialchars((string) ($doc['original_filename'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge rounded-pill fw-normal" style="background-color: <?= htmlspecialchars($typeInfo['color'], ENT_QUOTES, 'UTF-8') ?>20; color: <?= htmlspecialchars($typeInfo['color'], ENT_QUOTES, 'UTF-8') ?>; border: 1px solid <?= htmlspecialchars($typeInfo['color'], ENT_QUOTES, 'UTF-8') ?>40;">
                                        <?= htmlspecialchars($typeInfo['label'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($doc['tags'])): ?>
                                        <div class="d-flex flex-wrap gap-1">
                                            <?php foreach ($doc['tags'] as $tag): ?>
                                                <span class="badge rounded-pill" style="background-color: <?= htmlspecialchars((string) ($tag['color'] ?? '#6c757d'), ENT_QUOTES, 'UTF-8') ?>; color: #fff; font-size: 0.7rem;">
                                                    <?= htmlspecialchars((string) $tag['name'], ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($doc['document_date'])): ?>
                                        <span><?= date('d.m.Y', strtotime((string) $doc['document_date'])) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted"><?= date('d.m.Y', strtotime((string) $doc['created_at'])) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        v<?= (int) ($doc['latest_version'] ?? 1) ?>
                                        <?php if ((int) ($doc['version_count'] ?? 1) > 1): ?>
                                            <span class="text-muted ms-1">(<?= (int) $doc['version_count'] ?>)</span>
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td class="text-muted">
                                    <?= Upload::formatBytes((int) ($doc['file_size'] ?? 0)) ?>
                                </td>
                                <td>
                                    <?php 
                                    $vis = (string) ($doc['visibility'] ?? 'internal');
                                    if ($vis === 'owner_portal'):
                                    ?>
                                        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">Eigentümer</span>
                                    <?php elseif ($vis === 'public'): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Öffentlich</span>
                                    <?php elseif ($vis === 'admin_only'): ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">Vertraulich</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border">Intern</span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-3 text-end">
                                    <div class="btn-group btn-group-sm">
                                        <!-- Inline Vorschau -->
                                        <a href="?route=admin/documents/download&id=<?= (int) $doc['id'] ?>&inline=1" target="_blank" class="btn btn-outline-secondary" title="Vorschau im Browser">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
                                                <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/>
                                                <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/>
                                            </svg>
                                        </a>
                                        <!-- Direkter Download -->
                                        <a href="?route=admin/documents/download&id=<?= (int) $doc['id'] ?>&inline=0" class="btn btn-outline-secondary" title="Herunterladen">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" class="bi bi-download" viewBox="0 0 16 16">
                                                <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5"/>
                                                <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/>
                                            </svg>
                                        </a>
                                        <!-- Detailansicht / Bearbeiten -->
                                        <a href="?route=admin/documents/view&id=<?= (int) $doc['id'] ?>" class="btn btn-outline-primary" title="Details &amp; Versionen">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" class="bi bi-gear" viewBox="0 0 16 16">
                                                <path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492M5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0"/>
                                                <path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873 1.873 0 0 0 2.693 1.115l.291-.16c.764-.415 1.6.42 1.184 1.185l-.159.292a1.873 1.873 0 0 0 1.116 2.692l.318.094c.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 0 0-1.115 2.693l.16.291c.415.764-.42 1.6-1.185 1.184l-.291-.159a1.873 1.873 0 0 0-2.693 1.116l-.094.318c-.246.835-1.428.835-1.674 0l-.094-.319a1.873 1.873 0 0 0-2.692-1.115l-.292.16c-.764.415-1.6-.42-1.184-1.185l.159-.291A1.873 1.873 0 0 0 1.945 8.93l-.319-.094c-.835-.246-.835-1.428 0-1.674l.319-.094A1.873 1.873 0 0 0 3.06 4.377l-.16-.292c-.415-.764.42-1.6 1.185-1.184l.292.159a1.873 1.873 0 0 0 2.692-1.115z"/>
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="card-footer bg-white py-3 d-flex justify-content-between align-items-center">
                    <span class="small text-muted">Seite <?= $currentPage ?> von <?= $totalPages ?></span>
                    <nav aria-label="Dokumenten-Pagination">
                        <ul class="pagination pagination-sm mb-0">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= ($i === $currentPage) ? 'active' : '' ?>">
                                    <a class="page-link" href="?route=admin/documents&page=<?= $i ?>&search=<?= urlencode((string) ($filters['search'] ?? '')) ?>&doc_type=<?= urlencode((string) ($filters['doc_type'] ?? '')) ?>&tag_id=<?= (int) ($filters['tag_id'] ?? 0) ?>&status=<?= urlencode((string) ($filters['status'] ?? 'active')) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: NEUES DOKUMENT HOCHLADEN (PAPERLESS-STYLE)                         -->
<!-- ========================================================================= -->
<?php if ($canManage): ?>
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="?route=admin/documents/create" enctype="multipart/form-data">
                <?= Csrf::field() ?>

                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="uploadModalLabel">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-file-earmark-plus text-primary" viewBox="0 0 16 16">
                            <path d="M8 6.5a.5.5 0 0 1 .5.5v1.5H10a.5.5 0 0 1 0 1H8.5V11a.5.5 0 0 1-1 0V9.5H6a.5.5 0 0 1 0-1h1.5V7a.5.5 0 0 1 .5-.5"/>
                            <path d="M14 4.5V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h5.5zm-3 0A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V4.5z"/>
                        </svg>
                        Neues Dokument hochladen
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>

                <div class="modal-body p-4">
                    <!-- Datei-Upload Dropzone -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Dokument-Datei auswählen <span class="text-danger">*</span></label>
                        <div class="p-4 border border-2 border-dashed rounded-3 text-center bg-light" id="dropZone">
                            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" fill="currentColor" class="bi bi-cloud-arrow-up text-primary mb-2" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M7.646 5.146a.5.5 0 0 1 .708 0l2 2a.5.5 0 0 1-.708.708L8.5 6.707V10.5a.5.5 0 0 1-1 0V6.707L6.354 7.854a.5.5 0 1 1-.708-.708z"/>
                                <path d="M4.406 3.342A5.53 5.53 0 0 1 8 2c2.69 0 4.923 2 5.166 4.579C14.758 6.804 16 8.137 16 9.773 16 11.569 14.502 13 12.687 13H3.781C1.708 13 0 11.366 0 9.318c0-1.763 1.266-3.223 2.942-3.593.143-.863.698-1.723 1.464-2.383m.653.757c-.757.653-1.153 1.44-1.153 2.056v.448l-.445.049C2.064 6.805 1 7.952 1 9.318 1 10.785 2.23 12 3.781 12h8.906C13.98 12 15 10.988 15 9.773c0-1.216-1.02-2.228-2.313-2.228h-.5v-.5C12.188 4.825 10.328 3 8 3a4.53 4.53 0 0 0-2.941 1.1z"/>
                            </svg>
                            <p class="mb-2 text-dark fw-medium">Datei hier ablegen oder per Klick auswählen</p>
                            <input type="file" name="document_file" id="documentFileInput" class="form-control form-control-sm w-auto mx-auto" required accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.ods,.txt,.csv,.png,.jpg,.jpeg,.webp,.tiff">
                            <span class="text-muted small d-block mt-2" style="font-size: 0.75rem;">
                                Unterstützt: PDF, Word (DOC/DOCX), Excel (XLS/XLSX), LibreOffice, Bilder/Scans (PNG, JPG, TIFF) bis max. 25 MB.
                            </span>
                        </div>
                    </div>

                    <!-- Titel & Typ -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label for="docTitle" class="form-label fw-semibold">Titel / Bezeichnung</label>
                            <input type="text" name="title" id="docTitle" class="form-control" placeholder="Wird automatisch aus dem Dateinamen erzeugt, falls leer">
                        </div>
                        <div class="col-md-4">
                            <label for="docType" class="form-label fw-semibold">Dokumententyp <span class="text-danger">*</span></label>
                            <select name="doc_type" id="docType" class="form-select" required>
                                <?php foreach (DocumentService::DOC_TYPES as $k => $info): ?>
                                    <option value="<?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?>" <?= $k === 'INVOICE' ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($info['label'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Aktenzeichen & Datum -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="refNum" class="form-label fw-semibold">Aktenzeichen / Rechnungs-Nr.</label>
                            <input type="text" name="reference_number" id="refNum" class="form-control" placeholder="z. B. RE-2024-0012">
                        </div>
                        <div class="col-md-4">
                            <label for="docDate" class="form-label fw-semibold">Dokumentdatum</label>
                            <input type="date" name="document_date" id="docDate" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="visibility" class="form-label fw-semibold">Sichtbarkeit (Portal)</label>
                            <select name="visibility" id="visibility" class="form-select">
                                <?php foreach (DocumentService::VISIBILITIES as $vk => $vl): ?>
                                    <option value="<?= htmlspecialchars($vk, ENT_QUOTES, 'UTF-8') ?>" <?= $vk === 'internal' ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($vl, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Gültigkeit (optional) -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="validFrom" class="form-label text-muted small">Gültig ab (optional)</label>
                            <input type="date" name="valid_from" id="validFrom" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label for="validUntil" class="form-label text-muted small">Gültig bis (optional)</label>
                            <input type="date" name="valid_until" id="validUntil" class="form-control form-control-sm">
                        </div>
                    </div>

                    <!-- Tags -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Schlagworte (Tags)</label>
                        <?php if (!empty($tags)): ?>
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                <?php foreach ($tags as $t): ?>
                                    <div class="form-check form-check-inline m-0">
                                        <input class="form-check-input" type="checkbox" name="tags[]" value="<?= (int) $t['id'] ?>" id="tag_check_<?= (int) $t['id'] ?>">
                                        <label class="form-check-label badge rounded-pill" for="tag_check_<?= (int) $t['id'] ?>" style="background-color: <?= htmlspecialchars((string) ($t['color'] ?? '#6c757d'), ENT_QUOTES, 'UTF-8') ?>; color: #fff;">
                                            <?= htmlspecialchars((string) $t['name'], ENT_QUOTES, 'UTF-8') ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <input type="text" name="new_tags_csv" class="form-control form-control-sm" placeholder="Neue Schlagworte kommagetrennt hinzufügen (z. B. Reparatur, Dach, 2024)">
                    </div>

                    <!-- Eigentümerportal-Verknüpfung (Vorbereitung Property / Unit / Case) -->
                    <div class="border rounded p-3 bg-light bg-opacity-50">
                        <h6 class="fw-semibold text-dark small mb-2 d-flex align-items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-link-45deg" viewBox="0 0 16 16">
                                <path d="M4.715 6.542 3.343 7.914a3 3 0 1 0 4.243 4.243l1.828-1.829A3 3 0 0 0 8.586 5.5L8 6.086a1 1 0 0 0-.154.199 2 2 0 0 1 .861 3.337L6.88 11.45a2 2 0 1 1-2.83-2.83l.793-.792a4 4 0 0 1-.128-1.287z"/>
                                <path d="M6.586 4.672A3 3 0 0 0 7.414 9.5l.775-.776a2 2 0 0 1-.896-3.346L9.12 3.55a2 2 0 1 1 2.83 2.83l-.793.792c.112.42.155.855.128 1.287l1.372-1.372a3 3 0 0 0-4.243-4.243z"/>
                            </svg>
                            Eigentümerportal-Verknüpfung (Optional)
                        </h6>
                        <div class="row g-2">
                            <div class="col-md-3">
                                <select name="target_type" class="form-select form-select-sm">
                                    <option value="">Keine Zuordnung</option>
                                    <option value="property">Liegenschaft</option>
                                    <option value="unit">Einheit / Wohnung</option>
                                    <option value="case">Vorgang / Case</option>
                                    <option value="contact">Kontakt / Eigentümer</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="text" name="target_id" class="form-control form-control-sm" placeholder="Objekt-ID (z. B. 101)">
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="target_label" class="form-control form-control-sm" placeholder="Bezeichnung (z. B. WEG Mozartstr. 12)">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-sm btn-primary fw-semibold px-3">
                        Dokument archivieren &amp; speichern
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../../views/layouts/admin.php';
