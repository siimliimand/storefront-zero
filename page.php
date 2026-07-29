<?php
/**
 * The template for displaying all pages.
 *
 * This is the template that displays all static pages by default.
 * It uses the WordPress template hierarchy: for any page request,
 * WordPress loads page.php (or a more specific template like
 * page-{slug}.php or page-{id}.php if they exist).
 *
 * @package Storefront_Zero
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<div id="primary" class="content-area">
	<main id="main" class="site-main">
		<?php
		while ( have_posts() ) :
			the_post();
			get_template_part( 'template-parts/content', 'page' );

			// If there are comments, load them.
			if ( comments_open() || get_comments_number() ) :
				comments_template();
			endif;

			// Post navigation for multi-page posts.
			wp_link_pages( [
				'before' => '<div class="page-links mt-4 text-sm text-gray-600">',
				'after'  => '</div>',
			] );
		endwhile;
		?>
	</main>
</div>

<?php
get_sidebar();
get_footer();
