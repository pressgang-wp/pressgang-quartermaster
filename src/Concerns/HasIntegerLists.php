<?php

namespace PressGang\Quartermaster\Concerns;

/**
 * Integer list normalization for ID-based query helpers.
 */
trait HasIntegerLists
{
    /**
     * Normalize a mixed list into integer IDs.
     *
     * Accepts integers and integer-like scalar values; invalid values are removed.
     *
     * @param array<int, mixed> $values
     * @return array<int, int>
     */
    protected function normalizeIntList(array $values): array
    {
        $normalized = [];

        foreach ($values as $value) {
            if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                continue;
            }

            $normalized[] = (int) $value;
        }

        return array_values($normalized);
    }
}
