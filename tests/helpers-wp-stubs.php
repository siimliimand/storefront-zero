<?php

declare(strict_types=1);

/**
 * Minimal WordPress/WooCommerce function stubs for theme test suite.
 *
 * Provides just enough stubs to render view templates outside a full
 * WordPress environment. Loaded once via require_once in the TestCase.
 *
 * Every stub is guarded by function_exists / class_exists so it never
 * redefines something already provided by WordPress core, WooCommerce,
 * or WP_UnitTestCase when those runtimes are present.
 */

if (!function_exists('esc_html')) {
    function esc_html(string $text): string {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr(string $text): string {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_url')) {
    function esc_url(string $url): string {
        return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = 'default'): string {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_attr__')) {
    function esc_attr__($text, $domain = 'default'): string {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('__')) {
    function __($text, $domain = 'default'): string {
        return (string) $text;
    }
}

if (!function_exists('wp_kses_post')) {
    function wp_kses_post($content): string {
        return (string) $content;
    }
}

if (!function_exists('status_header')) {
    function status_header(int $code): void {
        // No-op in tests — headers aren't sent.
    }
}

if (!function_exists('header')) {
    function header(string $raw): void {
        // No-op in tests.
    }
}

if (!function_exists('header_remove')) {
    function header_remove(?string $name = null): void {
        // No-op in tests.
    }
}

if (!function_exists('wp_json_encode')) {
    function wp_json_encode(mixed $data, int $options = 0): string|false {
        return json_encode($data, $options);
    }
}

if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce(string $action): string {
        return 'test-nonce-' . md5($action);
    }
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce(string $nonce, string $action): bool {
        return $nonce === 'test-nonce-' . md5($action);
    }
}

if (!function_exists('absint')) {
    function absint(mixed $value): int {
        return abs((int) $value);
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field(string $str): string {
        return trim(strip_tags($str));
    }
}

if (!function_exists('wp_unslash')) {
    function wp_unslash(mixed $value): mixed {
        if (is_string($value)) {
            return stripslashes($value);
        }
        if (is_array($value)) {
            return array_map('wp_unslash', $value);
        }
        return $value;
    }
}

if (!function_exists('get_permalink')) {
    function get_permalink(int $id): string {
        return "https://example.com/product/{$id}";
    }
}

if (!function_exists('wp_get_attachment_image_url')) {
    function wp_get_attachment_image_url(int $id, string $size = 'thumbnail'): string|false {
        return "https://example.com/image-{$id}.jpg";
    }
}

if (!function_exists('wc_get_cart_url')) {
    function wc_get_cart_url(): string {
        return 'https://example.com/cart';
    }
}

if (!function_exists('wc_get_product')) {
    /**
     * Stub for wc_get_product. Returns a truthy dummy object when the
     * product ID is positive, null otherwise. Tests that need specific
     * behaviour should redefine this stub after loading helpers.
     */
    function wc_get_product(int $product_id): ?object {
        if ($product_id <= 0) {
            return null;
        }

        return new class ($product_id) {
            public function __construct(
                public readonly int $id,
            ) {}
        };
    }
}

/*
|--------------------------------------------------------------------------
| WooCommerce stubs
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| WooCommerce class stubs
|--------------------------------------------------------------------------
*/

if (!class_exists('WC_Cart')) {
    /**
     * Minimal WC_Cart stub for testing. Provides just enough surface
     * for CartController to mock against.
     */
    class WC_Cart
    {
        public function add_to_cart(int $product_id, int $quantity = 1, int $variation_id = 0, array $variation = []): string|false
        {
            return 'stub_cart_item_key';
        }

        public function get_cart_item(string $cart_item_key): array
        {
            return [];
        }

        public function set_quantity(string $cart_item_key, int $quantity = 1): bool
        {
            return true;
        }

        public function remove_cart_item(string $cart_item_key): bool
        {
            return true;
        }
    }
}

/*
|--------------------------------------------------------------------------
| Transient stubs (controllable per-test via static storage)
|--------------------------------------------------------------------------
*/

if (!class_exists('WpTransientStore')) {
    class WpTransientStore
    {
        /** @var array<string, mixed> */
        private static array $store = [];

        /** @var array<string, int> Expiration timestamps keyed by transient key. */
        private static array $expiries = [];

        public static function get(string $key): mixed
        {
            if (!isset(self::$store[$key])) {
                return false;
            }

            if (isset(self::$expiries[$key]) && time() > self::$expiries[$key]) {
                unset(self::$store[$key], self::$expiries[$key]);
                return false;
            }

            return self::$store[$key];
        }

        public static function set(string $key, mixed $value, int $expiration = 0): void
        {
            self::$store[$key] = $value;

            if ($expiration > 0) {
                self::$expiries[$key] = time() + $expiration;
            }
        }

        public static function reset(): void
        {
            self::$store  = [];
            self::$expiries = [];
        }
    }
}

if (!function_exists('get_transient')) {
    function get_transient(string $key): mixed {
        return \WpTransientStore::get($key);
    }
}

if (!function_exists('set_transient')) {
    function set_transient(string $key, mixed $value, int $expiration = 0): bool {
        \WpTransientStore::set($key, $value, $expiration);
        return true;
    }
}

/*
|--------------------------------------------------------------------------
| wc_get_products stub (controllable per-test)
|--------------------------------------------------------------------------
*/

if (!class_exists('WcProductsStub')) {
    class WcProductsStub
    {
        /** @var \Closure|null */
        private static ?\Closure $callback = null;

        /** @var array<int, object> Mock product objects keyed by ID. */
        private static array $products = [];

        /**
         * Set a callback that replaces the default wc_get_products behaviour.
         *
         * @param callable(array): array<mixed> $cb
         */
        public static function setCallback(callable $cb): void
        {
            self::$callback = \Closure::fromCallable($cb);
        }

        /**
         * Register mock WC_Product objects by ID.
         *
         * @param array<int, object> $products
         */
        public static function setProducts(array $products): void
        {
            self::$products = $products;
        }

        public static function get(array $args = []): array
        {
            if (self::$callback !== null) {
                return (self::$callback)($args);
            }

            // Default: return registered mock product objects when 'return' => 'objects',
            // otherwise return their IDs.
            $return = $args['return'] ?? 'ids';

            if ($return === 'objects') {
                $include = $args['include'] ?? [];
                return array_filter(
                    array_map(
                        fn(int $id) => self::$products[$id] ?? null,
                        $include
                    )
                );
            }

            return array_keys(self::$products);
        }

        public static function reset(): void
        {
            self::$callback = null;
            self::$products = [];
        }
    }
}

/*
|--------------------------------------------------------------------------
| WP_Query stub (controllable per-test via static storage)
|--------------------------------------------------------------------------
*/

if (!class_exists('WP_Query')) {
    class WP_Query
    {
        /** @var list<int> Product IDs to return as posts. */
        private static array $defaultPosts = [];

        /** @var array<string, mixed> Captured constructor args from the last call. */
        private static array $lastArgs = [];

        /** @var \Closure|null Optional callback to dynamically generate posts. */
        private static ?\Closure $callback = null;

        /**
         * Set the product IDs that the query will return.
         *
         * @param list<int> $posts
         */
        public static function setPosts(array $posts): void
        {
            self::$defaultPosts = $posts;
        }

        /**
         * Set a callback that dynamically generates the posts array.
         *
         * The callback receives the WP_Query constructor args and should return
         * a list of product IDs (integers).
         */
        public static function setCallback(callable $cb): void
        {
            self::$callback = Closure::fromCallable($cb);
        }

        /**
         * Get the constructor args from the most recent query.
         *
         * @return array<string, mixed>
         */
        public static function getLastArgs(): array
        {
            return self::$lastArgs;
        }

        public static function reset(): void
        {
            self::$defaultPosts = [];
            self::$lastArgs = [];
            self::$callback = null;
        }

        /** @var list<int> */
        public array $posts = [];

        public function __construct(array $args = [])
        {
            self::$lastArgs = $args;

            if (self::$callback !== null) {
                $this->posts = (self::$callback)($args);
            } else {
                $this->posts = self::$defaultPosts;
            }
        }
    }
}

if (!function_exists('wc_get_products')) {
    function wc_get_products(array $args = []): array {
        return \WcProductsStub::get($args);
    }
}

if (!function_exists('WC')) {
    function WC(): object {
        static $wc = null;

        if ($wc === null) {
            $cart = new class {
                public function get_cart_contents_count(): int {
                    return 0;
                }

                public function get_cart_subtotal(): string {
                    return '$0.00';
                }
            };

            $wc = new class ($cart) {
                public function __construct(
                    public readonly object $cart,
                ) {}
            };
        }

        return $wc;
    }
}
