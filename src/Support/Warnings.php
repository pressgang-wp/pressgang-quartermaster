<?php


namespace PressGang\Quartermaster\Support;

/**
 * Compute warning messages for suspicious arg combinations.
 */
final class Warnings
{
    /**
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
