<?php

declare(strict_types=1);

$debug = $debug ?? false;
$message = $message ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Erro interno</title>
    <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
</head>
<body class="error-page">
    <main class="error-card">
        <span class="error-code">500</span>
        <h1>Erro interno</h1>
        <p>Não foi possível concluir a solicitação. Tente novamente mais tarde.</p>
        <?php if ($debug): ?>
            <pre class="error-debug"><?= e($message) ?></pre>
        <?php endif; ?>
        <a class="btn btn-primary" href="<?= e(url('/')) ?>">Voltar ao início</a>
    </main>
</body>
</html>
