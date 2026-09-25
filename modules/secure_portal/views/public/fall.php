<?php

declare(strict_types=1);

/**
 * Sicherungsportal: Fallansicht für Antragsteller (/sicherung/fall)
 *
 * Vollständige Read-Only-Ansicht des eigenen Vorgangs für den Antragsteller (Polizei/StA).
 * Keine administrativen Bearbeitungsmöglichkeiten.
 *
 * @var array<string, mixed> $case
 * @var array<int, array<string, mixed>> $caseLogs
 */

$settings = class_exists('Settings') ? Settings::all() : [];
$siteTitle = 'Polizei- & Justiz-Sicherungsportal';
$adminBrandColor = (string) ($settings['admin_brand_color'] ?? '#0f4c81');
$primaryColor = !empty($settings['homepage_primary_color']) ? (string) $settings['homepage_primary_color'] : $adminBrandColor;
$logoPath = (string) ($settings['homepage_logo_path'] ?? '');

$caseNumber = (string) ($case['case_number'] ?? '');
$status = (string) ($case['status'] ?? 'new');
$statusLabel = (string) ($case['status_label'] ?? ucfirst($status));
$statusBadge = (string) ($case['status_badge'] ?? 'bg-secondary');
$statusDesc = (string) ($case['status_desc'] ?? '');

