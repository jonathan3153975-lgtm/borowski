<?php

declare(strict_types=1);

namespace App\Services\Loja;

use App\Core\Database;
use App\Models\Loja\ItemPublicado;
use App\Models\Loja\Reserva;
use App\Services\ConfiguracaoAppService;
use RuntimeException;
use Throwable;

class TelegramService
{
    private string $token;
    private string $groupChatId;
    private bool $ativo;
    private int $reservaMinutos;
    private ItemPublicado $publicacoes;
    private Reserva $reservas;
    private MercadoPagoService $mercadoPago;
    private ?string $ultimoErro = null;

    public function __construct()
    {
        $config = new ConfiguracaoAppService();

        $this->token = trim($config->get('telegram_bot_token'));
        $this->groupChatId = trim($config->get('telegram_group_chat_id'));
        $this->ativo = $config->get('telegram_ativo', '0') === '1';
        $this->reservaMinutos = max(1, (int) $config->get('telegram_reserva_minutos', '30'));

        $this->publicacoes = new ItemPublicado();
        $this->reservas = new Reserva();
        $this->mercadoPago = new MercadoPagoService();
    }

    public function configurado(): bool
    {
        return $this->token !== '' && $this->groupChatId !== '';
    }

    public function ultimoErro(): ?string
    {
        return $this->ultimoErro;
    }

    public function testarConexao(): ?array
    {
        return $this->api('getMe');
    }

    public function getUpdates(int $offset): array
    {
        $result = $this->api('getUpdates', [
            'offset' => $offset,
            'timeout' => 0,
            'allowed_updates' => ['callback_query', 'message'],
        ]);

        return is_array($result) ? $result : [];
    }

    public function deleteWebhook(): bool
    {
        return $this->api('deleteWebhook', ['drop_pending_updates' => false]) !== null;
    }

    public function setWebhook(string $url): bool
    {
        return $this->api('setWebhook', [
            'url' => $url,
            'allowed_updates' => ['callback_query', 'message'],
        ]) !== null;
    }

    public function webhookInfo(): ?array
    {
        return $this->api('getWebhookInfo');
    }

    /**
     * Lista os chats que interagiram recentemente com o bot (para descobrir o ID do grupo).
     * Requer que o webhook esteja desativado.
     */
    public function descobrirChats(): array
    {
        $updates = $this->api('getUpdates', [
            'offset' => 0,
            'limit' => 100,
            'timeout' => 0,
            'allowed_updates' => ['message', 'edited_message', 'channel_post', 'my_chat_member', 'chat_member'],
        ]);

        if (!is_array($updates)) {
            return [];
        }

        $chats = [];

        foreach ($updates as $update) {
            foreach (['message', 'edited_message', 'channel_post', 'my_chat_member', 'chat_member'] as $key) {
                $chat = $update[$key]['chat'] ?? null;

                if (!is_array($chat) || !isset($chat['id'])) {
                    continue;
                }

                $id = (string) $chat['id'];
                $chats[$id] = [
                    'id' => $id,
                    'tipo' => (string) ($chat['type'] ?? ''),
                    'titulo' => (string) ($chat['title'] ?? $chat['username'] ?? $chat['first_name'] ?? ''),
                ];
            }
        }

        return array_values($chats);
    }

    /**
     * Publica um item no grupo do Telegram com botao de compra.
     */
    public function publicarNoGrupo(int $publicacaoId): void
    {
        if (!$this->ativo) {
            throw new RuntimeException('A venda pelo Telegram está desativada nas configurações.');
        }

        if (!$this->configurado()) {
            throw new RuntimeException('Configure o token do bot e o grupo do Telegram.');
        }

        $publicacao = $this->publicacoes->findPublicacao($publicacaoId);

        if ($publicacao === null) {
            throw new RuntimeException('Publicação não encontrada.');
        }

        $texto = $this->textoAnuncio($publicacao);
        $teclado = [[['text' => '🛒 Comprar', 'callback_data' => 'buy:' . $publicacaoId]]];
        $foto = $this->caminhoImagem($publicacao['imagem_caminho'] ?? null);

        $enviado = $foto !== null
            ? $this->sendPhoto($this->groupChatId, $foto, $texto, $teclado)
            : $this->sendMessage($this->groupChatId, $texto, $teclado);

        if ($enviado === null) {
            $detalhe = $this->ultimoErro ?? 'Verifique o bot, o grupo e a conexão.';

            if (stripos($detalhe, 'chat not found') !== false) {
                $detalhe .= ' Verifique o ID do grupo e se o bot foi adicionado ao grupo.';
            }

            throw new RuntimeException('Não foi possível enviar a publicação ao Telegram. ' . $detalhe);
        }
    }

