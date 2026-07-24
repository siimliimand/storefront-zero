<?php
/**
 * Cart Controller
 *
 * Handles cart-related HTMX fragment requests.
 *
 * @package Storefront_Zero
 */

namespace ThemeApp\Controllers;

class CartController
{
	/**
	 * Add product to cart via HTMX POST.
	 * Returns updated mini-cart fragment with HX-Trigger header.
	 */
	public static function addToCart(): void
	{
		$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$quantity   = isset( $_POST['quantity'] ) ? absint( $_POST['quantity'] ) : 1;

		if ( empty( $product_id ) || ! wc_get_product( $product_id ) ) {
			status_header( 400 );
			echo '<div class="cart-error"><p>Invalid product. Please try again.</p></div>';
			return;
		}

		$added = WC()->cart->add_to_cart( $product_id, $quantity );

		if ( $added ) {
			header( 'HX-Trigger: cartUpdated' );
			self::renderMiniCart();
		} else {
			status_header( 400 );
			echo '<div class="cart-error"><p>Could not add product to cart. Please try again.</p></div>';
		}
	}

	/**
	 * Render mini-cart HTML fragment.
	 * Shows item count, subtotal, and checkout link.
	 */
	public static function renderMiniCart(): void
	{
		$cart       = WC()->cart;
		$item_count = $cart->get_cart_contents_count();
		$subtotal   = $cart->get_cart_subtotal();
		?>
		<div class="mini-cart" id="mini-cart">
			<div class="mini-cart-header flex justify-between items-center p-3 border-b">
				<span class="font-semibold">Shopping Cart</span>
				<span class="text-sm text-gray-600"><?php echo esc_html( $item_count ); ?> item(s)</span>
			</div>

			<?php if ( 0 === $item_count ) : ?>
				<div class="mini-cart-empty p-4 text-center text-gray-500">
					<p>Your cart is empty.</p>
				</div>
			<?php else : ?>
				<div class="mini-cart-items p-3">
					<?php foreach ( $cart->get_cart() as $cart_item ) : ?>
						<?php
						$product     = $cart_item['data'];
						$product_name = $product->get_name();
						$qty         = $cart_item['quantity'];
						$line_total  = WC()->cart->get_product_subtotal( $product, $qty );
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
						<span class="font-semibold">Subtotal:</span>
						<span class="font-semibold"><?php echo wp_kses_post( $subtotal ); ?></span>
					</div>
					<a href="<?php echo esc_url( wc_get_cart_url() ); ?>"
					   class="block w-full text-center bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition">
						View Cart
					</a>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}
