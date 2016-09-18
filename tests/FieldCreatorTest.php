<?php

namespace Chillu\GraphQL;

use SilverStripe\Dev\SapphireTest;

class FieldCreatorTest extends SapphireTest
{
    public function testGetAttributesIncludesResolver()
    {
        $mock = $this->getMockBuilder('Chillu\GraphQL\FieldCreator')
            ->setMethods(['resolve'])
            ->getMock();
        $mock->method('resolve')->willReturn(function () {
        });

        $attrs = $mock->getAttributes();

        $this->assertArrayHasKey('resolve', $attrs);
    }
}
