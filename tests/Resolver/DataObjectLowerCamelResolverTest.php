<?php

namespace Chillu\GraphQL\Tests\Resolver;

use Chillu\GraphQL\Resolver\DataObjectLowerCamelResolver;
use Chillu\GraphQL\Tests\DataObjectFake;
use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\Dev\SapphireTest;

class DataObjectLowerCamelResolverTest extends SapphireTest
{

    public function testResolvesOriginalCasing()
    {
        $fake = new DataObjectFake([
            'ID' => 99
        ]);
        $resolver = new DataObjectLowerCamelResolver();
        $info = new ResolveInfo([
            'fieldName' => 'ID'
        ]);
        $this->assertEquals(99, $resolver->resolve($fake, [], [], $info));
    }

    public function testResolvesDifferentCasing()
    {
        $fake = new DataObjectFake([
            'ID' => 99
        ]);
        $resolver = new DataObjectLowerCamelResolver();
        $info = new ResolveInfo([
            'fieldName' => 'id' // lowercase
        ]);
        $this->assertEquals(99, $resolver->resolve($fake, [], [], $info));
    }

    public function testResolvesCustomGetter()
    {
        $fake = new DataObjectFake([]);
        $resolver = new DataObjectLowerCamelResolver();
        $info = new ResolveInfo([
            'fieldName' => 'customGetter'
        ]);
        $this->assertEquals('customGetterValue', $resolver->resolve($fake, [], [], $info));
    }

    public function testResolvesMethod()
    {
        $fake = new DataObjectFake([]);
        $resolver = new DataObjectLowerCamelResolver();
        $info = new ResolveInfo([
            'fieldName' => 'customMethod'
        ]);
        $this->assertEquals('customMethodValue', $resolver->resolve($fake, [], [], $info));
    }
}
