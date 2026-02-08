<?php


namespace PressGang\Quartermaster\Concerns;

use PressGang\Quartermaster\Support\ClauseQuery;

/**
 * Fluent builders for `WP_Query` `meta_query` clauses.
 *
 * Clauses are appended using WordPress-native array structure. A single clause is stored as
 * `meta_query = [ clause ]`; once multiple clauses exist, a root `relation` key is included.
 *
 * See: https://developer.wordpress.org/reference/classes/wp_query/#custom-field-post-meta-parameters
 */
trait HasMetaQuery
{
    /**
     * Append a date-based meta clause (`type = DATE`) to `meta_query`.
     *
     * This is opt-in. If `$value` is null, the method resolves "today" using `wp_date($format)`
     * (WordPress timezone aware) when available.
     *
     * Sets: meta_query
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/#custom-field-post-meta-parameters
     * See: https://developer.wordpress.org/reference/functions/wp_date/
     *
     * @param string $key Meta key used in the clause.
     * @param string $operator WordPress compare operator, for example `>=` or `<`.
     * @param string|int|null $value Clause value; null resolves to today's date string.
     * @param string $format Date format passed to `wp_date()`, defaults to `Ymd`.
     * @return self
     */
    public function whereMetaDate(
        string $key,
        string $operator,
        string|int|null $value = null,
        string $format = 'Ymd'
    ): self {
        $resolvedValue = $value;

        if ($resolvedValue === null) {
            $resolvedValue = function_exists('wp_date') ? wp_date($format) : date($format);
        }

        $this->record('whereMetaDate', $key, $operator, $resolvedValue, $format);

        return $this->whereMeta($key, $resolvedValue, $operator, 'DATE');
    }

    /**
     * Append an `AND` meta clause to `meta_query`.
     *
     * The clause contains `key`, `value`, `compare`, and optional `type`.
     *
     * Sets: meta_query
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/#custom-field-post-meta-parameters
     *
     * @param string $key
     * @param mixed $value
     * @param string $compare WordPress meta compare operator, for example `=`, `>=`, `IN`.
     * @param string|null $type Optional WordPress meta type, for example `NUMERIC` or `DATE`.
     * @return self
     */
    public function whereMeta(string $key, mixed $value, string $compare = '=', ?string $type = null): self
    {
        $clause = $this->buildMetaClause($key, $value, $compare, $type);
        $query = $this->appendMetaClause($clause, 'AND');

        $this->set('meta_query', $query);
        $this->record('whereMeta', $key, $value, $compare, $type);

        return $this;
    }

    /**
     * Append a meta clause and force root relation to `OR`.
     *
     * This v0 method uses simple behavior: when multiple clauses exist, root `relation`
     * is set to `OR`.
     *
     * Sets: meta_query
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/#custom-field-post-meta-parameters
     *
     * @param string $key
     * @param mixed $value
     * @param string $compare WordPress meta compare operator.
     * @param string|null $type Optional WordPress meta type.
     * @return self
     */
    public function orWhereMeta(string $key, mixed $value, string $compare = '=', ?string $type = null): self
    {
        $clause = $this->buildMetaClause($key, $value, $compare, $type);
        $query = $this->appendMetaClause($clause, 'OR', 'OR');

        $this->set('meta_query', $query);
        $this->record('orWhereMeta', $key, $value, $compare, $type);

        return $this;
    }

    /**
     * Build one meta clause compatible with `WP_Query` `meta_query`.
     *
     * @param string $key
     * @param mixed $value
     * @param string $compare
     * @param string|null $type
     * @return array{key: string, value: mixed, compare: string, type?: string}
     */
    protected function buildMetaClause(string $key, mixed $value, string $compare, ?string $type): array
    {
        $clause = [
            'key' => $key,
            'value' => $value,
            'compare' => $compare,
        ];

        if ($type !== null && $type !== '') {
            $clause['type'] = strtoupper($type);
        }

        return $clause;
    }

    /**
     * Append one meta clause and normalize relation handling.
     *
     * The returned array stays compatible with WordPress `meta_query` expectations.
     *
     * @param array{key: string, value: mixed, compare: string, type?: string} $clause
     * @param 'AND'|'OR' $defaultRelation
     * @param 'AND'|'OR'|null $forcedRelation
     * @return array<int|string, mixed>
     */
    protected function appendMetaClause(array $clause, string $defaultRelation, ?string $forcedRelation = null): array
    {
        $query = $this->get('meta_query', []);

        if (!is_array($query)) {
            $query = [];
        }

        return ClauseQuery::appendClause($query, $clause, $defaultRelation, $forcedRelation);
    }
}
