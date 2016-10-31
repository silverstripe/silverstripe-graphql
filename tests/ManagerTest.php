<?php

namespace SilverStripe\GraphQL\Tests;

use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Tests\Fake\TypeCreatorFake;
use SilverStripe\GraphQL\Tests\Fake\QueryCreatorFake;
use SilverStripe\GraphQL\Tests\Fake\MutationCreatorFake;
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
            'queries' => [
                'myquery' => QueryCreatorFake::class,
            ],
            'mutations' => [
                'mymutation' => MutationCreatorFake::class,
            ],
        ];
        $manager = Manager::createFromConfig($config);
        $this->assertInstanceOf(
            Type::class,
            $manager->getType('mytype')
        );
        $this->assertInternalType(
            'array',
            $manager->getQuery('myquery')
        );
        $this->assertInternalType(
            'array',
            $manager->getMutation('mymutation')
        );
    }

    public function testSchema()
    {
        $manager = new Manager();
        $manager->addType($this->getType($manager), 'mytype');
        $manager->addQuery($this->getQuery($manager), 'myquery');
        $manager->addMutation($this->getMutation($manager), 'mymutation');

        $schema = $manager->schema();
        $this->assertInstanceOf(Schema::class, $schema);
        $this->assertNotNull($schema->getType('TypeCreatorFake'));
        $this->assertNotNull($schema->getMutationType()->getField('mymutation'));
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

    public function testAddMutation()
    {
        $manager = new Manager();
        $mutation = $this->getMutation($manager);
        $type = $this->getType($manager);
        $manager->addMutation($mutation, 'mymutation');
        $manager->addType($type, 'mytype');
        $this->assertEquals(
            $mutation,
            $manager->getMutation('mymutation')
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

    protected function getMutation(Manager $manager)
    {
        return (new MutationCreatorFake($manager))->toArray();
    }
}
