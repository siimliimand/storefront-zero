/**
 * Storefront Zero - HTMX Configuration
 * Injects WP nonce into all HTMX requests for Flight PHP middleware verification.
 */
document.addEventListener('htmx:configRequest', function(evt) {
    evt.detail.headers['X-WP-NONCE'] = window.ThemeSettings?.nonce || '';
});

/**
 * Post-swap handler: dispatches custom event, re-inits WooCommerce,
 * and toggles search results.
 * Other scripts can listen: window.addEventListener('theme:dom-updated', handler)
 */
document.addEventListener('htmx:afterSwap', function(evt) {
    // Dispatch custom event for downstream listeners.
    window.dispatchEvent(new CustomEvent('theme:dom-updated', {
        detail: {
            target: evt.detail.target,
            source: evt.detail.requestConfig?.path || ''
        }
    }));

    // Re-initialize WooCommerce scripts on swapped content
    if (window.jQuery && typeof window.jQuery.fn.wc_variation_form === 'function') {
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
 * HTMX showToast event — native HX-Trigger header dispatch.
 * Server sends: HX-Trigger: {"showToast": {"message": "...", "type": "success"}}
 * HTMX automatically fires this custom event on document.body.
 * Supports single toast or array payloads.
 */
document.body.addEventListener('showToast', function(evt) {
    var toasts = Array.isArray(evt.detail) ? evt.detail : [evt.detail];
    toasts.forEach(function(toastData) {
        var toast = document.createElement('toast-notification');
        toast.setAttribute('message', toastData.message || '');
        toast.setAttribute('type', toastData.type || 'success');
        document.body.appendChild(toast);
    });
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
 * Search dropdown accessibility — ARIA combobox pattern.
 * - Manages aria-expanded, aria-activedescendant, and option highlighting.
 * - ArrowDown/Up navigate results, Enter activates, Escape closes.
 */
(function () {
    var searchInput = document.querySelector('input[name="s"]');
    var searchResults = document.getElementById('search-results');

    if (!searchInput || !searchResults) return;

    var _activeIndex = -1;

    /**
     * Add role="option" and unique IDs to each result child.
     */
    function _prepareOptions() {
        var items = searchResults.querySelectorAll(':scope > *');
        items.forEach(function (item, i) {
            item.setAttribute('role', 'option');
            item.id = 'search-result-' + i;
        });
    }

    /**
     * Highlight the active option and update aria-activedescendant.
     */
    function _highlightResult(index) {
        var options = searchResults.querySelectorAll('[role="option"]');
        options.forEach(function (opt) {
            opt.removeAttribute('aria-selected');
            opt.classList.remove('bg-gray-100', 'dark:bg-gray-700');
        });

        if (index >= 0 && index < options.length) {
            var active = options[index];
            active.setAttribute('aria-selected', 'true');
            active.classList.add('bg-gray-100', 'dark:bg-gray-700');
            searchInput.setAttribute('aria-activedescendant', active.id);
            active.scrollIntoView({ block: 'nearest' });
        } else {
            searchInput.setAttribute('aria-activedescendant', '');
        }
    }

    /**
     * Close the dropdown and reset all ARIA state.
     */
    function _closeDropdown() {
        searchResults.classList.add('hidden');
        searchInput.setAttribute('aria-expanded', 'false');
        searchInput.setAttribute('aria-activedescendant', '');
        _activeIndex = -1;
    }

    // After HTMX swaps search results, set up ARIA combobox state.
    document.addEventListener('htmx:afterSwap', function (evt) {
        if (evt.detail.target.id !== 'search-results') return;

        var hasContent = searchResults.innerHTML.trim() !== '';
        _activeIndex = -1;
        searchInput.setAttribute('aria-activedescendant', '');

        if (hasContent) {
            _prepareOptions();
            searchInput.setAttribute('aria-expanded', 'true');
        } else {
            searchInput.setAttribute('aria-expanded', 'false');
        }
    });

    // Keyboard navigation on the search input.
    searchInput.addEventListener('keydown', function (evt) {
        var options = searchResults.querySelectorAll('[role="option"]');
        var count = options.length;

        switch (evt.key) {
            case 'ArrowDown':
                if (count === 0) break;
                evt.preventDefault();
                _activeIndex = Math.min(_activeIndex + 1, count - 1);
                _highlightResult(_activeIndex);
                break;

            case 'ArrowUp':
                if (count === 0) break;
                evt.preventDefault();
                _activeIndex = Math.max(_activeIndex - 1, 0);
                _highlightResult(_activeIndex);
                break;

            case 'Enter':
                if (_activeIndex >= 0 && _activeIndex < count) {
                    evt.preventDefault();
                    var link = options[_activeIndex].querySelector('a');
                    if (link) link.click();
                }
                break;

            case 'Escape':
                evt.preventDefault();
                _closeDropdown();
                searchInput.focus();
                break;
        }
    });

    // Close dropdown when clicking outside.
    document.addEventListener('click', function (evt) {
        if (!searchInput.contains(evt.target) && !searchResults.contains(evt.target)) {
            _closeDropdown();
        }
    });
})();
