<?php

namespace PressGang\Quartermaster\Bindings;

use PressGang\Quartermaster\Contracts\QueryVarSource;

/**
 * WordPress-backed query-var source using `get_query_var()`.
 */
final class WordPressQueryVarSource implements QueryVarSource
{
    /**
     * Read a query var from WordPress runtime.
     *
     * See: https://developer.wordpress.org/reference/functions/get_query_var/
     *
     * @param string $key Query-var key.
     * @param mixed $default Fallback value.
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (!function_exists('get_query_var')) {
            return $default;
        }

        return get_query_var($key, $default);
    }
}

