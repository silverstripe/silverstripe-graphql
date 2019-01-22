<?php


namespace SilverStripe\GraphQL\Tests\Fake;


use SilverStripe\Dev\TestOnly;
use SilverStripe\GraphQL\GraphQLPHP\BaseTypeRegistry;

class TypeRegistryFake extends BaseTypeRegistry implements TestOnly
{
    protected function TypeA()
    {
        return 'type-a';
    }

    protected function TypeB()
    {
        return 'type-b';
    }

    protected function Query()
    {
        return 'query';
    }

    protected function Mutation()
    {
        return 'mutation';
    }
}