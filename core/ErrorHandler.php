<?php

declare(strict_types=1);

/**
 * Zentrales Error- & Exception-Handling
 *
 * Verhindert das Ausgeben roher PHP-Fehlermeldungen, Pfade und Stack-Traces im Browser in Produktion.
 * - Loggt alle Fehler und Ausnahmen strukturiert via error_log()
 * - Zeigt in Produktion eine sichere, gestaltete 500-Fehlerseite
 * - Zeigt für angemeldete Administratoren optionale Diagnose-Details
 * - Erlaubt detaillierte Fehlerausgabe in Entwicklerumgebungen (APP_ENV=development oder APP_DEBUG=true)
 */
final class ErrorHandler
{
    private static bool $isRegistered = false;

    /**
     * Privater Konstruktor: Statische Hilfsklasse.
     */
    private function __construct()
    {
    }

    /**
     * Registriert die globalen PHP-Fehler-, Ausnahme- und Shutdown-Handler.
     */
    public static function register(): void
    {
        if (self::$isRegistered) {
            return;
        }

        self::$isRegistered = true;

        $isDev = self::isDebugMode();

        if ($isDev) {
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');
            error_reporting(E_ALL);
        } else {
            ini_set('display_errors', '0');
            ini_set('display_startup_errors', '0');
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
        }

        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    /**
     * Prüft, ob der Debug- bzw. Entwicklungsmodus aktiv ist.
     */
    public static function isDebugMode(): bool
    {
        $env = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? null);
        if ($env !== null && in_array(strtolower((string)$env), ['dev', 'development', 'local'], true)) {
            return true;
        }

        $debug = getenv('APP_DEBUG') ?: ($_ENV['APP_DEBUG'] ?? null);
        if ($debug !== null && in_array(strtolower((string)$debug), ['1', 'true', 'yes'], true)) {
            return true;
        }

        return false;
    }

    /**
     * Fängt PHP-Standardfehler ab und wandelt gravierende Fehler in Exceptions um.
     */
    public static function handleError(int $severity, string $message, string $file, int $line): bool
    {
        // Durch @ unterdrückte Fehler ignorieren
        if (!(error_reporting() & $severity)) {
            return false;
        }

        $logMsg = sprintf('[PHP Error %d] %s in %s:%d', $severity, $message, $file, $line);
        error_log($logMsg);

        // Fatale / schwere Fehlertypen in ErrorException umwandeln
        if (in_array($severity, [E_USER_ERROR, E_RECOVERABLE_ERROR], true)) {
            throw new ErrorException($message, 0, $severity, $file, $line);
        }

        return true;
    }

    /**
     * Fängt unbehandelte Ausnahmen (Exceptions & Errors) ab.
     */
    public static function handleException(\Throwable $exception): void
    {
        $logMsg = sprintf(
            "[Uncaught Exception] %s: %s in %s:%d\nStack trace:\n%s",
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );
        error_log($logMsg);

        self::renderErrorResponse($exception);
    }

    /**
     * Fängt fatale PHP-Fehler bei Skript-Beendigung ab.
     */
    public static function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE], true)) {
            $logMsg = sprintf(
                '[Fatal PHP Error %d] %s in %s:%d',
                $error['type'],
                $error['message'],
                $error['file'],
                $error['line']
            );
            error_log($logMsg);

            $exception = new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']);
            self::renderErrorResponse($exception);
        }
    }

    /**
     * Rendert die einheitliche Fehlerseite.
     */
    private static function renderErrorResponse(\Throwable $e): void
    {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
            header('X-Content-Type-Options: nosniff');
        }

        // Puffer leeren, damit keine halben HTML-Fragmente ausgegeben werden
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $isDev = self::isDebugMode();
        $isAdmin = false;
        if (session_status() === PHP_SESSION_ACTIVE) {
            $isAdmin = !empty($_SESSION['magic_authenticated']) || !empty($_SESSION['user_id']);
        }

        $title = '500 – Unerwarteter Serverfehler';
        $message = $e->getMessage();
        $file = $e->getFile();
        $line = $e->getLine();
        $trace = $e->getTraceAsString();
        $class = get_class($e);

        ?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8fafc;
            color: #1e293b;
            font-family: system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .error-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
            max-width: 640px;
            width: 100%;
            padding: 2.5rem;
            border: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="error-card text-center">
        <div class="mb-4">
            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 fs-6">
                Fehler 500
            </span>
        </div>
        <h1 class="h3 fw-bold text-dark mb-2">Ein unerwarteter Fehler ist aufgetreten</h1>
        <p class="text-muted mb-4">
            Die Anfrage konnte leider nicht wie gewohnt verarbeitet werden. Der Vorfall wurde im Server-Protokoll erfasst.
        </p>

        <div class="d-flex justify-content-center gap-2 mb-4">
            <a href="?route=/" class="btn btn-primary px-4">Zur Startseite</a>
            <a href="javascript:location.reload()" class="btn btn-outline-secondary px-3">Erneut versuchen</a>
        </div>

        <?php if ($isDev || $isAdmin): ?>
            <div class="text-start mt-4 pt-4 border-top">
                <details class="small">
                    <summary class="fw-bold text-secondary cursor-pointer" style="cursor: pointer;">
                        <?= $isDev ? 'Entwickler-Fehlerdetails (APP_DEBUG=true)' : 'Diagnose-Details für Administratoren' ?>
                    </summary>
                    <div class="mt-3 p-3 bg-light rounded border font-monospace" style="font-size: 0.8rem; overflow-x: auto;">
                        <p class="mb-1 text-danger fw-bold"><?= htmlspecialchars($class, ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="text-muted mb-2">in <?= htmlspecialchars($file, ENT_QUOTES, 'UTF-8') ?>:<?= $line ?></p>
                        <hr class="my-2">
                        <pre class="mb-0" style="white-space: pre-wrap; word-break: break-all;"><?= htmlspecialchars($trace, ENT_QUOTES, 'UTF-8') ?></pre>
                    </div>
                </details>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
        <?php
        exit;
    }
}
