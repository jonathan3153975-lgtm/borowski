<?php

declare(strict_types=1);

$result = $result ?? ['items' => [], 'total' => 0, 'page' => 1, 'pages' => 1, 'resumo' => []];
$filters = $filters ?? ['status' => '', 'publicacao' => '', 'busca' => ''];
$items = $result['items'];
$resumo = $result['resumo'];
$busca = (string) ($filters['busca'] ?? '');
$status = (string) ($filters['status'] ?? '');
$publicacao = (string) ($filters['publicacao'] ?? '');
$publicacoesPorItem = $publicacoesPorItem ?? [];
$reservasPorItem = $reservasPorItem ?? [];
$itens = $itens ?? [];
$mapa = $mapa ?? [];
$canais = $canais ?? [];
$opcoes = $opcoes ?? [];
$hasFilters = $busca !== '' || $status !== '' || $publicacao !== '';
$statusOptions = [
    '' => 'Todos',
    'disponivel' => 'Disponível em estoque',
    'em_transporte' => 'Em transporte',
    'pre_venda' => 'Em pré-venda',
];
$publicacaoOptions = [
    '' => 'Todos',
    'publicado' => 'Publicados',
    'nao_publicado' => 'Não publicados',
];
$baseQuery = array_filter([
    'status' => $status,
    'publicacao' => $publicacao,
    'busca' => $busca,
], static fn (string $value): bool => $value !== '');
$makeUrl = static function (array $overrides = []) use ($baseQuery): string {
    $query = array_filter(
        array_merge($baseQuery, $overrides),
        static fn (mixed $value): bool => $value !== null && $value !== ''
    );

    return url('/admin/loja/estoque' . ($query === [] ? '' : '?' . http_build_query($query)));
};

$rows = [];

