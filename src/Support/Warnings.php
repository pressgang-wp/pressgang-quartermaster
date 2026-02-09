<?php


namespace PressGang\Quartermaster\Support;

/**
 * Builds advisory diagnostics for potentially conflicting query args.
 *
 * Warnings are informational only: they do not mutate args and they do not change runtime
 * query behavior.
 */
final class Warnings
{
    /**
     * Return warning messages for known risky `WP_Query` arg combinations.
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

    /**
     * Return warning messages for known `WP_Term_Query` arg pitfalls.
     *
     * Current warnings:
     * - `hide_empty` not explicitly set (WordPress defaults to `true`, which silently
     *   excludes terms with no assigned posts)
     *
     * See: https://developer.wordpress.org/reference/classes/wp_term_query/#parameters
     *
     * @param array<string, mixed> $args
     * @return array<int, string>
     */
    public static function fromTermArgs(array $args): array
    {
        $warnings = [];

        if (!array_key_exists('hide_empty', $args)) {
            $warnings[] = 'hide_empty was not explicitly set; WordPress defaults to true, which excludes terms with no posts.';
        }

        return $warnings;
    }
}
