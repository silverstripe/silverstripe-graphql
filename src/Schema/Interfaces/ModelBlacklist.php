<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;


interface ModelBlacklist
{
    public function getBlacklistedFields(): array;
}
