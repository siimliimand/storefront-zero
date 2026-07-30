<?php

declare(strict_types=1);

/**
 * Theme setup: supports, menus, and performance tweaks.
 *
 * @package StorefrontZero
 * @since 1.0.0
 */

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
