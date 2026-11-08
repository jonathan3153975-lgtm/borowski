<?php

declare(strict_types=1);

$config = $config ?? [];
$valor = static fn (string $chave, string $padrao = ''): string => (string) ($config[$chave] ?? $padrao);
$secreto = $valor('telegram_webhook_secret');
$webhookUrl = url('/telegram/webhook/' . ($secreto !== '' ? $secreto : 'SEU_SEGREDO'));
?>
<section class="panel-card">
    <div class="panel-card-head">
        <div>
            <h2 class="panel-card-title">Venda pelo Telegram</h2>
            <p class="panel-card-subtitle mb-0">
                Configure o bot, o grupo de publicação e as formas de pagamento.
            </p>
        </div>
        <button
            type="button"
            class="btn btn-outline-secondary"
            data-bs-toggle="modal"
            data-bs-target="#telegramHelpModal"
        >
            <i class="bi bi-question-circle" aria-hidden="true"></i>
            Manual de configuração
        </button>
    </div>

        <form method="post" action="<?= e(url('/admin/configuracoes/telegram')) ?>" class="config-form">
            <?= csrf_field() ?>

            <div class="row g-3">
                <div class="col-12">
                    <div class="loja-form-divider"><span>Bot e grupo</span></div>
                </div>

                <div class="col-12 col-md-8">
                    <label for="telegram_bot_token" class="form-label">Token do bot</label>
                    <input
                        type="text"
                        class="form-control"
                        id="telegram_bot_token"
                        name="telegram_bot_token"
                        value="<?= e($valor('telegram_bot_token')) ?>"
                        autocomplete="off"
                        placeholder="123456:ABC-DEF..."
                    >
                    <div class="form-text">
                        Obtenha no <strong>@BotFather</strong>. O bot precisa ser administrador do grupo.
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <label for="telegram_reserva_minutos" class="form-label">Reserva (minutos)</label>
                    <input
                        type="number"
                        class="form-control"
                        id="telegram_reserva_minutos"
                        name="telegram_reserva_minutos"
                        min="1"
                        max="1440"
                        value="<?= e($valor('telegram_reserva_minutos', '30')) ?>"
                    >
                </div>

                <div class="col-12 col-md-6">
                    <label for="telegram_group_chat_id" class="form-label">ID do grupo (chat_id)</label>
                    <input
                        type="text"
                        class="form-control"
                        id="telegram_group_chat_id"
                        name="telegram_group_chat_id"
                        value="<?= e($valor('telegram_group_chat_id')) ?>"
                        placeholder="-1001234567890"
                    >
                    <div class="form-text">Ex.: -100... (grupos/supergrupos).</div>
                </div>

                <div class="col-12 col-md-6">
                    <label for="telegram_webhook_secret" class="form-label">Segredo do webhook</label>
                    <input
                        type="text"
                        class="form-control"
                        id="telegram_webhook_secret"
                        name="telegram_webhook_secret"
                        value="<?= e($secreto) ?>"
                        placeholder="uma-string-secreta"
                    >
                    <div class="form-text">
                        URL do webhook: <code><?= e($webhookUrl) ?></code>
                    </div>
                </div>

                <div class="col-12">
                    <div class="form-check form-switch">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            id="telegram_ativo"
                            name="telegram_ativo"
                            value="1"
                            <?= $valor('telegram_ativo') === '1' ? 'checked' : '' ?>
                        >
                        <label class="form-check-label" for="telegram_ativo">
                            Ativar venda pelo Telegram
                        </label>
                    </div>
                </div>

                <div class="col-12">
                    <div class="alert alert-info d-flex gap-2 align-items-start">
                        <i class="bi bi-info-circle" aria-hidden="true"></i>
                        <div>
                            Para receber os cliques em "Comprar", aponte o webhook do bot para
                            <code><?= e($webhookUrl) ?></code> ou execute periodicamente
                            <code>php scripts/telegram-poll.php</code> em ambiente local.
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <a href="<?= e(url('/admin/configuracoes/categorias')) ?>" class="btn btn-outline-secondary">
                    Voltar
                </a>
                <button
                    type="submit"
                    class="btn btn-outline-secondary"
                    formaction="<?= e(url('/admin/configuracoes/telegram/testar')) ?>"
                >
                    <i class="bi bi-plug" aria-hidden="true"></i>
                    Testar conexão
                </button>
                <button type="submit" class="btn btn-brand">Salvar configurações</button>
            </div>
        </form>
