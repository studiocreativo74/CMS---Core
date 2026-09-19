<?php
declare(strict_types=1);

/** @var array<int, array<string, mixed>> $roles */
$roles = $roles ?? (class_exists('Rbac') ? Rbac::getAllRoles() : []);
/** @var array<int, array<string, mixed>> $permissions */
$permissions = $permissions ?? (class_exists('Rbac') ? Rbac::getAllPermissions() : []);

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$title = 'Rollen & Rechte';
$currentRoute = 'admin/roles';

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

<div class="mb-4">
    <p class="text-muted mb-0">Definition der Benutzerrollen und Zuweisung granularer Berechtigungen (RBAC-Modell).</p>
</div>

<div class="row g-4">
    <!-- Übersicht Rollen -->
    <div class="col-lg-8">
        <?php if (empty($roles)): ?>
            <div class="card border-0 shadow-sm p-4 text-center">
                <p class="text-muted mb-0">Aktuell sind keine Rollen in der Datenbank hinterlegt.</p>
            </div>
        <?php else: ?>
            <div class="accordion shadow-sm" id="rolesAccordion">
                <?php foreach ($roles as $idx => $role): 
                    $roleId = (int) ($role['id'] ?? 0);
                    $roleKey = (string) ($role['key'] ?? '');
                    $roleName = (string) ($role['name'] ?? $roleKey);
                    $roleDesc = (string) ($role['description'] ?? '');
                    $isSuperadmin = ($roleKey === 'superadmin');

                    $rolePerms = class_exists('Rbac') ? Rbac::getRolePermissions($roleId) : [];
                    $assignedPermIds = array_column($rolePerms, 'id');
                ?>
                    <div class="accordion-item border-0 mb-3 rounded overflow-hidden">
                        <h2 class="accordion-header" id="headingRole<?= $roleId ?>">
                            <button class="accordion-button <?= $idx > 0 ? 'collapsed' : '' ?> bg-white py-3" type="button" 
                                    data-bs-toggle="collapse" data-bs-target="#collapseRole<?= $roleId ?>" 
                                    aria-expanded="<?= $idx === 0 ? 'true' : 'false' ?>" aria-controls="collapseRole<?= $roleId ?>">
                                <div class="d-flex align-items-center justify-content-between w-100 me-3">
                                    <div>
                                        <span class="fw-bold text-dark fs-6"><?= htmlspecialchars($roleName, ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="badge bg-secondary ms-2 text-uppercase" style="font-size: 0.72rem;"><?= htmlspecialchars($roleKey, ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <div>
                                        <?php if ($isSuperadmin): ?>
                                            <span class="badge bg-danger">Vollzugriff (Superadmin)</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary"><?= count($assignedPermIds) ?> Berechtigungen</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </button>
                        </h2>
                        <div id="collapseRole<?= $roleId ?>" class="accordion-collapse collapse <?= $idx === 0 ? 'show' : '' ?>" 
                             aria-labelledby="headingRole<?= $roleId ?>" data-bs-parent="#rolesAccordion">
                            <div class="accordion-body bg-light border-top p-4">
                                <?php if ($roleDesc !== ''): ?>
                                    <p class="text-muted small mb-3"><?= htmlspecialchars($roleDesc, ENT_QUOTES, 'UTF-8') ?></p>
                                <?php endif; ?>

                                <?php if ($isSuperadmin): ?>
                                    <div class="alert alert-info mb-0 py-2 small">
                                        <strong>Hinweis:</strong> Die Rolle <code>superadmin</code> besitzt automatisch alle Berechtigungen im gesamten System.
                                    </div>
                                <?php else: ?>
                                    <form method="POST" action="?route=admin/roles/permissions">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(class_exists('Csrf') ? Csrf::getToken() : '', ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="role_id" value="<?= $roleId ?>">

                                        <h6 class="fw-semibold text-dark mb-3">Berechtigungen für diese Rolle:</h6>

                                        <div class="row g-2 mb-3">
                                            <?php foreach ($permissions as $perm): 
                                                $pId = (int) ($perm['id'] ?? 0);
                                                $pKey = (string) ($perm['key'] ?? '');
                                                $pName = (string) ($perm['name'] ?? $pKey);
                                                $isChecked = in_array($pId, $assignedPermIds, true);
                                            ?>
                                                <div class="col-md-6">
                                                    <div class="form-check p-2 bg-white rounded border">
                                                        <input class="form-check-input ms-0 me-2" type="checkbox" 
                                                               name="permissions[]" value="<?= $pId ?>" 
                                                               id="role<?= $roleId ?>_perm<?= $pId ?>" 
                                                               <?= $isChecked ? 'checked' : '' ?>>
                                                        <label class="form-check-label small fw-medium" for="role<?= $roleId ?>_perm<?= $pId ?>">
                                                            <?= htmlspecialchars($pName, ENT_QUOTES, 'UTF-8') ?>
                                                            <span class="d-block text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($pKey, ENT_QUOTES, 'UTF-8') ?></span>
                                                        </label>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>

                                        <button type="submit" class="btn btn-sm btn-primary">
                                            Berechtigungen speichern
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Info-Box zu Berechtigungen -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-semibold text-dark">Definierte Berechtigungen (<?= count($permissions) ?>)</h6>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush small">
                    <?php foreach ($permissions as $p): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <div>
                                <span class="fw-semibold text-dark"><?= htmlspecialchars((string) ($p['name'] ?? $p['key']), ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="d-block text-muted" style="font-size: 0.72rem;"><?= htmlspecialchars((string) ($p['key'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/admin.php';
