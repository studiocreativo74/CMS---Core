<?php

declare(strict_types=1);

/**
 * Bootsfahrschule - Boot bearbeiten
 *
 * @var array<string, mixed> $boat
 */

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$title = 'Boot bearbeiten: ' . htmlspecialchars((string) $boat['name'], ENT_QUOTES, 'UTF-8');
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
                <li class="breadcrumb-item"><a href="?route=admin/boating">Nautic</a></li>
                <li class="breadcrumb-item"><a href="?route=admin/boating/boats">Boote</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars((string) $boat['name'], ENT_QUOTES, 'UTF-8') ?></li>
            </ol>
        </nav>
        <h1 class="h3 fw-bold text-dark mb-0">
            <i class="bi bi-water text-primary me-2"></i>Boot bearbeiten: <?= htmlspecialchars((string) $boat['name'], ENT_QUOTES, 'UTF-8') ?>
        </h1>
    </div>
    <div class="d-flex gap-2">
        <a href="?route=admin/boating/boats" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Zurück zur Flotte
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="post" action="?route=admin/boating/boats/update" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="id" value="<?= (int) $boat['id'] ?>">

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Anzeigename des Boots <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars((string) $boat['name'], ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Kennzeichen / Schiffsnummer</label>
                            <input type="text" name="registration" class="form-control" value="<?= htmlspecialchars((string) ($boat['registration'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="z. B. ZH-1234">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Bootstyp</label>
                            <select name="type" class="form-select">
                                <option value="motor" <?= ($boat['type'] ?? '') === 'motor' ? 'selected' : '' ?>>Motorboot</option>
                                <option value="sail" <?= ($boat['type'] ?? '') === 'sail' ? 'selected' : '' ?>>Segelboot</option>
                                <option value="rib" <?= ($boat['type'] ?? '') === 'rib' ? 'selected' : '' ?>>RIB / Festrumpfschlauchboot</option>
                                <option value="electric" <?= ($boat['type'] ?? '') === 'electric' ? 'selected' : '' ?>>Elektroboot</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Kapazität (Personen)</label>
                            <input type="number" name="capacity" class="form-control" value="<?= !empty($boat['capacity']) ? (int) $boat['capacity'] : '' ?>" min="1" max="100">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Motorleistung (PS/kW)</label>
                            <input type="number" name="engine_power" class="form-control" value="<?= !empty($boat['engine_power']) ? (int) $boat['engine_power'] : '' ?>" min="0">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Farbe</label>
                            <input type="text" name="color" class="form-control" value="<?= htmlspecialchars((string) ($boat['color'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Neues Boot-Bild hochladen (optional)</label>
                            <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Notizen / Ausstattung</label>
                            <textarea name="notes" rows="4" class="form-control"><?= htmlspecialchars((string) ($boat['notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="boatActiveSwitch" value="1" <?= !empty($boat['is_active']) ? 'checked' : '' ?>>
                                <label class="form-check-label small fw-semibold" for="boatActiveSwitch">Boot ist aktiv und betriebsbereit</label>
                            </div>
                        </div>

                        <div class="col-12 pt-3 border-top d-flex gap-2 justify-content-end">
                            <a href="?route=admin/boating/boats" class="btn btn-sm btn-secondary">Abbrechen</a>
                            <button type="submit" class="btn btn-sm btn-primary fw-semibold px-4">Änderungen speichern</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Seitenleiste mit Foto & Metadaten -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0 text-dark">Boot-Vorschau</h6>
            </div>
            <div class="card-body p-3 text-center">
                <?php if (!empty($boat['image_path'])): ?>
                    <img src="<?= htmlspecialchars((string) $boat['image_path'], ENT_QUOTES, 'UTF-8') ?>" alt="Boot-Bild" class="img-fluid rounded mb-3 shadow-sm" style="max-height: 220px; object-fit: cover; width: 100%;">
                <?php else: ?>
                    <div class="bg-light rounded p-5 text-muted mb-3">
                        <i class="bi bi-water fs-1 d-block mb-2"></i>
                        <span class="small">Kein Bild hinterlegt</span>
                    </div>
                <?php endif; ?>
                
                <div class="text-start small">
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Boot-ID:</span>
                        <span class="font-monospace">#<?= (int) $boat['id'] ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Typ:</span>
                        <span><?= BoatingSchoolService::getBoatTypeBadge((string) ($boat['type'] ?? '')) ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Erstellt am:</span>
                        <span><?= htmlspecialchars((string) $boat['created_at'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted">Aktualisiert:</span>
                        <span><?= htmlspecialchars((string) $boat['updated_at'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../../../views/layouts/admin.php';
