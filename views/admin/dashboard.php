<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin-Dashboard</title>
</head>
<body>
    <h1>Admin-Dashboard</h1>
    <p>Hallo, <?= htmlspecialchars($user['name'] ?? $user['email'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?>!</p>
    <p>Hier entsteht das Admin-Dashboard.</p>

    <p>
        <a href="?route=logout">Logout</a> |
        <a href="?route=/">Zurück zur Startseite</a>
    </p>
</body>
</html>
