<?php

namespace SilverStripe\GraphQL\Tests\Scaffolders\Scaffolding;

use SilverStripe\Core\Config\Config;
use SilverStripe\GraphQL\Manager;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Scaffolding\Extensions\TypeCreatorExtension;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\DataObjectScaffolder;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\OperationScaffolder;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;
use SilverStripe\GraphQL\Tests\Fake\FakeSiteTree;
use SilverStripe\GraphQL\Tests\Fake\FakePage;
use SilverStripe\GraphQL\Tests\Fake\FakeRedirectorPage;
use InvalidArgumentException;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\SchemaScaffolder;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\QueryScaffolder;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\Create;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\Read;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\Update;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\Delete;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use Exception;
use SilverStripe\ORM\FieldType\DBInt;

class DataObjectScaffolderTest extends SapphireTest
{
    protected function setUp()
    {
        parent::setUp();
        foreach (Read::get_extensions() as $class) {
            Read::remove_extension($class);
        }
    }

    public function testDataObjectScaffolderConstructor()
    {
        $scaffolder = $this->getFakeScaffolder();
        $this->assertEquals(DataObjectFake::class, $scaffolder->getDataObjectClass());
        $this->assertInstanceOf(DataObjectFake::class, $scaffolder->getDataObjectInstance());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/Non-existent classname/i');
        new DataObjectScaffolder('fail');
    }

    public function testDataObjectScaffolderFields()
    {
        $scaffolder = $this->getFakeScaffolder();

        $scaffolder->addField('MyField');
        $this->assertEquals(['MyField'], $scaffolder->getFields()->column('Name'));

        $scaffolder->addField('MyField', 'Some description');
        $this->assertEquals(
            'Some description',
            $scaffolder->getFieldDescription('MyField')
        );

        $scaffolder->addFields([
            'MyField',
            'MyInt' => 'Int description',
        ]);

        $this->assertEquals(['MyField', 'MyInt'], $scaffolder->getFields()->column('Name'));
        $this->assertNull(
            $scaffolder->getFieldDescription('MyField')
        );
        $this->assertEquals(
            'Int description',
            $scaffolder->getFieldDescription('MyInt')
        );

        $scaffolder->setFieldDescription('MyInt', 'New int description');
        $this->assertEquals(
            'New int description',
            $scaffolder->getFieldDescription('MyInt')
        );

        $scaffolder = $this->getFakeScaffolder();
        $scaffolder->addAllFields();
        $this->assertEquals(
            ['ID', 'ClassName', 'LastEdited', 'Created', 'MyField', 'MyInt'],
            $scaffolder->getFields()->column('Name')
        );

        $scaffolder = $this->getFakeScaffolder();
        $scaffolder->addAllFields(true);
        $this->assertEquals(
            ['ID', 'ClassName', 'LastEdited', 'Created', 'MyField', 'MyInt', 'Author'],
            $scaffolder->getFields()->column('Name')
        );

        $scaffolder = $this->getFakeScaffolder();
        $scaffolder->addAllFieldsExcept('MyInt');
        $this->assertEquals(
            ['ID', 'ClassName', 'LastEdited', 'Created', 'MyField'],
            $scaffolder->getFields()->column('Name')
        );

        $scaffolder = $this->getFakeScaffolder();
        $scaffolder->addAllFieldsExcept('MyInt', true);
        $this->assertEquals(
            ['ID', 'ClassName', 'LastEdited', 'Created', 'MyField', 'Author'],
            $scaffolder->getFields()->column('Name')
        );

        $scaffolder->removeField('ClassName');
        $this->assertEquals(
            ['ID', 'LastEdited', 'Created', 'MyField', 'Author'],
            $scaffolder->getFields()->column('Name')
        );
        $scaffolder->removeFields(['LastEdited', 'Created']);
        $this->assertEquals(
            ['ID', 'MyField', 'Author'],
            $scaffolder->getFields()->column('Name')
        );
    }

