<?php

declare(strict_types=1);

/**
 * Admin: Detailansicht eines Sicherungsvorgangs (/admin/secure/cases/view)
 *
 * @var array<string, mixed> $case
 * @var array<int, array<string, mixed>> $caseLogs
 * @var bool $canManage
 */

$title = 'Vorgang ' . htmlspecialchars((string) ($case['case_number'] ?? ''), ENT_QUOTES, 'UTF-8');
$activeNav = 'admin/secure/cases';

$caseId = (int) ($case['id'] ?? 0);
$caseNumber = (string) ($case['case_number'] ?? '');
$status = (string) ($case['status'] ?? 'new');
$statusLabel = (string) ($case['status_label'] ?? ucfirst($status));
$statusBadge = (string) ($case['status_badge'] ?? 'bg-secondary');

$secType = (string) ($case['securing_type'] ?? 'VIDEO');
$secTypeLabel = (string) ($case['securing_type_label'] ?? $secType);
$meta = (array) ($case['securing_meta_decoded'] ?? []);

$statuses = SecurePortalRepository::STATUSES;
$csrfToken = class_exists('Csrf') ? Csrf::getToken() : '';
$caseFiles = SecurePortalRepository::getCaseFiles($caseId, true);
$downloadLogs = SecurePortalRepository::getDownloadLogs($caseId);

$downloadToken = (string) ($case['download_token'] ?? '');
$downloadEnabled = !empty($case['download_enabled']);
$downloadExpiresAt = !empty($case['download_expires_at']) ? (string)$case['download_expires_at'] : null;
$availableAt = !empty($case['available_at']) ? (string)$case['available_at'] : null;
$daysActiveSetting = class_exists('SecurePortalConfig') ? SecurePortalConfig::getDownloadDaysActive() : 30;
$daysDeleteSetting = class_exists('SecurePortalConfig') ? SecurePortalConfig::getDownloadDaysDelete() : 60;

$tLockDate = null;
$tDeleteDate = null;
if ($availableAt !== null) {
    $tLockDate = date('d.m.Y', strtotime($availableAt . " +{$daysActiveSetting} days"));
    $tDeleteDate = date('d.m.Y', strtotime($availableAt . " +{$daysDeleteSetting} days"));
}

$isExpired = false;
if ($downloadExpiresAt !== null) {
    $expTs = strtotime($downloadExpiresAt);
    if ($expTs !== false && $expTs < time()) {
        $isExpired = true;
    }
}

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$downloadUrl = $downloadToken !== '' ? ($protocol . '://' . $host . '/?route=sicherung/download&token=' . urlencode($downloadToken)) : '';
$downloadRelUrl = $downloadToken !== '' ? ('?route=sicherung/download&token=' . urlencode($downloadToken)) : '';

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError   = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

ob_start();
?>

<?php if ($flashSuccess): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-center">
            <i class="bi bi-check-circle-fill text-success fs-5 me-2"></i>
            <div><?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>
    </div>
<?php endif; ?>

<?php if ($flashError): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-center">
            <i class="bi bi-exclamation-triangle-fill text-danger fs-5 me-2"></i>
            <div><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>
    </div>
