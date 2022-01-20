<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;

/**
 * Used by any class that declares an identifier
 */
interface Identifiable
{
    /**
     * @return string
     */
    public static function getIdentifier(): string;
}
