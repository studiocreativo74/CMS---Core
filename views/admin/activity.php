<?php
declare(strict_types=1);

/**
 * Admin: Globale Audit- und Aktivitätsprotokolle (/admin/activity)
 *
 * @var array<int, array<string, mixed>> $logs
 * @var int $totalCount
 * @var int $totalPages
 * @var int $page
 * @var int $limit
 * @var array<string, mixed> $filters
 * @var array<int, string> $eventTypes
 * @var array<string, int|string> $stats
 */

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$title = 'Aktivität & Audit-Logs';
$currentRoute = 'admin/activity';

$activeTab = (string) ($_GET['tab'] ?? 'audit');

// Letzte Logins und Magic-Code-Aktivitäten für optionale Sekundär-Tabs
$recentLogins = [];
try {
    $recentLogins = DB::fetchAll('SELECT `id`, `name`, `email`, `last_login_at` FROM `users` WHERE `last_login_at` IS NOT NULL ORDER BY `last_login_at` DESC LIMIT 15');
} catch (\Throwable $e) {
    // DB not available or empty
}

$recentCodes = [];
try {
    $recentCodes = DB::fetchAll('SELECT `id`, `email`, `usage_type`, `used_count`, `used_at`, `created_at` FROM `magic_codes` ORDER BY `created_at` DESC LIMIT 15');
} catch (\Throwable $e) {
    // DB not available or empty
}

// Hilfsfunktion: Berechnet relative Zeit (z. B. "vor 5 Min.")
$formatRelativeTime = static function (string $datetime): string {
    $ts = strtotime($datetime);
    if ($ts === false) {
        return $datetime;
    }
    $diff = time() - $ts;
    if ($diff < 60) {
        return 'gerade eben';
    }
    if ($diff < 3600) {
        $m = (int) floor($diff / 60);
        return 'vor ' . $m . ' Min.';
    }
    if ($diff < 86400) {
        $h = (int) floor($diff / 3600);
        return 'vor ' . $h . ' Std.';
    }
    if ($diff < 604800) {
        $d = (int) floor($diff / 86400);
        return 'vor ' . $d . ' T.';
    }
    return date('d.m.Y H:i', $ts);
};

// URL-Builder für Paginierung und Filter
$buildUrl = static function (array $overrideParams) use ($filters, $limit, $page, $activeTab): string {
    $params = array_merge([
        'route'      => 'admin/activity',
        'tab'        => $activeTab,
        'page'       => $page,
        'limit'      => $limit,
        'search'     => $filters['search'] ?? '',
        'event_type' => $filters['event_type'] ?? '',
        'category'   => $filters['category'] ?? '',
        'email'      => $filters['email'] ?? '',
        'date_from'  => $filters['date_from'] ?? '',
        'date_to'    => $filters['date_to'] ?? '',
        'case_id'    => $filters['case_id'] ?? '',
        'user_id'    => $filters['user_id'] ?? '',
    ], $overrideParams);

    // Leere Parameter entfernen
    $filtered = array_filter($params, static fn($v) => $v !== '' && $v !== null);
    return '?' . http_build_query($filtered);
};

$hasActiveFilters = !empty($filters['search']) 
    || !empty($filters['event_type']) 
    || !empty($filters['category']) 
    || !empty($filters['email']) 
    || !empty($filters['date_from']) 
    || !empty($filters['date_to']) 
    || !empty($filters['case_id']) 
    || !empty($filters['user_id']);

ob_start();
?>

<!-- Kopfbereich mit Seitentitel und Aktionen -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="mb-1 fw-bold text-dark d-flex align-items-center gap-2">
            <i class="bi bi-journal-text text-primary"></i>
            Aktivität &amp; Audit-Logs
        </h4>
        <p class="text-muted mb-0 small">
            Lückenlose Nachvollziehbarkeit aller System-, Sicherheits-, Benutzer- und Vorgangsereignisse.
        </p>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
        <a href="<?= htmlspecialchars($buildUrl(['export' => 'csv']), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1 shadow-sm" title="Aktuelle Filterergebnisse als CSV herunterladen">
            <i class="bi bi-file-earmark-spreadsheet"></i>
            <span>CSV-Export</span>
        </a>
        <a href="?route=admin/activity" class="btn btn-light btn-sm d-inline-flex align-items-center gap-1 border shadow-sm" title="Ansicht aktualisieren">
            <i class="bi bi-arrow-clockwise"></i>
            <span>Aktualisieren</span>
        </a>
    </div>
