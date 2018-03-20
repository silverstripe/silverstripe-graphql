<?php

namespace SilverStripe\GraphQL\Tests\Scaffolders;

use Exception;
use InvalidArgumentException;
use SilverStripe\Assets\File;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\Create;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\Read;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\DataObjectScaffolder;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\ListQueryScaffolder;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\MutationScaffolder;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\SchemaScaffolder;
use SilverStripe\GraphQL\Scaffolding\StaticSchema;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;
use SilverStripe\GraphQL\Tests\Fake\FakeInt;
use SilverStripe\GraphQL\Tests\Fake\FakePage;
use SilverStripe\GraphQL\Tests\Fake\FakeRedirectorPage;
use SilverStripe\GraphQL\Tests\Fake\FakeResolver;
use SilverStripe\GraphQL\Tests\Fake\FakeSiteTree;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Security\Member;

class SchemaScaffolderTest extends SapphireTest
{
    protected function setUp()
    {
        parent::setUp();
        foreach (Read::get_extensions() as $class) {
            Read::remove_extension($class);
        }
        foreach (SchemaScaffolder::get_extensions() as $class) {
            SchemaScaffolder::remove_extension($class);
        }
        foreach (DataObjectScaffolder::get_extensions() as $class) {
            DataObjectScaffolder::remove_extension($class);
        }
    }

    public function testSchemaScaffolderTypes()
    {
        $scaffolder = new SchemaScaffolder();
        $type = $scaffolder->type(DataObjectFake::class);
        $type->Test = true;
        $type2 = $scaffolder->type(DataObjectFake::class);

        $query = $scaffolder->query('testQuery', DataObjectFake::class);
        $query->Test = true;
        $query2 = $scaffolder->query('testQuery', DataObjectFake::class);

        $mutation = $scaffolder->mutation('testMutation', DataObjectFake::class);
        $mutation->Test = true;
        $mutation2 = $scaffolder->mutation('testMutation', DataObjectFake::class);

        $this->assertEquals(1, count($scaffolder->getTypes()));
        $this->assertTrue($type2->Test);

        $this->assertEquals(1, $scaffolder->getQueries()->count());
        $this->assertTrue($query2->Test);

        $this->assertEquals(1, $scaffolder->getMutations()->count());
        $this->assertTrue($mutation2->Test);

        $scaffolder->removeQuery('testQuery');
        $this->assertEquals(0, $scaffolder->getQueries()->count());

        $scaffolder->removeMutation('testMutation');
        $this->assertEquals(0, $scaffolder->getMutations()->count());
    }

    public function testSchemaScaffolderAddToManager()
    {
        Config::modify()->merge(FakePage::class, 'db', [
            'TestPageField' => 'Varchar',
        ]);

        $manager = new Manager();
        $scaffolder = (new SchemaScaffolder())
            ->type(FakeRedirectorPage::class)
                ->addFields(['Created', 'TestPageField', 'RedirectionType'])
                ->operation(SchemaScaffolder::CREATE)
                    ->end()
                ->operation(SchemaScaffolder::READ)
                    ->end()
                ->end()
            ->type(DataObjectFake::class)
                ->addFields(['Author'])
                ->nestedQuery('Files')
                    ->end()
                ->end()
            ->query('testQuery', DataObjectFake::class)
                ->end()
            ->mutation('testMutation', DataObjectFake::class)
                ->end();

        $scaffolder->addToManager($manager);
        $queries = $scaffolder->getQueries();
        $mutations = $scaffolder->getMutations();
        $types = $scaffolder->getTypes();

        $classNames = array_map(function (DataObjectScaffolder $scaffold) {
            return $scaffold->getDataObjectClass();
        }, $types);

        $this->assertEquals([
            FakeRedirectorPage::class,
            DataObjectFake::class,
            FakePage::class,
            FakeSiteTree::class,
            Member::class,
            File::class,
        ], $classNames);

        $this->assertEquals(
            ['Created', 'TestPageField', 'RedirectionType'],
            $scaffolder->type(FakeRedirectorPage::class)->getFields()->column('Name')
        );

        $this->assertEquals(
            ['Created', 'TestPageField'],
            $scaffolder->type(FakePage::class)->getFields()->column('Name')
        );

        $this->assertEquals(
            ['Created'],
            $scaffolder->type(FakeSiteTree::class)->getFields()->column('Name')
        );

        $this->assertEquals('testQuery', $queries->first()->getName());
        $this->assertEquals('testMutation', $mutations->first()->getName());

        $this->assertInstanceof(
            Read::class,
            $scaffolder->type(FakeRedirectorPage::class)->getOperations()->findByIdentifier(SchemaScaffolder::READ)
        );
        $this->assertInstanceof(
            Read::class,
            $scaffolder->type(FakePage::class)->getOperations()->findByIdentifier(SchemaScaffolder::READ)
        );
        $this->assertInstanceof(
            Read::class,
            $scaffolder->type(FakeSiteTree::class)->getOperations()->findByIdentifier(SchemaScaffolder::READ)
        );

        $this->assertInstanceof(
            Create::class,
            $scaffolder->type(FakeRedirectorPage::class)->getOperations()->findByIdentifier(SchemaScaffolder::CREATE)
        );
        $this->assertInstanceof(
            Create::class,
            $scaffolder->type(FakePage::class)->getOperations()->findByIdentifier(SchemaScaffolder::CREATE)
        );
        $this->assertInstanceof(
            Create::class,
            $scaffolder->type(FakeSiteTree::class)->getOperations()->findByIdentifier(SchemaScaffolder::CREATE)
        );
    }

