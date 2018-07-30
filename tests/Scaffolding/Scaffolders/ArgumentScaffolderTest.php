<?php

namespace SilverStripe\GraphQL\Tests\Scaffolders\Scaffolding;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\ArgumentScaffolder;
use GraphQL\Type\Definition\StringType;
use GraphQL\Type\Definition\NonNull;
use InvalidArgumentException;

class ArgumentScaffolderTest extends SapphireTest
{
    public function testArgumentScaffolder()
    {
        $scaffolder = new ArgumentScaffolder('Test', 'String');
        $scaffolder->setRequired(false)
            ->setDescription('Description')
            ->setDefaultValue('Default');

        $this->assertEquals('Description', $scaffolder->getDescription());
        $this->assertEquals('Default', $scaffolder->getDefaultValue());
        $this->assertFalse($scaffolder->isRequired());

        $scaffolder->applyConfig([
            'description' => 'Foo',
            'default' => 'Bar',
            'required' => true,
        ]);

        $this->assertEquals('Foo', $scaffolder->getDescription());
        $this->assertEquals('Bar', $scaffolder->getDefaultValue());
        $this->assertTrue($scaffolder->isRequired());

        $arr = $scaffolder->toArray();

        $this->assertEquals('Foo', $arr['description']);
        $this->assertEquals('Bar', $arr['defaultValue']);
        $this->assertInstanceOf(NonNull::class, $arr['type']);
        $this->assertInstanceOf(StringType::class, $arr['type']->getWrappedType());

        $scaffolder->setDefaultValue(null);
        $arr = $scaffolder->toArray();
        $this->assertArrayNotHasKey('defaultValue', $arr);
    }

    public function testNonInternalType()
    {
        $manager = new Manager();
        $manager->addType(new InputObjectType([
            'name' => 'MyType',
            'fields' => [
                'test' => ['type' => Type::string()]
            ]
        ]), 'MyType');
        $scaffolder = new ArgumentScaffolder('Test', 'MyType');

        $result = $scaffolder->toArray($manager);
        $this->assertInstanceOf(InputObjectType::class, $result['type']);
    }

    public function testNonInternalTypeNoManager()
    {
        $this->expectException(InvalidArgumentException::class);
        $scaffolder = new ArgumentScaffolder('Test', 'MyType');
        $scaffolder->toArray();
    }
}
