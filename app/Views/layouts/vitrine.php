<?php

declare(strict_types=1);

$title = $title ?? 'Loja';
$brand = (string) config('app.name');
$extraStyles = $extraStyles ?? [];
$extraScripts = $extraScripts ?? [];
$carrinhoCount = $carrinhoCount ?? array_sum(array_map('intval', (array) ($_SESSION['carrinho'] ?? [])));
$busca = $busca ?? trim((string) ($_GET['busca'] ?? ''));
$ogTitle = $ogTitle ?? $title;
$ogDescription = $ogDescription ?? '';
$ogImage = $ogImage ?? null;
$ogUrl = $ogUrl ?? url('/loja');
$ogPrice = $ogPrice ?? null;
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
    <meta property="og:type" content="product">
    <meta property="og:site_name" content="<?= e($brand) ?>">
    <meta property="og:title" content="<?= e((string) $ogTitle) ?>">
    <meta property="og:description" content="<?= e((string) $ogDescription) ?>">
    <meta property="og:url" content="<?= e((string) $ogUrl) ?>">
    <?php if (!empty($ogImage)): ?>
        <meta property="og:image" content="<?= e((string) $ogImage) ?>">
        <meta property="og:image:alt" content="<?= e((string) $ogTitle) ?>">
    <?php endif; ?>
    <?php if ($ogPrice !== null): ?>
        <meta property="product:price:amount" content="<?= e(number_format((float) $ogPrice, 2, '.', '')) ?>">
        <meta property="product:price:currency" content="BRL">
    <?php endif; ?>
    <meta name="twitter:card" content="<?= !empty($ogImage) ? 'summary_large_image' : 'summary' ?>">
    <meta name="twitter:title" content="<?= e((string) $ogTitle) ?>">
    <meta name="twitter:description" content="<?= e((string) $ogDescription) ?>">
    <?php if (!empty($ogImage)): ?>
        <meta name="twitter:image" content="<?= e((string) $ogImage) ?>">
    <?php endif; ?>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >
    <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/vitrine.css')) ?>">
    <?php foreach ($extraStyles as $style): ?>
        <link rel="stylesheet" href="<?= e((string) $style) ?>">
    <?php endforeach; ?>
</head>
<body class="vitrine-body">
    <header class="vitrine-header">
        <div class="vitrine-container vitrine-header-inner">
            <a class="vitrine-brand" href="<?= e(url('/loja')) ?>">
                <span class="vitrine-brand-mark" aria-hidden="true">LC</span>
                <span class="vitrine-brand-text"><?= e($brand) ?></span>
            </a>

            <form class="vitrine-search vitrine-search-header" method="get" action="<?= e(url('/loja')) ?>" role="search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input
                    type="search"
                    name="busca"
                    value="<?= e($busca) ?>"
                    placeholder="Buscar por nome ou SKU"
                    aria-label="Buscar itens"
                >
                <button type="submit" class="btn btn-brand">Buscar</button>
            </form>

            <nav class="vitrine-nav" aria-label="Navegação da loja">
                <a href="<?= e(url('/loja/carrinho')) ?>" class="vitrine-nav-link vitrine-cart-link">
                    <i class="bi bi-cart3" aria-hidden="true"></i>
                    <span>Carrinho</span>
                    <span class="vitrine-cart-count"><?= (int) $carrinhoCount ?></span>
                </a>
            </nav>
        </div>
    </header>

    <main class="vitrine-main">
        <div class="vitrine-container">
            <?php if (is_string($success) && $success !== ''): ?>
                <div class="alert alert-success" role="alert"><?= e($success) ?></div>
            <?php endif; ?>
            <?php if (is_string($error) && $error !== ''): ?>
                <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
            <?php endif; ?>
            <?php if (is_string($warning) && $warning !== ''): ?>
                <div class="alert alert-warning" role="alert"><?= e($warning) ?></div>
            <?php endif; ?>
            <?php if (is_string($info) && $info !== ''): ?>
                <div class="alert alert-info" role="alert"><?= e($info) ?></div>
            <?php endif; ?>

            <?= $content ?>
        </div>
    </main>

    <footer class="vitrine-footer">
        <div class="vitrine-container vitrine-footer-inner">
            <span>&copy; <?= e(date('Y')) ?> <?= e($brand) ?></span>
            <span>Compra segura com Mercado Pago</span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= e(asset('js/vitrine.js')) ?>"></script>
    <?php foreach ($extraScripts as $script): ?>
        <script src="<?= e((string) $script) ?>"></script>
    <?php endforeach; ?>
</body>
</html>
