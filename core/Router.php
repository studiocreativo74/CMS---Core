<?php

declare(strict_types=1);

class Router
{
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, callable $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(string $method, string $path): void
    {
        $method = strtoupper($method);

        if ($path === '') {
            $path = '/';
        }

        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        if (isset($this->routes[$method][$path]) && is_callable($this->routes[$method][$path])) {
            call_user_func($this->routes[$method][$path]);
            return;
        }

        http_response_code(404);
        echo '404 - Not Found';
    }
}
