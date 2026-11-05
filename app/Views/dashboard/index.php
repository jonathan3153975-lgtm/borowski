<?php

declare(strict_types=1);

$user = $user ?? auth_user();
$modules = [
    [
        'icon' => 'bi-cash-stack',
        'title' => 'Vendas do mês',
        'status' => 'Aguardando módulo de vendas',
    ],
    [
        'icon' => 'bi-receipt',
        'title' => 'Pedidos recebidos',
        'status' => 'Aguardando módulo de pedidos',
    ],
    [
        'icon' => 'bi-box-seam',
        'title' => 'Produtos cadastrados',
        'status' => 'Aguardando módulo de produtos',
    ],
    [
        'icon' => 'bi-exclamation-triangle',
        'title' => 'Estoque baixo',
        'status' => 'Aguardando módulo de estoque',
    ],
];
?>
<section class="admin-greeting">
    <div>
        <span class="admin-eyebrow">Visão geral</span>
        <h2 class="admin-greeting-title">Olá, <?= e((string) ($user['nome'] ?? 'administrador')) ?>!</h2>
        <p class="admin-greeting-text mb-0">
            Esta é a base do painel administrativo. Os módulos operacionais da loja serão
            habilitados nas próximas etapas do projeto.
        </p>
    </div>
    <span class="badge-soft badge-soft-yellow">Etapa inicial</span>
</section>

<div class="row g-3">
    <?php foreach ($modules as $module): ?>
        <div class="col-12 col-sm-6 col-xl-3">
            <article class="metric-card">
                <div class="metric-card-icon" aria-hidden="true">
                    <i class="bi <?= e($module['icon']) ?>"></i>
                </div>
                <div class="metric-card-body">
                    <h3 class="metric-card-title"><?= e($module['title']) ?></h3>
                    <p class="metric-card-value">—</p>
                    <span class="metric-card-status"><?= e($module['status']) ?></span>
                </div>
                <span class="metric-card-tag">Demonstrativo</span>
            </article>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mt-1">
    <div class="col-12 col-lg-8">
        <section class="panel-card h-100">
            <div class="panel-card-head">
                <div>
                    <h3 class="panel-card-title">Desempenho de vendas</h3>
                    <p class="panel-card-subtitle mb-0">Indicadores ilustrativos, sem dados reais.</p>
                </div>
                <span class="badge-soft">Estado vazio</span>
            </div>
            <div class="empty-state empty-state-chart">
                <i class="bi bi-bar-chart-line" aria-hidden="true"></i>
                <p class="mb-1">Aguardando módulo de relatórios</p>
                <span>O gráfico será exibido quando os módulos de vendas e pedidos estiverem disponíveis.</span>
            </div>
        </section>
    </div>

    <div class="col-12 col-lg-4">
        <section class="panel-card h-100">
            <div class="panel-card-head">
                <div>
                    <h3 class="panel-card-title">Últimos pedidos</h3>
                    <p class="panel-card-subtitle mb-0">Nenhum pedido será exibido nesta etapa.</p>
                </div>
            </div>
            <div class="empty-state">
                <i class="bi bi-inbox" aria-hidden="true"></i>
                <p class="mb-1">Aguardando módulo de pedidos</p>
                <span>Quando o módulo estiver ativo, os pedidos recentes aparecerão aqui.</span>
            </div>
        </section>
    </div>
</div>

<p class="admin-disclaimer">
    <i class="bi bi-info-circle" aria-hidden="true"></i>
    Os cards acima são demonstrativos e não representam dados reais da loja nesta etapa.
</p>
