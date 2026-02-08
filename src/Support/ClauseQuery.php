<?php

namespace PressGang\Quartermaster\Support;

/**
 * Shared helpers for `meta_query` and `tax_query` clause arrays.
 *
 * This utility centralizes relation handling so meta/tax builders follow the same WordPress-
 * native shape rules for single and multi-clause queries.
 *
 * See: https://developer.wordpress.org/reference/classes/wp_query/#custom-field-post-meta-parameters
 * See: https://developer.wordpress.org/reference/classes/wp_query/#taxonomy-parameters
 */
final class ClauseQuery
{
    /**
     * Append one clause and normalize root relation handling.
     *
     * Single-clause arrays do not include `relation`; multi-clause arrays include
     * `relation` with either the forced value, existing value, or default relation.
     *
     * @param array<int|string, mixed> $query
     * @param array<string, mixed> $clause
     * @param 'AND'|'OR' $defaultRelation
     * @param 'AND'|'OR'|null $forcedRelation
     * @return array<int|string, mixed>
     */
    public static function appendClause(
        array $query,
        array $clause,
        string $defaultRelation = 'AND',
        ?string $forcedRelation = null
    ): array {
        $existingClauseCount = 0;
        $hasExistingRelation = false;
        $existingRelation = $defaultRelation;

        foreach ($query as $key => $value) {
            if ($key === 'relation') {
                $hasExistingRelation = true;
                $existingRelation = self::normalizeRelation((string) $value, $defaultRelation);
                continue;
            }

            if (is_array($value)) {
                $existingClauseCount++;
            }
        }

        $query[] = $clause;
        $totalClauseCount = $existingClauseCount + 1;

        if ($totalClauseCount === 1) {
            unset($query['relation']);

            return $query;
        }

        $query['relation'] = $forcedRelation
            ?? ($hasExistingRelation ? $existingRelation : $defaultRelation);

        return $query;
    }

    /**
     * Normalize a relation token to WordPress-supported values.
     *
     * @param string $relation
     * @param 'AND'|'OR' $defaultRelation
     * @return 'AND'|'OR'
     */
    public static function normalizeRelation(string $relation, string $defaultRelation = 'AND'): string
    {
        $upper = strtoupper($relation);

        if ($upper === 'OR') {
            return 'OR';
        }

        if ($upper === 'AND') {
            return 'AND';
        }

        return $defaultRelation;
    }
}
