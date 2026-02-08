<?php

namespace PressGang\Quartermaster\Contracts;

/**
 * Abstraction for reading query vars from a runtime source.
 *
 * This keeps query-var binding explicit and testable without directly depending on
 * WordPress globals in builder internals.
 */
interface QueryVarSource
{
    /**
     * Read one query-var value by key.
     *
     * See: https://developer.wordpress.org/reference/functions/get_query_var/
     *
     * @param string $key Query-var key.
     * @param mixed $default Fallback when key is absent.
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed;
}

