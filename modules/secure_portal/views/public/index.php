<?php

declare(strict_types=1);

/**
 * Sicherungsportal: Öffentliche Startseite (/sicherung)
 *
 * Präsentiert die drei Hauptzugangswege:
 * 1. Privatperson (Aufklärung & Info-Text)
 * 2. Polizei / Staatsanwaltschaft (Mehrteiliges Antragsformular)
 * 3. Fallzugang (Einsicht in bestehenden Fall via Vorgangs-ID + Zugangscode)
 */

$flashError = $flashError ?? ($_SESSION['flash_secure_error'] ?? null);
$flashSuccess = $flashSuccess ?? ($_SESSION['flash_secure_success'] ?? null);
$flashInfo = $flashInfo ?? ($_SESSION['flash_secure_info'] ?? null);
unset($_SESSION['flash_secure_error'], $_SESSION['flash_secure_success'], $_SESSION['flash_secure_info']);

$settings = class_exists('Settings') ? Settings::all() : [];
$siteTitle = 'Polizei- & Justiz-Sicherungsportal';
$adminBrandColor = (string) ($settings['admin_brand_color'] ?? '#0f4c81');
$primaryColor = !empty($settings['homepage_primary_color']) ? (string) $settings['homepage_primary_color'] : $adminBrandColor;
$logoPath = (string) ($settings['homepage_logo_path'] ?? '');

$customerName = class_exists('Settings') 
    ? (string) (Settings::get('customer_name') 
        ?: Settings::get('company_name') 
        ?: Settings::get('site_name') 
        ?: Settings::get('homepage_title') 
        ?: 'StudioCreativo CMS')
    : 'StudioCreativo CMS';
if (empty(trim($customerName))) {
    $customerName = 'StudioCreativo CMS';
}

