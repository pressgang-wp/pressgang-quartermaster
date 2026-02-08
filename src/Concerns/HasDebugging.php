<?php


namespace PressGang\Quartermaster\Concerns;

use PressGang\Quartermaster\Support\Warnings;

/**
 * Debug/explainability helpers for fluent call chains.
 *
 * This trait records method calls and builds a structured `explain()` payload containing
 * final args, call history, and advisory warnings. It does not mutate query behavior.
 */
trait HasDebugging
{
    /**
     * @var array<int, array{name: string, params: array<int, mixed>}>
     */
    protected array $applied = [];

    /**
     * Record one fluent call in the explain log.
     *
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
     * Return inspectable builder state for debugging.
     *
     * `warnings` are advisory only and do not change runtime query args.
     *
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
