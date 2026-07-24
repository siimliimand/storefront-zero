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

    // Re-initialize WooCommerce scripts on swapped content
    if (window.jQuery) {
        var $ = window.jQuery;
        // Re-init variation forms
        $(evt.detail.target).find('.variations_form').wc_variation_form();
        $(evt.detail.target).filter('.variations_form').wc_variation_form();
    }
});

/**
 * Web Component registration confirmation
 * Logs registered components on load for debugging.
 * Components self-register via customElements.define() in their own files.
 */
document.addEventListener('DOMContentLoaded', function() {
    const components = document.querySelectorAll('mobile-drawer, cart-drawer, search-overlay');
    if (components.length > 0) {
        console.log('[Storefront Zero] Web components initialized:',
            Array.from(components).map(el => el.tagName.toLowerCase()).join(', '));
    }
});
