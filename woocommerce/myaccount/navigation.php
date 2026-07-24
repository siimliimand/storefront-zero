<?php
/**
 * My Account navigation
 *
 * Storefront Zero theme override for woocommerce/myaccount/navigation.php
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/navigation.php.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package Storefront_Zero
 * @version 9.3.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_account_navigation' );
?>

<nav class="woocommerce-MyAccount-navigation" aria-label="<?php esc_html_e( 'Account pages', 'storefront-zero' ); ?>">
	<ul class="space-y-1" role="list">
		<?php foreach ( wc_get_account_menu_items() as $endpoint => $label ) : ?>
			<?php
			$item_classes = wc_get_account_menu_item_classes( $endpoint );
			$is_current   = wc_is_current_account_menu_item( $endpoint );
			?>

			<li class="<?php echo esc_attr( $item_classes ); ?>">
				<a
					href="<?php echo esc_url( wc_get_account_endpoint_url( $endpoint ) ); ?>"
				class="block px-4 py-2.5 rounded-lg text-sm font-medium transition-colors <?php echo $is_current
					? 'bg-brand-50 dark:bg-brand-900/30 text-brand-700 dark:text-brand-300 font-semibold'
					: 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-gray-100'; ?>"
					<?php echo $is_current ? 'aria-current="page"' : ''; ?>
				>
					<?php echo esc_html( $label ); ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>

<?php do_action( 'woocommerce_after_account_navigation' ); ?>
