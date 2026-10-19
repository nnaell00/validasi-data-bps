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

const appShell = document.getElementById('app-shell');
const mobileSidebarOpen = document.getElementById('mobile-sidebar-open');
const mobileSidebarClose = document.getElementById('mobile-sidebar-close');
const desktopSidebarToggle = document.getElementById('desktop-sidebar-toggle');
const sidebarBackdrop = document.getElementById('sidebar-backdrop');

if (appShell instanceof HTMLElement) {
    const closeMobileSidebar = (restoreFocus = false) => {
        appShell.classList.remove('sidebar-open');
        mobileSidebarOpen?.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('overflow-hidden');

        if (restoreFocus && mobileSidebarOpen instanceof HTMLButtonElement) {
            mobileSidebarOpen.focus();
        }
    };

    mobileSidebarOpen?.addEventListener('click', () => {
        appShell.classList.add('sidebar-open');
        mobileSidebarOpen.setAttribute('aria-expanded', 'true');
        document.body.classList.add('overflow-hidden');
        mobileSidebarClose?.focus();
    });

    mobileSidebarClose?.addEventListener('click', () => closeMobileSidebar(true));
    sidebarBackdrop?.addEventListener('click', () => closeMobileSidebar(true));

    appShell.querySelectorAll('#app-sidebar nav a').forEach((link) => {
        link.addEventListener('click', () => closeMobileSidebar());
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && appShell.classList.contains('sidebar-open')) {
            closeMobileSidebar(true);
        }
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth >= 1024) {
            closeMobileSidebar();
        }
    });

    desktopSidebarToggle?.addEventListener('click', () => {
        const isCollapsed = appShell.classList.toggle('sidebar-collapsed');
        desktopSidebarToggle.setAttribute('aria-expanded', String(!isCollapsed));
        desktopSidebarToggle.setAttribute('aria-label', isCollapsed ? 'Perluas sidebar' : 'Perkecil sidebar');
        desktopSidebarToggle.setAttribute('title', isCollapsed ? 'Perluas sidebar' : 'Perkecil sidebar');
    });
}
