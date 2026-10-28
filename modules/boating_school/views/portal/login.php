<?php

declare(strict_types=1);

/**
 * Nautic Modul - Eigene Login- & Registrierungsseite mit SVG-Logo
 *
 * Aufgeräumter 2-Stufen Magic-Code Zugang:
 * - Stufe 1: Nur E-Mail-Adresse eingeben (keine störenden Buttons)
 * - Stufe 2: Magic-Code in kleinen Einzelboxen (eine Box pro Zeichen/Ziffer)
 *
 * Im Admin-Bereich anpassbar: Testmodus ein / aus (nautic_login_testmode).
 * Copyright-Hinweis fest im Hauptfenster unten links platziert.
 *
 * @var string|null $error
 * @var string|null $success
 * @var string|null $info
 * @var string|null $sessionEmail
 */

$pageTitle = 'Nautic Login – Magic-Code Zugang';
$error = $error ?? ($_SESSION['flash_nautic_error'] ?? ($_SESSION['flash_error'] ?? null));
$success = $success ?? ($_SESSION['flash_nautic_success'] ?? ($_SESSION['flash_success'] ?? null));
$info = $info ?? ($_SESSION['flash_nautic_info'] ?? null);

unset($_SESSION['flash_nautic_error'], $_SESSION['flash_nautic_success'], $_SESSION['flash_nautic_info']);

$viewMode = trim((string) ($_GET['action'] ?? ($_POST['action'] ?? 'login')));
if ($viewMode !== 'register') {
    $viewMode = 'login';
}

$csrfToken = class_exists('Csrf') ? Csrf::getToken() : '';
$isLoggedIn = class_exists('Auth') && (Auth::check() || Auth::checkMagic());
$currentUser = $isLoggedIn ? Auth::user() : null;

// E-Mail aus Session oder Cookie
$sessionEmail = trim((string) ($sessionEmail ?? ($_SESSION['nautic_login_email'] ?? '')));
$rememberedEmail = (string) ($_COOKIE['nautic_remember_email'] ?? ($sessionEmail !== '' ? $sessionEmail : ($_POST['email'] ?? '')));

// Stufe 2 (Code-Eingabe) aktiv, wenn E-Mail in der Session ist oder explizit step=code
$isCodeStep = ($viewMode === 'login') && ($sessionEmail !== '' || (isset($_GET['step']) && $_GET['step'] === 'code'));

