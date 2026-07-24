<?php
/**
 * View template for mini-cart fragment.
 *
 * Uses WC()->cart directly — no variables passed via extract().
 *
 * @package Storefront_Zero
 */

$cart       = WC()->cart;
$item_count = $cart->get_cart_contents_count();
$subtotal   = $cart->get_cart_subtotal();
?>
<div class="mini-cart" id="mini-cart">
    <div class="mini-cart-header flex justify-between items-center p-3 border-b">
        <span class="font-semibold"><?php esc_html_e( 'Shopping Cart', 'storefront-zero' ); ?></span>
        <span class="text-sm text-gray-600"><?php echo esc_html( sprintf( _n( '%s item', '%s items', $item_count, 'storefront-zero' ), $item_count ) ); ?></span>
    </div>

    <?php if ( 0 === $item_count ) : ?>
        <div class="mini-cart-empty p-4 text-center text-gray-500">
            <p><?php esc_html_e( 'Your cart is empty.', 'storefront-zero' ); ?></p>
        </div>
    <?php else : ?>
        <div class="mini-cart-items p-3">
            <?php foreach ( $cart->get_cart() as $cart_item ) :
                $product      = $cart_item['data'];
                $product_name = $product->get_name();
                $qty          = $cart_item['quantity'];
                $line_total   = WC()->cart->get_product_subtotal( $product, $qty );
            ?>
                <div class="mini-cart-item flex justify-between items-center py-2 border-b last:border-0">
                    <div>
                        <span class="block text-sm font-medium"><?php echo esc_html( $product_name ); ?></span>
                        <span class="block text-xs text-gray-500">Qty: <?php echo esc_html( $qty ); ?></span>
                    </div>
                    <span class="text-sm"><?php echo wp_kses_post( $line_total ); ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mini-cart-footer p-3 border-t bg-gray-50">
            <div class="flex justify-between items-center mb-3">
                <span class="font-semibold"><?php esc_html_e( 'Subtotal:', 'storefront-zero' ); ?></span>
                <span class="font-semibold"><?php echo wp_kses_post( $subtotal ); ?></span>
            </div>
            <a href="<?php echo esc_url( wc_get_cart_url() ); ?>"
               class="block w-full text-center bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition">
                <?php esc_html_e( 'View Cart', 'storefront-zero' ); ?>
            </a>
        </div>
    <?php endif; ?>
</div>
