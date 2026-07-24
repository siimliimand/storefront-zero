<?php
declare(strict_types=1);

/**
 * Cart Controller
 *
 * Handles cart-related HTMX fragment requests.
 *
 * @package Storefront_Zero
 */

namespace ThemeApp\Controllers;

use ThemeApp\View;

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
			View::render( 'cart-error', [ 'message' => __( 'Invalid product. Please try again.', 'storefront-zero' ) ] );
			return;
		}

		$added = WC()->cart->add_to_cart( $product_id, $quantity );

		if ( $added ) {
			header( 'HX-Trigger: cartUpdated' );
			self::renderMiniCart();
		} else {
			status_header( 400 );
			View::render( 'cart-error', [ 'message' => __( 'Could not add product to cart. Please try again.', 'storefront-zero' ) ] );
		}
	}

	/**
	 * Render mini-cart HTML fragment.
	 * Shows item count, subtotal, and checkout link.
	 */
	public static function renderMiniCart(): void
	{
		View::render( 'mini-cart' );
	}
}
