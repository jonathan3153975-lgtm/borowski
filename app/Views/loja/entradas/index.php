<?php

declare(strict_types=1);

$result = $result ?? ['items' => [], 'total' => 0, 'page' => 1, 'pages' => 1];
$filters = $filters ?? ['modo' => '', 'recebido' => '', 'busca' => ''];
$items = $result['items'];
$hasFilters = $filters['modo'] !== '' || $filters['recebido'] !== '' || $filters['busca'] !== '';
?>
<?php require __DIR__ . '/../partials/tabs.php'; ?>

<div class="toolbar">
    <form method="get" action="<?= e(url('/admin/loja/entradas')) ?>" class="toolbar-filters" role="search">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-5">
                <label for="busca" class="form-label">Pesquisar</label>
                <input
                    type="search"
                    class="form-control"
                    id="busca"
                    name="busca"
                    value="<?= e($filters['busca']) ?>"
                    placeholder="Descrição, link ou rastreio"
                    maxlength="180"
                >
            </div>
            <div class="col-6 col-md-2">
                <label for="modo" class="form-label">Modo</label>
                <select class="form-select" id="modo" name="modo">
                    <option value="">Todos</option>
                    <option value="direta" <?= $filters['modo'] === 'direta' ? 'selected' : '' ?>>Compra direta</option>
                    <option value="previsao" <?= $filters['modo'] === 'previsao' ? 'selected' : '' ?>>Previsão</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label for="recebido" class="form-label">Recebimento</label>
                <select class="form-select" id="recebido" name="recebido">
                    <option value="">Todos</option>
                    <option value="1" <?= $filters['recebido'] === '1' ? 'selected' : '' ?>>Recebidas</option>
                    <option value="0" <?= $filters['recebido'] === '0' ? 'selected' : '' ?>>Não recebidas</option>
                </select>
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-outline-secondary flex-grow-1">Filtrar</button>
                <?php if ($hasFilters): ?>
                    <a href="<?= e(url('/admin/loja/entradas')) ?>" class="btn btn-outline-secondary">Limpar</a>
                <?php endif; ?>
            </div>
        </div>
    </form>

    <a href="<?= e(url('/admin/loja/entradas/criar')) ?>" class="btn btn-brand">
        <i class="bi bi-plus-lg" aria-hidden="true"></i>
        Nova entrada
    </a>
</div>

<section class="panel-card">
    <div class="panel-card-head">
        <div>
            <h2 class="panel-card-title">Entradas de produtos</h2>
            <p class="panel-card-subtitle mb-0">Compras diretas e previsões de compra cadastradas.</p>
        </div>
        <span class="badge-soft"><?= (int) $result['total'] ?> registro(s)</span>
    </div>

    <?php if ($items === []): ?>
        <div class="empty-state">
            <i class="bi bi-box-seam" aria-hidden="true"></i>
            <p class="mb-1">
                <?= $hasFilters ? 'Nenhuma entrada encontrada para os filtros.' : 'Nenhuma entrada cadastrada ainda.' ?>
            </p>
            <span>
                <?= $hasFilters
                    ? 'Ajuste os filtros ou limpe a pesquisa.'
                    : 'Crie a primeira compra direta ou previsão de compra.' ?>
            </span>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table admin-table loja-table align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">Entrada</th>
                        <th scope="col">Modo</th>
                        <th scope="col">Situação</th>
                        <th scope="col">Itens</th>
                        <th scope="col">Custos adicionais</th>
                        <th scope="col">Total real</th>
                        <th scope="col">Data</th>
                        <th scope="col" class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <?php $entryId = (int) $item['id']; ?>
                        <tr>
                            <td>
                                <a href="<?= e(url('/admin/loja/entradas/' . $entryId)) ?>" class="loja-entry-link">
                                    <strong><?= e((string) $item['descricao_origem']) ?></strong>
                                </a>
                                <div class="small text-muted">
                                    <?= $item['modo'] === 'direta'
                                        ? ($item['codigo_rastreio'] ? 'Rastreio: ' . e((string) $item['codigo_rastreio']) : 'Compra online')
                                        : 'Itens para pré-venda' ?>
                                </div>
                            </td>
                            <td><span class="badge-soft"><?= e(loja_mode_label((string) $item['modo'])) ?></span></td>
                            <td>
                                <?php if ($item['modo'] === 'direta'): ?>
                                    <span class="status-pill <?= (int) $item['recebido'] === 1 ? 'status-pill-active' : 'status-pill-transit' ?>">
                                        <?= (int) $item['recebido'] === 1 ? 'Recebida' : 'Em transporte' ?>
                                    </span>
                                <?php else: ?>
                                    <span class="status-pill status-pill-pre">Em pré-venda</span>
                                <?php endif; ?>
                            </td>
                            <td><?= (int) $item['itens_count'] ?></td>
                            <td><?= e(format_money($item['custos_total'])) ?></td>
                            <td><strong><?= e(format_money($item['total_real'])) ?></strong></td>
                            <td><?= e(format_datetime($item['criado_em'])) ?></td>
                            <td class="text-end">
                                <div class="table-actions">
                                    <a href="<?= e(url('/admin/loja/entradas/' . $entryId)) ?>" class="btn btn-sm btn-outline-secondary">
                                        Abrir
                                    </a>
                                    <form method="post" action="<?= e(url('/admin/loja/entradas/' . $entryId . '/excluir')) ?>">
                                        <?= csrf_field() ?>
                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-outline-danger"
                                            data-confirm="<?= e('Excluir a entrada "' . $item['descricao_origem'] . '"? Itens, custos e ofertas vinculados também serão removidos.') ?>"
                                        >
                                            Excluir
                                        </button>
                                    </form>
                                </div>
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
