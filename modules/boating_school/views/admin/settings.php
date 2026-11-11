<?php

declare(strict_types=1);

/**
 * Bootsfahrschule - E-Mail-Kommunikation & Benachrichtigungen
 *
 * @var array<string, string> $settings
 * @var bool $canManage
 */

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$title = 'E-Mail & Einstellungen - Nautic';
$currentRoute = 'admin/boating/settings';
$csrfToken = class_exists('Csrf') ? Csrf::getToken() : '';

// Aktiver Tab
$activeTab = !empty($_GET['tab']) ? trim((string) $_GET['tab']) : 'general';

ob_start();
?>

<!-- Flash Meldungen -->
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

<!-- Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="?route=admin/boating">Nautic</a></li>
                <li class="breadcrumb-item active" aria-current="page">E-Mail &amp; Einstellungen</li>
            </ol>
        </nav>
        <h1 class="h3 fw-bold text-dark mb-0">
            <i class="bi bi-gear-fill text-primary me-2"></i>E-Mail &amp; Einstellungen
        </h1>
        <p class="text-muted small mb-0">
            Konfiguration von Absenderadressen, Ereignis-Benachrichtigungen, Textvorlagen und automatischen Terminerinnerungen.
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <div class="dropdown">
            <button class="btn btn-outline-dark btn-sm dropdown-toggle d-flex align-items-center gap-1 rounded-3 px-3" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-list fs-6"></i> Nautic Menü
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 p-2" style="min-width: 240px; z-index: 1060;">
                <li><a class="dropdown-item rounded-2 py-2" href="?route=admin/boating"><i class="bi bi-compass me-2 text-primary"></i>Übersicht &amp; Flotte</a></li>
                <li><a class="dropdown-item rounded-2 py-2" href="?route=admin/boating/calendar"><i class="bi bi-calendar3 me-2 text-info"></i>Kalender &amp; Disposition</a></li>
                <li><a class="dropdown-item rounded-2 py-2" href="?route=admin/boating/boats"><i class="bi bi-tsunami me-2"></i>Boote &amp; Vermietung</a></li>
                <li><a class="dropdown-item rounded-2 py-2" href="?route=admin/boating/instructors"><i class="bi bi-person-badge me-2 text-warning"></i>Fahrlehrer &amp; Skipper</a></li>
                <li><a class="dropdown-item rounded-2 py-2" href="?route=admin/boating/services"><i class="bi bi-tag me-2 text-danger"></i>Leistungen &amp; Tarife</a></li>
                <li><a class="dropdown-item rounded-2 py-2" href="?route=admin/boating/customers"><i class="bi bi-people me-2 text-success"></i>Kunden CRM</a></li>
                <li><a class="dropdown-item rounded-2 py-2" href="?route=admin/boating/reports"><i class="bi bi-graph-up-arrow me-2 text-secondary"></i>Auswertungen &amp; Payroll</a></li>
                <li><a class="dropdown-item rounded-2 py-2 active" href="?route=admin/boating/settings"><i class="bi bi-gear me-2"></i>Einstellungen</a></li>
            </ul>
        </div>
        <button type="button" class="btn btn-outline-primary btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#testMailModal">
            <i class="bi bi-send me-1"></i> Test-E-Mail senden
        </button>
        <form method="POST" action="?route=admin/boating/reminders/send" class="d-inline" onsubmit="return confirm('Möchten Sie den Erinnerungslauf für alle anstehenden Termine jetzt manuell ausführen?');">
            <?php if ($csrfToken): ?>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <?php endif; ?>
            <button type="submit" class="btn btn-outline-info btn-sm shadow-sm">
                <i class="bi bi-alarm me-1"></i> Erinnerungslauf starten
            </button>
        </form>
    </div>
</div>

