<?php

namespace SilverStripe\GraphQL\Tests\Schema\DataObject\Plugin\DBFieldArgs;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Schema\DataObject\DataObjectModel;
use SilverStripe\GraphQL\Schema\DataObject\Plugin\DBFieldArgs\DBDecimalArgs;
use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\SchemaConfig;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;

class DBDecimalArgsTest extends SapphireTest
{
    public function testApply()
    {
        $field = new ModelField('test', [], new DataObjectModel(DataObjectFake::class, new SchemaConfig()));
        $factory = new DBDecimalArgs();
        $factory->applyToField($field);
        $args = $field->getArgs();

        $this->assertArrayHasKey('format', $args);
        $arg = $args['format'];
        $this->assertEquals($factory->getEnum()->getName(), $arg->getType());
    }
}
