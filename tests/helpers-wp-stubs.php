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

if (!class_exists('HeaderCapture')) {
    /**
     * Records every header() call so tests can assert on output headers.
     */
    class HeaderCapture
    {
        /** @var list<string> */
        private static array $headers = [];

        public static function capture(string $raw): void
        {
            self::$headers[] = $raw;
        }

        /** @return list<string> */
        public static function getHeaders(): array
        {
            return self::$headers;
        }

        public static function getLastHeader(): ?string
        {
            return end(self::$headers) ?: null;
        }

        public static function reset(): void
        {
            self::$headers = [];
        }
    }
}

if (!function_exists('header')) {
    function header(string $raw): void {
        \HeaderCapture::capture($raw);
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

        /** @var array<string, mixed> Key-value pairs set via set() on the last instance. */
        private static array $lastSetValues = [];

        /** Whether the query pretends to be the main query. */
        private static bool $isMainQuery = false;

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

        /**
         * Get key-value pairs set via the instance set() method (from pre_get_posts).
         *
         * @return array<string, mixed>
         */
        public static function getLastSetValues(): array
        {
            return self::$lastSetValues;
        }

        /**
         * Configure whether the next WP_Query instance reports as main query.
         */
        public static function setIsMainQuery(bool $value): void
        {
            self::$isMainQuery = $value;
        }

        public static function reset(): void
        {
            self::$defaultPosts = [];
            self::$lastArgs = [];
            self::$callback = null;
            self::$lastSetValues = [];
            self::$isMainQuery = false;
        }

        /** @var list<int> */
        public array $posts = [];

        public function __construct(array $args = [])
        {
            self::$lastArgs = $args;
            self::$lastSetValues = [];

            if (self::$callback !== null) {
                $this->posts = (self::$callback)($args);
            } else {
                $this->posts = self::$defaultPosts;
            }

            // Simulate WordPress pre_get_posts hook — fire registered callbacks.
            if (class_exists('WpHookStore')) {
                foreach (WpHookStore::get('pre_get_posts') as $callback) {
                    $callback($this);
                }
            }
        }

        /**
         * Whether this is the main WordPress query.
         */
        public function is_main_query(): bool
        {
            return self::$isMainQuery;
        }

        /**
         * Set a query argument (called by pre_get_posts callbacks).
         */
        public function set(string $key, mixed $value): void
        {
            self::$lastSetValues[$key] = $value;
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

/*
|--------------------------------------------------------------------------
| WooCommerce notices stubs (controllable per-test)
|--------------------------------------------------------------------------
*/

if (!class_exists('WcNoticesStub')) {
    class WcNoticesStub
    {
        /** @var array<int, array{type: string, notice: string}> */
        private static array $notices = [];

        private static int $getCalls = 0;

        private static int $clearCalls = 0;

        /**
         * Pre-populate notices for the next wc_get_notices() call.
         *
         * @param array<int, array{type: string, notice: string}> $notices
         */
        public static function setNotices(array $notices): void
        {
            self::$notices = $notices;
        }

        /** @return array<int, array{type: string, notice: string}> */
        public static function get(): array
        {
            self::$getCalls++;
            return self::$notices;
        }

        public static function clear(): void
        {
            self::$clearCalls++;
            self::$notices = [];
        }

        public static function getGetCalls(): int
        {
            return self::$getCalls;
        }

        public static function getClearCalls(): int
        {
            return self::$clearCalls;
        }

        public static function reset(): void
        {
            self::$notices = [];
            self::$getCalls = 0;
            self::$clearCalls = 0;
        }
    }
}

if (!function_exists('wc_get_notices')) {
    function wc_get_notices(): array {
        return \WcNoticesStub::get();
    }
}

if (!function_exists('wc_clear_notices')) {
    function wc_clear_notices(): void {
        \WcNoticesStub::clear();
    }
}

/*
|--------------------------------------------------------------------------
| delete_transient stub
|--------------------------------------------------------------------------
*/

if (!function_exists('delete_transient')) {
    function delete_transient(string $key): bool {
        \WpTransientStore::set($key, false);
        return true;
    }
}

/*
|--------------------------------------------------------------------------
| get_option stub — returns default value (no database)
|--------------------------------------------------------------------------
*/

if (!function_exists('get_option')) {
    function get_option(string $option, mixed $default = false): mixed {
        return $default;
    }
}

if (!function_exists('update_option')) {
    function update_option(string $option, mixed $value, bool $autoload = true): bool {
        return true;
    }
}

/*
|--------------------------------------------------------------------------
| home_url stub
|--------------------------------------------------------------------------
*/

if (!function_exists('home_url')) {
    function home_url(string $path = ''): string {
        return 'https://example.com' . $path;
    }
}

/*
|--------------------------------------------------------------------------
| Hook stubs (controllable per-test via WpHookStore)
|--------------------------------------------------------------------------
*/

if (!class_exists('WpHookStore')) {
    class WpHookStore
    {
        /** @var array<string, list<callable>> */
        private static array $hooks = [];

        /**
         * Register a callback for a hook tag.
         */
        public static function add(string $tag, callable $callback, int $priority = 10): void
        {
            self::$hooks[$tag][] = $callback;
        }

        /**
         * Get all callbacks registered for a hook tag.
         *
         * @return list<callable>
         */
        public static function get(string $tag): array
        {
            return self::$hooks[$tag] ?? [];
        }

        public static function reset(): void
        {
            self::$hooks = [];
        }
    }
}

// Override the no-op add_action from wordpress-stubs so WpHookStore
// receives callbacks. Without this, pre_get_posts hooks never fire.
if (!function_exists('add_action')) {
    function add_action(string $tag, callable $callback, int $priority = 10, int $accepted_args = 1): bool {
        \WpHookStore::add($tag, $callback, $priority);
        return true;
    }
}

if (!function_exists('add_filter')) {
    function add_filter(string $tag, callable $callback, int $priority = 10, int $accepted_args = 1): bool {
        \WpHookStore::add($tag, $callback, $priority);
        return true;
    }
}
