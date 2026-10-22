<?php

declare(strict_types=1);

/**
 * Bootsfahrschule - Coaches & Fahrlehrer Übersicht
 *
 * @var array<int, array<string, mixed>> $instructors
 * @var int $totalInstructors
 * @var array<string, mixed> $filters
 * @var bool $tablesCreated
 * @var array<int, array<string, mixed>> $hrDetailsMap
 */

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$title = 'Coaches & Fahrlehrer - Bootsfahrschule';
$currentRoute = 'admin/boating/instructors';
$canManage = BoatingSchoolService::canManage();
$csrfToken = class_exists('Csrf') ? Csrf::getToken() : '';
$users = $users ?? (class_exists('User') ? User::all() : []);
$hrDetailsMap = $hrDetailsMap ?? [];

ob_start();
?>

<style>
/* Scoped Coach Modal & Card Design */
.coach-modal-content {
    border: none !important;
    border-radius: 24px !important;
    overflow: hidden;
    box-shadow: 0 25px 60px -15px rgba(15, 23, 42, 0.35);
}
.coach-modal-header {
    background-color: #0f172a;
    padding: 24px 28px 20px 28px;
    border: none;
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

<!-- Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="?route=admin/boating">Bootsfahrschule</a></li>
                <li class="breadcrumb-item active" aria-current="page">Coaches</li>
            </ol>
        </nav>
        <h1 class="h3 fw-bold text-dark mb-0">Coaches</h1>
        <div class="text-muted small mt-1">Ihre Instruktoren und deren Qualifikationen (<?= (int) $totalInstructors ?>)</div>
    </div>
    <div class="d-flex gap-2">
        <a href="?route=admin/boating" class="btn btn-outline-secondary btn-sm rounded-3 px-3">
            <i class="bi bi-arrow-left me-1"></i> Dashboard
        </a>
        <?php if ($canManage): ?>
            <button type="button" class="btn btn-coach-brown btn-sm px-3 shadow-sm d-flex align-items-center gap-2" id="btnOpenCreateCoach">
                <i class="bi bi-plus-lg"></i> Coach hinzufügen
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Filter-Leiste -->
<div class="card border-0 shadow-sm mb-4 rounded-3">
    <div class="card-body p-3">
        <form method="get" class="row g-2 align-items-center">
            <input type="hidden" name="route" value="admin/boating/instructors">
            <div class="col-md-7">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Suchbegriff (Name, E-Mail, Titel, Qualifikationen)..." value="<?= htmlspecialchars((string) ($filters['search'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="is_active" class="form-select form-select-sm">
                    <option value="">Status: Alle</option>
                    <option value="1" <?= ($filters['is_active'] ?? '') === '1' ? 'selected' : '' ?>>Aktiv</option>
                    <option value="0" <?= ($filters['is_active'] ?? '') === '0' ? 'selected' : '' ?>>Inaktiv</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-secondary flex-grow-1 rounded-2">Filtern</button>
                <?php if (!empty($filters['search']) || (isset($filters['is_active']) && $filters['is_active'] !== '')): ?>
                    <a href="?route=admin/boating/instructors" class="btn btn-sm btn-outline-secondary rounded-2" title="Zurücksetzen">
                        <i class="bi bi-x-lg"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Fahrlehrer-Tabelle -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-body p-0">
        <?php if (empty($instructors)): ?>
            <div class="text-center py-5">
                <i class="bi bi-person-badge text-muted fs-1 d-block mb-2"></i>
                <h6 class="text-muted mb-2">Keine Coaches gefunden</h6>
                <p class="text-muted small mb-3">Erfassen Sie Instruktoren für Theorie- und Praxisfahrstunden.</p>
                <?php if ($canManage): ?>
                    <button type="button" class="btn btn-sm btn-coach-brown px-3" onclick="openCreateCoachModal()">
                        <i class="bi bi-plus-lg me-1"></i> Coach hinzufügen
                    </button>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th style="width: 50px;">Foto</th>
                            <th>Coach</th>
                            <th>Funktion / Titel</th>
                            <th>Farbe</th>
                            <th>Honorar / Satz</th>
                            <th>Kontakt</th>
                            <th>Status</th>
                            <th class="text-end" style="width: 140px;">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($instructors as $inst): ?>
                            <?php 
                            $instId = (int) $inst['id'];
                            $hr = $hrDetailsMap[$instId] ?? [];
                            $photoUrl = !empty($inst['image_path']) ? (string) $inst['image_path'] : (!empty($inst['image_url']) ? (string) $inst['image_url'] : '');
                            ?>
                            <tr>
                                <td>
                                    <?php if (!empty($photoUrl)): ?>
                                        <img src="<?= htmlspecialchars($photoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Foto" class="rounded-circle object-fit-cover border" style="width: 40px; height: 40px;">
                                    <?php else: ?>
                                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold small" style="width: 40px; height: 40px; background-color: <?= htmlspecialchars((string) ($inst['color_code'] ?? '#0d6efd'), ENT_QUOTES, 'UTF-8') ?>;">
                                            <?= mb_substr((string) $inst['first_name'], 0, 1) . mb_substr((string) $inst['last_name'], 0, 1) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">
                                        <?= htmlspecialchars((string) $inst['first_name'] . ' ' . (string) $inst['last_name'], ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <?php if (!empty($inst['user_id'])): ?>
                                        <span class="badge bg-light text-muted border small"><i class="bi bi-person-check me-1"></i>CMS-User #<?= (int) $inst['user_id'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border fw-normal px-2 py-1">
                                        <?= htmlspecialchars((string) ($inst['title'] ?? 'Fahrcoach'), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="rounded d-inline-block border" style="width: 20px; height: 20px; background-color: <?= htmlspecialchars((string) ($inst['color_code'] ?? '#0d6efd'), ENT_QUOTES, 'UTF-8') ?>;"></span>
                                        <span class="font-monospace small text-muted"><?= htmlspecialchars((string) ($inst['color_code'] ?? '#0d6efd'), ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark small">
                                        CHF <?= number_format((float) ($inst['pay_rate_amount'] ?? 0.0), 2) ?>
                                        <span class="badge bg-light text-muted border fw-normal ms-1">
                                            <?= ($inst['pay_rate_type'] ?? 'per_hour') === 'per_appointment' ? 'Pauschale' : '/ Std.' ?>
                                        </span>
                                    </div>
                                    <?php if (!empty($inst['payroll_external_code'])): ?>
                                        <div class="font-monospace text-muted small" title="Lohn-Code / Externe Kennung">
                                            <i class="bi bi-tag text-muted me-1"></i><?= htmlspecialchars((string) $inst['payroll_external_code'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($inst['public_mobile']) && !empty($inst['is_mobile_public'])): ?>
                                        <div class="small fw-semibold text-primary"><i class="bi bi-phone me-1"></i><?= htmlspecialchars((string) $inst['public_mobile'], ENT_QUOTES, 'UTF-8') ?> <span class="badge bg-primary-subtle text-primary" style="font-size: 0.68rem;">Öffentlich</span></div>
                                    <?php elseif (!empty($inst['phone'])): ?>
                                        <div class="small"><i class="bi bi-telephone me-1 text-muted"></i><?= htmlspecialchars((string) $inst['phone'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($inst['email'])): ?>
                                        <div class="small text-muted"><i class="bi bi-envelope me-1 text-muted"></i><?= htmlspecialchars((string) $inst['email'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($canManage): ?>
                                        <form method="post" action="?route=admin/boating/instructors/toggle-status" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="id" value="<?= $instId ?>">
                                            <button type="submit" class="btn btn-sm p-0 border-0 bg-transparent" title="Status umschalten">
                                                <?php if (!empty($inst['is_active'])): ?>
                                                    <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>Aktiv</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary-subtle text-muted"><i class="bi bi-pause-circle me-1"></i>Inaktiv</span>
                                                <?php endif; ?>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <?php if (!empty($inst['is_active'])): ?>
                                            <span class="badge bg-success-subtle text-success">Aktiv</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-muted">Inaktiv</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" 
                                                class="btn btn-outline-secondary btn-edit-coach" 
                                                title="Coach im Modal bearbeiten"
                                                data-id="<?= $instId ?>"
                                                data-first_name="<?= htmlspecialchars((string) ($inst['first_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-last_name="<?= htmlspecialchars((string) ($inst['last_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-title="<?= htmlspecialchars((string) ($inst['title'] ?? 'Fahrcoach'), ENT_QUOTES, 'UTF-8') ?>"
                                                data-email="<?= htmlspecialchars((string) ($inst['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-phone="<?= htmlspecialchars((string) ($inst['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-public_mobile="<?= htmlspecialchars((string) ($inst['public_mobile'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-is_mobile_public="<?= !empty($inst['is_mobile_public']) ? '1' : '0' ?>"
                                                data-image_url="<?= htmlspecialchars((string) ($inst['image_url'] ?? $inst['image_path'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-qualifications="<?= htmlspecialchars((string) ($inst['qualifications'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-color_code="<?= htmlspecialchars((string) ($inst['color_code'] ?? '#0d6efd'), ENT_QUOTES, 'UTF-8') ?>"
                                                data-user_id="<?= (int) ($inst['user_id'] ?? 0) ?>"
                                                data-pay_rate_type="<?= htmlspecialchars((string) ($inst['pay_rate_type'] ?? 'per_hour'), ENT_QUOTES, 'UTF-8') ?>"
                                                data-pay_rate_amount="<?= htmlspecialchars((string) (isset($inst['pay_rate_amount']) ? number_format((float) $inst['pay_rate_amount'], 2, '.', '') : '0.00'), ENT_QUOTES, 'UTF-8') ?>"
                                                data-payroll_external_code="<?= htmlspecialchars((string) ($inst['payroll_external_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-notes="<?= htmlspecialchars((string) ($inst['notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-is_active="<?= !empty($inst['is_active']) ? '1' : '0' ?>"
                                                data-birth_date="<?= htmlspecialchars((string) ($hr['birth_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-entry_date="<?= htmlspecialchars((string) ($hr['entry_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-ahv_number="<?= htmlspecialchars((string) ($hr['ahv_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-bank_iban="<?= htmlspecialchars((string) ($hr['bank_iban'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-bank_name="<?= htmlspecialchars((string) ($hr['bank_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-bank_city="<?= htmlspecialchars((string) ($hr['bank_city'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-address="<?= htmlspecialchars((string) ($hr['address'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="?route=admin/boating/instructors/edit&id=<?= $instId ?>" class="btn btn-outline-secondary" title="Zur Detailseite">
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                        <?php if ($canManage): ?>
                                            <form method="post" action="?route=admin/boating/instructors/delete" class="d-inline" onsubmit="return confirm('Möchten Sie diesen Coach wirklich löschen?');">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="id" value="<?= $instId ?>">
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
<!-- MODERN COACH MODAL (Braunes Farbschema nach Vorlage)                      -->
<!-- ========================================================================= -->
<?php if ($canManage): ?>
<div class="modal fade" id="coachModal" tabindex="-1" aria-labelledby="coachModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content coach-modal-content">
            <!-- Modal Header (Dunkelblau/Navy mit Segmented Switcher) -->
            <div class="coach-modal-header text-white">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <h4 class="fw-bold text-white mb-1" id="coachModalTitle">Coach bearbeiten</h4>
                        <div class="text-white-50 small" id="coachModalSubtitle">Verwalten Sie das öffentliche Profil und interne Daten.</div>
                    </div>
                    <button type="button" class="btn-close btn-close-white opacity-75" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>

                <!-- Segmented Control Switcher -->
                <div class="coach-tab-container mt-3">
                    <button type="button" class="coach-tab-btn active" id="tabBtnPublic" data-tab="public">
                        Öffentlich
                    </button>
                    <button type="button" class="coach-tab-btn" id="tabBtnInternal" data-tab="internal">
                        Intern / PII
                    </button>
                </div>
            </div>

            <!-- Form -->
            <form method="post" action="?route=admin/boating/instructors/create" enctype="multipart/form-data" id="coachForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="redirect_to" value="?route=admin/boating/instructors">
                <input type="hidden" name="id" id="coachFieldId" value="">

                <div class="modal-body p-4 bg-white">
                    <!-- ===================================================== -->
                    <!-- TAB 1: ÖFFENTLICH                                     -->
                    <!-- ===================================================== -->
                    <div id="coachPanePublic" class="coach-tab-pane">
                        <div class="row g-4">
                            <!-- Linke Spalte -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="coach-field-label">Vorname <span class="text-danger">*</span></label>
                                    <input type="text" name="first_name" id="coachFieldFirstName" class="coach-form-control" required placeholder="z.B. Marc">
                                </div>

                                <div class="mb-3">
                                    <label class="coach-field-label">Nachname <span class="text-danger">*</span></label>
                                    <input type="text" name="last_name" id="coachFieldLastName" class="coach-form-control" required placeholder="z.B. Meier">
                                </div>

                                <div class="mb-3">
                                    <label class="coach-field-label">Titel / Funktion</label>
                                    <input type="text" name="title" id="coachFieldTitle" class="coach-form-control" placeholder="Fahrcoach">
                                </div>

                                <div>
                                    <label class="coach-field-label">Bild URL / Profilfoto</label>
                                    <input type="url" name="image_url" id="coachFieldImageUrl" class="coach-form-control mb-1" placeholder="https://beispiel.ch/foto.jpg">
                                    <div class="small text-muted mb-1" style="font-size: 0.75rem;">Oder Datei hochladen:</div>
                                    <input type="file" name="image" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp" style="border-radius: 10px; background: #f8fafc; font-size: 0.8rem;">
                                </div>
                            </div>

                            <!-- Rechte Spalte -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="coach-field-label">Öffentliche Mobilnummer</label>
                                    <div class="coach-card-box">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="form-check m-0">
                                                <input class="form-check-input" type="checkbox" name="is_mobile_public" id="coachFieldIsMobilePublic" value="1">
                                                <label class="form-check-label fw-semibold text-dark small" for="coachFieldIsMobilePublic" style="cursor: pointer; white-space: nowrap;">Freischalten</label>
                                            </div>
                                            <input type="text" name="public_mobile" id="coachFieldPublicMobile" class="form-control form-control-sm border-0 bg-white shadow-none" placeholder="z.B. +41 79 ..." style="border-radius: 10px; padding: 7px 10px; font-size: 0.88rem;">
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="coach-field-label">Beschreibung / Bio</label>
                                    <textarea name="qualifications" id="coachFieldQualifications" rows="4" class="coach-form-control" placeholder="Biografie, Bootsqualifikationen, Erfahrung..."></textarea>
                                </div>

                                <div class="coach-card-box d-flex align-items-center justify-content-between p-2 px-3">
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input" type="checkbox" name="is_active" id="coachFieldIsActive" value="1" checked>
                                        <label class="form-check-label small fw-semibold text-dark ms-1" for="coachFieldIsActive">Coach ist aktiv</label>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="coach-field-label mb-0" style="font-size: 0.68rem;">Farbe:</span>
                                        <input type="color" name="color_code" id="coachFieldColorCode" class="form-control form-control-color border-0 p-0" value="#0d6efd" style="width: 28px; height: 28px; border-radius: 8px; cursor: pointer;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ===================================================== -->
                    <!-- TAB 2: INTERN / PII                                   -->
                    <!-- ===================================================== -->
                    <div id="coachPaneInternal" class="coach-tab-pane" style="display: none;">
                        <div class="row g-4">
                            <!-- Linke Spalte -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="coach-field-label"><i class="bi bi-envelope me-1"></i> E-Mail Adresse <span class="text-danger">*</span></label>
                                    <input type="email" name="email" id="coachFieldEmail" class="coach-form-control" required placeholder="test@coach.ch">
                                </div>

                                <div class="mb-3">
                                    <label class="coach-field-label"><i class="bi bi-telephone me-1"></i> Telefonnummer</label>
                                    <input type="text" name="phone" id="coachFieldPhone" class="coach-form-control" placeholder="+41 79 123 45 67">
                                </div>

                                <div class="mb-3">
                                    <label class="coach-field-label"><i class="bi bi-calendar-event me-1"></i> Geburtsdatum</label>
                                    <input type="date" name="birth_date" id="coachFieldBirthDate" class="coach-form-control">
                                </div>

                                <div class="mb-3">
                                    <label class="coach-field-label"><i class="bi bi-person-lock me-1"></i> CMS-Benutzer</label>
                                    <select name="user_id" id="coachFieldUserId" class="coach-form-control">
                                        <option value="">-- Kein Benutzer --</option>
                                        <?php foreach ($users as $u): ?>
                                            <option value="<?= (int) $u['id'] ?>">
                                                <?= htmlspecialchars((string) ($u['name'] ?: $u['email']), ENT_QUOTES, 'UTF-8') ?> (ID: <?= (int) $u['id'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <label class="coach-field-label">Notizen / Bemerkungen (Intern)</label>
                                    <textarea name="notes" id="coachFieldNotes" rows="2" class="coach-form-control" placeholder="Interne Bemerkungen..."></textarea>
                                </div>
                            </div>

                            <!-- Rechte Spalte -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="coach-field-label"><i class="bi bi-geo-alt me-1"></i> Adresse</label>
                                    <input type="text" name="address" id="coachFieldAddress" class="coach-form-control" placeholder="Musterstrasse 12, 8000 Zürich">
                                </div>

                                <div class="mb-3">
                                    <label class="coach-field-label"><i class="bi bi-credit-card me-1"></i> AHV Nummer</label>
                                    <input type="text" name="ahv_number" id="coachFieldAhvNumber" class="coach-form-control font-monospace" placeholder="756.XXXX.XXXX.XX">
                                </div>

                                <div class="mb-3">
                                    <label class="coach-field-label"><i class="bi bi-bank me-1"></i> Bankverbindung (IBAN)</label>
                                    <input type="text" name="bank_iban" id="coachFieldBankIban" class="coach-form-control font-monospace" placeholder="CHxx xxxx xxxx xxxx x">
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="coach-field-label">Bankname</label>
                                        <input type="text" name="bank_name" id="coachFieldBankName" class="coach-form-control" placeholder="z.B. ZKB">
                                    </div>
                                    <div class="col-6">
                                        <label class="coach-field-label">Ort (Bank)</label>
                                        <input type="text" name="bank_city" id="coachFieldBankCity" class="coach-form-control" placeholder="z.B. Zürich">
                                    </div>
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="coach-field-label">Eintrittsdatum</label>
                                        <input type="date" name="entry_date" id="coachFieldEntryDate" class="coach-form-control">
                                    </div>
                                    <div class="col-6">
                                        <label class="coach-field-label">Stundenlohn (CHF)</label>
                                        <input type="number" step="0.05" min="0" name="pay_rate_amount" id="coachFieldPayRateAmount" class="coach-form-control" placeholder="0.00">
                                    </div>
                                </div>

                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="coach-field-label">Lohnmodell</label>
                                        <select name="pay_rate_type" id="coachFieldPayRateType" class="coach-form-control">
                                            <option value="per_hour">Pro Stunde</option>
                                            <option value="per_appointment">Pauschale/Termin</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="coach-field-label">Lohn-Code</label>
                                        <input type="text" name="payroll_external_code" id="coachFieldPayrollExternalCode" class="coach-form-control" placeholder="z.B. PERS-101">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Buttons -->
                <div class="p-4 pt-2 border-0 bg-white">
                    <div class="d-flex align-items-center justify-content-center gap-3">
                        <button type="button" class="btn btn-coach-cancel px-4 py-2" data-bs-dismiss="modal" style="min-width: 140px;">
                            Abbrechen
                        </button>
                        <button type="submit" id="coachSubmitBtn" class="btn btn-coach-brown px-5 py-2 shadow-sm" style="min-width: 180px;">
                            Coach speichern
                        </button>
                    </div>

                    <!-- Löschen Option bei bestehenden Coaches -->
                    <div class="text-center mt-3" id="coachDeleteContainer" style="display: none;">
                        <button type="button" class="btn btn-link text-danger text-decoration-none fw-semibold small p-0 d-inline-flex align-items-center gap-1" id="coachDeleteTrigger">
                            <i class="bi bi-trash"></i> Coach löschen
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Separates Lösch-Formular für den Modal-Löschbutton -->
<form method="post" action="?route=admin/boating/instructors/delete" id="coachModalDeleteForm" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="id" id="coachModalDeleteId" value="">
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('coachModal');
    if (!modalEl) return;
    const bsModal = new bootstrap.Modal(modalEl);

    const tabBtnPublic = document.getElementById('tabBtnPublic');
    const tabBtnInternal = document.getElementById('tabBtnInternal');
    const panePublic = document.getElementById('coachPanePublic');
    const paneInternal = document.getElementById('coachPaneInternal');

    const form = document.getElementById('coachForm');
    const modalTitle = document.getElementById('coachModalTitle');
    const submitBtn = document.getElementById('coachSubmitBtn');
    const deleteContainer = document.getElementById('coachDeleteContainer');
    const deleteIdInput = document.getElementById('coachModalDeleteId');

    // Tab Umschaltung
    function switchTab(tab) {
        if (tab === 'public') {
            tabBtnPublic.classList.add('active');
            tabBtnInternal.classList.remove('active');
            panePublic.style.display = 'block';
            paneInternal.style.display = 'none';
        } else {
            tabBtnInternal.classList.add('active');
            tabBtnPublic.classList.remove('active');
            paneInternal.style.display = 'block';
            panePublic.style.display = 'none';
        }
    }

    tabBtnPublic.addEventListener('click', () => switchTab('public'));
    tabBtnInternal.addEventListener('click', () => switchTab('internal'));

    // Modal für neuen Coach öffnen
    window.openCreateCoachModal = function () {
        form.reset();
        form.action = '?route=admin/boating/instructors/create';
        document.getElementById('coachFieldId').value = '';
        modalTitle.textContent = 'Neuen Coach anlegen';
        submitBtn.textContent = 'Coach speichern';
        deleteContainer.style.display = 'none';
        document.getElementById('coachFieldColorCode').value = '#0d6efd';
        document.getElementById('coachFieldIsActive').checked = true;
        document.getElementById('coachFieldTitle').value = 'Fahrcoach';
        switchTab('public');
        bsModal.show();
    };

    const btnOpenCreate = document.getElementById('btnOpenCreateCoach');
    if (btnOpenCreate) {
        btnOpenCreate.addEventListener('click', window.openCreateCoachModal);
    }

    // Modal für Bearbeiten öffnen
    document.querySelectorAll('.btn-edit-coach').forEach(function (btn) {
        btn.addEventListener('click', function () {
            form.reset();
            form.action = '?route=admin/boating/instructors/update';
            modalTitle.textContent = 'Coach bearbeiten';
            submitBtn.textContent = 'Coach speichern';
            deleteContainer.style.display = 'block';

            const id = btn.getAttribute('data-id') || '';
            document.getElementById('coachFieldId').value = id;
            deleteIdInput.value = id;

            document.getElementById('coachFieldFirstName').value = btn.getAttribute('data-first_name') || '';
            document.getElementById('coachFieldLastName').value = btn.getAttribute('data-last_name') || '';
            document.getElementById('coachFieldTitle').value = btn.getAttribute('data-title') || 'Fahrcoach';
            document.getElementById('coachFieldImageUrl').value = btn.getAttribute('data-image_url') || '';
            document.getElementById('coachFieldPublicMobile').value = btn.getAttribute('data-public_mobile') || '';
            document.getElementById('coachFieldIsMobilePublic').checked = (btn.getAttribute('data-is_mobile_public') === '1');
            document.getElementById('coachFieldQualifications').value = btn.getAttribute('data-qualifications') || '';
            document.getElementById('coachFieldColorCode').value = btn.getAttribute('data-color_code') || '#0d6efd';
            document.getElementById('coachFieldIsActive').checked = (btn.getAttribute('data-is_active') === '1');

            document.getElementById('coachFieldEmail').value = btn.getAttribute('data-email') || '';
            document.getElementById('coachFieldPhone').value = btn.getAttribute('data-phone') || '';
            document.getElementById('coachFieldBirthDate').value = btn.getAttribute('data-birth_date') || '';
            document.getElementById('coachFieldUserId').value = btn.getAttribute('data-user_id') || '';
            document.getElementById('coachFieldNotes').value = btn.getAttribute('data-notes') || '';

            document.getElementById('coachFieldAddress').value = btn.getAttribute('data-address') || '';
            document.getElementById('coachFieldAhvNumber').value = btn.getAttribute('data-ahv_number') || '';
            document.getElementById('coachFieldBankIban').value = btn.getAttribute('data-bank_iban') || '';
            document.getElementById('coachFieldBankName').value = btn.getAttribute('data-bank_name') || '';
            document.getElementById('coachFieldBankCity').value = btn.getAttribute('data-bank_city') || '';
            document.getElementById('coachFieldEntryDate').value = btn.getAttribute('data-entry_date') || '';
            document.getElementById('coachFieldPayRateAmount').value = btn.getAttribute('data-pay_rate_amount') || '0.00';
            document.getElementById('coachFieldPayRateType').value = btn.getAttribute('data-pay_rate_type') || 'per_hour';
            document.getElementById('coachFieldPayrollExternalCode').value = btn.getAttribute('data-payroll_external_code') || '';

            switchTab('public');
            bsModal.show();
        });
    });

    // Löschbutton im Modal
    const deleteTrigger = document.getElementById('coachDeleteTrigger');
    if (deleteTrigger) {
        deleteTrigger.addEventListener('click', function () {
            if (confirm('Möchten Sie diesen Coach wirklich unwiderruflich löschen?')) {
                document.getElementById('coachModalDeleteForm').submit();
            }
        });
    }
});
</script>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../../../views/layouts/admin.php';
