<?php

declare(strict_types=1);

/**
 * HTMX API router.
 *
 * Registers rewrite rules for the /htmx-api path, makes WordPress
 * recognise the htmx-api query variable, and boots Flight PHP to
 * handle incoming requests.
 *
 * @package StorefrontZero
 * @since 1.1.0
 */

/**
 * Register rewrite rules for HTMX API endpoints.
 *
 * Without this, WordPress returns 404 before template_redirect fires.
 *
 * @return void
 */
function storefront_zero_htmx_rewrite_rules(): void {
	add_rewrite_rule( 'htmx-api/(.+?)/?$', 'index.php?htmx-api=$matches[1]', 'top' );
	add_rewrite_rule( 'htmx-api/?$', 'index.php?htmx-api=', 'top' );
}
add_action( 'init', 'storefront_zero_htmx_rewrite_rules' );

/**
 * Register the htmx-api query variable so WordPress recognizes it.
 *
 * @param array<string> $vars Existing public query vars.
 * @return array<string>
 */
function storefront_zero_htmx_query_vars( array $vars ): array {
	$vars[] = 'htmx-api';
	return $vars;
}
add_filter( 'query_vars', 'storefront_zero_htmx_query_vars' );

/**
 * Intercept HTMX API requests and hand them off to Flight PHP.
 *
 * Only handles requests under /htmx-api — all other URLs proceed through
 * the normal WordPress template hierarchy, preserving Yoast SEO metadata.
 * Request URI is normalized against home_url() via parse_url() so both root
 * and subdirectory WordPress installs are handled correctly.
 *
 * @return void
 */
function storefront_zero_flight_init(): void {
	$request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) );

	// Normalize against the site root so subdirectory installs work.
	$site_path = rtrim( (string) parse_url( home_url(), PHP_URL_PATH ), '/' );

	// Strip the site path prefix to get the relative URI.
	$relative_uri = '/' . ltrim( substr( $request_uri, strlen( $site_path ) ), '/' );

	if ( false === strpos( $relative_uri, '/htmx-api' ) ) {
		return;
	}

	define( 'FLIGHT_START', true );

	// Build the full prefix (site path + htmx-api) for Flight routing.
	$api_prefix = $site_path . '/htmx-api';
	Flight::set( 'base_url', $api_prefix );

	require_once __DIR__ . '/app/routes.php';

	// Persist WooCommerce session after Flight handles the request.
	Flight::after( 'start', function () {
		try {
			WC()->session->save_data();
		} catch ( \Throwable $e ) {
			error_log( sprintf( '[Storefront Zero] WC session save failed: %s in %s on line %d', $e->getMessage(), $e->getFile(), $e->getLine() ) );
		}
	} );

	Flight::start();
	exit;
}
add_action( 'template_redirect', 'storefront_zero_flight_init', 5 );
