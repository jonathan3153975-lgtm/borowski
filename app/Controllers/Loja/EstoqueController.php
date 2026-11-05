<?php

declare(strict_types=1);

namespace App\Controllers\Loja;

use App\Core\Controller;
use App\Services\Loja\EstoqueService;

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
        $filters = [
            'status' => in_array($status, ['disponivel', 'em_transporte', 'pre_venda'], true)
                ? $status
                : '',
            'busca' => trim((string) ($_GET['busca'] ?? '')),
        ];
        $page = max(1, (int) ($_GET['pagina'] ?? 1));
        $query = array_filter($filters, static fn (string $value): bool => $value !== '');

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
            'extraStyles' => [asset('css/loja.css')],
            'extraScripts' => [asset('js/loja.js')],
        ], 'admin');
    }
}
