<?php

declare(strict_types=1);

/**
 * Script and style enqueuing logic.
 *
 * Extracted from functions.php for maintainability. Contains all
 * wp_enqueue_scripts and script_loader_tag / style_loader_tag filters.
 *
 * @package StorefrontZero
 * @since 1.1.0
 */

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
	$dist = __DIR__ . '/../assets/js/dist/' . basename( $source );

	if ( file_exists( $dist ) ) {
		return [
			$dist,
			get_template_directory_uri() . '/assets/js/dist/' . basename( $source ),
		];
	}

	return [
		__DIR__ . '/../' . $source,
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
		storefront_zero_filemtime( __DIR__ . '/../assets/css/main.css' )
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
	// Product variation form — both only needed on single product pages.
	if ( is_product() ) {
		[ $qty_path, $qty_uri ] = storefront_zero_resolve_js( 'assets/js/qty-stepper.js' );

		wp_enqueue_script(
			'storefront-zero-qty-stepper',
			$qty_uri,
			[ 'htmx' ],
			storefront_zero_filemtime( $qty_path ),
			true
		);

		[ $pvf_path, $pvf_uri ] = storefront_zero_resolve_js( 'assets/js/web-components/product-variation-form.js' );

		wp_enqueue_script(
			'sz-wc-product-variation-form',
			$pvf_uri,
			[ 'htmx' ],
			storefront_zero_filemtime( $pvf_path ),
			true
		);
	}

	// Web Components — explicit registration (no glob I/O on every page load).
	$web_components = [
		'mobile-drawer',
		'toast-notification',
		'dark-mode-toggle',
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
 * Load main.css asynchronously to avoid render-blocking.
 *
 * Changes the <link> tag to media="print" onload="this.media='all'" so the
 * browser fetches the stylesheet in parallel without blocking first paint.
 * Critical CSS is inlined in header.php for above-the-fold content.
 *
 * @param string $tag    The link tag HTML.
 * @param string $handle The stylesheet handle.
 * @return string Modified link tag.
 */
function storefront_zero_async_stylesheet( string $tag, string $handle ): string {
	if ( 'storefront-zero-style' !== $handle ) {
		return $tag;
	}

	// Replace media="all" with the async loading pattern.
	$tag = str_replace(
		" media='all'",
		" media='print' onload=\"this.media='all'\"",
		$tag
	);

	return $tag;
}
add_filter( 'style_loader_tag', 'storefront_zero_async_stylesheet', 10, 2 );

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
