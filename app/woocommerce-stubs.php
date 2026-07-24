<?php
/**
 * PHPStan stubs for WooCommerce classes used by the theme.
 *
 * WooCommerce provides these at runtime via the plugin. This file lets
 * PHPStan resolve the types during static analysis without requiring a
 * full WooCommerce installation in the theme's vendor directory.
 */

declare(strict_types=1);

if ( ! class_exists( 'WC_Product', false ) ) {
    /**
     * Stub for WC_Product.
     *
     * @see https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/AbstractWcProduct.php
     */
    class WC_Product
    {
        public function get_id(): int { return 0; }
        public function get_name(): string { return ''; }
        public function get_image_id(): int|string { return ''; }
        public function get_price_html(): string { return ''; }
    }
}

if ( ! class_exists( 'WC_Cart', false ) ) {
    /**
     * Stub for WC_Cart.
     *
     * @see https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/includes/class-wc-cart.php
     */
    class WC_Cart
    {
        public function add_to_cart( int $product_id, int $quantity = 1 ): bool { return false; }
        public function remove_cart_item( string $cart_item_key ): bool { return false; }
        public function set_quantity( string $cart_item_key, int $quantity = 1 ): bool { return false; }
        public function get_cart_contents_count(): int { return 0; }
        public function get_cart_subtotal(): string { return ''; }
        public function get_product_subtotal( \WC_Product $product, int $quantity ): string { return ''; }
        /** @return array<int, array{key: string, quantity: int, data: \WC_Product}> */
        public function get_cart(): array { return []; }
    }
}
