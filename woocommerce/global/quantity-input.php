<?php
/**
 * WooCommerce Quantity Input Template
 *
 * Storefront Zero theme override for woocommerce/global/quantity-input.php
 * Provides a quantity stepper with +/- buttons.
 *
 * @package Storefront_Zero
 *
 * @var bool   $input_only  Whether to show input only (no buttons).
 * @var string $input_name  Input name attribute.
 * @var int    $input_value Current quantity value.
 * @var string $input_id    Input ID attribute.
 * @var string $input_class Additional input CSS classes.
 * @var string $max         Maximum quantity.
 * @var string $min         Minimum quantity.
 * @var string $step        Step increment.
 */

defined( 'ABSPATH' ) || exit;

if ( $input_only ) {
    echo '<input type="number" class="form-input w-16 text-center border rounded px-2 py-1 ' . esc_attr( $input_class ) . '" step="' . esc_attr( $step ) . '" min="' . esc_attr( $min ) . '" max="' . esc_attr( $max > 0 ? $max : '' ) . '" name="' . esc_attr( $input_name ) . '" value="' . esc_attr( $input_value ) . '" id="' . esc_attr( $input_id ) . '" inputmode="numeric" autocomplete="off" />';
    return;
}
?>
<div class="quantity flex items-center border rounded overflow-hidden">
    <button type="button" class="qty-change bg-gray-100 hover:bg-gray-200 px-3 py-1 text-lg font-bold transition" data-action="minus" aria-label="<?php esc_attr_e( 'Decrease quantity', 'storefront-zero' ); ?>">&minus;</button>
    <input type="number" class="form-input w-16 text-center border-x border-gray-200 py-1 <?php echo esc_attr( $input_class ); ?>" step="<?php echo esc_attr( $step ); ?>" min="<?php echo esc_attr( $min ); ?>" max="<?php echo esc_attr( $max > 0 ? $max : '' ); ?>" name="<?php echo esc_attr( $input_name ); ?>" value="<?php echo esc_attr( $input_value ); ?>" id="<?php echo esc_attr( $input_id ); ?>" inputmode="numeric" autocomplete="off" />
    <button type="button" class="qty-change bg-gray-100 hover:bg-gray-200 px-3 py-1 text-lg font-bold transition" data-action="plus" aria-label="<?php esc_attr_e( 'Increase quantity', 'storefront-zero' ); ?>">+</button>
</div>
