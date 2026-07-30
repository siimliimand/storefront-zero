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

// HTMX API router: rewrite rules, query vars, and Flight initialization.
require_once __DIR__ . '/inc/htmx-router.php';

// Cache-related functions: transient purging and invalidation.
require_once __DIR__ . '/inc/cache.php';

/**
 * Theme version for cache busting.
 */
define( 'SZ_HTMX_VERSION', '1.9.10' );

/**
 * HTMX v2.0 Upgrade Assessment
 *
 * Current version: 1.9.10 (bundled at assets/js/vendor/htmx.min.js)
 *
 * Key HTMX 2.0 changes relevant to this theme:
 * - htmx.config.selfRequestsOnly removed (was false by default, no impact)
 * - HX-Trigger response header now dispatches events on the triggering element,
 *   not document.body. Our showToast listener on document.body will need to move
 *   to the target element or use a delegated listener.
 * - htmx:configRequest event model unchanged — our nonce injection works as-is
 * - hx-swap="outerHTML" behavior unchanged
 * - New htmx:beforeSend event for request interception
 *
 * Migration steps:
 * 1. Update vendor/htmx.min.js to 2.0.x
 * 2. Move showToast listener from document.body to event delegation on target
 * 3. Test all HTMX endpoints (cart, search, filter) with new event model
 * 4. Update SZ_HTMX_VERSION constant
 *
 * Recommendation: Upgrade after WC 9.x stabilizes. Current 1.9.10 is functional
 * and well-tested. No urgency unless a security patch is needed.
 */

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
