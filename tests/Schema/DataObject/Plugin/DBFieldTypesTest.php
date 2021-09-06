<?php


namespace SilverStripe\GraphQL\Tests\Schema\DataObject\Plugin;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Schema\DataObject\DataObjectModel;
use SilverStripe\GraphQL\Schema\DataObject\FieldAccessor;
use SilverStripe\GraphQL\Schema\DataObject\Plugin\DBFieldTypes;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\SchemaConfig;
use SilverStripe\GraphQL\Schema\Type\Enum;
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
        $this->assertEquals('MyEnumEnum', $enum->getType());
        $this->assertNotNull($schema->getEnum('MyEnumEnum'));

        $composite = $type->getFieldByName('myMoney');
        $this->assertNotNull($composite);
        $this->assertEquals('DBMoneyComposite', $composite->getType());
        $type = $schema->getType('DBMoneyComposite');
        $this->assertNotNull($type);
        $this->assertNotNull($type->getFieldByName('currency'));
        $this->assertNotNull($type->getFieldByName('amount'));
    }

    public function testDeduplication()
    {
        $model = new DataObjectModel(DataObjectFake::class, new SchemaConfig());
        $model->setFieldAccessor(new FieldAccessor());

        $type = new ModelType($model);
        $type->addAllFields();

        $plugin = new DBFieldTypes();
        $schema = new Schema('test');
        $schema->addEnum(Enum::create(
            'MyEnumEnum',
            ['Test' => 'Test']
        ));
        $plugin->apply($type, $schema);

        $enum = $type->getFieldByName('myEnum');
        $this->assertNotNull($enum);
        $this->assertEquals('DataObjectFakeMyEnumEnum', $enum->getType());
        $this->assertNotNull($schema->getEnum('DataObjectFakeMyEnumEnum'));
        $this->assertNotNull($schema->getEnum('MyEnumEnum'));
    }

    public function testEnumReuse()
    {
        $model = new DataObjectModel(DataObjectFake::class, new SchemaConfig());
        $model->setFieldAccessor(new FieldAccessor());

        $type = new ModelType($model);
        $type->addAllFields();

        $plugin = new DBFieldTypes();
        $schema = new Schema('test');
        $schema->addEnum(Enum::create(
            'MyEnumEnum',
            [
                'ONE' => 'ONE',
                'TWO' => 'TWO',
            ]
        ));
        $plugin->apply($type, $schema);

        $enum = $type->getFieldByName('myEnum');
        $this->assertNotNull($enum);
        $this->assertEquals('MyEnumEnum', $enum->getType());
        $this->assertNotNull($schema->getEnum('MyEnumEnum'));
        // Did not create a new type because it found an existing one with the same signature.
        $this->assertNull($schema->getEnum('DataObjectFakeMyEnumEnum'));
    }

    public function testEnumCustomName()
    {
        $model = new DataObjectModel(DataObjectFake::class, new SchemaConfig());
        $model->setFieldAccessor(new FieldAccessor());

        $type = new ModelType($model);
        $type->addAllFields();

        $plugin = new DBFieldTypes();
        $schema = new Schema('test');
        $plugin->apply($type, $schema, [
            'enumTypeMapping' => [
                'DataObjectFake' => [
                    'myEnum' => 'CustomTypeName',
                ]
            ]
        ]);

        $enum = $type->getFieldByName('myEnum');
        $this->assertNotNull($enum);
        $this->assertEquals('CustomTypeName', $enum->getType());
        $this->assertNotNull($schema->getEnum('CustomTypeName'));
        $this->assertNull($schema->getEnum('DataObjectFakeMyEnumEnum'));
        $this->assertNull($schema->getEnum('MyEnumEnum'));
    }

    public function testEnumIgnore()
    {
        $model = new DataObjectModel(DataObjectFake::class, new SchemaConfig());
        $model->setFieldAccessor(new FieldAccessor());

        $type = new ModelType($model);
        $type->addAllFields();

        $plugin = new DBFieldTypes();
        $schema = new Schema('test');
        $plugin->apply($type, $schema, [
            'ignore' => [
                'DataObjectFake' => [
                    'myEnum' => true,
                ]
            ]
        ]);

        $notEnum = $type->getFieldByName('myEnum');
        $this->assertNotNull($notEnum);
        $this->assertEquals('String', $notEnum->getType());
        $this->assertNull($schema->getEnum('DataObjectFakeMyEnumEnum'));
        $this->assertNull($schema->getEnum('MyEnumEnum'));
    }
}
