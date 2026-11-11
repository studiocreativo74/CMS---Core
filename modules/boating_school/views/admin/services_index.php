<?php

declare(strict_types=1);

/**
 * Bootsfahrschule - Leistungen & Kurse Übersicht (Tarifverwaltung)
 *
 * @var array<int, array<string, mixed>> $services
 * @var int $totalServices
 * @var array<string, mixed> $filters
 * @var bool $tablesCreated
 */

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$title = 'Leistungen & Tarife - Nautic';
$currentRoute = 'admin/boating/services';
$canManage = BoatingSchoolService::canManage();
$csrfToken = class_exists('Csrf') ? Csrf::getToken() : '';
$availableCategories = class_exists('ServiceService') ? ServiceService::getAllAvailableCategories() : [];

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

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="?route=admin/boating">Nautic</a></li>
                <li class="breadcrumb-item active" aria-current="page">Leistungen &amp; Tarife</li>
            </ol>
        </nav>
        <h1 class="h3 fw-bold text-dark mb-0">
            <i class="bi bi-tag-fill text-primary me-2"></i>Leistungen &amp; Tarife (<?= $totalServices ?>)
        </h1>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <div class="dropdown">
            <button class="btn btn-outline-dark btn-sm dropdown-toggle d-flex align-items-center gap-1 rounded-3 px-3" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-list fs-6"></i> Nautic Menü
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 p-2" style="min-width: 240px; z-index: 1060;">
                <li><a class="dropdown-item rounded-2 py-2" href="?route=admin/boating"><i class="bi bi-compass me-2 text-primary"></i>Übersicht &amp; Flotte</a></li>
                <li><a class="dropdown-item rounded-2 py-2" href="?route=admin/boating/calendar"><i class="bi bi-calendar3 me-2 text-info"></i>Kalender &amp; Disposition</a></li>
                <li><a class="dropdown-item rounded-2 py-2" href="?route=admin/boating/boats"><i class="bi bi-tsunami me-2"></i>Boote &amp; Vermietung</a></li>
                <li><a class="dropdown-item rounded-2 py-2" href="?route=admin/boating/instructors"><i class="bi bi-person-badge me-2 text-warning"></i>Fahrlehrer &amp; Skipper</a></li>
                <li><a class="dropdown-item rounded-2 py-2 active" href="?route=admin/boating/services"><i class="bi bi-tag me-2 text-danger"></i>Leistungen &amp; Tarife</a></li>
                <li><a class="dropdown-item rounded-2 py-2" href="?route=admin/boating/customers"><i class="bi bi-people me-2 text-success"></i>Kunden &amp; Schüler CRM</a></li>
                <li><a class="dropdown-item rounded-2 py-2" href="?route=admin/boating/reports"><i class="bi bi-graph-up-arrow me-2 text-secondary"></i>Auswertungen &amp; Payroll</a></li>
                <li><a class="dropdown-item rounded-2 py-2" href="?route=admin/boating/settings"><i class="bi bi-gear me-2"></i>Einstellungen</a></li>
            </ul>
        </div>
        <a href="?route=admin/boating" class="btn btn-outline-secondary btn-sm rounded-3 px-3">
            <i class="bi bi-arrow-left me-1"></i> Dashboard
        </a>
        <?php if ($canManage): ?>
            <button type="button" class="btn btn-primary btn-sm rounded-3 px-3" data-bs-toggle="modal" data-bs-target="#createServiceModal">
                <i class="bi bi-plus-lg me-1"></i> Leistung hinzufügen
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Sub-Tabs: Angebote vs Gutscheine -->
<div class="mb-4">
    <div class="bg-white p-1.5 rounded-3 border shadow-xs d-inline-flex gap-1">
        <a href="?route=admin/boating/services" class="btn btn-sm btn-primary fw-bold px-3 py-1.5 rounded-2 d-flex align-items-center gap-1.5 shadow-xs">
            <i class="bi bi-tag-fill"></i>
            <span>1. Leistungen &amp; Tarife</span>
            <span class="badge bg-white text-primary ms-1"><?= $totalServices ?></span>
        </a>
        <a href="?route=admin/boating/promos" class="btn btn-sm btn-light text-secondary fw-semibold px-3 py-1.5 rounded-2 d-flex align-items-center gap-1.5">
            <i class="bi bi-ticket-perforated text-success"></i>
            <span>2. Gutscheine &amp; Rabattcodes</span>
        </a>
    </div>
