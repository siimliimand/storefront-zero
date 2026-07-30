/**
 * <dark-mode-toggle> Web Component
 *
 * Toggle between light and dark mode. Persists preference to
 * localStorage('sz-dark-mode'). Falls back to prefers-color-scheme
 * when no stored preference exists. Applies/removes the `dark` class
 * on <html> so Tailwind's darkMode: 'class' works.
 *
 * Uses Light DOM so Tailwind classes apply natively.
 *
 * Usage:
 *   <dark-mode-toggle></dark-mode-toggle>
 */
class DarkModeToggle extends HTMLElement {
    STORAGE_KEY = 'sz-dark-mode';

    connectedCallback() {
        this._isDark = this._resolveInitialMode();

        // Apply initial class before first paint.
        document.documentElement.classList.toggle('dark', this._isDark);

        this._render();
        this._handleClick = this._handleClick.bind(this);
        this._handleKeydown = this._handleKeydown.bind(this);
        this.addEventListener('click', this._handleClick);
        this.addEventListener('keydown', this._handleKeydown);

        // Respect OS preference changes when user hasn't set an override.
        this._mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        this._handleMediaChange = this._handleMediaChange.bind(this);
        this._mediaQuery.addEventListener('change', this._handleMediaChange);
    }

    disconnectedCallback() {
        if (this._handleClick) {
            this.removeEventListener('click', this._handleClick);
        }
        if (this._handleKeydown) {
            this.removeEventListener('keydown', this._handleKeydown);
        }
        if (this._mediaQuery && this._handleMediaChange) {
            this._mediaQuery.removeEventListener(
                'change',
                this._handleMediaChange,
            );
        }
    }

    /**
     * Determine initial dark mode state:
     * 1. localStorage value ('dark' | 'light')
     * 2. OS preference via prefers-color-scheme
     */
    _resolveInitialMode() {
        const stored = localStorage.getItem(this.STORAGE_KEY);
        if (stored === 'dark') return true;
        if (stored === 'light') return false;
        return window.matchMedia('(prefers-color-scheme: dark)').matches;
    }

    _handleClick() {
        this._isDark = !this._isDark;
        document.documentElement.classList.toggle('dark', this._isDark);
        localStorage.setItem(
            this.STORAGE_KEY,
            this._isDark ? 'dark' : 'light',
        );
        this._render();
    }

    _handleKeydown(evt) {
        if (evt.key === 'Enter' || evt.key === ' ') {
            evt.preventDefault();
            this._handleClick();
        }
    }

    _handleMediaChange(evt) {
        // Only follow OS when user has never set an explicit preference.
        if (localStorage.getItem(this.STORAGE_KEY) !== null) return;
        this._isDark = evt.matches;
        document.documentElement.classList.toggle('dark', this._isDark);
        this._render();
    }

    _render() {
        // Light DOM — Tailwind utility classes only.
        this.setAttribute('role', 'button');
        this.setAttribute('aria-label', this._isDark ? 'Switch to light mode' : 'Switch to dark mode');
        this.setAttribute('tabindex', '0');

        this.className =
            'inline-flex items-center justify-center w-9 h-9 rounded-lg transition-colors duration-200 hover:bg-neutral-200 dark:hover:bg-neutral-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-neutral-500';

        // Sun icon (shown in dark mode — "click to go light").
        // Moon icon (shown in light mode — "click to go dark").
        this.innerHTML = this._isDark
            ? /* html */ `<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><path d="M12 1v2m0 18v2M4.22 4.22l1.42 1.42m12.72 12.72l1.42 1.42M1 12h2m18 0h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>`
            : /* html */ `<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-neutral-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3a7 7 0 0 0 9.79 9.79z"/></svg>`;
    }
}

if (!customElements.get('dark-mode-toggle')) {
    customElements.define('dark-mode-toggle', DarkModeToggle);
}
