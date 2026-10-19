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

$title = 'Kunden & Schüler CRM - Bootsfahrschule';
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
                <li class="breadcrumb-item active" aria-current="page">Kunden &amp; Schüler CRM</li>
            </ol>
        </nav>
        <h1 class="h3 fw-bold text-dark mb-0">
            <i class="bi bi-people-fill text-primary me-2"></i>Kundenkartei &amp; CRM (<?= $totalCustomers ?>)
        </h1>
    </div>
    <div class="d-flex flex-wrap gap-2">
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
            <button type="button" class="btn btn-primary btn-sm fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#createCustomerModal">
                <i class="bi bi-person-plus-fill me-1"></i> Neuer Kunde / Schüler
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
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createCustomerModal">
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

<!-- Modal: Neuer Kunde / Schüler mit vollständigen CRM-Feldern -->
<?php if ($canManage): ?>
<div class="modal fade" id="createCustomerModal" tabindex="-1" aria-labelledby="createCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form method="post" action="?route=admin/boating/customers/create">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="createCustomerModalLabel">
                        <i class="bi bi-person-plus-fill text-primary me-2"></i>Neuen Schüler / Kunden erfassen
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>
                
                <div class="modal-body p-4">
                    <!-- Sektion 1: Stammdaten & Typ -->
                    <h6 class="fw-bold text-primary mb-3 pb-2 border-bottom">1. Stammdaten &amp; Kundentyp</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Kundentyp <span class="text-danger">*</span></label>
                            <select name="type" class="form-select">
                                <option value="student">Fahrschüler (student)</option>
                                <option value="renter">Bootsmieter (renter)</option>
                                <option value="both">Schüler &amp; Mieter (both)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">CRM-Status</label>
                            <select name="crm_stage" class="form-select">
                                <?php foreach (CustomerService::getCrmStages() as $sKey => $sVal): ?>
                                    <option value="<?= htmlspecialchars($sKey, ENT_QUOTES, 'UTF-8') ?>" <?= $sKey === 'student' ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($sVal, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Kundennummer</label>
                            <input type="text" name="customer_number" class="form-control" placeholder="Auto-ID falls leer (KD-2026-XXXX)">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">CMS-Benutzer</label>
                            <select name="user_id" class="form-select">
                                <option value="">-- Kein Benutzer --</option>
                                <?php foreach ($users as $u): ?>
                                    <option value="<?= (int) $u['id'] ?>">
                                        <?= htmlspecialchars((string) ($u['name'] ?: $u['email']), ENT_QUOTES, 'UTF-8') ?> (ID: <?= (int) $u['id'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Vorname <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control" required placeholder="z. B. Laura">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Nachname <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control" required placeholder="z. B. Keller">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Firma / Organisation</label>
                            <input type="text" name="company" class="form-control" placeholder="Optional z. B. Yachtclub Zürich">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">E-Mail</label>
                            <input type="email" name="email" class="form-control" placeholder="laura.keller@example.ch">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Telefon / Mobil</label>
                            <input type="text" name="phone" class="form-control" placeholder="+41 78 987 65 43">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Bevorzugter Kontaktweg</label>
                            <select name="preferred_contact" class="form-select">
                                <option value="email">E-Mail</option>
                                <option value="phone">Telefon / Anruf</option>
                                <option value="whatsapp">WhatsApp</option>
                                <option value="sms">SMS</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Straße &amp; Hausnummer</label>
                            <input type="text" name="street" class="form-control" placeholder="Seestrasse 42">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">PLZ</label>
                            <input type="text" name="zip" class="form-control" placeholder="8000">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Ort</label>
                            <input type="text" name="city" class="form-control" placeholder="Zürich">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Geburtsdatum</label>
                            <input type="date" name="birthdate" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Notfallkontakt</label>
                            <input type="text" name="emergency_contact" class="form-control" placeholder="Name &amp; Telefonnummer">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Lead-Quelle / Herkunft</label>
                            <input type="text" name="lead_source" class="form-control" placeholder="z. B. Empfehlung, Google, Messe">
                        </div>
                    </div>

                    <!-- Sektion 2: Nautisches Profil & Ausbildung -->
                    <h6 class="fw-bold text-primary mb-3 pb-2 border-bottom">2. Nautisches Profil &amp; Scheine</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Ziel-Führerschein</label>
                            <input type="text" name="target_license" class="form-control" placeholder="z. B. Kat. A Motorboot, Kat. D Segel">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Vorhandene Lizenzen / Patente</label>
                            <input type="text" name="licenses_held" class="form-control" placeholder="z. B. Bodenseeschifferpatent, Funkschein SRC">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Erfahrungsstufe</label>
                            <select name="experience_level" class="form-select">
                                <?php foreach (CustomerService::getExperienceLevels() as $lvlKey => $lvlVal): ?>
                                    <option value="<?= htmlspecialchars($lvlKey, ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($lvlVal, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Führerschein-/Ausweis-Nr.</label>
                            <input type="text" name="license_number" class="form-control" placeholder="z. B. CH-6005-9988">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">CRM Tags (Kommagetrennt)</label>
                            <input type="text" name="tags" class="form-control" placeholder="z. B. Motorboot, Theoriekurs, VIP, Frühjahr-2026">
                        </div>

                        <!-- Prüfungsstatus -->
                        <div class="col-md-6">
                            <div class="card bg-light border-0 p-3">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="theory_passed" id="tpCheck" value="1">
                                    <label class="form-check-label small fw-semibold" for="tpCheck">Theorieprüfung bestanden</label>
                                </div>
                                <label class="form-label small text-muted mb-1">Datum Theorieprüfung</label>
                                <input type="date" name="theory_passed_at" class="form-control form-control-sm">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light border-0 p-3">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="practical_passed" id="ppCheck" value="1">
                                    <label class="form-check-label small fw-semibold" for="ppCheck">Praktische Prüfung bestanden</label>
                                </div>
                                <label class="form-label small text-muted mb-1">Datum Praxisprüfung</label>
                                <input type="date" name="practical_passed_at" class="form-control form-control-sm">
                            </div>
                        </div>
                    </div>

                    <!-- Sektion 3: Notizen & Status -->
                    <h6 class="fw-bold text-primary mb-3 pb-2 border-bottom">3. Notizen &amp; Aktivierung</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Ausbildungsnotizen (allgemein)</label>
                            <textarea name="notes" rows="3" class="form-control" placeholder="Bemerkungen zur Fahrausbildung, Vorkenntnissen oder Manövern..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Interne CRM-Notizen (vertraulich)</label>
                            <textarea name="internal_notes" rows="3" class="form-control" placeholder="Interne Bemerkungen, Sondervereinbarungen, Zahlungsmodalitäten..."></textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="custActiveSwitch" value="1" checked>
                                <label class="form-check-label small fw-semibold" for="custActiveSwitch">Kunde ist aktiv</label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-sm btn-primary fw-semibold px-4">Kunde anlegen &amp; zur CRM-Akte</button>
                </div>
            </form>
        </div>
    </div>
</div>
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

<?php
$content = ob_get_clean();
require dirname(__DIR__, 3) . '/views/layouts/admin.php';