    public function testDataObjectScaffolderOperations()
    {
        $scaffolder = $this->getFakeScaffolder();
        $op = $scaffolder->operation(SchemaScaffolder::CREATE);

        $this->assertInstanceOf(OperationScaffolder::class, $op);

        // Ensure we get back the same reference
        $op->Test = true;
        $op = $scaffolder->operation(SchemaScaffolder::CREATE);
        $this->assertEquals(true, $op->Test);

        // Ensure duplicates aren't created
        $scaffolder->operation(SchemaScaffolder::DELETE);
        $this->assertEquals(2, $scaffolder->getOperations()->count());

        $scaffolder->removeOperation(SchemaScaffolder::DELETE);
        $this->assertEquals(1, $scaffolder->getOperations()->count());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/Invalid operation/');
        $scaffolder = $this->getFakeScaffolder();
        $scaffolder->operation('fail');
    }

    public function testDataObjectScaffolderNestedQueries()
    {
        $scaffolder = $this->getFakeScaffolder();
        $query = $scaffolder->nestedQuery('Files');

        $this->assertInstanceOf(QueryScaffolder::class, $query);

        // Ensure we get back the same reference
        $query->Test = true;
        $query = $scaffolder->nestedQuery('Files');
        $this->assertEquals(true, $query->Test);

        // Ensure duplicates aren't created
        $this->assertCount(1, $scaffolder->getNestedQueries());

        // Ensure nested queries are not added to manager
        $managerMock = $this->getMockBuilder(Manager::class)
            ->setMethods(['addQuery'])
            ->getMock();
        $managerMock
            ->expects($this->never())
            ->method('addQuery');
        $managerMock->addType(new ObjectType([
            'name' => 'File',
            'fields' => []
        ]));
        $scaffolder->addToManager($managerMock);

        // Can't add a nested query for a regular field
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/returns a DataList or ArrayList/');
        $scaffolder = $this->getFakeScaffolder();
        $scaffolder->nestedQuery('MyField');
    }

    public function testDataObjectScaffolderDependentClasses()
    {
        $scaffolder = $this->getFakeScaffolder();
        $this->assertEquals([], $scaffolder->getDependentClasses());
        $scaffolder->nestedQuery('Files');
        $this->assertEquals(
            [$scaffolder->getDataObjectInstance()->Files()->dataClass()],
            $scaffolder->getDependentClasses()
        );

        $scaffolder->addField('Author');
        $this->assertEquals(
            [
                get_class($scaffolder->getDataObjectInstance()->Author()),
                $scaffolder->getDataObjectInstance()->Files()->dataClass(),
            ],
            $scaffolder->getDependentClasses()
        );
    }

    public function testDataObjectScaffolderAncestralClasses()
    {
        $scaffolder = new DataObjectScaffolder(FakeRedirectorPage::class);
        $classes = $scaffolder->getAncestralClasses();

        $this->assertEquals([
            FakePage::class,
            FakeSiteTree::class,
        ], $classes);
    }

    public function testDataObjectScaffolderApplyConfig()
    {
        /** @var DataObjectScaffolder $observer */
        $observer = $this->getMockBuilder(DataObjectScaffolder::class)
            ->setConstructorArgs([DataObjectFake::class])
            ->setMethods(['addFields', 'removeFields', 'operation', 'nestedQuery', 'setFieldDescription'])
            ->getMock();

        $observer->expects($this->once())
            ->method('addFields')
            ->with($this->equalTo(['ID', 'MyField', 'MyInt']));

        $observer->expects($this->once())
            ->method('removeFields')
            ->with($this->equalTo(['ID']));

        $observer->expects($this->exactly(2))
            ->method('operation')
            ->withConsecutive(
                [$this->equalTo(SchemaScaffolder::CREATE)],
                [$this->equalTo(SchemaScaffolder::READ)]
            )
            ->will($this->returnValue(
                $this->getMockBuilder(Create::class)
                    ->disableOriginalConstructor()
                    ->getMock()
            ));

        $observer->expects($this->once())
            ->method('nestedQuery')
            ->with($this->equalTo('Files'))
            ->will($this->returnValue(
                $this->getMockBuilder(QueryScaffolder::class)
                    ->disableOriginalConstructor()
                    ->getMock()
            ));

        $observer->expects($this->once())
            ->method('setFieldDescription')
            ->with(
                $this->equalTo('MyField'),
                $this->equalTo('This is myfield')
            );

        $observer->applyConfig([
            'fields' => ['ID', 'MyField', 'MyInt'],
            'excludeFields' => ['ID'],
            'operations' => ['create' => true, 'read' => true],
            'nestedQueries' => ['Files' => true],
            'fieldDescriptions' => [
                'MyField' => 'This is myfield',
            ],
        ]);
    }

