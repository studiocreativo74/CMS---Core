<?php

declare(strict_types=1);

/**
 * Bootsfahrschule - Fahrlehrer bearbeiten
 *
 * @var array<string, mixed> $instructor
 */

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$fullName = htmlspecialchars((string) $instructor['first_name'] . ' ' . (string) $instructor['last_name'], ENT_QUOTES, 'UTF-8');
$title = 'Fahrlehrer bearbeiten: ' . $fullName;
$currentRoute = 'admin/boating/instructors';
$canManage = BoatingSchoolService::canManage();
$csrfToken = class_exists('Csrf') ? Csrf::getToken() : '';
$users = $users ?? (class_exists('User') ? User::all() : []);

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
                <li class="breadcrumb-item"><a href="?route=admin/boating/instructors">Fahrlehrer</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= $fullName ?></li>
            </ol>
        </nav>
        <h1 class="h3 fw-bold text-dark mb-0">
            <i class="bi bi-person-badge text-primary me-2"></i>Fahrlehrer bearbeiten: <?= $fullName ?>
        </h1>
    </div>
    <div class="d-flex gap-2">
        <a href="?route=admin/boating/instructors" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Zurück zur Übersicht
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="post" action="?route=admin/boating/instructors/update" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="id" value="<?= (int) $instructor['id'] ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Vorname <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars((string) $instructor['first_name'], ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nachname <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars((string) $instructor['last_name'], ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">E-Mail</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars((string) ($instructor['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Telefon / Mobil</label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars((string) ($instructor['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Lizenz- / Ausweisnummer</label>
                            <input type="text" name="license_number" class="form-control" value="<?= htmlspecialchars((string) ($instructor['license_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Farbe</label>
                            <input type="color" name="color_code" class="form-control form-control-color w-100" value="<?= htmlspecialchars((string) ($instructor['color_code'] ?? '#0d6efd'), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">CMS-Benutzer</label>
                            <select name="user_id" class="form-select">
                                <option value="">-- Kein Benutzer --</option>
                                <?php foreach ($users as $u): ?>
                                    <option value="<?= (int) $u['id'] ?>" <?= (!empty($instructor['user_id']) && (int) $instructor['user_id'] === (int) $u['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars((string) ($u['name'] ?: $u['email']), ENT_QUOTES, 'UTF-8') ?> (ID: <?= (int) $u['id'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Titel & Öffentliche Angaben -->
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Berufsbezeichnung / Titel</label>
                            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars((string) ($instructor['title'] ?? 'Fahrcoach'), ENT_QUOTES, 'UTF-8') ?>" placeholder="z. B. Bootsfahrlehrer eidg. FA / Fahrcoach">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Öffentliche Mobilnummer (für Schüler)</label>
                            <div class="input-group">
                                <input type="text" name="public_mobile" class="form-control" value="<?= htmlspecialchars((string) ($instructor['public_mobile'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="+41 79 123 45 67">
                                <div class="input-group-text bg-white">
                                    <input class="form-check-input mt-0 me-1" type="checkbox" name="is_mobile_public" value="1" id="mobilePublicCheck" <?= !empty($instructor['is_mobile_public']) ? 'checked' : '' ?>>
                                    <label class="small text-muted mb-0" for="mobilePublicCheck">Sichtbar</label>
                                </div>
                            </div>
                        </div>

                        <!-- Payroll & Honorar -->
                        <div class="col-12">
                            <div class="p-3 bg-light rounded border">
                                <h6 class="fw-bold text-dark mb-2">
                                    <i class="bi bi-cash-stack text-success me-1"></i> Honorar &amp; Payroll-Konditionen
                                </h6>
                                <p class="text-muted small mb-3">
                                    Definiert die Berechnungsgrundlage für die Auswertung und Lohnabrechnung dieses Fahrlehrers.
                                </p>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold">Berechnungsmodell</label>
                                        <select name="pay_rate_type" class="form-select">
                                            <option value="per_hour" <?= (($instructor['pay_rate_type'] ?? 'per_hour') === 'per_hour') ? 'selected' : '' ?>>
                                                Pro Stunde (CHF / h)
                                            </option>
                                            <option value="per_appointment" <?= (($instructor['pay_rate_type'] ?? '') === 'per_appointment') ? 'selected' : '' ?>>
                                                Pauschale pro Termin
                                            </option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold">Ansatz / Honorar (CHF)</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white">CHF</span>
                                            <input type="number" step="0.05" min="0" name="pay_rate_amount" class="form-control" value="<?= htmlspecialchars((string) (isset($instructor['pay_rate_amount']) ? number_format((float) $instructor['pay_rate_amount'], 2, '.', '') : '0.00'), ENT_QUOTES, 'UTF-8') ?>" placeholder="0.00">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold">Externe Kennung / Lohn-Code</label>
                                        <input type="text" name="payroll_external_code" class="form-control" value="<?= htmlspecialchars((string) ($instructor['payroll_external_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="z. B. PERS-104 oder Kreditor-Nr.">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- HR-Daten & Personalakte (bs_instructor_details) - Streng RBAC-geschützt -->
                        <?php
                        $canViewHr = (!class_exists('Rbac') || Rbac::can('boating.coaches.hr_details') || BoatingSchoolService::canManagePayroll());
                        if ($canViewHr):
                        ?>
                        <div class="col-12">
                            <div class="p-3 bg-white rounded border border-primary border-opacity-25 shadow-sm">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <h6 class="fw-bold text-dark mb-0">
                                        <i class="bi bi-shield-lock-fill text-primary me-1"></i> HR-Personalakte &amp; Schweizer Sozialversicherung
                                    </h6>
                                    <span class="badge bg-primary-subtle text-primary small">Vertraulich (boating.coaches.hr_details)</span>
                                </div>
                                <p class="text-muted small mb-3">
                                    Sensible Personaldaten zur Weiterverarbeitung im Schweizer Lohnsystem (AHV-Nummer, Bankverbindung &amp; individuelle Abzugssätze).
                                </p>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold">AHV-Nummer (CH)</label>
                                        <input type="text" name="ahv_number" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($hrDetails['ahv_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="756.xxxx.xxxx.xx">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold">Geburtsdatum</label>
                                        <input type="date" name="birth_date" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($hrDetails['birth_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold">Eintrittsdatum</label>
                                        <input type="date" name="entry_date" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($hrDetails['entry_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Bank IBAN (Schweiz / FL)</label>
                                        <input type="text" name="bank_iban" class="form-control form-control-sm font-monospace" value="<?= htmlspecialchars((string) ($hrDetails['bank_iban'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="CHxx xxxx xxxx xxxx xxxx x">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-semibold">Bank Name</label>
                                        <input type="text" name="bank_name" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($hrDetails['bank_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="z. B. ZKB, UBS">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-semibold">Bank Ort</label>
                                        <input type="text" name="bank_city" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($hrDetails['bank_city'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="z. B. Zürich">
                                    </div>

                                    <div class="col-md-8">
                                        <label class="form-label small fw-semibold">Privatadresse (Strasse, Nr, PLZ, Ort)</label>
                                        <input type="text" name="address" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($hrDetails['address'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Mustergasse 12, 8001 Zürich">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold">Basis-Stundensatz HR (CHF)</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-light">CHF</span>
                                            <input type="number" step="0.05" min="0" name="hourly_rate" class="form-control" value="<?= htmlspecialchars((string) (isset($hrDetails['hourly_rate']) ? number_format((float) $hrDetails['hourly_rate'], 2, '.', '') : ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="z. B. 65.00">
                                        </div>
                                    </div>

                                    <!-- Individuelle Lohnabzugs-Overrides (CH) -->
                                    <div class="col-12 mt-3 pt-3 border-top">
                                        <h6 class="small fw-bold text-dark mb-2">
                                            <i class="bi bi-sliders text-secondary me-1"></i> Individuelle Abweichungen / Lohnabzüge (optional, übersteuert Standard)
                                        </h6>
                                        <div class="row g-2">
                                            <div class="col-md-2">
                                                <label class="form-label small text-muted">AHV (%)</label>
                                                <input type="number" step="0.01" min="0" max="100" name="ahv_rate_override" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($hrDetails['ahv_rate_override'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Std: 5.30">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small text-muted">ALV (%)</label>
                                                <input type="number" step="0.01" min="0" max="100" name="alv_rate_override" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($hrDetails['alv_rate_override'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Std: 1.10">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small text-muted">NBU (%)</label>
                                                <input type="number" step="0.01" min="0" max="100" name="nbu_rate_override" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($hrDetails['nbu_rate_override'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Std: 1.30">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small text-muted">BVG (%)</label>
                                                <input type="number" step="0.01" min="0" max="100" name="bvg_rate_override" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($hrDetails['bvg_rate_override'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="z.B. 3.50">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small text-muted">Quellensteuer (%)</label>
                                                <input type="number" step="0.01" min="0" max="100" name="withholding_tax_rate" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($hrDetails['withholding_tax_rate'] ?? '0.00'), ENT_QUOTES, 'UTF-8') ?>" placeholder="0.00">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small text-muted">Pauschal (CHF)</label>
                                                <input type="number" step="0.05" min="0" name="misc_fixed_deduction" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($hrDetails['misc_fixed_deduction'] ?? '0.00'), ENT_QUOTES, 'UTF-8') ?>" placeholder="0.00">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Qualifikationen / Scheine (Mehrzeilenfeld)</label>
                            <textarea name="qualifications" rows="3" class="form-control" placeholder="z. B. Kat. A Motorboot, Kat. D Segel, Hochsee, Funkzeugnis SRC"><?= htmlspecialchars((string) ($instructor['qualifications'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Neues Foto hochladen (optional)</label>
                            <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Notizen / Bemerkungen</label>
                            <textarea name="notes" rows="4" class="form-control"><?= htmlspecialchars((string) ($instructor['notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="instActiveSwitch" value="1" <?= !empty($instructor['is_active']) ? 'checked' : '' ?>>
                                <label class="form-check-label small fw-semibold" for="instActiveSwitch">Fahrlehrer ist aktiv</label>
                            </div>
                        </div>

                        <div class="col-12 pt-3 border-top d-flex gap-2 justify-content-end">
                            <a href="?route=admin/boating/instructors" class="btn btn-sm btn-secondary">Abbrechen</a>
                            <button type="submit" class="btn btn-sm btn-primary fw-semibold px-4">Änderungen speichern</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Seitenleiste mit Foto & Status -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0 text-dark">Profil-Vorschau</h6>
            </div>
            <div class="card-body p-3 text-center">
                <?php if (!empty($instructor['image_path'])): ?>
                    <img src="<?= htmlspecialchars((string) $instructor['image_path'], ENT_QUOTES, 'UTF-8') ?>" alt="Foto" class="rounded-circle mb-3 shadow-sm object-fit-cover" style="width: 120px; height: 120px;">
                <?php else: ?>
                    <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center text-white fw-bold fs-2 mb-3 shadow-sm" style="width: 120px; height: 120px; background-color: <?= htmlspecialchars((string) ($instructor['color_code'] ?? '#0d6efd'), ENT_QUOTES, 'UTF-8') ?>;">
                        <?= mb_substr((string) $instructor['first_name'], 0, 1) . mb_substr((string) $instructor['last_name'], 0, 1) ?>
                    </div>
                <?php endif; ?>

                <h5 class="fw-bold text-dark mb-1"><?= $fullName ?></h5>
                <div class="text-muted small mb-3">
                    <?= !empty($instructor['is_active']) ? '<span class="badge bg-success-subtle text-success">Aktiv</span>' : '<span class="badge bg-secondary-subtle text-muted">Inaktiv</span>' ?>
                </div>

                <div class="text-start small">
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Fahrlehrer-ID:</span>
                        <span class="font-monospace">#<?= (int) $instructor['id'] ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Kalender-Farbe:</span>
                        <div class="d-flex align-items-center gap-1">
                            <span class="rounded d-inline-block border" style="width: 14px; height: 14px; background-color: <?= htmlspecialchars((string) ($instructor['color_code'] ?? '#0d6efd'), ENT_QUOTES, 'UTF-8') ?>;"></span>
                            <span class="font-monospace small"><?= htmlspecialchars((string) ($instructor['color_code'] ?? '#0d6efd'), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Honorar:</span>
                        <span class="fw-semibold text-dark">
                            CHF <?= number_format((float) ($instructor['pay_rate_amount'] ?? 0.0), 2) ?>
                            <small class="text-muted fw-normal">(<?= ($instructor['pay_rate_type'] ?? 'per_hour') === 'per_appointment' ? 'Termin' : 'Std.' ?>)</small>
                        </span>
                    </div>
                    <?php if (!empty($instructor['payroll_external_code'])): ?>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Lohn-Code:</span>
                        <span class="font-monospace small"><?= htmlspecialchars((string) $instructor['payroll_external_code'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Erfasst am:</span>
                        <span><?= htmlspecialchars((string) $instructor['created_at'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted">Aktualisiert:</span>
                        <span><?= htmlspecialchars((string) $instructor['updated_at'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require dirname(__DIR__, 3) . '/views/layouts/admin.php';
