<?php

declare(strict_types=1);

namespace App\Controllers\Loja;

use App\Core\Controller;
use App\Services\Loja\EntradaService;
use App\Services\ConfiguracaoService;
use App\Services\Loja\PrevisaoService;
use InvalidArgumentException;
use RuntimeException;

class EntradaController extends Controller
{
    private EntradaService $entradas;
    private PrevisaoService $previsao;

    public function __construct()
    {
        $this->entradas = new EntradaService();
        $this->previsao = new PrevisaoService();
    }

    public function index(): void
    {
        $filters = $this->filters();
        $page = max(1, (int) ($_GET['pagina'] ?? 1));

        $this->view('loja.entradas.index', array_merge([
            'title' => 'Entrada de produtos',
            'breadcrumb' => [
                ['label' => 'Loja', 'url' => null],
                ['label' => 'Entrada de produtos', 'url' => null],
            ],
            'activeTab' => 'entradas',
            'filters' => $filters,
            'result' => $this->entradas->listar($filters, $page),
            'query' => $this->query($filters),
            'basePath' => '/admin/loja/entradas',
        ], $this->extraAssets()), 'admin');
    }

    public function store(): void
    {
        try {
            $id = $this->entradas->criar($_POST, (array) auth_user());
            flash('success', 'Entrada criada com sucesso. Agora inclua os itens e custos necessários.');
            redirect('/admin/loja/entradas/' . $id);
        } catch (InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
            flash_input($_POST);
            redirect('/admin/loja/entradas');
        }
    }

    public function show(string $id): void
    {
        try {
            $detail = $this->entradas->obterDetalhe((int) $id);
        } catch (InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
            redirect('/admin/loja/entradas');
        }

        $this->view('loja.entradas.detalhe', array_merge([
            'title' => 'Detalhes da entrada',
            'breadcrumb' => [
                ['label' => 'Loja', 'url' => null],
                ['label' => 'Entrada de produtos', 'url' => '/admin/loja/entradas'],
                ['label' => 'Detalhes', 'url' => null],
            ],
            'activeTab' => 'entradas',
            'detail' => $detail,
            'opcoes' => (new ConfiguracaoService())->opcoes(),
        ], $this->extraAssets()), 'admin');
    }

    public function update(string $id): void
    {
        try {
            $this->entradas->atualizar((int) $id, $_POST, (array) auth_user());
            flash('success', 'Entrada atualizada com sucesso.');
        } catch (InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
            flash_input($_POST);
        }

        redirect('/admin/loja/entradas/' . (int) $id);
    }

    public function toggleReceived(string $id): void
    {
        try {
            $recebido = $this->entradas->alternarRecebido((int) $id, (array) auth_user());
            flash('success', $recebido
                ? 'Entrada marcada como recebida. Os itens foram disponibilizados no estoque.'
                : 'Recebimento desmarcado. Os itens voltaram para transporte.');
        } catch (InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
        }

        redirect('/admin/loja/entradas/' . (int) $id);
    }

    public function destroy(string $id): void
    {
        try {
            $this->entradas->excluir((int) $id, (array) auth_user());
            flash('success', 'Entrada excluída com sucesso.');
            redirect('/admin/loja/entradas');
        } catch (InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
            redirect('/admin/loja/entradas/' . (int) $id);
        }
    }

    public function addCost(string $id): void
    {
        try {
            $this->entradas->adicionarCusto((int) $id, $_POST, (array) auth_user());
            flash('success', 'Custo adicional incluído e custo real recalculado.');
        } catch (InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
            flash_input($_POST);
        }

        redirect('/admin/loja/entradas/' . (int) $id);
    }

    public function deleteCost(string $id, string $custoId): void
    {
        try {
            $this->entradas->excluirCusto((int) $id, (int) $custoId, (array) auth_user());
            flash('success', 'Custo adicional excluído e custo real recalculado.');
        } catch (InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
        }

        redirect('/admin/loja/entradas/' . (int) $id);
    }

