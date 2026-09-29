<?php

declare(strict_types=1);

/**
 * Admin: Einstellungen & Namens-Templates des Sicherungsportals
 *
 * Konfiguration von:
 * - Ordner-Template für Fallakte (`secure_case_folder_template`)
 * - Dateinamen-Template für Editionsverfügung (`secure_case_warrant_filename_template`)
 * - SharePoint & Microsoft Graph Integration
 */

$title = 'Sicherungsportal Einstellungen';
$activeNav = 'admin/secure/cases';

$folderTemplate = (string) Settings::get('secure_case_folder_template', Naming::DEFAULT_FOLDER_TEMPLATE);
$filenameTemplate = (string) Settings::get('secure_case_warrant_filename_template', Naming::DEFAULT_FILENAME_TEMPLATE);
$notificationEmail = (string) Settings::get('secure_notification_email', '');
$daysActive = class_exists('SecurePortalConfig') ? SecurePortalConfig::getDownloadDaysActive() : 30;
$daysDelete = class_exists('SecurePortalConfig') ? SecurePortalConfig::getDownloadDaysDelete() : 60;

$videoObjectsConfig = class_exists('SecurePortalConfig') ? SecurePortalConfig::getVideoObjectsConfig() : [];

// Beispiel-Auflösung für die Vorschau
$sampleCase = [
    'case_number'       => 'POL-2026-000123',
    'reference_number'  => 'ST.2026.4589',
    'police_department' => 'Kantonspolizei Zürich',
    'city'              => 'Zürich',
    'created_at'        => date('Y-m-d H:i:s'),
];
$previewFolder = Naming::buildCaseFolder($sampleCase['case_number'], $sampleCase);
$previewFile   = Naming::buildWarrantFileName($sampleCase['case_number'], $sampleCase);
$spBase        = SharePointConfig::getBaseFolder();
$previewSpPath = ($spBase !== '' ? rtrim($spBase, '/') . '/' : '') . $previewFolder . '/' . $previewFile;

$isSpConfigured = SharePointConfig::isConfigured();

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError   = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

ob_start();
?>

