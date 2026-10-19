<?php

declare(strict_types=1);

/**
 * Bootsfahrschule - 360° CRM Kundenakte & Bearbeitung
 *
 * @var array<string, mixed> $customer
 * @var array<int, array<string, mixed>> $appointments
 * @var array<int, array<string, mixed>> $subscriptions
 * @var array<int, array<string, mixed>> $services
 * @var array<int, array<string, mixed>> $users
 * @var string $activeTab
 */

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$fullName = trim((string) ($customer['first_name'] ?? '') . ' ' . (string) ($customer['last_name'] ?? ''));
$title = 'Kundenakte CRM: ' . $fullName;
$currentRoute = 'admin/boating/customers';
$canManage = BoatingSchoolService::canManage();
$canExport = BoatingSchoolService::canExportCustomers();
$canManageSubs = BoatingSchoolService::canManageSubscriptions();
$csrfToken = class_exists('Csrf') ? Csrf::getToken() : '';
$users = $users ?? (class_exists('User') ? User::all() : []);
$services = $services ?? [];
$appointments = $appointments ?? [];
$subscriptions = $subscriptions ?? [];
$activeTab = $activeTab ?? 'overview';

// Telefonnummer für WhatsApp bereinigen (nur Ziffern, ohne führendes Plus oder Nullen für intl Format)
$rawPhone = preg_replace('/[^0-9]/', '', (string) ($customer['phone'] ?? ''));
$whatsappUrl = $rawPhone !== '' ? 'https://wa.me/' . $rawPhone : null;

// KPIs berechnen
$totalAppointments = count($appointments);
$completedCount = 0;
$upcomingCount = 0;
$appointmentRevenue = 0.00;
$today = date('Y-m-d');

foreach ($appointments as $app) {
    $st = (string) ($app['status'] ?? '');
    $pr = (float) ($app['price'] ?? 0.00);
    $d = (string) ($app['date'] ?? '');

    if ($st === 'completed') {
        $completedCount++;
        $appointmentRevenue += $pr;
    } elseif (in_array($st, ['requested', 'confirmed'], true)) {
        $appointmentRevenue += $pr;
        if ($d >= $today) {
            $upcomingCount++;
        }
    }
}

$subRevenue = 0.00;
$totalUnitsPurchased = 0;
$totalUnitsUsed = 0;
$activeSubsCount = 0;

foreach ($subscriptions as $s) {
    $subRevenue += (float) ($s['price'] ?? 0.00);
    $totalUnitsPurchased += (int) ($s['total_units'] ?? 0);
    $totalUnitsUsed += (int) ($s['used_units'] ?? 0);
    if (($s['status'] ?? '') === 'active') {
        $activeSubsCount++;
    }
}
$remainingUnits = max(0, $totalUnitsPurchased - $totalUnitsUsed);
$totalRevenue = $appointmentRevenue + $subRevenue;

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

<!-- Header mit Titel und Quick-Actions -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="?route=admin/boating">Bootsfahrschule</a></li>
                <li class="breadcrumb-item"><a href="?route=admin/boating/customers">Kunden &amp; Schüler</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?></li>
            </ol>
        </nav>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <h1 class="h3 fw-bold text-dark mb-0">
                <i class="bi bi-person-badge text-primary me-2"></i><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>
            </h1>
            <?= BoatingSchoolService::getCustomerTypeBadge((string) ($customer['type'] ?? 'student')) ?>
            <?= BoatingSchoolService::getCrmStageBadge((string) ($customer['crm_stage'] ?? 'student')) ?>
            <?php if (!empty($customer['is_active'])): ?>
                <span class="badge bg-success-subtle text-success border border-success"><i class="bi bi-check-circle me-1"></i>Aktiv</span>
            <?php else: ?>
                <span class="badge bg-secondary-subtle text-muted border"><i class="bi bi-pause-circle me-1"></i>Inaktiv</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="?route=admin/boating/customers" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kundenübersicht
        </a>
        <?php if ($canExport): ?>
            <a href="?route=admin/boating/customers/vcard&id=<?= (int) $customer['id'] ?>" class="btn btn-outline-secondary btn-sm" title="vCard Visitenkarte herunterladen">
                <i class="bi bi-person-vcard me-1"></i> vCard (.vcf)
            </a>
        <?php endif; ?>
        <a href="?route=admin/boating/calendar&customer_id=<?= (int) $customer['id'] ?>" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-calendar-plus me-1"></i> Termin disponieren
        </a>
    </div>
</div>

