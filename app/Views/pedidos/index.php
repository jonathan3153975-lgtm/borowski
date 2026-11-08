<?php

declare(strict_types=1);

$reservas = $reservas ?? [];
$pedidosAgrupados = $pedidosAgrupados ?? [];
$canais = $canais ?? [];
$filters = $filters ?? ['canal' => '', 'tipo' => '', 'status' => '', 'busca' => ''];
$canal = (string) ($filters['canal'] ?? '');
$tipo = (string) ($filters['tipo'] ?? '');
$statusFiltro = (string) ($filters['status'] ?? '');
$busca = (string) ($filters['busca'] ?? '');
$total = count($reservas) + count($pedidosAgrupados);
$reservadas = count(array_filter($reservas, static fn (array $r): bool => (string) $r['status'] === 'reservada'))
    + count(array_filter(
        $pedidosAgrupados,
        static fn (array $p): bool => (string) $p['status'] === 'pendente' && empty($p['pagamento_na_entrega'])
    ));

$canalOptions = ['' => 'Todos'];
foreach ($canais as $c) {
    $canalOptions[(string) $c['slug']] = (string) $c['nome'];
}
$tipoOptions = ['' => 'Todos', 'normal' => 'Venda normal', 'pre_venda' => 'Pré-venda'];
$statusOptions = [
    '' => 'Todos',
    'reservada' => 'Reservada',
    'confirmada' => 'Confirmada',
    'expirada' => 'Expirada',
    'cancelada' => 'Cancelada',
];

$baseQuery = array_filter([
    'canal' => $canal,
    'tipo' => $tipo,
    'status' => $statusFiltro,
    'busca' => $busca,
], static fn (string $value): bool => $value !== '');

