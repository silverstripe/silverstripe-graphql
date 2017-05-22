<?php

namespace SilverStripe\GraphQL\Tests;

use GraphQL\Type\Definition\InputObjectType;
use PHPUnit_Framework_MockObject_MockObject;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\TypeCreator;
use GraphQL\Type\Definition\Type;

class TypeCreatorTest extends SapphireTest
{
    public function testGetFields()
    {
        $mock = $this->getTypeCreatorMock();
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
        $mock = $this->getTypeCreatorMock();
        $actual = $mock->toArray();
        $this->assertArrayHasKey('fields', $actual);
        $fields = $actual['fields']();
        $this->assertArrayHasKey('ID', $fields);
    }

    public function testToType()
    {
        $mock = $this->getTypeCreatorMock();
        $actual = $mock->toType();
        $this->assertInstanceOf(Type::class, $actual);
    }

    public function testToTypeWithInputObject()
    {
        $mock = $this->getTypeCreatorMock(['isInputObject']);
        $mock->method('isInputObject')->willReturn(true);
        $actual = $mock->toType();
        $this->assertInstanceOf(InputObjectType::class, $actual);
    }

    public function testGetAttributes()
    {
        $mock = $this->getTypeCreatorMock();
        $actual = $mock->getAttributes();
        $this->assertArrayHasKey('fields', $actual);
        $fields = $actual['fields']();
        $this->assertArrayHasKey('ID', $fields);
    }

    public function testGetFieldsUsesResolveConfig()
    {
        $mock = $this->getTypeCreatorMock(['resolveFieldAField', 'fields']);
        $mock->method('fields')->willReturn([
            'fieldA' => [
                'type' => Type::string(),
                'resolve' => function () {
                    return 'config';
                },
            ],
            'fieldB' => [
                'type' => Type::string(),
            ],
        ]);
        $mock->method('resolveFieldAField')
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
        $mock = $this->getTypeCreatorMock(['resolveFieldAField', 'fields']);
        $mock->method('fields')->willReturn([
            'fieldA' => [
                'type' => Type::string(),
            ],
            'fieldB' => [
                'type' => Type::string(),
            ],
        ]);
        $mock->method('resolveFieldAField')
            ->willReturn('resolved');

        $fields = $mock->getFields();
        $this->assertArrayHasKey('fieldA', $fields);
        $this->assertArrayHasKey('fieldB', $fields);
        $this->assertArrayHasKey('resolve', $fields['fieldA']);
        $this->assertArrayNotHasKey('resolve', $fields['fieldB']);
    }

    public function testGetFieldsUsesAllFieldsResolverMethod()
    {
        $mock = $this->getTypeCreatorMock(['resolveField', 'fields']);
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

    /**
     * @param array $extraMethods
     * @return PHPUnit_Framework_MockObject_MockObject|TypeCreator
     */
    protected function getTypeCreatorMock($extraMethods = [])
    {
        $mock = $this->getMockBuilder(TypeCreator::class)
            ->setMethods(array_unique(array_merge(['fields', 'attributes'], $extraMethods)))
            ->getMock();

        if (!in_array('fields', $extraMethods)) {
            $mock->method('fields')->willReturn([
                'ID' => [
                    'type' => Type::nonNull(Type::id()),
                ],
            ]);
        }

        if (!in_array('attributes', $extraMethods)) {
            $mock->method('attributes')
                ->willReturn(['name' => 'myType']);
        }

        return $mock;
    }
}
