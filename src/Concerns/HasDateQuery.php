<?php

namespace PressGang\Quartermaster\Concerns;

use PressGang\Quartermaster\Support\ClauseQuery;

/**
 * Fluent builders for `WP_Query` `date_query` clauses.
 *
 * Clauses are appended using WordPress-native array structure. A single clause is stored as
 * `date_query = [ clause ]`; once multiple clauses exist, a root `relation` key is included.
 *
 * See: https://developer.wordpress.org/reference/classes/wp_query/#date-parameters
 */
trait HasDateQuery
{
    /**
     * Append one raw WordPress-native date clause to `date_query`.
     *
     * This is opt-in and does not parse or validate the provided clause shape.
     *
     * Sets: date_query
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/#date-parameters
     *
     * @param array<string, mixed> $clause Raw date clause in WP-native shape.
     * @return self
     */
    public function whereDate(array $clause): self
    {
        $query = $this->appendDateClause($clause);

        $this->set('date_query', $query);
        $this->record('whereDate', $clause);

        return $this;
    }

    /**
     * Append a date clause using the WordPress `after` key.
     *
     * This is opt-in and sets only `date_query`.
     *
     * Sets: date_query
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/#date-parameters
     *
     * @param string|array<string, int|string> $after Date expression accepted by `WP_Query`.
     * @param bool $inclusive Whether the boundary date should be included.
     * @return self
     */
    public function whereDateAfter(string|array $after, bool $inclusive = true): self
    {
        return $this->whereDate([
            'after' => $after,
            'inclusive' => $inclusive,
        ]);
    }

    /**
     * Append a date clause using the WordPress `before` key.
     *
     * This is opt-in and sets only `date_query`.
     *
     * Sets: date_query
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/#date-parameters
     *
     * @param string|array<string, int|string> $before Date expression accepted by `WP_Query`.
     * @param bool $inclusive Whether the boundary date should be included.
     * @return self
     */
    public function whereDateBefore(string|array $before, bool $inclusive = true): self
    {
        return $this->whereDate([
            'before' => $before,
            'inclusive' => $inclusive,
        ]);
    }

    /**
     * Append one date clause and normalize relation handling.
     *
     * The returned array stays compatible with WordPress `date_query` expectations.
     *
     * @param array<string, mixed> $clause
     * @return array<int|string, mixed>
     */
    protected function appendDateClause(array $clause): array
    {
        $query = $this->getArg('date_query', []);

        if (!is_array($query)) {
            $query = [];
        }

        return ClauseQuery::appendClause($query, $clause, 'AND');
    }
}
