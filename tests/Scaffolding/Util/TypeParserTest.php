<?php

namespace SilverStripe\GraphQL\Tests\Util;

use SilverStripe\Dev\SapphireTest;
use Doctrine\Instantiator\Exception\InvalidArgumentException;
use SilverStripe\GraphQL\Scaffolding\Util\StringTypeParser;
use GraphQL\Type\Definition\StringType;

/**
 * @skipUpgrade
 */
class TypeParserTest extends SapphireTest
{
    public function testTypeParser()
    {
        $parser = new StringTypeParser('String!(Test)');
        $this->assertTrue($parser->isRequired());
        $this->assertEquals('String', $parser->getArgTypeName());
        $this->assertEquals('Test', $parser->getDefaultValue());
        $this->assertTrue(is_string($parser->getDefaultValue()));

        $parser = new StringTypeParser('String! (Test)');
        $this->assertTrue($parser->isRequired());
        $this->assertEquals('String', $parser->getArgTypeName());
        $this->assertEquals('Test', $parser->getDefaultValue());

        $parser = new StringTypeParser('Int!');
        $this->assertTrue($parser->isRequired());
        $this->assertEquals('Int', $parser->getArgTypeName());
        $this->assertNull($parser->getDefaultValue());

        $parser = new StringTypeParser('Int!(23)');
        $this->assertTrue($parser->isRequired());
        $this->assertEquals('Int', $parser->getArgTypeName());
        $this->assertEquals('23', $parser->getDefaultValue());
        $this->assertTrue(is_int($parser->getDefaultValue()));

        $parser = new StringTypeParser('Boolean');
        $this->assertFalse($parser->isRequired());
        $this->assertEquals('Boolean', $parser->getArgTypeName());
        $this->assertNull($parser->getDefaultValue());

        $parser = new StringTypeParser('Boolean(1)');
        $this->assertFalse($parser->isRequired());
        $this->assertEquals('Boolean', $parser->getArgTypeName());
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

    public function testTypeInvalidDefault()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/Invalid type/');
        $type = new StringTypeParser('Nothing!(bob)');
        $type->getDefaultValue();
    }
}
