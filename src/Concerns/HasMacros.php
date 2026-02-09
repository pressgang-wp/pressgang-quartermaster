<?php

namespace PressGang\Quartermaster\Concerns;

use BadMethodCallException;

/**
 * Opt-in macro system for project-level fluent sugar.
 *
 * Macros allow consumers to register named closures that are resolved at
 * runtime via `__call()`. Closures are bound to the builder instance so
 * `$this` refers to the builder inside the macro body.
 *
 * Macros should call existing fluent methods rather than mutating args directly.
 */
trait HasMacros
{
    /**
     * @var array<string, callable>
     */
    protected static array $macros = [];

    /**
     * Register a named macro.
     *
     * @param string $name
     * @param callable $macro Typically a Closure that uses `$this`.
     * @return void
     */
    public static function macro(string $name, callable $macro): void
    {
        static::$macros[$name] = $macro;
    }

    /**
     * Check whether a macro is registered.
     *
     * @param string $name
     * @return bool
     */
    public static function hasMacro(string $name): bool
    {
        return isset(static::$macros[$name]);
    }

    /**
     * Remove all registered macros (test helper).
     *
     * @return void
     */
    public static function flushMacros(): void
    {
        static::$macros = [];
    }

    /**
     * Dispatch instance calls to registered macros.
     *
     * Closures are bound to `$this` so the macro body can call fluent methods
     * directly. Non-Closure callables receive `$this` as the first argument.
     *
     * @param string $method
     * @param array<int, mixed> $args
     * @return mixed
     *
     * @throws BadMethodCallException If no macro is registered for the method.
     */
    public function __call(string $method, array $args): mixed
    {
        if (!static::hasMacro($method)) {
            throw new BadMethodCallException(
                sprintf('%s method [%s] does not exist.', static::class, $method)
            );
        }

        $macro = static::$macros[$method];

        $this->record('macro:' . $method, ...$args);

        if ($macro instanceof \Closure) {
            return $macro->call($this, ...$args);
        }

        return $macro($this, ...$args);
    }
}
