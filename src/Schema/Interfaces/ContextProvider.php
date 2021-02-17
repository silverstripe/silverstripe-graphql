<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;

/**
 * A class that can store a generic array of context
 */
interface ContextProvider
{
    /**
     * Should return key/value pairs that will merge into a separate query context
     * @return array
     */
    public function provideContext(): array;

    /**
     * Get the value out of the graphql context array
     * @param array $context
     * @return mixed
     */
    public static function get(array $context);
}
