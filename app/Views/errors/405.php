<?php

declare(strict_types=1);

$allowed = $allowed ?? [];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>405 - Método não permitido</title>
    <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
</head>
<body class="error-page">
    <main class="error-card">
        <span class="error-code">405</span>
        <h1>Método não permitido</h1>
        <p>Esta rota não aceita o método HTTP utilizado.</p>
        <?php if ($allowed !== []): ?>
            <p class="text-muted">Métodos permitidos: <?= e(implode(', ', $allowed)) ?></p>
        <?php endif; ?>
        <a class="btn btn-primary" href="<?= e(url('/')) ?>">Voltar ao início</a>
    </main>
</body>
</html>
