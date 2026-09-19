<?php
declare(strict_types=1);

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$title = 'Aktivität & Logs';
$currentRoute = 'admin/activity';

// Letzte Logins und Magic-Code-Aktivitäten
$recentLogins = [];
try {
    $recentLogins = DB::fetchAll('SELECT `id`, `name`, `email`, `last_login_at` FROM `users` WHERE `last_login_at` IS NOT NULL ORDER BY `last_login_at` DESC LIMIT 10');
} catch (\Throwable $e) {
    // DB not available or empty
}

$recentCodes = [];
try {
    $recentCodes = DB::fetchAll('SELECT `id`, `email`, `usage_type`, `used_count`, `used_at`, `created_at` FROM `magic_codes` ORDER BY `created_at` DESC LIMIT 10');
} catch (\Throwable $e) {
    // DB not available or empty
}

ob_start();
?>

<div class="mb-4">
    <p class="text-muted mb-0">Systemprotokolle, Anmeldehistorie und Sicherheitsereignisse.</p>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-semibold text-dark">Letzte Benutzer-Logins</h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentLogins)): ?>
                    <p class="text-muted small p-3 mb-0">Keine Login-Einträge vorhanden.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Benutzer</th>
                                    <th>E-Mail</th>
                                    <th class="pe-3 text-end">Zeitpunkt</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentLogins as $l): ?>
                                    <tr>
                                        <td class="ps-3 fw-semibold"><?= htmlspecialchars((string) ($l['name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-secondary"><?= htmlspecialchars((string) $l['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="pe-3 text-end text-muted"><?= htmlspecialchars((string) $l['last_login_at'], ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h6 class="card-title mb-0 fw-semibold text-dark">Letzte Magic-Code-Aktivitäten</h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentCodes)): ?>
                    <p class="text-muted small p-3 mb-0">Keine Magic-Code-Einträge vorhanden.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">E-Mail</th>
                                    <th>Typ</th>
                                    <th class="pe-3 text-end">Erstellt</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentCodes as $c): ?>
                                    <tr>
                                        <td class="ps-3 fw-semibold"><?= htmlspecialchars((string) $c['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars((string) $c['usage_type'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                        <td class="pe-3 text-end text-muted"><?= htmlspecialchars((string) $c['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin.php';
