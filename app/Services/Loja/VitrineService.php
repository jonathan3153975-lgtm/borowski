<?php

declare(strict_types=1);

namespace App\Services\Loja;

use App\Core\Database;
use App\Models\Loja\ItemImagem;
use App\Models\Loja\ItemPublicado;
use App\Models\Loja\PedidoOnline;
use App\Models\Loja\Reserva;
use InvalidArgumentException;
use PDO;
use Throwable;

class VitrineService
{
    private const RESERVA_MINUTOS = 30;

    private PDO $db;
    private ItemPublicado $publicacoes;
    private PedidoOnline $pedidos;
    private Reserva $reservas;

    public function __construct()
    {
        $this->db = Database::connection();
        $this->publicacoes = new ItemPublicado();
        $this->pedidos = new PedidoOnline();
        $this->reservas = new Reserva();
    }

    public function listarItens(string $busca = ''): array
    {
        return $this->publicacoes->listarVitrine($busca);
    }

    public function detalhePublicacao(int $publicacaoId): ?array
    {
        $publicacao = $this->publicacoes->findPublicacao($publicacaoId);

        if (
            $publicacao === null
            || (string) $publicacao['status'] !== 'publicado'
            || !in_array((string) ($publicacao['item_status'] ?? ''), ['disponivel', 'pre_venda'], true)
        ) {
            return null;
        }

        $imagens = (new ItemImagem())->listByItem((int) $publicacao['item_id']);
        $galeria = [];

        foreach ($imagens as $imagem) {
            $galeria[] = [
                'id' => (int) $imagem['id'],
                'url' => url('/loja/imagem/' . (int) $imagem['id']),
                'principal' => (int) $imagem['imagem_principal'] === 1,
            ];
        }

        return [
            'id' => (int) $publicacao['id'],
            'item_id' => (int) $publicacao['item_id'],
            'titulo' => trim((string) ($publicacao['titulo'] ?? '')) !== ''
                ? (string) $publicacao['titulo']
                : (string) $publicacao['item_nome'],
            'descricao' => trim((string) ($publicacao['descricao'] ?? '')) !== ''
                ? (string) $publicacao['descricao']
                : (string) ($publicacao['item_descricao'] ?? ''),
            'codigo' => $publicacao['item_codigo'] ?? null,
            'preco' => format_money($publicacao['preco_venda']),
            'preco_raw' => (float) $publicacao['preco_venda'],
            'preco_reserva' => $publicacao['preco_reserva'] !== null
                ? format_money($publicacao['preco_reserva'])
                : null,
            'preco_reserva_raw' => $publicacao['preco_reserva'] !== null
                ? (float) $publicacao['preco_reserva']
                : null,
            'pre_venda' => (string) ($publicacao['item_status'] ?? '') === 'pre_venda',
            'quantidade' => (int) $publicacao['quantidade'],
            'disponivel' => $this->disponivel($publicacaoId),
            'imagens' => $galeria,
        ];
    }

    public function carrinho(): array
    {
        $carrinho = $_SESSION['carrinho'] ?? [];

        return is_array($carrinho) ? $carrinho : [];
    }

    public function contagemCarrinho(): int
    {
        return array_sum(array_map('intval', $this->carrinho()));
    }

    public function adicionarAoCarrinho(int $publicacaoId, int $quantidade = 1): void
    {
        $this->cancelarReservaSessao();

        $publicacao = $this->publicacoes->findPublicacao($publicacaoId);

        if ($publicacao === null || (string) $publicacao['status'] !== 'publicado') {
            throw new InvalidArgumentException('Este item não está mais disponível.');
        }

        if (!in_array((string) ($publicacao['item_status'] ?? ''), ['disponivel', 'pre_venda'], true)) {
            throw new InvalidArgumentException('Este item não está disponível para venda.');
        }

        $disponivel = $this->disponivel($publicacaoId);

        if ($disponivel <= 0) {
            throw new InvalidArgumentException('Item esgotado.');
        }

        $quantidade = max(1, $quantidade);
        $carrinho = $this->carrinho();
        $atual = (int) ($carrinho[$publicacaoId] ?? 0);
        $carrinho[$publicacaoId] = min($disponivel, $atual + $quantidade);

        $_SESSION['carrinho'] = $carrinho;
    }

