<?php

declare(strict_types=1);

/**
 * WooCommerce-specific optimizations.
 *
 * Handles asset dequeuing (replaced by HTMX) and lazy image filtering
 * for WC product pages.
 *
 * @package StorefrontZero
 */

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

	// Non-cart/checkout pages (shop, product, archive, front page, standard pages).

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
