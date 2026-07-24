<?php
declare(strict_types=1);

namespace ThemeApp;

class View
{
    /**
     * Render a view template with the given data.
     *
     * @param string         $view View name relative to app/Views/ (without .php extension).
     * @param array<string, mixed> $data Variables to extract into the template scope.
     *
     * @return void
     *
     * @throws \RuntimeException If the view file does not exist.
     */
    public static function render( string $view, array $data = [] ): void
    {
        $view = basename( $view );
        $path = __DIR__ . '/Views/' . $view . '.php';

        if ( ! file_exists( $path ) ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                throw new \RuntimeException(
                    sprintf( 'View "%s" not found at %s', $view, $path )
                );
            }

            status_header( 500 );
            echo '<!-- View not found: ' . esc_html( $view ) . ' -->';
            return;
        }

        extract( $data, EXTR_SKIP );

        $level = ob_get_level();
        ob_start();
        try {
            include $path;
            echo ob_get_clean();
        } catch ( \Throwable $e ) {
            while ( ob_get_level() > $level ) {
                ob_end_clean();
            }

            status_header( 500 );

            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                echo '<!-- View rendering error: ' . esc_html( $e->getMessage() ) . ' in ' . esc_html( $e->getFile() ) . ' -->';
            } else {
                echo '<!-- View rendering error -->';
            }
        }
    }
}
