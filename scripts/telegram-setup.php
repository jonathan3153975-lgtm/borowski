<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Este script deve ser executado via linha de comando.\n");
}

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

\Dotenv\Dotenv::createImmutable(BASE_PATH)->safeLoad();

$acao = $argv[1] ?? '--info';
$telegram = new \App\Services\Loja\TelegramService();
$config = new \App\Services\ConfiguracaoAppService();

switch ($acao) {
    case '--info':
        $me = $telegram->testarConexao();
        echo $me === null
            ? 'Bot: FALHA - ' . $telegram->ultimoErro() . "\n"
            : 'Bot: @' . ($me['username'] ?? '') . ' (' . ($me['first_name'] ?? '') . ")\n";

        $webhook = $telegram->webhookInfo();

        if ($webhook !== null) {
            $url = (string) ($webhook['url'] ?? '');
            $segredo = $config->get('telegram_webhook_secret');

            if ($url !== '' && $segredo !== '') {
                $url = str_replace($segredo, '***', $url);
            }

            echo 'Webhook: ' . ($url !== '' ? $url : '(nenhum)') . "\n";
        }

        echo 'Grupo configurado: ' . ($config->get('telegram_group_chat_id') ?: '(vazio)') . "\n";
        break;

    case '--delete-webhook':
        echo $telegram->deleteWebhook()
            ? "Webhook removido.\n"
            : 'Falha: ' . $telegram->ultimoErro() . "\n";
        break;

    case '--updates':
        // getUpdates não funciona com webhook ativo.
        $telegram->deleteWebhook();
        $chats = $telegram->descobrirChats();

        if ($chats === []) {
            echo "Nenhum chat encontrado. Envie uma mensagem no grupo e execute novamente.\n";
        } else {
            echo "Chats encontrados:\n";
            foreach ($chats as $chat) {
                echo $chat['id'] . ' | ' . $chat['tipo'] . ' | ' . $chat['titulo'] . "\n";
            }
        }
        break;

    case '--set-webhook':
        $segredo = $config->get('telegram_webhook_secret');

        if ($segredo === '') {
            fwrite(STDERR, "Defina o segredo do webhook em Configurações > Telegram.\n");
            exit(1);
        }

        $url = rtrim((string) config('app.url'), '/') . '/telegram/webhook/' . $segredo;
        echo $telegram->setWebhook($url)
            ? "Webhook definido: {$url}\n"
            : 'Falha: ' . $telegram->ultimoErro() . "\n";
        break;

    default:
        echo "Uso: php scripts/telegram-setup.php [--info|--delete-webhook|--updates|--set-webhook]\n";
}
