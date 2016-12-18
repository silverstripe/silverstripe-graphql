<?php

namespace SilverStripe\GraphQL;

use SilverStripe\Dev\SapphireTest;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\InputObjectType;
use SilverStripe\GraphQL\Scaffolding\Creators\DataObjectTypeCreator;
use SilverStripe\GraphQL\Manager;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\StringType;
use GraphQL\Type\Definition\IntType;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;
use SilverStripe\GraphQL\Scaffolding\Creators\MutationOperationCreator;
use SilverStripe\GraphQL\Scaffolding\Creators\QueryOperationCreator;
use SilverStripe\GraphQL\Scaffolding\Creators\PaginatedQueryOperationCreator;
use SilverStripe\GraphQL\Tests\Fake\FakeResolver;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\MutationScaffolder;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\OperationScaffolder;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\DataObjectScaffolder;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\GraphQLScaffolder;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\QueryScaffolder;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\CreateOperationScaffolder;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\UpdateOperationScaffolder;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\DeleteOperationScaffolder;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\ReadOperationScaffolder;
use Doctrine\Instantiator\Exception\InvalidArgumentException;
use SilverStripe\GraphQL\Scaffolding\OperationList;
use SilverStripe\Core\Config\Config;
use SilverStripe\Security\Member;
use SilverStripe\Assets\File;
use ReflectionClass;
use SilverStripe\GraphQL\Scaffolding\Util\ArgsParser;
use SilverStripe\GraphQL\Scaffolding\Util\TypeParser;
use SilverStripe\GraphQL\Pagination\Connection;

class ScaffoldingTest extends SapphireTest
{

    public function testDataObjectTypeCreator()
    {
        $creator = new DataObjectTypeCreator(new Manager, 'test', ['Foo' => 'Bar']);

        $this->assertEquals(['name' => 'test'], $creator->attributes());
        $this->assertEquals(['Foo' => 'Bar'], $creator->fields());

        $fake = new DataObjectFake([
            'MyField' => 'test'
        ]);
        $info = new ResolveInfo(['fieldName' => 'MyField']);

        $result = $creator->resolveField($fake, [], null, $info);

        $this->assertInstanceOf('SilverStripe\ORM\FieldType\DBVarchar', $result);
        $this->assertEquals('test', $result->getValue());
    }

    public function testMutationOperationCreator()
    {
        $managerMock = $this->getMockBuilder(Manager::class)
            ->setMethods(['getType'])
            ->getMock();
        $managerMock
            ->method('getType')
            ->will($this->returnArgument(0));

        $mutationCreator = new MutationOperationCreator($managerMock, 'testOperation', 'testType', 'testResolver');
        $result = $mutationCreator->type();

        $this->assertInstanceOf('\Closure', $result);
        $this->assertEquals('testType', $result());

    }

    public function testQueryOperationCreator()
    {
        $managerMock = $this->getMockBuilder(Manager::class)
            ->setMethods(['getType'])
            ->getMock();
        $managerMock
            ->method('getType')
            ->willReturn(new ObjectType(['name' => 'test']));

        $mutationCreator = new QueryOperationCreator($managerMock, 'testOperation', 'testType','testResolver');
        $result = $mutationCreator->type();

        $this->assertInstanceOf('\Closure', $result);
        $this->assertInstanceOf(ListOfType::class, $result());
        $this->assertInstanceOf(ObjectType::class, $result()->getWrappedType());
        $this->assertEquals('test', $result()->getWrappedType()->config['name']);

    }

    public function testOperationCreatorConstuctor()
    {
        $mutationCreator = $this->createOperationCreator();
        $this->assertEquals(['name' => 'testOperation'], $mutationCreator->attributes());
        $this->assertEquals(['foo' => 'bar'], $mutationCreator->args());

        $this->assertInstanceOf('\Closure', $mutationCreator->toArray()['resolve']);
    }

    public function testOperationCreatorAcceptsClosureAsResolver()
    {
        // test resolvers
        $mutationCreator = $this->createOperationCreator(function () {
            return 'abc';
        });
        $field = $mutationCreator->toArray();
        $this->assertEquals('abc', $field['resolve'](null, [], null, null));
    }

    public function testOperationCreatorAcceptsResolverInterfaceInstanceAsResolver()
    {
        $mutationCreator = $this->createOperationCreator(new FakeResolver());
        $field = $mutationCreator->toArray();
        $this->assertEquals('resolved', $field['resolve'](null, [], null, null));
    }

