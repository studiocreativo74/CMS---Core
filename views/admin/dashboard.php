<?php
declare(strict_types=1);

$user = $user ?? Auth::user();
$magicCodes = $magicCodes ?? MagicCode::getAll();
$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashCreatedCode = $_SESSION['flash_created_code'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;

// Flash-Nachrichten nach dem Auslesen leeren
unset($_SESSION['flash_success'], $_SESSION['flash_created_code'], $_SESSION['flash_error']);

// Titel für das Admin-Layout festlegen
$title = 'Dashboard';

// Kennzahlen berechnen
$totalCount = count($magicCodes);
$activeCount = 0;
$usedCount = 0;
$expiredCount = 0;

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

// Seiteninhalt via Output-Buffering erfassen
ob_start();
?>

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

<!-- Statistik-Kacheln -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Alle Codes</span>
                    <span class="fs-4 fw-bold text-dark"><?= $totalCount ?></span>
                </div>
                <div class="p-2 bg-primary bg-opacity-10 text-primary rounded">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" class="bi bi-key-fill" viewBox="0 0 16 16">
                        <path d="M3.5 11.5a3.5 3.5 0 1 1 3.163-5H14L15.5 8 14 9.5l-1-1-1 1-1-1-1 1-1.5-1.5H6.663a3.5 3.5 0 0 1-3.163 3.5M2.5 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Aktiv &amp; Nutzbar</span>
                    <span class="fs-4 fw-bold text-success"><?= $activeCount ?></span>
                </div>
                <div class="p-2 bg-success bg-opacity-10 text-success rounded">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" class="bi bi-check-circle-fill" viewBox="0 0 16 16">
                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Voll genutzt</span>
                    <span class="fs-4 fw-bold text-secondary"><?= $usedCount ?></span>
                </div>
                <div class="p-2 bg-secondary bg-opacity-10 text-secondary rounded">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" class="bi bi-check2-all" viewBox="0 0 16 16">
                        <path d="M12.354 4.354a.5.5 0 0 0-.708-.708L5 10.293 1.854 7.146a.5.5 0 1 0-.708.708l3.5 3.5a.5.5 0 0 0 .708 0zm-4.208 7-.896-.897.707-.707.543.543 6.646-6.647a.5.5 0 0 1 .708.708l-7 7a.5.5 0 0 1-.708 0"/>
                        <path d="m5.354 7.146.896.897-.707.707-.897-.896a.5.5 0 1 1 .708-.708"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Abgelaufen</span>
                    <span class="fs-4 fw-bold text-warning"><?= $expiredCount ?></span>
                </div>
                <div class="p-2 bg-warning bg-opacity-10 text-warning rounded">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" class="bi bi-clock-history" viewBox="0 0 16 16">
                        <path d="M8.515 1.019A7 7 0 0 0 8 1V0a8 8 0 0 1 .589.022zm2.004.45a7 7 0 0 0-.985-.299l.219-.976q.576.129 1.126.342zm1.37.71a7 7 0 0 0-.439-.27l.493-.87a8 8 0 0 1 .979.654l-.615.789a7 7 0 0 0-.418-.302zm1.834 1.79a7 7 0 0 0-.653-.796l.724-.69q.406.429.747.91zm.744 1.352a7 7 0 0 0-.214-.468l.893-.45a8 8 0 0 1 .45 1.088l-.95.313a7 7 0 0 0-.179-.483m.53 2.507a7 7 0 0 0-.1-1.025l.985-.17q.1.58.116 1.17zm-.131 1.538q.05-.254.084-.51l.995.106a8 8 0 0 1-.119.764zm-.759 2.075q.083-.179.155-.364l.93.374a8 8 0 0 1-.36 1.058zm-1.127 1.691q.168-.17.314-.355l.8.6a8 8 0 0 1-.741.977zm-1.572 1.285q.228-.11.438-.238l.6.8a8 8 0 0 1-1.024.582zm-2.072.751q.255-.05.508-.122l.3.954a8 8 0 0 1-1.18.257z"/>
                        <path d="M8 4.5a.5.5 0 0 1 .5.5v3.25l2.25 1.35a.5.5 0 1 1-.515.858l-2.5-1.5A.5.5 0 0 1 8 8.5V5a.5.5 0 0 1 .5-.5"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Formular: Neuen Magic-Code erstellen -->
    <div class="col-lg-5 col-xl-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 fw-bold text-dark">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-plus-circle-fill text-primary me-2" viewBox="0 0 16 16">
                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M8.5 4.5a.5.5 0 0 0-1 0v3h-3a.5.5 0 0 0 0 1h3v3a.5.5 0 0 0 1 0v-3h3a.5.5 0 0 0 0-1h-3z"/>
                    </svg>
                    Neuen Code erstellen
                </h5>
            </div>
            <div class="card-body p-4">
                <form method="post" action="?route=admin/magic-codes/create">
                    <?= Csrf::input() ?>

                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold text-secondary">Empfänger E-Mail:</label>
                        <input type="email" class="form-control" id="email" name="email" value="office@studiocreativo.ch" maxlength="191" required>
                    </div>

                    <div class="mb-3">
                        <label for="usage_type" class="form-label fw-semibold text-secondary">Verwendungszweck:</label>
                        <select class="form-select" id="usage_type" name="usage_type">
                            <option value="admin_login">admin_login (Admin-Dashboard)</option>
                            <option value="frontend">frontend (Frontend-Zugang)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="max_uses" class="form-label fw-semibold text-secondary">Maximale Nutzungen:</label>
                        <input type="number" class="form-control" id="max_uses" name="max_uses" value="1" min="1" max="999" required>
                    </div>

                    <div class="mb-4">
                        <label for="expires_at" class="form-label fw-semibold text-secondary">Ablaufdatum (optional):</label>
                        <input type="datetime-local" class="form-control" id="expires_at" name="expires_at">
                        <div class="form-text">Leer lassen, falls der Code unbegrenzt gültig sein soll.</div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                        Magic-Code generieren &amp; versenden
                    </button>
                    <div class="form-text text-center mt-2 small">
                        Kopie wird automatisch an <strong>office@studiocreativo.ch</strong> gesendet.
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Tabelle: Vorhandene Magic-Codes (Übersicht) -->
    <div class="col-lg-7 col-xl-8">
        <div class="card shadow-sm border-0 h-100" id="magic-codes-card">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <h5 class="card-title mb-0 fw-bold text-dark me-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-shield-lock-fill text-secondary me-2" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M8 0c-.69 0-1.843.265-2.928.56-1.11.3-2.229.655-2.887.87a1.54 1.54 0 0 0-1.044 1.262c-.596 4.477.787 7.795 2.465 9.99a11.8 11.8 0 0 0 2.517 2.453c.386.273.744.482 1.048.625.28.132.581.24.829.24s.548-.108.829-.24a7 7 0 0 0 1.048-.625 11.8 11.8 0 0 0 2.517-2.453c1.678-2.195 3.061-5.513 2.465-9.99a1.54 1.54 0 0 0-1.044-1.263 63 63 0 0 0-2.887-.87C9.843.266 8.69 0 8 0m0 5a1.5 1.5 0 0 1 .5 2.915l.385 1.99a.5.5 0 0 1-.491.595h-.788a.5.5 0 0 1-.49-.595l.384-1.99A1.5 1.5 0 0 1 8 5"/>
                        </svg>
                        Aktuelle Codes
                    </h5>
                    <span class="badge bg-secondary rounded-pill px-2 py-1">
                        <?= count($magicCodes) ?>
                    </span>
                </div>
                <a href="?route=admin/magic-codes" class="btn btn-sm btn-outline-primary fw-semibold">
                    Vollständige Verwaltung &rarr;
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($magicCodes)): ?>
                    <div class="p-4 text-center text-muted">
                        <p class="mb-0">Noch keine Magic-Codes in der Datenbank hinterlegt.</p>
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
                                    $usedCount = (int) ($row['used_count'] ?? 0);
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
                                        <span class="fw-medium"><?= $usedCount ?></span> / <span class="text-muted"><?= $maxUses ?></span>
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
                                Alle <?= count($magicCodes) ?> Codes mit Filtern und Verwaltungs-Aktionen anzeigen &rarr;
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Zentrales Layout einbinden
require __DIR__ . '/../layouts/admin.php';
