// Flag di modulo: il document listener viene registrato una volta sola,
// anche se turbo:load + DOMContentLoaded sparano entrambi sulla stessa pagina.
let _navListenerRegistered = false;

const SVG_MENU = '<svg class="icon icon-nav" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="18" y2="18"/></svg>';
const SVG_X    = '<svg class="icon icon-nav" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>';

function getNavEls() {
    return {
        btn:  document.getElementById('navbar-hamburger'),
        menu: document.getElementById('navbar-menu'),
    };
}

function closeMenu() {
    const { btn, menu } = getNavEls();
    if (!menu) return;
    menu.classList.remove('open');
    if (btn) {
        btn.innerHTML = SVG_MENU;
        btn.setAttribute('aria-expanded', 'false');
    }
}

function initNavbar() {
    if (_navListenerRegistered) return;
    _navListenerRegistered = true;

    document.addEventListener('click', e => {
        const { btn, menu } = getNavEls();
        if (!btn || !menu) return;

        // Click sull'hamburger → toggle menu
        if (e.target.closest('#navbar-hamburger')) {
            e.stopPropagation();
            const opening = menu.classList.toggle('open');
            btn.setAttribute('aria-expanded', String(opening));
            btn.innerHTML = opening ? SVG_X : SVG_MENU;
            return;
        }

        // Click su un link dentro il menu → chiudi
        if (e.target.closest('#navbar-menu .nav-link')) {
            closeMenu();
            return;
        }

        // Click fuori dalla navbar → chiudi se aperto
        if (menu.classList.contains('open') && !e.target.closest('.navbar')) {
            closeMenu();
        }
    });
}

document.addEventListener('DOMContentLoaded', initNavbar);
document.addEventListener('turbo:load', initNavbar);