<?php

namespace SilverStripe\GraphQL\Tests\Schema\Services;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Dev\BuildState;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\Query;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\SchemaBuilder;
use SilverStripe\GraphQL\Schema\Services\NestedInputBuilder;
use SilverStripe\GraphQL\Schema\Type\ModelType;
use SilverStripe\GraphQL\Tests\Fake\FakeProduct;
use SilverStripe\GraphQL\Tests\Fake\FakeProductPage;
use SilverStripe\GraphQL\Tests\Fake\FakeReview;
use SilverStripe\GraphQL\Tests\Schema\TestSchemaBuilder;
use SilverStripe\Security\Member;

class NestedInputBuilderTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        FakeProductPage::class,
        FakeProduct::class,
        FakeReview::class,
        Member::class,
    ];

    protected static $fixture_file = '../fixtures.yml';

    /**
     * @throws SchemaBuilderException
     */
    public function testNestedInputBuilder()
    {
        $schema = (new TestSchemaBuilder())->boot('inputBuilderTest');
        $schema
            ->addModelbyClassName(FakeProductPage::class, function (ModelType $model) {
                $model->addField('title');
                $model->addField('products');
                $model->addAllOperations();
            })
            ->addModelbyClassName(FakeProduct::class, function (ModelType $model) {
                $model->addField('title');
                $model->addField('reviews');
                $model->addField('relatedProducts');
            })
            ->addModelbyClassName(FakeReview::class, function (ModelType $model) {
                $model->addField('content');
                $model->addField('author');
            })
            ->addModelbyClassName(Member::class, function (ModelType $model) {
                $model->addField('firstName');
            });
        $root = $schema->getModelByClassName(FakeProductPage::class);
        $query = Query::create('myQuery', '[' . $root->getName() . ']');

        $builder = NestedInputBuilder::create($query, $schema);
        $builder->populateSchema();
        $this->assertSchema([
            'FakeProductPageInputType' => [
                'id' => 'ID',
                'title' => 'String',
                'products' => 'FakeProductInputType',
            ],
            'FakeProductInputType' => [
                'id' => 'ID',
                'title' => 'String',
                'reviews' => 'FakeReviewInputType',
                'relatedProducts' => 'FakeProductInputType',
            ],
            'FakeReviewInputType' => [
                'id' => 'ID',
                'content' => 'String',
                'author' => 'MemberInputType',
            ],
        ], $schema);
    }

    /**
     * @throws SchemaBuilderException
     */
    public function testNestedInputBuilderBuildsCyclicFilterFields()
    {
        $schema = (new TestSchemaBuilder())->boot('filterfieldbuilder');
        $schema
            ->addModelbyClassName(FakeProductPage::class, function (ModelType $model) {
                $model->addField('title');
                $model->addField('products', ['plugins' => ['filter' => true]]);
            })
            ->addModelbyClassName(FakeProduct::class, function (ModelType $model) {
                $model->addField('title');
                $model->addField('parent');
                $model->addField('reviews');
                $model->addField('relatedProducts');
            })
            ->addModelbyClassName(FakeReview::class, function (ModelType $model) {
                $model->addField('content');
                $model->addField('author');
            })
            ->addModelbyClassName(Member::class, function (ModelType $model) {
                $model->addField('firstName');
            });
        $schema->createStoreableSchema();
        $filterType = $schema->getType('FakeReviewFilterFields');
        $this->assertNotNull($filterType, "Type FakeReviewFilterFields not found in schema");
        $filterFieldObj = $filterType->getFieldByName('author');
        $this->assertNotNull($filterFieldObj, "Field author not found on {$filterType->getName()}");
        $this->assertEquals('MemberFilterFields', $filterFieldObj->filterFieldObj());
    }

    private function assertSchema(array $graph, Schema $schema)
    {
        foreach ($graph as $typeName => $fields) {
            $type = $schema->getType($typeName);
            $this->assertNotNull($type);
            foreach ($fields as $fieldName => $typeName) {
                $fieldObj = $type->getFieldByName($fieldName);
                $this->assertNotNull($fieldObj, "Field $fieldName not found on {$type->getName()}");
                $this->assertEquals($typeName, $fieldObj->getType());
            }
            foreach ($type->getFields() as $fieldObj) {
                $this->assertArrayHasKey($fieldObj->getName(), $fields);
                $this->assertEquals($fieldObj->getType(), $fields[$fieldObj->getName()]);
            }
        }
    }
}
