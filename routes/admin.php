<?php

declare(strict_types=1);

use App\Controllers\CaixaController;
use App\Controllers\DashboardController;
use App\Controllers\EntregaController;
use App\Controllers\PedidoController;
use App\Controllers\UserController;
use App\Controllers\VendaController;
use App\Controllers\VendaDiretaController;
use App\Core\Router;
use App\Middleware\AuthMiddleware;

return static function (Router $router): void {
    $router->get('/admin', [DashboardController::class, 'index'], [AuthMiddleware::class]);

    $router->get('/admin/pedidos', [PedidoController::class, 'index'], [AuthMiddleware::class]);
    $router->post('/admin/pedidos/online/{id}/pagamento-entrega', [PedidoController::class, 'pagamentoEntregaPedido'], [AuthMiddleware::class]);
    $router->post('/admin/pedidos/online/{id}/verificar', [PedidoController::class, 'verificarPedido'], [AuthMiddleware::class]);
    $router->post('/admin/pedidos/{id}/pagamento-entrega', [PedidoController::class, 'pagamentoEntrega'], [AuthMiddleware::class]);
    $router->post('/admin/pedidos/{id}/verificar', [PedidoController::class, 'verificar'], [AuthMiddleware::class]);
    $router->post('/admin/pedidos/{id}/cobrar-restante', [PedidoController::class, 'cobrarRestante'], [AuthMiddleware::class]);

    $router->get('/admin/vendas', [VendaController::class, 'index'], [AuthMiddleware::class]);
    $router->get('/admin/vendas/direta', [VendaDiretaController::class, 'index'], [AuthMiddleware::class]);
    $router->post('/admin/vendas/direta', [VendaDiretaController::class, 'store'], [AuthMiddleware::class]);
    $router->get('/admin/vendas/cliente', [VendaController::class, 'cliente'], [AuthMiddleware::class]);
    $router->post('/admin/vendas/entregas', [VendaController::class, 'storeEntrega'], [AuthMiddleware::class]);
    $router->post('/admin/vendas/online/{id}/marcar-pago', [VendaController::class, 'marcarPagoPedido'], [AuthMiddleware::class]);
    $router->post('/admin/vendas/{id}/verificar', [VendaController::class, 'verificar'], [AuthMiddleware::class]);
    $router->post('/admin/vendas/{id}/cobrar-restante', [VendaController::class, 'cobrarRestante'], [AuthMiddleware::class]);
    $router->post('/admin/vendas/{id}/marcar-pago', [VendaController::class, 'marcarPago'], [AuthMiddleware::class]);

    $router->get('/admin/caixa', [CaixaController::class, 'index'], [AuthMiddleware::class]);

    $router->get('/admin/entregas', [EntregaController::class, 'index'], [AuthMiddleware::class]);
    $router->get('/admin/entregas/{id}/itens', [EntregaController::class, 'itens'], [AuthMiddleware::class]);
    $router->post('/admin/entregas/{id}/encaminhar', [EntregaController::class, 'encaminhar'], [AuthMiddleware::class]);
    $router->post('/admin/entregas/{id}/finalizar', [EntregaController::class, 'finalizar'], [AuthMiddleware::class]);

    $router->get('/admin/usuarios', [UserController::class, 'index'], [AuthMiddleware::class]);
    $router->get('/admin/usuarios/criar', [UserController::class, 'create'], [AuthMiddleware::class]);
    $router->post('/admin/usuarios', [UserController::class, 'store'], [AuthMiddleware::class]);
    $router->get('/admin/usuarios/{id}/editar', [UserController::class, 'edit'], [AuthMiddleware::class]);
    $router->post('/admin/usuarios/{id}/editar', [UserController::class, 'update'], [AuthMiddleware::class]);
    $router->post('/admin/usuarios/{id}/status', [UserController::class, 'status'], [AuthMiddleware::class]);
    $router->post('/admin/usuarios/{id}/excluir', [UserController::class, 'destroy'], [AuthMiddleware::class]);
};
