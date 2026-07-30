<?php
/**
 * WooCommerce Product Archive Template
 *
 * Storefront Zero theme override for woocommerce/archive-product.php
 *
 * @package Storefront_Zero
 * @version 8.6.0
 */

get_header();
?>

<div id="primary" class="content-area">
	<main id="main" class="site-main">
		<?php do_action( 'woocommerce_before_main_content' ); ?>

		<?php if ( have_posts() ) : ?>

			<header class="woocommerce-products-header">
				<?php woocommerce_output_all_notices(); ?>

				<?php if ( is_product_category() || is_product_tag() ) : ?>
					<h1 class="woocommerce-products-header__title page-title">
						<?php woocommerce_page_title(); ?>
					</h1>
				<?php endif; ?>
			</header>

		<div id="product-grid"
			 class="products"
			 hx-swap="innerHTML"
			 hx-target="this">

		<?php woocommerce_product_loop_start(); ?>

			<?php while ( have_posts() ) : the_post(); ?>
				<?php wc_get_template_part( 'content', 'product' ); ?>
			<?php endwhile; ?>

		<?php woocommerce_product_loop_end(); ?>

		</div>

		<?php woocommerce_pagination(); ?>

		<?php else : ?>
			<p><?php esc_html_e( 'No products found matching your selection.', 'storefront-zero' ); ?></p>
		<?php endif; ?>

		<?php do_action( 'woocommerce_after_main_content' ); ?>
	</main>
</div>

<?php
get_footer();
