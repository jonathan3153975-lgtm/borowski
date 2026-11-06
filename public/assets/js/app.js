document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const targetId = button.getAttribute('data-password-toggle');
            const input = targetId ? document.getElementById(targetId) : null;

            if (!input) {
                return;
            }

            const shouldShow = input.type === 'password';
            input.type = shouldShow ? 'text' : 'password';
            button.setAttribute('aria-pressed', shouldShow ? 'true' : 'false');
            button.setAttribute('aria-label', shouldShow ? 'Ocultar senha' : 'Mostrar senha');

            const icon = button.querySelector('i');

            if (icon) {
                icon.classList.toggle('bi-eye', !shouldShow);
                icon.classList.toggle('bi-eye-slash', shouldShow);
            }
        });
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const message = event.submitter?.getAttribute('data-confirm')
            || form.getAttribute('data-confirm');

        if (message && !window.confirm(message)) {
            event.preventDefault();
            return;
        }

        if (!form.matches('[data-loading-form]')) {
            return;
        }

        const button = form.querySelector('[data-loading-button]');

        if (!button || button.disabled) {
            return;
        }

        button.disabled = true;

        const spinner = button.querySelector('.spinner-border');
        const label = button.querySelector('.button-label');

        if (spinner) {
            spinner.classList.remove('d-none');
        }

        if (label) {
            label.textContent = 'Entrando...';
        }
    });

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[data-confirm]');

        if (link && !window.confirm(link.getAttribute('data-confirm'))) {
            event.preventDefault();
        }
    });

    document.querySelectorAll('[data-image-preview]').forEach((input) => {
        input.addEventListener('change', () => {
            const target = document.getElementById(input.dataset.imagePreview || '');

            if (!target) {
                return;
            }

            (input._previewUrls || []).forEach((url) => URL.revokeObjectURL(url));

            const urls = [];
            target.innerHTML = '';

            Array.prototype.forEach.call(input.files || [], (file) => {
                if (!file.type || file.type.indexOf('image/') !== 0) {
                    return;
                }

                const url = URL.createObjectURL(file);
                urls.push(url);

                const wrapper = document.createElement('div');
                wrapper.className = 'image-preview-item';
                const image = document.createElement('img');
                image.src = url;
                image.alt = file.name;
                wrapper.appendChild(image);
                target.appendChild(wrapper);
            });

            input._previewUrls = urls;
        });
    });

    if (typeof bootstrap !== 'undefined') {
        window.setTimeout(() => {
            document.querySelectorAll('.alert-dismissible').forEach((alert) => {
                bootstrap.Alert.getOrCreateInstance(alert).close();
            });
        }, 7000);
    }
});
