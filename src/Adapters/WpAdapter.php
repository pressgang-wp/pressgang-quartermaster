<?php


namespace PressGang\Quartermaster\Adapters;

use RuntimeException;

/**
 * WP_Query terminal adapter.
 */
final class WpAdapter
{
    /**
     * @param array<string, mixed> $args
     * @return \WP_Query
     */
    public function wpQuery(array $args): \WP_Query
    {
        if (!class_exists('WP_Query')) {
            throw new RuntimeException('WP_Query is unavailable. Ensure WordPress is bootstrapped before calling wpQuery().');
        }

        return new \WP_Query($args);
    }
}
