<?php

declare(strict_types=1);

$user = $user ?? auth_user();
$resumo = $resumo ?? [];
$estoque = $resumo['estoque'] ?? [];
$ultimosPedidos = $resumo['ultimos_pedidos'] ?? [];
$entregasRecentes = $resumo['entregas_recentes'] ?? [];

$cards = [
    [
        'icon' => 'bi-receipt',
        'title' => 'Pedidos pendentes',
        'value' => (string) (int) ($resumo['pedidos_pendentes'] ?? 0),
        'status' => 'Aguardando pagamento',
        'href' => '/admin/pedidos',
    ],
    [
        'icon' => 'bi-exclamation-circle',
        'title' => 'A receber',
        'value' => format_money($resumo['a_receber'] ?? 0),
        'status' => 'Saldo pendente de recebimento',
        'href' => '/admin/caixa?status=saldo_pendente',
    ],
    [
        'icon' => 'bi-cash-stack',
        'title' => 'Vendas do mês',
        'value' => format_money($resumo['vendas_mes'] ?? 0),
        'status' => 'Valores efetivamente pagos',
        'href' => '/admin/vendas',
    ],
    [
        'icon' => 'bi-truck',
        'title' => 'Aguardando entrega',
        'value' => (string) (int) ($resumo['aguardando_entrega'] ?? 0),
        'status' => 'Vendas pagas sem entrega',
        'href' => '/admin/vendas',
    ],
    [
        'icon' => 'bi-box-seam',
        'title' => 'Entregas pendentes',
        'value' => (string) (int) ($resumo['entregas_pendentes'] ?? 0),
        'status' => 'Pendentes ou encaminhadas',
        'href' => '/admin/entregas',
    ],
    [
        'icon' => 'bi-boxes',
        'title' => 'Unidades disponíveis',
        'value' => (int) ($estoque['unidades_disponivel'] ?? 0) . ' / ' . (int) ($estoque['total_unidades'] ?? 0),
        'status' => 'Disponível / total em estoque',
        'href' => '/admin/loja/estoque',
    ],
    [
        'icon' => 'bi-megaphone',
        'title' => 'Publicações ativas',
        'value' => (string) (int) ($resumo['publicacoes_ativas'] ?? 0),
        'status' => 'Anúncios publicados',
        'href' => '/admin/loja/estoque/publicados',
    ],
    [
        'icon' => 'bi-grid',
        'title' => 'Itens cadastrados',
        'value' => (string) (int) ($estoque['total_itens'] ?? 0),
        'status' => 'Itens no estoque',
        'href' => '/admin/loja/estoque',
    ],
];

$statusPedido = static function (array $pedido): array {
    $status = (string) ($pedido['status'] ?? 'pendente');

    if (!empty($pedido['entregue_em'])) {
        return ['Entregue', 'status-pill status-pill-active'];
    }

    if ($status === 'pago') {
        return ['Pago', 'status-pill status-pill-active'];
    }

    if (!empty($pedido['pagamento_na_entrega'])) {
        return ['Pagamento na entrega', 'status-pill status-pill-transit'];
    }

    return [ucfirst($status), 'status-pill status-pill-pre'];
};
?>
<section class="admin-greeting">
    <div>
        <span class="admin-eyebrow">Visão geral</span>
        <h2 class="admin-greeting-title">Olá, <?= e((string) ($user['nome'] ?? 'administrador')) ?>!</h2>
        <p class="admin-greeting-text mb-0">
            Acompanhe pedidos, vendas, caixa, entregas e estoque em tempo real.
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-brand" href="<?= e(url('/admin/vendas/direta')) ?>">
            <i class="bi bi-lightning-charge" aria-hidden="true"></i>
            Venda direta
        </a>
        <a class="btn btn-outline-secondary" href="<?= e(url('/admin/caixa')) ?>">
            <i class="bi bi-safe" aria-hidden="true"></i>
            Caixa
        </a>
    </div>
</section>