    public function testExceptionThrownOnBadResolver()
    {
        $this->setExpectedException(\Exception::class);
        $mutationCreator = $this->createOperationCreator(FakeResolver::class);
        $field = $mutationCreator->toArray();
        $field['resolve'](null, [], null, null);
    }

    public function testMutationOperationScaffolderCore()
    {
        $scaffolder = new MutationScaffolder(
            'testOperation',
            'testType'
        );

        $this->assertEquals('testOperation', $scaffolder->getName());

        $creator = $scaffolder->getCreator(new Manager());
        $this->assertInstanceOf(MutationOperationCreator::class, $creator);
        $this->assertEquals(['name' => 'testOperation'], $creator->attributes());
    }

    public function testQueryOperationScaffolderCore()
    {
        $scaffolder = new QueryScaffolder(
            'testOperation',
            'testType'
        );

        $this->assertEquals('testOperation', $scaffolder->getName());

        $creator = $scaffolder->getCreator(new Manager());
        $this->assertInstanceOf(PaginatedQueryOperationCreator::class, $creator);
        $this->assertEquals(['name' => 'testOperation'], $creator->attributes());

        $scaffolder->setUsePagination(false);
        $creator = $scaffolder->getCreator(new Manager());
        $this->assertInstanceOf(QueryOperationCreator::class, $creator);
		$this->assertEquals(['name' => 'testOperation'], $creator->attributes());        
    }

    public function testOperationScaffolderCreator()
    {
        $scaffolder = new MutationScaffolder(
            'testOperation',
            'testType'
        );

        $scaffolder->addArgs(['foo' => 'String']);
        $creator = $scaffolder->getCreator(new Manager());
        // only test the keys to isolate testing of ArgsParser
        $this->assertEquals(['foo'], array_keys($creator->args()));
        $scaffolder->addArgs(['qux' => 'Int']);
        $creator = $scaffolder->getCreator(new Manager());
        $this->assertEquals(['foo', 'qux'], array_keys($creator->args()));

        $scaffolder->setResolver(function () {
        });
    }

    public function testOperationScaffolderRejectsInvalidResolvers()
    {
        $this->setExpectedException(InvalidArgumentException::class);
        $scaffolder = new MutationScaffolder(
            'testOperation',
            'testType',
            'notAValidResover'
        );
    }

    public function testOperationScaffolderAcceptsValidResolvers()
    {
        $m = new Manager();
        $resolver = $this->getMockBuilder(FakeResolver::class)
            ->setMethods(['resolve'])
            ->getMock();
        $resolver->expects($this->once())
            ->method('resolve');


        $scaffolder = new MutationScaffolder(
            'testOperation',
            'testType',
            $resolver
        );

        $resolveMethod = $scaffolder->getCreator($m)->toArray()['resolve'];
        $resolveMethod(null, null, null, null);

        $scaffolder = new MutationScaffolder(
            'testOperation',
            'testType',
            function () {
            }
        );

        $this->assertInstanceOf('\Closure', $scaffolder->getCreator($m)->toArray()['resolve']);
    }


    public function testOperationScaffolderCreateFromConfig()
    {
        $exception = null;
        $mockManager = $this->getMockBuilder(Manager::class)
            ->setMethods(['getType'])
            ->getMock();
        $mockManager
            ->expects($this->once())
            ->method('getType')
            ->willReturn('abc');

        try {
            MutationScaffolder::createFromConfig('test', []);
        } catch (\Exception $e) {
            $exception = $e;
        }
        $this->assertInstanceOf('\Exception', $e);

        try {
            MutationScaffolder::createFromConfig('test', [
                'name' => 'testOperation',
                'resolver' => 'not a resolver'
            ]);
        } catch (\Exception $e) {
            $exception = $e;
        }

        $this->assertInstanceOf('\Exception', $e);

        $result = MutationScaffolder::createFromConfig('test', [
            'name' => 'testOperation',
            'resolver' => FakeResolver::class
        ]);

        $this->assertInstanceOf(MutationScaffolder::class, $result);

        $creator = $result->getCreator($mockManager);
        $typeCreator = $creator->type();
        $type = $typeCreator();
        $this->assertEquals('abc', $type);
    }

    public function testDataObjectScaffolderConstructor()
    {
        $this->setExpectedException(InvalidArgumentException::class);
        $scaffolder = new DataObjectScaffolder('Not\A\Real\Thing');
    }

