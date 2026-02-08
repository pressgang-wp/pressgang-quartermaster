<?php

namespace PressGang\Quartermaster\Bindings;

use PressGang\Quartermaster\Contracts\QueryVarSource;

/**
 * In-memory query-var source for deterministic tests.
 */
final class ArrayQueryVarSource implements QueryVarSource
{
    /**
     * @var array<string, mixed>
     */
    private array $vars;

    /**
     * @param array<string, mixed> $vars
     */
    public function __construct(array $vars)
    {
        $this->vars = $vars;
    }

    /**
     * @param string $key Query-var key.
     * @param mixed $default Fallback value.
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->vars[$key] ?? $default;
    }
}

