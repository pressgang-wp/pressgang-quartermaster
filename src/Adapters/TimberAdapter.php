<?php


namespace PressGang\Quartermaster\Adapters;

use RuntimeException;

/**
 * Optional Timber terminal adapter.
 */
final class TimberAdapter
{
    /**
     * @param array<string, mixed> $args
     * @return object
     */
    public function postQuery(array $args): object
    {
        if (!class_exists(\Timber\PostQuery::class)) {
            throw new RuntimeException('Timber is not installed. Install timber/timber before calling timber().');
        }

        /** @var object $query */
        $query = new \Timber\PostQuery($args);

        return $query;
    }
}
