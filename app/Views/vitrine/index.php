<?php

declare(strict_types=1);

$itens = $itens ?? [];
$busca = $busca ?? '';
?>
<section class="vitrine-hero">
    <div>
        <span class="vitrine-eyebrow">Loja virtual</span>
        <h1 class="vitrine-hero-title">Carrinhos colecionáveis para sua coleção</h1>
        <p class="vitrine-hero-text">
            Escolha seus itens, monte seu carrinho e pague com segurança pelo Mercado Pago.
        </p>
    </div>
</section>

<?php if ($itens === []): ?>
    <div class="vitrine-empty">
        <i class="bi bi-box-seam" aria-hidden="true"></i>
        <h2>Nenhum item disponível</h2>
        <p>
            <?= $busca !== ''
                ? 'Nenhum item encontrado para a busca informada.'
                : 'Assim que novos itens forem publicados, eles aparecerão aqui.' ?>
        </p>
        <?php if ($busca !== ''): ?>
            <a class="btn btn-outline-secondary" href="<?= e(url('/loja')) ?>">Limpar busca</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="vitrine-grid">
        <?php foreach ($itens as $item): ?>
            <?php
            $disponivel = isset($item['disponivel_calc'])
                ? max(0, (int) $item['disponivel_calc'])
                : max(
                    0,
                    (int) $item['quantidade'] - (int) ($item['reservado'] ?? 0) - (int) ($item['vendidos'] ?? 0)
                );
            $preVenda = (string) ($item['item_status'] ?? '') === 'pre_venda';
            $nome = trim((string) ($item['titulo'] ?? '')) !== ''
                ? (string) $item['titulo']
                : (string) ($item['item_nome'] ?? 'Item');
            $descricao = trim((string) ($item['descricao'] ?? '')) !== ''
                ? (string) $item['descricao']
                : (string) ($item['item_descricao'] ?? '');
            $descricao = trim(strip_tags($descricao));
            $resumo = $descricao !== ''
                ? mb_strimwidth($descricao, 0, 150, '…', 'UTF-8')
                : '';
            $imagem = !empty($item['imagem_id'])
                ? url('/loja/imagem/' . (int) $item['imagem_id'])
                : null;
            ?>
            <article
                class="vitrine-card"
                data-vitrine-item="<?= (int) $item['id'] ?>"
                data-vitrine-item-url="<?= e(url('/loja/item/' . (int) $item['id'])) ?>"
            >
                <div class="vitrine-card-media">
                    <?php if ($imagem !== null): ?>
                        <img src="<?= e($imagem) ?>" alt="<?= e($nome) ?>" loading="lazy">
                    <?php else: ?>
                        <span class="vitrine-card-placeholder">
                            <i class="bi bi-image" aria-hidden="true"></i>
                        </span>
                    <?php endif; ?>
                    <?php if ($preVenda): ?>
                        <span class="vitrine-badge vitrine-badge-pre">Pré-venda</span>
                    <?php endif; ?>
                </div>

                <div class="vitrine-card-body">
                    <h2 class="vitrine-card-title"><?= e($nome) ?></h2>
                    <?php if (!empty($item['item_codigo'])): ?>
                        <span class="vitrine-sku">SKU: <?= e((string) $item['item_codigo']) ?></span>
                    <?php endif; ?>
                    <?php if ($resumo !== ''): ?>
                        <p class="vitrine-card-desc"><?= e($resumo) ?></p>
                    <?php endif; ?>

                    <div class="vitrine-card-meta">
                        <strong class="vitrine-price"><?= e(format_money($item['preco_venda'])) ?></strong>
                        <span class="vitrine-stock">
                            <?= $disponivel > 0 ? $disponivel . ' un.' : 'Esgotado' ?>
                        </span>
                    </div>
                    <?php if (!empty($item['preco_reserva'])): ?>
                        <span class="vitrine-card-reserve">
                            Reserva: <?= e(format_money($item['preco_reserva'])) ?>
                            • saldo: <?= e(format_money(max(0, (float) $item['preco_venda'] - (float) $item['preco_reserva']))) ?>
                        </span>
                    <?php endif; ?>

                    <?php if ($disponivel > 0): ?>
                        <form method="post" action="<?= e(url('/loja/carrinho/adicionar')) ?>" class="vitrine-add">
                            <?= csrf_field() ?>
                            <input type="hidden" name="publicacao_id" value="<?= (int) $item['id'] ?>">
                            <div class="vitrine-qty" role="group" aria-label="Quantidade">
                                <button type="button" data-vitrine-step="-1" aria-label="Diminuir">−</button>
                                <input
                                    type="number"
                                    name="quantidade"
                                    value="1"
                                    min="1"
                                    max="<?= $disponivel ?>"
                                    aria-label="Quantidade"
                                >
                                <button type="button" data-vitrine-step="1" aria-label="Aumentar">+</button>
                            </div>
                            <button type="submit" class="btn btn-brand vitrine-add-btn">
                                <i class="bi bi-cart-plus" aria-hidden="true"></i>
                                Adicionar
                            </button>
                        </form>
                    <?php else: ?>
                        <span class="vitrine-sold-out">Esgotado</span>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="modal fade" id="vitrineItemModal" tabindex="-1" aria-labelledby="vitrineItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title h5" id="vitrineItemModalLabel" data-vitrine-modal-title>Detalhes do item</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="vitrine-item-view">
                    <div class="vitrine-item-gallery">
                        <button type="button" class="vitrine-item-main" data-vitrine-main-btn>
                            <img data-vitrine-main src="" alt="" hidden>
                            <span class="vitrine-item-placeholder" data-vitrine-placeholder>
                                <i class="bi bi-image" aria-hidden="true"></i>
                            </span>
                        </button>
                        <div class="vitrine-item-thumbs" data-vitrine-thumbs></div>
                    </div>

                    <div class="vitrine-item-details">
                        <span class="vitrine-badge vitrine-badge-pre" data-vitrine-badge hidden>Pré-venda</span>
                        <h4 class="vitrine-item-title" data-vitrine-title></h4>
                        <span class="vitrine-sku" data-vitrine-sku></span>
                        <p class="vitrine-item-desc" data-vitrine-desc></p>
                        <div class="vitrine-item-price" data-vitrine-price></div>
                        <div class="vitrine-item-reserve" data-vitrine-reserve></div>
                        <div class="vitrine-item-stock" data-vitrine-stock></div>

                        <form method="post" action="<?= e(url('/loja/carrinho/adicionar')) ?>" class="vitrine-item-form" data-vitrine-form>
                            <?= csrf_field() ?>
                            <input type="hidden" name="publicacao_id" data-vitrine-id value="">
                            <div class="vitrine-qty" role="group" aria-label="Quantidade">
                                <button type="button" data-vitrine-step="-1" aria-label="Diminuir">−</button>
                                <input
                                    type="number"
                                    name="quantidade"
                                    value="1"
                                    min="1"
                                    max="1"
                                    aria-label="Quantidade"
                                    data-vitrine-qty
                                >
                                <button type="button" data-vitrine-step="1" aria-label="Aumentar">+</button>
                            </div>
                            <button type="submit" class="btn btn-brand" data-vitrine-add-btn>
                                <i class="bi bi-cart-plus" aria-hidden="true"></i>
                                Adicionar ao carrinho
                            </button>
                        </form>

                        <div class="vitrine-item-soldout" data-vitrine-soldout hidden>
                            <i class="bi bi-x-circle" aria-hidden="true"></i>
                            Esgotado
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="vitrine-lightbox" data-vitrine-lightbox hidden>
    <button type="button" class="vitrine-lightbox-close" data-vitrine-lightbox-close aria-label="Fechar imagem">&times;</button>
    <img data-vitrine-lightbox-img src="" alt="">
</div>