</div>

<!-- Filter-Leiste -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="get" class="row g-2 align-items-center">
            <input type="hidden" name="route" value="admin/boating/services">
            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Suchbegriff (Name, Kürzel)..." value="<?= htmlspecialchars((string) ($filters['search'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="category" class="form-select form-select-sm">
                    <option value="">Alle Kategorien</option>
                    <?php foreach ($availableCategories as $catKey => $catLabel): ?>
                        <option value="<?= htmlspecialchars((string) $catKey, ENT_QUOTES, 'UTF-8') ?>" <?= (($filters['category'] ?? '') === (string) $catKey || ($filters['category'] ?? '') === (string) $catLabel) ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string) $catLabel, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="is_active" class="form-select form-select-sm">
                    <option value="">Status: Alle</option>
                    <option value="1" <?= ($filters['is_active'] ?? '') === '1' ? 'selected' : '' ?>>Aktiv</option>
                    <option value="0" <?= ($filters['is_active'] ?? '') === '0' ? 'selected' : '' ?>>Inaktiv</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-secondary flex-grow-1">Filtern</button>
                <?php if (!empty($filters['search']) || !empty($filters['category']) || (isset($filters['is_active']) && $filters['is_active'] !== '')): ?>
                    <a href="?route=admin/boating/services" class="btn btn-sm btn-outline-secondary" title="Zurücksetzen">
                        <i class="bi bi-x-lg"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Leistungs-Tabelle -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($services)): ?>
            <div class="text-center py-5">
                <i class="bi bi-tag text-muted fs-1 d-block mb-2"></i>
                <h6 class="text-muted mb-2">Keine Leistungen gefunden</h6>
                <p class="text-muted small mb-3">Erfassen Sie Fahrstunden, Theorieblöcke, Prüfungspauschalen oder Mietpreise.</p>
                <?php if ($canManage): ?>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createServiceModal">
                        <i class="bi bi-plus-lg me-1"></i> Neue Leistung anlegen
                    </button>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="servicesSortableTable">
                    <thead class="table-light small">
                        <tr>
                            <th style="width: 44px;" class="text-center" title="Reihenfolge per Drag &amp; Drop ändern">Pos</th>
                            <th>Leistung / Kurs</th>
                            <th>Kürzel</th>
                            <th>Kategorie</th>
                            <th>Dauer</th>
                            <th>Preis</th>
                            <th>Status</th>
                            <th class="text-end" style="width: 140px;">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody id="servicesSortableBody">
                        <?php foreach ($services as $srv): ?>
                            <tr class="draggable-row" draggable="true" data-id="<?= (int) $srv['id'] ?>" style="cursor: grab;">
                                <td class="text-center px-1">
                                    <div class="text-muted cursor-grab d-inline-flex align-items-center justify-content-center p-1 rounded hover-bg-light" title="Ziehen zum Sortieren">
                                        <i class="bi bi-grip-vertical fs-5"></i>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">
                                        <?= htmlspecialchars((string) $srv['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <?php if (!empty($srv['description'])): ?>
                                        <div class="small text-muted text-truncate" style="max-width: 280px;">
                                            <?= htmlspecialchars((string) $srv['description'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($srv['code'])): ?>
                                        <span class="font-monospace small text-muted border rounded px-1"><?= htmlspecialchars((string) $srv['code'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?= htmlspecialchars(BoatingSchoolService::getCategoryLabel((string) ($srv['category'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($srv['duration_minutes'])): ?>
                                        <span class="small text-dark fw-semibold"><?= (int) $srv['duration_minutes'] ?> Min.</span>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">
                                        <?= BoatingSchoolService::formatPrice((float) ($srv['price'] ?? 0.0)) ?>
                                    </div>
                                    <?php if (!empty($srv['tax_rate']) && (float) $srv['tax_rate'] > 0): ?>
                                        <div class="small text-muted">inkl. <?= number_format((float) $srv['tax_rate'], 1) ?>% MwSt. (Miete)</div>
                                    <?php else: ?>
                                        <div class="small text-muted">0.0% MwSt. (Fahrschule)</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($canManage): ?>
                                        <form method="post" action="?route=admin/boating/services/toggle-status" class="d-inline">
                                             <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="id" value="<?= (int) $srv['id'] ?>">
                                            <button type="submit" class="btn btn-sm p-0 border-0 bg-transparent" title="Status umschalten">
                                                <?php if (!empty($srv['is_active'])): ?>
                                                    <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>Aktiv</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary-subtle text-muted"><i class="bi bi-pause-circle me-1"></i>Inaktiv</span>
                                                <?php endif; ?>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <?php if (!empty($srv['is_active'])): ?>
                                            <span class="badge bg-success-subtle text-success">Aktiv</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-muted">Inaktiv</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="?route=admin/boating/services/edit&id=<?= (int) $srv['id'] ?>" class="btn btn-outline-secondary" title="Bearbeiten">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($canManage): ?>
                                            <form method="post" action="?route=admin/boating/services/delete" class="d-inline delete-confirm-form" data-name="<?= htmlspecialchars((string) $srv['name'], ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="id" value="<?= (int) $srv['id'] ?>">
                                                <button type="button" class="btn btn-outline-danger btn-trigger-delete" title="Löschen">
                                                    <i class="bi bi-trash"></i>
                                                </button>
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

<!-- Modal: Leistung erstellen -->
<?php if ($canManage): ?>
<div class="modal fade" id="createServiceModal" tabindex="-1" aria-labelledby="createServiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" action="?route=admin/boating/services/create">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="createServiceModalLabel">
                        <i class="bi bi-tag-fill text-primary me-2"></i>Neue Leistung / Tarif anlegen
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>
                
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Bezeichnung der Leistung <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required placeholder="z. B. Praxisausbildung Motorboot Kat. A (90 Min)">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Artikel- / Kurskürzel</label>
                            <input type="text" name="code" class="form-control" placeholder="z. B. PRAX-MOT-90">
                        </div>

                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">
                                Kategorie / Leistungsart <span class="text-danger">*</span>
                                <span class="text-muted fw-normal ms-1">(beliebig bearbeitbar oder aus Liste wählen)</span>
                            </label>
                            <div class="input-group">
                                <input type="text" name="category" id="createServiceCategory" list="createServiceCategoryList" class="form-control" placeholder="z. B. Praxisunterricht, VIP-Coaching, Bootsmiete..." required>
                                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Vorschläge anzeigen"></button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="max-height: 240px; overflow-y: auto;">
                                    <?php foreach ($availableCategories as $catKey => $catLabel): ?>
                                        <li>
                                            <a class="dropdown-item py-1.5 small" href="javascript:void(0)" onclick="document.getElementById('createServiceCategory').value = <?= htmlspecialchars(json_encode($catLabel), ENT_QUOTES, 'UTF-8') ?>">
                                                <?= htmlspecialchars($catLabel, ENT_QUOTES, 'UTF-8') ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <datalist id="createServiceCategoryList">
                                <?php foreach ($availableCategories as $catKey => $catLabel): ?>
                                    <option value="<?= htmlspecialchars($catLabel, ENT_QUOTES, 'UTF-8') ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <input type="hidden" name="boat_type" value="any">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Dauer (in Minuten)</label>
                            <input type="number" name="duration_minutes" class="form-control" value="60" min="15" step="15">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Preis brutto (CHF / EUR) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="price" class="form-control" step="0.05" min="0" placeholder="z. B. 150.00" required>
                                <span class="input-group-text">CHF</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">MwSt.-Satz (%)</label>
                            <input type="number" name="tax_rate" class="form-control" step="0.1" min="0" value="8.1" placeholder="z. B. 8.1">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Beschreibung / Inhalt</label>
                            <textarea name="description" rows="3" class="form-control" placeholder="Detaillierte Beschreibung der Ausbildungsleistung oder Mietkonditionen..."></textarea>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="srvActiveSwitch" value="1" checked>
                                <label class="form-check-label small fw-semibold" for="srvActiveSwitch">Leistung ist aktiv buchbar</label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-sm btn-primary fw-semibold px-3">Leistung speichern</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Modal: Sicherheitsabfrage vor dem Löschen -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-body p-4 text-center">
                <div class="rounded-circle bg-danger bg-opacity-10 text-danger d-inline-flex align-items-center justify-content-center mb-3" style="width: 52px; height: 52px;">
                    <i class="bi bi-trash3-fill fs-4"></i>
                </div>
                <h6 class="fw-bold text-dark mb-1" id="deleteModalTitle">Leistung wirklich löschen?</h6>
                <p class="text-muted small mb-3" id="deleteModalMessage">Möchten Sie dieses Angebot wirklich unwiderruflich löschen? Zugehörige Preisstufen werden ebenfalls entfernt.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-sm btn-light border px-3" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="button" class="btn btn-sm btn-danger fw-semibold px-3" id="confirmDeleteSubmitBtn">Ja, löschen</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Sichere Lösch-Bestätigung (kein window.confirm)
    let formToSubmit = null;
    const deleteModalEl = document.getElementById('deleteConfirmModal');
    const deleteModal = deleteModalEl && window.bootstrap ? new bootstrap.Modal(deleteModalEl) : null;
    const deleteModalTitle = document.getElementById('deleteModalTitle');
    const deleteModalMessage = document.getElementById('deleteModalMessage');
    const confirmDeleteBtn = document.getElementById('confirmDeleteSubmitBtn');

    document.querySelectorAll('.btn-trigger-delete').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            formToSubmit = this.closest('form');
            const itemName = formToSubmit ? formToSubmit.getAttribute('data-name') : 'dieses Element';
            if (deleteModalTitle) deleteModalTitle.textContent = 'Leistung wirklich löschen?';
            if (deleteModalMessage) {
                deleteModalMessage.textContent = 'Möchten Sie das Angebot "' + itemName + '" wirklich unwiderruflich aus der Datenbank entfernen?';
            }
            if (deleteModal) {
                deleteModal.show();
            } else if (confirm('Möchten Sie "' + itemName + '" wirklich löschen?')) {
                formToSubmit.submit();
            }
        });
    });

    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function () {
            if (formToSubmit) {
                formToSubmit.submit();
            }
        });
    }

    // 2. Drag & Drop Reordering der Tabellen-Zeilen
    const tbody = document.getElementById('servicesSortableBody');
    if (!tbody) return;

    let draggedRow = null;

    tbody.querySelectorAll('.draggable-row').forEach(row => {
        row.addEventListener('dragstart', function (e) {
            draggedRow = this;
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', this.dataset.id || '');
            this.classList.add('table-warning', 'opacity-50');
        });

        row.addEventListener('dragover', function (e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            if (this !== draggedRow) {
                this.classList.add('table-active');
                this.style.borderTop = '2px solid #0d6efd';
            }
        });

        row.addEventListener('dragleave', function () {
            this.classList.remove('table-active');
            this.style.borderTop = '';
        });

        row.addEventListener('drop', function (e) {
            e.preventDefault();
            this.classList.remove('table-active');
            this.style.borderTop = '';

            if (draggedRow && draggedRow !== this) {
                const rows = Array.from(tbody.querySelectorAll('.draggable-row'));
                const draggedIndex = rows.indexOf(draggedRow);
                const targetIndex = rows.indexOf(this);

                if (draggedIndex < targetIndex) {
                    this.after(draggedRow);
                } else {
                    this.before(draggedRow);
                }

                // Feedback Toast / Notification
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-info py-2 px-3 small position-fixed bottom-0 end-0 m-3 shadow-lg z-3 d-flex align-items-center gap-2';
                alertDiv.innerHTML = '<i class="bi bi-check-circle-fill text-primary"></i> <span>Reihenfolge der Leistungen angepasst</span>';
                document.body.appendChild(alertDiv);
                setTimeout(() => alertDiv.remove(), 2500);
            }
        });

        row.addEventListener('dragend', function () {
            this.classList.remove('table-warning', 'opacity-50');
            tbody.querySelectorAll('.draggable-row').forEach(r => {
                r.classList.remove('table-active');
                r.style.borderTop = '';
            });
            draggedRow = null;
        });
    });
});
</script>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../../../views/layouts/admin.php';
