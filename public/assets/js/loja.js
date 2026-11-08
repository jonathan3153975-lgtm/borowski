document.addEventListener('DOMContentLoaded', () => {
    const modeInputs = document.querySelectorAll('[data-entry-mode]');
    const directFields = document.querySelectorAll('[data-direct-field]');

    const applyMode = (mode) => {
        directFields.forEach((field) => {
            const isDirect = mode === 'direta';
            field.hidden = !isDirect;

            field.querySelectorAll('input, select, textarea').forEach((input) => {
                input.disabled = !isDirect;
            });
        });
    };

    if (modeInputs.length > 0) {
        const checked = document.querySelector('[data-entry-mode]:checked');

        applyMode(checked ? checked.value : 'direta');

        modeInputs.forEach((input) => {
            input.addEventListener('change', () => {
                if (input.checked) {
                    applyMode(input.value);
                }
            });
        });
    }

    document.querySelectorAll('[data-product-select]').forEach((select) => {
        select.addEventListener('change', () => {
            const option = select.selectedOptions[0];

            if (!option || !option.value) {
                return;
            }

            const nameTarget = document.getElementById(select.dataset.nameTarget || '');
            const codeTarget = document.getElementById(select.dataset.codeTarget || '');

            if (nameTarget && option.dataset.nome !== undefined) {
                nameTarget.value = option.dataset.nome;
            }

            if (codeTarget && option.dataset.sku !== undefined) {
                codeTarget.value = option.dataset.sku;
            }
        });
    });

    const stickyTops = document.querySelectorAll('[data-loja-sticky-top]');

    if (stickyTops.length > 0) {
        const media = window.matchMedia('(max-width: 991.98px)');

        const syncToggles = (expanded) => {
            document.querySelectorAll('[data-loja-compact-toggle]').forEach((button) => {
                button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                button.setAttribute('aria-label', expanded ? 'Ocultar opções' : 'Mostrar opções');

                const icon = button.querySelector('i');

                if (icon) {
                    icon.classList.toggle('bi-chevron-up', expanded);
                    icon.classList.toggle('bi-chevron-down', !expanded);
                }
            });
        };

        const setExpanded = (expanded) => {
            const effective = media.matches ? expanded : true;

            stickyTops.forEach((top) => {
                top.classList.toggle('is-expanded', effective);
            });

            syncToggles(effective);
        };

        document.querySelectorAll('[data-loja-compact-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const expanded = !stickyTops[0].classList.contains('is-expanded');
                setExpanded(expanded);
            });
        });

        // mobile inicia recolhido; desktop sempre expandido
        setExpanded(false);

        let wasMobile = media.matches;

        const onMediaChange = () => {
            const isMobile = media.matches;

            if (!isMobile) {
                setExpanded(true);
            } else if (!wasMobile) {
                setExpanded(false);
            }

            wasMobile = isMobile;
        };

        if (typeof media.addEventListener === 'function') {
            media.addEventListener('change', onMediaChange);
        } else {
            media.addListener(onMediaChange);
        }
    }

    if (typeof bootstrap !== 'undefined') {
        document.querySelectorAll('.modal[data-auto-open="1"]').forEach((modal) => {
            bootstrap.Modal.getOrCreateInstance(modal).show();
        });
    }
});

