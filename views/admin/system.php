<?php

declare(strict_types=1);

/**
 * Admin-Ansicht: System, Versionen & Datenbank-Migrationen
 *
 * Zeigt die Core-Version, installierte Modul-Versionen, Kompatibilitätsstatus
 * und eine Übersicht aller DB-Migrationen inklusive copy-fähiger SQL-Statements für phpMyAdmin.
 * Standardmäßig werden nur die MySQL-Migrationen für den Core und die aktuell aktivierten Module angezeigt.
 */

$user = $user ?? Auth::user();
$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$title = 'System, Versionen & Migrationen';
$currentRoute = 'admin/system';

// Daten laden
$coreVersion = class_exists('CoreVersion') ? CoreVersion::VERSION : '1.0.0';
$coreFull = class_exists('CoreVersion') ? CoreVersion::getFull() : 'StudioCreativo CMS v1.0.0';
$releaseDate = class_exists('CoreVersion') ? CoreVersion::RELEASE_DATE : '2026-09-20';

$modules = class_exists('ModuleManager') ? ModuleManager::all(false) : [];
$activeModuleCount = 0;
$incompatibleModuleCount = 0;
foreach ($modules as $m) {
    if ((int) ($m['is_enabled'] ?? 0) === 1) {
        $activeModuleCount++;
    }
    if (class_exists('ModuleManager') && !ModuleManager::isCompatible($m)) {
        $incompatibleModuleCount++;
    }
}

// Filterung der Migrationen: Standard = nur aktive Module & Core
$filter = trim((string) ($_GET['filter'] ?? 'active'));
if ($filter === '') {
    $filter = 'active';
}

$allMigrations = class_exists('MigrationManager') ? MigrationManager::getAll(null, false) : [];
$activeMigrations = array_values(array_filter($allMigrations, static fn($m) => !empty($m['is_module_active'])));

// Migrationen entsprechend dem gewählten Filter bereitstellen
if ($filter === 'all') {
    $migrations = $allMigrations;
    $filterMode = 'all';
} elseif ($filter === 'active') {
    $migrations = $activeMigrations;
    $filterMode = 'active';
} else {
    // Spezifischer Modulfilter (z.B. 'core', 'boating_school', 'documents', etc.)
    $migrations = array_values(array_filter($allMigrations, static fn($m) => ($m['module_key'] ?? '') === $filter));
    $filterMode = $filter;
}

// Ausstehende Zähler berechnen
$pendingMigrationCount = 0;
foreach ($migrations as $m) {
    if (!$m['is_applied']) {
        $pendingMigrationCount++;
    }
}

// Zähler speziell für aktive Module
$activePendingCount = 0;
foreach ($activeMigrations as $m) {
    if (!$m['is_applied']) {
        $activePendingCount++;
    }
}

$isMigrationTableCreated = class_exists('MigrationManager') && MigrationManager::isTableCreated();
$createTableSql = class_exists('MigrationManager') ? MigrationManager::getCreateTableSql() : '';

// Kombiniertes SQL aller ausstehenden Migrationen für die aktuelle Ansicht
$combinedPendingSql = class_exists('MigrationManager') 
    ? MigrationManager::getCombinedPendingSql(null, $filter === 'active', ($filter !== 'all' && $filter !== 'active') ? $filter : null)
    : '';

// Modul-Liste mit Migrations-Counts für Filterleiste vorbereiten
$modulesWithMigrations = [];
foreach ($allMigrations as $mig) {
    $mKey = (string) $mig['module_key'];
    if (!isset($modulesWithMigrations[$mKey])) {
        $modulesWithMigrations[$mKey] = [
            'key'       => $mKey,
            'name'      => $mig['module_name'],
            'is_core'   => $mig['is_core'],
            'is_active' => $mig['is_module_active'],
            'total'     => 0,
            'applied'   => 0,
            'pending'   => 0,
        ];
    }
    $modulesWithMigrations[$mKey]['total']++;
    if ($mig['is_applied']) {
        $modulesWithMigrations[$mKey]['applied']++;
    } else {
        $modulesWithMigrations[$mKey]['pending']++;
    }
}

// Systemumgebung auslesen
$phpVersion = PHP_VERSION;
$pdoAvailable = extension_loaded('pdo') && extension_loaded('pdo_mysql');
$memoryLimit = ini_get('memory_limit') ?: 'N/A';
$maxUpload = ini_get('upload_max_filesize') ?: 'N/A';
$postMax = ini_get('post_max_size') ?: 'N/A';
$serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'PHP CLI / Built-in';

