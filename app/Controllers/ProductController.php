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

use Flight;
use ThemeApp\View;

/**
 * ProductController manages product search and display via HTMX fragments.
 */
class ProductController
{
    /**
     * View instance for rendering templates.
     *
     * @var \ThemeApp\View
     */
    private View $view;

    /**
     * Constructor. Injected by the DI container.
     *
     * @param \ThemeApp\View $view View renderer.
     */
    public function __construct( View $view )
    {
        $this->view = $view;
    }

    /**
     * Live product search via HTMX.
     *
     * Sanitizes the query, queries WooCommerce for matching published products,
     * and renders the search-results view template. Product IDs are cached in
     * a transient with a 60-second TTL; full WC_Product objects are hydrated
     * on each request to avoid serialization issues across WC version changes.
     *
     * @return void
     */
    public function liveSearch(): void
    {
        header( 'Content-Type: text/html; charset=utf-8' );

        $query = isset( Flight::request()->query['s'] ) ? sanitize_text_field( wp_unslash( Flight::request()->query['s'] ) ) : '';

        if ( empty( $query ) ) {
            echo '';
            return;
        }

        $cache_key = 'sz_search_' . hash( 'xxh3', $query );
        $products  = get_transient( $cache_key );

        if ( false === $products ) {
            $query_result = new \WP_Query( [
                's'              => $query,
                'post_type'      => 'product',
                'post_status'    => 'publish',
                'posts_per_page' => 5,
                'search_columns' => [ 'post_title' ],
                'fields'         => 'ids',
            ] );

            $product_ids = $query_result->posts;

            set_transient( $cache_key, $product_ids, 60 );
            $products = $product_ids;
        }

        // Hydrate product IDs into WC_Product objects via a single batch query (avoids N+1).
        // Short-circuit on empty array: wc_get_products(['include' => []]) returns ALL products.
        if ( empty( $products ) ) {
            $products = [];
        } else {
            $products = array_filter(
                wc_get_products( [
                    'include' => $products,
                    'return'  => 'objects',
                ] )
            );
        }

        $this->view->render( 'search-results', [
            'products' => $products,
            'query'    => $query,
        ] );
    }
}