document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('publishModal');

    if (!modal) {
        return;
    }

    const select = modal.querySelector('[data-publish-select]');
    const preview = modal.querySelector('[data-publish-preview]');
    const image = modal.querySelector('[data-preview-image]');
    const placeholder = modal.querySelector('[data-preview-placeholder]');
    const nameEl = modal.querySelector('[data-preview-nome]');
    const metaEl = modal.querySelector('[data-preview-meta]');
    const descEl = modal.querySelector('[data-preview-descricao]');
    const priceEl = modal.querySelector('[data-preview-preco]');
    const costEl = modal.querySelector('[data-cost-display]');
    const marginEl = modal.querySelector('[data-margin-display]');
    const marginPctEl = modal.querySelector('[data-margin-pct]');
    const priceInput = modal.querySelector('[data-publish-price]');
    const availableEl = modal.querySelector('[data-available-qty]');
    const eyebrow = modal.querySelector('[data-preview-canal]');
    const reserveField = modal.querySelector('[data-reserve-field]');
    const reserveInput = modal.querySelector('[data-publish-reserve]');
    const submitBtn = modal.querySelector('[data-publish-submit]');
    const channelChecks = Array.from(modal.querySelectorAll('[data-publish-channel]'));

    if (!select || !priceInput) {
        return;
    }

    const formatMoney = (value) => 'R$ ' + value.toLocaleString('pt-BR', {
        minimumFractionDigits: 3,
        maximumFractionDigits: 3
    });

    const parseMoney = (value) => {
        const digits = String(value || '').replace(/\D/g, '');

        return digits === '' ? 0 : parseInt(digits, 10) / 1000;
    };

    let cost = 0;

    const selectedOption = () => select.selectedOptions[0];

    const selectedChannels = () => channelChecks.filter((cb) => cb.checked && !cb.disabled);

    const qtyFor = (slug) => {
        const row = modal.querySelector('[data-publish-channel-row][data-channel-slug="' + slug + '"]');

        return row ? row.querySelector('[data-publish-channel-qty]') : null;
    };

    const dispose = () => {
        preview.hidden = true;
        cost = 0;
        costEl.textContent = '-';

        if (availableEl) {
            availableEl.textContent = '-';
        }

        if (reserveField) {
            reserveField.hidden = true;
        }

        refreshMargin();
    };

    const refreshMargin = () => {
        const price = parseMoney(priceInput.value);
        const margin = price - cost;

        priceEl.textContent = price > 0 ? formatMoney(price) : '';

        if (price > 0) {
            marginEl.textContent = formatMoney(margin);
            marginEl.classList.toggle('text-success', margin >= 0);
            marginEl.classList.toggle('text-danger', margin < 0);
        } else {
            marginEl.textContent = '-';
            marginEl.classList.remove('text-success', 'text-danger');
        }

        marginPctEl.textContent = (cost > 0 && price > 0)
            ? '(' + ((margin / cost) * 100).toFixed(1).replace('.', ',') + '%)'
            : '';
    };

    const updateSummary = (available) => {
        const channels = selectedChannels();
        const total = channels.reduce((sum, cb) => {
            const qty = qtyFor(cb.value);

            return sum + Math.max(0, parseInt(qty ? qty.value : '0', 10) || 0);
        }, 0);

        if (eyebrow) {
            eyebrow.textContent = channels.length > 0
                ? 'Anúncio para ' + channels.map((cb) => {
                    const row = cb.closest('[data-publish-channel-row]');
                    const label = row ? row.querySelector('.publish-channel-name') : null;

                    return label ? label.textContent.trim() : cb.value;
                }).join(', ')
                : 'Anúncio';
        }

        const invalid = channels.length === 0 || total < 1;

        if (submitBtn) {
            submitBtn.disabled = invalid;
        }
    };

    const refreshChannelState = () => {
        const option = selectedOption();
        const canais = option && option.value ? (option.dataset.canais || '').split(',').filter(Boolean) : [];
        const available = option ? (parseInt(option.dataset.disponivel || '0', 10) || 0) : 0;

        if (availableEl) {
            availableEl.textContent = option && option.value ? available + ' un.' : '-';
        }

        channelChecks.forEach((cb) => {
            const row = cb.closest('[data-publish-channel-row]');
            const qty = row ? row.querySelector('[data-publish-channel-qty]') : null;
            const note = row ? row.querySelector('[data-publish-channel-note]') : null;
            const jaTem = canais.indexOf(cb.value) !== -1;

            cb.disabled = jaTem;

            if (row) {
                row.classList.toggle('is-published', jaTem);
            }

            if (jaTem) {
                cb.checked = false;
            }

            if (qty) {
                qty.max = String(Math.max(1, available));
                qty.disabled = jaTem || !cb.checked;

                if (Number(qty.value) < 1) {
                    qty.value = '1';
                }
            }

            if (note) {
                note.textContent = jaTem ? 'Já publicado' : '';
            }
        });

        updateSummary(available);
    };

    const refreshPreview = () => {
        const option = selectedOption();

        if (!option || option.value === '') {
            dispose();
            refreshChannelState();
            return;
        }

        preview.hidden = false;

        const isPreVenda = option.dataset.status === 'pre_venda';

        if (reserveField) {
            reserveField.hidden = !isPreVenda;

            if (reserveInput) {
                reserveInput.required = isPreVenda;
            }
        }

        nameEl.textContent = option.dataset.nome || '';
        metaEl.textContent = option.dataset.meta || '';
        descEl.textContent = option.dataset.descricao || '';
        cost = parseFloat(option.dataset.custo || '0') || 0;
        costEl.textContent = cost > 0 ? formatMoney(cost) : '-';

        if (option.dataset.imagem) {
            image.src = option.dataset.imagem;
            image.hidden = false;
            placeholder.style.display = 'none';
        } else {
            image.hidden = true;
            image.removeAttribute('src');
            placeholder.style.display = '';
        }

        refreshChannelState();
        refreshMargin();
    };

    select.addEventListener('change', refreshPreview);
    priceInput.addEventListener('input', refreshMargin);

    channelChecks.forEach((cb) => {
        cb.addEventListener('change', () => {
            const row = cb.closest('[data-publish-channel-row]');
            const qty = row ? row.querySelector('[data-publish-channel-qty]') : null;

            if (qty) {
                qty.disabled = !cb.checked;
            }

            const option = selectedOption();
            const disponivel = option ? (parseInt(option.dataset.disponivel || '0', 10) || 0) : 0;

            updateSummary(disponivel);
        });
    });

    modal.querySelectorAll('[data-publish-channel-qty]').forEach((qty) => {
        qty.addEventListener('input', () => {
            const min = parseInt(qty.min || '1', 10) || 1;
            const max = parseInt(qty.max || '0', 10) || 0;
            const value = parseInt(qty.value || '0', 10) || 0;

            if (value < min) {
                qty.value = String(min);
            } else if (max > 0 && value > max) {
                qty.value = String(max);
            }

            const option = selectedOption();
            const disponivel = option ? (parseInt(option.dataset.disponivel || '0', 10) || 0) : 0;

            updateSummary(disponivel);
        });
    });

    const form = modal.querySelector('[data-publish-form]');

    if (form) {
                form.addEventListener('submit', (event) => {
            if (form.dataset.submitting === '1') {
                event.preventDefault();
                return;
            }

            const channels = selectedChannels();

            if (channels.length === 0) {
                event.preventDefault();
                window.alert('Selecione ao menos um canal de publicação.');
                return;
            }

            const total = channels.reduce((sum, cb) => {
                const qty = qtyFor(cb.value);

                return sum + (parseInt(qty ? qty.value : '0', 10) || 0);
            }, 0);

            if (total < 1) {
                event.preventDefault();
                window.alert('Informe a quantidade para os canais selecionados.');
                return;
            }

            form.dataset.submitting = '1';

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Publicando...';
            }

            const overlay = document.createElement('div');
            overlay.className = 'loja-loading-overlay';
            overlay.innerHTML = '<div class="loja-loading-card"><span class="spinner-border text-primary" role="status" aria-hidden="true"></span><strong>Publicando item...</strong><span>Aguarde, não feche esta tela.</span></div>';
            document.body.appendChild(overlay);
        });
    }
    modal.addEventListener('show.bs.modal', (event) => {
        const trigger = event.relatedTarget;
        const itemId = trigger
            ? trigger.getAttribute('data-publish-item')
            : (modal.getAttribute('data-publish-preselect') || '');

        if (!itemId) {
            refreshPreview();
            return;
        }

        const option = Array.from(select.options).find((opt) => opt.value === itemId);

        if (!option) {
            return;
        }

        select.value = itemId;

        const canais = (option.dataset.canais || '').split(',').filter(Boolean);
        channelChecks.forEach((cb) => {
            cb.checked = false;
        });

        const primeiro = channelChecks.find((cb) => canais.indexOf(cb.value) === -1);

        if (primeiro) {
            primeiro.checked = true;
        }

        refreshPreview();
    });

    refreshPreview();

    const optionInicial = selectedOption();

    if (optionInicial && optionInicial.value !== '' && selectedChannels().length === 0) {
        const canaisIniciais = (optionInicial.dataset.canais || '').split(',').filter(Boolean);
        const primeiroInicial = channelChecks.find((cb) => !cb.disabled && canaisIniciais.indexOf(cb.value) === -1);

        if (primeiroInicial) {
            primeiroInicial.checked = true;
        }

        refreshChannelState();
    }
});

