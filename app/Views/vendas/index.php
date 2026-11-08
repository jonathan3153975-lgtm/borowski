<?php

declare(strict_types=1);

$reservas = $reservas ?? [];
$pedidosAgrupados = $pedidosAgrupados ?? [];
$canais = $canais ?? [];
$filters = $filters ?? ['canal' => '', 'tipo' => '', 'status' => '', 'busca' => ''];
$canal = (string) ($filters['canal'] ?? '');
$tipo = (string) ($filters['tipo'] ?? '');
$busca = (string) ($filters['busca'] ?? '');
$total = count($reservas) + count($pedidosAgrupados);
$pendentesSaldo = count(array_filter($reservas, static fn (array $r): bool => $r['valor_reserva'] !== null && empty($r['saldo_pago_em'])))
    + count(array_filter($pedidosAgrupados, static fn (array $p): bool => empty($p['pago_total'])));

$canalOptions = ['' => 'Todos'];
foreach ($canais as $c) {
    $canalOptions[(string) $c['slug']] = (string) $c['nome'];
}
$tipoOptions = ['' => 'Todos', 'normal' => 'Venda normal', 'pre_venda' => 'Pré-venda'];

$baseQuery = array_filter([
    'canal' => $canal,
    'tipo' => $tipo,
    'busca' => $busca,
], static fn (string $value): bool => $value !== '');

$makeUrl = static function (array $overrides = []) use ($baseQuery): string {
    $query = array_filter(
        array_merge($baseQuery, $overrides),
        static fn (mixed $value): bool => $value !== null && $value !== ''
    );

    return url('/admin/vendas' . ($query === [] ? '' : '?' . http_build_query($query)));
};