    public function addItem(string $id): void
    {
        try {
            $this->entradas->adicionarItem(
                (int) $id,
                $_POST,
                (array) auth_user(),
                $_FILES['imagens'] ?? []
            );
            flash('success', 'Item incluído com sucesso.');
        } catch (InvalidArgumentException | RuntimeException $exception) {
            flash('error', $exception->getMessage());
            flash_input($_POST);
        }

        redirect('/admin/loja/entradas/' . (int) $id);
    }

    public function updateItem(string $id, string $itemId): void
    {
        try {
            $this->entradas->atualizarItem((int) $itemId, $_POST, (array) auth_user(), $_FILES['imagens'] ?? []);
            flash('success', 'Item atualizado com sucesso.');
        } catch (InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
            flash_input($_POST);
        }

        redirect('/admin/loja/entradas/' . (int) $id);
    }

    public function deleteItem(string $itemId): void
    {
        $fallback = (int) ($_POST['entrada_id'] ?? 0);

        try {
            $entradaId = $this->entradas->excluirItem((int) $itemId, (array) auth_user());
            flash('success', 'Item excluído com sucesso.');
            redirect('/admin/loja/entradas/' . $entradaId);
        } catch (InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
            redirect($fallback > 0 ? '/admin/loja/entradas/' . $fallback : '/admin/loja/entradas');
        }
    }

    public function addOffer(string $itemId): void
    {
        $fallback = (int) ($_POST['entrada_id'] ?? 0);

        try {
            $entradaId = $this->previsao->adicionarOferta((int) $itemId, $_POST, (array) auth_user());
            flash('success', 'Oferta de fornecedor incluída com sucesso.');
            redirect('/admin/loja/entradas/' . $entradaId);
        } catch (InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
            flash_input($_POST);
            redirect($fallback > 0 ? '/admin/loja/entradas/' . $fallback : '/admin/loja/entradas');
        }
    }

    public function selectOffer(string $itemId, string $ofertaId): void
    {
        $fallback = (int) ($_POST['entrada_id'] ?? 0);

        try {
            $entradaId = $this->previsao->selecionarOferta(
                (int) $itemId,
                (int) $ofertaId,
                (array) auth_user()
            );
            flash('success', 'Oferta selecionada. O custo do item foi atualizado.');
            redirect('/admin/loja/entradas/' . $entradaId);
        } catch (InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
            redirect($fallback > 0 ? '/admin/loja/entradas/' . $fallback : '/admin/loja/entradas');
        }
    }

    public function deleteOffer(string $itemId, string $ofertaId): void
    {
        $fallback = (int) ($_POST['entrada_id'] ?? 0);

        try {
            $entradaId = $this->previsao->excluirOferta(
                (int) $itemId,
                (int) $ofertaId,
                (array) auth_user()
            );
            flash('success', 'Oferta de fornecedor excluída.');
            redirect('/admin/loja/entradas/' . $entradaId);
        } catch (InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
            redirect($fallback > 0 ? '/admin/loja/entradas/' . $fallback : '/admin/loja/entradas');
        }
    }

    private function filters(): array
    {
        $modoParam = $_GET['modo'] ?? '';
        $recebidoParam = $_GET['recebido'] ?? '';
        $buscaParam = $_GET['busca'] ?? '';

        $modo = is_string($modoParam) && in_array($modoParam, ['direta', 'previsao'], true)
            ? $modoParam
            : '';
        $recebido = $modo === 'direta'
            && is_string($recebidoParam)
            && in_array($recebidoParam, ['0', '1'], true)
                ? $recebidoParam
                : '';

        return [
            'modo' => $modo,
            'recebido' => $recebido,
            'busca' => is_string($buscaParam) ? trim($buscaParam) : '',
        ];
    }

    private function query(array $filters): array
    {
        return array_filter($filters, static fn (string $value): bool => $value !== '');
    }

    private function extraAssets(): array
    {
        return [
            'extraStyles' => [asset('css/loja.css')],
            'extraScripts' => [asset('js/loja.js')],
        ];
    }
}
