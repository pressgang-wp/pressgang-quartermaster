<?php


namespace PressGang\Quartermaster\Concerns;

use PressGang\Quartermaster\Support\ClauseQuery;

/**
 * Taxonomy query clause helpers.
 */
trait HasTaxQuery
{
    /**
     * @param string $taxonomy
     * @param array<int, int|string> $terms
     * @param string $field
     * @param string $operator
     * @return $this
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
