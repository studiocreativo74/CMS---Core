<?php

declare(strict_types=1);

class Router
{
    private static ?Router $instance = null;
    private array $routes = [];

    public function __construct()
    {
        self::$instance = $this;
    }

    /**
     * Prüft statisch auf der aktuellen Router-Instanz, ob eine Route für eine Methode registriert ist.
     */
    public static function hasRoute(string $path, string $method = 'GET'): bool
    {
        if (self::$instance === null) {
            return false;
        }
        return self::$instance->has($path, $method);
    }

    /**
     * Normalisiert einen Pfad: Führendes '/', kein abschließendes '/' (außer Root), Query-Parameter entfernen.
     */
    private function normalizePath(string $path): string
    {
        $path = explode('?', $path, 2)[0];
        $trimmed = trim($path, '/');
        return $trimmed === '' ? '/' : '/' . $trimmed;
    }

    /**
     * Prüft, ob ein Pfad für eine HTTP-Methode registriert ist.
     */
    public function has(string $path, string $method = 'GET'): bool
    {
        $method = strtoupper($method);
        $normalized = $this->normalizePath($path);
        return isset($this->routes[$method][$normalized]);
    }

    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][$this->normalizePath($path)] = $handler;
    }

    public function post(string $path, callable $handler): void
    {
        $this->routes['POST'][$this->normalizePath($path)] = $handler;
    }

    public function dispatch(string $method, string $path): void
    {
        $method = strtoupper($method);
        $normalized = $this->normalizePath($path);

        if (isset($this->routes[$method][$normalized]) && is_callable($this->routes[$method][$normalized])) {
            call_user_func($this->routes[$method][$normalized]);
            return;
        }

        http_response_code(404);
        echo '404 - Not Found';
    }
}
