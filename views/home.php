<?php
declare(strict_types=1);

$sessionEmail = $_SESSION['magic_input_email'] ?? null;
$error = $error ?? ($_SESSION['flash_home_error'] ?? null);
$success = $success ?? ($_SESSION['flash_home_success'] ?? null);

unset($_SESSION['flash_home_error'], $_SESSION['flash_home_success']);
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
            max-width: 520px;
            margin: 3rem auto;
            padding: 0 1rem;
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
        .form-row input[type="text"],
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
        .code-input {
            letter-spacing: 2px;
            text-transform: uppercase;
            font-family: monospace;
            font-size: 1.1rem !important;
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
    <h1>Willkommen im CMS-Prototype</h1>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <p style="color: red; margin: 0;"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p>
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
            <form method="post" action="?route=magic-start">
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
                <form method="post" action="?route=magic-reset-email" style="margin: 0;">
                    <button type="submit" class="btn btn-outline" style="padding: 0.35rem 0.75rem; font-size: 0.85rem;">E-Mail ändern</button>
                </form>
            </div>

            <p>Gib deinen 10-stelligen Magic Code ein:</p>
            <form method="post" action="?route=magic-login">
                <div class="form-row">
                    <input type="text" name="magic_code" class="code-input" maxlength="10" required placeholder="ABCDE12345" autofocus>
                </div>
                <div class="btn-group">
                    <button type="submit" class="btn">Code einloggen</button>
                </div>
            </form>

            <div style="margin-top: 1.25rem; padding-top: 1rem; border-top: 1px dashed #d1d5db;">
                <p style="margin: 0 0 0.5rem 0; font-size: 0.9rem; color: #555;">Keinen Code zur Hand oder abgelaufen?</p>
                <form method="post" action="?route=magic-request-code" style="margin: 0;">
                    <button type="submit" class="btn btn-secondary" style="font-size: 0.88rem;">Neuen Code anfordern</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <div class="links">
        <a href="?route=db-test">Zum DB-Test</a>
        <?php if (!empty($_SESSION['magic_authenticated'])): ?>
            <a href="?route=admin">Zum Admin-Dashboard</a>
        <?php endif; ?>
    </div>
</body>
</html>