</div>

<?php if ($flashSuccess): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
        <i class="bi bi-check-circle-fill text-success me-2"></i>
        <?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>
    </div>
<?php endif; ?>

<?php if ($flashError): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>
        <?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>
    </div>
<?php endif; ?>

<!-- 1. Statistik-Karten -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center p-3">
                <div class="rounded-circle bg-primary-subtle text-primary p-3 me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i class="bi bi-journal-check fs-5"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.75rem;">Gesamtprotokolle</div>
                    <div class="fs-4 fw-bold text-dark"><?= number_format((int) ($stats['total_count'] ?? 0), 0, ',', '.') ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center p-3">
                <div class="rounded-circle bg-success-subtle text-success p-3 me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i class="bi bi-calendar-event fs-5"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.75rem;">Ereignisse heute</div>
                    <div class="fs-4 fw-bold text-dark"><?= number_format((int) ($stats['today_count'] ?? 0), 0, ',', '.') ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center p-3">
                <div class="rounded-circle bg-info-subtle text-info p-3 me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i class="bi bi-folder2-open fs-5"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.75rem;">Sicherungsfälle</div>
                    <div class="fs-4 fw-bold text-dark"><?= number_format((int) ($stats['case_events_count'] ?? 0), 0, ',', '.') ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center p-3">
                <div class="rounded-circle bg-warning-subtle text-warning p-3 me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i class="bi bi-clock-history fs-5"></i>
                </div>
                <div class="overflow-hidden">
                    <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.75rem;">Letztes Event</div>
                    <div class="fw-bold text-dark text-truncate" style="font-size: 0.95rem;">
                        <?php if (!empty($stats['latest_timestamp']) && $stats['latest_timestamp'] !== '—'): ?>
                            <?= date('d.m.Y H:i', strtotime((string) $stats['latest_timestamp'])) ?>
                            <span class="text-muted small fw-normal d-block" style="font-size: 0.75rem;">(<?= $formatRelativeTime((string) $stats['latest_timestamp']) ?>)</span>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabs Navigation -->
<ul class="nav nav-pills mb-4 bg-white p-2 rounded shadow-sm border" id="activityTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <a class="nav-link <?= $activeTab === 'audit' ? 'active' : '' ?> fw-semibold d-inline-flex align-items-center gap-2" href="?route=admin/activity&tab=audit">
            <i class="bi bi-list-task"></i>
            Globales Audit-Log
            <span class="badge bg-secondary-subtle text-secondary rounded-pill"><?= number_format($totalCount, 0, ',', '.') ?></span>
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link <?= $activeTab === 'logins' ? 'active' : '' ?> fw-semibold d-inline-flex align-items-center gap-2" href="?route=admin/activity&tab=logins">
            <i class="bi bi-box-arrow-in-right"></i>
            Letzte Benutzer-Logins
            <span class="badge bg-secondary-subtle text-secondary rounded-pill"><?= count($recentLogins) ?></span>
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link <?= $activeTab === 'magic' ? 'active' : '' ?> fw-semibold d-inline-flex align-items-center gap-2" href="?route=admin/activity&tab=magic">
            <i class="bi bi-key"></i>
            Magic-Codes Historie
            <span class="badge bg-secondary-subtle text-secondary rounded-pill"><?= count($recentCodes) ?></span>
        </a>
    </li>
</ul>

