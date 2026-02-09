<?php

namespace PressGang\Quartermaster\Terms;

use PressGang\Quartermaster\Adapters\TimberTermAdapter;
use PressGang\Quartermaster\Concerns\HasArgs;
use PressGang\Quartermaster\Concerns\HasConditionals;
use PressGang\Quartermaster\Concerns\HasDebugging;
use PressGang\Quartermaster\Support\ClauseQuery;
use PressGang\Quartermaster\Support\Warnings;

/**
 * Args-first fluent builder for `WP_Term_Query` / `get_terms()` arguments.
 *
 * Like Quartermaster's posts builder, this class is opt-in only and does not add defaults.
 *
 * See: https://developer.wordpress.org/reference/classes/wp_term_query/
 * See: https://developer.wordpress.org/reference/functions/get_terms/
 *
 * @property array<string, mixed> $args Current mutable term-query argument payload.
 * @property array<int, array{name: string, params: array<int, mixed>}> $applied Call log used by `explain()`.
 */
final class TermsBuilder
{
    use HasArgs;
    use HasConditionals;
    use HasDebugging;

    /**
     * Create a new term builder with optional seed args.
     *
     * Sets: (none)
     *
     * @param array<string, mixed> $args
     */
    public function __construct(array $args = [])
    {
        $this->args = $args;
    }

    /**
     * Start a terms builder with optional seed taxonomy or seed args.
     *
     * String (or list array) input seeds `taxonomy`; associative-array input seeds raw args.
     *
     * Sets: taxonomy
     *
     * See: https://developer.wordpress.org/reference/classes/wp_term_query/
     *
     * @param string|array<int|string, mixed>|null $taxonomy Taxonomy slug/slugs or seed args.
     * @return self
     */
    public static function prepare(string|array|null $taxonomy = null): self
    {
        if (is_array($taxonomy) && !array_is_list($taxonomy)) {
            return new self($taxonomy);
        }

        $builder = new self();

        if ($taxonomy !== null) {
            $builder->taxonomy($taxonomy);
        }

        return $builder;
    }

    /**
     * Set explicit `taxonomy` constraints.
     *
     * Sets: taxonomy
     *
     * See: https://developer.wordpress.org/reference/classes/wp_term_query/#parameters
     *
     * @param string|array<int, string> $taxonomy
     * @return self
     */
    public function taxonomy(string|array $taxonomy): self
    {
        $this->set('taxonomy', $taxonomy);
        $this->record('taxonomy', $taxonomy);

        return $this;
    }

    /**
     * Set `object_ids` to retrieve terms associated with specific objects.
     *
     * Sets: object_ids
     *
     * See: https://developer.wordpress.org/reference/classes/wp_term_query/#parameters
     *
     * @param int|array<int, int> $objectIds
     * @return self
     */
    public function objectIds(int|array $objectIds): self
    {
        $this->set('object_ids', $objectIds);
        $this->record('objectIds', $objectIds);

        return $this;
    }

    /**
     * Set `hide_empty`.
     *
     * Sets: hide_empty
     *
     * See: https://developer.wordpress.org/reference/classes/wp_term_query/#parameters
     *
     * @param bool $hide
     * @return self
     */
    public function hideEmpty(bool $hide = true): self
    {
        $this->set('hide_empty', $hide);
        $this->record('hideEmpty', $hide);

        return $this;
    }

    /**
     * Set `slug` filter.
     *
     * Sets: slug
     *
     * See: https://developer.wordpress.org/reference/classes/wp_term_query/#parameters
     *
     * @param string|array<int, string> $slug
     * @return self
     */
    public function slug(string|array $slug): self
    {
        $this->set('slug', $slug);
        $this->record('slug', $slug);

        return $this;
    }

    /**
     * Set `name` filter.
     *
     * Sets: name
     *
     * See: https://developer.wordpress.org/reference/classes/wp_term_query/#parameters
     *
     * @param string|array<int, string> $name
     * @return self
     */
    public function name(string|array $name): self
    {
        $this->set('name', $name);
        $this->record('name', $name);

        return $this;
    }

    /**
     * Set `fields` to control the return format.
     *
     * Accepted values: all, all_with_object_id, ids, tt_ids, names, slugs, count,
     * id=>parent, id=>name, id=>slug.
     *
     * Sets: fields
     *
     * See: https://developer.wordpress.org/reference/classes/wp_term_query/#parameters
     *
     * @param string $fields
     * @return self
     */
    public function fields(string $fields): self
    {
        $this->set('fields', $fields);
        $this->record('fields', $fields);

        return $this;
    }

