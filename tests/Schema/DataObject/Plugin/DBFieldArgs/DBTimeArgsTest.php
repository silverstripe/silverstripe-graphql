<?php

namespace SilverStripe\GraphQL\Tests\Schema\DataObject\Plugin\DBFieldArgs;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Schema\DataObject\DataObjectModel;
use SilverStripe\GraphQL\Schema\DataObject\Plugin\DBFieldArgs\DBDateArgs;
use SilverStripe\GraphQL\Schema\DataObject\Plugin\DBFieldArgs\DBTimeArgs;
use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\SchemaConfig;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBTime;

class DBTimeArgsTest extends SapphireTest
{
    public function testApply()
    {
        $field = new ModelField('test', [], new DataObjectModel(DataObjectFake::class, new SchemaConfig()));
        $factory = new DBTimeArgs();
        $factory->applyToField($field);
        $args = $field->getArgs();

        $this->assertArrayHasKey('format', $args);
        $arg = $args['format'];
        $this->assertEquals($factory->getEnum()->getName(), $arg->getType());

        $this->assertArrayHasKey('customFormat', $args);
        $arg = $args['customFormat'];
        $this->assertEquals('String', $arg->getType());
    }

    public function testResolve()
    {
        $fake = $this->getMockBuilder(DBTime::class)
            ->setMethods(['Nice'])
            ->getMock();
        $fake->expects($this->once())
            ->method('Nice');

        DBTimeArgs::resolve($fake, ['format' => 'Nice']);

        $time = DBField::create_field('Time', '123445789');
        $result = DBTimeArgs::resolve($time, ['format' => 'FAIL']);
        // Referential equality if method not found
        $this->assertEquals($result, $time);

        $this->expectExceptionMessage('The "custom" option requires a value for "customFormat"');

        DBTimeArgs::resolve($time, ['format' => 'Custom']);

        $this->expectExceptionMessage('The "customFormat" argument should not be set for formats that are not "custom"');

        DBTimeArgs::resolve($time, ['customFormat' => 'test']);
    }
}
