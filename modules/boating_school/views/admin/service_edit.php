<?php

declare(strict_types=1);

/**
 * Bootsfahrschule - Leistung bearbeiten
 *
 * @var array<string, mixed> $service
 */

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$serviceName = htmlspecialchars((string) $service['name'], ENT_QUOTES, 'UTF-8');
$title = 'Leistung bearbeiten: ' . $serviceName;
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
                <li class="breadcrumb-item"><a href="?route=admin/boating/services">Leistungen</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= $serviceName ?></li>
            </ol>
        </nav>
        <h1 class="h3 fw-bold text-dark mb-0">
            <i class="bi bi-tag-fill text-primary me-2"></i>Leistung bearbeiten: <?= $serviceName ?>
        </h1>
    </div>
    <div class="d-flex gap-2">
        <a href="?route=admin/boating/services" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Zurück zur Übersicht
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="post" action="?route=admin/boating/services/update">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="id" value="<?= (int) $service['id'] ?>">

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Bezeichnung der Leistung <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars((string) $service['name'], ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Artikel- / Kurskürzel</label>
                            <input type="text" name="code" class="form-control" value="<?= htmlspecialchars((string) ($service['code'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="z. B. PRAX-MOT-90">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Kategorie</label>
                            <select name="category" class="form-select">
                                <option value="practical" <?= ($service['category'] ?? '') === 'practical' ? 'selected' : '' ?>>Praxisunterricht (practical)</option>
                                <option value="theory" <?= ($service['category'] ?? '') === 'theory' ? 'selected' : '' ?>>Theoriekurs (theory)</option>
                                <option value="rental" <?= ($service['category'] ?? '') === 'rental' ? 'selected' : '' ?>>Bootsmiete (rental)</option>
                                <option value="exam" <?= ($service['category'] ?? '') === 'exam' ? 'selected' : '' ?>>Prüfungsfahrt (exam)</option>
                                <option value="package" <?= ($service['category'] ?? '') === 'package' ? 'selected' : '' ?>>Pauschale / Paket (package)</option>
                                <option value="other" <?= ($service['category'] ?? '') === 'other' ? 'selected' : '' ?>>Sonstiges (other)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Bootstyp</label>
                            <select name="boat_type" class="form-select">
                                <option value="any" <?= (($service['boat_type'] ?? '') === 'any' || empty($service['boat_type'])) ? 'selected' : '' ?>>Alle / Beliebig (any)</option>
                                <option value="motor" <?= ($service['boat_type'] ?? '') === 'motor' ? 'selected' : '' ?>>Motorboot (motor)</option>
                                <option value="sail" <?= ($service['boat_type'] ?? '') === 'sail' ? 'selected' : '' ?>>Segelboot (sail)</option>
                                <option value="rib" <?= ($service['boat_type'] ?? '') === 'rib' ? 'selected' : '' ?>>RIB / Festrumpfschlauchboot (rib)</option>
                                <option value="electric" <?= ($service['boat_type'] ?? '') === 'electric' ? 'selected' : '' ?>>Elektroboot (electric)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Dauer (in Minuten)</label>
                            <input type="number" name="duration_minutes" class="form-control" value="<?= !empty($service['duration_minutes']) ? (int) $service['duration_minutes'] : 60 ?>" min="15" step="15">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Preis brutto (CHF / EUR) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="price" class="form-control" step="0.05" min="0" value="<?= number_format((float) ($service['price'] ?? 0), 2, '.', '') ?>" required>
                                <span class="input-group-text">CHF</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">MwSt.-Satz (%)</label>
                            <input type="number" name="tax_rate" class="form-control" step="0.1" min="0" value="<?= number_format((float) ($service['tax_rate'] ?? 0), 1, '.', '') ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Beschreibung / Inhalt</label>
                            <textarea name="description" rows="4" class="form-control"><?= htmlspecialchars((string) ($service['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="srvActiveSwitch" value="1" <?= !empty($service['is_active']) ? 'checked' : '' ?>>
                                <label class="form-check-label small fw-semibold" for="srvActiveSwitch">Leistung ist aktiv buchbar</label>
                            </div>
                        </div>

                        <div class="col-12 pt-3 border-top d-flex gap-2 justify-content-end">
                            <a href="?route=admin/boating/services" class="btn btn-sm btn-secondary">Abbrechen</a>
                            <button type="submit" class="btn btn-sm btn-primary fw-semibold px-4">Änderungen speichern</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Seitenleiste mit Tarifdetails -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0 text-dark">Tarifdaten</h6>
            </div>
            <div class="card-body p-3">
                <div class="h3 fw-bold text-primary mb-1">
                    <?= BoatingSchoolService::formatPrice((float) ($service['price'] ?? 0.0)) ?>
                </div>
                <div class="text-muted small mb-3">
                    <?php if (!empty($service['tax_rate']) && (float) $service['tax_rate'] > 0): ?>
                        inkl. <?= number_format((float) $service['tax_rate'], 1) ?>% Mehrwertsteuer
                    <?php else: ?>
                        Mehrwertsteuerfrei / Exklusive
                    <?php endif; ?>
                </div>

                <div class="small">
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Leistungs-ID:</span>
                        <span class="font-monospace">#<?= (int) $service['id'] ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Kategorie:</span>
                        <span><?= htmlspecialchars(BoatingSchoolService::getCategoryLabel((string) ($service['category'] ?? '')), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Erstellt am:</span>
                        <span><?= htmlspecialchars((string) $service['created_at'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted">Aktualisiert:</span>
                        <span><?= htmlspecialchars((string) $service['updated_at'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require dirname(__DIR__, 3) . '/views/layouts/admin.php';
