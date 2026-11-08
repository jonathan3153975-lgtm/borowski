<?php

declare(strict_types=1);

$result = $result ?? ['items' => [], 'total' => 0, 'page' => 1, 'pages' => 1];
$filters = $filters ?? ['modo' => '', 'recebido' => '', 'busca' => ''];
$items = $result['items'];
$busca = (string) ($filters['busca'] ?? '');
$modo = (string) ($filters['modo'] ?? '');
$recebido = $modo === 'direta' ? (string) ($filters['recebido'] ?? '') : '';
$hasFilters = $busca !== '' || $modo !== '' || $recebido !== '';

$baseQuery = array_filter([
    'busca' => $busca,
    'modo' => $modo,
    'recebido' => $recebido,
], static fn (string $value): bool => $value !== '');

$makeUrl = static function (array $overrides = []) use ($baseQuery): string {
    $query = array_filter(
        array_merge($baseQuery, $overrides),
        static fn (mixed $value): bool => $value !== null && $value !== ''
    );

    return url('/admin/loja/entradas' . ($query === [] ? '' : '?' . http_build_query($query)));
};
?>
<div class="loja-sticky-top" data-loja-sticky-top>
    <?php require __DIR__ . '/../partials/tabs.php'; ?>

    <div class="loja-entry-toolbar">
        <form method="get" action="<?= e(url('/admin/loja/entradas')) ?>" class="loja-entry-search" role="search">
            <?php if ($modo !== ''): ?>
                <input type="hidden" name="modo" value="<?= e($modo) ?>">
            <?php endif; ?>
            <?php if ($modo === 'direta' && $recebido !== ''): ?>
                <input type="hidden" name="recebido" value="<?= e($recebido) ?>">
            <?php endif; ?>

            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                <input
                    type="search"
                    class="form-control"
                    id="busca"
                    name="busca"
                    value="<?= e($busca) ?>"
                    placeholder="Pesquisar por descrição, link ou rastreio"
                    maxlength="180"
                    data-live-filter
                >
                <?php if ($busca !== ''): ?>
                    <a href="<?= e($makeUrl(['busca' => null])) ?>" class="btn btn-outline-secondary">
                        Limpar
                    </a>
                <?php endif; ?>
            </div>
        </form>

        <button
            type="button"
            class="btn btn-outline-secondary loja-compact-toggle d-lg-none"
            data-loja-compact-toggle
            aria-expanded="false"
            aria-label="Mostrar opções"
        >
            <i class="bi bi-chevron-down" aria-hidden="true"></i>
        </button>

        <button
            type="button"
            class="btn btn-brand loja-entry-new loja-hide-on-compact"
            data-bs-toggle="modal"
            data-bs-target="#newEntryModal"
        >
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            Nova entrada
        </button>
    </div>

    <div class="loja-entry-filters loja-hide-on-compact">
        <div class="loja-filter-group">
            <span class="loja-filter-label">Modo</span>
            <div class="loja-filter-pills" role="group" aria-label="Filtrar por modo">
                <a
                    href="<?= e($makeUrl(['modo' => null, 'recebido' => null])) ?>"
                    class="loja-filter-pill<?= $modo === '' ? ' active' : '' ?>"
                    <?= $modo === '' ? 'aria-current="page"' : '' ?>
                >Todos</a>
                <a
                    href="<?= e($makeUrl(['modo' => 'direta', 'recebido' => null])) ?>"
                    class="loja-filter-pill<?= $modo === 'direta' ? ' active' : '' ?>"
                    <?= $modo === 'direta' ? 'aria-current="page"' : '' ?>
                >Compra direta</a>
            </div>
        </div>

        <div class="loja-filter-group" title="Disponível apenas para compra direta">
            <span class="loja-filter-label">Recebimento</span>
            <div class="loja-filter-pills" role="group" aria-label="Filtrar por recebimento">
                <?php if ($modo === 'direta'): ?>
                    <a
                        href="<?= e($makeUrl(['recebido' => null])) ?>"
                        class="loja-filter-pill<?= $recebido === '' ? ' active' : '' ?>"
                        <?= $recebido === '' ? 'aria-current="page"' : '' ?>
                    >Todos</a>
                    <a
                        href="<?= e($makeUrl(['recebido' => '1'])) ?>"
                        class="loja-filter-pill<?= $recebido === '1' ? ' active' : '' ?>"
                        <?= $recebido === '1' ? 'aria-current="page"' : '' ?>
                    >Recebidas</a>
                    <a
                        href="<?= e($makeUrl(['recebido' => '0'])) ?>"
                        class="loja-filter-pill<?= $recebido === '0' ? ' active' : '' ?>"
                        <?= $recebido === '0' ? 'aria-current="page"' : '' ?>
                    >Não recebidas</a>
                <?php else: ?>
                    <span class="loja-filter-pill disabled" aria-disabled="true">Todos</span>
                    <span class="loja-filter-pill disabled" aria-disabled="true">Recebidas</span>
                    <span class="loja-filter-pill disabled" aria-disabled="true">Não recebidas</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div
    class="modal fade"
    id="newEntryModal"
    tabindex="-1"
    aria-labelledby="newEntryModalLabel"
    aria-hidden="true"
    data-auto-open="<?= old('form_context') === 'entry_create' ? '1' : '0' ?>"
