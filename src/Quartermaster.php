<?php


namespace PressGang\Quartermaster;

use PressGang\Quartermaster\Adapters\TimberAdapter;
use PressGang\Quartermaster\Adapters\WpAdapter;
use PressGang\Quartermaster\Concerns\HasArgs;
use PressGang\Quartermaster\Concerns\HasDateQuery;
use PressGang\Quartermaster\Concerns\HasDebugging;
use PressGang\Quartermaster\Concerns\HasMetaQuery;
use PressGang\Quartermaster\Concerns\HasTaxQuery;
use PressGang\Quartermaster\Support\WpRuntime;

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
    use HasDateQuery;
    use HasDebugging;
    use HasMetaQuery;
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
     * Set the `post_type` constraint for `WP_Query`.
     *
     * This is opt-in and only mutates the `post_type` key.
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/#post-type-parameters
     *
     * @param string $postType Post type slug, for example `post` or `event`.
     * @return self
     */
    public function postType(string $postType): self
    {
        $this->set('post_type', $postType);
        $this->record('postType', $postType);

        return $this;
    }

    /**
     * Set the `post_status` constraint for `WP_Query`.
     *
     * This is opt-in and only mutates the `post_status` key.
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/#status-parameters
     *
     * @param string $status Post status value, for example `publish`.
     * @return self
     */
    public function status(string $status): self
    {
        $this->set('post_status', $status);
        $this->record('status', $status);

        return $this;
    }

    /**
     * Set pagination args (`posts_per_page`, `paged`) for `WP_Query`.
     *
     * This is opt-in. If `$paged` is null, the method reads `get_query_var('paged', 1)` at call
     * time via `WpRuntime` and clamps the resolved value to at least `1`.
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/#pagination-parameters
     * See: https://developer.wordpress.org/reference/functions/get_query_var/
     *
     * @param int $postsPerPage Value for `posts_per_page`.
     * @param int|null $paged Explicit page number; null defers to `get_query_var('paged', 1)`.
     * @return self
     */
    public function paged(int $postsPerPage = 10, ?int $paged = null): self
    {
        $resolvedPaged = $paged;

        if ($resolvedPaged === null) {
            $resolvedPaged = WpRuntime::queryVarInt('paged', 1);
        }

        $resolvedPaged = max(1, $resolvedPaged);

        $this->merge([
            'posts_per_page' => $postsPerPage,
            'paged' => $resolvedPaged,
        ]);

        $this->record('paged', $postsPerPage, $paged, $resolvedPaged);

        return $this;
    }

    /**
     * Set ordering args (`orderby`, `order`) for `WP_Query`.
     *
     * This is opt-in and only mutates the `orderby` and `order` keys.
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/#order-orderby-parameters
     *
     * @param string $orderby Value for `orderby`.
     * @param string $order Sort direction; normalized to uppercase.
     * @return self
     */
    public function orderBy(string $orderby, string $order = 'DESC'): self
    {
        $this->merge([
            'orderby' => $orderby,
            'order' => strtoupper($order),
        ]);

        $this->record('orderBy', $orderby, $order);

        return $this;
    }

    /**
     * Configure meta-value ordering using `WP_Query` meta args.
     *
     * Sets `meta_key`, sets `orderby` to `meta_value` (v0 behavior), sets `order`,
     * and stores `meta_type` for explicitness/debugging.
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/#custom-field-post-meta-parameters
     * See: https://developer.wordpress.org/reference/classes/wp_query/#order-orderby-parameters
     *
     * @param string $metaKey Meta key stored as `meta_key`.
     * @param string $order Sort direction stored as `order` (uppercased).
     * @param string $metaType Meta type stored as `meta_type` (uppercased).
     * @return self
     */
    public function orderByMeta(string $metaKey, string $order = 'ASC', string $metaType = 'CHAR'): self
    {
        $this->merge([
            'meta_key' => $metaKey,
            'orderby' => 'meta_value',
            'order' => strtoupper($order),
            'meta_type' => strtoupper($metaType),
        ]);

        $this->record('orderByMeta', $metaKey, $order, $metaType);

        return $this;
    }

    /**
     * Configure numeric meta ordering using `WP_Query` meta args.
     *
     * Sets `meta_key`, sets `orderby` to `meta_value_num`, and sets `order`.
     * This is opt-in and does not change behavior unless called.
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/#custom-field-post-meta-parameters
     * See: https://developer.wordpress.org/reference/classes/wp_query/#order-orderby-parameters
     *
     * @param string $metaKey Meta key stored as `meta_key`.
     * @param string $order Sort direction stored as `order` (uppercased).
     * @return self
     */
    public function orderByMetaNumeric(string $metaKey, string $order = 'ASC'): self
    {
        $this->merge([
            'meta_key' => $metaKey,
            'orderby' => 'meta_value_num',
            'order' => strtoupper($order),
        ]);

        $this->record('orderByMetaNumeric', $metaKey, $order);

        return $this;
    }

    /**
     * Set the search term (`s`) for `WP_Query`.
     *
     * This is opt-in. The value is sanitized with `sanitize_text_field()` when WordPress
     * is loaded; otherwise it is trimmed. Empty results are ignored and do not set `s`.
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/#search-parameters
     * See: https://developer.wordpress.org/reference/functions/sanitize_text_field/
     *
     * @param string|null $search Raw search string; null/empty leaves args unchanged.
     * @return self
     */
    public function search(?string $search): self
    {
        if ($search === null) {
            $this->record('search', null);

            return $this;
        }

        $value = WpRuntime::sanitizeText($search);

        if ($value === '') {
            $this->record('search', '');

            return $this;
        }

        $this->set('s', $value);
        $this->record('search', $value);

        return $this;
    }

    /**
     * Set a single post ID constraint (`p`) for `WP_Query`.
     *
     * This is opt-in and only mutates the `p` key.
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/#post-page-parameters
     *
     * @param int $id Post ID.
     * @return self
     */
    public function whereId(int $id): self
    {
        $this->set('p', $id);
        $this->record('whereId', $id);

        return $this;
    }

    /**
     * Set post inclusion list (`post__in`) for `WP_Query`.
     *
     * This is opt-in. Non-integer values are filtered out; empty results do not mutate args.
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/#post-page-parameters
     *
     * @param array<int, mixed> $ids Candidate post IDs.
     * @return self
     */
    public function whereInIds(array $ids): self
    {
        $normalizedIds = $this->normalizeIntList($ids);

        if ($normalizedIds === []) {
            $this->record('whereInIds', $normalizedIds);

            return $this;
        }

        $this->set('post__in', $normalizedIds);
        $this->record('whereInIds', $normalizedIds);

        return $this;
    }

    /**
     * Set post exclusion list (`post__not_in`) for `WP_Query`.
     *
     * This is opt-in. Non-integer values are filtered out; empty results do not mutate args.
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/#post-page-parameters
     *
     * @param array<int, mixed> $ids Candidate post IDs.
     * @return self
     */
    public function excludeIds(array $ids): self
    {
        $normalizedIds = $this->normalizeIntList($ids);

        if ($normalizedIds === []) {
            $this->record('excludeIds', $normalizedIds);

            return $this;
        }

        $this->set('post__not_in', $normalizedIds);
        $this->record('excludeIds', $normalizedIds);

        return $this;
    }

    /**
     * Set parent ID constraint (`post_parent`) for `WP_Query`.
     *
     * This is opt-in and only mutates the `post_parent` key.
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/#post-page-parameters
     *
     * @param int $parentId Parent post ID.
     * @return self
     */
    public function whereParent(int $parentId): self
    {
        $this->set('post_parent', $parentId);
        $this->record('whereParent', $parentId);

        return $this;
    }

    /**
     * Set parent inclusion list (`post_parent__in`) for `WP_Query`.
     *
     * This is opt-in. Non-integer values are filtered out; empty results do not mutate args.
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/#post-page-parameters
     *
     * @param array<int, mixed> $parentIds Candidate parent IDs.
     * @return self
     */
    public function whereParentIn(array $parentIds): self
    {
        $normalizedIds = $this->normalizeIntList($parentIds);

        if ($normalizedIds === []) {
            $this->record('whereParentIn', $normalizedIds);

            return $this;
        }

        $this->set('post_parent__in', $normalizedIds);
        $this->record('whereParentIn', $normalizedIds);

        return $this;
    }

    /**
     * Set a single author constraint (`author`) for `WP_Query`.
     *
     * This is opt-in and only mutates the `author` key.
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/#author-parameters
     *
     * @param int $authorId Author user ID.
     * @return self
     */
    public function whereAuthor(int $authorId): self
    {
        $this->set('author', $authorId);
        $this->record('whereAuthor', $authorId);

        return $this;
    }

    /**
     * Set author inclusion list (`author__in`) for `WP_Query`.
     *
     * This is opt-in. Non-integer values are filtered out; empty results do not mutate args.
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/#author-parameters
     *
     * @param array<int, mixed> $authorIds Candidate author IDs.
     * @return self
     */
    public function whereAuthorIn(array $authorIds): self
    {
        $normalizedIds = $this->normalizeIntList($authorIds);

        if ($normalizedIds === []) {
            $this->record('whereAuthorIn', $normalizedIds);

            return $this;
        }

        $this->set('author__in', $normalizedIds);
        $this->record('whereAuthorIn', $normalizedIds);

        return $this;
    }

    /**
     * Set author exclusion list (`author__not_in`) for `WP_Query`.
     *
     * This is opt-in. Non-integer values are filtered out; empty results do not mutate args.
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/#author-parameters
     *
     * @param array<int, mixed> $authorIds Candidate author IDs.
     * @return self
     */
    public function whereAuthorNotIn(array $authorIds): self
    {
        $normalizedIds = $this->normalizeIntList($authorIds);

        if ($normalizedIds === []) {
            $this->record('whereAuthorNotIn', $normalizedIds);

            return $this;
        }

        $this->set('author__not_in', $normalizedIds);
        $this->record('whereAuthorNotIn', $normalizedIds);

        return $this;
    }

    /**
     * Set `fields = 'ids'` for lower-memory ID-only result sets.
     *
     * This is opt-in and only mutates the `fields` key.
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/#return-fields-parameter
     *
     * @return self
     */
    public function idsOnly(): self
    {
        $this->set('fields', 'ids');
        $this->record('idsOnly');

        return $this;
    }

    /**
     * Set `no_found_rows = true` to skip SQL row counting.
     *
     * This is opt-in and can improve performance when total pagination counts are not needed.
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/#pagination-parameters
     *
     * @return self
     */
    public function noFoundRows(): self
    {
        $this->set('no_found_rows', true);
        $this->record('noFoundRows');

        return $this;
    }

    /**
     * Toggle `update_post_meta_cache` for result posts.
     *
     * This is opt-in and only mutates the `update_post_meta_cache` key.
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/#caching-parameters
     *
     * @param bool $enabled True to enable meta cache priming, false to disable.
     * @return self
     */
    public function withMetaCache(bool $enabled = true): self
    {
        $this->set('update_post_meta_cache', $enabled);
        $this->record('withMetaCache', $enabled);

        return $this;
    }

    /**
     * Toggle `update_post_term_cache` for result posts.
     *
     * This is opt-in and only mutates the `update_post_term_cache` key.
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/#caching-parameters
     *
     * @param bool $enabled True to enable term cache priming, false to disable.
     * @return self
     */
    public function withTermCache(bool $enabled = true): self
    {
        $this->set('update_post_term_cache', $enabled);
        $this->record('withTermCache', $enabled);

        return $this;
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

    /**
     * Normalize a mixed list into integer IDs.
     *
     * Accepts integers and integer-like scalar values; invalid values are removed.
     *
     * @param array<int, mixed> $values
     * @return array<int, int>
     */
    protected function normalizeIntList(array $values): array
    {
        $normalized = [];

        foreach ($values as $value) {
            if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                continue;
            }

            $normalized[] = (int) $value;
        }

        return array_values($normalized);
    }

    // TODO: Add macro system / Eloquent-style scope host.
    // TODO: Evaluate separate TermQuartermaster class (out of current scaffold scope).
}
