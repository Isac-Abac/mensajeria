(function () {
    const root = document.documentElement;
    const buttons = document.querySelectorAll('[data-theme-toggle]');

    function currentTheme() {
        return root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
    }

    function applyTheme(theme) {
        const next = theme === 'dark' ? 'dark' : 'light';
        root.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);

        buttons.forEach((btn) => {
            btn.textContent = next === 'dark' ? 'Cambiar a claro' : 'Cambiar a oscuro';
        });
    }

    applyTheme(localStorage.getItem('theme') || currentTheme());

    buttons.forEach((btn) => {
        btn.addEventListener('click', () => {
            applyTheme(currentTheme() === 'dark' ? 'light' : 'dark');
        });
    });
})();
