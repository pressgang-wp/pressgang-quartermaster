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
}
