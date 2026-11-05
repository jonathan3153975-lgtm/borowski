<?php

declare(strict_types=1);

if (PHP_SAPI === 'cli-server') {
    $requested = realpath(__DIR__ . (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'));

    if (
        $requested !== false
        && str_starts_with($requested, __DIR__ . DIRECTORY_SEPARATOR)
        && is_file($requested)
    ) {
        return false;
    }
}

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

use App\Core\Router;
use App\Core\View;
use Dotenv\Dotenv;

Dotenv::createImmutable(BASE_PATH)->safeLoad();

date_default_timezone_set((string) config('app.timezone', 'America/Sao_Paulo'));

$debug = (bool) config('app.debug', false);

error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', BASE_PATH . '/storage/logs/app.log');

$session = (array) config('app.session', []);
$secureCookie = (bool) ($session['secure'] ?? false)
    || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

session_name((string) ($session['name'] ?? 'loja_carrinhos_session'));
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $secureCookie,
    'httponly' => true,
    'samesite' => 'Lax',
]);
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_start();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !verify_csrf($_POST['_token'] ?? null)) {
    http_response_code(419);
    echo View::render('errors/419', [], null);
    exit;
}

$router = new Router();

foreach (['web', 'admin', 'loja'] as $routeFile) {
    $registerRoutes = require BASE_PATH . '/routes/' . $routeFile . '.php';
    $registerRoutes($router);
}

try {
    $router->dispatch(
        $_SERVER['REQUEST_METHOD'] ?? 'GET',
        $_SERVER['REQUEST_URI'] ?? '/'
    );
} catch (Throwable $exception) {
    error_log($exception->getMessage() . ' em ' . $exception->getFile() . ':' . $exception->getLine());

    http_response_code(500);
    echo View::render('errors/500', [
        'debug' => $debug,
        'message' => $exception->getMessage(),
    ], null);
} finally {
    unset($_SESSION['_old']);
}