    public function handleUpdate(array $update): void
    {
        $tipo = isset($update['callback_query']) ? 'callback'
            : (isset($update['message']) ? 'message' : 'outro');
        $dados = (string) ($update['callback_query']['data'] ?? '');

        error_log('[telegram] update ' . (string) ($update['update_id'] ?? '?')
            . ' tipo=' . $tipo . ($dados !== '' ? ' data=' . $dados : ''));

        if (isset($update['callback_query']) && is_array($update['callback_query'])) {
            $this->handleCallback($update['callback_query']);

            return;
        }

        if (isset($update['message']) && is_array($update['message'])) {
            $this->handleMessage($update['message']);
        }
    }

    private function handleCallback(array $callback): void
    {
        $callbackId = (string) ($callback['id'] ?? '');
        $data = (string) ($callback['data'] ?? '');
        $from = (array) ($callback['from'] ?? []);

        if (str_starts_with($data, 'buy:')) {
            $this->handleBuy((int) substr($data, 4), $from, $callbackId);

            return;
        }

        if (str_starts_with($data, 'mp:')) {
            $this->handlePayment((int) substr($data, 3), 'mercadopago', $from, $callbackId);

            return;
        }

        $this->answerCallbackQuery($callbackId, 'Ação não reconhecida.');
    }

    private function handleMessage(array $message): void
    {
        $text = trim((string) ($message['text'] ?? ''));
        $chatId = (string) ($message['chat']['id'] ?? '');

        if ($chatId === '') {
            return;
        }

        if (str_starts_with($text, '/start') || str_starts_with($text, '/ajuda')) {
            $this->sendMessage(
                $chatId,
                "Olá! Aqui você recebe os detalhes e as opções de pagamento dos itens reservados.\n"
                . 'Use o botão "Comprar" na publicação do grupo para reservar um item.'
            );
        }
    }

