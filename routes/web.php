<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\Loja\ItemImagemController;
use App\Controllers\VitrineController;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;
use App\Services\ConfiguracaoAppService;
use App\Services\Loja\MercadoPagoService;
use App\Services\Loja\TelegramService;

return static function (Router $router): void {
    $router->get('/', static function (): void {
        redirect('/loja');
    });

    // Loja virtual publica (sem autenticacao)
    $router->get('/loja', [VitrineController::class, 'index']);
    $router->get('/loja/oferta/{id}', [VitrineController::class, 'oferta']);
    $router->get('/loja/item/{id}', [VitrineController::class, 'item']);
    $router->get('/loja/imagem/{id}', [ItemImagemController::class, 'show']);
    $router->get('/loja/carrinho', [VitrineController::class, 'carrinho']);
    $router->post('/loja/carrinho/adicionar', [VitrineController::class, 'adicionar']);
    $router->post('/loja/carrinho/atualizar', [VitrineController::class, 'atualizar']);
    $router->post('/loja/carrinho/remover', [VitrineController::class, 'remover']);
    $router->post('/loja/checkout/iniciar', [VitrineController::class, 'reservar']);
    $router->get('/loja/checkout', [VitrineController::class, 'checkoutForm']);
    $router->post('/loja/checkout', [VitrineController::class, 'checkout']);
    $router->get('/loja/checkout/retorno', [VitrineController::class, 'retorno']);
    $router->get('/loja/pedido/{codigo}', [VitrineController::class, 'pedido']);
    $router->post('/loja/pedido/{codigo}/pagar', [VitrineController::class, 'pagar']);

    $router->get('/login', [AuthController::class, 'showLogin'], [GuestMiddleware::class]);
    $router->post('/login', [AuthController::class, 'login'], [GuestMiddleware::class]);
    $router->post('/logout', [AuthController::class, 'logout'], [AuthMiddleware::class]);

    // Webhook do Telegram (sem autenticacao de sessao; valida o segredo da URL)
    $router->post('/telegram/webhook/{secret}', static function (string $secret): void {
        $expected = (new ConfiguracaoAppService())->get('telegram_webhook_secret');

        if ($expected === '' || !hash_equals($expected, $secret)) {
            http_response_code(403);
            echo 'Forbidden';

            return;
        }

        $payload = file_get_contents('php://input');
        $update = json_decode((string) $payload, true);

        if (is_array($update)) {
            (new TelegramService())->handleUpdate($update);
        }

        header('Content-Type: application/json; charset=utf-8');
        echo '{"ok":true}';
    });

    // Webhook do Mercado Pago (sem autenticacao de sessao; valida via API usando o token)
    $mercadopagoWebhook = static function (): void {
        $payload = json_decode((string) file_get_contents('php://input'), true);
        $payload = is_array($payload) ? $payload : [];
        $type = (string) ($payload['type'] ?? $_GET['type'] ?? $_GET['topic'] ?? '');
        $paymentId = $payload['data']['id']
            ?? ($_GET['data']['id'] ?? ($_GET['data.id'] ?? ($_GET['id'] ?? null)));

        if ($paymentId !== null && ($type === '' || $type === 'payment')) {
            (new MercadoPagoService())->processarPagamentoPorId((string) $paymentId);
        }

        header('Content-Type: application/json; charset=utf-8');
        echo '{"ok":true}';
    };

    $router->post('/mercadopago/webhook', $mercadopagoWebhook);
    $router->get('/mercadopago/webhook', $mercadopagoWebhook);
};