    public function atualizarCarrinho(array $quantidades): void
    {
        $this->cancelarReservaSessao();

        $carrinho = [];

        foreach ($quantidades as $id => $qtd) {
            $id = (int) $id;
            $qtd = (int) $qtd;

            if ($id <= 0 || $qtd <= 0) {
                continue;
            }

            $disponivel = $this->disponivel($id);

            if ($disponivel <= 0) {
                continue;
            }

            $carrinho[$id] = min($qtd, $disponivel);
        }

        $_SESSION['carrinho'] = $carrinho;
    }

    public function removerDoCarrinho(int $publicacaoId): void
    {
        $this->cancelarReservaSessao();

        $carrinho = $this->carrinho();
        unset($carrinho[$publicacaoId]);
        $_SESSION['carrinho'] = $carrinho;
    }

    public function limparCarrinho(): void
    {
        unset($_SESSION['carrinho']);
    }

    public function limparReservaSessao(): void
    {
        unset($_SESSION['pedido_reserva_id']);
    }

    public function disponivel(int $publicacaoId): int
    {
        $statement = $this->db->prepare(
            "SELECT p.quantidade, p.status, i.status AS item_status, i.quantidade AS item_quantidade,
                (SELECT COALESCE(SUM(r.quantidade), 0) FROM loja_reservas r
                    WHERE r.publicacao_id = p.id
                      AND r.status = 'reservada'
                      AND r.expira_em > NOW()) AS reservado,
                (SELECT COALESCE(SUM(r.quantidade), 0) FROM loja_reservas r
                    WHERE r.publicacao_id = p.id
                      AND r.status = 'confirmada') AS vendidos,
                (SELECT COALESCE(SUM(r.quantidade), 0) FROM loja_reservas r
                    WHERE r.item_id = i.id
                      AND r.status = 'reservada'
                      AND r.expira_em > NOW()) AS item_reservado,
                (SELECT COALESCE(SUM(r.quantidade), 0) FROM loja_reservas r
                    WHERE r.item_id = i.id
                      AND r.status = 'confirmada') AS item_vendido
             FROM loja_itens_publicados p
             INNER JOIN loja_itens i ON i.id = p.item_id
             WHERE p.id = :id
             LIMIT 1"
        );
        $statement->execute(['id' => $publicacaoId]);
        $row = $statement->fetch();

        if ($row === false || (string) $row['status'] !== 'publicado') {
            return 0;
        }

        if (!in_array((string) $row['item_status'], ['disponivel', 'pre_venda'], true)) {
            return 0;
        }

        $disponivelPublicacao = (int) $row['quantidade'] - (int) $row['reservado'] - (int) $row['vendidos'];
        $disponivelItem = (int) $row['item_quantidade'] - (int) $row['item_reservado'] - (int) $row['item_vendido'];

        return max(0, min($disponivelPublicacao, $disponivelItem));
    }

    public function disponivelItem(int $itemId): int
    {
        $statement = $this->db->prepare(
            "SELECT i.quantidade,
                (SELECT COALESCE(SUM(r.quantidade), 0) FROM loja_reservas r
                    WHERE r.item_id = i.id
                      AND r.status = 'reservada'
                      AND r.expira_em > NOW()) AS reservado,
                (SELECT COALESCE(SUM(r.quantidade), 0) FROM loja_reservas r
                    WHERE r.item_id = i.id
                      AND r.status = 'confirmada') AS vendido
             FROM loja_itens i
             WHERE i.id = :id
             LIMIT 1"
        );
        $statement->execute(['id' => $itemId]);
        $row = $statement->fetch();

        if ($row === false) {
            return 0;
        }

        return max(0, (int) $row['quantidade'] - (int) $row['reservado'] - (int) $row['vendido']);
    }

