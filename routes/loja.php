<?php

declare(strict_types=1);

use App\Controllers\Loja\EntradaController;
use App\Controllers\Loja\EstoqueController;
use App\Controllers\Loja\ItemImagemController;
use App\Controllers\Loja\PublicadoController;
use App\Core\Router;
use App\Middleware\AuthMiddleware;

return static function (Router $router): void {
    $auth = [AuthMiddleware::class];

    $router->get('/admin/loja', static function (): void {
        redirect('/admin/loja/entradas');
    }, $auth);

    $router->get('/admin/loja/entradas', [EntradaController::class, 'index'], $auth);
    $router->post('/admin/loja/entradas', [EntradaController::class, 'store'], $auth);
    $router->get('/admin/loja/entradas/{id}', [EntradaController::class, 'show'], $auth);
    $router->post('/admin/loja/entradas/{id}', [EntradaController::class, 'update'], $auth);
    $router->post('/admin/loja/entradas/{id}/recebido', [EntradaController::class, 'toggleReceived'], $auth);
    $router->post('/admin/loja/entradas/{id}/excluir', [EntradaController::class, 'destroy'], $auth);
    $router->post('/admin/loja/entradas/{id}/custos', [EntradaController::class, 'addCost'], $auth);
    $router->post('/admin/loja/entradas/{id}/custos/{custoId}/excluir', [EntradaController::class, 'deleteCost'], $auth);
    $router->post('/admin/loja/entradas/{id}/itens', [EntradaController::class, 'addItem'], $auth);
    $router->post('/admin/loja/entradas/{id}/itens/{itemId}/atualizar', [EntradaController::class, 'updateItem'], $auth);
    $router->get('/admin/loja/itens/imagem/{id}', [ItemImagemController::class, 'show'], $auth);
    $router->post('/admin/loja/itens/{itemId}/excluir', [EntradaController::class, 'deleteItem'], $auth);
    $router->post('/admin/loja/itens/{itemId}/fornecedores', [EntradaController::class, 'addOffer'], $auth);
    $router->post('/admin/loja/itens/{itemId}/fornecedores/{ofertaId}/selecionar', [EntradaController::class, 'selectOffer'], $auth);
    $router->post('/admin/loja/itens/{itemId}/fornecedores/{ofertaId}/excluir', [EntradaController::class, 'deleteOffer'], $auth);

    $router->get('/admin/loja/estoque/publicados', [PublicadoController::class, 'index'], $auth);
    $router->post('/admin/loja/estoque/publicados', [PublicadoController::class, 'store'], $auth);
    $router->post('/admin/loja/estoque/publicados/{itemId}/publicar/telegram', [PublicadoController::class, 'publicarTelegram'], $auth);
    $router->post('/admin/loja/estoque/publicados/{itemId}/publicar/loja', [PublicadoController::class, 'publicarLoja'], $auth);
    $router->post('/admin/loja/estoque/publicados/{id}/excluir', [PublicadoController::class, 'destroy'], $auth);

    $router->post('/admin/loja/estoque/demanda', [EstoqueController::class, 'storeDemanda'], $auth);

    $router->get('/admin/loja/estoque', [EstoqueController::class, 'index'], $auth);
};