<?php if ($activeTab === 'audit'): ?>
    <!-- 2. Filter-Leiste -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
            <h6 class="card-title mb-0 fw-semibold text-dark d-flex align-items-center gap-2">
                <i class="bi bi-funnel text-secondary"></i>
                Filter &amp; Suche
            </h6>
            <?php if ($hasActiveFilters): ?>
                <a href="?route=admin/activity" class="btn btn-outline-danger btn-sm py-0 px-2" style="font-size: 0.8rem;">
                    <i class="bi bi-x-circle me-1"></i>Filter zurücksetzen
                </a>
            <?php endif; ?>
        </div>
        <div class="card-body p-3 p-md-4">
            <form method="GET" action="" class="row g-3">
                <input type="hidden" name="route" value="admin/activity">
                <input type="hidden" name="tab" value="audit">
                <?php if (!empty($filters['case_id'])): ?>
                    <input type="hidden" name="case_id" value="<?= htmlspecialchars((string) $filters['case_id'], ENT_QUOTES, 'UTF-8') ?>">
                <?php endif; ?>
                <?php if (!empty($filters['user_id'])): ?>
                    <input type="hidden" name="user_id" value="<?= htmlspecialchars((string) $filters['user_id'], ENT_QUOTES, 'UTF-8') ?>">
                <?php endif; ?>

                <div class="col-md-4 col-lg-3">
                    <label class="form-label small fw-semibold text-secondary">Suchbegriff</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Details, IP, Fall-ID..." value="<?= htmlspecialchars((string) ($filters['search'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>

                <div class="col-md-4 col-lg-3">
                    <label class="form-label small fw-semibold text-secondary">Ereignis-Typ</label>
                    <select name="event_type" class="form-select form-select-sm">
                        <option value="">Alle Ereignistypen</option>
                        <?php if (!empty($eventTypes)): ?>
                            <optgroup label="Verfügbare Ereignisse">
                                <?php foreach ($eventTypes as $t): 
                                    $meta = ActivityLogRepository::getEventMeta($t);
                                ?>
                                    <option value="<?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8') ?>" <?= ($filters['event_type'] ?? '') === $t ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($meta['title'] . ' (' . $t . ')', ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endif; ?>
                        <optgroup label="Standard-Ereignisse">
                            <option value="secure_case.created" <?= ($filters['event_type'] ?? '') === 'secure_case.created' ? 'selected' : '' ?>>Vorgang erstellt (secure_case.created)</option>
                            <option value="secure_case.warrant_uploaded" <?= ($filters['event_type'] ?? '') === 'secure_case.warrant_uploaded' ? 'selected' : '' ?>>Editionsverfügung hochgeladen</option>
                            <option value="secure_case.warrant_sp_uploaded" <?= ($filters['event_type'] ?? '') === 'secure_case.warrant_sp_uploaded' ? 'selected' : '' ?>>SharePoint archiviert (secure_case.warrant_sp_uploaded)</option>
                            <option value="secure_case.file_uploaded" <?= ($filters['event_type'] ?? '') === 'secure_case.file_uploaded' ? 'selected' : '' ?>>Sicherungsdatei bereitgestellt</option>
                            <option value="secure_case.file_downloaded" <?= ($filters['event_type'] ?? '') === 'secure_case.file_downloaded' ? 'selected' : '' ?>>Datei heruntergeladen (secure_case.file_downloaded)</option>
                            <option value="secure_case.download_enabled" <?= ($filters['event_type'] ?? '') === 'secure_case.download_enabled' ? 'selected' : '' ?>>Download freigeschaltet</option>
                            <option value="secure_case.download_disabled" <?= ($filters['event_type'] ?? '') === 'secure_case.download_disabled' ? 'selected' : '' ?>>Download gesperrt</option>
                            <option value="auth.login" <?= ($filters['event_type'] ?? '') === 'auth.login' ? 'selected' : '' ?>>Benutzer-Login (auth.login)</option>
                            <option value="user.role_changed" <?= ($filters['event_type'] ?? '') === 'user.role_changed' ? 'selected' : '' ?>>Rollenänderung (user.role_changed)</option>
                            <option value="healthcheck.mail_failed" <?= ($filters['event_type'] ?? '') === 'healthcheck.mail_failed' ? 'selected' : '' ?>>Mail-Fehler (healthcheck.mail_failed)</option>
                        </optgroup>
                    </select>
                </div>

                <div class="col-md-4 col-lg-2">
                    <label class="form-label small fw-semibold text-secondary">Kategorie</label>
                    <select name="category" class="form-select form-select-sm">
                        <option value="">Alle Kategorien</option>
                        <option value="secure_case" <?= ($filters['category'] ?? '') === 'secure_case' ? 'selected' : '' ?>>Sicherungsfälle</option>
                        <option value="auth" <?= ($filters['category'] ?? '') === 'auth' ? 'selected' : '' ?>>Authentifizierung</option>
                        <option value="user" <?= ($filters['category'] ?? '') === 'user' ? 'selected' : '' ?>>Benutzer &amp; Rollen</option>
                        <option value="healthcheck" <?= ($filters['category'] ?? '') === 'healthcheck' ? 'selected' : '' ?>>Systemdiagnose</option>
                        <option value="system" <?= ($filters['category'] ?? '') === 'system' ? 'selected' : '' ?>>System &amp; Settings</option>
                    </select>
                </div>

                <div class="col-md-4 col-lg-2">
                    <label class="form-label small fw-semibold text-secondary">Benutzer / E-Mail</label>
                    <input type="text" name="email" class="form-control form-control-sm" placeholder="z. B. admin@..." value="<?= htmlspecialchars((string) ($filters['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="col-md-4 col-lg-2">
                    <label class="form-label small fw-semibold text-secondary">Einträge / Seite</label>
                    <select name="limit" class="form-select form-select-sm">
                        <option value="25" <?= $limit === 25 ? 'selected' : '' ?>>25 Einträge</option>
                        <option value="50" <?= $limit === 50 ? 'selected' : '' ?>>50 Einträge</option>
                        <option value="100" <?= $limit === 100 ? 'selected' : '' ?>>100 Einträge</option>
                    </select>
                </div>

                <div class="col-md-6 col-lg-3">
                    <label class="form-label small fw-semibold text-secondary">Datum von</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($filters['date_from'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="col-md-6 col-lg-3">
                    <label class="form-label small fw-semibold text-secondary">Datum bis</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars((string) ($filters['date_to'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="col-12 d-flex align-items-center justify-content-end gap-2 pt-2">
                    <?php if ($hasActiveFilters): ?>
                        <a href="?route=admin/activity" class="btn btn-light btn-sm border">
                            Filter zurücksetzen
                        </a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary btn-sm px-3 shadow-sm">
                        <i class="bi bi-funnel-fill me-1"></i> Filtern
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Aktive Filter-Pills -->
    <?php if ($hasActiveFilters): ?>
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <span class="small text-muted fw-semibold me-1">Aktive Filter:</span>
            <?php if (!empty($filters['search'])): ?>
                <span class="badge bg-light text-dark border d-inline-flex align-items-center gap-1">
                    Suchbegriff: "<?= htmlspecialchars($filters['search'], ENT_QUOTES, 'UTF-8') ?>"
                    <a href="<?= htmlspecialchars($buildUrl(['search' => '']), ENT_QUOTES, 'UTF-8') ?>" class="text-danger text-decoration-none ms-1">&times;</a>
                </span>
            <?php endif; ?>
            <?php if (!empty($filters['event_type'])): ?>
                <span class="badge bg-light text-dark border d-inline-flex align-items-center gap-1">
                    Typ: <?= htmlspecialchars($filters['event_type'], ENT_QUOTES, 'UTF-8') ?>
                    <a href="<?= htmlspecialchars($buildUrl(['event_type' => '']), ENT_QUOTES, 'UTF-8') ?>" class="text-danger text-decoration-none ms-1">&times;</a>
                </span>
            <?php endif; ?>
            <?php if (!empty($filters['category'])): ?>
                <span class="badge bg-light text-dark border d-inline-flex align-items-center gap-1">
                    Kategorie: <?= htmlspecialchars($filters['category'], ENT_QUOTES, 'UTF-8') ?>
                    <a href="<?= htmlspecialchars($buildUrl(['category' => '']), ENT_QUOTES, 'UTF-8') ?>" class="text-danger text-decoration-none ms-1">&times;</a>
                </span>
            <?php endif; ?>
            <?php if (!empty($filters['email'])): ?>
                <span class="badge bg-light text-dark border d-inline-flex align-items-center gap-1">
                    E-Mail: <?= htmlspecialchars($filters['email'], ENT_QUOTES, 'UTF-8') ?>
                    <a href="<?= htmlspecialchars($buildUrl(['email' => '']), ENT_QUOTES, 'UTF-8') ?>" class="text-danger text-decoration-none ms-1">&times;</a>
                </span>
            <?php endif; ?>
            <?php if (!empty($filters['case_id'])): ?>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle d-inline-flex align-items-center gap-1">
                    Sicherungsfall #<?= (int) $filters['case_id'] ?>
                    <a href="<?= htmlspecialchars($buildUrl(['case_id' => '']), ENT_QUOTES, 'UTF-8') ?>" class="text-danger text-decoration-none ms-1">&times;</a>
                </span>
            <?php endif; ?>
            <?php if (!empty($filters['user_id'])): ?>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle d-inline-flex align-items-center gap-1">
                    Benutzer #<?= (int) $filters['user_id'] ?>
                    <a href="<?= htmlspecialchars($buildUrl(['user_id' => '']), ENT_QUOTES, 'UTF-8') ?>" class="text-danger text-decoration-none ms-1">&times;</a>
                </span>
            <?php endif; ?>
            <?php if (!empty($filters['date_from']) || !empty($filters['date_to'])): ?>
                <span class="badge bg-light text-dark border d-inline-flex align-items-center gap-1">
                    Zeitraum: <?= htmlspecialchars($filters['date_from'] ?? '—', ENT_QUOTES, 'UTF-8') ?> bis <?= htmlspecialchars($filters['date_to'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                    <a href="<?= htmlspecialchars($buildUrl(['date_from' => '', 'date_to' => '']), ENT_QUOTES, 'UTF-8') ?>" class="text-danger text-decoration-none ms-1">&times;</a>
                </span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- 3. Haupt-Tabelle der Audit-Logs -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
            <h6 class="card-title mb-0 fw-semibold text-dark">
                Audit-Log Protokolleinträge
            </h6>
            <div class="text-muted small">
                <?php if ($totalCount > 0): ?>
                    Zeige <strong><?= number_format(min($totalCount, $offset + 1), 0, ',', '.') ?></strong> bis 
                    <strong><?= number_format(min($totalCount, $offset + count($logs)), 0, ',', '.') ?></strong> von 
                    <strong><?= number_format($totalCount, 0, ',', '.') ?></strong> Einträgen
                <?php else: ?>
                    0 Einträge
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if (empty($logs)): ?>
                <div class="text-center py-5">
                    <div class="text-muted mb-3">
                        <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                        <span class="fw-semibold">Keine Protokolleinträge gefunden.</span>
                    </div>
                    <?php if ($hasActiveFilters): ?>
                        <p class="text-muted small mb-3">Für die gewählten Filterkriterien liegen keine Einträge vor.</p>
                        <a href="?route=admin/activity" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-funnel me-1"></i> Alle Filter aufheben
                        </a>
                    <?php else: ?>
                        <p class="text-muted small mb-0">Im System wurden noch keine Audit-Log-Ereignisse protokolliert.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light text-secondary">
                            <tr>
                                <th class="ps-3" style="width: 170px;">Zeitstempel</th>
                                <th style="width: 240px;">Ereignis-Typ</th>
                                <th style="width: 200px;">Akteur / E-Mail</th>
                                <th style="width: 170px;">Client / IP</th>
                                <th>Details &amp; Kontext</th>
                                <th class="pe-3 text-end" style="width: 90px;">Aktion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): 
                                $meta = $log['meta'] ?? ActivityLogRepository::getEventMeta($log['event_type']);
                                $detailsData = $log['details_data'] ?? [];
                                $modalId = 'logModal_' . $log['id'];
                            ?>
                                <tr>
                                    <!-- Zeitstempel -->
                                    <td class="ps-3 text-nowrap">
                                        <div class="fw-semibold text-dark">
                                            <?= date('d.m.Y H:i:s', strtotime((string) $log['created_at'])) ?>
                                        </div>
                                        <small class="text-muted" style="font-size: 0.75rem;">
                                            <?= $formatRelativeTime((string) $log['created_at']) ?>
                                        </small>
                                    </td>

                                    <!-- Ereignis-Typ -->
                                    <td>
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <span class="badge <?= htmlspecialchars($meta['badge_class'], ENT_QUOTES, 'UTF-8') ?> d-inline-flex align-items-center gap-1 shadow-xs">
                                                <i class="bi <?= htmlspecialchars($meta['icon_class'], ENT_QUOTES, 'UTF-8') ?>"></i>
                                                <?= htmlspecialchars($meta['title'], ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </div>
                                        <div class="font-monospace text-muted" style="font-size: 0.75rem;">
                                            <?= htmlspecialchars($log['event_type'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    </td>

                                    <!-- Akteur / E-Mail -->
                                    <td>
                                        <?php if (!empty($log['email'])): ?>
                                            <div class="d-flex align-items-center gap-1 text-truncate" style="max-width: 190px;" title="<?= htmlspecialchars($log['email'], ENT_QUOTES, 'UTF-8') ?>">
                                                <i class="bi bi-person-circle text-secondary"></i>
                                                <a href="<?= htmlspecialchars($buildUrl(['email' => $log['email'], 'page' => 1]), ENT_QUOTES, 'UTF-8') ?>" class="text-dark text-decoration-none fw-semibold">
                                                    <?= htmlspecialchars($log['email'], ENT_QUOTES, 'UTF-8') ?>
                                                </a>
                                            </div>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border">System / Gast</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- IP & Client -->
                                    <td>
                                        <?php if (!empty($log['ip_address'])): ?>
                                            <div class="d-flex align-items-center gap-1 font-monospace text-secondary" style="font-size: 0.75rem;">
                                                <i class="bi bi-globe2 text-muted"></i>
                                                <span><?= htmlspecialchars($log['ip_address'], ENT_QUOTES, 'UTF-8') ?></span>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>

                                        <?php if (!empty($log['user_agent'])): 
                                            $shortUa = strlen($log['user_agent']) > 35 ? (substr($log['user_agent'], 0, 32) . '...') : $log['user_agent'];
                                        ?>
                                            <div class="text-muted text-truncate" style="font-size: 0.7rem; max-width: 150px;" title="<?= htmlspecialchars($log['user_agent'], ENT_QUOTES, 'UTF-8') ?>">
                                                <?= htmlspecialchars($shortUa, ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Details & Kontext -->
                                    <td>
                                        <?php if (!empty($detailsData)): ?>
                                            <div class="d-flex flex-wrap gap-1 align-items-center">
                                                <?php 
                                                $displayedKeys = 0;
                                                foreach ($detailsData as $k => $v): 
                                                    if ($displayedKeys >= 3) break;
                                                    if (is_array($v)) continue;
                                                    $valStr = (string) $v;
                                                    if (strlen($valStr) > 40) $valStr = substr($valStr, 0, 37) . '...';
                                                    $displayedKeys++;
                                                ?>
                                                    <span class="badge bg-light text-dark border" style="font-size: 0.75rem;">
                                                        <span class="text-muted"><?= htmlspecialchars((string) $k, ENT_QUOTES, 'UTF-8') ?>:</span>
                                                        <strong><?= htmlspecialchars($valStr, ENT_QUOTES, 'UTF-8') ?></strong>
                                                    </span>
                                                <?php endforeach; ?>

                                                <?php if (count($detailsData) > $displayedKeys): ?>
                                                    <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.7rem;">+<?= count($detailsData) - $displayedKeys ?> weitere</span>
                                                <?php endif; ?>

                                                <?php if (!empty($detailsData['case_id'])): ?>
                                                    <a href="?route=admin/secure/cases/view&id=<?= (int)$detailsData['case_id'] ?>" class="btn btn-outline-primary btn-sm py-0 px-1 ms-1" style="font-size: 0.7rem;" title="Direkt zum Vorgang">
                                                        <i class="bi bi-box-arrow-up-right"></i> Fall #<?= (int)$detailsData['case_id'] ?>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        <?php elseif (!empty($log['details'])): ?>
                                            <span class="text-muted" style="font-size: 0.8rem;"><?= htmlspecialchars(substr($log['details'], 0, 60), ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Detail-Aktion -->
                                    <td class="pe-3 text-end">
                                        <button type="button" class="btn btn-light btn-sm border py-0 px-2 shadow-xs" data-bs-toggle="modal" data-bs-target="#<?= $modalId ?>" title="Vollständiges Protokoll einsehen">
                                            <i class="bi bi-eye"></i>
                                        </button>

                                        <!-- Modal für Detailansicht -->
                                        <div class="modal fade text-start" id="<?= $modalId ?>" tabindex="-1" aria-labelledby="<?= $modalId ?>Label" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                                <div class="modal-content shadow">
                                                    <div class="modal-header">
                                                        <h6 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="<?= $modalId ?>Label">
                                                            <i class="bi <?= htmlspecialchars($meta['icon_class'], ENT_QUOTES, 'UTF-8') ?> text-primary"></i>
                                                            Ereignis-Details #<?= $log['id'] ?>: <?= htmlspecialchars($meta['title'], ENT_QUOTES, 'UTF-8') ?>
                                                        </h6>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                                                    </div>
                                                    <div class="modal-body p-4">
                                                        <!-- Stammdaten-Tabelle -->
                                                        <div class="row g-3 mb-4">
                                                            <div class="col-sm-6">
                                                                <div class="p-3 bg-light rounded border">
                                                                    <div class="text-muted small">Ereignis-Typ (Key)</div>
                                                                    <div class="font-monospace fw-bold text-dark"><?= htmlspecialchars($log['event_type'], ENT_QUOTES, 'UTF-8') ?></div>
                                                                    <div class="text-muted small mt-1"><?= htmlspecialchars($meta['description'], ENT_QUOTES, 'UTF-8') ?></div>
                                                                </div>
                                                            </div>
                                                            <div class="col-sm-6">
                                                                <div class="p-3 bg-light rounded border">
                                                                    <div class="text-muted small">Zeitpunkt</div>
                                                                    <div class="fw-bold text-dark"><?= date('d.m.Y H:i:s', strtotime((string)$log['created_at'])) ?></div>
                                                                    <div class="text-muted small mt-1"><?= $formatRelativeTime((string)$log['created_at']) ?></div>
                                                                </div>
                                                            </div>
                                                            <div class="col-sm-6">
                                                                <div class="p-3 bg-light rounded border">
                                                                    <div class="text-muted small">Akteur / E-Mail</div>
                                                                    <div class="fw-bold text-dark"><?= !empty($log['email']) ? htmlspecialchars($log['email'], ENT_QUOTES, 'UTF-8') : 'System / Automatisiert' ?></div>
                                                                </div>
                                                            </div>
                                                            <div class="col-sm-6">
                                                                <div class="p-3 bg-light rounded border">
                                                                    <div class="text-muted small">IP-Adresse &amp; Client</div>
                                                                    <div class="font-monospace text-dark"><?= htmlspecialchars($log['ip_address'] ?? 'Unbekannt', ENT_QUOTES, 'UTF-8') ?></div>
                                                                    <div class="text-muted text-truncate small" title="<?= htmlspecialchars($log['user_agent'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                                                        <?= htmlspecialchars($log['user_agent'] ?? 'Kein User-Agent übermittelt', ENT_QUOTES, 'UTF-8') ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- Kontext-Daten / Payload -->
                                                        <h6 class="fw-bold text-dark mb-2 d-flex align-items-center justify-content-between">
                                                            <span>Strukturierte Kontext-Parameter (Details)</span>
                                                            <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size: 0.75rem;" onclick="navigator.clipboard.writeText(document.getElementById('rawJson_<?= $log['id'] ?>').innerText)">
                                                                <i class="bi bi-clipboard me-1"></i> JSON kopieren
                                                            </button>
                                                        </h6>

                                                        <?php if (!empty($detailsData)): ?>
                                                            <div class="table-responsive border rounded mb-3">
                                                                <table class="table table-sm table-striped mb-0 small">
                                                                    <thead class="table-light">
                                                                        <tr>
                                                                            <th class="ps-3" style="width: 35%;">Schlüssel (Key)</th>
                                                                            <th class="pe-3">Wert (Value)</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php foreach ($detailsData as $k => $v): ?>
                                                                            <tr>
                                                                                <td class="ps-3 font-monospace text-secondary fw-semibold"><?= htmlspecialchars((string) $k, ENT_QUOTES, 'UTF-8') ?></td>
                                                                                <td class="pe-3 text-dark font-monospace" style="word-break: break-all;">
                                                                                    <?php if (is_array($v) || is_object($v)): ?>
                                                                                        <pre class="mb-0 bg-white p-2 rounded border small"><?= htmlspecialchars(json_encode($v, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?></pre>
                                                                                    <?php elseif (is_bool($v)): ?>
                                                                                        <span class="badge bg-secondary"><?= $v ? 'true' : 'false' ?></span>
                                                                                    <?php else: ?>
                                                                                        <?= htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8') ?>
                                                                                    <?php endif; ?>
                                                                                </td>
                                                                            </tr>
                                                                        <?php endforeach; ?>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        <?php endif; ?>

                                                        <!-- Roh-JSON Ansicht -->
                                                        <details class="mt-2">
                                                            <summary class="text-muted small cursor-pointer">Vollständigen Roh-Datensatz (JSON) anzeigen</summary>
                                                            <pre class="bg-dark text-light p-3 rounded mt-2 small font-monospace" id="rawJson_<?= $log['id'] ?>" style="max-height: 250px; overflow-y: auto;"><?= htmlspecialchars(json_encode([
                                                                'id'         => $log['id'],
                                                                'event_type' => $log['event_type'],
                                                                'email'      => $log['email'],
                                                                'ip_address' => $log['ip_address'],
                                                                'user_agent' => $log['user_agent'],
                                                                'created_at' => $log['created_at'],
                                                                'details'    => $detailsData,
                                                            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?></pre>
                                                        </details>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <?php if (!empty($detailsData['case_id'])): ?>
                                                            <a href="?route=admin/secure/cases/view&id=<?= (int)$detailsData['case_id'] ?>" class="btn btn-primary btn-sm">
                                                                <i class="bi bi-box-arrow-up-right me-1"></i> Sicherungsvorgang aufrufen
                                                            </a>
                                                        <?php endif; ?>
                                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Schließen</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- 4. Paginierung -->
                <?php if ($totalPages > 1): ?>
                    <div class="card-footer bg-white py-3 border-top d-flex flex-column flex-md-row align-items-center justify-content-between gap-3">
                        <div class="text-muted small">
                            Seite <strong><?= $page ?></strong> von <strong><?= $totalPages ?></strong>
                        </div>
                        <nav aria-label="Audit-Log Paginierung">
                            <ul class="pagination pagination-sm mb-0">
                                <!-- Erste Seite -->
                                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="<?= htmlspecialchars($buildUrl(['page' => 1]), ENT_QUOTES, 'UTF-8') ?>" aria-label="Erste">
                                        <i class="bi bi-chevron-double-left"></i>
                                    </a>
                                </li>
                                <!-- Vorherige Seite -->
                                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="<?= htmlspecialchars($buildUrl(['page' => $page - 1]), ENT_QUOTES, 'UTF-8') ?>" aria-label="Vorherige">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>

                                <!-- Dynamische Seitenzahlen -->
                                <?php
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);
                                for ($p = $startPage; $p <= $endPage; $p++):
                                ?>
                                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="<?= htmlspecialchars($buildUrl(['page' => $p]), ENT_QUOTES, 'UTF-8') ?>"><?= $p ?></a>
                                    </li>
                                <?php endfor; ?>

                                <!-- Nächste Seite -->
                                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                    <a class="page-link" href="<?= htmlspecialchars($buildUrl(['page' => $page + 1]), ENT_QUOTES, 'UTF-8') ?>" aria-label="Nächste">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                                <!-- Letzte Seite -->
                                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                    <a class="page-link" href="<?= htmlspecialchars($buildUrl(['page' => $totalPages]), ENT_QUOTES, 'UTF-8') ?>" aria-label="Letzte">
                                        <i class="bi bi-chevron-double-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

<?php elseif ($activeTab === 'logins'): ?>
    <!-- TAB 2: Letzte Benutzer-Logins -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="card-title mb-0 fw-semibold text-dark">Erfolgreiche Benutzer-Anmeldungen</h6>
        </div>
        <div class="card-body p-0">
            <?php if (empty($recentLogins)): ?>
                <div class="text-muted small p-4 text-center">Keine Benutzer-Login-Einträge vorhanden.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Benutzer-ID</th>
                                <th>Name</th>
                                <th>E-Mail</th>
                                <th class="pe-3 text-end">Letzter Login</th>
                                <th class="pe-3 text-end">Aktion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentLogins as $l): ?>
                                <tr>
                                    <td class="ps-3 font-monospace text-muted">#<?= (int)$l['id'] ?></td>
                                    <td class="fw-semibold text-dark"><?= htmlspecialchars((string) ($l['name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-secondary"><?= htmlspecialchars((string) $l['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="pe-3 text-end text-muted font-monospace"><?= htmlspecialchars((string) $l['last_login_at'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="pe-3 text-end">
                                        <a href="?route=admin/activity&email=<?= urlencode((string)$l['email']) ?>" class="btn btn-outline-primary btn-sm py-0 px-2" style="font-size: 0.75rem;">
                                            <i class="bi bi-filter me-1"></i> Logs filtern
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

<?php elseif ($activeTab === 'magic'): ?>
    <!-- TAB 3: Letzte Magic-Codes -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="card-title mb-0 fw-semibold text-dark">Letzte Magic-Code-Generierungen &amp; -Nutzungen</h6>
        </div>
        <div class="card-body p-0">
            <?php if (empty($recentCodes)): ?>
                <div class="text-muted small p-4 text-center">Keine Magic-Code-Einträge vorhanden.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">ID</th>
                                <th>Empfänger E-Mail</th>
                                <th>Verwendungszweck</th>
                                <th>Nutzungsanzahl</th>
                                <th>Zuletzt genutzt</th>
                                <th class="pe-3 text-end">Erstellt am</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentCodes as $c): ?>
                                <tr>
                                    <td class="ps-3 font-monospace text-muted">#<?= (int)$c['id'] ?></td>
                                    <td class="fw-semibold text-dark"><?= htmlspecialchars((string) $c['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars((string) $c['usage_type'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td><?= (int) ($c['used_count'] ?? 0) ?></td>
                                    <td class="text-muted"><?= !empty($c['used_at']) ? htmlspecialchars((string) $c['used_at'], ENT_QUOTES, 'UTF-8') : 'Noch ungenutzt' ?></td>
                                    <td class="pe-3 text-end text-muted font-monospace"><?= htmlspecialchars((string) $c['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin.php';
