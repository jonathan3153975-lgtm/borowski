<?php

declare(strict_types=1);

$title = $title ?? 'Entrar';
$brand = (string) config('app.name');
$success = flash('success');
$error = flash('error');
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
    <link rel="stylesheet" href="<?= e(asset('css/auth.css')) ?>">
</head>
<body class="auth-body">
    <div class="auth-wrapper">
        <section class="auth-brand" aria-label="Identificação do sistema">
            <div class="auth-brand-content">
                <div class="auth-brand-mark" aria-hidden="true">LC</div>
                <h1 class="auth-brand-title"><?= e($brand) ?></h1>
                <p class="auth-brand-text">
                    Sistema próprio de gerenciamento da loja virtual de carrinhos colecionáveis.
                </p>
                <ul class="auth-brand-list">
                    <li><i class="bi bi-shield-check" aria-hidden="true"></i> Acesso restrito à equipe</li>
                    <li><i class="bi bi-grid-1x2" aria-hidden="true"></i> Painel administrativo integrado</li>
                    <li><i class="bi bi-database-check" aria-hidden="true"></i> Dados protegidos</li>
                </ul>
            </div>
        </section>

        <section class="auth-panel">
            <div class="auth-card">
                <div class="auth-card-header">
                    <span class="auth-card-eyebrow">Área administrativa</span>
                    <h2 class="auth-card-title">Acessar o painel</h2>
                    <p class="auth-card-subtitle">Informe suas credenciais para continuar.</p>
                </div>

                <?php if (is_string($success) && $success !== ''): ?>
                    <div class="alert alert-success" role="alert"><?= e($success) ?></div>
                <?php endif; ?>

                <?php if (is_string($error) && $error !== ''): ?>
                    <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
                <?php endif; ?>

                <?= $content ?>
            </div>

            <p class="auth-footer">
                &copy; <?= e(date('Y')) ?> <?= e($brand) ?>. Todos os direitos reservados.
            </p>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
