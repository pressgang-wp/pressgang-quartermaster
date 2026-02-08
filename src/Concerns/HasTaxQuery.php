<?php


namespace PressGang\Quartermaster\Concerns;

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
     * @param string $relation
     * @return $this
     */
    public function whereTax(
        string $taxonomy,
        array $terms,
        string $field = 'term_id',
        string $operator = 'IN',
        string $relation = 'AND'
    ): self {
        $query = $this->normalizeTaxQuery($relation);
        $query[] = [
            'taxonomy' => $taxonomy,
            'field' => $field,
            'terms' => $terms,
            'operator' => $operator,
        ];

        $this->set('tax_query', $query);
        $this->record('whereTax', $taxonomy, $terms, $field, $operator, $relation);

        return $this;
    }

    /**
     * @param string $relation
     * @return array<int|string, mixed>
     */
    protected function normalizeTaxQuery(string $relation = 'AND'): array
    {
        $query = $this->get('tax_query', []);

        if (!is_array($query)) {
            $query = [];
        }

        $query['relation'] = strtoupper($relation) === 'OR' ? 'OR' : 'AND';

        return $query;
    }
}
