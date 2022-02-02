<?php


namespace Schema\Type;

use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\NodeKind;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Schema\Type\TypeReference;

class TypeReferenceTest extends SapphireTest
{
    public function testToAST()
    {
        $typeName = '[MyType!]!';
        $ref = TypeReference::create($typeName);
        $this->assertInstanceOf(Node::class, $ref->toAST());
    }

    public function testDefaultValue()
    {
        $typeName = 'String! = tester';
        $ref = TypeReference::create($typeName);
        $this->assertEquals('tester', $ref->getDefaultValue());

        $typeName = 'String!=tester';
        $ref = TypeReference::create($typeName);
        $this->assertEquals('tester', $ref->getDefaultValue());

        $typeName = 'String!= tester';
        $ref = TypeReference::create($typeName);
        $this->assertEquals('tester', $ref->getDefaultValue());

        $typeName = 'String!  =         tester';
        $ref = TypeReference::create($typeName);
        $this->assertEquals('tester', $ref->getDefaultValue());

        $typeName = 'String! : tester';
        $ref = TypeReference::create($typeName);
        $this->assertNull($ref->getDefaultValue());
    }

    public function testIsList()
    {
        $typeName = 'MyType!';
        $ref = TypeReference::create($typeName);
        $this->assertFalse($ref->isList());

        $typeName = '[MyType!]';
        $ref = TypeReference::create($typeName);
        $this->assertTrue($ref->isList());
    }

    public function testIsRequired()
    {
        $typeName = 'MyType!';
        $ref = TypeReference::create($typeName);
        $this->assertTrue($ref->isRequired());

        $typeName = '[MyType!]';
        $ref = TypeReference::create($typeName);
        $this->assertTrue($ref->isRequired());

        $typeName = '[MyType]';
        $ref = TypeReference::create($typeName);
        $this->assertFalse($ref->isRequired());
    }

    public function testGetTypeName()
    {
        $typeName = '[MyType!]';
        $ref = TypeReference::create($typeName);
        [$name, $path] = $ref->getTypeName();
        $this->assertEquals('MyType', $name);
        $this->assertEquals([NodeKind::LIST_TYPE, NodeKind::NON_NULL_TYPE], $path);
    }

    public function testGetNamedType()
    {
        $typeName = '[MyType!]';
        $ref = TypeReference::create($typeName);
        $this->assertEquals('MyType', $ref->getNamedType());

        $typeName = 'MyType';
        $ref = TypeReference::create($typeName);
        $this->assertEquals('MyType', $ref->getNamedType());
    }

    public function testGetRawType()
    {
        $typeName = '[MyType!]';
        $ref = TypeReference::create($typeName);
        $this->assertEquals('[MyType!]', $ref->getRawType());
    }

    public function testIsInternal()
    {
        $typeName = '[MyType!]';
        $ref = TypeReference::create($typeName);
        $this->assertFalse($ref->isInternal());

        $typeName = '[String!]';
        $ref = TypeReference::create($typeName);
        $this->assertTrue($ref->isInternal());
    }

    public function testCreateFromPath()
    {
        $name = 'MyType';
        $path = [NodeKind::NON_NULL_TYPE, NodeKind::LIST_TYPE, NodeKind::NON_NULL_TYPE];
        $ref = TypeReference::createFromPath($name, $path);
        $this->assertInstanceOf(TypeReference::class, $ref);
        $this->assertEquals('[MyType!]!', $ref->getRawType());
    }
}
