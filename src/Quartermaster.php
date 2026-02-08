<?php


namespace PressGang\Quartermaster;

use PressGang\Quartermaster\Adapters\TimberAdapter;
use PressGang\Quartermaster\Adapters\WpAdapter;
use PressGang\Quartermaster\Concerns\HasArgs;
use PressGang\Quartermaster\Concerns\HasDebugging;
use PressGang\Quartermaster\Concerns\HasLegacyScopes;
use PressGang\Quartermaster\Concerns\HasMetaQuery;
use PressGang\Quartermaster\Concerns\HasTaxQuery;
use PressGang\Quartermaster\Contracts\ScopeHost;

/**
 * Fluent WP_Query args builder.
 */
final class Quartermaster implements ScopeHost
{
    use HasArgs;
    use HasDebugging;
    use HasLegacyScopes;
    use HasMetaQuery;
    use HasTaxQuery;

    /**
     * @param array<string, mixed> $args
     */
    public function __construct(array $args = [])
    {
        $this->args = $args;
    }

    /**
     * @param array<string, mixed> $args
     * @return self
     */
    public static function prepare(array $args = []): self
    {
        return new self($args);
    }

    /**
     * @param string $postType
     * @return $this
     */
    public function postType(string $postType): self
    {
        $this->set('post_type', $postType);
        $this->record('postType', $postType);

        return $this;
    }

    /**
     * @param string $status
     * @return $this
     */
    public function status(string $status): self
    {
        $this->set('post_status', $status);
        $this->record('status', $status);

        return $this;
    }

    /**
     * @param int $postsPerPage
     * @return $this
     */
    public function paged(int $postsPerPage = 10): self
    {
        $paged = 1;

        if (function_exists('get_query_var')) {
            $rawPaged = get_query_var('paged', 1);
            $paged = is_numeric($rawPaged) ? max(1, (int) $rawPaged) : 1;
        }

        $this->merge([
            'posts_per_page' => $postsPerPage,
            'paged' => $paged,
        ]);

        $this->record('paged', $postsPerPage, $paged);

        return $this;
    }

    /**
     * @param string $orderby
     * @param string $order
     * @return $this
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
     * @param string $metaKey
     * @param string $order
     * @param string $metaType
     * @return $this
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
     * @param string|null $search
     * @return $this
     */
    public function search(?string $search): self
    {
        if ($search === null) {
            $this->record('search', null);

            return $this;
        }

        $value = function_exists('sanitize_text_field')
            ? sanitize_text_field($search)
            : trim($search);

        if ($value === '') {
            $this->record('search', '');

            return $this;
        }

        $this->set('s', $value);
        $this->record('search', $value);

        return $this;
    }

    /**
     * @param callable(array<string, mixed>): array<string, mixed> $fn
     * @return $this
     */
    public function tapArgs(callable $fn): self
    {
        $next = $fn($this->toArgs());
        $this->args = $next;
        $this->record('tapArgs', $fn);

        return $this;
    }

    /**
     * @return \WP_Query
     */
    public function wpQuery(): \WP_Query
    {
        return (new WpAdapter())->wpQuery($this->toArgs());
    }

    /**
     * @return object
     */
    public function timber(): object
    {
        return (new TimberAdapter())->postQuery($this->toArgs());
    }

    // TODO: Add macro system / Eloquent-style scope host.
    // TODO: Evaluate separate TermQuartermaster class (out of current scaffold scope).
}
