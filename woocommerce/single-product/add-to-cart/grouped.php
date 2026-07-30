<?php
/**
 * Grouped product add-to-cart
 *
 * Template override for Storefront Zero theme with HTMX support.
 * Displays a table of child products with quantity inputs for grouped products.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package storefront-zero
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

global $product, $post;

do_action( 'woocommerce_before_add_to_cart_form' ); ?>

<form
	class="cart grouped_form flex flex-col gap-4"
	action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>"
	method="post"
	enctype="multipart/form-data"
	hx-post="<?php echo esc_url( home_url( '/htmx-api/cart/add' ) ); ?>"
	hx-target="#mini-cart-container"
	hx-swap="outerHTML"
	hx-indicator="#add-to-cart-spinner"
>

	<?php echo wc_get_stock_html( $product ); // WPCS: XSS ok. ?>

	<table class="w-full text-left border-collapse">
		<thead>
			<tr class="border-b border-gray-200 dark:border-gray-700">
				<th class="py-3 text-sm font-semibold text-gray-900 dark:text-gray-100">
					<?php esc_html_e( 'Product', 'storefront-zero' ); ?>
				</th>
				<th class="py-3 text-sm font-semibold text-gray-900 dark:text-gray-100">
					<?php esc_html_e( 'Price', 'storefront-zero' ); ?>
				</th>
				<th class="py-3 text-sm font-semibold text-gray-900 dark:text-gray-100">
					<?php esc_html_e( 'Qty', 'storefront-zero' ); ?>
				</th>
			</tr>
		</thead>
		<tbody>
			<?php
			$quantities_required      = false;
			$previous_post            = $post;
			$grouped_product_columns  = apply_filters(
				'woocommerce_grouped_product_columns',
				array(
					'label',
					'price',
					'quantity',
				),
				$product
			);
			$show_add_to_cart_button = false;

			do_action( 'woocommerce_grouped_product_list_before', $grouped_product_columns, $quantities_required, $product );

			foreach ( $grouped_products as $grouped_product_child ) :
				$post_object        = get_post( $grouped_product_child->get_id() );
				$quantities_required = $quantities_required || ( $grouped_product_child->is_purchasable() && ! $grouped_product_child->has_options() );
				$post               = $post_object; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				setup_postdata( $post );

				if ( $grouped_product_child->is_in_stock() ) {
					$show_add_to_cart_button = true;
				}

				$classes = implode( ' ', wc_get_product_class( '', $grouped_product_child ) );
				?>
				<tr
					id="product-<?php echo esc_attr( $grouped_product_child->get_id() ); ?>"
					class="woocommerce-grouped-product-list-item <?php echo esc_attr( $classes ); ?> border-b border-gray-100 dark:border-gray-800"
				>
					<?php
					foreach ( $grouped_product_columns as $column_id ) :
						do_action( 'woocommerce_grouped_product_list_before_' . $column_id, $grouped_product_child );

						echo '<td class="woocommerce-grouped-product-list-item__' . esc_attr( $column_id ) . ' py-3">';

						switch ( $column_id ) :
							case 'quantity':
								ob_start();

								if ( ! $grouped_product_child->is_purchasable() || $grouped_product_child->has_options() || ! $grouped_product_child->is_in_stock() ) {
									woocommerce_template_loop_add_to_cart();
								} elseif ( $grouped_product_child->is_sold_individually() ) {
									echo '<input type="checkbox" name="' . esc_attr( 'quantity[' . $grouped_product_child->get_id() . ']' ) . '" value="1" class="wc-grouped-product-add-to-cart-checkbox rounded border-gray-300 dark:border-gray-600 text-brand-600 focus:ring-brand-500" id="' . esc_attr( 'quantity-' . $grouped_product_child->get_id() ) . '" />';
									echo '<label for="' . esc_attr( 'quantity-' . $grouped_product_child->get_id() ) . '" class="sr-only">';
									if ( $grouped_product_child->is_on_sale() ) {
										printf(
											/* translators: %1$s: Product name. %2$s: Sale price. %3$s: Regular price */
											esc_html__( 'Buy one of %1$s on sale for %2$s, original price was %3$s', 'storefront-zero' ),
											esc_html( $grouped_product_child->get_name() ),
											esc_html( wp_strip_all_tags( wc_price( $grouped_product_child->get_price() ) ) ),
											esc_html( wp_strip_all_tags( wc_price( $grouped_product_child->get_regular_price() ) ) )
										);
									} else {
										printf(
											/* translators: %1$s: Product name. %2$s: Product price */
											esc_html__( 'Buy one of %1$s for %2$s', 'storefront-zero' ),
											esc_html( $grouped_product_child->get_name() ),
											esc_html( wp_strip_all_tags( wc_price( $grouped_product_child->get_price() ) ) )
										);
									}
									echo '</label>';
								} else {
									do_action( 'woocommerce_before_add_to_cart_quantity' );

									woocommerce_quantity_input(
										array(
											'input_name'  => 'quantity[' . $grouped_product_child->get_id() . ']',
											'input_value' => isset( $_POST['quantity'][ $grouped_product_child->get_id() ] ) ? wc_stock_amount( wc_clean( wp_unslash( $_POST['quantity'][ $grouped_product_child->get_id() ] ) ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
											'min_value'   => apply_filters( 'woocommerce_quantity_input_min', 0, $grouped_product_child ),
											'max_value'   => $grouped_product_child->get_max_purchase_quantity(),
											'placeholder' => '0',
										)
									);

									do_action( 'woocommerce_after_add_to_cart_quantity' );
								}

								$value = ob_get_clean();
								break;

							case 'label':
								$value  = '<label for="product-' . esc_attr( $grouped_product_child->get_id() ) . '" class="text-sm font-medium text-gray-900 dark:text-gray-100">';
								$value .= $grouped_product_child->is_visible() ? '<a href="' . esc_url( apply_filters( 'woocommerce_grouped_product_list_link', $grouped_product_child->get_permalink(), $grouped_product_child->get_id() ) ) . '" class="hover:text-brand-600 transition-colors">' . esc_html( $grouped_product_child->get_name() ) . '</a>' : esc_html( $grouped_product_child->get_name() );
								$value .= '</label>';
								break;

							case 'price':
								$value = '<span class="text-sm text-gray-700 dark:text-gray-300">' . wp_kses_post( $grouped_product_child->get_price_html() ) . '</span>' . wc_get_stock_html( $grouped_product_child );
								break;

							default:
								$value = '';
								break;
						endswitch;

						echo apply_filters( 'woocommerce_grouped_product_list_column_' . $column_id, $value, $grouped_product_child ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

						echo '</td>';

						do_action( 'woocommerce_grouped_product_list_after_' . $column_id, $grouped_product_child );
					endsforeach;
					?>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<?php
	$post = $previous_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	setup_postdata( $post );

	do_action( 'woocommerce_grouped_product_list_after', $grouped_product_columns, $quantities_required, $product );
	?>

	<input type="hidden" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>" />
	<?php wp_nonce_field( 'woocommerce_add_to_cart', 'woocommerce-add-to-cart-nonce' ); ?>

	<?php if ( $quantities_required && $show_add_to_cart_button ) : ?>

		<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>

		<div class="flex items-center gap-4 mt-2">
			<button
				type="submit"
				class="single_add_to_cart_button button alt px-6 py-2 bg-brand-600 text-white font-semibold rounded hover:bg-brand-700 transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed <?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>"
			>
				<?php echo esc_html( $product->single_add_to_cart_text() ); ?>
			</button>

			<span id="add-to-cart-spinner" class="htmx-indicator">
				<span class="inline-block w-5 h-5 border-2 border-gray-300 dark:border-gray-600 border-t-brand-600 rounded-full animate-spin"></span>
			</span>
		</div>

		<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>

	<?php endif; ?>
</form>

<div id="add-to-cart-response"></div>

<?php do_action( 'woocommerce_after_add_to_cart_form' ); ?>
