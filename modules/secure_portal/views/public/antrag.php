<?php

declare(strict_types=1);

/**
 * Sicherungsportal: Mehrteiliges Antragsformular (/sicherung/antrag)
 *
 * Schritte:
 * 1. Basisdaten (Dienststelle, Ansprechpartner, Aktenzeichen, Umfang)
 * 2. Upload Editionsverfügung (PDF-Verfügung gemäss Art. 265 ff. Schweizer StPO)
 * 3. Typ der Sicherung & Spezifikation (Video, Mail, Cloud, Zutritt etc.)
 * success: Vorgangsbestätigung mit Vorgangs-ID & Zugangscode
 *
 * @var int $currentStep
 * @var array<string, mixed> $formData
 * @var array<string, string> $errors
 * @var array<string, mixed>|null $createdCase
 */

$currentStep = $currentStep ?? 1;
$formData = $formData ?? [];
$errors = $errors ?? [];
$createdCase = $createdCase ?? null;

$settings = class_exists('Settings') ? Settings::all() : [];
$siteTitle = 'Polizei- & Justiz-Sicherungsportal';
$adminBrandColor = (string) ($settings['admin_brand_color'] ?? '#0f4c81');
$primaryColor = !empty($settings['homepage_primary_color']) ? (string) $settings['homepage_primary_color'] : $adminBrandColor;
$logoPath = (string) ($settings['homepage_logo_path'] ?? '');

