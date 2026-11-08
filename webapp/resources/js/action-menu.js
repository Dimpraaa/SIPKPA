export function initActionMenus(root = document, browser = window) {
    function closeMenus(except = null) {
        root.querySelectorAll('[data-action-menu][open]').forEach((menu) => {
            if (menu !== except) menu.open = false;
        });
    }

    root.addEventListener('toggle', (event) => {
        const menu = event.target;
        if (!menu.matches?.('[data-action-menu]')) return;
        if (!menu.open) {
            menu.removeAttribute('data-positioned');
            return;
        }

        closeMenus(menu);
        menu.setAttribute('data-positioned', '');
        const trigger = menu.querySelector('summary').getBoundingClientRect();
        const panel = menu.querySelector('.action-menu-content').getBoundingClientRect();
        const top = trigger.bottom + panel.height + 12 > browser.innerHeight
            ? Math.max(8, trigger.top - panel.height - 6)
            : trigger.bottom + 6;
        const left = Math.max(8, Math.min(trigger.right - panel.width, browser.innerWidth - panel.width - 8));
        menu.style.setProperty('--action-menu-top', `${top}px`);
        menu.style.setProperty('--action-menu-left', `${left}px`);
    }, true);

    root.addEventListener('click', (event) => {
        const menu = event.target.closest?.('[data-action-menu]');
        closeMenus(menu);
        if (event.target.closest?.('.action-menu-content a')) closeMenus();
    });

    root.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        const menu = root.querySelector('[data-action-menu][open]');
        if (!menu) return;
        event.preventDefault();
        menu.open = false;
        menu.querySelector('summary').focus({ preventScroll: true });
    });

    root.addEventListener('focusin', (event) => closeMenus(event.target.closest?.('[data-action-menu]')));
    root.addEventListener('scroll', () => closeMenus(), true);
    browser.addEventListener('resize', () => closeMenus());
}
