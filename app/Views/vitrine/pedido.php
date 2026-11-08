<?php

declare(strict_types=1);

$pedido = $pedido ?? [];
$itens = $pedido['itens'] ?? [];
$status = (string) ($pedido['status'] ?? 'pendente');
$statusLabel = match ($status) {
    'pago' => 'Pagamento confirmado',
    'cancelado' => 'Cancelado',
    'expirado' => 'Expirado',
    default => 'Aguardando pagamento',
};
$statusClass = match ($status) {
    'pago' => 'success',
    'cancelado' => 'danger',
    'expirado' => 'muted',
    default => 'warning',
};
$modo = (string) ($pedido['entrega_modo'] ?? 'retirada') === 'envio' ? 'Envio' : 'Retirada na loja';
$codigo = (string) ($pedido['codigo'] ?? '');
?>
<div class="vitrine-page-head">
    <div>
        <span class="vitrine-eyebrow">Pedido</span>
        <h1 class="vitrine-page-title">#<?= e($codigo) ?></h1>
    </div>
    <a class="btn btn-outline-secondary" href="<?= e(url('/loja')) ?>">
        <i class="bi bi-shop" aria-hidden="true"></i>
        Voltar à loja
    </a>
</div>

<div class="vitrine-order">
    <div class="vitrine-order-head">
        <div class="vitrine-order-code">
            <span>Status</span>
            <strong class="vitrine-status vitrine-status-<?= e($statusClass) ?>">
                <?= e($statusLabel) ?>
            </strong>
        </div>
        <div class="vitrine-order-code">
            <span>Forma de entrega</span>
            <strong><?= e($modo) ?></strong>
        </div>
        <div class="vitrine-order-code">
            <span>Total</span>
            <strong><?= e(format_money($pedido['total'] ?? 0)) ?></strong>
        </div>
    </div>

    <?php if ($status === 'pago'): ?>
        <div class="alert alert-success" role="alert">
            <strong>Pagamento confirmado!</strong>
            Seu pedido foi registrado. Entre em contato para combinar a retirada na loja.
        </div>
    <?php elseif ($status === 'pendente'): ?>
        <div class="alert alert-warning" role="alert">
            <strong>Aguardando pagamento.</strong>
            Conclua o pagamento para garantir seus itens.
            <?php if (!empty($pedido['expira_em'])): ?>
                A reserva é válida até <?= e(format_datetime((string) $pedido['expira_em'])) ?>.
            <?php endif; ?>
        </div>
        <form method="post" action="<?= e(url('/loja/pedido/' . rawurlencode($codigo) . '/pagar')) ?>" class="mb-3">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-brand">
                <i class="bi bi-credit-card" aria-hidden="true"></i>
                Pagar agora com Mercado Pago
            </button>
        </form>
    <?php elseif ($status === 'cancelado'): ?>
        <div class="alert alert-danger" role="alert">
            Este pedido foi cancelado. Se precisar, monte um novo carrinho.
        </div>
    <?php else: ?>
        <div class="alert alert-secondary" role="alert">
            O prazo deste pedido expirou. Os itens voltaram a ficar disponíveis na loja.
        </div>
    <?php endif; ?>

    <div class="vitrine-order-grid">
        <section class="vitrine-panel">
            <h2 class="vitrine-panel-title">Itens do pedido</h2>

            <ul class="vitrine-order-items">
                <?php foreach ($itens as $item): ?>
                    <li>
                        <span class="vitrine-summary-name">
                            <?= e((string) $item['item_nome']) ?>
                            <?php if (!empty($item['item_codigo'])): ?>
                                <small>SKU: <?= e((string) $item['item_codigo']) ?></small>
                            <?php endif; ?>
                            <small><?= (int) $item['quantidade'] ?> × <?= e(format_money($item['preco_unitario'])) ?></small>
                            <?php if (!empty($item['valor_reserva'])): ?>
                                <small class="text-primary">
                                    Reserva: <?= e(format_money($item['valor_reserva'])) ?> / un.
                                    • Saldo: <?= e(format_money(max(0, $item['preco_unitario'] - $item['valor_reserva']))) ?> / un.
                                </small>
                            <?php endif; ?>
                        </span>
                        <strong><?= e(format_money($item['subtotal'])) ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="vitrine-summary-total">
                <span><?= $status === 'pago' ? 'Pago' : 'Total a pagar agora' ?></span>
                <strong><?= e(format_money($status === 'pago' ? ($pedido['valor_pago'] ?? 0) : ($pedido['total'] ?? 0))) ?></strong>
            </div>

            <?php if (($pedido['saldo'] ?? 0) > 0): ?>
                <p class="small mb-1">
                    Saldo na entrega: <strong class="text-danger"><?= e(format_money($pedido['saldo'])) ?></strong>
                </p>
                <p class="small mb-0">
                    Total do pedido: <strong><?= e(format_money($pedido['subtotal'] ?? $pedido['total'])) ?></strong>
                </p>
            <?php endif; ?>
        </section>

        <aside class="vitrine-panel">
            <h2 class="vitrine-panel-title">Dados do pedido</h2>
            <dl class="vitrine-dl">
                <dt>Nome</dt>
                <dd><?= e((string) ($pedido['cliente_nome'] ?? '')) ?></dd>

                <?php if (!empty($pedido['cliente_email'])): ?>
                    <dt>E-mail</dt>
                    <dd><?= e((string) $pedido['cliente_email']) ?></dd>
                <?php endif; ?>

                <?php if (!empty($pedido['cliente_telefone'])): ?>
                    <dt>Telefone</dt>
                    <dd><?= e((string) $pedido['cliente_telefone']) ?></dd>
                <?php endif; ?>

                <dt>Entrega</dt>
                <dd><?= e($modo) ?></dd>

                <?php if (!empty($pedido['observacoes'])): ?>
                    <dt>Observações</dt>
                    <dd><?= nl2br(e((string) $pedido['observacoes'])) ?></dd>
                <?php endif; ?>

                <dt>Criado em</dt>
                <dd><?= e(format_datetime((string) ($pedido['criado_em'] ?? ''))) ?></dd>

                <?php if (!empty($pedido['pago_em'])): ?>
                    <dt>Pago em</dt>
                    <dd><?= e(format_datetime((string) $pedido['pago_em'])) ?></dd>
                <?php endif; ?>
            </dl>
        </aside>
    </div>
</div>
