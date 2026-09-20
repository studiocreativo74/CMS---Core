<?php
declare(strict_types=1);

/** @var array<int, array<string, mixed>> $users */
$users = $users ?? (class_exists('User') ? User::all() : []);
/** @var array<int, array<string, mixed>> $roles */
$roles = $roles ?? (class_exists('Rbac') ? Rbac::getAllRoles() : []);

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$title = 'Benutzer';
$currentRoute = 'admin/users';

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

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <p class="text-muted mb-0">Verwaltung der Benutzerkonten, Rollen und Zugangsrechte.</p>
    </div>
    <div>
        <button type="button" class="btn btn-primary d-inline-flex align-items-center" data-bs-toggle="modal" data-bs-target="#createUserModal">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-plus-fill me-2" viewBox="0 0 16 16">
                <path d="M1 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6"/>
                <path fill-rule="evenodd" d="M13.5 5a.5.5 0 0 1 .5.5V7h1.5a.5.5 0 0 1 0 1H14v1.5a.5.5 0 0 1-1 0V8h-1.5a.5.5 0 0 1 0-1H13V5.5a.5.5 0 0 1 .5-.5"/>
            </svg>
            Neuer Benutzer
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h5 class="card-title mb-0 fw-semibold text-dark">Registrierte Benutzer (<?= count($users) ?>)</h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($users)): ?>
            <div class="text-center py-5">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" class="bi bi-people text-muted mb-3" viewBox="0 0 16 16">
                    <path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1zm-7.978-1L7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.022.004a6 6 0 0 0-.978.995"/>
                    <path d="M7 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.24 2.24 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.3 6.3 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5"/>
                </svg>
                <h6 class="text-muted fw-normal">Bisher sind noch keine separaten Benutzer angelegt.</h6>
                <p class="text-muted small">Aktuell erfolgt der Zugang vorrangig über Magic Codes oder den Superadmin-Zugang.</p>
                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createUserModal">
                    Jetzt ersten Benutzer anlegen
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width: 60px;">ID</th>
                            <th>Name</th>
                            <th>E-Mail</th>
                            <th>Rolle</th>
                            <th>Theme</th>
                            <th>Status</th>
                            <th>Letzter Login</th>
                            <th>Erstellt</th>
                            <th class="text-end pe-3" style="width: 170px;">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $userItem): 
                            $isActive = (int) ($userItem['is_active'] ?? 1) === 1;
                            $roleKey = (string) ($userItem['role'] ?? 'admin');
                            $userTheme = (string) ($userItem['theme_mode'] ?? 'system');
                        ?>
                            <tr>
                                <td class="ps-3 fw-semibold text-muted">#<?= (int) $userItem['id'] ?></td>
                                <td class="fw-semibold text-dark">
                                    <?= htmlspecialchars((string) ($userItem['name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td>
                                    <span class="text-secondary"><?= htmlspecialchars((string) ($userItem['email'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary text-uppercase" style="font-size: 0.75rem;">
                                        <?= htmlspecialchars($roleKey, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($userTheme === 'light'): ?>
                                        <span class="badge bg-light text-dark border">Hell</span>
                                    <?php elseif ($userTheme === 'dark'): ?>
                                        <span class="badge bg-dark text-white">Dunkel</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary border">System</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($isActive): ?>
                                        <span class="badge bg-success">Aktiv</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inaktiv</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted">
                                    <?= !empty($userItem['last_login_at']) ? htmlspecialchars((string) $userItem['last_login_at'], ENT_QUOTES, 'UTF-8') : 'Nie' ?>
                                </td>
                                <td class="small text-muted">
                                    <?= !empty($userItem['created_at']) ? htmlspecialchars((string) $userItem['created_at'], ENT_QUOTES, 'UTF-8') : '—' ?>
                                </td>
                                <td class="text-end pe-3 text-nowrap">
                                    <a href="?route=admin/users/edit&id=<?= (int) $userItem['id'] ?>" class="btn btn-sm btn-outline-primary me-1" title="Benutzer bearbeiten">
                                        Bearbeiten
                                    </a>
                                    <form method="POST" action="?route=admin/users/toggle" class="d-inline" onsubmit="return confirm('Möchten Sie den Status dieses Benutzers wirklich ändern?');">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(class_exists('Csrf') ? Csrf::getToken() : '', ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="id" value="<?= (int) $userItem['id'] ?>">
                                        <input type="hidden" name="active" value="<?= $isActive ? '0' : '1' ?>">
                                        <?php if ($isActive): ?>
                                            <button type="submit" class="btn btn-sm btn-outline-warning" title="Benutzer deaktivieren">
                                                Deaktivieren
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Benutzer aktivieren">
                                                Aktivieren
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: Neuer Benutzer -->
<div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="?route=admin/users/create" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createUserModalLabel">Neuen Benutzer anlegen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(class_exists('Csrf') ? Csrf::getToken() : '', ENT_QUOTES, 'UTF-8') ?>">
                
                <div class="mb-3">
                    <label for="userName" class="form-label">Vollständiger Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="userName" name="name" required placeholder="z. B. Roland Muster">
                </div>

                <div class="mb-3">
                    <label for="userEmail" class="form-label">E-Mail-Adresse <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="userEmail" name="email" required placeholder="name@example.ch">
                </div>

                <div class="mb-3">
                    <label for="userPassword" class="form-label">Passwort <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="userPassword" name="password" required minlength="6" placeholder="Mindestens 6 Zeichen">
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label for="userRole" class="form-label">Rolle</label>
                        <select class="form-select" id="userRole" name="role">
                            <?php if (!empty($roles)): ?>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= htmlspecialchars((string) ($r['key'] ?? 'admin'), ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars((string) ($r['name'] ?? $r['key']), ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="admin">Administrator</option>
                                <option value="editor">Redakteur</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="createThemeMode" class="form-label">Admin-Theme</label>
                        <select class="form-select" id="createThemeMode" name="theme_mode">
                            <option value="system" selected>System (Standard)</option>
                            <option value="light">Hell</option>
                            <option value="dark">Dunkel</option>
                        </select>
                    </div>
                </div>

                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="userActive" name="is_active" value="1" checked>
                    <label class="form-check-label" for="userActive">
                        Benutzerkonto sofort aktivieren
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <button type="submit" class="btn btn-primary">Benutzer erstellen</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/admin.php';
