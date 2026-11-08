<?php

declare(strict_types=1);

namespace App\Services\Loja;

use App\Models\Loja\Reserva;
use App\Services\ConfiguracaoAppService;
use Throwable;

class MercadoPagoService
{
    private string $accessToken;
    private bool $ativo;
    private string $notificationUrl;
    private Reserva $reservas;
    private ?string $ultimoErro = null;

    public function __construct()
    {
        $config = new ConfiguracaoAppService();
        $this->accessToken = trim($config->get('mp_access_token'));
        $this->ativo = $config->get('mp_ativo', '0') === '1';
        $this->notificationUrl = rtrim((string) config('app.url'), '/') . '/mercadopago/webhook';
        $this->reservas = new Reserva();
    }

    public function configurado(): bool
    {
        return $this->accessToken !== '';
    }

    public function ativo(): bool
    {
        return $this->ativo && $this->configurado();
    }

    public function ultimoErro(): ?string
    {
        return $this->ultimoErro;
    }

    public function testarConexao(): ?array
    {
        if (!$this->configurado()) {
            $this->ultimoErro = 'Access token do Mercado Pago não informado.';

            return null;
        }

        return $this->request('GET', 'https://api.mercadopago.com/users/me');
    }

    /**
     * Cria uma preferencia de pagamento (Checkout Pro) e retorna o link.
     */
    public function criarLinkPagamento(array $reserva): ?string
    {
        if (!$this->ativo()) {
            $this->ultimoErro = 'Mercado Pago desativado ou não configurado.';

            return null;
        }

        $valor = $reserva['valor_reserva'] !== null
            ? (float) $reserva['valor_reserva']
            : (float) ($reserva['preco_venda'] ?? 0);

        if ($valor <= 0) {
            $this->ultimoErro = 'Valor de pagamento inválido.';

            return null;
        }

        $corpo = [
            'items' => [[
                'title' => (string) ($reserva['item_nome'] ?? 'Item'),
                'quantity' => max(1, (int) ($reserva['quantidade'] ?? 1)),
                'unit_price' => round($valor, 2),
                'currency_id' => 'BRL',
            ]],
            'external_reference' => 'reserva:' . (int) ($reserva['id'] ?? 0),
            'statement_descriptor' => 'LOJA CARRINHOS',
            'notification_url' => $this->notificationUrl,
        ];

        $resultado = $this->request('POST', 'https://api.mercadopago.com/checkout/preferences', $corpo);

        if ($resultado === null) {
            return null;
        }

        return $resultado['init_point']
            ?? $resultado['sandbox_init_point']
            ?? null;
    }

    /**
     * Cria uma preferencia de pagamento para um pedido da loja virtual.
     *
     * @return array{link: string, preference_id: ?string}|null
     */
    public function criarLinkPedido(array $pedido, array $itens): ?array
    {
        if (!$this->ativo()) {
            $this->ultimoErro = 'Mercado Pago desativado ou não configurado.';

            return null;
        }

        $mpItens = [];

        foreach ($itens as $item) {
            $valorReserva = isset($item['valor_reserva']) && $item['valor_reserva'] !== null
                ? (float) $item['valor_reserva']
                : null;
            $preco = round($valorReserva ?? (float) ($item['preco_unitario'] ?? 0), 2);

            if ($preco <= 0) {
                continue;
            }

            $mpItens[] = [
                'title' => (string) ($item['item_nome'] ?? 'Item'),
                'quantity' => max(1, (int) ($item['quantidade'] ?? 1)),
                'unit_price' => $preco,
                'currency_id' => 'BRL',
            ];
        }

        if ($mpItens === []) {
            $this->ultimoErro = 'Pedido sem itens válidos.';

            return null;
        }

        $base = rtrim((string) config('app.url'), '/');
        $retorno = $base . '/loja/checkout/retorno?pedido=' . rawurlencode((string) ($pedido['codigo'] ?? ''));

        $corpo = [
            'items' => $mpItens,
            'external_reference' => 'pedido_online:' . (int) ($pedido['id'] ?? 0),
            'statement_descriptor' => 'LOJA CARRINHOS',
            'notification_url' => $this->notificationUrl,
            'back_urls' => [
                'success' => $retorno,
                'pending' => $retorno,
                'failure' => $retorno,
            ],
        ];

        $email = trim((string) ($pedido['cliente_email'] ?? ''));

        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
            $corpo['payer'] = ['email' => $email];
        }

        $resultado = $this->request('POST', 'https://api.mercadopago.com/checkout/preferences', $corpo);

        if ($resultado === null) {
            return null;
        }

        $link = $resultado['init_point'] ?? $resultado['sandbox_init_point'] ?? null;

        if ($link === null) {
            $this->ultimoErro = 'O Mercado Pago não retornou um link de pagamento.';

            return null;
        }

