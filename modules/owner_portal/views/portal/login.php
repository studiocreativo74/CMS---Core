<?php

declare(strict_types=1);

/**
 * Eigentümerportal: Portal-Startseite mit Login
 *
 * Öffentliche Startseite für Eigentümer, Mieter und Verwaltungsbeiräte.
 * Wenn der Benutzer bereits angemeldet ist, wird er direkt zum Portal-Dashboard weitergeleitet.
 *
 * @var array<string, mixed> $portalSettings
 * @var string|null $flashError
 * @var string|null $flashSuccess
 * @var string|null $flashInfo
 */

$flashError = $flashError ?? ($_SESSION['flash_portal_error'] ?? ($_SESSION['flash_home_error'] ?? null));
$flashSuccess = $flashSuccess ?? ($_SESSION['flash_portal_success'] ?? ($_SESSION['flash_home_success'] ?? null));
$flashInfo = $flashInfo ?? ($_SESSION['flash_portal_info'] ?? null);
unset($_SESSION['flash_portal_error'], $_SESSION['flash_portal_success'], $_SESSION['flash_portal_info'], $_SESSION['flash_home_error'], $_SESSION['flash_home_success']);

$sessionEmail = $_SESSION['magic_email'] ?? ($_SESSION['magic_input_email'] ?? null);

$settings = class_exists('Settings') ? Settings::all() : [];
$portalTitle = !empty($settings['portal_title']) ? (string) $settings['portal_title'] : 'Eigentümerportal & Hausverwaltung';
$portalSubtitle = !empty($settings['portal_subtitle']) ? (string) $settings['portal_subtitle'] : 'Geschützter Online-Bereich für Eigentümer, Mieter und Beiräte';
$adminBrandColor = (string) ($settings['admin_brand_color'] ?? '#0d6efd');
$homepagePrimaryColor = (string) (!empty($settings['homepage_primary_color']) ? $settings['homepage_primary_color'] : $adminBrandColor);
$homepageLogoPath = (string) ($settings['homepage_logo_path'] ?? '');

$csrfToken = class_exists('Csrf') ? Csrf::getToken() : '';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($portalTitle, ENT_QUOTES, 'UTF-8') ?> - Anmeldung</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --portal-primary: <?= htmlspecialchars($homepagePrimaryColor, ENT_QUOTES, 'UTF-8') ?>;
            --portal-primary-hover: #0b5ed7;
            --portal-bg: #f8fafc;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--portal-bg);
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .portal-header {
            background-color: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 1rem 1.5rem;
        }

        .portal-main {
            flex: 1 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 1rem;
        }

        .portal-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
            overflow: hidden;
            width: 100%;
            max-width: 520px;
        }

        .portal-card-header {
            background: linear-gradient(135deg, var(--portal-primary) 0%, #0a58ca 100%);
            color: #ffffff;
            padding: 2rem 2rem 1.75rem 2rem;
            text-align: center;
        }

        .portal-card-body {
            padding: 2rem;
        }

        .nav-tabs .nav-link {
            font-weight: 600;
            color: #64748b;
            border: none;
            border-bottom: 2px solid transparent;
            padding: 0.75rem 1.25rem;
        }

        .nav-tabs .nav-link.active {
            color: var(--portal-primary);
            border-bottom: 2px solid var(--portal-primary);
            background: transparent;
        }

        .btn-portal {
            background-color: var(--portal-primary);
            border-color: var(--portal-primary);
            color: #ffffff;
            font-weight: 600;
            padding: 0.65rem 1.25rem;
        }

        .btn-portal:hover {
            background-color: var(--portal-primary-hover);
            border-color: var(--portal-primary-hover);
            color: #ffffff;
        }

        /* 10-Digit Magic Code Inputs */
        .code-digits-wrapper {
            display: flex;
            gap: 6px;
            justify-content: center;
            margin: 1.25rem 0;
        }

        .code-digit {
            width: 38px;
            height: 48px;
            font-size: 1.25rem;
            font-weight: 700;
            text-align: center;
            text-transform: uppercase;
            border: 2px solid #cbd5e1;
            border-radius: 6px;
            background: #ffffff;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .code-digit:focus {
            border-color: var(--portal-primary);
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.2);
        }

        @media (max-width: 480px) {
            .code-digits-wrapper {
                gap: 3px;
            }
            .code-digit {
                width: 30px;
                height: 40px;
                font-size: 1.05rem;
            }
            .portal-card-body {
                padding: 1.5rem 1rem;
            }
        }

        .portal-features-list {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px dashed #e2e8f0;
        }

        .portal-feature-item {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            font-size: 0.88rem;
            color: #64748b;
            margin-bottom: 0.5rem;
        }

        .portal-footer {
            flex-shrink: 0;
            text-align: center;
            padding: 1.5rem 1rem;
            font-size: 0.85rem;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            background: #ffffff;
        }
    </style>
