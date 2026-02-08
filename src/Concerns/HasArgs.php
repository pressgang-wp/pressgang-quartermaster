<?php


namespace PressGang\Quartermaster\Concerns;

/**
 * Core args storage and mutation helpers.
 */
trait HasArgs
{
    /**
     * @var array<string, mixed>
     */
    protected array $args = [];

    /**
     * @return array<string, mixed>
     */
    public function toArgs(): array
    {
        return $this->args;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    protected function set(string $key, mixed $value): self
    {
        $this->args[$key] = $value;

        return $this;
    }

    /**
     * @param array<string, mixed> $args
     * @return $this
     */
    protected function merge(array $args): self
    {
        $this->args = array_merge($this->args, $args);

        return $this;
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function get(string $key, mixed $default = null): mixed
    {
        return $this->args[$key] ?? $default;
    }
}
