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

    if (typeof bootstrap !== 'undefined') {
        document.querySelectorAll('.modal[data-auto-open="1"]').forEach((modal) => {
            bootstrap.Modal.getOrCreateInstance(modal).show();
        });
    }
});
