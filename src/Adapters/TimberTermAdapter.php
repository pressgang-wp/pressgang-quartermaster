<?php

namespace PressGang\Quartermaster\Adapters;

use RuntimeException;

/**
 * Optional Timber terminal adapter for term queries.
 *
 * This adapter does not mutate args; it only guards for Timber availability and returns
 * the result of `Timber::get_terms()` built from the provided args.
 *
 * See: https://timber.github.io/docs/v2/reference/timber-timber/#get_terms
 */
final class TimberTermAdapter
{
    /**
     * Create Timber term objects from the provided args.
     *
     * Runtime requirement: `\Timber\Timber` must exist.
     *
     * @param array<string, mixed> $args
     * @return iterable<int, object> Timber term objects.
     */
    public function getTerms(array $args): iterable
    {
        if (!class_exists(\Timber\Timber::class)) {
            throw new RuntimeException('Timber is not installed. Install timber/timber before calling timber().');
        }

        return \Timber\Timber::get_terms($args);
    }
}
