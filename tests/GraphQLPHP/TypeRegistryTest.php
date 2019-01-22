<?php


namespace SilverStripe\GraphQL\Tests\GraphQLPHP;


use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Tests\Fake\TypeRegistryAltFake;
use SilverStripe\GraphQL\Tests\Fake\TypeRegistryFake;

class TypeRegistryTest extends SapphireTest
{
    public function testHasType()
    {
        $registry = new TypeRegistryFake();
        $this->assertTrue($registry->hasType('TypeA'));
        $this->assertTrue($registry->hasType('TypeB'));
        $this->assertFalse($registry->hasType('Fail'));
    }

    public function testGetType()
    {
        $registry = new TypeRegistryFake();
        $this->assertEquals('type-a', $registry->getType('TypeA'));
        $this->assertEquals('type-b', $registry->getType('TypeB'));
        $this->assertNull($registry->getType('Fail'));
    }

    public function testExtensions()
    {
        $registry = new TypeRegistryFake();
        $this->assertNull($registry->getType('NewType'));
        $registry->addExtension(new TypeRegistryAltFake());

        $this->assertEquals('new-type', $registry->getType('NewType'));
    }

}