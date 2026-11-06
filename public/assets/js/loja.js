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
        let ticking = false;
        let lastY = window.scrollY || document.documentElement.scrollTop || 0;

        const update = (useDirection = true) => {
            const y = window.scrollY || document.documentElement.scrollTop || 0;
            const goingUp = useDirection && y < lastY;
            const goingDown = useDirection && y > lastY;

            stickyTops.forEach((stickyTop) => {
                if (!media.matches) {
                    stickyTop.classList.remove('is-compact');
                    return;
                }

                if (y <= 40 || goingUp) {
                    stickyTop.classList.remove('is-compact');
                } else if (useDirection) {
                    if (goingDown && y > 120) {
                        stickyTop.classList.add('is-compact');
                    }
                } else {
                    stickyTop.classList.toggle('is-compact', y >= 120);
                }
            });

            lastY = y;
            ticking = false;
        };

        const onScroll = () => {
            if (ticking) {
                return;
            }

            ticking = true;
            window.requestAnimationFrame(() => update());
        };

        const onResize = () => update(false);

        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onResize);

        if (typeof media.addEventListener === 'function') {
            media.addEventListener('change', onResize);
        } else {
            media.addListener(onResize);
        }

        update(false);
    }

    if (typeof bootstrap !== 'undefined') {
        document.querySelectorAll('.modal[data-auto-open="1"]').forEach((modal) => {
            bootstrap.Modal.getOrCreateInstance(modal).show();
        });
    }
});
