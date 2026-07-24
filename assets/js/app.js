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
 * Nonce auto-retry on 403 responses.
 * When a full-page cache serves a stale nonce, fetch a fresh one and retry.
 */
document.addEventListener('htmx:responseError', function(evt) {
    if (evt.detail.xhr.status !== 403) return;

    // Prevent infinite retry loops — only retry once per request.
    if (evt.detail.requestConfig?.szRetrying) return;

    var path = (window.ThemeSettings?.endpoint || '/htmx-api') + '/nonce?_t=' + Date.now();

    fetch(path)
        .then(function(response) {
            if (!response.ok) throw new Error('Nonce refresh failed');
            return response.json();
        })
        .then(function(data) {
            if (data.nonce) {
                window.ThemeSettings.nonce = data.nonce;

                // Mark this request as already retried, then re-issue it.
                evt.detail.requestConfig.szRetrying = true;
                evt.detail.requestConfig.headers['X-WP-NONCE'] = data.nonce;
                htmx.ajax(evt.detail.requestConfig.verb, evt.detail.requestConfig.path, {
                    source: evt.detail.requestConfig.target,
                    event: evt.detail.requestConfig.event,
                    values: evt.detail.requestConfig.values,
                    headers: { 'X-WP-NONCE': data.nonce }
                });
            }
        })
        .catch(function(err) {
            console.error('[Storefront Zero] Nonce auto-retry failed:', err);
        });
});

/**
 * Toast notification integration with HTMX responses.
 * Parses HX-Trigger headers for showToast events and injects toast elements.
 */
document.addEventListener('htmx:afterSwap', function(evt) {
    var triggerHeader = evt.detail.xhr?.getResponseHeader('HX-Trigger');
    if (!triggerHeader) return;

    try {
        var triggers = JSON.parse(triggerHeader);
        if (triggers.showToast) {
            var toast = document.createElement('toast-notification');
            toast.setAttribute('message', triggers.showToast.message || '');
            toast.setAttribute('type', triggers.showToast.type || 'success');
            document.body.appendChild(toast);
        }
    } catch (e) {
        // HX-Trigger header is not JSON — ignore.
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