<?php endif; ?>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <a href="?route=admin/secure/cases" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Zurück zur Liste
            </a>
            <span class="badge bg-light text-dark border font-monospace">ID: <?= htmlspecialchars($caseNumber, ENT_QUOTES, 'UTF-8') ?></span>
            <span class="badge <?= $statusBadge ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <h1 class="h3 fw-bold text-dark mb-0">
            Fall Nr: <?= htmlspecialchars((string) ($case['reference_number'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
        </h1>
        <small class="text-muted">
            Behörde: <strong><?= htmlspecialchars((string) ($case['police_department'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong> &middot; 
            Sachbearbeiter: <?= htmlspecialchars((string) ($case['contact_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
        </small>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
        <?php if ($status !== 'available'): ?>
            <form method="POST" action="?route=admin/secure/cases/set-available" class="d-inline m-0">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="id" value="<?= $caseId ?>">
                <button type="submit" class="btn btn-success btn-sm shadow-sm" title="Vorgangsstatus auf Bereitgestellt setzen">
                    <i class="bi bi-check2-circle me-1"></i> Sicherungsdaten bereitstellen
                </button>
            </form>
        <?php else: ?>
            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                <i class="bi bi-check-circle-fill me-1"></i> Daten bereitgestellt
            </span>
        <?php endif; ?>

        <a href="?route=admin/secure/settings" class="btn btn-outline-dark btn-sm" title="Einstellungen &amp; Namens-Templates">
            <i class="bi bi-gear"></i>
        </a>
        <?php if (!empty($case['sharepoint_folder_url'])): ?>
            <a href="<?= htmlspecialchars((string) $case['sharepoint_folder_url'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary btn-sm" target="_blank" rel="noopener noreferrer">
                <i class="bi bi-folder-symlink-fill me-1 text-primary"></i> SharePoint-Ordner
            </a>
        <?php endif; ?>
        <a href="?route=admin/secure/cases/download-warrant&id=<?= $caseId ?>" class="btn btn-outline-danger btn-sm" target="_blank">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i> Editionsverfügung herunterladen
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Linke Spalte: Vorgangsdetails & Technische Spezifikation -->
    <div class="col-lg-7">

        <!-- 1. Basis- und Kontaktdaten -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0 fw-semibold text-dark">
                    <i class="bi bi-building text-primary me-2"></i> Behörden- &amp; Kontaktdaten
                </h5>
                <span class="small text-muted">
                    Eingang: <?= date('d.m.Y H:i', strtotime((string)$case['created_at'])) ?> Uhr
                </span>
            </div>
            <div class="card-body p-4">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="text-muted small fw-bold d-block text-uppercase">Dienststelle</label>
                        <div class="fw-semibold text-dark"><?= htmlspecialchars((string) ($case['police_department'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small fw-bold d-block text-uppercase">Sachbearbeiter</label>
                        <div class="fw-semibold text-dark"><?= htmlspecialchars((string) ($case['contact_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small fw-bold d-block text-uppercase">Dienstliche E-Mail</label>
                        <a href="mailto:<?= htmlspecialchars((string) ($case['contact_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none">
                            <?= htmlspecialchars((string) ($case['contact_email'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small fw-bold d-block text-uppercase">Telefonnummer</label>
                        <a href="tel:<?= htmlspecialchars((string) ($case['contact_phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none">
                            <?= htmlspecialchars((string) ($case['contact_phone'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small fw-bold d-block text-uppercase">Fall Nr</label>
                        <span class="badge bg-light text-dark border fs-6 font-monospace">
                            <?= htmlspecialchars((string) ($case['reference_number'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small fw-bold d-block text-uppercase">Gewünschter Sicherungstermin</label>
                        <div class="fw-semibold <?= !empty($case['desired_date']) ? 'text-primary' : 'text-muted' ?>">
                            <?= !empty($case['desired_date']) ? date('d.m.Y', strtotime((string)$case['desired_date'])) : 'Keine spezifische Frist' ?>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="text-muted small fw-bold d-block text-uppercase">Beschreibung des Sicherungsumfangs</label>
                    <div class="p-3 bg-light rounded border text-dark">
                        <?= nl2br(htmlspecialchars((string) ($case['description'] ?? ''), ENT_QUOTES, 'UTF-8')) ?>
                    </div>
                </div>

                <?php if (!empty($case['remarks'])): ?>
                    <div>
                        <label class="text-muted small fw-bold d-block text-uppercase">Besondere Hinweise / Bemerkungen</label>
                        <div class="p-2 bg-light rounded text-muted small border">
                            <?= nl2br(htmlspecialchars((string) $case['remarks'], ENT_QUOTES, 'UTF-8')) ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 2. Technische Spezifikation -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0 fw-semibold text-dark">
                    <i class="bi bi-hdd-network text-primary me-2"></i> Art der Sicherung: <?= htmlspecialchars($secTypeLabel, ENT_QUOTES, 'UTF-8') ?>
                </h5>
                <span class="badge bg-primary-subtle text-primary border">
                    <?= htmlspecialchars($secType, ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>
            <div class="card-body p-4">
                <?php if ($secType === 'VIDEO'): ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="text-muted small fw-bold d-block text-uppercase">Sicherungszeitraum VON</label>
                            <div class="p-2 bg-light rounded border font-monospace">
                                <?= !empty($meta['timeframe_from']) ? htmlspecialchars((string) $meta['timeframe_from'], ENT_QUOTES, 'UTF-8') : '-' ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small fw-bold d-block text-uppercase">Sicherungszeitraum BIS</label>
                            <div class="p-2 bg-light rounded border font-monospace">
                                <?= !empty($meta['timeframe_to']) ? htmlspecialchars((string) $meta['timeframe_to'], ENT_QUOTES, 'UTF-8') : '-' ?>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="text-muted small fw-bold d-block text-uppercase">Kamera-Standorte / Bereiche</label>
                            <div class="fw-semibold text-dark">
                                <?= htmlspecialchars((string) ($meta['camera_location'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </div>
                        <?php if (!empty($meta['video_notes'])): ?>
                            <div class="col-12">
                                <label class="text-muted small fw-bold d-block text-uppercase">Personen- / Fahrzeugmerkmale</label>
                                <div class="p-2 bg-light rounded text-muted small border">
                                    <?= nl2br(htmlspecialchars((string) $meta['video_notes'], ENT_QUOTES, 'UTF-8')) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                <?php elseif ($secType === 'MAIL'): ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="text-muted small fw-bold d-block text-uppercase">Postfach / E-Mail</label>
                            <div class="fw-semibold text-dark"><?= htmlspecialchars((string) ($meta['mailbox_address'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small fw-bold d-block text-uppercase">Umfang</label>
                            <div><?= htmlspecialchars((string) ($meta['mail_scope'] ?? 'Vollständig'), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small fw-bold d-block text-uppercase">Zeitraum von</label>
                            <div><?= htmlspecialchars((string) ($meta['mail_timeframe_from'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small fw-bold d-block text-uppercase">Zeitraum bis</label>
                            <div><?= htmlspecialchars((string) ($meta['mail_timeframe_to'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    </div>

                <?php elseif ($secType === 'CLOUD'): ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="text-muted small fw-bold d-block text-uppercase">System / Server</label>
                            <div class="fw-semibold text-dark"><?= htmlspecialchars((string) ($meta['system_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="col-12">
                            <label class="text-muted small fw-bold d-block text-uppercase">Zieldateien / Pfade / Accounts</label>
                            <div class="p-2 bg-light rounded font-monospace small border">
                                <?= nl2br(htmlspecialchars((string) ($meta['cloud_target_data'] ?? '-'), ENT_QUOTES, 'UTF-8')) ?>
                            </div>
                        </div>
                    </div>

                <?php elseif ($secType === 'ACCESS_LOG'): ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="text-muted small fw-bold d-block text-uppercase">Türen / Zutrittspunkte</label>
                            <div class="fw-semibold text-dark"><?= htmlspecialchars((string) ($meta['doors_points'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small fw-bold d-block text-uppercase">Zeitraum</label>
                            <div><?= htmlspecialchars((string) ($meta['log_timeframe'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <?php if (!empty($meta['card_ids'])): ?>
                            <div class="col-12">
                                <label class="text-muted small fw-bold d-block text-uppercase">Transponder- / Kartennummern</label>
                                <div class="font-monospace small"><?= htmlspecialchars((string) $meta['card_ids'], ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                        <?php endif; ?>
                    </div>

                <?php else: ?>
                    <div>
                        <label class="text-muted small fw-bold d-block text-uppercase">Spezifikation</label>
                        <div class="p-3 bg-light rounded border">
                            <?= nl2br(htmlspecialchars((string) ($meta['other_details'] ?? '-'), ENT_QUOTES, 'UTF-8')) ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 3. Sicherungsdaten & Beweismittel-Archive (NAS/Intern) -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-primary-subtle text-primary p-2 rounded">
                        <i class="bi bi-archive-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="card-title mb-0 fw-bold text-dark">
                            Sicherungsdaten &amp; Beweismittel-Archive
                        </h5>
                        <small class="text-muted">Interne Ablage (NAS), Hash-Dokumentation &amp; Bereitstellung</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-dark border">
                        <?= count($caseFiles) ?> Datei(en)
                    </span>
                    <?php if ($status === 'available'): ?>
                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                            <i class="bi bi-check-circle-fill me-1"></i> Bereitgestellt
                        </span>
                    <?php else: ?>
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                            <i class="bi bi-hourglass-split me-1"></i> Ausstehend
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body p-4">

                <!-- Schnelle Bereitstellung (Banner / Button) -->
                <?php if ($status !== 'available'): ?>
                    <div class="alert alert-warning border-warning-subtle d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
                        <div class="d-flex align-items-center gap-3">
                            <i class="bi bi-exclamation-circle-fill text-warning fs-3 flex-shrink-0"></i>
                            <div>
                                <strong class="d-block text-dark">Sicherungsdaten noch nicht freigegeben</strong>
                                <small class="text-muted">
                                    Der Vorgang steht derzeit auf „<?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>“. Sobald die Daten vorliegen, können Sie den Fall mit einem Klick auf „Bereitgestellt“ setzen.
                                </small>
                            </div>
                        </div>
                        <form method="POST" action="?route=admin/secure/cases/set-available" class="m-0">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="id" value="<?= $caseId ?>">
                            <button type="submit" class="btn btn-success fw-bold text-nowrap shadow-sm">
                                <i class="bi bi-check2-circle me-1"></i> Jetzt bereitstellen
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success border-success-subtle d-flex align-items-center gap-3 mb-4">
                        <i class="bi bi-check-circle-fill text-success fs-3 flex-shrink-0"></i>
                        <div>
                            <strong class="d-block">Sicherungsdaten sind bereitgestellt</strong>
                            <small class="text-muted">
                                Der Vorgang ist für den Antragsteller im Fallzugang als „Bereitgestellt“ sichtbar.
                            </small>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Liste vorhandener Sicherungsdateien -->
                <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                    <i class="bi bi-files text-primary"></i> Hinterlegte Sicherungsdateien
                </h6>

                <?php if (empty($caseFiles)): ?>
                    <div class="p-4 bg-light rounded text-center text-muted border mb-4">
                        <i class="bi bi-folder-x fs-1 d-block mb-2 text-secondary"></i>
                        <div class="fw-semibold text-dark">Noch keine Sicherungsdaten hochgeladen</div>
                        <small>Laden Sie hier das aufbereitete Sicherungsarchiv (z. B. ZIP, TAR, 7Z oder Raw-Images) hoch.</small>
                    </div>
                <?php else: ?>
                    <div class="list-group mb-4">
                        <?php foreach ($caseFiles as $cf): ?>
                            <?php 
                                $fileSizeMb = number_format(((int)$cf['file_size']) / 1024 / 1024, 2);
                                $cfId = (int) $cf['id'];
                            ?>
                            <div class="list-group-item p-3 border rounded mb-2 shadow-sm">
                                <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-primary-subtle text-primary p-2 rounded">
                                            <i class="bi bi-file-earmark-zip-fill fs-4"></i>
                                        </div>
                                        <div>
                                            <span class="fw-bold text-dark font-monospace"><?= htmlspecialchars((string) $cf['file_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                            <div class="text-muted small">
                                                <span><i class="bi bi-hdd me-1"></i><?= $fileSizeMb ?> MB</span> &middot;
                                                <span><i class="bi bi-clock me-1"></i><?= date('d.m.Y H:i', strtotime((string)$cf['uploaded_at'])) ?> Uhr</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <!-- Re-Verify Hash Button -->
                                        <form method="POST" action="?route=admin/secure/cases/verify-file-hash" class="d-inline m-0">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="case_id" value="<?= $caseId ?>">
                                            <input type="hidden" name="file_id" value="<?= $cfId ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary" title="SHA-256 Prüfsumme auf Datenträger erneut validieren">
                                                <i class="bi bi-shield-check me-1 text-primary"></i> SHA-256 prüfen
                                            </button>
                                        </form>

                                        <!-- Download Button -->
                                        <a href="?route=admin/secure/cases/download-file&case_id=<?= $caseId ?>&file_id=<?= $cfId ?>" class="btn btn-sm btn-outline-primary" title="Datei intern herunterladen">
                                            <i class="bi bi-download me-1"></i> Download
                                        </a>

                                        <!-- Delete Button -->
                                        <form method="POST" action="?route=admin/secure/cases/delete-file" class="d-inline m-0" onsubmit="return confirm('Möchten Sie diese Sicherungsdatei wirklich entfernen?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="case_id" value="<?= $caseId ?>">
                                            <input type="hidden" name="file_id" value="<?= $cfId ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Datei entfernen">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <!-- SHA-256 Checksum Display -->
                                <div class="bg-light p-2 rounded border font-monospace small d-flex align-items-center justify-content-between">
                                    <div class="text-truncate me-2">
                                        <span class="text-muted fw-bold me-1">SHA-256:</span>
                                        <span class="text-dark select-all" id="hash_<?= $cfId ?>"><?= htmlspecialchars((string) $cf['sha256'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-link text-decoration-none py-0 px-1 text-muted text-nowrap" onclick="copyHash('hash_<?= $cfId ?>', this)" title="In Zwischenablage kopieren">
                                        <i class="bi bi-clipboard me-1"></i> Kopieren
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Upload Formular -->
                <div class="border rounded p-3 bg-light">
                    <h6 class="fw-bold text-dark mb-2">
                        <i class="bi bi-cloud-arrow-up text-primary me-1"></i> Neue Sicherungsdatei / Archiv hochladen
                    </h6>
                    <form method="POST" action="?route=admin/secure/cases/upload-data" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="id" value="<?= $caseId ?>">

                        <div class="mb-3">
                            <label for="securing_file" class="form-label small fw-semibold text-dark">
                                Sicherungsdatei auswählen (z. B. .zip, .tar, .7z, .iso, .raw)
                            </label>
                            <input type="file" name="securing_file" id="securing_file" class="form-control form-control-sm" required>
                            <div class="form-text small text-muted">
                                Beim Upload wird auf dem Server automatisch der SHA-256 Prüfsummen-Hashwert berechnet und manipulationssicher im Fallprotokoll verankert.
                            </div>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="set_available" id="set_available_cb" value="1" <?= $status !== 'available' ? 'checked' : '' ?>>
                            <label class="form-check-label small fw-semibold" for="set_available_cb">
                                Fallstatus nach erfolgreichem Upload automatisch auf „Bereitgestellt“ setzen
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm px-3">
                            <i class="bi bi-upload me-1"></i> Sicherungsdatei hochladen &amp; SHA-256 berechnen
                        </button>
                    </form>
                </div>

            </div>
        </div>

        <!-- 4. Externer Datenraum (Polizei-Download & Freigabelink) -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-primary-subtle text-primary p-2 rounded">
                        <i class="bi bi-shield-lock-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="card-title mb-0 fw-bold text-dark">
                            Externer Datenraum (Polizei-Download)
                        </h5>
                        <small class="text-muted">Geschützter Download-Bereich für Ermittlungsbehörden &amp; Audit-Logging</small>
                    </div>
                </div>
                <div>
                    <?php if (!$downloadEnabled): ?>
                        <span class="badge bg-secondary-subtle text-secondary border">
                            <i class="bi bi-slash-circle me-1"></i> Deaktiviert
                        </span>
                    <?php elseif ($isExpired): ?>
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                            <i class="bi bi-clock-history me-1"></i> Abgelaufen
                        </span>
                    <?php else: ?>
                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                            <i class="bi bi-check-circle-fill me-1"></i> Aktiv freigegeben
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body p-4">

                <?php if ($downloadToken === ''): ?>
                    <!-- Noch kein Token vorhanden -->
                    <div class="alert alert-light border p-4 text-center mb-0">
                        <i class="bi bi-key-fill text-muted fs-2 d-block mb-2"></i>
                        <h6 class="fw-bold text-dark">Noch kein Download-Token erzeugt</h6>
                        <p class="text-muted small mb-3">
                            Für diesen Vorgang wurde noch kein Freigabelink für Ermittlungsbehörden eingerichtet.
                        </p>
                        <form method="POST" action="?route=admin/secure/cases/download-token/regenerate" class="m-0">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="id" value="<?= $caseId ?>">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus-circle me-1"></i> Download-Token jetzt erzeugen &amp; Datenraum aktivieren
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <!-- Token vorhanden & konfigurierbar -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-dark text-uppercase mb-1">
                            Freigabelink für Ermittlungsbehörde (Polizei / Staatsanwaltschaft)
                        </label>
                        <div class="input-group mb-2">
                            <span class="input-group-text bg-light text-muted">
                                <i class="bi bi-link-45deg fs-5"></i>
                            </span>
                            <input type="text" id="policeDownloadUrlInput" class="form-control font-monospace small bg-white" readonly value="<?= htmlspecialchars($downloadUrl, ENT_QUOTES, 'UTF-8') ?>">
                            <button type="button" class="btn btn-outline-secondary" onclick="copyInputText('policeDownloadUrlInput', this)" title="Link kopieren">
                                <i class="bi bi-clipboard me-1"></i> Kopieren
                            </button>
                            <a href="<?= htmlspecialchars($downloadRelUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary" title="Datenraum in neuem Tab öffnen">
                                <i class="bi bi-box-arrow-up-right"></i>
                            </a>
                        </div>
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 text-muted small">
                            <span>
                                <i class="bi bi-shield-check text-success me-1"></i>
                                Token: <code class="text-dark font-monospace"><?= htmlspecialchars(substr($downloadToken, 0, 10), ENT_QUOTES, 'UTF-8') ?>...<?= htmlspecialchars(substr($downloadToken, -6), ENT_QUOTES, 'UTF-8') ?></code>
                            </span>
                            <form method="POST" action="?route=admin/secure/cases/download-token/regenerate" class="d-inline m-0" onsubmit="return confirm('Möchten Sie wirklich einen neuen Download-Token generieren? Der bisherige Zugriffslink der Polizei wird dadurch sofort ungültig.');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="id" value="<?= $caseId ?>">
                                <button type="submit" class="btn btn-link btn-sm text-danger p-0 text-decoration-none">
                                    <i class="bi bi-arrow-repeat me-1"></i> Neuen Token generieren (alten Link ungültig machen)
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Fristen-Übersicht (T+30 & T+60) -->
                    <div class="row g-2 mb-4 small">
                        <div class="col-md-4">
                            <div class="p-2 border rounded bg-light text-center h-100">
                                <span class="text-muted d-block fw-semibold" style="font-size: 0.72rem;">BEREITSTELLUNG (T+0)</span>
                                <?php if ($availableAt !== null): ?>
                                    <strong class="text-dark d-block"><?= date('d.m.Y H:i', strtotime($availableAt)) ?> Uhr</strong>
                                    <span class="badge bg-success-subtle text-success border py-0 px-1 mt-1">Erfasst</span>
                                <?php else: ?>
                                    <span class="text-muted fst-italic">Noch nicht bereitgestellt</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-2 border rounded bg-light text-center h-100">
                                <span class="text-muted d-block fw-semibold" style="font-size: 0.72rem;">ZUGANGSSPERRE (T+<?= $daysActiveSetting ?>)</span>
                                <?php if ($tLockDate !== null): ?>
                                    <strong class="text-dark d-block"><?= $tLockDate ?></strong>
                                    <?php if ($downloadEnabled): ?>
                                        <span class="badge bg-warning-subtle text-warning-emphasis border py-0 px-1 mt-1">Automatische Sperre</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary border py-0 px-1 mt-1">Bereits gesperrt</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted fst-italic">Nach Bereitstellung</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-2 border rounded bg-light text-center h-100">
                                <span class="text-muted d-block fw-semibold" style="font-size: 0.72rem;">DATENLÖSCHUNG (T+<?= $daysDeleteSetting ?>)</span>
                                <?php if ($tDeleteDate !== null): ?>
                                    <strong class="text-danger d-block"><?= $tDeleteDate ?></strong>
                                    <span class="badge bg-danger-subtle text-danger border py-0 px-1 mt-1">Server-Bereinigung</span>
                                <?php else: ?>
                                    <span class="text-muted fst-italic">Nach Bereitstellung</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Einstellungen: Freigabe & Gültigkeitsfrist -->
                    <div class="p-3 bg-light rounded border mb-4">
                        <h6 class="fw-bold text-dark mb-3">
                            <i class="bi bi-sliders text-primary me-1"></i> Zugriffssteuerung &amp; Befristung
                        </h6>
                        <form method="POST" action="?route=admin/secure/cases/download-token/update">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="id" value="<?= $caseId ?>">

                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" role="switch" name="download_enabled" id="download_enabled_switch" value="1" <?= $downloadEnabled ? 'checked' : '' ?>>
                                <label class="form-check-label fw-semibold text-dark" for="download_enabled_switch">
                                    Download-Zugriff für Polizei aktiv
                                </label>
                                <div class="form-text small">
                                    Wird dieser Schalter deaktiviert, wird der Abruf über den Freigabelink sofort blockiert.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="download_expires_at_input" class="form-label small fw-semibold text-dark">
                                    Gültig bis (Ablaufdatum &amp; Uhrzeit)
                                </label>
                                <?php 
                                    $expiresFormatted = '';
                                    if ($downloadExpiresAt !== null) {
                                        $expiresFormatted = date('Y-m-d\TH:i', strtotime($downloadExpiresAt));
                                    }
                                ?>
                                <input type="datetime-local" class="form-control form-control-sm" name="download_expires_at" id="download_expires_at_input" value="<?= htmlspecialchars($expiresFormatted, ENT_QUOTES, 'UTF-8') ?>">
                                
                                <div class="mt-2 d-flex gap-1 flex-wrap">
                                    <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size: 0.75rem;" onclick="setExpiryDays(14)">
                                        +14 Tage
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size: 0.75rem;" onclick="setExpiryDays(30)">
                                        +30 Tage
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size: 0.75rem;" onclick="setExpiryDays(60)">
                                        +60 Tage
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size: 0.75rem;" onclick="clearExpiry()">
                                        Unbegrenzt
                                    </button>
                                </div>
                                <div class="form-text small text-muted">
                                    Leer lassen für unbegrenzte Gültigkeit. Nach Ablauf kann die Ermittlungsbehörde keine Dateien mehr herunterladen.
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-sm px-3">
                                <i class="bi bi-check2-circle me-1"></i> Freigabe-Einstellungen speichern
                            </button>
                        </form>
                    </div>

                    <!-- Download-Audit-Protokoll -->
                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                                <i class="bi bi-clock-history text-primary"></i> Download-Protokoll (Audit Trail)
                            </h6>
                            <span class="badge bg-light text-dark border">
                                <?= count($downloadLogs) ?> Abruf(e)
                            </span>
                        </div>

                        <?php if (empty($downloadLogs)): ?>
                            <div class="p-3 bg-light rounded text-center text-muted border small">
                                <i class="bi bi-info-circle me-1"></i> Bisher wurden noch keine Dateien über den externen Datenraum heruntergeladen.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive border rounded bg-white" style="max-height: 250px; overflow-y: auto;">
                                <table class="table table-sm table-hover align-middle mb-0 small">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th>Zeitstempel</th>
                                            <th>Datei</th>
                                            <th>IP-Adresse</th>
                                            <th>Client</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($downloadLogs as $dl): ?>
                                            <tr>
                                                <td class="text-nowrap fw-semibold text-dark">
                                                    <?= date('d.m.Y H:i:s', strtotime((string)$dl['downloaded_at'])) ?>
                                                </td>
                                                <td>
                                                    <span class="font-monospace text-primary fw-semibold">
                                                        <?= htmlspecialchars((string)($dl['file_name'] ?? ('ID #' . $dl['file_id'])), ENT_QUOTES, 'UTF-8') ?>
                                                    </span>
                                                </td>
                                                <td class="font-monospace text-nowrap">
                                                    <?= htmlspecialchars((string)$dl['ip_address'], ENT_QUOTES, 'UTF-8') ?>
                                                </td>
                                                <td class="text-muted text-truncate" style="max-width: 180px;" title="<?= htmlspecialchars((string)($dl['user_agent'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>">
                                                    <?= htmlspecialchars((string)($dl['user_agent'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                <?php endif; ?>

            </div>
        </div>

    </div>

    <!-- Rechte Spalte: Status ändern, Notizen & Audit-Log -->
    <div class="col-lg-5">

        <!-- Editionsverfügung & Fall-Zugangscode -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 fw-semibold text-dark">
                    <i class="bi bi-shield-lock text-primary me-2"></i> Dokumente &amp; Zugangsdaten
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded border mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <i class="bi bi-file-earmark-pdf-fill fs-1 text-danger"></i>
                        <div>
                            <div class="fw-bold text-dark font-monospace">
                                <?= htmlspecialchars(class_exists('SecurePortalConfig') ? SecurePortalConfig::buildWarrantFileName((string) $case['case_number'], 1) : 'Editionsverfuegung.pdf', ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <small class="text-muted d-block">
                                <i class="bi bi-folder me-1"></i> <?= htmlspecialchars((string) ($case['warrant_file_path'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                            </small>
                            <small class="text-secondary">
                                <?= number_format(((int)($case['warrant_file_size'] ?? 0)) / 1024 / 1024, 2) ?> MB &middot; 
                                Hochgeladen <?= date('d.m.Y H:i', strtotime((string)$case['warrant_uploaded_at'])) ?>
                            </small>
                        </div>
                    </div>
                    <a href="?route=admin/secure/cases/download-warrant&id=<?= $caseId ?>" class="btn btn-sm btn-outline-danger" target="_blank">
                        <i class="bi bi-download me-1"></i> Download
                    </a>
                </div>

                <!-- SharePoint-Archivierungsstatus -->
                <div class="p-3 rounded border mb-3 <?= !empty($case['sharepoint_item_id']) ? 'bg-light border-primary-subtle' : 'bg-light' ?>">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-microsoft text-primary fs-5"></i>
                            <strong class="text-dark small">SharePoint-Archivierung</strong>
                        </div>
                        <?php if (!empty($case['sharepoint_item_id'])): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                <i class="bi bi-check2-circle me-1"></i> Synchronisiert
                            </span>
                        <?php elseif (class_exists('SharePointConfig') && SharePointConfig::isConfigured()): ?>
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                <i class="bi bi-hourglass-split me-1"></i> Ausstehend
                            </span>
                        <?php else: ?>
                            <span class="badge bg-secondary-subtle text-secondary border">
                                Nicht konfiguriert
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($case['sharepoint_item_id'])): ?>
                        <div class="small text-muted mb-2">
                            <div><strong>Item-ID:</strong> <code class="text-dark small"><?= htmlspecialchars((string) $case['sharepoint_item_id'], ENT_QUOTES, 'UTF-8') ?></code></div>
                            <?php if (!empty($case['sharepoint_synced_at'])): ?>
                                <div><strong>Synchronisiert:</strong> <?= date('d.m.Y H:i', strtotime((string) $case['sharepoint_synced_at'])) ?> Uhr</div>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex gap-2">
                            <?php if (!empty($case['sharepoint_folder_url'])): ?>
                                <a href="<?= htmlspecialchars((string) $case['sharepoint_folder_url'], ENT_QUOTES, 'UTF-8') ?>" 
                                   class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener noreferrer">
                                    <i class="bi bi-folder2-open me-1"></i> Ordner öffnen
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($case['sharepoint_web_url'])): ?>
                                <a href="<?= htmlspecialchars((string) $case['sharepoint_web_url'], ENT_QUOTES, 'UTF-8') ?>" 
                                   class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener noreferrer">
                                    <i class="bi bi-file-earmark-pdf me-1"></i> Datei öffnen
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php elseif (class_exists('SharePointConfig') && SharePointConfig::isConfigured()): ?>
                        <p class="small text-muted mb-2">
                            Die Editionsverfügung wurde noch nicht in SharePoint archiviert oder die letzte Verbindung ist fehlgeschlagen.
                        </p>
                        <form method="POST" action="?route=admin/secure/cases/sync-sharepoint" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="id" value="<?= $caseId ?>">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="bi bi-cloud-arrow-up-fill me-1"></i> Jetzt in SharePoint archivieren
                            </button>
                        </form>
                    <?php else: ?>
                        <small class="text-muted d-block">
                            SharePoint-Parameter (Tenant, Client-ID etc.) noch nicht in Settings/Config hinterlegt. Die Datei bleibt sicher lokal archiviert.
                        </small>
                    <?php endif; ?>
                </div>

                <div class="p-3 bg-dark text-white rounded text-center">
                    <small class="text-white-50 text-uppercase d-block mb-1">Fall-Zugangscode (Antragsteller)</small>
                    <div class="font-monospace fs-5 fw-bold text-warning letter-spacing-1">
                        <?= htmlspecialchars((string) ($case['access_code'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <small class="text-white-50" style="font-size: 0.75rem;">
                        Nur zur behördlichen Authentifizierung bei telefonischen Rückfragen
                    </small>
                </div>
            </div>
        </div>

        <!-- Status & Bearbeitung -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title mb-0 fw-semibold text-dark">
                    <i class="bi bi-pencil-square text-primary me-2"></i> Status aktualisieren &amp; Notizen
                </h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="?route=admin/secure/cases/update">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="id" value="<?= $caseId ?>">

                    <div class="mb-3">
                        <label for="status" class="form-label fw-semibold text-dark">Vorgangsstatus</label>
                        <select name="status" id="status" class="form-select">
                            <?php foreach ($statuses as $stKey => $stInfo): ?>
                                <option value="<?= $stKey ?>" <?= $status === $stKey ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($stInfo['label'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="internal_notes" class="form-label fw-semibold text-dark">Interne Bearbeitungsnotizen</label>
                        <textarea name="internal_notes" id="internal_notes" rows="3" class="form-control" 
                                  placeholder="Nur für Technik und Verwaltung sichtbar..."><?= htmlspecialchars((string) ($case['internal_notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                        <div class="form-text">Wird dem Antragsteller niemals angezeigt.</div>
                    </div>

                    <div class="mb-3">
                        <label for="log_message" class="form-label fw-semibold text-dark">Protokoll-Eintrag / Mitteilung verfassen</label>
                        <textarea name="log_message" id="log_message" rows="2" class="form-control" 
                                  placeholder="z. B. Sicherungsdaten auf Transfer-Medium aufgespielt..."></textarea>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="log_is_public" id="log_is_public" value="1" checked>
                        <label class="form-check-label small fw-semibold" for="log_is_public">
                            Mitteilung im Fallzugang für Antragsteller sichtbar machen
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-semibold">
                        <i class="bi bi-check2-circle me-1"></i> Änderungen speichern
                    </button>
                </form>
            </div>
        </div>

        <!-- Verlaufsprotokoll (Audit Trail) -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0 fw-semibold text-dark">
                    <i class="bi bi-clock-history text-primary me-2"></i> Aktivitäten- &amp; Statusprotokoll
                </h5>
                <span class="badge bg-secondary-subtle text-secondary"><?= count($caseLogs) ?></span>
            </div>
            <div class="card-body p-4">
                <?php if (empty($caseLogs)): ?>
                    <div class="text-center text-muted small py-3">Keine Protokolleinträge vorhanden.</div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($caseLogs as $log): ?>
                            <div class="list-group-item px-0 py-2 border-bottom">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="badge <?= !empty($log['is_internal']) ? 'bg-secondary' : 'bg-success-subtle text-success border' ?> small">
                                        <?= !empty($log['is_internal']) ? 'Intern' : 'Öffentlich sichtbar' ?>
                                    </span>
                                    <small class="text-muted"><?= date('d.m.Y H:i', strtotime((string)$log['created_at'])) ?></small>
                                </div>
                                <div class="text-dark small mb-1">
                                    <?= htmlspecialchars((string)$log['message'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <div class="text-muted" style="font-size: 0.75rem;">
                                    Von: <?= htmlspecialchars((string)$log['author'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script>
function copyHash(elementId, btn) {
    var el = document.getElementById(elementId);
    if (!el) return;
    var text = (el.innerText || el.textContent).trim();
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(function() {
            var oldHtml = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check text-success"></i> Kopiert!';
            setTimeout(function() { btn.innerHTML = oldHtml; }, 2000);
        });
    } else {
        var textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.position = "fixed";
        textArea.style.left = "-999999px";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            document.execCommand('copy');
            var oldHtml = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check text-success"></i> Kopiert!';
            setTimeout(function() { btn.innerHTML = oldHtml; }, 2000);
        } catch (err) {
            console.error('Kopieren fehlgeschlagen', err);
        }
        document.body.removeChild(textArea);
    }
}

function copyInputText(inputId, btn) {
    var el = document.getElementById(inputId);
    if (!el) return;
    var text = el.value.trim();
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(function() {
            var oldHtml = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check text-success"></i> Kopiert!';
            setTimeout(function() { btn.innerHTML = oldHtml; }, 2000);
        });
    } else {
        el.focus();
        el.select();
        try {
            document.execCommand('copy');
            var oldHtml = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check text-success"></i> Kopiert!';
            setTimeout(function() { btn.innerHTML = oldHtml; }, 2000);
        } catch (err) {
            console.error('Kopieren fehlgeschlagen', err);
        }
    }
}

function setExpiryDays(days) {
    var d = new Date();
    d.setDate(d.getDate() + days);
    d.setHours(23, 59, 0, 0);
    var pad = function(n) { return n < 10 ? '0' + n : n; };
    var val = d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
    var input = document.getElementById('download_expires_at_input');
    if (input) {
        input.value = val;
    }
}

function clearExpiry() {
    var input = document.getElementById('download_expires_at_input');
    if (input) {
        input.value = '';
    }
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../../../views/layouts/admin.php';
