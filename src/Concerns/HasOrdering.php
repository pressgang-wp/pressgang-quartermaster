<?php

namespace PressGang\Quartermaster\Concerns;

/**
 * Ordering helpers for `WP_Query`.
 */
trait HasOrdering
{
    /**
     * Normalise an order direction to ASC/DESC, falling back to a default.
     *
     * Invalid values are downgraded to `$default` and surfaced as an advisory warning in
     * `explain()` output.
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/#order-orderby-parameters
     *
     * @param string $order Raw direction string.
     * @param string $default Default direction (`ASC` or `DESC`).
     * @param string $method Method name used in warning text.
     * @return string `ASC` or `DESC`.
     */
    protected function normaliseOrder(string $order, string $default, string $method): string
    {
        $normalizedDefault = strtoupper($default);
        $normalizedOrder = strtoupper(trim($order));

        if ($normalizedOrder === 'ASC' || $normalizedOrder === 'DESC') {
            return $normalizedOrder;
        }

        $this->warn(sprintf(
            "Invalid order direction '%s' in %s(); defaulted to '%s'.",
            $order,
            $method,
            $normalizedDefault
        ));

        return $normalizedDefault;
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
        $normalizedOrder = $this->normaliseOrder($order, 'DESC', 'orderBy');

        $this->merge([
            'orderby' => $orderby,
            'order' => $normalizedOrder,
        ]);

        $this->record('orderBy', $orderby, $order, $normalizedOrder);

        return $this;
    }

    /**
     * Set ordering args (`orderby`, `order`) with `ASC` direction.
     *
     * This delegates to `orderBy($orderby, 'ASC')`.
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/#order-orderby-parameters
     *
     * @param string $orderby Value for `orderby`.
     * @return self
     */
    public function orderByAsc(string $orderby): self
    {
        return $this->orderBy($orderby, 'ASC');
    }

    /**
     * Set ordering args (`orderby`, `order`) with `DESC` direction.
     *
     * This delegates to `orderBy($orderby, 'DESC')`.
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/#order-orderby-parameters
     *
     * @param string $orderby Value for `orderby`.
     * @return self
     */
    public function orderByDesc(string $orderby): self
    {
        return $this->orderBy($orderby, 'DESC');
    }

    /**
     * Configure meta-value ordering using `WP_Query` meta args.
     *
     * Sets `meta_key`, sets `orderby` to `meta_value` (v0 behavior), sets `order`,
     * and stores `meta_type` for explicitness/debugging. Meta ordering in WordPress
     * requires a `meta_key`, which this method sets explicitly.
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
        $normalizedOrder = $this->normaliseOrder($order, 'ASC', 'orderByMeta');

        $this->merge([
            'meta_key' => $metaKey,
            'orderby' => 'meta_value',
            'order' => $normalizedOrder,
            'meta_type' => strtoupper($metaType),
        ]);

        $this->record('orderByMeta', $metaKey, $order, $normalizedOrder, $metaType);

        return $this;
    }

    /**
     * Configure meta-value ordering with `ASC` direction.
     *
     * This delegates to `orderByMeta($metaKey, 'ASC', $metaType)`.
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/#custom-field-post-meta-parameters
     * See: https://developer.wordpress.org/reference/classes/wp_query/#order-orderby-parameters
     *
     * @param string $metaKey Meta key stored as `meta_key`.
     * @param string $metaType Meta type stored as `meta_type` (uppercased).
     * @return self
     */
    public function orderByMetaAsc(string $metaKey, string $metaType = 'CHAR'): self
    {
        return $this->orderByMeta($metaKey, 'ASC', $metaType);
    }

    /**
     * Configure meta-value ordering with `DESC` direction.
     *
     * This delegates to `orderByMeta($metaKey, 'DESC', $metaType)`.
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/#custom-field-post-meta-parameters
     * See: https://developer.wordpress.org/reference/classes/wp_query/#order-orderby-parameters
     *
     * @param string $metaKey Meta key stored as `meta_key`.
     * @param string $metaType Meta type stored as `meta_type` (uppercased).
     * @return self
     */
    public function orderByMetaDesc(string $metaKey, string $metaType = 'CHAR'): self
    {
        return $this->orderByMeta($metaKey, 'DESC', $metaType);
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
        $normalizedOrder = $this->normaliseOrder($order, 'ASC', 'orderByMetaNumeric');

        $this->merge([
            'meta_key' => $metaKey,
            'orderby' => 'meta_value_num',
            'order' => $normalizedOrder,
        ]);

        $this->record('orderByMetaNumeric', $metaKey, $order, $normalizedOrder);

        return $this;
    }

    /**
     * Configure numeric meta ordering with `ASC` direction.
     *
     * This delegates to `orderByMetaNumeric($metaKey, 'ASC')`.
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/#custom-field-post-meta-parameters
     * See: https://developer.wordpress.org/reference/classes/wp_query/#order-orderby-parameters
     *
     * @param string $metaKey Meta key stored as `meta_key`.
     * @return self
     */
    public function orderByMetaNumericAsc(string $metaKey): self
    {
        return $this->orderByMetaNumeric($metaKey, 'ASC');
    }

    /**
     * Configure numeric meta ordering with `DESC` direction.
     *
     * This delegates to `orderByMetaNumeric($metaKey, 'DESC')`.
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/#custom-field-post-meta-parameters
     * See: https://developer.wordpress.org/reference/classes/wp_query/#order-orderby-parameters
     *
     * @param string $metaKey Meta key stored as `meta_key`.
     * @return self
     */
    public function orderByMetaNumericDesc(string $metaKey): self
    {
        return $this->orderByMetaNumeric($metaKey, 'DESC');
    }
}
