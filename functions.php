<?php

declare(strict_types=1);

/**
 * Theme functions for Storefront Zero.
 *
 * @package StorefrontZero
 * @since 1.0.0
 */

// Composer autoloader.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

// Admin Settings Page.
require_once __DIR__ . '/inc/admin-settings.php';

// Theme setup: supports, menus, and performance tweaks.
require_once __DIR__ . '/inc/setup.php';

// Script and style enqueuing logic.
require_once __DIR__ . '/inc/assets.php';

// WooCommerce-specific optimizations (asset dequeuing, lazy images).
require_once __DIR__ . '/inc/wc-optimization.php';

/**
 * Theme version for cache busting.
 */
define( 'SZ_HTMX_VERSION', '1.9.10' );

/**
 * Get file modification time as a version string, with fallback.
 *
 * Returns the file's mtime when the file exists, otherwise falls back to
 * '1.0.0' so cache-busting still works before the first build.
 *
 * @param string $path Absolute path to the file.
 * @return string Version string.
 */
function storefront_zero_filemtime( string $path ): string {
	$mtime = filemtime( $path );
	return $mtime ? (string) $mtime : '1.0.0';
}

/**
 * Build a full URL for an HTMX API endpoint.
 *
 * @param string $path Optional sub-path, e.g. 'search' or 'cart/mini'.
 * @return string Escaped absolute URL.
 */
function sz_endpoint( string $path = '' ): string {
	return esc_url( home_url( '/htmx-api/' . ltrim( $path, '/' ) ) );
}

/**
 * Register the Sidebar widget area.
 *
 * @return void
 */
function storefront_zero_widgets_init(): void {
	register_sidebar( [
		'name'          => esc_html__( 'Sidebar', 'storefront-zero' ),
		'id'            => 'sidebar-1',
		'description'   => esc_html__( 'Add widgets here to appear in the sidebar.', 'storefront-zero' ),
		'before_widget' => '<section id="%1$s" class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	] );
}
add_action( 'widgets_init', 'storefront_zero_widgets_init' );

/**
 * Register rewrite rule for HTMX API endpoints.
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

/**
 * Purge cached search transients when products change.
 *
 * Deletes the sz_search_hash transient to ensure search results
 * reflect the latest product data.
 *
 * @return void
 */
function storefront_zero_purge_search_transients(): void {
	$generation = (int) get_option( 'sz_search_generation', 0 );
	update_option( 'sz_search_generation', $generation + 1 );
}
add_action( 'save_post_product', 'storefront_zero_purge_search_transients' );
add_action( 'woocommerce_update_product', 'storefront_zero_purge_search_transients' );
add_action( 'delete_post', 'storefront_zero_purge_search_transients' );
