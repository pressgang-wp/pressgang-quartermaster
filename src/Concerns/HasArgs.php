<?php


namespace PressGang\Quartermaster\Concerns;

/**
 * Shared storage helpers for the raw `WP_Query` argument array.
 *
 * This trait is intentionally simple: it only stores and mutates the explicit argument payload
 * used by fluent methods. It does not introduce defaults or infer missing keys.
 */
trait HasArgs
{
    /**
     * @var array<string, mixed>
     */
    protected array $args = [];

    /**
     * Return the current raw `WP_Query` arguments.
     *
     * No normalization is applied here; callers receive exactly what has been set.
     *
     * Sets: (none)
     *
     * @return array<string, mixed>
     */
    public function toArgs(): array
    {
        return $this->args;
    }

    /**
     * Set one explicit `WP_Query` argument key.
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    protected function set(string $key, mixed $value): self
    {
        $this->args[$key] = $value;

        return $this;
    }

    /**
     * Merge explicit arguments into the current payload.
     *
     * Later values overwrite earlier values for matching keys.
     *
     * @param array<string, mixed> $args
     * @return self
     */
    protected function merge(array $args): self
    {
        $this->args = array_merge($this->args, $args);

        return $this;
    }

    /**
     * Read one argument key with fallback default.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getArg(string $key, mixed $default = null): mixed
    {
        return $this->args[$key] ?? $default;
    }
}
