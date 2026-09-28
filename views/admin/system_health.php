<?php

declare(strict_types=1);

/**
 * Admin: Systemstatus- & Health-Check Ansicht (/admin/system/health)
 *
 * Zeigt den Ampelstatus (OK / Warnung / Fehler) für:
 * - Datenbankverbindung
 * - Upload-Verzeichnisse & Schreibrechte
 * - E-Mail-Konfiguration mit manuellem Testversand
 * - SharePoint / Microsoft Graph Anbindung mit manuellem OAuth-Token-Test
 *
 * @var array<string, mixed> $healthResult
 * @var array<string, mixed>|null $testMailResult
 * @var array<string, mixed>|null $tokenTestResult
 */

$title = 'Systemstatus & Health-Check';
$currentRoute = 'admin/system/health';
$activeNav = 'admin/system';

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError   = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$csrfToken = class_exists('Csrf') ? Csrf::getToken() : '';

$overall = $healthResult['overall'] ?? 'ok';
$overallBadge = $healthResult['overall_badge'] ?? 'bg-success';
$overallLabel = $healthResult['overall_label'] ?? 'Alle Systeme betriebsbereit';
$timestamp = $healthResult['timestamp'] ?? date('d.m.Y H:i:s');

$checks = $healthResult['checks'] ?? [];
$dbCheck = $checks['database'] ?? [];
$uploadCheck = $checks['uploads'] ?? [];
$mailCheck = $checks['mail'] ?? [];
$spCheck = $checks['sharepoint'] ?? [];

// Falls ein direkter Token-Test oder Test-Mail-Ergebnis vorliegt, überschreiben wir die Ansicht
if (!empty($testMailResult)) {
    $mailCheck['status'] = $testMailResult['status'] ?? $mailCheck['status'];
    $mailCheck['badge']  = $testMailResult['badge'] ?? $mailCheck['badge'];
    $mailCheck['message'] = $testMailResult['message'] ?? $mailCheck['message'];
    $mailCheck['test_result'] = $testMailResult['test_result'] ?? null;
}

