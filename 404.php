<?php
/**
 * The template for displaying 404 pages (not found).
 *
 * @package Storefront_Zero
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

status_header( 404 );
nocache_headers();

get_header();
?>

<div id="primary" class="content-area">
	<main id="main" class="site-main">
		<section class="not-found py-16 text-center">
			<header class="page-header mb-6">
				<h1 class="page-title text-3xl font-bold text-gray-900">
					<?php esc_html_e( 'Page not found', 'storefront-zero' ); ?>
				</h1>
			</header>

			<div class="page-content max-w-xl mx-auto mb-10">
				<p class="text-gray-600 mb-4">
					<?php esc_html_e( 'The page you were looking for doesn\'t exist. It may have been moved or removed.', 'storefront-zero' ); ?>
				</p>

				<!-- HTMX search form -->
				<div class="relative max-w-md mx-auto mb-8">
					<input
						type="search"
						name="s"
						placeholder="<?php esc_attr_e( 'Search products...', 'storefront-zero' ); ?>"
						class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent"
						hx-get="/htmx-api/search"
						hx-trigger="keyup changed delay:300ms, search"
						hx-target="#search-results-404"
						hx-indicator=".search-spinner-404"
						autocomplete="off"
						aria-label="<?php esc_attr_e( 'Search products', 'storefront-zero' ); ?>"
					/>
					<span class="search-spinner-404 htmx-indicator absolute right-3 top-1/2 -translate-y-1/2" role="status" aria-label="<?php esc_attr_e( 'Searching...', 'storefront-zero' ); ?>">
						<svg class="animate-spin h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
							<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
							<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
						</svg>
					</span>

					<div id="search-results-404" class="absolute z-50 w-full bg-white border border-gray-200 rounded-lg shadow-lg mt-1 hidden" aria-live="polite">
					</div>
				</div>

				<!-- Navigation links -->
				<nav class="flex flex-col sm:flex-row items-center justify-center gap-4">
					<a
						href="<?php echo esc_url( home_url( '/' ) ); ?>"
						class="inline-flex items-center px-5 py-2.5 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition-colors"
					>
						<?php esc_html_e( 'Go to homepage', 'storefront-zero' ); ?>
					</a>

					<?php
					$recent_posts_query = new WP_Query( [
						'posts_per_page' => 3,
						'no_found_rows'  => true,
						'orderby'        => 'date',
						'order'          => 'DESC',
					] );

					if ( $recent_posts_query->have_posts() ) :
						$recent_posts_query->the_post();
					?>
						<a
							href="<?php echo esc_url( get_permalink() ); ?>"
							class="inline-flex items-center px-5 py-2.5 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors"
						>
							<?php esc_html_e( 'Read latest post', 'storefront-zero' ); ?>
						</a>
					<?php
					endif;

					wp_reset_postdata();
					?>
				</nav>
			</div>
		</section>
	</main>
</div>

<?php
get_footer();
