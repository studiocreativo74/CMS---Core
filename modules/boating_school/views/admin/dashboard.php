<?php

declare(strict_types=1);

/**
 * Bootsfahrschule - Dashboard & Zentrale Admin-Kommandozentrale
 *
 * @var array<string, int> $stats
 * @var array<int, array<string, mixed>> $recentBoats
 * @var array<int, array<string, mixed>> $recentInstructors
 * @var array<int, array<string, mixed>> $recentCustomers
 * @var array<int, array<string, mixed>> $recentPromos
 * @var array<int, array<string, mixed>> $recentServices
 * @var array<int, array<string, mixed>> $pendingRequests
 * @var array<string, string> $availableCategories
 * @var bool $tablesCreated
 */

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$title = 'Nautic - Admin Dashboard & Kommandozentrale';
$currentRoute = 'admin/boating';
$canManage = BoatingSchoolService::canManage();
$csrfToken = class_exists('Csrf') ? Csrf::getToken() : '';

$recentPromos = $recentPromos ?? (class_exists('BoatingSchoolRepository') ? BoatingSchoolRepository::getPromoCodes() : []);
$recentServices = $recentServices ?? (class_exists('ServiceService') ? ServiceService::getAll([], 6, 0) : []);
$pendingRequests = $pendingRequests ?? (class_exists('NauticAccountRequestService') 
    ? (method_exists('NauticAccountRequestService', 'getPendingRequests') ? NauticAccountRequestService::getPendingRequests() : NauticAccountRequestService::getAll('pending')) 
    : []);
$availableCategories = $availableCategories ?? (class_exists('ServiceService') ? ServiceService::getAllAvailableCategories() : []);

$activePromosCount = 0;
$totalPromoUsed = 0;
foreach ($recentPromos as $pr) {
    if (!empty($pr['is_active'])) $activePromosCount++;
    $totalPromoUsed += (int) ($pr['used_count'] ?? 0);
}

ob_start();
?>

<?php 
$breadcrumbCurrent = 'Admin Dashboard & Zentrale';
$navTitle = 'Nautic – Admin Dashboard & Kommandozentrale';
$navSubtitle = 'Alle operativen Aktionen, Gutscheine, Tarife, Account-Freigaben, Flotte und Disposition an einem Ort.';
require __DIR__ . '/_nautic_nav.php'; 
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

<!-- 1. ZENTRALE SCHNELLAKTIONEN-LEISTE (Kein Hin- und Herwechseln nötig!) -->
<div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
    <div class="card-body p-3 p-md-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
            <div>
                <h6 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                    <i class="bi bi-lightning-charge-fill text-warning fs-5"></i>
                    <span>Zentrale Schnellaktionen &amp; Sofort-Erfassung</span>
                </h6>
                <p class="text-muted small mb-0">Erfassen und bearbeiten Sie Gutscheine, Tarife, Termine und Freigaben direkt hier im Admin-Dashboard.</p>
            </div>
            <span class="badge bg-light text-muted border px-2.5 py-1.5 small font-monospace">
                <i class="bi bi-shield-check me-1 text-success"></i>Admin Command Center
            </span>
        </div>

        <div class="d-flex flex-wrap gap-2 pt-1">
            <?php if ($canManage): ?>
                <button type="button" class="btn btn-success btn-sm fw-bold px-3 py-2 rounded-3 shadow-xs d-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#createPromoModal">
                    <i class="bi bi-ticket-perforated-fill"></i>
                    <span>+ Neuer Gutschein / Rabattcode</span>
                </button>

                <button type="button" class="btn btn-primary btn-sm fw-bold px-3 py-2 rounded-3 shadow-xs d-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#createServiceModal">
                    <i class="bi bi-plus-circle-fill"></i>
                    <span>+ Neue Leistung / Tarif anlegen</span>
                </button>
            <?php endif; ?>

            <a href="?route=admin/boating/calendar" class="btn btn-outline-dark btn-sm fw-semibold px-3 py-2 rounded-3 d-flex align-items-center gap-1.5">
                <i class="bi bi-calendar3 text-info"></i>
                <span>Kalender &amp; Disposition</span>
            </a>

            <a href="?route=admin/boating/account-requests" class="btn btn-outline-secondary btn-sm fw-semibold px-3 py-2 rounded-3 d-flex align-items-center gap-1.5 position-relative">
                <i class="bi bi-person-check-fill text-warning"></i>
                <span>Account-Anfragen</span>
                <?php if (!empty($pendingRequests)): ?>
                    <span class="badge bg-danger ms-1"><?= count($pendingRequests) ?></span>
                <?php endif; ?>
            </a>

            <a href="?route=admin/boating/reports&tab=payroll" class="btn btn-outline-secondary btn-sm fw-semibold px-3 py-2 rounded-3 d-flex align-items-center gap-1.5">
                <i class="bi bi-file-earmark-spreadsheet text-success"></i>
                <span>Lohnabrechnungen &amp; Timesheets</span>
            </a>
        </div>
    </div>