</head>
<body>

    <!-- Header / Brand -->
    <header class="portal-header d-flex justify-content-between align-items-center">
        <a href="?route=portal" class="d-flex align-items-center gap-2 text-decoration-none text-dark fw-bold">
            <?php if (!empty($homepageLogoPath)): ?>
                <img src="<?= htmlspecialchars($homepageLogoPath, ENT_QUOTES, 'UTF-8') ?>" alt="Logo" style="max-height: 36px; object-fit: contain;">
            <?php else: ?>
                <span class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded p-1" style="width: 32px; height: 32px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-buildings-fill" viewBox="0 0 16 16">
                        <path d="M15 .5a.5.5 0 0 0-.724-.447l-8 4A.5.5 0 0 0 6 4.5v3.14L.342 9.526A.5.5 0 0 0 0 10v5.5a.5.5 0 0 0 .5.5h15a.5.5 0 0 0 .5-.5V.5ZM2 11h1v1H2zm2 0h1v1H4zm-2 2h1v1H2zm2 0h1v1H4zm4-4h1v1H8zm2 0h1v1h-1zm-2 2h1v1H8zm2 0h1v1h-1zm-2 2h1v1H8zm2 0h1v1h-1zM8 7h1v1H8zm2 0h1v1h-1zM8 5h1v1H8zm2 0h1v1h-1zm2 2h1v1h-1zm0 2h1v1h-1zm0 2h1v1h-1zm0 2h1v1h-1z"/>
                    </svg>
                </span>
            <?php endif; ?>
            <span><?= htmlspecialchars($portalTitle, ENT_QUOTES, 'UTF-8') ?></span>
        </a>

        <div>
            <a href="?route=admin" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center" title="Zum Admin-Backend">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-shield-lock me-1" viewBox="0 0 16 16">
                    <path d="M5.338 1.59a61 61 0 0 0-2.837.856.48.48 0 0 0-.328.39c-.554 4.157.726 7.19 2.253 9.188a10.7 10.7 0 0 0 2.287 2.233c.346.244.652.42.893.533q.18.085.293.118a1 1 0 0 0 .101.025 1 1 0 0 0 .1-.025q.114-.034.294-.118c.24-.113.547-.29.893-.533a10.7 10.7 0 0 0 2.287-2.233c1.527-1.997 2.807-5.031 2.253-9.188a.48.48 0 0 0-.328-.39c-.651-.213-1.75-.56-2.837-.855C9.552 1.29 8.531 1.067 8 1.067c-.53 0-1.552.223-2.662.524zM5.072.56C6.157.265 7.31 0 8 0s1.843.265 2.928.56c1.11.3 2.229.655 2.887.87a1.54 1.54 0 0 1 1.044 1.262c.596 4.477-.787 7.795-2.465 9.99a11.8 11.8 0 0 1-2.517 2.453 7 7 0 0 1-1.048.625c-.28.132-.581.24-.829.24s-.548-.108-.829-.24a7 7 0 0 1-1.048-.625 11.8 11.8 0 0 1-2.517-2.453C1.928 10.487.545 7.169 1.141 2.692A1.54 1.54 0 0 1 2.185 1.43 63 63 0 0 1 5.072.56"/>
                </svg>
                Admin-Bereich
            </a>
        </div>
    </header>

    <!-- Main Login / Portal Entry -->
    <main class="portal-main">
        <div class="portal-card">
            <div class="portal-card-header">
                <span class="badge bg-white text-primary px-3 py-1 rounded-pill fw-semibold mb-2">Portal-Zugang</span>
                <h4 class="fw-bold mb-1 text-white"><?= htmlspecialchars($portalTitle, ENT_QUOTES, 'UTF-8') ?></h4>
                <p class="text-white-50 small mb-0"><?= htmlspecialchars($portalSubtitle, ENT_QUOTES, 'UTF-8') ?></p>
            </div>

            <div class="portal-card-body">
                <!-- Flash Messages -->
                <?php if (!empty($flashError)): ?>
                    <div class="alert alert-danger alert-dismissible fade show small mb-3" role="alert">
                        <?= htmlspecialchars((string) $flashError, ENT_QUOTES, 'UTF-8') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($flashSuccess)): ?>
                    <div class="alert alert-success alert-dismissible fade show small mb-3" role="alert">
                        <?= htmlspecialchars((string) $flashSuccess, ENT_QUOTES, 'UTF-8') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($flashInfo)): ?>
                    <div class="alert alert-info alert-dismissible fade show small mb-3" role="alert">
                        <?= htmlspecialchars((string) $flashInfo, ENT_QUOTES, 'UTF-8') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>
                    </div>
                <?php endif; ?>

                <!-- Login Tabs: Magic Code (Standard) vs. Passwort -->
                <ul class="nav nav-tabs nav-fill mb-3" id="portalLoginTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= empty($_POST['password_login']) ? 'active' : '' ?>" id="magic-tab" data-bs-toggle="tab" data-bs-target="#tab-magic" type="button" role="tab" aria-selected="<?= empty($_POST['password_login']) ? 'true' : 'false' ?>">
                            Magic Code (Passwortlos)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= !empty($_POST['password_login']) ? 'active' : '' ?>" id="password-tab" data-bs-toggle="tab" data-bs-target="#tab-password" type="button" role="tab" aria-selected="<?= !empty($_POST['password_login']) ? 'true' : 'false' ?>">
                            Passwort-Login
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="portalLoginTabContent">
                    <!-- Tab 1: Magic Code -->
                    <div class="tab-pane fade <?= empty($_POST['password_login']) ? 'show active' : '' ?>" id="tab-magic" role="tabpanel" aria-labelledby="magic-tab">
                        <?php if (empty($sessionEmail)): ?>
                            <!-- Schritt 1: E-Mail für Magic Code anfordern -->
                            <form method="post" action="?route=portal/magic-set-email">
                                <div class="mb-3">
                                    <label for="portal_magic_email" class="form-label fw-medium">Ihre registrierte E-Mail-Adresse</label>
                                    <input type="email" class="form-control" id="portal_magic_email" name="email" maxlength="191" required placeholder="name@beispiel.ch" autofocus>
                                    <div class="form-text">Geben Sie die bei der Hausverwaltung hinterlegte E-Mail-Adresse an.</div>
                                </div>
                                <button type="submit" class="btn btn-portal w-100">Weiter &amp; Code anfordern</button>
                            </form>
                        <?php else: ?>
                            <!-- Schritt 2: 10-stelligen Code eingeben -->
                            <div class="bg-light p-2 rounded mb-3 d-flex justify-content-between align-items-center">
                                <div class="small text-truncate me-2">
                                    <span class="text-muted d-block">Angemeldete E-Mail:</span>
                                    <strong><?= htmlspecialchars($sessionEmail, ENT_QUOTES, 'UTF-8') ?></strong>
                                </div>
                                <form method="post" action="?route=portal/magic-clear-email" class="m-0">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Ändern</button>
                                </form>
                            </div>

                            <form method="post" action="?route=portal/magic-login" id="portal_magic_form">
                                <input type="hidden" name="magic_code" id="portal_magic_code_hidden">
                                
                                <label class="form-label fw-medium text-center d-block mb-1">10-stelligen Magic Code eingeben</label>
                                <div class="form-text text-center mb-2">Den Code haben Sie per E-Mail erhalten.</div>

                                <div class="code-digits-wrapper" id="portal_code_container">
                                    <?php for ($i = 0; $i < 10; $i++): ?>
                                        <input type="text"
                                               class="code-digit"
                                               data-index="<?= $i ?>"
                                               maxlength="1"
                                               autocomplete="off"
                                               autocapitalize="characters"
                                               spellcheck="false"
                                               <?= $i === 0 ? 'autofocus' : '' ?>>
                                    <?php endfor; ?>
                                </div>

                                <button type="submit" class="btn btn-portal w-100 mt-2">Im Portal anmelden</button>
                            </form>

                            <div class="text-center mt-3 pt-2 border-top">
                                <form method="post" action="?route=portal/magic-request" class="m-0">
                                    <button type="submit" class="btn btn-link btn-sm text-decoration-none text-muted">
                                        Keinen Code erhalten? Neuen Code zusenden
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Tab 2: Passwort Login -->
                    <div class="tab-pane fade <?= !empty($_POST['password_login']) ? 'show active' : '' ?>" id="tab-password" role="tabpanel" aria-labelledby="password-tab">
                        <form method="post" action="?route=portal/login">
                            <input type="hidden" name="password_login" value="1">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                            <div class="mb-3">
                                <label for="portal_login_email" class="form-label fw-medium">E-Mail-Adresse</label>
                                <input type="email" class="form-control" id="portal_login_email" name="email" required value="<?= htmlspecialchars((string) ($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            </div>

                            <div class="mb-3">
                                <label for="portal_login_password" class="form-label fw-medium">Passwort</label>
                                <input type="password" class="form-control" id="portal_login_password" name="password" required>
                            </div>

                            <button type="submit" class="btn btn-portal w-100">Mit Passwort anmelden</button>
                        </form>
                    </div>
                </div>

                <!-- Features Info -->
                <div class="portal-features-list">
                    <div class="portal-feature-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-circle-fill text-success" viewBox="0 0 16 16">
                            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                        </svg>
                        <span>Einsicht in Liegenschafts- und Wohnungsdaten</span>
                    </div>
                    <div class="portal-feature-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-circle-fill text-success" viewBox="0 0 16 16">
                            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                        </svg>
                        <span>Schadensmeldungen online einreichen &amp; verfolgen</span>
                    </div>
                    <div class="portal-feature-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-circle-fill text-success" viewBox="0 0 16 16">
                            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                        </svg>
                        <span>Dokumente, Abrechnungen &amp; Versammlungsprotokolle herunterladen</span>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="portal-footer">
        &copy; <?= date('Y') ?> <?= htmlspecialchars($portalTitle, ENT_QUOTES, 'UTF-8') ?> &bull; Alle Rechte vorbehalten.
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- 10-Digit Code Input Logik -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('portal_magic_form');
        if (!form) return;

        const hiddenInput = document.getElementById('portal_magic_code_hidden');
        const inputs = Array.from(form.querySelectorAll('.code-digit'));

        function updateHiddenValue() {
            if (hiddenInput) {
                hiddenInput.value = inputs.map(function(inp) {
                    return inp.value.trim().toUpperCase();
                }).join('');
            }
        }

        inputs.forEach(function(input, index) {
            input.addEventListener('input', function() {
                const clean = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
                this.value = clean.slice(0, 1);

                if (this.value.length === 1 && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                    inputs[index + 1].select();
                }
                updateHiddenValue();
            });

            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace') {
                    if (this.value === '' && index > 0) {
                        e.preventDefault();
                        inputs[index - 1].focus();
                        inputs[index - 1].value = '';
                        updateHiddenValue();
                    }
                } else if (e.key === 'ArrowLeft' && index > 0) {
                    e.preventDefault();
                    inputs[index - 1].focus();
                } else if (e.key === 'ArrowRight' && index < inputs.length - 1) {
                    e.preventDefault();
                    inputs[index + 1].focus();
                }
            });

            input.addEventListener('paste', function(e) {
                e.preventDefault();
                const pastedData = (e.clipboardData || window.clipboardData).getData('text')
                    .toUpperCase()
                    .replace(/[^A-Z0-9]/g, '')
                    .slice(0, 10);

                if (!pastedData) return;

                pastedData.split('').forEach(function(char, i) {
                    if (inputs[i]) {
                        inputs[i].value = char;
                    }
                });

                updateHiddenValue();

                if (pastedData.length < inputs.length) {
                    inputs[pastedData.length].focus();
                } else {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) submitBtn.focus();
                }
            });
        });
    });
    </script>
</body>
</html>
