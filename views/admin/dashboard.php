<?php
declare(strict_types=1);

$user = $user ?? Auth::user();
$magicCodes = $magicCodes ?? MagicCode::getAll();
$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashCreatedCode = $_SESSION['flash_created_code'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;

// Flash-Nachrichten nach dem Auslesen leeren
unset($_SESSION['flash_success'], $_SESSION['flash_created_code'], $_SESSION['flash_error']);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin-Dashboard - Magic-Codes</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.5;
            color: #222;
            max-width: 960px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        h1, h2 {
            color: #111;
        }
        .nav {
            margin-bottom: 2rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #ddd;
        }
        .nav a {
            color: #0066cc;
            text-decoration: none;
            margin-right: 1rem;
        }
        .nav a:hover {
            text-decoration: underline;
        }
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
        }
        .alert-success {
            background-color: #e6f7ec;
            color: #1e6b37;
            border: 1px solid #c2ebd0;
        }
        .alert-error {
            background-color: #fde8e8;
            color: #9b1c1c;
            border: 1px solid #f8b4b4;
        }
        .code-highlight {
            font-size: 1.25rem;
            font-weight: bold;
            letter-spacing: 2px;
            padding: 0.2rem 0.6rem;
            background: #fff;
            border: 1px dashed #1e6b37;
            border-radius: 3px;
            display: inline-block;
            margin: 0.4rem 0;
        }
        .card {
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 1.25rem;
            margin-bottom: 2rem;
            background: #fafafa;
        }
        .form-row {
            margin-bottom: 1rem;
        }
        .form-row label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        .form-row input, .form-row select {
            width: 100%;
            max-width: 380px;
            padding: 0.45rem 0.6rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 1rem;
            box-sizing: border-box;
        }
        button[type="submit"] {
            background: #0066cc;
            color: #fff;
            border: none;
            padding: 0.6rem 1.2rem;
            font-size: 1rem;
            border-radius: 4px;
            cursor: pointer;
        }
        button[type="submit"]:hover {
            background: #0052a3;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            background: #fff;
        }
        th, td {
            text-align: left;
            padding: 0.65rem 0.75rem;
            border: 1px solid #e2e8f0;
            font-size: 0.92rem;
        }
        th {
            background: #f1f5f9;
            font-weight: 600;
        }
        .badge {
            display: inline-block;
            padding: 0.15rem 0.45rem;
            border-radius: 3px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .badge-active {
            background: #def7ec;
            color: #03543f;
        }
        .badge-used {
            background: #f3f4f6;
            color: #374151;
        }
        .badge-expired {
            background: #feecdc;
            color: #92400e;
        }
    </style>
</head>
<body>
    <div class="nav">
        <strong>CMS Admin</strong> |
        <span>Angemeldet als: <strong><?= htmlspecialchars($user['email'] ?? $user['name'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?></strong></span> |
        <a href="?route=logout">Logout</a> |
        <a href="?route=/">Zur Startseite</a>
    </div>

    <h1>Admin-Dashboard</h1>

    <?php if ($flashSuccess): ?>
        <div class="alert alert-success">
            <p><?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?></p>
            <?php if ($flashCreatedCode): ?>
                <p>Generierter Klartext-Code: <span class="code-highlight"><?= htmlspecialchars($flashCreatedCode, ENT_QUOTES, 'UTF-8') ?></span></p>
                <small>Hinweis: Der Klartext-Code wird nur einmalig angezeigt und wurde verschlüsselt in der Datenbank gespeichert.</small>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($flashError): ?>
        <div class="alert alert-error">
            <?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2>Neuen Magic-Code erstellen</h2>
        <form method="post" action="?route=admin/magic-code/create">
            <div class="form-row">
                <label for="email">Empfänger E-Mail:</label>
                <input type="email" id="email" name="email" value="office@studiocreativo.ch" maxlength="191" required>
            </div>

            <div class="form-row">
                <label for="usage_type">Verwendungszweck:</label>
                <select id="usage_type" name="usage_type">
                    <option value="admin_login">admin_login (Admin-Dashboard)</option>
                    <option value="frontend">frontend (Frontend-Zugang)</option>
                </select>
            </div>

            <div class="form-row">
                <label for="max_uses">Maximale Nutzungen:</label>
                <input type="number" id="max_uses" name="max_uses" value="1" min="1" max="999" required>
            </div>

            <div class="form-row">
                <label for="expires_at">Ablaufdatum (optional):</label>
                <input type="datetime-local" id="expires_at" name="expires_at">
                <small style="display:block; color:#666; margin-top:0.25rem;">Leer lassen, falls der Code unbegrenzt gültig sein soll.</small>
            </div>

            <button type="submit">Magic-Code generieren & versenden</button>
            <small style="display:block; color:#666; margin-top:0.5rem;">
                Der Klartext-Code wird an die angegebene E-Mail gesendet (Kopie an <strong>office@studiocreativo.ch</strong>).
            </small>
        </form>
    </div>

    <h2>Vorhandene Magic-Codes</h2>

    <?php if (empty($magicCodes)): ?>
        <p>Noch keine Magic-Codes in der Datenbank hinterlegt.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>E-Mail</th>
                    <th>Verwendungszweck</th>
                    <th>Nutzungen</th>
                    <th>Status</th>
                    <th>Ablaufdatum</th>
                    <th>Erstellt am</th>
                    <th>Zuletzt verwendet</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $now = new DateTimeImmutable();
                foreach ($magicCodes as $row):
                    $usedCount = (int) ($row['used_count'] ?? 0);
                    $maxUses = (int) ($row['max_uses'] ?? 1);
                    $expiresAtStr = $row['expires_at'] ?? null;
                    $isExpired = false;

                    if ($expiresAtStr !== null) {
                        try {
                            $expiresAtDate = new DateTimeImmutable((string) $expiresAtStr);
                            $isExpired = $expiresAtDate <= $now;
                        } catch (\Exception $e) {
                            $isExpired = false;
                        }
                    }

                    $isExhausted = $usedCount >= $maxUses;
                ?>
                <tr>
                    <td>#<?= (int) $row['id'] ?></td>
                    <td><strong><?= htmlspecialchars((string) ($row['email'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td><code><?= htmlspecialchars((string) $row['usage_type'], ENT_QUOTES, 'UTF-8') ?></code></td>
                    <td><?= $usedCount ?> / <?= $maxUses ?></td>
                    <td>
                        <?php if ($isExhausted): ?>
                            <span class="badge badge-used">Aufgebraucht</span>
                        <?php elseif ($isExpired): ?>
                            <span class="badge badge-expired">Abgelaufen</span>
                        <?php else: ?>
                            <span class="badge badge-active">Aktiv</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $expiresAtStr ? htmlspecialchars((string) $expiresAtStr, ENT_QUOTES, 'UTF-8') : '<em>Kein Ablauf</em>' ?></td>
                    <td><?= htmlspecialchars((string) $row['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= !empty($row['used_at']) ? htmlspecialchars((string) $row['used_at'], ENT_QUOTES, 'UTF-8') : '<em>Noch nie</em>' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
