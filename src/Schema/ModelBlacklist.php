<?php


namespace SilverStripe\GraphQL\Schema;


interface ModelBlacklist
{
    public function getBlacklistedFields(): array;
}
