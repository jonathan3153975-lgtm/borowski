<?php

declare(strict_types=1);

$result = $result ?? ['items' => [], 'total' => 0, 'page' => 1, 'pages' => 1, 'resumo' => []];
$filters = $filters ?? ['status' => '', 'busca' => ''];
$items = $result['items'];
$resumo = $result['resumo'];
$hasFilters = $filters['status'] !== '' || $filters['busca'] !== '';
$statusOptions = [
    '' => 'Todos',
    'disponivel' => 'Disponível em estoque',
    'em_transporte' => 'Em transporte',
    'pre_venda' => 'Em pré-venda',
];
?>
<?php require __DIR__ . '/../partials/tabs.php'; ?>

<div class="toolbar">
    <form method="get" action="<?= e(url('/admin/loja/estoque')) ?>" class="toolbar-filters" role="search">
        <input type="hidden" name="status" value="<?= e($filters['status']) ?>">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-8">
                <label for="busca" class="form-label">Pesquisar no estoque</label>
                <input
                    type="search"
                    class="form-control"
                    id="busca"
                    name="busca"
                    value="<?= e($filters['busca']) ?>"
                    placeholder="Nome do item, código ou origem"
                    maxlength="180"
                >
            </div>
            <div class="col-12 col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-outline-secondary flex-grow-1">Pesquisar</button>
                <?php if ($hasFilters): ?>
                    <a href="<?= e(url('/admin/loja/estoque')) ?>" class="btn btn-outline-secondary">Limpar</a>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<div class="loja-filter-pills" role="group" aria-label="Filtrar estoque por situação">
    <?php foreach ($statusOptions as $value => $label): ?>
        <?php
        $query = array_filter([
            'status' => $value,
            'busca' => $filters['busca'],
        ], static fn (string $item): bool => $item !== '');
        $href = url('/admin/loja/estoque' . ($query === [] ? '' : '?' . http_build_query($query)));
        ?>
        <a
            href="<?= e($href) ?>"
            class="loja-filter-pill<?= $filters['status'] === $value ? ' active' : '' ?>"
            <?= $filters['status'] === $value ? 'aria-current="page"' : '' ?>
        >
            <?= e($label) ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="loja-summary-grid loja-summary-grid-6">
    <article class="loja-summary-card">
        <span class="loja-summary-icon"><i class="bi bi-box-seam" aria-hidden="true"></i></span>
        <div>
            <span class="loja-summary-label">Itens cadastrados</span>
            <strong class="loja-summary-value"><?= (int) ($resumo['total_itens'] ?? 0) ?></strong>
        </div>
    </article>
    <article class="loja-summary-card">
        <span class="loja-summary-icon"><i class="bi bi-boxes" aria-hidden="true"></i></span>
        <div>
            <span class="loja-summary-label">Unidades totais</span>
            <strong class="loja-summary-value"><?= (int) ($resumo['total_unidades'] ?? 0) ?></strong>
        </div>
    </article>
    <article class="loja-summary-card">
        <span class="loja-summary-icon"><i class="bi bi-check2-circle" aria-hidden="true"></i></span>
        <div>
            <span class="loja-summary-label">Disponíveis</span>
            <strong class="loja-summary-value"><?= (int) ($resumo['unidades_disponivel'] ?? 0) ?></strong>
        </div>
    </article>
    <article class="loja-summary-card">
        <span class="loja-summary-icon"><i class="bi bi-truck" aria-hidden="true"></i></span>
        <div>
            <span class="loja-summary-label">Em transporte</span>
            <strong class="loja-summary-value"><?= (int) ($resumo['unidades_em_transporte'] ?? 0) ?></strong>
        </div>
    </article>
    <article class="loja-summary-card">
        <span class="loja-summary-icon"><i class="bi bi-calendar2-check" aria-hidden="true"></i></span>
        <div>
            <span class="loja-summary-label">Em pré-venda</span>
            <strong class="loja-summary-value"><?= (int) ($resumo['unidades_pre_venda'] ?? 0) ?></strong>
        </div>
    </article>
    <article class="loja-summary-card">
        <span class="loja-summary-icon"><i class="bi bi-calculator" aria-hidden="true"></i></span>
        <div>
            <span class="loja-summary-label">Custo real total</span>
            <strong class="loja-summary-value"><?= e(format_money($resumo['valor_total_real'] ?? 0)) ?></strong>
        </div>
    </article>
