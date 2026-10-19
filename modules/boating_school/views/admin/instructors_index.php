<?php

declare(strict_types=1);

/**
 * Bootsfahrschule - Fahrlehrer Übersicht
 *
 * @var array<int, array<string, mixed>> $instructors
 * @var int $totalInstructors
 * @var array<string, mixed> $filters
 * @var bool $tablesCreated
 */

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$title = 'Fahrlehrer - Bootsfahrschule';
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
                <li class="breadcrumb-item active" aria-current="page">Fahrlehrer</li>
            </ol>
        </nav>
        <h1 class="h3 fw-bold text-dark mb-0">
            <i class="bi bi-person-badge text-primary me-2"></i>Fahrlehrer &amp; Instruktoren (<?= $totalInstructors ?>)
        </h1>
    </div>
    <div class="d-flex gap-2">
        <a href="?route=admin/boating" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Dashboard
        </a>
        <?php if ($canManage): ?>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createInstructorModal">
                <i class="bi bi-plus-lg me-1"></i> Fahrlehrer hinzufügen
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Filter-Leiste -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="get" class="row g-2 align-items-center">
            <input type="hidden" name="route" value="admin/boating/instructors">
            <div class="col-md-7">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Suchbegriff (Name, E-Mail, Lizenz, Qualifikationen)..." value="<?= htmlspecialchars((string) ($filters['search'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
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
                <button type="submit" class="btn btn-sm btn-secondary flex-grow-1">Filtern</button>
                <?php if (!empty($filters['search']) || (isset($filters['is_active']) && $filters['is_active'] !== '')): ?>
                    <a href="?route=admin/boating/instructors" class="btn btn-sm btn-outline-secondary" title="Zurücksetzen">
                        <i class="bi bi-x-lg"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Fahrlehrer-Tabelle -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($instructors)): ?>
            <div class="text-center py-5">
                <i class="bi bi-person-badge text-muted fs-1 d-block mb-2"></i>
                <h6 class="text-muted mb-2">Keine Fahrlehrer gefunden</h6>
                <p class="text-muted small mb-3">Erfassen Sie Instruktoren für Theorie- und Praxisfahrstunden.</p>
                <?php if ($canManage): ?>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createInstructorModal">
                        <i class="bi bi-plus-lg me-1"></i> Neuen Fahrlehrer anlegen
                    </button>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th style="width: 50px;">Foto</th>
                            <th>Fahrlehrer</th>
                            <th>Farbe</th>
                            <th>Lizenz / Ausweis</th>
                            <th>Honorar / Satz</th>
                            <th>Kontakt</th>
                            <th>Status</th>
                            <th class="text-end" style="width: 140px;">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($instructors as $inst): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($inst['image_path'])): ?>
                                        <img src="<?= htmlspecialchars((string) $inst['image_path'], ENT_QUOTES, 'UTF-8') ?>" alt="Foto" class="rounded-circle object-fit-cover" style="width: 38px; height: 38px;">
                                    <?php else: ?>
                                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold small" style="width: 38px; height: 38px; background-color: <?= htmlspecialchars((string) ($inst['color_code'] ?? '#0d6efd'), ENT_QUOTES, 'UTF-8') ?>;">
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
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="rounded d-inline-block border" style="width: 20px; height: 20px; background-color: <?= htmlspecialchars((string) ($inst['color_code'] ?? '#0d6efd'), ENT_QUOTES, 'UTF-8') ?>;"></span>
                                        <span class="font-monospace small text-muted"><?= htmlspecialchars((string) ($inst['color_code'] ?? '#0d6efd'), ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($inst['license_number'])): ?>
                                        <span class="font-monospace small text-dark fw-semibold"><?= htmlspecialchars((string) $inst['license_number'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark small">
                                        CHF <?= number_format((float) ($inst['pay_rate_amount'] ?? 0.0), 2) ?>
                                        <span class="badge bg-light text-muted border fw-normal">
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
                                    <?php if (!empty($inst['phone'])): ?>
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
                                            <input type="hidden" name="id" value="<?= (int) $inst['id'] ?>">
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
                                        <a href="?route=admin/boating/instructors/edit&id=<?= (int) $inst['id'] ?>" class="btn btn-outline-secondary" title="Bearbeiten">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($canManage): ?>
                                            <form method="post" action="?route=admin/boating/instructors/delete" class="d-inline" onsubmit="return confirm('Möchten Sie diesen Fahrlehrer wirklich löschen?');">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="id" value="<?= (int) $inst['id'] ?>">
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

<!-- Modal: Fahrlehrer erstellen -->
<?php if ($canManage): ?>
<div class="modal fade" id="createInstructorModal" tabindex="-1" aria-labelledby="createInstructorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" action="?route=admin/boating/instructors/create" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="createInstructorModalLabel">
                        <i class="bi bi-person-badge text-primary me-2"></i>Neuen Fahrlehrer anlegen
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>
                
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Vorname <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control" required placeholder="z. B. Marc">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nachname <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control" required placeholder="z. B. Meier">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">E-Mail</label>
                            <input type="email" name="email" class="form-control" placeholder="marc.meier@bootsfahrschule.ch">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Telefon / Mobil</label>
                            <input type="text" name="phone" class="form-control" placeholder="+41 79 123 45 67">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Lizenz- / Ausweisnummer</label>
                            <input type="text" name="license_number" class="form-control" placeholder="z. B. FL-KAT-A-9812">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Kalender-Farbe</label>
                            <input type="color" name="color_code" class="form-control form-control-color w-100" value="#0d6efd" title="Farbcode wählen">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">CMS-Benutzer</label>
                            <select name="user_id" class="form-select">
                                <option value="">-- Kein Benutzer (optional) --</option>
                                <?php foreach ($users as $u): ?>
                                    <option value="<?= (int) $u['id'] ?>">
                                        <?= htmlspecialchars((string) ($u['name'] ?: $u['email']), ENT_QUOTES, 'UTF-8') ?> (ID: <?= (int) $u['id'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Payroll / Honorar -->
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Lohnmodell</label>
                            <select name="pay_rate_type" class="form-select">
                                <option value="per_hour" selected>Pro Stunde (CHF / h)</option>
                                <option value="per_appointment">Pauschale pro Termin</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Ansatz / Honorar (CHF)</label>
                            <input type="number" step="0.05" min="0" name="pay_rate_amount" class="form-control" value="0.00" placeholder="0.00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Lohn-Code / Externe Nr.</label>
                            <input type="text" name="payroll_external_code" class="form-control" placeholder="z. B. PERS-101">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Qualifikationen / Scheine (Mehrzeilenfeld)</label>
                            <textarea name="qualifications" rows="2" class="form-control" placeholder="z. B. Kat. A Motorboot, Kat. D Segel, Hochsee, Funkzeugnis SRC"></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Profilfoto (optional, JPG/PNG/WebP, max. 4MB)</label>
                            <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Notizen / Bemerkungen</label>
                            <textarea name="notes" rows="3" class="form-control" placeholder="Interne Notizen zum Fahrlehrer..."></textarea>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="instActiveSwitch" value="1" checked>
                                <label class="form-check-label small fw-semibold" for="instActiveSwitch">Fahrlehrer ist aktiv</label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-sm btn-primary fw-semibold px-3">Fahrlehrer speichern</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require dirname(__DIR__, 3) . '/views/layouts/admin.php';
