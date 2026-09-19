<?php

declare(strict_types=1);

class Auth
{
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
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

        if (empty($_SESSION['user_id'])) {
            return null;
        }

        $user = DB::fetchOne(
            'SELECT * FROM users WHERE id = :id AND is_active = 1',
            ['id' => $_SESSION['user_id']]
        );

        return $user ?: null;
    }

    public static function check(): bool
    {
        self::startSession();

        return !empty($_SESSION['user_id']);
    }
}
