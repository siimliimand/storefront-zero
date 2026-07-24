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

    // Toggle search results visibility based on content.
    if (evt.detail.target.id === 'search-results') {
        var hasContent = evt.detail.target.innerHTML.trim() !== '';
        evt.detail.target.classList.toggle('hidden', !hasContent);
    }
});

/**
 * Web Component registration confirmation
 * Logs registered components on load for debugging.
 * Components self-register via customElements.define() in their own files.
 */
document.addEventListener('DOMContentLoaded', function() {
    const components = document.querySelectorAll('mobile-drawer');
    if (components.length > 0) {
        console.log('[Storefront Zero] Web components initialized:',
            Array.from(components).map(el => el.tagName.toLowerCase()).join(', '));
    }
});

/**
 * Search dropdown accessibility.
 * - Escape key closes the dropdown and returns focus to the search input.
 * - Clicking outside the search area dismisses the dropdown.
 */
(function () {
    var searchInput = document.querySelector('input[name="s"]');
    var searchResults = document.getElementById('search-results');

    if (!searchInput || !searchResults) return;

    document.addEventListener('keydown', function (evt) {
        if (evt.key === 'Escape' && searchResults.innerHTML.trim() !== '') {
            searchResults.classList.add('hidden');
            searchInput.focus();
        }
    });

    document.addEventListener('click', function (evt) {
        if (!searchInput.contains(evt.target) && !searchResults.contains(evt.target)) {
            searchResults.classList.add('hidden');
        }
    });
})();
