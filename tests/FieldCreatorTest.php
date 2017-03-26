<?php

namespace SilverStripe\GraphQL\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\FieldCreator;

class FieldCreatorTest extends SapphireTest
{
    public function testGetAttributesIncludesResolver()
    {
        /** @var FieldCreator $mock */
        $mock = $this->getMockBuilder(FieldCreator::class)
            ->setMethods(['resolve'])
            ->getMock();
        $mock->method('resolve')->willReturn(function () {
        });

        $attrs = $mock->getAttributes();

        $this->assertArrayHasKey('resolve', $attrs);
    }
}
