<?php
/**
 * WooCommerce Single Product Template
 *
 * Storefront Zero theme override for woocommerce/single-product.php
 *
 * @package Storefront_Zero
 * @version 1.6.4
 */

get_header();
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        <?php do_action( 'woocommerce_before_main_content' ); ?>

        <?php
        while ( have_posts() ) :
            the_post();
            wc_get_template_part( 'content', 'single-product' );
        endwhile;
        ?>

        <?php do_action( 'woocommerce_after_main_content' ); ?>
    </main>
</div>

<?php
get_footer();
