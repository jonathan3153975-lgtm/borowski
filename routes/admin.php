<?php

declare(strict_types=1);

use App\Controllers\DashboardController;
use App\Controllers\UserController;
use App\Core\Router;
use App\Middleware\AuthMiddleware;

return static function (Router $router): void {
    $router->get('/admin', [DashboardController::class, 'index'], [AuthMiddleware::class]);

    $router->get('/admin/usuarios', [UserController::class, 'index'], [AuthMiddleware::class]);
    $router->get('/admin/usuarios/criar', [UserController::class, 'create'], [AuthMiddleware::class]);
    $router->post('/admin/usuarios', [UserController::class, 'store'], [AuthMiddleware::class]);
    $router->get('/admin/usuarios/{id}/editar', [UserController::class, 'edit'], [AuthMiddleware::class]);
    $router->post('/admin/usuarios/{id}/editar', [UserController::class, 'update'], [AuthMiddleware::class]);
    $router->post('/admin/usuarios/{id}/status', [UserController::class, 'status'], [AuthMiddleware::class]);
    $router->post('/admin/usuarios/{id}/excluir', [UserController::class, 'destroy'], [AuthMiddleware::class]);
};