</section>

<section class="panel-card mt-3">
    <div class="panel-card-head">
        <div>
            <h3 class="panel-card-title">Conexão e webhook</h3>
            <p class="panel-card-subtitle mb-0">
                O Telegram usa <strong>webhook</strong> (produção) ou <strong>getUpdates/poller</strong> (local) — nunca os dois.
                Para testar no localhost, remova o webhook e use o poller.
            </p>
        </div>
    </div>
    <div class="config-form">
        <div class="d-flex gap-2 flex-wrap mb-3">
            <form method="post" action="<?= e(url('/admin/configuracoes/telegram/desconectar')) ?>">
                <?= csrf_field() ?>
                <button
                    type="submit"
                    class="btn btn-outline-danger"
                    data-confirm="Remover o webhook atual? Use isto para testar no localhost com o poller."
                >
                    <i class="bi bi-x-circle" aria-hidden="true"></i>
                    Desconectar webhook (usar no localhost)
                </button>
            </form>
            <form method="post" action="<?= e(url('/admin/configuracoes/telegram/descobrir')) ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline-secondary">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    Descobrir ID do grupo
                </button>
            </form>
        </div>

        <div class="alert alert-info">
            <strong>Como descobrir o ID do grupo:</strong>
            <ol class="mb-0">
                <li>Adicione o bot ao grupo e envie uma mensagem qualquer no grupo.</li>
                <li>Clique em <strong>Descobrir ID do grupo</strong> (isto remove o webhook).</li>
                <li>Use o ID listado abaixo no campo <strong>ID do grupo</strong>.</li>
                <li>Para receber os cliques, rode <code>php scripts/telegram-poll.php</code> periodicamente (local) ou reconfigure o webhook (produção).</li>
            </ol>
        </div>

        <?php if (($chats ?? []) !== []): ?>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>ID (chat_id)</th>
                            <th>Tipo</th>
                            <th>Título</th>
                            <th class="text-end">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($chats as $chat): ?>
                            <tr>
                                <td><code><?= e((string) $chat['id']) ?></code></td>
                                <td><?= e((string) $chat['tipo']) ?></td>
                                <td><?= e((string) $chat['titulo']) ?></td>
                                <td class="text-end">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-secondary"
                                        data-use-chat="<?= e((string) $chat['id']) ?>"
                                    >
                                        Usar este ID
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>

