<?php
/**
 * Template part for displaying posts.
 *
 * @package Storefront_Zero
 */

?>

<article <?php post_class( 'mb-8' ); ?>>
	<header class="entry-header mb-2">
		<h2 class="entry-title text-xl font-bold">
			<a href="<?php the_permalink(); ?>" rel="bookmark">
				<?php the_title(); ?>
			</a>
		</h2>
	</header>

	<div class="entry-summary text-gray-700 dark:text-gray-300">
		<?php the_excerpt(); ?>
	</div>

	<footer class="entry-footer mt-2">
		<a href="<?php the_permalink(); ?>" class="text-brand-600 hover:underline" aria-label="<?php printf( esc_attr__( 'Read more: %s', 'storefront-zero' ), get_the_title() ); ?>">
			<?php esc_html_e( 'Read more', 'storefront-zero' ); ?> &rarr;
		</a>
	</footer>
</article>
