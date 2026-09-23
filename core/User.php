<?php

declare(strict_types=1);

/**
 * Zentrale Klasse zur Verwaltung von internen CMS-Benutzern (User-Accounts).
 */
final class User
{
    /**
     * Privater Konstruktor: rein statische Repository-Klasse.
     */
    private function __construct()
    {
    }

    /**
     * Gibt alle Benutzer zurück (geordnet nach ID aufsteigend).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        try {
            // Zuerst versuchen wir die Abfrage inklusive theme_mode
            $users = DB::fetchAll(
                'SELECT `id`, `email`, `name`, `role`, `is_active`, `theme_mode`, `last_login_at`, `created_at`, `updated_at` 
                 FROM `users` 
                 ORDER BY `id` ASC'
            );
            return array_map(static function (array $u): array {
                if (!isset($u['theme_mode']) || !in_array($u['theme_mode'], ['light', 'dark', 'system'], true)) {
                    $u['theme_mode'] = 'system';
                }
                return $u;
            }, $users);
        } catch (\Throwable $e) {
            // Fallback, falls neuere Spalten (theme_mode, name, is_active, last_login_at) noch nicht existieren
            try {
                $users = DB::fetchAll(
                    'SELECT `id`, `email`, `role`, `created_at`, `updated_at` 
                     FROM `users` 
                     ORDER BY `id` ASC'
                );
                return array_map(static function (array $u): array {
                    $u['name'] = explode('@', (string) ($u['email'] ?? ''))[0] ?: 'User';
                    $u['is_active'] = 1;
                    $u['theme_mode'] = 'system';
                    $u['last_login_at'] = null;
                    return $u;
                }, $users);
            } catch (\Throwable $e2) {
                error_log('User::all Fehler: ' . $e2->getMessage());
                return [];
            }
        }
    }

    /**
     * Findet einen Benutzer anhand seiner ID.
     *
     * @param int $id Benutzer-ID
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        try {
            $user = DB::fetchOne(
                'SELECT `id`, `email`, `name`, `role`, `is_active`, `theme_mode`, `last_login_at`, `created_at`, `updated_at` 
                 FROM `users` 
                 WHERE `id` = :id 
                 LIMIT 1',
                ['id' => $id]
            );
            if ($user !== null) {
                if (!isset($user['theme_mode']) || !in_array($user['theme_mode'], ['light', 'dark', 'system'], true)) {
                    $user['theme_mode'] = 'system';
                }
                return $user;
            }
            return null;
        } catch (\Throwable $e) {
            // Fallback, falls neuere Spalten (theme_mode, name, is_active, last_login_at) noch nicht existieren
            try {
                $user = DB::fetchOne(
                    'SELECT `id`, `email`, `role`, `created_at`, `updated_at` 
                     FROM `users` 
                     WHERE `id` = :id 
                     LIMIT 1',
                    ['id' => $id]
                );
                if ($user !== null) {
                    $user['name'] = explode('@', (string) ($user['email'] ?? ''))[0] ?: 'User';
                    $user['is_active'] = 1;
                    $user['theme_mode'] = 'system';
                    $user['last_login_at'] = null;
                    return $user;
                }
                return null;
            } catch (\Throwable $e2) {
                error_log('User::find Fehler: ' . $e2->getMessage());
                return null;
            }
        }
    }

    /**
     * Findet einen Benutzer anhand seiner E-Mail-Adresse.
     *
     * @param string $email E-Mail-Adresse
     * @return array<string, mixed>|null
     */
    public static function findByEmail(string $email): ?array
    {
        $cleanEmail = strtolower(trim($email));
        try {
            $user = DB::fetchOne(
                'SELECT `id`, `email`, `name`, `role`, `is_active`, `theme_mode`, `last_login_at`, `created_at`, `updated_at` 
                 FROM `users` 
                 WHERE `email` = :email 
                 LIMIT 1',
                ['email' => $cleanEmail]
            );
            if ($user !== null) {
                if (!isset($user['theme_mode']) || !in_array($user['theme_mode'], ['light', 'dark', 'system'], true)) {
                    $user['theme_mode'] = 'system';
                }
                return $user;
            }
            return null;
        } catch (\Throwable $e) {
            // Fallback, falls neuere Spalten (theme_mode, name, is_active, last_login_at) noch nicht existieren
            try {
                $user = DB::fetchOne(
                    'SELECT `id`, `email`, `role`, `created_at`, `updated_at` 
                     FROM `users` 
                     WHERE `email` = :email 
                     LIMIT 1',
                    ['email' => $cleanEmail]
                );
                if ($user !== null) {
                    $user['name'] = explode('@', (string) ($user['email'] ?? ''))[0] ?: 'User';
                    $user['is_active'] = 1;
                    $user['theme_mode'] = 'system';
                    $user['last_login_at'] = null;
                    return $user;
                }
                return null;
            } catch (\Throwable $e2) {
                error_log('User::findByEmail Fehler: ' . $e2->getMessage());
                return null;
            }
        }
    }

