<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;

return static function (Router $router): void {
    $router->get('/', static function (): void {
        redirect(auth_check() ? '/admin' : '/login');
    });

    $router->get('/login', [AuthController::class, 'showLogin'], [GuestMiddleware::class]);
    $router->post('/login', [AuthController::class, 'login'], [GuestMiddleware::class]);
    $router->post('/logout', [AuthController::class, 'logout'], [AuthMiddleware::class]);
};
