<?php


namespace PressGang\Quartermaster\Adapters;

use RuntimeException;

/**
 * Optional Timber terminal adapter for `Timber\PostQuery`.
 *
 * This adapter does not mutate args; it only guards for Timber availability and returns
 * a `Timber\PostQuery` object built from the provided args.
 *
 * See: https://timber.github.io/docs/v2/reference/timber-postquery/
 */
final class TimberAdapter
{
    /**
     * Create a Timber post query from the provided args.
     *
     * Runtime requirement: `\Timber\PostQuery` must exist.
     *
     * @param array<string, mixed> $args
     * @return object Timber post query object.
     */
    public function postQuery(array $args): object
    {
        if (!class_exists(\Timber\PostQuery::class)) {
            throw new RuntimeException('Timber is not installed. Install timber/timber before calling timber().');
        }

        /** @var object $query */
        $query = new \Timber\PostQuery(new \WP_Query($args));

        return $query;
    }
}
