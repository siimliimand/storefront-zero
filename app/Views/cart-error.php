<?php
/**
 * View template for cart error messages.
 *
 * Available variables: $message (string).
 *
 * @package Storefront_Zero
 */
?>
<div class="cart-error p-4 bg-red-50 border border-red-200 rounded text-red-700 text-sm">
    <p><?php echo esc_html( $message ); ?></p>
</div>
