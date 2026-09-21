<?php
declare(strict_types=1);

$user = $user ?? Auth::user();
$magicCodes = $magicCodes ?? (class_exists('MagicCode') ? MagicCode::getAll() : []);
$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashCreatedCode = $_SESSION['flash_created_code'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;

// Flash-Nachrichten nach dem Auslesen leeren
unset($_SESSION['flash_success'], $_SESSION['flash_created_code'], $_SESSION['flash_error']);

// Titel für das Admin-Layout festlegen
$title = 'Dashboard';

// Aktive E-Mail ermitteln
$activeEmail = $_SESSION['magic_email'] 
    ?? ($_SESSION['user_email'] 
    ?? (isset($user['email']) ? (string) $user['email'] : 'Administrator'));

// =========================================================================
// RBAC Berechtigungsprüfung für Dashboard-Widgets:
// - Wenn $_SESSION['magic_authenticated'] === true ist (Superadmin via Magic-Code),
//   sind immer ausnahmslos alle Widgets sichtbar.
// - Ansonsten greift Rbac::can() auf die feingranularen Berechtigungen.
// =========================================================================
$isMagicAdmin = !empty($_SESSION['magic_authenticated']);

$can = static function (array|string $permissions) use ($isMagicAdmin): bool {
    if ($isMagicAdmin) {
        return true;
    }
    if (class_exists('Rbac') && method_exists('Rbac', 'can')) {
        foreach ((array) $permissions as $perm) {
            if (Rbac::can((string) $perm)) {
                return true;
            }
        }
        return false;
    }
    return true;
};

// Berechtigungen für einzelne Dashboard-Widgets auswerten
$canViewMagicCodes   = $can(['admin.magic_codes.view', 'admin.magic_codes']);
$canManageMagicCodes = $can(['admin.magic_codes.manage', 'admin.magic_codes']);
$canViewUsers        = $can(['admin.users.view', 'admin.users.manage', 'admin.users']);
$canViewHomepage     = $can(['admin.homepage.view', 'admin.homepage.manage', 'admin.homepage', 'admin.settings']);
$canViewModules      = $can(['admin.modules.view', 'admin.modules.manage', 'admin.modules']);
$canViewRoles        = $can(['admin.roles.view', 'admin.roles.manage', 'admin.rbac.manage', 'admin.rbac']);

// Kennzahlen berechnen
$totalCount = count($magicCodes);
$activeCount = 0;
$usedCount = 0;
$expiredCount = 0;

if ($canViewMagicCodes && class_exists('MagicCode')) {
    foreach ($magicCodes as $item) {
        $st = MagicCode::getStatus($item);
        if ($st['key'] === 'active') {
            $activeCount++;
        } elseif ($st['key'] === 'used') {
            $usedCount++;
        } elseif ($st['key'] === 'expired') {
            $expiredCount++;
        }
    }
}

// Optionale Zusatzdaten für berechtigte Widgets laden
$userList = ($canViewUsers && class_exists('User')) ? User::all() : [];
$allRoles = ($canViewRoles && class_exists('Rbac')) ? Rbac::getAllRoles() : [];
$modulesList = ($canViewModules && class_exists('ModuleManager')) 
    ? (method_exists('ModuleManager', 'all') ? ModuleManager::all() : (method_exists('ModuleManager', 'getAll') ? ModuleManager::getAll() : []))
    : [];

// Startseiten-Daten
$homepageTitle = 'StudioCreativo CMS';
$homepageTheme = 'standard';
$afterLoginRedirect = 'admin';
if ($canViewHomepage && class_exists('Settings')) {
    $homepageTitle = (string) Settings::get('homepage_title', 'StudioCreativo CMS');
    $homepageTheme = (string) Settings::get('homepage_theme', 'standard');
    $afterLoginRedirect = (string) Settings::get('after_login_redirect', 'admin');
}

// Seiteninhalt via Output-Buffering erfassen
ob_start();
?>

<div class="container-fluid px-0 px-md-2">

    <!-- Dashboard Kopfbereich -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1 fw-bold text-dark">Dashboard</h1>
            <p class="text-muted small mb-0">Willkommen zurück, <?= htmlspecialchars($activeEmail, ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge <?= $isMagicAdmin ? 'bg-primary' : 'bg-secondary' ?> py-2 px-3 fw-normal">
                <?= $isMagicAdmin ? 'Superadmin (Magic Auth)' : 'Rolle: ' . htmlspecialchars((string) ($user['role'] ?? 'Benutzer'), ENT_QUOTES, 'UTF-8') ?>
            </span>
        </div>
    </div>

    <!-- Flash-Meldungen -->
    <?php if ($flashSuccess): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-start">
                <div class="me-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-check-circle-fill text-success" viewBox="0 0 16 16">
                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                    </svg>
                </div>
                <div>
                    <p class="mb-1 fw-medium"><?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php if ($flashCreatedCode): ?>
                        <div class="mt-2 p-2 bg-white border border-success rounded d-inline-block">
                            <small class="text-muted d-block">Generierter Klartext-Code (wird nur einmalig angezeigt):</small>
                            <span class="code-highlight fw-bold text-success fs-5"><?= htmlspecialchars($flashCreatedCode, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>
        </div>
    <?php endif; ?>

    <?php if ($flashError): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-exclamation-triangle-fill text-danger me-2" viewBox="0 0 16 16">
                    <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5m.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/>
                </svg>
                <div><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>
        </div>
    <?php endif; ?>

    <!-- ======================================================================= -->
    <!-- 1. REIHE: 4 KACHELN MIT KENNZAHLEN (admin.magic_codes.view)             -->
    <!-- ======================================================================= -->
    <?php if ($canViewMagicCodes): ?>
    <div class="row g-3 mb-4">
        <!-- 1. Alle Codes -->
        <div class="col-sm-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Alle Codes</span>
                        <span class="fs-4 fw-bold text-dark"><?= $totalCount ?></span>
                    </div>
                    <div class="p-2 rounded bg-primary bg-opacity-10 text-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" class="bi bi-key-fill" viewBox="0 0 16 16">
                            <path d="M3.5 11.5a3.5 3.5 0 1 1 3.163-5H14L15.5 8 14 9.5l-1-1-1 1-1-1-1 1-1.5-1.5H6.663a3.5 3.5 0 0 1-3.163 3.5M2.5 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Aktiv & nutzbar -->
        <div class="col-sm-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Aktiv &amp; nutzbar</span>
                        <span class="fs-4 fw-bold text-success"><?= $activeCount ?></span>
                    </div>
                    <div class="p-2 rounded bg-success bg-opacity-10 text-success">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" class="bi bi-check-circle-fill" viewBox="0 0 16 16">
                            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Voll genutzt -->
        <div class="col-sm-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Voll genutzt</span>
                        <span class="fs-4 fw-bold text-secondary"><?= $usedCount ?></span>
                    </div>
                    <div class="p-2 rounded bg-secondary bg-opacity-10 text-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" class="bi bi-lock-fill" viewBox="0 0 16 16">
                            <path d="M8 1a2 2 0 0 0-2 2v4H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2m0 1a1 1 0 0 1 1 1v4H7V3a1 1 0 0 1 1-1"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Abgelaufen -->
        <div class="col-sm-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Abgelaufen</span>
                        <span class="fs-4 fw-bold text-warning"><?= $expiredCount ?></span>
                    </div>
                    <div class="p-2 rounded bg-warning bg-opacity-10 text-warning">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" class="bi bi-clock-history" viewBox="0 0 16 16">
                            <path d="M8.515 1.019A7 7 0 0 0 8 1V0a8 8 0 0 1 .589.022zm2.004.45a7 7 0 0 0-.985-.299l.219-.976q.576.129 1.126.342zm1.37.71a7 7 0 0 0-.439-.27l.493-.87a8 8 0 0 1 .979.654l-.615.789a7 7 0 0 0-.418-.302zm1.834 1.79a7 7 0 0 0-.653-.796l.724-.69q.406.429.747.91zm.744 1.352a7 7 0 0 0-.214-.468l.893-.45a8 8 0 0 1 .45 1.088l-.95.313a7 7 0 0 0-.179-.483m.53 2.507a7 7 0 0 0-.1-1.025l.985-.17q.1.58.116 1.17zm-.131 1.538q.05-.254.084-.51l.995.106a8 8 0 0 1-.119.764zm-.759 2.075q.083-.179.155-.364l.93.374a8 8 0 0 1-.36 1.058zm-1.127 1.691q.168-.17.314-.355l.8.6a8 8 0 0 1-.741.977zm-1.572 1.285q.228-.11.438-.238l.6.8a8 8 0 0 1-1.024.582zm-2.072.751q.255-.05.508-.122l.3.954a8 8 0 0 1-1.18.257z"/>
                            <path d="M8 4.5a.5.5 0 0 1 .5.5v3.25l2.25 1.35a.5.5 0 1 1-.515.858l-2.5-1.5A.5.5 0 0 1 8 8.5V5a.5.5 0 0 1 .5-.5"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ======================================================================= -->
    <!-- 2. REIHE: FORMULAR-CARD (LINKS) & TABELLE/LISTE (RECHTS)                -->
    <!-- ======================================================================= -->
    <?php if ($canManageMagicCodes || $canViewMagicCodes): ?>
    <div class="row g-4 mb-4">
        <?php if ($canManageMagicCodes): ?>
        <!-- Links: Neuen Code erstellen -->
        <div class="<?= $canViewMagicCodes ? 'col-lg-5 col-xl-4' : 'col-12' ?>">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header py-3 border-bottom">
                    <h2 class="h5 mb-0 fw-bold d-flex align-items-center text-dark">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-plus-circle-fill text-primary me-2 flex-shrink-0" viewBox="0 0 16 16">
                            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M8.5 4.5a.5.5 0 0 0-1 0v3h-3a.5.5 0 0 0 0 1h3v3a.5.5 0 0 0 1 0v-3h3a.5.5 0 0 0 0-1h-3z"/>
                        </svg>
                        Neuen Code erstellen
                    </h2>
                </div>
                <div class="card-body p-4">
                    <form method="post" action="?route=admin/magic-codes/create">
                        <?= Csrf::input() ?>

                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold small text-muted">Empfänger E-Mail:</label>
                            <input type="email" class="form-control" id="email" name="email" value="office@studiocreativo.ch" maxlength="191" required>
                        </div>

                        <div class="mb-3">
                            <label for="usage_type" class="form-label fw-semibold small text-muted">Verwendungszweck:</label>
                            <select class="form-select" id="usage_type" name="usage_type">
                                <option value="admin_login">admin_login (Admin-Dashboard)</option>
                                <option value="frontend">frontend (Frontend-Zugang)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="max_uses" class="form-label fw-semibold small text-muted">Maximale Nutzungen:</label>
                            <input type="number" class="form-control" id="max_uses" name="max_uses" value="1" min="1" max="999" required>
                        </div>

                        <div class="mb-4">
                            <label for="expires_at" class="form-label fw-semibold small text-muted">Ablaufdatum (optional):</label>
                            <input type="datetime-local" class="form-control" id="expires_at" name="expires_at">
                            <div class="form-text small">Leer lassen für unbegrenzte Gültigkeit.</div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                            Magic-Code generieren &amp; versenden
                        </button>
                        <div class="form-text text-center mt-2 small">
                            Kopie wird an <strong>office@studiocreativo.ch</strong> gesendet.
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($canViewMagicCodes): ?>
        <!-- Rechts: Aktuelle Codes (Tabelle oder ruhiger Leerer Zustand) -->
        <div class="<?= $canManageMagicCodes ? 'col-lg-7 col-xl-8' : 'col-12' ?>">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center">
                        <h2 class="h5 mb-0 fw-bold d-flex align-items-center text-dark me-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-shield-lock-fill text-secondary me-2 flex-shrink-0" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M8 0c-.69 0-1.843.265-2.928.56-1.11.3-2.229.655-2.887.87a1.54 1.54 0 0 0-1.044 1.262c-.596 4.477.787 7.795 2.465 9.99a11.8 11.8 0 0 0 2.517 2.453c.386.273.744.482 1.048.625.28.132.581.24.829.24s.548-.108.829-.24a7 7 0 0 0 1.048-.625 11.8 11.8 0 0 0 2.517-2.453c1.678-2.195 3.061-5.513 2.465-9.99a1.54 1.54 0 0 0-1.044-1.263 63 63 0 0 0-2.887-.87C9.843.266 8.69 0 8 0m0 5a1.5 1.5 0 0 1 .5 2.915l.385 1.99a.5.5 0 0 1-.491.595h-.788a.5.5 0 0 1-.49-.595l.384-1.99A1.5 1.5 0 0 1 8 5"/>
                            </svg>
                            Aktuelle Codes
                        </h2>
                        <span class="badge bg-secondary rounded-pill px-2 py-1">
                            <?= count($magicCodes) ?>
                        </span>
                    </div>
                    <a href="?route=admin/magic-codes" class="btn btn-sm btn-outline-primary fw-semibold">
                        Vollständige Verwaltung &rarr;
                    </a>
                </div>
                <div class="card-body p-0 d-flex flex-column justify-content-between">
                    <?php if (empty($magicCodes)): ?>
                        <!-- Ruhiger Leerer Zustand -->
                        <div class="p-5 text-center my-auto">
                            <div class="mb-3 text-muted opacity-50">
                                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" class="bi bi-key" viewBox="0 0 16 16">
                                    <path d="M0 8a4 4 0 0 1 7.465-2H14a.5.5 0 0 1 .354.146l1.5 1.5a.5.5 0 0 1 0 .708l-1.5 1.5a.5.5 0 0 1-.708 0L13 9.207l-.646.647a.5.5 0 0 1-.708 0L11 9.207l-.646.647a.5.5 0 0 1-.708 0L9 9.207l-.646.647A.5.5 0 0 1 8 10h-.535A4 4 0 0 1 0 8m4-3a3 3 0 1 0 2.712 4.285A.5.5 0 0 1 7.163 9h.63l.853-.854a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.854.854V8.707l-1.146-1.147a.5.5 0 0 1 0-.708l1.146-1.146V5.5h-1.5a.5.5 0 0 1-.354-.146L12.5 4.5l-.646.646a.5.5 0 0 1-.708 0L10.5 4.5l-.646.646a.5.5 0 0 1-.708 0L8.5 4.5l-.646.646a.5.5 0 0 1-.354.147h-.63a.5.5 0 0 1-.45-.285A3 3 0 0 0 4 5"/>
                                    <path d="M4 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/>
                                </svg>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Noch keine Magic-Codes vorhanden</h6>
                            <p class="text-muted small mb-3">Erstellen Sie einen neuen Code für den sicheren Login oder Frontend-Zugang.</p>
                            <?php if ($canManageMagicCodes): ?>
                                <p class="small text-muted mb-0">Nutzen Sie das Formular links, um den ersten Code zu erzeugen.</p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">ID</th>
                                        <th>E-Mail</th>
                                        <th>Zweck</th>
                                        <th>Nutzung</th>
                                        <th>Status</th>
                                        <th class="pe-3">Erstellt am</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $displaySlice = array_slice($magicCodes, 0, 7);
                                    foreach ($displaySlice as $row):
                                        $rowUsedCount = (int) ($row['used_count'] ?? 0);
                                        $maxUses = (int) ($row['max_uses'] ?? 1);
                                        $statusInfo = MagicCode::getStatus($row);
                                    ?>
                                    <tr>
                                        <td class="ps-3 text-muted fw-bold">#<?= (int) $row['id'] ?></td>
                                        <td>
                                            <span class="fw-semibold text-dark"><?= htmlspecialchars((string) ($row['email'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                                        </td>
                                        <td>
                                            <code class="text-secondary small bg-light px-2 py-1 rounded border"><?= htmlspecialchars((string) $row['usage_type'], ENT_QUOTES, 'UTF-8') ?></code>
                                        </td>
                                        <td>
                                            <span class="fw-medium"><?= $rowUsedCount ?></span> / <span class="text-muted"><?= $maxUses ?></span>
                                        </td>
                                        <td>
                                            <span class="badge <?= $statusInfo['badge_class'] ?>">
                                                <?= htmlspecialchars($statusInfo['label'], ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </td>
                                        <td class="pe-3 small text-muted">
                                            <?= htmlspecialchars((string) $row['created_at'], ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if (count($magicCodes) > 7): ?>
                            <div class="p-3 bg-light text-center border-top">
                                <a href="?route=admin/magic-codes" class="small fw-semibold text-decoration-none">
                                    Alle <?= count($magicCodes) ?> Codes anzeigen &rarr;
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ======================================================================= -->
    <!-- 3. REIHE: 3 INFO-KARTEN IN 3 SPALTEN (RBAC GESTEUERT)                   -->
    <!-- ======================================================================= -->
    <?php if ($canViewUsers || $canViewHomepage || $canViewModules || $canViewRoles): ?>
    <div class="row g-4 mb-4">
        <!-- Karte 1: Benutzer -->
        <?php if ($canViewUsers): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h2 class="h5 mb-0 fw-bold d-flex align-items-center text-dark">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-people-fill text-primary me-2 flex-shrink-0" viewBox="0 0 16 16">
                            <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.24 2.24 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.3 6.3 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5"/>
                        </svg>
                        Benutzer (<?= count($userList) ?>)
                    </h2>
                    <a href="?route=admin/users" class="btn btn-sm btn-outline-primary">Verwalten</a>
                </div>
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <?php if (empty($userList)): ?>
                        <div class="p-4 text-center my-auto text-muted">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-people text-secondary opacity-50 mb-2" viewBox="0 0 16 16">
                                <path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1zm-7.978-1L7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.004-.014.002zM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4m3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0M6.936 9.28a6 6 0 0 0-1.23-.247A7 7 0 0 0 5 9c-4 0-5 3-5 4q0 1 1 1h4.216A2.24 2.24 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816M4.92 10A5.5 5.5 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275ZM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0m3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4"/>
                            </svg>
                            <p class="small mb-0">Keine weiteren Benutzer angelegt.</p>
                        </div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush small mb-3">
                            <?php foreach (array_slice($userList, 0, 4) as $u): ?>
                                <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="fw-semibold text-dark"><?= htmlspecialchars((string) ($u['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="text-muted d-block small"><?= htmlspecialchars((string) ($u['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-secondary mb-1"><?= htmlspecialchars((string) ($u['role'] ?? 'user'), ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php if (!empty($u['theme_mode'])): ?>
                                            <span class="badge bg-light text-dark border d-block"><?= htmlspecialchars((string) $u['theme_mode'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if (count($userList) > 4): ?>
                            <div class="text-center pt-2 border-top">
                                <a href="?route=admin/users" class="small text-decoration-none">+ <?= count($userList) - 4 ?> weitere Benutzer</a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Karte 2: Startseite & Landing -->
        <?php if ($canViewHomepage): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h2 class="h5 mb-0 fw-bold d-flex align-items-center text-dark">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-window-sidebar text-success me-2 flex-shrink-0" viewBox="0 0 16 16">
                            <path d="M2.5 4a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1m2-.5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0m1 .5a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1"/>
                            <path d="M2 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2zm12 1a1 1 0 0 1 1 1v2H1V3a1 1 0 0 1 1-1zM1 13V6h4v8H2a1 1 0 0 1-1-1m5 1V6h9v7a1 1 0 0 1-1 1z"/>
                        </svg>
                        Startseite &amp; Landing
                    </h2>
                    <a href="?route=admin/homepage" class="btn btn-sm btn-outline-success">Bearbeiten</a>
                </div>
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div>
                        <div class="mb-2">
                            <small class="text-muted d-block">Öffentlicher Titel:</small>
                            <span class="fw-semibold text-dark"><?= htmlspecialchars($homepageTitle, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted d-block">Design / Theme:</small>
                            <span class="badge bg-info text-dark"><?= htmlspecialchars(ucfirst($homepageTheme), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block">Weiterleitung nach Login:</small>
                            <code class="text-secondary small bg-light px-2 py-1 rounded border"><?= htmlspecialchars($afterLoginRedirect, ENT_QUOTES, 'UTF-8') ?></code>
                        </div>
                    </div>
                    <div class="d-flex flex-column gap-2 pt-2 border-top">
                        <a href="?route=admin/homepage" class="btn btn-sm btn-outline-success w-100 fw-semibold">
                            Startseite bearbeiten
                        </a>
                        <a href="?route=/" target="_blank" class="btn btn-sm btn-light border w-100 text-dark">
                            Öffentliche Seite öffnen ↗
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Karte 3: Module & Sicherheit -->
        <?php if ($canViewModules || $canViewRoles): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h2 class="h5 mb-0 fw-bold d-flex align-items-center text-dark">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-grid-fill text-warning me-2 flex-shrink-0" viewBox="0 0 16 16">
                            <path d="M1 2.5A1.5 1.5 0 0 1 2.5 1h3A1.5 1.5 0 0 1 7 2.5v3A1.5 1.5 0 0 1 5.5 7h-3A1.5 1.5 0 0 1 1 5.5zm8 0A1.5 1.5 0 0 1 10.5 1h3A1.5 1.5 0 0 1 15 2.5v3A1.5 1.5 0 0 1 13.5 7h-3A1.5 1.5 0 0 1 9 5.5zm-8 8A1.5 1.5 0 0 1 2.5 9h3A1.5 1.5 0 0 1 7 10.5v3A1.5 1.5 0 0 1 5.5 15h-3A1.5 1.5 0 0 1 1 13.5zm8 0A1.5 1.5 0 0 1 10.5 9h3a1.5 1.5 0 0 1 1.5 1.5v3a1.5 1.5 0 0 1-1.5 1.5h-3A1.5 1.5 0 0 1 9 13.5z"/>
                        </svg>
                        Module &amp; Sicherheit
                    </h2>
                </div>
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div>
                        <?php if ($canViewModules): ?>
                            <div class="mb-3 pb-3 border-bottom">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="text-muted fw-semibold">Erweiterungs-Module:</small>
                                    <a href="?route=admin/modules" class="small text-decoration-none">Module verwalten</a>
                                </div>
                                <?php if (empty($modulesList)): ?>
                                    <span class="text-muted small">Keine zusätzlichen Module aktiv.</span>
                                <?php else: ?>
                                    <span class="badge bg-success">
                                        <?= count($modulesList) ?> Modul(e) registriert
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($canViewRoles): ?>
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="text-muted fw-semibold">RBAC-Rollen:</small>
                                    <a href="?route=admin/roles" class="small text-decoration-none">Rollen &amp; Rechte</a>
                                </div>
                                <span class="badge bg-secondary">
                                    <?= count($allRoles) ?> Rolle(n) konfiguriert
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="pt-3 border-top mt-3 text-center">
                        <small class="text-muted">Zugriffsrechte werden rollenbasiert gesteuert.</small>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ======================================================================= -->
    <!-- 4. FALLBACK-HINWEIS: WENN KEINE SPEZIFISCHEN WIDGETS FREIGESCHALTET SIND-->
    <!-- ======================================================================= -->
    <?php if (!$canViewMagicCodes && !$canManageMagicCodes && !$canViewUsers && !$canViewHomepage && !$canViewModules && !$canViewRoles): ?>
    <div class="card shadow-sm border-0 p-5 text-center my-4">
        <div class="py-4">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" class="bi bi-person-badge text-secondary opacity-50 mb-3" viewBox="0 0 16 16">
                <path d="M6.5 2a.5.5 0 0 0 0 1h3a.5.5 0 0 0 0-1zM11 8a3 3 0 1 1-6 0 3 3 0 0 1 6 0"/>
                <path d="M4.5 0A2.5 2.5 0 0 0 2 2.5V14a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V2.5A2.5 2.5 0 0 0 11.5 0zM3 2.5A1.5 1.5 0 0 1 4.5 1h7A1.5 1.5 0 0 1 13 2.5V14a1.5 1.5 0 0 1-1.5 1.5h-8A1.5 1.5 0 0 1 3 14z"/>
            </svg>
            <h2 class="h4 fw-bold text-dark">Willkommen im Admin-Bereich</h2>
            <p class="text-muted max-w-lg mx-auto mb-0" style="max-width: 500px;">
                Sie sind erfolgreich angemeldet. Für Ihre Rolle sind derzeit keine spezifischen Dashboard-Widgets freigeschaltet.
                Wenden Sie sich bitte an einen Administrator, falls Sie zusätzliche Berechtigungen benötigen.
            </p>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php
$content = ob_get_clean();

// Zentrales Layout einbinden
require __DIR__ . '/../layouts/admin.php';