// Admin-Einstellung: Testmodus / Schnell-Login ein oder aus (Default: aus = 0)
$isTestMode = class_exists('Settings') ? (Settings::get('nautic_login_testmode', '0') === '1') : false;
?>
<!DOCTYPE html>
<html lang="de" class="h-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        :root {
            --nautic-deep-ocean: #06192a;
            --nautic-navy: #0c2d48;
            --nautic-blue: #0284c7;
            --nautic-cyan: #38bdf8;
            --nautic-gold: #f59e0b;
            --nautic-card-bg: rgba(255, 255, 255, 0.98);
        }

        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: radial-gradient(circle at 50% 12%, #0d3859 0%, var(--nautic-navy) 45%, var(--nautic-deep-ocean) 100%);
            color: #1e293b;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                radial-gradient(rgba(56, 189, 248, 0.08) 1px, transparent 1px),
                linear-gradient(to bottom, transparent 65%, rgba(6, 25, 42, 0.95) 100%);
            background-size: 28px 28px, 100% 100%;
            pointer-events: none;
            z-index: 0;
        }

        .nautic-auth-container {
            position: relative;
            z-index: 1;
            max-width: 480px;
            width: 100%;
            margin: auto;
            padding: 2.5rem 1rem;
        }

        .nautic-brand-card {
            background: var(--nautic-card-bg);
            border: 1px solid rgba(255, 255, 255, 0.35);
            border-radius: 1.25rem;
            box-shadow: 0 20px 45px rgba(2, 22, 38, 0.5), 0 0 0 1px rgba(56, 189, 248, 0.15);
            backdrop-filter: blur(16px);
            overflow: hidden;
        }

        .nautic-logo-wrap {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.25rem 0;
            transition: transform 0.2s ease;
        }

        .nautic-logo-wrap:hover {
            transform: scale(1.02);
        }

        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label {
            color: #0284c7;
        }

        .form-control:focus, .form-select:focus {
            border-color: #0284c7;
            box-shadow: 0 0 0 0.25rem rgba(2, 132, 199, 0.15);
        }

        .btn-nautic-primary {
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            border: none;
            color: #ffffff;
            font-weight: 600;
            padding: 0.72rem 1.25rem;
            border-radius: 0.65rem;
            box-shadow: 0 4px 14px rgba(2, 132, 199, 0.35);
            transition: all 0.2s ease;
        }

        .btn-nautic-primary:hover {
            background: linear-gradient(135deg, #0369a1 0%, #075985 100%);
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(2, 132, 199, 0.45);
        }

        .nautic-footer-links a {
            color: #94a3b8;
            font-size: 0.84rem;
            text-decoration: none;
            transition: color 0.15s ease;
        }

        .nautic-footer-links a:hover {
            color: #38bdf8;
        }

        /* Kleine Boxen für den Magic Code (1 Box pro Zeichen) */
        .code-digits-wrapper {
            display: flex;
            gap: 6px;
            justify-content: space-between;
            margin: 1.25rem 0 1rem 0;
        }

        .code-digit {
            width: 36px;
            height: 48px;
            text-align: center;
            font-size: 1.25rem;
            font-weight: 700;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
            text-transform: uppercase;
            border: 1.5px solid #cbd5e1;
            border-radius: 0.5rem;
            padding: 0;
            background: #ffffff;
            color: #0c4a6e;
            transition: all 0.15s ease;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.04);
        }

        .code-digit:focus {
            border-color: #0284c7;
            outline: none;
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.22);
            background: #f0f9ff;
        }

        @media (max-width: 480px) {
            .code-digits-wrapper {
                gap: 4px;
            }
            .code-digit {
                width: 29px;
                height: 42px;
                font-size: 1.05rem;
            }
        }

        /* E-Mail Info Leiste in Stufe 2 */
        .email-info-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 0.65rem;
            padding: 0.55rem 0.85rem;
            margin-bottom: 1.25rem;
        }

        /* Testmodus Schnell-Login Pills */
        .quick-dev-pill {
            font-size: 0.75rem;
            padding: 0.3rem 0.65rem;
            background: rgba(2, 132, 199, 0.08);
            border: 1px solid rgba(2, 132, 199, 0.2);
            color: #0369a1;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.15s;
        }

        .quick-dev-pill:hover {
            background: rgba(2, 132, 199, 0.18);
            color: #075985;
        }

        /* Copyright-Position fest unten links im Hauptfenster */
        .nautic-copyright-fixed {
            position: fixed;
            bottom: 1.25rem;
            left: 1.5rem;
            z-index: 30;
            font-size: 0.78rem;
            color: rgba(255, 255, 255, 0.55);
            letter-spacing: 0.25px;
            pointer-events: auto;
        }

        @media (max-width: 768px) {
            .nautic-copyright-fixed {
                position: static;
                text-align: center;
                margin-top: 1.5rem;
                padding-bottom: 1rem;
            }
        }
    </style>
