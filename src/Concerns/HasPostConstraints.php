<?php

namespace PressGang\Quartermaster\Concerns;

/**
 * Post-type and post-ID constraint helpers for `WP_Query`.
 */
trait HasPostConstraints
{
    /**
     * Set the `post_type` constraint for `WP_Query`.
     *
     * This is opt-in and only mutates the `post_type` key.
     *
     * Sets: post_type
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/#post-type-parameters
     *
     * @param string|array<int, string> $postType Post type slug or slugs.
     * @return self
     */
    public function postType(string|array $postType): self
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
     * Sets: post_status
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
     * Set a single post ID constraint (`p`) for `WP_Query`.
     *
     * This is opt-in and only mutates the `p` key.
     *
     * Sets: p
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
     * Sets: post__in
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
     * Sets: post__not_in
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
     * Sets: post_parent
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
     * Sets: post_parent__in
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
}
