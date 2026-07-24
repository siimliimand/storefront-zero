<?php
declare(strict_types=1);
/**
 * Mini-cart HTML fragment for HTMX swap.
 *
 * Rendered by CartController::renderMiniCart() and swapped into
 * #mini-cart-container via hx-get="/htmx-api/cart/mini".
 *
 * @package Storefront_Zero
 */

$cart_count = WC()->cart->get_cart_contents_count();
$cart_total = WC()->cart->get_cart_subtotal();
?>
<div id="mini-cart-container"
     hx-get="/htmx-api/cart/mini"
     hx-trigger="load, cartUpdated from:body"
     hx-swap="outerHTML"
     aria-live="polite"
     class="relative">
    <a href="<?php echo esc_url( wc_get_cart_url() ); ?>"
       class="relative flex items-center text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100"
       aria-label="<?php printf( esc_attr__( 'Cart (%d items)', 'storefront-zero' ), $cart_count ); ?>">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"></path>
        </svg>
        <?php if ( $cart_count > 0 ) : ?>
            <span class="absolute -top-2 -right-2 bg-brand-600 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center">
                <?php echo esc_html( $cart_count ); ?>
            </span>
        <?php endif; ?>
    </a>
</div>
