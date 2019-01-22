<?php


namespace SilverStripe\GraphQL\Tests\Fake;

use SilverStripe\GraphQL\Schema\Encoding\Helpers;
use SilverStripe\GraphQL\Schema\Encoding\Interfaces\NamedTypeFetcherInterface;

class RegistryFetcherFake implements NamedTypeFetcherInterface
{
    public function getExpression($type)
    {
        return Helpers::normaliseValue($type);
    }
}