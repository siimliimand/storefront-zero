<?php
/**
 * WooCommerce Success Notice Template
 *
 * Storefront Zero theme override for woocommerce/notices/success.php
 *
 * @package Storefront_Zero
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $notices ) ) {
    return;
}
?>
<?php foreach ( $notices as $notice ) : ?>
    <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300 rounded-lg p-4 mb-4 flex items-center" role="alert">
        <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
        </svg>
        <div><?php echo wp_kses_post( $notice ); ?></div>
    </div>
<?php endforeach; ?>
