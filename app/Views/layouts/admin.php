<?php

declare(strict_types=1);

$title = $title ?? 'Painel administrativo';
$brand = (string) config('app.name');
$user = auth_user();
$breadcrumb = $breadcrumb ?? [];
$current = current_path();
$isSuper = is_super_admin();
$dashboardActive = $current === '/admin';
$usersActive = str_starts_with($current, '/admin/usuarios');
$lojaEntradasActive = $current === '/admin/loja' || str_starts_with($current, '/admin/loja/entradas');
$lojaEstoqueActive = str_starts_with($current, '/admin/loja/estoque');
$pedidosActive = str_starts_with($current, '/admin/pedidos');
$vendaDiretaActive = str_starts_with($current, '/admin/vendas/direta');
$vendasActive = str_starts_with($current, '/admin/vendas') && !$vendaDiretaActive;
$caixaActive = str_starts_with($current, '/admin/caixa');
$entregasActive = str_starts_with($current, '/admin/entregas');
$configActive = str_starts_with($current, '/admin/configuracoes');
$configMenu = \App\Services\ConfiguracaoService::MAP;
$extraStyles = $extraStyles ?? [];
$extraScripts = $extraScripts ?? [];
$initial = $user !== null ? mb_strtoupper(mb_substr((string) $user['nome'], 0, 1)) : '?';
$success = flash('success');
$error = flash('error');
$warning = flash('warning');
$info = flash('info');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> | <?= e($brand) ?></title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >
    <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
    <?php foreach ($extraStyles as $style): ?>
        <link rel="stylesheet" href="<?= e((string) $style) ?>">
    <?php endforeach; ?>