$secTypeLabel = (string) ($case['securing_type_label'] ?? ($case['securing_type'] ?? ''));
$meta = (array) ($case['securing_meta_decoded'] ?? []);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($caseNumber, ENT_QUOTES, 'UTF-8') ?> - Vorgangsstatus | Sicherungsportal</title>
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

        .case-header-card {
            background-color: #ffffff;
            border-radius: 0.75rem;
            border: 1px solid var(--sec-border);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .detail-card {
            background-color: #ffffff;
            border-radius: 0.75rem;
            border: 1px solid var(--sec-border);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.04);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .detail-card-header {
            background-color: #f8fafc;
            border-bottom: 1px solid var(--sec-border);
            padding: 1rem 1.5rem;
            font-weight: 600;
            color: #1e293b;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .detail-card-body {
            padding: 1.5rem;
        }

        /* Timeline / History */
        .timeline {
            position: relative;
            padding-left: 2rem;
            margin-left: 0.5rem;
            border-left: 2px solid #e2e8f0;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 1.75rem;
        }

        .timeline-item:last-child {
            margin-bottom: 0;
        }

        .timeline-dot {
            position: absolute;
            left: -2.45rem;
            top: 0.2rem;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background-color: #2563eb;
            border: 3px solid #ffffff;
            box-shadow: 0 0 0 2px #93c5fd;
        }

        .timeline-dot.success {
            background-color: #059669;
            box-shadow: 0 0 0 2px #a7f3d0;
        }

        .timeline-dot.warning {
            background-color: #d97706;
            box-shadow: 0 0 0 2px #fde68a;
        }

        /* Status-Steps Tracker */
        .status-steps {
            display: flex;
            justify-content: space-between;
            margin: 1.5rem 0 0.5rem;
            position: relative;
        }

        .status-steps::before {
            content: '';
            position: absolute;
            top: 14px;
            left: 5%;
            right: 5%;
            height: 3px;
            background-color: #e2e8f0;
            z-index: 1;
        }

        .step-point {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            font-size: 0.785rem;
            font-weight: 600;
            color: #94a3b8;
        }

        .step-dot {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #ffffff;
            border: 2px solid #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.4rem;
            color: #64748b;
            font-size: 0.85rem;
        }

        .step-point.active .step-dot {
            border-color: #2563eb;
            background-color: #2563eb;
            color: #ffffff;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.2);
        }

        .step-point.completed .step-dot {
            border-color: #059669;
            background-color: #059669;
            color: #ffffff;
        }

        .step-point.active, .step-point.completed {
            color: #1e293b;
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

        @media print {
            .portal-navbar, .footer, .btn, .no-print {
                display: none !important;
            }
            body {
                background: #ffffff !important;
            }
            .case-header-card, .detail-card {
                border: 1px solid #000 !important;
                box-shadow: none !important;
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
                        <small class="text-white-50">Fallzugang: <?= htmlspecialchars($caseNumber, ENT_QUOTES, 'UTF-8') ?></small>
                    </div>
                </a>

                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-light btn-sm px-3" onclick="window.print()">
                        <i class="bi bi-printer-fill me-1"></i> Drucken
                    </button>
                    <a href="?route=sicherung/abmelden" class="btn btn-danger btn-sm px-3 fw-semibold">
                        <i class="bi bi-box-arrow-right me-1"></i> Fallzugang beenden
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="container my-5">

        <!-- Vorgangs-Kopfkarte -->
        <div class="case-header-card">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 pb-3 border-bottom">
                <div>
                    <span class="badge bg-light text-dark border px-2 py-1 font-monospace mb-2">
                        Vorgangs-ID: <?= htmlspecialchars($caseNumber, ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <h2 class="h3 fw-bold text-dark mb-1">
                        Aktenzeichen: <?= htmlspecialchars((string) ($case['reference_number'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                    </h2>
                    <div class="text-muted small">
                        Dienststelle: <strong><?= htmlspecialchars((string) ($case['police_department'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong> &middot; 
                        Sachbearbeiter: <strong><?= htmlspecialchars((string) ($case['contact_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                </div>

                <div class="text-end">
                    <span class="badge <?= $statusBadge ?> fs-6 px-3 py-2 d-inline-flex align-items-center gap-2">
                        <i class="bi <?= htmlspecialchars((string) ($case['status_icon'] ?? 'bi-info-circle'), ENT_QUOTES, 'UTF-8') ?>"></i>
                        <?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <div class="text-muted small mt-1">
                        Eingegangen: <?= date('d.m.Y H:i', strtotime((string) $case['created_at'])) ?> Uhr
                    </div>
                </div>
            </div>

            <!-- Status-Fortschrittsleiste -->
            <?php
            $stepIndex = match($status) {
                'new' => 1,
                'in_review' => 2,
                'in_progress' => 3,
                'available' => 4,
                'closed', 'archived' => 5,
                default => 1,
            };
            ?>
            <div class="status-steps">
                <div class="step-point <?= $stepIndex === 1 ? 'active' : ($stepIndex > 1 ? 'completed' : '') ?>">
                    <div class="step-dot"><?= $stepIndex > 1 ? '<i class="bi bi-check-lg"></i>' : '1' ?></div>
                    <span>Eingegangen</span>
                </div>
                <div class="step-point <?= $stepIndex === 2 ? 'active' : ($stepIndex > 2 ? 'completed' : '') ?>">
                    <div class="step-dot"><?= $stepIndex > 2 ? '<i class="bi bi-check-lg"></i>' : '2' ?></div>
                    <span>In Prüfung</span>
                </div>
                <div class="step-point <?= $stepIndex === 3 ? 'active' : ($stepIndex > 3 ? 'completed' : '') ?>">
                    <div class="step-dot"><?= $stepIndex > 3 ? '<i class="bi bi-check-lg"></i>' : '3' ?></div>
                    <span>In Bearbeitung</span>
                </div>
                <div class="step-point <?= $stepIndex === 4 ? 'active' : ($stepIndex > 4 ? 'completed' : '') ?>">
                    <div class="step-dot"><?= $stepIndex > 4 ? '<i class="bi bi-check-lg"></i>' : '4' ?></div>
                    <span>Bereitgestellt</span>
                </div>
                <div class="step-point <?= $stepIndex >= 5 ? 'completed' : '' ?>">
                    <div class="step-dot"><?= $stepIndex >= 5 ? '<i class="bi bi-check-lg"></i>' : '5' ?></div>
                    <span>Abgeschlossen</span>
                </div>
            </div>

            <?php if ($status === 'clarification_required'): ?>
                <div class="alert alert-danger mt-4 mb-0 d-flex align-items-center gap-3">
                    <i class="bi bi-question-octagon-fill fs-3 text-danger flex-shrink-0"></i>
                    <div>
                        <strong class="d-block">Rückfrage der bearbeitenden Stelle erforderlich:</strong>
                        Bitte prüfen Sie die nachfolgenden Statusmitteilungen oder kontaktieren Sie die zuständigen Techniker.
                    </div>
                </div>
            <?php elseif ($status === 'available'): ?>
                <div class="alert alert-success mt-4 mb-0 d-flex align-items-center gap-3">
                    <i class="bi bi-check-circle-fill fs-3 text-success flex-shrink-0"></i>
                    <div>
                        <strong class="d-block">Sicherungsdaten stehen bereit!</strong>
                        Die angeforderten Beweismittel wurden erfolgreich gesichert und stehen zur Übergabe bzw. Abholung zur Verfügung.
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="row g-4">
            <!-- Linke Spalte: Details & Spezifikation -->
            <div class="col-lg-8">

                <!-- 1. Basisinformationen -->
                <div class="detail-card">
                    <div class="detail-card-header">
                        <span><i class="bi bi-card-text text-primary me-2"></i> Antragsumfang &amp; Basisdaten</span>
                    </div>
                    <div class="detail-card-body">
                        <div class="mb-3">
                            <label class="text-muted small fw-bold d-block text-uppercase">Beschreibung des Sicherungsumfangs</label>
                            <div class="p-3 bg-light rounded text-dark border">
                                <?= nl2br(htmlspecialchars((string) ($case['description'] ?? ''), ENT_QUOTES, 'UTF-8')) ?>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold d-block text-uppercase">Gewünschter Sicherungstermin</label>
                                <span class="fw-semibold">
                                    <?= !empty($case['desired_date']) ? date('d.m.Y', strtotime((string) $case['desired_date'])) : 'Keine spezifische Frist angegeben' ?>
                                </span>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold d-block text-uppercase">Telefonkontakt</label>
                                <span class="fw-semibold"><?= htmlspecialchars((string) ($case['contact_phone'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold d-block text-uppercase">E-Mail für Benachrichtigungen</label>
                                <span class="fw-semibold"><?= htmlspecialchars((string) ($case['contact_email'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold d-block text-uppercase">Besondere Hinweise</label>
                                <span class="text-muted"><?= htmlspecialchars((string) ($case['remarks'] ?? 'Keine'), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Spezifikation der Sicherungsart -->
                <div class="detail-card">
                    <div class="detail-card-header">
                        <span><i class="bi bi-hdd-network text-primary me-2"></i> Sicherungsart &amp; technische Spezifikation</span>
                        <span class="badge bg-primary-subtle text-primary border">
                            <?= htmlspecialchars($secTypeLabel, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                    <div class="detail-card-body">
                        <?php if ($case['securing_type'] === 'VIDEO'): ?>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="text-muted small fw-bold d-block text-uppercase">Zeitraum VON</label>
                                    <span class="fw-semibold">
                                        <?= !empty($meta['timeframe_from']) ? htmlspecialchars((string) $meta['timeframe_from'], ENT_QUOTES, 'UTF-8') : '-' ?>
                                    </span>
                                </div>
                                <div class="col-md-6">
                                    <label class="text-muted small fw-bold d-block text-uppercase">Zeitraum BIS</label>
                                    <span class="fw-semibold">
                                        <?= !empty($meta['timeframe_to']) ? htmlspecialchars((string) $meta['timeframe_to'], ENT_QUOTES, 'UTF-8') : '-' ?>
                                    </span>
                                </div>
                                <div class="col-12">
                                    <label class="text-muted small fw-bold d-block text-uppercase">Kamera-Standorte / Bereiche</label>
                                    <span class="fw-semibold">
                                        <?= htmlspecialchars((string) ($meta['camera_location'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </div>
                                <?php if (!empty($meta['video_notes'])): ?>
                                    <div class="col-12">
                                        <label class="text-muted small fw-bold d-block text-uppercase">Personen- / Vorfallsbeschreibung</label>
                                        <div class="p-2 bg-light rounded small">
                                            <?= nl2br(htmlspecialchars((string) $meta['video_notes'], ENT_QUOTES, 'UTF-8')) ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                        <?php elseif ($case['securing_type'] === 'MAIL'): ?>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="text-muted small fw-bold d-block text-uppercase">Postfach / E-Mail</label>
                                    <span class="fw-semibold"><?= htmlspecialchars((string) ($meta['mailbox_address'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <div class="col-md-6">
                                    <label class="text-muted small fw-bold d-block text-uppercase">Umfang</label>
                                    <span><?= htmlspecialchars((string) ($meta['mail_scope'] ?? 'Vollständig'), ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <div class="col-md-6">
                                    <label class="text-muted small fw-bold d-block text-uppercase">Zeitraum von</label>
                                    <span><?= htmlspecialchars((string) ($meta['mail_timeframe_from'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <div class="col-md-6">
                                    <label class="text-muted small fw-bold d-block text-uppercase">Zeitraum bis</label>
                                    <span><?= htmlspecialchars((string) ($meta['mail_timeframe_to'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </div>

                        <?php elseif ($case['securing_type'] === 'CLOUD'): ?>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="text-muted small fw-bold d-block text-uppercase">System / Dienst</label>
                                    <span class="fw-semibold"><?= htmlspecialchars((string) ($meta['system_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <div class="col-12">
                                    <label class="text-muted small fw-bold d-block text-uppercase">Zieldateien / Pfade / Accounts</label>
                                    <div class="p-2 bg-light rounded font-monospace small">
                                        <?= nl2br(htmlspecialchars((string) ($meta['cloud_target_data'] ?? '-'), ENT_QUOTES, 'UTF-8')) ?>
                                    </div>
                                </div>
                            </div>

                        <?php elseif ($case['securing_type'] === 'ACCESS_LOG'): ?>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="text-muted small fw-bold d-block text-uppercase">Türen / Zutrittspunkte</label>
                                    <span class="fw-semibold"><?= htmlspecialchars((string) ($meta['doors_points'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <div class="col-md-6">
                                    <label class="text-muted small fw-bold d-block text-uppercase">Zeitraum</label>
                                    <span><?= htmlspecialchars((string) ($meta['log_timeframe'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </div>

                        <?php else: ?>
                            <div>
                                <label class="text-muted small fw-bold d-block text-uppercase">Spezifikation</label>
                                <div class="p-3 bg-light rounded">
                                    <?= nl2br(htmlspecialchars((string) ($meta['other_details'] ?? 'Keine weiteren Angaben'), ENT_QUOTES, 'UTF-8')) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- Rechte Spalte: Editionsverfügung & Verlauf -->
            <div class="col-lg-4">

                <!-- Editionsverfügung Status -->
                <div class="detail-card">
                    <div class="detail-card-header">
                        <span><i class="bi bi-file-earmark-lock-fill text-danger me-2"></i> Editionsverfügung</span>
                    </div>
                    <div class="detail-card-body">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <i class="bi bi-file-earmark-pdf-fill fs-1 text-danger"></i>
                            <div>
                                <div class="fw-bold text-dark">Editionsverfügung (PDF)</div>
                                <small class="text-muted">
                                    Größe: <?= number_format(((int)($case['warrant_file_size'] ?? 0)) / 1024 / 1024, 2) ?> MB
                                </small>
                            </div>
                        </div>

                        <div class="p-2 bg-success-subtle text-success border border-success-subtle rounded small mb-2 text-center">
                            <i class="bi bi-check-circle-fill me-1"></i> Dokument manipulationssicher hinterlegt
                        </div>
                        <div class="text-muted small text-center">
                            Hochgeladen am <?= date('d.m.Y H:i', strtotime((string) $case['warrant_uploaded_at'])) ?> Uhr
                        </div>
                    </div>
                </div>

                <!-- Verlauf & Mitteilungen -->
                <div class="detail-card">
                    <div class="detail-card-header">
                        <span><i class="bi bi-clock-history text-primary me-2"></i> Vorgangsprotokoll &amp; Updates</span>
                    </div>
                    <div class="detail-card-body">
                        <?php if (empty($caseLogs)): ?>
                            <div class="text-center text-muted small py-3">
                                Noch keine weiteren Protokolleinträge vorhanden.
                            </div>
                        <?php else: ?>
                            <div class="timeline">
                                <?php foreach ($caseLogs as $log): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-dot <?= str_contains((string) $log['action'], 'status') ? 'success' : '' ?>"></div>
                                        <div class="small fw-semibold text-dark mb-1">
                                            <?= htmlspecialchars((string) $log['message'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between text-muted" style="font-size: 0.75rem;">
                                            <span><?= htmlspecialchars((string) $log['author'], ENT_QUOTES, 'UTF-8') ?></span>
                                            <span><?= date('d.m.Y H:i', strtotime((string) $log['created_at'])) ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container text-center">
            <p class="mb-0 text-secondary small">
                &copy; <?= date('Y') ?> Sicherungsportal &middot; Elektronischer Beweismittel-Workflow für Ermittlungsbehörden
            </p>
        </div>
    </footer>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
