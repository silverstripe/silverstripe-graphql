<?php

namespace SilverStripe\GraphQL;

use SilverStripe\Dev\SapphireTest;

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
