<?php

declare(strict_types=1);

namespace App\Services\Loja;

use App\Models\Loja\ItemEstoque;

class EstoqueService
{
    public const PER_PAGE = 20;

    private ItemEstoque $itens;

    public function __construct()
    {
        $this->itens = new ItemEstoque();
    }

    public function listar(array $filters, int $page, int $perPage = self::PER_PAGE): array
    {
        $page = max(1, $page);
        $total = $this->itens->contarEstoque($filters);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);
        $offset = ($page - 1) * $perPage;

        return [
            'items' => $this->itens->listarEstoque($filters, $perPage, $offset),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'per_page' => $perPage,
            'resumo' => $this->itens->resumoEstoque(),
        ];
    }
}
