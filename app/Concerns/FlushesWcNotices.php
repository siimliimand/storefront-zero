<?php
declare(strict_types=1);

namespace ThemeApp\Concerns;

trait FlushesWcNotices
{
    private function flush_wc_notices(): string
    {
        $notices = wc_get_notices();
        wc_clear_notices();

        if ( ! empty( $notices ) ) {
            $notice = reset( $notices );
            $type   = $notice['type'] ?? 'notice';

            header(
                'HX-Trigger: ' . wp_json_encode( [
                    'showToast' => [
                        'message' => $notice['notice'] ?? '',
                        'type'    => $type,
                    ],
                ] )
            );
        }

        return '';
    }
}
