<?php

namespace SilverStripe\GraphQL\Tests\Schema\DataObject\Plugin\DBFieldArgs;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Schema\DataObject\DataObjectModel;
use SilverStripe\GraphQL\Schema\DataObject\FieldAccessor;
use SilverStripe\GraphQL\Schema\DataObject\Plugin\DBFieldArgs\DBFieldArgsPlugin;
use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\SchemaConfig;
use SilverStripe\GraphQL\Schema\Type\ModelType;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\FieldType\DBText;

class DBFieldArgsPluginTest extends SapphireTest
{
    public function testApply()
    {
        $model = new DataObjectModel(DataObjectFake::class, new SchemaConfig());
        $model->setFieldAccessor(new FieldAccessor());

        $field1 = new ModelField('test1', [], $model);
        $field1->getMetadata()->set('dataClass', DBText::class);
        $field2 = new ModelField('test2', [], $model);
        $field2->getMetadata()->set('dataClass', DBDatetime::class);
        $field3 = new ModelField('test3', [], $model);
        $field3->getMetadata()->set('dataClass', null);

        $type = new ModelType($model, [
            'fields' => [
                'test1' => $field1,
                'test2' => $field2,
                'test3' => $field3,
            ]
        ]);
        $plugin = new DBFieldArgsPlugin();
        $plugin->apply($type, new Schema('test'));

        $field = $type->getFieldByName('test1');
        $args = $field->getArgs();
        $this->assertArrayHasKey('format', $args);
        $this->assertArrayHasKey('limit', $args);

        $field = $type->getFieldByName('test2');
        $args = $field->getArgs();
        $this->assertArrayHasKey('format', $args);
        $this->assertArrayHasKey('customFormat', $args);

        $field = $type->getFieldByName('test3');
        $args = $field->getArgs();
        $this->assertEmpty($args);
    }
}
