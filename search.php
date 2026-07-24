<?php
/**
 * The template for displaying search results
 *
 * @package Storefront_Zero
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<div id="primary" class="content-area max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
	<main id="main" class="site-main">

		<header class="search-header mb-8">
			<h1 class="page-title text-3xl font-bold text-gray-900">
				<?php
				printf(
					/* translators: %s: search query */
					esc_html__( 'Search results for: %s', 'storefront-zero' ),
					'<span class="text-blue-600">' . esc_html( get_search_query() ) . '</span>'
				);
				?>
			</h1>
		</header>

		<?php if ( have_posts() ) : ?>

			<div class="search-results-list">
				<?php while ( have_posts() ) : the_post(); ?>

					<?php if ( locate_template( 'template-parts/content-search.php' ) ) : ?>
					<?php get_template_part( 'template-parts/content', 'search' ); ?>
				<?php else : ?>
					<article <?php post_class( 'mb-8 pb-8 border-b border-gray-200' ); ?>>
						<h2 class="entry-title text-xl font-semibold mb-2">
							<a href="<?php the_permalink(); ?>" class="text-blue-600 hover:text-blue-800 hover:underline">
								<?php the_title(); ?>
							</a>
						</h2>

						<div class="entry-meta flex items-center gap-3 text-sm text-gray-500 mb-3">
							<span class="post-type-badge inline-block px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
								<?php
								$post_type_obj = get_post_type_object( get_post_type() );
								echo esc_html( $post_type_obj->labels->singular_name );
								?>
							</span>

							<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>">
								<?php echo esc_html( get_the_date() ); ?>
							</time>
						</div>

						<div class="entry-summary text-gray-700 leading-relaxed">
							<?php the_excerpt(); ?>
						</div>
					</article>
				<?php endif; ?>

				<?php endwhile; ?>
			</div>

			<?php the_posts_pagination( [
				'mid_size'  => 2,
				'prev_text' => esc_html__( '&laquo; Previous', 'storefront-zero' ),
				'next_text' => esc_html__( 'Next &raquo;', 'storefront-zero' ),
				'class'     => 'pagination mt-8 flex justify-center space-x-2 text-sm',
			] ); ?>

		<?php else : ?>

			<?php get_template_part( 'template-parts/content', 'none' ); ?>

		<?php endif; ?>

	</main>
</div>

<?php
get_sidebar();
get_footer();
