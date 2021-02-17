<?php


namespace SilverStripe\GraphQL\Tests\Schema;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Field\Query;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Type\InterfaceType;
use SilverStripe\GraphQL\Schema\Type\Type;

class StorableSchemaTest extends SapphireTest
{
    public function testValidation()
    {
        $this->expectException(SchemaBuilderException::class);
        $schema = Schema::create('test');
        $schema->addType(Type::create('TestType'));
        $schema->addInterface(InterfaceType::create('TestType'));
        $schema->createStoreableSchema()->validate();

        $schema = Schema::create('test');
        $schema->addQuery(Query::create('myQuery', ['type' => 'MyType']));
        $schema->addType(Type::create('TestType'));
        $schema->addInterface(InterfaceType::create('TestInterface'));
        $schema->createStoreableSchema()->validate();
    }
}
