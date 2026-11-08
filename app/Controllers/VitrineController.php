<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Models\Loja\PedidoOnline;
use App\Services\Loja\MercadoPagoService;
use App\Services\Loja\VisitaService;
use App\Services\Loja\VitrineService;
use InvalidArgumentException;
use Throwable;

class VitrineController extends Controller
{
    private VitrineService $vitrine;

    public function __construct()
    {
        $this->vitrine = new VitrineService();
    }

    public function index(): void
    {
        $busca = trim((string) ($_GET['busca'] ?? ''));
        $this->vitrine->expirarPendentes();

        $this->view('vitrine.index', [
            'title' => 'Loja',
            'busca' => $busca,
            'itens' => $this->vitrine->listarItens($busca),
            'carrinhoCount' => $this->vitrine->contagemCarrinho(),
            'extraStyles' => [asset('css/vitrine.css')],
            'extraScripts' => [asset('js/vitrine.js')],
        ], 'vitrine');
    }

    public function oferta(string $id): void
    {
        $this->vitrine->expirarPendentes();
        $detalhe = $this->vitrine->detalhePublicacao((int) $id);

        if ($detalhe === null) {
            flash('error', 'Esta oferta não está mais disponível.');
            redirect('/loja');
        }

        (new VisitaService())->registrar((int) $detalhe['item_id'], (int) $detalhe['id'], 'oferta');

        $imagemPrincipal = null;

        foreach ($detalhe['imagens'] as $imagem) {
            if (!empty($imagem['principal'])) {
                $imagemPrincipal = $imagem;
                break;
            }
        }

        if ($imagemPrincipal === null) {
            $imagemPrincipal = $detalhe['imagens'][0] ?? null;
        }

        $descricao = trim(strip_tags((string) $detalhe['descricao']));

        if (mb_strlen($descricao) > 200) {
            $descricao = mb_substr($descricao, 0, 197) . '...';
        }

        $item = [
            'id' => $detalhe['id'],
            'item_nome' => $detalhe['titulo'],
            'titulo' => $detalhe['titulo'],
            'descricao' => $detalhe['descricao'],
            'item_descricao' => $detalhe['descricao'],
            'item_codigo' => $detalhe['codigo'],
            'preco_venda' => $detalhe['preco_raw'],
            'preco_reserva' => $detalhe['preco_reserva_raw'],
            'quantidade' => $detalhe['quantidade'],
            'disponivel_calc' => $detalhe['disponivel'],
            'item_status' => $detalhe['pre_venda'] ? 'pre_venda' : 'disponivel',
            'imagem_id' => $imagemPrincipal['id'] ?? null,
            'reservado' => 0,
            'vendidos' => 0,
            'canal_slug' => 'loja',
            'canal_nome' => 'Loja virtual',
        ];

        $this->view('vitrine.index', [
            'title' => $detalhe['titulo'],
            'busca' => '',
            'itens' => [$item],
            'carrinhoCount' => $this->vitrine->contagemCarrinho(),
            'ogTitle' => $detalhe['titulo'],
            'ogDescription' => $descricao,
            'ogImage' => $imagemPrincipal['url'] ?? null,
            'ogUrl' => url('/loja/oferta/' . $detalhe['id']),
            'ogPrice' => $detalhe['preco_raw'],
            'extraStyles' => [asset('css/vitrine.css')],
            'extraScripts' => [asset('js/vitrine.js')],
        ], 'vitrine');
    }

    public function item(string $id): void
    {
        $this->vitrine->expirarPendentes();
        $detalhe = $this->vitrine->detalhePublicacao((int) $id);

        if ($detalhe === null) {
            $this->json(['error' => 'Item não encontrado.'], 404);
        }

        (new VisitaService())->registrar((int) $detalhe['item_id'], (int) $detalhe['id'], 'loja');

        $this->json($detalhe);
    }

