<?php

declare(strict_types=1);

class Auth
{
    public static function startSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
            session_start();
        }
    }

    public static function loginWithMagicCodeForEmail(string $email, string $code): bool
    {
        self::startSession();

        // =====================================================================
        // TODO: Dev-Magic-Code vor Livegang deaktivieren/entfernen.
        // Vorab-Prüfung auf den festen Entwickler-Code (Developer-Backdoor).
        // Funktioniert unabhängig von der Datenbank.
        // =====================================================================
        if (MagicCode::isDevMagicCodeValid($email, $code)) {
            session_regenerate_id(true);

            $cleanEmail = strtolower(trim($email));
            $_SESSION['magic_authenticated'] = true;
            $_SESSION['magic_code_id'] = 0;
            $_SESSION['magic_email'] = $cleanEmail;
            $_SESSION['magic_input_email'] = $cleanEmail;

            try {
                $user = DB::fetchOne(
                    'SELECT * FROM users WHERE email = :email AND is_active = 1 LIMIT 1',
                    ['email' => $cleanEmail]
                );
                if ($user) {
                    $_SESSION['user_id'] = (int) $user['id'];
                    $_SESSION['user_email'] = (string) $user['email'];
                    $_SESSION['user_name'] = $user['name'] ?? null;
                    DB::execute('UPDATE users SET last_login_at = NOW() WHERE id = :id', ['id' => $user['id']]);
                }
            } catch (\Throwable $e) {
                // users-Tabelle existiert evtl. noch nicht
            }

            return true;
        }

        $record = MagicCode::verifyAdminCodeForEmail($email, $code);
        if ($record !== null) {
            session_regenerate_id(true);

            $_SESSION['magic_authenticated'] = true;
            $_SESSION['magic_code_id'] = (int) $record['id'];
            $_SESSION['magic_email'] = (string) $record['email'];
            $_SESSION['magic_input_email'] = (string) $record['email'];

            try {
                $user = DB::fetchOne(
                    'SELECT * FROM users WHERE email = :email AND is_active = 1 LIMIT 1',
                    ['email' => (string) $record['email']]
                );
                if ($user) {
                    $_SESSION['user_id'] = (int) $user['id'];
                    $_SESSION['user_email'] = (string) $user['email'];
                    $_SESSION['user_name'] = $user['name'] ?? null;
                    DB::execute('UPDATE users SET last_login_at = NOW() WHERE id = :id', ['id' => $user['id']]);
                }
            } catch (\Throwable $e) {
                // users-Tabelle existiert evtl. noch nicht
            }

            MagicCode::markUsed((int) $record['id']);

            return true;
        }

        return false;
    }

    public static function checkMagic(): bool
    {
        self::startSession();

        return !empty($_SESSION['magic_authenticated']);
    }

    public static function requireMagic(): void
    {
        if (!self::checkMagic()) {
            header('Location: ?route=/');
            exit;
        }
    }

    public static function login(string $email, string $password): bool
    {
        self::startSession();

        $user = DB::fetchOne(
            'SELECT * FROM users WHERE email = :email AND is_active = 1',
            ['email' => $email]
        );

        if ($user === null || !password_verify($password, (string) ($user['password_hash'] ?? ''))) {
            return false;
        }

        // Neue Session-ID generieren (Session Fixation Schutz)
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['name'] ?? null;

        DB::execute(
            'UPDATE users SET last_login_at = NOW() WHERE id = :id',
            ['id' => $user['id']]
        );

        return true;
    }

    public static function logout(): void
    {
        self::startSession();

        $_SESSION = [];

        if (ini_get('session.use_cookies') && !headers_sent()) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                (bool) $params['secure'],
                (bool) $params['httponly']
            );
        }

        session_destroy();
    }

    public static function user(): ?array
    {
        self::startSession();

        if (!empty($_SESSION['user_id'])) {
            try {
                $user = DB::fetchOne(
                    'SELECT * FROM users WHERE id = :id AND is_active = 1',
                    ['id' => $_SESSION['user_id']]
                );
                if ($user) {
                    if (empty($user['theme_mode']) || !in_array($user['theme_mode'], ['light', 'dark', 'system'], true)) {
                        $user['theme_mode'] = $_SESSION['theme_mode'] ?? 'system';
                    }
                    return $user;
                }
            } catch (\Throwable $e) {
                // users-Tabelle oder Spalte noch nicht vorhanden
            }
        }

        if (!empty($_SESSION['magic_authenticated'])) {
            $email = (string) ($_SESSION['magic_email'] ?? 'admin@magic-code');
            return [
                'id' => 0,
                'name' => 'Magic-Admin',
                'email' => $email,
                'is_magic' => true,
                'theme_mode' => (string) ($_SESSION['theme_mode'] ?? 'system'),
            ];
        }

        return null;
    }

    /**
     * Alias für self::user()
     *
     * @return array<string, mixed>|null
     */
    public static function currentUser(): ?array
    {
        return self::user();
    }

    /**
     * Liefert das Theme des aktuellen Benutzers ('light', 'dark', 'system').
     *
     * @return string
     */
    public static function getCurrentTheme(): string
    {
        self::startSession();

        $user = self::user();
        if ($user !== null && !empty($user['theme_mode'])) {
            $mode = (string) $user['theme_mode'];
            if (in_array($mode, ['light', 'dark', 'system'], true)) {
                return $mode;
            }
        }

        $sessionTheme = (string) ($_SESSION['theme_mode'] ?? 'system');
        return in_array($sessionTheme, ['light', 'dark', 'system'], true) ? $sessionTheme : 'system';
    }

    /**
     * Speichert das Theme für den aktuellen Benutzer (in Session und DB).
     *
     * @param string $theme
     * @return void
     */
    public static function setCurrentTheme(string $theme): void
    {
        self::startSession();

        $theme = strtolower(trim($theme));
        if (!in_array($theme, ['light', 'dark', 'system'], true)) {
            $theme = 'system';
        }

        $_SESSION['theme_mode'] = $theme;

        if (!empty($_SESSION['user_id']) && class_exists('User')) {
            try {
                User::updatePreferences((int) $_SESSION['user_id'], ['theme_mode' => $theme]);
            } catch (\Throwable $e) {
                error_log('Auth::setCurrentTheme DB Update Fehler: ' . $e->getMessage());
            }
        }
    }

    public static function check(): bool
    {
        self::startSession();

        return !empty($_SESSION['user_id']) || !empty($_SESSION['magic_authenticated']);
    }
}