    public function testSchemaScaffolderCreateFromConfigThrowsIfBadTypes()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/"types" must be a map of class name to settings/');
        SchemaScaffolder::createFromConfig([
            'types' => ['fail'],
        ]);
    }

    public function testSchemaScaffolderCreateFromConfigThrowsIfBadQueries()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/must be a map of operation name to settings/');
        SchemaScaffolder::createFromConfig([
            'types' => [
                DataObjectFake::class => [
                    'fields' => '*',
                ],
            ],
            'queries' => ['fail'],
        ]);
    }

    public function testSchemaScaffolderCreateFromConfig()
    {
        $observer = $this->getMockBuilder(SchemaScaffolder::class)
            ->setMethods(['query', 'mutation', 'type'])
            ->getMock();

        $observer->expects($this->once())
            ->method('query')
            ->will($this->returnValue(new ListQueryScaffolder('test', 'test')));

        $observer->expects($this->once())
            ->method('mutation')
            ->will($this->returnValue(new MutationScaffolder('test', 'test')));

        $observer->expects($this->once())
            ->method('type')
            ->willReturn(
                new DataObjectScaffolder(DataObjectFake::class)
            );

        Injector::inst()->registerService($observer, SchemaScaffolder::class);

        SchemaScaffolder::createFromConfig([
            'types' => [
                DataObjectFake::class => [
                    'fields' => ['MyField'],
                ],
            ],
            'queries' => [
                'testQuery' => [
                    'type' => DataObjectFake::class,
                    'resolver' => FakeResolver::class,
                ],
            ],
            'mutations' => [
                'testMutation' => [
                    'type' => DataObjectFake::class,
                    'resolver' => FakeResolver::class,
                ],
            ],
        ]);
    }

    public function testSchemaScaffolderFixedTypes()
    {
        Config::modify()->merge(SchemaScaffolder::class, 'fixed_types', [FakeInt::class]);
        Config::modify()->merge(FakeInt::class, 'graphql_type', [
            'FieldOne' => 'String',
            'FieldTwo' => 'Int'
        ]);
        $typeName = StaticSchema::inst()->typeName(FakeInt::class);
        $manager = new Manager();
        (new SchemaScaffolder())->addToManager($manager);
        $this->assertTrue($manager->hasType($typeName));
    }

    public function testSchemaScaffolderFixedTypeMustBeViewableData()
    {
        Config::modify()->merge(SchemaScaffolder::class, 'fixed_types', ['stdclass']);
        $this->expectException(Exception::class);
        $this->expectExceptionMessageRegExp('/Cannot auto register/');
        (new SchemaScaffolder())->addToManager(new Manager());
    }

    public function testSchemaScaffolderFixedTypeMustHaveTypeCreatorExtension()
    {
        Config::modify()->merge(SchemaScaffolder::class, 'fixed_types', [ArrayList::class]);
        $this->expectException(Exception::class);
        $this->expectExceptionMessageRegExp('/Cannot auto register/');
        (new SchemaScaffolder())->addToManager(new Manager());
    }

    public function testSchemaScaffolderFixedTypeMustBeAnArray()
    {
        Config::modify()->merge(SchemaScaffolder::class, 'fixed_types', 'fail');
        $this->expectException(Exception::class);
        $this->expectExceptionMessageRegExp('/must be an array/');
        (new SchemaScaffolder())->addToManager(new Manager());
    }
}
