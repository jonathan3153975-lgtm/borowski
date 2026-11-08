<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Este script deve ser executado via linha de comando.\n");
}

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

\Dotenv\Dotenv::createImmutable(BASE_PATH)->safeLoad();

$config = new \App\Services\ConfiguracaoAppService();
$telegram = new \App\Services\Loja\TelegramService();

if ($telegram->configurado() === false) {
    fwrite(STDERR, "Telegram não configurado. Informe o token do bot e o grupo em Configurações > Telegram.\n");
    exit(1);
}

$lastId = (int) $config->get('telegram_last_update_id', '0');
$updates = $telegram->getUpdates($lastId + 1);

foreach ($updates as $update) {
    $telegram->handleUpdate($update);
    $lastId = max($lastId, (int) ($update['update_id'] ?? 0));
}

if ($updates !== []) {
    $config->set('telegram_last_update_id', (string) $lastId);
    echo 'Processados: ' . count($updates) . " atualização(ões).\n";
} else {
    echo "Nenhuma atualização pendente.\n";
}
