<?php


namespace SilverStripe\GraphQL\Tests\Schema;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Schema\DataObject\DataObjectModel;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\SchemaBuilder;
use SilverStripe\GraphQL\Schema\SchemaConfig;
use SilverStripe\GraphQL\Schema\Type\ModelType;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;
use SilverStripe\GraphQL\Tests\Fake\FakeSiteTree;

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
        $storeableSchema = $schema->getStoreableSchema();
        $this->assertEquals(
            $modelType1->getName(),
            $storeableSchema->getContext()->getTypeNameForClass(DataObjectFake::class)
        );

        // Rely on model creation for second model
        $this->assertEquals(
            $modelType2->getName(),
            $storeableSchema->getContext()->getTypeNameForClass(FakeSiteTree::class)
        );
    }
}
