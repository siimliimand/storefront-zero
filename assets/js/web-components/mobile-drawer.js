/**
 * <mobile-drawer> Web Component
 *
 * Toggles a mobile navigation drawer with full ARIA support.
 * Uses Light DOM so Tailwind classes apply natively.
 *
 * Usage:
 *   <mobile-drawer>
 *     <button data-drawer-toggle>Menu</button>
 *     <nav data-drawer-menu class="hidden">
 *       ... nav items ...
 *     </nav>
 *   </mobile-drawer>
 */
class MobileDrawer extends HTMLElement {
    connectedCallback() {
        this._toggle = this.querySelector('[data-drawer-toggle]');
        this._menu = this.querySelector('[data-drawer-menu]');
        this._isOpen = false;

        if (this._toggle && this._menu) {
            // Generate unique IDs for aria-controls.
            const menuId = 'drawer-menu-' + Math.random().toString(36).slice(2, 9);
            this._menu.setAttribute('id', menuId);
            this._toggle.setAttribute('aria-controls', menuId);
            this._toggle.setAttribute('aria-expanded', 'false');
            this._menu.setAttribute('role', 'menu');

            // Add role="menuitem" to links.
            this._menu.querySelectorAll('a').forEach(function (link) {
                link.setAttribute('role', 'menuitem');
            });

            this._handleToggle = this._handleToggle.bind(this);
            this._handleKeyDown = this._handleKeyDown.bind(this);
            this._toggle.addEventListener('click', this._handleToggle);
            document.addEventListener('keydown', this._handleKeyDown);
        }
    }

    disconnectedCallback() {
        if (this._toggle) {
            if (this._handleToggle) {
                this._toggle.removeEventListener('click', this._handleToggle);
            }
            if (this._handleKeyDown) {
                document.removeEventListener('keydown', this._handleKeyDown);
            }
        }
    }

    _handleToggle() {
        this._isOpen = !this._isOpen;
        this._menu.classList.toggle('hidden', !this._isOpen);
        this._toggle.setAttribute('aria-expanded', String(this._isOpen));

        if (this._isOpen) {
            var firstLink = this._menu.querySelector('a');
            if (firstLink) firstLink.focus();
        }
    }

    _handleKeyDown(evt) {
        if (evt.key === 'Escape' && this._isOpen) {
            this._isOpen = false;
            this._menu.classList.add('hidden');
            this._toggle.setAttribute('aria-expanded', 'false');
            this._toggle.focus();
        }
    }
}

if (!customElements.get('mobile-drawer')) {
    customElements.define('mobile-drawer', MobileDrawer);
}
