<?php

namespace SilverStripe\GraphQL;

use SilverStripe\Dev\SapphireTest;

class FieldCreatorTest extends SapphireTest
{
    public function testGetAttributesIncludesResolver()
    {
        $mock = $this->getMockBuilder('SilverStripe\GraphQL\FieldCreator')
            ->setMethods(['resolve'])
            ->getMock();
        $mock->method('resolve')->willReturn(function () {
        });

        $attrs = $mock->getAttributes();

        $this->assertArrayHasKey('resolve', $attrs);
    }
}
