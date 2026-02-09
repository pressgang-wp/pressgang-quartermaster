<?php

namespace PressGang\Quartermaster\Concerns;

/**
 * Conditional and hook helpers for fluent query builders.
 *
 * Allows closures to be applied conditionally or unconditionally within
 * a fluent chain without introducing branching, hidden state, or magic.
 */
trait HasConditionals
{
    /**
     * Conditionally apply a closure to the builder.
     *
     * If `$condition` is true, `$then` is invoked with `$this`.
     * If `$condition` is false and `$else` is provided, `$else` is invoked instead.
     * The closure return value is ignored; `$this` is always returned.
     *
     * Sets: (dynamic)
     *
     * @param bool $condition
     * @param callable(self): void $then
     * @param (callable(self): void)|null $else
     * @return self
     */
    public function when(bool $condition, callable $then, ?callable $else = null): self
    {
        if ($condition) {
            $then($this);
        } elseif ($else !== null) {
            $else($this);
        }

        $this->record('when', $condition);

        return $this;
    }

    /**
     * Inverse of `when()`: apply a closure when the condition is false.
     *
     * If `$condition` is false, `$then` is invoked with `$this`.
     * If `$condition` is true and `$else` is provided, `$else` is invoked instead.
     * The closure return value is ignored; `$this` is always returned.
     *
     * Sets: (dynamic)
     *
     * @param bool $condition
     * @param callable(self): void $then
     * @param (callable(self): void)|null $else
     * @return self
     */
    public function unless(bool $condition, callable $then, ?callable $else = null): self
    {
        if (!$condition) {
            $then($this);
        } elseif ($else !== null) {
            $else($this);
        }

        $this->record('unless', $condition);

        return $this;
    }

    /**
     * Always invoke a closure with the builder instance.
     *
     * Useful for builder-level conditional logic or side effects without
     * breaking the fluent chain. Complements `tapArgs()` (args-level manipulation).
     * The closure return value is ignored; `$this` is always returned.
     *
     * Sets: (dynamic)
     *
     * @param callable(self): void $callback
     * @return self
     */
    public function tap(callable $callback): self
    {
        $callback($this);

        $this->record('tap');

        return $this;
    }
}