<!-- Hauptformular -->
<form method="POST" action="?route=admin/boating/settings">
    <?php if ($csrfToken): ?>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>
    <input type="hidden" name="active_tab" id="activeTabInput" value="<?= htmlspecialchars($activeTab, ENT_QUOTES, 'UTF-8') ?>">

    <!-- Tabs Navigation -->
    <ul class="nav nav-pills mb-4 gap-2 bg-white p-2 rounded shadow-sm border" id="settingsTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= $activeTab === 'general' ? 'active' : '' ?> fw-semibold" id="tab-general-btn" data-bs-toggle="pill" data-bs-target="#tab-general" type="button" role="tab" onclick="document.getElementById('activeTabInput').value='general';">
                <i class="bi bi-sliders me-1"></i> 1. Absender &amp; Allgemein
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= $activeTab === 'toggles' ? 'active' : '' ?> fw-semibold" id="tab-toggles-btn" data-bs-toggle="pill" data-bs-target="#tab-toggles" type="button" role="tab" onclick="document.getElementById('activeTabInput').value='toggles';">
                <i class="bi bi-toggle-on me-1"></i> 2. Benachrichtigungs-Toggles
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= $activeTab === 'templates' ? 'active' : '' ?> fw-semibold" id="tab-templates-btn" data-bs-toggle="pill" data-bs-target="#tab-templates" type="button" role="tab" onclick="document.getElementById('activeTabInput').value='templates';">
                <i class="bi bi-file-earmark-text me-1"></i> 3. E-Mail-Vorlagen (Templates)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= $activeTab === 'cron' ? 'active' : '' ?> fw-semibold" id="tab-cron-btn" data-bs-toggle="pill" data-bs-target="#tab-cron" type="button" role="tab" onclick="document.getElementById('activeTabInput').value='cron';">
                <i class="bi bi-clock-history me-1"></i> 4. Cronjob &amp; Automatisierung
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= $activeTab === 'locations' ? 'active' : '' ?> fw-semibold" id="tab-locations-btn" data-bs-toggle="pill" data-bs-target="#tab-locations" type="button" role="tab" onclick="document.getElementById('activeTabInput').value='locations';">
                <i class="bi bi-geo-alt-fill me-1 text-danger"></i> 5. Häfen &amp; Standorte
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="settingsTabContent">

        <!-- =================================================================== -->
        <!-- TAB 1: ABSENDER & ALLGEMEIN -->
        <!-- =================================================================== -->
        <div class="tab-pane fade <?= $activeTab === 'general' ? 'show active' : '' ?>" id="tab-general" role="tabpanel">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light py-3">
                    <h5 class="card-title fw-bold text-dark mb-0">
                        <i class="bi bi-envelope-at text-primary me-2"></i>Absender-Informationen &amp; Adressen
                    </h5>
                    <small class="text-muted">Legen Sie fest, unter welchem Namen und welcher E-Mail-Adresse Benachrichtigungen versendet werden.</small>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                Absender-Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="boating_mail_from_name" class="form-control"
                                   value="<?= htmlspecialchars($settings['boating_mail_from_name'] ?? 'Deine Bootsfahrschule', ENT_QUOTES, 'UTF-8') ?>" required>
                            <div class="form-text">Name, der im E-Mail-Programm des Kunden als Absender erscheint (z.B. <code>Bootsfahrschule Vierwaldstättersee</code>).</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                Absender E-Mail-Adresse (From) <span class="text-danger">*</span>
                            </label>
                            <input type="email" name="boating_mail_from_address" class="form-control"
                                   value="<?= htmlspecialchars($settings['boating_mail_from_address'] ?? 'info@bootschule.ch', ENT_QUOTES, 'UTF-8') ?>" required>
                            <div class="form-text">Offizielle Absenderadresse (z.B. <code>info@bootschule.ch</code>).</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                Antwort-Adresse (Reply-To) <span class="badge bg-secondary">Optional</span>
                            </label>
                            <input type="email" name="boating_mail_reply_to" class="form-control"
                                   placeholder="antworten@bootschule.ch"
                                   value="<?= htmlspecialchars($settings['boating_mail_reply_to'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <div class="form-text">Kundenantworten gehen an diese Adresse, falls sie vom Absender abweicht.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                Admin-Empfänger (Schule) <span class="text-danger">*</span>
                            </label>
                            <input type="email" name="boating_mail_admin_address" class="form-control"
                                   value="<?= htmlspecialchars($settings['boating_mail_admin_address'] ?? 'info@bootschule.ch', ENT_QUOTES, 'UTF-8') ?>" required>
                            <div class="form-text">E-Mail-Adresse der Schule/Disposition für Benachrichtigungen über neue Buchungen und Stornierungen.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                Admin BCC-Kopie (Blind Carbon Copy) <span class="badge bg-secondary">Optional</span>
                            </label>
                            <input type="email" name="boating_mail_bcc_admin" class="form-control"
                                   placeholder="archiv@bootschule.ch"
                                   value="<?= htmlspecialchars($settings['boating_mail_bcc_admin'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <div class="form-text">Erhält von jeder an den Kunden gesendeten Bestätigung automatisch eine unsichtbare Kopie.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                Terminerinnerung: Vorlaufzeit (Stunden) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="number" min="1" max="168" step="1" name="boating_reminder_hours_before" class="form-control"
                                       value="<?= (int) ($settings['boating_reminder_hours_before'] ?? 24) ?>" required>
                                <span class="input-group-text">Stunden vor Termin</span>
                            </div>
                            <div class="form-text">Empfohlen: <code>24</code> (ein Tag vorher) oder <code>48</code> (zwei Tage vorher).</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- TAB 2: BENACHRICHTIGUNGS-TOGGLES -->
        <!-- =================================================================== -->
        <div class="tab-pane fade <?= $activeTab === 'toggles' ? 'show active' : '' ?>" id="tab-toggles" role="tabpanel">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light py-3">
                    <h5 class="card-title fw-bold text-dark mb-0">
                        <i class="bi bi-toggle-on text-primary me-2"></i>Automatische Benachrichtigungen aktivieren / deaktivieren
                    </h5>
                    <small class="text-muted">Hier bestimmen Sie, bei welchen Ereignissen E-Mails automatisch versendet werden.</small>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">

                        <!-- Kunden-Ereignisse -->
                        <div class="col-lg-6">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="bi bi-person-circle me-1"></i> Kunden-Benachrichtigungen
                            </h6>

                            <div class="card border p-3 mb-3 bg-light">
                                <div class="form-check form-switch fs-6 mb-1">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                           name="boating_notify_customer_booking_created" id="toggle_cust_created" value="1"
                                           <?= !empty($settings['boating_notify_customer_booking_created']) ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-bold text-dark" for="toggle_cust_created">
                                        Buchungs- / Eingangseingangsbestätigung
                                    </label>
                                </div>
                                <p class="text-muted small mb-0 ps-4">
                                    Versendet sofort eine Bestätigung an den Kunden, wenn über das Online-Portal oder im Kalender ein Termin angelegt wird.
                                </p>
                            </div>

                            <div class="card border p-3 mb-3 bg-light">
                                <div class="form-check form-switch fs-6 mb-1">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                           name="boating_notify_customer_booking_updated" id="toggle_cust_updated" value="1"
                                           <?= !empty($settings['boating_notify_customer_booking_updated']) ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-bold text-dark" for="toggle_cust_updated">
                                        Terminänderung &amp; Verschiebung
                                    </label>
                                </div>
                                <p class="text-muted small mb-0 ps-4">
                                    Informiert den Kunden bei Datums- oder Uhrzeitänderungen (z.B. Verschieben per Drag &amp; Drop im Dispo-Kalender).
                                </p>
                            </div>

                            <div class="card border p-3 mb-3 bg-light">
                                <div class="form-check form-switch fs-6 mb-1">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                           name="boating_notify_customer_booking_cancelled" id="toggle_cust_cancelled" value="1"
                                           <?= !empty($settings['boating_notify_customer_booking_cancelled']) ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-bold text-dark" for="toggle_cust_cancelled">
                                        Stornierungsbestätigung
                                    </label>
                                </div>
                                <p class="text-muted small mb-0 ps-4">
                                    Versendet eine Bestätigung, wenn ein Termin durch den Kunden im Portal oder durch die Schule storniert wird.
                                </p>
                            </div>

                            <div class="card border p-3 mb-3 bg-light">
                                <div class="form-check form-switch fs-6 mb-1">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                           name="boating_notify_customer_booking_reminder" id="toggle_cust_reminder" value="1"
                                           <?= !empty($settings['boating_notify_customer_booking_reminder']) ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-bold text-dark" for="toggle_cust_reminder">
                                        Terminerinnerung (Cronjob)
                                    </label>
                                </div>
                                <p class="text-muted small mb-0 ps-4">
                                    Erinnert den Kunden automatisch X Stunden vor Beginn an die bevorstehende Fahrstunde / Bootsmiete.
                                </p>
                            </div>

                            <div class="alert alert-info py-2 px-3 small mb-0">
                                <i class="bi bi-info-circle me-1"></i> <strong>Kundenpräferenz:</strong> Kunden mit gesetztem Flag <code>notify_email = 0</code> erhalten gemäß Vorgabe keine E-Mails.
                            </div>
                        </div>

                        <!-- Schule & Fahrlehrer Ereignisse -->
                        <div class="col-lg-6">
                            <h6 class="fw-bold text-success mb-3">
                                <i class="bi bi-building me-1"></i> Schule &amp; Fahrlehrer Benachrichtigungen
                            </h6>

                            <div class="card border p-3 mb-3 bg-light">
                                <div class="form-check form-switch fs-6 mb-1">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                           name="boating_notify_admin_new_booking" id="toggle_admin_new" value="1"
                                           <?= !empty($settings['boating_notify_admin_new_booking']) ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-bold text-dark" for="toggle_admin_new">
                                        Admin-Benachrichtigung bei neuen Buchungen
                                    </label>
                                </div>
                                <p class="text-muted small mb-0 ps-4">
                                    Sendet eine E-Mail an die Dispositionsadresse (<code><?= htmlspecialchars($settings['boating_mail_admin_address'] ?? 'info@bootschule.ch') ?></code>), sobald ein Kunde über das Online-Portal bucht.
                                </p>
                            </div>

                            <div class="card border p-3 mb-3 bg-light">
                                <div class="form-check form-switch fs-6 mb-1">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                           name="boating_notify_instructor_new_booking" id="toggle_instructor_new" value="1"
                                           <?= !empty($settings['boating_notify_instructor_new_booking']) ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-bold text-dark" for="toggle_instructor_new">
                                        Fahrlehrer-Benachrichtigung bei Zuweisung
                                    </label>
                                </div>
                                <p class="text-muted small mb-0 ps-4">
                                    Benachrichtigt den zugewiesenen Fahrlehrer per E-Mail, wenn ihm eine neue Fahrstunde zugeteilt wurde (sofern Fahrlehrer E-Mail hinterlegt hat).
                                </p>
                            </div>

                            <div class="card border border-warning p-3 bg-warning bg-opacity-10">
                                <h6 class="fw-bold text-dark mb-1"><i class="bi bi-shield-check text-warning me-1"></i>DKIM &amp; Zustellbarkeit</h6>
                                <p class="small text-muted mb-0">
                                    Stellen Sie sicher, dass Ihre Domain SPF- und DKIM-Einträge für den Webserver besitzt, damit Benachrichtigungen nicht im Spam-Ordner landen.
                                </p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- TAB 3: E-MAIL-VORLAGEN (TEMPLATES) -->
        <!-- =================================================================== -->
        <div class="tab-pane fade <?= $activeTab === 'templates' ? 'show active' : '' ?>" id="tab-templates" role="tabpanel">

            <!-- Platzhalter-Legende -->
            <div class="card border-0 shadow-sm mb-4 bg-light">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="fw-bold text-dark mb-0">
                            <i class="bi bi-code-slash text-primary me-2"></i>Verfügbare Platzhalter (Klicken zum Einfügen)
                        </h6>
                        <span class="badge bg-primary">Automatische Ersetzung</span>
                    </div>
                    <div class="d-flex flex-wrap gap-1" id="placeholderButtons">
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 font-monospace copy-tag" data-tag="{customer_first_name}">{customer_first_name}</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 font-monospace copy-tag" data-tag="{customer_last_name}">{customer_last_name}</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 font-monospace copy-tag" data-tag="{customer_name}">{customer_name}</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 font-monospace copy-tag" data-tag="{customer_email}">{customer_email}</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 font-monospace copy-tag" data-tag="{customer_phone}">{customer_phone}</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 font-monospace copy-tag" data-tag="{appointment_id}">{appointment_id}</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 font-monospace copy-tag" data-tag="{service_name}">{service_name}</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 font-monospace copy-tag" data-tag="{date}">{date}</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 font-monospace copy-tag" data-tag="{start_time}">{start_time}</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 font-monospace copy-tag" data-tag="{end_time}">{end_time}</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 font-monospace copy-tag" data-tag="{location_name}">{location_name}</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 font-monospace copy-tag" data-tag="{location_address}">{location_address}</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 font-monospace copy-tag" data-tag="{boat_name}">{boat_name}</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 font-monospace copy-tag" data-tag="{instructor_name}">{instructor_name}</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 font-monospace copy-tag" data-tag="{instructor_first_name}">{instructor_first_name}</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 font-monospace copy-tag" data-tag="{price}">{price}</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 font-monospace copy-tag" data-tag="{status_label}">{status_label}</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 font-monospace copy-tag" data-tag="{portal_url}">{portal_url}</button>
                        <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 font-monospace copy-tag fw-bold" data-tag="{{my_appointments_url}}" title="Persönlicher Schüler-Magic-Link zu 'Meine Termine' (7 Tage gültig)">{{my_appointments_url}}</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 font-monospace copy-tag" data-tag="{school_name}">{school_name}</button>
                    </div>
                    <div class="mt-2 text-muted small">
                        <i class="bi bi-info-circle me-1"></i> <strong>{{my_appointments_url}}</strong> erzeugt automatisch einen sicheren, passwortlosen Magic-Link für den Kunden mit 7 Tagen Gültigkeit.
                    </div>
                </div>
            </div>

            <!-- Accordion für Vorlagen -->
            <div class="accordion shadow-sm mb-4" id="templateAccordion">

                <!-- 1. Buchungsbestätigung Kunde -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingTpl1">
                        <button class="accordion-button fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTpl1" aria-expanded="true" aria-controls="collapseTpl1">
                            <i class="bi bi-file-earmark-check text-success me-2 fs-5"></i>
                            1. Kunde: Buchungs- &amp; Eingangsbestätigung (Created)
                        </button>
                    </h2>
                    <div id="collapseTpl1" class="accordion-collapse collapse show" aria-labelledby="headingTpl1" data-bs-parent="#templateAccordion">
                        <div class="accordion-body p-4">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">E-Mail-Betreff (Subject)</label>
                                <input type="text" name="boating_tpl_customer_booking_created_subject" class="form-control"
                                       value="<?= htmlspecialchars($settings['boating_tpl_customer_booking_created_subject'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-semibold">E-Mail-Nachrichtentext (Body)</label>
                                <textarea name="boating_tpl_customer_booking_created_body" class="form-control font-monospace" rows="10" required><?= htmlspecialchars($settings['boating_tpl_customer_booking_created_body'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Terminänderung Kunde -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingTpl2">
                        <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTpl2" aria-expanded="false" aria-controls="collapseTpl2">
                            <i class="bi bi-arrow-repeat text-primary me-2 fs-5"></i>
                            2. Kunde: Terminänderung &amp; Verschiebung (Updated)
                        </button>
                    </h2>
                    <div id="collapseTpl2" class="accordion-collapse collapse" aria-labelledby="headingTpl2" data-bs-parent="#templateAccordion">
                        <div class="accordion-body p-4">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">E-Mail-Betreff (Subject)</label>
                                <input type="text" name="boating_tpl_customer_booking_updated_subject" class="form-control"
                                       value="<?= htmlspecialchars($settings['boating_tpl_customer_booking_updated_subject'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-semibold">E-Mail-Nachrichtentext (Body)</label>
                                <textarea name="boating_tpl_customer_booking_updated_body" class="form-control font-monospace" rows="10" required><?= htmlspecialchars($settings['boating_tpl_customer_booking_updated_body'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Stornierung Kunde -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingTpl3">
                        <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTpl3" aria-expanded="false" aria-controls="collapseTpl3">
                            <i class="bi bi-x-circle text-danger me-2 fs-5"></i>
                            3. Kunde: Stornierungsbestätigung (Cancelled)
                        </button>
                    </h2>
                    <div id="collapseTpl3" class="accordion-collapse collapse" aria-labelledby="headingTpl3" data-bs-parent="#templateAccordion">
                        <div class="accordion-body p-4">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">E-Mail-Betreff (Subject)</label>
                                <input type="text" name="boating_tpl_customer_booking_cancelled_subject" class="form-control"
                                       value="<?= htmlspecialchars($settings['boating_tpl_customer_booking_cancelled_subject'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-semibold">E-Mail-Nachrichtentext (Body)</label>
                                <textarea name="boating_tpl_customer_booking_cancelled_body" class="form-control font-monospace" rows="10" required><?= htmlspecialchars($settings['boating_tpl_customer_booking_cancelled_body'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. Terminerinnerung Kunde -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingTpl4">
                        <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTpl4" aria-expanded="false" aria-controls="collapseTpl4">
                            <i class="bi bi-alarm text-warning me-2 fs-5"></i>
                            4. Kunde: Terminerinnerung (Reminder)
                        </button>
                    </h2>
                    <div id="collapseTpl4" class="accordion-collapse collapse" aria-labelledby="headingTpl4" data-bs-parent="#templateAccordion">
                        <div class="accordion-body p-4">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">E-Mail-Betreff (Subject)</label>
                                <input type="text" name="boating_tpl_customer_booking_reminder_subject" class="form-control"
                                       value="<?= htmlspecialchars($settings['boating_tpl_customer_booking_reminder_subject'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-semibold">E-Mail-Nachrichtentext (Body)</label>
                                <textarea name="boating_tpl_customer_booking_reminder_body" class="form-control font-monospace" rows="10" required><?= htmlspecialchars($settings['boating_tpl_customer_booking_reminder_body'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5. Admin Neue Buchung -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingTpl5">
                        <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTpl5" aria-expanded="false" aria-controls="collapseTpl5">
                            <i class="bi bi-bell text-info me-2 fs-5"></i>
                            5. Admin / Disposition: Neue Buchung eingegangen
                        </button>
                    </h2>
                    <div id="collapseTpl5" class="accordion-collapse collapse" aria-labelledby="headingTpl5" data-bs-parent="#templateAccordion">
                        <div class="accordion-body p-4">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">E-Mail-Betreff (Subject)</label>
                                <input type="text" name="boating_tpl_admin_new_booking_subject" class="form-control"
                                       value="<?= htmlspecialchars($settings['boating_tpl_admin_new_booking_subject'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-semibold">E-Mail-Nachrichtentext (Body)</label>
                                <textarea name="boating_tpl_admin_new_booking_body" class="form-control font-monospace" rows="10" required><?= htmlspecialchars($settings['boating_tpl_admin_new_booking_body'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 6. Fahrlehrer Neue Zuweisung -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingTpl6">
                        <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTpl6" aria-expanded="false" aria-controls="collapseTpl6">
                            <i class="bi bi-person-badge text-secondary me-2 fs-5"></i>
                            6. Fahrlehrer: Neue Buchung / Termin zugewiesen
                        </button>
                    </h2>
                    <div id="collapseTpl6" class="accordion-collapse collapse" aria-labelledby="headingTpl6" data-bs-parent="#templateAccordion">
                        <div class="accordion-body p-4">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">E-Mail-Betreff (Subject)</label>
                                <input type="text" name="boating_tpl_instructor_new_booking_subject" class="form-control"
                                       value="<?= htmlspecialchars($settings['boating_tpl_instructor_new_booking_subject'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-semibold">E-Mail-Nachrichtentext (Body)</label>
                                <textarea name="boating_tpl_instructor_new_booking_body" class="form-control font-monospace" rows="10" required><?= htmlspecialchars($settings['boating_tpl_instructor_new_booking_body'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- =================================================================== -->
        <!-- TAB 4: CRONJOB & AUTOMATISIERUNG -->
        <!-- =================================================================== -->
        <div class="tab-pane fade <?= $activeTab === 'cron' ? 'show active' : '' ?>" id="tab-cron" role="tabpanel">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light py-3">
                    <h5 class="card-title fw-bold text-dark mb-0">
                        <i class="bi bi-clock-history text-primary me-2"></i>Automatisierte Terminerinnerungen via Cronjob
                    </h5>
                    <small class="text-muted">Informationen zur Server-Einrichtung und manueller Ausführung.</small>
                </div>
                <div class="card-body p-4">
                    <p>
                        Terminerinnerungen werden über ein schlankes PHP-CLI-Skript versendet. Das Skript prüft anstehende bestätigte Buchungen,
                        berücksichtigt die Vorlaufzeit (aktuell: <strong><?= (int) ($settings['boating_reminder_hours_before'] ?? 24) ?> Stunden</strong>)
                        und markiert bereits erinnerte Termine mit einem Zeitstempel (<code>reminder_sent_at</code>), um Mehrfachsendungen auszuschliessen.
                    </p>

                    <h6 class="fw-bold text-dark mt-4 mb-2">Empfohlener Crontab-Eintrag (z.B. stündlich):</h6>
                    <div class="bg-dark text-light p-3 rounded font-monospace small mb-3">
                        0 * * * * php <?= htmlspecialchars(dirname(__DIR__, 4)) ?>/bin/boating_send_reminders.php &gt; /dev/null 2&gt;&amp;1
                    </div>

                    <h6 class="fw-bold text-dark mt-4 mb-2">Oder per Web-Cron / HTTP-Aufruf:</h6>
                    <div class="bg-light border p-3 rounded font-monospace small text-dark mb-4">
                        curl -s -X POST "<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') ?>://<?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'localhost') ?>/?route=admin/boating/reminders/send"
                    </div>

                    <div class="d-flex align-items-center gap-3">
                        <button type="button" class="btn btn-primary" onclick="triggerManualReminder()">
                            <i class="bi bi-play-circle me-1"></i> Erinnerungslauf jetzt manuell starten
                        </button>
                        <span id="manualCronStatus" class="text-muted small"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- =================================================================== -->
        <!-- TAB 5: HÄFEN & STANDORTE (bs_locations)                             -->
        <!-- =================================================================== -->
        <div class="tab-pane fade <?= $activeTab === 'locations' ? 'show active' : '' ?>" id="tab-locations" role="tabpanel">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <h5 class="card-title fw-bold text-dark mb-0">
                            <i class="bi bi-geo-alt-fill text-danger me-2"></i>Häfen, Steganlagen &amp; Standorte (bs_locations)
                        </h5>
                        <small class="text-muted">
                            Verwaltung aller Stützpunkte &amp; Treffpunkte für das Dropdown «Wunsch-Standort / Hafen» im Kundenportal und für Trainer-Verfügbarkeiten.
                        </small>
                    </div>
                    <div>
                        <button type="button" class="btn btn-sm btn-success fw-bold d-flex align-items-center gap-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#locationModal" onclick="openCreateLocationModal()">
                            <i class="bi bi-plus-circle"></i> + Neuen Standort anlegen
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-muted small text-uppercase">
                                <tr>
                                    <th style="width: 60px;">ID</th>
                                    <th>Bezeichnung / Hafensteg</th>
                                    <th>Kürzel</th>
                                    <th>Adresse / Treffpunkt</th>
                                    <th>Hinweise / Notizen</th>
                                    <th style="width: 100px;">Status</th>
                                    <th class="text-end" style="width: 120px;">Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($locations)): ?>
                                    <?php foreach ($locations as $loc): ?>
                                        <tr>
                                            <td class="font-monospace text-muted small">#<?= (int) $loc['id'] ?></td>
                                            <td>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars((string) $loc['name'], ENT_QUOTES, 'UTF-8') ?></div>
                                            </td>
                                            <td>
                                                <?php if (!empty($loc['code'])): ?>
                                                    <span class="badge bg-secondary"><?= htmlspecialchars((string) $loc['code'], ENT_QUOTES, 'UTF-8') ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted small">&ndash;</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="small text-muted">
                                                <?php if (!empty($loc['address'])): ?>
                                                    <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode((string) $loc['address']) ?>" target="_blank" rel="noopener noreferrer" class="text-decoration-none text-dark d-inline-flex align-items-center gap-1 hover-underline" title="Auf Google Maps öffnen">
                                                        <i class="bi bi-geo-alt-fill text-danger flex-shrink-0"></i>
                                                        <span class="fw-semibold"><?= htmlspecialchars((string) $loc['address'], ENT_QUOTES, 'UTF-8') ?></span>
                                                        <i class="bi bi-box-arrow-up-right text-muted" style="font-size: 0.7rem;"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">&ndash;</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="small text-muted">
                                                <?= htmlspecialchars((string) ($loc['notes'] ?? '–'), ENT_QUOTES, 'UTF-8') ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($loc['is_active'])): ?>
                                                    <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25">Aktiv</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-25">Inaktiv</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-secondary" title="Bearbeiten"
                                                            onclick='openEditLocationModal(<?= json_encode($loc, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <form method="POST" action="?route=admin/boating/locations/delete" class="d-inline" onsubmit="return confirm('Möchten Sie diesen Standort wirklich löschen?');">
                                                        <?php if ($csrfToken): ?>
                                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                        <?php endif; ?>
                                                        <input type="hidden" name="id" value="<?= (int) $loc['id'] ?>">
                                                        <button type="submit" class="btn btn-outline-danger" title="Löschen">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted small">
                                            Keine Standorte erfasst.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Speichern-Button Leiste -->
    <div class="card border-0 shadow-sm mb-5 bg-white p-3">
        <div class="d-flex align-items-center justify-content-between">
            <span class="text-muted small">
                <i class="bi bi-info-circle me-1"></i> Änderungen werden sofort für alle künftigen Buchungs-E-Mails wirksam.
            </span>
            <button type="submit" class="btn btn-primary fw-semibold px-4 shadow-sm">
                <i class="bi bi-save me-1"></i> Einstellungen &amp; Vorlagen speichern
            </button>
        </div>
    </div>
</form>

<!-- Modal: Test-E-Mail senden -->
<div class="modal fade" id="testMailModal" tabindex="-1" aria-labelledby="testMailModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="?route=admin/boating/settings/test-mail">
                <?php if ($csrfToken): ?>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <?php endif; ?>

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="testMailModalLabel">
                        <i class="bi bi-send-check me-2"></i>Test-E-Mail versenden
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>

                <div class="modal-body p-4">
                    <p class="small text-muted mb-3">
                        Senden Sie eine Test-E-Mail mit Musterdaten, um Layout und Textanzeige in Ihrem E-Mail-Programm zu überprüfen.
                    </p>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Empfänger-E-Mail <span class="text-danger">*</span></label>
                        <input type="email" name="test_recipient" class="form-control"
                               value="<?= htmlspecialchars($settings['boating_mail_admin_address'] ?? 'info@bootschule.ch', ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Zu testende Vorlage</label>
                        <select name="template_key" class="form-select">
                            <option value="boating_tpl_customer_booking_created_body">1. Kunde: Buchungsbestätigung</option>
                            <option value="boating_tpl_customer_booking_updated_body">2. Kunde: Terminänderung</option>
                            <option value="boating_tpl_customer_booking_cancelled_body">3. Kunde: Stornierungsbestätigung</option>
                            <option value="boating_tpl_customer_booking_reminder_body">4. Kunde: Terminerinnerung</option>
                            <option value="boating_tpl_admin_new_booking_body">5. Admin: Neue Buchung eingegangen</option>
                            <option value="boating_tpl_instructor_new_booking_body">6. Fahrlehrer: Neuer Termin zugewiesen</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-semibold px-4">
                        <i class="bi bi-send me-1"></i> Jetzt senden
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Standort / Hafen anlegen & bearbeiten -->
<div class="modal fade" id="locationModal" tabindex="-1" aria-labelledby="locationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="?route=admin/boating/locations/save">
                <?php if ($csrfToken): ?>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <?php endif; ?>
                <input type="hidden" name="id" id="locModalId" value="0">

                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold" id="locationModalLabel">
                        <i class="bi bi-geo-alt-fill text-danger me-2"></i><span id="locModalTitle">Neuen Standort erfassen</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Bezeichnung / Hafensteg <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="locModalName" class="form-control" placeholder="z. B. Buochs - Ennerberg Steganlage oder Luzern - Alpenquai" required>
                        <small class="text-muted">Wird Kunden im Dropdown «Wunsch-Standort / Hafen» angezeigt.</small>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <label class="form-label fw-semibold">Kanton / Kürzel</label>
                            <input type="text" name="code" id="locModalCode" class="form-control" placeholder="z. B. NW, LU">
                        </div>
                        <div class="col-8">
                            <label class="form-label fw-semibold">Status</label>
                            <div class="form-check form-switch pt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" id="locModalActive" value="1" checked>
                                <label class="form-check-label fw-semibold" for="locModalActive">Aktiv &amp; buchbar</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <label class="form-label fw-semibold mb-0">Genaue Adresse / Treffpunkt</label>
                            <a id="locModalMapsPreview" href="https://maps.google.com" target="_blank" rel="noopener noreferrer" class="small text-decoration-none text-primary fw-semibold d-inline-flex align-items-center gap-1" style="display: none !important;">
                                <i class="bi bi-geo-alt-fill text-danger"></i>
                                <span>In Google Maps prüfen &nearr;</span>
                            </a>
                        </div>
                        <input type="text" name="address" id="locModalAddress" class="form-control" placeholder="z. B. Seestrasse 48, 6374 Buochs" oninput="updateLocModalMapsPreview(this.value)">
                        <small class="text-muted">Erscheint in der Buchungsbestätigung und Wegbeschreibung (automatisch mit Google Maps verknüpft).</small>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-semibold">Hinweise / Notizen (optional)</label>
                        <textarea name="notes" id="locModalNotes" rows="2" class="form-control" placeholder="z. B. Stützpunkt Süd, Schlüsselkasten Steg 3..."></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-semibold px-4">
                        <i class="bi bi-save me-1"></i> Speichern
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updateLocModalMapsPreview(address) {
    const preview = document.getElementById('locModalMapsPreview');
    if (!preview) return;
    const trimmed = (address || '').trim();
    if (trimmed.length > 2) {
        preview.href = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(trimmed);
        preview.style.setProperty('display', 'inline-flex', 'important');
    } else {
        preview.style.setProperty('display', 'none', 'important');
    }
}

function openCreateLocationModal() {
    document.getElementById('locModalId').value = '0';
    document.getElementById('locModalTitle').textContent = 'Neuen Standort erfassen';
    document.getElementById('locModalName').value = '';
    document.getElementById('locModalCode').value = '';
    document.getElementById('locModalAddress').value = '';
    document.getElementById('locModalNotes').value = '';
    document.getElementById('locModalActive').checked = true;
    updateLocModalMapsPreview('');
}

function openEditLocationModal(loc) {
    document.getElementById('locModalId').value = loc.id || 0;
    document.getElementById('locModalTitle').textContent = 'Standort bearbeiten: ' + (loc.name || '');
    document.getElementById('locModalName').value = loc.name || '';
    document.getElementById('locModalCode').value = loc.code || '';
    document.getElementById('locModalAddress').value = loc.address || '';
    document.getElementById('locModalNotes').value = loc.notes || '';
    document.getElementById('locModalActive').checked = !!parseInt(loc.is_active, 10);
    updateLocModalMapsPreview(loc.address || '');

    const modal = new bootstrap.Modal(document.getElementById('locationModal'));
    modal.show();
}

document.addEventListener('DOMContentLoaded', function () {
    // Klick auf Platzhalter-Button kopiert den Tag in die Zwischenablage
    const tagButtons = document.querySelectorAll('.copy-tag');
    tagButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const tag = this.getAttribute('data-tag');
            if (!tag) return;
            navigator.clipboard.writeText(tag).then(() => {
                const originalText = this.textContent;
                this.classList.replace('btn-outline-secondary', 'btn-success');
                this.textContent = 'Kopiert!';
                setTimeout(() => {
                    this.classList.replace('btn-success', 'btn-outline-secondary');
                    this.textContent = originalText;
                }, 1500);
            });
        });
    });
});

function triggerManualReminder() {
    if (!confirm('Möchten Sie den Erinnerungslauf jetzt starten?')) return;
    const statusEl = document.getElementById('manualCronStatus');
    statusEl.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Wird ausgeführt...';

    const csrf = <?= json_encode($csrfToken) ?>;
    const formData = new FormData();
    formData.append('csrf_token', csrf);

    fetch('?route=admin/boating/reminders/send', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            statusEl.innerHTML = '<span class="text-success fw-bold"><i class="bi bi-check-circle"></i> ' + data.message + '</span>';
        } else {
            statusEl.innerHTML = '<span class="text-danger fw-bold"><i class="bi bi-exclamation-circle"></i> ' + (data.message || 'Fehler aufgetreten') + '</span>';
        }
    })
    .catch(err => {
        statusEl.innerHTML = '<span class="text-danger fw-bold">Fehler: ' + err.message + '</span>';
    });
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../../../views/layouts/admin.php';
