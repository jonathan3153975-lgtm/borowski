<?php

declare(strict_types=1);

$config = $config ?? [];
$valor = static fn (string $chave, string $padrao = ''): string => (string) ($config[$chave] ?? $padrao);
?>
<section class="panel-card">
    <div class="panel-card-head">
        <div>
            <h2 class="panel-card-title">Recebimento por Mercado Pago</h2>
            <p class="panel-card-subtitle mb-0">
                Configure a conta que receberá os pagamentos das vendas pelo Telegram.
            </p>
        </div>
        <button
            type="button"
            class="btn btn-outline-secondary"
            data-bs-toggle="modal"
            data-bs-target="#mpHelpModal"
        >
            <i class="bi bi-question-circle" aria-hidden="true"></i>
            Manual de configuração
        </button>
    </div>

    <form method="post" action="<?= e(url('/admin/configuracoes/mercadopago')) ?>" class="config-form">
        <?= csrf_field() ?>

        <div class="row g-3">
            <div class="col-12">
                <div class="form-check form-switch">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        id="mp_ativo"
                        name="mp_ativo"
                        value="1"
                        <?= $valor('mp_ativo') === '1' ? 'checked' : '' ?>
                    >
                    <label class="form-check-label" for="mp_ativo">
                        Ativar pagamento pelo Mercado Pago
                    </label>
                </div>
            </div>

            <div class="col-12">
                <label for="mp_access_token" class="form-label">Access Token</label>
                <input
                    type="text"
                    class="form-control"
                    id="mp_access_token"
                    name="mp_access_token"
                    value="<?= e($valor('mp_access_token')) ?>"
                    autocomplete="off"
                    placeholder="APP_USR-..."
                >
                <div class="form-text">
                    Credencial secreta da sua aplicação no Mercado Pago. Não compartilhe.
                </div>
            </div>

            <div class="col-12">
                <label for="mp_public_key" class="form-label">Public Key (opcional)</label>
                <input
                    type="text"
                    class="form-control"
                    id="mp_public_key"
                    name="mp_public_key"
                    value="<?= e($valor('mp_public_key')) ?>"
                    autocomplete="off"
                    placeholder="APP_USR-..."
                >
                <div class="form-text">
                    Não é usada neste fluxo (o pagamento é feito por link de checkout).
                </div>
            </div>

            <div class="col-12">
                <div class="alert alert-info d-flex gap-2 align-items-start">
                    <i class="bi bi-info-circle" aria-hidden="true"></i>
                    <div>
                        Ao clicar em <strong>Comprar</strong> no Telegram, o sistema gera um link de checkout
                        no Mercado Pago com o valor da reserva (ou valor total) e envia ao cliente.
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
                formaction="<?= e(url('/admin/configuracoes/mercadopago/testar')) ?>"
            >
                <i class="bi bi-plug" aria-hidden="true"></i>
                Testar conexão
            </button>
            <button type="submit" class="btn btn-brand">Salvar configurações</button>
        </div>
    </form>
</section>

<div class="modal fade" id="mpHelpModal" tabindex="-1" aria-labelledby="mpHelpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title h5" id="mpHelpModalLabel">
                        <i class="bi bi-credit-card" aria-hidden="true"></i>
                        Manual — Recebimento por Mercado Pago
                    </h3>
                    <p class="modal-subtitle mb-0">Passo a passo e explicação de cada campo.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <h4 class="h6 mt-2">1. Criar a aplicação</h4>
                <ol>
                    <li>Acesse o <strong>Mercado Pago Developers</strong> (mercadopago.com.br/developers) com a conta que receberá os pagamentos.</li>
                    <li>Crie uma aplicação do tipo <strong>Checkout Pro</strong>.</li>
                    <li>Abra <strong>Credenciais</strong> e copie o <strong>Access Token</strong> (produção) ou o de teste.</li>
                </ol>

                <h4 class="h6 mt-4">2. Configurar no sistema</h4>
                <ol>
                    <li>Cole o token em <strong>Access Token</strong>.</li>
                    <li>Marque <strong>Ativar pagamento pelo Mercado Pago</strong>.</li>
                    <li>Clique em <strong>Testar conexão</strong> (valida a conta) e depois em <strong>Salvar</strong>.</li>
                </ol>

                <h4 class="h6 mt-4">3. Como funciona</h4>
                <ol>
                    <li>O cliente clica em <strong>Comprar</strong> na publicação do Telegram.</li>
                    <li>O sistema cria a reserva e gera um <strong>link de checkout</strong> no Mercado Pago.</li>
                    <li>O cliente recebe no privado o botão <strong>Pagar no Mercado Pago</strong>.</li>
                    <li>O pagamento é feito diretamente no Mercado Pago; o pedido fica registrado em Pedidos.</li>
                </ol>

                <h4 class="h6 mt-4">4. Campos do formulário</h4>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr><th>Campo</th><th>O que é / como preencher</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Ativar pagamento</strong></td>
                                <td>Liga/desliga o envio de links de pagamento do Mercado Pago.</td>
                            </tr>
                            <tr>
                                <td><strong>Access Token</strong></td>
                                <td>Credencial secreta da aplicação (produção ou teste). Usada para gerar o checkout.</td>
                            </tr>
                            <tr>
                                <td><strong>Public Key</strong></td>
                                <td>Credencial pública. Não é usada neste fluxo; pode ficar em branco.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-warning mb-0">
                    <i class="bi bi-shield-lock" aria-hidden="true"></i>
                    O Access Token é como uma senha. Não compartilhe. Se vazar, gere um novo no painel do Mercado Pago.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-brand" data-bs-dismiss="modal">Entendi</button>
            </div>
        </div>
    </div>
</div>
