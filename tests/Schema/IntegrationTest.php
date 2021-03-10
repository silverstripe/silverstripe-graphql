<?php


namespace SilverStripe\GraphQL\Tests\Schema;

use GraphQL\Type\Definition\ObjectType;
use SilverStripe\Assets\File;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\QueryHandler\QueryHandler;
use SilverStripe\GraphQL\QueryHandler\SchemaConfigProvider;
use SilverStripe\GraphQL\Schema\Exception\SchemaBuilderException;
use SilverStripe\GraphQL\Schema\Exception\SchemaNotFoundException;
use SilverStripe\GraphQL\Schema\Field\Query;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\SchemaBuilder;
use SilverStripe\GraphQL\Schema\Storage\CodeGenerationStore;
use SilverStripe\GraphQL\Schema\Storage\CodeGenerationStoreCreator;
use SilverStripe\GraphQL\Tests\Fake\DataObjectFake;
use SilverStripe\GraphQL\Tests\Fake\FakePage;
use SilverStripe\GraphQL\Tests\Fake\FakeProduct;
use SilverStripe\GraphQL\Tests\Fake\FakeProductPage;
use SilverStripe\GraphQL\Tests\Fake\FakeRedirectorPage;
use SilverStripe\GraphQL\Tests\Fake\FakeReview;
use SilverStripe\GraphQL\Tests\Fake\FakeSiteTree;
use SilverStripe\GraphQL\Tests\Fake\IntegrationTestResolver;
use SilverStripe\Security\Member;
use Symfony\Component\Filesystem\Filesystem;
use GraphQL\Type\Schema as GraphQLSchema;
use Exception;

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
    ];

    protected function setUp()
    {
        parent::setUp();
        TestStoreCreator::$dir = __DIR__;
    }

    protected function tearDown()
    {
        parent::tearDown();
        $this->clean();
        DataObjectFake::get()->removeAll();
        File::get()->removeAll();
        Member::get()->removeAll();
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

    public function testInheritance()
    {
        $this->markTestSkipped();
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
    id
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
                        'id' => false,
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
        $this->assertMissingField($result, 'id');

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

    public function testFilterAndSort()
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

        $file1 = File::create(['Title' => 'file1']);
        $file1->write();

        $file2 = File::create(['Title' => 'file2']);
        $file2->write();

        $dataObject1->Files()->add($file1);
        $dataObject1->Files()->add($file2);

        $id1 = $dataObject1->ID;
        $id2 = $dataObject2->ID;

        $schema = $this->createSchema(new TestSchemaBuilder([$dir]));

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

        $query = <<<GRAPHQL
query {
  readOneDataObjectFake(filter: { id: { ne: $id1 } }) {
    id
  }
}
GRAPHQL;
        $result = $this->querySchema($schema, $query);
        $this->assertSuccess($result);
        $this->assertResult('readOneDataObjectFake.id', $id2, $result);

        $query = <<<GRAPHQL
query {
  readOneDataObjectFake(sort: { myField: ASC }) {
    myField
  }
}
GRAPHQL;
        $result = $this->querySchema($schema, $query);
        $this->assertSuccess($result);
        $this->assertResult('readOneDataObjectFake.myField', 'test1', $result);

        $query = <<<GRAPHQL
query {
  readOneDataObjectFake(sort: { myField: DESC }) {
    myField
  }
}
GRAPHQL;
        $result = $this->querySchema($schema, $query);
        $this->assertSuccess($result);
        $this->assertResult('readOneDataObjectFake.myField', 'test2', $result);

        $query = <<<GRAPHQL
query {
  readOneDataObjectFake(sort: { myField: DESC }, filter: { id: { ne: $id2 } }) {
    myField
  }
}
GRAPHQL;
        $result = $this->querySchema($schema, $query);
        $this->assertSuccess($result);
        $this->assertResult('readOneDataObjectFake.myField', 'test1', $result);

        $query = <<<GRAPHQL
query {
  readOneDataObjectFake(filter: { author: { firstName: { eq: "tester1" } } }) {
    id
    author {
      firstName
    }
  }
}
GRAPHQL;
        $result = $this->querySchema($schema, $query);
        // Nested fields aren't working. Needs refactoring.
//        $this->assertSuccess($result);
//        $this->assertResult('readOneDataObjectFake.author.firstName', 'tester1', $result);

        $query = <<<GRAPHQL
query {
  readOneDataObjectFake(filter: { author: { firstName: { eq: "tester2" } } }) {
    id
    author {
      firstName
    }
  }
}
GRAPHQL;
        $result = $this->querySchema($schema, $query);

//        $this->assertSuccess($result);
//        $this->assertNull($result['data']['readOneDataObjectFake']);
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
        $this->assertTrue(in_array('1', $arr));
        $this->assertTrue(in_array('2', $arr));

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

    public function testQueriesAndMutations()
    {
        $schema = $this->createSchema(new TestSchemaBuilder(['_' . __FUNCTION__]));

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
            list($productPageID, $productID) = explode('__', $sku);
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
            list ($productPageID, $productID, $reviewID) = explode('__', $sku);
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

    /**
     * @param TestSchemaBuilder $factory
     * @return Schema
     * @throws SchemaBuilderException
     * @throws SchemaNotFoundException
     */
    private function createSchema(TestSchemaBuilder $factory): Schema
    {
        $this->clean();
        $schema = $factory->boot();
        $factory->build($schema, true);

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
        $fs->remove(__DIR__ . '/.graphql');
    }

    private function assertSuccess(array $result)
    {
        $errors = $result['errors'] ?? [];
        if (!empty($errors)) {
            $this->fail('Failed to assert successful query. Got errors: ' . json_encode($errors, JSON_PRETTY_PRINT));
        }
        $error = $result['error'] ?? null;
        if ($error) {
            $this->fail('Failed to assert successful query. Got error: ' . $error);
        }
    }

    private function assertFailure(array $result)
    {
        $errors = $result['errors'] ?? [];
        if (empty($errors)) {
            $this->fail('Failed to assert that query was not successful');
        }
    }

    private function assertMissingField(array $result, string $fieldName)
    {
        $errors = $result['errors'] ?? [];
        foreach ($errors as $error) {
            if (preg_match('/^Cannot query field "' . $fieldName . '"/', $error['message'])) {
                return;
            }
        }

        $this->fail('Failed to assert that result was missing field "' . $fieldName . '"');
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
        $parts = explode('.', $path);
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
}
