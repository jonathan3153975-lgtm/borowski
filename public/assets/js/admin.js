document.addEventListener('DOMContentLoaded', () => {
    const shell = document.getElementById('adminShell');
    const toggle = document.getElementById('sidebarToggle');
    const storageKey = 'lojaCarrinhosSidebarCollapsed';

    if (!shell || !toggle) {
        return;
    }

    const applyState = (collapsed) => {
        shell.classList.toggle('admin-shell--collapsed', collapsed);
        toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        toggle.setAttribute(
            'aria-label',
            collapsed ? 'Expandir menu lateral' : 'Recolher menu lateral'
        );
    };

    applyState(window.localStorage.getItem(storageKey) === '1');

    toggle.addEventListener('click', () => {
        const collapsed = !shell.classList.contains('admin-shell--collapsed');
        applyState(collapsed);
        window.localStorage.setItem(storageKey, collapsed ? '1' : '0');
    });
});