    public function carrinho(): void
    {
        $this->vitrine->cancelarReservaSessao();
        $resumo = $this->vitrine->montarResumo($this->vitrine->carrinho());

        $this->view('vitrine.carrinho', [
            'title' => 'Carrinho',
            'resumo' => $resumo,
            'carrinhoCount' => $this->vitrine->contagemCarrinho(),
            'extraStyles' => [asset('css/vitrine.css')],
            'extraScripts' => [asset('js/vitrine.js')],
        ], 'vitrine');
    }

    public function adicionar(): void
    {
        try {
            $this->vitrine->adicionarAoCarrinho(
                (int) ($_POST['publicacao_id'] ?? 0),
                (int) ($_POST['quantidade'] ?? 1)
            );
            flash('success', 'Item adicionado ao carrinho.');
        } catch (InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
        } catch (Throwable $exception) {
            flash('error', 'Não foi possível adicionar o item: ' . $exception->getMessage());
        }

        redirect('/loja');
    }

    public function atualizar(): void
    {
        $quantidades = $_POST['quantidades'] ?? [];
        $this->vitrine->atualizarCarrinho(is_array($quantidades) ? $quantidades : []);

        flash('success', 'Carrinho atualizado.');
        redirect('/loja/carrinho');
    }

    public function remover(): void
    {
        $this->vitrine->removerDoCarrinho((int) ($_POST['publicacao_id'] ?? 0));
        flash('success', 'Item removido do carrinho.');
        redirect('/loja/carrinho');
    }

    public function reservar(): void
    {
        try {
            $this->vitrine->criarReserva($this->vitrine->carrinho());
            redirect('/loja/checkout');
        } catch (InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
        } catch (Throwable $exception) {
            flash('error', 'Não foi possível reservar os itens: ' . $exception->getMessage());
        }

        redirect('/loja/carrinho');
    }

    public function checkoutForm(): void
    {
        $pedido = $this->vitrine->reservaSessao();

        if ($pedido === null) {
            flash('error', 'Sua reserva não está ativa. Finalize a compra novamente a partir do carrinho.');
            redirect('/loja/carrinho');
        }

        $itens = array_map(
            static function (array $item): array {
                $preco = (float) $item['preco_unitario'];
                $quantidade = (int) $item['quantidade'];
                $reserva = $item['valor_reserva'] !== null ? (float) $item['valor_reserva'] : null;
                $subtotal = (float) $item['subtotal'];
                $cobrarAgora = round(($reserva ?? $preco) * $quantidade, 3);

                return [
                    'item_nome' => (string) $item['item_nome'],
                    'item_codigo' => $item['item_codigo'] ?? null,
                    'quantidade' => $quantidade,
                    'preco_unitario' => $preco,
                    'valor_reserva_unitario' => $reserva,
                    'subtotal' => $subtotal,
                    'cobrar_agora' => $cobrarAgora,
                    'saldo' => round($subtotal - $cobrarAgora, 3),
                ];
            },
            $pedido['itens']
        );

        $resumo = [
            'itens' => $itens,
            'total' => (float) $pedido['total'],
            'total_itens' => (float) $pedido['subtotal'],
            'saldo' => (float) $pedido['saldo'],
            'problemas' => [],
            'tem_problemas' => false,
        ];

        $this->view('vitrine.checkout', [
            'title' => 'Finalizar compra',
            'resumo' => $resumo,
            'pedido' => $pedido,
            'carrinhoCount' => $this->vitrine->contagemCarrinho(),
            'extraStyles' => [asset('css/vitrine.css')],
            'extraScripts' => [asset('js/vitrine.js')],
        ], 'vitrine');
    }

