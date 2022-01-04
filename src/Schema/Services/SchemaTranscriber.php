<?php


namespace SilverStripe\GraphQL\Schema\Services;

use GraphQL\Executor\ExecutionResult;
use GraphQL\Server\OperationParams;
use SilverStripe\Assets\Storage\GeneratedAssetHandler;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Path;
use SilverStripe\GraphQL\QueryHandler\QueryHandler;
use GraphQL\Type\Schema as GraphQLSchema;
use Exception;
use SilverStripe\GraphQL\QueryHandler\QueryHandlerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Persists a graphql schema to a json document consumable by Apollo
 */
class SchemaTranscriber
{
    use Injectable;

    const CACHE_FILENAME = 'types.graphql';

    /**
     * @var GraphQLSchema
     */
    private $schema;

    /**
     * @var string
     */
    private $name;

    /**
     * @var GeneratedAssetHandler
     */
    protected $assetHandler;

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var string
     */
    private $rootDir;

    /**
     * SchemaTranscriber constructor.
     *
     * @param GraphQLSchema $schema
     * @param string $name
     * @param string $rootDir Storage path for the generated file.
     *                        Caution: This location may be used by frontend assets relying on GraphQL, e.g. silverstripe/assets.
     */
    public function __construct(GraphQLSchema $schema, string $name, string $rootDir = null)
    {
        $this->fs = new Filesystem();
        $this->schema = $schema;
        $this->name = $name;
        $this->rootDir = $rootDir ?: Path::join(PUBLIC_PATH, '_graphql');
    }

    /**
     * Introspect the schema and persist it to the filesystem
     * @throws Exception
     */
    public function writeSchemaToFilesystem()
    {
        try {
            $types = $this->introspectTypes();
        } catch (Exception $e) {
            throw new Exception(sprintf(
                'There was an error caching the GraphQL types: %s',
                $e->getMessage()
            ));
        }

        $this->writeTypes(json_encode($types));
    }

    /**
     * @return void
     */
    public function removeSchemaFromFilesystem(): void
    {
        $this->fs->remove($this->generateCacheFilename());
    }

    /**
     * @param string $content
     */
    public function writeTypes(string $content)
    {
        $this->fs->dumpFile($this->generateCacheFilename(), $content);
    }

    /**
     * @return string
     */
    public function getRootDir(): string
    {
        return $this->rootDir;
    }

    /**
     * @param string
     * @return SchemaTranscriber
     */
    public function setRootDir(string $rootDir): self
    {
        $this->rootDir = $rootDir;
        return $this;
    }

    /**
     * @return ExecutionResult
     * @throws Exception
     */
    private function introspectTypes(): ExecutionResult
    {
        $operation = OperationParams::create([
            'query' => <<<GRAPHQL
query IntrospectionQuery {
    __schema {
      types {
        kind
        name
        possibleTypes {
          name
        }
      }
    }
}
GRAPHQL
        ]);

        $handler = QueryHandler::create();
        /** @var ExecutionResult $executionResult */
        $executionResult = $handler->executeOperations($operation, $this->schema);

        if (!empty($executionResult->errors)) {
            $messages = array_map(function ($error) {
                return $error->getMessage();
            }, $executionResult->errors);

            throw new Exception(sprintf(
                'There were some errors with the introspection query: %s',
                implode(PHP_EOL, $messages)
            ));
        }

        return $executionResult;
    }

    /**
     * @return string
     */
    private function generateCacheFilename(): string
    {
        return Path::join(
            $this->rootDir,
            $this->name . '.' . self::CACHE_FILENAME
        );
    }
}
