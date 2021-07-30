<?php

namespace SilverStripe\GraphQL\Tests\Schema\DataObject\Plugin\DBFieldArgs;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Schema\DataObject\DataObjectModel;
use SilverStripe\GraphQL\Schema\DataObject\Plugin\DBFieldArgs\DBDateArgs;
use SilverStripe\GraphQL\Schema\DataObject\Plugin\DBFieldArgs\DBDatetimeArgs;
use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\SchemaConfig;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\FieldType\DBField;

class DBDatetimeArgsTest extends SapphireTest
{
    public function testApply()
    {
        $field = new ModelField('test', [], new DataObjectModel(DataObjectFake::class, new SchemaConfig()));
        $factory = new DBDatetimeArgs();
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
        $fake = $this->getMockBuilder(DBDatetime::class)
            ->setMethods(['Time'])
            ->getMock();
        $fake->expects($this->once())
            ->method('Time');

        DBDatetimeArgs::resolve($fake, ['format' => 'Time']);

        $date = DBField::create_field('Datetime', '123445789');
        $result = DBDateArgs::resolve($date, ['format' => 'FAIL']);
        // Referential equality if method not found
        $this->assertEquals($result, $date);

        $this->expectExceptionMessage('The "custom" option requires a value for "customFormat"');

        DBDateArgs::resolve($date, ['format' => 'Custom']);

        $this->expectExceptionMessage('The "customFormat" argument should not be set for formats that are not "custom"');

        DBDateArgs::resolve($date, ['customFormat' => 'test']);
    }
}
