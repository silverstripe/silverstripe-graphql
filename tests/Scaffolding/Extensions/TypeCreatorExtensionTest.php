<?php

namespace SilverStripe\GraphQL\Tests\Scaffolding\Extensions;

use GraphQL\Type\Definition\ObjectType;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Extensions\TypeCreatorExtension;
use SilverStripe\GraphQL\Scaffolding\Interfaces\TypeParserInterface;
use SilverStripe\GraphQL\Scaffolding\StaticSchema;
use SilverStripe\Core\Config\Config;
use GraphQL\Type\Definition\IntType;
use SilverStripe\ORM\FieldType\DBInt;

class TypeCreatorExtensionTest extends SapphireTest
{
    public function testGraphQLTypeAsString()
    {
        Config::modify()->merge(DBInt::class, 'graphql_type', 'Int');
        $manager = $this->getMockBuilder(Manager::class)
            ->setMethods(['hasType','getType','addType'])
            ->getMock();
        $manager->expects($this->never())
            ->method('addType');
        $manager->expects($this->never())
            ->method('getType');

        $fake = new DBInt(5);
        $extension = new TypeCreatorExtension();
        $extension->setOwner($fake);

        $parser = $extension->createTypeParser();
        $this->assertInstanceOf(TypeParserInterface::class, $parser);
        $this->assertInstanceOf(IntType::class, $parser->getType());
        $this->assertTrue($extension->isInternalGraphQLType());
        $extension->addToManager($manager);
        $this->assertInstanceOf(IntType::class, $extension->getGraphQLType($manager));
    }

    public function testGraphQLTypeAsArray()
    {
        Config::modify()->merge(DBInt::class, 'graphql_type', [
            'FieldOne' => 'String',
            'FieldTwo' => 'Int'
        ]);
        $typeName = StaticSchema::inst()->typeName(DBInt::class);

        $mockManager = $this->getMockBuilder(Manager::class)
            ->setMethods(['getType','addType'])
            ->getMock();
        $mockManager->expects($this->once())
            ->method('addType')
            ->with(
                $this->isInstanceOf(ObjectType::class),
                $this->equalTo($typeName)
            );
        $mockManager->expects($this->once())
            ->method('getType')
            ->with($typeName);
        $manager = new Manager();

        $fake = new DBInt(0);
        $extension = new TypeCreatorExtension();
        $extension->setOwner($fake);

        $parser = $extension->createTypeParser();
        $this->assertInstanceOf(TypeParserInterface::class, $parser);
        $this->assertInstanceOf(ObjectType::class, $parser->getType());
        $this->assertFalse($extension->isInternalGraphQLType());
        $extension->addToManager($mockManager);
        $extension->getGraphQLType($mockManager);

        $extension->addToManager($manager);
        $this->assertTrue($manager->hasType($typeName));
    }
}
