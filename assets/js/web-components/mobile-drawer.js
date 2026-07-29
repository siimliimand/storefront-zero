/**
 * <mobile-drawer> Web Component
 *
 * Toggles a mobile navigation drawer. Relies on <nav> landmark
 * semantics for accessibility; toggle button uses aria-controls/expanded.
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
            const index = document.querySelectorAll('mobile-drawer').length;
            const menuId = 'drawer-menu-' + index;
            this._menu.setAttribute('id', menuId);
            this._toggle.setAttribute('aria-controls', menuId);
            this._toggle.setAttribute('aria-expanded', 'false');

            this._handleToggle = this._handleToggle.bind(this);
            this._handleKeyDown = this._handleKeyDown.bind(this);
            this._handleFocusTrap = this._handleFocusTrap.bind(this);
            this._toggle.addEventListener('click', this._handleToggle);
            document.addEventListener('keydown', this._handleKeyDown);
            document.addEventListener('keydown', this._handleFocusTrap);
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
            if (this._handleFocusTrap) {
                document.removeEventListener('keydown', this._handleFocusTrap);
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

    _handleFocusTrap(evt) {
        if (!this._isOpen || evt.key !== 'Tab') return;

        const focusableSelector =
            'a[href], button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])';
        const focusable = Array.from(this._menu.querySelectorAll(focusableSelector));
        if (focusable.length === 0) return;

        const first = focusable[0];
        const last = focusable[focusable.length - 1];

        if (evt.shiftKey) {
            if (document.activeElement === first) {
                evt.preventDefault();
                last.focus();
            }
        } else {
            if (document.activeElement === last) {
                evt.preventDefault();
                first.focus();
            }
        }
    }
}

if (!customElements.get('mobile-drawer')) {
    customElements.define('mobile-drawer', MobileDrawer);
}
