<?php
declare(strict_types=1);

namespace ThemeApp;

class View
{
    /**
     * Render a view template with the given data.
     *
     * @param string $view View name relative to app/Views/ (without .php extension).
     * @param array  $data Variables to extract into the template scope.
     *
     * @return void
     *
     * @throws \RuntimeException If the view file does not exist.
     */
    public static function render( string $view, array $data = [] ): void
    {
        $path = __DIR__ . '/' . $view . '.php';

        if ( ! file_exists( $path ) ) {
            throw new \RuntimeException(
                sprintf( 'View "%s" not found at %s', $view, $path )
            );
        }

        extract( $data, EXTR_SKIP );

        ob_start();
        include $path;
        echo ob_get_clean();
    }
}