</div>

<!-- 2. KPI COCKPIT (5 KARTEN INKL. GUTSCHEINE) -->
<div class="row g-3 mb-4">
    <!-- Gutscheine & Rabattcodes -->
    <div class="col-sm-6 col-xl">
        <div class="card border-0 shadow-sm h-100 rounded-3 border-start border-4 border-success">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge bg-success bg-opacity-10 text-success fw-semibold px-2 py-1">
                        <i class="bi bi-ticket-perforated me-1"></i>Gutscheine &amp; Codes
                    </span>
                    <a href="?route=admin/boating/promos" class="small text-muted text-decoration-none">Verwalten &rarr;</a>
                </div>
                <div class="d-flex align-items-baseline gap-2">
                    <div class="h3 fw-bold mb-0 text-dark"><?= $activePromosCount ?></div>
                    <div class="small text-muted">aktiv / <?= count($recentPromos) ?> Total</div>
                </div>
                <div class="small text-success mt-2">
                    <i class="bi bi-check2-all me-1"></i><?= $totalPromoUsed ?>x im Store eingelöst
                </div>
            </div>
        </div>
    </div>

    <!-- Leistungen & Tarife -->
    <div class="col-sm-6 col-xl">
        <div class="card border-0 shadow-sm h-100 rounded-3">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge bg-warning bg-opacity-10 text-warning fw-semibold px-2 py-1">
                        <i class="bi bi-tag-fill me-1"></i>Leistungen &amp; Tarife
                    </span>
                    <a href="?route=admin/boating/services" class="small text-muted text-decoration-none">Verwalten &rarr;</a>
                </div>
                <div class="d-flex align-items-baseline gap-2">
                    <div class="h3 fw-bold mb-0 text-dark"><?= (int) ($stats['services_total'] ?? 0) ?></div>
                    <div class="small text-muted">Angebote</div>
                </div>
                <div class="small text-muted mt-2">
                    Praxis, Theorie, Abos &amp; Mieten
                </div>
            </div>
        </div>
    </div>

    <!-- Fahrlehrer & Coaches -->
    <div class="col-sm-6 col-xl">
        <div class="card border-0 shadow-sm h-100 rounded-3">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge bg-info bg-opacity-10 text-info fw-semibold px-2 py-1">
                        <i class="bi bi-person-badge-fill me-1"></i>Fahrlehrer
                    </span>
                    <a href="?route=admin/boating/instructors" class="small text-muted text-decoration-none">Team &rarr;</a>
                </div>
                <div class="d-flex align-items-baseline gap-2">
                    <div class="h3 fw-bold mb-0 text-dark"><?= (int) ($stats['instructors_total'] ?? 0) ?></div>
                    <div class="small text-muted">Instruktoren</div>
                </div>
                <div class="small text-success mt-2">
                    <i class="bi bi-check-circle-fill me-1"></i><?= (int) ($stats['instructors_active'] ?? 0) ?> aktiv im Einsatz
                </div>
            </div>
        </div>
    </div>

    <!-- Boote & Flotte -->
    <div class="col-sm-6 col-xl">
        <div class="card border-0 shadow-sm h-100 rounded-3">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold px-2 py-1">
                        <i class="bi bi-tsunami me-1"></i>Bootsflotte
                    </span>
                    <a href="?route=admin/boating/boats" class="small text-muted text-decoration-none">Flotte &rarr;</a>
                </div>
                <div class="d-flex align-items-baseline gap-2">
                    <div class="h3 fw-bold mb-0 text-dark"><?= (int) ($stats['boats_total'] ?? 0) ?></div>
                    <div class="small text-muted">Schiffe</div>
                </div>
                <div class="small text-success mt-2">
                    <i class="bi bi-check-circle-fill me-1"></i><?= (int) ($stats['boats_active'] ?? 0) ?> einsatzbereit
                </div>
            </div>
        </div>
    </div>

    <!-- Schüler / Kunden -->
    <div class="col-sm-6 col-xl">
        <div class="card border-0 shadow-sm h-100 rounded-3">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge bg-secondary bg-opacity-10 text-dark fw-semibold px-2 py-1">
                        <i class="bi bi-people-fill me-1"></i>Kunden &amp; Schüler
                    </span>
                    <a href="?route=admin/boating/customers" class="small text-muted text-decoration-none">CRM &rarr;</a>
                </div>
                <div class="d-flex align-items-baseline gap-2">
                    <div class="h3 fw-bold mb-0 text-dark"><?= (int) ($stats['customers_total'] ?? 0) ?></div>
                    <div class="small text-muted">im System</div>
                </div>
                <div class="small text-muted mt-2">
                    <?= (int) ($stats['students_total'] ?? 0) ?> Schüler &bull; <?= (int) ($stats['renters_total'] ?? 0) ?> Mieter
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 3. ACCOUNT-ANFRAGEN & FREIGABEN DIREKT IM DASHBOARD -->
<?php if (!empty($pendingRequests)): ?>
    <div class="card border-0 shadow-sm rounded-4 mb-4 border-start border-4 border-warning">
        <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-warning text-dark px-2.5 py-1 rounded-pill fw-bold">
                    <?= count($pendingRequests) ?> Neue Anfragen
                </span>
                <h6 class="fw-bold mb-0 text-dark">Registrierungen &amp; Account-Freigaben</h6>
            </div>
            <a href="?route=admin/boating/account-requests" class="small text-muted text-decoration-none">Zur Vollansicht &rarr;</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>Name / E-Mail</th>
                            <th>Telefon</th>
                            <th>Gewünschte Rolle</th>
                            <th>Notizen / Bewerbung</th>
                            <th>Eingereicht</th>
                            <th class="text-end" style="width: 220px;">Direkt-Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingRequests as $req): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars((string) ($req['first_name'] . ' ' . $req['last_name']), ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="text-muted small"><?= htmlspecialchars((string) $req['email'], ENT_QUOTES, 'UTF-8') ?></div>
                                </td>
                                <td>
                                    <?= !empty($req['phone']) ? htmlspecialchars((string) $req['phone'], ENT_QUOTES, 'UTF-8') : '<span class="text-muted">-</span>' ?>
                                </td>
                                <td>
                                    <?php 
                                    $role = (string) ($req['desired_role'] ?? 'student');
                                    $roleBadge = match($role) {
                                        'instructor' => '<span class="badge bg-warning text-dark">Fahrlehrer / Coach</span>',
                                        'student'    => '<span class="badge bg-primary">Fahrschüler</span>',
                                        'renter'     => '<span class="badge bg-info text-dark">Bootsmieter</span>',
                                        default      => '<span class="badge bg-secondary">' . htmlspecialchars($role) . '</span>'
                                    };
                                    echo $roleBadge;
                                    ?>
                                </td>
                                <td>
                                    <div class="text-truncate text-muted small" style="max-width: 260px;">
                                        <?= !empty($req['notes']) ? htmlspecialchars((string) $req['notes'], ENT_QUOTES, 'UTF-8') : '<span class="text-muted italic">Keine Notiz</span>' ?>
                                    </div>
                                </td>
                                <td class="text-muted small">
                                    <?= !empty($req['created_at']) ? date('d.m.Y H:i', strtotime((string) $req['created_at'])) : '-' ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex align-items-center justify-content-end gap-1.5">
                                        <form method="post" action="?route=admin/boating/account-requests/approve" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="id" value="<?= (int) $req['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-success py-1 px-2.5 fw-semibold d-flex align-items-center gap-1 shadow-2xs">
                                                <i class="bi bi-check2"></i> Freischalten
                                            </button>
                                        </form>

                                        <form method="post" action="?route=admin/boating/account-requests/reject" class="d-inline" onsubmit="return confirm('Anfrage wirklich ablehnen?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="id" value="<?= (int) $req['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2" title="Ablehnen">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- 4. ZWEI SPALTEN: GUTSCHEINE & RABATTCODES (LINKS) | LEISTUNGEN & TARIFE (RECHTS) -->
<div class="row g-4 mb-4">
    <!-- Gutscheine & Rabattcodes Direktverwaltung -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100 rounded-3">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-ticket-perforated-fill text-success fs-5"></i>
                    <h6 class="fw-bold mb-0 text-dark">Gutscheine &amp; Rabattcodes</h6>
                    <span class="badge bg-light text-muted border"><?= count($recentPromos) ?></span>
                </div>
                <div class="d-flex align-items-center gap-1.5">
                    <?php if ($canManage): ?>
                        <button type="button" class="btn btn-sm btn-success py-1 px-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#createPromoModal">
                            <i class="bi bi-plus-lg me-1"></i> Neu
                        </button>
                    <?php endif; ?>
                    <a href="?route=admin/boating/promos" class="btn btn-sm btn-outline-secondary py-1 px-2">Alle</a>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentPromos)): ?>
                    <div class="p-4 text-center text-muted small">
                        Keine Rabattcodes erfasst.
                        <?php if ($canManage): ?>
                            <br><button type="button" class="btn btn-sm btn-success mt-2 fw-bold" data-bs-toggle="modal" data-bs-target="#createPromoModal">Ersten Rabattcode anlegen</button>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Code</th>
                                    <th>Rabatt</th>
                                    <th>Nutzung</th>
                                    <th>Status</th>
                                    <th class="text-end" style="width: 80px;">Aktion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($recentPromos, 0, 5) as $promo): ?>
                                    <tr>
                                        <td>
                                            <span class="font-monospace fw-bold text-dark px-2 py-0.5 bg-light border rounded">
                                                <?= htmlspecialchars((string) $promo['code'], ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                            <?php if (!empty($promo['description'])): ?>
                                                <div class="text-muted small text-truncate" style="max-width: 140px;">
                                                    <?= htmlspecialchars((string) $promo['description'], ENT_QUOTES, 'UTF-8') ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-dark">
                                                <?= ($promo['discount_type'] ?? '') === 'percentage' ? ((int) $promo['discount_value'] . '%') : ('CHF ' . number_format((float) $promo['discount_value'], 2, '.', "'")) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-dark font-semibold"><?= (int) ($promo['used_count'] ?? 0) ?></span>
                                            <span class="text-muted small">/ <?= !empty($promo['max_uses']) ? (int) $promo['max_uses'] : '∞' ?></span>
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
                                                <form method="post" action="?route=admin/boating/promos/toggle-status" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                    <input type="hidden" name="id" value="<?= (int) $promo['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary py-0.5 px-1.5" title="Status umschalten">
                                                        <i class="bi <?= !empty($promo['is_active']) ? 'bi-pause-fill text-warning' : 'bi-play-fill text-success' ?>"></i>
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

    <!-- Leistungen & Tarife Direktverwaltung -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100 rounded-3">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-tag-fill text-primary fs-5"></i>
                    <h6 class="fw-bold mb-0 text-dark">Leistungen &amp; Tarife</h6>
                    <span class="badge bg-light text-muted border"><?= count($recentServices) ?></span>
                </div>
                <div class="d-flex align-items-center gap-1.5">
                    <?php if ($canManage): ?>
                        <button type="button" class="btn btn-sm btn-primary py-1 px-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#createServiceModal">
                            <i class="bi bi-plus-lg me-1"></i> Neu
                        </button>
                    <?php endif; ?>
                    <a href="?route=admin/boating/services" class="btn btn-sm btn-outline-secondary py-1 px-2">Alle</a>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentServices)): ?>
                    <div class="p-4 text-center text-muted small">
                        Keine Leistungen hinterlegt.
                        <?php if ($canManage): ?>
                            <br><button type="button" class="btn btn-sm btn-primary mt-2 fw-bold" data-bs-toggle="modal" data-bs-target="#createServiceModal">Erste Leistung anlegen</button>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Leistung</th>
                                    <th>Kategorie</th>
                                    <th>Dauer</th>
                                    <th>Preis</th>
                                    <th class="text-end" style="width: 80px;">Aktion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($recentServices, 0, 5) as $srv): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark text-truncate" style="max-width: 170px;">
                                                <?= htmlspecialchars((string) $srv['name'], ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                <?= htmlspecialchars(BoatingSchoolService::getCategoryLabel((string) ($srv['category'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?= !empty($srv['duration_minutes']) ? ((int) $srv['duration_minutes'] . ' Min.') : '-' ?>
                                        </td>
                                        <td class="fw-bold text-dark">
                                            <?= BoatingSchoolService::formatPrice((float) ($srv['price'] ?? 0.0)) ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="?route=admin/boating/services/edit&id=<?= (int) $srv['id'] ?>" class="btn btn-sm btn-outline-secondary py-0.5 px-1.5" title="Bearbeiten">
                                                <i class="bi bi-pencil"></i>
                                            </a>
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
</div>

<!-- 5. FLOTTE & FAHRLEHRER TEASER TABELLEN -->
<div class="row g-4 mb-4">
    <!-- Boote Übersicht -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100 rounded-3">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-tsunami text-primary me-2"></i>Aktuelle Flotte
                </h6>
                <a href="?route=admin/boating/boats" class="btn btn-sm btn-outline-secondary py-1 px-2">Alle Boote</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentBoats)): ?>
                    <div class="p-4 text-center text-muted small">Keine Boote erfasst.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Boot</th>
                                    <th>Typ / Reg.</th>
                                    <th>Kapazität</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentBoats as $boat): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars((string) $boat['name'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <?php if (!empty($boat['engine_power'])): ?>
                                                <div class="text-muted small"><?= (int) $boat['engine_power'] ?> PS/kW</div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= BoatingSchoolService::getBoatTypeBadge((string) ($boat['type'] ?? '')) ?>
                                        </td>
                                        <td>
                                            <?= !empty($boat['capacity']) ? (int) $boat['capacity'] . ' Pers.' : '-' ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($boat['is_active'])): ?>
                                                <span class="badge bg-success-subtle text-success">Aktiv</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-muted">Inaktiv</span>
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

    <!-- Fahrlehrer Übersicht -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100 rounded-3">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-person-badge text-primary me-2"></i>Fahrlehrer &amp; Skipper
                </h6>
                <a href="?route=admin/boating/instructors" class="btn btn-sm btn-outline-secondary py-1 px-2">Alle Instruktoren</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentInstructors)): ?>
                    <div class="p-4 text-center text-muted small">Keine Fahrlehrer erfasst.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Fahrlehrer</th>
                                    <th>Lizenz / Rolle</th>
                                    <th>Kontakt</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentInstructors as $inst): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="rounded-circle d-inline-block flex-shrink-0" style="width: 12px; height: 12px; background-color: <?= htmlspecialchars((string) ($inst['color_code'] ?? '#0d6efd'), ENT_QUOTES, 'UTF-8') ?>;"></span>
                                                <div class="fw-semibold text-dark">
                                                    <?= htmlspecialchars((string) $inst['first_name'] . ' ' . (string) $inst['last_name'], ENT_QUOTES, 'UTF-8') ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="text-truncate text-muted small" style="max-width: 160px;">
                                                <?= htmlspecialchars((string) (!empty($inst['title']) ? $inst['title'] : 'Fahrcoach'), ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                        </td>
                                        <td class="small">
                                            <?= !empty($inst['phone']) ? htmlspecialchars((string) $inst['phone']) : htmlspecialchars((string) ($inst['email'] ?? '-')) ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($inst['is_active'])): ?>
                                                <span class="badge bg-success-subtle text-success">Aktiv</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-muted">Inaktiv</span>
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
</div>

