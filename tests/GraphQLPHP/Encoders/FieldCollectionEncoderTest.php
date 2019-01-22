<?php


namespace SilverStripe\GraphQL\Tests\GraphQLPHP\Encoders;


use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use PhpParser\BuilderFactory;
use PhpParser\PrettyPrinter\Standard;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\GraphQLPHP\Encoders\FieldCollectionEncoder;
use SilverStripe\GraphQL\GraphQLPHP\Encoders\TypeReferenceEncoder;
use SilverStripe\GraphQL\Schema\Components\DynamicFunction;
use SilverStripe\GraphQL\Schema\Components\Enum;
use SilverStripe\GraphQL\Schema\Components\Field;
use SilverStripe\GraphQL\Schema\Components\FieldCollection;
use SilverStripe\GraphQL\Schema\Components\InternalType;
use SilverStripe\GraphQL\Schema\Components\StaticFunction;
use SilverStripe\GraphQL\Schema\Encoding\Encoders\DynamicFunctionEncoder;
use SilverStripe\GraphQL\Schema\Encoding\Encoders\StaticFunctionEncoder;
use SilverStripe\GraphQL\Schema\Encoding\Registries\ResolverEncoderRegistry;
use SilverStripe\GraphQL\Tests\Fake\ClosureFactoryFake;
use SilverStripe\GraphQL\Tests\Fake\RegistryFetcherFake;

class FieldCollectionEncoderTest extends SapphireTest
{
    public function testAppliesTo()
    {
        $encoder = $this->createEncoder();
        $obj = $this->createType();
        $this->assertTrue($encoder->appliesTo($obj));
        $this->assertFalse($encoder->appliesTo(new Enum('fail')));
    }

    public function testCode()
    {
        $obj = $this->createType();
        $encoder = $this->createEncoder();
        $expr = $encoder->getExpression($obj);
        $prettyPrinter = new Standard();
        $code = $prettyPrinter->prettyPrintExpr($expr);
        $type = eval('return ' . $code . ';');

        $this->assertInstanceOf(ObjectType::class, $type);
        /* @var ObjectType $type */
        $this->assertArrayHasKey('name', $type->config);
        $this->assertArrayHasKey('description', $type->config);
        $this->assertArrayHasKey('fields', $type->config);

        $fields = $type->getFields();
        $this->assertCount(3, $fields);
        $this->assertArrayHasKey('url', $fields);
        $this->assertInstanceOf(FieldDefinition::class, $fields['url']);
        $this->assertEquals('url', $fields['url']->name);
        $this->assertEquals(Type::string(), $fields['url']->getType());

        $this->assertArrayHasKey('title', $fields);
        $this->assertInstanceOf(FieldDefinition::class, $fields['title']);
        $this->assertEquals('title', $fields['title']->name);
        $this->assertEquals('awesometype', $fields['title']->getType());
        $this->assertEquals('resolved', call_user_func($fields['title']->resolveFn));

        $this->assertArrayHasKey('size', $fields);
        $this->assertInstanceOf(FieldDefinition::class, $fields['size']);
        $this->assertEquals('size', $fields['size']->name);
        $this->assertEquals('crazytype', $fields['size']->getType());
        $this->assertEquals('100MB', call_user_func($fields['size']->resolveFn));
    }

    protected function createEncoder()
    {
        $factory = new BuilderFactory();

        return new FieldCollectionEncoder(
            new TypeReferenceEncoder(
                $factory,
                new RegistryFetcherFake()
            ),
            new ResolverEncoderRegistry(
                new StaticFunctionEncoder(),
                new DynamicFunctionEncoder()
            )
        );
    }

    protected function createType()
    {
        return new FieldCollection(
            'image',
            'its an image',
            [
                new Field('url', InternalType::string()),
                new Field('title', 'awesometype', new StaticFunction([static::class, 'myResolver'])),
                new Field('size', 'crazytype', new DynamicFunction(new ClosureFactoryFake(['mb' => '100MB']))),
            ]
        );
    }

    public static function myResolver()
    {
        return 'resolved';
    }
}