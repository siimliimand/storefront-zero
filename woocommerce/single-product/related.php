<?php
/**
 * Related Products
 *
 * Storefront Zero theme override for woocommerce/single-product/related.php
 * Displays related products in a responsive Tailwind CSS grid with
 * thumbnail, title, and price for each product.
 *
 * @see         https://woocommerce.com/document/template-structure/
 * @package     Storefront_Zero
 * @version     10.3.0
 *
 * @var WC_Product $product The current product object.
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product instanceof WC_Product ) {
	return;
}

$related_ids = wc_get_related_products( $product->get_id(), 4, $product->get_upsell_ids() );

if ( empty( $related_ids ) ) {
	return;
}

$heading = apply_filters(
	'woocommerce_product_related_products_heading',
	__( 'Related products', 'storefront-zero' )
);
?>

<section class="related-products py-12">
	<?php if ( $heading ) : ?>
		<h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-8">
			<?php echo esc_html( $heading ); ?>
		</h2>
	<?php endif; ?>

	<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
		<?php foreach ( $related_ids as $related_id ) :
			$related_product = wc_get_product( $related_id );

			if ( ! $related_product instanceof WC_Product ) {
				continue;
			}

			$post_object = get_post( $related_id );
			setup_postdata( $GLOBALS['post'] = $post_object ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			?>
			<div class="product-card group border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden transition-shadow hover:shadow-lg dark:bg-darkCard">
				<a href="<?php echo esc_url( $related_product->get_permalink() ); ?>"
				   class="block">
					<div class="aspect-square bg-gray-100 dark:bg-gray-800 overflow-hidden">
						<?php
						$thumbnail_url = get_the_post_thumbnail_url( $related_id, 'woocommerce_thumbnail' );

						if ( $thumbnail_url ) :
							?>
							<img src="<?php echo esc_url( $thumbnail_url ); ?>"
							     alt="<?php echo esc_attr( $related_product->get_name() ); ?>"
							     class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
							     loading="lazy" />
						<?php else : ?>
							<div class="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-500">
								<svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
									      d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
								</svg>
							</div>
						<?php endif; ?>
					</div>

					<div class="p-4">
						<h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 line-clamp-2 mb-2 group-hover:text-brand-600 transition-colors">
							<?php echo esc_html( $related_product->get_name() ); ?>
						</h3>

						<div class="text-base font-semibold text-gray-900 dark:text-gray-100">
							<?php echo wp_kses_post( $related_product->get_price_html() ); ?>
						</div>
					</div>
				</a>
			</div>
		<?php endforeach; ?>
	</div>
</section>
<?php
wp_reset_postdata();
