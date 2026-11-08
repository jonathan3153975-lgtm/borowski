<?php

declare(strict_types=1);

namespace App\Controllers\Loja;

use App\Core\Controller;
use App\Models\Loja\Reserva;
use App\Services\ConfiguracaoService;
use App\Services\Loja\DemandaService;
use App\Services\Loja\EstoqueService;
use App\Services\Loja\PublicacaoService;
use InvalidArgumentException;

class EstoqueController extends Controller
{
    private EstoqueService $estoque;

    public function __construct()
    {
        $this->estoque = new EstoqueService();
    }

    public function index(): void
    {
        $status = (string) ($_GET['status'] ?? '');
        $publicacao = (string) ($_GET['publicacao'] ?? '');
        $filters = [
            'status' => in_array($status, ['disponivel', 'em_transporte', 'pre_venda'], true)
                ? $status
                : '',
            'publicacao' => in_array($publicacao, ['publicado', 'nao_publicado'], true)
                ? $publicacao
                : '',
            'busca' => trim((string) ($_GET['busca'] ?? '')),
        ];
        $page = max(1, (int) ($_GET['pagina'] ?? 1));
        $query = array_filter($filters, static fn (string $value): bool => $value !== '');
        $publicacaoData = (new PublicacaoService())->listar();

        $this->view('loja.estoque.index', [
            'title' => 'Estoque',
            'breadcrumb' => [
                ['label' => 'Loja', 'url' => null],
                ['label' => 'Estoque', 'url' => null],
            ],
            'activeTab' => 'estoque',
            'filters' => $filters,
            'result' => $this->estoque->listar($filters, $page),
            'query' => $query,
            'basePath' => '/admin/loja/estoque',
            'publicacoesPorItem' => $publicacaoData['mapa'],
            'itens' => $publicacaoData['itens'],
            'mapa' => $publicacaoData['mapa'],
            'canais' => $publicacaoData['canais'],
            'reservasPorItem' => (new Reserva())->resumoPorItem(),
            'opcoes' => (new ConfiguracaoService())->opcoes(),
            'extraStyles' => [asset('css/loja.css')],
            'extraScripts' => [asset('js/loja.js')],
        ], 'admin');
    }

    public function storeDemanda(): void
    {
        try {
            (new DemandaService())->criar($_POST, (array) auth_user(), $_FILES['imagens'] ?? []);
            flash('success', 'Item sob demanda (pré-venda) cadastrado com sucesso.');
        } catch (InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
            flash_input($_POST);
        }

        redirect('/admin/loja/estoque');
    }
}
