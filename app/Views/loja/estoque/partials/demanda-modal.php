<?php

declare(strict_types=1);

$opcoes = $opcoes ?? [];
$renderOpcoes = static function (array $lista): void {
    foreach ($lista as $opcao) {
        echo '<option value="' . (int) $opcao['id'] . '">' . e((string) $opcao['nome']) . '</option>';
    }
};
?>
<div class="modal fade" id="demandaModal" tabindex="-1" aria-labelledby="demandaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form method="post" action="<?= e(url('/admin/loja/estoque/demanda')) ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="form_context" value="demanda_create">

                <div class="modal-header">
                    <div>
                        <h3 class="modal-title h5" id="demandaModalLabel">Item sob demanda (pré-venda)</h3>
                        <p class="modal-subtitle mb-0">
                            Cadastre o item e as origens/fornecedores com seus custos. O item entra como pré-venda.
                        </p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body" style="max-height: 75vh; overflow: auto;">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="loja-form-divider"><span>Catálogo do item</span></div>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label" for="dem_categoria">Categoria</label>
                            <select class="form-select" id="dem_categoria" name="categoria_id">
                                <option value="">Não informada</option>
                                <?php $renderOpcoes($opcoes['categorias'] ?? []); ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label" for="dem_marca">Marca</label>
                            <select class="form-select" id="dem_marca" name="marca_id">
                                <option value="">Não informada</option>
                                <?php $renderOpcoes($opcoes['marcas'] ?? []); ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label" for="dem_fabricante">Fabricante</label>
                            <select class="form-select" id="dem_fabricante" name="fabricante_id">
                                <option value="">Não informado</option>
                                <?php $renderOpcoes($opcoes['fabricantes'] ?? []); ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label" for="dem_colecao">Coleção</label>
                            <select class="form-select" id="dem_colecao" name="colecao_id">
                                <option value="">Não informada</option>
                                <?php $renderOpcoes($opcoes['colecoes'] ?? []); ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label" for="dem_escala">Escala</label>
                            <select class="form-select" id="dem_escala" name="escala_id">
                                <option value="">Não informada</option>
                                <?php $renderOpcoes($opcoes['escalas'] ?? []); ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label" for="dem_ano">Ano de fabricação</label>
                            <input type="number" class="form-control" id="dem_ano" name="ano_fabricacao" min="1900" max="2100">
                        </div>

                        <div class="col-12">
                            <div class="loja-form-divider"><span>Dados do item</span></div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="dem_nome">Nome do item</label>
                            <input type="text" class="form-control" id="dem_nome" name="nome" minlength="2" maxlength="180" required>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label" for="dem_codigo">Código/SKU</label>
                            <input type="text" class="form-control" id="dem_codigo" name="codigo" maxlength="80">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label" for="dem_quantidade">Unidades</label>
                            <input type="number" class="form-control" id="dem_quantidade" name="quantidade" min="1" max="999999" value="1" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="dem_descricao">Descrição (opcional)</label>
                            <input type="text" class="form-control" id="dem_descricao" name="descricao" maxlength="5000">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="dem_observacoes">Observações (opcional)</label>
                            <input type="text" class="form-control" id="dem_observacoes" name="observacoes" maxlength="2000">
                        </div>

                        <div class="col-12">
                            <div class="loja-form-divider"><span>Origens / fornecedores e custos</span></div>
                        </div>

                        <div class="col-12">
                            <div data-demanda-ofertas>
                                <div class="loja-inline-form demanda-oferta" data-demanda-oferta>
                                    <div class="loja-inline-field loja-inline-field-grow">
                                        <label class="form-label">Fornecedor</label>
                                        <input type="text" class="form-control" name="ofertas[0][fornecedor]" maxlength="150">
                                    </div>
                                    <div class="loja-inline-field loja-inline-field-grow">
                                        <label class="form-label">Link (opcional)</label>
                                        <input type="url" class="form-control" name="ofertas[0][url]" maxlength="500">
                                    </div>
                                    <div class="loja-inline-field">
                                        <label class="form-label">Custo</label>
                                        <input type="text" class="form-control" name="ofertas[0][preco_custo]" data-money placeholder="0,000">
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-demanda-add>
                                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                                Adicionar fornecedor
                            </button>
                        </div>

                        <div class="col-12">
                            <div class="loja-form-divider"><span>Fotos do item</span></div>
                        </div>
                        <div class="col-12">
                            <label for="dem_imagens" class="form-label">Fotos (opcional)</label>
                            <input
                                type="file"
                                class="form-control"
                                id="dem_imagens"
                                name="imagens[]"
                                accept="image/jpeg,image/png,image/webp"
                                multiple
                                data-image-preview="demandaImagePreview"
                            >
                            <div class="image-preview-grid" id="demandaImagePreview" aria-live="polite"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-brand">
                        <i class="bi bi-check-lg" aria-hidden="true"></i>
                        Cadastrar item sob demanda
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