    private function handleBuy(int $publicacaoId, array $from, string $callbackId): void
    {
        $chatId = (string) ($from['id'] ?? '');

        if ($chatId === '') {
            $this->answerCallbackQuery($callbackId, 'Não foi possível identificar o usuário.');

            return;
        }

        $db = Database::connection();
        $db->beginTransaction();

        try {
            $statement = $db->prepare(
                "SELECT p.*, i.status AS item_status
                 FROM loja_itens_publicados p
                 INNER JOIN loja_itens i ON i.id = p.item_id
                 WHERE p.id = :id AND p.status = 'publicado' FOR UPDATE"
            );
            $statement->execute(['id' => $publicacaoId]);
            $publicacao = $statement->fetch();

            if ($publicacao === false) {
                $db->rollBack();
                error_log('[telegram] buy: publicacao ' . $publicacaoId . ' indisponivel');
                $this->answerCallbackQuery($callbackId, 'Este item não está mais disponível.', true);

                return;
            }

            (new VisitaService())->registrar((int) $publicacao['item_id'], $publicacaoId, 'telegram', (int) $chatId);

            $reservadas = $this->reservas->reservadasAtivas($publicacaoId);
            $disponivel = (int) $publicacao['quantidade'] - $reservadas;

            if ($disponivel <= 0) {
                $db->rollBack();
                error_log('[telegram] buy: publicacao ' . $publicacaoId . ' esgotada');
                $this->answerCallbackQuery($callbackId, 'Item esgotado. Nenhuma unidade disponível.', true);

                return;
            }

            $nome = trim((string) ($from['first_name'] ?? '') . ' ' . (string) ($from['last_name'] ?? ''));
            $expira = date('Y-m-d H:i:s', time() + ($this->reservaMinutos * 60));

            $reservaId = $this->reservas->create([
                'publicacao_id' => $publicacaoId,
                'item_id' => (int) $publicacao['item_id'],
                'canal_id' => (int) $publicacao['canal_id'],
                'quantidade' => 1,
                'preco_venda' => $publicacao['preco_venda'],
                'valor_reserva' => ($publicacao['item_status'] ?? '') === 'pre_venda'
                    ? ($publicacao['preco_reserva'] ?? null)
                    : null,
                'status' => 'reservada',
                'telegram_user_id' => (int) $chatId,
                'telegram_chat_id' => (int) $chatId,
                'comprador_nome' => $nome !== '' ? mb_substr($nome, 0, 180) : null,
                'expira_em' => $expira,
            ]);

            $db->commit();
            error_log('[telegram] reserva ' . $reservaId . ' criada (publicacao ' . $publicacaoId . ', usuario ' . $chatId . ')');
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            error_log('Falha ao reservar item: ' . $exception->getMessage());
            $this->answerCallbackQuery($callbackId, 'Não foi possível reservar o item agora. Tente novamente.', true);

            return;
        }

        $reserva = $this->reservas->findReserva($reservaId);

        if ($reserva === null) {
            $this->answerCallbackQuery($callbackId, 'Reserva criada, mas não foi possível carregar os dados.');

            return;
        }

        $texto = "Você está comprando:\n\n"
            . '<b>' . $this->esc((string) $reserva['item_nome']) . '</b>'
            . ($reserva['item_codigo'] ? "\nSKU: " . $this->esc((string) $reserva['item_codigo']) : '')
            . "\nValor: <b>" . $this->money($reserva['preco_venda']) . '</b>'
            . ($reserva['valor_reserva'] !== null
                ? "\nReserva (pago agora): <b>" . $this->money($reserva['valor_reserva']) . '</b>'
                    . "\nRestante na entrega: <b>"
                    . $this->money((float) $reserva['preco_venda'] - (float) $reserva['valor_reserva'])
                    . '</b>'
                : '')
            . "\n\nSua reserva fica válida por {$this->reservaMinutos} minutos."
            . "\n\nToque no botão para pagar com Mercado Pago.";

        $teclado = [[
            ['text' => 'Pagar com Mercado Pago', 'callback_data' => 'mp:' . $reservaId],
        ]];

        $enviado = $this->sendMessage($chatId, $texto, $teclado);

        if ($enviado === null) {
            $this->reservas->updateStatus($reservaId, 'cancelada');
            error_log('[telegram] DM falhou para usuario ' . $chatId . ': ' . ($this->ultimoErro ?? 'erro desconhecido'));
            $this->answerCallbackQuery(
                $callbackId,
                'Inicie uma conversa com o bot no privado e clique em Comprar novamente.',
                true
            );

            return;
        }

        error_log('[telegram] DM enviada para usuario ' . $chatId . ' (reserva ' . $reservaId . ')');
        $this->answerCallbackQuery($callbackId, 'Reserva criada! Veja a mensagem no privado.');
    }

