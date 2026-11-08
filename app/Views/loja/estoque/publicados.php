<?php

declare(strict_types=1);

$publicacoes = $publicacoes ?? [];
$busca = $busca ?? '';
$ordem = $ordem ?? 'vistos';
$mapa = $mapa ?? [];
$imagensPorItem = $imagensPorItem ?? [];

$porItem = [];

foreach ($publicacoes as $publicacao) {
    $porItem[(int) $publicacao['item_id']][] = $publicacao;
}

$rows = [];

foreach ($porItem as $itemId => $publicacoesItem) {
    usort($publicacoesItem, static function (array $a, array $b): int {
        $aLoja = (string) ($a['canal_slug'] ?? '') === 'loja' ? 1 : 0;
        $bLoja = (string) ($b['canal_slug'] ?? '') === 'loja' ? 1 : 0;

        if ($aLoja !== $bLoja) {
            return $bLoja <=> $aLoja;
        }

        return (int) ($b['id'] ?? 0) <=> (int) ($a['id'] ?? 0);
    });

    $principal = $publicacoesItem[0];
    $plataformas = [];
    $vendidosTotal = 0;
    $reservasAbertasTotal = 0;

    foreach ($publicacoesItem as $pub) {
        $plataformas[] = [
            'publicacao_id' => (int) $pub['id'],
            'slug' => (string) $pub['canal_slug'],
            'nome' => (string) $pub['canal_nome'],
            'quantidade' => (int) $pub['quantidade'],
            'reservas_abertas' => (int) ($pub['reservas_abertas'] ?? 0),
            'vendidos' => (int) ($pub['vendidos'] ?? 0),
            'status' => (string) $pub['status'],
            'publicado_em' => $pub['publicado_em'] ?? null,
        ];

        $vendidosTotal += (int) ($pub['vendidos'] ?? 0);
        $reservasAbertasTotal += (int) ($pub['reservas_abertas'] ?? 0);
    }

    $itemQuantidade = (int) ($principal['item_quantidade'] ?? $principal['quantidade'] ?? 0);
    $vendido = $itemQuantidade > 0 && $vendidosTotal >= $itemQuantidade;
    $preco = (float) $principal['preco_venda'];
    $custo = (float) $principal['custo_unitario_real'];
    $margem = $preco - $custo;
    $slug = (string) $principal['canal_slug'];

    $galeria = [];
    foreach (($imagensPorItem[$itemId] ?? []) as $imagem) {
        $galeria[] = [
            'url' => item_imagem_url((int) $imagem['id']),
            'principal' => (int) $imagem['imagem_principal'] === 1,
        ];
    }

    $canaisDoItem = array_map(
        static fn (array $plataforma): array => [
            'nome' => $plataforma['nome'],
            'slug' => $plataforma['slug'],
        ],
        $plataformas
    );

    $rows[] = [
        'itemId' => $itemId,
        'principal' => $principal,
        'publicacao_id' => (int) $principal['id'],
        'plataformas' => $plataformas,
        'temLoja' => in_array('loja', array_column($plataformas, 'slug'), true),
        'temTelegram' => in_array('telegram', array_column($plataformas, 'slug'), true),
        'vendidos' => $vendidosTotal,
        'item_quantidade' => $itemQuantidade,
        'reservas_abertas' => $reservasAbertasTotal,
        'vendido' => $vendido,
        'preco' => $preco,
        'custo' => $custo,
        'margem' => $margem,
        'margem_pct' => $custo > 0 ? ($margem / $custo) * 100 : null,
        'status' => (string) $principal['status'],
        'canalSlug' => $slug,
        'titulo' => trim((string) ($principal['titulo'] ?? '')) !== ''
            ? (string) $principal['titulo']
            : (string) $principal['item_nome'],
        'descricao' => trim((string) ($principal['descricao'] ?? '')) !== ''
            ? (string) $principal['descricao']
            : (string) ($principal['item_descricao'] ?? ''),
        'origem' => (int) ($principal['entrada_id'] ?? 0) > 0
            ? (string) ($principal['entrada_descricao'] ?? '')
            : 'Item sob demanda',
        'galeria' => $galeria,
        'canaisDoItem' => $canaisDoItem,
        'imagem' => !empty($principal['imagem_id'])
            ? item_imagem_url((int) $principal['imagem_id'])
            : null,
        'publicado_em' => $principal['publicado_em'] ?? null,
        'visitas' => (int) ($principal['visitas'] ?? 0),
    ];
}

