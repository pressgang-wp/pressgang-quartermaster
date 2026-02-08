<?php


namespace PressGang\Quartermaster\Concerns;

/**
 * Bridge for legacy scope callbacks returning modified args arrays.
 */
trait HasLegacyScopes
{
    /**
     * @param callable(array<string, mixed>, mixed...): array<string, mixed> $scope
     * @param mixed ...$params
     * @return static
     */
    public function apply(callable $scope, mixed ...$params): static
    {
        $next = $scope($this->toArgs(), ...$params);

        $this->merge($next);
        $this->record('apply', $scope, ...$params);

        // TODO: Map existing scope_* methods into first-class fluent methods.
        return $this;
    }
}
