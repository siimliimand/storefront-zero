<?php
declare(strict_types=1);

/**
 * Flight PHP Route Definitions
 *
 * Routes are relative to the base URL /htmx-api.
 * Includes nonce verification middleware for mutating requests.
 *
 * @package Storefront_Zero
 */

use ThemeApp\Controllers\ProductController;
use ThemeApp\Controllers\CartController;

/**
 * Nonce verification middleware.
 * Checks X-WP-NONCE header on POST, PUT, DELETE requests.
 * GET requests are exempt (read-only, no state change).
 */
Flight::before( 'start', function () {
	$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

	if ( in_array( strtoupper( $method ), [ 'POST', 'PUT', 'DELETE' ], true ) ) {
		$nonce = $_SERVER['HTTP_X_WP_NONCE'] ?? '';

		if ( ! wp_verify_nonce( $nonce, 'storefront_zero_htmx' ) ) {
			Flight::halt( 403, wp_json_encode( [
				'error'   => 'Invalid Security Token',
				'message' => 'The request could not be authenticated. Please refresh the page and try again.',
			] ) );
		}
	}
} );

// GET /search — Live product search.
Flight::route( 'GET /search', function () {
	ProductController::liveSearch();
} );

// POST /cart/add — Add product to cart.
Flight::route( 'POST /cart/add', function () {
	CartController::addToCart();
} );

// GET /nonce — Fresh nonce for cache-safe requests.
Flight::route( 'GET /nonce', function () {
	header( 'Content-Type: application/json' );
	echo wp_json_encode( [
		'nonce' => wp_create_nonce( 'storefront_zero_htmx' ),
	] );
} );

// GET /cart/mini — Mini-cart HTML fragment.
Flight::route( 'GET /cart/mini', function () {
	CartController::renderMiniCart();
} );

// POST /cart/update-qty — Update cart item quantity.
Flight::route( 'POST /cart/update-qty', function () {
	CartController::updateQuantity();
} );

// DELETE /cart/remove — Remove item from cart.
Flight::route( 'DELETE /cart/remove', function () {
	CartController::removeItem();
} );
