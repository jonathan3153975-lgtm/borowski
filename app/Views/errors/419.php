<?php

declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>419 - Sessão expirada</title>
    <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
</head>
<body class="error-page">
    <main class="error-card">
        <span class="error-code">419</span>
        <h1>Sessão expirada</h1>
        <p>O formulário perdeu a validade. Recarregue a página e tente novamente.</p>
        <a class="btn btn-primary" href="<?= e(url('/login')) ?>">Ir para o login</a>
    </main>
</body>
</html>
