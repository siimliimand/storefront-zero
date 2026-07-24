<?php
/**
 * WooCommerce Error Notice Template
 *
 * Storefront Zero theme override for woocommerce/notices/error.php
 *
 * @package Storefront_Zero
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $notices ) ) {
    return;
}
?>
<?php foreach ( $notices as $notice ) : ?>
    <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 rounded-lg p-4 mb-4 flex items-center" role="alert">
        <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
        </svg>
        <div><?php echo wp_kses_post( $notice ); ?></div>
    </div>
<?php endforeach; ?>
