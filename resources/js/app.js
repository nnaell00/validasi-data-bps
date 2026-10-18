document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
    toggle.addEventListener('click', () => {
        const password = document.getElementById(toggle.getAttribute('aria-controls'));

        if (!(password instanceof HTMLInputElement)) {
            return;
        }

        const showPassword = password.type === 'password';
        password.type = showPassword ? 'text' : 'password';
        toggle.textContent = showPassword ? 'Sembunyikan' : 'Tampilkan';
        toggle.setAttribute('aria-pressed', String(showPassword));
    });
});
