<?php

declare(strict_types=1);

/**
 * Cache-related functions for Storefront Zero.
 *
 * Handles cache invalidation and transient purging.
 *
 * @package StorefrontZero
 * @since 1.0.0
 */

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
