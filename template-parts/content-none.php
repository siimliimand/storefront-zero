<?php
/**
 * Template part for displaying a message when no posts are found.
 *
 * @package Storefront_Zero
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<section class="no-results not-found py-12 text-center">
	<header class="page-header mb-4">
		<h1 class="page-title text-2xl font-bold">
			<?php esc_html_e( 'Nothing Found', 'storefront-zero' ); ?>
		</h1>
	</header>

	<div class="page-content text-gray-600">
		<p><?php esc_html_e( 'It seems we can&rsquo;t find what you&rsquo;re looking for. Try searching?', 'storefront-zero' ); ?></p>
		<?php get_search_form(); ?>
	</div>
</section>
