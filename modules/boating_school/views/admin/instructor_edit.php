<?php

declare(strict_types=1);

/**
 * Bootsfahrschule - Coach bearbeiten
 *
 * @var array<string, mixed> $instructor
 * @var array<int, array<string, mixed>> $users
 * @var array<string, mixed>|null $hrDetails
 */

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$fullName = htmlspecialchars((string) $instructor['first_name'] . ' ' . (string) $instructor['last_name'], ENT_QUOTES, 'UTF-8');
$title = 'Coach bearbeiten: ' . $fullName;
$currentRoute = 'admin/boating/instructors';
$canManage = BoatingSchoolService::canManage();
$csrfToken = class_exists('Csrf') ? Csrf::getToken() : '';
$users = $users ?? (class_exists('User') ? User::all() : []);
$hrDetails = $hrDetails ?? [];

ob_start();
?>

<style>
.coach-edit-card {
    border: none;
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 20px 45px -15px rgba(15, 23, 42, 0.15);
}
.coach-edit-header {
    background-color: #0f172a;
    padding: 24px 28px 20px 28px;
}
.coach-tab-container {
    background-color: #1e293b;
    padding: 4px;
    border-radius: 12px;
    display: inline-flex;
    gap: 4px;
}
.coach-tab-btn {
    border: none;
    font-size: 0.85rem;
    font-weight: 600;
    padding: 7px 18px;
    border-radius: 8px;
    transition: all 0.2s ease;
    cursor: pointer;
}
.coach-tab-btn.active {
    background-color: #5c3d2e !important;
    color: #ffffff !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
}
.coach-tab-btn:not(.active) {
    background-color: transparent !important;
    color: #94a3b8 !important;
}
.coach-tab-btn:not(.active):hover {
    color: #f1f5f9 !important;
}
.coach-field-label {
    display: block;
    font-size: 0.70rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748b;
    margin-bottom: 6px;
}
.coach-form-control {
    background-color: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 14px;
    padding: 10px 14px;
    font-size: 0.92rem;
    color: #0f172a;
    width: 100%;
    transition: all 0.2s ease;
}
.coach-form-control:focus {
    background-color: #ffffff;
    border-color: #5c3d2e;
    outline: none;
    box-shadow: 0 0 0 3px rgba(92, 61, 46, 0.14);
}
.coach-card-box {
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
    <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4 rounded-3" role="alert">
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
    <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4 rounded-3" role="alert">
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
                <li class="breadcrumb-item"><a href="?route=admin/boating/instructors">Coaches</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= $fullName ?></li>
            </ol>
        </nav>
        <h1 class="h3 fw-bold text-dark mb-0">Coach bearbeiten</h1>
        <div class="text-muted small mt-1">Verwalten Sie das öffentliche Profil und interne Daten von <?= $fullName ?>.</div>
    </div>
    <div class="d-flex gap-2">
        <a href="?route=admin/boating/instructors" class="btn btn-outline-secondary btn-sm rounded-3 px-3">
            <i class="bi bi-arrow-left me-1"></i> Zurück zur Übersicht
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Formular-Karte -->
    <div class="col-lg-8">
        <div class="card coach-edit-card shadow-sm">
            <!-- Dunkelblauer Header mit Segmented Tab Control -->
            <div class="coach-edit-header text-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="fw-bold text-white mb-1">Coach bearbeiten</h4>
                        <div class="text-white-50 small">Verwalten Sie das öffentliche Profil und interne Daten.</div>
                    </div>
                </div>

                <div class="coach-tab-container mt-3">
                    <button type="button" class="coach-tab-btn active" id="editTabBtnPublic" data-tab="public">
                        Öffentlich
                    </button>
                    <button type="button" class="coach-tab-btn" id="editTabBtnInternal" data-tab="internal">
                        Intern / PII
                    </button>
                </div>
            </div>

            <div class="card-body p-4 bg-white">
                <form method="post" action="?route=admin/boating/instructors/update" enctype="multipart/form-data" id="instructorEditForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="id" value="<?= (int) $instructor['id'] ?>">
                    <input type="hidden" name="redirect_to" value="?route=admin/boating/instructors/edit&id=<?= (int) $instructor['id'] ?>">

                    <!-- TAB 1: ÖFFENTLICH -->
                    <div id="editPanePublic">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="coach-field-label">Vorname <span class="text-danger">*</span></label>
                                    <input type="text" name="first_name" class="coach-form-control" value="<?= htmlspecialchars((string) $instructor['first_name'], ENT_QUOTES, 'UTF-8') ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="coach-field-label">Nachname <span class="text-danger">*</span></label>
                                    <input type="text" name="last_name" class="coach-form-control" value="<?= htmlspecialchars((string) $instructor['last_name'], ENT_QUOTES, 'UTF-8') ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="coach-field-label">Titel / Funktion</label>
                                    <input type="text" name="title" class="coach-form-control" value="<?= htmlspecialchars((string) ($instructor['title'] ?? 'Fahrcoach'), ENT_QUOTES, 'UTF-8') ?>" placeholder="Fahrcoach">
                                </div>

                                <div>
                                    <label class="coach-field-label">Bild URL / Profilfoto</label>
                                    <input type="url" name="image_url" class="coach-form-control mb-1" value="<?= htmlspecialchars((string) ($instructor['image_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="https://beispiel.ch/foto.jpg">
                                    <div class="small text-muted mb-1" style="font-size: 0.75rem;">Oder neues Foto hochladen:</div>
                                    <input type="file" name="image" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp" style="border-radius: 10px; background: #f8fafc; font-size: 0.8rem;">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="coach-field-label">Öffentliche Mobilnummer</label>
                                    <div class="coach-card-box">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="form-check m-0">
                                                <input class="form-check-input" type="checkbox" name="is_mobile_public" id="editIsMobilePublic" value="1" <?= !empty($instructor['is_mobile_public']) ? 'checked' : '' ?>>
                                                <label class="form-check-label fw-semibold text-dark small" for="editIsMobilePublic" style="cursor: pointer; white-space: nowrap;">Freischalten</label>
                                            </div>
                                            <input type="text" name="public_mobile" class="form-control form-control-sm border-0 bg-white shadow-none" value="<?= htmlspecialchars((string) ($instructor['public_mobile'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="z.B. +41 79 ..." style="border-radius: 10px; padding: 7px 10px; font-size: 0.88rem;">
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="coach-field-label">Beschreibung / Bio</label>
                                    <textarea name="qualifications" rows="4" class="coach-form-control" placeholder="Biografie, Bootsqualifikationen, Erfahrung..."><?= htmlspecialchars((string) ($instructor['qualifications'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                                </div>

                                <div class="coach-card-box d-flex align-items-center justify-content-between p-2 px-3">
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input" type="checkbox" name="is_active" id="editIsActive" value="1" <?= !empty($instructor['is_active']) ? 'checked' : '' ?>>
                                        <label class="form-check-label small fw-semibold text-dark ms-1" for="editIsActive">Coach ist aktiv</label>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="coach-field-label mb-0" style="font-size: 0.68rem;">Farbe:</span>
                                        <input type="color" name="color_code" class="form-control form-control-color border-0 p-0" value="<?= htmlspecialchars((string) ($instructor['color_code'] ?? '#0d6efd'), ENT_QUOTES, 'UTF-8') ?>" style="width: 28px; height: 28px; border-radius: 8px; cursor: pointer;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: INTERN / PII -->
                    <div id="editPaneInternal" style="display: none;">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="coach-field-label"><i class="bi bi-envelope me-1"></i> E-Mail Adresse <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="coach-form-control" value="<?= htmlspecialchars((string) ($instructor['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required placeholder="test@coach.ch">
                                </div>

                                <div class="mb-3">
                                    <label class="coach-field-label"><i class="bi bi-telephone me-1"></i> Telefonnummer</label>
                                    <input type="text" name="phone" class="coach-form-control" value="<?= htmlspecialchars((string) ($instructor['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="+41 79 ...">
                                </div>

                                <div class="mb-3">
                                    <label class="coach-field-label"><i class="bi bi-calendar-event me-1"></i> Geburtsdatum</label>
                                    <input type="date" name="birth_date" class="coach-form-control" value="<?= htmlspecialchars((string) ($hrDetails['birth_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="coach-field-label"><i class="bi bi-person-lock me-1"></i> CMS-Benutzer</label>
                                    <select name="user_id" class="coach-form-control">
                                        <option value="">-- Kein Benutzer --</option>
                                        <?php foreach ($users as $u): ?>
                                            <option value="<?= (int) $u['id'] ?>" <?= (!empty($instructor['user_id']) && (int) $instructor['user_id'] === (int) $u['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars((string) ($u['name'] ?: $u['email']), ENT_QUOTES, 'UTF-8') ?> (ID: <?= (int) $u['id'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <label class="coach-field-label">Notizen / Bemerkungen (Intern)</label>
                                    <textarea name="notes" rows="2" class="coach-form-control" placeholder="Interne Bemerkungen..."><?= htmlspecialchars((string) ($instructor['notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="coach-field-label"><i class="bi bi-geo-alt me-1"></i> Adresse</label>
                                    <input type="text" name="address" class="coach-form-control" value="<?= htmlspecialchars((string) ($hrDetails['address'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Musterstrasse 12, 8000 Zürich">
                                </div>

                                <div class="mb-3">
                                    <label class="coach-field-label"><i class="bi bi-credit-card me-1"></i> AHV Nummer</label>
                                    <input type="text" name="ahv_number" class="coach-form-control font-monospace" value="<?= htmlspecialchars((string) ($hrDetails['ahv_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="756.XXXX.XXXX.XX">
                                </div>

                                <div class="mb-3">
                                    <label class="coach-field-label"><i class="bi bi-bank me-1"></i> Bankverbindung (IBAN)</label>
                                    <input type="text" name="bank_iban" class="coach-form-control font-monospace" value="<?= htmlspecialchars((string) ($hrDetails['bank_iban'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="CHxx xxxx xxxx xxxx x">
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="coach-field-label">Bankname</label>
                                        <input type="text" name="bank_name" class="coach-form-control" value="<?= htmlspecialchars((string) ($hrDetails['bank_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="z.B. ZKB">
                                    </div>
                                    <div class="col-6">
                                        <label class="coach-field-label">Ort (Bank)</label>
                                        <input type="text" name="bank_city" class="coach-form-control" value="<?= htmlspecialchars((string) ($hrDetails['bank_city'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="z.B. Zürich">
                                    </div>
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="coach-field-label">Eintrittsdatum</label>
                                        <input type="date" name="entry_date" class="coach-form-control" value="<?= htmlspecialchars((string) ($hrDetails['entry_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    </div>
                                    <div class="col-6">
                                        <label class="coach-field-label">Stundenlohn (CHF)</label>
                                        <input type="number" step="0.05" min="0" name="pay_rate_amount" class="coach-form-control" value="<?= htmlspecialchars((string) (isset($instructor['pay_rate_amount']) ? number_format((float) $instructor['pay_rate_amount'], 2, '.', '') : '0.00'), ENT_QUOTES, 'UTF-8') ?>" placeholder="0.00">
                                    </div>
                                </div>

                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="coach-field-label">Lohnmodell</label>
                                        <select name="pay_rate_type" class="coach-form-control">
                                            <option value="per_hour" <?= (($instructor['pay_rate_type'] ?? 'per_hour') === 'per_hour') ? 'selected' : '' ?>>Pro Stunde</option>
                                            <option value="per_appointment" <?= (($instructor['pay_rate_type'] ?? '') === 'per_appointment') ? 'selected' : '' ?>>Pauschale/Termin</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="coach-field-label">Lohn-Code</label>
                                        <input type="text" name="payroll_external_code" class="coach-form-control" value="<?= htmlspecialchars((string) ($instructor['payroll_external_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="z.B. PERS-101">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="pt-4 mt-4 border-top">
                        <div class="d-flex align-items-center justify-content-center gap-3">
                            <a href="?route=admin/boating/instructors" class="btn btn-coach-cancel px-4 py-2" style="min-width: 140px;">
                                Abbrechen
                            </a>
                            <button type="submit" class="btn btn-coach-brown px-5 py-2 shadow-sm" style="min-width: 180px;">
                                Coach speichern
                            </button>
                        </div>

                        <?php if ($canManage): ?>
                        <div class="text-center mt-3">
                            <button type="button" class="btn btn-link text-danger text-decoration-none fw-semibold small p-0 d-inline-flex align-items-center gap-1" onclick="if(confirm('Möchten Sie diesen Coach wirklich löschen?')) document.getElementById('deleteCoachFormStandalone').submit();">
                                <i class="bi bi-trash"></i> Coach löschen
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Seitenleiste mit Foto & Status -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-bold mb-0 text-dark">Profil-Vorschau</h6>
            </div>
            <div class="card-body p-4 text-center">
                <?php 
                $photo = !empty($instructor['image_path']) ? (string) $instructor['image_path'] : (!empty($instructor['image_url']) ? (string) $instructor['image_url'] : '');
                ?>
                <?php if (!empty($photo)): ?>
                    <img src="<?= htmlspecialchars($photo, ENT_QUOTES, 'UTF-8') ?>" alt="Foto" class="rounded-circle mb-3 shadow-sm object-fit-cover border" style="width: 110px; height: 110px;">
                <?php else: ?>
                    <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center text-white fw-bold fs-2 mb-3 shadow-sm" style="width: 110px; height: 110px; background-color: <?= htmlspecialchars((string) ($instructor['color_code'] ?? '#0d6efd'), ENT_QUOTES, 'UTF-8') ?>;">
                        <?= mb_substr((string) $instructor['first_name'], 0, 1) . mb_substr((string) $instructor['last_name'], 0, 1) ?>
                    </div>
                <?php endif; ?>

                <h5 class="fw-bold text-dark mb-1"><?= $fullName ?></h5>
                <div class="badge bg-light text-dark border px-2 py-1 mb-2">
                    <?= htmlspecialchars((string) ($instructor['title'] ?? 'Fahrcoach'), ENT_QUOTES, 'UTF-8') ?>
                </div>
                <div class="mb-3">
                    <?= !empty($instructor['is_active']) ? '<span class="badge bg-success-subtle text-success">Aktiv</span>' : '<span class="badge bg-secondary-subtle text-muted">Inaktiv</span>' ?>
                </div>

                <div class="text-start small">
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Coach-ID:</span>
                        <span class="font-monospace">#<?= (int) $instructor['id'] ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Kalender-Farbe:</span>
                        <div class="d-flex align-items-center gap-1">
                            <span class="rounded d-inline-block border" style="width: 14px; height: 14px; background-color: <?= htmlspecialchars((string) ($instructor['color_code'] ?? '#0d6efd'), ENT_QUOTES, 'UTF-8') ?>;"></span>
                            <span class="font-monospace small"><?= htmlspecialchars((string) ($instructor['color_code'] ?? '#0d6efd'), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Honorar:</span>
                        <span class="fw-semibold text-dark">
                            CHF <?= number_format((float) ($instructor['pay_rate_amount'] ?? 0.0), 2) ?>
                            <small class="text-muted fw-normal">(<?= ($instructor['pay_rate_type'] ?? 'per_hour') === 'per_appointment' ? 'Termin' : 'Std.' ?>)</small>
                        </span>
                    </div>
                    <?php if (!empty($instructor['public_mobile'])): ?>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Öffentl. Mobil:</span>
                        <span class="fw-semibold text-primary"><?= htmlspecialchars((string) $instructor['public_mobile']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($instructor['payroll_external_code'])): ?>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Lohn-Code:</span>
                        <span class="font-monospace small"><?= htmlspecialchars((string) $instructor['payroll_external_code'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Erfasst am:</span>
                        <span><?= htmlspecialchars((string) $instructor['created_at'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span class="text-muted">Aktualisiert:</span>
                        <span><?= htmlspecialchars((string) $instructor['updated_at'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($canManage): ?>
<form method="post" action="?route=admin/boating/instructors/delete" id="deleteCoachFormStandalone" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="id" value="<?= (int) $instructor['id'] ?>">
</form>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const editTabBtnPublic = document.getElementById('editTabBtnPublic');
    const editTabBtnInternal = document.getElementById('editTabBtnInternal');
    const editPanePublic = document.getElementById('editPanePublic');
    const editPaneInternal = document.getElementById('editPaneInternal');

    function switchEditTab(tab) {
        if (tab === 'public') {
            editTabBtnPublic.classList.add('active');
            editTabBtnInternal.classList.remove('active');
            editPanePublic.style.display = 'block';
            editPaneInternal.style.display = 'none';
        } else {
            editTabBtnInternal.classList.add('active');
            editTabBtnPublic.classList.remove('active');
            editPaneInternal.style.display = 'block';
            editPanePublic.style.display = 'none';
        }
    }

    if (editTabBtnPublic && editTabBtnInternal) {
        editTabBtnPublic.addEventListener('click', () => switchEditTab('public'));
        editTabBtnInternal.addEventListener('click', () => switchEditTab('internal'));
    }
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../../../views/layouts/admin.php';
