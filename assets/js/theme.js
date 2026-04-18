// ============================================================
// Script de tema global (claro/oscuro)
// ============================================================
(function () {
    // Referencias base
    const root = document.documentElement;
    const buttons = document.querySelectorAll('[data-theme-toggle]');

    // Obtener tema actual del atributo data-theme
    function currentTheme() {
        return root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
    }

    // Aplicar tema y persistir en localStorage
    function applyTheme(theme) {
        const next = theme === 'dark' ? 'dark' : 'light';
        root.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);

        // Actualizar etiqueta del boton
        buttons.forEach((btn) => {
            btn.textContent = next === 'dark' ? 'Cambiar a claro' : 'Cambiar a oscuro';
        });
    }

    // Cargar tema inicial guardado
    applyTheme(localStorage.getItem('theme') || currentTheme());

    // Alternar tema al hacer clic
    buttons.forEach((btn) => {
        btn.addEventListener('click', () => {
            applyTheme(currentTheme() === 'dark' ? 'light' : 'dark');
        });
    });
})();
