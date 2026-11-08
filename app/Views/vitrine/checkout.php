<?php

declare(strict_types=1);

$resumo = $resumo ?? ['itens' => [], 'total' => 0, 'total_itens' => 0, 'saldo' => 0, 'problemas' => []];
$itens = $resumo['itens'];
$pedido = $pedido ?? [];

$nomeInicial = (string) old('cliente_nome', '');
if ($nomeInicial === '' && isset($pedido['cliente_nome']) && (string) $pedido['cliente_nome'] !== 'Reserva') {
    $nomeInicial = (string) $pedido['cliente_nome'];
}
$emailInicial = (string) old('cliente_email', (string) ($pedido['cliente_email'] ?? ''));
$telefoneInicial = (string) old('cliente_telefone', (string) ($pedido['cliente_telefone'] ?? ''));
$observacoesInicial = (string) old('observacoes', (string) ($pedido['observacoes'] ?? ''));
?>
<div class="vitrine-page-head">
    <div>
        <span class="vitrine-eyebrow">Checkout</span>
        <h1 class="vitrine-page-title">Finalizar compra</h1>
    </div>
    <a class="btn btn-outline-secondary" href="<?= e(url('/loja/carrinho')) ?>">
        <i class="bi bi-arrow-left" aria-hidden="true"></i>
        Voltar ao carrinho
    </a>
</div>

<?php if (!empty($pedido['expira_em'])): ?>
    <div class="vitrine-reserve-banner" role="status">
        <i class="bi bi-clock-history" aria-hidden="true"></i>
        <span>
            Itens reservados para você.
            <?php if (!empty($pedido['codigo'])): ?>
                Pedido <strong>#<?= e((string) $pedido['codigo']) ?></strong>.
            <?php endif; ?>
            A reserva é válida até <strong><?= e(format_datetime((string) $pedido['expira_em'])) ?></strong>.
        </span>
    </div>
<?php endif; ?>

<?php foreach (($resumo['problemas'] ?? []) as $problema): ?>
    <div class="alert alert-warning" role="alert"><?= e($problema) ?></div>
<?php endforeach; ?>

<form method="post" action="<?= e(url('/loja/checkout')) ?>" class="vitrine-checkout">
    <?= csrf_field() ?>

    <div class="vitrine-checkout-grid">
        <section class="vitrine-panel">
            <h2 class="vitrine-panel-title">Seus dados</h2>

            <div class="row g-3">
                <div class="col-12">
                    <label for="clienteNome" class="form-label">Nome completo *</label>
                    <input
                        type="text"
                        class="form-control"
                        id="clienteNome"
                        name="cliente_nome"
                        maxlength="180"
                        required
                        value="<?= e($nomeInicial) ?>"
                    >
                </div>
                <div class="col-12 col-md-6">
                    <label for="clienteEmail" class="form-label">E-mail</label>
                    <input
                        type="email"
                        class="form-control"
                        id="clienteEmail"
                        name="cliente_email"
                        maxlength="180"
                        value="<?= e($emailInicial) ?>"
                    >
                </div>
                <div class="col-12 col-md-6">
                    <label for="clienteTelefone" class="form-label">Telefone / WhatsApp *</label>
                    <input
                        type="tel"
                        class="form-control"
                        id="clienteTelefone"
                        name="cliente_telefone"
                        maxlength="30"
                        required
                        placeholder="(11) 99999-9999"
                        value="<?= e($telefoneInicial) ?>"
                    >
                </div>
            </div>

            <h2 class="vitrine-panel-title mt-4">Entrega</h2>

            <label class="vitrine-option">
                <input type="radio" name="entrega_modo" value="retirada" checked>
                <span class="vitrine-option-body">
                    <strong>Retirar na loja</strong>
                    <small>Após a confirmação do pagamento, combinamos a retirada.</small>
                </span>
            </label>

            <label class="vitrine-option vitrine-option-disabled">
                <input type="radio" name="entrega_modo" value="envio" disabled>
                <span class="vitrine-option-body">
                    <strong>Solicitar envio</strong>
                    <small>Em breve: cálculo de frete e prazo de entrega.</small>
                </span>
            </label>

            <div class="mt-4">
                <label for="observacoes" class="form-label">Observações (opcional)</label>
                <textarea
                    class="form-control"
                    id="observacoes"
                    name="observacoes"
                    rows="3"
                    maxlength="2000"
                    placeholder="Alguma informação sobre a retirada?"
                ><?= e($observacoesInicial) ?></textarea>
            </div>

            <div class="vitrine-payment-note">
                <i class="bi bi-shield-lock" aria-hidden="true"></i>
                <span>Pagamento processado com segurança pelo Mercado Pago.</span>
            </div>
        </section>

        <aside class="vitrine-summary">
            <h2 class="vitrine-panel-title">Resumo do pedido</h2>

            <ul class="vitrine-summary-list">
                <?php foreach ($itens as $item): ?>
                    <li>
                        <span class="vitrine-summary-name">
                            <?= e((string) $item['item_nome']) ?>
                            <small><?= (int) $item['quantidade'] ?> × <?= e(format_money($item['preco_unitario'])) ?></small>
                            <?php if (!empty($item['valor_reserva_unitario'])): ?>
                                <small class="text-primary">
                                    Reserva: <?= e(format_money($item['valor_reserva_unitario'])) ?> / un.
                                    • Saldo: <?= e(format_money(max(0, $item['preco_unitario'] - $item['valor_reserva_unitario']))) ?> / un.
                                </small>
                            <?php endif; ?>
                        </span>
                        <strong><?= e(format_money($item['subtotal'])) ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="vitrine-summary-total">
                <span>Total a pagar agora</span>
                <strong><?= e(format_money($resumo['total'])) ?></strong>
            </div>

            <?php if (($resumo['saldo'] ?? 0) > 0): ?>
                <p class="small mb-2">
                    Saldo na entrega: <strong class="text-danger"><?= e(format_money($resumo['saldo'])) ?></strong><br>
                    Total do pedido: <strong><?= e(format_money($resumo['total_itens'] ?? $resumo['total'])) ?></strong>
                </p>
            <?php endif; ?>

            <button type="submit" class="btn btn-brand btn-lg w-100">
                <i class="bi bi-credit-card" aria-hidden="true"></i>
                Pagar com Mercado Pago
            </button>

            <p class="vitrine-summary-note">
                Você será redirecionado para o ambiente seguro do Mercado Pago para concluir o pagamento.
            </p>
        </aside>
    </div>
</form>