$mpLabel = static function (string $mpStatus): string {
    return match ($mpStatus) {
        'approved' => 'Pagamento aprovado',
        'pending' => 'Pagamento pendente',
        'in_process' => 'Pagamento em análise',
        'authorized' => 'Pagamento autorizado',
        'rejected' => 'Pagamento recusado',
        'cancelled' => 'Pagamento cancelado',
        'refunded' => 'Pagamento estornado',
        'charged_back' => 'Pagamento contestado',
        default => '',
    };
};
?>
<section class="panel-card">
    <div class="panel-card-head">
        <div>
            <h2 class="panel-card-title">Vendas</h2>
            <p class="panel-card-subtitle mb-0">
                Pedidos com pagamento confirmado. Em pré-venda, envie a cobrança do saldo restante ao comprador.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge-soft"><?= (int) $total ?> venda(s)</span>
            <span class="badge-soft"><?= (int) $pendentesSaldo ?> saldo(s) pendente(s)</span>
            <?php if ($total > 0): ?>
                <button type="button" class="btn btn-brand" id="btnCriarEntrega" data-entrega-toggle>
                    <i class="bi bi-box-seam" aria-hidden="true"></i>
                    Criar entrega
                </button>
                <button
                    type="button"
                    class="btn btn-success d-none"
                    id="btnConfirmarEntrega"
                    data-entrega-confirm
                    data-cliente-url="<?= e(url('/admin/vendas/cliente')) ?>"
                    disabled
                >
                    <i class="bi bi-check2-circle" aria-hidden="true"></i>
                    Confirmar entrega
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="loja-entry-toolbar" style="padding: 0 1.35rem;">
        <form method="get" action="<?= e(url('/admin/vendas')) ?>" class="loja-entry-search" role="search">
            <?php if ($canal !== ''): ?>
                <input type="hidden" name="canal" value="<?= e($canal) ?>">
            <?php endif; ?>
            <?php if ($tipo !== ''): ?>
                <input type="hidden" name="tipo" value="<?= e($tipo) ?>">
            <?php endif; ?>

            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                <input
                    type="search"
                    class="form-control"
                    name="busca"
                    value="<?= e($busca) ?>"
                    placeholder="Pesquisar por item, SKU, comprador ou canal"
                    maxlength="180"
                    data-live-filter
                >
                <?php if ($baseQuery !== []): ?>
                    <a href="<?= e(url('/admin/vendas')) ?>" class="btn btn-outline-secondary">Limpar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="loja-entry-filters" style="padding: 0 1.35rem;">
        <div class="loja-filter-group">
            <span class="loja-filter-label">Canal</span>
            <div class="loja-filter-pills" role="group" aria-label="Filtrar por canal">
                <?php foreach ($canalOptions as $value => $label): ?>
                    <a
                        href="<?= e($makeUrl(['canal' => $value === '' ? null : $value])) ?>"
                        class="loja-filter-pill<?= $canal === $value ? ' active' : '' ?>"
                        <?= $canal === $value ? 'aria-current="page"' : '' ?>
                    ><?= e($label) ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="loja-filter-group">
            <span class="loja-filter-label">Tipo</span>
            <div class="loja-filter-pills" role="group" aria-label="Filtrar por tipo de venda">
                <?php foreach ($tipoOptions as $value => $label): ?>
                    <a
                        href="<?= e($makeUrl(['tipo' => $value === '' ? null : $value])) ?>"
                        class="loja-filter-pill<?= $tipo === $value ? ' active' : '' ?>"
                        <?= $tipo === $value ? 'aria-current="page"' : '' ?>
                    ><?= e($label) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php if ($reservas === [] && $pedidosAgrupados === []): ?>
        <div class="empty-state">
            <i class="bi bi-cash-coin" aria-hidden="true"></i>
            <p class="mb-1">Nenhuma venda confirmada.</p>
            <span>
                <?= $baseQuery !== []
                    ? 'Ajuste os filtros ou a pesquisa.'
                    : 'Quando um pagamento for confirmado no Mercado Pago, a venda aparece aqui.' ?>
            </span>
        </div>
    <?php else: ?>
        <div class="d-none d-lg-block table-responsive">
            <table class="table admin-table loja-table align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col" class="js-entrega-col">Seleção</th>
                        <th scope="col">Item</th>
                        <th scope="col">Comprador</th>
                        <th scope="col">Qtd. / Valores</th>
                        <th scope="col">Status / Datas</th>
                        <th scope="col" class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pedidosAgrupados as $pedido): ?>
                        <?php
                        $itensJson = (string) json_encode($pedido['itens'], JSON_UNESCAPED_UNICODE);
                        $pagoTotal = !empty($pedido['pago_total']);
                        $pagamentoNaEntrega = !empty($pedido['pagamento_na_entrega']);
                        $valorPago = (float) ($pedido['valor_pago'] ?? 0);
                        $saldo = (float) ($pedido['saldo'] ?? 0);
                        $pedidoStatusLabel = $pagoTotal
                            ? 'Pago'
                            : ($valorPago > 0 && $saldo > 0
                                ? 'Saldo pendente'
                                : ($pagamentoNaEntrega ? 'Pagamento na entrega' : 'Pendente'));
                        $pedidoStatusClass = $pagoTotal
                            ? 'status-pill status-pill-active'
                            : 'status-pill status-pill-transit';
                        $pedidoInfo = 'Loja virtual • ' . format_datetime($pedido['criado_em'] ?? null)
                            . ' • ' . count($pedido['itens']) . ' item(ns)';
                        ?>
                        <tr data-filter-row>
                            <td class="js-entrega-col">
                                <?php if ($pagoTotal): ?>
                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        data-entrega-item
                                        value="pedido:<?= (int) $pedido['id'] ?>"
                                        data-buyer="pedido:<?= (int) $pedido['id'] ?>"
                                        data-nome="Pedido #<?= e((string) $pedido['codigo']) ?>"
                                        data-qtd="<?= (int) $pedido['quantidade'] ?>"
                                        data-preco="<?= e(format_money($pedido['total'])) ?>"
                                    >
                                <?php else: ?>
                                    <input type="checkbox" class="form-check-input" disabled>
                                    <span class="small text-muted d-block">Aguardando pagamento</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="loja-item-cell-info">
                                    <strong class="d-block">Pedido #<?= e((string) $pedido['codigo']) ?></strong>
                                    <span class="small text-muted d-block">
                                        <?= count($pedido['itens']) ?> item(ns) • Loja virtual
                                    </span>
                                    <span class="small text-muted d-block">
                                        <?= e((string) $pedido['primeiro_item']) ?><?= count($pedido['itens']) > 1 ? ' +' . (count($pedido['itens']) - 1) : '' ?>
                                    </span>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-secondary mt-1"
                                        data-bs-toggle="modal"
                                        data-bs-target="#registroDetalhesModal"
                                        data-registro-titulo="Pedido #<?= e((string) $pedido['codigo']) ?>"
                                        data-registro-subtitulo="<?= e((string) $pedido['cliente_nome']) ?>"
                                        data-registro-info="<?= e($pedidoInfo) ?>"
                                        data-registro-itens="<?= e($itensJson) ?>"
                                    >
                                        <i class="bi bi-list-ul" aria-hidden="true"></i>
                                        Ver detalhes
                                    </button>
                                </div>
                            </td>
                            <td>
                                <strong class="d-block"><?= e((string) $pedido['cliente_nome']) ?></strong>
                                <?php if (!empty($pedido['cliente_email'])): ?>
                                    <span class="small text-muted d-block"><?= e((string) $pedido['cliente_email']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="small text-muted">Qtd.: <strong class="text-body"><?= (int) $pedido['quantidade'] ?></strong></span>
                                    <span class="small text-muted">Total: <strong class="text-body"><?= e(format_money($pedido['total'])) ?></strong></span>
                                    <span class="small text-muted">
                                        Pago: <strong class="text-body"><?= $valorPago > 0 ? e(format_money($valorPago)) : ($pagamentoNaEntrega ? 'Na entrega' : 'Aguardando') ?></strong>
                                    </span>
                                    <?php if ($saldo > 0): ?>
                                        <span class="small text-muted">
                                            <span class="text-danger">A receber:</span>
                                            <strong class="text-danger"><?= e(format_money($saldo)) ?></strong>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="<?= e($pedidoStatusClass) ?>"><?= e($pedidoStatusLabel) ?></span>
                                <span class="small text-muted d-block mt-1">Criado: <?= e(format_datetime($pedido['criado_em'] ?? null)) ?></span>
                                <span class="small text-muted d-block">Expira: <?= e(format_datetime($pedido['expira_em'] ?? null)) ?></span>
                            </td>
                            <td class="text-end">
                                <?php if ($pagoTotal): ?>
                                    <span class="status-pill status-pill-transit">
                                        <i class="bi bi-clock-history" aria-hidden="true"></i>
                                        Aguardando entrega
                                    </span>
                                <?php else: ?>
                                    <div class="dropdown d-inline-block">
                                        <button
                                            class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                            type="button"
                                            data-bs-toggle="dropdown"
                                            data-vendas-actions
                                            aria-expanded="false"
                                        >
                                            <i class="bi bi-three-dots" aria-hidden="true"></i>
                                            Ações
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <button
                                                type="button"
                                                class="dropdown-item"
                                                data-bs-toggle="modal"
                                                data-bs-target="#registroDetalhesModal"
                                                data-registro-titulo="Pedido #<?= e((string) $pedido['codigo']) ?>"
                                                data-registro-subtitulo="<?= e((string) $pedido['cliente_nome']) ?>"
                                                data-registro-info="<?= e($pedidoInfo) ?>"
                                                data-registro-itens="<?= e($itensJson) ?>"
                                            >
                                                <i class="bi bi-list-ul me-1" aria-hidden="true"></i>
                                                Ver detalhes
                                            </button>
                                            <form method="post" action="<?= e(url('/admin/vendas/online/' . (int) $pedido['id'] . '/marcar-pago')) ?>" class="m-0">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="dropdown-item">
                                                    <i class="bi bi-check2-circle me-1" aria-hidden="true"></i>
                                                    Marcar como pago
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php foreach ($reservas as $reserva): ?>
                        <?php
                        $preVenda = $reserva['valor_reserva'] !== null;
                        $valorItem = (float) ($reserva['preco_venda'] ?? 0);
                        $valorReserva = $preVenda ? (float) $reserva['valor_reserva'] : null;
                        $pagamentoNaEntrega = !empty($reserva['pagamento_na_entrega']);
                        $saldoPago = !empty($reserva['saldo_pago_em']);
                        $valorPago = $pagamentoNaEntrega
                            ? (!empty($reserva['pago_em']) && $preVenda ? $valorReserva : null)
                            : ($preVenda ? ($saldoPago ? $valorItem : $valorReserva) : $valorItem);
                        $saldoDevedor = $pagamentoNaEntrega
                            ? max(0, $valorItem - (!empty($reserva['pago_em']) && $valorReserva !== null ? $valorReserva : 0.0))
                            : ($preVenda && !$saldoPago ? max(0, $valorItem - ($valorReserva ?? 0)) : 0.0);
                        $pagamentoTotal = !$pagamentoNaEntrega && !empty($reserva['pago_em'])
                            && ($reserva['valor_reserva'] === null || $saldoPago);
                        $mpTexto = $mpLabel((string) ($reserva['mp_status'] ?? ''));
                        $canalSlug = (string) ($reserva['canal_slug'] ?? '');
                        ?>
                        <tr data-filter-row>
                            <td class="js-entrega-col">
                                <?php if ($pagamentoTotal): ?>
                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        data-entrega-item
                                        value="<?= (int) $reserva['id'] ?>"
                                        data-buyer="<?= e((string) ($reserva['telegram_user_id'] ?? '')) ?>"
                                        data-nome="<?= e((string) $reserva['item_nome']) ?>"
                                        data-qtd="<?= (int) $reserva['quantidade'] ?>"
                                        data-preco="<?= e(format_money((float) ($reserva['preco_venda'] ?? 0))) ?>"
                                    >
                                <?php else: ?>
                                    <input type="checkbox" class="form-check-input" disabled>
                                    <span class="small text-muted d-block">Aguardando pagamento</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="loja-item-cell-info">
                                    <strong class="d-block"><?= e((string) $reserva['item_nome']) ?></strong>
                                    <?php if (!empty($reserva['item_codigo'])): ?>
                                        <span class="small text-muted d-block">SKU: <?= e((string) $reserva['item_codigo']) ?></span>
                                    <?php endif; ?>
                                    <span class="d-flex flex-wrap gap-1 mt-1">
                                        <span class="badge-soft">
                                            <i class="bi <?= $canalSlug === 'telegram' ? 'bi-telegram' : ($canalSlug === 'whatsapp' ? 'bi-whatsapp' : 'bi-shop') ?>" aria-hidden="true"></i>
                                            <?= e((string) ($reserva['canal_nome'] ?? '—')) ?>
                                        </span>
                                        <?php if ($preVenda): ?>
                                            <span class="badge-soft badge-soft-yellow">
                                                <i class="bi bi-calendar2-check" aria-hidden="true"></i>
                                                Pré-venda
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($pagamentoNaEntrega): ?>
                                            <span class="badge-soft badge-soft-yellow">
                                                <i class="bi bi-cash-coin" aria-hidden="true"></i>
                                                Pagamento na entrega
                                            </span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($reserva['comprador_nome'])): ?>
                                    <strong class="d-block"><?= e((string) $reserva['comprador_nome']) ?></strong>
                                <?php endif; ?>
                                <?php if (!empty($reserva['telegram_user_id'])): ?>
                                    <span class="small text-muted d-block">Telegram: <?= e((string) $reserva['telegram_user_id']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="small text-muted">Qtd.: <strong class="text-body"><?= (int) $reserva['quantidade'] ?></strong></span>
                                    <span class="small text-muted">Item: <strong class="text-body"><?= e(format_money($valorItem)) ?></strong></span>
                                    <span class="small text-muted">Pago: <strong class="text-body"><?= $valorPago === null ? 'Na entrega' : e(format_money($valorPago)) ?></strong></span>
                                    <?php if ($saldoDevedor > 0): ?>
                                        <span class="small text-muted">
                                            <span class="text-danger">A receber:</span> <strong class="text-danger"><?= e(format_money($saldoDevedor)) ?></strong>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="status-pill status-pill-active">Confirmada</span>
                                <?php if ($mpTexto !== ''): ?>
                                    <span class="small text-muted d-block mt-1"><?= e($mpTexto) ?></span>
                                <?php endif; ?>
                                <span class="small text-muted d-block mt-1">Criado: <?= e(format_datetime($reserva['criado_em'] ?? null)) ?></span>
                                <span class="small text-muted d-block">Expira: <?= e(format_datetime($reserva['expira_em'] ?? null)) ?></span>
                            </td>
                            <td class="text-end">
                                <?php if ($pagamentoTotal): ?>
                                    <span class="status-pill status-pill-transit">
                                        <i class="bi bi-clock-history" aria-hidden="true"></i>
                                        Aguardando entrega
                                    </span>
                                <?php else: ?>
                                <div class="dropdown d-inline-block">
                                    <button
                                        class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                        type="button"
                                        data-bs-toggle="dropdown"
                                        data-vendas-actions
                                        aria-expanded="false"
                                    >
                                        <i class="bi bi-three-dots" aria-hidden="true"></i>
                                        Ações
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <form method="post" action="<?= e(url('/admin/vendas/' . (int) $reserva['id'] . '/verificar')) ?>" class="m-0">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="dropdown-item">
                                                <i class="bi bi-arrow-repeat me-1" aria-hidden="true"></i>
                                                Verificar
                                            </button>
                                        </form>
                                        <?php if ($pagamentoNaEntrega): ?>
                                            <form method="post" action="<?= e(url('/admin/vendas/' . (int) $reserva['id'] . '/cobrar-restante')) ?>" class="m-0">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="dropdown-item">
                                                    <i class="bi bi-send me-1" aria-hidden="true"></i>
                                                    Enviar cobrança
                                                </button>
                                            </form>
                                            <form method="post" action="<?= e(url('/admin/vendas/' . (int) $reserva['id'] . '/marcar-pago')) ?>" class="m-0">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="dropdown-item">
                                                    <i class="bi bi-check2-circle me-1" aria-hidden="true"></i>
                                                    Marcar como pago
                                                </button>
                                            </form>
                                            <?php if (!empty($reserva['saldo_link'])): ?>
                                                <a class="dropdown-item" href="<?= e((string) $reserva['saldo_link']) ?>" target="_blank" rel="noopener noreferrer">
                                                    <i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i>
                                                    Abrir link
                                                </a>
                                            <?php endif; ?>
                                        <?php elseif ($preVenda && !$saldoPago): ?>
                                            <form method="post" action="<?= e(url('/admin/vendas/' . (int) $reserva['id'] . '/cobrar-restante')) ?>" class="m-0">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="dropdown-item">
                                                    <i class="bi bi-send me-1" aria-hidden="true"></i>
                                                    Cobrar restante
                                                </button>
                                            </form>
                                            <?php if (!empty($reserva['saldo_link'])): ?>
                                                <a class="dropdown-item" href="<?= e((string) $reserva['saldo_link']) ?>" target="_blank" rel="noopener noreferrer">
                                                    <i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i>
                                                    Abrir link
                                                </a>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="dropdown-item-text text-success">
                                                <i class="bi bi-check2-circle me-1" aria-hidden="true"></i>
                                                Pago integral
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="d-lg-none loja-entry-cards">
            <?php foreach ($pedidosAgrupados as $pedido): ?>
                <?php
                $itensJson = (string) json_encode($pedido['itens'], JSON_UNESCAPED_UNICODE);
                $pagoTotal = !empty($pedido['pago_total']);
                $pagamentoNaEntrega = !empty($pedido['pagamento_na_entrega']);
                $valorPago = (float) ($pedido['valor_pago'] ?? 0);
                $saldo = (float) ($pedido['saldo'] ?? 0);
                $pedidoStatusLabel = $pagoTotal
                    ? 'Pago'
                    : ($valorPago > 0 && $saldo > 0
                        ? 'Saldo pendente'
                        : ($pagamentoNaEntrega ? 'Pagamento na entrega' : 'Pendente'));
                $pedidoStatusClass = $pagoTotal
                    ? 'status-pill status-pill-active'
                    : 'status-pill status-pill-transit';
                $pedidoInfo = 'Loja virtual • ' . format_datetime($pedido['criado_em'] ?? null)
                    . ' • ' . count($pedido['itens']) . ' item(ns)';
                ?>
                <article class="loja-entry-card" data-filter-row>
                    <div class="js-entrega-card form-check mb-1">
                        <?php if ($pagoTotal): ?>
                            <input
                                type="checkbox"
                                class="form-check-input"
                                data-entrega-item
                                value="pedido:<?= (int) $pedido['id'] ?>"
                                data-buyer="pedido:<?= (int) $pedido['id'] ?>"
                                data-nome="Pedido #<?= e((string) $pedido['codigo']) ?>"
                                data-qtd="<?= (int) $pedido['quantidade'] ?>"
                                data-preco="<?= e(format_money($pedido['total'])) ?>"
                            >
                            <label class="form-check-label small">Selecionar para entrega</label>
                        <?php else: ?>
                            <input type="checkbox" class="form-check-input" disabled>
                            <label class="form-check-label small text-muted">Aguardando pagamento</label>
                        <?php endif; ?>
                    </div>

                    <div class="loja-entry-card-top">
                        <div class="loja-entry-card-info">
                            <span class="loja-entry-card-title">Pedido #<?= e((string) $pedido['codigo']) ?></span>
                            <span class="loja-entry-card-date"><?= e((string) $pedido['cliente_nome']) ?></span>
                        </div>
                        <span class="<?= e($pedidoStatusClass) ?>"><?= e($pedidoStatusLabel) ?></span>
                    </div>

                    <div class="loja-entry-card-status">
                        <span class="badge-soft"><i class="bi bi-shop" aria-hidden="true"></i> Loja virtual</span>
                        <span class="small text-muted"><?= count($pedido['itens']) ?> item(ns)</span>
                    </div>

                    <div class="loja-entry-card-stats">
                        <div><span>Qtd.</span><strong><?= (int) $pedido['quantidade'] ?></strong></div>
                        <div><span>Total</span><strong><?= e(format_money($pedido['total'])) ?></strong></div>
                        <div><span>Pago</span><strong><?= $valorPago > 0 ? e(format_money($valorPago)) : ($pagamentoNaEntrega ? 'Na entrega' : 'Aguardando') ?></strong></div>
                        <?php if ($saldo > 0): ?>
                            <div><span>A receber</span><strong class="text-danger"><?= e(format_money($saldo)) ?></strong></div>
                        <?php endif; ?>
                    </div>

                    <div class="small text-muted">
                        Criado em <?= e(format_datetime($pedido['criado_em'] ?? null)) ?><br>
                        Expira em <?= e(format_datetime($pedido['expira_em'] ?? null)) ?>
                    </div>

                    <?php if ($pagoTotal): ?>
                        <div class="loja-entry-card-actions">
                            <span class="status-pill status-pill-transit">
                                <i class="bi bi-clock-history" aria-hidden="true"></i>
                                Aguardando entrega
                            </span>
                        </div>
                    <?php else: ?>
                        <div class="loja-entry-card-actions">
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-secondary"
                                data-bs-toggle="modal"
                                data-bs-target="#registroDetalhesModal"
                                data-registro-titulo="Pedido #<?= e((string) $pedido['codigo']) ?>"
                                data-registro-subtitulo="<?= e((string) $pedido['cliente_nome']) ?>"
                                data-registro-info="<?= e($pedidoInfo) ?>"
                                data-registro-itens="<?= e($itensJson) ?>"
                            >
                                <i class="bi bi-list-ul" aria-hidden="true"></i>
                                Ver detalhes
                            </button>
                            <form method="post" action="<?= e(url('/admin/vendas/online/' . (int) $pedido['id'] . '/marcar-pago')) ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-check2-circle" aria-hidden="true"></i>
                                    Marcar como pago
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
            <?php foreach ($reservas as $reserva): ?>
                <?php
                $preVenda = $reserva['valor_reserva'] !== null;
                $valorItem = (float) ($reserva['preco_venda'] ?? 0);
                $valorReserva = $preVenda ? (float) $reserva['valor_reserva'] : null;
                $pagamentoNaEntrega = !empty($reserva['pagamento_na_entrega']);
                $saldoPago = !empty($reserva['saldo_pago_em']);
                $valorPago = $pagamentoNaEntrega
                    ? (!empty($reserva['pago_em']) && $preVenda ? $valorReserva : null)
                    : ($preVenda ? ($saldoPago ? $valorItem : $valorReserva) : $valorItem);
                $saldoDevedor = $pagamentoNaEntrega
                    ? max(0, $valorItem - (!empty($reserva['pago_em']) && $valorReserva !== null ? $valorReserva : 0.0))
                    : ($preVenda && !$saldoPago ? max(0, $valorItem - ($valorReserva ?? 0)) : 0.0);
                $pagamentoTotal = !$pagamentoNaEntrega && !empty($reserva['pago_em'])
                    && ($reserva['valor_reserva'] === null || $saldoPago);
                $mpTexto = $mpLabel((string) ($reserva['mp_status'] ?? ''));
                $canalSlug = (string) ($reserva['canal_slug'] ?? '');
                ?>
                <article class="loja-entry-card" data-filter-row>
                    <div class="js-entrega-card form-check mb-1">
                        <?php if ($pagamentoTotal): ?>
                            <input
                                type="checkbox"
                                class="form-check-input"
                                data-entrega-item
                                value="<?= (int) $reserva['id'] ?>"
                                data-buyer="<?= e((string) ($reserva['telegram_user_id'] ?? '')) ?>"
                                data-nome="<?= e((string) $reserva['item_nome']) ?>"
                                data-qtd="<?= (int) $reserva['quantidade'] ?>"
                                data-preco="<?= e(format_money((float) ($reserva['preco_venda'] ?? 0))) ?>"
                            >
                            <label class="form-check-label small">Selecionar para entrega</label>
                        <?php else: ?>
                            <input type="checkbox" class="form-check-input" disabled>
                            <label class="form-check-label small text-muted">Aguardando pagamento</label>
                        <?php endif; ?>
                    </div>
                    <div class="loja-entry-card-top">
                        <div class="loja-entry-card-info">
                            <span class="loja-entry-card-title"><?= e((string) $reserva['item_nome']) ?></span>
                            <span class="loja-entry-card-date">
                                <?= !empty($reserva['comprador_nome']) ? e((string) $reserva['comprador_nome']) : 'Comprador não identificado' ?>
                            </span>
                        </div>
                        <div class="d-flex flex-column gap-1 align-items-end">
                            <span class="badge-soft">
                                <i class="bi <?= $canalSlug === 'telegram' ? 'bi-telegram' : ($canalSlug === 'whatsapp' ? 'bi-whatsapp' : 'bi-shop') ?>" aria-hidden="true"></i>
                                <?= e((string) ($reserva['canal_nome'] ?? '—')) ?>
                            </span>
                            <?php if ($preVenda): ?>
                                <span class="badge-soft badge-soft-yellow">
                                    <i class="bi bi-calendar2-check" aria-hidden="true"></i>
                                    Pré-venda
                                </span>
                            <?php endif; ?>
                            <?php if ($pagamentoNaEntrega): ?>
                                <span class="badge-soft badge-soft-yellow">
                                    <i class="bi bi-cash-coin" aria-hidden="true"></i>
                                    Pagamento na entrega
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="loja-entry-card-status">
                        <span class="status-pill status-pill-active">Confirmada</span>
                        <?php if ($mpTexto !== ''): ?>
                            <span class="small text-muted"><?= e($mpTexto) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="loja-entry-card-stats">
                        <div><span>Qtd.</span><strong><?= (int) $reserva['quantidade'] ?></strong></div>
                        <div><span>Valor do item</span><strong><?= e(format_money($valorItem)) ?></strong></div>
                        <div><span>Pago</span><strong><?= $valorPago === null ? 'Na entrega' : e(format_money($valorPago)) ?></strong></div>
                        <?php if ($saldoDevedor > 0): ?>
                            <div>
                                <span>A receber</span>
                                <strong class="text-danger"><?= e(format_money($saldoDevedor)) ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="small text-muted">
                        Criado em <?= e(format_datetime($reserva['criado_em'] ?? null)) ?><br>
                        Expira em <?= e(format_datetime($reserva['expira_em'] ?? null)) ?>
                    </div>

                    <?php if ($pagamentoTotal): ?>
                        <div class="loja-entry-card-actions">
                            <span class="status-pill status-pill-transit">
                                <i class="bi bi-clock-history" aria-hidden="true"></i>
                                Aguardando entrega
                            </span>
                        </div>
                    <?php else: ?>
                    <div class="loja-entry-card-actions">
                        <form method="post" action="<?= e(url('/admin/vendas/' . (int) $reserva['id'] . '/verificar')) ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
                                Verificar
                            </button>
                        </form>
                        <?php if ($pagamentoNaEntrega): ?>
                            <form method="post" action="<?= e(url('/admin/vendas/' . (int) $reserva['id'] . '/cobrar-restante')) ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-brand">
                                    <i class="bi bi-send" aria-hidden="true"></i>
                                    Enviar cobrança
                                </button>
                            </form>
                            <form method="post" action="<?= e(url('/admin/vendas/' . (int) $reserva['id'] . '/marcar-pago')) ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-check2-circle" aria-hidden="true"></i>
                                    Marcar como pago
                                </button>
                            </form>
                        <?php elseif ($preVenda && !$saldoPago): ?>
                            <form method="post" action="<?= e(url('/admin/vendas/' . (int) $reserva['id'] . '/cobrar-restante')) ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-brand">
                                    <i class="bi bi-send" aria-hidden="true"></i>
                                    Cobrar restante
                                </button>
                            </form>
                        <?php else: ?>
                            <span class="badge-soft"><i class="bi bi-check2-circle" aria-hidden="true"></i> Pago integral</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<div class="modal fade" id="entregaModal" tabindex="-1" aria-labelledby="entregaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="post" action="<?= e(url('/admin/vendas/entregas')) ?>" id="entregaForm">
                <?= csrf_field() ?>
                <input type="hidden" name="telegram_user_id" id="entregaTelegramId" value="">
                <div id="entregaItensIds"></div>

                <div class="modal-header">
                    <div>
                        <h3 class="modal-title h5" id="entregaModalLabel">Criar entrega</h3>
                        <p class="modal-subtitle mb-0">Confira os itens e preencha os dados de entrega.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-light border">
                        <strong>Itens selecionados</strong>
                        <ul class="mb-0 mt-1" id="entregaItensResumo"></ul>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="entregaNome" class="form-label">Nome</label>
                            <input type="text" class="form-control" id="entregaNome" name="nome" required maxlength="180">
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="entregaCpf" class="form-label">CPF</label>
                            <input type="text" class="form-control" id="entregaCpf" name="cpf" maxlength="20" inputmode="numeric">
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="entregaTelefone" class="form-label">Telefone</label>
                            <input type="text" class="form-control" id="entregaTelefone" name="telefone" maxlength="30" inputmode="tel">
                        </div>

                        <div class="col-6 col-md-3">
                            <label for="entregaCep" class="form-label">CEP</label>
                            <input type="text" class="form-control" id="entregaCep" name="cep" maxlength="9" inputmode="numeric" placeholder="00000-000" required>
                        </div>
                        <div class="col-12 col-md-7">
                            <label for="entregaLogradouro" class="form-label">Logradouro</label>
                            <input type="text" class="form-control" id="entregaLogradouro" name="logradouro" maxlength="200" required>
                        </div>
                        <div class="col-6 col-md-2">
                            <label for="entregaNumero" class="form-label">Número</label>
                            <input type="text" class="form-control" id="entregaNumero" name="numero" maxlength="20" required>
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="entregaComplemento" class="form-label">Complemento</label>
                            <input type="text" class="form-control" id="entregaComplemento" name="complemento" maxlength="120">
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="entregaBairro" class="form-label">Bairro</label>
                            <input type="text" class="form-control" id="entregaBairro" name="bairro" maxlength="120" required>
                        </div>
                        <div class="col-6 col-md-4">
                            <label for="entregaCidade" class="form-label">Cidade</label>
                            <input type="text" class="form-control" id="entregaCidade" name="cidade" maxlength="120" required>
                        </div>
                        <div class="col-6 col-md-1">
                            <label for="entregaEstado" class="form-label">UF</label>
                            <input type="text" class="form-control" id="entregaEstado" name="estado" maxlength="2" required>
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="entregaCodigoRastreio" class="form-label">Código de rastreio (opcional)</label>
                            <input type="text" class="form-control" id="entregaCodigoRastreio" name="codigo_rastreio" maxlength="120" placeholder="Ex.: BR123456789BR">
                        </div>
                        <div class="col-12 col-md-8">
                            <label for="entregaObservacoes" class="form-label">Observações (opcional)</label>
                            <textarea class="form-control" id="entregaObservacoes" name="observacoes" rows="2" maxlength="2000"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-brand">
                        <i class="bi bi-check-lg" aria-hidden="true"></i>
                        Confirmar entrega
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../loja/partials/registro-detalhes-modal.php'; ?>
