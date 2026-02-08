<?php

namespace PressGang\Quartermaster\Bindings;

/**
 * Small fluent config object for numeric meta bindings.
 */
final class MetaBinding
{
    private Binder $binder;
    private string $queryVar;

    /**
     * @param Binder $binder Parent binder.
     * @param string $queryVar Query-var key to bind.
     */
    public function __construct(Binder $binder, string $queryVar)
    {
        $this->binder = $binder;
        $this->queryVar = $queryVar;
    }

    /**
     * Complete numeric meta binding config and return the parent binder.
     *
     * See: https://developer.wordpress.org/reference/classes/wp_query/#custom-field-post-meta-parameters
     *
     * @param string $metaKey Target meta key.
     * @param string $compare Comparison operator.
     * @return Binder
     */
    public function to(string $metaKey, string $compare = '>='): Binder
    {
        return $this->binder->register($this->queryVar, Bind::metaNum($metaKey, $compare, $this->queryVar));
    }
}

