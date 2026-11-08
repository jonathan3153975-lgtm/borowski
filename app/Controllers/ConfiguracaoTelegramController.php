<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\ConfiguracaoAppService;
use App\Services\Loja\TelegramService;

class ConfiguracaoTelegramController extends Controller
{
    public function index(): void
    {
        $config = new ConfiguracaoAppService();
        $chats = $_SESSION['telegram_chats'] ?? [];
        unset($_SESSION['telegram_chats']);

        $this->view('configuracoes.telegram', [
            'title' => 'Telegram',
            'breadcrumb' => [
                ['label' => 'Configurações', 'url' => null],
                ['label' => 'Telegram', 'url' => null],
            ],
            'config' => $config->all(),
            'chats' => is_array($chats) ? $chats : [],
            'extraStyles' => [asset('css/configuracoes.css')],
        ], 'admin');
    }

    public function save(): void
    {
        $this->persistir($_POST);

        flash('success', 'Configurações do Telegram salvas com sucesso.');
        redirect('/admin/configuracoes/telegram');
    }

    public function testar(): void
    {
        // O botão de teste envia os campos do formulário: persiste antes de testar,
        // para validar o token que está na tela e não apenas o salvo.
        if (array_key_exists('telegram_bot_token', $_POST)) {
            $this->persistir($_POST);
        }

        $telegram = new TelegramService();
        $result = $telegram->testarConexao();

        if ($result === null) {
            flash('error', 'Não foi possível conectar: ' . ($telegram->ultimoErro() ?? 'erro desconhecido'));
        } else {
            flash('success', 'Conectado ao bot @' . (string) ($result['username'] ?? '') . '.');
        }

        redirect('/admin/configuracoes/telegram');
    }

    public function desconectarWebhook(): void
    {
        $telegram = new TelegramService();

        if ($telegram->deleteWebhook()) {
            flash('success', 'Webhook removido. Agora o modo local (poller) pode ser usado.');
        } else {
            flash('error', 'Não foi possível remover o webhook: ' . ($telegram->ultimoErro() ?? 'erro desconhecido'));
        }

        redirect('/admin/configuracoes/telegram');
    }

    public function descobrirChats(): void
    {
        $telegram = new TelegramService();

        // getUpdates não funciona com webhook ativo; remove antes de buscar.
        $telegram->deleteWebhook();
        $chats = $telegram->descobrirChats();

        $_SESSION['telegram_chats'] = $chats;

        if ($chats === []) {
            flash('warning', 'Nenhum chat encontrado. Envie uma mensagem no grupo e clique em "Descobrir ID do grupo" novamente.');
        } else {
            flash('success', 'Chats encontrados. Use o ID do grupo na lista abaixo.');
        }

        redirect('/admin/configuracoes/telegram');
    }

    private function persistir(array $dados): void
    {
        (new ConfiguracaoAppService())->setMany([
            'telegram_ativo' => (isset($dados['telegram_ativo']) && $dados['telegram_ativo'] === '1') ? '1' : '0',
            'telegram_bot_token' => trim((string) ($dados['telegram_bot_token'] ?? '')),
            'telegram_group_chat_id' => trim((string) ($dados['telegram_group_chat_id'] ?? '')),
            'telegram_webhook_secret' => trim((string) ($dados['telegram_webhook_secret'] ?? '')),
            'telegram_reserva_minutos' => (string) max(1, (int) ($dados['telegram_reserva_minutos'] ?? 30)),
            'loja_pix_chave' => trim((string) ($dados['loja_pix_chave'] ?? '')),
            'loja_pix_titular' => trim((string) ($dados['loja_pix_titular'] ?? '')),
            'loja_mercadopago_link' => trim((string) ($dados['loja_mercadopago_link'] ?? '')),
        ]);
    }
}
