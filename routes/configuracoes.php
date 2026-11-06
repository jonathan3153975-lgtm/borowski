<?php

declare(strict_types=1);

use App\Controllers\ConfiguracaoController;
use App\Core\Router;
use App\Middleware\AuthMiddleware;

return static function (Router $router): void {
    $auth = [AuthMiddleware::class];

    $router->get('/admin/configuracoes', static function (): void {
        redirect('/admin/configuracoes/categorias');
    }, $auth);

    $router->get('/admin/configuracoes/{tipo}', [ConfiguracaoController::class, 'index'], $auth);
    $router->get('/admin/configuracoes/{tipo}/criar', [ConfiguracaoController::class, 'create'], $auth);
    $router->post('/admin/configuracoes/{tipo}/rapido', [ConfiguracaoController::class, 'quickStore'], $auth);
    $router->post('/admin/configuracoes/{tipo}', [ConfiguracaoController::class, 'store'], $auth);
    $router->get('/admin/configuracoes/{tipo}/{id}/editar', [ConfiguracaoController::class, 'edit'], $auth);
    $router->post('/admin/configuracoes/{tipo}/{id}', [ConfiguracaoController::class, 'update'], $auth);
    $router->post('/admin/configuracoes/{tipo}/{id}/status', [ConfiguracaoController::class, 'status'], $auth);
    $router->post('/admin/configuracoes/{tipo}/{id}/excluir', [ConfiguracaoController::class, 'destroy'], $auth);
};
