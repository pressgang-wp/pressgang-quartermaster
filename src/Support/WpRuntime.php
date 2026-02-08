<?php

namespace PressGang\Quartermaster\Support;

/**
 * Isolates optional WordPress global helpers behind testable methods.
 */
final class WpRuntime
{
    /**
     * @param string $key
     * @param int $default
     * @return int
     */
    public static function queryVarInt(string $key, int $default = 1): int
    {
        if (!function_exists('get_query_var')) {
            return $default;
        }

        $value = get_query_var($key, $default);

        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * @param string $value
     * @return string
     */
    public static function sanitizeText(string $value): string
    {
        if (function_exists('sanitize_text_field')) {
            return sanitize_text_field($value);
        }

        return trim($value);
    }
}
