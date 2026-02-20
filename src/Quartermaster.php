<?php

namespace PressGang\Quartermaster;

use PressGang\Quartermaster\Adapters\QueryModifierAdapter;
use PressGang\Quartermaster\Adapters\TimberAdapter;
use PressGang\Quartermaster\Adapters\WpAdapter;
use PressGang\Quartermaster\Bindings\Binder;
use PressGang\Quartermaster\Bindings\WordPressQueryVarSource;
use PressGang\Quartermaster\Concerns\HasArgs;
use PressGang\Quartermaster\Concerns\HasAuthorConstraints;
use PressGang\Quartermaster\Concerns\HasConditionals;
use PressGang\Quartermaster\Concerns\HasDateQuery;
use PressGang\Quartermaster\Concerns\HasDebugging;
use PressGang\Quartermaster\Concerns\HasIntegerLists;
use PressGang\Quartermaster\Concerns\HasMacros;
use PressGang\Quartermaster\Concerns\HasMetaQuery;
use PressGang\Quartermaster\Concerns\HasOrdering;
use PressGang\Quartermaster\Concerns\HasPagination;
use PressGang\Quartermaster\Concerns\HasPostConstraints;
use PressGang\Quartermaster\Concerns\HasQueryFlags;
use PressGang\Quartermaster\Concerns\HasSearch;
use PressGang\Quartermaster\Concerns\HasTaxQuery;
use PressGang\Quartermaster\Contracts\QueryVarSource;
use PressGang\Quartermaster\Terms\TermsBuilder;

/**
 * Args-first fluent builder for WordPress `WP_Query` arguments.
 *
 * `prepare()`/`posts()` are zero side effects: no default `WP_Query` keys are added unless
 * a fluent method is explicitly called. Optional seeds only set explicitly provided values.
 * Terminal methods expose args directly (`toArgs()`), instantiate `WP_Query` (`wpQuery()`),
 * or return a guarded Timber query object (`timber()`).
 *
 * See: https://developer.wordpress.org/reference/classes/wp_query/#parameters
 *
 * @property array<string, mixed> $args Current mutable `WP_Query` argument payload.
 * @property array<int, array{name: string, params: array<int, mixed>}> $applied Call log used by `explain()`.
 */
final class Quartermaster
{
    use HasArgs;
    use HasAuthorConstraints;
    use HasConditionals;
    use HasDateQuery;
    use HasDebugging;
    use HasIntegerLists;
    use HasMacros;
    use HasMetaQuery;
    use HasOrdering;
    use HasPagination;
    use HasPostConstraints;
    use HasQueryFlags;
    use HasSearch;
    use HasTaxQuery;

    /**
     * Create a new builder with optional seed args.
     *
     * This constructor does not add defaults; it only stores the provided array.
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
     * Start a fluent posts builder with optional seed.
     *
     * This is opt-in only: with no input, the builder starts with an empty args array.
     * String (or list array) input seeds `post_type`; associative-array input seeds raw args.
     *
     * Sets: post_type
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/#post-type-parameters
     *
     * @param string|array<int|string, mixed>|null $postType Post type slug/slugs or seed args.
     * @return self
     */
    public static function prepare(string|array|null $postType = null): self
    {
        if (is_array($postType) && !array_is_list($postType)) {
            return new self($postType);
        }

        $builder = new self();

        if ($postType !== null) {
            $builder->postType($postType);
        }

        return $builder;
    }

    /**
     * Preferred posts entrypoint.
     *
     * Delegates to `prepare()` and preserves the same zero-default behavior.
     *
     * Sets: post_type
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/#post-type-parameters
     *
     * @param string|array<int|string, mixed>|null $postType Post type slug/slugs or seed args.
     * @return self
     */
    public static function posts(string|array|null $postType = null): self
    {
        return self::prepare($postType);
    }

    /**
     * Preferred terms entrypoint for `WP_Term_Query` / `get_terms()` args.
     *
     * With a string input, this seeds `taxonomy`. With an associative array, this seeds
     * explicit term-query args. With no input, the args payload starts empty.
     *
     * Sets: taxonomy
     *
     * See: https://developer.wordpress.org/reference/classes/wp_term_query/
     * See: https://developer.wordpress.org/reference/functions/get_terms/
     *
     * @param string|array<int|string, mixed>|null $taxonomy Taxonomy seed or term-query seed args.
     * @return TermsBuilder
     */
    public static function terms(string|array|null $taxonomy = null): TermsBuilder
    {
        return TermsBuilder::prepare($taxonomy);
    }

    /**
     * Apply an explicit argument transform callback.
     *
     * The callback receives the current args and must return a full args array replacement.
     * This method is an escape hatch for custom behavior while preserving fluent chaining.
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
     * Bind query vars into fluent calls using either a map or fluent binder callback.
     *
     * This is strictly opt-in: query vars are only read when this method is called.
     *
     * Sets: (dynamic)
     *
     * See: https://developer.wordpress.org/reference/functions/get_query_var/
     *
     * @param array<string, callable>|callable(Binder): void $bindings
     * @param QueryVarSource|null $source Query-var source; defaults to WordPress runtime.
     * @return self
     */
    public function bindQueryVars(array|callable $bindings, ?QueryVarSource $source = null): self
    {
        $map = $this->normaliseBindingsToMap($bindings);
        $resolvedSource = $source ?? new WordPressQueryVarSource();

        return $this->applyBindingMap($map, $resolvedSource);
    }

