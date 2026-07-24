<?php
/**
 * WooCommerce Product Archive Template
 *
 * Storefront Zero theme override for woocommerce/archive-product.php
 *
 * @package Storefront_Zero
 */

get_header( 'shop' );
?>

<div id="primary" class="content-area">
	<main id="main" class="site-main">
		<?php if ( have_posts() ) : ?>

			<header class="woocommerce-products-header">
				<?php woocommerce_output_all_notices(); ?>

				<?php if ( is_product_category() || is_product_tag() ) : ?>
					<h1 class="woocommerce-products-header__title page-title">
						<?php woocommerce_page_title(); ?>
					</h1>
				<?php endif; ?>
			</header>

			<?php woocommerce_product_loop_start(); ?>

				<?php while ( have_posts() ) : the_post(); ?>
					<?php wc_get_template_part( 'content', 'product' ); ?>
				<?php endwhile; ?>

			<?php woocommerce_product_loop_end(); ?>

			<?php woocommerce_pagination(); ?>

		<?php else : ?>
			<p><?php esc_html_e( 'No products found matching your selection.', 'storefront-zero' ); ?></p>
		<?php endif; ?>
	</main>
</div>

<?php
get_footer( 'shop' );
