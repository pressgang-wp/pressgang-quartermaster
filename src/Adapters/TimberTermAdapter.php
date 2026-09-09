<?php

namespace PressGang\Quartermaster\Adapters;

use RuntimeException;
use Timber\Timber;
use WP_Error;
use WP_Term;

/**
 * Converts WordPress term results without bypassing get_terms filters.
 */
final class TimberTermAdapter
{
    /**
     * Fetch terms through WordPress and apply Timber's term class mapping.
     *
     * Empty results remain empty. Scalar projections such as IDs and names keep
     * their values and keys. Query arguments are passed through unchanged.
     *
     * @param array<string, mixed> $args
     * @return array<int|string, mixed>
     * @throws RuntimeException When Timber is unavailable or WordPress cannot return a term list.
     */
    public function getTerms(array $args): array
    {
        if (!class_exists(Timber::class)) {
            throw new RuntimeException('Timber is not installed. Install timber/timber before calling timber().');
        }

        $terms = \get_terms($args);

        if ($terms instanceof WP_Error) {
            throw new RuntimeException($terms->get_error_message());
        }

        if (!is_array($terms)) {
            throw new RuntimeException('The timber() term terminal requires a term list; count queries are not supported.');
        }

        return array_map(
            static fn (mixed $term): mixed => $term instanceof WP_Term ? Timber::get_term($term) : $term,
            $terms
        );
    }
}
