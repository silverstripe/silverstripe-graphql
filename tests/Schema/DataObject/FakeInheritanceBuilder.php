<?php


namespace SilverStripe\GraphQL\Tests\Schema\DataObject;


use SilverStripe\Dev\TestOnly;
use SilverStripe\GraphQL\Schema\DataObject\InheritanceBuilder;
use SilverStripe\GraphQL\Schema\Type\ModelType;

class FakeInheritanceBuilder extends InheritanceBuilder implements TestOnly
{
    public static $ancestryCalls = [];
    public static $descendantCalls = [];

    public function fillAncestry(ModelType $modelType): void
    {
       static::$ancestryCalls[$modelType->getName()] = true;
    }

    public function fillDescendants(ModelType $modelType): void
    {
        static::$descendantCalls[$modelType->getName()] = true;
    }
}
