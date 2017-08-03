<?php

namespace SilverStripe\GraphQL\Tests\Util;

use GraphQL\Type\Definition\IntType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\StringType;
use SilverStripe\Dev\SapphireTest;
use Doctrine\Instantiator\Exception\InvalidArgumentException;
use SilverStripe\GraphQL\Scaffolding\Util\ArrayTypeParser;

/**
 * @skipUpgrade
 */
class ArrayTypeParserTest extends SapphireTest
{
    public function testConstructor()
    {
        $parser = new ArrayTypeParser('test', []);
        $this->assertEquals('test', $parser->getType()->name);
    }

    public function testGetType()
    {
        $parser = new ArrayTypeParser('test', [
            'FieldOne' => 'String',
            'FieldTwo' => 'Int'
        ]);
        $type = $parser->getType();

        $this->assertInstanceOf(ObjectType::class, $type);
        $this->assertInstanceOf(StringType::class, $type->getField('FieldOne'));
        $this->assertInstanceOf(IntType::class, $type->getField('FieldTwo'));
    }

    public function testInvalidConstructorNotArray()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/second parameter must be an associative array/');
        new ArrayTypeParser('String');
    }

    public function testInvalidConstructorNotAssociative()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/second parameter must be an associative array/');
        new ArrayTypeParser('test', ['oranges', 'apples']);
    }

}
