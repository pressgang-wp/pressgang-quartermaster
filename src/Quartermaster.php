<?php

namespace PressGang\Quartermaster;

use PressGang\Quartermaster\Adapters\TimberAdapter;
use PressGang\Quartermaster\Adapters\WpAdapter;
use PressGang\Quartermaster\Concerns\HasArgs;
use PressGang\Quartermaster\Concerns\HasAuthorConstraints;
use PressGang\Quartermaster\Concerns\HasDateQuery;
use PressGang\Quartermaster\Concerns\HasDebugging;
use PressGang\Quartermaster\Concerns\HasIntegerLists;
use PressGang\Quartermaster\Concerns\HasMetaQuery;
use PressGang\Quartermaster\Concerns\HasOrdering;
use PressGang\Quartermaster\Concerns\HasPagination;
use PressGang\Quartermaster\Concerns\HasPostConstraints;
use PressGang\Quartermaster\Concerns\HasQueryFlags;
use PressGang\Quartermaster\Concerns\HasSearch;
use PressGang\Quartermaster\Concerns\HasTaxQuery;

/**
 * Args-first fluent builder for WordPress `WP_Query` arguments.
 *
 * `prepare()` is zero side effects: no default `WP_Query` keys are added unless a fluent
 * method is explicitly called, or seed args are explicitly provided to `prepare($args)`.
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
    use HasDateQuery;
    use HasDebugging;
    use HasIntegerLists;
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
     * @param array<string, mixed> $args
     */
    public function __construct(array $args = [])
    {
        $this->args = $args;
    }

    /**
     * Start a fluent builder from optional seed args.
     *
     * This is opt-in only: with no input, the builder starts with an empty args array.
     *
     * @param array<string, mixed> $args
     * @return self New builder instance containing only the provided seed args.
     */
    public static function prepare(array $args = []): self
    {
        return new self($args);
    }

    /**
     * Apply an explicit argument transform callback.
     *
     * The callback receives the current args and must return a full args array replacement.
     * This method is an escape hatch for custom behavior while preserving fluent chaining.
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
     * Build and return a `WP_Query` instance from the current args.
     *
     * This method does not mutate args and does not add implicit defaults.
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
     * See: https://timber.github.io/docs/v2/reference/timber-postquery/
     *
     * @return object Timber `PostQuery` instance when Timber is installed.
     */
    public function timber(): object
    {
        return (new TimberAdapter())->postQuery($this->toArgs());
    }

    // TODO: Add macro system / Eloquent-style scope host.
    // TODO: Evaluate separate TermQuartermaster class (out of current scaffold scope).
}
