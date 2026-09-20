<?php
declare(strict_types=1);

/** @var array<string, mixed> $editUser */
/** @var array<int, array<string, mixed>> $roles */
$roles = $roles ?? (class_exists('Rbac') ? Rbac::getAllRoles() : []);

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$userId = (int) ($editUser['id'] ?? 0);
$userName = (string) ($editUser['name'] ?? 'Unbekannt');
$userEmail = (string) ($editUser['email'] ?? '');
$userRole = (string) ($editUser['role'] ?? 'admin');
$userTheme = (string) ($editUser['theme_mode'] ?? 'system');
if (!in_array($userTheme, ['light', 'dark', 'system'], true)) {
    $userTheme = 'system';
}
$isActive = (int) ($editUser['is_active'] ?? 1) === 1;

$title = 'Benutzer bearbeiten';
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
        <h4 class="mb-1 fw-bold text-dark">Benutzerkonto bearbeiten: #<?= $userId ?> <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></h4>
        <p class="text-muted mb-0 small">Aktualisieren Sie Stammdaten, Zugangsrolle, Theme-Präferenz und Passwort.</p>
    </div>
    <div>
        <a href="?route=admin/users" class="btn btn-outline-secondary d-inline-flex align-items-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left me-2" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8"/>
            </svg>
            Zurück zur Benutzerliste
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 fw-semibold text-dark">Benutzerdaten</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="?route=admin/users/edit">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(class_exists('Csrf') ? Csrf::getToken() : '', ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="id" value="<?= $userId ?>">

                    <div class="mb-3">
                        <label for="userName" class="form-label fw-semibold">Vollständiger Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="userName" name="name" required 
                               value="<?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>" maxlength="191">
                    </div>

                    <div class="mb-3">
                        <label for="userEmail" class="form-label fw-semibold">E-Mail-Adresse <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="userEmail" name="email" required 
                               value="<?= htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8') ?>" maxlength="191">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="userRole" class="form-label fw-semibold">Zugewiesene Rolle</label>
                            <select class="form-select" id="userRole" name="role">
                                <?php if (!empty($roles)): ?>
                                    <?php foreach ($roles as $r): 
                                        $rKey = (string) ($r['key'] ?? '');
                                        $rName = (string) ($r['name'] ?? $rKey);
                                    ?>
                                        <option value="<?= htmlspecialchars($rKey, ENT_QUOTES, 'UTF-8') ?>" <?= $rKey === $userRole ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($rName, ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="admin" <?= $userRole === 'admin' ? 'selected' : '' ?>>Administrator</option>
                                    <option value="editor" <?= $userRole === 'editor' ? 'selected' : '' ?>>Redakteur</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <!-- 1. Pro-User-Theme Dropdown -->
                        <div class="col-md-6">
                            <label for="themeMode" class="form-label fw-semibold">Admin-Theme / Darstellung</label>
                            <select class="form-select" id="themeMode" name="theme_mode">
                                <option value="system" <?= $userTheme === 'system' ? 'selected' : '' ?>>
                                    System (Standard)
                                </option>
                                <option value="light" <?= $userTheme === 'light' ? 'selected' : '' ?>>
                                    Hell
                                </option>
                                <option value="dark" <?= $userTheme === 'dark' ? 'selected' : '' ?>>
                                    Dunkel
                                </option>
                            </select>
                            <div class="form-text small">Individuelles Farbschema für das Admin-Dashboard dieses Benutzers.</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="userActive" name="is_active" value="1" <?= $isActive ? 'checked' : '' ?>>
                            <label class="form-check-label fw-medium" for="userActive">
                                Benutzerkonto ist aktiv (Login erlaubt)
                            </label>
                        </div>
                    </div>

                    <hr class="my-4">

                    <h5 class="fw-semibold text-dark mb-3">Passwort ändern</h5>
                    <div class="mb-3">
                        <label for="userPassword" class="form-label fw-semibold">Neues Passwort</label>
                        <input type="password" class="form-control" id="userPassword" name="password" minlength="6" 
                               placeholder="Nur ausfüllen, wenn das Passwort neu gesetzt werden soll">
                        <div class="form-text">Leer lassen, falls das bisherige Passwort unverändert bleiben soll.</div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top mt-4">
                        <a href="?route=admin/users" class="btn btn-outline-secondary">Abbrechen</a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold">
                            Änderungen speichern
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Metadaten / Info Sidebar -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-semibold text-dark">Konto-Informationen</h6>
            </div>
            <div class="card-body p-3">
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Benutzer-ID:</span>
                        <span class="fw-bold">#<?= $userId ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Aktueller Status:</span>
                        <span>
                            <?php if ($isActive): ?>
                                <span class="badge bg-success">Aktiv</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inaktiv</span>
                            <?php endif; ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Theme-Einstellung:</span>
                        <span class="badge bg-info text-dark text-uppercase"><?= htmlspecialchars($userTheme, ENT_QUOTES, 'UTF-8') ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Letzter Login:</span>
                        <span><?= !empty($editUser['last_login_at']) ? htmlspecialchars((string) $editUser['last_login_at'], ENT_QUOTES, 'UTF-8') : 'Nie' ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Erstellt am:</span>
                        <span><?= !empty($editUser['created_at']) ? htmlspecialchars((string) $editUser['created_at'], ENT_QUOTES, 'UTF-8') : '—' ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Zuletzt geändert:</span>
                        <span><?= !empty($editUser['updated_at']) ? htmlspecialchars((string) $editUser['updated_at'], ENT_QUOTES, 'UTF-8') : '—' ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/admin.php';
