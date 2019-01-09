<?php

namespace SilverStripe\GraphQL\Tests;

use GraphQL\Error\Error;
use GraphQL\Language\SourceLocation;
use GraphQL\Schema;
use GraphQL\Type\Definition\Type;
use InvalidArgumentException;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\PersistedQuery\PersistedQueryMappingProvider;
use SilverStripe\GraphQL\Tests\Fake\MutationCreatorFake;
use SilverStripe\GraphQL\Tests\Fake\QueryCreatorFake;
use SilverStripe\GraphQL\Tests\Fake\TypeCreatorFake;
use SilverStripe\Security\IdentityStore;
use SilverStripe\Security\Member;

class ManagerTest extends SapphireTest
{

    protected function setUp()
    {
        parent::setUp();
        /** @var IdentityStore $store */
        $store = Injector::inst()->get(IdentityStore::class);
        $store->logOut();
    }


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
        $manager = Manager::create()->applyConfig($config);
        $this->assertInstanceOf(
            Type::class,
            $manager->getType('mytype')
        );
        $this->assertInstanceOf(
            'Closure',
            $manager->getQuery('myquery')
        );
        $this->assertInstanceOf(
            'Closure',
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
        $manager->addType($type, 'mytype');

        $query = $this->getQuery($manager);
        $manager->addQuery($query, 'myquery');

        $this->assertEquals(
            $query,
            $manager->getQuery('myquery')
        );
        $this->assertEquals(
            $type,
            $manager->getType('mytype')
        );
    }

    public function testAddMutation()
    {
        $manager = new Manager();
        $type = $this->getType($manager);
        $manager->addType($type, 'mytype');

        $mutation = $this->getMutation($manager);
        $manager->addMutation($mutation, 'mymutation');

        $this->assertEquals(
            $mutation,
            $manager->getMutation('mymutation')
        );
    }

    public function testSchemaKey()
    {
        $manager = new Manager();
        $this->assertNull($manager->getSchemaKey());
        $manager->setSchemaKey('test');
        $this->assertEquals('test', $manager->getSchemaKey());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/must be a string/');
        $manager->setSchemaKey(['test']);
        $this->assertEquals('test', $manager->getSchemaKey());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/cannnot be empty/');
        $manager->setSchemaKey('');
        $this->assertEquals('test', $manager->getSchemaKey());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/alphanumeric/');
        $manager->setSchemaKey('completely % invalid #key');
        $this->assertEquals('test', $manager->getSchemaKey());
    }

    public function testConfigure()
    {
        $mock = $this->getMockBuilder(Manager::class)
            ->setMethods(['applyConfig', 'getSchemaKey'])
            ->getMock();
        $mock->expects($this->once())
            ->method('applyConfig')
            ->with(
                ['some' => 'setting']
            );
        $mock->expects($this->any())
            ->method('getSchemaKey')
            ->willReturn('testKey');

        Config::modify()->set(
            Manager::class,
            'schemas',
            [
                'testKey' => [
                    'some' => 'setting'
                ]
            ]
        );

        $mock->configure();
    }

    public function testQueryWithError()
    {
        /** @var Manager $mock */
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

    /**
     * Test the getter and setter for the Member. If not set, Member should be retrieved from the session.
     */
    public function testGetAndSetMember()
    {
        $manager = new Manager;
        $this->assertNull($manager->getMember());

        $member = Member::create();
        $manager->setMember($member);
        $this->assertSame($member, $manager->getMember());
    }

    public function testGetPersistedQueryByID()
    {
        $stub = $this->createMock(PersistedQueryMappingProvider::class);
        $stub->expects($this->once())
            ->method('getByID')
            ->with(
                $this->equalTo('someID'),
                $this->equalTo('default')
            )
            ->willReturn('someQuery');
        Injector::inst()->registerService($stub, PersistedQueryMappingProvider::class);

        $manager = new Manager();

        $result = $manager->getQueryFromPersistedID('someID');
        $this->assertEquals('someQuery', $result);
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
