<?php

namespace PressGang\Quartermaster\Concerns;

/**
 * Ordering helpers for `WP_Query`.
 */
trait HasOrdering
{
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
}
