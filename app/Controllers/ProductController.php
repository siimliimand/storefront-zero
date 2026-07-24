<?php
declare(strict_types=1);

/**
 * Product Controller
 *
 * Handles product-related HTMX fragment requests.
 *
 * @package Storefront_Zero
 */

namespace ThemeApp\Controllers;

use ThemeApp\View;

/**
 * ProductController manages product search and display via HTMX fragments.
 */
class ProductController
{
    /**
     * Live product search via HTMX.
     *
     * Sanitizes the query, queries WooCommerce for matching published products,
     * and renders the search-results view template.
     *
     * @return void
     */
    public static function liveSearch(): void
    {
        $query = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';

        if ( empty( $query ) ) {
            echo '';
            return;
        }

        $products = wc_get_products( [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => 5,
            's'              => $query,
        ] );

        View::render( 'search-results', [
            'products' => $products,
            'query'    => $query,
        ] );
    }
}
