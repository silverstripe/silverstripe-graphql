<?php

namespace SilverStripe\GraphQL;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\DataObjectScaffolder;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;
use SilverStripe\GraphQL\Tests\Fake\OperationScaffolderFake;
use SilverStripe\GraphQL\Tests\Fake\FakeResolver;
use Doctrine\Instantiator\Exception\InvalidArgumentException;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\SchemaScaffolder;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\OperationScaffolder;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\QueryScaffolder;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\MutationScaffolder;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\Create;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\Read;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\Update;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CRUD\Delete;
use SilverStripe\GraphQL\Scaffolding\Interfaces\CRUDInterface;
use SilverStripe\GraphQL\Scaffolding\Util\ArgsParser;
use SilverStripe\GraphQL\Scaffolding\Util\TypeParser;
use SilverStripe\GraphQL\Scaffolding\Util\OperationList;
use SilverStripe\GraphQL\Manager;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\StringType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\Type;
use SilverStripe\CMS\Model\RedirectorPage;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Security\Member;
use SilverStripe\Assets\File;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use Exception;
use Page;

class ScaffoldingTest extends SapphireTest
{

	public function testDataObjectScaffolderConstructor()
	{
		$scaffolder = $this->getFakeScaffolder();
		$this->assertEquals(DataObjectFake::class, $scaffolder->getDataObjectClass());
		$this->assertInstanceOf(DataObjectFake::class, $scaffolder->getDataObjectInstance());

		$this->setExpectedExceptionRegExp(
			InvalidArgumentException::class,
			'/non-existent classname/'
		);
		$scaffolder = new DataObjectScaffolder('fail');
	}

	public function testDataObjectScaffolderFields()
	{
		$scaffolder = $this->getFakeScaffolder();
		
		$scaffolder->addField('MyField');
		$this->assertEquals(['MyField'], $scaffolder->getFields()->toArray());
		
		$scaffolder->addFields(['MyField','MyInt']);
		$this->assertEquals(['MyField', 'MyInt'], $scaffolder->getFields()->toArray());

		$scaffolder = $this->getFakeScaffolder();
		$scaffolder->addAllFields();
		$this->assertEquals(['ID','ClassName','LastEdited','Created','MyField','MyInt'], $scaffolder->getFields()->toArray());

		$scaffolder = $this->getFakeScaffolder();
		$scaffolder->addAllFields(true);
		$this->assertEquals(['ID','ClassName','LastEdited','Created','MyField','MyInt', 'Author'], $scaffolder->getFields()->toArray());

		$scaffolder = $this->getFakeScaffolder();
		$scaffolder->addAllFieldsExcept('MyInt');
		$this->assertEquals(['ID','ClassName','LastEdited','Created','MyField'], $scaffolder->getFields()->toArray());

		$scaffolder = $this->getFakeScaffolder();
		$scaffolder->addAllFieldsExcept('MyInt', true);
		$this->assertEquals(['ID','ClassName','LastEdited','Created','MyField','Author'], $scaffolder->getFields()->toArray());

		$scaffolder->removeField('ClassName');
		$this->assertEquals(['ID','LastEdited','Created','MyField','Author'], $scaffolder->getFields()->toArray());
		$scaffolder->removeFields(['LastEdited','Created']);
		$this->assertEquals(['ID','MyField','Author'], $scaffolder->getFields()->toArray());
	}