    public function testDataObjectScaffolderApplyConfigNoFieldsException()
    {
        $scaffolder = $this->getFakeScaffolder();

        // Must have "fields" defined
        $this->expectException(Exception::class);
        $this->expectExceptionMessageRegExp('/No array of fields/');
        $scaffolder->applyConfig([
            'operations' => ['create' => true],
        ]);
    }

    public function testDataObjectScaffolderApplyConfigInvalidFieldsException()
    {
        $scaffolder = $this->getFakeScaffolder();

        // Invalid fields
        $this->expectException(Exception::class);
        $this->expectExceptionMessageRegExp('/Fields must be an array/');
        $scaffolder->applyConfig([
            'fields' => 'fail',
        ]);
    }

    public function testDataObjectScaffolderApplyConfigInvalidFieldsExceptException()
    {
        $scaffolder = $this->getFakeScaffolder();

        // Invalid fieldsExcept
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/"excludeFields" must be an enumerated list/');
        $scaffolder->applyConfig([
            'fields' => ['MyField'],
            'excludeFields' => 'fail',
        ]);
    }

    public function testDataObjectScaffolderApplyConfigInvalidOperationsException()
    {
        $scaffolder = $this->getFakeScaffolder();

        // Invalid operations
        $this->expectException(Exception::class);
        $this->expectExceptionMessageRegExp('/Operations field must be a map/');
        $scaffolder->applyConfig([
            'fields' => ['MyField'],
            'operations' => ['create'],
        ]);
    }

    public function testDataObjectScaffolderApplyConfigInvalidNestedQueriesException()
    {
        $scaffolder = $this->getFakeScaffolder();

        // Invalid nested queries
        $this->expectException(Exception::class);
        $this->expectExceptionMessageRegExp('/"nestedQueries" must be a map of relation name/');
        $scaffolder->applyConfig([
            'fields' => ['MyField'],
            'nestedQueries' => ['Files'],
        ]);
    }

    public function testDataObjectScaffolderWildcards()
    {
        $scaffolder = $this->getFakeScaffolder();
        $scaffolder->applyConfig([
            'fields' => SchemaScaffolder::ALL,
            'operations' => SchemaScaffolder::ALL,
        ]);
        $ops = $scaffolder->getOperations();

        $this->assertInstanceOf(Create::class, $ops->findByIdentifier(SchemaScaffolder::CREATE));
        $this->assertInstanceOf(Delete::class, $ops->findByIdentifier(SchemaScaffolder::DELETE));
        $this->assertInstanceOf(Read::class, $ops->findByIdentifier(SchemaScaffolder::READ));
        $this->assertInstanceOf(Update::class, $ops->findByIdentifier(SchemaScaffolder::UPDATE));

        $this->assertEquals(
            ['ID', 'ClassName', 'LastEdited', 'Created', 'MyField', 'MyInt', 'Author'],
            $scaffolder->getFields()->column('Name')
        );
    }

    public function testDataObjectScaffolderScaffold()
    {
        $manager = $this->getMockBuilder(Manager::class)
            ->setMethods(['getType', 'hasType'])
            ->getMock();
        $manager->method('getType')
            ->will($this->returnValue([]));
        $manager->method('hasType')
            ->will($this->returnValue(true));

        $scaffolder = $this->getFakeScaffolder();
        $scaffolder->addFields(['MyField', 'Author'])
                   ->nestedQuery('Files');

        $objectType = $scaffolder->scaffold($manager);

        $this->assertInstanceof(ObjectType::class, $objectType);
        $config = $objectType->config;

        $this->assertEquals($scaffolder->getTypeName(), $config['name']);
        $this->assertEquals(['MyField', 'Author', 'Files'], array_keys($config['fields']()));
    }

