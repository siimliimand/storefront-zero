<?php
declare(strict_types=1);
/**
 * Full cart page fragment for HTMX swap.
 *
 * Rendered by CartController::renderCartPage() — outputs the complete
 * cart form (table, quantities, coupons, totals) for hx-target="#cart-content".
 *
 * @package Storefront_Zero
 */

wc_get_template( 'cart/cart.php' );