    private function handlePayment(int $reservaId, string $metodo, array $from, string $callbackId): void
    {
        $reserva = $this->reservas->findReserva($reservaId);
        $chatId = (string) ($from['id'] ?? '');

        error_log(
            '[telegram] pagamento reserva=' . $reservaId . ' encontrada='
            . ($reserva === null ? 'nao' : (string) $reserva['status'])
            . ' usuario=' . $chatId
        );

        if ($reserva === null) {
            $this->answerCallbackQuery(
                $callbackId,
                'Reserva não encontrada. Clique em Comprar novamente na publicação.',
                true
            );

            return;
        }

        $status = (string) $reserva['status'];
        $expirada = strtotime((string) $reserva['expira_em']) <= time();

        if ($status === 'confirmada') {
            $this->answerCallbackQuery($callbackId, 'Este pedido já está confirmado.', true);

            return;
        }

        if ($status !== 'reservada' || $expirada) {
            $reservaAnterior = $reservaId;
            $renovada = $this->renovarReserva($reserva, $from);

            if ($renovada === null) {
                $this->answerCallbackQuery(
                    $callbackId,
                    'Reserva expirada ou encerrada. Clique em Comprar novamente na publicação.',
                    true
                );

                return;
            }

            $reserva = $renovada;
            $reservaId = (int) $reserva['id'];
            error_log('[telegram] reserva renovada ' . $reservaId . ' (anterior ' . $reservaAnterior . ')');
        }

        if ((string) $reserva['telegram_user_id'] !== '' && (int) $reserva['telegram_user_id'] !== (int) $chatId) {
            $this->answerCallbackQuery($callbackId, 'Esta reserva pertence a outro usuário.', true);

            return;
        }

        $this->reservas->updateMetodo($reservaId, 'mercadopago');

        $link = $this->mercadoPago->criarLinkPagamento($reserva);

        if ($link === null) {
            error_log('[telegram] MP falhou reserva ' . $reservaId . ': ' . ($this->mercadoPago->ultimoErro() ?? 'erro'));
            $this->sendMessage(
                $chatId,
                'Não foi possível gerar o pagamento no Mercado Pago. '
                    . ($this->mercadoPago->ultimoErro() ?? 'Verifique a configuração.')
            );
            $this->answerCallbackQuery($callbackId, 'Falha ao gerar o pagamento.', true);

            return;
        }

        $valor = $reserva['valor_reserva'] !== null ? $reserva['valor_reserva'] : $reserva['preco_venda'];

        $texto = "Pagamento via <b>Mercado Pago</b>\n\n"
            . 'Item: ' . $this->esc((string) $reserva['item_nome']) . "\n"
            . 'Valor: <b>' . $this->money($valor) . "</b>\n\n"
            . 'Clique no botão abaixo para pagar.';

        $this->sendMessage(
            $chatId,
            $texto,
            [[['text' => 'Pagar no Mercado Pago', 'url' => $link]]]
        );
        $this->answerCallbackQuery($callbackId, 'Link de pagamento enviado.');
    }

    private function renovarReserva(array $reserva, array $from): ?array
    {
        $publicacaoId = (int) ($reserva['publicacao_id'] ?? 0);

        if ($publicacaoId <= 0) {
            return null;
        }

        $chatId = (string) ($from['id'] ?? '');

        if ($chatId === '') {
            return null;
        }

        $db = Database::connection();
        $db->beginTransaction();

        try {
            $statement = $db->prepare(
                "SELECT p.*, i.status AS item_status
                 FROM loja_itens_publicados p
                 INNER JOIN loja_itens i ON i.id = p.item_id
                 WHERE p.id = :id AND p.status = 'publicado' FOR UPDATE"
            );
            $statement->execute(['id' => $publicacaoId]);
            $publicacao = $statement->fetch();

            if ($publicacao === false) {
                $db->rollBack();

                return null;
            }

            $reservadas = $this->reservas->reservadasAtivas($publicacaoId);

            if ((int) $publicacao['quantidade'] - $reservadas <= 0) {
                $db->rollBack();

                return null;
            }

            $nome = trim((string) ($from['first_name'] ?? '') . ' ' . (string) ($from['last_name'] ?? ''));
            $expira = date('Y-m-d H:i:s', time() + ($this->reservaMinutos * 60));

            $novoId = $this->reservas->create([
                'publicacao_id' => $publicacaoId,
                'item_id' => (int) $publicacao['item_id'],
                'canal_id' => (int) $publicacao['canal_id'],
                'quantidade' => 1,
                'preco_venda' => $publicacao['preco_venda'],
                'valor_reserva' => ($publicacao['item_status'] ?? '') === 'pre_venda'
                    ? ($publicacao['preco_reserva'] ?? null)
                    : null,
                'status' => 'reservada',
                'telegram_user_id' => (int) $chatId,
                'telegram_chat_id' => (int) $chatId,
                'comprador_nome' => $nome !== '' ? mb_substr($nome, 0, 180) : null,
                'expira_em' => $expira,
            ]);

            $db->commit();
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            error_log('[telegram] falha ao renovar reserva: ' . $exception->getMessage());

            return null;
        }

        $this->reservas->updateStatus((int) $reserva['id'], 'expirada');

        return $this->reservas->findReserva($novoId);
    }


