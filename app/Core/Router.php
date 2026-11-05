<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, array|callable $action, array $middleware = []): void
    {
        $this->add('GET', $path, $action, $middleware);
    }

    public function post(string $path, array|callable $action, array $middleware = []): void
    {
        $this->add('POST', $path, $action, $middleware);
    }

    public function add(string $method, string $path, array|callable $action, array $middleware = []): void
    {
        $normalized = $this->normalizeRoute($path);
        $pattern = preg_replace(
            '#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#',
            '(?P<$1>[^/]+)',
            $normalized
        );

        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $normalized,
            'pattern' => '#^' . $pattern . '$#',
            'action' => $action,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = $this->normalizeRequestPath($uri);
        $allowedMethods = [];

        foreach ($this->routes as $route) {
            if (preg_match($route['pattern'], $path, $matches) !== 1) {
                continue;
            }

            if ($route['method'] !== strtoupper($method)) {
                $allowedMethods[] = $route['method'];
                continue;
            }

            $this->runMiddleware($route['middleware']);

            $parameters = array_filter(
                $matches,
                static fn (int|string $key): bool => is_string($key),
                ARRAY_FILTER_USE_KEY
            );

            $this->invoke($route['action'], array_values($parameters));

            return;
        }

        if ($allowedMethods !== []) {
            $allowedMethods = array_values(array_unique($allowedMethods));
            header('Allow: ' . implode(', ', $allowedMethods));
            http_response_code(405);
            echo View::render('errors/405', ['allowed' => $allowedMethods], null);

            return;
        }

        http_response_code(404);
        echo View::render('errors/404', [], null);
    }

    private function runMiddleware(array $middleware): void
    {
        foreach ($middleware as $class) {
            $instance = new $class();

            if (!method_exists($instance, 'handle')) {
                throw new \RuntimeException('Middleware inválido: ' . $class);
            }

            $instance->handle();
        }
    }

    private function invoke(array|callable $action, array $parameters): void
    {
        if (is_array($action)) {
            [$class, $method] = $action;
            $controller = new $class();

            $controller->{$method}(...$parameters);

            return;
        }

        $action(...$parameters);
    }

    private function normalizeRoute(string $path): string
    {
        $path = '/' . trim($path, '/');

        return $path === '/' ? '/' : $path;
    }

    private function normalizeRequestPath(string $uri): string
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $basePath = parse_url((string) config('app.url', ''), PHP_URL_PATH) ?: '';

        if ($basePath !== '' && $basePath !== '/' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
        }

        $path = '/' . trim($path, '/');

        return $path === '/' ? '/' : $path;
    }
}
