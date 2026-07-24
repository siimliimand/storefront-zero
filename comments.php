<?php
/**
 * The template for displaying comments
 *
 * @package Storefront_Zero
 */

defined( 'ABSPATH' ) || exit;

if ( post_password_required() ) {
	return;
}
?>

<div id="comments" class="comments-area mt-8">

	<?php if ( have_comments() ) : ?>
		<h2 class="comments-title text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">
			<?php
			$comment_count = get_comments_number();
			printf(
				/* translators: 1: comment count, 2: post title */
				esc_html( _nx( '%1$s comment on &ldquo;%2$s&rdquo;', '%1$s comments on &ldquo;%2$s&rdquo;', $comment_count, 'comments title', 'storefront-zero' ) ),
				number_format_i18n( $comment_count ),
				'<span>' . wp_kses_post( get_the_title() ) . '</span>'
			);
			?>
		</h2>

		<ol class="comment-list space-y-4">
			<?php
			wp_list_comments( [
				'style'      => 'ol',
				'short_ping' => true,
				'avatar_size' => 48,
			] );
			?>
		</ol>

		<?php the_comments_navigation(); ?>

		<?php if ( ! comments_open() ) : ?>
			<p class="no-comments text-gray-500 dark:text-gray-400 italic mt-4">
				<?php esc_html_e( 'Comments are closed.', 'storefront-zero' ); ?>
			</p>
		<?php endif; ?>

	<?php endif; ?>

	<?php comment_form( [
		'class_form'    => 'space-y-4',
		'class_submit'  => 'button wp-element-button bg-brand-600 hover:bg-brand-700 text-white rounded px-4 py-2 text-sm font-medium',
		'title_reply'   => __( 'Leave a comment', 'storefront-zero' ),
	] ); ?>

</div>
