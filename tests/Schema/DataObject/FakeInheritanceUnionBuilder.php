<?php


namespace SilverStripe\GraphQL\Tests\Schema\DataObject;


use SilverStripe\Dev\TestOnly;
use SilverStripe\GraphQL\Schema\DataObject\InheritanceUnionBuilder;

class FakeInheritanceUnionBuilder extends InheritanceUnionBuilder implements TestOnly
{
    public static $createCalled = false;
    public static $applyCalled = false;

    public function createUnions(): void
    {
        static::$createCalled = true;
    }

    public function applyUnions(): void
    {
        static::$applyCalled = true;
    }
}
