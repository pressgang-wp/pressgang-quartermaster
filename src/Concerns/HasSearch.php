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
}
