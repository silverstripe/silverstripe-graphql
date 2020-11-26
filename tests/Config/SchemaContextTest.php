<?php

namespace SilverStripe\GraphQL\Tests\Config;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Schema\Field\Field;
use SilverStripe\GraphQL\Schema\Resolver\DefaultResolverStrategy;
use SilverStripe\GraphQL\Schema\SchemaContext;
use SilverStripe\GraphQL\Tests\Fake\IntegrationTestResolverA;
use SilverStripe\GraphQL\Tests\Fake\IntegrationTestResolverB;

class SchemaContextTest extends SapphireTest
{
    public function testResolverDiscovery()
    {
        $context = new SchemaContext([
            'resolvers' => [
                IntegrationTestResolverA::class,
                IntegrationTestResolverB::class,
            ],
            'resolverStrategy' => [DefaultResolverStrategy::class, 'getResolverMethod']
        ]);

        $result = $context->discoverResolver('TypeName', new Field('fieldName'));
        $this->assertEquals('resolveTypeNameFieldName', $result->getMethod());
        $this->assertEquals(IntegrationTestResolverA::class, $result->getClass());

        $result = $context->discoverResolver('TypeName', new Field('foo'));
        $this->assertEquals('resolveTypeName', $result->getMethod());
        $this->assertEquals(IntegrationTestResolverA::class, $result->getClass());

        $result = $context->discoverResolver('Nothing', new Field('foo'));
        $this->assertEquals('resolve', $result->getMethod());
        $this->assertEquals(IntegrationTestResolverA::class, $result->getClass());

        $result = $context->discoverResolver('Nothing', new Field('specialField'));
        $this->assertEquals('resolveSpecialField', $result->getMethod());
        $this->assertEquals(IntegrationTestResolverB::class, $result->getClass());

    }
}
