<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <h1>Login</h1>

    <?php if (!empty($error)): ?>
        <p style="color: red;"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="post" action="?route=login">
        <div>
            <label for="email">E-Mail:</label><br>
            <input type="email" id="email" name="email" required value="<?= htmlspecialchars((string) ($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <br>
        <div>
            <label for="password">Passwort:</label><br>
            <input type="password" id="password" name="password" required>
        </div>
        <br>
        <div>
            <button type="submit">Einloggen</button>
        </div>
    </form>

    <p>
        <a href="?route=/">Zurück zur Startseite</a>
    </p>
</body>
</html>
