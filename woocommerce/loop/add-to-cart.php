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
<a href="<?php echo esc_url( $link ); ?>"
   class="button wp-element-button <?php echo esc_attr( wc_wp_class_single( $link ) ); ?>"
   hx-post="<?php echo esc_url( home_url( '/htmx-api/cart/add' ) ); ?>"
   hx-vals='{"product_id": "<?php echo esc_attr( $product->get_id() ); ?>", "quantity": "1"}'
   hx-target="#mini-cart"
   hx-swap="innerHTML"
   hx-indicator="#mini-cart"
   data-product-id="<?php echo esc_attr( $product->get_id() ); ?>">
    <?php echo esc_html( $product->add_to_cart_text() ); ?>
</a>