    public function testDataObjectScaffolderQueriesAndMutations()
    {
        $scaffolder = new DataObjectScaffolder(DataObjectFake::class);

        $this->assertInstanceOf(OperationList::class, $scaffolder->getQueries());
        $this->assertInstanceOf(OperationList::class, $scaffolder->getMutations());

        $query = $scaffolder->query('test');

        $this->assertInstanceOf(QueryScaffolder::class, $query);
        $this->assertEquals('test', $query->getName());

        $mutation = $scaffolder->mutation('test');

        $this->assertInstanceOf(MutationScaffolder::class, $mutation);
        $this->assertEquals('test', $mutation->getName());

        $query2 = $scaffolder->query('test');
        $this->assertEquals(1, $scaffolder->getQueries()->count());

        $mutation2 = $scaffolder->mutation('test');
        $this->assertEquals(1, $scaffolder->getMutations()->count());

        $this->assertSame($query, $query2);
        $this->assertSame($mutation, $mutation2);

        $query3 = $scaffolder->query('abc');
        $mutation3 = $scaffolder->mutation('abc');

        $this->assertEquals(2, $scaffolder->getQueries()->count());
        $this->assertEquals(2, $scaffolder->getMutations()->count());

        $scaffolder->removeQuery('test');
        $scaffolder->removeMutation('test');

        $this->assertEquals(1, $scaffolder->getQueries()->count());
        $this->assertEquals(1, $scaffolder->getMutations()->count());

    }


    public function testDataObjectScaffolderThrowsIfNotDataObject()
    {
        $this->setExpectedException(InvalidArgumentException::class);
        $scaffolder = new DataObjectScaffolder(Config::class);
    }

    public function testStockOperations()
    {
        // Create
        $scaffolder = new DataObjectScaffolder(DataObjectFake::class);
        $scaffolder->mutation(GraphQLScaffolder::CREATE);
        $this->assertInstanceOf(CreateOperationScaffolder::class, $scaffolder->getMutations()->first());

        // Read
        $scaffolder = new DataObjectScaffolder(DataObjectFake::class);
        $scaffolder->query(GraphQLScaffolder::READ);
        $this->assertInstanceOf(ReadOperationScaffolder::class, $scaffolder->getQueries()->first());

        // Update
        $scaffolder = new DataObjectScaffolder(DataObjectFake::class);
        $scaffolder->mutation(GraphQLScaffolder::UPDATE);
        $this->assertInstanceOf(UpdateOperationScaffolder::class, $scaffolder->getMutations()->first());

        // Delete
        $scaffolder = new DataObjectScaffolder(DataObjectFake::class);
        $scaffolder->mutation(GraphQLScaffolder::DELETE);
        $this->assertInstanceOf(DeleteOperationScaffolder::class, $scaffolder->getMutations()->first());


    }

    public function testDataObjectScaffolderFields()
    {
        $scaffolder = new DataObjectScaffolder(DataObjectFake::class);
        $scaffolder->addFields(['One', 'Two']);

        $this->assertEquals(['One', 'Two'], $scaffolder->getFields()->toArray());

        $scaffolder->addFields(['One', 'Two', 'Three']);

        $this->assertCount(3, $scaffolder->getFields());
        $this->assertEquals(['One', 'Two', 'Three'], $scaffolder->getFields()->toArray());

        $scaffolder->removeField('Nothing');
        $this->assertCount(3, $scaffolder->getFields());
        $scaffolder->removeField('One');
        $this->assertCount(2, $scaffolder->getFields());

        $scaffolder->removeFields(['Two', 'Three']);
        $this->assertCount(0, $scaffolder->getFields());
    }

    public function testDataObjectScaffolderUsesDefaultFields()
    {
    	Config::inst()->update(DataObjectScaffolder::class, 'default_fields', ['ID' => 'ID']);
    	$scaffolder = new DataObjectScaffolder(DataObjectFake::class);
    	$creator = $scaffolder->getCreator(new Manager());
    	
    	$this->assertEquals(
    		['ID'],
    		array_keys($creator->fields())
    	);
    }

    public function testDataObjectTrait()
    {
        Config::inst()->update(DataObjectFake::class, 'table_name', 'Some\Namespaced\Table');
        $scaffolder = new DataObjectScaffolder(DataObjectFake::class);
        $inst = $scaffolder->getDataObjectInstance();
        $this->assertInstanceOf(DataObjectFake::class, $inst);

        $this->assertEquals('Some_Namespaced_Table', $scaffolder->typeName());

        $scaffolder->setDataObjectClass('test');
        $this->assertEquals('test', $scaffolder->getDataObjectClass());
    }