    /**
     * Set `include` term IDs.
     *
     * Sets: include
     *
     * See: https://developer.wordpress.org/reference/classes/wp_term_query/#parameters
     *
     * @param array<int, int> $ids
     * @return self
     */
    public function include(array $ids): self
    {
        $this->set('include', array_values($ids));
        $this->record('include', $ids);

        return $this;
    }

    /**
     * Set `exclude` term IDs.
     *
     * Sets: exclude
     *
     * See: https://developer.wordpress.org/reference/classes/wp_term_query/#parameters
     *
     * @param array<int, int> $ids
     * @return self
     */
    public function exclude(array $ids): self
    {
        $this->set('exclude', array_values($ids));
        $this->record('exclude', $ids);

        return $this;
    }

    /**
     * Set `exclude_tree` to exclude a term and all its descendants.
     *
     * Sets: exclude_tree
     *
     * See: https://developer.wordpress.org/reference/classes/wp_term_query/#parameters
     *
     * @param int|array<int, int> $ids
     * @return self
     */
    public function excludeTree(int|array $ids): self
    {
        $this->set('exclude_tree', $ids);
        $this->record('excludeTree', $ids);

        return $this;
    }

    /**
     * Set `parent` term ID.
     *
     * Sets: parent
     *
     * See: https://developer.wordpress.org/reference/classes/wp_term_query/#parameters
     *
     * @param int $parentId
     * @return self
     */
    public function parent(int $parentId): self
    {
        $this->set('parent', $parentId);
        $this->record('parent', $parentId);

        return $this;
    }

    /**
     * Set `child_of` to retrieve all descendants of a term.
     *
     * Sets: child_of
     *
     * See: https://developer.wordpress.org/reference/classes/wp_term_query/#parameters
     *
     * @param int $termId
     * @return self
     */
    public function childOf(int $termId): self
    {
        $this->set('child_of', $termId);
        $this->record('childOf', $termId);

        return $this;
    }

    /**
     * Set `childless` to limit results to terms with no children.
     *
     * Sets: childless
     *
     * See: https://developer.wordpress.org/reference/classes/wp_term_query/#parameters
     *
     * @param bool $childless
     * @return self
     */
    public function childless(bool $childless = true): self
    {
        $this->set('childless', $childless);
        $this->record('childless', $childless);

        return $this;
    }

    /**
     * Set `search` with basic trim sanitization.
     *
     * Blank strings are ignored and advisory warning is recorded for explainability.
     *
     * Sets: search
     *
     * See: https://developer.wordpress.org/reference/classes/wp_term_query/#parameters
     *
     * @param string $search
     * @return self
     */
    public function search(string $search): self
    {
        $trimmed = trim($search);

        if ($trimmed === '') {
            $this->warn('search() received an empty value and was ignored.');
            $this->record('search', $search);

            return $this;
        }

        $this->set('search', $trimmed);
        $this->record('search', $search);

        return $this;
    }

    /**
     * Set `number` for term limits.
     *
     * Sets: number
     *
     * See: https://developer.wordpress.org/reference/classes/wp_term_query/#parameters
     *
     * @param int $number
     * @return self
     */
    public function limit(int $number): self
    {
        $this->set('number', $number);
        $this->record('limit', $number);

        return $this;
    }

    /**
     * Set `offset`.
     *
     * Sets: offset
     *
     * See: https://developer.wordpress.org/reference/classes/wp_term_query/#parameters
     *
     * @param int $offset
     * @return self
     */
    public function offset(int $offset): self
    {
        $this->set('offset', $offset);
        $this->record('offset', $offset);

        return $this;
    }

    /**
     * Convenience pagination helper that computes explicit `offset` from `page` and `perPage`.
     *
     * This method sets only `number` and `offset`.
     *
     * Sets: number, offset
     *
     * See: https://developer.wordpress.org/reference/classes/wp_term_query/#parameters
     *
     * @param int $page
     * @param int $perPage
     * @return self
     */
    public function page(int $page, int $perPage): self
    {
        $safePage = max(1, $page);
        $safePerPage = max(1, $perPage);
        $offset = ($safePage - 1) * $safePerPage;

        $this->merge([
            'number' => $safePerPage,
            'offset' => $offset,
        ]);
        $this->record('page', $page, $perPage);

        return $this;
    }

    /**
     * Set ordering args.
     *
     * Sets: orderby, order
     *
     * See: https://developer.wordpress.org/reference/classes/wp_term_query/#parameters
     *
     * @param string $orderby
     * @param string $order
     * @return self
     */
    public function orderBy(string $orderby, string $order = 'ASC'): self
    {
        $normalizedOrder = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

        if (strtoupper($order) !== $normalizedOrder) {
            $this->warn(sprintf('orderBy() received invalid order "%s"; defaulted to "%s".', $order, $normalizedOrder));
        }

        $this->merge([
            'orderby' => $orderby,
            'order' => $normalizedOrder,
        ]);
        $this->record('orderBy', $orderby, $order);

        return $this;
    }

