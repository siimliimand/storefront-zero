<?php
/**
 * WooCommerce Cart Template
 *
 * Storefront Zero theme override for woocommerce/cart/cart.php
 *
 * @package Storefront_Zero
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_cart_table' );
?>

<form class="woocommerce-cart-form" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
    <?php do_action( 'woocommerce_before_cart_table' ); ?>

    <table class="shop_table shop_table_responsive cart woocommerce-cart-form__contents min-w-full" cellspacing="0">
        <thead>
            <tr class="border-b border-gray-200 text-left text-sm font-medium text-gray-600">
                <th class="product-remove p-3">&nbsp;</th>
                <th class="product-name p-3"><?php esc_html_e( 'Product', 'storefront-zero' ); ?></th>
                <th class="product-price p-3"><?php esc_html_e( 'Price', 'storefront-zero' ); ?></th>
                <th class="product-quantity p-3"><?php esc_html_e( 'Quantity', 'storefront-zero' ); ?></th>
                <th class="product-subtotal p-3"><?php esc_html_e( 'Subtotal', 'storefront-zero' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php do_action( 'woocommerce_before_cart_contents' ); ?>

            <?php
            foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
                $_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
                $product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

                if ( ! $_product ) {
                    continue;
                }

                $item_downloadable = $_product->is_downloadable() && $_product->has_file();
                $item_visible       = apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key );

                if ( ! $item_visible ) {
                    continue;
                }

                $product_name = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key );
                $thumbnail    = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key );
                $product_url  = apply_filters( 'woocommerce_cart_item_permalink', $_product->get_permalink(), $cart_item, $cart_item_key );
                $product_price = apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key );
                $product_quantity = apply_filters( 'woocommerce_cart_item_quantity', woocommerce_quantity_input( [
                    'input_name'  => "cart[{$cart_item_key}][qty]",
                    'input_value' => $cart_item['quantity'],
                    'max_text'    => sprintf( esc_html__( 'Max: %s', 'storefront-zero' ), $_product->get_max_purchase_quantity() ),
                    'min_value'   => 0,
                    'max_value'   => $_product->get_max_purchase_quantity(),
                ], $_product, false ), $cart_item, $cart_item_key );
            ?>
                <tr class="woocommerce-cart-form__cart-item cart_item border-b border-gray-100">
                    <td class="product-remove p-3 text-center">
                        <?php
                        echo apply_filters( 'woocommerce_cart_item_remove_link', sprintf(
                            '<a href="%s" class="remove text-red-500 hover:text-red-700" aria-label="%s" data-product_id="%s" data-cart_item_key="%s">&times;</a>',
                            esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
                            esc_html__( 'Remove this item', 'storefront-zero' ),
                            esc_attr( $product_id ),
                            esc_attr( $cart_item_key )
                        ), $cart_item_key );
                        ?>
                    </td>

                    <td class="product-name p-3" data-title="<?php esc_attr_e( 'Product', 'storefront-zero' ); ?>">
                        <?php
                        $thumbnail_display = $thumbnail;
                        if ( $product_url ) {
                            $thumbnail_display = sprintf( '<a href="%s">%s</a>', esc_url( $product_url ), $thumbnail );
                        }
                        echo wp_kses_post( $thumbnail_display );
                        echo '<span class="ml-2">' . wp_kses_post( $product_name ) . '</span>';
                        do_action( 'woocommerce_after_cart_item_name', $cart_item, $cart_item_key );
                        ?>
                    </td>

                    <td class="product-price p-3" data-title="<?php esc_attr_e( 'Price', 'storefront-zero' ); ?>">
                        <?php echo wp_kses_post( $product_price ); ?>
                    </td>

                    <td class="product-quantity p-3" data-title="<?php esc_attr_e( 'Quantity', 'storefront-zero' ); ?>">
                        <?php echo wp_kses_post( $product_quantity ); ?>
                    </td>

                    <td class="product-subtotal p-3 text-right font-semibold" data-title="<?php esc_attr_e( 'Subtotal', 'storefront-zero' ); ?>">
                        <?php echo wp_kses_post( WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ) ); ?>
                    </td>
                </tr>
            <?php
            }
            do_action( 'woocommerce_cart_contents' );
            ?>
        </tbody>
    </table>

    <?php do_action( 'woocommerce_after_cart_table' ); ?>

    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mt-6 gap-4">
        <div class="coupon">
            <?php if ( wc_coupons_enabled() ) : ?>
                <label for="coupon_code" class="block text-sm font-medium text-gray-700 mb-1"><?php esc_html_e( 'Coupon:', 'storefront-zero' ); ?></label>
                <div class="flex gap-2">
                    <input type="text" name="coupon_code" class="input-text border border-gray-300 rounded px-3 py-2 text-sm w-48" id="coupon_code" value="" placeholder="<?php esc_attr_e( 'Coupon code', 'storefront-zero' ); ?>" />
                    <button type="submit" class="button wp-element-button bg-gray-100 hover:bg-gray-200 border border-gray-300 rounded px-4 py-2 text-sm" name="apply_coupon" value="<?php esc_attr_e( 'Apply coupon', 'storefront-zero' ); ?>"><?php esc_html_e( 'Apply coupon', 'storefront-zero' ); ?></button>
                </div>
                <?php do_action( 'woocommerce_cart_coupon' ); ?>
            <?php endif; ?>
        </div>

        <button type="submit" class="button wp-element-button bg-blue-600 hover:bg-blue-700 text-white rounded px-6 py-2 text-sm font-medium" name="update_cart" value="<?php esc_attr_e( 'Update cart', 'storefront-zero' ); ?>"><?php esc_html_e( 'Update cart', 'storefront-zero' ); ?></button>
    </div>

    <?php do_action( 'woocommerce_before_cart_collaterals' ); ?>

    <div class="cart-collaterals mt-8">
        <?php
        do_action( 'woocommerce_cart_collaterals' );
        ?>
    </div>
</form>

<?php do_action( 'woocommerce_after_cart_form' ); ?>