usort($rows, static function (array $a, array $b) use ($ordem): int {
    if ($ordem === 'vistos') {
        $comparacao = $b['visitas'] <=> $a['visitas'];

        if ($comparacao !== 0) {
            return $comparacao;
        }
    }

    return strcmp((string) $b['publicado_em'], (string) $a['publicado_em']);
});

$total = count($rows);

$dataAttrs = static function (array $row): string {
    $principal = $row['principal'];
    $attrs = [
        'data-pub-id' => (string) $row['publicacao_id'],
        'data-pub-nome' => $row['titulo'],
        'data-pub-sku' => (string) ($principal['item_codigo'] ?? ''),
        'data-pub-descricao' => $row['descricao'],
        'data-pub-meta' => $row['origem'],
        'data-pub-preco' => format_money($principal['preco_venda']),
        'data-pub-reserva' => $principal['preco_reserva'] !== null ? format_money($principal['preco_reserva']) : '',
        'data-pub-quantidade' => (string) (int) $principal['quantidade'],
        'data-pub-vendidos' => (string) $row['vendidos'],
        'data-pub-custo' => format_money($principal['custo_unitario_real']),
        'data-pub-margem' => format_money($row['margem']),
        'data-pub-canal' => (string) $principal['canal_nome'],
        'data-pub-canais' => (string) json_encode($row['canaisDoItem'], JSON_UNESCAPED_UNICODE),
        'data-pub-imagens' => (string) json_encode($row['galeria'], JSON_UNESCAPED_UNICODE),
    ];

    $html = '';

    foreach ($attrs as $name => $value) {
        $html .= ' ' . $name . '="' . e($value) . '"';
    }

    return $html;
};
?>
<?php require __DIR__ . '/../partials/tabs.php'; ?>

