<?php
/**
 * View template for product search results.
 *
 * Available variables: $products (WC_Product[]), $query (string).
 *
 * @package Storefront_Zero
 */

if ( empty( $products ) ) :
?>
<div class="search-results-empty p-4 text-center text-gray-500">
    <p>
        <?php
        echo esc_html(
            sprintf(
                /* translators: %s: search query */
                __( 'No products found for "%s"', 'storefront-zero' ),
                $query
            )
        );
        ?>
    </p>
</div>
<?php else : ?>
<div class="search-results">
    <?php foreach ( $products as $product ) :
        $permalink  = get_permalink( $product->get_id() );
        $image_id   = $product->get_image_id();
        $image_url  = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : '';
    ?>
    <a href="<?php echo esc_url( $permalink ); ?>"
       class="search-result-item flex items-center gap-3 p-2 hover:bg-gray-100 rounded">
        <?php if ( $image_url ) : ?>
            <img src="<?php echo esc_url( $image_url ); ?>"
                 alt="<?php echo esc_attr( $product->get_name() ); ?>"
                 class="w-12 h-12 object-cover rounded"
                 loading="lazy" />
        <?php endif; ?>
        <div>
            <span class="block font-medium text-sm">
                <?php echo esc_html( $product->get_name() ); ?>
            </span>
            <span class="block text-sm text-gray-600">
                <?php echo wp_kses_post( $product->get_price_html() ); ?>
            </span>
        </div>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>
