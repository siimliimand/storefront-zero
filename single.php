<?php
/**
 * The template for displaying single posts
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

		<?php while ( have_posts() ) : the_post(); ?>

			<article <?php post_class( 'mb-12' ); ?>>

				<header class="entry-header mb-6">
					<h1 class="entry-title text-3xl font-bold text-gray-900">
						<?php the_title(); ?>
					</h1>

					<div class="entry-meta mt-3 text-sm text-gray-500">
						<span class="posted-on">
							<?php
							printf(
								/* translators: %s: post date */
								esc_html__( 'Posted on %s', 'storefront-zero' ),
								'<time datetime="' . esc_attr( get_the_date( DATE_W3C ) ) . '">' . esc_html( get_the_date() ) . '</time>'
							);
							?>
						</span>

						<span class="mx-2" aria-hidden="true">&middot;</span>

						<span class="byline">
							<?php
							printf(
								/* translators: %s: post author */
								esc_html__( 'By %s', 'storefront-zero' ),
								'<span class="author vcard"><a class="url fn n text-blue-600 hover:underline" href="' . esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ) . '">' . esc_html( get_the_author() ) . '</a></span>'
							);
							?>
						</span>
					</div>
				</header>

				<?php if ( has_post_thumbnail() ) : ?>
					<div class="entry-thumbnail mb-6">
						<?php the_post_thumbnail( 'large', [ 'class' => 'w-full h-auto rounded-lg' ] ); ?>
					</div>
				<?php endif; ?>

				<div class="entry-content text-gray-700 text-lg leading-relaxed">
					<?php
					the_content();

					wp_link_pages( [
						'before' => '<div class="page-links mt-4">',
						'after'  => '</div>',
					] );
					?>
				</div>

				<footer class="entry-footer mt-8 pt-6 border-t border-gray-200">
					<?php if ( has_category() ) : ?>
						<div class="cat-links mb-3">
							<span class="text-sm font-semibold text-gray-600"><?php esc_html_e( 'Categories:', 'storefront-zero' ); ?></span>
							<?php the_category( ', ' ); ?>
						</div>
					<?php endif; ?>

					<?php if ( has_tag() ) : ?>
						<div class="tag-links">
							<span class="text-sm font-semibold text-gray-600"><?php esc_html_e( 'Tags:', 'storefront-zero' ); ?></span>
							<?php the_tags( '', ', ' ); ?>
						</div>
					<?php endif; ?>
				</footer>

			</article>

			<nav class="post-navigation mt-8">
				<?php
				the_post_navigation( [
					'prev_text' => '<span class="nav-subtitle">' . esc_html__( 'Previous:', 'storefront-zero' ) . '</span> <span class="nav-title">%title</span>',
					'next_text' => '<span class="nav-subtitle">' . esc_html__( 'Next:', 'storefront-zero' ) . '</span> <span class="nav-title">%title</span>',
					'class'     => 'flex justify-between text-sm',
				] );
				?>
			</nav>

			<?php if ( comments_open() || get_comments_number() ) : ?>
				<div class="comments-area mt-10">
					<?php comments_template(); ?>
				</div>
			<?php endif; ?>

		<?php endwhile; ?>

	</main>
</div>

<?php
get_sidebar();
get_footer();
