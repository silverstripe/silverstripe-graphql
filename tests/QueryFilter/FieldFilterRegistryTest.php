<?php

namespace SilverStripe\GraphQL\Tests\QueryFilter;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\QueryFilter\FieldFilterRegistry;
use SilverStripe\GraphQL\Tests\Fake\FakeFilter;

class FieldFilterRegistryTest extends SapphireTest
{
    public function testAddFilter()
    {
        $registry = new FieldFilterRegistry();
        $filter = new FakeFilter();
        $registry->addFilter($filter);
        $this->assertEquals($filter, $registry->getFilterByIdentifier('fake'));
        $this->assertNull($registry->getFilterByIdentifier('fail'));
    }


    public function testAddFilterIdentifierException()
    {
        $fake = $this->getMockBuilder(FakeFilter::class)
            ->setMethods(['getIdentifier'])
            ->getMock();
        $fake
            ->expects($this->once())
            ->method('getIdentifier')
            ->willReturn('not! a valid ide^ntifer');
        $this->expectException('InvalidArgumentException');
        $registry = new FieldFilterRegistry();
        $registry->addFilter($fake);
    }
}
