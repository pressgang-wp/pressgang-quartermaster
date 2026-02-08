<?php


namespace PressGang\Quartermaster\Concerns;

/**
 * Meta query clause helpers.
 */
trait HasMetaQuery
{
    /**
     * @param string $key
     * @param mixed $value
     * @param string $compare
     * @param string $type
     * @return $this
     */
    public function whereMeta(string $key, mixed $value, string $compare = '=', string $type = 'CHAR'): self
    {
        $query = $this->normalizeMetaQuery('AND');
        $query[] = [
            'key' => $key,
            'value' => $value,
            'compare' => $compare,
            'type' => $type,
        ];

        $this->set('meta_query', $query);
        $this->record('whereMeta', $key, $value, $compare, $type);

        return $this;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param string $compare
     * @param string $type
     * @return $this
     */
    public function orWhereMeta(string $key, mixed $value, string $compare = '=', string $type = 'CHAR'): self
    {
        $query = $this->normalizeMetaQuery('OR');
        $query[] = [
            'key' => $key,
            'value' => $value,
            'compare' => $compare,
            'type' => $type,
        ];

        $this->set('meta_query', $query);
        $this->record('orWhereMeta', $key, $value, $compare, $type);

        return $this;
    }

    /**
     * @param 'AND'|'OR' $relation
     * @return array<int|string, mixed>
     */
    protected function normalizeMetaQuery(string $relation = 'AND'): array
    {
        $query = $this->get('meta_query', []);

        if (!is_array($query)) {
            $query = [];
        }

        $query['relation'] = $relation;

        return $query;
    }
}