$makeUrl = static function (array $overrides = []) use ($baseQuery): string {
    $query = array_filter(
        array_merge($baseQuery, $overrides),
        static fn (mixed $value): bool => $value !== null && $value !== ''
    );

    return url('/admin/pedidos' . ($query === [] ? '' : '?' . http_build_query($query)));
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
<section class="panel-card" data-pedidos-auto-refresh="12">
    <div class="panel-card-head">
        <div>
            <h2 class="panel-card-title">Pedidos</h2>
            <p class="panel-card-subtitle mb-0">
                Pedidos com pagamento pendente. Ao quitar todos os pagamentos (ou marcar pagamento na entrega), o pedido passa para Vendas e Entregas.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge-soft"><?= (int) $reservadas ?> reserva(s) ativa(s)</span>
            <span class="badge-soft"><?= (int) $total ?> pedido(s)</span>
            <span class="badge-soft" title="Atualiza automaticamente">
                <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
                Tempo real
            </span>
        </div>
    </div>

    <div class="loja-entry-toolbar" style="padding: 0 1.35rem;">
        <form method="get" action="<?= e(url('/admin/pedidos')) ?>" class="loja-entry-search" role="search">
            <?php if ($canal !== ''): ?>
                <input type="hidden" name="canal" value="<?= e($canal) ?>">
            <?php endif; ?>
            <?php if ($tipo !== ''): ?>
                <input type="hidden" name="tipo" value="<?= e($tipo) ?>">
            <?php endif; ?>
            <?php if ($statusFiltro !== ''): ?>
                <input type="hidden" name="status" value="<?= e($statusFiltro) ?>">
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
                    <a href="<?= e(url('/admin/pedidos')) ?>" class="btn btn-outline-secondary">Limpar</a>
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

        <div class="loja-filter-group">
            <span class="loja-filter-label">Status</span>
            <div class="loja-filter-pills" role="group" aria-label="Filtrar por status">
                <?php foreach ($statusOptions as $value => $label): ?>
                    <a
                        href="<?= e($makeUrl(['status' => $value === '' ? null : $value])) ?>"
                        class="loja-filter-pill<?= $statusFiltro === $value ? ' active' : '' ?>"
                        <?= $statusFiltro === $value ? 'aria-current="page"' : '' ?>
                    ><?= e($label) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php if ($reservas === [] && $pedidosAgrupados === []): ?>
        <div class="empty-state">
            <i class="bi bi-receipt" aria-hidden="true"></i>
            <p class="mb-1">Nenhum pedido encontrado.</p>
            <span>
                <?= $baseQuery !== []
                    ? 'Ajuste os filtros ou a pesquisa.'
                    : 'Quando um cliente clicar em Comprar no Telegram, o pedido aparece aqui.' ?>
            </span>
        </div>
    <?php else: ?>
        <div class="d-none d-lg-block table-responsive">
            <table class="table admin-table loja-table align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">Item</th>
                        <th scope="col">Comprador</th>
                        <th scope="col">Qtd.</th>
                        <th scope="col">Valores</th>
                        <th scope="col">Status</th>
                        <th scope="col">Criado / Expira</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pedidosAgrupados as $pedido): ?>
                        <?php
                        $itensJson = (string) json_encode($pedido['itens'], JSON_UNESCAPED_UNICODE);
                        $valorPago = (float) ($pedido['valor_pago'] ?? 0);
                        $saldo = (float) ($pedido['saldo'] ?? 0);
                        $pedidoStatusLabel = !empty($pedido['pagamento_na_entrega'])
                            ? 'Pagamento na entrega'
                            : ($valorPago > 0 && $saldo > 0
                                ? 'Saldo pendente'
                                : ((string) $pedido['status'] === 'pendente' ? 'Pendente' : ucfirst((string) $pedido['status'])));
                        $pedidoInfo = 'Loja virtual • ' . format_datetime($pedido['criado_em'] ?? null)
                            . ' • ' . count($pedido['itens']) . ' item(ns)';
                        ?>
                        <tr data-filter-row>
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
                            <td><?= (int) $pedido['quantidade'] ?></td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="small text-muted">
                                        Total: <strong class="text-body"><?= e(format_money($pedido['total'])) ?></strong>
                                    </span>
                                    <span class="small text-muted">
                                        Pago: <strong class="text-body"><?= $valorPago > 0 ? e(format_money($valorPago)) : 'Aguardando' ?></strong>
                                    </span>
                                    <?php if ($saldo > 0): ?>
                                        <span class="small text-muted">
                                            <span class="text-danger">A receber:</span> <strong class="text-danger"><?= e(format_money($saldo)) ?></strong>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($pedido['pagamento_na_entrega'])): ?>
                                        <span class="badge-soft badge-soft-yellow">Pagamento na entrega</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="status-pill status-pill-pre"><?= e($pedidoStatusLabel) ?></span>
                            </td>
                            <td>
                                <span class="small text-muted d-block">
                                    Criado: <?= e(format_datetime($pedido['criado_em'] ?? null)) ?>
                                </span>
                                <span class="small text-muted d-block">
                                    Expira: <?= e(format_datetime($pedido['expira_em'] ?? null)) ?>
                                </span>
                                <?php if (empty($pedido['pagamento_na_entrega']) && $valorPago <= 0): ?>
                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                        <form method="post" action="<?= e(url('/admin/pedidos/online/' . (int) $pedido['id'] . '/pagamento-entrega')) ?>">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-secondary" title="Habilitar o pedido para entrega com pagamento na entrega">
                                                <i class="bi bi-cash-coin" aria-hidden="true"></i>
                                                Pagamento na entrega
                                            </button>
                                        </form>
                                        <form method="post" action="<?= e(url('/admin/pedidos/online/' . (int) $pedido['id'] . '/verificar')) ?>">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-secondary" title="Consultar pagamentos no Mercado Pago">
                                                <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
                                                Verificar
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php foreach ($reservas as $reserva): ?>
                        <?php
                        $status = (string) $reserva['status'];
                        $statusClasse = match ($status) {
                            'reservada' => 'status-pill status-pill-transit',
                            'confirmada' => 'status-pill status-pill-active',
                            default => 'status-pill',
                        };
                        $statusLabel = match ($status) {
                            'reservada' => 'Reservada',
                            'confirmada' => 'Confirmada',
                            'expirada' => 'Expirada',
                            'cancelada' => 'Cancelada',
                            default => ucfirst($status),
                        };
                        $preVenda = $reserva['valor_reserva'] !== null;
                        $valorItem = (float) ($reserva['preco_venda'] ?? 0);
                        $valorReserva = $preVenda ? (float) $reserva['valor_reserva'] : null;
                        $confirmada = $status === 'confirmada';
                        $saldoPago = !empty($reserva['saldo_pago_em']);
                        $valorPago = null;
                        if ($confirmada) {
                            $valorPago = $preVenda ? ($saldoPago ? $valorItem : $valorReserva) : $valorItem;
                        }
                        $saldoDevedor = $preVenda ? max(0, $valorItem - ($valorReserva ?? 0)) : null;
                        $mpStatus = (string) ($reserva['mp_status'] ?? '');
                        $mpTexto = $mpLabel($mpStatus);
                        $canalSlug = (string) ($reserva['canal_slug'] ?? '');
                        ?>
                        <tr data-filter-row>
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
                            <td><?= (int) $reserva['quantidade'] ?></td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="small text-muted">Item: <strong class="text-body"><?= e(format_money($valorItem)) ?></strong></span>
                                    <span class="small text-muted">
                                        Pago: <strong class="text-body"><?= $valorPago !== null ? e(format_money($valorPago)) : 'Aguardando' ?></strong>
                                    </span>
                                    <?php if ($preVenda && !$saldoPago): ?>
                                        <span class="small text-muted">Saldo: <strong class="text-danger"><?= e(format_money($saldoDevedor ?? 0)) ?></strong></span>
                                    <?php endif; ?>
                                    <?php if ($preVenda && $confirmada && empty($reserva['saldo_pago_em'])): ?>
                                        <form
                                            method="post"
                                            action="<?= e(url('/admin/pedidos/' . (int) $reserva['id'] . '/cobrar-restante')) ?>"
                                            class="mt-1"
                                        >
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-secondary" title="Gerar link do saldo e enviar ao comprador">
                                                <i class="bi bi-send" aria-hidden="true"></i>
                                                Cobrar restante
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="<?= e($statusClasse) ?>"><?= e($statusLabel) ?></span>
                                <?php if ($mpTexto !== ''): ?>
                                    <span class="small text-muted d-block mt-1"><?= e($mpTexto) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="small text-muted d-block">
                                    Criado: <?= e(format_datetime($reserva['criado_em'] ?? null)) ?>
                                </span>
                                <span class="small text-muted d-block">
                                    Expira: <?= e(format_datetime($reserva['expira_em'] ?? null)) ?>
                                </span>
                                <form
                                    method="post"
                                    action="<?= e(url('/admin/pedidos/' . (int) $reserva['id'] . '/pagamento-entrega')) ?>"
                                    class="mt-1"
                                >
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-secondary" title="Habilitar para entrega mantendo a cobrança na entrega">
                                        <i class="bi bi-cash-coin" aria-hidden="true"></i>
                                        Pagamento na entrega
                                    </button>
                                </form>
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
                $valorPago = (float) ($pedido['valor_pago'] ?? 0);
                $saldo = (float) ($pedido['saldo'] ?? 0);
                $pedidoStatusLabel = !empty($pedido['pagamento_na_entrega'])
                    ? 'Pagamento na entrega'
                    : ($valorPago > 0 && $saldo > 0
                        ? 'Saldo pendente'
                        : ((string) $pedido['status'] === 'pendente' ? 'Pendente' : ucfirst((string) $pedido['status'])));
                $pedidoInfo = 'Loja virtual • ' . format_datetime($pedido['criado_em'] ?? null)
                    . ' • ' . count($pedido['itens']) . ' item(ns)';
                ?>
                <article class="loja-entry-card" data-filter-row>
                    <div class="loja-entry-card-top">
                        <div class="loja-entry-card-info">
                            <span class="loja-entry-card-title">Pedido #<?= e((string) $pedido['codigo']) ?></span>
                            <span class="loja-entry-card-date"><?= e((string) $pedido['cliente_nome']) ?></span>
                        </div>
                        <span class="status-pill status-pill-pre"><?= e($pedidoStatusLabel) ?></span>
                    </div>

                    <div class="loja-entry-card-status">
                        <span class="badge-soft"><i class="bi bi-shop" aria-hidden="true"></i> Loja virtual</span>
                        <span class="small text-muted"><?= count($pedido['itens']) ?> item(ns)</span>
                    </div>

                    <div class="loja-entry-card-stats">
                        <div><span>Qtd.</span><strong><?= (int) $pedido['quantidade'] ?></strong></div>
                        <div><span>Total</span><strong><?= e(format_money($pedido['total'])) ?></strong></div>
                        <div><span>Pago</span><strong><?= $valorPago > 0 ? e(format_money($valorPago)) : 'Aguardando' ?></strong></div>
                        <?php if ($saldo > 0): ?>
                            <div><span>A receber</span><strong class="text-danger"><?= e(format_money($saldo)) ?></strong></div>
                        <?php endif; ?>
                    </div>

                    <div class="small text-muted">
                        Criado em <?= e(format_datetime($pedido['criado_em'] ?? null)) ?><br>
                        Expira em <?= e(format_datetime($pedido['expira_em'] ?? null)) ?>
                    </div>

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
                        <?php if (empty($pedido['pagamento_na_entrega']) && $valorPago <= 0): ?>
                            <form method="post" action="<?= e(url('/admin/pedidos/online/' . (int) $pedido['id'] . '/pagamento-entrega')) ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-cash-coin" aria-hidden="true"></i>
                                    Pagamento na entrega
                                </button>
                            </form>
                            <form method="post" action="<?= e(url('/admin/pedidos/online/' . (int) $pedido['id'] . '/verificar')) ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
                                    Verificar
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
            <?php foreach ($reservas as $reserva): ?>
                <?php
                $status = (string) $reserva['status'];
                $statusClasse = match ($status) {
                    'reservada' => 'status-pill status-pill-transit',
                    'confirmada' => 'status-pill status-pill-active',
                    default => 'status-pill',
                };
                $statusLabel = match ($status) {
                    'reservada' => 'Reservada',
                    'confirmada' => 'Confirmada',
                    'expirada' => 'Expirada',
                    'cancelada' => 'Cancelada',
                    default => ucfirst($status),
                };
                $preVenda = $reserva['valor_reserva'] !== null;
                $valorItem = (float) ($reserva['preco_venda'] ?? 0);
                $valorReserva = $preVenda ? (float) $reserva['valor_reserva'] : null;
                $confirmada = $status === 'confirmada';
                $saldoPago = !empty($reserva['saldo_pago_em']);
                $valorPago = null;
                if ($confirmada) {
                    $valorPago = $preVenda ? ($saldoPago ? $valorItem : $valorReserva) : $valorItem;
                }
                $saldoDevedor = $preVenda ? max(0, $valorItem - ($valorReserva ?? 0)) : null;
                $mpStatus = (string) ($reserva['mp_status'] ?? '');
                $mpTexto = $mpLabel($mpStatus);
                $canalSlug = (string) ($reserva['canal_slug'] ?? '');
                ?>
                <article class="loja-entry-card" data-filter-row>
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
                        </div>
                    </div>

                    <div class="loja-entry-card-status">
                        <span class="<?= e($statusClasse) ?>"><?= e($statusLabel) ?></span>
                        <?php if ($mpTexto !== ''): ?>
                            <span class="small text-muted"><?= e($mpTexto) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="loja-entry-card-stats">
                        <div>
                            <span>Qtd.</span>
                            <strong><?= (int) $reserva['quantidade'] ?></strong>
                        </div>
                        <div>
                            <span>Valor do item</span>
                            <strong><?= e(format_money($valorItem)) ?></strong>
                        </div>
                        <div>
                            <span>Pago</span>
                            <strong><?= $valorPago !== null ? e(format_money($valorPago)) : 'Aguardando' ?></strong>
                        </div>
                        <?php if ($preVenda && !$saldoPago): ?>
                            <div>
                                <span>Saldo devedor</span>
                                <strong class="text-danger"><?= e(format_money($saldoDevedor ?? 0)) ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="loja-entry-card-actions">
                        <?php if ($preVenda && $confirmada && empty($reserva['saldo_pago_em'])): ?>
                            <form method="post" action="<?= e(url('/admin/pedidos/' . (int) $reserva['id'] . '/cobrar-restante')) ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-send" aria-hidden="true"></i>
                                    Cobrar restante
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <div class="small text-muted">
                        Criado em <?= e(format_datetime($reserva['criado_em'] ?? null)) ?><br>
                        Expira em <?= e(format_datetime($reserva['expira_em'] ?? null)) ?>
                    </div>

                    <form method="post" action="<?= e(url('/admin/pedidos/' . (int) $reserva['id'] . '/pagamento-entrega')) ?>" class="mt-2">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-cash-coin" aria-hidden="true"></i>
                            Pagamento na entrega
                        </button>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../loja/partials/registro-detalhes-modal.php'; ?>