    public function testDataObjectScaffolderScaffoldFieldException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/Invalid field/');
        $scaffolder = $this->getFakeScaffolder()
            ->addFields(['not a field'])
            ->scaffold(new Manager());
        $scaffolder->config['fields']();
    }

    public function testDataObjectScaffolderScaffoldNestedQueryException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/returns a list/');
        $scaffolder = $this->getFakeScaffolder()
            ->addFields(['Files'])
            ->scaffold(new Manager());
        $scaffolder->config['fields']();
    }

    public function testDataObjectScaffolderAddToManager()
    {
        $manager = new Manager();
        /** @var DataObjectScaffolder $scaffolder */
        $scaffolder = $this->getFakeScaffolder()
            ->addFields(['MyField'])
            ->operation(SchemaScaffolder::CREATE)
                ->setName('Create and Barrel')
                ->end()
            ->operation(SchemaScaffolder::READ)
                ->setName('Ready McRead')
                ->end();

        $scaffolder->addToManager($manager);

        $schema = $manager->schema();
        $queryConfig = $schema->getQueryType()->config;
        $mutationConfig = $schema->getMutationType()->config;

        $this->assertArrayHasKey(
            'Ready McRead',
            $queryConfig['fields']()
        );

        $this->assertArrayHasKey(
            'Create and Barrel',
            $mutationConfig['fields']()
        );

        $this->assertTrue($manager->hasType($scaffolder->getTypeName()));
    }

    public function testDataObjectScaffolderSimpleFieldTypes()
    {
        $scaffolder = $this->getFakeScaffolder();
        $scaffolder->addField('MyInt');
        $manager = new Manager();
        $result = $scaffolder->scaffold($manager);
        $fields = $result->config['fields']();
        $myIntResolver = $fields['MyInt']['resolve'];
        $fake = new DataObjectFake(['MyInt' => 5]);
        $value = $myIntResolver($fake, [], null, new ResolveInfo(['fieldName' => 'MyInt']));

        $this->assertEquals(5, $value);
        Config::modify()->merge(DBInt::class, 'graphql_type', [
            'FieldOne' => 'String',
            'FieldTwo' => 'Int',
        ]);
    }

    public function testDataObjectScaffolderComplexFieldTypes()
    {
        Config::modify()->merge(DBInt::class, 'graphql_type', [
            'FieldOne' => 'String',
            'FieldTwo' => 'Int',
        ]);
        $fake = new DataObjectFake(['MyInt' => 5]);
        $manager = new Manager();
        /** @var DBInt|TypeCreatorExtension $dbInt */
        $dbInt = new DBInt(0);
        $dbInt->addToManager($manager);

        $this->assertInstanceOf(ObjectType::class, $fake->obj('MyInt')->getGraphQLType($manager));
        $scaffolder = $this->getFakeScaffolder();
        $scaffolder->addField('MyInt');
        $result = $scaffolder->scaffold($manager);
        $fields = $result->config['fields']();
        $myIntResolver = $fields['MyInt']['resolve'];

        $value = $myIntResolver($fake, [], null, new ResolveInfo(['fieldName' => 'MyInt']));

        $this->assertInstanceOf(DBInt::class, $value);
    }

    public function testCloneTo()
    {
        $scaffolder = new DataObjectScaffolder(FakeRedirectorPage::class);
        $scaffolder->addFields(['Title', 'RedirectionType', 'ID']);
        $scaffolder->operation(SchemaScaffolder::READ);
        $scaffolder->operation(SchemaScaffolder::UPDATE);

        $target = new DataObjectScaffolder(FakeSiteTree::class);
        $this->assertNotContains('Title', $target->getFields()->column('Name'));
        $this->assertNotContains('RedirectionType', $target->getFields()->column('Name'));
        $this->assertEmpty($target->getOperations());

        $target = $scaffolder->cloneTo($target);

        $this->assertContains('Title', $target->getFields()->column('Name'));
        $this->assertNotContains('RedirectionType', $target->getFields()->column('Name'));
        $this->assertCount(2, $target->getOperations());
    }

    /**
     * @return DataObjectScaffolder
     */
    protected function getFakeScaffolder()
    {
        return new DataObjectScaffolder(DataObjectFake::class);
    }
}
