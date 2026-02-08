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
     * @var array<int, array{key: string, applied: bool, reason: string, value: string}>
     */
    protected array $bindings = [];

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
     * Record one query-var binding attempt for explainability.
     *
     * @param string $key Query-var key that was checked.
     * @param bool $applied Whether the binding changed args.
     * @param string $reason Short reason, such as `applied` or `skipped`.
     * @param string $valueSummary Redacted value shape summary, never raw user input.
     * @return void
     */
    protected function recordBinding(string $key, bool $applied, string $reason, string $valueSummary): void
    {
        $this->bindings[] = [
            'key' => $key,
            'applied' => $applied,
            'reason' => $reason,
            'value' => $valueSummary,
        ];
    }

    /**
     * Return inspectable builder state for debugging.
     *
     * `warnings` are advisory only and do not change runtime query args.
     *
     * @return array{
     *     args: array<string, mixed>,
     *     applied: array<int, array{name: string, params: array<int, mixed>}>,
     *     warnings: array<int, string>,
     *     bindings?: array<int, array{key: string, applied: bool, reason: string, value: string}>
     * }
     */
    public function explain(): array
    {
        $args = $this->toArgs();
        $explain = [
            'args' => $args,
            'applied' => $this->applied,
            'warnings' => Warnings::fromArgs($args),
        ];

        if ($this->bindings !== []) {
            $explain['bindings'] = $this->bindings;
        }

        return $explain;
    }
}
