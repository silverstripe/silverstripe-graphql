<?php

namespace SilverStripe\GraphQL\Tests\Scaffolding\Util;

use SilverStripe\Dev\SapphireTest;
use InvalidArgumentException;
use SilverStripe\GraphQL\Scaffolding\Util\StringTypeParser;
use GraphQL\Type\Definition\StringType;

/**
 * @skipUpgrade
 */
class StringTypeParserTest extends SapphireTest
{
    public function testStringTypeParser()
    {
        $parser = new StringTypeParser('String!(Test)');
        $this->assertTrue($parser->isRequired());
        $this->assertEquals('String', $parser->getName());
        $this->assertEquals('Test', $parser->getDefaultValue());
        $this->assertTrue(is_string($parser->getDefaultValue()));

        $parser = new StringTypeParser('String! (Test)');
        $this->assertTrue($parser->isRequired());
        $this->assertEquals('String', $parser->getName());
        $this->assertEquals('Test', $parser->getDefaultValue());

        $parser = new StringTypeParser('Int!');
        $this->assertTrue($parser->isRequired());
        $this->assertEquals('Int', $parser->getName());
        $this->assertNull($parser->getDefaultValue());

        $parser = new StringTypeParser('Int!(23)');
        $this->assertTrue($parser->isRequired());
        $this->assertEquals('Int', $parser->getName());
        $this->assertEquals('23', $parser->getDefaultValue());
        $this->assertTrue(is_int($parser->getDefaultValue()));

        $parser = new StringTypeParser('Boolean');
        $this->assertFalse($parser->isRequired());
        $this->assertEquals('Boolean', $parser->getName());
        $this->assertNull($parser->getDefaultValue());

        $parser = new StringTypeParser('Boolean(1)');
        $this->assertFalse($parser->isRequired());
        $this->assertEquals('Boolean', $parser->getName());
        $this->assertEquals('1', $parser->getDefaultValue());
        $this->assertTrue(is_bool($parser->getDefaultValue()));

        $parser = new StringTypeParser('String!(Test)');
        $this->assertInstanceOf(StringType::class, $parser->getType());
        $this->assertEquals('Test', $parser->getDefaultValue());
    }

    public function testTypeInvalid()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/Invalid argument/');
        new StringTypeParser('  ... Nothing');
    }

    public function testTypeNotAString()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/must be passed a string/');
        new StringTypeParser(['fail']);
    }

    public function testTypeInvalidDefault()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/Invalid type/');
        $type = new StringTypeParser('Nothing!(bob)');
        $type->getDefaultValue();
    }
}
