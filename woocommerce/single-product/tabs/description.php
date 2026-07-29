<?php
/**
 * WooCommerce Product Description Tab
 *
 * Storefront Zero theme override for woocommerce/single-product/tabs/description.php
 *
 * @package Storefront_Zero
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

global $post;
?>

<div class="woocommerce-product-description">
	<?php the_content(); ?>
</div>
