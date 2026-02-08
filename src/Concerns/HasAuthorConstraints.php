<?php

namespace PressGang\Quartermaster\Concerns;

/**
 * Author constraint helpers for `WP_Query`.
 */
trait HasAuthorConstraints
{
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
}