<!-- CRM KPI Leiste -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm bg-white h-100">
            <div class="card-body p-3">
                <div class="text-muted small text-uppercase fw-semibold mb-1">Gesamtumsatz</div>
                <div class="h4 fw-bold text-dark mb-0"><?= BoatingSchoolService::formatPrice($totalRevenue) ?></div>
                <div class="small text-muted mt-1"><?= BoatingSchoolService::formatPrice($appointmentRevenue) ?> Termine + <?= BoatingSchoolService::formatPrice($subRevenue) ?> Abos</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm bg-white h-100">
            <div class="card-body p-3">
                <div class="text-muted small text-uppercase fw-semibold mb-1">Fahrstunden / Termine</div>
                <div class="h4 fw-bold text-primary mb-0"><?= $totalAppointments ?> <span class="fs-6 fw-normal text-muted">gesamt</span></div>
                <div class="small text-muted mt-1"><?= $completedCount ?> abgeschlossen, <?= $upcomingCount ?> anstehend</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm bg-white h-100">
            <div class="card-body p-3">
                <div class="text-muted small text-uppercase fw-semibold mb-1">Abo-Guthaben</div>
                <div class="h4 fw-bold text-success mb-0"><?= $remainingUnits ?> <span class="fs-6 fw-normal text-muted">Einheiten</span></div>
                <div class="small text-muted mt-1"><?= $activeSubsCount ?> aktive Karte(n) vorhanden</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm bg-white h-100">
            <div class="card-body p-3">
                <div class="text-muted small text-uppercase fw-semibold mb-1">Prüfungsstatus</div>
                <div class="d-flex align-items-center gap-2 mt-1">
                    <?php if (!empty($customer['practical_passed'])): ?>
                        <span class="badge bg-success text-white"><i class="bi bi-award-fill me-1"></i>Praxis bestanden!</span>
                    <?php elseif (!empty($customer['theory_passed'])): ?>
                        <span class="badge bg-info-subtle text-info border"><i class="bi bi-check2 me-1"></i>Theorie bestanden</span>
                    <?php else: ?>
                        <span class="badge bg-light text-muted border">In Ausbildung</span>
                    <?php endif; ?>
                </div>
                <div class="small text-muted mt-1"><?= htmlspecialchars((string) ($customer['target_license'] ?? 'Keine Ziel-Kategorie'), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Tabs Navigation -->
<ul class="nav nav-tabs mb-4" id="crmCustomerTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'overview' ? 'active fw-bold' : '' ?>" id="tab-overview-btn" data-bs-toggle="tab" data-bs-target="#tab-overview" type="button" role="tab">
            <i class="bi bi-person me-1"></i> Stammdaten &amp; Kontakt
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'nautical' ? 'active fw-bold' : '' ?>" id="tab-nautical-btn" data-bs-toggle="tab" data-bs-target="#tab-nautical" type="button" role="tab">
            <i class="bi bi-compass me-1"></i> Nautisches Profil &amp; Lizenzen
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'appointments' ? 'active fw-bold' : '' ?>" id="tab-appointments-btn" data-bs-toggle="tab" data-bs-target="#tab-appointments" type="button" role="tab">
            <i class="bi bi-calendar3 me-1"></i> Termine &amp; Umsatz (<?= count($appointments) ?>)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'subscriptions' ? 'active fw-bold' : '' ?>" id="tab-subscriptions-btn" data-bs-toggle="tab" data-bs-target="#tab-subscriptions" type="button" role="tab">
            <i class="bi bi-card-checklist me-1"></i> Abo- &amp; Punktekarten (<?= count($subscriptions) ?>)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'notes' ? 'active fw-bold' : '' ?>" id="tab-notes-btn" data-bs-toggle="tab" data-bs-target="#tab-notes" type="button" role="tab">
            <i class="bi bi-journal-text me-1"></i> Interne CRM-Notizen
        </button>
    </li>
</ul>

<div class="tab-content" id="crmCustomerTabsContent">
    
    <!-- TAB 1: Stammdaten & Kontakt -->
    <div class="tab-pane fade <?= $activeTab === 'overview' ? 'show active' : '' ?>" id="tab-overview" role="tabpanel">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <form method="post" action="?route=admin/boating/customers/update">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="id" value="<?= (int) $customer['id'] ?>">
                            <input type="hidden" name="active_tab" value="overview">

                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">Kundentyp <span class="text-danger">*</span></label>
                                    <select name="type" class="form-select">
                                        <option value="student" <?= ($customer['type'] ?? '') === 'student' ? 'selected' : '' ?>>Fahrschüler (student)</option>
                                        <option value="renter" <?= ($customer['type'] ?? '') === 'renter' ? 'selected' : '' ?>>Bootsmieter (renter)</option>
                                        <option value="both" <?= ($customer['type'] ?? '') === 'both' ? 'selected' : '' ?>>Schüler &amp; Mieter (both)</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">CRM-Stufe</label>
                                    <select name="crm_stage" class="form-select">
                                        <?php foreach (CustomerService::getCrmStages() as $sKey => $sVal): ?>
                                            <option value="<?= htmlspecialchars($sKey, ENT_QUOTES, 'UTF-8') ?>" <?= ($customer['crm_stage'] ?? '') === $sKey ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($sVal, ENT_QUOTES, 'UTF-8') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">Kundennummer</label>
                                    <input type="text" name="customer_number" class="form-control" value="<?= htmlspecialchars((string) ($customer['customer_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="z. B. KD-2026-0001">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">CMS-Benutzer</label>
                                    <select name="user_id" class="form-select">
                                        <option value="">-- Kein Benutzer --</option>
                                        <?php foreach ($users as $u): ?>
                                            <option value="<?= (int) $u['id'] ?>" <?= (!empty($customer['user_id']) && (int) $customer['user_id'] === (int) $u['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars((string) ($u['name'] ?: $u['email']), ENT_QUOTES, 'UTF-8') ?> (ID: <?= (int) $u['id'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Vorname <span class="text-danger">*</span></label>
                                    <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars((string) $customer['first_name'], ENT_QUOTES, 'UTF-8') ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Nachname <span class="text-danger">*</span></label>
                                    <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars((string) $customer['last_name'], ENT_QUOTES, 'UTF-8') ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Firma / Club</label>
                                    <input type="text" name="company" class="form-control" value="<?= htmlspecialchars((string) ($customer['company'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="z. B. Yachtclub Zürich">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">E-Mail</label>
                                    <div class="input-group">
                                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars((string) ($customer['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                        <?php if (!empty($customer['email'])): ?>
                                            <a href="mailto:<?= htmlspecialchars((string) $customer['email'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary" title="E-Mail schreiben"><i class="bi bi-envelope"></i></a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Telefon / Mobil</label>
                                    <div class="input-group">
                                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars((string) ($customer['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                        <?php if (!empty($customer['phone'])): ?>
                                            <a href="tel:<?= htmlspecialchars((string) $customer['phone'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary" title="Anrufen"><i class="bi bi-telephone"></i></a>
                                        <?php endif; ?>
                                        <?php if ($whatsappUrl): ?>
                                            <a href="<?= $whatsappUrl ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-success" title="WhatsApp Chat öffnen"><i class="bi bi-whatsapp"></i></a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Bevorzugter Kontaktweg</label>
                                    <select name="preferred_contact" class="form-select">
                                        <?php foreach (CustomerService::getPreferredContacts() as $pKey => $pVal): ?>
                                            <option value="<?= htmlspecialchars($pKey, ENT_QUOTES, 'UTF-8') ?>" <?= ($customer['preferred_contact'] ?? 'email') === $pKey ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($pVal, ENT_QUOTES, 'UTF-8') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Straße &amp; Hausnummer</label>
                                    <input type="text" name="street" class="form-control" value="<?= htmlspecialchars((string) ($customer['street'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-semibold">PLZ</label>
                                    <input type="text" name="zip" class="form-control" value="<?= htmlspecialchars((string) ($customer['zip'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Ort</label>
                                    <input type="text" name="city" class="form-control" value="<?= htmlspecialchars((string) ($customer['city'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Geburtsdatum</label>
                                    <input type="date" name="birthdate" class="form-control" value="<?= htmlspecialchars((string) ($customer['birthdate'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Notfallkontakt</label>
                                    <input type="text" name="emergency_contact" class="form-control" value="<?= htmlspecialchars((string) ($customer['emergency_contact'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Name &amp; Telefon">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Lead-Quelle / Herkunft</label>
                                    <input type="text" name="lead_source" class="form-control" value="<?= htmlspecialchars((string) ($customer['lead_source'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="z. B. Empfehlung, Google, Messe">
                                </div>

                                <div class="col-12">
                                    <label class="form-label small fw-semibold">CRM Tags (kommagetrennt)</label>
                                    <input type="text" name="tags" class="form-control" value="<?= htmlspecialchars((string) ($customer['tags'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="z. B. Motorboot, Theoriekurs, VIP, Frühjahr-2026">
                                </div>

                                <!-- Versteckte Felder der anderen Tabs beibehalten -->
                                <input type="hidden" name="target_license" value="<?= htmlspecialchars((string) ($customer['target_license'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="licenses_held" value="<?= htmlspecialchars((string) ($customer['licenses_held'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="license_number" value="<?= htmlspecialchars((string) ($customer['license_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="experience_level" value="<?= htmlspecialchars((string) ($customer['experience_level'] ?? 'beginner'), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="theory_passed" value="<?= !empty($customer['theory_passed']) ? '1' : '0' ?>">
                                <input type="hidden" name="theory_passed_at" value="<?= htmlspecialchars((string) ($customer['theory_passed_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="practical_passed" value="<?= !empty($customer['practical_passed']) ? '1' : '0' ?>">
                                <input type="hidden" name="practical_passed_at" value="<?= htmlspecialchars((string) ($customer['practical_passed_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="notes" value="<?= htmlspecialchars((string) ($customer['notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="internal_notes" value="<?= htmlspecialchars((string) ($customer['internal_notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_active" id="custActiveSwitch" value="1" <?= !empty($customer['is_active']) ? 'checked' : '' ?>>
                                        <label class="form-check-label small fw-semibold" for="custActiveSwitch">Kunde ist aktiv</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="notify_email" id="custNotifyEmailSwitch" value="1" <?= (!isset($customer['notify_email']) || !empty($customer['notify_email'])) ? 'checked' : '' ?>>
                                        <label class="form-check-label small fw-semibold" for="custNotifyEmailSwitch">
                                            <i class="bi bi-envelope-check text-primary me-1"></i>E-Mail-Benachrichtigungen aktiviert
                                        </label>
                                        <div class="form-text small">Erlaubt den automatischen Empfang von Buchungsbestätigungen &amp; Erinnerungen.</div>
                                    </div>
                                </div>

                                <div class="col-12 pt-3 border-top d-flex gap-2 justify-content-end">
                                    <button type="submit" class="btn btn-sm btn-primary fw-semibold px-4">Stammdaten speichern</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Seitenleiste: CRM Quick-Info -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-info-circle text-primary me-2"></i>Kunden-Profil</h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="small">
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-muted">Kundennummer:</span>
                                <span class="font-monospace fw-bold"><?= htmlspecialchars((string) ($customer['customer_number'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-muted">Erfasst am:</span>
                                <span><?= htmlspecialchars((string) $customer['created_at'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-muted">Bevorzugter Kontakt:</span>
                                <span class="fw-semibold"><?= BoatingSchoolService::getPreferredContactLabel((string) ($customer['preferred_contact'] ?? 'email')) ?></span>
                            </div>
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-muted">Erfahrungsstufe:</span>
                                <span><?= BoatingSchoolService::getExperienceLevelLabel((string) ($customer['experience_level'] ?? 'beginner')) ?></span>
                            </div>
                            <div class="d-flex justify-content-between py-2">
                                <span class="text-muted">Zuletzt aktualisiert:</span>
                                <span><?= htmlspecialchars((string) $customer['updated_at'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </div>

                        <?php if ($whatsappUrl): ?>
                            <div class="mt-3">
                                <a href="<?= $whatsappUrl ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-success btn-sm w-100">
                                    <i class="bi bi-whatsapp me-1"></i> Direkt WhatsApp Nachricht senden
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: Nautisches Profil & Lizenzen -->
    <div class="tab-pane fade <?= $activeTab === 'nautical' ? 'show active' : '' ?>" id="tab-nautical" role="tabpanel">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="post" action="?route=admin/boating/customers/update">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="id" value="<?= (int) $customer['id'] ?>">
                    <input type="hidden" name="active_tab" value="nautical">

                    <!-- Stammdaten beibehalten -->
                    <input type="hidden" name="customer_number" value="<?= htmlspecialchars((string) ($customer['customer_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="type" value="<?= htmlspecialchars((string) ($customer['type'] ?? 'student'), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="crm_stage" value="<?= htmlspecialchars((string) ($customer['crm_stage'] ?? 'student'), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="user_id" value="<?= htmlspecialchars((string) ($customer['user_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="first_name" value="<?= htmlspecialchars((string) $customer['first_name'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="last_name" value="<?= htmlspecialchars((string) $customer['last_name'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="company" value="<?= htmlspecialchars((string) ($customer['company'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="email" value="<?= htmlspecialchars((string) ($customer['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="phone" value="<?= htmlspecialchars((string) ($customer['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="preferred_contact" value="<?= htmlspecialchars((string) ($customer['preferred_contact'] ?? 'email'), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="lead_source" value="<?= htmlspecialchars((string) ($customer['lead_source'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="tags" value="<?= htmlspecialchars((string) ($customer['tags'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="street" value="<?= htmlspecialchars((string) ($customer['street'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="zip" value="<?= htmlspecialchars((string) ($customer['zip'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="city" value="<?= htmlspecialchars((string) ($customer['city'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="birthdate" value="<?= htmlspecialchars((string) ($customer['birthdate'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="emergency_contact" value="<?= htmlspecialchars((string) ($customer['emergency_contact'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="notes" value="<?= htmlspecialchars((string) ($customer['notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="internal_notes" value="<?= htmlspecialchars((string) ($customer['internal_notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="is_active" value="<?= !empty($customer['is_active']) ? '1' : '0' ?>">

                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Angestrebter Führerschein (Zielkategorie)</label>
                            <input type="text" name="target_license" class="form-control" value="<?= htmlspecialchars((string) ($customer['target_license'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="z. B. Kat. A Motorboot oder Kat. D Segelschiff">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Vorhandene Lizenzen / Vorbesitz</label>
                            <input type="text" name="licenses_held" class="form-control" value="<?= htmlspecialchars((string) ($customer['licenses_held'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="z. B. Bodenseepatent, Funkschein SRC, SBF Binnen">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Schiffsführerausweis- / Führerschein-Nummer</label>
                            <input type="text" name="license_number" class="form-control" value="<?= htmlspecialchars((string) ($customer['license_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="z. B. CH-6005-1234">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Erfahrungsstufe auf dem Wasser</label>
                            <select name="experience_level" class="form-select">
                                <?php foreach (CustomerService::getExperienceLevels() as $lvlKey => $lvlVal): ?>
                                    <option value="<?= htmlspecialchars($lvlKey, ENT_QUOTES, 'UTF-8') ?>" <?= ($customer['experience_level'] ?? 'beginner') === $lvlKey ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($lvlVal, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Prüfungsstatus -->
                        <div class="col-md-6">
                            <div class="card bg-light border-0 p-3 h-100">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="theory_passed" id="tpCheck2" value="1" <?= !empty($customer['theory_passed']) ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-bold text-dark" for="tpCheck2">Theorieprüfung bestanden</label>
                                </div>
                                <label class="form-label small text-muted mb-1">Datum der bestandenen Theorieprüfung</label>
                                <input type="date" name="theory_passed_at" class="form-control" value="<?= htmlspecialchars((string) ($customer['theory_passed_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light border-0 p-3 h-100">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="practical_passed" id="ppCheck2" value="1" <?= !empty($customer['practical_passed']) ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-bold text-dark" for="ppCheck2">Praktische Prüfung bestanden</label>
                                </div>
                                <label class="form-label small text-muted mb-1">Datum der bestandenen Praxisprüfung</label>
                                <input type="date" name="practical_passed_at" class="form-control" value="<?= htmlspecialchars((string) ($customer['practical_passed_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                        </div>

                        <div class="col-12 pt-3 border-top d-flex justify-content-end">
                            <button type="submit" class="btn btn-sm btn-primary fw-semibold px-4">Nautisches Profil speichern</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- TAB 3: Termine & Umsatz (Historie) -->
    <div class="tab-pane fade <?= $activeTab === 'appointments' ? 'show active' : '' ?>" id="tab-appointments" role="tabpanel">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-clock-history text-primary me-2"></i>Terminhistorie &amp; Fahrstunden</h6>
                <a href="?route=admin/boating/calendar&customer_id=<?= (int) $customer['id'] ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-plus-lg me-1"></i> Neuer Termin
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($appointments)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
                        Noch keine Termine für diesen Kunden erfasst.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th>Datum &amp; Zeit</th>
                                    <th>Leistung / Kurs</th>
                                    <th>Boot &amp; Fahrlehrer</th>
                                    <th>Standort</th>
                                    <th>Status</th>
                                    <th>Preis</th>
                                    <th class="text-end">Aktion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointments as $app): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars(date('d.m.Y', strtotime((string) $app['date'])), ENT_QUOTES, 'UTF-8') ?></div>
                                            <div class="small text-muted"><?= htmlspecialchars(substr((string) $app['start_time'], 0, 5), ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars(substr((string) $app['end_time'], 0, 5), ENT_QUOTES, 'UTF-8') ?> Uhr</div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars((string) ($app['service_name'] ?? 'Fahrstunde'), ENT_QUOTES, 'UTF-8') ?></div>
                                            <?php if (!empty($app['service_duration'])): ?>
                                                <span class="badge bg-light text-muted border small"><?= (int) $app['service_duration'] ?> Min.</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($app['boat_name'])): ?>
                                                <div><i class="bi bi-water text-primary me-1"></i><?= htmlspecialchars((string) $app['boat_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($app['instructor_first_name'])): ?>
                                                <div class="small text-muted"><i class="bi bi-person me-1"></i><?= htmlspecialchars((string) $app['instructor_first_name'] . ' ' . (string) $app['instructor_last_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="small"><?= htmlspecialchars((string) ($app['location_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                                        </td>
                                        <td>
                                            <?= match ($app['status'] ?? '') {
                                                'confirmed' => '<span class="badge bg-success">Bestätigt</span>',
                                                'completed' => '<span class="badge bg-primary">Abgeschlossen</span>',
                                                'requested' => '<span class="badge bg-warning text-dark">Angefragt</span>',
                                                'cancelled_customer', 'cancelled_system' => '<span class="badge bg-danger">Storniert</span>',
                                                default => '<span class="badge bg-secondary">' . htmlspecialchars((string) $app['status'], ENT_QUOTES, 'UTF-8') . '</span>',
                                            } ?>
                                        </td>
                                        <td class="fw-bold">
                                            <?= BoatingSchoolService::formatPrice((float) ($app['price'] ?? 0)) ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="?route=admin/boating/calendar&date=<?= htmlspecialchars((string) $app['date'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-secondary" title="Im Kalender anzeigen">
                                                <i class="bi bi-calendar3"></i>
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

    <!-- TAB 4: Abo- & Punktekarten (Subscriptions) -->
    <div class="tab-pane fade <?= $activeTab === 'subscriptions' ? 'show active' : '' ?>" id="tab-subscriptions" role="tabpanel">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-card-checklist text-primary me-2"></i>Abonnements &amp; Punktekarten</h5>
            <?php if ($canManageSubs): ?>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createSubscriptionModal">
                    <i class="bi bi-plus-lg me-1"></i> Neues Abo anlegen
                </button>
            <?php endif; ?>
        </div>

        <?php if (empty($subscriptions)): ?>
            <div class="card border-0 shadow-sm p-5 text-center text-muted">
                <i class="bi bi-card-heading fs-1 d-block mb-2"></i>
                <p class="mb-3">Bislang sind keine Punktekarten oder Abos für diesen Kunden erfasst.</p>
                <?php if ($canManageSubs): ?>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#createSubscriptionModal">
                            <i class="bi bi-plus-lg me-1"></i> Jetzt 10er-Abo oder Punktekarte buchen
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="row g-4 mb-4">
                <?php foreach ($subscriptions as $sub): ?>
                    <?php
                        $tot = max(1, (int) ($sub['total_units'] ?? 10));
                        $used = (int) ($sub['used_units'] ?? 0);
                        $pct = min(100, (int) round(($used / $tot) * 100));
                        $rem = max(0, $tot - $used);
                        $usages = BoatingSchoolRepository::getSubscriptionUsages((int) $sub['id']);
                    ?>
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars((string) $sub['title'], ENT_QUOTES, 'UTF-8') ?></h6>
                                    <span class="font-monospace small text-muted"><?= htmlspecialchars((string) ($sub['code'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <?= BoatingSchoolService::getSubscriptionStatusBadge((string) ($sub['status'] ?? 'active')) ?>
                            </div>
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="small fw-semibold text-muted">Verbrauch:</span>
                                    <span class="fw-bold text-dark"><?= $used ?> von <?= $tot ?> Einheiten verbraucht (<?= $rem ?> übrig)</span>
                                </div>
                                <div class="progress mb-3" style="height: 10px;">
                                    <div class="progress-bar <?= $pct >= 100 ? 'bg-secondary' : 'bg-primary' ?>" role="progressbar" style="width: <?= $pct ?>%;" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>

                                <div class="row g-2 small text-muted mb-3">
                                    <div class="col-6">
                                        <span>Preis:</span> <strong class="text-dark"><?= BoatingSchoolService::formatPrice((float) ($sub['price'] ?? 0)) ?></strong>
                                    </div>
                                    <div class="col-6">
                                        <span>Gültig bis:</span> <strong class="text-dark"><?= !empty($sub['valid_until']) ? htmlspecialchars((string) $sub['valid_until'], ENT_QUOTES, 'UTF-8') : 'Unbegrenzt' ?></strong>
                                    </div>
                                    <?php if (!empty($sub['service_name'])): ?>
                                        <div class="col-12">
                                            <span>Gültig für:</span> <?= htmlspecialchars((string) $sub['service_name'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="d-flex gap-2">
                                    <?php if ($canManageSubs && ($sub['status'] ?? '') === 'active' && $rem > 0): ?>
                                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#useSubscriptionModal<?= (int) $sub['id'] ?>">
                                            <i class="bi bi-check2-circle me-1"></i> Einheit abbuchen
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($canManageSubs): ?>
                                        <form method="post" action="?route=admin/boating/customers/subscriptions/delete" class="ms-auto" onsubmit="return confirm('Möchten Sie dieses Abonnement wirklich löschen?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="id" value="<?= (int) $sub['id'] ?>">
                                            <input type="hidden" name="customer_id" value="<?= (int) $customer['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Abo löschen">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>

                                <!-- Entwertungs-Journal für diese Karte -->
                                <?php if (!empty($usages)): ?>
                                    <div class="mt-4 pt-3 border-top">
                                        <div class="small fw-semibold text-dark mb-2"><i class="bi bi-journal-check me-1"></i>Abbuchungsprotokoll:</div>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-borderless small mb-0">
                                                <tbody>
                                                    <?php foreach ($usages as $u): ?>
                                                        <tr class="border-bottom">
                                                            <td class="text-muted"><?= htmlspecialchars((string) $u['used_date'], ENT_QUOTES, 'UTF-8') ?></td>
                                                            <td><span class="badge bg-light text-dark border"><?= (int) $u['units'] ?> Einheit(en)</span></td>
                                                            <td><?= htmlspecialchars((string) ($u['note'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                                            <td class="text-end">
                                                                <?php if ($canManageSubs): ?>
                                                                    <form method="post" action="?route=admin/boating/customers/subscriptions/refund" class="d-inline" onsubmit="return confirm('Möchten Sie diese Abbuchung rückgängig machen?');">
                                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                                        <input type="hidden" name="usage_id" value="<?= (int) $u['id'] ?>">
                                                                        <input type="hidden" name="customer_id" value="<?= (int) $customer['id'] ?>">
                                                                        <button type="submit" class="btn btn-sm p-0 text-danger" title="Abbuchung stornieren">
                                                                            <i class="bi bi-x-circle"></i>
                                                                        </button>
                                                                    </form>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Modal: Einheit von diesem Abo abbuchen -->
                    <div class="modal fade" id="useSubscriptionModal<?= (int) $sub['id'] ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="post" action="?route=admin/boating/customers/subscriptions/use">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="subscription_id" value="<?= (int) $sub['id'] ?>">
                                    <input type="hidden" name="customer_id" value="<?= (int) $customer['id'] ?>">

                                    <div class="modal-header">
                                        <h6 class="modal-title fw-bold">Einheit abbuchen: <?= htmlspecialchars((string) $sub['title'], ENT_QUOTES, 'UTF-8') ?></h6>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Datum der Entwertung</label>
                                            <input type="date" name="used_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Anzahl Einheiten</label>
                                            <input type="number" name="units" class="form-control" value="1" min="1" max="<?= $rem ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Bemerkung / Thema</label>
                                            <input type="text" name="note" class="form-control" placeholder="z. B. Hafenmanöver &amp; Anlegen">
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                                        <button type="submit" class="btn btn-sm btn-success">Jetzt abbuchen</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- TAB 5: Interne CRM-Notizen -->
    <div class="tab-pane fade <?= $activeTab === 'notes' ? 'show active' : '' ?>" id="tab-notes" role="tabpanel">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="post" action="?route=admin/boating/customers/update">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="id" value="<?= (int) $customer['id'] ?>">
                    <input type="hidden" name="active_tab" value="notes">

                    <!-- Stammdaten beibehalten -->
                    <input type="hidden" name="customer_number" value="<?= htmlspecialchars((string) ($customer['customer_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="type" value="<?= htmlspecialchars((string) ($customer['type'] ?? 'student'), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="crm_stage" value="<?= htmlspecialchars((string) ($customer['crm_stage'] ?? 'student'), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="user_id" value="<?= htmlspecialchars((string) ($customer['user_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="first_name" value="<?= htmlspecialchars((string) $customer['first_name'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="last_name" value="<?= htmlspecialchars((string) $customer['last_name'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="company" value="<?= htmlspecialchars((string) ($customer['company'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="email" value="<?= htmlspecialchars((string) ($customer['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="phone" value="<?= htmlspecialchars((string) ($customer['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="preferred_contact" value="<?= htmlspecialchars((string) ($customer['preferred_contact'] ?? 'email'), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="lead_source" value="<?= htmlspecialchars((string) ($customer['lead_source'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="tags" value="<?= htmlspecialchars((string) ($customer['tags'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="street" value="<?= htmlspecialchars((string) ($customer['street'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="zip" value="<?= htmlspecialchars((string) ($customer['zip'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="city" value="<?= htmlspecialchars((string) ($customer['city'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="birthdate" value="<?= htmlspecialchars((string) ($customer['birthdate'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="emergency_contact" value="<?= htmlspecialchars((string) ($customer['emergency_contact'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="target_license" value="<?= htmlspecialchars((string) ($customer['target_license'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="licenses_held" value="<?= htmlspecialchars((string) ($customer['licenses_held'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="license_number" value="<?= htmlspecialchars((string) ($customer['license_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="experience_level" value="<?= htmlspecialchars((string) ($customer['experience_level'] ?? 'beginner'), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="theory_passed" value="<?= !empty($customer['theory_passed']) ? '1' : '0' ?>">
                    <input type="hidden" name="theory_passed_at" value="<?= htmlspecialchars((string) ($customer['theory_passed_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="practical_passed" value="<?= !empty($customer['practical_passed']) ? '1' : '0' ?>">
                    <input type="hidden" name="practical_passed_at" value="<?= htmlspecialchars((string) ($customer['practical_passed_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="is_active" value="<?= !empty($customer['is_active']) ? '1' : '0' ?>">

                    <div class="row g-4">
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-danger"><i class="bi bi-shield-lock me-1"></i>Interne CRM-Notizen (nur für Fahrlehrer &amp; Disposition sichtbar)</label>
                            <textarea name="internal_notes" rows="6" class="form-control font-monospace" placeholder="Vertrauliche Vermerke, Zahlungsabsprachen, Ausbildungshistorie, persönliche Besonderheiten..."><?= htmlspecialchars((string) ($customer['internal_notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                            <div class="form-text small">Diese Notizen sind ausschließlich im internen Admin-Bereich sichtbar.</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Allgemeine Ausbildungsnotizen</label>
                            <textarea name="notes" rows="4" class="form-control" placeholder="Allgemeine Bemerkungen zum Schüler..."><?= htmlspecialchars((string) ($customer['notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>

                        <div class="col-12 pt-3 border-top d-flex justify-content-end">
                            <button type="submit" class="btn btn-sm btn-primary fw-semibold px-4">Notizen speichern</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Neues Abo anlegen -->
<?php if ($canManageSubs): ?>
<div class="modal fade" id="createSubscriptionModal" tabindex="-1" aria-labelledby="createSubscriptionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" action="?route=admin/boating/customers/subscriptions/create">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="customer_id" value="<?= (int) $customer['id'] ?>">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="createSubscriptionModalLabel">
                        <i class="bi bi-card-plus text-primary me-2"></i>Neues Abo / Punktekarte für <?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Titel der Karte <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" placeholder="z. B. 10er-Karte Fahrstunden Motorboot Kat. A" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Kartennummer / Code</label>
                            <input type="text" name="code" class="form-control" placeholder="Auto-ID falls leer (ABO-2026-XXXX)">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Guthaben-Typ</label>
                            <select name="type" class="form-select">
                                <option value="units">Einheiten (z. B. Fahrstunden)</option>
                                <option value="hours">Stunden (z. B. Bootsmiete)</option>
                                <option value="unlimited">Unbegrenzt (Saisonkarte)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Gesamtzahl Einheiten <span class="text-danger">*</span></label>
                            <input type="number" name="total_units" class="form-control" value="10" min="1" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Verkaufspreis (CHF / EUR)</label>
                            <input type="number" step="0.05" name="price" class="form-control" value="0.00" placeholder="0.00">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Gültig ab</label>
                            <input type="date" name="valid_from" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Gültig bis (Ablaufdatum)</label>
                            <input type="date" name="valid_until" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Verknüpfte Leistung (optional)</label>
                            <select name="service_id" class="form-select">
                                <option value="">-- Beliebige / Alle Leistungen --</option>
                                <?php foreach ($services as $srv): ?>
                                    <option value="<?= (int) $srv['id'] ?>"><?= htmlspecialchars((string) $srv['name'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                <option value="active">Aktiv</option>
                                <option value="depleted">Aufgebraucht</option>
                                <option value="expired">Abgelaufen</option>
                                <option value="cancelled">Storniert</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Notizen zum Abonnement</label>
                            <textarea name="notes" rows="2" class="form-control" placeholder="Zahlungsvermerke, Rabattcodes oder Besonderheiten..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-sm btn-primary fw-semibold px-4">Abo anlegen</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require dirname(__DIR__, 3) . '/views/layouts/admin.php';
