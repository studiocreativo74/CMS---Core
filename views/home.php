<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Willkommen im CMS-Prototype</title>
</head>
<body>
    <h1>Willkommen im CMS-Prototype</h1>

    <p>Gib deinen Magic Code ein, um fortzufahren:</p>

    <?php if (!empty($error)): ?>
        <p style="color: red;"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="post" action="?route=magic-login">
        <input type="text" name="magic_code" maxlength="10" required>
        <button type="submit">Einloggen</button>
    </form>

    <p>
        <a href="?route=db-test">Zum DB-Test</a>
    </p>
</body>
</html>
