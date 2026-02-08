<?php

namespace PressGang\Quartermaster\Bindings;

use PressGang\Quartermaster\Quartermaster;

/**
 * Callable factories for explicit query-var -> fluent-method bindings.
 *
 * Each factory returns a callable compatible with `Quartermaster::bindQueryVars()`.
 */
final class Bind
{
    /**
     * Bind a query var to pagination.
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/#pagination-parameters
     *
     * @param string $queryVar Informational only; the map key remains authoritative.
     * @return callable(Quartermaster, mixed, string): Quartermaster
     */
    public static function paged(string $queryVar = 'paged'): callable
    {
        return static function (Quartermaster $q, mixed $value, string $key) use ($queryVar): Quartermaster {
            if ($key !== $queryVar) {
                return $q;
            }

            $paged = (int) $value;

            if ($paged <= 0) {
                return $q;
            }

            return $q->paged(null, $paged);
        };
    }

    /**
     * Bind a query var to one `tax_query` clause.
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/#taxonomy-parameters
     *
     * @param string $taxonomy Target taxonomy.
     * @param string $field Taxonomy field, usually `slug`.
     * @param string $operator Tax query operator, usually `IN`.
     * @return callable(Quartermaster, mixed, string): Quartermaster
     */
    public static function tax(string $taxonomy, string $field = 'slug', string $operator = 'IN'): callable
    {
        return static function (Quartermaster $q, mixed $value, string $key) use ($taxonomy, $field, $operator): Quartermaster {
            unset($key);
            $terms = array_values(array_filter((array) $value, static fn (mixed $term): bool => $term !== null && $term !== ''));

            if ($terms === []) {
                return $q;
            }

            return $q->whereTax($taxonomy, $terms, $field, $operator);
        };
    }

    /**
     * Bind a query var to one numeric `meta_query` clause.
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/#custom-field-post-meta-parameters
     *
     * @param string $metaKey Meta key to query.
     * @param string $compare Meta compare operator.
     * @param string|null $queryVar Informational only; the map key remains authoritative.
     * @return callable(Quartermaster, mixed, string): Quartermaster
     */
    public static function metaNum(string $metaKey, string $compare, ?string $queryVar = null): callable
    {
        return static function (Quartermaster $q, mixed $value, string $key) use ($metaKey, $compare, $queryVar): Quartermaster {
            if ($queryVar !== null && $key !== $queryVar) {
                return $q;
            }

            if ($value === null || $value === '') {
                return $q;
            }

            $number = (float) $value;

            return $q->whereMeta($metaKey, $number, $compare, 'NUMERIC');
        };
    }

    /**
     * Bind a query var to the search term.
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/#search-parameters
     * See: https://developer.wordpress.org/reference/functions/sanitize_text_field/
     *
     * @param string $queryVar Informational only; the map key remains authoritative.
     * @return callable(Quartermaster, mixed, string): Quartermaster
     */
    public static function search(string $queryVar = 'search'): callable
    {
        return static function (Quartermaster $q, mixed $value, string $key) use ($queryVar): Quartermaster {
            if ($key !== $queryVar) {
                return $q;
            }

            $search = trim((string) $value);

            if ($search === '') {
                return $q;
            }

            if (function_exists('sanitize_text_field')) {
                $search = sanitize_text_field($search);
            }

            return $q->search($search);
        };
    }
}
