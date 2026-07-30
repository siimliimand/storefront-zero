<?php
declare(strict_types=1);

namespace ThemeApp;

interface ViewInterface
{
    /**
     * Render a view template with the given data.
     *
     * @param string         $view View name relative to app/Views/ (without .php extension).
     * @param array<string, mixed> $data Variables to extract into the template scope.
     *
     * @return void
     */
    public function render( string $view, array $data = [] ): void;
}
