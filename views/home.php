<?php
declare(strict_types=1);

$sessionEmail = $_SESSION['magic_email'] ?? ($_SESSION['magic_input_email'] ?? null);
$error = $error ?? ($_SESSION['flash_home_error'] ?? null);
$success = $success ?? ($_SESSION['flash_home_success'] ?? null);
$info = $info ?? ($_SESSION['flash_home_info'] ?? null);

unset($_SESSION['flash_home_error'], $_SESSION['flash_home_success'], $_SESSION['flash_home_info']);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Willkommen im CMS-Prototype</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.5;
            color: #222;
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
        }
        .magic-wrapper {
            max-width: 520px;
            margin: 3.5rem auto;
            padding: 0 1rem;
            box-sizing: border-box;
        }
        h1 {
            color: #111;
            font-size: 1.6rem;
            margin-bottom: 1.5rem;
        }
        .alert {
            padding: 0.65rem 0.85rem;
            border-radius: 4px;
            margin-bottom: 1.25rem;
            font-size: 0.95rem;
        }
        .alert-error {
            background-color: #fde8e8;
            color: #9b1c1c;
            border: 1px solid #f8b4b4;
        }
        .alert-success {
            background-color: #e6f7ec;
            color: #1e6b37;
            border: 1px solid #c2ebd0;
        }
        .alert-info {
            background-color: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }
        .card {
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 1.25rem;
            background: #fafafa;
            margin-bottom: 1.5rem;
        }
        .form-row {
            margin-bottom: 1rem;
        }
        .form-row label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.35rem;
        }
        .form-row input[type="email"] {
            width: 100%;
            box-sizing: border-box;
            padding: 0.5rem 0.65rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 1rem;
        }
        .btn {
            display: inline-block;
            background: #0066cc;
            color: #fff;
            border: none;
            padding: 0.55rem 1.1rem;
            font-size: 0.95rem;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }
        .btn:hover {
            background: #0052a3;
        }
        .btn-secondary {
            background: #6b7280;
        }
        .btn-secondary:hover {
            background: #4b5563;
        }
        .btn-outline {
            background: transparent;
            color: #4b5563;
            border: 1px solid #d1d5db;
        }
        .btn-outline:hover {
            background: #f3f4f6;
            color: #111;
        }
        .btn-group {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 0.5rem;
        }
        .email-info-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            border: 1px solid #e2e8f0;
            padding: 0.6rem 0.85rem;
            border-radius: 4px;
            margin-bottom: 1.25rem;
        }
        .email-display {
            font-weight: 600;
            color: #1f2937;
            word-break: break-all;
        }
        /* 10 einzelne Ziffern-Felder */
        .code-digits-wrapper {
            display: flex;
            gap: 6px;
            justify-content: space-between;
            margin: 1rem 0;
        }
        .code-digit {
            width: 38px;
            height: 48px;
            text-align: center;
            font-size: 1.25rem;
            font-weight: 600;
            font-family: monospace, monospace;
            text-transform: uppercase;
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 0;
            box-sizing: border-box;
            background: #fff;
            color: #111;
        }
        .code-digit:focus {
            border-color: #0066cc;
            outline: none;
            box-shadow: 0 0 0 2px rgba(0, 102, 204, 0.2);
        }
        @media (max-width: 480px) {
            .code-digits-wrapper {
                gap: 4px;
            }
            .code-digit {
                width: 30px;
                height: 42px;
                font-size: 1.05rem;
            }
        }
        .links {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
            font-size: 0.9rem;
        }
        .links a {
            color: #0066cc;
            text-decoration: none;
            margin-right: 1rem;
        }
        .links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="magic-wrapper">
        <h1>Willkommen im CMS-Prototype</h1>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <p style="color: red; margin: 0;"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($info)): ?>
            <div class="alert alert-info">
                <p style="margin: 0;"><?= htmlspecialchars((string) $info, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <p style="margin: 0;"><?= htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        <?php endif; ?>

        <?php if (empty($sessionEmail)): ?>
            <!-- Schritt 1: Noch keine E-Mail in der Session -->
            <div class="card">
                <p>Gib deine E-Mail-Adresse ein, um fortzufahren:</p>
                <form method="post" action="?route=magic-set-email">
                    <div class="form-row">
                        <label for="email">E-Mail-Adresse:</label>
                        <input type="email" id="email" name="email" maxlength="191" required placeholder="name@beispiel.ch" autofocus>
                    </div>
                    <button type="submit" class="btn">Weiter</button>
                </form>
            </div>
        <?php else: ?>
            <!-- Schritt 2: E-Mail in der Session gespeichert -->
            <div class="card">
                <div class="email-info-box">
                    <div>
                        <small style="color: #666; display: block;">Angemeldete E-Mail:</small>
                        <span class="email-display"><?= htmlspecialchars($sessionEmail, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <form method="post" action="?route=magic-clear-email" style="margin: 0;">
                        <button type="submit" class="btn btn-outline" style="padding: 0.35rem 0.75rem; font-size: 0.85rem;">E-Mail ändern</button>
                    </form>
                </div>

                <p>Gib deinen 10-stelligen Magic Code ein:</p>
                <form method="post" action="?route=magic-login" id="magic_login_form">
                    <input type="hidden" name="magic_code" id="magic_code_hidden">

                    <div class="code-digits-wrapper" id="magic_code_container">
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

                    <div class="btn-group">
                        <button type="submit" class="btn">Code einloggen</button>
                    </div>
                </form>

                <div style="margin-top: 1.25rem; padding-top: 1rem; border-top: 1px dashed #d1d5db;">
                    <p style="margin: 0 0 0.5rem 0; font-size: 0.9rem; color: #555;">Keinen Code zur Hand oder abgelaufen?</p>
                    <form method="post" action="?route=magic-request" style="margin: 0;">
                        <button type="submit" class="btn btn-secondary" style="font-size: 0.88rem;">Neuen Code anfordern</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['magic_authenticated'])): ?>
            <div class="links">
                <a href="?route=admin">Zum Admin-Dashboard</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('magic_login_form');
        if (!form) return;

        const hiddenInput = document.getElementById('magic_code_hidden');
        const inputs = Array.from(form.querySelectorAll('.code-digit'));

        function updateHiddenValue() {
            if (hiddenInput) {
                hiddenInput.value = inputs.map(function(inp) {
                    return inp.value.trim().toUpperCase();
                }).join('');
            }
        }

        inputs.forEach(function(input, index) {
            // Nur Grossbuchstaben und Ziffern A-Z, 0-9 erlauben
            input.addEventListener('input', function() {
                const clean = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
                this.value = clean.slice(0, 1);

                if (this.value.length === 1 && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                    inputs[index + 1].select();
                }
                updateHiddenValue();
            });

            // Backspace-Sprung zum vorherigen Feld
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

            // Unterstützung für Copy & Paste des gesamten Codes
            input.addEventListener('paste', function(e) {
                e.preventDefault();
                const pasteData = (e.clipboardData || window.clipboardData).getData('text') || '';
                const cleanData = pasteData.toUpperCase().replace(/[^A-Z0-9]/g, '');

                if (!cleanData) return;

                let targetIndex = index;
                for (let i = 0; i < cleanData.length && targetIndex < inputs.length; i++, targetIndex++) {
                    inputs[targetIndex].value = cleanData[i];
                }
                updateHiddenValue();

                const focusIndex = Math.min(targetIndex, inputs.length - 1);
                inputs[focusIndex].focus();
                inputs[focusIndex].select();
            });
        });

        form.addEventListener('submit', function() {
            updateHiddenValue();
        });
    });
    </script>
</body>
</html>
