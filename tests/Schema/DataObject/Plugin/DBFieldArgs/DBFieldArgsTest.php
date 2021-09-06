<?php


namespace SilverStripe\GraphQL\Tests\Schema\DataObject\Plugin\DBFieldArgs;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Schema\DataObject\Plugin\DBFieldArgs\DBFieldArgs;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBText;

class DBFieldArgsTest extends SapphireTest
{
    public function testBaseFormatResolver()
    {
        $fake = $this->getMockBuilder(DBText::class)
            ->setMethods(['FirstSentence'])
            ->getMock();
        $fake->expects($this->once())
            ->method('FirstSentence');

        DBFieldArgs::baseFormatResolver($fake, ['format' => 'FirstSentence']);

        $test = DBField::create_field('Text', 'test');
        $result = DBFieldArgs::baseFormatResolver($test, ['format' => 'FAIL']);

        // Referential equality if method not found
        $this->assertEquals($result, $test);
    }
}
