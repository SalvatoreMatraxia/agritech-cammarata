let _sidebarListenerRegistered = false;

function closeSidebar() {
    const sidebar = document.getElementById('app-sidebar');
    if (sidebar) sidebar.classList.remove('open');
}

function initSidebar() {
    if (_sidebarListenerRegistered) return;
    _sidebarListenerRegistered = true;

    document.addEventListener('click', e => {
        const sidebar = document.getElementById('app-sidebar');
        if (!sidebar) return;

        if (e.target.closest('#sidebar-hamburger')) {
            e.stopPropagation();
            const btn = document.getElementById('sidebar-hamburger');
            const opening = !sidebar.classList.contains('open');
            sidebar.classList.toggle('open', opening);
            if (btn) btn.setAttribute('aria-expanded', String(opening));
            return;
        }

        if (e.target.closest('#sidebar-overlay')) {
            closeSidebar();
            return;
        }

        if (e.target.closest('.app-sidebar-link') || e.target.closest('.app-sidebar-logout')) {
            closeSidebar();
        }
    });
}

document.addEventListener('DOMContentLoaded', initSidebar);
document.addEventListener('turbo:load', initSidebar);