<?php
/**
 * Simple product add-to-cart
 *
 * Template override for Storefront Zero theme with HTMX support.
 * Displays quantity input and add-to-cart button for simple products.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package storefront-zero
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product->is_purchasable() ) {
	return;
}

echo wc_get_stock_html( $product ); // WPCS: XSS ok.

if ( $product->is_in_stock() ) :
	$add_to_cart_url = esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) );
	$product_id      = absint( $product->get_id() );

	do_action( 'woocommerce_before_add_to_cart_form' );
	?>

	<form
		class="cart flex flex-wrap items-end gap-4"
		action="<?php echo $add_to_cart_url; ?>"
		method="post"
		enctype="multipart/form-data"
		hx-post="<?php echo $add_to_cart_url; ?>"
		hx-target="#add-to-cart-response"
		hx-swap="innerHTML"
		hx-indicator="#add-to-cart-spinner"
	>
		<input type="hidden" name="product_id" value="<?php echo esc_attr( $product_id ); ?>">
		<?php wp_nonce_field( 'woocommerce_add_to_cart', 'woocommerce-add-to-cart-nonce' ); ?>

		<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>

		<div class="flex flex-col gap-1">
			<?php
			do_action( 'woocommerce_before_add_to_cart_quantity' );

			woocommerce_quantity_input(
				array(
					'min_value'   => $product->get_min_purchase_quantity(),
					'max_value'   => $product->get_max_purchase_quantity(),
					'input_value' => isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : $product->get_min_purchase_quantity(), // WPCS: CSRF ok, input var ok.
				)
			);

			do_action( 'woocommerce_after_add_to_cart_quantity' );
			?>
		</div>

		<button
			type="submit"
			name="add-to-cart"
			value="<?php echo esc_attr( $product_id ); ?>"
			class="single_add_to_cart_button button alt px-6 py-2 bg-brand-600 text-white font-semibold rounded hover:bg-brand-700 transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed <?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>"
		>
			<?php echo esc_html( $product->single_add_to_cart_text() ); ?>
		</button>

		<span id="add-to-cart-spinner" class="htmx-indicator inline-block ml-2">
			<span class="inline-block w-5 h-5 border-2 border-gray-300 dark:border-gray-600 border-t-brand-600 rounded-full animate-spin"></span>
		</span>

		<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>
	</form>

	<div id="add-to-cart-response"></div>

	<?php do_action( 'woocommerce_after_add_to_cart_form' ); ?>

<?php endif; ?>
