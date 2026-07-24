<?php

declare(strict_types=1);

/**
 * Minimal WordPress function stubs for theme test suite.
 *
 * Provides just enough stubs to render view templates outside a full
 * WordPress environment. Loaded once via require in the TestCase or
 * individual test files that need to call View::render().
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

/*
|--------------------------------------------------------------------------
| WooCommerce stubs
|--------------------------------------------------------------------------
*/

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