</div>

<section class="panel-card mt-3">
    <div class="panel-card-head">
        <div>
            <h2 class="panel-card-title">Itens em estoque</h2>
            <p class="panel-card-subtitle mb-0">Todos os itens registrados nas entradas de produtos.</p>
        </div>
        <span class="badge-soft"><?= (int) $result['total'] ?> registro(s)</span>
    </div>

    <?php if ($items === []): ?>
        <div class="empty-state">
            <i class="bi bi-boxes" aria-hidden="true"></i>
            <p class="mb-1">
                <?= $hasFilters ? 'Nenhum item encontrado para os filtros.' : 'Nenhum item cadastrado no estoque.' ?>
            </p>
            <span>
                <?= $hasFilters
                    ? 'Ajuste a pesquisa ou selecione outra situação.'
                    : 'Cadastre itens em uma entrada de produtos para vê-los aqui.' ?>
            </span>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table admin-table loja-table align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">Item</th>
                        <th scope="col">Origem / entrada</th>
                        <th scope="col">Modo</th>
                        <th scope="col">Situação</th>
                        <th scope="col">Qtd.</th>
                        <th scope="col">Custo real</th>
                        <th scope="col">Fornecedor</th>
                        <th scope="col">Cadastro</th>
                        <th scope="col" class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <?php
                        $entryId = (int) $item['entrada_id'];
                        $isPrevisao = $item['entrada_modo'] === 'previsao';
                        $semCusto = $isPrevisao && $item['fornecedor_selecionado'] === null;
                        ?>
                        <tr>
                            <td>
                                <strong class="d-block"><?= e((string) $item['nome']) ?></strong>
                                <?php if (!empty($item['codigo'])): ?>
                                    <span class="small text-muted">Código: <?= e((string) $item['codigo']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= e(url('/admin/loja/entradas/' . $entryId)) ?>">
                                    <?= e((string) $item['entrada_descricao']) ?>
                                </a>
                            </td>
                            <td><span class="badge-soft"><?= e(loja_mode_label((string) $item['entrada_modo'])) ?></span></td>
                            <td>
                                <span class="status-pill <?= e(loja_status_class((string) $item['status'])) ?>">
                                    <?= e(loja_status_label((string) $item['status'])) ?>
                                </span>
                            </td>
                            <td><?= (int) $item['quantidade'] ?></td>
                            <td>
                                <?php if ($semCusto): ?>
                                    <span class="badge-soft">Custo a definir</span>
                                <?php else: ?>
                                    <strong><?= e(format_money($item['custo_unitario_real'])) ?></strong>
                                    <span class="small text-muted">/ un.</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($item['fornecedor_selecionado'])): ?>
                                    <?= e((string) $item['fornecedor_selecionado']) ?>
                                <?php elseif ($isPrevisao && $item['menor_oferta'] !== null): ?>
                                    <span class="small text-muted">
                                        Menor ref.: <?= e(format_money($item['menor_oferta'])) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e(format_datetime($item['criado_em'], 'd/m/Y')) ?></td>
                            <td class="text-end">
                                <a href="<?= e(url('/admin/loja/entradas/' . $entryId)) ?>" class="btn btn-sm btn-outline-secondary">
                                    Ver entrada
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php
$pages = $result['pages'];
$page = $result['page'];
$total = $result['total'];
require __DIR__ . '/../partials/pagination.php';
?>
