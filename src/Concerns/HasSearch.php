<?php

namespace PressGang\Quartermaster\Concerns;

use PressGang\Quartermaster\Support\WpRuntime;

/**
 * Search-term mapping for `WP_Query`.
 */
trait HasSearch
{
    /**
     * Set the search term (`s`) for `WP_Query`.
     *
     * This is opt-in. The value is sanitized with `sanitize_text_field()` when WordPress
     * is loaded; otherwise it is trimmed. Empty results are ignored and do not set `s`.
     *
     * Sets: s
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
     * Set the search term and enable the Relevanssi integration flag.
     *
     * This is opt-in. Delegates to `search()` for sanitization, then sets
     * `relevanssi = true` so the Relevanssi plugin intercepts the query.
     * Empty/null values are ignored — neither `s` nor `relevanssi` are set.
     *
     * Sets: s, relevanssi
     *
     * See: https://www.relevanssi.com/knowledge-base/wp_query-arguments/
     *
     * @param string|null $search Raw search string; null/empty leaves args unchanged.
     * @return self
     */
    public function relevanssi(?string $search): self
    {
        $this->search($search);

        if ($this->getArg('s') !== null) {
            $this->set('relevanssi', true);
        }

        $this->record('relevanssi', $search);

        return $this;
    }
}