<div class="modal fade" id="telegramHelpModal" tabindex="-1" aria-labelledby="telegramHelpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title h5" id="telegramHelpModalLabel">
                        <i class="bi bi-telegram" aria-hidden="true"></i>
                        Manual — Venda pelo Telegram
                    </h3>
                    <p class="modal-subtitle mb-0">
                        Passo a passo e explicação de cada campo.
                    </p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p>
                    A venda funciona assim: o item é publicado no grupo com o botão <strong>Comprar</strong>;
                    ao clicar, o cliente recebe uma mensagem privada com o item e as opções de pagamento.
                    Siga os passos abaixo.
                </p>

                <h4 class="h6 mt-4">1. Criar o bot e obter o token</h4>
                <ol>
                    <li>No Telegram, procure <strong>@BotFather</strong> e envie <code>/newbot</code>.</li>
                    <li>Escolha um nome e um usuário (terminando em "bot").</li>
                    <li>O BotFather devolve o <strong>token</strong>, algo como <code>123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ</code>.</li>
                    <li>Cole em <strong>Token do bot</strong> e clique em <strong>Testar conexão</strong>.</li>
                </ol>
                <div class="alert alert-warning">
                    <i class="bi bi-shield-lock" aria-hidden="true"></i>
                    O token é como uma senha do bot. Não compartilhe. Se vazar, revogue no @BotFather (<code>/revoke</code>).
                </div>

                <h4 class="h6 mt-4">2. Adicionar o bot ao grupo</h4>
                <ol>
                    <li>Adicione o bot ao grupo onde os anúncios serão publicados.</li>
                    <li>Promova o bot a <strong>administrador</strong> (ou permita ao menos enviar mensagens).</li>
                </ol>

                <h4 class="h6 mt-4">3. Descobrir o ID do grupo (chat_id)</h4>
                <ol>
                    <li>Envie qualquer mensagem no grupo.</li>
                    <li>Abra no navegador: <code>https://api.telegram.org/bot&lt;SEU_TOKEN&gt;/getUpdates</code></li>
                    <li>Procure <code>"chat":{"id":-1001234567890}</code>. O número (negativo) é o <strong>ID do grupo</strong>.</li>
                </ol>
                <div class="alert alert-info">
                    Grupos e supergrupos usam IDs negativos, geralmente começando com <code>-100</code>.
                </div>

                <h4 class="h6 mt-4">4. O que é o segredo do webhook</h4>
                <p>
                    O <strong>segredo do webhook</strong> é uma senha que <em>você inventa</em> para proteger o
                    endereço que o Telegram chama quando alguém clica em <strong>Comprar</strong>. Ele faz parte da URL:
                </p>
                <p><code><?= e($webhookUrl) ?></code></p>
                <p>
                    Assim, só quem conhece o segredo consegue acionar o fluxo de compra. Não é a senha do bot e não
                    vem do Telegram: use uma sequência longa e aleatória (letras e números), por exemplo
                    <code>loja-tg-9f3k2x7b</code>. Se trocar o segredo, atualize a URL no <code>setWebhook</code>.
                </p>

                <h4 class="h6 mt-4">5. Conectar o Telegram ao sistema</h4>
                <p><strong>Em produção (com domínio público/HTTPS):</strong> registre a URL do webhook uma vez:</p>
                <p><code>https://api.telegram.org/bot&lt;SEU_TOKEN&gt;/setWebhook?url=<?= e($webhookUrl) ?></code></p>
                <p><strong>Ambiente local (sem domínio público):</strong> não use webhook. Execute periodicamente:</p>
                <p><code>php scripts/telegram-poll.php</code></p>
                <p>O script busca as atualizações e processa os cliques em Comprar.</p>

                <h4 class="h6 mt-4">6. Campos do formulário</h4>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Campo</th>
                                <th>O que é / como preencher</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Token do bot</strong></td>
                                <td>Credencial gerada pelo @BotFather. Usada para enviar mensagens.</td>
                            </tr>
                            <tr>
                                <td><strong>Reserva (minutos)</strong></td>
                                <td>Tempo que a unidade fica reservada após o clique em Comprar, antes de voltar ao estoque.</td>
                            </tr>
                            <tr>
                                <td><strong>ID do grupo (chat_id)</strong></td>
                                <td>Identificador do grupo onde os anúncios serão publicados (negativo, ex.: -1001234567890).</td>
                            </tr>
                            <tr>
                                <td><strong>Segredo do webhook</strong></td>
                                <td>Senha que você inventa e inclui na URL do webhook. Protege o endpoint de compras.</td>
                            </tr>
                            <tr>
                                <td><strong>Ativar venda pelo Telegram</strong></td>
                                <td>Liga/desliga a publicação e o fluxo de compra pelo Telegram.</td>
                            </tr>
                            
                            
                            
                        </tbody>
                    </table>
                </div>

                <h4 class="h6 mt-4">7. Como funciona a compra</h4>
                <ol>
                    <li>O item é publicado no grupo com o botão <strong>Comprar</strong>.</li>
                    <li>Ao clicar, o sistema reserva 1 unidade (se houver disponibilidade) e envia uma mensagem privada com o item e o botão <strong>Pagar no Mercado Pago</strong>.</li>
                    <li>O cliente escolhe a forma de pagamento e recebe os dados para pagar.</li>
                    <li>Se não pagar dentro do tempo de reserva, a unidade volta a ficar disponível.</li>
                </ol>
                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle" aria-hidden="true"></i>
                    Para receber a mensagem privada, o cliente precisa ter aberto conversa com o bot (enviado
                    <code>/start</code>) ao menos uma vez. Se o envio privado falhar, a reserva é cancelada e o
                    cliente é avisado para iniciar o bot e clicar em Comprar novamente.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-brand" data-bs-dismiss="modal">Entendi</button>
            </div>
        </div>
    </div>
</div>