$csrfToken = class_exists('Csrf') ? Csrf::getToken() : '';
$securingTypes = SecurePortalRepository::SECURING_TYPES;
$selectedType = (string) ($formData['securing_type'] ?? 'VIDEO');
if (!array_key_exists($selectedType, $securingTypes)) {
    $selectedType = 'VIDEO';
}
$securingMeta = (array) ($formData['securing_meta'] ?? []);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($siteTitle, ENT_QUOTES, 'UTF-8') ?> - Sicherungsantrag stellen</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --sec-primary: <?= htmlspecialchars($primaryColor, ENT_QUOTES, 'UTF-8') ?>;
            --sec-primary-dark: #0a2f52;
            --sec-bg: #f4f6f9;
            --sec-card-bg: #ffffff;
            --sec-text-dark: #1e293b;
            --sec-border: #e2e8f0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--sec-bg);
            color: var(--sec-text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .portal-navbar {
            background: linear-gradient(135deg, #0b1f3a 0%, #1e3a8a 100%);
            color: #ffffff;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);
        }

        /* Step Progress Bar */
        .step-progress {
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            margin-bottom: 2.5rem;
        }

        .step-progress::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 5%;
            right: 5%;
            height: 3px;
            background-color: #e2e8f0;
            z-index: 1;
        }

        .step-item {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: #64748b;
        }

        .step-circle {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background-color: #ffffff;
            border: 2px solid #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1rem;
            color: #64748b;
            margin-bottom: 0.5rem;
            transition: all 0.2s ease;
        }

        .step-item.active .step-circle {
            border-color: #2563eb;
            background-color: #2563eb;
            color: #ffffff;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.2);
        }

        .step-item.completed .step-circle {
            border-color: #059669;
            background-color: #059669;
            color: #ffffff;
        }

        .step-label {
            font-size: 0.85rem;
            font-weight: 600;
            text-align: center;
        }

        .step-item.active .step-label {
            color: #1e3a8a;
        }

        .form-card {
            background-color: #ffffff;
            border-radius: 0.75rem;
            border: 1px solid var(--sec-border);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            padding: 2.5rem;
        }

        .type-selector-card {
            border: 2px solid #e2e8f0;
            border-radius: 0.65rem;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.15s ease;
            height: 100%;
        }

        .type-selector-card:hover {
            border-color: #93c5fd;
            background-color: #f8fafc;
        }

        .type-selector-card.selected {
            border-color: #2563eb;
            background-color: #eff6ff;
        }

        .code-display-box {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #ffffff;
            border-radius: 0.75rem;
            padding: 2rem;
            text-align: center;
        }

        .code-large {
            font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: 4px;
            color: #38bdf8;
        }

        .footer {
            margin-top: auto;
            background-color: #0f172a;
            color: #94a3b8;
            padding: 2rem 0;
            font-size: 0.875rem;
            border-top: 1px solid #1e293b;
        }

        .footer a {
            color: #cbd5e1;
            text-decoration: none;
        }

        .footer a:hover {
            color: #ffffff;
            text-decoration: underline;
        }

        @media print {
            .portal-navbar, .footer, .btn, .step-progress, .no-print {
                display: none !important;
            }
            .form-card {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
            }
        }
    </style>
</head>
<body>

    <!-- Header / Navbar -->
    <header class="portal-navbar">
        <div class="container">
            <div class="d-flex align-items-center justify-content-between">
                <a href="?route=sicherung" class="d-flex align-items-center gap-3 text-decoration-none text-white">
                    <?php if ($logoPath !== ''): ?>
                        <img src="<?= htmlspecialchars($logoPath, ENT_QUOTES, 'UTF-8') ?>" alt="Logo" style="height: 42px; max-width: 180px; object-fit: contain;">
                    <?php else: ?>
                        <div class="bg-white bg-opacity-10 p-2 rounded text-white d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-shield-lock-fill fs-4 text-warning"></i>
                        </div>
                    <?php endif; ?>
                    <div>
                        <h1 class="h5 mb-0 fw-bold text-white tracking-tight">Sicherungsportal</h1>
                        <small class="text-white-50">Behördlicher Antrag auf Datensicherung</small>
                    </div>
                </a>

                <div class="d-flex align-items-center gap-2">
                    <a href="?route=sicherung" class="btn btn-outline-light btn-sm px-3">
                        <i class="bi bi-house-door-fill me-1"></i> Startseite
                    </a>
                    <a href="?route=sicherung/fallzugang" class="btn btn-outline-light btn-sm px-3">
                        <i class="bi bi-key-fill me-1"></i> Fallzugang
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="container my-5">
        <div class="row justify-content-center">
            <div class="col-xl-9 col-lg-10">

                <?php if ($currentStep !== 4 && $createdCase === null): ?>
                    <!-- Step Progress Indicator -->
                    <div class="step-progress">
                        <div class="step-item <?= $currentStep === 1 ? 'active' : ($currentStep > 1 ? 'completed' : '') ?>">
                            <div class="step-circle">
                                <?= $currentStep > 1 ? '<i class="bi bi-check-lg"></i>' : '1' ?>
                            </div>
                            <span class="step-label">Basisdaten &amp; Behörde</span>
                        </div>

                        <div class="step-item <?= $currentStep === 2 ? 'active' : ($currentStep > 2 ? 'completed' : '') ?>">
                            <div class="step-circle">
                                <?= $currentStep > 2 ? '<i class="bi bi-check-lg"></i>' : '2' ?>
                            </div>
                            <span class="step-label">Editionsverfügung (PDF)</span>
                        </div>

                        <div class="step-item <?= $currentStep === 3 ? 'active' : '' ?>">
                            <div class="step-circle">3</div>
                            <span class="step-label">Sicherungsart &amp; Umfang</span>
                        </div>

                        <div class="step-item">
                            <div class="step-circle"><i class="bi bi-award"></i></div>
                            <span class="step-label">Bestätigung &amp; Fall-ID</span>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Fehleranzeige -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger shadow-sm mb-4" role="alert">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                            <strong>Bitte korrigieren Sie folgende Angaben:</strong>
                        </div>
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $field => $msg): ?>
                                <li><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="form-card">

                    <!-- ========================================================= -->
                    <!-- SCHRITT 1: BASISDATEN & BEHÖRDE                          -->
                    <!-- ========================================================= -->
                    <?php if ($currentStep === 1): ?>
                        <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
                            <div>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 mb-1">Schritt 1 von 3</span>
                                <h2 class="h4 fw-bold text-dark mb-0">Behörden- &amp; Vorgangsdaten erfassen</h2>
                            </div>
                            <i class="bi bi-building-fill-check fs-2 text-primary opacity-50"></i>
                        </div>

                        <form method="POST" action="?route=sicherung/antrag&step=1">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="step" value="1">

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="police_department" class="form-label fw-semibold">
                                        Antragstellende Dienststelle / Behörde <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control <?= isset($errors['police_department']) ? 'is-invalid' : '' ?>" 
                                           id="police_department" name="police_department" 
                                           value="<?= htmlspecialchars((string) ($formData['police_department'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                           placeholder="z. B. Kantonspolizei Zürich, Kriminalpolizei oder StA See/Oberland" required>
                                    <div class="form-text">Vollständige Bezeichnung der ermittelnden Behörde (Kapo, Stapo, fedpol, StA).</div>
                                </div>

                                <div class="col-md-6">
                                    <label for="reference_number" class="form-label fw-semibold">
                                        Behördliches Aktenzeichen / Geschäfts-Nr. <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control <?= isset($errors['reference_number']) ? 'is-invalid' : '' ?>" 
                                           id="reference_number" name="reference_number" 
                                           value="<?= htmlspecialchars((string) ($formData['reference_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                           placeholder="z. B. Kapo ZH V-2026-1234 oder StAZH Ref. 2026/5678" required>
                                    <div class="form-text">Dient der internen Zuordnung Ihrer Dienststelle.</div>
                                </div>

                                <div class="col-md-4">
                                    <label for="contact_name" class="form-label fw-semibold">
                                        Sachbearbeiter (Name, Grad / Funktion) <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control <?= isset($errors['contact_name']) ? 'is-invalid' : '' ?>" 
                                           id="contact_name" name="contact_name" 
                                           value="<?= htmlspecialchars((string) ($formData['contact_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                           placeholder="z. B. Det. Wm. M. Frei oder lic. iur. T. Meier" required>
                                </div>

                                <div class="col-md-4">
                                    <label for="contact_email" class="form-label fw-semibold">
                                        Dienstliche E-Mail-Adresse <span class="text-danger">*</span>
                                    </label>
                                    <input type="email" class="form-control <?= isset($errors['contact_email']) ? 'is-invalid' : '' ?>" 
                                           id="contact_email" name="contact_email" 
                                           value="<?= htmlspecialchars((string) ($formData['contact_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                           placeholder="vorname.nachname@kapo.zh.ch" required>
                                    <div class="form-text">Für Statusaktualisierungen &amp; Rückfragen.</div>
                                </div>

                                <div class="col-md-4">
                                    <label for="contact_phone" class="form-label fw-semibold">
                                        Telefonnummer (Rückfragen) <span class="text-danger">*</span>
                                    </label>
                                    <input type="tel" class="form-control <?= isset($errors['contact_phone']) ? 'is-invalid' : '' ?>" 
                                           id="contact_phone" name="contact_phone" 
                                           value="<?= htmlspecialchars((string) ($formData['contact_phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                           placeholder="z. B. +41 44 247 22 11 oder 044 123 45 67" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="description" class="form-label fw-semibold">
                                    Kurzbeschreibung des Sicherungsumfangs &amp; Sachverhalts <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>" 
                                          id="description" name="description" rows="3" required
                                          placeholder="Kurze Zusammenfassung des Ermittlungsvorgangs und der zu sichernden Daten..."><?= htmlspecialchars((string) ($formData['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                                <div class="form-text">Geben Sie hier stichpunktartig den Gegenstand der Sicherung an.</div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="desired_date" class="form-label fw-semibold">
                                        Gewünschter Bereitstellungstermin (Frist)
                                    </label>
                                    <input type="date" class="form-control <?= isset($errors['desired_date']) ? 'is-invalid' : '' ?>" 
                                           id="desired_date" name="desired_date" 
                                           value="<?= htmlspecialchars((string) ($formData['desired_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <div class="form-text">Optional: Falls behördliche Fristen bestehen.</div>
                                </div>

                                <div class="col-md-6">
                                    <label for="remarks" class="form-label fw-semibold">
                                        Besondere Hinweise / Bemerkungen
                                    </label>
                                    <input type="text" class="form-control" id="remarks" name="remarks" 
                                           value="<?= htmlspecialchars((string) ($formData['remarks'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                           placeholder="z. B. Eilt sehr, Sicherungsfrist läuft ab">
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                                <a href="?route=sicherung" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-lg me-1"></i> Abbrechen
                                </a>
                                <button type="submit" class="btn btn-primary px-4 fw-semibold">
                                    Weiter zu Schritt 2: Editionsverfügung &rarr;
                                </button>
                            </div>
                        </form>

                    <!-- ========================================================= -->
                    <!-- SCHRITT 2: UPLOAD EDITIONSVERFÜGUNG (PDF)                 -->
                    <!-- ========================================================= -->
                    <?php elseif ($currentStep === 2): ?>
                        <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
                            <div>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 mb-1">Schritt 2 von 3</span>
                                <h2 class="h4 fw-bold text-dark mb-0">Editionsverfügung (Art. 265 StPO) hochladen</h2>
                            </div>
                            <i class="bi bi-file-earmark-pdf-fill fs-2 text-danger opacity-75"></i>
                        </div>

                        <div class="alert alert-info d-flex align-items-start gap-3 mb-4">
                            <i class="bi bi-shield-check fs-4 flex-shrink-0 mt-1 text-primary"></i>
                            <div class="small">
                                <strong>Rechtlicher Hinweis gemäss Art. 265 ff. Schweizer StPO:</strong><br>
                                Voraussetzung für jede Beweissicherung und Datenherausgabe ist die Vorlage einer rechtsgültigen
                                behördlichen Editionsverfügung der Staatsanwaltschaft, des Gerichts oder der zuständigen Untersuchungsbehörde (Art. 265 StPO). Bitte laden Sie das Dokument
                                als PDF hoch. Das Dokument wird verschlüsselt gespeichert und vor Bereitstellung verifiziert.
                            </div>
                        </div>

                        <form method="POST" action="?route=sicherung/antrag&step=2" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="step" value="2">

                            <?php if (!empty($formData['warrant_file_path'])): ?>
                                <div class="p-3 bg-light rounded border mb-4 d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-3">
                                        <i class="bi bi-file-earmark-check-fill fs-2 text-success"></i>
                                        <div>
                                            <div class="fw-bold text-dark">
                                                <?= htmlspecialchars((string) ($formData['warrant_original_name'] ?? 'Editionsverfuegung.pdf'), ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                            <small class="text-muted">
                                                Bereits in diesem Vorgang hinterlegt (<?= number_format(((int)($formData['warrant_file_size'] ?? 0)) / 1024 / 1024, 2) ?> MB).
                                            </small>
                                        </div>
                                    </div>
                                    <span class="badge bg-success-subtle text-success border">Bereit zur Einreichung</span>
                                </div>
                            <?php endif; ?>

                            <div class="mb-4">
                                <label for="warrant_file" class="form-label fw-semibold">
                                    <?= !empty($formData['warrant_file_path']) ? 'Andere Datei auswählen (ersetzen)' : 'Editionsverfügung als PDF auswählen *' ?>
                                </label>
                                <input type="file" class="form-control <?= isset($errors['warrant_file']) ? 'is-invalid' : '' ?>" 
                                       id="warrant_file" name="warrant_file" accept="application/pdf"
                                       <?= empty($formData['warrant_file_path']) ? 'required' : '' ?>>
                                <div class="form-text d-flex justify-content-between mt-1">
                                    <span>Zulässiges Format: <strong>Ausschliesslich PDF</strong></span>
                                    <span>Maximalgrösse: <strong>30 MB</strong></span>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                                <a href="?route=sicherung/antrag&step=1" class="btn btn-outline-secondary">
                                    &larr; Zurück zu Schritt 1
                                </a>
                                <button type="submit" class="btn btn-primary px-4 fw-semibold">
                                    Weiter zu Schritt 3: Sicherungsart &rarr;
                                </button>
                            </div>
                        </form>

                    <!-- ========================================================= -->
                    <!-- SCHRITT 3: TYP DER SICHERUNG & SPEZIFIKATION             -->
                    <!-- ========================================================= -->
                    <?php elseif ($currentStep === 3): ?>
                        <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
                            <div>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 mb-1">Schritt 3 von 3</span>
                                <h2 class="h4 fw-bold text-dark mb-0">Art der Sicherung &amp; technische Spezifikation</h2>
                            </div>
                            <i class="bi bi-hdd-stack-fill fs-2 text-primary opacity-50"></i>
                        </div>

                        <form method="POST" action="?route=sicherung/antrag&step=3">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="step" value="3">

                            <!-- Typ-Auswahl -->
                            <label class="form-label fw-semibold mb-3">Wählen Sie die Art des digitalen Beweismittels <span class="text-danger">*</span></label>
                            <div class="row g-3 mb-4">
                                <?php foreach ($securingTypes as $typeKey => $tInfo): ?>
                                    <div class="col-md-6 col-lg-4">
                                        <label class="type-selector-card d-block <?= $selectedType === $typeKey ? 'selected' : '' ?>" id="card_type_<?= $typeKey ?>" onclick="selectSecuringType('<?= $typeKey ?>')">
                                            <div class="form-check p-0">
                                                <input class="form-check-input visually-hidden" type="radio" name="securing_type" 
                                                       id="type_<?= $typeKey ?>" value="<?= $typeKey ?>" 
                                                       <?= $selectedType === $typeKey ? 'checked' : '' ?>>
                                                <div class="d-flex align-items-center gap-2 mb-2">
                                                    <i class="bi <?= $tInfo['icon'] ?> fs-4 text-primary"></i>
                                                    <span class="fw-bold text-dark"><?= htmlspecialchars($tInfo['short'], ENT_QUOTES, 'UTF-8') ?></span>
                                                </div>
                                                <p class="small text-muted mb-0" style="min-height: 40px;">
                                                    <?= htmlspecialchars($tInfo['desc'], ENT_QUOTES, 'UTF-8') ?>
                                                </p>
                                            </div>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Spezifische Felder je Typ -->
                            <div class="p-4 bg-light rounded border mb-4">
                                <h5 class="fw-bold text-dark mb-3">
                                    <i class="bi bi-sliders text-primary me-2"></i>
                                    Technische Detailangaben zur Sicherung
                                </h5>

                                <!-- TYPE: VIDEO -->
                                <div id="fields_VIDEO" class="securing-fields <?= $selectedType === 'VIDEO' ? '' : 'd-none' ?>">
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <label for="timeframe_from" class="form-label fw-semibold">Sicherungszeitraum VON <span class="text-danger">*</span></label>
                                            <input type="datetime-local" class="form-control" id="timeframe_from" name="securing_meta[timeframe_from]" 
                                                   value="<?= htmlspecialchars((string) ($securingMeta['timeframe_from'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="timeframe_to" class="form-label fw-semibold">Sicherungszeitraum BIS <span class="text-danger">*</span></label>
                                            <input type="datetime-local" class="form-control" id="timeframe_to" name="securing_meta[timeframe_to]" 
                                                   value="<?= htmlspecialchars((string) ($securingMeta['timeframe_to'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="camera_location" class="form-label fw-semibold">Kamera-Standorte / Bereiche <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="camera_location" name="securing_meta[camera_location]" 
                                               value="<?= htmlspecialchars((string) ($securingMeta['camera_location'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                               placeholder="z. B. Haupteingang, Parkhaus Ebene -1, Kameras 03 & 04">
                                        <div class="form-text">Präzise Ortsangabe oder betroffene Kameranummern.</div>
                                    </div>
                                    <div>
                                        <label for="video_notes" class="form-label fw-semibold">Personen- / Fahrzeugmerkmale / Vorfallsbeschreibung</label>
                                        <textarea class="form-control" id="video_notes" name="securing_meta[video_notes]" rows="2" 
                                                  placeholder="z. B. Täter trug rote Jacke, Tatzeitpunkt ca. 22:15 Uhr"><?= htmlspecialchars((string) ($securingMeta['video_notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                                    </div>
                                </div>

                                <!-- TYPE: MAIL -->
                                <div id="fields_MAIL" class="securing-fields <?= $selectedType === 'MAIL' ? '' : 'd-none' ?>">
                                    <div class="mb-3">
                                        <label for="mailbox_address" class="form-label fw-semibold">Betroffenes Postfach / E-Mail-Adresse <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="mailbox_address" name="securing_meta[mailbox_address]" 
                                               value="<?= htmlspecialchars((string) ($securingMeta['mailbox_address'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                               placeholder="z. B. benutzer@unternehmen.de">
                                    </div>
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <label for="mail_timeframe_from" class="form-label fw-semibold">Zeitraum VON <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" id="mail_timeframe_from" name="securing_meta[mail_timeframe_from]" 
                                                   value="<?= htmlspecialchars((string) ($securingMeta['mail_timeframe_from'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="mail_timeframe_to" class="form-label fw-semibold">Zeitraum BIS <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" id="mail_timeframe_to" name="securing_meta[mail_timeframe_to]" 
                                                   value="<?= htmlspecialchars((string) ($securingMeta['mail_timeframe_to'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                        </div>
                                    </div>
                                    <div>
                                        <label for="mail_scope" class="form-label fw-semibold">Umfang der Mailbox-Sicherung</label>
                                        <input type="text" class="form-control" id="mail_scope" name="securing_meta[mail_scope]" 
                                               value="<?= htmlspecialchars((string) ($securingMeta['mail_scope'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                               placeholder="z. B. Vollständiges Postfach inkl. Posteingang, Gesendete &amp; Papierkorb">
                                    </div>
                                </div>

                                <!-- TYPE: CLOUD -->
                                <div id="fields_CLOUD" class="securing-fields <?= $selectedType === 'CLOUD' ? '' : 'd-none' ?>">
                                    <div class="mb-3">
                                        <label for="system_name" class="form-label fw-semibold">System-, Server- oder Dienstbezeichnung <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="system_name" name="securing_meta[system_name]" 
                                               value="<?= htmlspecialchars((string) ($securingMeta['system_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                               placeholder="z. B. Nextcloud, Fileserver 01, Microsoft 365 SharePoint">
                                    </div>
                                    <div class="mb-3">
                                        <label for="cloud_target_data" class="form-label fw-semibold">Pfade, Dateien oder Benutzerkonten <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="cloud_target_data" name="securing_meta[cloud_target_data]" rows="2" 
                                                  placeholder="z. B. Verzeichnis /daten/buchhaltung_2026/ oder Benutzerkonto max.mustermann"><?= htmlspecialchars((string) ($securingMeta['cloud_target_data'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                                    </div>
                                </div>

                                <!-- TYPE: ACCESS_LOG -->
                                <div id="fields_ACCESS_LOG" class="securing-fields <?= $selectedType === 'ACCESS_LOG' ? '' : 'd-none' ?>">
                                    <div class="mb-3">
                                        <label for="doors_points" class="form-label fw-semibold">Türen, Schliessungen oder Zutrittskontrollpunkte <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="doors_points" name="securing_meta[doors_points]" 
                                               value="<?= htmlspecialchars((string) ($securingMeta['doors_points'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                               placeholder="z. B. Haupteingang Schranke Nord, Serverraum Tür 104">
                                    </div>
                                    <div class="mb-3">
                                        <label for="log_timeframe" class="form-label fw-semibold">Relevanter Zeitraum <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="log_timeframe" name="securing_meta[log_timeframe]" 
                                               value="<?= htmlspecialchars((string) ($securingMeta['log_timeframe'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                               placeholder="z. B. 01.03.2026 00:00 Uhr bis 03.03.2026 23:59 Uhr">
                                    </div>
                                    <div>
                                        <label for="card_ids" class="form-label fw-semibold">Transponder-, Chip- oder Kartennummern</label>
                                        <input type="text" class="form-control" id="card_ids" name="securing_meta[card_ids]" 
                                               value="<?= htmlspecialchars((string) ($securingMeta['card_ids'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                               placeholder="Optional: z. B. RFID-UID #A4-9F-12-88">
                                    </div>
                                </div>

                                <!-- TYPE: OTHER -->
                                <div id="fields_OTHER" class="securing-fields <?= $selectedType === 'OTHER' ? '' : 'd-none' ?>">
                                    <div>
                                        <label for="other_details" class="form-label fw-semibold">Genaue Beschreibung der Sicherungsanforderung <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="other_details" name="securing_meta[other_details]" rows="3" 
                                                  placeholder="Spezifizieren Sie hier die technischen Quellen, Parameter und Besonderheiten..."><?= htmlspecialchars((string) ($securingMeta['other_details'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                                <a href="?route=sicherung/antrag&step=2" class="btn btn-outline-secondary">
                                    &larr; Zurück zu Schritt 2
                                </a>
                                <button type="submit" class="btn btn-success px-4 fw-bold">
                                    <i class="bi bi-send-check-fill me-1"></i> Verbindlichen Sicherungsantrag übermitteln
                                </button>
                            </div>
                        </form>

                    <!-- ========================================================= -->
                    <!-- SCHRITT SUCCESS: VORGANGS-ID & ZUGANGSCODE                -->
                    <!-- ========================================================= -->
                    <?php elseif ($createdCase !== null): ?>
                        <div class="text-center py-3">
                            <div class="mb-3">
                                <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                            </div>
                            <h2 class="h3 fw-bold text-dark mb-2">Sicherungsantrag erfolgreich übermittelt!</h2>
                            <p class="text-muted max-w-lg mx-auto mb-4" style="max-width: 620px;">
                                Ihr Antrag wurde verbindlich im Sicherungsportal erfasst und die beigefügte Editionsverfügung
                                sicher hinterlegt. Bitte notieren Sie sich Ihre persönlichen Fall-Zugangsdaten.
                            </p>

                            <!-- Zugangsdaten Box -->
                            <div class="code-display-box my-4">
                                <div class="row align-items-center g-3">
                                    <div class="col-md-6 border-end border-secondary pb-3 pb-md-0">
                                        <small class="text-white-50 text-uppercase d-block mb-1">Offizielle Vorgangs-ID</small>
                                        <div class="code-large text-warning">
                                            <?= htmlspecialchars((string) $createdCase['case_number'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-white-50 text-uppercase d-block mb-1">Ihr geheimer Fall-Zugangscode</small>
                                        <div class="code-large">
                                            <?= htmlspecialchars((string) $createdCase['access_code'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-warning border text-start small mb-4">
                                <strong class="d-block mb-1 text-dark">
                                    <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i> Wichtiger Aufbewahrungshinweis:
                                </strong>
                                Aus Gründen der Datensicherheit und Vertraulichkeit wird dieser Zugangscode
                                <strong>nicht unverschlüsselt per E-Mail versandt</strong>.
                                Bitte drucken Sie diese Bestätigung aus oder notieren Sie den 12-stelligen Code bei Ihren Ermittlungsakten.
                            </div>

                            <div class="d-flex flex-wrap justify-content-center gap-3 no-print">
                                <button type="button" class="btn btn-outline-dark px-4" onclick="window.print()">
                                    <i class="bi bi-printer-fill me-1"></i> Bestätigung drucken
                                </button>
                                <a href="?route=sicherung/fall" class="btn btn-success px-4 fw-bold">
                                    <i class="bi bi-folder2-open me-1"></i> Direkt zum Fallzugang &rarr;
                                </a>
                                <a href="?route=sicherung" class="btn btn-outline-secondary px-3">
                                    Zurück zur Startseite
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer no-print">
        <div class="container text-center">
            <p class="mb-0 text-secondary small">
                &copy; <?= date('Y') ?> Sicherungsportal &middot; Elektronischer Beweismittel-Workflow für Schweizer Ermittlungsbehörden
            </p>
        </div>
    </footer>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectSecuringType(typeKey) {
            // Radio button selektieren
            const radio = document.getElementById('type_' + typeKey);
            if (radio) radio.checked = true;

            // Karten visuell umschalten
            document.querySelectorAll('.type-selector-card').forEach(el => el.classList.remove('selected'));
            const activeCard = document.getElementById('card_type_' + typeKey);
            if (activeCard) activeCard.classList.add('selected');

            // Formularblöcke ein-/ausblenden
            document.querySelectorAll('.securing-fields').forEach(el => el.classList.add('d-none'));
            const targetFields = document.getElementById('fields_' + typeKey);
            if (targetFields) targetFields.classList.remove('d-none');
        }
    </script>
</body>
</html>
