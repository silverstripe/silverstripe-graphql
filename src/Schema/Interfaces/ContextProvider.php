<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;

/**
 * A class that can store a generic array of context
 */
interface ContextProvider
{
    /**
     * @param string $key
     * @param $val
     * @return ContextProvider
     */
    public function addContext(string $key, $val): self;

    /**
     * @return array
     */
    public function getContext(): array;
}
