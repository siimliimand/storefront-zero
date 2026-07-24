<?php
/**
 * WooCommerce Product Additional Information Tab
 *
 * Storefront Zero theme override for woocommerce/single-product/tabs/additional-information.php
 *
 * @package Storefront_Zero
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

$attributes = $product->get_attributes();
?>

<div class="woocommerce-product-attributes">
	<?php if ( $attributes ) : ?>
		<table class="w-full border-collapse">
			<tbody>
				<?php foreach ( $attributes as $attribute ) : ?>
					<?php if ( $attribute->is_visible() ) : ?>
						<tr class="border-b border-gray-200 dark:border-gray-700">
							<th class="py-3 pr-6 text-left font-semibold text-gray-900 dark:text-gray-100 w-1/3">
								<?php echo esc_html( wc_attribute_label( $attribute->get_name() ) ); ?>
							</th>
							<td class="py-3 text-gray-700 dark:text-gray-300">
								<?php
								$values = array();

								if ( $attribute->is_taxonomy() ) {
									$terms = wp_get_post_terms( $product->get_id(), $attribute->get_name(), 'all' );
									if ( $terms ) {
										foreach ( $terms as $term ) {
											$values[] = $term->name;
										}
									}
								} else {
									$values = $attribute->get_options();
								}

								echo esc_html( implode( ', ', $values ) );
								?>
							</td>
						</tr>
					<?php endif; ?>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php else : ?>
		<p class="text-gray-500 dark:text-gray-400 italic">
			<?php esc_html_e( 'No additional information available.', 'storefront-zero' ); ?>
		</p>
	<?php endif; ?>
</div>