    /**
     * Monta o carrinho com a disponibilidade atual de cada item (sem ajustar o carrinho).
     */
    public function montarResumo(array $carrinho): array
    {
        $itens = [];
        $total = 0.0;
        $totalItens = 0.0;
        $saldoTotal = 0.0;
        $problemas = [];
        $temProblemas = false;

        foreach ($carrinho as $publicacaoId => $quantidade) {
            $publicacaoId = (int) $publicacaoId;
            $quantidade = max(1, (int) $quantidade);
            $publicacao = $this->publicacoes->findPublicacao($publicacaoId);

            if (
                $publicacao === null
                || (string) $publicacao['status'] !== 'publicado'
                || !in_array((string) ($publicacao['item_status'] ?? ''), ['disponivel', 'pre_venda'], true)
            ) {
                $temProblemas = true;
                $problemas[] = 'Um item do carrinho não está mais disponível. Remova-o para finalizar.';
                $itens[] = [
                    'publicacao_id' => $publicacaoId,
                    'item_id' => 0,
                    'canal_id' => 0,
                    'item_nome' => 'Item indisponível',
                    'item_codigo' => null,
                    'quantidade' => $quantidade,
                    'preco_unitario' => 0.0,
                    'valor_reserva_unitario' => null,
                    'cobrar_agora' => 0.0,
                    'saldo' => 0.0,
                    'subtotal' => 0.0,
                    'imagem_id' => null,
                    'pre_venda' => false,
                    'disponivel' => 0,
                    'ok' => false,
                    'problema' => 'Item não está mais disponível. Remova do carrinho.',
                ];
                continue;
            }

            $disponivel = $this->disponivel($publicacaoId);
            $ok = $disponivel >= $quantidade;
            $problema = '';

            if ($disponivel <= 0) {
                $problema = 'Esgotado. Remova do carrinho.';
            } elseif (!$ok) {
                $problema = 'Apenas ' . $disponivel . ' disponível(is). Ajuste a quantidade ou remova.';
            }

            if (!$ok) {
                $temProblemas = true;
                $problemas[] = '"' . $publicacao['item_nome'] . '": ' . $problema;
            }

            $preco = (float) $publicacao['preco_venda'];
            $precoReserva = null;

            if (
                (string) ($publicacao['item_status'] ?? '') === 'pre_venda'
                && $publicacao['preco_reserva'] !== null
                && (float) $publicacao['preco_reserva'] > 0
            ) {
                $precoReserva = (float) $publicacao['preco_reserva'];
            }

            $faturavel = min($quantidade, $disponivel);
            $subtotal = round($preco * $faturavel, 3);
            $valorAgora = round(($precoReserva ?? $preco) * $faturavel, 3);
            $saldoItem = round($subtotal - $valorAgora, 3);
            $totalItens += $subtotal;
            $total += $valorAgora;
            $saldoTotal += $saldoItem;

            $itens[] = [
                'publicacao_id' => $publicacaoId,
                'item_id' => (int) $publicacao['item_id'],
                'canal_id' => (int) $publicacao['canal_id'],
                'item_nome' => (string) $publicacao['item_nome'],
                'item_codigo' => $publicacao['item_codigo'] ?? null,
                'quantidade' => $quantidade,
                'preco_unitario' => $preco,
                'valor_reserva_unitario' => $precoReserva,
                'cobrar_agora' => $valorAgora,
                'saldo' => $saldoItem,
                'subtotal' => $subtotal,
                'imagem_id' => $publicacao['imagem_id'] ?? null,
                'pre_venda' => (string) ($publicacao['item_status'] ?? '') === 'pre_venda',
                'disponivel' => $disponivel,
                'ok' => $ok,
                'problema' => $problema,
            ];
        }

        $porItem = [];
        $nomesItem = [];

        foreach ($itens as $item) {
            if ((int) $item['item_id'] <= 0) {
                continue;
            }

            $itemId = (int) $item['item_id'];
            $porItem[$itemId] = ($porItem[$itemId] ?? 0) + (int) $item['quantidade'];
            $nomesItem[$itemId] = (string) $item['item_nome'];
        }

        foreach ($porItem as $itemId => $necessario) {
            $disponivelItem = $this->disponivelItem($itemId);

            if ($necessario <= $disponivelItem) {
                continue;
            }

            $temProblemas = true;
            $problemas[] = '"' . ($nomesItem[$itemId] ?? 'Item') . '": a quantidade total no carrinho ('
                . $necessario . ') não está disponível (' . $disponivelItem . '). Ajuste ou remova.';

            foreach ($itens as $indice => $item) {
                if ((int) $item['item_id'] !== $itemId) {
                    continue;
                }

                $itens[$indice]['ok'] = false;
                $itens[$indice]['problema'] = 'Quantidade total no carrinho excede o disponível. Ajuste ou remova.';
            }
        }

        return [
            'itens' => $itens,
            'total' => round($total, 3),
            'total_itens' => round($totalItens, 3),
            'saldo' => round($saldoTotal, 3),
            'problemas' => $problemas,
            'tem_problemas' => $temProblemas,
        ];
    }

