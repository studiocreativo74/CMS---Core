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
        if (isset($this->routes[$method][$normalized])) {
            return true;
        }

        if (!empty($this->routes[$method])) {
            foreach (array_keys($this->routes[$method]) as $routePattern) {
                if (str_contains($routePattern, '{')) {
                    $regex = '#^' . preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $routePattern) . '$#';
                    if (preg_match($regex, $normalized)) {
                        return true;
                    }
                }
            }
        }

        return false;
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

        // 1. Exakter Match
        if (isset($this->routes[$method][$normalized]) && is_callable($this->routes[$method][$normalized])) {
            call_user_func($this->routes[$method][$normalized]);
            return;
        }

        // 2. Parametrisierte Routen wie /bootschule/me/appointments/{id}/cancel
        if (!empty($this->routes[$method])) {
            foreach ($this->routes[$method] as $routePattern => $handler) {
                if (str_contains($routePattern, '{') && is_callable($handler)) {
                    $regex = '#^' . preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $routePattern) . '$#';
                    if (preg_match($regex, $normalized, $matches)) {
                        $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                        foreach ($params as $k => $v) {
                            $_GET[$k] = $v;
                        }
                        call_user_func_array($handler, array_values($params));
                        return;
                    }
                }
            }
        }

        // Intelligenter Fallback für Bootsfahrschul- & Nautic-Einstiegsrouten
        $clean = trim($normalized, '/');
        if (in_array($clean, ['boating_school', 'boating-school', 'nautic', 'bootschule', 'boating'], true)) {
            header('Location: ?route=nautic/login');
            exit;
        }

        http_response_code(404);
        echo '404 - Not Found';
    }
}