document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('viewItemModal');

    if (!modal) {
        return;
    }

    const image = modal.querySelector('[data-view-image]');
    const placeholder = modal.querySelector('[data-view-placeholder]');
    const nomeEl = modal.querySelector('[data-view-nome]');
    const metaEl = modal.querySelector('[data-view-meta]');
    const descEl = modal.querySelector('[data-view-descricao]');
    const canaisEl = modal.querySelector('[data-view-canais]');
    const precoEl = modal.querySelector('[data-view-preco]');
    const custoEl = modal.querySelector('[data-view-custo]');
    const reservaEl = modal.querySelector('[data-view-reserva]');
    const totalEl = modal.querySelector('[data-view-total]');
    const disponivelEl = modal.querySelector('[data-view-disponivel]');
    const reservadaEl = modal.querySelector('[data-view-reservada]');
    const vendidaEl = modal.querySelector('[data-view-vendida]');
    const custoTotalEl = modal.querySelector('[data-view-custo-total]');
    const valorEstoqueEl = modal.querySelector('[data-view-valor-estoque]');
    const valorReservadoEl = modal.querySelector('[data-view-valor-reservado]');
    const arrecadadoEl = modal.querySelector('[data-view-arrecadado]');

    modal.addEventListener('show.bs.modal', (event) => {
        const trigger = event.relatedTarget;

        if (!trigger) {
            return;
        }

        const data = trigger.dataset;
        nomeEl.textContent = data.nome || '';
        metaEl.textContent = data.meta || '';
        descEl.textContent = data.descricao || '';
        precoEl.textContent = data.preco ? data.preco : 'Valor de venda a definir';
        custoEl.textContent = data.custo || '—';
        reservaEl.textContent = data.reserva ? ('Reserva: ' + data.reserva) : '';
        totalEl.textContent = data.total || '0';
        disponivelEl.textContent = data.disponivel || '0';
        reservadaEl.textContent = data.reservada || '0';
        vendidaEl.textContent = data.vendida || '0';
        custoTotalEl.textContent = data.custototal || '—';
        valorEstoqueEl.textContent = data.valorestoque || '—';
        valorReservadoEl.textContent = data.valorreservado || '—';
        arrecadadoEl.textContent = data.arrecadado || '—';

        if (data.imagem) {
            image.src = data.imagem;
            image.hidden = false;
            placeholder.style.display = 'none';
        } else {
            image.hidden = true;
            image.removeAttribute('src');
            placeholder.style.display = '';
        }

        canaisEl.innerHTML = '';

        (data.canais || '').split(',').map((item) => item.trim()).filter(Boolean).forEach((nome) => {
            const badge = document.createElement('span');
            badge.className = 'badge-soft';
            badge.textContent = nome;
            canaisEl.appendChild(badge);
        });
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const container = document.querySelector('[data-demanda-ofertas]');
    const addButton = document.querySelector('[data-demanda-add]');

    if (!container || !addButton) {
        return;
    }

    let counter = 1;

    addButton.addEventListener('click', () => {
        const first = container.querySelector('[data-demanda-oferta]');

        if (!first) {
            return;
        }

        const index = counter++;
        const clone = first.cloneNode(true);

        clone.querySelectorAll('input').forEach((input) => {
            input.name = input.name.replace(/ofertas\[\d+\]/, 'ofertas[' + index + ']');
            input.value = '';
        });

        container.appendChild(clone);

        if (window.jQuery && window.jQuery.fn.moneyMask) {
            window.jQuery(clone).find('[data-money]').moneyMask();
        }
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const pedidos = document.querySelector('[data-pedidos-auto-refresh]');

    if (!pedidos) {
        return;
    }

    const seconds = parseInt(pedidos.getAttribute('data-pedidos-auto-refresh'), 10) || 15;

    window.setInterval(() => {
        if (document.hidden || document.querySelector('.modal.show')) {
            return;
        }

        window.location.reload();
    }, seconds * 1000);
});

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-live-filter]').forEach((input) => {
        const apply = () => {
            const query = input.value.trim().toLowerCase();

            document.querySelectorAll('[data-filter-row]').forEach((row) => {
                row.hidden = query !== '' && row.textContent.toLowerCase().indexOf(query) === -1;
            });
        };

        input.addEventListener('input', apply);
        apply();
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('btnCriarEntrega');
    const confirmBtn = document.getElementById('btnConfirmarEntrega');
    const modalEl = document.getElementById('entregaModal');

    if (!toggleBtn || !confirmBtn || !modalEl) {
        return;
    }

    const checkboxes = () => Array.from(document.querySelectorAll('[data-entrega-item]'));
    const checkedBoxes = () => checkboxes().filter((cb) => cb.checked);
    const syncConfirm = () => {
        confirmBtn.disabled = checkedBoxes().length === 0;
    };

    toggleBtn.addEventListener('click', () => {
        const active = document.body.classList.toggle('entrega-mode');

        toggleBtn.innerHTML = active
            ? '<i class="bi bi-x-circle"></i> Cancelar entrega'
            : '<i class="bi bi-box-seam"></i> Criar entrega';
        confirmBtn.classList.toggle('d-none', !active);

        if (!active) {
            checkboxes().forEach((cb) => {
                cb.checked = false;
            });
        }

        syncConfirm();
    });

    checkboxes().forEach((cb) => {
        cb.addEventListener('change', () => {
            if (cb.checked) {
                const buyer = cb.getAttribute('data-buyer') || '';
                const conflito = checkedBoxes().find(
                    (outro) => outro !== cb && (outro.getAttribute('data-buyer') || '') !== buyer
                );

                if (conflito) {
                    cb.checked = false;
                    window.alert('Selecione apenas vendas do mesmo comprador.');
                }
            }

            syncConfirm();
        });
    });

    const setValue = (id, value) => {
        const el = document.getElementById(id);

        if (el && value !== null && value !== undefined) {
            el.value = value;
        }
    };

    confirmBtn.addEventListener('click', () => {
        const mapa = new Map();

        checkedBoxes().forEach((cb) => {
            if (!mapa.has(cb.value)) {
                mapa.set(cb.value, cb);
            }
        });

        const itens = Array.from(mapa.values());

        if (itens.length === 0) {
            return;
        }

        const buyer = itens[0].getAttribute('data-buyer') || '';
        const idsContainer = document.getElementById('entregaItensIds');
        const resumo = document.getElementById('entregaItensResumo');
        idsContainer.innerHTML = '';
        resumo.innerHTML = '';

        itens.forEach((cb) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'itens[]';
            input.value = cb.value;
            idsContainer.appendChild(input);

            const li = document.createElement('li');
            li.textContent = (cb.getAttribute('data-nome') || '')
                + ' — ' + (cb.getAttribute('data-qtd') || '1') + ' un. — '
                + (cb.getAttribute('data-preco') || '');
            resumo.appendChild(li);
        });

        setValue('entregaTelegramId', buyer);

        ['entregaNome', 'entregaCpf', 'entregaTelefone', 'entregaCep', 'entregaLogradouro',
            'entregaNumero', 'entregaComplemento', 'entregaBairro', 'entregaCidade', 'entregaEstado']
            .forEach((id) => setValue(id, ''));

        const clienteUrl = confirmBtn.getAttribute('data-cliente-url') || '';

        if (buyer !== '' && clienteUrl !== '') {
            fetch(clienteUrl + '?telegram=' + encodeURIComponent(buyer), {
                headers: { Accept: 'application/json' }
            })
                .then((response) => (response.ok ? response.json() : null))
                .then((cliente) => {
                    if (!cliente) {
                        return;
                    }

                    setValue('entregaNome', cliente.nome);
                    setValue('entregaCpf', cliente.cpf);
                    setValue('entregaTelefone', cliente.telefone);
                    setValue('entregaCep', cliente.cep);
                    setValue('entregaLogradouro', cliente.logradouro);
                    setValue('entregaNumero', cliente.numero);
                    setValue('entregaComplemento', cliente.complemento);
                    setValue('entregaBairro', cliente.bairro);
                    setValue('entregaCidade', cliente.cidade);
                    setValue('entregaEstado', cliente.estado);
                })
                .catch(() => {});
        }

        if (typeof bootstrap !== 'undefined') {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    });

    const cepInput = document.getElementById('entregaCep');

    if (cepInput) {
        cepInput.addEventListener('input', () => {
            const digitos = cepInput.value.replace(/\D/g, '').slice(0, 8);
            cepInput.value = digitos.length > 5 ? digitos.slice(0, 5) + '-' + digitos.slice(5) : digitos;

            if (digitos.length !== 8) {
                return;
            }

            fetch('https://viacep.com.br/ws/' + digitos + '/json/')
                .then((response) => response.json())
                .then((dados) => {
                    if (!dados || dados.erro) {
                        return;
                    }

                    const preencher = (id, valor) => {
                        const el = document.getElementById(id);

                        if (el && el.value.trim() === '') {
                            el.value = valor || '';
                        }
                    };

                    preencher('entregaLogradouro', dados.logradouro);
                    preencher('entregaBairro', dados.bairro);
                    preencher('entregaCidade', dados.localidade);
                    preencher('entregaEstado', dados.uf);

                    const numero = document.getElementById('entregaNumero');

                    if (numero) {
                        numero.focus();
                    }
                })
                .catch(() => {});
        });
    }
});

