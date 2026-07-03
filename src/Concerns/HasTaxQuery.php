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
     * Append an `AND` taxonomy clause to `tax_query`.
     *
     * Null and empty terms are filtered out. When no terms remain, the builder
     * is unchanged — so optional filters can be passed directly
     * (`->whereTax('topic', $topic ?: null)`) without conditional wrappers.
     *
     * Sets: tax_query
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/#taxonomy-parameters
     *
     * @param string $taxonomy
     * @param string|int|array<int, int|string>|null $terms Term value(s) matched by `$field`. Scalars are normalized to a single-element array; null is a no-op.
     * @param string $field Tax field key such as `slug`, `term_id`, or `name`.
     * @param string $operator Tax operator such as `IN`, `NOT IN`, or `AND`.
     * @return self
     */
    public function whereTax(
        string $taxonomy,
        string|int|array|null $terms,
        string $field = 'slug',
        string $operator = 'IN'
    ): self {
        $terms = $this->normalizeTaxTerms($terms);

        if ($terms === []) {
            $this->record('whereTax', $taxonomy, $terms, $field, $operator);

            return $this;
        }

        $query = $this->appendTaxClause($this->buildTaxClause($taxonomy, $terms, $field, $operator));

        $this->set('tax_query', $query);
        $this->record('whereTax', $taxonomy, $terms, $field, $operator);

        return $this;
    }

    /**
     * Append an `OR` taxonomy clause to `tax_query`.
     *
     * The first `orWhereTax()` call switches the root `relation` to `OR`; subsequent clauses are
     * appended under the same relation. Mirrors `orWhereMeta()` for `meta_query`.
     *
     * Null and empty terms are filtered out. When no terms remain, the builder
     * is unchanged.
     *
     * Sets: tax_query
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/#taxonomy-parameters
     *
     * @param string $taxonomy
     * @param string|int|array<int, int|string>|null $terms Term value(s) matched by `$field`. Scalars are normalized to a single-element array; null is a no-op.
     * @param string $field Tax field key such as `slug`, `term_id`, or `name`.
     * @param string $operator Tax operator such as `IN`, `NOT IN`, or `AND`.
     * @return self
     */
    public function orWhereTax(
        string $taxonomy,
        string|int|array|null $terms,
        string $field = 'slug',
        string $operator = 'IN'
    ): self {
        $terms = $this->normalizeTaxTerms($terms);

        if ($terms === []) {
            $this->record('orWhereTax', $taxonomy, $terms, $field, $operator);

            return $this;
        }

        $query = $this->appendTaxClause($this->buildTaxClause($taxonomy, $terms, $field, $operator), 'OR', 'OR');

        $this->set('tax_query', $query);
        $this->record('orWhereTax', $taxonomy, $terms, $field, $operator);

        return $this;
    }

    /**
     * Normalize terms to a list, dropping empty string and null values.
     *
     * @param string|int|array<int, int|string>|null $terms
     * @return array<int, int|string>
     */
    protected function normalizeTaxTerms(string|int|array|null $terms): array
    {
        if ($terms === null) {
            return [];
        }

        $terms = is_array($terms) ? $terms : [$terms];

        return array_values(
            array_filter(
                $terms,
                static fn (mixed $term): bool => $term !== '' && $term !== null
            )
        );
    }

    /**
     * Build one WordPress-native `tax_query` clause.
     *
     * @param string $taxonomy
     * @param array<int, int|string> $terms
     * @param string $field
     * @param string $operator
     * @return array{taxonomy: string, field: string, terms: array<int, int|string>, operator: string}
     */
    protected function buildTaxClause(string $taxonomy, array $terms, string $field, string $operator): array
    {
        return [
            'taxonomy' => $taxonomy,
            'field' => $field,
            'terms' => $terms,
            'operator' => $operator,
        ];
    }

    /**
     * Append one taxonomy clause and normalize relation handling.
     *
     * The returned array stays compatible with WordPress `tax_query` expectations.
     *
     * @param array{taxonomy: string, field: string, terms: array<int, int|string>, operator: string} $clause
     * @param 'AND'|'OR' $defaultRelation
     * @param 'AND'|'OR'|null $forcedRelation
     * @return array<int|string, mixed>
     */
    protected function appendTaxClause(array $clause, string $defaultRelation = 'AND', ?string $forcedRelation = null): array
    {
        $query = $this->getArg('tax_query', []);

        if (!is_array($query)) {
            $query = [];
        }

        return ClauseQuery::appendClause($query, $clause, $defaultRelation, $forcedRelation);
    }
}