</head>
<body>

    <div class="nautic-auth-container">
        
        <!-- Bereits eingeloggt-Hinweis (falls Session bereits aktiv) -->
        <?php if ($isLoggedIn && $currentUser): ?>
            <div class="alert bg-white bg-opacity-95 border-0 shadow-lg rounded-4 p-3 mb-3 text-dark">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-2 bg-success bg-opacity-10 text-success rounded-circle">
                        <i class="bi bi-person-check fs-4"></i>
                    </div>
                    <div class="flex-grow-1 small">
                        <div class="fw-bold">Bereits angemeldet</div>
                        <div class="text-muted"><?= htmlspecialchars((string) ($currentUser['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <a href="?route=admin/boating" class="btn btn-sm btn-primary rounded-pill px-3">
                        Zum Modul &rarr;
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Haupt-Card mit SVG Logo & schlanker Login- bzw. Registrierungsmaske -->
        <div class="nautic-brand-card p-4 p-sm-4">
            
            <!-- Nautic SVG Logo Einbindung (KEIN Zusatztext darunter!) -->
            <div class="text-center mb-4">
                <a href="?route=bootschule" class="nautic-logo-wrap text-decoration-none" title="Nautic Startseite">
                    <img src="public/assets/nautic-logo.svg" 
                         alt="Nautic" 
                         width="260" 
                         height="60" 
                         style="max-width: 100%; height: auto;"
                         onerror="this.style.display='none'; document.getElementById('svgFallback').style.display='block';">
                         
                    <!-- Fallback-Inline SVG falls Pfad nicht geladen werden kann -->
                    <div id="svgFallback" style="display: none;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 280 64" width="260" height="60">
                            <circle cx="28" cy="28" r="26" fill="#0284c7" />
                            <path d="M 28 14 L 40 37 C 36 40, 20 40, 16 37 Z" fill="#ffffff" />
                            <text x="70" y="36" font-family="sans-serif" font-size="28" font-weight="900" fill="#0c4a6e">NAUTIC</text>
                            <text x="72" y="54" font-family="sans-serif" font-size="9" font-weight="700" fill="#0284c7">FAHRSCHULE &amp; VERMIETUNG</text>
                        </svg>
                    </div>
                </a>
            </div>

            <!-- Flash-Meldungen -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 py-2 px-3 small d-flex align-items-center mb-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-6"></i>
                    <div><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></div>
                    <button type="button" class="btn-close ms-auto p-2" data-bs-dismiss="alert" aria-label="Schließen"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show border-0 rounded-3 py-2 px-3 small d-flex align-items-center mb-3" role="alert">
                    <i class="bi bi-check-circle-fill me-2 fs-6"></i>
                    <div><?= htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8') ?></div>
                    <button type="button" class="btn-close ms-auto p-2" data-bs-dismiss="alert" aria-label="Schließen"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($info)): ?>
                <div class="alert alert-info alert-dismissible fade show border-0 rounded-3 py-2 px-3 small d-flex align-items-center mb-3" role="alert">
                    <i class="bi bi-info-circle-fill me-2 fs-6"></i>
                    <div><?= htmlspecialchars((string) $info, ENT_QUOTES, 'UTF-8') ?></div>
                    <button type="button" class="btn-close ms-auto p-2" data-bs-dismiss="alert" aria-label="Schließen"></button>
                </div>
            <?php endif; ?>

            <!-- ============================================================== -->
            <!-- ANSICHT 1: LOGIN (2 STUFEN)                                    -->
            <!-- ============================================================== -->
            <?php if ($viewMode === 'login'): ?>

                <?php if (!$isCodeStep): ?>
                    <!-- ====================================================== -->
                    <!-- STUFE 1 (STANDARD): NUR E-MAIL-ADRESSE EINGEBEN        -->
                    <!-- ====================================================== -->
                    <form method="POST" action="?route=nautic/login" id="nauticEmailForm">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="action" value="login">

                        <div class="form-floating mb-3">
                            <input type="email" 
                                   class="form-control rounded-3" 
                                   id="nauticEmail" 
                                   name="email" 
                                   placeholder="name@nautic.ch" 
                                   required 
                                   value="<?= htmlspecialchars($rememberedEmail, ENT_QUOTES, 'UTF-8') ?>"
                                   autocomplete="username"
                                   autofocus>
                            <label for="nauticEmail">
                                <i class="bi bi-envelope me-1 text-muted"></i>
                                E-Mail-Adresse
                            </label>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-3 pt-1">
                            <div class="form-check small">
                                <input class="form-check-input" type="checkbox" name="remember" value="1" id="rememberCheck" <?= !empty($rememberedEmail) ? 'checked' : '' ?>>
                                <label class="form-check-label text-muted" for="rememberCheck">
                                    E-Mail merken
                                </label>
                            </div>
                            <span class="text-muted small" style="font-size: 0.78rem;">
                                <i class="bi bi-shield-check text-success me-1"></i>Passwortlos &amp; sicher
                            </span>
                        </div>

                        <button type="submit" class="btn btn-nautic-primary w-100 d-flex align-items-center justify-content-center gap-2 mb-3">
                            <i class="bi bi-magic fs-5"></i>
                            <span>Magic-Code anfordern &rarr;</span>
                        </button>

                        <!-- Schnell-Login für Test/Demo im Prototyp (NUR WENN IM ADMIN AKTIVIERT) -->
                        <?php if ($isTestMode): ?>
                            <div class="p-2.5 bg-light rounded-3 border small text-muted text-center mt-3">
                                <div class="fw-semibold text-dark mb-1.5" style="font-size: 0.76rem;">
                                    <i class="bi bi-lightning-charge-fill text-warning me-1"></i>Testmodus (Schnell-Auswahl):
                                </div>
                                <div class="d-flex justify-content-center gap-1.5 flex-wrap">
                                    <button type="button" class="quick-dev-pill" onclick="fillQuickEmail('beat@bootschule-see.ch')">
                                        Beat (Coach)
                                    </button>
                                    <button type="button" class="quick-dev-pill" onclick="fillQuickEmail('office@studiocreativo.ch')">
                                        Flottenleitung (Admin)
                                    </button>
                                    <button type="button" class="quick-dev-pill" onclick="fillQuickEmail('lukas.berner@swiss-pilot.ch')">
                                        Lukas (Schüler)
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </form>

                <?php else: ?>
                    <!-- ====================================================== -->
                    <!-- STUFE 2: MAGIC-CODE IN KLEINEN BOXEN EINGEBEN          -->
                    <!-- ====================================================== -->
                    <div class="email-info-bar">
                        <div class="small">
                            <div class="text-muted" style="font-size: 0.72rem;">E-Mail-Adresse:</div>
                            <strong class="text-dark font-monospace"><?= htmlspecialchars($sessionEmail, ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                        <form method="POST" action="?route=nautic/login" class="m-0">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="action" value="clear_email">
                            <button type="submit" class="btn btn-sm btn-outline-secondary py-1 px-2.5 rounded-pill" style="font-size: 0.76rem;">
                                <i class="bi bi-pencil-square me-1"></i>E-Mail ändern
                            </button>
                        </form>
                    </div>

                    <div class="text-center mb-2">
                        <h6 class="fw-bold text-dark mb-1">Magic-Code eingeben</h6>
                        <p class="text-muted small mb-0">Gib deinen 10-stelligen Code aus der E-Mail ein:</p>
                    </div>

                    <form method="POST" action="?route=nautic/login" id="nauticCodeForm">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="action" value="login">
                        <input type="hidden" name="email" value="<?= htmlspecialchars($sessionEmail, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="code" id="nauticCodeHidden">

                        <!-- 10 kleine Boxen – pro Code-Zeichen genau eine Box -->
                        <div class="code-digits-wrapper" id="codeDigitContainer">
                            <?php for ($i = 0; $i < 10; $i++): ?>
                                <input type="text"
                                       class="code-digit"
                                       name="digit_<?= $i ?>"
                                       data-index="<?= $i ?>"
                                       maxlength="1"
                                       autocomplete="off"
                                       autocapitalize="characters"
                                       spellcheck="false"
                                       <?= $i === 0 ? 'autofocus' : '' ?>>
                            <?php endfor; ?>
                        </div>

                        <button type="submit" class="btn btn-nautic-primary w-100 d-flex align-items-center justify-content-center gap-2 mb-3">
                            <i class="bi bi-check2-circle fs-5"></i>
                            <span>Code bestätigen &amp; Anmelden</span>
                        </button>

                        <div class="text-center pt-1 border-top">
                            <span class="text-muted small" style="font-size: 0.8rem;">Keinen Code erhalten?</span>
                            <form method="POST" action="?route=nautic/login" class="d-inline ms-1">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="action" value="login">
                                <input type="hidden" name="email" value="<?= htmlspecialchars($sessionEmail, ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="btn btn-link btn-sm p-0 text-decoration-none fw-semibold" style="font-size: 0.8rem;">
                                    Neuen Code anfordern
                                </button>
                            </form>
                        </div>

                        <!-- Schnellauswahl im Testmodus -->
                        <?php if ($isTestMode): ?>
                            <div class="p-2.5 bg-light rounded-3 border small text-muted text-center mt-3">
                                <div class="fw-semibold text-dark mb-1" style="font-size: 0.74rem;">
                                    <i class="bi bi-lightning-charge-fill text-warning me-1"></i>Testmodus:
                                </div>
                                <button type="button" class="quick-dev-pill" onclick="fillTestCode('ROLAND1234')">
                                    Dev-Code (ROLAND1234) einfüllen
                                </button>
                            </div>
                        <?php endif; ?>
                    </form>
                <?php endif; ?>

                <!-- Umschaltung zu Account anfordern -->
                <div class="text-center pt-3 mt-3 border-top">
                    <span class="text-muted small">Noch kein Nautic-Konto?</span>
                    <a href="?route=nautic/login&action=register" class="fw-bold text-primary small text-decoration-none ms-1">
                        Jetzt Account anfordern &rarr;
                    </a>
                </div>

            <!-- ============================================================== -->
            <!-- ANSICHT 2: NEUEN ACCOUNT ANFORDERN / REGISTRIERUNG             -->
            <!-- ============================================================== -->
            <?php else: ?>
                <div class="mb-3">
                    <h6 class="fw-bold mb-1 text-dark">Nautic Account anfordern</h6>
                    <p class="text-muted small mb-0">
                        Erstelle deine Anfrage. Die Flottenleitung prüft deinen Account und weist dein persönliches Profil zu.
                    </p>
                </div>

                <form method="POST" action="?route=nautic/request-account" id="nauticRegisterForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="action" value="register">

                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <div class="form-floating">
                                <input type="text" class="form-control rounded-3" id="regFirstName" name="first_name" placeholder="Vorname" required>
                                <label for="regFirstName">Vorname *</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-floating">
                                <input type="text" class="form-control rounded-3" id="regLastName" name="last_name" placeholder="Nachname" required>
                                <label for="regLastName">Nachname *</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-floating mb-2">
                        <input type="email" class="form-control rounded-3" id="regEmail" name="email" placeholder="name@domain.ch" required>
                        <label for="regEmail"><i class="bi bi-envelope me-1 text-muted"></i>E-Mail-Adresse *</label>
                    </div>

                    <div class="form-floating mb-2">
                        <input type="tel" class="form-control rounded-3" id="regPhone" name="phone" placeholder="+41 79 ...">
                        <label for="regPhone"><i class="bi bi-phone me-1 text-muted"></i>Telefon / Mobil</label>
                    </div>

                    <div class="form-floating mb-2">
                        <select class="form-select rounded-3" id="regDesiredRole" name="desired_role" required>
                            <option value="student" selected>Fahrschüler (Praxisausbildung Kat. A / D)</option>
                            <option value="instructor">Fahrcoach / Skipper (Fahrlehrer)</option>
                            <option value="renter">Bootsmieter / Charterkunde</option>
                            <option value="admin">Flottenleitung &amp; Disposition</option>
                        </select>
                        <label for="regDesiredRole">Gewünschtes Profil / Zweck *</label>
                    </div>

                    <div class="form-floating mb-3">
                        <textarea class="form-control rounded-3" id="regNotes" name="notes" placeholder="Bemerkung" style="height: 70px;"></textarea>
                        <label for="regNotes">Bemerkung / Lizenz / Vorkenntnisse (optional)</label>
                    </div>

                    <button type="submit" class="btn btn-nautic-primary w-100 d-flex align-items-center justify-content-center gap-2 mb-2">
                        <i class="bi bi-send-fill"></i>
                        <span>Account-Anfrage absenden</span>
                    </button>

                    <div class="text-center pt-2">
                        <a href="?route=nautic/login" class="small text-muted text-decoration-none">
                            <i class="bi bi-arrow-left me-1"></i> Zurück zum Magic-Link Login
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Copyright fest im Hauptfenster UNTEN LINKS -->
    <div class="nautic-copyright-fixed">
        &copy; <?= date('Y') ?> Coaching-Boot / Nautic. Alle Rechte vorbehalten.
    </div>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function fillQuickEmail(email) {
            const emailInput = document.getElementById('nauticEmail');
            if (emailInput) {
                emailInput.value = email;
                emailInput.focus();
            }
        }

        function fillTestCode(code) {
            const inputs = Array.from(document.querySelectorAll('.code-digit'));
            if (!inputs.length) return;
            const chars = code.toUpperCase().split('');
            inputs.forEach((inp, idx) => {
                inp.value = chars[idx] || '';
            });
            updateHiddenCode();
            const submitBtn = document.querySelector('#nauticCodeForm button[type="submit"]');
            if (submitBtn) submitBtn.focus();
        }

        function updateHiddenCode() {
            const hidden = document.getElementById('nauticCodeHidden');
            const inputs = Array.from(document.querySelectorAll('.code-digit'));
            if (hidden && inputs.length) {
                hidden.value = inputs.map(i => i.value.trim().toUpperCase()).join('');
            }
        }

        // Steuerung der kleinen Magic-Code Boxen
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('nauticCodeForm');
            if (!form) return;

            const inputs = Array.from(form.querySelectorAll('.code-digit'));
            if (!inputs.length) return;

            inputs.forEach((input, index) => {
                // Automatisch zum nächsten Feld springen
                input.addEventListener('input', function() {
                    const clean = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
                    this.value = clean.slice(0, 1);

                    if (this.value.length === 1 && index < inputs.length - 1) {
                        inputs[index + 1].focus();
                        inputs[index + 1].select();
                    }
                    updateHiddenCode();
                });

                // Backspace & Pfeiltasten Navigation
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace') {
                        if (this.value === '' && index > 0) {
                            e.preventDefault();
                            inputs[index - 1].focus();
                            inputs[index - 1].value = '';
                            updateHiddenCode();
                        }
                    } else if (e.key === 'ArrowLeft' && index > 0) {
                        e.preventDefault();
                        inputs[index - 1].focus();
                    } else if (e.key === 'ArrowRight' && index < inputs.length - 1) {
                        e.preventDefault();
                        inputs[index + 1].focus();
                    }
                });

                // Vollständigen Code einfügen (Paste)
                input.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const pastedData = (e.clipboardData || window.clipboardData)
                        .getData('text')
                        .toUpperCase()
                        .replace(/[^A-Z0-9]/g, '')
                        .slice(0, inputs.length);

                    if (!pastedData) return;

                    pastedData.split('').forEach((char, i) => {
                        if (inputs[i]) {
                            inputs[i].value = char;
                        }
                    });

                    updateHiddenCode();

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
