<?php

declare(strict_types=1);

$recebidoMarcado = old('recebido') === '1' || old('recebido') === 'on';
?>
<form method="post" action="<?= e(url('/admin/loja/entradas')) ?>" class="loja-form">
    <?= csrf_field() ?>
    <input type="hidden" name="form_context" value="entry_create">
    <input type="hidden" name="modo" value="direta">

    <div class="row g-3">
        <div class="col-12">
            <label for="descricao_origem" class="form-label">Descrição de origem</label>
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

        <div class="col-12 col-md-8">
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

        <div class="col-12 col-md-4">
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

        <div class="col-12">
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
                <div class="form-text">Se não for marcado, os itens entrarão como "Em transporte".</div>
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
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-brand">
            <i class="bi bi-check-lg" aria-hidden="true"></i>
            Salvar entrada
        </button>
    </div>
</form>
