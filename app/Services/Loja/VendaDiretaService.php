<?php

declare(strict_types=1);

namespace App\Services\Loja;

use App\Core\Database;
use App\Models\Loja\ItemEstoque;
use App\Models\Loja\ItemPublicado;
use App\Models\Loja\PedidoOnline;
use App\Models\Loja\Reserva;
use InvalidArgumentException;
use PDO;
use Throwable;

class VendaDiretaService
{
    private PDO $db;
    private ItemEstoque $itens;
    private ItemPublicado $publicacoes;
    private PedidoOnline $pedidos;
    private Reserva $reservas;

    public function __construct()
    {
        $this->db = Database::connection();
        $this->itens = new ItemEstoque();
        $this->publicacoes = new ItemPublicado();
        $this->pedidos = new PedidoOnline();
        $this->reservas = new Reserva();
    }

    public function listarItens(): array
    {
        return $this->itens->listarParaVendaDireta();
    }

    /**
     * Cria uma venda presencial ja paga.
     *
     * @param array<int, array{quantidade: int, preco: mixed}> $selecionados
     */
    public function criar(array $dados, array $selecionados, array $actor): int
    {
        $linhas = [];
        $total = 0.0;

        foreach ($selecionados as $itemId => $info) {
            $itemId = (int) $itemId;
            $quantidade = max(0, (int) ($info['quantidade'] ?? 0));

            if ($itemId <= 0 || $quantidade <= 0) {
                continue;
            }

            $item = $this->itens->find($itemId);

            if ($item === null) {
                throw new InvalidArgumentException('Item não encontrado (#' . $itemId . ').');
            }

            $comprometido = $this->reservas->comprometidoPorItem($itemId);
            $disponivel = max(0, (int) $item['quantidade'] - $comprometido);

            if ($disponivel < $quantidade) {
                throw new InvalidArgumentException(
                    'Estoque insuficiente para "' . $item['nome'] . '" (' . $disponivel . ' disponível(is)).'
                );
            }

            $preco = $this->parseMoney($info['preco'] ?? null);

            if ($preco <= 0) {
                throw new InvalidArgumentException('Informe o preço de venda para "' . $item['nome'] . '".');
            }

            $subtotal = round($preco * $quantidade, 3);
            $total += $subtotal;

            $linhas[] = [
                'item' => $item,
                'quantidade' => $quantidade,
                'preco' => $preco,
                'subtotal' => $subtotal,
            ];
        }

        if ($linhas === []) {
            throw new InvalidArgumentException('Selecione ao menos um item para a venda.');
        }

        $cliente = trim((string) ($dados['cliente_nome'] ?? '')) ?: 'Venda presencial';
        $metodo = (string) ($dados['metodo_pagamento'] ?? 'dinheiro');

        if (!in_array($metodo, ['dinheiro', 'pix', 'cartao_credito', 'cartao_debito', 'transferencia', 'outro'], true)) {
            $metodo = 'dinheiro';
        }

        $entregarAgora = !empty($dados['entregar_agora']);
        $observacoes = trim((string) ($dados['observacoes'] ?? '')) ?: null;
        $pagoEm = date('Y-m-d H:i:s');
        $expira = date('Y-m-d H:i:s', time() + 3600);
        $canalLoja = $this->publicacoes->canalPorSlug('loja');
        $canalLojaId = $canalLoja !== null ? (int) $canalLoja['id'] : 1;

        $this->db->beginTransaction();

        try {
            $codigo = $this->pedidos->gerarCodigo();

            $pedidoId = $this->pedidos->create([
                'codigo' => $codigo,
                'cliente_nome' => $cliente,
                'entrega_modo' => 'retirada',
                'observacoes' => $observacoes,
                'subtotal' => $total,
                'frete' => 0,
                'total' => $total,
                'status' => 'pago',
                'origem' => 'presencial',
                'metodo_pagamento' => $metodo,
                'expira_em' => $expira,
            ]);

            $this->pedidos->marcarPago($pedidoId, $pagoEm);

            if ($entregarAgora) {
                $this->pedidos->marcarEntregue($pedidoId);
            }

            foreach ($linhas as $linha) {
                $this->pedidos->adicionarItem($pedidoId, [
                    'publicacao_id' => null,
                    'item_id' => (int) $linha['item']['id'],
                    'quantidade' => $linha['quantidade'],
                    'preco_unitario' => $linha['preco'],
                    'subtotal' => $linha['subtotal'],
                    'item_nome' => (string) $linha['item']['nome'],
                    'item_codigo' => $linha['item']['codigo'] ?? null,
                ]);

                $this->reservas->create([
                    'publicacao_id' => null,
                    'item_id' => (int) $linha['item']['id'],
                    'canal_id' => $canalLojaId,
                    'quantidade' => $linha['quantidade'],
                    'preco_venda' => number_format($linha['preco'], 3, '.', ''),
                    'valor_reserva' => null,
                    'status' => 'confirmada',
                    'metodo_pagamento' => $metodo,
                    'comprador_nome' => $cliente,
                    'pedido_id' => $pedidoId,
                    'expira_em' => $expira,
                ]);
            }

            $this->reservas->marcarPagoPorPedido($pedidoId, $pagoEm);
            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }

        return $pedidoId;
    }

    private function parseMoney(mixed $value): float
    {
        $digits = preg_replace('/\D/', '', (string) $value) ?? '';

        if ($digits === '') {
            return 0.0;
        }

        return ((int) $digits) / 1000;
    }
}
