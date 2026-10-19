<?php

declare(strict_types=1);

/**
 * Bootsfahrschule - Boote Übersicht (Flottenverwaltung)
 *
 * @var array<int, array<string, mixed>> $boats
 * @var int $totalBoats
 * @var array<string, mixed> $filters
 * @var bool $tablesCreated
 */

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$title = 'Boote & Flotte - Bootsfahrschule';
$currentRoute = 'admin/boating/boats';
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
                <li class="breadcrumb-item active" aria-current="page">Boote</li>
            </ol>
        </nav>
        <h1 class="h3 fw-bold text-dark mb-0">
            <i class="bi bi-water text-primary me-2"></i>Boote &amp; Flotte (<?= $totalBoats ?>)
        </h1>
    </div>
    <div class="d-flex gap-2">
        <a href="?route=admin/boating" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Dashboard
        </a>
        <?php if ($canManage): ?>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createBoatModal">
                <i class="bi bi-plus-lg me-1"></i> Boot hinzufügen
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Filter-Leiste -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="get" class="row g-2 align-items-center">
            <input type="hidden" name="route" value="admin/boating/boats">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Suchbegriff (Name, Kennzeichen)..." value="<?= htmlspecialchars((string) ($filters['search'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="type" class="form-select form-select-sm">
                    <option value="">Alle Bootstypen</option>
                    <option value="motor" <?= ($filters['type'] ?? '') === 'motor' ? 'selected' : '' ?>>Motorboot</option>
                    <option value="sail" <?= ($filters['type'] ?? '') === 'sail' ? 'selected' : '' ?>>Segelboot</option>
                    <option value="rib" <?= ($filters['type'] ?? '') === 'rib' ? 'selected' : '' ?>>RIB / Festrumpfschlauchboot</option>
                    <option value="electric" <?= ($filters['type'] ?? '') === 'electric' ? 'selected' : '' ?>>Elektroboot</option>
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
                <?php if (!empty($filters['search']) || !empty($filters['type']) || (isset($filters['is_active']) && $filters['is_active'] !== '')): ?>
                    <a href="?route=admin/boating/boats" class="btn btn-sm btn-outline-secondary" title="Zurücksetzen">
                        <i class="bi bi-x-lg"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Boot-Tabelle -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($boats)): ?>
            <div class="text-center py-5">
                <i class="bi bi-water text-muted fs-1 d-block mb-2"></i>
                <h6 class="text-muted mb-2">Keine Boote gefunden</h6>
                <p class="text-muted small mb-3">Erfassen Sie Ihr erstes Schul- oder Mietboot.</p>
                <?php if ($canManage): ?>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createBoatModal">
                        <i class="bi bi-plus-lg me-1"></i> Neues Boot anlegen
                    </button>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th style="width: 60px;">Bild</th>
                            <th>Name &amp; Kennzeichen</th>
                            <th>Typ</th>
                            <th>Kapazität</th>
                            <th>Motor / Leistung</th>
                            <th>Farbe</th>
                            <th>Status</th>
                            <th class="text-end" style="width: 140px;">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($boats as $boat): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($boat['image_path'])): ?>
                                        <img src="<?= htmlspecialchars((string) $boat['image_path'], ENT_QUOTES, 'UTF-8') ?>" alt="Boot" class="rounded object-fit-cover" style="width: 48px; height: 36px;">
                                    <?php else: ?>
                                        <div class="bg-light rounded d-flex align-items-center justify-content-center text-muted" style="width: 48px; height: 36px;">
                                            <i class="bi bi-water"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">
                                        <?= htmlspecialchars((string) $boat['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <?php if (!empty($boat['registration'])): ?>
                                        <div class="small text-muted font-monospace">
                                            <i class="bi bi-card-text me-1"></i><?= htmlspecialchars((string) $boat['registration'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= BoatingSchoolService::getBoatTypeBadge((string) ($boat['type'] ?? '')) ?>
                                </td>
                                <td>
                                    <?php if (!empty($boat['capacity'])): ?>
                                        <span class="badge bg-light text-dark border">
                                            <i class="bi bi-people me-1"></i><?= (int) $boat['capacity'] ?> Pers.
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($boat['engine_power'])): ?>
                                        <span class="small fw-semibold"><?= (int) $boat['engine_power'] ?> PS/kW</span>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($boat['color'])): ?>
                                        <span class="small text-muted"><?= htmlspecialchars((string) $boat['color'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($canManage): ?>
                                        <form method="post" action="?route=admin/boating/boats/toggle-status" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="id" value="<?= (int) $boat['id'] ?>">
                                            <button type="submit" class="btn btn-sm p-0 border-0 bg-transparent" title="Status umschalten">
                                                <?php if (!empty($boat['is_active'])): ?>
                                                    <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>Aktiv</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary-subtle text-muted"><i class="bi bi-pause-circle me-1"></i>Inaktiv</span>
                                                <?php endif; ?>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <?php if (!empty($boat['is_active'])): ?>
                                            <span class="badge bg-success-subtle text-success">Aktiv</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-muted">Inaktiv</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="?route=admin/boating/boats/edit&id=<?= (int) $boat['id'] ?>" class="btn btn-outline-secondary" title="Bearbeiten">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($canManage): ?>
                                            <form method="post" action="?route=admin/boating/boats/delete" class="d-inline" onsubmit="return confirm('Möchten Sie dieses Boot wirklich löschen?');">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="id" value="<?= (int) $boat['id'] ?>">
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

<!-- Modal: Boot erstellen -->
<?php if ($canManage): ?>
<div class="modal fade" id="createBoatModal" tabindex="-1" aria-labelledby="createBoatModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" action="?route=admin/boating/boats/create" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="createBoatModalLabel">
                        <i class="bi bi-water text-primary me-2"></i>Neues Boot anlegen
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>
                
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Anzeigename des Boots <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="z. B. Sea Ray 210 SPX oder Bénéteau First 24" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Kennzeichen / Schiffsnummer</label>
                            <input type="text" name="registration" class="form-control" placeholder="z. B. ZH-1234 oder BS-5678">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Bootstyp</label>
                            <select name="type" class="form-select">
                                <option value="motor">Motorboot</option>
                                <option value="sail">Segelboot</option>
                                <option value="rib">RIB / Festrumpfschlauchboot</option>
                                <option value="electric">Elektroboot</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Kapazität (Personen)</label>
                            <input type="number" name="capacity" class="form-control" placeholder="z. B. 6" min="1" max="100">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Motorleistung (PS/kW)</label>
                            <input type="number" name="engine_power" class="form-control" placeholder="z. B. 150" min="0">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Farbe</label>
                            <input type="text" name="color" class="form-control" placeholder="z. B. Weiß / Blau">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Boot-Bild (optional, JPG/PNG/WebP, max. 5MB)</label>
                            <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Notizen / Ausstattung</label>
                            <textarea name="notes" rows="3" class="form-control" placeholder="z. B. Ausgerüstet mit Bugstrahlruder, Echolot, Schwimmwesten etc."></textarea>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="boatActiveSwitch" value="1" checked>
                                <label class="form-check-label small fw-semibold" for="boatActiveSwitch">Boot sofort als aktiv markieren</label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-sm btn-primary fw-semibold px-3">Boot speichern</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require dirname(__DIR__, 3) . '/views/layouts/admin.php';
