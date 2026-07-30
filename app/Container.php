<?php
declare(strict_types=1);

/**
 * Minimal DI container for the Storefront Zero theme.
 *
 * Registers View and controller factories. WooCommerce cart and
 * CartController are only registered when WooCommerce is active.
 * Controllers are created as instances (replacing the current static
 * usage) once tasks 3.2–3.5 migrate them to instance methods.
 *
 * @package Storefront_Zero
 */

namespace ThemeApp;

use ThemeApp\Controllers\CartController;
use ThemeApp\Controllers\ProductController;
use ThemeApp\ViewInterface;

class Container
{
    /** @var array<string, callable(self): mixed> */
    private array $factories = [];

    /** @var array<string, mixed> */
    private array $singletons = [];

    /**
     * Register a service factory.
     *
     * The factory receives this container instance so it can resolve
     * dependencies inline.
     *
     * @param string         $id       Service identifier (class name or alias).
     * @param callable(self): mixed $factory Closure that builds the service.
     */
    public function set(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
        unset($this->singletons[$id]);
    }

    /**
     * Resolve a service by identifier.
     *
     * Factories are called on first get() and cached; subsequent calls
     * return the singleton.
     *
     * @throws \RuntimeException If no factory is registered for $id.
     */
    public function get(string $id): mixed
    {
        if (isset($this->singletons[$id])) {
            return $this->singletons[$id];
        }

        if (!isset($this->factories[$id])) {
            throw new \RuntimeException(
                sprintf('No service registered for "%s".', $id)
            );
        }

        $result = $this->factories[$id]($this);
        $this->singletons[$id] = $result;

        return $result;
    }

    /**
     * Check whether a service is registered.
     */
    public function has(string $id): bool
    {
        return isset($this->factories[$id]) || isset($this->singletons[$id]);
    }

    /**
     * Create a new Container pre-loaded with the theme's default services.
     */
    public static function create(): self
    {
        $container = new self();

        // WooCommerce cart — only available when WC is active.
        if (function_exists('WC')) {
            $container->set(\WC_Cart::class, static function (): \WC_Cart {
                return WC()->cart;
            });

            // CartController depends on WC_Cart.
            $container->set(CartController::class, static function (self $c): CartController {
                return new CartController(
                    $c->get(\WC_Cart::class),
                    $c->get(ViewInterface::class),
                );
            });
        }

        // View renderer — unconditional.
        $container->set(ViewInterface::class, static function (): ViewInterface {
            return new View();
        });

        $container->set(ProductController::class, static function (self $c): ProductController {
            return new ProductController(
                $c->get(ViewInterface::class),
            );
        });

        return $container;
    }
}