document.addEventListener('DOMContentLoaded', () => {
    const viewButtons = document.querySelectorAll('[data-entrega-view]');
    const modalEl = document.getElementById('entregaItensModal');

    if (!viewButtons.length || !modalEl) {
        return;
    }

    const info = document.getElementById('entregaItensInfo');
    const body = document.getElementById('entregaItensBody');
    const modal = window.bootstrap ? new window.bootstrap.Modal(modalEl) : null;

    const esc = (value) => {
        const div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    };

    const dinheiro = (value) => Number(value || 0).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    });

    viewButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const url = button.getAttribute('data-entrega-view-url');

            if (!url) {
                return;
            }

            info.textContent = 'Carregando...';
            body.innerHTML = '<tr><td colspan="4" class="text-muted">Carregando...</td></tr>';

            if (modal) {
                modal.show();
            }

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then((response) => response.json())
                .then((data) => {
                    if (data.error) {
                        info.textContent = data.error;
                        body.innerHTML = '';
                        return;
                    }

                    const entrega = data.entrega || {};
                    const itens = data.itens || [];
                    const local = [entrega.cidade, entrega.estado].filter(Boolean).join(' / ');

                    info.innerHTML = '<strong>Entrega #' + esc(entrega.id) + '</strong>'
                        + '<div class="small text-muted">' + esc(entrega.cliente_nome || '') + '</div>'
                        + (local ? '<div class="small text-muted">' + esc(local) + '</div>' : '')
                        + (entrega.codigo_rastreio
                            ? '<div class="small text-muted">Rastreio: ' + esc(entrega.codigo_rastreio) + '</div>'
                            : '');

                    if (!itens.length) {
                        body.innerHTML = '<tr><td colspan="4" class="text-muted">Nenhum item.</td></tr>';
                        return;
                    }

                    let total = 0;
                    const linhas = itens.map((item) => {
                        const qtd = Number(item.quantidade || 0);
                        const preco = Number(item.preco_venda || 0);
                        const subtotal = qtd * preco;
                        total += subtotal;

                        return '<tr>'
                            + '<td><strong>' + esc(item.item_nome || '') + '</strong>'
                            + (item.item_codigo ? '<span class="small text-muted d-block">SKU: ' + esc(item.item_codigo) + '</span>' : '')
                            + '</td>'
                            + '<td>' + qtd + '</td>'
                            + '<td>' + dinheiro(preco) + '</td>'
                            + '<td class="text-end">' + dinheiro(subtotal) + '</td>'
                            + '</tr>';
                    });

                    linhas.push(
                        '<tr><td colspan="3" class="text-end"><strong>Total</strong></td>'
                        + '<td class="text-end"><strong>' + dinheiro(total) + '</strong></td></tr>'
                    );

                    body.innerHTML = linhas.join('');
                })
                .catch(() => {
                    info.textContent = 'Não foi possível carregar os itens.';
                    body.innerHTML = '';
                });
        });
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('viewPublicacaoModal');

    if (!modal) {
        return;
    }

    const main = modal.querySelector('[data-pub-main]');
    const placeholder = modal.querySelector('[data-pub-placeholder]');
    const mainBtn = modal.querySelector('[data-pub-main-btn]');
    const thumbs = modal.querySelector('[data-pub-thumbs]');
    const title = modal.querySelector('[data-pub-title]');
    const sku = modal.querySelector('[data-pub-sku]');
    const meta = modal.querySelector('[data-pub-meta]');
    const desc = modal.querySelector('[data-pub-desc]');
    const canais = modal.querySelector('[data-pub-canais]');
    const preco = modal.querySelector('[data-pub-preco]');
    const reserva = modal.querySelector('[data-pub-reserva]');
    const qtd = modal.querySelector('[data-pub-quantidade]');
    const vendidos = modal.querySelector('[data-pub-vendidos]');
    const custo = modal.querySelector('[data-pub-custo]');
    const margem = modal.querySelector('[data-pub-margem]');
    const canalLabel = modal.querySelector('[data-pub-canal-label]');

    const lightbox = document.querySelector('[data-admin-lightbox]');
    const lightboxImg = document.querySelector('[data-admin-lightbox-img]');
    const lightboxClose = document.querySelector('[data-admin-lightbox-close]');

    const openLightbox = (src) => {
        if (!lightbox || !lightboxImg || !src) {
            return;
        }

        lightboxImg.src = src;
        lightbox.hidden = false;
    };

    const closeLightbox = () => {
        if (!lightbox) {
            return;
        }

        lightbox.hidden = true;

        if (lightboxImg) {
            lightboxImg.removeAttribute('src');
        }
    };

    if (lightboxClose) {
        lightboxClose.addEventListener('click', closeLightbox);
    }

    if (lightbox) {
        lightbox.addEventListener('click', (event) => {
            if (event.target === lightbox) {
                closeLightbox();
            }
        });
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && lightbox && !lightbox.hidden) {
            closeLightbox();
        }
    });

    if (mainBtn) {
        mainBtn.addEventListener('click', () => {
            if (main && !main.hidden && main.src) {
                openLightbox(main.src);
            }
        });
    }

    const setMain = (src) => {
        if (!main || !placeholder) {
            return;
        }

        if (!src) {
            main.hidden = true;
            main.removeAttribute('src');
            placeholder.style.display = '';
            return;
        }

        main.src = src;
        main.hidden = false;
        placeholder.style.display = 'none';
    };

    const renderThumbs = (imagens) => {
        if (!thumbs) {
            return;
        }

        thumbs.innerHTML = '';

        imagens.forEach((imagem, index) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'pub-view-thumb' + (index === 0 ? ' is-active' : '');

            const img = document.createElement('img');
            img.src = imagem.url;
            img.alt = '';
            img.loading = 'lazy';

            button.appendChild(img);
            button.addEventListener('click', () => {
                thumbs.querySelectorAll('.pub-view-thumb').forEach((el) => el.classList.remove('is-active'));
                button.classList.add('is-active');
                setMain(imagem.url);
                openLightbox(imagem.url);
            });

            thumbs.appendChild(button);
        });
    };

    modal.addEventListener('show.bs.modal', (event) => {
        const trigger = event.relatedTarget;

        if (!trigger) {
            return;
        }

        const data = trigger.dataset;
        title.textContent = data.pubNome || '';

        if (sku) {
            sku.textContent = data.pubSku ? 'SKU: ' + data.pubSku : '';
        }

        meta.textContent = data.pubMeta ? 'Origem: ' + data.pubMeta : '';
        desc.textContent = data.pubDescricao || '';
        preco.textContent = data.pubPreco || '';
        reserva.textContent = data.pubReserva ? 'Reserva: ' + data.pubReserva : '';
        qtd.textContent = data.pubQuantidade || '0';
        vendidos.textContent = data.pubVendidos || '0';
        custo.textContent = data.pubCusto || '-';
        margem.textContent = data.pubMargem || '-';
        canalLabel.textContent = 'Anuncio para ' + (data.pubCanal || '');

        canais.innerHTML = '';

        let lista = [];
        try {
            lista = JSON.parse(data.pubCanais || '[]');
        } catch (e) {
            lista = [];
        }

        lista.forEach((canal) => {
            const badge = document.createElement('span');
            badge.className = 'badge-soft';
            const icon = document.createElement('i');
            icon.className = 'bi ' + (canal.slug === 'telegram' ? 'bi-telegram' : (canal.slug === 'whatsapp' ? 'bi-whatsapp' : 'bi-shop'));
            badge.appendChild(icon);
            badge.appendChild(document.createTextNode(' ' + (canal.nome || '')));
            canais.appendChild(badge);
        });

        let imagens = [];
        try {
            imagens = JSON.parse(data.pubImagens || '[]');
        } catch (e) {
            imagens = [];
        }

        const principal = imagens.find((img) => img.principal) || imagens[0];
        setMain(principal ? principal.url : null);
        renderThumbs(imagens);
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-venda-direta-form]');

    if (!form) {
        return;
    }

    const rows = Array.from(form.querySelectorAll('tr')).filter((row) => row.querySelector('[data-venda-item-check]'));
    const totalEl = form.querySelector('[data-venda-total]');
    const money = (value) => Number(value || 0).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL',
        minimumFractionDigits: 3,
        maximumFractionDigits: 3
    });
    const parse = (value) => {
        const digits = String(value || '').replace(/\D/g, '');

        return digits === '' ? 0 : parseInt(digits, 10) / 1000;
    };

    const recalc = () => {
        let total = 0;

        rows.forEach((row) => {
            const check = row.querySelector('[data-venda-item-check]');
            const qty = row.querySelector('[data-venda-item-qty]');
            const price = row.querySelector('[data-venda-item-price]');
            const sub = row.querySelector('[data-venda-item-subtotal]');

            if (!check || !qty || !price || !sub) {
                return;
            }

            qty.disabled = !check.checked;
            price.disabled = !check.checked;

            if (!check.checked) {
                sub.textContent = '�';
                return;
            }

            const max = parseInt(qty.max || '1', 10) || 1;
            const q = Math.min(max, Math.max(1, parseInt(qty.value || '1', 10) || 1));
            qty.value = String(q);
            const subtotal = parse(price.value) * q;
            total += subtotal;
            sub.textContent = money(subtotal);
        });

        if (totalEl) {
            totalEl.textContent = money(total);
        }
    };

    form.addEventListener('change', recalc);
    form.addEventListener('input', recalc);
    form.addEventListener('submit', (event) => {
        if (!form.querySelector('[data-venda-item-check]:checked')) {
            event.preventDefault();
            window.alert('Selecione ao menos um item para a venda.');
        }
    });

    recalc();
});

