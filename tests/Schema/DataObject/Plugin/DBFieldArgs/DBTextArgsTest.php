<?php

namespace SilverStripe\GraphQL\Tests\Schema\DataObject\Plugin\DBFieldArgs;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Schema\DataObject\DataObjectModel;
use SilverStripe\GraphQL\Schema\DataObject\Plugin\DBFieldArgs\DBHTMLTextArgs;
use SilverStripe\GraphQL\Schema\DataObject\Plugin\DBFieldArgs\DBTextArgs;
use SilverStripe\GraphQL\Schema\Field\ModelField;
use SilverStripe\GraphQL\Schema\SchemaConfig;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;
use SilverStripe\ORM\FieldType\DBText;

class DBTextArgsTest extends SapphireTest
{
    public function testApply()
    {
        $field = new ModelField('test', [], new DataObjectModel(DataObjectFake::class, new SchemaConfig()));
        $factory = new DBHTMLTextArgs();
        $factory->applyToField($field);
        $args = $field->getArgs();

        $this->assertArrayHasKey('format', $args);
        $arg = $args['format'];
        $this->assertEquals($factory->getEnum()->getName(), $arg->getType());

        $this->assertArrayHasKey('parseShortcodes', $args);
        $arg = $args['parseShortcodes'];
        $this->assertEquals('Boolean', $arg->getType());
    }

    public function testResolve()
    {
        $fake = $this->getMockBuilder(DBText::class)
            ->setMethods(['FirstParagraph'])
            ->getMock();
        $fake->expects($this->once())
            ->method('FirstParagraph');

        DBTextArgs::resolve($fake, ['format' => 'FirstParagraph']);
        DBTextArgs::resolve($fake, []);

        $this->expectExceptionMessage('Arg "limit" is not allowed for format "FirstParagraph"');
        DBTextArgs::resolve($fake, ['format' => 'FirstParagraph', 'limit' => 5]);

        $fake = $this->getMockBuilder(DBText::class)
            ->setMethods(['LimitSentences'])
            ->getMock();
        $fake->expects($this->once())
            ->method('LimitSentences')
            ->with([5]);

        DBTextArgs::resolve($fake, ['format' => 'LimitSentences', 'limit' => 5]);
    }
}