<!-- ======================================================================= -->
<!-- MODALE DIREKT IM DASHBOARD (KEIN HIN- UND HERWECHSELN MEHR NÖTIG!)      -->
<!-- ======================================================================= -->

<!-- Modal 1: Neuen Rabattcode anlegen -->
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
                            <select name="discount_type" class="form-select">
                                <option value="percentage">Prozentualer Rabatt (% auf Gesamtwert)</option>
                                <option value="fixed">Fixbetrag in CHF (z. B. CHF 50.- Gutschein)</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Rabattwert <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="discount_value" class="form-control fw-bold" step="0.5" min="0" placeholder="z. B. 10 oder 50" required>
                                <span class="input-group-text">% / CHF</span>
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
                                <input class="form-check-input" type="checkbox" name="is_active" id="dashPromoActiveSwitch" value="1" checked>
                                <label class="form-check-label small fw-semibold" for="dashPromoActiveSwitch">Gutscheincode ist ab sofort aktiv &amp; einlösbar</label>
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

<!-- Modal 2: Neue Leistung anlegen (Kategorie frei eingebbar, KEIN Bootstyp!) -->
<div class="modal fade" id="createServiceModal" tabindex="-1" aria-labelledby="createServiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
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
                                <input type="text" name="category" id="dashCreateServiceCategory" list="dashServiceCategoryList" class="form-control" placeholder="z. B. Praxisunterricht, VIP-Coaching, Bootsmiete..." required>
                                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Vorschläge anzeigen"></button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="max-height: 240px; overflow-y: auto;">
                                    <?php foreach ($availableCategories as $catKey => $catLabel): ?>
                                        <li>
                                            <a class="dropdown-item py-1.5 small" href="javascript:void(0)" onclick="document.getElementById('dashCreateServiceCategory').value = <?= htmlspecialchars(json_encode($catLabel), ENT_QUOTES, 'UTF-8') ?>">
                                                <?= htmlspecialchars($catLabel, ENT_QUOTES, 'UTF-8') ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <datalist id="dashServiceCategoryList">
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
                                <input class="form-check-input" type="checkbox" name="is_active" id="dashSrvActiveSwitch" value="1" checked>
                                <label class="form-check-label small fw-semibold" for="dashSrvActiveSwitch">Leistung ist aktiv buchbar</label>
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