document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('registroDetalhesModal');

    if (!modal) {
        return;
    }

    const titulo = modal.querySelector('[data-registro-titulo]');
    const subtitulo = modal.querySelector('[data-registro-subtitulo]');
    const info = modal.querySelector('[data-registro-info]');
    const tbody = modal.querySelector('[data-registro-itens]');
    const totalEl = modal.querySelector('[data-registro-total]');
    const money = (value) => Number(value || 0).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL',
        minimumFractionDigits: 3,
        maximumFractionDigits: 3
    });

    modal.addEventListener('show.bs.modal', (event) => {
        const trigger = event.relatedTarget;

        if (!trigger || !tbody) {
            return;
        }

        const data = trigger.dataset;

        if (titulo) {
            titulo.textContent = data.registroTitulo || 'Detalhes';
        }

        if (subtitulo) {
            subtitulo.textContent = data.registroSubtitulo || '';
        }

        if (info) {
            info.textContent = data.registroInfo || '';
        }

        let itens = [];
        try {
            itens = JSON.parse(data.registroItens || '[]');
        } catch (e) {
            itens = [];
        }

        tbody.innerHTML = '';
        let total = 0;

        if (!Array.isArray(itens) || itens.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-muted">Nenhum item.</td></tr>';
        } else {
            itens.forEach((item) => {
                const qtd = Number(item.quantidade || 0);
                const preco = Number(item.preco || 0);
                const subtotal = Number(item.subtotal || qtd * preco);
                total += subtotal;

                const tr = document.createElement('tr');
                const td1 = document.createElement('td');
                const strong = document.createElement('strong');
                strong.textContent = item.nome || '';
                td1.appendChild(strong);

                if (item.codigo) {
                    const span = document.createElement('span');
                    span.className = 'small text-muted d-block';
                    span.textContent = 'SKU: ' + item.codigo;
                    td1.appendChild(span);
                }

                tr.appendChild(td1);

                const td2 = document.createElement('td');
                td2.textContent = String(qtd);
                tr.appendChild(td2);

                const td3 = document.createElement('td');
                td3.textContent = money(preco);
                tr.appendChild(td3);

                const td4 = document.createElement('td');
                td4.className = 'text-end';
                td4.textContent = money(subtotal);
                tr.appendChild(td4);

                tbody.appendChild(tr);
            });
        }

        if (totalEl) {
            totalEl.textContent = money(total);
        }
    });
});

