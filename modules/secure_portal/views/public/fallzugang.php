<?php

declare(strict_types=1);

/**
 * Sicherungsportal: Fallzugang-Login (/sicherung/fallzugang)
 *
 * Eingabemaske für Antragsteller (Polizei/StA) mit Vorgangs-ID und Zugangscode.
 *
 * @var string|null $error
 * @var string $caseNumberInput
 */

$error = $error ?? ($_SESSION['flash_secure_error'] ?? null);
unset($_SESSION['flash_secure_error']);

$caseNumberInput = $caseNumberInput ?? (string) ($_GET['id'] ?? '');

$settings = class_exists('Settings') ? Settings::all() : [];
$siteTitle = 'Polizei- & Justiz-Sicherungsportal';
$adminBrandColor = (string) ($settings['admin_brand_color'] ?? '#0f4c81');
$primaryColor = !empty($settings['homepage_primary_color']) ? (string) $settings['homepage_primary_color'] : $adminBrandColor;
$logoPath = (string) ($settings['homepage_logo_path'] ?? '');

$csrfToken = class_exists('Csrf') ? Csrf::getToken() : '';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($siteTitle, ENT_QUOTES, 'UTF-8') ?> - Fallzugang</title>
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

        .login-card {
            background-color: #ffffff;
            border-radius: 0.75rem;
            border: 1px solid var(--sec-border);
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.08), 0 8px 10px -6px rgba(0,0,0,0.04);
            border-top: 5px solid #059669;
            padding: 2.5rem;
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
                        <small class="text-white-50">Fallzugang für Antragsteller</small>
                    </div>
                </a>

                <div>
                    <a href="?route=sicherung" class="btn btn-outline-light btn-sm px-3">
                        <i class="bi bi-house-door-fill me-1"></i> Startseite
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="container my-auto py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 col-xl-5">

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger shadow-sm mb-4" role="alert">
                        <i class="bi bi-exclamation-octagon-fill me-2 fs-5"></i>
                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <div class="login-card">
                    <div class="text-center mb-4">
                        <div class="bg-success-subtle text-success p-3 rounded-circle d-inline-flex mb-3">
                            <i class="bi bi-folder-check fs-2"></i>
                        </div>
                        <h2 class="h4 fw-bold text-dark mb-1">Fallzugang aufrufen</h2>
                        <p class="text-muted small mb-0">
                            Einsicht in Bearbeitungsstatus, Rückfragen und Bereitstellung digitaler Beweismittel.
                        </p>
                    </div>

                    <form method="POST" action="?route=sicherung/fallzugang">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                        <div class="mb-3">
                            <label for="case_number" class="form-label fw-semibold text-dark">
                                Vorgangs-ID <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control form-control-lg font-monospace text-uppercase" 
                                   id="case_number" name="case_number" 
                                   placeholder="POL-2026-XXXXXX"
                                   value="<?= htmlspecialchars($caseNumberInput, ENT_QUOTES, 'UTF-8') ?>"
                                   required autofocus autocomplete="off">
                            <div class="form-text">Ihre bei Antragstellung erhaltene Vorgangs-ID.</div>
                        </div>

                        <div class="mb-4">
                            <label for="access_code" class="form-label fw-semibold text-dark">
                                12-stelliger Zugangscode <span class="text-danger">*</span>
                            </label>
                            <input type="password" class="form-control form-control-lg font-monospace text-uppercase" 
                                   id="access_code" name="access_code" 
                                   placeholder="z. B. K7P2-9F4X-M8W3"
                                   required autocomplete="off">
                            <div class="form-text">Geheimer Fallcode aus der Antragsbestätigung.</div>
                        </div>

                        <button type="submit" class="btn btn-success btn-lg w-100 fw-bold d-flex align-items-center justify-content-center gap-2 mb-3">
                            <i class="bi bi-box-arrow-in-right"></i> Vorgang öffnen
                        </button>
                    </form>

                    <div class="p-3 bg-light rounded border text-muted small mt-4">
                        <i class="bi bi-shield-lock text-primary me-1"></i>
                        <strong>Sicherheitshinweis:</strong> Dieser Zugang gewährt ausschließlich Lesezugriff auf diesen einen Fall.
                        Es werden keine administrativen oder systemweiten Rechte vergeben.
                    </div>

                    <div class="text-center mt-4">
                        <a href="?route=sicherung" class="text-decoration-none text-muted small">
                            <i class="bi bi-arrow-left me-1"></i> Zurück zur Portal-Startseite
                        </a>
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
