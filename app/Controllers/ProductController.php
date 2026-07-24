<?php
declare(strict_types=1);

/**
 * Product Controller
 *
 * Handles product-related HTMX fragment requests.
 *
 * @package Storefront_Zero
 */

namespace ThemeApp\Controllers;

/**
 * ProductController manages product search and display via HTMX fragments.
 */
class ProductController
{
    /**
     * Live product search via HTMX.
     *
     * Sanitizes the query, queries WooCommerce for matching published products,
     * and renders an HTML fragment with product name, price, image, and permalink.
     *
     * @return void
     */
    public static function liveSearch(): void
    {
        $query = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';

        if (empty($query)) {
            echo '';
            return;
        }

        $products = wc_get_products([
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => 5,
            's'              => $query,
        ]);

        if (empty($products)) {
            printf(
                '<div class="search-results-empty"><p>%s</p></div>',
                esc_html(
                    sprintf(
                        /* translators: %s: search query */
                        __('No products found for "%s"', 'storefront-zero'),
                        $query
                    )
                )
            );
            return;
        }

        echo '<div class="search-results">';

        foreach ($products as $product) {
            $permalink = get_permalink($product->get_id());
            $image_id  = $product->get_image_id();
            $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : '';
            ?>
            <a href="<?php echo esc_url($permalink); ?>"
               class="search-result-item flex items-center gap-3 p-2 hover:bg-gray-100 rounded">
                <?php if ($image_url) : ?>
                    <img src="<?php echo esc_url($image_url); ?>"
                         alt="<?php echo esc_attr($product->get_name()); ?>"
                         class="w-12 h-12 object-cover rounded"
                         loading="lazy" />
                <?php endif; ?>
                <div>
                    <span class="block font-medium text-sm">
                        <?php echo esc_html($product->get_name()); ?>
                    </span>
                    <span class="block text-sm text-gray-600">
                        <?php echo wp_kses_post($product->get_price_html()); ?>
                    </span>
                </div>
            </a>
            <?php
        }

        echo '</div>';
    }
}
