<?php
/**
 * Template part for displaying page content.
 *
 * @package Storefront_Zero
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<article <?php post_class( 'mb-8' ); ?>>
	<header class="entry-header mb-4">
		<h1 class="entry-title text-2xl font-bold">
			<?php the_title(); ?>
		</h1>
	</header>

	<div class="entry-content text-gray-700">
		<?php
		the_content();

		wp_link_pages( [
			'before' => '<div class="page-links mt-4">',
			'after'  => '</div>',
		] );
		?>
	</div>
</article>