        return [
            'link' => (string) $link,
            'preference_id' => isset($resultado['id']) ? (string) $resultado['id'] : null,
        ];
    }

    /**
     * Cria uma preferencia para o saldo restante de uma pre-venda.
     */
    public function criarLinkSaldo(array $reserva): ?string
    {
        if (!$this->ativo()) {
            $this->ultimoErro = 'Mercado Pago desativado ou não configurado.';

            return null;
        }

        $valorItem = (float) ($reserva['preco_venda'] ?? 0);
        $valorReserva = (!empty($reserva['pago_em']) && $reserva['valor_reserva'] !== null)
            ? (float) $reserva['valor_reserva']
            : 0.0;
        $saldo = round($valorItem - $valorReserva, 3);

        if ($saldo <= 0) {
            $this->ultimoErro = 'Não há saldo a cobrar.';

            return null;
        }

        $corpo = [
            'items' => [[
                'title' => (string) ($reserva['item_nome'] ?? 'Item') . ' - pagamento pendente',
                'quantity' => 1,
                'unit_price' => round($saldo, 2),
                'currency_id' => 'BRL',
            ]],
            'external_reference' => 'reserva:' . (int) ($reserva['id'] ?? 0) . ':saldo',
            'statement_descriptor' => 'LOJA CARRINHOS',
            'notification_url' => $this->notificationUrl,
        ];

        $resultado = $this->request('POST', 'https://api.mercadopago.com/checkout/preferences', $corpo);

        if ($resultado === null) {
            return null;
        }

        return $resultado['init_point']
            ?? $resultado['sandbox_init_point']
            ?? null;
    }

    public function buscarPagamento(string $paymentId): ?array
    {
        return $this->request(
            'GET',
            'https://api.mercadopago.com/v1/payments/' . rawurlencode($paymentId)
        );
    }

    public function buscarPagamentosPorReferencia(int $reservaId): array
    {
        $resultado = $this->request(
            'GET',
            'https://api.mercadopago.com/v1/payments/search?external_reference='
                . rawurlencode('reserva:' . $reservaId)
        );

        return is_array($resultado['results'] ?? null) ? $resultado['results'] : [];
    }

    public function buscarPagamentosPorSaldo(int $reservaId): array
    {
        $resultado = $this->request(
            'GET',
            'https://api.mercadopago.com/v1/payments/search?external_reference='
                . rawurlencode('reserva:' . $reservaId . ':saldo')
        );

        return is_array($resultado['results'] ?? null) ? $resultado['results'] : [];
    }

    private function escolherPagamento(array $pagamentos): ?array
    {
        $escolhido = null;

        foreach ($pagamentos as $pagamento) {
            if ((string) ($pagamento['status'] ?? '') === 'approved') {
                return $pagamento;
            }

            $dataAtual = strtotime((string) ($pagamento['date_created'] ?? ''));
            $dataEscolhida = $escolhido !== null ? strtotime((string) ($escolhido['date_created'] ?? '')) : false;

            if ($escolhido === null || ($dataAtual !== false && ($dataEscolhida === false || $dataAtual > $dataEscolhida))) {
                $escolhido = $pagamento;
            }
        }

        return $escolhido;
    }

    /**
     * Processa uma notificacao de pagamento (webhook) pelo id do pagamento.
     */
    public function processarPagamentoPorId(string $paymentId): ?array
    {
        $pagamento = $this->buscarPagamento($paymentId);

        if ($pagamento === null) {
            return null;
        }

        $referencia = (string) ($pagamento['external_reference'] ?? '');

        if (preg_match('/^pedido_online:(\d+)$/', $referencia, $partes) === 1) {
            (new VitrineService())->confirmarPagamento((int) $partes[1], $pagamento);
        } elseif (preg_match('/^reserva:(\d+):saldo$/', $referencia, $partes) === 1) {
            $this->aplicarSaldo((int) $partes[1], $pagamento);
        } elseif (preg_match('/^reserva:(\d+)$/', $referencia, $partes) === 1) {
            $this->aplicarPagamento((int) $partes[1], $pagamento);
        }

        return $pagamento;
    }

    private function aplicarSaldo(int $reservaId, array $pagamento): void
    {
        $mpStatus = (string) ($pagamento['status'] ?? '');
        $pagoEm = $mpStatus === 'approved' ? $this->dataPagamento($pagamento) : null;
        $antes = $this->reservas->findReserva($reservaId);
        $jaSaldoPago = $antes !== null && !empty($antes['saldo_pago_em']);

        $this->reservas->updateSaldo(
            $reservaId,
            $mpStatus,
            isset($pagamento['id']) ? (string) $pagamento['id'] : null,
            $pagoEm
        );

        if ($pagoEm !== null) {
            $this->reservas->marcarPagoTotal($reservaId, $pagoEm);

            if (!$jaSaldoPago) {
                try {
                    (new TelegramService())->notificarSaldoConfirmado($reservaId);
                } catch (Throwable $exception) {
                    error_log('[mercadopago] falha ao notificar saldo: ' . $exception->getMessage());
                }
            }
        }

        error_log('[mercadopago] saldo reserva ' . $reservaId . ' -> ' . ($pagoEm !== null ? 'pago' : $mpStatus));
    }

    /**
     * Consulta o(s) pagamento(s) de uma reserva e atualiza o pedido.
     */
    public function processarPagamentoDaReserva(int $reservaId): ?array
    {
        $escolhido = $this->escolherPagamento($this->buscarPagamentosPorReferencia($reservaId));

        if ($escolhido !== null) {
            $this->aplicarPagamento($reservaId, $escolhido);
        }

        return $escolhido;
    }

    public function processarPedidoOnline(int $pedidoId): ?array
    {
        $resultado = $this->request(
            'GET',
            'https://api.mercadopago.com/v1/payments/search?external_reference='
                . rawurlencode('pedido_online:' . $pedidoId)
        );

        $pagamentos = is_array($resultado['results'] ?? null) ? $resultado['results'] : [];
        $escolhido = $this->escolherPagamento($pagamentos);

        if ($escolhido !== null) {
            (new VitrineService())->confirmarPagamento($pedidoId, $escolhido);
        }

        return $escolhido;
    }

    public function processarSaldoDaReserva(int $reservaId): ?array
    {
        $escolhido = $this->escolherPagamento($this->buscarPagamentosPorSaldo($reservaId));

        if ($escolhido !== null) {
            $this->aplicarSaldo($reservaId, $escolhido);
        }

        return $escolhido;
    }

    private function aplicarPagamento(int $reservaId, array $pagamento): void
    {
        $mpStatus = (string) ($pagamento['status'] ?? '');
        $antes = $this->reservas->findReserva($reservaId);
        $jaConfirmada = $antes !== null && !empty($antes['pago_em']);

        $status = match ($mpStatus) {
            'approved' => 'confirmada',
            'rejected', 'cancelled', 'refunded', 'charged_back' => 'cancelada',
            default => 'reservada',
        };

        $pagoEm = $status === 'confirmada' ? $this->dataPagamento($pagamento) : null;

        $this->reservas->updatePagamento(
            $reservaId,
            $status,
            isset($pagamento['id']) ? (string) $pagamento['id'] : null,
            $mpStatus !== '' ? $mpStatus : null,
            $pagoEm
        );

        error_log('[mercadopago] reserva ' . $reservaId . ' -> ' . $status . ' (mp_status=' . $mpStatus . ')');

        if ($status === 'confirmada' && !$jaConfirmada) {
            try {
                (new TelegramService())->notificarPagamentoConfirmado($reservaId);
            } catch (Throwable $exception) {
                error_log('[mercadopago] falha ao notificar pagamento: ' . $exception->getMessage());
            }
        }
    }

    private function dataPagamento(array $pagamento): string
    {
        $data = (string) ($pagamento['date_approved'] ?? '');

        if ($data !== '') {
            $timestamp = strtotime($data);

            if ($timestamp !== false) {
                return date('Y-m-d H:i:s', $timestamp);
            }
        }

        return date('Y-m-d H:i:s');
    }

    private function request(string $metodo, string $url, ?array $corpo = null): ?array
    {
        $ch = curl_init($url);

        if ($ch === false) {
            $this->ultimoErro = 'Não foi possível inicializar o cURL.';

            return null;
        }

        $headers = [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json',
        ];

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if (defined('CURLSSLOPT_NATIVE_CA')) {
            curl_setopt($ch, CURLOPT_SSL_OPTIONS, CURLSSLOPT_NATIVE_CA);
        }

        $caBundle = trim((string) env('MERCADOPAGO_CA_BUNDLE', ''));

        if ($caBundle !== '' && is_file($caBundle)) {
            curl_setopt($ch, CURLOPT_CAINFO, $caBundle);
        }

        if ($metodo === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($corpo));
        }

        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            $this->ultimoErro = 'Falha de conexão (cURL ' . $errno . '): ' . $error;
            error_log('Mercado Pago cURL (' . $errno . '): ' . $error);

            return null;
        }

        $decoded = json_decode((string) $response, true);

        if ($status >= 400 || !is_array($decoded)) {
            $descricao = is_array($decoded)
                ? (string) ($decoded['message'] ?? json_encode($decoded))
                : substr((string) $response, 0, 200);
            $this->ultimoErro = 'Mercado Pago HTTP ' . $status . ': ' . $descricao;
            error_log('Mercado Pago API (' . $status . '): ' . $response);

            return null;
        }

        return $decoded;
    }
}