<section class="panel-card">
    <div class="panel-card-head">
        <div>
            <h2 class="panel-card-title">Itens publicados</h2>
            <p class="panel-card-subtitle mb-0">
                Cada item aparece uma única vez, com as plataformas em que foi publicado.
            </p>
        </div>
        <span class="badge-soft"><?= (int) $total ?> item(ns) publicado(s)</span>
    </div>

    <form
        method="get"
        action="<?= e(url('/admin/loja/estoque/publicados')) ?>"
        class="loja-entry-toolbar"
        role="search"
        style="padding: 0 1.35rem 0.75rem;"
    >
        <div class="loja-entry-search">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                <input
                    type="search"
                    class="form-control"
                    name="busca"
                    value="<?= e($busca) ?>"
                    placeholder="Pesquisar por item, SKU, origem ou canal"
                    maxlength="180"
                    data-live-filter
                >
            </div>
        </div>
        <div class="ms-2" style="min-width: 170px;">
            <select class="form-select" name="ordem" data-auto-submit aria-label="Ordenar publicados">
                <option value="vistos" <?= $ordem === 'vistos' ? 'selected' : '' ?>>Mais vistos</option>
                <option value="recentes" <?= $ordem === 'recentes' ? 'selected' : '' ?>>Mais recentes</option>
            </select>
        </div>
    </form>

    <?php if ($rows === []): ?>
        <div class="empty-state">
            <i class="bi bi-megaphone" aria-hidden="true"></i>
            <p class="mb-1">Nenhum item publicado ainda.</p>
            <span>Publique um item pela tela de Estoque.</span>
        </div>
    <?php else: ?>
        <div class="d-none d-lg-block table-responsive">
            <table class="table admin-table loja-table align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">Foto</th>
                        <th scope="col">Item</th>
                        <th scope="col">Plataformas</th>
                        <th scope="col">Valores</th>
                        <th scope="col">Qtd.</th>
                        <th scope="col">Visitas</th>
                        <th scope="col">Status</th>
                        <th scope="col">Publicado em</th>
                        <th scope="col" class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <?php
                        $principal = $row['principal'];
                        $offerUrl = url('/loja/oferta/' . $row['publicacao_id']);
                        $waText = $row['titulo'] . ' - ' . format_money($row['preco']) . ' - ' . $offerUrl;
                        ?>
                        <tr data-filter-row>
                            <td>
                                <span class="loja-item-thumb">
                                    <?php if ($row['imagem'] !== null): ?>
                                        <img src="<?= e($row['imagem']) ?>" alt="<?= e((string) $principal['item_nome']) ?>" loading="lazy">
                                    <?php else: ?>
                                        <i class="bi bi-image" aria-hidden="true"></i>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td>
                                <div class="loja-item-cell-info">
                                    <strong class="d-block"><?= e((string) $principal['item_nome']) ?></strong>
                                    <span class="small text-muted d-block">
                                        SKU: <?= $principal['item_codigo'] !== null && $principal['item_codigo'] !== '' ? e((string) $principal['item_codigo']) : '—' ?>
                                    </span>
                                    <span class="small text-muted d-block">Origem: <?= e($row['origem']) ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <?php foreach ($row['plataformas'] as $plataforma): ?>
                                        <span class="badge-soft">
                                            <i class="bi <?= $plataforma['slug'] === 'telegram' ? 'bi-telegram' : ($plataforma['slug'] === 'whatsapp' ? 'bi-whatsapp' : 'bi-shop') ?>" aria-hidden="true"></i>
                                            <?= e($plataforma['nome']) ?>
                                            <span class="text-muted">(<?= (int) $plataforma['quantidade'] ?>)</span>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <strong class="fs-6"><?= e(format_money($row['preco'])) ?></strong>
                                    <span class="small text-muted">Custo: <?= e(format_money($row['custo'])) ?></span>
                                    <span class="small text-muted">
                                        Margem:
                                        <strong class="<?= $row['margem'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= e(format_money($row['margem'])) ?></strong>
                                        <?php if ($row['margem_pct'] !== null): ?>
                                            (<?= e(number_format($row['margem_pct'], 1, ',', '.')) ?>%)
                                        <?php endif; ?>
                                    </span>
                                    <?php if ($principal['preco_reserva'] !== null): ?>
                                        <span class="small text-muted">Reserva: <?= e(format_money($principal['preco_reserva'])) ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <strong class="d-block"><?= (int) $row['item_quantidade'] ?></strong>
                                <span class="small text-muted">vendidos: <?= (int) $row['vendidos'] ?></span>
                            </td>
                            <td>
                                <span class="badge-soft">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                    <?= (int) $row['visitas'] ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($row['vendido']): ?>
                                    <span class="status-pill status-pill-active">
                                        <i class="bi bi-bag-check" aria-hidden="true"></i>
                                        Vendido
                                    </span>
                                <?php else: ?>
                                    <span class="status-pill <?= $row['status'] === 'publicado' ? 'status-pill-active' : 'status-pill-transit' ?>">
                                        <?= e(ucfirst($row['status'])) ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($row['reservas_abertas'] > 0): ?>
                                    <span class="badge-soft d-block mt-1">
                                        <i class="bi bi-lock" aria-hidden="true"></i>
                                        Reserva em aberto
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?= e(format_datetime($row['publicado_em'], 'd/m/Y')) ?></td>
                            <td class="text-end">
                                <div class="table-actions">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-secondary"
                                        title="Ver anúncio"
                                        data-bs-toggle="modal"
                                        data-bs-target="#viewPublicacaoModal"
                                        <?= $dataAttrs($row) ?>
                                    >
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-secondary"
                                        title="Copiar link da oferta"
                                        data-copy-link="<?= e($offerUrl) ?>"
                                    >
                                        <i class="bi bi-link-45deg" aria-hidden="true"></i>
                                    </button>
                                    <a
                                        class="btn btn-sm btn-outline-success"
                                        href="https://wa.me/?text=<?= e(rawurlencode($waText)) ?>"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        title="Enviar no WhatsApp"
                                    >
                                        <i class="bi bi-whatsapp" aria-hidden="true"></i>
                                    </a>
                                    <?php if (!$row['temTelegram']): ?>
                                        <form method="post" action="<?= e(url('/admin/loja/estoque/publicados/' . $row['itemId'] . '/publicar/telegram')) ?>">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-brand" title="Publicar também no Telegram">
                                                <i class="bi bi-telegram" aria-hidden="true"></i>
                                                Telegram
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if (!$row['temLoja']): ?>
                                        <form method="post" action="<?= e(url('/admin/loja/estoque/publicados/' . $row['itemId'] . '/publicar/loja')) ?>">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-secondary" title="Publicar também na Loja">
                                                <i class="bi bi-shop" aria-hidden="true"></i>
                                                Loja
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <div class="dropdown d-inline-block">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-danger dropdown-toggle"
                                            data-bs-toggle="dropdown"
                                            data-vendas-actions
                                            aria-expanded="false"
                                            title="Remover publicações"
                                        >
                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <?php foreach ($row['plataformas'] as $plataforma): ?>
                                                <form method="post" action="<?= e(url('/admin/loja/estoque/publicados/' . $plataforma['publicacao_id'] . '/excluir')) ?>" class="m-0">
                                                    <?= csrf_field() ?>
                                                    <button
                                                        type="submit"
                                                        class="dropdown-item"
                                                        <?= $plataforma['reservas_abertas'] > 0 ? 'disabled' : '' ?>
                                                    >
                                                        <i class="bi bi-x-circle me-1" aria-hidden="true"></i>
                                                        Remover de <?= e($plataforma['nome']) ?>
                                                        <?= $plataforma['reservas_abertas'] > 0 ? '(reservas abertas)' : '' ?>
                                                    </button>
                                                </form>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="d-lg-none loja-entry-cards">
            <?php foreach ($rows as $row): ?>
                <?php
                $principal = $row['principal'];
                $offerUrl = url('/loja/oferta/' . $row['publicacao_id']);
                $waText = $row['titulo'] . ' - ' . format_money($row['preco']) . ' - ' . $offerUrl;
                ?>
                <article class="loja-entry-card" data-filter-row>
                    <div class="loja-entry-card-top">
                        <div class="loja-item-card-main">
                            <span class="loja-item-thumb">
                                <?php if ($row['imagem'] !== null): ?>
                                    <img src="<?= e($row['imagem']) ?>" alt="<?= e((string) $principal['item_nome']) ?>" loading="lazy">
                                <?php else: ?>
                                    <i class="bi bi-image" aria-hidden="true"></i>
                                <?php endif; ?>
                            </span>
                            <div class="loja-entry-card-info">
                                <span class="loja-entry-card-title"><?= e((string) $principal['item_nome']) ?></span>
                                <span class="loja-entry-card-date">
                                    SKU: <?= $principal['item_codigo'] !== null && $principal['item_codigo'] !== '' ? e((string) $principal['item_codigo']) : '—' ?>
                                </span>
                            </div>
                        </div>
                        <?php if ($row['vendido']): ?>
                            <span class="status-pill status-pill-active">Vendido</span>
                        <?php else: ?>
                            <span class="status-pill <?= $row['status'] === 'publicado' ? 'status-pill-active' : 'status-pill-transit' ?>"><?= e(ucfirst($row['status'])) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="loja-entry-card-status">
                        <?php foreach ($row['plataformas'] as $plataforma): ?>
                            <span class="badge-soft">
                                <i class="bi <?= $plataforma['slug'] === 'telegram' ? 'bi-telegram' : ($plataforma['slug'] === 'whatsapp' ? 'bi-whatsapp' : 'bi-shop') ?>" aria-hidden="true"></i>
                                <?= e($plataforma['nome']) ?>
                                <span class="text-muted">(<?= (int) $plataforma['quantidade'] ?>)</span>
                            </span>
                        <?php endforeach; ?>
                        <?php if ($row['reservas_abertas'] > 0): ?>
                            <span class="badge-soft"><i class="bi bi-lock" aria-hidden="true"></i> Reserva em aberto</span>
                        <?php endif; ?>
                    </div>

                    <div class="loja-entry-card-stats">
                        <div><span>Qtd.</span><strong><?= (int) $row['item_quantidade'] ?></strong></div>
                        <div><span>Vendidos</span><strong><?= (int) $row['vendidos'] ?></strong></div>
                        <div><span>Visitas</span><strong><?= (int) $row['visitas'] ?></strong></div>
                        <div><span>Valor de venda</span><strong><?= e(format_money($row['preco'])) ?></strong></div>
                        <div><span>Custo</span><strong><?= e(format_money($row['custo'])) ?></strong></div>
                        <div>
                            <span>Margem</span>
                            <strong class="<?= $row['margem'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= e(format_money($row['margem'])) ?></strong>
                        </div>
                    </div>

                    <div class="small text-muted">
                        Publicado em <?= e(format_datetime($row['publicado_em'], 'd/m/Y')) ?>
                    </div>

                    <div class="loja-entry-card-actions">
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary"
                            data-bs-toggle="modal"
                            data-bs-target="#viewPublicacaoModal"
                            <?= $dataAttrs($row) ?>
                        >
                            <i class="bi bi-eye" aria-hidden="true"></i>
                            Ver anúncio
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-copy-link="<?= e($offerUrl) ?>">
                            <i class="bi bi-link-45deg" aria-hidden="true"></i>
                            Copiar link
                        </button>
                        <a
                            class="btn btn-sm btn-outline-success"
                            href="https://wa.me/?text=<?= e(rawurlencode($waText)) ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <i class="bi bi-whatsapp" aria-hidden="true"></i>
                            WhatsApp
                        </a>
                        <?php if (!$row['temTelegram']): ?>
                            <form method="post" action="<?= e(url('/admin/loja/estoque/publicados/' . $row['itemId'] . '/publicar/telegram')) ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-brand">
                                    <i class="bi bi-telegram" aria-hidden="true"></i>
                                    Publicar no Telegram
                                </button>
                            </form>
                        <?php endif; ?>
                        <?php if (!$row['temLoja']): ?>
                            <form method="post" action="<?= e(url('/admin/loja/estoque/publicados/' . $row['itemId'] . '/publicar/loja')) ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-shop" aria-hidden="true"></i>
                                    Publicar na Loja
                                </button>
                            </form>
                        <?php endif; ?>
                        <div class="dropdown d-inline-block">
                            <button type="button" class="btn btn-sm btn-outline-danger dropdown-toggle" data-bs-toggle="dropdown" data-vendas-actions>
                                <i class="bi bi-trash" aria-hidden="true"></i>
                                Remover
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <?php foreach ($row['plataformas'] as $plataforma): ?>
                                    <form method="post" action="<?= e(url('/admin/loja/estoque/publicados/' . $plataforma['publicacao_id'] . '/excluir')) ?>" class="m-0">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="dropdown-item" <?= $plataforma['reservas_abertas'] > 0 ? 'disabled' : '' ?>>
                                            <i class="bi bi-x-circle me-1" aria-hidden="true"></i>
                                            Remover de <?= e($plataforma['nome']) ?>
                                            <?= $plataforma['reservas_abertas'] > 0 ? '(reservas abertas)' : '' ?>
                                        </button>
                                    </form>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<div class="modal fade" id="viewPublicacaoModal" tabindex="-1" aria-labelledby="viewPublicacaoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title h5" id="viewPublicacaoModalLabel">Anúncio do item</h3>
                    <p class="modal-subtitle mb-0">Prévia de como o anúncio aparece para o cliente.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="pub-view">
                    <div class="pub-view-gallery">
                        <button type="button" class="pub-view-main" data-pub-main-btn>
                            <img data-pub-main src="" alt="" hidden>
                            <span class="pub-view-placeholder" data-pub-placeholder>
                                <i class="bi bi-image" aria-hidden="true"></i>
                            </span>
                        </button>
                        <div class="pub-view-thumbs" data-pub-thumbs></div>
                    </div>

                    <div class="pub-view-details">
                        <span class="publish-preview-eyebrow" data-pub-canal-label>Anúncio</span>
                        <h4 class="pub-view-title" data-pub-title></h4>
                        <span class="pub-view-sku" data-pub-sku></span>
                        <p class="pub-view-meta" data-pub-meta></p>
                        <p class="pub-view-desc" data-pub-desc></p>

                        <div class="pub-view-canais" data-pub-canais></div>

                        <p class="pub-view-price" data-pub-preco></p>
                        <p class="pub-view-reserva" data-pub-reserva></p>

                        <div class="pub-view-stats">
                            <div>
                                <span>Qtd. publicada</span>
                                <strong data-pub-quantidade>-</strong>
                            </div>
                            <div>
                                <span>Vendidos</span>
                                <strong data-pub-vendidos>-</strong>
                            </div>
                            <div>
                                <span>Custo/un.</span>
                                <strong data-pub-custo>-</strong>
                            </div>
                            <div>
                                <span>Margem/un.</span>
                                <strong data-pub-margem>-</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="admin-image-lightbox" data-admin-lightbox hidden>
    <button type="button" class="admin-image-lightbox-close" data-admin-lightbox-close aria-label="Fechar imagem">&times;</button>
    <img data-admin-lightbox-img src="" alt="">
</div>
