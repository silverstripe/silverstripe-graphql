<?php

namespace SilverStripe\GraphQL;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\TypeCreator;
use GraphQL\Type\Definition\Type;

class TypeCreatorTest extends SapphireTest
{

    public function testGetFields()
    {
        $mock = $this->getMockBuilder('SilverStripe\GraphQL\TypeCreator')
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
        $mock = $this->getMockBuilder('SilverStripe\GraphQL\TypeCreator')
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
        $mock = $this->getMockBuilder('SilverStripe\GraphQL\TypeCreator')
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

}
