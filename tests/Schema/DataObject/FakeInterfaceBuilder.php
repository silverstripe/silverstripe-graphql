<?php


namespace SilverStripe\GraphQL\Tests\Schema\DataObject;


use SilverStripe\Dev\TestOnly;
use SilverStripe\GraphQL\Schema\DataObject\InterfaceBuilder;
use SilverStripe\GraphQL\Schema\Type\ModelType;

class FakeInterfaceBuilder extends InterfaceBuilder implements TestOnly
{
    public static $createCalls = [];
    public static $baseCalled = false;

    public function createInterfaces(ModelType $modelType, array $interfaceStack = []): void
    {
        static::$createCalls[$modelType->getName()] = true;
    }

    public function applyBaseInterface(): void
    {
        static::$baseCalled = true;
    }

}
