<?php
/**
 * The archive template
 *
 * Displays category, tag, author, and date archives.
 *
 * @package Storefront_Zero
 */

get_header();
?>

<div id="primary" class="content-area">
	<main id="main" class="site-main">
		<?php if ( have_posts() ) : ?>

			<header class="archive-header mb-8">
				<?php the_archive_title( '<h1 class="archive-title text-3xl font-bold text-gray-900 mb-2">', '</h1>' ); ?>
				<?php the_archive_description( '<div class="archive-description text-gray-600">', '</div>' ); ?>
			</header>

			<?php while ( have_posts() ) : the_post(); ?>
				<?php get_template_part( 'template-parts/content', get_post_type() ); ?>
			<?php endwhile; ?>

			<?php the_posts_pagination(); ?>

		<?php else : ?>
			<?php get_template_part( 'template-parts/content', 'none' ); ?>
		<?php endif; ?>
	</main>
</div>

<?php
get_sidebar();
get_footer();
