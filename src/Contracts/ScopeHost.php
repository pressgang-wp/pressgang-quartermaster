<?php


namespace PressGang\Quartermaster\Contracts;

/**
 * Contract for builders that can host legacy/closure scopes.
 */
interface ScopeHost
{
    /**
     * Apply a scope callback to the current args.
     *
     * @param callable(array<string, mixed>, mixed...): array<string, mixed> $scope
     * @param mixed ...$params
     * @return static
     */
    public function apply(callable $scope, mixed ...$params): static;
}