</head>
<body class="admin-body">
    <div class="admin-shell" id="adminShell">
        <aside class="admin-sidebar" data-admin-sidebar aria-label="Menu principal">
            <div class="admin-sidebar-head">
                <a href="<?= e(url('/admin')) ?>" class="admin-brand">
                    <span class="admin-brand-mark" aria-hidden="true">LC</span>
                    <span class="admin-brand-text nav-label"><?= e($brand) ?></span>
                </a>
            </div>

            <nav class="admin-nav">
                <span class="admin-nav-label nav-label">Principal</span>
                <a
                    href="<?= e(url('/admin')) ?>"
                    class="admin-nav-link<?= $dashboardActive ? ' active' : '' ?>"
                    <?= $dashboardActive ? 'aria-current="page"' : '' ?>
                >
                    <i class="bi bi-speedometer2" aria-hidden="true"></i>
                    <span class="nav-label">Dashboard</span>
                </a>
                <a
                    href="<?= e(url('/admin/vendas/direta')) ?>"
                    class="admin-nav-link<?= $vendaDiretaActive ? ' active' : '' ?>"
                    <?= $vendaDiretaActive ? 'aria-current="page"' : '' ?>
                >
                    <i class="bi bi-lightning-charge" aria-hidden="true"></i>
                    <span class="nav-label">Venda direta</span>
                </a>
                <a
                    href="<?= e(url('/admin/caixa')) ?>"
                    class="admin-nav-link<?= $caixaActive ? ' active' : '' ?>"
                    <?= $caixaActive ? 'aria-current="page"' : '' ?>
                >
                    <i class="bi bi-safe" aria-hidden="true"></i>
                    <span class="nav-label">Caixa</span>
                </a>

                <span class="admin-nav-label nav-label">Loja</span>
                <a
                    href="<?= e(url('/admin/loja/estoque')) ?>"
                    class="admin-nav-link<?= $lojaEstoqueActive || $lojaEntradasActive ? ' active' : '' ?>"
                    <?= ($lojaEstoqueActive || $lojaEntradasActive) ? 'aria-current="page"' : '' ?>
                >
                    <i class="bi bi-boxes" aria-hidden="true"></i>
                    <span class="nav-label">Estoque</span>
                </a>
                <a
                    href="<?= e(url('/admin/pedidos')) ?>"
                    class="admin-nav-link<?= $pedidosActive ? ' active' : '' ?>"
                    <?= $pedidosActive ? 'aria-current="page"' : '' ?>
                >
                    <i class="bi bi-receipt" aria-hidden="true"></i>
                    <span class="nav-label">Pedidos</span>
                </a>
                <a
                    href="<?= e(url('/admin/vendas')) ?>"
                    class="admin-nav-link<?= $vendasActive ? ' active' : '' ?>"
                    <?= $vendasActive ? 'aria-current="page"' : '' ?>
                >
                    <i class="bi bi-cash-coin" aria-hidden="true"></i>
                    <span class="nav-label">Vendas</span>
                </a>
                <a
                    href="<?= e(url('/admin/entregas')) ?>"
                    class="admin-nav-link<?= $entregasActive ? ' active' : '' ?>"
                    <?= $entregasActive ? 'aria-current="page"' : '' ?>
                >
                    <i class="bi bi-truck" aria-hidden="true"></i>
                    <span class="nav-label">Entregas</span>
                </a>

                <?php if ($isSuper): ?>
                    <span class="admin-nav-label nav-label">Sistema</span>
                    <a
                        href="<?= e(url('/admin/usuarios')) ?>"
                        class="admin-nav-link<?= $usersActive ? ' active' : '' ?>"
                        <?= $usersActive ? 'aria-current="page"' : '' ?>
                    >
                        <i class="bi bi-people" aria-hidden="true"></i>
                        <span class="nav-label">Administradores</span>
                    </a>
                <?php endif; ?>

                <span class="admin-nav-label nav-label">Configurações</span>
                <button
                    type="button"
                    class="admin-nav-link admin-nav-button admin-nav-toggle<?= $configActive ? ' active' : '' ?>"
                    data-bs-toggle="collapse"
                    data-bs-target="#configMenuDesktop"
                    aria-expanded="<?= $configActive ? 'true' : 'false' ?>"
                    aria-controls="configMenuDesktop"
                >
                    <i class="bi bi-gear" aria-hidden="true"></i>
                    <span class="nav-label">Configurações</span>
                    <i class="bi bi-chevron-down ms-auto nav-label" aria-hidden="true"></i>
                </button>
                <div class="collapse admin-nav-collapse<?= $configActive ? ' show' : '' ?>" id="configMenuDesktop">
                    <?php foreach ($configMenu as $tipoMenu => $configItem): ?>
                        <?php $configLinkActive = str_starts_with($current, '/admin/configuracoes/' . $tipoMenu); ?>
                        <a
                            href="<?= e(url('/admin/configuracoes/' . $tipoMenu)) ?>"
                            class="admin-nav-link admin-nav-sublink<?= $configLinkActive ? ' active' : '' ?>"
                            <?= $configLinkActive ? 'aria-current="page"' : '' ?>
                        >
                            <i class="bi <?= e($configItem['icon']) ?>" aria-hidden="true"></i>
                            <span class="nav-label"><?= e($configItem['plural']) ?></span>
                        </a>
                    <?php endforeach; ?>
                    <?php $telegramActive = str_starts_with($current, '/admin/configuracoes/telegram'); ?>
                    <a
                        href="<?= e(url('/admin/configuracoes/telegram')) ?>"
                        class="admin-nav-link admin-nav-sublink<?= $telegramActive ? ' active' : '' ?>"
                        <?= $telegramActive ? 'aria-current="page"' : '' ?>
                    >
                        <i class="bi bi-telegram" aria-hidden="true"></i>
                        <span class="nav-label">Telegram</span>
                    </a>
                    <?php $mpActive = str_starts_with($current, '/admin/configuracoes/mercadopago'); ?>
                    <a
                        href="<?= e(url('/admin/configuracoes/mercadopago')) ?>"
                        class="admin-nav-link admin-nav-sublink<?= $mpActive ? ' active' : '' ?>"
                        <?= $mpActive ? 'aria-current="page"' : '' ?>
                    >
                        <i class="bi bi-credit-card" aria-hidden="true"></i>
                        <span class="nav-label">Mercado Pago</span>
                    </a>
                </div>
            </nav>

            <div class="admin-sidebar-foot">
                <form method="post" action="<?= e(url('/logout')) ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="admin-nav-link admin-nav-button">
                        <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                        <span class="nav-label">Sair</span>
                    </button>
                </form>
            </div>
        </aside>

        <div class="admin-workspace">
            <header class="admin-topbar">
                <div class="admin-topbar-left">
                    <button
                        type="button"
                        class="btn btn-icon d-none d-lg-inline-flex"
                        id="sidebarToggle"
                        aria-label="Recolher menu lateral"
                        aria-controls="adminShell"
                        aria-expanded="true"
                    >
                        <i class="bi bi-layout-sidebar-inset" aria-hidden="true"></i>
                    </button>
                    <button
                        type="button"
                        class="btn btn-icon d-lg-none"
                        data-bs-toggle="offcanvas"
                        data-bs-target="#adminMobileMenu"
                        aria-controls="adminMobileMenu"
                        aria-label="Abrir menu"
                    >
                        <i class="bi bi-list" aria-hidden="true"></i>
                    </button>

                    <?php if ($breadcrumb !== []): ?>
                        <nav aria-label="breadcrumb" class="admin-breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <?php foreach ($breadcrumb as $item): ?>
                                    <?php if (($item['url'] ?? null) !== null): ?>
                                        <li class="breadcrumb-item">
                                            <a href="<?= e(url((string) $item['url'])) ?>"><?= e((string) $item['label']) ?></a>
                                        </li>
                                    <?php else: ?>
                                        <li class="breadcrumb-item active" aria-current="page">
                                            <?= e((string) $item['label']) ?>
                                        </li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ol>
                        </nav>
                    <?php endif; ?>
                </div>

                <div class="admin-topbar-user">
                    <span class="admin-avatar" aria-hidden="true"><?= e($initial) ?></span>
                    <span class="admin-user-info">
                        <span class="admin-user-name"><?= e((string) ($user['nome'] ?? 'Usuário')) ?></span>
                        <span class="admin-user-role">
                            <?= $isSuper ? 'Superadministrador' : 'Administrador' ?>
                        </span>
                    </span>
                </div>
            </header>

            <main class="admin-content">
                <div class="admin-page-head">
                    <div>
                        <span class="admin-eyebrow">Painel administrativo</span>
                        <h1 class="admin-page-title"><?= e($title) ?></h1>
                    </div>
                </div>

                <?php if (is_string($success) && $success !== ''): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= e($success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                    </div>
                <?php endif; ?>

                <?php if (is_string($error) && $error !== ''): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= e($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                    </div>
                <?php endif; ?>

                <?php if (is_string($warning) && $warning !== ''): ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <?= e($warning) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                    </div>
                <?php endif; ?>

                <?php if (is_string($info) && $info !== ''): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <?= e($info) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                    </div>
                <?php endif; ?>

                <?= $content ?>
            </main>
        </div>
    </div>

    <div class="offcanvas offcanvas-start admin-offcanvas" tabindex="-1" id="adminMobileMenu" aria-labelledby="adminMobileMenuLabel">
        <div class="offcanvas-header">
            <h2 class="offcanvas-title h5" id="adminMobileMenuLabel"><?= e($brand) ?></h2>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Fechar"></button>
        </div>
        <div class="offcanvas-body">
            <nav class="admin-nav">
                <a
                    href="<?= e(url('/admin')) ?>"
                    class="admin-nav-link<?= $dashboardActive ? ' active' : '' ?>"
                >
                    <i class="bi bi-speedometer2" aria-hidden="true"></i>
                    <span>Dashboard</span>
                </a>
                <a
                    href="<?= e(url('/admin/vendas/direta')) ?>"
                    class="admin-nav-link<?= $vendaDiretaActive ? ' active' : '' ?>"
                >
                    <i class="bi bi-lightning-charge" aria-hidden="true"></i>
                    <span>Venda direta</span>
                </a>
                <a
                    href="<?= e(url('/admin/caixa')) ?>"
                    class="admin-nav-link<?= $caixaActive ? ' active' : '' ?>"
                >
                    <i class="bi bi-safe" aria-hidden="true"></i>
                    <span>Caixa</span>
                </a>

                <a
                    href="<?= e(url('/admin/loja/estoque')) ?>"
                    class="admin-nav-link<?= ($lojaEstoqueActive || $lojaEntradasActive) ? ' active' : '' ?>"
                >
                    <i class="bi bi-boxes" aria-hidden="true"></i>
                    <span>Estoque</span>
                </a>
                <a
                    href="<?= e(url('/admin/pedidos')) ?>"
                    class="admin-nav-link<?= $pedidosActive ? ' active' : '' ?>"
                >
                    <i class="bi bi-receipt" aria-hidden="true"></i>
                    <span>Pedidos</span>
                </a>
                <a
                    href="<?= e(url('/admin/vendas')) ?>"
                    class="admin-nav-link<?= $vendasActive ? ' active' : '' ?>"
                >
                    <i class="bi bi-cash-coin" aria-hidden="true"></i>
                    <span>Vendas</span>
                </a>
                <a
                    href="<?= e(url('/admin/entregas')) ?>"
                    class="admin-nav-link<?= $entregasActive ? ' active' : '' ?>"
                >
                    <i class="bi bi-truck" aria-hidden="true"></i>
                    <span>Entregas</span>
                </a>

                <?php if ($isSuper): ?>
                    <span class="admin-nav-label">Sistema</span>
                    <a
                        href="<?= e(url('/admin/usuarios')) ?>"
                        class="admin-nav-link<?= $usersActive ? ' active' : '' ?>"
                    >
                        <i class="bi bi-people" aria-hidden="true"></i>
                        <span>Administradores</span>
                    </a>
                <?php endif; ?>

                <span class="admin-nav-label">Configurações</span>
                <button
                    type="button"
                    class="admin-nav-link admin-nav-button admin-nav-toggle"
                    data-bs-toggle="collapse"
                    data-bs-target="#configMenuMobile"
                    aria-expanded="<?= $configActive ? 'true' : 'false' ?>"
                    aria-controls="configMenuMobile"
                >
                    <i class="bi bi-gear" aria-hidden="true"></i>
                    <span>Configurações</span>
                    <i class="bi bi-chevron-down ms-auto" aria-hidden="true"></i>
                </button>
                <div class="collapse admin-nav-collapse<?= $configActive ? ' show' : '' ?>" id="configMenuMobile">
                    <?php foreach ($configMenu as $tipoMenu => $configItem): ?>
                        <?php $configLinkActive = str_starts_with($current, '/admin/configuracoes/' . $tipoMenu); ?>
                        <a
                            href="<?= e(url('/admin/configuracoes/' . $tipoMenu)) ?>"
                            class="admin-nav-link admin-nav-sublink<?= $configLinkActive ? ' active' : '' ?>"
                        >
                            <i class="bi <?= e($configItem['icon']) ?>" aria-hidden="true"></i>
                            <span><?= e($configItem['plural']) ?></span>
                        </a>
                    <?php endforeach; ?>
                    <?php $telegramActive = str_starts_with($current, '/admin/configuracoes/telegram'); ?>
                    <a
                        href="<?= e(url('/admin/configuracoes/telegram')) ?>"
                        class="admin-nav-link admin-nav-sublink<?= $telegramActive ? ' active' : '' ?>"
                    >
                        <i class="bi bi-telegram" aria-hidden="true"></i>
                        <span>Telegram</span>
                    </a>
                    <?php $mpActive = str_starts_with($current, '/admin/configuracoes/mercadopago'); ?>
                    <a
                        href="<?= e(url('/admin/configuracoes/mercadopago')) ?>"
                        class="admin-nav-link admin-nav-sublink<?= $mpActive ? ' active' : '' ?>"
                    >
                        <i class="bi bi-credit-card" aria-hidden="true"></i>
                        <span>Mercado Pago</span>
                    </a>
                </div>
            </nav>

            <form method="post" action="<?= e(url('/logout')) ?>" class="mt-4">
                <?= csrf_field() ?>
                <button type="submit" class="admin-nav-link admin-nav-button">
                    <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                    <span>Sair</span>
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="<?= e(asset('js/masks.js')) ?>"></script>
    <script src="<?= e(asset('js/app.js')) ?>"></script>
    <script src="<?= e(asset('js/admin.js')) ?>"></script>
    <?php foreach ($extraScripts as $script): ?>
        <script src="<?= e((string) $script) ?>"></script>
    <?php endforeach; ?>
</body>
</html>