>
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title h5" id="newEntryModalLabel">Nova entrada</h3>
                    <p class="modal-subtitle mb-0">
                        Escolha o modo de entrada. Os itens e custos serão adicionados na próxima tela.
                    </p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <?php require __DIR__ . '/../partials/entrada-form.php'; ?>
            </div>
        </div>
    </div>
</div>

<section class="panel-card loja-entries-list">
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
                    ? 'Ajuste a pesquisa ou selecione outro filtro.'
                    : 'Crie a primeira compra direta ou previsão de compra.' ?>
            </span>
        </div>
    <?php else: ?>
        <div class="d-none d-lg-block table-responsive">
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
                        <tr data-filter-row>
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

        <div class="d-lg-none loja-entry-cards">
            <?php foreach ($items as $item): ?>
                <?php $entryId = (int) $item['id']; ?>
                <article class="loja-entry-card" data-filter-row>
                    <div class="loja-entry-card-top">
                        <div class="loja-entry-card-info">
                            <a href="<?= e(url('/admin/loja/entradas/' . $entryId)) ?>" class="loja-entry-card-title">
                                <?= e((string) $item['descricao_origem']) ?>
                            </a>
                            <span class="loja-entry-card-date">
                                <?= e(format_datetime($item['criado_em'], 'd/m/Y H:i')) ?>
                            </span>
                        </div>
                        <span class="badge-soft"><?= e(loja_mode_label((string) $item['modo'])) ?></span>
                    </div>

                    <div class="loja-entry-card-status">
                        <?php if ($item['modo'] === 'direta'): ?>
                            <span class="status-pill <?= (int) $item['recebido'] === 1 ? 'status-pill-active' : 'status-pill-transit' ?>">
                                <?= (int) $item['recebido'] === 1 ? 'Recebida' : 'Em transporte' ?>
                            </span>
                        <?php else: ?>
                            <span class="status-pill status-pill-pre">Em pré-venda</span>
                        <?php endif; ?>
                        <?php if (!empty($item['codigo_rastreio'])): ?>
                            <span class="loja-entry-card-tracking"><?= e((string) $item['codigo_rastreio']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="loja-entry-card-stats">
                        <div>
                            <span>Itens</span>
                            <strong><?= (int) $item['itens_count'] ?></strong>
                        </div>
                        <div>
                            <span>Custos adicionais</span>
                            <strong><?= e(format_money($item['custos_total'])) ?></strong>
                        </div>
                        <div>
                            <span>Total real</span>
                            <strong><?= e(format_money($item['total_real'])) ?></strong>
                        </div>
                    </div>

                    <div class="loja-entry-card-actions">
                        <a href="<?= e(url('/admin/loja/entradas/' . $entryId)) ?>" class="btn btn-sm btn-outline-secondary">
                            Abrir entrada
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
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php
$pages = $result['pages'];
$page = $result['page'];
$total = $result['total'];
require __DIR__ . '/../partials/pagination.php';
?>
