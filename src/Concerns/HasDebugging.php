<?php


namespace PressGang\Quartermaster\Concerns;

use PressGang\Quartermaster\Support\Warnings;

/**
 * Explainability and method application tracking.
 */
trait HasDebugging
{
    /**
     * @var array<int, array{name: string, params: array<int, mixed>}>
     */
    protected array $applied = [];

    /**
     * @param string $name
     * @param mixed ...$params
     * @return void
     */
    protected function record(string $name, mixed ...$params): void
    {
        $this->applied[] = [
            'name' => $name,
            'params' => $params,
        ];
    }

    /**
     * @return array{args: array<string, mixed>, applied: array<int, array{name: string, params: array<int, mixed>}>, warnings: array<int, string>}
     */
    public function explain(): array
    {
        $args = $this->toArgs();

        return [
            'args' => $args,
            'applied' => $this->applied,
            'warnings' => Warnings::fromArgs($args),
        ];
    }
}
