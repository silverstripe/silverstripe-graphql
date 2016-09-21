<?php

namespace Chillu\GraphQL;

use SilverStripe\Dev\SapphireTest;
use GraphQL\Type\Definition\Type;

class TypeCreatorTest extends SapphireTest
{
    public function testGetFields()
    {
        $mock = $this->getMockBuilder(TypeCreator::class)
            ->setMethods(['fields'])
            ->getMock();
        $mock->method('fields')->willReturn([
            'ID' => [
                'type' => Type::nonNull(Type::id()),
            ],
        ]);

        $fields = $mock->getFields();

        $this->assertArrayHasKey('ID', $fields);
    }

    public function testToArray()
    {
        $mock = $this->getMockBuilder(TypeCreator::class)
            ->setMethods(['fields'])
            ->getMock();
        $mock->method('fields')->willReturn([
            'ID' => [
                'type' => Type::nonNull(Type::id()),
            ],
        ]);

        $actual = $mock->toArray();
        $this->assertArrayHasKey('fields', $actual);

        $fields = $actual['fields']();
        $this->assertArrayHasKey('ID', $fields);
    }

    public function testGetAttributes()
    {
        $mock = $this->getMockBuilder(TypeCreator::class)
            ->setMethods(['fields'])
            ->getMock();
        $mock->method('fields')->willReturn([
            'ID' => [
                'type' => Type::nonNull(Type::id()),
            ],
        ]);

        $actual = $mock->getAttributes();
        $this->assertArrayHasKey('fields', $actual);

        $fields = $actual['fields']();
        $this->assertArrayHasKey('ID', $fields);
    }

    public function testGetFieldsUsesResolveConfig()
    {
        $mock = $this->getMockBuilder(TypeCreator::class)
            ->setMethods(['fields','resolveFieldAField'])
            ->getMock();
        $mock->method('fields')->willReturn([
            'fieldA' => [
                'type' => Type::string(),
                'resolve' => function() {return 'config';},
            ],
            'fieldB' => [
                'type' => Type::string(),
            ],
        ]);
        $mock->method('resolveFieldA')
            ->willReturn('method');

        $fields = $mock->getFields();
        $this->assertArrayHasKey('fieldA', $fields);
        $this->assertArrayHasKey('fieldB', $fields);
        $this->assertArrayHasKey('resolve', $fields['fieldA']);
        $this->assertArrayNotHasKey('resolve', $fields['fieldB']);
        $this->assertEquals('config', $fields['fieldA']['resolve']());
    }

    public function testGetFieldsUsesResolverMethod()
    {
        $mock = $this->getMockBuilder(TypeCreator::class)
            ->setMethods(['fields','resolveFieldAField'])
            ->getMock();
        $mock->method('fields')->willReturn([
            'fieldA' => [
                'type' => Type::string(),
            ],
            'fieldB' => [
                'type' => Type::string(),
            ],
        ]);
        $mock->method('resolveFieldA')
            ->willReturn('resolved');

        $fields = $mock->getFields();
        $this->assertArrayHasKey('fieldA', $fields);
        $this->assertArrayHasKey('fieldB', $fields);
        $this->assertArrayHasKey('resolve', $fields['fieldA']);
        $this->assertArrayNotHasKey('resolve', $fields['fieldB']);
    }

    public function testGetFieldsUsesAllFieldsResolverMethod()
    {
        $mock = $this->getMockBuilder(TypeCreator::class)
            ->setMethods(['fields','resolveField'])
            ->getMock();
        $mock->method('fields')->willReturn([
            'fieldA' => [
                'type' => Type::string(),
            ],
            'fieldB' => [
                'type' => Type::string(),
            ],
        ]);
        $mock->method('resolveField')
            ->willReturn('resolved');

        $fields = $mock->getFields();
        $this->assertArrayHasKey('fieldA', $fields);
        $this->assertArrayHasKey('fieldB', $fields);
        $this->assertArrayHasKey('resolve', $fields['fieldA']);
        $this->assertArrayHasKey('resolve', $fields['fieldB']);
        $this->assertEquals('resolved', $fields['fieldA']['resolve']());
        $this->assertEquals('resolved', $fields['fieldB']['resolve']());
    }
}
