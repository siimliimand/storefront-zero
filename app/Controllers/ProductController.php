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
use ThemeApp\Concerns\FlushesWcNotices;
use ThemeApp\View;

/**
 * ProductController manages product search and display via HTMX fragments.
 */
class ProductController
{
    use FlushesWcNotices;
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

        $generation = (int) get_option( 'sz_search_generation', 0 );
        $cache_key = 'sz_search_' . $generation . '_' . hash( 'xxh3', $query );
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

    /**
     * Faceted product filter via HTMX.
     *
     * Accepts query parameters (category, attribute, min_price, max_price,
     * orderby, order), sanitises them, builds a WP_Query with the assembled
     * arguments, and renders the product-grid HTML fragment.
     *
     * @return void
     */
    public function filterProducts(): void
    {
        header( 'Content-Type: text/html; charset=utf-8' );

        $request = Flight::request();

        // Sanitise all inputs.
        $category = isset( $request->query['category'] )
            ? sanitize_text_field( wp_unslash( $request->query['category'] ) )
            : '';

        $min_price = isset( $request->query['min_price'] )
            ? absint( $request->query['min_price'] )
            : 0;

        $max_price = isset( $request->query['max_price'] )
            ? absint( $request->query['max_price'] )
            : 0;

        $orderby = isset( $request->query['orderby'] )
            ? sanitize_text_field( wp_unslash( $request->query['orderby'] ) )
            : 'date';

        $order = isset( $request->query['order'] )
            ? strtoupper( sanitize_text_field( wp_unslash( $request->query['order'] ) ) )
            : 'DESC';

        // Whitelist sort options.
        $allowed_orderby = [ 'date', 'price', 'title', 'popularity', 'rating', 'relevance' ];
        if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
            $orderby = 'date';
        }

        $allowed_order = [ 'ASC', 'DESC' ];
        if ( ! in_array( $order, $allowed_order, true ) ) {
            $order = 'DESC';
        }

        // Collect attribute filters (filter_color, filter_size, etc.).
        $attributes = [];
        foreach ( $request->query as $key => $value ) {
            if ( 0 !== strpos( $key, 'filter_' ) || empty( $value ) ) {
                continue;
            }

            $slug           = sanitize_text_field( wp_unslash( substr( $key, 7 ) ) );
            $attribute_value = sanitize_text_field( wp_unslash( $value ) );

            // Validate the taxonomy exists. WooCommerce attributes use the pa_ prefix.
            if ( taxonomy_exists( $slug ) ) {
                $attributes[ $slug ] = $attribute_value;
            } elseif ( taxonomy_exists( 'pa_' . $slug ) ) {
                $attributes[ 'pa_' . $slug ] = $attribute_value;
            }
        }

        // Build query arguments.
        $query_args = [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => absint( get_option( 'posts_per_page', 12 ) ),
            'orderby'        => $orderby,
            'order'          => $order,
        ];

        // Map WooCommerce orderby values to WP_Query equivalents.
        if ( 'price' === $orderby ) {
            $query_args['meta_key'] = '_price';
            $query_args['orderby']  = 'meta_value_num';
        } elseif ( 'popularity' === $orderby ) {
            $query_args['meta_key'] = 'total_sales';
            $query_args['orderby']  = 'meta_value_num';
        } elseif ( 'rating' === $orderby ) {
            $query_args['meta_key'] = '_wc_average_rating';
            $query_args['orderby']  = 'meta_value_num';
        } elseif ( 'relevance' === $orderby ) {
            $query_args['orderby'] = 'relevance';
        }

        // Category filter.
        if ( ! empty( $category ) ) {
            $query_args['tax_query'] = [
                [
                    'taxonomy' => 'product_cat',
                    'field'    => 'slug',
                    'terms'    => $category,
                ],
            ];
        }

        // Attribute filters.
        if ( ! empty( $attributes ) ) {
            $attribute_tax_query = [];
            foreach ( $attributes as $attr_key => $attr_value ) {
                $attribute_tax_query[] = [
                    'taxonomy' => $attr_key,
                    'field'    => 'slug',
                    'terms'    => $attr_value,
                ];
            }

            if ( isset( $query_args['tax_query'] ) ) {
                $query_args['tax_query']['relation'] = 'AND';
                $query_args['tax_query']             = array_merge(
                    $query_args['tax_query'],
                    $attribute_tax_query
                );
            } else {
                $query_args['tax_query'] = $attribute_tax_query;
            }
        }

        // Price range filter.
        if ( $min_price > 0 || $max_price > 0 ) {
            $price_meta_query = [ 'relation' => 'AND' ];

            if ( $min_price > 0 ) {
                $price_meta_query[] = [
                    'key'     => '_price',
                    'value'   => $min_price,
                    'compare' => '>=',
                    'type'    => 'NUMERIC',
                ];
            }

            if ( $max_price > 0 ) {
                $price_meta_query[] = [
                    'key'     => '_price',
                    'value'   => $max_price,
                    'compare' => '<=',
                    'type'    => 'NUMERIC',
                ];
            }

            $query_args['meta_query'] = $price_meta_query;
        }

        // Pass all query args directly to WP_Query — no pre_get_posts hook needed.
        $product_query = new \WP_Query( $query_args );

        // Render the product grid.
        $this->view->render( 'product-grid', [
            'products' => $product_query->posts,
            'query'    => $product_query,
        ] );

        echo $this->flush_wc_notices();
    }
}