if (!empty($tokenTestResult)) {
    $spCheck['status'] = $tokenTestResult['status'] ?? $spCheck['status'];
    $spCheck['badge']  = $tokenTestResult['badge'] ?? $spCheck['badge'];
    $spCheck['message'] = $tokenTestResult['message'] ?? $spCheck['message'];
    $spCheck['token_result'] = $tokenTestResult['token_result'] ?? null;
}

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

    <!-- Kopfbereich mit Gesamtzustand & Aktionen -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="?route=admin/system" class="btn btn-outline-secondary btn-sm">
                    &larr; System &amp; Migrationen
                </a>
                <span class="badge <?= $overallBadge ?> fs-6 px-3 py-1">
                    <?php if ($overall === 'ok'): ?>
                        <i class="bi bi-check-circle-fill me-1"></i>
                    <?php elseif ($overall === 'warning'): ?>
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    <?php else: ?>
                        <i class="bi bi-x-circle-fill me-1"></i>
                    <?php endif; ?>
                    <?= htmlspecialchars($overallLabel, ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>
            <h1 class="h3 mb-0 fw-bold text-dark">Systemstatus &amp; Health-Check</h1>
            <small class="text-muted">
                Automatische Integritätsprüfung aller Core-Dienste &middot; Letzte Prüfung: <strong><?= htmlspecialchars($timestamp, ENT_QUOTES, 'UTF-8') ?></strong>
            </small>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="?route=admin/system/health" class="btn btn-primary d-inline-flex align-items-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-clockwise me-2" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2z"/>
                    <path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466"/>
                </svg>
                Alle Checks neu ausführen
            </a>
            <a href="?route=admin/secure/settings" class="btn btn-outline-dark d-inline-flex align-items-center" title="Sicherungsportal Einstellungen">
                Portal-Einstellungen
            </a>
        </div>
    </div>

    <!-- 4 Haupt-Prüfungsbereiche (Cards) -->
    <div class="row g-4 mb-4">

        <!-- 1. CHECK: DATENBANK -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <div class="p-2 rounded <?= ($dbCheck['status'] ?? '') === 'ok' ? 'bg-success-subtle text-success' : (($dbCheck['status'] ?? '') === 'warning' ? 'bg-warning-subtle text-warning-emphasis' : 'bg-danger-subtle text-danger') ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-database-check" viewBox="0 0 16 16">
                                <path d="M12.5 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7m1.679-4.493-1.335 2.226a.5.5 0 0 1-.864.004l-.668-1.114a.5.5 0 1 1 .858-.514l.237.394 1.054-1.755a.5.5 0 0 1 .718.761"/>
                                <path d="M12.096 6.223A5 5 0 0 0 13 5.698V7c0 .802-.697 1.488-1.897 1.838a6 6 0 0 1-.954-.606A5.5 5.5 0 0 0 11.5 7.5a5.5 5.5 0 0 0-1.127.116C9.176 7.822 8 8.444 8 9.5c0 .351.134.675.378.96-.346.035-.705.056-1.078.056C3.582 10.5 0 9.157 0 7.5V9c0 1.657 3.582 3 7.3 3 .026 0 .052-.001.077-.003.04.382.158.742.342 1.063C7.54 13.048 7.42 13.05 7.3 13.05 3.582 13.05 0 11.707 0 10.05v1.5C0 13.207 3.582 14.55 7.3 14.55c.183 0 .362-.004.538-.013A4 4 0 0 0 9 16c-4.418 0-8-1.567-8-3.5V4c0-1.933 3.582-3.5 8-3.5s8 1.567 8 3.5v2.096A5 5 0 0 0 12.096 6.223M15 4c0-.986-3.03-2-7-2S1 3.014 1 4s3.03 2 7 2 7-1.014 7-2"/>
                            </svg>
                        </div>
                        <div>
                            <h5 class="card-title mb-0 fw-bold text-dark"><?= htmlspecialchars((string) ($dbCheck['label'] ?? 'Datenbank'), ENT_QUOTES, 'UTF-8') ?></h5>
                            <small class="text-muted">Konnektivität, Zeichensatz &amp; Tabellenbestand</small>
                        </div>
                    </div>
                    <span class="badge <?= htmlspecialchars((string) ($dbCheck['badge'] ?? 'bg-secondary'), ENT_QUOTES, 'UTF-8') ?> px-2 py-1">
                        <?= strtoupper((string) ($dbCheck['status'] ?? 'unknown')) ?>
                    </span>
                </div>
                <div class="card-body p-4">
                    <div class="alert <?= ($dbCheck['status'] ?? '') === 'ok' ? 'alert-success border-success-subtle' : (($dbCheck['status'] ?? '') === 'warning' ? 'alert-warning border-warning-subtle' : 'alert-danger border-danger-subtle') ?> py-2 px-3 mb-3 small">
                        <strong>Status:</strong> <?= htmlspecialchars((string) ($dbCheck['message'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                    </div>

                    <?php if (!empty($dbCheck['details'])): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-0 small">
                                <tbody>
                                    <?php foreach ($dbCheck['details'] as $k => $v): ?>
                                        <tr>
                                            <th class="table-light text-muted w-40"><?= htmlspecialchars((string) $k, ENT_QUOTES, 'UTF-8') ?></th>
                                            <td class="font-monospace text-dark text-break"><?= htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 2. CHECK: UPLOADS & DATEISYSTEM -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <div class="p-2 rounded <?= ($uploadCheck['status'] ?? '') === 'ok' ? 'bg-success-subtle text-success' : (($uploadCheck['status'] ?? '') === 'warning' ? 'bg-warning-subtle text-warning-emphasis' : 'bg-danger-subtle text-danger') ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-folder-check" viewBox="0 0 16 16">
                                <path d="m.5 3 .04.87a2 2 0 0 0-.342 1.311l.637 7A2 2 0 0 0 2.826 14H9v-1H2.826a1 1 0 0 1-.995-.91l-.637-7A1 1 0 0 1 2.19 4h11.62a1 1 0 0 1 .996 1.09L14.54 8h1.005l.256-2.819A2 2 0 0 0 13.81 3H9.828a2 2 0 0 1-1.414-.586l-.828-.828A2 2 0 0 0 6.172 1H2.5a2 2 0 0 0-2 2m5.672-1a1 1 0 0 1 .707.293L7.586 3H2.19c-.523 0-.974.39-1.02.913l-.04-.87A1 1 0 0 1 2.19 2z"/>
                                <path d="M15.854 10.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 0 1 .708-.708l1.146 1.147 2.646-2.647a.5.5 0 0 1 .708 0"/>
                            </svg>
                        </div>
                        <div>
                            <h5 class="card-title mb-0 fw-bold text-dark"><?= htmlspecialchars((string) ($uploadCheck['label'] ?? 'Upload-Verzeichnisse'), ENT_QUOTES, 'UTF-8') ?></h5>
                            <small class="text-muted">Schreibrechte, Verzeichnisexistenz &amp; Speicherkapazität</small>
                        </div>
                    </div>
                    <span class="badge <?= htmlspecialchars((string) ($uploadCheck['badge'] ?? 'bg-secondary'), ENT_QUOTES, 'UTF-8') ?> px-2 py-1">
                        <?= strtoupper((string) ($uploadCheck['status'] ?? 'unknown')) ?>
                    </span>
                </div>
                <div class="card-body p-4">
                    <div class="alert <?= ($uploadCheck['status'] ?? '') === 'ok' ? 'alert-success border-success-subtle' : (($uploadCheck['status'] ?? '') === 'warning' ? 'alert-warning border-warning-subtle' : 'alert-danger border-danger-subtle') ?> py-2 px-3 mb-3 small">
                        <strong>Status:</strong> <?= htmlspecialchars((string) ($uploadCheck['message'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                    </div>

                    <?php if (!empty($uploadCheck['details'])): ?>
                        <div class="d-flex align-items-center justify-content-between mb-3 p-2 bg-light rounded border small">
                            <span><strong>Freier Speicher:</strong> <?= htmlspecialchars((string) ($uploadCheck['details']['Freier Speicherplatz'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="text-muted">Max Upload: <?= htmlspecialchars((string) ($uploadCheck['details']['Upload Max Filesize'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($uploadCheck['directories'])): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0 small">
                                <thead class="table-light">
                                    <tr>
                                        <th>Verzeichnis</th>
                                        <th>Status</th>
                                        <th>Rechte</th>
                                        <th>Schreibtest</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($uploadCheck['directories'] as $d): ?>
                                        <tr>
                                            <td>
                                                <strong class="text-dark d-block"><?= htmlspecialchars((string) $d['label'], ENT_QUOTES, 'UTF-8') ?></strong>
                                                <small class="text-muted font-monospace text-truncate d-block" style="max-width: 250px;" title="<?= htmlspecialchars((string) $d['path'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <?= htmlspecialchars((string) $d['path'], ENT_QUOTES, 'UTF-8') ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php if (!empty($d['exists'])): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Vorhanden</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Fehlt</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="font-monospace text-muted"><?= htmlspecialchars((string) $d['permissions'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td>
                                                <?php if (!empty($d['is_writable'])): ?>
                                                    <span class="badge bg-success text-white">Beschreibbar</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger text-white">Gesperrt</span>
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
        </div>

        <!-- 3. CHECK: E-MAIL SYSTEM & TEST-VERSAND -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <div class="p-2 rounded <?= ($mailCheck['status'] ?? '') === 'ok' ? 'bg-success-subtle text-success' : (($mailCheck['status'] ?? '') === 'warning' ? 'bg-warning-subtle text-warning-emphasis' : 'bg-danger-subtle text-danger') ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-envelope-check-fill" viewBox="0 0 16 16">
                                <path d="M.05 3.555A2 2 0 0 1 2 2h12a2 2 0 0 1 1.95 1.555L8 8.414zM0 4.697v7.104l5.803-3.558zM6.761 8.83l-6.57 4.027A2 2 0 0 0 2 14h12a2 2 0 0 0 1.808-1.144l-6.57-4.027L8 9.586zm3.436-.561L16 11.801V4.697z"/>
                                <path d="M16 12.5a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0m-1.993-1.679a.5.5 0 0 0-.686.172l-1.17 1.95-.549-.549a.5.5 0 1 0-.708.708l1 1a.5.5 0 0 0 .76-.041l1.5-2.5a.5.5 0 0 0-.147-.74"/>
                            </svg>
                        </div>
                        <div>
                            <h5 class="card-title mb-0 fw-bold text-dark"><?= htmlspecialchars((string) ($mailCheck['label'] ?? 'E-Mail-Konfiguration'), ENT_QUOTES, 'UTF-8') ?></h5>
                            <small class="text-muted">Absender, PHP mail() &amp; Zustellbarkeitstest</small>
                        </div>
                    </div>
                    <span class="badge <?= htmlspecialchars((string) ($mailCheck['badge'] ?? 'bg-secondary'), ENT_QUOTES, 'UTF-8') ?> px-2 py-1">
                        <?= strtoupper((string) ($mailCheck['status'] ?? 'unknown')) ?>
                    </span>
                </div>
                <div class="card-body p-4">
                    <div class="alert <?= ($mailCheck['status'] ?? '') === 'ok' ? 'alert-success border-success-subtle' : (($mailCheck['status'] ?? '') === 'warning' ? 'alert-warning border-warning-subtle' : 'alert-danger border-danger-subtle') ?> py-2 px-3 mb-3 small">
                        <strong>Status:</strong> <?= htmlspecialchars((string) ($mailCheck['message'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                    </div>

                    <?php if (!empty($mailCheck['details'])): ?>
                        <div class="table-responsive mb-3">
                            <table class="table table-sm table-bordered align-middle mb-0 small">
                                <tbody>
                                    <?php foreach ($mailCheck['details'] as $k => $v): ?>
                                        <tr>
                                            <th class="table-light text-muted w-40"><?= htmlspecialchars((string) $k, ENT_QUOTES, 'UTF-8') ?></th>
                                            <td class="font-monospace text-dark text-break"><?= htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <!-- Interaktives Formular: Test-E-Mail senden -->
                    <div class="p-3 bg-light rounded border">
                        <label class="form-label small fw-bold text-dark mb-1">
                            <i class="bi bi-send-fill text-primary me-1"></i> Test-E-Mail versenden
                        </label>
                        <form method="POST" action="?route=admin/system/health/test-mail" class="m-0">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <div class="input-group input-group-sm mb-2">
                                <span class="input-group-text bg-white">@</span>
                                <input type="email" 
                                       name="test_email" 
                                       class="form-control form-control-sm" 
                                       placeholder="test@example.com" 
                                       value="<?= htmlspecialchars((string) ($mailCheck['details']['Absender-Adresse'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" 
                                       required>
                                <button type="submit" class="btn btn-primary btn-sm px-3 fw-semibold">
                                    Test-Mail absenden
                                </button>
                            </div>
                            <div class="form-text small text-muted">
                                Versendet eine verifizierte Test-E-Mail über den konfigurierten Absender zur Überprüfung der Server-Zustellung.
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. CHECK: SHAREPOINT / MICROSOFT GRAPH -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <div class="p-2 rounded <?= ($spCheck['status'] ?? '') === 'ok' ? 'bg-success-subtle text-success' : (($spCheck['status'] ?? '') === 'warning' ? 'bg-warning-subtle text-warning-emphasis' : 'bg-danger-subtle text-danger') ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-microsoft" viewBox="0 0 16 16">
                                <path d="M7.462 0H0v7.19h7.462zM16 0H8.538v7.19H16zM7.462 8.811H0V16h7.462zm8.538 0H8.538V16H16z"/>
                            </svg>
                        </div>
                        <div>
                            <h5 class="card-title mb-0 fw-bold text-dark"><?= htmlspecialchars((string) ($spCheck['label'] ?? 'SharePoint & Graph'), ENT_QUOTES, 'UTF-8') ?></h5>
                            <small class="text-muted">Microsoft Entra ID, OAuth-Token &amp; Drive-Anbindung</small>
                        </div>
                    </div>
                    <span class="badge <?= htmlspecialchars((string) ($spCheck['badge'] ?? 'bg-secondary'), ENT_QUOTES, 'UTF-8') ?> px-2 py-1">
                        <?= strtoupper((string) ($spCheck['status'] ?? 'unknown')) ?>
                    </span>
                </div>
                <div class="card-body p-4">
                    <div class="alert <?= ($spCheck['status'] ?? '') === 'ok' ? 'alert-success border-success-subtle' : (($spCheck['status'] ?? '') === 'warning' ? 'alert-warning border-warning-subtle' : 'alert-danger border-danger-subtle') ?> py-2 px-3 mb-3 small">
                        <strong>Status:</strong> <?= htmlspecialchars((string) ($spCheck['message'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                    </div>

                    <?php if (!empty($spCheck['details'])): ?>
                        <div class="table-responsive mb-3">
                            <table class="table table-sm table-bordered align-middle mb-0 small">
                                <tbody>
                                    <?php foreach ($spCheck['details'] as $k => $v): ?>
                                        <tr>
                                            <th class="table-light text-muted w-40"><?= htmlspecialchars((string) $k, ENT_QUOTES, 'UTF-8') ?></th>
                                            <td class="font-monospace text-dark text-break"><?= htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <!-- Interaktives Formular: SharePoint Token-Test starten -->
                    <div class="p-3 bg-light rounded border">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div>
                                <strong class="d-block text-dark small">Microsoft Graph Token-Test</strong>
                                <small class="text-muted">
                                    Fragt live einen OAuth 2.0 Access-Token bei Microsoft Entra ID an.
                                </small>
                            </div>
                            <form method="POST" action="?route=admin/system/health/test-sharepoint" class="m-0">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="btn btn-outline-primary btn-sm px-3 fw-semibold shadow-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-play-circle me-1" viewBox="0 0 16 16">
                                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                                        <path d="M6.271 5.055a.5.5 0 0 1 .52.038l3.5 2.5a.5.5 0 0 1 0 .814l-3.5 2.5A.5.5 0 0 1 6 10.5v-5a.5.5 0 0 1 .271-.445"/>
                                    </svg>
                                    Token-Test jetzt starten
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin.php';
