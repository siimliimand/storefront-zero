<?php
/**
 * The front page template.
 *
 * Displays the static front page when one is set in Reading Settings,
 * otherwise shows a welcome section with featured products or recent posts.
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

		<?php if ( is_front_page() && 'page' === get_option( 'show_on_front' ) && get_option( 'page_on_front' ) ) : ?>

			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/content', 'page' );
			endwhile;
			?>

		<?php else : ?>

			<!-- Hero section -->
			<section class="bg-gradient-to-br from-gray-900 to-gray-800 text-white py-20 px-4 sm:px-6 lg:px-8">
				<div class="max-w-4xl mx-auto text-center">
					<h1 class="text-4xl sm:text-5xl font-bold mb-6">
						<?php echo esc_html( get_bloginfo( 'name' ) ); ?>
					</h1>
					<?php $description = get_bloginfo( 'description', 'display' ); ?>
					<?php if ( $description ) : ?>
						<p class="text-lg sm:text-xl text-gray-300 mb-8">
							<?php echo esc_html( $description ); ?>
						</p>
					<?php endif; ?>

					<?php if ( class_exists( 'WooCommerce' ) ) : ?>
						<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"
						   class="inline-block bg-white text-gray-900 font-semibold px-8 py-3 rounded-lg hover:bg-gray-100 transition-colors">
							<?php esc_html_e( 'Browse Shop', 'storefront-zero' ); ?>
						</a>
					<?php else : ?>
						<a href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ); ?>"
						   class="inline-block bg-white text-gray-900 font-semibold px-8 py-3 rounded-lg hover:bg-gray-100 transition-colors">
							<?php esc_html_e( 'Read Latest', 'storefront-zero' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</section>

			<!-- Featured products or recent posts -->
			<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">

				<?php if ( class_exists( 'WooCommerce' ) ) : ?>

					<h2 class="text-2xl font-bold text-gray-900 mb-8 text-center">
						<?php esc_html_e( 'Featured Products', 'storefront-zero' ); ?>
					</h2>

					<?php
					$featured_products = wc_get_products( [
						'limit'   => 6,
						'orderby' => 'date',
						'order'   => 'DESC',
						'status'  => 'publish',
					] );

					if ( $featured_products ) :
					?>
						<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
							<?php foreach ( $featured_products as $product ) : ?>
								<?php
								$product_id    = $product->get_id();
								$product_url   = get_permalink( $product_id );
								$product_title = $product->get_name();
								?>
								<article class="bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-lg transition-shadow">
									<a href="<?php echo esc_url( $product_url ); ?>" class="block">
										<?php if ( has_post_thumbnail( $product_id ) ) : ?>
											<div class="aspect-w-1 aspect-h-1 bg-gray-100">
												<?php echo get_the_post_thumbnail( $product_id, 'woocommerce_thumbnail', [ 'class' => 'w-full h-full object-cover' ] ); ?>
											</div>
										<?php else : ?>
											<div class="aspect-w-1 aspect-h-1 bg-gray-100 flex items-center justify-center">
												<span class="text-gray-400 text-sm"><?php esc_html_e( 'No image', 'storefront-zero' ); ?></span>
											</div>
										<?php endif; ?>
									</a>

									<div class="p-4">
										<h3 class="font-semibold text-gray-900 mb-2">
											<a href="<?php echo esc_url( $product_url ); ?>" class="hover:text-brand-600 transition-colors">
												<?php echo esc_html( $product_title ); ?>
											</a>
										</h3>
										<div class="text-lg font-bold text-gray-900">
											<?php echo wp_kses_post( $product->get_price_html() ); ?>
										</div>
									</div>
								</article>
							<?php endforeach; ?>
						</div>

						<div class="text-center mt-10">
							<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"
							   class="inline-block bg-gray-900 text-white font-semibold px-6 py-3 rounded-lg hover:bg-gray-800 transition-colors">
								<?php esc_html_e( 'View All Products', 'storefront-zero' ); ?>
							</a>
						</div>
					<?php else : ?>
						<p class="text-center text-gray-500">
							<?php esc_html_e( 'No products found.', 'storefront-zero' ); ?>
						</p>
					<?php endif; ?>

				<?php else : ?>

					<h2 class="text-2xl font-bold text-gray-900 mb-8 text-center">
						<?php esc_html_e( 'Latest Posts', 'storefront-zero' ); ?>
					</h2>

					<?php
					$recent_posts = new WP_Query( [
						'posts_per_page' => 6,
						'post_status'    => 'publish',
						'orderby'        => 'date',
						'order'          => 'DESC',
					] );

					if ( $recent_posts->have_posts() ) :
					?>
						<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
							<?php while ( $recent_posts->have_posts() ) : $recent_posts->the_post(); ?>
								<article class="bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-lg transition-shadow">
									<?php if ( has_post_thumbnail() ) : ?>
										<a href="<?php the_permalink(); ?>" class="block">
											<div class="aspect-w-16 aspect-h-9 bg-gray-100">
												<?php the_post_thumbnail( 'medium_large', [ 'class' => 'w-full h-full object-cover' ] ); ?>
											</div>
										</a>
									<?php endif; ?>

									<div class="p-4">
										<h3 class="font-semibold text-gray-900 mb-2">
											<a href="<?php the_permalink(); ?>" class="hover:text-brand-600 transition-colors">
												<?php the_title(); ?>
											</a>
										</h3>
										<p class="text-sm text-gray-500 mb-2">
											<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>">
												<?php echo esc_html( get_the_date() ); ?>
											</time>
										</p>
										<div class="text-gray-600 text-sm">
											<?php the_excerpt(); ?>
										</div>
									</div>
								</article>
							<?php endwhile; ?>
						</div>

						<div class="text-center mt-10">
							<a href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ); ?>"
							   class="inline-block bg-gray-900 text-white font-semibold px-6 py-3 rounded-lg hover:bg-gray-800 transition-colors">
								<?php esc_html_e( 'View All Posts', 'storefront-zero' ); ?>
							</a>
						</div>
					<?php
					wp_reset_postdata();
					else :
					?>
						<p class="text-center text-gray-500">
							<?php esc_html_e( 'No posts found.', 'storefront-zero' ); ?>
						</p>
					<?php endif; ?>

				<?php endif; ?>

			</section>

		<?php endif; ?>

	</main>
</div>

<?php
get_footer();
