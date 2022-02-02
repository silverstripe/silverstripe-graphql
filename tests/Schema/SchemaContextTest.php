<?php


namespace SilverStripe\GraphQL\Tests\Schema;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Schema\DataObject\DataObjectModel;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\Field;
use SilverStripe\GraphQL\Schema\Resolver\DefaultResolverStrategy;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\SchemaBuilder;
use SilverStripe\GraphQL\Schema\SchemaConfig;
use SilverStripe\GraphQL\Schema\Type\ModelType;
use SilverStripe\GraphQL\Schema\Type\Type;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;
use SilverStripe\GraphQL\Tests\Fake\FakeSiteTree;
use SilverStripe\GraphQL\Tests\Fake\SchemaContextTestResolverA;
use SilverStripe\GraphQL\Tests\Fake\SchemaContextTestResolverB;

class SchemaContextTest extends SapphireTest
{
    /**
     * @throws SchemaBuilderException
     */
    public function testGetTypeNameForClass()
    {
        $schema = SchemaBuilder::singleton()->boot('default');
        $modelType1 = new ModelType(
            DataObjectModel::create(DataObjectFake::class, new SchemaConfig())
        );
        $modelType2 = new ModelType(
            DataObjectModel::create(FakeSiteTree::class, new SchemaConfig())
        );
        // Only add one model
        $schema->addModel($modelType1);
        $storeableSchema = $schema->createStoreableSchema();
        $this->assertEquals(
            $modelType1->getName(),
            $storeableSchema->getConfig()->getTypeNameForClass(DataObjectFake::class)
        );

        // Rely on model creation for second model
        $this->assertEquals(
            $modelType2->getName(),
            $storeableSchema->getConfig()->getTypeNameForClass(FakeSiteTree::class)
        );
    }

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
