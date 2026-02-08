<?php


namespace PressGang\Quartermaster\Concerns;

use PressGang\Quartermaster\Support\ClauseQuery;

/**
 * Fluent builders for `WP_Query` `tax_query` clauses.
 *
 * Clauses are appended using WordPress-native array structure. A single clause is stored as
 * `tax_query = [ clause ]`; once multiple clauses exist, a root `relation` key is included.
 *
 * See: https://developer.wordpress.org/reference/classes/wp_query/#taxonomy-parameters
 */
trait HasTaxQuery
{
    /**
     * Append a taxonomy clause to `tax_query`.
     *
     * Empty terms are filtered out. When no terms remain, the builder is unchanged.
     *
     * @param string $taxonomy
     * @param array<int, int|string> $terms Term values matched by `$field`.
     * @param string $field Tax field key such as `slug`, `term_id`, or `name`.
     * @param string $operator Tax operator such as `IN`, `NOT IN`, or `AND`.
     * @return self
     */
    public function whereTax(
        string $taxonomy,
        array $terms,
        string $field = 'slug',
        string $operator = 'IN'
    ): self {
        $terms = array_values(
            array_filter(
                $terms,
                static fn (mixed $term): bool => $term !== '' && $term !== null
            )
        );

        if ($terms === []) {
            $this->record('whereTax', $taxonomy, $terms, $field, $operator);

            return $this;
        }

        $clause = [
            'taxonomy' => $taxonomy,
            'field' => $field,
            'terms' => $terms,
            'operator' => $operator,
        ];
        $query = $this->appendTaxClause($clause);

        $this->set('tax_query', $query);
        $this->record('whereTax', $taxonomy, $terms, $field, $operator);

        return $this;
    }

    /**
     * Append one taxonomy clause and normalize relation handling.
     *
     * The returned array stays compatible with WordPress `tax_query` expectations.
     *
     * @param array{taxonomy: string, field: string, terms: array<int, int|string>, operator: string} $clause
     * @return array<int|string, mixed>
     */
    protected function appendTaxClause(array $clause): array
    {
        $query = $this->get('tax_query', []);

        if (!is_array($query)) {
            $query = [];
        }

        return ClauseQuery::appendClause($query, $clause, 'AND');
    }
}
