<?php


namespace SilverStripe\GraphQL\Tests\Schema\DataObject;

use SilverStripe\Dev\TestOnly;
use SilverStripe\GraphQL\Schema\DataObject\InheritanceUnionBuilder;

class FakeInheritanceUnionBuilder extends InheritanceUnionBuilder implements TestOnly
{
    public static $createCalled = false;
    public static $applyCalled = false;

    public static function reset()
    {
        self::$createCalled = false;
        self::$applyCalled = false;
    }

    public function createUnions(): self
    {
        static::$createCalled = true;
        return $this;
    }

    public function applyUnionsToQueries(): self
    {
        static::$applyCalled = true;
        return $this;
    }
}
