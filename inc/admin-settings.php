<?php

declare(strict_types=1);

/**
 * Storefront Zero Theme Admin Settings.
 *
 * Provides performance optimization and attribution configuration options.
 *
 * @package StorefrontZero
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register theme settings in WordPress.
 */
function storefront_zero_register_settings(): void {
	// Multi-session attribution toggle (0 = single session, 1 = multi-session / 6 months)
	register_setting(
		'storefront_zero_options_group',
		'sz_multi_session_attribution',
		[
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '0',
		]
	);

	// Order attribution script loading scope ('checkout_only' or 'all_pages')
	register_setting(
		'storefront_zero_options_group',
		'sz_attribution_global_loading',
		[
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'checkout_only',
		]
	);

	// WooCommerce CSS optimization toggle (0 = keep all WC CSS everywhere, 1 = dequeue on non-WC pages)
	register_setting(
		'storefront_zero_options_group',
		'sz_optimize_wc_css',
		[
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '1',
		]
	);

	// Settings section
	add_settings_section(
		'sz_performance_attribution_section',
		__( 'Performance & Attribution Settings', 'storefront-zero' ),
		function () {
			echo '<p>' . esc_html__( 'Configure asset optimization and WooCommerce Order Attribution behavior.', 'storefront-zero' ) . '</p>';
		},
		'storefront-zero-settings'
	);

	// Multi-session field
	add_settings_field(
		'sz_multi_session_attribution',
		__( 'Multi-Session Attribution', 'storefront-zero' ),
		'storefront_zero_multi_session_field_render',
		'storefront-zero-settings',
		'sz_performance_attribution_section'
	);

	// Attribution loading scope field
	add_settings_field(
		'sz_attribution_global_loading',
		__( 'Order Attribution Loading Scope', 'storefront-zero' ),
		'storefront_zero_attribution_scope_field_render',
		'storefront-zero-settings',
		'sz_performance_attribution_section'
	);

	// CSS optimization field
	add_settings_field(
		'sz_optimize_wc_css',
		__( 'WooCommerce CSS Optimization', 'storefront-zero' ),
		'storefront_zero_optimize_css_field_render',
		'storefront-zero-settings',
		'sz_performance_attribution_section'
	);
}
add_action( 'admin_init', 'storefront_zero_register_settings' );

/**
 * Render multi-session attribution setting field.
 */
function storefront_zero_multi_session_field_render(): void {
	$val = get_option( 'sz_multi_session_attribution', '0' );
	?>
	<label for="sz_multi_session_attribution">
		<input type="checkbox" id="sz_multi_session_attribution" name="sz_multi_session_attribution" value="1" <?php checked( '1', $val ); ?> />
		<?php esc_html_e( 'Enable multi-session attribution tracking (extends attribution cookies beyond single-session up to 6 months)', 'storefront-zero' ); ?>
	</label>
	<?php
}

/**
 * Render attribution script loading scope setting field.
 */
function storefront_zero_attribution_scope_field_render(): void {
	$val = get_option( 'sz_attribution_global_loading', 'checkout_only' );
	?>
	<fieldset>
		<label style="display:block; margin-bottom: 6px;">
			<input type="radio" name="sz_attribution_global_loading" value="checkout_only" <?php checked( 'checkout_only', $val ); ?> />
			<strong><?php esc_html_e( 'Checkout page only (Recommended for speed)', 'storefront-zero' ); ?></strong>
			<br><span class="description" style="margin-left: 22px; display:inline-block;"><?php esc_html_e( 'Dequeues sourcebuster.js and order-attribution.js on non-checkout pages, saving ~6 kB of JS.', 'storefront-zero' ); ?></span>
		</label>
		<label style="display:block;">
			<input type="radio" name="sz_attribution_global_loading" value="all_pages" <?php checked( 'all_pages', $val ); ?> />
			<strong><?php esc_html_e( 'All pages (Maximum tracking accuracy)', 'storefront-zero' ); ?></strong>
			<br><span class="description" style="margin-left: 22px; display:inline-block;"><?php esc_html_e( 'Keeps sourcebuster.js and order-attribution.js loaded everywhere to track initial landing campaign parameters across multiple visits.', 'storefront-zero' ); ?></span>
		</label>
	</fieldset>
	<?php
}

/**
 * Render CSS optimization setting field.
 */
function storefront_zero_optimize_css_field_render(): void {
	$val = get_option( 'sz_optimize_wc_css', '1' );
	?>
	<label for="sz_optimize_wc_css">
		<input type="checkbox" id="sz_optimize_wc_css" name="sz_optimize_wc_css" value="1" <?php checked( '1', $val ); ?> />
		<?php esc_html_e( 'Dequeue WooCommerce CSS (wc-blocks, woocommerce-layout, woocommerce-general) on non-store pages', 'storefront-zero' ); ?>
	</label>
	<p class="description"><?php esc_html_e( 'Saves ~19 kB of CSS on non-store pages like home page, blog posts, and standard pages.', 'storefront-zero' ); ?></p>
	<?php
}

/**
 * Add theme options page to admin menu.
 */
function storefront_zero_add_admin_menu(): void {
	add_theme_page(
		__( 'Storefront Zero Settings', 'storefront-zero' ),
		__( 'Theme Settings', 'storefront-zero' ),
		'manage_options',
		'storefront-zero-settings',
		'storefront_zero_render_settings_page'
	);
}
add_action( 'admin_menu', 'storefront_zero_add_admin_menu' );

/**
 * Render the Theme Settings page.
 */
function storefront_zero_render_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Storefront Zero Settings', 'storefront-zero' ); ?></h1>
		<form action="options.php" method="post">
			<?php
			settings_fields( 'storefront_zero_options_group' );
			do_settings_sections( 'storefront-zero-settings' );
			submit_button( __( 'Save Settings', 'storefront-zero' ) );
			?>
		</form>
	</div>
	<?php
}

/**
 * Hook multi-session attribution filter based on theme option.
 */
add_filter( 'wc_order_attribution_cookie_lifetime_months', function ( $lifetime ) {
	if ( '1' === get_option( 'sz_multi_session_attribution', '0' ) ) {
		return 6.0;
	}
	return $lifetime;
} );
