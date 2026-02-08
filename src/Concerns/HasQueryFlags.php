<?php

namespace PressGang\Quartermaster\Concerns;

/**
 * Query-shaping and cache flag helpers for `WP_Query`.
 */
trait HasQueryFlags
{
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
}
