<?php


namespace PressGang\Quartermaster\Support;

/**
 * Builds advisory diagnostics for potentially conflicting `WP_Query` args.
 *
 * Warnings are informational only: they do not mutate args and they do not change runtime
 * query behavior.
 */
final class Warnings
{
    /**
     * Return warning messages for known risky arg combinations.
     *
     * Current warnings:
     * - `posts_per_page = -1` with `paged` set (pagination is typically ignored by WordPress)
     * - `orderby = meta_value` without `meta_key` (ordering becomes ambiguous)
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/#pagination-parameters
     * See: https://developer.wordpress.org/reference/classes/wp_query/#custom-field-post-meta-parameters
     *
     * @param array<string, mixed> $args
     * @return array<int, string>
     */
    public static function fromArgs(array $args): array
    {
        $warnings = [];

        if (($args['posts_per_page'] ?? null) === -1 && array_key_exists('paged', $args)) {
            $warnings[] = 'Using posts_per_page=-1 with paged is usually conflicting and paged will be ignored.';
        }

        if (($args['orderby'] ?? null) === 'meta_value' && empty($args['meta_key'])) {
            $warnings[] = 'Using orderby=meta_value without meta_key will produce unreliable ordering.';
        }

        return $warnings;
    }
}