    /**
     * Cria a reserva do carrinho ao clicar em "Finalizar compra".
     * Bloqueia quando algum item nao esta disponivel na quantidade desejada.
     */
    public function criarReserva(array $carrinho): array
    {
        $resumo = $this->montarResumo($carrinho);

        if ($resumo['itens'] === []) {
            throw new InvalidArgumentException('Seu carrinho está vazio.');
        }

        if (!empty($resumo['tem_problemas'])) {
            throw new InvalidArgumentException(
                'Alguns itens não estão disponíveis nas quantidades desejadas. '
                . 'Ajuste ou remova-os do carrinho antes de finalizar: ' . implode(' ', $resumo['problemas'])
            );
        }

        $this->cancelarReservaSessao();

        $expira = date('Y-m-d H:i:s', time() + self::RESERVA_MINUTOS * 60);
        $canalLoja = $this->publicacoes->canalPorSlug('loja');
        $canalLojaId = $canalLoja !== null ? (int) $canalLoja['id'] : null;

        $this->db->beginTransaction();

        try {
            $codigo = $this->pedidos->gerarCodigo();

            $pedidoId = $this->pedidos->create([
                'codigo' => $codigo,
                'cliente_nome' => 'Reserva',
                'entrega_modo' => 'retirada',
                'subtotal' => $resumo['total_itens'],
                'frete' => 0,
                'total' => $resumo['total'],
                'valor_pago' => 0,
                'saldo' => $resumo['saldo'],
                'status' => 'pendente',
                'origem' => 'loja',
                'metodo_pagamento' => 'mercadopago',
                'expira_em' => $expira,
            ]);

            foreach ($resumo['itens'] as $item) {
                $this->pedidos->adicionarItem($pedidoId, [
                    'publicacao_id' => $item['publicacao_id'],
                    'item_id' => $item['item_id'],
                    'quantidade' => $item['quantidade'],
                    'preco_unitario' => $item['preco_unitario'],
                    'valor_reserva' => $item['valor_reserva_unitario'],
                    'subtotal' => $item['subtotal'],
                    'item_nome' => $item['item_nome'],
                    'item_codigo' => $item['item_codigo'],
                ]);

                $this->reservas->create([
                    'publicacao_id' => $item['publicacao_id'],
                    'item_id' => $item['item_id'],
                    'canal_id' => $canalLojaId ?? $item['canal_id'],
                    'quantidade' => $item['quantidade'],
                    'preco_venda' => number_format($item['preco_unitario'], 3, '.', ''),
                    'valor_reserva' => $item['valor_reserva_unitario'] !== null
                        ? number_format($item['valor_reserva_unitario'], 3, '.', '')
                        : null,
                    'status' => 'reservada',
                    'metodo_pagamento' => 'mercadopago',
                    'pedido_id' => $pedidoId,
                    'expira_em' => $expira,
                ]);
            }

            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }

        $_SESSION['pedido_reserva_id'] = $pedidoId;

        return [
            'pedido' => $this->pedidos->find($pedidoId),
            'itens' => $this->pedidos->itens($pedidoId),
        ];
    }

    /**
     * Retorna a reserva ativa da sessao, se ainda estiver pendente e valida.
     */
    public function reservaSessao(): ?array
    {
        $pedidoId = (int) ($_SESSION['pedido_reserva_id'] ?? 0);

        if ($pedidoId <= 0) {
            return null;
        }

        $pedido = $this->pedidos->find($pedidoId);

        if ($pedido === null || (string) $pedido['status'] !== 'pendente') {
            unset($_SESSION['pedido_reserva_id']);

            return null;
        }

        if (strtotime((string) $pedido['expira_em']) <= time()) {
            $this->pedidos->updateStatus($pedidoId, 'expirado');
            $this->reservas->cancelarPorPedido($pedidoId);
            unset($_SESSION['pedido_reserva_id']);

            return null;
        }

        $pedido['itens'] = $this->pedidos->itens($pedidoId);

        return $pedido;
    }

    public function cancelarReservaSessao(): void
    {
        $pedidoId = (int) ($_SESSION['pedido_reserva_id'] ?? 0);
        unset($_SESSION['pedido_reserva_id']);

        if ($pedidoId <= 0) {
            return;
        }

        $pedido = $this->pedidos->find($pedidoId);

        if ($pedido !== null && (string) $pedido['status'] === 'pendente') {
            $this->cancelarPedido($pedidoId);
        }
    }

