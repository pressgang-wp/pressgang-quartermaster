<?php

namespace PressGang\Quartermaster\Concerns;

use PressGang\Quartermaster\Support\WpRuntime;

/**
 * Pagination and related query-shaping helpers for `WP_Query`.
 */
trait HasPagination
{
    /**
     * Set pagination args (`posts_per_page`, `paged`) for `WP_Query`.
     *
     * This is opt-in. If `$paged` is null, the method reads `get_query_var('paged', 1)` at call
     * time via `WpRuntime` and clamps the resolved value to at least `1`.
     *
     * Sets: posts_per_page, paged
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/#pagination-parameters
     * See: https://developer.wordpress.org/reference/functions/get_query_var/
     *
     * @param int|null $postsPerPage Value for `posts_per_page`; null defaults to the `posts_per_page` option.
     * @param int|null $paged Explicit page number; null defers to `get_query_var('paged', 1)`.
     * @return self
     */
    public function paged(?int $postsPerPage = null, ?int $paged = null): self
    {
        $postsPerPage ??= WpRuntime::optionInt('posts_per_page', 10);

        $resolvedPaged = $paged ?? WpRuntime::queryVarInt('paged', 1);
        $resolvedPaged = max(1, $resolvedPaged);

        $this->merge([
            'posts_per_page' => $postsPerPage,
            'paged' => $resolvedPaged,
        ]);

        $this->record('paged', $postsPerPage, $paged, $resolvedPaged);

        return $this;
    }

    /**
     * Configure `WP_Query` to fetch all matching posts.
     *
     * This maps directly to `posts_per_page = -1` with `nopaging = true`. If `paged`
     * was previously set, it is removed because pagination is disabled in this mode.
     *
     * Sets: posts_per_page, nopaging, paged
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/#pagination-parameters
     *
     * @return self
     */
    public function all(): self
    {
        $this->set('posts_per_page', -1);
        $this->set('nopaging', true);
        unset($this->args['paged']);
        $this->record('all');

        return $this;
    }

    /**
     * Set `no_found_rows = true` to skip SQL row counting.
     *
     * This is opt-in and can improve performance when total pagination counts are not needed.
     *
     * Sets: no_found_rows
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
}
