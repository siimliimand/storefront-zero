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

        $cache_key = 'sz_search_' . md5( $query );
        $products  = get_transient( $cache_key );

        if ( false === $products ) {
            $product_ids = wc_get_products( [
                'post_type'      => 'product',
                'post_status'    => 'publish',
                'posts_per_page' => 5,
                's'              => $query,
                'return'         => 'ids',
            ] );

            set_transient( $cache_key, $product_ids, 60 );
            $products = $product_ids;
        }

        // Hydrate product IDs into WC_Product objects.
        $products = array_filter( array_map( static fn( int $id ): ?\WC_Product => wc_get_product( $id ), $products ) );

        $this->view->render( 'search-results', [
            'products' => $products,
            'query'    => $query,
        ] );
    }
}