    public function testDataObjectScaffolderCreator()
    {
        $scaffolder = (new DataObjectScaffolder(DataObjectFake::class))
            ->addFields(['MyField', 'NonExistentField', 'Author', 'Files']);

        $extraDataObjects = $scaffolder->getExtraDataObjects();
        $this->assertArrayHasKey('Author', $extraDataObjects);
        $this->assertArrayHasKey('Files', $extraDataObjects);

        $this->assertEquals($extraDataObjects['Author'], 'SilverStripe\Security\Member');
        $this->assertEquals($extraDataObjects['Files'], 'SilverStripe\Assets\File');

        $creator = $scaffolder->getCreator(new Manager());

        $this->assertEquals($scaffolder->typeName(), $creator->attributes()['name']);
        $fields = array_keys($creator->fields());
        $this->assertEquals(['MyField', 'NonExistentField', 'Author', 'Files'], $fields);
    }


    public function testDataObjectScaffolderAddToManager()
    {
        $scaffolder = new DataObjectScaffolder(DataObjectFake::class);
        $scaffolder->addFields(['MyField', 'NonExistentField', 'Author', 'Files']);
        $scaffolder->mutation('myMutation', new FakeResolver());
        $scaffolder->query('myQuery', new FakeResolver());

        $managerMock = $this->getMockBuilder(Manager::class)
            ->setMethods(['getType', 'addType', 'addQuery', 'addMutation'])
            ->getMock();
        $managerMock->expects($this->once())
            ->method('addType')
            ->with($scaffolder->typeName());
        $managerMock->expects($this->once())
            ->method('addMutation')
            ->with(
                $this->callback(function ($subject) {                	
                    return (
                        is_array($subject) && $subject['name'] == 'myMutation'
                    );
                }),
                $this->equalTo('myMutation')
            );

        $managerMock->expects($this->once())
            ->method('addQuery')
            ->with(
                $this->callback(function ($subject) {                
                    return (
                        is_array($subject) && $subject['name'] == 'myQuery'
                    );
                }),
                $this->equalTo('myQuery')
            );

        $scaffolder->addToManager($managerMock);
    }

    public function testGraphQLScaffolderAddToManager()
    {
        $resolver = new FakeResolver();
        $scaffolder = new GraphQLScaffolder();
        $scaffolder->dataObject(DataObjectFake::class)
            ->addFields(['MyField', 'MyInt', 'Author', 'Files'])
            ->mutation('testMutation', $resolver)
            ->addArgs(['Test' => 'String']);
        $scaffolder->dataObject(DataObjectFake::class)
            ->query('testQuery', $resolver)
            ->addArgs(['Test' => 'String']);
        $scaffolder->dataObject(DataObjectFake::class)
            ->query('testQueryUnpaginated', $resolver)
            ->addArgs(['Test' => 'String'])
            ->setUsePagination(false);
        $scaffolder->dataObject(Member::class)
            ->addFields(['Surname']);
        $scaffolder->dataObject(File::class)
            ->addFields(['Filename']);
        $managerMock = $this->getMockBuilder(Manager::class)
            ->setMethods(['addType', 'addQuery', 'addMutation'])
            ->getMock();

        $doTypeName = $scaffolder->dataObject(DataObjectFake::class)->typeName();
        $memberTypeName = $scaffolder->dataObject(Member::class)->typeName();
        $fileTypeName = $scaffolder->dataObject(File::class)->typeName();

        $createFieldCheckCallback = function ($fields, $typeName) {
            return function ($subject) use ($fields, $typeName) {
                if ($subject instanceof ObjectType) {
                    $fieldFunc = $subject->config['fields'];
                    $typeFields = $fieldFunc();
                    return (
                        ($subject->config['name'] == $typeName) &&
                        (array_keys($typeFields) == $fields)
                    );
                }

                return false;
            };
        };

        $managerMock->expects($this->exactly(3))
            ->method('addType')
            ->withConsecutive(
                [
                    $this->callback($createFieldCheckCallback(
                        ['MyField', 'MyInt', 'Author', 'Files'],
                        $doTypeName
                    )),
                    $doTypeName
                ],
                [
                    $this->callback($createFieldCheckCallback(['Surname'], $memberTypeName)),
                    $memberTypeName
                ],
                [
                    $this->callback($createFieldCheckCallback(['Filename'], $fileTypeName)),
                    $fileTypeName
                ]

            );

        $managerMock->expects($this->exactly(2))
            ->method('addQuery')
            ->withConsecutive(
            	[
            		$this->callback(function ($subject) {
            			$connectionKeys = array_keys(Connection::create('dummy')->args());
                		return (
                			is_array($subject) &&
                			$subject['name'] == 'testQuery' &&
                			array_keys($subject['args']) == array_merge(['Test'], $connectionKeys)
                		);
            		})
            	],
            	[
            		$this->callback(function ($subject) {
                		return (
                			is_array($subject) &&
                			$subject['name'] == 'testQueryUnpaginated' &&
                			array_keys($subject['args']) == ['Test']
                		);
            		})            	
            	]
            );

        $managerMock->expects($this->once())
            ->method('addMutation')
            ->with($this->callback(function ($subject) {
                return is_array($subject) &&
                $subject['name'] == 'testMutation' &&
                array_keys($subject['args']) == ['Test'];
            }));

        $scaffolder->addToManager($managerMock);
    }

