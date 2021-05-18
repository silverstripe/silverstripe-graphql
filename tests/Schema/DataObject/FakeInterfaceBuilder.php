<?php


namespace SilverStripe\GraphQL\Tests\Schema\DataObject;

use SilverStripe\Dev\TestOnly;
use SilverStripe\GraphQL\Schema\DataObject\InterfaceBuilder;
use SilverStripe\GraphQL\Schema\Type\ModelType;

class FakeInterfaceBuilder extends InterfaceBuilder implements TestOnly
{
    public static $createCalls = [];
    public static $baseCalled = false;
    public static $applyCalled = false;

    public static function reset()
    {
        self::$createCalls = [];
        self::$baseCalled = false;
        self::$applyCalled = false;
    }

    public function createInterfaces(ModelType $modelType, array $interfaceStack = []): self
    {
        static::$createCalls[$modelType->getName()] = true;
        return $this;
    }

    public function applyBaseInterface(): self
    {
        static::$baseCalled = true;
        return $this;
    }

    public function applyInterfacesToQueries(): InterfaceBuilder
    {
        self::$applyCalled = true;
        return $this;
    }
}