    public function checkout(): void
    {
        $reserva = $this->vitrine->reservaSessao();

        if ($reserva === null) {
            flash('error', 'Sua reserva expirou ou não está ativa. Volte ao carrinho e finalize novamente.');
            redirect('/loja/carrinho');
        }

        try {
            $mercadoPago = new MercadoPagoService();

            if (!$mercadoPago->ativo()) {
                throw new InvalidArgumentException('O pagamento online não está disponível no momento.');
            }

            $criado = $this->vitrine->finalizarPedido((int) $reserva['id'], $_POST);
            $pedido = $criado['pedido'];

            $pagamento = $mercadoPago->criarLinkPedido($pedido, $criado['itens']);

            if ($pagamento === null) {
                throw new InvalidArgumentException(
                    'Não foi possível iniciar o pagamento: ' . ($mercadoPago->ultimoErro() ?? 'erro desconhecido')
                );
            }

            if ($pagamento['preference_id'] !== null) {
                (new PedidoOnline())->updatePreference(
                    (int) $pedido['id'],
                    $pagamento['preference_id'],
                    $pagamento['link']
                );
            }

            $this->vitrine->limparCarrinho();
            $this->vitrine->limparReservaSessao();
            redirect($pagamento['link']);
        } catch (InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
            flash_input($_POST);
        } catch (Throwable $exception) {
            flash('error', 'Falha ao finalizar a compra: ' . $exception->getMessage());
            flash_input($_POST);
        }

        redirect('/loja/checkout');
    }

    public function pagar(string $codigo): void
    {
        $pedido = $this->vitrine->pedidoPorCodigo($codigo);

        if ($pedido === null) {
            flash('error', 'Pedido não encontrado.');
            redirect('/loja');
        }

        if ((string) $pedido['status'] !== 'pendente') {
            flash('info', 'Este pedido não está aguardando pagamento.');
            redirect('/loja/pedido/' . rawurlencode($codigo));
        }

        if (strtotime((string) $pedido['expira_em']) <= time()) {
            $this->vitrine->cancelarPedido((int) $pedido['id']);
            flash('error', 'O prazo de pagamento expirou. Refaça a compra.');
            redirect('/loja/pedido/' . rawurlencode($codigo));
        }

        try {
            $mercadoPago = new MercadoPagoService();

            if (!$mercadoPago->ativo()) {
                throw new InvalidArgumentException('O pagamento online não está disponível no momento.');
            }

            $pagamento = $mercadoPago->criarLinkPedido($pedido, $pedido['itens']);

            if ($pagamento === null) {
                throw new InvalidArgumentException(
                    'Não foi possível gerar o pagamento: ' . ($mercadoPago->ultimoErro() ?? 'erro desconhecido')
                );
            }

            if ($pagamento['preference_id'] !== null) {
                (new PedidoOnline())->updatePreference(
                    (int) $pedido['id'],
                    $pagamento['preference_id'],
                    $pagamento['link']
                );
            }

            redirect($pagamento['link']);
        } catch (InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
        } catch (Throwable $exception) {
            flash('error', 'Falha ao gerar o pagamento: ' . $exception->getMessage());
        }

        redirect('/loja/pedido/' . rawurlencode($codigo));
    }

    public function retorno(): void
    {
        $codigo = trim((string) ($_GET['pedido'] ?? ''));
        $paymentId = $_GET['payment_id'] ?? $_GET['collection_id'] ?? null;

        if (is_string($paymentId) && $paymentId !== '') {
            try {
                (new MercadoPagoService())->processarPagamentoPorId($paymentId);
            } catch (Throwable $exception) {
                error_log('[vitrine] falha ao sincronizar pagamento: ' . $exception->getMessage());
            }
        }

        $this->vitrine->expirarPendentes();

        if ($codigo === '') {
            redirect('/loja');
        }

        $pedido = $this->vitrine->pedidoPorCodigo($codigo);

        if ($pedido !== null && (string) $pedido['status'] === 'pago') {
            $this->vitrine->limparCarrinho();
            $this->vitrine->limparReservaSessao();
        }

        redirect('/loja/pedido/' . rawurlencode($codigo));
    }

    public function pedido(string $codigo): void
    {
        $this->vitrine->expirarPendentes();
        $pedido = $this->vitrine->pedidoPorCodigo($codigo);

        if ($pedido === null) {
            http_response_code(404);
            echo View::render('errors.404', [], null);

            return;
        }

        $this->view('vitrine.pedido', [
            'title' => 'Pedido ' . (string) $pedido['codigo'],
            'pedido' => $pedido,
            'carrinhoCount' => $this->vitrine->contagemCarrinho(),
            'extraStyles' => [asset('css/vitrine.css')],
            'extraScripts' => [asset('js/vitrine.js')],
        ], 'vitrine');
    }
}