<div class="container-fluid px-0">

    <!-- Kopfzeile -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="?route=admin/secure/cases" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Zurück zu Vorgängen
                </a>
                <span class="badge bg-secondary-subtle text-secondary border">Konfiguration</span>
            </div>
            <h1 class="h3 mb-1 fw-bold text-dark">Sicherungsportal – Einstellungen &amp; Namens-Templates</h1>
            <p class="text-muted small mb-0">
                Konfigurieren Sie dynamische Benennungsmuster für Fallordner, Editionsverfügungen und die SharePoint-Archivierung.
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="?route=admin/secure/cases" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-folder-check me-1"></i> Vorgänge anzeigen
            </a>
            <a href="?route=sicherung/antrag" target="_blank" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-box-arrow-up-right me-1"></i> Antragsformular öffnen
            </a>
        </div>
    </div>

    <!-- Flash-Meldungen -->
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

    <form method="POST" action="?route=admin/secure/settings" class="needs-validation">
        <?php if (class_exists('Csrf')): ?>
            <?= Csrf::field() ?>
        <?php endif; ?>

        <div class="row g-4">

            <!-- Linke Spalte: Namens- und Ordner-Templates -->
            <div class="col-lg-8">

                <!-- HAUPT-ABSCHNITT: Dateinamen & Ordnerstruktur -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-primary-subtle text-primary p-2 rounded">
                                    <i class="bi bi-folder-symlink fs-5"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0 fw-bold text-dark">Sicherungsportal – Dateinamen &amp; Ordnerstruktur</h5>
                                    <small class="text-muted">Einheitliche Benennung für das lokale Dateisystem und SharePoint</small>
                                </div>
                            </div>
                            <span class="badge bg-light text-dark border">Phase 1</span>
                        </div>
                    </div>
                    <div class="card-body p-4">

                        <!-- Ordner-Template -->
                        <div class="mb-4">
                            <label for="secure_case_folder_template" class="form-label fw-bold text-dark d-flex align-items-center justify-content-between">
                                <span>
                                    Ordner-Template für Fallakte
                                    <span class="text-danger">*</span>
                                </span>
                                <span class="badge bg-light text-muted border font-monospace">Standard: {YEAR}/{CITY}/{CASE_NUMBER}</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted font-monospace">uploads/secure/cases/</span>
                                <input type="text"
                                       class="form-control font-monospace"
                                       id="secure_case_folder_template"
                                       name="secure_case_folder_template"
                                       value="<?= htmlspecialchars($folderTemplate, ENT_QUOTES, 'UTF-8') ?>"
                                       placeholder="{YEAR}/{CITY}/{CASE_NUMBER}"
                                       maxlength="255"
                                       required
                                       title="Erlaubte Platzhalter: {YEAR}, {CITY}, {ORT}, {CASE_NUMBER}, {DATE}, {DATE_Y}, {DATE_YMD}, {AKTENZEICHEN}, {DEPARTMENT}. Schrägstriche / sind für Unterordner erlaubt.">
                                <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('secure_case_folder_template').value='{YEAR}/{CITY}/{CASE_NUMBER}';" title="Auf Standard zurücksetzen">
                                    Standard
                                </button>
                            </div>
                            <div class="form-text mt-1 text-muted small">
                                <i class="bi bi-info-circle me-1"></i>
                                Bestimmt den Ordnernamen jedes Vorgangs. Unterstützt Unterordner wie <code>{YEAR}/{CITY}/{CASE_NUMBER}</code>. Falls kein Ort ermittelt werden kann, wird <code>{CITY}</code> ohne doppelte Schrägstriche ausgelassen.
                            </div>
                        </div>

                        <!-- Dateinamen-Template -->
                        <div class="mb-4">
                            <label for="secure_case_warrant_filename_template" class="form-label fw-bold text-dark d-flex align-items-center justify-content-between">
                                <span>
                                    Dateinamen-Template für Editionsverfügung
                                    <span class="text-danger">*</span>
                                </span>
                                <span class="badge bg-light text-muted border font-monospace">Standard: {CASE_NUMBER}_Editionsverfuegung_{DATE}_v1.pdf</span>
                            </label>
                            <div class="input-group">
                                <input type="text"
                                       class="form-control font-monospace"
                                       id="secure_case_warrant_filename_template"
                                       name="secure_case_warrant_filename_template"
                                       value="<?= htmlspecialchars($filenameTemplate, ENT_QUOTES, 'UTF-8') ?>"
                                       placeholder="{CASE_NUMBER}_Editionsverfuegung_{DATE}_v1.pdf"
                                       maxlength="255"
                                       required
                                       title="Erlaubte Platzhalter: {CASE_NUMBER}, {YEAR}, {CITY}, {ORT}, {DATE}, {DATE_Y}, {DATE_YMD}, {AKTENZEICHEN}, {DEPARTMENT}. Keine Schrägstriche / oder \ erlaubt.">
                                <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('secure_case_warrant_filename_template').value='{CASE_NUMBER}_Editionsverfuegung_{DATE}_v1.pdf';" title="Auf Standard zurücksetzen">
                                    Standard
                                </button>
                            </div>
                            <div class="form-text mt-1 text-muted small">
                                <i class="bi bi-shield-exclamation me-1 text-warning"></i>
                                <strong>Sicherheitsregel:</strong> Innerhalb des Dateinamens sind Schrägstriche (<code>/</code>, <code>\</code>) unzulässig. Die Endung <code>.pdf</code> wird automatisch gesichert.
                            </div>
                        </div>

                        <!-- Live-Vorschau Box -->
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="fw-bold text-dark small text-uppercase">
                                    <i class="bi bi-eye me-1 text-primary"></i> Live-Vorschau (aktuelle Einstellung)
                                </span>
                                <span class="badge bg-secondary-subtle text-secondary small">Beispiel-Fall</span>
                            </div>
                            <div class="small mb-1">
                                <span class="text-muted d-block mb-1">Fall-Parameter: <code>ID: POL-2026-000123</code> | <code>Ort: Zürich</code> | <code>Fall Nr / Ref: ST.2026.4589</code> | <code>Behörde: Kantonspolizei Zürich</code></span>
                            </div>
                            <hr class="my-2 text-muted opacity-25">
                            <div class="row g-2 small font-monospace">
                                <div class="col-12 col-md-4 text-muted">Lokaler Pfad:</div>
                                <div class="col-12 col-md-8 text-break fw-bold text-dark">
                                    uploads/secure/cases/<span class="text-primary"><?= htmlspecialchars($previewFolder, ENT_QUOTES, 'UTF-8') ?></span>/<span class="text-success"><?= htmlspecialchars($previewFile, ENT_QUOTES, 'UTF-8') ?></span>
                                </div>

                                <div class="col-12 col-md-4 text-muted">SharePoint-Pfad:</div>
                                <div class="col-12 col-md-8 text-break fw-bold text-dark">
                                    <?= htmlspecialchars($previewSpPath, ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- E-Mail-Benachrichtigung an Sammeladresse -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-info-subtle text-info-emphasis p-2 rounded">
                                    <i class="bi bi-envelope-at fs-5"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0 fw-bold text-dark">E-Mail-Benachrichtigungen bei neuen Anträgen</h5>
                                    <small class="text-muted">Automatische Statusinformation an eine zentrale Sammeladresse</small>
                                </div>
                            </div>
                            <?php if ($notificationEmail !== '' && filter_var($notificationEmail, FILTER_VALIDATE_EMAIL)): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                    <i class="bi bi-check-circle me-1"></i> Aktiviert
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary-subtle text-secondary border">
                                    <i class="bi bi-bell-slash me-1"></i> Deaktiviert (leer)
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label for="secure_notification_email" class="form-label fw-bold text-dark">
                                Sammel-E-Mail für neue Sicherungsfälle
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted">
                                    <i class="bi bi-envelope"></i>
                                </span>
                                <input type="email"
                                       class="form-control"
                                       id="secure_notification_email"
                                       name="secure_notification_email"
                                       value="<?= htmlspecialchars($notificationEmail, ENT_QUOTES, 'UTF-8') ?>"
                                       placeholder="z. B. security@example.org"
                                       maxlength="255">
                            </div>
                            <div class="form-text mt-2 text-muted small">
                                <i class="bi bi-info-circle me-1 text-primary"></i>
                                An diese Adresse wird eine Benachrichtigung gesendet, wenn ein neuer Sicherungsantrag eingeht. Keine sensiblen Anhänge, nur Metadaten.
                            </div>
                        </div>

                        <div class="p-3 bg-light rounded border small text-muted">
                            <div class="d-flex align-items-start gap-2">
                                <i class="bi bi-shield-lock-fill text-success fs-6 mt-1"></i>
                                <div>
                                    <strong class="text-dark">Datenschutz &amp; Vertraulichkeit:</strong>
                                    Die Benachrichtigungs-E-Mail enthält ausschliesslich unkritische Vorgangs-Metadaten (Vorgangs-ID, Fall Nr / Aktenzeichen, antragstellende Behörde, Art der Sicherung und Datum). Die amtliche Editionsverfügung (PDF) und gesicherte Beweisdaten werden <strong>niemals per E-Mail versendet</strong>, sondern verbleiben ausschliesslich im geschützten internen Administrationsbereich.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Fristen für externen Datenraum (T+30 Sperre, T+60 Löschung) -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-warning-subtle text-warning-emphasis p-2 rounded">
                                    <i class="bi bi-hourglass-split fs-5"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0 fw-bold text-dark">Fristen &amp; Aufbewahrung für externen Datenraum</h5>
                                    <small class="text-muted">Automatische Zugangssperre und Datenlöschung nach Bereitstellung</small>
                                </div>
                            </div>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                <i class="bi bi-shield-check me-1"></i> DSGVO &amp; Revisionssicher
                            </span>
                        </div>
                    </div>
                    <div class="card-body p-4">

                        <p class="text-muted small mb-4">
                            Konfigurieren Sie die zeitgesteuerten Fristen für Sicherungsdaten ab dem Zeitpunkt der Bereitstellung (<code>available_at</code>). Die Bereinigung erfolgt automatisiert über den Scheduled Cron-Task oder manuell.
                        </p>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="secure_download_days_active" class="form-label fw-bold text-dark">
                                    Tage bis Sperrung des externen Zugangs (z. B. 30)
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted">
                                        <i class="bi bi-lock"></i>
                                    </span>
                                    <input type="number"
                                           class="form-control"
                                           id="secure_download_days_active"
                                           name="secure_download_days_active"
                                           value="<?= htmlspecialchars((string)$daysActive, ENT_QUOTES, 'UTF-8') ?>"
                                           min="1"
                                           max="365"
                                           required>
                                    <span class="input-group-text bg-light text-muted">Tage (T+X)</span>
                                </div>
                                <div class="form-text mt-2 text-muted small">
                                    Anzahl Tage nach Bereitstellung, nach denen der behördliche Download-Zugang automatisch gesperrt wird (<code>download_enabled = 0</code>). Standard: 30 Tage.
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="secure_download_days_delete" class="form-label fw-bold text-dark">
                                    Tage bis Löschung der Sicherungsdaten (z. B. 60)
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted">
                                        <i class="bi bi-trash3"></i>
                                    </span>
                                    <input type="number"
                                           class="form-control"
                                           id="secure_download_days_delete"
                                           name="secure_download_days_delete"
                                           value="<?= htmlspecialchars((string)$daysDelete, ENT_QUOTES, 'UTF-8') ?>"
                                           min="1"
                                           max="730"
                                           required>
                                    <span class="input-group-text bg-light text-muted">Tage (T+Y)</span>
                                </div>
                                <div class="form-text mt-2 text-muted small">
                                    Anzahl Tage nach Bereitstellung, nach denen alle Sicherungsdateien unwiderruflich vom Server gelöscht und der Fallstatus aktualisiert werden. Muss &ge; Sperrfrist sein. Standard: 60 Tage.
                                </div>
                            </div>
                        </div>

                        <!-- Info-Box Lebenszyklus -->
                        <div class="p-3 bg-light rounded border mb-3 small">
                            <strong class="text-dark d-block mb-2">Automatisierter Lebenszyklus im Datenraum:</strong>
                            <div class="d-flex flex-column gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-success-subtle text-success border px-2 py-1">T+0</span>
                                    <span>Bereitstellung durch Technik &middot; Externer Datenraum wird für Polizei aktiv geschaltet &amp; <code>available_at</code> erfasst.</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-warning-subtle text-warning border px-2 py-1">T+<?= $daysActive ?></span>
                                    <span>Fristablauf Download &middot; Freigabelink wird deaktiviert, Polizei erhält keinen Zugriff mehr.</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-danger-subtle text-danger border px-2 py-1">T+<?= $daysDelete ?></span>
                                    <span>Datensparsamkeit &amp; Aufbewahrung &middot; Beweismittel-Dateien werden physisch vom Datenträger gelöscht, Fallstatus auf „Abgeschlossen“ gesetzt, Audit-Log verfasst.</span>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 pt-2 border-top">
                            <small class="text-muted">
                                <i class="bi bi-terminal me-1"></i> Cron-Befehl für Server (z. B. täglich 03:00 Uhr):
                                <code class="text-dark">0 3 * * * php bin/secure-retention-cron.php</code>
                            </small>
                        </div>

                    </div>
                </div>

                <!-- Video-Antrag Dropdown-Auswahllisten (PRO OBJEKT konfigurierbar) -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-primary-subtle text-primary p-2 rounded">
                                    <i class="bi bi-camera-video fs-5"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0 fw-bold text-dark">Video-Antragsoptionen (Pro Objekt konfigurierbar)</h5>
                                    <small class="text-muted">Stockwerke, Farben und Parkplätze individuell pro Liegenschaft/Objekt festlegen</small>
                                </div>
                            </div>
                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                <i class="bi bi-layers-half me-1"></i> Pro Objekt aktiv
                            </span>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <p class="text-muted small mb-3">
                            Hier konfigurieren Sie für jedes <strong>Objekt / Gebäude</strong> die spezifischen Stockwerke, Farben (Sektoren) und Parkplatz-Nummern. 
                            Wählt der Antragsteller im öffentlichen Formular ein Objekt aus, passen sich die Stockwerk-, Farb- und Parkplatz-Dropdowns dynamisch an das jeweilige Objekt an.
                        </p>

                        <!-- Objekt Nav-Pills -->
                        <ul class="nav nav-pills gap-2 p-2 bg-light rounded border mb-4 flex-wrap align-items-center" id="objectConfigTabs" role="tablist">
                            <?php foreach ($videoObjectsConfig as $idx => $obj): ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link <?= $idx === 0 ? 'active' : '' ?> fw-bold" 
                                            id="tab-btn-<?= $idx ?>" 
                                            data-bs-toggle="pill" 
                                            data-bs-target="#tab-pane-<?= $idx ?>" 
                                            type="button" 
                                            role="tab">
                                        <i class="bi bi-building me-1"></i>
                                        <span class="tab-label"><?= htmlspecialchars($obj['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </button>
                                </li>
                            <?php endforeach; ?>
                            <li class="nav-item ms-auto">
                                <button type="button" class="btn btn-sm btn-outline-success fw-bold px-3 py-2" onclick="addNewObjectTab()">
                                    <i class="bi bi-plus-circle-fill me-1"></i> Neues Objekt hinzufügen
                                </button>
                            </li>
                        </ul>

                        <!-- Tab Panes für jedes Objekt -->
                        <div class="tab-content" id="objectConfigTabContent">
                            <?php foreach ($videoObjectsConfig as $idx => $obj): ?>
                                <div class="tab-pane fade <?= $idx === 0 ? 'show active' : '' ?> object-pane" id="tab-pane-<?= $idx ?>" role="tabpanel">
                                    <div class="p-3 bg-white rounded border mb-4">
                                        <div class="row align-items-end g-3">
                                            <div class="col-md-8">
                                                <label class="form-label fw-bold text-dark small text-uppercase mb-1">
                                                    <i class="bi bi-building text-primary me-1"></i> Objekt-Bezeichnung (Liegenschaft / Areal)
                                                </label>
                                                <input type="hidden" name="video_objects[<?= $idx ?>][id]" value="<?= htmlspecialchars($obj['id'], ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="text" 
                                                       class="form-control form-control-lg fw-bold" 
                                                       id="obj_name_<?= $idx ?>"
                                                       name="video_objects[<?= $idx ?>][name]" 
                                                       value="<?= htmlspecialchars($obj['name'], ENT_QUOTES, 'UTF-8') ?>" 
                                                       required
                                                       oninput="updateTabLabel(<?= $idx ?>, this.value)"
                                                       placeholder="z. B. Hauptgebäude / Areal Nord">
                                            </div>
                                            <div class="col-md-4 text-md-end">
                                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeObjectTab(<?= $idx ?>)">
                                                    <i class="bi bi-trash3-fill me-1"></i> Dieses Objekt löschen
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row g-4">
                                        <!-- Stockwerke -->
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold text-dark d-flex align-items-center justify-content-between">
                                                <span><i class="bi bi-layers text-primary me-1"></i> Stockwerke / Ebenen</span>
                                                <span class="badge bg-light text-muted border">1 pro Zeile</span>
                                            </label>
                                            <textarea class="form-control font-monospace small" 
                                                      name="video_objects[<?= $idx ?>][floors]" 
                                                      rows="8" 
                                                      placeholder="Erdgeschoss (EG)&#10;1. Obergeschoss (+1)&#10;2. Obergeschoss (+2)"><?= htmlspecialchars(SecurePortalConfig::formatLines($obj['floors']), ENT_QUOTES, 'UTF-8') ?></textarea>
                                            <div class="form-text small text-muted">Etagen speziell für dieses Gebäude.</div>
                                        </div>

                                        <!-- Farben -->
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold text-dark d-flex align-items-center justify-content-between">
                                                <span><i class="bi bi-palette text-primary me-1"></i> Farben / Sektoren</span>
                                                <span class="badge bg-light text-muted border">1 pro Zeile</span>
                                            </label>
                                            <textarea class="form-control font-monospace small" 
                                                      name="video_objects[<?= $idx ?>][colors]" 
                                                      rows="8" 
                                                      placeholder="Blau (Sektor A)&#10;Gelb (Sektor B)&#10;Rot (Sektor C)"><?= htmlspecialchars(SecurePortalConfig::formatLines($obj['colors']), ENT_QUOTES, 'UTF-8') ?></textarea>
                                            <div class="form-text small text-muted">Sektorenfarben für dieses Gebäude.</div>
                                        </div>

                                        <!-- Parkplatz Nummern -->
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold text-dark d-flex align-items-center justify-content-between">
                                                <span><i class="bi bi-p-square text-primary me-1"></i> Parkplatz-Nummern</span>
                                                <span class="badge bg-light text-muted border">1 pro Zeile</span>
                                            </label>
                                            <textarea class="form-control font-monospace small" 
                                                      name="video_objects[<?= $idx ?>][parking_spaces]" 
                                                      rows="8" 
                                                      placeholder="Parkplatz 01&#10;Parkplatz 02&#10;Besucherparkplatz"><?= htmlspecialchars(SecurePortalConfig::formatLines($obj['parking_spaces']), ENT_QUOTES, 'UTF-8') ?></textarea>
                                            <div class="form-text small text-muted">Vorschläge für die freie Eingabe im Formular (kein Zwang).</div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- SharePoint Konfiguration -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-success-subtle text-success p-2 rounded">
                                    <i class="bi bi-cloud-arrow-up fs-5"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0 fw-bold text-dark">SharePoint &amp; Microsoft Graph Anbindung</h5>
                                    <small class="text-muted">Client-Credentials Flow zur revisionssicheren PDF-Archivierung</small>
                                </div>
                            </div>
                            <?php if ($isSpConfigured): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                    <i class="bi bi-check-circle me-1"></i> Aktiv &amp; bereit
                                </span>
                            <?php else: ?>
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                    <i class="bi bi-exclamation-circle me-1"></i> Nicht vollständig
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body p-4">

                        <p class="text-muted small mb-3">
                            Parameter können entweder über die nachfolgenden Felder oder sicher außerhalb des Repositories über <code>config/config.php</code> bzw. Environment-Variablen gepflegt werden.
                        </p>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="sp_tenant_id" class="form-label small fw-bold text-dark">Microsoft 365 Tenant ID</label>
                                <input type="text"
                                       class="form-control font-monospace form-control-sm"
                                       id="sp_tenant_id"
                                       name="sp_tenant_id"
                                       value="<?= htmlspecialchars(SharePointConfig::getTenantId(), ENT_QUOTES, 'UTF-8') ?>"
                                       placeholder="z. B. 8a7b6c5d-4e3f-2a1b-0c9d-8e7f6a5b4c3d">
                            </div>
                            <div class="col-md-6">
                                <label for="sp_client_id" class="form-label small fw-bold text-dark">App / Client ID</label>
                                <input type="text"
                                       class="form-control font-monospace form-control-sm"
                                       id="sp_client_id"
                                       name="sp_client_id"
                                       value="<?= htmlspecialchars(SharePointConfig::getClientId(), ENT_QUOTES, 'UTF-8') ?>"
                                       placeholder="z. B. 12345678-abcd-ef01-2345-6789abcdef01">
                            </div>
                            <div class="col-md-6">
                                <label for="sp_client_secret" class="form-label small fw-bold text-dark">
                                    Client Secret
                                    <?php if (SharePointConfig::getClientSecret() !== ''): ?>
                                        <span class="text-success ms-1 small"><i class="bi bi-check2"></i> (Gesetzt)</span>
                                    <?php endif; ?>
                                </label>
                                <input type="password"
                                       class="form-control font-monospace form-control-sm"
                                       id="sp_client_secret"
                                       name="sp_client_secret"
                                       value="<?= htmlspecialchars(SharePointConfig::getClientSecret(), ENT_QUOTES, 'UTF-8') ?>"
                                       placeholder="••••••••••••••••••••"
                                       autocomplete="new-password">
                            </div>
                            <div class="col-md-6">
                                <label for="sp_base_folder" class="form-label small fw-bold text-dark">Basisordner in Dokumentbibliothek</label>
                                <input type="text"
                                       class="form-control font-monospace form-control-sm"
                                       id="sp_base_folder"
                                       name="sp_base_folder"
                                       value="<?= htmlspecialchars(SharePointConfig::getBaseFolder(), ENT_QUOTES, 'UTF-8') ?>"
                                       placeholder="Vorgaenge">
                            </div>
                            <div class="col-md-6">
                                <label for="sp_site_id" class="form-label small fw-bold text-dark">SharePoint Site ID oder Hostname/Path</label>
                                <input type="text"
                                       class="form-control font-monospace form-control-sm"
                                       id="sp_site_id"
                                       name="sp_site_id"
                                       value="<?= htmlspecialchars(SharePointConfig::getSiteId(), ENT_QUOTES, 'UTF-8') ?>"
                                       placeholder="tenant.sharepoint.com:/sites/Sicherungsportal:">
                            </div>
                            <div class="col-md-6">
                                <label for="sp_drive_id" class="form-label small fw-bold text-dark">Drive ID / Dokumentbibliothek-ID</label>
                                <input type="text"
                                       class="form-control font-monospace form-control-sm"
                                       id="sp_drive_id"
                                       name="sp_drive_id"
                                       value="<?= htmlspecialchars(SharePointConfig::getDriveId(), ENT_QUOTES, 'UTF-8') ?>"
                                       placeholder="b!abcdef... oder leer für Standard-Drive">
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Speichern-Button -->
                <div class="mt-4 d-flex justify-content-end gap-2">
                    <a href="?route=admin/secure/cases" class="btn btn-outline-secondary">
                        Abbrechen
                    </a>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-save me-1"></i> Einstellungen speichern
                    </button>
                </div>

            </div>

            <!-- Rechte Spalte: Platzhalter-Legende & Dokumentation -->
            <div class="col-lg-4">

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-tags text-primary"></i> Erlaubte Platzhalter
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        <p class="text-muted small mb-3">
                            Klicken Sie auf einen Platzhalter, um ihn an das Dateinamen-Template anzuhängen:
                        </p>
                        <div class="list-group list-group-flush small">
                            <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-start cursor-pointer"
                                 onclick="appendPlaceholder('{CASE_NUMBER}')" role="button" title="Klicken zum Einfügen">
                                <div>
                                    <span class="badge bg-light text-primary border font-monospace me-1">{CASE_NUMBER}</span>
                                    <div class="text-muted small mt-1">Offizielle Vorgangs-ID (z. B. <code>POL-2026-000123</code>)</div>
                                </div>
                                <i class="bi bi-plus-circle text-primary"></i>
                            </div>

                            <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-start cursor-pointer"
                                 onclick="appendPlaceholder('{YEAR}')" role="button" title="Klicken zum Einfügen">
                                <div>
                                    <span class="badge bg-light text-primary border font-monospace me-1">{YEAR}</span>
                                    <span class="badge bg-light text-muted border font-monospace">{DATE_Y}</span>
                                    <div class="text-muted small mt-1">Vierstellige Jahreszahl des Vorgangs (z. B. <code>2026</code>)</div>
                                </div>
                                <i class="bi bi-plus-circle text-primary"></i>
                            </div>

                            <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-start cursor-pointer"
                                 onclick="appendPlaceholder('{CITY}')" role="button" title="Klicken zum Einfügen">
                                <div>
                                    <span class="badge bg-light text-primary border font-monospace me-1">{CITY}</span>
                                    <span class="badge bg-light text-muted border font-monospace">{ORT}</span>
                                    <div class="text-muted small mt-1">Ort der Dienststelle oder Fallort bereinigt (z. B. <code>Zuerich</code>)</div>
                                </div>
                                <i class="bi bi-plus-circle text-primary"></i>
                            </div>

                            <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-start cursor-pointer"
                                 onclick="appendPlaceholder('{DATE}')" role="button" title="Klicken zum Einfügen">
                                <div>
                                    <span class="badge bg-light text-primary border font-monospace me-1">{DATE}</span>
                                    <div class="text-muted small mt-1">Aktuelles Datum im ISO-Format (z. B. <code>2026-09-26</code>)</div>
                                </div>
                                <i class="bi bi-plus-circle text-primary"></i>
                            </div>

                            <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-start cursor-pointer"
                                 onclick="appendPlaceholder('{DATE_YMD}')" role="button" title="Klicken zum Einfügen">
                                <div>
                                    <span class="badge bg-light text-primary border font-monospace me-1">{DATE_YMD}</span>
                                    <div class="text-muted small mt-1">Kompaktes Datum <code>YYYYMMDD</code> (z. B. <code>20260926</code>)</div>
                                </div>
                                <i class="bi bi-plus-circle text-primary"></i>
                            </div>

                            <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-start cursor-pointer"
                                 onclick="appendPlaceholder('{AKTENZEICHEN}')" role="button" title="Klicken zum Einfügen">
                                <div>
                                    <span class="badge bg-light text-primary border font-monospace me-1">{AKTENZEICHEN}</span>
                                    <span class="badge bg-light text-muted border font-monospace">{FALL_NR}</span>
                                    <div class="text-muted small mt-1">Fall Nr / Aktenzeichen bereinigt (z. B. <code>ST_2026_4589</code>)</div>
                                </div>
                                <i class="bi bi-plus-circle text-primary"></i>
                            </div>

                            <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-start cursor-pointer"
                                 onclick="appendPlaceholder('{DEPARTMENT}')" role="button" title="Klicken zum Einfügen">
                                <div>
                                    <span class="badge bg-light text-primary border font-monospace me-1">{DEPARTMENT}</span>
                                    <div class="text-muted small mt-1">Dienststelle bereinigt (z. B. <code>Kantonspolizei_Zuerich</code>)</div>
                                </div>
                                <i class="bi bi-plus-circle text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sicherheitsregeln -->
                <div class="card border-0 shadow-sm bg-light mb-4">
                    <div class="card-body p-3">
                        <h6 class="fw-bold text-dark mb-2 small text-uppercase">
                            <i class="bi bi-shield-check text-success me-1"></i> Automatische Bereinigung
                        </h6>
                        <ul class="text-muted small ps-3 mb-0">
                            <li>Umlaute (ä, ö, ü, ß) werden standardkonform transliteriert (ae, oe, ue, ss).</li>
                            <li>Sonderzeichen und Leerzeichen werden in Unterstriche <code>_</code> umgewandelt.</li>
                            <li>Pfadmanipulationen (<code>../</code>, Null-Bytes) werden strikt abgewehrt.</li>
                            <li>Dateinamen behalten immer garantiert die Dateiendung <code>.pdf</code>.</li>
                            <li>E-Mails an die Sammeladresse enthalten ausschliesslich Metadaten (keine PDFs oder Passwörter).</li>
                        </ul>
                    </div>
                </div>

                <!-- Manuelle Fristenprüfung durchführen -->
                <div class="card border-0 shadow-sm border-danger-subtle">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold text-danger d-flex align-items-center gap-2">
                            <i class="bi bi-clock-history"></i> Manuelle Fristenprüfung
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        <p class="text-muted small mb-3">
                            Führt die Prüfung auf abgelaufene Zugänge (T+<?= $daysActive ?>) und fällige Datenlöschungen (T+<?= $daysDelete ?>) sofort aus.
                        </p>
                        <button type="submit"
                                formaction="?route=admin/secure/retention/run"
                                class="btn btn-outline-danger btn-sm w-100 fw-semibold"
                                onclick="return confirm('Möchten Sie die Fristenprüfung und Dateibereinigung jetzt sofort ausführen?');">
                            <i class="bi bi-play-circle me-1"></i> Fristen-Bereinigung jetzt ausführen
                        </button>
                    </div>
                </div>

            </div>

        </div>
    </form>

</div>

<script>
function appendPlaceholder(tag) {
    var input = document.getElementById('secure_case_warrant_filename_template');
    if (!input) return;
    var val = input.value;
    // Vor .pdf einfügen falls vorhanden
    if (val.toLowerCase().endsWith('.pdf')) {
        var base = val.substring(0, val.length - 4);
        input.value = base + '_' + tag + '.pdf';
    } else {
        input.value = val + '_' + tag;
    }
    input.focus();
}

var objectIndexCounter = <?= count($videoObjectsConfig) ?>;

function updateTabLabel(idx, val) {
    var btn = document.getElementById('tab-btn-' + idx);
    if (btn) {
        var span = btn.querySelector('.tab-label');
        if (span) {
            span.textContent = val.trim() !== '' ? val.trim() : 'Unbenanntes Objekt';
        }
    }
}

function removeObjectTab(idx) {
    var allPanes = document.querySelectorAll('.object-pane');
    if (allPanes.length <= 1) {
        alert('Mindestens ein Objekt muss in der Konfiguration verbleiben.');
        return;
    }
    if (!confirm('Möchten Sie dieses Objekt samt allen spezifischen Stockwerken, Farben und Parkplätzen wirklich entfernen?')) {
        return;
    }
    var btn = document.getElementById('tab-btn-' + idx);
    var li = btn ? btn.closest('li') : null;
    var pane = document.getElementById('tab-pane-' + idx);

    var wasActive = btn && btn.classList.contains('active');
    if (li) li.remove();
    if (pane) pane.remove();

    if (wasActive) {
        var firstRemainingBtn = document.querySelector('#objectConfigTabs .nav-link:not(.ms-auto button)');
        if (firstRemainingBtn && typeof bootstrap !== 'undefined' && bootstrap.Tab) {
            var trigger = new bootstrap.Tab(firstRemainingBtn);
            trigger.show();
        }
    }
}

function addNewObjectTab() {
    var idx = objectIndexCounter++;
    var defaultName = 'Neues Objekt ' + (idx + 1);

    // Tab Button
    var tabsList = document.getElementById('objectConfigTabs');
    var addBtnLi = tabsList.querySelector('li.ms-auto');

    var newLi = document.createElement('li');
    newLi.className = 'nav-item';
    newLi.setAttribute('role', 'presentation');
    newLi.innerHTML = '<button class="nav-link fw-bold" id="tab-btn-' + idx + '" data-bs-toggle="pill" data-bs-target="#tab-pane-' + idx + '" type="button" role="tab">' +
        '<i class="bi bi-building me-1"></i> <span class="tab-label">' + defaultName + '</span>' +
        '</button>';
    tabsList.insertBefore(newLi, addBtnLi);

    // Tab Pane
    var content = document.getElementById('objectConfigTabContent');
    var newPane = document.createElement('div');
    newPane.className = 'tab-pane fade object-pane';
    newPane.id = 'tab-pane-' + idx;
    newPane.setAttribute('role', 'tabpanel');
    newPane.innerHTML = 
        '<div class="p-3 bg-white rounded border mb-4">' +
            '<div class="row align-items-end g-3">' +
                '<div class="col-md-8">' +
                    '<label class="form-label fw-bold text-dark small text-uppercase mb-1">' +
                        '<i class="bi bi-building text-primary me-1"></i> Objekt-Bezeichnung (Liegenschaft / Areal)' +
                    '</label>' +
                    '<input type="hidden" name="video_objects[' + idx + '][id]" value="obj_' + (idx + 1) + '">' +
                    '<input type="text" class="form-control form-control-lg fw-bold" id="obj_name_' + idx + '" name="video_objects[' + idx + '][name]" value="' + defaultName + '" required oninput="updateTabLabel(' + idx + ', this.value)" placeholder="z. B. Filiale West">' +
                '</div>' +
                '<div class="col-md-4 text-md-end">' +
                    '<button type="button" class="btn btn-outline-danger btn-sm" onclick="removeObjectTab(' + idx + ')">' +
                        '<i class="bi bi-trash3-fill me-1"></i> Dieses Objekt löschen' +
                    '</button>' +
                '</div>' +
            '</div>' +
        '</div>' +
        '<div class="row g-4">' +
            '<div class="col-md-4">' +
                '<label class="form-label fw-bold text-dark d-flex align-items-center justify-content-between">' +
                    '<span><i class="bi bi-layers text-primary me-1"></i> Stockwerke / Ebenen</span>' +
                    '<span class="badge bg-light text-muted border">1 pro Zeile</span>' +
                '</label>' +
                '<textarea class="form-control font-monospace small" name="video_objects[' + idx + '][floors]" rows="8" placeholder="Erdgeschoss (EG)&#10;1. Obergeschoss (+1)">Erdgeschoss (EG)\n1. Obergeschoss (+1)\n2. Obergeschoss (+2)</textarea>' +
                '<div class="form-text small text-muted">Etagen speziell für dieses Gebäude.</div>' +
            '</div>' +
            '<div class="col-md-4">' +
                '<label class="form-label fw-bold text-dark d-flex align-items-center justify-content-between">' +
                    '<span><i class="bi bi-palette text-primary me-1"></i> Farben / Sektoren</span>' +
                    '<span class="badge bg-light text-muted border">1 pro Zeile</span>' +
                '</label>' +
                '<textarea class="form-control font-monospace small" name="video_objects[' + idx + '][colors]" rows="8" placeholder="Blau&#10;Gelb&#10;Rot">Blau\nGelb\nRot\nKeine Farbzuordnung</textarea>' +
                '<div class="form-text small text-muted">Sektorenfarben für dieses Gebäude.</div>' +
            '</div>' +
            '<div class="col-md-4">' +
                '<label class="form-label fw-bold text-dark d-flex align-items-center justify-content-between">' +
                    '<span><i class="bi bi-p-square text-primary me-1"></i> Parkplatz-Nummern</span>' +
                    '<span class="badge bg-light text-muted border">1 pro Zeile</span>' +
                '</label>' +
                '<textarea class="form-control font-monospace small" name="video_objects[' + idx + '][parking_spaces]" rows="8" placeholder="Parkplatz 01&#10;Parkplatz 02">Parkplatz 01\nParkplatz 02\nParkplatz 03\nBesucherparkplatz</textarea>' +
                '<div class="form-text small text-muted">Parkplätze speziell für diese Liegenschaft.</div>' +
            '</div>' +
        '</div>';
    content.appendChild(newPane);

    if (typeof bootstrap !== 'undefined' && bootstrap.Tab) {
        var trigger = new bootstrap.Tab(document.getElementById('tab-btn-' + idx));
        trigger.show();
    }
    var inp = document.getElementById('obj_name_' + idx);
    if (inp) {
        inp.focus();
        inp.select();
    }
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../../../views/layouts/admin.php';
