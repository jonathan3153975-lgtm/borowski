<?php

declare(strict_types=1);

$resumo = $resumo ?? ['itens' => [], 'total' => 0, 'total_itens' => 0, 'saldo' => 0, 'problemas' => [], 'tem_problemas' => false];
$itens = $resumo['itens'];
$problemas = $resumo['problemas'];
$temProblemas = !empty($resumo['tem_problemas']);
?>
<div class="vitrine-page-head">
    <div>
        <span class="vitrine-eyebrow">Sua compra</span>
        <h1 class="vitrine-page-title">Carrinho</h1>
    </div>
    <a class="btn btn-outline-secondary" href="<?= e(url('/loja')) ?>">
        <i class="bi bi-arrow-left" aria-hidden="true"></i>
        Continuar comprando
    </a>
</div>

<?php foreach ($problemas as $problema): ?>
    <div class="alert alert-warning" role="alert"><?= e($problema) ?></div>
<?php endforeach; ?>

<?php if ($itens === []): ?>
    <div class="vitrine-empty">
        <i class="bi bi-cart-x" aria-hidden="true"></i>
        <h2>Seu carrinho está vazio</h2>
        <p>Adicione itens da loja para continuar.</p>
        <a class="btn btn-brand" href="<?= e(url('/loja')) ?>">Ver itens</a>
    </div>
<?php else: ?>
    <form method="post" action="<?= e(url('/loja/carrinho/atualizar')) ?>" class="vitrine-cart-form">
        <?= csrf_field() ?>

        <div class="vitrine-cart-list">
            <?php foreach ($itens as $item): ?>
                <?php
                $imagem = !empty($item['imagem_id'])
                    ? url('/loja/imagem/' . (int) $item['imagem_id'])
                    : null;
                ?>
                <article class="vitrine-cart-item<?= empty($item['ok']) ? ' is-unavailable' : '' ?>">
                    <div class="vitrine-cart-media">
                        <?php if ($imagem !== null): ?>
                            <img src="<?= e($imagem) ?>" alt="<?= e((string) $item['item_nome']) ?>" loading="lazy">
                        <?php else: ?>
                            <span class="vitrine-card-placeholder">
                                <i class="bi bi-image" aria-hidden="true"></i>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="vitrine-cart-info">
                        <h2><?= e((string) $item['item_nome']) ?></h2>
                        <?php if (!empty($item['item_codigo'])): ?>
                            <span class="vitrine-sku">SKU: <?= e((string) $item['item_codigo']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($item['pre_venda'])): ?>
                            <span class="vitrine-badge vitrine-badge-pre">Pré-venda</span>
                        <?php endif; ?>
                        <span class="vitrine-cart-unit">
                            <?= e(format_money($item['preco_unitario'])) ?> / un.
                        </span>
                        <?php if (!empty($item['valor_reserva_unitario'])): ?>
                            <span class="vitrine-cart-reserve">
                                Reserva: <?= e(format_money($item['valor_reserva_unitario'])) ?>
                                • Saldo/un.: <?= e(format_money(max(0, $item['preco_unitario'] - $item['valor_reserva_unitario']))) ?>
                            </span>
                        <?php endif; ?>
                        <span class="vitrine-cart-availability">
                            <?= (int) $item['disponivel'] ?> disponível(is)
                        </span>
                        <?php if (empty($item['ok']) && !empty($item['problema'])): ?>
                            <span class="vitrine-cart-problem">
                                <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                                <?= e((string) $item['problema']) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="vitrine-cart-qty">
                        <label for="qtd-<?= (int) $item['publicacao_id'] ?>" class="vitrine-cart-label">Qtd.</label>
                        <div class="vitrine-qty">
                            <button type="button" data-vitrine-step="-1" aria-label="Diminuir">−</button>
                            <input
                                type="number"
                                id="qtd-<?= (int) $item['publicacao_id'] ?>"
                                name="quantidades[<?= (int) $item['publicacao_id'] ?>]"
                                value="<?= (int) $item['quantidade'] ?>"
                                min="1"
                                max="<?= max(1, (int) $item['disponivel']) ?>"
                                <?= empty($item['ok']) ? 'disabled' : '' ?>
                            >
                            <button type="button" data-vitrine-step="1" aria-label="Aumentar">+</button>
                        </div>
                    </div>

                    <div class="vitrine-cart-subtotal">
                        <span class="vitrine-cart-label">Subtotal</span>
                        <strong><?= e(format_money($item['subtotal'])) ?></strong>
                        <?php if (($item['saldo'] ?? 0) > 0): ?>
                            <span class="small text-muted d-block">Pagar agora: <?= e(format_money($item['cobrar_agora'])) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="vitrine-cart-remove">
                        <button
                            type="submit"
                            formaction="<?= e(url('/loja/carrinho/remover')) ?>"
                            name="publicacao_id"
                            value="<?= (int) $item['publicacao_id'] ?>"
                            class="vitrine-remove-btn"
                            title="Remover item"
                        >
                            <i class="bi bi-trash3" aria-hidden="true"></i>
                            <span>Remover</span>
                        </button>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="vitrine-cart-actions">
            <button type="submit" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
                Atualizar carrinho
            </button>
        </div>
    </form>

    <div class="vitrine-cart-bottom">
        <div class="vitrine-cart-total">
            <span>Total a pagar agora</span>
            <strong><?= e(format_money($resumo['total'])) ?></strong>
            <?php if (($resumo['saldo'] ?? 0) > 0): ?>
                <span class="vitrine-cart-hint">Saldo na entrega: <?= e(format_money($resumo['saldo'])) ?></span>
                <span class="vitrine-cart-hint">Total do pedido: <?= e(format_money($resumo['total_itens'] ?? $resumo['total'])) ?></span>
            <?php endif; ?>
            <?php if ($temProblemas): ?>
                <span class="vitrine-cart-hint">Itens indisponíveis não entram no total.</span>
            <?php endif; ?>
        </div>

        <?php if ($temProblemas): ?>
            <div class="vitrine-cart-block">
                <div class="alert alert-warning mb-2 py-2">
                    Remova ou ajuste os itens indisponíveis antes de finalizar.
                </div>
                <button type="button" class="btn btn-brand btn-lg" disabled>
                    Finalizar compra
                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </button>
            </div>
        <?php else: ?>
            <form method="post" action="<?= e(url('/loja/checkout/iniciar')) ?>" class="vitrine-cart-finalize">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-brand btn-lg">
                    Finalizar compra
                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </button>
            </form>
        <?php endif; ?>
    </div>
<?php endif; ?>
