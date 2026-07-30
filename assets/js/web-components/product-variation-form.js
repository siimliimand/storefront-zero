/**
 * <product-variation-form> Web Component
 *
 * Bridges WooCommerce variable-product forms with HTMX add-to-cart.
 * Wraps a `.variations_form` so attribute selection updates the
 * hidden `variation_id` input and wires HTMX attributes on the
 * add-to-cart button.
 *
 * Uses Light DOM to match existing component conventions.
 *
 * Usage:
 *   <product-variation-form product-id="123">
 *     <!-- .variations_form with attribute selects and add-to-cart button -->
 *   </product-variation-form>
 */
class ProductVariationForm extends HTMLElement {
    connectedCallback() {
        this._productId = this.getAttribute('product-id') || '';
        this._variationId = '';

        // Find the WooCommerce variations form inside this component.
        this._form =
            this.querySelector('.variations_form') || this.querySelector('form.cart');

        if (!this._form) return;

        // Locate the add-to-cart button and quantity input.
        this._addToCartBtn =
            this._form.querySelector('.single_add_to_cart_button') ||
            this._form.querySelector('button[name="add-to-cart"]');
        this._qtyInput = this._form.querySelector('input[name="quantity"]');

        // Attach change listener on attribute selects.
        this._handleChange = this._handleChange.bind(this);
        this._form.addEventListener('change', this._handleChange);

        // Listen for WooCommerce's own variation_found event as a fallback.
        this._handleVariationFound = this._handleVariationFound.bind(this);
        this._form.addEventListener('wc_variation_form_variation_found', this._handleVariationFound);

        this._applyInitialState();
    }

    disconnectedCallback() {
        if (this._form) {
            if (this._handleChange) {
                this._form.removeEventListener('change', this._handleChange);
            }
            if (this._handleVariationFound) {
                this._form.removeEventListener(
                    'wc_variation_form_variation_found',
                    this._handleVariationFound,
                );
            }
        }
    }

    /**
     * Disable add-to-cart on initial load — no variation selected yet.
     */
    _applyInitialState() {
        if (!this._addToCartBtn) return;
        this._addToCartBtn.disabled = true;
        this._addToCartBtn.classList.add('opacity-50', 'cursor-not-allowed');
    }

    /**
     * Handle select change events on attribute dropdowns.
     * Reads all selected attributes and attempts to find a matching variation.
     */
    _handleChange() {
        const attributes = this._readAttributes();

        // All attributes must be selected before a variation can be resolved.
        const allSelected = Object.values(attributes).every(function (val) {
            return val !== '';
        });

        if (!allSelected) {
            this._clearVariation();
            return;
        }

        // Try to find matching variation from WooCommerce's variation data.
        const variation = this._findVariation(attributes);
        if (variation) {
            this._setVariation(variation.variation_id, attributes);
        } else {
            this._clearVariation();
        }
    }

    /**
     * Listen for WooCommerce's variation_found event.
     * This fires after wc_variation_form.js resolves a match.
     */
    _handleVariationFound(evt) {
        if (evt.detail && evt.detail.variation_id) {
            const attributes = this._readAttributes();
            this._setVariation(evt.detail.variation_id, attributes);
        }
    }

    /**
     * Read current attribute selections from all `.variations select` elements.
     * Returns e.g. { "attribute_color": "red", "attribute_size": "large" }.
     */
    _readAttributes() {
        const attributes = {};
        if (!this._form) return attributes;

        const selects = this._form.querySelectorAll('.variations select');
        selects.forEach(function (select) {
            const name = select.getAttribute('data-attribute_name') || select.name;
            attributes[name] = select.value || '';
        });

        return attributes;
    }

    /**
     * Search WooCommerce's variations JSON data for a matching combination.
     * WooCommerce stores variation data as `data-product_variations` on the form.
     */
    _findVariation(attributes) {
        let variationsJson = this._form.getAttribute('data-product_variations');
        if (!variationsJson) return null;

        let variations;
        try {
            variations = JSON.parse(variationsJson);
        } catch (e) {
            return null;
        }

        if (!Array.isArray(variations)) return null;

        return variations.find(function (variation) {
            if (!variation.attributes) return false;
            return Object.keys(attributes).every(function (key) {
                const selected = attributes[key];
                const option = variation.attributes[key] || '';
                return option === '' || option === selected;
            });
        });
    }

    /**
     * Update the hidden variation_id input and wire HTMX attributes.
     */
    _setVariation(variationId, attributes) {
        this._variationId = variationId;

        // Update hidden input if present.
        let hiddenInput = this._form.querySelector('input[name="variation_id"]');
        if (!hiddenInput) {
            hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'variation_id';
            this._form.appendChild(hiddenInput);
        }
        hiddenInput.value = variationId;

        // Enable the add-to-cart button.
        if (this._addToCartBtn) {
            this._addToCartBtn.disabled = false;
            this._addToCartBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            this._wireHtmx(variationId, attributes);
        }
    }

    /**
     * Clear the current variation and disable add-to-cart.
     */
    _clearVariation() {
        this._variationId = '';

        const hiddenInput = this._form.querySelector('input[name="variation_id"]');
        if (hiddenInput) hiddenInput.value = '';

        if (this._addToCartBtn) {
            this._addToCartBtn.disabled = true;
            this._addToCartBtn.classList.add('opacity-50', 'cursor-not-allowed');
        }
    }

    /**
     * Attach HTMX attributes to the add-to-cart button so it posts
     * to /htmx-api/cart/add with the correct payload.
     */
    _wireHtmx(variationId, attributes) {
        if (!this._addToCartBtn) return;

        const qty = this._qtyInput ? this._qtyInput.value : '1';
        const endpoint =
            (window.ThemeSettings && window.ThemeSettings.endpoint) ||
            '/htmx-api';

        const hxValues = Object.assign(
            {},
            { product_id: this._productId, variation_id: variationId, quantity: qty },
            attributes,
        );

        this._addToCartBtn.setAttribute('hx-post', endpoint + '/cart/add');
        this._addToCartBtn.setAttribute('hx-vals', JSON.stringify(hxValues));
        this._addToCartBtn.setAttribute('hx-target', '#mini-cart-container');
        this._addToCartBtn.setAttribute('hx-swap', 'innerHTML');
        this._addToCartBtn.setAttribute('hx-indicator', '#mini-cart-container');

        // Tell HTMX to process the element so the dynamically-set
        // hx-* attributes become active.
        if (typeof htmx !== 'undefined') {
            htmx.process(this._addToCartBtn);
        }
    }
}

if (!customElements.get('product-variation-form')) {
    customElements.define('product-variation-form', ProductVariationForm);
}
