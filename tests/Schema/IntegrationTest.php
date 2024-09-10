<?php


namespace SilverStripe\GraphQL\Tests\Schema;

use GraphQL\Type\Definition\ObjectType;
use SilverStripe\Assets\File;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Path;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\QueryHandler\QueryHandler;
use SilverStripe\GraphQL\QueryHandler\SchemaConfigProvider;
use SilverStripe\GraphQL\Schema\Exception\EmptySchemaException;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Exception\SchemaNotFoundException;
use SilverStripe\GraphQL\Schema\Field\Query;
use SilverStripe\GraphQL\Schema\Logger;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\SchemaBuilder;
use SilverStripe\GraphQL\Schema\Storage\CodeGenerationStore;
use SilverStripe\GraphQL\Schema\Storage\CodeGenerationStoreCreator;
use SilverStripe\GraphQL\Schema\Storage\HashNameObfuscator;
use SilverStripe\GraphQL\Schema\Storage\NameObfuscator;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;
use SilverStripe\GraphQL\Tests\Fake\FakeDataObjectWithCanView;
use SilverStripe\GraphQL\Tests\Fake\FakePage;
use SilverStripe\GraphQL\Tests\Fake\FakeProduct;
use SilverStripe\GraphQL\Tests\Fake\FakeProductPage;
use SilverStripe\GraphQL\Tests\Fake\FakeRedirectorPage;
use SilverStripe\GraphQL\Tests\Fake\FakeReview;
use SilverStripe\GraphQL\Tests\Fake\FakeSiteTree;
use SilverStripe\GraphQL\Tests\Fake\Inheritance\A;
use SilverStripe\GraphQL\Tests\Fake\IntegrationTestResolver;
use SilverStripe\Security\Member;
use Symfony\Component\Filesystem\Filesystem;
use GraphQL\Type\Schema as GraphQLSchema;
use Exception;
use GraphQL\Error\Error as GraphQLError;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Validator\Rules\CustomValidationRule;
use GraphQL\Validator\Rules\QueryComplexity;
use GraphQL\Validator\Rules\QueryDepth;
use GraphQL\Validator\ValidationContext;
use ReflectionClass;

class IntegrationTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        FakePage::class,
        DataObjectFake::class,
        FakeSiteTree::class,
        FakeRedirectorPage::class,
        FakeProductPage::class,
        FakeProduct::class,
        FakeReview::class,
        Member::class,
        FakeDataObjectWithCanView::class,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        TestStoreCreator::$dir = __DIR__;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->clean();
        DataObjectFake::get()->removeAll();
        File::get()->removeAll();
        Member::get()->removeAll();
        FakeDataObjectWithCanView::get()->removeAll();
    }

    public function testSimpleType()
    {
        $factory = new TestSchemaBuilder(['_' . __FUNCTION__]);
        $factory->resolvers = [IntegrationTestResolver::class];
        $schema = $this->createSchema($factory);
        $query = <<<GRAPHQL
query {
    readMyTypes {
        field1
        field2
        field3
    }
}
GRAPHQL;

        $result = $this->querySchema($schema, $query);
        $this->assertSuccess($result);
        $records = $result['data']['readMyTypes'] ?? [];
        $this->assertCount(2, $records);
        $this->assertResults([
            ['field1' => 'foo', 'field2' => 2, 'field3' => 'no arg'],
            ['field1' => 'bar', 'field2' => 3, 'field3' => 'no arg'],
        ], $records);

        $query = <<<GRAPHQL
query {
    readMyTypes {
        field1
        field2
        field3(MyArg: "test")
    }
}
GRAPHQL;

        $result = $this->querySchema($schema, $query);
        $this->assertSuccess($result);
        $records = $result['data']['readMyTypes'] ?? [];
        $this->assertCount(2, $records);
        $this->assertResults([
            ['field1' => 'foo', 'field2' => 2, 'field3' => 'arg'],
            ['field1' => 'bar', 'field2' => 3, 'field3' => 'arg'],
        ], $records);
    }

    public function testSourceOverride()
    {
        $dirs = [
            '_' . __FUNCTION__ . '-a',
            '_' . __FUNCTION__ . '-b',
        ];
        // The second config (test2a) redefines the field types on the same MyType.
        $factory = new TestSchemaBuilder($dirs);
        $factory->resolvers = [IntegrationTestResolver::class];
        $schema = $this->createSchema($factory);
        $query = <<<GRAPHQL
query {
    readMyTypesAgain {
        field1
        field2
    }
}
GRAPHQL;

        $result = $this->querySchema($schema, $query);
        $this->assertSuccess($result);
        $records = $result['data']['readMyTypesAgain'] ?? [];
        $this->assertCount(2, $records);
        $this->assertResults([
            ['field1' => 'foo', 'field2' => true],
            ['field1' => 'bar', 'field2' => false],
        ], $records);
    }

    public function testModelConfig()
    {
        $schema = $this->createSchema(new TestSchemaBuilder(['_' . __FUNCTION__]));
        // Uses type_formatter with sttrev. See FakeFunctions::fakeFormatter
        $this->assertSchemaHasType($schema, 'TestekaFtcejbOataD');
    }

    public function testModelPlugins()
    {
        $testDir = '_' . __FUNCTION__;
        $schema = $this->createSchema($factory = new TestSchemaBuilder([$testDir]));
        $this->assertSchemaHasType($schema, 'FakePage');

        // disable versioning as a global plugin
        $factory->extraConfig = [
            'config' => [
                'modelConfig' => [
                    'DataObject' => [
                        'plugins' => [
                            'versioning' => false
                        ]
                    ]
                ]
            ]
        ];
        $schema = $this->createSchema($factory);
        $this->assertSchemaNotHasType($schema, 'FakePageVersion');

        // Disable versioning per type
        $factory->extraConfig = [
            'models' => [
                FakePage::class => [
                    'plugins' => [
                        'versioning' => false,
                    ]
                ]
            ]
        ];
        $schema = $this->createSchema($factory);
        $this->assertSchemaNotHasType($schema, 'FakePageVersion');
    }

    public function testPluginOverride()
    {
        $schema = $this->createSchema(new TestSchemaBuilder(['_' . __FUNCTION__]));
        $this->assertSchemaHasType($schema, 'FakePage');
        $this->assertSchemaHasType($schema, 'FakeRedirectorPage');
        $this->assertSchemaNotHasType($schema, 'FakeSiteTree');

        $page = FakePage::create(['Title' => 'test', 'FakePageField' => 'foo']);
        $page->write();
        $page->publishRecursive();

        $page = FakeRedirectorPage::create(['Title' => 'test', 'ExternalURL' => 'foo']);
        $page->write();
        $page->publishRecursive();

        $query = <<<GRAPHQL
query {
  readFakePages {
    nodes {
        ... on FakePageInterface {
            title
        }
    }
    edges {
        node {
            ... on FakePageInterface {
                title
            }
        }
    }
  }
}
GRAPHQL;
        $result = $this->querySchema($schema, $query);
        $this->assertSuccess($result);
        $nodes = $result['data']['readFakePages']['nodes'] ?? null;
        $this->assertNotNull($nodes);
        $this->assertCount(2, $nodes);
        $this->assertEquals('test', $nodes[0]['title']);
        $this->assertEquals('test', $nodes[1]['title']);

        $edges = $result['data']['readFakePages']['edges'] ?? null;
        $this->assertNotNull($edges);
        $this->assertCount(2, $edges);
        $this->assertEquals('test', $edges[0]['node']['title']);
        $this->assertEquals('test', $edges[1]['node']['title']);

        $query = <<<GRAPHQL
query {
  readFakeRedirectorPages {
    nodes {
        title
    }
    edges {
        node {
            title
        }
    }
  }
}
GRAPHQL;
        $result = $this->querySchema($schema, $query);
        $this->assertFailure($result);
        $this->assertMissingField($result, 'nodes');
        $this->assertMissingField($result, 'edges');

        $query = <<<GRAPHQL
query {
  readFakeRedirectorPages {
    title
  }
}
GRAPHQL;
        $result = $this->querySchema($schema, $query);
        $this->assertSuccess($result);
        $nodes = $result['data']['readFakeRedirectorPages'] ?? null;
        $this->assertNotNull($nodes);
        $this->assertCount(1, $nodes);
        $this->assertEquals('test', $nodes[0]['title']);
    }

    public function testFieldInclusion()
    {
        $schema = $this->createSchema(new TestSchemaBuilder(['_' . __FUNCTION__]));
        $this->assertSchemaHasType($schema, 'DataObjectFake');
        $fake = DataObjectFake::create(['MyField' => 'test', 'MyInt' => 5]);
        $fake->write();
        $query = <<<GRAPHQL
query {
  readOneDataObjectFake {
    myInt
    myField
  }
}
GRAPHQL;
        $result = $this->querySchema($schema, $query);
        $this->assertSuccess($result);
        $this->assertResult('readOneDataObjectFake.myField', 'test', $result);
        $factory = new TestSchemaBuilder();
        $factory->extraConfig = [
            'models' => [
                DataObjectFake::class => [
                    'fields' => [
                        'myInt' => false,
                        'myField' => true,
                    ],
                    'operations' => [
                        'readOne' => true,
                    ]
                ]
            ]
        ];
        $schema = $this->createSchema($factory);
        $result = $this->querySchema($schema, $query);
        $this->assertFailure($result);
        $this->assertMissingField($result, 'myInt');

        $factory = new TestSchemaBuilder();
        $factory->extraConfig = [
            'models' => [
                DataObjectFake::class => [
                    'fields' => [
                        '*' => true,
                        'myField' => false,
                    ],
                    'operations' => [
                        'readOne' => true,
                    ]
                ]
            ]
        ];
        $schema = $this->createSchema($factory);
        $result = $this->querySchema($schema, $query);
        $this->assertFailure($result);
        $this->assertMissingField($result, 'myField');

        $query = <<<GRAPHQL
query {
  readOneDataObjectFake {
    id
    myInt
  }
}
GRAPHQL;
        $result = $this->querySchema($schema, $query);
        $this->assertSuccess($result);
        $this->assertResult('readOneDataObjectFake.myInt', 5, $result);
        $factory = new TestSchemaBuilder();
        $factory->extraConfig = [
            'models' => [
                DataObjectFake::class => [
                    'fields' => '*',
                    'operations' => [
                        '*' => true,
                        'create' => false,
                    ],
                ]
            ]
        ];
        $schema = $this->createSchema($factory);
        $gql = $factory->fetchSchema($schema);
        $queryType = $gql->getQueryType();
        $mutationType = $gql->getMutationType();
        $queries = $queryType->getFields();
        $mutations = $mutationType->getFields();

        $this->assertArrayHasKey('readOneDataObjectFake', $queries);
        $this->assertArrayHasKey('readDataObjectFakes', $queries);
        $this->assertArrayHasKey('deleteDataObjectFakes', $mutations);
        $this->assertArrayHasKey('updateDataObjectFake', $mutations);
        $this->assertArrayNotHasKey('createDataObjectFake', $mutations);
    }

    public function testNestedFieldDefinitions()
    {
        $author = Member::create(['FirstName' => 'tester']);
        $author->write();

        $dataObject = DataObjectFake::create(['MyField' => 'test', 'AuthorID' => $author->ID]);
        $dataObject->write();

        $file = File::create(['Title' => 'test']);
        $file->write();

        $dataObject->Files()->add($file);

        $schema = $this->createSchema(new TestSchemaBuilder(['_' . __FUNCTION__]));

        $query = <<<GRAPHQL
query {
  readOneDataObjectFake {
    myField
    author {
      firstName
    }
    files {
      id
    }
  }
}
GRAPHQL;
        $result = $this->querySchema($schema, $query);
        $this->assertSuccess($result);
        $this->assertResult('readOneDataObjectFake.myField', 'test', $result);
        $this->assertResult('readOneDataObjectFake.author.firstName', 'tester', $result);
        $fileID = $result['data']['readOneDataObjectFake']['files'][0]['id'] ?? null;
        $this->assertNotNull($fileID);
        $this->assertEquals($file->ID, $fileID);

        $query = <<<GRAPHQL
query {
  readOneDataObjectFake {
    myField
    author {
      firstName
    }
    files {
      id
      title
    }
  }
}
GRAPHQL;
        $result = $this->querySchema($schema, $query);
        $this->assertFailure($result);
        $this->assertMissingField($result, 'title');
    }

    public function provideFilterAndSort(): array
    {
        return [
            [
                'query' => <<<GRAPHQL
                query {
                  readOneDataObjectFake(filter: { id: { eq: _ID_PLACEHOLDER_ } }) {
                    id
                  }
                }
                GRAPHQL,
                'testAgainst' => 'id',
                'placeholderRecord' => 'fake1',
                'expected' => 'fake1',
            ],
            [
                'query' => <<<GRAPHQL
                query {
                  readOneDataObjectFake(filter: { id: { ne: _ID_PLACEHOLDER_ } }) {
                    id
                  }
                }
                GRAPHQL,
                'testAgainst' => 'id',
                'placeholderRecord' => 'fake1',
                'expected' => 'fake2',
            ],
            [
                'query' => <<<GRAPHQL
                query {
                  readOneDataObjectFake(sort: { myField: ASC }) {
                    myField
                  }
                }
                GRAPHQL,
                'testAgainst' => 'myField',
                'placeholderRecord' => '',
                'expected' => 'test1',
            ],
            [
                'query' => <<<GRAPHQL
                query {
                  readOneDataObjectFake(sort: { AuthorID: DESC, myField: ASC }) {
                    myField
                  }
                }
                GRAPHQL,
                'testAgainst' => 'myField',
                'placeholderRecord' => '',
                'expected' => 'test2',
            ],
            [
              'query' => <<<GRAPHQL
              query {
                readOneDataObjectFake(sort: { myField: ASC, AuthorID: DESC }) {
                  myField
                }
              }
              GRAPHQL,
              'testAgainst' => 'myField',
              'placeholderRecord' => '',
              'expected' => 'test1',
            ],
            [
                'query' => <<<GRAPHQL
                query {
                  readOneDataObjectFake(sort: { myField: DESC }) {
                    myField
                  }
                }
                GRAPHQL,
                'testAgainst' => 'myField',
                'placeholderRecord' => '',
                'expected' => 'test4',
            ],
            [
                'query' => <<<GRAPHQL
                query {
                  readOneDataObjectFake(sort: { AuthorID: ASC, myField: DESC }, filter: { id: { ne: _ID_PLACEHOLDER_ } }) {
                    myField
                  }
                }
                GRAPHQL,
                'testAgainst' => 'myField',
                'placeholderRecord' => 'fake3',
                'expected' => 'test4',
            ],
        ];
    }

    /**
     * @dataProvider provideFilterAndSort
     */
    public function testFilterAndSort(string $query, string $testAgainst, string $placeholderRecord, string $expected): void
    {
        $dir = '_' . __FUNCTION__;

        $author1 = Member::create(['FirstName' => 'tester1']);
        $author1->write();

        $author2 = Member::create(['FirstName' => 'tester2']);
        $author2->write();

        $dataObject1 = DataObjectFake::create(['MyField' => 'test1', 'AuthorID' => $author1->ID]);
        $dataObject1->write();

        $dataObject2 = DataObjectFake::create(['MyField' => 'test2', 'AuthorID' => $author2->ID]);
        $dataObject2->write();

        $dataObject3 = DataObjectFake::create(['MyField' => 'test3', 'AuthorID' => $author2->ID]);
        $dataObject3->write();

        $dataObject4 = DataObjectFake::create(['MyField' => 'test4', 'AuthorID' => $author1->ID]);
        $dataObject4->write();

        $file1 = File::create(['Title' => 'file1']);
        $file1->write();

        $file2 = File::create(['Title' => 'file2']);
        $file2->write();

        $dataObject1->Files()->add($file1);
        $dataObject1->Files()->add($file2);

        $id1 = $dataObject1->ID;
        $id2 = $dataObject2->ID;
        $id3 = $dataObject3->ID;
        $id4 = $dataObject4->ID;

        if ($testAgainst === 'id') {
            switch ($expected) {
                case 'fake1':
                    $expected = $id1;
                    break;
                case 'fake2':
                    $expected = $id2;
                    break;
                case 'fake3':
                    $expected = $id3;
                    break;
                default:
                    throw new LogicException("No ID known for '$expected'");
            }
        }

        $placeholderID = null;
        switch ($placeholderRecord) {
            case 'fake1':
                $placeholderID = $id1;
                break;
            case 'fake2':
                $placeholderID = $id2;
                break;
            case 'fake3':
                $placeholderID = $id3;
                break;
        }
        if ($placeholderID) {
            $query = str_replace('_ID_PLACEHOLDER_', (string) $placeholderID, $query);
        }

        $schema = $this->createSchema(new TestSchemaBuilder([$dir]));

        $result = $this->querySchema($schema, $query);
        $this->assertSuccess($result);
        $this->assertResult("readOneDataObjectFake.{$testAgainst}", $expected, $result);
    }


    public function testFieldAliases()
    {
        $author = Member::create(['FirstName' => 'tester']);
        $author->write();

        $dataObject1 = DataObjectFake::create(['MyField' => 'test1', 'AuthorID' => $author->ID]);
        $dataObject1->write();

        $dataObject2 = DataObjectFake::create(['MyField' => 'test2', 'AuthorID' => $author->ID]);
        $dataObject2->write();

        $factory = new TestSchemaBuilder();
        $factory->extraConfig = [
            'models' => [
                DataObjectFake::class => [
                    'operations' => [
                        'readOne' => [
                            'plugins' => [
                                'filter' => true,
                                'sort' => true,
                            ]
                        ],
                    ],
                    'fields' => [
                        'myAliasedField' => [
                            'property' => 'MyField',
                        ],
                        'author' => [
                            'fields' => [
                                'nickname' => [
                                    'property' => 'FirstName',
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $schema = $this->createSchema($factory);

        $query = <<<GRAPHQL
query {
  readOneDataObjectFake {
    myField
  }
}
GRAPHQL;
        $result = $this->querySchema($schema, $query);
        $this->assertFailure($result);
        $this->assertMissingField($result, 'myField');

        $query = <<<GRAPHQL
query {
  readOneDataObjectFake(sort: { myAliasedField: ASC }) {
    myAliasedField
    author {
      nickname
    }
  }
}
GRAPHQL;

        $result = $this->querySchema($schema, $query);
        $this->assertSuccess($result);
        $this->assertResult('readOneDataObjectFake.myAliasedField', 'test1', $result);
        $this->assertResult('readOneDataObjectFake.author.nickname', 'tester', $result);

        $query = <<<GRAPHQL
query {
  readOneDataObjectFake(filter: { myAliasedField: { eq: "test2"} }) {
    myAliasedField
    author {
      nickname
    }
  }
}
GRAPHQL;

        $result = $this->querySchema($schema, $query);
        $this->assertSuccess($result);
        $this->assertResult('readOneDataObjectFake.myAliasedField', 'test2', $result);
        $this->assertResult('readOneDataObjectFake.author', null, $result);
    }

    public function provideFilterAndSortOnlyRead(): array
    {
        return [
          'read with sort' => [
            'fixture' => '_QuerySort',
            'query' => <<<GRAPHQL
            query {
              readDataObjectFakes(sort: { author: { id: DESC }, myField: ASC }) {
                nodes {
                  myField
                  author {
                    firstName
                  }
                }
              }
            }
            GRAPHQL,
            'expected' => [
              ["myField" => "test2", "author" => ["firstName" => "tester2"]],
              ["myField" => "test3", "author" => ["firstName" => "tester2"]],
              ["myField" => "test1", "author" => ["firstName" => "tester1"]],
            ],
          ],
          'read with sorter files title DESC' => [
            'fixture' => '_SortPlugin',
            'query' => <<<GRAPHQL
            query {
              readDataObjectFakes(sort: { myField: ASC }) {
                nodes {
                  myField
                  files(sort: { title: DESC }) {
                    title
                  }
                }
              }
            }
            GRAPHQL,
            'expected' => [
              ["myField" => "test1", "files" => [["title" => "file4"], ["title" => "file3"], ["title" => "file2"], ["title" => "file1"]]],
              ["myField" => "test2", "files" => []],
              ["myField" => "test3", "files" => []],
            ],
          ],
          'read with sorter files ParentID ACS, name DESC' => [
            'fixture' => '_SortPlugin',
            'query' => <<<GRAPHQL
            query {
              readDataObjectFakes(sort: { myField: ASC }) {
                nodes {
                  myField
                  files(sort: { ParentID: ASC, name: DESC }) {
                    title
                  }
                }
              }
            }
            GRAPHQL,
            'expected' => [
              ["myField" => "test1", "files" => [["title" => "file2"],["title" => "file1"], ["title" => "file4"],["title" => "file3"]]],
              ["myField" => "test2", "files" => []],
              ["myField" => "test3", "files" => []],
            ],
          ],
          'read with sorter files ParentID DESC, name ASC' => [
            'fixture' => '_SortPlugin',
            'query' => <<<GRAPHQL
            query {
              readDataObjectFakes(sort: { myField: ASC }) {
                nodes {
                  myField
                  files(sort: { ParentID: DESC, name: ASC }) {
                    title
                  }
                }
              }
            }
            GRAPHQL,
            'expected' => [
              ["myField" => "test1", "files" => [["title" => "file3"],["title" => "file4"], ["title" => "file1"],["title" => "file2"]]],
              ["myField" => "test2", "files" => []],
              ["myField" => "test3", "files" => []],
            ],
          ],
          'read with sorter files name ASC, ParentID DESC' => [
            'fixture' => '_SortPlugin',
            'query' => <<<GRAPHQL
            query {
              readDataObjectFakes(sort: { myField: ASC }) {
                nodes {
                  myField
                  files(sort: { name: ASC, ParentID: DESC }) {
                    title
                  }
                }
              }
            }
            GRAPHQL,
            'expected' => [
              ["myField" => "test1", "files" => [["title" => "file3"],["title" => "file1"], ["title" => "file4"],["title" => "file2"]]],
              ["myField" => "test2", "files" => []],
              ["myField" => "test3", "files" => []],
            ],
          ],
          'read with sorter files name DESC, ParentID ASC' => [
            'fixture' => '_SortPlugin',
            'query' => <<<GRAPHQL
            query {
              readDataObjectFakes(sort: { myField: ASC }) {
                nodes {
                  myField
                  files(sort: { name: DESC, ParentID: ASC }) {
                    title
                  }
                }
              }
            }
            GRAPHQL,
            'expected' => [
              ["myField" => "test1", "files" => [["title" => "file2"],[ "title" => "file4"],["title" => "file1"],["title" => "file3"]]],
              ["myField" => "test2", "files" => []],
              ["myField" => "test3", "files" => []],
            ],
          ],
        ];
    }

    /**
     * @dataProvider provideFilterAndSortOnlyRead
     */
    public function testFilterAndSortOnlyRead(string $fixture, string $query, array $expected)
    {
        $author = Member::create(['FirstName' => 'tester1']);
        $author->write();

        $author2 = Member::create(['FirstName' => 'tester2']);
        $author2->write();

        $dataObject1 = DataObjectFake::create(['MyField' => 'test1', 'AuthorID' => $author->ID]);
        $dataObject1->write();

        $dataObject2 = DataObjectFake::create(['MyField' => 'test2', 'AuthorID' => $author2->ID]);
        $dataObject2->write();

        $dataObject3 = DataObjectFake::create(['MyField' => 'test3', 'AuthorID' => $author2->ID]);
        $dataObject3->write();

        $file1 = File::create(['Title' => 'file1', 'Name' => 'asc_name']);
        $file1->ParentID = 1;
        $file1->write();

        $file2 = File::create(['Title' => 'file2', 'Name' => 'desc_name']);
        $file2->ParentID = 1;
        $file2->write();

        $file3 = File::create(['Title' => 'file3', 'Name' => 'asc_name']);
        $file3->ParentID = 2;
        $file3->write();

        $file4 = File::create(['Title' => 'file4', 'Name' => 'desc_name']);
        $file4->ParentID = 2;
        $file4->write();

        $dataObject1->Files()->add($file1);
        $dataObject1->Files()->add($file2);
        $dataObject1->Files()->add($file3);
        $dataObject1->Files()->add($file4);

        $factory = new TestSchemaBuilder(['_' . __FUNCTION__ . $fixture]);
        $schema = $this->createSchema($factory);

        $result = $this->querySchema($schema, $query);
        $this->assertSuccess($result);
        $records = $result['data']['readDataObjectFakes']['nodes'] ?? [];
        $this->assertResults($expected, $records);
    }

    public function provideFilterAndSortWithArgsOnlyRead(): array
    {
        return [
            'read with sort - with sort arg' => [
                'fixture' => '_QuerySort',
                'query' => <<<GRAPHQL
query (\$sort: DataObjectFakeSortFields) {
  readDataObjectFakes(sort: \$sort) {
    nodes {
      myField
      author {
        firstName
      }
    }
  }
}
GRAPHQL,
                'args' => [
                    'sort' => ['author' => ['id' => 'DESC'], 'myField' => 'ASC']
                ],
                'expected' => [
                    ["myField" => "test2", "author" => ["firstName" => "tester2"]],
                    ["myField" => "test3", "author" => ["firstName" => "tester2"]],
                    ["myField" => "test1", "author" => ["firstName" => "tester1"]],
                ],
            ],
            'read with sorter files ParentID DESC, name ASC - with sort args' => [
                'fixture' => '_SortPlugin',
                'query' => <<<GRAPHQL
query (\$sort: DataObjectFakeSortFields, \$fileSort: FilesSimpleSortFields) {
  readDataObjectFakes(sort: \$sort) {
    nodes {
      myField
      files(sort: \$fileSort) {
        title
      }
    }
  }
}
GRAPHQL,
                'args' => [
                    'sort' => ['myField' => 'ASC'],
                    'fileSort' => ['ParentID' => 'DESC', 'name' => 'ASC'],
                ],
                'expected' => [
                    ["myField" => "test1", "files" => [["title" => "file2"], ["title" => "file4"], ["title" => "file1"], ["title" => "file3"]]],
                    ["myField" => "test2", "files" => []],
                    ["myField" => "test3", "files" => []],
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideFilterAndSortWithArgsOnlyRead
     */
    public function testFilterAndSortWithArgsOnlyRead(string $fixture, string $query, array $args, array $expected)
    {
        $author = Member::create(['FirstName' => 'tester1']);
        $author->write();

        $author2 = Member::create(['FirstName' => 'tester2']);
        $author2->write();

        $dataObject1 = DataObjectFake::create(['MyField' => 'test1', 'AuthorID' => $author->ID]);
        $dataObject1->write();

        $dataObject2 = DataObjectFake::create(['MyField' => 'test2', 'AuthorID' => $author2->ID]);
        $dataObject2->write();

        $dataObject3 = DataObjectFake::create(['MyField' => 'test3', 'AuthorID' => $author2->ID]);
        $dataObject3->write();

        $file1 = File::create(['Title' => 'file1', 'Name' => 'asc_name']);
        $file1->ParentID = 1;
        $file1->write();

        $file2 = File::create(['Title' => 'file2', 'Name' => 'desc_name']);
        $file2->ParentID = 1;
        $file2->write();

        $file3 = File::create(['Title' => 'file3', 'Name' => 'asc_name']);
        $file3->ParentID = 2;
        $file3->write();

        $file4 = File::create(['Title' => 'file4', 'Name' => 'desc_name']);
        $file4->ParentID = 2;
        $file4->write();

        $dataObject1->Files()->add($file1);
        $dataObject1->Files()->add($file2);
        $dataObject1->Files()->add($file3);
        $dataObject1->Files()->add($file4);

        $factory = new TestSchemaBuilder(['_' . __FUNCTION__ . $fixture]);
        $schema = $this->createSchema($factory);

        $result = $this->querySchema($schema, $query, $args);
        $this->assertSuccess($result);
        $records = $result['data']['readDataObjectFakes']['nodes'] ?? [];

        // We intentionally don't check the sort order, as it's expected it may not match:
        // See https://github.com/silverstripe/silverstripe-graphql/issues/573
        $this->assertEqualsCanonicalizing($expected, $records);
    }

    public function testAggregateProperties()
    {
        $file1 = File::create(['Title' => '1']);
        $file1->write();

        $file2 = File::create(['Title' => '2']);
        $file2->write();

        $dataObject1 = DataObjectFake::create(['MyField' => 'test1']);
        $dataObject1->write();

        $dataObject2 = DataObjectFake::create(['MyField' => 'test2']);
        $dataObject2->write();

        $dataObject1->Files()->add($file1);
        $dataObject1->Files()->add($file2);

        $dataObject1->write();

        $factory = new TestSchemaBuilder();
        $factory->extraConfig = [
            'models' => [
                DataObjectFake::class => [
                    'operations' => [
                        'readOne' => [
                            'plugins' => [
                                'filter' => true,
                            ],
                        ],
                    ],
                    'fields' => [
                        'myField' => true,
                        'fileCount' => [
                            'property' => 'Files.Count()',
                            'type' => 'Int',
                        ],
                        'maxFileTitle' => [
                            'property' => 'Files.Max(Title)',
                            'type' => 'String',
                        ],
                        'minFileTitle' => [
                            'property' => 'Files.Min(Title)',
                            'type' => 'String',
                        ],
                        'fileTitles' => [
                            'property' => 'Files.Title',
                            'type' => '[String]',
                        ],
                    ]
                ]
            ]
        ];
        $schema = $this->createSchema($factory);

        $query = <<<GRAPHQL
query {
  readOneDataObjectFake(filter: { id: { eq: $dataObject1->ID } }) {
    myField
    fileCount
    maxFileTitle
    minFileTitle
    fileTitles
  }
}
GRAPHQL;
        $result = $this->querySchema($schema, $query);
        $this->assertSuccess($result);
        $this->assertResult('readOneDataObjectFake.myField', 'test1', $result);
        $this->assertResult('readOneDataObjectFake.fileCount', 2, $result);
        $this->assertResult('readOneDataObjectFake.maxFileTitle', '2', $result);
        $this->assertResult('readOneDataObjectFake.minFileTitle', '1', $result);
        $arr = $result['data']['readOneDataObjectFake']['fileTitles'];
        $this->assertNotNull($arr);
        $this->assertCount(2, $arr);
        $this->assertTrue(in_array('1', $arr ?? []));
        $this->assertTrue(in_array('2', $arr ?? []));

        $query = <<<GRAPHQL
query {
  readOneDataObjectFake(filter: { id: { eq: $dataObject2->ID } }) {
    myField
    fileCount
    maxFileTitle
    minFileTitle
    fileTitles
  }
}
GRAPHQL;

        $result = $this->querySchema($schema, $query);
        $this->assertSuccess($result);
        $this->assertResult('readOneDataObjectFake.myField', 'test2', $result);
        $this->assertResult('readOneDataObjectFake.fileCount', 0, $result);
        $this->assertNull($result['data']['readOneDataObjectFake']['maxFileTitle']);
        $this->assertNull($result['data']['readOneDataObjectFake']['minFileTitle']);
        $arr = $result['data']['readOneDataObjectFake']['fileTitles'];
        $this->assertNotNull($arr);
        $this->assertCount(0, $arr);
    }

    public function testBasicPaginator()
    {
        $factory = new TestSchemaBuilder(['_' . __FUNCTION__]);
        $factory->resolvers = [IntegrationTestResolver::class];
        $schema = $this->createSchema($factory);
        $query = <<<GRAPHQL
query {
  readMyTypes(limit: 5) {
    nodes {
        field1
    }
    pageInfo {
      totalCount
      hasNextPage
      hasPreviousPage
    }
  }
}
GRAPHQL;
        $result = $this->querySchema($schema, $query);
        $this->assertSuccess($result);
        $this->assertResult('readMyTypes.pageInfo.totalCount', 100, $result);
        $this->assertResult('readMyTypes.pageInfo.hasNextPage', true, $result);
        $this->assertResult('readMyTypes.pageInfo.hasPreviousPage', false, $result);
        $records = $result['data']['readMyTypes']['nodes'] ?? [];
        $this->assertCount(5, $records);
        $this->assertResults([
            ['field1' => 'field1-1'],
            ['field1' => 'field1-2'],
            ['field1' => 'field1-3'],
            ['field1' => 'field1-4'],
            ['field1' => 'field1-5'],
        ], $records);

        $query = <<<GRAPHQL
query {
  readMyTypes(limit: 5, offset: 5) {
    nodes {
        field1
    }
    pageInfo {
      totalCount
      hasNextPage
      hasPreviousPage
    }
  }
}
GRAPHQL;
        $result = $this->querySchema($schema, $query);
        $this->assertSuccess($result);
        $this->assertResult('readMyTypes.pageInfo.totalCount', 100, $result);
        $this->assertResult('readMyTypes.pageInfo.hasNextPage', true, $result);
        $this->assertResult('readMyTypes.pageInfo.hasPreviousPage', true, $result);
        $records = $result['data']['readMyTypes']['nodes'] ?? [];
        $this->assertCount(5, $records);
        $this->assertResults([
            ['field1' => 'field1-6'],
            ['field1' => 'field1-7'],
            ['field1' => 'field1-8'],
            ['field1' => 'field1-9'],
            ['field1' => 'field1-10'],
        ], $records);
    }

    public function testCanViewPagination()
    {
        // FakeDataObjectWithCanView has a canView() check of `return $this->ID % 2` i.e. half the records are viewable
        // This test will:
        // - Create 20 records
        // - Query 10 records
        // - Assert that 5 records were returned
        for ($i = 0; $i < 20; $i++) {
            $obj = FakeDataObjectWithCanView::create(['Title' => "obj$i"]);
            $obj->write();
        }
        $schema = $this->createSchema(new TestSchemaBuilder(['_' . __FUNCTION__]));
        $query = <<<GRAPHQL
            query {
              readFakeDataObjectWithCanViews(limit: 10) {
                nodes {
                  id
                  title
                }
                edges {
                  node {
                    id
                    title
                  }
                }
              }
            }
        GRAPHQL;
        $result = $this->querySchema($schema, $query);
        $this->assertSuccess($result);
        $records = $result['data']['readFakeDataObjectWithCanViews']['nodes'];
        $this->assertCount(5, $records);
        $ids = array_map(fn($record) => $record['id'], $records);
        $filteredIDs = array_filter($ids, fn($id) => $id % 2);
        $this->assertSame(count($ids), count($filteredIDs));
    }

    /**
     * @throws SchemaBuilderException
     * @throws SchemaNotFoundException
     * @dataProvider provideObfuscationState
     * @param bool $shouldObfuscateTypes
     */
    public function testQueriesAndMutations($shouldObfuscateTypes)
    {
        FakeProductPage::get()->removeAll();
        if ($shouldObfuscateTypes) {
            Injector::inst()->load([
                NameObfuscator::class => [
                    'class' => HashNameObfuscator::class,
                ]
            ]);
        }
        $schema = $this->createSchema(new TestSchemaBuilder(['_' . __FUNCTION__]));

        if ($shouldObfuscateTypes) {
            $obfuscator = new HashNameObfuscator();
            $obfuscatedName = $obfuscator->obfuscate('FakeProductPage');
            $path = Path::join(
                __DIR__,
                CodeGenerationStore::config()->get('dirName'),
                $schema->getSchemaKey(),
                $obfuscatedName . '.php'
            );
            $this->assertTrue(file_exists($path ?? ''));
        }
        // Create a couple of product pages
        $productPageIDs = [];
        foreach (range(1, 2) as $num) {
            $query = <<<GRAPHQL
mutation {
  createFakeProductPage(input: {
    title: "Product page $num"
  }) {
    id
  }
}
GRAPHQL;
            $result = $this->querySchema($schema, $query);
            $this->assertSuccess($result);
            $productPageIDs[] = $result['data']['createFakeProductPage']['id'];
        }
        // Create products for each product page
        $productIDs = [];
        foreach ($productPageIDs as $productPageID) {
            foreach (range(1, 5) as $num) {
                $query = <<<GRAPHQL
mutation {
  createFakeProduct(input: {
    parentID: $productPageID,
    title: "Product $num on page $productPageID",
    price: $num
  }) {
    id
  }
}
GRAPHQL;
                $result = $this->querySchema($schema, $query);
                $this->assertSuccess($result);
                $productIDs[] = $productPageID . '__' . $result['data']['createFakeProduct']['id'];
            }
        }

        // Create reviews for reach product
        $reviewIDs = [];
        foreach ($productIDs as $sku) {
            list($productPageID, $productID) = explode('__', $sku ?? '');
            foreach (range(1, 5) as $num) {
                $query = <<<GRAPHQL
mutation {
  createFakeReview(input: {
    productID: $productID,
    content: "Review $num on product $productID",
    score: $num
  }) {
    id
  }
}
GRAPHQL;
                $result = $this->querySchema($schema, $query);
                $this->assertSuccess($result);
                $reviewIDs[] = $productPageID . '__' . $productID . '__' . $result['data']['createFakeReview']['id'];
            }
        }

        // Add authors to reviews
        $this->logInWithPermission();
        foreach ($reviewIDs as $sku) {
            list ($productPageID, $productID, $reviewID) = explode('__', $sku ?? '');
            $query = <<<GRAPHQL
mutation {
  createMember(input: { firstName: "Member $num" }) {
    id
  }
}
GRAPHQL;
            $result = $this->querySchema($schema, $query);
            $this->assertSuccess($result);

            $memberID = $result['data']['createMember']['id'];

            $query = <<<GRAPHQL
mutation {
  updateFakeReview(
    input: { id: $reviewID, authorID: $memberID }
  ) {
    authorID
  }
}
GRAPHQL;
            $result = $this->querySchema($schema, $query);
            $this->assertSuccess($result);
            $this->assertEquals($memberID, $result['data']['updateFakeReview']['authorID']);
        }

        // Check the counts
        $query = <<<GRAPHQL
query {
  readFakeProductPages(sort: { title: ASC }) {
    nodes {
      title
      products {
        nodes {
        	reviews {
            pageInfo {
              totalCount
            }
          }
        }
        pageInfo {
          totalCount
        }
      }
    }
    pageInfo {
      totalCount
    }
  }
}
GRAPHQL;
        $result = $this->querySchema($schema, $query);
        $this->assertSuccess($result);
        $this->assertResult('readFakeProductPages.pageInfo.totalCount', 2, $result);
        $this->assertResult('readFakeProductPages.nodes.0.products.pageInfo.totalCount', 5, $result);
        $this->assertResult('readFakeProductPages.nodes.1.products.pageInfo.totalCount', 5, $result);
        $this->assertResult('readFakeProductPages.nodes.0.products.nodes.0.reviews.pageInfo.totalCount', 5, $result);
        $this->assertResult('readFakeProductPages.nodes.1.products.nodes.0.reviews.pageInfo.totalCount', 5, $result);
        $this->assertResult('readFakeProductPages.nodes.1.products.nodes.0.reviews.pageInfo.totalCount', 5, $result);
        $this->assertResult('readFakeProductPages.nodes.1.products.nodes.1.reviews.pageInfo.totalCount', 5, $result);
        $this->assertResult('readFakeProductPages.nodes.0.title', 'Product page 1', $result);
        $this->assertResult('readFakeProductPages.nodes.1.title', 'Product page 2', $result);

        // Get all the product pages that have a product with "on product page 2"
        $secondProductPageID = $productPageIDs[1];
        $query = <<<GRAPHQL
query {
  readFakeProductPages(
    filter: {
        products: {
            title: { endswith: "on page $secondProductPageID" }
        }
    },
    sort: { title: DESC }
  ) {
    pageInfo {
      totalCount
    }
    nodes {
      id
      title
      products(
        sort: { title: ASC },
        filter: {
          reviews: {
            score: { gt: 3 }
          }
        }
      ) {
        nodes {
          id
          title
          reviews {
            nodes {
              score
              author {
                firstName
              }
            }
          }
        }
      }
    }
  }
}
GRAPHQL;
        $result = $this->querySchema($schema, $query);
        $this->assertSuccess($result);
        $this->assertResult('readFakeProductPages.pageInfo.totalCount', 1, $result);
        $this->assertResult('readFakeProductPages.nodes.0.title', 'Product page 2', $result);
        $this->assertCount(5, $result['data']['readFakeProductPages']['nodes'][0]['products']['nodes']);
        $this->assertResult(
            'readFakeProductPages.nodes.0.products.nodes.2.title',
            'Product 3 on page ' . $secondProductPageID,
            $result
        );

        $query = <<<GRAPHQL
query {
  readFakeProductPages(
    filter: { id: { eq: $productPageIDs[0] } },
    sort: { title: DESC }
  ) {
    nodes {
      id
      title
      products(
        sort: { title: ASC }
      ) {
        nodes {
          id
          title
          highestReview
          reviews(filter: { score: { gt: 3 } }) {
            nodes {
              score
              author {
                firstName
              }
            }
          }
        }
      }
    }
  }
}
GRAPHQL;
        $result = $this->querySchema($schema, $query);
        $this->assertSuccess($result);
        $products = $result['data']['readFakeProductPages']['nodes'][0]['products']['nodes'];
        foreach ($products as $product) {
            $this->assertEquals(5, $product['highestReview']);
            foreach ($product['reviews']['nodes'] as $review) {
                $this->assertGreaterThan(3, $review['score']);
            }
        }
    }


    public function testDBFieldArgs()
    {
        $schema = $this->createSchema(new TestSchemaBuilder(['_' . __FUNCTION__]));
        $this->assertSchemaHasType($schema, 'DataObjectFake');
        $obj = DataObjectFake::create([
            'MyField' => 'This is a varchar field',
            'MyDate' => '1582995600', // 29 Feb 2020 17h
            'MyCurrency' => '204.75',
            'MyText' => 'This is a really long text field. It has a few sentences. Just filling some space now.',
        ]);
        $obj->write();

        $query = <<<GRAPHQL
query {
  readOneDataObjectFake {
    myField
    date1: myDate(format: DAY_OF_WEEK)
    date2: myDate(format: SHORT)
    date3: myDate(format: TIME)
    date4: myDate(format: CUSTOM, customFormat: "YYYY")
    myText(format: LIMIT_SENTENCES, limit: 2)
  }
}
GRAPHQL;
        $result = $this->querySchema($schema, $query);
        $this->assertSuccess($result);
        $node = $result['data']['readOneDataObjectFake'] ?? null;
        $this->assertEquals('This is a varchar field', $node['myField']);
        $this->assertEquals('Saturday', $node['date1']);
        $this->assertEquals('2/29/20, 5:00 PM', $node['date2']);
        $this->assertEquals('5:00:00 PM', $node['date3']);
        $this->assertEquals('2020', $node['date4']);
        $this->assertEquals('This is a really long text field. It has a few sentences.', $node['myText']);
    }

    public function testBulkLoadInheritance()
    {
        $schema = $this->createSchema(new TestSchemaBuilder(['_' . __FUNCTION__]));
        $this->assertSchemaHasType($schema, 'A1');
        $this->assertSchemaHasType($schema, 'A1a');
        $this->assertSchemaHasType($schema, 'A1b');
        $this->assertSchemaHasType($schema, 'C');
        $this->assertSchemaHasType($schema, 'C1');
        $this->assertSchemaHasType($schema, 'C2');
        $this->assertSchemaNotHasType($schema, 'C2a');
        $this->assertSchemaNotHasType($schema, 'B');
        $this->assertSchemaNotHasType($schema, 'A2');

        $query = $schema->getQueryType();
        $this->assertNotNull($query->getFieldByName('readA1s'));
        $this->assertNotNull($query->getFieldByName('readA1as'));
        $this->assertNotNull($query->getFieldByName('readA1bs'));
        $this->assertNotNull($query->getFieldByName('readCs'));
        $this->assertNotNull($query->getFieldByName('readC1s'));
        $this->assertNotNull($query->getFieldByName('readC2s'));
        $this->assertNull($query->getFieldByName('readC2as'));

        $a1 = $schema->getType('A1');
        $this->assertNotNull($a1->getFieldByName('A1Field'));
        $this->assertNull($a1->getFieldByName('created'));

        $c = $schema->getType('C');
        $this->assertNotNull($c->getFieldByName('cField'));
        $this->assertNull($c->getFieldByName('created'));
    }

    public function testBulkLoadNamespaceAndFilepath()
    {
        $schema = $this->createSchema(new TestSchemaBuilder(['_' . __FUNCTION__]));
        $this->assertSchemaHasType($schema, 'A1');
        $this->assertSchemaHasType($schema, 'A2');
        $this->assertSchemaHasType($schema, 'A1a');
        $this->assertSchemaHasType($schema, 'A1b');
        $this->assertSchemaHasType($schema, 'A2a');

        $this->assertSchemaHasType($schema, 'B');
        $this->assertSchemaHasType($schema, 'B1');
        $this->assertSchemaHasType($schema, 'B2');
        $this->assertSchemaHasType($schema, 'B1a');
        $this->assertSchemaHasType($schema, 'B1b');

        $this->assertSchemaHasType($schema, 'C');
        $this->assertSchemaHasType($schema, 'C1');
        $this->assertSchemaHasType($schema, 'C2');

        $this->assertSchemaNotHasType($schema, 'C2a');

        $this->assertSchemaHasType($schema, 'SubFakePage');
        $this->assertSchemaNotHasType($schema, 'FakePage');

        $this->assertSchemaHasType($schema, 'FakeProductPage');
        $this->assertSchemaHasType($schema, 'FakeRedirectorPage');
    }

    /**
     * @return array
     */
    public function provideObfuscationState(): array
    {
        return [ [false], [true] ];
    }

    public function testCustomFilterFields()
    {
        $dir = '_' . __FUNCTION__;

        $dataObject1 = DataObjectFake::create(['MyField' => 'Atest1']);
        $dataObject1->write();

        $dataObject2 = DataObjectFake::create(['MyField' => 'Btest2']);
        $dataObject2->write();

        $id1 = $dataObject1->ID;
        $id2 = $dataObject2->ID;

        $schema = $this->createSchema(new TestSchemaBuilder([$dir]));

        $query = <<<GRAPHQL
query {
  readOneDataObjectFake(filter: { onlyStartsWithA: { eq: true } }) {
    id
  }
}
GRAPHQL;
        $result = $this->querySchema($schema, $query);
        $this->assertSuccess($result);
        $this->assertResult('readOneDataObjectFake.id', $id1, $result);

        $query = <<<GRAPHQL
query {
  readOneDataObjectFake(filter: { onlyStartsWithA: { eq: false } }) {
    id
  }
}
GRAPHQL;
        $result = $this->querySchema($schema, $query);
        $this->assertSuccess($result);
        $this->assertResult('readOneDataObjectFake.id', $id2, $result);

        $query = <<<GRAPHQL
query {
  readOneDataObjectFake(filter: { id: { eq: $id1 } }) {
    id
  }
}
GRAPHQL;
        $result = $this->querySchema($schema, $query);
        $this->assertSuccess($result);
        $this->assertResult('readOneDataObjectFake.id', $id1, $result);
    }

    public function testHtaccess(): void
    {
        FakeProductPage::get()->removeAll();
        $schema = $this->createSchema(new TestSchemaBuilder(['_testSimpleType']));

        $file = Path::join(
            __DIR__,
            CodeGenerationStore::config()->get('dirName'),
            $schema->getSchemaKey(),
            '.htaccess'
        );
        $this->assertFileExists($file);
        $this->assertEquals(
            <<<HTACCESS
            Require all denied
            RewriteRule .* - [F]
            HTACCESS,
            file_get_contents($file)
        );
    }

    /**
     * @dataProvider provideDefaultDepthLimit
     */
    public function testDefaultDepthLimit(int $queryDepth, int $limit)
    {
        // This global rule should be ignored.
        DocumentValidator::addRule(new QueryDepth(1));

        try {
            $schema = $this->createSchema(new TestSchemaBuilder(['_' . __FUNCTION__]));
            $this->runDepthLimitTest($queryDepth, $limit, $schema);
        } finally {
            $this->removeDocumentValidatorRule(QueryDepth::class);
        }
    }

    public function provideDefaultDepthLimit()
    {
        return $this->createProviderForComplexityOrDepth(15);
    }

    /**
     * @dataProvider provideCustomDepthLimit
     */
    public function testCustomDepthLimit(int $queryDepth, int $limit)
    {
        // This global rule should be ignored.
        DocumentValidator::addRule(new QueryDepth(1));

        try {
            $schema = $this->createSchema(new TestSchemaBuilder(['_' . __FUNCTION__]));
            $this->runDepthLimitTest($queryDepth, $limit, $schema);
        } finally {
            $this->removeDocumentValidatorRule(QueryDepth::class);
        }
    }

    public function provideCustomDepthLimit()
    {
        return $this->createProviderForComplexityOrDepth(25);
    }

    /**
     * @dataProvider provideCustomComplexityLimit
     */
    public function testCustomComplexityLimit(int $queryComplexity, int $limit)
    {
        // This global rule should be ignored.
        DocumentValidator::addRule(new QueryComplexity(1));

        try {
            $schema = $this->createSchema(new TestSchemaBuilder(['_' . __FUNCTION__]));
            $this->runComplexityLimitTest($queryComplexity, $limit, $schema);
        } finally {
            $this->removeDocumentValidatorRule(QueryComplexity::class);
        }
    }

    public function provideCustomComplexityLimit()
    {
        return $this->createProviderForComplexityOrDepth(10);
    }

    /**
     * @dataProvider provideDefaultNodeLimit
     */
    public function testDefaultNodeLimit(int $numNodes, int $limit)
    {
        $schema = $this->createSchema(new TestSchemaBuilder(['_' . __FUNCTION__]));
        $this->runNodeLimitTest($numNodes, $limit, $schema);
    }

    public function provideDefaultNodeLimit()
    {
        return $this->createProviderForComplexityOrDepth(500);
    }

    /**
     * @dataProvider provideCustomNodeLimit
     */
    public function testCustomNodeLimit(int $numNodes, int $limit)
    {
        $schema = $this->createSchema(new TestSchemaBuilder(['_' . __FUNCTION__]));
        $this->runNodeLimitTest($numNodes, $limit, $schema);
    }

    public function provideCustomNodeLimit()
    {
        return $this->createProviderForComplexityOrDepth(200);
    }

    public function testGlobalRuleNotRemoved()
    {
        // This global rule should NOT be ignored.
        DocumentValidator::addRule(new CustomValidationRule('never-passes', function (ValidationContext $context) {
            $context->reportError(new GraphQLError('This is the custom rule'));
            return [];
        }));

        try {
            $schema = $this->createSchema(new TestSchemaBuilder(['_' . __FUNCTION__]));
            $result = $this->querySchema($schema, $this->craftRecursiveQuery(15));
            $this->assertFailure($result);
            $this->assertErrorMatchingRegex($result, '/^This is the custom rule$/');
        } finally {
            $this->removeDocumentValidatorRule('never-passes');
        }
    }

    private function removeDocumentValidatorRule(string $ruleName): void
    {
        $reflectionRules = new ReflectionClass(DocumentValidator::class);
        $rules = $reflectionRules->getStaticPropertyValue('rules');
        unset($rules[$ruleName]);
        $reflectionRules->setStaticPropertyValue('rules', $rules);
    }

    private function createProviderForComplexityOrDepth(int $limit): array
    {
        return [
            'far less than limit' => [1, $limit],
            'one less than limit' => [$limit - 1, $limit],
            'exactly at the limit' => [$limit, $limit],
            'one more than limit' => [$limit + 1, $limit],
            'far more than limit' => [$limit + 25, $limit],
        ];
    }

    private function runDepthLimitTest(int $queryDepth, int $maxDepth, Schema $schema): void
    {
        $result = $this->querySchema($schema, $this->craftRecursiveQuery($queryDepth));
        if ($queryDepth > $maxDepth) {
            $this->assertFailure($result);
            $this->assertErrorMatchingRegex($result, '/^Max query depth should be ' . $maxDepth . ' but got ' . $queryDepth . '\.$/');
        } else {
            // Note that the depth limit is based on the depth of the QUERY, not of the RESULTS, so all we really care about
            // is that the query was successful, not what the results were.
            $this->assertSuccess($result);
        }
    }

    private function runComplexityLimitTest(int $queryComplexity, int $maxComplexity, Schema $schema): void
    {
        $result = $this->querySchema($schema, $this->craftComplexQuery($queryComplexity));
        if ($queryComplexity > $maxComplexity) {
            $this->assertFailure($result);
            $this->assertErrorMatchingRegex($result, '/^Max query complexity should be ' . $maxComplexity . ' but got ' . $queryComplexity . '\.$/');
        } else {
            // Note that the complexity limit is based on the complexity of the QUERY, not of the RESULTS, so all we really care about
            // is that the query was successful, not what the results were.
            $this->assertSuccess($result);
        }
    }

    private function runNodeLimitTest(int $queryNodeCount, int $maxNodes, Schema $schema): void
    {
        $result = $this->querySchema($schema, $this->craftComplexQuery($queryNodeCount - 1));
        if ($queryNodeCount > $maxNodes) {
            $this->assertFailure($result);
            $this->assertErrorMatchingRegex($result, '/^GraphQL query body must not be longer than ' . $maxNodes . ' nodes\.$/');
        } else {
            // Note that the complexity limit is based on the complexity of the QUERY, not of the RESULTS, so all we really care about
            // is that the query was successful, not what the results were.
            $this->assertSuccess($result);
        }
    }

    private function craftRecursiveQuery(int $queryDepth): string
    {
        $query = 'query{ readFakeSiteTrees { nodes {';

        for ($i = 0; $i < $queryDepth; $i++) {
            if ($i % 3 === 0) {
                $query .= 'id title';
            } elseif ($i % 3 === 1) {
                $query .= ' parent {';
            } elseif ($i % 3 === 2) {
                if ($i === $queryDepth - 1) {
                    $query .= 'id title';
                } else {
                    $query .= 'id title children { nodes {';
                }
            }
        }

        $endsWith = strrpos($query, 'id title') === strlen($query) - strlen('id title');
        $query .= $endsWith ? '' : 'id title';
        // Add all of the closing brackets
        $numChars = array_count_values(str_split($query));
        for ($i = 0; $i < $numChars['{']; $i++) {
            $query .= '}';
        }

        return $query;
    }

    private function craftComplexQuery(int $queryComplexity): string
    {
        $query = 'query{ readOneFakeSiteTree { id';

        // skip the first two complexity, because those are taken up by "readOneFakeSiteTree { id" above
        for ($i = 0; $i < $queryComplexity - 2; $i++) {
            $query .= ' id';
        }
        // Add all of the closing brackets
        $numChars = array_count_values(str_split($query));
        for ($i = 0; $i < $numChars['{']; $i++) {
            $query .= '}';
        }

        return $query;
    }

    /**
     * @param TestSchemaBuilder $factory
     * @return Schema
     * @throws SchemaBuilderException
     * @throws SchemaNotFoundException
     * @throws EmptySchemaException
     */
    private function createSchema(TestSchemaBuilder $factory): Schema
    {
        $this->clean();
        $schema = $factory->boot();

        /* @var Logger $logger */
        $logger = Injector::inst()->get(Logger::class);
        $logger->setVerbosity(Logger::ERROR);

        $factory->build($schema, true);

        // Register as the default SchemaBuilder so that any calls to
        // SchemaBuidler::singleton() get this TestSchemaBuilder
        Injector::inst()->registerService($factory, SchemaBuilder::class);

        return $schema;
    }

    /**
     * @param Schema $schema
     * @param string $query
     * @param array $variables
     * @return array
     * @throws SchemaNotFoundException
     */
    private function querySchema(Schema $schema, string $query, array $variables = [])
    {
        $builder = new TestSchemaBuilder();
        $graphQLSchena = $builder->fetchSchema($schema);
        $handler = new QueryHandler();
        $schemaContext = $builder->getConfig($schema->getSchemaKey());
        $handler->addContextProvider(SchemaConfigProvider::create($schemaContext));
        try {
            return $handler->query($graphQLSchena, $query, $variables);
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    private function clean()
    {
        $fs = new Filesystem();
        $fs->remove(__DIR__ . '/.graphql-generated');
    }

    private function assertSuccess(array $result)
    {
        $errors = $result['errors'] ?? [];
        $this->assertEmpty($errors, 'Failed to assert successful query. Got errors: ' . json_encode($errors, JSON_PRETTY_PRINT));
        $error = $result['error'] ?? null;
        $this->assertFalse((bool) $error, 'Failed to assert successful query. Got error: ' . $error);
    }

    private function assertFailure(array $result)
    {
        $errors = $result['errors'] ?? $result['error'] ?? [];
        if (empty($errors)) {
            $this->fail('Failed to assert that query was not successful');
        }
    }

    private function assertMissingField(array $result, string $fieldName)
    {
        $this->assertErrorMatchingRegex(
            $result,
            '/^Cannot query field "' . $fieldName . '"/',
            'Failed to assert that result was missing field "' . $fieldName . '"'
        );
    }

    private function assertErrorMatchingRegex(
        array $result,
        string $errorRegex,
        string $message = 'Failed to assert that expected error was present.'
    ) {
        $errors = $result['errors'] ?? [];
        if (isset($result['error'])) {
            $errors[] = ['message' => $result['error']];
        }
        $errorMessages = [];
        $foundError = false;
        foreach ($errors as $error) {
            if (!isset($error['message'])) {
                continue;
            }
            if (preg_match($errorRegex, $error['message'])) {
                $foundError = true;
                break;
            }
            $errorMessages[] = '"' . $error['message'] . '"';
        }
        $this->assertTrue(
            $foundError,
            $message . ' Regex was: ' . $errorRegex . ', Errors were: ' . implode(', ', $errorMessages)
        );
    }

    private function assertResults(array $expected, array $actual)
    {
        $this->assertEquals(json_encode($expected), json_encode($actual));
    }

    private function assertSchemaHasType(Schema $schema, string $type)
    {
        try {
            $graphQLSchema = (new TestSchemaBuilder())->fetchSchema($schema);
            $result = $graphQLSchema->getType($type);
            $this->assertInstanceOf(ObjectType::class, $result);
        } catch (\Exception $e) {
            $this->fail('Schema does not have type "' . $type . '"');
        }
    }

    private function assertSchemaNotHasType(Schema $schema, string $type)
    {
        try {
            (new TestSchemaBuilder())->fetchSchema($schema)->getType($type);
            $this->fail('Failed to assert that schema does not have type "' . $type . '"');
        } catch (\Exception $e) {
        }
    }

    private function assertResult(string $path, $value, array $result)
    {
        $data = $result['data'];
        $parts = explode('.', $path ?? '');
        $curr = $data;
        foreach ($parts as $part) {
            $next = $curr[$part] ?? null;
            if ($next === null) {
                $this->fail('Path "' . $path . '" does not exist on query. Failed on "' . $part . '"');
            }
            if (is_array($next)) {
                $curr = $next;
            } else {
                $this->assertEquals($value, $next);
            }
        }
    }

    public static function resolveCustomFilter($list, $args, $context)
    {
        $bool = $context['filterValue'];
        $comp = $context['filterComparator'];
        if ($comp === 'ne') {
            $bool = !$bool;
        }

        if ($bool) {
            $list = $list->filter('MyField:StartsWith', 'A');
        } else {
            $list = $list->exclude('MyField:StartsWith', 'A');
        }

        return $list;
    }
}
