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

    public static function loginWithMagicCode(string $code): bool
    {
        self::startSession();

        $cleanCode = strtoupper(trim($code));
        if ($cleanCode === '' || strlen($cleanCode) > 10) {
            return false;
        }

        try {
            $candidates = DB::fetchAll(
                "SELECT * FROM magic_codes 
                 WHERE usage_type = 'admin_login' 
                   AND used_count < max_uses 
                   AND (expires_at IS NULL OR expires_at > NOW())"
            );
        } catch (\Throwable $e) {
            return false;
        }

        foreach ($candidates as $row) {
            if (password_verify($cleanCode, (string) ($row['code_hash'] ?? ''))) {
                session_regenerate_id(true);

                $_SESSION['magic_authenticated'] = true;
                $_SESSION['magic_code_id'] = (int) $row['id'];

                try {
                    DB::execute(
                        'UPDATE magic_codes SET used_count = used_count + 1, used_at = NOW() WHERE id = :id',
                        ['id' => $row['id']]
                    );
                } catch (\Throwable $e) {
                    // Update error handled gracefully
                }

                return true;
            }
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
            $user = DB::fetchOne(
                'SELECT * FROM users WHERE id = :id AND is_active = 1',
                ['id' => $_SESSION['user_id']]
            );
            if ($user) {
                return $user;
            }
        }

        if (!empty($_SESSION['magic_authenticated'])) {
            return [
                'id' => 0,
                'name' => 'Magic-Admin',
                'email' => 'admin@magic-code',
                'is_magic' => true,
            ];
        }

        return null;
    }

    public static function check(): bool
    {
        self::startSession();

        return !empty($_SESSION['user_id']) || !empty($_SESSION['magic_authenticated']);
    }
}
