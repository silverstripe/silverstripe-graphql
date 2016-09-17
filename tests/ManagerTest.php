<?php

namespace SilverStripe\GraphQL\Tests;

use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Tests\Fake\TypeCreatorFake;
use SilverStripe\GraphQL\Tests\Fake\QueryCreatorFake;
use SilverStripe\Dev\SapphireTest;
use GraphQL\Error;
use GraphQL\Schema;
use GraphQL\Language\SourceLocation;

class ManagerTest extends SapphireTest
{
    public function testConstructWithConfig()
    {
        $config = [
            'types' => [
                'mytype' => TypeCreatorFake::class,
            ],
        ];
        $manager = new Manager($config);
        $this->assertInstanceOf(
            TypeCreatorFake::class,
            $manager->getType('mytype')
        );
    }

    public function testSchema()
    {
        $manager = new Manager();
        $manager->addType(TypeCreatorFake::class, 'mytype');
        $manager->addQuery(QueryCreatorFake::class, 'myquery');

        $schema = $manager->schema();
        $this->assertInstanceOf(Schema::class, $schema);
        $this->assertNotNull($schema->getQueryType()->getField('myquery'));
    }

    public function testAddTypeAsNamedString()
    {
        $manager = new Manager();
        $manager->addType(TypeCreatorFake::class, 'mytype');
        $this->assertInstanceOf(
            TypeCreatorFake::class,
            $manager->getType('mytype')
        );
    }

    public function testAddTypeAsUnnamedString()
    {
        $manager = new Manager();
        $manager->addType(TypeCreatorFake::class);
        $this->assertInstanceOf(
            TypeCreatorFake::class,
            $manager->getType(TypeCreatorFake::class)
        );
    }

    public function testAddTypeAsNamedObject()
    {
        $manager = new Manager();
        $type = new TypeCreatorFake();
        $manager->addType($type, 'mytype');
        $this->assertInstanceOf(
            TypeCreatorFake::class,
            $manager->getType('mytype')
        );
        $this->assertEquals(
            $type,
            $manager->getType('mytype')
        );
    }

    public function testAddTypeAsUnnamedObject()
    {
        $manager = new Manager();
        $type = new TypeCreatorFake();
        $manager->addType($type);
        $this->assertInstanceOf(
            TypeCreatorFake::class,
            $manager->getType(TypeCreatorFake::class)
        );
        $this->assertEquals(
            $type,
            $manager->getType(TypeCreatorFake::class)
        );
    }

    public function testAddQueryAsNamedString()
    {
        $manager = new Manager();
        $manager->addType(TypeCreatorFake::class, 'mytype');
        $manager->addQuery(QueryCreatorFake::class, 'myquery');
        $this->assertInstanceOf(
            QueryCreatorFake::class,
            $manager->getQuery('myquery')
        );
    }

    public function testAddQueryAsUnnamedString()
    {
        $manager = new Manager();
        $manager->addType(TypeCreatorFake::class, 'mytype');
        $manager->addQuery(QueryCreatorFake::class);
        $this->assertInstanceOf(
            QueryCreatorFake::class,
            $manager->getQuery(QueryCreatorFake::class)
        );
    }

    public function testAddQueryAsNamedObject()
    {
        $manager = new Manager();
        $type = new QueryCreatorFake();
        $manager->addType(TypeCreatorFake::class, 'mytype');
        $manager->addQuery($type, 'myquery');
        $this->assertInstanceOf(
            QueryCreatorFake::class,
            $manager->getQuery('myquery')
        );
        $this->assertEquals(
            $type,
            $manager->getQuery('myquery')
        );
    }

    public function testAddQueryAsUnnamedObject()
    {
        $manager = new Manager();
        $type = new QueryCreatorFake();
        $manager->addType(TypeCreatorFake::class, 'mytype');
        $manager->addQuery($type);
        $this->assertInstanceOf(
            QueryCreatorFake::class,
            $manager->getQuery(QueryCreatorFake::class)
        );
        $this->assertEquals(
            $type,
            $manager->getQuery(QueryCreatorFake::class)
        );
    }

    public function testQueryWithError()
    {
        $mock = $this->getMockBuilder(Manager::class)
            ->setMethods(['queryAndReturnResult'])
            ->getMock();
        $responseData = new \stdClass();
        $responseData->data = null;
        $responseData->errors = [
            Error::createLocatedError(
                'Something went wrong',
                [new SourceLocation(1, 10)]
            ),
        ];
        $mock->method('queryAndReturnResult')
            ->willReturn($responseData);

        $response = $mock->query('');
        $this->assertArrayHasKey('errors', $response);
    }
}
