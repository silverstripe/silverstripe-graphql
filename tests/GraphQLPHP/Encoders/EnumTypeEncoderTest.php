<?php


namespace SilverStripe\GraphQL\Tests\GraphQLPHP\Encoders;


use GraphQL\Type\Definition\EnumType;
use PhpParser\PrettyPrinter\Standard;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\GraphQLPHP\Encoders\EnumTypeEncoder;
use SilverStripe\GraphQL\Schema\Components\Enum;
use SilverStripe\GraphQL\Schema\Components\FieldCollection;

class EnumTypeEncoderTest extends SapphireTest
{
    public function testAppliesTo()
    {
        $enum = $this->createEnum();
        $encoder = new EnumTypeEncoder();
        $this->assertTrue($encoder->appliesTo($enum));
        $this->assertFalse($encoder->appliesTo(new FieldCollection('fail')));
    }

    public function testCode()
    {
        $enum = $this->createEnum();
        $encoder = new EnumTypeEncoder();
        $expr = $encoder->getExpression($enum);
        $prettyPrinter = new Standard();
        $code = $prettyPrinter->prettyPrintExpr($expr);
        $type = eval('return ' . $code . ';');

        $this->assertInstanceOf(EnumType::class, $type);
        $this->assertArrayHasKey('name', $type->config);
        $this->assertArrayHasKey('description', $type->config);
        $this->assertArrayHasKey('values', $type->config);

        $this->assertEquals('colours', $type->config['name']);
        $this->assertEquals('black and white', $type->config['description']);
        $this->assertEquals([
            'fff' => 'white',
            '000' => 'black',
        ], $type->config['values']);
    }

    protected function createEnum()
    {
        return new Enum('colours', 'black and white', [
            'fff' => 'white',
            '000' => 'black',
        ]);
    }
}