    /**
     * Finaliza a reserva (dados do cliente) sem recriar os itens.
     */
    public function finalizarPedido(int $pedidoId, array $dados): array
    {
        $pedido = $this->pedidos->find($pedidoId);

        if ($pedido === null) {
            throw new InvalidArgumentException('Reserva não encontrada.');
        }

        if ((string) $pedido['status'] !== 'pendente') {
            throw new InvalidArgumentException('Esta reserva não está mais disponível para finalização.');
        }

        if (strtotime((string) $pedido['expira_em']) <= time()) {
            $this->cancelarPedido($pedidoId);
            unset($_SESSION['pedido_reserva_id']);

            throw new InvalidArgumentException('A reserva expirou. Volte ao carrinho e finalize novamente.');
        }

        $cliente = $this->validarDadosCliente($dados);

        $this->pedidos->updateCliente($pedidoId, $cliente);

        return [
            'pedido' => $this->pedidos->find($pedidoId),
            'itens' => $this->pedidos->itens($pedidoId),
        ];
    }

    /**
     * Compatibilidade: cria a reserva e finaliza em uma unica chamada.
     */
    public function criarPedido(array $dados, array $carrinho): array
    {
        $reserva = $this->criarReserva($carrinho);

        return $this->finalizarPedido((int) $reserva['pedido']['id'], $dados);
    }

    public function confirmarPagamento(int $pedidoId, array $pagamento): void
    {
        $pedido = $this->pedidos->find($pedidoId);

        if ($pedido === null) {
            return;
        }

        $mpStatus = (string) ($pagamento['status'] ?? '');

        $status = match ($mpStatus) {
            'approved' => 'pago',
            'rejected', 'cancelled', 'refunded', 'charged_back' => 'cancelado',
            default => 'pendente',
        };

        $pagoEm = null;

        if ($status === 'pago') {
            $data = (string) ($pagamento['date_approved'] ?? '');
            $timestamp = $data !== '' ? strtotime($data) : false;
            $pagoEm = $timestamp !== false ? date('Y-m-d H:i:s', $timestamp) : date('Y-m-d H:i:s');
        }

        $this->pedidos->updatePagamento(
            $pedidoId,
            $status,
            isset($pagamento['id']) ? (string) $pagamento['id'] : null,
            $mpStatus !== '' ? $mpStatus : null,
            $pagoEm
        );

        if ($status === 'pago') {
            $this->reservas->confirmarPorPedido($pedidoId, $pagoEm ?? date('Y-m-d H:i:s'));

            if ((int) ($_SESSION['pedido_reserva_id'] ?? 0) === $pedidoId) {
                unset($_SESSION['pedido_reserva_id']);
            }
        } elseif ($status === 'cancelado') {
            $this->reservas->cancelarPorPedido($pedidoId);
        }
    }

    public function cancelarPedido(int $pedidoId): void
    {
        $this->pedidos->updateStatus($pedidoId, 'cancelado');
        $this->reservas->cancelarPorPedido($pedidoId);
    }

    public function expirarPendentes(): void
    {
        $this->pedidos->expirarVencidos();
        $this->reservas->expirarVencidas();
    }

    public function pedidoPorCodigo(string $codigo): ?array
    {
        $pedido = $this->pedidos->findByCodigo($codigo);

        if ($pedido === null) {
            return null;
        }

        $pedido['itens'] = $this->pedidos->itens((int) $pedido['id']);

        return $pedido;
    }

    private function validarDadosCliente(array $dados): array
    {
        $modo = (string) ($dados['entrega_modo'] ?? 'retirada');

        if (!in_array($modo, ['retirada', 'envio'], true)) {
            throw new InvalidArgumentException('Forma de entrega inválida.');
        }

        if ($modo === 'envio') {
            throw new InvalidArgumentException('O envio ainda não está disponível. Escolha retirar na loja.');
        }

        $nome = trim((string) ($dados['cliente_nome'] ?? ''));

        if (mb_strlen($nome) < 3) {
            throw new InvalidArgumentException('Informe seu nome completo.');
        }

        $email = trim((string) ($dados['cliente_email'] ?? ''));

        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Informe um e-mail válido.');
        }

        $telefone = preg_replace('/\D/', '', (string) ($dados['cliente_telefone'] ?? '')) ?? '';

        if (strlen($telefone) < 10) {
            throw new InvalidArgumentException('Informe um telefone válido com DDD.');
        }

        return [
            'nome' => $nome,
            'email' => $email !== '' ? $email : null,
            'telefone' => $telefone,
            'modo' => $modo,
            'observacoes' => trim((string) ($dados['observacoes'] ?? '')) ?: null,
        ];
    }
}
