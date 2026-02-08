<?php

namespace PressGang\Quartermaster\Support;

/**
 * Thin wrappers around optional WordPress global functions.
 *
 * Keeping these calls centralized makes core fluent methods easier to test while preserving
 * WordPress-native behavior when WordPress is loaded.
 */
final class WpRuntime
{
    /**
     * Read a numeric query var using `get_query_var()`.
     *
     * Falls back to `$default` when WordPress is unavailable or when the value is non-numeric.
     *
     * See: https://developer.wordpress.org/reference/functions/get_query_var/
     *
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
     * Sanitize text using WordPress `sanitize_text_field()` when available.
     *
     * Falls back to `trim()` when WordPress is unavailable.
     *
     * See: https://developer.wordpress.org/reference/functions/sanitize_text_field/
     *
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
