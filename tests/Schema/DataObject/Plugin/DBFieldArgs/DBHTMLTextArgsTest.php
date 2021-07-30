<?php

namespace SilverStripe\GraphQL\Tests\Schema\DataObject\Plugin\DBFieldArgs;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Schema\DataObject\DataObjectModel;
use SilverStripe\GraphQL\Schema\DataObject\Plugin\DBFieldArgs\DBHTMLTextArgs;
use SilverStripe\GraphQL\Schema\DataObject\Plugin\DBFieldArgs\DBTextArgs;
use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\SchemaConfig;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;
use SilverStripe\ORM\FieldType\DBHTMLText;

class DBHTMLTextArgsTest extends SapphireTest
{
    public function testApply()
    {
        $field = new ModelField('test', [], new DataObjectModel(DataObjectFake::class, new SchemaConfig()));
        $factory = new DBTextArgs();
        $factory->applyToField($field);
        $args = $field->getArgs();

        $this->assertArrayHasKey('format', $args);
        $arg = $args['format'];
        $this->assertEquals($factory->getEnum()->getName(), $arg->getType());

        $this->assertArrayHasKey('limit', $args);
        $arg = $args['limit'];
        $this->assertEquals('Int', $arg->getType());
    }

    public function testResolve()
    {
        $fake = $this->getMockBuilder(DBHTMLText::class)
            ->setMethods(['setProcessShortcodes'])
            ->getMock();
        $fake->expects($this->once())
            ->method('setProcessShortcodes');

        DBHTMLTextArgs::resolve($fake, ['parseShortcodes' => true]);
        DBHTMLTextArgs::resolve($fake, []);
    }
}