    /**
     * Append an `AND` termmeta clause to `meta_query`.
     *
     * Sets: meta_query
     *
     * See: https://developer.wordpress.org/reference/classes/wp_term_query/#parameters
     *
     * @param string $key
     * @param mixed $value
     * @param string $compare
     * @param string $type
     * @return self
     */
    public function whereMeta(string $key, mixed $value, string $compare = '=', string $type = 'CHAR'): self
    {
        $clause = $this->buildMetaClause($key, $value, $compare, $type);
        $query = $this->appendMetaClause($clause, 'AND');

        $this->set('meta_query', $query);
        $this->record('whereMeta', $key, $value, $compare, $type);

        return $this;
    }

    /**
     * Append a termmeta clause and force root relation to `OR`.
     *
     * Sets: meta_query
     *
     * See: https://developer.wordpress.org/reference/classes/wp_term_query/#parameters
     *
     * @param string $key
     * @param mixed $value
     * @param string $compare
     * @param string $type
     * @return self
     */
    public function orWhereMeta(string $key, mixed $value, string $compare = '=', string $type = 'CHAR'): self
    {
        $clause = $this->buildMetaClause($key, $value, $compare, $type);
        $query = $this->appendMetaClause($clause, 'OR', 'OR');

        $this->set('meta_query', $query);
        $this->record('orWhereMeta', $key, $value, $compare, $type);

        return $this;
    }

    /**
     * Apply an explicit argument transform callback.
     *
     * Sets: (dynamic)
     *
     * @param callable(array<string, mixed>): array<string, mixed> $fn
     * @return self
     */
    public function tapArgs(callable $fn): self
    {
        $next = $fn($this->toArgs());
        $this->args = $next;
        $this->record('tapArgs', $fn);

        return $this;
    }

    /**
     * Execute `get_terms()` with current args.
     *
     * Sets: (none)
     *
     * See: https://developer.wordpress.org/reference/functions/get_terms/
     *
     * @return array<int, mixed>|\WP_Error
     */
    public function get(): array|\WP_Error
    {
        return \get_terms($this->toArgs());
    }

    /**
     * Execute `Timber::get_terms()` with current args.
     *
     * Timber is optional and guarded at runtime. This method does not mutate args and does
     * not add implicit defaults.
     *
     * Sets: (none)
     *
     * See: https://timber.github.io/docs/v2/reference/timber-timber/#get_terms
     *
     * @return iterable<int, object> Timber term objects.
     */
    public function timber(): iterable
    {
        return (new TimberTermAdapter())->getTerms($this->toArgs());
    }

    /**
     * Return inspectable builder state for debugging.
     *
     * Uses term-specific warnings instead of `WP_Query` warnings.
     *
     * Sets: (none)
     *
     * @return array{
     *     args: array<string, mixed>,
     *     applied: array<int, array{name: string, params: array<int, mixed>}>,
     *     warnings: array<int, string>,
     *     bindings?: array<int, array{key: string, applied: bool, reason: string, value: string}>
     * }
     */
    public function explain(): array
    {
        $args = $this->toArgs();
        $explain = [
            'args' => $args,
            'applied' => $this->applied,
            'warnings' => array_values(array_unique(array_merge(
                Warnings::fromTermArgs($args),
                $this->runtimeWarnings,
            ))),
        ];

        if ($this->bindings !== []) {
            $explain['bindings'] = $this->bindings;
        }

        return $explain;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param string $compare
     * @param string $type
     * @return array{key: string, value: mixed, compare: string, type: string}
     */
    private function buildMetaClause(string $key, mixed $value, string $compare, string $type): array
    {
        return [
            'key' => $key,
            'value' => $value,
            'compare' => $compare,
            'type' => strtoupper($type),
        ];
    }

    /**
     * @param array{key: string, value: mixed, compare: string, type: string} $clause
     * @param 'AND'|'OR' $defaultRelation
     * @param 'AND'|'OR'|null $forcedRelation
     * @return array<int|string, mixed>
     */
    private function appendMetaClause(array $clause, string $defaultRelation, ?string $forcedRelation = null): array
    {
        $query = $this->getArg('meta_query', []);

        if (!is_array($query)) {
            $query = [];
        }

        return ClauseQuery::appendClause($query, $clause, $defaultRelation, $forcedRelation);
    }
}
