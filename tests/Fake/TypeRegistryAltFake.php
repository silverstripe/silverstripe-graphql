<?php


namespace SilverStripe\GraphQL\Tests\Fake;


use SilverStripe\Dev\TestOnly;
use SilverStripe\GraphQL\GraphQLPHP\BaseTypeRegistry;

class TypeRegistryAltFake extends BaseTypeRegistry implements TestOnly
{
    protected function NewType()
    {
        return 'new-type';
    }
}