ob_start();
?>

<div class="container-fluid px-0">

    <!-- Flash-Nachrichten -->
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

    <!-- Kopfbereich -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1 fw-bold text-dark">System &amp; Migrationen</h1>
            <p class="text-muted mb-0">Zentrale Übersicht über Core-Version, Modul-Kompatibilität und bedarfsgerechte MySQL-Migrationen.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="?route=admin/system/health" class="btn btn-primary d-inline-flex align-items-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-heart-pulse-fill me-2" viewBox="0 0 16 16">
                    <path d="m8 2.748-.717-.737C5.6.281 2.514.878 1.4 3.053.918 3.995.78 5.323 1.508 7H.43c-2.128 0-2.483 3.013-.414 3.486L4 11.414l1.293 1.293a1 1 0 0 0 1.414 0L8 11.414l1.293 1.293a1 1 0 0 0 1.414 0L12 11.414l2.984-.828c2.069-.473 1.714-3.486-.414-3.486h-1.078c.728-1.677.59-3.005.108-3.947C12.486.878 9.4.28 7.717 2.01zM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0"/>
                </svg>
                Systemstatus &amp; Health-Check
            </a>
            <a href="?route=admin/modules" class="btn btn-outline-secondary d-inline-flex align-items-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-grid-fill me-2" viewBox="0 0 16 16">
                    <path d="M1 2.5A1.5 1.5 0 0 1 2.5 1h3A1.5 1.5 0 0 1 7 2.5v3A1.5 1.5 0 0 1 5.5 7h-3A1.5 1.5 0 0 1 1 5.5zm8 0A1.5 1.5 0 0 1 10.5 1h3A1.5 1.5 0 0 1 15 2.5v3A1.5 1.5 0 0 1 13.5 7h-3A1.5 1.5 0 0 1 9 5.5zm-8 8A1.5 1.5 0 0 1 2.5 9h3A1.5 1.5 0 0 1 7 10.5v3A1.5 1.5 0 0 1 5.5 15h-3A1.5 1.5 0 0 1 1 13.5zm8 0A1.5 1.5 0 0 1 10.5 9h3a1.5 1.5 0 0 1 1.5 1.5v3a1.5 1.5 0 0 1-1.5 1.5h-3A1.5 1.5 0 0 1 9 13.5z"/>
                </svg>
                Module verwalten
            </a>
            <a href="?route=admin" class="btn btn-light border d-inline-flex align-items-center">
                Zum Dashboard
            </a>
        </div>
    </div>

    <!-- Metrik-Karten -->
    <div class="row g-3 mb-4">
        <!-- Karte 1: Core-Version -->
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">CMS Core Version</span>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Core</span>
                    </div>
                    <div class="h3 fw-bold text-dark mb-1">v<?= htmlspecialchars($coreVersion, ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="small text-muted">
                        Stand: <?= htmlspecialchars($releaseDate, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Karte 2: Datenbank-Migrationen (Angepasst an aktive Module) -->
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">DB-Migrationen</span>
                        <?php if ($filter === 'active'): ?>
                            <?php if ($activePendingCount === 0): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Aktuell</span>
                            <?php else: ?>
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle"><?= $activePendingCount ?> offen</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"><?= htmlspecialchars($filter) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="h3 fw-bold text-dark mb-1">
                        <?= count($migrations) - $pendingMigrationCount ?> / <?= count($migrations) ?>
                    </div>
                    <div class="small text-muted">
                        <?php if ($filter === 'active'): ?>
                            <?php if ($activePendingCount === 0): ?>
                                <span class="text-success"><i class="bi bi-check-circle me-1"></i>Für alle aktiven Module aktuell</span>
                            <?php else: ?>
                                <span class="text-warning fw-semibold">⚠ <?= $activePendingCount ?> Migration(en) ausstehend</span>
                            <?php endif; ?>
                            <div class="text-muted small" style="font-size: 0.72rem;">Basis: Core &amp; <?= $activeModuleCount ?> aktive Module</div>
                        <?php else: ?>
                            <span>Filter: <?= htmlspecialchars($filter === 'all' ? 'Alle Module (inkl. inaktiv)' : $filter) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Karte 3: Module & Kompatibilität -->
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Installierte Module</span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle"><?= $activeModuleCount ?> aktiv</span>
                    </div>
                    <div class="h3 fw-bold text-dark mb-1"><?= count($modules) ?></div>
                    <div class="small text-muted">
                        <?php if ($incompatibleModuleCount === 0): ?>
                            <span class="text-success">✓ Alle Module kompatibel</span>
                        <?php else: ?>
                            <span class="text-danger fw-semibold">⚠ <?= $incompatibleModuleCount ?> inkompatibel</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Karte 4: Laufzeit-Umgebung -->
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">PHP Laufzeit</span>
                        <span class="badge bg-info-subtle text-info border border-info-subtle">PHP <?= phpversion() ?></span>
                    </div>
                    <div class="h5 fw-bold text-dark mb-1 text-truncate" title="<?= htmlspecialchars($serverSoftware, ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars(explode(' ', $serverSoftware)[0], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div class="small text-muted">
                        Memory Limit: <?= htmlspecialchars($memoryLimit, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- HINWEISBOX ZUR BEDARFSGERECHTEN SQL-AUSFÜHRUNG -->
    <div class="alert alert-info border-info-subtle shadow-sm mb-4 d-flex align-items-start gap-3">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-shield-check text-info flex-shrink-0 mt-1" viewBox="0 0 16 16">
            <path d="M5.338 1.59a61 61 0 0 0-2.837.856.48.48 0 0 0-.328.39c-.554 4.157.726 7.19 2.253 9.188a10.7 10.7 0 0 0 2.287 2.233c.346.244.652.42.893.533q.18.085.293.118a1 1 0 0 0 .101.025 1 1 0 0 0 .1-.025q.114-.034.294-.118c.24-.113.547-.29.893-.533a10.7 10.7 0 0 0 2.287-2.233c1.527-1.997 2.807-5.031 2.253-9.188a.48.48 0 0 0-.328-.39c-.651-.213-1.75-.56-2.837-.855C9.552 1.29 8.531 1.067 8 1.067c-.53 0-1.552.223-2.662.524zM5.072.56C6.157.265 7.31 0 8 0s1.843.265 2.928.56c1.11.3 2.229.655 2.887.87a1.54 1.54 0 0 1 1.044 1.262c.596 4.477-.787 7.795-2.465 9.99a11.8 11.8 0 0 1-2.517 2.453 7 7 0 0 1-1.048.625c-.28.132-.581.24-.829.24s-.548-.108-.829-.24a7 7 0 0 1-1.048-.625 11.8 11.8 0 0 1-2.517-2.453C1.928 10.487.545 7.169 1.141 2.692A1.54 1.54 0 0 1 2.185 1.43 63 63 0 0 1 5.072.56"/>
            <path d="M10.854 5.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 7.793l2.646-2.647a.5.5 0 0 1 .708 0"/>
        </svg>
        <div class="flex-grow-1">
            <h6 class="fw-bold mb-1">Bedarfsgerechte Migrationen für Ihre System-Version</h6>
            <p class="small mb-0">
                Strukturänderungen (DDL) werden aus Sicherheitsgründen nicht automatisch im PHP-Hintergrund ausgeführt. 
                Es werden standardmäßig <strong>nur die MySQL-Migrationen für den CMS-Core und Ihre aktuell aktivierten Module</strong> angezeigt, damit keine Tabellen für nicht benötigte Funktionen erzeugt werden. 
                Führen Sie die benötigten SQL-Statements bei Bedarf in <strong>phpMyAdmin</strong> aus und markieren Sie diese anschließend als ausgeführt.
            </p>
        </div>
    </div>

    <!-- TABELLE: MIGRATIONS -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h5 class="card-title mb-0 fw-bold text-dark d-flex align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-database-gear text-primary me-2" viewBox="0 0 16 16">
                            <path d="M12.096 6.223A5 5 0 0 0 13 5.698V7c0 .083-.042.22-.178.375-.246.282-.676.549-1.282.773-.807.297-1.892.482-3.04.53-.195.008-.39.012-.584.012s-.389-.004-.584-.012c-1.148-.048-2.233-.233-3.04-.53-.606-.224-1.036-.49-1.282-.772C3.043 7.22 3 7.083 3 7V5.698c.384.238.857.44 1.384.598A8 8 0 0 0 8 6.5c.712 0 1.393-.058 2.022-.167.075.127.165.25.268.368.163.187.352.348.558.479l.525-.791.723.334z"/>
                            <path d="M8 0c-4.418 0-8 1.79-8 4v5c0 2.21 3.582 4 8 4a12 12 0 0 0 1.05-.046c.11.23.25.446.417.643l.525-.791.723.334A8 8 0 0 1 8 13c-4.418 0-8-1.79-8-4V7c0 2.21 3.582 4 8 4a12 12 0 0 0 1.05-.046c.11.23.25.446.417.643l.525-.791.723.334A8 8 0 0 1 8 11c-4.418 0-8-1.79-8-4V4c0-2.21 3.582-4 8-4s8 1.79 8 4v1.5a5 5 0 0 0-1-.284V4c0-2.21-3.582-4-8-4"/>
                        </svg>
                        MySQL Datenbank-Migrationen
                    </h5>
                    <span class="text-muted small">
                        <?php if ($filter === 'active'): ?>
                            Zeigt ausschließlich Migrationen für den CMS-Kern und <strong>aktivierte Module (<?= $activeModuleCount ?> aktiv)</strong>.
                        <?php elseif ($filter === 'all'): ?>
                            Zeigt <strong>alle verfügbaren Migrationen</strong> des Repositories (inklusive nicht aktiver Module).
                        <?php else: ?>
                            Gefiltert nach Modul: <strong><?= htmlspecialchars($filter) ?></strong>.
                        <?php endif; ?>
                    </span>
                </div>

                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <?php if (!$isMigrationTableCreated): ?>
                        <span class="badge bg-warning text-dark px-3 py-2">
                            Tabelle `migrations` fehlt noch
                        </span>
                    <?php else: ?>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">
                            Tracking aktiv
                        </span>
                    <?php endif; ?>

                    <?php if (!empty($combinedPendingSql) && $pendingMigrationCount > 0): ?>
                        <button type="button" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1 shadow-sm" onclick="copySql('combined-pending-sql', this)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" class="bi bi-copy" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M4 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1zM2 5a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1v-1h1v1a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h1v1z"/>
                            </svg>
                            <span>Alle ausstehenden SQL kopieren (<?= $pendingMigrationCount ?>)</span>
                        </button>
                        <textarea id="combined-pending-sql" style="display: none;"><?= htmlspecialchars($combinedPendingSql, ENT_QUOTES, 'UTF-8') ?></textarea>
                    <?php endif; ?>

                    <?php if (!empty($migrations)): ?>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAllAppliedMigrations()" title="Alle eingespielten Migrationen ein- oder ausklappen">
                            <span id="toggleAllAppliedText">Eingespielte ausklappen</span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Filter-Leiste: Nur aktive Module vs. Alle Module & Modulauswahl -->
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 pt-2 border-top">
                <div class="d-flex align-items-center gap-1 flex-wrap">
                    <span class="text-muted small fw-semibold me-2">Ansicht:</span>
                    <a href="?route=admin/system&filter=active" class="btn btn-sm <?= $filter === 'active' ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill px-3 py-1">
                        <i class="bi bi-check-circle me-1"></i>Nur aktive Module &amp; Core
                        <span class="badge <?= $filter === 'active' ? 'bg-white text-primary' : 'bg-secondary text-white' ?> ms-1"><?= count($activeMigrations) ?></span>
                    </a>
                    <a href="?route=admin/system&filter=all" class="btn btn-sm <?= $filter === 'all' ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill px-3 py-1">
                        Alle Module anzeigen
                        <span class="badge <?= $filter === 'all' ? 'bg-white text-primary' : 'bg-secondary text-white' ?> ms-1"><?= count($allMigrations) ?></span>
                    </a>
                </div>

                <!-- Modul-Direktschalter -->
                <div class="d-flex align-items-center gap-1 flex-wrap">
                    <span class="text-muted small me-1">Nach Modul filtern:</span>
                    <?php foreach ($modulesWithMigrations as $mKey => $mInfo): 
                        $isSelected = ($filter === $mKey);
                        $btnClass = $isSelected ? 'btn-dark' : 'btn-light border';
                    ?>
                        <a href="?route=admin/system&filter=<?= urlencode($mKey) ?>" class="btn btn-sm <?= $btnClass ?> py-0 px-2 rounded-2" style="font-size: 0.78rem;" title="<?= htmlspecialchars($mInfo['name']) ?> (<?= $mInfo['total'] ?> Migrationen)">
                            <?= htmlspecialchars($mInfo['name']) ?>
                            <?php if (!$mInfo['is_active'] && !$mInfo['is_core']): ?>
                                <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.65rem;">inaktiv</span>
                            <?php endif; ?>
                            <span class="text-muted ms-1">(<?= $mInfo['total'] ?>)</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="card-body p-0">

            <!-- Falls Migrations-Tabelle noch nicht existiert -->
            <?php if (!$isMigrationTableCreated): ?>
                <div class="p-4 bg-light border-bottom">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold text-dark mb-0">Schritt 1: Tabelle <code>migrations</code> in phpMyAdmin anlegen</h6>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="copySql('migration-table-sql', this)">
                            SQL kopieren
                        </button>
                    </div>
                    <p class="small text-muted mb-2">
                        Führen Sie diese DDL-Anweisung einmalig in phpMyAdmin aus, um das Tracking der Migrationen zu aktivieren:
                    </p>
                    <pre class="bg-dark text-light p-3 rounded small mb-0"><code id="migration-table-sql"><?= htmlspecialchars($createTableSql, ENT_QUOTES, 'UTF-8') ?></code></pre>
                </div>
            <?php endif; ?>

            <?php if (empty($migrations)): ?>
                <div class="text-center py-5 text-muted">
                    <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" fill="currentColor" class="bi bi-folder-check text-muted mb-2" viewBox="0 0 16 16">
                        <path d="m.5 3 .04.87a2 2 0 0 0-.342 1.311l.637 7A2 2 0 0 0 2.826 14H9v-1H2.826a1 1 0 0 1-.995-.91l-.637-7A1 1 0 0 1 2.19 4h11.62a1 1 0 0 1 .996 1.09l-.637 7a1 1 0 0 1-.995.91H11v1h2.174a2 2 0 0 0 1.995-1.819l.637-7a2 2 0 0 0-.342-1.31L15.5 3z"/>
                        <path d="M15.854 10.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 0 1 .708-.708l1.146 1.147 2.646-2.647a.5.5 0 0 1 .708 0"/>
                        <path d="M13.5 1a.5.5 0 0 1 .5.5v1h1.5a.5.5 0 0 1 0 1H14v1.5a.5.5 0 0 1-1 0V3.5h-1.5a.5.5 0 0 1 0-1H13V1.5a.5.5 0 0 1 .5-.5"/>
                    </svg>
                    <h6>Keine Migrationen für diesen Filter gefunden</h6>
                    <p class="small mb-3">Für die gewählte Auswahl sind aktuell keine SQL-Migrationen hinterlegt.</p>
                    <a href="?route=admin/system&filter=active" class="btn btn-sm btn-outline-primary">Zu den aktiven Modulen zurückkehren</a>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush" id="migrations-accordion">
                    <?php foreach ($migrations as $idx => $mig): 
                        $isApplied = $mig['is_applied'];
                        $sqlId = 'mig-sql-' . $idx;
                        $trackingSqlId = 'mig-track-' . $idx;
                        $collapseId = 'mig-collapse-' . $idx;
                        $isCore = (bool) ($mig['is_core'] ?? false);
                        $isModActive = (bool) ($mig['is_module_active'] ?? true);
                        $moduleKey = (string) ($mig['module_key'] ?? 'core');
                        $moduleName = (string) ($mig['module_name'] ?? 'Core');
                    ?>
                        <div class="list-group-item p-3 p-md-4 <?= $isApplied ? 'bg-light-subtle migration-applied' : 'migration-pending' ?>">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="d-flex align-items-center flex-wrap gap-2">
                                    <?php if ($isApplied): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle p-1 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 24px; height: 24px;" title="Eingespielt">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-check2" viewBox="0 0 16 16">
                                                <path d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0z"/>
                                            </svg>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle p-1 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 24px; height: 24px;" title="Ausstehend">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-clock" viewBox="0 0 16 16">
                                                <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z"/>
                                                <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0"/>
                                            </svg>
                                        </span>
                                    <?php endif; ?>

                                    <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($mig['title'], ENT_QUOTES, 'UTF-8') ?></h6>

                                    <!-- Modul-Badge -->
                                    <?php if ($isCore): ?>
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle font-monospace small">
                                            <i class="bi bi-cpu me-1"></i>Core System
                                        </span>
                                    <?php elseif ($moduleKey === 'boating_school'): ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace small">
                                            <i class="bi bi-compass me-1"></i>Nautic
                                        </span>
                                    <?php elseif ($isModActive): ?>
                                        <span class="badge bg-info-subtle text-info border border-info-subtle font-monospace small">
                                            <i class="bi bi-box me-1"></i><?= htmlspecialchars($moduleName, ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-dark border border-warning-subtle font-monospace small" title="Dieses Modul ist aktuell inaktiv">
                                            <i class="bi bi-pause-circle me-1"></i><?= htmlspecialchars($moduleName, ENT_QUOTES, 'UTF-8') ?> (inaktiv)
                                        </span>
                                    <?php endif; ?>

                                    <span class="badge bg-light text-secondary border font-monospace small">
                                        <?= htmlspecialchars($mig['filename'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </div>

                                <div class="d-flex align-items-center gap-2">
                                    <?php if ($isApplied): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 d-inline-flex align-items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-check2 me-1" viewBox="0 0 16 16">
                                                <path d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0z"/>
                                            </svg>
                                            Eingespielt <?= !empty($mig['executed_at']) ? '(' . htmlspecialchars($mig['executed_at'], ENT_QUOTES, 'UTF-8') . ')' : '' ?>
                                        </span>
                                        <form method="POST" action="?route=admin/system/unmark-migration" class="d-inline" onsubmit="return confirm('Möchten Sie den Status dieser Migration wirklich auf unerledigt zurücksetzen?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(class_exists('Csrf') ? Csrf::getToken() : '', ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="key" value="<?= htmlspecialchars($mig['key'], ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="redirect_to" value="?route=admin/system&filter=<?= urlencode($filter) ?>">
                                            <button type="submit" class="btn btn-sm btn-link text-muted p-0 text-decoration-none small" title="Status auf unerledigt zurücksetzen">
                                                Zurücksetzen
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2">
                                            Ausstehend
                                        </span>
                                        <form method="POST" action="?route=admin/system/mark-migration" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(class_exists('Csrf') ? Csrf::getToken() : '', ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="key" value="<?= htmlspecialchars($mig['key'], ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="redirect_to" value="?route=admin/system&filter=<?= urlencode($filter) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-success">
                                                Als ausgeführt markieren
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <button class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $collapseId ?>" aria-expanded="<?= !$isApplied ? 'true' : 'false' ?>" aria-controls="<?= $collapseId ?>" title="Details und SQL ein- oder ausklappen">
                                        <span class="collapse-btn-text"><?= $isApplied ? 'Details anzeigen' : 'Einklappen' ?></span>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="bi bi-chevron-down" viewBox="0 0 16 16">
                                            <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="collapse <?= !$isApplied ? 'show' : '' ?> mt-3" id="<?= $collapseId ?>">
                                <?php if (!empty($mig['description'])): ?>
                                    <p class="small text-muted mb-2"><?= htmlspecialchars($mig['description'], ENT_QUOTES, 'UTF-8') ?></p>
                                <?php endif; ?>

                                <?php if (!$isCore && !$isModActive): ?>
                                    <div class="alert alert-warning py-2 px-3 small mb-2 d-flex align-items-center gap-2">
                                        <i class="bi bi-info-circle-fill text-warning"></i>
                                        <span>Das Modul <strong><?= htmlspecialchars($moduleName) ?></strong> ist in dieser System-Installation derzeit deaktiviert. Diese Migration wird nicht zwingend benötigt.</span>
                                    </div>
                                <?php endif; ?>

                                <!-- SQL Box zum Kopieren -->
                                <div class="mt-2">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <small class="text-muted fw-semibold">SQL für phpMyAdmin:</small>
                                        <div class="d-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 0.75rem;" onclick="copySql('<?= $sqlId ?>', this)">
                                                DDL kopieren
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 0.75rem;" onclick="copySql('<?= $trackingSqlId ?>', this)" title="Tracking-SQL zum Eintragen in migrations-Tabelle">
                                                Tracking-SQL
                                            </button>
                                        </div>
                                    </div>
                                    <pre class="bg-dark text-light p-3 rounded small mb-2" style="max-height: 180px; overflow-y: auto;"><code id="<?= $sqlId ?>"><?= htmlspecialchars($mig['sql'], ENT_QUOTES, 'UTF-8') ?></code></pre>
                                    <pre class="bg-dark text-secondary p-2 rounded small mb-0" style="display: none;"><code id="<?= $trackingSqlId ?>"><?= htmlspecialchars($mig['tracking_sql'], ENT_QUOTES, 'UTF-8') ?></code></pre>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ABSCHNITT: MODULE & KOMPATIBILITÄT -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="card-title mb-0 fw-bold text-dark d-flex align-items-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-boxes text-primary me-2" viewBox="0 0 16 16">
                    <path d="M7.752.066a.5.5 0 0 1 .496 0l3.75 2.143a.5.5 0 0 1 .252.434v3.995l3.498 2A.5.5 0 0 1 16 9.07v4.286a.5.5 0 0 1-.252.434l-3.75 2.143a.5.5 0 0 1-.496 0l-3.502-2-3.502 2.001a.5.5 0 0 1-.496 0l-3.75-2.143A.5.5 0 0 1 0 13.357V9.071a.5.5 0 0 1 .252-.434L3.75 6.638V2.643a.5.5 0 0 1 .252-.434zM4.25 7.504 1.508 9.071l2.742 1.567 2.742-1.567zM7.5 9.933l-2.75 1.571v3.134l2.75-1.571zm1 3.134 2.75 1.571v-3.134L8.5 9.933zm3.75-2.366 2.742-1.567-2.742-1.567-2.742 1.567zM8 8.498l2.75-1.571V3.793L8 5.364zM7 5.364 4.25 3.793v3.134L7 8.498zM4.5 1.866 1.758 3.433 4.5 5.001l2.742-1.568zM8.5 5.001l2.742-1.568L8.5 1.866 5.758 3.433z"/>
                </svg>
                Installierte Module &amp; Core-Kompatibilität
            </h5>
            <span class="text-muted small">Prüft, ob die Modulversionen mit der aktuellen Core-Version (v<?= htmlspecialchars($coreVersion, ENT_QUOTES, 'UTF-8') ?>) harmonieren.</span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($modules)): ?>
                <div class="text-center py-4 text-muted small">
                    Keine Module registriert.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Modul</th>
                                <th>Schlüssel</th>
                                <th>Modul-Version</th>
                                <th>Benötigt Core</th>
                                <th>Kompatibilität</th>
                                <th>Status</th>
                                <th class="text-end pe-3">MySQL Migrationen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($modules as $mod): 
                                $key = (string) ($mod['key'] ?? '');
                                $name = (string) ($mod['name'] ?? $key);
                                $version = (string) ($mod['version'] ?? '1.0.0');
                                $requiresCore = (string) ($mod['requires_core'] ?? '');
                                $isEnabled = (int) ($mod['is_enabled'] ?? 0) === 1;
                                $compat = class_exists('ModuleManager') ? ModuleManager::getCompatibilityInfo($mod) : ['is_compatible' => true, 'message' => 'OK'];
                                $modMigInfo = $modulesWithMigrations[$key] ?? null;
                            ?>
                                <tr>
                                    <td class="ps-3 fw-semibold text-dark"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><code><?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?></code></td>
                                    <td>
                                        <span class="badge bg-light text-dark border">v<?= htmlspecialchars($version, ENT_QUOTES, 'UTF-8') ?></span>
                                    </td>
                                    <td>
                                        <?php if ($requiresCore !== ''): ?>
                                            <code><?= htmlspecialchars($requiresCore, ENT_QUOTES, 'UTF-8') ?></code>
                                        <?php else: ?>
                                            <span class="text-muted small">Beliebig (*)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($compat['is_compatible']): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                ✓ Kompatibel
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle" title="<?= htmlspecialchars($compat['message'], ENT_QUOTES, 'UTF-8') ?>">
                                                ⚠ Inkompatibel
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($isEnabled): ?>
                                            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Aktiv</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><i class="bi bi-pause-circle me-1"></i>Deaktiviert</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-3">
                                        <?php if ($modMigInfo): ?>
                                            <a href="?route=admin/system&filter=<?= urlencode($key) ?>" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 0.75rem;">
                                                <?= $modMigInfo['applied'] ?> / <?= $modMigInfo['total'] ?> SQL
                                                <?php if ($modMigInfo['pending'] > 0 && $isEnabled): ?>
                                                    <span class="badge bg-warning text-dark ms-1"><?= $modMigInfo['pending'] ?> offen</span>
                                                <?php endif; ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ABSCHNITT: SYSTEM- & RUNTIME-INFOS -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="card-title mb-0 fw-bold text-dark d-flex align-items-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-info-circle-fill text-primary me-2" viewBox="0 0 16 16">
                    <path d="M8 16A8 8 0 1 1 0 8a8 8 0 0 0 0 16m.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2"/>
                </svg>
                Systemumgebung &amp; Parameter
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0 small">
                    <tbody>
                        <tr>
                            <td class="ps-3 fw-semibold text-muted" style="width: 250px;">CMS Core</td>
                            <td><?= htmlspecialchars($coreFull, ENT_QUOTES, 'UTF-8') ?> (Veröffentlicht: <?= htmlspecialchars($releaseDate, ENT_QUOTES, 'UTF-8') ?>)</td>
                        </tr>
                        <tr>
                            <td class="ps-3 fw-semibold text-muted">PHP-Version</td>
                            <td>PHP <?= phpversion() ?> (Voraussetzung: &gt;= 8.1.0)</td>
                        </tr>
                        <tr>
                            <td class="ps-3 fw-semibold text-muted">Server Software</td>
                            <td><?= htmlspecialchars($serverSoftware, ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                        <tr>
                            <td class="ps-3 fw-semibold text-muted">PDO MySQL Treiber</td>
                            <td>
                                <?php if ($pdoAvailable): ?>
                                    <span class="text-success fw-semibold">✓ Installiert und aktiv</span>
                                <?php else: ?>
                                    <span class="text-danger fw-semibold">⚠ Fehlt</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="ps-3 fw-semibold text-muted">Speicherlimit (memory_limit)</td>
                            <td><?= htmlspecialchars($memoryLimit, ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                        <tr>
                            <td class="ps-3 fw-semibold text-muted">Max. Upload / POST Größe</td>
                            <td>Upload: <?= htmlspecialchars($maxUpload, ENT_QUOTES, 'UTF-8') ?> | Post: <?= htmlspecialchars($postMax, ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                        <tr>
                            <td class="ps-3 fw-semibold text-muted">Zeitzone</td>
                            <td><?= htmlspecialchars(date_default_timezone_get(), ENT_QUOTES, 'UTF-8') ?> (Serverzeit: <?= date('Y-m-d H:i:s') ?>)</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- JavaScript zum bequemen Kopieren von SQL in die Zwischenablage -->
<script>
function copySql(elementId, btn) {
    var codeElem = document.getElementById(elementId);
    if (!codeElem) return;
    var text = codeElem.innerText || codeElem.value || codeElem.textContent;

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(function() {
            showCopiedFeedback(btn);
        }).catch(function() {
            fallbackCopy(text, btn);
        });
    } else {
        fallbackCopy(text, btn);
    }
}

function fallbackCopy(text, btn) {
    var textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.position = "fixed";
    textArea.style.top = "0";
    textArea.style.left = "0";
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    try {
        document.execCommand('copy');
        showCopiedFeedback(btn);
    } catch (err) {
        console.error('Kopieren fehlgeschlagen', err);
    }
    document.body.removeChild(textArea);
}

function showCopiedFeedback(btn) {
    var originalText = btn.innerHTML;
    btn.innerText = "✓ Kopiert!";
    btn.classList.add("btn-success");
    btn.classList.remove("btn-primary", "btn-outline-primary", "btn-outline-secondary");
    setTimeout(function() {
        btn.innerHTML = originalText;
        btn.classList.remove("btn-success");
        btn.classList.add("btn-outline-secondary");
    }, 2000);
}

// Steuerung für das Ein-/Ausklappen eingespielter Migrationen
var allAppliedExpanded = false;
function toggleAllAppliedMigrations() {
    var appliedItems = document.querySelectorAll('.migration-applied .collapse');
    allAppliedExpanded = !allAppliedExpanded;
    appliedItems.forEach(function(el) {
        if (allAppliedExpanded) {
            el.classList.add('show');
            var btn = el.closest('.list-group-item').querySelector('.collapse-btn-text');
            if (btn) btn.innerText = 'Einklappen';
        } else {
            el.classList.remove('show');
            var btn = el.closest('.list-group-item').querySelector('.collapse-btn-text');
            if (btn) btn.innerText = 'Details anzeigen';
        }
    });
    var btnText = document.getElementById('toggleAllAppliedText');
    if (btnText) {
        btnText.innerText = allAppliedExpanded ? 'Eingespielte einklappen' : 'Eingespielte ausklappen';
    }
}

// Aktualisierung der Button-Beschriftung bei individuellem Auf-/Zuklappen
document.addEventListener('DOMContentLoaded', function() {
    var collapseEls = document.querySelectorAll('#migrations-accordion .collapse');
    collapseEls.forEach(function(el) {
        el.addEventListener('show.bs.collapse', function() {
            var btn = this.closest('.list-group-item').querySelector('.collapse-btn-text');
            if (btn) btn.innerText = 'Einklappen';
        });
        el.addEventListener('hide.bs.collapse', function() {
            var btn = this.closest('.list-group-item').querySelector('.collapse-btn-text');
            if (btn) btn.innerText = 'Details anzeigen';
        });
    });
});
</script>

<?php
$content = ob_get_clean();

// Zentrales Admin-Layout einbinden
require __DIR__ . '/../layouts/admin.php';
