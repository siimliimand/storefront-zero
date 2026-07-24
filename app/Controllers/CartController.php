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
		header( 'Content-Type: text/html; charset=utf-8' );

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
		header( 'Content-Type: text/html; charset=utf-8' );

		View::render( 'mini-cart' );
	}

	/**
	 * Update cart item quantity via HTMX POST.
	 * Returns updated mini-cart fragment with HX-Trigger header.
	 */
	public static function updateQuantity(): void
	{
		header( 'Content-Type: text/html; charset=utf-8' );

		$cart_item_key = isset( $_POST['cart_item_key'] ) ? sanitize_text_field( wp_unslash( $_POST['cart_item_key'] ) ) : '';
		$quantity      = isset( $_POST['quantity'] ) ? absint( $_POST['quantity'] ) : 1;

		if ( empty( $cart_item_key ) ) {
			status_header( 400 );
			echo '<!-- Invalid cart item key -->';
			return;
		}

		if ( 0 === $quantity ) {
			$removed = WC()->cart->remove_cart_item( $cart_item_key );
		} else {
			$updated = WC()->cart->set_quantity( $cart_item_key, $quantity );
		}

		if ( isset( $removed ) ? $removed : $updated ) {
			header( 'HX-Trigger: cartUpdated' );
			self::renderMiniCart();
		} else {
			status_header( 400 );
			echo '<!-- Could not update cart -->';
		}
	}
}