    public function testGraphQLScaffolderCreateFromConfig()
    {
        $scaffolder = GraphQLScaffolder::createFromConfig([
            DataObjectFake::class => [
                'fields' => [
                    'MyField',
                    'MyInt',
                    'Author',
                    'Files'
                ],
                'operations' => ['CREATE', 'READ', 'UPDATE', 'DELETE']
            ],

            Member::class => [
                'fields' => ['Surname'],
                'operations' => 'all',
                'queries' => [
                    'myQuery' => [
                        'args' => [
                            'Test' => 'String'
                        ],
                        'resolver' => FakeResolver::class
                    ],
                    'myQueryUnpaginated' => [
                    	'args' => [
                    		'Test' => 'String'
                    	],
                    	'resolver' => FakeResolver::class
                    ]
                ]
            ],

            File::class => [
                'fields' => ['Surname'],
                'mutations' => [
                    'myMutation' => [
                        'args' => [
                            'Test' => 'String'
                        ],
                        'resolver' => FakeResolver::class
                    ]
                ]
            ]
        ]);

        $reflection = new ReflectionClass($scaffolder);
        $property = $reflection->getProperty('scaffolds');
        $property->setAccessible(true);
        $scaffolds = $property->getValue($scaffolder);

        $dataObjectClasses = [
            DataObjectFake::class => true,
            Member::class => true,
            File::class => true
        ];

        foreach ($scaffolds as $scaffold) {
            $name = $scaffold->getDataObjectClass();
            $typeName = ucfirst($scaffold->typeName());
            $pluralTypeName = $scaffold->getDataObjectInstance()->plural_name();
            $pluralTypeName = str_replace(' ', '', $pluralTypeName);
            $pluralTypeName = ucfirst($pluralTypeName);

            $this->assertArrayHasKey($name, $dataObjectClasses);
            unset($dataObjectClasses[$name]);
            
            if (in_array($name, [DataObjectFake::class, Member::class])) {
                $this->assertInstanceOf(
                    CreateOperationScaffolder::class,
                    $scaffold->getMutations()->findByName('create' . $typeName)
                );
                $this->assertInstanceOf(
                    UpdateOperationScaffolder::class,
                    $scaffold->getMutations()->findByName('update' . $typeName)
                );
                $this->assertInstanceOf(
                    DeleteOperationScaffolder::class,
                    $scaffold->getMutations()->findByName('delete' . $typeName)
                );
                $this->assertInstanceOf(
                    ReadOperationScaffolder::class,
                    $scaffold->getQueries()->findByName('read' . $pluralTypeName)
                );
                if ($name == Member::class) {
                    $op = $scaffold->getQueries()->findByName('myQuery');
                    $this->assertInstanceOf(
                        QueryScaffolder::class,
                        $op
                    );
                    $args = $op->getCreator(new Manager())->args();
                    foreach(Connection::create('dummy')->args() as $a => $config) {
                    	$this->assertArrayHasKey($a, $args);
                    }
                    $this->assertArrayHasKey('Test', $args);

                    $op = $scaffold->getQueries()->findByName('myQueryUnpaginated');
                    $this->assertInstanceOf(
                        QueryScaffolder::class,
                        $op
                    );
                    $this->assertArrayHasKey('Test', $op->getCreator(new Manager())->args());

                }

            } else {
                if ($name == File::class) {
                    $op = $scaffold->getMutations()->findByName('myMutation');
                    $this->assertInstanceOf(
                        MutationScaffolder::class,
                        $op
                    );
                    $this->assertArrayHasKey('Test', $op->getCreator(new Manager())->args());
                }
            }
        }
    }

