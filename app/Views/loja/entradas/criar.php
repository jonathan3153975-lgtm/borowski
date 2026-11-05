<?php

declare(strict_types=1);

$modo = (string) old('modo', 'direta');
$isDireta = $modo !== 'previsao';
$recebidoMarcado = old('recebido') === '1' || old('recebido') === 'on';
?>
<?php require __DIR__ . '/../partials/tabs.php'; ?>

<section class="panel-card form-card loja-form-card">
    <div class="panel-card-head">
        <div>
            <h2 class="panel-card-title">Nova entrada</h2>
            <p class="panel-card-subtitle mb-0">
                Escolha o modo de entrada. Os itens e custos serão adicionados na próxima tela.
            </p>
        </div>
    </div>

    <form method="post" action="<?= e(url('/admin/loja/entradas')) ?>" class="loja-form">
        <?= csrf_field() ?>

        <fieldset class="loja-mode-selector">
            <legend class="form-label">Modo de entrada</legend>
            <div class="loja-mode-options">
                <label class="loja-mode-option">
                    <input
                        type="radio"
                        name="modo"
                        value="direta"
                        data-entry-mode
                        <?= $isDireta ? 'checked' : '' ?>
                    >
                    <span class="loja-mode-option-body">
                        <span class="loja-mode-option-title">
                            <i class="bi bi-bag-check" aria-hidden="true"></i>
                            Compra direta
                        </span>
                        <span class="loja-mode-option-text">
                            Compra já realizada, com link, recebimento, rastreio e custos adicionais.
                        </span>
                    </span>
                </label>

                <label class="loja-mode-option">
                    <input
                        type="radio"
                        name="modo"
                        value="previsao"
                        data-entry-mode
                        <?= !$isDireta ? 'checked' : '' ?>
                    >
                    <span class="loja-mode-option-body">
                        <span class="loja-mode-option-title">
                            <i class="bi bi-calendar2-check" aria-hidden="true"></i>
                            Previsão de compra
                        </span>
                        <span class="loja-mode-option-text">
                            Itens para pré-venda, cadastrados depois com múltiplos fornecedores.
                        </span>
                    </span>
                </label>
            </div>
        </fieldset>

        <div class="row g-3 mt-1">
            <div class="col-12">
                <label for="descricao_origem" class="form-label">Descrição de origem / previsão</label>
                <input
                    type="text"
                    class="form-control"
                    id="descricao_origem"
                    name="descricao_origem"
                    value="<?= e(old('descricao_origem', '')) ?>"
                    minlength="3"
                    maxlength="180"
                    placeholder="Ex.: fornecedor, marketplace, campanha ou lote"
                    required
                >
            </div>

            <div class="col-12 col-md-8 js-direct-field" data-direct-field>
                <label for="link_compra" class="form-label">Link de compra</label>
                <input
                    type="url"
                    class="form-control"
                    id="link_compra"
                    name="link_compra"
                    value="<?= e(old('link_compra', '')) ?>"
                    maxlength="500"
                    placeholder="https://..."
                >
            </div>

            <div class="col-12 col-md-4 js-direct-field" data-direct-field>
                <label for="codigo_rastreio" class="form-label">Código de rastreio (opcional)</label>
                <input
                    type="text"
                    class="form-control"
                    id="codigo_rastreio"
                    name="codigo_rastreio"
                    value="<?= e(old('codigo_rastreio', '')) ?>"
                    maxlength="120"
                    placeholder="Ex.: AB123456789BR"
                >
            </div>

            <div class="col-12 js-direct-field" data-direct-field>
                <div class="form-check loja-check">
                    <input
                        type="checkbox"
                        class="form-check-input"
                        id="recebido"
                        name="recebido"
                        value="1"
                        <?= $recebidoMarcado ? 'checked' : '' ?>
                    >
                    <label class="form-check-label" for="recebido">
                        A mercadoria já foi recebida
                    </label>
                    <div class="form-text">Se não for marcado, os itens entrarão como “Em transporte”.</div>
                </div>
            </div>

            <div class="col-12">
                <label for="observacoes" class="form-label">Observações (opcional)</label>
                <textarea
                    class="form-control"
                    id="observacoes"
                    name="observacoes"
                    rows="3"
                    maxlength="2000"
                    placeholder="Informações adicionais sobre esta entrada"
                ><?= e(old('observacoes', '')) ?></textarea>
            </div>
        </div>

        <div class="form-actions">
            <a href="<?= e(url('/admin/loja/entradas')) ?>" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-brand">
                <i class="bi bi-check-lg" aria-hidden="true"></i>
                Salvar entrada
            </button>
        </div>
    </form>
</section>
