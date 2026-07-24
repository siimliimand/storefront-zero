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
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header id="masthead" class="site-header bg-white shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Site branding -->
            <div class="flex-shrink-0">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="text-xl font-bold text-gray-900">
                    <?php bloginfo('name'); ?>
                </a>
            </div>

            <!-- HTMX Search -->
            <div class="relative flex-1 max-w-md mx-8">
                <input 
                    type="search"
                    name="s"
                    placeholder="Search products..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    hx-get="/htmx-api/search"
                    hx-trigger="keyup changed delay:300ms, search"
                    hx-target="#search-results"
                    hx-indicator=".search-spinner"
                    autocomplete="off"
                />
                <span class="search-spinner htmx-indicator absolute right-3 top-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </span>
                
                <!-- Search results dropdown -->
                <div id="search-results" class="absolute z-50 w-full bg-white border border-gray-200 rounded-lg shadow-lg mt-1 hidden">
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex items-center space-x-4">
                <mobile-drawer>
                    <button data-drawer-toggle class="p-2 text-gray-600 hover:text-gray-900 lg:hidden">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <nav data-drawer-menu class="hidden lg:flex lg:space-x-4">
                        <a href="<?php echo esc_url(home_url('/shop')); ?>" class="text-gray-600 hover:text-gray-900">Shop</a>
                        <a href="<?php echo esc_url(home_url('/cart')); ?>" class="text-gray-600 hover:text-gray-900">Cart</a>
                        <a href="<?php echo esc_url(home_url('/checkout')); ?>" class="text-gray-600 hover:text-gray-900">Checkout</a>
                    </nav>
                </mobile-drawer>
            </nav>
        </div>
    </div>
</header>

<div id="content" class="site-content">