    public function testInputTypesAreCreated()
    {
        $create = (new CreateOperationScaffolder(DataObjectFake::class))
            ->addArgs(['Test' => 'String']);
        $creator = $create->getCreator(new Manager());

        $this->assertArrayHasKey('Input', $creator->args());
        $type = $creator->args()['Input']['type'];
        $this->assertInstanceOf(NonNull::class, $type);
        $this->assertInstanceOf(InputObjectType::class, $type->getWrappedType());

        $create = (new UpdateOperationScaffolder(DataObjectFake::class))
            ->addArgs(['Test' => 'String']);
        $creator = $create->getCreator(new Manager());

        $this->assertArrayHasKey('Input', $creator->args());
        $this->assertArrayHasKey('ID', $creator->args());
        $type = $creator->args()['Input']['type'];
        $this->assertInstanceOf(NonNull::class, $type);
        $this->assertInstanceOf(InputObjectType::class, $type->getWrappedType());
    }

    // public function testTypeParser()
    // {
    //     $parser = new TypeParser('String!=Test');
    //     $this->assertTrue($parser->isRequired());
    //     $this->assertEquals('String', $parser->getArgTypeName());
    //     $this->assertEquals('Test', $parser->getDefaultValue());

    //     $parser = new TypeParser('String! = Test');
    //     $this->assertTrue($parser->isRequired());
    //     $this->assertEquals('String', $parser->getArgTypeName());
    //     $this->assertEquals('Test', $parser->getDefaultValue());

    //     $parser = new TypeParser('Int!');
    //     $this->assertTrue($parser->isRequired());
    //     $this->assertEquals('Int', $parser->getArgTypeName());
    //     $this->assertNull($parser->getDefaultValue());

    //     $parser = new TypeParser('Boolean');
    //     $this->assertFalse($parser->isRequired());
    //     $this->assertEquals('Boolean', $parser->getArgTypeName());
    //     $this->assertNull($parser->getDefaultValue());

    //     $parser = new TypeParser('String!=Test');
    //     $arr = $parser->toArray();
    //     $this->assertInstanceOf(NonNull::class, $arr['type']);
    //     $this->assertInstanceOf(StringType::class, $arr['type']->getWrappedType());
    //     $this->assertEquals('Test', $arr['defaultValue']);

    //     $this->setExpectedException(InvalidArgumentException::class);
    //     $parser = new TypeParser('  ... Nothing');

    //     $this->setExpectedException(InvalidArgumentException::class);
    //     $parser = (new TypeParser('Nothing'))->toArray();
    // }

    // public function testArgsParser()
    // {
    //     $parsers = [
    //         new ArgsParser([
    //             'Test' => 'String'
    //         ]),
    //         new ArgsParser([
    //             'Test' => Type::string()
    //         ]),
    //         new ArgsParser([
    //             'Test' => ['type' => Type::string()]
    //         ])
    //     ];

    //     foreach ($parsers as $parser) {
    //         $arr = $parser->toArray();
    //         $this->assertArrayHasKey('Test', $arr);
    //         $this->assertArrayHasKey('type', $arr['Test']);
    //         $this->assertInstanceOf(StringType::class, $arr['Test']['type']);
    //     }
    // }


    // public function testOperationList()
    // {
    //     $list = new OperationList();

    //     $list->push(new MutationScaffolder('myMutation1', 'test1'));
    //     $list->push(new MutationScaffolder('myMutation2', 'test2'));

    //     $this->assertInstanceOf(
    //         MutationScaffolder::class,
    //         $list->findByName('myMutation1')
    //     );
    //     $this->assertFalse($list->findByName('myMutation3'));

    //     $list->removeByName('myMutation2');
    //     $this->assertEquals(1, $list->count());

    //     $list->removeByName('nothing');
    //     $this->assertEquals(1, $list->count());

    //     $this->setExpectedException(InvalidArgumentException::class);
    //     $list->push(new OperationList());
    // }

    protected function createOperationCreator($resolver = null)
    {
        return new MutationOperationCreator(
            new Manager(),
            'testOperation',
            'testType',
            $resolver,
            ['foo' => 'bar']
        );
    }

}
