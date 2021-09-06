<?php

namespace SilverStripe\GraphQL\Tests\Schema\DataObject\Plugin\DBFieldArgs;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Config\ModelConfiguration;
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
        $fake->expects($this->exactly(4))
            ->method('setProcessShortcodes')
            ->withConsecutive([true], [false], [false], [true]);

        $trueConfig = new SchemaConfig();
        $trueConfig->set('modelConfig.DataObject', ['parseShortcodes' => true]);
        $falseConfig = new SchemaConfig();
        $falseConfig->set('modelConfig.DataObject', ['parseShortcodes' => false]);

        DBHTMLTextArgs::resolve($fake, ['parseShortcodes' => true], ['schemaConfig' => $falseConfig]);
        DBHTMLTextArgs::resolve($fake, [], ['schemaConfig' => $falseConfig]);
        DBHTMLTextArgs::resolve($fake, ['parseShortcodes' => false], ['schemaConfig' => $trueConfig]);
        DBHTMLTextArgs::resolve($fake, [], ['schemaConfig' => $trueConfig]);
    }
}
