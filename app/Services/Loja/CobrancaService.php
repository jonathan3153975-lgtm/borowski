<?php

declare(strict_types=1);

namespace App\Services\Loja;

use App\Models\Loja\Reserva;
use InvalidArgumentException;

class CobrancaService
{
    private Reserva $reservas;

    public function __construct()
    {
        $this->reservas = new Reserva();
    }

    /**
     * Gera o link do saldo restante e envia ao comprador no Telegram (se possível).
     *
     * @return array{link: string, enviado: bool}
     */
    public function cobrarSaldo(int $reservaId): array
    {
        $reserva = $this->reservas->findReserva($reservaId);

        if ($reserva === null) {
            throw new InvalidArgumentException('Pedido não encontrado.');
        }

        if ((string) $reserva['status'] !== 'confirmada') {
            throw new InvalidArgumentException('O pedido ainda não foi habilitado para cobrança.');
        }

        if (!empty($reserva['saldo_pago_em'])) {
            throw new InvalidArgumentException('O pedido já está totalmente pago.');
        }

        $valorReserva = (!empty($reserva['pago_em']) && $reserva['valor_reserva'] !== null)
            ? (float) $reserva['valor_reserva']
            : 0.0;
        $valor = (float) $reserva['preco_venda'] - $valorReserva;

        if ($valor <= 0) {
            throw new InvalidArgumentException('Não há valor pendente para cobrar.');
        }

        $mercadoPago = new MercadoPagoService();

        if (!$mercadoPago->ativo()) {
            throw new InvalidArgumentException('Mercado Pago não está ativo nas configurações.');
        }

        $link = $mercadoPago->criarLinkSaldo($reserva);

        if ($link === null) {
            throw new InvalidArgumentException(
                'Não foi possível gerar o link: ' . ($mercadoPago->ultimoErro() ?? 'erro')
            );
        }

        $this->reservas->updateSaldoLink($reservaId, $link, date('Y-m-d H:i:s'));

        $enviado = false;
        $chatId = (string) ($reserva['telegram_chat_id'] ?? '');

        if ($chatId !== '') {
            $texto = "Cobrança pendente\n\n"
                . '<b>' . e((string) $reserva['item_nome']) . "</b>\n"
                . 'Valor: <b>R$ ' . number_format($valor, 2, ',', '.') . "</b>\n\n"
                . 'Finalize o pagamento no botão abaixo.';

            $enviado = (new TelegramService())->sendMessage(
                $chatId,
                $texto,
                [[['text' => 'Pagar no Mercado Pago', 'url' => $link]]]
            ) !== null;
        }

        return ['link' => $link, 'enviado' => $enviado];
    }
}
