<?php

namespace SilverStripe\GraphQL\Tests\Scaffolders\Scaffolding;

use Exception;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\UnionType;
use InvalidArgumentException;
use SilverStripe\Assets\File;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Manager;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\Create;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\Delete;
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

        $expectedTypes = [];
        $explicitTypes = [
            FakeRedirectorPage::class,
            DataObjectFake::class,
            Member::class,
            File::class,
        ];
        foreach ($explicitTypes as $className) {
            $expectedTypes = array_merge(
                [$className],
                $expectedTypes,
                StaticSchema::inst()->getDescendants($className),
                StaticSchema::inst()->getAncestry($className)
            );
        }

        sort($expectedTypes);
        sort($classNames);
        $this->assertEquals($expectedTypes, $classNames);

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

    public function testUnionInheritanceForTypes()
    {
        $scaffolder = (new SchemaScaffolder())
            ->type(FakeRedirectorPage::class)
            ->addFields(['Title', 'RedirectionType'])
            ->operation(SchemaScaffolder::READ)
                ->setName('READ')
            ->end()
            ->operation(SchemaScaffolder::DELETE)
                ->setName('DELETE')
            ->end()
            ->end();
        $scaffolder->addToManager($manager = new Manager());

        $inheritanceTypeName = StaticSchema::inst()
            ->inheritanceTypeNameForDataObject(FakeRedirectorPage::class);
        $normalTypeName = StaticSchema::inst()
            ->typeNameForDataObject(FakeRedirectorPage::class);

        $this->assertFalse($manager->hasType($inheritanceTypeName));
        $this->assertTrue($manager->hasType($normalTypeName));

        $this->assertNotNull($manager->getQuery('READ'));
        $this->assertNotNull($manager->getMutation('DELETE'));

        /** @var ObjectType $type */
        $type = $manager->getType($normalTypeName);
        $fields = $type->getFields();
        $this->assertArrayHasKey('Title', $fields);
        $this->assertArrayHasKey('RedirectionType', $fields);
        $ancestors = StaticSchema::inst()->getAncestry(FakeRedirectorPage::class);

        foreach ($ancestors as $ancestor) {
            $inheritanceTypeName = StaticSchema::inst()
                ->inheritanceTypeNameForDataObject($ancestor);
            $normalTypeName = StaticSchema::inst()
                ->typeNameForDataObject($ancestor);

            $this->assertTrue($manager->hasType($inheritanceTypeName));
            /* @var UnionType $type */
            $type = $manager->getType($inheritanceTypeName);
            $numDescendants = count(StaticSchema::inst()->getDescendants($ancestor));
            $this->assertCount($numDescendants + 1, $type->getTypes());
            $this->assertTrue($manager->hasType($normalTypeName));
            /* @var ObjectType $type */
            $type = $manager->getType($normalTypeName);
            $this->assertArrayHasKey('Title', $type->getFields());

            $read = new Read($ancestor);
            $delete = new Delete($ancestor);

            $this->assertNotNull($manager->getQuery($read->getName()));
            $this->assertNotNull($manager->getMutation($delete->getName()));
        }
    }

    public function testUnionInheritanceForFields()
    {
        $scaffolder = (new SchemaScaffolder())
            ->type(DataObjectFake::class)
                ->addFields(['MyField', 'Author'])
                ->nestedQuery('Files')
                    ->end()
            ->end();
        $scaffolder->addToManager($manager = new Manager());
        $inheritanceTypeName = StaticSchema::inst()
            ->inheritanceTypeNameForDataObject(Member::class);
        $normalTypeName = StaticSchema::inst()
            ->typeNameForDataObject(Member::class);

        $this->assertTrue($manager->hasType($inheritanceTypeName));
        $this->assertTrue($manager->hasType($normalTypeName));

        /* @var UnionType $union */
        $union = $manager->getType($inheritanceTypeName);
        $descendants = StaticSchema::inst()->getDescendants(Member::class);
        $this->assertCount(count($descendants) + 1, $union->getTypes());
        $inheritanceTypeName = StaticSchema::inst()
            ->inheritanceTypeNameForDataObject(File::class);
        $normalTypeName = StaticSchema::inst()
            ->typeNameForDataObject(File::class);

        $this->assertTrue($manager->hasType($inheritanceTypeName));
        $this->assertTrue($manager->hasType($normalTypeName));

        $union = $manager->getType($inheritanceTypeName);
        $descendants = StaticSchema::inst()->getDescendants(File::class);
        $this->assertCount(count($descendants) + 1, $union->getTypes());
    }
}
