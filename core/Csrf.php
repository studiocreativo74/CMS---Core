<?php

declare(strict_types=1);

final class Csrf
{
    private const SESSION_KEY = 'csrf_token';

    /**
     * Gibt das aktuelle CSRF-Token zurück oder erzeugt ein neues, falls noch keines existiert.
     */
    public static function getToken(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
            session_start();
        }

        if (empty($_SESSION[self::SESSION_KEY]) || !is_string($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Prüft, ob das übergebene Token mit dem Session-Token übereinstimmt.
     */
    public static function validate(?string $token): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
            session_start();
        }

        $sessionToken = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_string($sessionToken) || $sessionToken === '' || !is_string($token) || $token === '') {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    /**
     * Prüft das CSRF-Token aus dem aktuellen Request ($_POST['csrf_token'] oder HTTP-Header).
     */
    public static function validateRequest(): bool
    {
        $token = $_POST['csrf_token'] 
            ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] 
            ?? null);

        return self::validate(is_string($token) ? $token : null);
    }

    /**
     * Erzeugt ein HTML Hidden-Input-Feld mit dem CSRF-Token.
     */
    public static function input(): string
    {
        $token = htmlspecialchars(self::getToken(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }
}
