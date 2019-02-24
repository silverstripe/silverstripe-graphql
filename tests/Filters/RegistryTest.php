<?php

namespace SilverStripe\GraphQL\Tests\Filters;

use http\Exception\InvalidArgumentException;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Filters\FilterInterface;
use SilverStripe\GraphQL\Filters\Registry;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;
use SilverStripe\GraphQL\Tests\Fake\FakeFilter;

class RegistryTest extends SapphireTest
{
    public function testAddFilter()
    {
        $registry = new Registry();
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
        $registry = new Registry();
        $registry->addFilter($fake);
    }
}