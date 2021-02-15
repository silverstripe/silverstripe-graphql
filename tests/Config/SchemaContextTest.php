<?php

namespace SilverStripe\GraphQL\Tests\Config;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Schema\Field\Field;
use SilverStripe\GraphQL\Schema\Resolver\DefaultResolverStrategy;
use SilverStripe\GraphQL\Schema\SchemaConfig;
use SilverStripe\GraphQL\Schema\Type\Type;
use SilverStripe\GraphQL\Tests\Fake\SchemaContextTestResolverA;
use SilverStripe\GraphQL\Tests\Fake\SchemaContextTestResolverB;

class SchemaContextTest extends SapphireTest
{
    public function testResolverDiscovery()
    {
        $context = new SchemaConfig([
            'resolvers' => [
                SchemaContextTestResolverA::class,
                SchemaContextTestResolverB::class,
            ],
            'resolverStrategy' => [DefaultResolverStrategy::class, 'getResolverMethod']
        ]);

        $result = $context->discoverResolver(new Type('TypeName'), new Field('fieldName'));
        $this->assertEquals('resolveTypeNameFieldName', $result->getMethod());
        $this->assertEquals(SchemaContextTestResolverA::class, $result->getClass());

        $result = $context->discoverResolver(new Type('TypeName'), new Field('foo'));
        $this->assertEquals('resolveTypeName', $result->getMethod());
        $this->assertEquals(SchemaContextTestResolverA::class, $result->getClass());

        $result = $context->discoverResolver(new Type('Nothing'), new Field('foo'));
        $this->assertEquals('resolve', $result->getMethod());
        $this->assertEquals(SchemaContextTestResolverA::class, $result->getClass());

        $result = $context->discoverResolver(new Type('Nothing'), new Field('specialField'));
        $this->assertEquals('resolveSpecialField', $result->getMethod());
        $this->assertEquals(SchemaContextTestResolverB::class, $result->getClass());
    }
}
