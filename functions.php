<?php

declare(strict_types=1);

/**
 * Theme functions for Storefront Zero.
 *
 * @package StorefrontZero
 * @since 1.0.0
 */

// Composer autoloader.
require_once __DIR__ . '/vendor/autoload.php';

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
		(string) filemtime( __DIR__ . '/assets/css/main.css' )
	);

	// HTMX from CDN — loaded in footer.
	wp_enqueue_script(
		'htmx',
		'https://unpkg.com/htmx.org@1.9.10/dist/htmx.min.js',
		[],
		'1.9.10',
		true
	);

	// Theme app JS — depends on HTMX, loaded in footer.
	wp_enqueue_script(
		'storefront-zero-app',
		get_template_directory_uri() . '/assets/js/app.js',
		[ 'htmx' ],
		(string) filemtime( __DIR__ . '/assets/js/app.js' ),
		true
	);

	// Pass theme settings to JS.
	wp_localize_script(
		'storefront-zero-app',
		'ThemeSettings',
		[
			'endpoint' => home_url( '/htmx-api' ),
			'nonce'    => wp_create_nonce( 'wp_rest' ),
		]
	);

	// Web Components — each .js file in the directory depends on HTMX.
	$wc_dir = __DIR__ . '/assets/js/web-components';
	if ( is_dir( $wc_dir ) ) {
		foreach ( glob( $wc_dir . '/*.js' ) as $wc_script ) {
			$handle     = 'sz-wc-' . sanitize_title( basename( $wc_script, '.js' ) );
			$wc_version = (string) filemtime( $wc_script );

			wp_enqueue_script(
				$handle,
				get_template_directory_uri() . '/assets/js/web-components/' . basename( $wc_script ),
				[ 'htmx' ],
				$wc_version,
				true
			);
		}
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
 * Intercept HTMX API requests and hand them off to Flight PHP.
 *
 * Only handles requests under /htmx-api — all other URLs proceed through
 * the normal WordPress template hierarchy, preserving Yoast SEO metadata.
 *
 * @return void
 */
function storefront_zero_flight_init(): void {
	if ( strpos( $_SERVER['REQUEST_URI'], '/htmx-api' ) !== 0 ) {
		return;
	}

	define( 'FLIGHT_START', true );

	Flight::set( 'base_url', '/htmx-api' );

	require_once __DIR__ . '/app/routes.php';

	Flight::start();
	exit;
}
add_action( 'template_redirect', 'storefront_zero_flight_init', 5 );
