<?php

declare(strict_types=1);

/**
 * DMS - Schlagwort-Verwaltung (Tags)
 *
 * @var array<int, array<string, mixed>> $tags
 */

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$title = 'Schlagworte verwalten';
$currentRoute = 'admin/documents';

$canManage = DocumentService::canManage();

ob_start();
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="?route=admin/documents" class="text-decoration-none">Dokumente</a></li>
                <li class="breadcrumb-item active" aria-current="page">Schlagworte</li>
            </ol>
        </nav>
        <h4 class="fw-bold text-dark mb-0">Schlagworte (Tags)</h4>
    </div>
    <a href="?route=admin/documents" class="btn btn-sm btn-outline-secondary">
        &larr; Zurück zur Dokumentenübersicht
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

<div class="row g-4">
    <!-- Tag Liste -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-semibold text-dark">Alle definierten Schlagworte (<?= count($tags) ?>)</h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($tags)): ?>
                    <p class="text-muted small p-4 mb-0">Noch keine Schlagworte definiert.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Vorschau</th>
                                    <th>Name</th>
                                    <th>Slug</th>
                                    <th>Zugeordnete Dokumente</th>
                                    <th class="pe-3 text-end">Aktion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tags as $t): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <span class="badge rounded-pill px-3 py-1" style="background-color: <?= htmlspecialchars((string) ($t['color'] ?? '#6c757d'), ENT_QUOTES, 'UTF-8') ?>; color: #fff;">
                                                # <?= htmlspecialchars((string) $t['name'], ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </td>
                                        <td class="fw-semibold text-dark">
                                            <?= htmlspecialchars((string) $t['name'], ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                        <td>
                                            <code><?= htmlspecialchars((string) $t['slug'], ENT_QUOTES, 'UTF-8') ?></code>
                                        </td>
                                        <td>
                                            <a href="?route=admin/documents&tag_id=<?= (int) $t['id'] ?>" class="text-decoration-none">
                                                <span class="badge bg-light text-dark border"><?= (int) ($t['document_count'] ?? 0) ?> Dokumente</span>
                                            </a>
                                        </td>
                                        <td class="pe-3 text-end">
                                            <?php if ($canManage): ?>
                                                <form method="POST" action="?route=admin/documents/tags/delete" onsubmit="return confirm('Möchten Sie das Schlagwort wirklich löschen? Die Zuordnungen zu Dokumenten werden dabei entfernt.');" class="d-inline">
                                                    <?= Csrf::field() ?>
                                                    <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" title="Löschen">
                                                        Löschen
                                                    </button>
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
    </div>

    <!-- Neues Schlagwort anlegen -->
    <?php if ($canManage): ?>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="card-title mb-0 fw-semibold text-dark">Neues Schlagwort erstellen</h6>
                </div>
                <div class="card-body p-3">
                    <form method="POST" action="?route=admin/documents/tags/save">
                        <?= Csrf::field() ?>

                        <div class="mb-3">
                            <label for="tagName" class="form-label fw-semibold">Name des Tags <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="tagName" class="form-control" placeholder="z. B. Jahresabrechnung" required>
                        </div>

                        <div class="mb-3">
                            <label for="tagColor" class="form-label fw-semibold">Farbe</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="color" name="color" id="tagColor" class="form-control form-control-color" value="#0d6efd" title="Farbe wählen">
                                <span class="text-muted small">Farb-Badge für schnelles visuelles Erkennen im Paperless-Archiv.</span>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-sm btn-primary w-100 fw-semibold">
                            Schlagwort anlegen
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../../views/layouts/admin.php';
