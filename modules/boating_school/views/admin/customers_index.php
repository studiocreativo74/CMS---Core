<?php

declare(strict_types=1);

/**
 * Bootsfahrschule - Schüler & Kunden Übersicht mit CRM & Import/Export
 *
 * @var array<int, array<string, mixed>> $customers
 * @var int $totalCustomers
 * @var array<string, mixed> $filters
 * @var bool $tablesCreated
 */

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$title = 'Kunden & Schüler CRM - Nautic';
$currentRoute = 'admin/boating/customers';
$canManage = BoatingSchoolService::canManage();
$canExport = BoatingSchoolService::canExportCustomers();
$canImport = BoatingSchoolService::canImportCustomers();
$csrfToken = class_exists('Csrf') ? Csrf::getToken() : '';
$users = $users ?? (class_exists('User') ? User::all() : []);

// Query-String für CSV-Export mit aktiven Filtern
$exportQuery = http_build_query(array_filter([
    'route'            => 'admin/boating/customers/export-csv',
    'search'           => $filters['search'] ?? '',
    'type'             => $filters['type'] ?? '',
    'crm_stage'        => $filters['crm_stage'] ?? '',
    'experience_level' => $filters['experience_level'] ?? '',
    'theory_passed'    => $filters['theory_passed'] ?? '',
    'practical_passed' => $filters['practical_passed'] ?? '',
], static fn($v) => $v !== ''));

ob_start();
?>

