<?php

declare(strict_types=1);

namespace App\Controllers\Loja;

use App\Core\Controller;
use App\Models\Loja\ItemImagem;
use App\Models\Loja\ItemPublicado;
use App\Services\Loja\PublicacaoService;
use App\Services\Loja\VitrineService;
use InvalidArgumentException;
use Throwable;

class PublicadoController extends Controller
{
    private PublicacaoService $publicacoes;

    public function __construct()
    {
        $this->publicacoes = new PublicacaoService();
    }

    public function index(): void
    {
        $busca = trim((string) ($_GET['busca'] ?? ''));
        $ordem = (string) ($_GET['ordem'] ?? 'vistos');

        if (!in_array($ordem, ['vistos', 'recentes'], true)) {
            $ordem = 'vistos';
        }

        $data = $this->publicacoes->listar($busca);
        $itemIds = array_map(
            static fn (array $publicacao): int => (int) $publicacao['item_id'],
            $data['publicacoes']
        );

        $this->view('loja.estoque.publicados', [
            'title' => 'Itens publicados',
            'breadcrumb' => [
                ['label' => 'Loja', 'url' => null],
                ['label' => 'Estoque', 'url' => '/admin/loja/estoque'],
                ['label' => 'Publicados', 'url' => null],
            ],
            'activeTab' => 'publicados',
            'publicacoes' => $data['publicacoes'],
            'mapa' => $data['mapa'],
            'imagensPorItem' => (new ItemImagem())->listByItems($itemIds),
            'busca' => $busca,
            'ordem' => $ordem,
            'extraStyles' => [asset('css/loja.css')],
            'extraScripts' => [asset('js/loja.js')],
        ], 'admin');
    }

    public function store(): void
    {
        try {
            $ids = $this->publicacoes->publicarEmCanais($_POST, (array) auth_user());
            flash(
                'success',
                count($ids) === 1
                    ? 'Item publicado com sucesso.'
                    : count($ids) . ' publicações criadas com sucesso.'
            );
        } catch (InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
        }

        redirect('/admin/loja/estoque/publicados');
    }

    public function publicarTelegram(string $itemId): void
    {
        $this->publicarCanal((int) $itemId, 'telegram');
    }

    public function publicarLoja(string $itemId): void
    {
        $this->publicarCanal((int) $itemId, 'loja');
    }

    private function publicarCanal(int $itemId, string $canalSlug): void
    {
        try {
            $publicacoes = (new ItemPublicado())->listar(null, '');
            $existentes = [];

            foreach ($publicacoes as $publicacao) {
                if ((int) $publicacao['item_id'] !== $itemId) {
                    continue;
                }

                $existentes[(string) $publicacao['canal_slug']] = $publicacao;
            }

            if (isset($existentes[$canalSlug])) {
                flash('info', 'Este item já está publicado neste canal.');
                redirect('/admin/loja/estoque/publicados');
            }

            $base = $existentes['loja'] ?? (reset($existentes) ?: null);

            if ($base === null) {
                throw new InvalidArgumentException('Não há publicação de referência para este item.');
            }

            $disponivel = (new VitrineService())->disponivelItem($itemId);
            $quantidade = min((int) $base['quantidade'], $disponivel);

            if ($quantidade < 1) {
                throw new InvalidArgumentException('Não há unidades disponíveis para publicar neste canal.');
            }

            (new PublicacaoService())->publicarEmCanais([
                'item_id' => $itemId,
                'preco_venda' => format_money_input($base['preco_venda']),
                'preco_reserva' => $base['preco_reserva'] !== null
                    ? format_money_input($base['preco_reserva'])
                    : null,
                'canais' => [$canalSlug],
                'quantidades' => [$canalSlug => $quantidade],
            ], (array) auth_user());

            flash('success', 'Item publicado no canal com sucesso.');
        } catch (InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
        } catch (Throwable $exception) {
            flash('error', 'Falha ao publicar: ' . $exception->getMessage());
        }

        redirect('/admin/loja/estoque/publicados');
    }

    public function destroy(string $id): void
    {
        try {
            $this->publicacoes->excluir((int) $id, (array) auth_user());
            flash('success', 'Publicação removida.');
        } catch (InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
        }

        redirect('/admin/loja/estoque/publicados');
    }
}
