<?php

declare(strict_types=1);

/**
 * Bootsfahrschule - Gutscheine & Rabattcodes Verwaltung (bs_promo_codes)
 *
 * @var array<int, array<string, mixed>> $promos
 * @var int $totalPromos
 * @var array<string, mixed> $filters
 */

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$title = 'Gutscheine & Rabattcodes - Nautic';
$currentRoute = 'admin/boating/promos';
$canManage = BoatingSchoolService::canManage();
$csrfToken = class_exists('Csrf') ? Csrf::getToken() : '';

$activeCount = 0;
$totalUsedCount = 0;
foreach ($promos as $p) {
    if (!empty($p['is_active'])) {
        $activeCount++;
    }
    $totalUsedCount += (int) ($p['used_count'] ?? 0);
}

ob_start();
?>

<?php if ($flashSuccess): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-center">
            <i class="bi bi-check-circle-fill text-success fs-5 me-2"></i>
            <div><?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>
    </div>
<?php endif; ?>

<?php if ($flashError): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-center">
            <i class="bi bi-exclamation-triangle-fill text-danger fs-5 me-2"></i>
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
                <li class="breadcrumb-item"><a href="?route=admin/boating/services">Leistungen &amp; Tarife</a></li>
                <li class="breadcrumb-item active" aria-current="page">Gutscheine &amp; Rabattcodes</li>
            </ol>
        </nav>
        <h1 class="h3 fw-bold text-dark mb-0">
            <i class="bi bi-ticket-perforated-fill text-success me-2"></i>Gutscheine &amp; Rabattcodes (<?= count($promos) ?>)
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
                <li><a class="dropdown-item rounded-2 py-2" href="?route=admin/boating/services"><i class="bi bi-tag me-2 text-danger"></i>Leistungen &amp; Tarife</a></li>
                <li><a class="dropdown-item rounded-2 py-2 active" href="?route=admin/boating/promos"><i class="bi bi-ticket-perforated me-2 text-success"></i>Gutscheine &amp; Codes</a></li>
                <li><a class="dropdown-item rounded-2 py-2" href="?route=admin/boating/customers"><i class="bi bi-people me-2 text-success"></i>Kunden &amp; Schüler CRM</a></li>
                <li><a class="dropdown-item rounded-2 py-2" href="?route=admin/boating/reports"><i class="bi bi-graph-up-arrow me-2 text-secondary"></i>Auswertungen &amp; Payroll</a></li>
                <li><a class="dropdown-item rounded-2 py-2" href="?route=admin/boating/settings"><i class="bi bi-gear me-2"></i>Einstellungen</a></li>
            </ul>
        </div>
        <a href="?route=admin/boating/services" class="btn btn-outline-secondary btn-sm rounded-3 px-3">
            <i class="bi bi-arrow-left me-1"></i> Zu den Leistungen
        </a>
        <?php if ($canManage): ?>
            <button type="button" class="btn btn-success btn-sm rounded-3 px-3 fw-bold shadow-xs" data-bs-toggle="modal" data-bs-target="#createPromoModal">
                <i class="bi bi-plus-lg me-1"></i> Neuer Rabattcode
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Sub-Tabs: Angebote vs Gutscheine -->
<div class="mb-4">
    <div class="bg-white p-1.5 rounded-3 border shadow-xs d-inline-flex gap-1">
        <a href="?route=admin/boating/services" class="btn btn-sm btn-light text-secondary fw-semibold px-3 py-1.5 rounded-2 d-flex align-items-center gap-1.5">
            <i class="bi bi-tag text-primary"></i>
            <span>1. Leistungen &amp; Tarife</span>
        </a>
        <a href="?route=admin/boating/promos" class="btn btn-sm btn-success fw-bold px-3 py-1.5 rounded-2 d-flex align-items-center gap-1.5 shadow-xs">
            <i class="bi bi-ticket-perforated-fill"></i>
            <span>2. Gutscheine &amp; Rabattcodes</span>
            <span class="badge bg-white text-success ms-1"><?= count($promos) ?></span>
        </a>
    </div>
</div>

