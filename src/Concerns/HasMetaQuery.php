<?php


namespace PressGang\Quartermaster\Concerns;

use PressGang\Quartermaster\Support\ClauseQuery;

/**
 * Meta query clause helpers.
 */
trait HasMetaQuery
{
    /**
     * @param string $key
     * @param mixed $value
     * @param string $compare
     * @param string|null $type
     * @return $this
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
     * @param string $key
     * @param mixed $value
     * @param string $compare
     * @param string|null $type
     * @return $this
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
