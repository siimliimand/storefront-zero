<?php
/**
 * The footer template
 *
 * @package Storefront_Zero
 */

?>
</div><!-- #content -->

<footer id="colophon" class="site-footer bg-gray-50 border-t">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col md:flex-row justify-between items-center">
            <div class="text-sm text-gray-600">
                &copy; <?php echo esc_html( date( 'Y' ) ); ?> <?php bloginfo('name'); ?>. All rights reserved.
            </div>
            <nav class="mt-4 md:mt-0">
                <?php
                wp_nav_menu([
                    'theme_location' => 'footer',
                    'container'      => false,
                    'menu_class'     => 'flex space-x-4 text-sm text-gray-600',
                    'fallback_cb'    => false,
                    'depth'          => 1,
                ]);
                ?>
            </nav>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>

</body>
</html>
