<?php
/**
 * The header template
 *
 * @package Storefront_Zero
 */

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preload" href="<?php echo esc_url( get_template_directory_uri() . '/assets/fonts/inter-latin.woff2' ); ?>" as="font" type="font/woff2" crossorigin>
    <script>
    (function() {
        var preference = localStorage.getItem('sz-dark-mode');
        var shouldBeDark = preference === 'dark' || (preference === null && window.matchMedia('(prefers-color-scheme: dark)').matches);
        if (shouldBeDark) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    })();
    </script>
    <!-- Critical CSS: above-the-fold styles inlined for fast first paint -->
    <style>
    /* Reset & base */
    *, ::after, ::before { box-sizing: border-box; border-color: currentColor; }
    html { font-family: Inter, ui-sans-serif, system-ui, -apple-system, sans-serif; line-height: 1.5; -webkit-text-size-adjust: 100%; tab-size: 4; }
    body { margin: 0; }

    /* Dark mode base */
    .dark { color-scheme: dark; }
    .dark body, .dark html { background-color: #0f172a; color: #f3f4f6; }

    /* Header */
    .site-header { background-color: #ffffff; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); }
    .dark .site-header { background-color: #1a1a2e; }
    .site-header .max-w-7xl { max-width: 80rem; margin-left: auto; margin-right: auto; padding-left: 1rem; padding-right: 1rem; }
    @media (min-width: 640px) { .site-header .max-w-7xl { padding-left: 1.5rem; padding-right: 1.5rem; } }
    @media (min-width: 1024px) { .site-header .max-w-7xl { padding-left: 2rem; padding-right: 2rem; } }
    .site-header .flex { display: flex; }
    .site-header .items-center { align-items: center; }
    .site-header .justify-between { justify-content: space-between; }
    .site-header .h-16 { height: 4rem; }

    /* Branding */
    .site-header .text-xl { font-size: 1.25rem; line-height: 1.75rem; font-weight: 700; }
    .site-header .text-gray-900 { color: #111827; }
    .dark .site-header .text-gray-900, .dark .site-header .text-gray-100 { color: #f3f4f6; }

    /* Nav */
    .site-header nav.flex { display: flex; align-items: center; }

    /* Search input visible size */
    .site-header .max-w-md { max-width: 28rem; }
    .site-header .mx-8 { margin-left: 2rem; margin-right: 2rem; }
    .site-header .relative { position: relative; }
    .site-header .flex-1 { flex: 1 1 0%; }

    /* HTMX indicators — hidden by default */
    .htmx-indicator { display: none; }

    /* Skip link */
    .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border-width: 0; }
    </style>
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:bg-white focus:px-4 focus:py-2 focus:rounded focus:shadow-md focus:outline-none">
    <?php esc_html_e( 'Skip to content', 'storefront-zero' ); ?>
</a>

<header id="masthead" class="site-header bg-white dark:bg-darkSurface shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Site branding -->
            <div class="flex-shrink-0">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="text-xl font-bold text-gray-900 dark:text-gray-100">
                    <?php if ( has_custom_logo() ) : ?>
                        <?php the_custom_logo(); ?>
                    <?php else : ?>
                        <?php echo esc_html( get_bloginfo( 'name' ) ); ?>
                    <?php endif; ?>
                </a>
            </div>

            <!-- HTMX Search -->
            <div class="relative flex-1 max-w-md mx-8">
                <form role="search" aria-label="<?php esc_attr_e( 'Product search', 'storefront-zero' ); ?>">
                    <input 
                        type="search"
                        name="s"
                        placeholder="<?php esc_attr_e( 'Search products...', 'storefront-zero' ); ?>"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent bg-white dark:bg-darkCard text-gray-900 dark:text-gray-100"
                        hx-get="<?php echo esc_attr( sz_endpoint( 'search' ) ); ?>"
                        hx-trigger="keyup changed delay:300ms, search"
                        hx-target="#search-results"
                        hx-indicator=".search-spinner"
                        autocomplete="off"
                        aria-label="<?php esc_attr_e( 'Search products', 'storefront-zero' ); ?>"
                        role="combobox"
                        aria-autocomplete="list"
                        aria-expanded="false"
                        aria-controls="search-results"
                        aria-activedescendant=""
                    />
                    <span class="search-spinner htmx-indicator absolute right-3 top-1/2 -translate-y-1/2" role="status" aria-label="<?php esc_attr_e( 'Searching...', 'storefront-zero' ); ?>">
                        <svg class="animate-spin h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                    
                    <!-- Search results dropdown -->
                    <div id="search-results" role="listbox" class="absolute z-50 w-full bg-white dark:bg-darkCard border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg mt-1 hidden" aria-live="polite">
                        <!-- Search results loading skeleton -->
                        <div class="skeleton-search-results htmx-indicator animate-pulse p-2 space-y-2">
                            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
                            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
                            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-2/3"></div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Navigation -->
            <nav class="flex items-center space-x-2">
                <!-- Dark mode toggle -->
                <dark-mode-toggle></dark-mode-toggle>

                <!-- Mini-cart with live HTMX sync -->
                <div id="mini-cart-container"
                     hx-get="<?php echo esc_attr( sz_endpoint( 'cart/mini' ) ); ?>"
                     hx-trigger="load, cartUpdated from:body"
                     hx-swap="outerHTML"
                     aria-live="polite"
                     class="relative">
                    <!-- Mini-cart loading skeleton -->
                    <div class="skeleton-mini-cart htmx-indicator animate-pulse flex items-center space-x-2">
                        <div class="w-6 h-6 bg-gray-200 dark:bg-gray-700 rounded"></div>
                        <div class="w-5 h-5 bg-gray-200 dark:bg-gray-700 rounded-full"></div>
                    </div>
                </div>

                <mobile-drawer>
                    <button data-drawer-toggle aria-label="<?php esc_attr_e( 'Open navigation menu', 'storefront-zero' ); ?>" class="p-2 text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100 lg:hidden">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <nav data-drawer-menu class="hidden lg:flex lg:space-x-4">
                        <?php
                        wp_nav_menu( [
                            'theme_location' => 'primary',
                            'container'      => false,
                            'menu_class'     => 'flex space-x-4',
                            'fallback_cb'    => false,
                            'depth'          => 1,
                        ] );
                        ?>
                    </nav>
                </mobile-drawer>
            </nav>
        </div>
    </div>
</header>

<div id="content" class="site-content">