    private function textoAnuncio(array $publicacao): string
    {
        $linhas = ['<b>' . $this->esc((string) $publicacao['item_nome']) . '</b>'];

        if (!empty($publicacao['item_codigo'])) {
            $linhas[] = 'SKU: ' . $this->esc((string) $publicacao['item_codigo']);
        }

        if (!empty($publicacao['item_descricao'])) {
            $linhas[] = '';
            $linhas[] = $this->esc((string) $publicacao['item_descricao']);
        }

        $linhas[] = '';
        $linhas[] = 'Valor: <b>' . $this->money($publicacao['preco_venda']) . '</b>';

        if (isset($publicacao['preco_reserva']) && $publicacao['preco_reserva'] !== null) {
            $linhas[] = 'Reserva (pago agora): <b>' . $this->money($publicacao['preco_reserva']) . '</b>';
            $linhas[] = 'Restante na entrega: <b>'
                . $this->money((float) $publicacao['preco_venda'] - (float) $publicacao['preco_reserva'])
                . '</b>';
        }

        $linhas[] = 'Disponível: ' . (int) $publicacao['quantidade'] . ' un.';

        return implode("\n", $linhas);
    }

    private function caminhoImagem(?string $caminhoRelativo): ?string
    {
        if ($caminhoRelativo === null || trim($caminhoRelativo) === '') {
            return null;
        }

        $base = (string) config('app.uploads.base_path', BASE_PATH . '/storage/uploads');
        $arquivo = rtrim($base, '/\\') . DIRECTORY_SEPARATOR . ltrim($caminhoRelativo, '/\\');

        return is_file($arquivo) ? $arquivo : null;
    }

    public function notificarPagamentoConfirmado(int $reservaId): void
    {
        $reserva = $this->reservas->findReserva($reservaId);

        if ($reserva === null || (string) ($reserva['canal_slug'] ?? '') !== 'telegram') {
            return;
        }

        $chatId = (string) ($reserva['telegram_chat_id'] ?? '');

        if ($chatId === '' && !empty($reserva['telegram_user_id'])) {
            $chatId = (string) $reserva['telegram_user_id'];
        }

        if ($chatId === '') {
            return;
        }

        $total = (float) ($reserva['preco_venda'] ?? 0);
        $valorReserva = $reserva['valor_reserva'] !== null ? (float) $reserva['valor_reserva'] : null;
        $pagoAgora = $valorReserva ?? $total;

        $texto = "Pagamento confirmado!\n\n"
            . '<b>' . $this->esc((string) ($reserva['item_nome'] ?? 'Item')) . "</b>\n"
            . 'Valor pago: <b>' . $this->money($pagoAgora) . "</b>\n";

        if ($valorReserva !== null) {
            $restante = max(0, $total - $valorReserva);

            $texto .= $restante > 0
                ? 'Restante: <b>' . $this->money($restante) . "</b>\n\nO restante será cobrado na entrega."
                : 'Pedido totalmente pago.';
        } else {
            $texto .= 'Pedido totalmente pago.';
        }

        $this->sendMessage($chatId, $texto);
    }

    public function notificarSaldoConfirmado(int $reservaId): void
    {
        $reserva = $this->reservas->findReserva($reservaId);

        if ($reserva === null || (string) ($reserva['canal_slug'] ?? '') !== 'telegram') {
            return;
        }

        $chatId = (string) ($reserva['telegram_chat_id'] ?? '');

        if ($chatId === '' && !empty($reserva['telegram_user_id'])) {
            $chatId = (string) $reserva['telegram_user_id'];
        }

        if ($chatId === '') {
            return;
        }

        $texto = "Pagamento confirmado!\n\n"
            . '<b>' . $this->esc((string) ($reserva['item_nome'] ?? 'Item')) . "</b>\n"
            . 'Valor total: <b>' . $this->money((float) ($reserva['preco_venda'] ?? 0)) . "</b>\n\n"
            . 'Pedido totalmente pago. Obrigado!';

        $this->sendMessage($chatId, $texto);
    }

