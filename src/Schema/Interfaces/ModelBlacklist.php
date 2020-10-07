<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;

/**
 * Implementors of this interface can prevent a given set of fields from being added
 */
interface ModelBlacklist
{
    public function getBlacklistedFields(): array;
}
