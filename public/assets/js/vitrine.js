document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-vitrine-step]').forEach((button) => {
        button.addEventListener('click', () => {
            const wrap = button.closest('.vitrine-qty');
            const input = wrap ? wrap.querySelector('input[type="number"]') : null;

            if (!input || input.disabled) {
                return;
            }

            const step = Number(button.getAttribute('data-vitrine-step')) || 0;
            const min = Number(input.min || 1);
            const max = Number(input.max || 999);
            const atual = Number(input.value) || min;
            const proximo = Math.min(max, Math.max(min, atual + step));

            input.value = String(proximo);
        });
    });

    const modalEl = document.getElementById('vitrineItemModal');
    const modal = modalEl && window.bootstrap ? new window.bootstrap.Modal(modalEl) : null;
    const lightbox = document.querySelector('[data-vitrine-lightbox]');
    const lightboxImg = document.querySelector('[data-vitrine-lightbox-img]');
    const lightboxClose = document.querySelector('[data-vitrine-lightbox-close]');

    const openLightbox = (src) => {
        if (!lightbox || !lightboxImg || !src) {
            return;
        }

        lightboxImg.src = src;
        lightbox.hidden = false;
        document.body.classList.add('vitrine-lightbox-open');
    };

    const closeLightbox = () => {
        if (!lightbox) {
            return;
        }

        lightbox.hidden = true;

        if (lightboxImg) {
            lightboxImg.removeAttribute('src');
        }

        document.body.classList.remove('vitrine-lightbox-open');
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

    if (!modalEl || !modal) {
        return;
    }

    modalEl.addEventListener('hidden.bs.modal', () => {
        closeLightbox();
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
        document.querySelectorAll('.modal-backdrop').forEach((el) => el.remove());
    });

    const mainImg = modalEl.querySelector('[data-vitrine-main]');
    const mainBtn = modalEl.querySelector('[data-vitrine-main-btn]');
    const placeholder = modalEl.querySelector('[data-vitrine-placeholder]');
    const thumbs = modalEl.querySelector('[data-vitrine-thumbs]');
    const titleEl = modalEl.querySelector('[data-vitrine-title]');
    const modalTitle = modalEl.querySelector('[data-vitrine-modal-title]');
    const skuEl = modalEl.querySelector('[data-vitrine-sku]');
    const descEl = modalEl.querySelector('[data-vitrine-desc]');
    const priceEl = modalEl.querySelector('[data-vitrine-price]');
    const reserveEl = modalEl.querySelector('[data-vitrine-reserve]');
    const stockEl = modalEl.querySelector('[data-vitrine-stock]');
    const badgeEl = modalEl.querySelector('[data-vitrine-badge]');
    const form = modalEl.querySelector('[data-vitrine-form]');
    const idInput = modalEl.querySelector('[data-vitrine-id]');
    const qtyInput = modalEl.querySelector('[data-vitrine-qty]');
    const soldOut = modalEl.querySelector('[data-vitrine-soldout]');

    const setMainImage = (src) => {
        if (!mainImg || !placeholder) {
            return;
        }

        if (!src) {
            mainImg.hidden = true;
            mainImg.removeAttribute('src');
            placeholder.style.display = '';
            return;
        }

        mainImg.src = src;
        mainImg.hidden = false;
        placeholder.style.display = 'none';
    };

    if (mainBtn) {
        mainBtn.addEventListener('click', () => {
            if (mainImg && !mainImg.hidden && mainImg.src) {
                openLightbox(mainImg.src);
            }
        });
    }

    const renderThumbs = (imagens) => {
        if (!thumbs) {
            return;
        }

        thumbs.innerHTML = '';

        imagens.forEach((imagem, index) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'vitrine-thumb' + (index === 0 ? ' is-active' : '');
            button.setAttribute('aria-label', 'Ver foto ' + (index + 1));

            const img = document.createElement('img');
            img.src = imagem.url;
            img.alt = '';
            img.loading = 'lazy';

            button.appendChild(img);
            button.addEventListener('click', () => {
                thumbs.querySelectorAll('.vitrine-thumb').forEach((el) => el.classList.remove('is-active'));
                button.classList.add('is-active');
                setMainImage(imagem.url);
                openLightbox(imagem.url);
            });

            thumbs.appendChild(button);
        });
    };

    let opening = false;

    const openItem = (url) => {
        if (!url || opening || modalEl.classList.contains('show')) {
            return;
        }

        opening = true;
        closeLightbox();
        modal.show();

        if (titleEl) {
            titleEl.textContent = 'Carregando...';
        }

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then((response) => response.json())
            .then((data) => {
                if (data.error) {
                    if (titleEl) {
                        titleEl.textContent = data.error;
                    }

                    return;
                }

                if (titleEl) {
                    titleEl.textContent = data.titulo || '';
                }

                if (modalTitle) {
                    modalTitle.textContent = data.titulo || 'Detalhes do item';
                }

                if (skuEl) {
                    skuEl.textContent = data.codigo ? 'SKU: ' + data.codigo : '';
                }

                if (descEl) {
                    descEl.textContent = data.descricao || '';
                }

                if (priceEl) {
                    priceEl.textContent = data.preco || '';
                }

                if (reserveEl) {
                    reserveEl.textContent = data.preco_reserva ? 'Reserva: ' + data.preco_reserva : '';
                }

                if (badgeEl) {
                    badgeEl.hidden = !data.pre_venda;
                }

                const disponivel = Number(data.disponivel || 0);

                if (stockEl) {
                    stockEl.textContent = disponivel > 0
                        ? disponivel + ' un. disponível(is)'
                        : 'Esgotado';
                }

                if (idInput) {
                    idInput.value = String(data.id || '');
                }

                if (qtyInput) {
                    qtyInput.max = String(Math.max(1, disponivel));
                    qtyInput.value = '1';
                }

                if (form) {
                    form.hidden = disponivel <= 0;
                }

                if (soldOut) {
                    soldOut.hidden = disponivel > 0;
                }

                const imagens = Array.isArray(data.imagens) ? data.imagens : [];
                const principal = imagens.find((imagem) => imagem.principal) || imagens[0];

                setMainImage(principal ? principal.url : null);
                renderThumbs(imagens);
            })
            .catch(() => {
                if (titleEl) {
                    titleEl.textContent = 'Não foi possível carregar o item.';
                }
            })
            .finally(() => {
                opening = false;
            });
    };

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-vitrine-item]');

        if (!trigger) {
            return;
        }

        if (event.target.closest('form') || event.target.closest('a')) {
            return;
        }

        event.preventDefault();
        closeLightbox();
        openItem(trigger.getAttribute('data-vitrine-item-url'));
    });
});