    /**
     * Execute the query and return the posts array.
     *
     * This is the primary terminal: it runs `new WP_Query($args)` and returns the
     * resulting `posts` array. Use `wpQuery()` instead when you need the full `WP_Query`
     * object (pagination metadata, found rows, loop state).
     *
     * Sets: (none)
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/
     *
     * @return array<int, \WP_Post> Posts returned by the query.
     */
    public function get(): array
    {
        return $this->wpQuery()->posts;
    }

    /**
     * Build and return a `WP_Query` instance from the current args.
     *
     * Use this when you need the full query object for pagination metadata
     * (`found_posts`, `max_num_pages`) or loop helpers. For just the posts
     * array, prefer `get()`.
     *
     * Sets: (none)
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/
     *
     * @return \WP_Query Query instance created with `new \WP_Query($this->toArgs())`.
     */
    public function wpQuery(): \WP_Query
    {
        return (new WpAdapter())->wpQuery($this->toArgs());
    }

    /**
     * Build and return a Timber `PostQuery` object from the current args.
     *
     * Timber is optional and guarded at runtime. This method does not mutate args and does
     * not add implicit defaults.
     *
     * Sets: (none)
     *
     * See: https://timber.github.io/docs/v2/reference/timber-postquery/
     *
     * @return \Timber\PostQuery
     */
    public function timber(): \Timber\PostQuery
    {
        return (new TimberAdapter())->postQuery($this->toArgs());
    }

    /**
     * Apply the builder's args to an existing `WP_Query` instance.
     *
     * This terminal is designed for `pre_get_posts` hooks where WordPress provides
     * an existing query object. Scalar args are set directly via `$query->set()`.
     * Clause arrays (`tax_query`, `meta_query`, `date_query`) are merged with
     * existing clauses so that modifications from multiple hooks compose safely.
     *
     * Sets: (dynamic)
     *
     * See: https://developer.wordpress.org/reference/hooks/pre_get_posts/
     *
     * @param \WP_Query $query The existing query to modify in place.
     * @return void
     */
    public function applyTo(\WP_Query $query): void
    {
        $this->record('applyTo');

        (new QueryModifierAdapter())->modify($query, $this->toArgs());
    }

    /**
     * Execute the query and return a plain PHP array of posts.
     *
     * Prefers Timber when available (returns Timber\Post objects via `PostQuery::to_array()`).
     * Falls back to `WP_Query::$posts` otherwise. The engine used is recorded in `explain()`.
     *
     * Sets: (none)
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/
     * See: https://timber.github.io/docs/v2/reference/timber-postquery/
     *
     * @return array<int, mixed> Posts array (Timber\Post objects when Timber is available, WP_Post objects otherwise).
     */
    public function toArray(): array
    {
        if (class_exists(\Timber\PostQuery::class)) {
            $result = $this->timber()->to_array();
            $this->record('toArray', 'timber');

            return $result;
        }

        $result = $this->get();
        $this->record('toArray', 'wp');

        return $result;
    }

    /**
     * Normalize map/callback bindings into an array map.
     *
     * @param array<string, callable>|callable(Binder): void $bindings
     * @return array<string, callable>
     */
    private function normaliseBindingsToMap(array|callable $bindings): array
    {
        if (is_array($bindings)) {
            return $bindings;
        }

        $binder = new Binder();
        $bindings($binder);

        return $binder->toMap();
    }

    /**
     * Apply one normalized binding map using the provided source.
     *
     * @param array<string, callable> $map
     * @param QueryVarSource $source
     * @return self
     */
    private function applyBindingMap(array $map, QueryVarSource $source): self
    {
        foreach ($map as $rawKey => $binding) {
            if (!is_callable($binding)) {
                throw new \InvalidArgumentException('Each binding map entry must be callable.');
            }

            $key = (string) $rawKey;
            $value = $source->get($key, null);
            $before = $this->toArgs();
            $result = $binding($this, $value, $key);

            if (!$result instanceof self) {
                throw new \UnexpectedValueException('Binding callables must return a Quartermaster instance.');
            }

            $this->args = $result->toArgs();
            $after = $this->toArgs();
            $applied = $after !== $before;
            $reason = $applied ? 'applied' : self::summarizeSkipReason($value);
            $this->recordBinding($key, $applied, $reason, self::summarizeBoundValue($value));
        }

        $this->record('bindQueryVars', array_keys($map));

        return $this;
    }

    /**
     * Build a redacted summary for one bound value.
     *
     * @param mixed $value
     * @return string
     */
    private static function summarizeBoundValue(mixed $value): string
    {
        if (is_array($value)) {
            return 'array(len=' . count($value) . ')';
        }

        if (is_string($value)) {
            return 'string(len=' . strlen($value) . ')';
        }

        if (is_int($value)) {
            return 'int(' . $value . ')';
        }

        if (is_float($value)) {
            return 'float(' . $value . ')';
        }

        if ($value === null) {
            return 'null';
        }

        if (is_bool($value)) {
            return 'bool(' . ($value ? 'true' : 'false') . ')';
        }

        return get_debug_type($value);
    }

    /**
     * Build a coarse skip reason from a raw bound value shape.
     *
     * @param mixed $value
     * @return string
     */
    private static function summarizeSkipReason(mixed $value): string
    {
        if ($value === null) {
            return 'empty:null';
        }

        if (is_string($value) && trim($value) === '') {
            return 'empty:string';
        }

        if (is_array($value) && $value === []) {
            return 'empty:array';
        }

        return 'skipped';
    }

    // TODO: Add macro system / Eloquent-style scope host.
    // TODO: Evaluate separate TermQuartermaster class (out of current scaffold scope).
}