document.addEventListener('DOMContentLoaded', () => {
    if (!window.bootstrap) {
        return;
    }

    document.querySelectorAll('[data-vendas-actions]').forEach((toggle) => {
        bootstrap.Dropdown.getOrCreateInstance(toggle, {
            popperConfig: { strategy: 'fixed' }
        });
    });
});

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-copy-link]').forEach((button) => {
        button.addEventListener('click', () => {
            const link = button.getAttribute('data-copy-link') || '';

            if (!link) {
                return;
            }

            const done = () => {
                const original = button.innerHTML;
                button.innerHTML = '<i class="bi bi-check2"></i> Copiado!';
                window.setTimeout(() => {
                    button.innerHTML = original;
                }, 1500);
            };

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(link).then(done).catch(() => {
                    window.prompt('Copie o link:', link);
                });
            } else {
                window.prompt('Copie o link:', link);
            }
        });
    });

    const form = document.querySelector('[data-venda-direta-form]');
    const modalEl = document.getElementById('vendaDiretaResumoModal');

    if (!form || !modalEl) {
        return;
    }

    const modal = window.bootstrap ? new window.bootstrap.Modal(modalEl) : null;
    const tbody = modalEl.querySelector('[data-venda-resumo-itens]');
    const totalEl = modalEl.querySelector('[data-venda-resumo-total]');
    const clienteEl = modalEl.querySelector('[data-venda-resumo-cliente]');
    const metodoEl = modalEl.querySelector('[data-venda-resumo-metodo]');
    const entregaEl = modalEl.querySelector('[data-venda-resumo-entrega]');
    const dataEl = modalEl.querySelector('[data-venda-resumo-data]');
    const money = (value) => Number(value || 0).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL',
        minimumFractionDigits: 3,
        maximumFractionDigits: 3
    });
    const parse = (value) => {
        const digits = String(value || '').replace(/\D/g, '');
        return digits === '' ? 0 : parseInt(digits, 10) / 1000;
    };

    const review = () => {
        const checked = Array.from(form.querySelectorAll('[data-venda-item-check]:checked'));

        if (checked.length === 0) {
            window.alert('Selecione ao menos um item para a venda.');
            return;
        }

        let total = 0;
        tbody.innerHTML = '';

        checked.forEach((checkbox) => {
            const row = checkbox.closest('tr');
            const nameEl = row ? row.querySelector('.loja-item-cell-info strong') : null;
            const qtyInput = row ? row.querySelector('[data-venda-item-qty]') : null;
            const priceInput = row ? row.querySelector('[data-venda-item-price]') : null;
            const qty = Math.max(1, parseInt(qtyInput ? qtyInput.value : '1', 10) || 1);
            const price = parse(priceInput ? priceInput.value : '');
            const subtotal = qty * price;
            total += subtotal;

            const tr = document.createElement('tr');
            const td1 = document.createElement('td');
            td1.textContent = nameEl ? nameEl.textContent.trim() : '';
            tr.appendChild(td1);
            const td2 = document.createElement('td');
            td2.textContent = String(qty);
            tr.appendChild(td2);
            const td3 = document.createElement('td');
            td3.textContent = money(price);
            tr.appendChild(td3);
            const td4 = document.createElement('td');
            td4.className = 'text-end';
            td4.textContent = money(subtotal);
            tr.appendChild(td4);
            tbody.appendChild(tr);
        });

        totalEl.textContent = money(total);

        const cliente = form.querySelector('[name="cliente_nome"]');
        clienteEl.textContent = cliente && cliente.value.trim() !== '' ? cliente.value.trim() : 'Venda presencial';

        const metodo = form.querySelector('[name="metodo_pagamento"]');
        metodoEl.textContent = metodo ? metodo.options[metodo.selectedIndex].text : '';

        const entregar = form.querySelector('[name="entregar_agora"]');
        entregaEl.textContent = entregar && entregar.checked ? 'Entrega imediata' : 'Aguardando entrega';

        dataEl.textContent = new Date().toLocaleString('pt-BR');

        if (modal) {
            modal.show();
        }
    };

    const reviewBtn = form.querySelector('[data-venda-revisar]');

    if (reviewBtn) {
        reviewBtn.addEventListener('click', review);
    }

    const confirmBtn = modalEl.querySelector('[data-venda-confirmar]');

    if (confirmBtn) {
        confirmBtn.addEventListener('click', () => {
            if (modal) {
                modal.hide();
            }

            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        });
    }

    modalEl.querySelectorAll('[data-venda-imprimir], [data-venda-pdf]').forEach((button) => {
        button.addEventListener('click', () => window.print());
    });
});

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-auto-submit]').forEach((field) => {
        field.addEventListener('change', () => {
            if (field.form) {
                field.form.submit();
            }
        });
    });
});
