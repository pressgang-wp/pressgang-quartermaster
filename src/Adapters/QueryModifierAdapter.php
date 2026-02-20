<?php

namespace PressGang\Quartermaster\Adapters;

use PressGang\Quartermaster\Support\ClauseQuery;

/**
 * Terminal adapter that applies builder args to an existing `WP_Query` instance.
 *
 * Unlike `WpAdapter` and `TimberAdapter`, which create new query objects, this adapter
 * modifies an existing `WP_Query` in place. It is designed for use inside WordPress
 * `pre_get_posts` hooks where a `WP_Query` object is already provided.
 *
 * Scalar args are applied directly via `$query->set()`. Clause arrays (`tax_query`,
 * `meta_query`, `date_query`) are merged with existing clauses using `ClauseQuery::appendClause()`
 * so that hooks from multiple sources compose safely.
 *
 * See: https://developer.wordpress.org/reference/hooks/pre_get_posts/
 * See: https://developer.wordpress.org/reference/classes/wp_query/set/
 */
final class QueryModifierAdapter
{
    /**
     * Keys whose values are clause arrays requiring merge rather than overwrite.
     */
    private const CLAUSE_KEYS = ['tax_query', 'meta_query', 'date_query'];

    /**
     * Apply builder args to an existing WP_Query instance.
     *
     * Scalar args are set directly. Clause arrays are merged with any existing
     * clauses on the query using ClauseQuery::appendClause(), preserving clauses
     * added by other hooks.
     *
     * @param \WP_Query $query The existing query to modify.
     * @param array<string, mixed> $args Builder args from Quartermaster::toArgs().
     * @return void
     */
    public function modify(\WP_Query $query, array $args): void
    {
        foreach ($args as $key => $value) {
            if (in_array($key, self::CLAUSE_KEYS, true) && is_array($value)) {
                $this->mergeClause($query, $key, $value);
                continue;
            }

            $query->set($key, $value);
        }
    }

    /**
     * Merge a clause array into the query's existing clause for the given key.
     *
     * Extracts individual sub-clauses from the builder's clause array and appends
     * each one to the query's existing clause using ClauseQuery::appendClause().
     * The builder's relation is used as the default relation (not forced), allowing
     * the existing query's relation to take precedence when already set.
     *
     * @param \WP_Query $query
     * @param string $key One of 'tax_query', 'meta_query', or 'date_query'.
     * @param array<int|string, mixed> $builderClause The clause array from the builder.
     * @return void
     */
    private function mergeClause(\WP_Query $query, string $key, array $builderClause): void
    {
        $existing = $query->get($key);

        if (!is_array($existing)) {
            $existing = [];
        }

        $builderRelation = 'AND';

        if (isset($builderClause['relation'])) {
            $builderRelation = ClauseQuery::normalizeRelation(
                (string) $builderClause['relation']
            );
        }

        foreach ($builderClause as $clauseKey => $clause) {
            if ($clauseKey === 'relation') {
                continue;
            }

            if (!is_array($clause)) {
                continue;
            }

            $existing = ClauseQuery::appendClause(
                $existing,
                $clause,
                $builderRelation
            );
        }

        $query->set($key, $existing);
    }
}