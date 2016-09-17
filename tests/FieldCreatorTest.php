<?php

namespace SilverStripe\GraphQL;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\FieldCreator;

class FieldCreatorTest extends SapphireTest
{

    public function testGetAttributesIncludesResolver()
    {
        $mock = $this->getMockBuilder('SilverStripe\GraphQL\FieldCreator')
            ->setMethods(['resolve'])
            ->getMock();
        $mock->method('resolve')->willReturn(function() {});

        $attrs = $mock->getAttributes();

        $this->assertArrayHasKey('resolve', $attrs);
    }

}
