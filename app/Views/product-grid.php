<?php
declare(strict_types=1);
/**
 * View template for the filtered product grid.
 *
 * Renders a WooCommerce product loop suitable for HTMX swap.
 * Available variables: $products (WP_Post[]), $query (WP_Query).
 *
 * @package Storefront_Zero
 */

/** @var array<int, \WP_Post> $products */
/** @var \WP_Query $query */

if ( empty( $products ) ) :
?>
<div id="product-grid" class="product-grid-empty py-8 text-center text-gray-500 dark:text-gray-400">
	<p><?php esc_html_e( 'No products found matching your selection.', 'storefront-zero' ); ?></p>
</div>
<?php else : ?>
<div id="product-grid"
	 class="products"
	 hx-swap="innerHTML"
	 hx-target="this">
	<?php
	woocommerce_product_loop_start();

	foreach ( $products as $post ) :
		setup_postdata( $post );
		wc_get_template_part( 'content', 'product' );
	endforeach;

	woocommerce_product_loop_end();
	?>
</div>
<?php
endif;

wp_reset_postdata();
