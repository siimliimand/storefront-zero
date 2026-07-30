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

use Flight;
use ThemeApp\Concerns\FlushesWcNotices;
use ThemeApp\View;

class CartController
{
    use FlushesWcNotices;
	/**
	 * View instance for rendering templates.
	 *
	 * @var \ThemeApp\View
	 */
	private View $view;

	/**
	 * WooCommerce cart instance.
	 *
	 * @var \WC_Cart
	 */
	private \WC_Cart $cart;

	/**
	 * Constructor. Injected by the DI container.
	 *
	 * @param \WC_Cart     $cart WooCommerce cart instance.
	 * @param \ThemeApp\View $view View renderer.
	 */
	public function __construct( \WC_Cart $cart, View $view )
	{
		$this->cart = $cart;
		$this->view = $view;
	}

	/**
	 * Add product to cart via HTMX POST.
	 * Returns updated mini-cart fragment with HX-Trigger header.
	 */
	public function addToCart(): void
	{
		header( 'Content-Type: text/html; charset=utf-8' );

		ob_start();

		// @phpstan-ignore-next-line ternary.alwaysTrue
		$data = Flight::request()->data ?: [];

		$product_id  = isset( $data['product_id'] ) ? absint( $data['product_id'] ) : 0;
		$quantity    = isset( $data['quantity'] ) ? absint( $data['quantity'] ) : 1;
		$variation_id = isset( $data['variation_id'] ) ? absint( $data['variation_id'] ) : 0;

		if ( empty( $product_id ) || ! wc_get_product( $product_id ) ) {
			ob_end_clean();
			status_header( 400 );
			$this->view->render( 'cart-error', [ 'message' => __( 'Invalid product. Please try again.', 'storefront-zero' ) ] );
			echo $this->flush_wc_notices();
			return;
		}

		// Collect attribute data for variable products.
		$variation = [];
		if ( $variation_id > 0 ) {
			foreach ( $data as $key => $value ) {
				if ( 0 === strpos( $key, 'attribute_' ) ) {
					$variation[ $key ] = sanitize_text_field( wp_unslash( $value ) );
				}
			}
		}

		$added = $this->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation );

		if ( $added ) {
			// Extract the added product name from the cart item.
			$cart_item    = $this->cart->get_cart_item( $added );
			$product_name = $cart_item && isset( $cart_item['data'] )
				? $cart_item['data']->get_name()
				: '';

			header( 'HX-Trigger: cartUpdated' );
			$this->renderMiniCart( [ 'added_product' => $product_name ] );
		} else {
			status_header( 400 );
			$this->view->render( 'cart-error', [ 'message' => __( 'Could not add product to cart. Please try again.', 'storefront-zero' ) ] );
		}

		echo $this->flush_wc_notices();
	}

	/**
	 * Render mini-cart HTML fragment.
	 * Shows cart icon with item count badge; used by GET /htmx-api/cart/mini.
	 *
	 * @param array<string, mixed> $data Optional view data (e.g. added_product name).
	 */
	public function renderMiniCart( array $data = [] ): void
	{
		header( 'Content-Type: text/html; charset=utf-8' );

		$this->view->render( 'mini-cart-fragment', $data );
	}

	/**
	 * Render full cart page HTML fragment.
	 * Re-renders the WooCommerce cart form (table, quantities, coupons,
	 * totals) for HTMX swap targeting #cart-content.
	 *
	 * @param array<string, mixed> $data Optional view data.
	 */
	public function renderCartPage( array $data = [] ): void
	{
		header( 'Content-Type: text/html; charset=utf-8' );

		$this->view->render( 'cart-page', $data );

		echo $this->flush_wc_notices();
	}

	/**
	 * Update cart item quantity via HTMX POST.
	 * Returns updated cart page fragment with HX-Trigger header.
	 */
	public function updateQuantity(): void
	{
		header( 'Content-Type: text/html; charset=utf-8' );

		ob_start();

		$cart_item_key = isset( Flight::request()->data['cart_item_key'] ) ? sanitize_text_field( wp_unslash( Flight::request()->data['cart_item_key'] ) ) : '';
		$quantity      = isset( Flight::request()->data['quantity'] ) ? absint( Flight::request()->data['quantity'] ) : 1;

		if ( empty( $cart_item_key ) ) {
			ob_end_clean();
			status_header( 400 );
			echo '<!-- Invalid cart item key -->';
			echo $this->flush_wc_notices();
			return;
		}

		$success = false;

		if ( 0 === $quantity ) {
			$success = $this->cart->remove_cart_item( $cart_item_key );
		} else {
			$success = $this->cart->set_quantity( $cart_item_key, $quantity );
		}

		if ( $success ) {
			header( 'HX-Trigger: cartUpdated' );
			$this->renderCartPage();
		} else {
			status_header( 400 );
			echo '<!-- Could not update cart -->';
		}

		echo $this->flush_wc_notices();
	}

	/**
	 * Remove cart item via HTMX DELETE.
	 * Returns updated cart page fragment with HX-Trigger header.
	 */
	public function removeItem(): void
	{
		header( 'Content-Type: text/html; charset=utf-8' );

		ob_start();

		$cart_item_key = isset( Flight::request()->data['cart_item_key'] ) ? sanitize_text_field( wp_unslash( Flight::request()->data['cart_item_key'] ) ) : '';

		if ( empty( $cart_item_key ) ) {
			ob_end_clean();
			status_header( 400 );
			echo '<!-- Invalid cart item key -->';
			echo $this->flush_wc_notices();
			return;
		}

		$removed = $this->cart->remove_cart_item( $cart_item_key );

		if ( $removed ) {
			header( 'HX-Trigger: cartUpdated' );
			$this->renderCartPage();
		} else {
			status_header( 400 );
			echo '<!-- Could not remove cart item -->';
		}

		echo $this->flush_wc_notices();
	}
}