	public function testDataObjectScaffolderOperations()
	{
		$scaffolder = $this->getFakeScaffolder();
		$op = $scaffolder->operation(SchemaScaffolder::CREATE);

		$this->assertInstanceOf(CRUDInterface::class, $op);

		// Ensure we get back the same reference
		$op->Test = true;
		$op = $scaffolder->operation(SchemaScaffolder::CREATE);
		$this->assertEquals(true, $op->Test);

		// Ensure duplicates aren't created
		$scaffolder->operation(SchemaScaffolder::DELETE);
		$this->assertEquals(2, $scaffolder->getOperations()->count());

		$scaffolder->removeOperation(SchemaScaffolder::DELETE);
		$this->assertEquals(1, $scaffolder->getOperations()->count());

		$this->setExpectedExceptionRegExp(
			InvalidArgumentException::class,
			'/Invalid operation/'
		);
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
		$this->assertEquals(1, $scaffolder->getNestedQueries()->count());

		$this->setExpectedExceptionRegExp(
			InvalidArgumentException::class,
			'/returns a DataList or ArrayList/'
		);
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
				$scaffolder->getDataObjectInstance()->Author()->class,
				$scaffolder->getDataObjectInstance()->Files()->dataClass()
			],
			$scaffolder->getDependentClasses()
		);
	}

	public function testDataObjectScaffolderAncestralClasses()
	{
		$scaffolder = new DataObjectScaffolder(RedirectorPage::class);
		$classes = $scaffolder->getAncestralClasses();

		$this->assertEquals([
			'Page','SilverStripe\CMS\Model\SiteTree'
		], $classes);

	}

	public function testDataObjectScaffolderApplyConfig()
	{
		$observer = $this->getMockBuilder(DataObjectScaffolder::class)
			->setConstructorArgs([DataObjectFake::class])
			->setMethods(['addFields','addAllFieldsExcept','operation','nestedQuery'])
			->getMock();

		$observer->expects($this->once())
			->method('addFields')
			->with($this->equalTo(['MyField']));

		$observer->expects($this->once())
			->method('addAllFieldsExcept')
			->with($this->equalTo(['MyField']));

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


		$observer->applyConfig([
			'fields' => ['MyField'],
			'fieldsExcept' => ['MyField'],
			'operations' => ['create' => true,'read' => true],
			'nestedQueries' => ['Files' => true]
		]);

	}

	public function testDataObjectScaffolderApplyConfigNoFieldsException()
	{
		$scaffolder = $this->getFakeScaffolder();

		// Must have "fields" defined
		$this->setExpectedExceptionRegExp(
			Exception::class,
			'/No array of fields/'
		);
		$scaffolder->applyConfig([
			'operations' => ['create' => true]
		]);
	}

	public function testDataObjectScaffolderApplyConfigInvalidFieldsException()
	{
		$scaffolder = $this->getFakeScaffolder();

		// Invalid fields
		$this->setExpectedExceptionRegExp(
			Exception::class,
			'/Fields must be an array/'
		);
		$scaffolder->applyConfig([
			'fields' => 'fail'
		]);
	}

	public function testDataObjectScaffolderApplyConfigInvalidFieldsExceptException()
	{
		$scaffolder = $this->getFakeScaffolder();

		// Invalid fieldsExcept
		$this->setExpectedExceptionRegExp(
			InvalidArgumentException::class,
			'/"fieldsExcept" must be an array/'
		);
		$scaffolder->applyConfig([
			'fieldsExcept' => 'fail'
		]);
	}

	public function testDataObjectScaffolderApplyConfigInvalidOperationsException()
	{
		$scaffolder = $this->getFakeScaffolder();

		// Invalid operations
		$this->setExpectedExceptionRegExp(
			Exception::class,
			'/Operations field must be a map/'
		);
		$scaffolder->applyConfig([
			'fieldsExcept' => ['MyField'],
			'operations' => ['create']
		]);
	}

	public function testDataObjectScaffolderApplyConfigInvalidNestedQueriesException()
	{
		$scaffolder = $this->getFakeScaffolder();

		// Invalid nested queries
		$this->setExpectedExceptionRegExp(
			Exception::class,
			'/"nestedQueries" must be a map of relation name/'
		);
		$scaffolder->applyConfig([
			'fields' => ['MyField'],
			'nestedQueries' => ['Files']
		]);
	}



	public function testDataObjectScaffolderWildcards()
	{
		$scaffolder = $this->getFakeScaffolder();
		$scaffolder->applyConfig([
			'fields' => '*',
			'operations' => '*'
		]);
		$ops = $scaffolder->getOperations();

		$this->assertInstanceOf(Create::class, $ops->findByIdentifier(SchemaScaffolder::CREATE));
		$this->assertInstanceOf(Delete::class, $ops->findByIdentifier(SchemaScaffolder::DELETE));
		$this->assertInstanceOf(Read::class, $ops->findByIdentifier(SchemaScaffolder::READ));
		$this->assertInstanceOf(Update::class, $ops->findByIdentifier(SchemaScaffolder::UPDATE));

		$this->assertEquals(
			['ID','ClassName','LastEdited','Created','MyField','MyInt'],
			$scaffolder->getFields()->toArray()
		);
	}

	public function testDataObjectScaffolderScaffold()
	{
		$scaffolder = $this->getFakeScaffolder();
		$scaffolder->addFields(['MyField', 'Author'])
				   ->nestedQuery('Files');

		$objectType = $scaffolder->scaffold(new Manager());

		$this->assertInstanceof(ObjectType::class, $objectType);
		$config = $objectType->config;

		$this->assertEquals($scaffolder->typeName(), $config['name']);
		$this->assertEquals(['MyField', 'Author', 'Files'], array_keys($config['fields']));
	}

	public function testDataObjectScaffolderScaffoldFieldException()
	{
		$this->setExpectedExceptionRegExp(
			InvalidArgumentException::class,
			'/Invalid field/'
		);
		$scaffolder = $this->getFakeScaffolder()
			->addFields(['not a field'])
			->scaffold(new Manager());		
	}

	public function testDataObjectScaffolderScaffoldNestedQueryException()
	{
		$this->setExpectedExceptionRegExp(
			InvalidArgumentException::class,
			'/returns a list/'
		);
		$scaffolder = $this->getFakeScaffolder()
			->addFields(['Files'])
			->scaffold(new Manager());
	}

	public function testDataObjectScaffolderAddToManager()
	{
		$manager = new Manager();
		$scaffolder = $this->getFakeScaffolder()
			->addFields(['MyField'])
			->operation(SchemaScaffolder::CREATE)
				->end()
			->operation(SchemaScaffolder::READ)
				->end();

		$scaffolder->addToManager($manager);

		$schema = $manager->schema();
		$queryConfig = $schema->getQueryType()->config;
		$mutationConfig = $schema->getMutationType()->config;
		$types = $schema->getTypeMap();

		$this->assertArrayHasKey(
			(new Read(DataObjectFake::class))->getName(),
			$queryConfig['fields']
		);

		$this->assertArrayHasKey(
			(new Create(DataObjectFake::class))->getName(),
			$mutationConfig['fields']
		);

		$this->assertTrue($manager->hasType($scaffolder->typeName()));

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
		Config::inst()->update('Page','db', [
			'TestPageField' => 'Varchar'
		]);

		$manager = new Manager();
		$scaffolder = (new SchemaScaffolder())
			->type(RedirectorPage::class)
				->addFields(['Created','TestPageField', 'RedirectionType'])
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

		$classNames = array_map(function($scaffold) {
			return $scaffold->getDataObjectClass();
		}, $types);

		$this->assertEquals([
			RedirectorPage::class,			
			DataObjectFake::class,			
			Page::class,
			SiteTree::class,
			Member::class,
			File::class
		], $classNames);

		$this->assertEquals([
			'Created', 'TestPageField', 'RedirectionType'
		], $scaffolder->type(RedirectorPage::class)->getFields()->toArray());

		$this->assertEquals([
			'Created', 'TestPageField'
		], $scaffolder->type(Page::class)->getFields()->toArray());

		$this->assertEquals([
			'Created'
		], $scaffolder->type(SiteTree::class)->getFields()->toArray());


		$this->assertEquals('testQuery', $scaffolder->getQueries()->first()->getName());
		$this->assertEquals('testMutation', $scaffolder->getMutations()->first()->getName());

		$this->assertInstanceof(
			Read::class,			
			$scaffolder->type(RedirectorPage::class)->getOperations()->findByIdentifier(SchemaScaffolder::READ)
		);
		$this->assertInstanceof(
			Read::class,			
			$scaffolder->type(Page::class)->getOperations()->findByIdentifier(SchemaScaffolder::READ)
		);
		$this->assertInstanceof(
			Read::class,			
			$scaffolder->type(SiteTree::class)->getOperations()->findByIdentifier(SchemaScaffolder::READ)
		);

		$this->assertInstanceof(
			Create::class,			
			$scaffolder->type(RedirectorPage::class)->getOperations()->findByIdentifier(SchemaScaffolder::CREATE)
		);
		$this->assertInstanceof(
			Create::class,			
			$scaffolder->type(Page::class)->getOperations()->findByIdentifier(SchemaScaffolder::CREATE)
		);
		$this->assertInstanceof(
			Create::class,			
			$scaffolder->type(SiteTree::class)->getOperations()->findByIdentifier(SchemaScaffolder::CREATE)
		);
	}

	public function testSchemaScaffolderCreateFromConfigThrowsIfBadTypes()
	{
		$this->setExpectedExceptionRegExp(
			InvalidArgumentException::class,
			'/"types" must be a map of class name to settings/'
		);
		SchemaScaffolder::createFromConfig([
			'types' => ['fail']
		]);
	}

	public function testSchemaScaffolderCreateFromConfigThrowsIfBadQueries()
	{
		$this->setExpectedExceptionRegExp(
			InvalidArgumentException::class,
			'/must be a map of operation name to settings/'
		);
		SchemaScaffolder::createFromConfig([
			'types' => [
				DataObjectFake::class => [
					'fields' => '*'
				]
			],
			'queries' => ['fail']
		]);
	}

	public function testSchemaScaffolderCreateFromConfig()
	{
		$observer = $this->getMockBuilder(SchemaScaffolder::class)
			->setMethods(['query','mutation','type'])
			->getMock();

		$observer->expects($this->once())
			->method('query')
			->will($this->returnValue(new QueryScaffolder('test','test')));

		$observer->expects($this->once())
			->method('mutation')
			->will($this->returnValue(new MutationScaffolder('test','test')));

		$observer->expects($this->once())
			->method('type')
			->willReturn(
				new DataObjectScaffolder(DataObjectFake::class)
			);

		Injector::inst()->registerService($observer, SchemaScaffolder::class);

		SchemaScaffolder::createFromConfig([
			'types' => [
				DataObjectFake::class => [
					'fields' => ['MyField']
				]
			],
			'queries' => [
				'testQuery' => [
					'type' => DataObjectFake::class,
					'resolver' => FakeResolver::class
				]
			],
			'mutations' => [
				'testMutation' => [
					'type' => DataObjectFake::class,
					'resolver' => FakeResolver::class
				]
			]
		]);
	}

	public function testOperationIdentifiers()
	{
		$this->assertEquals(
			Read::class, 
			OperationScaffolder::getOperationScaffoldFromIdentifier(SchemaScaffolder::READ)
		);
		$this->assertEquals(
			Update::class, 
			OperationScaffolder::getOperationScaffoldFromIdentifier(SchemaScaffolder::UPDATE)
		);
		$this->assertEquals(
			Delete::class, 
			OperationScaffolder::getOperationScaffoldFromIdentifier(SchemaScaffolder::DELETE)
		);
		$this->assertEquals(
			Create::class, 
			OperationScaffolder::getOperationScaffoldFromIdentifier(SchemaScaffolder::CREATE)
		);

	}

	public function testOperationScaffolderArgs()
	{
		$scaffolder = new OperationScaffolderFake('testOperation','testType');
		
		$this->assertEquals('testOperation', $scaffolder->getName());
		$scaffolder->addArgs([
			'One' => 'String',
			'Two' => 'Boolean'
		]);
		$scaffolder->addArgs([
			'One' => 'String'
		]);

		$this->assertEquals(['One','Two'], array_keys($scaffolder->getArgs()));
	}

	public function testOperationScaffolderResolver()
	{
		$scaffolder = new OperationScaffolderFake('testOperation','testType');

		try {
			$scaffolder->setResolver(function() {});
			$scaffolder->setResolver(FakeResolver::class);
			$scaffolder->setResolver(new FakeResolver());
			$success = true;
		} catch (Exception $e) {
			$success = false;
		}

		$this->assertTrue($success);

		$this->setExpectedExceptionRegExp(
			InvalidArgumentException::class,
			'/closures, instances of/'
		);
		$scaffolder->setResolver('fail');
	}

	public function testOperationScaffolderAppliesConfig()
	{
		$scaffolder = new OperationScaffolderFake('testOperation','testType');
		
		try {
			$scaffolder->applyConfig([
				'args' => [
					'One' => 'String',
					'Two' => 'Boolean'
				],
				'resolver' => FakeResolver::class
			]);
			$success = true;
		} catch (Exception $e) {
			$success = false;
		}

		$this->assertTrue($success);
		$this->assertEquals(['One','Two'], array_keys($scaffolder->getArgs()));
	}

	public function testMutationScaffolder()
	{
		$observer = $this->getMockBuilder(Manager::class)
			->setMethods(['addMutation'])
			->getMock();
		$scaffolder = new MutationScaffolder('testMutation', 'test');
		$scaffolder->addArgs(['Test' => 'String']);
		$scaffold = $scaffolder->scaffold($manager = new Manager());
		$manager->addType($o = new ObjectType([
			'name' => 'test',
			'fields' => []
		]));
		$o->Test = true;

		$this->assertEquals('testMutation', $scaffold['name']);
		$this->assertArrayHasKey('Test', $scaffold['args']);
		$this->assertTrue(is_callable($scaffold['resolve']));
		$this->assertTrue($scaffold['type']()->Test);

		$observer->expects($this->once())
			->method('addMutation')
			->with(
				$this->equalTo($scaffold),
				$this->equalTo('testMutation')
			);

		$scaffolder->addToManager($observer);
	}

	public function testQueryScaffolderUnpaginated()
	{
		$observer = $this->getMockBuilder(Manager::class)
			->setMethods(['addQuery'])
			->getMock();
		$scaffolder = new QueryScaffolder('testQuery', 'test');
		$scaffolder->setUsePagination(false);
		$scaffolder->addArgs(['Test' => 'String']);
		$scaffold = $scaffolder->scaffold($manager = new Manager());
		
		$manager->addType($o = new ObjectType([
			'name' => 'test',
			'fields' => []
		]));
		$o->Test = true;

		$this->assertEquals('testQuery', $scaffold['name']);
		$this->assertArrayHasKey('Test', $scaffold['args']);
		$this->assertTrue(is_callable($scaffold['resolve']));
		$this->assertTrue($scaffold['type']()->Test);

		$observer->expects($this->once())
			->method('addQuery')
			->with(
				$this->equalTo($scaffold),
				$this->equalTo('testQuery')
			);

		$scaffolder->addToManager($observer);
	}

	public function testQueryScaffolderPaginated()
	{
		$scaffolder = new QueryScaffolder('testQuery', 'test');
		$scaffolder->setUsePagination(true);
		$scaffolder->addArgs(['Test' => 'String']);
		$scaffolder->addSortableFields(['test']);
		$scaffold = $scaffolder->scaffold($manager = new Manager());
		$manager->addType($o = new ObjectType([
			'name' => 'test',
			'fields' => []
		]));
		$o->Test = true;
		$config = $scaffold['type']()->config;

		$this->assertEquals('testQueryConnection', $config['name']);
		$this->assertArrayHasKey('pageInfo', $config['fields']);
		$this->assertArrayHasKey('edges', $config['fields']);
	}

	public function testQueryScaffolderApplyConfig()
	{
		$mock = $this->getMockBuilder(QueryScaffolder::class)
			->setConstructorArgs(['testQuery', 'testType'])
			->setMethods(['addSortableFields','setUsePagination'])
			->getMock();
		$mock->expects($this->once())
			->method('addSortableFields')
			->with(['Test1','Test2']);
		$mock->expects($this->once())
			->method('setUsePagination')
			->with(false);

		$mock->applyConfig([
			'sortableFields' => ['Test1','Test2'],
			'paginate' => false
		]);
	}

	public function testQueryScaffolderApplyConfigThrowsOnBadSortableFields()
	{
		$scaffolder = new QueryScaffolder('testQuery','testType');
		$this->setExpectedExceptionRegExp(
			InvalidArgumentException::class,
			'/sortableFields must be an array/'
		);
		$scaffolder->applyConfig([
			'sortableFields' => 'fail'			
		]);
	}

    public function testTypeParser()
    {
        $parser = new TypeParser('String!=Test');
        $this->assertTrue($parser->isRequired());
        $this->assertEquals('String', $parser->getArgTypeName());
        $this->assertEquals('Test', $parser->getDefaultValue());
        $this->assertTrue(is_string($parser->toArray()['defaultValue']));

        $parser = new TypeParser('String! = Test');
        $this->assertTrue($parser->isRequired());
        $this->assertEquals('String', $parser->getArgTypeName());
        $this->assertEquals('Test', $parser->getDefaultValue());

        $parser = new TypeParser('Int!');
        $this->assertTrue($parser->isRequired());
        $this->assertEquals('Int', $parser->getArgTypeName());
        $this->assertNull($parser->getDefaultValue());

        $parser = new TypeParser('Int!=23');
        $this->assertTrue($parser->isRequired());
        $this->assertEquals('Int', $parser->getArgTypeName());
        $this->assertEquals('23', $parser->getDefaultValue());
		$this->assertTrue(is_int($parser->toArray()['defaultValue']));

        $parser = new TypeParser('Boolean');
        $this->assertFalse($parser->isRequired());
        $this->assertEquals('Boolean', $parser->getArgTypeName());
        $this->assertNull($parser->getDefaultValue());

        $parser = new TypeParser('Boolean=1');
        $this->assertFalse($parser->isRequired());
        $this->assertEquals('Boolean', $parser->getArgTypeName());
        $this->assertEquals('1', $parser->getDefaultValue());
		$this->assertTrue(is_bool($parser->toArray()['defaultValue']));

        $parser = new TypeParser('String!=Test');
        $arr = $parser->toArray();
        $this->assertInstanceOf(NonNull::class, $arr['type']);
        $this->assertInstanceOf(StringType::class, $arr['type']->getWrappedType());
        $this->assertEquals('Test', $arr['defaultValue']);

        $this->setExpectedException(InvalidArgumentException::class);
        $parser = new TypeParser('  ... Nothing');

        $this->setExpectedException(InvalidArgumentException::class);
        $parser = (new TypeParser('Nothing'))->toArray();
    }

    public function testArgsParser()
    {
        $parsers = [
            new ArgsParser([
                'Test' => 'String'
            ]),
            new ArgsParser([
                'Test' => Type::string()
            ]),
            new ArgsParser([
                'Test' => ['type' => Type::string()]
            ])
        ];

        foreach ($parsers as $parser) {
            $arr = $parser->toArray();
            $this->assertArrayHasKey('Test', $arr);
            $this->assertArrayHasKey('type', $arr['Test']);
            $this->assertInstanceOf(StringType::class, $arr['Test']['type']);
        }
    }


    public function testOperationList()
    {
        $list = new OperationList();

        $list->push(new MutationScaffolder('myMutation1', 'test1'));
        $list->push(new MutationScaffolder('myMutation2', 'test2'));

        $this->assertInstanceOf(
            MutationScaffolder::class,
            $list->findByName('myMutation1')
        );
        $this->assertFalse($list->findByName('myMutation3'));

        $list->removeByName('myMutation2');
        $this->assertEquals(1, $list->count());

        $list->removeByName('nothing');
        $this->assertEquals(1, $list->count());

        $this->setExpectedExceptionRegExp(
        	InvalidArgumentException::class,
        	'/only accepts instances of/'
        );
        $list->push(new OperationList());
    }

	protected function getFakeScaffolder()
	{
		return new DataObjectScaffolder(DataObjectFake::class);
	}


}
