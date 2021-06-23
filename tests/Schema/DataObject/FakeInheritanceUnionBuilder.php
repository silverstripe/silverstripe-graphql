<?php


namespace SilverStripe\GraphQL\Tests\Schema\DataObject;

use SilverStripe\Dev\TestOnly;
use SilverStripe\GraphQL\Schema\DataObject\InheritanceUnionBuilder;
use SilverStripe\GraphQL\Schema\Type\ModelType;

class FakeInheritanceUnionBuilder extends InheritanceUnionBuilder implements TestOnly
{
    public static $createCalls = [];
    public static $applyCalls = [];

    public static function reset()
    {
        self::$createCalls = [];
        self::$applyCalls = [];
    }

    public function createUnions(ModelType $type): InheritanceUnionBuilder
    {
        static::$createCalls[$type->getName()] = true;
        return $this;
    }

    public function applyUnionsToQueries(ModelType $type): InheritanceUnionBuilder
    {
        static::$applyCalls[$type->getName()] = true;
        return $this;
    }
}
