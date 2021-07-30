<?php


namespace SilverStripe\GraphQL\Tests\Schema\DataObject\Plugin;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Schema\DataObject\DataObjectModel;
use SilverStripe\GraphQL\Schema\DataObject\FieldAccessor;
use SilverStripe\GraphQL\Schema\DataObject\Plugin\DBFieldTypes;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\SchemaConfig;
use SilverStripe\GraphQL\Schema\Type\ModelType;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;

class DBFieldTypesTest extends SapphireTest
{
    public function testApply()
    {
        $model = new DataObjectModel(DataObjectFake::class, new SchemaConfig());
        $model->setFieldAccessor(new FieldAccessor());

        $type = new ModelType($model);
        $type->addAllFields();

        $plugin = new DBFieldTypes();
        $plugin->apply($type, $schema = new Schema('test'));

        $enum = $type->getFieldByName('myEnum');
        $this->assertNotNull($enum);
        $this->assertEquals('DataObjectFakemyEnumEnum', $enum->getType());
        $this->assertNotNull($schema->getEnum('DataObjectFakemyEnumEnum'));

        $composite = $type->getFieldByName('myMoney');
        $this->assertNotNull($composite);
        $this->assertEquals('DBMoneyComposite', $composite->getType());
        $type = $schema->getType('DBMoneyComposite');
        $this->assertNotNull($type);
        $this->assertNotNull($type->getFieldByName('currency'));
        $this->assertNotNull($type->getFieldByName('amount'));
    }
}
