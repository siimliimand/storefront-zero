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
 * Resolve a JS file path, preferring the minified build output when available.
 *
 * Checks for a compiled version under assets/js/dist/ (output of
 * `npm run build:js` via esbuild). Falls back to the source file so the
 * theme works without a prior build step.
 *
 * @param string $source Relative path from theme root, e.g. 'assets/js/app.js'.
 * @return array{0: string, 1: string} [absolute path, URI path].
 */
function storefront_zero_resolve_js( string $source ): array {
	$dist = __DIR__ . '/assets/js/dist/' . basename( $source );

	if ( file_exists( $dist ) ) {
		return [
			$dist,
			get_template_directory_uri() . '/assets/js/dist/' . basename( $source ),
		];
	}

	return [
		__DIR__ . '/' . $source,
		get_template_directory_uri() . '/' . $source,
	];
}

/**
 * Enqueue theme assets: Tailwind CSS, HTMX, app JS, and Web Components.
 *
 * @return void
 */
function storefront_zero_enqueue_assets(): void {
	// Compiled Tailwind CSS.
	wp_enqueue_style(
		'storefront-zero-style',
		get_template_directory_uri() . '/assets/css/main.css',
		[],
		storefront_zero_filemtime( __DIR__ . '/assets/css/main.css' )
	);

	// HTMX — local vendor bundle for reliability and GDPR compliance.
	wp_enqueue_script(
		'htmx',
		get_template_directory_uri() . '/assets/js/vendor/htmx.min.js',
		[],
		SZ_HTMX_VERSION,
		true
	);

	// Theme app JS — depends on HTMX, loaded in footer.
	[ $app_path, $app_uri ] = storefront_zero_resolve_js( 'assets/js/app.js' );

	wp_enqueue_script(
		'storefront-zero-app',
		$app_uri,
		[ 'htmx' ],
		storefront_zero_filemtime( $app_path ),
		true
	);

	// Pass theme settings to JS.
	wp_localize_script(
		'storefront-zero-app',
		'ThemeSettings',
		[
			'endpoint' => home_url( '/htmx-api' ),
			'nonce'    => wp_create_nonce( 'storefront_zero_htmx' ),
		]
	);

	// Quantity stepper - +/- buttons for WooCommerce quantity inputs.
	[ $qty_path, $qty_uri ] = storefront_zero_resolve_js( 'assets/js/qty-stepper.js' );

	wp_enqueue_script(
		'storefront-zero-qty-stepper',
		$qty_uri,
		[ 'htmx' ],
		storefront_zero_filemtime( $qty_path ),
		true
	);

	// Web Components — explicit registration (no glob I/O on every page load).
	$web_components = [
		'mobile-drawer',
		'toast-notification',
		'dark-mode-toggle',
		'product-variation-form',
	];

	foreach ( $web_components as $wc_name ) {
		[ $wc_path, $wc_uri ] = storefront_zero_resolve_js( 'assets/js/web-components/' . $wc_name . '.js' );
		$wc_version            = storefront_zero_filemtime( $wc_path );

		wp_enqueue_script(
			'sz-wc-' . $wc_name,
			$wc_uri,
			[ 'htmx' ],
			$wc_version,
			true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'storefront_zero_enqueue_assets' );

/**
 * Register theme support features.
 *
 * @return void
 */
function storefront_zero_setup(): void {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', [
		'search-form',
		'comment-form',
		'comment-list',
		'gallery',
		'caption',
	] );

	register_nav_menus( [
		'primary' => esc_html__( 'Primary Menu', 'storefront-zero' ),
		'footer'  => esc_html__( 'Footer Menu', 'storefront-zero' ),
	] );

	add_theme_support( 'woocommerce', [
		'gallery_thumbnail_image_width' => 300,
		'single_image_width'            => 600,
		'product_grid'                  => [
			'default_rows'    => 3,
			'min_rows'        => 1,
			'default_columns' => 3,
			'min_columns'     => 1,
			'max_columns'     => 4,
		],
	] );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
}
add_action( 'after_setup_theme', 'storefront_zero_setup' );

/**
 * Remove WordPress emoji scripts for performance.
 *
 * @return void
 */
function storefront_zero_disable_emoji(): void {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
}
add_action( 'init', 'storefront_zero_disable_emoji' );

/**
 * Dequeue unused WooCommerce scripts and styles on non-product pages.
 *
 * Removes select2, zoom, and prettyPhoto on pages that don't need them.
 *
 * @return void
 */
function storefront_zero_dequeue_unused_wc_assets(): void {
	if ( is_product() ) {
		return;
	}

	wp_dequeue_script( 'wc-add-to-cart-variation' );
	wp_dequeue_script( 'wc-single-product' );
	wp_dequeue_script( 'zoom' );
	wp_dequeue_script( 'select2' );
	wp_dequeue_style( 'select2' );
	wp_dequeue_script( 'prettyPhoto' );
	wp_dequeue_style( 'prettyPhoto' );
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

	Flight::start();
	exit;
}
add_action( 'template_redirect', 'storefront_zero_flight_init', 5 );

/**
 * Add type="module" to web component scripts.
 *
 * Web components use modern JavaScript features and should be loaded as modules.
 *
 * @param string $tag    The script tag.
 * @param string $handle The script handle.
 * @return string Modified script tag.
 */
function storefront_zero_script_module_tag( string $tag, string $handle ): string {
	if ( strpos( $handle, 'sz-wc-' ) === 0 ) {
		return str_replace( ' src=', ' type="module" src=', $tag );
	}
	return $tag;
}
add_filter( 'script_loader_tag', 'storefront_zero_script_module_tag', 10, 2 );

/**
 * Add defer attribute to the HTMX script tag.
 *
 * WordPress has no native `defer` parameter for wp_enqueue_script(), so we
 * inject it via the script_loader_tag filter.
 *
 * @param string $tag    The script tag.
 * @param string $handle The script handle.
 * @return string Modified script tag.
 */
function storefront_zero_defer_htmx( string $tag, string $handle ): string {
	if ( 'htmx' === $handle ) {
		return str_replace( ' src=', ' defer src=', $tag );
	}
	return $tag;
}
add_filter( 'script_loader_tag', 'storefront_zero_defer_htmx', 20, 2 );

/**
 * Add loading="lazy" to WooCommerce product images on shop and archive pages.
 *
 * Filters wp_get_attachment_image_attributes only on WooCommerce product pages
 * to avoid affecting above-the-fold images site-wide.
 *
 * @param array<string, string> $attr       Image attributes.
 * @param \WP_Post              $attachment Attachment post object.
 * @param string|int[]          $size       Requested image size.
 * @return array<string, string> Modified attributes.
 */
function storefront_zero_lazy_product_images( array $attr, \WP_Post $attachment, $size ): array {
	if ( is_woocommerce() ) {
		$attr['loading'] = 'lazy';
	}
	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'storefront_zero_lazy_product_images', 10, 3 );
