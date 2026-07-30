<?php
/**
 * External product add-to-cart
 *
 * Template override for Storefront Zero theme with HTMX support.
 * Displays a "Buy product" button linking to the external product URL.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package storefront-zero
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_add_to_cart_form' ); ?>

<form
	class="cart flex items-center gap-4"
	action="<?php echo esc_url( $product_url ); ?>"
	method="get"
>

	<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>

	<button
		type="submit"
		class="single_add_to_cart_button button alt px-6 py-2 bg-brand-600 text-white font-semibold rounded hover:bg-brand-700 transition-colors cursor-pointer <?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>"
	>
		<?php echo esc_html( $button_text ); ?>
	</button>

	<?php wc_query_string_form_fields( $product_url ); ?>

	<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>

</form>

<div id="add-to-cart-response"></div>

<?php do_action( 'woocommerce_after_add_to_cart_form' ); ?>
