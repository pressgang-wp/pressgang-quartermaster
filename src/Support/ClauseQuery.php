<?php

namespace PressGang\Quartermaster\Support;

/**
 * Shared helpers for WP meta/tax query clause arrays.
 */
final class ClauseQuery
{
    /**
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
