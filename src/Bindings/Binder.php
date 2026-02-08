<?php

namespace PressGang\Quartermaster\Bindings;

/**
 * Fluent query-var binding configurator.
 *
 * This class registers explicit bindings and exports them as a plain map consumable by
 * `Quartermaster::bindQueryVars()`.
 */
final class Binder
{
    /**
     * @var array<string, callable>
     */
    private array $map = [];

    /**
     * Bind pagination from one query var.
     *
     * @param string $queryVar
     * @return self
     */
    public function paged(string $queryVar = 'paged'): self
    {
        return $this->register($queryVar, Bind::paged($queryVar));
    }

    /**
     * Bind search from one query var.
     *
     * @param string $queryVar
     * @return self
     */
    public function search(string $queryVar = 'search'): self
    {
        return $this->register($queryVar, Bind::search($queryVar));
    }

    /**
     * Bind a query var to a taxonomy clause.
     *
     * If `$taxonomy` is omitted, the query-var key is used as the taxonomy name.
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/#taxonomy-parameters
     *
     * @param string $queryVar Query-var key.
     * @param string|null $taxonomy Target taxonomy; null defaults to `$queryVar`.
     * @param string $field Taxonomy field, usually `slug`.
     * @param string $operator Tax query operator, usually `IN`.
     * @return self
     */
    public function tax(string $queryVar, ?string $taxonomy = null, string $field = 'slug', string $operator = 'IN'): self
    {
        $resolvedTaxonomy = $taxonomy ?? $queryVar;

        return $this->register($queryVar, Bind::tax($resolvedTaxonomy, $field, $operator));
    }

    /**
     * Start configuring a numeric meta binding.
     *
     * @param string $queryVar
     * @return MetaBinding
     */
    public function metaNum(string $queryVar): MetaBinding
    {
        return new MetaBinding($this, $queryVar);
    }

    /**
     * Register one binding in the internal map.
     *
     * @param string $queryVar
     * @param callable $binding
     * @return self
     */
    public function register(string $queryVar, callable $binding): self
    {
        $this->map[$queryVar] = $binding;

        return $this;
    }

    /**
     * Export configured bindings as a plain map.
     *
     * @return array<string, callable>
     */
    public function toMap(): array
    {
        return $this->map;
    }
}
