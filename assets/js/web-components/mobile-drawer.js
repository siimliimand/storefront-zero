/**
 * <mobile-drawer> Web Component
 *
 * Toggles a mobile navigation drawer. Uses Light DOM so Tailwind classes
 * apply natively without Shadow DOM style injection.
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

        if (this._toggle && this._menu) {
            this._handleToggle = this._handleToggle.bind(this);
            this._toggle.addEventListener('click', this._handleToggle);
        }
    }

    disconnectedCallback() {
        if (this._toggle && this._handleToggle) {
            this._toggle.removeEventListener('click', this._handleToggle);
            this._handleToggle = null;
        }
    }

    _handleToggle() {
        this._menu.classList.toggle('hidden');
    }
}

// Register the custom element
if (!customElements.get('mobile-drawer')) {
    customElements.define('mobile-drawer', MobileDrawer);
}
