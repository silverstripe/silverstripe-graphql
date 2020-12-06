<?php


namespace SilverStripe\GraphQL\Tests\Schema;


use SilverStripe\Dev\SapphireTest;
use SilverStripe\GraphQL\QueryHandler\QueryHandler;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\GraphQL\Schema\Storage\CodeGenerationStore;
use SilverStripe\GraphQL\Schema\Storage\CodeGenerationStoreCreator;
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
        $schema = $this->createSchema('_test2');
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
        $records = $result['data'];
        $this->assertCount(2, $records);
        $this->assertResults([
            ['field1' => 'foo', 'field2' => 2],
            ['field1' => 'bar', 'field2' => 3],
        ], $records);

    }

    private function createSchema(string $configDir, array $extraConfig = []): GraphQLSchema
    {
        $this->clean();
        $schema = new Schema('test');
        /* @var CodeGenerationStore $store */
        $store = (new CodeGenerationStoreCreator())->createStore('test');
        $store->setRootDir(__DIR__);
        $schema->setStore($store);

        $schema->applyConfig([
            'src' => __DIR__ . '/' . $configDir,
        ]);
        $schema->applyConfig($extraConfig);
        $schema->save();

        return $schema->fetch();
    }

    private function querySchema(GraphQLSchema $schema, string $query, array $variables = [])
    {
        $handler = new QueryHandler();
        try {
            $result = $handler->query($schema, $query, $variables);
            return [
                'data' => $result,
            ];
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