$csrfToken = class_exists('Csrf') ? Csrf::getToken() : '';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($siteTitle, ENT_QUOTES, 'UTF-8') ?> - Digitale Beweissicherung</title>
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

        .btn-header-antrag {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #ffffff !important;
            font-weight: 700;
            padding: 0.45rem 1.15rem;
            border-radius: 0.5rem;
            border: 1px solid rgba(255, 255, 255, 0.35);
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.4);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-header-antrag:hover {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: #ffffff !important;
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.6);
            transform: translateY(-1px);
        }

        .hero-banner {
            background: linear-gradient(180deg, rgba(15, 76, 129, 0.08) 0%, rgba(244, 246, 249, 1) 100%);
            padding: 3.5rem 0 2.5rem;
            border-bottom: 1px solid var(--sec-border);
        }

        .hero-badge {
            background-color: rgba(14, 116, 144, 0.12);
            color: #0e7490;
            font-size: 0.825rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            padding: 0.4rem 0.85rem;
            border-radius: 9999px;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .portal-card {
            background-color: #ffffff;
            border-radius: 0.85rem;
            display: flex;
            flex-direction: column;
            height: 100%;
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 25px -4px rgba(15, 23, 42, 0.12), 0 4px 10px -2px rgba(15, 23, 42, 0.06);
        }

        .portal-card.card-private {
            border: 2.5px solid #475569;
            border-top: 8px solid #1e293b;
        }

        .portal-card.card-private:hover {
            transform: translateY(-5px);
            border-color: #1e293b;
            box-shadow: 0 20px 35px -5px rgba(30, 41, 59, 0.25), 0 8px 16px -4px rgba(30, 41, 59, 0.15);
        }

        .portal-card.card-police {
            border: 3px solid #1d4ed8;
            border-top: 8px solid #1e40af;
            box-shadow: 0 14px 35px -5px rgba(29, 78, 216, 0.22), 0 6px 12px -3px rgba(15, 23, 42, 0.1);
        }

        .portal-card.card-police:hover {
            transform: translateY(-5px);
            border-color: #1e40af;
            box-shadow: 0 24px 45px -5px rgba(29, 78, 216, 0.32), 0 10px 20px -4px rgba(29, 78, 216, 0.2);
        }

        .portal-card.card-access {
            border: 2.5px solid #059669;
            border-top: 8px solid #047857;
            box-shadow: 0 12px 30px -5px rgba(5, 150, 105, 0.18), 0 4px 10px -2px rgba(15, 23, 42, 0.08);
        }

        .portal-card.card-access:hover {
            transform: translateY(-5px);
            border-color: #047857;
            box-shadow: 0 20px 35px -5px rgba(5, 150, 105, 0.28), 0 8px 16px -4px rgba(5, 150, 105, 0.16);
        }

        .portal-card-header {
            padding: 1.5rem 1.5rem 0.75rem;
        }

        .portal-icon-wrapper {
            width: 54px;
            height: 54px;
            border-radius: 0.65rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.65rem;
            margin-bottom: 1rem;
        }

        .icon-police {
            background-color: #dbeafe;
            color: #1e40af;
            border: 2px solid #93c5fd;
        }

        .icon-private {
            background-color: #e2e8f0;
            color: #1e293b;
            border: 2px solid #cbd5e1;
        }

        .icon-access {
            background-color: #d1fae5;
            color: #065f46;
            border: 2px solid #a7f3d0;
        }

        .portal-card-body {
            padding: 0 1.5rem 1.25rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .portal-card-footer {
            margin-top: auto;
            padding: 1rem 1.5rem 1.25rem;
            border-top: 2px solid #e2e8f0;
            background-color: #f8fafc;
        }

        .btn-portal-police {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            color: #ffffff;
            font-weight: 700;
            padding: 0.85rem 1.25rem;
            border-radius: 0.5rem;
            border: 0;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px rgba(29, 78, 216, 0.35);
        }

        .btn-portal-police:hover {
            background: linear-gradient(135deg, #1e40af 0%, #172554 100%);
            color: #ffffff;
            box-shadow: 0 6px 18px rgba(29, 78, 216, 0.45);
            transform: translateY(-1px);
        }

        .btn-portal-access {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: #ffffff;
            font-weight: 700;
            padding: 0.85rem 1.25rem;
            border-radius: 0.5rem;
            border: 0;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px rgba(5, 150, 105, 0.35);
        }

        .btn-portal-access:hover {
            background: linear-gradient(135deg, #047857 0%, #064e3b 100%);
            color: #ffffff;
            box-shadow: 0 6px 18px rgba(5, 150, 105, 0.45);
            transform: translateY(-1px);
        }

        /* High Contrast Modals */
        .modal-contrast .modal-content {
            border: 3px solid #0f172a !important;
            border-radius: 1rem;
            box-shadow: 0 30px 70px rgba(0, 0, 0, 0.45) !important;
            overflow: hidden;
            background-color: #ffffff;
        }

        .modal-contrast .modal-header {
            padding: 1.25rem 1.75rem;
            color: #ffffff;
        }

        .modal-contrast .modal-header.header-police {
            background: linear-gradient(135deg, #0f2b5c 0%, #1e3a8a 100%);
            border-bottom: 3px solid #3b82f6;
        }

        .modal-contrast .modal-header.header-private {
            background: linear-gradient(135deg, #0f172a 0%, #334155 100%);
            border-bottom: 3px solid #64748b;
        }

        .modal-contrast .modal-header.header-access {
            background: linear-gradient(135deg, #064e3b 0%, #047857 100%);
            border-bottom: 3px solid #10b981;
        }

        .modal-contrast .modal-header .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
            opacity: 0.9;
        }

        .modal-contrast .modal-header .btn-close:hover {
            opacity: 1;
        }

        .modal-contrast .modal-body {
            padding: 2rem 1.75rem;
            color: #0f172a;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .modal-contrast .modal-footer {
            border-top: 2px solid #cbd5e1;
            padding: 1.25rem 1.75rem;
            background-color: #f1f5f9;
        }

        .modal-contrast .alert-warning-contrast {
            background-color: #fef3c7;
            border: 2.5px solid #d97706;
            color: #78350f;
            border-radius: 0.65rem;
            font-weight: 500;
        }

        .modal-contrast .alert-info-contrast {
            background-color: #eff6ff;
            border: 2.5px solid #1d4ed8;
            color: #1e3a8a;
            border-radius: 0.65rem;
            font-weight: 500;
        }

        .modal-contrast .step-card-contrast {
            border: 2.5px solid #2563eb;
            background-color: #f0f7ff;
            border-radius: 0.65rem;
            transition: all 0.15s ease;
        }

        .modal-contrast .step-card-contrast:hover {
            border-color: #1e40af;
            background-color: #ffffff;
            box-shadow: 0 6px 14px rgba(37, 99, 235, 0.2);
        }

        .modal-contrast .emergency-box-contrast {
            background-color: #fee2e2;
            border: 2.5px solid #b91c1c;
            color: #7f1d1d;
            border-radius: 0.65rem;
        }

        .modal-contrast .form-control {
            border: 2.5px solid #475569;
            color: #0f172a;
            font-weight: 600;
            background-color: #ffffff;
        }

        .modal-contrast .form-control:focus {
            border-color: #059669;
            box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.3);
            color: #0f172a;
        }

        .legal-notice-box {
            background-color: #ffffff;
            border: 2px solid #cbd5e1;
            border-radius: 0.75rem;
            padding: 1.75rem;
            box-shadow: 0 6px 16px -2px rgba(15, 23, 42, 0.06);
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
    </style>
</head>
<body>

    <!-- Header / Navbar -->
    <header class="portal-navbar">
        <div class="container">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <?php if ($logoPath !== ''): ?>
                        <img src="<?= htmlspecialchars($logoPath, ENT_QUOTES, 'UTF-8') ?>" alt="Logo" style="height: 42px; max-width: 180px; object-fit: contain;">
                    <?php else: ?>
                        <div class="bg-white bg-opacity-10 p-2 rounded text-white d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-shield-lock-fill fs-4 text-warning"></i>
                        </div>
                    <?php endif; ?>
                    <div>
                        <h1 class="h5 mb-0 fw-bold text-white tracking-tight">Sicherungsportal</h1>
                        <small class="text-white-50">Behördliche Datensicherung &amp; Beweismittel-Workflow</small>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <a href="?route=sicherung/fallzugang" class="btn btn-outline-light btn-sm px-3">
                        <i class="bi bi-key-fill me-1"></i> Fallzugang
                    </a>
                    <a href="?route=sicherung/antrag" class="btn-header-antrag">
                        <i class="bi bi-file-earmark-plus-fill text-warning"></i> Sicherungsantrag einreichen
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Flash Messages -->
    <?php if ($flashError): ?>
        <div class="container mt-4">
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <i class="bi bi-exclamation-octagon-fill me-2 fs-5"></i>
                <?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schliessen"></button>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($flashSuccess): ?>
        <div class="container mt-4">
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                <?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schliessen"></button>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($flashInfo): ?>
        <div class="container mt-4">
            <div class="alert alert-info alert-dismissible fade show shadow-sm" role="alert">
                <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                <?= htmlspecialchars($flashInfo, ENT_QUOTES, 'UTF-8') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schliessen"></button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Hero Bereich -->
    <section class="hero-banner">
        <div class="container text-center">
            <div class="hero-badge mb-3 mx-auto">
                <i class="bi bi-shield-check"></i> Rechtskonforme digitale Sicherungsbereitstellung gemäss Art. 265 ff. Schweizer StPO
            </div>
            <h2 class="display-6 fw-bold text-dark mb-3">
                Zentrales Portal zur Sicherung digitaler Beweismittel (Schweiz)
            </h2>
            <p class="lead text-muted mx-auto mb-0" style="max-width: 840px;">
                Offizielle Schnittstelle zur Entgegennahme von Editionsverfügungen der Staatsanwaltschaften und Gerichte,
                zur manipulationssicheren Bereitstellung von Video-, Zutritts- und Systemdaten sowie zur transparenten Fallverfolgung.
            </p>
        </div>
    </section>

    <!-- Die 3 grossen Hauptboxen (kompakte Übersicht mit Modal-Detailansichten) -->
    <main class="container my-5">
        <div class="row g-4 align-items-stretch">
            
            <!-- BOX 1: Privatperson -->
            <div class="col-lg-4 col-md-12">
                <div class="portal-card card-private h-100">
                    <div class="portal-card-header">
                        <div class="portal-icon-wrapper icon-private">
                            <i class="bi bi-person-fill-exclamation"></i>
                        </div>
                        <div>
                            <span class="badge bg-secondary text-white px-2 py-1 mb-2 fw-semibold">Geschädigter &amp; Zeugen</span>
                            <h3 class="h4 fw-bold text-dark mb-1">1. Privatperson</h3>
                            <p class="text-muted small mb-0">
                                Hinweise zur Datenherausgabe in der Schweiz
                            </p>
                        </div>
                    </div>

                    <div class="portal-card-body">
                        <p class="small text-secondary mb-3">
                            Aus Datenschutzgründen (DSG / ZGB) dürfen Aufzeichnungen <strong>nicht direkt an Privatpersonen</strong> ausgehändigt werden.
                        </p>
                        <div class="d-flex flex-wrap gap-2 mb-2 mt-auto">
                            <span class="badge bg-light text-dark border small">
                                <i class="bi bi-shield-slash me-1 text-secondary"></i> Keine Direktherausgabe
                            </span>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle small fw-bold">
                                <i class="bi bi-telephone-fill me-1"></i> Notruf 117
                            </span>
                        </div>
                    </div>

                    <div class="portal-card-footer">
                        <button type="button" class="btn btn-outline-dark w-100 fw-bold d-flex align-items-center justify-content-center gap-2 border-2 py-2" data-bs-toggle="modal" data-bs-target="#modalPrivatperson">
                            <i class="bi bi-info-circle-fill text-secondary"></i> Hinweise &amp; Notruf öffnen
                        </button>
                    </div>
                </div>
            </div>

            <!-- BOX 2: Polizei / Staatsanwaltschaft -->
            <div class="col-lg-4 col-md-12">
                <div class="portal-card card-police h-100">
                    <div class="portal-card-header">
                        <div class="portal-icon-wrapper icon-police">
                            <i class="bi bi-shield-fill-check"></i>
                        </div>
                        <div>
                            <span class="badge bg-primary text-white px-2 py-1 mb-2 fw-semibold">Behördenzugang Schweiz</span>
                            <h3 class="h4 fw-bold text-dark mb-1">2. Polizei &amp; StA</h3>
                            <p class="text-muted small mb-0">
                                Editionsbegehren nach Art. 265 StPO einreichen
                            </p>
                        </div>
                    </div>

                    <div class="portal-card-body">
                        <p class="small text-secondary mb-3">
                            Digitale Einreichung von Editionsverfügungen für Kantonspolizei, Stadtpolizei, fedpol und Staatsanwaltschaften.
                        </p>
                        <div class="d-flex flex-wrap gap-2 mb-2 mt-auto">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle small fw-bold">
                                <i class="bi bi-shield-check me-1"></i> Art. 265 StPO
                            </span>
                            <span class="badge bg-light text-dark border small">
                                <i class="bi bi-file-earmark-pdf me-1"></i> PDF-Upload
                            </span>
                        </div>
                    </div>

                    <div class="portal-card-footer">
                        <button type="button" class="btn btn-portal-police w-100 fw-bold d-flex align-items-center justify-content-center gap-2 py-2 shadow" data-bs-toggle="modal" data-bs-target="#modalPolizeiInfo">
                            <i class="bi bi-diagram-3-fill fs-5"></i> Workflow &amp; Vorgaben ansehen
                        </button>
                    </div>
                </div>
            </div>

            <!-- BOX 3: Fallzugang (Case Login) -->
            <div class="col-lg-4 col-md-12">
                <div class="portal-card card-access h-100">
                    <div class="portal-card-header">
                        <div class="portal-icon-wrapper icon-access">
                            <i class="bi bi-folder-check"></i>
                        </div>
                        <div>
                            <span class="badge bg-success text-white px-2 py-1 mb-2 fw-semibold">Antragsteller-Bereich</span>
                            <h3 class="h4 fw-bold text-dark mb-1">3. Fallzugang</h3>
                            <p class="text-muted small mb-0">
                                Statusabfrage &amp; sicherer Datenabruf
                            </p>
                        </div>
                    </div>

                    <div class="portal-card-body">
                        <p class="small text-secondary mb-3">
                            Bereits eingereichten Fall mit Vorgangs-ID und 12-stelligem Fall-Zugangscode abrufen und Daten downloaden.
                        </p>
                        <div class="d-flex flex-wrap gap-2 mb-2 mt-auto">
                            <span class="badge bg-success-subtle text-success border border-success-subtle small fw-bold">
                                <i class="bi bi-key-fill me-1"></i> 12-stelliger Fallcode
                            </span>
                            <span class="badge bg-light text-dark border small">
                                <i class="bi bi-eye me-1"></i> Status in Echtzeit
                            </span>
                        </div>
                    </div>

                    <div class="portal-card-footer">
                        <button type="button" class="btn btn-portal-access w-100 fw-bold d-flex align-items-center justify-content-center gap-2 py-2 shadow" data-bs-toggle="modal" data-bs-target="#modalFallzugang">
                            <i class="bi bi-box-arrow-in-right fs-5"></i> Fallzugang öffnen
                        </button>
                    </div>
                </div>
            </div>

        </div>

        <!-- Vertrauens- & Prozess-Informationen -->
        <div class="legal-notice-box mt-5">
            <div class="row align-items-center g-4">
                <div class="col-md-8">
                    <h5 class="fw-bold text-dark mb-2">
                        <i class="bi bi-file-earmark-lock text-primary me-2"></i>
                        Rechtsgrundlagen (Schweiz) &amp; Sicherheitsstandards
                    </h5>
                    <p class="small text-muted mb-0">
                        Das Sicherungsportal erfüllt die Vorgaben der Schweizerischen Strafprozessordnung (insbesondere Art. 265 ff. StPO zur Herausgabepflicht und Editionsverfügung)
                        sowie des Bundesgesetzes über den Datenschutz (DSG, SR 235.1) und des BÜPF (SR 780.1). Hochgeladene Editionsverfügungen und bereitgestellte Beweismittel werden verschlüsselt gespeichert,
                        vor unbefugtem Zugriff geschützt und nach Ablauf gesetzlicher Aufbewahrungsfristen revisionssicher vernichtet.
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="d-inline-flex flex-column gap-1 text-md-end">
                        <span class="badge bg-light text-dark border p-2 text-start">
                            <i class="bi bi-shield-check text-success me-1"></i> Art. 265 StPO konform
                        </span>
                        <span class="badge bg-light text-dark border p-2 text-start">
                            <i class="bi bi-shield-lock text-primary me-1"></i> Schweizer Datenschutz (DSG)
                        </span>
                        <span class="badge bg-light text-dark border p-2 text-start">
                            <i class="bi bi-hash text-secondary me-1"></i> SHA-256 Integritätsprüfung
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- ========================================================================= -->
    <!-- MODALS: DETAILANSICHTEN DER 3 BEREICHE (HIGH CONTRAST)                  -->
    <!-- ========================================================================= -->

    <!-- MODAL 1: Privatperson -->
    <div class="modal fade modal-contrast" id="modalPrivatperson" tabindex="-1" aria-labelledby="modalPrivatpersonLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content shadow-lg">
                <div class="modal-header header-private">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-white text-dark p-2 rounded shadow-sm">
                            <i class="bi bi-person-fill-exclamation fs-4"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold text-white mb-0" id="modalPrivatpersonLabel">
                                Wichtige Hinweise für Privatpersonen &amp; Zeugen
                            </h5>
                            <small class="text-white-50 fw-semibold">Rechtliche Grundlagen &amp; Vorgehensweise in der Schweiz</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schliessen"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="alert alert-warning-contrast p-3 d-flex gap-3 mb-4">
                        <i class="bi bi-shield-slash-fill fs-3 text-warning flex-shrink-0 mt-1"></i>
                        <div>
                            <strong class="text-dark d-block mb-1 fs-6">Keine Direktherausgabe an Privatpersonen</strong>
                            Aus datenschutz- und persönlichkeitsrechtlichen Gründen gemäss Schweizer Recht
                            (Bundesgesetz über den Datenschutz <strong>DSG</strong>, Art. 28 Zivilgesetzbuch <strong>ZGB</strong>) dürfen Videoaufzeichnungen (CCTV),
                            Zutritts- und Kommunikationsdaten <strong>nicht direkt an Privatpersonen, Geschädigte oder Zeugen</strong> übergeben werden.
                        </div>
                    </div>

                    <h6 class="fw-bold text-dark mb-2 fs-6">
                        <i class="bi bi-list-check text-primary me-2"></i>Sie sind Betroffener eines Vorfalls oder möchten einen Schaden melden?
                    </h6>
                    <p class="text-muted small mb-3">
                        Bitte beachten Sie die folgende Vorgehensweise, um eine rechtssichere Beweissicherung zu veranlassen:
                    </p>

                    <ol class="small text-dark ps-3 mb-4">
                        <li class="mb-2">
                            <strong>Anzeige erstatten:</strong> Erstatten Sie unverzüglich Anzeige beim zuständigen Polizeiposten Ihrer Kantons- oder Stadtpolizei.
                        </li>
                        <li class="mb-2">
                            <strong>Ereignisdaten angeben:</strong> Nennen Sie den ermittelnden Beamten das genaue Datum, die Uhrzeit und den präzisen Tat- bzw. Vorfallsort.
                        </li>
                        <li class="mb-2">
                            <strong>Auf das Sicherungsportal hinweisen:</strong> Verweisen Sie die Polizei auf dieses offizielle Sicherungsportal (<strong>safecase.ch</strong>). Die zuständige Behörde kann hier eine Editionsverfügung gemäss Art. 265 StPO einreichen, um eine fristgerechte Löschung der Aufnahmen zu verhindern.
                        </li>
                    </ol>

                    <div class="p-3 emergency-box-contrast text-center">
                        <div class="fs-4 fw-bold text-danger mb-1">
                            <i class="bi bi-telephone-fill me-2"></i>Polizeinotruf Schweiz: 117
                        </div>
                        <div class="small fw-bold text-danger">
                            Für akute Notfälle, Gefahrenlagen &amp; Sofortmeldungen (Europäischer Notruf: 112)
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light d-flex justify-content-between">
                    <span class="small text-muted"><i class="bi bi-shield-check text-success me-1"></i>Rechtskonform nach Schweizer DSG &amp; ZGB</span>
                    <button type="button" class="btn btn-dark px-4 fw-bold" data-bs-dismiss="modal">Schliessen</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 2: Polizei / StA Info -->
    <div class="modal fade modal-contrast" id="modalPolizeiInfo" tabindex="-1" aria-labelledby="modalPolizeiInfoLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content shadow-lg">
                <div class="modal-header header-police">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-white text-primary p-2 rounded shadow-sm">
                            <i class="bi bi-shield-fill-check fs-4"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold text-white mb-0" id="modalPolizeiInfoLabel">
                                Behörden-Workflow: Digitale Editionsverfügung
                            </h5>
                            <small class="text-white-50 fw-semibold">Art. 265 ff. Schweizerische Strafprozessordnung (StPO)</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schliessen"></button>
                </div>

                <div class="modal-body p-4">
                    <p class="text-dark small mb-4">
                        Dieses Portal dient als sichere, verschlüsselte Schnittstelle für Untersuchungs- und Ermittlungsbehörden der Schweizer Polizeibehörden (Kantonspolizei, Stadtpolizei, fedpol) sowie Staatsanwaltschaften und Gerichte.
                    </p>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="p-3 step-card-contrast h-100">
                                <div class="badge bg-primary text-white mb-2 fw-bold">Schritt 1</div>
                                <h6 class="fw-bold text-dark mb-1">Dienststelle &amp; Aktenzeichen</h6>
                                <p class="text-muted small mb-0">Erfassung von Behörde, Aktenzeichen, Sachbearbeiter und eventuellen Fristen.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 step-card-contrast h-100">
                                <div class="badge bg-primary text-white mb-2 fw-bold">Schritt 2</div>
                                <h6 class="fw-bold text-dark mb-1">Upload Editionsverfügung</h6>
                                <p class="text-muted small mb-0">Rechtsgültige Editionsverfügung (Art. 265 StPO) als PDF hochladen (bis 30 MB).</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 step-card-contrast h-100">
                                <div class="badge bg-primary text-white mb-2 fw-bold">Schritt 3</div>
                                <h6 class="fw-bold text-dark mb-1">Typ &amp; Spezifikation</h6>
                                <p class="text-muted small mb-0">Videoüberwachung (CCTV), Mail-Server, Cloud-Dateien oder Schliessprotokolle.</p>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info-contrast p-3 small mb-0">
                        <strong class="d-block mb-1 text-dark fs-6"><i class="bi bi-shield-lock-fill me-1 text-primary"></i>Sicherheits- &amp; Integritätsgarantie:</strong>
                        Nach Absenden des Antrags erhalten Sie sofort eine offizielle <strong>Vorgangs-ID (z. B. POL-2026-000123)</strong> sowie einen geheimen <strong>12-stelligen Fall-Zugangscode</strong> zur lückenlosen Statusverfolgung und Bereitstellungseinsicht.
                    </div>
                </div>

                <div class="modal-footer bg-light d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary fw-semibold" data-bs-dismiss="modal">Schliessen</button>
                    <a href="?route=sicherung/antrag" class="btn btn-primary px-4 fw-bold shadow">
                        <i class="bi bi-file-earmark-plus-fill me-1"></i> Direkt zum Sicherungsantrag &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 3: Fallzugang Formular -->
    <div class="modal fade modal-contrast" id="modalFallzugang" tabindex="-1" aria-labelledby="modalFallzugangLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg">
                <div class="modal-header header-access">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-white text-success p-2 rounded shadow-sm">
                            <i class="bi bi-folder-check fs-4"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold text-white mb-0" id="modalFallzugangLabel">
                                Fallzugang aufrufen
                            </h5>
                            <small class="text-white-50 fw-semibold">Einsicht für antragstellende Behörden</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schliessen"></button>
                </div>

                <form method="POST" action="?route=sicherung/fallzugang">
                    <div class="modal-body p-4">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                        <p class="text-dark small mb-3">
                            Geben Sie Ihre bei Antragstellung erhaltene <strong>Vorgangs-ID</strong> und den <strong>12-stelligen Zugangscode</strong> ein:
                        </p>

                        <div class="mb-3">
                            <label for="modal_case_number" class="form-label small fw-bold text-dark mb-1">
                                Vorgangs-ID (z. B. POL-2026-123456) <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control form-control-lg font-monospace text-uppercase" 
                                   id="modal_case_number" name="case_number" 
                                   placeholder="POL-2026-XXXXXX" required autocomplete="off">
                        </div>

                        <div class="mb-3">
                            <label for="modal_access_code" class="form-label small fw-bold text-dark mb-1">
                                12-stelliger Zugangscode <span class="text-danger">*</span>
                            </label>
                            <input type="password" class="form-control form-control-lg font-monospace text-uppercase" 
                                   id="modal_access_code" name="access_code" 
                                   placeholder="z. B. K7P2-9F4X-M8W3" required autocomplete="off">
                        </div>

                        <div class="p-3 bg-light rounded border border-2 text-dark small mt-3">
                            <i class="bi bi-shield-lock-fill text-success me-1"></i>
                            <strong>Sicherheitshinweis:</strong> Isolierter Nur-Lese-Zugriff auf diesen Vorgang gemäss Schweizer Datenschutz (DSG).
                        </div>
                    </div>

                    <div class="modal-footer bg-light d-flex justify-content-between">
                        <a href="?route=sicherung/fallzugang" class="btn btn-link btn-sm text-decoration-none text-muted p-0">
                            Auf separater Seite öffnen
                        </a>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary fw-semibold" data-bs-dismiss="modal">Abbrechen</button>
                            <button type="submit" class="btn btn-success fw-bold px-4 shadow">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Fall aufrufen
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container text-center">
            <div class="d-flex flex-wrap justify-content-center gap-3 mb-2 small">
                <a href="?route=sicherung">Startseite Sicherung</a>
                <span class="text-secondary">&middot;</span>
                <a href="?route=sicherung/antrag">Sicherungsantrag einreichen</a>
                <span class="text-secondary">&middot;</span>
                <a href="?route=sicherung/fallzugang">Fallzugang</a>
            </div>
            <p class="mb-0 text-secondary small">
                &copy; <?= date('Y') ?> <?= htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') ?> | design &amp; development by <a href="https://studiocreativo.ch" target="_blank" rel="noopener noreferrer">StudioCreativo</a>
            </p>
        </div>
    </footer>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
