<?php
/**
 * My Account Dashboard
 *
 * Storefront Zero theme override for woocommerce/myaccount/dashboard.php
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/dashboard.php.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package Storefront_Zero
 * @version 4.4.0
 */

defined( 'ABSPATH' ) || exit;

$allowed_html = array(
	'a' => array(
		'href' => array(),
	),
);
?>

<div class="space-y-6">
	<p class="text-base text-gray-700 dark:text-gray-300">
		<?php
		printf(
			/* translators: 1: user display name 2: logout url */
			wp_kses( __( 'Hello %1$s (not %1$s? <a href="%2$s" class="text-brand-600 dark:text-brand-400 hover:text-brand-800 dark:hover:text-brand-200 underline">Log out</a>)', 'storefront-zero' ), $allowed_html ),
			'		<strong class="font-semibold text-gray-900 dark:text-gray-100">' . esc_html( $current_user->display_name ) . '</strong>',
			esc_url( wc_logout_url() )
		);
		?>
	</p>

	<p class="text-base text-gray-600 dark:text-gray-400 leading-relaxed">
		<?php
		/* translators: 1: Orders URL 2: Address URL 3: Account URL. */
		$dashboard_desc = __( 'From your account dashboard you can view your <a href="%1$s" class="text-brand-600 dark:text-brand-400 hover:text-brand-800 dark:hover:text-brand-200 underline">recent orders</a>, manage your <a href="%2$s" class="text-brand-600 dark:text-brand-400 hover:text-brand-800 dark:hover:text-brand-200 underline">billing address</a>, and <a href="%3$s" class="text-brand-600 dark:text-brand-400 hover:text-brand-800 dark:hover:text-brand-200 underline">edit your password and account details</a>.', 'storefront-zero' );
		if ( wc_shipping_enabled() ) {
			/* translators: 1: Orders URL 2: Addresses URL 3: Account URL. */
			$dashboard_desc = __( 'From your account dashboard you can view your <a href="%1$s" class="text-brand-600 dark:text-brand-400 hover:text-brand-800 dark:hover:text-brand-200 underline">recent orders</a>, manage your <a href="%2$s" class="text-brand-600 dark:text-brand-400 hover:text-brand-800 dark:hover:text-brand-200 underline">shipping and billing addresses</a>, and <a href="%3$s" class="text-brand-600 dark:text-brand-400 hover:text-brand-800 dark:hover:text-brand-200 underline">edit your password and account details</a>.', 'storefront-zero' );
		}
		printf(
			wp_kses( $dashboard_desc, $allowed_html ),
			esc_url( wc_get_endpoint_url( 'orders' ) ),
			esc_url( wc_get_endpoint_url( 'edit-address' ) ),
			esc_url( wc_get_endpoint_url( 'edit-account' ) )
		);
		?>
	</p>

	<?php
	/**
	 * My Account dashboard.
	 *
	 * @since 2.6.0
	 */
	do_action( 'woocommerce_account_dashboard' );

	/**
	 * Deprecated woocommerce_before_my_account action.
	 *
	 * @deprecated 2.6.0
	 */
	do_action( 'woocommerce_before_my_account' );

	/**
	 * Deprecated woocommerce_after_my_account action.
	 *
	 * @deprecated 2.6.0
	 */
	do_action( 'woocommerce_after_my_account' );
	?>
</div>

<?php
/* Omit closing PHP tag at the end of PHP files to avoid "headers already sent" issues. */