    /**
     * Legt einen neuen Benutzer an.
     * Erwartet: email, name, password (Klartext), role (optional, default 'admin'), is_active (optional, default 1).
     * Das Passwort wird intern sicher mit password_hash() gehasht.
     *
     * @param array<string, mixed> $data
     * @return int ID des neu angelegten Benutzers
     * @throws InvalidArgumentException Wenn Validierung fehlschlägt
     * @throws RuntimeException Wenn Einfügen fehlschlägt
     */
    public static function create(array $data): int
    {
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $name = trim((string) ($data['name'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        $role = trim((string) ($data['role'] ?? 'admin'));
        if ($role === '') {
            $role = 'admin';
        }
        $isActive = isset($data['is_active']) ? ((bool) $data['is_active'] ? 1 : 0) : 1;

        if ($email === '' || strlen($email) > 191 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Bitte eine gültige E-Mail-Adresse angeben (max. 191 Zeichen).');
        }

        if ($name === '' || strlen($name) > 191) {
            throw new InvalidArgumentException('Bitte einen Namen angeben (max. 191 Zeichen).');
        }

        if ($password === '') {
            throw new InvalidArgumentException('Das Passwort darf nicht leer sein.');
        }

        $existing = self::findByEmail($email);
        if ($existing !== null) {
            throw new InvalidArgumentException('Ein Benutzer mit dieser E-Mail-Adresse existiert bereits.');
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $themeMode = strtolower(trim((string) ($data['theme_mode'] ?? 'system')));
        if (!in_array($themeMode, ['light', 'dark', 'system'], true)) {
            $themeMode = 'system';
        }

        try {
            DB::execute(
                'INSERT INTO `users` (`email`, `password_hash`, `name`, `role`, `is_active`, `theme_mode`, `created_at`, `updated_at`) 
                 VALUES (:email, :password_hash, :name, :role, :is_active, :theme_mode, NOW(), NOW())',
                [
                    'email' => $email,
                    'password_hash' => $passwordHash,
                    'name' => $name,
                    'role' => $role,
                    'is_active' => $isActive,
                    'theme_mode' => $themeMode,
                ]
            );

            return (int) DB::lastInsertId();
        } catch (\Throwable $e) {
            // Fallback, falls theme_mode Spalte noch nicht existiert
            try {
                DB::execute(
                    'INSERT INTO `users` (`email`, `password_hash`, `name`, `role`, `is_active`, `created_at`, `updated_at`) 
                     VALUES (:email, :password_hash, :name, :role, :is_active, NOW(), NOW())',
                    [
                        'email' => $email,
                        'password_hash' => $passwordHash,
                        'name' => $name,
                        'role' => $role,
                        'is_active' => $isActive,
                    ]
                );

                return (int) DB::lastInsertId();
            } catch (\Throwable $e2) {
                error_log('User::create Fehler: ' . $e2->getMessage());
                throw new RuntimeException('Fehler beim Erstellen des Benutzers: ' . $e2->getMessage(), 0, $e2);
            }
        }
    }

    /**
     * Aktualisiert die Stammdaten eines Benutzers.
     * Kann optional ein neues Klartext-Passwort enthalten, das dann neu gehasht wird.
     *
     * @param int $id Benutzer-ID
     * @param array<string, mixed> $data
     * @throws InvalidArgumentException Wenn Validierung fehlschlägt
     * @throws RuntimeException Wenn DB-Update fehlschlägt
     */
    public static function update(int $id, array $data): void
    {
        $fields = [];
        $params = ['id' => $id];

        if (array_key_exists('email', $data)) {
            $email = strtolower(trim((string) $data['email']));
            if ($email === '' || strlen($email) > 191 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Bitte eine gültige E-Mail-Adresse angeben (max. 191 Zeichen).');
            }

            // Prüfen, ob die E-Mail bereits von einem anderen User belegt ist
            $existing = self::findByEmail($email);
            if ($existing !== null && (int) $existing['id'] !== $id) {
                throw new InvalidArgumentException('Diese E-Mail-Adresse wird bereits von einem anderen Benutzer verwendet.');
            }

            $fields[] = '`email` = :email';
            $params['email'] = $email;
        }

        if (array_key_exists('name', $data)) {
            $name = trim((string) $data['name']);
            if ($name === '' || strlen($name) > 191) {
                throw new InvalidArgumentException('Bitte einen Namen angeben (max. 191 Zeichen).');
            }
            $fields[] = '`name` = :name';
            $params['name'] = $name;
        }

        if (array_key_exists('role', $data)) {
            $role = trim((string) $data['role']);
            if ($role === '') {
                $role = 'admin';
            }
            $fields[] = '`role` = :role';
            $params['role'] = $role;
        }

        if (array_key_exists('is_active', $data)) {
            $fields[] = '`is_active` = :is_active';
            $params['is_active'] = (bool) $data['is_active'] ? 1 : 0;
        }

        if (array_key_exists('theme_mode', $data)) {
            $themeMode = strtolower(trim((string) $data['theme_mode']));
            if (!in_array($themeMode, ['light', 'dark', 'system'], true)) {
                $themeMode = 'system';
            }
            $fields[] = '`theme_mode` = :theme_mode';
            $params['theme_mode'] = $themeMode;
        }

        if (!empty($data['password'])) {
            $password = (string) $data['password'];
            $fields[] = '`password_hash` = :password_hash';
            $params['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }

        if (empty($fields)) {
            return;
        }

        $fields[] = '`updated_at` = NOW()';

        try {
            $sql = 'UPDATE `users` SET ' . implode(', ', $fields) . ' WHERE `id` = :id';
            DB::execute($sql, $params);
        } catch (\Throwable $e) {
            // Falls theme_mode in der DB noch nicht existiert, versuchen wir es ohne theme_mode
            if (array_key_exists('theme_mode', $params)) {
                unset($params['theme_mode']);
                $filteredFields = array_filter($fields, static fn(string $f): bool => !str_contains($f, '`theme_mode`'));
                if (!empty($filteredFields)) {
                    try {
                        $sqlFallback = 'UPDATE `users` SET ' . implode(', ', $filteredFields) . ' WHERE `id` = :id';
                        DB::execute($sqlFallback, $params);
                        return;
                    } catch (\Throwable $eFallback) {
                        // Weitermachen zum Hauptfehler
                    }
                }
            }
            error_log('User::update Fehler: ' . $e->getMessage());
            throw new RuntimeException('Fehler beim Aktualisieren des Benutzers: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Aktualisiert Benutzereinstellungen wie z.B. das Theme.
     *
     * @param int $id Benutzer-ID
     * @param array<string, mixed> $preferences
     */
    public static function updatePreferences(int $id, array $preferences): void
    {
        $allowed = [];
        if (array_key_exists('theme_mode', $preferences)) {
            $themeMode = strtolower(trim((string) $preferences['theme_mode']));
            $allowed['theme_mode'] = in_array($themeMode, ['light', 'dark', 'system'], true) ? $themeMode : 'system';
        }

        if (!empty($allowed)) {
            self::update($id, $allowed);
        }
    }

    /**
     * Gibt das eingestellte Theme eines Benutzers zurück ('light', 'dark', 'system').
     *
     * @param int $id Benutzer-ID
     * @return string
     */
    public static function getTheme(int $id): string
    {
        $user = self::find($id);
        $mode = (string) ($user['theme_mode'] ?? 'system');
        return in_array($mode, ['light', 'dark', 'system'], true) ? $mode : 'system';
    }

    /**
     * Deaktiviert einen Benutzer (is_active = 0).
     *
     * @param int $id Benutzer-ID
     */
    public static function deactivate(int $id): void
    {
        try {
            DB::execute('UPDATE `users` SET `is_active` = 0, `updated_at` = NOW() WHERE `id` = :id', [
                'id' => $id,
            ]);
        } catch (\Throwable $e) {
            error_log('User::deactivate Fehler: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Reaktiviert einen Benutzer (is_active = 1).
     *
     * @param int $id Benutzer-ID
     */
    public static function activate(int $id): void
    {
        try {
            DB::execute('UPDATE `users` SET `is_active` = 1, `updated_at` = NOW() WHERE `id` = :id', [
                'id' => $id,
            ]);
        } catch (\Throwable $e) {
            error_log('User::activate Fehler: ' . $e->getMessage());
            throw $e;
        }
    }
}
