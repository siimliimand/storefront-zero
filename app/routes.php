<?php
declare(strict_types=1);

/**
 * Flight PHP Route Definitions
 *
 * All routes include the /htmx-api prefix since Flight does not
 * auto-strip the base_url from the request URI.
 *
 * @package Storefront_Zero
 */

use ThemeApp\Container;
use ThemeApp\Controllers\ProductController;
use ThemeApp\Controllers\CartController;

// Build the DI container once per request.
$container = Container::create();

/**
 * Nonce verification middleware.
 * Checks X-WP-NONCE header on POST, PUT, DELETE requests.
 * GET requests are exempt (read-only, no state change).
 */
Flight::before( 'start', function () {
	$method = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) );

	if ( in_array( strtoupper( $method ), [ 'POST', 'PUT', 'DELETE' ], true ) ) {
		$nonce = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_WP_NONCE'] ?? '' ) );

		if ( ! wp_verify_nonce( $nonce, 'storefront_zero_htmx' ) ) {
			$message = wp_json_encode( [
				'error'   => 'Invalid Security Token',
				'message' => 'The request could not be authenticated. Please refresh the page and try again.',
			] );
			Flight::halt( 403, is_string( $message ) ? $message : '' );
		}
	}
} );

// GET /htmx-api/search — Live product search.
Flight::route( 'GET /htmx-api/search', function () use ( $container ) {
	$container->get( ProductController::class )->liveSearch();
} );

// GET /htmx-api/products/filter — Faceted product filter.
Flight::route( 'GET /htmx-api/products/filter', function () use ( $container ) {
	$container->get( ProductController::class )->filterProducts();
} );

// POST /htmx-api/cart/add — Add product to cart.
Flight::route( 'POST /htmx-api/cart/add', function () use ( $container ) {
	$container->get( CartController::class )->addToCart();
} );

// GET /htmx-api/nonce — Fresh nonce for cache-safe requests.
Flight::route( 'GET /htmx-api/nonce', function () {
	// Rate limit: 1 request per second per IP.
	$ip_key    = 'sfz_nonce_' . md5( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' );
	$last_time = get_transient( $ip_key );

	if ( false !== $last_time ) {
		$elapsed = microtime( true ) - (float) $last_time;

		if ( $elapsed < 1.0 ) {
			$rateLimitMessage = wp_json_encode( [
				'error'   => 'Too Many Requests',
				'message' => 'Please wait before requesting a new nonce.',
			] );
			Flight::halt( 429, is_string( $rateLimitMessage ) ? $rateLimitMessage : '' );
		}
	}

	set_transient( $ip_key, microtime( true ), 5 );

	// Prevent browsers and proxies from caching this response.
	header( 'Cache-Control: private, no-store, must-revalidate' );
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Content-Type: application/json' );

	echo wp_json_encode( [
		'nonce' => wp_create_nonce( 'storefront_zero_htmx' ),
	] );
} );

// GET /htmx-api/cart/mini — Mini-cart HTML fragment.
Flight::route( 'GET /htmx-api/cart/mini', function () use ( $container ) {
	$container->get( CartController::class )->renderMiniCart();
} );

// POST /htmx-api/cart/update-qty — Update cart item quantity.
Flight::route( 'POST /htmx-api/cart/update-qty', function () use ( $container ) {
	$container->get( CartController::class )->updateQuantity();
} );

// DELETE /htmx-api/cart/remove — Remove item from cart.
Flight::route( 'DELETE /htmx-api/cart/remove', function () use ( $container ) {
	$container->get( CartController::class )->removeItem();
} );