foreach ($items as $item) {
    $itemId = (int) $item['id'];
    $entryId = (int) ($item['entrada_id'] ?? 0);
    $preVenda = (string) $item['status'] === 'pre_venda';
    $semCusto = $preVenda && $item['fornecedor_selecionado'] === null;
    $total = (int) $item['quantidade'];
    $resumoReserva = $reservasPorItem[$itemId] ?? ['reservada' => 0, 'vendida' => 0, 'valor_reservado' => 0.0, 'arrecadado' => 0.0];
    $disponivel = in_array((string) $item['status'], ['disponivel', 'pre_venda'], true)
        ? max(0, $total - $resumoReserva['reservada'] - $resumoReserva['vendida'])
        : 0;
    $custo = (float) $item['custo_unitario_real'];
    $custoTotal = $custo * $total;
    $valorEstoque = $custo * $disponivel;
    $pubs = $publicacoesPorItem[$itemId] ?? [];
    $publicadoQtd = 0;

    foreach ($pubs as $pub) {
        $publicadoQtd += (int) ($pub['quantidade'] ?? 0);
    }

    $restantePublicar = max(0, $total - $publicadoQtd);
    $canaisCobertos = array_map(static fn (array $pub): string => (string) $pub['slug'], $pubs);
    $podePublicar = in_array((string) $item['status'], ['disponivel', 'pre_venda'], true)
        && $disponivel > 0
        && array_diff(['loja', 'telegram'], $canaisCobertos) !== [];
    $precoPublicado = null;
    $reservaPublicada = null;

    foreach ($pubs as $pub) {
        if ($pub['slug'] === 'loja' && $precoPublicado === null) {
            $precoPublicado = $pub['preco_venda'];
            $reservaPublicada = $pub['preco_reserva'] ?? null;
        }
    }

    if ($precoPublicado === null && $pubs !== []) {
        $precoPublicado = $pubs[0]['preco_venda'];
        $reservaPublicada = $pubs[0]['preco_reserva'] ?? null;
    }

    $stockItemMeta = array_filter([
        $item['categoria_nome'] ?? null,
        $item['marca_nome'] ?? null,
        $item['escala_nome'] ?? null,
        !empty($item['ano_fabricacao']) ? 'Ano ' . $item['ano_fabricacao'] : null,
    ]);

    $viewData = [
        'nome' => (string) $item['nome'],
        'sku' => (string) ($item['codigo'] ?? ''),
        'descricao' => (string) ($item['descricao'] ?? ''),
        'meta' => implode(' · ', $stockItemMeta),
        'imagem' => !empty($item['imagem_id']) ? (string) item_imagem_url((int) $item['imagem_id']) : '',
        'canais' => implode(', ', array_map(static fn (array $pub): string => (string) $pub['nome'], $pubs)),
        'preco' => $precoPublicado !== null ? format_money($precoPublicado) : '',
        'reserva' => $reservaPublicada !== null ? format_money($reservaPublicada) : '',
        'custo' => e(format_money($custo)),
        'custoTotal' => e(format_money($custoTotal)),
        'total' => (string) $total,
        'disponivel' => (string) $disponivel,
        'reservada' => (string) $resumoReserva['reservada'],
        'vendida' => (string) $resumoReserva['vendida'],
        'valorEstoque' => e(format_money($valorEstoque)),
        'valorReservado' => e(format_money($resumoReserva['valor_reservado'])),
        'arrecadado' => e(format_money($resumoReserva['arrecadado'])),
    ];

    $rows[] = [
        'item' => $item,
        'itemId' => $itemId,
        'viewData' => $viewData,
        'entryId' => $entryId,
        'preVenda' => $preVenda,
        'semCusto' => $semCusto,
        'modoLabel' => $entryId === 0 ? 'Sob demanda' : loja_mode_label((string) ($item['entrada_modo'] ?? 'direta')),
        'total' => $total,
        'disponivel' => $disponivel,
        'resumoReserva' => $resumoReserva,
        'custo' => $custo,
        'custoTotal' => $custoTotal,
        'valorEstoque' => $valorEstoque,
        'pubs' => $pubs,
        'restantePublicar' => $restantePublicar,
        'podePublicar' => $podePublicar,
        'canaisNomes' => implode(', ', array_map(static fn (array $pub): string => (string) $pub['nome'], $pubs)),
        'precoExibicao' => $precoPublicado !== null ? format_money($precoPublicado) : '',
        'reservaExibicao' => $reservaPublicada !== null ? format_money($reservaPublicada) : '',
        'meta' => $stockItemMeta,
    ];
}
?>
<div class="loja-sticky-top" data-loja-sticky-top>
    <?php require __DIR__ . '/../partials/tabs.php'; ?>

    <div class="loja-stock-summary-wrap">
        <div class="loja-summary-grid loja-summary-grid-6 loja-stock-summary">
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
    </div>

    <div class="loja-entry-toolbar">
        <form method="get" action="<?= e(url('/admin/loja/estoque')) ?>" class="loja-entry-search" role="search">
            <?php if ($status !== ''): ?>
                <input type="hidden" name="status" value="<?= e($status) ?>">
            <?php endif; ?>
            <?php if ($publicacao !== ''): ?>
                <input type="hidden" name="publicacao" value="<?= e($publicacao) ?>">
            <?php endif; ?>

            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                <input
                    type="search"
                    class="form-control"
                    id="busca"
                    name="busca"
                    value="<?= e($busca) ?>"
                    placeholder="Pesquisar por item, código ou origem"
                    maxlength="180"
                    data-live-filter
                >
                <?php if ($hasFilters): ?>
                    <a href="<?= e(url('/admin/loja/estoque')) ?>" class="btn btn-outline-secondary">Limpar</a>
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
            class="btn btn-brand loja-hide-on-compact"
            data-bs-toggle="modal"
            data-bs-target="#demandaModal"
        >
            <i class="bi bi-calendar2-plus" aria-hidden="true"></i>
            Item sob demanda
        </button>
    </div>

    <div class="loja-entry-filters loja-hide-on-compact">
        <div class="loja-filter-group">
            <span class="loja-filter-label">Situação</span>
            <div class="loja-filter-pills loja-stock-filters" role="group" aria-label="Filtrar estoque por situação">
                <?php foreach ($statusOptions as $value => $label): ?>
                    <a
                        href="<?= e($makeUrl(['status' => $value === '' ? null : $value])) ?>"
                        class="loja-filter-pill<?= $status === $value ? ' active' : '' ?>"
                        <?= $status === $value ? 'aria-current="page"' : '' ?>
                    >
                        <?= e($label) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="loja-filter-group">
            <span class="loja-filter-label">Publicação</span>
            <div class="loja-filter-pills loja-stock-filters" role="group" aria-label="Filtrar itens publicados">
                <?php foreach ($publicacaoOptions as $value => $label): ?>
                    <a
                        href="<?= e($makeUrl(['publicacao' => $value === '' ? null : $value])) ?>"
                        class="loja-filter-pill<?= $publicacao === $value ? ' active' : '' ?>"
                        <?= $publicacao === $value ? 'aria-current="page"' : '' ?>
                    >
                        <?= e($label) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<section class="panel-card mt-3">
    <div class="panel-card-head">
        <div>
            <h2 class="panel-card-title">Itens em estoque</h2>
            <p class="panel-card-subtitle mb-0">Itens de compras diretas e itens sob demanda (pré-venda).</p>
        </div>
        <span class="badge-soft"><?= (int) $result['total'] ?> registro(s)</span>
    </div>

    <?php if ($rows === []): ?>
        <div class="empty-state">
            <i class="bi bi-boxes" aria-hidden="true"></i>
            <p class="mb-1">
                <?= $hasFilters ? 'Nenhum item encontrado para os filtros.' : 'Nenhum item cadastrado no estoque.' ?>
            </p>
            <span>
                <?= $hasFilters
                    ? 'Ajuste a pesquisa ou selecione outro filtro.'
                    : 'Cadastre itens em uma entrada de produtos ou como item sob demanda.' ?>
            </span>
        </div>
    <?php else: ?>
        <div class="d-none d-lg-block table-responsive">
            <table class="table admin-table loja-table align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">Foto</th>
                        <th scope="col">Item</th>
                        <th scope="col">Modo / Situação</th>
                        <th scope="col">Quantidade</th>
                        <th scope="col">Custo</th>
                        <th scope="col">Cadastro</th>
                        <th scope="col" class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <?php $item = $row['item']; ?>
                        <tr data-filter-row>
                            <td>
                                <span class="loja-item-thumb">
                                    <?php if (!empty($item['imagem_id'])): ?>
                                        <img src="<?= e((string) item_imagem_url((int) $item['imagem_id'])) ?>" alt="<?= e((string) $item['nome']) ?>" loading="lazy">
                                    <?php else: ?>
                                        <i class="bi bi-image" aria-hidden="true"></i>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td>
                                <div class="loja-item-cell-info">
                                    <strong class="d-block"><?= e((string) $item['nome']) ?></strong>
                                    <span class="small text-muted d-block">
                                        SKU: <?= !empty($item['codigo']) ? e((string) $item['codigo']) : '—' ?>
                                        <?php if ((int) ($item['imagens_count'] ?? 0) > 0): ?>
                                            · <?= (int) $item['imagens_count'] ?> foto(s)
                                        <?php endif; ?>
                                    </span>
                                    <span class="small text-muted d-block">
                                        Origem:
                                        <?php if ($row['entryId'] > 0): ?>
                                            <a href="<?= e(url('/admin/loja/entradas/' . $row['entryId'])) ?>"><?= e((string) ($item['entrada_descricao'] ?? '')) ?></a>
                                        <?php else: ?>
                                            <span class="text-muted">Item sob demanda</span>
                                        <?php endif; ?>
                                    </span>
                                    <?php if ($row['meta'] !== []): ?>
                                        <span class="small text-muted d-block"><?= e(implode(' · ', $row['meta'])) ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <span class="badge-soft"><?= e($row['modoLabel']) ?></span>
                                    <span class="status-pill <?= e(loja_status_class((string) $item['status'])) ?>">
                                        <?= e(loja_status_label((string) $item['status'])) ?>
                                    </span>
                                    <?php if ($row['pubs'] !== []): ?>
                                        <div class="d-flex flex-wrap gap-1 mt-1">
                                            <?php foreach ($row['pubs'] as $pub): ?>
                                                <span class="badge-soft badge-canal" title="Publicado: <?= e((string) $pub['nome']) ?>">
                                                    <i class="bi <?= $pub['slug'] === 'telegram' ? 'bi-telegram' : ($pub['slug'] === 'whatsapp' ? 'bi-whatsapp' : 'bi-shop') ?>" aria-hidden="true"></i>
                                                    <?= e((string) $pub['nome']) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span><strong><?= (int) $row['disponivel'] ?></strong> <span class="small text-muted">disp.</span></span>
                                    <span class="small text-muted"><?= (int) $row['total'] ?> total</span>
                                </div>
                            </td>
                            <td>
                                <?php if ($row['semCusto']): ?>
                                    <span class="badge-soft">A definir</span>
                                <?php else: ?>
                                    <strong><?= e(format_money($row['custo'])) ?></strong>
                                    <span class="small text-muted d-block">Total: <?= e(format_money($row['custoTotal'])) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= e(format_datetime($item['criado_em'], 'd/m/Y')) ?></td>
                            <td class="text-end">
                                <div class="table-actions">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-secondary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#viewItemModal"
                                        title="Ver item"
                                        aria-label="Ver item"
                                        <?php foreach ($row['viewData'] as $key => $value): ?>
                                            data-<?= e($key) ?>="<?= e($value) ?>"
                                        <?php endforeach; ?>
                                    >
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </button>

                                    <?php if ($row['entryId'] > 0): ?>
                                        <a
                                            href="<?= e(url('/admin/loja/entradas/' . $row['entryId'])) ?>"
                                            class="btn btn-sm btn-outline-secondary"
                                            title="Ver entrada"
                                            aria-label="Ver entrada"
                                        >
                                            <i class="bi bi-arrow-right-circle" aria-hidden="true"></i>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($row['podePublicar']): ?>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-brand"
                                            data-bs-toggle="modal"
                                            data-bs-target="#publishModal"
                                            data-publish-item="<?= (int) $row['itemId'] ?>"
                                            title="Publicar"
                                            aria-label="Publicar"
                                        >
                                            <i class="bi bi-megaphone" aria-hidden="true"></i>
                                        </button>
                                    <?php endif; ?>

                                    <?php if ($row['pubs'] !== []): ?>
                                        <a
                                            href="<?= e(url('/admin/loja/estoque/publicados')) ?>"
                                            class="btn btn-sm btn-outline-success"
                                            title="Ver publicações"
                                            aria-label="Ver publicações"
                                        >
                                            <i class="bi bi-broadcast" aria-hidden="true"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="d-lg-none loja-entry-cards">
            <?php foreach ($rows as $row): ?>
                <?php $item = $row['item']; ?>
                <article class="loja-entry-card" data-filter-row>
                    <div class="loja-entry-card-top">
                        <div class="loja-item-card-main">
                            <span class="loja-item-thumb">
                                <?php if (!empty($item['imagem_id'])): ?>
                                    <img src="<?= e((string) item_imagem_url((int) $item['imagem_id'])) ?>" alt="<?= e((string) $item['nome']) ?>" loading="lazy">
                                <?php else: ?>
                                    <i class="bi bi-image" aria-hidden="true"></i>
                                <?php endif; ?>
                            </span>
                            <div class="loja-entry-card-info">
                                <span class="loja-entry-card-title"><?= e((string) $item['nome']) ?></span>
                                <span class="loja-entry-card-date">
                                    SKU: <?= !empty($item['codigo']) ? e((string) $item['codigo']) : '—' ?>
                                </span>
                            </div>
                        </div>
                        <span class="badge-soft"><?= e($row['modoLabel']) ?></span>
                    </div>

                    <div class="loja-entry-card-status">
                        <span class="status-pill <?= e(loja_status_class((string) $item['status'])) ?>">
                            <?= e(loja_status_label((string) $item['status'])) ?>
                        </span>
                        <?php foreach ($row['pubs'] as $pub): ?>
                            <span class="badge-soft badge-canal">
                                <i class="bi <?= $pub['slug'] === 'telegram' ? 'bi-telegram' : ($pub['slug'] === 'whatsapp' ? 'bi-whatsapp' : 'bi-shop') ?>" aria-hidden="true"></i>
                                <?= e((string) $pub['nome']) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>

                    <div class="loja-entry-card-stats">
                        <div>
                            <span>Disponível / total</span>
                            <strong><?= (int) $row['disponivel'] ?> / <?= (int) $row['total'] ?></strong>
                        </div>
                        <div>
                            <span>Custo/un.</span>
                            <strong><?= $row['semCusto'] ? 'A definir' : e(format_money($row['custo'])) ?></strong>
                        </div>
                        <div>
                            <span>Total em estoque</span>
                            <strong><?= $row['semCusto'] ? 'A definir' : e(format_money($row['custoTotal'])) ?></strong>
                        </div>
                    </div>

                    <div class="loja-entry-card-actions">
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary"
                            data-bs-toggle="modal"
                            data-bs-target="#viewItemModal"
                            <?php foreach ($row['viewData'] as $key => $value): ?>
                                data-<?= e($key) ?>="<?= e($value) ?>"
                            <?php endforeach; ?>
                        >
                            <i class="bi bi-eye" aria-hidden="true"></i>
                            Ver item
                        </button>
                        <?php if ($row['podePublicar']): ?>
                            <button
                                type="button"
                                class="btn btn-sm btn-brand"
                                data-bs-toggle="modal"
                                data-bs-target="#publishModal"
                                data-publish-item="<?= (int) $row['itemId'] ?>"
                            >
                                <i class="bi bi-megaphone" aria-hidden="true"></i>
                                Publicar
                            </button>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<div class="modal fade" id="viewItemModal" tabindex="-1" aria-labelledby="viewItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title h5" id="viewItemModalLabel">Visualizar item</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="publish-preview">
                    <div class="publish-preview-media">
                        <img data-view-image src="" alt="" hidden>
                        <i class="bi bi-image" data-view-placeholder aria-hidden="true"></i>
                    </div>
                    <div class="publish-preview-body">
                        <span class="publish-preview-eyebrow">Anúncio</span>
                        <h3 class="publish-preview-title" data-view-nome></h3>
                        <p class="publish-preview-meta" data-view-meta></p>
                        <p class="publish-preview-desc" data-view-descricao></p>
                        <div class="d-flex flex-wrap gap-1 mb-2" data-view-canais></div>
                        <p class="publish-preview-price" data-view-preco></p>
                        <p class="small text-muted mb-0" data-view-reserva></p>
                    </div>
                </div>

                <div class="row g-2 mt-3 small">
                    <div class="col-6 col-md-4">
                        <div class="border rounded p-2">
                            <span class="text-muted d-block">Total de unidades</span>
                            <strong data-view-total>—</strong>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="border rounded p-2">
                            <span class="text-muted d-block">Disponível</span>
                            <strong data-view-disponivel>—</strong>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="border rounded p-2">
                            <span class="text-muted d-block">Reservadas</span>
                            <strong data-view-reservada>—</strong>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="border rounded p-2">
                            <span class="text-muted d-block">Vendidas</span>
                            <strong data-view-vendida>—</strong>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="border rounded p-2">
                            <span class="text-muted d-block">Custo total</span>
                            <strong data-view-custo-total>—</strong>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="border rounded p-2">
                            <span class="text-muted d-block">Valor em estoque</span>
                            <strong data-view-valor-estoque>—</strong>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="border rounded p-2">
                            <span class="text-muted d-block">Valor reservado</span>
                            <strong data-view-valor-reservado>—</strong>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="border rounded p-2">
                            <span class="text-muted d-block">Total arrecadado</span>
                            <strong data-view-arrecadado>—</strong>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="border rounded p-2">
                            <span class="text-muted d-block">Custo/un.</span>
                            <strong data-view-custo>—</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$publicarId = 0;
require __DIR__ . '/partials/publish-modal.php';
require __DIR__ . '/partials/demanda-modal.php';
?>

<?php
$pages = $result['pages'];
$page = $result['page'];
$total = $result['total'];
require __DIR__ . '/../partials/pagination.php';
?>
