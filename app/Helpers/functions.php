<?php

declare(strict_types=1);

use App\Core\View;

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);

        if ($value === false) {
            $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
        }

        if ($value === null) {
            return $default;
        }

        return $value;
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        static $loaded = [];

        $segments = explode('.', $key);
        $file = array_shift($segments);

        if (!array_key_exists($file, $loaded)) {
            $path = BASE_PATH . '/config/' . $file . '.php';
            $loaded[$file] = is_file($path) ? require $path : [];
        }

        $value = $loaded[$file];

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $base = (string) config('app.url', '');
        $path = ltrim($path, '/');

        return $path === '' ? $base : $base . '/' . $path;
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path): never
    {
        $location = preg_match('#^https?://#i', $path) === 1 ? $path : url($path);

        header('Location: ' . $location);
        exit;
    }
}

if (!function_exists('flash')) {
    function flash(string $key, mixed $value = null): mixed
    {
        if (func_num_args() > 1) {
            $_SESSION['_flash'][$key] = $value;

            return null;
        }

        $message = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);

        return $message;
    }
}

if (!function_exists('flash_input')) {
    function flash_input(array $data): void
    {
        $_SESSION['_old'] = $data;
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = null): mixed
    {
        return $_SESSION['_old'][$key] ?? $default;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('verify_csrf')) {
    function verify_csrf(?string $token): bool
    {
        return is_string($token)
            && $token !== ''
            && isset($_SESSION['_csrf_token'])
            && hash_equals($_SESSION['_csrf_token'], $token);
    }
}

if (!function_exists('auth_user')) {
    function auth_user(): ?array
    {
        return $_SESSION['auth_user'] ?? null;
    }
}

if (!function_exists('auth_check')) {
    function auth_check(): bool
    {
        return isset($_SESSION['auth_user']['id']);
    }
}

if (!function_exists('is_super_admin')) {
    function is_super_admin(): bool
    {
        return (auth_user()['tipo'] ?? null) === 'super_admin';
    }
}

if (!function_exists('login_user')) {
    function login_user(array $user): void
    {
        session_regenerate_id(true);

        $_SESSION['auth_user'] = [
            'id' => (int) $user['id'],
            'nome' => (string) $user['nome'],
            'email' => (string) $user['email'],
            'tipo' => (string) $user['tipo'],
        ];

        unset($_SESSION['_csrf_token']);
    }
}

if (!function_exists('logout_user')) {
    function logout_user(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }
}

if (!function_exists('client_ip')) {
    function client_ip(): ?string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;

        if (!is_string($ip) || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return null;
        }

        return $ip;
    }
}

if (!function_exists('format_datetime')) {
    function format_datetime(?string $value, string $format = 'd/m/Y H:i'): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        try {
            return (new DateTimeImmutable($value))->format($format);
        } catch (Throwable) {
            return '—';
        }
    }
}

if (!function_exists('format_money')) {
    function format_money(mixed $value): string
    {
        return 'R$ ' . number_format((float) $value, 2, ',', '.');
    }
}

if (!function_exists('loja_mode_label')) {
    function loja_mode_label(string $mode): string
    {
        return $mode === 'previsao' ? 'Previsão de compra' : 'Compra direta';
    }
}

if (!function_exists('loja_status_label')) {
    function loja_status_label(string $status): string
    {
        return match ($status) {
            'disponivel' => 'Disponível em estoque',
            'em_transporte' => 'Em transporte',
            'pre_venda' => 'Em pré-venda',
            default => '—',
        };
    }
}

if (!function_exists('loja_status_class')) {
    function loja_status_class(string $status): string
    {
        return match ($status) {
            'disponivel' => 'status-pill-active',
            'em_transporte' => 'status-pill-transit',
            'pre_venda' => 'status-pill-pre',
            default => '',
        };
    }
}

if (!function_exists('current_path')) {
    function current_path(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $basePath = parse_url((string) config('app.url', ''), PHP_URL_PATH) ?: '';

        if ($basePath !== '' && $basePath !== '/' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
        }

        $path = '/' . trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }
}

if (!function_exists('view')) {
    function view(string $name, array $data = [], ?string $layout = null): string
    {
        return View::render($name, $data, $layout);
    }
}
