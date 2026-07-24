/**
 * Storefront Zero - HTMX Configuration
 * Injects WP nonce into all HTMX requests for Flight PHP middleware verification.
 */
document.addEventListener('htmx:configRequest', function(evt) {
    evt.detail.headers['X-WP-NONCE'] = window.ThemeSettings?.nonce || '';
});

/**
 * Dispatch custom event after HTMX swap for downstream listeners.
 * Other scripts can listen: window.addEventListener('theme:dom-updated', handler)
 */
document.addEventListener('htmx:afterSwap', function(evt) {
    window.dispatchEvent(new CustomEvent('theme:dom-updated', {
        detail: {
            target: evt.detail.target,
            source: evt.detail.requestConfig?.path || ''
        }
    }));
});
