<?php


namespace SilverStripe\GraphQL\Tests\Schema;


use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\QueryHandler\QueryHandler;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Storage\CodeGenerationStore;
use SilverStripe\GraphQL\Schema\Storage\CodeGenerationStoreCreator;
use SilverStripe\GraphQL\Tests\Fake\IntegrationTestResolverA;
use Symfony\Component\Filesystem\Filesystem;
use GraphQL\Type\Schema as GraphQLSchema;
use Exception;

class IntegrationTest extends SapphireTest
{
    protected function tearDownOnce()
    {
        parent::tearDownOnce();
        $this->clean();
    }

    public function testSimpleType()
    {
        $schema = $this->createSchema(__FUNCTION__, ['_test2'], [IntegrationTestResolverA::class]);
        $query = <<<GRAPHQL
query {
    readMyTypes {
        field1
        field2
    }
}
GRAPHQL;

        $result = $this->querySchema($schema, $query);
        $this->assertSuccess($result);
        $records = $result['data']['readMyTypes'] ?? [];
        $this->assertCount(2, $records);
        $this->assertResults([
            ['field1' => 'foo', 'field2' => 2],
            ['field1' => 'bar', 'field2' => 3],
        ], $records);

    }

    public function testSourceOverride()
    {
        $schema = $this->createSchema(__FUNCTION__, ['_test2', '_test2a'], [IntegrationTestResolverA::class]);
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

    private function createSchema(string $name, array $configDirs, array $resolvers = [], array $extraConfig = []): GraphQLSchema
    {
        $this->clean();
        Config::modify()->merge(
            Schema::class,
            'schemas',
            [
                $name => [
                    'src' => array_map(function ($dir) {
                        return __DIR__ . '/' . $dir;
                    }, $configDirs),
                ],
            ]
        );
        $schema = Schema::build($name);
        /* @var CodeGenerationStore $store */
        $store = (new CodeGenerationStoreCreator())->createStore($name);
        $store->setRootDir(__DIR__);
        $store->clear();
        $schema->setStore($store);
        $schema->getSchemaContext()->apply([
            'resolvers' => $resolvers
        ]);
        $schema->applyConfig($extraConfig);
        $schema->save();

        return $schema->fetch();
    }

    private function querySchema(GraphQLSchema $schema, string $query, array $variables = [])
    {
        $handler = new QueryHandler();
        try {
            return $handler->query($schema, $query, $variables);
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
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayNotHasKey('error', $result);
    }

    private function assertResults(array $expected, array $actual)
    {
        $this->assertEquals(json_encode($expected), json_encode($actual));
    }
}
