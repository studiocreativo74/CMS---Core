<?php

declare(strict_types=1);

/**
 * Nautic Modul - Eigene Login- & Registrierungsseite mit SVG-Logo
 *
 * Einheitlicher Magic-Link-Zugang für alle Benutzer (Fahrlehrer, Admins, Schüler & Mieter)
 * OHNE Formular-Wechselbuttons.
 * Enthält ein Registrierungsformular zur Anforderung neuer Accounts,
 * welche die Administration anschließend prüft und einem Profil zuweist.
 *
 * @var string|null $error
 * @var string|null $success
 * @var string|null $info
 */

$pageTitle = 'Nautic Login – Magic-Link Zugang & Registrierung';
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
$rememberedEmail = (string) ($_COOKIE['nautic_remember_email'] ?? ($_POST['email'] ?? ''));
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
            --nautic-card-bg: rgba(255, 255, 255, 0.97);
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
            max-width: 470px;
            width: 100%;
            margin: auto;
            padding: 2rem 1rem;
        }

        .nautic-brand-card {
            background: var(--nautic-card-bg);
            border: 1px solid rgba(255, 255, 255, 0.25);
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
            background: rgba(2, 132, 199, 0.16);
            color: #075985;
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

        <!-- Haupt-Card mit SVG Logo & Login- bzw. Registrierungsmaske -->
        <div class="nautic-brand-card p-4 p-sm-4">
            
            <!-- Nautic SVG Logo Einbindung (KEIN Zusatztext darunter) -->
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
            <!-- ANSICHT 1: EINHEITLICHER MAGIC-LINK LOGIN FÜR ALLE             -->
            <!-- ============================================================== -->
            <?php if ($viewMode === 'login'): ?>
                <form method="POST" action="?route=nautic/login" id="nauticLoginForm">
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
                               autocomplete="username">
                        <label for="nauticEmail">
                            <i class="bi bi-envelope me-1 text-muted"></i>
                            E-Mail-Adresse
                        </label>
                    </div>

                    <!-- Optionaler Magic-Code Input (falls Benutzer bereits einen Code oder Token erhalten hat) -->
                    <div class="form-floating mb-2 position-relative">
                        <input type="text" 
                               class="form-control rounded-3 pe-5 font-monospace text-uppercase" 
                               id="nauticCode" 
                               name="code" 
                               placeholder="z. B. ROLAND1234" 
                               autocomplete="one-time-code"
                               maxlength="32">
                        <label for="nauticCode">
                            <i class="bi bi-key me-1 text-muted"></i>
                            Magic-Code (falls bereits erhalten)
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
                        <span>Magic-Link anfordern / Anmelden</span>
                    </button>

                    <!-- Schnell-Login für Test/Demo im Prototyp -->
                    <div class="p-2.5 bg-light rounded-3 border small text-muted text-center mt-3">
                        <div class="fw-semibold text-dark mb-1.5" style="font-size: 0.76rem;">
                            <i class="bi bi-lightning-charge-fill text-warning me-1"></i>Schnell-Login für Prototyp:
                        </div>
                        <div class="d-flex justify-content-center gap-1.5 flex-wrap">
                            <button type="button" class="quick-dev-pill" onclick="fillQuickLogin('beat@bootschule-see.ch', 'ROLAND1234')">
                                Beat Vollenweider (Coach)
                            </button>
                            <button type="button" class="quick-dev-pill" onclick="fillQuickLogin('office@studiocreativo.ch', 'ROLAND1234')">
                                Flottenleitung (Admin)
                            </button>
                            <button type="button" class="quick-dev-pill" onclick="fillQuickLogin('lukas.berner@swiss-pilot.ch', '')">
                                Lukas Berner (Schüler)
                            </button>
                        </div>
                    </div>
                </form>

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

            <!-- Trennlinie & Verlinkung zum Kundenportal (OHNE Fahrstunde buchen Link!) -->
            <hr class="my-3 opacity-10">

            <div class="d-flex justify-content-between align-items-center text-muted small">
                <a href="?route=bootschule" class="text-decoration-none text-muted d-inline-flex align-items-center gap-1">
                    <i class="bi bi-compass text-primary"></i>
                    <span>Zum Kundenportal</span>
                </a>
                <span class="text-muted" style="font-size: 0.76rem;">
                    Nautic &middot; Fahrschule &amp; Vermietung
                </span>
            </div>
        </div>

        <!-- Fußzeile außerhalb der Card -->
        <div class="text-center mt-3 nautic-footer-links">
            <div class="d-flex justify-content-center gap-3 mb-1">
                <a href="?route=bootschule/services">Angebote &amp; Tarife</a>
                <span class="text-white-50">&middot;</span>
                <a href="?route=admin/boating">Flotten-Admin</a>
                <span class="text-white-50">&middot;</span>
                <a href="?route=/">CMS Startseite</a>
            </div>
            <div class="text-white-50 small" style="font-size: 0.74rem;">
                &copy; <?= date('Y') ?> Nautic – Fahrschule, Vermietung &amp; Motorboot-Akademie
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function fillQuickLogin(email, code) {
            const emailInput = document.getElementById('nauticEmail');
            const codeInput = document.getElementById('nauticCode');
            if (emailInput) {
                emailInput.value = email;
            }
            if (codeInput) {
                codeInput.value = code;
                codeInput.focus();
            }
        }
    </script>
</body>
</html>