<!-- KPI Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-md-4">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
            <div class="text-xs text-muted fw-semibold text-uppercase">Aktive Rabattcodes</div>
            <div class="h3 fw-bold text-success mt-1 mb-0"><?= $activeCount ?> / <?= count($promos) ?></div>
            <div class="small text-muted mt-1">Im Store oder Checkout sofort anwendbar</div>
        </div>
    </div>
    <div class="col-sm-6 col-md-4">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
            <div class="text-xs text-muted fw-semibold text-uppercase">Erfolgreiche Einlösungen</div>
            <div class="h3 fw-bold text-dark mt-1 mb-0"><?= $totalUsedCount ?>x</div>
            <div class="small text-muted mt-1">Von Kunden &amp; Schülern verwendet</div>
        </div>
    </div>
    <div class="col-sm-6 col-md-4">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
            <div class="text-xs text-muted fw-semibold text-uppercase">Rabatt-Formate</div>
            <div class="h3 fw-bold text-primary mt-1 mb-0">% &amp; CHF</div>
            <div class="small text-muted mt-1">Prozentuale Abzüge oder feste CHF-Beträge</div>
        </div>
    </div>
</div>

<!-- Filter-Leiste -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="get" class="row g-2 align-items-center">
            <input type="hidden" name="route" value="admin/boating/promos">
            <div class="col-md-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Aktionscode oder Beschreibung suchen..." value="<?= htmlspecialchars((string) ($filters['search'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="is_active" class="form-select form-select-sm">
                    <option value="">Status: Alle</option>
                    <option value="1" <?= ($filters['is_active'] ?? '') === '1' ? 'selected' : '' ?>>Nur aktive Codes</option>
                    <option value="0" <?= ($filters['is_active'] ?? '') === '0' ? 'selected' : '' ?>>Nur inaktive Codes</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-secondary flex-grow-1">Filtern</button>
                <?php if (!empty($filters['search']) || (isset($filters['is_active']) && $filters['is_active'] !== '')): ?>
                    <a href="?route=admin/boating/promos" class="btn btn-sm btn-outline-secondary" title="Zurücksetzen">
                        <i class="bi bi-x-lg"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Promo Codes Tabelle -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($promos)): ?>
            <div class="text-center py-5">
                <i class="bi bi-ticket-perforated text-muted fs-1 d-block mb-2"></i>
                <h6 class="text-muted mb-2">Keine Gutschein- oder Rabattcodes gefunden</h6>
                <p class="text-muted small mb-3">Erstellen Sie Aktionscodes für saisonale Rabatte, Messe-Gutscheine oder Freundschaftswerbung.</p>
                <?php if ($canManage): ?>
                    <button type="button" class="btn btn-sm btn-success fw-bold px-3" data-bs-toggle="modal" data-bs-target="#createPromoModal">
                        <i class="bi bi-plus-lg me-1"></i> Ersten Rabattcode anlegen
                    </button>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th>Gutscheincode</th>
                            <th>Beschreibung</th>
                            <th>Rabattart</th>
                            <th>Rabattwert</th>
                            <th>Mindestbestellwert</th>
                            <th>Einlösungen</th>
                            <th>Gültigkeit</th>
                            <th>Status</th>
                            <th class="text-end" style="width: 120px;">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($promos as $promo): ?>
                            <tr>
                                <td>
                                    <span class="font-monospace fw-bold text-dark px-2.5 py-1 bg-light border border-secondary-subtle rounded-3 d-inline-block">
                                        <?= htmlspecialchars((string) $promo['code'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($promo['description'])): ?>
                                        <div class="small text-dark"><?= htmlspecialchars((string) $promo['description'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (($promo['discount_type'] ?? '') === 'percentage'): ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                            Prozentual (%)
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            Fixbetrag (CHF)
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">
                                        <?php if (($promo['discount_type'] ?? '') === 'percentage'): ?>
                                            <?= (int) ($promo['discount_value'] ?? 0) ?>% Rabatt
                                        <?php else: ?>
                                            CHF <?= number_format((float) ($promo['discount_value'] ?? 0), 2, '.', "'") ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($promo['min_order_amount']) && (float) $promo['min_order_amount'] > 0): ?>
                                        <span class="small text-dark font-monospace">ab CHF <?= number_format((float) $promo['min_order_amount'], 2, '.', "'") ?></span>
                                    <?php else: ?>
                                        <span class="text-muted small">Kein Min.-Wert</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="fw-bold text-dark"><?= (int) ($promo['used_count'] ?? 0) ?></span>
                                    <span class="text-muted small"> / <?= !empty($promo['max_uses']) ? (int) $promo['max_uses'] : '∞' ?></span>
                                </td>
                                <td>
                                    <div class="small text-dark">
                                        <?= !empty($promo['valid_from']) ? date('d.m.Y', strtotime((string) $promo['valid_from'])) : 'sofort' ?>
                                        bis
                                        <?= !empty($promo['valid_until']) ? date('d.m.Y', strtotime((string) $promo['valid_until'])) : 'unbefristet' ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($promo['is_active'])): ?>
                                        <span class="badge bg-success">Aktiv</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inaktiv</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($canManage): ?>
                                        <div class="d-flex align-items-center justify-content-end gap-1">
                                            <form method="post" action="?route=admin/boating/promos/toggle-status" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="id" value="<?= (int) $promo['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-secondary py-1 px-2" title="<?= !empty($promo['is_active']) ? 'Deaktivieren' : 'Aktivieren' ?>">
                                                    <i class="bi <?= !empty($promo['is_active']) ? 'bi-pause-fill text-warning' : 'bi-play-fill text-success' ?>"></i>
                                                </button>
                                            </form>

                                            <form method="post" action="?route=admin/boating/promos/delete" class="d-inline" onsubmit="return confirm('Rabattcode wirklich löschen?');">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="id" value="<?= (int) $promo['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2" title="Löschen">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
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

<!-- Modal: Neuen Rabattcode anlegen -->
<?php if ($canManage): ?>
<div class="modal fade" id="createPromoModal" tabindex="-1" aria-labelledby="createPromoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="post" action="?route=admin/boating/promos/create">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="createPromoModalLabel">
                        <i class="bi bi-ticket-perforated-fill text-success me-2"></i>Neuen Gutschein- / Rabattcode anlegen
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>
                
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Gutscheincode <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control font-monospace text-uppercase fw-bold" required placeholder="z. B. BOOT50, SOMMER2026" style="letter-spacing: 0.05em;">
                            <div class="form-text small">Wird automatisch in Grossbuchstaben umgewandelt.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Rabatt-Typ <span class="text-danger">*</span></label>
                            <select name="discount_type" class="form-select" id="discountTypeSelect">
                                <option value="percentage">Prozentualer Rabatt (% auf Gesamtwert)</option>
                                <option value="fixed">Fixbetrag in CHF (z. B. CHF 50.- Gutschein)</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Rabattwert <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="discount_value" class="form-control fw-bold" step="0.5" min="0" placeholder="z. B. 10 oder 50" required>
                                <span class="input-group-text" id="discountUnitAddon">% / CHF</span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Mindestbestellwert (CHF)</label>
                            <div class="input-group">
                                <input type="number" name="min_order_amount" class="form-control" step="10" min="0" placeholder="z. B. 300.00 (optional)">
                                <span class="input-group-text">CHF</span>
                            </div>
                            <div class="form-text small">Leer lassen, wenn kein Mindestumsatz nötig ist.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Maximal-Einlösungen (Kontingent)</label>
                            <input type="number" name="max_uses" class="form-control" min="1" placeholder="z. B. 50 (leer = unbegrenzt)">
                            <div class="form-text small">Wie oft darf dieser Code insgesamt eingelöst werden?</div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Gültig ab</label>
                            <input type="date" name="valid_from" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Gültig bis</label>
                            <input type="date" name="valid_until" class="form-control" value="<?= date('Y-12-31') ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Interne Beschreibung / Kampagne</label>
                            <input type="text" name="description" class="form-control" placeholder="z. B. Frühbucherrabatt Bootsfahrschule Saison 2026">
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="promoActiveSwitch" value="1" checked>
                                <label class="form-check-label small fw-semibold" for="promoActiveSwitch">Gutscheincode ist ab sofort aktiv &amp; einlösbar</label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-sm btn-success fw-semibold px-4">Rabattcode erstellen</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../../../views/layouts/admin.php';
