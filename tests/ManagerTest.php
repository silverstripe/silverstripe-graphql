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
                'mytype' => 'SilverStripe\GraphQL\Tests\Fake\TypeCreatorFake',
            ],
        ];
        $manager = new Manager($config);
        $this->assertInstanceOf(
            'SilverStripe\GraphQL\Tests\Fake\TypeCreatorFake',
            $manager->getType('mytype')
        );
    }

    public function testSchema()
    {
        $manager = new Manager();
        $manager->addType('SilverStripe\GraphQL\Tests\Fake\TypeCreatorFake', 'mytype');
        $manager->addQuery('SilverStripe\GraphQL\Tests\Fake\QueryCreatorFake', 'myquery');

        $schema = $manager->schema();
        $this->assertInstanceOf(Schema::class, $schema);
        $this->assertNotNull($schema->getQueryType()->getField('myquery'));

    }

    public function testAddTypeAsNamedString() {
        $manager = new Manager();
        $manager->addType('SilverStripe\GraphQL\Tests\Fake\TypeCreatorFake', 'mytype');
        $this->assertInstanceOf(
            'SilverStripe\GraphQL\Tests\Fake\TypeCreatorFake',
            $manager->getType('mytype')
        );
    }

    public function testAddTypeAsUnnamedString() {
        $manager = new Manager();
        $manager->addType('SilverStripe\GraphQL\Tests\Fake\TypeCreatorFake');
        $this->assertInstanceOf(
            'SilverStripe\GraphQL\Tests\Fake\TypeCreatorFake',
            $manager->getType('SilverStripe\GraphQL\Tests\Fake\TypeCreatorFake')
        );
    }

    public function testAddTypeAsNamedObject() {
        $manager = new Manager();
        $type = new TypeCreatorFake();
        $manager->addType($type, 'mytype');
        $this->assertInstanceOf(
            'SilverStripe\GraphQL\Tests\Fake\TypeCreatorFake',
            $manager->getType('mytype')
        );
        $this->assertEquals(
            $type,
            $manager->getType('mytype')
        );
    }

    public function testAddTypeAsUnnamedObject() {
        $manager = new Manager();
        $type = new TypeCreatorFake();
        $manager->addType($type);
        $this->assertInstanceOf(
            'SilverStripe\GraphQL\Tests\Fake\TypeCreatorFake',
            $manager->getType('SilverStripe\GraphQL\Tests\Fake\TypeCreatorFake')
        );
        $this->assertEquals(
            $type,
            $manager->getType('SilverStripe\GraphQL\Tests\Fake\TypeCreatorFake')
        );
    }

    public function testAddQueryAsNamedString() {
        $manager = new Manager();
        $manager->addType('SilverStripe\GraphQL\Tests\Fake\TypeCreatorFake', 'mytype');
        $manager->addQuery('SilverStripe\GraphQL\Tests\Fake\QueryCreatorFake', 'myquery');
        $this->assertInstanceOf(
            'SilverStripe\GraphQL\Tests\Fake\QueryCreatorFake',
            $manager->getQuery('myquery')
        );
    }

    public function testAddQueryAsUnnamedString() {
        $manager = new Manager();
        $manager->addType('SilverStripe\GraphQL\Tests\Fake\TypeCreatorFake', 'mytype');
        $manager->addQuery('SilverStripe\GraphQL\Tests\Fake\QueryCreatorFake');
        $this->assertInstanceOf(
            'SilverStripe\GraphQL\Tests\Fake\QueryCreatorFake',
            $manager->getQuery('SilverStripe\GraphQL\Tests\Fake\QueryCreatorFake')
        );
    }

    public function testAddQueryAsNamedObject() {
        $manager = new Manager();
        $type = new QueryCreatorFake();
        $manager->addType('SilverStripe\GraphQL\Tests\Fake\TypeCreatorFake', 'mytype');
        $manager->addQuery($type, 'myquery');
        $this->assertInstanceOf(
            'SilverStripe\GraphQL\Tests\Fake\QueryCreatorFake',
            $manager->getQuery('myquery')
        );
        $this->assertEquals(
            $type,
            $manager->getQuery('myquery')
        );
    }

    public function testAddQueryAsUnnamedObject() {
        $manager = new Manager();
        $type = new QueryCreatorFake();
        $manager->addType('SilverStripe\GraphQL\Tests\Fake\TypeCreatorFake', 'mytype');
        $manager->addQuery($type);
        $this->assertInstanceOf(
            'SilverStripe\GraphQL\Tests\Fake\QueryCreatorFake',
            $manager->getQuery('SilverStripe\GraphQL\Tests\Fake\QueryCreatorFake')
        );
        $this->assertEquals(
            $type,
            $manager->getQuery('SilverStripe\GraphQL\Tests\Fake\QueryCreatorFake')
        );
    }

    public function testQueryWithError()
    {
        $mock = $this->getMockBuilder('SilverStripe\GraphQL\Manager')
            ->setMethods(['queryAndReturnResult'])
            ->getMock();
        $responseData = new \stdClass();
        $responseData->data = null;
        $responseData->errors = [
            Error::createLocatedError(
                'Something went wrong',
                [ new SourceLocation(1, 10) ]
            )
        ];
        $mock->method('queryAndReturnResult')
            ->willReturn($responseData);

        $response = $mock->query('');
        $this->assertArrayHasKey('errors', $response);
    }
}