<div class="row g-3">
    <?php foreach ($cards as $card): ?>
        <div class="col-12 col-sm-6 col-xl-3">
            <a class="metric-card metric-card-link" href="<?= e(url($card['href'])) ?>">
                <div class="metric-card-icon" aria-hidden="true">
                    <i class="bi <?= e($card['icon']) ?>"></i>
                </div>
                <div class="metric-card-body">
                    <h3 class="metric-card-title"><?= e($card['title']) ?></h3>
                    <p class="metric-card-value"><?= e($card['value']) ?></p>
                    <span class="metric-card-status"><?= e($card['status']) ?></span>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mt-1">
    <div class="col-12 col-lg-7">
        <section class="panel-card h-100">
            <div class="panel-card-head">
                <div>
                    <h3 class="panel-card-title">Últimos pedidos</h3>
                    <p class="panel-card-subtitle mb-0">Pedidos da loja, vendas diretas e canais.</p>
                </div>
                <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/pedidos')) ?>">Ver todos</a>
            </div>

            <?php if ($ultimosPedidos === []): ?>
                <div class="empty-state">
                    <i class="bi bi-inbox" aria-hidden="true"></i>
                    <p class="mb-1">Nenhum pedido registrado.</p>
                    <span>Os pedidos aparecerão aqui conforme forem criados.</span>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table admin-table loja-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Pedido</th>
                                <th scope="col">Cliente</th>
                                <th scope="col">Total</th>
                                <th scope="col">A receber</th>
                                <th scope="col">Status</th>
                                <th scope="col">Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimosPedidos as $pedido): ?>
                                <?php
                                [$statusLabel, $statusClass] = $statusPedido($pedido);
                                $saldo = max(0, (float) ($pedido['subtotal'] ?? 0) - (float) ($pedido['valor_pago'] ?? 0));
                                ?>
                                <tr>
                                    <td><strong>#<?= e((string) $pedido['codigo']) ?></strong></td>
                                    <td><?= e((string) $pedido['cliente_nome']) ?></td>
                                    <td><?= e(format_money($pedido['subtotal'] ?? $pedido['total'] ?? 0)) ?></td>
                                    <td>
                                        <?= $saldo > 0
                                            ? '<strong class="text-danger">' . e(format_money($saldo)) . '</strong>'
                                            : '—' ?>
                                    </td>
                                    <td><span class="<?= e($statusClass) ?>"><?= e($statusLabel) ?></span></td>
                                    <td><?= e(format_datetime($pedido['criado_em'] ?? null)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <div class="col-12 col-lg-5">
        <section class="panel-card h-100">
            <div class="panel-card-head">
                <div>
                    <h3 class="panel-card-title">Entregas em andamento</h3>
                    <p class="panel-card-subtitle mb-0">Pendentes ou encaminhadas.</p>
                </div>
                <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/entregas')) ?>">Ver todas</a>
            </div>

            <?php if ($entregasRecentes === []): ?>
                <div class="empty-state">
                    <i class="bi bi-truck" aria-hidden="true"></i>
                    <p class="mb-1">Nenhuma entrega pendente.</p>
                    <span>As entregas em andamento aparecerão aqui.</span>
                </div>
            <?php else: ?>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($entregasRecentes as $entrega): ?>
                        <li class="d-flex align-items-center justify-content-between gap-2 py-2 border-bottom">
                            <span>
                                <strong class="d-block">Entrega #<?= (int) $entrega['id'] ?></strong>
                                <span class="small text-muted"><?= e((string) $entrega['cliente_nome']) ?></span>
                            </span>
                            <span class="badge-soft">
                                <?= e($entrega['status'] === 'enviada' ? 'Encaminhada' : 'Pendente') ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>
</div>

<section class="panel-card mt-3">
    <div class="panel-card-head">
        <div>
            <h3 class="panel-card-title">Ações rápidas</h3>
            <p class="panel-card-subtitle mb-0">Atalhos para as principais operações.</p>
        </div>
    </div>
    <div class="row g-2">
        <div class="col-6 col-md-4 col-xl-2">
            <a class="btn btn-outline-secondary w-100" href="<?= e(url('/admin/vendas/direta')) ?>">
                <i class="bi bi-lightning-charge" aria-hidden="true"></i>
                Venda direta
            </a>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <a class="btn btn-outline-secondary w-100" href="<?= e(url('/admin/caixa')) ?>">
                <i class="bi bi-safe" aria-hidden="true"></i>
                Caixa
            </a>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <a class="btn btn-outline-secondary w-100" href="<?= e(url('/admin/pedidos')) ?>">
                <i class="bi bi-receipt" aria-hidden="true"></i>
                Pedidos
            </a>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <a class="btn btn-outline-secondary w-100" href="<?= e(url('/admin/vendas')) ?>">
                <i class="bi bi-cash-coin" aria-hidden="true"></i>
                Vendas
            </a>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <a class="btn btn-outline-secondary w-100" href="<?= e(url('/admin/loja/estoque')) ?>">
                <i class="bi bi-boxes" aria-hidden="true"></i>
                Estoque
            </a>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <a class="btn btn-outline-secondary w-100" href="<?= e(url('/admin/entregas')) ?>">
                <i class="bi bi-truck" aria-hidden="true"></i>
                Entregas
            </a>
        </div>
    </div>
</section>
