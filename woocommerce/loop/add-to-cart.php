<?php
/**
 * WooCommerce Loop Add to Cart Template
 *
 * Storefront Zero theme override for woocommerce/loop/add-to-cart.php
 * Uses HTMX for add-to-cart without full page reload.
 *
 * @package Storefront_Zero
 *
 * @var WC_Product $product  Product object.
 * @var string     $link     Add to cart URL (not used — replaced by HTMX).
 */

defined( 'ABSPATH' ) || exit;
?>
<button type="button"
   class="button wp-element-button"
   hx-post="<?php echo esc_url( home_url( '/htmx-api/cart/add' ) ); ?>"
   hx-vals='{"product_id": "<?php echo esc_attr( $product->get_id() ); ?>", "quantity": "1"}'
   hx-target="#mini-cart-container"
   hx-swap="outerHTML"
   hx-indicator=".htmx-indicator"
   aria-label="<?php echo esc_attr( sprintf( __( 'Add "%s" to cart', 'storefront-zero' ), $product->get_name() ) ); ?>"
   data-product-id="<?php echo esc_attr( $product->get_id() ); ?>">
    <?php echo esc_html( $product->add_to_cart_text() ); ?>
</button>
