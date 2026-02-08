<?php


namespace PressGang\Quartermaster\Adapters;

use RuntimeException;

/**
 * Terminal adapter that instantiates `WP_Query` from explicit args.
 *
 * This adapter does not mutate the args array; it only validates runtime availability of
 * the `WP_Query` class and then returns `new \WP_Query($args)`.
 *
 * See: https://developer.wordpress.org/reference/classes/wp_query/
 */
final class WpAdapter
{
    /**
     * Create a WordPress query from the provided args.
     *
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