<style>
/* Scoped Customer/Student Modal Design (Nautic Brown/Navy Theme) */
.cust-modal-content {
    border: none !important;
    border-radius: 24px !important;
    overflow: hidden;
    box-shadow: 0 25px 60px -15px rgba(15, 23, 42, 0.35);
}
.cust-modal-header {
    background-color: #0f172a;
    padding: 24px 28px 20px 28px;
    border: none;
}
.cust-tab-container {
    background-color: #1e293b;
    padding: 4px;
    border-radius: 12px;
    display: inline-flex;
    gap: 4px;
}
.cust-tab-btn {
    border: none;
    font-size: 0.85rem;
    font-weight: 600;
    padding: 7px 18px;
    border-radius: 8px;
    transition: all 0.2s ease;
    cursor: pointer;
}
.cust-tab-btn.active {
    background-color: #5c3d2e !important;
    color: #ffffff !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
}
.cust-tab-btn:not(.active) {
    background-color: transparent !important;
    color: #94a3b8 !important;
}
.cust-tab-btn:not(.active):hover {
    color: #f1f5f9 !important;
}
.cust-field-label {
    display: block;
    font-size: 0.70rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748b;
    margin-bottom: 6px;
}
.cust-form-control {
    background-color: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 14px;
    padding: 10px 14px;
    font-size: 0.90rem;
    color: #0f172a;
    width: 100%;
    transition: all 0.2s ease;
}
.cust-form-control:focus {
    background-color: #ffffff;
    border-color: #5c3d2e;
    outline: none;
    box-shadow: 0 0 0 3px rgba(92, 61, 46, 0.14);
}
.cust-card-box {
    background-color: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 14px;
    padding: 12px 14px;
}
.btn-coach-brown {
    background-color: #5c3d2e !important;
    border-color: #5c3d2e !important;
    color: #ffffff !important;
    border-radius: 14px !important;
    font-weight: 600;
    transition: all 0.2s ease;
}
.btn-coach-brown:hover {
    background-color: #4a2f23 !important;
    border-color: #4a2f23 !important;
    color: #ffffff !important;
    box-shadow: 0 6px 20px rgba(92, 61, 46, 0.4);
}
.btn-coach-cancel {
    background-color: #f1f5f9 !important;
    color: #334155 !important;
    border: none !important;
    border-radius: 14px !important;
    font-weight: 600;
}
.btn-coach-cancel:hover {
    background-color: #e2e8f0 !important;
    color: #0f172a !important;
}
</style>

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
                <li class="breadcrumb-item active" aria-current="page">Kunden &amp; Schüler CRM</li>
            </ol>
        </nav>
        <h1 class="h3 fw-bold text-dark mb-0">
            <i class="bi bi-people-fill text-primary me-2"></i>Kundenkartei &amp; CRM (<?= $totalCustomers ?>)
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
                <li><a class="dropdown-item rounded-2 py-2 active" href="?route=admin/boating/customers"><i class="bi bi-people me-2 text-success"></i>Kunden &amp; Schüler CRM</a></li>
                <li><a class="dropdown-item rounded-2 py-2" href="?route=admin/boating/reports"><i class="bi bi-graph-up-arrow me-2 text-secondary"></i>Auswertungen &amp; Payroll</a></li>
                <li><a class="dropdown-item rounded-2 py-2" href="?route=admin/boating/settings"><i class="bi bi-gear me-2"></i>Einstellungen</a></li>
            </ul>
        </div>
        <a href="?route=admin/boating" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Dashboard
        </a>
        <?php if ($canExport): ?>
            <a href="?<?= $exportQuery ?>" class="btn btn-outline-success btn-sm" title="Aktuelle Auswahl als CSV herunterladen">
                <i class="bi bi-file-earmark-excel me-1"></i> CSV Export
            </a>
        <?php endif; ?>
        <?php if ($canImport): ?>
            <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#importCsvModal">
                <i class="bi bi-cloud-arrow-up me-1"></i> CSV Import
            </button>
        <?php endif; ?>
        <?php if ($canManage): ?>
            <button type="button" class="btn btn-coach-brown btn-sm px-3 shadow-sm d-flex align-items-center gap-2" id="btnOpenCreateCustomer">
                <i class="bi bi-person-plus-fill"></i> Kunde / Schüler anlegen
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Filter-Leiste -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="get" class="row g-2 align-items-center">
            <input type="hidden" name="route" value="admin/boating/customers">
            <div class="col-lg-3 col-md-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Name, KD-Nr, Firma, Ort, Tags..." value="<?= htmlspecialchars((string) ($filters['search'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>
            <div class="col-lg-2 col-md-3">
                <select name="type" class="form-select form-select-sm">
                    <option value="">Alle Kundentypen</option>
                    <option value="student" <?= ($filters['type'] ?? '') === 'student' ? 'selected' : '' ?>>Fahrschüler</option>
                    <option value="renter" <?= ($filters['type'] ?? '') === 'renter' ? 'selected' : '' ?>>Bootsmieter</option>
                    <option value="both" <?= ($filters['type'] ?? '') === 'both' ? 'selected' : '' ?>>Schüler &amp; Mieter</option>
                </select>
            </div>
            <div class="col-lg-2 col-md-3">
                <select name="crm_stage" class="form-select form-select-sm">
                    <option value="">Alle CRM-Stufen</option>
                    <?php foreach (CustomerService::getCrmStages() as $stageKey => $stageLabel): ?>
                        <option value="<?= htmlspecialchars($stageKey, ENT_QUOTES, 'UTF-8') ?>" <?= ($filters['crm_stage'] ?? '') === $stageKey ? 'selected' : '' ?>>
                            <?= htmlspecialchars($stageLabel, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2 col-md-3">
                <select name="theory_passed" class="form-select form-select-sm">
                    <option value="">Theorie: Alle</option>
                    <option value="1" <?= ($filters['theory_passed'] ?? '') === '1' ? 'selected' : '' ?>>Theorie bestanden</option>
                    <option value="0" <?= ($filters['theory_passed'] ?? '') === '0' ? 'selected' : '' ?>>Theorie offen</option>
                </select>
            </div>
            <div class="col-lg-2 col-md-3">
                <select name="practical_passed" class="form-select form-select-sm">
                    <option value="">Praxis: Alle</option>
                    <option value="1" <?= ($filters['practical_passed'] ?? '') === '1' ? 'selected' : '' ?>>Praxis bestanden</option>
                    <option value="0" <?= ($filters['practical_passed'] ?? '') === '0' ? 'selected' : '' ?>>Praxis offen</option>
                </select>
            </div>
            <div class="col-lg-1 col-md-6 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-secondary flex-grow-1" title="Filter anwenden">
                    <i class="bi bi-funnel"></i>
                </button>
                <?php if (!empty($filters['search']) || !empty($filters['type']) || !empty($filters['crm_stage']) || (isset($filters['theory_passed']) && $filters['theory_passed'] !== '') || (isset($filters['practical_passed']) && $filters['practical_passed'] !== '')): ?>
                    <a href="?route=admin/boating/customers" class="btn btn-sm btn-outline-secondary" title="Filter zurücksetzen">
                        <i class="bi bi-x-lg"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Kunden-Tabelle -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($customers)): ?>
            <div class="text-center py-5">
                <i class="bi bi-people text-muted fs-1 d-block mb-2"></i>
                <h6 class="text-muted mb-2">Keine Kunden oder Fahrschüler gefunden</h6>
                <p class="text-muted small mb-3">Erfassen Sie einen neuen Kunden oder importieren Sie eine CSV-Kundenliste.</p>
                <?php if ($canManage): ?>
                    <button type="button" class="btn btn-sm btn-coach-brown px-3" onclick="openCreateCustomerModal()">
                        <i class="bi bi-plus-lg me-1"></i> Jetzt anlegen
                    </button>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th style="width: 110px;">KD-Nr.</th>
                            <th>Name, Firma &amp; Typ</th>
                            <th>CRM-Status</th>
                            <th>Ziel-Führerschein &amp; Lizenzen</th>
                            <th>Prüfungsstatus</th>
                            <th>Kontakt &amp; Wohnort</th>
                            <th style="width: 80px;">Status</th>
                            <th class="text-end" style="width: 170px;">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $cust): ?>
                            <tr>
                                <td>
                                    <span class="font-monospace small fw-bold text-dark">
                                        <?= htmlspecialchars((string) ($cust['customer_number'] ?? ('#' . $cust['id'])), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">
                                        <a href="?route=admin/boating/customers/edit&id=<?= (int) $cust['id'] ?>" class="text-decoration-none text-dark">
                                            <?= htmlspecialchars((string) $cust['first_name'] . ' ' . (string) $cust['last_name'], ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    </div>
                                    <?php if (!empty($cust['company'])): ?>
                                        <div class="small text-muted"><i class="bi bi-building me-1"></i><?= htmlspecialchars((string) $cust['company'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                    <div class="mt-1 d-flex flex-wrap gap-1 align-items-center">
                                        <?= BoatingSchoolService::getCustomerTypeBadge((string) ($cust['type'] ?? 'student')) ?>
                                        <?php if (!empty($cust['tags'])): ?>
                                            <?php foreach (array_slice(explode(',', (string) $cust['tags']), 0, 2) as $tag): ?>
                                                <span class="badge bg-light text-secondary border small"><?= htmlspecialchars(trim($tag), ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?= BoatingSchoolService::getCrmStageBadge((string) ($cust['crm_stage'] ?? 'student')) ?>
                                    <?php if (!empty($cust['lead_source'])): ?>
                                        <div class="small text-muted mt-1"><i class="bi bi-funnel me-1"></i><?= htmlspecialchars((string) $cust['lead_source'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($cust['target_license'])): ?>
                                        <div class="small fw-semibold text-dark"><i class="bi bi-compass text-primary me-1"></i><?= htmlspecialchars((string) $cust['target_license'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                    <?php if (!empty($cust['licenses_held'])): ?>
                                        <div class="small text-muted" title="Vorbesitz"><i class="bi bi-award me-1"></i><?= htmlspecialchars((string) $cust['licenses_held'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1 small">
                                        <div>
                                            <?php if (!empty($cust['theory_passed'])): ?>
                                                <span class="badge bg-success-subtle text-success"><i class="bi bi-check2-circle me-1"></i>Theorie &check;</span>
                                                <?php if (!empty($cust['theory_passed_at'])): ?>
                                                    <span class="text-muted small ms-1">(<?= htmlspecialchars((string) $cust['theory_passed_at'], ENT_QUOTES, 'UTF-8') ?>)</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border">Theorie offen</span>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <?php if (!empty($cust['practical_passed'])): ?>
                                                <span class="badge bg-success text-white"><i class="bi bi-award-fill me-1"></i>Praxis bestanden!</span>
                                                <?php if (!empty($cust['practical_passed_at'])): ?>
                                                    <span class="text-muted small ms-1">(<?= htmlspecialchars((string) $cust['practical_passed_at'], ENT_QUOTES, 'UTF-8') ?>)</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border">Praxis offen</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($cust['phone'])): ?>
                                        <div class="small">
                                            <a href="tel:<?= htmlspecialchars((string) $cust['phone'], ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none text-dark">
                                                <i class="bi bi-telephone me-1 text-primary"></i><?= htmlspecialchars((string) $cust['phone'], ENT_QUOTES, 'UTF-8') ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($cust['email'])): ?>
                                        <div class="small text-muted">
                                            <a href="mailto:<?= htmlspecialchars((string) $cust['email'], ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none text-muted">
                                                <i class="bi bi-envelope me-1"></i><?= htmlspecialchars((string) $cust['email'], ENT_QUOTES, 'UTF-8') ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($cust['city'])): ?>
                                        <div class="small text-muted"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars((string) $cust['city'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($canManage): ?>
                                        <form method="post" action="?route=admin/boating/customers/toggle-status" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="id" value="<?= (int) $cust['id'] ?>">
                                            <button type="submit" class="btn btn-sm p-0 border-0 bg-transparent" title="Status umschalten">
                                                <?php if (!empty($cust['is_active'])): ?>
                                                    <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>Aktiv</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary-subtle text-muted"><i class="bi bi-pause-circle me-1"></i>Inaktiv</span>
                                                <?php endif; ?>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <?php if (!empty($cust['is_active'])): ?>
                                            <span class="badge bg-success-subtle text-success">Aktiv</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-muted">Inaktiv</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" 
                                                class="btn btn-outline-secondary btn-edit-customer" 
                                                title="Kunde / Schüler schnell bearbeiten"
                                                data-id="<?= (int) $cust['id'] ?>"
                                                data-first_name="<?= htmlspecialchars((string) ($cust['first_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-last_name="<?= htmlspecialchars((string) ($cust['last_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-customer_number="<?= htmlspecialchars((string) ($cust['customer_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-type="<?= htmlspecialchars((string) ($cust['type'] ?? 'student'), ENT_QUOTES, 'UTF-8') ?>"
                                                data-crm_stage="<?= htmlspecialchars((string) ($cust['crm_stage'] ?? 'student'), ENT_QUOTES, 'UTF-8') ?>"
                                                data-company="<?= htmlspecialchars((string) ($cust['company'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-email="<?= htmlspecialchars((string) ($cust['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-phone="<?= htmlspecialchars((string) ($cust['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-preferred_contact="<?= htmlspecialchars((string) ($cust['preferred_contact'] ?? 'email'), ENT_QUOTES, 'UTF-8') ?>"
                                                data-street="<?= htmlspecialchars((string) ($cust['street'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-zip="<?= htmlspecialchars((string) ($cust['zip'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-city="<?= htmlspecialchars((string) ($cust['city'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-birthdate="<?= htmlspecialchars((string) ($cust['birthdate'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-emergency_contact="<?= htmlspecialchars((string) ($cust['emergency_contact'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-lead_source="<?= htmlspecialchars((string) ($cust['lead_source'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-target_license="<?= htmlspecialchars((string) ($cust['target_license'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-licenses_held="<?= htmlspecialchars((string) ($cust['licenses_held'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-experience_level="<?= htmlspecialchars((string) ($cust['experience_level'] ?? 'beginner'), ENT_QUOTES, 'UTF-8') ?>"
                                                data-tags="<?= htmlspecialchars((string) ($cust['tags'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-theory_passed="<?= !empty($cust['theory_passed']) ? '1' : '0' ?>"
                                                data-theory_passed_at="<?= htmlspecialchars((string) ($cust['theory_passed_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-practical_passed="<?= !empty($cust['practical_passed']) ? '1' : '0' ?>"
                                                data-practical_passed_at="<?= htmlspecialchars((string) ($cust['practical_passed_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-notes="<?= htmlspecialchars((string) ($cust['notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-user_id="<?= (int) ($cust['user_id'] ?? 0) ?>"
                                                data-is_active="<?= !empty($cust['is_active']) ? '1' : '0' ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="?route=admin/boating/customers/edit&id=<?= (int) $cust['id'] ?>" class="btn btn-outline-primary" title="360° Kundenakte öffnen">
                                            <i class="bi bi-person-lines-fill"></i>
                                        </a>
                                        <a href="?route=admin/boating/customers/vcard&id=<?= (int) $cust['id'] ?>" class="btn btn-outline-secondary" title="vCard (.vcf) herunterladen">
                                            <i class="bi bi-person-vcard"></i>
                                        </a>
                                        <a href="?route=admin/boating/calendar&customer_id=<?= (int) $cust['id'] ?>" class="btn btn-outline-info" title="Termin im Kalender buchen">
                                            <i class="bi bi-calendar-plus"></i>
                                        </a>
                                        <?php if ($canManage): ?>
                                            <form method="post" action="?route=admin/boating/customers/delete" class="d-inline" onsubmit="return confirm('Möchten Sie diesen Kunden wirklich löschen?');">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="id" value="<?= (int) $cust['id'] ?>">
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

<!-- ========================================================================= -->
<!-- MODERN CUSTOMER / STUDENT MODAL (Braunes Farbschema nach Vorlage)         -->
<!-- ========================================================================= -->
<?php if ($canManage): ?>
<div class="modal fade" id="customerModal" tabindex="-1" aria-labelledby="customerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content cust-modal-content">
            <!-- Modal Header (Dunkelblau/Navy mit Segmented Switcher) -->
            <div class="cust-modal-header text-white">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <h4 class="fw-bold text-white mb-1" id="customerModalTitle">Kunde / Schüler bearbeiten</h4>
                        <div class="text-white-50 small" id="customerModalSubtitle">Verwalten Sie Basisdaten, Prüfungsstatus und CRM-Informationen.</div>
                    </div>
                    <button type="button" class="btn-close btn-close-white opacity-75" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>

                <!-- Segmented Control Switcher -->
                <div class="cust-tab-container mt-3">
                    <button type="button" class="cust-tab-btn active" id="tabBtnCustBasic" data-tab="basic">
                        Basis &amp; Kontakt
                    </button>
                    <button type="button" class="cust-tab-btn" id="tabBtnCustNautic" data-tab="nautic">
                        Ausbildung &amp; CRM
                    </button>
                </div>
            </div>

            <!-- Form -->
            <form method="post" action="?route=admin/boating/customers/create" id="customerForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="redirect_to" value="?route=admin/boating/customers">
                <input type="hidden" name="id" id="custFieldId" value="">

                <div class="modal-body p-4 bg-white">
                    <!-- ===================================================== -->
                    <!-- TAB 1: BASIS & KONTAKT                                -->
                    <!-- ===================================================== -->
                    <div id="custPaneBasic" class="cust-tab-pane">
                        <div class="row g-4">
                            <!-- Linke Spalte: Person & Typ -->
                            <div class="col-md-6">
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="cust-field-label">Vorname <span class="text-danger">*</span></label>
                                        <input type="text" name="first_name" id="custFieldFirstName" class="cust-form-control" required placeholder="z. B. Marc">
                                    </div>
                                    <div class="col-6">
                                        <label class="cust-field-label">Nachname <span class="text-danger">*</span></label>
                                        <input type="text" name="last_name" id="custFieldLastName" class="cust-form-control" required placeholder="z. B. Keller">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="cust-field-label">Kundentyp <span class="text-danger">*</span></label>
                                    <select name="type" id="custFieldType" class="cust-form-control" required>
                                        <option value="student">Fahrschüler (student)</option>
                                        <option value="renter">Bootsmieter (renter)</option>
                                        <option value="both">Schüler &amp; Mieter (both)</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="cust-field-label">Firma / Organisation</label>
                                    <input type="text" name="company" id="custFieldCompany" class="cust-form-control" placeholder="Optional z. B. Yachtclub Zürich">
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="cust-field-label"><i class="bi bi-calendar-event me-1"></i> Geburtsdatum</label>
                                        <input type="date" name="birthdate" id="custFieldBirthdate" class="cust-form-control">
                                    </div>
                                    <div class="col-6">
                                        <label class="cust-field-label">Kundennummer</label>
                                        <input type="text" name="customer_number" id="custFieldCustomerNumber" class="cust-form-control font-monospace" placeholder="Auto-ID">
                                    </div>
                                </div>

                                <div>
                                    <label class="cust-field-label">Notfallkontakt</label>
                                    <input type="text" name="emergency_contact" id="custFieldEmergencyContact" class="cust-form-control" placeholder="Name &amp; Tel. für Notfälle">
                                </div>
                            </div>

                            <!-- Rechte Spalte: Kontakt & Adresse -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="cust-field-label"><i class="bi bi-envelope me-1"></i> E-Mail Adresse</label>
                                    <input type="email" name="email" id="custFieldEmail" class="cust-form-control" placeholder="kunde@beispiel.ch">
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-7">
                                        <label class="cust-field-label"><i class="bi bi-telephone me-1"></i> Telefon / Mobil</label>
                                        <input type="text" name="phone" id="custFieldPhone" class="cust-form-control" placeholder="+41 79 ...">
                                    </div>
                                    <div class="col-5">
                                        <label class="cust-field-label">Kontaktweg</label>
                                        <select name="preferred_contact" id="custFieldPreferredContact" class="cust-form-control">
                                            <option value="email">E-Mail</option>
                                            <option value="phone">Telefon</option>
                                            <option value="whatsapp">WhatsApp</option>
                                            <option value="sms">SMS</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="cust-field-label"><i class="bi bi-geo-alt me-1"></i> Strasse &amp; Nr.</label>
                                    <input type="text" name="street" id="custFieldStreet" class="cust-form-control" placeholder="Seestrasse 42">
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-4">
                                        <label class="cust-field-label">PLZ</label>
                                        <input type="text" name="zip" id="custFieldZip" class="cust-form-control" placeholder="8000">
                                    </div>
                                    <div class="col-8">
                                        <label class="cust-field-label">Ort</label>
                                        <input type="text" name="city" id="custFieldCity" class="cust-form-control" placeholder="Zürich">
                                    </div>
                                </div>

                                <div class="cust-card-box d-flex align-items-center justify-content-between p-2 px-3 mt-4">
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input" type="checkbox" name="is_active" id="custFieldIsActive" value="1" checked>
                                        <label class="form-check-label small fw-semibold text-dark ms-1" for="custFieldIsActive">Kunde ist aktiv</label>
                                    </div>
                                    <span class="badge bg-success-subtle text-success border-0 small" id="custStatusBadge">Aktiv</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ===================================================== -->
                    <!-- TAB 2: AUSBILDUNG & CRM                               -->
                    <!-- ===================================================== -->
                    <div id="custPaneNautic" class="cust-tab-pane" style="display: none;">
                        <div class="row g-4">
                            <!-- Linke Spalte: Nautische Ausbildung -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="cust-field-label"><i class="bi bi-compass me-1"></i> Ziel-Führerschein</label>
                                    <input type="text" name="target_license" id="custFieldTargetLicense" class="cust-form-control" placeholder="z. B. Kat. A Motorboot, Kat. D Segel">
                                </div>

                                <div class="mb-3">
                                    <label class="cust-field-label">Vorhandene Lizenzen / Patente</label>
                                    <input type="text" name="licenses_held" id="custFieldLicensesHeld" class="cust-form-control" placeholder="z. B. Bodenseeschifferpatent, Funkschein SRC">
                                </div>

                                <div class="mb-3">
                                    <label class="cust-field-label">Erfahrungsstufe</label>
                                    <select name="experience_level" id="custFieldExperienceLevel" class="cust-form-control">
                                        <?php foreach (CustomerService::getExperienceLevels() as $lvlKey => $lvlVal): ?>
                                            <option value="<?= htmlspecialchars($lvlKey, ENT_QUOTES, 'UTF-8') ?>">
                                                <?= htmlspecialchars($lvlVal, ENT_QUOTES, 'UTF-8') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Prüfungsstatus Box -->
                                <div>
                                    <label class="cust-field-label">Prüfungsstatus</label>
                                    <div class="cust-card-box">
                                        <div class="mb-2 pb-2 border-bottom">
                                            <div class="d-flex align-items-center justify-content-between mb-1">
                                                <div class="form-check form-switch m-0">
                                                    <input class="form-check-input" type="checkbox" name="theory_passed" id="custFieldTheoryPassed" value="1">
                                                    <label class="form-check-label small fw-semibold text-dark ms-1" for="custFieldTheoryPassed">Theorieprüfung bestanden</label>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center gap-2 mt-1 ps-4">
                                                <span class="small text-muted" style="font-size: 0.72rem;">Datum:</span>
                                                <input type="date" name="theory_passed_at" id="custFieldTheoryPassedAt" class="form-control form-control-sm border-0 bg-white" style="border-radius: 8px; font-size: 0.8rem; padding: 3px 8px;">
                                            </div>
                                        </div>

                                        <div>
                                            <div class="d-flex align-items-center justify-content-between mb-1">
                                                <div class="form-check form-switch m-0">
                                                    <input class="form-check-input" type="checkbox" name="practical_passed" id="custFieldPracticalPassed" value="1">
                                                    <label class="form-check-label small fw-semibold text-dark ms-1" for="custFieldPracticalPassed">Praktische Prüfung bestanden</label>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center gap-2 mt-1 ps-4">
                                                <span class="small text-muted" style="font-size: 0.72rem;">Datum:</span>
                                                <input type="date" name="practical_passed_at" id="custFieldPracticalPassedAt" class="form-control form-control-sm border-0 bg-white" style="border-radius: 8px; font-size: 0.8rem; padding: 3px 8px;">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Rechte Spalte: CRM & Notizen -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="cust-field-label">CRM-Status</label>
                                    <select name="crm_stage" id="custFieldCrmStage" class="cust-form-control">
                                        <?php foreach (CustomerService::getCrmStages() as $sKey => $sVal): ?>
                                            <option value="<?= htmlspecialchars($sKey, ENT_QUOTES, 'UTF-8') ?>" <?= $sKey === 'student' ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($sVal, ENT_QUOTES, 'UTF-8') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="cust-field-label">Lead-Quelle / Herkunft</label>
                                        <input type="text" name="lead_source" id="custFieldLeadSource" class="cust-form-control" placeholder="z. B. Empfehlung, Website">
                                    </div>
                                    <div class="col-6">
                                        <label class="cust-field-label">CRM Tags</label>
                                        <input type="text" name="tags" id="custFieldTags" class="cust-form-control" placeholder="z. B. VIP, Motorboot">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="cust-field-label"><i class="bi bi-person-lock me-1"></i> Verknüpfter CMS-Benutzer</label>
                                    <select name="user_id" id="custFieldUserId" class="cust-form-control">
                                        <option value="">-- Kein CMS-Benutzer --</option>
                                        <?php foreach ($users as $u): ?>
                                            <option value="<?= (int) $u['id'] ?>">
                                                <?= htmlspecialchars((string) ($u['name'] ?: $u['email']), ENT_QUOTES, 'UTF-8') ?> (ID: <?= (int) $u['id'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <label class="cust-field-label">Notizen &amp; Bemerkungen</label>
                                    <textarea name="notes" id="custFieldNotes" rows="4" class="cust-form-control" placeholder="Bemerkungen zur Fahrausbildung, Vorkenntnisse, Manöver oder Kundenwünsche..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Buttons -->
                <div class="p-4 pt-2 border-0 bg-white">
                    <div class="d-flex flex-wrap align-items-center justify-content-center gap-3">
                        <button type="button" class="btn btn-coach-cancel px-4 py-2" data-bs-dismiss="modal" style="min-width: 130px;">
                            Abbrechen
                        </button>
                        <button type="submit" id="customerSubmitBtn" class="btn btn-coach-brown px-5 py-2 shadow-sm" style="min-width: 180px;">
                            Kunde speichern
                        </button>
                        <a href="#" id="customerDetailLink" class="btn btn-outline-secondary px-3 py-2" style="border-radius: 14px; display: none;">
                            <i class="bi bi-box-arrow-up-right me-1"></i> 360° Akte öffnen
                        </a>
                    </div>

                    <!-- Löschen Option bei bestehenden Kunden -->
                    <div class="text-center mt-3" id="customerDeleteContainer" style="display: none;">
                        <button type="button" class="btn btn-link text-danger text-decoration-none fw-semibold small p-0 d-inline-flex align-items-center gap-1" id="customerDeleteTrigger">
                            <i class="bi bi-trash"></i> Kunde löschen
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Separates Lösch-Formular für den Modal-Löschbutton -->
<form method="post" action="?route=admin/boating/customers/delete" id="customerModalDeleteForm" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="id" id="customerModalDeleteId" value="">
</form>
<?php endif; ?>

<!-- Modal: CSV-Import -->
<?php if ($canImport): ?>
<div class="modal fade" id="importCsvModal" tabindex="-1" aria-labelledby="importCsvModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="?route=admin/boating/customers/import-csv" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="importCsvModalLabel">
                        <i class="bi bi-cloud-arrow-up text-info me-2"></i>Kundenliste aus CSV importieren
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>
                
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">CSV-Datei auswählen <span class="text-danger">*</span></label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv,text/csv" required>
                        <div class="form-text small">Trennzeichen <code>;</code> (Semikolon) oder <code>,</code> (Komma) wird automatisch erkannt.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Duplikatsbehandlung (bei gleicher E-Mail oder KD-Nr.)</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="duplicate_mode" id="dupModeSkip" value="skip" checked>
                            <label class="form-check-label small" for="dupModeSkip">
                                <strong>Duplikate überspringen</strong> (bestehende Daten bleiben unverändert)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="duplicate_mode" id="dupModeUpdate" value="update">
                            <label class="form-check-label small" for="dupModeUpdate">
                                <strong>Bestehende Daten aktualisieren</strong> (Daten aus CSV überschreiben)
                            </label>
                        </div>
                    </div>

                    <div class="alert alert-light border small text-muted mb-0">
                        <strong class="text-dark d-block mb-1"><i class="bi bi-info-circle me-1"></i>Unterstützte Spaltenüberschriften:</strong>
                        <code>Vorname, Nachname, Firma, E-Mail, Telefon, Strasse, PLZ, Ort, Geburtsdatum, Kundentyp, Ziel_Fuehrerschein, Vorhandene_Lizenzen, CRM_Status</code>
                    </div>
                </div>
                
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-sm btn-info text-white fw-semibold px-3">
                        <i class="bi bi-upload me-1"></i> Datei hochladen &amp; importieren
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('customerModal');
    if (!modalEl) return;
    const bsModal = new bootstrap.Modal(modalEl);

    const tabBtnBasic = document.getElementById('tabBtnCustBasic');
    const tabBtnNautic = document.getElementById('tabBtnCustNautic');
    const paneBasic = document.getElementById('custPaneBasic');
    const paneNautic = document.getElementById('custPaneNautic');

    const form = document.getElementById('customerForm');
    const modalTitle = document.getElementById('customerModalTitle');
    const modalSubtitle = document.getElementById('customerModalSubtitle');
    const submitBtn = document.getElementById('customerSubmitBtn');
    const detailLink = document.getElementById('customerDetailLink');
    const deleteContainer = document.getElementById('customerDeleteContainer');
    const deleteIdInput = document.getElementById('customerModalDeleteId');
    const statusBadge = document.getElementById('custStatusBadge');
    const activeSwitch = document.getElementById('custFieldIsActive');

    function updateStatusBadge() {
        if (!statusBadge || !activeSwitch) return;
        if (activeSwitch.checked) {
            statusBadge.className = 'badge bg-success-subtle text-success border-0 small';
            statusBadge.textContent = 'Aktiv';
        } else {
            statusBadge.className = 'badge bg-secondary-subtle text-muted border-0 small';
            statusBadge.textContent = 'Inaktiv';
        }
    }

    if (activeSwitch) {
        activeSwitch.addEventListener('change', updateStatusBadge);
    }

    function switchTab(tab) {
        if (tab === 'basic') {
            tabBtnBasic.classList.add('active');
            tabBtnNautic.classList.remove('active');
            paneBasic.style.display = 'block';
            paneNautic.style.display = 'none';
        } else {
            tabBtnNautic.classList.add('active');
            tabBtnBasic.classList.remove('active');
            paneNautic.style.display = 'block';
            paneBasic.style.display = 'none';
        }
    }

    if (tabBtnBasic) tabBtnBasic.addEventListener('click', () => switchTab('basic'));
    if (tabBtnNautic) tabBtnNautic.addEventListener('click', () => switchTab('nautic'));

    // Modal für neuen Kunden öffnen
    window.openCreateCustomerModal = function () {
        form.reset();
        form.action = '?route=admin/boating/customers/create';
        document.getElementById('custFieldId').value = '';
        modalTitle.textContent = 'Neuen Kunden / Schüler anlegen';
        if (modalSubtitle) modalSubtitle.textContent = 'Erfassen Sie die Basis- und Ausbildungsdaten des Schülers.';
        submitBtn.textContent = 'Kunde anlegen';
        if (detailLink) detailLink.style.display = 'none';
        if (deleteContainer) deleteContainer.style.display = 'none';
        document.getElementById('custFieldType').value = 'student';
        document.getElementById('custFieldCrmStage').value = 'student';
        document.getElementById('custFieldPreferredContact').value = 'email';
        document.getElementById('custFieldExperienceLevel').value = 'beginner';
        document.getElementById('custFieldIsActive').checked = true;
        updateStatusBadge();
        switchTab('basic');
        bsModal.show();
    };

    const btnOpenCreate = document.getElementById('btnOpenCreateCustomer');
    if (btnOpenCreate) {
        btnOpenCreate.addEventListener('click', window.openCreateCustomerModal);
    }

    // Modal für bestehenden Kunden bearbeiten öffnen
    document.querySelectorAll('.btn-edit-customer').forEach(function (btn) {
        btn.addEventListener('click', function () {
            form.reset();
            form.action = '?route=admin/boating/customers/update';
            modalTitle.textContent = 'Kunde / Schüler bearbeiten';
            if (modalSubtitle) modalSubtitle.textContent = 'Verwalten Sie Basisdaten, Prüfungsstatus und CRM-Informationen.';
            submitBtn.textContent = 'Änderungen speichern';

            const id = btn.getAttribute('data-id') || '';
            document.getElementById('custFieldId').value = id;
            if (deleteIdInput) deleteIdInput.value = id;

            if (detailLink) {
                detailLink.href = '?route=admin/boating/customers/edit&id=' + id;
                detailLink.style.display = 'inline-flex';
            }
            if (deleteContainer) {
                deleteContainer.style.display = 'block';
            }

            document.getElementById('custFieldFirstName').value = btn.getAttribute('data-first_name') || '';
            document.getElementById('custFieldLastName').value = btn.getAttribute('data-last_name') || '';
            document.getElementById('custFieldCustomerNumber').value = btn.getAttribute('data-customer_number') || '';
            document.getElementById('custFieldType').value = btn.getAttribute('data-type') || 'student';
            document.getElementById('custFieldCrmStage').value = btn.getAttribute('data-crm_stage') || 'student';
            document.getElementById('custFieldCompany').value = btn.getAttribute('data-company') || '';
            document.getElementById('custFieldEmail').value = btn.getAttribute('data-email') || '';
            document.getElementById('custFieldPhone').value = btn.getAttribute('data-phone') || '';
            document.getElementById('custFieldPreferredContact').value = btn.getAttribute('data-preferred_contact') || 'email';
            document.getElementById('custFieldStreet').value = btn.getAttribute('data-street') || '';
            document.getElementById('custFieldZip').value = btn.getAttribute('data-zip') || '';
            document.getElementById('custFieldCity').value = btn.getAttribute('data-city') || '';
            document.getElementById('custFieldBirthdate').value = btn.getAttribute('data-birthdate') || '';
            document.getElementById('custFieldEmergencyContact').value = btn.getAttribute('data-emergency_contact') || '';
            document.getElementById('custFieldLeadSource').value = btn.getAttribute('data-lead_source') || '';
            document.getElementById('custFieldTargetLicense').value = btn.getAttribute('data-target_license') || '';
            document.getElementById('custFieldLicensesHeld').value = btn.getAttribute('data-licenses_held') || '';
            document.getElementById('custFieldExperienceLevel').value = btn.getAttribute('data-experience_level') || 'beginner';
            document.getElementById('custFieldTags').value = btn.getAttribute('data-tags') || '';
            document.getElementById('custFieldTheoryPassed').checked = (btn.getAttribute('data-theory_passed') === '1');
            document.getElementById('custFieldTheoryPassedAt').value = btn.getAttribute('data-theory_passed_at') || '';
            document.getElementById('custFieldPracticalPassed').checked = (btn.getAttribute('data-practical_passed') === '1');
            document.getElementById('custFieldPracticalPassedAt').value = btn.getAttribute('data-practical_passed_at') || '';
            document.getElementById('custFieldNotes').value = btn.getAttribute('data-notes') || '';
            document.getElementById('custFieldUserId').value = btn.getAttribute('data-user_id') || '';
            document.getElementById('custFieldIsActive').checked = (btn.getAttribute('data-is_active') === '1');
            updateStatusBadge();

            switchTab('basic');
            bsModal.show();
        });
    });

    // Löschbutton im Modal
    const deleteTrigger = document.getElementById('customerDeleteTrigger');
    if (deleteTrigger) {
        deleteTrigger.addEventListener('click', function () {
            if (confirm('Möchten Sie diesen Kunden / Fahrschüler wirklich unwiderruflich löschen?')) {
                document.getElementById('customerModalDeleteForm').submit();
            }
        });
    }
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../../../views/layouts/admin.php';
