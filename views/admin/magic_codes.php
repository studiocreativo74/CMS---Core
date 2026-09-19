<?php
declare(strict_types=1);

/**
 * Vollständige Magic-Code-Verwaltung im Admin-Bereich
 */

$user = $user ?? Auth::user();
$filters = $filters ?? [];
$magicCodes = $magicCodes ?? [];

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashCreatedCode = $_SESSION['flash_created_code'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;

// Flash-Nachrichten nach dem Auslesen leeren
unset($_SESSION['flash_success'], $_SESSION['flash_created_code'], $_SESSION['flash_error']);

$title = 'Magic-Code-Verwaltung';
$currentRoute = 'admin/magic-codes';

// Aktive Filter-Werte für Formular-Vorbelegung
$filterEmail = (string) ($filters['email'] ?? '');
$filterStatus = (string) ($filters['status'] ?? 'all');
$filterUsageType = (string) ($filters['usage_type'] ?? 'all');
$filterDateFrom = (string) ($filters['date_from'] ?? '');
$filterDateTo = (string) ($filters['date_to'] ?? '');

$isFiltered = ($filterEmail !== '' || $filterStatus !== 'all' || $filterUsageType !== 'all' || $filterDateFrom !== '' || $filterDateTo !== '');

ob_start();
?>

<!-- Flash-Meldungen -->
<?php if ($flashSuccess): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-start">
            <div class="me-2 pt-1">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-check-circle-fill text-success" viewBox="0 0 16 16">
                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                </svg>
            </div>
            <div>
                <p class="mb-1 fw-semibold"><?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?></p>
                <?php if ($flashCreatedCode): ?>
                    <div class="mt-2 p-2 bg-white border border-success rounded d-inline-block shadow-sm">
                        <small class="text-muted d-block fw-semibold">Generierter Klartext-Code (wird nur einmalig angezeigt):</small>
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
            <div class="fw-medium"><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>
    </div>
<?php endif; ?>

<!-- Aktions- und Filterbereich -->
<div class="row g-4 mb-4">
    <!-- Formular: Neuen Magic-Code erstellen (Akkordeon/Karte) -->
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-plus-circle-fill text-primary me-2" viewBox="0 0 16 16">
                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M8.5 4.5a.5.5 0 0 0-1 0v3h-3a.5.5 0 0 0 0 1h3v3a.5.5 0 0 0 1 0v-3h3a.5.5 0 0 0 0-1h-3z"/>
                    </svg>
                    <h5 class="card-title mb-0 fw-bold text-dark">Neuen Magic-Code generieren</h5>
                </div>
                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#createCodeFormCollapse" aria-expanded="true" aria-controls="createCodeFormCollapse">
                    Formular ein-/ausblenden
                </button>
            </div>
            <div class="collapse show" id="createCodeFormCollapse">
                <div class="card-body p-4 bg-light border-top">
                    <form method="post" action="?route=admin/magic-codes/create" class="row g-3">
                        <?= Csrf::input() ?>

                        <div class="col-md-4">
                            <label for="create_email" class="form-label fw-semibold text-secondary small">Empfänger E-Mail *</label>
                            <input type="email" class="form-control" id="create_email" name="email" value="office@studiocreativo.ch" maxlength="191" required placeholder="z.B. user@example.ch">
                        </div>

                        <div class="col-md-3">
                            <label for="create_usage_type" class="form-label fw-semibold text-secondary small">Verwendungszweck</label>
                            <select class="form-select" id="create_usage_type" name="usage_type">
                                <option value="admin_login" selected>admin_login (Admin-Zugang)</option>
                                <option value="frontend">frontend (Kunden-Bereich)</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label for="create_max_uses" class="form-label fw-semibold text-secondary small">Max. Nutzungen *</label>
                            <input type="number" class="form-control" id="create_max_uses" name="max_uses" value="1" min="1" max="999" required>
                        </div>

                        <div class="col-md-3">
                            <label for="create_expires_at" class="form-label fw-semibold text-secondary small">Ablaufdatum (optional)</label>
                            <input type="datetime-local" class="form-control" id="create_expires_at" name="expires_at">
                        </div>

                        <div class="col-12 d-flex justify-content-between align-items-center pt-2">
                            <span class="text-muted small">
                                Der Code wird generiert, gehasht gespeichert und direkt per E-Mail versendet.
                            </span>
                            <button type="submit" class="btn btn-primary px-4 fw-semibold shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-send-fill me-1" viewBox="0 0 16 16">
                                    <path d="M15.964.686a.5.5 0 0 0-.65-.65L.767 5.855H.766l-.452.18a.5.5 0 0 0-.082.887l.41.26.001.002 4.995 3.178 3.178 4.995.002.002.26.41a.5.5 0 0 0 .886-.083zm-1.833 1.89L6.637 10.07l-.215-.136L2.17 7.42 14.13 2.576z"/>
                                </svg>
                                Code erzeugen &amp; senden
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter- und Suchleiste -->
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <div class="d-flex align-items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-funnel-fill text-secondary me-2" viewBox="0 0 16 16">
                        <path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 14.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5z"/>
                    </svg>
                    <h5 class="card-title mb-0 fw-bold text-dark">Filter &amp; Suche</h5>
                    <?php if ($isFiltered): ?>
                        <span class="badge bg-primary ms-2">Filter aktiv</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body p-3">
                <form method="get" action="" class="row g-2 align-items-end">
                    <input type="hidden" name="route" value="admin/magic-codes">

                    <div class="col-md-3">
                        <label for="filter_email" class="form-label text-secondary small mb-1 fw-semibold">E-Mail (Suche)</label>
                        <input type="text" class="form-control form-control-sm" id="filter_email" name="email" 
                               value="<?= htmlspecialchars($filterEmail, ENT_QUOTES, 'UTF-8') ?>" 
                               placeholder="z.B. @studiocreativo.ch">
                    </div>

                    <div class="col-md-2">
                        <label for="filter_status" class="form-label text-secondary small mb-1 fw-semibold">Status</label>
                        <select class="form-select form-select-sm" id="filter_status" name="status">
                            <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>>Alle Status</option>
                            <option value="active" <?= $filterStatus === 'active' ? 'selected' : '' ?>>Aktiv &amp; noch nutzbar</option>
                            <option value="used" <?= $filterStatus === 'used' ? 'selected' : '' ?>>Bereits voll genutzt</option>
                            <option value="expired" <?= $filterStatus === 'expired' ? 'selected' : '' ?>>Abgelaufen</option>
                            <option value="deactivated" <?= $filterStatus === 'deactivated' ? 'selected' : '' ?>>Deaktiviert / Gesperrt</option>
                            <option value="deleted" <?= $filterStatus === 'deleted' ? 'selected' : '' ?>>Gelöscht (Soft-Delete)</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="filter_usage_type" class="form-label text-secondary small mb-1 fw-semibold">Verwendungszweck</label>
                        <select class="form-select form-select-sm" id="filter_usage_type" name="usage_type">
                            <option value="all" <?= $filterUsageType === 'all' ? 'selected' : '' ?>>Alle Zwecke</option>
                            <option value="admin_login" <?= $filterUsageType === 'admin_login' ? 'selected' : '' ?>>admin_login</option>
                            <option value="frontend" <?= $filterUsageType === 'frontend' ? 'selected' : '' ?>>frontend</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="filter_date_from" class="form-label text-secondary small mb-1 fw-semibold">Erstellt ab</label>
                        <input type="date" class="form-control form-control-sm" id="filter_date_from" name="date_from" 
                               value="<?= htmlspecialchars($filterDateFrom, ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-2">
                        <label for="filter_date_to" class="form-label text-secondary small mb-1 fw-semibold">Erstellt bis</label>
                        <input type="date" class="form-control form-control-sm" id="filter_date_to" name="date_to" 
                               value="<?= htmlspecialchars($filterDateTo, ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-md-1 d-flex gap-1">
                        <button type="submit" class="btn btn-sm btn-primary w-100 fw-semibold" title="Filter anwenden">
                            Filtern
                        </button>
                        <?php if ($isFiltered): ?>
                            <a href="?route=admin/magic-codes" class="btn btn-sm btn-outline-secondary" title="Filter zurücksetzen">
                                ✕
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Tabelle der Magic-Codes -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-shield-lock-fill text-secondary me-2" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M8 0c-.69 0-1.843.265-2.928.56-1.11.3-2.229.655-2.887.87a1.54 1.54 0 0 0-1.044 1.262c-.596 4.477.787 7.795 2.465 9.99a11.8 11.8 0 0 0 2.517 2.453c.386.273.744.482 1.048.625.28.132.581.24.829.24s.548-.108.829-.24a7 7 0 0 0 1.048-.625 11.8 11.8 0 0 0 2.517-2.453c1.678-2.195 3.061-5.513 2.465-9.99a1.54 1.54 0 0 0-1.044-1.263 63 63 0 0 0-2.887-.87C9.843.266 8.69 0 8 0m0 5a1.5 1.5 0 0 1 .5 2.915l.385 1.99a.5.5 0 0 1-.491.595h-.788a.5.5 0 0 1-.49-.595l.384-1.99A1.5 1.5 0 0 1 8 5"/>
            </svg>
            <h5 class="card-title mb-0 fw-bold text-dark">Magic-Code Übersicht</h5>
        </div>
        <span class="badge bg-secondary rounded-pill px-3 py-2">
            <?= count($magicCodes) ?> <?= count($magicCodes) === 1 ? 'Eintrag' : 'Einträge' ?>
        </span>
    </div>

    <div class="card-body p-0">
        <?php if (empty($magicCodes)): ?>
            <div class="p-5 text-center text-muted">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" class="bi bi-inbox text-secondary opacity-50 mb-3" viewBox="0 0 16 16">
                    <path d="M4.98 4a.5.5 0 0 0-.39.188L1.54 8H6a.5.5 0 0 1 .5.5 1.5 1.5 0 1 0 3 0A.5.5 0 0 1 10 8h4.46l-3.05-3.812A.5.5 0 0 0 11.02 4zm9.954 5H10.45a2.5 2.5 0 0 1-4.9 0H1.066l.32 2.562a.5.5 0 0 0 .497.438h12.234a.5.5 0 0 0 .496-.438zM3.809 3.563A1.5 1.5 0 0 1 4.981 3h6.038a1.5 1.5 0 0 1 1.172.563l3.7 4.625a.5.5 0 0 1 .109.312v4.5A1.5 1.5 0 0 1 14.5 14h-13A1.5 1.5 0 0 1 0 12.5v-4.5a.5.5 0 0 1 .109-.312z"/>
                </svg>
                <p class="fs-6 fw-medium mb-1">Keine Magic-Codes gefunden.</p>
                <p class="small text-muted mb-3">Für die gewählten Filterkriterien existieren keine Datensätze in der Datenbank.</p>
                <?php if ($isFiltered): ?>
                    <a href="?route=admin/magic-codes" class="btn btn-sm btn-outline-primary">
                        Filter zurücksetzen
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover table-sm align-middle mb-0">
                    <thead class="table-light border-bottom">
                        <tr>
                            <th class="ps-3 py-2 text-secondary" style="width: 60px;">ID</th>
                            <th class="py-2 text-secondary">E-Mail</th>
                            <th class="py-2 text-secondary">Zweck</th>
                            <th class="py-2 text-secondary text-center" style="width: 110px;">Nutzung</th>
                            <th class="py-2 text-secondary text-center" style="width: 120px;">Status</th>
                            <th class="py-2 text-secondary">Ablaufdatum</th>
                            <th class="py-2 text-secondary">Erstellt am</th>
                            <th class="py-2 text-secondary">Zuletzt genutzt</th>
                            <th class="pe-3 py-2 text-secondary text-end" style="min-width: 230px;">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($magicCodes as $row):
                            $id = (int) $row['id'];
                            $email = (string) ($row['email'] ?? '');
                            $usageType = (string) ($row['usage_type'] ?? 'admin_login');
                            $usedCount = (int) ($row['used_count'] ?? 0);
                            $maxUses = (int) ($row['max_uses'] ?? 1);
                            $expiresAt = $row['expires_at'] ?? null;
                            $createdAt = (string) ($row['created_at'] ?? '');
                            $usedAt = $row['used_at'] ?? null;

                            $statusInfo = MagicCode::getStatus($row);
                            $statusKey = $statusInfo['key'];
                            $isRowDeleted = ($statusKey === 'deleted');
                            $isRowDeactivated = ($statusKey === 'deactivated');
                            $isRowInactive = in_array($statusKey, ['deleted', 'deactivated', 'used', 'expired'], true);
                        ?>
                        <tr class="<?= $isRowDeleted ? 'table-secondary opacity-75' : '' ?>">
                            <td class="ps-3 fw-bold text-muted">#<?= $id ?></td>
                            <td>
                                <span class="fw-semibold text-dark"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border font-monospace small">
                                    <?= htmlspecialchars($usageType, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="fw-bold <?= $usedCount >= $maxUses ? 'text-secondary' : 'text-primary' ?>"><?= $usedCount ?></span>
                                <span class="text-muted">/ <?= $maxUses ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge <?= $statusInfo['badge_class'] ?> rounded-pill px-2 py-1">
                                    <?= htmlspecialchars($statusInfo['label'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td class="small text-muted">
                                <?= $expiresAt ? htmlspecialchars((string) $expiresAt, ENT_QUOTES, 'UTF-8') : '<span class="text-black-50">Unbegrenzt</span>' ?>
                            </td>
                            <td class="small text-muted">
                                <?= htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="small text-muted">
                                <?= $usedAt ? htmlspecialchars((string) $usedAt, ENT_QUOTES, 'UTF-8') : '<span class="text-black-50">Noch nie</span>' ?>
                            </td>
                            <td class="pe-3 text-end">
                                <div class="d-inline-flex gap-1 justify-content-end">
                                    <!-- Aktion: Resend (neuen Code erzeugen & senden) -->
                                    <form method="post" action="?route=admin/magic-codes/resend" class="d-inline"
                                          onsubmit="return confirm('Neuen 10-stelligen Code für <?= htmlspecialchars(addslashes($email), ENT_QUOTES, 'UTF-8') ?> generieren und per E-Mail versenden?');">
                                        <?= Csrf::input() ?>
                                        <input type="hidden" name="id" value="<?= $id ?>">
                                        <button type="submit" class="btn btn-outline-primary btn-sm py-0 px-2" title="Neuen Code generieren &amp; versenden">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" class="bi bi-arrow-repeat me-1" viewBox="0 0 16 16">
                                                <path d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41m-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9"/>
                                                <path fill-rule="evenodd" d="M8 3c-1.552 0-2.94.707-3.857 1.818a.5.5 0 1 1-.771-.636A6.002 6.002 0 0 1 13.917 7H12.9A5 5 0 0 0 8 3M3.1 9a5.002 5.002 0 0 0 8.757 2.182.5.5 0 1 1 .771.636A6.002 6.002 0 0 1 2.083 9z"/>
                                            </svg>
                                            Resend
                                        </button>
                                    </form>

                                    <!-- Aktion: Deaktivieren / Sperren -->
                                    <?php if (!$isRowDeactivated && !$isRowDeleted): ?>
                                        <form method="post" action="?route=admin/magic-codes/deactivate" class="d-inline"
                                              onsubmit="return confirm('Magic-Code #<?= $id ?> für <?= htmlspecialchars(addslashes($email), ENT_QUOTES, 'UTF-8') ?> wirklich sperren/deaktivieren?');">
                                            <?= Csrf::input() ?>
                                            <input type="hidden" name="id" value="<?= $id ?>">
                                            <button type="submit" class="btn btn-outline-warning btn-sm py-0 px-2" title="Code sofort sperren">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" class="bi bi-slash-circle me-1" viewBox="0 0 16 16">
                                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                                                    <path d="M11.354 4.646a.5.5 0 0 0-.708 0l-6 6a.5.5 0 0 0 .708.708l6-6a.5.5 0 0 0 0-.708"/>
                                                </svg>
                                                Sperren
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-outline-secondary btn-sm py-0 px-2 opacity-50" disabled>
                                            Sperren
                                        </button>
                                    <?php endif; ?>

                                    <!-- Aktion: Soft-Delete -->
                                    <?php if (!$isRowDeleted): ?>
                                        <form method="post" action="?route=admin/magic-codes/delete" class="d-inline"
                                              onsubmit="return confirm('Magic-Code #<?= $id ?> wirklich löschen (Soft-Delete)?');">
                                            <?= Csrf::input() ?>
                                            <input type="hidden" name="id" value="<?= $id ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-2" title="Code ins Archiv / Soft-Delete">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" class="bi bi-trash3 me-1" viewBox="0 0 16 16">
                                                    <path d="M6.5 1h3a.5.5 0 0 1 .5.5v1H6v-1a.5.5 0 0 1 .5-.5M11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3A1.5 1.5 0 0 0 5 1.5v1H1.5a.5.5 0 0 0 0 1h.538l.853 10.66A2 2 0 0 0 4.885 16h6.23a2 2 0 0 0 1.994-1.84l.853-10.66h.538a.5.5 0 0 0 0-1zm1.958 1-.846 10.58a1 1 0 0 1-.997.92h-6.23a1 1 0 0 1-.997-.92L3.042 3.5zm-7.487 1a.5.5 0 0 1 .528.47l.5 8.5a.5.5 0 0 1-.998.06L5 5.03a.5.5 0 0 1 .47-.53Zm5.058 0a.5.5 0 0 1 .47.53l-.5 8.5a.5.5 0 1 1-.998-.06l.5-8.5a.5.5 0 0 1 .528-.47M8 4.5a.5.5 0 0 1 .5.5v8.5a.5.5 0 0 1-1 0V5a.5.5 0 0 1 .5-.5"/>
                                                </svg>
                                                Löschen
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="badge bg-secondary py-1 px-2">Archiviert</span>
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

<?php
$content = ob_get_clean();

// Zentrales Layout einbinden
require __DIR__ . '/../layouts/admin.php';
