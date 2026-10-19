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

$title = 'Leistungen & Tarife - Bootsfahrschule';
$currentRoute = 'admin/boating/services';
$canManage = BoatingSchoolService::canManage();
$csrfToken = class_exists('Csrf') ? Csrf::getToken() : '';

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
                <li class="breadcrumb-item"><a href="?route=admin/boating">Bootsfahrschule</a></li>
                <li class="breadcrumb-item active" aria-current="page">Leistungen</li>
            </ol>
        </nav>
        <h1 class="h3 fw-bold text-dark mb-0">
            <i class="bi bi-tag-fill text-primary me-2"></i>Leistungen &amp; Tarife (<?= $totalServices ?>)
        </h1>
    </div>
    <div class="d-flex gap-2">
        <a href="?route=admin/boating" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Dashboard
        </a>
        <?php if ($canManage): ?>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createServiceModal">
                <i class="bi bi-plus-lg me-1"></i> Leistung hinzufügen
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Filter-Leiste -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="get" class="row g-2 align-items-center">
            <input type="hidden" name="route" value="admin/boating/services">
            <div class="col-md-3">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Suchbegriff (Name, Kürzel)..." value="<?= htmlspecialchars((string) ($filters['search'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="category" class="form-select form-select-sm">
                    <option value="">Alle Kategorien</option>
                    <option value="practical" <?= ($filters['category'] ?? '') === 'practical' ? 'selected' : '' ?>>Praxisunterricht (practical)</option>
                    <option value="theory" <?= ($filters['category'] ?? '') === 'theory' ? 'selected' : '' ?>>Theoriekurs (theory)</option>
                    <option value="rental" <?= ($filters['category'] ?? '') === 'rental' ? 'selected' : '' ?>>Bootsmiete (rental)</option>
                    <option value="exam" <?= ($filters['category'] ?? '') === 'exam' ? 'selected' : '' ?>>Prüfungsfahrt (exam)</option>
                    <option value="package" <?= ($filters['category'] ?? '') === 'package' ? 'selected' : '' ?>>Pauschale / Paket (package)</option>
                    <option value="other" <?= ($filters['category'] ?? '') === 'other' ? 'selected' : '' ?>>Sonstiges (other)</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="boat_type" class="form-select form-select-sm">
                    <option value="">Alle Bootstypen</option>
                    <option value="any" <?= ($filters['boat_type'] ?? '') === 'any' ? 'selected' : '' ?>>Beliebig / any</option>
                    <option value="motor" <?= ($filters['boat_type'] ?? '') === 'motor' ? 'selected' : '' ?>>Motorboot (motor)</option>
                    <option value="sail" <?= ($filters['boat_type'] ?? '') === 'sail' ? 'selected' : '' ?>>Segelboot (sail)</option>
                    <option value="rib" <?= ($filters['boat_type'] ?? '') === 'rib' ? 'selected' : '' ?>>RIB (rib)</option>
                    <option value="electric" <?= ($filters['boat_type'] ?? '') === 'electric' ? 'selected' : '' ?>>Elektroboot (electric)</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="is_active" class="form-select form-select-sm">
                    <option value="">Status: Alle</option>
                    <option value="1" <?= ($filters['is_active'] ?? '') === '1' ? 'selected' : '' ?>>Aktiv</option>
                    <option value="0" <?= ($filters['is_active'] ?? '') === '0' ? 'selected' : '' ?>>Inaktiv</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-secondary flex-grow-1">Filtern</button>
                <?php if (!empty($filters['search']) || !empty($filters['category']) || !empty($filters['boat_type']) || (isset($filters['is_active']) && $filters['is_active'] !== '')): ?>
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
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th>Leistung / Kurs</th>
                            <th>Kürzel</th>
                            <th>Kategorie</th>
                            <th>Bootstyp</th>
                            <th>Dauer</th>
                            <th>Preis</th>
                            <th>Status</th>
                            <th class="text-end" style="width: 140px;">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services as $srv): ?>
                            <tr>
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
                                    <?php if (!empty($srv['boat_type'])): ?>
                                        <?= BoatingSchoolService::getBoatTypeBadge((string) $srv['boat_type']) ?>
                                    <?php else: ?>
                                        <span class="text-muted small">Flexibel / Alle</span>
                                    <?php endif; ?>
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
                                        <div class="small text-muted">inkl. <?= number_format((float) $srv['tax_rate'], 1) ?>% MwSt.</div>
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
                                            <form method="post" action="?route=admin/boating/services/delete" class="d-inline" onsubmit="return confirm('Möchten Sie diese Leistung wirklich löschen?');">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="id" value="<?= (int) $srv['id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger" title="Löschen">
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

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Kategorie</label>
                            <select name="category" class="form-select">
                                <option value="practical">Praxisunterricht (practical)</option>
                                <option value="theory">Theoriekurs (theory)</option>
                                <option value="rental">Bootsmiete (rental)</option>
                                <option value="exam">Prüfungsfahrt (exam)</option>
                                <option value="package">Pauschale / Paket (package)</option>
                                <option value="other">Sonstiges (other)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Bootstyp</label>
                            <select name="boat_type" class="form-select">
                                <option value="any">Alle / Beliebig (any)</option>
                                <option value="motor">Motorboot (motor)</option>
                                <option value="sail">Segelboot (sail)</option>
                                <option value="rib">RIB / Festrumpfschlauchboot (rib)</option>
                                <option value="electric">Elektroboot (electric)</option>
                            </select>
                        </div>
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
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../../../views/layouts/admin.php';