    public function notificarEntregaFinalizada(int $entregaId): void
    {
        $db = Database::connection();

        $statement = $db->prepare(
            "SELECT e.id, e.codigo_rastreio, c.telegram_user_id
             FROM loja_entregas e
             INNER JOIN loja_clientes_entrega c ON c.id = e.cliente_id
             WHERE e.id = :id
             LIMIT 1"
        );
        $statement->execute(['id' => $entregaId]);
        $entrega = $statement->fetch();

        if ($entrega === false || (int) ($entrega['telegram_user_id'] ?? 0) <= 0) {
            return;
        }

        $statement = $db->prepare(
            "SELECT COUNT(*) FROM loja_entrega_itens ei
             INNER JOIN loja_reservas r ON r.id = ei.reserva_id
             INNER JOIN loja_canais c ON c.id = r.canal_id
             WHERE ei.entrega_id = :id AND c.slug = 'telegram'"
        );
        $statement->execute(['id' => $entregaId]);

        if ((int) $statement->fetchColumn() <= 0) {
            return;
        }

        $texto = "Entrega finalizada!\n\n"
            . 'Sua entrega <b>#' . (int) $entrega['id'] . "</b> foi concluída.\n";

        if (!empty($entrega['codigo_rastreio'])) {
            $texto .= 'Rastreio: <b>' . $this->esc((string) $entrega['codigo_rastreio']) . "</b>\n";
        }

        $texto .= 'Obrigado pela compra!';

        $this->sendMessage((string) $entrega['telegram_user_id'], $texto);
    }

    public function sendMessage(string $chatId, string $text, ?array $keyboard = null): ?array
    {
        $params = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ];

        if ($keyboard !== null) {
            $params['reply_markup'] = ['inline_keyboard' => $keyboard];
        }

        return $this->api('sendMessage', $params);
    }

    public function sendPhoto(string $chatId, string $filePath, string $caption, ?array $keyboard = null): ?array
    {
        $params = [
            'chat_id' => $chatId,
            'caption' => $caption,
            'parse_mode' => 'HTML',
        ];

        if ($keyboard !== null) {
            $params['reply_markup'] = json_encode(['inline_keyboard' => $keyboard]);
        }

        return $this->api('sendPhoto', $params, $filePath);
    }

    private function answerCallbackQuery(string $callbackId, string $text, bool $alert = false): void
    {
        if ($callbackId === '') {
            return;
        }

        $this->api('answerCallbackQuery', [
            'callback_query_id' => $callbackId,
            'text' => $text,
            'show_alert' => $alert,
        ]);
    }

    private function api(string $method, array $params = [], ?string $photoPath = null): ?array
    {
        $this->ultimoErro = null;

        if ($this->token === '') {
            $this->ultimoErro = 'Token do bot não informado.';

            return null;
        }

        $url = 'https://api.telegram.org/bot' . $this->token . '/' . $method;
        $ch = curl_init($url);

        if ($ch === false) {
            $this->ultimoErro = 'Não foi possível inicializar o cURL.';

            return null;
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        // Ambientes com inspeção de TLS (proxy corporativo) precisam do store
        // de certificados do sistema para validar a cadeia corretamente.
        if (defined('CURLSSLOPT_NATIVE_CA')) {
            curl_setopt($ch, CURLOPT_SSL_OPTIONS, CURLSSLOPT_NATIVE_CA);
        }

        $caBundle = trim((string) env('TELEGRAM_CA_BUNDLE', ''));

        if ($caBundle !== '' && is_file($caBundle)) {
            curl_setopt($ch, CURLOPT_CAINFO, $caBundle);
        }

        if ($photoPath !== null) {
            $params['photo'] = new \CURLFile($photoPath);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        } else {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        }

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            $this->ultimoErro = 'Falha de conexão (cURL ' . $errno . '): ' . $error;
            error_log('Telegram cURL (' . $errno . '): ' . $error);

            return null;
        }

        $decoded = json_decode((string) $response, true);

        if (!is_array($decoded) || ($decoded['ok'] ?? false) !== true) {
            $descricao = is_array($decoded) ? (string) ($decoded['description'] ?? '') : '';
            $this->ultimoErro = 'Telegram respondeu HTTP ' . $status
                . ($descricao !== '' ? ': ' . $descricao : '.');
            error_log('Telegram API (' . $status . '): ' . $response);

            return null;
        }

        return is_array($decoded['result'] ?? null) ? $decoded['result'] : [];
    }

    private function money(mixed $value): string
    {
        return 'R$ ' . number_format((float) $value, 2, ',', '.');
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
