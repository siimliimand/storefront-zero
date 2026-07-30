<?php
declare(strict_types=1);

namespace ThemeApp\Concerns;

trait FlushesWcNotices
{
    private function flush_wc_notices(): string
    {
        $notices = wc_get_notices();
        wc_clear_notices();

        $flat = [];

        foreach ( $notices as $type => $group ) {
            foreach ( $group as $message ) {
                $flat[] = [
                    'message' => $message,
                    'type'    => $type,
                ];
            }
        }

        if ( [] === $flat ) {
            return '';
        }

        header(
            'HX-Trigger: ' . wp_json_encode( [
                'showToast' => $flat,
            ] )
        );

        return '';
    }
}
