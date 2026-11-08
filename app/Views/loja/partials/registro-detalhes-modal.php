<?php

declare(strict_types=1);
?>
<div class="modal fade" id="registroDetalhesModal" tabindex="-1" aria-labelledby="registroDetalhesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title h5" id="registroDetalhesModalLabel" data-registro-titulo>Detalhes</h3>
                    <p class="modal-subtitle mb-0" data-registro-subtitulo></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted" data-registro-info></p>
                <div class="table-responsive">
                    <table class="table admin-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Item</th>
                                <th scope="col">Qtd.</th>
                                <th scope="col">Preço</th>
                                <th scope="col" class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody data-registro-itens>
                            <tr><td colspan="4" class="text-muted">Carregando...</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-end mt-3">
                    <span class="fs-6">Total: <strong data-registro-total>R$ 0,000</strong></span>
                </div>
            </div>
        </div>
    </div>
</div>
