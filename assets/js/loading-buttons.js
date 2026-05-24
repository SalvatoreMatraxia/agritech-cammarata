let _loadingListenerRegistered = false;

function initLoadingButtons() {
    if (_loadingListenerRegistered) return;
    _loadingListenerRegistered = true;

    document.addEventListener('submit', (e) => {
        const form = e.target.closest('.predict-form');
        if (!form) return;

        const btn = form.querySelector('[type="submit"]');
        if (!btn || btn.disabled) return;

        btn.disabled = true;
        btn.dataset.originalText = btn.textContent;
        btn.textContent = 'Elaborazione...';
        btn.classList.add('btn-loading');
    });
}

document.addEventListener('DOMContentLoaded', initLoadingButtons);
document.addEventListener('turbo:load', initLoadingButtons);
