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
 * Dequeue WooCommerce scripts and styles that the theme replaces with HTMX or doesn't need.
 *
 * Configured via Theme Settings (Appearance > Theme Settings).
 *
 * @return void
 */
function storefront_zero_dequeue_unused_wc_assets(): void {
	// Order attribution script scope setting.
	$attribution_scope = get_option( 'sz_attribution_global_loading', 'checkout_only' );
	if ( 'checkout_only' === $attribution_scope && ! is_checkout() ) {
		wp_dequeue_script( 'sourcebuster-js' );
		wp_dequeue_script( 'wc-order-attribution' );
	}

	// CSS Optimization setting.
	$optimize_css = get_option( 'sz_optimize_wc_css', '1' );
	if ( '1' === $optimize_css ) {
		// Dequeue WooCommerce core CSS on non-store pages.
		if ( ! is_woocommerce() && ! is_cart() && ! is_checkout() && ! is_account_page() && ! is_product() ) {
			wp_dequeue_style( 'woocommerce-layout' );
			wp_dequeue_style( 'woocommerce-smallscreen' );
			wp_dequeue_style( 'woocommerce-general' );
			wp_dequeue_style( 'woocommerce-inline' );
			wp_dequeue_style( 'wc-blocks-style' );
			wp_dequeue_style( 'wc-blocks-vendors-style' );
			wp_dequeue_style( 'wc-blocks-packages-style' );
		}
	}

	// Cart, checkout, and my-account pages NEED jQuery for WooCommerce core scripts.
	if ( is_cart() || is_checkout() || is_account_page() ) {
		if ( ! is_product() ) {
			wp_dequeue_script( 'wc-add-to-cart-variation' );
			wp_dequeue_script( 'wc-single-product' );
			wp_dequeue_script( 'zoom' );
			wp_dequeue_script( 'select2' );
			wp_dequeue_style( 'select2' );
			wp_dequeue_script( 'prettyPhoto' );
			wp_dequeue_style( 'prettyPhoto' );
		}
		return;
	}

	// ── Non-cart/checkout pages (shop, product, archive, front page, standard pages) ──

	// Dequeue WC's AJAX add-to-cart — theme uses HTMX hx-post="/htmx-api/cart/add".
	wp_dequeue_script( 'wc-add-to-cart' );

	// Dequeue WooCommerce core frontend JS (cart fragments, overlay blocking, cookies).
	wp_dequeue_script( 'woocommerce' );
	wp_dequeue_script( 'wc-cart-fragments' );

	// Dequeue jQuery UI & blockUI dependencies.
	wp_dequeue_script( 'wc-jquery-blockui' );
	wp_dequeue_script( 'jquery-blockui' );
	wp_dequeue_script( 'wc-js-cookie' );
	wp_dequeue_script( 'js-cookie' );

	// Dequeue jQuery itself on non-cart/checkout pages.
	wp_dequeue_script( 'jquery' );
	wp_dequeue_script( 'jquery-core' );
	wp_dequeue_script( 'jquery-migrate' );

	if ( ! is_product() ) {
		wp_dequeue_script( 'wc-add-to-cart-variation' );
		wp_dequeue_script( 'wc-single-product' );
		wp_dequeue_script( 'zoom' );
		wp_dequeue_script( 'select2' );
		wp_dequeue_style( 'select2' );
		wp_dequeue_script( 'prettyPhoto' );
		wp_dequeue_style( 'prettyPhoto' );
	}
}
add_action( 'wp_enqueue_scripts', 'storefront_zero_dequeue_unused_wc_assets', 99 );

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
 * Lazy-load WooCommerce product images, skipping above-the-fold hero images.
 *
 * - Single product pages: the main thumbnail gets fetchpriority="high" and no
 *   lazy load (it is the LCP element).
 * - Front page: the hero product thumbnail gets the same treatment.
 * - All other WooCommerce images (shop, archives, galleries) are lazy-loaded.
 *
 * @param array<string, string> $attr       Image attributes.
 * @param \WP_Post              $attachment Attachment post object.
 * @param string|int[]          $size       Requested image size.
 * @return array<string, string> Modified attributes.
 */
function storefront_zero_lazy_product_images( array $attr, \WP_Post $attachment, $size ): array {
	// Skip lazy loading for the main product image on single product pages.
	if ( is_product() && has_post_thumbnail() ) {
		$thumbnail_id = get_post_thumbnail_id();
		if ( (string) $attachment->ID === (string) $thumbnail_id ) {
			$attr['fetchpriority'] = 'high';
			return $attr;
		}
	}

	// Skip lazy loading for the hero image on the front page.
	if ( is_front_page() && has_post_thumbnail() ) {
		$thumbnail_id = get_post_thumbnail_id();
		if ( (string) $attachment->ID === (string) $thumbnail_id ) {
			$attr['fetchpriority'] = 'high';
			return $attr;
		}
	}

	// Lazy load all other WooCommerce product images.
	if ( is_woocommerce() ) {
		$attr['loading'] = 'lazy';
	}

	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'storefront_zero_lazy_product_images', 10, 3 );

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
