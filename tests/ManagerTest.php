<?php

namespace Chillu\GraphQL\Tests;

use Chillu\GraphQL\Manager;
use Chillu\GraphQL\Tests\Fake\TypeCreatorFake;
use Chillu\GraphQL\Tests\Fake\QueryCreatorFake;
use GraphQL\Type\Definition\Type;
use SilverStripe\Dev\SapphireTest;
use GraphQL\Error;
use GraphQL\Schema;
use GraphQL\Language\SourceLocation;

class ManagerTest extends SapphireTest
{
    public function testCreateFromConfig()
    {
        $config = [
            'types' => [
                'mytype' => TypeCreatorFake::class,
            ],
        ];
        $manager = Manager::createFromConfig($config);
        $this->assertInstanceOf(
            Type::class,
            $manager->getType('mytype')
        );
    }

    public function testSchema()
    {
        $manager = new Manager();
        $manager->addType($this->getType($manager), 'mytype');
        $manager->addQuery($this->getQuery($manager), 'myquery');

        $schema = $manager->schema();
        $this->assertInstanceOf(Schema::class, $schema);
        $this->assertNotNull($schema->getQueryType()->getField('myquery'));
    }

    public function testAddTypeAsNamedObject()
    {
        $manager = new Manager();
        $type = $this->getType($manager);
        $manager->addType($type, 'mytype');
        $this->assertEquals(
            $type,
            $manager->getType('mytype')
        );
    }

    public function testAddTypeAsUnnamedObject()
    {
        $manager = new Manager();
        $type = $this->getType($manager);
        $manager->addType($type);
        $this->assertEquals(
            $type,
            $manager->getType((string)$type)
        );
    }

    public function testAddQuery()
    {
        $manager = new Manager();
        $type = $this->getType($manager);
        $query = $this->getQuery($manager);
        $manager->addType($type, 'mytype');
        $manager->addQuery($query, 'myquery');
        $this->assertEquals(
            $query,
            $manager->getQuery('myquery')
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

    protected function getType(Manager $manager)
    {
        return (new TypeCreatorFake($manager))->toType();
    }

    protected function getQuery(Manager $manager)
    {
        return (new QueryCreatorFake($manager))->toArray();
    }
}
